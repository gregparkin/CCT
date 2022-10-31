<?php
/**
 * @package    CCT
 * @file       maintwin_scheduler.php
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
// based upon the IR window and the actual work start date the user should use.
//

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

/**
 * @class maintwin_scheduler
 *
 * @brief This class is used to compute the actual server work start/end dates and times based upon the IR window and the servers maintenance window.
 * @brief Used by classes: cct6_systems.php and gen_request.php
 * @brief Used by Ajax servers: server_edit_work_request.php and server_test_maintenance_windows.php
 */
class maintwin_scheduler extends library
{
	var $data = array();
	var $LAST = 9991;
	var $top_ir_window = null;

	var $scheduled_starts;
	var $scheduled_ends;

	/**
	 * @fn    __construct()
	 *
	 * @brief Class constructor - Create oracle object and setup some dynamic class variables
	 */
	public function __construct()
	{
		date_default_timezone_set('America/Denver');

		// Setup some default maintenance windows
		//
		$this->default_weekly    = '+0+TUE,THU+0200+180';
		$this->default_monthly   = '+2+SUN+0000+240';
		$this->default_quarterly = 'FEB,MAY,AUG,NOV+3+SAT+2200+1440';	
		
		$this->reset();
		
		// $this->debug_start('maintwin_scheduler.html');
	}

