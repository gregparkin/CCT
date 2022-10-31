<?php
/**
 * help_approve.php
 *
 * @package   PhpStorm
 * @file      help_approve.php
 * @author    gparkin
 * @date      6/23/16
 * @version   7.0
 *
 * @brief     About this module.
 */

//$path = '/usr/lib/pear';
//set_include_path(get_include_path() . PATH_SEPARATOR . $path);

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
<h3>Approve</h3>
<p style="font-size: 18px;">

</p>

<br>
<h3>Main Grid</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/a1.png"><br>

</p>

<br>
<h3>Sub Grids</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/a2.png"><br>

</p>

<br>
<h3>Ticket Dialog Box - General</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/ticket1.png"><br>

</p>

<br>
<h3>Ticket Dialog Box - View Remedy Ticket</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/ticket2.png"><br>

</p>

<br>
<h3>Ticket Dialog Box - Description / Implementation Instructions</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/ticket3.png"><br>

</p>

<br>
<h3>Ticket Dialog Box - Backoff Plans</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/ticket4.png"><br>

</p>

<br>
<h3>Ticket Dialog Box - Business Reasons / Impacts</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/ticket5.png"><br>

</p>

<br>
<h3>Ticket Dialog Box - Send Email</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/ticket6.png"><br>

</p>

<br>
<h3>Ticket Dialog Box - Log</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/ticket7.png"><br>

</p>

<br>
<h3>Server Dialog Box - Information</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/server1.png"><br>

</p>

<br>
<h3>Server Dialog Box - Send Email</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/server2.png"><br>

</p>

<br>
<h3>Server Dialog Box - Log</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/server3.png"><br>

</p>

<br>
<h3>Contact Dialog Box - Result</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/contact1.png"><br>

</p>

<br>
<h3>Contact Dialog Box - Netpin Group Members</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/contact2.png"><br>

</p>

<br>
<h3>Contact Dialog Box - Send Email</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/contact3.png"><br>

</p>

<br>
<h3>Contact Dialog Box - Log</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/contact4.png"><br>

</p>



<br>
<h3>xxx</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/"><br>

</p>

<br>
<h3>xxx</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/xxx"><br>

</p>
<br>
<h3>xxx</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/xxx"><br>

</p>

<br>
<h3>xxx</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/xxx"><br>

</p>

<br>
<h3>xxx</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/xxx"><br>

</p>

<br>
<h3>xxx</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/xxx"><br>

</p>

<br>
<h3>xxx</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_approve/xxx"><br>

</p>

