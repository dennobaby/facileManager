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
*/

/**
 * Contains permission ACLs for facileManager
 *
 * @package facileManager
 *
 */

if (!defined('PERM_FM_SUPER_ADMIN'))		define('PERM_FM_SUPER_ADMIN', 1);
if (!defined('PERM_FM_MODULE_MANAGEMENT'))	define('PERM_FM_MODULE_MANAGEMENT', 2);
if (!defined('PERM_FM_USER_MANAGEMENT'))	define('PERM_FM_USER_MANAGEMENT', 4);
if (!defined('PERM_FM_RUN_TOOLS'))			define('PERM_FM_RUN_TOOLS', 8);
if (!defined('PERM_FM_MANAGE_SETTINGS'))	define('PERM_FM_MANAGE_SETTINGS', 16);

/** Get permissions */
if (isset($_SESSION['user'])) {
	$super_admin = $_SESSION['user']['fm_perms'] & PERM_FM_SUPER_ADMIN;
	$allowed_to_manage_modules = ($_SESSION['user']['fm_perms'] & PERM_FM_MODULE_MANAGEMENT) || ($_SESSION['user']['fm_perms'] & PERM_FM_SUPER_ADMIN);
	$allowed_to_run_tools = ($_SESSION['user']['fm_perms'] & PERM_FM_RUN_TOOLS) || ($_SESSION['user']['fm_perms'] & PERM_FM_SUPER_ADMIN);
	$allowed_to_manage_users = ($_SESSION['user']['fm_perms'] & PERM_FM_USER_MANAGEMENT) || ($_SESSION['user']['fm_perms'] & PERM_FM_SUPER_ADMIN);
	$allowed_to_manage_settings = ($_SESSION['user']['fm_perms'] & PERM_FM_MANAGE_SETTINGS) || ($_SESSION['user']['fm_perms'] & PERM_FM_SUPER_ADMIN);
}

?>