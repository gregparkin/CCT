<?php
/**
 * scheduler.php
 *
 * @package   PhpStorm
 * @file      scheduler.php
 * @author    gparkin
 * @date      8/2/16
 * @version   7.0
 *
 * @brief     About this module.
 */

/*
 * 	$this->default_weekly    = '+0+TUE,THU+0200+180';
 * 	$this->default_monthly   = '+2+SUN+0000+240';
 * 	$this->default_quarterly = 'FEB,MAY,AUG,NOV+3+SAT+2200+1440';
 */


//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

/**
 * @class scheduler
 *
 * @brief This class is used to compute the actual server work start/end dates and times based upon the IR window and the servers maintenance window.
 * @brief Used by classes: cct6_systems.php and gen_request.php
 * @brief Used by Ajax servers: server_edit_work_request.php and server_test_maintenance_windows.php
 */
class scheduler extends library
{
	var $data = array();
	var $LAST = 9991;

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
		$this->debug_start('scheduler.html');
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
	 * @fn     ComputeStart(
	 *                      $scheduled_starts=array(),
	 *                      $scheduled_ends=array(),
	 *                      $schedule_starting_date,
	 *                      $maintenance_window,
	 *                      $system_timezone_name='America/Denver')
	 *
	 * @brief  After object properties have been set, this function computes the server work start/end/duration dates and times.
	 *
	 * @param  array  $scheduled_starts
	 * @param  array  $scheduled_ends
	 * @param  string $schedule_starting_date
	 * @param  string $maintenance_window
	 * @param  string $system_timezone_name
	 *
	 * @return bool -  true or false, true means success
	 */
	public function ComputeStart(
		$scheduled_starts=array(),
		$scheduled_ends=array(),
		$schedule_starting_date,
		$maintenance_window,
		$system_timezone_name='America/Denver')
	{
		$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "scheduled_starts");
		$this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $scheduled_starts);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "scheduled_ends");
		$this->debug_r1(__FILE__, __FUNCTION__, __LINE__, $scheduled_ends);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "schedule_starting_date: %s", $schedule_starting_date);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "maintenance_window: %s", $maintenance_window);
		$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "system_timezone_name: %s", $system_timezone_name);
		
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

		//
		// Convert the $schedule_starting_date (string) 'mm/dd/yyyy' to utime.
		//
		$begin_start_utime = date('U', strtotime($schedule_starting_date));
		$begin_start_char  = date('m/d/Y', strtotime($schedule_starting_date));

		$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "begin_start_utime: %d (%s)", $begin_start_utime, $begin_start_char);

		if (strlen($schedule_starting_date) == 0 || $begin_start_utime == 0)
		{
			//
			// Hmm, missing date. Let's use a default. 8 days from the current time.
			//
			$begin_start_utime = $this->now_to_gmt_utime() + (86400 * 8);  // + 8 days
			$begin_start_char  = date('m/d/Y', $begin_start_utime);
			$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "begin_start_utime: %d (%s)", $begin_start_utime, $begin_start_char);
		}

		if ($this->now_to_gmt_utime() >= $begin_start_utime)
		{
			//
			// The current date and time ($this->now_to_gmt_utime()) is greater than the $begin_start_utime.
			// Let's fix this!
			//
			//$begin_start_utime = date('U', strtotime($this->now_to_gmt_utime() . ' + 8 days'));
			//$begin_start_char = date('m/d/Y', strtotime($this->now_to_gmt_utime() . ' + 8 days'));

			$begin_start_utime = $this->now_to_gmt_utime() + (86400 * 8);  // + 8 days
			$begin_start_char  = date('m/d/Y', $begin_start_utime);

			$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "begin_start_utime: %d (%s)", $begin_start_utime, $begin_start_char);
		}

		//
		// Format the OS Maintenance Window string.
		//
		if (strlen($maintenance_window) == 0)
		{
			//
			// Hmm, missing maintenance window. Let's use a default.
			//
			$maintenance_window = '+0+TUE,THU+0200+180';
		}
		else
		{
			$maintenance_window = $this->format($maintenance_window);
		}

		$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "maintenance_window: %s", $maintenance_window);

		/**
		 * Create a link list containing all the possible maintenance window patterns.
		 */

		//
		// The formatted OS Maintenance Window string can have multiple patterns consisting of 5 parts.
		// (i.e. Months+Week#+Weekdays+Start+Duration;...repeat pattern...
		//
		$parts = explode(';', $maintenance_window);

		$top = $p = null;

		foreach ($parts as $part)
		{
			if ($top == null)
			{
				$top = $p = new data_node();
			}
			else
			{
				$p->next = new data_node();
				$p = $p->next;
			}

			$fields = explode('+', $part);
			$x      = 1;

			foreach ($fields as $field)  // Months + weekday_occurrence + Weekdays + Start + Duration
			{
				$field = trim($field);

				switch ($x++)
				{
					case 1:  // Months
						if (strlen($field) > 0)
						{
							$p->months = explode(',', $field);   // JAN,APR,JUL,OCT
						}
						else
						{
							$p->months = array('JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC');
						}
						break;
					case 2:  // Weekday Occurrence
						if ($field == 'LAST')
						{
							$p->weekday_occurrence = $this->LAST;
						}
						else
						{
							$p->weekday_occurrence = $field;
						}
						break;
					case 3:  // Weekdays
						if (strlen($field) > 0)
						{
							$p->weekdays = explode(',', $field); // MON,TUE,WED,THU,FRI
						}
						else
						{
							$p->weekdays = array('SUN','MON','TUE','WED','THU','FRI','SAT');
						}
						break;
					case 4:  // Start time
						$p->start = $field;
						$hold     = explode(':', $p->start);
						$p->hr    = $hold[0];
						$p->mi    = $hold[1];
						break;
					case 5:  // Duration
						$p->duration = $field;
						break;
					default:
						break;
				}
			}
		}

		/**
		 * Okay we are using utime for all dates which are total number seconds since 01/01/1970.
		 * We are doing away with monthly and quarterly windows and will just use the weekly window
		 * for now on. In this case, if there is no server weekly maintenance window we will assign
		 * a default ('+0+TUE,THU+0200+180'). Also we must have a beginning starting date that is
		 * greater than the current date. If not we create one by default.
		 */

		/**
		 * $begin_start_utime will actually be midnight 00:00 for the day we want to start scheduling on.
		 * So with that in mind, we will keep advancing $begin_start_utime until we find a matching day of
		 * the week and time we can use from the OS maintenance window string. When find a match we then
		 * check to see if there are any scheduling conflicts. If there are, we continue on for the next
		 * match.
		 *
		 * Because there is a potential possibility for a infinite loop, we won't let the schedule advance
		 * past 355 days.
		 */
		$ndays = 0;

		while ($ndays <= 356)
		{
			++$ndays;

			//
			// Break down $begin_start_utime a bit so we can see what day of week it is and the starting time
			// which should be 00:00.
			//
			$begin_month   = strtoupper(date('M', $begin_start_utime));  // A short representation of a month (Jan, Feb, Mar, ...)
			$begin_day     =            date('d', $begin_start_utime);   // The day of the month (from 01 to 31)
			$begin_year    =            date('Y', $begin_start_utime);   // A four digit representation of a year
			$begin_mdays   =            date('t', $begin_start_utime);   // The number of days in the given month
			$begin_weekday = strtoupper(date('D', $begin_start_utime));  // [SUN,MON,TUE,WED,THU,FRI,SAT]
			//$begin_hour    =            date('G', $begin_start_utime);   // 24-hour format of an hour (0 to 23)
			//$begin_minutes =            date('i', $begin_start_utime);   // Minutes with leading zeros (00 to 59)

			// $begin_array = getdate($begin_start_utime);
			// $x = getdate(time());
			//
			// printf("seconds: %d\n", $x['seconds']);
			// printf("minutes: %d\n", $x['minutes']);
			// printf("  hours: %d\n", $x['hours']);
			// printf("   mday: %d\n", $x['mday']);
			// printf("   wday: %d\n", $x['wday']);
			// printf("    mon: %d\n", $x['mon']);
			// printf("   year: %d\n", $x['year']);
			// printf("   yday: %d\n", $x['yday']);
			// printf("weekday: %s\n", $x['weekday']);
			// printf("m  onth: %s\n", $x['month']);
			// printf("  Epoch: %d\n", $x['0']);

			//
			// Look for match!
			//
			$match = false;

			for ($p=$top; $p!=null; $p=$p->next)
			{
				foreach ($p->months as $month)
				{
					if ($month == $begin_month)
					{
						foreach ($p->weekdays as $weekday)
						{
							if ($weekday == $begin_weekday)
							{
								$continue_on = true;

								//
								// If the weekday occurrence is greater than zero then we need to
								// match on the weekday occurrence number for the given month.
								//
								if ($p->weekday_occurrence > 0 && $p->weekday_occurence != $this->LAST)
								{
									$dd                 = date('j', $begin_start_utime);  // month day 1-31
									$this_weekday       = date("w", $begin_start_utime);  // weekday number 0-6 where 0 = Sunday
									$starting_weekday   = $this_weekday;
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

									// $weekday_occurrence contains this days weekday occurence number for the month. (i.e. 2 Tue)

									if ($p->weekday_occurrence != $weekday_occurrence)
									{
										$continue_on = false;
									}

								}
								else if ($p->weekday_occurrence == $this->LAST && $begin_mdays > ($begin_day + 7))
								{
									$continue_on = false;
								}

								if ($continue_on == true)
								{
									//
									// Build start/end strings - 06-JAN-2016 02:00
									//
									$new_start_utime =
										strtotime(sprintf("%s-%s-%s %d:%s", $begin_day, $begin_month, $begin_year, $p->hr, $p->mi));

									$mm = date('m', $new_start_utime); // 01-12
									$dd = date('d', $new_start_utime); // 01-31
									$yy = date('Y', $new_start_utime); // 2016
									$hr = date('H', $new_start_utime); // 00-23
									$mi = date('i', $new_start_utime); // 00-59

									$total = $hr * 60 + $mi + $p->duration;

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

									$hours   = $total / 60;
									$minutes = $total - ($hours * 60);
									$hours   = floor($hours);
									$minutes = floor($minutes);

									if ($hours == 24)
										$hours = 0;

									$new_end_utime =
										strtotime(
											str_pad($mm,      2, "0", STR_PAD_LEFT) . '/' .
											str_pad($dd,      2, "0", STR_PAD_LEFT) . '/' . $yy . ' ' .
											str_pad($hours,   2, "0", STR_PAD_LEFT) . ':' .
											str_pad($minutes, 2, "0", STR_PAD_LEFT));

									/**
									 * Do a conflict analysis using the $scheduled_starts[] and $schedule_ends[] date arrays.
									 *
									 * If array counts are greater than 0 and the two array counts match, then proceed.
									 */
									$okay = true;

									if ($scheduled_starts_count > 0 && $scheduled_starts_count == $scheduled_ends_count)
									{
										for ($i=0; $i<$scheduled_starts_count; $i++)
										{
											//
											//                   SS----------------------------SE
											//        S-------------E
											//                      S--------E
											//                                    S-----------------E
											//                S----------------------------------------E
											//
											if ($new_start_utime >= $scheduled_starts[$i] && $new_start_utime <= $scheduled_ends[$i])
											{
												$okay = false;
												continue;
											}

											if ($new_end_utime >= $scheduled_starts[$i] && $new_end_utime <= $scheduled_ends[$i])
											{
												$okay = false;
												continue;
											}

											if ($scheduled_starts[$i] < $new_start_utime && $scheduled_ends[$i] > $new_end_utime)
											{
												$okay = false;
												continue;
											}
										}
									}

									//
									// Is there any scheduling conflicts for this server?
									//
									if ($okay == true)
									{
										//
										// No conflicts. Let's go with these dates.
										//
										$this->system_work_start_date_char = date('m/d/Y H:i', $new_start_utime);
										$this->system_work_start_date_num  = $this->to_gmt($this->system_work_start_date_char, $system_timezone_name);

										$this->system_work_end_date_char   = date('m/d/Y H:i', $new_end_utime);
										$this->system_work_end_date_num    = $this->to_gmt($this->system_work_end_date_char, $system_timezone_name);

										$seconds = $new_end_utime - $new_start_utime;

										$days  = floor($seconds / 60 / 60 / 24);
										$hours = $seconds / 60 / 60 % 24;
										$mins  = $seconds / 60 % 60;
										// $secs  = $seconds % 60;

										$this->system_work_duration = sprintf("%02d%s%02d%s%02d", $days, ':', $hours, ':', $mins); // 47:56:15

										$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "system_work_start_date_char: %s", $this->system_work_start_date_char);
										$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "system_work_start_date_num: %d",  $this->system_work_start_date_num);
										$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "system_work_end_date_char: %s",   $this->system_work_end_date_char);
										$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "system_work_end_date_num: %d",    $this->system_work_end_date_num);
										$this->debug1(__FILE__, __FUNCTION__, __LINE__,   "system_work_duration: %s",        $this->system_work_duration);

										return true;
									}
								}
							}
						}
					}
				}
			}

			//
			// Increment $begin_starting_date by one day and look for another match.
			//
			$begin_start_utime += 86400;  // 60 * 60 * 24 = total number of seconds in a day
		}

		//
		// Could not find any matches even though we might have used a default maintenance window.
		// We should never reach here!
		//
		$this->error = "Unable to schedule this server!";

		return false;
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

	// make_formatter.php

	/** @fn     format($unformated)
	 *
	 *  @brief  This function formats the unformatted maintenance window string.
	 *
	 *  @param  string $unformatted OS Maintenance Window string
	 *
	 *  @return string
	 */
	private function format($unformatted)
	{
		$month_list   = array("JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEP","OCT","NOV","DEC");
		$weekday_list = array("SUN","MON","TUE","WED","THU","FRI","SAT");

		if (($x = strpos($unformatted, '#')) > 0)
		{
			$maintwin = substr($unformatted, 0, $x); // Remove # comments from $text
		}
		else
		{
			$maintwin = $unformatted;
		}

		$maintwin = trim(strtoupper($maintwin)); // Remove any leading and trailing space characters, and make uppercase
		$this->debug4(__FILE__, __FUNCTION__, __LINE__, "maintwin = (%s)", $maintwin);

		$formatted = '';
		$line1     = explode(';', $maintwin);

		// MONTHS+WEEK+WEEKDAYS+START+DURATION;MONTHS+WEEK+WEEKDAYS+START+DURATION
		foreach ($line1 as $item1)
		{
			$line2        = explode(' ', $item1);
			$months       = '';
			$week_no      = 0;
			$weekdays     = '';
			$start        = '';
			$duration     = 120;

			$got_months   = false;
			$got_week_no  = false;
			$got_weekdays = false;
			$got_start    = false;
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
							$months     = $item2;
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
							$weekdays     = $item2;
							$got_weekdays = true;
							break;
						}
					}

					if ($got_weekdays == true)
						continue;
				}

				if ($got_start == false && strlen($item2) == 5)
				{
					$start     = $item2;
					$got_start = true;
					continue;
				}

				if ($got_start && !$got_duration && ctype_digit($item2))
				{
					$duration     = $item2;
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
}