<?php
/**
 * help_.php
 *
 * @package   PhpStorm
 * @file      help_.php
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
<p><b><u>Ready Work</u></b></p>
You can view the CCT work schedule by group (net-groups) or view them all. Both views work the same, but with different
filters set. Normally you would want to just work with your group tickets and not be concerned about the others.
The Change Management team would most likely want to view all the work for the enterprise.<br><br>
<font color="blue">
    <u>
        Please not that this view of scheduled work will only show work that has been approved or where approves are not
        required.
    </u>
</font>

<br>
<h3>Schedule Work All - Grid</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_schedule_group/schedule_work_group.png"><br>
    This grid is laid out differently than the other grids. There are no expanding sub-grids, but you will notice that
    there is a check box in the first column of each row. This box is used to select the number of servers you want
    to change and send out notifications from this page.
</p>

<br>
<h3>Selecting Servers from the Grid</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_schedule_group/select_four_servers.png"><br>
    Changing the status and sending out notifications is accomplished selecting the rows you want to perform an action
    on.
</p>

<br>
<h3>Button - Change Status and Send Notification</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_schedule_group/change_status_send_notifications.png"><br>
    After selecting your servers you would click on the big button at the bottom of the grid called
    "Change Status and Send Notifications".
</p>

<br>
<h3>Dialog Box - Change Status and Send Notification</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_schedule_group/dialog_change_status.png"><br>
    Next, the Change Status and Send Notification dialog box will appear showing you any log messages containing
    information you should know about when working on the server, a place where you can add additional paging text
    information, and change status action buttons.<br><br>
    As you hover over the buttons, help text will appear below the button to give you more information about what
    the button does. Here is what each button does:
<ul>
    <li>
        Starting - Change work status to "STARTING", and page the on-call users desiring notification.
    </li>
    <li>
        Success - Change work status to "SUCCESS", and page the on-call users desiring notification.
    </li>
    <li>
        Failed - Change work status to "FAILED", and page the on-call users desiring notification.
    </li>
    <li>
        Canceled - Change work status to "CANCELED", and page the on-call users desiring notification.
    </li>
    <li>
        Close - Close the dialog box.
    </li>
</ul>
</p>

<br>
<h3>Log Messages in the Dialog Box</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_schedule_group/log_messages.png"><br>
    For every server you selected back on the grid, a copy of that servers log messages will appear in the log
    text area which is a scrollable window. As you scroll through the messages, any additional messages that users
    have left will appear here so please take note of any special instructions they may leave here.
</p>

<br>
<h3>Status Change</h3>
<p style="font-size: 18px;">
    <img src="help/toolbar_schedule_group/starting.png"><br>
    This example shows that the "Starting" button in the dialog box was clicked. Pages were sent out to users
    desiring notification. All the dialog change status buttons from the dialog will update the grid for your
    selected servers respectfully.
</p>

<br>
<h3>Email Notifications</h3>
<p style="font-size: 18px;">
    Email notification will still go out to users, but only once a day and there should be only one email compiled
    for a user.<br><br>
    CCT scans its log records once a day to see what has changed. When it finds something that has changed such as a
    status for a server it will compile and send email to the users about that change.
</p>

