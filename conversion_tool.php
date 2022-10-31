#!/opt/lampp/bin/php -a
<?php
/**
 * conversion_tool.php
 *
 * @package   PhpStorm
 * @file      conversion_tool.php
 * @author    gparkin
 * @date      02/23/2017
 * @version   7.0
 *
 * @brief     Used to copy CCT6 data into new CCT7 Oracle tables.
 */

ini_set("error_reporting",        "E_ALL & ~E_DEPRECATED & ~E_STRICT");
ini_set("log_errors",             1);
ini_set("error_log",              "/opt/ibmtools/cct7/logs/php-error.log");
ini_set("log_errors_max_len",     0);
ini_set("report_memleaks",        1);
ini_set("track_errors",           1);
ini_set("html_errors",            1);
ini_set("ignore_repeated_errors", 0);
ini_set("ignore_repeated_source", 0);

ini_set('xdebug.collect_vars',    '5');
ini_set('xdebug.collect_vars',    'on');
ini_set('xdebug.collect_params',  '4');
ini_set('xdebug.dump_globals',    'on');
ini_set('xdebug.dump.SERVER',     'REQUEST_URI');
ini_set('xdebug.show_local_vars', 'on');

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

$lib = new library();  // classes/library.php
$lib->debug_start('conversion_tool.html');
date_default_timezone_set('America/Denver');

$ora1 = new oracle();
$ora2 = new oracle();
$ora3 = new oracle();

function make_system_lists()
{
	global $lib, $ora1, $ora2;

	// list_name_id|NUMBER|0|NOT NULL|Unique record ID
	// insert_date|DATE|7||Date record was inserted
	// insert_cuid|VARCHAR2|20||CUID of person inserting the record
	// insert_name|VARCHAR2|200||Name of person inserting the record
	// list_name|VARCHAR2|200||Name of the system list
	// group_name|VARCHAR2|200||Remedy Assign Group owner belonging to

	$ora1->sql2("delete from cct7_list_names");
	$ora1->sql2("delete from cct7_list_systems");

	$query  = "select ";
	$query .= "  list_name_id, ";
	$query .= "  to_char(insert_date, 'MM/DD/YYYY HH24:MI') as insert_date, ";
	$query .= "  insert_cuid, ";
	$query .= "  insert_name, ";
	$query .= "  list_name ";
	$query .= "from ";
	$query .= "  cct6_list_names";

	if ($ora1->sql2($query) == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit();
	}

	while ($ora1->fetch())
	{
		$insert_date = returnGMT($ora1->insert_date);

		// list_name_id|NUMBER|0|NOT NULL|Unique record ID
		// create_date|NUMBER|0||Date record was inserted (GMT timestamp)
		// owner_cuid|VARCHAR2|20||CUID of person inserting the record
		// owner_name|VARCHAR2|200||Name of person inserting the record
		// list_name|VARCHAR2|200||Name of the system list

		$rc = $ora2->insert("cct7_list_names")
				   ->column("list_name_id")
				   ->column("create_date")
				   ->column("owner_cuid")
				   ->column("owner_name")
				   ->column("list_name")
				   ->value("int",  $ora1->list_name_id)
				   ->value("int",  $insert_date)
				   ->value("char", $ora1->insert_cuid)
				   ->value("char", $ora1->insert_name)
				   ->value("char", $ora1->list_name)
				   ->execute();

		if ($rc == false)
		{
			printf("Unable to create cct7_list_names record for list_name_id = %d\n", $ora1->list_name_id);
			exit();
		}

		printf("%d - %s\n", $ora1->list_name_id, $ora1->list_name);
	}

	$ora2->commit();

	// TABLE: cct6_list_systems
	//
	// list_system_id|NUMBER|0|NOT NULL|Unique record ID
	// list_name_id|NUMBER|0||
	// insert_date|DATE|7||Date record was inserted
	// insert_cuid|VARCHAR2|20||CUID of person inserting the record
	// insert_name|VARCHAR2|200||Name of person inserting the record
	// computer_hostname|VARCHAR2|255||Computer hostname
	// computer_ip_address|VARCHAR2|64||Computer IP address
	// computer_os_lite|VARCHAR2|20||Computer short OS Name: HPUX
	// computer_status|VARCHAR2|80||Computer status: PRODUCTION, TEST, DEVELOPMENT
	// computer_managing_group|VARCHAR2|40||Managing group like: CMP-UNIX

	$query  = "select ";
	$query .= "  list_system_id, ";
	$query .= "  list_name_id, ";
	$query .= "  to_char(insert_date, 'MM/DD/YYYY HH24:MI') as insert_date, ";
	$query .= "  insert_cuid, ";
	$query .= "  insert_name, ";
	$query .= "  computer_hostname, ";
	$query .= "  computer_ip_address, ";
	$query .= "  computer_os_lite, ";
	$query .= "  computer_status, ";
	$query .= "  computer_managing_group ";
	$query .= "from ";
	$query .= "  cct6_list_systems";

	if ($ora1->sql2($query) == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit();
	}

	while ($ora1->fetch())
	{
		$insert_date = returnGMT($ora1->insert_date);

		// TABLE: cct7_list_systems
		//
		// list_system_id|NUMBER|0|NOT NULL|Unique record ID
		// list_name_id|NUMBER|0||
		// create_date|NUMBER|0||Date record was created. (GMT unix timestamp)
		// owner_cuid|VARCHAR2|20||CUID of person inserting the record
		// owner_name|VARCHAR2|200||Name of person inserting the record
		// computer_hostname|VARCHAR2|255||Computer hostname
		// computer_ip_address|VARCHAR2|64||Computer IP address
		// computer_os_lite|VARCHAR2|20||Computer short OS Name: HPUX
		// computer_status|VARCHAR2|80||Computer status: PRODUCTION, TEST, DEVELOPMENT
		// computer_managing_group|VARCHAR2|40||Managing group like: CMP-UNIX (shorter version of COMPUTER_CIO_GROUP)

		$rc = $ora2->insert("cct7_list_systems")
				   ->column("list_system_id")
				   ->column("list_name_id")
				   ->column("create_date")
				   ->column("owner_cuid")
				   ->column("owner_name")
				   ->column("computer_hostname")
				   ->column("computer_ip_address")
				   ->column("computer_os_lite")
				   ->column("computer_status")
				   ->column("computer_managing_group")
				   ->value("int",  $ora1->list_system_id)
				   ->value("int",  $ora1->list_name_id)
				   ->value("int",  $insert_date)
				   ->value("char", $ora1->insert_cuid)
				   ->value("char", $ora1->insert_name)
				   ->value("char", $ora1->computer_hostname)
				   ->value("char", $ora1->computer_ip_address)
				   ->value("char", $ora1->computer_os_lite)
				   ->value("char", $ora1->computer_status)
				   ->value("char", $ora1->computer_managing_group)
				   ->execute();

		if ($rc == false)
		{
			printf("Unable to create cct7_list_names record for list_name_id = %d\n", $ora1->list_name_id);
			exit();
		}
	}

	//
	// Reset the sequence number.
	//
	if ($ora1->sql2("select list_name_id from cct7_list_names order by list_name_id desc") == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit();
	}

	$next_list_name_id = $ora1->fetch() + 1;

	if ($ora1->sql2("select list_system_id from cct7_list_systems order by list_system_id desc") == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit();
	}

	$next_list_system_id = $ora1->fetch() + 1;

	if ($ora1->sql2("drop sequence cct7_list_namesseq") == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit();
	}

	if ($ora1->sql2("create sequence cct7_list_namesseq increment by 1 start with " . $next_list_name_id . " nocache") == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit();
	}


	if ($ora1->sql2("drop sequence cct7_list_systemsseq") == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit();
	}

	if ($ora1->sql2("create sequence cct7_list_systemsseq increment by 1 start with " . $next_list_system_id . " nocache") == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit();
	}

	$ora1->commit();
}

