<?php
/**
 * ajax_toolbar_search.php
 *
 * @package   PhpStorm
 * @file      ajax_toolbar_search.php
 * @author    gparkin
 * @date      8/26/16
 * @version   7.0
 *
 * @brief     Search database for matching search criteria entered on the toolbar.
 *
 * @brief     Incoming JSON data sent from input.php
 *            search_buffer:
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
$lib = new library();       // classes/library.php
$lib->debug_start('ajax_toolbar_search.html');
date_default_timezone_set('America/Denver');

$ora = new oracle();

$search_buffer = '';

if (isset($_GET['search_buffer']))
	$search_buffer = $_GET['search_buffer'];

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "search_buffer = %s", $search_buffer);

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
// BY TICKET
//
// Search in cct7_tickets for matches in ticket_no and cm_ticket_no
//
$query  = "select ";
$query .= "  ticket_no ";
$query .= "from ";
$query .= "  cct7_tickets ";
$query .= "where ";
$query .= sprintf("  upper(ticket_no) = upper('%s') or upper(cm_ticket_no) = upper('%s')",
				  $search_buffer, $search_buffer);

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "%s", $query);

if ($ora->sql2($query) == false)
{
	$json['ajax_status']   = "FAILED";
	$json['ajax_message']  = $ora->dbErrMsg;
	echo json_encode($json);
	exit();
}

if ($ora->fetch())
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "toolbar_open.php?what_tickets=%s", $search_buffer);
	$json['ajax_status']   = "SUCCESS";
	$json['ajax_message']  = sprintf("toolbar_open.php?what_tickets=%s", $search_buffer);
	echo json_encode($json);
	exit();
}

//
// BY HOSTNAME
//
// Search in cct7_systems for matches in system_hostname
//
$query  = "select ";
$query .= "  system_hostname ";
$query .= "from ";
$query .= "  cct7_systems ";
$query .= "where ";
$query .= sprintf("  lower(system_hostname) = lower('%s')", $search_buffer);

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "%s", $query);

if ($ora->sql2($query) == false)
{
	$json['ajax_status']   = "FAILED";
	$json['ajax_message']  = $ora->dbErrMsg;
	echo json_encode($json);
	exit();
}

if ($ora->fetch())
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "toolbar_open.php?what_hostname=%s", $ora->system_hostname);
	$json['ajax_status']   = "SUCCESS";
	$json['ajax_message']  = sprintf("toolbar_open.php?what_hostname=%s", $ora->system_hostname);
	echo json_encode($json);
	exit();
}

//
// BY NETPIN
//
// Search in cct7_contacts for matches in contact_netpin_no
//
$query  = "select ";
$query .= "  * ";
$query .= "from ";
$query .= "  cct7_contacts ";
$query .= "where ";
$query .= sprintf("  contact_netpin_no = '%s'", $search_buffer);

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "%s", $query);

if ($ora->sql2($query) == false)
{
	$json['ajax_status']   = "FAILED";
	$json['ajax_message']  = $ora->dbErrMsg;
	echo json_encode($json);
	exit();
}

if ($ora->fetch())
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "toolbar_open.php?what_netpin_no=%s", $search_buffer);
	$json['ajax_status']   = "SUCCESS";
	$json['ajax_message']  = sprintf("toolbar_open.php?what_netpin_no=%s", $search_buffer);
	echo json_encode($json);
	exit();
}

//
// BY CUID
//
// Search cct7_contacts for matching contact_netpin_no where 'cuid' is a member of that netpin group.
//
$query  = "select ";
$query .= "  ticket_no ";
$query .= "from ";
$query .= "  cct7_tickets ";
$query .= "where ";
$query .= sprintf("  upper(owner_cuid) = upper('%s') ", $search_buffer);

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "%s", $query);

if ($ora->sql2($query) == false)
{
	$json['ajax_status']   = "FAILED";
	$json['ajax_message']  = $ora->dbErrMsg;
	echo json_encode($json);
	exit();
}

if ($ora->fetch())
{
	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "toolbar_open.php?what_cuid=%s", $search_buffer);
	$json['ajax_status']   = "SUCCESS";
	$json['ajax_message']  = sprintf("toolbar_open.php?what_cuid=%s", $search_buffer);
	echo json_encode($json);
	exit();
}

$json['ajax_status']   = "FAILED";
$json['ajax_message']  = sprintf("Search: (%s) does not match any ticket no., hostnames, netpins or ticket owner cuids.",
								 $search_buffer);
echo json_encode($json);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__,
			   "Search: (%s) does not match any ticket no., hostnames, netpins or ticket owner cuids.", $search_buffer);
exit();


