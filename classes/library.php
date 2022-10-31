<?php
/**
 * @package    CCT
 * @file       library.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       $Date:  $ GMT
 * @version    $Revision:  $
 *
 * $Log:  $
 *
 * $Source:  $
 *
 * @brief      This program runs from the shell command line and it's purpose is to convert CCT6 records to CCT7.
 *
 */

//
// Base class library of reusable code!
//
// NOTE: It is very important that you do not turn on debugging without writing to a file for server side AJAX code. Any HTML
//       comments coming from functions while writing XML will show up in the XML output and you will get a XML parsing error
//       in the client side program.
//
// sql_insert_varchar2(&$query, $value, $add_comma)
// sql_insert_number(&$query, $value, $add_comma)
// sql_insert_to_date(&$query, $value, $add_comma)
// sql_insert_sysdate(&$query, $add_comma)
// sql_insert_sysdate_gmt_utime(&$query, $add_comma)
//
// sql_update_varchar2(&$query, $fieldname, $value, $add_comma)
// sql_update_number(&$query, $fieldname, $value, $add_comma)
// sql_update_to_date(&$query, $fieldname, $value, $add_comma)
// sql_update_sysdate(&$query, $fieldname, $add_comma)
// sql_update_sysdate_gmt_utime(&$query, $fieldname, $add_comma)
//
// fixDuration(&$duration)
// FixString($receive)
// isValidEmail($email)
// phone_clean($string)
// rightPad($str, $len)
// leftPad($str, $len)
// remove_doublewhitespace($s = null)
// remove_whitespace($s = null)
// remove_whitespace_feed($s = null)
// smart_clean($s = null)
// strip($str = null)
// substractDays($date, $days)
// addDays($date, $days)
//
// debug_start($debug_file)
// debug_on()
// debug_off()
// debug1()
// debug2()
// debug3()
// debug4()
// debug5()
// debug_sql1()
// debug_sql2()
// debug_sql3()
// debug_sql4()
// debug_sql5()
// debug_r1($file, $func, $line, $what = "")
// debug_r2($file, $func, $line, $what = "")
// debug_r3($file, $func, $line, $what = "")
// debug_r4($file, $func, $line, $what = "")
// debug_r5($file, $func, $line, $what = "")
// backtrace()
// error_reporting($level)
// environment_dump()
//

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

/** @class library
 *  @brief Library of useful miscellanous functions.
 *  @brief Used by all classes.
 *  @brief Called directly in program: trace_data_sources.php
 *  @brief Used by all Ajax servers.
 */
class library extends SqlFormatter
{
	var $default_timezone_name = '';
	var $fp_debug = null;
	var $sql_formatter = null;
	var $time_start = 0;
	var $time_end = 0;
	var $run_time = 0;

	var $user_timezone_name;
	var $user_timezone;

	var $debug_onoff = 0;

	//var $whoops;

	/** @fn __construct()
	 *
	 *  @brief Constructor function for the class library
	 *  @brief Called once when the class is first created.
	 */
	public function __construct()
	{
		date_default_timezone_set('America/Denver');

		//
		// The following is required by $this->now_to_gmt_utime();
		//
		$this->default_timezone_name = date_default_timezone_get();  // See timezone in php.ini

		if (PHP_SAPI === 'cli')
		{
			$this->user_timezone_name = 'America/Denver';
		}
		else
		{
			if (session_id() == '')
				session_start();     // Required to start once in order to retrieve user session information

			if (isset($_SESSION['local_timezone_name']))
			{
				$this->user_timezone_name = $_SESSION['local_timezone_name'];
			}
			else
			{
				$this->user_timezone_name = 'America/Denver';
			}
		}
	}

	/** @fn __destruct()
	 *  @brief Destructor function called when no other references to this object can be found, or in any
	 *  @brief order during the shutdown sequence. The destructor will be called even if script execution
	 *  @brief is stopped using exit(). Calling exit() in a destructor will prevent the remaining shutdown
	 *  @brief routines from executing.
	 *  @brief Attempting to throw an exception from a destructor (called in the time of script termination)
	 *  @brief causes a fatal error.
	 *  @return null
	 */
	public function __destruct()
	{
	}

	/**
	 * @fn    maxStringLength($str, $max_length)
	 *
	 * @brief Trim or truncate end of string down to a specific length. If it needs to be
	 *        truncated it will replace the last three chars in the max_length of the string
	 *        with ...
	 *
	 * @param string $str
	 * @param int    $max_length
	 *
	 * @return string
	 */
	public function maxStringLength($str, $max_length)
	{
		$max_length = 340;

		if (strlen($str) > $max_length)
		{
			$offset = ($max_length - 3) - strlen($str);
			return substr($str, 0, strrpos($str, ' ', $offset)) . '...';
		}

		return $str;
	}

	/**
	 * @fn    cleanString($text)
	 *
	 * @brief Returns an string clean of UTF8 characters. It will convert them to a similar ASCII character
	 *
	 * @param $text - String to clean.
	 *
	 * @return mixed
	 */
	public function cleanString($text)
	{
		// 1) convert á ô => a o
		$text = preg_replace("/[áàâãªä]/u","a",$text);
		$text = preg_replace("/[ÁÀÂÃÄ]/u","A",$text);
		$text = preg_replace("/[ÍÌÎÏ]/u","I",$text);
		$text = preg_replace("/[íìîï]/u","i",$text);
		$text = preg_replace("/[éèêë]/u","e",$text);
		$text = preg_replace("/[ÉÈÊË]/u","E",$text);
		$text = preg_replace("/[óòôõºö]/u","o",$text);
		$text = preg_replace("/[ÓÒÔÕÖ]/u","O",$text);
		$text = preg_replace("/[úùûü]/u","u",$text);
		$text = preg_replace("/[ÚÙÛÜ]/u","U",$text);
		$text = preg_replace("/[’‘‹›‚]/u","'",$text);
		$text = preg_replace("/[“”«»„]/u",'"',$text);
		$text = str_replace("–","-",$text);
		$text = str_replace(" "," ",$text);
		$text = str_replace("ç","c",$text);
		$text = str_replace("Ç","C",$text);
		$text = str_replace("ñ","n",$text);
		$text = str_replace("Ñ","N",$text);

		//2) Translation CP1252. &ndash; => -
		$trans = get_html_translation_table(HTML_ENTITIES);
		$trans[chr(130)] = '&sbquo;';   // Single Low-9 Quotation Mark
		$trans[chr(131)] = '&fnof;';    // Latin Small Letter F With Hook
		$trans[chr(132)] = '&bdquo;';   // Double Low-9 Quotation Mark
		$trans[chr(133)] = '&hellip;';  // Horizontal Ellipsis
		$trans[chr(134)] = '&dagger;';  // Dagger
		$trans[chr(135)] = '&Dagger;';  // Double Dagger
		$trans[chr(136)] = '&circ;';    // Modifier Letter Circumflex Accent
		$trans[chr(137)] = '&permil;';  // Per Mille Sign
		$trans[chr(138)] = '&Scaron;';  // Latin Capital Letter S With Caron
		$trans[chr(139)] = '&lsaquo;';  // Single Left-Pointing Angle Quotation Mark
		$trans[chr(140)] = '&OElig;';   // Latin Capital Ligature OE
		$trans[chr(145)] = '&lsquo;';   // Left Single Quotation Mark
		$trans[chr(146)] = '&rsquo;';   // Right Single Quotation Mark
		$trans[chr(147)] = '&ldquo;';   // Left Double Quotation Mark
		$trans[chr(148)] = '&rdquo;';   // Right Double Quotation Mark
		$trans[chr(149)] = '&bull;';    // Bullet
		$trans[chr(150)] = '&ndash;';   // En Dash
		$trans[chr(151)] = '&mdash;';   // Em Dash
		$trans[chr(152)] = '&tilde;';   // Small Tilde
		$trans[chr(153)] = '&trade;';   // Trade Mark Sign
		$trans[chr(154)] = '&scaron;';  // Latin Small Letter S With Caron
		$trans[chr(155)] = '&rsaquo;';  // Single Right-Pointing Angle Quotation Mark
		$trans[chr(156)] = '&oelig;';   // Latin Small Ligature OE
		$trans[chr(159)] = '&Yuml;';    // Latin Capital Letter Y With Diaeresis
		$trans['euro'] = '&euro;';      // euro currency symbol
		ksort($trans);

		foreach ($trans as $k => $v)
		{
			$text = str_replace($v, $k, $text);
		}

		// 3) remove <p>, <br/> ...
		$text = strip_tags($text);

		// 4) &amp; => & &quot; => '
		$text = html_entity_decode($text);

		// 5) remove Windows-1252 symbols like "TradeMark", "Euro"...
		$text = preg_replace('/[^(\x20-\x7F)]*/','', $text);

		$targets=array('\r\n','\n','\r','\t');
		$results=array(" "," "," ","");
		$text = str_replace($targets,$results,$text);

		//XML compatible
		/*
		$text = str_replace("&", "and", $text);
		$text = str_replace("<", ".", $text);
		$text = str_replace(">", ".", $text);
		$text = str_replace("\\", "-", $text);
		$text = str_replace("/", "-", $text);
		*/

		return ($text);
	}

	/**
	 * @fn     globalCounter()
	 *
	 * @brief  Update the global counter file for footer.php
	 *
	 * @return int
	 */
	public function globalCounter()
	{
		$page_hit_count = 0;

		// Defined in cct_init.php
		// $_SESSION['COUNTER_FILE']      = '/opt/ibmtools/cct7/etc/cct_page_counts';

		$page_hit_file = "/opt/ibmtools/cct7/etc/cct_page_counts";

		if (file_exists($page_hit_file))
		{
			if (($fp = fopen($page_hit_file, "r")) === false)
			{
				/**
				$trace = debug_backtrace();
				trigger_error(
					'Cannot open file for read: ' . $page_hit_file .
					' in ' . $trace[0]['file'] .
					' on line ' . $trace[0]['line'],
					E_USER_NOTICE);
				*/

				$this->error = sprintf("Cannot open file for read: %s", $page_hit_file);

				return 0;
			}

			$count = fread($fp, 80);
			$count += 1;
			fclose($fp);
			$page_hit_count = $count;
		}
		else
		{
			$page_hit_count = 1;
			$count = 1;
		}

		if (($fp = fopen($page_hit_file, "w")) === false)
		{
			/**
			$trace = debug_backtrace();
			trigger_error(
				'Cannot open file for write: ' . $page_hit_file .
				' in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line'],
				E_USER_NOTICE);
			*/

			$this->error = sprintf("Cannot open file for write: %s", $page_hit_file);

			return false;
		}

		fprintf($fp, "%d\n", $count);
		fclose($fp);

		return $page_hit_count;
	}

