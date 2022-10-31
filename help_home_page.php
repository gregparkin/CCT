<?php
/**
 * help_home_page.php
 *
 * @package   PhpStorm
 * @file      help_home_page.php
 * @author    gparkin
 * @date      6/23/16
 * @version   7.0
 *
 * @brief     About this module.
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
<p><b><u>Welcome to CCT 7.0</u></b></p>

<p>
    CCT has been redesigned to give the user a better experience while working with the tool. The application is now
    laid out within panels making the tool act like a seamless application that one would find running on their
    workstation.
</p>
<p>
    For an overview of what has changed and what is new in CCT, please look at the slide presentation which can
    be viewed by clicking on the <img src="images/slidedeck.png"> next to the help icon in the upper right corner
    on the toolbar.
</p>
<p>
    As you work with the tool by clicking on the icons along the top in the toolbar, new help files are made available
    for that screen. Simply click on the help button anytime and a panel will slide up from the bottom showing you
    additional help about what you are doing.
</p>
<p>
    You may notice that the panel that slides up from the bottom of your window is resizable. You can move your mouse
    over the divider line and the icon will change to indicate that you can click, hold and drag your mouse up over
    down. This is handy if you want to go back to what you are doing and have the help documentation open and
    available to you.
</p>
<p>
    There are two ways to close the help panel. This first way is to click on the help icon again and the second is to
    just click on the red square with the white X in it.
</p>
<hr>
<p>
    If you find any program errors or have a suggestion about CCT, you can send Greg Parkin an email at
    <a href="mail:gregparkin58@gmail.com">gregparkin58@gmail.com</a>. Please note that Greg does not add, change or remove
    your contact information in CCT, CSC or NET. It is the user's responsibility. The most common place to remove
    or add your contact information is from NET where you are apart of a paging group.
</p>
<p>
    Please review the slide deck that is available on the toolbar next to the help icon. This will give you a quick
    overview of what's changed and what is new.
</p>