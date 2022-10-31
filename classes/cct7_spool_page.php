<?php
/**
 * cct7_spool_page.php
 *
 * @package   PhpStorm
 * @file      cct7_spool_page.php
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

class cct7_spool_page extends library
{
	var $data;                    // Magic variables
	var $ora;                     // Database connection object
	var $error;                   // Error message when functions return false

	var $spool_email_id;
	var $spool_email_date;
	var $sendmail_date;
	var $sendmail_successful;
	var $ticket_id;
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

			$this->debug_start('cct7_spool_page.html');
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


}