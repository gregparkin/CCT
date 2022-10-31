<?php
/**
 * ajax_dialog_subscriber_group.php
 *
 * @package   PhpStorm
 * @file      ajax_dialog_subscriber_group.php
 * @author    gparkin
 * @date      11/23/16
 * @version   7.0
 *
 * @brief     Called by ajax request from dialog_subscriber_group.php
 * @brief     Performs the following operations:
 *
 *            action = delete  Delete subscriber list identified by group_id 
 *            action = add     Add new subscriber list.
 *            action = save    Save new text for group_name identified by group_id
 *
 * @brief     Perform function for creating new list names, editing list names or removing lists.
 *
 */

set_include_path("/opt/ibmtools/www/cct7/classes");

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
    require_once('classes/autoloader.php');
}

if (session_id() == '')
	session_start();

if (isset($_SESSION['user_cuid']) && $_SESSION['user_cuid'] == 'gparkin')
{
	ini_set('xdebug.collect_vars',    '5');
	ini_set('xdebug.collect_vars',    'on');
	ini_set('xdebug.collect_params',  '4');
	ini_set('xdebug.dump_globals',    'on');
	ini_set('xdebug.dump.SERVER',     'REQUEST_URI');
	ini_set('xdebug.show_local_vars', 'on');

	//$path = '/usr/lib/pear';
	//set_include_path(get_include_path() . PATH_SEPARATOR . $path);
}
else
{
	ini_set('display_errors', 'Off');
}

// Prevent caching.
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');

// The JSON standard MIME header.
header('Content-type: application/json');

//
// NOTE: It is very important that you do not turn on debugging without writing to a file for server side AJAX code. Any HTML
//       comments coming from functions while writing JSON will show up in the JSON output and you will get a parsing error
//       in the client side program.
//
$lib = new library();        // classes/library.php
$lib->debug_start('ajax_dialog_subscriber_group.html');
date_default_timezone_set('America/Denver');

$ora = new oracle();

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"));

//
// action values:
//   save            - Create or save list name
//   remove          - Remove list

$action       = 'save';
$group_id     = 0;
$group_name   = 'Empty List Name';

if (isset($input->action))
    $action     = $input->action;

if (isset($input->group_id))
	$group_id   = $input->group_id;

if (isset($input->group_name))
	$group_name = $input->group_name;

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "action     = %s", $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "group_id   = %s", $group_id);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "group_name = %s", $group_name);

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "input: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $input);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_POST: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_POST);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_GET: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_GET);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_REQUEST: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_REQUEST);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_SERVER: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_SERVER);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_SESSION: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_SESSION);

$json = array();

//
// Add new list
//
if ($action == "add")
{
	$group_id = 'SUB' . $ora->next_seq("cct7_subscriber_groupsseq");

	// cct7_subscriber_groups
	// group_id|VARCHAR2(20|NOT NULL|PK: Unique Record ID
	// create_date|NUMBER|0||GMT date record was created
	// owner_cuid|VARCHAR2|20||Owner CUID of this subscriber list
	// owner_name|VARCHAR2|200||Owner NAME of this subscriber list
	// group_name|VARCHAR2|200||Group Name

	$rc = $ora->insert("cct7_subscriber_groups")
			  ->column("group_id")
			  ->column("create_date")
			  ->column("owner_cuid")
			  ->column("owner_name")
			  ->column("group_name")
			  ->value("char", $group_id)
			  ->value("int",  $lib->now_to_gmt_utime())
			  ->value("char", $_SESSION['user_cuid'])
			  ->value("char", $_SESSION['user_name'])
			  ->value("char", $group_name)
			  ->execute();

	if ($rc == true)
	{
		$json['ajax_status']  = 'SUCCESS';
		$json['ajax_message'] = sprintf("Successfully created new list name: %s", $group_name);
		echo json_encode($json);
		exit();
	}

	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $ora->error;
	echo json_encode($json);
	exit();
}

if ($action == "save")
{
	if (strlen($group_id) > 0)
	{
		// cct7_subscriber_groups
		// group_id|VARCHAR2(20|NOT NULL|PK: Unique Record ID
		// create_date|NUMBER|0||GMT date record was created
		// owner_cuid|VARCHAR2|20||Owner CUID of this subscriber list
		// owner_name|VARCHAR2|200||Owner NAME of this subscriber list
		// group_name|VARCHAR2|200||Group Name

		$rc = $ora->update("cct7_subscriber_groups")
				  ->set("char",   "owner_cuid",    $_SESSION['user_cuid'])
				  ->set("char",   "owner_name",    $_SESSION['user_name'])
				  ->set("char",   "group_name",    $group_name)
				  ->where("char", "group_id", "=", $group_id)
				  ->execute();

		if ($rc == true)
		{
			$json['ajax_status']  = 'SUCCESS';
			$json['ajax_message'] = sprintf("Successfully saved list name: %s", $group_name);
			echo json_encode($json);
			exit();
		}

		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = 'Error: group_id = 0 on Save.';
	echo json_encode($json);
	exit();
}

//
// Remove list name and all servers pertaining to this list in cct7_subscriber_groups and cct7_list_servers.
//
if ($action == "delete")
{
	if (strlen($group_id) > 0)
	{
		$query = sprintf("delete from cct7_subscriber_groups where group_id = '%s'", $group_id);

		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, $query);

		if ($ora->sql2($query) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $ora->error;
			echo json_encode($json);
			exit();
		}

		$ora->commit();

		$json['ajax_status']  = 'SUCCESS';
		$json['ajax_message'] = sprintf("Successfully removed list name: %s", $group_name);
		echo json_encode($json);
		exit();
	}

	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $ora->error;
	echo json_encode($json);
	exit();
}

$json['ajax_status']  = 'FAILED';
$json['ajax_message'] = 'Unknown action';
echo json_encode($json);
exit();

