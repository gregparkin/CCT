#!/xxx/apache/php/bin/php -q
<?php
/**
 * <cct6_auto.php>
 *
 * @package    CCT
 * @file       cct6_auto.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       10/11/2013
 * @version    6.0.0
 */

//
// Send all email and pages every 5 minutes
//
// Run from cron every 5 minutes starting at 0,5,10,15,20,25,30,35,40,45,50,55
//
 
//
// Class autoloader - /xxx/www/cct/classes:/xxx/www/cct/servers:/xxx/www/cct/includes
// See: include_paths= in file /xxx/apache/php/php.ini
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

$now = date('m/d/Y h:i');

$email = new cct6_email_spool();

//
// Send spooled Ticket Messages
//
if ($email->SendSpooled() == false)
{
	printf("%s SendSpooled(): %s\n", $now, $email->error);
}
else if ($email->email_success > 0 || $email->email_failure > 0)
{
	printf("%s Send Ticket Work Status: Success=%d, Failure=%d\n", $now, $email->email_success, $email->email_failure);
}

//
// Send spooled Server Work Reschedule Messages
//
if ($email->SendReschedules() == false)
{
	printf("%s SendReschedules(): %s\n", $now, $email->error);
}
else if ($email->email_success > 0 || $email->email_failure > 0)
{
	printf("%s Send Reschedules: Success=%d, Failure=%d\n", $now, $email->email_success, $email->email_failure);
}

$page = new cct6_page_spool();  // classes/cct6_page_spool.php

//
// Send out any auto pages
//
if ($page->AutoPages() == false)
{
	printf("%s AutoPages(): %s\n", $now, $page->error);
	exit();
}
else if ($page->work_start_auto_pages > 0 || $page->work_end_auto_pages > 0)
{
	printf("%s Work start auto pages: %d,  Work end auto pages: %d\n", $now, $page->work_start_auto_pages, $page->work_end_auto_pages);
}

//
// Send out any spooled pages
//
if ($page->SendPages() == false)
{
	printf("%s SendPages(): %s\n", $now, $page->error);
}
else if ($page->page_success > 0 || $page->page_failure > 0)
{
	printf("%s Send Pages: Success=%d, Failure=%d\n", $now, $page->page_success, $page->page_failure);
}

//
// Send spooled ADHOC messages to server contacts only
//
if ($email->SendADHOC() == false)
{
	printf("%s SendADHOC(): %s\n", $now, $email->error);
}
else if ($email->email_success > 0 || $email->email_failure > 0)
{
	printf("%s Send ADHOC: Success=%d, Failure=%d\n", $now, $email->email_success, $email->email_failure);
}

//
// Send spooled Work Status
//
if ($email->SendStatus() == false)
{
	printf("%s SendStatus(): %s\n", $now, $email->error);
}
else if ($email->email_success > 0 || $email->email_failure > 0)
{
	printf("%s Send Server Work Status: Success=%d, Failure=%d\n", $now, $email->email_success, $email->email_failure);
}

?>
