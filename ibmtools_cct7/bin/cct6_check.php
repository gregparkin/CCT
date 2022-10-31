#!/xxx/apache/php/bin/php -q
<?php
/**
 * <cct6_check.php>
 *
 * @package    CCT
 * @file       cct6_check.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       06/23/2015
 * @version    6.0.0
 */

//
// Run from cron once a day.
//

//
// Class autoloader - /xxx/www/cct/classes:/xxx/www/cct/servers:/xxx/www/cct/includes
// See: include_paths= in file /xxx/apache/php/php.ini
//
if (!function_exists('__autoload'))
{
        require_once('autoloader.php');
}

$logdir = "/xxx/cct6/logs";
$check_log = $logdir . "/check.log";
$yesterday = date('d-M-y', time() - 86400);  // i.e. 01-JUN-15

$fp = fopen($check_log, "w") or die($php_errormsg);

fprintf($fp, "<h1>CCT Daily Check for %s</h1>\n", $yesterday);

function run($cmd)
{
        global $fp;

        fprintf($fp, "<h3><b>%s</b></h3>\n", $cmd);
        fprintf($fp, "<pre>\n");
        $fpPipe = popen($cmd . ' 2>&1', 'r');

        while (($buffer = fgets($fpPipe, 4096)) !== false)
        {
                fprintf($fp, "%s", $buffer);
        }

        pclose($fpPipe);
        fprintf($fp, "</pre>\n");
}

function viewLog($filename)
{
        global $logdir, $fp;

        fprintf($fp, "<h2><b>View Last 25 lines of %s/%s</b></h2>\n", $logdir, $filename);
        run("tail -25 " . $logdir . "/" . $filename);
}

run("cd /xxx/cct6/logs; ls -ld *");
run("cd /xxx/cct6/logs; cat run_nightly.log");

viewLog("cct6_auto.log");
viewLog("mail.log");
viewLog("make_applications.log");
viewLog("make_computers.log");
viewLog("make_computer_status.log");
viewLog("make_contract.log");
viewLog("make_csc.log");
viewLog("make_databases.log");
viewLog("make_managing_group.log");
viewLog("make_mnet.log");
viewLog("make_net_members.log");
viewLog("make_netpin_to_cuid.log");
viewLog("make_os_lite.log");
viewLog("make_platform.log");
viewLog("make_state_city.log");
viewLog("make_virtual_servers.log");
viewLog("spool_escalations.log");
viewLog("update_assign_groups.log");
viewLog("update_reschedules.log");
viewLog("update_tickets.log");

fprintf($fp, "<hr><h1><b>Dumping email spool from yesterday to current.</b></h1>\n");

$ora = new dbms();

$query  = "select distinct  ";
$query .= "  e.email_spool_id              as email_spool_id, ";
$query .= "  e.sendmail_date               as sendmail_date, ";
$query .= "  e.sendmail_successful         as sendmail_successful, ";
$query .= "  e.spool_date                  as spool_date, ";
$query .= "  e.from_cuid                   as from_cuid, ";
$query .= "  e.from_name                   as from_name, ";
$query .= "  e.from_email                  as from_email, ";
$query .= "  e.to_cuid                     as to_cuid, ";
$query .= "  m.mnet_first_name             as to_first_name, ";
$query .= "  m.mnet_nick_name              as to_nick_name, ";
$query .= "  e.to_name                     as to_name, ";
$query .= "  e.to_email                    as to_email, ";
$query .= "  e.email_template              as email_template, ";
$query .= "  e.email_subject               as email_subject, ";
$query .= "  e.email_message               as email_message, ";
$query .= "  e.cc_owner                    as cc_owner, ";
$query .= "  t.ticket_status               as ticket_status, ";
$query .= "  t.classification              as classification, ";
$query .= "  t.ticket_cancel_date          as ticket_cancel_date, ";
$query .= "  t.ticket_cancel_name          as ticket_cancel_name, ";
$query .= "  t.ticket_cancel_comments      as ticket_cancel_comments, ";
$query .= "  t.ticket_resp_esc3_date       as respond_by_date, ";
$query .= "  t.cm_ticket_no                as cm_ticket_no, ";
$query .= "  t.cm_open_closed              as cm_open_closed, ";
$query .= "  t.cm_status                   as cm_status, ";
$query .= "  t.cm_start_date               as cm_start_date, ";
$query .= "  t.cm_end_date                 as cm_end_date, ";
$query .= "  t.cm_duration_computed        as cm_duration_computed, ";
$query .= "  t.cm_assign_group             as cm_assign_group, ";
$query .= "  t.cm_entered_by               as cm_entered_by, ";
$query .= "  t.cm_ipl_boot                 as cm_ipl_boot, ";
$query .= "  t.cm_description               as cm_description, ";
$query .= "  t.ticket_submit_note          as ticket_submit_note, ";
$query .= "  s.system_id                   as system_id, ";
$query .= "  s.computer_hostname           as computer_hostname, ";
$query .= "  s.computer_os_lite            as computer_os_lite, ";
$query .= "  s.computer_status             as computer_status, ";
$query .= "  s.system_work_status          as system_work_status, ";
$query .= "  s.system_actual_work_start    as system_actual_work_start, ";
$query .= "  s.system_actual_work_end      as system_actual_work_end, ";
$query .= "  s.system_actual_work_duration as system_actual_work_duration, ";
$query .= "  c.contact_id                  as contact_id, ";
$query .= "  c.contact_cuid                as contact_cuid, ";
$query .= "  c.contact_name                as contact_name, ";
$query .= "  c.contact_email               as contact_email, ";
$query .= "  c.contact_group_type          as contact_group_type, ";
$query .= "  c.contact_notify_type         as contact_notify_type ";
$query .= "from ";
$query .= "  cct6_email_spool e, ";
$query .= "  cct6_tickets     t, ";
$query .= "  cct6_systems     s, ";
$query .= "  cct6_contacts    c, ";
$query .= "  cct6_mnet        m ";
$query .= "where ";
$query .= "  t.cm_ticket_no = e.cm_ticket_no and ";
$query .= "  s.cm_ticket_no = t.cm_ticket_no and ";
$query .= "  s.computer_hostname = e.computer_hostname and ";
$query .= "  c.system_id = s.system_id and ";
$query .= "  c.contact_cuid = e.to_cuid and ";
$query .= "  m.mnet_cuid = e.to_cuid and ";
//$query .= "  e.sendmail_date is null ";

