<?php
/**
 * help_toolbar_override_netpins.php
 *
 * @package   PhpStorm
 * @file      help_schedule.php
 * @author    gparkin
 * @date      07/24/2017
 * @version   7.0
 *
 * @brief     Help file for toolbar_override_netpins.php
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

<h2>Netpin Overrides</h2>

<p>
    CCT gathers NET group pins from CSC support banners for each server. Then it retrieves a list of
    group members associated with that netpin from the NET application. These then become the contacts
    that CCT uses to send out notifications for pending work.
</p>

<p>
    There are some work groups in IT that use several NET groups where managers and directors are
    included. As you can imagine, sending out notifications to everyone may not be ideal for these
    situations. This is where Netpin Overrides comes in.
</p>

<p>
    Netpin Overrides will override the NET group member list with the list provided in this database
    table. From the "Lists" menu, you select "Netpin Override" to access the tool to setup your
    overrides.
</p>

<p>
    <img src="help/toolbar_override_netpins/slide1.png"><br>
    There are two grids that will appear when you access this tool. The first grid is a master grid
    containing a master list of all the Netpin Overrides that have been created. The second grid shows
    you the alternate contact notification list. When you click on an existing NET pin from the master
    grid (top grid), all the alternate members for that Net pin will display in the bottom grid.
</p>

<p>
    <img src="help/toolbar_override_netpins/slide2.png"><br>
    Here in this screen I have clicked on the row for 17340 and now the bottom row populates the
    member list in the bottom grid.
</p>

<p>
    <img src="help/toolbar_override_netpins/slide3.png"><br>
    Once you have clicked on a Netpin in the top grid it is now selected so if you were to click the
    Delete button you will see a prompt asking you to confirm your action. Once confirmed you the
    entire override list for that netpin will be removed.
</p>

<p>
    <img src="help/toolbar_override_netpins/slide4.png"><br>
    To create a new Netpin Override, you click on the green "Add" button located under the top grid.
    This dialog box will appear where you enter a netpin. Please not that there can only be one
    one unique netpin in the master table so it will reject your entry if it is already in the list.
    Also if the netpin is not defined in NET, then those netpins will be rejected as well.
</p>

<p>
    <img src="help/toolbar_override_netpins/slide5.png"><br>
    In the screen the "Add Members" button was clicked. This dialog box opens where you type in the
    alternate contact names. Here I enter three cuids. The cuids must be valid account ID's found in
    the MNET database, otherwise they will be rejected.
</p>

<p>
    <img src="help/toolbar_override_netpins/slide6.png"><br>
    After you "Add" button in the Add Members dialog box, the dialog closes and the bottom grid will
    update with the member cuids.
</p>

<p>
    Now at this point all email notifications destine to go to the 4901 Net group members will be
    rerouted to these three individuals. Only these three individuals will get emails and only these
    three individuals need to approve work requests.
</p>

<p>
    The remaining two buttons "Delete Members" and "Delete All Members" can be used to one or more
    alternate contact members or all of them. If you want to delete just a few names click the checkbox
    on the bottom grid next to the name. Then click the "Delete Members button. Clicking the "Delete All
    Members" button empties the entire list so you can add all new names.
</p>
