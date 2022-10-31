<?php
/**
 * @package    CCT
 * @file       make_test_ticket.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       $Date:  $ GMT
 * @version    $Revision:  $
 *
 * $Log:  $
 *
 *
 * $Source:  $
 */

//
// Class autoloader - Removes the need to add include and require statements 
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

/** @class make_test_ticket
 *  @brief This class is used for creating a test ticket for a new CCT work request
 *  @brief Used in new_work_request_step2.php and cct6_tickets.php
 */
class make_test_ticket extends library
{
	var $data = array();            // Associative array for properties of $this->xxx
	
	/** @fn __construct()
	 *  @brief Class constructor - Create oracle object and setup some dynamic class variables
	 *  @return void 
	 */
	public function __construct()
	{
		// session_start(); must be called in calling module before $_SESSION['...'] data can be picked up here.
		if (session_id() == '')
			session_start();
		
		if (isset($_SESSION['user_cuid']))
		{
			$this->user_cuid          = $_SESSION['user_cuid'];
			$this->user_first_name    = $_SESSION['user_first_name'];
			$this->user_last_name     = $_SESSION['user_last_name'];
			$this->user_name          = $_SESSION['user_name'];
			$this->user_email         = $_SESSION['user_email'];
			$this->user_company       = $_SESSION['user_company'];
			$this->user_access_level  = $_SESSION['user_access_level'];
			
			$this->manager_cuid       = $_SESSION['manager_cuid'];
			$this->manager_first_name = $_SESSION['manager_first_name'];
			$this->manager_last_name  = $_SESSION['manager_last_name'];
			$this->manager_name       = $_SESSION['manager_name'];
			$this->manager_email      = $_SESSION['manager_email'];
			$this->manager_company    = $_SESSION['manager_company'];
			
			$this->is_debug_on        = $_SESSION['is_debug_on'];
		}
		else
		{
			$this->user_cuid          = 'cctadm';
			$this->user_first_name    = 'Application';
			$this->user_last_name     = 'CCT';
			$this->user_name          = 'CCT Application';
			$this->user_email         = 'gregparkin58@gmail.com';
			$this->user_company       = 'CMP';
			$this->user_access_level  = 'admin';
			
			$this->manager_cuid       = 'gparkin';
			$this->manager_first_name = 'Greg';
			$this->manager_last_name  = 'Parkin';
			$this->manager_name       = 'Greg Parkin';
			$this->manager_email      = 'gregparkin58@gmail.com';
			$this->manager_company    = 'CMP';
			
			$this->is_debug_on        = 'N';
		}
		
		$this->debug_start('make_test_ticket.html');
	}
	
	/** @fn __destruct()
	 *  @brief Destructor function called when no other references to this object can be found, or in any
	 *  order during the shutdown sequence. The destructor will be called even if script execution
	 *  is stopped using exit(). Calling exit() in a destructor will prevent the remaining shutdown
	 *  routines from executing.
	 *  @brief Attempting to throw an exception from a destructor (called in the time of script termination)
	 *  causes a fatal error.
	 *  @return null 
	 */	
	public function __destruct()
	{
	}
	
