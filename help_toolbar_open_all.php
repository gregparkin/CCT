<?php
/**
 * help_toolbar_open_all.php
 *
 * @package   PhpStorm
 * @file      help_open.php
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

//
// header() is a PHP function for modifying the HTML header information
// going to the client's browser. Here we tell their browser not to cache
// the page coming in so their browser will always rebuild the page from
// scratch instead of retrieving a copy of one from its cache.
//
// header("Expires: " . gmstrftime("%a, %d %b %Y %H:%M:%S GMT"));
//
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

//
// Disable buffering - This is what makes the loading screen work properly.
//
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('output_buffering', 'Off');
@ini_set('implicit_flush', 1);

ob_implicit_flush(1); // Flush buffers

for ($i = 0, $level = ob_get_level(); $i < $level; $i++)
{
	ob_end_flush();
}
?>
<html>
<head>
    <?php
	switch ( $_SERVER['SERVER_NAME'] )
	{
		case 'cct.corp.intranet':
		    ?><base href="https://cct.corp.intranet/"><?php
			break;
		case 'lxomp47x.corp.intranet':
		    ?><base href="https://lxomp47x.corp.intranet/cct7/"><?php
			break;

		case 'cct.test.intranet':
			?><base href="https://cct.test.intranet/"><?php
			break;
		case 'vlodts022.test.intranet':
			?><base href="https://vlodts022.test.intranet/cct7/"><?php
			break;

		default:
			?><base href="http://cct7.localhost/"><?php
			break;
	}
    ?>
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE10">
    <title>Change Coordination Tool</title>
    <link rel="icon" type="image/png" href="images/handshake1.ico">
    <script src="js/jquery-2.1.4.js"></script>
    <script type="text/javascript" src="js/jstz.js"></script><!-- Determines user's timezone of the PC their using -->
</head>
<body>
    <p><b><u>All Tickets</u></b></p>
    <p>
        The "Open" icon on the tool bar contains two options for viewing CCT tickets. You can open either all tickets
        created by everyone or you can open tickets by the Net-Pin tool groups you belong too. The only difference in the
        two options is a filter setting that shows the user what they want to see.
    </p>

    <br>
    <h3>Main Grid</h3>
    <p style="font-size: 18px;">
        The following is a grid containing all the tickets found in CCT.
        <img src="help/toolbar_open_all/toolbar_open1.png">
    </p>

    <br>
    <h3>Paging Through the Grid</h3>
    <p style="font-size: 18px;">
        <img align="top" src="help/toolbar_open_all/toolbar_open2.png"><br>
        You page through the grid by clicking on the arrow icons at the bottom (center) of the grid. The first icon starting
        at the left (bar - left arrow, left arrow) will page back to the top of the grid. The second icon (left arrow,
        left arrow) takes you back one page. The third icon (right arrow, right arrow) takes you one page forward. And the
        last icon (right arrow, right arrow, bar) takes you to the last page in the grid.
    </p>

    <br>
    <h3>Useful Grid Icons</h3>
    <p style="font-size: 18px;">
        <img align="bottom" src="help/toolbar_open_all/toolbar_open6.png"><br>
        These icons on the far bottom left of the grid can be used to <b><u>Search</u></b>, <b><u>Export to Excel</u></b>,
        <b><u>Refresh</u></b> the grid, and <b><u>Sort Order</u></b> where you choose how you want the grid sorted.
    </p>

    <br>
    <h3>Search</h3>
    <p style="font-size: 18px;">
        <img align="bottom" src="help/toolbar_open_all/search1.png">
        This is what the search window looks like when you click on the magnifying glass. Once you setup your search
        criteria you would click on the Find button. You can search on multiple columns to drill down to what you want.
        When you close the search menu you can page up and down through your search results. To reset the search
        options, click on the magnifying glass icon again and click on the Reset button. You will now see all the
        default tickets again.
    </p>

    <br>
    <h3>Export to Excel</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/excel.png"><br>
        When you click on the suitcase icon, the entire grid will be downloaded to your PC in cvs format which is
        compatible with Excel and LibreOffice Calc. Just remember it is the whole grid and not just the data you are
        viewing in the current grid page.
    </p>

    <br>
    <h3>Sort Order</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/help_toolbar_open_sort.png"><br>
        Clicking on the star icon will bring up this dialog box where you can choose what columns you would
        like the grid sorted by. You can also choose the column sort order and direction (ascending or descending).
    </p>

    <br>
    <h3>Sub-Grids</h3>
    <p style="font-size: 18px;">
        In the far left column of the grid you will notice a <img align="top" src="help/toolbar_open_all/toolbar_open3.png"><br><br>
        <img src="help/toolbar_open_all/toolbar_open4.png"><br>
        This opens up a sub-grid under the record showing the servers associated with the ticket. When that sub-grid opens
        with the servers you will see another + sign that you can click on. When you click that icon a list of all the
        contacts will be displayed. Lastly, the contacts sub-grid will have a plus sign that will show you how this group
        of people are connected to the server being worked on. <br><br>
        For example the actual work may be on a physical server that houses a bunch of VMWARE servers. So this last grid
        will show the contacts how they are connected to the physical server where they have no responsibility for, but
        where they need to be notified of pending work which may effect their application server.
    </p>

    <br>
    <h3>Ticket Dialog Box - General (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/ticket1.png"><br>
        If you want to see more information about a ticket or perform some kind of action on it, click on on the row where
        the ticket is located. A dialog box will appear giving you a list of options you can perform. If you are not the
        CCT owner or CCT admin you will not be able to cancel or update the ticket. But you can look at detail information
        about the work request, log messages and send email out.
    </p>

    <h3>Ticket Dialog Box - General (tab) - View Remedy Ticket</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/cm_ticket.png"><br>
        Remedy tickets are no longer required in order for CCT to work. However, there is a place in the CCT ticket where
        you can enter a Remedy ticket that references this CCT ticket. There is also a View button that you click on that
        can retrieve the Remedy ticket directly from the Remedy CM database. Be patience here, because it can sometimes
        take time to retrieve and display the ticket here.
    </p>

    <h3>Ticket Dialog Box - General (tab) - Copy To</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/cm_copy_to.png"><br>
        The Copy To button next to the Remedy CM ticket number and View button is used to copy down Remedy ticket
        information into your CCT ticket. This button is for your convenience when you want the text in your
        CCT ticket to match what is in the Remedy ticket and you don't want to copy and paste or retype everything.
        <br><br>
        Before it overwrites any text that might be in your CCT ticket, you will be prompted with a message asking
        for your intentions before proceeding with the operation.
    </p>

    <br>
    <h3>Ticket Dialog Box - Description/Implementation Instructions (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/ticket2.png"><br>
        This tab in the Ticket Dialog Box and is where you input information about the work request and your implementation
        instructions. It is the same information you would find in a Remedy ticket.
    </p>

    <br>
    <h3>Ticket Dialog Box - Backout Plans (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/ticket3.png"><br>
        This is where you describe your backout plans in the event your work was unsuccessful.
    </p>

    <br>
    <h3>Ticket Dialog Box - Business Reasons/Impact (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/ticket5.png"><br>
        This tab is used to documentation business reasons for the changes and what the impact are to the users.
    </p>

    <br>
    <h3>Ticket Dialog Box - Sendmail (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/ticket6.png"><br>
        This tab is used to send out email to all contacts identified by systems being worked on. If you are a application
        owner and there are servers in this ticket that you do not support, then it would be better if you use the sendmail
        function that is available in the Server Dialog box. (See below for details.)<br><br>
        This email form should be self explanatory. Simply type in a message under 4000 characters and click the Sendmail
        button.
    </p>

    <br>
    <h3>Ticket Dialog Box - Log (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/ticket7.png"><br>
        Any notes generated or added by the users at the ticket level are shown here in the Log tab. There is a log
        at the system and contact level as well. All log information is sent to users anytime something is added to
        ensure that everyone is in the loop.<br><br>
        <img src="help/toolbar_open_all/ticket8.png">
        The green text area is where the log is actually displayed and cannot be updated or changed. The yellow text area
        is where you would type additional information. After you type your note you click on the Add Entry button and it
        adds it to the ticket log. Email will be sent out to everyone at set intervals so they can see your messages so
        remember, everything you type here everyone sees. Be professional.
    </p>

    <br>
    <h3>Server Dialog Box - Server (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/system1.png"><br>
        When the sub-grid is open under a ticket in the main grid, a list of servers is displayed. When you click on a row
        for server you are interested in a Server Dialog Box will appear. If you are the owner of the ticket you have some
        options to Cancel, Reschedule, or Reset Original schedule dates. An explanation for each button is displayed in the
        dialog box.
    </p>

    <br>
    <h3>Server Dialog Box - Sendmail (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/system2.png"><br>
        If you want to send an email to all the clients for this server, this is where you would do it. It works just like
        the sendmail option for tickets and contacts only it targets only clients for this server. Simply type in your
        messages and click the Sendmail button.
    </p>

    <br>
    <h3>Server Dialog Box - Log (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/system3.png"><br>
        This is the log for ticket server. All messages about the server are shown in the green text box. To add to the
        log, type your message in the yellow text area and click the the Add Entry button. Any new entries will be sent
        out to all contacts associated with this server.
    </p>

    <br>
    <h3>Contact Dialog Box - Respond (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/contact1.png"><br>
        This dialog box opens when you click on a contact record under the server sub-grid when the contact sub-grid is
        opened. If you are the ticket owner you can approve the work for others, but your name will recorded as the one
        who approved the work, so make sure you have prior written documentation first before you approve work for someone
        else.<br><br>
        Additional information on what the different buttons do are listed in the dialog box.
    </p>

    <br>
    <h3>Contact Dialog Box - Netpin Group Members (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/contact2.png"><br>
        This tab simply displays all the members of the current Net-Group Pin # as defined in NET. Use the information
        to see if you need to remove or add someone to your Net-Group. (See the NET application).
    </p>

    <br>
    <h3>Contact Dialog Box - Send Mail (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/contact3.png"><br>
        Use this tab to send email to the list of contacts for this Netpin Group. This is a good place to send out
        additional reminders for contacts to approve the work.<br><br>
        Add your text to the message body text area and click the Sendmail button.
    </p>

    <br>
    <h3>Contact Dialog Box - Log (tab)</h3>
    <p style="font-size: 18px;">
        <img src="help/toolbar_open_all/contact4.png"><br>
        Log notes and turnover on the contact member list level. The text area in green are a list of any previous
        messages entered. The yellow text area is where you add any new notes. Once you have entered a note you would
        click the Add Entry button to save it. Your note will then be shown in the green log area. You cannot remove
        any notes you have added so be sure it is what you want to say before clicking the Add Entry button.
    </p>

</body>
</html>
