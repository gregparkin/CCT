<?php
/**
 * work_schedule.php
 *
 * @package   PhpStorm
 * @file      work_schedule.php
 * @author    gparkin
 * @date      4/14/17
 * @version   7.0
 *
 * @brief     This program will produce a CCT 6 sytle work schedule in the format displayed in the comments
 *            below. The program will accept one or more ticket numbers and then generate a CVS file that
 *            downloads into Excel or LibrCalc. Options are available to convert the dates and times to
 *            any timezone the user desires.
 */

//
// New Report Format - (See: CCT 6 - report_ready_work.php
//
//    THU       Hr.Min                  User       CM            CM        CCT                     Assignment
// 09/12/2013  Duration  Hostname       Approvals  Ticket No.    Status    Status  Classification  Group       Gold  S.H.  Boot
// ----------  --------  -------------  ---------  ------------  --------  ------  --------------  ----------  ----  ----  ----
// MST: 20:00  02.00     LXDENVMPC061   READY      CM0000211803  Turnover  FROZEN  Patching        MITS-ALL    N     N     Y

// Implementor Name  Turnover Notes    CM  Implemenater Notes
// ----------------  ----------------  --  ------------------


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

$lib = new library();  // classes/library.php
$lib->debug_start('work_schedule.html');
date_default_timezone_set('America/Denver');

$lib->globalCounter();

?>
<html>
<link rel="stylesheet" type="text/css" href="css/jqx.base.css">
<link rel="stylesheet" type="text/css" href="css/jqx.bootstrap.css">
<link rel="stylesheet" type="text/css" media="screen" href="css/jquery-ui.css">

<!--
  base, black-tie, blitzer, cupertino, dark-hive, dot-luv, eggplant, excite-bike, flick, hot-sneaks,
  humanity, le-frog, mint-choc, overcast, pepper-grinder, redmond, smoothness, south-street, start,
  sunny, swanky-purse, trontastic, ui-darkness, ui-lightness, vadar
-->
<link id="pagestyle" rel="stylesheet" type="text/css" href="css/themes/humanity/jquery-ui.css">
<!-- 4.7.1 or 5.1.1 -->
<link rel="stylesheet" type="text/css" media="screen" href="jqGrid.5.1.1/css/ui.jqgrid.css">
<link rel="stylesheet" type="text/css" media="all"    href="css/jquery-ui-timepicker-addon.css">

<!link rel="stylesheet" type="text/css" media="screen" href="css/tabcontent.css">

<style type="text/css">
    .textalignright
    {
        text-align: right !important;
    }
    .textalignleft
    {
        text-align:left  !important;
    }
    .textalignright div
    {
        padding-right: 5px;
    }
    .textalignleft div
    {
        padding-left: 5px;
    }
    ol, ul
    {
        list-style: none;
    }
    /* Bump up the font-size in the grid */
    /*
	.ui-jqgrid,
	.ui-jqgrid .ui-jqgrid-view,
	.ui-jqgrid .ui-jqgrid-pager,
	.ui-jqgrid .ui-pg-input {
		font-size: 13px;
	}
	*/
    /* Page loading spinner image */
    .loader
    {
        position:         fixed;
        left:             0px;
        top:              0px;
        width:            100%;
        height:           100%;
        z-index:          9999;
        background:       url('images/page-loader.gif') 50% 50% no-repeat rgb(249,249,249);
    }
</style>

<!-- SCRIPTS - Probably don't need all this, but just in case. -->
<script type="text/javascript" src="js/jquery-2.1.4.js"></script>

<!-- <script type="text/javascript" src="js/DateTimePicker.js"></script> -->
<!-- Bootstrap core JavaScript ================================================== -->
<script type="text/javascript" src="js/bootstrap.js"></script>
<script type="text/javascript" src="js/jquery-ui.js"></script><!-- must come after bootstrap.min.js -->
<script type="text/javascript" src="js/jquery.themeswitcher.js"></script>

<script type="text/javascript" src="js/jqxcore.js"></script><!-- jqWidgets: base code -->
<script type="text/javascript" src="js/jqxwindow.js"></script>
<script type="text/javascript" src="js/jqxbuttons.js"></script>
<script type="text/javascript" src="js/jqxscrollbar.js"></script>
<script type="text/javascript" src="js/jqxpanel.js"></script>
<script type="text/javascript" src="js/jqxtabs.js"></script>
<script type="text/javascript" src="js/jqxcheckbox.js"></script>

