<?php
/**
 * help_toolbar_email.php
 *
 * @package   PhpStorm
 * @file      help_toolbar_email.php
 * @author    gparkin
 * @date      2/28/17
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
<h3>View last email sent</h3>
<p>
    This toolbar option will display the <u>last</u> email generated for you. As you can see the email is displayed
    in the right panel of the web page. The panels are adjustable by clicking on the borders and dragging them
    left or right.
</p>
<p>
    To close the email window you can click on the red X icon or by clicking on the email icon again.
</p>
<p>
    Future options for this feature will include the ability to resend the email back to you and
    the option to print the email from this web page.
</p>
<p>
    <b>The following is an example of a email notification that is sent to a user.</b>
</p>

    <center>
            <div style="width: 825px;">
                <font color="red" face="Zapf Chancery, cursive" size="+2"><b>Change Coordination Tool - Email Notification</b></font>
                <p align="left">Greg,</p>
                <p align="left">The following is a detailed notification outlining the status of pending work requiring your approval
                    or attention.</p>
                <u>Please read through this email carefully!</u><p></p>
                <p align="left">Logon to CCT to and click on the Approval button to approve work for the servers you support.</p>
                <p><a href="http://cct.corp.intranet/">https://cct.corp.intranet</a></p>
                <p align="left">If there are servers that are parent hosts for virtual servers you support then you will be asked to
                    approve the work for the parent servers where there is a reboot that will effect your virtual server.</p>
                <p align="left"><b>Please Note:</b> All dates and times in this email are shown in <u>Mountain Time</u>. When you logon to
                    CCT all dates and times are shown in your local time zone. This means that the dates and times in this email may
                    not match what you see in the CCT application.</p>
                <p align="left">CCT work notifications go out to Netpin Groups so your team members will receive the same notification.
                    Any member of your Net Group team can approve or reject work requests so it is important that you work closely with your
                    team in deciding who should be approving the work.
                </p></div>
            <br><br>
            <div style="width: 825px;">
                <table style="height: 100%; color: black" cellpadding="1" cellspacing="1" width="100%" bgcolor="#AAB4FF">
                    <tbody><tr>
                        <td valign="top" align="right"><b>CCT Ticket:</b></td><td valign="top" align="left">CCT700044347</td>
                        <td valign="top" align="right"><b>Owner Name:</b></td><td valign="top" align="left">Ashok Kumar</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Creation Date:</b></td><td valign="top" align="left">05/02/2017 03:09</td>
                        <td valign="top" align="right"><b>Owner Email:</b></td><td valign="top" align="left"><a href="mailto:askodali@in.ibm.com">askodali@in.ibm.com</a></td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Ticket Status:</b></td><td valign="top" align="left"><font color="blue"><b>ACTIVE</b></font></td>
                        <td valign="top" align="right"><b>Owner Job Title:</b></td><td valign="top" align="left"></td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Work Activity:</b></td><td valign="top" align="left">Patching</td>
                        <td valign="top" align="right"><b>Manager Name:</b></td><td valign="top" align="left">Naga Venkateswara Rao Gudise</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Reboot Required:</b></td><td valign="top" align="left">N</td>
                        <td valign="top" align="right"><b>Manager Email:</b></td><td valign="top" align="left">venkat.gudise@in.ibm.com</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Approvals Required:</b></td><td valign="top" align="left">Y</td>
                        <td valign="top" align="right"><b>Manager Job Title:</b></td><td valign="top" align="left"></td>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Email Reminder 1:</b></td><td valign="top" align="left">05/11/2017</td>
                        <td valign="top" align="right"><b>Last Modified:</b></td><td valign="top" align="left">05/18/2017 12:36</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Email Reminder 2:</b></td><td valign="top" align="left">05/11/2017</td>
                        <td valign="top" align="right"><b>Modified By:</b></td><td valign="top" align="left">Greg Parkin</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Email Reminder 3:</b></td><td valign="top" align="left">05/11/2017</td>
                        <td valign="top" align="right"><b>Total Servers:</b></td><td valign="top" align="left">5</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Respond By:</b></td><td valign="top" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                        <td valign="top" align="right"><b>Servers Approved:</b></td><td valign="top" align="left">0</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Schedule Start:</b></td>
                        <td valign="top" align="left">05/16/2017 00:00</td>
                        <td valign="top" align="right"><b>Remedy Start:</b></td>
                        <td valign="top" align="left">06/07/2017 02:00</td>
                    </tr>
                    <tr>
                        <td valign="middle" align="right"><b>Remedy CM:</b></td>
                        <td valign="middle" align="left">CM0000333941</td>
                        <td valign="top" align="right"><b>Remedy End:</b></td>
                        <td valign="top" align="left">06/07/2017 04:00</td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left"><b><u>Description</u></b><pre>Applying PSU Jan2017 Combo 24917069  on Database wasp001p which is running on server lxdnp25j/lxdnp22j/lxdnp23j/lxdnp24j/lxdnp26j</pre></td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left"><b><u>Implementation/Instructions</u></b><pre>Attached To This IR
IBm-UNIX Sysadmin need to freeze these pkg for database wasp001p on server  lxdnp23j and unfreeze the pkg after the patching</pre></td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left"><b><u>Backoff Plans</u></b><pre>Roll Back the Patch. &amp; Bounce DB</pre></td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left"><b><u>Business Reasons</u></b><pre>Oracle Patch is required to fix bug and security vulnerability which recommended by Oracle Support</pre></td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left"><b><u>Impact</u></b><pre>Database PSU Patch will cause database and application will not be available during this time.</pre></td>
                    </tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#a7ce83">
                    <tbody><tr>
                        <td valign="top" width="20%" align="right"><b>Server:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">lxdnp25j</font></td>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>OS:</b></td>
                        <td valign="top" width="30%" align="left">Linux</td>
                        <td valign="top" width="20%" align="right"><b>Work Status:</b></td>
                        <td valign="top" width="30%" align="left"><font color="red"><b>WAITING</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Usage:</b></td>
                        <td valign="top" width="30%" align="left">PRODUCTION</td>
                        <td valign="top" width="20%" align="right"><b>Work Start:</b></td>
                        <td valign="top" width="30%" align="left">05/16/2017 00:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Location:</b></td>
                        <td valign="top" width="30%" align="left">DENVER</td>
                        <td valign="top" width="20%" align="right"><b>Work End:</b></td>
                        <td valign="top" width="30%" align="left">05/16/2017 02:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Time Zone:</b></td>
                        <td valign="top" width="30%" align="left">America/Denver</td>
                        <td valign="top" width="20%" align="right"><b>Duration:</b></td>
                        <td valign="top" width="30%" align="left">00:02:00</td>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Maintenance:</b></td>
                        <td valign="top" width="30%" align="left">MON,THU 00:00 120</td>
                        <td valign="top" width="20%" align="right"><b># Responded:</b></td>
                        <td valign="top" width="30%" align="left">1</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td valign="top" width="20%" align="right"><b># Waiting:</b></td>
                        <td valign="top" width="30%" align="left">31</td>
                    </tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#ffffcc">
                    <tbody><tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Netpin:</b></td>
                        <td valign="top" width="30%" align="left">17340</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Response Date:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 08:56</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Response:</b></td>
                        <td valign="top" width="30%" align="left"><font color="green"><b>APPROVED</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Responder Name:</b></td>
                        <td valign="top" width="30%" align="left">Greg Parkin</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Connection:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">hcdnx18j-&gt;lxdnp25j</font></td>
                        <td valign="top" width="20%" align="right"><b>Group:</b></td>
                        <td valign="top" width="30%" align="left">DBA</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Page On-Call:</b></td>
                        <td valign="top" width="30%" align="left">N</td>
                        <td valign="top" width="20%" align="right"><b>Type:</b></td>
                        <td valign="top" width="30%" align="left">FYI</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Send Email:</b></td>
                        <td valign="top" width="30%" align="left">Y</td>
                        <td valign="top" width="20%" align="right">&nbsp;</td>
                        <td valign="top" width="30%" align="left">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left">
                            <b>05/22/2017 08:56</b> Greg Parkin<br>
                            <b>APPROVED</b> CCT700044347 NETPIN 17340 approved work for server lxdnp25j.<br><br>
                        </td></tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#a7ce83">
                    <tbody><tr>
                        <td valign="top" width="20%" align="right"><b>Server:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">lxdnp22j</font></td>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>OS:</b></td>
                        <td valign="top" width="30%" align="left">Linux</td>
                        <td valign="top" width="20%" align="right"><b>Work Status:</b></td>
                        <td valign="top" width="30%" align="left"><font color="red"><b>WAITING</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Usage:</b></td>
                        <td valign="top" width="30%" align="left">PRODUCTION</td>
                        <td valign="top" width="20%" align="right"><b>Work Start:</b></td>
                        <td valign="top" width="30%" align="left">05/16/2017 00:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Location:</b></td>
                        <td valign="top" width="30%" align="left">DENVER</td>
                        <td valign="top" width="20%" align="right"><b>Work End:</b></td>
                        <td valign="top" width="30%" align="left">05/16/2017 02:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Time Zone:</b></td>
                        <td valign="top" width="30%" align="left">America/Denver</td>
                        <td valign="top" width="20%" align="right"><b>Duration:</b></td>
                        <td valign="top" width="30%" align="left">00:02:00</td>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Maintenance:</b></td>
                        <td valign="top" width="30%" align="left">MON,FRI 00:00 120</td>
                        <td valign="top" width="20%" align="right"><b># Responded:</b></td>
                        <td valign="top" width="30%" align="left">1</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td valign="top" width="20%" align="right"><b># Waiting:</b></td>
                        <td valign="top" width="30%" align="left">29</td>
                    </tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#ffffcc">
                    <tbody><tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Netpin:</b></td>
                        <td valign="top" width="30%" align="left">17340</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Response Date:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 08:56</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Response:</b></td>
                        <td valign="top" width="30%" align="left"><font color="green"><b>APPROVED</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Responder Name:</b></td>
                        <td valign="top" width="30%" align="left">Greg Parkin</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Connection:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">hcdnx16j-&gt;lxdnp22j</font></td>
                        <td valign="top" width="20%" align="right"><b>Group:</b></td>
                        <td valign="top" width="30%" align="left">DBA</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Page On-Call:</b></td>
                        <td valign="top" width="30%" align="left">N</td>
                        <td valign="top" width="20%" align="right"><b>Type:</b></td>
                        <td valign="top" width="30%" align="left">FYI</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Send Email:</b></td>
                        <td valign="top" width="30%" align="left">Y</td>
                        <td valign="top" width="20%" align="right">&nbsp;</td>
                        <td valign="top" width="30%" align="left">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left">
                            <b>05/22/2017 08:56</b> Greg Parkin<br>
                            <b>APPROVED</b> CCT700044347 NETPIN 17340 approved work for server lxdnp22j.<br><br>
                        </td></tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#a7ce83">
                    <tbody><tr>
                        <td valign="top" width="20%" align="right"><b>Server:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">lxdnp23j</font></td>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>OS:</b></td>
                        <td valign="top" width="30%" align="left">Linux</td>
                        <td valign="top" width="20%" align="right"><b>Work Status:</b></td>
                        <td valign="top" width="30%" align="left"><font color="red"><b>WAITING</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Usage:</b></td>
                        <td valign="top" width="30%" align="left">PRODUCTION</td>
                        <td valign="top" width="20%" align="right"><b>Work Start:</b></td>
                        <td valign="top" width="30%" align="left">05/16/2017 00:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Location:</b></td>
                        <td valign="top" width="30%" align="left">DENVER</td>
                        <td valign="top" width="20%" align="right"><b>Work End:</b></td>
                        <td valign="top" width="30%" align="left">05/16/2017 02:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Time Zone:</b></td>
                        <td valign="top" width="30%" align="left">America/Denver</td>
                        <td valign="top" width="20%" align="right"><b>Duration:</b></td>
                        <td valign="top" width="30%" align="left">00:02:00</td>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Maintenance:</b></td>
                        <td valign="top" width="30%" align="left">TUE,SAT 00:00 120</td>
                        <td valign="top" width="20%" align="right"><b># Responded:</b></td>
                        <td valign="top" width="30%" align="left">1</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td valign="top" width="20%" align="right"><b># Waiting:</b></td>
                        <td valign="top" width="30%" align="left">29</td>
                    </tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#ffffcc">
                    <tbody><tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Netpin:</b></td>
                        <td valign="top" width="30%" align="left">17340</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Response Date:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 08:56</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Response:</b></td>
                        <td valign="top" width="30%" align="left"><font color="green"><b>APPROVED</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Responder Name:</b></td>
                        <td valign="top" width="30%" align="left">Greg Parkin</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Connection:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">hcdnx16j-&gt;lxdnp23j</font></td>
                        <td valign="top" width="20%" align="right"><b>Group:</b></td>
                        <td valign="top" width="30%" align="left">DBA</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Page On-Call:</b></td>
                        <td valign="top" width="30%" align="left">N</td>
                        <td valign="top" width="20%" align="right"><b>Type:</b></td>
                        <td valign="top" width="30%" align="left">FYI</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Send Email:</b></td>
                        <td valign="top" width="30%" align="left">Y</td>
                        <td valign="top" width="20%" align="right">&nbsp;</td>
                        <td valign="top" width="30%" align="left">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left">
                            <b>05/22/2017 08:56</b> Greg Parkin<br>
                            <b>APPROVED</b> CCT700044347 NETPIN 17340 approved work for server lxdnp23j.<br><br>
                        </td></tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#a7ce83">
                    <tbody><tr>
                        <td valign="top" width="20%" align="right"><b>Server:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">lxdnp24j</font></td>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>OS:</b></td>
                        <td valign="top" width="30%" align="left">Linux</td>
                        <td valign="top" width="20%" align="right"><b>Work Status:</b></td>
                        <td valign="top" width="30%" align="left"><font color="red"><b>WAITING</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Usage:</b></td>
                        <td valign="top" width="30%" align="left">PRODUCTION</td>
                        <td valign="top" width="20%" align="right"><b>Work Start:</b></td>
                        <td valign="top" width="30%" align="left">05/16/2017 00:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Location:</b></td>
                        <td valign="top" width="30%" align="left">DENVER</td>
                        <td valign="top" width="20%" align="right"><b>Work End:</b></td>
                        <td valign="top" width="30%" align="left">05/16/2017 02:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Time Zone:</b></td>
                        <td valign="top" width="30%" align="left">America/Denver</td>
                        <td valign="top" width="20%" align="right"><b>Duration:</b></td>
                        <td valign="top" width="30%" align="left">00:02:00</td>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Maintenance:</b></td>
                        <td valign="top" width="30%" align="left">SUN,WED 00:00 120</td>
                        <td valign="top" width="20%" align="right"><b># Responded:</b></td>
                        <td valign="top" width="30%" align="left">1</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td valign="top" width="20%" align="right"><b># Waiting:</b></td>
                        <td valign="top" width="30%" align="left">29</td>
                    </tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#ffffcc">
                    <tbody><tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Netpin:</b></td>
                        <td valign="top" width="30%" align="left">17340</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Response Date:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 08:56</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Response:</b></td>
                        <td valign="top" width="30%" align="left"><font color="green"><b>APPROVED</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Responder Name:</b></td>
                        <td valign="top" width="30%" align="left">Greg Parkin</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Connection:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">hcdnx17j-&gt;lxdnp24j</font></td>
                        <td valign="top" width="20%" align="right"><b>Group:</b></td>
                        <td valign="top" width="30%" align="left">DBA</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Page On-Call:</b></td>
                        <td valign="top" width="30%" align="left">N</td>
                        <td valign="top" width="20%" align="right"><b>Type:</b></td>
                        <td valign="top" width="30%" align="left">FYI</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Send Email:</b></td>
                        <td valign="top" width="30%" align="left">Y</td>
                        <td valign="top" width="20%" align="right">&nbsp;</td>
                        <td valign="top" width="30%" align="left">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left">
                            <b>05/22/2017 08:56</b> Greg Parkin<br>
                            <b>APPROVED</b> CCT700044347 NETPIN 17340 approved work for server lxdnp24j.<br><br>
                        </td></tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#a7ce83">
                    <tbody><tr>
                        <td valign="top" width="20%" align="right"><b>Server:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">lxdnp26j</font></td>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>OS:</b></td>
                        <td valign="top" width="30%" align="left">Linux</td>
                        <td valign="top" width="20%" align="right"><b>Work Status:</b></td>
                        <td valign="top" width="30%" align="left"><font color="red"><b>WAITING</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Usage:</b></td>
                        <td valign="top" width="30%" align="left">PRODUCTION</td>
                        <td valign="top" width="20%" align="right"><b>Work Start:</b></td>
                        <td valign="top" width="30%" align="left">05/16/2017 00:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Location:</b></td>
                        <td valign="top" width="30%" align="left">DENVER</td>
                        <td valign="top" width="20%" align="right"><b>Work End:</b></td>
                        <td valign="top" width="30%" align="left">05/16/2017 02:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Time Zone:</b></td>
                        <td valign="top" width="30%" align="left">America/Denver</td>
                        <td valign="top" width="20%" align="right"><b>Duration:</b></td>
                        <td valign="top" width="30%" align="left">00:02:00</td>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Maintenance:</b></td>
                        <td valign="top" width="30%" align="left">TUE,FRI 00:00 120</td>
                        <td valign="top" width="20%" align="right"><b># Responded:</b></td>
                        <td valign="top" width="30%" align="left">1</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td valign="top" width="20%" align="right"><b># Waiting:</b></td>
                        <td valign="top" width="30%" align="left">33</td>
                    </tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#ffffcc">
                    <tbody><tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Netpin:</b></td>
                        <td valign="top" width="30%" align="left">17340</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Response Date:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 08:56</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Response:</b></td>
                        <td valign="top" width="30%" align="left"><font color="green"><b>APPROVED</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Responder Name:</b></td>
                        <td valign="top" width="30%" align="left">Greg Parkin</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Connection:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">hcdnx18j-&gt;lxdnp26j</font></td>
                        <td valign="top" width="20%" align="right"><b>Group:</b></td>
                        <td valign="top" width="30%" align="left">DBA</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Page On-Call:</b></td>
                        <td valign="top" width="30%" align="left">N</td>
                        <td valign="top" width="20%" align="right"><b>Type:</b></td>
                        <td valign="top" width="30%" align="left">FYI</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Send Email:</b></td>
                        <td valign="top" width="30%" align="left">Y</td>
                        <td valign="top" width="20%" align="right">&nbsp;</td>
                        <td valign="top" width="30%" align="left">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left">
                            <b>05/22/2017 08:56</b> Greg Parkin<br>
                            <b>APPROVED</b> CCT700044347 NETPIN 17340 approved work for server lxdnp26j.<br><br>
                        </td></tr>
                    </tbody></table>
            </div>
            <br><br>
            <div style="width: 825px;">
                <table style="height: 100%; color: black" cellpadding="1" cellspacing="1" width="100%" bgcolor="#AAB4FF">
                    <tbody><tr>
                        <td valign="top" align="right"><b>CCT Ticket:</b></td><td valign="top" align="left">CCT700044352</td>
                        <td valign="top" align="right"><b>Owner Name:</b></td><td valign="top" align="left">Ashok Kumar</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Creation Date:</b></td><td valign="top" align="left">05/03/2017 04:42</td>
                        <td valign="top" align="right"><b>Owner Email:</b></td><td valign="top" align="left"><a href="mailto:askodali@in.ibm.com">askodali@in.ibm.com</a></td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Ticket Status:</b></td><td valign="top" align="left"><font color="blue"><b>ACTIVE</b></font></td>
                        <td valign="top" align="right"><b>Owner Job Title:</b></td><td valign="top" align="left"></td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Work Activity:</b></td><td valign="top" align="left">Patching</td>
                        <td valign="top" align="right"><b>Manager Name:</b></td><td valign="top" align="left">Naga Venkateswara Rao Gudise</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Reboot Required:</b></td><td valign="top" align="left">N</td>
                        <td valign="top" align="right"><b>Manager Email:</b></td><td valign="top" align="left">venkat.gudise@in.ibm.com</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Approvals Required:</b></td><td valign="top" align="left">Y</td>
                        <td valign="top" align="right"><b>Manager Job Title:</b></td><td valign="top" align="left"></td>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Email Reminder 1:</b></td><td valign="top" align="left">05/11/2017</td>
                        <td valign="top" align="right"><b>Last Modified:</b></td><td valign="top" align="left">05/18/2017 12:36</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Email Reminder 2:</b></td><td valign="top" align="left">05/11/2017</td>
                        <td valign="top" align="right"><b>Modified By:</b></td><td valign="top" align="left">Greg Parkin</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Email Reminder 3:</b></td><td valign="top" align="left">05/11/2017</td>
                        <td valign="top" align="right"><b>Total Servers:</b></td><td valign="top" align="left">5</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Respond By:</b></td><td valign="top" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                        <td valign="top" align="right"><b>Servers Approved:</b></td><td valign="top" align="left">0</td>
                    </tr>
                    <tr>
                        <td valign="top" align="right"><b>Schedule Start:</b></td>
                        <td valign="top" align="left">05/22/2017 00:00</td>
                        <td valign="top" align="right"><b>Remedy Start:</b></td>
                        <td valign="top" align="left">06/07/2017 02:00</td>
                    </tr>
                    <tr>
                        <td valign="middle" align="right"><b>Remedy CM:</b></td>
                        <td valign="middle" align="left">CM0000333949</td>
                        <td valign="top" align="right"><b>Remedy End:</b></td>
                        <td valign="top" align="left">06/07/2017 04:00</td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left"><b><u>Description</u></b><pre>Applying PSU Jan2017 Combo 24917069  on Databases  btgtwy,eldbden,thuncat which is running on server lxdnp25j/lxdnp22j/lxdnp23j/lxdnp24j/lxdnp26j</pre></td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left"><b><u>Implementation/Instructions</u></b><pre>Attached To This IR
IBm-UNIX Sysadmin need to freeze these pkg for database btgtwy,eldbden,thuncat on server  lxdnp25j and unfreeze the pkg after the patching</pre></td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left"><b><u>Backoff Plans</u></b><pre>Roll Back the Patch. &amp; Bounce DB</pre></td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left"><b><u>Business Reasons</u></b><pre>Oracle Patch is required to fix bug and security vulnerability which recommended by Oracle Support</pre></td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left"><b><u>Impact</u></b><pre>Database PSU Patch will cause database and application will not be available during this time.</pre></td>
                    </tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#a7ce83">
                    <tbody><tr>
                        <td valign="top" width="20%" align="right"><b>Server:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">lxdnp25j</font></td>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>OS:</b></td>
                        <td valign="top" width="30%" align="left">Linux</td>
                        <td valign="top" width="20%" align="right"><b>Work Status:</b></td>
                        <td valign="top" width="30%" align="left"><font color="red"><b>WAITING</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Usage:</b></td>
                        <td valign="top" width="30%" align="left">PRODUCTION</td>
                        <td valign="top" width="20%" align="right"><b>Work Start:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 00:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Location:</b></td>
                        <td valign="top" width="30%" align="left">DENVER</td>
                        <td valign="top" width="20%" align="right"><b>Work End:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 02:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Time Zone:</b></td>
                        <td valign="top" width="30%" align="left">America/Denver</td>
                        <td valign="top" width="20%" align="right"><b>Duration:</b></td>
                        <td valign="top" width="30%" align="left">00:02:00</td>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Maintenance:</b></td>
                        <td valign="top" width="30%" align="left">MON,THU 00:00 120</td>
                        <td valign="top" width="20%" align="right"><b># Responded:</b></td>
                        <td valign="top" width="30%" align="left">1</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td valign="top" width="20%" align="right"><b># Waiting:</b></td>
                        <td valign="top" width="30%" align="left">31</td>
                    </tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#ffffcc">
                    <tbody><tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Netpin:</b></td>
                        <td valign="top" width="30%" align="left">17340</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Response Date:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 08:55</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Response:</b></td>
                        <td valign="top" width="30%" align="left"><font color="green"><b>APPROVED</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Responder Name:</b></td>
                        <td valign="top" width="30%" align="left">Greg Parkin</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Connection:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">hcdnx18j-&gt;lxdnp25j</font></td>
                        <td valign="top" width="20%" align="right"><b>Group:</b></td>
                        <td valign="top" width="30%" align="left">DBA</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Page On-Call:</b></td>
                        <td valign="top" width="30%" align="left">N</td>
                        <td valign="top" width="20%" align="right"><b>Type:</b></td>
                        <td valign="top" width="30%" align="left">FYI</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Send Email:</b></td>
                        <td valign="top" width="30%" align="left">Y</td>
                        <td valign="top" width="20%" align="right">&nbsp;</td>
                        <td valign="top" width="30%" align="left">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left">
                            <b>05/22/2017 08:55</b> Greg Parkin<br>
                            <b>APPROVED</b> CCT700044352 NETPIN 17340 approved work for server lxdnp25j.<br><br>
                        </td></tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#a7ce83">
                    <tbody><tr>
                        <td valign="top" width="20%" align="right"><b>Server:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">lxdnp22j</font></td>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>OS:</b></td>
                        <td valign="top" width="30%" align="left">Linux</td>
                        <td valign="top" width="20%" align="right"><b>Work Status:</b></td>
                        <td valign="top" width="30%" align="left"><font color="red"><b>WAITING</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Usage:</b></td>
                        <td valign="top" width="30%" align="left">PRODUCTION</td>
                        <td valign="top" width="20%" align="right"><b>Work Start:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 00:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Location:</b></td>
                        <td valign="top" width="30%" align="left">DENVER</td>
                        <td valign="top" width="20%" align="right"><b>Work End:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 02:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Time Zone:</b></td>
                        <td valign="top" width="30%" align="left">America/Denver</td>
                        <td valign="top" width="20%" align="right"><b>Duration:</b></td>
                        <td valign="top" width="30%" align="left">00:02:00</td>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Maintenance:</b></td>
                        <td valign="top" width="30%" align="left">MON,FRI 00:00 120</td>
                        <td valign="top" width="20%" align="right"><b># Responded:</b></td>
                        <td valign="top" width="30%" align="left">1</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td valign="top" width="20%" align="right"><b># Waiting:</b></td>
                        <td valign="top" width="30%" align="left">29</td>
                    </tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#ffffcc">
                    <tbody><tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Netpin:</b></td>
                        <td valign="top" width="30%" align="left">17340</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Response Date:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 08:55</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Response:</b></td>
                        <td valign="top" width="30%" align="left"><font color="green"><b>APPROVED</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Responder Name:</b></td>
                        <td valign="top" width="30%" align="left">Greg Parkin</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Connection:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">hcdnx16j-&gt;lxdnp22j</font></td>
                        <td valign="top" width="20%" align="right"><b>Group:</b></td>
                        <td valign="top" width="30%" align="left">DBA</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Page On-Call:</b></td>
                        <td valign="top" width="30%" align="left">N</td>
                        <td valign="top" width="20%" align="right"><b>Type:</b></td>
                        <td valign="top" width="30%" align="left">FYI</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Send Email:</b></td>
                        <td valign="top" width="30%" align="left">Y</td>
                        <td valign="top" width="20%" align="right">&nbsp;</td>
                        <td valign="top" width="30%" align="left">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left">
                            <b>05/22/2017 08:55</b> Greg Parkin<br>
                            <b>APPROVED</b> CCT700044352 NETPIN 17340 approved work for server lxdnp22j.<br><br>
                        </td></tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#a7ce83">
                    <tbody><tr>
                        <td valign="top" width="20%" align="right"><b>Server:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">lxdnp23j</font></td>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>OS:</b></td>
                        <td valign="top" width="30%" align="left">Linux</td>
                        <td valign="top" width="20%" align="right"><b>Work Status:</b></td>
                        <td valign="top" width="30%" align="left"><font color="red"><b>WAITING</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Usage:</b></td>
                        <td valign="top" width="30%" align="left">PRODUCTION</td>
                        <td valign="top" width="20%" align="right"><b>Work Start:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 00:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Location:</b></td>
                        <td valign="top" width="30%" align="left">DENVER</td>
                        <td valign="top" width="20%" align="right"><b>Work End:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 02:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Time Zone:</b></td>
                        <td valign="top" width="30%" align="left">America/Denver</td>
                        <td valign="top" width="20%" align="right"><b>Duration:</b></td>
                        <td valign="top" width="30%" align="left">00:02:00</td>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Maintenance:</b></td>
                        <td valign="top" width="30%" align="left">TUE,SAT 00:00 120</td>
                        <td valign="top" width="20%" align="right"><b># Responded:</b></td>
                        <td valign="top" width="30%" align="left">1</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td valign="top" width="20%" align="right"><b># Waiting:</b></td>
                        <td valign="top" width="30%" align="left">29</td>
                    </tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#ffffcc">
                    <tbody><tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Netpin:</b></td>
                        <td valign="top" width="30%" align="left">17340</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Response Date:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 08:55</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Response:</b></td>
                        <td valign="top" width="30%" align="left"><font color="green"><b>APPROVED</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Responder Name:</b></td>
                        <td valign="top" width="30%" align="left">Greg Parkin</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Connection:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">hcdnx16j-&gt;lxdnp23j</font></td>
                        <td valign="top" width="20%" align="right"><b>Group:</b></td>
                        <td valign="top" width="30%" align="left">DBA</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Page On-Call:</b></td>
                        <td valign="top" width="30%" align="left">N</td>
                        <td valign="top" width="20%" align="right"><b>Type:</b></td>
                        <td valign="top" width="30%" align="left">FYI</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Send Email:</b></td>
                        <td valign="top" width="30%" align="left">Y</td>
                        <td valign="top" width="20%" align="right">&nbsp;</td>
                        <td valign="top" width="30%" align="left">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left">
                            <b>05/22/2017 08:55</b> Greg Parkin<br>
                            <b>APPROVED</b> CCT700044352 NETPIN 17340 approved work for server lxdnp23j.<br><br>
                        </td></tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#a7ce83">
                    <tbody><tr>
                        <td valign="top" width="20%" align="right"><b>Server:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">lxdnp24j</font></td>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>OS:</b></td>
                        <td valign="top" width="30%" align="left">Linux</td>
                        <td valign="top" width="20%" align="right"><b>Work Status:</b></td>
                        <td valign="top" width="30%" align="left"><font color="red"><b>WAITING</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Usage:</b></td>
                        <td valign="top" width="30%" align="left">PRODUCTION</td>
                        <td valign="top" width="20%" align="right"><b>Work Start:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 00:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Location:</b></td>
                        <td valign="top" width="30%" align="left">DENVER</td>
                        <td valign="top" width="20%" align="right"><b>Work End:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 02:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Time Zone:</b></td>
                        <td valign="top" width="30%" align="left">America/Denver</td>
                        <td valign="top" width="20%" align="right"><b>Duration:</b></td>
                        <td valign="top" width="30%" align="left">00:02:00</td>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Maintenance:</b></td>
                        <td valign="top" width="30%" align="left">SUN,WED 00:00 120</td>
                        <td valign="top" width="20%" align="right"><b># Responded:</b></td>
                        <td valign="top" width="30%" align="left">1</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td valign="top" width="20%" align="right"><b># Waiting:</b></td>
                        <td valign="top" width="30%" align="left">29</td>
                    </tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#ffffcc">
                    <tbody><tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Netpin:</b></td>
                        <td valign="top" width="30%" align="left">17340</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Response Date:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 08:55</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Response:</b></td>
                        <td valign="top" width="30%" align="left"><font color="green"><b>APPROVED</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Responder Name:</b></td>
                        <td valign="top" width="30%" align="left">Greg Parkin</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Connection:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">hcdnx17j-&gt;lxdnp24j</font></td>
                        <td valign="top" width="20%" align="right"><b>Group:</b></td>
                        <td valign="top" width="30%" align="left">DBA</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Page On-Call:</b></td>
                        <td valign="top" width="30%" align="left">N</td>
                        <td valign="top" width="20%" align="right"><b>Type:</b></td>
                        <td valign="top" width="30%" align="left">FYI</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Send Email:</b></td>
                        <td valign="top" width="30%" align="left">Y</td>
                        <td valign="top" width="20%" align="right">&nbsp;</td>
                        <td valign="top" width="30%" align="left">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left">
                            <b>05/22/2017 08:55</b> Greg Parkin<br>
                            <b>APPROVED</b> CCT700044352 NETPIN 17340 approved work for server lxdnp24j.<br><br>
                        </td></tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#a7ce83">
                    <tbody><tr>
                        <td valign="top" width="20%" align="right"><b>Server:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">lxdnp26j</font></td>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>OS:</b></td>
                        <td valign="top" width="30%" align="left">Linux</td>
                        <td valign="top" width="20%" align="right"><b>Work Status:</b></td>
                        <td valign="top" width="30%" align="left"><font color="red"><b>WAITING</b></font></td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Usage:</b></td>
                        <td valign="top" width="30%" align="left">PRODUCTION</td>
                        <td valign="top" width="20%" align="right"><b>Work Start:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 00:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Location:</b></td>
                        <td valign="top" width="30%" align="left">DENVER</td>
                        <td valign="top" width="20%" align="right"><b>Work End:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 02:00</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Time Zone:</b></td>
                        <td valign="top" width="30%" align="left">America/Denver</td>
                        <td valign="top" width="20%" align="right"><b>Duration:</b></td>
                        <td valign="top" width="30%" align="left">00:02:00</td>
                    </tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Maintenance:</b></td>
                        <td valign="top" width="30%" align="left">TUE,FRI 00:00 120</td>
                        <td valign="top" width="20%" align="right"><b># Responded:</b></td>
                        <td valign="top" width="30%" align="left">1</td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                        <td valign="top" width="20%" align="right"><b># Waiting:</b></td>
                        <td valign="top" width="30%" align="left">33</td>
                    </tr>
                    </tbody></table>
            </div>
            <div style="width: 825px;">
                <table style="color: black" cellpadding="2" cellspacing="2" width="100%" bgcolor="#ffffcc">
                    <tbody><tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Netpin:</b></td>
                        <td valign="top" width="30%" align="left">17340</td>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Respond By:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue"><b>05/11/2017</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Response Date:</b></td>
                        <td valign="top" width="30%" align="left">05/22/2017 08:55</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Response:</b></td>
                        <td valign="top" width="30%" align="left"><font color="green"><b>APPROVED</b></font></td>
                        <td valign="top" width="20%" align="right"><b>Responder Name:</b></td>
                        <td valign="top" width="30%" align="left">Greg Parkin</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Connection:</b></td>
                        <td valign="top" width="30%" align="left"><font color="blue">hcdnx18j-&gt;lxdnp26j</font></td>
                        <td valign="top" width="20%" align="right"><b>Group:</b></td>
                        <td valign="top" width="30%" align="left">DBA</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Page On-Call:</b></td>
                        <td valign="top" width="30%" align="left">N</td>
                        <td valign="top" width="20%" align="right"><b>Type:</b></td>
                        <td valign="top" width="30%" align="left">FYI</td>
                    </tr>
                    <tr>
                        <td valign="top" width="20%" align="right"><b>Send Email:</b></td>
                        <td valign="top" width="30%" align="left">Y</td>
                        <td valign="top" width="20%" align="right">&nbsp;</td>
                        <td valign="top" width="30%" align="left">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="4" valign="top" align="left">
                            <b>05/22/2017 08:55</b> Greg Parkin<br>
                            <b>APPROVED</b> CCT700044352 NETPIN 17340 approved work for server lxdnp26j.<br><br>
                        </td></tr>
                    </tbody></table>
            </div>
        </center>
