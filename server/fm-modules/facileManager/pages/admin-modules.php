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
 +-------------------------------------------------------------------------+
 | http://www.facilemanager.com/                                           |
 +-------------------------------------------------------------------------+
 | Processes module management page                                        |
 | Author: Jon LaBass                                                      |
 +-------------------------------------------------------------------------+
*/

$page_name = 'Admin';
$page_name_sub = 'Modules';

include(ABSPATH . 'fm-modules' . DIRECTORY_SEPARATOR . $fm_name . DIRECTORY_SEPARATOR . 'permissions.inc.php');
include(ABSPATH . 'fm-modules' . DIRECTORY_SEPARATOR . $fm_name . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'class_tools.php');

$output = $avail_modules = $response = null;
$import_output = '<p>Processing...</p>';

$disabled = $super_admin ? null : 'disabled';

if (array_key_exists('action', $_GET) && array_key_exists('module', $_GET)) {
	if ($allowed_to_manage_modules) {
		if ($fm_tools->manageModule(sanitize($_GET['action']), sanitize($_GET['module']))) {
			$response = $_GET['module'] . ' was ' . sanitize($_GET['action']);
			$response .= substr(sanitize($_GET['action']), -1) == 'e' ? 'd.' : 'ed.';
			addLogEntry($response, $fm_name);
			
			if ($_GET['module'] == $_SESSION['module']) $_SESSION['module'] = $fm_name;
			
			header('Location: ' . $GLOBALS['basename']);
		} else {
			$response = 'This module could not be ' . sanitize($_GET['action']);
			$response .= substr(sanitize($_GET['action']), -1) == 'e' ? 'd.' : 'ed.';
		}
	} else header('Location: ' . $GLOBALS['basename']);
}

require(ABSPATH . 'fm-includes/version.php');
$fm_new_version_available = isNewVersionAvailable($fm_name, $fm_version);

printHeader();
@printMenu($page_name, $page_name_sub);

echo '<div id="body_container">';
if (!empty($response) || !empty($fm_new_version_available)) echo '<div id="response">' . $fm_new_version_available . '<p>' . $response . '</p></div>';

$modules = getAvailableModules();
if (count($modules)) {
	$module_display = <<<HTML
			<p>The following modules have been detected:</p>
			<table class="display_results">
				<thead>
					<tr>
						<td>Module Name</td>
						<td>Description</td>
						<td>Version</td>
						<td>Status</td>
					</tr>
				</thead>
				<tbody>
HTML;

	foreach ($modules as $module_name) {
		/** Include module variables */
		@include(ABSPATH . 'fm-modules/' . $module_name . '/variables.inc.php');
		
		$activate_link = null;
		
		/** Get module status */
		$module_version = getOption(strtolower($module_name) . '_version', 0);
		if ($module_version !== false) {
			if (version_compare($module_version, $__FM_CONFIG[$module_name]['version'], '>=')) {
				if (in_array($module_name, getActiveModules())) {
					if ($allowed_to_manage_modules) {
						$activate_link = '<br /><a href="?action=deactivate&module=' . $module_name . '">Deactivate</a>' . "\n";
					}
				} else {
					if ($allowed_to_manage_modules) {
						$activate_link = '<br /><a href="?action=activate&module=' . $module_name . '">Activate</a>' . "\n";
						$activate_link .= ' | <a href="?action=uninstall&module=' . $module_name . '"><span class="not_installed" onClick="return del(\'Are you sure you want to delete this module?\');">Uninstall</span></a>' . "\n";
					}
				}
			} else {
				if ($allowed_to_manage_modules) {
					include(ABSPATH . 'fm-includes/version.php');
					if (version_compare($fm_version, $__FM_CONFIG[$module_name]['required_fm_version']) >= 0) {
						$activate_link = '<br /><input id="module_upgrade" name="' . $module_name . '" type="submit" value="Upgrade Now" class="button" />' . "\n";
					} else {
						$activate_link .= '<br />' . $fm_name . ' v' . $__FM_CONFIG[$module_name]['required_fm_version'] . ' or later is required<br />before this module can be upgraded.';
					}
				} else $activate_link = '<br />Upgrade available' . "\n";
			}
			$status_options = '<p><span class="installed">Installed</span>' . $activate_link . "<p>\n";
		} else {
			$status_options = '<span class="not_installed">Not Installed</span> ';
			$module_version = $__FM_CONFIG[$module_name]['version'];
			if ($allowed_to_manage_modules) {
				include(ABSPATH . 'fm-includes/version.php');
				if (version_compare($fm_version, $__FM_CONFIG[$module_name]['required_fm_version']) >= 0) {
					$status_options .= '<input id="module_install" name="' . $module_name . '" type="submit" value="Install Now" class="button" />';
				} else {
					$status_options .= '<br />' . $fm_name . ' v' . $__FM_CONFIG[$module_name]['required_fm_version'] . ' or later is required.';
				}
			}
		}
		
		$module_new_version_available = isNewVersionAvailable($module_name, $module_version);
		
		$avail_modules .= <<<MODULE
					<tr>
						<td><b>$module_name</b></td>
						<td><p>{$__FM_CONFIG[$module_name]['description']}</p>$module_new_version_available</td>
						<td>$module_version</td>
						<td>$status_options</td>
					</tr>
MODULE;
	}
	
	$module_display .= <<<HTML
					$avail_modules
				</tbody>
			</table>
HTML;
} else {
	$module_display = '<p>There are no modules detected. You must first install the files in <code>' . ABSPATH . 'fm-modules</code> and then return to this page.</p>' . "\n";
	$module_display .= '<p>If you don\'t have any modules, you can download them from the <a href="http://www.facilemanager.com/modules/">module directory</a>.</p>' . "\n";
}

echo <<<HTML
	<div id="admin-tools">
		<form enctype="multipart/form-data" method="post" action="">
			<h2>Module Configuration</h2>
			$module_display
		</form>
	</div>
</div>
HTML;

printFooter($output);

?>
