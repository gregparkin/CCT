<?php
/**
 * dialog_view_cm_ticket.php
 *
 * @package   PhpStorm
 * @file      dialog_view_cm_ticket.php
 * @author    gparkin
 * @date      1/3/17
 * @version   7.0
 *
 * @brief     About this module.
 */

//$path = '/usr/lib/pear';
//set_include_path(get_include_path() . PATH_SEPARATOR . $path);

set_include_path("/opt/ibmtools/www/cct7/classes");

// <button class="btn" onclick="w2ui.tabs.click('tab3')">Set Tab 3 Active</button>

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('classes/autoloader.php');
}

session_start(); // Call to make $_SESSION['...'] data available

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

$parm = array();
$parm_count = 0;

//
// NOTE: It is very important that you do not turn on debugging without writing to a file for server side AJAX code. Any HTML
//       comments coming from functions while writing XML will show up in the XML output and you will get a XML parsing error
//       in the client side program.
//
$ora = new oracle();
$lib = new library();  // classes/library.php
$lib->debug_start('dialog_view_cm_ticket.html');
date_default_timezone_set('America/Denver');

//
// Parse QUERY_STRING
//
if (isset($_SERVER['QUERY_STRING']))
{
	//
	// $myQryStr = "first=1&second=Z&third[]=5000&third[]=6000";
	//
	// parse_str($_SERVER['QUERY_STRING'], $parm);
	// echo $parm['first']; //will output 1
	// echo $parm['second']; //will output Z
	// echo $parm['third'][0]; //will output 5000
	// echo $parm['third'][1]; //will output 6000
	//
	parse_str($_SERVER['QUERY_STRING'], $parm);
	$parm_count = count($parm);

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "QUERY_STRING: %s", $_SERVER['QUERY_STRING']);

	foreach ($parm as $key => $value)
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "parm[%s] = %s", $key, $value);
}

if (!isset($parm['cm_ticket_no']))
{
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing cm_ticket_no");
	printf("Missing cm_ticket_no");
	exit();
}

if (!isset($parm['ticket_no']))
{
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing ticket_no");
	printf("Missing ticket_no");
	exit();
}

$cm_ticket_no = $parm['cm_ticket_no'];
$ticket_no    = $parm['ticket_no'];

$start_date = '';
$end_date   = '';

?>

<!DOCTYPE html>
<html>
<head>
    <title>CCT Ticket</title>

    <style>
        .my_textarea
        {
            border:  1px solid #999999;
            width:   98%;
            margin:  5px 0;
            padding: 3px;
            resize:  none;
            font-size: 12px;
        }
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
    <script type="text/javascript" src="js/jquery-2.1.4.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>