<!-- script type="text/javascript" src="js/jqxmenu.js"></script><!-- jqWidgets: jqxMenu - runs nav menu -->
<script type="text/javascript" src="js/jstz.js"></script><!-- Determines user's timezone of the PC their using -->
<script type="text/javascript" src="js/html.js"></script>

<!-- jqGrid.4.8.2 does not work so don't use it -->
<!-- jqGrid.4.7.1 works -->
<script type="text/javascript" src="jqGrid.5.1.1/js/jquery.jqGrid.min.js"></script>
<script type="text/javascript" src="jqGrid.5.1.1/js/i18n/grid.locale-en.js"></script>
<!-- <script type="text/javascript" src="js/jquery.blockUI.js"></script> -->

<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
<script type="text/javascript" src="js/jquery-ui-sliderAccess.js"></script>

<script type="text/javascript" src="js/detect.js"></script><!-- Detect browser so we can size dialog boxes -->

<!-- w2ui popup -->
<!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
<!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

<link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
<script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>

<script src="js/go-debug.js"></script>

<!--[if lt IE 9]>
<script src="js/IE9.js"></script>
<![endif]-->

<script type="text/javascript">
    function resetCursor(txtElement)
    {
        if (txtElement.setSelectionRange)
        {
            txtElement.focus();
            txtElement.setSelectionRange(0, 0);
        }
        else if (txtElement.createTextRange)
        {
            var range = txtElement.createTextRange();
            range.moveStart('character', 0);
            range.select();
        }
    }
</script>
<style>
    .my_textarea
    {
        border:  1px solid #999999;
        width:   40%;
        margin:  5px 0;
        padding: 3px;
        resize:  none;
        font-size: 13px;
    }
