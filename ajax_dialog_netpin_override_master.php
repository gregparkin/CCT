<?php
/**
 * ajax_dialog_netpin_override_master.php
 *
 * @package   PhpStorm
 * @file      ajax_dialog_netpin_override_master.php
 * @author    gparkin
 * @date      07/23/2017
 * @version   7.0
 *
 * @brief     Called by ajax request from dialog_netpin_override_master.php
 * @brief     Performs the following operations:
 *
 *            action = add     Add new NET Group pin override list
 *            action = delete  Delete selected NET Group pin override list
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
$lib->debug_start('ajax_dialog_netpin_override_master.html');
date_default_timezone_set('America/Denver');

$ora = new oracle();

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"));

$action    = '';
$netpin_id = 0;
$netpin_no = '';

if (isset($input->action))
    $action     = $input->action;

if (isset($input->netpin_id))
	$netpin_id  = $input->netpin_id;

if (isset($input->netpin_no))
	$netpin_no  = $input->netpin_no;

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "action    = %s", $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "netpin_id = %d", $netpin_id);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "netpin_no = %s", $netpin_no);

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
	if (strlen($netpin_no) == 0)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = "You must type in a valid NET Group pin number before clicking the Add button.";
		echo json_encode($json);
		exit();
	}

	//
	// Check to see if this is a valid netpin
	//
	$query  = sprintf(
		"select * from cct7_netpin_to_cuid where net_pin_no = '%s'", $netpin_no);

	if ($ora->sql2($query) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	if ($ora->fetch() == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] =
			sprintf("The NET Group pin you typed is not currently found in our nightly copy of NET: %s. ",
					$netpin_no);
		echo json_encode($json);
		exit();
	}

	//
	// Make sure this $netpin_no is not found current being used in cct7_override_netpins
	//
	$query = sprintf("select * from cct7_override_netpins where netpin_no = '%s'", $netpin_no);

	if ($ora->sql2($query) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	if ($ora->fetch())
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] =
			sprintf("NET Group pin: %s is already being used in the override list. Local the netpin and select the record to see the members.", $netpin_no);
		echo json_encode($json);
		exit();
	}

	$netpin_id = $ora->next_seq("cct7_override_netpinsseq");

	$rc = $ora->insert("cct7_override_netpins")
			  ->column("netpin_id")
			  ->column("create_date")
			  ->column("create_cuid")
			  ->column("create_name")
			  ->column("netpin_no")
			  ->value("int", $netpin_id)
			  ->value("int",  $lib->now_to_gmt_utime())
			  ->value("char", $_SESSION['user_cuid'])
			  ->value("char", $_SESSION['user_name'])
			  ->value("char", $netpin_no)
			  ->execute();

	if ($rc == true)
	{
		$json['ajax_status']  = 'SUCCESS';
		$json['ajax_message'] =
			sprintf("NET group pin %s has been created. You can now add your override members",
					$netpin_no);
		echo json_encode($json);
		exit();
	}

	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $ora->error;
	echo json_encode($json);
	exit();
}

//
// Remove the override list.
//
if ($action == "delete")
{
	if ($netpin_id > 0)
	{
		$query = sprintf("delete from cct7_override_netpins where netpin_id = %d", $netpin_id);

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
		$json['ajax_message'] = sprintf("Successfully removed NET Group pin override list for %s",
										$netpin_no);
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

