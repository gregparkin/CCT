#!/opt/lampp/bin/php -q
<?php
/**
 * <update_assign_groups.php>
 *
 * @package    CCT
 * @file       update_assign_groups.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       10/11/2013
 * @version    6.0.0
 */
 
//
// The purpose of this script is to update cct6_assign_groups with any new assignment groups
// that have been added to Remedy. Remember: Users have the ability to adjust and change
// what Remedy groups that want to be in within CCT. That is why we update instead of just
// rebuild the table every night.
//

//
// Class autoloader - /xxx/www/cct/classes:/xxx/www/cct/servers:/xxx/www/cct/includes
// See: include_paths= in file /xxx/apache/php/php.ini
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

$ora = new dbms();    // classes/dbms.php
$lib = new library(); // classes/library.php

//
// Grab all the current assign groups and place them in a associated array (hash table).
//
$current_assign_groups = array();

// For TESTING only!
// $ora->sql("delete from cct6_assign_groups where insert_name = 'CCT Automation'");
// $ora->sql("delete from cct6_assign_groups where group_name = 'AIM-TOOLS-BOM'");

if ($ora->sql("select distinct group_name from cct6_assign_groups order by group_name") == false)
{
	printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
	exit();
}

while ($ora->fetch())
{
	$current_assign_groups[$ora->group_name] = true;
}

//
// Scan Remedy for new Assignment Groups and place them into our storage link list.
//
$query  = "select distinct ";
$query .= "  ag.login_name        as login_name, ";
$query .= "  ag.group_x           as group_x ";
$query .= "from ";
$query .= "  aradmin.user_x@remedy_im2   ag, ";
$query .= "  cct6_mnet                   mn ";
$query .= "where ";
$query .= "  ag.login_name is not null and ";
$query .= "  mn.mnet_cuid = ag.login_name and ";
$query .= "  mn.mnet_email is not null ";
$query .= "order by ";
$query .= "  ag.login_name";

if ($ora->sql($query) == false)
{
	printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
	exit();
}

$top = $p = null;

while ($ora->fetch())
{
	$list = explode(" ", $ora->group_x);
	
	foreach($list as $group_name)
	{
		$group_name = trim($group_name);
		
		//
		// Tyler Murdock - In the group_x column from user_x you can ignore or discard the following 
		// group names - CDSR, CMREQ, CM-BASIC, Administrator, CM-ADMIN, CMMAN, CMIMP
		//				
		if (strlen($group_name) == 0 || $group_name == "CDSR" || $group_name == "CMREQ" || $group_name == "CM-BASIC" ||
		    $group_name == "Administrator" || $group_name == "CM-ADMIN" || $group_name == "CMMAN" || $group_name == "CMIMP")
		{
			continue;
		}

		//
		// Do we already have this group recorded?
		//
		if (array_key_exists($group_name, $current_assign_groups))
		{
			continue;
		}
	
		if ($top == null)
		{
			$top = new data_assign_groups();  // See class below
			$p = $top;
		}
		else
		{
			$p->next = new data_assign_groups();
			$p = $p->next;
		}
	
		$p->login_cuid = $ora->login_name;
		$p->group_name = $group_name;
	}
}

//
// Add in any new groups and their associated login_cuid's
//
$add_count = 0;

for ($p=$top; $p!=null; $p=$p->next)
{
	//
	// Check cct6_assign_groups to make sure we don't already have this group_name
	//
	if ($ora->sql("select * from cct6_assign_groups where group_name = '" . $p->group_name . "'") == false)
	{
		printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
		exit();
	}
	
	if ($ora->fetch())
	{
		printf("GOT: %s\n", $p->group_name);
		continue;
	}

	printf("NEW: %s\n", $p->group_name);

	$add_count++;

	$insert = "insert into cct6_assign_groups (insert_name, group_name, login_cuid) values ( ";

	$lib->makeInsertCHAR($insert, 'CCT Automation',  true);
	$lib->makeInsertCHAR($insert, $p->group_name,    true);
	$lib->makeInsertCHAR($insert, $p->login_cuid,   false);

	$insert .= " )";

	printf("Adding: %s - %s\n", $p->group_name, $p->login_cuid);

	if ($ora->sql($insert) == false)
	{
		printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
		exit();
	}
}

if ($add_count > 0)
{
	printf("\nAdded %d new assignment groups with login cuids to cct6_assign_groups\n", $add_count);
}
else
{
	printf("\nNo new assignment groups were added to cct6_assign_groups\n");
}

printf("\nAll done!\n");
exit();

/*! @class data_assign_groups
 *  @brief This class is used for data storage for the current CCT assign groups. There is only around 2700 records so the storage requirements be small.
 */
class data_assign_groups
{
	var $data;

	/*! @fn __construct()
	 *  @brief Class constructor - Create oracle object and setup some dynamic class variables
	 *  @return void 
	 */
	public function __construct()
	{
		$this->group_name = '';
		$this->login_cuid = '';
		$this->next = null;
	}

	/*! @fn __destruct()
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
	 *  @brief Determine if item ($name) exists in the $this->data array
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
}
?>
