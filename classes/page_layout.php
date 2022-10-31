<?php
/**
 * @package    CCT
 * @file       page_layout.php
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
// dump()                               - Dump HTML PHP global variables for evaluation.
// isButton($what)                      - Helper function used to determine if a button has been pressed on a web page.
// set_refresh_seconds($what)           - Used to set the meta refresh time interval after the web page loads.
// set_meta_refresh($what)              - Set the META_REFRESH to the URL we want to refresh when the timer elapses.
// set_program($what)                   - Used to set the program and options in a form statement
// set_form_name($what)                 - Overrides the default form name of f1.
// set_method_post()                    - Changes the form method to post which is the default setting in the constructor.
// set_method_get()                     - Changes the form method to get.
// set_loading_file_off()               - Disable the loading page which is the default setting.
// set_loading_file_on($what)           - Sets flag and the text that will be displayed for the loading file screen
// set_base_href($what)                 - Overrides the default base_href setting created in the constructor function
// set_on_load($what)                   - Sets the body's onload= to perform some kind of javascript action.
// stop_before_end()                    - Sets a flag to instruct footer() to return before writing the ending body and html tags.
// stop($file, $func, $line, $msg)      - Output a Stop graphic, file, function, line number and message.
//


//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
    require_once('autoloader.php');  // includes/autoloader.php
}

/** @class page_layout
 *  @brief HTML page generator which uses a custom templete to include a static position header menu and footer.
 *  @brief Used by all CCT programs.
 */
class page_layout extends library
{
    //
    // CCT Version Numbers.
    //
    var $major_version = 7;
    var $minor_version = 0;
    var $build_date = '06/18/2016';

    //
    // Private properties
    //
    var $parm;                                //!< array of HTML URL parm values by key => value
    var $parm_count;                          //!< number of parms in $parm
    var $data;                                //!< array of data values by key => value (See: __get(), __set(), __isset(), __unset())
    var $numberOfTabs = 0;                    //!< initial value for the number of tabs we are using if we want to use the javascript tab control
    var $colourOfInactiveTab  = "#999966";    //!< Color for web page inactive tabs in javascript tab control.
    var $colourOfActiveTab    = "#cccc99";    //!< Color for web page active tabs in javascript tab control.
    var $colourOfInactiveLink = "#333333";    //!< Color for web page inactive URL links.
    var $colourOfActiveLink   = "black";      //!< Color for web page active URL links.
    var $msie = 0;                            // If using Microsoft IE, what version is it?

