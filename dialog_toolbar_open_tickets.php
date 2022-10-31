<?php
/**
 * dialog_toolbar_open_tickets.php
 *
 * @package   PhpStorm
 * @file      dialog_toolbar_open_tickets.php
 * @author    gparkin
 * @date      6/30/16
 * @version   7.0
 *
 * @brief     Dialog box used for working with CCT Tickets selected in toolbar_open.php
 *
 * @brief     This dialog box will DISABLE buttons based upon the ticket status. No buttons
 *            will be available unless it is ACTIVE or in DRAFT status mode.
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
$lib->debug_start('dialog_toolbar_open_tickets.html');
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

if (!isset($parm['ticket_no']))
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing ticket_no");
    printf("Missing ticket_no");
    exit();
}

$ticket_no = $parm['ticket_no'];

if (!isset($parm['what_tickets']))
{
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing what_tickets");
	printf("Missing what_tickets");
	exit();
}

// toolbar links set $what_tickets to one of the following:
//   Open Tickets = group
//   Approve      = approve
//   All Tickets  = <blank>
//   Search       = ticket_no or cm_ticket_no

$what_tickets = $parm['what_tickets'];
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "what_tickets: %s", $what_tickets);

$tic = new cct7_tickets();

if ($tic->getTicket($ticket_no) == false)
{
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $tic->error);
	printf("Missing ticket_no");
	exit();
}

// $authorize is a bool to indicate whether user is an owner of the ticket.
$authorize = $tic->authorize;
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "authorize = %s", $authorize == true ? "true" : "false");
?>

<!DOCTYPE html>
<html>
<head>
    <title>CCT Ticket</title>

    <style>
        .my_textarea
        {
            border:  1px solid #999999;
            width:   99%;
            margin:  5px 0;
            padding: 3px;
            resize:  none;
            font-size: 13px;
        }

        .loader
        {
            position:         fixed;
            left:             0px;
            top:              0px;
            width:            100%;
            height:           100%;
            z-index:          9999;
            background:       url('images/gears_animated.gif') 50% 50% no-repeat rgb(249,249,249);
        }
        .blue {
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            padding: 4px 23px;
            border: 1px solid #07347b;
            border-radius: 8px;
            background: #0d62e8;
            background: -webkit-gradient(linear, left top, left bottom, from(#0d62e8), to(#07347b));
            background: -moz-linear-gradient(top, #0d62e8, #07347b);
            background: linear-gradient(to bottom, #0d62e8, #07347b);
            -webkit-box-shadow: #1076ff 4px 4px 5px 0px;
            -moz-box-shadow: #1076ff 4px 4px 5px 0px;
            box-shadow: #1076ff 4px 4px 5px 0px;
            text-shadow: #041f49 3px 2px 0px;
            font: normal normal bold 20px arial;
            color: #ffffff;
            text-decoration: none;
        }
        .blue:hover,
        .blue:focus {
            border: 1px solid ##083d91;
            background: #1076ff;
            background: -webkit-gradient(linear, left top, left bottom, from(#1076ff), to(#083e94));
            background: -moz-linear-gradient(top, #1076ff, #083e94);
            background: linear-gradient(to bottom, #1076ff, #083e94);
            color: #ffffff;
            text-decoration: none;
        }
        .blue:active {
            background: #07347b;
            background: -webkit-gradient(linear, left top, left bottom, from(#07347b), to(#07347b));
            background: -moz-linear-gradient(top, #07347b, #07347b);
            background: linear-gradient(to bottom, #07347b, #07347b);
        }
        .green {
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            padding: 4px 23px;
            border: 1px solid #046a14;
            border-radius: 8px;
            background: #08c825;
            background: -webkit-gradient(linear, left top, left bottom, from(#08c825), to(#046a14));
            background: -moz-linear-gradient(top, #08c825, #046a14);
            background: linear-gradient(to bottom, #08c825, #046a14);
            -webkit-box-shadow: #0af02c 4px 4px 5px 0px;
            -moz-box-shadow: #0af02c 4px 4px 5px 0px;
            box-shadow: #0af02c 4px 4px 5px 0px;
            text-shadow: #033f0c 3px 2px 0px;
            font: normal normal bold 20px arial;
            color: #ffffff;
            text-decoration: none;
        }
        .green:hover,
        .green:focus {
            border: 1px solid ##057d17;
            background: #0af02c;
            background: -webkit-gradient(linear, left top, left bottom, from(#0af02c), to(#057f18));
            background: -moz-linear-gradient(top, #0af02c, #057f18);
            background: linear-gradient(to bottom, #0af02c, #057f18);
            color: #ffffff;
            text-decoration: none;
        }
        .green:active {
            background: #046a14;
            background: -webkit-gradient(linear, left top, left bottom, from(#046a14), to(#046a14));
            background: -moz-linear-gradient(top, #046a14, #046a14);
            background: linear-gradient(to bottom, #046a14, #046a14);
        }
        .detail_green {
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            padding: 4px 23px;
            border: 1px solid #046a14;
            border-radius: 8px;
            background: #08c825;
            background: -webkit-gradient(linear, left top, left bottom, from(#08c825), to(#046a14));
            background: -moz-linear-gradient(top, #08c825, #046a14);
            background: linear-gradient(to bottom, #08c825, #046a14);
            -webkit-box-shadow: #0af02c 4px 4px 5px 0px;
            -moz-box-shadow: #0af02c 4px 4px 5px 0px;
            box-shadow: #0af02c 4px 4px 5px 0px;
            text-shadow: #033f0c 3px 2px 0px;
            font: normal normal bold 20px arial;
            color: #ffffff;
            text-decoration: none;
        }
        .detail_green:hover,
        .detail_green:focus {
            border: 1px solid ##057d17;
            background: #0af02c;
            background: -webkit-gradient(linear, left top, left bottom, from(#0af02c), to(#057f18));
            background: -moz-linear-gradient(top, #0af02c, #057f18);
            background: linear-gradient(to bottom, #0af02c, #057f18);
            color: #ffffff;
            text-decoration: none;
        }
        .detail_green:active {
            background: #046a14;
            background: -webkit-gradient(linear, left top, left bottom, from(#046a14), to(#046a14));
            background: -moz-linear-gradient(top, #046a14, #046a14);
            background: linear-gradient(to bottom, #046a14, #046a14);
        }
        .red {
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            padding: 4px 23px;
            border: 1px solid #64070d;
            border-radius: 8px;
            background: #ff1423;
            background: -webkit-gradient(linear, left top, left bottom, from(#ff1423), to(#64070d));
            background: -moz-linear-gradient(top, #ff1423, #64070d);
            background: linear-gradient(to bottom, #ff1423, #64070d);
            -webkit-box-shadow: #ff182a 4px 4px 5px 0px;
            -moz-box-shadow: #ff182a 4px 4px 5px 0px;
            box-shadow: #ff182a 4px 4px 5px 0px;
            text-shadow: #380407 3px 2px 0px;
            font: normal normal bold 20px arial;
            color: #ffffff;
            text-decoration: none;
        }
        .red:hover,
        .red:focus {
            border: 1px solid ##6f080e;
            background: #ff182a;
            background: -webkit-gradient(linear, left top, left bottom, from(#ff182a), to(#780810));
            background: -moz-linear-gradient(top, #ff182a, #780810);
            background: linear-gradient(to bottom, #ff182a, #780810);
            color: #ffffff;
            text-decoration: none;
        }
        .red:active {
            background: #64070d;
            background: -webkit-gradient(linear, left top, left bottom, from(#64070d), to(#64070d));
            background: -moz-linear-gradient(top, #64070d, #64070d);
            background: linear-gradient(to bottom, #64070d, #64070d);
        }
        .detail_red {
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            padding: 4px 23px;
            border: 1px solid #64070d;
            border-radius: 8px;
            background: #ff1423;
            background: -webkit-gradient(linear, left top, left bottom, from(#ff1423), to(#64070d));
            background: -moz-linear-gradient(top, #ff1423, #64070d);
            background: linear-gradient(to bottom, #ff1423, #64070d);
            -webkit-box-shadow: #ff182a 4px 4px 5px 0px;
            -moz-box-shadow: #ff182a 4px 4px 5px 0px;
            box-shadow: #ff182a 4px 4px 5px 0px;
            text-shadow: #380407 3px 2px 0px;
            font: normal normal bold 20px arial;
            color: #ffffff;
            text-decoration: none;
        }
        .detail_red:hover,
        .detail_red:focus {
            border: 1px solid ##6f080e;
            background: #ff182a;
            background: -webkit-gradient(linear, left top, left bottom, from(#ff182a), to(#780810));
            background: -moz-linear-gradient(top, #ff182a, #780810);
            background: linear-gradient(to bottom, #ff182a, #780810);
            color: #ffffff;
            text-decoration: none;
        }
        .detail_red:active {
            background: #64070d;
            background: -webkit-gradient(linear, left top, left bottom, from(#64070d), to(#64070d));
            background: -moz-linear-gradient(top, #64070d, #64070d);
            background: linear-gradient(to bottom, #64070d, #64070d);
        }
        .brown {
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            padding: 4px 23px;
            border: 1px solid #89441e;
            border-radius: 8px;
            background: #de6f31;
            background: -webkit-gradient(linear, left top, left bottom, from(#de6f31), to(#89441e));
            background: -moz-linear-gradient(top, #de6f31, #89441e);
            background: linear-gradient(to bottom, #de6f31, #89441e);
            -webkit-box-shadow: #cd662e 4px 4px 5px 0px;
            -moz-box-shadow: #cd662e 4px 4px 5px 0px;
            box-shadow: #cd662e 4px 4px 5px 0px;
            text-shadow: #562b13 3px 2px 0px;
            font: normal normal bold 20px arial;
            color: #ffffff;
            text-decoration: none;
        }
        .brown:hover,
        .brown:focus {
            border: 1px solid ##ab5526;
            background: #ff853b;
            background: -webkit-gradient(linear, left top, left bottom, from(#ff853b), to(#a45224));
            background: -moz-linear-gradient(top, #ff853b, #a45224);
            background: linear-gradient(to bottom, #ff853b, #a45224);
            color: #ffffff;
            text-decoration: none;
        }
        .brown:active {
            background: #89441e;
            background: -webkit-gradient(linear, left top, left bottom, from(#89441e), to(#89441e));
            background: -moz-linear-gradient(top, #89441e, #89441e);
            background: linear-gradient(to bottom, #89441e, #89441e);
        }
        .detail_brown {
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            padding: 4px 23px;
            border: 1px solid #89441e;
            border-radius: 8px;
            background: #de6f31;
            background: -webkit-gradient(linear, left top, left bottom, from(#de6f31), to(#89441e));
            background: -moz-linear-gradient(top, #de6f31, #89441e);
            background: linear-gradient(to bottom, #de6f31, #89441e);
            -webkit-box-shadow: #cd662e 4px 4px 5px 0px;
            -moz-box-shadow: #cd662e 4px 4px 5px 0px;
            box-shadow: #cd662e 4px 4px 5px 0px;
            text-shadow: #562b13 3px 2px 0px;
            font: normal normal bold 20px arial;
            color: #ffffff;
            text-decoration: none;
        }
        .detail_brown:hover,
        .detail_brown:focus {
            border: 1px solid ##ab5526;
            background: #ff853b;
            background: -webkit-gradient(linear, left top, left bottom, from(#ff853b), to(#a45224));
            background: -moz-linear-gradient(top, #ff853b, #a45224);
            background: linear-gradient(to bottom, #ff853b, #a45224);
            color: #ffffff;
            text-decoration: none;
        }
        .detail_brown:active {
            background: #89441e;
            background: -webkit-gradient(linear, left top, left bottom, from(#89441e), to(#89441e));
            background: -moz-linear-gradient(top, #89441e, #89441e);
            background: linear-gradient(to bottom, #89441e, #89441e);
        }
        .purple {
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            padding: 4px 23px;
            border: 1px solid #570784;
            border-radius: 8px;
            background: #a30df8;
            background: -webkit-gradient(linear, left top, left bottom, from(#a30df8), to(#570784));
            background: -moz-linear-gradient(top, #a30df8, #570784);
            background: linear-gradient(to bottom, #a30df8, #570784);
            -webkit-box-shadow: #c410ff 4px 4px 5px 0px;
            -moz-box-shadow: #c410ff 4px 4px 5px 0px;
            box-shadow: #c410ff 4px 4px 5px 0px;
            text-shadow: #33044e 3px 2px 0px;
            font: normal normal bold 20px arial;
            color: #ffffff;
            text-decoration: none;
        }
        .purple:hover,
        .purple:focus {
            border: 1px solid ##66089b;
            background: #c410ff;
            background: -webkit-gradient(linear, left top, left bottom, from(#c410ff), to(#68089e));
            background: -moz-linear-gradient(top, #c410ff, #68089e);
            background: linear-gradient(to bottom, #c410ff, #68089e);
            color: #ffffff;
            text-decoration: none;
        }
        .purple:active {
            background: #570784;
            background: -webkit-gradient(linear, left top, left bottom, from(#570784), to(#570784));
            background: -moz-linear-gradient(top, #570784, #570784);
            background: linear-gradient(to bottom, #570784, #570784);
        }

    </style>
    <script type="text/javascript" src="js/jquery-2.1.4.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>

    <script type="text/javascript">

        function callAjaxTicket(action, ticket_no)
        {
            $(".loader").show();

            var data;

            // where action = 'sendmail'
            var note         = document.getElementById('note').value;
            var email_cc     = document.getElementById('email_cc').value;
            var email_bcc    = document.getElementById('email_bcc').value;
            var subject_line = document.getElementById('subject_line').value;
            var message_body = document.getElementById('message_body').value;

            // where action = 'update'
            var cm_ticket_no         = document.getElementById('cm_ticket_no').value;
            var work_description     = document.getElementById('work_description').value;
            var work_implementation  = document.getElementById('work_implementation').value;
            var work_backoff_plan    = document.getElementById('work_backoff_plan').value;
            var work_business_reason = document.getElementById('work_business_reason').value;
            var work_user_impact     = document.getElementById('work_user_impact').value;

            //
            // Prepare the data that will be sent to ajax_ticket.php
            //
            data = {
                "action":               action,
                "ticket_no":            ticket_no,
                "log_entry":            note,
                "email_cc":             email_cc,
                "email_bcc":            email_bcc,
                "subject_line":         subject_line,
                "message_body":         message_body,
                "cm_ticket_no":         cm_ticket_no,
                "work_description":     work_description,
                "work_implementation":  work_implementation,
                "work_backoff_plan":    work_backoff_plan,
                "work_business_reason": work_business_reason,
                "work_user_impact":     work_user_impact
            };

            //
            // Create a JSON string from the selected row of data.
            //
            //var gridData = jQuery(TicketGrid).jqGrid('getRowData', Ticket_id);
            //var postData = JSON.stringify(gridData);
            //var postData = JSON.stringify(data);
            //alert(postData);

            //alert(JSON.stringify(data));

            var url = 'ajax_dialog_toolbar_open_tickets.php';

            $.ajax(
                {
                    type:     "POST",
                    url:      url,
                    dataType: "json",
                    data:     JSON.stringify(data),
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

                        document.getElementById('ticket_no').innerHTML     = data['ticket_no'];
                        document.getElementById('owner_name').innerHTML    = data['owner_name'];
                        document.getElementById('insert_date').innerHTML   = data['insert_date'];
                        document.getElementById('owner_email').innerHTML   = data['owner_email'];
                        document.getElementById("status").style.color      = 'yellow';
                        document.getElementById("status").style.background = 'black';
                        document.getElementById("status").style.fontWeight = 'bold';
                        document.getElementById('status').innerHTML        = data['status'];

                        switch ( data['status'] )
                        {
                            case 'DRAFT':
                                $(".cancel").hide();
                                $(".email").hide();
                                break;
                            case 'ACTIVE':
                                $(".activate").hide();
                                $(".delete").hide();
                                break;
                            default:
                                $(".approve_servers_with_paging").hide();
                                $(".approve_servers_no_paging").hide();
                                $(".approve_group_with_paging").hide();
                                $(".approve_group_no_paging").hide();
                                $(".activate").hide();
                                $(".delete").hide();
                                $(".cancel").hide();
                                //$(".view").hide();
                                $(".copy_to").hide();
                                $(".update1").hide();
                                $(".update2").hide();
                                $(".update3").hide();
                                $(".update4").hide();
                                $(".sendmail").hide();
                                $(".add_entry").hide();
                                break;
                        }

                        <?php
                            /*
                             * Remove all restrictions for India GTS
                             *
                            if ($authorize == false)
                            {
                                if ($what_tickets !== "approve")
                                {
                                    ?>
                                    $(".approve_group_with_paging").hide();
                                    $(".approve_group_no_paging").hide();
                                    <?php
                                }
                                ?>

                                $(".approve_servers_with_paging").hide();
                                $(".approve_servers_no_paging").hide();

                                $(".activate").hide();
                                $(".delete").hide();
                                $(".cancel").hide();
                                $(".update1").hide();
                                $(".update2").hide();
                                $(".update3").hide();
                                $(".update4").hide();
                                <?php
                            }
                            */
                        ?>

                        document.getElementById('owner_job_title').innerHTML         = data['owner_job_title'];
                        document.getElementById('work_activity').innerHTML           = data['work_activity'];
                        document.getElementById('manager_name').innerHTML            = data['manager_name'];
                        document.getElementById('reboot_required').innerHTML         = data['reboot_required'];
                        document.getElementById('manager_email').innerHTML           = data['manager_email'];
                        document.getElementById('approvals_required').innerHTML      = data['approvals_required'];
                        document.getElementById('manager_job_title').innerHTML       = data['manager_job_title'];
                        document.getElementById('email_reminder1_date').innerHTML    = data['email_reminder1_date'];
                        document.getElementById('update_date').innerHTML             = data['update_date'];
                        document.getElementById('email_reminder2_date').innerHTML    = data['email_reminder2_date'];
                        document.getElementById('update_name').innerHTML             = data['update_name'];
                        document.getElementById('email_reminder3_date').innerHTML    = data['email_reminder3_date'];
                        document.getElementById('respond_by_date').innerHTML         = data['respond_by_date'];
                        document.getElementById('total_servers_scheduled').innerHTML = data['total_servers_scheduled'];
                        document.getElementById('total_servers_approved').innerHTML  = data['total_servers_approved'];
                        document.getElementById('schedule_start_date').innerHTML     = data['schedule_start_date'];
                        document.getElementById('remedy_cm_start_date').innerHTML    = data['remedy_cm_start_date'];
                        document.getElementById('remedy_cm_end_date').innerHTML      = data['remedy_cm_end_date'];
                        document.getElementById('cm_ticket_no').value                = data['cm_ticket_no'];
                        document.getElementById('work_description').textContent      = data['work_description'];
                        document.getElementById('work_implementation').textContent   = data['work_implementation'];
                        document.getElementById('work_backoff_plan').textContent     = data['work_backoff_plan'];
                        document.getElementById('work_business_reason').textContent  = data['work_business_reason'];
                        document.getElementById('work_user_impact').textContent      = data['work_user_impact'];

                        document.getElementById('log_entries').textContent           = data['log_entries'];

                        if (data['cm_ticket_no'])
                        {
                            document.getElementById('cct_ticket_no').textContent         =
                                'All contacts associated with ticket ' +
                                data['ticket_no'] + '/' + data['cm_ticket_no'];

                            document.getElementById('subject_line').value =
                                data['ticket_no'] + '/' + data['cm_ticket_no'];
                        }
                        else
                        {
                            document.getElementById('cct_ticket_no').textContent         =
                                'All contacts associated with ticket ' +
                                data['ticket_no'];

                            document.getElementById('subject_line').value = data['ticket_no'];
                        }

                        //alert(data['work_implementation']);

                        if (data['ajax_message'].length > 0)
                            alert(data['ajax_message']);

                        /**
                         * Action can be one of these:
                         * - log
                         * - activate
                         * - delete
                         * - cancel
                         * - sendmail
                         * - update
                         * - approve_servers_with_paging
                         * - approve_servers_no_paging
                         * - approve_group_with_paging
                         * - approve_group_no_paging
                         */
                        if (action === 'delete')
                        {
                            alert('This DRAFT ticket has been deleted. No notifications were sent out.');
                            parent.postMessage('close w2popup', '*');
                        }

                        if (action === 'approve_servers_with_paging' ||
                            action === 'approve_servers_no_paging' ||
                            action === 'approve_group_with_paging' ||
                            action === 'approve_group_no_paging')
                        {
                            parent.postMessage('refresh_main_grid', '*');
                            parent.postMessage('close w2popup', '*');
                        }

                        if (action === 'cancel')
                        {
                            alert('Work for this ACTIVE ticket has been canceled. Contacts will be notified.')
                            parent.postMessage('close w2popup', '*');
                        }
                        else if (action === 'sendmail_ticket_owner')
                        {
                            alert('Email has been to sent to the ticket owner only.');
                            parent.postMessage('close w2popup', '*');
                        }
                        else if (action === 'sendmail')
                        {
                            alert('Email has been sent to all contacts in this ticket.');
                            parent.postMessage('close w2popup', '*');
                        }
                        else if (action === 'sendmail_not_responded')
                        {
                            alert('Email has been sent to contacts who have not responded in this ticket.');
                            parent.postMessage('close w2popup', '*');
                        }
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
                case 'Copy To':
                    w2confirm('Are you sure you want to overwrite all text in this CCT ticket with text from the Remedy ticket?',
                        function btn(answer)
                        {
                            // console.log(answer); // Yes or No -- case-sensative

                            if (answer == 'Yes')
                                callAjaxTicket("copy_to", "<?php echo $ticket_no; ?>");
                        }
                    );
                    break;
                case 'Add Entry':
                    callAjaxTicket("log", "<?php echo $ticket_no; ?>");
                    break;
                case 'Activate':
                    callAjaxTicket("activate", "<?php echo $ticket_no; ?>");
                    break;
                case 'Delete':
                    w2confirm('Are you sure you want to delete this ticket?',
                        function btn(answer)
                        {
                            // console.log(answer); // Yes or No -- case-sensative

                            if (answer == 'Yes')
                                callAjaxTicket("delete", "<?php echo $ticket_no; ?>");
                        }
                    );
                    break;
                case 'Cancel':
                    w2confirm('Are you sure you want to cancel all work for this ticket?',
                        function btn(answer)
                        {
                            // console.log(answer); // Yes or No -- case-sensative

                            if (answer == 'Yes')
                                callAjaxTicket("cancel", "<?php echo $ticket_no; ?>");
                        }
                    );
                    break;
                case 'Sendmail - Ticket Owner':
                    callAjaxTicket("sendmail_ticket_owner", "<?php echo $ticket_no; ?>");
                    break;
                case 'Sendmail - To All':
                    callAjaxTicket("sendmail", "<?php echo $ticket_no; ?>");
                    break;
                case 'Sendmail - Not Responded':
                    callAjaxTicket("sendmail_not_responded", "<?php echo $ticket_no; ?>");
                    break;
                case 'Update':
                    callAjaxTicket("update", "<?php echo $ticket_no; ?>");
                    break;
                case 'Approve Servers - With Paging':
                    callAjaxTicket("approve_servers_with_paging", "<?php echo $ticket_no; ?>");
                    break;
                case 'Approve Servers - NO PAGING':
                    callAjaxTicket("approve_servers_no_paging", "<?php echo $ticket_no; ?>");
                    break;
                case 'Approve Group - With Paging':
                    callAjaxTicket("approve_group_with_paging", "<?php echo $ticket_no; ?>");
                    break;
                    break;
                case 'Approve Group - NO PAGING':
                    callAjaxTicket("approve_group_no_paging", "<?php echo $ticket_no; ?>");
                    break;
                    break;
                default:
                    alert('No logic coded for this button. Please contact Greg Parkin: ' + ele.value);
                    break;
            }
        }
    </script>
    <script type="text/javascript">
        $(window).load(function()
        {
            $(".loader").fadeOut("slow");
        });
    </script>
