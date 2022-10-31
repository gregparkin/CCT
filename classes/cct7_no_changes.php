<?php
/**
 * @package    CCT
 * @file       cct7_no_changes.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       $Date:  $ GMT
 * @version    $Revision:  $
 *
 * $Log:  $
 *
 * $Source:  $
 *
 * @brief The module was designed to perform most of the functions on the Oracle Table: cct7_no_changes
 *
 */

/**
 * @brief Public Methods
 *        initialize()
 *        check()
 *        add()
 *        truncated()
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
 * @class cct7_no_changes
 *
 * @brief This class contains all the main processing routines for working with cct7_no_changes
 */
class cct7_no_changes extends library
{
	var $data;
	var $ora;                     // Database connection object
	var $error;                   // Error message when functions return false

    public $change_type;
    public $start_date;
    public $end_date;
    public $reason;

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

		$this->ora = new oracle();

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
				$this->user_cuid          = 'cctadm';
				$this->user_first_name    = 'Application';
				$this->user_last_name     = 'CCT';
				$this->user_name          = 'CCT Application';
				$this->user_email         = 'gregparkin58@gmail.com';
                $this->user_job_title     = 'CMP-TOOLS';
				$this->user_company       = 'CMP';
				$this->user_access_level  = 'admin';
                $this->user_timezone_name = 'America/Denver';
				
				$this->manager_cuid       = 'gparkin';
				$this->manager_first_name = 'Robert';
				$this->manager_last_name  = 'Pelan';
				$this->manager_name       = 'Bob Pelan';
				$this->manager_email      = 'gregparkin58@gmail.com';
                $this->manager_job_title  = 'Director';
				$this->manager_company    = 'CMP';
				
				$this->is_debug_on        = 'Y';
			}

			$this->debug_start('cct7_no_changes.html');
		}
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
	 * @brief Initialize all the field variables for cct7_no_changes
	 */
	public function initialize()
	{
		$this->no_change_type              = ''; // No Change or Minimal Change
        $this->start_date                  = 0;  // Start date and time
        $this->end_date                    = 0;  // End date and time
        $this->reason                      = ''; // Reason for the no change window
	}

	/**
	 * @fn     check($change_date)
	 *
	 * @brief  Check to see if $change_date is in a no change window.
	 *
	 * @param  int $change_date is the date (GMT) we want to check
	 *
	 * @return bool - true or false, where true means $change_date is in a no change window
	 */	
	public function check($change_date)
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "$change_date = %d", $change_date);

		$this->initialize();  // Initialize storage variables.

		$rc = $this->ora
            ->select()
            ->column('no_change_type')
			->column('start_date')
            ->column('end_date')
            ->column('reason')
            ->from('cct7_no_changes')
            ->where('int', 'start_date', '>=', $change_date)
			->where_and('int', 'end_date', '<=', $change_date)
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
            return false;
		}

		$mmddyyyy_hhmm_tz = 'm/d/Y H:i T';

        $this->no_change_type  = $this->ora->no_change_type;
        $this->start_date_char = $this->gmt_to_format($this->ora->start_date, $mmddyyyy_hhmm_tz, $this->user_timezone_name);
        $this->end_date_char   = $this->gmt_to_format($this->ora->end_date, $mmddyyyy_hhmm_tz, $this->user_timezone_name);
        $this->reason          = $this->ora->reason;

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "no_change_type  = %s",  $this->ticket_no);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_date_char = %d",  $this->start_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "end_date_char   = %s",  $this->end_date_char);
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "reason          = %s",  $this->reason);

		return true;
	}

	/**
	 * @fn    add()
	 *
	 * @brief add a new no change record to table cct7_no_changes
	 *
	 * @param string $no_change_type will be either 'No Change' or 'Minimal Change'
	 * @param string $start_date is a datetime string when no change window starts.
	 * @param string $end_date is a datetime string when no change window ends.
	 * @param string $reason is the reason for the no change window.
	 *
	 * @return bool where true means the record was successfully added
	 */
	public function add($no_change_type, $start_date, $end_date, $reason)
	{
		$start_date_num = $this->to_gmt($start_date, 'America/Chicago');
		$end_date_num   = $this->to_gmt($end_date,   'America/Chicago');

		$rc = $this->ora
			->insert("cct7_no_changes")
			->column("no_change_type")
            ->column("start_date")
            ->column("end_date")
            ->column("reason")
			->value("char",  $no_change_type)
            ->value("int",   $start_date_num)
            ->value("int",   $end_date_num)
            ->value("char",  $reason)
			->execute();

		if ($rc == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
                $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
		}

		$this->ora->commit();
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Record added.");

		return true;
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
	public function truncate()
	{
		if ($this->ora->sql2("delete from cct7_no_changes") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$this->ora->commit();
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cct7_no_changes has been truncated");

		return true;
	}
}
?>
