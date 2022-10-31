<?php
/**
 * cct7_spool_email.php
 *
 * @package   PhpStorm
 * @file      cct7_spool_email.php
 * @author    gparkin
 * @date      8/31/16
 * @version   7.0
 *
 * @brief     This class handles all the functionality for sending email.
 *
 * $Log:  $
 *
 * $Source:  $
 */

//
// Public Methods
//
//

//
//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');  //!< @see includes/autoloader.php
}

class cct7_spool_email extends library
{
	var $data;                    // Magic variables
	var $ora;                     // Database connection object
	var $error;                   // Error message when functions return false

	var $spool_email_id;
	var $spool_email_date;
	var $sendmail_date;
	var $sendmail_successful;
	var $ticket_no;
	var $system_id;
	var $hostname;
	var $netpin_no;
	var $email_subject;
	var $email_from;
	var $email_to;
	var $email_cc;
	var $email_bc;
	var $email_template;
	var $email_message;

	/**
	 * @fn    __construct()
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

		$this->init_spool_email();

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

			$this->debug_start('cct7_spool_email.html');
		}
	}

	/**
	 * @fn    __destruct()
	 *
	 * @brief Destructor function called when no other references to this object can be found, or in any
	 *        order during the shutdown sequence. The destructor will be called even if script execution
	 *        is stopped using exit(). Calling exit() in a destructor will prevent the remaining shutdown
	 *        routines from executing.
	 *
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
	 * @fn __get($name)
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
	 * @fn __isset($name)
	 *
	 * @brief Determine if item ($name) exists in the $this->data array
	 * @brief var_dump(isset($obj->first_name));
	 *
	 * @param string $name is the key in the associated $data array
	 *
	 * @return true or false
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
	 *
	 * @return void
	 */
	public function __unset($name)
	{
		unset($this->data[$name]);
	}

	public function init_spool_email()
	{
		$this->spool_email_id      = 0;
		$this->spool_email_date    = 0;;
		$this->sendmail_date       = 0;
		$this->sendmail_successful = '';
		$this->ticket_no           = '';
		$this->system_id           = 0;
		$this->hostname            = '';
		$this->netpin_no           = '';
		$this->email_subject       = '';
		$this->email_from          = '';
		$this->email_to            = '';
		$this->email_cc            = '';
		$this->email_bc            = '';
		$this->email_template      = '';
		$this->email_message       = '';
	}

	/**
	 * @fn    activateTicket($ticket_no)
	 *
	 * @brief Send a activation message to all contacts identified by $ticket_no
	 *
	 * @param string $ticket_no
	 *
	 * @return bool
	 */
	public function activateTicket($ticket_no)
	{
		$tic = new cct7_tickets($this->ora);

		//
		// Retrieve the ticket from cct7_tickets
		//
		if ($tic->getTicket($ticket_no) == false)
		{
			$this->error = $tic->error;
			return false;
		}

		$subject = "CCT Work Request Notification for ticket: " . $ticket_no;

		//
		// I'm no longer going to include a list of servers the SA wants them to approve. It might be too
		// confusing for them if the approvals are for complexes where they have no responsibility for but where that
		// complex server may effect their application server from a possible reboot. This way when they log into CCT
		// to approve they can see the server list then they can drill down to see the connections on why they have
		// been included.
		//
		$cct_url = "<a href=cct.intranet.com>cct.intranet.com</a>";

		if ($tic->approvals_required == 'Y')
		{
			$message  = "<p>Please login to CCT at " . $cct_url . " to review and respond to this work activity. ";
		}
		else
		{
			$message  = "<p>You are not required to approve this work, but you should login to CCT at ";
			$message .= $cct_url . " to review this work activity. ";
		}

		$message .= "A list of servers and when the work activity starts and ends will be available in CCT.</p>";
		$message .= "<p><b>A description of the work is as follows:</b><br>" . $tic->work_description;

		$query  = "select distinct ";
		$query .= "  m.mnet_cuid, ";
		$query .= "  m.mnet_email ";
		$query .= "from ";
		$query .= " cct7_systems s, ";
		$query .= " cct7_contacts c, ";
		$query .= " cct7_netpin_to_cuid n, ";
		$query .= " cct7_mnet m ";
		$query .= "where ";
		$query .= " s.ticket_no = '" . $ticket_no . "' and ";
		$query .= " c.system_id = s.system_id and ";
		$query .= " n.net_pin_no = c.contact_netpin_no and ";
		$query .= " m.mnet_cuid = n.user_cuid and ";
		$query .= " m.mnet_email is not null";

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$contacts = array( $tic->owner_cuid => $tic->owner_email);
		$count = 0;

		while ($this->ora->fetch())
		{
			++$count;
			$contacts[$this->ora->mnet_cuid] = $this->ora->mnet_email;
		}

		if ($count == 0)
		{
			$message = "CCT was unable to locate any contacts to send this work notification too!";
		}

		foreach ($contacts as $cuid => $email_address)
		{
			$spool_email_id = $this->ora->next_seq('cct7_spool_emailseq');

			$rc = $this->ora
				->insert("cct7_spool_email")
				->column("spool_email_id")
				->column("spool_email_date")
				->column("ticket_no")
				->column("email_subject")
				->column("email_from")
				->column("email_to")
				->column("email_message")
				->value("int",  $spool_email_id)              // spool_email_id
				->value("int",  $this->now_to_gmt_utime())    // sendmail_date
				->value("char", $ticket_no)                   // ticket_no
				->value("char", $subject)	                  // email_subject
				->value("char", $this->user_email)            // email_from
				->value("char", $email_address)               // email_to
				->value("char", $message)                     // email_message
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

		if ($this->putLogTicket($this->ora, $ticket_no, 'EMAIL', $subject) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);

			return false;
		}

		$this->ora->commit();

		return true;
	}

