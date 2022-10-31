<?php
/**
 * dialog_toolbar_open_contacts.php
 *
 * @package   PhpStorm
 * @file      dialog_toolbar_open_contacts.php
 * @author    gparkin
 * @date      6/30/16
 * @version   7.0
 *
 * @brief     Dialog box used for approving work request and viewing connection information.
 *
 */

//$path = '/usr/lib/pear';
//set_include_path(get_include_path() . PATH_SEPARATOR . $path);

set_include_path("/opt/ibmtools/www/cct7/classes");

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
$lib->debug_start('dialog_toolbar_open_contacts.html');
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

if (!isset($parm['system_id']))
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing system_id");
    printf("Missing system_id");
    exit();
}

if (!isset($parm['contact_netpin_no']))
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing contact_netpin_no");
    printf("Missing contact_netpin_no");
    exit();
}

if (!isset($parm['what_tickets']))
{
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing what_tickets");
	printf("Missing what_tickets");
	exit();
}

if (!isset($parm['title']))
{
    $lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing title");
    printf("Missing title");
    exit();
}

$ticket_no         = $parm['ticket_no'];
$system_id         = $parm['system_id'];
$contact_netpin_no = $parm['contact_netpin_no'];
$title             = $parm['title'];   // CCT700000002 - lxomp11m - REJECTED
$what_tickets      = $parm['what_tickets'];   // Looking to see what mode toolbar_open.php is in. (approve)

// toolbar links set $what_tickets to one of the following:
//   Open Tickets = group
//   Approve      = approve
//   All Tickets  = <blank>
//   Search       = ticket_no or cm_ticket_no
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no         = %d", $ticket_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id         = %d", $system_id);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "contact_netpin_no = %d", $contact_netpin_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "title             = %d", $title);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "what_tickets      = %d", $what_tickets);

$arr = explode('-', $title);

// All contacts associated with CCT700000002 - lxomp11m

if (count($arr) >= 1)
    $title = trim($arr[0]) . ", " . trim($arr[1]) . ", and netpin: " . $contact_netpin_no;

