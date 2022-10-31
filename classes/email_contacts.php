<?php
/**
 * email_contacts.php
 *
 * @package   PhpStorm
 * @file      email_contacts.php
 * @author    gparkin
 * @date      7/24/17
 * @version   7.0
 *
 * @brief     Three public methods to retrieve contact lists.
 *
 *            byTicket($ticket_no, $send_to_approvers, $send_to_fyi)
 *            bySystem($system_id, $send_to_approvers, $send_to_fyi)
 *            byContact($ticket_no, $system_id, $contact_id, $send_to_approvers, $send_to_fyi)
 *
 *            After one of these three methods have been called you will have hash array loaded
 *            with the contact information you need.
 *
 *  function byTicket($ticket_no,                           $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
 *  function bySystem(             $system_id,              $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
 *  function byContact($ticket_no, $system_id, $contact_id, $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
 *  function getContacts($hostname);
 *
 *  $list = new email_contacts();
 *
 *  $list->byTicket("CCT700049281");                     // tic, ...
 *  $list->bySystem("CCT700049281", 1038861);            // tic, system_id, ...
 *  $list->byContact("CCT700049281", 1038861, 12283205); // tic, system_id, contact_id, ...
 *
 *  foreach ($list->email_list as $cuid => $name_and_email)
 *  {
 *  	printf("%s|%s\n", $cuid, $name_and_email);
 *  }
 *
 *  Sample Output:
 *
 *  aa12845|Scott Clancy|Scott.T.Clancy@centurylink.com|APPROVER
 *  ab27638|Sahana Kasam|kasamsah@us.ibm.com|APPROVER
 *  jxmims|John David Mims|JohnDavid.Mims@centurylink.com|APPROVER
 *  rmeierh|Richard Meierhans|Richard.Meierhans@centurylink.com|APPROVER
 *
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
 * @class email_contacts
 *
 * @brief Class used to create a list of notification contacts.
 *
 */
class email_contacts extends library
{
	var $data;
	var $ora;                     // Database connection object
	var $error;                   // Error message when functions return false

	var $email_list;              // Hash array. keys=cuid, values=name|email_address

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

			$this->debug_start('email_contacts.html');
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
	 * @fn    initialize();
	 *
	 * @brief Initialize some class properties.
	 *
	 */
	private function initialize()
	{
		$this->error = "";
		$this->email_list = null;
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
	 * @fn    getContacts($hostname)
	 *
	 * @brief Used by server_contacts.php to get a list of contacts for a system.
	 *
	 * @param string $hostname
	 *
	 * @return bool
	 */
	public function getContacts($hostname)
	{
		$this->initialize();

		$list_of_netpin_no = array();

		$query  = "select distinct ";
		$query .= "  c.contact_netpin_no,   ";
		$query .= "  c.contact_approver_fyi ";
		$query .= "from ";
		$query .= "  cct7_tickets t, ";
		$query .= "  cct7_systems s, ";
		$query .= "  cct7_contacts c ";
		$query .= "where ";
		$query .= sprintf("  t.ticket_no = '%s' and ", $ticket_no);
		$query .= "  s.ticket_no = t.ticket_no and ";
		$query .= "  c.system_id = s.system_id ";

		if ($send_to_approvers == "Y" && $send_to_fyi == "N")
		{
			$query .= " and c.contact_approver_fyi = 'APPROVER' ";
		}
		else if ($send_to_approvers == "N" && $send_to_fyi == "Y")
		{
			$query .= " and c.contact_approver_fyi = 'FYI' ";
		}

		if ($waiting_only == "Y")
		{
			$query .= " and c.contact_response_status = 'WAITING' ";
		}

		$query .= "order by ";
		$query .= "  c.contact_netpin_no";

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
			$list_of_netpin_no[$this->ora->contact_netpin_no] = $this->ora->contact_approver_fyi;
		}

		return $this->buildList($list_of_netpin_no);
	}