	/**
	 * @fn    cancelTicket($ticket_no)
	 *
	 * @brief Send a cancel message to all contacts identified by $ticket_no
	 *
	 * @param string $ticket_no
	 *
	 * @return bool
	 */
	public function cancelTicket($ticket_no)
	{
		$tic = new cct7_tickets($this->ora);

		//
		// Retrieve the ticket from cct7_tickets
		//
		if ($tic->getTicket($ticket_no) == false)
		{
			$this->error = $tic->error;
			return false;
		}

		$subject = "CCT Work Request Notification for ticket: " . $ticket_no . " has been canceled.";

		$message = "This CCT work request has been canceled. No further action is required.";

		$query  = "select distinct ";
		$query .= "  m.mnet_cuid, ";
		$query .= "  m.mnet_email ";
		$query .= "from ";
		$query .= " cct7_systems s, ";
		$query .= " cct7_contacts c, ";
		$query .= " cct7_netpin_to_cuid n, ";
		$query .= " cct7_mnet m ";
		$query .= "where ";
		$query .= " s.ticket_no = '" . $ticket_no . "' and ";
		$query .= " c.system_id = s.system_id and ";
		$query .= " n.net_pin_no = c.contact_netpin_no and ";
		$query .= " m.mnet_cuid = n.user_cuid and ";
		$query .= " m.mnet_email is not null";

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$contacts = array( $tic->owner_cuid => $tic->owner_email);
		$count = 0;

		while ($this->ora->fetch())
		{
			++$count;
			$contacts[$this->ora->mnet_cuid] = $this->ora->mnet_email;
		}

		if ($count == 0)
		{
			$message = "CCT was unable to locate any contacts to send this work notification too!";
		}

		foreach ($contacts as $cuid => $email_address)
		{
			$spool_email_id = $this->ora->next_seq('cct7_spool_emailseq');

			$rc = $this->ora
				->insert("cct7_spool_email")
				->column("spool_email_id")
				->column("spool_email_date")
				->column("ticket_no")
				->column("email_subject")
				->column("email_from")
				->column("email_to")
				->column("email_message")
				->value("int",  $spool_email_id)              // spool_email_id
				->value("int",  $this->now_to_gmt_utime())    // sendmail_date
				->value("char", $ticket_no)                   // ticket_no
				->value("char", $subject)	                  // email_subject
				->value("char", $this->user_email)            // email_from
				->value("char", $email_address)               // email_to
				->value("char", $message)                     // email_message
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

		if ($this->putLogTicket($this->ora, $ticket_no, 'EMAIL', $subject) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);

			return false;
		}

		$this->ora->commit();

		return true;
	}

