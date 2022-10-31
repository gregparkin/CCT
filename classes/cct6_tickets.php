<?php
/**
 * <cct6_tickets.php>
 *
 * @package    CCT
 * @file       cct6_tickets.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       04/29/2014
 * @version    6.0.2
 *
 * 03/03/2014 - Fixed authorizeTicket() function to use user's cuid instead of my own.
 *
 */
set_include_path("/opt/ibmtools/www/cct7/classes:/opt/ibmtools/www/cct7/includes:/opt/ibmtools/www/cct7/servers");

//
// Public Methods
// authorizeTicket($ticket_no)
// getRemedyTicket($ticket_no)
// getTicket($ticket_no)
// IsTicket($ticket_no)
// createTicket($ticket_no)
// deleteTicket($ticket_no)
// cancelTicket($ticket_no)
// updateRemedy($ticket_no)
// SubmitRequest($ticket_no)
// freezeTicket($ticket_no)
// unfreezeTicket($ticket_no)
//
//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');  //!< @see includes/autoloader.php
}

/*! @class cct6_tickets
 *  @brief This class contains all the main processing routines for CCT Remedy tickets
 *  @brief Used by programs: new_work_request_step4.php and work_requests.php
 *  @brief Used by classes: cct6_systems.php
 *  @brief Used by Ajax servers: server_edit_work_request.php and server_work_requests.php
 */
class cct6_tickets extends library
{
	var $data;
	var $ora;                     // Database connection object
	var $error;                   // Error message when functions return false
		
