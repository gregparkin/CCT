<?php
/**
 * ajax_dialog_open_ticket_sort.php
 *
 * @package   PhpStorm
 * @file      ajax_dialog_open_ticket_sort.php
 * @author    gparkin
 * @date      7/9/2017
 * @version   7.0
 *
 * @brief     Select sort order from dialog_open_ticket_sort.php sets $_SESSION[sort_order] for
 *            the program ajax_jqgrid_toolbar_open_tickets.php
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
$lib = new library();
$lib->debug_start('ajax_dialog_open_ticket_sort.html');
date_default_timezone_set('America/Denver');

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"), FALSE);
// $input = json_decode(json_encode($my_request), FALSE);

$sort_list = '';

if (isset($input->{'sort_list'}))
	$sort_list          = $input->sort_list;

$_SESSION['sort_list']  = $sort_list;

$json['ajax_status']  = 'SUCCESS';
$json['ajax_message'] = $sort_list;
echo json_encode($json);
exit();