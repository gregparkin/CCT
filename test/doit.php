<?php
/**
 * Created by  PhpStorm on 1/14/2016 3:59 PM
 *
 * @package    cct6
 * @file       doit.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 *             $Date: $ GMT
 *             $Revision: $
 *             $Date$
 *             $Log $
 *             $Source: $
 */

function substractDays($date, $days)
{
	$ndays = sprintf("-%d days", $days);
	$newdate = strtotime($ndays, strtotime($date));
	$newdate = date('m/d/Y H:i', $newdate);

	return $newdate;
}

$start_date = "Monday, January 25, 2016";
printf("%s\n", substractDays($start_date, 0));
?>
