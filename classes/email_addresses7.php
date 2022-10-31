<?php
/**
 * NOT CURRENTLY IN USE - This was from STT
 * 
 * @package    CCT
 * @file       email_addresses7.php
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
// Extract email addresses for a given issue_id or action_id
//
// Usage:
//   $addr = new email_addresses();
//   $addr->getIssueAddresses($issue_id);  or $addr->getActionAddresses($action_id)
//   printf("     To: %s\n", $addr->to);
//   printf("     Cc: %s\n", $addr->cc);
//   printf("Subject: %s\n", $addr->subject);
//

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
    require_once('autoloader7.php');
}

/*! @class email_addresses
 *  @brief Extract email information for PHP Mail()
 */
class email_addresses7 extends library7
{
    var $data = array();           // Associated array for any properties we want to create: $this->xxx
    var $ora;                      // Oracle connection handle
    var $dups = array();           // Used to weed out email address duplicates.
    var $group_members = array();  // List of work group cuids. Everyone who can access this spreadsheet.

    /*! @fn __construct()
     *  @brief Class constructor
     *  @return void
     */
    public function __construct()
    {
		date_default_timezone_set('America/Denver');

        $this->ora      = new oracle7();
        $this->to       = '';
        $this->cc       = '';
        $this->subject  = '';
        $this->message  = '';
        $this->error    = '';

        if (PHP_SAPI === 'cli')
        {
            $this->user_cuid         = 'gparkin';
            $this->user_first_name   = 'Greg';
            $this->user_last_name    = 'Parkin';
            $this->user_name         = 'Greg Parkin';
            $this->user_email        = 'gregparkin58@gmail.com';
            $this->user_company      = 'CMP';
            $this->user_access_level = 'admin';

            $this->manager_cuid       = 'gparkin';
            $this->manager_first_name = 'Greg';
            $this->manager_last_name  = 'Parkin';
            $this->manager_name       = 'Greg Parkin';
            $this->manager_email      = 'gregparkin58@gmail.com';
            $this->manager_company    = 'CMP';

            $this->is_debug_on = 'N';

            $this->local_timezone      = "America/Denver(MST)";
            $this->local_timezone_name = "America/Denver";;
            $this->local_timezone_abbr   = "MST";
            $this->local_timezone_offset = 0;

            $this->sql_zone_offset = "(0)";
            $this->sql_zone_abbr   = "MST";

            $this->baseline_timezone        = "America/Denver(MST)";
            $this->baseline_timezone_name   = "America/Denver";
            $this->baseline_timezone_abbr   = "MST";
            $this->baseline_timezone_offset = 0;
        }
        else
        {
            if (session_id() == '')
                session_start();                // Required to start once in order to retrieve user session information

            if (isset($_SESSION['user_cuid']))
            {
                $this->real_cuid       = $_SESSION['real_cuid'];
                $this->user_cuid       = $_SESSION['user_cuid'];
                $this->user_first_name = $_SESSION['user_first_name'];
                $this->user_last_name  = $_SESSION['user_last_name'];
                $this->user_name       = $_SESSION['user_name'];
                $this->user_email      = $_SESSION['user_email'];
                $this->user_company    = $_SESSION['user_company'];
                $this->user_or_admin   = $_SESSION['user_or_admin']; // [user_or_admin] => admin

                $this->manager_cuid       = $_SESSION['manager_cuid'];
                $this->manager_first_name = $_SESSION['manager_first_name'];
                $this->manager_last_name  = $_SESSION['manager_last_name'];
                $this->manager_name       = $_SESSION['manager_name'];
                $this->manager_email      = $_SESSION['manager_email'];
                $this->manager_company    = $_SESSION['manager_company'];

                $this->is_debug_on = $_SESSION['is_debug_on'];

                $this->local_timezone        = $_SESSION['local_timezone'];
                $this->local_timezone_name   = $_SESSION['local_timezone_name'];
                $this->local_timezone_abbr   = $_SESSION['local_timezone_abbr'];
                $this->local_timezone_offset = $_SESSION['local_timezone_offset'];

                $this->sql_zone_offset = $_SESSION['sql_zone_offset'];
                $this->sql_zone_abbr   = $_SESSION['sql_zone_abbr'];

                $this->baseline_timezone        = $_SESSION['baseline_timezone'];
                $this->baseline_timezone_name   = $_SESSION['baseline_timezone_name'];
                $this->baseline_timezone_abbr   = $_SESSION['baseline_timezone_abbr'];
                $this->baseline_timezone_offset = $_SESSION['baseline_timezone_offset'];
            }
            else
            {
                $this->real_cuid       = 'gparkin';
                $this->user_cuid       = 'gparkin';
                $this->user_first_name = 'Greg';
                $this->user_last_name  = 'Parkin';
                $this->user_name       = 'Greg Parkin';
                $this->user_email      = 'gregparkin58@gmail.com';
                $this->user_company    = 'CMP';
                $this->user_or_admin   = 'admin';

                $this->manager_cuid       = 'gparkin';
                $this->manager_first_name = 'Greg';
                $this->manager_last_name  = 'Parkin';
                $this->manager_name       = 'Greg Parkin';
                $this->manager_email      = 'gregparkin58@gmail.com';
                $this->manager_company    = 'CMP';

                $this->is_debug_on = 'N';

                $this->local_timezone      = "America/Denver(MST)";
                $this->local_timezone_name = "America/Denver";;
                $this->local_timezone_abbr   = "MST";
                $this->local_timezone_offset = 0;

                $this->sql_zone_offset = "(0)";
                $this->sql_zone_abbr   = "MST";

                $this->baseline_timezone        = "America/Denver(MST)";
                $this->baseline_timezone_name   = "America/Denver";
                $this->baseline_timezone_abbr   = "MST";
                $this->baseline_timezone_offset = 0;
            }

            $this->debug_start('email_addresses.txt');
        }

        //
        // Pull in a list of group member email addresses that we may want to send mail to.
        //
        $query  = "select ";
        $query .= "  m.mnet_cuid  as mnet_cuid, ";
        $query .= "  m.mnet_name  as mnet_name, ";
        $query .= "  m.mnet_email as mnet_email ";
        $query .= "from ";
        $query .= "  stt_members s, ";
        $query .= "  stt_mnet    m  ";
        $query .= "where ";
        $query .= "  s.work_group_name = '" . $_SESSION['work_group_name'] . "' and ";
        $query .= "  m.mnet_cuid = s.member_cuid";

        if ($this->ora->sql($query) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s - %s", $this->ora->sql_statement, $this->ora->dbErrMsg);
        }

        while ($this->ora->fetch())
        {
            $email_address = sprintf("%s <%s>", $this->ora->mnet_name, $this->ora->mnet_email);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Group Member: %s - %s", $this->ora->mnet_cuid, $email_address);
            $this->group_members[$this->ora->mnet_cuid] = $email_address;
        }
    }

