<?php
/**
 * NO LONGER NEED! - Functions moved to library.php
 */

//
// Public Methods

//
//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');  //!< @see includes/autoloader.php
}

/** @class cct7_systems
 *  @brief Class for CCT Event Logging.
 */
class cct7_event_log extends library
{
	var $ora = null;
	var $data = array();        // Associated array for properties of $this->xxx

	/**
	 * @fn __construct()
	 *
	 * @brief Class constructor - Create oracle object and setup some dynamic class variables
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

		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->wreq_osmaint = %s", $this->wreq_osmaint);

		$this->ora = new oracle();

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

			$this->debug_start('cct7_logger.html');
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

	/**
	 * @fn     log($system_id, $netpin_no, $event_type, $event_message)
	 *
	 * @brief  CCT Event Logging function.
	 *
	 * @example $this->log(__FILE__, __FUNCTION__, __LINE__, 0, 234, 0, 17340, 'EMAIL', 'Message...');
	 *
	 * @param int    $ticket_id       - cct7_tickets.ticket_id
	 * @param int    $system_id       - cct7_systems.system_id FOREIGN KEY with CASCADE DELETE
	 * @param string $hostname        - cct7_systems.system_hostname
	 * @param int    $netpin_no       - cct7_contacts.netpin_no
	 * @param string $event_type      - [SUBMIT,CANCEL,APPROVE,REJECT,EXEMPT,EMAIL,PAGE,INFO,ERROR]
	 * @param string $event_message   - Event message
	 *
	 * @return bool
	 */
	public function log($ora, $ticket_id, $system_id, $hostname, $netpin_no, $event_type, $event_message)
	{

		// ticket_id|NUMBER|0|NOT NULL|CCT ticket_id no.
		// system_id|NUMBER|0|NOT NULL|FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE
		// hostname|VARCHAR2|255|NOT NULL|Hostname for this log entry
		// netpin_no|VARCHAR2|20||CSC/Net-Tool Pin No.
		// event_date|NUMBER|0||Event Date (GMT)
		// event_cuid|VARCHAR2|20|NOT NULL|Event Owner CUID
		// event_name|VARCHAR2|200|NOT NULL|Event Owner Name
		// event_type|VARCHAR2|20|NOT NULL|Event type
		// event_message|VARCHAR2|4000|NOT NULL|Event message


		$rc = $this->ora
			->insert("cct7_event_log")
			->column("ticket_id")        // ticket_id    |NUMBER  |0   |NOT NULL|CCT ticket_id no.
			->column("system_id")        // system_id    |NUMBER  |0   |        |FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE
			->column("hostname")         // hostname     |VARCHAR2|255 |NOT NULL|Hostname for this log entry
			->column("netpin_no")        // netpin_no    |VARCHAR2|20  |        |CSC/Net-Tool Pin No.
			->column("event_date")       // event_date   |NUMBER  |0   |        |Event Date (GMT)
			->column("event_cuid")       // event_cuid   |VARCHAR2|20  |        |Event Owner CUID
			->column("event_name")       // event_name   |VARCHAR2|200 |        |Event Owner Name
			->column("event_type")       // event_type   |VARCHAR2|20  |        |Event type
			->column("event_message")    // event_message|VARCHAR2|4000|        |Event message
			->value("int",   $ticket_id)
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
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$this->ora->commit();
		return true;
	}

	/**
	 * @fn    getEvents($where_clause)
	 *
	 * @brief Create a link list of events matching the parameter list passed to this method.
	 *
	 * @brief Index built for cct7_event_log are as follows:
	 *        create index idx_cct7_event_log1 on cct7_event_log (ticket_id);            -- Pull all events by ticket_id
	 *        create index idx_cct7_event_log2 on cct7_event_log (system_id);            -- Pull all events by system_id
	 *        create index idx_cct7_event_log3 on cct7_event_log (hostname);             -- Pull all events by hostname (multiple ticket events)
	 *        create index idx_cct7_event_log4 on cct7_event_log (netpin_no);            -- Pull all events by netpin_no
	 *        create index idx_cct7_event_log5 on cct7_event_log (system_id, netpin_no); -- Pull all events by system_id and netpin_no
	 *        create index idx_cct7_event_log6 on cct7_event_log (hostname, netpin_no);  -- Pull all events by hostname and netpin_no
	 *        create index idx_cct7_event_log7 on cct7_event_log (event_cuid);           -- Pull all events by cuid
	 *        create index idx_cct7_event_log8 on cct7_event_log (event_type);           -- Pull all events by event type [EMAIL, PAGE, etc.]
	 *
	 * @param string $where_clause  - SQL where clause for the query.
	 *
	 * @return null
	 */
	public function getEvents($where_clause)
	{
		$top = $p = null;

		$query  = "select ";
		$query .= "  system_id, ";
		$query .= "  netpin_no, ";
		$query .= "  event_date, ";
		$query .= "  event_cuid, ";
		$query .= "  event_name, ";
		$query .= "  event_type, ";
		$query .= "  event_message ";
		$query .= "from ";
		$query .= "  cct7_event_log ";
		$query .= "where " . $where_clause . " ";
		$query .= "order by ";
		$query .= "  event_date";

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

			$p->system_id       = $this->ora->system_id;
			$p->event_date_num  = $this->event_date;
			$p->event_date_char = $this->gmt_to_format($this->ora->event_date, 'm/d/Y H:i T', $this->user_timezone_name);
			$p->event_cuid      = $this->ora->event_cuid;
			$p->event_name      = $this->ora->event_name;
			$p->event_type      = $this->ora->event_type;
			$p->event_message   = $this->ora->event_message;
		}

		if ($top == null)
			$this->error = "No events records found for this search.";

		return $top;
	}
}
?>