	/**
	 * @fn __set($name, $value)
     *
	 * @brief Setter function for $this->data
	 * @brief Example: $obj->first_name = 'Greg';
     *
	 * @param  string $name is the key in the associated $data array
	 * @param  string $value is the value in the assoicated $data array for the identified key
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
	 * @return string Return the value or null
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
	 * @return bool true or false
	 */	
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}

	/**
	 * @fn __unset($name)
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
	 * @fn     ComputeStart($scheduled_starts=array(), $scheduled_ends=array())
     *
	 * @brief  After object properties have been set, this function computes the server work start/end/duration dates and times.
	 *
	 * @param  array $scheduled_starts
	 * @param  array $scheduled_ends
	 *
	 * @return bool -  true or false, true means success
	 */			
	public function ComputeStart($scheduled_starts=array(), $scheduled_ends=array())
	{
		//
		// Used in conflict analysis to force scheduler routine to continue looking for a start date that is acceptable.
		//
		if (is_array($scheduled_starts))
		{
			$scheduled_starts_count = count($scheduled_starts);
			$this->scheduled_starts = $scheduled_starts;
		}
		else
		{
			$scheduled_starts_count = 0;
			$this->scheduled_starts = array();
		}

		if (is_array($scheduled_ends))
		{
			$scheduled_ends_count = count($scheduled_ends);
			$this->scheduled_ends = $scheduled_ends;
		}
		else
		{
			$scheduled_ends_count = 0;
			$this->scheduled_ends = array();
		}

		if ($this->ir_start_time == 0)
		{
			$this->ErrorMessage = 'Missing obj->ir_start_date. Use: obj->set_ir_window($start, $end);';
			return false;
		}
		
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->ir_start_date = %s", $this->ir_start_date);
		
		if ($this->ir_end_time == 0)
		{
			$this->ErrorMessage = 'Missing obj->ir_end_date. Use: obj->set_ir_window($start, $end);';
			return false;
		}
		
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->ir_end_date = %s", $this->ir_end_date);
	
		if ($this->ticket_use_os_maintwin == "N" || $this->wreq_osmaint == "N")
		{
			//
			// No match so set server work start/end to ir window and return false
			//
			$this->wreq_start_date = $this->ir_start_date;
            $this->wreq_start_time = $this->ir_start_time;

			$this->wreq_end_date   = $this->ir_end_date;
            $this->wreq_end_time   = $this->ir_end_time;

			$this->wreq_duration   = $this->getDuration($this->wreq_start_date, $this->wreq_end_date);

			return true;
		}
		
		//
		// $this->wreq_osmaint will either be the servers maintenance window (weekly, monthly, quarterly) or
		// one of the default maintenance windows as defined by set_osmaint_win($mw_type, $mw);
		//
		if (strlen($this->wreq_osmaint) == 0)
		{
			$this->ErrorMessage = 'Missing obj->wreq_osmaint. Use: obj->set_osmaint_win($osmaint_type, $osmaint_win);';
			return false;
		}
		
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->wreq_osmaint = %s", $this->wreq_osmaint);

		//
		// Construct all possible work start and end datetime values in GMT Unix sytle time() format and compare
		// to ir_start_time and ir_end_time. The first work start/end values that fall within the IR window is the
		// date and time we will use.
		//
		// Strings in wreq_osmaint are formatted and should not contain any comments.
		// (i.e. Months+Week#+Weekdays+Start+Duration;...repeat pattern...
		//
		$parts = explode(';', $this->wreq_osmaint);

		foreach ($parts as $part)
		{
			if (isset($months))
				unset($months);
				
			if (isset($weekday_occurrence))
				unset($weekday_occurrence);
								
			if (isset($weekdays))
				unset($weekdays);
				
			if (isset($start))
				unset($start);
				
			if (isset($duration))
				unset($duration);
		
			$fields = explode('+', $part);
			$x = 1;
			
			foreach ($fields as $field)  // Months+weekday_occurrence+Weekdays+Start+Duration
			{
				$field = trim($field);
				
				switch ( $x++ )
				{
				case 1:  // Months
					if (strlen($field) > 0)
					{
						$months = explode(',', $field);   // JAN,APR,JUL,OCT
					}
					else
					{
						$months = explode(',', $this->ir_months);       // ir_months created from $obj->set_ir_window(<start>, <end>);
					}
					break;
				case 2:  // Weekday Occurrence
					if ($field == 'LAST')
					{
						$weekday_occurrence = $this->LAST;
					}
					else
					{
						$weekday_occurrence = $field;
					}
					break;
				case 3:  // Weekdays
					if (strlen($field) > 0)
					{
						$weekdays = explode(',', $field); // MON,TUE,WED,THU,FRI
					}
					else
					{
						$weekdays = explode(',', $this->ir_weekdays);   // ir_weekdays created from $obj->set_ir_window(<start>, <end>);
					}
					break;
				case 4:  // Start time
					$start = $field;
					$hold = explode(':', $start);
					$hr = $hold[0];
					$mi = $hold[1];
					break;
				case 5:  // Duration
					$duration = $field;
					break;
				default:
					break;
				}
			}
			
			if ($x != 6)
			{
				// problem parsing this maintenance window. 
				continue;
			}
			
			foreach ($months as $month)
			{
				foreach($weekdays as $weekday)
				{
					for ($p=$this->top_ir_window; $p!=null; $p=$p->next)
					{
						// $p->month              = 0;     // 1-12
						// $p->month_name         = '';    // JAN, FEB, ...
						// $p->mday               = 0;     // 1-31
						// $p->year               = 0;     // 2 digit year
						// $p->weekday            = 0;     // 0-6
						// $p->weekday_name       = '';    // SUN, MON, ...
						// $p->weekday_occurrence = 0;     // +2+SUN+.. would mean 2nd SUN in this month
						// $p->hour               = 0;     // 0-23
						// $p->minutes            = 0;     // 0-59
						// $p->tbuf               = 0;     // GMT time. total seconds since 01-JAN-1970
						// $p->last_week          = false; // Last weekday occurrence? 0=no, 1=yes	
						//
						if ($month == $p->month_name && $weekday == $p->weekday_name)
						{
							if ($weekday_occurrence == 0 || $weekday_occurrence == $p->weekday_occurrence || ($weekday_occurrence == $this->LAST && $p->last_week == true))
							{
								// Construct start and end dates and times			
								$work_start =
									str_pad($p->month, 2, "0", STR_PAD_LEFT) . '/' .
									str_pad($p->mday,  2, "0", STR_PAD_LEFT) . '/' . $p->year . ' ' .
									str_pad($hr,       2, "0", STR_PAD_LEFT) . ':' .
									str_pad($mi,       2, "0", STR_PAD_LEFT);

								$work_start_time = strtotime($work_start);			
								
								$work_end      = $this->makeEndDateTime($p->month, $p->mday, $p->year, $hr, $mi, $duration);
								$work_end_time = strtotime($work_end);				

								//
								// There is no more IR windows, just a scheduling start date in the future. So we no longer
								// need to look at $this->ir_end_time.
								//
								if ($work_start_time >= $this->ir_start_time && $work_end_time > $this->ir_start_time)
								{
									$work_start_time = $this->to_gmt($work_start, $this->system_timezone_name);
									$work_end_time   = $this->to_gmt($work_end,   $this->system_timezone_name);;

									$is_there_a_conflict = false;

									//
									// Do a conflict analysis using the $scheduled_starts[] and $schedule_ends[] date arrays.
									//
									// If array counts are greater than 0 and the two array counts match, then proceed.
									//
									if ($scheduled_starts_count > 0 && $scheduled_starts_count == $scheduled_ends_count)
									{
										for ($i=0; $i<$scheduled_starts_count; $i++)
										{
											//
											//                   SS----------------------------SE
											//        S-------------E
											//                      S--------E
											//                                    S-----------------E
											//
											if ($work_start_time >= $scheduled_starts[$i] && $work_start_time <= $scheduled_ends[$i])
											{
												$is_there_a_conflict = true;
												continue;
											}

											if ($work_end_time >= $scheduled_starts[$i] && $work_end_time <= $scheduled_ends[$i])
											{
												$is_there_a_conflict = true;
												continue;
											}
										}
									}

									//
									// Is there any scheduling conflicts for this server?
									//
									if ($is_there_a_conflict == false)
									{
										//
										// No conflicts. Let's go with these dates.
										//
										$this->wreq_start_date = $work_start;
										$this->wreq_start_time = $work_start_time;

										$this->wreq_end_date   = $work_end;
										$this->wreq_end_time   = $work_end_time;

										$this->wreq_duration   = $this->getDuration($work_start, $work_end);

										return true;
									}
								} // if ($work_start_time >= $this->ir_start_time)
							} // if ($weekday_occurrence == 0 || $weekday_occurrence == $p->weekday_occurrence || ($weekday_occurrence == $LAST && $p->last_week == true))
						} // if ($month == $p->month_name && $weekday == $p->weekday_name)
					} // for ($p=$top_ir_window; $p!=null; $p=$p->next)
				} // foreach($weekdays as $weekday)
			} // foreach ($months as $month)
		} // foreach ($parts as $part)
		
		//
		// No match so set server work start/end to ir window and return false
		//
		$this->wreq_start_date = $this->ir_start_date;
        $this->wreq_start_time = $this->to_gmt($this->ir_start_date, $this->system_timezone_name);

		$this->wreq_end_date = $this->ir_end_date;
        $this->wreq_end_time = $this->to_gmt($this->ir_end_date, $this->system_timezone_name);

        $this->wreq_duration = $this->getDuration($this->wreq_start_date, $this->wreq_end_date);
		
		return true;
	}

	/** @fn makeEndDateTime($mm, $dd, $yy, $hr, $mi, $duration)
     *
	 *  @brief Create a End date and time string using a current date and duration in minutes
     *
	 *  @param int $mm is the month number
	 *  @param int $dd is the day number
	 *  @param int $yy is the year number
	 *  @param int $hr is the hour number 00-23
	 *  @param int $mi is the minute number 00-59
	 *  @param string $duration is the number in minutes
     *
	 *  @return string
	 */	
	private function makeEndDateTime($mm, $dd, $yy, $hr, $mi, $duration)
	{
		$total = $hr * 60 + $mi + $duration;
		
		//$this->debug1(__FILE__, __FUNCTION__, __LINE__, "total=%d mm=%d dd=%d yy=%d hr=%d mi=%d duration=%d",
		//	$total, $mm, $dd, $yy, $hr, $mi, $duration);
		
		while ($total >= 1440)
		{
			$total -= 1440;
			$num_days = cal_days_in_month(CAL_GREGORIAN, $mm, $yy);
			$dd++;
			
			if ($dd > $num_days)
			{
				$dd = 1;
				$mm++;
				
				if ($mm > 12)
				{
					$mm = 1;
					$yy++;
				}
			}
		}
		
		$hours = $total / 60;
		$minutes = $total - ($hours * 60);
		
		// Remove any fractal part of the number
		$hours = number_format($hours, 0);      
		$minutes = number_format($minutes, 0);
		
		if ($hours == 24)
			$hours = 0;
			
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "mm=%d dd=%d yy=%d hr=%d mi=%d", $mm, $dd, $yy, $hours, $minutes);
		
		return str_pad($mm, 2, "0", STR_PAD_LEFT) . '/' . 
			   str_pad($dd, 2, "0", STR_PAD_LEFT) . '/' . $yy . ' ' . 
			   str_pad($hours, 2, "0", STR_PAD_LEFT) . ':' . 
			   str_pad($minutes, 2, "0", STR_PAD_LEFT);
	}
	
	/** @fn set_osmaint_win($mw_type, $mw)
     *
	 *  @brief Setup the object property values used by ComputeStart() from the Remedy IR start and end window.
	 *         obj->set_osmaint_win('W', '+0+MON,TUE,WED,THU,FRI+0100+120;+0+SAT+2200+240');
     *
	 *  @param string $mw_type is the maintenance window type: Weekly, Monthly, Quarterly
	 *  @param string $mw is the server's formatted maintenance window string.
     *
	 *  @return void
	 */	
	public function set_osmaint_win($mw_type, $mw)
	{
		$this->wreq_osmaint_type = $mw_type;  // W=Weekly, M=Monthly, Q=Quarterly

		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "mw_type=%s mw=%s", $mw_type, $mw);
		
		if (strlen($mw) == 0)
		{
            if ($mw_type == 'W' || $mw_type == 'Weekly')
            {
                // $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Using default weekly maintwin");
                $this->wreq_osmaint = $this->default_weekly;
            }
			else if ($mw_type == 'M' || $mw_type == 'Monthly')
			{
				// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Using default monthly maintwin");
				$this->wreq_osmaint = $this->default_monthly;
			}
			else if ($mw_type == 'Q' || $mw_type == 'Quarterly')
			{
				// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Using default quarterly maintwin");
				$this->wreq_osmaint = $this->default_quarterly;
			}
			else
			{
				// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "No OS maintwin will be used.");
				$this->wreq_osmaint = 'N';
			}
		}
		else
		{
			// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Using servers os_maintwin");
			$this->wreq_osmaint = $mw;
		}
		
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "wreq_osmaint=%s", $this->wreq_osmaint);
	}
	
	/** @fn     set_ir_window($ir_start_date, $ir_start_num, $ir_end_date, $ir_end_num)
	 *
	 *  @brief  Setup the object property values used by ComputeStart() from the Remedy IR start and end window.
	 *
	 *  @param  string $ir_start_date        is the IR start date in MM/DD/YYYY HH:MI (STRING)
	 *  @param  int    $ir_start_num         is the IR end date in GMT utime          (NUMBER)
     *  @param  string $ir_end_date          is the IR start date in MM/DD/YYYY HH:MI (STRING)
     *  @param  int    $ir_end_num           is the IR end date in GMT utime          (NUMBER)
	 *  @param  string $system_timezone_name is the server's timezone name
     *
	 *  @return void
	 */	
	public function set_ir_window($ir_start_date, $ir_start_num, $ir_end_date, $ir_end_num, $system_timezone_name)
	{
		//$this->debug1(__FILE__, __FUNCTION__, __LINE__,
        //    "ir_start_date=%s, ir_start_num=%d, ir_end_date=%s, ir_end_num=%d",
        //    $ir_start_date, $ir_start_num, $ir_end_date, $ir_end_num);

        $this->ir_start_date = $ir_start_date;
		$this->ir_start_time = $ir_start_num;

        $this->ir_end_date   = $ir_end_date;
		$this->ir_end_time   = $ir_end_num;

		$this->system_timezone_name = $system_timezone_name;
		
		if ($ir_start_num > $ir_end_num)
		{
			// Should never happen, but just in case.
			// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "ir_start_num(%d) > ir_end_num(%d)", $ir_start_num, $ir_end_num);
			return;
		}          

		$start_mm   = $this->s_mm   = date("n", $this->ir_start_time);  // 1-12 without leading zeros
		$start_dd   = $this->s_dd   = date("j", $this->ir_start_time);  // 1-31 day of month without leading zeros
		$start_yy   = $this->s_yy   = date("Y", $this->ir_start_time);  // YYYY 4 digit year 'y' is 2 digit year
		$start_hr   = $this->s_hr   = date("H", $this->ir_start_time);  // 1-23 hour without leading zeros
		$start_mi   = $this->s_mi   = date("i", $this->ir_start_time);  // 0-59 minutes
		$start_wday = $this->s_wday = date("w", $this->ir_start_time);  // 0-6 weeday where 0 = Sunday
		$start_yday = $this->s_yday = date("z", $this->ir_start_time);  // 0-365 Day of year. Julian Date

		$end_mm     = $this->e_mm   = date("n", $this->ir_end_time);    // 1-12 without leading zeros
		$end_dd     = $this->e_dd   = date("j", $this->ir_end_time);    // 1-31 day of month without leading zeros
		$end_yy     = $this->e_yy   = date("Y", $this->ir_end_time);    // YYYY 4 digit year 'y' is 2 digit year
		$end_hr     = $this->e_hr   = date("H", $this->ir_end_time);    // 1-23 hour without leading zeros
		$end_mi     = $this->e_mi   = date("i", $this->ir_end_time);    // 0-59 minutes without leading zeros
		$end_wday   = $this->e_wday = date("w", $this->ir_end_time);    // 0-6 weeday where 0 = Sunday
		$end_yday   = $this->e_yday = date("z", $this->ir_end_time);    // 0-365 Day of year. Julian Date

        $this->wreq_start_date = $ir_start_date;
        $this->wreq_start_num  = $ir_start_num;

		$this->wreq_end_date   = $ir_end_date;
        $this->wreq_end_num    = $ir_end_num;
		
		//
		// Get a list of months and weekdays this IR window covers.
		// This data is used with os windows do not specify any months and or weekdays.
		//
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Getting list of months and weekdays this IR window covers");
		$months = array();
		
		for ($i=0; $i<13; $i++)
			$months[$i] = 0;
			
		$weekdays = array();
			
		for ($i=0; $i<7; $i++)
			$weekdays[$i] = 0;
	
		//
		// Scan through the IR window and flag the months and weeksdays the window covers.
		//
		//$this->debug1(__FILE__, __FUNCTION__, __LINE__,
        //    "Scanning through IR window to flag months and weekdays the window covers. Step1");
		
		$start = date("Y-m-d", strtotime($ir_start_date));
		$end   = date("Y-m-d", strtotime($ir_end_date));
		
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "start = %s, end = %s", $start, $end);
		
		while ($start <= $end)
		{
			$today = strtotime("$start + 1 day");
			
			$start = date("Y-m-d", $today);
			$start_mm = intval(date("m", $today));   // intval() forces month to single value: (i.e. 03 = 3, 12 = 12, 01 = 1)
  			$start_wday = date("w", $today);
			
			//$this->debug1(__FILE__, __FUNCTION__, __LINE__,
            //    "start = %s, start_mm = %s, start_wday = %s", $start, $start_mm, $start_wday);
			
			$months[$start_mm] = 1;
			$weekdays[$start_wday] = 1;
		}
				
		$mm = '';

		for ($i=1; $i<=12; $i++)
		{
			// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "i = %d, months[%d] = %d", $i, $i, $months[$i]);
			
			if ($i == 1 && $months[1] == 1)
			{
				if (strlen($mm) > 0)
					$mm .= ',JAN';
				else
					$mm = 'JAN';
			}
			else if ($i == 2 && $months[2] == 1)
			{
				if (strlen($mm) > 0)
					$mm .= ',FEB';
				else
					$mm = 'FEB';
			}
			else if ($i == 3 && $months[3] == 1)
			{
				if (strlen($mm) > 0)
					$mm .= ',MAR';
				else
					$mm = 'MAR';
			}
			else if ($i == 4 && $months[4] == 1)
			{
				if (strlen($mm) > 0)
					$mm .= ',APR';
				else
					$mm = 'APR';
			}
			else if ($i == 5 && $months[5] == 1)
			{
				if (strlen($mm) > 0)
					$mm .= ',MAY';
				else
					$mm = 'MAY';
			}
			else if ($i == 6 && $months[6] == 1)
			{
				if (strlen($mm) > 0)
					$mm .= ',JUN';
				else
					$mm = 'JUN';
			}
			else if ($i == 7 && $months[7] == 1)
			{
				if (strlen($mm) > 0)
					$mm .= ',JUL';
				else
					$mm = 'JUL';
			}
			else if ($i == 8 && $months[8] == 1)
			{
				if (strlen($mm) > 0)
					$mm .= ',AUG';
				else
					$mm = 'AUG';
			}
			else if ($i == 9 && $months[9] == 1)
			{
				if (strlen($mm) > 0)
					$mm .= ',SEP';
				else
					$mm = 'SEP';
			}
			else if ($i == 10 && $months[10] == 1)
			{
				if (strlen($mm) > 0)
					$mm .= ',OCT';
				else
					$mm = 'OCT';
			}
			else if ($i == 11 && $months[11] == 1)
			{
				if (strlen($mm) > 0)
					$mm .= ',NOV';
				else
					$mm = 'NOV';
			}
			else if ($i == 12 && $months[12] == 1)
			{
				if (strlen($mm) > 0)
					$mm .= ',DEC';
				else
					$mm = 'DEC';
			}																																
		}
		
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "mm = %s", $mm);
		
		$wd = '';
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "Step3");
		
		for ($i=0; $i<=6; $i++)
		{
			if ($i == 0 && $weekdays[0] == 1)
			{
				if (strlen($wd) > 0)
					$wd .= ',SUN';
				else
					$wd = 'SUN';
			}
			else if ($i == 1 && $weekdays[1] == 1)
			{
				if (strlen($wd) > 0)
					$wd .= ',MON';
				else
					$wd = 'MON';
			}
			else if ($i == 2 && $weekdays[2] == 1)
			{
				if (strlen($wd) > 0)
					$wd .= ',TUE';
				else
					$wd = 'TUE';
			}
			else if ($i == 3 && $weekdays[3] == 1)
			{
				if (strlen($wd) > 0)
					$wd .= ',WED';
				else
					$wd = 'WED';
			}
			else if ($i == 4 && $weekdays[4] == 1)
			{
				if (strlen($wd) > 0)
					$wd .= ',THU';
				else
					$wd = 'THU';
			}
			else if ($i == 5 && $weekdays[5] == 1)
			{
				if (strlen($wd) > 0)
					$wd .= ',FRI';
				else
					$wd = 'FRI';
			}
			else if ($i == 6 && $weekdays[6] == 1)
			{
				if (strlen($wd) > 0)
					$wd .= ',SAT';
				else
					$wd = 'SAT';
			}												
		}
		
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "wd = %s", $wd);
		
		$this->ir_months = $mm;
		$this->ir_weekdays = $wd;
		
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->ir_months=%s (mm)", $this->ir_months);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "this->ir_weekdays=%s (wd)", $this->ir_weekdays);

		//
		// Builds a link list of nodes for the IR window.
		//
		$start_mm = $this->s_mm;                // 1-12 without leading zeros
		$start_dd = $this->s_dd;                // 1-31 day of month without leading zeros
		$start_yy = $this->s_yy;                // YYYY 4 digit year 'y' is 2 digit year
		$start_hr = $this->s_hr;                // 1-23 hour without leading zeros
		$start_mi = $this->s_mi;                // 0-59 minutes without leading zeros
		$start_wday = $this->s_wday;            // 0-6 weeday where 0 = Sunday
		$start_yday = $this->s_yday;            // 0-365 Day of year. Julian Date		
		$start_time = $this->ir_start_time;     // Unix sytle GMT time
		$start_ndays = cal_days_in_month(CAL_GREGORIAN, $start_mm, $start_yy);
	
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_mm = %d", $start_mm);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_dd = %d", $start_dd);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_yy = %d", $start_yy);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_hr = %d", $start_hr);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_mi = %d", $start_mi);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_wday = %d", $start_wday);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_yday = %d", $start_yday);

		$end_mm = $this->e_mm;                  // 1-12 without leading zeros
		$end_dd = $this->e_dd;                  // 1-31 day of month without leading zeros
		$end_yy = $this->e_yy;                  // YYYY 4 digit year 'y' is 2 digit year
		$end_hr = $this->e_hr;                  // 1-23 hour without leading zeros
		$end_mi = $this->e_mi;                  // 0-59 minutes without leading zeros
		$end_wday = $this->e_wday;              // 0-6 weeday where 0 = Sunday
		$end_yday = $this->e_yday;              // 0-365 Day of year. Julian Date
		$end_time = $this->ir_end_time;         // Unix sytle GMT time
		
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "end_mm = %d", $end_mm);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "end_dd = %d", $end_dd);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "end_yy = %d", $end_yy);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "end_hr = %d", $end_hr);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "end_mi = %d", $end_mi);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "end_wday = %d", $end_wday);
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "end_yday = %d", $end_yday);
				
		// year * 1000 + year days (i.e. 2013 * 1000 = (2013000) + 365 = (2013365)
		$start_julian = $start_yy * 1000 + $start_yday;
		$end_julian = $end_yy * 1000 + $end_yday;  	
		// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "start_julian = %d, end_julian = %d", $start_julian, $end_julian);
		
		$this->top_ir_window = $p = null;
		
		while ($start_julian <= $end_julian)
		{
			if ($this->top_ir_window == null)
			{
				$this->top_ir_window = new ir_window();
				$p = $this->top_ir_window;
			}
			else
			{
				$p->next = new ir_window();
				$p = $p->next;
			}	
			
			if ($start_mm == $this->s_mm && $start_dd == $this->s_dd && $start_yy == $this->s_yy)
			{
				// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "START: %.2d/%.2d/%d %.2d:%.2d", $start_mm, $start_dd, $start_yy, $start_hr, $start_mi);
				$p->month = $start_mm;
				$p->month_name = $this->getMonthName($start_mm);
				$p->mday = $start_dd;
				$p->year = $start_yy;
				$p->weekday = $start_wday;
				$p->weekday_name = $this->getWeekdayName($start_wday);
				$p->weekday_occurrence = $this->monthWeekdayOccurrence($start_mm, $start_dd, $start_yy);
				$p->hour = $start_hr;
				$p->minutes = $start_mi;
				$p->tbuf = $start_time;
				$p->last_week = $this->lastWeekdayOccurrence($start_mm, $start_dd, $start_yy);
			}
			else if ($start_mm == $this->e_mm && $start_dd == $this->e_dd && $start_yy == $this->e_yy)
			{
				// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "END: %.2d/%.2d/%d %.2d:%.2d", $end_mm, $end_dd, $end_yy, $end_hr, $end_mi);
				$p->month = $end_mm;
				$p->month_name = $this->getMonthName($end_mm);
				$p->mday = $end_dd;
				$p->year = $end_yy;
				$p->weekday = $end_wday;
				$p->weekday_name = $this->getWeekdayName($end_wday);
				$p->weekday_occurrence = $this->monthWeekdayOccurrence($end_mm, $end_dd, $end_yy);
				$p->hour = $end_hr;
				$p->minutes = $end_mi;			
				$p->tbuf = $end_time;
				$p->last_week = $this->lastWeekdayOccurrence($end_mm, $end_dd, $end_yy);
			}
			else
			{
				// $this->debug1(__FILE__, __FUNCTION__, __LINE__, "MIDDLE: %.2d/%.2d/%d %.2d:%.2d", $start_mm, $start_dd, $start_yy, $start_hr, $start_mi);
				$p->month = $start_mm;
				$p->month_name = $this->getMonthName($start_mm);
				$p->mday = $start_dd;
				$p->year = $start_yy;
				$p->weekday = $start_wday;
				$p->weekday_name = $this->getWeekdayName($start_wday);
				$p->weekday_occurrence = $this->monthWeekdayOccurrence($start_mm, $start_dd, $start_yy);
				$p->hour = 0;
				$p->minutes = 0;
				
				$datetime = str_pad($start_mm, 2, "0", STR_PAD_LEFT) . '/' . 
							str_pad($start_dd, 2, "0", STR_PAD_LEFT) . '/' . $start_yy . ' ' . 
							str_pad($start_hr, 2, "0", STR_PAD_LEFT) . ':' . 
							str_pad($start_mi, 2, "0", STR_PAD_LEFT);	
				$p->tbuf = strtotime($datetime);
				$p->last_week = $this->lastWeekdayOccurrence($start_mm, $start_dd, $start_yy);	
			}
	
			$start_julian++;
			$start_dd++;
			
			if ($start_wday == 6)
				$start_wday = 0;
			else
				$start_wday++;
			
			if ($start_dd > $start_ndays)
			{
				$start_mm++;
				
				if ($start_mm > 12)
				{
					$start_mm = 1;
					$start_yy++;
				}
				
				$start_dd = 1;
				$start_ndays = cal_days_in_month(CAL_GREGORIAN, $start_mm, $start_yy);
			}			
		} // while ($s_julian <= $e_julian)
		
		//$this->dump_ir_window();
	}

	/** @fn monthWeekdayOccurrence($mm, $dd, $yy)
     *
	 *  @brief Return the weekday occurence number for a given date
     *
	 *  @param int $mm is the month number
	 *  @param int $dd is the month day number
	 *  @param int $yy is the year number
     *
	 *  @return int weekday occurence number
	 *
	 *        January               February                 March
	 * Su Mo Tu We Th Fr Sa   Su Mo Tu We Th Fr Sa   Su Mo Tu We Th Fr Sa
	 *        1  2  3  4  5                   1  2                   1  2
	 *  6  7  8  9 10 11 12    3  4  5  6  7  8  9    3  4  5  6  7  8  9
	 * 13 14 15 16 17 18 19   10 11 12 13 14 15 16   10 11 12 13 14 15 16
	 * 20 21 22 23 24 25 26   17 18 19 20 21 22 23   17 18 19 20 21 22 23
	 * 27 28 29 30 31         24 25 26 27 28         24 25 26 27 28 29 30
	 *                                               31	
	 * Examples:
	 *   03/01 = 1st FRI return 1
	 *   03/02 = 1st SAT return 1
	 *   03/03 = 1st SUN return 1
	 *   ...
	 *   03/08 = 2nd FRI return 2
	 *   03/09 = 2nd SAT return 2
	 *   03/10 = 2nd SUN return 2
	 *   ...
	 *
 	 */	
	private function monthWeekdayOccurrence($mm, $dd, $yy)
	{
		// Construct a string that is the first day of the month and year.

		$first_of_the_month = str_pad($mm, 1, "0", STR_PAD_LEFT) . '/01/' . $yy;   // (i.e. change 3/1/2013 to 03/01/2013)
		$this_date          = strtotime($first_of_the_month);                      // Unix style GMT time value
		$this_weekday       = date("w", $this_date);                               // Get the weekday number 0-6, 0=Sunday
		$starting_weekday   = $this_weekday;                                       // Remember what weekday we started on
		$weekday_occurrence = 1;
		
		for ($mday=1; $mday<=$dd; $mday++)
		{
			if ($this_weekday == 6)
				$this_weekday = 0;
			else
				$this_weekday++;
				
			if ($this_weekday == $starting_weekday)
				$weekday_occurrence++;
		}		
		
		return $weekday_occurrence;
	}
	
	/** @fn lastWeekdayOccurrence($mm, $dd, $yy)
     *
	 *  @brief Indicates if a date qualifies as a last weekday occurrence
	 *  @param int $mm is the month number
	 *  @param int $dd is the month day number
	 *  @param int $yy is the year number
     *
	 *  @return true or false, true means success 
	 */	
	private function lastWeekdayOccurrence($mm, $dd, $yy)
	{
		$num_days = cal_days_in_month(CAL_GREGORIAN, $mm, $yy);
		
		if (($dd + 7) > $num_days)
			return true;
			
		return false;
	}
	
	/** @fn dump_ir_window()
     *
	 *  @brief Dump the IR Window information. Only used for debugging.
     *
	 *  @return void 
	 */	
	public function dump_ir_window()
	{
		for ($p=$this->top_ir_window; $p!=null; $p=$p->next)
		{
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "-----------------------------------------------");
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "IR WINDOW: %s", date('r', $p->tbuf));  // Thu, 21 Dec 2000 16:01:07 +0200
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "IR WINDOW: month = %d", $p->month);
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "IR WINDOW: month_name = %s", $p->month_name);
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "IR WINDOW: mday = %d", $p->mday);
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "IR WINDOW: year = %d", $p->year);
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "IR WINDOW: weekday = %d", $p->weekday);
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "IR WINDOW: weekday_name = %s", $p->weekday_name);
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "IR WINDOW: weekday_occurrence = %d", $p->weekday_occurrence);
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "IR WINDOW: hour = %d", $p->hour);
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "IR WINDOW: minutes = %d", $p->minutes);
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "IR WINDOW: tbuf = %ld", $p->tbuf);
			$this->debug3(__FILE__, __FUNCTION__, __LINE__, "IR WINDOW: last_week = %s", $p->last_week == true ? "true" : "false");
		}
		
		$this->debug3(__FILE__, __FUNCTION__, __LINE__, "-----------------------------------------------");
	}
	
	/** @fn getMonthName($num)
     *
	 *  @brief Return a three character month name identified by a month number
     *
	 *  @param int $num is the month number in the range of 1-12, where 1 is January
     *
	 *  @return string
	 */	
	private function getMonthName($num)
	{
		switch ( $num )
		{
		case 1:  return 'JAN';
		case 2:  return 'FEB';
		case 3:  return 'MAR';
		case 4:  return 'APR';
		case 5:  return 'MAY';
		case 6:  return 'JUN';
		case 7:  return 'JUL';
		case 8:  return 'AUG';
		case 9:  return 'SEP';
		case 10: return 'OCT';
		case 11: return 'NOV';
		case 12: return 'DEC';
		default: return '???';
		}
	}
	
	/** @fn getWeekdayName($num)
     *
	 *  @brief Return a three character weekday name identified by a weekday number
     *
	 *  @param int $num is the weekday number in the range of 0-6, where 0 is Sunday
     *
	 *  @return string 
	 */	
	private function getWeekdayName($num)
	{
		switch ( $num )
		{
		case 0:  return 'SUN';
		case 1:  return 'MON';
		case 2:  return 'TUE';
		case 3:  return 'WED';
		case 4:  return 'THU';
		case 5:  return 'FRI';
		case 6:  return 'SAT';
		default: return '???';
		}
	}
	
	/** @fn get_wday($mm, $dd, $yy)
     *
	 *  @brief Return the weekday number for a given date.
     *
	 *  @param int $mm is the month
	 *  @param int $dd is the day of the month
	 *  @param int $yy is the year
     *
	 *  @return int 0-6, where 0 is for Sunday
	 */		
	private function get_wday($mm, $dd, $yy)
	{
		$date_string = str_pad($mm, 1, "0", STR_PAD_LEFT) . '/' . str_pad($dd, 1, "0", STR_PAD_LEFT) . '/' . $yy;  // (i.e. 03/13/2013)
		$this_date = strtotime($date_string);  // Return Unix GMT style time

		return date("w", $this_date);          // Get the weekday number 0-6, 0=Sunday	
	}
	
	/** @fn    getDuration($start, $end)
     *
	 *  @brief Create a duration string from a start and end dates and times.
     *
     *  @param string $start
     *  @param string $end
     *
	 *  @return string duration in days:hours:minutes (47:56:15)
	 */		
	public function getDuration($start, $end)
	{
		$start_time = strtotime($start);
		$end_time   = strtotime($end);
		
		$seconds = $end_time - $start_time;
		
		$days  = floor($seconds / 60 / 60 / 24);
		$hours = $seconds / 60 / 60 % 24;
		$mins  = $seconds / 60 % 60;
		$secs  = $seconds % 60;
		
		$duration = sprintf("%02d%s%02d%s%02d", $days, ':', $hours, ':', $mins); // outputs 47:56:15
			
		return $duration;
	}
	
	/** @fn     reset()
     *
	 *  @brief  Reset all the dynamic variable properties used by ComputeStart()
     *
	 *  @return void
	 */	
	public function reset()
	{
		$this->ErrorMessage = '';
		
		$this->ir_start_date = '';      // 10/10/2010 01:00      IR Start window
		$this->ir_end_date = '';        // 10/31/2010 12:00      IR End window
		$this->wreq_osmaint = '';       // +0+MON,THU+0100+660   Maintenance Window to use in scheduling
		$this->wreq_osmaint_type = '';  // Weekday, Monthly, Quarterly
		$this->wreq_start_date = '';    // 10/15/2010 23:00      Computed work start date and time
		$this->wreq_end_date = '';      // 10/15/2010 23:59      Computed work start date and time
		$this->wreq_duration = '';
		
		$this->s_mm = 0;                // 1-12 without leading zeros
		$this->s_dd =0;                 // 1-31 day of month without leading zeros
		$this->s_yy = 0;                // YYYY 4 digit year 'y' is 2 digit year
		$this->s_hr = 0;                // 1-23 hour without leading zeros
		$this->s_mi = 0;                // 0-59 minutes without leading zeros
		$this->s_wday = 0;              // 0-6 weeday where 0 = Sunday
		$this->s_yday = 0;              // 0-365 Day of year. Julian Date		
		
		$this->e_mm = 0;                // 1-12 without leading zeros
		$this->e_dd = 0;                // 1-31 day of month without leading zeros
		$this->e_yy = 0;                // YYYY 4 digit year 'y' is 2 digit year
		$this->e_hr = 0;                // 1-23 hour without leading zeros
		$this->e_mi = 0;                // 0-59 minutes without leading zeros
		$this->e_wday = 0;              // 0-6 weeday where 0 = Sunday
		$this->e_yday = 0;             // 0-365 Day of year. Julian Date	
	}	
}

