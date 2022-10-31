<?php
/**
 * ajax_dialog_netpin_override_add.php
 *
 * @package   PhpStorm
 * @file      ajax_dialog_netpin_override_add.php
 * @author    gparkin
 * @date      07/23/17
 * @version   7.0
 *
 * @brief     Called by dialog_netpin_override_detail.php to add members to cct7_override_members
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
$lib->debug_start('ajax_dialog_netpin_override_add.html');
date_default_timezone_set('America/Denver');

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"), FALSE);
// $input = json_decode(json_encode($my_request), FALSE);

$netpin_id                = 0;
$target_these_only       = '';

if (isset($input->{'netpin_id'}))
	$netpin_id             = $input->netpin_id;

if (isset($input->{'target_these_only'}))
	$target_these_only        = $input->target_these_only;

$lib->debug1(__FILE__, __FUNCTION__, __LINE__,  "netpin_id = %s",          $netpin_id);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__,  "target_these_only = %s",  $target_these_only);

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

// Fix up $target_these_only
$str = str_replace(",", " ", $target_these_only);                          // Convert any commas to spaces
$target_these_only = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $str);  // Remove multiple spaces, tabs and newlines if present

$array_of_cuids = explode(" ", $target_these_only);  // Create an array of $array_of_cuids
$okay = true;
$already_in_list = "";
$not_in_mnet = "";
$count = 0;
$duplicates = array();

foreach ($array_of_cuids as $member_cuid)
{
	if (strlen($member_cuid) == 0)
		continue;

	$member_cuid = strtolower($member_cuid);

	//
	// Avoid duplicates
	//
	if (array_key_exists($member_cuid, $duplicates))
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Duplicate detected: %s", $member_cuid);
		continue;
	}

	$duplicates[$member_cuid] = "There be whales here captain!";

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "member_cuid: %s", $member_cuid);

	//
	// Make sure we haven't already added it.
	//
	$query = sprintf(
		"select * from cct7_override_members where netpin_id = %d and member_cuid = '%s'",
		$netpin_id, $member_cuid);

	if ($ora->sql2($query) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	if ($ora->fetch())
	{
		if ($already_in_list == "")
		{
			$already_in_list = $member_cuid;
		}
		else
		{
			$already_in_list .= ", " . $member_cuid;
		}

		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Already in the list: %s", $member_cuid);
		continue;
	}

	//
	// Check to see if this cuid is found in MNET
	//
	$query = sprintf("select * from cct7_mnet where mnet_cuid = '%s'", $member_cuid);

	if ($ora->sql2($query) == false)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s - %s", $ora->sql_statement, $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	$member_cuid = "";
	$member_name = "";

	if ($ora->fetch() == false)
	{
		if ($not_in_mnet == "")
		{
			$not_in_mnet = $member_cuid;
		}
		else
		{
			$not_in_mnet .= ", " . $member_cuid;
		}

		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Not found in cct7_mnet: %s", $member_cuid);
		$okay = false;
	}
	else
	{
		$member_cuid = $ora->mnet_cuid;
		$member_name = $ora->mnet_name;
	}

	//
	// Okay now add it to cct7_override_members
	//
	if ($okay)
	{
		$count += 1;

		//
		// cct7_override_members
		// member_id|NUMBER|0|NOT NULL|PK: Unique record ID
		// netpin_id|NUMBER|0||FK: cct7_override_netpins.netpin_id
		// create_date|NUMBER|0||Date record was created. (GMT unix timestamp)
		// create_cuid|VARCHAR2|20||CUID of person who created this record
		// create_name|VARCHAR2|200||Name of person who created this record
		// member_cuid|VARCHAR2|20||CUID of person who will receive notifications
		// member_name|VARCHAR2|200||Name of person who will receive notifications
		//
		$member_id = $ora->next_seq("cct7_override_membersseq");

		$rc = $ora->insert("cct7_override_members")
				  ->column("member_id")
				  ->column("netpin_id")
				  ->column("create_date")
				  ->column("create_cuid")
				  ->column("create_name")
				  ->column("member_cuid")
				  ->column("member_name")
				  ->value("int",  $member_id)
				  ->value("int",  $netpin_id)
				  ->value("int",  $lib->now_to_gmt_utime())
				  ->value("char", $_SESSION['user_cuid'])
				  ->value("char", $_SESSION['user_name'])
				  ->value("char", $member_cuid)
				  ->value("char", $member_name)
				  ->execute();

		if ($rc == false)
		{
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s - %s", $ora->sql_statement, $ora->dbErrMsg);
			$json['ajax_status']  = 'FALSE';
			$json['ajax_message'] = $ora->dbErrMsg;
			echo json_encode($json);
			exit();
		}
	}
}

if (strlen($already_in_list) > 0 && strlen($not_in_mnet) > 0)
{
	$msg = sprintf("Add failed because: Already in list: %s, Not in MNET: %s",
				   $already_in_list, $not_in_mnet);

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $msg);
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $msg;
	echo json_encode($json);
	exit();
}
else if (strlen($already_in_list) > 0)
{
	$msg = sprintf("Add failed because: Already in list: %s", $already_in_list);
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $msg;
	echo json_encode($json);
	exit();
}
else if (strlen($not_in_mnet) > 0)
{
	$msg = sprintf("Add failed because: Not in MNET: %s", $not_in_mnet);
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $msg;
	echo json_encode($json);
	exit();
}

$json['ajax_status']  = 'SUCCESS';
$json['ajax_message'] = sprintf("Total members added: %d", $count);
echo json_encode($json);
exit();