	/**
	 * @fn    spoolTicket($ticket_no, $subject, $cc, $bc, $message)
	 *
	 * @brief Send email to all contacts identified by $ticket_no
	 *
	 * @param string $ticket_no
	 * @param string $subject
	 * @param string $cc
	 * @param string $bc
	 * @param string $message
	 *
	 * @return bool
	 */
	public function spoolTicket($ticket_no, $subject, $cc, $bc, $message)
	{
		$tic = new cct7_tickets($this->ora);

		//
		// Retrieve the ticket from cct7_tickets
		//
		if ($tic->getTicket($ticket_no) == false)
		{
			$this->error = $tic->error;
			return false;
		}

		$query  = "select distinct ";
		$query .= "  m.mnet_cuid, ";
		$query .= "  m.mnet_email ";
		$query .= "from ";
		$query .= " cct7_systems s, ";
		$query .= " cct7_contacts c, ";
		$query .= " cct7_netpin_to_cuid n, ";
		$query .= " cct7_mnet m ";
		$query .= "where ";
		$query .= " s.ticket_no = '" . $ticket_no . "' and ";
		$query .= " c.system_id = s.system_id and ";
		$query .= " n.net_pin_no = c.contact_netpin_no and ";
		$query .= " m.mnet_cuid = n.user_cuid and ";
		$query .= " m.mnet_email is not null";

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$contacts = array( $tic->owner_cuid => $tic->owner_email);
		$count = 0;

		while ($this->ora->fetch())
		{
			++$count;
			$contacts[$this->ora->mnet_cuid] = $this->ora->mnet_email;
		}

		if ($count == 0)
		{
			$message = "CCT was unable to locate any contacts to send this work notification too!";
		}

		foreach ($contacts as $cuid => $email_address)
		{
			$spool_email_id = $this->ora->next_seq('cct7_spool_emailseq');

			$rc = $this->ora
				->insert("cct7_spool_email")
				->column("spool_email_id")
				->column("spool_email_date")
				->column("ticket_no")
				->column("email_subject")
				->column("email_from")
				->column("email_to")
				->column("email_cc")
				->column("email_bc")
				->column("email_message")
				->value("int",  $spool_email_id)              // spool_email_id
				->value("int",  $this->now_to_gmt_utime())    // sendmail_date
				->value("char", $ticket_no)                   // ticket_no
				->value("char", $subject)	                  // email_subject
				->value("char", $this->user_email)            // email_from
				->value("char", $email_address)               // email_to
				->value("char", $cc)                          // email_cc
				->value("char", $bc)                          // email_bc
				->value("char", $message)                     // email_message
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

		if ($this->putLogTicket($this->ora, $ticket_no, 'EMAIL', $subject) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);

			return false;
		}

		$this->ora->commit();

		return true;
	}

	/**
	 * @fn    spoolHostname($ticket_no, $hostname, $subject, $cc, $bc, $message)
	 *
	 * @brief Send email to all contacts identified by $ticket_no, $hostname
	 *
	 * @param string $ticket_no
	 * @param string $hostname
	 * @param string $subject
	 * @param string $cc
	 * @param string $bc
	 * @param string $message
	 *
	 * @return bool
	 */
	public function spoolHostname($ticket_no, $hostname, $subject, $cc, $bc, $message)
	{
		$system_id = 0;

		$tic = new cct7_tickets($this->ora);

		//
		// Retrieve the ticket from cct7_tickets
		//
		if ($tic->getTicket($ticket_no) == false)
		{
			$this->error = $tic->error;
			return false;
		}

		$query  = "select distinct ";
		$query .= "  m.mnet_cuid, ";
		$query .= "  m.mnet_email, ";
		$query .= "  s.system_id ";
		$query .= "from ";
		$query .= " cct7_systems s, ";
		$query .= " cct7_contacts c, ";
		$query .= " cct7_netpin_to_cuid n, ";
		$query .= " cct7_mnet m ";
		$query .= "where ";
		$query .= " s.ticket_no = '" . $ticket_no . "' and ";
		$query .= " c.system_id = s.system_id and ";
		$query .= " s.system_hostname = '" . $hostname . "' and";
		$query .= " n.net_pin_no = c.contact_netpin_no and ";
		$query .= " m.mnet_cuid = n.user_cuid and ";
		$query .= " m.mnet_email is not null";

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$contacts = array( $tic->owner_cuid => $tic->owner_email);
		$count = 0;

		while ($this->ora->fetch())
		{
			++$count;
			$contacts[$this->ora->mnet_cuid] = $this->ora->mnet_email;
			$system_id = $this->ora->system_id;
		}

		if ($count == 0)
		{
			$message = "CCT was unable to locate any contacts to send this work notification too!";
		}

		foreach ($contacts as $cuid => $email_address)
		{
			$spool_email_id = $this->ora->next_seq('cct7_spool_emailseq');

			$rc = $this->ora
				->insert("cct7_spool_email")
				->column("spool_email_id")
				->column("spool_email_date")
				->column("ticket_no")
				->column("hostname")
				->column("email_subject")
				->column("email_from")
				->column("email_to")
				->column("email_cc")
				->column("email_bc")
				->column("email_message")
				->value("int",  $spool_email_id)              // spool_email_id
				->value("int",  $this->now_to_gmt_utime())    // sendmail_date
				->value("char", $ticket_no)                   // ticket_no
				->value("char", $hostname)                    // hostname
				->value("char", $subject)	                  // email_subject
				->value("char", $this->user_email)            // email_from
				->value("char", $email_address)               // email_to
				->value("char", $cc)                          // email_cc
				->value("char", $bc)                          // email_bc
				->value("char", $message)                     // email_message
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

		// putLogSystem($ora, $ticket_no, $system_id, $hostname, $netpin_no, $event_type, $event_message)

		if ($this->putLogSystem($this->ora, $ticket_no, $system_id, $hostname, '', 'EMAIL', $subject) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->error);

			return false;
		}

		$this->ora->commit();

		return true;
	}

