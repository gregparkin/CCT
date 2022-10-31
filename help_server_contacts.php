<?php
/**
 * help_server_contacts.php
 *
 * @package   PhpStorm
 * @file      help_server_contacts.php
 * @author    gparkin
 * @date      7/2/2017
 * @version   7.0
 *
 * @brief     Help file for Server Contacts.
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
date_default_timezone_set('America/Denver');
$lib->globalCounter();
?>
<style>
	p {
		font-size: 18px;
	}
</style>
<h3>Replaces CCT 6 - Trace Data Sources Tool</h3>
<p style="font-size: 18px;">
    This tool replaces the original CCT 6 Trace Data Sources tool. It outputs information slightly
    different because of the new way CCT 7 gathers contact information.
</p>

<br>

<h3>Server Contacts</h3>
<p style="font-size: 18px;">
    Use this tool to determine who will receive notifications from CCT and whether they will be
    an Approver or just want to receive FYI notications. CCT gathers contact information from
    three places only; CSC, NET and CCT. CSC contains contact records or CSC banners as we call
    them. These contact records contain NET group pin numbers from the NET tool. <i>(A list of CSC
    banners used by CCT are listed below.)</i> If users don't use CSC or NET but want to receive
    notifications or be in the work approval process, they will setup CCT Subscriber Lists.
</p>

<br>

<h3>Instructions</h3>
<p style="font-size: 18px;">
    In the text box below you type in one or more server names and click on the submit
    button. Once the data is displayed, the text box below will clear so you can enter
    more servers and click submit again.
</p>