    /*! @fn __destruct()
     *  @brief Destructor function called when no other references to this object can be found, or in any
     *  order during the shutdown sequence. The destructor will be called even if script execution
     *  is stopped using exit(). Calling exit() in a destructor will prevent the remaining shutdown
     *  routines from executing.
     *  @brief Attempting to throw an exception from a destructor(called in the time of script termination)
     *  causes a fatal error.
     *  @return null
     */
    public function __destruct()
    {
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

    /*! @fn __isset($name)
     *  @brief Determine if item($name) exists in the $this->data array
     *  @brief var_dump(isset($obj->first_name));
     *  @param $name is the key in the associated $data array
     *  @return null
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /*! @fn __unset($name)
     *  @brief Unset an item in $this->data assoicated by $name
     *  @brief unset($obj->name);
     *  @param $name is the key in the associated $data array
     *  @return null
     */
    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    /*! @fn getIssueAddresses($issue_id)
     *  @brief
     *  @param $issue_id
     *  @return true or false
     */
    public function getIssueAddresses($issue_id)
    {
        $this->to       = '';
        $this->cc       = '';
        $this->subject  = '';
        $this->error    = '';

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "issue_id = %d", $issue_id);

        if ($this->setIssueSubject($issue_id) == false)
            return false;

        if ($this->getIssue($issue_id) == false)
            return false;

        return true;
    }

