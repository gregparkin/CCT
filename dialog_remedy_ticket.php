<?php
/**
 * dialog_remedy_ticket.php
 *
 * @package   PhpStorm
 * @file      dialog_remedy_ticket.php
 * @author    gparkin
 * @date      6/30/16
 * @version   7.0
 *
 * @brief     Dialog box used for working with tickets.
 *            This module is included in work_request_grid.php
 *
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
$lib = new library();  // classes/library.php
$lib->debug_start('dialog_remedy_ticket.html');
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

$cm_ticket_no    = $parm['cm_ticket_no'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>W2UI Demo: combo-5</title>

    <script type="text/javascript" src="js/jquery-2.1.4.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>

    <script type="text/javascript">

        function callAjaxTicket(ticket_no)
        {
            var data;

            //
            // Prepare the data that will be sent to ajax_ticket.php
            //
            data = {
                "ticket_no":       ticket_no
            };

            //
            // Create a JSON string from the selected row of data.
            //
            //var gridData = jQuery(TicketGrid).jqGrid('getRowData', Ticket_id);
            //var postData = JSON.stringify(gridData);
            //var postData = JSON.stringify(data);
            //alert(postData);

            //alert(JSON.stringify(data));

            var url = 'ajax_remedy_ticket.php';

            $.ajax(
                {
                    type:     "POST",
                    url:      url,
                    dataType: "json",
                    data:     JSON.stringify(data),
                    success:  function(data)
                    {
                        if (data['status'] == 'FAILED')
                        {
                            alert(data['message']);
                            return;
                        }

                        //
                        // <input type=text ...>
                        //
                        document.getElementById('cm_ticket_no').value                   = data['cm_ticket_no'];
                        document.getElementById('cm_start_date').value                  = data['cm_start_date'];
                        document.getElementById('cm_status').value                      = data['cm_status'];
                        document.getElementById('cm_open_closed').value                 = data['cm_open_closed'];
                        document.getElementById('cm_end_date').value                    = data['cm_end_date'];
                        document.getElementById('cm_duration_computed').value           = data['cm_duration_computed'];
                        document.getElementById('cm_closed_by').value                   = data['cm_closed_by'];

                        if (data['cm_close_date'] == '0')
                            document.getElementById('cm_close_date').value              = '';
                        else
                            document.getElementById('cm_close_date').value              = data['cm_close_date'];

                        document.getElementById('cm_owner_first_name').value            = data['cm_owner_first_name'];
                        document.getElementById('cm_owner_last_name').value             = data['cm_owner_last_name'];
                        document.getElementById('cm_owner_cuid').value                  = data['cm_owner_cuid'];
                        document.getElementById('cm_owner_group').value                 = data['cm_owner_group'];
                        document.getElementById('cm_implementor_login').value           = data['cm_implementor_login'];
                        document.getElementById('cm_assign_group').value                = data['cm_assign_group'];
                        document.getElementById('cm_director').value                    = data['cm_director'];
                        document.getElementById('cm_manager').value                     = data['cm_manager'];
                        document.getElementById('cm_phone').value                       = data['cm_phone'];
                        document.getElementById('cm_email').value                       = data['cm_email'];
                        document.getElementById('cm_company_name').value                = data['cm_company_name'];
                        document.getElementById('cm_category').value                    = data['cm_category'];
                        document.getElementById('cm_category_type').value               = data['cm_category_type'];
                        document.getElementById('cm_tested').value                      = data['cm_tested'];
                        document.getElementById('cm_scheduling_flexibility').value      = data['cm_scheduling_flexibility'];
                        document.getElementById('cm_risk').value                        = data['cm_risk'];

                        //
                        // <input type=checkbox ...>
                        //
                        if (data['cm_tested_itv'] == 'checked')
                            document.getElementById('cm_tested_itv').checked = true;
                        else
                            document.getElementById('cm_tested_itv').checked = false;

                        if (data['cm_tested_endtoend'] == 'checked')
                            document.getElementById('cm_tested_endtoend').checked = true;
                        else
                            document.getElementById('cm_tested_endtoend').checked = false;

                        if (data['cm_tested_development'] == 'checked')
                            document.getElementById('cm_tested_development').checked = true;
                        else
                            document.getElementById('cm_tested_development').checked = false;

                        if (data['cm_tested_user'] == 'checked')
                            document.getElementById('cm_tested_user').checked = true;
                        else
                            document.getElementById('cm_tested_user').checked = false;

                        if (data['cm_tested_orl'] == 'checked')
                            document.getElementById('cm_tested_orl').checked = true;
                        else
                            document.getElementById('cm_tested_orl').checked = false;

                        if (data['cm_emergency_change'] == 'checked')
                            document.getElementById('cm_emergency_change').checked = true;
                        else
                            document.getElementById('cm_emergency_change').checked = false;

                        if (data['cm_featured_project'] == 'checked')
                            document.getElementById('cm_featured_project').checked = true;
                        else
                            document.getElementById('cm_featured_project').checked = false;

                        document.getElementById('cm_ipl_boot').value                    = data['cm_ipl_boot'];
                        document.getElementById('cm_plan_a_b').value                    = data['cm_plan_a_b'];

                        //
                        // <textarea> ... </textarea>
                        //
                        document.getElementById('cm_description').innerText                 = data['cm_description'];
                        document.getElementById('cm_implementation_instructions').innerText = data['cm_implementation_instructions'];
                        document.getElementById('cm_backoff_plan').innerText                = data['cm_backoff_plan'];
                        document.getElementById('cm_business_reason').innerText             = data['cm_business_reason'];
                        document.getElementById('cm_impact').innerText                      = data['cm_impact'];


                        //print_r(data);
                        //alert(data['status']);

                        if (data['message'].length > 0)
                            alert(data['message']);
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

    </script>
</head>
<body>

<style>
    .tab {
        width: 98%;
        height: 450px;
        border: 1px solid silver;
        border-top: 0px;
        display: none;
        padding: 10px;
        overflow: auto;
    }

    input[type=text] {
        width: 100%;
        padding: 1px 1px;
        font-size: 12   px;
        margin: 0px 0;
        box-sizing: border-box;
        border: 1px solid blue;
        border-radius: 4px;
    }

    b {
        font-size: 11px;
    }
</style>

<div id="tab-example">
    <div id="tabs" style="width: 100%; height: 29px;"></div>

    <!-- GENERAL -->

    <!-- document.getElementById('sb_action').innerHTML = info_action[what]; -->
    <!-- <span id="sb_action" style="color: blue; font-size: 14px">Action edit dialog screen.</span> -->

    <div id="tab1" class="tab" style="width: 800px; height: 520px;">
        <table bgcolor="#ECE9D8" width="100%" cellspacing="0" cellpadding="0" style="height: 100%; color: black">
            <tr>
                <td width="33%" align="left">
                    <b>Remedy Ticket</b><br>
                    <input type=text id="cm_ticket_no" size="27" style="width: 15em" value="">
                </td>
                <td width="33%" align="left" size="20">
                    <b>Start Date &amp; Time</b><br>
                    <input type=text id="cm_start_date" size="27" style="width: 15em" value="">
                </td>
                <td width="34%" align="left">
                    <b>Approval Status</b><br>
                    <input type=text id="cm_status" size="27" style="width: 15em" value="">
                </td>
            </tr>
            <tr>
                <td width="33%" align="left">
                    <b>Open/Closed</b><br>
                    <input type=text id="cm_open_closed" size="27" style="width: 15em" value="">
                </td>
                <td width="33%" align="left" size="20">
                    <b>End Date &amp; Time</b><br>
                    <input type=text id="cm_end_date" size="27" style="width: 15em" value="">
                </td>
                <td width="34%" align="left">
                    <b>Duration (Days:Hrs:Min)</b><br>
                    <input type=text id="cm_duration_computed" size="27" style="width: 15em" value="">
                </td>
            </tr>
            <tr>
                <td width="33%" align="left">
                    <b>Closed By</b><br>
                    <input type=text id="cm_closed_by" size="27" style="width: 15em" value="">
                </td>
                <td width="33%" align="left" size="20">
                    <b>Close Date & Time</b><br>
                    <input type=text id="cm_close_date" size="27" style="width: 15em" value="">
                </td>
                <td width="34%" align="left">
                    <b>Close Code</b><br>
                    <input type=text id="close_code" size="27" style="width: 15em" value="">
                </td>
            </tr>
            <tr>
                <td width="100%" colspan="3"><hr></td>
            </tr>
            <tr>
                <td width="33%" align="left">
                    <b>Owner First Name</b><br>
                    <input type=text id="cm_owner_first_name" size="27" style="width: 15em" value="">
                </td>
                <td width="33%" align="left" size="20">
                    <b>Owner Last Name</b><br>
                    <input type=text id="cm_owner_last_name" size="27" style="width: 15em" value="">
                </td>
                <td width="34%" align="left">
                    <b>Owner CUID</b></b><br>
                    <input type=text id="cm_owner_cuid" size="27" style="width: 15em" value="">
                </td>
            </tr>
            <tr>
                <td width="33%" align="left">
                    <b>Owner Group</b><br>
                    <input type=text id="cm_owner_group" size="27" style="width: 15em" value="">
                </td>
                <td width="33%" align="left">
                    <b>Director</b><br>
                    <input type=text id="cm_director" size="27" style="width: 15em" value="">
                </td>
                <td width="34%" align="left">
                    <b>Manager</b><br>
                    <input type=text id="cm_manager" size="27" style="width: 15em" value="">
                </td>
            </tr>
            <tr>
                <td width="33%" align="left">
                    <b>Phone</b><br>
                    <input type=text id="cm_phone" size="27" style="width: 15em" value="">
                </td>
                <td width="33%" align="left">
                    <b>E-Mail</b><br>
                    <input type=text id="cm_email" size="27" style="width: 15em" value="">
                </td>
                <td width="34%" align="left">
                    <b>Company Name</b><br>
                    <input type=text id="cm_company_name" size="27" style="width: 15em" value="">
                </td>
            </tr>
            <tr>
                <td width="33%" align="left">
                    <b>Implementor</b><br>
                    <input type=text id="cm_implementor_login" size="27" style="width: 15em" value="">
                </td>
                <td width="33%" align="left">
                    <b>Assign Group</b><br>
                    <input type=text id="cm_assign_group" size="27" style="width: 15em" value="">
                </td>
                <td width="34%" align="left">&nbsp;</td>
            </tr>
            <tr>
                <td width="100%" colspan="3"><hr></td>
            </tr>
            <tr>
                <td width="33%" align="left">
                    <b>Category</b><br>
                    <input type=text id="cm_category" size="27" style="width: 15em" value="" >
                </td>
                <td width="33%" align="left">
                    <b>Category Type</b><br>
                    <input type=text id="cm_category_type" size="27" style="width: 15em" value="">
                </td>
                <td width="34%" align="left">
                    <b>Tested</b><br>
                    <input type=text id="cm_tested" size="27" style="width: 15em" value="" >
                </td>
            </tr>
            <tr>
                <td width="33%" align="left">
                    <b>Scheduling Flexibility</b><br>
                    <input type=text id="cm_scheduling_flexibility" size="27" style="width: 15em" value="" >
                </td>
                <td width="33%" align="left">
                    <b>Risk</b><br>
                    <input type=text id="cm_risk" size="27" style="width: 15em" value="" >
                </td>
                <td width="34%" rowspan="2" valign="top" align="left">
                    <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse" bordercolor="#111111" width="100%">
                        <tr>
                            <td width="54%" align="left"><b>System Tested</b></td>
                            <td width="46%" align="left">
                                <input type="checkbox" id="cm_tested_itv" value="ON">
                            </td>
                        </tr>
                        <tr>
                            <td width="54%" align="left"><b>End to End Tested</b></td>
                            <td width="46%" align="left">
                                <input type="checkbox" id="cm_tested_endtoend" value="ON" >
                            </td>
                        </tr>
                        <tr>
                            <td width="54%" align="left"><b>Development Tested</b></td>
                            <td width="46%" align="left">
                                <input type="checkbox" id="cm_tested_development" value="ON" >
                            </td>
                        </tr>
                        <tr>
                            <td width="54%" align="left"><b>User Tested</b></td>
                            <td width="46%" align="left">
                                <input type="checkbox" id="cm_tested_user" value="ON" >
                            </td>
                        </tr>
                        <tr>
                            <td width="54%" align="left"><b>ORL Tested</b></td>
                            <td width="46%" align="left">
                                <input type="checkbox" id="cm_tested_orl" value="ON" >
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td width="33%" valign="top" align="left">
                    <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse" bordercolor="#111111" width="100%">
                        <tr>
                            <td width="60%" align="left"><b>Emergency Change?</b></td>
                            <td width="40%" align="left">
                                <input type="checkbox" id="cm_emergency_change" value="ON" >
                            </td>
                        </tr>
                        <tr>
                            <td width="60%" align="left"><b>IR for Featured Project</b></td>
                            <td width="40%" align="left">
                                <input type="checkbox" id="cm_featured_project" value="ON" >
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
                                <input type=text id="cm_ipl_boot" size="27" style="width: 5em" value="">
                            </td>
                            <td width="48%" align="left">
                                <input type=text id="cm_plan_a_b" size="27" style="width: 5em" value="">
                            </td>
                        </tr>
                    </table>
                    <br>
                </td>
            </tr>
        </table>
    </div>

    <!-- DESCRIPTION IMPLEMENTATION INSTRUCTIONS -->
    <div id="tab2" class="tab" style="width: 800px; height: 520px;">
        <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
            <tr>
                <td width="100%">
                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                        <legend><font color="#8F4300"><b>Description</b></font></legend>
                        <textarea rows="13" id="cm_description" name="description" cols="99" style="width: 99%"></textarea>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <td width="100%">
                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                        <legend><font color="#8F4300"><b>Implementation Instructions</b></font></legend>
                        <textarea rows="13" id="cm_implementation_instructions" name="implementation_instructions" cols="99" style="width: 99%"></textarea>
                    </fieldset>
                </td>
            </tr>
        </table>
    </div>

    <!-- BACKOFF PLANS -->
    <div id="tab3" class="tab" style="width: 800px; height: 520px;">
        <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
            <tr>
                <td width="100%">
                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                        <legend><font color="#8F4300"><b>Backoff Plans</b></font></legend>
                        <textarea rows="28" id="cm_backoff_plan" name="backoff_plan" cols="99" style="width: 99%"></textarea>
                    </fieldset>
                </td>
            </tr>
        </table>
    </div>

    <!-- BUSINESS REASON -->
    <div id="tab4" class="tab" style="width: 800px; height: 520px;">
        <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
            <tr>
                <td width="100%">
                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                        <legend><font color="#8F4300"><b>Business Reasons</b></font></legend>
                        <textarea rows="13" id="cm_business_reason" name="business_reason" cols="99" style="width: 99%"></textarea>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <td width="100%">
                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                        <legend><font color="#8F4300"><b>Impact</b></font></legend>
                        <textarea rows="13" id="cm_impact" name="impact" cols="99" style="width: 99%"></textarea>
                    </fieldset>
                </td>
            </tr>
        </table>
    </div>
</div>

<script type="text/javascript">
    var config = {
        tabs: {
            name: 'tabs',
            active: 'tab1',
            style: 'background: #ECE9D8',
            tabs: [
                { id: 'tab1', caption: 'General' },
                { id: 'tab2', caption: 'Description / Implemenation Instructions' },
                { id: 'tab3', caption: 'Backoff Plans' },
                { id: 'tab4', caption: 'Business Reason / Impact' },
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
        callAjaxTicket("<?php echo $cm_ticket_no; ?>");
        $('#tabs').w2tabs(config.tabs);
        $('#tab2').show();
        w2ui.tabs.click('tab1');
    });
</script>

</body>
</html>