	/**
	 * @fn    spoolSystemID($ticket_no, $system_id, $subject, $cc, $bc, $message)
	 *
	 * @brief Send email to all contacts identified by $ticket_no and $system_id
	 *
	 * @param string $ticket_no
	 * @param int    $system_id
	 * @param string $subject
	 * @param string $cc
	 * @param string $bc
	 * @param string $message
	 *
	 * @return bool
	 */
	public function spoolSystemID($ticket_no, $system_id, $subject, $cc, $bc, $message)
	{
		$tic = new cct7_tickets($this->ora);

		//
		// Retrieve the ticket from cct7_tickets
		//
		if ($tic->getTicket($ticket_no) == false)
		{
			$this->error = $tic->error;
			return false;
		}

		$query  = "select distinct ";
		$query .= "  m.mnet_cuid, ";
		$query .= "  m.mnet_email ";
		$query .= "from ";
		$query .= " cct7_systems s, ";
		$query .= " cct7_contacts c, ";
		$query .= " cct7_netpin_to_cuid n, ";
		$query .= " cct7_mnet m ";
		$query .= "where ";
		$query .= " s.ticket_no = '" . $ticket_no . "' and ";
		$query .= " c.system_id = s.system_id and ";
		$query .= " s.system_id = " . $system_id . " and";
		$query .= " n.net_pin_no = c.contact_netpin_no and ";
		$query .= " m.mnet_cuid = n.user_cuid and ";
		$query .= " m.mnet_email is not null";

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$contacts = array( $tic->owner_cuid => $tic->owner_email);
		$count = 0;

		while ($this->ora->fetch())
		{
			++$count;
			$contacts[$this->ora->mnet_cuid] = $this->ora->mnet_email;
		}

		if ($count == 0)
		{
			$message = "CCT was unable to locate any contacts to send this work notification too!";
		}

		foreach ($contacts as $cuid => $email_address)
		{
			$spool_email_id = $this->ora->next_seq('cct7_spool_emailseq');

			$rc = $this->ora
				->insert("cct7_spool_email")
				->column("spool_email_id")
				->column("spool_email_date")
				->column("system_id")
				->column("email_subject")
				->column("email_from")
				->column("email_to")
				->column("email_cc")
				->column("email_bc")
				->column("email_message")
				->value("int",  $spool_email_id)              // spool_email_id
				->value("int",  $this->now_to_gmt_utime())    // sendmail_date
				->value("char", $system_id)                   // system_id
				->value("char", $subject)	                  // email_subject
				->value("char", $this->user_email)            // email_from
				->value("char", $email_address)               // email_to
				->value("char", $cc)                          // email_cc
				->value("char", $bc)                          // email_bc
				->value("char", $message)                     // email_message
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

		$con = new cct7_contacts($this->ora);

		$netpin = '';

		if ($con->putLogContact($system_id, $netpin, 'EMAIL', $subject) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $con->error);
			$this->error = $con->error;

			return false;
		}

		$this->ora->commit();

		return true;
	}

