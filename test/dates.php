<?php
/**
 * Created by  PhpStorm on 3/16/2016 9:21 AM
 *
 * @package    cct7
 * @file       dates.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 *             $Date: $ GMT
 *             $Revision: $
 *             $Date$
 *             $Log $
 *             $Source: $
 */

echo "Thursday, 21 July 2016 12:00 = " . gmdate('U', strtotime('Thursday, 21 JULY 2016 12:00'));

$gmt_time = gmdate('U', strtotime('Thursday, 21 JULY 2016 12:00'));



$dt = new DateTime();
//$dt->setTimezone(new DateTimeZone('Europe/London'));
$dt->setTimestamp($gmt_time);
$dt->setTimezone(new DateTimeZone('America/Denver'));
printf("\n%s\n", $dt->format('m/d/Y H:i'));

printf("gmt_time_to_mmddyyyy_hhmm = %s\n", gmt_time_to_mmddyyyy_hhmm($gmt_time, 'America/Denver'));
exit();

function mmmddyyyy_hhmm_to_gmt_utime($mmddyyyy_hhmm, $from_tz)
{
	$dt = new DateTime($mmddyyyy_hhmm, new DateTimeZone($from_tz));
	$dt->setTimezone(new DateTimeZone('Europe/London'));
	return $dt->format('U');
}

function gmt_time_to_mmddyyyy_hhmm($gmt_time, $to_tz)
{
	$dt = new DateTime();
	$dt->setTimezone(new DateTimeZone('Europe/London'));
	$dt->setTimestamp($gmt_time);
	$dt->setTimezone(new DateTimeZone($to_tz));
	return $dt->format('m/d/Y H:i');
}

date_default_timezone_set('America/New_York');
printf("Now: %s\n", date('U', time()));

$what = '03/19/2016 10:00';
$utime = mmmddyyyy_hhmm_to_gmt_utime($what, 'America/Denver');

$back  = gmt_time_to_mmddyyyy_hhmm($utime, 'America/Denver');
$cst   = gmt_time_to_mmddyyyy_hhmm($utime, 'America/Chicago');

printf("From: %s MDT = %d GMT\n", $what, $utime);
printf("Back: %d GMT = %s MDT\n", $utime, $back);
printf("Back: %d GMT = %s CDT\n", $utime, $cst);


$dateTime = new DateTime();
$dateTime->setTimeZone(new DateTimeZone('America/Chicago'));
$dateTime->setDate(2016, 3, 16);
$dateTime->setTime(21, 0);

printf("\n%s %s epoc = %d\n", $dateTime->format('m/d/Y H:i'), $dateTime->format('T'), $dateTime->format('U'));




printf("\n%s %s epoc = %d\n", $dateTime->format('m/d/Y H:i'), $dateTime->format('T'), $dateTime->format('U'));

echo(strtotime("now") . "\n");
echo(strtotime("3 October 2005") . "\n");
echo(strtotime("+5 hours") . "\n");
echo(strtotime("+1 week") . "\n");
echo(strtotime("+1 week 3 days 7 hours 5 seconds") . "\n");
echo(strtotime("next Monday") . "\n");
echo(strtotime("last Sunday") . "\n");


printf("Thursday, 21 JULY 2015 = %s\n", strtotime("Thursday, 21 JULY 2015"));
exit();

$tz = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY,'US');



foreach ($tz as $key => $zoneName)
{
	$z = new DateTimeZone($zoneName);
	$loc = $z->getLocation();
	printf("%s = %s\n", $zoneName, $loc['comments']);
}


?>