    /*! @fn getActionAddresses($action_id)
     *  @brief
     *  @param $action_id
     *  @return true or false
     */
    public function getActionAddresses($action_id)
    {
        $this->to       = '';
        $this->cc       = '';
        $this->subject  = '';
        $this->error    = '';

        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "action_id = %d", $action_id);

        if ($this->setActionSubject($action_id) == false)
            return false;

        if ($this->getAction($action_id) == false)
            return false;

        return true;
    }

    /*! @fn setIssueSubject($issue_id)
     *  @brief Create a email subject line using data from the issue record.
     *  @param $issue_id  is the id number of the stt_issues record.
     *  @return true or false
     */
    private function setIssueSubject($issue_id)
    {
        // Subject: Super Tracker: Issue xxx - HD0000234156 - Work Order - SUDNX974

        if ($issue_id <= 0)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "issue_id = %d", $issue_id);
            return false;
        }

        if ($this->ora->sql("select * from stt_issues where issue_id = " . $issue_id) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s - %s", $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
        }

        $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);

        if ($this->ora->fetch())
        {
            $this->subject = sprintf("Super Tracker: Issue %d", $issue_id);

            if (strlen($this->ora->issue_ticket_no) > 0)
                $this->subject .= sprintf(" - %s", $this->ora->issue_ticket_no);

            if (strlen($this->ora->issue_category_type) > 0)
                $this->subject .= sprintf(" - %s", $this->ora->issue_category_type);

            if (strlen($this->ora->issue_hostname) > 0)
                $this->subject .= sprintf(" - %s", $this->ora->issue_hostname);

            return true;
        }

        return false;
    }

    /*! @fn setActionSubject($action_id)
     *  @brief Create a email subject line using data from the issue record.
     *  @param $action_id  is the id number of the stt_actions record.
     *  @return true or false
     */
    private function setActionSubject($action_id)
    {
        // Subject: Super Tracker: Issue xxx - HD0000234156 - Work Order - SUDNX974

        if ($action_id <= 0)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "action_id = %d", $action_id);
            return false;
        }

        $query  = "select ";
        $query .= "  i.issue_id            as issue_id, ";
        $query .= "  i.issue_ticket_no     as issue_ticket_no, ";
        $query .= "  i.issue_category_type as issue_category_type, ";
        $query .= "  i.issue_hostname      as issue_hostname ";
        $query .= "from ";
        $query .= "  stt_issues i, ";
        $query .= "  stt_actions a ";
        $query .= "where ";
        $query .= "  a.action_id = " . $action_id . " and ";
        $query .= "  i.issue_id = a.issue_id";

        if ($this->ora->sql($query) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s - %s", $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
        }

        $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);

        if ($this->ora->fetch())
        {
            $this->subject = sprintf("Super Tracker: Issue %d, Action %d", $this->ora->issue_id, $action_id);

            if (strlen($this->ora->issue_ticket_no) > 0)
                $this->subject .= sprintf(" - %s", $this->ora->issue_ticket_no);

            if (strlen($this->ora->issue_category_type) > 0)
                $this->subject .= sprintf(" - %s", $this->ora->issue_category_type);

            if (strlen($this->ora->issue_hostname) > 0)
                $this->subject .= sprintf(" - %s", $this->ora->issue_hostname);

            return true;
        }

        return false;
    }

    /*! @fn getIssue($issue_id)
     *  @brief
     *  @param $
     *  @return null
     */
    private function getIssue($issue_id)
    {
        if ($issue_id <= 0)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "issue_id = %d", $issue_id);
            return false;
        }

        if ($this->ora->sql("select * from stt_issues where issue_id = " . $issue_id) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s - %s", $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
        }

        $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);

        if ($this->ora->fetch())
        {
            $issue_insert_cuid      = $this->ora->issue_insert_cuid;
            $issue_update_cuid      = $this->ora->issue_update_cuid;
            $issue_owner_cuid       = $this->ora->issue_owner_cuid;
            $issue_last_modify_cuid = $this->ora->issue_last_modify_cuid;

            $this->debug1(__FILE__, __FUNCTION__, __LINE__,
                "Got issue record: %d, issue_insert_cuid=%s, issue_update_cuid=%s, issue_owner_cuid=%s, issue_last_modify_cuid=%s",
                $issue_id, $issue_insert_cuid, $issue_update_cuid, $issue_owner_cuid, $issue_last_modify_cuid);

            $this->copyAddressTO($issue_owner_cuid);
            $this->copyAddressCC($issue_insert_cuid);
            $this->copyAddressCC($issue_update_cuid);
            $this->copyAddressCC($issue_last_modify_cuid);

            $list_of_action_ids = array();

            if ($this->ora->sql("select action_id from stt_actions where issue_id = " . $issue_id) == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                $this->error = sprintf("%s - %s", $this->ora->sql_statement, $this->ora->dbErrMsg);
                return false;
            }

            while ($this->ora->fetch())
            {
                array_push($list_of_action_ids, $this->ora->action_id);
            }

            foreach ($list_of_action_ids as $action_id)
            {
                $this->getAction($action_id);
            }
        }

        return true;
    }

    /*! @fn getAction($action_id)
     *  @brief Called by getAddresses(). Copies assign to person into $this->to and assign by into $this->cc
     *  @param $action_id This is the action_id we are getting addresses from.
     *  @return null
     */
    private function getAction($action_id)
    {
        if ($action_id <= 0)
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "action_id = %d", $action_id);
            return false;
        }

        if ($this->ora->sql("select * from stt_actions where action_id = " . $action_id) == false)
        {
            $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
            $this->error = sprintf("%s - %s", $this->ora->sql_statement, $this->ora->dbErrMsg);
            return false;
        }

        $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);

        if ($this->ora->fetch()) 
        {
            $assign_to_cuid = $this->ora->action_assign_to_cuid;
            $assign_by_cuid = $this->ora->action_posted_by_cuid;

            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Got action record: %d, action_posted_by_cuid=%s, action_assign_to_cuid=%s",
                $action_id, $assign_by_cuid, $assign_to_cuid);

            $this->copyAddressTO($assign_to_cuid);
            $this->copyAddressCC($assign_by_cuid);
        }

        return true;
    }

    private function copyAddressTO($cuid)
    {
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "cuid = %s", $cuid);

        if (array_key_exists($cuid, $this->group_members))
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->group_members[%s] = %s", $cuid, $this->group_members[$cuid]);

            if (!array_key_exists($cuid, $this->dups))
            {
                $this->dups[$cuid] = $this->group_members[$cuid];

                if (strlen($this->to) > 0)
                {
                    $this->to .= ", " . $this->group_members[$cuid];
                }
                else
                {
                    $this->to = $this->group_members[$cuid];
                }

                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "TO: %s", $this->to);
            }
            else
            {
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "cuid: %s is a duplicate", $cuid);
            }
        }
        else
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "cuid: %s not found in this->group_members", $cuid);
        }
    }

    private function copyAddressCC($cuid)
    {
        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "cuid = %s", $cuid);

        if (array_key_exists($cuid, $this->group_members))
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->group_members[%s] = %s", $cuid, $this->group_members[$cuid]);

            if (!array_key_exists($cuid, $this->dups))
            {
                $this->dups[$cuid] = $this->group_members[$cuid];

                if (strlen($this->cc) > 0)
                {
                    $this->cc .= ", " . $this->group_members[$cuid];
                }
                else
                {
                    $this->cc = $this->group_members[$cuid];
                }

                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "CC: %s", $this->cc);
            }
            else
            {
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "cuid: %s is a duplicate", $cuid);
            }
        }
        else
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "cuid: %s not found in this->group_members", $cuid);
        }
    }
}
?>
