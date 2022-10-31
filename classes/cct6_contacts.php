<?php
/**
 * <cct6_contacts.php>
 *
 * @package    CCT
 * @file       cct6_contacts.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       10/11/2013
 * @version    6.0.0
 */
set_include_path("/opt/ibmtools/www/cct8/classes:/opt/ibmtools/www/cct8/includes:/opt/ibmtools/www/cct8/servers");

//
// gen_request.php does not use this class, but has much of the same code found here in this class.
// The difference between gen_request.php and cct6_contacts as far as contact records are concerned
// is in regards to complexes and the reboot flag. In gen_requests.php when the ticket reboot flag is Yes
// and there are complex servers involved, contacts from the children servers are copied to the parent
// complex server. The AddContacts() function in this module does not do any cross checking and copying
// of contacts from children to parent servers.
//
// Used in edit_work_request_server.php a user wants to add a new server and contacts to a work request.
// Also used in the Trace Data Sources program.
//
// Public methods
// UpdateGroupNotifyType($system_id, $contact_id, $contact_group_type, $contact_notify_type)
// UpdateEmailReadDate($cm_ticket_no, $contact_cuid)
// UpdateContactResponseStatus($cm_ticket_no, $system_id, $contact_id, $response_status)
// RemoveContact($cm_ticket_no, $system_id, $contact_id, $hostname, $cancel_message)
// AddContact($cm_ticket_no, $system_id, $contact_cuid)
// AddContacts($cm_ticket_no, $system_id, $lastid, $hostname)
// 
//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

/*! @class cct6_contacts
 *  @brief Canned operations for table: cct6_contacts. 
 *  @brief Used in Ajax servers: server_edit_work_request.php and server_group_inbox.php
 */
class cct6_contacts extends library
{
	var $data = array();       // Associated array for public class variables.
	var $ora;                  // Database connection object
	var $error;

	var $oncall_overrides;      // Master Override Lists by Net-Tool Net-Pin numbers
	var $subscribers;           // APPROVER and FYI subscriber lists by hostname
	var $mnet;                  // Used by foundInMNET($cuid)
		
	var $mail;                  // Email spool object
	var $log;                   // Log object
		
	/*! @fn __construct()
	 *  @brief Class constructor - called once when class is created.
	 *  @brief Create oracle, email and event log objects, and setup some object variables.
	 *  @return void 
	 */	
	public function __construct()
	{
		$this->ora = new dbms();                // classes/dbms.php
		$this->email = new cct6_email_spool();  // classes/cct6_email_spool.php
		$this->log = new cct6_event_log();      // classes/cct6_event_log.php
		
		$this->mnet = array();
		
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
				$this->user_company       = 'qwestibm';
				$this->user_access_level  = 'admin';
				
				$this->manager_cuid       = 'gparkin';
				$this->manager_first_name = 'Greg';
				$this->manager_last_name  = 'Parkin';
				$this->manager_name       = 'Greg Parkin';
				$this->manager_email      = 'gregparkin58@gmail.com';
				$this->manager_company    = 'CMP';
				
				$this->is_debug_on        = 'N';
			}
			
			$this->debug_start('cct6_contacts.txt');
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
	
	public function checkTicketStatus($cm_ticket_no)
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_ticket_no: %s", $cm_ticket_no);
		
