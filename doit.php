<?php
/**
 * doit.php
 *
 * @package   PhpStorm
 * @file      doit.php
 * @author    gparkin
 * @date      7/23/16
 * @version   7.0
 *
 * @brief     About this module.
 */

ini_set('xdebug.collect_vars',    '5');
ini_set('xdebug.collect_vars',    'on');
ini_set('xdebug.collect_params',  '4');
ini_set('xdebug.dump_globals',    'on');
ini_set('xdebug.dump.SERVER',     'REQUEST_URI');
ini_set('xdebug.show_local_vars', 'on');

ini_set("error_reporting", "E_ALL & ~E_DEPRECATED & ~E_STRICT");
ini_set("log_errors", 1);
ini_set("error_log", "/opt/ibmtools/cct7/logs/php-error.log");
ini_set("log_errors_max_len", 0);
ini_set("report_memleaks", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
ini_set("ignore_repeated_errors", 0);
ini_set("ignore_repeated_source", 0);

//$path = '/usr/lib/pear';
//set_include_path(get_include_path() . PATH_SEPARATOR . $path);

set_include_path("/opt/ibmtools/www/cct7/classes");

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('classes/autoloader.php');
}

date_default_timezone_set('America/Denver');

$list = new email_contacts();

//$list->byTicket("CCT700049402", "N", "N");
//$list->bySystem(1038861, "Y", "Y");
//$list->byContact("CCT700049281", 1038861, 12283205, "Y", "Y", "N");

$netpin_list = array();
$netpin_list['17340'] = "APPROVER";
$list->buildList($netpin_list);

foreach ($list->email_list as $cuid => $name_and_email)
{
	printf("%s|%s\n", $cuid, $name_and_email);
}


exit();

$start_utime  = 0;
$end_utime    = 0;
$start_string = "";
$end_string   = "";

today($start_utime, $end_utime, $start_string, $end_string);

printf("BEG: %d = %s\n", $start_utime, $start_string);
printf("END: %d = %s\n", $end_utime, $end_string);



exit();

error_log("TEST LOG MESSAGE");

$str = "Hello,\r\n";
$str .= "Greg Parkin!\r\n";

echo shell_exec('echo' . $str);





exit();


//
//
//$content = file_get_contents('/opt/ibmtools/cct7/email/mpatra.html');

//Create a new PHPMailer instance
$mail = new PHPMailer7();

// Set PHPMailer to use the sendmail transport
$mail->isSendmail();

//Set who the message is to be sent from
$mail->setFrom('gregparkin58@gmail.com', 'Greg Parkin');

//Set an alternative reply-to address
$mail->addReplyTo('gregparkin58@gmail.com', 'Greg Parkin');

//Set who the message is to be sent to
$mail->addAddress('gregparkin58@gmail.com', 'Greg Parkin');

//Set the subject line
$mail->Subject = 'PHPMailer sendmail test';

//Read an HTML message body from an external file, convert referenced images to embedded,
//convert HTML into a basic plain-text alternative body
$mail->msgHTML(file_get_contents('/opt/ibmtools/cct7/email/mpatra.html'), dirname(__FILE__));

//Replace the plain text body with one created manually
$mail->AltBody = 'This is a plain-text message body';

//Attach an image file
//$mail->addAttachment('images/phpmailer_mini.png');

//send the message, check for errors
if (!$mail->send()) {
	echo "Mailer Error: " . $mail->ErrorInfo;
} else {
	echo "Message sent!";
}


exit();
$ora  = new oracle();
$ora2 = new oracle();

//
// Get the record count of cct7_mnet to make sure it is not empty!
//
$ora->sql2("select count(*) as total_records from cct7_mnet");
$ora->fetch();
$total_records = $ora->total_records;

printf("Total Records: %d\n", $total_records);
printf("%s\n", $ora->sql_statement);
printf("%s\n", $ora->dbErrMsg);
exit();

if ($ora->sql2("select * from cct7_subscriber_groups order by group_name") == false)
{
	printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
	exit();
}

while ($ora->fetch())
{
	$query = sprintf(
		"select * from cct7_mnet where mnet_cuid = '%s'", $ora->owner_cuid);

	if ($ora2->sql2($query) == false)
	{
		printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
		exit();
	}

	if ($ora2->fetch())
	{
		printf("Subscriber Group has been validated: %s\n", $ora->group_name);
	}
	else
	{
		printf("Deleting Subscriber Group owned by %s - %s\n", $ora->owner_cuid, $ora->group_name);
		$query = sprintf("delete cct7_subscriber_groups where owner_cuid = '%s'", $ora->owner_cuid);

		if ($ora2->sql2($query) == false)
		{
			printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
			exit();
		}
	}
}

if ($ora2->sql2("delete from cct7_subscriber_groups where owner_cuid is null") == false)
{
	printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
	exit();
}

$ora2->commit();

