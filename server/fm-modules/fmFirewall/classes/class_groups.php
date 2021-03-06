<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2013 The facileManager Team                               |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | facileManager: Easy System Administration                               |
 | fmFirewall: Easily manage one or more software firewalls                |
 +-------------------------------------------------------------------------+
 | http://www.facilemanager.com/modules/fmfirewall/                        |
 +-------------------------------------------------------------------------+
*/

class fm_module_groups {
	
	/**
	 * Displays the group list
	 */
	function rows($result, $type) {
		global $fmdb, $allowed_to_manage_services;
		
		echo '			<table class="display_results" id="table_edits" name="groups">' . "\n";
		if (!$result) {
			echo '<p id="noresult">There are no groups defined.</p>';
		} else {
			echo <<<HTML
				<thead>
					<tr>
						<th>Group Name</th>
						<th>{$type}s</th>
						<th style="width: 40%;">Comment</th>
						<th width="110" style="text-align: center;">Actions</th>
					</tr>
				</thead>
				<tbody>

HTML;
					$num_rows = $fmdb->num_rows;
					$results = $fmdb->last_result;
					for ($x=0; $x<$num_rows; $x++) {
						$this->displayRow($results[$x]);
					}
					echo '</tbody>';
		}
		echo '</table>';
	}

	/**
	 * Adds the new group
	 */
	function add($post) {
		global $fmdb, $__FM_CONFIG;
		echo '<pre>';
		print_r($post);
		
		/** Validate entries */
		$post = $this->validatePost($post);
		if (!is_array($post)) return $post;
		
		$sql_insert = "INSERT INTO `fm_{$__FM_CONFIG[$_SESSION['module']]['prefix']}groups`";
		$sql_fields = '(';
		$sql_values = null;
		
		$post['account_id'] = $_SESSION['user']['account_id'];
		
		$exclude = array('submit', 'action', 'group_id', 'compress', 'AUTHKEY');

		foreach ($post as $key => $data) {
			$clean_data = sanitize($data);
			if (($key == 'group_name') && empty($clean_data)) return 'No group name defined.';
			if (!in_array($key, $exclude)) {
				$sql_fields .= $key . ',';
				$sql_values .= "'$clean_data',";
			}
		}
		$sql_fields = rtrim($sql_fields, ',') . ')';
		$sql_values = rtrim($sql_values, ',');
		
		$query = "$sql_insert $sql_fields VALUES ($sql_values)";
		$result = $fmdb->query($query);
		
		if (!$fmdb->result) return 'Could not add the group because a database error occurred.';

		addLogEntry("Added {$post['group_type']} group:\nName: {$post['group_name']}\n" .
				"Comment: {$post['group_comment']}");
		return true;
	}