	/**
	 * @fn    spoolHostnameNetpin($ticket_no, $hostname, $netpin, $subject, $cc, $bc, $message)
	 *
	 * @brief Send email to all contacts identified by $ticket_no, $hostname, and $netpin
	 *
	 * @param string $ticket_no
	 * @param string $hostname
	 * @param string $netpin
	 * @param string $subject
	 * @param string $cc
	 * @param string $bc
	 * @param string $message
	 *
	 * @return bool
	 */
	public function spoolHostnameNetpin($ticket_no, $hostname, $netpin, $subject, $cc, $bc, $message)
	{
		$system_id = 0;

		$tic = new cct7_tickets($this->ora);

		//
		// Retrieve the ticket from cct7_tickets
		//
		if ($tic->getTicket($ticket_no) == false)
		{
			$this->error = $tic->error;
			return false;
		}

		$query  = "select distinct ";
		$query .= "  m.mnet_cuid, ";
		$query .= "  m.mnet_email, ";
		$query .= "  s.system_id ";
		$query .= "from ";
		$query .= " cct7_systems s, ";
		$query .= " cct7_contacts c, ";
		$query .= " cct7_netpin_to_cuid n, ";
		$query .= " cct7_mnet m ";
		$query .= "where ";
		$query .= " s.ticket_no = '" . $ticket_no . "' and ";
		$query .= " c.system_id = s.system_id and ";
		$query .= " s.system_hostname = '" . $hostname . "' and";
		$query .= " n.net_pin_no = c.contact_netpin_no and ";
		$query .= " c.contact_netpin_no = '" . $netpin . "' and ";
		$query .= " m.mnet_cuid = n.user_cuid and ";
		$query .= " m.mnet_email is not null";

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$contacts = array( $tic->owner_cuid => $tic->owner_email);
		$count = 0;

		while ($this->ora->fetch())
		{
			++$count;
			$contacts[$this->ora->mnet_cuid] = $this->ora->mnet_email;
			$system_id = $this->ora->system_id;
		}

		if ($count == 0)
		{
			$message = "CCT was unable to locate any contacts to send this work notification too!";
		}

		foreach ($contacts as $cuid => $email_address)
		{
			$spool_email_id = $this->ora->next_seq('cct7_spool_emailseq');

			$rc = $this->ora
				->insert("cct7_spool_email")
				->column("spool_email_id")
				->column("spool_email_date")
				->column("ticket_no")
				->column("hostname")
				->column("email_subject")
				->column("email_from")
				->column("email_to")
				->column("email_cc")
				->column("email_bc")
				->column("email_message")
				->value("int",  $spool_email_id)              // spool_email_id
				->value("int",  $this->now_to_gmt_utime())    // sendmail_date
				->value("char", $ticket_no)                   // ticket_no
				->value("char", $hostname)                    // hostname
				->value("char", $subject)	                  // email_subject
				->value("char", $this->user_email)            // email_from
				->value("char", $email_address)               // email_to
				->value("char", $cc)                          // email_cc
				->value("char", $bc)                          // email_bc
				->value("char", $message)                     // email_message
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

		$con = new cct7_contacts($this->ora);

		if ($con->putLogContact($system_id, $netpin, 'EMAIL', $subject) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $con->error);
			$this->error = $con->error;

			return false;
		}

		$this->ora->commit();

		return true;
	}

