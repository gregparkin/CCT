<?php
/**
 * @package    CCT
 * @file       cct7_contacts.php
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
// Public Methods
//
// public function deleteContact($contact_id = 0)
// public function getContacts($system_id)
// public function getContactNetpin($system_id, $contact_netpin_no)
// public function getNetGroupMembers($contact_netpin_no)
// public function saveContacts($system_id, $lastid, $reboot, $approvals_required, $system_respond_by_date_num)
// public function updateContactStatus($contact_id, $contact_response_status)
// public function updateStatusByNetpin($system_id, $netpin_no, $status)
//
// public function getLogContacts($system_id, $netpin_no)
// public function putLogContacts($ticket_no, $system_id, $hostname, $netpin_no, $event_type, $event_message)
//

//
//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
    require_once('autoloader.php');  //!< @see includes/autoloader.php
}

/** @class cct7_contacts
 *  @brief This class contains all the methods for managing data in cct7_contacts and cct7_connections
 */
class cct7_contacts extends library
{
    var $data;                    // Magic variables
    var $ora;                     // Database connection object
    var $ora2;                    // For putLogContacts()
    var $error;                   // Error message when functions return false

    var $children_server = array();
    var $contacts        = array();
    var $cluster_list    = array();

    public $rows_affected;
    
    public $contact_id;
    public $system_id;
    public $contact_netpin_no;
    public $contact_insert_date_num;
    public $contact_insert_date_char;
	public $contact_insert_date_char2;
    public $contact_insert_cuid;
    public $contact_insert_name;

    public $contact_update_date_num;
    public $contact_update_date_char;
	public $contact_update_date_char2;
    public $contact_update_cuid;
    public $contact_update_name;

    public $contact_connection;
    public $contact_server_os;
    public $contact_server_usage;
    public $contact_work_group;
    public $contact_approver_fyi;
    public $contact_csc_banner;
    public $contact_apps_databases;

    public $contact_response_status;
    public $contact_response_date;
	public $contact_response_date2;
    public $contact_response_cuid;
    public $contact_response_name;

    public $contact_send_page;
    public $contact_send_email;

    //
    // Used in getContactsCSC()
    //
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

    //
	// Used in the traceContactSources($hostname) method down below.
	//
    public $computer_hostname;
    public $computer_lastid;
    public $computer_status;
    public $computer_os_lite;
	public $computer_city;
	public $computer_state;
	public $computer_timezone;
	public $computer_applications;
	public $computer_osmaint_weekly;
    public $computer_complex;
    public $computer_complex_lastid;
    public $computer_complex_name;
    public $computer_complex_parent_name;
    public $computer_complex_child_names;

	/** @fn    __construct()
	 *
	 *  @brief Class constructor - Create oracle object and setup some dynamic class variables
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

		$this->init_cct7_contacts();

		$this->csc_banner1  = 'Y'; // CSC Banner: Applications or Databases Desiring Notification (Not Hosted on this Server)
		$this->csc_banner2  = 'Y'; // csc_banner2|VARCHAR2|1||CSC Banner: Application Support
		$this->csc_banner3  = 'Y'; // csc_banner3|VARCHAR2|1||CSC Banner: Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)
		$this->csc_banner4  = 'Y'; // csc_banner4|VARCHAR2|1||CSC Banner: Infrastructure
		$this->csc_banner5  = 'Y'; // csc_banner5|VARCHAR2|1||CSC Banner: MiddleWare Support
		$this->csc_banner6  = 'Y'; // csc_banner6|VARCHAR2|1||CSC Banner: Database Support
		$this->csc_banner7  = 'Y'; // csc_banner7|VARCHAR2|1||CSC Banner: Development Database Support
		$this->csc_banner8  = 'Y'; // csc_banner8|VARCHAR2|1||CSC Banner: Operating System Support
		$this->csc_banner9  = 'Y'; // csc_banner9|VARCHAR2|1||CSC Banner: Applications Owning Database (DB Hosted on this Server, Owning App Is Not)
		$this->csc_banner10 = 'Y'; // csc_banner10|VARCHAR2|1||CSC Banner: Development Support

		if (PHP_SAPI === 'cli')
		{
			$this->user_cuid          = 'cctadm';
			$this->user_first_name    = 'Application';
			$this->user_last_name     = 'CCT';
			$this->user_name          = 'CCT Application';
			$this->user_email         = 'gregparkin58@gmail.com';
			$this->user_company       = 'CMP';
			$this->user_access_level  = 'admin';
			$this->user_timezone_name = 'America/Denver';

			$this->manager_cuid       = 'gparkin';
			$this->manager_first_name = 'Greg';
			$this->manager_last_name  = 'Parkin';
			$this->manager_name       = 'Greg Parkin';
			$this->manager_email      = 'gregparkin58@gmail.com';
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
				$this->user_company       = $_SESSION['user_company'];
				$this->user_access_level  = $_SESSION['user_access_level'];
				$this->user_timezone_name = $_SESSION['local_timezone_name'];

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
				$this->user_timezone_name = 'America/Denver';

				$this->manager_cuid       = 'gparkin';
				$this->manager_first_name = 'Greg';
				$this->manager_last_name  = 'Parkin';
				$this->manager_name       = 'Greg Parkin';
				$this->manager_email      = 'gregparkin58@gmail.com';
				$this->manager_company    = 'CMP';

				$this->is_debug_on        = 'Y';
			}

			$this->debug_start('cct7_contacts.html');
		}
	}

	/** @fn    __destruct()
     *
     *  @brief Destructor function called when no other references to this object can be found, or in any
     *         order during the shutdown sequence. The destructor will be called even if script execution
     *         is stopped using exit(). Calling exit() in a destructor will prevent the remaining shutdown
     *         routines from executing.
     *
     *  @brief Attempting to throw an exception from a destructor (called in the time of script termination)
     *         causes a fatal error.
     *
     *  @return void
     */
    public function __destruct()
    {
    }

	/** @fn    __set($name, $value)
     *
     *  @brief Setter function for $this->data
     *  @brief Example: $obj->first_name = 'Greg';
     *
     *  @param string $name is the key in the associated $data array
     *  @param string $value is the value in the assoicated $data array for the identified key
     *
     *  @return void
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

	/** @fn __get($name)
     *
     *  @brief Getter function for $this->data
     *  @brief echo $obj->first_name;
     *
     *  @param string $name is the key in the associated $data array
     *
     *  @return string or null
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
     *
     *  @brief Determine if item ($name) exists in the $this->data array
     *  @brief var_dump(isset($obj->first_name));
     *
     *  @param string $name is the key in the associated $data array
     *
     *  @return true or false
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

	/** @fn    __unset($name)
     *
     *  @brief Unset an item in $this->data assoicated by $name
     *  @brief unset($obj->name);
     *
     *  @param string $name is the key in the associated $data array
     *
     *  @return void
     */
    public function __unset($name)
    {
        unset($this->data[$name]);
    }

	/** @fn    init_cct7_contacts()
     *
     *  @brief Initialize storage variables for cct7_contacts
     */
    private function init_cct7_contacts()
    {
    	$this->rows_affected                = 0;  // Number of rows affected by update.

        $this->contact_id                   = 0;  // NUMBER|0|NOT NULL|PK: Unique record ID
        $this->system_id                    = 0;  // NUMBER|0||FK: cct7_systems.system_id - CASCADE DELETE
        $this->contact_netpin_no            = ''; // VARCHAR2|20||CSC/Net-Tool Pin number
        $this->contact_insert_date_num      = 0;  // NUMBER|0||Date of person who created this record
        $this->contact_insert_date_char     = '';
		$this->contact_insert_date_char2    = '';
        $this->contact_insert_cuid          = ''; // VARCHAR2|20||CUID of person who created this record
        $this->contact_insert_name          = ''; // VARCHAR2|200||Name of person who created this record

        $this->contact_update_date_num      = 0;  // NUMBER|0||Date of person who updated this record
        $this->contact_update_date_char     = '';
		$this->contact_update_date_char2    = '';
        $this->contact_update_cuid          = ''; // VARCHAR2|20||CUID of person who updated this record
        $this->contact_update_name          = ''; // VARCHAR2|200||Name of person who updated this record

        $this->contact_connection           = ''; // VARCHAR2|80||Grid label: Connections                    - Server connection list
        $this->contact_server_os            = ''; // VARCHAR2|80||Grid label: OS                             - Server OS list
        $this->contact_server_usage         = ''; // VARCHAR2|80||Grid Label: Status                         - Server OS status: Production, Test, etc.
        $this->contact_work_group           = ''; // VARCHAR2|80||Grid Label: Status                         - OS, APP, DBA, APP_DBA
        $this->contact_approver_fyi         = ''; // VARCHAR2|80||Grid Label: Notify Type                    - APPROVER or FYI
        $this->contact_csc_banner           = ''; // VARCHAR2|200||Grid Label: CSC Support Banners (Primary) - CSC Banner list
        $this->contact_apps_databases       = ''; // cVARCHAR2|200||Grid Label: Apps/DBMS                    - MAL and MDL list of applications and databases

        $this->contact_response_status      = ''; // VARCHAR2|20||Response Status: WAITING, APPROVED, REJECTED, RESCHEDULE
        $this->contact_response_date_num    = 0; // NUMBER|0||Response Date
		$this->contact_response_date_char   = ''; // NUMBER|0||Response Date
		$this->contact_response_date_char2  = ''; // NUMBER|0||Response Date
        $this->contact_response_cuid        = ''; // VARCHAR2|20||Response CUID of the net-group member that approved this work
        $this->contact_response_name        = ''; // |VARCHAR2|200||Response Name of the net-group member that approved this work

        $this->contact_send_page            = 'N'; // VARCHAR2|10||Do they want a page?   Y/N
        $this->contact_send_email           = '';  // contact_send_email|VARCHAR2|10||Do they want an email? Y/N

		//
		// Used in the traceContactSources($hostname) method.
		//
		$this->computer_hostname            = '';
		$this->computer_lastid              = 0;
		$this->computer_status              = '';
		$this->computer_os_lite             = '';
		$this->computer_city                = '';
		$this->computer_state               = '';
		$this->computer_timezone            = '';
		$this->computer_applications        = '';
		$this->computer_osmaint_weekly      = '';
		$this->computer_complex             = '';
		$this->computer_complex_lastid      = 0;
		$this->computer_complex_name        = '';
		$this->computer_complex_parent_name = '';
		$this->computer_complex_child_names = '';
	}

	/**
	 * @param object $c
	 * @param int    $system_id
	 * @param int    $contact_id
	 *
	 * @return bool
	 */
	public function addContactCCT6($c, $system_id, &$contact_id)
	{
		// Get a new record number from the sequenct table for cct6_contacts
		$contact_id = $this->ora->next_seq('cct6_contactsseq');

		// Build the $insert SQL command
		$insert = "insert into cct6_contacts (" .
			"contact_id, system_id, contact_response_status, contact_insert_date, contact_insert_cuid, contact_insert_name, " .
			"contact_csc_banner, contact_app_acronym, contact_group_type, contact_notify_type, contact_source, contact_override, " .
			"contact_cuid, contact_last_name, contact_first_name, contact_nick_name, contact_middle, contact_name, " .
			"contact_job_title, contact_email, contact_work_phone, contact_pager, contact_street, contact_city, contact_state, " .
			"contact_rc, contact_company, contact_tier1, contact_tier2, contact_tier3, contact_status, " .
			"contact_change_date, contact_ctl_cuid, contact_mgr_cuid ) values ( ";

		$this->makeInsertINT(     $insert, $contact_id,                 true);
		$this->makeInsertINT(     $insert, $system_id,                  true);
		$this->makeInsertCHAR(    $insert, $c->contact_response_status, true);
		$this->makeInsertDateTIME($insert, $c->contact_insert_date,     true);
		$this->makeInsertCHAR(    $insert, $c->contact_insert_cuid,     true);
		$this->makeInsertCHAR(    $insert, $c->contact_insert_name,     true);
		$this->makeInsertCHAR(    $insert, $c->contact_csc_banner,      true);
		$this->makeInsertCHAR(    $insert, $c->contact_app_acronym,     true);
		$this->makeInsertCHAR(    $insert, $c->contact_group_type,      true);
		$this->makeInsertCHAR(    $insert, $c->contact_notify_type,     true);
		$this->makeInsertCHAR(    $insert, $c->contact_source,          true);
		$this->makeInsertCHAR(    $insert, $c->contact_override,        true);
		$this->makeInsertCHAR(    $insert, $c->contact_cuid,            true);
		$this->makeInsertCHAR(    $insert, $c->contact_last_name,       true);
		$this->makeInsertCHAR(    $insert, $c->contact_first_name,      true);
		$this->makeInsertCHAR(    $insert, $c->contact_nick_name,       true);
		$this->makeInsertCHAR(    $insert, $c->contact_middle,          true);
		$this->makeInsertCHAR(    $insert, $c->contact_name,            true);
		$this->makeInsertCHAR(    $insert, $c->contact_job_title,       true);
		$this->makeInsertCHAR(    $insert, $c->contact_email,           true);
		$this->makeInsertCHAR(    $insert, $c->contact_work_phone,      true);
		$this->makeInsertCHAR(    $insert, $c->contact_pager,           true);
		$this->makeInsertCHAR(    $insert, $c->contact_street,          true);
		$this->makeInsertCHAR(    $insert, $c->contact_city,            true);
		$this->makeInsertCHAR(    $insert, $c->contact_state,           true);
		$this->makeInsertCHAR(    $insert, $c->contact_rc,              true);
		$this->makeInsertCHAR(    $insert, $c->contact_company,         true);
		$this->makeInsertCHAR(    $insert, $c->contact_tier1,           true);
		$this->makeInsertCHAR(    $insert, $c->contact_tier2,           true);
		$this->makeInsertCHAR(    $insert, $c->contact_tier3,           true);
		$this->makeInsertCHAR(    $insert, $c->contact_status,          true);
		$this->makeInsertDateTIME($insert, $c->contact_change_date,     true);
		$this->makeInsertCHAR(    $insert, $c->contact_ctl_cuid,        true);
		$this->makeInsertCHAR(    $insert, $c->contact_mgr_cuid,        false);

		$insert .= 	" )";

		if ($this->ora->sql($insert) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin",
								   __FILE__, __LINE__);
			return false;
		}