	public function updateAllStatuses($ora, $ticket_no)
	{
		$lib = new library();
		$lib->debug_start('update_all_statuses.html');

		if (PHP_SAPI === 'cli')
		{
			$user_cuid = 'gparkin';
			$user_name = 'Greg Parkin';
		}
		else
		{
			$user_cuid = isset($_SESSION['user_cuid']) ? $_SESSION['user_cuid'] : '';
			$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
		}

		//
		// Work your way from the bottom up?
		//
		$query  = "select  ";
		$query .= "  s.system_id               as system_id, ";
		$query .= "  s.system_hostname         as system_hostname, ";
		$query .= "  c.contact_netpin_no       as contact_netpin_no, ";
		$query .= "  t.status                  as ticket_status, ";
		$query .= "  s.system_work_status      as system_status, ";
		$query .= "  c.contact_response_status as contact_status ";
		$query .= "from ";
		$query .= "  cct7_tickets t, ";
		$query .= "  cct7_systems s, ";
		$query .= "  cct7_contacts c ";
		$query .= "where ";
		$query .= sprintf("  t.ticket_no = '%s' and ", $ticket_no);
		$query .= "  s.ticket_no = t.ticket_no and ";
		$query .= "  c.system_id = s.system_id ";
		$query .= "order by ";
		$query .= "  s.system_id, c.contact_netpin_no";

		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

		// CCT700048215	1041274	12350409	ACTIVE	WAITING	lxodtv004	REJECTED	17340
		// CCT700048215	1041274	12350408	ACTIVE	WAITING	lxodtv004	WAITING	    51190
		// CCT700048215	1041274	12350412	ACTIVE	WAITING	lxodtv004	WAITING	    7581
		// CCT700048215	1041274	12350411	ACTIVE	WAITING	lxodtv004	WAITING	    7581
		// CCT700048215	1041274	12350414	ACTIVE	WAITING	lxodtv004	WAITING	    7581
		// CCT700048215	1041274	12350413	ACTIVE	WAITING	lxodtv004	WAITING  	7581
		// CCT700048215	1041274	12350410	ACTIVE	WAITING	lxodtv004	WAITING	    SUB257

		if ($ora->sql2($query) == false)
		{
			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$this->error =
				sprintf("%s %s %d: %s - %s",
						__FILE__, __FUNCTION__, __LINE__,
						$ora->sql_statement, $ora->dbErrMsg);
		}

		$servers = array();

		$last_netpin_no          = "";

		$total_servers_scheduled = 0;
		$total_servers_waiting   = 0;
		$total_servers_approved  = 0;
		$total_servers_rejected  = 0;

		while ($ora->fetch())
		{
			$system_id = $ora->system_id;

			if (array_key_exists($system_id, $servers) == false)
			{
				$p = new data_node();

				$p->system_id         = $ora->system_id;
				$p->system_hostname   = $ora->system_hostname;
				$p->contact_netpin_no = $ora->contact_netpin_no;
				$p->ticket_status     = $ora->ticket_status;

				// Assign contact_status to system_status
				$p->system_status     = $ora->contact_status;

				// *** $contact_status ***
				// cct7_contacts.contact_response_status = APPROVED
				// cct7_contacts.contact_response_status = CANCELED
				// cct7_contacts.contact_response_status = EXEMPTED
				// cct7_contacts.contact_response_status = REJECTED
				// cct7_contacts.contact_response_status = WAITING

				//
				// 1 APPROVED
				// 2 EXEMPTED
				// 3 WAITING
				// 4 REJECTED
				// 5 CANCELED
				//
				switch ( $ora->contact_status )
				{

					case "APPROVED":
						$p->system_status_weight = 1;
						break;
					case "EXEMPTED":
						$p->system_status_weight = 2;
						break;
					case "WAITING":
						$p->system_status_weight = 3;
						break;
					case "REJECTED":
						$p->system_status_weight = 4;
						break;
					case "CANCELED":
						$p->system_status_weight = 5;
						break;
					default:
						$p->system_status_weight = 0;
						break;
				}

				if ($ora->contact_status == "WAITING")
				{
					$p->total_contacts_not_responded = 1;
				}
				else
				{
					$p->total_contacts_responded = 1;
				}

				$last_netpin_no = $ora->contact_netpin_no;

				$total_servers_scheduled += 1;
				$servers[$system_id] = $p;

				continue;
			}

			$p = $servers[$system_id];

			if ($last_netpin_no != $ora->contact_netpin_no)
			{
				if ($ora->contact_status == "WAITING")
				{
					$p->total_contacts_not_responded += 1;
				}
				else
				{
					$p->total_contacts_responded += 1;
				}

				$last_netpin_no = $ora->contact_netpin_no;
			}

			// *** $system_status ***
			// cct7_systems.system_work_status = APPROVED
			// cct7_systems.system_work_status = CANCELED
			// cct7_systems.system_work_status = FAILED
			// cct7_systems.system_work_status = REJECTED
			// cct7_systems.system_work_status = STARTING
			// cct7_systems.system_work_status = SUCCESS
			// cct7_systems.system_work_status = WAITING

			// *** $contact_status ***
			// cct7_contacts.contact_response_status = APPROVED
			// cct7_contacts.contact_response_status = CANCELED
			// cct7_contacts.contact_response_status = EXEMPTED
			// cct7_contacts.contact_response_status = REJECTED
			// cct7_contacts.contact_response_status = WAITING

			//
			// 1 APPROVED
			// 2 EXEMPTED
			// 3 WAITING
			// 4 REJECTED
			// 5 CANCELED
			//
			switch ( $ora->contact_status )
			{
				case "APPROVED":
					$system_status_weight = 1;
					break;
				case "EXEMPTED":
					$system_status_weight = 2;
					break;
				case "WAITING":
					$system_status_weight = 3;
					break;
				case "REJECTED":
					$system_status_weight = 4;
					break;
				case "CANCELED":
					$system_status_weight = 5;
					break;
				default:
					$system_status_weight = 0;
					break;
			}

			if ($system_status_weight > $p->system_status_weight)
			{
				$p->system_status        = $ora->contact_status;
				$p->system_status_weight = $system_status_weight;
			}

			$servers[$system_id] = $p;
		}

		//
		// Update all the cct7_system records that match $p->system_id
		//
		foreach ($servers as $system_id => $p)
		{
			// cct7_systems.system_work_status = APPROVED
			// cct7_systems.system_work_status = CANCELED
			// cct7_systems.system_work_status = FAILED
			// cct7_systems.system_work_status = REJECTED
			// cct7_systems.system_work_status = STARTING
			// cct7_systems.system_work_status = SUCCESS
			// cct7_systems.system_work_status = WAITING

			//
			// Add up these totals
			//
			if ($p->system_status == "WAITING")
			{
				$total_servers_waiting += 1;
			}
			else if ($p->system_status == "APPROVED")
			{
				$total_servers_approved += 1;
			}
			else // REJECTED and CANCEL
			{
				$total_servers_rejected += 1;
			}

			$query  = "update cct7_systems set ";
			$query .= sprintf("  system_update_date = %d, ",          $this->now_to_gmt_utime());
			$query .= sprintf("  system_update_cuid = '%s', ",        $user_cuid);
			$query .= sprintf("  system_update_name = '%s', ",        $user_name);
			$query .= sprintf("  system_work_status = '%s', ",        $p->system_status);
			$query .= sprintf("  total_contacts_responded = %d, ",    $p->total_contacts_responded);
			$query .= sprintf("  total_contacts_not_responded = %d ", $p->total_contacts_not_responded);
			$query .= "where ";
			$query .= sprintf("  system_id = %d", $system_id);

			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

			if ($ora->sql2($query) == false)
			{
				$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
				$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
				$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
									   $ora->sql_statement, $ora->dbErrMsg);
				return false;
			}
		}

		//
		// Now update the cct7_tickets record with our new counts.
		//
		$query  =               "update cct7_tickets set ";
		$query .= sprintf("  update_date = %d, ",             $this->now_to_gmt_utime());
		$query .= sprintf("  update_cuid = '%s', ",           $user_cuid);
		$query .= sprintf("  update_name = '%s', ",           $user_name);
		$query .= sprintf("  total_servers_scheduled = %d, ", $total_servers_scheduled);
		$query .= sprintf("  total_servers_waiting = %d, ",   $total_servers_waiting);
		$query .= sprintf("  total_servers_approved = %d, ",  $total_servers_approved);
		$query .= sprintf("  total_servers_rejected = %d ",   $total_servers_rejected);
		$query .= sprintf("where ticket_no = '%s'",           $ticket_no);

		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