	/**
	 * @fn    byTicket($ticket_no, $send_to_approvers, $send_to_fyi)
	 *
	 * @brief Get list of contacts for a ticket.
	 *
	 * @param string $ticket_no
	 * @param string $send_to_approvers
	 * @param string $send_to_fyi
	 * @param string $waiting_only
	 *
	 * @return bool
	 */
	public function byTicket($ticket_no, $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
	{
		$this->initialize();

		$list_of_netpin_no = array();

		$query  = "select distinct ";
		$query .= "  c.contact_netpin_no,   ";
		$query .= "  c.contact_approver_fyi ";
		$query .= "from ";
		$query .= "  cct7_tickets t, ";
		$query .= "  cct7_systems s, ";
		$query .= "  cct7_contacts c ";
		$query .= "where ";
		$query .= sprintf("  t.ticket_no = '%s' and ", $ticket_no);
		$query .= "  s.ticket_no = t.ticket_no and ";
		$query .= "  c.system_id = s.system_id ";

		if ($send_to_approvers == "Y" && $send_to_fyi == "N")
		{
			$query .= " and c.contact_approver_fyi = 'APPROVER' ";
		}
		else if ($send_to_approvers == "N" && $send_to_fyi == "Y")
		{
			$query .= " and c.contact_approver_fyi = 'FYI' ";
		}

		if ($waiting_only == "Y")
		{
			$query .= " and c.contact_response_status = 'WAITING' ";
		}

		$query .= "order by ";
		$query .= "  c.contact_netpin_no";

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
			$list_of_netpin_no[$this->ora->contact_netpin_no] = $this->ora->contact_approver_fyi;
		}

		return $this->buildList($list_of_netpin_no);
	}


	/**
	 * @fn    bySystem($system_id, $send_to_approvers, $send_to_fyi)
	 *
	 * @brief Get list of contacts for a ticket and server.
	 *
	 * @param int    $system_id
	 * @param string $send_to_approvers
	 * @param string $send_to_fyi
	 * @param string $waiting_only
	 *
	 * @return bool
	 */
	public function bySystem($system_id, $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
	{
		$this->initialize();

		$list_of_netpin_no = array();

		$query  = "select distinct ";
		$query .= "  c.contact_netpin_no,   ";
		$query .= "  c.contact_approver_fyi ";
		$query .= "from ";
		$query .= "  cct7_systems s, ";
		$query .= "  cct7_contacts c ";
		$query .= "where ";
		$query .= sprintf("  s.system_id = %d and ", $system_id);
		$query .= "  c.system_id = s.system_id ";

		if ($send_to_approvers == "Y" && $send_to_fyi == "N")
		{
			$query .= " and c.contact_approver_fyi = 'APPROVER' ";
		}
		else if ($send_to_approvers == "N" && $send_to_fyi == "Y")
		{
			$query .= " and c.contact_approver_fyi = 'FYI' ";
		}

		if ($waiting_only == "Y")
		{
			$query .= " and c.contact_response_status = 'WAITING' ";
		}

		$query .= "order by ";
		$query .= "  c.contact_netpin_no";

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
			$list_of_netpin_no[$this->ora->contact_netpin_no] = $this->ora->contact_approver_fyi;
		}

		return $this->buildList($list_of_netpin_no);
	}

	/**
	 * @fn    byContact($ticket_no, $system_id, $contact_id, $send_to_approvers, $send_to_fyi)
	 *
	 * @brief Get list of contacts for a ticket, server, netpin or subscriber no.
	 *
	 * @param string $ticket_no
	 * @param int    $system_id
	 * @param int    $contact_id
	 * @param string $send_to_approvers
	 * @param string $send_to_fyi
	 * @param string $waiting_only
	 *
	 * @return bool
	 */
	public function byContact($ticket_no, $system_id, $contact_id, $send_to_approvers="Y", $send_to_fyi="N", $waiting_only="N")
	{
		$this->initialize();

		$list_of_netpin_no = array();

		$query  = "select distinct ";
		$query .= "  c.contact_netpin_no,   ";
		$query .= "  c.contact_approver_fyi ";
		$query .= "from ";
		$query .= "  cct7_tickets t, ";
		$query .= "  cct7_systems s, ";
		$query .= "  cct7_contacts c ";
		$query .= "where ";
		$query .= sprintf("  t.ticket_no = '%s' and ", $ticket_no);
		$query .= "  s.ticket_no = t.ticket_no and ";
		$query .= sprintf("  s.system_id = %d and ", $system_id);
		$query .= "  c.system_id = s.system_id and ";
		$query .= sprintf("  c.contact_id = %d ", $contact_id);

		if ($send_to_approvers == "Y" && $send_to_fyi == "N")
		{
			$query .= " and c.contact_approver_fyi = 'APPROVER' ";
		}
		else if ($send_to_approvers == "N" && $send_to_fyi == "Y")
		{
			$query .= " and c.contact_approver_fyi = 'FYI' ";
		}

		if ($waiting_only == "Y")
		{
			$query .= " and c.contact_response_status = 'WAITING' ";
		}

		$query .= "order by ";
		$query .= "  c.contact_netpin_no";

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
			$list_of_netpin_no[$this->ora->contact_netpin_no] = $this->ora->contact_approver_fyi;
		}