	/**
	 * @fn    spoolSystemIDNetpin($ticket_no, $system_id, $netpin, $subject, $cc, $bc, $message)
	 *
	 * @brief Send email to all contacts identified by $ticket_no, $system_id and $netpin
	 *
	 * @param string $ticket_no
	 * @param int    $system_id
	 * @param string $netpin
	 * @param string $subject
	 * @param string $cc
	 * @param string $bc
	 * @param string $message
	 *
	 * @return bool
	 */
	public function spoolSystemIDNetpin($ticket_no, $system_id, $netpin, $subject, $cc, $bc, $message)
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "ticket_no = %s", $ticket_no);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %d", $system_id);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "netpin    = %s", $netpin);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "subject   = %s", $subject);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "cc        = %s", $cc);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "bc        = %s", $bc);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__, "message   = %s", $message);

		$tic = new cct7_tickets($this->ora);

		//
		// Retrieve the ticket from cct7_tickets
		//
		if ($tic->getTicket($ticket_no) == false)
		{
			$this->error = $tic->error;
			return false;
		}

		$query  = "select distinct ";
		$query .= "  m.mnet_cuid, ";
		$query .= "  m.mnet_email ";
		$query .= "from ";
		$query .= " cct7_systems s, ";
		$query .= " cct7_contacts c, ";
		$query .= " cct7_netpin_to_cuid n, ";
		$query .= " cct7_mnet m ";
		$query .= "where ";
		$query .= " s.ticket_no = '" . $ticket_no . "' and ";
		$query .= " c.system_id = s.system_id and ";
		$query .= " s.system_id = " . $system_id . " and";
		$query .= " n.net_pin_no = c.contact_netpin_no and ";
		$query .= " c.contact_netpin_no = '" . $netpin . "' and ";
		$query .= " m.mnet_cuid = n.user_cuid and ";
		$query .= " m.mnet_email is not null";

		if ($this->ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
		}

		$contacts = array( $tic->owner_cuid => $tic->owner_email);
		$count = 0;

		while ($this->ora->fetch())
		{
			++$count;
			$contacts[$this->ora->mnet_cuid] = $this->ora->mnet_email;
		}

		if ($count == 0)
		{
			$message = "CCT was unable to locate any contacts to send this work notification too!";
		}

		foreach ($contacts as $cuid => $email_address)
		{
			$spool_email_id = $this->ora->next_seq('cct7_spool_emailseq');

			$rc = $this->ora
				->insert("cct7_spool_email")
				->column("spool_email_id")
				->column("spool_email_date")
				->column("system_id")
				->column("email_subject")
				->column("email_from")
				->column("email_to")
				->column("email_cc")
				->column("email_bc")
				->column("email_message")
				->value("int",  $spool_email_id)              // spool_email_id
				->value("int",  $this->now_to_gmt_utime())    // sendmail_date
				->value("int",  $system_id)                   // system_id
				->value("char", $subject)	                  // email_subject
				->value("char", $this->user_email)            // email_from
				->value("char", $email_address)               // email_to
				->value("char", $cc)                          // email_cc
				->value("char", $bc)                          // email_bc
				->value("char", $message)                     // email_message
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

		$con = new cct7_contacts($this->ora);

		if ($con->putLogContact($system_id, $netpin, 'EMAIL', $subject) == false)
		{
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $con->error);
			$this->error = $con->error;

			return false;
		}

		$this->ora->commit();

		return true;
	}

	/**
	 * @fn     sendSpooled()
	 *
	 * @brief  Scan cct7_spool_email and send email.
	 *
	 * @return bool
	 */
	public function sendSpooled()
	{
		$top = '';
		$p = '';

		if ($this->ora->sql2("select * from cct7_spool_email where sendmail_date = 0") == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $this->ora->sql_statement, $this->ora->dbErrMsg);
			return false;
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

			$p->spool_email_id      = $this->ora->spool_email_id;
			$p->spool_email_date    = $this->ora->spool_email_date;
			$p->sendmail_date       = $this->ora->sendmail_date;
			$p->sendmail_successful = $this->ora->sendmail_successful;
			$p->ticket_no           = $this->ora->ticket_no;
			$p->system_id           = $this->ora->system_id;
			$p->hostname            = $this->ora->hostname;
			$p->netpin_no           = $this->ora->netpin_no;
			$p->email_subject       = $this->ora->email_subject;
			$p->email_from          = $this->ora->email_from;
			$p->email_to            = $this->ora->email_to;
			$p->email_cc            = $this->ora->email_cc;
			$p->email_bc            = $this->ora->email_bc;
			$p->email_template      = $this->ora->email_template;
			$p->email_message       = $this->ora->email_message;
		}

		for ($p=$top; $p!=null; $p=$p->next)
		{
			//
			// Send the email
			//
			$headers  = sprintf("From: %s\r\n",     $p->email_from);
			$headers .= sprintf("Reply-To: %s\r\n", $p->email_from);
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

			$to       = $p->email_to;
			$subject  = $p->email_subject;
			$message  = "<html><body>";
			$message .= $p->email_message;
			$message .= "</body></html>";

			//
			// mail() returns true if mail seccessfully sent, otherwise false.
			//
			/*
			if (mail($to, $subject, $message, $headers))
			{
				$success = 'Y';
			}
			else
			{
				$success = 'N';
			}
			*/
			$success = 'Y';

			//
			// Update the sendmail_date and sendmail_successful status flag.
			//
			$rc = $this->ora
				->update('cct7_spool_email')
				->set("int",   "sendmail_date",       $this->now_to_gmt_utime())
				->set("char",  "sendmail_successful", $success)
				->where("int", "spool_email_id", "=", $p->spool_email_id)
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

		return true;
	}
}