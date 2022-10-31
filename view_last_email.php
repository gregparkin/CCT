<?php
/**
 * view_last_email.php
 *
 * @package   PhpStorm
 * @file      view_last_email.php
 * @author    gparkin
 * @date      2/28/17
 * @version   7.0
 *
 * @brief     About this module.
 */

set_include_path("/opt/ibmtools/www/cct7/classes");

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('classes/autoloader.php');
}

//
// Required to start once in order to retrieve user session information
//
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

$lib = new library();
$lib->globalCounter();
$lib->debug_start('view_last_email.html');
date_default_timezone_set('America/Denver');

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

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"));

if (isset($input->{'cuid'}) && strlen($input->{'cuid'}) > 0)
	$cuid = $input->{'cuid'};
else if (isset($_SESSION['user_cuid']) && strlen($_SESSION['user_cuid']) > 0)
    $cuid = $_SESSION['user_cuid'];
else if (isset($_SERVER['REMOTE_USER']) && strlen($_SERVER['REMOTE_USER']) > 0)
    $cuid = $_SERVER['REMOTE_USER'];
else
    $cuid = '';

?>
<style>
	p {
		font-size: 18px;
	}
</style>

<?php

$email_filename = "/opt/ibmtools/cct7/email/" . $cuid . ".html";

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "email_filename: %s", $email_filename);

if (file_exists($email_filename) == false)
{
	printf("<h2>No email file available for cuid: %s</h2>", $cuid);
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "No email file available for cuid: %s", $cuid);
	exit();
}

$stat = stat($email_filename);

$fp = fopen($email_filename, "r") or die("Unable to open email file!");

printf("<p align=center><font size='+2'><b>%s</b></font></p>\n",
	   gmdate("m/d/Y", $stat['mtime']));
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Created: %s", gmdate("m/d/Y", $stat['mtime']));
// gmdate("Y-m-d\TH:i:s\Z", $timestamp);

$buffer =
$buffer = fread($fp, filesize($email_filename));

echo $buffer;
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "\n%s\n", $buffer);

fclose($fp);

exit();