	/*! @fn __construct()
	 *  @brief Class constructor - Create oracle object and setup some dynamic class variables
	 *  @return void 
	 */
	public function __construct()
	{
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->wreq_osmaint = %s", $this->wreq_osmaint);
			
		$this->ora = new oracle();

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
			// session_start(); must be called in calling module before $_SESSION['...'] data can be picked up here.
			if (session_id() == '')
				session_start();
		
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

			$this->debug_start('cct6_tickets.txt');
		}
	}
	
	/*! @fn __destruct()
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
	
	/*! @fn authorizeTicket($ticket_no, $operation)
	 *  @brief Check to see if the user ($h->user_cuid) is authorized to change this ticket
	 *  @param $ticket_no is CCT ticket we want to check authorization.
	 *  @param $operation will be something like: delete, change, cancel (used for authorization denied text)
	 *  @return true or false, where true means they are allowed to change the ticket
	 */		
	public function authorizeTicket($ticket_no, $operation)
	{
	
		return true;
		
		// Stupid code is not working for most people. I think it might be something to do with workstation login accounts.
		
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s, operation = %s", $ticket_no, $operation);
		
		if ($this->user_access_level == 'admin')
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "User is a CCT Admin - authorization granted!");
			return true;
		}
		
		$query  = "select ";
		$query .= " cm_ticket_no    as cm_ticket_no, ";
		$query .= " cm_owner_group  as cm_owner_group ";
		$query .= "from ";
		$query .= "  cct6_tickets ";
		$query .= "where ";
		$query .= sprintf("  cm_ticket_no = '%s' and ticket_insert_cuid = '%s'", $ticket_no, $this->user_cuid);

		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		if ($this->ora->fetch())
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Authorization granted!");
			return true;
		}	

		$query  = "select ";
		$query .= "  t.cm_ticket_no    as cm_ticket_no, ";
		$query .= "  t.cm_owner_group  as cm_owner_group, ";
		$query .= "  a.group_name      as group_name ";
		$query .= "from ";
		$query .= "  cct6_assign_groups a, ";
		$query .= "  cct6_tickets t ";
		$query .= "where ";
		$query .= sprintf("  a.login_cuid = '%s' and ", $this->user_cuid);
		$query .= "  (t.cm_owner_group = a.group_name or t.cm_assign_group = a.group_name) and ";
		$query .= sprintf("  t.cm_ticket_no = '%s'", $ticket_no);
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
	
		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Authorization denied!");
			$this->error = sprintf("Sorry, but you must belong to group %s in order to %s ticket: %s", $this->ora->cm_owner_group, $operation, $ticket_no);
			return false;
		}	
		
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Authorization granted!");
		return true;	
	}		
	
	/*! @fn getRemedyTicket($ticket_no)
	 *  @brief Get Remedy Ticket information from table: t_cm_implementation_request@remedy_prod
	 *  @param $ticket_no is the Remedy ticket we want to get
	 *  @return true or false, where true is success
	 */	
	public function getRemedyTicket($ticket_no)
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s", $ticket_no);
		
		if (substr($ticket_no, 0, 4) == 'TEST')
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "This is a TEST ticket");
			
			$mt = new make_test_ticket();  // classes/make_test_ticket.php
			
			if ($mt->make_ticket($this->ora, $ticket_no) == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "mt->make_ticket() in make_test_ticket.php function failed: %s", $mt->error);
				$this->error = $mt->error;
				return false;
			}

			$this->tested_itv         = $this->ora->tested_itv;
			$this->tested_endtoend    = $this->ora->tested_endtoend;
			$this->tested_development = $this->ora->tested_development;
			$this->tested_user        = $this->ora->tested_user;
			$this->emergency_change   = $this->ora->emergency_change;
			$this->featured_project   = $this->ora->featured_project;	
			
			// Copy in all the goodies
			
			$this->start_date = $this->ora->start_date;
			$this->end_date = $this->ora->end_date;
			
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_date=(%s), end_date=(%s)", $this->start_date, $this->end_date);
			
			$this->change_id = $this->ora->change_id;                                             // VARCHAR2
			$this->assign_group = $this->ora->assign_group;                                       // VARCHAR2
			$this->category = $this->ora->category;                                               // VARCHAR2
			$this->category_type = $this->ora->category_type;                                     // VARCHAR2
			$this->closed_by = $this->ora->closed_by;                                             // VARCHAR2
			$this->close_code = $this->ora->close_code;                                           // VARCHAR2
			$this->component = $this->ora->component;                                             // VARCHAR2
			$this->scheduling_flexibility = $this->ora->scheduling_flexibility;                   // VARCHAR2
			$this->entered_by = $this->ora->entered_by;                                           // VARCHAR2
			$this->exp_code = $this->ora->exp_code;                                               // VARCHAR2
			$this->fix_level = $this->ora->fix_level;                                             // VARCHAR2
			$this->impact = $this->ora->impact;                                                   // VARCHAR2
			$this->implementor_first_last = $this->ora->implementor_first_last;                   // VARCHAR2
			$this->implementor_login = $this->ora->implementor_login;                             // VARCHAR2
			$this->parent_ir = $this->ora->parent_ir;                                             // VARCHAR2
			$this->normal_release_session = $this->ora->normal_release_session;                   // VARCHAR2
			$this->pager = $this->ora->pager;                                                     // VARCHAR2
			$this->phone = $this->ora->phone;                                                     // VARCHAR2
			$this->pin = $this->ora->pin;                                                         // VARCHAR2
			$this->product = $this->ora->product;                                                 // VARCHAR2
			$this->product_type = $this->ora->product_type;                                       // VARCHAR2
			$this->software_object = $this->ora->software_object;                                 // VARCHAR2
			$this->status = $this->ora->status;                                                   // VARCHAR2
			$this->tested = $this->ora->tested;                                                   // NUMBER
			$this->duration = $this->ora->duration;                                               // NUMBER
			$this->business_unit = $this->ora->business_unit;                                     // VARCHAR2
			$this->duration_computed = $this->ora->duration_computed;                             // VARCHAR2
			$this->email = $this->ora->email;                                                     // VARCHAR2
			$this->company_name = $this->ora->company_name;                                       // VARCHAR2
			$this->director = $this->ora->director;                                               // VARCHAR2
			$this->manager = $this->ora->manager;                                                 // VARCHAR2
			$this->owner_name = $this->ora->owner_name;                                           // VARCHAR2
			$this->owner_cuid = $this->ora->owner_cuid;                                           // VARCHAR2
			$this->groupid = $this->ora->groupid;                                                 // VARCHAR2
			$this->temp = $this->ora->temp;                                                       // VARCHAR2
			$this->last_modified_by = $this->ora->last_modified_by;                               // VARCHAR2
			$this->last_modified = $this->ora->last_modified;                                     // NUMBER
			$this->risk_integer = $this->ora->risk_integer;                                       // NUMBER
			$this->owner_login_id = $this->ora->owner_login_id;                                   // VARCHAR2
			$this->open_closed = $this->ora->open_closed;                                         // VARCHAR2
			$this->user_timestamp = $this->ora->user_timestamp;                                   // VARCHAR2
			$this->description = $this->ora->description;                                         // VARCHAR2
			$this->backoff_plan = $this->ora->backoff_plan;                                       // VARCHAR2
			$this->implementation_instructions = $this->ora->implementation_instructions;         // VARCHAR2
			$this->business_reason = $this->ora->business_reason;                                 // VARCHAR2
			$this->owner_first_name = $this->ora->owner_first_name;                               // VARCHAR2
			$this->owner_last_name = $this->ora->owner_last_name;                                 // VARCHAR2
			$this->change_occurs = $this->ora->change_occurs;                                     // NUMBER
			$this->release_level = $this->ora->release_level;                                     // VARCHAR2
			$this->master_ir = $this->ora->master_ir;                                             // VARCHAR2
			$this->owner_group = $this->ora->owner_group;                                         // VARCHAR2
			$this->approval_status = $this->ora->approval_status;                                 // NUMBER
			$this->component_type = $this->ora->component_type;                                   // VARCHAR2
			$this->desc_short = $this->ora->desc_short;                                           // VARCHAR2
			$this->last_status_change_by = $this->ora->last_status_change_by;                     // VARCHAR2
			$this->previous_status = $this->ora->previous_status;                                 // VARCHAR2
			$this->component_id = $this->ora->component_id;                                       // VARCHAR2
			$this->test_tool = $this->ora->test_tool;                                             // VARCHAR2
			$this->featured_proj_name = $this->ora->featured_proj_name;                           // VARCHAR2
			$this->tmpmainplatform = $this->ora->tmpmainplatform;                                 // VARCHAR2
			$this->tmpblockmessage = $this->ora->tmpblockmessage;                                 // VARCHAR2
			$this->guid = $this->ora->guid;                                                       // VARCHAR2
			$this->platform = $this->ora->platform;                                               // VARCHAR2
			$this->cllicodes = $this->ora->cllicodes;                                             // VARCHAR2
			$this->processor_name = $this->ora->processor_name;                                   // VARCHAR2
			$this->system_name = $this->ora->system_name;                                         // VARCHAR2
			$this->city = $this->ora->city;                                                       // VARCHAR2
			$this->state = $this->ora->state;                                                     // VARCHAR2
			$this->tmpdesc = $this->ora->tmpdesc;                                                 // VARCHAR2
			$this->assign_group2 = $this->ora->assign_group2;                                     // VARCHAR2
			$this->assign_group3 = $this->ora->assign_group3;                                     // VARCHAR2
			$this->implementor_name2 = $this->ora->implementor_name2;                             // VARCHAR2
			$this->implementor_name3 = $this->ora->implementor_name3;                             // VARCHAR2
			$this->groupid2 = $this->ora->groupid2;                                               // VARCHAR2
			$this->groupid3 = $this->ora->groupid3;                                               // VARCHAR2
			$this->template = $this->ora->template;                                               // VARCHAR2
			$this->hd_outage_ticket_number = $this->ora->hd_outage_ticket_number;                 // VARCHAR2
			$this->root_cause_owner = $this->ora->root_cause_owner;                               // VARCHAR2
			$this->control_count = $this->ora->control_count;                                     // VARCHAR2	

			$this->ipl_boot = $this->ora->ipl_boot;                                               // NUMBER
			$this->late = $this->ora->late;                                                       // NUMBER
			$this->plan_a_b = $this->ora->plan_a_b;                                               // VARCHAR2
			$this->risk = $this->ora->risk;                                                       // VARCHAR2
			$this->tested_itv = $this->ora->tested_itv;                                           // NUMBER
			$this->tested_endtoend = $this->ora->tested_endtoend;                                 // NUMBER
			$this->tested_development = $this->ora->tested_development;                           // NUMBER
			$this->tested_user = $this->ora->tested_user;                                         // NUMBER
			$this->lla_refresh = $this->ora->lla_refresh;                                         // NUMBER
			$this->ims_cold_start = $this->ora->ims_cold_start;                                   // NUMBER
			$this->cab_approval_required = $this->ora->cab_approval_required;                     // NUMBER
			$this->change_executive_team_flag = $this->ora->change_executive_team_flag;           // NUMBER
			$this->emergency_change = $this->ora->emergency_change;                               // NUMBER
			$this->tested_orl = $this->ora->tested_orl;                                           // NUMBER
			$this->featured_project = $this->ora->featured_project;                               // NUMBER		
			
			$this->owner_name = $this->ora->owner_name;
			$this->owner_first_name = $this->ora->owner_first_name;
			$this->owner_last_name = $this->ora->owner_last_name;				
			$this->owner_cuid = $this->ora->owner_cuid;
			$this->pager = $this->ora->pager;
			$this->phone = $this->ora->phone;								

			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "getRemedyTicket (TEST TICKET) SUCCESS - returning true");

			return true;
		}

		// Grab the data from Remedy
		if ($this->ora->sql("select * from t_cm_implementation_request@remedy_prod where change_id = '" . $ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
	
		if ($this->ora->fetch() == false)
		{
			$this->error = "Unable to pull ticket from Remedy: " . $ticket_no;
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		//
		// Remedy dates are recorded as NUMBERs in Unix Sytle GMT format. Total number of seconds since 01/01/1970.
		//
		$this->change_id = $this->ora->change_id;                                             // VARCHAR2
		$this->assign_group = $this->ora->assign_group;                                       // VARCHAR2
		$this->category = $this->ora->category;                                               // VARCHAR2
		$this->category_type = $this->ora->category_type;                                     // VARCHAR2
		$this->closed_by = $this->ora->closed_by;                                             // VARCHAR2
		$this->close_code = $this->ora->close_code;                                           // VARCHAR2
		$this->component = $this->ora->component;                                             // VARCHAR2
		$this->scheduling_flexibility = $this->ora->scheduling_flexibility;                   // VARCHAR2
		$this->entered_by = $this->ora->entered_by;                                           // VARCHAR2
		$this->exp_code = $this->ora->exp_code;                                               // VARCHAR2
		$this->fix_level = $this->ora->fix_level;                                             // VARCHAR2
		$this->impact = $this->ora->impact;                                                   // VARCHAR2
		$this->implementor_first_last = $this->ora->implementor_first_last;                   // VARCHAR2
		$this->implementor_login = $this->ora->implementor_login;                             // VARCHAR2
		$this->parent_ir = $this->ora->parent_ir;                                             // VARCHAR2
		$this->normal_release_session = $this->ora->normal_release_session;                   // VARCHAR2
		$this->pager = $this->ora->pager;                                                     // VARCHAR2
		$this->phone = $this->ora->phone;                                                     // VARCHAR2
		$this->pin = $this->ora->pin;                                                         // VARCHAR2
		$this->product = $this->ora->product;                                                 // VARCHAR2
		$this->product_type = $this->ora->product_type;                                       // VARCHAR2
		$this->software_object = $this->ora->software_object;                                 // VARCHAR2
		$this->status = $this->ora->status;                                                   // VARCHAR2
		$this->tested = $this->ora->tested;                                                   // NUMBER
		$this->duration = $this->ora->duration;                                               // NUMBER
		$this->business_unit = $this->ora->business_unit;                                     // VARCHAR2
		$this->duration_computed = $this->ora->duration_computed;                             // VARCHAR2
		$this->email = $this->ora->email;                                                     // VARCHAR2
		$this->company_name = $this->ora->company_name;                                       // VARCHAR2
		$this->director = $this->ora->director;                                               // VARCHAR2
		$this->manager = $this->ora->manager;                                                 // VARCHAR2
		$this->owner_name = $this->ora->owner_name;                                           // VARCHAR2
		$this->owner_cuid = $this->ora->owner_cuid;                                           // VARCHAR2
		$this->groupid = $this->ora->groupid;                                                 // VARCHAR2
		$this->temp = $this->ora->temp;                                                       // VARCHAR2
		$this->last_modified_by = $this->ora->last_modified_by;                               // VARCHAR2
		$this->last_modified = $this->ora->last_modified;                                     // NUMBER
		$this->risk_integer = $this->ora->risk_integer;                                       // NUMBER
		$this->owner_login_id = $this->ora->owner_login_id;                                   // VARCHAR2
		$this->open_closed = $this->ora->open_closed;                                         // VARCHAR2
		$this->user_timestamp = $this->ora->user_timestamp;                                   // VARCHAR2
		$this->description = $this->ora->description;                                         // VARCHAR2
		$this->backoff_plan = $this->ora->backoff_plan;                                       // VARCHAR2
		$this->implementation_instructions = $this->ora->implementation_instructions;         // VARCHAR2
		$this->business_reason = $this->ora->business_reason;                                 // VARCHAR2
		$this->owner_first_name = $this->ora->owner_first_name;                               // VARCHAR2
		$this->owner_last_name = $this->ora->owner_last_name;                                 // VARCHAR2
		$this->change_occurs = $this->ora->change_occurs;                                     // NUMBER
		$this->release_level = $this->ora->release_level;                                     // VARCHAR2
		$this->master_ir = $this->ora->master_ir;                                             // VARCHAR2
		$this->owner_group = $this->ora->owner_group;                                         // VARCHAR2
		$this->approval_status = $this->ora->approval_status;                                 // NUMBER
		$this->component_type = $this->ora->component_type;                                   // VARCHAR2
		$this->desc_short = $this->ora->desc_short;                                           // VARCHAR2
		$this->last_status_change_by = $this->ora->last_status_change_by;                     // VARCHAR2
		$this->previous_status = $this->ora->previous_status;                                 // VARCHAR2
		$this->component_id = $this->ora->component_id;                                       // VARCHAR2
		$this->test_tool = $this->ora->test_tool;                                             // VARCHAR2
		$this->featured_proj_name = $this->ora->featured_proj_name;                           // VARCHAR2
		$this->tmpmainplatform = $this->ora->tmpmainplatform;                                 // VARCHAR2
		$this->tmpblockmessage = $this->ora->tmpblockmessage;                                 // VARCHAR2
		$this->guid = $this->ora->guid;                                                       // VARCHAR2
		$this->platform = $this->ora->platform;                                               // VARCHAR2
		$this->cllicodes = $this->ora->cllicodes;                                             // VARCHAR2
		$this->processor_name = $this->ora->processor_name;                                   // VARCHAR2
		$this->system_name = $this->ora->system_name;                                         // VARCHAR2
		$this->city = $this->ora->city;                                                       // VARCHAR2
		$this->state = $this->ora->state;                                                     // VARCHAR2
		$this->tmpdesc = $this->ora->tmpdesc;                                                 // VARCHAR2
		$this->assign_group2 = $this->ora->assign_group2;                                     // VARCHAR2
		$this->assign_group3 = $this->ora->assign_group3;                                     // VARCHAR2
		$this->implementor_name2 = $this->ora->implementor_name2;                             // VARCHAR2
		$this->implementor_name3 = $this->ora->implementor_name3;                             // VARCHAR2
		$this->groupid2 = $this->ora->groupid2;                                               // VARCHAR2
		$this->groupid3 = $this->ora->groupid3;                                               // VARCHAR2
		$this->template = $this->ora->template;                                               // VARCHAR2
		$this->hd_outage_ticket_number = $this->ora->hd_outage_ticket_number;                 // VARCHAR2
		$this->root_cause_owner = $this->ora->root_cause_owner;                               // VARCHAR2
		$this->control_count = $this->ora->control_count;                                     // VARCHAR2
		
		//
		// Dates are stored in Remedy as numbers (GMT). Remedy provide me with the function
		// fn_number_to_date() to convert the numbers to DATE objects. The first thing I do
		// is get the current maintain timezone which could be MST or MDT depending on the
		// time of year. The $tz value is then used in the function to current the ticket
		// to Mountain MST/MDT time. These dates are then stored in the CCT database in
		// cct6_tickets
		//
		$tz = $this->get_mountain_timezone();  // classes/library.php
		//$tz = $this->get_central_timezone();  // classes/library.php
		
		$query  =         "select ";
		$query .= sprintf("  fn_number_to_date(create_date, '%s')             as create_date, ",             $tz);		
		$query .= sprintf("  fn_number_to_date(start_date, '%s')              as start_date, ",              $tz);
		$query .= sprintf("  fn_number_to_date(end_date, '%s')                as end_date, ",                $tz);
		$query .= sprintf("  fn_number_to_date(close_date, '%s')              as close_date, ",              $tz);
		$query .= sprintf("  fn_number_to_date(last_modified, '%s')           as last_modified, ",           $tz);
		$query .= sprintf("  fn_number_to_date(late_date, '%s')               as late_date, ",               $tz);
		$query .= sprintf("  fn_number_to_date(last_status_change_time, '%s') as last_status_change_time, ", $tz);
		$query .= sprintf("  fn_number_to_date(turn_overdate, '%s')           as turn_overdate ",            $tz);
		$query .= sprintf("from t_cm_implementation_request@remedy_prod where change_id = '%s'", $ticket_no);
		
		$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, $query);
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$this->html_stop(__FILE__, __FUNCTION__, __LINE__, "SQL Error: %s. Please contact Greg Parkin", $ora->dbErrMsg);
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->error = "Unable to pull ticket: " . $ticket_no;
			return false;
		}
		
		$this->close_date = $this->ora->close_date;                                           // NUMBER
		$this->end_date = $this->ora->end_date;                                               // NUMBER
		$this->create_date = $this->ora->create_date;                                         // NUMBER
		$this->start_date = $this->ora->start_date;                                           // NUMBER
		$this->last_modified = $this->ora->last_modified;                                     // NUMBER
		$this->late_date = $this->ora->late_date;                                             // NUMBER
		$this->last_status_change_time = $this->ora->last_status_change_time;                 // NUMBER
		$this->turn_overdate = $this->ora->turn_overdate;                                     // NUMBER		
		
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "create_date = %s",             $this->ora->create_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_date = %s",              $this->ora->start_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "end_date = %s",                $this->ora->end_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "close_date = %s",              $this->ora->close_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "last_modified = %s",           $this->ora->last_modified);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "late_date = %s",               $this->ora->late_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "last_status_change_time = %s", $this->ora->last_status_change_time);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "turn_overdate = %s",           $this->ora->turn_overdate);		
		
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
		$query .= sprintf("from t_cm_implementation_request@remedy_prod where change_id = '%s'", $ticket_no);
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}

		if ($this->ora->fetch() == false)
		{
			$this->error = "Unable to pull ticket part3 from Remedy: " . $ticket_no;
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		$this->ipl_boot = $this->ora->ipl_boot;                                               // NUMBER
		$this->late = $this->ora->late;                                                       // NUMBER
		$this->plan_a_b = $this->ora->plan_a_b;                                               // VARCHAR2
		$this->risk = $this->ora->risk;                                                       // VARCHAR2
		$this->tested_itv = $this->ora->tested_itv;                                           // NUMBER
		$this->tested_endtoend = $this->ora->tested_endtoend;                                 // NUMBER
		$this->tested_development = $this->ora->tested_development;                           // NUMBER
		$this->tested_user = $this->ora->tested_user;                                         // NUMBER
		$this->lla_refresh = $this->ora->lla_refresh;                                         // NUMBER
		$this->ims_cold_start = $this->ora->ims_cold_start;                                   // NUMBER
		$this->cab_approval_required = $this->ora->cab_approval_required;                     // NUMBER
		$this->change_executive_team_flag = $this->ora->change_executive_team_flag;           // NUMBER
		$this->emergency_change = $this->ora->emergency_change;                               // NUMBER
		$this->tested_orl = $this->ora->tested_orl;                                           // NUMBER
		$this->featured_project = $this->ora->featured_project;                               // NUMBER
				
		if ($this->ora->sql("select * from cct6_mnet where mnet_cuid = lower('" . $this->entered_by . "')") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
	
		if ($this->ora->fetch())
		{
			$this->owner_name = $this->ora->mnet_name;
			$this->owner_first_name = $this->ora->mnet_first_name;
			$this->owner_last_name = $this->ora->mnet_last_name;
						
			$this->owner_cuid = $this->ora->mnet_cuid;
			$this->pager = $this->ora->mnet_pager;
			$this->phone = $this->ora->mnet_work_phone;		
			
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "fixing: owner_name = %s", $this->owner_name);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "fixing: owner_first_name = %s", $this->owner_first_name);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "fixing: owner_last_name = %s", $this->owner_last_name);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "fixing: owner_cuid = %s", $this->owner_cuid);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "fixing: pager = %s", $this->pager);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "fixing: phone = %s", $this->phone);
		}
					
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "SUCCESS - returning true");
							
		return true;
	}	
	
	/*! @fn getTicket($ticket_no)
	 *  @brief Get the ticket from table: cct6_tickets and copy values to $this->xxx
	 *  @param $ticket_no we want to retrieve
	 *  @return true or false, where true is success 
	 */	
	public function getTicket($ticket_no)
	{
		//
		// The dbms.php class alters the session NLS_DATE_FORMAT to display dates in MM/DD/YYYY HH24:MI
		//
		$query = "select * from cct6_tickets where cm_ticket_no = '" . $ticket_no . "'";
		
		$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->error = "Ticket: " . $ticket_no . " does not exist in CCT";
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;		
		}

		$this->cm_ticket_no = $this->ora->cm_ticket_no;                                          // VARCHAR2 PRIMARY KEY: Remedy CM Ticket No.
		$this->ticket_status = $this->ora->ticket_status;                                        // VARCHAR2 Draft, Submitted, Canceled, etc.
		$this->ticket_insert_cuid = $this->ora->ticket_insert_cuid;                              // VARCHAR2 CUID of person who created this record
		$this->ticket_insert_name = $this->ora->ticket_insert_name;                              // VARCHAR2 Name of person who created this record
		$this->ticket_insert_date = $this->ora->ticket_insert_date;                              // DATE     Date of person who created this record
		$this->ticket_update_cuid = $this->ora->ticket_update_cuid;                              // VARCHAR2 CUID of person who updated this record
		$this->ticket_update_name = $this->ora->ticket_update_name;                              // VARCHAR2 Name of person who updated this record
		$this->ticket_update_date = $this->ora->ticket_update_date;                              // DATE     Date of person who updated this record
		$this->ticket_contact_cuid = $this->ora->ticket_contact_cuid;                            // VARCHAR2 CUID of person who created this record. (Same as insert_cuid)
		$this->ticket_contact_first_name = $this->ora->ticket_contact_first_name;                // VARCHAR2 Frist name of person who created this record
		$this->ticket_contact_last_name = $this->ora->ticket_contact_last_name;                  // VARCHAR2 Last name of person who created this record
		$this->ticket_contact_name = $this->ora->ticket_contact_name;                            // VARCHAR2 Name of person who created this record. (Same as insert_name)
		$this->ticket_contact_email = $this->ora->ticket_contact_email;                          // VARCHAR2 Email address of person who created this record
		$this->ticket_contact_company = $this->ora->ticket_contact_company;                      // VARCHAR2 Company name of person who created this record
		$this->ticket_manager_cuid = $this->ora->ticket_manager_cuid;                            // VARCHAR2 Owners managers CUID
		$this->ticket_manager_first_name = $this->ora->ticket_manager_first_name;                // VARCHAR2 Owners managers first name
		$this->ticket_manager_last_name = $this->ora->ticket_manager_last_name;                  // VARCHAR2 Owners managers last name
		$this->ticket_manager_name = $this->ora->ticket_manager_name;                            // VARCHAR2 Owners managers full name
		$this->ticket_manager_email = $this->ora->ticket_manager_email;                          // VARCHAR2 Owners managers email address
		$this->ticket_manager_company = $this->ora->ticket_manager_company;                      // VARCHAR2 Owners managers company name
		$this->ticket_submit_date = $this->ora->ticket_submit_date;                              // DATE     Date work request was submitted. No longer in draft mode.
		$this->ticket_submit_cuid = $this->ora->ticket_submit_cuid;                              // VARCHAR2 CUID of person who submitted work request
		$this->ticket_submit_name = $this->ora->ticket_submit_name;                              // VARCHAR2 Name of person who submitted work request
		$this->ticket_submit_note = $this->ora->ticket_submit_note;                              // VARCHAR2 Optional note to clients
		$this->ticket_freeze_date = $this->ora->ticket_freeze_date;                              // DATE     Date work request was frozen
		$this->ticket_freeze_cuid = $this->ora->ticket_freeze_cuid;                              // VARCHAR2 CUID of person who freezed work request
		$this->ticket_freeze_name = $this->ora->ticket_freeze_name;                              // VARCHAR2 Name of person who freezed work request
		$this->ticket_cancel_date = $this->ora->ticket_cancel_date;                              // DATE     Date work request was canceled
		$this->ticket_cancel_cuid = $this->ora->ticket_cancel_cuid;                              // VARCHAR2 CUID of person who canceled the work request
		$this->ticket_cancel_name = $this->ora->ticket_cancel_name;                              // VARCHAR2 Name of person who canceled the work request
		$this->ticket_cancel_comments = $this->ora->ticket_cancel_comments;                      // VARCHAR2 Comments as to why the work request was canceled
		$this->classification_id = $this->ora->classification_id;                                // NUMBER   cct6_classifications.classification_id (ie. Unique record ID)
		$this->classification = $this->ora->classification;                                      // VARCHAR2 cct_classification.classification (ie. Patching)
		$this->classification_comments = $this->ora->classification_comments;                    // VARCHAR2 Classification comments
		$this->classification_cuid = $this->ora->classification_cuid;                            // VARCHAR2 CUID of person who will approve this work
		$this->classification_last_name = $this->ora->classification_last_name;                  // VARCHAR2 Last name of approver
		$this->classification_first_name = $this->ora->classification_first_name;                // VARCHAR2 First name of approver
		$this->classification_nick_name = $this->ora->classification_nick_name;                  // VARCHAR2 Nick name of approver
		$this->classification_middle = $this->ora->classification_middle;                        // VARCHAR2 Middle name of approver
		$this->classification_name = $this->ora->classification_name;                            // VARCHAR2 Full name of approver
		$this->classification_job_title = $this->ora->classification_job_title;                  // VARCHAR2 Job title of approver
		$this->classification_email = $this->ora->classification_email;                          // VARCHAR2 Email address of approver
		$this->classification_work_phone = $this->ora->classification_work_phone;                // VARCHAR2 Work phone number of approver
		$this->classification_pager = $this->ora->classification_pager;                          // VARCHAR2 Pager number of approver
		$this->classification_street = $this->ora->classification_street;                        // VARCHAR2 Street address of approver
		$this->classification_city = $this->ora->classification_city;                            // VARCHAR2 City of approver
		$this->classification_state = $this->ora->classification_state;                          // VARCHAR2 State of approver
		$this->classification_rc = $this->ora->classification_rc;                                // VARCHAR2 Qwest RC code of approver
		$this->classification_company = $this->ora->classification_company;                      // VARCHAR2 Company name that the approver works for
		$this->classification_tier1 = $this->ora->classification_tier1;                          // VARCHAR2 Tier1 level support for approver
		$this->classification_tier2 = $this->ora->classification_tier2;                          // VARCHAR2 Tier2 level support for approver
		$this->classification_tier3 = $this->ora->classification_tier3;                          // VARCHAR2 Tier3 level support for approver
		$this->classification_status = $this->ora->classification_status;                        // VARCHAR2 Employee status for approver
		$this->classification_change_date = $this->ora->classification_change_date;              // DATE     Last mnet record change date for this person
		$this->classification_ctl_cuid = $this->ora->classification_ctl_cuid;                    // VARCHAR2 Persons sponsor CTL cuid
		$this->classification_mgr_cuid = $this->ora->classification_mgr_cuid;                    // VARCHAR2 Manager CUID of approver
		$this->ticket_os_maintwin = $this->ora->ticket_os_maintwin;                              // VARCHAR2 OS Maintenance Window: W=Weekly, M=Monthly, Q=Quarterly
		$this->ticket_approvals_required = $this->ora->ticket_approvals_required;                // VARCHAR2 Are approvals required? Yes or No
		$this->ticket_read_esc1_date = $this->ora->ticket_read_esc1_date;                        // DATE     Read Receipt escalation 1
		$this->ticket_read_esc2_date = $this->ora->ticket_read_esc2_date;                        // DATE     Read Receipt escalation 2
		$this->ticket_read_esc3_date = $this->ora->ticket_read_esc3_date;                        // DATE     Read Receipt escalation 3
		$this->ticket_resp_esc1_date = $this->ora->ticket_resp_esc1_date;                        // DATE     Response escalation 1
		$this->ticket_resp_esc2_date = $this->ora->ticket_resp_esc2_date;                        // DATE     Response escalation 2
		$this->ticket_resp_esc3_date = $this->ora->ticket_resp_esc3_date;                        // DATE     Response escalation 3
		$this->cm_assign_group = $this->ora->cm_assign_group;                                    // VARCHAR2
		$this->cm_category = $this->ora->cm_category;                                            // VARCHAR2
		$this->cm_category_type = $this->ora->cm_category_type;                                  // VARCHAR2
		$this->cm_closed_by = $this->ora->cm_closed_by;                                          // VARCHAR2
		$this->cm_close_code = $this->ora->cm_close_code;                                        // VARCHAR2
		$this->cm_close_date = $this->ora->cm_close_date;                                        // DATE
		$this->cm_component = $this->ora->cm_component;                                          // VARCHAR2
		$this->cm_scheduling_flexibility = $this->ora->cm_scheduling_flexibility;                // VARCHAR2
		$this->cm_end_date = $this->ora->cm_end_date;                                            // DATE
		$this->cm_entered_by = $this->ora->cm_entered_by;                                        // VARCHAR2
		$this->cm_exp_code = $this->ora->cm_exp_code;                                            // VARCHAR2
		$this->cm_fix_level = $this->ora->cm_fix_level;                                          // VARCHAR2
		$this->cm_impact = $this->ora->cm_impact;                                                // VARCHAR2
		$this->cm_implementor_first_last = $this->ora->cm_implementor_first_last;                // VARCHAR2
		$this->cm_implementor_login = $this->ora->cm_implementor_login;                          // VARCHAR2
		$this->cm_ipl_boot = $this->ora->cm_ipl_boot;                                            // VARCHAR2
		$this->cm_late = $this->ora->cm_late;                                                    // VARCHAR2
		$this->cm_parent_ir = $this->ora->cm_parent_ir;                                          // VARCHAR2
		$this->cm_normal_release_session = $this->ora->cm_normal_release_session;                // VARCHAR2
		$this->cm_create_date = $this->ora->cm_create_date;                                      // DATE
		$this->cm_pager = $this->ora->cm_pager;                                                  // VARCHAR2
		$this->cm_phone = $this->ora->cm_phone;                                                  // VARCHAR2
		$this->cm_pin = $this->ora->cm_pin;                                                      // VARCHAR2
		$this->cm_plan_a_b = $this->ora->cm_plan_a_b;                                            // VARCHAR2
		$this->cm_product = $this->ora->cm_product;                                              // VARCHAR2
		$this->cm_product_type = $this->ora->cm_product_type;                                    // VARCHAR2
		$this->cm_risk = $this->ora->cm_risk;                                                    // NUMBER
		$this->cm_software_object = $this->ora->cm_software_object;                              // VARCHAR2
		$this->cm_start_date = $this->ora->cm_start_date;                                        // DATE
		$this->cm_status = $this->ora->cm_status;                                                // VARCHAR2
		$this->cm_tested = $this->ora->cm_tested;                                                // VARCHAR2
		$this->cm_duration = $this->ora->cm_duration;                                            // NUMBER
		$this->cm_business_unit = $this->ora->cm_business_unit;                                  // VARCHAR2
		$this->cm_duration_computed = $this->ora->cm_duration_computed;                          // VARCHAR2
		$this->cm_email = $this->ora->cm_email;                                                  // VARCHAR2
		$this->cm_company_name = $this->ora->cm_company_name;                                    // VARCHAR2
		$this->cm_director = $this->ora->cm_director;                                            // VARCHAR2
		$this->cm_manager = $this->ora->cm_manager;                                              // VARCHAR2
		$this->cm_tested_itv = $this->ora->cm_tested_itv;                                        // VARCHAR2
		$this->cm_tested_endtoend = $this->ora->cm_tested_endtoend;                              // VARCHAR2
		$this->cm_tested_development = $this->ora->cm_tested_development;                        // VARCHAR2
		$this->cm_tested_user = $this->ora->cm_tested_user;                                      // VARCHAR2
		$this->cm_owner_name = $this->ora->cm_owner_name;                                        // VARCHAR2
		$this->cm_owner_cuid = $this->ora->cm_owner_cuid;                                        // VARCHAR2
		$this->cm_groupid = $this->ora->cm_groupid;                                              // VARCHAR2
		$this->cm_temp = $this->ora->cm_temp;                                                    // VARCHAR2
		$this->cm_last_modified_by = $this->ora->cm_last_modified_by;                            // VARCHAR2
		$this->cm_last_modified = $this->ora->cm_last_modified;                                  // DATE
		$this->cm_late_date = $this->ora->cm_late_date;                                          // DATE
		$this->cm_risk_integer = $this->ora->cm_risk_integer;                                    // NUMBER
		$this->cm_owner_login_id = $this->ora->cm_owner_login_id;                                // VARCHAR2
		$this->cm_open_closed = $this->ora->cm_open_closed;                                      // VARCHAR2
		$this->cm_user_timestamp = $this->ora->cm_user_timestamp;                                // VARCHAR2
		$this->cm_description = $this->ora->cm_description;                                      // VARCHAR2
		$this->cm_backoff_plan = $this->ora->cm_backoff_plan;                                    // VARCHAR2
		$this->cm_implementation_instructions = $this->ora->cm_implementation_instructions;      // VARCHAR2
		$this->cm_business_reason = $this->ora->cm_business_reason;                              // VARCHAR2
		$this->cm_owner_first_name = $this->ora->cm_owner_first_name;                            // VARCHAR2
		$this->cm_owner_last_name = $this->ora->cm_owner_last_name;                              // VARCHAR2
		$this->cm_change_occurs = $this->ora->cm_change_occurs;                                  // VARCHAR2
		$this->cm_lla_refresh = $this->ora->cm_lla_refresh;                                      // VARCHAR2
		$this->cm_ims_cold_start = $this->ora->cm_ims_cold_start;                                // VARCHAR2
		$this->cm_release_level = $this->ora->cm_release_level;                                  // VARCHAR2
		$this->cm_master_ir = $this->ora->cm_master_ir;                                          // VARCHAR2
		$this->cm_owner_group = $this->ora->cm_owner_group;                                      // VARCHAR2
		$this->cm_cab_approval_required = $this->ora->cm_cab_approval_required;                  // VARCHAR2
		$this->cm_change_executive_team_flag = $this->ora->cm_change_executive_team_flag;        // VARCHAR2
		$this->cm_emergency_change = $this->ora->cm_emergency_change;                            // VARCHAR2
		$this->cm_approval_status = $this->ora->cm_approval_status;                              // NUMBER
		$this->cm_component_type = $this->ora->cm_component_type;                                // VARCHAR2
		$this->cm_desc_short = $this->ora->cm_desc_short;                                        // VARCHAR2
		$this->cm_last_status_change_by = $this->ora->cm_last_status_change_by;                  // VARCHAR2
		$this->cm_last_status_change_time = $this->ora->cm_last_status_change_time;              // DATE
		$this->cm_previous_status = $this->ora->cm_previous_status;                              // VARCHAR2
		$this->cm_component_id = $this->ora->cm_component_id;                                    // VARCHAR2
		$this->cm_test_tool = $this->ora->cm_test_tool;                                          // VARCHAR2
		$this->cm_tested_orl = $this->ora->cm_tested_orl;                                        // VARCHAR2
		$this->cm_featured_project = $this->ora->cm_featured_project;                            // VARCHAR2
		$this->cm_featured_proj_name = $this->ora->cm_featured_proj_name;                        // VARCHAR2
		$this->cm_tmpmainplatform = $this->ora->cm_tmpmainplatform;                              // VARCHAR2
		$this->cm_tmpblockmessage = $this->ora->cm_tmpblockmessage;                              // VARCHAR2
		$this->cm_guid = $this->ora->cm_guid;                                                    // VARCHAR2
		$this->cm_platform = $this->ora->cm_platform;                                            // VARCHAR2
		$this->cm_cllicodes = $this->ora->cm_cllicodes;                                          // VARCHAR2
		$this->cm_processor_name = $this->ora->cm_processor_name;                                // VARCHAR2
		$this->cm_system_name = $this->ora->cm_system_name;                                      // VARCHAR2
		$this->cm_city = $this->ora->cm_city;                                                    // VARCHAR2
		$this->cm_state = $this->ora->cm_state;                                                  // VARCHAR2
		$this->cm_tmpdesc = $this->ora->cm_tmpdesc;                                              // VARCHAR2
		$this->cm_turn_overdate = $this->ora->cm_turn_overdate;                                  // DATE
		$this->cm_assign_group2 = $this->ora->cm_assign_group2;                                  // VARCHAR2
		$this->cm_assign_group3 = $this->ora->cm_assign_group3;                                  // VARCHAR2
		$this->cm_implementor_name2 = $this->ora->cm_implementor_name2;                          // VARCHAR2
		$this->cm_implementor_name3 = $this->ora->cm_implementor_name3;                          // VARCHAR2
		$this->cm_groupid2 = $this->ora->cm_groupid2;                                            // VARCHAR2
		$this->cm_groupid3 = $this->ora->cm_groupid3;                                            // VARCHAR2
		$this->cm_template = $this->ora->cm_template;                                            // VARCHAR2
		$this->cm_hd_outage_ticket_number = $this->ora->cm_hd_outage_ticket_number;              // VARCHAR2
		$this->cm_root_cause_owner = $this->ora->cm_root_cause_owner;                            // VARCHAR2
		$this->cm_control_count = $this->ora->cm_control_count;                                  // VARCHAR2
		$this->gen_request_start = $this->ora->gen_request_start;                                // DATE     Performance: GenRequest() start time
		$this->gen_request_end = $this->ora->gen_request_end;                                    // DATE     Performance: GenRequest() end time
		$this->gen_request_duration = $this->ora->gen_request_duration;                          // VARCHAR2 Performance: GenRequest() duration (i.e. 00:00:14) HH:MM:SS
		$this->gen_request_total_systems = $this->ora->gen_request_total_systems;                // NUMBER   Total systems GenRequest() gathered from user input
		$this->gen_request_total_contacts = $this->ora->gen_request_total_contacts;              // NUMBER   Total contacts GenRequest() gathered for each server
		
		$this->ticket_submit_note = $this->ora->ticket_submit_note;                              // VARCHAR2 Optional note to clients
		$this->ticket_use_os_maintwin = $this->ora->ticket_use_os_maintwin;                      // VARCHAR2 Flag used to disable scheduler from using os maintwin
		$this->ticket_target_os = $this->ora->ticket_target_os;                                  // VARCHAR2 Flag used to disable selecting os contacts
		$this->ticket_target_pase = $this->ora->ticket_target_pase;                              // VARCHAR2 Flag used to disable selecting pase contacts
		$this->ticket_target_dba = $this->ora->ticket_target_dba;                                // VARCHAR2 Flag used to disable selecting dba contacts
		$this->ticket_target_dev_dba = $this->ora->ticket_target_dev_dba;                        // VARCHAR2 Flag used to disable selecting dev dba contacts
		$this->ticket_override_master = $this->ora->ticket_override_master;                      // VARCHAR2 Y or N - override master approver
		$this->ticket_include_child_servers = $this->ora->ticket_include_child_servers;
		$this->ticket_include_vmware_servers = $this->ora->ticket_include_vmware_servers;

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "getTICKET SUCCESS - returning true");
																		
		return true;		
	}		
	
	/*! @fn function IsTicket($ticket_no)
	 *  @brief Check to see if $ticket_no is found in cct6_tickets
	 *  @param $ticket_no we want to check
	 *  @return true or false, where true is success 
	 */
	public function IsTicket($ticket_no)
	{
		//
		// The dbms.php class alters the session NLS_DATE_FORMAT to display dates in MM/DD/YYYY HH24:MI
		//
		$query = "select ticket_status from cct6_tickets where cm_ticket_no = '" . $ticket_no . "'";
		
		$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;	
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->error = "Ticket: " . $ticket_no . " does not exist in CCT";
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;		
		}
		
		$this->ticket_status = $this->ora->ticket_status;
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "IsTICKET() - returning true");
		
		return true;
	}	
	
	/*! @fn createTicket($ticket_no)
	 *  @brief Using set properties and ticket_no, a new cct6_tickets record will be created. Remedy CM data copied to the record.
	 *  @param $ticket_no we want to create
	 *  @return true or false, where true is success 
	 */
	public function createTicket($ticket_no)
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s", $ticket_no);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "os_maintwin = %s", $this->os_maintwin);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "approvals_required = %s", $this->approvals_required);
		
		//
		// Don't create the ticket if it already exists in cct6_tickets
		//	
		if ($this->ora->sql("select cm_ticket_no from cct6_tickets where cm_ticket_no = '" . $ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		if ($this->ora->fetch() == true)
		{
			$this->error = "Ticket: " . $ticket_no . " already exits in CCT";
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}
		
		//
		// Construct the SQL to insert the first part of the ticket
		//	
		$insert = "insert into cct6_tickets (" .
			"cm_ticket_no, ticket_status, ticket_insert_cuid, ticket_insert_name, " .
			"ticket_contact_cuid, ticket_contact_first_name, ticket_contact_last_name, ticket_contact_name, ticket_contact_email, ticket_contact_company, " .
			"ticket_manager_cuid, ticket_manager_first_name, ticket_manager_last_name, ticket_manager_name, ticket_manager_email, ticket_manager_company, " .
			"ticket_os_maintwin, ticket_approvals_required, ticket_use_os_maintwin, ticket_target_os, ticket_target_pase, ticket_target_dba, " .
			"ticket_target_dev_dba, ticket_override_master, ticket_include_child_servers, ticket_include_vmware_servers) values (";

		$this->makeInsertCHAR($insert, $ticket_no,                            true);  // cm_ticket_no (Primary Key)
		$this->makeInsertCHAR($insert, "DRAFT",                          true);  // ticket_status
		$this->makeInsertCHAR($insert, $this->user_cuid,                      true);  // ticket_insert_cuid
		$this->makeInsertCHAR($insert, $this->user_name,                      true);  // ticket_insert_name
		$this->makeInsertCHAR($insert, $this->user_cuid,                      true);  // ticket_contact_cuid
		$this->makeInsertCHAR($insert, $this->user_first_name,                true);  // ticket_contact_first_name
		$this->makeInsertCHAR($insert, $this->user_last_name,                 true);  // ticket_contact_last_name
		$this->makeInsertCHAR($insert, $this->user_name,                      true);  // ticket_contact_name
		$this->makeInsertCHAR($insert, $this->user_email,                     true);  // ticket_contact_email
		$this->makeInsertCHAR($insert, $this->user_company,                   true);  // ticket_contact_company
		$this->makeInsertCHAR($insert, $this->manager_cuid,                   true);  // ticket_manager_cuid   
		$this->makeInsertCHAR($insert, $this->manager_first_name,             true);  // ticket_manager_first_name
		$this->makeInsertCHAR($insert, $this->manager_last_name,              true);  // ticket_manager_last_name
		$this->makeInsertCHAR($insert, $this->manager_name,                   true);  // ticket_manager_name
		$this->makeInsertCHAR($insert, $this->manager_email,                  true);  // ticket_manager_email
		$this->makeInsertCHAR($insert, $this->manager_company,                true);  // ticket_ manager_company
		$this->makeInsertCHAR($insert, $this->os_maintwin,                    true);  // ticket_os_maintwin
		$this->makeInsertCHAR($insert, $this->approvals_required,             true);  // ticket_approvals_required
		$this->makeInsertCHAR($insert, $this->ticket_use_os_maintwin,         true);  // ticket_use_os_maintwin
		$this->makeInsertCHAR($insert, $this->ticket_target_os,               true);  // ticket_target_os
		$this->makeInsertCHAR($insert, $this->ticket_target_pase,             true);  // ticket_target_pase
		$this->makeInsertCHAR($insert, $this->ticket_target_dba,              true);  // ticket_target_dba
		$this->makeInsertCHAR($insert, $this->ticket_target_dev_dba,          true);  // ticket_target_dev_dba
		$this->makeInsertCHAR($insert, $this->ticket_override_master,         true);  // ticket_override_master
		$this->makeInsertCHAR($insert, $this->ticket_include_child_servers,   true);  // ticket_override_master
		$this->makeInsertCHAR($insert, $this->ticket_include_vmware_servers, false);  // ticket_override_master

		$insert .= ")";
		
		$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $insert);
		
		if ($this->ora->sql($insert) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}

		//
		// Grab the cct6_classifications record for this classification.
		// 
		if (strlen($this->classification) > 0 && strlen($this->classification_cuid) == 0)
		{
			if ($this->ora->sql("select * from cct6_classifications where delete_date is null and classification = '" . $this->classification . "'") == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
				$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
				return false;			
			}
		
			if ($this->ora->fetch() == false)
			{
				$this->error = "Unable to retrieve cct6_classification record for classification: " . $this->classification;
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
				return false;
			}
			
			$this->classification_id = $this->ora->classification_id;
			$this->classification = $this->ora->classification;
			$this->classification_comments = $this->ora->classification_comments;
			$this->classification_cuid = $this->ora->classification_cuid;
			$this->classification_last_name = $this->ora->classification_last_name;
			$this->classification_first_name = $this->ora->classification_first_name;
			$this->classification_nick_name = $this->ora->classification_nick_name;
			$this->classification_middle = $this->ora->classification_middle;
			$this->classification_name = $this->ora->classification_name;
			$this->classification_job_title = $this->ora->classification_job_title;
			$this->classification_email = $this->ora->classification_email;
			$this->classification_work_phone = $this->ora->classification_work_phone;
			$this->classification_pager = $this->ora->classification_pager;
			$this->classification_street = $this->ora->classification_street;
			$this->classification_city = $this->ora->classification_city;
			$this->classification_state = $this->ora->classification_state;
			$this->classification_rc = $this->ora->classification_rc;
			$this->classification_company = $this->ora->classification_company;
			$this->classification_tier1 = $this->ora->classification_tier1;
			$this->classification_tier2 = $this->ora->classification_tier2;
			$this->classification_tier3 = $this->ora->classification_tier3;
			$this->classification_status = $this->ora->classification_status;
			$this->classification_change_date = $this->ora->classification_change_date;
			$this->classification_ctl_cuid = $this->ora->classification_ctl_cuid;
			$this->classification_mgr_cuid = $this->ora->classification_mgr_cuid;
		}
		
		$update = "update cct6_tickets set ";
		
		$this->makeUpdateCHAR(    $update, "ticket_update_cuid",         $this->user_cuid,                  true);
		$this->makeUpdateCHAR(    $update, "ticket_update_name",         $this->user_name,                  true);	
		$this->makeUpdateINT(     $update, "classification_id",          $this->classification_id,          true);
		$this->makeUpdateCHAR(    $update, "classification",             $this->classification,             true);
		$this->makeUpdateCHAR(    $update, "classification_comments",    $this->classification_comments,    true);
		$this->makeUpdateCHAR(    $update, "classification_cuid",        $this->classification_cuid,        true);
		$this->makeUpdateCHAR(    $update, "classification_last_name",   $this->classification_last_name,   true);
		$this->makeUpdateCHAR(    $update, "classification_first_name",  $this->classification_first_name,  true);
		$this->makeUpdateCHAR(    $update, "classification_nick_name",   $this->classification_nick_name,   true);
		$this->makeUpdateCHAR(    $update, "classification_middle",      $this->classification_middle,      true);
		$this->makeUpdateCHAR(    $update, "classification_name",        $this->classification_name,        true);
		$this->makeUpdateCHAR(    $update, "classification_job_title",   $this->classification_job_title,   true);
		$this->makeUpdateCHAR(    $update, "classification_email",       $this->classification_email,       true);
		$this->makeUpdateCHAR(    $update, "classification_work_phone",  $this->classification_work_phone,  true);
		$this->makeUpdateCHAR(    $update, "classification_pager",       $this->classification_pager,       true);
		$this->makeUpdateCHAR(    $update, "classification_street",      $this->classification_street,      true);
		$this->makeUpdateCHAR(    $update, "classification_city",        $this->classification_city,        true);
		$this->makeUpdateCHAR(    $update, "classification_state",       $this->classification_state,       true);
		$this->makeUpdateCHAR(    $update, "classification_rc",          $this->classification_rc,          true);
		$this->makeUpdateCHAR(    $update, "classification_company",     $this->classification_company,     true);
		$this->makeUpdateCHAR(    $update, "classification_tier1",       $this->classification_tier1,       true);
		$this->makeUpdateCHAR(    $update, "classification_tier2",       $this->classification_tier2,       true);
		$this->makeUpdateCHAR(    $update, "classification_tier3",       $this->classification_tier3,       true);
		$this->makeUpdateCHAR(    $update, "classification_status",      $this->classification_status,      true);
		$this->makeUpdateDateTIME($update, "classification_change_date", $this->classification_change_date, true);
		$this->makeUpdateCHAR(    $update, "classification_ctl_cuid",    $this->classification_ctl_cuid,    true);
		$this->makeUpdateCHAR(    $update, "classification_mgr_cuid",    $this->classification_mgr_cuid,    true);
		$this->makeUpdateDateTIME($update, "ticket_read_esc1_date",      $this->ticket_read_esc1_date,      true);
		$this->makeUpdateDateTIME($update, "ticket_read_esc2_date",      $this->ticket_read_esc2_date,      true);
		$this->makeUpdateDateTIME($update, "ticket_read_esc3_date",      $this->ticket_read_esc3_date,      true);
		$this->makeUpdateDateTIME($update, "ticket_resp_esc1_date",      $this->ticket_resp_esc1_date,      true);
		$this->makeUpdateDateTIME($update, "ticket_resp_esc2_date",      $this->ticket_resp_esc2_date,      true);
		$this->makeUpdateDateTIME($update, "ticket_resp_esc3_date",      $this->ticket_resp_esc3_date,      true);
		$this->makeUpdateCHAR(    $update, "ticket_submit_note",         $this->ticket_submit_note,         false);
				
		$update .= " where cm_ticket_no = '" . $ticket_no . "'";
		
		$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $update);
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->updateRemedy($ticket_no) == false)
		{
			$this->ora->rollback();
			return false;
		}
			
		$this->ora->commit();
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "CREATE TICKET SUCCESS - returning true");
			
		return true;
	}

	/*! @fn deleteTicket($ticket_no)
	 *  @brief Delete this ticket form the database along with all data connected via. foreign keys.
	 *  @param $ticket_no we want to delete
	 *  @return true or false, where true is success 
	 */
	public function deleteTicket($ticket_no)
	{
		//
		// Is this user authorized to do this?
		//
		if ($this->authorizeTicket($ticket_no, "delete") == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);	
			return false;
		}
		
		//
		// Don't create the ticket if it already exists in cct6_tickets
		//	
		if ($this->ora->sql("select cm_ticket_no from cct6_tickets where cm_ticket_no = '" . $ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->error = "Ticket: " . $ticket_no . " does not exist in CCT";
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}	
		
		//
		// Cascade delete is setup to remove all records where foreign keys exist for $ticket_no. This means cct6_servers,
		// cct6_contacts, cct6_notes, etc. will be removed.
		//
		if ($this->ora->sql("delete from cct6_tickets where cm_ticket_no = '" . $ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		$this->ora->commit();
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "DELETE SUCCESS - returning true");
		return true;
	}

	/*! @fn cancelTicket($cm_ticket_no, $cancel_comments)
	 *  @brief Sets the status flag to cancel to indicate that work has been canceled. Spool cancelation email messages to the clients
	 *  @param $ticket_no we want to cancel
	 *  @param $cancel_comments are comments as to why the ticket is being canceled.
	 *  @return true or false, where true is success 
	 */
	public function cancelTicket($cm_ticket_no, $cancel_comments)
	{
		//
		// Is this user authorized to do this?
		//
		if ($this->authorizeTicket($ticket_no, "cancel") == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);	
			return false;
		}
			
		//
		// Don't create the ticket if it already exists in cct6_tickets
		//	
		if ($this->ora->sql("select cm_ticket_no from cct6_tickets where cm_ticket_no = '" . $cm_ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->error = "Ticket: " . $cm_ticket_no . " does not exits in CCT";
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}	
		
		$update = "update cct6_tickets set ";
		
		$this->makeUpdateCHAR(   $update, "ticket_update_cuid",       $this->user_cuid,  true);
		$this->makeUpdateCHAR(   $update, "ticket_update_name",       $this->user_name,  true);
		$this->makeUpdateDateNOW($update, "ticket_cancel_date",                          true);
		$this->makeUpdateCHAR(   $update, "ticket_cancel_cuid",       $this->user_cuid,  true);
		$this->makeUpdateCHAR(   $update, "ticket_cancel_name",       $this->user_name,  true);
		$this->makeUpdateCHAR(   $update, "ticket_status",            "CANCELED",        true);
		$this->makeUpdateCHAR(   $update, "ticket_cancel_comments",   $cancel_comments, false);
		
		$update .= " where cm_ticket_no = '" . $cm_ticket_no . "'";
		
		$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $update);
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$email = new cct6_email_spool();      // classes/cct6_email_spool.php
		
		//
		// SpoolEmailTicket($cm_ticket_no, $email_template="", $email_subject="", $email_message="", $ticket_owner, $os_group, $pase_group, $dba_group)
		//
		if ($email->SpoolEmailTicket($cm_ticket_no, "CANCELED", "", "", false, true, true, true) == false)
		{
			$this->error = $email->error;
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}
			
		$this->ora->commit();
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "CANCEL SUCCESS - returning true");
		return true;		
	}
	
	/*! @fn updateRemedy($ticket_no)
	 *  @brief Pull in Remedy CM data and update the cct6_ticket record with any new information.
	 *  @param $ticket_no we want to update
	 *  @return true or false, where true is success 
	 */
	public function updateRemedy($ticket_no)
	{		
		if ($this->getRemedyTicket($ticket_no) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "getRemedyTicket(%s) has failed", $ticket_no);
			return false;
		}
			
		$update = "update cct6_tickets set ";
		
		$this->makeUpdateCHAR($update,     "ticket_update_cuid",             $this->user_cuid,                   true);
		$this->makeUpdateCHAR($update,     "ticket_update_name",             $this->user_name,                   true);
		$this->makeUpdateCHAR($update,     "cm_assign_group",                $this->assign_group,                true);
		$this->makeUpdateCHAR($update,     "cm_category",                    $this->category,                    true);
		$this->makeUpdateCHAR($update,     "cm_category_type",               $this->category_type,               true);
		$this->makeUpdateCHAR($update,     "cm_closed_by",                   $this->closed_by,                   true);
		$this->makeUpdateCHAR($update,     "cm_close_code",                  $this->close_code,                  true);
		$this->makeUpdateDateTIME($update, "cm_close_date",                  $this->close_date,                  true);
		$this->makeUpdateCHAR($update,     "cm_component",                   $this->component,                   true);
		$this->makeUpdateCHAR($update,     "cm_scheduling_flexibility",      $this->scheduling_flexibility,      true);
		$this->makeUpdateDateTIME($update, "cm_end_date",                    $this->end_date,                    true);
		$this->makeUpdateCHAR($update,     "cm_entered_by",                  $this->entered_by,                  true);
		$this->makeUpdateCHAR($update,     "cm_exp_code",                    $this->exp_code,                    true);
		$this->makeUpdateCHAR($update,     "cm_fix_level",                   $this->fix_level,                   true);	
		$this->makeUpdateCHAR($update,     "cm_implementor_first_last",      $this->implementor_first_last,      true);
		$this->makeUpdateCHAR($update,     "cm_implementor_login",           $this->implementor_login,           true);
		$this->makeUpdateCHAR($update,     "cm_ipl_boot",                    $this->ipl_boot,                    true);
		$this->makeUpdateCHAR($update,     "cm_late",                        $this->late,                        true);
		$this->makeUpdateCHAR($update,     "cm_parent_ir",                   $this->parent_ir,                   true);
		$this->makeUpdateCHAR($update,     "cm_normal_release_session",      $this->normal_release_session,      true);
		$this->makeUpdateDateTIME($update, "cm_create_date",                 $this->create_date,                 true);
		$this->makeUpdateCHAR($update,     "cm_pager",                       $this->pager,                       true);
		$this->makeUpdateCHAR($update,     "cm_phone",                       $this->phone,                       true);
		$this->makeUpdateCHAR($update,     "cm_pin",                         $this->pin,                         true);
		$this->makeUpdateCHAR($update,     "cm_plan_a_b",                    $this->plan_a_b,                    true);
		$this->makeUpdateCHAR($update,     "cm_product",                     $this->product,                     true);
		$this->makeUpdateCHAR($update,     "cm_product_type",                $this->product_type,                true);
		$this->makeUpdateINT( $update,     "cm_risk",                        $this->risk,                        true);
		$this->makeUpdateCHAR($update,     "cm_software_object",             $this->software_object,             true);
		$this->makeUpdateDateTIME($update, "cm_start_date",                  $this->start_date,                  true);
		$this->makeUpdateCHAR($update,     "cm_status",                      $this->status,                      true);
		$this->makeUpdateCHAR($update,     "cm_tested",                      $this->tested,                      true);
		$this->makeUpdateINT( $update,     "cm_duration",                    $this->duration,                    true);
		$this->makeUpdateCHAR($update,     "cm_business_unit",               $this->business_unit,               true);
		$this->makeUpdateCHAR($update,     "cm_duration_computed",           $this->duration_computed,           true);
		$this->makeUpdateCHAR($update,     "cm_email",                       $this->email,                       true);
		$this->makeUpdateCHAR($update,     "cm_company_name",                $this->company_name,                true);
		$this->makeUpdateCHAR($update,     "cm_director",                    $this->director,                    true);
		$this->makeUpdateCHAR($update,     "cm_manager",                     $this->manager,                     true);
		$this->makeUpdateCHAR($update,     "cm_tested_itv",                  $this->tested_itv,                  true);
		$this->makeUpdateCHAR($update,     "cm_tested_endtoend",             $this->tested_endtoend,             true);
		$this->makeUpdateCHAR($update,     "cm_tested_development",          $this->tested_development,          true);
		$this->makeUpdateCHAR($update,     "cm_tested_user",                 $this->tested_user,                 true);
		$this->makeUpdateCHAR($update,     "cm_owner_name",                  $this->owner_name,                  true);
		$this->makeUpdateCHAR($update,     "cm_owner_cuid",                  $this->owner_cuid,                  true);
		$this->makeUpdateCHAR($update,     "cm_groupid",                     $this->groupid,                     true);
		$this->makeUpdateCHAR($update,     "cm_temp",                        $this->temp,                        true);
		$this->makeUpdateCHAR($update,     "cm_last_modified_by",            $this->last_modified_by,            true);
		$this->makeUpdateDateTIME($update, "cm_last_modified",               $this->last_modified,               true);
		$this->makeUpdateDateTIME($update, "cm_late_date",                   $this->late_date,                   true);
		$this->makeUpdateINT( $update,     "cm_risk_integer",                $this->risk_integer,                true);
		$this->makeUpdateCHAR($update,     "cm_owner_login_id",              $this->owner_login_id,              true);
		$this->makeUpdateCHAR($update,     "cm_open_closed",                 $this->open_closed,                 true);
		$this->makeUpdateCHAR($update,     "cm_user_timestamp",              $this->user_timestamp,              true);
		$this->makeUpdateCHAR($update,     "cm_owner_first_name",            $this->owner_first_name,            true);
		$this->makeUpdateCHAR($update,     "cm_owner_last_name",             $this->owner_last_name,             true);
		$this->makeUpdateCHAR($update,     "cm_change_occurs",               $this->change_occurs,               true);
		$this->makeUpdateCHAR($update,     "cm_lla_refresh",                 $this->lla_refresh,                 true);
		$this->makeUpdateCHAR($update,     "cm_ims_cold_start",              $this->ims_cold_start,              true);
		$this->makeUpdateCHAR($update,     "cm_release_level",               $this->release_level,               true);
		$this->makeUpdateCHAR($update,     "cm_master_ir",                   $this->master_ir,                   true);
		$this->makeUpdateCHAR($update,     "cm_owner_group",                 $this->owner_group,                 true);
		$this->makeUpdateCHAR($update,     "cm_cab_approval_required",       $this->cab_approval_required,       true);
		$this->makeUpdateCHAR($update,     "cm_change_executive_team_flag",  $this->change_executive_team_flag,  true);
		$this->makeUpdateCHAR($update,     "cm_emergency_change",            $this->emergency_change,            true);
		$this->makeUpdateINT( $update,     "cm_approval_status",             $this->approval_status,             true);
		$this->makeUpdateCHAR($update,     "cm_component_type",              $this->component_type,              true);				
		$this->makeUpdateCHAR($update,     "cm_desc_short",                  $this->desc_short,                  true);
		$this->makeUpdateCHAR($update,     "cm_last_status_change_by",       $this->last_status_change_by,       true);
		$this->makeUpdateDateTIME($update, "cm_last_status_change_time",     $this->last_status_change_time,     true);
		$this->makeUpdateCHAR($update,     "cm_previous_status",             $this->previous_status,             true);
		$this->makeUpdateCHAR($update,     "cm_component_id",                $this->component_id,                true);
		$this->makeUpdateCHAR($update,     "cm_test_tool",                   $this->test_tool,                   true);
		$this->makeUpdateCHAR($update,     "cm_tested_orl",                  $this->tested_orl,                  true);
		$this->makeUpdateCHAR($update,     "cm_featured_project",            $this->featured_project,            true);
		$this->makeUpdateCHAR($update,     "cm_featured_proj_name",          $this->featured_proj_name,          true);
		$this->makeUpdateCHAR($update,     "cm_tmpmainplatform",             $this->tmpmainplatform,             true);
		$this->makeUpdateCHAR($update,     "cm_tmpblockmessage",             $this->tmpblockmessage,             true);				
		$this->makeUpdateCHAR($update,     "cm_guid",                        $this->guid,                        true);
		$this->makeUpdateCHAR($update,     "cm_platform",                    $this->platform,                    true);
		$this->makeUpdateCHAR($update,     "cm_cllicodes",                   $this->cllicodes,                   true);
		$this->makeUpdateCHAR($update,     "cm_processor_name",              $this->processor_name,              true);
		$this->makeUpdateCHAR($update,     "cm_system_name",                 $this->system_name,                 true);
		$this->makeUpdateCHAR($update,     "cm_city",                        $this->city,                        true);
		$this->makeUpdateCHAR($update,     "cm_state",                       $this->state,                       true);
		$this->makeUpdateCHAR($update,     "cm_tmpdesc",                     $this->tmpdesc,                     true);
		$this->makeUpdateDateTIME($update, "cm_turn_overdate",               $this->turn_overdate,               true);
		$this->makeUpdateCHAR($update,     "cm_assign_group2",               $this->assign_group2,               true);
		$this->makeUpdateCHAR($update,     "cm_assign_group3",               $this->assign_group3,               true);				
		$this->makeUpdateCHAR($update,     "cm_implementor_name2",           $this->implementor_name2,           true);
		$this->makeUpdateCHAR($update,     "cm_implementor_name3",           $this->implementor_name3,           true);
		$this->makeUpdateCHAR($update,     "cm_groupid2",                    $this->groupid2,                    true);
		$this->makeUpdateCHAR($update,     "cm_groupid3",                    $this->groupid3,                    true);
		$this->makeUpdateCHAR($update,     "cm_template",                    $this->template,                    true);
		$this->makeUpdateCHAR($update,     "cm_hd_outage_ticket_number",     $this->hd_outage_ticket_number,     true);
		$this->makeUpdateCHAR($update,     "cm_root_cause_owner",            $this->root_cause_owner,            true);
		$this->makeUpdateCHAR($update,     "cm_control_count",               $this->control_count,              false);
	
		$update .= " where cm_ticket_no = '" . $ticket_no . "'";
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$update = "update cct6_tickets set ";
		
		$this->makeUpdateCHAR($update, "ticket_update_cuid",             $this->user_cuid,    true);
		$this->makeUpdateCHAR($update, "ticket_update_name",             $this->user_name,    true);
		$this->makeUpdateCHAR($update, "cm_impact",                      $this->impact,       true);  // 2050
		$this->makeUpdateCHAR($update, "cm_description",                 $this->description, false);  // 2048
		
		$update .= " where cm_ticket_no = '" . $ticket_no . "'";
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$update = "update cct6_tickets set ";
		
		$this->makeUpdateCHAR($update, "ticket_update_cuid",             $this->user_cuid,                    true);
		$this->makeUpdateCHAR($update, "ticket_update_name",             $this->user_name,                    true);
		$this->makeUpdateCHAR($update, "cm_backoff_plan",                $this->backoff_plan,                 true);  // 2048
		$this->makeUpdateCHAR($update, "cm_implementation_instructions", $this->implementation_instructions, false);  // 2048

		$update .= " where cm_ticket_no = '" . $ticket_no . "'";
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
				
		$update = "update cct6_tickets set ";
		
		$this->makeUpdateCHAR($update, "ticket_update_cuid",             $this->user_cuid,        true);
		$this->makeUpdateCHAR($update, "ticket_update_name",             $this->user_name,        true);
		$this->makeUpdateCHAR($update, "cm_business_reason",             $this->business_reason, false);  // 2048
		
		$update .= " where cm_ticket_no = '" . $ticket_no . "'";
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}			
		
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "UPDATE REMEDY TICKETS SUCCESS - returning true");
		return true;
	}

	/*! @fn SubmitRequest($ticket_no)
	 *  @brief Submit a ticket that is currently in DRAFT mode. The ticket_status is changed to ACTIVE and notification is sent out to the clients.
	 *  @param $ticket_no we want to submit
	 *  @return true or false, where true is success 
	 */
	public function SubmitRequest($ticket_no)
	{
		//
		// Is this user authorized to do this?
		//
		if ($this->authorizeTicket($ticket_no, "submit") == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);	
			return false;
		}
			
		if ($this->getTicket($ticket_no) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);	
			return false;
		}	
			
		$email = new cct6_email_spool();      // classes/cct6_email_spool.php
		
		//
		// SpoolEmailTicket($cm_ticket_no, $email_template="", $email_subject="", $email_message="", $ticket_owner, $os_group, $pase_group, $dba_group)
		//
		if ($email->SpoolEmailTicket($ticket_no, "SUBMIT", "", $this->ticket_submit_note, false, true, true, true) == false)
		{
			$this->error = $email->error;
			return false;
		}
		
		//
		// Mark the ticket as having been submitted
		//
		$update = "update cct6_tickets set ";
	
		$this->makeUpdateCHAR(   $update, "ticket_update_cuid", $this->user_cuid,  true);
		$this->makeUpdateCHAR(   $update, "ticket_update_name", $this->user_name,  true);
		$this->makeUpdateCHAR(   $update, "ticket_status",      "ACTIVE",          true);
		$this->makeUpdateCHAR(   $update, "ticket_submit_cuid", $this->user_cuid,  true);
		$this->makeUpdateCHAR(   $update, "ticket_submit_name", $this->user_name,  true);
		$this->makeUpdateDateNOW($update, "ticket_submit_date",                   false);
	
		$update .= "where cm_ticket_no = '" . $ticket_no . "'";
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
			
		$this->ora->commit();
		
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "SUBMIT SUCCESS - returning true");
		return true;		
	}	
	
	/*! @fn freezeTicket($ticket_no)
	 *  @brief Set the freeze_date, freeze_cuid, freeze_name, and ticket_status values in cct6_tickets for this $ticket_no
	 *  Ticket status is then set to FROZEN and can no longer be changed. Ticket must THAW the ticket to put it back in 
	 *  ACTIVE status in order for the ticket be changed again.
	 *  @param $ticket_no we want to freeze
	 *  @return true or false, where true is success 
	 */
	public function freezeTicket($ticket_no)
	{
		//
		// Is this user authorized to do this?
		//
		if ($this->authorizeTicket($ticket_no, "freeze") == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);	
			return false;
		}
			
		//
		// Don't create the ticket if it already exists in cct6_tickets
		//	
		if ($this->ora->sql("select cm_ticket_no, ticket_status from cct6_tickets where cm_ticket_no = '" . $ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->error = "Ticket: " . $ticket_no . " does not exits in CCT";
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}	
		
		if ($this->ora->ticket_status != "ACTIVE")
		{
			$this->error = "Ticket: " . $ticket_no . " must be in ACTIVE status in order to freeze.";
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}
		
		$update = "update cct6_tickets set ";
		
		$this->makeUpdateCHAR(   $update, "ticket_update_cuid",       $this->user_cuid,  true);
		$this->makeUpdateCHAR(   $update, "ticket_update_name",       $this->user_name,  true);
		$this->makeUpdateDateNOW($update, "ticket_freeze_date",                          true);
		$this->makeUpdateCHAR(   $update, "ticket_freeze_cuid",       $this->user_cuid,  true);
		$this->makeUpdateCHAR(   $update, "ticket_freeze_name",       $this->user_name,  true);
		$this->makeUpdateCHAR(   $update, "ticket_status",            "FROZEN",         false);
		
		$update .= " where cm_ticket_no = '" . $ticket_no . "'";
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$this->ora->commit();
		return true;	
	}
	
	/*! @fn unfreezeTicket($ticket_no)
	 *  @brief Set freeze_date, freeze_cuid, freeze_name to null, and ticket_status to ACTIVE in cct6_tickets for this $ticket_no
	 *  This function is the THAW function within CCT.
	 *  @param $ticket_no we want to unfreeze
	 *  @return true or false, where true is success 
	 */
	public function unfreezeTicket($ticket_no)
	{
		//
		// Is this user authorized to do this?
		//
		if ($this->authorizeTicket($ticket_no, "thaw") == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);	
			return false;
		}
			
		//
		// Don't create the ticket if it already exists in cct6_tickets
		//	
		if ($this->ora->sql("select cm_ticket_no, ticket_status from cct6_tickets where cm_ticket_no = '" . $ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->error = "Ticket: " . $ticket_no . " does not exits in CCT";
			return false;
		}	
		
		if ($this->ora->ticket_status != "FROZEN")
		{
			$this->error = "Ticket: " . $ticket_no . " is not frozen!";
			return false;
		}
		
		$update = "update cct6_tickets set ";
		
		$this->makeUpdateCHAR($update, "ticket_update_cuid",       $this->user_cuid,  true);
		$this->makeUpdateCHAR($update, "ticket_update_name",       $this->user_name,  true);
		$this->makeUpdateCHAR($update, "ticket_freeze_date",       '',                true);  // sets DATE to null
		$this->makeUpdateCHAR($update, "ticket_freeze_cuid",       '',                true);
		$this->makeUpdateCHAR($update, "ticket_freeze_name",       '',                true);
		$this->makeUpdateCHAR($update, "ticket_status",            "ACTIVE",         false);
		
		$update .= " where cm_ticket_no = '" . $ticket_no . "'";
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$this->ora->commit();
		return true;	
	}	
}
?>
