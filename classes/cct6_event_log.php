<?php
/**
 * <cct6_event_log.php>
 *
 * @package    CCT
 * @file       cct6_event_log.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       10/11/2013
 * @version    6.0.0
 */
set_include_path("/opt/ibmtools/www/cct7/classes:/opt/ibmtools/www/cct7/includes:/opt/ibmtools/www/cct7/servers");

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

/*! @class cct6_event_log
 *  @brief This class is used by other classes to attach event log messages to ticket and systems.
 *  @brief Used in classes: cct6_contacts.php, cct6_email_spool.php, cct6_page_spool.php, cct6_systems.php, and gen_request.php
 *  @brief Used in Ajax server: server_group_inbox.php
 */
class cct6_event_log extends library
{
	var $ora = null;
	var $data = array();        // Associated array for properties of $this->xxx
	
	/*! @fn __construct()
	 *  @brief Class constructor - Create oracle object and setup some dynamic class variables
	 *  @return void 
	 */
	public function __construct()
	{
		$this->ora = new dbms();

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
			
			$this->debug_start('cct6_event_log.txt');
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
	
	/*! @fn AddEvent($system_id, $event_type, $event_message)
	 *  @brief Used by various programs to attach an event log message to a cct6_systems record identified by $system_id
	 *  @param $system_id is the system_id number of the cct6_systems record we want to attach this event to.
	 *  @param $event_type is a string identifying the event type: UPDATE, CHANGE, ADDED, CANCEL, DELETE, ADD, LOCK, EMAIL, PAGE, ADD MST, APPROVED, REJECTED, RESCHEDULE, APPROVED 
	 *  @param $event_message is a string containing a message about the event.
	 *  @return true or false, where true is success 
	 */
	public function AddEvent($system_id, $event_type, $event_message)
	{
		if ($system_id > 0)
		{
			$insert = "insert into cct6_event_log (system_id, " .
				"user_cuid, user_name, user_email, user_company, " .
				"manager_cuid, manager_name, manager_email, manager_company, " .
				"event_type, event_message) values (";
				
			$this->makeInsertINT( $insert, $system_id,              true);  // system_id
			$this->makeInsertCHAR($insert, $this->user_cuid,        true);  // user_cuid
			$this->makeInsertCHAR($insert, $this->user_name,        true);  // user_name
			$this->makeInsertCHAR($insert, $this->user_email,       true);  // user_email
			$this->makeInsertCHAR($insert, $this->user_company,     true);  // user_company
			$this->makeInsertCHAR($insert, $this->manager_cuid,     true);  // manager_cuid
			$this->makeInsertCHAR($insert, $this->manager_name,     true);  // manager_name
			$this->makeInsertCHAR($insert, $this->manager_email,    true);  // manager_email
			$this->makeInsertCHAR($insert, $this->manager_company,  true);  // manager_company
			$this->makeInsertCHAR($insert, $event_type,             true);  // event_type
			$this->makeInsertCHAR($insert, $event_message,         false);  // event_message
			
			$insert .= ")";
			
			if ($this->ora->sql($insert) == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
				$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
				return false;
			}
			
			$this->ora->commit();
			return true;
		}
		
		$this->error = "Invalid $system_id value: " . $system_id;
		return false;		
	}
	
	/*! @fn AutoAddEvent($system_id, $event_type, $to_cuid, $to_name, $to_email, $to_company, $mgr_cuid, $mgr_name, $mgr_email, $mgr_company, $event_message)
	 *  @brief CCT automation uses this function to attach events to a cct6_systems record identified by $system_id
	 *  @param $system_id is the system_id number of the cct6_systems record we want to attach this event to.
	 *  @param $event_type is a string identifying the event type: UPDATE, CHANGE, ADDED, CANCEL, DELETE, ADD, LOCK, EMAIL, PAGE, ADD MST, APPROVED, REJECTED, RESCHEDULE, APPROVED 
	 *  @param $to_cuid is the to person cuid
	 *  @param $to_name is the to person's full name
	 *  @param $to_email is the to person's email address
	 *  @param $to_company is the to person's company name
	 *  @param $mgr_cuid is the to person's manager's cuid
	 *  @param $mgr_name is the to person's manager's full name
	 *  @param $mgr_email is the to person's manager's email address
	 *  @param $mgr_company is the to person's manager's company name
	 *  @param $event_message is a string containing a message about the event.
	 *  @return true or false, where true is success 
	 */	
	public function AutoAddEvent($system_id, $event_type, $to_cuid, $to_name, $to_email, $to_company, $mgr_cuid, $mgr_name, $mgr_email, $mgr_company, $event_message)
	{
		if ($system_id > 0)
		{
			$insert = "insert into cct6_event_log (system_id, " .
				"user_cuid, user_name, user_email, user_company, " .
				"manager_cuid, manager_name, manager_email, manager_company, " .
				"event_type, event_message) values (";
				
			$this->makeInsertINT( $insert, $system_id,      true);  // system_id
			$this->makeInsertCHAR($insert, $to_cuid,        true);  // user_cuid
			$this->makeInsertCHAR($insert, $to_name,        true);  // user_name
			$this->makeInsertCHAR($insert, $to_email,       true);  // user_email
			$this->makeInsertCHAR($insert, $to_company,     true);  // user_company
			$this->makeInsertCHAR($insert, $mgr_cuid,       true);  // manager_cuid
			$this->makeInsertCHAR($insert, $mgr_name,       true);  // manager_name
			$this->makeInsertCHAR($insert, $mgr_email,      true);  // manager_email
			$this->makeInsertCHAR($insert, $mgr_company,    true);  // manager_company
			$this->makeInsertCHAR($insert, $event_type,     true);  // event_type
			$this->makeInsertCHAR($insert, $event_message, false);  // event_message
			
			$insert .= ")";
			
			if ($this->ora->sql($insert) == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
				$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
				return false;
			}
			
			$this->ora->commit();
			return true;
		}
		
		$this->error = "Invalid $system_id value: " . $system_id;
		return false;		
	}	
		
}
?>