function make_cct7_tickets()
{
	global $lib, $ora1, $ora2;

	printf("Deleting old records from cct7_tickets\n");

	if ($ora1->sql2("delete from cct7_tickets") == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	$ora1->commit();

	$dt            = new DateTime();
	$mmddyyyy_hhmm = sprintf("%s 00:00", $dt->format('m/d/Y'));
	$dt            = new DateTime($mmddyyyy_hhmm, new DateTimeZone("America/Denver"));
	$dt->setTimezone(new DateTimeZone('Europe/London'));
	$today = $dt->format('U');

	printf("Today: %s (%d)\n", $mmddyyyy_hhmm, $today);

	$query = "select ";
	$query .= "  * ";
	$query .= "from ";
	$query .= "  cct6_tickets ";
	$query .= "where ";
	$query .= "  cm_ticket_no like 'CM%' and cm_start_date >= to_date('01/01/2016 00:00', 'MM/DD/YYYY HH24:MI')";

	if ($ora1->sql2($query) == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	while ($ora1->fetch())
	{
		$ticket_id = $ora2->next_seq('cct7_ticketsseq');

		$ticket_no = sprintf("CCT7%08d", $ticket_id);

		$insert_date = returnGMT($ora1->ticket_insert_date);
		$update_date = returnGMT($ora1->ticket_update_date);

		$resp_esc1_date = returnGMT($ora1->ticket_resp_esc1_date);
		$resp_esc2_date = returnGMT($ora1->ticket_resp_esc2_date);
		$resp_esc3_date = returnGMT($ora1->ticket_resp_esc3_date);  // Also the respond by date

		$close_date = returnGMT($ora1->cm_close_date);

		$start_date = returnGMT($ora1->cm_start_date);
		$end_date   = returnGMT($ora1->cm_end_date);

		$status = "ACTIVE";

		if (strlen($ora1->cm_close_date) > 0 || $today > $end_date)
			$status = "CLOSED";

		if (strlen($ora1->cm_cancel_date) > 0)
			$status = "CANCELED";

		switch ($ora1->cm_close_code)
		{
			case "Backed Out":
			case "Caused Outage":
			case "Caused Outage - Backed Out":
				$status = "FAILED";
				break;
			case "Cancelled":
				$status = "CANCELED";
				break;
			default:
				if (strlen($ora1->cm_close_code) > 0)
				{
					$status = "CLOSED";
				}
				break;
		}

		if ($ora1->classification == 'GSD331 Remediation' || $ora1->classification == 'GSD331 Remedication')
			$classification = "Remediation";
		else if (strlen($ora1->classification) == 0)
			$classification = "Unknown";
		else
			$classification = $ora1->classification;

		if ($ora1->ticket_approvals_required == '')
			$ora1->ticket_approvals_required = 'Y';

		if ($ora1->cm_ipl_boot == '')
			$ora1->cm_ipl_boot = 'Y';

		//
		// Construct the SQL to insert the first part of the ticket
		//
		$rc = $ora2
			->insert("cct7_tickets")
			->column("ticket_no")
			->column("insert_date")
			->column("insert_cuid")
			->column("insert_name")
			->column("update_date")
			->column("update_cuid")
			->column("update_name")
			->column("status")
			->column("owner_cuid")
			->column("owner_first_name")
			->column("owner_name")
			->column("owner_email")
			->column("manager_cuid")
			->column("manager_first_name")
			->column("manager_name")
			->column("manager_email")
			->column("work_activity")
			->column("approvals_required")
			->column("reboot_required")
			->column("respond_by_date")
			->column("email_reminder1_date")
			->column("email_reminder2_date")
			->column("email_reminder3_date")
			->column("remedy_cm_start_date")
			->column("remedy_cm_end_date")
			->column("work_description")
			->column("work_implementation")
			->column("work_backoff_plan")
			->column("work_business_reason")
			->column("work_user_impact")
			->column("cm_ticket_no")
			->column("csc_banner1")
			->column("csc_banner2")
			->column("csc_banner3")
			->column("csc_banner4")
			->column("csc_banner5")
			->column("csc_banner6")
			->column("csc_banner7")
			->column("csc_banner8")
			->column("csc_banner9")
			->column("csc_banner10")
			->value("char", $ticket_no)// ticket_no
			->value("int", $insert_date)// insert_date
			->value("char", $ora1->ticket_insert_cuid)// insert_cuid
			->value("char", $ora1->ticket_insert_name)// insert_name
			->value("int", $update_date)// update_date
			->value("char", $ora1->ticket_update_cuid)// update_cuid
			->value("char", $ora1->ticket_insert_name)// update_name
			->value("char", $status)// status
			->value("char", $ora1->ticket_contact_cuid)// owner_cuid
			->value("char", $ora1->ticket_contact_first_name)// owner_first_name
			->value("char", $ora1->ticket_contact_name)// owner_name
			->value("char", $ora1->ticket_contact_email)// owner_email
			->value("char", $ora1->ticket_manager_cuid)// manager_cuid
			->value("char", $ora1->ticket_manager_first_name)// manager_first_name
			->value("char", $ora1->ticket_manager_name)// manager_name
			->value("char", $ora1->ticket_manager_email)// manager_email
			->value("char", $classification)// work_activity
			->value("char", $ora1->ticket_approvals_required)// approvals_required
			->value("char", $ora1->cm_ipl_boot)// reboot_required
			->value("int", $resp_esc1_date)// respond_by_date
			->value("int", $resp_esc1_date)// email_reminder1_date
			->value("int", $resp_esc1_date)// email_reminder2_date
			->value("int", $resp_esc1_date)// email_reminder3_date
			->value("int", $start_date)// schedule_start_date
			->value("int", $end_date)// schedule_end_date
			->value("char", $ora1->cm_description)// work_description
			->value("char", $ora1->cm_implementation_instructions)// work_implementation
			->value("char", $ora1->cm_backoff_plan)// work_backoff_plan
			->value("char", $ora1->cm_business_reason)// work_business_reason
			->value("char", $ora1->cm_impact)// work_user_impact
			->value("char", $ora1->cm_ticket_no)// cm_ticket_no
			->value("char", 'Y')
			->value("char", 'Y')
			->value("char", 'Y')
			->value("char", 'Y')
			->value("char", 'Y')
			->value("char", 'Y')
			->value("char", 'Y')
			->value("char", 'Y')
			->value("char", 'Y')
			->value("char", 'Y')
			->execute();

		if ($rc == false)
		{
			printf("Unable to create ticket: %s for %s\n", $ticket_no, $ora1->cm_ticket_no);
			exit();
		}

		printf("Added: %s for %s\n", $ticket_no, $ora1->cm_ticket_no);
	}

	$ora2->commit();
}

function make_cct7_systems()
{
	global $lib, $ora1, $ora2, $ora3;

	//
	// Step 2 - Create the cct7_systems records
	//
	$query  = "select ";
	$query .= "  s.system_id                      as cct6_system_id, ";                 // Lookup cct6_event_log info
	$query .= "  s.system_insert_cuid             as system_insert_cuid, ";
	$query .= "  s.system_insert_name             as system_insert_name, ";
	$query .= "  s.system_insert_date             as system_lastid, ";
	$query .= "  s.system_update_cuid             as system_lastid, ";
	$query .= "  s.system_update_name             as system_lastid, ";
	$query .= "  s.system_update_date             as system_lastid, ";
	$query .= "  t.ticket_no                      as ticket_no, ";
	$query .= "  t.cm_ticket_no                   as cm_ticket_no, ";
	$query .= "  s.computer_lastid                as system_lastid, ";
	$query .= "  s.computer_hostname              as system_hostname, ";
	$query .= "  s.computer_os_lite               as system_os, ";
	$query .= "  s.computer_status                as system_usage, ";
	$query .= "  s.computer_city                  as system_location, ";
	$query .= "  s.computer_timezone              as system_timezone_name, ";           // MST, MDT, CST, CDT, etc.
	$query .= "  s.computer_osmaint_weekly        as system_osmaint_weekly, ";
	$query .= "  t.respond_by_date                as system_respond_by_date, ";
	$query .= "  s.system_actual_work_start       as system_work_start_date, ";
	$query .= "  s.system_actual_work_end         as system_work_end_date, ";
	$query .= "  s.system_actual_work_duration    as system_work_duration, ";
	$query .= "  s.system_work_status             as system_work_status, ";
	$query .= "  s.system_original_work_start     as system_original_work_start, ";
	$query .= "  s.system_original_work_end       as system_original_work_end, ";
	$query .= "  s.system_original_work_duration  as system_original_work_duration, ";
	$query .= "  s.system_override_status_date    as system_override_status_date, ";    // Not in cct7_systems
	$query .= "  s.system_override_status_cuid    as system_override_status_cuid, ";    // Not in cct7_systems
	$query .= "  s.system_override_status_name    as system_override_status_name, ";    // Not in cct7_systems
	$query .= "  s.system_override_status_notes   as system_override_status_notes, ";   // Not in cct7_systems
	$query .= "  s.system_completion_date         as system_completion_date, ";         // Not in cct7_systems
	$query .= "  s.system_completion_status       as system_completion_status, ";       // Not in cct7_systems
	$query .= "  s.system_completion_cuid         as system_completion_cuid, ";         // Not in cct7_systems
	$query .= "  s.system_completion_name         as system_completion_name, ";         // Not in cct7_systems
	$query .= "  s.system_completion_notes        as system_completion_notes ";         // Not in cct7_systems
	$query .= "from ";
	$query .= "  cct7_tickets t, ";
	$query .= "  cct6_systems s ";
	$query .= "where ";
	$query .= "  s.cm_ticket_no = t.cm_ticket_no";

	if ($ora1->sql2($query) == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	$high = 100;
	$count = 0;

	while ($ora1->fetch())
	{
		++$count;

		if ($count >= $high)
		{
			$high += 100;
			printf("cct7_systems total inserts = %d\n", $count);
		}

		$cct6_system_id           = $ora1->cct6_system_id;

		$system_insert_date       = returnGMT($ora1->system_insert_date);
		$system_update_date       = returnGMT($ora1->system_update_date);

		$system_work_start_date   = returnGMT($ora1->system_work_start_date);
		$system_work_end_date     = returnGMT($ora1->system_work_end_date);

		$original_work_start_date = returnGMT($ora1->original_work_start_date);
		$original_work_end_date   = returnGMT($ora1->original_work_end_date);

		// Use the following to create log entries as needed.
		//
		// cct6_systems
		// system_override_status_date|DATE|7||Date Final System Status Lock was applied
		// system_override_status_cuid|VARCHAR2|20||CUID of person who initiated Final System Status Lock
		// system_override_status_name|VARCHAR2|200||Name of person who initiated Final System Status Lock
		// system_override_status_notes|VARCHAR2|4000||Notes of person who initiated Final System Status Lock
		// system_completion_date|DATE|7||Date of Page/Email final completion notice to contacts
		// system_completion_status|VARCHAR2|20||Status of completion notice: SUCCESS, FAILURE
		// system_completion_cuid|VARCHAR2|20||CUID of Page/Email person who recorded completion status
		// system_completion_name|VARCHAR2|200||Name of Page/Email person who recorded completion status
		// system_completion_notes|VARCHAR2|4000||Completion notes

		switch ( $ora1->system_timezone_name )
		{
			case "MST":
				$system_timezone_name = "America/Denver";
				break;
			case "IST":
				$system_timezone_name = "Eurpope/London";
				break;
			case "MST-AZ":
				$system_timezone_name = "America/Denver";
				break;
			case "GMT":
				$system_timezone_name = "Eurpope/London";
				break;
			case "HST":
				$system_timezone_name = "Pacific/Honolulu";
				break;
			case "CST":
				$system_timezone_name = "America/Chicago";
				break;
			case "EST":
				$system_timezone_name = "America/New_York";
				break;
			case "PST":
				$system_timezone_name = "America/Los Angeles";
				break;
			default:
				$system_timezone_name = "America/Denver";
				break;
		}

		$system_id = $ora2->next_seq('cct7_systemsseq');

		// $query .= "  t.ticket_no                    as ticket_no, ";
		// $query .= "  t.cm_ticket_no                 as cm_ticket_no, ";

		// printf("ticket_no = %s, cm_ticket_no = %s\n", $ora1->ticket_no, $ora1->cm_ticket_no);

		// WAITING, READY, REJECTED, CANCELED

		// PENDING          -> WAITING
		// REJECTED         -> REJECTED
		// READY-SUCCESS    -> SUCCESS
		// READY-OTHER      -> UNKNOWN
		// READY-FAILURE    -> FAILED
		// READY-CANCEL     -> CANCELED
		// READY-STARTING   -> STARTING
		// READY            -> APPROVED
		// CANCELED         -> CANCELED
		// READY-BACKOUT    -> BACKOUT
		// WAITING          -> WAITING
		// RESCHEDULE       -> REJECTED

		switch ( $ora1->system_work_status )
		{
			case "PENDING":
				$system_work_status = "WAITING";
				break;
			case "REJECTED":
				$system_work_status = "REJECTED";
				break;
			case "READY-SUCCESS":
				$system_work_status = "SUCCESS";
				break;
			case "READY-OTHER":
				$system_work_status = "UNKNOWN";
				break;
			case "READY-FAILURE":
				$system_work_status = "FAILED";
				break;
			case "READY-CANCEL":
				$system_work_status = "CANCELED";
				break;
			case "READY-STARTING":
				$system_work_status = "STARTING";
				break;
			case "READY":
				$system_work_status = "APPROVED";
				break;
			case "CANCELED":
				$system_work_status = "CANCELED";
				break;
			case "READY-BACKOUT":
				$system_work_status = "BACKOUT";
				break;
			case "WAITING":
				$system_work_status = "WAITING";
				break;
			case "RESCHEDULE":
				$system_work_status = "REJECTED";
				break;
			default:
				$system_work_status = "WAITING";
				break;
		}

		// cct7_systems
		// system_id                    |NUMBER|0|NOT NULL|PK: Unique record ID
		// ticket_no                    |VARCHAR2|20|NOT NULL|FK: Link to cct7_tickets record
		// system_insert_date           |NUMBER|0||GMT UNIX TIME - Date of person who created this record
		// system_insert_cuid           |VARCHAR2|20||CUID of person who created this record
		// system_insert_name           |VARCHAR2|200||Name of person who created this record
		// system_update_date           |NUMBER|0||GMT UNIX TIME - Date of person who updated this record
		// system_update_cuid           |VARCHAR2|20||CUID of person who updated this record
		// system_update_name           |VARCHAR2|200||Name of person who updated this record
		// system_lastid                |computer_lastid              |NUMBER|0||233494988
		// system_hostname              |computer_hostname            |VARCHAR2|255||hvdnp16e
		// system_os                    |computer_os_lite             |VARCHAR2|20||HPUX
		// system_usage                 |computer_status              |VARCHAR2|80||PRODUCTION
		// system_location              |computer_city                |VARCHAR2|80||DENVER
		// system_timezone_name         |computer_timezone            |VARCHAR2|200||(i.e. America/Chicago
		// system_osmaint_weekly        |computer_osmaint_weekly      |VARCHAR2|4000||MON,TUE,WED,THU,FRI,SAT,SUN 2200 480
		// system_respond_by_date       |respond_by_date              |NUMBER|0||Copied over from cct7_tickets.respond_by_date
		// system_work_start_date       |system_actual_work_start     |NUMBER|0||GMT UNIX TIME - Actual computed work start datetime
		// system_work_end_date         |system_actual_work_end       |NUMBER|0||GMT UNIX TIME - Actual computed work end datetime
		// system_work_duration         |system_actual_work_duration  |VARCHAR2|30||Actual computed work duration window
		// system_work_status           |system_work_status           |VARCHAR2|20||WAITING, READY, REJECTED, CANCELED
		// total_contacts_responded     |                             |NUMBER|0||Total number of contacts who have responded
		// total_contacts_not_responded |                             |NUMBER|0||Total number of contacts who have NOT responded
		// original_work_start_date     |system_original_work_start   |NUMBER|0||Original scheduled work start date
		// original_work_end_date       |system_original_work_end     |NUMBER|0||Original scheduled work end date
		// original_work_duration       |system_original_work_duration|VARCHAR2|30||Original schedule work duration

		$rc = $ora2
			->insert("cct7_systems")
			->column("system_id")
			->column("ticket_no")
			->column("system_insert_date")
			->column("system_insert_cuid")
			->column("system_insert_name")
			->column("system_update_date")
			->column("system_update_cuid")
			->column("system_update_name")
			->column("system_lastid")
			->column("system_hostname")
			->column("system_os")
			->column("system_usage")
			->column("system_location")
			->column("system_timezone_name")
			->column("system_osmaint_weekly")
			->column("system_respond_by_date")
			->column("system_work_start_date")
			->column("system_work_end_date")
			->column("system_work_duration")
			->column("system_work_status")
			->column("original_work_start_date")
			->column("original_work_end_date")
			->column("original_work_duration")
			->value("int",   $system_id)                            // system_id
			->value("char",  $ora1->ticket_no)                      // ticket_no
			->value("int",   $system_insert_date)                   // system_insert_date
			->value("char",  $ora1->system_insert_cuid)             // system_insert_cuid
			->value("char",  $ora1->system_insert_name)             // system_insert_name
			->value("int",   $system_update_date)                   // system_update_date
			->value("char",  $ora1->system_update_cuid)             // system_update_cuid
			->value("char",  $ora1->system_update_name)             // system_update_name
			->value("int",   $ora1->system_lastid)                  // system_lastid
			->value("char",  $ora1->system_hostname)                // system_hostname
			->value("char",  $ora1->system_os)                      // system_os
			->value("char",  $ora1->system_usage)                   // system_usage
			->value("char",  $ora1->system_location)                // system_location
			->value("char",  $system_timezone_name)                 // system_timezone_name
			->value("char",  $ora1->system_osmaint_weekly)          // system_osmaint_weekly
			->value("int",   $ora1->system_respond_by_date)         // system_respond_by_date
			->value("int",   $system_work_start_date)               // system_work_start_date
			->value("int",   $system_work_end_date)                 // system_work_end_date
			->value("char",  $ora1->system_work_duration)           // system_work_duration
			->value("char",  $system_work_status)                   // system_work_status
			->value("int",   $system_work_start_date)               // original_work_start_date
			->value("int",   $system_work_end_date)                 // original_work_end_date
			->value("char",  $ora1->system_work_duration)           // original_work_duration
			->execute();

		if ($rc == false)
		{
			printf("Unable to create ticket: %s for %s\n", $ora1->ticket_no, $ora1->cm_ticket_no);
			exit();
		}

		$ora2->commit();
	}

	printf("cct7_systems total inserts = %d\n", $count);

	$ora2->commit();
}

function make_cct7_contacts()
{
	global $lib, $ora1, $ora2;

	printf("Building contacts for each server\n");

	/** @fn    saveContacts($system_id, $lastid, $reboot, $approvals_required, $system_respond_by_date_num)
	 *
	 *  @brief Find the contact and connection information for this $lastid. Create the cct7_contacts records.
	 *
	 *  @param int    $system_id                 is the cct7_systems.system_id number.
	 *  @parma int    $lastid                    is the asset manager LASTID number for the hostname record.
	 *  @parma string $reboot                    is the Y or N to indicate if this server needs to be rebooted.
	 *  @param string $approvals_required        is Y or N to indicate whether contacts need to approve this work.
	 *  @param int    $system_repond_by_date_num is GMT time copied over from cct7_tickets.php
	 *
	 *  @return true or false where true means success.
	 */
	// public function saveContacts($system_id, $lastid, $reboot, $approvals_required, $system_respond_by_date_num)

    $ora1->sql2("select count(*) as total from cct7_systems");
    $ora1->fetch();
    $total = $ora1->total;

	$query  = "select ";
	$query .= "  t.ticket_no, ";
	$query .= "  t.approvals_required, ";
	$query .= "  t.reboot_required, ";
	$query .= "  t.respond_by_date, ";
	$query .= "  t.exclude_virtual_contacts, ";
	$query .= "  s.system_id, ";
	$query .= "  s.system_lastid, ";
	$query .= "  s.system_hostname ";
	$query .= "from ";
	$query .= "  cct7_tickets t, ";
	$query .= "  cct7_systems s ";
	$query .= "where ";
	$query .= "  s.ticket_no = t.ticket_no";

	$con = new cct7_contacts();

	if ($ora1->sql2($query) == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	$count = 0;

	while ($ora1->fetch())
	{
	    $count += 1;

		$approvals_required       = $ora1->approvals_required;
		$reboot_required          = $ora1->reboot_required;
		$respond_by_date          = $ora1->respond_by_date;
		$exclude_virtual_contacts = $ora1->exclude_virtual_contacts;
		$system_id                = $ora1->system_id;
		$system_lastid            = $ora1->system_lastid;
		$system_hostname          = $ora1->system_hostname;

		printf("make_cct7_contacts %d - %d (%.2d): %s approvals=%s, reboot=%s, exclude_virtual_contacts=%s, system_id=%d, system_lastid=%d\n",
			   $count,
               $total,
               ($count / $total) * 100,
               $system_hostname,
			   $approvals_required,
			   $reboot_required,
			   $exclude_virtual_contacts,
			   $system_id,
			   $system_lastid);

		//        saveContacts($system_id, $lastid, $reboot, $approvals_required, $system_respond_by_date_num)

		if ($con->saveContacts(
		        (int)$system_id,
                $system_lastid,
                $reboot_required,
                $approvals_required,
                $exclude_virtual_contacts,
                $respond_by_date) == false)
		{
			printf("Failed finding contacts for: %s - %s\n", $system_hostname, $con->error);
		}
	}

	printf("Fixing cct7_contacts status\n");

	$query  = "select ";
	$query .= "  system_id, ";
	$query .= "  ticket_no, ";
	$query .= "  system_insert_date, ";
	$query .= "  system_insert_cuid, ";
	$query .= "  system_insert_name, ";
	$query .= "  system_update_date, ";
	$query .= "  system_update_cuid, ";
	$query .= "  system_update_name, ";
	$query .= "  system_hostname ";
	$query .= "from ";
	$query .= "  cct7_systems ";
	$query .= "where ";
	$query .= "  system_work_status = 'APPROVED' ";
	$query .= "order by ";
	$query .= "  system_hostname";

	if ($ora1->sql2($query) == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	while ($ora1->fetch())
	{
		$ticket_no       = $ora1->ticket_no;
		$system_id       = $ora1->system_id;
		$system_hostname = $ora1->system_hostname;

		$contact_response_date = $ora1->system_insert_date;
		$contact_response_cuid = $ora1->system_insert_cuid;
		$contact_response_name = $ora1->system_insert_name;

		if (strlen($ora1->system_update_cuid) > 0)
		{
			$contact_response_date = $ora1->system_update_date;
			$contact_response_cuid = $ora1->system_update_cuid;
			$contact_response_name = $ora1->system_update_name;
		}

		printf("%sd - %s\n", $system_id, $system_hostname);

		$rc = $ora2->update("cct7_contacts")
				   ->set("char", "contact_response_status", "APPROVED")
				   ->set("int",  "contact_response_date",   $contact_response_date)
				   ->set("char", "contact_response_cuid",   $contact_response_cuid)
				   ->set("char", "contact_response_name",   $contact_response_name)
				   ->where("int", "system_id", "=", $system_id)
				   ->execute();

		if ($rc == false)
		{
			printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
			exit(1);
		}

		//
		// Execute stored procedure. (See: ibmtools_cct7/Procedures/updatestatus.sql)
		//
		$query = sprintf("BEGIN updateStatus('%s'); END;", $ticket_no);

		printf("%s\n", $query);

		if ($ora2->sql2($query) == false)
		{
			printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
		}
	}

	$ora2->commit();
}

function make_cct7_log_systems()
{
	global $lib, $ora1, $ora2;

	printf("Building cct7_log_systems\n");

	if ($ora1->sql2("delete from cct7_log_systems") == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	$ora1->commit();

	$query  = "insert into cct7_log_systems  ";
	$query .= "( ";
	$query .= "  ticket_no, ";
	$query .= "  system_id, ";
	$query .= "  hostname, ";
	$query .= "  event_date, ";
	$query .= "  event_cuid, ";
	$query .= "  event_name, ";
	$query .= "  event_type, ";
	$query .= "  event_message ";
	$query .= ") ";
	$query .= "select ";
	$query .= "  t7.ticket_no                                               as ticket_no, ";
	$query .= "  s7.system_id                                               as system_id, ";
	$query .= "  s7.system_hostname                                         as hostname, ";
	$query .= "  date_to_utime(to_char(l6.event_date, 'MM/DD/YYYY'), 'MST') as event_date, ";
	$query .= "  l6.user_cuid                                               as event_cuid, ";
	$query .= "  l6.user_name                                               as event_name, ";
	$query .= "  l6.event_type                                              as event_type, ";
	$query .= "  l6.event_message                                           as event_message ";
	$query .= "from ";
	$query .= "  cct6_tickets t6, ";
	$query .= "  cct6_systems s6, ";
	$query .= "  cct6_event_log l6, ";
	$query .= "  cct7_tickets t7, ";
	$query .= "  cct7_systems s7 ";
	$query .= "where ";
	$query .= "  s6.cm_ticket_no = t6.cm_ticket_no and ";
	$query .= "  s7.system_hostname = s6.computer_hostname and ";
	$query .= "  l6.system_id = s6.system_id and ";
	$query .= "  l6.event_message is not null and ";
	$query .= "  t7.cm_ticket_no = t6.cm_ticket_no and  ";
	$query .= "  s7.ticket_no = t7.ticket_no and ";
	$query .= "  l6.event_type = 'EMAIL' ";
	//$query .= "  l6.event_message not like '%SUBMIT%' and ";
	//$query .= "  l6.event_message not like '%FROZEN%'";

	if ($ora1->sql2($query) == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	$ora1->commit();

	if ($ora1->sql2("update cct7_log_systems set sendmail_date = event_date") == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	$ora1->commit();
}

function updateScheduleDates()
{
    global $lib, $ora1, $ora2;

    printf("\nRunning updateScheduleDates('ticket_no') on cct7_tickets.\n");

	$query  = "DECLARE ";
	$query .= "  v_ticket_no   VARCHAR2(20); ";
	$query .= "   ";
	$query .= "CURSOR c1 IS ";
	$query .= "  select ";
	$query .= "    ticket_no ";
	$query .= "  from ";
	$query .= "    cct7_tickets; ";
	$query .= "     ";
	$query .= "BEGIN ";
	$query .= "  open c1; ";
	$query .= "  LOOP ";
	$query .= "    FETCH c1 INTO v_ticket_no; ";
	$query .= "    EXIT WHEN c1%NOTFOUND; ";
	$query .= "     ";
	$query .= "    updateScheduleDates(v_ticket_no); ";
	$query .= "  END LOOP; ";
	$query .= "   ";
	$query .= "  commit; ";
	$query .= "  close c1; ";
	$query .= "END; ";

	if ($ora1->sql2($query) == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	$ora1->commit();
}

function updateStatus()
{
	global $lib, $ora1, $ora2;

	printf("\nRunning updateStatus('ticket_no') on cct7_tickets where total_servers_schedule = 0\n");

    $query = "DECLARE ";
    $query .= "  v_ticket_no               VARCHAR2(80); ";

    $query .= "CURSOR c1 IS ";
    $query .= "  select ";
    $query .= "    ticket_no ";
    $query .= "  from ";
    $query .= "    cct7_tickets ";
    $query .= "  where ";
    $query .= "    total_servers_scheduled = 0; ";

    $query .= "BEGIN ";
    $query .= "open c1; ";
    $query .= "LOOP ";
    $query .= "  FETCH c1 into v_ticket_no; ";
    $query .= "  EXIT WHEN c1%NOTFOUND; ";

    $query .= "  updateStatus(v_ticket_no); ";

    $query .= "END LOOP; ";
    $query .= "close c1; ";
    $query .= "commit; ";
    $query .= "END;";

	if ($ora1->sql2($query) == false)
	{
		printf("%s - %s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	$ora1->commit();
}

function returnGMT($datetime_string)
{
	// $datetime_string = "10/28/2016 10:35";

	$dt = new DateTime($datetime_string, new DateTimeZone("America/Denver"));
	$dt->setTimezone(new DateTimeZone('GMT'));

	return $dt->format('U');
}

function make_subscriber_lists()
{
	global $lib, $ora1, $ora2;
	$group_id = 0;

	printf("\nMaking subscriber lists\n");

	printf("Dropping old subscriber sequences\n");

	if ($ora1->sql2("drop sequence cct7_subscriber_groupsseq") == false)
    {
		printf("%s\n%s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
    }

	if ($ora1->sql2("drop sequence cct7_subscriber_membersseq") == false)
	{
		printf("%s\n%s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	if ($ora1->sql2("drop sequence cct7_subscriber_serversseq") == false)
	{
		printf("%s\n%s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}


	printf("Creating new subscriber sequences\n");

	if ($ora1->sql2("create sequence cct7_subscriber_groupsseq  increment by 1 start with 1 nocache") == false)
	{
		printf("%s\n%s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	if ($ora1->sql2("create sequence cct7_subscriber_membersseq increment by 1 start with 1 nocache") == false)
	{
		printf("%s\n%s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	if ($ora1->sql2("create sequence cct7_subscriber_serversseq increment by 1 start with 1 nocache") == false)
	{
		printf("%s\n%s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

    printf("Remove old records from subscriber lists\n");

	if ($ora1->sql2("delete from cct7_subscriber_groups") == false)
    {
		printf("%s\n%s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
    }

	if ($ora1->sql2("delete from cct7_subscriber_members") == false)
	{
		printf("%s\n%s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	if ($ora1->sql2("delete from cct7_subscriber_servers") == false)
	{
		printf("%s\n%s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	// --------------------------------------------------------------------------------------------------------

    /**
	 * cct6_subscriber_lists
	 * subscriber_list_id|NUMBER|0|NOT NULL|Unique record ID
	 * insert_date|DATE|7||Date record was created
	 * subscriber_cuid|VARCHAR2|20||Subscriber CUID - (Also the insert cuid)
	 * subscriber_name|VARCHAR2|200||Subscriber Name - (Also the insert name)
	 * notify_type|VARCHAR2|20||Approver or FYI
	 * group_type|VARCHAR2|20||OS, PASE, DBA, OTHER
	 * computer_hostname|VARCHAR2|255||Computer hostname
	 * computer_os_lite|VARCHAR2|20||Computer short OS Name: HPUX
	 * computer_status|VARCHAR2|80||Computer status: PRODUCTION, TEST, DEVELOPMENT
	 * computer_managing_group|VARCHAR2|40||Computer managing group like: CMP-UNIX
    */

    printf("Building new subscriber lists for CCT7\n");

	if ($ora1->sql2("select * from cct6_subscriber_lists order by subscriber_cuid, computer_hostname") == false)
	{
		printf("%s\n%s\n", $ora1->sql_statement, $ora1->dbErrMsg);
		exit(1);
	}

	$last_cuid = '';

	while ($ora1->fetch())
    {
        $create_date = returnGMT($ora1->insert_date);

        //
        // Do we need to create a new cct7_subscriber_groups record?
        //
        if ($last_cuid != $ora1->subscriber_cuid)
        {
            $last_cuid = $ora1->subscriber_cuid;

            /**
             * cct7_subscriber_groups
             * group_id|VARCHAR2|20|NOT NULL|PK: Unique Record ID
             * create_date|NUMBER|0||GMT date record was created
             * owner_cuid|VARCHAR2|20||Owner CUID of this subscriber list
             * owner_name|VARCHAR2|200||Owner NAME of this subscriber list
             * group_name|VARCHAR2|200||Group Name
             */
            $group_id = "SUB" . $ora2->next_seq('cct7_subscriber_groupsseq');

            printf("%s - %s\n", $group_id, $ora1->subscriber_cuid);

            $rc = $ora2
                ->insert("cct7_subscriber_groups")
                ->column("group_id")
                ->column("create_date")
                ->column("owner_cuid")
                ->column("owner_name")
                ->column("group_name")
                ->value("char", $group_id)
                ->value("int",  $create_date)
                ->value("char", $ora1->subscriber_cuid)  // owner_cuid
                ->value("char", $ora1->subscriber_name)  // owner_name
                ->value("char", $ora1->subscriber_name)  // Group Name - make the name of the owner
                ->execute();

			if ($rc == false)
			{
				printf("%s\n%s\n", $ora2->sql_statement, $ora2->dbErrMsg);
				exit();
			}

			//
            // Create cct7_subscriber_members record for this user
            //

            /**
			 * cct7_subscriber_members
			 * member_id|NUMBER|0|NOT NULL|PK: Unique Record ID
			 * group_id|VARCHAR2|20||FK: cct7_subscriber_groups
			 * create_date|NUMBER|0||GMT date record was created
			 * member_cuid|VARCHAR2|20||Member CUID
			 * member_name|VARCHAR2|200||Member NAME
             */
			$member_id = $ora2->next_seq('cct7_subscriber_membersseq');

			$rc = $ora2
                ->insert("cct7_subscriber_members")
                ->column("member_id")
                ->column("group_id")
                ->column("create_date")
                ->column("member_cuid")
                ->column("member_name")
                ->value("int",  $member_id)
				->value("char", $group_id)
                ->value("int",  $create_date)
                ->value("char", $ora1->subscriber_cuid)  // member_cuid
                ->value("char", $ora1->subscriber_name)  // member_name
                ->execute();

			if ($rc == false)
			{
				printf("%s\n%s\n", $ora2->sql_statement, $ora2->dbErrMsg);
				exit();
			}
        }

        //
        // Add the cct7_subscriber_servers record
        //

        /**
         * cct7_subscriber_servers
		 * server_id|NUMBER|0|NOT NULL|PK: Unique Record ID
		 * group_id|VARCHAR2|20||FK: cct7_subscriber_groups
		 * create_date|NUMBER|0||GMT creation date
		 * owner_cuid|VARCHAR2|20||Owner CUID
		 * owner_name|VARCHAR2|200||Owner NAME
		 * computer_lastid|NUMBER|0||Asset Manager computer record ID
		 * computer_hostname|VARCHAR2|255||Server Hostname
		 * computer_ip_address|VARCHAR2|64||Server IP Address
		 * computer_os_lite|VARCHAR2|20||Server Operating System
		 * computer_status|VARCHAR2|80||Server Status: PRODUCTION, DEVELOPMENT, etc.
		 * computer_managing_group|VARCHAR2|40||Server Managing Group name
		 * notification_type|VARCHAR2|20||Notification Type: APPROVER or FYI
         *
         * Available from cct6_subscriber_lists for server information $ora->xxx
         * notify_type|VARCHAR2|20||Approver or FYI
		 * group_type|VARCHAR2|20||OS, PASE, DBA, OTHER
		 * computer_hostname|VARCHAR2|255||Computer hostname
		 * computer_os_lite|VARCHAR2|20||Computer short OS Name: HPUX
		 * computer_status|VARCHAR2|80||Computer status: PRODUCTION, TEST, DEVELOPMENT
		 * computer_managing_group|VARCHAR2|40||Computer managing group like: CMP-UNIX
         */

        //
        // Get the cct7_computers record for this server so we can get all the information we need.
        // Also do not create the server record in cct7_subscriber_servers if the cct7_computers does
        // not exist. This means the server was retired.
        //
        $query  = "select ";
        $query .= "  computer_lastid, ";
        $query .= "  computer_hostname, ";
		$query .= "  computer_ip_address, ";
		$query .= "  computer_os_lite, ";
		$query .= "  computer_status, ";
		$query .= "  computer_managing_group ";
		$query .= "from ";
		$query .= "  cct7_computers ";
		$query .= "where ";
		$query .= "  computer_hostname = '" . $ora1->computer_hostname . "'";

		if ($ora2->sql2($query) == false)
		{
			printf("%s\n%s\n", $ora2->sql_statement, $ora2->dbErrMsg);
			exit(1);
		}

		if ($ora2->fetch())
        {
			/**
			 * cct7_computers - (data we need)
			 * computer_lastid|NUMBER|22|NOT NULL|233494988
			 * computer_hostname|VARCHAR2|255||hvdnp16e
			 * computer_ip_address|VARCHAR2|64||151.119.98.174
			 * computer_os_lite|VARCHAR2|20||HPUX
			 * computer_status|VARCHAR2|80||PRODUCTION
			 * computer_managing_group|VARCHAR2|40||CMP-UNIX
			 */
			$computer_lastid         = $ora2->computer_lastid;
			$computer_hostname       = $ora2->computer_hostname;
			$computer_ip_address     = $ora2->computer_ip_address;
			$computer_os_lite        = $ora2->computer_os_lite;
			$computer_status         = $ora2->computer_status;
			$computer_managing_group = $ora2->computer_managing_group;

			/**
			 * cct7_subscriber_servers
			 * server_id|NUMBER|0|NOT NULL|PK: Unique Record ID
			 * group_id|VARCHAR2|20||FK: cct7_subscriber_groups
			 * create_date|NUMBER|0||GMT creation date
			 * owner_cuid|VARCHAR2|20||Owner CUID
			 * owner_name|VARCHAR2|200||Owner NAME
			 * computer_lastid|NUMBER|0||Asset Manager computer record ID
			 * computer_hostname|VARCHAR2|255||Server Hostname
			 * computer_ip_address|VARCHAR2|64||Server IP Address
			 * computer_os_lite|VARCHAR2|20||Server Operating System
			 * computer_status|VARCHAR2|80||Server Status: PRODUCTION, DEVELOPMENT, etc.
			 * computer_managing_group|VARCHAR2|40||Server Managing Group name
			 * notification_type|VARCHAR2|20||Notification Type: APPROVER or FYI
			 */

			$server_id = $ora2->next_seq('cct7_subscriber_serversseq');

            $rc = $ora2
                ->insert("cct7_subscriber_servers")
                ->column("server_id")
                ->column("group_id")
                ->column("create_date")
                ->column("owner_cuid")
                ->column("owner_name")
				->column("computer_lastid")
				->column("computer_hostname")
				->column("computer_ip_address")
				->column("computer_os_lite")
				->column("computer_status")
				->column("computer_managing_group")
				->column("notification_type")
                ->value("int",  $server_id)                 // server_id
				->value("char", $group_id)                  // group_id
                ->value("int",  $create_date)               // create_date
				->value("char", $ora1->subscriber_cuid)     // owner_cuid
				->value("char", $ora1->subscriber_name)     // owner_name
                ->value("int",  $computer_lastid)
                ->value("char", $computer_hostname)
                ->value("char", $computer_ip_address)
                ->value("char", $computer_os_lite)
                ->value("char", $computer_status)
                ->value("char", $computer_managing_group)
                ->value("char", $ora1->notify_type)         // From cct6_subscriber_lists
                ->execute();

			if ($rc == false)
			{
				printf("%s\n%s\n", $ora2->sql_statement, $ora2->dbErrMsg);
				exit();
			}
        }
    }

    $ora2->commit();

    printf("\nRemoving subscriber lists where there are no servers defined.\n");

	$query  = "DECLARE ";
	$query .= "  v_group_id    VARCHAR2(20); ";
	$query .= "  v_servers     NUMBER; ";

	$query .= "CURSOR c1 IS ";
	$query .= "  select ";
	$query .= "    group_id ";
	$query .= "  from ";
	$query .= "    cct7_subscriber_groups; ";
	$query .= "     ";
	$query .= "BEGIN ";
	$query .= "  open c1; ";
	$query .= "  LOOP ";
	$query .= "    FETCH c1 into v_group_id; ";
	$query .= "    EXIT WHEN c1%NOTFOUND; ";

	$query .= "    select count(*) into v_servers from cct7_subscriber_servers where group_id = v_group_id; ";

	$query .= "    IF v_servers = 0 ";
	$query .= "    THEN ";
	$query .= "      delete from cct7_subscriber_servers where group_id = v_group_id; ";
	$query .= "      delete from cct7_subscriber_members where group_id = v_group_id; ";
	$query .= "      delete from cct7_subscriber_groups  where group_id = v_group_id; ";
	$query .= "    END IF; ";

	$query .= "  END LOOP; ";

	$query .= "  close c1; ";
	$query .= "  commit; ";
	$query .= "END;";

	if ($ora2->sql2($query) == false)
	{
		printf("%s\n%s\n", $ora2->sql_statement, $ora2->dbErrMsg);
		exit(1);
	}
}

$start_time = new DateTime("now");

make_system_lists();
make_cct7_tickets();
make_cct7_systems();
make_cct7_contacts();
make_cct7_log_systems();
make_subscriber_lists();
updateStatus();
updateScheduleDates();

//
// Set the change_date so "send_notifications.php" will not send out a ton of email on startup.
//
$ora1->sql2('update cct7_tickets set change_date = update_date where change_date != update_date');

$ora1->sql2('update cct7_systems set change_date = system_update_date where change_date != system_update_date');

$ora1->sql2('update cct7_contacts set change_date = contact_update_date where change_date != contact_update_date');

$ora1->commit();

printf("\nChecking change_dates after updates were made.\n");

$ora1->sql2('select count(*) as tickets from cct7_tickets where change_date != update_date');
$ora1->fetch();
printf("cct7_tickets: (change_date != update_date) = %d\n", $ora1->tickets);

$ora1->sql2('select count(*) as systems from cct7_systems where change_date != system_update_date');
$ora1->fetch();
printf("cct7_systems: (change_date != update_date) = %d\n", $ora1->systems);

$ora1->sql2('select count(*) as contacts from cct7_contacts where change_date != contact_update_date');
$ora1->fetch();
printf("cct7_contacts: (change_date != update_date) = %d\n", $ora1->contacts);

$end_time = new DateTime("now");

printf("\n");
printf("==========================================================================================\n");
printf("Started: %s\n", $start_time->format("r"));
printf("Ended:   %s\n", $end_time->format("r"));
printf("\n");
$interval = $start_time->diff($end_time);
printf("Runtime: %s\n", $interval->format("r"));
printf("==========================================================================================\n");

printf("\nRemember to run rebuild_sequences.sql\n");
printf("\nRemember to run update_cct7_no_changes.php\n");

printf("\nAll Done!\n");
