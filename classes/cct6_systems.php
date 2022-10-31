<?php
/**
 * <cct6_systems.php>
 *
 * @package    CCT
 * @file       cct6_systems.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       10/11/2013
 * @version    6.0.0
 */
set_include_path("/opt/ibmtools/www/cct8/classes:/opt/ibmtools/www/cct8/includes:/opt/ibmtools/www/cct8/servers");

//
// Public Methods
// AddSystem($cm_ticket_no, $hostname)
// RemoveSystem($cm_ticket_no, $system_id, $cancel_message)
// SetSystemActualWorkByID($system_id, $system_actual_work_start, $system_actual_work_end, $system_actual_work_duration)
// SetSystemWorkStatusByID($system_id, $system_work_status, $override_lock_status)
// SetSystemEmailNotificationDateByID($system_id)
// SetAllSystemEmailNotificationDateByTicket($cm_ticket_no)
// SetSystemWorkCancelDateByID($system_id, $cancel_message)
// SetAllSystemWorkCancelDateByTicket($cm_ticket_no, $cancel_message)
// SetOverrideStatusByID($system_id, $override_status, $override_notes)
// SetCompletionStatusByID($system_id, $system_completion_status, $notes)  Needs code
// SetApproverCountsByID($system_id, $os, $pase, $dba, $fyi)  Needs code
// GetSystemByID($system_id)
// GetSystem($cm_ticket_no, $hostname)
// conflictsFound($system_id, $computer_hostname, $this_host_start_time, $this_host_end_time)
// CheckAndUpdateSystemWorkStatus($system_id)
//
//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

/*! @class cct6_systems
 *  @brief Basic SQL for selects, inserts and updates for table: cct6_systems
 *  @brief Used by class: cct6_contacts.php
 *  @brief Used by Ajax servers: server_edit_work_request.php, server_group_inbox.php and server_view_calendar.php
 */
class cct6_systems extends library
{
	var $data = array();       // Associated array for public class variables.
	var $ora;                  // Database connection object
	var $error;
	var $log;
	
	/*! @fn __construct()
	 *  @brief Class constructor - Create oracle and event log objects, and setup some dynamic class variables
	 *  @return void 
	 */
	public function __construct()
	{
		$this->ora = new dbms();             // classes/dbms.php
		$this->log = new cct6_event_log();   // classes/cct6_event_log.php
		
		if (PHP_SAPI === 'cli')
		{
			$this->user_cuid = 'cctadm';
			$this->user_first_name = 'Application';
			$this->user_last_name = 'CCT';
			$this->user_name = 'CCT Application';
			$this->user_email = 'gregparkin58@gmail.com';
			$this->user_company = 'qwestibm';	
			$this->user_access_level = 'admin';
			
			$this->manager_cuid = 'gparkin';
			$this->manager_first_name = 'Greg';
			$this->manager_last_name = 'Parkin';
			$this->manager_name = 'Greg Parkin';
			$this->manager_email = 'gregparkin58@gmail.com';
			$this->manager_company = 'IGS';	
		}
		else
		{
			if (session_id() == '')
				session_start();                // Required to start once in order to retrieve user session information
		
			if (isset($_SESSION['user_cuid']))
			{
				$this->user_cuid = $_SESSION['user_cuid'];
				$this->user_first_name = $_SESSION['user_first_name'];
				$this->user_last_name = $_SESSION['user_last_name'];
				$this->user_name = $_SESSION['user_name'];
				$this->user_email = $_SESSION['user_email'];
				$this->user_company = $_SESSION['user_company'];	
				$this->user_access_level = $_SESSION['user_access_level'];
			
				$this->manager_cuid = $_SESSION['manager_cuid'];
				$this->manager_first_name = $_SESSION['manager_first_name'];
				$this->manager_last_name = $_SESSION['manager_last_name'];
				$this->manager_name = $_SESSION['manager_name'];
				$this->manager_email = $_SESSION['manager_email'];
				$this->manager_company = $_SESSION['manager_company'];		
			
				$this->is_debug_on = $_SESSION['is_debug_on'];
			}
			else
			{
				$this->user_cuid = 'cctadm';
				$this->user_first_name = 'Application';
				$this->user_last_name = 'CCT';
				$this->user_name = 'CCT Application';
				$this->user_email = 'gregparkin58@gmail.com';
				$this->user_company = 'qwestibm';	
				$this->user_access_level = 'admin';
			
				$this->manager_cuid = 'gparkin';
				$this->manager_first_name = 'Greg';
				$this->manager_last_name = 'Parkin';
				$this->manager_name = 'Greg Parkin';
				$this->manager_email = 'gregparkin58@gmail.com';
				$this->manager_company = 'IGS';	
			
				$this->is_debug_on = 'N';	
			}
			
			$this->debug_start('cct6_systems.txt');	
		}
	}