/** @class ir_window
 *  @brief Storage class used for the maintwin_scheduler class listed above.
 */
class ir_window
{
	var $data;

	/** @fn    __construct()
     *
	 *  @brief Class constructor - Create oracle object and setup some dynamic class variables
     *
	 *  @return void 
	 */
	public function __construct()
	{
		$this->month              = 0;     // 1-12
		$this->month_name         = '';    // JAN, FEB, ...
		$this->mday               = 0;     // 1-31
		$this->year               = 0;     // 2 digit year
		$this->weekday            = 0;     // 0-6
		$this->weekday_name       = '';    // SUN, MON, ...
		$this->weekday_occurrence = 0;     // +2+SUN+.. would mean 2nd SUN in this month
		$this->hour               = 0;     // 0-23
		$this->minutes            = 0;     // 0-59
		$this->tbuf               = 0;     // GMT time. total seconds since 01-JAN-1970
		$this->last_week          = false; // Last weekday occurrence? 0=no, 1=yes
		$this->next               = null;  // Next node object
	}
		
	/** @fn __destruct()
     *
	 *  @brief Destructor function called when no other references to this object can be found, or in any
	 *         order during the shutdown sequence. The destructor will be called even if script execution
	 *         is stopped using exit(). Calling exit() in a destructor will prevent the remaining shutdown
	 *         routines from executing.
     *
	 *  @brief Attempting to throw an exception from a destructor (called in the time of script termination)
	 *         causes a fatal error.
     *
	 *  @return void
	 */	
	public function __destruct()
	{
	}
	
	/** @fn __set($name, $value)
     *
	 *  @brief Setter function for $this->data
	 *  @brief Example: $obj->first_name = 'Greg';
     *
	 *  @param string $name is the key in the associated $data array
	 *  @param string $value is the value in the assoicated $data array for the identified key
     *
	 *  @return void
	 */
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}
	
	/** @fn __get($name)
     *
	 *  @brief Getter function for $this->data
	 *  @brief echo $obj->first_name;
     *
	 *  @param string $name is the key in the associated $data array
     *
	 *  @return string or null
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->data))
		{
			return $this->data[$name];
		}
		
		return null;
	}
	
	/** @fn    __isset($name)
     *
	 *  @brief Determine if item ($name) exists in the $this->data array
	 *  @brief var_dump(isset($obj->first_name));
     *
	 *  @param string $name is the key in the associated $data array
     *
	 *  @return true or false
	 */	
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}
	
	/** @fn    __unset($name)
     *
	 *  @brief Unset an item in $this->data assoicated by $name
	 *  @brief unset($obj->name);
     *
	 *  @param string $name is the key in the associated $data array
     *
	 *  @return void
	 */	
	public function __unset($name)
	{
		unset($this->data[$name]);
	}		
}
?>
