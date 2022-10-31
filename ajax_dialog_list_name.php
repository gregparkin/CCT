<?php
/**
 * ajax_dialog_list_name.php
 *
 * @package   PhpStorm
 * @file      ajax_dialog_list_name.php
 * @author    gparkin
 * @date      08/23/16
 * @version   7.0
 *
 * @brief     Called by ajax request from ajax_dialog_list_name.php
 * @brief     Performs the following operations:
 *
 *            action = save       Retrieve system record from cct7_systems
 *            action = remove     Refresh data. (Same as get)
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

	ini_set("error_reporting",        "E_ALL & ~E_DEPRECATED & ~E_STRICT");
	ini_set("log_errors",             1);
	ini_set("error_log",              "/opt/ibmtools/cct7/logs/php-error.log");
	ini_set("log_errors_max_len",     0);
	ini_set("report_memleaks",        1);
	ini_set("track_errors",           1);
	ini_set("html_errors",            1);
	ini_set("ignore_repeated_errors", 0);
	ini_set("ignore_repeated_source", 0);

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
$lib->debug_start('ajax_dialog_list_name.html');
date_default_timezone_set('America/Denver');

$ora = new oracle();

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"));

//
// action values:
//   save            - Create or save list name
//   remove          - Remove list

$action       = 'save';
$list_name_id = 0;
$list_name    = 'Empty List Name';

if (isset($input->action))
    $action       = $input->action;

if (isset($input->list_name_id))
	$list_name_id = $input->list_name_id;

if (isset($input->list_name))
	$list_name    = $input->list_name;

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "action = %s",       $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "list_name_id = %d", $list_name_id);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "list_name    = %s", $list_name);

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
	$list_name_id = $ora->next_seq("cct7_list_namesseq");

	$rc = $ora->insert("cct7_list_names")
			  ->column("list_name_id")
			  ->column("create_date")
			  ->column("owner_cuid")
			  ->column("owner_name")
			  ->column("list_name")
			  ->value("int",  $list_name_id)
			  ->value("int",  $lib->now_to_gmt_utime())
			  ->value("char", $_SESSION['user_cuid'])
			  ->value("char", $_SESSION['user_name'])
			  ->value("char", $list_name)
			  ->execute();

	if ($rc == true)
	{
		$json['ajax_status']  = 'SUCCESS';
		$json['ajax_message'] = sprintf("Successfully created new list name: %s", $list_name);
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
	if ($list_name_id > 0)
	{
		$rc = $ora->update("cct7_list_names")
				  ->set("char",  "owner_cuid",        $_SESSION['user_cuid'])
				  ->set("char",  "owner_name",        $_SESSION['user_name'])
				  ->set("char",  "list_name",         $list_name)
				  ->where("int", "list_name_id", "=", $list_name_id)
				  ->execute();

		if ($rc == true)
		{
			$json['ajax_status']  = 'SUCCESS';
			$json['ajax_message'] = sprintf("Successfully saved list name: %s", $list_name);
			echo json_encode($json);
			exit();
		}

		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = 'Error: list_name_id = 0 on Save.';
	echo json_encode($json);
	exit();
}

//
// Remove list name and all servers pertaining to this list in cct7_list_names and cct7_list_servers.
//
if ($action == "remove")
{
	if ($list_name_id > 0)
	{
		$query = sprintf("delete from cct7_list_names where list_name_id = %d", $list_name_id);

		if ($ora->sql2($query) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $ora->error;
			echo json_encode($json);
			exit();
		}

		$ora->commit();

		$json['ajax_status']  = 'SUCCESS';
		$json['ajax_message'] = sprintf("Successfully removed list name: %s", $list_name);
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

