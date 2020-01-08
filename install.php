<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif(!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify that you put this file in the same place as SMF\'s index.php and SSI.php files.');

if ((SMF == 'SSI') && !$user_info['is_admin'])
	die('Admin privileges required.');

$result = $smcFunc['db_query']('', "SELECT id_field FROM {db_prefix}custom_fields WHERE col_name LIKE 'cust_likes' LIMIT 1", array());
if (!$smcFunc['db_num_rows']($result))
	$smcFunc['db_query']('', "INSERT INTO {db_prefix}custom_fields (col_name, field_name, field_desc, field_type, field_length, field_options, field_order, mask, show_reg, show_display, show_mlist, show_profile, private, active, bbc, can_search, default_value, enclose, placement) VALUES ('cust_likes', 'Karma', '', 'text', 0, '', 5, 'number', 0, 1, 0, 'forumprofile', 1, 1, 0, 0, '', '{INPUT}', 0)");

if (SMF == 'SSI')
	echo 'Database changes are complete! Please wait...';