    /** @fn __construct()
     *  @brief Class constructor - Create oracle object and setup some dynamic class variables
     *  @return void
     */
    public function __construct()
    {
		date_default_timezone_set('America/Denver');

        if (session_id() == '')
            session_start();                // Required to start once in order to retrieve user session information

        $this->parm = array();              // Parameters from $_SERVER['QUERY_STRING (e.g., "id=234&name=greg")
        $this->parm_count = 0;              // Number of parms in $parm array.

        $this->data = array();              // Associated array for public class variables.

        $this->COUNTER_DIR          = "/opt/ibmtools/cct7/counters/";                          // Path to counter file directory
        $this->COUNTER_FILE         = basename($_SERVER['SCRIPT_FILENAME'], '.php');           // Counter filename
        $this->ICON_FILE            = 'icons/handshake1.ico';                                  // Browser application icon file
        $this->PROGRAM              = "";                                                      // Program name. See: set_program()
        $this->FORM_NAME            = "f1";                                                    // HTML form name=f1
        $this->BASE_HREF            = "";                                                      // HTML base_ref path
        $this->METHOD               = "post";                                                  // HTML form method=post
        $this->ON_LOAD              = "";                                                      // On load message. See: set_loading_file_on()
        $this->NUMBEROFTABS         = 0;                                                       // Number of tabs to create for tab control
        $this->COLOUROFINACTIVETAB  = "#C0C0C0";                                               // Color of inactive tabs
        $this->COLOUROFACTIVETAB    = "#ECE9D8";                                               // Color of active tab
        $this->COLOUROFINACTIVELINK = "#333333";                                               // Color of inactive links
        $this->COLOUROFACTIVELINK   = "black";                                                 // Color of active links
        $this->PAGE_HITS            = true;                                                    // Page Hits turned on by default

        if (isset($_SESSION['alert']) && strlen($_SESSION['alert']) > 0)
        {
            $this->error    = $_SESSION['alert'];                                      // Popup message from session data
            unset($_SESSION['alert']);
        }
        else
        {
            $this->error    = "";                                                      // Popup message on load. See: setAlert()
        }

        $this->META_REFRESH         = "";                                                      // URL to load on refresh. See: set_meta_refresh()
        $this->REFRESH_RATE         = "";		                                               // Seconds to wait before refresh. See: set_refresh_seconds()
        $this->META_KEYWORDS        = 'cct, change, coordination, request';                    // Meta tags for search engines
        $this->META_DISTRIBUTION    = 'CMP';                                           // Meta distribution tag
        $this->META_COMPANY         = 'CMP';                                                   // Meta company tag - company that created CCT
        $this->META_CONTACT_PERSON  = 'Greg Parkin';                                           // Meta contact person
        $this->META_CONTACT_EMAIL   = 'parking[AT]us[DOT]ibm[DOT]com';                         // Meta contact email

        $this->IS_LOADING_FILE_ON   = false;                                                   // Flag to enable onload screen. See: set_loading_file_on()
        $this->LOADING_MESSAGE      = "";                                                      // Message for onload screen. See: set_loading_file_on()
        $this->STOP_BEFORE_END      = false;                                                   // Stop before end of body and html statements. See footer()
        $this->LOAD_MENU            = "main";                                                  // Load the main menu by default

        //
        // Determine what server CCT is running on and setup server specific variables.
        //)
        switch ( $_SERVER['SERVER_NAME'] )
        {
            case 'lxomp11m.qintra.com':                                                     // Production CCT server on lxomp11m - 151.117.157.53
                $this->BASE_HREF             = 'https://lxomp11m.qintra.com/cct7/';         // HTML base_ref location from Apache web doc root
                $this->WWW                   = 'https://lxomp11m.qintra.com/cct7';          // HTML URL on production: lxomp11m.qintra.com/CCT
                $this->CCT_APPLICATION       = 'Change Coordination Tool - Production';     // CCT application title for Production
                $this->CCT_APPLICATION_COLOR = 'lightgreen';                                // CCT application title text color
                $this->CCT_SERVER            = 'Production Server';                         // CCT application server type
                $this->DEBUG_PATH            = '/xxx/cct7/debug';                           // Path to debug directory containing debug files
                $this->LOG_PATH              = '/xxx/cct7/logs';                            // Path to log directory containing log files
                $this->ICON_FILE             = 'images/superman2.gif';                      // Browser application icon file
                $this->HTML_TITLE            = 'Change Coordination Tool';                  // HTML web page title text: <title> ... </title>
                $this->DOCUMENT_ROOT         = $_SERVER['DOCUMENT_ROOT'];                   // (i.e. /xxx/www/CCT)
                break;

            case 'cct.qintra.com':                                                          // Production CCT server on lxomp11m - 151.117.157.53
                $this->BASE_HREF             = 'https://cct.qintra.com/';                   // HTML base_ref location from Apache web doc root
                $this->WWW                   = 'https://cct.qintra.com';                    // HTML URL on production: cct.qintra.com
                $this->CCT_APPLICATION       = 'Change Coordination Tool - Production';     // CCT application title for Production
                $this->CCT_APPLICATION_COLOR = 'lightgreen';                                // CCT application title text color
                $this->CCT_SERVER            = 'Production Server';                         // CCT application server type
                $this->DEBUG_PATH            = '/xxx/cct7/debug';                           // Path to debug directory containing debug files
                $this->LOG_PATH              = '/xxx/cct7/logs';                            // Path to log directory containing log files
                $this->ICON_FILE             = 'images/superman2.gif';                      // Browser application icon file
                $this->HTML_TITLE            = 'Change Coordination Tool';                  // HTML web page title text: <title> ... </title>
                $this->DOCUMENT_ROOT         = $_SERVER['DOCUMENT_ROOT'];                   // (i.e. /xxx/www/cct)
                break;

            case 'lxomt12m.dev.qintra.com':                                                 // Dev/Test CCT server on lxomt12m - 151.117.41.173
                $this->BASE_HREF             = 'https://lxomt12m.dev.qintra.com/cct/';      // HTML base_ref location from Apache web doc root
                $this->WWW                   = 'https://lxomt12m.dev.qintra.com/cct';       // HTML URL on Dev/Test: lxomt12m.dev.qintra.com/cct
                $this->CCT_APPLICATION       = 'Change Coordination Tool - Dev/Test';       // CCT application title for Dev/Test
                $this->CCT_APPLICATION_COLOR = 'yellow';                                    // CCT application title text color
                $this->CCT_SERVER            = 'Dev/Test Server';                           // CCT application server type
                $this->DEBUG_PATH            = '/xxx/cct7/debug';                           // Path to debug directory containing debug files
                $this->LOG_PATH              = '/xxx/cct7/logs';                            // Path to log directory containing log files
                $this->ICON_FILE             = 'images/superman2.gif';                      // Browser application icon file
                $this->HTML_TITLE            = 'Change Coordination Tool Dev/Test';         // HTML web page title text: <title> ... </title>
                $this->DOCUMENT_ROOT         = $_SERVER['DOCUMENT_ROOT'];                   // (i.e. /xxx/www/cct)
                break;

            case 'cct.dev.qintra.com':
                $this->BASE_HREF             = 'https://cct.dev.qintra.com/';               // HTML base_ref location from Apache web doc root
                $this->WWW                   = 'https://cct.dev.qintra.com';                // HTML URL on Dev/Test: cct.dev.qintra.com
                $this->CCT_APPLICATION       = 'Change Coordination Tool - Dev/Test';       // CCT application title for Dev/Test
                $this->CCT_APPLICATION_COLOR = 'yellow';                                    // CCT application title text color
                $this->CCT_SERVER            = 'Dev/Test Server';                           // CCT application server type
                $this->DEBUG_PATH            = '/xxx/cct7/debug';                           // Path to debug directory containing debug files
                $this->LOG_PATH              = '/xxx/cct7/logs';                            // Path to log directory containing log files
                $this->ICON_FILE             = 'images/superman2.gif';                      // Browser application icon file
                $this->HTML_TITLE            = 'Change Coordination Tool Dev/Test';         // HTML web page title text: <title> ... </title>
                $this->DOCUMENT_ROOT         = $_SERVER['DOCUMENT_ROOT'];                   // (i.e. /xxx/www/cct)
                break;

            default:                                                                        // Dev/Test CCT server on LENOVO - 127.0.0.1
                $this->BASE_HREF             = 'http://cct7.localhost/';                    // HTML base_ref location from Apache web doc root
                $this->WWW                   = 'http://cct7.localhost';                     // HTML URL on GREG-PC: localhost/supertracker
                $this->CCT_APPLICATION       = 'Change Coordination Tool - LENOVO';         // CCT application title for Dev/Test
                $this->CCT_APPLICATION_COLOR = 'cyan';                                      // CCT application title text color
                $_SERVER['REMOTE_USER']      = "gparkin";                                   // LDAP not setup on LENOVO so set REMOTE_USER to gparkin
                $this->CCT_SERVER            = 'Development Server';                        // CCT application server type
                $this->DEBUG_PATH            = '/opt/ibmtools/cct7/debug';                  // Path to debug directory containing debug files
                $this->LOG_PATH              = '/opt/ibmtools/cct7/logs';                   // Path to log directory containing log files
                $this->ICON_FILE             = 'images/handshake1.ico';                     // Browser application icon file
                $this->HTML_TITLE            = 'Change Coordination Tool LENOVO';           // HTML web page title text: <title> ... </title>
                $this->DOCUMENT_ROOT         = $_SERVER['DOCUMENT_ROOT'] ;                  // (i.e. /xxx/www/cct)
        }

        //
        // Initialize variables for session data
        //
        $this->user_cuid                 = 'gparkin';
        $this->user_first_name           = 'Greg';
        $this->user_last_name            = 'Parkin';
        $this->user_name                 = 'Greg Parkin';
        $this->user_email                = 'gregparkin58@gmail.com';
        $this->user_company              = 'CMP';
        $this->user_or_admin             = 'admin';

        $this->manager_cuid              = 'gparkin';
        $this->manager_first_name        = 'Greg';
        $this->manager_last_name         = 'Parkin';
        $this->manager_name              = 'Greg Parkin';
        $this->manager_email             = 'gregparkin58@gmail.com';
        $this->manager_company           = 'CMP';

        $this->is_debug_on               = 'Y';
        $this->debug_level1              = 'Y';
        $this->debug_level2              = 'Y';
        $this->debug_level3              = 'Y';
        $this->debug_level4              = 'Y';
        $this->debug_level5              = 'Y';
        $this->debug_path                = '/opt/ibmtools/cct7/debug';
        $this->debug_mode                = 'w';

        $this->local_timezone            = "America/Denver (MST)";
        $this->local_timezone_name       = "America/Denver";
        $this->local_timezone_abbr       = "MST";
        $this->local_timezone_offset     = 0;

        $this->sql_zone_offset           = "(0)";
        $this->sql_zone_abbr             = "MST";

        $this->baseline_timezone         = "America/Denver (MST)";
        $this->baseline_timezone_name    = "America/Denver";
        $this->baseline_timezone_abbr    = "MST";
        $this->baseline_timezone_offset  = 0;

        $this->time_difference           = "0 seconds or 0 hours";

        $this->user_report_output        = isset($_SESSION['user_report_output'])
            ? $_SESSION['user_report_output'] : "HTML"; // Remote user report output setting

        //
        // Information for the FOOTER
        //
        $this->NET_GROUP_NAME  = "AIM-TOOLS-BOM";   // Footer Remedy Assign Group for creating trouble tickets
        $this->NET_PIN         = "17340";           // Footer Net-Pin number for Net-Tool paging
        $this->APP_ACRONYM     = 'CCT';             // Change Coordination Tool acronym - CCT
        $this->BUILD_DATE      = $this->build_date; // Last release date for CCT
        $this->BUILD_VERSION   = $this->major_version . "." . $this->minor_version; // Last release version for CCT

        $this->parm_count = 0;

        //
        // Parse QUERY_STRING
        //
        if (isset($_SERVER['QUERY_STRING']))   // Parse QUERY_STRING if it exists
        {
            //printf("<!-- QUERY_STRING = %s -->\n", $_SERVER['QUERY_STRING']);

            //
            // $myQryStr = "first=1&second=Z&third[]=5000&third[]=6000";
            // parse_str($myQryStr, $myArray);
            // echo $myArray['first']; //will output 1
            // echo $myArray['second']; //will output Z
            // echo $myArray['third'][0]; //will output 5000
            // echo $myArray['third'][1]; //will output 6000
            //
            parse_str($_SERVER['QUERY_STRING'], $this->parm);  // Parses URL parameter options into an array called $this->parm
            $this->parm_count = count($this->parm);            // Get the parm count. Number of items in the $this->parm array.
        }

        //
        // Copy the $_REQUEST information to our $this->data[key] = value
        //
        // For example if you have a HTML form input variable called 'cm_ticket_no' then you will be
        // to access the information in this form: $obj->cm_ticket_name
        //
        foreach ($_REQUEST as $key => $value)
        {
            $this->data[$key] = $value;
        }

        $this->debug_start('page_layout.txt');

        //
        // If the user is running Internet Explorer then what version is it?
        // (i.e. Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0))
        //
        $this->msie = 0;

        preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);

