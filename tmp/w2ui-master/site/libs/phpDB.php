<?
/***********************************************************************************
*   Class: phpDBConnection
*   This class provides interface for database connections
*   Only MySQL and PostreSQL databases are supported
*/

class phpDBConnection {
	public $dbConn	= null;
	public $debug 	= false;
	public $dbType;
	public $dbVersion;
	public $dbName;
	public $res_sql;
	public $res_data;
	public $res_errMsg;
	public $res_affectedRows;
	public $res_rowCount;
	public $res_fieldCount;
	public $res_fields;
	public $res_fieldsInfo;
	
	// -- Constructor
	function __construct($dbType) {
		$dbType = strtolower($dbType);
		if ($dbType != 'postgres' && $dbType != 'mysql') {
			die('<b>ERROR:</b> Only two database types are supported, postgres and mysql... <b>phpDB.php, line 27</b>');
		}
		$this->dbType = $dbType;
	}
	
	// -- Clean up
	function __destruct() {
		if ($this->dbConn == null) return;
		if ($this->dbType == 'postgres') { @pg_close($this->dbConn);}
		if ($this->dbType == 'mysql') {  @mysql_close($this->dbConn); }
	}
	
	// -- Connect to the db
	public function connect($dbIP, $dbUser, $dbPass, $dbName, $dbPort=null) {		
		// check parameters
		if ($dbIP   == '') die('<b>ERROR:</b> no database host provided... <b>system/libs/phpDB.php, line 32</b>');
		if ($dbName == '') die('<b>ERROR:</b> no database name provided... <b>system/libs/phpDB.php, line 33</b>');
		if ($dbUser == '') die('<b>ERROR:</b> no database user provided... <b>system/libs/phpDB.php, line 34</b>');
		//if ($dbPass == '') die('no database password provided');
		$this->dbName = $dbName;
		
		// connect
		if ($this->dbType == 'postgres') {
			$this->dbConn = pg_connect("host=$dbIP ".($dbPort != null ? "port=$dbPort " : "")."dbname=$dbName user=$dbUser password=$dbPass");
			if (!$this->dbConn) {
				$this->dbConn = null;
				print("<b>ERROR:</b> Cannot connect to postgres.<br>");
				return false;
			}
			$this->dbVersion = pg_version($this->dbConn);
			$this->dbVersion['host'] = pg_host($this->dbConn);
		}
		if ($this->dbType == 'mysql') {
			$this->dbConn = mysql_connect($dbIP.($dbPort != null ? ":".$dbPort : ""), $dbUser, $dbPass);
			if (!$this->dbConn) {
				$this->dbConn = null;
				print("<b>ERROR:</b> Cannot connect to mysql.<br>");
				return false;
			}
			mysql_select_db($dbName);
			$this->dbVersion = Array();
			$this->dbVersion['client'] 	 = mysql_get_client_info();
			$this->dbVersion['protocol'] = mysql_get_proto_info($this->dbConn);
			$this->dbVersion['server'] 	 = mysql_get_server_info($this->dbConn);
			$this->dbVersion['host'] 	 = mysql_get_host_info($this->dbConn);
		}
	}	