		if ($ora->sql2($query) == false)
		{
			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $ora->sql_statement, $ora->dbErrMsg);
			return false;
		}

		return true;
	}

	/**
	 * @fn    updateAllStatuses($ora, $ticket_no)
	 *
	 * @brief Update cct7_systems and cct7_ticket status and total counts for records identified with this ticket.
	 *
	 * @param object $ora       - Object pointing to Oracle connection.
	 * @param string $ticket_no - CCT7 Ticket No.
	 *
	 * @return bool
	 */
	public function updateAllStatusesBAK1($ora, $ticket_no)
	{
		$lib = new library();
		$lib->debug_start('update_all_statuses.html');

		/**
		 * //
		 * // Execute stored procedure. (See: ibmtools_cct7/Procedures/updatestatus.sql)
		 * //
		 * $query = sprintf("BEGIN updateStatus('%s'); END;", $ticket_no);
		 *
		 * if ($ora->sql2($query) == false)
		 * {
		 * $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
		 * $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
		 * $this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
		 * $ora->sql_statement, $ora->dbErrMsg);
		 * return false;
		 * }
		 *
		 * return true;
		 */
		/*
		select
		  c.system_id                as system_id,
		  c.contact_id               as contact_id,
		  c.contact_response_status  as contact_response_status
		from
		  cct7_systems s,
		  cct7_contacts c
		where
		  s.ticket_no = 'CCT700049426' and
		  c.system_id = s.system_id
		order by
		  c.system_id, c.contact_netpin_no;
		*/

		//
		// FYI- If you run this from your laptop against your CCT schema in database ORCL it
		//      will not reflect the data in CCT schema in database CMPTOOLP on lxomp47x.
		//
		$query = "select ";
		$query .= "  s.system_id                as system_id, ";
		$query .= "  s.system_work_status       as system_work_status, ";
		$query .= "  c.contact_id               as contact_id,";
		$query .= "  c.contact_response_status  as contact_response_status ";
		$query .= "from ";
		$query .= "  cct7_systems s, ";
		$query .= "  cct7_contacts c ";
		$query .= "where ";
		$query .= "  s.ticket_no = '" . $ticket_no . "' and ";
		$query .= "  c.system_id = s.system_id ";
		$query .= "order by ";
		$query .= "  c.system_id, c.contact_netpin_no";

		if ($ora->sql2($query) == false)
		{
			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $ora->sql_statement, $ora->dbErrMsg);

			return false;
		}

		$last_system_id    = 0;
		$system_id         = 0;
		$system_status     = "";
		$new_system_status = "";

		$new_system_status_list = array();

		while ($ora->fetch())
		{
			$system_id               = $ora->system_id;
			$contact_id              = $ora->contact_id;
			$contact_response_status = $ora->contact_response_status;

			if ($system_id == 1041274)
			{
				$lib->debug1(__FILE__, __FUNCTION__, __LINE__,
					"system_id = %d, contact_id = %d, contact_responst_status = %s",
					$system_id, $contact_id, $contact_response_status);
			}

			if ($last_system_id != $system_id)
			{
				$lib->debug1(__FILE__, __FUNCTION__, __LINE__,
					"new_system_status > 0 %s != %s", $system_status, $new_system_status);

				if (strlen($new_system_status) > 0 && $system_status != $new_system_status)
				{
					$lib->debug1(__FILE__, __FUNCTION__, __LINE__,
						"new_system_status_list[%d] = %s", $system_id, $new_system_status);

					$new_system_status_list[$system_id] = $new_system_status;
				}

				$last_system_id    = $system_id;
				$system_status     = $ora->system_work_status;
				$new_system_status = "";
			}

			// cct7_systems.system_work_status = APPROVED
			// cct7_systems.system_work_status = CANCELED
			// cct7_systems.system_work_status = FAILED
			// cct7_systems.system_work_status = REJECTED
			// cct7_systems.system_work_status = STARTING
			// cct7_systems.system_work_status = SUCCESS
			// cct7_systems.system_work_status = WAITING

			// cct7_contacts.contact_response_status = APPROVED
			// cct7_contacts.contact_response_status = CANCELED
			// cct7_contacts.contact_response_status = EXEMPTED
			// cct7_contacts.contact_response_status = REJECTED
			// cct7_contacts.contact_response_status = WAITING

			switch ($contact_response_status)
			{
				case "APPROVED":
					if ($system_status != "REJECTED" && $system_status != "CANCELED")
					{
						$new_system_status = "APPROVED";
					}
					break;
				case "CANCELED": // CANCELED overrides REJECTED
					$new_system_status = "CANCELED";
					break;
				case "EXEMPTED":
					if ($system_status != "REJECTED" && $system_status != "CANCELED")
					{
						$new_system_status = "APPROVED"; // Same as APPROVED
					}
						break;
				case "REJECTED":
					if ($system_status != "CANCELED")
					{
						$new_system_status = "REJECTED";
					}
					break;
				default:
					break;
			}
		}

		$lib->debug1(__FILE__, __FUNCTION__, __LINE__,
					 "new_system_status > 0 %s != %s", $system_status, $new_system_status);

		if (strlen($new_system_status) > 0 && $system_status != $new_system_status)
		{
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__,
						 "new_system_status_list[%d] = %s", $system_id, $new_system_status);

			$new_system_status_list[$system_id] = $new_system_status;
		}

		if (PHP_SAPI === 'cli')
		{
			$user_cuid = 'gparkin';
			$user_name = 'Greg Parkin';
		}
		else
		{
			$user_cuid = isset($_SESSION['user_cuid']) ? $_SESSION['user_cuid'] : '';
			$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
		}

		//
		// Compute the total_servers_xxxx values for this ticket.
		//
		$total_servers_scheduled = 0;  // cct7_tickets.total_servers_scheduled
		$total_servers_waiting   = 0;  // cct7_tickets.total_servers_waiting
		$total_servers_approved  = 0;  // cct7_tickets.total_servers_approved
		$total_servers_rejected  = 0;  // cct7_tickets.total_servers_rejected

		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "new_systems_list_list:");
		$lib->debug_r1(__FILE__, __FUNCTION__, __LINE__, $new_system_status_list);

		foreach ($new_system_status_list as $system_id => $new_status)
		{
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %d, new_status = %s",
				$system_id, $new_status);

			$total_servers_scheduled += 1;

			// APPROVED, REJECTED, WAITING, EXEMPTED, CANCELED

			switch ($new_status)
			{
				case "APPROVED":
					$total_servers_approved += 1;
					break;
				case "REJECTED":
					$total_servers_rejected += 1;
					break;
				case "WAITING":
					$total_servers_waiting += 1;
					break;
				case "EXEMPTED":
					$total_servers_approved += 1;
					break;
				case "CANCELED":
					$total_servers_rejected += 1;
					break;
				default:
					$total_servers_waiting += 1;
					break;
			}
		}

		//
		// We should now have our totals for the cct7_tickets record and the
		// new system status value for cct7_systems.
		//
		$rc = $ora
			->update("cct7_tickets")
			->set("int", "update_date", $this->now_to_gmt_utime())
			->set("char", "update_cuid", $user_cuid)
			->set("char", "update_name", $user_name)
			->set("int", "total_servers_scheduled", $total_servers_scheduled)
			->set("int", "total_servers_waiting", $total_servers_waiting)
			->set("int", "total_servers_approved", $total_servers_approved)
			->set("int", "total_servers_rejected", $total_servers_rejected)
			->where("char", "ticket_no", "=", $ticket_no)
			->execute();

		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);

		if ($rc == false)
		{
			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $ora->sql_statement, $ora->dbErrMsg);

			return false;
		}

		$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "Next leg");

		//
		// $new_system_status_list[$ora->system_id] = $new_system_status;
		//
		foreach ($new_system_status_list as $system_id => $new_status)
		{
			$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "system_id = %d, new_status = %s",
						 $system_id, $new_status);

			//
			// This works as long as all the server netpin contacts (that are the same) have
			// the exact contact_response_status. This should always be the case!
			//
			$query  = "select distinct ";
			$query .= "  contact_netpin_no, ";
			$query .= "  contact_response_status ";
			$query .= "from ";
			$query .= "  cct7_contacts ";
			$query .= "where ";
			$query .= "  system_id = " . $system_id;

			if ($ora->sql2($query) == false)
			{
				$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
				$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
				$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
									   $ora->sql_statement, $ora->dbErrMsg);
				$ora->rollback();

				return false;
			}

			$total_contacts_responded     = 0;
			$total_contacts_not_responded = 0;

			while ($ora->fetch())
			{
				$response_status = $ora->contact_response_status;

				switch ( $response_status )
				{
					case "APPROVED":
						$total_contacts_responded += 1;
						break;
					case "EXEMPTED":
						$total_contacts_responded += 1;
						break;
					case "REJECTED":
						$total_contacts_responded += 1;
						break;
					case "WAITING":
						$total_contacts_not_responded += 1;
						break;
					case "CANCELED":
						$total_contacts_responded += 1;
						break;
					default:
						$total_contacts_not_responded += 1;
						break;
				}
			}

			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__,
							 "total_contacts_responded = %d, total_contacts_not_responded = %d",
							 $total_contacts_responded, $total_contacts_not_responded);

			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__,
							 "Ready to update cct7_systems system_id=%d, new_status=%s",
							 $system_id,$new_status);

			$query  = "update cct7_systems set ";
			$query .= sprintf("  system_update_date = %d, ", $this->now_to_gmt_utime());
			$query .= sprintf("  system_update_cuid = '%s', ", $user_cuid);
			$query .= sprintf("  system_update_name = '%s', ", $user_name);
			$query .= sprintf("  system_work_status = '%s', ", $new_status);
			$query .= sprintf("  total_contacts_responded = %d, ", $total_contacts_responded);
			$query .= sprintf("  total_contacts_not_responded = %d ", $total_contacts_not_responded);
			$query .= "where ";
			$query .= sprintf("  system_id = %d", $system_id);

			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $query);

			if ($ora->sql2($query) == false)
			{
				$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
				$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
				$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
									   $ora->sql_statement, $ora->dbErrMsg);
				return false;
			}

			/**
			$rc = $ora
				->update('cct7_systems')
				->set("int",  "system_update_date",           $this->now_to_gmt_utime())
				->set("char", "system_update_cuid",           $user_cuid)
				->set("char", "system_update_name",           $user_name)
				->set("char", "system_work_status",           $new_status)
				->set("int",  "total_contacts_responded",     $total_contacts_responded)
				->set("int",  "total_contacts_not_responded", $total_contacts_not_responded)
				->where("int", "system_id", "=", $system_id)
				->execute();

			$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);

			if ($rc == false)
			{
				$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
				$lib->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
				$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
									   $ora->sql_statement, $ora->dbErrMsg);
				$ora->rollback();

				return false;
			}
			*/
		}

		$lib->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "Were all done!");

		$ora->commit();
