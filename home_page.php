<?php
/**
 * @package    CCT
 * @file       home_page.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       $Date:  $ GMT
 * @version    $Revision:  $
 *
 * $Log:  $
 *
 * $Source:  $
 */

//
// CCT 7.0 Home page containing messages, FAQs, and other useful hints.
//

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
<!DOCTYPE html>
<html>
<head>
    <title>CCT 7.0 Home Page</title>
</head>
<body>
<div class="loader"></div>
<center>
<table border="0" cellspacing="0" cellpadding="0" style="width: 100%; height: 100%; background-color: lightgoldenrodyellow">
    <tr>
        <td align="center" valign="top">
            <img src="images/cct70_lightgoldenrodyellow.png">
        </td>
    </tr>
    <tr>
        <td align="center" valign="top">
            <p style="color: red; font-size: 24px;">
                <b><u>Due to unpopular demand, CCT 6 will be restored to production by Monday.</u></b>
            </p>
            <p style="color: blue; font-size: 16px;">
                All new data created in CCT 7 will be converted and copied into the CCT 6 database
                so you shouldn't loose any work requests you have created or any responses you have
                received from contacts.
            </p>
            <p style="color: green; font-size: 16px;">
                All CCT 7 training sessions have been canceled.
            </p>
        </td>
    </tr>
    <tr>
        <td>
            <br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
        </td>
    </tr>
</table>
</center>
</body>
</html>