$query .= "  e.spool_date >= to_date('" . $yesterday . "', 'DD-MON-YY') ";

//$query .= "  e.email_subject is null and ";   // Used instead of the email_template != xxx commented out below
//$query .= "  e.email_message is null ";       // Used instead of the email_template != xxx commented out below
//$query .= "  e.email_template != 'SUCCESS' and ";
//$query .= "  e.email_template != 'STARTING' and ";
//$query .= "  e.email_template != 'FAILURE' and ";
//$query .= "  e.email_template != 'CANCEL' and ";
//$query .= "  e.email_template != 'BACKOUT' and ";
//$query .= "  e.email_template != 'ADHOC' ";
$query .= "order by ";
$query .= "  e.to_cuid, ";
$query .= "  t.cm_ticket_no, ";
$query .= "  s.computer_hostname, ";
$query .= "  c.contact_id";

if ($ora->sql($query))
{
        fprintf($fp, "<table cellspacing=2 cellpadding=2 border=1>\n");
        fprintf($fp, "<tr>\n");
        fprintf($fp, " <td><b>spool id</b></td>\n");
        fprintf($fp, " <td><b>sendmail_date</b></td>\n");
        fprintf($fp, " <td><b>sendmail_successful</b></td>\n");
        fprintf($fp, " <td><b>spool_date</b></td>\n");
        fprintf($fp, " <td><b>from_email</b></td>\n");
        fprintf($fp, " <td><b>to_email</b></td>\n");
        fprintf($fp, " <td><b>email_template</b></td>\n");
        fprintf($fp, " <td><b>cc_owner</b></td>\n");
        fprintf($fp, "</tr>\n");

        while ($ora->fetch())
        {
                fprintf($fp, "<tr>\n");
                fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->email_spool_id);
                fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->sendmail_date);
                fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->sendmail_successful);
                fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->spool_date);
                fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->from_email);
                fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->to_email);
                fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->email_template);
                fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->cc_owner);
                fprintf($fp, "</tr>\n");
        }

        fprintf($fp, "</table>\n");
}
else
{
        fprintf($fp, "SQL ERROR: %s\n", $ora->dbErrMsg);
}

fprintf($fp, "<hr><h1><b>Checking CMP Employee Email Addresses in cct6_mnet.</b></h1>\n");

$query = "select count(*) as count from cct6_mnet where mnet_company = 'CMP'";
$ora->sql($query);
$ora->fetch();
fprintf($fp, "CMP employees in cct6_mnet: %d<br>\n", $ora->count);

$query = "select count(*) as count from cct6_mnet where  mnet_company = 'CMP' and mnet_email not like '%%ibm.com'";
$ora->sql($query);
$ora->fetch();
fprintf($fp, "CMP Employees in cct6_mnet missing CMP Email addresses: %d<br><br>\n", $ora->count);