</style>
<body>
<div class="loader"></div>
<form name="f1" method="post">
    <p align="center">
        <font size="+2"><b>Work Schedule</b></font><br><br>
    <table border="1" cellpadding="4" cellspacing="4" width="75%">
        <tr>
            <td align="center"><b><u>Tickets</u></b></td>
            <td align="center"><b><u>Ticket Status</u></b></td>
            <td align="center"><b><u>Server Status</u></b></td>
            <td align="center"><b><u>Time Zone</u></b></td>
            <td align="center"><b><u>Spreadsheet Templates</u></b></td>
        </tr>
        <tr>
            <td align="center" valign="top">
                <textarea class="my_textarea" rows="15" id="ticket_list" name="ticket_list" cols="30"
                          onfocus="resetCursor(this);"></textarea>
            </td>
            <td align="center" valign="top"><!-- Ticket Status -->
                <table border="0">
                    <tr>
                        <td>
                            <input type="checkbox" id="ticket_active" name="ticket_active" value="ON" checked>ACTIVE
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" id="ticket_canceled" name="ticket_canceled" value="ON">CANCELED
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" id="ticket_closed" name="ticket_closed" value="ON" checked>CLOSED
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" id="ticket_draft" name="ticket_draft" value="ON">DRAFT
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" id="ticket_failed" name="ticket_failed" value="ON">FAILED
                        </td>
                    </tr>
                </table>
            </td>
            <td align="center" valign="top"><!-- Server Status -->
                <table border="0">
                    <tr>
                        <td>
                            <input type="checkbox" id="server_approved" name="server_approved" value="ON" checked>APPROVED
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" id="server_canceled" name="server_canceled" value="ON">CANCELED
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" id="server_failed" name="server_failed" value="ON">FAILED
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" id="server_rejected" name="server_rejected" value="ON">REJECTED
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" id="server_starting" name="server_starting" value="ON">STARTING
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" id="server_success" name="server_success" value="ON">SUCCESS
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="checkbox" id="server_waiting" name="server_waiting" value="ON">WAITING
                        </td>
                    </tr>
                </table>
            </td>
            <td align="center" valign="top">
                <table border="0">
                    <tr>
                        <td>
                            <input type="radio" id="timezone" name="timezone" value="tz_server" checked>Server Local
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="radio" id="timezone" name="timezone" value="tz_local">User Local
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="radio" id="timezone" name="timezone" value="tz_pacific">Pacific
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="radio" id="timezone" name="timezone" value="tz_mountain">Mountain
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="radio" id="timezone" name="timezone" value="tz_central">Central
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="radio" id="timezone" name="timezone" value="tz_eastern">Eastern
                        </td>
                    </tr>
                </table>
            </td>
            <td align="center" valign="top">
                <table border="0">
                    <tr>
                        <td>
                            <input type="radio" id="template" name="template" value="template1" checked>CCT 6 Classic Work Schedule
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="radio" id="template" name="template" value="template2">Firmware Template
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="5" align="center">
                <input type="button" name="button"
                       value="Okay"
                       onclick="download();">
                <input type="reset" value="Reset">
            </td>
        </tr>
    </table>
    </p>
    <center>
        <table border="0" width="75%">
        <tr>
            <td align="left">
                <font color="blue"><b>
                        <p>
                            Type in one or more tickets in the ticket text area. The tickets can be
                            either CM or CCT7 or both types.
                        </p>
                        <p>
                            The Ticket Status column list tickets that must be in one of these
                            categories. If you type a ticket that has been CLOSED and you only have
                            the ACTIVE checked then you will not see any data for that ticket.
                        </p>
                        <p>
                            Server Status column tells the program what server data to pull from.
                            In most cases you will only want servers that have been APPROVED, but
                            there may be a reason why you might want to see date for other servers
                            that do not fall under the APPROVED status category.
                        </p>
                        <p>
                            You have 6 timezone options to choose from in the Time Zone column. By
                            default the "Server Local" is selected meaning you want all the times
                            for each server to be converted to the server's local time zone. The User
                            Local will convert everything to your local time zone. The other 4 timezones
                            are for Pacific, Mountain, Central and Eastern. That should pretty much cover
                            the server range.
                        </p>
                        <p>
                            The Spreadsheet Template column list all the available report output formats
                            for this report generator. The classic CCT 6 Work Schedule format is the
                            first one and is the default. The next one is for Tory Lehr's Firmware
                            template that he uses. And there will most likely be more templates added
                            over time as I receive more requests to add them.
                        </p>
                        <p>
                            After typing in your tickets and setting your options, click the button that
                            says "Okay". The program will create a CVS file for you to download. Be patient,
                            it may take a little time to gather the information and build the file.
                        </p>
                </b></font>
            </td>
        </tr>
    </table>
    </center>
