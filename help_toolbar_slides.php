<?php
/**
 * help_toolbar_slides.php
 *
 * @package   PhpStorm
 * @file      help_toolbar_slides.php
 * @author    gparkin
 * @date      03/06/2017
 * @version   7.0
 *
 * @brief     Brief help file on how to view the slide deck.
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
<h3>CCT 7.0 - Slide Deck</h3>
The slide deck for CCT is intended to give a user a high level overview of CCT version 7. The operation of the slide
deck is straight forward. Just click on the right arrow on the slide to advance to the next slide. Click on the left
arrow to return to the previous slide.
<br>
<br>
There are 12 slides in the presentation.
<br>
<br>
More detail information about each screen is available as users work in each part of the program. If you have a
need more information on creating a new work request while you are in the work request screen, just click on the help
button in the toolbar on the right side. It is the one with the yellow smiley face. When you click on it a panel will
slide up from the bottom and give you more information about the screens you are working in. Clicking on the red-X
icon in the panel or clicking the help icon again will close the panel.