		$this->debug4(__FILE__, __FUNCTION__, __LINE__, "commit and return true");
		$this->ora->commit();

		return true;
	}

	/** @fn     deleteContact($contact_id)
     *
     *  @brief  Delete contact record identified by $contact_id from cct7_contacts
     *
     *  @param  int $contact_id is the primary key in cct7_contacts.
     *
     *  @return true or false, true meaning success
     */
    public function deleteContact($contact_id = 0)
    {
        $query = "delete from cct7_contacts where contact_id = " . $contact_id;

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

	public function getContactByContactId($contact_id)
	{
		$this->init_cct7_contacts();

		$query  = "select ";
		$query .= "  contact_id, ";
		$query .= "  system_id, ";
		$query .= "  contact_netpin_no, ";
		$query .= "  contact_insert_date, ";
		$query .= "  contact_insert_cuid, ";
		$query .= "  contact_insert_name, ";
		$query .= "  contact_update_date, ";
		$query .= "  contact_update_cuid, ";
		$query .= "  contact_update_name, ";
		$query .= "  contact_connection, ";
		$query .= "  contact_server_os, ";
		$query .= "  contact_server_usage, ";
		$query .= "  contact_work_group, ";
		$query .= "  contact_approver_fyi, ";
		$query .= "  contact_csc_banner, ";
		$query .= "  contact_apps_databases, ";
		$query .= "  contact_respond_by_date, ";
		$query .= "  contact_response_status, ";
		$query .= "  contact_response_date, ";
		$query .= "  contact_response_cuid, ";
		$query .= "  contact_response_name, ";
		$query .= "  contact_send_page, ";
		$query .= "  contact_send_email ";
		$query .= "from ";
		$query .= "  cct7_contacts ";
		$query .= "where ";
		$query .= "  contact_id = " . $contact_id . " ";
		$query .= "order by ";
		$query .= "  contact_netpin_no";

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		if ($this->ora->fetch() == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->error = sprintf("No record available where contact_id = %d", $contact_id);
			return false;
		}

		$this->contact_id                   = $this->ora->contact_id;
		$this->system_id                    = $this->ora->system_id;
		$this->contact_netpin_no            = $this->ora->contact_netpin_no;

		$this->contact_insert_date_num      = $this->ora->contact_insert_date;

		$this->contact_insert_date_char     = $this->gmt_to_format(
			$this->ora->contact_insert_date,
			'm/d/Y H:i T',
			$this->user_timezone_name);

		$this->contact_insert_date_char2    = $this->gmt_to_format(
			$this->ora->contact_insert_date,
			'm/d/Y H:i',
			'America/Denver');

		$this->contact_insert_cuid          = $this->ora->contact_insert_cuid;
		$this->contact_insert_name          = $this->ora->contact_insert_name;

		$this->contact_update_date_num      = $this->ora->contact_update_date;

		$this->contact_update_date_char     = $this->gmt_to_format(
			$this->ora->contact_update_date,
			'm/d/Y H:i T',
			$this->user_timezone_name);

		$this->contact_update_date_char2    = $this->gmt_to_format(
			$this->ora->contact_update_date,
			'm/d/Y H:i',
			'America/Denver');

		$this->contact_update_cuid          = $this->ora->contact_update_cuid;
		$this->contact_update_name          = $this->ora->contact_update_name;

		$this->contact_connection           = $this->ora->contact_connection;
		$this->contact_server_os            = $this->ora->contact_server_os;
		$this->contact_server_usage         = $this->ora->contact_server_usage;
		$this->contact_work_group           = $this->ora->contact_work_group;
		$this->contact_approver_fyi         = $this->ora->contact_approver_fyi;
		$this->contact_csc_banner           = $this->ora->contact_csc_banner;
		$this->contact_apps_databases       = $this->ora->contact_apps_databases;

		$this->contact_respond_by_date_num  = $this->ora->contact_respond_by_date;

		$this->contact_respond_by_date_char = $this->gmt_to_format(
			$this->ora->contact_respond_by_date,
			'm/d/Y T',
			$this->user_timezone_name);

		$this->contact_respond_by_date_char2 = $this->gmt_to_format(
			$this->ora->contact_respond_by_date,
			'm/d/Y',
			'America/Denver');

		$this->contact_response_status      = $this->ora->contact_response_status;
		$this->contact_response_date_num    = $this->ora->contact_response_date;

		$this->contact_response_date_char   = $this->gmt_to_format(
			$this->ora->contact_response_date,
			'm/d/Y H:i T',
			$this->user_timezone_name);

		$this->contact_response_date_char2  = $this->gmt_to_format(
			$this->ora->contact_response_date,
			'm/d/Y H:i',
			'America/Denver');

		$this->contact_response_cuid        = $this->ora->contact_response_cuid;
		$this->contact_response_name        = $this->ora->contact_response_name;

		$this->contact_send_page            = $this->ora->contact_send_page;
		$this->contact_send_email           = $this->ora->contact_send_email;

		return true;
	}

	/** @fn     getContacts($system_id)
     *
     *  @brief  Retrieve list of contacts for $system_id from cct7_contacts
     *
     *  @param  int $system_id is the cct7_systems.system_id number.
     *
     *  @return object or null - data_node object containing a structured link list of contacts and connection information.
     */
    public function getContacts($system_id)
    {
        $top = $p = null;

        $query  = "select ";
        $query .= "  contact_id, ";
        $query .= "  system_id, ";
        $query .= "  contact_netpin_no, ";
        $query .= "  contact_insert_date, ";
        $query .= "  contact_insert_cuid, ";
        $query .= "  contact_insert_name, ";
        $query .= "  contact_update_date, ";
        $query .= "  contact_update_cuid, ";
        $query .= "  contact_update_name, ";
        $query .= "  contact_connection, ";
        $query .= "  contact_server_os, ";
        $query .= "  contact_server_usage, ";
        $query .= "  contact_work_group, ";
        $query .= "  contact_approver_fyi, ";
        $query .= "  contact_csc_banner, ";
        $query .= "  contact_apps_databases, ";
        $query .= "  contact_respond_by_date, ";
        $query .= "  contact_response_status, ";
        $query .= "  contact_response_date, ";
        $query .= "  contact_response_cuid, ";
        $query .= "  contact_response_name, ";
        $query .= "  contact_send_page, ";
        $query .= "  contact_send_email ";
        $query .= "from ";
        $query .= "  cct7_contacts ";
        $query .= "where ";
        $query .= "  system_id = " . $system_id . " ";
        $query .= "order by ";
        $query .= "  contact_netpin_no";

        if ($this->ora->sql2($query) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                $this->ora->sql_statement, $this->ora->dbErrMsg);
            return null;
        }
        
        while ($this->ora->fetch())
        {
            if ($top == null)
            {
                $top = new data_node();
                $p = $top;
            }
            else
            {
                $p->next = new data_node();
                $p = $p->next;
            }

            $p->contact_id                   = $this->ora->contact_id;
            $p->system_id                    = $this->ora->system_id;
            $p->contact_netpin_no            = $this->ora->contact_netpin_no;

            $p->contact_insert_date_num      = $this->ora->contact_insert_date;
            $p->contact_insert_date_char     = $this->gmt_to_format($this->ora->contact_insert_date, 'm/d/Y H:i T', $this->user_timezone_name);
            $p->contact_insert_cuid          = $this->ora->contact_insert_cuid;
            $p->contact_insert_name          = $this->ora->contact_insert_name;

            $p->contact_update_date_num      = $this->ora->contact_update_date;
            $p->contact_insert_date_char     = $this->gmt_to_format($this->ora->contact_update_date, 'm/d/Y H:i T', $this->user_timezone_name);
            $p->contact_update_cuid          = $this->ora->contact_update_cuid;
            $p->contact_update_name          = $this->ora->contact_update_name;

            $p->contact_connection           = $this->ora->contact_connection;
            $p->contact_server_os            = $this->ora->contact_server_os;
            $p->contact_server_usage         = $this->ora->contact_server_usage;
            $p->contact_work_group           = $this->ora->contact_work_group;
            $p->contact_approver_fyi         = $this->ora->contact_approver_fyi;
            $p->contact_csc_banner           = $this->ora->contact_csc_banner;
            $p->contact_apps_databases       = $this->ora->contact_apps_databases;

            $p->contact_respond_by_date_num  = $this->ora->contact_respond_by_date;
            $p->contact_respond_by_date_char = $this->gmt_to_format($this->ora->contact_respond_by_date, 'm/d/Y T', $this->user_timezone_name);
            $p->contact_response_status      = $this->ora->contact_response_status;
            $p->contact_response_date        = $this->gmt_to_format($this->ora->contact_response_date, 'm/d/Y T',   $this->user_timezone_name);
            $p->contact_response_cuid        = $this->ora->contact_response_cuid;
            $p->contact_response_name        = $this->ora->contact_response_name;

            $p->contact_send_page            = $this->ora->contact_send_page;
            $p->contact_send_email           = $this->ora->contact_send_email;
        }

        if ($top == null)
            $this->error = "No data available in cct7_contacts where system_id = " . $system_id;

        return $top;
    }

    /** @fn     getContactNetpin($system_id, $contact_netpin_no)
     *
     *  @brief  Retrieve contact/netpin for $system_id and $contact_netpin_no from cct7_contacts
     *
     *  @param  int    $system_id is the cct7_systems.system_id number.
     *  @param  string $contact_netpin_no is the netpin number for this server.
     *
     *  @return bool - true we got the record, false means no data found.
     */
    public function getContactNetpin($system_id, $contact_netpin_no)
    {
        $this->init_cct7_contacts();

        $query  = "select ";
        $query .= "  contact_id, ";
        $query .= "  system_id, ";
        $query .= "  contact_netpin_no, ";
        $query .= "  contact_insert_date, ";
        $query .= "  contact_insert_cuid, ";
        $query .= "  contact_insert_name, ";
        $query .= "  contact_update_date, ";
        $query .= "  contact_update_cuid, ";
        $query .= "  contact_update_name, ";
        $query .= "  contact_connection, ";
        $query .= "  contact_server_os, ";
        $query .= "  contact_server_usage, ";
        $query .= "  contact_work_group, ";
        $query .= "  contact_approver_fyi, ";
        $query .= "  contact_csc_banner, ";
        $query .= "  contact_apps_databases, ";
        $query .= "  contact_respond_by_date, ";
        $query .= "  contact_response_status, ";
        $query .= "  contact_response_date, ";
        $query .= "  contact_response_cuid, ";
        $query .= "  contact_response_name, ";
        $query .= "  contact_send_page, ";
        $query .= "  contact_send_email ";
        $query .= "from ";
        $query .= "  cct7_contacts ";
        $query .= "where ";
        $query .= "  system_id = " . $system_id . " and ";
        $query .= "  contact_netpin_no = '" . $contact_netpin_no . "' ";
        $query .= "order by ";
        $query .= "  contact_netpin_no";

        if ($this->ora->sql2($query) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                                   $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
        }

        if ($this->ora->fetch() == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->error = sprintf("No record available where system_id = %d and contact_netpin_no = %s",
                                   $system_id, $contact_netpin_no);
            return false;
        }

        $this->contact_id                   = $this->ora->contact_id;
        $this->system_id                    = $this->ora->system_id;
        $this->contact_netpin_no            = $this->ora->contact_netpin_no;

        $this->contact_insert_date_num      = $this->ora->contact_insert_date;
        $this->contact_insert_date_char     = $this->gmt_to_format($this->ora->contact_insert_date, 'm/d/Y H:i T', $this->user_timezone_name);
        $this->contact_insert_cuid          = $this->ora->contact_insert_cuid;
        $this->contact_insert_name          = $this->ora->contact_insert_name;

        $this->contact_update_date_num      = $this->ora->contact_update_date;
        $this->contact_insert_date_char     = $this->gmt_to_format($this->ora->contact_update_date, 'm/d/Y H:i T', $this->user_timezone_name);
        $this->contact_update_cuid          = $this->ora->contact_update_cuid;
        $this->contact_update_name          = $this->ora->contact_update_name;

        $this->contact_connection           = $this->ora->contact_connection;
        $this->contact_server_os            = $this->ora->contact_server_os;
        $this->contact_server_usage         = $this->ora->contact_server_usage;
        $this->contact_work_group           = $this->ora->contact_work_group;
        $this->contact_approver_fyi         = $this->ora->contact_approver_fyi;
        $this->contact_csc_banner           = $this->ora->contact_csc_banner;
        $this->contact_apps_databases       = $this->ora->contact_apps_databases;

		$this->contact_respond_by_date_num  = $this->ora->contact_respond_by_date;
		$this->contact_respond_by_date_char = $this->gmt_to_format($this->ora->contact_respond_by_date, 'm/d/Y T', $this->user_timezone_name);
        $this->contact_response_status      = $this->ora->contact_response_status;
        $this->contact_response_date_num    = $this->ora->contact_response_date;
        $this->contact_response_date_char   = $this->gmt_to_format($this->ora->contact_response_date, 'm/d/Y H:i T',    $this->user_timezone_name);
        $this->contact_response_cuid        = $this->ora->contact_response_cuid;
        $this->contact_response_name        = $this->ora->contact_response_name;

        $this->contact_send_page            = $this->ora->contact_send_page;
        $this->contact_send_email           = $this->ora->contact_send_email;

        return true;
    }

    /**
     * @fn    getNetGroupMembers($contact_netpin_no)
     *
     * @brief Return a string containing the net-pin members or subscriber list members.
     *
     * @param string $contact_netpin_no
     *
     * @return object link list of netgroup members
     */
    public function getNetGroupMembers($contact_netpin_no)
    {
        $top = $p = null;

        function startsWith($haystack, $needle)
        {
            $length = strlen($needle);
            return (substr($haystack, 0, $length) === $needle);
        }

        $needle = "SUB";
        $length = strlen($needle);

        if (substr($contact_netpin_no, 0, $length) === $needle)
        {
            // cct7_subscriber_members
            // member_id|NUMBER|0|NOT NULL|PK: Unique Record ID
            // group_id|VARCHAR2|20||FK: cct7_subscriber_groups
            // create_date|NUMBER|0||GMT date record was created
            // member_cuid|VARCHAR2|20||Member CUID
            // member_name|VARCHAR2|200||Member NAME

            $query  = "select distinct ";
            $query .= "  m.mnet_cuid      as mnet_cuid, ";
            $query .= "  m.mnet_name      as mnet_name, ";
            $query .= "  m.mnet_email     as mnet_email ";
            $query .= "  from ";
            $query .= "  cct7_subscriber_members n, ";
            $query .= "  cct7_mnet m ";
            $query .= "  where ";
            $query .= "  n.group_id = '" . $contact_netpin_no . "' and ";
            $query .= "  m.mnet_cuid = n.member_cuid ";
            $query .= "order by ";
            $query .= "  m.mnet_cuid";

            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

            if ($this->ora->sql2($query) == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                                       $this->ora->sql_statement, $this->ora->dbErrMsg);
                return null;
            }

            while ($this->ora->fetch())
            {
                if ($top == null)
                {
                    $top = new data_node();
                    $p   = $top;
                }
                else
                {
                    $p->next = new data_node();
                    $p       = $p->next;
                }

                $p->mnet_cuid      = $this->ora->mnet_cuid;
                $p->mnet_name      = $this->ora->mnet_name;
                $p->mnet_email     = $this->ora->mnet_email;
                $p->oncall_primary = "";
                $p->oncall_backup  = "";
            }
        }
        else
        {
            $query  = "select distinct ";
            $query .= "  n.oncall_primary as oncall_primary, ";
            $query .= "  n.oncall_backup  as oncall_backup, ";
            $query .= "  m.mnet_cuid      as mnet_cuid, ";
            $query .= "  m.mnet_name      as mnet_name, ";
            $query .= "  m.mnet_email     as mnet_email ";
            $query .= "  from ";
            $query .= "  cct7_netpin_to_cuid n, ";
            $query .= "  cct7_mnet m ";
            $query .= "  where ";
            $query .= "  n.net_pin_no = '" . $contact_netpin_no . "' and ";
            $query .= "  m.mnet_cuid = n.user_cuid ";
            $query .= "order by ";
            $query .= "  m.mnet_cuid";

            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

            if ($this->ora->sql2($query) == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                                       $this->ora->sql_statement, $this->ora->dbErrMsg);
                return null;
            }

            while ($this->ora->fetch())
            {
                if ($top == null)
                {
                    $top = new data_node();
                    $p   = $top;
                }
                else
                {
                    $p->next = new data_node();
                    $p       = $p->next;
                }

                $p->mnet_cuid      = $this->ora->mnet_cuid;
                $p->mnet_name      = $this->ora->mnet_name;
                $p->mnet_email     = $this->ora->mnet_email;
                $p->oncall_primary = $this->ora->oncall_primary;
                $p->oncall_backup  = $this->ora->oncall_backup;
            }
        }

        return $top;
    }

    /**
     * @fn    getLogContacts($system_id, $netpin_no)
     *
     * @brief Return a list of log entries for this server's netpin contact.
     *
     * @param int    $system_id - Record ID to cct7_systems
     * @param string $netpin_no - Netpin No.
     *
     * @return object
     */
    public function getLogContacts($system_id, $netpin_no)
    {
        //
        // cct7_log_contacts
        // ticket_no|VARCHAR2|20|NOT NULL|CCT ticket number.
        // system_id|NUMBER|0|NOT NULL|FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE
        // hostname|VARCHAR2|255|NOT NULL|Hostname for this log entry
        // netpin_no|VARCHAR2|20|NOT NULL|CSC/Net-Tool Pin No.
        // event_date|NUMBER|0||Event Date (GMT)
        // event_cuid|VARCHAR2|20||Event Owner CUID
        // event_name|VARCHAR2|200||Event Owner Name
        // event_type|VARCHAR2|20||Event type
        // event_message|VARCHAR2|4000||Event message
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
        $query .= "  system_id = " . $system_id . " and ";
        $query .= "  netpin_no = '" . $netpin_no . "' ";
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
            $p->system_id       = $this->ora->system_id;
            $p->hostname        = $this->ora->hostname;
            $p->netpin_no       = $this->ora->netpin_no;
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
     * @fn    putLogContacts($ticket_no, $system_id, $netpin_no, $event_type, $event_message)
     *
     * @brief Create a contact log entry in cct7_log_systems.
     * @brief cct7_log_systems contains both system and contact log entries.
     *
     * @param string $ticket_no     - CCT ticket number
     * @param int    $system_id     - Record ID to cct7_systems
     * @param string $hostname      - Hostname
     * @param string $netpin_no     - Netpin No.
     * @param string $event_type    - Event Type: SUBMIT, EMAIL, etc.
     * @param string $event_message - Message body
     *
     * @return bool true or false
     */
    public function putLogContacts($ticket_no, $system_id, $hostname, $netpin_no, $event_type, $event_message)
    {
        if ($this->ora2 == null)
            $this->ora2 = new oracle();

        if (strlen($netpin_no) == 0)
        	$netpin_no = "unknown";

        //
        // cct7_log_contacts
        // ticket_no|VARCHAR2|20|NOT NULL|CCT ticket number.
        // system_id|NUMBER|0|NOT NULL|FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE
        // hostname|VARCHAR2|255|NOT NULL|Hostname for this log entry
        // netpin_no|VARCHAR2|20|NOT NULL|CSC/Net-Tool Pin No.
        // event_date|NUMBER|0||Event Date (GMT)
        // event_cuid|VARCHAR2|20||Event Owner CUID
        // event_name|VARCHAR2|200||Event Owner Name
        // event_type|VARCHAR2|20||Event type
        // event_message|VARCHAR2|4000||Event message
        //
        $rc = $this->ora2
            ->insert("cct7_log_contacts")
            ->column("ticket_no")        // ticket_no    |VARCHAR2|20  |NOT NULL|CCT Ticket
            ->column("system_id")        // system_id    |NUMBER  |0   |        |FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE
            ->column("hostname")         // hostname     |VARCHAR2|255 |NOT NULL|Hostname for this log entry
            ->column("netpin_no")        // netpin_no    |VARCHAR2(20  |NOT NULL|CSC/Net-Tool Pin No.
            ->column("event_date")       // event_date   |NUMBER  |0   |        |Event Date (GMT)
            ->column("event_cuid")       // event_cuid   |VARCHAR2|20  |        |Event Owner CUID
            ->column("event_name")       // event_name   |VARCHAR2|200 |        |Event Owner Name
            ->column("event_type")       // event_type   |VARCHAR2|20  |        |Event type
            ->column("event_message")    // event_message|VARCHAR2|4000|        |Event message
            ->value("char",  $ticket_no)
            ->value("int",   $system_id)
            ->value("char",  $hostname)
            ->value("char",  $netpin_no)
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
		// Change the contact_update_date in cct7_contacts for $system_id so send_notifications.php can send out email
		// for this log event.
		//
		$rc = $this->ora2
			->update("cct7_contacts")
			->set("int",    "contact_update_date",     $this->now_to_gmt_utime())
			->set("char",   "contact_update_cuid",     $this->user_cuid)
			->set("char",   "contact_update_name",     $this->user_name)
			->where("int",  "system_id", "=",          $system_id)
			->execute();

		if ($rc == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora2->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora2->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora2->sql_statement, $this->ora2->dbErrMsg);
			return false;
		}

        $this->ora->commit();

        return true;
    }

	/** @fn    traceContactSources($hostname)
	 *
	 *  @brief Used by the server_contacts.php to discover all the contacts that CCT will use when
	 *         notifications are gathered in the database. This function uses a lot of the code
	 *         found in the saveContacts() method except it does not save any information to the
	 *         database. Once data is retrieved it must parse through all the data to get at the
	 *         information. See the getContacts($hostname) function in the server_contacts.php
	 *         for how it done. You can also look at the saveContacts() method below.
	 *
	 *  @param string $hostname
	 *
	 *  @return bool where true means we have data, false means failure
	 */
    public function traceContactSources($hostname)
	{
		if ($this->ora2 == null)
			$this->ora2 = new oracle();

		$computer_hostname = strtolower($hostname);

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "computer_hostname = %s", $computer_hostname);

		$this->children_server = array();
		$this->contacts        = array();
		$this->cluster_list    = array();

		//
		// Get the cct7_computers server record
		//
		$query  = "select ";
		$query .= "  computer_hostname, ";
		$query .= "  computer_lastid, ";
		$query .= "  computer_status, ";
		$query .= "  computer_os_lite, ";
		$query .= "  computer_city, ";
		$query .= "  computer_state, ";
		$query .= "  computer_timezone, ";
		$query .= "  computer_applications, ";
		$query .= "  computer_osmaint_weekly, ";
		$query .= "  computer_complex, ";
		$query .= "  computer_complex_lastid, ";
		$query .= "  computer_complex_name, ";
		$query .= "  computer_complex_parent_name, ";
		$query .= "  computer_complex_child_names ";
		$query .= "from ";
		$query .= "  cct7_computers ";
		$query .= "where ";
		$query .= sprintf("  lower(computer_hostname) = '%s'", $computer_hostname);

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		if ($this->ora->fetch() == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		//
		// Make this data accessible to calling program.
		//
		$this->computer_hostname             = $this->ora->computer_hostname;
		$this->computer_lastid               = $this->ora->computer_lastid;
		$this->computer_status               = $this->ora->computer_status;
		$this->computer_os_lite              = $this->ora->computer_os_lite;
		$this->computer_city                 = $this->ora->computer_city;
		$this->computer_state                = $this->ora->computer_state;
		$this->computer_timezone             = $this->ora->computer_timezone;
		$this->computer_applications         = $this->ora->computer_applications;
		$this->computer_osmaint_weekly       = $this->ora->computer_osmaint_weekly;
		$this->computer_complex              = $this->ora->computer_complex;
		$this->computer_complex_lastid       = $this->ora->computer_complex_lastid;
		$this->computer_complex_name         = $this->ora->computer_complex_name;
		$this->computer_complex_parent_name  = $this->ora->computer_complex_parent_name;
		$this->computer_complex_child_names  = $this->ora->computer_complex_child_names;

		//
		// Grab the timezone information for this server from cct7_timezone
		//
		$query  = "select ";
		$query .= "  timezone ";
		$query .= "from ";
		$query .= "  cct7_timezone ";
		$query .= "where ";
		$query .= sprintf("  city = '%s' and state = '%s'",
			$this->ora->computer_city, $this->ora->computer_state);

		if ($this->ora2->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		if ($this->ora2->fetch() == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
		}
		else
		{
			$this->computer_timezone = $this->ora2->timezone;
		}

		//
		// Begin gathering contact information.
		//
		$node = new data_node();
		$node->computer_lastid   = $this->ora->computer_lastid;
		$node->computer_hostname = $this->ora->computer_hostname;
		$node->computer_status   = $this->ora->computer_status;

		if ($this->ora->computer_complex == "Y" && strlen($this->ora->computer_os_lite) == 0)
		{
			$node->computer_os_lite = "COMPLEX";
		}
		else
		{
			$node->computer_os_lite = $this->ora->computer_os_lite;
		}

		$node->computer_complex             = $this->ora->computer_complex;
		$node->computer_complex_lastid      = $this->ora->computer_complex_lastid;
		$node->computer_complex_name        = $this->ora->computer_complex_name;
		$node->computer_complex_parent_name = $this->ora->computer_complex_parent_name;
		$node->computer_complex_child_names = $this->ora->computer_complex_child_names;
		$node->connections                  = $this->ora->computer_hostname;
		$node->contacts                     = NULL;
		$node->connections                  = $this->connectionString(
			$this->ora->computer_complex_name,
			$this->ora->computer_complex_parent_name,
			$this->ora->computer_hostname);

		$this->children_server[$this->ora->computer_hostname] = $node;

		$this->debug1(  __FILE__, __FUNCTION__, __LINE__, "children_server");
		$this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $this->children_server);

		$this->childrenServers($this->ora->computer_complex_child_names);

		//
		// Add virtual servers (vmware)
		//
		foreach ($this->children_server as $child)
		{
			$this->debug1(  __FILE__, __FUNCTION__, __LINE__, "foreach(children_server as child)");
			$this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $child);

			$query  = "select connected_name from cct7_virtual_servers where name = upper('" . $child->computer_hostname . "')";
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
				// Record the cluster name
				$this->debug1(  __FILE__, __FUNCTION__, __LINE__, "cluster_list[%s] = true", $this->ora->connected_name);
				$this->cluster_list[$this->ora->connected_name] = true;

				$this->debug1(  __FILE__, __FUNCTION__, __LINE__, "children_server");
				$this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $this->children_server);
			}
		}

		$this->debug1(  __FILE__, __FUNCTION__, __LINE__, "cluster_list");
		$this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $this->cluster_list);

		//
		// Add any new VMWARE servers to our main server list
		//
		foreach($this->cluster_list as $cluster_name => $val)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Adding vmware servers for cluster name: %s", $cluster_name);

			$query = "select distinct " .
				"c.computer_lastid, " .
				"to_char(c.computer_last_update,  'MM/DD/YYYY HH24:MI'), " .
				"to_char(c.computer_install_date, 'MM/DD/YYYY HH24:MI'), " .
				"c.computer_systemname, " .
				"c.computer_hostname, " .
				"c.computer_operating_system, " .
				"c.computer_os_lite, " .
				"c.computer_status, " .
				"c.computer_status_description, " .
				"c.computer_description, " .
				"c.computer_nature, " .
				"c.computer_platform, " .
				"c.computer_type, " .
				"c.computer_clli, " .
				"c.computer_clli_fullname, " .
				"c.computer_timezone, " .
				"c.computer_building, " .
				"c.computer_address, " .
				"c.computer_city, " .
				"c.computer_state, " .
				"c.computer_floor_room, " .
				"c.computer_grid_location, " .
				"c.computer_lease_purchase, " .
				"c.computer_serial_no, " .
				"c.computer_asset_tag, " .
				"c.computer_model_category, " .
				"c.computer_model_no, " .
				"c.computer_model, " .
				"c.computer_model_mfg, " .
				"c.computer_cpu_type, " .
				"c.computer_cpu_count, " .
				"c.computer_cpu_speed, " .
				"c.computer_memory_mb, " .
				"c.computer_ip_address, " .
				"c.computer_domain, " .
				"c.computer_hostname_domain, " .
				"c.computer_dmz, " .
				"c.computer_ewebars_title, " .
				"c.computer_ewebars_status, " .
				"c.computer_backup_format, " .
				"c.computer_backup_nodename, " .
				"c.computer_backup_program, " .
				"c.computer_backup_server, " .
				"c.computer_netbackup, " .
				"c.computer_complex, " .
				"c.computer_complex_lastid, " .
				"c.computer_complex_name, " .
				"c.computer_complex_parent_name, " .
				"c.computer_complex_child_names, " .
				"c.computer_complex_partitions, " .
				"c.computer_service_guard, " .
				"c.computer_os_group_contact, " .
				"c.computer_cio_group, " .
				"c.computer_managing_group, " .
				"c.computer_contract, " .
				"c.computer_contract_ref, " .
				"c.computer_contract_status, " .
				"c.computer_contract_status_type, " .
				"to_char(c.computer_contract_date, 'MM/DD/YYYY HH24:MI'), " .
				"c.computer_ibm_supported, " .
				"c.computer_gold_server, " .
				"c.computer_slevel_objective, " .
				"c.computer_slevel_score, " .
				"c.computer_slevel_colors, " .
				"c.computer_special_handling, " .
				"c.computer_applications, " .
				"c.computer_osmaint_weekly, " .
				"c.computer_osmaint_monthly, " .
				"c.computer_osmaint_quarterly, " .
				"c.computer_csc_os_banners, " .
				"c.computer_csc_pase_banners, " .
				"c.computer_csc_dba_banners, " .
				"c.computer_csc_fyi_banners, " .
				"c.computer_disk_array_alloc_kb, " .
				"c.computer_disk_array_used_kb, " .
				"c.computer_disk_array_free_kb, " .
				"c.computer_disk_local_alloc_kb, " .
				"c.computer_disk_local_used_kb, " .
				"c.computer_disk_local_free_kb " .
				"from cct7_computers c";

			$query .= ", cct7_virtual_servers v ";
			$query .= "where ";
			$query .= "  v.connected_name = '" . $cluster_name . "' and ";
			$query .= "  v.connection_type = 'ESX SERVER TO VMWARE CLUSTER' and ";
			$query .= "  c.computer_lastid = v.lastid ";
			$query .= "order by ";
			$query .= "  c.computer_hostname";

			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

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
				$node = new data_node();
				$node->computer_lastid   = $this->ora->computer_lastid;
				$node->computer_hostname = $this->ora->computer_hostname;
				$node->computer_status   = $this->ora->computer_status;

				if ($this->ora->computer_complex == "Y" && strlen($this->ora->computer_os_lite) == 0)
				{
					$node->computer_os_lite = "COMPLEX";
				}
				else
				{
					$node->computer_os_lite = $this->ora->computer_os_lite;
				}

				$node->computer_complex             = $this->ora->computer_complex;
				$node->computer_complex_lastid      = $this->ora->computer_complex_lastid;
				$node->computer_complex_name        = $this->ora->computer_complex_name;
				$node->computer_complex_parent_name = $this->ora->computer_complex_parent_name;
				$node->computer_complex_child_names = $this->ora->computer_complex_child_names;

				//$node->connections = connectionString($ora->computer_complex_name, $ora->computer_complex_parent_name, $ora->computer_hostname);
				//$node->connections = connectionString($ora->computer_complex_name, $cluster_name, $ora->computer_hostname);

				$node->connections = $cluster_name . "->" . $this->ora->computer_hostname;

				$node->contacts = NULL;

				$this->children_server[$this->ora->computer_hostname] = $node;;
			}
		}

		$this->debug1(  __FILE__, __FUNCTION__, __LINE__, "children_server");
		$this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $this->children_server);

		foreach ($this->children_server as $child)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "foreach(children_server as child)");
			$this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $child);

			$this->getContactsCSC($child);
			$this->getContactsSubscribers($child);
		}

		return true;  // There be whales here captain!
	}

    /** @fn    saveContacts($system_id, $lastid, $reboot, $approvals_required, $system_respond_by_date_num)
     *
     *  @brief Find the contact and connection information for this $lastid. Create the cct7_contacts records.
     *
     *  @param int    $system_id                 is the cct7_systems.system_id number.
     *
     *  @parma int    $lastid                    is the asset manager LASTID number for the hostname record.
     *  @parma string $reboot                    is the Y or N to indicate if this server needs to be rebooted.
     *
     *  @param string $approvals_required        is Y or N to indicate whether contacts need to approve this work.
	 *  @param string $exclude_virtual_contacts  is Y or N to indicate whether to exclude virtual contacts
     *  @param int    $system_repond_by_date_num is GMT time copied over from cct7_tickets.php
	 *
	 * $system_id,
	 * $lastid,
	 * $this->reboot_required,
	 * $this->exclude_virtual_contacts,
	 * $this->approvals_required,
	 * $this->system_respond_by_date_num) == false)
	 *
     *  @return true or false where true means success.
     */
    public function saveContacts($system_id, $lastid, $reboot, $approvals_required, $exclude_virtual_contacts, $system_respond_by_date_num=0)
    {
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %d", $system_id);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "lastid = %d", $lastid);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "reboot = %s", $reboot);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "approvals_required = %s", $approvals_required);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "exclude_virtual_contacts = %s", $exclude_virtual_contacts);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_respond_by_date_num = %d", $system_respond_by_date_num);

        $this->children_server = array();
        $this->contacts        = array();
        $this->cluster_list    = array();

        //
        // Get the cct7_computers server record
        //
        $query  = "select ";
        $query .= "  computer_hostname, ";
        $query .= "  computer_lastid, ";
        $query .= "  computer_status, ";
        $query .= "  computer_os_lite, ";
        $query .= "  computer_complex, ";
        $query .= "  computer_complex_lastid, ";
        $query .= "  computer_complex_name, ";
        $query .= "  computer_complex_parent_name, ";
        $query .= "  computer_complex_child_names ";
        $query .= "from ";
        $query .= "  cct7_computers ";
        $query .= "where ";
        $query .= "  computer_lastid = " . $lastid;

        if ($this->ora->sql2($query) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
        }

        if ($this->ora->fetch() == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
        }

        $node = new data_node();
        $node->computer_lastid   = $this->ora->computer_lastid;
        $node->computer_hostname = $this->ora->computer_hostname;
        $node->computer_status   = $this->ora->computer_status;

        if ($this->ora->computer_complex == "Y" && strlen($this->ora->computer_os_lite) == 0)
        {
            $node->computer_os_lite = "COMPLEX";
        }
        else
        {
            $node->computer_os_lite = $this->ora->computer_os_lite;
        }

        $node->computer_complex             = $this->ora->computer_complex;
        $node->computer_complex_lastid      = $this->ora->computer_complex_lastid;
        $node->computer_complex_name        = $this->ora->computer_complex_name;
        $node->computer_complex_parent_name = $this->ora->computer_complex_parent_name;
        $node->computer_complex_child_names = $this->ora->computer_complex_child_names;
        $node->connections                  = $this->ora->computer_hostname;
        $node->contacts                     = NULL;
        $node->connections                  = $this->connectionString(
												$this->ora->computer_complex_name,
												$this->ora->computer_complex_parent_name,
												$this->ora->computer_hostname);

        $this->children_server[$this->ora->computer_hostname] = $node;
        // Notice: Indirect modification of overloaded property cct7_contacts::$this->children_server has no effect
        // in /opt/ibmtools/www/cct7/classes/cct7_contacts.php on line 414

        $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "children_server");
        $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $this->children_server);

        $this->childrenServers($this->ora->computer_complex_child_names);

        if ($reboot == "Y" && $exclude_virtual_contacts == "N")
        {
            $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "reboot == Y");

            //
            // Add virtual servers (vmware)
            //
            foreach ($this->children_server as $child)
            {
                $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "foreach(children_server as child)");
                $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $child);

                $query  = "select connected_name from cct7_virtual_servers where name = upper('" . $child->computer_hostname . "')";
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
                    // Record the cluster name
                    $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "cluster_list[%s] = true", $this->ora->connected_name);
                    $this->cluster_list[$this->ora->connected_name] = true;

                    $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "children_server");
                    $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $this->children_server);
                }
            }

            $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "cluster_list");
            $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $this->cluster_list);

            //
            // Add any new VMWARE servers to our main server list
            //
            foreach($this->cluster_list as $cluster_name => $val)
            {
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Adding vmware servers for cluster name: %s", $cluster_name);

                $query = "select distinct " .
                    "c.computer_lastid, " .
                    "to_char(c.computer_last_update,  'MM/DD/YYYY HH24:MI'), " .
                    "to_char(c.computer_install_date, 'MM/DD/YYYY HH24:MI'), " .
                    "c.computer_systemname, " .
                    "c.computer_hostname, " .
                    "c.computer_operating_system, " .
                    "c.computer_os_lite, " .
                    "c.computer_status, " .
                    "c.computer_status_description, " .
                    "c.computer_description, " .
                    "c.computer_nature, " .
                    "c.computer_platform, " .
                    "c.computer_type, " .
                    "c.computer_clli, " .
                    "c.computer_clli_fullname, " .
                    "c.computer_timezone, " .
                    "c.computer_building, " .
                    "c.computer_address, " .
                    "c.computer_city, " .
                    "c.computer_state, " .
                    "c.computer_floor_room, " .
                    "c.computer_grid_location, " .
                    "c.computer_lease_purchase, " .
                    "c.computer_serial_no, " .
                    "c.computer_asset_tag, " .
                    "c.computer_model_category, " .
                    "c.computer_model_no, " .
                    "c.computer_model, " .
                    "c.computer_model_mfg, " .
                    "c.computer_cpu_type, " .
                    "c.computer_cpu_count, " .
                    "c.computer_cpu_speed, " .
                    "c.computer_memory_mb, " .
                    "c.computer_ip_address, " .
                    "c.computer_domain, " .
                    "c.computer_hostname_domain, " .
                    "c.computer_dmz, " .
                    "c.computer_ewebars_title, " .
                    "c.computer_ewebars_status, " .
                    "c.computer_backup_format, " .
                    "c.computer_backup_nodename, " .
                    "c.computer_backup_program, " .
                    "c.computer_backup_server, " .
                    "c.computer_netbackup, " .
                    "c.computer_complex, " .
                    "c.computer_complex_lastid, " .
                    "c.computer_complex_name, " .
                    "c.computer_complex_parent_name, " .
                    "c.computer_complex_child_names, " .
                    "c.computer_complex_partitions, " .
                    "c.computer_service_guard, " .
                    "c.computer_os_group_contact, " .
                    "c.computer_cio_group, " .
                    "c.computer_managing_group, " .
                    "c.computer_contract, " .
                    "c.computer_contract_ref, " .
                    "c.computer_contract_status, " .
                    "c.computer_contract_status_type, " .
                    "to_char(c.computer_contract_date, 'MM/DD/YYYY HH24:MI'), " .
                    "c.computer_ibm_supported, " .
                    "c.computer_gold_server, " .
                    "c.computer_slevel_objective, " .
                    "c.computer_slevel_score, " .
                    "c.computer_slevel_colors, " .
                    "c.computer_special_handling, " .
                    "c.computer_applications, " .
                    "c.computer_osmaint_weekly, " .
                    "c.computer_osmaint_monthly, " .
                    "c.computer_osmaint_quarterly, " .
                    "c.computer_csc_os_banners, " .
                    "c.computer_csc_pase_banners, " .
                    "c.computer_csc_dba_banners, " .
                    "c.computer_csc_fyi_banners, " .
                    "c.computer_disk_array_alloc_kb, " .
                    "c.computer_disk_array_used_kb, " .
                    "c.computer_disk_array_free_kb, " .
                    "c.computer_disk_local_alloc_kb, " .
                    "c.computer_disk_local_used_kb, " .
                    "c.computer_disk_local_free_kb " .
                    "from cct7_computers c";

                $query .= ", cct7_virtual_servers v ";
                $query .= "where ";
                $query .= "  v.connected_name = '" . $cluster_name . "' and ";
                $query .= "  v.connection_type = 'ESX SERVER TO VMWARE CLUSTER' and ";
                $query .= "  c.computer_lastid = v.lastid ";
                $query .= "order by ";
                $query .= "  c.computer_hostname";

                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

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
                    $node = new data_node();
                    $node->computer_lastid   = $this->ora->computer_lastid;
                    $node->computer_hostname = $this->ora->computer_hostname;
                    $node->computer_status   = $this->ora->computer_status;

                    if ($this->ora->computer_complex == "Y" && strlen($this->ora->computer_os_lite) == 0)
                    {
                        $node->computer_os_lite = "COMPLEX";
                    }
                    else
                    {
                        $node->computer_os_lite = $this->ora->computer_os_lite;
                    }

                    $node->computer_complex             = $this->ora->computer_complex;
                    $node->computer_complex_lastid      = $this->ora->computer_complex_lastid;
                    $node->computer_complex_name        = $this->ora->computer_complex_name;
                    $node->computer_complex_parent_name = $this->ora->computer_complex_parent_name;
                    $node->computer_complex_child_names = $this->ora->computer_complex_child_names;

                    //$node->connections = connectionString($ora->computer_complex_name, $ora->computer_complex_parent_name, $ora->computer_hostname);
                    //$node->connections = connectionString($ora->computer_complex_name, $cluster_name, $ora->computer_hostname);

                    $node->connections = $cluster_name . "->" . $this->ora->computer_hostname;

                    $node->contacts = NULL;

                    $this->children_server[$this->ora->computer_hostname] = $node;
                }
            }
        }

        $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "children_server");
        $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $this->children_server);

        foreach ($this->children_server as $child)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "foreach(children_server as child)");
            $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $child);

            $this->getContactsCSC($child);
            $this->getContactsSubscribers($child);
        }

        //
        // Write to cct7_contacts
        //

