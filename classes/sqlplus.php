<?php
/**
 * @package    CCT
 * @file       sqlplus.php
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
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

/** @class sqlplus
 *  @brief Used in CLI PHP shell functions found in /xxx/stt/bin/make_xxx.php that need to call sqlplus
 */
class sqlplus
{
	//
	// Properties
	//
	var $dbErrMsg = "";

	var $ORACLE_LABEL = "";      // from db.acc
	var $ORACLE_HOSTNAME = "";   // from db.acc

	var $ORACLE_ENVIRON = "";
	var $ORACLE_USERID = "";
	var $ORACLE_PASSWORD = "";
	var $ORACLE_SID = "";
	var $ORACLE_HOME_NAME = "";
	var $ORABASE_EXEC = "";
	var $ORACLE_BASE = "";
	var $ORACLE_HOME = "";
	var $ORACLE_DOC = "";
	var $ORACLE_TERM = "";
	var $ORACLE_HOME_LISTNER = "";
	var $PATH = "";
	var $LD_LIBRARY_PATH = "";
	var $SHLIB_PATH = "";
	var $LD_RUN_PATH = "";
	var $ORAKITPATH = "";
	var $NLS_LANG = "";
	var $CLASSPATH = "";
	var $ORACLE_SERVER = "";
	var $ORACLE_AUTOREG = "";
	var $ORATAB_FAIL = "";
	var $TNS_ADMIN = "";
	var $SQLPATH = "";
	var $VER = "";
	var $KBITS = "";
	var $OS = "";
	var $OGROUP = "";
	var $EDITOR = "";

	/** @fn __construct()
	 *  @brief Class constructor - called once when the class is created.
	 *  @return void
	 */
	public function __construct($database = NULL)
	{
		date_default_timezone_set('America/Denver');
		$this->read_oracle_password_file();
		$this->setup_oracle_environment();
	}

	/** @fn __destruct()
	 *  @brief Class destructor called with object is released.
	 *  @return void
	 */
	function __destruct()
	{
	}

	/** @fn sqlplus($input_file)
	 *  @brief Login to sqlplus and run the $input_file (SQL).
	 *  @param $input_file is the sqlfile containing SQLPL1 code
	 *  @return true or false (true = success)
	 */
	public function sqlplus($input_file = NULL)
	{
		if (strlen($this->dbErrMsg) > 0) {
			// Error will be posted in $this->dbErrMsg
			return false;
		}

		if ($input_file == NULL) {
			$this->dbErrMsg = sprintf("%s %s %d: Syntax error: $input_file cannot be NULL", __FILE__, __FUNCTION__, __LINE__);
			return false;
		}

		if (!file_exists($input_file)) {
			$this->dbErrMsg = sprintf("File does not exist: %s", $input_file);
			return false;
		}

		$cmd = sprintf("%s/bin/sqlplus %s/%s @%s", $this->ORACLE_HOME, $this->ORACLE_USERID, $this->ORACLE_PASSWORD, $input_file);

		# $cmd = sprintf("sqlplus %s/%s @%s", $this->ORACLE_USERID, $this->ORACLE_PASSWORD, $input_file);

		printf("\nRunning: %s\n", $cmd);

		$fp = popen($cmd, "r");

		if ($fp) {
			while (($buffer = fgets($fp, 4096)) !== false) {
				// Remove multiple spaces, tabs and newlines if present
				$hold = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $buffer);
				printf("%s\n", $hold);
			}
		} else {
			printf("Pipe failed to execute: %s\n", $cmd);
			return false;
		}

		pclose($fp);

