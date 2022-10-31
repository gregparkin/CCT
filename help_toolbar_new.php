<?php
/**
 * help_toolbar_new.php
 *
 * @package   PhpStorm
 * @file      help_toolbar_new.php
 * @author    gparkin
 * @date      6/23/16
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
<a name="a1"><h3>New Ticket</h3></a>
<p style="font-size: 18px;">
    Use these forms to generate a new CCT ticket and work schedule. CCT is more than a change coordination
    notification tool, it is also a work scheduler and uses the CSC OS maintenance window to do this.
</p>

<br>
<a name="a2"><h3>New Work Request Creation</h3></a>
<p style="font-size: 18px;">
    <img src="help/toolbar_new/toolbar_new1.png"><br>
    As you look at this form you see four tabs running along the top. An explanation of each tab are described below.
    <br><br>
    There are 13 work activities available to classify the work you are doing. They are as follows: Database, Emergency,
    Firmware, Hardware, Other, Patching APP, Patching DB, Patching OS, Patching OTHER, Project, Remediation, Security,
    and Software.
    <br><br>
    The Reboot Required: Yes or No indicates whether work on a server will be followed by a reboot. This is important
    for users to know because it may effect your applications whether they need to be restarted manually or whether they
    reside on another server housed within the server you are working on. CCT is able to pull in other contacts that
    need to be notified when this reboot flag is set to yes.
    <br><br>
    Approvals Required: Yes or No is a flag to indicate whether you require work approval from users before processing.
    If the approval required flag is no, notifications are still sent out, but no further action is required on their
    part.
    <br><br>
    The three month calendar block is for you to look at as a reference as you setup the email reminder dates, respond
    by dates, and scheduled work start dates.
    <br><br>
    By default when you create a new work request, CCT will calculate set the dates accordingly. But you are able to
    click on each of the dates and a mini calendar will pop up where you can select a new date.
    <br><br>
    There are three email reminder dates. CCT automation will keep track of these dates and will send out email reminders
    to contacts who have not yet approved the work requests. This should save you from having to send out individual
    email messages to get your clients to approve the work.
    <br><br>
    The Respond By date is tell all contacts when you need them to respond by. You will notice that this date is set
    by default to be one day past all the email reminders. So they will continue to get notifications until the work
    gets approved.
    <br><br>
    Scheduled Work Start date is when you want CCT to begin scheduling work activity. As you can see this date needs
    to be a date after the Respond By date to give users enough time to approve the work. CCT retrieves all the OS
    maintenance window information from CSC for each server you are working on and generates a work start and end
    dates from the scheduled work start date. You never have to specify a work start/end date because CCT does that
    for you based upon the scheduled work start date and the server's OS maintenance window.
    <br><br>
    Remedy Ticket is a place where you can reference a Remedy CM ticket if you need too. CCT no longer requires a
    Remedy ticket in order to create a CCT work request. However, current CMP policies may still require an
    additional Remedy ticket so follow whatever process is required by CMP's Change Management.
    <br><br>
    The two buttons next to the Remedy ticket field are used to View the Remedy ticket and to Copy the text from the
    Remedy ticket into the CCT ticket.
    <br><br>
    <img src="help/toolbar_new/toolbar_new1a.png"><br>
    As you can see if you enter a Remedy ticket and click the View button, this dialog box will appear with information
    about the ticket.
    <br><br>
    <img src="help/toolbar_new/toolbar_new1b.png"><br>
    In this image you see I have clicked on the Copy To button and the text from the Remedy ticket has been
    successfully copied into the CCT ticket.
    <br><br>
    <img src="help/toolbar_new/toolbar_new1c.png"><br>
    After the Copy To button has been pressed, you can look at the tabs for Description, Implementation, etc., and
    see that the text from the Remedy ticket has been copied into the CCT ticket.
</p>

<br>
<a name="a3"><h3>New CCT Work Request - Description / Implementation Instructions (tab)</h3></a>
<p style="font-size: 18px;">
    <img src="help/toolbar_new/toolbar_new2.png"><br>
    This tab is where you document your work description and the implemenation instructions. This is the same information
    you would document in a Remedy ticket and is sent out to the users for notification and work approval.
</p>

<br>
<a name="a4"><h3>New CCT Work Request - Backoff Plans (tab)</h3></a>
<p style="font-size: 18px;">
    <img src="help/toolbar_new/toolbar_new3.png"><br>
    Document your backoff plan in the event that your changes failed. These instructions will provide users with
    a understanding that you can restore their environment if your changes are unsuccessful.
</p>

<br>
<a name="a5"><h3>New CCT Work Request - Business Reason / Impact (tab)</h3></a>
<p style="font-size: 18px;">
    <img src="help/toolbar_new/toolbar_new4.png"><br>
    Lastly, this tab is used to document the business reason for the changes and what impact to users can be expected
    while the changes are being implemented.
</p>

<br>
<a name="a6"><h3>List of CSC Banners to pull contacts from</h3></a>
<p style="font-size: 18px;">
    <img src="help/toolbar_new/toolbar_new5.png"><br>
    This is a list of CSC Banners that CCT pulls Net-pin contact information from. Be default all these banners are
    selected and should be targeted, but there are times when you may only want to target a specific group. You can
    uncheck and check the boxes next to CSC banners as you desire.
    <br><br>
    Optionally, CCT still uses a internal subscriber list which is maintained under the Lists icon on the toolbar called
    Subscriber Lists. CCT will still use subscriber lists because there are several groups who do not wish to use CSC or
    NET for contact and paging notifications, but still want CCT work notifications. These are usually application
    test and development groups.
</p>

<br>
<a name="a7"><h3>Exclude Virtual Server Contacts</h3></a>
<p style="font-size: 18px;">
    <img src="help/toolbar_new/toolbar_new_virtual_host_contacts.png"><br>
    There may be a situation where nodes or virtual hosts in a complex have been failed over to another
    set of nodes. In this case there is no need to include the virtual host contacts for work being
    done on these parent servers. If this is your case, check this box to exclude all virtual contacts
    from being notified of the pending work.
</p>

<br>
<a name="a8"><h3>Disable Scheduler</h3></a>
<p style="font-size: 18px;">
    <img src="help/toolbar_new/toolbar_new_disable_scheduler.png"><br>
    Check this box if you do not want CCT to build a work schedule for each server using OS
    Maintenance Windows. CCT will refer clients to look at the Remedy ticket for server schedule
    details. Please note that when you use this option there is no conflict analysis done with
    other work that may conflict with your work. Also no checks are done to ensure you are not
    scheduling work during a minimal change window.
</p>

<br>
<a name="a9"><h3>Select the servers - Asset Manager (tab)</h3></a>
<p style="font-size: 18px;">
    <img src="help/toolbar_new/toolbar_new6.png"><br>
    There are three ways to select servers for your CCT work request. One is by using the data in the first tab called
    Asset Manager, the second one is to use pre-build ssystem lists, and the third way is to copy and paste hostnames,
    applications and databases in a text box.
    <br><br>
    Selecting servers by entering Asset Manager criteria is not normally a preferred method. System Lists is generally
    the preferred method followed by entering server, apps, and databases names into the third tab.
    <br><br>
    From the Asset Manager screen you can select one or more targeting information from the 6 selection windows. CCT
    will then create a list of servers from Asset Manager matching your criteria.
    <br><br>
    If you provide a partial IP address in the text box, CCT can pull all the servers that start with that IP address.
    This is just another way CCT can build a server list for you.
</p>

<br>
<a name="a10"><h3>Select the servers - System Lists (tab)</h3></a>
<p style="font-size: 18px;">
    <img src="help/toolbar_new/toolbar_new7.png"><br>
    When you select this tab, a list of Net-Group owned system lists are displayed. These are prebuilt server lists that
    you or a member of you Net-Group owners have created. To manage these group system lists, you would click on the
    List icon on the toolbar and select Server Lists. Once you are there in that screen you can click on the Help button
    again to get further information on how to edit and manage you lists.
    <br><br>
    So from the Syste Lists tab, you would select one of your server lists and CCT will build a work schedule for the
    servers found in that list.
</p>

<br>
<a name="a11"><h3>Select the servers - Hosts, Applications, Databases (tab)</h3></a>
<p style="font-size: 18px;">
    <img src="help/toolbar_new/toolbar_new8.png"><br>
    The last tab in the Select the servers screen is for manual entries. You can enter one or more hostnames, application
    acronyms, and database acronyms. CCT will then look up the servers using this criteria using data found in Asset
    Manager, the MAL and the MDL.
    <br><br>
    <b>*** WARNING ***</b><br>
    When entering information from a spreadsheet such as Excel, do not copy and paste from Excel to this HTML text box.
    If you do, control characters get copied with your server names and HTML/CCT does not like your input. When this
    happens unexpected results can happen. If you need to copy from Excel to this box you need to copy the server lists
    to empty text file or to the Windows Notepad first so you can clean up the control characters first. Then select and
    copy the text from these new text files and paste them into this CCT text box. It should work much better this time.
</p>

<br>
<a name="a12"><h3>Submit and Reset Buttons</h3></a>
<p style="font-size: 18px;">
    <img src="help/toolbar_new/toolbar_new9.png"><br>
    When you have everything entered you click on the Submit button. At this point CCT will create a new work request
    and place the it in DRAFT mode. A summary of the generated ticket will be presented when it is finished and you
    will have an opportunity place it in Active mode or you can open it up under the open up the ticket by clicking on
    the Open icon on the toolbar and selecting Group Tickets. You will see your ticket in DRAFT mode and there you can
    click on the ticket and activate it.
</p>

<br>
<a name="a13"><h3>Summary</h3></a>
<p style="font-size: 18px;">
    <img src="help/toolbar_new/summary_status.png"><br>
    This is an example of the summary output you will see when CCT finishes generating your work request. If there
    is any problems finding a server you have requested it will tell you in this report. Also, you will notice there
    are three buttons along the bottom. Activate will make the ticket live and notifications will be sent out to the
    contacts. Delete will remove the ticket from CCT and no further action is required. The Open button will make the
    ticket available as if you clicked on the Open - By Group icon from the toolbar.<br><br>
    As the CCT work scheduler uses the servers OS maintenance windows to figure out day and time the work can be done
    it will look for other tickets for that server that has already been scheduled in that time slot. If there is, CCT
    will look for the next available date to schedule the work. This feature will ensure that overlapping work will
    not be scheduled at the same time.
</p>

<br>
<a name="a14"><h3>Example Email that Users Receive</h3></a>
<p style="font-size: 18px;">
    <center><div style="width: 825px;">
            <font size="+2" color="red" face="Zapf Chancery, cursive"><b>Change Coordination Tool - Email Notification</b></font>
<p align='left'>Vamsi K,</p>
<p align='left'>
    The following is a detailed notification outlining the status of pending work requiring your approval or attention.
</p>
<u>Please read through this email carefully!</u></p>
<p align='left'>Logon to CCT to and click on the Approval button to approve work for the servers you support.</p>
<?php
switch ( $_SERVER['SERVER_NAME'] )
{
case 'cct.corp.intranet':
	printf("<p><a href='https://cct.corp.intranet'>https://cct.corp.intranet</a></p>\n");
	break;
case 'lxomp47x.corp.intranet':
	printf("<p><a href='https://lxomp47x.corp.intranet'>https://lxomp47x.corp.intranet/cct7</a></p>\n");
	break;
case 'cct.test.intranet':
	printf("<p><a href='https://cct.corp.intranet'>https://cct.corp.intranet</a></p>\n");
	break;
case 'vlodts022.test.intranet':
	printf("<p><a href='https://vlodts022.test.intranet/cct7'>https://vlodts022.test.intranet/cct7</a></p>\n");
	break;
default:
	printf("<p><a href='http://cct7.localhost'>http://cct7.localhost</a></p>\n");
	break;
}
?>
<p align='left'>
    If there are servers that are parent hosts for virtual servers you support then you will be asked to approve the
    work for the parent servers where there is a reboot that will effect your virtual server.
</p>
<p align='left'>
    <b>Please Note:</b> All dates and times in this email are shown in <u>Mountain Time</u>. When you logon to CCT all
    dates and times are shown in your local time zone. This means that the dates and times in this email may not match
    what you see in the CCT application.
</p>
<p align='left'>
    CCT work notifications go out to Netpin Groups so your team members will receive the same notification. Any member
    of your Net Group team can approve or reject work requests so it is important that you work closely with your team
    in deciding who should be approving the work.
</p>

<table border="1">
    <tr>
        <td>
            <div style="width: 825px;"><table bgcolor="#ECE9D8" width="100%" cellspacing="1" cellpadding="1"
                                              style="height: 100%; color: black">
                    <tr>
                        <td align="right" valign="top"><b>CCT Ticket:</b></td>
                        <td align="left" valign="top">CCT700009054</td>
                        <td align="right" valign="top"><b>Owner Name:</b></td>
                        <td align="left" valign="top">Yogeswari Mekala</td>
                    </tr>
                    <tr>
                        <td align="right" valign="top"><b>Creation Date:</b></td>
                        <td align="left" valign="top">12/12/2016 11:22</td>
                        <td align="right" valign="top"><b>Owner Email:</b></td>
                        <td align="left" valign="top">mekala.yogeswari@in.ibm.com</td>
                    </tr>
                    <tr>
                        <td align="right" valign="top"><b>Ticket Status:</b></td>
                        <td align="left" valign="top"><font color="blue"><b>ACTIVE</b></font></td>
                        <td align="right" valign="top"><b>Owner Job Title:</b></td>
                        <td align="left" valign="top"></td>
                    </tr>
                    <tr>
                        <td align="right" valign="top"><b>Work Activity:</b></td>
                        <td align="left" valign="top">UNIX</td>
                        <td align="right" valign="top"><b>Manager Name:</b></td>
                        <td align="left" valign="top">Naga Venkateswara Rao Gudise</td>
                    </tr>
                    <tr>
                        <td align="right" valign="top"><b>Reboot Required:</b></td>
                        <td align="left" valign="top">Y</td>
                        <td align="right" valign="top"><b>Manager Email:</b></td>
                        <td align="left" valign="top">venkat.gudise@in.ibm.com</td>
                    </tr>
                    <tr>
                        <td align="right" valign="top"><b>Approvals Required:</b></td>
                        <td align="left" valign="top">Y</td>
                        <td align="right" valign="top"><b>Manager Job Title:</b></td>
                        <td align="left" valign="top"></td></tr>
                    <tr>
                        <td colspan="4">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="right" valign="top"><b>Email Reminder 1:</b></td>
                        <td align="left" valign="top">12/22/2016</td>
                        <td align="right" valign="top"><b>Last Modified:</b></td>
                        <td align="left" valign="top">01/12/2017 04:14</td>
                    </tr>
                    <tr>
                        <td align="right" valign="top"><b>Email Reminder 2:</b></td>
                        <td align="left" valign="top">12/22/2016</td>
                        <td align="right" valign="top"><b>Modified By:</b></td>
                        <td align="left" valign="top">Yogeswari Mekala</td>
                    </tr>
                    <tr>
                        <td align="right" valign="top"><b>Email Reminder 3:</b></td>
                        <td align="left" valign="top">12/22/2016</td>
                        <td align="right" valign="top"><b>Total Servers:</b></td>
                        <td align="left" valign="top">4</td>
                    </tr>
                    <tr>
                        <td align="right" valign="top"><b>Respond By:</b></td>
                        <td align="left" valign="top"><font color="blue"><b>12/22/2016</b></font></td>
                        <td align="right" valign="top"><b>Servers Approved:</b></td>
                        <td align="left" valign="top">4</td>
                    </tr>
                    <tr>
                        <td align="right" valign="top"><b>Schedule Start:</b></td>
                        <td align="left" valign="top">12/31/1969 17:00</td>
                        <td align="right" valign="top"><b>Window Begin:</b></td>
                        <td align="left" valign="top">01/20/2017 23:59</td>
                    </tr>
                    <tr>
                        <td align="right" valign="middle"><b>Remedy CM:</b></td>
                        <td align="left" valign="middle">CM0000321848</td>
                        <td align="right" valign="top"><b>Window End:</b></td>
                        <td align="left" valign="top">01/21/2017 03:00</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" colspan="4"><b><u>Description</u></b><br>
                            As per HD00009136578:Need  to reboot NCON, SD servers with the below instructions
                            HPDNP41C,HPOMP83M and SERVICE DELIVERY servers HPDNP42C,HPOMP87M application servers
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" colspan="4"><b><u>Implementation/Instructions</u></b><br>
                            Implementation Date: 1/20/2017 to 1/21/2017
                            Start and End Time: 11:59 pm PM (MT) to 3:00 AM (MT)
                            Join the conference: +1 206-462-4305 , Conf. Code: 9342794

                            IMPLEMENTATION STEPS FOR NCON:

                            NCON:

                            HPDNP41C:
                            ?? MITS-ALL -- Halt the service guard Package.
                            ?? MITS-ALL -- Reboot HPDNP41C
                            ?? MITS-ALL -- Failover the application fromHPDNP39Cto HPDNP41C
                            ?? AIP -- Verify the application health check on HPDNP41C and leave the application here.
                            ?? MITS-ALL -- Unfreeze the service Guard Package.

                            HPOMP83M:
                            ?? MITS-ALL -- Halt the service guard Package.
                            ?? MITS-ALL -- Reboot server HPOMP83M
                            ?? MITS-ALL--Failover the application from HPOMP81Mto HPOMP83M
                            ?? AIP -- Verify the application health check on HPOMP83M and leave the application here.
                            ?? MITS-ALL -- Unfreeze the service Guard Package.

                            IMPLEMENTATION STEPS SERVICE DELIVERY:

                            HPDNP42C:
                            ?? MITS-ALL -- Halt the service guard package.
                            ?? MITS-ALL -- Reboot HPDNP42C
                            ?? MITS-ALL -- Failover the application from HPDNP40Cto HPDNP42C
                            ?? AIP -- Verify the application health check on HPDNP42C and leave the application here.
                            ?? MITS-ALL -- Unfreeze the service guard package.

                            HPOMP87M:
                            ?? MITS-ALL -- Halt the service guard package.
                            ?? MITS-ALL -- Reboot server HPOMP87M
                            ?? MITS-ALL -- Failover the application from HPOMP85Mto HPOMP87M
                            ?? AIP -- Verify the application health check on HPOMP87M and leave the application here.
                            ?? MI
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>
</center>
</p>

<br>
<a name="a15"><h3>Note</h3>
<p style="font-size: 18px;">
    All email is compiled and sent out once a day except for the emails you send immediately through the ticket,
    servers, and contacts Email tab. Those emails go out right away.<br><br>
    For the once a day emails, emails are spooled into one email per user so they won't get several emails, but the
    emails can be lengthly depending on the number of servers with applications they support.
</p>
