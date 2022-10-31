<?php
/**
 * @package    CCT
 * @file       cct7_systems.php
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
//   public function getAssetCenter()
//   public function updateSystemStatus($system_id, $system_work_status)
//   public function setSystemStatus($system_id)
//   public function approve($system_id)
//   public function delete($system_id)
//   public function cancel($system_id)
//   public function reschedule($system_id)
//   public function resetOriginal($system_id)
//

//
//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
    require_once('autoloader.php');  //!< @see includes/autoloader.php
}

/** @class cct7_systems
 *  @brief This class contains all the methods for managing data in cct7_contacts and cct7_connections
 */
class cct7_systems extends library
{
    var $data;
    var $ora;                     // Database connection object
    var $ora2;                    // For putLogSystem()
    var $error;                   // Error message when functions return false
    var $formatter;
    var $scheduler;

    public $system_id;
    public $ticket_no;
    public $system_insert_date_num;
    public $system_insert_date_char;
	public $system_insert_date_char2;
    public $system_insert_cuid;
    public $system_insert_name;
    public $system_update_date_num;
    public $system_update_date_char;
	public $system_update_date_char2;
    public $system_update_cuid;
    public $system_update_name;
    public $system_lastid;
    public $system_hostname;
    public $system_os;
    public $system_usage;
    public $system_location;
    public $system_timezone_name;
    public $system_osmaint_weekly;

    public $system_respond_by_date_num;
    public $system_respond_by_date_char;
	public $system_respond_by_date_char2;

    public $system_work_start_date_num;
    public $system_work_start_date_char;
	public $system_work_start_date_char2;

    public $system_work_end_date_num;
    public $system_work_end_date_char;
	public $system_work_end_date_char2;

    public $system_work_duration;
    public $system_work_status;

    public $schedule_start_date_num;
    public $schedule_start_date_char;
	public $schedule_start_date_char2;

	public $reboot_required;
    public $approvals_required;

    public $remedy_cm_start_date;         // Start Date for the Remedy CM Ticket
    public $remedy_cm_end_date;           // End Date for the Remedy CM Ticket
    public $total_contacts_responded;     // Total contacts responded count
    public $total_contacts_not_responded; // Total contacts not responded count
    public $total_servers_scheduled;      // Total servers scheduled
    public $total_servers_not_scheduled;  // Total servers not scheduled
    public $servers_not_scheduled;        // List of servers not scheduled.
    public $generator_runtime;            // Total minutes and seconds the server took to generate the schedule.

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
	public $cm_start_date_num;
	public $cm_start_date_char;
	public $cm_start_date_char2;
	public $cm_end_date_num;
	public $cm_end_date_char;
	public $cm_end_date_char2;
	public $cm_duration_computed;

    public $authorized;

    //
    // Used by buildServerArray()
    //
    var $servers                 = array();  // Array containing a list of servers
    var $duplicates              = array();  // Array used to make sure we have no duplicates.
    var $target_these_only;                  // "lxomp11m, lxomt12m";
    var $computer_managing_group = array();  // array( 'CMP-UNIX', 'SMU');
    var $computer_os_lite        = array();  // array( 'HPUX', 'Linux', 'SunOS' );
    var $computer_status         = array();  // array( 'PRE-PRODUCTION', 'PRODUCTION' );
    var $computer_contract       = array();  // array( 'IGS FULL CONTRACT UNIX-PROD', 'IGS SUPPORT FS HYPERVISOR (NB)' );
    var $state_and_city          = array();  // array( 'CO:DENVER', 'NE:OMAHA' );
    var $miscellaneous           = array();  // array( 'BCR:GOLD', 'SPECIAL:HANDLING', 'PLATFORM:MIDRANGE' );
    var $system_lists            = array();  // array( '1' );
    var $ip_starts_with;                     // Search for servers where their IP address begins with xxx
    var $invalid_servers         = array();  // List of servers not found in Asset Manager (cct7_computers)