	/** @fn __set($name, $value)
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
	
	/** @fn __get($name)
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
	
	/** @fn __isset($name)
	 *  @brief Determine if item ($name) exists in the $this->data array
	 *  @brief var_dump(isset($obj->first_name));
	 *  @param $name is the key in the associated $data array
	 *  @return null 
	 */	
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}
	
	/** @fn __unset($name)
	 *  @brief Unset an item in $this->data assoicated by $name
	 *  @brief unset($obj->name);
	 *  @param $name is the key in the associated $data array
	 *  @return null 
	 */	
	public function __unset($name)
	{
		unset($this->data[$name]);
	}

	/** @fn make_ticket($ora, $ticket_no='')
	 *  @brief Create a test (dummy) ticket that can be used in the creation of a CCT work request. (TESTING ONLY)
	 *  Test ticket is copied to the oracle $ora object
	 *  @param $ora is a pointer to the oracle oracle object (classes/oracle.php)
	 *  @param $ticket_no is the ticket number we want assigned to the ticket. If blank we let the routine create one.
	 *  @return true or false, where true is success 
	 */	
	public function make_ticket($ora, $ticket_no='')
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = (%s)", $ticket_no);
		
		if (!isset($ora) || $ora == NULL)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora object is NULL or non-existent!");
			return false;
		}

		//
		// Grab the MNET record for this person.
		//
		if ($ora->sql("select * from cct6_mnet where mnet_cuid = '" . $this->user_cuid . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		if ($ora->fetch() == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Cannot retrieve MNET record for: %s", $this->user_cuid);
			$this->error = sprintf("Cannot retrieve MNET record for: %s", $this->user_cuid);	
			return false;
		}

		// $_SESSION[local_timezone]      => America/Denver (MDT)
		// $_SESSION[local_timezone_name] => America/Denver
		// $_SESSION[local_timezone_abbr] => MDT

		$dateTime = new DateTime();  // Set to today's current date and time.
		$dateTime->setTimeZone(new DateTimeZone($_SESSION['local_timezone_name']));  // Set to user's local timezone

		//
		// Create some dates for this ticket
		//
        if (strtoupper($ticket_no) == "SHORT")
		{
			$start_date  = sprintf("%s 21:00", $dateTime->format('m/d/Y'));
			$end_date    = sprintf("%s 23:00", $dateTime->format('m/d/Y'));
		}
		else // $ticket_no == "test"
		{
			$time_start_date  = time() + (10 * 24 * 60 * 60);  // start_date  = '08/21/2013 17:00   today's date + 10 days
			$time_end_date    = time() + (20 * 24 * 60 * 60);  // end_date    = '08/22/2013 04:00   today's date + 20 days

			$start_date  = sprintf("%s 21:00", date('m/d/Y', $time_start_date));
			$end_date    = sprintf("%s 23:00", date('m/d/Y', $time_end_date));
		}

		$create_date = $dateTime->format('m/d/Y H:i');	      // create_date = '08/13/2013 09:17   today's date and time
		
		$this->start_date_char = $start_date;
        $this->start_date_num  = $this->mmmddyyyy_hhmm_to_gmt_utime($start_date, $_SESSION['local_timezone_name']);

        $this->end_date_char   = $end_date;
        $this->end_date_num    = $this->mmmddyyyy_hhmm_to_gmt_utime($end_date, $_SESSION['local_timezone_name']);
		
		//
		// Create a CCT test ticket
		//
        $ora->change_id = sprintf("CCT%08d", $ora->next_seq('cct7_test_ticketseq'));

		$ora->assign_group                = 'AIM-TOOLS-BOM';
		$ora->category                    = 'Software';
		$ora->category_type               = 'Operating System';
		$ora->closed_by                   = '';
		$ora->close_code                  = '';
		$ora->close_date                  = '';
		$ora->close_date_num              = 0;
		$ora->component                   = '';
		$ora->scheduling_flexibility      = 'Flexible';
		$ora->end_date                    = $end_date;               // See above: end_date = today + 20 days at 23:00:00
		$ora->end_date_num                = $this->mmmddyyyy_hhmm_to_gmt_utime($end_date, $_SESSION['local_timezone_name']);

		$ora->entered_by                  = $ora->mnet_cuid;         // Users MNET record
		$ora->exp_code                    = '';
		$ora->fix_level                   = '';
		$ora->impact                      = 'TEST ONLY';
		$ora->implementor_first_last      = $ora->mnet_name;         // Users MNET record
		$ora->implementor_login           = $ora->mnet_cuid;         // Users MNET record
		$ora->ipl_boot                    = 'Y';
		$ora->late                        = 'Y';
		$ora->parent_ir                   = '';
		$ora->normal_release_session      = '';
		$ora->create_date                 = $create_date;            // See above: create_date = NOW
		$ora->create_date_num             = $this->mmmddyyyy_hhmm_to_gmt_utime($create_date, $_SESSION['local_timezone_name']);
		$ora->pin                         = '';
		$ora->plan_a_b                    = 'B';
		$ora->product                     = '';
		$ora->product_type                = '';
		$ora->risk                        = '2';
		$ora->software_object             = '';
		$ora->start_date                  = $start_date;            // See above: start_date = today + 10 days starting at 22:00
		$ora->start_date_num              = $this->mmmddyyyy_hhmm_to_gmt_utime($start_date, $_SESSION['local_timezone_name']);
		$ora->status                      = 'Pending';
		$ora->tested                      = 'Y';
		$ora->duration                    = '7200';
		$ora->business_unit               = '';
		$ora->duration_computed           = '0 : 2 : 0';
		$ora->email                       = $ora->mnet_email;       // Users MNET record
		$ora->company_name                = $ora->mnet_company;     // Users MNET record
		$ora->tested_itv                  = 'Y';
		$ora->tested_endtoend             = 'N';
		$ora->tested_development          = 'N';
		$ora->tested_user                 = 'N';
		$ora->owner_name                  = $ora->mnet_name;        // Users MNET record
		$ora->owner_first_name            = $ora->mnet_first_name;  // Users MNET record
		$ora->owner_last_name             = $ora->mnet_last_name;   // Users MNET record
		$ora->owner_cuid                  = $ora->mnet_cuid;        // Users MNET record
		$ora->pager                       = $ora->mnet_pager;       // Users MNET record
		$ora->phone                       = $ora->mnet_work_phone;  // Users MNET record
		$ora->groupid                     = '';
		$ora->temp                        = '';
		$ora->last_modified_by            = '';
		$ora->last_modified               = '';
		$ora->last_modified_num           = 0;
		$ora->late_date                   = '';
		$ora->late_date_num               = 0;
		$ora->risk_integer                = '2';
		$ora->owner_login_id              = $ora->mnet_id;          // Users MNET record
		$ora->open_closed                 = 'Open';
		$ora->user_timestamp              = '';
		$ora->description                 = 'TEST ONLY';
		$ora->implementation_instructions = 'TEST ONLY';
		$ora->business_reason             = 'TEST ONLY';
		$ora->backoff_plan                = 'TEST ONLY';

		$ora->change_occurs               = 'Y';
		$ora->lla_refresh                 = 'Y';
		$ora->ims_cold_start              = 'Y';
		$ora->release_level               = '';
		$ora->master_ir                   = '';
		$ora->owner_group                 = 'CMP-TOOLS';
		$ora->cab_approval_required       = 'N';
		$ora->change_executive_team_flag  = 'N';
		$ora->emergency_change            = 'N';
		$ora->approval_status             = '1';
		$ora->component_type              = 'Processor';
		$ora->desc_short                  = 'CCT TEST Ticket';
		$ora->last_status_change_by       = '';
		$ora->last_status_change_time     = '';
		$ora->last_status_change_time_num = 0;
		$ora->previous_status             = 'Pending';
		$ora->component_id                = '665957193';
		$ora->test_tool                   = '';
		$ora->tested_orl                  = 'N';
		$ora->featured_project            = 'N';
		$ora->featured_proj_name          = '';
		$ora->tmpmainplatform             = 'Midrange';
		$ora->tmpblockmessage             = '';
		$ora->guid                        = '';
		$ora->platform                    = 'Midrange';
		$ora->cllicodes                   = '';
		$ora->processor_name              = '';
		$ora->system_name                 = '';
		$ora->city                        = '';
		$ora->turn_overdate               = '';
		$ora->turn_overdate_num           = 0;

		$ora->director                    = 'Simon, Elizabeth G';   // Default Director
		$ora->manager                     = 'Kobialka, Janet';       // Default Manager
			
		if (strlen($ora->mnet_mgr_cuid) > 0)
		{
			if ($ora->sql("select * from cct6_mnet where mnet_cuid = '" . $ora->mnet_mgr_cuid . "'") == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
				$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
				return false;
			}
			
			if ($ora->fetch())
			{
				$ora->manager = $ora->mnet_name; // Users MNET record
			}
		}
		
		if (strlen($ora->mnet_mgr_cuid) > 0)
		{
			if ($ora->sql("select * from cct6_mnet where mnet_cuid = '" . $ora->mnet_mgr_cuid . "'") == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
				$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
				return false;
			}
			
			if ($ora->fetch())
			{
				$ora->director = $ora->mnet_name; // Users MNET record
			}
		}		
		
		//
		// Dump variables in sorted order for our viewing pleasure!
		//
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->change_id = %s", $ora->change_id);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->approval_status = %s", $ora->approval_status);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->assign_group = %s", $ora->assign_group);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->backoff_plan = %s", $ora->backoff_plan);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->business_reason = %s", $ora->business_reason);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->business_unit = %s", $ora->business_unit);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->cab_approval_required = %s", $ora->cab_approval_required);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->category = %s", $ora->category);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->category_type = %s", $ora->category_type);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->change_executive_team_flag = %s", $ora->change_executive_team_flag);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->change_id = %s", $ora->change_id);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->change_occurs = %s", $ora->change_occurs);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->city = %s", $ora->city);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->cllicodes = %s", $ora->cllicodes);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->close_code = %s", $ora->close_code);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->close_date = %s", $ora->close_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->closed_by = %s", $ora->closed_by);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->company_name = %s", $ora->company_name);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->component = %s", $ora->component);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->component_id = %s", $ora->component_id);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->component_type = %s", $ora->component_type);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->create_date = %s", $ora->create_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->description = %s", $ora->description);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->desc_short = %s", $ora->desc_short);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->director = %s", $ora->director);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->duration = %s", $ora->duration);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->duration_computed = %s", $ora->duration_computed);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->email = %s", $ora->email);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->emergency_change = %s", $ora->emergency_change);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->end_date = %s", $ora->end_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->entered_by = %s", $ora->entered_by);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->exp_code = %s", $ora->exp_code);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->featured_project = %s", $ora->featured_project);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->featured_proj_name = %s", $ora->featured_proj_name);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->fix_level = %s", $ora->fix_level);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->groupid = %s", $ora->groupid);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->guid = %s", $ora->guid);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->impact = %s", $ora->impact);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->implementation_instructions = %s", $ora->implementation_instructions);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->implementor_first_last = %s", $ora->implementor_first_last);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->implementor_login = %s", $ora->implementor_login);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->ims_cold_start = %s", $ora->ims_cold_start);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->ipl_boot = %s", $ora->ipl_boot);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->last_modified = %s", $ora->last_modified);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->last_modified_by = %s", $ora->last_modified_by);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->last_status_change_by = %s", $ora->last_status_change_by);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->last_status_change_time = %s", $ora->last_status_change_time);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->late = %s", $ora->late);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->late_date = %s", $ora->late_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->lla_refresh = %s", $ora->lla_refresh);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->manager = %s", $ora->manager);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->master_ir = %s", $ora->master_ir);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->normal_release_session = %s", $ora->normal_release_session);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->open_closed = %s", $ora->open_closed);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->owner_cuid = %s", $ora->owner_cuid);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->owner_first_name = %s", $ora->owner_first_name);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->owner_group = %s", $ora->owner_group);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->owner_last_name = %s", $ora->owner_last_name);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->owner_login_id = %s", $ora->owner_login_id);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->owner_name = %s", $ora->owner_name);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->pager = %s", $ora->pager);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->parent_ir = %s", $ora->parent_ir);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->phone = %s", $ora->phone);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->pin = %s", $ora->pin);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->plan_a_b = %s", $ora->plan_a_b);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->platform = %s", $ora->platform);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->previous_status = %s", $ora->previous_status);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->processor_name = %s", $ora->processor_name);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->product = %s", $ora->product);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->product_type = %s", $ora->product_type);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->release_level = %s", $ora->release_level);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->risk = %s", $ora->risk);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->risk_integer = %s", $ora->risk_integer);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->scheduling_flexibility = %s", $ora->scheduling_flexibility);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->software_object = %s", $ora->software_object);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->start_date = %s", $ora->start_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->status = %s", $ora->status);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->system_name = %s", $ora->system_name);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->temp = %s", $ora->temp);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->tested = %s", $ora->tested);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->tested_development = %s", $ora->tested_development);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->tested_endtoend = %s", $ora->tested_endtoend);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->tested_itv = %s", $ora->tested_itv);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->tested_orl = %s", $ora->tested_orl);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->tested_user = %s", $ora->tested_user);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->test_tool = %s", $ora->test_tool);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->tmpblockmessage = %s", $ora->tmpblockmessage);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->tmpmainplatform = %s", $ora->tmpmainplatform);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ora->user_timestamp = %s", $ora->user_timestamp);
		
		return true;
	}
	
} // END: class make_test_ticket extends library
?>
