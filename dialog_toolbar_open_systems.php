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
$lib->debug_start('dialog_toolbar_open_systems.html');
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

if (!isset($parm['system_id']))
{
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Syntax error. Missing system_id");
	printf("Missing system_id");
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

// toolbar links set $what_tickets to one of the following:
//   Open Tickets = group
//   Approve      = approve
//   All Tickets  = <blank>
//   Search       = ticket_no or cm_ticket_no


$system_id    = $parm['system_id'];
$title        = $parm['title'];
$parts        = explode(" ", $title);
$ticket_no    = $parts[0];
$what_tickets = $parm['what_tickets'];   // Looking to see what mode toolbar_open.php is in. (approve)

$sys = new cct7_systems();

if ($sys->getSystem($system_id) == false)
{
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $sys->error);
	printf("Missing title");
	exit();
}

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %d", $system_id);
?>

<!DOCTYPE html>
<html>
<head>
	<title>system_id = <?php echo $system_id; ?></title>

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
    <script type="text/javascript" src="js/DateTimePicker.js"></script>

	<script type="text/javascript">
		function print_r(printthis)
		{
			var output = '';

			if($.isArray(printthis) || typeof(printthis) == 'object')
			{
				for(var i in printthis)
				{
					output += i + ' : ' + print_r(printthis[i]) + '\n';
				}
			}
			else
			{
				output += printthis;
			}

			alert(output);
		}

		// var number_of_ajax_calls = 0;

		function callAjaxSystem(action, system_id)
		{
			$(".loader").show();

			var data;

			var note                   = document.getElementById('note').value;
			var email_cc               = document.getElementById('email_cc').value;
			var email_bcc              = document.getElementById('email_bcc').value;
			var subject_line           = document.getElementById('subject_line').value;
			var message_body           = document.getElementById('message_body').value;
            var system_work_start_date = document.getElementById('system_work_start_date').value;
            var system_work_end_date   = document.getElementById('system_work_end_date').value;

			//
			// Prepare the data that will be sent to ajax_ticket.php
			//
			data = {
				"action":            action,
				"system_id":         system_id,
				"log_entry":         note,
				"email_cc":          email_cc,
				"email_bcc":         email_bcc,
				"subject_line":      subject_line,
				"message_body":      message_body,
                "work_start_date":   system_work_start_date,
                "work_end_date":     system_work_end_date
			};

			//
			// Create a JSON string from the selected row of data.
			//
			//var gridData = jQuery(TicketGrid).jqGrid('getRowData', Ticket_id);
			//var postData = JSON.stringify(gridData);
			//var postData = JSON.stringify(data);
			//alert(postData);

			//alert(JSON.stringify(data));

			var url = 'ajax_dialog_toolbar_open_systems.php';

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

						//print_r(data);

						document.getElementById("system_work_status").style.color      = 'yellow';
						document.getElementById("system_work_status").style.background = 'black';
						document.getElementById("system_work_status").style.fontWeight = 'bold';
						document.getElementById('system_work_status').innerHTML        = data['system_work_status'];

						//
						// Hide buttons based upon system status
						//
                        /*
						if (data['system_work_status'] != 'APPROVED' && data['system_work_status'] != 'WAITING')
						{
							$(".cancel").hide();      // Cancel
							$(".reschedule").hide();  // Reschedule (next OS maint. window)
							$(".reset").hide();       // Reset Original
						}
						else
						{
							$(".help_me").show();
						}
                        */

                        $(".help_me").show();

						document.getElementById('to_contacts').innerHTML =
                            '<?php printf("All contacts associated with %s - ", $ticket_no); ?>' + data['system_hostname'];

                        document.getElementById('subject_line').value =
                            '<?php printf("%s - ", $ticket_no); ?>' + data['system_hostname'];

						document.getElementById('system_hostname').innerHTML               = data['system_hostname'];
						document.getElementById('system_os').innerHTML                     = data['system_os'];
						document.getElementById('system_usage').innerHTML                  = data['system_usage'];
						document.getElementById('system_location').innerHTML               = data['system_location'];
						document.getElementById('system_timezone_name').innerHTML          = data['system_timezone_name'];
						document.getElementById('system_osmaint_weekly').innerHTML         = data['system_osmaint_weekly'];
						document.getElementById('system_respond_by_date').innerHTML        = data['system_respond_by_date'];

						<?php
                          if ($sys->system_work_start_date_num == 0)
                          {
                              ?>
                                document.getElementById('system_work_start_date').innerHTML        = '(See Remedy)';
                                document.getElementById('system_work_end_date').innerHTML          = '(See Remedy)';
                                document.getElementById('system_work_duration').innerHTML          = '(See Remedy)';
                              <?php
                          }
                          else
                          {
                              ?>
                                document.getElementById('system_work_start_date').value            = data['system_work_start_date'];
                                document.getElementById('system_work_end_date').value              = data['system_work_end_date'];
                                document.getElementById('system_work_duration').innerHTML          = data['system_work_duration'];
							  <?php
                          }
                        ?>

						document.getElementById('system_work_status').innerHTML            = data['system_work_status'];
						document.getElementById('total_contacts_responded').innerHTML      = data['total_contacts_responded'];
						document.getElementById('total_contacts_not_responded').innerHTML  = data['total_contacts_not_responded'];
						document.getElementById('log_entries').value                       = data['log_entries'];

						if (data['ajax_message'].length > 0)
							alert(data['ajax_message']);
						//else if (number_of_ajax_calls > 0)
						//	alert('Operation completed successfully.');

						//++number_of_ajax_calls;

                        if (action === 'reschedule')
                        {
                            alert('The dates should now reflect your new work schedule requirements.');
                        }
                        else if (action === 'reset_original')
                        {
                            alert('The dates should now reflect the original scheduled maintenance window for this server.');
                        }
						else if (action === 'delete')
						{
						    alert('The ticket in DRAFT mode has deleted this server. Not contacts notified.');
							parent.postMessage('close w2popup', '*');
						}
						else if (action === 'cancel')
                        {
                            alert('Work for this ACTIVE ticket and server has been canceled. Contacts will be notified.')
                            parent.postMessage('close w2popup', '*');
                        }
                        else if (action === 'sendmail_ticket_owner')
                        {
                            alert('Email has been to sent to the ticket owner only.');
                            parent.postMessage('close w2popup', '*');
                        }
						else if (action === 'sendmail')
                        {
                            alert('Email has been sent to all server side contacts.');
                            parent.postMessage('close w2popup', '*');
                        }
                        else if (action === 'sendmail_not_responded')
                        {
                            alert('Email has been sent to all server side contacts who have not responded.');
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
			//	alert(ele.value);

			switch ( ele.value )
			{
                case 'Approve - With Paging':
                    callAjaxSystem("approve_with_paging", "<?php echo $system_id; ?>");
                    break;
                case 'Approve - NO PAGING':
                    callAjaxSystem("approve_no_paging", "<?php echo $system_id; ?>");
                    break;
                case 'Reject':
                    w2confirm('Are you sure you want to reject this work?', 'Confirm your action.',
                        function btn(answer)
                        {
                            // console.log(answer); // Yes or No -- case-sensative

                            if (answer == 'Yes')
                                callAjaxSystem("reject", "<?php echo $system_id; ?>");
                        }
                    );
                    break;
                case 'Exempt':
                    w2confirm('Are you sure you want to exempt your team this time?', 'Confirm your action.',
                        function btn(answer)
                        {
                            // console.log(answer); // Yes or No -- case-sensative

                            if (answer == 'Yes')
                                callAjaxSystem("exempt", "<?php echo $system_id; ?>");
                        }
                    );
                    break;
				case 'Delete':         // If ticket is in DRAFT mode, then we can delete the record.
                    w2confirm('Are you sure you want to delete this server?', 'Confirm your action.',
                        function btn(answer)
                        {
                            // console.log(answer); // Yes or No -- case-sensative

                            if (answer == 'Yes')
                                callAjaxSystem("delete", "<?php echo $system_id; ?>");
                        }
                    );
					break;
				case 'Cancel':         // Cancel work request for this server if in ACTIVE mode.
					w2confirm('Are you sure you want to cancel work on this server?', 'Confirm your action.',
						function btn(answer)
						{
							// console.log(answer); // Yes or No -- case-sensative

							if (answer == 'Yes')
								callAjaxSystem("cancel", "<?php echo $system_id; ?>");
						}
					);
					break;
                case 'Reschedule':     // Reschedule start and end dates. (Not the save as Next Maint. Window)
					callAjaxSystem("reschedule",     "<?php echo $system_id; ?>");
					break;
				case 'Reset Original': // Reset work schedule to original start, end times.
					callAjaxSystem("reset_original", "<?php echo $system_id; ?>");
					break;
                case 'Sendmail - Ticket Owner':
                    callAjaxSystem("sendmail_ticket_owner", "<?php echo $system_id; ?>");
                    break;
                case 'Sendmail - To All':
                    callAjaxTicket("sendmail",       "<?php echo $system_id; ?>");
                    break;
                case 'Sendmail - Not Responded':
                    callAjaxTicket("sendmail_not_responded", "<?php echo $system_id; ?>");
                    break;
                case 'Add Entry':
					callAjaxSystem("log",            "<?php echo $system_id; ?>");
					break;
				default:
					alert('No logic coded for this button: ' + ele.value);
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
	<div id="tabs" style="width: 99%; height: 29px;"></div>

	<!-- GENERAL -->

	<!-- document.getElementById('sb_action').innerHTML = info_action[what]; -->
	<!-- <span id="sb_action" style="color: blue; font-size: 14px">Action edit dialog screen.</span> -->

	<div id="tab1" class="tab" style="width: 800px; height: 520px;">
		<table bgcolor="#ECE9D8" width="100%" cellspacing="2" cellpadding="2" style="color: black">
			<tr>
				<td align="right" valign="top" width="15%">            <b>Server:</b></td>
				<td align="left" valign="top" width="20%"><span id="system_hostname"></span></td>
				<td align="right" valign="top" width="20%">        <b>Respond By:</b></td>
				<td align="left" valign="top" width="45%"><span id="system_respond_by_date"></span></td>
			</tr>
			<tr>
				<td align="right" valign="top" width="15%">                <b>OS:</b></td>
				<td align="left" valign="top" width="20%"><span id="system_os"></span></td>
				<td align="right" valign="top" width="20%">       <b>Work Status:</b></td>
				<td align="left" valign="top" width="45%"><span id="system_work_status"></span></td>
			</tr>
			<tr>
				<td align="right" valign="top" width="15%">             <b>Usage:</b></td>
				<td align="left" valign="top" width="20%"><span id="system_usage"></span></td>
				<td align="right" valign="middle" width="20%">     <b>Work Start:</b></td>
				<td align="left" valign="middle" width="45%">
                    <?php
                        if ($sys->system_work_start_date_num == 0)
                        {
							?>
                            <span id="system_work_start_date"></span>
							<?php
                        }
                        else
                        {
                            ?>
                            <input id='system_work_start_date' type="text" name="system_work_start_date"
                                   value="" size="15"
                                   onclick="NewCssCal('system_work_start_date', 'MMddyyyy', 'dropdown', true, '24')" />
                            <?php
                        }
                    ?>
                </td>
			</tr>
			<tr>
				<td align="right" valign="top" width="15%">          <b>Location:</b></td>
				<td align="left" valign="top" width="20%"><span id="system_location"></span></td>
				<td align="right" valign="middle" width="20%">       <b>Work End:</b></td>
				<td align="left" valign="middle" width="45%">
					<?php
					if ($what_tickets != "group" && $what_tickets != "approve")
					{
						?>
                        <span id="system_work_end_date"></span>
						<?php
					}
					else
					{
                        ?>
                        <input id='system_work_end_date' type="text" name="system_work_end_date"
                               value="" size="15"
                               onclick="NewCssCal('system_work_end_date', 'MMddyyyy', 'dropdown', true, '24')" />
                        <?php
					}
					?>
                </td>
			</tr>
			<tr>
				<td align="right" valign="top" width="15%">         <b>Time Zone:</b></td>
				<td align="left" valign="top" width="20%"><span id="system_timezone_name"></span></td>
				<td align="right" valign="top" width="20%">          <b>Duration:</b></td>
				<td align="left" valign="top" width="45%"><span id="system_work_duration"></span></td>
			</tr>
			<tr>
				<td colspan="4">&nbsp;</td>
			</tr>
			<tr>
				<td align="right" valign="top" width="15%">       <b>Maintenance:</b></td>
				<td align="left" valign="top" width="20%"><span id="system_osmaint_weekly"></span></td>
				<td align="right" valign="top" width="20%">
                    <b># Responded:<br>
                       # Waiting:</b>
                </td>
				<td align="left" valign="top" width="45%">
                    <span id="total_contacts_responded"></span><br>
                    <span id="total_contacts_not_responded">
                </td>
			</tr>
            <?php
            if ($what_tickets == "approve")
            {
                ?>
                <tr>
                    <td colspan="4">
                        <div class="help_me" id="help_me" style="display: none">
                            <p>
                                Options available to you.
                            <ul>
                                <li>
                                    <b>Approve - With Paging</b> - Approve and page the on-call before and after work.<br><br>
                                </li>
                                <li>
                                    <b>Approve - NO Paging</b> - Approve, but don't page the on-call person for our team.<br><br>
                                </li>
                                <li>
                                    <b>Reject</b> - Reject the work. Did you consider the reschedule option first?<br><br>
                                </li>
                                <li>
                                    <b>Exempt</b> - You want to exempt your team from approving this work.<br><br>
                                </li>
                                <li>
                                    <b>Reschedule</b> - Click on date fields to pick net times. Then click Reschedule.<br><br>
                                </li>
                                <li>
                                    <b>Reset Original</b> - Reset the dates to their original scheduled times.<br><br>
                                </li>
                            </ul>
                            </p>
                        </div>
                    </td>
                </tr>
            <?php
            }
            else
            {
                printf("<tr><td colspan='6'>&nbsp;</td></tr>\n");
            }
            ?>
            <tr>
                <td align="center" colspan="6">
                    <?php
                    if ($what_tickets == "approve")
                    {
                        ?>
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

                        <br>
                        <?php
                    }
                    else if ($what_tickets == "group")
					{
						?>
                        <input title="Cancel work for this server."
                               class="cancel" type="button" name="cancel"
                               value="Cancel"
                               onclick="buttonClick(this);">
						<?php
					}

					//
					// If the system_work_start_date_num is 0 that means the ticket owner did not use
					// the CCT scheduler and all dates say "(See Remedy)". If system_work_start_date_num > 0
					// then we want to give them the options to change dates.
					//
					if ($what_tickets == "group" || $what_tickets == "approve")
					{
						?>
                        <input title="After manually changing the dates and times, click the Save button to commit changes."
                               class="reschedule" type="button" name="reschedule"   value="Reschedule"     onclick="buttonClick(this);">

                        <input title="Go back to the original work scheduled start and end for this server."
                               class="reset"      type="button" name="reset"        value="Reset Original" onclick="buttonClick(this);">
						<?php
					}
					?>
                </td>
            </tr>
		</table>
	</div>

	<!-- SEND EMAIL -->
	<div id="tab2" class="tab" style="width: 800px; height: 520px;">
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
                    <span id="to_contacts">All contacts associated with <?php echo $title;?></span>
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

	<!-- SYSTEM EVENT LOG -->
	<div id="tab3" class="tab" style="width: 800px; height: 520px;">
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
				{ id: 'tab1', caption: 'Server' },
				{ id: 'tab2', caption: 'Send Email' },
				{ id: 'tab3', caption: 'Log' }
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
		callAjaxSystem('get', "<?php echo $system_id; ?>");
		$('#tabs').w2tabs(config.tabs);
		$('#tab1').show();
		w2ui.tabs.click('tab1');
	});
</script>
</form>
</body>
</html>