		return $this->buildList($list_of_netpin_no);
	}

	/**
	 * @fn    buildList($list_of_netpin_no)
	 *
	 * @brief Build an array containing names and email addresses.
	 *
	 * @param array $list_of_netpin_no
	 *
	 * @return bool
	 */
	public function buildList($list_of_netpin_no)
	{
		$list_of_mnet = array();
		$count = 0;

		$this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $list_of_netpin_no);


		foreach ($list_of_netpin_no as $netpin_no => $approver_fyi)
		{
			$override = false;

			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "netpin_no: %s, approver_fyi: %s", $netpin_no, $approver_fyi);

			//
			// Check to see if there are any cct7_override_netpins records.
			//

			// Table: CCT7_OVERRIDE_NETPINS
			// netpin_id|NUMBER|0|NOT NULL|PK: Unique record ID
			// create_date|NUMBER|0||Date record was created (GMT timestamp)
			// create_cuid|VARCHAR2|20||CUID of person who created the record
			// create_name|VARCHAR2|200||Name of person who created the record
			// netpin_no|VARCHAR2|20|NOT NULL|Netpin No.

			// Table: CCT7_OVERRIDE_MEMBERS
			// member_id|NUMBER|0|NOT NULL|PK: Unique record ID
			// netpin_id|NUMBER|0||FK: cct7_override_netpins.netpin_id
			// create_date|NUMBER|0||Date record was created. (GMT unix timestamp)
			// create_cuid|VARCHAR2|20||CUID of person who created this record
			// create_name|VARCHAR2|200||Name of person who created this record
			// member_cuid|VARCHAR2|20||CUID of person who will receive notifications
			// member_name|VARCHAR2|200||Name of person who will receive notifications

			// Table: CCT7_MNET
			// mnet_id|VARCHAR2|6|NOT NULL|
			// mnet_cuid|VARCHAR2|20|NOT NULL|User ID
			// mnet_workstation_login|VARCHAR2|20||CTL workstation login
			// mnet_last_name|VARCHAR2|80||Last name
			// mnet_first_name|VARCHAR2|80||First name
			// mnet_nick_name|VARCHAR2|80||Nick name
			// mnet_middle|VARCHAR2|20||Middle name
			// mnet_name|VARCHAR2|200||Full name
			// mnet_job_title|VARCHAR2|80||Job Title
			// mnet_email|VARCHAR2|80||Email Address
			// mnet_work_phone|VARCHAR2|20||Work phone number
			// mnet_pager|VARCHAR2|20||Pager number
			// mnet_street|VARCHAR2|80||Street address
			// mnet_city|VARCHAR2|80||City
			// mnet_state|VARCHAR2|10||State
			// mnet_country|VARCHAR2|10||Country
			// mnet_rc|VARCHAR2|45||QWEST RC Code
			// mnet_company|VARCHAR2|80||Employee Company name
			// mnet_tier1|VARCHAR2|100||CMP Support Tier1
			// mnet_tier2|VARCHAR2|100||CMP Support Tier2
			// mnet_tier3|VARCHAR2|100||CMP Support Tier3
			// mnet_status|VARCHAR2|20||Employee Status
			// mnet_change_date|DATE|7||Date Record last updated
			// mnet_ctl_cuid|VARCHAR2|15||CMP Sponsor Manager User ID
			// mnet_mgr_cuid|VARCHAR2|15||Manager User ID

			$query  = "select ";
			$query .= "  mnet.mnet_cuid  as mnet_cuid, ";
			$query .= "  mnet.mnet_name  as mnet_name, ";
			$query .= "  mnet.mnet_email as mnet_email ";
			$query .= "from ";
			$query .= "  cct7_override_netpins netpin, ";
			$query .= "  cct7_override_members member, ";
			$query .= "  cct7_mnet             mnet    ";
			$query .= "where ";
			$query .= sprintf("  netpin.netpin_no = '%s' and ", $netpin_no);
			$query .= "  member.netpin_id = netpin.netpin_id and ";
			$query .= "  mnet.mnet_cuid = member.member_cuid  and ";
			$query .= "  mnet.mnet_email is not null ";
			$query .= "order by ";
			$query .= "  mnet.mnet_cuid";

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
				$override = true;
				$count += 1;

				if (array_key_exists($this->ora->mnet_cuid, $list_of_mnet))
				{
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "changing: %s", $this->ora->mnet_cuid);
					$str = $list_of_mnet[$this->ora->mnet_cuid] . ", " . $netpin_no;
					$list_of_mnet[$this->ora->mnet_cuid] = $str;
				}
				else
				{
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "adding: %s", $this->ora->mnet_cuid);
					$list_of_mnet[$this->ora->mnet_cuid] =
						sprintf("%s|%s|%s|%s", $this->ora->mnet_name, $this->ora->mnet_email, $approver_fyi, $netpin_no);
				}
			}

			if ($override)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "This was an override. Continue;");
				continue;
			}

			//
			// IS THIS A SUBSCRIBER?
			//
			// cct7_subscriber_members

			// Table: CCT7_SUBSCRIBER_GROUPS
			// group_id|VARCHAR2|20|NOT NULL|PK: Unique Record ID
			// create_date|NUMBER|0||GMT date record was created
			// owner_cuid|VARCHAR2|20||Owner CUID of this subscriber list
			// owner_name|VARCHAR2|200||Owner NAME of this subscriber list
			// group_name|VARCHAR2|200||Group Name



			// Table: CCT7_SUBSCRIBER_SERVERS
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

			// Table: CCT7_SUBSCRIBER_MEMBERS
			// member_id|NUMBER|0|NOT NULL|PK: Unique Record ID
			// group_id|VARCHAR2|20||FK: cct7_subscriber_groups
			// create_date|NUMBER|0||GMT date record was created
			// member_cuid|VARCHAR2|20||Member CUID
			// member_name|VARCHAR2|200||Member NAME

			$query  = " select distinct  ";
			$query .= "  m.mnet_cuid  as mnet_cuid, ";
			$query .= "  m.mnet_name  as mnet_name, ";
			$query .= "  m.mnet_email as mnet_email ";
			$query .= "from ";
			$query .= "  cct7_subscriber_members n, ";
			$query .= "  cct7_mnet               m  ";
			$query .= "where ";
			$query .= sprintf(" n.group_id = '%s' and ", $netpin_no);
			$query .= " m.mnet_cuid = n.member_cuid and ";
			$query .= " m.mnet_email is not null ";

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
				$count += 1;

				if (array_key_exists($this->ora->mnet_cuid, $list_of_mnet))
				{
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "changing: %s", $this->ora->mnet_cuid);
					$str = $list_of_mnet[$this->ora->mnet_cuid] . ", " . $netpin_no;
					$list_of_mnet[$this->ora->mnet_cuid] = $str;
				}
				else
				{
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "adding: %s", $this->ora->mnet_cuid);
					$list_of_mnet[$this->ora->mnet_cuid] =
						sprintf("%s|%s|%s|%s", $this->ora->mnet_name, $this->ora->mnet_email, $approver_fyi, $netpin_no);
				}
			}

			//
			// IS THIS A NETPIN?
			//

			// Table: CCT7_NETPIN_TO_CUID
			// net_pin_no|VARCHAR2|20||Net-Pin number defined in Net-Tool
			// user_cuid|VARCHAR2|20||Employee CUID that is a member of the net_pin_no group
			// oncall_primary|VARCHAR2|10||Is this person the primary oncall person? Y/N
			// oncall_backup|VARCHAR2|10||Is this person the backup oncall person? Y/N
			// last_update|DATE|7||Record last updated. Used to verify records are being refreshed nightly.

			$query  = "select ";
			$query .= "  m.mnet_cuid  as mnet_cuid, ";
			$query .= "  m.mnet_name  as mnet_name, ";
			$query .= "  m.mnet_email as mnet_email ";
			$query .= "from ";
			$query .= "  cct7_netpin_to_cuid   n, ";
			$query .= "  cct7_mnet             m  ";
			$query .= "where ";
			$query .= sprintf("  n.net_pin_no = '%s' and ", $netpin_no);
			$query .= "  m.mnet_cuid = n.user_cuid and ";
			$query .= "  m.mnet_email is not null ";
			$query .= "order by ";
			$query .= "  m.mnet_cuid";

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
				$count += 1;

				if (array_key_exists($this->ora->mnet_cuid, $list_of_mnet))
				{
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "changing: %s", $this->ora->mnet_cuid);
					$str = $list_of_mnet[$this->ora->mnet_cuid] . ", " . $netpin_no;
					$list_of_mnet[$this->ora->mnet_cuid] = $str;
				}
				else
				{
					$this->debug1(__FILE__, __FUNCTION__, __LINE__, "adding: %s", $this->ora->mnet_cuid);
					$list_of_mnet[$this->ora->mnet_cuid] =
						sprintf("%s|%s|%s|%s", $this->ora->mnet_name, $this->ora->mnet_email, $approver_fyi, $netpin_no);
				}
			}
		}

		if ($count > 0)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "count = %d", $count);
			$this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $list_of_mnet);
			$this->email_list = $list_of_mnet;
			return true;
		}

		return false;
	}
}