#!/opt/lampp/bin/php -q
<?php
/**
 * <spool_escalations.php>
 *
 * @package    CCT
 * @file       spool_escalations.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       10/11/2013
 * @version    6.0.0
 */
 
//
// Spool any escalations messages that need to go out.
//
// Run once a day through: run_nightly.php
//

//
// Class autoloader - /xxx/www/cct/classes:/xxx/www/cct/servers:/xxx/www/cct/includes
// See: include_paths= in file /xxx/apache/php/php.ini
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

$email = new cct6_email_spool();  // classes/cct6_email_spool.php

if ($email->SendReadEsc1() == false)
{
	printf("SendReadEsc1(): %s\n", $email->error);
}
else
{
	printf("Send Read ESC1: Success=%d, Failure=%d\n", $email->read_esc1_success, $email->read_esc1_failure);
}

if ($email->SendReadEsc2() == false)
{
	printf("SendReadEsc2(): %s\n", $email->error);
}
else
{
	printf("Send Read ESC2: Success=%d, Failure=%d\n", $email->read_esc2_success, $email->read_esc2_failure);
}

if ($email->SendReadEsc3() == false)
{
	printf("SendReadEsc3(): %s\n", $email->error);
}
else
{
	printf("Send Read ESC3: Success=%d, Failure=%d\n", $email->read_esc3_success, $email->read_esc3_failure);
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

if ($email->SendRespEsc1() == false)
{
	printf("SendRespEsc1(): %s\n", $email->error);
}
else
{
	printf("Send Response ESC1: Success=%d, Failure=%d\n", $email->resp_esc1_success, $email->resp_esc1_failure);
}

if ($email->SendRespEsc2() == false)
{
	printf("SendRespEsc2(): %s\n", $email->error);
}
else
{
	printf("Send Response ESC2: Success=%d, Failure=%d\n", $email->resp_esc2_success, $email->resp_esc2_failure);
}

if ($email->SendRespEsc3() == false)
{
	printf("SendRespEsc3(): %s\n", $email->error);
}
else
{
	printf("Send Response ESC3: Success=%d, Failure=%d\n", $email->resp_esc3_success, $email->resp_esc3_failure);
}
?>
