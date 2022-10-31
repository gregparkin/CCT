<?php
/**
 * ajax_dialog_system_list_import.php
 *
 * @package   PhpStorm
 * @file      ajax_dialog_system_list_import.php
 * @author    gparkin
 * @date      7/1/16
 * @version   7.0
 *
 * @brief     Called by ajax request from dialog_list_systems_import.php
 * @brief     Import servers into server list.
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
$lib->debug_start('ajax_dialog_system_import.html');
date_default_timezone_set('America/Denver');

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"), FALSE);
// $input = json_decode(json_encode($my_request), FALSE);

$list_name_id            = 0;
$computer_managing_group = Array();
$computer_os_lite        = Array();
$computer_status         = Array();
$computer_contract       = Array();
$state_and_city          = Array();
$miscellaneous           = Array();
$target_these_only       = '';
$ip_starts_with          = '';

if (isset($input->{'list_name_id'}))
	$list_name_id             = $input->list_name_id;

if (isset($input->{'computer_managing_group'}))
    $computer_managing_group  = $input->computer_managing_group;

if (isset($input->{'computer_os_lite'}))
    $computer_os_lite         = $input->computer_os_lite;

if (isset($input->{'computer_status'}))
	$computer_status          = $input->computer_status;

if (isset($input->{'computer_contract'}))
	$computer_contract        = $input->computer_contract;

if (isset($input->{'state_and_city'}))
	$state_and_city           = $input->state_and_city;

if (isset($input->{'miscellaneous '}))
	$miscellaneous            = $input->miscellaneous;

if (isset($input->{'target_these_only'}))
	$target_these_only        = $input->target_these_only;

if (isset($input->{'ip_starts_with'}))
	$ip_starts_with           = $input->ip_starts_with;

$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "list_name_id = %s",      $list_name_id);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "computer_managing_group: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $computer_managing_group);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "computer_os_lite: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $computer_os_lite);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "computer_status: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $computer_status);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "computer_contract: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $computer_contract);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "state_and_city: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $state_and_city);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "miscellaneous: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $miscellaneous);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,  "target_these_only = %s",  $target_these_only);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,  "ip_starts_with = %s",     $ip_starts_with);

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

$ora = new oracle();
$sys = new cct7_systems();

$sys->target_these_only       = $target_these_only;
$sys->computer_managing_group = $computer_managing_group;
$sys->computer_os_lite        = $computer_os_lite;
$sys->computer_status         = $computer_status;
$sys->computer_contract       = $computer_contract;
$sys->state_and_city          = $state_and_city;
$sys->miscellaneous           = $miscellaneous;
$sys->ip_starts_with          = $ip_starts_with;

$x = 0;

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "running getTheseOnly()");

if (($i = $sys->getTheseOnly()) == -1)
{
	$lib->debug4(__FILE__, __FUNCTION__, __LINE__, "%s", $sys->error);
	return false;
}

$x += $i;

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "running getAssetCenter()");

// 1: ajax_dialog_system_list_import.php 108: target_these_only = lxomp47x
if (($i = $sys->getAssetCenter()) == -1)
{
	$lib->debug4(__FILE__, __FUNCTION__, __LINE__, "%s", $sys->error);
	return false;
}

$x += $i;

if ($x == 0)
{
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = sprintf("Servers not found in asset manager.");
	echo json_encode($json);
	exit();
}

//
// Copy the servers currently in this list to our $sys (cct7_systems.php) object.
//
$query  = sprintf("select * from cct7_list_systems where list_name_id = %d", $list_name_id);

if ($ora->sql2($query) == false)
{
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $ora->error;
	echo json_encode($json);
	exit();
}

while ($ora->fetch())
	$sys->addHostname($ora->computer_hostname);

//
// Delete the current list.
//
$query = sprintf("delete from cct7_list_systems where list_name_id = %d", $list_name_id);

if ($ora->sql2($query) == false)
{
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $ora->error;
	echo json_encode($json);
	exit();
}

$ora->commit();
$count = 0;

//
// Add the servers back into this list and include any new servers.
//
foreach ($sys->servers as $hostname => $lastid)
{
	++$count;

	$query  = "select ";
	$query .= "  computer_hostname, ";
	$query .= "  computer_ip_address, ";
	$query .= "  computer_os_lite, ";
	$query .= "  computer_status, ";
	$query .= "  computer_managing_group ";
	$query .= "from ";
	$query .= "  cct7_computers ";
	$query .= "where ";
	$query .= "  computer_lastid = " . $lastid;

	if ($ora->sql2($query) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	if ($ora->fetch())
	{
		//
		// Add this server record to cct7_list_systems
		//
		$list_system_id = $ora->next_seq("cct7_list_systemsseq");

		// list_system_id|NUMBER|0|NOT NULL|Unique record ID
		// list_name_id|NUMBER|0||
		// create_date|NUMBER|0||Date record was created. (GMT unix timestamp)
		// owner_cuid|VARCHAR2|20||CUID of person inserting the record
		// owner_name|VARCHAR2|200||Name of person inserting the record
		// computer_hostname|VARCHAR2|255||Computer hostname
		// computer_ip_address|VARCHAR2|64||Computer IP address
		// computer_os_lite|VARCHAR2|20||Computer short OS Name: HPUX
		// computer_status|VARCHAR2|80||Computer status: PRODUCTION, TEST, DEVELOPMENT
		// computer_managing_group|VARCHAR2|40||Managing group like: CMP-UNIX (shorter version of COMPUTER_CIO_GROUP)

		$query  = "insert into cct7_list_systems (";
		$query .= "  list_system_id, ";
		$query .= "  list_name_id, ";
		$query .= "  create_date, ";
		$query .= "  owner_cuid, ";
		$query .= "  owner_name, ";
		$query .= "  computer_hostname, ";
		$query .= "  computer_ip_address, ";
		$query .= "  computer_os_lite, ";
		$query .= "  computer_status, ";
		$query .= "  computer_managing_group ";
		$query .= ") values ( ";
		$query .= sprintf("%d, ",   $list_system_id);
		$query .= sprintf("%d, ",   $list_name_id);
		$query .= sprintf("%d, ",   $lib->now_to_gmt_utime());
		$query .= sprintf("'%s', ", $_SESSION['user_cuid']);
		$query .= sprintf("'%s', ", $_SESSION['user_name']);
		$query .= sprintf("'%s', ", $ora->computer_hostname);
		$query .= sprintf("'%s', ", $ora->computer_ip_address);
		$query .= sprintf("'%s', ", $ora->computer_os_lite);
		$query .= sprintf("'%s', ", $ora->computer_status);
		$query .= sprintf("'%s') ", $ora->computer_managing_group);

		if ($ora->sql2($query) == false)
		{
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = $ora->error . " Unable to insert: " . $computer_hostname;
			echo json_encode($json);
			exit();
		}
	}
}

$ora->commit();

if ($ora->sql("select * from cct7_list_names where list_name_id = %d", $list_name_id) == false)
{
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $ora->error;
	echo json_encode($json);
	exit();
}

$list_name = '';

if ($ora->fetch() == true)
{
	$list_name = sprintf("into list: %s", $ora->list_name);
}

$json['ajax_status']  = 'SUCCESS';
$json['ajax_message'] = sprintf("Server Name: %s, Total servers: %d", $list_name, $count);
echo json_encode($json);
exit();