	/**
	 * Updates the selected group
	 */
	function update($post) {
		global $fmdb, $__FM_CONFIG;
		
		/** Validate entries */
		$post = $this->validatePost($post);
		if (!is_array($post)) return $post;
		
		$exclude = array('submit', 'action', 'group_id', 'AUTHKEY');

		$sql_edit = null;
		
		foreach ($post as $key => $data) {
			if (!in_array($key, $exclude)) {
				$sql_edit .= $key . "='" . sanitize($data) . "',";
			}
		}
		$sql = rtrim($sql_edit, ',');
		
		// Update the group
		$old_name = getNameFromID($post['group_id'], 'fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'groups', 'group_', 'group_id', 'group_name');
		$query = "UPDATE `fm_{$__FM_CONFIG[$_SESSION['module']]['prefix']}groups` SET $sql WHERE `group_id`={$post['group_id']} AND `account_id`='{$_SESSION['user']['account_id']}'";
		$result = $fmdb->query($query);
		
		if (!$fmdb->result) return 'Could not update the group because a database error occurred.';
		
		/** Return if there are no changes */
		if (!$fmdb->rows_affected) return true;

//		setBuildUpdateConfigFlag(getServerSerial($post['group_id'], $_SESSION['module']), 'yes', 'build');
		
		addLogEntry("Updated {$post['group_type']} group '$old_name' to:\nName: {$post['group_name']}\n" .
					"Comment: {$post['group_comment']}");
		return true;
	}
	
	/**
	 * Deletes the selected group
	 */
	function delete($group_id) {
		global $fmdb, $__FM_CONFIG;
		
		/** Does the group_id exist for this account? */
		basicGet('fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'groups', $group_id, 'group_', 'group_id');
		if ($fmdb->num_rows) {
			/** Is the group_id present in a policy? */
			if (isItemInPolicy($group_id, 'group')) return 'This group could not be deleted because it is associated with one or more policies.';
			
			/** Delete group */
			$tmp_name = getNameFromID($group_id, 'fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'groups', 'group_', 'group_id', 'group_name');
			if (updateStatus('fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'groups', $group_id, 'group_', 'deleted', 'group_id')) {
				addLogEntry("Deleted group '$tmp_name'.");
				return true;
			}
		}
		
		return 'This group could not be deleted.';
	}


	function displayRow($row) {
		global $fmdb, $__FM_CONFIG, $allowed_to_manage_services;
		
		$disabled_class = ($row->group_status == 'disabled') ? ' class="disabled"' : null;
		
		$edit_status = $group_items = null;
		
		if ($allowed_to_manage_services) {
			$edit_status = '<a class="edit_form_link" name="' . $row->group_type . '" href="#">' . $__FM_CONFIG['icons']['edit'] . '</a>';
			if (!isItemInPolicy($row->group_id, 'group')) $edit_status .= '<a href="#" class="delete">' . $__FM_CONFIG['icons']['delete'] . '</a>';
			$edit_status = '<td id="edit_delete_img">' . $edit_status . '</td>';
		}
		
		/** Process group items */
		foreach (explode(';', $row->group_items) as $item) {
			$item_id = substr($item, 1);
			switch ($item[0]) {
				case 's':
					$item_name = basicGet('fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'services', $item_id, 'service_', 'service_id');
					if ($fmdb->num_rows) {
						$result = $fmdb->last_result[0];
						if ($result->service_type == 'icmp') {
							$group_items[] = $result->service_name . ' (type: ' . $result->service_icmp_type . ' code: ' . $result->service_icmp_code . ')';
						} else {
							$service_src_ports = $result->service_src_ports ? $result->service_src_ports : '0:0';
							$service_dest_ports = $result->service_dest_ports ? $result->service_dest_ports : '0:0';
							$group_items[] = $result->service_name . ' (' . $service_src_ports . ' / ' . $service_dest_ports . ')';
						}
					}
					break;
				case 'o':
					$item_name = basicGet('fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'objects', $item_id, 'object_', 'object_id');
					if ($fmdb->num_rows) {
						$result = $fmdb->last_result[0];
						$group_items[] = $result->object_name . ' (' . $result->object_address . ' / ' . $result->object_mask . ')';
					}
					break;
				case 'g':
					$group_items[] = getNameFromID($item_id, 'fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'groups', 'group_', 'group_id', 'group_name');
					break;
			}
		}
		$group_items = implode("<br />\n", $group_items);
		
		echo <<<HTML
			<tr id="$row->group_id"$disabled_class>
				<td>$row->group_name</td>
				<td>$group_items</td>
				<td>$row->group_comment</td>
				$edit_status
			</tr>

HTML;
	}

	/**
	 * Displays the form to add new group
	 */
	function printForm($data = '', $action = 'add', $group_type = 'service') {
		global $__FM_CONFIG;
		
		$group_id = 0;
		$group_name = $group_items = $group_comment = null;
		$ucaction = ucfirst($action);
		$uc_group_type = ucfirst($group_type) . 's';
		
		if (!empty($_POST) && !array_key_exists('is_ajax', $_POST)) {
			if (is_array($_POST))
				extract($_POST);
		} elseif (@is_object($data[0])) {
			extract(get_object_vars($data[0]));
		}
		
		$group_items_assigned = getGroupItems($group_items);

		$group_name_length = getColumnLength('fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'groups', 'group_name');
		$assigned_list = buildSelect(null, 'group_items_assigned', availableGroupItems($group_type, 'assigned', $group_items_assigned), null, 7, null, true);
		$available_list = buildSelect(null, 'group_items_available', availableGroupItems($group_type, 'available', $group_items_assigned, $group_id), null, 7, null, true);
		
		$return_form = <<<FORM
		<form name="manage" id="manage" method="post" action="{$group_type}-groups">
			<input type="hidden" name="action" value="$action" />
			<input type="hidden" name="group_id" value="$group_id" />
			<input type="hidden" name="group_type" value="$group_type" />
			<input type="hidden" name="group_items" id="group_items" value="$group_items" />
			<table class="form-table">
				<tr>
					<th width="33%" scope="row"><label for="group_name">Group Name</label></th>
					<td width="67%"><input name="group_name" id="group_name" type="text" value="$group_name" size="40" placeholder="http" maxlength="$group_name_length" /></td>
				</tr>
				<tr>
					<th width="33%" scope="row"><label for="group_items">$uc_group_type</label></th>
					<td width="67%">
						<table class="form-table list-toggle">
							<tbody>
								<tr>
									<th>Assigned</th>
									<th style="width: 50px;"></th>
									<th>Available</th>
								</tr>
								<tr>
									<td>$assigned_list</td>
									<td>
										<input type="button" id="buttonLeft" value="<" /><br />
										<input type="button" id="buttonRight" value=">" />
									</td>
									<td>$available_list</td>
								</tr>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<th width="33%" scope="row"><label for="group_comment">Comment</label></th>
					<td width="67%"><textarea id="group_comment" name="group_comment" rows="4" cols="30">$group_comment</textarea></td>
				</tr>
			</table>
			<input type="submit" name="submit" id="submit_items" value="$ucaction Group" class="button" />
			<input value="Cancel" class="button cancel" id="cancel_button" />
		</form>
FORM;

		return $return_form;
	}
	

	function validatePost($post) {
		global $fmdb, $__FM_CONFIG;
		
		if (empty($post['group_name'])) return 'No group name defined.';
		
		/** Check name field length */
		$field_length = getColumnLength('fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'groups', 'group_name');
		if ($field_length !== false && strlen($post['group_name']) > $field_length) return 'Group name is too long (maximum ' . $field_length . ' characters).';
		
		/** Does the record already exist for this account? */
		basicGet('fm_' . $__FM_CONFIG[$_SESSION['module']]['prefix'] . 'groups', $post['group_name'], 'group_', 'group_name', "AND group_type='{$post['group_type']}' AND group_id!={$post['group_id']}");
		if ($fmdb->num_rows) return 'This group name already exists.';
		
		/** Process assigned items */
		$post['group_items'] = trim($post['group_items'], ';');
		if (empty($post['group_items'])) return 'You must assign at least one ' . $post['group_type'] . '.';
		
		return $post;
	}
	

}

if (!isset($fm_module_groups))
	$fm_module_groups = new fm_module_groups();

?>
