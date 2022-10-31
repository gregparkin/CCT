#!/opt/lampp/bin/php -q
<?php
/**
 * conversion_cct7_to_cct6.php
 *
 * @package   PhpStorm
 * @file      conversion_cct7_to_cct6.php
 * @author    gparkin
 * @date      7/26/17
 * @version   7.0
 *
 * @brief     Convert the data in the cct7_xxx tables back into the cct6_xxx tables
 *
 */

ini_set('xdebug.collect_vars',    '5');
ini_set('xdebug.collect_vars',    'on');
ini_set('xdebug.collect_params',  '4');
ini_set('xdebug.dump_globals',    'on');
ini_set('xdebug.dump.SERVER',     'REQUEST_URI');
ini_set('xdebug.show_local_vars', 'on');

ini_set("error_reporting", "E_ALL & ~E_DEPRECATED & ~E_STRICT");
ini_set("log_errors", 1);
ini_set("error_log", "/opt/ibmtools/cct7/logs/php-error.log");
ini_set("log_errors_max_len", 0);
ini_set("report_memleaks", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
ini_set("ignore_repeated_errors", 0);
ini_set("ignore_repeated_source", 0);

//$path = '/usr/lib/pear';
//set_include_path(get_include_path() . PATH_SEPARATOR . $path);

set_include_path("/opt/ibmtools/www/cct7/classes");

if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

$ora1 = new oracle();
$ora2 = new oracle();
$ora3 = new oracle();

$mnet             = array();
$oncall_overrides = array();
$subscribers      = array();

$ran_loadOncallOverrides = false;
$ran_loadSubscribers     = false;

loadOncallOverrides();
loadSubscribers();

//
// Build a list of cm_ticket_no that are not found in cct6_tickets from cct7_tickets.
//
$query  = "select distinct ";
$query .= "  cm_ticket_no ";
$query .= "from ";
$query .= "  cct7_tickets ";
$query .= "where ";
$query .= "  (cm_ticket_no) not in (select cm_ticket_no from cct6_tickets) ";
$query .= "union all ";
$query .= "  select cm_ticket_no from cct6_tickets ";
$query .= "  where (cm_ticket_no) not in (select cm_ticket_no from cct7_tickets) ";
$query .= "order by ";
$query .= "  cm_ticket_no";

if ($ora1->sql2($query) == false)
{
    printf("%s\n", $ora1->dbErrMsg);
    exit();
}

$ticket_list = array();

while ($ora1->fetch())
{
    //
    // Throw out any tickets that do not begin with CM
    //
    if (substr($ora1->cm_ticket_no, 0, 2) != 'CM')
        continue;

    //array_push($ticket_list, $ora1->cm_ticket_no);
    $ticket_list[$ora1->cm_ticket_no] = null;
}

$total_tickets = count($ticket_list);
printf("Total Tickets: %d\n", $total_tickets);

$ticket_count = 0;

$tic = new cct7_tickets();
$sys = new cct7_systems();
$con = new cct7_contacts();
$lib = new library();

//
// Build a link list of all the cct7 data we need to convert into the cc6 tables
//
foreach ($ticket_list as $cm_ticket_no => $obj)
{
    //printf("%s\n", $cm_ticket_no);
    $ticket_count += 1;

    if ($tic->getTicket($cm_ticket_no) == false)
    {
        printf("Cannot retrieve from cct7_tickets: %s\n", $cm_ticket_no);
        continue;
    }

    $t = new data_node();

    $t->cm_ticket_no              = $cm_ticket_no;
    $t->ticket_no                 = $tic->ticket_no;  // Used to pull cct7 records
	$t->ticket_status 	          = $tic->status;
	$t->ticket_insert_cuid        = $tic->insert_cuid;
	$t->ticket_insert_name        = $tic->insert_name;
	$t->ticket_insert_date        = $tic->insert_date_char2;
	$t->ticket_update_cuid        = $tic->update_cuid;
	$t->ticket_update_name        = $tic->update_name;
	$t->ticket_update_date        = $tic->update_date_char2;
	$t->ticket_contact_cuid       = $tic->owner_cuid;
	$t->ticket_contact_first_name = $tic->owner_first_name;
	$t->ticket_contact_name	      = $tic->owner_email;
	$t->ticket_manager_cuid       = $tic->manager_cuid;
	$t->ticket_manager_first_name = $tic->manager_first_name;
	$t->ticket_manager_name       = $tic->manager_name;
	$t->ticket_manager_email      = $tic->manager_email;

	if ($ora1->status != "DRAFT")
    {
		$t->ticket_submit_date    = $tic->update_date_char2;
		$t->ticket_submit_cuid    = $tic->update_cuid;
		$t->ticket_submit_name    = $tic->update_name;
    }
    else
    {
		$t->ticket_submit_date    = "";
		$t->ticket_submit_cuid    = "";
		$t->ticket_submit_name    = "";
    }

	$t->ticket_freeze_date    = "";
	$t->ticket_freeze_cuid    = "";
	$t->ticket_freeze_name    = "";

	if ($ora1->status == "CANCELED")
    {
		$t->ticket_cancel_date    = $tic->update_date_char2;
		$t->ticket_cancel_cuid    = $tic->update_cuid;
		$t->ticket_cancel_name    = $tic->update_name;
    }
    else
    {
		$t->ticket_cancel_date    = "";
		$t->ticket_cancel_cuid    = "";
		$t->ticket_cancel_name    = "";
    }

    $query = sprintf("select * from cct7_classifications where classification = '%s'",
					 $tic->work_activity);

	if ($ora1->sql2($query) == false)
    {
        printf("%s - %d\n", $query, $ora1->dbErrMsg);
        exit();
    }

    if ($ora1->fetch())
    {
		$t->classification_id          = $ora1->classification_id;
        $t->classification             = $ora1->classification;
        $t->classification_comments    = $ora1->classification_comments;
        $t->classification_cuid        = $ora1->classification_cuid;
        $t->classification_last_name   = $ora1->classification_last_name;
        $t->classification_first_name  = $ora1->classification_first_name;
        $t->classification_nick_name   = $ora1->classification_nick_name;
        $t->classification_middle      = $ora1->classification_middle;
        $t->classification_name        = $ora1->classification_name;
        $t->classification_job_title   = $ora1->classification_job_title;
        $t->classification_email       = $ora1->classification_email;
        $t->classification_work_phone  = $ora1->classification_work_phone;
        $t->classification_pager       = $ora1->classification_pager;
        $t->classification_street      = $ora1->classification_street;
        $t->classification_city        = $ora1->classification_city;
        $t->classification_state       = $ora1->classification_state;
        $t->classification_rc          = $ora1->classification_rc;
        $t->classification_company     = $ora1->classification_company;
        $t->classification_tier1       = $ora1->classification_tier1;
        $t->classification_tier2       = $ora1->classification_tier2;
        $t->classification_tier3       = $ora1->classification_tier3;
        $t->classification_status      = $ora1->classification_status;
        $t->classification_change_date = $ora1->classification_change_date;
        $t->classification_ctl_cuid    = $ora1->classification_ctl_cuid;
        $t->classification_mgr_cuid    = $ora1->classification_mgr_cuid;
    }
    else
    {
		$t->classification_id          = "";
        $t->classification             = "";
        $t->classification_comments    = "";
        $t->classification_cuid        = "";
        $t->classification_last_name   = "";
        $t->classification_first_name  = "";
        $t->classification_nick_name   = "";
        $t->classification_middle      = "";
        $t->classification_name        = "";
        $t->classification_job_title   = "";
        $t->classification_email       = "";
        $t->classification_work_phone  = "";
        $t->classification_pager       = "";
        $t->classification_street      = "";
        $t->classification_city        = "";
        $t->classification_state       = "";
        $t->classification_rc          = "";
        $t->classification_company     = "";
        $t->classification_tier1       = "";
        $t->classification_tier2       = "";
        $t->classification_tier3       = "";
        $t->classification_status      = "";
        $t->classification_change_date = "";
        $t->classification_ctl_cuid    = "";
        $t->classification_mgr_cuid    = "";
    }

	$t->ticket_os_maintwin	        = "W";
    $t->ticket_approvals_required	= $tic->approvals_required;
    $t->ticket_read_esc1_date	    = $tic->email_reminder1_date;
    $t->ticket_read_esc2_date	    = $tic->email_reminder2_date;
    $t->ticket_read_esc3_date	    = $tic->email_reminder3_date;
    $t->ticket_resp_esc1_date	    = $tic->email_reminder1_date;
    $t->ticket_resp_esc2_date	    = $tic->email_reminder2_date;
    $t->ticket_resp_esc3_date	    = $tic->respond_by_date;

    //
    // Go get the Remedy ticket
    //
    $query = sprintf(
		"select * from t_cm_implementation_request@remedy_prod where change_id = '%s'",
        $cm_ticket_no);

    if ($ora1->sql2($query) == false)
    {
		printf("%s - %d\n", $query, $ora1->dbErrMsg);
		exit();
    }

    if ($ora1->fetch() == false)
    {
        printf("No data found in remedy for ticket: %s\n", $cm_ticket_no);
        exit();
    }

	//
	// Remedy dates are recorded as NUMBERs in Unix Sytle GMT format. Total number of seconds since 01/01/1970.
	//
	$t->cm_change_id                   = $ora1->change_id;
	$t->cm_assign_group                = $ora1->assign_group;
	$t->cm_category                    = $ora1->category;
	$t->cm_category_type               = $ora1->category_type;
	$t->cm_closed_by                   = $ora1->closed_by;
	$t->cm_close_code                  = $ora1->close_code;
	$t->cm_component                   = $ora1->component;
	$t->cm_scheduling_flexibility      = $ora1->scheduling_flexibility;
	$t->cm_entered_by                  = $ora1->entered_by;
	$t->cm_exp_code                    = $ora1->exp_code;
	$t->cm_fix_level                   = $ora1->fix_level;
	$t->cm_impact                      = $ora1->impact;
	$t->cm_implementor_first_last      = $ora1->implementor_first_last;
	$t->cm_implementor_login           = $ora1->implementor_login;
	$t->cm_parent_ir                   = $ora1->parent_ir;
	$t->cm_normal_release_session      = $ora1->normal_release_session;
	$t->cm_pager                       = $ora1->pager;
	$t->cm_phone                       = $ora1->phone;
	$t->cm_pin                         = $ora1->pin;
	$t->cm_product                     = $ora1->product;
	$t->cm_product_type                = $ora1->product_type;
	$t->cm_software_object             = $ora1->software_object;
	$t->cm_status                      = $ora1->status;
	$t->cm_tested                      = $ora1->tested;
	$t->cm_duration                    = $ora1->duration;
	$t->cm_business_unit               = $ora1->business_unit;
	$t->cm_duration_computed           = $ora1->duration_computed;
	$t->cm_email                       = $ora1->email;
	$t->cm_company_name                = $ora1->company_name;
	$t->cm_director                    = $ora1->director;
	$t->cm_manager                     = $ora1->manager;
	$t->cm_owner_name                  = $ora1->owner_name;
	$t->cm_owner_cuid                  = $ora1->owner_cuid;
	$t->cm_groupid                     = $ora1->groupid;
	$t->cm_temp                        = $ora1->temp;
	$t->cm_last_modified_by            = $ora1->last_modified_by;
	$t->cm_last_modified               = $ora1->last_modified;
	$t->cm_risk_integer                = $ora1->risk_integer;
	$t->cm_owner_login_id              = $ora1->owner_login_id;
	$t->cm_open_closed                 = $ora1->open_closed;
	$t->cm_user_timestamp              = $ora1->user_timestamp;
	$t->cm_description                 = $ora1->description;
	$t->cm_backoff_plan                = $ora1->backoff_plan;
	$t->cm_implementation_instructions = $ora1->implementation_instructions;
	$t->cm_business_reason             = $ora1->business_reason;
	$t->cm_owner_first_name            = $ora1->owner_first_name;
	$t->cm_owner_last_name             = $ora1->owner_last_name;
	$t->cm_change_occurs               = $ora1->change_occurs;
	$t->cm_release_level               = $ora1->release_level;
	$t->cm_master_ir                   = $ora1->master_ir;
	$t->cm_owner_group                 = $ora1->owner_group;
	$t->cm_approval_status             = $ora1->approval_status;
	$t->cm_component_type              = $ora1->component_type;
	$t->cm_desc_short                  = $ora1->desc_short;
	$t->cm_last_status_change_by       = $ora1->last_status_change_by;
	$t->cm_previous_status             = $ora1->previous_status;
	$t->cm_component_id                = $ora1->component_id;
	$t->cm_test_tool                   = $ora1->test_tool;
	$t->cm_featured_proj_name          = $ora1->featured_proj_name;
	$t->cm_tmpmainplatform             = $ora1->tmpmainplatform;
	$t->cm_tmpblockmessage             = $ora1->tmpblockmessage;
	$t->cm_guid                        = $ora1->guid;
	$t->cm_platform                    = $ora1->platform;
	$t->cm_cllicodes                   = $ora1->cllicodes;
	$t->cm_processor_name              = $ora1->processor_name;
	$t->cm_system_name                 = $ora1->system_name;
	$t->cm_city                        = $ora1->city;
	$t->cm_state                       = $ora1->state;
	$t->cm_tmpdesc                     = $ora1->tmpdesc;
	$t->cm_assign_group2               = $ora1->assign_group2;
	$t->cm_assign_group3               = $ora1->assign_group3;
	$t->cm_implementor_name2           = $ora1->implementor_name2;
	$t->cm_implementor_name3           = $ora1->implementor_name3;
	$t->cm_groupid2                    = $ora1->groupid2;
	$t->cm_groupid3                    = $ora1->groupid3;
	$t->cm_template                    = $ora1->template;
	$t->cm_hd_outage_ticket_number     = $ora1->hd_outage_ticket_number;
	$t->cm_root_cause_owner            = $ora1->root_cause_owner;
	$t->cm_control_count               = $ora1->control_count;

	//
	// Dates are stored in Remedy as numbers (GMT). Remedy provide me with the function
	// fn_number_to_date() to convert the numbers to DATE objects. The first thing I do
	// is get the current maintain timezone which could be MST or MDT depending on the
	// time of year. The $tz value is then used in the function to current the ticket
	// to Mountain MST/MDT time. These dates are then stored in the CCT database in
	// cct6_tickets
	//
	$tz = "MDT";

	$query  = "select ";
	$query .= sprintf("  fn_number_to_date(create_date, '%s')             as create_date, ",             $tz);
	$query .= sprintf("  fn_number_to_date(start_date, '%s')              as start_date, ",              $tz);
	$query .= sprintf("  fn_number_to_date(end_date, '%s')                as end_date, ",                $tz);
	$query .= sprintf("  fn_number_to_date(close_date, '%s')              as close_date, ",              $tz);
	$query .= sprintf("  fn_number_to_date(last_modified, '%s')           as last_modified, ",           $tz);
	$query .= sprintf("  fn_number_to_date(late_date, '%s')               as late_date, ",               $tz);
	$query .= sprintf("  fn_number_to_date(last_status_change_time, '%s') as last_status_change_time, ", $tz);
	$query .= sprintf("  fn_number_to_date(turn_overdate, '%s')           as turn_overdate ",            $tz);
	$query .= sprintf("from t_cm_implementation_request@remedy_prod where change_id = '%s'", $cm_ticket_no);

	if ($ora1->sql($query) == false)
	{
		printf("%s - %d\n", $query, $ora1->dbErrMsg);
		exit();
	}

	if ($ora1->fetch() == false)
	{
		printf("Unable to pull ticket: %s\n", $cm_ticket_no);
		exit();
	}

	$t->cm_close_date              = $ora1->close_date;
	$t->cm_end_date                = $ora1->end_date;
	$t->cm_create_date             = $ora1->create_date;
	$t->cm_start_date              = $ora1->start_date;
	$t->cm_last_modified           = $ora1->last_modified;
	$t->cm_late_date               = $ora1->late_date;
	$t->cm_last_status_change_time = $ora1->last_status_change_time;
	$t->cm_turn_overdate           = $ora1->turn_overdate;

	//
	// Flags in Remedy are NUMBERS where 1 = YES, and NULL = NO. Convert these flags to "Y" or "N".
	// REMOVED *** 				"CASE WHEN risk                       is not NULL THEN 'Y' ELSE 'N' END as risk, " .
	//
	$query  = "select ";
	$query .= "  CASE WHEN ipl_boot                   = 1   THEN 'Y' ELSE 'N' END as ipl_boot, ";
	$query .= "  CASE WHEN late                       = 1   THEN 'Y' ELSE 'N' END as late, ";
	$query .= "  CASE WHEN tested                     = 1   THEN 'Y' ELSE 'N' END as tested, ";
	$query .= "  CASE WHEN tested_itv                 = 1   THEN 'Y' ELSE 'N' END as tested_itv, ";
	$query .= "  CASE WHEN tested_endtoend            = 1   THEN 'Y' ELSE 'N' END as tested_endtoend, ";
	$query .= "  CASE WHEN tested_development         = 1   THEN 'Y' ELSE 'N' END as tested_development, ";
	$query .= "  CASE WHEN tested_user                = 1   THEN 'Y' ELSE 'N' END as tested_user, ";
	$query .= "  CASE WHEN change_occurs              = 1   THEN 'Y' ELSE 'N' END as change_occurs, ";
	$query .= "  CASE WHEN cab_approval_required      = 1   THEN 'Y' ELSE 'N' END as cab_approval_required, ";
	$query .= "  CASE WHEN change_executive_team_flag = 1   THEN 'Y' ELSE 'N' END as change_executive_team_flag, ";
	$query .= "  CASE WHEN emergency_change           = 1   THEN 'Y' ELSE 'N' END as emergency_change, ";
	$query .= "  CASE WHEN tested_orl                 = 1   THEN 'Y' ELSE 'N' END as tested_orl, ";
	$query .= "  CASE WHEN featured_project           = 1   THEN 'Y' ELSE 'N' END as featured_project ";
	$query .= sprintf("from t_cm_implementation_request@remedy_prod where change_id = '%s'", $cm_ticket_no);

	if ($ora1->sql($query) == false)
	{
		printf("%s - %d\n", $query, $ora1->dbErrMsg);
		exit();
	}

	if ($ora1->fetch() == false)
	{
		printf("Unable to pull ticket part3 from Remedy: %s\n", $cm_ticket_no);
        exit();
	}

	$t->cm_ipl_boot                   = $ora1->ipl_boot;
	$t->cm_late                       = $ora1->late;
	$t->cm_plan_a_b                   = $ora1->plan_a_b;
	$t->cm_risk                       = $ora1->risk;
	$t->cm_tested_itv                 = $ora1->tested_itv;
	$t->cm_tested_endtoend            = $ora1->tested_endtoend;
	$t->cm_tested_development         = $ora1->tested_development;
	$t->cm_tested_user                = $ora1->tested_user;
	$t->cm_lla_refresh                = $ora1->lla_refresh;
	$t->cm_ims_cold_start             = $ora1->ims_cold_start;
	$t->cm_cab_approval_required      = $ora1->cab_approval_required;
	$t->cm_change_executive_team_flag = $ora1->change_executive_team_flag;
	$t->cm_emergency_change           = $ora1->emergency_change;
	$t->cm_tested_orl                 = $ora1->tested_orl;
	$t->cm_featured_project           = $ora1->featured_project;

	if ($ora1->sql("select * from cct6_mnet where mnet_cuid = lower('" . $t->cm_entered_by . "')") == false)
	{
		printf("%s - %d\n", $query, $ora1->dbErrMsg);
		exit();
	}

	if ($ora1->fetch())
	{
		$t->cm_owner_name       = $ora1->mnet_name;
		$t->cm_owner_first_name = $ora1->mnet_first_name;
		$t->cm_owner_last_name  = $ora1->mnet_last_name;

		$t->cm_owner_cuid       = $ora1->mnet_cuid;
		$t->cm_pager            = $ora1->mnet_pager;
		$t->cm_phone            = $ora1->mnet_work_phone;
	}

	$t->gen_request_start              = "07/27/2017 00:00";
	$t->gen_request_end                = "07/27/2017 00:15";
	$t->gen_request_duration           = "00:00:15";
	$t->gen_request_total_systems      = $tic->total_servers_scheduled;
	$t->gen_request_total_contacts     = 0;
	$t->ticket_submit_note             = $tic->note_to_clients;
	$t->ticket_use_os_maintwin         = $tic->disable_scheduler == "Y" ? "N" : "Y";
	$t->ticket_target_os               = "Y";
    $t->ticket_target_pase             = "Y";
    $t->ticket_target_dba              = "Y";
    $t->ticket_override_master         = "N";
    $t->ticket_target_dev_dba          = "N";
    $t->ticket_include_child_servers   = $tic->cm_ipl_boot == "Y" ? "Y" : "N";
    $t->ticket_include_vmware_servers  = $tic->exclude_virtual_contacts;

    //
    // Okay we should have everything we need to create a new cct6_ticket
    //
    // Next get the data for cct6_systems from cct7_systems
    //
    $ticket_no = $tic->ticket_no;

    $t->systems = null;
    $s          = null;   // link list of system records

	$rc = $ora2
		->select()
		->column('system_id')
		->column('ticket_no')
		->column('system_insert_date')
		->column('system_insert_cuid')
		->column('system_insert_name')
		->column('system_update_date')
		->column('system_update_cuid')
		->column('system_update_name')
		->column('system_lastid')
		->column('system_hostname')
		->column('system_os')
		->column('system_usage')
		->column('system_location')
		->column('system_timezone_name')
		->column('system_osmaint_weekly')
		->column('system_respond_by_date')
		->column('system_work_start_date')
		->column('system_work_end_date')
		->column('system_work_duration')
		->column('system_work_status')
		->column('total_contacts_responded')
		->column('total_contacts_not_responded')
		->column('original_work_start_date')
		->column('original_work_end_date')
		->column('original_work_duration')
		->column('disable_schedule')
		->from('cct7_systems')
		->where("char", "ticket_no", '=', $ticket_no)
		->execute();

	if ($rc == false)
    {
		printf("%s - %d\n", $query, $ora2->dbErrMsg);
		exit();
    }

    while ($ora2->fetch())
    {
        $system_id       = $ora2->system_id;
        $ticket_no       = $ora2->ticket_no;
        $system_lastid   = $ora2->system_lastid;
        $system_hostname = $ora2->system_hostname;

        //printf("%s, %s\n", $cm_ticket_no, $system_hostname);

        if ($sys->getSystem($system_id) == false)
        {
            printf("%s\n", $sys->error);
            exit();
        }

        if ($t->systems == null)
        {
			$t->systems = new data_node();
			$s = $t->systems;
        }
        else
        {
            $s->next = new data_node();
            $s = $s->next;
        }

		$s->system_id               = $ora2->system_id;  // Used to pull cct7 records
		$s->ticket_no               = $ora2->ticket_no;  // Used to pull cct7 records

		// $s->system_insert_date_num       = $ora2->system_insert_date;

		$s->system_insert_date      =
			$lib->gmt_to_format(
				$ora2->system_insert_date, 'm/d/Y H:i',
				'America/Denver');

		$s->system_insert_cuid           = $ora2->system_insert_cuid;
		$s->system_insert_name           = $ora2->system_insert_name;
		// $s->system_update_date_num       = $ora2->system_update_date;

		$s->system_update_date      =
			$lib->gmt_to_format(
				$ora2->system_update_date,
				'm/d/Y H:i',
				'America/Denver');

		$s->system_update_cuid           = $ora2->system_update_cuid;
		$s->system_update_name           = $ora2->system_update_name;

		//
        // Grab a new system record from cct6_computers
        //
        $query  = "select ";
        $query .= " * ";
        $query .= "from ";
        $query .= "  cct7_computers ";
        $query .= "where ";
        $query .= "  computer_lastid = " . $system_lastid;

        if ($ora3->sql2($query) == false)
        {
            printf("%s - %s\n", $ora3->sql_statement, $ora3->dbErrMsg);
            exit();
        }

        if ($ora3->fetch() == false)
        {
			$s->computer_lastid                = $sys->system_lastid;
			$s->computer_last_update           = "";
			$s->computer_install_date          = "";
			$s->computer_systemname            = $sys->system_hostname;
			$s->computer_hostname              = $sys->system_hostname;
			$s->computer_operating_system      = $sys->system_os;
			$s->computer_os_lite               = $sys->system_os;
			$s->computer_status                = $sys->system_usage;
			$s->computer_status_description    = "In Use";
			$s->computer_description           = $sys->system_usage;
			$s->computer_nature                = "SUPPORT";
			$s->computer_platform              = "MIDRANGE";
			$s->computer_type                  = "SERVER";
			$s->computer_clli                  = "";
			$s->computer_clli_fullname         = "";
			$s->computer_timezone              = $sys->system_timezone_name;
			$s->computer_building              = "";
			$s->computer_address               = "";
			$s->computer_city                  = $sys->system_location;
			$s->computer_state                 = "";
			$s->computer_floor_room            = "";
			$s->computer_grid_location         = "";
			$s->computer_lease_purchase        = "";
			$s->computer_serial_no             = "";
			$s->computer_asset_tag             = "";
			$s->computer_model_category        = "";
			$s->computer_model_no              = "";
			$s->computer_model                 = "";
			$s->computer_model_mfg             = "";
			$s->computer_cpu_type              = "";
			$s->computer_cpu_count             = "";
			$s->computer_cpu_speed             = "";
			$s->computer_memory_mb             = "";
			$s->computer_ip_address            = "";
			$s->computer_domain                = "";
			$s->computer_hostname_domain       = "";
			$s->computer_dmz                   = "";
			$s->computer_ewebars_title         = "";
			$s->computer_ewebars_status        = "";
			$s->computer_backup_format         = "";
			$s->computer_backup_nodename       = "";
			$s->computer_backup_program        = "";
			$s->computer_backup_server         = "";
			$s->computer_netbackup             = "";
			$s->computer_complex               = "";
			$s->computer_complex_lastid        = "";
			$s->computer_complex_name          = "";
			$s->computer_complex_parent_name   = "";
			$s->computer_complex_child_names   = "";
			$s->computer_complex_partitions    = "";
			$s->computer_service_guard         = "";
			$s->computer_os_group_contact      = "";
			$s->computer_cio_group             = "";
			$s->computer_managing_group        = "";
			$s->computer_contract              = "";
			$s->computer_contract_ref          = "";
			$s->computer_contract_status       = "";
			$s->computer_contract_status_type  = "";
			$s->computer_contract_date         = "";
			$s->computer_ibm_supported         = "";
			$s->computer_gold_server           = "";
			$s->computer_slevel_objective      = "";
			$s->computer_slevel_score          = "";
			$s->computer_slevel_colors         = "";
			$s->computer_special_handling      = "";
			$s->computer_applications          = "";
			$s->computer_osmaint_weekly        = $sys->system_osmaint_weekly;
			$s->computer_osmaint_monthly       = "";
			$s->computer_osmaint_quarterly     = "";
			$s->computer_csc_os_banners        = "";
			$s->computer_csc_pase_banners      = "";
			$s->computer_csc_dba_banners       = "";
			$s->computer_csc_fyi_banners       = "";
        }
        else
        {
			$s->computer_lastid               = $ora3->computer_lastid;
			$s->computer_last_update          = $ora3->computer_last_update;
			$s->computer_install_date         = $ora3->computer_install_date;
			$s->computer_systemname           = $ora3->computer_systemname;
			$s->computer_hostname             = $ora3->computer_hostname;
			$s->computer_operating_system     = $ora3->computer_operating_system;
			$s->computer_os_lite              = $ora3->computer_os_lite;
			$s->computer_status               = $ora3->computer_status;
			$s->computer_status_description   = $ora3->computer_status_description;
			$s->computer_description          = $ora3->computer_description;
			$s->computer_nature               = $ora3->computer_nature;
			$s->computer_platform             = $ora3->computer_platform;
			$s->computer_type                 = $ora3->computer_type;
			$s->computer_clli                 = $ora3->computer_clli;
			$s->computer_clli_fullname        = $ora3->computer_clli_fullname;
			$s->computer_timezone             = $ora3->computer_timezone;
			$s->computer_building             = $ora3->computer_building;
			$s->computer_address              = $ora3->computer_address;
			$s->computer_city                 = $ora3->computer_city;
			$s->computer_state                = $ora3->computer_state;
			$s->computer_floor_room           = $ora3->computer_floor_room;
			$s->computer_grid_location        = $ora3->computer_grid_location;
			$s->computer_lease_purchase       = $ora3->computer_lease_purchase;
			$s->computer_serial_no            = $ora3->computer_serial_no;
			$s->computer_asset_tag            = $ora3->computer_asset_tag;
			$s->computer_model_category       = $ora3->computer_model_category;
			$s->computer_model_no             = $ora3->computer_model_no;
			$s->computer_model                = $ora3->computer_model;
			$s->computer_model_mfg            = $ora3->computer_model_mfg;
			$s->computer_cpu_type             = $ora3->computer_cpu_type;
			$s->computer_cpu_count            = $ora3->computer_cpu_count;
			$s->computer_cpu_speed            = $ora3->computer_cpu_speed;
			$s->computer_memory_mb            = $ora3->computer_memory_mb;
			$s->computer_ip_address           = $ora3->computer_ip_address;
			$s->computer_domain               = $ora3->computer_domain;
			$s->computer_hostname_domain      = $ora3->computer_hostname_domain;
			$s->computer_dmz                  = $ora3->computer_dmz;
			$s->computer_ewebars_title        = $ora3->computer_ewebars_title;
			$s->computer_ewebars_status       = $ora3->computer_ewebars_status;
			$s->computer_backup_format        = $ora3->computer_backup_format;
			$s->computer_backup_nodename      = $ora3->computer_backup_nodename;
			$s->computer_backup_program       = $ora3->computer_backup_program;
			$s->computer_backup_server        = $ora3->computer_backup_server;
			$s->computer_netbackup            = $ora3->computer_netbackup;
			$s->computer_complex              = $ora3->computer_complex;
			$s->computer_complex_lastid       = $ora3->computer_complex_lastid;
			$s->computer_complex_name         = $ora3->computer_complex_name;
			$s->computer_complex_parent_name  = $ora3->computer_complex_parent_name;
			$s->computer_complex_child_names  = $ora3->computer_complex_child_names;
			$s->computer_complex_partitions   = $ora3->computer_complex_partitions;
			$s->computer_service_guard        = $ora3->computer_service_guard;
			$s->computer_os_group_contact     = $ora3->computer_os_group_contact;
			$s->computer_cio_group            = $ora3->computer_cio_group;
			$s->computer_managing_group       = $ora3->computer_managing_group;
			$s->computer_contract             = $ora3->computer_contract;
			$s->computer_contract_ref         = $ora3->computer_contract_ref;
			$s->computer_contract_status      = $ora3->computer_contract_status;
			$s->computer_contract_status_type = $ora3->computer_contract_status_type;
			$s->computer_contract_date        = $ora3->computer_contract_date;
			$s->computer_ibm_supported        = $ora3->computer_ibm_supported;
			$s->computer_gold_server          = $ora3->computer_gold_server;
			$s->computer_slevel_objective     = $ora3->computer_slevel_objective;
			$s->computer_slevel_score         = $ora3->computer_slevel_score;
			$s->computer_slevel_colors        = $ora3->computer_slevel_colors;
			$s->computer_special_handling     = $ora3->computer_special_handling;
			$s->computer_applications         = $ora3->computer_applications;
			$s->computer_osmaint_weekly       = $ora3->computer_osmaint_weekly;
			$s->computer_osmaint_monthly      = $ora3->computer_osmaint_monthly;
			$s->computer_osmaint_quarterly    = $ora3->computer_osmaint_quarterly;
			$s->computer_csc_os_banners       = $ora3->computer_csc_os_banners;
			$s->computer_csc_pase_banners     = $ora3->computer_csc_pase_banners;
			$s->computer_csc_dba_banners      = $ora3->computer_csc_dba_banners;
			$s->computer_csc_fyi_banners      = $ora3->computer_csc_fyi_banners;
        }

		//
		// Copy in the Schedule information
		//
        $s->system_work_status              = $sys->system_work_status;

        if ($sys->system_work_start_date_num == 0)
        {
			$s->system_actual_work_start	    = $t->cm_start_date;
			$s->system_actual_work_end	        = $t->cm_end_date;
			$s->system_actual_work_duration	    = $t->cm_duration_computed;
			$s->system_original_work_start	    = $t->cm_start_date;
			$s->system_original_work_end	    = $t->cm_end_date;
			$s->system_original_work_duration	= $t->cm_duration_computed;
        }
        else
        {
			$s->system_actual_work_start	    = $sys->system_work_start_date_char2;
			$s->system_actual_work_end	        = $sys->system_work_end_date_char2;
			$s->system_actual_work_duration	    = $sys->system_work_duration;
			$s->system_original_work_start	    = $sys->original_work_start_date_char2;
			$s->system_original_work_end	    = $sys->original_work_end_date_char2;
			$s->system_original_work_duration	= $sys->original_work_duration;
        }

        $s->system_email_notification_date	= $t->ticket_status         == 'ACTIVE'   ? $t->ticket_update_date : "";
        $s->system_work_cancel_date	        = $sys->system_work_status  == 'CANCELED' ? $t->ticket_update_date : "";
        $s->system_approvals_required	    = $t->ticket_approvals_required;
        $s->system_override_status_date	    = "";
        $s->system_override_status_cuid	    = "";
        $s->system_override_status_name		= "";
        $s->system_override_status_notes	= "";
        $s->system_completion_date		    = "";
        $s->system_completion_status		= "";
        $s->system_completion_cuid		    = "";
        $s->system_completion_name		    = "";
        $s->system_completion_notes		    = "";
	}

	//
    // Now go after the contact information. This is where it really gets interesting.
    //
    $system_hostname = $s->system_hostname;
    $s->contacts     = null;

	//
    // Using the original code from CCT6, retrieve the contact information.
    //
	$c               = null;

	$duplicate_contact = array();       // Used to weed out duplicates
	$override = 'N';

	//
	// Check for Master Approver Override for this host - cct6_master_approver
	// When there is a master approver all other contact approvers notify type is changed to FYI unless ticket_override_master = Y
	// The Master Approver will be the only person that is allowed to give the green light for the
	// pending work.
	//
	$query = "select " .
            "o.computer_hostname   as computer_hostname, " .
            "o.approver_cuid       as approver_cuid, " .
            "o.approver_name       as approver_name, " .
            "lower(m.mnet_cuid)    as mnet_cuid, " .
            "m.mnet_last_name      as mnet_last_name, " .
            "m.mnet_first_name     as mnet_first_name, " .
            "m.mnet_nick_name      as mnet_nick_name, " .
            "m.mnet_middle         as mnet_middle, " .
            "m.mnet_name           as mnet_name, " .
            "m.mnet_job_title      as mnet_job_title, " .
            "m.mnet_email          as mnet_email, " .
            "m.mnet_work_phone     as mnet_work_phone, " .
            "m.mnet_pager          as mnet_pager, " .
            "m.mnet_street         as mnet_street, " .
            "m.mnet_city           as mnet_city, " .
            "m.mnet_state          as mnet_state, " .
            "m.mnet_rc             as mnet_rc, " .
            "m.mnet_company        as mnet_company, " .
            "m.mnet_tier1          as mnet_tier1, " .
            "m.mnet_tier2          as mnet_tier2, " .
            "m.mnet_tier3          as mnet_tier3, " .
            "m.mnet_status         as mnet_status, " .
            "to_char(m.mnet_change_date, 'MM/DD/YYYY HH24:MI') as mnet_change_date, " .
            "m.mnet_ctl_cuid       as mnet_ctl_cuid, " .
            "m.mnet_mgr_cuid       as mnet_mgr_cuid " .
		"from " .
            "cct6_master_approvers o, " .
            "cct6_mnet m " .
		"where " .
            "m.mnet_cuid = o.approver_cuid and " .
            "o.computer_hostname = '" . $system_hostname . "'";

	if ($ora2->sql($query) == false)
	{
		printf("%s\n", $ora2->dbErrMsg);
		exit();
	}

	$master_approver_found = false;
	
	if ($ora2->fetch())
    {
        $master_approver_found = true;

        if ($s->contacts == null)
        {
			$s->contacts = new data_node();
			$c = $s->contacts;
        }
        else
        {
            $c->next = new data_node();
            $c = $c->next;
        }

		$c->contact_csc_banner = 'Override Used';            // VARCHAR2 (i.e. Application Support)
		$c->contact_app_acronym = 'ALL APPS';                // VARCHAR2 Application acronym (i.e. CCT)
		$c->contact_group_type = 'PASE';                     // VARCHAR2 OS, PASE, DBA, OTHER
		$c->contact_notify_type = 'APPROVER';                // VARCHAR2 APPROVER, FYI
		$c->contact_source = 'CCT Master Approver Override'; // VARCHAR2 CSC, CCT, On-Call
		$c->contact_cuid = strtolower($ora2->mnet_cuid);     // VARCHAR2 Contact CUID login name
		$c->contact_override = 'Y';                          // VARCHAR2 Override used? Y or N
		$c->contact_last_name = $ora2->mnet_last_name;       // VARCHAR2 Contact last name
		$c->contact_first_name = $ora2->mnet_first_name;     // VARCHAR2 Contact first name
		$c->contact_nick_name = $ora2->mnet_nick_name;       // VARCHAR2 Contact nick name
		$c->contact_middle = $ora2->mnet_middle;             // VARCHAR2 Contact middle name

		$c->contact_name = $ora2->mnet_name;                 // VARCHAR2 Contact name
		$c->contact_job_title = $ora2->mnet_job_title;       // VARCHAR2 Contact Job Title
		$c->contact_email = $ora2->mnet_email;               // VARCHAR2 Contact email address
		$c->contact_work_phone = $ora2->mnet_work_phone;     // VARCHAR2 Contact work phone number
		$c->contact_pager = $ora2->mnet_pager;               // VARCHAR2 Contact pager number
		$c->contact_street = $ora2->mnet_street;             // VARCHAR2 Contact street
		$c->contact_city = $ora2->mnet_city;                 // VARCHAR2 Contact City
		$c->contact_state = $ora2->mnet_state;               // VARCHAR2 Contact State
		$c->contact_rc = $ora2->mnet_rc;                     // VARCHAR2 Contact RC
		$c->contact_company = $ora2->mnet_company;           // VARCHAR2 Contact company name
		$c->contact_tier1 = $ora2->mnet_tier1;               // VARCHAR2 Contact tier1 support information
		$c->contact_tier2 = $ora2->mnet_tier2;               // VARCHAR2 Contact tier2 support information
		$c->contact_tier3 = $ora2->mnet_tier3;               // VARCHAR2 Contact tier3 support information
		$c->contact_status = $ora2->mnet_status;             // VARCHAR2 Contact employee status
		$c->contact_change_date = $ora2->mnet_change_date;   // DATE     MNET information change date
		$c->contact_ctl_cuid = $ora2->mnet_ctl_cuid;         // VARCHAR2 Contact CTL sponsor CUID person
		$c->contact_mgr_cuid = $ora2->mnet_mgr_cuid;         // VARCHAR2 Contact Manager CUID person

		$duplicate_contact[$ora2->mnet_cuid] = 'got it';     // $ora2->mnet_name;
    }

	//
	// Add contacts from CSC that are identified under the following CSC Banners (cct_csc_group_name)
	//
	$query = "select " .
            "cct_csc_netgroup, " .
            "cct_app_acronym, " .
            "lower(cct_csc_userid_1) as cct_csc_userid_1, " .
            "lower(cct_csc_userid_2) as cct_csc_userid_2, " .
            "lower(cct_csc_userid_3) as cct_csc_userid_3, " .
            "lower(cct_csc_userid_4) as cct_csc_userid_4, " .
            "lower(cct_csc_userid_5) as cct_csc_userid_5, " .
            "cct_csc_group_name, " .
            "lower(cct_csc_oncall) as cct_csc_oncall " .
		"from " .
            "cct6_csc " .
		"where " .
            "lastid = " . $s->computer_lastid . " and ( " .
            "cct_csc_group_name = 'MiddleWare Support' or " .
            "cct_csc_group_name = 'Development Support' or " .
            "cct_csc_group_name = '! Operating System Support' or " .
            "cct_csc_group_name = '! Database Support' or " .
            "cct_csc_group_name = '! Development Database Support' or " .
            "cct_csc_group_name = 'Application Support' or " .
            "cct_csc_group_name = 'E2E Support' or " .
            "cct_csc_group_name = 'Infrastructure' or " .
            "cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or " .
            "cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or " .
            "cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)' ) " .
		"order by cct_csc_group_name";

	$top_csc = $p_csc = null;

	if ($ora2->sql($query) == false)
	{
		printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
		exit();
	}

	while ($ora2->fetch())
	{
		//
		// Do not include any contacts that the user creating the new work request does not want to target.
		//	
		if ($t->ticket_target_os == 'N' && $ora2->cct_csc_group_name == '! Operating System Support')
		{
			continue;
		}

		if ($t->ticket_target_dba == 'N' && $ora2->cct_csc_group_name == '! Database Support')
		{
			continue;
		}

		if ($t->ticket_target_dev_dba == 'N' && $ora2->cct_csc_group_name == '! Development Database Support')
		{
			continue;
		}

		if ($t->ticket_target_pase == 'N' &&
			(   $ora2->cct_csc_group_name == 'MiddleWare Support' or
				$ora2->cct_csc_group_name == 'Application Support' or
				$ora2->cct_csc_group_name == 'E2E Support' or
				$ora2->cct_csc_group_name == 'Infrastructure' or
				$ora2->cct_csc_group_name == 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or
				$ora2->cct_csc_group_name == 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or
				$ora2->cct_csc_group_name == 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)'))
		{
			continue;
		}

		//
		// Add this contact to the list of contacts for this server.
		//
		if ($top_csc == null)
		{
			$top_csc = $p_csc = new data_node();
		}
		else
		{
			$p_csc->next = new data_node();
			$p_csc = $p_csc->next;
		}

		$p_csc->cct_csc_netgroup = $ora2->cct_csc_netgroup;                                 // VARCHAR2 cct6_contacts.contact_csc_banner
		$p_csc->cct_app_acronym = $ora2->cct_app_acronym;                                   // VARCHAR2 cct6_contacts.contact_app_acronym
		$p_csc->cct_csc_userid_1 = strtolower($ora2->cct_csc_userid_1);                     // VARCHAR2
		$p_csc->cct_csc_userid_2 = strtolower($ora2->cct_csc_userid_2);                     // VARCHAR2
		$p_csc->cct_csc_userid_3 = strtolower($ora2->cct_csc_userid_3);                     // VARCHAR2
		$p_csc->cct_csc_userid_4 = strtolower($ora2->cct_csc_userid_4);                     // VARCHAR2
		$p_csc->cct_csc_userid_5 = strtolower($ora2->cct_csc_userid_5);                     // VARCHAR2
		$p_csc->cct_csc_group_name = $ora2->cct_csc_group_name;                             // VARCHAR2
		$p_csc->cct_csc_oncall = strtolower($ora2->cct_csc_oncall);                         // VARCHAR2
	}

	//
	// Figure out what contact to use for each group based upon CMP policy rules.
	//
	for ($p_csc=$top_csc; $p_csc!=null; $p_csc=$p_csc->next)
	{
		if ($p_csc->cct_csc_group_name == 'Development Support')
		{
			$notify_type = 'FYI';
			$group_type = 'PASE';
		}
		else if ($p_csc->cct_csc_group_name == 'MiddleWare Support')
		{
			$notify_type = 'APPROVER';
			$group_type = 'PASE';
		}
		else if ($p_csc->cct_csc_group_name == '! Operating System Support')
		{
			$notify_type = 'APPROVER';
			$group_type = 'OS';
		}
		else if ($p_csc->cct_csc_group_name == '! Database Support')
		{
			$notify_type = 'APPROVER';
			$group_type = 'DBA';
		}
		else if ($p_csc->cct_csc_group_name == '! Development Database Support')
		{
			$notify_type = 'APPROVER';
			$group_type = 'DBA';
		}
		else if ($p_csc->cct_csc_group_name == 'Application Support')
		{
			$notify_type = 'APPROVER';
			$group_type = 'PASE';
		}
		else if ($p_csc->cct_csc_group_name == 'E2E Support')
		{
			$notify_type = 'FYI';
			$group_type = 'PASE';
		}
		else if ($p_csc->cct_csc_group_name == 'Infrastructure')
		{
			$notify_type = 'APPROVER';
			$group_type = 'PASE';
		}
		else if ($p_csc->cct_csc_group_name == 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)')
		{
			$notify_type = 'APPROVER';
			$group_type = 'PASE';
		}
		else if ($p_csc->cct_csc_group_name == 'Applications or Databases Desiring Notification (Not Hosted on this Server)')
		{
			$notify_type = 'FYI';
			$group_type = 'PASE';
		}
		else if ($p_csc->cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)')
		{
			$notify_type = 'APPROVER';
			$group_type = 'PASE';
		}

		$got_contact = false;
		$cct_csc_oncall = '';
		$cct_csc_netgroup = $ora2->cct_csc_netgroup;
		$source = '';
		$override = 'N';
		$p_override = null;
		$app_acronym = $p_csc->cct_app_acronym;

		//
		// Rule 1: Use CSC Primary contact if it exists
		//
		if      (foundInMNET($p_csc->cct_csc_userid_1))
		{
			$cct_csc_oncall = $p_csc->cct_csc_userid_1;
			$source = 'CSC Primary';
			$got_contact = true;
		}
		else if (foundInMNET($p_csc->cct_csc_userid_2))
		{
			$cct_csc_oncall = $p_csc->cct_csc_userid_2;
			$source = 'CSC Backup1';
			$got_contact = true;
		}
		else if (foundInMNET($p_csc->cct_csc_userid_3))
		{
			$cct_csc_oncall = $p_csc->cct_csc_userid_3;
			$source = 'CSC Backup2';
			$got_contact = true;
		}
		else if (foundInMNET($p_csc->cct_csc_userid_4))
		{
			$cct_csc_oncall = $p_csc->cct_csc_userid_4;
			$source = 'CSC Backup3';
			$got_contact = true;
		}
		else if (foundInMNET($p_csc->cct_csc_userid_5))
		{
			$cct_csc_oncall = $p_csc->cct_csc_userid_5;
			$source = 'CSC Backup4';
			$got_contact = true;
		}
		//
		// Rule 2: Net Group primary oncall person (via. interface to NET-Tool) See table: cct6_oncall_overrides
		//
		else if (($p_override = getOncallOverride($p_csc->cct_csc_netgroup)) != null)  // cct_csc_netgroup is the net-pin number
		{
			$cct_csc_oncall = trim($p_override->mnet_cuid);

			if (foundInMnet($cct_csc_oncall))
			{
				$override = 'Y';
				$source = 'CCT Override Pin(' . $p_csc->cct_csc_netgroup . ')';
				$got_contact = true;
			}
		}
		else if (foundInMNET($p_csc->cct_csc_oncall))
		{
			$cct_csc_oncall = $p_csc->cct_csc_oncall;
			$cct_csc_netgroup = $p_csc->cct_csc_netgroup;
			$source = "CSC/NetTool-" . $p_csc->cct_csc_netgroup;
			$got_contact = true;
		}
		//
		// Rule 3: Asset Center (cct6_computers)
		//
		else if (strlen($p_csc->cct_csc_oncall) == 0 && $group_type == 'OS' && strlen($s->computer_os_group_contact) > 0)
		{
			if (foundInMNET($s->computer_os_group_contact))
			{
				$cct_csc_oncall = $s->computer_os_group_contact;
				$source = "Asset Center";
				$got_contact = true;
			}
		}

		if ($got_contact == true)
		{
			//
			// Combind the data for this individual into one record if a previous record for this person 
			// has already been added.
			//
			if (array_key_exists($cct_csc_oncall, $duplicate_contact))
			{
				//
				// Find the record in this contact list
				//
				for ($p=$s->contacts; $p!=null; $p=$p->next)
				{
					if ($p->contact_cuid == $cct_csc_oncall)
						break;
				}

				if ($p == null)
				{
					continue;
				}

				$p->contact_csc_netpin = $cct_csc_netgroup;

				//
				// Consolidate the data into one contact record for this individual
				//
				if ($group_type == 'PASE')
				{
					$p->contact_group_type = 'PASE';                // VARCHAR2 OS, PASE, DBA, OTHER
				}
				else if ($group_type == 'DBA' && $p->contact_group_type != 'PASE')
				{
					$p->contact_group_type = 'DBA';                // VARCHAR2 OS, PASE, DBA, OTHER
				}
				else if ($group_type == 'OS' && ($p->contact_group_type != 'PASE' && $p->contact_group_type != 'DBA'))
				{
					$p->contact_group_type = 'OS';
				}

				if ($notify_type == 'APPROVER')
				{
					$p->contact_notify_type = 'APPROVER';          // VARCHAR2 APPROVER, FYI
				}

				if (strlen($p->contact_app_acronym) == 0 && strlen($app_acronym) > 0)
				{
					$p->contact_app_acronym = $app_acronym;
				}
				else
				{
					$p->contact_app_acronym .= " " . $app_acronym;
				}

				continue;
			}

			// Record that we have created a record in our contact link list for this individual
			$duplicate_contact[$cct_csc_oncall] = 'got it';

			// Grab the MNET data for this person so we can copy it into our contact list
			if (($m = getMNET($cct_csc_oncall)) == null)
			{
				continue;
			}

			//
			// Add it to the contact list
			//
			if ($s->contacts == null)
			{
				$s->contacts = new data_node();
				$c = $s->contacts;
			}
			else
			{
				$c->next = new data_node();
				$c = $c->next;
			}

			$c->contact_csc_banner = fixBanner($p_csc->cct_csc_group_name); // VARCHAR2 (i.e. Application Support)
			$c->contact_app_acronym = $p_csc->cct_app_acronym;                     // VARCHAR2 Application acronym (i.e. CCT)
			$c->contact_group_type = $group_type;                                  // VARCHAR2 OS, PASE, DBA, OTHER
			$c->contact_notify_type = $notify_type;                                // VARCHAR2 APPROVER, FYI
			$c->contact_source = $source;                                          // VARCHAR2 CSC, CCT, On-Call
			$c->contact_override = $override;                                      // VARCHAR2 Override used? Y or N
			$c->contact_cuid = strtolower($m->mnet_cuid);                          // VARCHAR2 Contact CUID login name
			$c->contact_last_name = $m->mnet_last_name;                            // VARCHAR2 Contact last name
			$c->contact_first_name = $m->mnet_first_name;                          // VARCHAR2 Contact first name
			$c->contact_nick_name = $m->mnet_nick_name;                            // VARCHAR2 Contact nick name
			$c->contact_middle = $m->mnet_middle;                                  // VARCHAR2 Contact middle name
			$c->contact_name = $m->mnet_name;                                      // VARCHAR2 Contact name
			$c->contact_job_title = $m->mnet_job_title;                            // VARCHAR2 Contact Job Title
			$c->contact_email = $m->mnet_email;                                    // VARCHAR2 Contact email address
			$c->contact_work_phone = $m->mnet_work_phone;                          // VARCHAR2 Contact work phone number
			$c->contact_pager = $m->mnet_pager;                                    // VARCHAR2 Contact pager number
			$c->contact_street = $m->mnet_street;                                  // VARCHAR2 Contact street
			$c->contact_city = $m->mnet_city;                                      // VARCHAR2 Contact City
			$c->contact_state = $m->mnet_state;                                    // VARCHAR2 Contact State
			$c->contact_rc = $m->mnet_rc;                                          // VARCHAR2 Contact RC
			$c->contact_company = $m->mnet_company;                                // VARCHAR2 Contact company name
			$c->contact_tier1 = $m->mnet_tier1;                                    // VARCHAR2 Contact tier1 support information
			$c->contact_tier2 = $m->mnet_tier2;                                    // VARCHAR2 Contact tier2 support information
			$c->contact_tier3 = $m->mnet_tier3;                                    // VARCHAR2 Contact tier3 support information
			$c->contact_status = $m->mnet_status;                                  // VARCHAR2 Contact employee status
			$c->contact_change_date = $m->mnet_change_date;                        // DATE     MNET information change date
			$c->contact_ctl_cuid = $m->mnet_ctl_cuid;                              // VARCHAR2 Contact CTL sponsor CUID person
			$c->contact_mgr_cuid = $m->mnet_mgr_cuid;                              // VARCHAR2 Contact Manager CUID person

			$c->contact_csc_netpin = $cct_csc_netgroup;	 // see lines: 2160 and 2222 (or there abouts)
		} // if ($got_contact == true)		
	} // for ($p_csc=$top_csc; $p_csc!=null; $p_csc=$p_csc->next)

    //
    // Pull in subscriber list users
    //
	if (($top_subscribers = getSubscribers($t->computer_hostname)) != null)
	{
		for ($p_subscriber=$top_subscribers; $p_subscriber!=null; $p_subscriber=$p_subscriber->next)
		{
			//
			// Combind the data for this individual into one record if a previous record for this person has already been added.
			//
			if (array_key_exists($p_subscriber->mnet_cuid, $duplicate_contact))
			{
				//
				// Find the record in this contact list
				//
				for ($p=$s->contacts; $p!=null; $p=$p->next)
				{
					if ($p->contact_cuid == $p_subscriber->mnet_cuid)
						break;
				}

				if ($p == null)
				{
					continue;
				}

				//
				// Consolidate the data into one contact record for this individual
				//
				if ($p_subscriber->group_type == 'PASE')
				{
					$p->contact_group_type = 'PASE';                // VARCHAR2 OS, PASE, DBA, OTHER
				}
				else if ($p_subscriber->group_type == 'DBA' && $p->contact_group_type != 'PASE')
				{
					$p->contact_group_type = 'DBA';                // VARCHAR2 OS, PASE, DBA, OTHER
				}
				else if ($p_subscriber->group_type == 'OS' && ($p->contact_group_type != 'PASE' && $p->contact_group_type != 'DBA'))
				{
					$p->contact_group_type = 'OS';
				}

				if ($p_subscriber->notify_type == 'APPROVER')
				{
					$p->contact_notify_type = 'APPROVER';          // VARCHAR2 APPROVER, FYI
				}

				continue;
			} // if (array_key_exists($p_subscriber->mnet_cuid, $duplicate_contact))

			// Note that we have created a record in our contact link list for this individual
			$duplicate_contact[$p_subscriber->mnet_cuid] = 'got it';

			//
			// Add it to the contact list
			//
			if ($s->contacts == null)
			{
				$s->contacts = new data_node();
				$c = $s->contacts;
			}
			else
			{
				$c->next = new data_node();
				$c = $c->next;
			}

			$c->contact_csc_banner = 'Subscriber: ' . $p_subscriber->notify_type;   // VARCHAR2 (i.e. Application Support)
			$c->contact_app_acronym = 'NA';                                         // VARCHAR2 Application acronym (i.e. CCT)
			$c->contact_group_type = $p_subscriber->group_type;                     // VARCHAR2 OS, PASE, DBA, OTHER
			$c->contact_notify_type = $p_subscriber->notify_type;                   // VARCHAR2 APPROVER, FYI
			$c->contact_source = 'CCT Subscriber';                                  // VARCHAR2 CSC, CCT, On-Call
			$c->contact_override = $override;                                       // VARCHAR2 Override used? Y or N
			$c->contact_cuid = strtolower($p_subscriber->mnet_cuid);                // VARCHAR2 Contact CUID login name
			$c->contact_last_name = $p_subscriber->mnet_last_name;                  // VARCHAR2 Contact last name
			$c->contact_first_name = $p_subscriber->mnet_first_name;                // VARCHAR2 Contact first name
			$c->contact_nick_name = $p_subscriber->mnet_nick_name;                  // VARCHAR2 Contact nick name
			$c->contact_middle = $p_subscriber->mnet_middle;                        // VARCHAR2 Contact middle name
			$c->contact_name = $p_subscriber->mnet_name;                            // VARCHAR2 Contact name
			$c->contact_job_title = $p_subscriber->mnet_job_title;                  // VARCHAR2 Contact Job Title
			$c->contact_email = $p_subscriber->mnet_email;                          // VARCHAR2 Contact email address
			$c->contact_work_phone = $p_subscriber->mnet_work_phone;                // VARCHAR2 Contact work phone number
			$c->contact_pager = $p_subscriber->mnet_pager;                          // VARCHAR2 Contact pager number
			$c->contact_street = $p_subscriber->mnet_street;                        // VARCHAR2 Contact street
			$c->contact_city = $p_subscriber->mnet_city;                            // VARCHAR2 Contact City
			$c->contact_state = $p_subscriber->mnet_state;                          // VARCHAR2 Contact State
			$c->contact_rc = $p_subscriber->mnet_rc;                                // VARCHAR2 Contact RC
			$c->contact_company = $p_subscriber->mnet_company;                      // VARCHAR2 Contact company name
			$c->contact_tier1 = $p_subscriber->mnet_tier1;                          // VARCHAR2 Contact tier1 support information
			$c->contact_tier2 = $p_subscriber->mnet_tier2;                          // VARCHAR2 Contact tier2 support information
			$c->contact_tier3 = $p_subscriber->mnet_tier3;                          // VARCHAR2 Contact tier3 support information
			$c->contact_status = $p_subscriber->mnet_status;                        // VARCHAR2 Contact employee status
			$c->contact_change_date = $p_subscriber->mnet_change_date;              // DATE     MNET information change date
			$c->contact_ctl_cuid = $p_subscriber->mnet_ctl_cuid;                    // VARCHAR2 Contact CTL sponsor CUID person
			$c->contact_mgr_cuid = $p_subscriber->mnet_mgr_cuid;                    // VARCHAR2 Contact Manager CUID person
		} // for ($p_subscriber=$top_subscribers; $p_subscriber!=null; $p_subscriber=$p_subscriber->next)
	} // if (($top_subscribers = $getSubscribers($s->computer_hostname)) != null)
    
    // 
    // Using the new contact information, see what response status should be set based upon the data
    // found in cct7_contacts by calling $con->getContact($contact_id)
    //
	$query  = "select ";
	$query .= "  contact_id ";
	$query .= "from ";
	$query .= "  cct7_contacts ";
	$query .= "where ";
	$query .= "  system_id = " . $s->system_id;

	if ($ora2->sql2($query) == false)
    {
		printf("%s - %s\n", $ora2->sql_statement, $ora2->dbErrMsg);
		exit();
    }

    while ($ora2->fetch())
    {
        if ($con->getContactByContactId($ora2->contact_id) == false)
        {
            printf("%s\n", $con->error);
            exit();
        }

        for ($c=$s->contacts; $c!=null; $c=$c->next)
        {
            if ($c->contact_csc_netpin == $con->contact_netpin_no)
            {
				$c->contact_response_status	= $con->contact_response_status;
				$c->contact_response_date	= $con->contact_response_date_char2;
				$c->contact_insert_cuid	    = $con->contact_insert_cuid;
				$c->contact_insert_name	    = $con->contact_insert_name;
				$c->contact_insert_date	    = $con->contact_insert_date_char2;
				$c->contact_update_cuid	    = $con->contact_update_cuid;
				$c->contact_update_name	    = $con->contact_update_name;
				$c->contact_update_date	    = $con->contact_update_date_char2;

                if ($t->ticket_status == "ACTIVE")
                {
					$c->contact_email_send_date	= $t->ticket_insert_date;
                }
                else
                {
					$c->contact_email_send_date	= "";
                }

                if ($con->contact_response_status != "WAITING" && $con->contact_response_status != "FYI")
                {
					$c->contact_email_read_date	= $con->contact_update_date_char2;
                }
                else
                {
                    $c->contact_email_read_date = "";
                }

				$c->contact_page_me	        = $con->contact_send_page;
				$c->contact_email_me	    = $con->contact_send_email;
				$c->contact_csc_netpin	    = $con->contact_netpin_no;

                continue;
            }
        }
    }

	// $ticket_list[$cm_ticket_no] = $t;

    $system_id  = 0;
	$contact_id = 0;

	printf("\nTicket: %s    (%d of %d)\n", $t->cm_ticket_no, $ticket_count, $total_tickets);

	$tic->addTicketCCT6($t);

	for ($s=$t->systems; $s!=null; $s=$s->next)
	{
		printf("  system: %s\n", $s->computer_hostname);

		$system_id = 0;
		$sys->addSystemCCT6($t, $s, $system_id); // method returns new value for $system_id

		for ($c=$s->contacts; $c!=null; $c=$c->next)
		{
			printf("    contact: %s\n", $c->contact_cuid);

			$contact_id = 0;
			$con->addContactCCT6($c, $system_id, $contact_id); // method returns new value for $contact_id
		}

		AddEvents($t, $s->system_id, $system_id);
	}

    unset($t);  // Free the link list

	//break;
}

printf("\nAll done!\n");
exit();

function AddEvents($t, $system_id, $new_system_id)
{
    global $ora2, $ora3, $lib;

    //printf("system_id = %d\n", $system_id);

    //
    // Copy all the Ticket event messages to cct6_event_log
    //
	$query  = "select ";
	$query .= "  ticket_no, ";
	$query .= "  event_date, ";
	$query .= "  event_cuid, ";
	$query .= "  event_name, ";
	$query .= "  event_type, ";
	$query .= "  event_message ";
	$query .= "from ";
	$query .= "  cct7_log_tickets ";
	$query .= "where ";
	$query .= "  ticket_no = '" . $t->ticket_no . "' ";
	$query .= "order by ";
	$query .= "  event_date desc";

	if ($ora2->sql2($query) == false)
    {
        printf("%s\n%s\n", $query, $ora2->dbErrMsg);
        exit();
    }

    while ($ora2->fetch())
    {
        printf("  event: %s - %s\n", $ora2->event_type, $ora2->event_message);

		$event_date = $lib->gmt_to_format($ora2->event_date, 'm/d/Y H:i', 'MDT');

		$insert = "insert into cct6_event_log (system_id, " .
			"user_cuid, user_name, user_email, user_company, " .
			"manager_cuid, manager_name, manager_email, manager_company, " .
			"event_date, event_type, event_message) values (";

		$lib->makeInsertINT(     $insert, $new_system_id,           true);  // system_id

		$lib->makeInsertCHAR(    $insert, $ora2->event_cuid,        true);  // user_cuid
		$lib->makeInsertCHAR(    $insert, $ora2->event_name,        true);  // user_name
		$lib->makeInsertCHAR(    $insert, "",                  true);  // user_email
		$lib->makeInsertCHAR(    $insert, "",                  true);  // user_company
		$lib->makeInsertCHAR(    $insert, "",                  true);  // manager_cuid
		$lib->makeInsertCHAR(    $insert, "",                  true);  // manager_name
		$lib->makeInsertCHAR(    $insert, "",                  true);  // manager_email
		$lib->makeInsertCHAR(    $insert, "",                  true);  // manager_company
		$lib->makeInsertDateTIME($insert, $event_date,              true);  // ticket_insert_date
		$lib->makeInsertCHAR(    $insert, $ora2->event_type,        true);  // event_type
		$lib->makeInsertCHAR(    $insert, $ora2->event_message,     false); // event_message

		$insert .= ")";

		if ($ora3->sql($insert) == false)
		{
			printf("%s\n%s\n", $insert, $ora3->dbErrMsg);
            return false;
		}
    }

    //
    // Copy all the system events to cct6_event_log
    //
	$query  = "select ";
	$query .= "  ticket_no, ";
	$query .= "  system_id, ";
	$query .= "  hostname, ";
	$query .= "  event_date, ";
	$query .= "  event_cuid, ";
	$query .= "  event_name, ";
	$query .= "  event_type, ";
	$query .= "  event_message ";
	$query .= "from ";
	$query .= "  cct7_log_systems ";
	$query .= "where ";
	$query .= "  system_id = " . $system_id . " ";
	$query .= "order by ";
	$query .= "  event_date desc";

	if ($ora2->sql2($query) == false)
	{
		printf("%s\n%s\n", $query, $ora2->dbErrMsg);
		exit();
	}

	while ($ora2->fetch())
    {
		printf("  event: %s - %s\n", $ora2->event_type, $ora2->event_message);

		$event_date = $lib->gmt_to_format($ora2->event_date, 'm/d/Y H:i', 'MDT');

		$insert = "insert into cct6_event_log (system_id, " .
			"user_cuid, user_name, user_email, user_company, " .
			"manager_cuid, manager_name, manager_email, manager_company, " .
			"event_date, event_type, event_message) values (";

		$lib->makeInsertINT(     $insert, $new_system_id,           true);  // system_id

		$lib->makeInsertCHAR(    $insert, $ora2->event_cuid,        true);  // user_cuid
		$lib->makeInsertCHAR(    $insert, $ora2->event_name,        true);  // user_name
		$lib->makeInsertCHAR(    $insert, "",                  true);  // user_email
		$lib->makeInsertCHAR(    $insert, "",                  true);  // user_company
		$lib->makeInsertCHAR(    $insert, "",                  true);  // manager_cuid
		$lib->makeInsertCHAR(    $insert, "",                  true);  // manager_name
		$lib->makeInsertCHAR(    $insert, "",                  true);  // manager_email
		$lib->makeInsertCHAR(    $insert, "",                  true);  // manager_company
		$lib->makeInsertDateTIME($insert, $event_date,              true);  // ticket_insert_date
		$lib->makeInsertCHAR(    $insert, $ora2->event_type,        true);  // event_type
		$lib->makeInsertCHAR(    $insert, $ora2->event_message,     false); // event_message

		$insert .= ")";

		if ($ora3->sql($insert) == false)
		{
			printf("%s\n%s\n", $insert, $ora3->dbErrMsg);
			return false;
		}
    }

	//
	// Copy all the Contact event messages to cct6_event_log
	//
	$query  = "select ";
	$query .= "  ticket_no, ";
	$query .= "  system_id, ";
	$query .= "  hostname, ";
	$query .= "  netpin_no, ";
	$query .= "  event_date, ";
	$query .= "  event_cuid, ";
	$query .= "  event_name, ";
	$query .= "  event_type, ";
	$query .= "  event_message ";
	$query .= "from ";
	$query .= "  cct7_log_contacts ";
	$query .= "where ";
	$query .= "  system_id = " . $system_id . " ";
	$query .= "order by ";
	$query .= "  event_date desc";

	if ($ora2->sql2($query) == false)
	{
		printf("%s\n%s\n", $query, $ora2->dbErrMsg);
		exit();
	}

	while ($ora2->fetch())
	{
		printf("  event: %s - %s\n", $ora2->event_type, $ora2->event_message);

		$event_date = $lib->gmt_to_format($ora2->event_date, 'm/d/Y H:i', 'MDT');

		$insert = "insert into cct6_event_log (system_id, " .
			"user_cuid, user_name, user_email, user_company, " .
			"manager_cuid, manager_name, manager_email, manager_company, " .
			"event_date, event_type, event_message) values (";

		$lib->makeInsertINT(     $insert, $new_system_id,           true);  // system_id

		$lib->makeInsertCHAR(    $insert, $ora2->event_cuid,        true);  // user_cuid
		$lib->makeInsertCHAR(    $insert, $ora2->event_name,        true);  // user_name
		$lib->makeInsertCHAR(    $insert, "",                  true);  // user_email
		$lib->makeInsertCHAR(    $insert, "",                  true);  // user_company
		$lib->makeInsertCHAR(    $insert, "",                  true);  // manager_cuid
		$lib->makeInsertCHAR(    $insert, "",                  true);  // manager_name
		$lib->makeInsertCHAR(    $insert, "",                  true);  // manager_email
		$lib->makeInsertCHAR(    $insert, "",                  true);  // manager_company
		$lib->makeInsertDateTIME($insert, $event_date,              true);  // ticket_insert_date
		$lib->makeInsertCHAR(    $insert, $ora2->event_type,        true);  // event_type
		$lib->makeInsertCHAR(    $insert, $ora2->event_message,     false); // event_message

		$insert .= ")";

		if ($ora3->sql($insert) == false)
		{
			printf("%s\n%s\n", $insert, $ora3->dbErrMsg);
			return false;
		}
	}

	return false;
}

function foundInMNET($cuid)
{
    global $ora3, $mnet;

	// If we have a copy of this MNET record then no need to query the mnet table.
	if (array_key_exists($cuid, $mnet))
		return true;

	$query = "select " .
		"lower(mnet_cuid) as mnet_cuid, " .
		"mnet_last_name, " .
		"mnet_first_name, " .
		"mnet_nick_name, " .
		"mnet_middle, " .
		"mnet_name, " .
		"mnet_job_title, " .
		"mnet_email, " .
		"mnet_work_phone, " .
		"mnet_pager, " .
		"mnet_street, " .
		"mnet_city, " .
		"mnet_state, " .
		"mnet_rc, " .
		"mnet_company, " .
		"mnet_tier1, " .
		"mnet_tier2, " .
		"mnet_tier3, " .
		"mnet_status, " .
		"to_char(mnet_change_date, 'MM/DD/YYYY HH24:MI'), " .
		"mnet_ctl_cuid, " .
		"mnet_mgr_cuid " .
		"from " .
		"cct6_mnet " .
		"where " .
		"lower(mnet_cuid) = '" . strtolower($cuid) . "'";

	if ($ora3->sql($query) == false)
	{
		printf("%s - %s\n", $ora3->sql_statement, $ora3->dbErrMsg);
		return false;
	}

	if ($ora3->fetch())
	{
		$p = new data_node();

		$p->mnet_cuid = strtolower($ora3->mnet_cuid);       // VARCHAR2 User ID
		$p->mnet_last_name = $ora3->mnet_last_name;         // VARCHAR2 Last name
		$p->mnet_first_name = $ora3->mnet_first_name;       // VARCHAR2 First name
		$p->mnet_nick_name = $ora3->mnet_nick_name;         // VARCHAR2 Nick name
		$p->mnet_middle = $ora3->mnet_middle;               // VARCHAR2 Middle name
		$p->mnet_name = $ora3->mnet_name;                   // VARCHAR2 Full name
		$p->mnet_job_title = $ora3->mnet_job_title;         // VARCHAR2 Job Title
		$p->mnet_email = $ora3->mnet_email;                 // VARCHAR2 Email Address
		$p->mnet_work_phone = $ora3->mnet_work_phone;       // VARCHAR2 Work phone number
		$p->mnet_pager = $ora3->mnet_pager;                 // VARCHAR2 Pager number
		$p->mnet_street = $ora3->mnet_street;               // VARCHAR2 Street address
		$p->mnet_city = $ora3->mnet_city;                   // VARCHAR2 City
		$p->mnet_state = $ora3->mnet_state;                 // VARCHAR2 State
		$p->mnet_rc = $ora3->mnet_rc;                       // VARCHAR2 QWEST RC Code
		$p->mnet_company = $ora3->mnet_company;             // VARCHAR2 Employee Company name
		$p->mnet_tier1 = $ora3->mnet_tier1;                 // VARCHAR2 CMP Support Tier1
		$p->mnet_tier2 = $ora3->mnet_tier2;                 // VARCHAR2 CMP Support Tier2
		$p->mnet_tier3 = $ora3->mnet_tier3;                 // VARCHAR2 CMP Support Tier3
		$p->mnet_status = $ora3->mnet_status;               // VARCHAR2 Employee Status
		$p->mnet_change_date = $ora3->mnet_change_date;     // DATE     Date Record last updated
		$p->mnet_ctl_cuid = $ora3->mnet_ctl_cuid;           // VARCHAR2 CMP Sponsor Manager User ID
		$p->mnet_mgr_cuid = $ora3->mnet_mgr_cuid;           // VARCHAR2 Manager User ID

		$mnet[$ora3->mnet_cuid] = $p;

		return true;
	}

	return false;
}

function getMNET($cuid)
{
    global $mnet;

	if (array_key_exists($cuid, $mnet))
		return $mnet[$cuid];

	return null;
}

function loadOncallOverrides()
{
    global $ora3, $ran_loadOncallOverrides, $mnet, $oncall_overrides;

	//
	// Check that we only call this function once.
	//
	if ($ran_loadOncallOverrides)
	{
		return true;
	}

	$ran_loadOncallOverrides = true;

	//
	// override_cuid's that are no longer in MNET will not be selected
	// which is what we want to do in this situation.
	//
	$query = "select " .
            "o.netpin_no, " .
            "m.mnet_cuid, " .
            "m.mnet_last_name, " .
            "m.mnet_first_name, " .
            "m.mnet_nick_name, " .
            "m.mnet_middle, " .
            "m.mnet_name, " .
            "m.mnet_job_title, " .
            "m.mnet_email, " .
            "m.mnet_work_phone, " .
            "m.mnet_pager, " .
            "m.mnet_street, " .
            "m.mnet_city, " .
            "m.mnet_state, " .
            "m.mnet_rc, " .
            "m.mnet_company, " .
            "m.mnet_tier1, " .
            "m.mnet_tier2, " .
            "m.mnet_tier3, " .
            "m.mnet_status, " .
            "to_char(m.mnet_change_date, 'MM/DD/YYYY HH24:MI'), " .
            "m.mnet_ctl_cuid, " .
            "m.mnet_mgr_cuid " .
		"from " .
            "cct6_oncall_overrides o, " .
            "cct6_mnet m " .
		"where " .
            "m.mnet_cuid = o.override_cuid " .
		"order by " .
            "m.mnet_cuid";

	if ($ora3->sql($query) == false)
	{
		printf("%s - %s\n", $ora3->sql_statement, $ora3->dbErrMsg);
		return false;
	}

	while ($ora3->fetch())
	{
		if (strlen($ora3->netpin_no) == 0)
			continue;

		$p = new data_node();

		$p->netpin_no = $ora3->netpin_no;                     // VARCHAR2 The unique netpin no that is to be override
		$p->mnet_cuid = $ora3->mnet_cuid;                     // VARCHAR2 User ID
		$p->mnet_last_name = $ora3->mnet_last_name;           // VARCHAR2 Last name
		$p->mnet_first_name = $ora3->mnet_first_name;         // VARCHAR2 First name
		$p->mnet_nick_name = $ora3->mnet_nick_name;           // VARCHAR2 Nick name
		$p->mnet_middle = $ora3->mnet_middle;                 // VARCHAR2 Middle name
		$p->mnet_name = $ora3->mnet_name;                     // VARCHAR2 Full name
		$p->mnet_job_title = $ora3->mnet_job_title;           // VARCHAR2 Job Title
		$p->mnet_email = $ora3->mnet_email;                   // VARCHAR2 Email Address
		$p->mnet_work_phone = $ora3->mnet_work_phone;         // VARCHAR2 Work phone number
		$p->mnet_pager = $ora3->mnet_pager;                   // VARCHAR2 Pager number
		$p->mnet_street = $ora3->mnet_street;                 // VARCHAR2 Street address
		$p->mnet_city = $ora3->mnet_city;                     // VARCHAR2 City
		$p->mnet_state = $ora3->mnet_state;                   // VARCHAR2 State
		$p->mnet_rc = $ora3->mnet_rc;                         // VARCHAR2 QWEST RC Code
		$p->mnet_company = $ora3->mnet_company;               // VARCHAR2 Employee Company name
		$p->mnet_tier1 = $ora3->mnet_tier1;                   // VARCHAR2 CMP Support Tier1
		$p->mnet_tier2 = $ora3->mnet_tier2;                   // VARCHAR2 CMP Support Tier2
		$p->mnet_tier3 = $ora3->mnet_tier3;                   // VARCHAR2 CMP Support Tier3
		$p->mnet_status = $ora3->mnet_status;                 // VARCHAR2 Employee Status
		$p->mnet_change_date = $ora3->mnet_change_date;       // DATE     Date Record last updated
		$p->mnet_ctl_cuid = $ora3->mnet_ctl_cuid;             // VARCHAR2 CMP Sponsor Manager User ID
		$p->mnet_mgr_cuid = $ora3->mnet_mgr_cuid;             // VARCHAR2 Manager User ID

		$oncall_overrides[$ora3->netpin_no] = $p;

		//
		// If this MNET record is not in our mnet array then add it.
		//
		if (!array_key_exists($ora3->mnet_cuid, $mnet))
		{
			$p = new data_node();

			$p->mnet_cuid = $ora3->mnet_cuid;                   // VARCHAR2 User ID
			$p->mnet_last_name = $ora3->mnet_last_name;         // VARCHAR2 Last name
			$p->mnet_first_name = $ora3->mnet_first_name;       // VARCHAR2 First name
			$p->mnet_nick_name = $ora3->mnet_nick_name;         // VARCHAR2 Nick name
			$p->mnet_middle = $ora3->mnet_middle;               // VARCHAR2 Middle name
			$p->mnet_name = $ora3->mnet_name;                   // VARCHAR2 Full name
			$p->mnet_job_title = $ora3->mnet_job_title;         // VARCHAR2 Job Title
			$p->mnet_email = $ora3->mnet_email;                 // VARCHAR2 Email Address
			$p->mnet_work_phone = $ora3->mnet_work_phone;       // VARCHAR2 Work phone number
			$p->mnet_pager = $ora3->mnet_pager;                 // VARCHAR2 Pager number
			$p->mnet_street = $ora3->mnet_street;               // VARCHAR2 Street address
			$p->mnet_city = $ora3->mnet_city;                   // VARCHAR2 City
			$p->mnet_state = $ora3->mnet_state;                 // VARCHAR2 State
			$p->mnet_rc = $ora3->mnet_rc;                       // VARCHAR2 QWEST RC Code
			$p->mnet_company = $ora3->mnet_company;             // VARCHAR2 Employee Company name
			$p->mnet_tier1 = $ora3->mnet_tier1;                 // VARCHAR2 CMP Support Tier1
			$p->mnet_tier2 = $ora3->mnet_tier2;                 // VARCHAR2 CMP Support Tier2
			$p->mnet_tier3 = $ora3->mnet_tier3;                 // VARCHAR2 CMP Support Tier3
			$p->mnet_status = $ora3->mnet_status;               // VARCHAR2 Employee Status
			$p->mnet_change_date = $ora3->mnet_change_date;     // DATE     Date Record last updated
			$p->mnet_ctl_cuid = $ora3->mnet_ctl_cuid;           // VARCHAR2 CMP Sponsor Manager User ID
			$p->mnet_mgr_cuid = $ora3->mnet_mgr_cuid;           // VARCHAR2 Manager User ID

			$mnet[$ora3->mnet_cuid] = $p;
		}
	}

	return true;
}

function getOncallOverride($net_pin)
{
    global $oncall_overrides;

	if ($oncall_overrides == null)
		return null;

	if (array_key_exists($net_pin, $oncall_overrides))
		return $oncall_overrides[$net_pin];

	return null;
}

function fixBanner($banner)
{
	return str_replace('! ', '', $banner);
}

function loadSubscribers()
{
    global $ora3, $ran_loadSubscribers, $mnet;

	//
	// Check that we only call this function once.
	//
	if ($ran_loadSubscribers)
	{
		return true;
	}

	$ran_loadSubscribers = true;

	//
	// override_cuid's that are no longer in MNET will not be selected
	// which is what we want to do in this situation.
	//
	// Do not change the "order by s.hostname" or this routine will break!
	//
	$query = "select " .
		"s.subscriber_cuid          as subscriber_cuid, " .
		"s.insert_date              as insert_date, " .
		"s.notify_type              as notify_type, " .
		"s.group_type               as group_type, " .
		"lower(s.computer_hostname) as computer_hostname, " .
		"m.mnet_cuid                as mnet_cuid, " .
		"m.mnet_last_name           as mnet_last_name, " .
		"m.mnet_first_name          as mnet_first_name, " .
		"m.mnet_nick_name           as mnet_nick_name, " .
		"m.mnet_middle              as mnet_middle, " .
		"m.mnet_name                as mnet_name, " .
		"m.mnet_job_title           as mnet_job_title, " .
		"m.mnet_email               as mnet_email, " .
		"m.mnet_work_phone          as mnet_work_phone, " .
		"m.mnet_pager               as mnet_pager, " .
		"m.mnet_street              as mnet_street, " .
		"m.mnet_city                as mnet_city, " .
		"m.mnet_state               as mnet_state, " .
		"m.mnet_rc                  as mnet_rc, " .
		"m.mnet_company             as mnet_company, " .
		"m.mnet_tier1               as mnet_tier1, " .
		"m.mnet_tier2               as mnet_tier2, " .
		"m.mnet_tier3               as mnet_tier3, " .
		"m.mnet_status              as mnet_status, " .
		"to_char(m.mnet_change_date, 'MM/DD/YYYY HH24:MI')  as mnet_change_date, " .
		"m.mnet_ctl_cuid            as mnet_ctl_cuid, " .
		"m.mnet_mgr_cuid            as mnet_mgr_cuid " .
		"from " .
		"cct6_subscriber_lists s, " .
		"cct6_mnet m " .
		"where " .
		"m.mnet_cuid = s.subscriber_cuid " .
		"order by " .
		"lower(s.computer_hostname)";

	if ($ora3->sql($query) == false)
	{
		return false;
	}

	$last_hostname = '';
	$next_link = null;
	$count = 0;

	while ($ora3->fetch())
	{
		if (strlen($ora3->mnet_cuid) == 0 || strlen($ora3->computer_hostname) == 0)
		{
			continue;
		}

		$count++;
		$p = new data_node();

		$p->subscriber_cuid = $ora3->subscriber_cuid;         // VARCHAR2 CUID of person who created this record
		$p->insert_date = $ora3->insert_date;                 // DATE     Date of person who created this record
		$p->notify_type = $ora3->notify_type;                 // VARCHAR2 APPROVER or FYI
		$p->group_type = $ora3->group_type;                   // VARCHAR2 OS, PASE, DBA, OTHER
		$p->computer_hostname = $ora3->computer_hostname;     // VARCHAR2 Computer hostname
		$p->mnet_cuid = $ora3->mnet_cuid;                     // VARCHAR2 User ID
		$p->mnet_last_name = $ora3->mnet_last_name;           // VARCHAR2 Last name
		$p->mnet_first_name = $ora3->mnet_first_name;         // VARCHAR2 First name
		$p->mnet_nick_name = $ora3->mnet_nick_name;           // VARCHAR2 Nick name
		$p->mnet_middle = $ora3->mnet_middle;                 // VARCHAR2 Middle name
		$p->mnet_name = $ora3->mnet_name;                     // VARCHAR2 Full name
		$p->mnet_job_title = $ora3->mnet_job_title;           // VARCHAR2 Job Title
		$p->mnet_email = $ora3->mnet_email;                   // VARCHAR2 Email Address
		$p->mnet_work_phone = $ora3->mnet_work_phone;         // VARCHAR2 Work phone number
		$p->mnet_pager = $ora3->mnet_pager;                   // VARCHAR2 Pager number
		$p->mnet_street = $ora3->mnet_street;                 // VARCHAR2 Street address
		$p->mnet_city = $ora3->mnet_city;                     // VARCHAR2 City
		$p->mnet_state = $ora3->mnet_state;                   // VARCHAR2 State
		$p->mnet_rc = $ora3->mnet_rc;                         // VARCHAR2 QWEST RC Code
		$p->mnet_company = $ora3->mnet_company;               // VARCHAR2 Employee Company name
		$p->mnet_tier1 = $ora3->mnet_tier1;                   // VARCHAR2 CMP Support Tier1
		$p->mnet_tier2 = $ora3->mnet_tier2;                   // VARCHAR2 CMP Support Tier2
		$p->mnet_tier3 = $ora3->mnet_tier3;                   // VARCHAR2 CMP Support Tier3
		$p->mnet_status = $ora3->mnet_status;                 // VARCHAR2 Employee Status
		$p->mnet_change_date = $ora3->mnet_change_date;       // DATE     Date Record last updated
		$p->mnet_ctl_cuid = $ora3->mnet_ctl_cuid;             // VARCHAR2 CMP Sponsor Manager User ID
		$p->mnet_mgr_cuid = $ora3->mnet_mgr_cuid;             // VARCHAR2 Manager User ID

		//
		// We are creating a link list and assigning the top node in the list to an associative array where
		// the hostname is the key. There is a 0-N relationship of people that may be subscribers to a host.
		//
		// In order for this to work properly the SQL must sort on the hostname so we process all the
		// subscriber lists for a host together.
		//
		if (strlen($last_hostname) > 0 && $last_hostname == $ora3->computer_hostname)
		{
			// The last_host matches this hostname
			// Add the link to the end of the link list and increment the next_link pointer
			$next_link->next = $p;
			$next_link = $next_link->next;
		}
		else
		{
			// The last_host does not match this hostname so it must be new in the list.
			$subscribers[$ora3->computer_hostname] = $p;
			$next_link = $p;
		}

		$last_hostname = $ora3->computer_hostname;

		//
		// If this MNET record is not in our mnet array then add it.
		//
		if (!array_key_exists($ora3->mnet_cuid, $mnet))
		{
			$p = new data_node();

			$p->mnet_cuid = $ora3->mnet_cuid;                   // VARCHAR2 User ID
			$p->mnet_last_name = $ora3->mnet_last_name;         // VARCHAR2 Last name
			$p->mnet_first_name = $ora3->mnet_first_name;       // VARCHAR2 First name
			$p->mnet_nick_name = $ora3->mnet_nick_name;         // VARCHAR2 Nick name
			$p->mnet_middle = $ora3->mnet_middle;               // VARCHAR2 Middle name
			$p->mnet_name = $ora3->mnet_name;                   // VARCHAR2 Full name
			$p->mnet_job_title = $ora3->mnet_job_title;         // VARCHAR2 Job Title
			$p->mnet_email = $ora3->mnet_email;                 // VARCHAR2 Email Address
			$p->mnet_work_phone = $ora3->mnet_work_phone;       // VARCHAR2 Work phone number
			$p->mnet_pager = $ora3->mnet_pager;                 // VARCHAR2 Pager number
			$p->mnet_street = $ora3->mnet_street;               // VARCHAR2 Street address
			$p->mnet_city = $ora3->mnet_city;                   // VARCHAR2 City
			$p->mnet_state = $ora3->mnet_state;                 // VARCHAR2 State
			$p->mnet_rc = $ora3->mnet_rc;                       // VARCHAR2 QWEST RC Code
			$p->mnet_company = $ora3->mnet_company;             // VARCHAR2 Employee Company name
			$p->mnet_tier1 = $ora3->mnet_tier1;                 // VARCHAR2 CMP Support Tier1
			$p->mnet_tier2 = $ora3->mnet_tier2;                 // VARCHAR2 CMP Support Tier2
			$p->mnet_tier3 = $ora3->mnet_tier3;                 // VARCHAR2 CMP Support Tier3
			$p->mnet_status = $ora3->mnet_status;               // VARCHAR2 Employee Status
			$p->mnet_change_date = $ora3->mnet_change_date;     // DATE     Date Record last updated
			$p->mnet_ctl_cuid = $ora3->mnet_ctl_cuid;           // VARCHAR2 CMP Sponsor Manager User ID
			$p->mnet_mgr_cuid = $ora3->mnet_mgr_cuid;           // VARCHAR2 Manager User ID

			$mnet[$ora3->mnet_cuid] = $p;
		}
	}

	return true;
}

function getSubscribers($computer_hostname)
{
    global $subscribers;

	if ($subscribers == null)
	{
		return null;
	}

	if (array_key_exists($computer_hostname, $subscribers))
	{
		return $subscribers[$computer_hostname];
	}

	return null;
}
