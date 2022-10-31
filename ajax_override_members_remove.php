<?php
/**
 * ajax_override_members_remove.php
 *
 * @package   PhpStorm
 * @file      ajax_override_members_remove.php
 * @author    gparkin
 * @date      07/24/17
 * @version   7.0
 *
 * @brief     Called by toolbar_override_netpins.php - deleteMembersDialog()
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
$lib->debug_start('ajax_override_members_remove.html');
date_default_timezone_set('America/Denver');

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"), FALSE);
// $input = json_decode(json_encode($my_request), FALSE);

$netpin_id = 0;
$list      = '';   // Comma separated list of member_id record numbers for cct7_override_members

if (isset($input->{'netpin_id'}))
	$netpin_id = $input->{'netpin_id'};

if (isset($input->{'list'}))
	$list = $input->{'list'};

$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "netpin_id = %d", $netpin_id);
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $list);

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

if ($ora->sql("select * from cct7_override_members where netpin_id = %d", $netpin_id) == false)
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "%s - %s", $ora->sql_statement, $ora->dbErrMsg);
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = $ora->error;
	echo json_encode($json);
	exit();
}

$ora->fetch();

$netpin_no = $ora->netpin_no;
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "netpin_no: %s", $netpin_no);

$cuids   = '';

foreach ($list as $member_id)
{
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "foreach: member_id = %d", $member_id);

	if ($ora->sql("select * from cct7_override_members where member_id = %d", $member_id) == false)
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "%s - %s", $ora->sql_statement, $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	$ora->fetch();

	if (strlen($cuids) > 0)
	{
		$cuids .= ", " . $ora->member_cuid;
	}
	else
	{
		$cuids = $ora->member_cuid;
	}

	$query = "delete from cct7_override_members where member_id = " . $member_id;

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "%s", $query);

	if ($ora->sql2($query) == false)
	{
		$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "%s - %s", $ora->sql_statement, $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $ora->error;
		echo json_encode($json);
		exit();
	}

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__,   "cuids: %s", $cuids);

	$ora->commit();
}

$json['ajax_status']  = 'SUCCESS';
$json['ajax_message'] = "Members(s) have been removed.";
echo json_encode($json);
exit();