/**
		//
		// Last thing we want to do is recompute the schedule_start_date and schedule_end_date in the
		// cct7_tickets.
		//
		$query = "select ";
		$query .= "  system_work_start_date, ";
		$query .= "  system_work_end_date ";
		$query .= "from ";
		$query .= "  cct7_systems s ";
		$query .= "where ";
		$query .= "  ticket_no = '" . $ticket_no . "' ";
		$query .= "order by ";
		$query .= "  system_work_start_date";

		if ($ora->sql2($query) == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $ora->sql_statement, $ora->dbErrMsg);

			return false;
		}

		$schedule_start_date = 0;
		$schedule_end_date   = 0;

		while ($ora->fetch())
		{
			if ($schedule_start_date == 0 || $ora->system_work_start_date < $schedule_start_date)
			{
				$schedule_start_date = $ora->system_work_start_date;
			}

			if ($schedule_end_date == 0 || $ora->system_work_end_date > $schedule_end_date)
			{
				$schedule_end_date = $ora->system_work_end_date;
			}
		}

		$rc = $ora
			->update("cct7_tickets")
			->set("int", "update_date", $this->now_to_gmt_utime())
			->set("char", "update_cuid", $user_cuid)
			->set("char", "update_name", $user_name)
			->set("int",  "schedule_start_date", $schedule_start_date)
			->set("int",  "schedule_end_date", $schedule_end_date)
			->where("char", "ticket_no", "=", $ticket_no)
			->execute();

		if ($rc == false)
		{
			$this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
			$this->error = sprintf("%s %s %d: %s - %s", __FILE__, __FUNCTION__, __LINE__,
								   $ora->sql_statement, $ora->dbErrMsg);

			return false;
		}
*/

		return true;
	}

	public function yesterday(&$start_utime, &$end_utime, &$start_string, &$end_string)
	{
		$dt = new DateTime("now");
		$dt->setTimezone(new DateTimeZone('GMT'));

		$start_utime = strtotime('-1 day', strtotime($dt->format('m/d/Y 00:00:00')));
		$end_utime   = strtotime('-1 day', strtotime($dt->format('m/d/Y 23:59:59')));

		$start_string = date ( 'm/d/Y H:i:s' , $start_utime );
		$end_string = date ( 'm/d/Y H:i:s' , $end_utime );
	}

	public function today(&$start_utime, &$end_utime, &$start_string, &$end_string)
	{
		$dt = new DateTime("now");

		$start = $dt->format('m/d/Y 00:00:00');
		$end   = $dt->format('m/d/Y 23:59:59');

		$dt1 = new DateTime($start);
		$dt1->setTimezone(new DateTimeZone('GMT'));

		$dt2 = new DateTime($end);
		$dt2->setTimezone(new DateTimeZone('GMT'));

		$start_utime  = $dt1->format('U');
		$end_utime    = $dt2->format('U');

		$start_string = $dt1->format('m/d/Y H:i:s');
		$end_string   = $dt2->format('m/d/Y H:i:s');
	}

	public function tomorrow(&$start_utime, &$end_utime, &$start_string, &$end_string)
	{
		$dt = new DateTime("now");
		$dt->setTimezone(new DateTimeZone('GMT'));

		$start_utime  = strtotime('+1 day', strtotime($dt->format('m/d/Y 00:00:00')));
		$end_utime    = strtotime('+1 day', strtotime($dt->format('m/d/Y 23:59:59')));

		$start_string = date ( 'm/d/Y H:i:s' , $start_utime );
		$end_string   = date ( 'm/d/Y H:i:s' , $end_utime );
	}

	public function now_to_gmt_utime()
	{
		$dt = new DateTime();

		if (strlen($this->user_timezone_name) == 0)
		{
			if (session_id() == '')
				session_start(); // Required to start once in order to retrieve user session information

			if (isset($_SESSION['local_timezone_name']))
			{
				$this->user_timezone_name = $_SESSION['local_timezone_name'];
			}
			else
			{
				$this->user_timezone_name = 'America/Denver';
			}
		}
		else
		{
			$dt->setTimezone(new DateTimeZone($this->user_timezone_name));
		}

		$dt->setTimestamp(time());

		return $dt->format('U');
	}

	/**
	 * @fn     to_gmt($datetime_string, $from_tz)
	 *
	 * @brief  Takes a date string (ex. 07/25/2016 23:00) and converts it from it's TZ to GMT and returns utime.
	 * @brief  This function is used to store server work start/end in the database as numbers utime so that who
	 *         ever views the record in their browser can see the start/end in the time zone they are located in.
	 *
	 * @param  $datetime_string - '07/25/2016 23:00' or '07/25/2016' or etc.
	 * @param  $from_tz         - Originating time zone for this $datatime_string (ex. 'America/Chicago')
	 *
	 * @return string           - Returns GMT utime numeric value.
	 */
	public function to_gmt($datetime_string, $from_tz="America/Denver")
	{
		//
		// The trick to remember here is that you have to set $dt with a starting TZ. That way when
		// you set the new time zone to GMT it will give you the correct utime.
		// $mmddyyyy_hhmm can also accept '07/25/2016' without the time part (defaults to 00:00).
		//
		$dt = new DateTime($datetime_string, new DateTimeZone($from_tz));
		$dt->setTimezone(new DateTimeZone('GMT'));

		return $dt->format('U');
	}

	/**
	 * @fn     gmt_to_format($gmt_utime, $time_format, $to_tz)
	 *
	 * @brief  This function takes a $gmt_utime value, convert's to $to_tz and displays a string in $time_format.
	 *
	 * @param  int    $gmt_utime    - Database stored utime value.
	 * @param  string $time_format  - 'm/d/Y' or 'm/d/Y H:i', etc.
	 * @param  string $to_tz        - User's local timezone: 'America/Denver'
	 *
	 * @return string        - Returns the formatted date time string.
	 */
	public function gmt_to_format($gmt_utime, $time_format, $to_tz="America/Denver")
	{
		$dt = new DateTime();
		$dt->setTimezone(new DateTimeZone('GMT'));
		$dt->setTimestamp($gmt_utime);
		$dt->setTimezone(new DateTimeZone($to_tz));

		return $dt->format($time_format);
	}

	/**
	 * @fn     canClassBeAutoloaded($class_name)
	 *
	 *  @brief Determines if a class can be loaded
	 *
	 *  @param object $class_name is the name of the class
	 *
	 *  @return bool - true if class can be loaded, false if it cannot
	 */
	public function canClassBeAutoloaded($class_name)
	{
		return class_exists($class_name);
	}

	/**
	 * @fn    timeStart()
	 *
	 * @brief Used to time routines in PHP. Record start time.
	 */
	public function timeStart()
	{
		$this->time_start = microtime(true);
		$this->time_end = 0;
	}

	/**
	 * @fn    timeEnd()
	 *
	 * @brief Used to time routines in PHP. Record end time and calculate run_time (seconds).
	 */
	public function timeEnd()
	{
		if ($this->time_start > 0)
		{
			$this->time_end = microtime(true);
			$this->run_time = $this->time_end - $this->time_start;
		}

		$this->time_start = 0;
		$this->time_end = 0;
	}

	/**
	 * @fn   runTime()
	 *
	 * @brief Used to time routines in PHP. Return the run time in seconds.
	 */
	public function runTime()
	{
		return $this->run_time;
	}

	/**
	 * @fn    debug_to_console( $data )
	 *
	 * @brief Write $data to a Javascript console for debugging purposes. ( Don't user in AJAX server! )
	 *
	 * @param string $data can be a variable or array.
	 */
	function debug_to_console( $data )
	{

		if ( is_array( $data ) )
			$output = "<script>console.log( 'Debug Objects: " . implode( ',', $data) . "' );</script>";
		else
			$output = "<script>console.log( 'Debug Objects: " . $data . "' );</script>";

		echo $output;
	}

	/** @fn makeInsertCHAR($query, $value, $add_comma)
	 *  @brief SQL insert string builder for VARCHAR2 or CHAR.
	 *  @brief Example: $obj->makeInsertCHAR($insert, $ticket_no, true);
	 *  @param $query This is the string where we append the value to the SQL insert command.
	 *  @param $value This is the VARCHAR2 or CHAR value we are inserting.
	 *  @param $add_comma true if a comma is needed at the end or false if it isn't needed.
	 *  @return null
	 */
	public function makeInsertCHAR(&$query, $value, $add_comma)
	{
		if ($add_comma == true)
		{
			$query .= sprintf("'%s', ", $this->FixString($value));
		}
		else
		{
			$query .= sprintf("'%s' ",  $this->FixString($value));
		}
	}

	/** @fn makeInsertINT($query, $value, $add_comma)
	 *  @brief SQL insert string builder for NUMBER or INT values.
	 *  @brief Example: $obj->makeInsertINT($insert, $system_id, true);
	 *  @param $query This is the string where we append the value to the SQL insert command.
	 *  @param $value This is the NUMBER or INT value we are inserting.
	 *  @param $add_comma true if a comma is needed at the end or false if it isn't needed.
	 *  @return null
	 */
	public function makeInsertINT(&$query, $value, $add_comma)
	{
		if ($add_comma == true)
		{
			$query .= sprintf("%d, ", $value);
		}
		else
		{
			$query .= sprintf("%d ", $value);
		}
	}

	/** @fn makeInsertDateTIME($query, $value, $add_comma)
	 *  @brief SQL insert string builder for DATE.
	 *  @brief Example: $obj->makeInsertDateTIME($insert, $cm_start_date, true);
	 *  @param $query This is the string where we append the value to the SQL insert command.
	 *  @param $value This is the charachter string representing the date and time.
	 *  @param $add_comma true if a comma is needed at the end or false if it isn't needed.
	 *  @return null
	 */
	public function makeInsertDateTIME(&$query, $value, $add_comma)
	{
		if ($add_comma == true)
		{
			$query .= sprintf("to_date('%s', 'MM/DD/YYYY HH24:MI'), ", $value);
		}
		else
		{
			$query .= sprintf("to_date('%s', 'MM/DD/YYYY HH24:MI') ", $value);
		}
	}

	/** @fn makeInsertDATE($query, $value, $add_comma)
	 *  @brief SQL insert string builder for DATE.
	 *  @brief Example: $obj->makeInsertDATE($insert, $cm_closed_date, true);
	 *  @param $query This is the string where we append the value to the SQL insert command.
	 *  @param $value This is the charachter string representing the date.
	 *  @param $add_comma true if a comma is needed at the end or false if it isn't needed.
	 *  @return null
	 */
	public function makeInsertDATE(&$query, $value, $add_comma)
	{
		if ($add_comma == true)
		{
			$query .= sprintf("to_date('%s', 'MM/DD/YYYY'), ", $value);
		}
		else
		{
			$query .= sprintf("to_date('%s', 'MM/DD/YYYY') ", $value);
		}
	}

	/** @fn makeInsertDateNOW($query, $add_comma)
	 *  @brief SQL insert string builder for DATE where set the date to SYSDATE or now.
	 *  @brief Example: $obj->makeInsertDateNOW($insert, true);
	 *  @param $query This is the string where we append the value to the SQL insert command.
	 *  @param $add_comma true if a comma is needed at the end or false if it isn't needed.
	 *  @return null
	 */
	public function makeInsertDateNOW(&$query, $add_comma)
	{
		if ($add_comma == true)
		{
			$query .= "SYSDATE, ";
		}
		else
		{
			$query .= "SYSDATE ";
		}
	}

	/** @fn makeUpdateCHAR($query, $fieldname, $value, $add_comma)
	 *  @brief SQL update string builder for VARCHAR2 or CHAR.
	 *  @brief Example: $obj->makeUpdateCHAR($insert, "cm_ticket_no", $ticket_no, true);
	 *  @param $query SQL update string builder for VARCHAR2 or CHAR values.
	 *  @param $fieldname is the name of the table column name.
	 *  @param $value is the string value we want to update.
	 *  @param $add_comma true if we want to include a comma after the statement or not.
	 *  @return null
	 */
	public function makeUpdateCHAR(&$query, $fieldname, $value, $add_comma)
	{
		if ($add_comma == true)
		{
			$query .= sprintf("%s='%s', ", $fieldname, $this->FixString($value));
		}
		else
		{
			$query .= sprintf("%s='%s' ",  $fieldname, $this->FixString($value));
		}
	}

	/** @fn makeUpdateINT($query, $fieldname, $value, $add_comma)
	 *  @brief SQL update string builder for NUMBER or INT.
	 *  @brief Example: $obj->makeUpdateINT($insert, "system_id", $system_id, true);
	 *  @param $query SQL update string builder for NUMBER or INT values.
	 *  @param $fieldname is the name of the table column name.
	 *  @param $value is the number value we want to update.
	 *  @param $add_comma true if we want to include a comma after the statement or not.
	 *  @return null
	 */
	public function makeUpdateINT(&$query, $fieldname, $value, $add_comma)
	{
		if ($add_comma == true)
		{
			$query .= sprintf("%s=%d, ", $fieldname, $value);
		}
		else
		{
			$query .= sprintf("%s=%d ", $fieldname, $value);
		}
	}

	/** @fn makeUpdateDateTIME($query, $fieldname, $value, $add_comma)
	 *  @brief SQL update string builder for DATE.
	 *  @brief Example: $obj->makeUpdateDateTIME($insert, "cm_start_date", $cm_start_date, true);
	 *  @param $query SQL update string builder for DATE values.
	 *  @param $fieldname is the name of the table column name.
	 *  @param $value is the date time string we want to update.
	 *  @param $add_comma true if we want to include a comma after the statement or not.
	 *  @return null
	 */
	public function makeUpdateDateTIME(&$query, $fieldname, $value, $add_comma)
	{
		if ($add_comma == true)
		{
			$query .= sprintf("%s=to_date('%s', 'MM/DD/YYYY HH24:MI'), ", $fieldname, $value);
		}
		else
		{
			$query .= sprintf("%s=to_date('%s', 'MM/DD/YYYY HH24:MI') ", $fieldname, $value);
		}
	}

	/** @fn makeUpdateDateHHMISS($query, $fieldname, $value, $add_comma)
	 *  @brief SQL update string builder for DATE.
	 *  @brief Example: $obj->makeUpdateDateHHMISS($insert, "cm_start_date", $cm_start_date, true);
	 *  @param $query SQL update string builder for DATE values.
	 *  @param $fieldname is the name of the table column name.
	 *  @param $value is the date time string we want to update.
	 *  @param $add_comma true if we want to include a comma after the statement or not.
	 *  @return null
	 */
	public function makeUpdateDateHHMISS(&$query, $fieldname, $value, $add_comma)
	{
		if ($add_comma == true)
		{
			$query .= sprintf("%s=to_date('%s', 'MM/DD/YYYY HH24:MI:SS'), ", $fieldname, $value);
		}
		else
		{
			$query .= sprintf("%s=to_date('%s', 'MM/DD/YYYY HH24:MI:SS') ", $fieldname, $value);
		}
	}

	/** @fn makeUpdateDateNOW($query, $fieldname, $add_comma)
	 *  @brief SQL update string builder for DATE.
	 *  @brief Example: $obj->makeUpdateDateNOW($insert, "cm_start_date", $cm_start_date, true);
	 *  @param $query SQL update string builder for DATE values.
	 *  @param $fieldname is the name of the table column name.
	 *  @param $add_comma true if we want to include a comma after the statement or not.
	 *  @return null
	 */
	public function makeUpdateDateNOW(&$query, $fieldname, $add_comma)
	{
		if ($add_comma == true)
		{
			$query .= sprintf("%s=SYSDATE, ", $fieldname);
		}
		else
		{
			$query .= sprintf("%s=SYSDATE ", $fieldname);
		}
	}

	/**
	 * @fn    fixDuration(&$duration)
	 *
	 * @brief Used to change Remedys formatted computed duration string from 0 : 4 : 59 to 00:04:59
	 *
	 * @param string $duration is the Remedy computed duration string we want to fix.
	 *
	 * @return true on success, false on failure.
	 */
	public function fixDuration(&$duration)
	{
		if (strlen($duration) == 0)
		{
			$this->debug5(__FILE__, __FUNCTION__, __LINE__, "duration is null");
			return false;
		}

		$arr = explode(":", $duration);

		if (count($arr) != 3)
		{
			$this->debug5(__FILE__, __FUNCTION__, __LINE__, "$arr count is not 3. It is %d", count($arr));
			return false;
		}

		$days = trim($arr[0]);
		$hours = trim($arr[1]);
		$minutes = trim($arr[2]);

		$duration = sprintf("%02d:%02d:%02d", $days, $hours, $minutes);
		return true;
	}

	/**
	 * @fn     FixString($receive)
	 *
	 * @brief  Escape any single quotes ' before inserting or updating in Oracle
	 *
	 * @param  string $receive is the string we want to escape single quotes.
	 *
	 * @return string
	 */
	public function FixString($receive)
	{
		$s = '';
		$str = str_split($receive);
		$len = count($str);

		for ($x=0; $x<$len; $x++)
		{
			//if ($str[$x] == '\'')
			//	$s .= '\'';

			if ($str[$x] == '%')
				$s .= '%';

			$s .= $str[$x];
		}

		return str_replace("'", "''", $s);
	}

	/**
	 * @fn    isValidEmail($email)
	 *
	 * @brief Checks to see if we hava a valid email address.
	 *
	 * @param string $email is the email address we want to check.
	 *
	 * @return bool true if the email address is okay, false if not.
	 */
	function isValidEmail($email)
	{
		return filter_var($email, FILTER_VALIDATE_EMAIL);  // Available in PHP >= 5.2.0
	}

	/**
	 * @fn    phone_clean($string)
	 *
	 * @brief Cleans up phone number strings and strips any phone number extensions.
	 *
	 * @param string $string This is the phone number string we want to fix up.
	 *
	 * @return string
	 */
	public function phone_clean($string)
	{
		$pattern = '/\D*\(?(\d{3})?\)?\D*(\d{3})\D*(\d{4})\D*(\d{1,8})?/';

		if (preg_match($pattern, $string, $match))
		{
			if (isset($match[3]) && $match[3])
			{
				if (isset($match[1]) && $match[1])
				{
					$num = $match[1] . '-' . $match[2] . '-' . $match[3];
				}
				else
				{
					$num = $match[2] . '-' . $match[3];
				}
			}
			else
			{
				$num = NULL;
			}
			if (isset($match[4]) && $match[4])
			{
				$ext = $match[4];
			}
			else
			{
				$ext = NULL;
			}
		}
		else
		{
			$num = NULL;
			$ext = NULL;
		}

		return $num;
	}

	/**
	 * @fn    rightPad($string)
	 *
	 * @brief Used to right pad HTML spaces in a listbox that is using the courier new font
	 *
	 * @param string $str is the string we want to pad.
	 * @param int    $len is the length of the string we want to pad spaces too.
	 *
	 * @return string
	 */
	public function rightPad($str, $len)
	{
		$rc = "";
		$l = strlen($str);

		while ($l < $len)
		{
			$rc = $rc . "&nbsp;";
			$l++;
		}

		$rc = $rc . $str;

		return $rc;
	}

	/**
	 * @fn    rightPad($string)
	 *
	 * @brief Used to left pad HTML spaces in a listbox that is using the courier new font
	 *
	 * @param string $str is the string we want to pad.
	 * @param int    $len is the length of the string we want to pad spaces too.
	 *
	 * @return string
	 */
	public function leftPad($str, $len)
	{
		$rc = $str;
		$l = strlen($str);

		while ($l < $len)
		{
			$rc = $rc . "&nbsp;";
			$l++;
		}

		return $rc;
	}

	/**
	 * @fn    remove_doublewhitespace($string)
	 *
	 * @brief Remove double white spaces from a string.
	 *
	 * @param string $s is the string you want to work on.
	 *
	 * @return a new string.
	 *
	 *  (.) capture any character
	 *  \1  if it is followed by itself
	 *  +   one or more
	 */
	public function remove_doublewhitespace($s = null)
	{
		return preg_replace('/([\s])\1+/', ' ', $s);
	}

	/**
	 * @fn    remove_whitespace($string)
	 *
	 * @brief Remove whitespace
	 *
	 * @param string $s - is the string you want to work on.
	 *
	 * @return string
	 */
	public function remove_whitespace($s = null)
	{
		return preg_replace('/[\s]+/', '', $s );
	}

	/**
	 * @fn    remove_whitespace_feed($string)
	 *
	 * @brief Remove whitespaces, tabs, new-line chars, and carriage-returns
	 *
	 * @param string $s - is the string you want to work on.
	 *
	 * @return string
	 */
	public function remove_whitespace_feed($s = null)
	{
		return preg_replace('/[\t\n\r\0\x0B]/', '', $s);
	}

	/**
	 * @fn    smart_clean($string)
	 *
	 * @brief Remove double while spaces and white space feed
	 *
	 * @param string $s - is the string you want to work on.
	 *
	 * @return string
	 *
	 *  Example:
	 *   $string = " Hey   yo, what's \t\n\tthe sc\r\nen\n\tario! \n";
	 *   echo smart_clean(string);
	 */
	public function smart_clean($s = null)
	{
		return trim( $this->remove_doublewhitespace( $this->remove_whitespace_feed($s) ) );
	}

	/**
	 * @fn    strip($string)
	 *
	 * @brief Used to left pad HTML spaces in a listbox that is using the courier new font
	 *
	 * @param string $s - is the string you want to work on.
	 *
	 * @return string
	 *
	 *  Example:
	 *   $str = "This is  a string       with
	 *   spaces, tabs and newlines present";
	 *   echo strip($str);
	 *   output: This is a string with spaces, tabs and newlines present
	 */
	public function strip($str = null)
	{
		return preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $str);
	}

	/**
	 * @fn substractDays($date, $days)
	 *
	 * @brief Substract x number of days from a given date
	 *
	 * @param string $date is the date string you want to substract days from
	 * @param int    $days is the number of days to substract
	 *
	 * @return string - the new date
	 */
	public function substractDays($date, $days)
	{
		$ndays = sprintf("-%d days", $days);
		$newdate = strtotime($ndays, strtotime($date));
		$newdate = date('m/d/Y H:i', $newdate);

		return $newdate;
	}

	/**
	 * @fn    addDays($date, $days)
	 *
	 * @brief Add x number of days to a given date
	 *
	 * @param string $date is the date string you want to add days to
	 * @param int    $days is the number of days to add
	 *
	 * @return string - the new date string
	 */
	public function addDays($date, $days)
	{
		$ndays = sprintf("+%d days", $days);
		$newdate = strtotime($ndays, strtotime($date));
		$newdate = date('m/d/Y H:i', $newdate);

		return $newdate;
	}

	/**
	 * @fn    startsWith($haystack, $needle)
	 *
	 * @brief Checks to see if the starting of a string (haystack) begins with the pattern (needle)
	 *
	 * @param string $haystack is the string to search
	 * @param string $needle is the pattern we are checking that we want to match
	 *
	 * @return int -1, 0, or 1, where 0 means match
	 */
	function startsWith($haystack, $needle)
	{
		return strncmp($haystack, $needle, strlen($needle)) == 0;
	}

	/**
	 * @fn    endsWith($haystack, $needle)
	 *
	 * @brief Check to see if the end of the string (haystack) matches the pattern string (needle)
	 *
	 * @param string $haystack is the the string to search
	 * @param string $needle is the pattern we are checking for at the end of the string
	 *
	 * @return int -1, 0, or 1, where 0 means match
	 */
	function endsWith($haystack, $needle)
	{
		return substr_compare($haystack, $needle, -strlen($needle)) == 0;
	}

	/**
	 * @fn    html_stop()
	 *
	 * @brief Output a Stop graphic, file, function, line number and message.
	 *
	 * @param string $file File name of calling function. __FILE__
	 * @param string $func Function name of calling module. __FUNCTION__
	 * @param int    $line Line number in File of calling function. __LINE__
	 * @param string $msg This is the error message
	 */
	public function html_stop($file, $func, $line, $msg)
	{
		$argv   = func_get_args();
		$file   = array_shift($argv);
		$func   = array_shift($argv);
		$line   = array_shift($argv);
		$format = array_shift($argv);
		$what   = vsprintf($format, $argv);

		printf("<html lang=\"en\">\n");
		printf("<head>\n");
		printf("<meta http-equiv=\"x-ua-compatible\" content=\"IE=EmulateIE10\">\n");
		printf("</head>\n");
		printf("<body>\n");
		printf("<p align=\"center\"><img border=\"0\" src=\"images/stop.gif\" width=\"75\" height=\"74\"></p>\n");

		// Some php code may not be in a function
		if (empty($func))
			printf("<p align=\"center\">%s %d: %s</p>\n", basename($file), $line, $what);
		else
			printf("<p align=\"center\">%s %s() %d: %s</p>\n", basename($file), $func, $line, $what);

		printf("</body>\n");
		printf("</html>\n");

		exit();
	}

	/**
	 * @fn    debug_start($debug_file)
	 *
	 * @brief Check session cache for debugging information. If on, file will be opened for writing $this->debugX()
	 *
	 * @param string $debug_file is the name of the debug file $this->debugX() will write to.
	 *
	 * @return bool - true or false
	 */
	public function debug_start($debug_file)
	{
		//
		// Debugging not available for scripting because there is no session cookies.
		//
		if (PHP_SAPI == 'cli')
			return false;

		if (session_id() == '')
			session_start();

		if (strlen(trim($debug_file)) == 0)
			$debug_file = 'debug.html';

		if (!isset($_SESSION['is_debug_on']) || $_SESSION['is_debug_on'] == 'N')
			return false;

		//$debug_level_set = false;

		if ($_SESSION['debug_level1'] == 'Y')
		{
			$this->debug_flag1 = true;
			$this->error_reporting(1);
			//$debug_level_set = true;
		}

		if ($_SESSION['debug_level2'] == 'Y')
		{
			$this->debug_flag2 = true;
			$this->error_reporting(2);
			//$debug_level_set = true;
		}

		if ($_SESSION['debug_level3'] == 'Y')
		{
			$this->debug_flag3 = true;
			$this->error_reporting(3);
			//$debug_level_set = true;
		}

		if ($_SESSION['debug_level4'] == 'Y')
		{
			$this->debug_flag4 = true;
			$this->error_reporting(4);
			//$debug_level_set = true;
		}

		if ($_SESSION['debug_level5'] == 'Y')
		{
			$this->debug_flag5 = true;
			$this->error_reporting(5);
			//$debug_level_set = true;
		}

		if (strlen(trim($debug_file)) > 0)
		{
			$debug_path = $_SESSION['debug_path'];

			$filename = $debug_path . "/" . $debug_file;

			$this->fp_debug = fopen($filename, $_SESSION['debug_mode']);

			if ($this->fp_debug == null || $this->fp_debug == false)
			{
				printf("<!-- Unable to open debug file: %s in mode: %s -->\n", $filename, $_SESSION['debug_mode']);
				return false;
			}

			fprintf($this->fp_debug, "==========================================================================================================================\n");

			$mode = "APPEND";

			if ($_SESSION['debug_mode'] == 'w')
			{
				$mode = "WRITE";
			}

			fprintf($this->fp_debug,
					"<html><body><pre>Debug file: %s/%s created on %s. Mode: %s.\n<br>\n",
					$debug_path, $debug_file, date("r", time()), $mode);
		}

		$this->debug_onoff = 1;
		return true;
	}

	//
	// There are 5 debug out messages types that you can use in your program.
	//	$obj->debug1(...);  and  $obj->debug_r1($array);
	//	$obj->debug2(...);  and  $obj->debug_r2($array);
	//	$obj->debug3(...);  and  $obj->debug_r3($array);
	//	$obj->debug4(...);  and  $obj->debug_r4($array);
	//	$obj->debug5(...);  and  $obj->debug_r5($array);
	//
	// You can control what debug messages (1-5) you want to display when you call $obj->on([...]);
	// Examples:
	//  $obj->debug_on();        Include all debug messages (1-5)
	//  $obj->debug_on(1,2,3);   Include debug messages for 1, 2, and 3
	//  $obj->debug_on(5);       Include debug messages for 5
	//

	/**
	 * @fn    debug_on()
	 *
	 * @brief Turn on debugging statement in the form of HTML comments found in the web page.
	 *
	 * @brief Example: $obj->debug_on();              Include all debug messages (1-5)
	 * @brief Example: $obj->debug_on(1,2,3);         Include debug messages for 1, 2, and 3
	 * @brief Example: $obj->debug_on(5);             Include debug messages for 5
	 * @brief Example: $obj->debug_on('output.txt');  Write to output file instead of creating <!-- comments -> lines.
	 */
	public function debug_on()
	{
		$argv = func_get_args();

		$debug_level_set = false;

		foreach($argv as $arg)
		{
			switch ( $arg )
			{
				case 1:
					$this->debug_flag1 = true;
					$this->error_reporting(1);
					$debug_level_set = true;
					break;
				case 2:
					$this->debug_flag2 = true;
					$this->error_reporting(2);
					$debug_level_set = true;
					break;
				case 3:
					$this->debug_flag3 = true;
					$this->error_reporting(3);
					$debug_level_set = true;
					break;
				case 4:
					$this->debug_flag4 = true;
					$this->error_reporting(4);
					$debug_level_set = true;
					break;
				case 5:
					$this->debug_flag5 = true;
					$this->error_reporting(5);
					$debug_level_set = true;
					break;
				default:
					if (is_string($arg))
					{
						switch ( $_SERVER['SERVER_ADDR'] )
						{
							case '151.117.157.53':  // lxomp11m
								$debug_path = '/xxx/cct7/debug';
								break;
							case '151.117.41.173':  // lxomt12m
								$debug_path = '/xxx/cct7/debug';
								break;
							default:                // vlodts022, LENOVO
								$debug_path = '/opt/ibmtools/cct7/debug';
								break;
						}
						$filename = $debug_path . "/" . $arg;

						$this->fp_debug = fopen($filename, 'w');

						if ($this->fp_debug == null)
						{
							printf("<!-- Unable to open debug file: %s -->\n", $filename);
						}
					}
					break;
			}
		}

		if ($debug_level_set == false)
		{
			$this->debug_flag1 = true;
			$this->error_reporting(1);
			$this->debug_flag2 = true;
			$this->error_reporting(2);
			$this->debug_flag3 = true;
			$this->error_reporting(3);
			$this->debug_flag4 = true;
			$this->error_reporting(4);
			$this->debug_flag5 = true;
			$this->error_reporting(5);
		}

		$this->debug_onoff = 1;
	}

	/**
	 * @fn    debug_off()
	 *
	 * @brief Turn off debugging statement in the form of HTML comments found in the web page.
	 */
	public function debug_off()
	{
		$this->debug_onoff = 0;
		$this->debug_flag1 = true;
		$this->debug_flag2 = true;
		$this->debug_flag3 = true;
		$this->debug_flag4 = true;
		$this->debug_flag5 = true;
		$this->error_reporting(0);

		if ($this->fp_debug != null)
		{
			fclose($this->fp_debug);
			$this->fp_debug = null;
		}
	}

	/**
	 * @fn    debug1()
	 *
	 * @brief Write debug_on(1) debugging comments to the HTML web page.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug1(__FILE__, __FUNCTION__, __LINE__, "Error code = %d", $errno);
	 */
	public function debug1()
	{
		if ($this->debug_onoff == 0 || $this->debug_flag1 == false)
			return;

		$argv = func_get_args();
		$file = array_shift($argv);
		$func = array_shift($argv);
		$line = array_shift($argv);

		$what = vsprintf(array_shift($argv), array_values($argv));

		// Some php code may not be in a function
		if (empty($func))
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "1: <font color=\"green\">%s</font>&nbsp;<font color=\"blue\">%d:</font> %s\n", basename($file), $line, $what);
			}
		}
		else
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "1: <font color=\"green\">%s</font>&nbsp;<font color=\"purple\">%s()</font>&nbsp;<font color=\"blue\">%d:</font> %s\n",
						basename($file), $func, $line, $what);
			}
		}
	}

	/**
	 * @fn    debug2()
	 *
	 * @brief Write debug_on(2) debugging comments to the HTML web page.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug1(__FILE__, __FUNCTION__, __LINE__, "Error code = %d", $errno);
	 */
	public function debug2()
	{
		if ($this->debug_onoff == 0 || $this->debug_flag2 == false)
			return;

		$argv = func_get_args();
		$file = array_shift($argv);
		$func = array_shift($argv);
		$line = array_shift($argv);

		$what = vsprintf(array_shift($argv), array_values($argv));

		// Some php code may not be in a function
		if (empty($func))
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "2: <font color=\"green\">%s</font>&nbsp;<font color=\"blue\">%d:</font> %s\n", basename($file), $line, $what);
			}
		}
		else
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "2: <font color=\"green\">%s</font>&nbsp;<font color=\"purple\">%s()</font>&nbsp;<font color=\"blue\">%d:</font> %s\n",
						basename($file), $func, $line, $what);
			}
		}
	}

	/**
	 * @fn    debug3()
	 *
	 * @brief Write debug_on(3) debugging comments to the HTML web page.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug1(__FILE__, __FUNCTION__, __LINE__, "Error code = %d", $errno);
	 */
	public function debug3()
	{
		if ($this->debug_onoff == 0 || $this->debug_flag3 == false)
			return;

		$argv = func_get_args();
		$file = array_shift($argv);
		$func = array_shift($argv);
		$line = array_shift($argv);

		$what = vsprintf(array_shift($argv), array_values($argv));

		// Some php code may not be in a function
		if (empty($func))
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "3: <font color=\"green\">%s</font>&nbsp;<font color=\"blue\">%d:</font> %s\n", basename($file), $line, $what);
			}
		}
		else
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "3: <font color=\"green\">%s</font>&nbsp;<font color=\"purple\">%s()</font>&nbsp;<font color=\"blue\">%d:</font> %s\n",
						basename($file), $func, $line, $what);
			}
		}
	}

	/**
	 * @fn    debug4()
	 *
	 * @brief Write debug_on(4) debugging comments to the HTML web page.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug1(__FILE__, __FUNCTION__, __LINE__, "Error code = %d", $errno);
	 */
	public function debug4()
	{
		if ($this->debug_onoff == 0 || $this->debug_flag4 == false)
			return;

		$argv = func_get_args();
		$file = array_shift($argv);
		$func = array_shift($argv);
		$line = array_shift($argv);

		$what = vsprintf(array_shift($argv), array_values($argv));

		// Some php code may not be in a function
		if (empty($func))
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "4: <font color=\"green\">%s</font>&nbsp;<font color=\"blue\">%d:</font> %s\n", basename($file), $line, $what);
			}
		}
		else
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "4: <font color=\"green\">%s</font>&nbsp;<font color=\"purple\">%s()</font>&nbsp;<font color=\"blue\">%d:</font> %s\n",
						basename($file), $func, $line, $what);
			}
		}
	}

	/**
	 * @fn    debug5()
	 *
	 * @brief Write debug_on(5) debugging comments to the HTML web page.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug1(__FILE__, __FUNCTION__, __LINE__, "Error code = %d", $errno);
	 */
	public function debug5()
	{
		if ($this->debug_onoff == 0 || $this->debug_flag5 == false)
			return;

		$argv = func_get_args();
		$file = array_shift($argv);
		$func = array_shift($argv);
		$line = array_shift($argv);

		$what = vsprintf(array_shift($argv), array_values($argv));

		// Some php code may not be in a function
		if (empty($func))
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "5: <font color=\"green\">%s</font> <font color=\"blue\">%d:</font> %s\n", basename($file), $line, $what);
			}
		}
		else
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "5: <font color=\"green\">%s</font>&nbsp;<font color=\"purple\">%s()</font>&nbsp;<font color=\"blue\">%d:</font> %s\n",
						basename($file), $func, $line, $what);
			}
		}
	}

	/**
	 * @fn    debug_sql1()
	 *
	 * @brief This works like all other debug1-5 functions except it is used when you want to format SQL statements in HTML.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug5(__FILE__, __FUNCTION__, __LINE__, "Error code = %d", $errno);
	 */
	public function debug_sql1()
	{
		if ($this->debug_onoff == 0 || $this->debug_flag5 == false)
			return;

		$argv = func_get_args();
		$file = array_shift($argv);
		$func = array_shift($argv);
		$line = array_shift($argv);

		$what = vsprintf(array_shift($argv), array_values($argv));

		// Some php code may not be in a function
		if (empty($func))
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "1: <font color=\"green\">%s</font> <font color=\"blue\">%d:</font><br>%s\n",
						basename($file), $line, $this->format_sql($what, true));
			}
		}
		else
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "1: <font color=\"green\">%s</font>&nbsp;<font color=\"purple\">%s()</font>&nbsp;<font color=\"blue\">%d:</font><br>%s\n",
						basename($file), $func, $line, $this->format_sql($what, true));
			}
		}
	}

	/**
	 * @fn    debug_sql2()
	 *
	 * @brief This works like all other debug1-5 functions except it is used when you want to format SQL statements in HTML.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug5(__FILE__, __FUNCTION__, __LINE__, "Error code = %d", $errno);
	 */
	public function debug_sql2()
	{
		if ($this->debug_onoff == 0 || $this->debug_flag5 == false)
			return;

		$argv = func_get_args();
		$file = array_shift($argv);
		$func = array_shift($argv);
		$line = array_shift($argv);

		$what = vsprintf(array_shift($argv), array_values($argv));

		// Some php code may not be in a function
		if (empty($func))
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "2: <font color=\"green\">%s</font> <font color=\"blue\">%d:</font><br>%s\n",
						basename($file), $line, $this->format_sql($what, true));
			}
		}
		else
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "2: <font color=\"green\">%s</font>&nbsp;<font color=\"purple\">%s()</font>&nbsp;<font color=\"blue\">%d:</font><br>%s\n",
						basename($file), $func, $line, $this->format_sql($what, true));
			}
		}
	}

	/**
	 * @fn    debug_sql3()
	 *
	 * @brief This works like all other debug1-5 functions except it is used when you want to format SQL statements in HTML.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug5(__FILE__, __FUNCTION__, __LINE__, "Error code = %d", $errno);
	 */
	public function debug_sql3()
	{
		if ($this->debug_onoff == 0 || $this->debug_flag5 == false)
			return;

		$argv = func_get_args();
		$file = array_shift($argv);
		$func = array_shift($argv);
		$line = array_shift($argv);

		$what = vsprintf(array_shift($argv), array_values($argv));

		// Some php code may not be in a function
		if (empty($func))
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "3: <font color=\"green\">%s</font> <font color=\"blue\">%d:</font><br>%s\n",
						basename($file), $line, $this->format_sql($what, true));
			}
		}
		else
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "3: <font color=\"green\">%s</font>&nbsp;<font color=\"purple\">%s()</font>&nbsp;<font color=\"blue\">%d:</font><br>%s\n",
						basename($file), $func, $line, $this->format_sql($what, true));
			}
		}
	}

	/**
	 * @fn    debug_sql4()
	 *
	 * @brief This works like all other debug1-5 functions except it is used when you want to format SQL statements in HTML.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug5(__FILE__, __FUNCTION__, __LINE__, "Error code = %d", $errno);
	 */
	public function debug_sql4()
	{
		if ($this->debug_onoff == 0 || $this->debug_flag5 == false)
			return;

		$argv = func_get_args();
		$file = array_shift($argv);
		$func = array_shift($argv);
		$line = array_shift($argv);

		$what = vsprintf(array_shift($argv), array_values($argv));

		// Some php code may not be in a function
		if (empty($func))
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "4: <font color=\"green\">%s</font> <font color=\"blue\">%d:</font><br>%s\n",
						basename($file), $line, $this->format_sql($what, true));
			}
		}
		else
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "4: <font color=\"green\">%s</font>&nbsp;<font color=\"purple\">%s()</font>&nbsp;<font color=\"blue\">%d:</font><br>%s\n",
						basename($file), $func, $line, $this->format_sql($what, true));
			}
		}
	}

	/**
	 * @fn    debug_sql5()
	 *
	 * @brief This works like all other debug1-5 functions except it is used when you want to format SQL statements in HTML.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug5(__FILE__, __FUNCTION__, __LINE__, "Error code = %d", $errno);
	 */
	public function debug_sql5()
	{
		if ($this->debug_onoff == 0 || $this->debug_flag5 == false)
			return;

		$argv = func_get_args();
		$file = array_shift($argv);
		$func = array_shift($argv);
		$line = array_shift($argv);

		$what = vsprintf(array_shift($argv), array_values($argv));

		// Some php code may not be in a function
		if (empty($func))
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "5: <font color=\"green\">%s</font> <font color=\"blue\">%d:</font><br>%s\n",
						basename($file), $line, $this->format_sql($what, true));
			}
		}
		else
		{
			if ($this->fp_debug != null)
			{
				fprintf($this->fp_debug, "5: <font color=\"green\">%s</font>&nbsp;<font color=\"purple\">%s()</font>&nbsp;<font color=\"blue\">%d:</font><br>%s\n",
						basename($file), $func, $line, $this->format_sql($what, true));
			}
		}
	}

	/**
	 * @fn    debug_r1()
	 *
	 * @brief Write debug_on(1) debugging comments for a PHP array.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug_r1(__FILE__, __FUNCTION__, __LINE__, $myarray);
	 */
	public function debug_r1($file, $func, $line, $what = "")
	{
		if ($this->debug_onoff == 0)
			return;

		$file = basename($file);

		if ($this->fp_debug != null)
		{
			fprintf($this->fp_debug,
					"1: <font color=\"green\">%s</font> <font color=\"purple\">%s()</font> <font color=\"blue\">%d:</font>\n", $file, $func, $line);
			$out = print_r($what, true);
			fprintf($this->fp_debug, "%s\n", $out);
		}
	}

	/**
	 * @fn    debug_r2()
	 *
	 * @brief Write debug_on(1) debugging comments for a PHP array.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug_r1(__FILE__, __FUNCTION__, __LINE__, $myarray);
	 */
	public function debug_r2($file, $func, $line, $what = "")
	{
		if ($this->debug_onoff == 0)
			return;

		$file = basename($file);

		if ($this->fp_debug != null)
		{
			fprintf($this->fp_debug, "2: <font color=\"green\">%s</font> <font color=\"purple\">%s()</font> <font color=\"blue\">%d:</font>\n", $file, $func, $line);
			$out = print_r($what, true);
			fprintf($this->fp_debug, "%s\n", $out);
		}
	}

	/**
	 * @fn    debug_r3()
	 *
	 * @brief Write debug_on(1) debugging comments for a PHP array.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug_r1(__FILE__, __FUNCTION__, __LINE__, $myarray);
	 */
	public function debug_r3($file, $func, $line, $what = "")
	{
		if ($this->debug_onoff == 0)
			return;

		$file = basename($file);

		if ($this->fp_debug != null)
		{
			fprintf($this->fp_debug, "3: <font color=\"green\">%s</font> <font color=\"purple\">%s()</font> <font color=\"blue\">%d:</font>\n", $file, $func, $line);
			$out = print_r($what, true);
			fprintf($this->fp_debug, "%s\n", $out);
		}
	}

	/**
	 * @fn    debug_r4()
	 *
	 * @brief Write debug_on(1) debugging comments for a PHP array.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug_r1(__FILE__, __FUNCTION__, __LINE__, $myarray);
	 */
	public function debug_r4($file, $func, $line, $what = "")
	{
		if ($this->debug_onoff == 0)
			return;

		$file = basename($file);

		if ($this->fp_debug != null)
		{
			fprintf($this->fp_debug, "4: <font color=\"green\">%s</font> <font color=\"purple\">%s()</font> <font color=\"blue\">%d:</font>\n", $file, $func, $line);
			$out = print_r($what, true);
			fprintf($this->fp_debug, "%s\n", $out);
		}
	}

	/**
	 * @fn    debug_r5()
	 *
	 * @brief Write debug_on(1) debugging comments for a PHP array.
	 *
	 * @brief argument 1 is the name of the program file or module name. __FILE__
	 * @brief argument 2 is the function name within the program. __FUNCTION__
	 * @brief argument 3 is the line number within the program. __LINE__
	 * @brief the rest of the arguments if the remainder of the text to be made into HTML comments.
	 * @brief Example: $obj->debug_r1(__FILE__, __FUNCTION__, __LINE__, $myarray);
	 */
	public function debug_r5($file, $func, $line, $what = "")
	{
		if ($this->debug_onoff == 0)
			return;

		$file = basename($file);

		if ($this->fp_debug != null)
		{
			fprintf($this->fp_debug,
					"5: <font color=\"green\">%s</font> <font color=\"purple\">%s()</font> <font color=\"blue\">%d:</font>\n",
					$file, $func, $line);
			$out = print_r($what, true);
			fprintf($this->fp_debug, "%s\n", $out);
		}
	}

	/**
	 * @fn    debug_dump_stack()
	 *
	 * @brief Used to dump the PHP call stack in reverse order
	 */
	public function debug_dump_stack()
	{
		if ($this->fp_debug == null)
			return;

		// Retrieve and reverse the backtrace data
		$trace = array_reverse(debug_backtrace());
		$total = count($trace);
		$x = 0;

		foreach ($trace as $item)
		{
			$file_name = '';
			$line_number = '';
			$class_name = '';
			$method_type = '';
			$function_name = '';
			$function_args = NULL;

			if (isset($item['file'])) 		$file_name = $item['file'];
			if (isset($item['line'])) 		$line_number = $item['line'];
			if (isset($item['class'])) 		$class_name = $item['class'];
			if (isset($item['type'])) 		$method_type = $item['type'];
			if (isset($item['function'])) 	$function_name = $item['function'];
			if (isset($item['args'])) 		$function_args = $item['args'];

			$str = sprintf("%d %s(%d)", $x, basename($file_name), $line_number);

			if (strncmp($function_name, "debug", 5) != 0)
			{
				if (!empty($class_name))
				{
					$str .= sprintf(" %s%s%s(", $class_name, $method_type, $function_name);
				}
				else
				{
					$str .= sprintf(" %s(", $function_name);
				}

				$separator = false;

				foreach($function_args as $arg_value)
				{
					if      (is_array($arg_value))    $what = sprintf("<array>");
					else if (is_bool($arg_value))     $what = sprintf("<%s>", $arg_value ? "true" : "false");
					else if (is_callable($arg_value)) $what = sprintf("<callable>");
					else if (is_null($arg_value))     $what = sprintf("<null>");
					else if (is_object($arg_value))   $what = sprintf("<object>");
					else if (is_resource($arg_value)) $what = sprintf("<resource>");
					else if (is_string($arg_value))   $what = sprintf("'%s'", $arg_value);
					else                              $what = sprintf("%s", $arg_value);

					if ($separator)
					{
						$str .= sprintf(",%s", $what);
					}
					else
					{
						$str .= sprintf("%s", $what);
						$separator = true;
					}
				}

				$str .= ")";
			}

			fprintf($this->fp_debug, "%s<br>\n", $str);

			$x++;
		}
	}

	/**
	 * @fn    get_caller($function = NULL, $use_stack = NULL)
	 *
	 * @brief This function will return the name string of the function that called $function. To return the
	 *        caller of your function, either call get_caller(), or get_caller(__FUNCTION__).
	 *
	 * @param string $function
	 * @param string $use_stack
	 *
	 * @return string
	 */
	public function get_caller($function = NULL, $use_stack = NULL)
	{
		if ( is_array($use_stack) )
		{
			//
			// If a function stack has been provided, used that.
			//
			$stack = $use_stack;
		}
		else
		{
			//
			// Otherwise create a fresh one.
			//
			$stack = $this->debug_backtrace();
			echo "\nPrintout of Function Stack: \n\n";
			print_r($stack);
			echo "\n";
		}

		if ($function == NULL)
		{
			//
			// We need $function to be a function name to retrieve its caller. If it is omitted, then
			// we need to first find what function called get_caller(), and substitute that as the
			// default $function. Remember that invoking get_caller() recursively will add another
			// instance of it to the function stack, so tell get_caller() to use the current stack.
			//
			$function = $this->get_caller(__FUNCTION__, $stack);
		}

		if ( is_string($function) && $function != "" )
		{
			//
			// If we are given a function name as a string, go through the function stack and find
			// it's caller.
			//
			for ($i = 0; $i < count($stack); $i++)
			{
				$curr_function = $stack[$i];

				//
				// Make sure that a caller exists, a function being called within the main script
				// won't have a caller.
				//
				if ( $curr_function["function"] == $function && ($i + 1) < count($stack) )
				{
					return $stack[$i + 1]["function"];
				}
			}
		}

		//
		// At this stage, no caller has been found, bummer.
		//
		return "";
	}


	/**
	 * @fn    error_reporting($level)
	 *
	 * @brief Called by debug_on(...) to setup additional PHP error reporting messages.
	 *
	 * @param int $level is the highest debugging level we want messages for.
	 */
	public function error_reporting($level)
	{
		switch ( $level )
		{
			case 0:  // Turn off all error reporting
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "All error reporting is turned off.");
				error_reporting(0);
				break;
			case 1:  // Report simple running errors
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "PHP will report simple running errors.");
				error_reporting(E_ERROR | E_WARNING | E_PARSE);
				break;
			case 2:  // Reporting E_NOTICE can be good too (to report unitialized variables or catch variable name misspellings ...)
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "PHP will report unitialized variables or catch variable name mispellings.");
				error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
				break;
			case 3:  // Report all errors except E_NOTICE. This is the default value set in php.ini
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "PHP will report on all errors except E_NOTICE.");
				error_reporting(E_ALL ^ E_NOTICE);
				break;
			case 4:  // Report all PHP errors (see changelog)
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "PHP will report all E_ALL errors.");
				error_reporting(E_ALL);
				break;
			case 5:  // Report all PHP errors
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "PHP will report all errors.");
				error_reporting(-1);
				break;
			default:
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "Invalid level specified. Use 1-5");
				break;
		}
	}

	/**
	 * @fn    environment_dump()
	 *
	 * @brief If running from apache this function dump the apache server global arrays.
	 */
	public function environment_dump()
	{
		if (PHP_SAPI !== 'cli')
		{
			if (session_id() == '')
				session_start();    // Required to start once in order to retrieve user session information

			//
			// debug_r1($file, $func, $line, $what = "")
			//
			foreach ($_POST as $key => $value)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "_POST: %s = %s", $key, $value);
			}

			foreach ($_GET as $key => $value)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "_GET: %s = %s", $key, $value);
			}

			foreach ($_REQUEST as $key => $value)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "_REQUEST: %s = %s", $key, $value);
			}

			foreach ($_SERVER as $key => $value)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "_SERVER: %s = %s", $key, $value);
			}

			foreach ($_SESSION as $key => $value)
			{
				$this->debug1(__FILE__, __FUNCTION__, __LINE__, "_SESSION: %s = %s", $key, $value);
			}
		}
	}

	/**
	 * @fn get_mountain_timezone($timezone_id='America/Denver')
	 *
	 * @brief Returns the abbreviated timezone for a given zone: (i.e. America/Denver = MST or MDT)
	 *        Used to convert the Remedy tickets to the Mountain Time zone whether it's MST or MDT
	 *
	 * @return string 'MST' or 'MDT'
	 */
	public function get_mountain_timezone($timezone_id='America/Denver')
	{
		if ($timezone_id)
		{
			$dateTime = new DateTime();
			$dateTime->setTimeZone(new DateTimeZone($timezone_id));

			return $dateTime->format('T');
		}

		return false;
	}
}
?>