</form>
<script type="application/javascript">
    $(window).load(function()
    {
        $(".loader").fadeOut("slow");
    });

    function download()
    {
        alert('Be patient. This may take a few minutes before you see the download file.');
        $(".loader").show();

        var ticket_list     = document.getElementById('ticket_list').value;
        var ticket_active   = 'N';
        var ticket_canceled = 'N';
        var ticket_closed   = 'N';
        var ticket_draft    = 'N';
        var ticket_failed   = 'N';
        var server_approved = 'N';
        var server_canceled = 'N';
        var server_failed   = 'N';
        var server_rejected = 'N';
        var server_starting = 'N';
        var server_success  = 'N';
        var server_waiting  = 'N';
        var timezone        = $('input[name="timezone"]:checked').val();  // Radio button
        var template        = $('input[name="template"]:checked').val();  // Radio button

        //alert('ticket_list: ' + ticket_list);
        //alert('timezone: ' + $('input[name="timezone"]:checked').val());
        //alert('template: ' + $('input[name="template"]:checked').val());

        if (document.getElementById("ticket_active").checked)
            ticket_active   = 'Y';

        if (document.getElementById("ticket_canceled").checked)
            ticket_canceled = 'Y';

        if (document.getElementById("ticket_closed").checked)
            ticket_closed   = 'Y';

        if (document.getElementById("ticket_draft").checked)
            ticket_draft    = 'Y';

        if (document.getElementById("ticket_failed").checked)
            ticket_failed   = 'Y';

        if (document.getElementById("server_approved").checked)
            server_approved = 'Y';

        if (document.getElementById("server_canceled").checked)
            server_canceled = 'Y';

        if (document.getElementById("server_failed").checked)
            server_failed   = 'Y';

        if (document.getElementById("server_rejected").checked)
            server_rejected = 'Y';

        if (document.getElementById("server_starting").checked)
            server_starting = 'Y';

        if (document.getElementById("server_success").checked)
            server_success  = 'Y';

        if (document.getElementById("server_waiting").checked)
            server_waiting  = 'Y';

        if (ticket_list.length == 0)
        {
            alert('Please type in one or more tickets and setup your options before clicking this button.');
            return;
        }

        //
        // Prepare JSON data
        //
        var data = {
            'ticket_list':     ticket_list,
            'ticket_active':   ticket_active,
            'ticket_canceled': ticket_canceled,
            'ticket_closed':   ticket_closed,
            'ticket_draft':    ticket_draft,
            'ticket_failed':   ticket_failed,
            'server_approved': server_approved,
            'server_canceled': server_canceled,
            'server_failed':   server_failed,
            'server_rejected': server_rejected,
            'server_starting': server_starting,
            'server_success':  server_success,
            'server_waiting':  server_waiting,
            'timezone':        timezone,
            'template':        template
        };

        var url = 'ajax_work_schedule.php';

        $.ajax({
            type: 'GET',
            url:   url,
            async: false,
            data:  data,
            beforeSend: function (xhr)
            {
                if (xhr && xhr.overrideMimeType)
                {
                    xhr.overrideMimeType('application/json;charset=utf-8');
                }
            },
            dataType: 'json',
            success: function (data)
            {
                $(".loader").fadeOut("slow");

                if (data['ajax_status'] == 'FAILED')
                {
                    alert(data['ajax_message']);
                    return;
                }

                JSONToCSVConvertor(data['rows'], template, true);
            },
            error: function(jqXHR, exception, errorThrown)
            {
                if (jqXHR.status === 0) {
                    alert('Not connect.\n Verfiy Network.');
                } else if (jqXHR.status == 404) {
                    alert('Requested page not found. [404]');
                } else if (jqXHR.status == 500) {
                    alert('Internal Server Error [500]');
                } else if (exception === 'parsererror') {
                    alert('Requested JSON parse failed.' + ' Error code: ' + errorThrown + ' ResponseText: ' + jqXHR.responseText);
                } else if (exception === 'timeout') {
                    alert('Time out error.');
                } else if (exception === 'abort') {
                    alert('Ajax request aborted.');
                } else {
                    alert('Uncaught Error.\n' + jqXHR.responseText);
                }
            }
        });
    }

    function JSONToCSVConvertor(JSONData, ReportTitle, ShowLabel)
    {
        //alert('In JSONToCSVConvertor');

        // If JSONData is not an object then JSON.parse will parse the JSON string in an Object
        var arrData = typeof JSONData != 'object' ? JSON.parse(JSONData) : JSONData;

        var CSV = '';

        // This condition will generate the Label/Header
        if (ShowLabel)
        {
            var row = "";

            // This loop will extract the label from 1st index of on array
            for (var index in arrData[0])
            {
                // Now convert each value to string and comma-seprated
                row += index + ',';
            }

            row = row.slice(0, -1);

            // Append Label row with line break
            CSV += row + '\r\n';
        }

        // 1st loop is to extract each row
        for (var i = 0; i < arrData.length; i++)
        {
            var row = "";

            // 2nd loop will extract each column and convert it in string comma-seprated
            for (var index in arrData[i])
            {
                row += '"' + arrData[i][index] + '",';
            }

            row.slice(0, row.length - 1);

            //add a line break after each row
            CSV += row + '\r\n';
        }

        if (CSV == '')
        {
            alert("Invalid data");
            return;
        }

        // Generate a file name
        var fileName = "cct7_";
        // This will remove the blank-spaces from the title and replace it with an underscore
        fileName += ReportTitle.replace(/ /g,"_");

        // Initialize file format you want csv or xls
        var uri = 'data:text/csv;charset=utf-8,' + escape(CSV);

        // Now the little tricky part.
        // you can use either>> window.open(uri);
        // but this will not work in some browsers
        // or you will not get the correct file extension

        // This trick will generate a temp <a /> tag
        var link = document.createElement("a");
        link.href = uri;

        // Set the visibility hidden so it will not effect on your web-layout
        link.style = "visibility:hidden";
        link.download = fileName + ".csv";

        // This part will append the anchor tag and remove it after automatic click
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>
</body>
</html>