        if(count($matches)<2)
        {
            preg_match('/Trident\/\d{1,2}.\d{1,2}; rv:([0-9]*)/', $_SERVER['HTTP_USER_AGENT'], $matches);
        }

        if (count($matches)>1)
        {
            $this->msie = $matches[1];
        }

        if ($this->msie == 7)
        {
            $this->msie = 9;
        }

        $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "HTML: parm");
        $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $this->parm);

        $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "_POST");
        $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_POST);

        $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "_GET");
        $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_GET);

        $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "_REQUEST");
        $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_REQUEST);

        $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "_SERVER");
        $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_SERVER);

        $this->debug1(  __FILE__, __FUNCTION__, __LINE__, "_SESSION");
        $this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $_SESSION);

        $this->initialize();
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

    /** @fn initialize()
     *  @brief Initialize the web page layout. May load twice to get user's TZ.
     *  @return null
     */
    private function initialize()
    {
        echo "<!DOCTYPE HTML>\n";   // Enable HTML 5

        if (session_id() == '')
        {
            session_start();
        }

        if (empty($_SERVER['REMOTE_USER']))
        {
            $this->stop(__FILE__, __FUNCTION__, __LINE__, "_SERVER['REMOTE_USER'] data is missing!");
        }

        //
        // There can be multiple applications that use a copy of this class (page_layout.php) such as Super Tracker.
        // We need to check that we are looking at the right session data for this application. We will check
        // a session variable called "APP_ACRONYM" to see if it contains: "cct7". If it doesn't then we want to
        // reload the session data.
        //
        // If this is the first time the user has logged into this web site this session variable
        // CALL_HTML_TIMEZONE will not be set. In this case we want to set it to YES to instruct the AJAX
        // code below will call server/html_timezone.php to retrieve the local user's workstation timezone
        // information. The session variable in the AJAX routine will set this variable to NO meaning
        // it has the timezone information stored in other session variables. After the AJAX routine
        // finishes running it will reload the index.php page where now the CALL_HTML_TIMEZONE variable
        // will be set to NO.
        //
        if (!isset($_SESSION['APP_ACRONYM']) || $_SESSION['APP_ACRONYM'] != $this->APP_ACRONYM || !isset($_SESSION['CALL_HTML_TIMEZONE']))
        {
            $_SESSION['APP_ACRONYM'] = $this->APP_ACRONYM;
            $_SESSION['CALL_HTML_TIMEZONE'] = "YES";
        }

        //
        // If the CALL_HTML_TIMEZONE session variable has been set to NO, it means AJAX has run to
        // receive the user's local timezone information. Next we check to see if session variable
        // user_cuid has been set. If it hasn't we need to retrieve the user's information from the
        // cct_mnet (CMP Employee Director table) table to set additional session variables.
        //
        if ($_SESSION['CALL_HTML_TIMEZONE'] == "NO" && !isset($_SESSION['cct7']))
        {
            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Session data does not exist for this user.");

            $ora = new oracle();  // classes/oracle.php

            $_SESSION['user_report_output'] = $this->user_report_output; // initialized to 'browser' in: __construct()

            $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Getting cct7_mnet record for user_cuid=%s", $_SERVER['REMOTE_USER']);

            //
            // Retrieve user information and store in session values.
            //
            $query  = "select * from cct7_mnet where ";
            $query .= sprintf("lower(mnet_cuid) = lower('%s') or ", $_SERVER['REMOTE_USER']);
            $query .= sprintf("lower(mnet_workstation_login) = lower('%s')", $_SERVER['REMOTE_USER']);

            if ($ora->sql($query) == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
            }

            if ($ora->fetch())
            {
                $this->debug1(__FILE__, __FUNCTION__, __LINE__,
                    "Got cct7_mnet record for user %s", $_SERVER['REMOTE_USER']);

                //
                // Copy this user's MNET data into their $_SESSION data variables
                //
                $_SESSION['real_cuid']       = $ora->mnet_cuid;
                $_SESSION['real_name']       = $ora->mnet_name;
                $_SESSION['user_cuid']       = $this->user_cuid       = $this->real_cuid      = $ora->mnet_cuid;
                $_SESSION['user_first_name'] = $this->user_first_name = $this->real_name      = $ora->mnet_first_name;
                $_SESSION['user_last_name']  = $this->user_last_name  = $ora->mnet_last_name;
                $_SESSION['user_name']       = $this->user_name       = $ora->mnet_name;
                $_SESSION['user_email']      = $this->user_email      = $ora->mnet_email;
                $_SESSION['user_company']    = $this->user_company    = $ora->mnet_company;

                //
                // Does the users manager's cuid exist?
                //
                if (!empty($ora->mnet_mgr_cuid))
                {
                    $this->debug1(__FILE__, __FUNCTION__, __LINE__,
                        "Getting cct7_mnet record for user %s managers cuid=%s",
                        $_SERVER['REMOTE_USER'], $ora->mnet_mgr_cuid);

                    //
                    // Retrieve the manager's cuid from MNET
                    //
                    if ($ora->sql("select * from cct7_mnet where mnet_cuid = '" . $ora->mnet_mgr_cuid . "'") == false)
                    {
                        $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                        $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                        $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin",
                            __FILE__, __LINE__);
                    }

                    if ($ora->fetch())
                    {
                        $this->debug1(__FILE__, __FUNCTION__, __LINE__,
                            "Got cct7_mnet record for user %s manager's cuid %s",
                            $_SERVER['REMOTE_USER'], $ora->mnet_cuid);
                        //
                        // Add the users manager's MNET data to the $_SESSION data
                        //
                        $_SESSION['manager_cuid']       = $this->manager_cuid       = $ora->mnet_cuid;
                        $_SESSION['manager_first_name'] = $this->manager_first_name = $ora->mnet_first_name;
                        $_SESSION['manager_last_name']  = $this->manager_last_name  = $ora->mnet_last_name;
                        $_SESSION['manager_name']       = $this->manager_name       = $ora->mnet_name;
                        $_SESSION['manager_email']      = $this->manager_email      = $ora->mnet_email;
                        $_SESSION['manager_company']    = $this->manager_company    = $ora->mnet_company;
                    }
                }
            }
            else
            {
                //
                // This code should never reach here because the user must authenticate using their CTL cuid where
                // is always (should be anyway) found in the MNET database (cct_mnet).
                //
                printf("<html><h2>Unable to locate User ID: %s in CMP MNET table!</h2></html>\n", $_SERVER['REMOTE_USER']);
                exit();
            }

            $this->issue_changed = $_SESSION['issue_changed'] = time(); // UNIX Timestamp (i.e. 1431656775)

            //
            // Next we want to create or update a record in cct7_users for this user and retrieve their
            // debugging session data. Debugging is only for the CCT admins.
            //
            // First off set see if the user record in cct7_users exist. If not create a new record. If it
            // does exist, then update the record with information about their browser settings, when they
            // lasted logged, and their local timezone information. The information about their browser and
            // local timezone is used debugging problems with CCT. It's helpful to know what browser they are
            // using where they are located in the world.
            //
            $query  = "select * from cct7_users where user_cuid = '" . $_SERVER['REMOTE_USER'] . "'";

            if ($ora->sql($query) == false)
            {
                $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
            }

            if ($ora->fetch() == false)
            {
                //
                // Record does not exist so lets create one for this user.
                //
                $rc = $ora
                    ->insert("cct7_users")
                    ->column("user_cuid")
                    ->column("insert_cuid")
                    ->column("insert_name")
                    ->column("local_timezone")
                    ->column("local_timezone_name")
                    ->column("local_timezone_abbr")
                    ->column("local_timezone_offset")
                    ->column("baseline_timezone")
                    ->column("baseline_timezone_name")
                    ->column("baseline_timezone_abbr")
                    ->column("baseline_timezone_offset")
                    ->column("time_difference")
                    ->column("sql_zone_offset")
                    ->column("sql_zone_abbr")
                    ->column("last_login_date")
                    ->column("http_user_agent")
                    ->value("char", $this->user_cuid)                      // user_cuid
                    ->value("char", $this->user_cuid)                      // insert_cuid
                    ->value("char", $this->user_name)                      // insert_name
                    ->value("char", $_SESSION['local_timezone'])           // local_timezone
                    ->value("char", $_SESSION['local_timezone_name'])      // local_timezone_name
                    ->value("char", $_SESSION['local_timezone_abbr'])      // local_timezone_abbr
                    ->value("int",  $_SESSION['local_timezone_offset'])    // local_timezone_offset
                    ->value("char", $_SESSION['baseline_timezone'])        // baseline_timezone
                    ->value("char", $_SESSION['baseline_timezone_name'])   // baseline_timezone_name
                    ->value("char", $_SESSION['baseline_timezone_abbr'])   // baseline_timezone_abbr
                    ->value("int",  $_SESSION['baseline_timezone_offset']) // baseline_timezone_offset
                    ->value("char", $_SESSION['time_difference'])          // time_difference
                    ->value("char", $_SESSION['sql_zone_offset'])          // sql_zone_offset
                    ->value("char", $_SESSION['sql_zone_abbr'])            // sql_zone_abbr
                    ->value("now")                                         // last_login_date
                    ->value("char", $_SERVER['HTTP_USER_AGENT'])           // http_user_agent
                    ->execute();

                if ($rc == false)
                {
                    $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->sql_statement);
                    $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $this->ora->dbErrMsg);
                    $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin: %s",
                        __FILE__, __LINE__, $ora->sql_statement);
                }

                $ora->commit();

                $this->user_access_level = $_SESSION['user_access_level'] = "user";
                $this->is_debug_on       = $_SESSION['is_debug_on']       = 'N';
                $this->debug_level1      = $_SESSION['debug_level1']      = 'Y';
                $this->debug_level2      = $_SESSION['debug_level2']      = 'Y';
                $this->debug_level3      = $_SESSION['debug_level3']      = 'Y';
                $this->debug_level4      = $_SESSION['debug_level4']      = 'Y';
                $this->debug_level5      = $_SESSION['debug_level5']      = 'Y';
                $this->debug_path        = $_SESSION['debug_path']        = '/opt/ibmtools/src/cct7/debug/';
                $this->debug_mode        = $_SESSION['debug_mode']        = 'w';
            }
            else
            {
                //
                // Update the user's cct7_users record with the information we want to save.
                //
                $rc = $ora
                    ->update("cct7_users")
                    ->set("char", "update_cuid",              $this->user_cuid)
                    ->set("char", "update_name",              $this->user_name)
                    ->set("char", "local_timezone",           $_SESSION['local_timezone'])
                    ->set("char", "local_timezone_name",      $_SESSION['local_timezone_name'])
                    ->set("char", "local_timezone_abbr",      $_SESSION['local_timezone_abbr'])
                    ->set("int",  "local_timezone_offset",    $_SESSION['local_timezone_offset'])
                    ->set("char", "baseline_timezone",        $_SESSION['baseline_timezone'])
                    ->set("char", "baseline_timezone_name",   $_SESSION['baseline_timezone_name'])
                    ->set("char", "baseline_timezone_abbr",   $_SESSION['baseline_timezone_abbr'])
                    ->set("int",  "baseline_timezone_offset", $_SESSION['baseline_timezone_offset'])
                    ->set("char", "time_difference",          $_SESSION['time_difference'])
                    ->set("char", "sql_zone_offset",          $_SESSION['sql_zone_offset'])
                    ->set("char", "sql_zone_abbr",            $_SESSION['sql_zone_abbr'])
                    ->set("now",  "last_login_date")
                    ->set("char", "http_user_agent",          $_SERVER['HTTP_USER_AGENT'])
                    ->where("char", "user_cuid", "=", $this->user_cuid)
                    ->execute();

                if ($rc == false)
                {
                    $this->debug_sql1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->sql_statement);
                    $this->debug1(__FILE__, __FUNCTION__, __LINE__, "%s", $ora->dbErrMsg);
                    $this->error = sprintf("SQL syntax error in file: %s, line: %d. Please contact Greg Parkin", __FILE__, __LINE__);
                }

                $ora->commit();

                //
                // Update the user's debugging session cache variables.
                //
                $this->user_access_level = $_SESSION['user_access_level'] = $ora->user_or_admin;
                $this->is_debug_on       = $_SESSION['is_debug_on']       = $ora->is_debug_on;
                $this->debug_level1      = $_SESSION['debug_level1']      = $ora->debug_level1;
                $this->debug_level2      = $_SESSION['debug_level2']      = $ora->debug_level2;
                $this->debug_level3      = $_SESSION['debug_level3']      = $ora->debug_level3;
                $this->debug_level4      = $_SESSION['debug_level4']      = $ora->debug_level4;
                $this->debug_level5      = $_SESSION['debug_level5']      = $ora->debug_level5;
                $this->debug_path        = $_SESSION['debug_path']        = $ora->debug_path;
                $this->debug_mode        = $_SESSION['debug_mode']        = $ora->debug_mode;
            }
        } // END: if ($_SESSION['CALL_HTML_TIMEZONE'] == "NO" && !isset($_SESSION['user_cuid']))
        else if ($_SESSION['CALL_HTML_TIMEZONE'] == "NO")
        {
            // Always refresh this session value
            $_SESSION['issue_changed'] = time();

            //
            // $_SESSION data already exists so retrieve it and copy the data into our global class variables for
            // further processing by any program that may need it.
            //
            $this->real_cuid                = $_SESSION['real_cuid'];
            $this->real_name                = $_SESSION['real_name'];

            $this->user_cuid                = $_SESSION['user_cuid'];
            $this->user_first_name          = $_SESSION['user_first_name'];
            $this->user_last_name           = $_SESSION['user_last_name'];
            $this->user_name                = $_SESSION['user_name'];
            $this->user_email               = $_SESSION['user_email'];
            $this->user_company             = $_SESSION['user_company'];
            $this->user_report_output       = $_SESSION['user_report_output'];

            $this->manager_cuid             = $_SESSION['manager_cuid'];
            $this->manager_first_name       = $_SESSION['manager_first_name'];
            $this->manager_last_name        = $_SESSION['manager_last_name'];
            $this->manager_name             = $_SESSION['manager_name'];
            $this->manager_email            = $_SESSION['manager_email'];
            $this->manager_company          = $_SESSION['manager_company'];

            //
            // This data actually comes from cct_members except for the timezone info which
            // is figured out by servers/html_timezone.php and then updated in cct_members
            // for debugging and tracking purposes.
            //
            $this->member_cuid              = $_SESSION['member_cuid'];
            $this->member_name              = $_SESSION['member_name'];
            $this->member_email             = $_SESSION['member_email'];
            $this->member_can_close         = $_SESSION['member_can_close'];
            $this->member_city              = $_SESSION['member_city'];
            $this->member_state_code        = $_SESSION['member_state_code'];
            $this->member_country_code      = $_SESSION['member_country_code'];
            $this->member_can_close         = $_SESSION['member_can_close'];
            $this->local_timezone           = $_SESSION['local_timezone'];
            $this->local_timezone_name      = $_SESSION['local_timezone_name'];
            $this->local_timezone_abbr      = $_SESSION['local_timezone_abbr'];
            $this->local_timezone_offset    = $_SESSION['local_timezone_offset'];
            $this->sql_zone_offset          = $_SESSION['sql_zone_offset'];
            $this->sql_zone_abbr            = $_SESSION['sql_zone_abbr'];
            $this->baseline_timezone        = $_SESSION['baseline_timezone'];
            $this->baseline_timezone_name   = $_SESSION['baseline_timezone_name'];
            $this->baseline_timezone_abbr   = $_SESSION['baseline_timezone_abbr'];
            $this->baseline_timezone_offset = $_SESSION['baseline_timezone_offset'];
            $this->time_difference          = $_SESSION['time_difference'];
            $this->user_or_admin            = $_SESSION['user_or_admin'];
            $this->is_debug_on              = $_SESSION['is_debug_on'];
            $this->debug_level1             = $_SESSION['debug_level1'];
            $this->debug_level2             = $_SESSION['debug_level2'];
            $this->debug_level3             = $_SESSION['debug_level3'];
            $this->debug_level4             = $_SESSION['debug_level4'];
            $this->debug_level5             = $_SESSION['debug_level5'];
            $this->debug_path               = $_SESSION['debug_path'];
            $this->debug_mode               = $_SESSION['debug_mode'];
            $this->pref_sort_field1         = $_SESSION['pref_sort_field1'];
            $this->pref_sort_field2         = $_SESSION['pref_sort_field2'];
            $this->pref_sort_field3         = $_SESSION['pref_sort_field3'];
            $this->pref_sort_field4         = $_SESSION['pref_sort_field4'];
            $this->pref_sort_field5         = $_SESSION['pref_sort_field5'];
            $this->pref_sort_field6         = $_SESSION['pref_sort_field6'];
            $this->pref_sort_direction      = $_SESSION['pref_sort_direction'];
            $this->pref_starting_date       = $_SESSION['pref_starting_date'];
            $this->pref_show_open           = $_SESSION['pref_show_open_issues'];
            $this->pref_show_closed         = $_SESSION['pref_show_closed_issues'];
            $this->pref_show_deleted_issues = $_SESSION['pref_show_deleted_issues'];
            $this->pref_just_mine           = $_SESSION['pref_show_just_mine'];
            $this->pref_excel_action_items  = $_SESSION['pref_excel_action_items'];
            $this->pref_excel_status_notes  = $_SESSION['pref_excel_status_notes'];
            $this->pref_excel_private_notes = $_SESSION['pref_excel_private_notes'];
            $this->work_group_name          = $_SESSION['work_group_name'];
            $this->issue_changed            = $_SESSION['issue_changed'];

        } // END: else if ($_SESSION['CALL_HTML_TIMEZONE'] == "NO")

        //
        // header() is a PHP function for modifying the HTML header information
        // going to the client's browser. Here we tell their browser not to cache
        // the page coming in so their browser will always rebuild the page from
        // scratch instead of retrieving a copy of one from its cache.
        //
        // header("Expires: " . gmstrftime("%a, %d %b %Y %H:%M:%S GMT"));
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        //
        // QUERY_STRING is actually parsed in the constructor function so programs can know right
        // away if there are any parms available. Below we just show the parm information if present.
        //
        if (isset($_SERVER['QUERY_STRING']))
        {
            printf("<!-- QUERY_STRING = %s -->\n", $_SERVER['QUERY_STRING']);
        }

        //
        // Disable buffering - This is what makes the loading screen work properly.
        //
        @apache_setenv('no-gzip', 1);
        @ini_set('zlib.output_compression', 0);
        @ini_set('output_buffering', 'Off');
        @ini_set('implicit_flush', 1);

        ob_implicit_flush(1); // Flush buffers

        for ($i = 0, $level = ob_get_level(); $i < $level; $i++)
        {
            ob_end_flush();
        }
    ?>

    <HTML lang="en">
    <HEAD>
        <META http-equiv=x-ua-compatible" content="IE=EmulateIE10">
        <TITLE><?php echo $this->HTML_TITLE;?></TITLE>
        <LINK rel="icon" href="<?php echo $this->ICON_FILE;?>">
        <BASE href="<?php echo $this->BASE_HREF;?>">

        <META NAME="Description"         CONTENT="<?php echo $this->HTML_TITLE?>">
        <META NAME="Keywords"            CONTENT="<?php echo $this->META_KEYWORDS?>">
        <META NAME="robots"              CONTENT="no, none">
        <META NAME="resource-type"       CONTENT="document">
        <META NAME="distribution"        CONTENT="<?php echo $this->META_DISTRIBUTION?>">
        <META NAME="country"             CONTENT="USA">
        <META HTTP-EQUIV="PUBLIC"        CONTENT="YES">
        <META NAME="security"            CONTENT="public">
        <META NAME="CATEGORY"            CONTENT="Tool">
        <META NAME="MS.LOCALE"           CONTENT="EN-US">
        <META HTTP-EQUIV="LANGUAGE"      CONTENT="ENGLISH">
        <META NAME="Usergroup"           CONTENT="Public">
        <META NAME="rating"              CONTENT="General">
        <META HTTP-EQUIV="EXPIRES"       CONTENT="0">
        <META NAME="REVISIT-AFTER"       CONTENT="15 days">
        <META http-equiv="Cache-Control" CONTENT="no-cache">
        <META HTTP-EQUIV="content-type"  CONTENT="text/html;charset=iso-8859-1">
        <META NAME="creation_date"       CONTENT = "<?php echo date('m-d-Y')?>">
        <META NAME="company"             CONTENT="<?php echo $this->META_COMPANY?>">
        <META NAME="contact person"      CONTENT="<?php echo $this->META_CONTACT_PERSON?>">
        <META NAME="owner"               CONTENT="<?php echo $this->META_CONTACT_EMAIL?>">
        <META NAME="Reply-To"            CONTENT="<?php echo $this->META_CONTACT_EMAIL?>">
        <META NAME="alias"               CONTENT="<?php $this->WWW?>">
        <META NAME="Copyright"           CONTENT="<?php echo $this->META_COPYRIGHT?>">
        <META NAME="Copyright Law"       CONTENT="<?php echo $this->META_COPYRIGHT_LAW?>">

        <?php
        //
        // This is useful for auto-refresh or URL redirects.
        // If META_REFRESH and META_REFRESH are not null then write out the meta
        // data used to refresh or redirect a web page.
        //
        if (isset($this->META_REFRESH) && strlen($this->META_REFRESH) > 0)
        {
            printf("<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"%d; url=%s\">",
                $this->REFRESH_RATE, $this->META_REFRESH);
        }
        ?>

        <link rel="stylesheet" type="text/css" href="css/w2ui-1.4.2.min.css">

        <!-- Page loading image -->
        <style type="text/css">
            .no-js #loader
            {
                display:    none;
            }
            .js #loader
            {
                display:    block;
                position:   absolute;
                left:       100px;
                top:        0;
            }
            .se-pre-con
            {
                position:   fixed;
                left:       0px;
                top:        0px;
                width:      100%;
                height:     100%;
                z-index:    9999;
                background: url(<?php echo $this->WWW;?>/images/Preloader_2.gif) center no-repeat #fff;
            }
        </style>


        <script src="<?php echo $this->WWW;?>/js/jquery-2.1.4.js"></script>
        <script src="<?php echo $this->WWW;?>/js/w2ui-1.4.3.js"></script>

        <!- script src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"><!/script>
        <!- script src="http://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.2/modernizr.js"><!/script>

        <script type="text/javascript">
            $(window).load(function() {
                // Animate loader off screen
                $(".se-pre-con").fadeOut("slow");
            });

            function escapeHTML(html)
            {
                if (typeof html !== 'string')
                    return html;

                var fn = function(tag)
                {
                    var charsToReplace = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&#34;'
                    };

                    return charsToReplace[tag] || tag;
                };

                return html.replace(/[&<>"]/g, fn);
            }

            $(document).ready(
                function()
                {
                    <?php
                    if ($_SESSION['CALL_HTML_TIMEZONE'] === "YES")
                    {
                        $_SESSION['CALL_HTML_TIMEZONE'] = "NO";
                    ?>
                        var tz = jstz.determine();
                        $.ajax(
                            {
                                type: "GET",
                                url:  "<?php echo $this->WWW; ?>/servers/html_timezone.php",
                                data: 'timezone=' + tz.name(),
                                success: function()
                                {
                                    f1.action = "<?php echo $this->WWW; ?>/index.php";
                                    f1.submit();
                                }
                            }
                        );
                    <?php
                    }
                    ?>
                }
            );
        </script>
    </HEAD>

    <?php
        //
        // If $this->error has a message then it displayed in a popup dialog box in the users browser.
        //
        $body_options = '';

        if (isset($this->error) && strlen($this->error) > 0)
        {
            if (isset($this->ON_LOAD) && strlen($this->ON_LOAD) > 0)
            {
                $body_options = sprintf(" alert('%s');%s>\n", $this->error, $this->ON_LOAD);
            }
            else
            {
                $body_options = sprintf(" alert('%s');%s>\n", $this->error);
            }
        }
        else
        {
            if (isset($this->ON_LOAD) && strlen($this->ON_LOAD) > 0)
            {
                $body_options = sprintf(" onload=\"%s\"", $this->ON_LOAD);
            }
        }
    ?>

    <BODY<?php echo $body_options;?>>
    <div class="""se-pre-con"></div><!-- Page Loading image -->

    <?php
    $form_options = '';

    if (strlen($this->PROGRAM) === 0)
    {
        $form_options = sprintf(" name=\"%s\" method\"%s\"", $this->FORM_NAME, $this->METHOD);
    }
    else
    {
        $form_options = sprintf(" name=\"%s\" method=\"%s\" action=\"%s\"", $this->FORM_NAME, $this->METHOD, $this->PROGRAM);
    }
    ?>

    <FORM<?php echo $form_options;?>>

        <!-- LAYOUT -->
        <DIV id="layout" style="width: 100%; height: 100%;"></DIV>

    <SCRIPT type="text/javascript">
        $(window).load(function()
        {
            // Animate loader off screen
            $(".se-pre-con").fadeOut("slow");
        });
    </SCRIPT>

    <script type="text/javascript">
        $(function () {
            var pstyle = 'border: 1px solid #dfdfdf; padding: 5px;';
            $('#layout').w2layout({
                name: 'layout',
                panels: [
                    { type: 'top',     size: 50,    resizable: true, hidden: false, style: pstyle,
                        toolbar: {
                            items: [
                                { type: 'check',  id: 'item1', caption: 'Check', icon: 'fa-check', checked: true },
                                { type: 'break',  id: 'break0' },
                                { type: 'menu',   id: 'item2', caption: 'Menu', icon: 'fa-table', count: 17, items: [
                                    { text: 'Item 1', icon: 'fa-camera', count: 5 },
                                    { text: 'Item 2', icon: 'fa-picture', disabled: true },
                                    { text: 'Item 3', icon: 'fa-glass', count: 12 }
                                ]},
                                { type: 'break', id: 'break1' },
                                { type: 'radio',  id: 'item3',  group: '1', caption: 'Radio 1', icon: 'fa-star', checked: true },
                                { type: 'radio',  id: 'item4',  group: '1', caption: 'Radio 2', icon: 'fa-heart' },
                                { type: 'break', id: 'break2' },
                                { type: 'drop',  id: 'item5', caption: 'Drop Down', icon: 'fa-plus', html: '<div style="padding: 10px">Drop down</div>' },
                                { type: 'break', id: 'break3' },
                                { type: 'html',  id: 'item6',
                                    html: '<div style="padding: 3px 10px;">'+
                                    ' Input:'+
                                    '    <input size="10" style="padding: 3px; border-radius: 2px; border: 1px solid silver"/>'+
                                    '</div>'
                                },
                                { type: 'spacer' },
                                { type: 'button',  id: 'item7',  caption: 'Item 5', icon: 'fa-flag' }
                            ]
                        }
                    },

                    { type: 'left',    size: 200,   resizable: true, hidden: true,  style: pstyle, content: 'left', title: 'Options' },
                    { type: 'main',                                  hidden: false, style: pstyle,
                        content: '<div id="grid" style="width: 100%; height: 400px; overflow: hidden;"></div>'
                    },
                    { type: 'preview', size: '50%', resizable: true, hidden: true,  style: pstyle, content: 'preview', title: 'Preview' },
                    { type: 'right',   size: 200,   resizable: true, hidden: true,  style: pstyle, content: 'right', title: 'Help' },
                    { type: 'bottom',  size: 50,    resizable: true, hidden: false, style: pstyle, content: 'bottom' }
                ]
            });

            $('#grid').w2grid({
                name:   'grid',
                header: 'List of Names',
                url:    'data/list.json',
                method: 'GET', // need this to avoid 412 error on Safari
                show: {
                    header: true,
                    toolbar: true,
                    footer: true,
                    lineNumbers: true,
                    selectColumn: true,
                    expandColumn: true
                },
                columns: [
                    { field: 'fname', caption: 'First Name', size: '30%' },
                    { field: 'lname', caption: 'Last Name', size: '30%' },
                    { field: 'email', caption: 'Email', size: '40%' },
                    { field: 'sdate', caption: 'Start Date', size: '120px' },
                ],
                searches: [
                    { type: 'int',  field: 'recid', caption: 'ID' },
                    { type: 'text', field: 'fname', caption: 'First Name' },
                    { type: 'text', field: 'lname', caption: 'Last Name' },
                    { type: 'date', field: 'sdate', caption: 'Start Date' }
                ],
                onExpand: function (event) {
                    $('#'+event.box_id).html('<div style="padding: 10px">Expanded content</div>').animate({ 'height': 100 }, 100);
                }
            });

        });
    </script>

    </FORM>
    </BODY>
    </HTML>

    <?php
    }

    /** @fn page_hits()
     *  @brief Opens the counter page hits file for the calling program, extracts the hit number, increments the hit number
     *  @brief and writes the new hit number to the counter file.
     *  @brief HTML is then generated to display the page hit images in the proper sequence.
     *  @brief page_hits() called from footer().
     *  @return null
     */
    public function page_hits()
    {
        $page_hit_count = 0;
        $page_hit_file = $this->COUNTER_DIR . $this->COUNTER_FILE;

        if (file_exists($page_hit_file))
        {
            if (($fp = fopen($page_hit_file, "r")) === false)
            {
                $page_hit_count = 0;
                $trace = debug_backtrace();
                trigger_error(
                    'Cannot open file for read: ' . $page_hit_file .
                    ' in ' . $trace[0]['file'] .
                    ' on line ' . $trace[0]['line'],
                    E_USER_NOTICE);
                return;
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
            $trace = debug_backtrace();
            trigger_error(
                'Cannot open file for write: ' . $page_hit_file .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line'],
                E_USER_NOTICE);
        }

        fprintf($fp, "%d\n", $count);
        fclose($fp);

        printf("<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse\" bordercolor=\"#111111\">\n");
        printf("<tr>\n");
        printf("<td><b><font size=\"2\" color=\"#800000\">Page Hits:&nbsp;</font></b></td>\n");

        $str = sprintf("%1$08d", $page_hit_count);
        $len = strlen($str);

        $str2 = str_split($str);
        reset($str2);

        while (list(, $value) = each($str2))
        {
            echo "<td><img border=0 src=images/$value.gif width=9 height=13></td>";
        }

        printf("</tr>\n");
        printf("</table>\n");
    }

    /** @fn isButton()
     *  @brief Helper function used to determine if a button has been pressed on a web page.
     *  @brief Buttons must have name set to button in order for this function to work.
     *  @param $what is button name (i.e. value='Okay') isButton('Okay')
     *  @return true = button was pressed, false = button was not pressed
     */
    public function isButton($what)
    {
        if (isset($_REQUEST['button']) && $_REQUEST['button'] == $what)
        {
            return true;
        }

        return false;
    }

    /** @fn set_refresh_seconds()
     *  @brief Used to set the meta refresh time interval after the web page loads.
     *  @brief If using for page redirects, set the refresh time to 0.
     *  @param $what is the interval in seconds.
     *  @return null
     */
    public function set_refresh_seconds($what)
    {
        $this->REFRESH_RATE = $what;
    }

    /** @fn set_meta_refresh()
     *  @brief Set the META_REFRESH to the URL we want to refresh when the timer elapses.
     *  @param $what is the URL
     *  @return null
     */
    public function set_meta_refresh($what)
    {
        $this->META_REFRESH = $what;
    }

    /** @fn set_program()
     *  @brief Used to set the program and options in a form statement
     *  @param $what is the action in a form statement
     *  @return null
     */
    public function set_program($what)
    {
        if (isset($this->WWW))
        {
            $this->PROGRAM = $this->WWW . '/' . $what;
        }
        else
        {
            $this->PROGRAM = $what;
        }
    }

    /** @fn set_on_load()
     *  @brief Sets the bodys onload= to perform some kind of javascript action.
     *  @param $what is the javascript action (i.e. set_on_load("alert('Hello World!')")
     *  @return null
     */
    public function set_on_load($what)
    {
        $this->ON_LOAD = $what;
    }

    /** @fn stop()
     *  @brief Output a Stop graphic, file, function, line number and message.
     *  @param $file File name of calling function. __FILE__
     *  @param $func Function name of calling module. __FUNCTION__
     *  @param $line Line number in File of calling function. __LINE__
     *  @param $msg This is the error message
     *  @return null
     */
    public function stop($file, $func, $line, $msg)
    {
        $argv = func_get_args();
        $file = array_shift($argv);
        $func = array_shift($argv);
        $line = array_shift($argv);
        $format = array_shift($argv);
        $what = vsprintf($format, $argv);

        // If your program already called top() then this function will just return.

        printf("<p align=\"center\"><img border=\"0\" src=\"images/stop.gif\" width=\"75\" height=\"74\"></p>\n");

        // Some php code may not be in a function
        if (empty($func))
            printf("<p align=\"center\">%s %d: %s</p>\n", basename($file), $line, $what);
        else
            printf("<p align=\"center\">%s %s() %d: %s</p>\n", basename($file), $func, $line, $what);

        exit();
    }

    /** @fn dump()
     *  @brief Dump HTML PHP global variables for evaluation.
     *  @brief Used for debugging your web pages.
     *  @return null
     */
    public function dump()
    {
        ?>
        <pre><!-- DUMPING HTML PHP SUPER GLOBAL VARIABLES FOR EVALUATION -->
            <?php
            printf("parm_count=%d\n", $this->parm_count);
            printf("\nparm: ");
            print_r($this->parm);
            printf("\n_POST: ");
            print_r($_POST);
            printf("\n_GET: ");
            print_r($_GET);
            printf("\n_REQUEST: ");
            print_r($_REQUEST);
            printf("\n_SERVER: ");
            print_r($_SERVER);
            printf("\n_SESSION: ");
            print_r($_SESSION);
            ?>
        </pre><!-- END: DUMP -->
        <?php
    }
}
?>
