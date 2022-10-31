<?php
/**
 * toolbar_remedy_attachment.php
 *
 * @package   PhpStorm
 * @file      toolbar_remedy_attachment.php
 * @author    gparkin
 * @date      4/12/17
 * @version   7.0
 *
 * @brief     This module is run when the user clicks on the "Remedy Attachment" icon on the toolbar menu.
 *            It will prompt them for the CCT ticket number. Then when they click the submit button the
 *            program will gather all the servers for that ticket that have an approved Ready status and
 *            will download that data into CVS spreadsheet file that can be opened on in Excel or LibrCalc.
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
$lib->debug_start('toolbar_remedy_attachment.html');
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
<body>
<form name="f1" method="post">

    <p align="center">
    <table border="0" cellpadding="4" cellspacing="4" width="50%">
        <tr>
            <td colspan="2" align="center">
                <h2>Remedy Attachment</h2>
                <b>Download Server Ready Work for a given CCT7 ticket.</b>
            </td>
        </tr>
        <tr>
            <td align="center">
                <input type="text" id="ticket_no" name="ticket_no" style="width: 20em">
            </td>
            <td>
                Type in a CCT7 ticket.
            </td>
        </tr>
        <tr>
            <td align="center">
                <?php
                    $ora = new oracle();

                    $query  = "select ";
                    $query .= "  t.ticket_no as ticket_no, ";
                    $query .= "  t.cm_ticket_no as cm_ticket_no ";
                    $query .= "from ";
                    $query .= "  cct7_tickets t, ";
					$query .= "(" .
						"select distinct " .
						"  n1.user_cuid as user_cuid " .
						"from " .
						"  cct7_netpin_to_cuid n1, " .
						"  (select net_pin_no from cct7_netpin_to_cuid where user_cuid = '" . $_SESSION['user_cuid'] . "') n2 " .
						"where " .
						"  n1.net_pin_no = n2.net_pin_no " .
						"order by " .
						"  n1.user_cuid " .
						") m ";
					$query .= "where (t.owner_cuid = m.user_cuid or t.manager_cuid = m.user_cuid) and ";
					$query .= "t.status = 'ACTIVE' ";
					$query .= "order by t.ticket_no";

					if ($ora->sql2($query) == false)
                    {
						$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s - %s", $query, $ora->dbErrMsg);
						printf("%s\n", $ora->dbErrMsg);
                    }

                ?>
                    <select name="ticket_list" id="ticket_list" size="20" style="width: 20em;">
                <?php

                    while ($ora->fetch())
                    {
                        if (strlen($ora->cm_ticket_no) > 0)
                        {
							printf("<option value=%s>%s - %s</option>\n",
								   $ora->ticket_no, $ora->ticket_no, $ora->cm_ticket_no);
                        }
                        else
                        {
							printf("<option value=%s>%s</option>\n",
								   $ora->ticket_no, $ora->ticket_no);
                        }
                    }
                ?>
                    </select>
            </td>
            <td valign="top">
                Or select one of your group owned CCT7 tickets from this list.
                <p style="color: blue">
        <!-- id="mv_w" name="ticket_os_maintwin" value="W"  -->
                    <input type="radio" id="convert_mountain" name="convert" value="mountain" checked> Convert to Mountain time
                    <br>
                    <input type="radio" id="convert_server" name="convert" value="server"> Convert to server localtime
                    <br>
                    <input type="radio" id="convert_user" name="convert" value="user"> Convert to user localtime
                </p>
                <p style="color: red">
                    <b>If there is no approved Ready Work available or you mis-typed the ticket
                       number, nothing will be downloaded.</b>
                </p>

            </td>
        </tr>
        <tr>
            <td colspan="2" align="center">
                <input type="button" name="button"
                       value="Click to Begin Download"
                       onclick="download();">
                <input type="reset" value="Reset">
            </td>
        </tr>
    </table>
    </p>

</form>
<script type="application/javascript">
    function download()
    {
        var ticket_no = document.getElementById('ticket_no').value;
        var ticket_list = document.getElementById('ticket_list').value;
        var convert = '';
        var this_ticket = '';

        if (document.getElementById("convert_server").checked)
            convert = 'server';
        else if (document.getElementById("convert_user").checked)
            convert = 'user';
        else
            convert = 'mountain';

        if (ticket_no.length > 0)
        {
            this_ticket = ticket_no;
        }
        else if (ticket_list.length > 0)
        {
            this_ticket = ticket_list;
        }
        else
        {
            alert('Please type or select a CCT7 ticket.');
            return;
        }

        //
        // Prepare JSON data containing the ticket_no and system_id we want update.
        //
        var data = {
            'ticket_no':   this_ticket,
            'convert':     convert
        };

        var url = 'ajax_toolbar_remedy_attachment.php';

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
                if (data['ajax_status'] == 'FAILED')
                {
                    alert(data['ajax_message']);
                    return;
                }

                JSONToCSVConvertor(data['rows'],
                    "Remedy Attachment",
                    data['cm_ticket_no'],
                    data['ticket_no'],
                    data['work_activity'],
                    data['owner_name'],
                    true);
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

    function JSONToCSVConvertor(JSONData, ReportTitle,
                                cm_ticket_no, ticket_no, work_activity, owner_name, ShowLabel)
    {
        // If JSONData is not an object then JSON.parse will parse the JSON string in an Object
        var arrData = typeof JSONData != 'object' ? JSON.parse(JSONData) : JSONData;

        var CSV = '';
        // Set Report title in first row or line

        CSV += ReportTitle + '\r\n\n';
        CSV += 'Remedy CM:,' + cm_ticket_no + '\r\n';
        CSV += 'CCT Ticket:,' + ticket_no + '\r\n';
        CSV += 'Work Activity:,' + work_activity + '\r\n';
        CSV += 'Owner Name:,' + owner_name + '\r\n';


        var convert = '';

        if (document.getElementById("convert_server").checked)
        {
            CSV += '\r\nDates and times posted here are in the server\'s time zone.\r\n\n';
        }
        else if (document.getElementById("convert_user").checked)
        {
            CSV += '\r\nDates and times posted here are in the ' +
                   '<?php echo $_SESSION['local_timezone_name']; ?> time zone.' +
                   '\r\n\n';
        }
        else
        {
            CSV += '\r\nDates and times posted here are in America/Denver time zone.\r\n\n';
        }

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
        var fileName = cm_ticket_no + "_";
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