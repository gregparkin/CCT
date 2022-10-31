<?php
/**
 * ajax_work_schedule.php
 *
 * @package   PhpStorm
 * @file      ajax_work_schedule.php
 * @author    gparkin
 * @date      4/14/17
 * @version   7.0
 *
 * @brief     Generates a Work Schedule. Called by work_schedule.php under the Reports menu in CCT
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

if (!isset($_SESSION['issue_changed']))
	$_SESSION['issue_changed'] = time();

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
$lib = new library();  // classes/library.php
$lib->debug_start('ajax_work_schedule.html');
date_default_timezone_set('America/Denver');

$ora  = new oracle();
$ora2 = new oracle();

$my_request  = array();
$input       = array();
$input_count = 0;

if (isset($_SERVER['QUERY_STRING']))   // Parse QUERY_STRING if it exists
{
	parse_str($_SERVER['QUERY_STRING'], $my_request);  // Parses URL parameter options into an array called $my_request
	$input_count = count($my_request);                 // Get the count of the number of $my_request array elements.
}

// Convert $my_request into an object called $input
$input          = json_decode(json_encode($my_request), FALSE);

$ticket_list     = isset($input->ticket_list)     ? $input->ticket_list      : '';

//
// Prepare the ticket list in this format: ('CM0000343231','CM0000...','...')
//
// Convert any commas to spaces
$str = str_replace(",", " ", $ticket_list);

// Remove multiple spaces, tabs and newlines if present
$tickets = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $str);

// Create an array of $systems
$ticket_list = explode(" ", $tickets);

$tickets = '';
$count   = 0;

foreach ($ticket_list as $ticket_no)
{
	if ($count == 0)
	{
		$count += 1;
		$tickets = "('" . trim(strtoupper($ticket_no)) . "'";
	}
	else
	{
		$tickets .= ",'" . trim(strtoupper($ticket_no)) . "'";
	}
}

if ($count > 0)
	$tickets .= ")";

//
// Prepare ticket_status in this format: ('ACTIVE','CLOSED',...)
//
$ticket_active   = isset($input->ticket_active)   ? $input->ticket_active   : '';
$ticket_canceled = isset($input->ticket_canceled) ? $input->ticket_canceled : '';
$ticket_closed   = isset($input->ticket_closed)   ? $input->ticket_closed   : '';
$ticket_draft    = isset($input->ticket_draft)    ? $input->ticket_draft    : '';
$ticket_failed   = isset($input->ticket_failed)   ? $input->ticket_failed   : '';

$ticket_status = "";
$count         = 0;

if ($ticket_active == "Y")
{
	if ($count == 0)
	{
		$count += 1;
		$ticket_status = "('ACTIVE'";
	}
	else
	{
		$ticket_status .= ",'ACTIVE'";
	}
}

if ($ticket_canceled == "Y")
{
	if ($count == 0)
	{
		$count += 1;
		$ticket_status = "('CANCELED'";
	}
	else
	{
		$ticket_status .= ",'CANCELED'";
	}
}

if ($ticket_closed == "Y")
{
	if ($count == 0)
	{
		$count += 1;
		$ticket_status = "('CLOSED'";
	}
	else
	{
		$ticket_status .= ",'CLOSED'";
	}
}

if ($ticket_draft == "Y")
{
	if ($count == 0)
	{
		$count += 1;
		$ticket_status = "('DRAFT'";
	}
	else
	{
		$ticket_status .= ",'DRAFT'";
	}
}

if ($ticket_failed == "Y")
{
	if ($count == 0)
	{
		$count += 1;
		$ticket_status = "('FAILED'";
	}
	else
	{
		$ticket_status .= ",'FAILED'";
	}
}

if ($count > 0)
	$ticket_status .= ")";

//
// Prepare server_status in this format: ('APPROVED','CANCELED',...)
//
$server_approved = isset($input->server_approved) ? $input->server_approved : '';
$server_canceled = isset($input->server_canceled) ? $input->server_canceled : '';
$server_failed   = isset($input->server_failed)   ? $input->server_failed   : '';
$server_rejected = isset($input->server_rejected) ? $input->server_rejected : '';
$server_starting = isset($input->server_starting) ? $input->server_starting : '';
$server_success  = isset($input->server_success)  ? $input->server_success  : '';
$server_waiting  = isset($input->server_waiting)  ? $input->server_waiting  : '';

$server_status = "";
$count         = 0;

if ($server_approved == "Y")
{
	if ($count == 0)
	{
		$count += 1;
		$server_status = "('APPROVED'";
	}
	else
	{
		$server_status .= ",'APPROVED'";
	}
}

if ($server_canceled == "Y")
{
	if ($count == 0)
	{
		$count += 1;
		$server_status = "('CANCELED'";
	}
	else
	{
		$server_status .= ",'CANCELED'";
	}
}

if ($server_failed == "Y")
{
	if ($count == 0)
	{
		$count += 1;
		$server_status = "('FAILED'";
	}
	else
	{
		$server_status .= ",'FAILED'";
	}
}

if ($server_rejected == "Y")
{
	if ($count == 0)
	{
		$count += 1;
		$server_status = "('REJECTED'";
	}
	else
	{
		$server_status .= ",'REJECTED'";
	}
}

if ($server_starting == "Y")
{
	if ($count == 0)
	{
		$count += 1;
		$server_status = "('STARTING'";
	}
	else
	{
		$server_status .= ",'STARTING'";
	}
}

if ($server_success == "Y")
{
	if ($count == 0)
	{
		$count += 1;
		$server_status = "('SUCCESS'";
	}
	else
	{
		$server_status .= ",'SUCCESS'";
	}
}

if ($server_waiting == "Y")
{
	if ($count == 0)
	{
		$count += 1;
		$server_status = "('WAITING'";
	}
	else
	{
		$server_status .= ",'WAITING'";
	}
}

if ($count > 0)
	$server_status .= ")";

$timezone        = isset($input->timezone)        ? $input->timezone      : '';
$template        = isset($input->template)        ? $input->template      : '';

$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_active: %s",   $ticket_active);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_canceled: %s", $ticket_canceled);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_closed: %s",   $ticket_closed);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_draft: %s",    $ticket_draft);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_failed: %s",   $ticket_failed);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "server_approved: %s", $server_approved);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "server_canceled: %s", $server_canceled);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "server_failed: %s",   $server_failed);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "server_rejected: %s", $server_rejected);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "server_starting: %s", $server_starting);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "server_success: %s",  $server_success);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "server_waiting: %s",  $server_waiting);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "timezone: %s",        $timezone);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "template: %s",        $template);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "tickets: %s",         $tickets);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_status: %s",   $ticket_status);
$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "server_status: %s",   $server_status);

$server_array = array();
$remedy_array = array();

function get_cct7_computers($system_lastid)
{
	global $ora2, $lib, $server_array;

	if (array_key_exists($system_lastid, $server_array))
	{
		$node = $server_array[$system_lastid];
		return $node;
	}

	$query  = "select ";
	$query .= "  * ";
	$query .= "from ";
	$query .= "  cct7_computers ";
	$query .= "where ";
	$query .= "  computer_lastid = " . $system_lastid;

	$computer_hostname            = "";
	$computer_os_lite             = "";
	$computer_status              = "";
	$computer_status_description  = "";
	$computer_description         = "";
	$computer_city                = "";
	$computer_state               = "";
	$computer_serial_no           = "";
	$computer_model_no            = "";
	$computer_model               = "";
	$computer_model_mfg           = "";
	$computer_cpu_type            = "";
	$computer_os_group_contact    = "";
	$computer_cio_group           = "";
	$computer_managing_group      = "";
	$computer_gold_server         = "";
	$computer_slevel_colors       = "";
	$computer_special_handling    = "";
	$app_server_assn_sox_critical = 0;
	$db_server_assn_sox_critical  = 0;

	if ($ora2->sql2($query) == false)
	{
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->dbErrMsg);
	}

	if ($ora2->fetch())
	{
		$computer_hostname            = $ora2->computer_hostname;
		$computer_os_lite             = $ora2->computer_os_lite;
		$computer_status              = $ora2->computer_status;
		$computer_status_description  = $ora2->computer_status_description;
		$computer_description         = $ora2->computer_description;
		$computer_city                = $ora2->computer_city;
		$computer_state               = $ora2->computer_state;
		$computer_serial_no           = $ora2->computer_serial_no;
		$computer_model_no            = $ora2->computer_model_no;
		$computer_model               = $ora2->computer_model;
		$computer_model_mfg           = $ora2->computer_model_mfg;
		$computer_cpu_type            = $ora2->computer_cpu_type;
		$computer_os_group_contact    = $ora2->computer_os_group_contact;
		$computer_cio_group           = $ora2->computer_cio_group;
		$computer_managing_group      = $ora2->computer_managing_group;
		$computer_gold_server         = $ora2->computer_gold_server;
		$computer_slevel_colors       = $ora2->computer_slevel_colors;
		$computer_special_handling    = $ora2->computer_special_handling;
		$app_server_assn_sox_critical = $ora2->app_server_assn_sox_critical;
		$db_server_assn_sox_critical  = $ora2->db_server_assn_sox_critical;
	}

	// computer_hostname
	// computer_os_lite             = Linux
	// computer_status              = PRODUCTION
	// computer_status_description  = In Use
	// computer_description         = HP ProLiant DL380 Gen9
	// computer_city                = OMAHA
	// computer_state               = NE
	// computer_serial_no           = MXQ62800GX
	// computer_model_no            = M551748
	// computer_model               = PROLIANT DL380 G9
	// computer_model_mfg           = HEWLETT PACKARD
	// computer_cpu_type            = Xeon
	// computer_os_group_contact    = mits-all
	// computer_cio_group           = CMP-UNIX SUPPORT
	// computer_managing_group      = CMP-UNIX
	// computer_gold_server         = N
	// computer_slevel_colors       = BRONZE,BLUE
	// computer_special_handling    = N
	// app_server_assn_sox_critical = 0
	// db_server_assn_sox_critical  = 0

	$node = new data_node();

	$node->computer_hostname            = $computer_hostname;
	$node->computer_os_lite             = $computer_os_lite;
	$node->computer_status              = $computer_status;
	$node->computer_status_description  = $computer_status_description;
	$node->computer_description         = $computer_description;
	$node->computer_city                = $computer_city;
	$node->computer_state               = $computer_state;
	$node->computer_serial_no           = $computer_serial_no;
	$node->computer_model_no            = $computer_model_no;
	$node->computer_model               = $computer_model;
	$node->computer_model_mfg           = $computer_model_mfg;
	$node->computer_cpu_type            = $computer_cpu_type;
	$node->computer_os_group_contact    = $computer_os_group_contact;
	$node->computer_cio_group           = $computer_cio_group;
	$node->computer_managing_group      = $computer_managing_group;
	$node->computer_gold_server         = $computer_gold_server;
	$node->computer_slevel_colors       = $computer_slevel_colors;
	$node->computer_special_handling    = $computer_special_handling;
	$node->app_server_assn_sox_critical = $app_server_assn_sox_critical;
	$node->db_server_assn_sox_critical  = $db_server_assn_sox_critical;

	$server_array[$system_lastid] = $node;

	return $node;
}

// computer_hostname
// computer_os_lite             = Linux
// computer_status              = PRODUCTION
// computer_status_description  = In Use
// computer_description         = HP ProLiant DL380 Gen9
// computer_city                = OMAHA
// computer_state               = NE
// computer_serial_no           = MXQ62800GX
// computer_model_no            = M551748
// computer_model               = PROLIANT DL380 G9
// computer_model_mfg           = HEWLETT PACKARD
// computer_cpu_type            = Xeon
// computer_os_group_contact    = mits-all
// computer_cio_group           = CMP-UNIX SUPPORT
// computer_managing_group      = CMP-UNIX
// computer_gold_server         = N
// computer_slevel_colors       = BRONZE,BLUE
// computer_special_handling    = N
// app_server_assn_sox_critical = 0
// db_server_assn_sox_critical  = 0


function get_remedy_cm($cm_ticket_no)
{
	global $ora2, $lib, $remedy_array;

	if (array_key_exists($cm_ticket_no, $remedy_array))
	{
		$node = $remedy_array[$cm_ticket_no];
		return $node;
	}

	$create_date       = 0;
	$start_date        = 0;
	$end_date          = 0;
	$status            = "";
	$duration_computed = "";
	$open_closed       = "";
	$owner_group       = "";
	$component_type    = "";
	$processor_name    = "";
	$category          = "";

	if ($ora2->sql("select * from t_cm_implementation_request@remedy_prod where change_id = '" . $cm_ticket_no . "'") == false)
	{
		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->sql_statement);
		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->dbErrMsg);
	}

	if ($ora2->fetch())
	{
		$create_date       = $ora2->create_date;
		$start_date        = $ora2->start_date;
		$end_date          = $ora2->end_date;
		$status            = $ora2->status;
		$duration_computed = $ora2->duration_computed;
		$open_closed       = $ora2->open_closed;
		$owner_group       = $ora2->owner_group;
		$component_type    = $ora2->component_type;
		$processor_name    = $ora2->processor_name;
		$category          = $ora2->category;
	}

	// CM0000338426
	// create_date
	// start_date
	// end_date
	// status
	// duration_computed
	// open_closed
	// owner_group
	// component_type (Processor)
	// processor_name  (LXDNP25J,LXDNP24J,...)
	// category        (Software, Project)  "Project / BAU" Anything not Project is BUA

	$node = new data_node();
	$node->create_date       = $create_date;
	$node->start_date        = $start_date;
	$node->end_date          = $end_date;
	$node->duration_computed = $duration_computed;
	$node->status            = $status;
	$node->open_closed       = $open_closed;
	$node->owner_group       = $owner_group;
	$node->component_type    = $component_type;
	$node->processor_name    = $processor_name;
	$node->category          = $category;

	$remedy_array[$cm_ticket_no] = $node;

	return $node;
}

$query  = "select ";
$query .= "  t.ticket_no, ";
$query .= "  t.status, ";
$query .= "  t.status_date, ";
$query .= "  t.status_cuid, ";
$query .= "  t.status_name, ";
$query .= "  t.owner_cuid, ";
$query .= "  t.owner_first_name, ";
$query .= "  t.owner_name, ";
$query .= "  t.owner_email, ";
$query .= "  t.owner_job_title, ";
$query .= "  t.work_activity, ";
$query .= "  t.approvals_required, ";
$query .= "  t.reboot_required, ";
$query .= "  t.respond_by_date, ";
$query .= "  t.schedule_start_date, ";
$query .= "  t.schedule_end_date, ";
$query .= "  t.cm_ticket_no, ";
$query .= "  t.remedy_cm_start_date, ";
$query .= "  t.remedy_cm_end_date, ";
$query .= "  t.total_servers_scheduled, ";
$query .= "  t.total_servers_waiting, ";
$query .= "  t.total_servers_approved, ";
$query .= "  t.total_servers_rejected, ";
$query .= "  t.total_servers_not_scheduled, ";
$query .= "  s.system_id, ";
$query .= "  s.ticket_no, ";
$query .= "  s.system_lastid, ";
$query .= "  s.system_hostname, ";
$query .= "  s.system_os, ";
$query .= "  s.system_usage, ";
$query .= "  s.system_location, ";
$query .= "  s.system_timezone_name, ";
$query .= "  s.system_osmaint_weekly, ";
$query .= "  s.system_respond_by_date, ";
$query .= "  s.system_work_start_date, ";
$query .= "  s.system_work_end_date, ";
$query .= "  s.system_work_duration, ";
$query .= "  s.system_work_status, ";
$query .= "  s.total_contacts_responded, ";
$query .= "  s.total_contacts_not_responded, ";
$query .= "  s.original_work_start_date, ";
$query .= "  s.original_work_end_date, ";
$query .= "  s.original_work_duration ";
$query .= "from ";
$query .= "  cct7_tickets t, ";
$query .= "  cct7_systems s ";
$query .= "where ";
$query .= "  (upper(t.ticket_no) in " . $tickets . " or upper(t.cm_ticket_no) in " . $tickets . ") and ";
$query .= "  t.status in " . $ticket_status . " and ";
$query .= "  s.ticket_no = t.ticket_no and ";
$query .= "  s.system_work_status in " . $server_status . " ";
$query .= "order by ";
$query .= "  s.system_work_start_date, s.system_hostname";

$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

if ($ora->sql2($query) == false)
{
	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
	$json['ajax_status']  = 'FAILED';
	$json['ajax_message'] = sprintf("SQL Error: %s", $ora->dbErrMsg);
	echo json_encode($json);
	exit();
}

printf("{\n");

$row = array();
$count_records = 0;

while ($ora->fetch())
{
	if ($count_records == 0)
	{
		printf("\"ajax_status\": \"SUCCESS\",\n");
		printf("\"ajax_message\": \"There be whales here Captain!\",\n");
		printf("\"rows\": [\n");
	}

	if ($count_records > 0)
	{
		printf(",\n");  // Let the client know that another record is coming.
	}

	//
	// Determine what timezone to use
	//
	$tz = 'America/Denver';

	switch ( $timezone )
	{
		case "tz_server":
			$tz = $ora->system_timezone_name;
			break;
		case "tz_local":
			if (isset($_SESSION['local_timezone_name']))
			{
				$tz = $_SESSION['local_timezone_name'];
			}
			break;
		case "tz_pacific":
			$tz = 'America/Los_Angeles';
			break;
		case "tz_mountain":
			$tz = 'America/Denver';
			break;
		case "tz_central":
			$tz = 'America/Chicago';
			break;
		case "tz_eastern":
			$tz = 'America/New_York';
			break;
		default:
			break;
	}

	$node1 = get_cct7_computers($ora->system_lastid);
	$node2 = get_remedy_cm($ora->cm_ticket_no);

	//$local_dtz                = new DateTimeZone($local_timezone_name);
	//$local_dt                 = new DateTime('now', $local_dtz);
	//$local_timezone_abbr      = $local_dt->format('T'); // (i.e. MST)
	//$local_timezone_offset    = $local_dt->getOffset();

	$format_weekday_mmddyyyy_hhmm_tz    = 'D m/d/Y H:i T';
	$format_weekday                     = 'D';
	$format_mmddyyyy                    = 'm/d/Y';
	$format_hhmm                        = 'H:i';
	$format_tz                          = 'T';

	// 04/01/17 Sat CDT: 00:00   m/d/Y D T: H:i
	$format_mmddyyyy_weekday_tz_hhmm    = 'm/d/Y D T: H:i';

	$system_work_start_date = "";
	$system_work_end_date   = "";
	$system_work_duration   = "";
	$start_date             = "";
	$start_time             = "";
	$start_wday             = "";
	$start_tz               = "";
	$cm_create_date         = "";
	$cm_start_date          = "";
	$cm_end_date            = "";
	$cm_duration_computed   = "";

	if ($ora->system_work_start_date == 0)
	{
		$system_work_start_date = '(See Remedy)';
		$system_work_end_date   = '(See Remedy)';
		$system_work_duration   = '(See Remedy)';

		$start_date = "(See Remedy)";
		$start_time = "";
		$start_wday = "";
		$start_tz   = "";
	}
	else
	{
		$system_work_start_date = $lib->gmt_to_format(
			$ora->system_work_start_date, $format_mmddyyyy_weekday_tz_hhmm, $tz);

		$system_work_end_date   = $lib->gmt_to_format(
			$ora->system_work_end_date,   $format_mmddyyyy_weekday_tz_hhmm, $tz);

		$system_work_duration   = $ora->system_work_duration;

		$start_date = strtoupper($lib->gmt_to_format(
			$ora->system_work_start_date, $format_mmddyyyy, $tz));

		$start_time = $lib->gmt_to_format(
			$ora->system_work_start_date, $format_hhmm, $tz);

		$start_wday = $lib->gmt_to_format(
			$ora->system_work_start_date, $format_weekday, $tz);

		$start_tz = $lib->gmt_to_format(
			$ora->system_work_start_date, $format_tz, $tz);
	}

	// Remedy Ticket conversion.
	// $cm_create_date
	// $cm_start_date
	// $cm_end_date
	// $cm_duration_computed

	$cm_create_date         = $lib->gmt_to_format(
		$node2->create_date,   $format_mmddyyyy_weekday_tz_hhmm, $tz);

	$cm_start_date          = $lib->gmt_to_format(
		$node2->start_date,   $format_mmddyyyy_weekday_tz_hhmm, $tz);

	$cm_end_date            = $lib->gmt_to_format(
		$node2->end_date,   $format_mmddyyyy_weekday_tz_hhmm, $tz);

	$cm_duration_computed   = $node2->duration_computed;

	if ($template == "template1")
	{
		//
		// Classic CCT 6 Work Schedule layout
		//
		//    THU       Hr.Min                  User       CM            CM        CCT                     Assignment
		// 09/12/2013  Duration  Hostname       Approvals  Ticket No.    Status    Status  Classification  Group       Gold  S.H.  Boot
		// ----------  --------  -------------  ---------  ------------  --------  ------  --------------  ----------  ----  ----  ----
		// MST: 20:00  02.00     LXDENVMPC061   READY      CM0000211803  Turnover  FROZEN  Patching        MITS-ALL    N     N     Y

		// Implementor Name  Turnover Notes    CM  Implemenater Notes
		// ----------------  ----------------  --  ------------------

		// Weekday | Date       | Time  | TZ  | Duration | Hostname     |
		// THU     | 07/14/2017 | 21:00 | MDT | 00:02:00 | LXDENVMPC061 |

		$row['Weekday']            = $start_wday;
		$row['Date']               = $start_date;
		$row['Time']               = $start_time;
		$row['TZ']                 = $start_tz;
		$row['Duration']           = $system_work_duration;
		$row['Hostname']           = $ora->system_hostname;
		$row['User Approvals']     = $ora->system_work_status;
		$row['CM Ticket']          = strlen($ora->cm_ticket_no) > 0 ? $ora->cm_ticket_no : "";
		$row['CM Status']          = $node2->status;
		$row['CCT Ticket']         = $ora->ticket_no;
		$row['CCT Status']         = $ora->status;
		$row['Classification']     = $ora->work_activity;
		$row['Assign. Group']      = $node2->owner_group;
		$row['Gold']               = $node1->computer_slevel_colors;
		$row['S.H.']               = $node1->computer_special_handling;
		$row['Boot']               = $ora->reboot_required;
		$row['Implementor Name']   = $ora->owner_name;
		$row['Turnover Notes']     = "";
		$row['CM']                 = "";
		$row['Implemenator Notes'] = "";
	}
	else if ($template == "template2")
	{
		//
		// Tory Lehre's Firmware work schedule template
		//

		// $node1 = get_cct7_computers($ora->system_lastid);
		// $node2 = get_remedy_cm($ora->cm_ticket_no);

		//
		// $node2->category (Software, Project, ...) Anything not Project is BAU
		//
		if ($node2->category != "Project")
			$row['Project / BAU']                    = "BAU";
		else
			$row['Project / BAU']                    = "Project";

		$row['System Name']                          = $ora->system_hostname;
		$row['System Name']                          = $node1->computer_serial_no;
		$row['City']                                 = $node1->computer_city;
		$row['To Be Updated By:']                    = "";
		$row['Sys Admin']                            = "";
		$row['ORIG DATE']                            = $system_work_start_date;
		$row['Duration']                             = $system_work_duration;
		$row['Duration']                             = $system_work_duration;
		$row['Time/Date start of Change Ticket']     = $cm_start_date;
		$row['Call into Bridge to contact SA']       = $cm_start_date;
		$row['Time/Date end of Change Ticket']       = $cm_end_date;
		$row['CM Ticket']                            = strlen($ora->cm_ticket_no) > 0 ? $ora->cm_ticket_no : "";
		$row['CM Status']                            = $node2->status;
		$row['CCT Ticket']                           = $ora->ticket_no;
		$row['CCT Status']                           = $ora->status;
		$row['Patching Ticket Number']               = "";
		$row['HP Ticket Number']                     = "";
		$row['System Type']                          = $node1->computer_model;
		$row['PDC/ROM Version']                      = "";

		$row['1. Current or Firmware file name']     = "";
		$row['1. Disk Type']                         = "";
		$row['1. Firmware Rev']                      = "";
		$row['2. Current or Firmware file name']     = "";
		$row['2. mp/gsp/ilo']                        = "";
		$row['2. Current']                           = "";
		$row['2. Smart Update Firmware DVD Version'] = "";
		$row['2. Siupllemen tal needed']             = "";
		$row['2. mp/gsp password']                   = "";
		$row['2. Disk Type']                         = "";
		$row['2. Firmware rev']                      = "";

		for ($x=3; $x<16; $x++)
		{
			$label1 = sprintf("%d. Current or Firmware file name", $x);
			$label2 = sprintf("%d. Disk Type", $x);
			$label3 = sprintf("%d. Firmware Rev", $x);

			$row[$label1] = "";
			$row[$label2] = "";
			$row[$label3] = "";
		}

		$row['Total Duration'] = "";
	}

	echo json_encode($row);

	$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "SENDING: %s", json_encode($row));

	$count_records++;  // Count up the records to determine if we sent anything.
}

if ($count_records == 0)
{
	printf("\"ajax_status\": \"FAILED\",\n");
	printf("\"ajax_message\": \"No records found using your search criteria.\",\n");
	printf("\"rows\": [\n");
}

printf("]}\n");  // Close out the data stream
exit();