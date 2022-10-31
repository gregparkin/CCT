<?php
/**
 * help_toolbar_subscriber_lists.php
 *
 * @package   PhpStorm
 * @file      help_toolbar_subscriber_lists.php
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
<h3>Subscriber Lists</h3>
<p style="font-size: 18px;">
    The primary contacts come from CSC where there are Net-Group pin # for designated support banners. As these
    pin numbers are gathered from CSC, a lookup in a database table containing member CUID's are pulled in and
    another lookup in the MNET employee directory table is called to retrieve the actual names and email addresses.
    This is the preferred way CCT gathers contact information.
    <br><br>
    However, there are some groups such as Test and Development groups at CMP that do not use CSC or NET
    but would still like to be involved in the notification and approval process. The subscriber lists were available
    in CCT 6 and will continue to be used in this version. The interface has changed to simply the process for
    maintaining your subscriber list, but how the list is used in CCT has not changed.
    <br><br>
    Something new that has been introduced to CCT 7 subscriber lists is a feature called subscriber groups. With
    subscriber groups you can manage all the members, the servers you want work notification about, and whether you
    want to be an approver or just receive FYI notification.
</p>

<br>
<h3>The Grid Layout</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/subscriber_lists1.png"><br>
    The subscriber list editor has 3 grids. Top left grid is a list of subscriber groups names. The grid on the top
    right is a list of members. The bottom grid is a list of servers you want to subscribe too along with the
    notification type.
</p>

<br>
<h3>Add Group Name</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/add_group_name.png"><br>
    To add a new subscriber group you click on the Add button located under the top left grid (Group Names). This will
    bring up a dialog box where you can type in your subscriber group name. In this example I typed in "CMP-TOOLS".
</p>

<br>
<h3>New Group Name Appears</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/subscriber_list2.png"><br>
    After saving your subscriber list, it appears in the group list grid. Notice the Group ID is something like
    "SUB101". As new work requests are created it looks up the contacts by Net-Pin number as defined in CSC, but since
    subscriber lists are not coming from CSC or NET, a place holder ID is created in the database called SUBxxx where
    xxx is a unique identifier for your subscriber group list. This ID shows up in the grids that show tickets,
    servers, contacts and connection information. If you see that the Net Group number of SUBxxx then you know that it
    is a subscriber list.
</p>

<br>
<h3>Edit Group Name</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/edit_group_name.png"><br>
    If you need to modify the group name, select it the top left grid and click on the Edit button. This dialog box
    will appear where you can now change the group name and then save it. After the dialog box closes the grid is
    updated to show your completed change.
</p>

<br>
<h3>Delete Group Name</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/delete_group_name.png"><br>
    To remove an entire subscriber group list, select it from the top left grid and click the Delete button. This
    dialog box appears with another button that says Delete. Confirm your intentions to delete the list and it will
    be removed. Once the dialog box closes the grid is updated to show your completed request.
</p>

<br>
<h3>Add Members</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/add_members.png"><br>
    In CCT 6, users had to create their own subscriber entry for each server they wanted notification about. In this
    version you can assign multiple members to the same subscriber server list, but keep in mind if a server has a
    FYI notification for pending work then all members will just receive FYI notifications so you may need to think
    this through a bit before adding more than one member to this subscriber group.
    <br><br>
    This dialog box is straight forward, just type in the CUID's of the people you want to add and click the the Save
    button. When the dialog box closes the member grid on the top right will be updated to show your adds completed
    successfully.
</p>

<br>
<h3>Delete Member</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/delete_members.png"><br>
    To remove one or more members click the check box next to the members you want to remove then click the Delete
    button.<br><br>
    <img src="help/toolbar_subscriber_lists/delete_members2.png"><br>
    Next, a confirm delete dialog box appears. Click on Yes and the members you selected will be removed.
</p>

<br>
<h3>xxx</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/delete_all_members.png"><br>
    If you want to remove all the members you can just click on the Delete All button. A dialog box will appear asking
    you to confirm your delete operation. When the dialog box closes the top member list grid on the right will be
    updated to show you that it was successful.
</p>

<br>
<h3>Add Servers - Asset Manager</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/add_servers_asset_manager.png"><br>
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
<h3>Add Servers - Hostnames, Applications and Databases</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/add_servers_host_app_dbms.png"><br>
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
<h3>Delete Servers</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/server_delete.png"><br>
    To delete one or more servers check the box next to the server name on the grid page. Then click on the Delete button
    and a confirm delete dialog box will appear. After clicking on the Yes button the dialog box closes and the grid
    updates to show you that it was successful.
</p>

<br>
<h3>Delete All Servers</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/server_delete_all.png"><br>
    Delete all servers is a quick way of removing all the servers from the server list. It works just like the delete
    all button for the members. Click the button and confirm your intentions when the dialog box appears. After it
    closes the grid is updated.
</p>

<br>
<h3>Server Notification Type - Approver or FYI</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_subscriber_lists/server_approver_fyi.png"><br>
    The Approver and FYI buttons below the server list are used to change the notification type for one or more
    servers in the list. You can set a default radio button to preset them when you add them to the list. To change
    your mind on a few servers after you added them, check the box next to the servers and click the Approver or
    FYI button. The grid will then change the notification type to let you know that it was successful.
</p>
