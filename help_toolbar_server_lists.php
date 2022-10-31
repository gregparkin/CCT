<?php
/**
 * help_toolbar_server_lists.php
 *
 * @package   PhpStorm
 * @file      help_toolbar_server_lists.php
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
<h3>Server Lists</h3>
<p style="font-size: 18px;">
    Server Lists are used when you want to import a list of servers you have prepared ahead of time into a new work
    request. The lists can be used over and over again by you and your team. Lists are created and maintained here in
    this screen and are handy when you support and work on many servers at a time, such as when you do patching.<br><br>
    Only you and your team members have access to the lists shown here. These teams as you remember are based upon
    the net-groups found in NET that you are a member of. Please note that you and your team members can be apart
    of multiple net-groups.
</p>

<br>
<h3>Master and Detail Grids</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_server_lists/system_lists.png"><br>
    The server list builder is laid out in two grids, a master grid, and a detail detail. The master contains the list
    names and the detail grid contains a list of servers for the list.
</p>

<br>
<h3>Server List Appears</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_server_lists/server_list_appears.png"><br>
    As you click on a list name from the master grid, CCT will retrieve and populate the detail grid with the servers
    found in that list.
</p>

<br>
<h3>Creating a New List</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_server_lists/add_list.png"><br>
    To create a new list you click on the green Add button under the top (master) grid. This brings up a small dialog
    box where you can enter some text to describe your list of servers. This can be anything such as "Greg's List" or
    "Firmware Patching 4th Quarter". Make the name of the list descriptive enough to help you and others know what
    the list of servers were for.
</p>

<br>
<h3>Adding Servers by Asset Manager</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_server_lists/add_servers_asset_manager.png"><br>
    After you have created your new list it will select it for you or if you wish to edit an existing list, just select
    the list name in the top (master) grid and it will load the servers in the detail grid below. Now you can click on
    the Add button under the bottom (detail) grid and this dialog box will appear.<br><br>
    You will notice that this dialog box has two tabs. The first tab is used for populating your list using search
    criteria found in Asset Manager. Select one or more pieces of information from any of the 6 lists in this tab.
    Then when you are ready click on the add button.<br><br>
    There is also a text box at the bottom where you can enter a partial IP address. This is sometimes desireable if
    you are a network administrator and want to target all devices on a sub-net.
</p>

<br>
<h3>Adding Servers by Manually typing them In</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_server_lists/add_servers_hosts_apps_dbs.png"><br>
    This is the second tab in the add servers dialog box. Here you can type in names of servers (hostnames), and
    acronyms for applications and database names. When you are ready, click the add button and the program will
    go validate the servers from your list against Asset Manager. When they check out (matches were found), they are
    added to your list. If they don't match anything in Asset Manager, CCT will look in the MAL and MDL for the server
    names matching application and database acronyms.<br><br>
    If you maintain a list of servers in a spreadsheet and want to copy them into a CCT server list, you need to first
    copy the list of servers from say Excel to Notepad. Then re-copy the list from Notepad and paste them into this
    text box. If you don't do this your text pasted directly from Excel will contain control characters which
    HTML does not like and you can get undesirable results. The list of servers you type or paste here must be free
    of any hidden control characters.
</p>

<br>
<h3>Delete All Servers</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_server_lists/delete_all.png"><br>
    If you want to clean out the servers from a list you have created so you and repopulate it with a new list, you can
    click on the brown "Delete All" button at the bottom of the screen. This confirm delete all servers message will
    appear asking you to verify your intentions. Click Yes and the list will be removed.<br><br>
    You can also remove one or more servers from the server grid by checking the box next to the server and then just
    clicking on the red "Delete" button. There will be no confirmation here, CCT will just remove the servers you
    selected.
</p>

<br>
<h3>Delete Entire Server List</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_server_lists/delete_list.png"><br>
    To delete an entire server list, select the list name in the top (master) grid and click the red "Delete" button.
    This will bring up a confirmation dialog box asking you to confirm your intentions. Click Yes and the list will be
    removed.
</p>

<br>
<h3>Edit List Name</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_server_lists/edit_list.png"><br>
    If you need to change or fix a type-o in your list name, select the entry in the top (master) grid and click the
    green Edit button. This dialog box will appear with the text you currently have as a description for this list
    name. Change the text and click the Save button. The list description text will now be changed in the grid.
</p>
