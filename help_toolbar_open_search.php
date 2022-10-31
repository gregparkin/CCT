<?php
/**
 * help_toolbar_open_search.php
 *
 * @package   PhpStorm
 * @file      help_toolbar_open_search.php
 * @author    gparkin
 * @date      1/18/17
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
date_default_timezone_set('America/Denver');
$lib->globalCounter();
?>
<style>
    p {
        font-size: 18px;
    }
</style>
<h3>Search</h3>
<p style="font-size: 18px;">
    Use the search text box and button to locate records found in the CCT database. When your matches are found the
    records will be displayed in the Open Tickets screen where you can drill down to see any detail. This feature is
    also useful if you want to get a work history for a server you support.
</p>

<br>
<h3>Search Text Box</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_search/search1.png"><br>
    The text box next to the search button will accept CCT or Remedy ticket numbers, Hostnames, Net-Pins, and CUIDS.
    These are exact single item matches. Do not enter partial text and multiple items to search. It will only
    search for one item at a time and it must be a complete exact match.<br><br>
    Examples:
    <div style="padding-left:40px;">
        <ui>
            <li>CCT700015070</li>
            <li>CM0000293341</li>
            <li>gparkin</li>
            <li>lxomp47x</li>
            <li>17340</li>
        </ui>
    </div>

</p>

<br>
<h3>Search Results</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_search/search2.png"><br>
    After you type text for what you are looking for, this is what the screen will display. In this example I
    searched for "lxomp47x" and these are all the tickets that this server was found in, both open and closed
    tickets.
</p>


