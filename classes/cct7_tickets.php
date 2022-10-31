<?php
/**
 * @package    CCT
 * @file       cct7_tickets.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       $Date:  $ GMT
 * @version    $Revision:  $
 *
 * $Log:  $
 *
 * $Source:  $
 *
 * @brief The module was designed to perform most of the functions on the Oracle Table: cct7_tickets
 *
 */

/**
 * @brief Public Methods
 *        initialize()
 *        getTicket($ticket_no)
 *        addTicket()
 *        activate($ticket_no)
 *        cancel($ticket_no
 *        deleteTicket($ticket_no)
 *        approveAllForCUID($ticket_no, $cuid)
 *        log($ticket_no, $event_message)
 *        putLogTicket($ticket_no, $event_type, $event_message)
 *        getLogTicket($ticket_no)
 *        updateRunInformation($ticket_no, $cm_start_date, $cm_end_date, $total_servers_not_scheduled, $servers_not_scheduled, $generator_runtime)
 *        sendmail($ticket_no, $subject_line, $email_cc, $email_bcc, $message_body)
 *        mail($to, $subject_line, $message_body, $headers)
 */

//
//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

/**
 * @class cct7_tickets
 *
 * @brief This class contains all the main processing routines for CCT Remedy tickets
 * @brief Used by programs: new_work_request_step4.php and work_requests.php
 * @brief Used by classes: cct7_systems.php
 * @brief Used by Ajax servers: server_edit_work_request.php and server_work_requests.php
 */
class cct7_tickets extends library
{
	var $data;
	var $ora;                     // Database connection object
	var $ora2;                    // Used in putLogTicket() function.
	var $error;                   // Error message when functions return false

	var $time_now;

	public $rows_affected;

    public $ticket_no;
    public $insert_date_num;
    public $insert_date_char;
	public $insert_date_char2;
    public $insert_cuid;
    public $insert_name;
    public $update_date_num;
    public $update_date_char;
	public $update_date_char2;
    public $update_cuid;
    public $update_name;
    public $status;
    public $status_date_num;
    public $status_date_char;
	public $status_date_char2;
    public $status_cuid;
    public $status_name;
    public $owner_cuid;
    public $owner_first_name;
    public $owner_name;
    public $owner_email;
    public $owner_job_title;
    public $manager_cuid;
    public $manager_first_name;
    public $manager_name;
    public $manager_email;
    public $manager_job_title;
    public $work_activity;
    public $approvals_required;
    public $reboot_required;
    public $respond_by_date_num;
    public $respond_by_date_char;
	public $respond_by_date_char2;
    public $email_reminder1_date_num;
    public $email_reminder1_date_char;
	public $email_reminder1_date_char2;
    public $email_reminder2_date_num;
    public $email_reminder2_date_char;
	public $email_reminder2_date_char2;
    public $email_reminder3_date_num;
    public $email_reminder3_date_char;
	public $email_reminder3_date_char2;
    public $schedule_start_date_num;
    public $schedule_start_date_char;
	public $schedule_start_date_char2;
    public $schedule_end_date_num;
    public $schedule_end_date_char;
	public $schedule_end_date_char2;
    public $work_description;
    public $work_implementation;
    public $work_backoff_plan;
    public $work_business_reason;
    public $work_user_impact;
    public $copy_to;
	public $total_servers_scheduled;
	public $total_servers_waiting;
	public $total_servers_approved;
	public $total_servers_rejected;
	public $total_servers_not_scheduled;
	public $servers_not_scheduled;
	public $generator_runtime;

	public $csc_banner1;
	public $csc_banner2;
	public $csc_banner3;
	public $csc_banner4;
	public $csc_banner5;
	public $csc_banner6;
	public $csc_banner7;
	public $csc_banner8;
	public $csc_banner9;
	public $csc_banner10;

	public $exclude_virtual_contacts;
	public $disable_scheduler;
	public $maintenance_window;

	public $change_date_num;
	public $change_date_char;
	public $change_date_char2;

	public $cm_ticket_no;
	public $cm_start_date_num;
	public $cm_start_date_char;
	public $cm_start_date_char2;
	public $cm_end_date_num;
	public $cm_end_date_char;
	public $cm_end_date_char2;
	public $cm_duration_computed;
	public $cm_ipl_boot;
	public $cm_status;
	public $cm_open_closed;
	public $cm_close_date_num;
	public $cm_close_date_char;
	public $cm_close_date_char2;
	public $cm_owner_first_name;
	public $cm_owner_last_name;
	public $cm_owner_cuid;
	public $cm_owner_group;

	public $note_to_clients;

	public $authorize;

	/**
	 * @fn     __construct()
	 *
	 * @brief  Class constructor - Create oracle object and setup some dynamic class variables
	 *
	 *  @param object $ora - Used to pass a oracle connection already in use. If this value is null we create a
	 *                       new oracle connection.
	 */
	public function __construct($ora=null)
	{
		date_default_timezone_set('America/Denver');

		if ($ora == null)
			$this->ora = new oracle();
		else
			$this->ora = $ora;

		$this->ora2 = null;

		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->wreq_osmaint = %s", $this->wreq_osmaint);
			
		//$this->ora = new oracle();

        $this->initialize();

		if (PHP_SAPI === 'cli')
		{
			$this->user_cuid          = 'cctadm';
			$this->user_first_name    = 'Application';
			$this->user_last_name     = 'CCT';
			$this->user_name          = 'CCT Application';
			$this->user_email         = 'gregparkin58@gmail.com';
            $this->user_job_title     = 'Software Engineer';
			$this->user_company       = 'CMP';
			$this->user_access_level  = 'admin';
            $this->user_timezone_name = 'America/Denver';

            $this->manager_cuid       = 'gparkin';
			$this->manager_first_name = 'Greg';
			$this->manager_last_name  = 'Parkin';
			$this->manager_name       = 'Greg Parkin';
			$this->manager_email      = 'gregparkin58@gmail.com';
            $this->manager_job_title  = 'Director';
			$this->manager_company    = 'CMP';
		}
		else
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
                $this->user_job_title     = $_SESSION['user_job_title'];
				$this->user_company       = $_SESSION['user_company'];
				$this->user_access_level  = $_SESSION['user_access_level'];
                $this->user_timezone_name = $_SESSION['local_timezone_name'];
				
				$this->manager_cuid       = $_SESSION['manager_cuid'];
				$this->manager_first_name = $_SESSION['manager_first_name'];
				$this->manager_last_name  = $_SESSION['manager_last_name'];
				$this->manager_name       = $_SESSION['manager_name'];
				$this->manager_email      = $_SESSION['manager_email'];
                $this->manager_job_title  = $_SESSION['manager_job_title'];
				$this->manager_company    = $_SESSION['manager_company'];

				$this->is_debug_on        = $_SESSION['is_debug_on'];
			}
			else
			{
				// ----------------------------------
				// $_SESSION cache has been blown away for this user. Did apache restart?
				//
				// Go rebuild user session cache information from cct7_mnet.
				//
				$_SESSION['REMOTE_USER'] = $_SERVER['REMOTE_USER']; // .htaccess authenticated LDAP userid

				$query = "select * from cct7_mnet where ";
				$query .= sprintf("lower(mnet_cuid) = lower('%s') or ", $_SERVER['REMOTE_USER']);
				$query .= sprintf("lower(mnet_workstation_login) = lower('%s')", $_SERVER['REMOTE_USER']);

				if ($this->ora->sql($query) == false)
				{
					$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
				}

				if ($this->ora->fetch())
				{
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Got cct7_mnet record for user %s", $_SERVER['REMOTE_USER']);

					//
					// Copy this user's MNET data into their $_SESSION data variables
					//
					$_SESSION['real_cuid'] = $this->ora->mnet_cuid;
					$_SESSION['real_name'] =
						(empty($this->ora->mnet_nick_name)
							? $this->ora->mnet_first_name
							: $this->ora->mnet_nick_name) . " " . $this->ora->mnet_last_name;

					$_SESSION['user_cuid']       = $this->ora->mnet_cuid;
					$_SESSION['user_first_name'] = $this->ora->mnet_first_name;
					$_SESSION['user_last_name']  = $this->ora->mnet_last_name;
					$_SESSION['user_name']       = $this->ora->mnet_name;
					$_SESSION['user_email']      = $this->ora->mnet_email;
					$_SESSION['user_company']    = $this->ora->mnet_company;
					$_SESSION['user_job_title']  = $this->ora->mnet_job_title;
					$_SESSION['user_work_phone'] = $this->ora->mnet_work_phone;

					$_SESSION['is_debug_on'] = "N";

					//
					// Does the users manager's cuid exist?
					//
					if (!empty($this->ora->mnet_mgr_cuid))
					{
						$this->debug1(__FILE__, __FUNCTION__, __LINE__,
									  "Getting cct7_mnet record for user %s managers cuid=%s",
									  $_SERVER['REMOTE_USER'], $this->ora->mnet_mgr_cuid);

						//
						// Retrieve the manager's cuid from MNET
						//
						if ($this->ora->sql("select * from cct7_mnet where mnet_cuid = '" . $this->ora->mnet_mgr_cuid . "'") == false)
						{
							$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
							$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
						}

						if ($this->ora->fetch())
						{
							$this->debug1(__FILE__, __FUNCTION__, __LINE__,
										  "Got cct7_mnet record for user %s manager's cuid %s",
										  $_SESSION['REMOTE_USER'], $this->ora->mnet_cuid);
							//
							// Add the users manager's MNET data to the $_SESSION data
							//
							$_SESSION['manager_cuid']       = $this->ora->mnet_cuid;
							$_SESSION['manager_first_name'] = $this->ora->mnet_first_name;
							$_SESSION['manager_last_name']  = $this->ora->mnet_last_name;
							$_SESSION['manager_name']       = $this->ora->mnet_name;
							$_SESSION['manager_email']      = $this->ora->mnet_email;
							$_SESSION['manager_company']    = $this->ora->mnet_company;
							$_SESSION['manager_job_title']  = $this->ora->mnet_job_title;
							$_SESSION['manager_work_phone'] = $this->ora->mnet_work_phone;
						}
					}
				}
				else
				{
					// No data returned from cct7_mnet for this user.

					$_SESSION['real_cuid']          = $_SERVER['REMOTE_USER'];
					$_SESSION['real_name']          = "CUID not found in cct7_mnet";

					$_SESSION['user_cuid']          = $_SERVER['REMOTE_USER'];
					$_SESSION['user_first_name']    = "";
					$_SESSION['user_last_name']     = "";
					$_SESSION['user_name']          = " CUID not found in cct7_mnet";
					$_SESSION['user_email']         = "";
					$_SESSION['user_company']       = "";
					$_SESSION['user_job_title']     = "";
					$_SESSION['user_work_phone']    = "";

					$_SESSION['manager_cuid']       = "";
					$_SESSION['manager_first_name'] = "";
					$_SESSION['manager_last_name']  = "";
					$_SESSION['manager_name']       = "";
					$_SESSION['manager_email']      = "";
					$_SESSION['manager_company']    = "";
					$_SESSION['manager_job_title']  = "";
					$_SESSION['manager_work_phone'] = "";

					$_SESSION['is_debug_on']        = "N";
				}

				$this->user_cuid          = $_SESSION['user_cuid'];
				$this->user_first_name    = $_SESSION['user_first_name'];
				$this->user_last_name     = $_SESSION['user_last_name'];
				$this->user_name          = $_SESSION['user_name'];
				$this->user_email         = $_SESSION['user_email'];
				$this->user_job_title     = $_SESSION['user_job_title'];
				$this->user_company       = $_SESSION['user_company'];
				$this->user_access_level  = $_SESSION['user_access_level'];
				$this->user_timezone_name = $_SESSION['local_timezone_name'];

				$this->manager_cuid       = $_SESSION['manager_cuid'];
				$this->manager_first_name = $_SESSION['manager_first_name'];
				$this->manager_last_name  = $_SESSION['manager_last_name'];
				$this->manager_name       = $_SESSION['manager_name'];
				$this->manager_email      = $_SESSION['manager_email'];
				$this->manager_job_title  = $_SESSION['manager_job_title'];
				$this->manager_company    = $_SESSION['manager_company'];

				$this->is_debug_on        = $_SESSION['is_debug_on'];
			}