	// -- Execute SQL
	public function execute($sql) {
		global $sys_dbPrefix;
		global $ses_userid;

		// hide errors
		$ini_err = ini_get('display_errors');
		ini_set('display_errors', 0);
		$res = false;
		
		$this->res_errMsg = null;
		
		// --- process sql
		if ($this->dbType == 'postgres') {
			$this->res_data = pg_query($this->dbConn, $sql);
			if (!$this->res_data) {
				$this->res_errMsg 		= pg_last_error($this->dbConn);
			} else {
				$this->res_errMsg 		= pg_result_error($this->res_data);
				$this->res_affectedRows = pg_affected_rows($this->res_data);
				$this->res_rowCount		= pg_num_rows($this->res_data);
				$this->res_fieldCount	= pg_num_fields($this->res_data);
				$res = new phpRecordSet($this->dbType, $this->res_data, $this->res_rowCount, $this->res_fieldCount);
				// -- parse field names
				for ($i=0; $i<$this->res_fieldCount; $i++) {
					$this->res_fields[$i] = pg_field_name($this->res_data, $i);
					$this->res_fieldsInfo[$i] = Array();
					$this->res_fieldsInfo[$i]['type']    = pg_field_type($this->res_data, $i);;
					$this->res_fieldsInfo[$i]['len']     = pg_field_size($this->res_data, $i);;
					$this->res_fieldsInfo[$i]['is_null'] = pg_field_is_null($this->res_data, $i);;
					$this->res_fieldsInfo[$i]['prt_len'] = pg_field_prtlen($this->res_data, $i);;
				}
			}
			// log error - do not log errors in db - there are 5 mill of them now there.
			// if ($this->res_errMsg != '') {
			// 	$userid = ($ses_userid != null ? $ses_userid : 'null');
			// 	$ssql = "INSERT INTO ".$sys_dbPrefix."log_error(domain, url, userid, sql, error)
			// 			 VALUES('".$_SERVER["HTTP_HOST"]."', '".pg_escape_string($_SERVER["REQUEST_URI"])."', $userid, 
			// 				'".pg_escape_string($sql)."', '".pg_escape_string($this->res_errMsg)."');";
			// 	pg_query($this->dbConn, $ssql);
			// }
		}
		
		// --- mysql
		if ($this->dbType == 'mysql') {
			$this->res_data = mysql_query($sql, $this->dbConn);
			if (!$this->res_data) {
				$this->res_errMsg 		= mysql_error($this->dbConn);
			} else {
				@$this->res_errMsg 		 = mysql_error($this->res_data);
				@$this->res_affectedRows = mysql_affected_rows($this->res_data);
				@$this->res_rowCount	 = mysql_num_rows($this->res_data);
				@$this->res_fieldCount	 = mysql_num_fields($this->res_data);
				@$res = new phpRecordSet($this->dbType, $this->res_data, $this->res_rowCount, $this->res_fieldCount);
				// -- parse field names
				for ($i=0; $i<$this->res_fieldCount; $i++) {
					$this->res_fields[$i] = mysql_field_name($this->res_data, $i);
					$this->res_fieldsInfo[$i] = Array();
					$this->res_fieldsInfo[$i]['type']  = mysql_field_type($this->res_data, $i);;
					$this->res_fieldsInfo[$i]['len']   = mysql_field_len($this->res_data, $i);;
					$this->res_fieldsInfo[$i]['flags'] = mysql_field_flags($this->res_data, $i);;
				}
			}
			// log error - do not log errors in db - there are 5 mill of them now there.
			// if ($this->res_errMsg != '') {
			// 	$userid = ($ses_userid != null ? $ses_userid : 'null');
			// 	$ssql = "INSERT INTO ".$sys_dbPrefix."log_error(domain, url, userid, sql, error)
			// 			 VALUES('".$_SERVER["HTTP_HOST"]."', '".pg_escape_string($_SERVER["REQUEST_URI"])."', $userid, 
			// 				'".pg_escape_string($sql)."', '".pg_escape_string($this->res_errMsg)."');";
			// 	pg_query($this->dbConn, $ssql);
			// }
		}
		
		$this->res_sql = $sql;

		// show debug info if on
		if ($this->debug == true) {
			print("<pre>".$sql."<hr>");
			if ($this->res_errMsg != '') print("<span style='color: red'>".$this->res_errMsg."</span><hr>");
			print("</pre>");
		}
		// restore errors
		ini_set('display_errors', $ini_err);
		
		return $res;
	}
	
	// -- Return all records as an Array
	public function getAllRecords($rs=null) {
		$ret = Array();
		if ($rs == null) $rs = $this->rs;
		while ($rs && !$rs->EOF) {
			$ret[] = $rs->fields;
			$rs->moveNext();
		}
		return $ret;
	}
}

// =============================================
// ----- Record Set class

class phpRecordSet {
	public $dbType;
	public $data;
	public $rowCount;
	public $fieldCount;
	public $EOF;
	public $fields;
	public $current;
	
	function __construct($dbType, $res, $rowCount, $fieldCount) {
		$this->dbType 		= $dbType;
		$this->data 		= $res;
		$this->rowCount		= $rowCount;
		$this->fieldCount	= $fieldCount;
		if ($rowCount == 0) {
			$this->EOF = true; 
		} else {
			$this->EOF = false;		
			$this->moveFirst();
		}
	}
	
	function __destruct() {
		if ($this->dbType == 'postgres') @pg_free_result($this->data);
		if ($this->dbType == 'mysql') @mysql_free_result($this->data);
	}
	
	public function moveFirst() {
		if ($this->dbType == 'postgres') {
			if ($this->rowCount == 0) return;
			$this->current = 0;
			$this->fields = pg_fetch_array($this->data, 0);
		}
		if ($this->dbType == 'mysql') {
			if ($this->rowCount == 0) return;
			$this->current = 0;
			mysql_data_seek($this->data, $this->current);
			$this->fields = mysql_fetch_array($this->data, MYSQL_BOTH);
		}
	}
	
	public function moveLast() {
		if ($this->dbType == 'postgres') {
			$this->current = $this->rowCount -1;
			$this->fields = pg_fetch_array($this->data, $this->current);
		}
		if ($this->dbType == 'mysql') {
			$this->current = $this->rowCount -1;
			mysql_data_seek($this->data, $this->current);
			$this->fields = mysql_fetch_array($this->data);
		}
	}
	
	public function moveNext() {
		if ($this->dbType == 'postgres') {
			if ($this->EOF) return;
			$this->current++;
			if ($this->current >= $this->rowCount) { $this->EOF = true; $this->fields = Array(); return; }
			$this->fields = pg_fetch_array($this->data, $this->current);
		}
		if ($this->dbType == 'mysql') {
			if ($this->EOF) return;
			$this->current++;
			if ($this->current >= $this->rowCount) { $this->EOF = true; $this->fields = Array(); return; }
			mysql_data_seek($this->data, $this->current);
			$this->fields = mysql_fetch_array($this->data);
		}
	}
	
	public function movePrevious() {
		if ($this->dbType == 'postgres') {
			if ($this->current == 0) { return; }
			$this->current--;
			$this->fields = pg_fetch_array($this->data, $this->current);
		}
		if ($this->dbType == 'mysql') {
			if ($this->current == 0) { return; }
			$this->current--;
			mysql_data_seek($this->data, $this->current);
			$this->fields = mysql_fetch_array($this->data);
		}
	}
}
?>