$query = "select * from cct6_mnet where mnet_company = 'CMP' and mnet_email not like '%%ibm.com' order by mnet_name";

if ($ora->sql($query))
{
		fprintf($fp, "<table cellspacing=2 cellpadding=2 border=1>\n");
        fprintf($fp, "<tr>\n");
        fprintf($fp, " <td><b>mnet_cuid</b></td>\n");
        fprintf($fp, " <td><b>mnet_company</b></td>\n");
        fprintf($fp, " <td><b>mnet_name</b></td>\n");
        fprintf($fp, " <td><b>mnet_email</b></td>\n");
        fprintf($fp, " <td><b>mnet_job_title</b></td>\n");
        fprintf($fp, " <td><b>mnet_city</b></td>\n");
        fprintf($fp, " <td><b>mnet_state</b></td>\n");
        fprintf($fp, "</tr>\n");
		
	while ($ora->fetch())
	{
		fprintf($fp, "<tr>\n");
        fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->mnet_cuid);
        fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->mnet_company);
        fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->mnet_name);
        fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->mnet_email);
        fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->mnet_job_title);
        fprintf($fp, " <td align=left valign=top>%s</td>\n", $ora->mnet_city);
        fprintf($fp, " <td align=left valign=top>%s&nbsp;</td>\n", $ora->mnet_state);
        fprintf($fp, "</tr>\n");
	}
	
	fprintf($fp, "</table>\n");
}
else
{
        fprintf($fp, "SQL ERROR: %s\n", $ora->dbErrMsg);
}

$tables = array();

array_push($tables, 'CCT6_APPLICATIONS');
array_push($tables, 'CCT6_ASSIGN_GROUPS');
array_push($tables, 'CCT6_AUTO_APPROVE');
array_push($tables, 'CCT6_CLASSIFICATIONS');
array_push($tables, 'CCT6_COMPUTERS');
array_push($tables, 'CCT6_COMPUTER_STATUS');
array_push($tables, 'CCT6_CONTACTS');
array_push($tables, 'CCT6_CONTRACT');
array_push($tables, 'CCT6_CSC');
array_push($tables, 'CCT6_DATABASES');
array_push($tables, 'CCT6_DEBUGGING');
array_push($tables, 'CCT6_EMAIL_LIST');
array_push($tables, 'CCT6_EMAIL_SPOOL');
array_push($tables, 'CCT6_EVENT_LOG');
array_push($tables, 'CCT6_LIST_NAMES');
array_push($tables, 'CCT6_LIST_SYSTEMS');
array_push($tables, 'CCT6_MANAGING_GROUP');
array_push($tables, 'CCT6_MASTER_APPROVERS');
array_push($tables, 'CCT6_MNET');
array_push($tables, 'CCT6_NETPIN_TO_CUID');
array_push($tables, 'CCT6_NET_MEMBERS');
array_push($tables, 'CCT6_ONCALL_OVERRIDES');
array_push($tables, 'CCT6_OS_LITE');
array_push($tables, 'CCT6_PAGE_SPOOL');
array_push($tables, 'CCT6_PLATFORM');
array_push($tables, 'CCT6_STATE_CITY');
array_push($tables, 'CCT6_SUBSCRIBER_LISTS');
array_push($tables, 'CCT6_SYSTEMS');
array_push($tables, 'CCT6_TICKETS');
array_push($tables, 'CCT6_USER_PROFILES');
array_push($tables, 'CCT6_VIRTUAL_SERVERS');

fprintf($fp, "<pre><br>\n");

foreach ($tables as $table_name)
{
  $query = "select count(*) as count from " . $table_name;
  $ora->sql($query);
  $ora->fetch();

  fprintf($fp, "%-30s record count: %d\n", $table_name, $ora->count);
  // printf("%-30s record count: %d\n", $table_name, $ora->count);
}

fprintf($fp, "</pre>\n");
fprintf($fp, "<h3><b>THE END</b></h3>\n");
fclose($fp);

//
// Email the file to myself
//
$to = "Greg Parkin <gregparkin58@gmail.com>";
$headers  = sprintf("From: Greg Parkin <gparkin@lxomp11m.qintra.com>\r\n");
$headers .= sprintf("Reply-To: Greg Parkin <gregparkin58@gmail.com>\r\n");
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

$subject = sprintf("CCT Daily Check for: %s", $yesterday);

$message  = "<html><body>";

$fp = fopen($check_log, "r") or die($php_errormsg);

while (($buffer = fgets($fp, 4096)) !== false)
{
        $message .= $buffer;
}

fclose($fp);
$message .= "</body></html>";

mail($to, $subject, $message, $headers);
