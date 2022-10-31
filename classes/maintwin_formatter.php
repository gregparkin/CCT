<?php
/**
 * @package    CCT
 * @file       maintwin_formatter.php
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
// This is a critical module for CCT. This code is what determines the work start/end date for a given server
// based upon the IR window and the actual work start date the user wants to use.
//

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

/** @class maintwin_formatter
 *  @brief This class is used to format CSC maintenance window strings into useful strings that CCT can work with. (See: maintwin_scheduler.php)
 *  @brief Used by classes: cct6_systems.php and gen_request.php
 *  @brief Used by nightly job: make_csc.php
 *  @brief Used by Ajax server: server_edit_work_request.php
 */
class maintwin_formatter extends library
{
	var $data = array();
			
	/** @fn __construct()
	 *  @brief Class constructor - Create oracle object and setup some dynamic class variables
	 *  @return void 
	 */
	public function __construct()
	{
		date_default_timezone_set('America/Denver');
		$this->debug_start('maintwin_formatter.html');
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
			
	/** @fn format($unformatted)
	 *  @brief This is the function formats the unformatted and returns it to a new string
	 *  @param $unformatted is the string to format
	 *  @return string
	 */			
	public function format($unformatted)
	{
		$month_list = array("JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEP","OCT","NOV","DEC");
		$weekday_list = array("SUN","MON","TUE","WED","THU","FRI","SAT");
		
		if (($x = strpos($unformatted, '#')) > 0)
			$maintwin = substr($unformatted, 0, $x); // Remove # comments from $text
		else
			$maintwin = $unformatted;
			 
		$maintwin = trim(strtoupper($maintwin)); // Remove any leading and trailing space characters, and make uppercase
		$this->debug4(__FILE__, __FUNCTION__, __LINE__, "maintwin = (%s)", $maintwin);
		// $maintwin_length = strlen($maintwin);

		$formatted = '';
		$line1 = explode(';', $maintwin);
		
		// MONTHS+WEEK+WEEKDAYS+START+DURATION;MONTHS+WEEK+WEEKDAYS+START+DURATION
		foreach ($line1 as $item1)
		{
			$line2 = explode(' ', $item1);
			$months = '';
			$week_no = 0;
			$weekdays = '';
			$start = '';
			$duration = 120;
			
			$got_months = false;
			$got_week_no = false;
			$got_weekdays = false;
			$got_start = false;
			$got_duration = false;
				
			// printf("item1: %s\n", $item1);
			
			foreach ($line2 as $item2)
			{
				//printf("item2: %s\n", $item2);
				
				if (!$got_months)
				{
					foreach ($month_list as $month)
					{
						if (strncasecmp($item2, $month, 3) == 0)
						{
							$months = $item2;
							$got_months = true;
							break;
						}
					}
					
					if ($got_months == true)
						continue;
				}
				
				if (!$got_week_no && !$got_start && ($item2 == "LAST" || ctype_digit($item2)))
				{
					$week_no = $item2;
					$got_week_no = true;
					continue;
				}
				
				if (!$got_weekdays)
				{
					foreach ($weekday_list as $weekday)
					{
						if (strncasecmp($item2, $weekday, 3) == 0)
						{
							$weekdays = $item2;
							$got_weekdays = true;
							break;
						}
					}
					
					if ($got_weekdays == true)
						continue;
				}
				
				if ($got_start == false && strlen($item2) == 5)
				{
					$start = $item2;
					$got_start = true;
					continue;
				}
				
				if ($got_start && !$got_duration && ctype_digit($item2))
				{
					$duration = $item2;
					$got_duration = true;
					continue;
				}
			} // foreach ($line2 as $item2)
			
			if (strlen($formatted) > 0)
				$formatted .= ';';
				
			$formatted .= $months . '+' . $week_no . '+' . $weekdays . '+' . $start . '+' . $duration;
		}		
			
		return $formatted;
	}
	
	/** @fn set_months($line)
	 *  @brief Setup $this->weekday_list with the formatted month information
	 *  @param $line is the string to format
	 *  @return void
	 */	
	private function set_months($line)
	{
		$this->debug4(__FILE__, __FUNCTION__, __LINE__, "line = (%s)", $line);
		$this->month_list = '';
		$mdays = $this->get_mday_list();
		$months = $this->get_month_list();
		
		$line = "|" . $line;  // for strpos()
		
		foreach ($months as $month_name => $month_value)
		{
			if (strpos($line, $month_name) > 0)
			{
				if (strlen($this->month_list) > 0)
					$this->month_list .= ',' . $month_name;
				else
					$this->month_list = $month_name;
			}
			
			foreach ($mdays as $mday_name => $mday_value)
			{
				$pattern = $mday_name . ' ' . $month_name;
				
				if (strpos($line, $pattern) > 0)
				{
					$this->mday = $mday_value;
				}
			}
		} 
	}
	
	/** @fn set_weekdays($line)
	 *  @brief Setup $this->weekday_list with the formatted weeday information
	 *  @param $line is the string to format
	 *  @return void
	 */	
	private function set_weekdays($line)
	{
		$weeks = $this->get_week_list();
		$weekdays = $this->get_weekday_list();
		
		$this->debug4(__FILE__, __FUNCTION__, __LINE__, "line = (%s)", $line);
		$this->weekday_list = '';
		$this->week = 0;
		
		$line = "|" . $line;  // for strpos()
		
		foreach ($weekdays as $weekday_long => $weekday_short)
		{
			if (strpos($line, $weekday_long) > 0)
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "strpos(line=%s, weekday_long=%s) > 0", $line, $weekday_long);
				
				if (strlen($this->weekday_list) > 0)
				{
					$this->weekday_list .= ',' . $weekday_short;
				}
				else
				{
					$this->weekday_list = $weekday_short;
				}
			}
			else if (strpos($line, $weekday_short) > 0)
			{
				$this->debug4(__FILE__, __FUNCTION__, __LINE__, "strpos(line=%s, weekday_short=%s) > 0", $line, $weekday_short);
				
				if (strlen($this->weekday_list) > 0)
				{
					$this->weekday_list .= ',' . $weekday_short;
				}
				else
				{
					$this->weekday_list = $weekday_short;
				}
			}			
			
			foreach ($weeks as $week_long => $week_short)
			{
				$pattern1 = $week_long  . ' ' . $weekday_long;
				$pattern2 = $week_short . ' ' . $weekday_short;
				
				if (strpos($line, $pattern1) > 0)
				{
					$this->mday = 0;   // Number before a weekday will cancel out the number (mday) before the month
					$this->week = $week_short;
				}
				else if (strpos($line, $pattern2) > 0)
				{
					$this->mday = 0;   // Number before a weekday will cancel out the number (mday) before the month
					$this->week = $week_short;				
				}
			}
		}	
		
