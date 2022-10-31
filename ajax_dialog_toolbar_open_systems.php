<?php
/**
 * ajax_dialog_toolbar_open_systems.php
 *
 * @package   PhpStorm
 * @file      ajax_dialog_toolbar_open_systems.php
 * @author    gparkin
 * @date      08/23/16
 * @version   7.0
 *
 * @brief     Called by ajax request from dialog_toolbar_open_systems.php
 * @brief     Performs the following operations:
 *            [get, refresh, activate, cancel, delete, freeze, unfreeze]
 *            action = get        Retrieve system record from cct7_systems
 *            action = refresh    Refresh data. (Same as get)
 *            action = approve    Override client approvals and approve work for this server.
 *            action = cancel     Cancel work activity for this server.
 *            action = reschedule Select the next available maintenance window for this server.
 *            action = reset      Reset this work activity to the original scheduled date and time.
 *            action = email      Send email message to all clients for this server.
 *            action = log        Write a log message for this server work activity.
 *
 * @brief     All operations are performed by class: cct7_systems.php
 *
 */

set_include_path("/opt/ibmtools/www/cct7/classes");

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
    require_once('classes/autoloader.php');
}

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

// Prevent caching.
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 01 Jan 1996 00:00:00 GMT');

// The JSON standard MIME header.
header('Content-type: application/json');

//
// NOTE: It is very important that you do not turn on debugging without writing to a file for server side AJAX code. Any HTML
//       comments coming from functions while writing JSON will show up in the JSON output and you will get a parsing error
//       in the client side program.
//
$lib = new library();        // classes/library.php
$lib->debug_start('ajax_dialog_toolbar_open_systems.html');
date_default_timezone_set('America/Denver');

$tic = new cct7_tickets();   // classes/cct7_tickets.php
$sys = new cct7_systems();   // classes/cct7_systems.php

// Read-only stream allows you to read raw data from the request body.
$input = json_decode(file_get_contents("php://input"));

//
// action values:
//   get             - Get ticket info from cct7_tickets and return the data.
//   approve         - Override client approvals/rejects and approve work for this server.
//   delete          - If ticket is in DRAFT mode, then we can delete the record.
//   cancel          - Cancel work request for this server if in ACTIVE mode.
//   reschedule      - Reschedule work to next available maintenance window.
//   reset_original  - Reset work schedule to original start, end times.
//   sendmail        - Spool email message to go out to all contacts for this server.
//   add_server      - Add a new server to $ticket_no.
//
$action          = '';
$system_id       = 0;
$log_entry       = '';
$email_cc        = '';
$email_bcc       = '';
$subject_line    = '';
$message_body    = '';

$work_start_date = '';
$work_end_date   = '';

$ticket_no       = '';
$hostname        = '';

if (isset($input->{'action'}))
    $action          = $input->{'action'};

if (isset($input->{'system_id'}))
    $system_id       = $input->{'system_id'};

if (isset($input->{'log_entry'}))
	$log_entry       = $input->{'log_entry'};

if (isset($input->{'email_cc'}))
	$email_cc        = $input->{'email_cc'};

if (isset($input->{'email_bcc'}))
	$email_bcc       = $input->{'email_bcc'};

if (isset($input->{'subject_line'}))
	$subject_line    = $input->{'subject_line'};

if (isset($input->{'message_body'}))
	$message_body    = $input->{'message_body'};

if (isset($input->{'work_start_date'}))
	$work_start_date = $input->{'work_start_date'};

if (isset($input->{'work_end_date'}))
	$work_end_date = $input->{'work_end_date'};

if (isset($input->{'ticket_no'}))
	$ticket_no       = $input->{'ticket_no'};