		return true;
	}

	/** @fn read_oracle_password_file($database)
	 *  @brief Retrieve the oracle userid and password from the session cache or oracle account password file
	 *  @param $database is the server's IP address
	 *  @return true or false (true is success)
	 */
	private function read_oracle_password_file()
	{
		$filename = '';
		$ip_addr = gethostbyname(gethostname());

		switch ( $ip_addr )
		{
			case 'localhost':  // LENOVO
			case '127.0.0.1':
				$this->ORACLE_SID = "localhost/ORCL";
				$this->ORACLE_USERID = "cct";
				//$filename = "/opt/ibmtools/cct7/etc/cct.txt";
				$filename = "/opt/ibmtools/private/db.acc";
				$label = "cct_lenovo";
				break;

			case 'lxomp47x.corp.intranet':
			case 'cct.corp.intranet':
			case '151.117.146.20':
				$this->ORACLE_SID = "CMPTOOLP";
				$this->ORACLE_USERID = "cct";
				//$filename = "/opt/ibmtools/cct7/etc/cct.txt";
				$filename = "/opt/ibmtools/private/db.acc";
				$label = "cct_prod";
				break;

			case 'vlodts022.test.intranet':
			case 'cct.test.intranet':
			case '151.117.209.10':
				$this->ORACLE_SID = "CMPTOOLT";
				$this->ORACLE_USERID = "cct";
				//$filename = "/opt/ibmtools/cct7/etc/cct.txt";
				$filename = "/opt/ibmtools/private/db.acc";
				$label = "cct_test";
				break;

			case 'lxomp11m.qintra.com':
			case 'cct.qintra.com':
			case '151.117.157.53':
				$this->ORACLE_SID = "ORION";
				$this->ORACLE_USERID = "cct";
				$filename = "/xxx/cct6/etc/cct.txt";
				break;

			case 'lxomt12m.dev.qintra.com':
			case 'cct.dev.qintra.com':
			case '151.117.41.173':
				$this->ORACLE_SID = "THOR";
				$this->ORACLE_USERID = "cct";
				$filename = "/xxx/cct6/etc/cct.txt";
				break;

			default:
				$this->dbErrMsg = "Cannot determine where oracle password file is located. (See: dbms.php read_oracle_password())";
				return false;
		}

		/*
		if (file_exists($filename))
		{
			if (($fp = fopen($filename, "r")) === false)
			{
				$this->dbErrMsg = sprintf("%s %s %s: Cannot open for read: %s", __FILE__, __FUNCTION__, __LINE__, $filename);
				return false;
			}

			$this->oracle_password = trim(fread($fp, 80));
			fclose($fp);
			return true;
		}
		*/

		// db.acc = cct_prod|lxomp47x.corp.intranet:1532|ibmtoolp|cct|candy4Kids|Oracle

		if (file_exists($filename))
		{
			if (($fp = fopen($filename, "r")) === false)
			{
				$this->dbErrMsg = sprintf("%s %s %s: Cannot open for read: %s", __FILE__, __FUNCTION__, __LINE__, $filename);
				return false;
			}

			while ( ($buffer = fgets($fp, 2048)) !== false )
			{
				$field = explode("|", $buffer);

				if (count($field) >= 5)
				{
					$this->ORACLE_LABEL    = trim($field[0]);
					$this->ORACLE_HOSTNAME = trim($field[1]);
					//$this->ORACLE_SID      = trim($field[2]);
					//$this->ORACLE_USERID   = trim($field[3]);
					$this->ORACLE_PASSWORD = trim($field[4]);
				}

				if (strcmp($label, $this->ORACLE_LABEL) == 0)
				{
					break;
				}
			}

			fclose($fp);

			return true;
		}

		$this->dbErrMsg = sprintf("%s %s %s: File does not exist: %s", __FILE__, __FUNCTION__, __LINE__, $filename);
		return false;
	}

	/** @fn setup_oracle_environment()
	 *  @brief /xxx/apache/bin/envvars has the Oracle database environment defined, but if you are running a script
	 *  from a php (shell) script (see: cct7_auto.php) in 'cli' mode we need to setup Oracle's database environment.
	 *  So this function is called from the __construct() function when in 'cli' mode to get the environment setup.
	 *  @return void
	 */
	private function setup_oracle_environment()
	{
		//
		// Only called when this is a CLI program
		//
		if (PHP_SAPI === 'cli')
		{
			$SERVER_NAME = "";
		}
		else
		{
			$SERVER_NAME = $_SERVER['SERVER_NAME'];
		}

		$hostname = gethostname();

		//
		// Is CCT running on my Levovo laptop?
		//
		if ($hostname === 'oc5723165606' || $hostname === 'oc5723165606.ibm.com' || $SERVER_NAME === "cct.localhost")
		{
			$PATH         = getenv("PATH");
			$ORABASE_EXEC = "/home/gparkin/app/gparkin/product/12.1.0/dbhome_1/bin/orabase";
			$ORACLE_BASE  = "/home/gparkin/app/gparkin";
			$ORACLE_HOME  = "/home/gparkin/app/gparkin/product/12.1.0/dbhome_1";
			$ORACLE_SID   = "orcl";

			putenv("OPTIND=1");
			putenv("ORABASE_EXEC=" . $ORABASE_EXEC);
			putenv("ORACLE_BASE="  . $ORACLE_BASE);
			putenv("ORACLE_HOME="  . $ORACLE_HOME);
			putenv("ORACLE_SID="   . $ORACLE_SID);
			putenv("ORAENV_ASK=NO");
			putenv("ORAHOME="      . $ORACLE_HOME);
			putenv("PATH=.:" . $PATH . ":" . $ORACLE_HOME . "/bin:/opt/ibmtools/bin:/opt/lampp/bin");
			putenv("LD_LIBRARY_PATH=" . $ORACLE_HOME . "/lib64:" . $ORACLE_HOME . "/jdbc:" . $ORACLE_HOME . "/lib");
			putenv("ORACLE_USER=cct");
			putenv("ORACLE_PASSWORD=candy4Kids");
		}
		else if ($hostname === 'lxomp47x' || $hostname  === 'lxomp47x.corp.intranet' || $SERVER_NAME === "cct.corp.intranet")
		{
printf("hostname: %s\n", $hostname);

			//
			// ORACLE_BASE=/db/oracle/product/12102_64
			// ORACLE_HOME=/db/oracle/product/12102_64
			// ORACLE_SID=ibmtoolp
			// ORAENV_ASK=NO
			// LD_LIBRARY_PATH=/db/oracle/product/12102_64/lib:/opt/ibmtools/src/lib
			// PATH=.:/usr/local/bin:/usr/bin:/usr/local/sbin:/usr/sbin:/db/oracle/product/12102_64/bin:/u/gparkin/bin:/opt/ibmtools/bin:/usr/local/bin:/db/oracle/product/12102_64/bin:/opt/lampp/bin
			//
			$PATH         = getenv("PATH");
			$ORABASE_EXEC = "/db/oracle/product/12102_64/bin/orabase";
			$ORACLE_BASE  = "/db/oracle/product/12102_64";
			$ORACLE_HOME  = "/db/oracle/product/12102_64";
			$ORACLE_SID   = "ibmtoolp";

			$this->PATH            = $PATH;
			$this->ORABASE_EXEC    = $ORABASE_EXEC;
			$this->ORACLE_HOME     = $ORACLE_HOME;
			$this->ORACLE_BASE     = $ORACLE_BASE;
			$this->ORACLE_SID      = $ORACLE_SID;
			$this->LD_LIBRARY_PATH = $ORACLE_HOME . "/lib64:" . $ORACLE_HOME . "/jdbc:" . $ORACLE_HOME . "/lib";

			putenv("OPTIND=1");
			putenv("ORABASE_EXEC=" . $ORABASE_EXEC);
			putenv("ORACLE_BASE="  . $ORACLE_BASE);
			putenv("ORACLE_HOME="  . $ORACLE_HOME);
			putenv("ORACLE_SID="   . $ORACLE_SID);
			putenv("ORAENV_ASK=NO");
			putenv("ORAHOME="      . $ORACLE_HOME);
			putenv("PATH=.:" . $PATH . ":" . $ORACLE_HOME . "/bin:/opt/ibmtools/bin:/opt/lampp/bin");
			putenv("LD_LIBRARY_PATH=" . $ORACLE_HOME . "/lib64:" . $ORACLE_HOME . "/jdbc:" . $ORACLE_HOME . "/lib");
			putenv("ORACLE_USER=cct");
			putenv("ORACLE_PASSWORD=candy4Kids");
		}
		else if ($hostname === 'vlodts022' || $hostname  === 'vlodts022.test.intranet' || $SERVER_NAME === "cct.test.intranet")
		{
			//
			// Same as lxomp47x except ORACLE_SIDE = ibmtoolt
			//
			$PATH         = getenv("PATH");
			$ORABASE_EXEC = "/db/oracle/product/12102_64/bin/orabase";
			$ORACLE_BASE  = "/db/oracle/product/12102_64";
			$ORACLE_HOME  = "/db/oracle/product/12102_64";
			$ORACLE_SID   = "ibmtoolt";

			$this->PATH            = $PATH;
			$this->ORABASE_EXEC    = $ORABASE_EXEC;
			$this->ORACLE_HOME     = $ORACLE_HOME;
			$this->ORACLE_BASE     = $ORACLE_BASE;
			$this->ORACLE_SID      = $ORACLE_SID;
			$this->LD_LIBRARY_PATH = $ORACLE_HOME . "/lib64:" . $ORACLE_HOME . "/jdbc:" . $ORACLE_HOME . "/lib";

			putenv("OPTIND=1");
			putenv("ORABASE_EXEC=" . $ORABASE_EXEC);
			putenv("ORACLE_BASE="  . $ORACLE_BASE);
			putenv("ORACLE_HOME="  . $ORACLE_HOME);
			putenv("ORACLE_SID="   . $ORACLE_SID);
			putenv("ORAENV_ASK=NO");
			putenv("ORAHOME="      . $ORACLE_HOME);
			putenv("PATH=.:" . $PATH . ":" . $ORACLE_HOME . "/bin:/opt/ibmtools/bin:/opt/lampp/bin");
			putenv("LD_LIBRARY_PATH=" . $ORACLE_HOME . "/lib64:" . $ORACLE_HOME . "/jdbc:" . $ORACLE_HOME . "/lib");
			putenv("ORACLE_USER=cct");
			putenv("ORACLE_PASSWORD=candy4Kids");
		}
		else
		{
			//
			// Original lxomp11m and lxomt12m Oracle server configuration.
			//
			$ORACLE_HOME_NAME = 'ora10204_64';
			putenv('ORACLE_HOME_NAME=' . $ORACLE_HOME_NAME);

			$ORACLE_BASE = '/opt/dbms/oracle';
			putenv('ORACLE_BASE=' . $ORACLE_BASE);

			$ORACLE_HOME = '/opt/dbms/oracle/product/ora10204_64';
			putenv('ORACLE_HOME=' . $ORACLE_HOME);

			$ORACLE_HOME_LISTNER = $ORACLE_HOME;
			putenv('ORACLE_HOME_LISTNER=' . $ORACLE_HOME_LISTNER);

			$ORACLE_SID = 'tla';
			putenv('ORACLE_SID=' . $ORACLE_SID);

			$ORACLE_DOC = $ORACLE_HOME . '/doc';
			putenv('ORACLE_DOC=' . $ORACLE_DOC);

			$ORACLE_TERM = 'xterm';
			putenv('ORACLE_TERM=' . $ORACLE_TERM);

			$PATH = $ORACLE_HOME . '/bin:' .
				$ORACLE_HOME . '/Apache/Apache/bin:' .
				'/usr/bin:/etc:/usr/sbin:/usr/ccs/bin:/usr/ucb:/usr/local/bin:/bin:/usr/bin:/usr/X11R6/bin:.';
			putenv('PATH=' . $PATH);

			$LD_LIBRARY_PATH = $ORACLE_HOME . '/lib64:' .
				$ORACLE_HOME . '/jdbc/lib:' .
				$ORACLE_HOME . '/lib:' .
				'/usr/lib:/usr/local/lib';
			putenv('LD_LIBRARY_PATH=' . $LD_LIBRARY_PATH);


			// Per Oracle, SHLIB_PATH should not include the 64bit lib directories
			$SHLIB_PATH = $ORACLE_HOME . '/jdbc/lib:' .
				$ORACLE_HOME . '/lib:' .
				'/usr/lib:/usr/local/lib';
			putenv('SHLIB_PATH=' . $SHLIB_PATH);

			$LD_RUN_PATH = $LD_LIBRARY_PATH;
			putenv('LD_RUN_PATH=' . $LD_RUN_PATH);

			$ORAKITPATH = $ORACLE_HOME . '/oraterm/admin/resource';
			putenv('ORAKITPATH=' . $ORAKITPATH);

			$NLS_LANG = 'american_america.us7ascii';
			putenv('NLS_LANG=' . $NLS_LANG);

			// Clients may need to properly set and uncomment the next 2 lines
			// NLS_DATE_FORMAT='DD-MON-RRRR'
			// export NLS_DATE_FORMAT
			// Uncomment the next two lines for Developer/2000
			// ORA_NLS33=${ORACLE_HOME}/ocommon/nls/admin/datad2k
			// export ORA_NLS33
			// ORA_NLS32=${ORACLE_HOME}/ocommon/nls/admin/data
			// export ORA_NLS32

			$CLASSPATH = $ORACLE_HOME . '/JRE:' .
				$ORACLE_HOME . '/jlib:' .
				$ORACLE_HOME . '/lib64:' .
				$ORACLE_HOME . '/lib:' .
				$ORACLE_HOME . '/jdbc/lib:' .
				$ORACLE_HOME . '/sqlj/lib:' .
				$ORACLE_HOME . '/classes/lib:' .
				$ORACLE_HOME . '/javavm/lib:/usr/java/lib';
			putenv('CLASSPATH=' . $CLASSPATH);

			$ORACLE_SERVER = 'T';
			putenv('ORACLE_SERVER=' . $ORACLE_SERVER);

			//
			// 17-MAY-2000  CSCHEUR - Added next ENV variable for Forms/Reports 6i
			//
			$ORACLE_AUTOREG = '/opt/dbms/oracle/product/ora10204_64/guicommon/tk60/admin';
			putenv('ORACLE_AUTOREG=' . $ORACLE_AUTOREG);

			$ORATAB_FAIL = 'true';
			putenv('ORATAB_FAIL=' . $ORATAB_FAIL);

			// Uncomment/Edit these lines to if /tmp is not large enough for relinks
			// TEMP=/opt/dbms/tmp
			// TMP=${TEMP}
			// TMPDIR=${TEMP}
			// TMP_DIR=${TEMP}
			// TEMPDIR=${TEMP}
			// export TEMP TMP TEMPDIR TMPDIR TMP_DIR

			$TNS_ADMIN = $ORACLE_HOME . '/network/admin';
			putenv('TNS_ADMIN=' . $TNS_ADMIN);

			// LD_ASSUME_KERNEL=2.2.5
			// export LD_ASSUME_KERNEL
			// unset OBJ_FORM and TWO_TASK
			putenv('OBJ_FORM');
			putenv('TWO_TASK');

			$SQLPATH = '.:/u/oracle';
			putenv('SQLPATH=' . $SQLPATH);

			// These variables are for the Build Response File script
			$VER = 'ora10204_64';
			putenv('VER=' . $VER);

			$KBITS = '64';
			putenv('KBITS=' . $KBITS);

			$OS = 'Linux';
			putenv('OS=' . $OS);

			$OGROUP = 'dba';
			putenv('OGROUP=' . $OGROUP);

			$EDITOR = 'vi';
			putenv('EDITOR=' . $EDITOR);
		}
	}
}
?>