	/*! @fn __set($name, $value)
	 *  @brief Setter function for $this->data
	 *  @brief Example: $obj->first_name = 'Greg';
	 *  @param $name is the key in the associated $data array
	 *  @param $value is the value in the assoicated $data array for the identified key
	 *  @return null 
	 */
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}
	
	/*! @fn __get($name)
	 *  @brief Getter function for $this->data
	 *  @brief echo $obj->first_name;
	 *  @param $name is the key in the associated $data array
	 *  @return null 
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->data))
		{
			return $this->data[$name];
		}
		
		return null;
	}
	
	/*! @fn __isset($name)
	 *  @brief Determine if item ($name) exists in the $this->data array
	 *  @brief var_dump(isset($obj->first_name));
	 *  @param $name is the key in the associated $data array
	 *  @return null 
	 */	
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}
	
	/*! @fn __unset($name)
	 *  @brief Unset an item in $this->data assoicated by $name
	 *  @brief unset($obj->name);
	 *  @param $name is the key in the associated $data array
	 *  @return null 
	 */	
	public function __unset($name)
	{
		unset($this->data[$name]);
	}
	
	/*! @fn isLockStatusOn($system_id)
	 *  @brief Check to see if the system lock status is on.
	 *  @param $system_id is the record number of cct6_systems that we want to check the lock
	 *  #return true means lock is on, false means lock is off
	 */
	private function isLockStatusOn($system_id)
	{
		$query  = sprintf("select system_override_status_date from cct6_systems where system_id = %d ", $system_id);
		$query .= "and system_override_status_date is null";
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			printf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			exit();	
		}
		
		// If a record is returned where system_override_status_date is null then lock is off
		if ($this->ora->fetch())
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %d, lock is off", $system_id);
			return false;   // Lock is off
		}
			
		$this->error = sprintf("System Status Lock is set for this system (system_id=%d). No changes can be made!", $system_id);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %d, lock is on", $system_id);
		return true;  // lock is on
	}

	/*! @fn UpdateSystemActualWorkStartEnd($system_id, $contact_reschedule_start, $contact_reschedule_end, $duration)
	 *  @brief Change the system_actual_work_start, system_actual_work_end, and system_actual_work_duration in cct6_systems
	 *  @param $system_id is the record number of cct6_systems
	 *  @param $contact_reschedule_start is the new system_actual_work_start
	 *  @param $contact_reschedule_end is the new system_actual_work_end
	 *  @param $duration is the new system_actual_work_duration
	 *  @return true for success, false for failure. $this->error contains reason for failure
	 */		
	public function UpdateSystemActualWorkStartEnd($system_id, $contact_reschedule_start, $contact_reschedule_end, $duration)
	{
		// Get system record so we have the computer_contract information.
		if ($this->GetSystemByID($system_id) == false)
			return false;
				
		// Change system_work_status to READY
		$update = "update cct6_systems set ";
		
		$this->makeUpdateCHAR($update,     "system_update_cuid",          $this->user_cuid,           true);  // VARCHAR2 system_update_cuid
		$this->makeUpdateCHAR($update,     "system_update_name",          $this->user_name,           true);  // VARCHAR2 system_update_name
		$this->makeUpdateDateTIME($update, "system_actual_work_start",    $contact_reschedule_start,  true);
		$this->makeUpdateDateTIME($update, "system_actual_work_end",      $contact_reschedule_end,    true);
		$this->makeUpdateCHAR(    $update, "system_actual_work_duration", $duration,                 false);
		
		$update .= " where system_id = " . $system_id;
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$this->ora->commit();
		$this->log->AddEvent($system_id, "CHANGE", 
			sprintf("Changed actual work start/end from: %s thru %s to: %s thru %s", 
				$this->cm_start_date, $this->cm_end_date, $contact_reschedule_start, $contact_reschedule_end));
				
		return true;
	}
	
	/*! @fn CheckAndUpdateSystemWorkStatus($system_id)
	 *  @brief Checks all the contacts to see if we have all the approvals needed and then change the status to READY
	 *  @param $system_id is the record number of cct6_systems
	 *  @return true for success, false for failure. $this->error contains reason for failure
	 */	
	public function CheckAndUpdateSystemWorkStatus($system_id)
	{
		if ($this->isLockStatusOn($system_id))
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}
		
		// Ignore if ticket_approvals_required == 'N' or system_override_status_date is not null	
		$query  = "select ";
		$query .= "  t.ticket_approvals_required    as ticket_approvals_required, ";
		$query .= "  s.system_override_status_date  as system_override_status_date ";
		$query .= "from ";
		$query .= "  cct6_tickets t, ";
		$query .= "  cct6_systems s ";
		$query .= "where ";
		$query .= "  t.cm_ticket_no = s.cm_ticket_no and ";
		$query .= "  s.system_id = " . $system_id;
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		//
		// Are approvals required for this ticket?
		//
		if ($this->ora->fetch() && $this->ora->ticket_approvals_required == "N")
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %d, ticket_approvals_required = N, will not update system work status", $system_id);
			return true;
		}
		
		//
		// Is the system override lock status in place?
		//
		if (strlen($this->ora->system_override_status_date) > 0)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "This record has an override status lock in place. Work status for this sytem will not be changed.");
			return true;
		}
			
		// Get system record so we have the computer_contract information. (Need: $this->computer_contract)
		if ($this->GetSystemByID($system_id) == false)
			return false;
			
		$query = "select * from cct6_contacts where system_id = " . $system_id;
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);	
			return false;		
		}
			
		$total = 0;
		$total_exempt = 0;
		$total_approved = 0;
		$total_rejected = 0;
		$total_rescheduled = 0;
		$total_pending = 0;	
		$total_waiting = 0;
		$total_pase = 0;
		$total_dba = 0;
		$total_os = 0;
		$total_other = 0;
			
		while ($this->ora->fetch())
		{
			// Don't count FYI notification contacts and people who have exempted themselves from approving any work.
			// Sets: $this->computer_contract
			if ($this->ora->contact_notify_type == 'FYI' || $this->ora->contact_response_status == 'EXEMPT')
				continue;
				
			$total++;
			
			switch ( $this->ora->contact_response_status )
			{
				case 'APPROVED':
					$total_approved++;
					break;
				case 'REJECTED':
					$total_rejected++;
					break;
				case 'RESCHEDULED':
					$total_rescheduled++;
					break;
				case 'EXEMPT':
					$total_exempt++;
					break;
				case 'WAITING':
					$total_waiting++;
					break;
				default:  // FYI or NA
					break;				
			}
		
			switch ( $this->ora->contact_group_type )
			{
				case 'PASE':
					$total_pase++;
					break;
				case 'DBA':
					$total_dba++;
					break;
				case 'OS':
					$total_os++;
					break;
				default:
					$total_other++;
					break;
			}
		}
		
		// Determine thw system work status
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total = %d", $total);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_exempt = %d", $total_exempt);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_approved = %d", $total_approved);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_rejected = %d", $total_rejected);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_rescheduled = %d", $total_rescheduled);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_pending = %d", $total_pending);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_pase = %d", $total_pase);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_dba = %d", $total_dba);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_os = %d", $total_os);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_other = %d", $total_other);
		
		$system_status = "";

		if (strstr($this->computer_contract, "INFRASTRUCTURE'") != false && $total_approved > 0 && $total_approved == $total_os)
		{
			$system_status = "READY";
		}
		else if ($total_rejected > 0)
		{
			$system_status = "REJECTED";
		}
		else if ($total_rescheduled > 0)
		{
			$system_status = "RESCHEDULED";
		}
		else if (($total_pase > 0 || $total_dba > 0) && $total > 0 && $total == ($total_approved + $total_exempt))
		{
			$system_status = "READY";
		}
		else if ($total_pase == 0 && $total_dba == 0)
		{
			$system_status = "PENDING";
		}
		else
		{
			$system_status = "WAITING";
		}
		
		//
		// Check to see if the status is still the same
		//
		if ($this->system_work_status == $system_status)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_work_status == system_status. No change required!");
			return true;
		}
			
		// Change system_work_status to READY
		$update = "update cct6_systems set ";
		
		$this->makeUpdateCHAR($update, "system_update_cuid", $this->user_cuid,  true);  // VARCHAR2 system_update_cuid
		$this->makeUpdateCHAR($update, "system_update_name", $this->user_name,  true);  // VARCHAR2 system_update_name
		$this->makeUpdateCHAR($update, "system_work_status", $system_status,   false);  // VARCHAR2 system_work_status
		
		$update .= " where system_id = " . $system_id;
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$this->ora->commit();
		$this->log->AddEvent($system_id, "CHANGE", 'Change system work status to ' . $system_status);	
		
		$this->system_work_status = $system_status;  // To indicate that it changed
		return true;
	}
	
	/*! @fn AddSystem($cm_ticket_no, $hostname)
	 *  @brief Add s system to this ticket
	 *  @param $cm_ticket_no is the Remedy ticket no.
	 *  @param $hostname is the computer hostname
	 *  @return true for success, false for failure. $this->error contains reason for failure
	 */
	public function AddSystem($cm_ticket_no, $hostname)
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "AddSystem(%s, %s)", $cm_ticket_no, $hostname);
				
		//
		// Grab the ticket from cct6_tickets
		//
		$tic = new cct6_tickets();  // classes/cct6_tickets.php
		
		if ($tic->getTicket($cm_ticket_no) == false)
		{
			// If the ticket is not in cct6_tickets (cm_ticket_no) then it could mean not there as to oppose an SQL error.
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $tic->error);
			$this->error = $tic->error;	
			return false;		
		}
		
		// Retrieve the system information from table: cct6_computers
		if ($this->ora->sql("select * from cct6_computers where computer_hostname = lower('" . $hostname . "')") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;	
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Hostname; %s not found in table: cct6_computers", $hostname);
			$this->error = sprintf("Hostname: %s not found in Asset Center", $hostname);
			return false;
		}
		
		// Get the next sequence number from cct6_systemsseq
		$this->system_id = $this->ora->next_seq('cct6_systemsseq');
		$this->lastid = $this->ora->computer_lastid;
		
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "new system_id = %d", $this->system_id);
		
		// Build the $insert SQL command
		$insert = "insert into cct6_systems (" .
			"system_id, cm_ticket_no, system_work_status, system_insert_cuid, system_insert_name, " .
			"computer_lastid, computer_last_update, computer_install_date, " .
			"computer_systemname, computer_hostname, computer_operating_system, " .
			"computer_os_lite, computer_status, computer_status_description, " .
			"computer_description, computer_nature, computer_platform, computer_type, " .
			"computer_clli, computer_clli_fullname, computer_timezone, computer_building, " .
			"computer_address, computer_city, computer_state, computer_floor_room, " .
			"computer_grid_location, computer_lease_purchase, computer_serial_no, " .
			"computer_asset_tag, computer_model_category, computer_model_no, computer_model, " .
			"computer_model_mfg, computer_cpu_type, computer_cpu_count, computer_cpu_speed, " .
			"computer_memory_mb, computer_ip_address, computer_domain, computer_hostname_domain, " .
			"computer_dmz, computer_ewebars_title, computer_ewebars_status, computer_backup_format, " .
			"computer_backup_nodename, computer_backup_program, computer_backup_server, " .
			"computer_netbackup, computer_complex, computer_complex_lastid, computer_complex_name, " .
			"computer_complex_parent_name, computer_complex_child_names, computer_complex_partitions, " .
			"computer_service_guard, computer_os_group_contact, computer_cio_group, computer_managing_group, " .
			"computer_contract, computer_contract_ref, computer_contract_status, computer_contract_status_type, " .
			"computer_contract_date, computer_ibm_supported, computer_gold_server, computer_slevel_objective, " .
			"computer_slevel_score, computer_slevel_colors, computer_special_handling, computer_applications, " .
			"computer_osmaint_weekly, computer_osmaint_monthly, computer_osmaint_quarterly, " .
			"computer_csc_os_banners, computer_csc_pase_banners, computer_csc_dba_banners, computer_csc_fyi_banners, " .
			"system_actual_work_start, system_actual_work_end, system_actual_work_duration, " .
			"system_original_work_start, system_original_work_end, system_original_work_duration, system_approvals_required ) values ( ";	
					
		$this->makeInsertINT(     $insert, $this->system_id,                          true);  // system_id
		$this->makeInsertCHAR(    $insert, $cm_ticket_no,                             true);  // cm_ticket_no
		$this->makeInsertCHAR(    $insert, $this->system_work_status,                 true);  // system_work_status
		$this->makeInsertCHAR(    $insert, $this->user_cuid,                          true);  // system_insert_cuid
		$this->makeInsertCHAR(    $insert, $this->user_name,                          true);  // system_insert_name
		$this->makeInsertINT(     $insert, $this->ora->computer_lastid,               true);  // computer_lastid
		$this->makeInsertDateTIME($insert, $this->ora->computer_last_update,          true);  // computer_last_update
		$this->makeInsertDateTIME($insert, $this->ora->computer_install_date,         true);  // computer_install_date
		$this->makeInsertCHAR(    $insert, $this->ora->computer_systemname,           true);  // computer_systemname
		$this->makeInsertCHAR(    $insert, $this->ora->computer_hostname,             true);  // computer_hostname
		$this->makeInsertCHAR(    $insert, $this->ora->computer_operating_system,     true);  // computer_operating_system
		$this->makeInsertCHAR(    $insert, $this->ora->computer_os_lite,              true);  // computer_os_lite
		$this->makeInsertCHAR(    $insert, $this->ora->computer_status,               true);  // computer_status
		$this->makeInsertCHAR(    $insert, $this->ora->computer_status_description,   true);  // computer_status_description
		$this->makeInsertCHAR(    $insert, $this->ora->computer_description,          true);  // computer_description
		$this->makeInsertCHAR(    $insert, $this->ora->computer_nature,               true);  // computer_nature
		$this->makeInsertCHAR(    $insert, $this->ora->computer_platform,             true);  // computer_platform
		$this->makeInsertCHAR(    $insert, $this->ora->computer_type,                 true);  // computer_type
		$this->makeInsertCHAR(    $insert, $this->ora->computer_clli,                 true);  // computer_clli
		$this->makeInsertCHAR(    $insert, $this->ora->computer_clli_fullname,        true);  // computer_clli_fullname
		$this->makeInsertCHAR(    $insert, $this->ora->computer_timezone,             true);  // computer_timezone
		$this->makeInsertCHAR(    $insert, $this->ora->computer_building,             true);  // computer_building
		$this->makeInsertCHAR(    $insert, $this->ora->computer_address,              true);  // computer_address
		$this->makeInsertCHAR(    $insert, $this->ora->computer_city,                 true);  // computer_city
		$this->makeInsertCHAR(    $insert, $this->ora->computer_state,                true);  // computer_state
		$this->makeInsertCHAR(    $insert, $this->ora->computer_floor_room,           true);  // computer_floor_room
		$this->makeInsertCHAR(    $insert, $this->ora->computer_grid_location,        true);  // computer_grid_location
		$this->makeInsertCHAR(    $insert, $this->ora->computer_lease_purchase,       true);  // computer_lease_purchase
		$this->makeInsertCHAR(    $insert, $this->ora->computer_serial_no,            true);  // computer_serial_no
		$this->makeInsertCHAR(    $insert, $this->ora->computer_asset_tag,            true);  // computer_asset_tag
		$this->makeInsertCHAR(    $insert, $this->ora->computer_model_category,       true);  // computer_model_category
		$this->makeInsertCHAR(    $insert, $this->ora->computer_model_no,             true);  // computer_model_no
		$this->makeInsertCHAR(    $insert, $this->ora->computer_model,                true);  // computer_model
		$this->makeInsertCHAR(    $insert, $this->ora->computer_model_mfg,            true);  // computer_model_mfg
		$this->makeInsertCHAR(    $insert, $this->ora->computer_cpu_type,             true);  // computer_cpu_type
		$this->makeInsertINT(     $insert, $this->ora->computer_cpu_count,            true);  // computer_cpu_count
		$this->makeInsertINT(     $insert, $this->ora->computer_cpu_speed,            true);  // computer_cpu_speed
		$this->makeInsertINT(     $insert, $this->ora->computer_memory_mb,            true);  // computer_memory_mb
		$this->makeInsertCHAR(    $insert, $this->ora->computer_ip_address,           true);  // computer_ip_address
		$this->makeInsertCHAR(    $insert, $this->ora->computer_domain,               true);  // computer_domain
		$this->makeInsertCHAR(    $insert, $this->ora->computer_hostname_domain,      true);  // computer_hostname_domain
		$this->makeInsertCHAR(    $insert, $this->ora->computer_dmz,                  true);  // computer_dmz
		$this->makeInsertCHAR(    $insert, $this->ora->computer_ewebars_title,        true);  // computer_ewebars_title
		$this->makeInsertCHAR(    $insert, $this->ora->computer_ewebars_status,       true);  // computer_ewebars_status
		$this->makeInsertCHAR(    $insert, $this->ora->computer_backup_format,        true);  // computer_backup_format
		$this->makeInsertCHAR(    $insert, $this->ora->computer_backup_nodename,      true);  // computer_backup_nodename
		$this->makeInsertCHAR(    $insert, $this->ora->computer_backup_program,       true);  // computer_backup_program
		$this->makeInsertCHAR(    $insert, $this->ora->computer_backup_server,        true);  // computer_backup_server
		$this->makeInsertCHAR(    $insert, $this->ora->computer_netbackup,            true);  // computer_netbackup
		$this->makeInsertCHAR(    $insert, $this->ora->computer_complex,              true);  // computer_complex
		$this->makeInsertINT(     $insert, $this->ora->computer_complex_lastid,       true);  // computer_complex_lastid
		$this->makeInsertCHAR(    $insert, $this->ora->computer_complex_name,         true);  // computer_complex_name
		$this->makeInsertCHAR(    $insert, $this->ora->computer_complex_parent_name,  true);  // computer_complex_parent_name
		$this->makeInsertCHAR(    $insert, $this->ora->computer_complex_child_names,  true);  // computer_complex_child_names
		$this->makeInsertCHAR(    $insert, $this->ora->computer_complex_partitions,   true);  // computer_complex_partitions
		$this->makeInsertCHAR(    $insert, $this->ora->computer_service_guard,        true);  // computer_service_guard
		$this->makeInsertCHAR(    $insert, $this->ora->computer_os_group_contact,     true);  // computer_os_group_contact
		$this->makeInsertCHAR(    $insert, $this->ora->computer_cio_group,            true);  // computer_cio_group
		$this->makeInsertCHAR(    $insert, $this->ora->computer_managing_group,       true);  // computer_managing_group
		$this->makeInsertCHAR(    $insert, $this->ora->computer_contract,             true);  // computer_contract
		$this->makeInsertCHAR(    $insert, $this->ora->computer_contract_ref,         true);  // computer_contract_ref
		$this->makeInsertCHAR(    $insert, $this->ora->computer_contract_status,      true);  // computer_contract_status
		$this->makeInsertCHAR(    $insert, $this->ora->computer_contract_status_type, true);  // computer_contract_status_type
		$this->makeInsertDateTIME($insert, $this->ora->computer_contract_date,        true);  // computer_contract_date
		$this->makeInsertCHAR(    $insert, $this->ora->computer_ibm_supported,        true);  // computer_ibm_supported
		$this->makeInsertCHAR(    $insert, $this->ora->computer_gold_server,          true);  // computer_gold_server
		$this->makeInsertINT(     $insert, $this->ora->computer_slevel_objective,     true);  // computer_slevel_objective
		$this->makeInsertINT(     $insert, $this->ora->computer_slevel_score,         true);  // computer_slevel_score
		$this->makeInsertCHAR(    $insert, $this->ora->computer_slevel_colors,        true);  // computer_slevel_colors
		$this->makeInsertCHAR(    $insert, $this->ora->computer_special_handling,     true);  // computer_special_handling
		$this->makeInsertCHAR(    $insert, $this->ora->computer_applications,         true);  // computer_applications
		$this->makeInsertCHAR(    $insert, $this->ora->computer_osmaint_weekly,       true);  // computer_osmaint_weekly
		$this->makeInsertCHAR(    $insert, $this->ora->computer_osmaint_monthly,      true);  // computer_osmaint_monthly
		$this->makeInsertCHAR(    $insert, $this->ora->computer_osmaint_quarterly,    true);  // computer_osmaint_quarterly
		$this->makeInsertINT(     $insert, $this->ora->computer_csc_os_banners,       true);  // computer_csc_os_banners
		$this->makeInsertINT(     $insert, $this->ora->computer_csc_pase_banners,     true);  // computer_csc_pase_banners
		$this->makeInsertINT(     $insert, $this->ora->computer_csc_dba_banners,      true);  // computer_csc_dba_banners
		$this->makeInsertINT(     $insert, $this->ora->computer_csc_fyi_banners,      true);  // computer_csc_fyi_banners
		
		//
		// Compute the work start/end for this server based upon the Remedy ticket window and the servers maintenance window.
		//
		$f = new maintwin_formatter();
		
		$formatted = "";
		
		switch ( $this->ticket_os_maintwin )
		{
			case 'Q':
				$formatted = $f->format($this->ora->computer_osmaint_quarterly);
				break;
			case 'M':
				$formatted = $f->format($this->ora->computer_osmaint_monthly);
				break;
			default: // W
				$formatted = $f->format($this->ora->computer_osmaint_weekly);
				break;
		}
		
		$s = new maintwin_scheduler();

		$s->set_ir_window($tic->cm_start_date, $tic->cm_end_date);
		$s->set_osmaint_win($this->ticket_os_maintwin, $formatted);

		$s->ComputeStart();
	
		$this->makeInsertDateTIME($insert, $s->wreq_start_date,              true);  // system_actual_work_start
		$this->makeInsertDateTIME($insert, $s->wreq_end_date,                true);  // system_actual_work_end
		$this->makeInsertCHAR(    $insert, $s->wreq_duration,                true);  // system_actual_work_duration
		$this->makeInsertDateTIME($insert, $s->wreq_start_date,              true);  // system_original_work_start
		$this->makeInsertDateTIME($insert, $s->wreq_end_date,                true);  // system_original_work_end
		$this->makeInsertCHAR(    $insert, $s->wreq_duration,                true);  // system_original_work_duration
		$this->makeInsertCHAR(    $insert, $tic->ticket_approvals_required, false); // system_approvals_required
					
		$insert .= 	" )";
		
		if ($this->ora->sql($insert) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		$this->ora->commit();
		
		$this->log->AddEvent($this->system_id, "ADDED", 'Server added to the work request.');
		
		return true;					
	}
	
	/*! @fn RemoveSystem($cm_ticket_no, $hostname)
	 *  @brief Remove hostname from this ticket.
	 *  @brief Ticket status DRAFT will remove the ticket from the database.
	 *  @brief Ticket status all others will mark the ticket as canceled and send out notifications.
	 *  @param $cm_ticket_no is the Remedy ticket no.
	 *  @param $system_id is the record in cct6_systems that we want to cancel or delete
	 *  @param $cancel_message is the user comments as to why the work was canceled
	 *  @return true for success, false for failure. $this->error contains reason for failure
	 */	
	public function RemoveSystem($cm_ticket_no, $system_id, $cancel_message)	
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "RemoveSystem(%s, %d, %s)", $cm_ticket_no, $system_id, $cancel_message);
		//
		// Grab the ticket from cct6_tickets
		//
		$tic = new cct6_tickets();  // classes/cct6_tickets.php
		
		if ($tic->getTicket($cm_ticket_no) == false)
		{
			// If the ticket is not in cct6_tickets (cm_ticket_no) then it could mean not there as to oppose an SQL error.
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $tic->error);
			$this->error = $tic->error;	
			return false;		
		}	
	
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Got the ticket");
		
		//
		// Determine the ticket status
		//
		if ($tic->ticket_status == 'DRAFT')
		{
			$query = "delete from cct6_systems where system_id = " . $system_id;
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "DRAFT TICKET: %s", $query);
			
			if ($this->ora->sql($query) == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
				$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
				return false;		
			}
		
			$this->ora->commit();
			return true;		
		}
		
		//
		// SpoolEmailTicket($cm_ticket_no, $email_template="", $email_subject="", $email_message="", $ticket_owner, $os_group, $pase_group, $dba_group)
		//
		$email = new cct6_email_spool();  // classes/cct6_email_spool.php
		
		//
		// SpoolEmailTicketSystemId($cm_ticket_no, $system_id, $email_template="", $email_subject="", $email_message="")
		//
		if ($email->SpoolEmailTicketSystemId($cm_ticket_no, $system_id, "CANCELED", "", "") == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $email->error);
			$this->error = $email->error;
			return false;
		}
		
		$update = "update cct6_systems set ";
		
		$this->makeUpdateCHAR(   $update, "system_update_cuid",       $this->user_cuid,   true);
		$this->makeUpdateCHAR(   $update, "system_update_name",       $this->user_name,   true);
		$this->makeUpdateCHAR(   $update, "system_work_status",       "CANCELED",         true);
		$this->makeUpdateDateNOW($update, "system_work_cancel_date",                      true);
		$this->makeUpdateDateNOW($update, "system_completion_date",                       true);
		$this->makeUpdateCHAR(   $update, "system_completion_status", "CANCELED",         true);
		$this->makeUpdateCHAR(   $update, "system_completion_cuid",   $this->user_cuid,   true);
		$this->makeUpdateCHAR(   $update, "system_completion_name",   $this->user_name,   true);
		$this->makeUpdateCHAR(   $update, "system_completion_notes",  $cancel_message,   false);
		
		$update .= "where system_id = " . $system_id;
						
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Adding event log messages");
		
		$this->log->AddEvent($system_id, "CANCEL", $cancel_message);
		
		$this->ora->commit();
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Looks good!");
		
		return true;
	}
	
	/*! @fn SetSystemActualWorkByID($system_id, $system_actual_work_start, $system_actual_work_end, $system_actual_work_duration)
	 *  @brief Update the actual work start, end, and duration for the system identified by system_id
	 *  @param $system_id is the cct6_systems unique record ID
	 *  @param $system_actual_work_start is the work start datetime MM/DD/YYYY HH24:MI
	 *  @param $system_actual_work_end is the work end datetime MM/DD/YYYY HH24:MI
	 *  @param $system_actual_work_duration is the computed duration time string for start to end
	 *  @return true for success, false for failure. $this->error contains reason for failure
	 */	
	public function SetSystemActualWorkByID($system_id, $system_actual_work_start, $system_actual_work_end, $system_actual_work_duration)
	{
		if (strlen($system_id) == 0)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id cannot be empty");
			$this->error = "SetSystemActualWorkByID($system_id) - system_id cannot be empty";
			return false;
		}
		
		$update = "update cct6_systems set ";
		
		$this->makeUpdateCHAR(    $update, "system_update_cuid",          $this->user_cuid,              true);  // VARCHAR2 system_update_cuid
		$this->makeUpdateCHAR(    $update, "system_update_name",          $this->user_name,              true);  // VARCHAR2 system_update_name
		$this->makeUpdateDateTIME($update, "system_actual_work_start",    $system_actual_work_start,     true);  // DATE     system_actual_work_start
		$this->makeUpdateDateTIME($update, "system_actual_work_end",      $system_actual_work_end,       true);  // DATE     system_actual_work_end
		$this->makeUpdateCHAR(    $update, "system_actual_work_duration", $system_actual_work_duration, false);  // VARCHAR2 system_actual_work_duration
		
		$update .= " where system_id = " . $system_id;
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$this->ora->commit();
		$this->log->AddEvent($system_id, "CHANGE", 'Changed work start/end datetimes');
		
		return true;
	}
	
	/*! @fn SetSystemWorkStatusByID($system_id, $system_work_status, $override_lock_status)
	 *  @brief Update the system work status field identified by system_id
	 *  @param $system_id is the cct6_systems unique record ID
	 *  @param $system_work_status is the string containing the new work status. (READY, WAITING, REJECTED, RESCHEDULE, PENDING=No Contacts)
	 *  @param $override_lock_status is true or false where true means don't check isLockStatusOn($system_id)
	 *  @return true for success, false for failure. $this->error contains reason for failure
	 */	
	public function SetSystemWorkStatusByID($system_id, $system_work_status, $override_lock_status)
	{
		if ($override_lock_status == false && $this->isLockStatusOn($system_id))
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}
			
		if (strlen($system_id) == 0 || strlen($system_work_status) == 0)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id and system_work_status cannot be empty");
			$this->error = "SetSystemWorkStatusByID($system_id, $system_work_status, $override_lock_status) - system_id and system_work_status cannot be empty";
			return false;
		}
		
		$update = "update cct6_systems set ";
		
		$this->makeUpdateCHAR($update, "system_update_cuid", $this->user_cuid,     true);  // VARCHAR2 system_update_cuid
		$this->makeUpdateCHAR($update, "system_update_name", $this->user_name,     true);  // VARCHAR2 system_update_name
		$this->makeUpdateCHAR($update, "system_work_status", $system_work_status, false);  // VARCHAR2 system_work_status
		
		$update .= " where system_id = " . $system_id;
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$this->ora->commit();
		$this->log->AddEvent($system_id, "CHANGE", 'Work status changed to: ' . $system_work_status);
		
		return true;
	}
	
	/*! @fn SetSystemEmailNotificationDateByID($system_id)
	 *  @brief Set the system_email_notification_date to the current date and time (SYSDATE)
	 *  @param $system_id is the cct6_systems unique record ID
	 *  @return true for success, false for failure. $this->error contains reason for failure
	 */	
	public function SetSystemEmailNotificationDateByID($system_id)
	{
		if (strlen($system_id) == 0)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id cannot be empty");
			$this->error = "SetSystemEmailNotificationDateByID($system_id) - system_id cannot be empty";
			return false;
		}
		
		$update = "update cct6_systems set ";
		
		makeUpdateCHAR($query, $fieldname, $value, $add_comma);
		
		$this->makeUpdateCHAR(   $update, "system_update_cuid",             $this->user_cuid,     true);  // VARCHAR2 system_update_cuid
		$this->makeUpdateDateNOW($update, "system_email_notification_date",                      false);  // DATE system_email_notification_date
		
		$update .= " where system_id = " . $system_id;
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$this->ora->commit();
		// AddEvent() not required here
		
		return true;
	}	
	
	/*! @fn SetAllSystemEmailNotificationDateByTicket($cm_ticket_no)
	 *  @brief Set all system_email_notification_date to the current date and time (SYSDATE) identified by cm_ticket_no
	 *  @param $cm_ticket_no is the Remedy ticket no.
	 *  @return true for success, false for failure. $this->error contains reason for failure
	 */	
	public function SetAllSystemEmailNotificationDateByTicket($cm_ticket_no)
	{
		if (strlen($cm_ticket_no) == 0)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_ticket_no cannot be empty");
			$this->error = "SetAllSystemEmailNotificationDateByTicket($cm_ticket_no) - cm_ticket_no cannot be empty";
			return false;
		}
		
		$update = "update cct6_systems set ";
		
		makeUpdateCHAR($query, $fieldname, $value, $add_comma);
		
		$this->makeUpdateCHAR(   $update, "system_update_cuid",             $this->user_cuid,  true);  // VARCHAR2 system_update_cuid
		$this->makeUpdateDateNOW($update, "system_email_notification_date",                   false);  // DATE system_email_notification_date
		
		$update .= " where cm_ticket_no = '" . $cm_ticket_no . "'";
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$this->ora->commit();
		// AddEvent() not required here
		
		return true;	
	}	
	
	/*! @fn SetSystemWorkCancelDateByID($system_id)
	 *  @brief Set the system_work_cancel_date to the current date and time (SYSDATE)
	 *  @param $system_id is the cct6_systems unique record ID
	 *  @return true for success, false for failure. $this->error contains reason for failure
	 */	
	public function SetSystemWorkCancelDateByID($system_id, $cancel_message)
	{
		if (strlen($system_id) == 0)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id cannot be empty");
			$this->error = "SetSystemWorkCancelDateByID($system_id) - system_id cannot be empty";
			return false;
		}
		
		$update = "update cct6_systems set ";
		
		$this->makeUpdateCHAR(   $update, "system_update_cuid",       $this->user_cuid,  true);
		$this->makeUpdateCHAR(   $update, "system_update_name",       $this->user_name,  true);
		$this->makeUpdateCHAR(   $update, "system_work_status",       "CANCELED",        true);
		$this->makeUpdateDateNOW($update, "system_work_cancel_date",                     true);
		$this->makeUpdateDateNOW($update, "system_completion_date",                      true);
		$this->makeUpdateCHAR(   $update, "system_completion_status", "CANCELED",        true);
		$this->makeUpdateCHAR(   $update, "system_completion_cuid",   $this->user_cuid,  true);
		$this->makeUpdateCHAR(   $update, "system_completion_name",   $this->user_name,  true);
		$this->makeUpdateCHAR(   $update, "system_completion_notes ", $cancel_message,  false);
		
		$update .= "where system_id = " . $system_id;
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$this->log->AddEvent($system_id, "CANCEL", $cancel_message);
		
		$this->ora->commit();
		return true;
	}	
	
	/*! @fn SetAllSystemWorkCancelDateByTicket($cm_ticket_no)
	 *  @brief Set all system_work_cancel_dates to the current date and time (SYSDATE) identified by cm_ticket_no
	 *  @param $cm_ticket_no is the Remedy ticket no.
	 *  @return true for success, false for failure. $this->error contains reason for failure
	 */	
	public function SetAllSystemWorkCancelDateByTicket($cm_ticket_no, $cancel_message)
	{
		if (strlen($cm_ticket_no) == 0)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_ticket_no cannot be empty");
			$this->error = "SetAllSystemWorkCancelDateByTicket($cm_ticket_no) - cm_ticket_no cannot be empty";
			return false;
		}
		
		$update = "update cct6_systems set ";
		
		$this->makeUpdateCHAR(   $update, "system_update_cuid",       $this->user_cuid,  true);
		$this->makeUpdateCHAR(   $update, "system_update_name",       $this->user_name,  true);
		$this->makeUpdateCHAR(   $update, "system_work_status",       "CANCELED",        true);
		$this->makeUpdateDateNOW($update, "system_work_cancel_date",                     true);
		$this->makeUpdateDateNOW($update, "system_completion_date",                      true);
		$this->makeUpdateCHAR(   $update, "system_completion_status", "CANCELED",        true);
		$this->makeUpdateCHAR(   $update, "system_completion_cuid",   $this->user_cuid,  true);
		$this->makeUpdateCHAR(   $update, "system_completion_name",   $this->user_name,  true);
		$this->makeUpdateCHAR(   $update, "system_completion_notes ", $cancel_message,  false);
				
		$update .= " where cm_ticket_no = '" . $cm_ticket_no . "'";
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->ora->sql("select system_id from cct6_systems where cm_ticket_no = '" . $cm_ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		while ($this->ora->fetch())
		{
			$this->log->AddEvent($this->ora->system_id, "CANCEL", $cancel_message);
		}
		
		$this->ora->commit();
		return true;
	}	
	
	/*! @fn SetOverrideStatusByID($system_id, $override_status, $override_notes)
	 *  @brief Set the system_work_cancel_date to the current date and time (SYSDATE)
	 *  @param $system_id is the cct6_systems unique record ID
	 *  @param $override_status is the string containing the new work status. (READY, WAITING, REJECTED, RESCHEDULE, PENDING=No Contacts)
	 *  @param $override_notes is the override status notes entered by the user
	 *  @return true for success, false for failure. $this->error contains reason for failure
	 */	
	public function SetOverrideStatusByID($system_id, $override_status, $override_notes)
	{
		// system_id                       NUMBER          PRIMARY KEY Unique record ID
		// system_work_status              VARCHAR2(20)    READY, WAITING, REJECTED, RESCHEDULE, PENDING
		// system_override_status_date     DATE
		// system_override_status_cuid     VARCHAR2(20)
		// system_override_status_name     VARCHAR2(200)
		// system_override_status_notes    VARCHAR2(4000)
		
		if (strlen($system_id) == 0)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id cannot be empty");
			$this->error = "SetOverrideStatusByID($system_id, $override_status, $override_notes) - system_id cannot be empty";
			return false;
		}
		
		if (strlen($override_status) == 0)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "override_status cannot be empty");
			$this->error = "SetOverrideStatusByID($system_id, $override_status, $override_notes) - override_status cannot be empty";
			return false;
		}		
		
		$update = "update cct6_systems set ";
		
		$this->makeUpdateCHAR(   $update, "system_update_cuid",            $this->user_cuid,  true);
		$this->makeUpdateCHAR(   $update, "system_update_name",            $this->user_name,  true);
		$this->makeUpdateCHAR(   $update, "system_work_status",            $override_status,  true);
		$this->makeUpdateDateNOW($update, "system_override_status_date",                      true);
		$this->makeUpdateCHAR(   $update, "system_override_status_cuid",   $this->user_cuid,  true);
		$this->makeUpdateCHAR(   $update, "system_override_status_name",   $this->user_name,  true);
		$this->makeUpdateCHAR(   $update, "system_override_status_notes ", $override_notes,  false);
		
		$update .= "where system_id = " . $system_id;
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$change_status_message = "Status changed to " . $override_status . " by " . $this->user_name . ". " . $override_notes;
		
		$this->log->AddEvent($system_id, "LOCK", $change_status_message);
		
		$this->ora->commit();
		return true;			
	}		

	/*! @fn GetSystemByID($system_id)
	 *  @brief Get cct6_systems data for record identified by system_id
	 *  @param $system_id is the unique cct6_systems record id number
	 *  @return true for success, false for failure. $this->error contains reason for failure
	 */	
	public function GetSystemByID($system_id)
	{
		$query = "select * from cct6_systems where system_id = " . $system_id;
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		if ($this->ora->fetch())
		{
			$this->copySystemData();
			return true;
		}
		
		$this->error = "system_id ecord not found: " . $system_id;
		return false;
	}
	
	/*! @fn GetSystem($cm_ticket_no, $hostname)
	 *  @brief Get the system record identified by the ticket and hostname
	 *  @param $cm_ticket_no
	 *  @param $hostname
	 *  @return true for success, false for failure.
	 */	
	public function GetSystem($cm_ticket_no, $hostname)
	{
		$query = "select * from cct6_systems where cm_ticket_no = '" . $cm_ticket_no . "' and lower(computer_hostname) = lower('" . $hostname . "')";
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		if ($this->ora->fetch())
		{
			$this->copySystemData();
			return true;
		}
		
		$this->error = "Hostname: " . $hostname . " not found for ticket: " . $cm_ticket_no;
		return false;
	}
	
	/*! @fn copySystemData()
	 *  @brief Copies the oracle system data into global class variables for easy access
	 */		
	private function copySystemData()
	{
		$this->system_id = $this->ora->system_id;                                            // NUMBER   PRIMARY KEY Unique record ID
		$this->cm_ticket_no = $this->ora->cm_ticket_no;                                      // VARCHAR2 FOREIGN KEY cct6_tickets.cm_ticket_no
		$this->system_work_status = $this->ora->system_work_status;                          // VARCHAR2 Ready, Pending, Rejected, Reschedule
		$this->system_insert_cuid = $this->ora->system_insert_cuid;                          // VARCHAR2 CUID of person who created this record
		$this->system_insert_name = $this->ora->system_insert_name;                          // VARCHAR2 Name of person who created this record
		$this->system_insert_date = $this->ora->system_insert_date;                          // DATE     Date of person who created this record
		$this->system_update_cuid = $this->ora->system_update_cuid;                          // VARCHAR2 CUID of person who updated this record
		$this->system_update_name = $this->ora->system_update_name;                          // VARCHAR2 Name of person who updated this record
		$this->system_update_date = $this->ora->system_update_date;                          // DATE     Date of person who updated this record
		$this->computer_lastid = $this->ora->computer_lastid;                                // NUMBER   233494988
		$this->computer_last_update = $this->ora->computer_last_update;                      // DATE     15-FEB-13
		$this->computer_install_date = $this->ora->computer_install_date;                    // DATE     30-JUN-08
		$this->computer_systemname = $this->ora->computer_systemname;                        // VARCHAR2 HVDNP16E
		$this->computer_hostname = $this->ora->computer_hostname;                            // VARCHAR2 hvdnp16e
		$this->computer_operating_system = $this->ora->computer_operating_system;            // VARCHAR2 HP-UX B.11.31
		$this->computer_os_lite = $this->ora->computer_os_lite;                              // VARCHAR2 HPUX
		$this->computer_status = $this->ora->computer_status;                                // VARCHAR2 PRODUCTION
		$this->computer_status_description = $this->ora->computer_status_description;        // VARCHAR2 In Use
		$this->computer_description = $this->ora->computer_description;                      // VARCHAR2 Locally Administered MAC Address ia64 ...
		$this->computer_nature = $this->ora->computer_nature;                                // VARCHAR2 SUPPORT
		$this->computer_platform = $this->ora->computer_platform;                            // VARCHAR2 MIDRANGE
		$this->computer_type = $this->ora->computer_type;                                    // VARCHAR2 SERVER
		$this->computer_clli = $this->ora->computer_clli;                                    // VARCHAR2 DNVRCODP
		$this->computer_clli_fullname = $this->ora->computer_clli_fullname;                  // VARCHAR2 /USA/CO/DENVER/DNVCODP/
		$this->computer_timezone = $this->ora->computer_timezone;                            // VARCHAR2 MST
		$this->computer_building = $this->ora->computer_building;                            // VARCHAR2 DENVER BUILDING LOC
		$this->computer_address = $this->ora->computer_address;                              // VARCHAR2 5325 ZUNI ST
		$this->computer_city = $this->ora->computer_city;                                    // VARCHAR2 DENVER
		$this->computer_state = $this->ora->computer_state;                                  // VARCHAR2 CO
		$this->computer_floor_room = $this->ora->computer_floor_room;                        // VARCHAR2 2
		$this->computer_grid_location = $this->ora->computer_grid_location;                  // VARCHAR2 D-14
		$this->computer_lease_purchase = $this->ora->computer_lease_purchase;                // VARCHAR2 0
		$this->computer_serial_no = $this->ora->computer_serial_no;                          // VARCHAR2 USE7507MWL
		$this->computer_asset_tag = $this->ora->computer_asset_tag;                          // VARCHAR2 SYSGEN0787081606
		$this->computer_model_category = $this->ora->computer_model_category;                // VARCHAR2 /HARDWARE/COMPUTERS/VPAR/
		$this->computer_model_no = $this->ora->computer_model_no;                            // VARCHAR2 M389973
		$this->computer_model = $this->ora->computer_model;                                  // VARCHAR2 HP VIRTUAL MACHINE
		$this->computer_model_mfg = $this->ora->computer_model_mfg;                          // VARCHAR2 HEWLETT PACKARD
		$this->computer_cpu_type = $this->ora->computer_cpu_type;                            // VARCHAR2 Itanium 9100 Series
		$this->computer_cpu_count = $this->ora->computer_cpu_count;                          // NUMBER   0
		$this->computer_cpu_speed = $this->ora->computer_cpu_speed;                          // NUMBER   1670
		$this->computer_memory_mb = $this->ora->computer_memory_mb;                          // NUMBER   4089
		$this->computer_ip_address = $this->ora->computer_ip_address;                        // VARCHAR2 151.119.98.174
		$this->computer_domain = $this->ora->computer_domain;                                // VARCHAR2 qintra.com
		$this->computer_hostname_domain = $this->ora->computer_hostname_domain;              // VARCHAR2 hvdnp16e.qintra.com
		$this->computer_dmz = $this->ora->computer_dmz;                                      // VARCHAR2 N
		$this->computer_ewebars_title = $this->ora->computer_ewebars_title;                  // VARCHAR2 CMP-UNIX SUPPORT
		$this->computer_ewebars_status = $this->ora->computer_ewebars_status;                // VARCHAR2 SUPPORT
		$this->computer_backup_format = $this->ora->computer_backup_format;                  // VARCHAR2 TSM
		$this->computer_backup_nodename = $this->ora->computer_backup_nodename;              // VARCHAR2 ahvdnp16eh
		$this->computer_backup_program = $this->ora->computer_backup_program;                // VARCHAR2 TSM
		$this->computer_backup_server = $this->ora->computer_backup_server;                  // VARCHAR2 aidnb07g.qintra.com
		$this->computer_netbackup = $this->ora->computer_netbackup;                          // VARCHAR2 (null)
		$this->computer_complex = $this->ora->computer_complex;                              // VARCHAR2 N
		$this->computer_complex_lastid = $this->ora->computer_complex_lastid;                // NUMBER   225693687
		$this->computer_complex_name = $this->ora->computer_complex_name;                    // VARCHAR2 hhdnp85d
		$this->computer_complex_parent_name = $this->ora->computer_complex_parent_name;      // VARCHAR2 hcdnx11a
		$this->computer_complex_child_names = $this->ora->computer_complex_child_names;      // VARCHAR2 (null)
		$this->computer_complex_partitions = $this->ora->computer_complex_partitions;        // NUMBER   0
		$this->computer_service_guard = $this->ora->computer_service_guard;                  // VARCHAR2 N
		$this->computer_os_group_contact = $this->ora->computer_os_group_contact;            // VARCHAR2 mits-all
		$this->computer_cio_group = $this->ora->computer_cio_group;                          // VARCHAR2 CMP-UNIX SUPPORT
		$this->computer_managing_group = $this->ora->computer_managing_group;                // VARCHAR2 CMP-UNIX
		$this->computer_contract = $this->ora->computer_contract;                            // VARCHAR2 IGS FULL CONTRACT UNIX-PROD
		$this->computer_contract_ref = $this->ora->computer_contract_ref;                    // VARCHAR2 C028602
		$this->computer_contract_status = $this->ora->computer_contract_status;              // VARCHAR2 (null)
		$this->computer_contract_status_type = $this->ora->computer_contract_status_type;    // VARCHAR2 SERVER
		$this->computer_contract_date = $this->ora->computer_contract_date;                  // DATE     01-OCT-12
		$this->computer_ibm_supported = $this->ora->computer_ibm_supported;                  // VARCHAR2 Y
		$this->computer_gold_server = $this->ora->computer_gold_server;                      // VARCHAR2 Y
		$this->computer_slevel_objective = $this->ora->computer_slevel_objective;            // NUMBER   98
		$this->computer_slevel_score = $this->ora->computer_slevel_score;                    // NUMBER   44.3
		$this->computer_slevel_colors = $this->ora->computer_slevel_colors;                  // VARCHAR2 GOLD
		$this->computer_special_handling = $this->ora->computer_special_handling;            // VARCHAR2 N
		$this->computer_applications = $this->ora->computer_applications;                    // VARCHAR2 NBA
		$this->computer_osmaint_weekly = $this->ora->computer_osmaint_weekly;                // VARCHAR2 MON,TUE,WED,THU,FRI,SAT,SUN 2200 480
		$this->computer_osmaint_monthly = $this->ora->computer_osmaint_monthly;              // VARCHAR2 3 SUN 01:00 240
		$this->computer_osmaint_quarterly = $this->ora->computer_osmaint_quarterly;          // VARCHAR2 FEB,MAY,AUG,NOV 3 FRI 22:00 720
		$this->computer_csc_os_banners = $this->ora->computer_csc_os_banners;                // NUMBER   1
		$this->computer_csc_pase_banners = $this->ora->computer_csc_pase_banners;              // NUMBER   1
		$this->computer_csc_dba_banners = $this->ora->computer_csc_dba_banners;              // NUMBER   0
		$this->computer_csc_fyi_banners = $this->ora->computer_csc_fyi_banners;              // NUMBER   0
		$this->system_actual_work_start = $this->ora->system_actual_work_start;              // DATE     Actual computed work start datetime
		$this->system_actual_work_end = $this->ora->system_actual_work_end;                  // DATE     Actual computed work end datetime
		$this->system_actual_work_duration = $this->ora->system_actual_work_duration;        // VARCHAR2 Actual computed work duration window
		$this->system_original_work_start = $this->ora->system_original_work_start;          // DATE     Original computed work start datetime
		$this->system_original_work_end = $this->ora->system_original_work_end;              // DATE     Original computed work end datetime
		$this->system_original_work_duration = $this->ora->system_original_work_duration;    // VARCHAR2 Original computed work duration window
		$this->system_email_notification_date = $this->ora->system_email_notification_date;  // DATE     Date when first email notification was sent out
		$this->system_work_cancel_date = $this->ora->system_work_cancel_date;                // DATE     Date when work for this system was canceled
		$this->system_approvals_required = $this->ora->system_approvals_required;            // VARCHAR2 Are approvals required? Y or N
		$this->system_override_status_date = $this->ora->system_override_status_date;        // DATE     Date Final System Status Lock was applied
		$this->system_override_status_cuid = $this->ora->system_override_status_cuid;        // VARCHAR2 CUID of person who initiated Final System Status Lock
		$this->system_override_status_name = $this->ora->system_override_status_name;        // VARCHAR2 Name of person who initiated Final System Status Lock
		$this->system_override_status_notes = $this->ora->system_override_status_notes;      // VARCHAR2 Notes of person who initiated Final System Status Lock
		$this->system_completion_date = $this->ora->system_completion_date;                  // DATE     Date of Page/Email final completion notice to contacts
		$this->system_completion_status = $this->ora->system_completion_status;              // VARCHAR2 Status of completion notice: SUCCESS, FAILURE
		$this->system_completion_cuid = $this->ora->system_completion_cuid;                  // VARCHAR2 CUID of Page/Email person who recorded completion status
		$this->system_completion_name = $this->ora->system_completion_name;                  // VARCHAR2 Name of Page/Email person who recorded completion status
		$this->system_completion_notes = $this->ora->system_completion_notes;                // VARCHAR2 Completion notes
	}

	/*! @fn conflictsFound($system_id, $computer_hostname, $this_host_start_time, $this_host_end_time)
	 *  @brief Used to identify whether there scheduling conflicts.
	 *  @brief This function can also be found in get_request.php
	 *  @param $system_id is the record ID of the record found in cct6_systems.system_id
	 *  @param $computer_hostname is the hostname we are scanning for conflicts for
	 *  @param $this_host_start_time is the actual host start datetime
	 *  @param $this_host_end_time is the actual host end datetime
	 *  @return true for success, false for failure.
	 */		
	public function conflictsFound($system_id, $computer_hostname, $this_host_start_time, $this_host_end_time)
	{
		$S1 = strtotime($this_host_start_time);
		$E1 = strtotime($this_host_end_time);
		
		$query = "select cm_ticket_no, system_actual_work_start, system_actual_work_end from cct6_systems where " .
			"system_actual_work_end > SYSDATE and " .
			"system_id != " . $system_id . " and computer_hostname = '" . $computer_hostname . "' " . "order by system_actual_work_start";
			
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);
			
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return 'No';		
		}
		
		while ($this->ora->fetch())
		{
			$S2 = strtotime($this->ora->system_actual_work_start);
			$E2 = strtotime($this->ora->system_actual_work_end);
			
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "S1=%ld, E1=%ld, S2=%ld, E2=%ld", $S1, $E1, $S2, $E2);		

			//       S2-----E2
			// S1------E1    |            CONFLICT
			//       |  S1----E1          CONFLICT
			// S1--E1|       |            OKAY            
			//       |       |  S1---E1   OKAY 
			//
			if ($S1 >= $E1 || $S2 >= $E2)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "S1 >= E1 || S2 >= E2");
				return 'Yes';			
			}
			
			if ($E1 >= $S2 && $E1 <= $E2)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "E1 >= S2 && E1 <= E2");
				return 'Yes';			
			}
			
			if ($S1 >= $S2 && $S1 <= $E2)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "S1 >= S2 && S1 <= E2");
				return 'Yes';			
			}
		}
		
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "No conflicts");
		
		return 'No';
	}	
	
	/*! @fn getDuration($start, $end)
	 *  @brief Compute the duration from start/end dates and times
	 *  @param $start is the start datetime: MM/DD/YYYY HH:MI
	 *  @param $end is the end datetime: MM/DD/YYYY HH:MI
	 *  @return fixed banner string.
	 */		
	private function getDuration($start, $end)
	{
		$start_time = strtotime($start);
		$end_time   = strtotime($end);
		
		$seconds = $end_time - $start_time;
		
		$days  = floor($seconds / 60 / 60 / 24);
		$hours = $seconds / 60 / 60 % 24;
		$mins  = $seconds / 60 % 60;
		$secs  = $seconds % 60;
		
		$duration = '';

		$duration = sprintf("%02d%s%02d%s%02d", $days, ':', $hours, ':', $mins); // outputs 47:56:15
			
		return $duration;
	}	
}
?>
