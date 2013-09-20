<?php

/**
 * Processes form posts
 *
 * @author		Jon LaBass
 * @version		$Id:$
 * @copyright	2013
 *
 */

if (!defined('AJAX')) define('AJAX', true);
require_once('../../../fm-init.php');

include(ABSPATH . 'fm-modules/fmSQLPass/classes/class_groups.php');
include(ABSPATH . 'fm-modules/fmSQLPass/classes/class_servers.php');

/** Edits */
if (is_array($_POST) && count($_POST) && $allowed_to_manage_servers) {
	if (array_key_exists('add_form', $_POST)) {
		$id = isset($_POST['item_id']) ? sanitize($_POST['item_id']) : null;
		$add_new = true;
	} elseif (array_key_exists('item_id', $_POST)) {
		$id = sanitize($_POST['item_id']);
		$view_id = isset($_POST['view_id']) ? sanitize($_POST['view_id']) : null;
		$add_new = false;
	} else returnError();
	
	$table = $__FM_CONFIG['fmSQLPass']['prefix'] . $_POST['item_type'];
	$item_type = $_POST['item_type'];
	$prefix = substr($item_type, 0, -1) . '_';
	$field = $prefix . 'id';
	$type_map = null;
	$action = 'add';
	
	/* Determine which class we need to deal with */
	switch($_POST['item_type']) {
		case 'groups':
			$post_class = $fm_sqlpass_groups;
			break;
		case 'servers':
			$post_class = $fm_sqlpass_servers;
			break;
	}
	
	if ($add_new) {
		$edit_form = '<h2>Add ' . substr(ucfirst($item_type), 0, -1) . '</h2>' . "\n";
		if ($_POST['item_type'] == 'logging') {
			$edit_form .= $post_class->printForm(null, $action, $_POST['item_sub_type']);
		} else {
			$edit_form .= $post_class->printForm(null, $action, $type_map, $id);
		}
	} else {
		$edit_form = '<h2>Edit ' . substr(ucfirst($item_type), 0, -1) . '</h2>' . "\n";
		basicGet('fm_' . $table, $id, $prefix, $field);
		$results = $fmdb->last_result;
		if (!$fmdb->num_rows) returnError();
		
		$edit_form_data[] = $results[0];
		if ($_POST['item_type'] == 'logging') {
			$edit_form .= $post_class->printForm($edit_form_data, 'edit', $_POST['item_sub_type']);
		} else {
			$edit_form .= $post_class->printForm($edit_form_data, 'edit', $type_map, $view_id);
		}
	}
	
	echo $edit_form;
} else returnUnAuth();

?>