</head>
<body>
<div class="loader">Retrieving Remedy ticket...</div>
<form name="f1" method="get" action="dialog_view_cm_ticket.php">
    <script type="text/javascript">

        function callAjaxTicket(action)
        {
            $(".loader").show();

            var cm_ticket_no = <?php printf("'%s'", $cm_ticket_no); ?>;
            var ticket_no    = <?php printf("'%s'", $ticket_no); ?>;

            var data;

            //
            // Prepare the data that will be sent to ajax_ticket.php
            //
            data = {
                "action":               action,
                "ticket_no":            ticket_no,
                "cm_ticket_no":         cm_ticket_no
            };

            //
            // Create a JSON string from the selected row of data.
            //
            //var gridData = jQuery(TicketGrid).jqGrid('getRowData', Ticket_id);
            //var postData = JSON.stringify(gridData);
            //var postData = JSON.stringify(data);
            //alert(postData);

            //alert(JSON.stringify(data));

            var url = 'dialog_view_cm_ticket_ajax.php?action=get&cm_ticket_no=' + cm_ticket_no + '&ticket_no=' + ticket_no;

            $.ajax(
                {
                    type:     "POST",
                    url:      url,
                    dataType: "json",
                    success:  function(data)
                    {
                        $(".loader").fadeOut("slow");

                        /*
                         for (var key in data)
                         {
                         var value = data[key];
                         // Use `key` and `value`

                         alert(key + ' = ' + data[key]);
                         }
                         */

                        if (data['ajax_status'] == 'FAILED')
                        {
                            alert(data['ajax_message']);
                            return;
                        }

                        document.getElementById('change_id').innerHTML                   = data['change_id'];
                        document.getElementById('start_date').innerHTML                  = data['start_date'];
                        document.getElementById('status').innerHTML                      = data['status'];
                        document.getElementById('open_closed').innerHTML                 = data['open_closed'];
                        document.getElementById('end_date').innerHTML                    = data['end_date'];
                        document.getElementById('duration_computed').innerHTML           = data['duration_computed'];
                        document.getElementById('closed_by').innerHTML                   = data['closed_by'];
                        document.getElementById('close_date').innerHTML                  = data['close_date'];
                        document.getElementById('close_code').innerHTML                  = data['close_code'];
                        document.getElementById('owner_first_name').innerHTML            = data['owner_first_name'];
                        document.getElementById('owner_last_name').innerHTML             = data['owner_last_name'];
                        document.getElementById('owner_cuid').innerHTML                  = data['owner_cuid'];
                        document.getElementById('owner_group').innerHTML                 = data['owner_group'];
                        document.getElementById('director').innerHTML                    = data['director'];
                        document.getElementById('manager').innerHTML                     = data['manager'];
                        document.getElementById('phone').innerHTML                       = data['phone'];
                        document.getElementById('phone2').innerHTML                      = data['phone2'];
                        document.getElementById('email').innerHTML                       = data['email'];
                        document.getElementById('company_name').innerHTML                = data['company_name'];
                        document.getElementById('phone2').innerHTML                      = data['phone2'];
                        document.getElementById('pin').innerHTML                         = data['pin'];
                        document.getElementById('category').innerHTML                    = data['category'];
                        document.getElementById('category_type').innerHTML               = data['category_type'];
                        document.getElementById('tested').innerHTML                      = data['tested'];
                        document.getElementById('scheduling_flexibility').innerHTML      = data['scheduling_flexibility'];
                        document.getElementById('tested_itv').innerHTML                  = data['tested_itv'];
                        document.getElementById('tested_endtoend').innerHTML             = data['tested_endtoend'];
                        document.getElementById('tested_development').innerHTML          = data['tested_development'];
                        document.getElementById('tested_user').innerHTML                 = data['tested_user'];
                        document.getElementById('tested_orl').innerHTML                  = data['tested_orl'];
                        document.getElementById('emergency_change').innerHTML            = data['emergency_change'];
                        document.getElementById('featured_project').innerHTML            = data['featured_project'];
                        document.getElementById('ipl_boot').innerHTML                    = data['ipl_boot'];
                        document.getElementById('plan_a_b').innerHTML                    = data['plan_a_b'];
                        document.getElementById('risk').innerHTML                        = data['risk'];
                        document.getElementById('description').innerHTML                 = data['description'];
                        document.getElementById('implementation_instructions').innerHTML = data['implementation_instructions'];
                        document.getElementById('backoff_plan').innerHTML                = data['backoff_plan'];
                        document.getElementById('business_reason').innerHTML             = data['business_reason'];
                        document.getElementById('impact').innerHTML                      = data['impact'];
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
                }
            );
        }

        function buttonClick(ele)
        {
            //alert(ele.value);

            switch ( ele.value )
            {
                case 'Add Entry':
                    callAjaxTicket("log", "<?php echo $ticket_no; ?>");
                    break;
                case 'Activate':
                    callAjaxTicket("activate", "<?php echo $ticket_no; ?>");
                    break;
                case 'Delete':
                    callAjaxTicket("delete", "<?php echo $ticket_no; ?>");
                    break;
                case 'Cancel':
                    callAjaxTicket("cancel", "<?php echo $ticket_no; ?>");
                    break;
                case 'Sendmail':
                    callAjaxTicket("sendmail", "<?php echo $ticket_no; ?>");
                    break;
                case 'Update':
                    callAjaxTicket("update", "<?php echo $ticket_no; ?>");
                    break;
                default:
                    alert('No logic coded for this button: ' + ele.value);
                    break;
            }
        }
    </script>

    <div id="tab-example">
        <div id="tabs" style="width: 100%; height: 29px;"></div>

        <!-- General -->
        <div id="tab1" class="tab" style="width: 785px; height: 400px;">
            <center><br>
                <table bgcolor="#ECE9D8" width="95%" height="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="33%" align="left">
                            <b>Remedy Ticket</b><br>
                            <span id="change_id"></span>
                        </td>
                        <td width="33%" align="left" size="20">
                            <b>Start Date &amp; Time</b><br>
                            <span id="start_date"></span>
                        </td>
                        <td width="34%" align="left">
                            <b>Approval Status</b><br>
                            <span id="status"></span>
                        </td>
                    </tr>
                    <tr>
                        <td width="33%" align="left">
                            <b>Open/Closed</b><br>
                            <span id="open_closed"></span>
                        </td>
                        <td width="33%" align="left" size="20">
                            <b>End Date &amp; Time</b><br>
                            <span id="end_date"></span>
                        </td>
                        <td width="34%" align="left">
                            <b>Duration (Days:Hrs:Min)</b><br>
                            <span id="duration_computed"></span>
                        </td>
                    </tr>
                    <tr>
                        <td width="33%" align="left">
                            <b>Closed By</b><br>
                            <span id="closed_by"></span>
                        </td>
                        <td width="33%" align="left" size="20">
                            <b>Close Date & Time</b><br>
                            <span id="close_date"></span>
                        </td>
                        <td width="34%" align="left">
                            <b>Close Code</b><br>
                            <span id="close_code"></span>
                        </td>
                    </tr>
                    <tr>
                        <td width="100%" colspan="3"><hr></td>
                    </tr>
                    <tr>
                        <td width="33%" align="left">
                            <b>Owner First Name</b><br>
                            <span id="owner_first_name"></span>
                        </td>
                        <td width="33%" align="left" size="20">
                            <b>Owner Last Name</b><br>
                            <span id="owner_last_name"></span>
                        </td>
                        <td width="34%" align="left">
                            <b>Owner CUID</b><br>
                            <span id="owner_cuid"></span>
                        </td>
                    </tr>
                    <tr>
                        <td width="33%" align="left">
                            <b>Owner Group</b><br>
                            <span id="owner_group"></span>
                        </td>
                        <td width="33%" align="left">
                            <b>Director</b><br>
                            <span id="director"></span>
                        </td>
                        <td width="34%" align="left">
                            <b>Manager</b><br>
                            <span id="manager"></span>
                        </td>
                    </tr>
                    <tr>
                        <td width="33%" align="left">
                            <b>Phone</b><br>
                            <span id="phone"></span>
                        </td>
                        <td width="33%" align="left">
                            <b>E-Mail</b><br>
                            <span id="email"></span>
                        </td>
                        <td width="34%" align="left">
                            <b>Company Name</b><br>
                            <span id="company_name"></span>
                        </td>
                    </tr>
                    <tr>
                        <td width="33%" align="left">
                            <b>Pager</b><br>
                            <span id="phone2"></span>
                        </td>
                        <td width="33%" align="left">
                            <b>PIN</b><br>
                            <span id="pin"></span>
                        </td>
                        <td width="34%" align="left">&nbsp;</td>
                    </tr>
                    <tr>
                        <td width="100%" colspan="3"><hr></td>
                    </tr>
                    <tr>
                        <td width="33%" align="left">
                            <b>Category</b><br>
                            <span id="category"></span>
                        </td>
                        <td width="33%" align="left">
                            <b>Category Type</b><br>
                            <span id="category_type"></span>
                        </td>
                        <td width="34%" align="left">
                            <b>Tested</b><br>
                            <span id="tested"></span>
                        </td>
                    </tr>
                    <tr>
                        <td width="33%" align="left">
                            <b>Scheduling Flexibility</b><br>
                            <span id="scheduling_flexibility"></span>
                        </td>
                        <td width="33%" align="left">
                            <b>Risk</b><br><span id="risk"></span>
                        </td>
                        <td width="34%" rowspan="2" valign="top" align="left">
                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse" bordercolor="#111111" width="100%">
                                <tr>
                                    <td width="54%" align="left"><b>System Tested</b></td>
                                    <td width="46%" align="left">
                                        <span id="tested_itv"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="54%" align="left"><b>End to End</b></td>
                                    <td width="46%" align="left">
                                        <span id="tested_endtoend"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="54%" align="left"><b>Dev Tested</b></td>
                                    <td width="46%" align="left">
                                        <span id="tested_development"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="54%" align="left"><b>User Tested</b></td>
                                    <td width="46%" align="left">
                                        <span id="tested_user"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="54%" align="left"><b>ORL Tested</b></td>
                                    <td width="46%" align="left">
                                        <span id="tested_orl"></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td width="33%" valign="top" align="left">
                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse" bordercolor="#111111" width="100%">
                                <tr>
                                    <td width="60%" align="left"><b>Emergency Chg?</b></td>
                                    <td width="40%" align="left">
                                        <span id="emergency_change"></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="60%" align="left"><b>Featured Project</b></td>
                                    <td width="40%" align="left">
                                        <span id="featured_project"></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td width="33%" align="left">
                            <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse" bordercolor="#111111" width="100%">
                                <tr>
                                    <td width="52%" align="left"><b>IPL/Boot</b></td>
                                    <td width="48%" align="left"><b>Plan A or B</b></td>
                                </tr>
                                <tr>
                                    <td width="52%" align="left">
                                        <span id="ipl_boot"></span>
                                    </td>
                                    <td width="48%" align="left">
                                        <span id="plan_a_b"></span>
                                    </td>
                                </tr>
                            </table>
                            <br>
                        </td>
                    </tr>
                </table>
            </center>
        </div>

        <!-- Description -->
        <div id="tab2" class="tab" style="width: 785px; height: 400px;">
            <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                <tr>
                    <td width="100%" valign="top">
                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                            <legend><font color="#8F4300"><b>Description</b></font></legend>
                            <textarea class="my_textarea" rows="24" id="description" cols="99" style="width: 98%"></textarea>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Implementation Instructions -->
        <div id="tab3" class="tab" style="width: 785px; height: 400px;">
            <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                <tr>
                    <td width="100%" valign="top">
                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                            <legend><font color="#8F4300"><b>Implementation Instructions</b></font></legend>
                            <textarea class="my_textarea" rows="24" id="implementation_instructions" cols="99" style="width: 98%"></textarea>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Backoff Plans -->
        <div id="tab4" class="tab" style="width: 785px; height: 400px;">
            <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                <tr>
                    <td width="100%" valign="top">
                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                            <legend><font color="#8F4300"><b>Backoff Plans</b></font></legend>
                            <textarea class="my_textarea" rows="24" id="backoff_plan" cols="99" style="width: 98%"></textarea>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Business Reasons -->
        <div id="tab5" class="tab" style="width: 785px; height: 400px;">
            <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                <tr>
                    <td width="100%" valign="top">
                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                            <legend><font color="#8F4300"><b>Business Reasons</b></font></legend>
                            <textarea class="my_textarea" rows="24" id="business_reason" cols="99" style="width: 98%"></textarea>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Impact -->
        <div id="tab6" class="tab" style="width: 785px; height: 400px;">
            <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
                <tr>
                    <td width="100%" valign="top">
                        <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                            <legend><font color="#8F4300"><b>Impact</b></font></legend>
                            <textarea class="my_textarea" rows="24" id="impact" cols="99" style="width: 98%"></textarea>
                        </fieldset>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <br><br>
    <p align="center">
        <input title="Close the Remedy ticket view window."
               class="add_entry" type="button" name="button" value="Close" onclick="parent.postMessage('close w2popup', '*');">
    </p>

    <script type="text/javascript">
        var config = {
            tabs: {
                name: 'tabs',
                active: 'tab1',
                style: 'background: #ECE9D8',
                tabs: [
                    { id: 'tab1', caption: 'General' },
                    { id: 'tab2', caption: 'Description' },
                    { id: 'tab3', caption: 'Implementation Instructions' },
                    { id: 'tab4', caption: 'Backoff Plans' },
                    { id: 'tab5', caption: 'Business Reasons' },
                    { id: 'tab6', caption: 'Impact' }
                ],
                onClick: function (event)
                {
                    $('#tab-example .tab').hide();
                    $('#tab-example #' + event.target).show();
                }
            }
        };

        $(function ()
        {
            $('#tabs').w2tabs(config.tabs);
            $('#tab2').show();
            w2ui.tabs.click('tab1');

            callAjaxTicket('get');
        });
    </script>
</form>
</body>
</html>