    /** @fn __construct()
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

        // $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->wreq_osmaint = %s", $this->wreq_osmaint);

        $this->ora       = new oracle();

        $this->initalize();

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
			$this->user_work_phone    = '(801) 989-8481';

            $this->manager_cuid       = 'gparkin';
            $this->manager_first_name = 'Greg';
            $this->manager_last_name  = 'Parkin';
            $this->manager_name       = 'Greg Parkin';
            $this->manager_email      = 'gregparkin58@gmail.com';
            $this->manager_job_title  = 'Director';
            $this->manager_company    = 'CMP';
			$this->manager_work_phone = '(801) 989-8481';
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
                $this->user_work_phone    = $this->phone_clean($_SESSION['user_work_phone']);

                $this->manager_cuid       = $_SESSION['manager_cuid'];
                $this->manager_first_name = $_SESSION['manager_first_name'];
                $this->manager_last_name  = $_SESSION['manager_last_name'];
                $this->manager_name       = $_SESSION['manager_name'];
                $this->manager_email      = $_SESSION['manager_email'];
                $this->manager_job_title  = $_SESSION['manager_job_title'];
                $this->manager_company    = $_SESSION['manager_company'];
                $this->manager_work_phone = $this->phone_clean($_SESSION['manager_work_phone']);

                $this->is_debug_on        = $_SESSION['is_debug_on'];
            }
            else
            {
                $this->user_cuid          = 'cctadm';
                $this->user_first_name    = 'Application';
                $this->user_last_name     = 'CCT';
                $this->user_name          = 'CCT Application';
                $this->user_email         = 'gregparkin58@gmail.com';
                $this->user_job_title     = 'CMP-TOOLS';
                $this->user_company       = 'CMP';
                $this->user_access_level  = 'admin';
                $this->user_timezone_name = 'America/Denver';
                $this->user_work_phone    = '(801) 989-8481';

                $this->manager_cuid       = 'gparkin';
                $this->manager_first_name = 'Robert';
                $this->manager_last_name  = 'Pelan';
                $this->manager_name       = 'Bob Pelan';
                $this->manager_email      = 'gregparkin58@gmail.com';
                $this->manager_job_title  = 'Director';
                $this->manager_company    = 'CMP';
                $this->manager_work_phone = '(801) 989-8481';

                $this->is_debug_on        = 'Y';
            }

            $this->debug_start('cct7_systems.html');
        }
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
     *  @return true or false
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

    public function initalize()
    {
        $this->system_id                    = 0;   // |NUMBER|0|NOT NULL|PK: Unique record ID
        $this->ticket_no                    = '';  // |VARCHAR2|20||FK: Link to cct7_tickets record
        $this->system_insert_date_num       = 0;   // |NUMBER|0||GMT UNIX TIME - Date of person who created this record
        $this->system_insert_date_char      = '';
        $this->system_insert_date_char2     = '';
        $this->system_insert_cuid           = '';  // |VARCHAR2|20||CUID of person who created this record
        $this->system_insert_name           = '';  // |VARCHAR2|200||Name of person who created this record
        $this->system_update_date_num       = 0;   // |NUMBER|0||GMT UNIX TIME - Date of person who updated this record
        $this->system_update_date_char      = '';
        $this->system_update_date_char2     = '';
        $this->system_update_cuid           = '';  // |VARCHAR2|20||CUID of person who updated this record
        $this->system_update_name           = '';  // |VARCHAR2|200||Name of person who updated this record
        $this->system_lastid                = 0;   // |NUMBER|0||233494988
        $this->system_hostname              = '';  // |VARCHAR2|255||hvdnp16e
        $this->system_os                    = '';  // |VARCHAR2|20||HPUX
        $this->system_usage                 = '';  // |VARCHAR2|80||PRODUCTION
        $this->system_location              = '';  // |VARCHAR2|80||DENVER
        $this->system_timezone_name         = '';  // |VARCHAR2|200||America/Denver
        $this->system_osmaint_weekly        = '';  // |VARCHAR2|4000||MON,TUE,WED,THU,FRI,SAT,SUN 2200 480
        $this->system_respond_by_date_num   = 0;   // |NUMBER|0||Copied over from cct7_tickets.respond_by_date
        $this->system_respond_by_date_char  = '';
        $this->system_respond_by_date_char2 = '';
        $this->system_work_start_date_num   = 0;   // |NUMBER|0||GMT UNIX TIME - Actual computed work start datetime
        $this->system_work_start_date_char  = '';
        $this->system_work_start_date_char2 = '';
        $this->system_work_end_date_num     = 0;   // |NUMBER|0||GMT UNIX TIME - Actual computed work end datetime
        $this->system_work_end_date_char    = '';
        $this->system_work_end_date_char2   = '';
        $this->system_work_duration         = '';  // |VARCHAR2|30||Actual computed work duration window
        $this->system_work_status           = '';  // |VARCHAR2|20||WAITING, APPROVED, REJECTED, CANCELED, STARTED, COMPLETED, FAILED
        $this->schedule_start_date_char     = '';
        $this->schedule_start_date_char2    = '';
        $this->schedule_start_date_num      = 0;

        $this->reboot_required              = 'Y';
        $this->approvals_required           = 'Y';

        $this->csc_banner1                  = 'Y';
        $this->csc_banner2                  = 'Y';
        $this->csc_banner3                  = 'Y';
        $this->csc_banner4                  = 'Y';
        $this->csc_banner5                  = 'Y';
        $this->csc_banner6                  = 'Y';
        $this->csc_banner7                  = 'Y';
        $this->csc_banner8                  = 'Y';
        $this->csc_banner9                  = 'Y';
        $this->csc_banner10                 = 'Y';

        $this->exclude_virtual_contacts     = 'N';
        $this->disable_scheduler            = 'N';
        $this->maintenance_window           = 'weekly';

		$this->cm_start_date_num            = 0;
		$this->cm_start_date_char           = "";
		$this->cm_start_date_char2          = "";
		$this->cm_end_date_num              = 0;
		$this->cm_end_date_char             = "";
		$this->cm_end_date_char2            = "";
		$this->cm_duration_computed         = "";

        $this->authorized                   = false;
    }
	/**
	 * @param object $t
	 * @param object $s
	 * @param int    $system_id
	 *
	 * @return bool
	 */
	public function addSystemCCT6($t, $s, &$system_id)
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "AddSystemCCT6(%s, %s)",
					  $t->cm_ticket_no, $s->computer_hostname);

		// Retrieve the system information from table: cct6_computers
		if ($this->ora->sql("select * from cct6_computers where computer_hostname = lower('" .
							$s->computer_hostname . "')") == false)
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
		$system_id = $this->ora->next_seq('cct6_systemsseq');

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "new system_id = %d", $system_id);

		// Build the $insert SQL command
		$insert = "insert into cct6_systems (" .
			"system_id, cm_ticket_no, system_work_status, system_insert_date, system_insert_cuid, system_insert_name, " .
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
			"system_original_work_start, system_original_work_end, system_original_work_duration, system_approvals_required " .
			" ) values ( ";

		$this->makeInsertINT(     $insert, $system_id,                                true);  // system_id
		$this->makeInsertCHAR(    $insert, $t->cm_ticket_no,                          true);  // cm_ticket_no
		$this->makeInsertCHAR(    $insert, $s->system_work_status,                    true);  // system_work_status
		$this->makeInsertDateTIME($insert, $t->system_insert_date,                    true);  // system_insert_date
		$this->makeInsertCHAR(    $insert, $s->system_insert_cuid,                    true);  // system_insert_cuid
		$this->makeInsertCHAR(    $insert, $s->system_insert_name,                    true);  // system_insert_name
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
		$this->makeInsertDateTIME($insert, $s->system_actual_work_start,              true);  // system_actual_work_start
		$this->makeInsertDateTIME($insert, $s->system_actual_work_end,                true);  // system_actual_work_end
		$this->makeInsertCHAR(    $insert, $s->system_actual_work_duration,           true);  // system_actual_work_duration
		$this->makeInsertDateTIME($insert, $s->system_original_work_start,            true);  // system_original_work_start
		$this->makeInsertDateTIME($insert, $s->system_original_work_end,              true);  // system_original_work_end
		$this->makeInsertCHAR(    $insert, $s->system_original_work_duration,         true);  // system_original_work_duration
		$this->makeInsertCHAR(    $insert, $t->ticket_approvals_required,             false);  // system_approvals_required

		$insert .= 	" )";

		if ($this->ora->sql($insert) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin",
								   __FILE__, __LINE__);
			return false;
		}

		$this->ora->commit();

		return true;
	}

	/**
	 * @fn    getTicketStatus($ticket_no, &$status)
	 *
	 * @brief Retrieve the ticket status. We don't want to preform certain operations on
	 *        the cct7_systems records when the ticket status is not ACTIVE.
	 *
	 * @param string $ticket_no
	 * @param string $status
	 *
	 * @return bool
	 */
    private function getTicketStatus($ticket_no, &$status)
	{
		$status = "";

		$query  = "select ";
		$query .= "  status ";
		$query .= "from ";
		$query .= "  cct7_tickets ";
		$query .= "where ";
		$query .= sprintf("  ticket_no = '%s'", strtoupper($ticket_no));

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
			$status = $this->ora->status;
			return true;
		}

		return false;
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
			$this->authorized = true;
			return true;
		}

		$this->authorized = false;
		return false;
	}

	/**
	 * @fn    page($system_id, $message)
	 *
	 * @brief Page the on-call primary persons for this system_id
	 *
	 * @param $system_id - system_id record id to cct7_systems
	 * @param $message   - page text message
	 *
	 * @return bool
	 */
	public function page($system_id, $message)
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id=%s, message=%s", $system_id, $message);

		// cct7_systems
		// system_id|NUMBER|0|NOT NULL|PK: Unique record ID
		// ticket_no|VARCHAR2|20|NOT NULL|FK: Link to cct7_tickets record
		// system_insert_date|NUMBER|0||GMT UNIX TIME - Date of person who created this record
		// system_insert_cuid|VARCHAR2|20||CUID of person who created this record
		// system_insert_name|VARCHAR2|200||Name of person who created this record
		// system_update_date|NUMBER|0||GMT UNIX TIME - Date of person who updated this record
		// system_update_cuid|VARCHAR2|20||CUID of person who updated this record
		// system_update_name|VARCHAR2|200||Name of person who updated this record
		// system_lastid|NUMBER|0||233494988
		// system_hostname|VARCHAR2|255||hvdnp16e
		// system_os|VARCHAR2|20||HPUX
		// system_usage|VARCHAR2|80||PRODUCTION
		// system_location|VARCHAR2|80||DENVER
		// system_timezone_name|VARCHAR2|200||(i.e. America/Chicago
		// system_osmaint_weekly|VARCHAR2|4000||MON,TUE,WED,THU,FRI,SAT,SUN 2200 480
		// system_respond_by_date|NUMBER|0||Copied over from cct7_tickets.respond_by_date
		// system_work_start_date|NUMBER|0||GMT UNIX TIME - Actual computed work start datetime
		// system_work_end_date|NUMBER|0||GMT UNIX TIME - Actual computed work end datetime
		// system_work_duration|VARCHAR2|30||Actual computed work duration window
		// system_work_status|VARCHAR2|20||WAITING, READY, REJECTED, CANCELED
		// total_contacts_responded|NUMBER|0||Total number of contacts who have responded
		// total_contacts_not_responded|NUMBER|0||Total number of contacts who have NOT responded
		// original_work_start_date|NUMBER|0||Original scheduled work start date
		// original_work_end_date|NUMBER|0||Original scheduled work end date
		// original_work_duration|VARCHAR2|30||Original schedule work duration

		// cct7_contacts
		// contact_id|NUMBER|0|NOT NULL|PK: Unique record ID
		// system_id|NUMBER|0||FK: cct7_systems.system_id - CASCADE DELETE
		// contact_netpin_no|VARCHAR2|20||CSC/Net-Tool Pin number
		// contact_insert_date|NUMBER|0||Date of person who created this record
		// contact_insert_cuid|VARCHAR2|20||CUID of person who created this record
		// contact_insert_name|VARCHAR2|200||Name of person who created this record
		// contact_update_date|NUMBER|0||Date of person who updated this record
		// contact_update_cuid|VARCHAR2|20||CUID of person who updated this record
		// contact_update_name|VARCHAR2|200||Name of person who updated this record
		// contact_connection|VARCHAR2|80||Grid label: Connections                   - Server connection list
		// contact_server_os|VARCHAR2|80||Grid label: OS                            - Server OS list
		// contact_server_usage|VARCHAR2|80||Grid Label: Status                        - Server OS status: Production, Test, etc.
		// contact_work_group|VARCHAR2|80||Grid Label: Status                        - OS, APP, DBA, APP_DBA
		// contact_approver_fyi|VARCHAR2|80||Grid Label: Notify Type                   - APPROVER or FYI
		// contact_csc_banner|VARCHAR2|200||Grid Label: CSC Support Banners (Primary) - CSC Banner list
		// contact_apps_databases|VARCHAR2|200||Grid Label: Apps/DBMS                     - MAL and MDL list of applications and databases
		// contact_respond_by_date|NUMBER|0||Copied over from cct7_tickets.respond_by_date
		// contact_response_status|VARCHAR2|20||Response Status: WAITING, APPROVED, REJECTED, RESCHEDULE
		// contact_response_date|NUMBER|0||Response Date
		// contact_response_cuid|VARCHAR2|20||Response CUID of the net-group member that approved this work
		// contact_response_name|VARCHAR2|200||Response Name of the net-group member that approved this work
		// contact_send_page|VARCHAR2|10||Do they want a page?   Y/N
		// contact_send_email|VARCHAR2|10||Do they want an email? Y/N

        $query  = "select distinct ";
        $query .= "  s.system_id, ";
        $query .= "  s.ticket_no, ";
        $query .= "  s.system_hostname, ";
        $query .= "  c.contact_netpin_no, ";
        $query .= "  c.contact_send_page ";
        $query .= "from ";
        $query .= "  cct7_systems s, ";
        $query .= "  cct7_contacts c ";
        $query .= "where ";
        $query .= "  s.system_id = " . $system_id . " and ";
        $query .= "  c.system_id = s.system_id and ";
        $query .= "  c.contact_netpin_no is not null ";
        $query .= "order by ";
        $query .= "  c.contact_netpin_no";

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
            if ($this->ora->contact_send_page == "N")
                continue;

            $fastpg        = "/opt/ibmtools/cct6/bin/fastpg.exe";
			$ticket_no     = $this->ora->ticket_no;
			$hostname      = $this->ora->system_hostname;
			$netpin        = $this->ora->contact_netpin_no;
			$from_cuid     = $this->user_cuid;

			if (strlen($message) == 0)
            {
				$event_message = sprintf("Net-Pin: %s", $netpin);
            }
            else
            {
				$event_message = sprintf("Net-Pin: %s - %s", $netpin, $message);
            }

            //
            // Add page callback information to message text.
            //
            $message_with_callback = sprintf("%s. Callback: %s - %s", $message, $this->user_cuid, $this->user_work_phone);

            //
            // Assemble the FASTPG command string
            //
			// fastpg -r4901 "-o(prty=10)" "-m Page everyone in the group"
			// fastpg -r4901 "-o(prty=41)" "-m E-mail the primary and escalate at critical severity"
			// fastpg -r4901 "-o(prty=4)"  "-m Page the primary with no escalation"
			// fastpg -r4901 "-o(prty=50)" "-m E-mail the backup"
			// fastpg -r4901 "-o(prty=52)" "-m E-mail the backup and escalate at high severity"
			// fastpg -r4901 "-o(prty=54)" "-m E-mail the backup and page the primary"
            //
			if ($this->confirm_page == "Y")
			{
				$command = sprintf("%s -G -t %s -f %s -m \"%s\"", $fastpg, $netpin, $from_cuid, $message_with_callback);
			}
			else
			{
				$command = sprintf("%s -oprty=4 -G -t %s -f %s -m \"%s\"", $fastpg, $netpin, $from_cuid, $message_with_callback);
			}

			//
            // Send the page if this is URL cct.corp.intranet
            //
			if ($_SERVER['SERVER_NAME'] == "cct.corp.intranet")
            {
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Sending page: %s", $command);
				$fp = popen($command, "r");

				if ($fp)
				{
					pclose($fp);
				}
            }

            /**
            //
            // Log the page event.
            //
            if ($this->putLogSystem($ticket_no, $system_id, $hostname, "PAGE", $event_message) == false)
            {
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
				return false;
            }
             */
        }

		return true;
	}

    /**
     * @fn    delete($system_id)
     *
     * @brief        If ticket is in DRAFT mode, then we can delete the record.
     *
     * @param int    $system_id
     *
     * @return bool
     */
    public function delete($system_id)
    {
        if ($this->ora->sql("delete from cct7_systems where system_id = %d", $system_id) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s", __FILE__, __FUNCTION__, __LINE__, $this->ora->dbErrMsg);

            return false;
        }

        $this->ora->commit();

        return true;
    }

	/**
     * @fn    reschedule($system_id, $start_date, $end_date)
     *
	 * @brief dialog_toolbar_open_systems.php/ajax_dialog_toolbar_open_systems.php uses this
	 *        method to reschedule the server work window. This method checks any scheduled
     *        windows for this server and any minimal change windows that may be in effect
     *        as recorded in table: cct7_no_changes.
	 *
	 * @param int  $system_id   Record ID for cct7_systems
	 * @param char $start_date  New work start date string: MM/DD/YYYY HR:MI
	 * @param char $end_date    New work end date string: MM/DD/YYYY HR:MI
	 *
	 * @return bool where true is success
	 */
    public function reschedule($system_id, $start_date, $end_date)
    {
		//
		// Get the following:
		//   $this->system_timezone_name    - America/Denver
		//   $this->system_osmaint_window   - Non-formatted
		//   $this->system_work_start_date
		//   $this->system_work_end_date
		//   $this->system_work_duration
		//
		if ($this->getSystem($system_id) == false)
		{
			return false;
		}

		if ($this->system_osmaint_weekly == "Using Remedy start/end/duration")
		{
			$this->error = "Cannot reschedule because this ticket and server is using the Remedy ticket start/end times.";
			return false;
		}

		$ticket_status = "";

		if ($this->getTicketStatus($this->ticket_no, $ticket_status) == false)
		{
			$this->error = sprintf("Unable to determine status for ticket: %s", $this->ticket_no);
			return false;
		}

		if ($ticket_status == "ACTIVE" || $ticket_status == "DRAFT")
		{
			// APPROVED
			// CANCELED
			// FAILED
			// REJECTED
			// STARTING
			// SUCCESS
			// WAITING

			if ($this->system_work_status != "APPROVED" &&
				$this->system_work_status != "WAITING")
			{
				$this->error =
					sprintf("%s status is %s. Cannot reschedule",
							$this->system_hostname, $this->system_work_status);

				return false;
			}
		}
		else
		{
			$this->error =
				sprintf("Cannot reschedule %s when ticket is %s.",
					$this->system_hostname, $ticket_status);

			return false;
		}

		//
        // Convert $start_date and $end_date to GMT.
        //
        //$start_date_num = $this->to_gmt($start_date, $this->system_timezone_name);
		//$end_date_num   = $this->to_gmt($end_date,   $this->system_timezone_name);

		//
		// When doing manual reschedules, use the user's timezone instead of the
		// server's local timezone. This will avoid confusion when the submit their
		// update and all of a sudden it is one or two hours off from where they
		// started.
		//
		$start_date_num =
			$this->to_gmt(
				$start_date,
				$this->system_timezone_name); // $this->user_timezone_name);

		$end_date_num   =
			$this->to_gmt(
				$end_date,
				$this->system_timezone_name); // $this->user_timezone_name);

		if ($end_date_num <= $start_date_num)
        {
            $this->error = sprintf("End Date: (%s), cannot be less than Start Date: (%s).",
                $end_date, $start_date);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);

            return false;
        }

        /**
		//
		// Do these new start/end dates conflict with any other scheduled work for this server?
        //
		$query  = "select ";
		$query .= "  t.ticket_no, ";
		$query .= "  t.cm_ticket_no, ";
		$query .= "  s.system_id, ";
		$query .= "  s.system_hostname, ";
		$query .= "  to_char(fn_number_to_date(s.system_work_start_date, 'MDT'), 'MM/DD/YYYY HH24:MI') as system_work_start_date,  ";
		$query .= "  to_char(fn_number_to_date(s.system_work_end_date, 'MDT'), 'MM/DD/YYYY HH24:MI') as system_work_end_date ";
		$query .= "from ";
		$query .= "  cct7_tickets t, ";
		$query .= "  cct7_systems s ";
		$query .= "where ";
		$query .= "  s.ticket_no = t.ticket_no and ";
		$query .= "  s.system_id != " . $system_id . " and ";
		$query .= "  s.system_hostname = '" . $this->system_hostname . "' and ";
		$query .= "  (" . $start_date_num . " >= s.system_work_start_date and " . $end_date_num   . " <= s.system_work_end_date) or ";
		$query .= "  (" . $start_date_num . " >= s.system_work_start_date and " . $start_date_num . " <= s.system_work_end_date) or ";
		$query .= "  (" . $end_date_num   . " >= s.system_work_start_date and " . $end_date_num   . " <= s.system_work_end_date) ";

		$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s - %s", $this->ora->sql_statement, $this->ora->dbErrMsg);

			return false;
		}

		while ($this->ora->fetch())
		{
			$this->error = sprintf("These dates conflict with %s (%s) From: %s, To: %s",
                $this->ora->ticket_no, $this->ora->cm_ticket_no,
                $this->ora->system_work_start_date,
                $this->ora->system_work_end_date);

			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);

			return false;
		}

		//
        // Get the Minimal Change window information
        //
        $query  = "select ";
		$query .= "  no_change_type, ";
		$query .= "  start_date, ";
		$query .= "  end_date, ";
		$query .= "  reason ";
		$query .= "from ";
		$query .= "  cct7_no_changes ";
		$query .= "where ";
		$query .= "  (" . $start_date_num . " >= start_date and " . $end_date_num   . " <= end_date) or ";
		$query .= "  (" . $start_date_num . " >= start_date and " . $start_date_num . " <= end_date) or ";
		$query .= "  (" . $end_date_num   . " >= start_date and " . $end_date_num   . " <= end_date) ";

		// (1493629200 >= start_date and 1493719200 <= end_date) or  -- start, end
		// (1493629200 >= start_date and 1493629200 <= end_date) or  -- start, start
		// (1493719200 >= start_date and 1493719200 <= end_date);    -- end, end

        if ($this->ora->sql2($query) == false)
        {
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s - %s", $this->ora->sql_statement, $this->ora->dbErrMsg);

			return false;
        }

        while ($this->ora->fetch())
        {
            //
            // Convert start_date and end_date from cct7_no_changes to strings for America/Chicago.
			// All minimal change dates stored in cct7_no_changes are in the Central time zone.
            //
			$start_date_char = $this->gmt_to_format($this->ora->start_date, 'm/d/Y H:i', 'America/Chicago');
			$end_date_char   = $this->gmt_to_format($this->ora->end_date,   'm/d/Y H:i', 'America/Chicago');

			$this->error = sprintf("%s: From: %s, To: %s, %s",
			    $this->ora->no_change_type,
			    $start_date_char,
			    $end_date_char,
			    $this->ora->reason);

			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);

			return false;
        }
		*/

        //
        // Recompute duration string
        //
		$seconds = $end_date_num - $start_date_num;

		$days  = floor($seconds / 60 / 60 / 24);
		$hours = $seconds / 60 / 60 % 24;
		$mins  = $seconds / 60 % 60;
		$secs  = $seconds % 60;

		$duration = sprintf("%02d%s%02d%s%02d", $days, ':', $hours, ':', $mins); // outputs 47:56:15

        //
        // Update the record.
        //
		$rc = $this->ora
			->update('cct7_systems')
			->set("int",    "system_update_date",      $this->now_to_gmt_utime())
			->set("char",   "system_update_cuid",      $this->user_cuid)
			->set("char",   "system_update_name",      $this->user_name)
			->set("int",    "system_work_start_date",  $start_date_num)
			->set("int",    "system_work_end_date",    $end_date_num)
			->set("char",   "system_work_duration",    $duration)
			->where("int",  "system_id", "=", $system_id)
			->execute();

		if ($rc == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			$this->ora->rollback();

			return false;
		}

		//
		// Next we want to update the CCT ticket's scheduled_work_start and scheduled_work_end dates to reflect the
		// changes.
		//
		//
		// Execute stored procedure. (See: ibmtools_cct7/Procedures/updateScheduleDates.sql)
		//
		$query = sprintf("BEGIN updateScheduleDates('%s'); END;", $this->ticket_no);

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		//
		// Create a log entry

		// Work for <ticket>, server %s has been rescheduled: start: xx, end: yy
		//
		$message = sprintf(
			"Work for %s - %s has been rescheduled to start %s and end %s.",
			$this->ticket_no, $this->system_hostname, $start_date, $end_date);

		if ($this->putLogSystem($this->ticket_no, $system_id, $this->system_hostname, 'RESCHEDULE', $message) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		$this->ora->commit();
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Rescheduled completed successfully.");

		return true;
    }

    /**
     * @fn    reschedule($system_id)
     *
     * @brief        Reschedule work to next available maintenance window.
     *
     * @param int    $system_id
     *
     * @return bool
     */
    public function nextMaintenanceWindow($system_id)
    {
        $ticket    = new cct7_tickets($this->ora);
        $scheduler = new scheduler();

		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Here we go!");

        //
        // Get the following:
        //   $this->system_timezone_name    - America/Denver
        //   $this->system_osmaint_window   - Non-formatted
        //   $this->system_work_start_date
        //   $this->system_work_end_date
        //   $this->system_work_duration
        //
        if ($this->getSystem($system_id) == false)
        {
            return false;
        }

        $this_system_work_start_date = $this->system_work_start_date_char;

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Reschedule work for server: %s", $this->system_hostname);

        //
        // Get a list of future scheduled events for this server (if any).
        // This is for conflict analysis that is done in maintwin_scheduler.php, $obj->ComputeStart()
        //
        $scheduled_starts = array();
        $scheduled_ends   = array();

        $query  = "select ";
        $query .= "  system_work_start_date, ";
        $query .= "  system_work_end_date ";
        $query .= "from ";
        $query .= "  cct7_systems ";
        $query .= "where ";
        $query .= "  system_hostname = '" . $this->system_hostname . "' and ";
        $query .= "  system_work_start_date > " . $this->now_to_gmt_utime();

        if ($this->ora->sql2($query) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s - %s", $this->ora->sql_statement, $this->ora->dbErrMsg);
            $this->ora->rollback();

            return false;
        }

        while ($this->ora->fetch())
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Currently Scheduled Work: system_work_start_date: %s, system_work_end_date: %s",
                          $this->ora->system_work_start_date, $this->ora->system_work_end_date);

            array_push($scheduled_starts,  $this->ora->system_work_start_date);
            array_push($scheduled_ends,    $this->ora->system_work_end_date);
        }

        //   $this->system_timezone_name    - America/Denver
        //   $this->system_osmaint_window   - Non-formatted
        //   $this->system_work_start_date
        //   $this->system_work_end_date
        //   $this->system_work_duration

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this_system_work_start_date = %s", $this_system_work_start_date);

        if ($scheduler->ComputeStart(
                $scheduled_starts,              // $scheduled_starts       = array()
                $scheduled_ends,                // $scheduled_ends         = array()
                $this_system_work_start_date,   // $this_system_work_start_date
                $this->system_osmaint_weekly,   // $maintenance_window
                $this->system_timezone_name     // $system_timezone_name   = 'America/Denver'
            ) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $scheduler->error);
            $this->error = sprintf("Scheduler error: %s (%s, line: %s). Please contact Greg Parkin",
                                   $scheduler->error, __FILE__, __LINE__);
            $this->ora->rollback();

            return false;
        }

        //
        // Copy the scheduled start, end, and duration to this objects storage variables.
        // This data is then stored in the cct7_systes record along with all of the other data.
        //
        $this->system_work_start_date_num  = $scheduler->system_work_start_date_num;
        $this->system_work_start_date_char = $scheduler->system_work_start_date_char;
        $this->system_work_end_date_num    = $scheduler->system_work_end_date_num;
        $this->system_work_end_date_char   = $scheduler->system_work_end_date_char;
        $this->system_work_duration        = $scheduler->system_work_duration;

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_work_start_date_num  = %d", $this->system_work_start_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_work_start_date_char = %s", $this->system_work_start_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_work_end_date_num    = %d", $this->system_work_end_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_work_end_date_char   = %s", $this->system_work_end_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_work_duration        = %s", $this->system_work_duration);

        //
        // Okay update cct7_systems
        //
        $rc = $this->ora
            ->update('cct7_systems')
            ->set("int",    "system_update_date",      $this->now_to_gmt_utime())
            ->set("char",   "system_update_cuid",      $this->user_cuid)
            ->set("char",   "system_update_name",      $this->user_name)
            ->set("int",    "system_work_start_date",  $this->system_work_start_date_num)
            ->set("int",    "system_work_end_date",    $this->system_work_end_date_num)
            ->set("char",   "system_work_duration",    $this->system_work_duration)
            ->where("int",  "system_id", "=", $system_id)
            ->execute();

        if ($rc == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                                   $this->ora->sql_statement, $this->ora->dbErrMsg);
            $this->ora->rollback();

            return false;
        }

        //
        // Get the ticket
        //
        if ($ticket->getTicket($this->ticket_no) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                                   $this->ora->sql_statement, $this->ora->dbErrMsg);
            $this->ora->rollback();

            return false;
        }

        $update_cct7_tickets  = false;
        $remedy_cm_start_date = $ticket->remedy_cm_start_date_num;
        $remedy_cm_end_date   = $ticket->remedy_cm_end_date_num;

        //
        // I this start date start before all other start dates then record this start date os the first date date
        // for all servers.
        //
        if ($this->system_work_start_date_num < $ticket->remedy_cm_start_date_num)
        {
            $update_cct7_tickets = true;
            $remedy_cm_start_date = $this->system_work_start_date_num;
        }

        //
        // If this end date is later than any of all the other end dates then record this one was geen the ned date for
        // all servers.
        //
        if ($this->system_work_end_date_num > $ticket->remedy_cm_end_date_num)
        {
            $update_cct7_tickets = true;
            $remedy_cm_end_date = $this->system_work_end_date_num;
        }

        if ($update_cct7_tickets)
        {
            $rc = $this->ora
                ->update('cct7_tickets')
                ->set("int",    "update_date",            $this->now_to_gmt_utime())
                ->set("char",   "update_cuid",            $this->user_cuid)
                ->set("char",   "update_name",            $this->user_name)
                ->set("int",    "remedy_cm_start_date",   $remedy_cm_start_date)
                ->set("int",    "remedy_cm_end_date",     $remedy_cm_end_date)
                ->where("char", "ticket_no", "=",         $this->ticket_no)
                ->execute();

            if ($rc == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                                       $this->ora->sql_statement, $this->ora->dbErrMsg);
                $this->ora->rollback();

                return false;
            }
        }

        //
        // Next we want to update the CCT ticket's scheduled_work_start and scheduled_work_end dates to reflect the
        // changes.
        //
		//
		// Execute stored procedure. (See: ibmtools_cct7/Procedures/updateScheduleDates.sql)
		//
		$query = sprintf("BEGIN updateScheduleDates('%s'); END;", $this->ticket_no);

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

        //
        // Create a log entry
        //
        $message = sprintf("%s work has been rescheduled for server %s.", $this->ticket_no, $this->system_hostname);

        if ($this->putLogSystem($ticket->ticket_no, $system_id, $this->system_hostname, 'RESCHEDULE', $message) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        $this->ora->commit();
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Rescheduled completed successfully.");

        return true;
    }

    /**
     * @fn    resetOriginal($system_id)
     *
     * @brief        Reset work schedule to original start, end times.
     *
     * @param int    $system_id
     *
     * @return bool
     */
    public function resetOriginal($system_id)
    {
        if ($this->getSystem($system_id) == false)
        {
            return false;
        }

		if ($this->system_osmaint_weekly == "Using Remedy start/end/duration")
		{
			$this->error = "Cannot reset because this ticket and server is using the Remedy ticket start/end times.";
			return false;
		}

        $ticket_status = "";

		if ($this->getTicketStatus($this->ticket_no, $ticket_status) == false)
		{
			$this->error = sprintf("Unable to determine status for ticket: %s", $this->ticket_no);
			return false;
		}

		if ($ticket_status == "ACTIVE" || $ticket_status == "DRAFT")
		{
			// APPROVED
			// CANCELED
			// FAILED
			// REJECTED
			// STARTING
			// SUCCESS
			// WAITING

			if ($this->system_work_status != "APPROVED" ||
				$this->system_work_status != "WAITING")
			{
				$this->error =
					sprintf("%s status is %s. Cannot reset to original scheduled times.",
							$this->system_hostname, $this->system_work_status);

				return false;
			}
		}
		else
		{
			$this->error =
				sprintf("Cannot reset to original scheduled times for %s when ticket is %s.",
						$this->system_hostname, $ticket_status);

			return false;
		}

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "original_work_start_date_num = %d", $this->original_work_start_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "original_work_end_date_num = %d",   $this->original_work_end_date_num);

        $rc = $this->ora
            ->update("cct7_systems")
            ->set("int",    "system_update_date",     $this->now_to_gmt_utime())
            ->set("char",   "system_update_cuid",     $this->user_cuid)
            ->set("char",   "system_update_name",     $this->user_name)
            ->set("int",    "system_work_start_date", $this->original_work_start_date_num)
            ->set("int",    "system_work_end_date",   $this->original_work_end_date_num)
            ->set("char",   "system_work_duration",   $this->original_work_duration)
            ->where("int",  "system_id", "=",         $system_id)
            ->execute();

        if ($rc == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                                   $this->ora->sql_statement, $this->ora->dbErrMsg);
            $this->ora->rollback();

            return false;
        }

		//
		// Next we want to update the CCT ticket's scheduled_work_start and scheduled_work_end dates to reflect the
		// changes.
		//
		//
		// Execute stored procedure. (See: ibmtools_cct7/Procedures/updateScheduleDates.sql)
		//
		$query = sprintf("BEGIN updateScheduleDates('%s'); END;", $this->ticket_no);

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

        //
        // Create a log entry
        //
        $message = sprintf("%s work has been reset to original schedule for server %s. Email sent to server contacts.",
                           $this->ticket_no, $this->system_hostname);

        if ($this->putLogSystem($this->ticket_no, $system_id, $this->system_hostname, 'RESET', $message) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        $this->ora->commit();
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Reset to original scheduled dates.");

        return true;
    }

    /**
     * @fn    log($system_id, $message)
     *
     * @brief Create a log entry in cct7_log_systems identified by $system_id.
     *
     * @param int    $system_id
     * @param string $message
     *
     * @return bool
     */
    public function log($system_id, $message)
    {
        if ($this->getSystem($system_id) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        if ($this->putLogSystem($this->ticket_no, $system_id, $this->system_hostname, "NOTE", $message) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        return true;
    }

	/**
	 * @fn    sendmailTicketOwner($system_id, $subject_line, $email_cc, $email_bcc, $message_body)
	 *
	 * @brief Called from ajax_dialog_toolbar_open_systems.php in response from a user's request
	 *        to send a email to the ticket owner from dialog_toolbar_open_systems.php
	 *
	 * @param int    $system_id
	 * @param string $subject_line
	 * @param string $email_cc
	 * @param string $email_bcc
	 * @param string $message_body
	 *
	 * @return bool
	 */
	public function sendmailTicketOwner($system_id, $subject_line, $email_cc, $email_bcc, $message_body)
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

		$query = "select ticket_no from cct7_systems where system_id = " . $system_id;

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
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);
			$this->error = sprintf("cct7_systems record not found for system_id = %d", $system_id);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
			return false;
		}

		$ticket_no = $this->ora->ticket_no;

		$tic = new cct7_tickets();

		if ($tic->getTicket($ticket_no) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Cannot retrieve ticket: %s", $ticket_no);
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
     * @fn    sendmail($system_id, $subject_line, $email_cc, $email_bcc, $message_body)
     *
     * @brief Send email to all contacts identified by $system_id
     *
     * @param $system_id
     * @param $subject_line
     * @param $email_cc
     * @param $email_bcc
     * @param $message_body
	 * @param string $waiting_only  - Y/(N) Send email to those that have not responded (approved).
     *
     * @return bool
     */
    public function sendmail($system_id, $subject_line, $email_cc, $email_bcc, $message_body, $waiting_only="N")
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
		// function bySystem($system_id, $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
		//
		if ($list->bySystem($system_id, "Y", "N", $waiting_only) == false)
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
     * @fn     getSystem($system_id)
     *
     * @brief  retrieve system record in cct7_systems identified by $system_id
     *
     * @param  int $system_is is the primary key
     *
     * @return true or false where true is success
     */
    public function getSystem($system_id)
    {
    	$this->initalize();

        $rc = $this->ora
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
            ->where("int", "system_id", '=', $system_id)
            ->execute();

        if ($rc == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
            return false;
        }

        if ($this->ora->fetch() == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->error = "Unable to fetch system record from cct7_computers where system_id = " . $system_id;
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        $mmddyyyy_hhmm_tz = 'm/d/Y H:i T';
        $mmddyyyy_hhmm    = 'm/d/Y H:i';
        $mmddyyyy         = 'm/d/Y';
        $mmddyyyy_tz      = 'm/d/Y T';

        $this->system_id                    = $this->ora->system_id;
        $this->ticket_no                    = $this->ora->ticket_no;
        $this->system_insert_date_num       = $this->ora->system_insert_date;

        $this->system_insert_date_char      =
			$this->gmt_to_format(
				$this->ora->system_insert_date, $mmddyyyy_hhmm_tz,
				$this->user_timezone_name);

		$this->system_insert_date_char2      =
			$this->gmt_to_format(
				$this->ora->system_insert_date, $mmddyyyy_hhmm,
				'America/Denver');

        $this->system_insert_cuid           = $this->ora->system_insert_cuid;
        $this->system_insert_name           = $this->ora->system_insert_name;
        $this->system_update_date_num       = $this->ora->system_update_date;

        $this->system_update_date_char      =
			$this->gmt_to_format(
				$this->ora->system_update_date,
				$mmddyyyy_hhmm_tz,
				$this->user_timezone_name);

		$this->system_update_date_char2      =
			$this->gmt_to_format(
				$this->ora->system_update_date,
				$mmddyyyy_hhmm,
				'America/Denver');

        $this->system_update_cuid           = $this->ora->system_update_cuid;
        $this->system_update_name           = $this->ora->system_update_name;
        $this->system_lastid                = $this->ora->system_lastid;
        $this->system_hostname              = $this->ora->system_hostname;
        $this->system_os                    = $this->ora->system_os;
        $this->system_usage                 = $this->ora->system_usage;
        $this->system_location              = $this->ora->system_location;
        $this->system_timezone_name         = $this->ora->system_timezone_name;
        $this->system_osmaint_weekly        = $this->ora->system_osmaint_weekly;

        $this->system_respond_by_date_num   = $this->ora->system_respond_by_date;

        $this->system_respond_by_date_char  =
			$this->gmt_to_format(
				$this->ora->system_respond_by_date,
				$mmddyyyy_tz,
				$this->user_timezone_name);

		$this->system_respond_by_date_char2  =
			$this->gmt_to_format(
				$this->ora->system_respond_by_date,
				$mmddyyyy,
				'America/Denver');

        if ($this->ora->disable_schedule == "Y")
		{
			//
			// This means the scheduler was disabled and the actual Remedy start/end/duration information is what
			// is coded in system_work_start_date, system_work_end_date, and system_work_duration. What we want to
			// do is simulate Remedy by converting the dates and times to the user's local server desktop time.
			//

			$remedy_tz = "America/Denver";

			if (isset($_SERVER['remedy_timezone_name']) && strlen($_SERVER['remedy_timezone_name']) > 0)
			{
				$remedy_tz = $_SERVER['remedy_timezone_name'];
			}

			$this->system_work_start_date_num     = $this->ora->system_work_start_date;

			$this->system_work_start_date_char    =
				$this->gmt_to_format(
					$this->ora->system_work_start_date,
					$mmddyyyy_hhmm_tz,
					$remedy_tz); // $this->user_timezone_name);

			$this->system_work_start_date_char2    =
				$this->gmt_to_format(
					$this->ora->system_work_start_date,
					$mmddyyyy_hhmm,
					$remedy_tz); // $this->user_timezone_name);


			$this->system_work_end_date_num       = $this->ora->system_work_end_date;

			$this->system_work_end_date_char      =
				$this->gmt_to_format(
					$this->ora->system_work_end_date,
					$mmddyyyy_hhmm_tz,
					$remedy_tz); // $this->user_timezone_name);

			$this->system_work_end_date_char2      =
				$this->gmt_to_format(
					$this->ora->system_work_end_date,
					$mmddyyyy_hhmm,
					$remedy_tz); // $this->user_timezone_name);

			$this->system_work_duration           = $this->ora->system_work_duration;


			$this->original_work_start_date_num   = $this->ora->original_work_start_date;

			$this->original_work_start_date_char  =
				$this->gmt_to_format(
					$this->ora->original_work_start_date,
					$mmddyyyy_hhmm_tz,
					$remedy_tz); // $this->user_timezone_name);

			$this->original_work_start_date_char2  =
				$this->gmt_to_format(
					$this->ora->original_work_start_date,
					$mmddyyyy_hhmm,
					$remedy_tz); // $this->user_timezone_name);


			$this->original_work_end_date_num     = $this->ora->original_work_end_date;

			$this->original_work_end_date_char    =
				$this->gmt_to_format(
					$this->ora->original_work_end_date,
					$mmddyyyy_hhmm_tz,
					$remedy_tz); // $this->user_timezone_name);

			$this->original_work_end_date_char2    =
				$this->gmt_to_format(
					$this->ora->original_work_end_date,
					$mmddyyyy_hhmm,
					$remedy_tz); // $this->user_timezone_name);

			$this->original_work_duration         = $this->ora->original_work_duration;
		}
		else
		{
			$this->system_work_start_date_num     = $this->ora->system_work_start_date;

			$this->system_work_start_date_char    =
				$this->gmt_to_format(
					$this->ora->system_work_start_date,
					$mmddyyyy_hhmm_tz,
					$this->ora->system_timezone_name); // $this->user_timezone_name);

			$this->system_work_start_date_char2    =
				$this->gmt_to_format(
					$this->ora->system_work_start_date,
					$mmddyyyy_hhmm,
					$this->ora->system_timezone_name); // $this->user_timezone_name);


			$this->system_work_end_date_num       = $this->ora->system_work_end_date;

			$this->system_work_end_date_char      =
				$this->gmt_to_format(
					$this->ora->system_work_end_date,
					$mmddyyyy_hhmm_tz,
					$this->ora->system_timezone_name); // $this->user_timezone_name);

			$this->system_work_end_date_char2      =
				$this->gmt_to_format(
					$this->ora->system_work_end_date,
					$mmddyyyy_hhmm,
					$this->ora->system_timezone_name); // $this->user_timezone_name);


			$this->system_work_duration           = $this->ora->system_work_duration;


			$this->original_work_start_date_num   = $this->ora->original_work_start_date;

			$this->original_work_start_date_char  =
				$this->gmt_to_format(
					$this->ora->original_work_start_date,
					$mmddyyyy_hhmm_tz,
					$this->ora->system_timezone_name); // $this->user_timezone_name);

			$this->original_work_start_date_char2  =
				$this->gmt_to_format(
					$this->ora->original_work_start_date,
					$mmddyyyy_hhmm,
					$this->ora->system_timezone_name); // $this->user_timezone_name);


			$this->original_work_end_date_num     = $this->ora->original_work_end_date;

			$this->original_work_end_date_char    =
				$this->gmt_to_format(
					$this->ora->original_work_end_date,
					$mmddyyyy_hhmm_tz,
					$this->ora->system_timezone_name); // $this->user_timezone_name);

			$this->original_work_end_date_char2    =
				$this->gmt_to_format(
					$this->ora->original_work_end_date,
					$mmddyyyy_hhmm,
					$this->ora->system_timezone_name); // $this->user_timezone_name);


			$this->original_work_duration         = $this->ora->original_work_duration;
		}

        $this->system_work_status           = $this->ora->system_work_status;

        $this->total_contacts_responded     = $this->ora->total_contacts_responded;
        $this->total_contacts_not_responded = $this->ora->total_contacts_not_responded;

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id                      = %d",  $this->system_id);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no                      = %s",  $this->ticket_no);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_insert_date_num         = %d",  $this->system_insert_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_insert_date_char        = %s",  $this->system_insert_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_insert_cuid             = %s",  $this->system_insert_cuid);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_insert_name             = %s",  $this->system_insert_name);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_update_date_num         = %d",  $this->system_update_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_update_date_char        = %s",  $this->system_update_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_update_cuid             = %s",  $this->system_update_cuid);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_update_name             = %s",  $this->system_update_name);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_lastid                  = %s",  $this->system_lastid);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_hostname                = %d",  $this->system_hostname);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_os                      = %s",  $this->system_os);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_usage                   = %s",  $this->system_usage);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_location                = %s",  $this->system_location);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_timezone_name           = %s",  $this->system_timezone_name);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_osmaint_weekly          = %s",  $this->system_osmaint_weekly);

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_respond_by_date_num     = %d",  $this->system_respond_by_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_respond_by_date_char    = %s",  $this->system_respond_by_date_char);

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_work_start_date_num     = %d",  $this->system_work_start_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_work_start_date_char    = %s",  $this->system_work_start_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_work_end_date_num       = %d",  $this->system_work_end_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_work_end_date_char      = %s",  $this->system_work_end_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_work_duration           = %s",  $this->system_work_duration);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_work_status             = %s",  $this->system_work_status);

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_contacts_responded       = %d",  $this->total_contacts_responded);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "total_contacts_not_responded   = %d",  $this->total_contacts_not_responded);

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "original_work_start_date_num   = %d",  $this->original_work_start_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "original_work_start_date_char  = %s",  $this->original_work_start_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "original_work_end_date_num     = %d",  $this->original_work_end_date_num);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "original_work_end_date_char    = %s",  $this->original_work_end_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "original_work_duration         = %s",  $this->original_work_duration);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "disable_schedule               = %s",  $this->ora->disable_schedule);

        $this->owner();  // Set $this->authorized (true or false)

        return true;
    }

	/** @fn     checkForSystem($ticket_no, $hostname)
	 *
	 *  @brief  Used to see if the hostname is already defined this ticket.
     *
	 * @param  string $ticket_no is the CCT ticket number.
	 * @param  string $hostname is the server hostname.
     *
     * @return bool true or false where true means the server is already in the list for this ticket.
	 */
    public function checkForSystem($ticket_no, $hostname)
    {
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s", $ticket_no);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "hostname  = %s", $hostname);

		$query  = sprintf("select system_hostname from cct7_systems where ticket_no = '%s' and system_hostname = '%s'", $ticket_no, $hostname);

		if ($this->ora->sql2($query) == false)
        {
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
			return false;
        }

        if ($this->ora->fetch())
        {
			$this->error = sprintf("Hostname: %s is already defined in this ticket: %s", $hostname, $ticket_no);
			return true;
        }

        return false;
    }

    /** @fn     addSystem($ticket_no)
     *
     *  @brief  Create a cct7_systems record for this hostname
     *
     *  @param  string $ticket_no is the Primary record key to cct7_tickets
     *
     *  @return int $system_id which is greater than 0. 0 means save failed.
     */
    public function addSystem($ticket_no)
    {
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no       = %s", $ticket_no);

        //
        // Build the cct7_systems record for this $ticket_no and $hostname
        //
        $system_id = $this->ora->next_seq('cct7_systemsseq');

        // system_id|NUMBER|0|NOT NULL|PK: Unique record ID
        // ticket_no|VARCHAR2|20|NOT NULL|FK: Link to cct7_tickets record
        // system_insert_date|NUMBER|0||GMT UNIX TIME - Date of person who created this record
        // system_insert_cuid|VARCHAR2|20||CUID of person who created this record
        // system_insert_name|VARCHAR2|200||Name of person who created this record
        // system_update_date|NUMBER|0||GMT UNIX TIME - Date of person who updated this record
        // system_update_cuid|VARCHAR2|20||CUID of person who updated this record
        // system_update_name|VARCHAR2|200||Name of person who updated this record
        // system_lastid|NUMBER|0||233494988
        // system_hostname|VARCHAR2|255||hvdnp16e
        // system_os|VARCHAR2|20||HPUX
        // system_usage|VARCHAR2|80||PRODUCTION
        // system_location|VARCHAR2|80||DENVER
        // system_timezone_name|VARCHAR2|200||(i.e. America/Chicago
        // system_osmaint_weekly|VARCHAR2|4000||MON,TUE,WED,THU,FRI,SAT,SUN 2200 480
        // system_respond_by_date|NUMBER|0||Copied over from cct7_tickets.respond_by_date
        // system_work_start_date|NUMBER|0||GMT UNIX TIME - Actual computed work start datetime
        // system_work_end_date|NUMBER|0||GMT UNIX TIME - Actual computed work end datetime
        // system_work_duration|VARCHAR2|30||Actual computed work duration window
        // system_work_status|VARCHAR2|20||WAITING, APPROVED, REJECTED, CANCELED, STARTED, COMPLETED, FAILED
        // total_contacts_responded|NUMBER|0||Total number of contacts who have responded
        // total_contacts_not_responded|NUMBER|0||Total number of contacts who have NOT responded
        // original_work_start_date|NUMBER|0||Original scheduled work start date
        // original_work_end_date|NUMBER|0||Original scheduled work end date
        // original_work_duration|VARCHAR2|30||Original schedule work duration
		// disable_schedule|VARCHAR2|1||Disable scheduler Y/N where Y means we are using the Remedy IR start/end/duration for all servers.

		//
		// If the following values are 0 and empty strings, then $this->disable_scheduler == 'Y'
		//
		// $this->system_work_start_date_num  = 0;
		// $this->system_work_start_date_char = '';
		// $this->system_work_end_date_num    = 0;
		// $this->system_work_end_date_char   = '';
		// $this->system_work_duration        = '';
		//

        $rc = $this->ora
            ->insert("cct7_systems")
            ->column("system_id")               // |NUMBER|0|NOT NULL|PK: Unique record ID
            ->column("ticket_no")               // |VARCHAR2|20||FK: Link to cct7_tickets record
            ->column("system_insert_date")      // |NUMBER|0||GMT UNIX TIME - Date of person who created this record
            ->column("system_insert_cuid")      // |VARCHAR2|20||CUID of person who created this record
            ->column("system_insert_name")      // |VARCHAR2|200||Name of person who created this record
            ->column("system_lastid")           // |NUMBER|0||233494988
            ->column("system_hostname")         // |VARCHAR2|255||hvdnp16e
            ->column("system_os")               // |VARCHAR2|20||HPUX
            ->column("system_usage")            // |VARCHAR2|80||PRODUCTION
            ->column("system_location")         // |VARCHAR2|80||DENVER
            ->column("system_timezone_name")    // |VARCHAR2|200||(i.e. America/Chicago
            ->column("system_osmaint_weekly")   // |VARCHAR2|4000||MON,TUE,WED,THU,FRI,SAT,SUN 2200 480
            ->column("system_respond_by_date")  // |NUMBER|0||Copied over from cct7_tickets.respond_by_date
            ->column("system_work_start_date")  // |NUMBER|0||GMT UNIX TIME - Actual computed work start datetime
            ->column("system_work_end_date")    // |NUMBER|0||GMT UNIX TIME - Actual computed work end datetime
            ->column("system_work_duration")    // |VARCHAR2|30||Actual computed work duration window
            ->column("system_work_status")      // |VARCHAR2|20||WAITING, APPROVED, REJECTED, CANCELED, STARTED, COMPLETED, FAILED
            ->column("original_work_start_date")
            ->column("original_work_end_date")
            ->column("original_work_duration")
			->column("change_date")
			->column("disable_schedule")
            ->value("int",  $system_id)                        // system_id
            ->value("char", $ticket_no)                        // ticket_no
            ->value("int",  $this->now_to_gmt_utime())         // insert_date
            ->value("char", $this->user_cuid)                  // insert_cuid
            ->value("char", $this->user_name)                  // insert_name
            ->value("int",  $this->system_lastid)              // system_lastid
            ->value("char", $this->system_hostname)            // system_hostname
            ->value("char", $this->system_os)                  // system_os
            ->value("char", $this->system_usage)               // system_usage
            ->value("char", $this->system_location)            // system_location
            ->value("char", $this->system_timezone_name)       // system_timezone_name
            ->value("char", $this->system_osmaint_weekly)      // system_osmaint_weekly
            ->value("int",  $this->system_respond_by_date_num) // system_respond_by_date
            ->value("int",  $this->system_work_start_date_num) // system_work_start
            ->value("int",  $this->system_work_end_date_num)   // system_work_end
            ->value("char", $this->system_work_duration)       // system_work_duration
            ->value("char", $this->system_work_status)         // system_work_status
            ->value("int",  $this->system_work_start_date_num) // original_work_start_date (copy of system_work_start_date_num)
            ->value("int",  $this->system_work_end_date_num)   // original_work_end_date   (copy of system_work_end_date_num)
            ->value("char", $this->system_work_duration)       // original_work_duration   (copy of system_work_duration)
			->value("int",  $this->change_date)
			->value("char", $this->disable_scheduler)
            ->execute();
            
        if ($rc == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
            return 0;
        }

        $this->ora->commit();

        return $system_id;
    }

    /** @fn     newWorkSchedule($ticket_no)
     *
     *  @brief  This method is called in toolbar_new.php step(). All the user's data should have been copied into
     *          this objects property variables before calling this method. Upon successfully return a new work
     *          schedule will have been created in "DRAFT" mode. The user will then have to latter submit to "ACTIVATE"
     *          the work request in order for the users to get their notifications so they can approve the work.
     *
     *  @brief  1. Generate an array of servers: $this->servers[$this->ora->computer_hostname] = $this->ora->computer_lastid;
     *          2. Pull server data from cct7_computers one server at a time.
     *          3. Get server's timezone name.
     *          4. Format the server's weekly os maintenance window string.
     *          5. Schedule the server work using the os maintenance window and schedule_work_start_date
     *          6. Create the new record for the server in cct7_systems and link it back to cct7_tickets ($ticket_no).
     *          7. Go get all the contact information and build cct7_contacts records for each server.
     *          8. Set all the status fields in cc7_contacts, cct7_systems and cct7_tickets based upon approvals_required.
     *
     *  @param  string $ticket_no - record id in cct7_tickets
     *
     *  @return true or false where true means success
     */
    public function newWorkSchedule($ticket_no)
    {
        $scheduler = new scheduler();
        $contacts  = new cct7_contacts();

        $contacts->csc_banner1  = $this->csc_banner1;
        $contacts->csc_banner2  = $this->csc_banner2;
        $contacts->csc_banner3  = $this->csc_banner3;
        $contacts->csc_banner4  = $this->csc_banner4;
        $contacts->csc_banner5  = $this->csc_banner5;
        $contacts->csc_banner6  = $this->csc_banner6;
        $contacts->csc_banner7  = $this->csc_banner7;
        $contacts->csc_banner8  = $this->csc_banner8;
        $contacts->csc_banner9  = $this->csc_banner9;
        $contacts->csc_banner10 = $this->csc_banner10;

        $this->cm_start_date = 0;                // Start Date for the Remedy CM Ticket
        $this->cm_end_date = 0;                  // End Date for the Remedy CM Ticket
        $this->total_contacts_responded = 0;     // Total scheduled servers
        $this->total_contacts_not_responded = 0; // Total servers not scheduled
        $this->servers_not_scheduled = '';       // Servers not scheduled. Not found in cct7_computers.
        $this->generator_runtime = '';           // Total minutes and seconds the server took to generate the schedule.

        $this->timeStart();

        //
        // One or more of the following class properties must be set in order to build $this->servers[]
        //
        // $this->target_these_only;                  "lxomp11m, lxomt12m";
        // $this->computer_managing_group = array();  array( 'CMP-UNIX', 'SMU');
        // $this->computer_os_lite        = array();  array( 'HPUX', 'Linux', 'SunOS' );
        // $this->computer_status         = array();  array( 'PRE-PRODUCTION', 'PRODUCTION' );
        // $this->computer_contract       = array();  array( 'IGS FULL CONTRACT UNIX-PROD', 'IGS SUPPORT FS HYPERVISOR (NB)' );
        // $this->state_and_city          = array();  array( 'CO:DENVER', 'NE:OMAHA' );
        // $this->miscellaneous           = array();  array( 'BCR:GOLD', 'SPECIAL:HANDLING', 'PLATFORM:MIDRANGE' );
        // $this->system_lists            = array();  array( '1' );
        // $this->ip_starts_with;                           Search for servers where their IP address begins with xxx
        //
        // $this->schedule_start_date_char
        // $this->schedule_start_date_num
        //

        /**
         * Step 1. Generate an array of servers: $this->servers[$this->ora->computer_hostname] = $this->ora->computer_lastid;
         */
        $x = 0;

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "running getSystemLists()");

        if (($i = $this->getSystemLists()) == -1)
        {
            $this->debug4(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        $x += $i;

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "running getTheseOnly()");

        if (($i = $this->getTheseOnly()) == -1)
        {
            $this->debug4(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        $x += $i;

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "running getAssetCenter()");

        if (($i = $this->getAssetCenter())  == -1)
        {
            $this->debug4(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        $x += $i;

        if ($x == 0)
        {
            $this->debug4(__FILE__, __FUNCTION__, __LINE__, "Search criteria did not produce any servers for your work request.");
            $this->error .= "Search criteria did not produce any servers for your work request. ";
            return false;
        }

        $this->total_servers_not_scheduled = 0;
        
        foreach ($this->invalid_servers as $host)
        {
            ++$this->total_servers_not_scheduled;
            
            if ($this->servers_not_scheduled == '')
            {
                $this->servers_not_scheduled = $host;
            }
            else
            {
                $this->servers_not_scheduled .= ", " . $host;
            }
        }

        $this->servers_not_scheduled = trim(substr($this->servers_not_scheduled, 0, 4000));

        //
        // We now have our list of servers in the $this->servers array.
        //
        foreach ($this->servers as $hostname => $lastid)
        {
            ++$this->total_servers_scheduled;

            /**
             * Step 2. Pull server data from cct7_computers one server at a time.
             */
            $query  = "select ";
            $query .= "  computer_lastid                       as system_lastid, ";           // 2348234
            $query .= "  computer_hostname                     as system_hostname, ";         // lxdnp44a
            $query .= "  computer_os_lite                      as system_os, ";               // Linux
            $query .= "  computer_status                       as system_usage, ";            // PRODUCTION
            $query .= "  computer_city ||', '|| computer_state as system_location, ";         // DENVER, CO
            $query .= "  upper(computer_city)                  as city, ";                    // DENVER
            $query .= "  upper(computer_state)                 as state, ";                   // CO
            $query .= "  computer_osmaint_weekly               as system_osmaint_weekly, ";   // MON,TUE,WED,THU,FRI,SAT,SUN 2200 480
			$query .= "  computer_osmaint_monthly              as system_osmaint_monthly, ";  // 2 SAT 16:00 1380
			$query .= "  computer_osmaint_quarterly            as system_osmaint_quarterly "; // JAN,APR,JUL,OCT 3 SAT 16:00 1380
            $query .= "from ";
            $query .= "  cct7_computers ";
            $query .= "where ";
            $query .= "  computer_lastid = " . $lastid;

            if ($this->ora->sql2($query) == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
                return false;
            }

            if ($this->ora->fetch() == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Unable retrieve cct7_computers record for computer_lastid = %d", $lastid);

                ++$this->total_contacts_not_responded;  // Total servers not scheduled

                if (strlen($this->servers_not_scheduled) == 0)
                {
                    $this->servers_not_scheduled = $hostname;
                }
                else
                {
                    $this->servers_not_scheduled .= ', ' . $hostname;
                }

                continue;
            }

            $this->system_lastid            = $this->ora->system_lastid;
            $this->system_hostname          = $this->ora->system_hostname;
            $this->system_os                = $this->ora->system_os;
            $this->system_usage             = $this->ora->system_usage;
            $this->system_location          = $this->ora->system_location;

            //
			// The database field in cct7_systems.systems_osmaint_weekly was originally designed to only
			// be the used to hold the weekly os maintenance window. Now users want to be able to select
			// between weekly, monthly and quarterly window information found in CSC for each server. So
			// now the system_osmaint_weekly can hold either one of the three.
			//
            switch ( $this->maintenance_window )
			{
				case "weekly":
					$this->system_osmaint_weekly    = $this->ora->system_osmaint_weekly;
					break;
				case "monthly":
					$this->system_osmaint_weekly    = $this->ora->system_osmaint_monthly;
					break;
				case "quarterly":
					$this->system_osmaint_weekly    = $this->ora->system_osmaint_quarterly;
					break;
				case "remedy":
					$this->system_osmaint_weekly    = "Using Remedy start/end/duration";
					break;
				default:
					$this->system_osmaint_weekly    = sprintf("Unknown: %s", $this->maintenance_window);
					break;
			}

            $this->system_timezone_name     = '';
            $city                           = $this->ora->city;
            $state                          = $this->ora->state;

            /**
             * Step 3. Get server's timezone name.
             */
            $this->system_timezone_name  = 'America/Denver';

            $query  = "select ";
            $query .= "  timezone ";
            $query .= "from ";
            $query .= "  cct7_timezone ";
            $query .= "where ";
            $query .= "  country = 'US' and ";
            $query .= "  city    = '" . $city . "' and ";
            $query .= "  state   = '" . $state . "'";

            if ($this->ora->sql2($query) == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                $this->error = sprintf("%s - %s", $this->ora->sql_statement, $this->ora->dbErrMsg);
                return false;
            }

            if ($this->ora->fetch())
            {
				$this->debug1(__FILE__, __FUNCTION__, __LINE__,
                              "Timezone for %s is %s",
							  $this->system_hostname, $this->ora->timezone);
                $this->system_timezone_name = $this->ora->timezone;
            }
            else
            {
				$this->debug1(__FILE__, __FUNCTION__, __LINE__,
                              "Unable to find timezone info for %s (%s) (%s) in cct7_timezone. Using America/Denver as a default.",
							  $this->system_hostname, $city, $state);
                $this->system_timezone_name = 'America/Denver';
            }

			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "disable_scheduler = %s", $this->disable_scheduler);

            if ($this->disable_scheduler == "N")
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Running scheduler");

				/**
				 * Step 4 Get a list of future scheduled events for this server (if any).
				 *        This is for conflict analysis that is done in maintwin_scheduler.php, $obj->ComputeStart()
				 */
				$scheduled_starts = array();
				$scheduled_ends   = array();

				$query  = "select ";
				$query .= "  system_work_start_date, ";
				$query .= "  system_work_end_date ";
				$query .= "from ";
				$query .= "  cct7_systems ";
				$query .= "where ";
				$query .= "  system_hostname = '" . $this->system_hostname . "' and ";
				$query .= "  system_work_start_date >= " . $this->now_to_gmt_utime();

				if ($this->ora->sql2($query) == false)
				{
					$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
					$this->error = sprintf("%s - %s", $this->ora->sql_statement, $this->ora->dbErrMsg);
					return false;
				}

				while ($this->ora->fetch())
				{
					array_push($scheduled_starts,  $this->ora->system_work_start_date);
					array_push($scheduled_ends,    $this->ora->system_work_end_date);
				}

				/**
				 * Copy in the CMP minimal change dates into $scheduled_starts and $scheduled_ends.
				 * This will prevent the scheduler from scheduling dates during the minimal change periods.
				 */
				$query  = "select ";
				$query .= "  start_date, ";
				$query .= "  end_date ";
				$query .= "from ";
				$query .= "  cct7_no_changes ";
				$query .= "where ";
				$query .= "  start_date >= " . $this->now_to_gmt_utime();

				if ($this->ora->sql2($query) == false)
				{
					$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
					$this->error = sprintf("%s - %s", $this->ora->sql_statement, $this->ora->dbErrMsg);
					return false;
				}

				while ($this->ora->fetch())
				{
					array_push($scheduled_starts,  $this->ora->start_date);
					array_push($scheduled_ends,    $this->ora->end_date);
				}

				/**
				 * Step 4. Format the server's [weekly,monthly,quarterly] os maintenance window string.
				 */
				if (strlen($this->system_osmaint_weekly) > 0)
				{
					$system_osmaint_weekly = $this->system_osmaint_weekly;
				}
				else
				{
					$system_osmaint_weekly = '+0+TUE,THU+0200+180'; // Default: TUE,THU 02:00 180
				}

				$start_date = date('m/d/Y H:i', strtotime($this->schedule_start_date_char));

				if ($scheduler->ComputeStart(
						$scheduled_starts,             // $scheduled_starts=array(),
						$scheduled_ends,               // $scheduled_ends=array(),
						$start_date,                   // $schedule_starting_date,
						$system_osmaint_weekly,        // $maintenance_window,
						$this->system_timezone_name    // $system_timezone_name='America/Denver')
					) == false)
				{
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $scheduler->error);
					$this->error = sprintf("Scheduler error: %s (%s, line: %s). Please contact Greg Parkin",
										   $scheduler->error, __FILE__, __LINE__);
					return false;
				}

				switch ( $this->maintenance_window )
				{
					case "weekly":
						$this->system_osmaint_weekly    = "Weekly: "    . $system_osmaint_weekly;
						break;
					case "monthly":
						$this->system_osmaint_weekly    = "Monthly: "   . $system_osmaint_weekly;
						break;
					case "quarterly":
						$this->system_osmaint_weekly    = "Quarterly: " . $system_osmaint_weekly;
						break;
					case "remedy":
						$this->system_osmaint_weekly    = "Using Remedy start/end dates";
						break;
					default:
						$this->system_osmaint_weekly    = sprintf("Unknown: %s", $this->maintenance_window);
						break;
				}

				//
				// Copy the scheduled start, end, and duration to this objects storage variables.
				// This data is then stored in the cct7_systes record along with all of the other data.
				//
				$this->system_work_start_date_num  = $scheduler->system_work_start_date_num;
				$this->system_work_start_date_char = $scheduler->system_work_start_date_char;
				$this->system_work_end_date_num    = $scheduler->system_work_end_date_num;
				$this->system_work_end_date_char   = $scheduler->system_work_end_date_char;
				$this->system_work_duration        = $scheduler->system_work_duration;

				//
				// If this start date starts before all other start dates then record this start date os the first date
				// for all servers.
				//
				if ($this->cm_start_date == 0 || $this->system_work_start_date_num < $this->remedy_cm_start_date)
				{
					$this->cm_start_date = $this->system_work_start_date_num;
				}

				//
				// If this end date is later than any of all the other end dates then record this one was for the end date
				// for all servers.
				//
				if ($this->cm_end_date == 0 || $this->system_work_end_date_num > $this->cm_end_date)
				{
					$this->cm_end_date = $this->system_work_end_date_num;
				}

				//
				// Values for $this->remedy_cm_start_date and $this->remedy_cm_end_date and used to update the cct7_tickets
				// statistics data.
				//
			}
			else
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Will NOT run the scheduler");

				//
				// $this->disable_scheduler == "Y"
				//
				// Here we cm_start_date_num, cm_end_date_num and cm_duration_computed will contain the
				// actual Remedy start, end, and duration values from the referenced CM ticket.
				//
				$this->system_work_start_date_num  = $this->cm_start_date_num;
				$this->system_work_start_date_char = $this->cm_start_date_char;
				$this->system_work_end_date_num    = $this->cm_end_date_num;
				$this->system_work_end_date_char   = $this->cm_end_date_char;
				$this->system_work_duration        = $this->cm_duration_computed;
			}

            /**
             * Step 5. Create the new record for the server in cct7_systems and link it back to cct7_tickets ($ticket_no).
             */
            $system_id = $this->addSystem($ticket_no);

            if ($system_id == 0)
            {
                return false;  // $this->error will contain the error message
            }

            $this->total_contacts_responded++;  // Part of the total number of servers that were successfully scheduled.

            /**
             * Step 6. Go get all the contact information and build cct7_contacts records for each server.
             */

            // saveContacts($system_id,
			//              $lastid,
			//              $reboot,
			//              $approvals_required,
			//              $exclude_virtual_contacts,
			//              $system_respond_by_date_num=0)

            if ($contacts->saveContacts($system_id,
										$lastid,
										$this->reboot_required,
										$this->approvals_required,
										$this->exclude_virtual_contacts,
										$this->system_respond_by_date_num) == false)
            {
                return false;  // $this->error will contain the error message
            }

            /**
             * Step 7. Set all the status fields in cc7_contacts, cct7_systems and cct7_tickets based upon approvals_required.
             */
            if ($this->setSystemStatus($system_id) == false)
            {
                return false;  // $this->error will contain the error message
            }
        }

        /**
         * Step 8. Update all the status values for this ticket.
         */
        if ($this->updateAllStatuses($this->ora, $ticket_no) == false)
        {
            return false;
        }

        $this->timeEnd();

        $total_seconds = $this->runTime();

        $minutes = floor($total_seconds / 60);
        $seconds = $total_seconds - ($minutes * 60);

        $this->generator_runtime = sprintf("%d minutes, %d seconds", $minutes, $seconds);

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Generator runtime: %s", $this->generator_runtime);

        return true;
    }
    
    // 
    // From gen_request.php
    //

    /**
     * @fn     checkForDuplicates($hostname)
     *
    *  @brief  Return true if we already have this hostname in $this->top_host
     *
    *  @param  string $hostname is the server name we are checking for duplications
     *
    *  @return bool true or false where false means there is no duplicate
    */
    private function checkForDuplicates($hostname)
    {
        if (!array_key_exists($hostname, $this->duplicates))
        {
            $this->debug4(__FILE__, __FUNCTION__, __LINE__, "Not a duplicate: %s, adding to duplicate array", $hostname);
            $this->duplicates[$hostname] = true;
            return false;
        }

        return true;
    }

    /**
     * @fn     getSystemLists()
     *
	 * @brief  Retrieve SYSTEMS from user defined system lists
     *
	 * @return int -1 when SQL Error, 0 no servers, or a number > 0 indicating the servers generated.
	 */
    private function getSystemLists()
    {
        if (!is_array($this->system_lists) && count($this->system_lists) == 0)
        {
            $this->debug4(__FILE__, __FUNCTION__, __LINE__, "No system_lists");
            return 0;
        }

        $x = 0;

        foreach ($this->system_lists as $list_name_id)
        {
            $list_name_id = trim($list_name_id);

            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "list_name_id = %s", $list_name_id);

            if (strlen($list_name_id) == 0)
                continue;

            $query = $this->sql_select_from_computers();
            $query .= ", cct7_list_systems l where c.computer_hostname = l.computer_hostname and l.list_name_id = " .
                $list_name_id . " order by c.computer_hostname";

            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

            if ($this->ora->sql2($query) == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
                return -1;
            }

            while ($this->ora->fetch())
            {
                if ($this->checkForDuplicates($this->ora->computer_hostname))
                    continue;

                $x++;  // Count the number of servers we add to the link list

                $this->servers[$this->ora->computer_hostname] = $this->ora->computer_lastid;
            }
        }

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Number servers added from system list: %d", $x);

        return $x;
    }

    /**
     * @fn    addHostname($hostname)
     *
     * @brief Add a server to $this->systems list.
     *
     * @param $hostname
     *
     * @return bool
     */
    public function addHostname($hostname)
    {
        //
        // Don't add it if it is already in the list. Just return true.
        //
        if ($this->checkForDuplicates($hostname))
            return true;

        $query  = $this->sql_select_from_computers();
        $query .= " where lower(c.computer_hostname) = lower('" . $hostname . "')";

        if ($this->ora->sql2($query) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
            return false;
        }

        if ($this->ora->fetch())
        {
            $this->servers[$this->ora->computer_hostname] = $this->ora->computer_lastid;
            return true;
        }

        array_push($this->invalid_servers, $hostname);

        return false;
    }

    /**
     * @fn     getTheseOnly()
     *
	 * @brief  Retrieve SYSTEMS where user has typed in a list of servers or application
     *
	 * @return int -1 when SQL Error, 0 no servers, or a number > 0 indicating the servers generated.
	 */
    public function getTheseOnly()
    {
        //	$obj->target_these_only          getTheseOnly()
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Running getTheseOnly()");

        if (!isset($this->target_these_only) && strlen($this->target_these_only) == 0)
        {
            $this->debug4(__FILE__, __FUNCTION__, __LINE__, "No target_these_only");
            return 0;
        }

        $x = 0;

        // Fix up $this->target_these_only
        $str = str_replace(",", " ", $this->target_these_only);                             // Convert any commas to spaces
        $this->target_these_only = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $str);  // Remove multiple spaces, tabs and newlines if present

        $systems = explode(" ", $this->target_these_only);  // Create an array of $systems

        foreach ($systems as $system)
        {
            if (strlen($system) == 0)
                continue;

            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "system: %s", $system);

            if ($this->checkForDuplicates($system))
                continue;

            //
            // Is it in Asset Manager? (cct7_computers)
            //
            $query  = $this->sql_select_from_computers();
            $query .= " where lower(c.computer_hostname) = lower('" . $system . "')";

            if ($this->ora->sql2($query) == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
                return -1;
            }

            if ($this->ora->fetch())
            {
                $x++;  // Count the number of servers we add to the link list
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Found %s in cct7_computers", $system);
                $this->servers[$this->ora->computer_hostname] = $this->ora->computer_lastid;
            }
            else
            {
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Server NOT found in cct7_computers: %s", $system);
                array_push($this->invalid_servers, $system);
                continue;
            }

            //
            // Is this an application? (cct7_applications)
            //
            $query  = $this->sql_select_from_computers();
            $query .= ", cct7_applications a ";
            $query .= "where ";
            $query .="  c.computer_lastid = a.computer_lastid and ";
            $query .="  a.application_acronym = upper('" . $system . "')";

            if ($this->ora->sql($query) == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
                return -1;
            }

            while ($this->ora->fetch())
            {
                if ($this->checkForDuplicates($this->ora->computer_hostname))
                    continue;

                $x++;  // Count the number of servers we add to the link list

                $this->servers[$this->ora->computer_hostname] = $this->ora->computer_lastid;
            }

            //
            // Is this a database? (cct7_databases)
            //
            $query  = $this->sql_select_from_computers();
            $query .= ", cct7_databases d ";
            $query .= "where ";
            $query .= "  c.computer_lastid = d.computer_lastid and ";
            $query .= "  d.database_name = upper('" . $system . "')";

            if ($this->ora->sql($query) == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
                return -1;
            }

            while ($this->ora->fetch())
            {
                if ($this->checkForDuplicates($this->ora->computer_hostname))
                    continue;

                $x++;  // Count the number of servers we add to the link list

                $this->servers[$this->ora->computer_hostname] = $this->ora->computer_lastid;
            }
        }

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Returning x = %d", $x);

        return $x;
    }

    /**
     * @fn     getAssetCenter()
     *
	 * @brief  Retrieve SYSTEMS based upon asset center search criteria provided by the user
     *
	 * @return int -1 when SQL Error, 0 no servers, or a number > 0 indicating the servers generated.
	 */
    public function getAssetCenter()
    {
        //	$obj->computer_managing_group    getAssetCenter()  sql_where_computer_managing_group()
        //	$obj->computer_os_lite           getAssetCenter()  sql_where_computer_os_lite()
        //	$obj->computer_status            getAssetCenter()  sql_where_computer_status()
        //	$obj->computer_contract          getAssetCenter()  sql_where_computer_contract()
        //	$obj->state_and_city             getAssetCenter()  sql_where_state_and_city()
        //	$obj->miscellaneous              getAssetCenter()  sql_where_miscellaneous()

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Running getAssetCenter()");

        $wheres[0] = $this->sql_where_computer_managing_group();
        $wheres[1] = $this->sql_where_computer_os_lite();
        $wheres[2] = $this->sql_where_computer_status();
        $wheres[3] = $this->sql_where_computer_contract();
        $wheres[4] = $this->sql_where_state_and_city();
        $wheres[5] = $this->sql_where_miscellaneous();
        $wheres[6] = $this->sql_where_ip_starts_with();

        $where_clause = '';

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "where_clause = %s", $where_clause);

        foreach ($wheres as $w)
        {
            if ($w == null || strlen(trim($w)) == 0)
                continue;

            if (strlen($where_clause) == 0)
            {
                $where_clause = $w;
            }
            else
            {
                $where_clause .= " and " . $w;
            }
        }

        if (strlen($where_clause) == 0)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "where_clause is empty. returning 0");
            return 0;
        }

        //
        // For Asset Center computer records, limit the scope to records that are servers that are supported.
        //
        $query = $this->sql_select_from_computers() . " where " . $where_clause . " and computer_type = 'SERVER' and computer_nature = 'SUPPORT' ";

        $this->debug_sql4(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

        if ($this->ora->sql($query) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
            return -1;
        }

        $x = 0;

        while ($this->ora->fetch())
        {
            if ($this->checkForDuplicates($this->ora->computer_hostname))
                continue;

            $x++;  // Count the number of servers we add to the link list

            $this->servers[$this->ora->computer_hostname] = $this->ora->computer_lastid;
        }

        return $x;
    }

    /**
     * @fn     sql_select_from_computers()
     *
	 * @brief  Used to build the select ... from computers SQL query
     *
	 * @return string sql string
	 */
    private function sql_select_from_computers()
    {
        $str = "select distinct " .
            "c.computer_lastid, " .
            "c.computer_hostname " .
            "from cct7_computers c";

        return $str;
    }

    /**
     * @fn     sql_where_computer_managing_group()
     *
	 * @brief  Generate a SQL where clause for user computer managing group targeting data
     *
	 * @return string empty string or the where clause string
	 */
    private function sql_where_computer_managing_group()
    {
        // cct7_computers.computer_managing_group - CMP-UNIX

        if (!is_array($this->computer_managing_group) || count($this->computer_managing_group) == 0)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->computer_managing_group is empty");
            return '';
        }

        $str = '';

        foreach ($this->computer_managing_group as $item)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->computer_managing_group (item) = %s", $item);

            if (strlen($str) == 0)
            {
                $str = "( c.computer_managing_group = '" . $this->FixString($item) . "' ";
            }
            else
            {
                $str .= "or c.computer_managing_group = '" . $this->FixString($item) . "' ";
            }
        }

        if (strlen($str) > 0)
            $str .= ")";

        return $str;
    }

    /**
     * @fn     sql_where_computer_os_lite()
     *
	 * @brief  Generate a SQL where clause for user computer OS targeting data
     *
	 * @return string empty string or the where clause string
	 */
    private function sql_where_computer_os_lite()
    {
        // cct7_computers.computer_os_lite - HPUX

        if (!is_array($this->computer_os_lite) || count($this->computer_os_lite) == 0)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->computer_os_lite is empty");
            return '';
        }

        $str = '';

        foreach ($this->computer_os_lite as $item)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->computer_os_lite (item) = %s", $item);

            if (strlen($str) == 0)
            {
                $str = "( c.computer_os_lite = '" . $this->FixString($item) . "' ";
            }
            else
            {
                $str .= "or c.computer_os_lite = '" . $this->FixString($item) . "' ";
            }
        }

        if (strlen($str) > 0)
            $str .= ")";

        return $str;
    }

    /**
     * @fn     sql_where_computer_status()
     *
	 * @brief  Generate a SQL where clause for user computer status targeting data
     *
	 * @return string empty string or the where clause string
	 */
    private function sql_where_computer_status()
    {
        // cct7_computers.computer_status - PRODUCTION

        if (!is_array($this->computer_status) || count($this->computer_status) == 0)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->computer_status is empty");
            return '';
        }

        $str = '';

        foreach ($this->computer_status as $item)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->computer_status (item) = %s", $item);

            if (strlen($str) == 0)
            {
                $str = "( c.computer_status = '" . $this->FixString($item) . "' ";
            }
            else
            {
                $str .= "or c.computer_status = '" . $this->FixString($item) . "' ";
            }
        }

        if (strlen($str) > 0)
            $str .= ")";

        return $str;
    }

    /**
     * @fn     sql_where_computer_contract()
     *
	 * @brief  Generate a SQL where clause for user computer contract targeting data
     *
	 * @return string empty string or the where clause string
	 */
    private function sql_where_computer_contract()
    {
        // cct7_computers.computer_contract - IGS FULL CONTRACT UNIX-PROD

        if (!is_array($this->computer_contract) || count($this->computer_contract) == 0)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->computer_contract is empty");
            return '';
        }

        $str = '';

        foreach ($this->computer_contract as $item)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->computer_contract (item) = %s", $item);

            if (strlen($str) == 0)
            {
                $str = "( c.computer_contract = '" . $this->FixString($item) . "' ";
            }
            else
            {
                $str .= "or c.computer_contract = '" . $this->FixString($item) . "' ";
            }
        }

        if (strlen($str) > 0)
            $str .= ")";

        return $str;
    }

    /**
     * @fn     sql_where_state_and_city()
     *
	 * @brief  Generate a SQL where clause for user state and city targeting data
     *
	 * @return string empty string or the where clause string
	 */
    private function sql_where_state_and_city()
    {
        // cct7_computers.computer_state and computers.computer_city (state:city) - CO:DENVER

        if (!is_array($this->state_and_city) || count($this->state_and_city) == 0)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->state_and_city is empty");
            return '';
        }

        $str = '';

        foreach ($this->state_and_city as $item)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->state_and_city (item) = %s", $item);

            $parts = explode(':', $item);

            if (count($parts) < 2)
                continue;

            if (strlen($str) == 0)
            {
                $str = "( (c.computer_state = '" . $parts[0] . "' and c.computer_city = '" . $parts[1] . "') ";
            }
            else
            {
                $str .= "or (c.computer_state = '" . $parts[0] . "' and c.computer_city = '" . $parts[1] . "') ";
            }
        }

        if (strlen($str) > 0)
            $str .= ")";

        return $str;
    }

    /**
     * @fn sql_where_miscellaneous()
     *
	 * @brief Generate a SQL where clause for user miscellaneous targeting data
     *
	 * @return string empty string or the where clause string
	 */
    private function sql_where_miscellaneous()
    {
        // BCR:GOLD
        // BCR:NOT-GOLD
        // BCR:BLUE
        // BCR:SILVER
        // BCR:BRONZE
        // BCR:NO-COLOR
        // SPECIAL:HANDLING
        // PLATFORM:MIDRANGE - cct7_computers.computer_platform

        if (!isset($this->miscellaneous) || !is_array($this->miscellaneous) || count($this->miscellaneous) == 0)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->miscellaneous is empty");
            return '';
        }

        $str = '';

        foreach ($this->miscellaneous as $item)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->miscellaneous (item) = %s", $item);

            $parts = explode(':', $item);

            if (count($parts) < 2)
                continue;

            // printf("part1 = %s, part2 = %s\n", $parts[0], $parts[1]);

            if (strlen($str) == 0)
            {
                switch ( $parts[0] )
                {
                    case "BCR":
                        switch ( $parts[1] )
                        {
                            case 'GOLD':
                                $str = "( c.computer_gold_server = 'Y' ";
                                break;
                            case "NOT-GOLD":
                                $str = "( c.computer_gold_server = 'N' ";
                                break;
                            case "BLUE":
                                $str = "( c.computer_slevel_colors like '%BLUE%' ";
                                break;
                            case "SILVER":
                                $str = "( c.computer_slevel_colors like '%SILVER%' ";
                                break;
                            case "BRONZE":
                                $str = "( c.computer_slevel_colors like '%BRONZE%' ";
                                break;
                            case "NO-COLOR":
                                $str = "( c.computer_slevel_colors is null ";
                                break;
                            default:
                                break;
                        }
                        break;
                    case "SPECIAL":
                        $str = "( c.computer_special_handling = 'Y' ";
                        break;
                    case "PLATFORM":
                        $str = "( c.computer_platform = '" . $parts[1] . "' ";
                        break;
                    default:
                        break;
                }
            }
            else
            {
                switch ( $parts[0] )
                {
                    case "BCR":
                        switch ( $parts[1] )
                        {
                            case "GOLD":
                                $str .= "or c.computer_gold_server = 'Y' ";
                                break;
                            case "NOT-GOLD":
                                $str .= "or c.computer_gold_server = 'N' ";
                                break;
                            case "BLUE":
                                $str .= "or c.computer_slevel_colors like '%BLUE%' ";
                                break;
                            case "SILVER":
                                $str .= "or c.computer_slevel_colors like '%SILVER%' ";
                                break;
                            case "BRONZE":
                                $str .= "or c.computer_slevel_colors like '%BRONZE%' ";
                                break;
                            case "NO-COLOR":
                                $str .= "or c.computer_slevel_colors is null ";
                                break;
                            default:
                                break;
                        }
                        break;
                    case "SPECIAL":
                        $str .= "or c.computer_special_handling = 'Y' ";
                        break;
                    case "PLATFORM":
                        $str .= "or c.computer_platform = '" . $parts[1] . "' ";
                        break;
                    default:
                        break;
                }
            }
        }

        if (strlen($str) > 0)
            $str .= ")";

        return $str;
    }

    /**
     * @fn     sql_where_ip_starts_with()
     *
	 * @brief  Generate a SQL where clause for a ip address that starts with ...
     *
	 * @return string empty string or the where clause if $this->ip_starts_with > 0
	 */
    private function sql_where_ip_starts_with()
    {
        // Where IP Starts with: xxx.xxx

        if (!isset($this->ip_starts_with) || strlen($this->ip_starts_with) == 0)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->ip_starts_with is empty");
            return '';
        }

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->ip_starts_with = %s", $this->ip_starts_with);

        $str = "c.computer_ip_address like '" . $this->ip_starts_with . "%%'";

        return $str;
    }

    /**
     * Available system_work_status values:
     *
     * APPROVED
     * CANCELED
     * FAILED
     * REJECTED
     * STARTING
     * SUCCESS
     * WAITING
     */

	/**
	 * @fn    approve_with_paging($system_id)
	 *
	 * @brief User wants to approve all their net group contacts from the server level with paging.
	 *
	 * @param $system_id
	 *
	 * @return bool
	 */
	public function approve_with_paging($system_id)
	{
		$con = new cct7_contacts();

		$groups = isset($_SESSION['user_groups']) ? $_SESSION['user_groups'] : "";
		$group_list = explode(',', $groups);

		foreach ($group_list as $netpin_no)
		{
			if ($con->approve($system_id, $netpin_no, "Y") == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $con->error);
				$this->error = $con->error;
				// Okay if this errors. There may be pins this user has that are not used for this server.
			}
		}

		return true;
	}

	/**
	 * @fn    approve_no_paging($system_id)
	 *
	 * @brief User wants to approve all their net group contacts from the server level with no paging.
	 *
	 * @param int $system_id
	 *
	 * @return bool
	 */
	public function approve_no_paging($system_id)
	{
		$con = new cct7_contacts();

		$groups = isset($_SESSION['user_groups']) ? $_SESSION['user_groups'] : "";
		$group_list = explode(',', $groups);

		foreach ($group_list as $netpin_no)
		{
			if ($con->approve($system_id, $netpin_no, "N") == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $con->error);
				$this->error = $con->error;
				// Okay if this errors. There may be pins this user has that are not used for this server.
			}
		}

		return true;
	}

	/**
	 * @fn    exempt($system_id)
	 *
	 * @brief User wants to exempt themselves from approving work for this server.
	 *
	 * @param int $system_id
	 *
	 * @return bool
	 */
	public function exempt($system_id)
	{
		$con = new cct7_contacts();

		$groups = isset($_SESSION['user_groups']) ? $_SESSION['user_groups'] : "";
		$group_list = explode(',', $groups);

		foreach ($group_list as $netpin_no)
		{
			if ($con->exempt($system_id, $netpin_no) == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $con->error);
				$this->error = $con->error;
				// Okay if this errors. There may be pins this user has that are not used for this server.
			}
		}

		return true;
	}

    /**
     * @fn    reject($system_id)
     *
     * @brief Reject work for this server, send out email to contacts, and log entry in cct7_log_systems.
     *
     * @param int $system_id
     *
     * @return bool
     */
    public function reject($system_id)
    {
    	$con = new cct7_contacts();

    	$groups = isset($_SESSION['user_groups']) ? $_SESSION['user_groups'] : "";
    	$group_list = explode(',', $groups);

		foreach ($group_list as $netpin_no)
		{
			if ($con->reject($system_id, $netpin_no) == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $con->error);
				$this->error = $con->error;
				// Okay if this errors. There may be pins this user has that are not used for this server.
			}
		}

		return true;
    }

    /**
     * @fn    cancel($system_id)
     *
     * @brief Cancel work for this server, send out email to contacts, and log entry in cct7_log_systems.
     *
     * @param $system_id
     *
     * @return bool
     */
    public function cancel($system_id)
    {
		$con = new cct7_contacts();

		$groups = isset($_SESSION['user_groups']) ? $_SESSION['user_groups'] : "";
		$group_list = explode(',', $groups);

		foreach ($group_list as $netpin_no)
		{
			if ($con->cancel($system_id, $netpin_no) == false)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $con->error);
				$this->error = $con->error;
				// Okay if this errors. There may be pins this user has that are not used for this server.
			}
		}

		return true;
    }

    /**
     * @fn    starting($system_id)
     *
     * @brief Work is starting for this server, send out email to contacts, and log entry in cct7_log_systems.
     *
     * @param $system_id
     *
     * @return bool
     */
    public function starting($system_id)
    {
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "In the zone");
        return $this->set_system_work_status($system_id, "STARTING");
    }

    /**
     * @fn    starting($system_id)
     *
     * @brief Cancel work for this server, send out email to contacts, and log entry in cct7_log_systems.
     *
     * @param $system_id
     *
     * @return bool
     */
    public function success($system_id)
    {
        return $this->set_system_work_status($system_id, "SUCCESS");
    }

    /**
     * @fn    failed($system_id)
     *
     * @brief Work failed for this server, send out email to contacts, and log entry in cct7_log_systems.
     *
     * @param $system_id
     *
     * @return bool
     */
    public function failed($system_id)
    {
        return $this->set_system_work_status($system_id, "FAILED");
    }

    /**
     * @fn    set_system_work_status($system_id, $system_work_status)
     *
     * @brief Set work for this server, send out email to contacts, and log entry in cct7_log_systems.
     *
     * @param $system_id
     * @param $system_work_status
     *
     * @return bool
     */
    private function set_system_work_status($system_id, $system_work_status)
    {
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "In the zone");

        if ($this->getSystem($system_id) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

		$ticket_status = "";

		if ($this->getTicketStatus($this->ticket_no, $ticket_status) == false)
		{
			$this->error = sprintf("Unable to determine status for ticket: %s", $this->ticket_no);
			return false;
		}

		if ($ticket_status == "ACTIVE" || $ticket_status == "DRAFT")
		{
			// APPROVED
			// CANCELED
			// FAILED
			// REJECTED
			// STARTING
			// SUCCESS
			// WAITING

			if ($this->system_work_status != "APPROVED" ||
				$this->system_work_status != "WAITING")
			{
				$this->error =
					sprintf("%s status is %s. Cannot change status to: %s",
							$this->system_hostname, $this->system_work_status, $system_work_status);

				return false;
			}
		}
		else
		{
			$this->error =
				sprintf("Cannot change status for %s when ticket status is: %s.",
						$this->system_hostname, $ticket_status);

			return false;
		}

        $rc = $this->ora
            ->update("cct7_systems")
            ->set("int",    "system_update_date",     $this->now_to_gmt_utime())
            ->set("char",   "system_update_cuid",     $this->user_cuid)
            ->set("char",   "system_update_name",     $this->user_name)
            ->set("char",   "system_work_status",     $system_work_status)
            ->where("int",  "system_id", "=",         $system_id)
            ->execute();

        $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);

        if ($rc == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                                   $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
        }

        //
        // This function is defined in library.php and it calls a stored oracle procedure called updateStatus.sql
        //
        if ($this->updateAllStatuses($this->ora, $this->ticket_no) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        //
        // Create a log entry
        //
        $message = sprintf("%s - %s - Status has changed to %s",
                           $this->system_hostname, $this->ticket_no, $system_work_status);

        if ($this->putLogSystem($this->ticket_no, $system_id, $this->system_hostname, $system_work_status, $message) == false)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);
            return false;
        }

        return true;
    }

    /**
     * @fn    putLogSystem($ticket_no, $system_id, $hostname, $event_type, $event_message)
     *
     * @brief Put events into table cct7_log_systems.
     *
     * @param string $ticket_no       - cct7_tickets.ticket_no
     * @param int    $system_id       - cct7_systems.system_id FOREIGN KEY with CASCADE DELETE
     * @param string $hostname        - cct7_systems.system_hostname
     * @param string $event_type      - [SUBMIT,CANCEL,APPROVE,REJECT,EXEMPT,EMAIL,PAGE,INFO,ERROR]
     * @param string $event_message   - Event message
     *
     * @return bool
     */
    public function putLogSystem($ticket_no, $system_id, $hostname, $event_type, $event_message)
    {
        if ($this->ora2 == null)
            $this->ora2 = new oracle();

        // cct7_log_systems
        //
        // ticket_id|NUMBER|0|NOT NULL|CCT ticket_id no.
        // system_id|NUMBER|0|NOT NULL|FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE
        // hostname|VARCHAR2|255|NOT NULL|Hostname for this log entry
        // event_date|NUMBER|0||Event Date (GMT)
        // event_cuid|VARCHAR2|20|NOT NULL|Event Owner CUID
        // event_name|VARCHAR2|200|NOT NULL|Event Owner Name
        // event_type|VARCHAR2|20|NOT NULL|Event type
        // event_message|VARCHAR2|4000|NOT NULL|Event message

        $rc = $this->ora2
            ->insert("cct7_log_systems")
            ->column("ticket_no")        // ticket_no    |VARCHAR2|20  |NOT NULL|CCT Ticket
            ->column("system_id")        // system_id    |NUMBER  |0   |        |FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE
            ->column("hostname")         // hostname     |VARCHAR2|255 |NOT NULL|Hostname for this log entry
            ->column("event_date")       // event_date   |NUMBER  |0   |        |Event Date (GMT)
            ->column("event_cuid")       // event_cuid   |VARCHAR2|20  |        |Event Owner CUID
            ->column("event_name")       // event_name   |VARCHAR2|200 |        |Event Owner Name
            ->column("event_type")       // event_type   |VARCHAR2|20  |        |Event type
            ->column("event_message")    // event_message|VARCHAR2|4000|        |Event message
            ->value("char",  $ticket_no)
            ->value("int",   $system_id)
            ->value("char",  $hostname)
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
        // Change the system_update_date in cct7_systems for $system_id so send_notifications.php can send out email
        // for this log event.
        //
		$rc = $this->ora2
			->update("cct7_systems")
			->set("int",    "system_update_date",     $this->now_to_gmt_utime())
			->set("char",   "system_update_cuid",     $this->user_cuid)
			->set("char",   "system_update_name",     $this->user_name)
			->where("int",  "system_id", "=",         $system_id)
			->execute();

		if ($rc == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora2->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora2->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora2->sql_statement, $this->ora2->dbErrMsg);
			return false;
		}

        $this->ora2->commit();

        return true;
    }

    /**
     * @fn    getLogSystem($system_id)
     *
     * @brief Create a link list of events matching the parameter list passed to this method.
     *
     * @brief Index built for cct7_log_systems are as follows:
     * create index idx_cct7_log_systems1 on cct7_log_systems (ticket_no);            -- Pull all events by ticket_no
     * create index idx_cct7_log_systems2 on cct7_log_systems (system_id);            -- Pull all events by system_id
     * create index idx_cct7_log_systems3 on cct7_log_systems (hostname);             -- Pull all events by hostname (history)
     * create index idx_cct7_log_systems4 on cct7_log_systems (event_cuid);           -- Pull all events by cuid
     * create index idx_cct7_log_systems5 on cct7_log_systems (event_type);           -- Pull all events by event type [EMAIL, PAGE, etc.]
     *
     * @param string $system_id  - SQL where clause for the query.
     *
     * @return null
     */
    public function getLogSystem($system_id)
    {
        $top = null;
        $p = null;

        // ticket_no|VARCHAR2|20|NOT NULL|CCT ticket number.
        // system_id|NUMBER|0||FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE
        // hostname|VARCHAR2|255||Hostname for this log entry
        // netpin_no|VARCHAR2|20||CSC/Net-Tool Pin No.
        // event_date|NUMBER|0||Event Date (GMT)
        // event_cuid|VARCHAR2|20||Event Owner CUID
        // event_name|VARCHAR2|200||Event Owner Name
        // event_type|VARCHAR2|20||Event type
        // event_message|VARCHAR2|4000||Event message

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
     * @fn     setSystemStatus($system_id)
     *
     * @brief  This method scans all the contact responses for this $system_id and sets this servers status to
     *         the appropriate status value.
     *
     * @brief  Used once in this class method: public function newWorkSchedule($ticket_no)
     *
     * @param  int $system_id is the server to analyze contact responses.
     *
     * @return bool
     */
    public function setSystemStatus($system_id)
    {
        $rc = $this->ora
            ->select()
            ->column('contact_response_status')  // APPROVED, REJECTED, EXEMPT, WAITING
            ->from('cct7_contacts')
            ->where("int", "system_id", '=', $system_id)
            ->where_and("char", 'contact_approver_fyi', '=', 'APPROVER')
            ->execute();

        if ($rc == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
            return false;
        }

        $new_server_status = "WAITING";
        $total_approved    = 0;
        $total_rejected    = 0;
        $total_exempt      = 0;
        $total_waiting     = 0;

        while ($this->ora->fetch())
        {
            switch ( $this->ora->contact_response_status )
            {
                case 'APPROVED':
                    $total_approved++;
                    break;
                case 'REJECTED':
                    $total_rejected++;
                    $new_server_status = "REJECTED";
                    break;
                case 'EXEMPT':
                    $total_exempt++;
                    $new_server_status = "REJECTED";
                    break;
                case 'WAITING':
                    $total_waiting++;
                    $new_server_status = "WAITING";
                    break;
                default:
                    break;
            }
        }

        $this->debug1(__FILE__, __FUNCTION__, __LINE__,
            "system_id: %d, approved: %d, rejected: %d, exempt: %d, waiting: %d, Server Status: %s",
            $system_id, $total_approved, $total_rejected, $total_exempt, $total_waiting, $new_server_status);

        $rc = $this->ora
            ->update("cct7_systems")
            ->set("int",    "system_update_date",     $this->now_to_gmt_utime())
            ->set("char",   "system_update_cuid",     $this->user_cuid)
            ->set("char",   "system_update_name",     $this->user_name)
            ->set("char",   "system_work_status",     "WAITING")
            ->where("int",  "system_id", "=",         $system_id)
            ->execute();

        if ($rc == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
            return false;
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