// Delete obsolete subscriber members
//
if ($ora->sql2("select * from cct7_subscriber_members order by member_cuid") == false)
{
	printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
	exit();
}

$member_list = array();

while ($ora->fetch())
{
	$query = sprintf(
		"select * from cct7_mnet where mnet_cuid = '%s'", $ora->member_cuid);

	if ($ora2->sql2($query) == false)
	{
		printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
		exit();
	}

	if ($ora2->fetch())
	{
		printf("Subscriber member has been validated: %s\n", $ora->member_cuid);
	}
	else
	{
		printf("Deleting subscriber member: %s\n", $ora->member_cuid);
		$member_list[$ora->member_cuid] = $ora->member_name;
	}
}

if ($ora2->sql2("delete from cct7_subscriber_members where member_cuid is null") == false)
{
	printf("%s - %s\n", $ora->sql_statement, $ora->dbErrMsg);
	exit();
}

$ora2->commit();



exit();

function yesterday(&$start_utime, &$end_utime, &$start_string, &$end_string)
{
	$dt = new DateTime("now");
	$dt->setTimezone(new DateTimeZone('GMT'));

	$start_utime = strtotime('-1 day', strtotime($dt->format('m/d/Y 00:00:00')));
	$end_utime   = strtotime('-1 day', strtotime($dt->format('m/d/Y 23:59:59')));

	$start_string = date ( 'm/d/Y H:i:s' , $start_utime );
	$end_string = date ( 'm/d/Y H:i:s' , $end_utime );
}

function today(&$start_utime, &$end_utime, &$start_string, &$end_string)
{
	$dt = new DateTime("now");

	$start = $dt->format('m/d/Y 00:00:00');
	$end   = $dt->format('m/d/Y 23:59:59');

	$dt1 = new DateTime($start);
	//$dt1->setTimezone(new DateTimeZone('GMT'));

	$dt2 = new DateTime($end);
	//$dt2->setTimezone(new DateTimeZone('GMT'));

	$start_utime  = $dt1->format('U');
	$end_utime    = $dt2->format('U');

	$start_string = $dt1->format('m/d/Y H:i:s');
	$end_string   = $dt2->format('m/d/Y H:i:s');
}

function tomorrow(&$start_utime, &$end_utime, &$start_string, &$end_string)
{
	$dt = new DateTime("now");
	$dt->setTimezone(new DateTimeZone('GMT'));

	$start_utime  = strtotime('+1 day', strtotime($dt->format('m/d/Y 00:00:00')));
	$end_utime    = strtotime('+1 day', strtotime($dt->format('m/d/Y 23:59:59')));

	$start_string = date ( 'm/d/Y H:i:s' , $start_utime );
	$end_string   = date ( 'm/d/Y H:i:s' , $end_utime );
}

$b = 0;
$e = 0;

$ss = "";
$es = "";

yesterday($b, $e, $ss, $es);
printf("yesterday: %d -> %d  %s -> %s\n", $b, $e, $ss, $es);

today($b, $e, $ss, $es);
printf("    today: %d -> %d  %s -> %s\n", $b, $e, $ss, $es);

tomorrow($b, $e, $ss, $es);
printf(" tomorrow: %d -> %d  %s -> %s\n", $b, $e, $ss, $es);


exit();

$dt = new DateTime('07/12/2017 00:00');
$dt->setTimezone(new DateTimeZone('GMT'));

printf("%d\n", $dt->format('U'));

$lib = new library();

$dt = new DateTime('05/21/2017 00:00:00', new DateTimeZone('GMT'));
printf("%s GMT\n\n", $dt->format('U - m/d/Y H:i:s'));
$dt = new DateTime('05/21/2017 23:59:59', new DateTimeZone('GMT'));
printf("%s GMT\n\n", $dt->format('U - m/d/Y H:i:s'));


$dt = new DateTime('05/21/2017 00:00:00', new DateTimeZone('America/Denver'));
printf("%s MDT\n\n", $dt->format('U - m/d/Y H:i:s'));
$dt = new DateTime('05/21/2017 23:59:59', new DateTimeZone('America/Denver'));
printf("%s MDT\n\n", $dt->format('U - m/d/Y H:i:s'));
exit();

$utime = $lib->to_gmt("05/21/2017", "America/Denver");
printf("%d - 05/21/2017 America/Denver\n", $utime);
printf("%d - %s\n", $utime, $lib->gmt_to_format($utime, 'm/d/Y H:i', 'GMT'));

$dt = new DateTime('05/21/2017 00:00');
$dt->setTimezone(new DateTimeZone('GMT'));


printf("%s\n", $dt->format('U - m/d/Y H:i'));
exit();

$date1 = new DateTime("05/05/2017 05:30.23");
$date2 = new DateTime("now");
$interval = $date1->diff($date2);
echo $interval->format('%H:%I:%S') . "\n";

?>