		if (strlen($this->weekday_list) == 0)
		{
			$this->weekday_list = 'SUN,MON,TUE,WED,THU,FRI,SAT';
			$this->week = 0;
		}	
	}
	
	/** @fn get_mday_list()
	 *  @brief Return an associated array key=value pairs of month day numbers
	 *  @return array
	 */	
	private function get_mday_list()
	{
		$list = array(
			"1ST"  => "1",
			"2ND"  => "2",
			"3RD"  => "3",
			"4TH"  => "4",
			"5TH"  => "5",
			"6TH"  => "6",
			"7TH"  => "7",
			"8TH"  => "8",
			"9TH"  => "9",
			"10TH" => "10",
			"11TH" => "11",
			"12TH" => "12",
			"13TH" => "13",
			"14TH" => "14",
			"15TH" => "15",
			"16TH" => "16",
			"17TH" => "17",
			"18TH" => "18",
			"19TH" => "19",
			"20TH" => "20",
			"21TH" => "21",
			"22TH" => "22",
			"23TH" => "23",
			"24TH" => "24",
			"25TH" => "25",
			"26TH" => "26",
			"27TH" => "27",
			"28TH" => "28",
			"29TH" => "29",
			"30TH" => "30",			
			"31TH" => "31",
			"LAST" => "99");
			
		return $list;
	}
	
	/** @fn get_month_list()
	 *  @brief Return an associated array key=value pairs of months
	 *  @return array
	 */	
	private function get_month_list()
	{
		$list = array(
			"JANUARY"   => "JAN",
			"FEBRUARY"  => "FEB",
			"MARCH"     => "MAR",
			"APRIL"     => "APR",
			"MAY"       => "MAY",
			"JUNE"      => "JUN",
			"JULY"      => "JUL",
			"AUGUEST"   => "AUG",
			"SEPTEMBER" => "SEP",
			"OCTOBER"   => "OCT",
			"NOVEMBER"  => "NOV",
			"DECEMBER"  => "DEC");
			
		return $list;
	}
	
	/** @fn get_week_list()
	 *  @brief Return an associated array key=value pairs of the week numbers
	 *  @return array
	 */	
	private function get_week_list()
	{
		$list = array(
			"FIRST" => 1,
			"1ST"   => 1,
			"2ND"   => 2,
			"3RD"   => 3,
			"4TH"   => 4,
			"5TH"   => 5,
			"LAST"  => 99);
			
		return $list;
	}
	
	/** @fn get_weekday_list()
	 *  @brief Return an associated array key=value pairs of the weekdays
	 *  @return array
	 */	
	private function get_weekday_list()
	{
		$list = array(
			"SUNDAY"    => "SUN",
			"MONDAY"    => "MON",
			"TUESDAY"   => "TUE",
			"WEDNESDAY" => "WED",
			"THURSDAY"  => "THU",
			"FRIDAY"    => "FRI",
			"SATURDAY"  => "SAT");
			
		return $list;
	}
	
	/** @fn FixEndTime($start_time, &$end_time, $increase)
	 *  @brief Create a new $end_time by using the start_time and increase values to calculate it
	 *  @param $start_time is the start time
	 *  @param $end_time is the new end_time we want to change
	 *  @param $increase is the amount of time we want to add to start_time to calculate end_time
	 *  @return true or false
	 */	
	private function FixEndTime($start_time, &$end_time, $increase)
	{
		if (strlen($end_time) == 0)
		{
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "returning false, strlen(end_time) == 0");
			return false;
		}
			
		$h = intval(substr($start_time, 0, 2));
		$m = intval(substr($start_time, 2, 2));
		
		while ($increase > 0)
		{
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "increase = %d", $increase);
			$m++;
			
			if ($m == 60)
			{
				$this->debug3(__FILE__, __FUNCTION__, __LINE__, "m == 60");
				$h++;
				$m = 0;
				
				if ($h == 24)
				{
					$this->debug3(__FILE__, __FUNCTION__, __LINE__, "h == 24");
					$h = 0;
				}
			}
			
			$increase--;
		}
		
		$end_time = sprintf("%.2d%.2d", $h, $m);
		$this->debug3(__FILE__, __FUNCTION__, __LINE__, "returning true, end_time = %s", $end_time);
		
		return true;
	}
	
	/** @fn Duration($from_time, $to_time)
	 *  @brief Return the duration total in minutes between $from_time and $to_time
	 *  @param $from_time
	 *  @param $to_time
	 *  @return duration string
	 */	
	private function Duration($from_time, $to_time)
	{
		$start_time = strtotime($from_time);
		$end_time   = strtotime($to_time);
		
		$seconds = $end_time - $start_time;
		
		$days  = floor($seconds / 60 / 60 / 24);
		$hours = $seconds / 60 / 60 % 24;
		$mins  = $seconds / 60 % 60;
		// $secs  = $seconds % 60;
		
		$duration = $mins + ($hours * 60) + ($days * (60 * 60));
		$this->debug3(__FILE__, __FUNCTION__, __LINE__, "returning duration = %d", $duration);
		
		return $duration;		
	}
}
?>