		//
		// Check the ticket status before proceeding
		//
		$query = sprintf("select * from cct6_tickets where cm_ticket_no = '%s'", $cm_ticket_no);
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;	
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Cannot locate CCT ticket: %s", $cm_ticket_no);
			$this->error = sprintf("Cannot locate CCT ticket: %s", $cm_ticket_no);
			return false;
		}
		
		if (strlen($this->ora->ticket_freeze_date) > 1)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Ticket: %s is Frozen. Cannot update!", $cm_ticket_no);
			$this->error = sprintf("Ticket: %s is Frozen. Cannot update!", $cm_ticket_no);
			return false;
		}
		
		if (strlen($this->ora->ticket_cancel_date) > 1)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Ticket: %s been canceled. No update possible.", $cm_ticket_no);
			$this->error = sprintf("Ticket: %s been canceled. No update possible.", $cm_ticket_no);
			return false;
		}	
		
		return true;
	}

	/*! @fn UpdatePageMe($contact_id, $page_me)
	 *  @brief Update the 
	 *  @param $contact_id is the contact record ID
	 *  @param $page_me is Y or N to let the paging tool know if this user wants to be paged.
	 *  @return true or false
	 */
	 public function UpdatePageMe($contact_id, $page_me)
	 {
		if ($contact_id <= 0)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "contact_id = %d", $contact_id);
			$this->error = sprintf("%s %s %d: contact_id = %d. Please contact Greg Parkin", __FILE__, __FUNCTION__, __LINE__, $contact_id);
			return false;
		}
	 
		$update = "update cct6_contacts set ";
		
		$this->makeUpdateCHAR($update, "contact_update_cuid",   $this->user_cuid,  true);
		$this->makeUpdateCHAR($update, "contact_update_name",   $this->user_name,  true);		
		$this->makeUpdateCHAR($update, "contact_page_me",       $page_me,         false);
		
		$update .= "where contact_id = " . $contact_id;
		
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
	 	
	/*! @fn UpdateTurnoverNote($contact_id, $contact_turnover_note)
	 *  @brief Update the optional contact turnover note.
	 *  @param $contact_id is the contact record ID
	 *  @param $contact_turnover_note is a turnover note from the client (4000 char max)
	 *  @return true or false
	 */
	 public function UpdateTurnoverNote($contact_id, $contact_turnover_note)
	 {
	 	/*
			if ($this->ora->sql("select * from cct6_contacts where contact_id = " . $contact_id) == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
				$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
				return false;		
			}
			
			if ($this->ora->fetch() == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to fetch cct6_contacts.contact_id = %ld", $contact_id);
				$this->error = sprintf("Unable to fetch cct6_contacts.contact_id = %ld", $contact_id);
				return false;
			}
		*/
		
		if ($contact_id <= 0)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "contact_id = %d", $contact_id);
			$this->error = sprintf("%s %s %d: ontact_id = %d. Please contact Greg Parkin", __FILE__, __FUNCTION__, __LINE__, $contact_id);
			return false;
		}
		
		$update = "update cct6_contacts set ";
		
		$this->makeUpdateCHAR($update, "contact_update_cuid",   $this->user_cuid,        true);
		$this->makeUpdateCHAR($update, "contact_update_name",   $this->user_name,        true);		
		$this->makeUpdateCHAR($update, "contact_turnover_note", $contact_turnover_note, false);
		
		$update .= "where contact_id = " . $contact_id;
		
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
	 
	 public function reassignContact($cm_ticket_no, $hostname, $system_id, $contact_id, $reassign_cuid)
	 {
	 	$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_ticket_no=%s, hostname=%s, system_id=%d, contact_id=%d, reassign_cuid=%s",
			$cm_ticket_no, $hostname, $system_id, $contact_id, $reassign_cuid);
		
		
	 	// Do we have a valid contact record?
	 	if ($this->ora->sql("select contact_name, contact_cuid from cct6_contacts where contact_id = " . $contact_id) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to fetch cct6_contacts.contact_id = %ld", $contact_id);
			$this->error = sprintf("Unable to fetch cct6_contacts.contact_id = %ld", $contact_id);
			return false;
		}
		
		$from_name = $this->ora->contact_name;
		$from_cuid = $this->ora->contact_cuid;
	 
	 	// Do we have a valid reassign cuid?
		if ($this->ora->sql("select * from cct6_mnet where lower(mnet_cuid) = lower('" . $reassign_cuid . "')") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to fetch cct6_mnet = %s", $reassign_cuid);
			$this->error = sprintf("Unable to fetch cct6_mnet = %s", $reassign_cuid);
			return false;
		}
		
		$to_name = $this->ora->mnet_name;
		$to_cuid = $this->ora->mnet_cuid;
		
		// Make the assignment change
		$update = "update cct6_contacts set ";
		
		$this->makeUpdateCHAR($update, "contact_update_cuid",  $this->user_cuid,              true);
		$this->makeUpdateCHAR($update, "contact_update_name",  $this->user_name,              true);
		$this->makeUpdateCHAR($update, "contact_cuid",         $this->ora->mnet_cuid,         true);
		$this->makeUpdateCHAR($update, "contact_last_name",    $this->ora->mnet_last_name,    true);
		$this->makeUpdateCHAR($update, "contact_first_name",   $this->ora->mnet_first_name,   true);
		$this->makeUpdateCHAR($update, "contact_nick_name",    $this->ora->mnet_nick_name,    true);
		$this->makeUpdateCHAR($update, "contact_middle",       $this->ora->mnet_middle,       true);
		$this->makeUpdateCHAR($update, "contact_name",         $this->ora->mnet_name,         true);
		$this->makeUpdateCHAR($update, "contact_job_title",    $this->ora->mnet_job_title,    true);
		$this->makeUpdateCHAR($update, "contact_email",        $this->ora->mnet_email,        true);
		$this->makeUpdateCHAR($update, "contact_work_phone",   $this->ora->mnet_work_phone,   true);
		$this->makeUpdateCHAR($update, "contact_pager",        $this->ora->mnet_pager,        true);
		$this->makeUpdateCHAR($update, "contact_street",       $this->ora->mnet_street,       true);
		$this->makeUpdateCHAR($update, "contact_city",         $this->ora->mnet_city,         true);
		$this->makeUpdateCHAR($update, "contact_state",        $this->ora->mnet_state,        true);
		$this->makeUpdateCHAR($update, "contact_rc",           $this->ora->mnet_rc,           true);
		$this->makeUpdateCHAR($update, "contact_company",      $this->ora->mnet_company,      true);
		$this->makeUpdateCHAR($update, "contact_tier1",        $this->ora->mnet_tier1,        true);
		$this->makeUpdateCHAR($update, "contact_tier2",        $this->ora->mnet_tier2,        true);
		$this->makeUpdateCHAR($update, "contact_tier3",        $this->ora->mnet_tier3,        true);
		$this->makeUpdateCHAR($update, "contact_status",       $this->ora->mnet_status,       true);
		$this->makeUpdateCHAR($update, "contact_change_date",  $this->ora->mnet_change_date,  true);
		$this->makeUpdateCHAR($update, "contact_ctl_cuid",     $this->ora->mnet_ctl_cuid,     true);
		$this->makeUpdateCHAR($update, "contact_mgr_cuid",     $this->ora->mnet_mgr_cuid,    false);
						
		$update .= "where contact_id = " . $contact_id;
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}	
		
		$this->ora->commit();
		
		// Log the reassign event
	 	$this->log->AddEvent($system_id, "REASSIGN", 
			sprintf("Reassign notification from %s (%s) to %s (%s)", $from_name, $from_cuid, $to_name, $to_cuid));
			
		$message = sprintf("CCT work request for %s - %s has been reassigned to you from %s (%s).",
			$cm_ticket_no, $hostname, $from_name, $from_cuid);
		
		// Send a email message to the person reassigned about the ticket
		if ($this->email->SpoolEmailTicketSystemContact($cm_ticket_no, $hostname, $reassign_cuid, "REASSIGN", "CCT Work Request Reassign", $message) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->email->error);
		}

		return true;
	 }

	/*! @fn AddContacts($cm_ticket_no, $system_id, $lastid, $hostname)
	 *  @brief Add contacts for a given ticket and hostname to cct6_contacts
	 *  @param $cm_ticket_no is the Remedy ticket no
	 *  @param $system_id this the cct6_systems.system_id record number
	 *  @param $lastid this the cct6_computers.computer_lastid record number
	 *  @param $hostname is the hostname
	 *  @return true or false
	 */	
	 public function UpdateGroupNotifyType($system_id, $contact_id, $contact_group_type, $contact_notify_type)
	 {
	 	if ($this->ora->sql("select * from cct6_contacts where contact_id = " . $contact_id) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to fetch cct6_contacts.contact_id = %ld", $contact_id);
			$this->error = sprintf("Unable to fetch cct6_contacts.contact_id = %ld", $contact_id);
			return false;
		}
		
		$update = "update cct6_contacts set ";
		
		$this->makeUpdateCHAR($update, "contact_update_cuid", $this->user_cuid,      true);
		$this->makeUpdateCHAR($update, "contact_update_name", $this->user_name,      true);
		$this->makeUpdateCHAR($update, "contact_group_type",  $contact_group_type,   true);
		$this->makeUpdateCHAR($update, "contact_notify_type", $contact_notify_type, false);
		
		$update .= "where contact_id = " . $contact_id;
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}		
		
		$this->ora->commit();
		
	 	$this->log->AddEvent($system_id, "UPDATE", 
			sprintf("Changed %s's group type to %s and notify type to %s.", $this->ora->contact_name, $contact_group_type, $contact_notify_type));
			
		return true;
	 }

	/*! @fn UpdateEmailReadDate($cm_ticket_no, $contact_cuid)
	 *  @brief When a user views a work request (Remedy Ticket) we want to update the cct6_contacts.contact.email_read_date to show they have read the request.
	 *  @0parm $cm_ticket_no is the Remedy ticket number
	 *  @param $contact_id is the cct6_contacts record for the cuid we want update
	 *  @return true or false
	 */	 	 
	 public function UpdateEmailReadDate($cm_ticket_no, $contact_id)
	 {
	 	//
	 	// Gather a list of system_id's for this $cm_ticket_no
		//
		$system_ids = array();
		
		if ($this->ora->sql("select system_id from cct6_systems where cm_ticket_no = '" . $cm_ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;			
		}
		
		while ($this->ora->fetch())
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Got system_id = %d", $this->ora->system_id);
			array_push($system_ids, $this->ora->system_id);
		}
		
		// Retrieve the contact CUID for this contact_id record
	 	if ($this->ora->sql("select * from cct6_contacts where contact_id = " . $contact_id) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;			
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to fetch cct6_contacts where contact_id = %d", $contact_id);
			$this->error = sprintf("Unable to fetch cct6_contacts where contact_id = %d", $contact_id);
			return false;			
		}
		
		// Set the contact_email_read_date for this CUID for all ticket contact records
		foreach ($system_ids as $system_id)
		{
			$update = "update cct6_contacts set contact_email_read_date = SYSDATE where contact_email_read_date is null and ";
			$update .= "system_id = " . $system_id . " and contact_cuid = '" . $this->ora->contact_cuid . "'";

			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "%s", $update);

			if ($this->ora->sql($update) == false)			
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
				$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
				return false;		
			}
		}
		
		$this->ora->commit();
		return true;
	}

	/*! @fn UpdateContactResponseStatus($cm_ticket_no, $system_id, $contact_id, $response_status)
	 *  @brief Used to update the cct6_contacts.contact_response_status field for a given contact_id
	 *  @0parm $cm_ticket_no is the Remedy ticket number
	 *  @param $system_id is the cct6_systems.system_id record number
	 *  @param $contact_id is the cct6_contacts.contact_id record number to be deleted
	 *  @param $response_status = [APPROVED|WAITING|REJECTED|RESCHEDULED|EXEMPT|PENDING|NON-COMPLY|CANCELED|UNKNOWN]
	 *  @return true or false
	 */	 
	 public function UpdateContactResponseStatus($cm_ticket_no, $system_id, $contact_id, $response_status)
	 {
	 	$this->debug1(__FILE__, __FUNCTION__, __LINE__, "UpdateContactResponseStatus(%s, %d, %d, %s)", $cm_ticket_no, $system_id, $contact_id, $response_status);
		$okay = false;
		
	 	switch ( $response_status )
		{
			case 'APPROVED':
			case 'WAITING':			
			case 'REJECTED':
			case 'RESCHEDULED':
			case 'EXEMPT':
			case 'PENDING':
			case 'NON-COMPLY':
			case 'CANCELED':
			case 'UNKNOWN':
				break;
			default:
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Invalid response status = %s", $response_status);
				$this->error = sprintf("Invalid response status = %s", $response_status);
				return false;
		}
		
		//
		// Check the ticket status before proceeding. Stop if ticket is frozen or canceled.
		//
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Calling: checkTicketStatus(%s)", $cm_ticket_no);
		
		if ($this->checkTicketStatus($cm_ticket_no)	== false)
			return false;

		//
		// Get the contact record we are approving.
		//
	 	if ($this->ora->sql("select * from cct6_contacts where contact_id = " . $contact_id) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to fetch cct6_contacts.contact_id = %ld", $contact_id);
			$this->error = sprintf("Unable to fetch cct6_contacts.contact_id = %ld", $contact_id);
			return false;
		}
		
		//
		// Check to see if this record is being approved by someone else.
		//
		if ($this->user_cuid != $this->ora->contact_cuid)
		{	
			//		
			// User is updating for someone else. Make sure it is not for the master approver
			//
			$query  = "select ";
			$query .= "  m.computer_hostname as computer_hostname, ";
			$query .= "  m.approver_cuid     as approver_cuid, ";
			$query .= "  m.approver_name     as approver_name ";
			$query .= "from ";
			$query .= "  cct6_systems s, ";
			$query .= "  cct6_master_approvers m ";
			$query .= "where ";
			$query .= "  s.system_id = " . $system_id . " and ";
			$query .= "  m.computer_hostname = s.computer_hostname";
			
			if ($this->ora->sql($query) == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
				$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
				return false;		
			}
			
			if ($this->ora->fetch() == true)
			{
				//
				// Ignore exceptions.
				// cctadm - CCT Automation
				// iamcct - Mark S. Vassar (mvassar)
				//
				$okay = false;
				
				// if ($this->user_cuid == 'mvassar' && $this->ora->approver_cuid == 'iamcct')
				if ($this->ora->approver_cuid == 'iamcct')
				{
					$okay = true;
				}
				
				if ($okay == false && $this->user_cuid != "cctadm" && ($this->user_cuid != $this->ora->approver_cuid))
				{
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Cannot approve for a Master Approver");
					$this->error = sprintf("%s (%s) is a Master Approver for this server. You cannot approve this for them.", 
						$this->ora->approver_name, $this->ora->approver_cuid);
					return false;
				}
			}
		}

		//
		// Update the user contact status
		//
		$update = "update cct6_contacts set ";
		
		$this->makeUpdateCHAR(   $update, "contact_update_cuid", $this->user_cuid,        true);
		$this->makeUpdateCHAR(   $update, "contact_update_name", $this->user_name,        true);
		
		// We will assume they read the work request if contact_email_read_date string is empty
		if (strlen($this->ora->contact_email_read_date) == 0)
		{
			$this->makeUpdateDateNOW($update, "contact_email_read_date",                  true);
		}
		
		$this->makeUpdateCHAR(   $update, "contact_response_status",  $response_status,   true);
		$this->makeUpdateDateNOW($update, "contact_response_date",                       false);
		
		$update .= "where contact_id = " . $contact_id;
		
		$this->debug3(__FILE__, __FUNCTION__, __LINE__, "%s", $update);
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}		
		
		$this->ora->commit();
		
	 	$this->log->AddEvent($system_id, "UPDATE", sprintf("%s has %s this work item.", $this->ora->contact_name, $response_status));
			
		return true;	 
	 }

	/*! @fn RescheduleSystemWork($cm_ticket_no, $system_id, $hostname, $contact_id, $contact_reschedule_start, $contact_reschedule_end, $reason_for_change)
	 *  @brief Reschedule the work and if necessary send an email to the Remedy ticket owner
	 *  @brief $this->contact_reschedule_start shows the new contact_reschedule_start datetime updated in the contact record
	 *  @brief contact_reschedule_end shows the new contact_reschedule_end datetime updated in the contact record
	 *  @brief contact_reschedule_duration shows the new compulted contact_reschedule_duration updated in the contact record
	 *  @brief contact_response_status shows the new contact rsponse status value which will be APPROVED or RESCHEDULED
	 *  @0parm $cm_ticket_no is the Remedy ticket number
	 *  @param $system_id is the cct6_systems.system_id record number
	 *  @param $hostname is the hostname for the server
	 *  @param $contact_id is the cct6_contacts.contact_id record number
	 *  @param $contact_reschedule_start is the new start date for the reschedule
	 *  @param $contact_reschedule_end is the new end date for the reschedule
	 *  @param $reason_for_change is the reason for the change
	 *  @return true or false
	 */	 
	 public function RescheduleSystemWork($cm_ticket_no, $system_id, $hostname, $contact_id, $contact_reschedule_start, $contact_reschedule_end, $reason_for_change)
	 {
	 	$this->debug1(__FILE__, __FUNCTION__, __LINE__, "RescheduleSystemWork(ticket=%s, system_id=%d, host=%s, contact_id=%d, start=%s, end=%s, reason=%s)",
			$cm_ticket_no, $system_id, $hostname, $contact_id, $contact_reschedule_start, $contact_reschedule_end, $reason_for_change);
			
		//
		// Check the ticket status before proceeding. Stop if ticket is frozen or canceled.
		//
		if ($this->checkTicketStatus($cm_ticket_no)	== false)
			return false;			
			
	 	if ($this->ora->sql("select * from cct6_tickets where cm_ticket_no = '" . $cm_ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to fetch Remedy ticket: %s", $cm_ticket_no);
			$this->error = sprintf("Unable to fetch Remedy ticket: %s", $cm_ticket_no);
			return false;
		}			
		
		if ($this->ora->sql("select * from cct6_contacts where contact_id = " . $contact_id) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}

		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to fetch cct6_contacts record where contact_id = %d", $contact_id);
			$this->error = sprintf("Unable to fetch cct6_contacts record where contact_id = %d", $contact_id);
			return false;
		}			

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_start_date = %s", $this->ora->cm_start_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_end_date = %s", $this->ora->cm_end_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_time = %s", $contact_reschedule_start);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "end_time = %s", $contact_reschedule_end);

		$cm_start_time = strtotime($this->ora->cm_start_date);
		$cm_end_time = strtotime($this->ora->cm_end_date);
		$start_time = strtotime($contact_reschedule_start);
		$end_time   = strtotime($contact_reschedule_end);
		
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_start_time=%ld, cm_end_time=%ld, start_time=%ld, end_time=%ld",
			$cm_start_time, $cm_end_time, $start_time, $end_time);
		
		if ($start_time >= $end_time)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Start datetime: %s is greater than or equal to End datetime: %s", 
				$contact_reschedule_start, $contact_reschedule_end);
			$this->error = sprintf("Start datetime: %s is greater than or equal to End datetime: %s", $contact_reschedule_start, $contact_reschedule_end);
			return false;		
		}
		
		$contact_response_status = "RESCHEDULED";
		
		//
		// If the rescheduled work falls within the IR window then change the contact_response_status to APPROVED
		//
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_time(%ld) >= cm_start_time(%ld) and end_time(%ld) <= cm_end_time(%ld)",
			$start_time, $cm_start_time, $end_time, $cm_end_time);
			
		if ($start_time >= $cm_start_time && $end_time <= $cm_end_time)
		{
			$contact_response_status = "APPROVED";
		}
				
	 	$duration = $this->getDuration($contact_reschedule_start, $contact_reschedule_end);
		
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "contact_response_status = %s", $contact_response_status);
		
		$update = "update cct6_contacts set ";
		
		$this->makeUpdateCHAR(    $update, "contact_update_cuid",         $this->user_cuid,          true);
		$this->makeUpdateCHAR(    $update, "contact_update_name",         $this->user_name,          true);
		
		// We will assume they read the work request if contact_email_read_date string is empty
		if (strlen($this->ora->contact_email_read_date) == 0)
		{
			$this->makeUpdateDateNOW($update, "contact_email_read_date",                             true);
		}
		
		$this->makeUpdateCHAR(    $update, "contact_response_status",     $contact_response_status,  true);
		$this->makeUpdateDateNOW( $update, "contact_response_date",                                  true);
		$this->makeUpdateDateTIME($update, "contact_reschedule_start",    $contact_reschedule_start, true);
		$this->makeUpdateDateTIME($update, "contact_reschedule_end",      $contact_reschedule_end,   true);
		$this->makeUpdateCHAR(    $update, "contact_reschedule_duration", $duration,                false);
		
		$update .= "where contact_id = " . $contact_id . " and system_id = " . $system_id;
		
		$this->debug3(__FILE__, __FUNCTION__, __LINE__, "%s", $update);
		
		if ($this->ora->sql($update) == false )
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}		
		
		$this->ora->commit();
		
		//
		// Update object properity values
		//
		$this->contact_reschedule_start = $contact_reschedule_start;
		$this->contact_reschedule_end = $contact_reschedule_end;
		$this->contact_reschedule_duration = $duration;
		$this->contact_response_status = $contact_response_status;
		
		$this->log->AddEvent($system_id, "RESCHEDULE", 
			sprintf("Requesting work to be rescheduled: %s - %s %s", $contact_reschedule_start, $contact_reschedule_end, $reason_for_change));
				
		if ($contact_response_status == "APPROVED")
		{
			$this->log->AddEvent($system_id, "RESCHEDULE", 
				sprintf("Because rescheduled work for %s - %s falls within Ticket Window, the work will be approved for this user.", 
					$contact_reschedule_start, $contact_reschedule_end));
					
			$this->log->AddEvent($system_id, "RESCHEDULE", sprintf("Reschedule reason: %s", $reason_for_change));
			
			//
			// Change the cct6_systems.system_actual_work_start and cct6_systems.system_actual_work_end for this $system_id
			//
			$sys = new cct6_systems();  // classes/cct6_systems.php
			
			if ($sys->UpdateSystemActualWorkStartEnd($system_id, $contact_reschedule_start, $contact_reschedule_end, $duration) == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ERROR: %s", $sys->error);
				$this->error = $sys->error;	
				return false;		
			}
		}
		else
		{
			$txt = sprintf("<p>%s is requesting that the %s work for ticket %s on %s be rescheduled to: %s - %s for reason: %s.</p><p>The IR window for %s is %s to %s. If you extend the IR window to commondate the reschedule work dates and times, CCT will approve the work for this user.</p>", 
				$this->ora->contact_name, $this->ora->classification, $cm_ticket_no, $hostname, $contact_reschedule_start, 
				$contact_reschedule_end, $reason_for_change, $cm_ticket_no, $this->ora->cm_start_date, $this->ora->cm_end_date);

			$email = new cct6_email_spool();  // classes/cct6_email_spool.php
			
			//
			// $this->user_xxx is from_xxx
			//
			if ($email->SpoolRescheduleTicketSystem($cm_ticket_no, $hostname, $this->user_cuid, $this->user_name, $this->user_email, $txt) == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ERROR: %s", $email->error);
				$this->error = $email->error;
				return false;				
			}
		}
		
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "returning true");
		return true;
	 }
	 	
	/*! @fn RemoveContact($cm_ticket_no, $system_id, $contact_id, $hostname, $cancel_message)
	 *  @brief Remove a contact from a server's notification list.
	 *  @0parm $cm_ticket_no is the Remedy ticket number
	 *  @param $system_id is the cct6_systems.system_id record number
	 *  @param $contact_id is the cct6_contacts.contact_id record number to be deleted
	 *  @param $hostname is the system that we are removing the contact from
	 *  @param $cancel_message is the reason why we are removing this contact from the list
	 *  @return true or false
	 */
	public function RemoveContact($cm_ticket_no, $system_id, $contact_id, $hostname, $cancel_message)
	{
		//
		// Check the ticket status before proceeding. Stop if ticket is frozen or canceled.
		//
		if ($this->checkTicketStatus($cm_ticket_no)	== false)
			return false;
			
		//
		// Grab some information about the person we are removing for the event log file
		//
		$query = "select * from cct6_contacts where contact_id = " . $contact_id;
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->ora->fetch() == false)
		{
			$error = sprintf("%s, %s: cct6_contacts record not found where contact_id=%ld", $cm_ticket_no, $hostname, $contact_id);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $error);
			$this->error = $error;
			return false;		
		}
		
		$delete = "delete from cct6_contacts where contact_id = " . $contact_id;
		
		if ($this->ora->sql($delete) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$this->ora->commit();

		$this->log->AddEvent($system_id, "DELETE", 
			sprintf("Removed %s (%s) from contact list. %s", $this->ora->contact_name, $this->ora->contact_cuid, $cancel_message));
						 
		return true;
	}
	
	/*! @fn AddContact($cm_ticket_no, $system_id, $hostname, $contact_cuid)
	 *  @brief Add a new contact to this $system_id record
	 *  @0parm $cm_ticket_no is the Remedy ticket number
	 *  @param $system_id is the cct6_systems.system_id record number
	 *  @param $hostname is the name of host server
	 *  @param $contact_cuid is the CUID of the person we want to add for this $system_id
	 *  @return true or false
	 */		
	public function AddContact($cm_ticket_no, $system_id, $hostname, $contact_cuid)
	{
		//
		// Check the ticket status before proceeding. Stop if ticket is frozen or canceled.
		//
		if ($this->checkTicketStatus($cm_ticket_no)	== false)
			return false;
			
		//
		// Don't add the contact if the record already exists
		//
		$query = "select * from cct6_contacts where system_id = " . $system_id . " and contact_cuid = '" . $contact_cuid . "'";
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->ora->fetch() == true)
		{
			$error = sprintf("CUID: %s is already a contact for this server.", $contact_cuid);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $error);
			$this->error = $error;
			return false;		
		}
		
		//
		// Get Ticket status so we can determine if an email notification needs to be sent out.
		//
		if ($this->ora->sql("select * from cct6_tickets where cm_ticket_no = '" . $cm_ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Ticket: %s does not exist in cct6_tickets", $cm_ticket-no);
			$this->error = sprintf("Ticket: %s does not exist in cct6_tickets", $cm_ticket-no);
			return false;
		}

		//
		// Retrieve the MNET record for this contact and ensure that it has a valid email address.
		//
		$query = sprintf("select * from cct6_mnet where lower(mnet_cuid) = lower('%s')", $contact_cuid);
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "CUID: %s does not exist in MNET", $contact_cuid);
			$this->error = sprintf("CUID: %s does not exist in MNET", $contact_cuid);
			return false;		
		}
		
		// Check for valid email address
		if (strlen($this->ora->mnet_email) == 0 || $this->isValidEmail($this->ora->mnet_email) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "CUID: %s does not have a valid MNET email address: %s", $cuid, $this->ora->mnet_email);
			$this->error = sprintf("CUID: %s does not have a valid MNET email address: %s", $cuid, $this->ora->mnet_email);
			return false;			
		}
		
		// Get a new record number from the sequenct table for cct6_contacts
		$contact_id = $this->ora->next_seq('cct6_contactsseq');
		            
		$contact_insert_cuid = $this->user_cuid;
		$contact_insert_name = $this->user_name;
		
		$contact_csc_banner = 'Not used';              // VARCHAR2 (i.e. Application Support)
		$contact_app_acronym = 'Not available';        // VARCHAR2 Application acronym (i.e. CCT)
		$contact_group_type = 'PASE';                   // VARCHAR2 OS, PASE, DBA, OTHER
		
		if ($this->ora->ticket_approvals_required == 'Y')
		{
			$contact_notify_type = 'APPROVER';         // VARCHAR2 APPROVER, FYI
			$contact_response_status = 'WAITING';
		}
		else
		{
			$contact_notify_type = 'FYI';              // VARCHAR2 APPROVER, FYI
			$contact_response_status = 'FYI';
		}
		
		$contact_source = 'Manual Add';                // VARCHAR2 CSC, CCT, On-Call
		$contact_override = 'N';                       // VARCHAR2 Override used? Y or N
		$contact_last_name = $this->ora->mnet_last_name;
		$contact_first_name = $this->ora->mnet_first_name;
		$contact_nick_name = $this->ora->mnet_nick_name;
		$contact_middle = $this->ora->mnet_middle;
		$contact_name = $this->ora->mnet_name;
		$contact_job_title = $this->ora->mnet_job_title;
		$contact_email = $this->ora->mnet_email;
		$contact_work_phone = $this->ora->mnet_work_phone;
		$contact_pager = $this->ora->mnet_pager;
		$contact_street = $this->ora->mnet_street;
		$contact_city = $this->ora->mnet_city;
		$contact_state = $this->ora->mnet_state;
		$contact_rc = $this->ora->mnet_rc;
		$contact_company = $this->ora->mnet_company;
		$contact_tier1 = $this->ora->mnet_tier1;
		$contact_tier2 = $this->ora->mnet_tier2;
		$contact_tier3 = $this->ora->mnet_tier3;
		$contact_status = $this->ora->mnet_status;
		$contact_change_date = $this->ora->mnet_change_date;
		$contact_ctl_cuid = $this->ora->mnet_ctl_cuid;
		$contact_mgr_cuid = $this->ora->mnet_mgr_cuid;

		// Build the $insert SQL command
		$insert = "insert into cct6_contacts (" .
					"contact_id, system_id, contact_response_status, contact_insert_cuid, contact_insert_name, contact_csc_banner, " .
					"contact_app_acronym, contact_group_type, contact_notify_type, contact_source, contact_override, contact_cuid, " .
					"contact_last_name, contact_first_name, contact_nick_name, contact_middle, contact_name, contact_job_title, " .
					"contact_email, contact_work_phone, contact_pager, contact_street, contact_city, contact_state, " .
					"contact_rc, contact_company, contact_tier1, contact_tier2, contact_tier3, contact_status, " .
					"contact_change_date, contact_ctl_cuid, contact_mgr_cuid ) values ( ";
					
		$this->makeInsertINT(     $insert, $contact_id,              true);
		$this->makeInsertINT(     $insert, $system_id,               true);
		$this->makeInsertCHAR(    $insert, $contact_response_status, true);
		$this->makeInsertCHAR(    $insert, $contact_insert_cuid,     true);
		$this->makeInsertCHAR(    $insert, $contact_insert_name,     true);
		$this->makeInsertCHAR(    $insert, $contact_csc_banner,      true);
		$this->makeInsertCHAR(    $insert, $contact_app_acronym,     true);
		$this->makeInsertCHAR(    $insert, $contact_group_type,      true);
		$this->makeInsertCHAR(    $insert, $contact_notify_type,     true);
		$this->makeInsertCHAR(    $insert, $contact_source,          true);
		$this->makeInsertCHAR(    $insert, $contact_override,        true);
		$this->makeInsertCHAR(    $insert, $contact_cuid,            true);
		$this->makeInsertCHAR(    $insert, $contact_last_name,       true);
		$this->makeInsertCHAR(    $insert, $contact_first_name,      true);
		$this->makeInsertCHAR(    $insert, $contact_nick_name,       true);
		$this->makeInsertCHAR(    $insert, $contact_middle,          true);
		$this->makeInsertCHAR(    $insert, $contact_name,            true);
		$this->makeInsertCHAR(    $insert, $contact_job_title,       true);
		$this->makeInsertCHAR(    $insert, $contact_email,           true);
		$this->makeInsertCHAR(    $insert, $contact_work_phone,      true);
		$this->makeInsertCHAR(    $insert, $contact_pager,           true);
		$this->makeInsertCHAR(    $insert, $contact_street,          true);
		$this->makeInsertCHAR(    $insert, $contact_city,            true);
		$this->makeInsertCHAR(    $insert, $contact_state,           true);
		$this->makeInsertCHAR(    $insert, $contact_rc,              true);
		$this->makeInsertCHAR(    $insert, $contact_company,         true);
		$this->makeInsertCHAR(    $insert, $contact_tier1,           true);
		$this->makeInsertCHAR(    $insert, $contact_tier2,           true);
		$this->makeInsertCHAR(    $insert, $contact_tier3,           true);
		$this->makeInsertCHAR(    $insert, $contact_status,          true);
		$this->makeInsertDateTIME($insert, $contact_change_date,     true);
		$this->makeInsertCHAR(    $insert, $contact_ctl_cuid,        true);
		$this->makeInsertCHAR(    $insert, $contact_mgr_cuid,        false);
					
		$insert .= 	" )";
		
		if ($this->ora->sql($insert) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}				
		
		//
		// If the ticket has already been submitted then we want to submit notifications to these new contacts as well.
		//
		if ($this->ora->ticket_status == 'ACTIVE')
		{
			//
			// SpoolEmailTicket($cm_ticket_no, $email_template="", $email_subject="", $email_message="", $ticket_owner, $os_group, $pase_group, $dba_group)
			//
			if ($this->email->SpoolEmailTicketSystemContact($cm_ticket_no, $hostname, $contact_cuid, 'SUBMIT', "", "") == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->email->error);
				$this->error = $this->email->error;
				return false;	
			}
		}
		
		$this->debug4(__FILE__, __FUNCTION__, __LINE__, "commit and return true");
		$this->ora->commit();
		
		$this->log->AddEvent($system_id, "ADD", 
			sprintf("Added CUID: %s for %s. Set group type to %s and notify type to %s.", $contact_cuid, $contact_name, $contact_group_type, $contact_notify_type));
			
		return true;	 			
	}
		 
	/*! @fn AddContacts($cm_ticket_no, $system_id, $lastid, $hostname)
	 *  @brief Add contacts for a given ticket and hostname to cct6_contacts
	 *  @param $cm_ticket_no is the Remedy ticket no
	 *  @param $system_id this the cct6_systems.system_id record number
	 *  @param $lastid this the cct6_computers.computer_lastid record number
	 *  @param $hostname is the hostname
	 *  @return true or false
	 */		
	public function AddContacts($cm_ticket_no, $system_id, $lastid, $hostname)
	{
		//
		// Check the ticket status before proceeding. Stop if ticket is frozen or canceled.
		//
		if ($this->checkTicketStatus($cm_ticket_no)	== false)
			return false;
			
		$e = new cct6_event_log();          // classes/cct6_event_log.php
		
		$top_contact = $p_contact = null;   // $top_contact is attached to $this->cur_host->contacts
		$duplicate_contact = array();       // Used to weed out duplicates
			
		//
		// Get classification and ticket_approvals_required from cct6_tickets
		//
		if ($this->ora->sql("select * from cct6_tickets where cm_ticket_no = '" . $cm_ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Ticket: %s does not exist in cct6_tickets", $cm_ticket-no);
			$this->error = sprintf("Ticket: %s does not exist in cct6_tickets", $cm_ticket-no);
			return false;
		}
		
		//
		// Check to make sure we can locate a Asset Center record for this hostname.
		//
		if ($this->ora->sql("select * from cct6_computers where computer_lastid = " . $lastid) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		if ($this->ora->fetch() == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Cannot locate hostname: %s in Asset Center!", $hostname);
			$this->error = sprintf("Cannot locate hostname: %s in Asset Center!", $hostname);
			return false;
		}
		
		if ($this->loadOncallOverrides() == false)
			return false;
			
		if ($this->loadSubscribers() == false)
			return false;		
		
		//
		// Check for Master Approver Override for this host - cct6_master_approver
		// When there is a master approver all other contact approvers notify type is changed to FYI.
		// The Master Approver will be the only person that is allowed to give the green light for the
		// pending work.
		//
		$query = "select " .
					"o.computer_hostname, " .
					"o.approver_cuid, " .
					"o.approver_name, " .
					"m.mnet_cuid, " .
					"m.mnet_last_name, " .
					"m.mnet_first_name, " .
					"m.mnet_nick_name, " .
					"m.mnet_middle, " .
					"m.mnet_name, " .
					"m.mnet_job_title, " .
					"m.mnet_email," .
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
					"cct6_master_approvers o, " .
					"cct6_mnet m " .
				"where " .
					"m.mnet_cuid = o.approver_cuid and " .
					"o.computer_hostname = '" . $hostname . "'";					
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$master_approver_found = false;
		
		if ($this->ora->fetch())
		{
			$master_approver_found = true;
			
			$top_contact = $p_contact = new data_contacts();
			
			$p_contact->contact_csc_banner = 'Override Used';                 // VARCHAR2 (i.e. Application Support)
			$p_contact->contact_app_acronym = 'ALL APPS';                     // VARCHAR2 Application acronym (i.e. CCT)
			$p_contact->contact_group_type = 'PASE';                           // VARCHAR2 OS, PASE, DBA, OTHER
			$p_contact->contact_notify_type = 'APPROVER';                     // VARCHAR2 APPROVER, FYI
			$p_contact->contact_source = 'CCT Master Approver Override';      // VARCHAR2 CSC, CCT, On-Call
			$p_contact->contact_cuid = $this->ora->mnet_cuid;                 // VARCHAR2 Contact CUID login name
			$p_contact->contact_override = 'Y';                               // VARCHAR2 Override used? Y or N
			$p_contact->contact_last_name = $this->ora->mnet_last_name;       // VARCHAR2 Contact last name
			$p_contact->contact_first_name = $this->ora->mnet_first_name;     // VARCHAR2 Contact first name
			$p_contact->contact_nick_name = $this->ora->mnet_nick_name;       // VARCHAR2 Contact nick name
			$p_contact->contact_middle = $this->ora->mnet_middle;             // VARCHAR2 Contact middle name
			$p_contact->contact_name = $this->ora->mnet_name;                 // VARCHAR2 Contact name
			$p_contact->contact_job_title = $this->ora->mnet_job_title;       // VARCHAR2 Contact Job Title
			$p_contact->contact_email = $this->ora->mnet_email;               // VARCHAR2 Contact email address
			$p_contact->contact_work_phone = $this->ora->mnet_work_phone;     // VARCHAR2 Contact work phone number
			$p_contact->contact_pager = $this->ora->mnet_pager;               // VARCHAR2 Contact pager number
			$p_contact->contact_street = $this->ora->mnet_street;             // VARCHAR2 Contact street
			$p_contact->contact_city = $this->ora->mnet_city;                 // VARCHAR2 Contact City
			$p_contact->contact_state = $this->ora->mnet_state;               // VARCHAR2 Contact State
			$p_contact->contact_rc = $this->ora->mnet_rc;                     // VARCHAR2 Contact RC
			$p_contact->contact_company = $this->ora->mnet_company;           // VARCHAR2 Contact company name
			$p_contact->contact_tier1 = $this->ora->mnet_tier1;               // VARCHAR2 Contact tier1 support information
			$p_contact->contact_tier2 = $this->ora->mnet_tier2;               // VARCHAR2 Contact tier2 support information
			$p_contact->contact_tier3 = $this->ora->mnet_tier3;               // VARCHAR2 Contact tier3 support information
			$p_contact->contact_status = $this->ora->mnet_status;             // VARCHAR2 Contact employee status
			$p_contact->contact_change_date = $this->ora->mnet_change_date;   // DATE     MNET information change date
			$p_contact->contact_ctl_cuid = $this->ora->mnet_ctl_cuid;         // VARCHAR2 Contact CTL sponsor CUID person
			$p_contact->contact_mgr_cuid = $this->ora->mnet_mgr_cuid;         // VARCHAR2 Contact Manager CUID person
			
			$duplicate_contact[$this->ora->mnet_cuid] = $this->ora->mnet_name;
		}
		
		//
		// Read CSC database table and gather banners for this system (LASTID)
		//
		$query = "select " .
				"cct_csc_netgroup, " .
				"cct_app_acronym, " .
				"cct_csc_userid_1, " .
				"cct_csc_userid_2, " .
				"cct_csc_userid_3, " .
				"cct_csc_userid_4, " .
				"cct_csc_userid_5, " .
				"cct_csc_group_name, " .
				"cct_csc_oncall " .
			"from " .
				"cct6_csc " .
			"where " .
				"lastid = " . $lastid . "and ( " .
				"cct_csc_group_name = 'MiddleWare Support' or " .
				"cct_csc_group_name = 'Development Support' or " .
				"cct_csc_group_name = '! Operating System Support' or " .
				"cct_csc_group_name = '! Database Support' or " .
				"cct_csc_group_name = 'Application Support' or " .
				"cct_csc_group_name = 'Infrastructure' or " .
				"cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or " .
				"cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or " .
				"cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)' ) " .
			"order by cct_csc_group_name";
			 
		$top_csc = $p_csc = null;
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}	
		
		while ($this->ora->fetch())
		{
			if ($top_csc == null)
			{
				$top_csc = $p_csc = new data_csc();
			}
			else
			{
				$p_csc->next = new data_csc();
				$p_csc = $p_csc->next;
			}
			
			$p_csc->cct_csc_netgroup = $this->ora->cct_csc_netgroup;                                 // VARCHAR2 cct6_contacts.contact_csc_banner
			$p_csc->cct_app_acronym = $this->ora->cct_app_acronym;                                   // VARCHAR2 cct6_contacts.contact_app_acronym
			$p_csc->cct_csc_userid_1 = $this->ora->cct_csc_userid_1;                                 // VARCHAR2
			$p_csc->cct_csc_userid_2 = $this->ora->cct_csc_userid_2;                                 // VARCHAR2
			$p_csc->cct_csc_userid_3 = $this->ora->cct_csc_userid_3;                                 // VARCHAR2
			$p_csc->cct_csc_userid_4 = $this->ora->cct_csc_userid_4;                                 // VARCHAR2
			$p_csc->cct_csc_userid_5 = $this->ora->cct_csc_userid_5;                                 // VARCHAR2
			$p_csc->cct_csc_group_name = $this->ora->cct_csc_group_name;                             // VARCHAR2
			$p_csc->cct_csc_oncall = $this->ora->cct_csc_oncall;                                     // VARCHAR2
			
			$this->debug4(__FILE__, __FUNCTION__, __LINE__, "cct_csc_netgroup:   %s", $p_csc->cct_csc_netgroup);
			$this->debug4(__FILE__, __FUNCTION__, __LINE__, "cct_app_acronym:    %s", $p_csc->cct_app_acronym);
			$this->debug4(__FILE__, __FUNCTION__, __LINE__, "cct_csc_userid_1:   %s", $p_csc->cct_csc_userid_1);
			$this->debug4(__FILE__, __FUNCTION__, __LINE__, "cct_csc_userid_2:   %s", $p_csc->cct_csc_userid_2);
			$this->debug4(__FILE__, __FUNCTION__, __LINE__, "cct_csc_userid_3:   %s", $p_csc->cct_csc_userid_3);
			$this->debug4(__FILE__, __FUNCTION__, __LINE__, "cct_csc_userid_4:   %s", $p_csc->cct_csc_userid_4);
			$this->debug4(__FILE__, __FUNCTION__, __LINE__, "cct_csc_userid_5:   %s", $p_csc->cct_csc_userid_5);
			$this->debug4(__FILE__, __FUNCTION__, __LINE__, "cct_csc_group_name: %s", $p_csc->cct_csc_group_name);
			$this->debug4(__FILE__, __FUNCTION__, __LINE__, "cct_csc_oncall:     %s", $p_csc->cct_csc_oncall);	
			$this->debug4(__FILE__, __FUNCTION__, __LINE__, "-----------------------------------------------------");
		}
		
		//
		// Figure out what contact to use for each group based upon CMP policy rules.
		//
		for ($p_csc=$top_csc; $p_csc!=null; $p_csc=$p_csc->next)
		{
			if ($p_csc->cct_csc_group_name == 'Development Support')
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Development Support: PASE - FYI");
				$notify_type = 'FYI'; 
				$group_type = 'PASE';
			}
			else if ($p_csc->cct_csc_group_name == 'MiddleWare Support')
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "MiddleWare Support: PASE - APPROVER");
				$group_type = 'PASE';
			}
			else if ($p_csc->cct_csc_group_name == '! Operating System Support')
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "! Operating System Support: OS - %s", $master_approver_found == true ? 'FYI' : 'APPROVER');
				$notify_type = $master_approver_found == true ? 'FYI' : 'APPROVER';
				$group_type = 'OS';				
			}
			else if ($p_csc->cct_csc_group_name == '! Database Support')
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "! Database Support: DBA - %s", $master_approver_found == true ? 'FYI' : 'APPROVER');
				$notify_type = $master_approver_found == true ? 'FYI' : 'APPROVER';
				$group_type = 'DBA';			
			}
			else if ($p_csc->cct_csc_group_name == 'Application Support')
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Application Support: PASE - %s", $master_approver_found == true ? 'FYI' : 'APPROVER');
				$notify_type = $master_approver_found == true ? 'FYI' : 'APPROVER';
				$group_type = 'PASE';			
			}
			else if ($p_csc->cct_csc_group_name == 'Infrastructure')
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Infrastructure: PASE - %s", $master_approver_found == true ? 'FYI' : 'APPROVER');
				$notify_type = $master_approver_found == true ? 'FYI' : 'APPROVER';
				$group_type = 'PASE';			
			}
			else if ($p_csc->cct_csc_group_name == 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)')
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server): PASE - %s", 
					$master_approver_found == true ? 'FYI' : 'APPROVER');
				$notify_type = $master_approver_found == true ? 'FYI' : 'APPROVER'; 
				$group_type = 'PASE';			
			}
			else if ($p_csc->cct_csc_group_name == 'Applications or Databases Desiring Notification (Not Hosted on this Server)')
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Applications or Databases Desiring Nofication (Not Hosted on this Server): PASE - FYI");
				$notify_type = 'FYI'; 
				$group_type = 'PASE';			
			}
			else if ($p_csc->cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)')
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Applications Owning Database (DB Hosted on this Server, Owning App Is Not): PASE - %s", 
					$master_approver_found == true ? 'FYI' : 'APPROVER');
				$notify_type = $master_approver_found == true ? 'FYI' : 'APPROVER';
				$group_type = 'PASE';			
			}
			else
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "NO MATCH FOR: %s", $p_csc->cct_csc_group_name);
			}
					
			$got_contact = false;
			$cct_csc_oncall = '';
			$source = '';
			$override = 'N';
			$p_override = null;
			$app_acronym = $p_csc->cct_app_acronym;

			//
			// Rule 1: Use CSC Primary contact if it exists
			//
			if      ($this->foundInMNET($p_csc->cct_csc_userid_1))
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Using: CSC Primary contact");
				$cct_csc_oncall = $p_csc->cct_csc_userid_1;
				$source = 'CSC Primary';
				$got_contact = true;
			}
			else if ($this->foundInMNET($p_csc->cct_csc_userid_2))
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Using: CSC Backup1 contact");
				$cct_csc_oncall = $p_csc->cct_csc_userid_2;
				$source = 'CSC Backup1';
				$got_contact = true;			
			}
			else if ($this->foundInMNET($p_csc->cct_csc_userid_3))
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Using: CSC Backup2 contact");
				$cct_csc_oncall = $p_csc->cct_csc_userid_3;
				$source = 'CSC Backup2';
				$got_contact = true;
			}
			else if ($this->foundInMNET($p_csc->cct_csc_userid_4))
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Using: CSC Backup3 contact");
				$cct_csc_oncall = $p_csc->cct_csc_userid_4;
				$source = 'CSC Backup3';
				$got_contact = true;			
			}
			else if ($this->foundInMNET($p_csc->cct_csc_userid_5))
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Using: CSC Backup4 contact");
				$cct_csc_oncall = $p_csc->cct_csc_userid_5;
				$source = 'CSC Backup4';
				$got_contact = true;			
			}		
			//
			// Rule 2: Net Group primary oncall person (via. interface to NET-Tool) See table: cct6_oncall_overrides
			//
			else if (($p_override = $this->getOncallOverride($p_csc->cct_csc_netgroup)) != null)  // cct_csc_netgroup is the net-pin number
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Using: CCT Override Pin(%s)", $p_csc->cct_csc_netgroup);
				$cct_csc_oncall = trim($p_override->mnet_cuid);
				if ($this->foundInMnet($cct_csc_oncall))
				{
					$override = 'Y';
					$source = 'CCT Override Pin(' . $p_csc->cct_csc_netgroup . ')';
					$got_contact = true;				
				}		
			}
			else if ($this->foundInMNET($p_csc->cct_csc_oncall))	
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Using: CSC/NetTool-%s contact", $p_csc->cct_csc_netgroup);
				$cct_csc_oncall = $p_csc->cct_csc_oncall;
				$source = "CSC/NetTool-" . $p_csc->cct_csc_netgroup;
				$got_contact = true;				
			}
			//
			// Rule 3: Asset Center (cct6_computers)
			//
			else if (strlen($p_csc->cct_csc_oncall) == 0 && $group_type == 'OS' && strlen($this->cur_host->computer_os_group_contact) > 0)
			{
				if ($this->foundInMNET($this->cur_host->computer_os_group_contact))
				{
					$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Using: cct6_computers computer_os_group_contact", $this->cur_host->computer_os_group_contact);
					$cct_csc_oncall = $this->cur_host->computer_os_group_contact;
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
					$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Duplicate contact found: %s", $cct_csc_oncall);
					
					//
					// Find the record in this contact list
					//
					for ($p=$top_contact; $p!=null; $p=$p->next)
					{
						if ($p->contact_cuid == $cct_csc_oncall)
							break;
					}
					
					if ($p == null)
					{
						$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Unable to find (%s) in this top_contact list: %s", $cct_csc_oncall, $source);
						continue;
					}
					
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
				if (($m = $this->getMNET($cct_csc_oncall)) == null)
				{
					$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Unable to retrieve MNET record from array: getMNET(%s)", $cct_csc_oncall);
					continue;
				}
				
				//
				// Add it to the contact list
				//
				if ($top_contact == null)
				{
					$top_contact = $p_contact = new data_contacts();
				}
				else
				{
					$p_contact->next = new data_contacts();
					$p_contact = $p_contact->next;
				}
			
				$p_contact->contact_csc_banner = $this->fixBanner($p_csc->cct_csc_group_name); // VARCHAR2 (i.e. Application Support)
				$p_contact->contact_app_acronym = $p_csc->cct_app_acronym;                     // VARCHAR2 Application acronym (i.e. CCT)
				$p_contact->contact_group_type = $group_type;                                  // VARCHAR2 OS, PASE, DBA, OTHER
				$p_contact->contact_notify_type = $notify_type;                                // VARCHAR2 APPROVER, FYI
				$p_contact->contact_source = $source;                                          // VARCHAR2 CSC, CCT, On-Call	
				$p_contact->contact_override = $override;                                      // VARCHAR2 Override used? Y or N                          
				
				$p_contact->contact_cuid = $m->mnet_cuid;                                      // VARCHAR2 Contact CUID login name
				$p_contact->contact_last_name = $m->mnet_last_name;                            // VARCHAR2 Contact last name
				$p_contact->contact_first_name = $m->mnet_first_name;                          // VARCHAR2 Contact first name
				$p_contact->contact_nick_name = $m->mnet_nick_name;                            // VARCHAR2 Contact nick name
				$p_contact->contact_middle = $m->mnet_middle;                                  // VARCHAR2 Contact middle name
				$p_contact->contact_name = $m->mnet_name;                                      // VARCHAR2 Contact name
				$p_contact->contact_job_title = $m->mnet_job_title;                            // VARCHAR2 Contact Job Title
				$p_contact->contact_email = $m->mnet_email;                                    // VARCHAR2 Contact email address
				$p_contact->contact_work_phone = $m->mnet_work_phone;                          // VARCHAR2 Contact work phone number
				$p_contact->contact_pager = $m->mnet_pager;                                    // VARCHAR2 Contact pager number
				$p_contact->contact_street = $m->mnet_street;                                  // VARCHAR2 Contact street
				$p_contact->contact_city = $m->mnet_city;                                      // VARCHAR2 Contact City
				$p_contact->contact_state = $m->mnet_state;                                    // VARCHAR2 Contact State
				$p_contact->contact_rc = $m->mnet_rc;                                          // VARCHAR2 Contact RC
				$p_contact->contact_company = $m->mnet_company;                                // VARCHAR2 Contact company name
				$p_contact->contact_tier1 = $m->mnet_tier1;                                    // VARCHAR2 Contact tier1 support information
				$p_contact->contact_tier2 = $m->mnet_tier2;                                    // VARCHAR2 Contact tier2 support information
				$p_contact->contact_tier3 = $m->mnet_tier3;                                    // VARCHAR2 Contact tier3 support information
				$p_contact->contact_status = $m->mnet_status;                                  // VARCHAR2 Contact employee status
				$p_contact->contact_change_date = $m->mnet_change_date;                        // DATE     MNET information change date
				$p_contact->contact_ctl_cuid = $m->mnet_ctl_cuid;                              // VARCHAR2 Contact CTL sponsor CUID person
				$p_contact->contact_mgr_cuid = $m->mnet_mgr_cuid;                              // VARCHAR2 Contact Manager CUID person				
			} // if ($got_contact == true)		
		} // for ($p_csc=$top_csc; $p_csc!=null; $p_csc=$p_csc->next)
		
		//
		// Add subscribers to the list
		//
		$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Add subscribers to the list");
		
		if (($top_subscribers = $this->getSubscribers($hostname)) != null && strlen($cct_csc_oncall) > 0)
		{
			for ($p_subscriber=$top_subscribers; $p_subscriber!=null; $p_subscriber=$p_subscriber->next)
			{
				//
				// Combind the data for this individual into one record if a previous record for this person 
				// has already been added.
				//
				if (array_key_exists($cct_csc_oncall, $duplicate_contact))
				{
					$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Duplicate contact found: %s", $cct_csc_oncall);
					
					//
					// Find the record in this contact list
					//
					for ($p=$top_contact; $p!=null; $p=$p->next)
					{
						if ($p->contact_cuid == $cct_csc_oncall)
							break;
					}
					
					if ($p == null)
					{
						$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Unable to find %s in this top_contact list", $cct_csc_oncall);
						continue;
					}
					
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
					
					continue;
				}
				
				// Note that we have created a record in our contact link list for this individual
				$duplicate_contact[$cct_csc_oncall] = 'got it';
							
				//
				// Add it to the contact list
				//
				if ($top_contact == null)
				{
					$top_contact = $p_contact = new data_contacts();
				}
				else
				{
					$p_contact->next = new data_contacts();
					$p_contact = $p_contact->next;
				}
			
				$p_contact->contact_csc_banner = 'Subscriber: ' . $p_subscriber->notify_type;   // VARCHAR2 (i.e. Application Support)
				$p_contact->contact_app_acronym = 'NA';                                         // VARCHAR2 Application acronym (i.e. CCT)
				$p_contact->contact_group_type = $p_subscriber->group_type;                     // VARCHAR2 OS, PASE, DBA, OTHER
				$p_contact->contact_notify_type = $p_subscriber->notify_type;                   // VARCHAR2 APPROVER, FYI
				$p_contact->contact_source = 'CCT Subscriber';                                  // VARCHAR2 CSC, CCT, On-Call	
				$p_contact->contact_override = $override;                                       // VARCHAR2 Override used? Y or N                          
				
				$p_contact->contact_cuid = $p_subscriber->mnet_cuid;                            // VARCHAR2 Contact CUID login name
				$p_contact->contact_last_name = $p_subscriber->mnet_last_name;                  // VARCHAR2 Contact last name
				$p_contact->contact_first_name = $p_subscriber->mnet_first_name;                // VARCHAR2 Contact first name
				$p_contact->contact_nick_name = $p_subscriber->mnet_nick_name;                  // VARCHAR2 Contact nick name
				$p_contact->contact_middle = $p_subscriber->mnet_middle;                        // VARCHAR2 Contact middle name
			
				$p_contact->contact_name = $p_subscriber->mnet_name;                            // VARCHAR2 Contact name
				$p_contact->contact_job_title = $p_subscriber->mnet_job_title;                  // VARCHAR2 Contact Job Title
				$p_contact->contact_email = $p_subscriber->mnet_email;                          // VARCHAR2 Contact email address
				$p_contact->contact_work_phone = $p_subscriber->mnet_work_phone;                // VARCHAR2 Contact work phone number
				$p_contact->contact_pager = $p_subscriber->mnet_pager;                          // VARCHAR2 Contact pager number
				$p_contact->contact_street = $p_subscriber->mnet_street;                        // VARCHAR2 Contact street
				$p_contact->contact_city = $p_subscriber->mnet_city;                            // VARCHAR2 Contact City
				$p_contact->contact_state = $p_subscriber->mnet_state;                          // VARCHAR2 Contact State
				$p_contact->contact_rc = $p_subscriber->mnet_rc;                                // VARCHAR2 Contact RC
				$p_contact->contact_company = $p_subscriber->mnet_company;                      // VARCHAR2 Contact company name
				$p_contact->contact_tier1 = $p_subscriber->mnet_tier1;                          // VARCHAR2 Contact tier1 support information
				$p_contact->contact_tier2 = $p_subscriber->mnet_tier2;                          // VARCHAR2 Contact tier2 support information
				$p_contact->contact_tier3 = $p_subscriber->mnet_tier3;                          // VARCHAR2 Contact tier3 support information
				$p_contact->contact_status = $p_subscriber->mnet_status;                        // VARCHAR2 Contact employee status
				$p_contact->contact_change_date = $p_subscriber->mnet_change_date;              // DATE     MNET information change date
				$p_contact->contact_ctl_cuid = $p_subscriber->mnet_ctl_cuid;                    // VARCHAR2 Contact CTL sponsor CUID person
				$p_contact->contact_mgr_cuid = $p_subscriber->mnet_mgr_cuid;                    // VARCHAR2 Contact Manager CUID person							
			}
		}
		
		$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Setting flag pase_dba_approver");
		
		$pase_dba_approver = false;
		
		for ($p=$top_contact; $p!=null; $p=$p->next)
		{
			if ($p->contact_notify_type == 'APPROVER' && ($p->contact_group_type == 'PASE' || $p->contact_group_type == 'DBA'))
			{
				$pase_dba_approver = true;
				break;
			}
		}
		
		//
		// No contacts or pase_dba_approvers then add classification contact user if it exists.
		//
		$this->debug4(__FILE__, __FUNCTION__, __LINE__, "No contacts or pase_dba_approvers then add classification contact user if it exists.");
		
		if ($top_contact == null || $pase_dba_approver == false)
		{			
			// $this->  will point to the classification information
			if (strlen($this->ora->classification_cuid) == 0)
			{
				// Means we do not currently have a person assigned to this classification record. No contacts available.
				return false;
			}
			
			if ($top_contact == null)
			{
				$top_contact = $p_contact = new data_contacts();
			}
			else
			{
				$p_contact->next = new data_contacts();
				$p_contact = $p_contact->next;
			}
			
			$p_contact->contact_csc_banner = 'NA';                                              // VARCHAR2 (i.e. Application Support)
			$p_contact->contact_app_acronym = 'ALL Applications';                               // VARCHAR2 Application acronym (i.e. CCT)
			$p_contact->contact_group_type = 'PASE';                                             // VARCHAR2 OS, PASE, DBA, OTHER
			$p_contact->contact_notify_type = 'APPROVER';                                       // VARCHAR2 APPROVER, FYI
			$p_contact->contact_source = 'CCT Classification';                                  // VARCHAR2 CSC, CCT, On-Call	
			$p_contact->contact_override = 'N';                                                 // VARCHAR2 Override used? Y or N                          
				
			$p_contact->contact_cuid = $this->ora->classification_cuid;                         // VARCHAR2 Contact CUID login name
			$p_contact->contact_last_name = $this->ora->classification_last_name;               // VARCHAR2 Contact last name
			$p_contact->contact_first_name = $this->ora->classification_first_name;             // VARCHAR2 Contact first name
			$p_contact->contact_nick_name = $this->ora->classification_nick_name;               // VARCHAR2 Contact nick name
			$p_contact->contact_middle = $this->ora->classification_middle;                     // VARCHAR2 Contact middle name
			
			// $fullname = (strlen($this->ora->classification_nick_name) > 0 ? 
				// $this->ora->classification_nick_name : $this->ora->classification_first_name) . " " . $this->ora->classification_last_name;
			
			$p_contact->contact_name = $this->ora->classification_name;                         // VARCHAR2 Contact name
			$p_contact->contact_job_title = $this->ora->classification_job_title;               // VARCHAR2 Contact Job Title
			$p_contact->contact_email = $this->ora->classification_email;                       // VARCHAR2 Contact email address
			$p_contact->contact_work_phone = $this->ora->classification_work_phone;             // VARCHAR2 Contact work phone number
			$p_contact->contact_pager = $this->ora->classification_pager;                       // VARCHAR2 Contact pager number
			$p_contact->contact_street = $this->ora->classification_street;                     // VARCHAR2 Contact street
			$p_contact->contact_city = $this->ora->classification_city;                         // VARCHAR2 Contact City
			$p_contact->contact_state = $this->ora->classification_state;                       // VARCHAR2 Contact State
			$p_contact->contact_rc = $this->ora->classification_rc;                             // VARCHAR2 Contact RC
			$p_contact->contact_company = $this->ora->classification_company;                   // VARCHAR2 Contact company name
			$p_contact->contact_tier1 = $this->ora->classification_tier1;                       // VARCHAR2 Contact tier1 support information
			$p_contact->contact_tier2 = $this->ora->classification_tier2;                       // VARCHAR2 Contact tier2 support information
			$p_contact->contact_tier3 = $this->ora->classification_tier3;                       // VARCHAR2 Contact tier3 support information
			$p_contact->contact_status = $this->ora->classification_status;                     // VARCHAR2 Contact employee status
			$p_contact->contact_change_date = $this->ora->classification_change_date;           // DATE     MNET information change date
			$p_contact->contact_ctl_cuid = $this->ora->classification_ctl_cuid;                 // VARCHAR2 Contact CTL sponsor CUID person
			$p_contact->contact_mgr_cuid = $this->ora->classification_mgr_cuid;                 // VARCHAR2 Contact Manager CUID person				
		}
		
		//
		// If no approvals are required for this work then change notification type for all these contacts
		//
		$this->debug4(__FILE__, __FUNCTION__, __LINE__, "If no approvals are required for this work then change notification type for all these contacts");
		
		if ($this->ora->ticket_approvals_required == 'N')
		{
			for ($p=$top_contact; $p!=null; $p=$p->next)
			{
				$p->contact_notify_type = 'FYI';
			}
		}
			
		//
		// Retrieve the cct6_auto_approver lists for each user
		//
		if ($this->ora->sql("select * from cct6_auto_approve order by user_cuid") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$last_cuid = '';
		$auto_approve = array();
		$this_top = null; 
		$next_top = null;
		
		while ($this->ora->fetch())
		{
			if ($last_cuid != $this->ora->user_cuid)
			{
				if ($this_top != null)
				{
					$auto_approve[$last_cuid] = $this_top;
				}
				
				$this_top = new data_auto_approve();  // classes/data_auto_approve.php
				$next_top = $this_top;
				$last_cuid = $this->ora->user_cuid;
			}
			else
			{
				$next_top->next = new data_auto_approve();
				$next_top = $next_top->next;
			}
			
			$next_top->auto_approve_id = $this->ora->auto_approve_id;             // NUMBER   PRIMARY KEY - Unique record ID
			$next_top->auto_insert_date = $this->ora->auto_insert_date;           // DATE     Date recorded was created
			$next_top->auto_insert_cuid = $this->ora->auto_insert_cuid;           // VARCHAR2 CUID of person who created this record
			$next_top->auto_insert_name = $this->ora->auto_insert_name;           // VARCHAR2 Name of person who created this record
			$next_top->auto_update_date = $this->ora->auto_update_date;           // DATE     Date record was last updated
			$next_top->auto_update_cuid = $this->ora->auto_update_cuid;           // VARCHAR2 CUID of person who last updated this record
			$next_top->auto_update_name = $this->ora->auto_update_name;           // VARCHAR2 Name of person who last updated this record
			$next_top->classification = $this->ora->classification;               // VARCHAR2 Auto Approve when classification matches
			$next_top->computer_hostname = $this->ora->computer_hostname;         // VARCHAR2 Auto Approve when computer hostname matches
			$next_top->user_cuid = $this->ora->user_cuid;                         // VARCHAR2 CUID of person this auto approve record is for
			$next_top->user_name = $this->ora->user_name;                         // VARCHAR2 Name of person this auto approve record is for
			$next_top->user_email = $this->ora->user_email;                       // VARCHAR2 Email of person this auto approve record is for
			$next_top->user_company = $this->ora->user_company;                   // VARCHAR2 Company name of person this auto approver record is for
			$next_top->manager_cuid = $this->ora->manager_cuid;                   // VARCHAR2 Manager CUID of person this auto approve record is for
			$next_top->manager_name = $this->ora->manager_name;                   // VARCHAR2 Manager Name of person this auto approve record is for
			$next_top->manager_email = $this->ora->manager_email;                 // VARCHAR2 Manager Email address of person this auto approve record is for
			$next_top->manager_company = $this->ora->manager_company;             // VARCHAR2 Manager company name of person this auto approve record is for
		}		
		
		//
		// Preset all the cct6_contacts.contact_response_status and cct6_systems.system_work_status fields.
		// Apply any auto approve information
		//
		for ($p=$top_contact; $p!=null; $p=$p->next)
		{
			if ($p->contact_notify_type == 'APPROVER')
			{
				$p->contact_response_status = 'WAITING';
				
				if (array_key_exists($p->contact_cuid, $auto_approve))
				{
					for ($next_top=$auto_approve[$p->contact_cuid]; $next_top!=null; $next_top=$next_top->next)
					{
						if ($p->classification == $next_top->classification)
						{
							$p->contact_response_status = 'APPROVED';
							$e->AddEvent($system_id, 'auto-approve', "Classification Auto Approve for: " . $p->contact_name);
							break;
						}
						
						if ($hostname == $next_top->computer_hostname)
						{
							$p->contact_response_status = 'APPROVED';
							$e->AddEvent($system_id, 'auto-approve', "Hostname Auto Approve for: " . $p->contact_name);
						}
					} // for ($next_top=$auto_approve[$p->contact_cuid]; $next_top!=null; $next_top=$next_top->next)
				} // if (array_key_exists($p->contact_cuid, $auto_approve))
			}
			else
			{
				$p->contact_response_status = 'FYI';
			}
		}
		
		//
		// Now write the contacts to cct6_contacts
		//	
		$this->debug4(__FILE__, __FUNCTION__, __LINE__, "Writing contacts to cct6_contacts");
		
		for ($p=$top_contact; $p!=null; $p=$p->next)
		{
			$p->contact_id= $this->ora->next_seq('cct6_contactsseq');
			$p->system_id = $system_id;

			// Build the $insert SQL command
			$insert = "insert into cct6_contacts (" .
						"contact_id, system_id, contact_response_status, contact_insert_cuid, contact_insert_name, contact_csc_banner, " .
						"contact_app_acronym, contact_group_type, contact_notify_type, contact_source, contact_override, contact_cuid, " .
						"contact_last_name, contact_first_name, contact_nick_name, contact_middle, contact_name, contact_job_title, " .
						"contact_email, contact_work_phone, contact_pager, contact_street, contact_city, contact_state, " .
						"contact_rc, contact_company, contact_tier1, contact_tier2, contact_tier3, contact_status, " .
						"contact_change_date, contact_ctl_cuid, contact_mgr_cuid ) values ( ";
					
			$this->makeInsertINT(     $insert, $p->contact_id,                true);
			$this->makeInsertINT(     $insert, $p->system_id,                 true);
			$this->makeInsertCHAR(    $insert, $p->contact_response_status,   true);
			$this->makeInsertCHAR(    $insert, $p->contact_insert_cuid,       true);
			$this->makeInsertCHAR(    $insert, $p->contact_insert_name,       true);
			$this->makeInsertCHAR(    $insert, $p->contact_csc_banner,        true);
			$this->makeInsertCHAR(    $insert, $p->contact_app_acronym,       true);
			$this->makeInsertCHAR(    $insert, $p->contact_group_type,        true);
			$this->makeInsertCHAR(    $insert, $p->contact_notify_type,       true);
			$this->makeInsertCHAR(    $insert, $p->contact_source,            true);
			$this->makeInsertCHAR(    $insert, $p->contact_override,          true);
			$this->makeInsertCHAR(    $insert, $p->contact_cuid,              true);
			$this->makeInsertCHAR(    $insert, $p->contact_last_name,         true);
			$this->makeInsertCHAR(    $insert, $p->contact_first_name,        true);
			$this->makeInsertCHAR(    $insert, $p->contact_nick_name,         true);
			$this->makeInsertCHAR(    $insert, $p->contact_middle,            true);
			$this->makeInsertCHAR(    $insert, $p->contact_name,              true);
			$this->makeInsertCHAR(    $insert, $p->contact_job_title,         true);
			$this->makeInsertCHAR(    $insert, $p->contact_email,             true);
			$this->makeInsertCHAR(    $insert, $p->contact_work_phone,        true);
			$this->makeInsertCHAR(    $insert, $p->contact_pager,             true);
			$this->makeInsertCHAR(    $insert, $p->contact_street,            true);
			$this->makeInsertCHAR(    $insert, $p->contact_city,              true);
			$this->makeInsertCHAR(    $insert, $p->contact_state,             true);
			$this->makeInsertCHAR(    $insert, $p->contact_rc,                true);
			$this->makeInsertCHAR(    $insert, $p->contact_company,           true);
			$this->makeInsertCHAR(    $insert, $p->contact_tier1,             true);
			$this->makeInsertCHAR(    $insert, $p->contact_tier2,             true);
			$this->makeInsertCHAR(    $insert, $p->contact_tier3,             true);
			$this->makeInsertCHAR(    $insert, $p->contact_status,            true);
			$this->makeInsertDateTIME($insert, $p->contact_change_date,       true);
			$this->makeInsertCHAR(    $insert, $p->contact_ctl_cuid,          true);
			$this->makeInsertCHAR(    $insert, $p->contact_mgr_cuid,         false);
					
			$insert .= 	" )";
		
			if ($this->ora->sql($insert) == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
				$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
				return false;
			}		
		}
		
		//
		// Set the server status
		//
		$number_of_approvers = 0;
		$number_of_approvers_approved = 0;
		
		for ($p=$top_contact; $p!=null; $p=$p->next)
		{
			if ($p->notify_type == 'APPROVER')
			{
				$number_of_approvers++;
				
				if ($p->contact_response_status != 'APPROVERED')  // Auto Approve
				{
					$number_of_approvers_approved++;
				}
			}
		}
		
		$system_work_status = 'WAITING';  // Default system work status setting.
		
		if ($number_of_approvers > 0 && $number_of_approvers == $number_of_approvers_approved)
		{
			$system_work_status = 'READY';  // Got all approvals we need. Ready the work for this server.
		}
		
		$update = "update cct6_systems set ";
		
		$this->makeUpdateCHAR($update, "system_update_cuid", $this->user_cuid,     true);
		$this->makeUpdateCHAR($update, "system_update_name", $this->user_name,     true);
		$this->makeUpdateCHAR($update, "system_work_status", $system_work_status, false);
		
		$update .= " where system_id = " . $system_id;
		
		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		//
		// If the ticket has already been submitted then we want to submit notifications to these new contacts as well.
		//
		if ($this->ora->ticket_status == 'ACTIVE')
		{
			for ($p=$top_contact; $p!=null; $p=$p->next)
			{
				//
				// AddContacts($cm_ticket_no, $system_id, $lastid, $hostname)
				//
				// SpoolEmailTicket($cm_ticket_no, $email_template="", $email_subject="", $email_message="", $ticket_owner, $os_group, $pase_group, $dba_group)
				//
				if ($this->email->SpoolEmailTicketSystemContact($cm_ticket_no, $hostname, $p->contact_cuid, 'SUBMIT', "", "") == false)
				{
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->email->error);
					$this->error = $this->email->error;
					return false;	
				}		
			}
		}
		
		$this->debug4(__FILE__, __FUNCTION__, __LINE__, "commit and return true");
		$this->ora->commit();
		return true;	 	
	}

	//
	// 
	//
	/*! @fn loadOncallOverrides()
	 *  @brief Load up the oncall net-pin override list
	 *  @return true for success, false for failure
	 */		
	private function loadOncallOverrides()
	{
		$this->oncall_overrides = array();
		
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
					"m.mnet_job_title, " .
					"m.mnet_email as mnet_email, " .
					"m.mnet_work_phone, " .
					"m.mnet_pager, " .
					"m.mnet_street, " .
					"m.mnet_city, " .
					"m.mnet_state, " .
					"m.mnet_rc, " .
					"m.mnet_company as mnet_company, " .
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
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		while ($this->ora->fetch())
		{
			if (strlen($this->ora->netpin_no) == 0)
				continue;
				
			$p = new data_oncall_overrides();
			
			$p->netpin_no = $this->ora->netpin_no;                     // VARCHAR2 The unique netpin no that is to be override
			$p->mnet_cuid = $this->ora->mnet_cuid;                     // VARCHAR2 User ID
			$p->mnet_last_name = $this->ora->mnet_last_name;           // VARCHAR2 Last name
			$p->mnet_first_name = $this->ora->mnet_first_name;         // VARCHAR2 First name
			$p->mnet_nick_name = $this->ora->mnet_nick_name;           // VARCHAR2 Nick name
			$p->mnet_middle = $this->ora->mnet_middle;                 // VARCHAR2 Middle name
			$p->mnet_job_title = $this->ora->mnet_job_title;           // VARCHAR2 Job Title
			$p->mnet_email = $this->ora->mnet_email;                   // VARCHAR2 Email Address
			$p->mnet_work_phone = $this->ora->mnet_work_phone;         // VARCHAR2 Work phone number
			$p->mnet_pager = $this->ora->mnet_pager;                   // VARCHAR2 Pager number
			$p->mnet_street = $this->ora->mnet_street;                 // VARCHAR2 Street address
			$p->mnet_city = $this->ora->mnet_city;                     // VARCHAR2 City
			$p->mnet_state = $this->ora->mnet_state;                   // VARCHAR2 State
			$p->mnet_rc = $this->ora->mnet_rc;                         // VARCHAR2 QWEST RC Code
			$p->mnet_company = $this->ora->mnet_company;               // VARCHAR2 Employee Company name
			$p->mnet_tier1 = $this->ora->mnet_tier1;                   // VARCHAR2 CMP Support Tier1
			$p->mnet_tier2 = $this->ora->mnet_tier2;                   // VARCHAR2 CMP Support Tier2
			$p->mnet_tier3 = $this->ora->mnet_tier3;                   // VARCHAR2 CMP Support Tier3
			$p->mnet_status = $this->ora->mnet_status;                 // VARCHAR2 Employee Status
			$p->mnet_change_date = $this->ora->mnet_change_date;       // DATE     Date Record last updated
			$p->mnet_ctl_cuid = $this->ora->mnet_ctl_cuid;             // VARCHAR2 CMP Sponsor Manager User ID
			$p->mnet_mgr_cuid = $this->ora->mnet_mgr_cuid;             // VARCHAR2 Manager User ID			
			
			$this->oncall_overrides[$this->ora->netpin_no] = $p;
			
			//
			// If this MNET record is not in our mnet array then add it.
			//
			if (!array_key_exists($this->ora->mnet_cuid, $this->mnet))
			{
				$p = new data_mnet();
			
				$p->mnet_cuid = $this->ora->mnet_cuid;                   // VARCHAR2 User ID
				$p->mnet_last_name = $this->ora->mnet_last_name;         // VARCHAR2 Last name
				$p->mnet_first_name = $this->ora->mnet_first_name;       // VARCHAR2 First name
				$p->mnet_nick_name = $this->ora->mnet_nick_name;         // VARCHAR2 Nick name
				$p->mnet_middle = $this->ora->mnet_middle;               // VARCHAR2 Middle name
				$p->mnet_job_title = $this->ora->mnet_job_title;         // VARCHAR2 Job Title
				$p->mnet_email = $this->ora->mnet_email;                 // VARCHAR2 Email Address
				$p->mnet_work_phone = $this->ora->mnet_work_phone;       // VARCHAR2 Work phone number
				$p->mnet_pager = $this->ora->mnet_pager;                 // VARCHAR2 Pager number
				$p->mnet_street = $this->ora->mnet_street;               // VARCHAR2 Street address
				$p->mnet_city = $this->ora->mnet_city;                   // VARCHAR2 City
				$p->mnet_state = $this->ora->mnet_state;                 // VARCHAR2 State
				$p->mnet_rc = $this->ora->mnet_rc;                       // VARCHAR2 QWEST RC Code
				$p->mnet_company = $this->ora->mnet_company;             // VARCHAR2 Employee Company name
				$p->mnet_tier1 = $this->ora->mnet_tier1;                 // VARCHAR2 CMP Support Tier1
				$p->mnet_tier2 = $this->ora->mnet_tier2;                 // VARCHAR2 CMP Support Tier2
				$p->mnet_tier3 = $this->ora->mnet_tier3;                 // VARCHAR2 CMP Support Tier3
				$p->mnet_status = $this->ora->mnet_status;               // VARCHAR2 Employee Status
				$p->mnet_change_date = $this->ora->mnet_change_date;     // DATE     Date Record last updated
				$p->mnet_ctl_cuid = $this->ora->mnet_ctl_cuid;           // VARCHAR2 CMP Sponsor Manager User ID
				$p->mnet_mgr_cuid = $this->ora->mnet_mgr_cuid;           // VARCHAR2 Manager User ID	
			
				$this->mnet[$this->ora->mnet_cuid] = $p;
			}
		}
		
		return true;
	}
	
	/*! @fn getOncallOverride($net_pin)
	 *  @brief See if there is an oncall override for this net-pin
	 *  @param $net_pin number to for overrides
	 *  @return oncall_override data or null
	 */		
	private function getOncallOverride($net_pin)
	{
		if (array_key_exists($net_pin, $this->oncall_overrides))
			return $this->oncall_overrides[$net_pin];
			
		return null;
	}
		
	/*! @fn foundInMNET($cuid)
	 *  @brief The purpose of this function is to validate the cuid to make sure they are found in MNET.
	 *  @brief We also only want to retrieve the information once if valid so we don't have to make multiple calls to the database for the same cuid.
	 *  @param $cuid is the user ID that we are looking for in MNET
	 *  @return the MNET data for this CUID or null
	 */		
	private function foundInMNET($cuid)
	{
		// If we have a copy of this MNET record then no need to query the mnet table.
		if (array_key_exists($cuid, $this->mnet))
			return true;
			
		$query = "select " .
					"mnet_cuid, " .
					"mnet_last_name, " .
					"mnet_first_name, " .
					"mnet_nick_name, " .
					"mnet_middle, " .
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
					"mnet_cuid = '" . $cuid . "'";
					
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}
		
		if ($this->ora->fetch())
		{
			$p = new data_mnet();
			
			$p->mnet_cuid = $this->ora->mnet_cuid;                   // VARCHAR2 User ID
			$p->mnet_last_name = $this->ora->mnet_last_name;         // VARCHAR2 Last name
			$p->mnet_first_name = $this->ora->mnet_first_name;       // VARCHAR2 First name
			$p->mnet_nick_name = $this->ora->mnet_nick_name;         // VARCHAR2 Nick name
			$p->mnet_middle = $this->ora->mnet_middle;               // VARCHAR2 Middle name
			$p->mnet_job_title = $this->ora->mnet_job_title;         // VARCHAR2 Job Title
			$p->mnet_email = $this->ora->mnet_email;                 // VARCHAR2 Email Address
			$p->mnet_work_phone = $this->ora->mnet_work_phone;       // VARCHAR2 Work phone number
			$p->mnet_pager = $this->ora->mnet_pager;                 // VARCHAR2 Pager number
			$p->mnet_street = $this->ora->mnet_street;               // VARCHAR2 Street address
			$p->mnet_city = $this->ora->mnet_city;                   // VARCHAR2 City
			$p->mnet_state = $this->ora->mnet_state;                 // VARCHAR2 State
			$p->mnet_rc = $this->ora->mnet_rc;                       // VARCHAR2 QWEST RC Code
			$p->mnet_company = $this->ora->mnet_company;             // VARCHAR2 Employee Company name
			$p->mnet_tier1 = $this->ora->mnet_tier1;                 // VARCHAR2 CMP Support Tier1
			$p->mnet_tier2 = $this->ora->mnet_tier2;                 // VARCHAR2 CMP Support Tier2
			$p->mnet_tier3 = $this->ora->mnet_tier3;                 // VARCHAR2 CMP Support Tier3
			$p->mnet_status = $this->ora->mnet_status;               // VARCHAR2 Employee Status
			$p->mnet_change_date = $this->ora->mnet_change_date;     // DATE     Date Record last updated
			$p->mnet_ctl_cuid = $this->ora->mnet_ctl_cuid;           // VARCHAR2 CMP Sponsor Manager User ID
			$p->mnet_mgr_cuid = $this->ora->mnet_mgr_cuid;           // VARCHAR2 Manager User ID	
			
			$this->mnet[$this->ora->mnet_cuid] = $p;
			
			return true;	
		}
		
		return false;		
	}

	/*! @fn function getMNET($cuid)
	 *  @brief Used to see if we have already searched MNET for this CUID.
	 *  @param $cuid is the user ID that we are looking for in MNET
	 *  @return the MNET data for this CUID or null
	 */	
	private function getMNET($cuid)
	{
		if (array_key_exists($cuid, $this->mnet))
			return $this->mnet[$cuid];
			
		return null;
	}	

	/*! @fn function loadSubscribers()
	 *  @brief Load up the subscriber APPROVER and FYI lists
	 *  @return true for success, false for failure
	 */		
	private function loadSubscribers()
	{
		$this->subscribers = array();
		
		//
		// override_cuid's that are no longer in MNET will not be selected
		// which is what we want to do in this situation.
		//
		// Do not change the "order by s.hostname" or this routine will break!
		//
		$query = "select " .
					"s.subscriber_cuid           as insert_cuid, " .
					"s.insert_date               as insert_date, " .
					"s.notify_type               as notify_type, " .
					"s.group_type                as group_type, " .
					"s.computer_hostname         as hostname, " .
					"m.mnet_cuid, " .
					"m.mnet_last_name, " .
					"m.mnet_first_name, " .
					"m.mnet_nick_name, " .
					"m.mnet_middle, " .
					"m.mnet_name, " .
					"m.mnet_job_title, " .
					"m.mnet_email as mnet_email, " .
					"m.mnet_work_phone, " .
					"m.mnet_pager, " .
					"m.mnet_street, " .
					"m.mnet_city, " .
					"m.mnet_state, " .
					"m.mnet_rc, " .
					"m.mnet_company as mnet_company, " .
					"m.mnet_tier1, " .
					"m.mnet_tier2, " .
					"m.mnet_tier3, " .
					"m.mnet_status, " .
					"to_char(m.mnet_change_date, 'MM/DD/YYYY HH24:MI'), " .
					"m.mnet_ctl_cuid, " .
					"m.mnet_mgr_cuid " .
				"from " .
					"cct6_subscriber_lists s, " .
					"cct6_mnet m " .
				"where " .
					"m.mnet_cuid = s.subscriber_cuid " .
				"order by " .
					"s.computer_hostname";
		
		if ($this->ora->sql($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;		
		}
		
		$last_hostname = '';
		$next_link = null;
		
		while ($this->ora->fetch())
		{
			if (strlen($this->ora->mnet_cuid) == 0)
				continue;
				
			$p = new data_subscribers();
			
			$p->insert_cuid = $this->ora->insert_cuid;                 // VARCHAR2 CUID of person who created this record
			$p->insert_date = $this->ora->insert_date;                 // DATE     Date of person who created this record
			$p->notify_type = $this->ora->notify_type;                 // VARCHAR2 APPROVER or FYI
			$p->group_type = $this->ora->group_type;                   // VARCHAR2 OS, PASE, DBA, OTHER
			$p->hostname = $this->ora->hostname;                       // VARCHAR2 Computer hostname
			$p->mnet_cuid = $this->ora->mnet_cuid;                     // VARCHAR2 User ID
			$p->mnet_last_name = $this->ora->mnet_last_name;           // VARCHAR2 Last name
			$p->mnet_first_name = $this->ora->mnet_first_name;         // VARCHAR2 First name
			$p->mnet_nick_name = $this->ora->mnet_nick_name;           // VARCHAR2 Nick name
			$p->mnet_middle = $this->ora->mnet_middle;                 // VARCHAR2 Middle name
			$p->mnet_name = $this->ora->mnet_name;                     // VARCHAR2 Full name
			$p->mnet_job_title = $this->ora->mnet_job_title;           // VARCHAR2 Job Title
			$p->mnet_email = $this->ora->mnet_email;                   // VARCHAR2 Email Address
			$p->mnet_work_phone = $this->ora->mnet_work_phone;         // VARCHAR2 Work phone number
			$p->mnet_pager = $this->ora->mnet_pager;                   // VARCHAR2 Pager number
			$p->mnet_street = $this->ora->mnet_street;                 // VARCHAR2 Street address
			$p->mnet_city = $this->ora->mnet_city;                     // VARCHAR2 City
			$p->mnet_state = $this->ora->mnet_state;                   // VARCHAR2 State
			$p->mnet_rc = $this->ora->mnet_rc;                         // VARCHAR2 QWEST RC Code
			$p->mnet_company = $this->ora->mnet_company;               // VARCHAR2 Employee Company name
			$p->mnet_tier1 = $this->ora->mnet_tier1;                   // VARCHAR2 CMP Support Tier1
			$p->mnet_tier2 = $this->ora->mnet_tier2;                   // VARCHAR2 CMP Support Tier2
			$p->mnet_tier3 = $this->ora->mnet_tier3;                   // VARCHAR2 CMP Support Tier3
			$p->mnet_status = $this->ora->mnet_status;                 // VARCHAR2 Employee Status
			$p->mnet_change_date = $this->ora->mnet_change_date;       // DATE     Date Record last updated
			$p->mnet_ctl_cuid = $this->ora->mnet_ctl_cuid;             // VARCHAR2 CMP Sponsor Manager User ID
			$p->mnet_mgr_cuid = $this->ora->mnet_mgr_cuid;             // VARCHAR2 Manager User ID			
			
			//
			// We are creating a link list and assigning the top node in the list to an associative array where
			// the hostname is the key. There is a 0-N relationship of people that may be subscribers to a host.
			//
			// In order for this to work properly the SQL must sort on the hostname so we process all the
			// subscriber lists for a host together.
			//
			if ($last_hostname == $this->ora->hostname)
			{
				// The last_host matches this hostname
				// Add the link to the end of the link list and increment the next_link pointer
				$next_link->next = $p;
				$next_link = $next_link->next;
			}
			else
			{
				// The last_host does not match this hostname so it must be new in the list.
				$this->subscribers[$this->ora->hostname] = $p;
				$next_link = $p;
			}
			
			$last_hostname = $this->ora->hostname;
			
			//
			// If this MNET record is not in our mnet array then add it.
			//
			if (!array_key_exists($this->ora->mnet_cuid, $this->mnet))
			{
				$p = new data_mnet();
			
				$p->mnet_cuid = $this->ora->mnet_cuid;                   // VARCHAR2 User ID
				$p->mnet_last_name = $this->ora->mnet_last_name;         // VARCHAR2 Last name
				$p->mnet_first_name = $this->ora->mnet_first_name;       // VARCHAR2 First name
				$p->mnet_nick_name = $this->ora->mnet_nick_name;         // VARCHAR2 Nick name
				$p->mnet_middle = $this->ora->mnet_middle;               // VARCHAR2 Middle name
				$p->mnet_name = $this->ora->mnet_name;                   // VARCHAR2 Full name
				$p->mnet_job_title = $this->ora->mnet_job_title;         // VARCHAR2 Job Title
				$p->mnet_email = $this->ora->mnet_email;                 // VARCHAR2 Email Address
				$p->mnet_work_phone = $this->ora->mnet_work_phone;       // VARCHAR2 Work phone number
				$p->mnet_pager = $this->ora->mnet_pager;                 // VARCHAR2 Pager number
				$p->mnet_street = $this->ora->mnet_street;               // VARCHAR2 Street address
				$p->mnet_city = $this->ora->mnet_city;                   // VARCHAR2 City
				$p->mnet_state = $this->ora->mnet_state;                 // VARCHAR2 State
				$p->mnet_rc = $this->ora->mnet_rc;                       // VARCHAR2 QWEST RC Code
				$p->mnet_company = $this->ora->mnet_company;             // VARCHAR2 Employee Company name
				$p->mnet_tier1 = $this->ora->mnet_tier1;                 // VARCHAR2 CMP Support Tier1
				$p->mnet_tier2 = $this->ora->mnet_tier2;                 // VARCHAR2 CMP Support Tier2
				$p->mnet_tier3 = $this->ora->mnet_tier3;                 // VARCHAR2 CMP Support Tier3
				$p->mnet_status = $this->ora->mnet_status;               // VARCHAR2 Employee Status
				$p->mnet_change_date = $this->ora->mnet_change_date;     // DATE     Date Record last updated
				$p->mnet_ctl_cuid = $this->ora->mnet_ctl_cuid;           // VARCHAR2 CMP Sponsor Manager User ID
				$p->mnet_mgr_cuid = $this->ora->mnet_mgr_cuid;           // VARCHAR2 Manager User ID	
			
				$this->mnet[$this->ora->mnet_cuid] = $p;
			}			
		}
		
		return true;
	}	
		
	/*! @fn function getSubscribers($hostname)
	 *  @brief This function is used to return a subscriber list for a given hostname to include additional contacts
	 *  @brief to the notification list. Returns both APPROVER and FYI lists. You must sort out the entries you don't
	 *  @brief want by looking at the $obj->notify_type
	 *  @param $hostname name of the host containing the subscriber list we want
	 *  @return the host subscriber list or null
	 */		
	private function getSubscribers($hostname)
	{
		if (array_key_exists($hostname, $this->subscribers))
			return $this->subscribers[$hostname];
			
		return null;
	}
	
	/*! @fn function fixBanner($banner)
	 *  @brief Some CSC application banners have '! ' pre-appended to the string.
	 *  @brief The function identifies those banners and returns the correct banner name.
	 *  @param $banner is the CSC banner we want to fix.
	 *  @return fixed banner string.
	 */		
	private function fixBanner($banner)
	{
		return str_replace('! ', '', $banner);
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