// Netpin/Members Connections              OS       Status      Approval Group Types Notify Type CSC Support Banners (Primary)       Apps/DBMS
// ============== ======================== ======== =========== ======== =========== =========== =================================== =========
// 51190          hcdnx11a,                COMPLEX, PRODUCTION, WAITING  OS          APROVER     Operating System Support(mits-all), NONE,
// aa65437        hcdnx11a->hcdnx11a-san2, ,        PRODUCTION,                                  NONE,                               NONE,
// ab04341(P)     ...                      ,        PRODUCTION,                                  NONE,                               NONE,
// ab39729(B)     hcdnx11a->hhdnp29a       HPUX,    PRODUCTION,                                  Operating System Support(mits-all), NONE,

        $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "contacts");
        $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $this->contacts);

        foreach($this->contacts as $netpin => $contact)
        {
            $contact_netpin_no          = $netpin;
            $contact_connection         = $contact->connections;
            $contact_server_os          = $contact->os_lite;
            $contact_server_usage       = $contact->status;
            $contact_work_group         = $contact->work_groups;
            $contact_approver_fyi       = $contact->notify_type;
            $contact_csc_banner         = $contact->group_name;
            $contact_apps_databases     = $contact->applications;

            //
            // If no approvals are required we will override the contact notify type and set it to "FYI".
            // We will then change the response status indicator to "READ". Otherwise, we will just set
            // the response status to "WAITING" to indicate that we are waiting for the contact to approve
            // the work.
            //
            if ($approvals_required == 'N' || $contact_approver_fyi == "FYI")
            {
                $contact_approver_fyi    = "FYI";
                $contact_response_status = "APPROVED";
            }
            else
            {
                $contact_response_status = "WAITING";
            }

            //
            // Break apart the strings so we can create multiple records out of the data. This is enable
            // us to map the data more effectively in the sub-grid. Below is an example of how we want the
            // data to look in the grid.
            //
            // Server   	Net Pin	Connections	                    OS	    Usage	    Banner	            App / DB	    Notify Type	Approval
            // ------------ ------- ------------------------------- ------- ----------- ------------------- --------------- ----------- --------
            // acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp59a	HPUX	PRODUCTION	Database Support	DB_NEPPROD1	    Approve 	APPROVED
            // acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp59a	HPUX	PRODUCTION	Database Support	DB_CTRLPROD1	Approve 	APPROVED
            // acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp59a	HPUX	PRODUCTION	Database Support	DB_SARMPROD1	Approve 	APPROVED
            // acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp61a	HPUX	PRODUCTION	Database Support	DB_CTRLPROD1	Approve 	APPROVED
            // acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp61a	HPUX	PRODUCTION	Database Support	DB_NEPPROD1	    Approve 	APPROVED
            // acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp61a	HPUX	PRODUCTION	Database Support	DB_CTRLPROD1	Approve 	APPROVED
            // acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp63a	HPUX	PRODUCTION	Database Support	DB_RMPROD1	    Approve 	APPROVED
            // acmspns01	20178	hhdnp29a->hcdnx11a->hvdnp63a	HPUX	PRODUCTION	Database Support	DB_INFOPROD1	Approve 	APPROVED
            //

            $contact_netpin_no          = $netpin;

            $connection     = explode(",", $contact_connection);
            $server_os      = explode(",", $contact_server_os);
            $server_usage   = explode(",", $contact_server_usage);
            //$work_group     = explode(",", $contact_work_group);
            //$approver_fyi   = explode(",", $contact_approver_fyi);
            $csc_banner     = explode(",", $contact_csc_banner);
            $apps_databases = explode(",", $contact_apps_databases);

            //
            // These counts should all be equal.
            //
            $connection_count    = count($connection);
            $server_os_count     = count($server_os);
            $server_usage_count  = count($server_usage);
            //$work_group_count    = count($work_group);
            //$approver_fyi_count  = count($approver_fyi);
            $csc_banner_count    = count($csc_banner);
            $apps_database_count = count($apps_databases);

            $this->debug1(__FILE__, __FUNCTION__, __LINE__,
                "connections: %d, os: %d, usage: %d, csc_banner: %d, apps_databases: %d",
                $connection_count, $server_os_count, $server_usage_count,
                $csc_banner_count, $apps_database_count);

            for ($i=0; $i<$connection_count; $i++)
            {
                $connection_item     = $connection[$i];
                $server_os_item      = '';
                $server_usage_item   = '';
                //$work_group_item     = '';
                //$approver_fyi_item   = '';
                $csc_banner_item     = '';
                $apps_databases_item = '';

                if ($i < $server_os_count)
                    $server_os_item = $server_os[$i];

                if ($i < $server_usage_count)
                    $server_usage_item = $server_usage[$i];

                //if ($i < $work_group_count)
                //    $work_group_item = $work_group[$i];

                //if ($i < $approver_fyi_count)
                //    $approver_fyi_item = $approver_fyi[$i];

                if ($i < $csc_banner_count)
                    $csc_banner_item = $csc_banner[$i];

                if ($i < $apps_database_count)
                    $apps_databases_item = $apps_databases[$i];

                //
                // SKIP contact and connection where there is no valid NETPIN number.
                //
                if ($contact_netpin_no == '0' || $contact_netpin_no == 'NONE' || $contact_netpin_no == '')
                {
                    $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Skipping insert where contact_netpin_no = %s", $contact_netpin_no);
                    continue;
                }

                $contact_id = $this->ora->next_seq('cct7_contactsseq');

                $rc = $this->ora
                    ->insert("cct7_contacts")
                    ->column("contact_id")
                    ->column("system_id")
                    ->column("contact_netpin_no")
                    ->column("contact_insert_date")
                    ->column("contact_insert_cuid")
                    ->column("contact_insert_name")
                    ->column("contact_connection")
                    ->column("contact_server_os")
                    ->column("contact_server_usage")
                    ->column("contact_work_group")
                    ->column("contact_approver_fyi")
                    ->column("contact_csc_banner")
                    ->column("contact_apps_databases")
                    ->column("contact_respond_by_date")
                    ->column("contact_response_status")
                    ->value("int",  $contact_id)                  // PRIMARY KEY - Unique record ID
                    ->value("int",  $system_id)                   // FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE
                    ->value("char", $contact_netpin_no)           // Netpin
                    ->value("int",  $this->now_to_gmt_utime())    // contact_insert_date
                    ->value("char", $this->user_cuid)             // CUID of person who created this record
                    ->value("char", $this->user_name)             // Name of person who created this record
                    ->value("char", $connection_item)             // contact_connection
                    ->value("char", $server_os_item)              // contact_server_os
                    ->value("char", $server_usage_item)           // contact_server_usage
                    ->value("char", $contact_work_group)          // contact_work_group
                    ->value("char", $contact_approver_fyi)        // contact_approver_fyi
                    ->value("char", $csc_banner_item)             // contact_csc_banner
                    ->value("char", $apps_databases_item)         // contact_apps_databases
                    ->value("int",  $system_respond_by_date_num)  // contact_respond_by_date
                    ->value("char", $contact_response_status)     // Group WAITING, APPROVED, REJECTED, RESCHEDULE
                    ->execute();

                if ($rc == false)
                {
                    $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                    $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                    $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                        $this->ora->sql_statement, $this->ora->dbErrMsg);
                    return false;
                }
            }

            $this->ora->commit();
        }

        $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "getContactsCSC() returning true");
        return true;
    }

    /**
     * @fn     getContactsSubscribers($child)
     *
     * @brief  Get a list of contacts for this server (object $child) from cct7_subscriber_groups.
     *
     * @param  object $child
     *
     * @return true or false
     */
    private function getContactsSubscribers($child)
    {
        // cct7_subscriber_groups
        // group_id|VARCHAR2|20|NOT NULL|PK: Unique Record ID
        // create_date|NUMBER|0||GMT date record was created
        // owner_cuid|VARCHAR2|20||Owner CUID of this subscriber list
        // owner_name|VARCHAR2|200||Owner NAME of this subscriber list
        // group_name|VARCHAR2|200||Group Name

        // cct7_subscriber_members
        // member_id|NUMBER|0|NOT NULL|PK: Unique Record ID
        // group_id|VARCHAR2|20||FK: cct7_subscriber_groups
        // create_date|NUMBER|0||GMT date record was created
        // member_cuid|VARCHAR2|20||Member CUID
        // member_name|VARCHAR2|200||Member NAME

        // cct7_subscriber_servers
        // server_id|NUMBER|0|NOT NULL|PK: Unique Record ID
        // group_id|VARCHAR2|20||FK: cct7_subscriber_groups
        // create_date|NUMBER|0||GMT creation date
        // owner_cuid|VARCHAR2|20||Owner CUID
        // owner_name|VARCHAR2|200||Owner NAME
        // computer_lastid|NUMBER|0||Asset Manager computer record ID
        // computer_hostname|VARCHAR2|255||Server Hostname
        // computer_ip_address|VARCHAR2|64||Server IP Address
        // computer_os_lite|VARCHAR2|20||Server Operating System
        // computer_status|VARCHAR2|80||Server Status: PRODUCTION, DEVELOPMENT, etc.
        // computer_managing_group|VARCHAR2|40||Server Managing Group name
        // notification_type|VARCHAR2|20||Notification Type: APPROVER or FYI

        $query  = "select distinct ";
        $query .= "  g.group_id, ";
        $query .= "  s.notification_type ";
        $query .= "from ";
        $query .= "  cct7_subscriber_groups g, ";
        $query .= "  cct7_subscriber_members m, ";
        $query .= "  cct7_subscriber_servers s ";
        $query .= "where ";
        $query .= "  s.computer_lastid = " . $child->computer_lastid . " and ";
        $query .= "  g.group_id = m.group_id and g.group_id = s.group_id";

        $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

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
            $subscriber_group  = $this->ora->group_id;
            $notification_type = $this->ora->notification_type;

            if (array_key_exists($subscriber_group, $this->contacts))
            {
                $node = $this->contacts[$subscriber_group];
            }
            else
            {
                $node = new data_node();
                $this->contacts[$subscriber_group] = $node;
                $node->connections  = "";
                $node->status       = "";
                $node->os_lite      = "";
                $node->group_name   = "Application Support";
                $node->applications = "";
                $node->work_groups  = "PASE";
                $node->notify_type  = $notification_type;
            }

            if (strlen($node->connections) == 0 && strlen($child->connections) > 0)
            {
                $node->connections = $child->connections;
            }
            else
            {
                $node->connections .= "," . $child->connections;
            }

            if (strlen($node->status) == 0 && strlen($child->computer_status) > 0)
            {
                $node->status = $child->computer_status;
            }
            else
            {
                $node->status .= "," . $child->computer_status;
            }

            if (strlen($node->os_lite) == 0)
            {
                $node->os_lite = $child->computer_os_lite;
            }
            else
            {
                $node->os_lite .= "," . $child->computer_os_lite;
            }

            if (strlen($node->group_name) == 0)
            {
                $node->group_name = "PASE";
            }
            else
            {
                $node->group_name .= ",PASE";
            }

            if (strlen($node->applications) == 0)
            {
                $node->applications = "Not Available";
            }
            else
            {
                $node->applications .= ",Not Available";
            }
        }

        return true;
    }

    /**
     * @fn     getContactsCSC($child)
     *
     * @brief  Get a list of contacts for this server (object $child) from CSC.
     *
     * @param  object $child
     *
     * @return true or false
     */
    private function getContactsCSC($child)
    {
        // cct_csc_netgroup   = Net-Tool net-pin number.
        // cct_csc_group_name = CSC Banner (i.e. 'Application Support')

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
            "cct7_csc " .
            "where " .
            "lastid = " . $child->computer_lastid . " and ( ";

        /*
         * APPROVER PASE Applications or Databases Desiring Notification (Not Hosted on this Server)
         * APPROVER PASE Application Support
         * APPROVER PASE Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)
         * APPROVER PASE Infrastructure
         * APPROVER PASE MiddleWare Support
         * APPROVER DBA  Database Support
         * APPROVER DBA  Development Database Support
         * APPROVER OS   Operating System Support
         *
         * FYI      PASE Applications Owning Database (DB Hosted on this Server, Owning App Is Not)
         * FYI      PASE Development Support
         */

        $add_or = false;

        if ($this->csc_banner1 == 'Y')    // Applications or Databases Desiring Notification (Not Hosted on this Server)
        {
            if ($add_or)
                $query .= "or ";

            $query .= "cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' ";

            $add_or = true;
        }

        if ($this->csc_banner2 == 'Y')    // Application Support
        {
            if ($add_or)
                $query .= "or ";

            $query .= "cct_csc_group_name = 'Application Support' ";

            $add_or = true;
        }

        if ($this->csc_banner3 == 'Y')    // Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)
        {
            if ($add_or)
                $query .= "or ";

            $query .= "cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' ";

            $add_or = true;
        }

        if ($this->csc_banner4 == 'Y')    // Infrastructure
        {
            if ($add_or)
                $query .= "or ";

            $query .= "cct_csc_group_name = 'Infrastructure' ";

            $add_or = true;
        }

        if ($this->csc_banner5 == 'Y')    // MiddleWare Support
        {
            if ($add_or)
                $query .= "or ";

            $query .= "cct_csc_group_name = 'MiddleWare Support' ";

            $add_or = true;
        }

        if ($this->csc_banner6 == 'Y')    // Database Support
        {
            if ($add_or)
                $query .= "or ";

            $query .= "cct_csc_group_name = '! Database Support' ";

            $add_or = true;
        }

        if ($this->csc_banner7 == 'Y')    // Development Database Support
        {
            if ($add_or)
                $query .= "or ";

            $query .= "cct_csc_group_name = 'Development Support' ";

            $add_or = true;
        }

        if ($this->csc_banner8 == 'Y')    // Operating System Support
        {
            if ($add_or)
                $query .= "or ";

            $query .= "cct_csc_group_name = '! Operating System Support' ";

            $add_or = true;
        }

        if ($this->csc_banner9 == 'Y')    // Applications Owning Database (DB Hosted on this Server, Owning App Is Not)
        {
            if ($add_or)
                $query .= "or ";

            $query .= "cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)' ";

            $add_or = true;
        }

        if ($this->csc_banner10 == 'Y') // Development Support
        {
            if ($add_or)
                $query .= "or ";

            $query .= "cct_csc_group_name = '! Development Database Support' ";
        }

        $query .= ") order by cct_csc_group_name";

        $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

        if ($this->ora->sql2($query) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
        }

        $count = 0;

        while ($this->ora->fetch())
        {
            $count++;

            //
            // Figure out what contact to use for each group based upon CMP policy rules.
            //
            $notify_type = "";
            $group_type  = "";
            $group_name  = "";

            if ($this->ora->cct_csc_group_name == 'Development Support')
            {
                $notify_type = 'FYI';
                $group_type  = 'PASE';
                $group_name  = $this->ora->cct_csc_group_name;
            }
            else if ($this->ora->cct_csc_group_name == 'MiddleWare Support')
            {
                $notify_type = 'APPROVER';
                $group_type  = 'PASE';
                $group_name  = $this->ora->cct_csc_group_name;
            }
            else if ($this->ora->cct_csc_group_name == '! Operating System Support')
            {
                $notify_type = 'APPROVER';
                $group_type  = 'OS';
                $group_name  = 'Operating System Support';
            }
            else if ($this->ora->cct_csc_group_name == '! Database Support')
            {
                $notify_type = 'APPROVER';
                $group_type  = 'DBA';
                $group_name  = 'Database Support';
            }
            else if ($this->ora->cct_csc_group_name == '! Development Database Support')
            {
                $notify_type = 'FYI';
                $group_type  = 'DBA';
                $group_name  = 'Development Database Support';
            }
            else if ($this->ora->cct_csc_group_name == 'Application Support')
            {
                $notify_type = 'APPROVER';
                $group_type  = 'PASE';
                $group_name  = $this->ora->cct_csc_group_name;
            }
            else if ($this->ora->cct_csc_group_name == 'Infrastructure')
            {
                $notify_type = 'APPROVER';
                $group_type  = 'PASE';
                $group_name  = $this->ora->cct_csc_group_name;
            }
            else if ($this->ora->cct_csc_group_name == 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)')
            {
                $notify_type = 'APPROVER';
                $group_type  = 'PASE';
                $group_name  = $this->ora->cct_csc_group_name;
            }
            else if ($this->ora->cct_csc_group_name == 'Applications or Databases Desiring Notification (Not Hosted on this Server)')
            {
                $notify_type = 'FYI';
                $group_type  = 'PASE';
                $group_name  = $this->ora->cct_csc_group_name;
            }
            else if ($this->ora->cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)')
            {
                $notify_type = 'APPROVER';
                $group_type  = 'PASE';
                $group_name  = $this->ora->cct_csc_group_name;
            }
            else
            {
                $this->debug4(__FILE__, __FUNCTION__, __LINE__, "NO MATCH FOR: %s", $this->ora->cct_csc_group_name);
                $group_name  = "no match";
            }

            if (array_key_exists($this->ora->cct_csc_netgroup, $this->contacts))
            {
                $node = $this->contacts[$this->ora->cct_csc_netgroup];
            }
            else
            {
                $node = new data_node();
                $this->contacts[$this->ora->cct_csc_netgroup] = $node;
                $node->connections  = "";
                $node->status       = "";
                $node->os_lite      = "";
                $node->group_name   = "";
                $node->applications = "";
                $node->work_groups  = "";
                $node->notify_type  = "";
            }

            if (strlen($node->connections) == 0 && strlen($child->connections) > 0)
            {
                $node->connections = $child->connections;
            }
            else
            {
                $node->connections .= "," . $child->connections;
            }

            if (strlen($node->status) == 0 && strlen($child->computer_status) > 0)
            {
                $node->status = $child->computer_status;
            }
            else
            {
                $node->status .= "," . $child->computer_status;
            }

            if (strlen($node->os_lite) == 0)
            {
                $node->os_lite = $child->computer_os_lite;
            }
            else
            {
                $node->os_lite .= "," . $child->computer_os_lite;
            }

            //
            // $group_name = [ 'PASE', "DBA", "OS" ]
            //
            if (strlen($node->group_name) == 0 && strlen($group_name) > 0)
            {
                if (strlen($this->ora->cct_csc_userid_1) > 0)
                {
                    $node->group_name = $group_name . "(" . $this->ora->cct_csc_userid_1 . ")";
                }
                else
                {
                    $node->group_name = $group_name;
                }
            }
            else if (strlen($group_name) > 0)
            {
                if (strlen($this->ora->cct_csc_userid_1) > 0)
                {
                    $node->group_name .= "," . $group_name . "(" . $this->ora->cct_csc_userid_1 . ")";
                }
                else
                {
                    $node->group_name .= "," . $group_name;
                }
            }
            else
            {
                $node->group_name .= ",";
            }

            if (strlen($node->applications) == 0 && strlen($this->ora->cct_app_acronym) > 0)
            {
                $node->applications = $this->ora->cct_app_acronym;
            }
            else if (strlen($this->ora->cct_app_acronym) > 0)
            {
                $node->applications .= "," . $this->ora->cct_app_acronym;
            }
            else
            {
                $node->applications .= ",";
            }

            //
            // Figure out group and type
            //
            $oflag = false;
            $pflag = false;
            $dflag = false;

            if      ($group_type == "OS")
                $oflag = true;
            else if ($group_type == "PASE")
                $pflag = true;
            else if ($group_type == "DBA")
                $dflag = true;

            if (preg_match("/OS/", $node->work_groups))
                $oflag = true;

            if (preg_match("/PASE/", $node->work_groups))
                $pflag = true;

            if (preg_match("/DBA/", $node->work_groups))
                $dflag = true;

            $work_groups = "";

            if ($oflag)
            {
                $work_groups = "OS";
            }

            if ($pflag)
            {
                if (strlen($work_groups) > 0)
                    $work_groups .= "_PASE";
                else
                    $work_groups  = "PASE";
            }

            if ($dflag)
            {
                if (strlen($work_groups) > 0)
                    $work_groups .= "_DBA";
                else
                    $work_groups = "DBA";
            }

            $node->work_groups = $work_groups;

            if (strlen($node->notify_type) == 0)
            {
                $node->notify_type = $notify_type;
            }
            else if ($node->notify_type == "FYI")
            {
                $node->notify_type = $notify_type;
            }
        }

        if ($count == 0)
        {
            if (array_key_exists('NONE', $this->contacts))
            {
                $node = $this->contacts[$this->ora->cct_csc_netgroup];
            }
            else
            {
                $node = new data_node();
                $this->contacts['NONE'] = $node;
                $node->connections      = "";
                $node->status           = "";
                $node->os_lite          = "";
                $node->group_name       = "";
                $node->applications     = "";
                $node->work_groups      = "";
                $node->notify_type      = "";
            }

            if (strlen($node->connections) == 0 && strlen($child->connections) > 0)
            {
                $node->connections = $child->connections;
            }
            else
            {
                $node->connections .= "," . $child->connections;
            }

            if (strlen($node->status) == 0 && strlen($child->computer_status) > 0)
            {
                $node->status = $child->computer_status;
            }
            else
            {
                $node->status .= "," . $child->computer_status;
            }

            if (strlen($node->os_lite) == 0)
            {
                $node->os_lite = $child->computer_os_lite;
            }
            else
            {
                $node->os_lite .= "," . $child->computer_os_lite;
            }

            if (strlen($node->group_name) == 0)
            {
                $node->group_name = "NONE";
            }
            else
            {
                $node->group_name .= ",NONE";
            }

            if (strlen($node->applications) == 0 && strlen($this->ora->cct_app_acronym) > 0)
            {
                $node->applications = $this->ora->cct_app_acronym;
            }
            else
            {
                $node->applications .= "," . $this->ora->cct_app_acronym;
            }
        }

        return true;
    }

    /**
     * @fn    childrenServers($computer_complex_child_names)
     *
     * @brief Get a list of child servers attached to this server.
     *
     * @param string $computer_complex_child_names
     *
     * @return true or false
     */
    private function childrenServers($computer_complex_child_names)
    {
        $str = str_replace(",", " ", $computer_complex_child_names);                  // Convert any commas to spaces
        $complex_children = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $str);   // Remove multiple spaces, tabs and newlines if present	
        $systems = explode(" ", $complex_children);                                   // Create an array of $systems

        $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "systems");
        $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $systems);

        foreach ($systems as $system)
        {
            if (array_key_exists($system, $this->children_server))
                continue;

            $query  = "select ";
            $query .= "  computer_hostname, ";
            $query .= "  computer_lastid, ";
            $query .= "  computer_status, ";
            $query .= "  computer_os_lite, ";
            $query .= "  computer_complex, ";
            $query .= "  computer_complex_lastid, ";
            $query .= "  computer_complex_name, ";
            $query .= "  computer_complex_parent_name, ";
            $query .= "  computer_complex_child_names ";
            $query .= "from ";
            $query .= "  cct7_computers ";
            $query .= "where ";
            $query .= "  computer_hostname = lower('" . $system . "')";

            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

            if ($this->ora->sql2($query) == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                    $this->ora->sql_statement, $this->ora->dbErrMsg);
                return false;
            }

            if ($this->ora->fetch() == false)
            {
                continue;
            }

            $node = new data_node();
            $node->computer_lastid              = $this->ora->computer_lastid;
            $node->computer_hostname            = $this->ora->computer_hostname;
            $node->computer_status              = $this->ora->computer_status;
            $node->computer_os_lite             = $this->ora->computer_os_lite;
            $node->computer_complex             = $this->ora->computer_complex;
            $node->computer_complex_lastid      = $this->ora->computer_complex_lastid;
            $node->computer_complex_name        = $this->ora->computer_complex_name;
            $node->computer_complex_parent_name = $this->ora->computer_complex_parent_name;
            $node->computer_complex_child_names = $this->ora->computer_complex_child_names;

            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "BEGIN: connectionString(%s, %s, %s)",
                $this->ora->computer_complex_name,
                $this->ora->computer_complex_parent_name,
                $this->ora->computer_hostname);

            $node->connections = $this->connectionString(
                $this->ora->computer_complex_name,
                $this->ora->computer_complex_parent_name,
                $this->ora->computer_hostname);

            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "END: node->connections = %s", $node->connections);

            $node->contacts = NULL;

            $this->children_server[$this->ora->computer_hostname] = $node;

            // Recursive 

            if (strlen($this->ora->computer_complex_child_names) > 0)
                $this->childrenServers($this->ora->computer_complex_child_names);
        }

        return true;
    }

    /**
     * @fn    connectionString($complex, $parent, $hostname)
     *
     * @brief Create connection strings.
     *
     * @param string $complex
     * @param string $parent
     * @param string $hostname
     *
     * @return string
     */
    private function connectionString($complex, $parent, $hostname)
    {
        $connection = "";

        if (strlen($complex) > 0)
            $connection = $complex;

        if (strlen($parent) > 0)
        {
            if (strlen($connection) > 0)
            {
                $connection = $connection . "->" . $parent;
            }
            else
            {
                $connection = $parent;
            }
        }

        if (strlen($hostname) > 0)
        {
            if (strlen($connection) > 0)
            {
                $connection = $connection . "->" . $hostname;
            }
            else
            {
                $connection = $hostname;
            }
        }

        return $connection;
    }

	/**
	 * @fn    approveTicket($ticket_no, $netpin_no)
	 *
	 * @brief Contact $netpin_no approves the work for all servers identified by $ticket_no
	 *
	 * @param string $ticket_no
	 * @param string $netpin_no
	 *
	 * @return bool
	 */
    public function approveTicket($ticket_no, $netpin_no, $send_page = 'Y')
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s, netpin_no = %s", $ticket_no, $netpin_no);

		//
		// Set cct7_contacts status to APPROVED
		//
		$query  = "update ";
		$query .= "  cct7_contacts ";
		$query .= "set ";
		$query .= "  contact_update_date = "    . $this->now_to_gmt_utime() . ", ";
		$query .= "  contact_update_cuid = '"   . $this->user_cuid . "', ";
		$query .= "  contact_update_name = '"   . $this->FixString($this->user_name) . "', ";
		$query .= "  contact_response_status =    'APPROVED', ";
		$query .= "  contact_response_date = "  . $this->now_to_gmt_utime() . ", ";
		$query .= "  contact_response_cuid = '" . $this->user_cuid . "', ";
		$query .= "  contact_response_name = '" . $this->FixString($this->user_name) . "', ";
		$query .= "  contact_send_page = '"     . $send_page . "' ";
		$query .= "where ";
		$query .= "  contact_id in ( ";
		$query .= "    select distinct ";
        $query .= "      c.contact_id ";
    	$query .= "    from ";
      	$query .= "      cct7_systems s, ";
      	$query .= "      cct7_contacts c ";
    	$query .= "    where ";
      	$query .= "      s.ticket_no = '" . $ticket_no . "' and ";
	  	$query .= "      c.system_id = s.system_id and ";
	  	$query .= "      c.contact_response_status != 'APPROVED' and ";
	  	$query .= "      c.contact_netpin_no = '" . $netpin_no . "')";

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

		$this->ora->commit();

		//
		// Update all status for this ticket by calling Oracle stored procedure identified in
		// updateAllStatuses() - library.php
		//
		if ($this->updateAllStatuses($this->ora, $ticket_no) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		//
		// Create a log entry in cct7_log_contacts for each server in $ticket_no where $netpin_no
		//
		$query  = "select distinct ";
		$query .= "  s.system_id, ";
		$query .= "  s.system_hostname ";
		$query .= "from ";
		$query .= "  cct7_systems s, ";
		$query .= "  cct7_contacts c ";
		$query .= "where ";
		$query .= "  s.ticket_no = '" . $ticket_no . "' and ";
		$query .= "  c.system_id = s.system_id and ";
		$query .= "  c.contact_netpin_no = '" . $netpin_no . "'";

		$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$list = array();

		while ($this->ora->fetch())
		{
			$list[$this->ora->system_hostname] = $this->ora->system_id;
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "list[%s] = %d",
				$this->ora->system_hostname, $this->ora->system_id);
		}

		foreach ($list as $system_hostname => $system_id)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__,
						  "Adding log message: system_hostname = %s, system_id = %d",
						  $system_hostname, $system_id);
			//
			// Create a log entry in cct7_log_contacts
			// i.e. ticket_no, netpin, hostname, by Greg Parkin
			//
			// APPROVED CCT700043714 work approved for server lxomp31u and netpin 17340
			// APPROVED CCT700043714 NETPIN 17340 approved work for server lxomp31u.

			$event_message = sprintf("%s NETPIN %s approved work for server %s.",
				$ticket_no, $netpin_no, $system_hostname);

			//$event_message = sprintf("%s work approved for server %s and netpin %s",
			//						 $ticket_no, $system_hostname, $netpin_no);

			if ($this->putLogContacts(
					$ticket_no,
					$system_id, $system_hostname,
					$netpin_no,
					'APPROVED',
					$event_message) == false)
			{
				$this->error = sprintf("system_id = %d, system_hostname = %s",
									   $system_id, $system_hostname);
				return false;
			}
		}

		return true;
	}

    /**
     * @fn    approve($system_id, $netpin_no)
     *
     * @brief Contact $netpin_no approves the work for server identified by $system_id
     *
     * @param int    $system_id
     * @param string $netpin_no
	 * @param string $send_page
     *
     * @return bool
     */
    public function approve($system_id, $netpin_no, $send_page)
    {
        $sys = new cct7_systems($this->ora);

        //
        // Get the cct7_systems record identified by $system_id
        //
        if ($sys->getSystem($system_id) == false)
        {
            $this->error = $sys->error;
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $sys->error);
            return false;
        }

        $ticket_no = $sys->ticket_no;
        $hostname  = $sys->system_hostname;

        //
        // Set cct7_contacts status to APPROVED
        //
        $rc = $this->ora
            ->update("cct7_contacts")
            ->set("int",  "contact_update_date",      $this->now_to_gmt_utime())
            ->set("char", "contact_update_cuid",      $this->user_cuid)
            ->set("char", "contact_update_name",      $this->user_name)
            ->set("char", "contact_response_status",  "APPROVED")
            ->set("int",  "contact_response_date",    $this->now_to_gmt_utime())
            ->set("char", "contact_response_cuid",    $this->user_cuid)
            ->set("char", "contact_response_name",    $this->user_name)
			->set("char", "contact_send_page",        $send_page)
            ->where(    "int",  "system_id",          "=", $system_id)
            ->where_and("char", "contact_netpin_no",  "=", $netpin_no)
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

        //
        // Update all status for this ticket by calling Oracle stored procedure identified in updateAllStatuses()
        //
        if ($this->updateAllStatuses($this->ora, $ticket_no) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        //
        // Create a log entry in cct7_log_contacts
        // ticket_no, netpin, hostname, by Greg Parkin
        //
        $event_message = sprintf("%s work approved for server %s and netpin %s", $ticket_no, $hostname, $netpin_no);

        if ($this->putLogContacts($ticket_no, $system_id, $hostname, $netpin_no, 'APPROVED', $event_message) == false)
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
     * @fn    reject($system_id, $netpin_no)
     *
     * @brief Contact $netpin_no rejects the work for server identified by $system_id and $netpin_no
     *
     * @param int    $system_id
     * @param string $netpin_no
     *
     * @return bool
     */
    public function reject($system_id, $netpin_no)
    {
        $tic = new cct7_tickets($this->ora);
        $sys = new cct7_systems($this->ora);

        //
        // Get the cct7_systems record identified by $system_id
        //
        if ($sys->getSystem($system_id) == false)
        {
            $this->error = $sys->error;
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $sys->error);
            return false;
        }

        $ticket_no = $sys->ticket_no;
        $hostname  = $sys->system_hostname;

        //
        // Get the cct7_tickets record identified by $sys->ticket_no
        //
        if ($tic->getTicket($ticket_no) == false)
        {
            $this->error = $sys->error;
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $sys->error);
            return false;
        }

        //
        // Set cct7_contacts status to REJECTED
        //
        $rc = $this->ora
            ->update("cct7_contacts")
            ->set("int",  "contact_update_date",      $this->now_to_gmt_utime())
            ->set("char", "contact_update_cuid",      $this->user_cuid)
            ->set("char", "contact_update_name",      $this->user_name)
            ->set("char", "contact_response_status",  "REJECTED")
            ->set("int",  "contact_response_date",    $this->now_to_gmt_utime())
            ->set("char", "contact_response_cuid",    $this->user_cuid)
            ->set("char", "contact_response_name",    $this->user_name)
            ->where(    "int",  "system_id",          "=", $system_id)
            ->where_and("char", "contact_netpin_no",  "=", $netpin_no)
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

        //
        // Update all status for this ticket by calling Oracle stored procedure identified in updateAllStatuses()
        //
        if ($this->updateAllStatuses($this->ora, $ticket_no) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        //
        // Create a log entry in cct7_log_contacts
        // ticket_no, netpin, hostname, by Greg Parkin
        //
        $event_message = sprintf("%s work approved for server %s and netpin %s", $ticket_no, $hostname, $netpin_no);

        if ($this->putLogContacts($ticket_no, $system_id, $hostname, $netpin_no, 'APPROVED', $event_message) == false)
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
	 * @fn    cancel($system_id, $netpin_no)
	 *
	 * @brief Contact $netpin_no cancels the work for server identified by $system_id and $netpin_no
	 *
	 * @param int    $system_id
	 * @param string $netpin_no
	 *
	 * @return bool
	 */
	public function cancel($system_id, $netpin_no)
	{
		$tic = new cct7_tickets($this->ora);
		$sys = new cct7_systems($this->ora);

		//
		// Get the cct7_systems record identified by $system_id
		//
		if ($sys->getSystem($system_id) == false)
		{
			$this->error = $sys->error;
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $sys->error);
			return false;
		}

		$ticket_no = $sys->ticket_no;
		$hostname  = $sys->system_hostname;

		//
		// Get the cct7_tickets record identified by $sys->ticket_no
		//
		if ($tic->getTicket($ticket_no) == false)
		{
			$this->error = $sys->error;
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $sys->error);
			return false;
		}

		//
		// Set cct7_contacts status to CANCELED
		//
		$rc = $this->ora
			->update("cct7_contacts")
			->set("int",  "contact_update_date",      $this->now_to_gmt_utime())
			->set("char", "contact_update_cuid",      $this->user_cuid)
			->set("char", "contact_update_name",      $this->user_name)
			->set("char", "contact_response_status",  "CANCELED")
			->set("int",  "contact_response_date",    $this->now_to_gmt_utime())
			->set("char", "contact_response_cuid",    $this->user_cuid)
			->set("char", "contact_response_name",    $this->user_name)
			->where(    "int",  "system_id",          "=", $system_id)
			->where_and("char", "contact_netpin_no",  "=", $netpin_no)
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

		//
		// Update all status for this ticket by calling Oracle stored procedure identified in updateAllStatuses()
		//
		if ($this->updateAllStatuses($this->ora, $ticket_no) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		//
		// Create a log entry in cct7_log_contacts
		// ticket_no, netpin, hostname, by Greg Parkin
		//
		$event_message = sprintf("%s work approved for server %s and netpin %s", $ticket_no, $hostname, $netpin_no);

		if ($this->putLogContacts($ticket_no, $system_id, $hostname, $netpin_no, 'APPROVED', $event_message) == false)
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
     * @fn    exempt($system_id, $netpin_no)
     *
     * @brief Contact $netpin_no exempt the user identified by $system_id and $netpin_no
     *
     * @param int    $system_id
     * @param string $netpin_no
     *
     * @return bool
     */
    public function exempt($system_id, $netpin_no)
    {
        $sys = new cct7_systems($this->ora);

        //
        // Get the cct7_systems record identified by $system_id
        //
        if ($sys->getSystem($system_id) == false)
        {
            $this->error = $sys->error;
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $sys->error);
            return false;
        }

        $ticket_no = $sys->ticket_no;
        $hostname  = $sys->system_hostname;

        //
        // Set cct7_contacts status to EXEMPTED
        //
        $rc = $this->ora
            ->update("cct7_contacts")
            ->set("int",  "contact_update_date",      $this->now_to_gmt_utime())
            ->set("char", "contact_update_cuid",      $this->user_cuid)
            ->set("char", "contact_update_name",      $this->user_name)
            ->set("char", "contact_response_status",  "EXEMPTED")
            ->set("int",  "contact_response_date",    $this->now_to_gmt_utime())
            ->set("char", "contact_response_cuid",    $this->user_cuid)
            ->set("char", "contact_response_name",    $this->user_name)
            ->where(    "int",  "system_id",          "=", $system_id)
            ->where_and("char", "contact_netpin_no",  "=", $netpin_no)
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

        //
        // Update all status for this ticket by calling Oracle stored procedure identified in updateAllStatuses()
        //
        if ($this->updateAllStatuses($this->ora, $ticket_no) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        //
        // Create a log entry in cct7_log_contacts
        //
        $event_message = sprintf("%s has requested to be exempted from approving this work for %s, server: %s, netpin: %s",
            $this->user_first_name, $ticket_no, $hostname, $netpin_no);

        if ($this->putLogContacts($ticket_no, $system_id, $hostname, $netpin_no, 'EXEMPTED', $event_message) == false)
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
     * @fn    togglePageOncall($ticket_no, $system_id, $contact_netpin_no)
     *
     * @brief Toggle the Page On-call indicator on or off.
     *
     * @param string $ticket_no
     * @param int    $system_id
     * @param string $contact_netpin_no
     *
     * @return bool true or false
     */
    public function togglePageOncall($ticket_no, $system_id, $contact_netpin_no)
    {
        $rc = $this->ora
            ->select()
            ->column("contact_send_page")
            ->from("cct7_contacts")
            ->where(    "int",  "system_id",         "=", $system_id)
            ->where_and("char", "contact_netpin_no", "=", $contact_netpin_no)
            ->execute();

        if ($rc == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                                   $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
        }

        $this->ora->fetch();
        $current_setting = $this->ora->contact_send_page;

        if ($current_setting == 'Y')
            $current_setting = 'N';
        else
            $current_setting = 'Y';

        $rc = $this->ora
            ->update("cct7_contacts")
            ->set("int",  "contact_update_date",      $this->now_to_gmt_utime())
            ->set("char", "contact_update_cuid",      $this->user_cuid)
            ->set("char", "contact_update_name",      $this->user_name)
            ->set("char", "contact_send_page",        $current_setting)
            ->where(    "int",  "system_id",         "=", $system_id)
            ->where_and("char", "contact_netpin_no", "=", $contact_netpin_no)
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

        //
        // Call updateAllStatuses() in library.php
        //
        if ($this->updateAllStatuses($this->ora, $ticket_no) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        $sys = new cct7_systems($this->ora);

        if ($sys->getSystem($system_id) == false)
        {
            $this->error = $sys->error;
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        if ($current_setting == 'Y')
            $message = "Group on-call paging has been set to YES.";
        else
            $message = "Group on-call paging has been set to NO.";

        if ($this->putLogContacts($ticket_no, $system_id, $sys->system_hostname, $contact_netpin_no, "PAGE", $message) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        return true;
    }

    /**
     * @fn    toggleSendEmail($ticket_no, $system_id, $contact_netpin_no)
     *
     * @brief Toggle the Send Email indicator on or off.
     *
     * @param string $ticket_no
     * @param int    $system_id
     * @param string $contact_netpin_no
     *
     * @return bool true or false
     */
    public function toggleSendEmail($ticket_no, $system_id, $contact_netpin_no)
    {
        $rc = $this->ora
            ->select()
            ->column("contact_send_email")
            ->from("cct7_contacts")
            ->where(    "int",  "system_id",         "=", $system_id)
            ->where_and("char", "contact_netpin_no", "=", $contact_netpin_no)
            ->execute();

        if ($rc == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                                   $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
        }

        $this->ora->fetch();
        $current_setting = $this->ora->contact_send_email;

        if ($current_setting == 'Y')
            $current_setting = 'N';
        else
            $current_setting = 'Y';

        $rc = $this->ora
            ->update("cct7_contacts")
            ->set("int",  "contact_update_date",      $this->now_to_gmt_utime())
            ->set("char", "contact_update_cuid",      $this->user_cuid)
            ->set("char", "contact_update_name",      $this->user_name)
            ->set("char", "contact_send_email",       $current_setting)
            ->where(    "int",  "system_id",          "=", $system_id)
            ->where_and("char", "contact_netpin_no",  "=", $contact_netpin_no)
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

        //
        // Call updateAllStatuses() in library.php
        //
        if ($this->updateAllStatuses($this->ora, $ticket_no) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        $sys = new cct7_systems($this->ora);

        if ($sys->getSystem($system_id) == false)
        {
            $this->error = $sys->error;
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        if ($current_setting == 'Y')
            $message = "Group email has been set to YES.";
        else
            $message = "Group email has been set to NO.";

        if ($this->putLogContacts($ticket_no, $system_id, $sys->system_hostname, $contact_netpin_no, "EMAIL", $message) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        return true;
    }

    /**
     * @fn    log($system_id, $netpin_no, $message)
     *
     * @brief Create a log entry in cct7_log_contacts identified by $system_id and $netpin_no.
     *
     * @param int    $system_id
     * @param string $netpin_no
     * @param string $message
     *
     * @return bool
     */
    public function log($system_id, $netpin_no, $message)
	{
        $sys = new cct7_systems($this->ora);

        if ($sys->getSystem($system_id) == false)
        {
            $this->error = $sys->error;
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        if ($this->putLogContacts($sys->ticket_no, $system_id, $sys->system_hostname, $netpin_no, "NOTE", $message) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

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

		$tic = new cct7_tickets();

		if ($tic->getTicket($ticket_no) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Cannot sendmail %s contacts.", $ticket_no);
			return false;
		}

		$to        = $tic->owner_email;
		$to_header = sprintf("%s <%s>", $tic->owner_name, $tic->owner_email);

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
			$success = "N";
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable to send email: %s <%s>",
						  $this->owner_name, $this->owner_email);
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
			->value("char",  $tic->owner_cuid)           // sendmail_cuid
			->value("char",  $tic->owner_name)           // sendmail_name
			->value("char",  $tic->owner_email)          // sendmail_email
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
	 * @fn    sendmail($ticket_no, $system_id, $contact_netpin_no, $subject_line, $email_cc, $email_bcc, $message_body, $waiting_only="N")
	 *
	 * @brief Send email to all ticket contacts. (See: ajax_dialog_toolbar_open_contacts.php)
	 *
	 * @param string $ticket_no
	 * @param int    $system_id
	 * @param string $subject_line
	 * @param string $email_cc
	 * @param string $email_bcc
	 * @param string $message_body
	 * @param string $waiting_only  - Y/(N) Send email to those that have not responded (approved).
	 *
	 * @return bool
	 */
	public function sendmail($ticket_no, $system_id, $contact_netpin_no, $subject_line, $email_cc, $email_bcc, $message_body, $waiting_only="N")
    {
		if (strlen($message_body) == 0)
		{
			$this->error = "Message body is empty!";
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Message body is empty");
			return false;
		}

		if ($this->getContactNetpin($system_id, $contact_netpin_no) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
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
		// Gather a list of contacts that approver only.
		//
		// function byContact($ticket_no, $system_id, $contact_id, $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
		//
		if ($list->byContact($ticket_no, $system_id, $this->contact_id, "Y", "N", $waiting_only) == false)
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
			mail($to, $subject_line, $message_body, $headers);
			return true;
		}

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "This is not cct.corp.intranet. Email not actually sent.");

        return true;
    }
}
?>