unset($arr);

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
    </style>
    <script type="text/javascript" src="js/jquery-2.1.4.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>

    <script type="text/javascript">

        // var number_of_ajax_calls = 0;

        function callAjaxTicket(action, ticket_no, system_id, contact_netpin_no)
        {
            $(".loader").show();

            var data;

            var note         = document.getElementById('note').value;
            var email_cc     = document.getElementById('email_cc').value;
            var email_bcc    = document.getElementById('email_bcc').value;
            var subject_line = document.getElementById('subject_line').value;
            var message_body = document.getElementById('message_body').value;

            //
            // Prepare the data that will be sent to ajax_ticket.php
            //
            data = {
                "action":            action,
                "ticket_no":         ticket_no,
                "system_id":         system_id,
                "contact_netpin_no": contact_netpin_no,
                "log_entry":         note,
                "mail_cc":           email_cc,
                "mail_bcc":          email_bcc,
                "subject_line":      subject_line,
                "message_body":      message_body
            };

            //
            // Create a JSON string from the selected row of data.
            //
            //var gridData = jQuery(TicketGrid).jqGrid('getRowData', Ticket_id);
            //var postData = JSON.stringify(gridData);
            //var postData = JSON.stringify(data);
            //alert(postData);

            //alert(JSON.stringify(data));

            var url = 'ajax_dialog_toolbar_open_contacts.php';

            $.ajax(
                {
                    type:     "POST",
                    url:      url,
                    dataType: "json",
                    data:     JSON.stringify(data),
                    success:  function(data)
                    {
                        $(".loader").fadeOut("slow");

                        if (data['ajax_status'] == 'FAILED')
                        {
                            alert(data['ajax_message']);
                            return;
                        }

                        // <span id='sb'>xxxx</span>
                        // document.getElementById('sb').innerHTML =
                        //
                        // <input type=text ...>
                        //
                        // document.getElementById('ticket_no').innerHTML               = data['ticket_no'];
                        // document.getElementById('owner_name').innerHTML              = data['owner_name'];
                        // document.getElementById('insert_date').innerHTML             = data['insert_date'];
                        // document.getElementById('owner_email').innerHTML             = data['owner_email'];

                        //
                        // DRAFT     - Ticket was just created and is not waiting to be activated.
                        // ACTIVE    - Ticket is now active and notifications have been sent out.
                        // DELETE    - Ticket is in DRAFT mode and owner wants to delete the ticket.
                        // CANCEL    - Ticket is in ACTIVE mode and owner wants to cancel the ticket.
                        // CLOSED    - Current date is now past the remedy_cm_end_date
                        //

                        // Do not allow any updates after scheduled work start.

                        //if (data['status'] == 'DRAFT')
                        //{
                        //    document.getElementById("status").style.color = '#ffffff';
                        //}

                        //document.getElementById("status").style.color = 'yellow';
                        //document.getElementById("status").style.background = 'black';
                        //document.getElementById("status").style.fontWeight = 'bold';
                        //document.getElementById('status').innerHTML                  = data['status'];

                        /*
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
                                $(".activate").hide();
                                $(".delete").hide();
                                $(".cancel").hide();
                                $(".email").hide();
                                $(".update1").hide();
                                $(".update2").hide();
                                $(".update3").hide();
                                break;
                        }
                        */

                        // contact_id|NUMBER|0|NOT NULL|PK: Unique record ID
                        // system_id|NUMBER|0||FK: cct7_systems.system_id - CASCADE DELETE
                        // contact_netpin_no|VARCHAR2|20||CSC/NET Pin number
                        // contact_insert_date|NUMBER|0||Date of person who created this record
                        // contact_insert_cuid|VARCHAR2|20||CUID of person who created this record
                        // contact_insert_name|VARCHAR2|200||Name of person who created this record
                        // contact_update_date|NUMBER|0||Date of person who updated this record
                        // contact_update_cuid|VARCHAR2|20||CUID of person who updated this record
                        // contact_update_name|VARCHAR2|200||Name of person who updated this record
                        // contact_connection|VARCHAR2|80||Grid label: Connections                    - Server connection list
                        // contact_server_os|VARCHAR2|80||Grid label: OS                              - Server OS list
                        // contact_server_usage|VARCHAR2|80||Grid Label: Status                       - Server OS status: Production, Test, etc.
                        // contact_work_group|VARCHAR2|80||Grid Label: Status                         - OS, APP, DBA, APP_DBA
                        // contact_approver_fyi|VARCHAR2|80||Grid Label: Notify Type                  - APPROVER or FYI
                        // contact_csc_banner|VARCHAR2|200||Grid Label: CSC Support Banners (Primary) - CSC Banner list
                        // contact_apps_databases|VARCHAR2|200||Grid Label: Apps/DBMS                 - MAL and MDL list of applications and databases
                        // contact_respond_by_date|NUMBER|0||Copied over from cct7_tickets.respond_by_date
                        // contact_response_status|VARCHAR2|20||Response Status: WAITING, APPROVED, REJECTED, RESCHEDULE
                        // contact_response_date|NUMBER|0||Response Date
                        // contact_response_cuid|VARCHAR2|20||Response CUID of the net-group member that approved this work
                        // contact_response_name|VARCHAR2|200||Response Name of the net-group member that approved this work
                        // contact_send_page|VARCHAR2|10||Do they want a page?   Y/N
                        // contact_send_email|VARCHAR2|10||Do they want an email? Y/N

                        //       Netpin: 17340
                        //
                        //   Respond By: 08/23/2016        Response Date: 08/17/2016 12:45
                        //     Response: Approved         Responder Name: Greg Parkin
                        //
                        // Page On-Call: Y                    Send Email: Y

                        document.getElementById('contact_netpin_no').innerHTML         = data['contact_netpin_no'];        // Netpin:
                        document.getElementById('contact_respond_by_date').innerHTML   = data['contact_respond_by_date'];  // Respond By:
                        document.getElementById('contact_response_date').innerHTML     = data['contact_response_date'];    // Response Date:
                        document.getElementById('contact_response_status').innerHTML   = data['contact_response_status'];  // Response:
                        document.getElementById('contact_response_name').innerHTML     = data['contact_response_name'];    // Responder Name:
                        document.getElementById('contact_send_page').innerHTML         = data['contact_send_page'];        // Page On-Call:
                        //document.getElementById('contact_send_email').innerHTML        = data['contact_send_email'];       // Send Email:

                        // escapeHTML(document.getElementById('private_note').value;

                        document.getElementById('member_list').value = data['member_list'];
                        document.getElementById('log_entries').value = data['log_entries']

                        if (data['ajax_message'].length > 0)
                            alert(data['ajax_message']);

                        // Available actions:
                        //
                        // log
                        // approve
                        // reject
                        // exempt
                        // sendmail
                        // Page On-Call: Y/N
                        if (action === 'approve_with_paging' || action === 'approve_no_paging' ||
                            action === 'reject'              || action === 'exempt')
                        {
                            parent.postMessage('close w2popup', '*');
                        }

                        if (action === 'sendmail')
                        {
                            alert('Email has been sent to all contacts for this NET group.');
                            parent.postMessage('close w2popup', '*');
                        }
                        else if (action === 'sendmail_not_responded')
                        {
                            alert('Email has been sent to all contacts for this NET group who have not responded.');
                            parent.postMessage('close w2popup', '*');
                        }
                        else if (action === 'sendmail_ticket_owner')
                        {
                            alert('Email has been to sent to the ticket owner only.');
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

            // [ APPROVE ] [ REJECT ] [ EXEMPT ] [ Email ] [ Toggle Page ] [ Toggle Email ]
            // approve, reject, exempt, email, toggle_page, toggle_email

            switch ( ele.value )
            {
                case 'Add Entry':
                    callAjaxTicket('log',          "<?php echo $ticket_no; ?>", "<?php echo $system_id; ?>", "<?php echo $contact_netpin_no; ?>");
                    break;
                case 'Approve - With Paging':
                    callAjaxTicket('approve_with_paging',
                        "<?php echo $ticket_no; ?>", "<?php echo $system_id; ?>", "<?php echo $contact_netpin_no; ?>");
                    break;
                case 'Approve - NO PAGING':
                    callAjaxTicket('approve_no_paging',
                        "<?php echo $ticket_no; ?>", "<?php echo $system_id; ?>", "<?php echo $contact_netpin_no; ?>");
                    break;
                case 'Reject':
                    w2confirm('Are you sure you want reject work for this server?',
                        function btn(answer)
                        {
                            // console.log(answer); // Yes or No -- case-sensative

                            if (answer == 'Yes')
                                callAjaxTicket('reject', "<?php echo $ticket_no; ?>", "<?php echo $system_id; ?>", "<?php echo $contact_netpin_no; ?>");
                        }
                    );
                    break;
                case 'Exempt':
                    w2confirm('Are you sure you want to exempt approving the work for your team on server?',
                        function btn(answer)
                        {
                            // console.log(answer); // Yes or No -- case-sensative

                            if (answer == 'Yes')
                                callAjaxTicket('exempt', "<?php echo $ticket_no; ?>", "<?php echo $system_id; ?>", "<?php echo $contact_netpin_no; ?>");
                        }
                    );
                    break;
                case 'Sendmail - Ticket Owner':
                    callAjaxTicket("sendmail_ticket_owner", "<?php echo $ticket_no; ?>", "<?php echo $system_id; ?>", "<?php echo $contact_netpin_no; ?>");
                    break;
                case 'Sendmail - To All':
                    callAjaxTicket("sendmail",       "<?php echo $ticket_no; ?>", "<?php echo $system_id; ?>", "<?php echo $contact_netpin_no; ?>");
                    break;
                case 'Sendmail - Not Responded':
                    callAjaxTicket("sendmail_not_responded", "<?php echo $ticket_no; ?>", "<?php echo $system_id; ?>", "<?php echo $contact_netpin_no; ?>");
                    break;

                case 'Page On-Call: Y/N':
                    callAjaxTicket('toggle_page',  "<?php echo $ticket_no; ?>", "<?php echo $system_id; ?>", "<?php echo $contact_netpin_no; ?>");
                    break;
                default:
                    alert('No logic coded for this button: ' + ele.value);
                    break;
            }
        }

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
<form name="f1">
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
    textarea {
        font-size: 12px;
    }
    select {
        font-size: 12px;
    }
</style>

<div id="tab-example">
    <div id="tabs" style="width: 100%; height: 29px;"></div>

    <!-- CONTACT -->
    <!--
            Netpin: 17340

        Respond By: 08/23/2016        Response Date: 08/17/2016 12:45
          Response: Approved         Responder Name: Greg Parkin

      Page On-Call: Y                    Send Email: Y

    -->
    <div id="tab1" class="tab" style="width: 800px; height: 520px;">
        <table bgcolor="#ECE9D8" width="100%" cellspacing="2" cellpadding="2" style="color: black">
            <tr>
                <td align="right" valign="top" width="20%"><b>Netpin:</b></td>
                <td align="left" valign="top" width="30%"><span id="contact_netpin_no"></span></td>
                <td colspan="2">&nbsp;</td>
            </tr>
            <tr>
                <td colspan="4">&nbsp;</td>
            </tr>
            <tr>
                <td align="right" valign="top" width="20%"><b>Respond By:</b></td
                ><td align="left" valign="top" width="30%"><span id="contact_respond_by_date"></span></td>
                <td align="right" valign="top" width="20%"><b>Response Date:</b></td>
                <td align="left"  valign="top" width="30%"><span id="contact_response_date"></span></td>
            </tr>
            <tr>
                <td align="right" valign="top" width="20%"><b>Response:</b></td>
                <td align="left"  valign="top" width="30%"><span id="contact_response_status"></span></td>
                <td align="right" valign="top" width="20%"><b>Responder Name:</b></td>
                <td align="left"  valign="top" width="30%"><span id="contact_response_name"></span></td>
            </tr>
            <tr>
                <td colspan="4">&nbsp;</td>
            </tr>
            <tr>
                <td align="right" valign="top" width="20%"><b>Page On-Call:</b></td>
                <td align="left"  valign="top" width="30%"><span id="contact_send_page"></span></td>
                <td colspan="2">&nbsp;</td>
            </tr>
			<?php
			if ($what_tickets == "approve")
			{
				?>
                <tr>
                    <td align="left" valign="top" colspan="4">
                        <p>
                            <b><u>Available options:</u></b>
                        <ul>
                            <li>
                                <b>Approve - With Paging</b> - Approve and page the on-call before and after work.<br><br>
                            </li>
                            <li>
                                <b>Approve - NO PAGING</b> - Approve, but do not page the on-call.<br><br>
                            </li>
                            <li>
                                <b>Reject</b> - Reject this work. Did you consider changing the scheduled start time first?<br><br>
                            </li>
                            <li>
                                <b>Exempt</b> - Exempt your team from having to approve this work.<br><br>
                            </li>
                        </ul>
                        </p>
                    </td>
                </tr>
                <tr>
                    <td colspan="4">&nbsp;</td>
                </tr>
                <!-- // [ APPROVE ] [ REJECT ] [ EXEMPT ] [ Email ] [ Toggle Page ]-->
                <tr>
                    <td align="center" colspan="4">
                        <input title="Approve work for this server and page the on-call during work activity."
                               class="approve_with_paging" type="button" name="approve_with_paging"
                               value="Approve - With Paging"
                               onclick="buttonClick(this);">

                        <input title="Approve work for this server and DO NOT page the on-call during work activity."
                               class="approve_no_paging" type="button" name="approve_no_paging"
                               value="Approve - NO PAGING"
                               onclick="buttonClick(this);">

                        <input title="Reject the work. You will be required to state the reason why."
                               class="reject" type="button" name="reject"
                               value="Reject"
                               onclick="buttonClick(this);">

                        <input title="Exempt group group from approving this work item."
                               class="exempt" type="button" name="exempt"
                               value="Exempt"
                               onclick="buttonClick(this);">
                    </td>
                </tr>
				<?php
			}
			?>
        </table>
    </div>

    <!-- NETPIN GROUP MEMBERS -->
    <div id="tab2" class="tab" style="width: 800px; height: 520px;">
        <table bgcolor="#ECE9D8" width="100%" ellspacing="2" cellpadding="2" style="height: 100%; color: black">
            <tr>
                <td width="100%" valign="top">
                    <textarea rows="30" id="member_list" name="member_list" cols="99" style="width: 99%"></textarea>
                </td>
            </tr>
        </table>
    </div>

    <!-- SEND EMAIL -->
    <div id="tab3" class="tab" style="width: 800px; height: 520px;">
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
                    All contacts associated with <?php echo $title;?>
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
                    <b>Bcc:</b> (optional)
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
                               type="text" name="subject_line" id="subject_line"
                               value="<?php echo $title; ?>" maxlength="80">
                </td>
            </tr>
            <tr>
                <td align="left" valign="top" style="color: #000000;" width="100%">
                    <b>Message Body:</b><br>
                        <textarea rows="12" id="message_body" name="message_body" class="my_textarea"
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

                    <input title="Click this button to send out your email."
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

    <!-- CONTACT EVENT LOG -->
    <div id="tab4" class="tab" style="width: 800px; height: 520px;">
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
                    <textarea rows="10" id="note" name="$log_entry" class="my_textarea" ></textarea>
                    <p align="center">
                        <input title="Click this button to save your log entry."
                               type="button" name="button" value="Add Entry" onclick="buttonClick(this);">
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
                { id: 'tab1', caption: 'Respond' },
                { id: 'tab2', caption: 'Netpin Group Members' },
                { id: 'tab3', caption: 'Send Email' },
                { id: 'tab4', caption: 'Log' }
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
        callAjaxTicket('get', "<?php echo $ticket_no; ?>", "<?php echo $system_id; ?>", "<?php echo $contact_netpin_no; ?>");
        $('#tabs').w2tabs(config.tabs);
        $('#tab1').show();
        w2ui.tabs.click('tab1');
    });
</script>
</form>
</body>
</html>