			$this->debug_start('cct7_tickets.html');
		}

		$this->time_now = $this->now_to_gmt_utime(); // Used in $this->updateStatus($ticket_no)
	}

	/**
	 * @fn __destruct()
	 *
	 * @brief Destructor function called when no other references to this object can be found, or in any
	 *        order during the shutdown sequence. The destructor will be called even if script execution
	 *        is stopped using exit(). Calling exit() in a destructor will prevent the remaining shutdown
	 *        routines from executing.
	 * @brief Attempting to throw an exception from a destructor (called in the time of script termination)
	 *        causes a fatal error.
	 */	
	public function __destruct()
	{
	}

	/**
	 * @fn    __set($name, $value)
	 *
	 * @brief Setter function for $this->data
	 * @brief Example: $obj->first_name = 'Greg';
	 *
	 * @param string $name is the key in the associated $data array
	 * @param string $value is the value in the assoicated $data array for the identified key
	 */
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}

	/**
	 * @fn    __get($name)
	 *
	 * @brief Getter function for $this->data
	 * @brief echo $obj->first_name;
	 *
	 * @param string $name is the key in the associated $data array
	 *
	 * @return string or null
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->data))
		{
			return $this->data[$name];
		}

		return null;
	}

	/**
	 * @fn    __isset($name)
	 *
	 * @brief Determine if item ($name) exists in the $this->data array
	 * @brief var_dump(isset($obj->first_name));
	 *
	 * @param string $name is the key in the associated $data array
	 *
	 * @return bool - true or false
	 */
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}

	/**
	 * @fn    __unset($name)
	 *
	 * @brief Unset an item in $this->data assoicated by $name
	 * @brief unset($obj->name);
	 *
	 * @param string $name is the key in the associated $data array
	 */	
	public function __unset($name)
	{
		unset($this->data[$name]);
	}

	/**
	 * @fn initialize()
	 *
	 * @brief Initialize all the field variables for cct7_tickets
	 */
	public function initialize()
	{
		$this->rows_affected               = 0;  // Number of rows affected by update

		$this->ticket_no                   = ''; // |VARCHAR2|20|NOT NULL|PK: Unique Record ID
        $this->insert_date_num             = 0;  // |NUMBER|0||Date record was inserted (GMT)
        $this->insert_date_char            = ''; //
		$this->insert_date_char2           = ''; //
        $this->insert_cuid                 = ''; // |VARCHAR2|20||CUID of person who inserted the record
        $this->insert_name                 = ''; // |VARCHAR2|200||Name of person who inserted the record
        $this->update_date_num             = 0;  // |NUMBER|0||Date record was updated (GMT)
        $this->update_date_char            = ''; //
		$this->update_date_char2           = ''; //
        $this->update_cuid                 = ''; // |VARCHAR2|20||CUID of person who updated the record
        $this->update_name                 = ''; // |VARCHAR2|200||Name of person who updated the record
		$this->status                      = ''; // |VARCHAR2|20||DRAFT, ACTIVE, FROZEN, CANCELED
		$this->status_date_num             = 0;  // |NUMBER|0||Date when status last changed (GMT)
        $this->status_date_char            = ''; //
		$this->status_date_char2           = ''; //
		$this->status_cuid                 = ''; // |VARCHAR2|20||CUID of person who changed the status
		$this->status_name                 = ''; // |VARCHAR2|200||Name of person who changed the status
		$this->owner_cuid                  = ''; // |VARCHAR2|20||CUID of person who owns this work request.
        $this->owner_first_name            = ''; // |VARCHAR2|80||First name of person who created this record
		$this->owner_name                  = ''; // |VARCHAR2|200||Name of person who created this record. (Same as insert_name)
		$this->owner_email                 = ''; // |VARCHAR2|40||Email address of person who created this record
		$this->owner_job_title             = ''; // |VARCHAR2|80||Owners job title
		$this->manager_cuid                = ''; // |VARCHAR2|20||Owners managers CUID
		$this->manager_first_name          = ''; // |VARCHAR2|80||Owners managers first_name
		$this->manager_name                = ''; // |VARCHAR2|200||Owners managers full name
		$this->manager_email               = ''; // |VARCHAR2|40||Owners managers email address
        $this->manager_job_title           = ''; // |VARCHAR2(80||Owners managers job title
		$this->work_activity               = ''; // |VARCHAR2|80||Patching, GSD331, etc.
        $this->approvals_required          = ''; // |VARCHAR2|1||Y or N
		$this->reboot_required             = ''; // |VARCHAR2|1||Y or N
		$this->respond_by_date_num         = 0;  // |NUMBER|0||Respond by date (GMT)
        $this->respond_by_date_char        = '';
		$this->respond_by_date_char2       = '';
		$this->email_reminder1_date_num    = 0;  // |NUMBER|0||Escalation 1 date (GMT)
        $this->email_reminder1_date_char   = '';
		$this->email_reminder1_date_char2  = '';
		$this->email_reminder2_date_num    = 0;  // |NUMBER|0||Escalation 2 date (GMT)
        $this->email_reminder2_date_char   = '';
		$this->email_reminder2_date_char2  = '';
		$this->email_reminder3_date_num    = 0;  // |NUMBER|0||Escalation 3 date (GMT)
        $this->email_reminder3_date_char   = '';
		$this->email_reminder3_date_char2  = '';
        $this->schedule_start_date_num     = 0;  // |NUMBER|0||Schedule Start date (GMT)
        $this->schedule_start_date_char    = '';
		$this->schedule_start_date_char2   = '';
        $this->schedule_end_date_num       = 0;  // |NUMBER|0||Schedule End date (GMT)
        $this->schedule_end_date_char      = '';
		$this->schedule_end_date_char2     = '';
        $this->work_description            = ''; // |VARCHAR2|4000||Detail description of the work activity
        $this->work_implementation         = '';
		$this->work_backoff_plan           = ''; // |VARCHAR2|4000||Back out plans if there are problems
        $this->work_business_reason        = '';
		$this->work_user_impact            = ''; // |VARCHAR2|4000||What impacts to users while doing the change
		$this->copy_to                     = ''; // Copy Remedy ticket data to CCT ticket
		$this->total_servers_scheduled     = 0;  // Total scheduled servers
		$this->total_servers_waiting       = 0;  // Total servers waiting for a response from clients
		$this->total_servers_approved      = 0;  // Total servers approved by clients
		$this->total_servers_rejected      = 0;  // Total servers rejected by clients
		$this->total_servers_not_scheduled = 0;  // Total servers not scheduled
		$this->servers_not_scheduled       = ''; // List of servers not scheduled because they were not found in cct7_computers
		$this->generator_runtime           = ''; // Total minutes and seconds the server took to generate the schedule. (i.e. 3m, 23s)
		$this->csc_banner1                 = 'Y'; // CSC Banner: Applications or Databases Desiring Notification (Not Hosted on this Server)
		$this->csc_banner2                 = 'Y'; // csc_banner2|VARCHAR2|1||CSC Banner: Application Support
		$this->csc_banner3                 = 'Y'; // csc_banner3|VARCHAR2|1||CSC Banner: Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)
		$this->csc_banner4                 = 'Y'; // csc_banner4|VARCHAR2|1||CSC Banner: Infrastructure
		$this->csc_banner5                 = 'Y'; // csc_banner5|VARCHAR2|1||CSC Banner: MiddleWare Support
		$this->csc_banner6                 = 'Y'; // csc_banner6|VARCHAR2|1||CSC Banner: Database Support
		$this->csc_banner7                 = 'Y'; // csc_banner7|VARCHAR2|1||CSC Banner: Development Database Support
		$this->csc_banner8                 = 'Y'; // csc_banner8|VARCHAR2|1||CSC Banner: Operating System Support
		$this->csc_banner9                 = 'Y'; // csc_banner9|VARCHAR2|1||CSC Banner: Applications Owning Database (DB Hosted on this Server, Owning App Is Not)
		$this->csc_banner10                = 'Y'; // csc_banner10|VARCHAR2|1||CSC Banner: Development Support
		$this->exclude_virtual_contacts    = 'N'; // exclude_virtual_contacts|1||Exclude virtual server contacts
		$this->disable_scheduler           = 'N'; // disable_scheduler|1||Disable scheduler. Don't use start/end dates for servers.
		$this->maintenance_window          = 'weekly';
		$this->change_date_num             = 0;
		$this->change_date_char            = '';
		$this->change_date_char2           = '';
		$this->cm_ticket_no                = ''; // |VARCHAR2|20||Remedy CM Ticket Number
		$this->cm_start_date_num           = 0;
		$this->cm_start_date_char          = '';
		$this->cm_start_date_char2         = '';
		$this->cm_end_date_num             = 0;
		$this->cm_end_date_char            = '';
		$this->cm_end_date_char2           = '';
		$this->cm_duration_computed        = '';
		$this->cm_ipl_boot                 = '';
		$this->cm_status                   = '';
		$this->cm_open_closed              = '';
		$this->cm_close_date_num           = 0;
		$this->cm_close_date_char          = '';
		$this->cm_close_date_char2         = '';
		$this->cm_owner_first_name         = '';
		$this->cm_owner_last_name          = '';
		$this->cm_owner_cuid               = '';
		$this->cm_owner_group              = '';
		$this->note_to_clients             = '';

		$this->authorize = false;
	}


	/**
	 * @param object $t
	 *
	 * @return bool
	 */
	public function addTicketCCT6($t)
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_ticket_no = %s", $t->cm_ticket_no);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "os_maintwin = %s", $t->os_maintwin);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "approvals_required = %s", $t->approvals_required);

		//
		// Don't create the ticket if it already exists in cct6_tickets
		//
		$query = sprintf("select cm_ticket_no from cct6_tickets where cm_ticket_no = '%s'", $t->cm_ticket_no);

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin",
								   __FILE__, __LINE__);
			return false;
		}

		if ($this->ora->fetch() == true)
		{
			$this->error = "Ticket: " . $t->cm_ticket_no . " already exits in CCT";
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		//
		// Construct the SQL to insert the first part of the ticket
		//
		$insert = "insert into cct6_tickets (" .
			"cm_ticket_no, ticket_status, ticket_insert_date, ticket_insert_cuid, ticket_insert_name, " .
			"ticket_contact_cuid, ticket_contact_first_name, ticket_contact_last_name, ticket_contact_name, ticket_contact_email, ticket_contact_company, " .
			"ticket_manager_cuid, ticket_manager_first_name, ticket_manager_last_name, ticket_manager_name, ticket_manager_email, ticket_manager_company, " .
			"ticket_os_maintwin, ticket_approvals_required, ticket_use_os_maintwin, ticket_target_os, ticket_target_pase, ticket_target_dba, " .
			"ticket_target_dev_dba, ticket_override_master, ticket_include_child_servers, ticket_include_vmware_servers) values (";

		$this->makeInsertCHAR($insert,     $t->cm_ticket_no,                   true);  // cm_ticket_no (Primary Key)
		$this->makeInsertCHAR($insert,     $t->ticket_status,                  true);  // ticket_status
		$this->makeInsertDateTIME($insert, $t->ticket_insert_date,             true);  // ticket_insert_date
		$this->makeInsertCHAR($insert,     $t->ticket_insert_cuid,             true);  // ticket_insert_cuid
		$this->makeInsertCHAR($insert,     $t->ticket_insert_name,             true);  // ticket_insert_name
		$this->makeInsertCHAR($insert,     $t->ticket_contact_cuid,            true);  // ticket_contact_cuid
		$this->makeInsertCHAR($insert,     $t->ticket_contact_first_name,      true);  // ticket_contact_first_name
		$this->makeInsertCHAR($insert,     $t->ticket_contact_last_name,       true);  // ticket_contact_last_name
		$this->makeInsertCHAR($insert,     $t->ticket_contact_name,            true);  // ticket_contact_name
		$this->makeInsertCHAR($insert,     $t->ticket_contact_email,           true);  // ticket_contact_email
		$this->makeInsertCHAR($insert,     $t->ticket_contact_company,         true);  // ticket_contact_company
		$this->makeInsertCHAR($insert,     $t->ticket_manager_cuid,            true);  // ticket_manager_cuid
		$this->makeInsertCHAR($insert,     $t->ticket_manager_first_name,      true);  // ticket_manager_first_name
		$this->makeInsertCHAR($insert,     $t->ticket_manager_last_name,       true);  // ticket_manager_last_name
		$this->makeInsertCHAR($insert,     $t->ticket_manager_name,            true);  // ticket_manager_name
		$this->makeInsertCHAR($insert,     $t->ticket_manager_email,           true);  // ticket_manager_email
		$this->makeInsertCHAR($insert,     $t->manager_company,                true);  // ticket_ manager_company
		$this->makeInsertCHAR($insert,     $t->ticket_os_maintwin,             true);  // ticket_os_maintwin
		$this->makeInsertCHAR($insert,     $t->ticket_approvals_required,      true);  // ticket_approvals_required
		$this->makeInsertCHAR($insert,     $t->ticket_use_os_maintwin,         true);  // ticket_use_os_maintwin
		$this->makeInsertCHAR($insert,     $t->ticket_target_os,               true);  // ticket_target_os
		$this->makeInsertCHAR($insert,     $t->ticket_target_pase,             true);  // ticket_target_pase
		$this->makeInsertCHAR($insert,     $t->ticket_target_dba,              true);  // ticket_target_dba
		$this->makeInsertCHAR($insert,     $t->ticket_target_dev_dba,          true);  // ticket_target_dev_dba
		$this->makeInsertCHAR($insert,     $t->ticket_override_master,         true);  // ticket_override_master
		$this->makeInsertCHAR($insert,     $t->ticket_include_child_servers,   true);  // ticket_override_master
		$this->makeInsertCHAR($insert,     $t->ticket_include_vmware_servers,  false); // ticket_override_master

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
		$update = "update cct6_tickets set ";

		$this->makeUpdateDateTIME($update, "ticket_update_date",         $t->ticket_update_date,         true);
		$this->makeUpdateCHAR(    $update, "ticket_update_cuid",         $t->ticket_update_cuid,         true);
		$this->makeUpdateCHAR(    $update, "ticket_update_name",         $t->ticket_update_name,         true);
		$this->makeUpdateINT(     $update, "classification_id",          $t->classification_id,          true);
		$this->makeUpdateCHAR(    $update, "classification",             $t->classification,             true);
		$this->makeUpdateCHAR(    $update, "classification_comments",    $t->classification_comments,    true);
		$this->makeUpdateCHAR(    $update, "classification_cuid",        $t->classification_cuid,        true);
		$this->makeUpdateCHAR(    $update, "classification_last_name",   $t->classification_last_name,   true);
		$this->makeUpdateCHAR(    $update, "classification_first_name",  $t->classification_first_name,  true);
		$this->makeUpdateCHAR(    $update, "classification_nick_name",   $t->classification_nick_name,   true);
		$this->makeUpdateCHAR(    $update, "classification_middle",      $t->classification_middle,      true);
		$this->makeUpdateCHAR(    $update, "classification_name",        $t->classification_name,        true);
		$this->makeUpdateCHAR(    $update, "classification_job_title",   $t->classification_job_title,   true);
		$this->makeUpdateCHAR(    $update, "classification_email",       $t->classification_email,       true);
		$this->makeUpdateCHAR(    $update, "classification_work_phone",  $t->classification_work_phone,  true);
		$this->makeUpdateCHAR(    $update, "classification_pager",       $t->classification_pager,       true);
		$this->makeUpdateCHAR(    $update, "classification_street",      $t->classification_street,      true);
		$this->makeUpdateCHAR(    $update, "classification_city",        $t->classification_city,        true);
		$this->makeUpdateCHAR(    $update, "classification_state",       $t->classification_state,       true);
		$this->makeUpdateCHAR(    $update, "classification_rc",          $t->classification_rc,          true);
		$this->makeUpdateCHAR(    $update, "classification_company",     $t->classification_company,     true);
		$this->makeUpdateCHAR(    $update, "classification_tier1",       $t->classification_tier1,       true);
		$this->makeUpdateCHAR(    $update, "classification_tier2",       $t->classification_tier2,       true);
		$this->makeUpdateCHAR(    $update, "classification_tier3",       $t->classification_tier3,       true);
		$this->makeUpdateCHAR(    $update, "classification_status",      $t->classification_status,      true);
		$this->makeUpdateDateTIME($update, "classification_change_date", $t->classification_change_date, true);
		$this->makeUpdateCHAR(    $update, "classification_ctl_cuid",    $t->classification_ctl_cuid,    true);
		$this->makeUpdateCHAR(    $update, "classification_mgr_cuid",    $t->classification_mgr_cuid,    true);
		$this->makeUpdateDateTIME($update, "ticket_read_esc1_date",      $t->ticket_read_esc1_date,      true);
		$this->makeUpdateDateTIME($update, "ticket_read_esc2_date",      $t->ticket_read_esc2_date,      true);
		$this->makeUpdateDateTIME($update, "ticket_read_esc3_date",      $t->ticket_read_esc3_date,      true);
		$this->makeUpdateDateTIME($update, "ticket_resp_esc1_date",      $t->ticket_resp_esc1_date,      true);
		$this->makeUpdateDateTIME($update, "ticket_resp_esc2_date",      $t->ticket_resp_esc2_date,      true);
		$this->makeUpdateDateTIME($update, "ticket_resp_esc3_date",      $t->ticket_resp_esc3_date,      true);
		$this->makeUpdateCHAR(    $update, "ticket_submit_note",         $t->ticket_submit_note,         false);

		$update .= " where cm_ticket_no = '" . $t->cm_ticket_no . "'";

		$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $update);

		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}

		if ($this->updateRemedyCCT6($t) == false)
		{
			$this->ora->rollback();
			return false;
		}

		$this->ora->commit();
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "CREATE TICKET SUCCESS - returning true");

		return true;
	}

	/**
	 * @param object $t
	 *
	 * @return bool
	 */
	public function updateRemedyCCT6($t)
	{
		$update = "update cct6_tickets set ";
		$this->makeUpdateDateTIME($update, "ticket_update_date",             $t->ticket_update_date,             true);
		$this->makeUpdateCHAR($update,     "ticket_update_cuid",             $t->ticket_update_cuid,             true);
		$this->makeUpdateCHAR($update,     "ticket_update_name",             $t->ticket_update_name,             true);
		$this->makeUpdateCHAR($update,     "cm_assign_group",                $t->cm_assign_group,                true);
		$this->makeUpdateCHAR($update,     "cm_category",                    $t->cm_category,                    true);
		$this->makeUpdateCHAR($update,     "cm_category_type",               $t->cm_category_type,               true);
		$this->makeUpdateCHAR($update,     "cm_closed_by",                   $t->cm_closed_by,                   true);
		$this->makeUpdateCHAR($update,     "cm_close_code",                  $t->cm_close_code,                  true);
		$this->makeUpdateDateTIME($update, "cm_close_date",                  $t->cm_close_date,                  true);
		$this->makeUpdateCHAR($update,     "cm_component",                   $t->cm_component,                   true);
		$this->makeUpdateCHAR($update,     "cm_scheduling_flexibility",      $t->cm_scheduling_flexibility,      true);
		$this->makeUpdateDateTIME($update, "cm_end_date",                    $t->cm_end_date,                    true);
		$this->makeUpdateCHAR($update,     "cm_entered_by",                  $t->cm_entered_by,                  true);
		$this->makeUpdateCHAR($update,     "cm_exp_code",                    $t->cm_exp_code,                    true);
		$this->makeUpdateCHAR($update,     "cm_fix_level",                   $t->cm_fix_level,                   true);
		$this->makeUpdateCHAR($update,     "cm_implementor_first_last",      $t->cm_implementor_first_last,      true);
		$this->makeUpdateCHAR($update,     "cm_implementor_login",           $t->cm_implementor_login,           true);
		$this->makeUpdateCHAR($update,     "cm_ipl_boot",                    $t->cm_ipl_boot,                    true);
		$this->makeUpdateCHAR($update,     "cm_late",                        $t->cm_late,                        true);
		$this->makeUpdateCHAR($update,     "cm_parent_ir",                   $t->cm_parent_ir,                   true);
		$this->makeUpdateCHAR($update,     "cm_normal_release_session",      $t->cm_normal_release_session,      true);
		$this->makeUpdateDateTIME($update, "cm_create_date",                 $t->cm_create_date,                 true);
		$this->makeUpdateCHAR($update,     "cm_pager",                       $t->cm_pager,                       true);
		$this->makeUpdateCHAR($update,     "cm_phone",                       $t->cm_phone,                       true);
		$this->makeUpdateCHAR($update,     "cm_pin",                         $t->cm_pin,                         true);
		$this->makeUpdateCHAR($update,     "cm_plan_a_b",                    $t->cm_plan_a_b,                    true);
		$this->makeUpdateCHAR($update,     "cm_product",                     $t->cm_product,                     true);
		$this->makeUpdateCHAR($update,     "cm_product_type",                $t->cm_product_type,                true);
		$this->makeUpdateINT( $update,     "cm_risk",                        $t->cm_risk,                        true);
		$this->makeUpdateCHAR($update,     "cm_software_object",             $t->cm_software_object,             true);
		$this->makeUpdateDateTIME($update, "cm_start_date",                  $t->cm_start_date,                  true);
		$this->makeUpdateCHAR($update,     "cm_status",                      $t->cm_status,                      true);
		$this->makeUpdateCHAR($update,     "cm_tested",                      $t->cm_tested,                      true);
		$this->makeUpdateINT( $update,     "cm_duration",                    $t->cm_duration,                    true);
		$this->makeUpdateCHAR($update,     "cm_business_unit",               $t->cm_business_unit,               true);
		$this->makeUpdateCHAR($update,     "cm_duration_computed",           $t->cm_duration_computed,           true);
		$this->makeUpdateCHAR($update,     "cm_email",                       $t->cm_email,                       true);
		$this->makeUpdateCHAR($update,     "cm_company_name",                $t->cm_company_name,                true);
		$this->makeUpdateCHAR($update,     "cm_director",                    $t->cm_director,                    true);
		$this->makeUpdateCHAR($update,     "cm_manager",                     $t->cm_manager,                     true);
		$this->makeUpdateCHAR($update,     "cm_tested_itv",                  $t->cm_tested_itv,                  true);
		$this->makeUpdateCHAR($update,     "cm_tested_endtoend",             $t->cm_tested_endtoend,             true);
		$this->makeUpdateCHAR($update,     "cm_tested_development",          $t->cm_tested_development,          true);
		$this->makeUpdateCHAR($update,     "cm_tested_user",                 $t->cm_tested_user,                 true);
		$this->makeUpdateCHAR($update,     "cm_owner_name",                  $t->cm_owner_name,                  true);
		$this->makeUpdateCHAR($update,     "cm_owner_cuid",                  $t->cm_owner_cuid,                  true);
		$this->makeUpdateCHAR($update,     "cm_groupid",                     $t->cm_groupid,                     true);
		$this->makeUpdateCHAR($update,     "cm_temp",                        $t->cm_temp,                        true);
		$this->makeUpdateCHAR($update,     "cm_last_modified_by",            $t->cm_last_modified_by,            true);
		$this->makeUpdateDateTIME($update, "cm_last_modified",               $t->cm_last_modified,               true);
		$this->makeUpdateDateTIME($update, "cm_late_date",                   $t->cm_late_date,                   true);
		$this->makeUpdateINT( $update,     "cm_risk_integer",                $t->cm_risk_integer,                true);
		$this->makeUpdateCHAR($update,     "cm_owner_login_id",              $t->cm_owner_login_id,              true);
		$this->makeUpdateCHAR($update,     "cm_open_closed",                 $t->cm_open_closed,                 true);
		$this->makeUpdateCHAR($update,     "cm_user_timestamp",              $t->cm_user_timestamp,              true);
		$this->makeUpdateCHAR($update,     "cm_owner_first_name",            $t->cm_owner_first_name,            true);
		$this->makeUpdateCHAR($update,     "cm_owner_last_name",             $t->cm_owner_last_name,             true);
		$this->makeUpdateCHAR($update,     "cm_change_occurs",               $t->cm_change_occurs,               true);
		$this->makeUpdateCHAR($update,     "cm_lla_refresh",                 $t->cm_lla_refresh,                 true);
		$this->makeUpdateCHAR($update,     "cm_ims_cold_start",              $t->cm_ims_cold_start,              true);
		$this->makeUpdateCHAR($update,     "cm_release_level",               $t->cm_release_level,               true);
		$this->makeUpdateCHAR($update,     "cm_master_ir",                   $t->cm_master_ir,                   true);
		$this->makeUpdateCHAR($update,     "cm_owner_group",                 $t->cm_owner_group,                 true);
		$this->makeUpdateCHAR($update,     "cm_cab_approval_required",       $t->cm_cab_approval_required,       true);
		$this->makeUpdateCHAR($update,     "cm_change_executive_team_flag",  $t->cm_change_executive_team_flag,  true);
		$this->makeUpdateCHAR($update,     "cm_emergency_change",            $t->cm_emergency_change,            true);
		$this->makeUpdateINT( $update,     "cm_approval_status",             $t->cm_approval_status,             true);
		$this->makeUpdateCHAR($update,     "cm_component_type",              $t->cm_component_type,              true);
		$this->makeUpdateCHAR($update,     "cm_desc_short",                  $t->cm_desc_short,                  true);
		$this->makeUpdateCHAR($update,     "cm_last_status_change_by",       $t->cm_last_status_change_by,       true);
		$this->makeUpdateDateTIME($update, "cm_last_status_change_time",     $t->cm_last_status_change_time,     true);
		$this->makeUpdateCHAR($update,     "cm_previous_status",             $t->cm_previous_status,             true);
		$this->makeUpdateCHAR($update,     "cm_component_id",                $t->cm_component_id,                true);
		$this->makeUpdateCHAR($update,     "cm_test_tool",                   $t->cm_test_tool,                   true);
		$this->makeUpdateCHAR($update,     "cm_tested_orl",                  $t->cm_tested_orl,                  true);
		$this->makeUpdateCHAR($update,     "cm_featured_project",            $t->cm_featured_project,            true);
		$this->makeUpdateCHAR($update,     "cm_featured_proj_name",          $t->cm_featured_proj_name,          true);
		$this->makeUpdateCHAR($update,     "cm_tmpmainplatform",             $t->cm_tmpmainplatform,             true);
		$this->makeUpdateCHAR($update,     "cm_tmpblockmessage",             $t->cm_tmpblockmessage,             true);
		$this->makeUpdateCHAR($update,     "cm_guid",                        $t->cm_guid,                        true);
		$this->makeUpdateCHAR($update,     "cm_platform",                    $t->cm_platform,                    true);
		$this->makeUpdateCHAR($update,     "cm_cllicodes",                   $t->cm_cllicodes,                   true);
		$this->makeUpdateCHAR($update,     "cm_processor_name",              $t->cm_processor_name,              true);
		$this->makeUpdateCHAR($update,     "cm_system_name",                 $t->cm_system_name,                 true);
		$this->makeUpdateCHAR($update,     "cm_city",                        $t->cm_city,                        true);
		$this->makeUpdateCHAR($update,     "cm_state",                       $t->cm_state,                       true);
		$this->makeUpdateCHAR($update,     "cm_tmpdesc",                     $t->cm_tmpdesc,                     true);
		$this->makeUpdateDateTIME($update, "cm_turn_overdate",               $t->cm_turn_overdate,               true);
		$this->makeUpdateCHAR($update,     "cm_assign_group2",               $t->cm_assign_group2,               true);
		$this->makeUpdateCHAR($update,     "cm_assign_group3",               $t->cm_assign_group3,               true);
		$this->makeUpdateCHAR($update,     "cm_implementor_name2",           $t->cm_implementor_name2,           true);
		$this->makeUpdateCHAR($update,     "cm_implementor_name3",           $t->cm_implementor_name3,           true);
		$this->makeUpdateCHAR($update,     "cm_groupid2",                    $t->cm_groupid2,                    true);
		$this->makeUpdateCHAR($update,     "cm_groupid3",                    $t->cm_groupid3,                    true);
		$this->makeUpdateCHAR($update,     "cm_template",                    $t->cm_template,                    true);
		$this->makeUpdateCHAR($update,     "cm_hd_outage_ticket_number",     $t->cm_hd_outage_ticket_number,     true);
		$this->makeUpdateCHAR($update,     "cm_root_cause_owner",            $t->cm_root_cause_owner,            true);
		$this->makeUpdateCHAR($update,     "cm_control_count",               $t->cm_control_count,               false);

		$update .= " where cm_ticket_no = '" . $t->cm_ticket_no . "'";

		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}

		$update = "update cct6_tickets set ";
		$this->makeUpdateDateTIME($update, "ticket_update_date",         $t->ticket_update_date, true);
		$this->makeUpdateCHAR($update, "ticket_update_cuid",             $t->ticket_update_cuid,    true);
		$this->makeUpdateCHAR($update, "ticket_update_name",             $t->ticket_update_name,    true);
		$this->makeUpdateCHAR($update, "cm_impact",                      $t->cm_impact,       true);  // 2050
		$this->makeUpdateCHAR($update, "cm_description",                 $t->cm_description, false);  // 2048

		$update .= " where cm_ticket_no = '" . $t->cm_ticket_no . "'";

		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}

		$update = "update cct6_tickets set ";
		$this->makeUpdateDateTIME($update, "ticket_update_date",         $t->ticket_update_date, true);
		$this->makeUpdateCHAR($update, "ticket_update_cuid",             $t->ticket_update_cuid,                    true);
		$this->makeUpdateCHAR($update, "ticket_update_name",             $t->ticket_update_name,                    true);
		$this->makeUpdateCHAR($update, "cm_backoff_plan",                $t->cm_backoff_plan,                 true);  // 2048
		$this->makeUpdateCHAR($update, "cm_implementation_instructions", $t->cm_implementation_instructions, false);  // 2048

		$update .= " where cm_ticket_no = '" . $t->cm_ticket_no . "'";

		if ($this->ora->sql($update) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
		}

		$update = "update cct6_tickets set ";
		$this->makeUpdateDateTIME($update, "ticket_update_date",         $t->ticket_update_date, true);
		$this->makeUpdateCHAR($update, "ticket_update_cuid",             $t->ticket_update_cuid,        true);
		$this->makeUpdateCHAR($update, "ticket_update_name",             $t->ticket_update_name,        true);
		$this->makeUpdateCHAR($update, "cm_business_reason",             $t->cm_business_reason, false);  // 2048

		$update .= " where cm_ticket_no = '" . $t->cm_ticket_no . "'";

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

	/**
	 * @fn    owner()
	 *
	 * @brief Determine if this user is the owner of this ticket or is a member of a NET Group where
	 *        another member is an owner of the ticket.
	 *
	 * @return bool True means the user is authorized to perform higher functions on this ticket.
	 */
	public function owner()
	{
		$query  = "select ";
		$query .= "  ticket_no, ";
		$query .= "  owner_cuid ";
		$query .= "from ";
		$query .= "  cct7_tickets t, ";
		$query .= "  (select distinct ";
		$query .= "    n1.user_cuid as user_cuid ";
		$query .= "  from ";
		$query .= "    cct7_netpin_to_cuid n1, ";
		$query .= "    (select net_pin_no from cct7_netpin_to_cuid where user_cuid = '" . $this->user_cuid . "') n2 ";
		$query .= "  where ";
		$query .= "    n1.net_pin_no = n2.net_pin_no ";
		$query .= "  order by ";
		$query .= "    n1.user_cuid) n ";
		$query .= "where ";
		$query .= "  t.owner_cuid = n.user_cuid and ";
		$query .= "  t.ticket_no = '" . $this->ticket_no . "'";

		$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		if ($this->ora->fetch())
		{
			$this->authorize = true;
			return true;
		}

		$this->authorize = false;
		return false;
	}

	/**
	 * @fn     getTicket($ticket_no)
	 *
	 * @brief  Retrieve cct7_ticket record for a given $ticket_no record number.
	 *
	 * @param  string $ticket_no is a unique record ID in cct7_tickets
	 *
	 * @return bool - true or false, where true is success
	 */	
	public function getTicket($ticket_no)
	{
		$ticket_no = strtoupper($ticket_no);

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s", $ticket_no);

		$this->initialize();  // Initialize storage variables.

		$rc = $this->ora
            ->select()
            ->column('ticket_no')
			->column('insert_date')
            ->column('insert_cuid')
            ->column('insert_name')
            ->column('update_date')
            ->column('update_cuid')
            ->column('update_name')
            ->column('status')
            ->column('status_date')
            ->column('status_cuid')
            ->column('status_name')
            ->column('owner_cuid')
            ->column('owner_first_name')
            ->column('owner_name')
            ->column('owner_email')
            ->column('owner_job_title')
            ->column('manager_cuid')
            ->column('manager_first_name')
            ->column('manager_name')
            ->column('manager_email')
            ->column('manager_job_title')
            ->column('work_activity')
            ->column('approvals_required')
            ->column('reboot_required')
            ->column('respond_by_date')
            ->column('email_reminder1_date')
            ->column('email_reminder2_date')
            ->column('email_reminder3_date')
			->column('schedule_start_date')
			->column('schedule_end_date')
            ->column('work_description')
			->column('work_implementation')
            ->column('work_backoff_plan')
			->column('work_business_reason')
            ->column('work_user_impact')
			->column('total_servers_scheduled')
			->column('total_servers_waiting')
			->column('total_servers_approved')
			->column('total_servers_rejected')
			->column('total_servers_not_scheduled')
			->column('servers_not_scheduled')
			->column('generator_runtime')
			->column('csc_banner1')
			->column('csc_banner2')
			->column('csc_banner3')
			->column('csc_banner4')
			->column('csc_banner5')
			->column('csc_banner6')
			->column('csc_banner7')
			->column('csc_banner8')
			->column('csc_banner9')
			->column('csc_banner10')
			->column('exclude_virtual_contacts')
			->column('disable_scheduler')
			->column('maintenance_window')
			->column('change_date')
			->column('cm_ticket_no')
			->column('cm_start_date')
			->column('cm_end_date')
			->column('cm_duration_computed')
			->column('cm_ipl_boot')
			->column('cm_status')
			->column('cm_open_closed')
			->column('cm_close_date')
			->column('cm_owner_first_name')
			->column('cm_owner_last_name')
			->column('cm_owner_cuid')
			->column('cm_owner_group')
			->column('note_to_clients')
            ->from('cct7_tickets')
            ->where('char', 'ticket_no', '=', $ticket_no)
			->where_or('char', 'cm_ticket_no', '=', $ticket_no)
            ->execute();

		if ($rc == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s", __FILE__, __FUNCTION__, __LINE__, $this->ora->dbErrMsg);
            return false;
		}

		if ($this->ora->fetch() == false)
		{
            $this->error = sprintf("Ticket: %s not available!", $ticket_no);
            return false;
		}

		$mmddyyyy_hhmm_tz = 'm/d/Y H:i T';
        $mmddyyyy_tz      = 'm/d/Y T';
		$mmddyyyy_hhmm    = 'm/d/Y H:i';
		$mmddyyyy         = 'm/d/Y';

        $this->ticket_no                   = $this->ora->ticket_no;

        $this->insert_date_num             = $this->ora->insert_date;

        if ($this->ora->insert_date == 0)
		{
			$this->insert_date_char            = "";
			$this->insert_date_char2           = "";
		}
		else
		{
			$this->insert_date_char            =
				$this->gmt_to_format(
					$this->ora->insert_date,
					$mmddyyyy_hhmm_tz,
					$this->user_timezone_name);

			$this->insert_date_char2           =
				$this->gmt_to_format(
					$this->ora->insert_date,
					$mmddyyyy_hhmm,
					'America/Denver');
		}

        $this->insert_cuid                 = $this->ora->insert_cuid;
        $this->insert_name                 = $this->ora->insert_name;

        $this->update_date_num             = $this->ora->update_date;

        if ($this->ora->update_date == 0)
		{
			$this->update_date_char            = "";
			$this->update_date_char2           = "";
		}
		else
		{
			$this->update_date_char            =
				$this->gmt_to_format(
					$this->ora->update_date,
					$mmddyyyy_hhmm_tz,
					$this->user_timezone_name);

			$this->update_date_char2           =
				$this->gmt_to_format(
					$this->ora->update_date,
					$mmddyyyy_hhmm,
					'America/Denver');
		}

        $this->update_cuid                 = $this->ora->update_cuid;
        $this->update_name                 = $this->ora->update_name;
        $this->status                      = $this->ora->status;

        $this->status_date_num             = $this->ora->status_date;

        if ($this->ora->status_date == 0)
		{
			$this->status_date_char            = "";
			$this->status_date_char2           = "";
		}
		else
		{
			$this->status_date_char            =
				$this->gmt_to_format(
					$this->ora->status_date,
					$mmddyyyy_hhmm_tz,
					$this->user_timezone_name);

			$this->status_date_char2           =
				$this->gmt_to_format(
					$this->ora->status_date,
					$mmddyyyy_hhmm,
					'America/Denver');
		}

        $this->status_cuid                 = $this->ora->status_cuid;
        $this->status_name                 = $this->ora->status_name;
        $this->owner_cuid                  = $this->ora->owner_cuid;
        $this->owner_first_name            = $this->ora->owner_first_name;
        $this->owner_name                  = $this->ora->owner_name;
        $this->owner_email                 = $this->ora->owner_email;
        $this->owner_job_title             = $this->ora->owner_job_title;
        $this->manager_cuid                = $this->ora->manager_cuid;
        $this->manager_first_name          = $this->ora->manager_first_name;
        $this->manager_name                = $this->ora->manager_name;
        $this->manager_email               = $this->ora->manager_email;
        $this->manager_job_title           = $this->ora->manager_job_title;
        $this->work_activity               = $this->ora->work_activity;
        $this->approvals_required          = $this->ora->approvals_required;
        $this->reboot_required             = $this->ora->reboot_required;
        $this->respond_by_date_num         = $this->ora->respond_by_date;

        if ($this->ora->respond_by_date == 0)
		{
			$this->respond_by_date_char        = "";
			$this->respond_by_date_char2       = "";
		}
		else
		{
			$this->respond_by_date_char        =
				$this->gmt_to_format(
					$this->ora->respond_by_date,
					$mmddyyyy_tz,
					$this->user_timezone_name);

			$this->respond_by_date_char2       =
				$this->gmt_to_format(
					$this->ora->respond_by_date,
					$mmddyyyy,
					'America/Denver');
		}

        $this->email_reminder1_date_num    = $this->ora->email_reminder1_date;

        $this->email_reminder1_date_char   =
			$this->gmt_to_format(
				$this->ora->email_reminder1_date,
				$mmddyyyy_tz,
				$this->user_timezone_name);

		$this->email_reminder1_date_char2  =
			$this->gmt_to_format(
				$this->ora->email_reminder1_date,
				$mmddyyyy,
				'America/Denver');


        $this->email_reminder2_date_num    = $this->ora->email_reminder2_date;

        $this->email_reminder2_date_char   =
			$this->gmt_to_format(
				$this->ora->email_reminder2_date,
				$mmddyyyy_tz,
				$this->user_timezone_name);

		$this->email_reminder2_date_char2  =
			$this->gmt_to_format(
				$this->ora->email_reminder2_date,
				$mmddyyyy,
				'America/Denver');


        $this->email_reminder3_date_num    = $this->ora->email_reminder3_date;

        $this->email_reminder3_date_char   =
			$this->gmt_to_format(
				$this->ora->email_reminder3_date,
				$mmddyyyy_tz,
				$this->user_timezone_name);

		$this->email_reminder3_date_char2  =
			$this->gmt_to_format(
				$this->ora->email_reminder3_date,
				$mmddyyyy,
				'America/Denver');


		$this->schedule_start_date_num     = $this->ora->schedule_start_date;

		if ($this->ora->schedule_start_date == 0)
		{
			$this->schedule_start_date_char    = "";
			$this->schedule_start_date_char2   = "";
		}
		else
		{
			$this->schedule_start_date_char    =
				$this->gmt_to_format(
					$this->ora->schedule_start_date,
					$mmddyyyy_hhmm_tz,
					$this->user_timezone_name);

			$this->schedule_start_date_char2   =
				$this->gmt_to_format(
					$this->ora->schedule_start_date,
					$mmddyyyy_hhmm,
					'America/Denver');
		}

		$this->schedule_end_date_num       = $this->ora->schedule_end_date;

		if ($this->ora->schedule_end_date == 0)
		{
			$this->schedule_end_date_char      = "";
			$this->schedule_end_date_char2     = "";
		}
		else
		{
			$this->schedule_end_date_char      =
				$this->gmt_to_format(
					$this->ora->schedule_end_date,
					$mmddyyyy_hhmm_tz,
					$this->user_timezone_name);

			$this->schedule_end_date_char2     =
				$this->gmt_to_format(
					$this->ora->schedule_end_date,
					$mmddyyyy_hhmm,
					'America/Denver');
		}

        $this->work_description            = $this->ora->work_description;
		$this->work_implementation         = $this->ora->work_implementation;
        $this->work_backoff_plan           = $this->ora->work_backoff_plan;
		$this->work_business_reason        = $this->ora->work_business_reason;
        $this->work_user_impact            = $this->ora->work_user_impact;

		$this->total_servers_scheduled     = $this->ora->total_servers_scheduled;
		$this->total_servers_waiting       = $this->ora->total_servers_waiting;
		$this->total_servers_approved      = $this->ora->total_servers_approved;
		$this->total_servers_rejected      = $this->ora->total_servers_rejected;
		$this->total_servers_not_scheduled = $this->ora->total_servers_not_scheduled;
		$this->servers_not_scheduled       = $this->ora->servers_not_scheduled;
		$this->generator_runtime           = $this->ora->generator_runtime;
		$this->csc_banner1                 = $this->ora->csc_banner1;
		$this->csc_banner2                 = $this->ora->csc_banner2;
		$this->csc_banner3                 = $this->ora->csc_banner3;
		$this->csc_banner4                 = $this->ora->csc_banner4;
		$this->csc_banner5                 = $this->ora->csc_banner5;
		$this->csc_banner6                 = $this->ora->csc_banner6;
		$this->csc_banner7                 = $this->ora->csc_banner7;
		$this->csc_banner8                 = $this->ora->csc_banner8;
		$this->csc_banner9                 = $this->ora->csc_banner9;
		$this->csc_banner10                = $this->ora->csc_banner10;
		$this->exclude_virtual_contacts    = $this->ora->exclude_virtual_contacts;
		$this->disable_scheduler           = $this->ora->disable_scheduler;
		$this->maintenance_window          = $this->ora->maintenance_window;

		$this->change_date_num             = $this->ora->change_date;

		if ($this->ora->change_date == 0)
		{
			$this->change_date_char        = "";
			$this->change_date_char2       = "";
		}
		else
		{
			$this->change_date_char        =
				$this->gmt_to_format(
					$this->ora->change_date,
					$mmddyyyy_hhmm_tz,
					$this->user_timezone_name);

			$this->change_date_char2       =
				$this->gmt_to_format(
					$this->ora->change_date,
					$mmddyyyy_hhmm,
					'America/Denver');
		}

		$this->cm_ticket_no                = $this->ora->cm_ticket_no;

		$this->cm_start_date_num           = $this->ora->cm_start_date;

		if ($this->ora->cm_start_date == 0)
		{
			$this->cm_start_date_char      = "";
			$this->cm_start_date_char2     = "";
		}
		else
		{
			$this->change_date = $this->ora->change_date;

			$this->change_date_char        =
				$this->gmt_to_format(
					$this->ora->change_date,
					$mmddyyyy_hhmm_tz,
					$this->user_timezone_name);

			$this->change_date_char2       =
				$this->gmt_to_format(
					$this->ora->change_date,
					$mmddyyyy_hhmm,
					'America/Denver');
		}

		$this->cm_end_date_num             = $this->ora->cm_end_date;

		if ($this->ora->cm_end_date == 0)
		{
			$this->cm_end_date_char        = "";
			$this->cm_end_date_char2       = "";
		}
		else
		{
			$this->cm_end_date_char        =
				$this->gmt_to_format(
					$this->ora->change_date,
					$mmddyyyy_hhmm_tz,
					$this->user_timezone_name);

			$this->cm_end_date_char2       =
				$this->gmt_to_format(
					$this->ora->change_date,
					$mmddyyyy_hhmm,
					'America/Denver');
		}

		$this->cm_duration_computed        = $this->ora->cm_duration_computed;
		$this->cm_ipl_boot                 = $this->ora->cm_ipl_boot;
		$this->cm_status                   = $this->ora->cm_status;
		$this->cm_open_closed              = $this->ora->cm_open_closed;

		$this->cm_close_date_num           = $this->ora->cm_close_date;

		if ($this->ora->cm_close_date == 0)
		{
			$this->cm_close_date_char      = "";
			$this->cm_close_date_char2     = "";
		}
		else
		{
			$this->cm_close_date_char      =
				$this->gmt_to_format(
					$this->ora->cm_close_date,
					$mmddyyyy_hhmm_tz,
					$this->user_timezone_name);

			$this->cm_close_date_char2     =
				$this->gmt_to_format(
					$this->ora->cm_close_date,
					$mmddyyyy_hhmm,
					'America/Denver');
		}

		$this->cm_owner_first_name         = $this->ora->cm_owner_first_name;
		$this->cm_owner_last_name          = $this->ora->cm_owner_last_name;
		$this->cm_owner_cuid               = $this->ora->cm_owner_cuid;
		$this->cm_owner_group              = $this->ora->cm_owner_group;
		$this->note_to_clients             = $this->ora->note_to_clients;

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no                   = %s",  $this->ticket_no);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "insert_date_num             = %d",  $this->insert_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "insert_date_char            = %s",  $this->insert_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "insert_cuid                 = %s",  $this->insert_cuid);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "insert_name                 = %s",  $this->insert_name);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "update_date_num             = %d",  $this->update_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "update_date_char            = %s",  $this->update_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "update_cuid                 = %s",  $this->update_cuid);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "update_name                 = %s",  $this->update_name);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "status                      = %s",  $this->status);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "status_date_num             = %d",  $this->status_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "status_date_char            = %s",  $this->status_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "status_cuid                 = %s",  $this->status_cuid);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "status_name                 = %s",  $this->status_name);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "owner_cuid                  = %s",  $this->owner_cuid);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "owner_first_name            = %s",  $this->owner_first_name);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "owner_name                  = %s",  $this->owner_name);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "owner_email                 = %s",  $this->owner_email);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "owner_job_title             = %s",  $this->owner_job_title);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "manager_cuid                = %s",  $this->manager_cuid);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "manager_first_name          = %s",  $this->manager_first_name);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "manager_name                = %s",  $this->manager_name);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "manager_email               = %s",  $this->manager_email);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "manager_job_title           = %s",  $this->manager_job_title);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "work_activity               = %s",  $this->work_activity);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "approvals_required          = %s",  $this->approvals_required);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "reboot_required             = %s",  $this->reboot_required);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "respond_by_date_num         = %d",  $this->respond_by_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "respond_by_date_char        = %s",  $this->respond_by_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "email_reminder1_date_num    = %d",  $this->email_reminder1_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "email_reminder1_date_char   = %s",  $this->email_reminder1_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "email_reminder2_date_num    = %d",  $this->email_reminder2_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "email_reminder2_date_char   = %s",  $this->email_reminder2_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "email_reminder3_date_num    = %d",  $this->email_reminder3_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "email_reminder3_date_char   = %s",  $this->email_reminder3_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "schedule_start_date_num     = %d",  $this->schedule_start_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "schedule_start_date_char    = %s",  $this->schedule_start_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "schedule_end_date_num       = %d",  $this->schedule_end_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "schedule_end_date_char      = %s",  $this->schedule_end_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "work_description            = %s",  $this->work_description);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "work_implementation         = %s",  $this->work_implementation);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "work_backoff_plan           = %s",  $this->work_backoff_plan);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "work_business_reason        = %s",  $this->work_business_reason);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "work_user_impact            = %s",  $this->work_user_impact);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_servers_scheduled     = %d",  $this->total_servers_scheduled);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_servers_waiting       = %d",  $this->total_servers_waiting);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_servers_approved      = %d",  $this->total_servers_approved);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_servers_rejected      = %d",  $this->total_servers_rejected);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_servers_not_schedule  = %d",  $this->total_servers_not_scheduled);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "servers_not_scheduled       = %s",  $this->servers_not_scheduled);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "generator_runtime           = %s",  $this->generator_runtime);

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "csc_banner1                 = %s",  $this->csc_banner1);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "csc_banner2                 = %s",  $this->csc_banner2);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "csc_banner3                 = %s",  $this->csc_banner3);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "csc_banner4                 = %s",  $this->csc_banner4);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "csc_banner5                 = %s",  $this->csc_banner5);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "csc_banner6                 = %s",  $this->csc_banner6);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "csc_banner7                 = %s",  $this->csc_banner7);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "csc_banner8                 = %s",  $this->csc_banner8);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "csc_banner9                 = %s",  $this->csc_banner9);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "csc_banner10                = %s",  $this->csc_banner10);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "exclude_virtual_contacts    = %s",  $this->exclude_virtual_contacts);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "disable_scheduler           = %s",  $this->disable_scheduler);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "maintenance_window          = %s",  $this->maintenance_window);

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "change_date_num             = %d",  $this->change_date_num);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "change_date_char            = %s",  $this->change_date_char);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_ticket_no                = %s",  $this->cm_ticket_no);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_start_date_num           = %d",  $this->cm_start_date_num);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_start_date_char          = %s",  $this->cm_start_date_char);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_end_date_num             = %d",  $this->cm_end_date_num);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_end_date_char            = %s",  $this->cm_end_date_char);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_duration_computed        = %s",  $this->cm_duration_computed);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_ipl_boot                 = %s",  $this->cm_ipl_boot);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_status                   = %s",  $this->cm_status);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_open_closed              = %s",  $this->cm_open_closed);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_close_date_num           = %d",  $this->cm_close_date_num);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_close_date_char          = %s",  $this->cm_close_date_char);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_owner_first_name         = %s",  $this->cm_owner_first_name);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_owner_last_name          = %s",  $this->cm_owner_last_name);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_owner_cuid               = %s",  $this->cm_owner_cuid);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_owner_group              = %s",  $this->cm_owner_group);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "note_to_clients             = %s",  $this->note_to_clients);

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "SUCCESS - returning true");

        $this->owner();

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "authorized = %s", $this->authorize == true ? "true" : "false");
							
		return true;
	}

	/**
	 * @fn    addTicket()
	 *
	 * @brief Create a new system record from data stored in dynamic storage variables.
	 *
	 * @return string where 0 means failed. Returns the ticket_no created for this record.
	 */
	public function addTicket()
	{
		if ($this->cm_ticket_no == '(Optional)')
			$this->cm_ticket_no = '';

		if (strlen($this->cm_ticket_no) > 0 && $this->copy_to == 'ON')
		{
			if ($this->ora->sql("select * from t_cm_implementation_request@remedy_prod where change_id = '" .
								$this->cm_ticket_no . "'") == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
				$this->error = $this->ora->dbErrMsg;
				return false;
			}

			if ($this->ora->fetch())
			{
				$message = "Unable to pull Remedy ticket: " . $this->cm_ticket_no;
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $message);
			}

			$this->work_description     = $this->ora->description;
			$this->work_implementation  = $this->ora->implementation_instructions;
			$this->work_backoff_plan    = $this->ora->backoff_plan;
			$this->work_business_reason = $this->ora->business_reason;
			$this->work_user_impact     = $this->ora->impact;
		}

        $ticket_id = $this->ora->next_seq('cct7_ticketsseq');

		$this->ticket_no = sprintf("CCT7%08d", $ticket_id);
		
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s", $this->ticket_no);

		$this->insert_date_num    = $this->now_to_gmt_utime();
		$this->insert_date_char   = $this->gmt_to_format($this->insert_date_num, 'm/d/Y h:i', $this->user_timezone_name);
		$this->insert_name        = $this->user_name;
		$this->status             = 'DRAFT';

		if ($this->user_cuid == 'cctadm')
		{
			$query = "select * from cct7_mnet where ";
			$query .= sprintf("lower(mnet_cuid) = lower('%s') or ", $_SESSION['REMOTE_USER']);
			$query .= sprintf("lower(mnet_workstation_login) = lower('%s')", $_SESSION['REMOTE_USER']);

			if ($this->ora->sql($query) == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			}

			if ($this->ora->fetch())
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Got cct7_mnet record for user %s", $_SERVER['REMOTE_USER']);

				//
				// Copy this user's MNET data into their $_SESSION data variables
				//
				$_SESSION['real_cuid'] = $this->ora->mnet_cuid;
				$_SESSION['real_name'] =
					(empty($this->ora->mnet_nick_name)
						? $this->ora->mnet_first_name
						: $this->ora->mnet_nick_name) . " " . $this->ora->mnet_last_name;

				$_SESSION['user_cuid']       = $this->ora->mnet_cuid;
				$_SESSION['user_first_name'] = $this->ora->mnet_first_name;
				$_SESSION['user_last_name']  = $this->ora->mnet_last_name;
				$_SESSION['user_name']       = $this->ora->mnet_name;
				$_SESSION['user_email']      = $this->ora->mnet_email;
				$_SESSION['user_company']    = $this->ora->mnet_company;
				$_SESSION['user_job_title']  = $this->ora->mnet_job_title;
				$_SESSION['user_work_phone'] = $this->ora->mnet_work_phone;

				//
				// Does the users manager's cuid exist?
				//
				if (!empty($this->ora->mnet_mgr_cuid))
				{
					$this->debug1(__FILE__, __FUNCTION__, __LINE__,
								 "Getting cct7_mnet record for user %s managers cuid=%s",
								 $_SERVER['REMOTE_USER'], $this->ora->mnet_mgr_cuid);

					//
					// Retrieve the manager's cuid from MNET
					//
					if ($this->ora->sql("select * from cct7_mnet where mnet_cuid = '" . $this->ora->mnet_mgr_cuid . "'") == false)
					{
						$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
						$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
					}

					if ($this->ora->fetch())
					{
						$this->debug1(__FILE__, __FUNCTION__, __LINE__,
									 "Got cct7_mnet record for user %s manager's cuid %s",
									 $_SESSION['REMOTE_USER'], $this->ora->mnet_cuid);
						//
						// Add the users manager's MNET data to the $_SESSION data
						//
						$_SESSION['manager_cuid']       = $this->ora->mnet_cuid;
						$_SESSION['manager_first_name'] = $this->ora->mnet_first_name;
						$_SESSION['manager_last_name']  = $this->ora->mnet_last_name;
						$_SESSION['manager_name']       = $this->ora->mnet_name;
						$_SESSION['manager_email']      = $this->ora->mnet_email;
						$_SESSION['manager_company']    = $this->ora->mnet_company;
						$_SESSION['manager_job_title']  = $this->ora->mnet_job_title;
						$_SESSION['manager_work_phone'] = $this->ora->mnet_work_phone;
					}
				}
			}

			$this->user_cuid          = $_SESSION['user_cuid'];
			$this->user_first_name    = $_SESSION['user_first_name'];
			$this->user_last_name     = $_SESSION['user_last_name'];
			$this->user_name          = $_SESSION['user_name'];
			$this->user_email         = $_SESSION['user_email'];
			$this->user_job_title     = $_SESSION['user_job_title'];
			$this->user_company       = $_SESSION['user_company'];
			$this->user_access_level  = $_SESSION['user_access_level'];
			$this->user_timezone_name = $_SESSION['local_timezone_name'];

			$this->manager_cuid       = $_SESSION['manager_cuid'];
			$this->manager_first_name = $_SESSION['manager_first_name'];
			$this->manager_last_name  = $_SESSION['manager_last_name'];
			$this->manager_name       = $_SESSION['manager_name'];
			$this->manager_email      = $_SESSION['manager_email'];
			$this->manager_job_title  = $_SESSION['manager_job_title'];
			$this->manager_company    = $_SESSION['manager_company'];

			$this->is_debug_on        = "N";

			$this->owner_cuid         = $this->user_cuid;
			$this->owner_first_name   = $this->user_first_name;
			$this->owner_name         = $this->user_name;
			$this->owner_email        = $this->user_email;
			$this->owner_job_title    = $this->user_job_title;
		}
		else
		{
			$this->owner_cuid         = $this->user_cuid;
			$this->owner_first_name   = $this->user_first_name;
			$this->owner_name         = $this->user_name;
			$this->owner_email        = $this->user_email;
			$this->owner_job_title    = $this->user_job_title;
		}

        //
		// Construct the SQL to insert the first part of the ticket
		//
		$rc = $this->ora
			->insert("cct7_tickets")
			->column("ticket_no")
            ->column("insert_date")
            ->column("insert_cuid")
            ->column("insert_name")
            ->column("status")
			->column("owner_cuid")
			->column("owner_first_name")
			->column("owner_name")
			->column("owner_email")
			->column("owner_job_title")
			->column("manager_cuid")
			->column("manager_first_name")
			->column("manager_name")
			->column("manager_email")
            ->column("manager_job_title")
            ->column("work_activity")
            ->column("approvals_required")
            ->column("reboot_required")
			->column("respond_by_date")
            ->column("email_reminder1_date")
            ->column("email_reminder2_date")
            ->column("email_reminder3_date")
            ->column("schedule_start_date")
            ->column("work_description")
            ->column("work_implementation")
            ->column("work_backoff_plan")
            ->column("work_business_reason")
            ->column("work_user_impact")
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
			->column("exclude_virtual_contacts")
			->column("disable_scheduler")
			->column("maintenance_window")
			->column("cm_ticket_no")
			->column("cm_start_date")
			->column("cm_end_date")
			->column("cm_duration_computed")
			->column("cm_ipl_boot")
			->column("cm_status")
			->column("cm_open_closed")
			->column("cm_close_date")
			->column("cm_owner_first_name")
			->column("cm_owner_last_name")
			->column("cm_owner_cuid")
			->column("cm_owner_group")
			->column("note_to_clients")
			->value("char",  $this->ticket_no)                // ticket_no
            ->value("int",   $this->insert_date_num)          // insert_date
            ->value("char",  $this->user_cuid)                // insert_cuid
            ->value("char",  $this->user_name)                // insert_name
            ->value("char",  $this->status)                   // status
			->value("char",  $this->owner_cuid)               // owner_cuid
			->value("char",  $this->owner_first_name)         // owner_first_name
			->value("char",  $this->owner_name)               // owner_name
			->value("char",  $this->owner_email)              // owner_email
            ->value("char",  $this->owner_job_title)          // owner_job_title
			->value("char",  $this->manager_cuid)             // manager_cuid
			->value("char",  $this->manager_first_name)       // manager_first_name
			->value("char",  $this->manager_name)             // manager_name
			->value("char",  $this->manager_email)            // manager_email
            ->value("char",  $this->manager_job_title)        // manager_job_title
            ->value("char",  $this->work_activity)            // work_activity
            ->value("char",  $this->approvals_required)       // approvals_required
            ->value("char",  $this->reboot_required)          // reboot_required
            ->value("int",   $this->respond_by_date_num)      // respond_by_date
            ->value("int",   $this->email_reminder1_date_num) // email_reminder1_date
            ->value("int",   $this->email_reminder2_date_num) // email_reminder2_date
            ->value("int",   $this->email_reminder3_date_num) // email_reminder3_date
            ->value("int",   $this->schedule_start_date_num)  // schedule_start_date
            ->value("char",  $this->work_description)         // work_description
            ->value("char",  $this->work_implementation)      // work_implementation
            ->value("char",  $this->work_backoff_plan)        // work_backoff_plan
            ->value("char",  $this->work_business_reason)     // work_business_reason
            ->value("char",  $this->work_user_impact)         // work_user_impact
			->value("char",  $this->csc_banner1)
			->value("char",  $this->csc_banner2)
			->value("char",  $this->csc_banner3)
			->value("char",  $this->csc_banner4)
			->value("char",  $this->csc_banner5)
			->value("char",  $this->csc_banner6)
			->value("char",  $this->csc_banner7)
			->value("char",  $this->csc_banner8)
			->value("char",  $this->csc_banner9)
			->value("char",  $this->csc_banner10)
			->value("char",  $this->exclude_virtual_contacts)
			->value("char",  $this->disable_scheduler)
			->value("char",  $this->maintenance_window)
			->value("char",  $this->cm_ticket_no)             // cm_ticket_no
			->value("int",   $this->cm_start_date_num)
			->value("int",   $this->cm_end_date_num)
			->value("char",  $this->cm_duration_computed)
			->value("char",  $this->cm_ipl_boot)
			->value("char",  $this->cm_status)
			->value("char",  $this->cm_open_closed)
			->value("int",   $this->cm_close_date)
			->value("char",  $this->cm_owner_first_name)
			->value("char",  $this->cm_owner_last_name)
			->value("char",  $this->cm_owner_cuid)
			->value("char",  $this->cm_owner_group)
			->value("char",  $this->note_to_clients)
			->execute();

		if ($rc == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                $this->ora->sql_statement, $this->ora->dbErrMsg);
            return null;
		}

		$this->ora->commit();
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "CREATE TICKET SUCCESS - returning true");

		return $this->ticket_no;
	}

	/**
	 * @fn    activate($ticket_no)
	 *
	 * @brief Activate the ticket, send out email notifications, log email activity, log activation message.
	 *
	 * @param string $ticket_no
	 *
	 * @return bool
	 */
	public function activate($ticket_no)
	{
		if ($this->getTicket($ticket_no) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		$subject = sprintf("Change Coordination Tool - NEW Work Request: %s/%s",
			$this->cm_ticket_no, $this->ticket_no);

		$rc = $this->ora
			->update("cct7_tickets")
			->set("int",  "update_date",  $this->now_to_gmt_utime())
			->set("char", "update_cuid",  $this->user_cuid)
			->set("char", "update_name",  $this->user_name)
			->set("char", "status",       "ACTIVE")
			->set("int",  "status_date",  $this->now_to_gmt_utime())
			->set("char", "status_cuid",  $this->user_cuid)
			->set("char", "status_name",  $this->user_name)
			->where("char", "ticket_no", "=", $ticket_no)
			->execute();

		if ($rc == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$this->rows_affected = $this->ora->rows_affected;

		if ($this->putLogTicket($ticket_no, "ACTIVATED", "Ticket has been activated.") == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		$this->ora->commit();
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Ticket %s has been activated.", $ticket_no);

		//
		// Now send out a email notice that the ticket has been activated.
		//
		$message_body  =
			sprintf("<p>This is a <font color='blue'><b>NEW %s Work Request</b></font> for tickets: %s/%s. ",
			$this->work_activity, $this->cm_ticket_no, $this->ticket_no);

		$message_body .= sprintf("If you are required to approve this work please do it by: %s. ",
			$this->respond_by_date_char);

		$message_body .= "Sign-on to CCT see details about the work, the schedule and whether you need to approve ";
		$message_body .= "the work.</p>";

		if (strlen($this->note_to_clients) > 0)
		{
			$message_body .= sprintf("<p>%s</p>", $this->note_to_clients);
		}

		$message_body .= "<p style='color: blue;'>";
		$message_body .= sprintf("If there are issues, please contact %s (%s) who sent the message.</p>",
								 $this->user_name, $this->user_email);

		$list = new email_contacts($this->ora);

		//
		// Gather a list of contacts that approvers only.
		//
		// function byTicket($ticket_no, $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
		//
		if ($list->byTicket($ticket_no, "Y", "Y", "N") == false)
		{
			$this->error = $list->error;
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		foreach ($list->email_list as $cuid => $name_and_email_and_type)
		{
			//
			// Break apart $name_and_email_and_type  (i.e. Mary Subach|Mary.Subach@CMP.com|FYI)
			//
			$str = explode('|', $name_and_email_and_type);

			$name          = isset($str[0]) ? $str[0] : "";
			$email_address = isset($str[1]) ? $str[1] : "";
			// $notify_type   = isset($str[2]) ? $str[2] : "";

			if (strlen($email_address) == 0)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Missing email address: %s", $str);
				continue;
			}

			$to        = $email_address;
			$to_header = sprintf("%s <%s>", $name, $email_address);

			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

			// Additional headers
			$headers .= 'From: ' . $this->user_name . ' <' . $this->user_email . '>' . "\r\n";;
			$headers .= 'To: '   . $to_header . "\r\n";

			$success = "Y";

			if ($this->mail($to, $subject, $message_body, $headers) == false)
			{
				$success = "N";
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to send email: %s <%s>", $name, $email_address);
			}

			//
			// Log email event
			//
			// Table: CCT7_SENDMAIL_LOG
			// sendmail_date|NUMBER|0||GMT Date of this record
			// sendmail_cuid|VARCHAR2|20||CUID of the person we emailed to
			// sendmail_name|VARCHAR2|200||Name of the person
			// sendmail_email|VARCHAR2|80||Email address
			// sendmail_success|VARCHAR2|1||PHP mail() successful? Y/N
			// sendmail_subject|VARCHAR2|4000||Email Subject Line
			// sendmail_message|VARCHAR2|4000||Email message body

			$rc = $this->ora
				->insert("cct7_sendmail_log")
				->column("sendmail_date")
				->column("sendmail_cuid")
				->column("sendmail_name")
				->column("sendmail_email")
				->column("sendmail_success")
				->column("sendmail_subject")
				->column("sendmail_message")
				->value("int",   $this->now_to_gmt_utime())  // sendmail_date
				->value("char",  $cuid)                      // sendmail_cuid
				->value("char",  $name)                      // sendmail_name
				->value("char",  $email_address)             // sendmail_email
				->value("char",  $success)                   // sendmail_success
				->value("char",  $this->maxStringLength($subject, 4000))      // sendmail_subject
				->value("char",  $this->maxStringLength($message_body, 4000)) // sendmail_message
				->execute();

			if ($rc == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
				$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
									   $this->ora->sql_statement, $this->ora->dbErrMsg);
				// Just continue on... I will discover that the log isn't working.
			}

			$this->ora->commit();
		}

		return true;
	}

	/**
	 * @fn     cancel($ticket_no)
	 *
	 * @brief  Cancel the ticket, send out email notifications, log email cancellation messages, log cancellation message.
	 *
	 * @param  string $ticket_no
	 *
	 * @return bool
	 */
	public function cancel($ticket_no)
	{
		if ($this->ora2 == null)
			$this->ora2 = new oracle();

		if ($this->getTicket($ticket_no) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		// ACTIVE
		// CANCELED
		// CLOSED
		// DRAFT
		// FAILED

		if ($this->status != "ACTIVE")
		{
			$this->error = sprintf("Current ticket %s status is %s. Cannot cancel this ticket.",
				$ticket_no, $this->status);
			return false;
		}

		$cc = sprintf("%s <%s>", $this->owner_name, $this->owner_email);

		$rc = $this->ora
			->update("cct7_tickets")
			->set("int",  "update_date",  $this->now_to_gmt_utime())
			->set("char", "update_cuid",  $this->user_cuid)
			->set("char", "update_name",  $this->user_name)
			->set("char", "status",       "CANCELED")
			->set("int",  "status_date",  $this->now_to_gmt_utime())
			->set("char", "status_cuid",  $this->user_cuid)
			->set("char", "status_name",  $this->user_name)
			->where("char", "ticket_no", "=", $ticket_no)
			->execute();

		if ($rc == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$this->rows_affected = $this->ora->rows_affected;

		if ($this->putLogTicket($ticket_no, "CANCELED", "Work has been canceled.") == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		$this->ora->commit();

		//
		// Change all the server_work_status' and contact_response_status' to CANCELED
		//
		$query = sprintf("select system_id from cct7_systems where ticket_no = '%s'", $ticket_no);

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		while ($this->ora->fetch())
		{
			$rc = $this->ora2
				->update("cct7_systems")
				->set("int",  "system_update_date",        $this->now_to_gmt_utime())
				->set("char", "system_update_cuid",        $this->user_cuid)
				->set("char", "system_update_name",        $this->user_name)
				->set("char", "system_work_status", "CANCELED")
				->where("int", "system_id", "=", $this->ora->system_id)
				->execute();

			if ($rc == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora2->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora2->dbErrMsg);
				$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
									   $this->ora2->sql_statement, $this->ora2->dbErrMsg);
				return false;
			}
		}

		$this->ora2->commit();

		$query  = "select ";
		$query .= "  c.contact_id ";
		$query .= "from ";
		$query .= "  cct7_systems s, ";
		$query .= "  cct7_contacts c ";
		$query .= "where ";
		$query .= sprintf("  s.ticket_no = '%s' and c.system_id = s.system_id", $ticket_no);

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		while ($this->ora->fetch())
		{
			$rc = $this->ora2
				->update("cct7_contacts")
				->set("int",  "contact_update_date",     $this->now_to_gmt_utime())
				->set("char", "contact_update_cuid",     $this->user_cuid)
				->set("char", "contact_update_name",     $this->user_name)
				->set("char", "contact_response_status", "CANCELED")
				->set("int",  "contact_response_date",   $this->now_to_gmt_utime())
				->set("char", "contact_response_cuid",   $this->user_cuid)
				->set("char", "contact_response_name",   $this->user_name)
				->where("int", "contact_id", "=", $this->ora->contact_id)
				->execute();
		}

		$this->ora2->commit();

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Ticket %s has been canceled.", $ticket_no);

		return true;
	}

	/**
	 * @fn     deleteTicket($ticket_no)
	 *
	 * @brief  Delete this "DRAFT" ticket from the database along with all data connected via. foreign keys.
	 *
	 * @param  string $ticket_no is the record id we want to delete.
	 *
	 * @return bool - true or false, where true is success
	 */
	public function delete($ticket_no)
	{
		if ($this->getTicket($ticket_no) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		// ACTIVE
		// CANCELED
		// CLOSED
		// DRAFT
		// FAILED

		if ($this->status != "ACTIVE" && $this->status != "DRAFT")
		{
			$this->error = sprintf("Current ticket %s status is %s. Cannot delete this ticket.",
								   $ticket_no, $this->status);
			return false;
		}

		//
		// Cascade delete is setup to remove all records where foreign keys exist for $ticket_no. This means cct7_servers,
		// cct7_contacts, cct7_notes, etc. will be removed.
		//
		if ($this->ora->sql2("delete from cct7_tickets where ticket_no = '" . $ticket_no . "'") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
		}

		$this->rows_affected = $this->ora->rows_affected;

		$this->ora->commit();
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "DELETE SUCCESS - returning true");
		return true;
	}

	/**
	 * @fn    approveAllServers($ticket_no, $send_page)
	 *
	 * @brief All all servers for this ticket.
	 *
	 * @param string $ticket_no
	 * @param string $send_page
	 *
	 * @return bool
	 */
	public function approveAllServers($ticket_no, $send_page)
	{
		$con = new cct7_contacts();

		$this->rows_affected = 0;

		$groups      = isset($_SESSION['user_groups']) ? $_SESSION['user_groups'] : '';
		$user_groups = explode(',', $groups);

		foreach ($user_groups as $group_pin)
		{
			if ($con->approveTicket($ticket_no, $group_pin, $send_page) == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "approveTicket(%s, %s) failed: %s",
							  $ticket_no, $group_pin, $con->error);
				$this->error = sprintf("%s %s %d: approveTicket(%s, %s) failed: %s",
									   __FILE__, __FUNCTION__, __LINE__,
									   $ticket_no, $group_pin, $con->error);
				return false;
			}

			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s - %s, rows affected by update: %d",
						  $ticket_no, $group_pin, $con->rows_affected);

			$this->rows_affected += $con->rows_affected;
		}

		return true;

		/**
		$query  = "select distinct ";
		$query .= "  c.contact_netpin_no ";
		$query .= "from ";
		$query .= "  cct7_systems s, ";
		$query .= "  cct7_contacts c ";
		$query .= "where ";
		$query .= "  c.system_id = s.system_id and ";
		$query .= "  s.ticket_no = '" . $ticket_no . "'";

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$con = new cct7_contacts();

		$this->rows_affected = 0;

		while ($this->ora->fetch())
		{
			if ($con->approveTicket($ticket_no, $this->ora->contact_netpin_no, $send_page) == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "approveTicket(%s, %s) failed: %s",
							  $ticket_no, $this->ora->netpin_no, $con->error);
				$this->error = sprintf("%s %s %d: approveTicket(%s, %s) failed: %s",
									   __FILE__, __FUNCTION__, __LINE__,
									   $ticket_no, $this->ora->contact_netpin_no, $con->error);
				return false;
			}

			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s - %s, rows affected by update: %d",
						  $ticket_no, $this->ora->contact_netpin_no, $con->rows_affected);

			$this->rows_affected += $con->rows_affected;
		}

		return true;
		*/
	}

	/**
	 * @fn    approveAllByCUID($ticket_no, $cuid, $action)
	 *
	 * @brief Approve work on all servers where this this user ($cuid) is a contact.
	 *
	 * @param string $ticket_no
	 * @param string $cuid
	 * @param string $send_page - 'Y' or 'N'
	 *
	 * @return bool - true or false, where true is success
	 */
	public function approveAllByCUID($ticket_no, $cuid, $send_page)
	{
		$con = new cct7_contacts();

		$this->rows_affected = 0;

		$groups      = isset($_SESSION['user_groups']) ? $_SESSION['user_groups'] : '';
		$user_groups = explode(',', $groups);

		foreach ($user_groups as $group_pin)
		{
			if ($con->approveTicket($ticket_no, $group_pin, $send_page) == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__,
							  "approveTicket(%s, %s) failed: %s",
							  $ticket_no, $group_pin, $con->error);

				$this->error = sprintf("%s %s %d: approveTicket(%s, %s) failed: %s",
									   __FILE__, __FUNCTION__, __LINE__,
									   $ticket_no, $group_pin, $con->error);
				return false;
			}

			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s - %s, rows affected by update: %d",
						  $ticket_no, $group_pin, $con->rows_affected);

			$this->rows_affected += $con->rows_affected;
		}

		return true;

		/**
		//
		// Get a list of group netpins this $cuid belongs to.
		//
		$query  = "select distinct ";
		$query .= "  net_pin_no as netpin_no ";
		$query .= "from ";
		$query .= "  cct7_netpin_to_cuid ";
		$query .= "where ";
		$query .= "  user_cuid = '" . $cuid . "'";

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$con = new cct7_contacts();

		$this->rows_affected = 0;

		while ($this->ora->fetch())
		{
			if ($con->approveTicket($ticket_no, $this->ora->netpin_no, $send_page) == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "approveTicket(%s, %s) failed: %s",
							  $ticket_no, $this->ora->netpin_no, $con->error);
				$this->error = sprintf("%s %s %d: approveTicket(%s, %s) failed: %s",
									   __FILE__, __FUNCTION__, __LINE__,
									   $ticket_no, $this->ora->netpin_no, $con->error);
				return false;
			}

			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s - %s, rows affected by update: %d",
						  $ticket_no, $this->ora->netpin_no, $con->rows_affected);

			$this->rows_affected += $con->rows_affected;
		}

		return true;
		*/
	}

	/**
	 * @fn     closeActiveTickets()
	 *
	 * @brief  This method scans CCT tickets searches for active tickets to close.
	 *
	 * @return bool true or false where true means a successful operation.
	 */
	public function closeActiveTickets()
	{
		$ora2 = new oracle();

		$query  = "select ";
		$query .= "  ticket_no ";
		$query .= "from ";
		$query .= "  cct7_tickets ";
		$query .= "where ";
		$query .= "  status = 'ACTIVE' and ";
		$query .= "  disable_scheduler = 'N' and ";
		$query .= "  schedule_end_date < " . $this->time_now;

		if ($ora2->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $ora2->sql_statement, $ora2->dbErrMsg);
			return false;
		}

		while ($ora2->fetch())
		{
			//
			// Close the ticket
			//
			$rc = $this->ora
				->update("cct7_tickets")
				->set("int",  "update_date",  $this->now_to_gmt_utime())
				->set("char", "update_cuid",  $this->user_cuid)
				->set("char", "update_name",  $this->user_name)
				->set("char", "status",       "CLOSED")
				->set("int",  "status_date",  $this->now_to_gmt_utime())
				->set("char", "status_cuid",  $this->user_cuid)
				->set("char", "status_name",  $this->user_name)
				->where("char", "ticket_no", "=", $ora2->ticket_no)
				->execute();

			if ($rc == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->dbErrMsg);
				$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
									   $ora2->sql_statement, $ora2->dbErrMsg);
				return false;
			}

			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Ticket: %s is now CLOSED", $ora2->ticket_no);
		}

		$query  = "select ";
		$query .= "  t.ticket_no ";
		$query .= "from ";
		$query .= "  cct7_tickets t, ";
		$query .= "  t_cm_implementation_request@remedy_prod r ";
		$query .= "where ";
		$query .= "  t.status = 'ACTIVE' and ";
		$query .= "  t.disable_scheduler = 'Y' and ";
		$query .= "  r.change_id = t.cm_ticket_no and ";
		$query .= "  r.open_closed = 'Closed'";

		if ($ora2->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $ora2->sql_statement, $ora2->dbErrMsg);
			return false;
		}

		while ($ora2->fetch())
		{
			//
			// Close the ticket
			//
			$rc = $this->ora
				->update("cct7_tickets")
				->set("int",  "update_date",  $this->now_to_gmt_utime())
				->set("char", "update_cuid",  $this->user_cuid)
				->set("char", "update_name",  $this->user_name)
				->set("char", "status",       "CLOSED")
				->set("int",  "status_date",  $this->now_to_gmt_utime())
				->set("char", "status_cuid",  $this->user_cuid)
				->set("char", "status_name",  $this->user_name)
				->where("char", "ticket_no", "=", $ora2->ticket_no)
				->execute();

			if ($rc == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora2->dbErrMsg);
				$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
									   $ora2->sql_statement, $ora2->dbErrMsg);
				return false;
			}

			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Ticket: %s is now CLOSED", $ora2->ticket_no);
		}

		return true;
	}

	/**
	 * @fn    log($ticket_no, $event_message)
	 *        
	 * @brief Log a NOTE in cct7_log_tickets identified by $ticket_no.
	 *
	 * @param string $ticket_no
	 * @param string $event_message
	 *
	 * @return bool
	 */
	public function log($ticket_no, $event_message)
	{
		if ($this->getTicket($ticket_no) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		if ($this->putLogTicket($ticket_no, "NOTE", $event_message) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		return true;
	}

	/**
	 * @fn    putLogTicket($ticket_no, $event_type, $event_message)
	 *
	 * @brief Log event information in cct7_log_tickets
	 *
	 * @param string $ticket_no
	 * @param string $event_type
	 * @param string $event_message
	 *
	 * @return bool
	 */
	public function putLogTicket($ticket_no, $event_type, $event_message)
	{
		if ($this->ora2 == null)
			$this->ora2 = new oracle();

		// cct7_log_tickets
		//
		// ticket_no|VARCHAR2|20|NOT NULL|CCT ticket_no no.
		// event_date|NUMBER|0||Event Date (GMT)
		// event_cuid|VARCHAR2|20|NOT NULL|Event Owner CUID
		// event_name|VARCHAR2|200|NOT NULL|Event Owner Name
		// event_type|VARCHAR2|20|NOT NULL|Event type
		// event_message|VARCHAR2|4000|NOT NULL|Event message
		//
		$rc = $this->ora2
			->insert("cct7_log_tickets")
			->column("ticket_no")        // ticket_no    |VARCHAR2|20  |NOT NULL|CCT Ticket
			->column("event_date")       // event_date   |NUMBER  |0   |        |Event Date (GMT)
			->column("event_cuid")       // event_cuid   |VARCHAR2|20  |        |Event Owner CUID
			->column("event_name")       // event_name   |VARCHAR2|200 |        |Event Owner Name
			->column("event_type")       // event_type   |VARCHAR2|20  |        |Event type
			->column("event_message")    // event_message|VARCHAR2|4000|        |Event message
			->value("char",  $ticket_no)
			->value("int",   $this->now_to_gmt_utime())  // event_date
			->value("char",  $this->user_cuid)
			->value("char",  $this->user_name)
			->value("char",  $event_type)
			->value("char",  $event_message)
			->execute();

		if ($rc == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora2->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora2->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora2->sql_statement, $this->ora2->dbErrMsg);
			return false;
		}

		//
		// Change the update_date in cct7_tickets for $ticket_no so send_notifications.php can send out email
		// for this log event.
		//
		$rc = $this->ora2
			->update("cct7_tickets")
			->set("int",    "update_date",     $this->now_to_gmt_utime())
			->set("char",   "update_cuid",     $this->user_cuid)
			->set("char",   "update_name",     $this->user_name)
			->where("char",  "ticket_no", "=", $ticket_no)
			->execute();

		if ($rc == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora2->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora2->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora2->sql_statement, $this->ora2->dbErrMsg);
			return false;
		}

		$this->rows_affected = $this->ora->rows_affected;

		$this->ora2->commit();

		return true;
	}

	/**
	 * @fn    getLogTicket($ticket_no)
	 *
	 * @brief Retrieve a list list object containing all the event log messages for a ticket.
	 *
	 * @brief create index idx_cct7_log_tickets1 on cct7_log_tickets (ticket_no);  -- Pull all events by ticket_no
	 *        create index idx_cct7_log_tickets2 on cct7_log_tickets (event_cuid); -- Pull all events by cuid
	 *        create index idx_cct7_log_tickets3 on cct7_log_tickets (event_type); -- Pull all events by event type [EMAIL, PAGE, etc.]
	 *
	 * @param string $ticket_no
	 *
	 * @return object
	 */
	public function getLogTicket($ticket_no)
	{
		$top = null;
		$p   = null;

		// cct7_log_tickets
		//
		// ticket_no|VARCHAR2|20|NOT NULL|CCT ticket_no no.
		// event_date|NUMBER|0||Event Date (GMT)
		// event_cuid|VARCHAR2|20|NOT NULL|Event Owner CUID
		// event_name|VARCHAR2|200|NOT NULL|Event Owner Name
		// event_type|VARCHAR2|20|NOT NULL|Event type
		// event_message|VARCHAR2|4000|NOT NULL|Event message

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
		$query .= "  ticket_no = '" . $ticket_no . "' ";
		$query .= "order by ";
		$query .= "  event_date desc";

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return null;
		}

		$top = $p = null;

		while ($this->ora->fetch())
		{
			if ($top == null)
			{
				$top = $p = new data_node();
			}
			else
			{
				$p->next = new data_node();
				$p = $p->next;
			}

			$p->ticket_no       = $this->ora->ticket_no;
			$p->event_date_num  = $this->ora->event_date;
			$p->event_date_char = $this->ora->gmt_to_format($this->ora->event_date, 'm/d/Y H:i T', $this->user_timezone_name);
			$p->event_cuid      = $this->ora->event_cuid;
			$p->event_name      = $this->ora->event_name;
			$p->event_type      = $this->ora->event_type;
			$p->event_message   = $this->ora->event_message;
		}

		if ($top == null)
			$this->error = "No events records found for this search.";

		return $top;
	}

	/**
	 * @fn    updateScheduleDates($ora, $ticket_no)
	 *
	 * @brief Update cct7_tickets's schedule_start_date and schedule_end_date values based upon data computed
	 *        in cct7_systems system_work_start and system_work_end dates.
	 *
	 * @param string $ticket_no - CCT7 Ticket No.
	 *
	 * @return bool
	 */
	public function updateScheduleDates($ticket_no)
	{
		//
		// Execute stored procedure. (See: ibmtools_cct7/Procedures/updateScheduleDates.sql)
		//
		$query = sprintf("BEGIN updateScheduleDates('%s'); END;", $ticket_no);

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		return true;
	}

	/**
	 * @fn    updateRunInformation($ticket_no, $cm_start_date, $cm_end_date, $total_servers_not_scheduled, $servers_not_scheduled, $generator_runtime)
	 *
	 * @brief Called by toolbar_new.php to record work request scheduler statics.
	 *
	 * @param string $ticket_no                   - The unique record ID we want to change the status for.
	 * @param int    $cm_start_date        - Used to show what the Remedy CM Start date should be.
	 * @param int    $cm_end_date          - Used to show what the Remedy CM End date should be.
	 * @param int    $total_servers_not_scheduled - Total number of servers not scheduled.
	 * @param string $servers_not_scheduled       - List of servers not scheduled because they were not found in cct7_computers
	 * @param string $generator_runtime           - Generator runtime. Used as CCT application tuning data.
	 *
	 * @return bool - true or false, where true is success
	 */
	public function updateRunInformation($ticket_no, $cm_start_date, $cm_end_date, $total_servers_not_scheduled, $servers_not_scheduled, $generator_runtime)
	{
		$query  = "update cct7_tickets set ";
		$query .= sprintf("  update_date = %d, ",                 $this->now_to_gmt_utime());
		$query .= sprintf("  update_cuid = '%s', ",               $this->user_cuid);
		$query .= sprintf("  update_name = '%s', ",               $this->user_name);
		$query .= sprintf("  cm_start_date = %d, ",               $cm_start_date);
		$query .= sprintf("  cm_end_date = %d, ",                 $cm_end_date);
		$query .= sprintf("  total_servers_not_scheduled = %d, ", $total_servers_not_scheduled);
		$query .= sprintf("  servers_not_scheduled = '%s', ",     substr($servers_not_scheduled, 0, 4000));
		$query .= sprintf("  generator_runtime = '%s' ",          $generator_runtime);
		$query .- sprintf("where ticket_no = '%s'",               $ticket_no);

		$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$this->rows_affected = $this->ora->rows_affected;

		$mmddyyyy_hhmm = 'm/d/Y H:i T';
		$tz = "America/Denver";

		$this->cm_start_date_num           = $cm_start_date;
		$this->cm_start_date_char          = $this->gmt_to_format($cm_start_date,  $mmddyyyy_hhmm, $tz);
		$this->cm_end_date_num             = $cm_end_date;
		$this->cm_end_date_char            = $this->gmt_to_format($cm_end_date,  $mmddyyyy_hhmm, $tz);
		$this->servers_not_scheduled       = $servers_not_scheduled;
		$this->generator_runtime           = $generator_runtime;

		$this->ora->commit();

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no                   = %s", $ticket_no);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_start_date               = %d", $cm_start_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cm_end_date                 = %d", $cm_end_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "servers_not_scheduled       = %d", $servers_not_scheduled);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "generator_runtime           = %s", $generator_runtime);

		return true;
	}

	/**
	 * @fn    sendmailTicketOwner($ticket_no, $subject_line, $email_cc, $email_bcc, $message_body)
	 *
	 * @brief Called from ajax_dialog_toolbar_open_tickets.php in response from a user's request
	 *        to send a email to the ticket owner from dialog_toolbar_open_tickets.php
	 *
	 * @param $ticket_no
	 * @param $subject_line
	 * @param $email_cc
	 * @param $email_bcc
	 * @param $message_body
	 *
	 * @return bool
	 */
	public function sendmailTicketOwner($ticket_no, $subject_line, $email_cc, $email_bcc, $message_body)
	{
		if (strlen($message_body) == 0)
		{
			$this->error = "Message body is empty!";
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Message body is empty");
			return false;
		}

		$message_body .= "<p style='color: blue;'>";
		$message_body .= "This message was sent via. the CCT emailer, https://cct.corp.intranet<br>";
		$message_body .= sprintf("If there are issues, please contact %s (%s) who sent the message.</p>",
								 $this->user_name, $this->user_email);

		if ($this->getTicket($ticket_no) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Cannot sendmail %s contacts.", $ticket_no);
			return false;
		}

		$to        = $this->owner_email;
		$to_header = sprintf("%s <%s>", $this->owner_name, $this->owner_email);

		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		// Additional headers
		$headers .= 'From: ' . $this->user_name . ' <' . $this->user_email . '>' . "\r\n";;
		$headers .= 'To: '   . $to_header . "\r\n";
		$headers .= 'Cc: '   . $email_cc . "\r\n";
		$headers .= 'Bcc: '  . $email_bcc . "\r\n";

		$success = "Y";

		if ($this->mail($to, $subject_line, $message_body, $headers) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to send email: %s <%s>",
						  $this->owner_name, $this->owner_email);
			$success = "N";
		}

		//
		// Log email event
		//
		// Table: CCT7_SENDMAIL_LOG
		// sendmail_date|NUMBER|0||GMT Date of this record
		// sendmail_cuid|VARCHAR2|20||CUID of the person we emailed to
		// sendmail_name|VARCHAR2|200||Name of the person
		// sendmail_email|VARCHAR2|80||Email address
		// sendmail_success|VARCHAR2|1||PHP mail() successful? Y/N
		// sendmail_subject|VARCHAR2|4000||Email Subject Line
		// sendmail_message|VARCHAR2|4000||Email message body

		$rc = $this->ora
			->insert("cct7_sendmail_log")
			->column("sendmail_date")
			->column("sendmail_cuid")
			->column("sendmail_name")
			->column("sendmail_email")
			->column("sendmail_success")
			->column("sendmail_subject")
			->column("sendmail_message")
			->value("int",   $this->now_to_gmt_utime())  // sendmail_date
			->value("char",  $this->owner_cuid)          // sendmail_cuid
			->value("char",  $this->owner_name)          // sendmail_name
			->value("char",  $this->owner_email)         // sendmail_email
			->value("char",  $success)                   // sendmail_success
			->value("char",  $this->maxStringLength($subject_line, 4000)) // sendmail_subject
			->value("char",  $this->maxStringLength($message_body, 4000)) // sendmail_message
			->execute();

		if ($rc == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			// Just continue on... I will discover that the log isn't working.
		}

		$this->ora->commit();

		return true;
	}

	/**
	 * @fn    sendmail($ticket_no, $subject_line, $email_cc, $email_bcc, $message_body, $waiting_only="N")
	 *
	 * @brief Send email to all ticket contacts that are APPROVERS. Further targeting using $waiting_only
	 *        (See: ajax_dialog_toolbar_open_tickets.php)
	 *
	 * @param string $ticket_no
	 * @param string $subject_line
	 * @param string $email_cc
	 * @param string $email_bcc
	 * @param string $message_body
	 * @param string $waiting_only  - Y/(N) Send email to those that have not responded (approved).
	 *
	 * @return bool
	 */
	public function sendmail($ticket_no, $subject_line, $email_cc, $email_bcc, $message_body, $waiting_only="N")
	{
		if (strlen($message_body) == 0)
		{
			$this->error = "Message body is empty!";
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Message body is empty");
			return false;
		}

		$message = sprintf("<p>This is in reference to %s. See CCT for more detail.</p>", $subject_line);

		$message_body .= "<p style='color: blue;'>";
		$message_body .= "This message was sent via. the CCT emailer, https://cct.corp.intranet<br>";
		$message_body .= sprintf("If there are issues, please contact %s (%s) who sent the message.</p>",
								 $this->user_name, $this->user_email);

		$message .= $message_body;

		$list = new email_contacts($this->ora);

		//
		// Gather a list of contacts that approvers only.
		//
		// function byTicket($ticket_no, $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
		//
		if ($list->byTicket($ticket_no, "Y", "N", $waiting_only) == false)
		{
			$this->error = $list->error;
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		foreach ($list->email_list as $cuid => $name_and_email_and_type)
		{
			//
			// Break apart $name_and_email_and_type  (i.e. Mary Subach|Mary.Subach@CMP.com|FYI)
			//
			$str = explode('|', $name_and_email_and_type);

			$name          = isset($str[0]) ? $str[0] : "";
			$email_address = isset($str[1]) ? $str[1] : "";
			// $notify_type   = isset($str[2]) ? $str[2] : "";

			if (strlen($email_address) == 0)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Missing email address: %s", $str);
				continue;
			}

			$to        = $email_address;
			$to_header = sprintf("%s <%s>", $name, $email_address);

			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

			// Additional headers
			$headers .= 'From: ' . $this->user_name . ' <' . $this->user_email . '>' . "\r\n";;
			$headers .= 'To: '   . $to_header . "\r\n";
			$headers .= 'Cc: '   . $email_cc . "\r\n";
			$headers .= 'Bcc: '  . $email_bcc . "\r\n";

			$success = "Y";

			if ($this->mail($to, $subject_line, $message, $headers) == false)
			{
				$success = "N";
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to send email: %s <%s>", $name, $email_address);
			}

			//
			// Log email event
			//
			// Table: CCT7_SENDMAIL_LOG
			// sendmail_date|NUMBER|0||GMT Date of this record
			// sendmail_cuid|VARCHAR2|20||CUID of the person we emailed to
			// sendmail_name|VARCHAR2|200||Name of the person
			// sendmail_email|VARCHAR2|80||Email address
			// sendmail_success|VARCHAR2|1||PHP mail() successful? Y/N
			// sendmail_subject|VARCHAR2|4000||Email Subject Line
			// sendmail_message|VARCHAR2|4000||Email message body

			$rc = $this->ora
				->insert("cct7_sendmail_log")
				->column("sendmail_date")
				->column("sendmail_cuid")
				->column("sendmail_name")
				->column("sendmail_email")
				->column("sendmail_success")
				->column("sendmail_subject")
				->column("sendmail_message")
				->value("int",   $this->now_to_gmt_utime())  // sendmail_date
				->value("char",  $cuid)                      // sendmail_cuid
				->value("char",  $name)                      // sendmail_name
				->value("char",  $email_address)             // sendmail_email
				->value("char",  $success)                   // sendmail_success
				->value("char",  $this->maxStringLength($subject_line, 4000)) // sendmail_subject
				->value("char",  $this->maxStringLength($message, 4000))      // sendmail_message
				->execute();

			if ($rc == false)
			{
				$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
				$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
									   $this->ora->sql_statement, $this->ora->dbErrMsg);
				// Just continue on... I will discover that the log isn't working.
			}

			$this->ora->commit();
		}

		return true;
	}

	/**
	 * @fn    mail($to, $subject_line, $message_body, $headers, $cuid)
	 *
	 * @brief This is the main mail program that this class uses. For testing I simply send the email to the debug
	 *        file.
	 *
	 * @param string $to
	 * @param string $subject_line
	 * @param string $message_body
	 * @param string $headers
	 * @param string $cuid
	 *
	 * @return bool
	 */
	private function mail($to, $subject_line, $message_body, $headers)
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "<br>%s<br>%s<br>%s", $headers, $subject_line, $message_body);

		//
		// Test whether to actually send the email.
		//
		$web_server_name = '';

		if (isset($_SERVER['SERVER_NAME']))
		{
			$web_server_name = $_SERVER['SERVER_NAME'];
		}

		if ($web_server_name === 'cct.corp.intranet')
		{
			return mail($to, $subject_line, $message_body, $headers);
		}

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "This is not cct.corp.intranet. Email not actually sent.");

		return true;
	}
}
?>