if (isset($input->{'hostname'}))
	$hostname        = strtolower($input->{'hostname'});

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "action = %s",          $action);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %s",       $system_id);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "log_entry = %s",       $log_entry);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "email_cc = %s",        $email_cc);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "email_bcc = %s",       $email_bcc);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "subject_line = %s",    $subject_line);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "message_body = %s",    $message_body);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "work_start_date = %s", $work_start_date);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "work_end_date = %s",   $work_end_date);

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s",       $ticket_no);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "hostname = %s",        $hostname);

$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "input: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $input);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_POST: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_POST);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_GET: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_GET);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_REQUEST: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_REQUEST);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_SERVER: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_SERVER);
$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "_SESSION: ");
$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_SESSION);

$json = array();

function get()
{
	global $lib, $tic, $sys, $system_id;

	//
	// Get the cct7_systems record for $system_id
	//
	if ($sys->getSystem($system_id) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	//
	// Get the cct7_tickets record for this $system_id's ticket_no
	//
	if ($tic->getTicket($sys->ticket_no) == false)
	{
		$json['ajax_status']  = 'REFRESH';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	$json['ajax_status']                  = 'SUCCESS';
	$json['ajax_message']                 = '';

	$json['ticket_no']                    = $tic->ticket_no;
	$json['ajax_status']                       = $tic->status;
	$json['work_activity']                = $tic->work_activity;

	$json['system_id']                    = $sys->system_id;
	$json['system_hostname']              = $sys->system_hostname;
	$json['system_os']                    = $sys->system_os;
	$json['system_usage']                 = $sys->system_usage;
	$json['system_location']              = $sys->system_location;
	$json['system_timezone_name']         = $sys->system_timezone_name;
	$json['system_osmaint_weekly']        = $sys->system_osmaint_weekly;
	$json['system_respond_by_date']       = $sys->system_respond_by_date_char;

	if ($sys->system_work_start_date_num == 0)
	{
		$json['system_work_start_date']       = "(See Remedy)";
		$json['system_work_end_date']         = "(See Remedy)";
		$json['system_work_duration']         = "(See Remedy)";
	}
	else
	{
		$json['system_work_start_date']       = $sys->system_work_start_date_char;
		$json['system_work_end_date']         = $sys->system_work_end_date_char;
		$json['system_work_duration']         = $sys->system_work_duration;
	}

	$json['system_work_status']           = $sys->system_work_status;
	$json['total_contacts_responded']     = $sys->total_contacts_responded;
	$json['total_contacts_not_responded'] = $sys->total_contacts_not_responded;

	$lib->debug1(  __FILE__, __FUNCTION__, __LINE__, "json: ");
	$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $json);

	$top = $sys->getLogSystem($system_id);
	$log_entries = '';

	for ($p=$top; $p!=null; $p=$p->next)
	{
		$hold = sprintf("%-17s %s\n%-17s %s\n",
						$p->event_date_char, $p->event_name,
						$p->event_type,      $p->event_message);

		if (strlen($log_entries) == 0)
		{
			$log_entries = $hold;
		}
		else
		{
			$log_entries .= "\n" . $hold;
		}
	}

	$json['log_entries'] = $log_entries;

	echo json_encode($json);

    exit();
}


if ($action == "approve_with_paging")
{
	if ($sys->approve_with_paging($system_id) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

if ($action == "approve_no_paging")
{
	if ($sys->approve_no_paging($system_id) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

if ($action == "reject")
{
	if ($sys->reject($system_id) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

if ($action == "exempt")
{
	if ($sys->exempt($system_id) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// Cancel work request for this server if in ACTIVE mode.
//
if ($action == "cancel")
{
	if ($sys->cancel($system_id) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// If ticket is in DRAFT mode, then we can delete the record.
//
if ($action == "delete")
{
	if ($sys->delete($system_id) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// Reschedule work server work start and end times. (Not Next OS Maintenance Window!)
//
if ($action == "reschedule")
{
	if ($sys->reschedule($system_id, $work_start_date, $work_end_date) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// Reset work schedule to original start, end times.
//
if ($action == "reset_original")
{
	if ($sys->resetOriginal($system_id) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// log - Log note
//
if ($action == "log")
{
	if ($sys->log($system_id, $log_entry) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// Send email to everyone for this ticket.
//
if ($action == "sendmail_ticket_owner")
{
	if ($sys->sendmailTicketOwner($system_id, $subject_line, $email_cc, $email_bcc, $message_body) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// Spool email message to go out to all contacts identified by $system_id
//
if ($action == "sendmail")
{
	if ($sys->sendmail($system_id, $subject_line, $email_cc, $email_bcc, $message_body, "N") == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

//
// Send email only to those who have not responded (where contact_response_status = 'WAITING')
//
if ($action == "sendmail_not_responded")
{
	if ($sys->sendmail($system_id, $subject_line, $email_cc, $email_bcc, $message_body, "Y") == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	get();
}

if ($action == "add_server")
{
	$ora = new oracle();

	/**
	 * Don't add the new hostname if it is already defined in this $ticket_no
	 */
	if ($sys->checkForSystem($ticket_no, $hostname) == true)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $sys->error;
		echo json_encode($json);
		exit();
	}

	/**
	 * Get the server information from cct7_computers (Asset Manager data).
	 */
	$query  = "select ";
	$query .= "  computer_lastid                       as system_lastid, ";         // 2348234
	$query .= "  computer_hostname                     as system_hostname, ";       // lxdnp44a
	$query .= "  computer_os_lite                      as system_os, ";             // Linux
	$query .= "  computer_status                       as system_usage, ";          // PRODUCTION
	$query .= "  computer_city ||', '|| computer_state as system_location, ";       // DENVER, CO
	$query .= "  upper(computer_city)                  as city, ";                  // DENVER
	$query .= "  upper(computer_state)                 as state, ";                 // CO
	$query .= "  computer_osmaint_weekly               as system_osmaint_weekly ";  // MON,TUE,WED,THU,FRI,SAT,SUN 2200 480
	$query .= "from ";
	$query .= "  cct7_computers ";
	$query .= "where ";
	$query .= "   computer_hostname = '" . $hostname . "'";

	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

	if ($ora->sql2($query) == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
		echo json_encode($json);
		exit();
	}

	if ($ora->fetch() == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = sprintf("Unable to find hostname %s in Asset Manager.", $hostname);
		echo json_encode($json);
		exit();
	}

	$system_lastid         = $ora->system_lastid;
	$system_hostname       = $ora->system_hostname;
	$system_os             = $ora->system_os;
	$system_usage          = $ora->system_usage;
	$system_location       = $ora->system_location;
	$system_osmaint_weekly = $ora->system_osmaint_weekly;
	$system_timezone_name  = 'America/Denver';  // Default
	$city                  = $ora->city;
	$state                 = $ora->state;

	/**
	 * Set a default OS maintenance window if this server does not already have one.
	 */
	if (strlen($system_osmaint_weekly) == 0)
		$system_osmaint_weekly = '+0+TUE,THU+0200+180'; // Default: TUE,THU 02:00 180

	/**
	 * Get the Server's timezone information
	 */
	$query  = "select ";
	$query .= "  timezone ";
	$query .= "from ";
	$query .= "  timezone ";
	$query .= "where ";
	$query .= "  upper(country) = 'US' and ";
	$query .= "  upper(city)    = '" . $city . "' and ";
	$query .= "  upper(state)   = '" . $state . "'";

	$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

	if ($ora->sql2($query) == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
		echo json_encode($json);
		exit();
	}

	if ($ora->fetch())
	{
		$system_timezone_name = $ora->timezone;
	}

	/**
	 * Get current ticket information for $ticket_no
	 */
	if ($tic->getTicket($ticket_no) == false)
	{
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = $tic->error;
		echo json_encode($json);
		exit();
	}

	if ($tic->disable_scheduler == "N")
	{
		/**
		 * Step 4 Get a list of future scheduled events for this server (if any).
		 *        This is for conflict analysis that is done in maintwin_scheduler.php, $obj->ComputeStart()
		 */
		$scheduled_starts = array();
		$scheduled_ends   = array();

		$query  = "select ";
		$query .= "  system_work_start_date, ";
		$query .= "  system_work_end_date ";
		$query .= "from ";
		$query .= "  cct7_systems ";
		$query .= "where ";
		$query .= "  system_hostname = '" . $hostname . "' and ";
		$query .= "  system_work_start_date > " . $lib->now_to_gmt_utime();

		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

		if ($ora->sql2($query) == false)
		{
			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = sprintf("%s - %s", $ora->sql_statement, $ora->dbErrMsg);
			echo json_encode($json);
			exit();
		}

		while ($ora->fetch())
		{
			array_push($scheduled_starts,  $ora->system_work_start_date);
			array_push($scheduled_ends,    $ora->system_work_end_date);
		}

		/**
		 * Figure out if we can use the work starting date from the ticket or if we need to
		 * compute a new one.
		 */
		$dt = new datetime();
		$dt->setTimezone(new DateTimeZone($system_timezone_name));
		$dt->setTimestamp(time());

		if ($tic->schedule_start_date_num >= $dt->format('U'))
		{
			$system_respond_by_date = $tic->schedule_start_date_num;
			$start_date = $tic->schedule_start_date_char;
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Using ticket schedule_start_date_num for system_respond_by_date/start_date");
		}
		else
		{
			$system_respond_by_date = $dt->format('U');
			$start_date = $dt->format('m/d/Y');
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Using current datetime for system_respond_by_date/start_date");
		}

		//
		// Compute a starting date based upon this servers OS maintenance window
		//
		$scheduler = new scheduler();

		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "schedule_starts:");
		$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $scheduled_starts);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "schedule_ends:");
		$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $scheduled_ends);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "start_date: %s", $start_date);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_osmaint_weekly: %s", $system_osmaint_weekly);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_timezone_name: %s", $system_timezone_name);

		if ($scheduler->ComputeStart(
				$scheduled_starts,             // $scheduled_starts=array(),
				$scheduled_ends,               // $scheduled_ends=array(),
				$start_date,                   // $schedule_starting_date,
				$system_osmaint_weekly,        // $maintenance_window,
				$system_timezone_name          // $system_timezone_name='America/Denver')
			) == false)
		{
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $scheduler->error);
			$json['ajax_status']  = 'FAILED';
			$json['ajax_message'] = sprintf("Scheduler error: %s (%s, line: %s). Please contact Greg Parkin",
											$scheduler->error, __FILE__, __LINE__);
			echo json_encode($json);
			exit();
		}

		$sys->system_work_start_date_num  = $scheduler->system_work_start_date_num;
		$sys->system_work_start_date_char = $scheduler->system_work_start_date_char;
		$sys->system_work_end_date_num    = $scheduler->system_work_end_date_num;
		$sys->system_work_end_date_char   = $scheduler->system_work_end_date_char;
		$sys->system_work_duration        = $scheduler->system_work_duration;
	}
	else
	{
		// Disable Scheduler == "Y"
		$sys->system_work_start_date_num  = 0;
		$sys->system_work_start_date_char = "";
		$sys->system_work_end_date_num    = 0;
		$sys->system_work_end_date_char   = "";
		$sys->system_work_duration        = "";
	}

	$sys->system_lastid               = $system_lastid;
	$sys->system_hostname             = $system_hostname;
	$sys->system_os                   = $system_os;
	$sys->system_usage                = $system_usage;
	$sys->system_location             = $system_location;
	$sys->system_timezone_name        = $system_timezone_name;
	$sys->system_osmaint_weekly       = $system_osmaint_weekly;
	$sys->system_respond_by_date_num  = $system_respond_by_date;

	/**
	 * Step 5. Create the new record for the server in cct7_systems and link it back to cct7_tickets ($ticket_no).
	 */
	$system_id = $sys->addSystem($ticket_no);

	if ($system_id == 0)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $sys->error);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = sprintf("Program error: %s (%s, line: %s). Please contact Greg Parkin",
								   $sys->error, __FILE__, __LINE__);
		echo json_encode($json);
		exit();
	}

	/**
	 * Step 6. Go get all the contact information and build cct7_contacts records for each server.
	 */
	$contacts = new cct7_contacts();

	if ($contacts->saveContacts($system_id, $system_lastid, $tic->reboot_required,
								$tic->approvals_required, $system_respond_by_date) == false)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $contacts->error);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = sprintf("Program error: %s (%s, line: %s). Please contact Greg Parkin",
								   $contacts->error, __FILE__, __LINE__);
		echo json_encode($json);
		exit();
	}

	/**
	 * Step 7. Set all the status fields in cc7_contacts, cct7_systems and cct7_tickets based upon approvals_required.
	 */
	if ($sys->setSystemStatus($system_id) == false)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $sys->error);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = sprintf("Program error: %s (%s, line: %s). Please contact Greg Parkin",
								   $sys->error, __FILE__, __LINE__);
		echo json_encode($json);
		exit();
	}

	/**
	 * Step 8. Update all the status values for this ticket.
	 */
	if ($lib->updateAllStatuses($ora, $ticket_no) == false)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $lib->error);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = sprintf("Program error: %s (%s, line: %s). Please contact Greg Parkin",
								   $lib->error, __FILE__, __LINE__);
		echo json_encode($json);
		exit();
	}

	//
	// Run updateScheduleDates()
	//
	if ($tic->updateScheduleDates($ticket_no) == false)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $lib->error);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = sprintf("Program error: %s (%s, line: %s). Please contact Greg Parkin",
								   $tic->error, __FILE__, __LINE__);
		echo json_encode($json);
		exit();
	}

	//
	// Retrieve the record we have just added.
	//
	if ($sys->getSystem($system_id) == false)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $sys->error);
		$json['ajax_status']  = 'FAILED';
		$json['ajax_message'] = sprintf("Program error: %s (%s, line: %s). Please contact Greg Parkin",
								   $sys->error, __FILE__, __LINE__);
		echo json_encode($json);
		exit();
	}

	//
	// JSON data returned is in the format needed for the jqgrid('addrowdata',...) function.
	//
	printf("{\n");
	printf("\"ajax_status\":    \"SUCCESS\",\n");
	printf("\"ajax_message\":   \"\",\n");
	printf("\"row\": [\n");    // Array of data being returned

	/**
	 * Return the data just entered back to the client.
	 */
	$json['ticket_no']                    = $ticket_no;                         // hidden column (ticket_no)
	$json['system_id']                    = $system_id;                         // hidden column (system_id) (key)
	$json['system_hostname']              = $sys->system_hostname;              // Server
	$json['system_os']                    = $sys->system_os;                    // OS
	$json['system_usage']                 = $sys->system_usage;                 // Usage
	$json['system_location']              = $sys->system_location;              // Location
	$json['system_timezone_name']         = $sys->system_timezone_name;         // Time Zone
	$json['system_work_status']           = $sys->system_work_status;           // Work Status
	$json['total_contacts_responded']     = $sys->total_contacts_responded;     // # Responded
	$json['total_contacts_not_responded'] = $sys->total_contacts_not_responded; // # Not Responded
	$json['system_respond_by_date']       = $sys->system_respond_by_date_char;  // Respond By
	$json['system_work_start_date']       = $sys->system_work_start_date_char;  // Work Start
	$json['system_work_end_date']         = $sys->system_work_end_date_char;    // Work End
	$json['system_work_duration']         = $sys->system_work_duration;         // Work Duration
	$json['system_osmaint_weekly']        = $system_osmaint_weekly;             // Maintenance Window

	echo json_encode($json);
	printf("]}\n");  // Close out the data stream

	exit();
}

get();