</head>
<body>
<div class="loader"></div>
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

    b {
        font-size: 13px;
    }

    select {
        font-size: 12px;
    }
</style>
<form name="f1">
<div id="tab-example">
    <div id="tabs" style="width: 100%; height: 29px;"></div>

    <!-- GENERAL -->

    <!-- document.getElementById('sb_action').innerHTML = info_action[what]; -->
    <!-- <span id="sb_action" style="color: blue; font-size: 14px">Action edit dialog screen.</span> -->

    <div id="tab1" class="tab" style="width: 825px; height: 520px;">
        <table bgcolor="#ECE9D8" width="100%" cellspacing="2" cellpadding="2" style="height: 100%; color: black">
            <tr>
                <td align="right" valign="top">        <b>CCT Ticket:</b></td><td align="left" valign="top"><span id="ticket_no"></span></td>
                <td align="right" valign="top">        <b>Owner Name:</b></td><td align="left" valign="top"><span id="owner_name"></span></td>
            </tr>
            <tr>
                <td align="right" valign="top">     <b>Creation Date:</b></td><td align="left" valign="top"><span id="insert_date"></span></td>
                <td align="right" valign="top">       <b>Owner Email:</b></td><td align="left" valign="top"><span id="owner_email"></span></td>
            </tr>
            <tr>
                <td align="right" valign="top">     <b>Ticket Status:</b></td><td align="left" valign="top"><span id="status"></span></td>
                <td align="right" valign="top">   <b>Owner Job Title:</b></td><td align="left" valign="top"><span id="owner_job_title"></span></td>
            </tr>
            <tr>
                <td align="right" valign="top">     <b>Work Activity:</b></td><td align="left" valign="top"><span id="work_activity"></span></td>
                <td align="right" valign="top">      <b>Manager Name:</b></td><td align="left" valign="top"><span id="manager_name"></span></td>
            </tr>
            <tr>
                <td align="right" valign="top">   <b>Reboot Required:</b></td><td align="left" valign="top"><span id="reboot_required"></span></td>
                <td align="right" valign="top">     <b>Manager Email:</b></td><td align="left" valign="top"><span id="manager_email"></span></td>
            </tr>
            <tr>
                <td align="right" valign="top"><b>Approvals Required:</b></td><td align="left" valign="top"><span id="approvals_required"></span></td>
                <td align="right" valign="top"> <b>Manager Job Title:</b></td><td align="left" valign="top"><span id="manager_job_title"></span></td>
            </tr>
            <tr>
                <td colspan="4">&nbsp;</td>
            </tr>
            <tr>
                <td align="right" valign="top">  <b>Email Reminder 1:</b></td><td align="left" valign="top"><span id="email_reminder1_date"></span></td>
                <td align="right" valign="top">     <b>Last Modified:</b></td><td align="left" valign="top"><span id="update_date"></span></td>
            </tr>
            <tr>
                <td align="right" valign="top">  <b>Email Reminder 2:</b></td><td align="left" valign="top"><span id="email_reminder2_date"></span></td>
                <td align="right" valign="top">       <b>Modified By:</b></td><td align="left" valign="top"><span id="update_name"></span></td>
            </tr>
            <tr>
                <td align="right" valign="top">  <b>Email Reminder 3:</b></td><td align="left" valign="top"><span id="email_reminder3_date"></span></td>
                <td align="right" valign="top">     <b>Total Servers:</b></td><td align="left" valign="top"><span id="total_servers_scheduled"></span></td>
            </tr>
            <tr>
                <td align="right" valign="top">        <b>Respond By:</b></td><td align="left" valign="top"><span id="respond_by_date"></span></td>
                <td align="right" valign="top">  <b>Servers Approved:</b></td><td align="left" valign="top"><span id="total_servers_approved"></span></td>
            </tr>
            <tr>
                <td align="right" valign="top">    <b>Schedule Start:</b></td>
                <td align="left" valign="top"><span id="schedule_start_date"></span></td>
                <td align="right" valign="top">     <b>Window Begin:</b></td>
                <td align="left" valign="top"><span id="remedy_cm_start_date"></span></td>
            </tr>
            <tr>
                <td align="right" valign="middle"><b>Remedy CM:</b></td>
                <td align="left" valign="middle">
                    <input title="Optional Remedy CM ticket."
                           class="cm_ticket_no" type="text" name="cm_ticket_no" id="cm_ticket_no" size="10" maxlength="20">

                    <input title="Click to view Remedy CM ticket."
                           class="view" type="button" name="button" value="View" onclick="viewRemedyCM();">

                    <input title="Copy text from Remedy ticket to this CCT ticket."
                           class="copy_to" type="button" name="button" value="Copy To" onclick="buttonClick(this);">
                </td>
                <td align="right" valign="top">      <b>Window End:</b></td>
                <td align="left" valign="top"><span id="remedy_cm_end_date"></span></td>
            </tr>

            <tr>
                <td align="center" colspan="4">
					<?php
					// $what_tickets = [ '', '<ticket_no | cm_ticket_no>', 'group', or 'approve' ]
					if ($what_tickets == "approve")
					{
						?>
                        <input title="Approve work with paging for all servers where user netgroup(s) is a contact."
                               class="approve_group_with_paging" type="button"
                               name="approve_group_with_paging" value="Approve Group - With Paging"
                               onclick="buttonClick(this);">

                        <input title="Approve work with no paging for all servers where user netgroup(s) is a contact."
                               class="approve_group_no_paging" type="button"
                               name="approve_group_no_paging" value="Approve Group - NO PAGING"
                               onclick="buttonClick(this);">
						<?php
					}
					/**
					else
					{
						?>
                        <input title="Approve work on all servers for all groups and page the oncall person."
                               class="approve_servers_with_paging" type="button"
                               name="approve_servers_with_paging" value="Approve Servers - With Paging"
                               onclick="buttonClick(this);">

                        <input title="Approve work on all servers for all groups and DO NOT page the oncall person."
                               class="approve_servers_no_paging" type="button"
                               name="approve_servers_no_paging" value="Approve Servers - NO PAGING"
                               onclick="buttonClick(this);">
						<?php
					}
                    */
					else if ($what_tickets == "group")
                    {
                        ?>
                        <input title="Click to activate this ticket. Notifications will be sent to users."
                               class="activate" type="button" name="activate" value="Activate" onclick="buttonClick(this);">

                        <input title="Click this button to delete this ticket. No email is sent."
                               class="delete"   type="button" name="delete"   value="Delete"   onclick="buttonClick(this);">

                        <input title="Click on this button to cancel this work request. Email will be sent out."
                               class="cancel"   type="button" name="cancel"   value="Cancel"   onclick="buttonClick(this);">
                        <?php
                    }
					?>
                    <!-- input title="Save your changes."
                           class="update1"  type="button" name="button"   value="Update"   onclick="buttonClick(this);" -->
                </td>
            </tr>
        </table>
    </div>

    <!-- DESCRIPTION IMPLEMENTATION INSTRUCTIONS -->
    <div id="tab2" class="tab" style="width: 825px; height: 520px;">
        <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
            <tr>
                <td width="100%" valign="top">
                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                        <legend><font color="#8F4300"><b>Description</b></font></legend>
                        <textarea class="my_textarea" rows="10" id="work_description" name="work_description"
                                  cols="99" style="width: 99%;"></textarea>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <td width="100%" valign="top">
                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                        <legend><font color="#8F4300"><b>Implementation Instructions</b></font></legend>
                        <textarea class="my_textarea" rows="10" id="work_implementation" name="work_implementation"
                                  cols="99" style="width: 99%;"></textarea>
                    </fieldset>
                </td>
            </tr>
        </table>
    </div>

    <!-- BACKOFF PLANS -->
    <div id="tab3" class="tab" style="width: 825px; height: 520px;">
        <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
            <tr>
                <td width="100%" valign="top">
                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                        <legend><font color="#8F4300"><b>Backoff Plans</b></font></legend>
                        <textarea class="my_textarea" rows="24" id="work_backoff_plan" name="work_backoff_plan"
                                  cols="99" style="width: 99%;"></textarea>
                    </fieldset>
                </td>
            </tr>
        </table>
    </div>

    <!-- BUSINESS REASON -->
    <div id="tab4" class="tab" style="width: 825px; height: 520px;">
        <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
            <tr>
                <td width="100%" valign="top">
                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                        <legend><font color="#8F4300"><b>Business Reasons</b></font></legend>
                        <textarea class="my_textarea" rows="10" id="work_business_reason" name="work_business_reason"
                                  cols="99" style="width: 99%;"></textarea>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <td width="100%" valign="top">
                    <fieldset style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px; border-color: white">
                        <legend><font color="#8F4300"><b>Impact</b></font></legend>
                        <textarea class="my_textarea" rows="10" id="work_user_impact" name="work_user_impact"
                                  cols="99" style="width: 99%;"></textarea>
                    </fieldset>
                </td>
            </tr>
        </table>
    </div>

    <!-- SEND EMAIL -->
    <div id="tab5" class="tab" style="width: 825px; height: 520px;">
        <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
            <tr>
                <td align="left" valign="top" style="color: #000000;" width="100%">
                    <b>From:</b>
                    <?php printf("%s &lt;%s&gt;", $_SESSION['user_name'], $_SESSION['user_email']); ?>
                </td>
            </tr>
            <tr>
                <td align="left" valign="top" style="color: #000000;" width="100%">
                    <b>To:</b>
                    <span id="cct_ticket_no"></span>
                </td>
            </tr>
            <tr>
                <td align="left" valign="top" style="color: #000000;" width="100%">
                    <b>Cc:</b> (optional)
                    <input title="Send email copy to the following..."
                           style="width: 99%" size="80"
                           type="text" name="email_cc" id="email_cc">
                </td>
            </tr>
            <tr>
                <td align="left" valign="top" style="color: #000000;" width="100%">
                    <b>Bc:</b> (optional)
                    <input title="Send a blind email copy to the following..."
                           style="width: 99%" size="80"
                           type="text" name="email_bcc" id="email_bcc">
                </td>
            </tr>
            <tr>
                <td align="left" valign="top" style="color: #000000;" width="100%">
                    <b>Subject:</b><br>
                    <input title="You may modify the subject line if you wish."
                           style="width: 99%" size="80"
                           type="text" name="subject_line" id="subject_line" maxlength="80">
                </td>
            </tr>
            <tr>
                <td align="left" valign="top" style="color: #000000;" width="100%">
                    <b>Message Body:</b><br>
                    <textarea rows="10" id="message_body" name="message_body" class="my_textarea"
                              onKeyDown="textCounter(this.form.message_body, this.form.remLen, 4000);"
                              onKeyUp="textCounter(this.form.message_body, this.form.remLen, 4000);"
                              onfocus="resetCursor(this);">
                        </textarea>
                    <font size="2" face="arial">
                        <input readonly type="text" name="remLen" size="3" maxlength="3" value="4000"> characters left
                    </font>
                </td>
            </tr>
            <tr>
                <td align="center" valign="top">
                    <input title="Click this button to send email to the ticket owner only."
                           class="sendmail_ticket_owner" type="button" name="button" value="Sendmail - Ticket Owner"
                           onclick="buttonClick(this);">

                    <input title="Click this button to send out your email to every contact identified in this ticket."
                           class="sendmail" type="button" name="button" value="Sendmail - To All"
                           onclick="buttonClick(this);">
                    &nbsp;&nbsp;
                    <input title="Click this button to send email to those that have not responded."
                           class="sendmail_not_responded" type="button" name="button" value="Sendmail - Not Responded"
                           onclick="buttonClick(this);">
                </td>
            </tr>
        </table>
    </div>

    <!-- TICKET EVENT LOG -->
    <div id="tab6" class="tab" style="width: 825px; height: 520px;">
        <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
            <tr>
                <td align="left" valign="top" style="background-color: #4cd964; color: #000000;" colspan="4" width="100%">
                    <b>Log</b><br>
                    <textarea rows="10" id="log_entries" name="log_entries" class="my_textarea" ></textarea>
                </td>
            </tr>
            <tr>
                <td align="left" valign="top" style="background-color: #e1e463; color: #000000;" colspan="4" width="100%">
                    &nbsp;<b>Note</b><br>
                    <textarea rows="10" id="note" name="note" class="my_textarea" ></textarea>
                    <p align="center">
                        <input title="Click this button to save your log entry."
                               class="add_entry" type="button" name="button" value="Add Entry" onclick="buttonClick(this);">
                    </p>
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
                { id: 'tab5', caption: 'Send Mail' },
                { id: 'tab6', caption: 'Log' }
            ],
            onClick: function (event)
            {
                $('#tab-example .tab').hide();
                $('#tab-example #' + event.target).show();
            }
        }
    };

    function textCounter(field, countfield, maxlimit)
    {
        if (field.value.length > maxlimit) // if too long...trim it!
            field.value = field.value.substring(0, maxlimit);
        else
            countfield.value = maxlimit - field.value.length;
    }

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

    $(function ()
    {
        callAjaxTicket("get", "<?php echo $ticket_no; ?>");
        $('#tabs').w2tabs(config.tabs);
        $('#tab2').show();
        w2ui.tabs.click('tab1');
    });

    function viewRemedyCM()
    {
        var cm_ticket_no = document.getElementById('cm_ticket_no').value;
        var ticket_no    = <?php printf("'%s'", $ticket_no); ?>;

        // view_remedy_cm.php?action=get&cm_ticket_no=CM0000314570&ticket_no=CCT70034521

        //var url = 'view_remedy_cm.php?ticket=' + cm_ticket_no;
        //
        // http://cct7.localhost/dialog_view_cm_ticket.php?cm_ticket_no=CM0000314574&ticket_no=CM0000314574
        //
        var url = 'dialog_view_cm_ticket.php?cm_ticket_no=' + cm_ticket_no + '&ticket_no=' + ticket_no;
        var content = '<iframe src="' + url + '" ' +
            'style="border: none; min-width: 100%; min-height: 100%;">' + '</iframe>';

        var close_button =
                '<a data-toggle="ticket_close" title="Close this dialog box.">' +
                '<button class="btn" onclick="w2popup.close();">Close</button></a>';

        w2popup.open({
            title     : 'View Remedy CM Ticket: ' + cm_ticket_no,
            body      : content,
            width     : 860,
            height    : 600,
            overflow  : 'hidden',
            color     : '#333',
            speed     : '0.3',
            opacity   : '0.8',
            modal     : false,
            showClose : true,
            showMax   : true,
            onOpen    : function (event)
            {

            },
            onClose   : function (event)
            {

            },
            onMax     : function (event) { console.log('max'); },
            onMin     : function (event) { console.log('min'); },
            onKeydown : function (event) { console.log('keydown'); }
        });
    }

    //
    // Setup a event listener to close w2popup (iframe) windows.
    // This event is sent from the child popup iframe window to
    // the parent (this). The event will instruct the parent to
    // close the w2popup window.
    //
    window.addEventListener('message', function(e)
    {
        var key = e.message ? 'message' : 'data';
        var data = e[key];

        w2popup.close();

    },false);
</script>
</form>
</body>
</html>
