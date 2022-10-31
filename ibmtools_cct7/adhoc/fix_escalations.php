#!/xxx/apache/php/bin/php -q
<?php
// 
// fix_escalations.php
//
// AUTHOR: Greg Parkin
//

//
// Class autoloader - /xxx/www/cct/classes:/xxx/www/cct/servers:/xxx/www/cct/includes
// See: include_paths= in file /xxx/apache/php/php.ini
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

$ora1 = new dbms();
$ora2 = new dbms();
$lib  = new library();

$query  = "select * from cct6_tickets where ticket_read_esc1_date < to_date('07-01-1970', 'MM-DD-YYYY')";

if ($ora1->sql($query) == false)
{
	printf("%s - %s\n", $query, $ora1->dbErrMsg);
	exit();
}

while ($ora1->fetch())
{
	$read_esc1_date = $lib->substractDays($ora1->cm_start_date, 8);
	$read_esc2_date = $lib->substractDays($ora1->cm_start_date, 7);
	$read_esc3_date = $lib->substractDays($ora1->cm_start_date, 6);
	$resp_esc1_date = $lib->substractDays($ora1->cm_start_date, 5);
	$resp_esc2_date = $lib->substractDays($ora1->cm_start_date, 4);
	$resp_esc3_date = $lib->substractDays($ora1->cm_start_date, 3);

	$update  = "update cct6_tickets set ";
	$update .= sprintf("  ticket_read_esc1_date = to_date('%s', 'MM/DD/YYYY HH24:MI'), ", $read_esc1_date);
	$update .= sprintf("  ticket_read_esc2_date = to_date('%s', 'MM/DD/YYYY HH24:MI'), ", $read_esc2_date);
	$update .= sprintf("  ticket_read_esc3_date = to_date('%s', 'MM/DD/YYYY HH24:MI'), ", $read_esc3_date);
	$update .= sprintf("  ticket_resp_esc1_date = to_date('%s', 'MM/DD/YYYY HH24:MI'), ", $resp_esc1_date);
	$update .= sprintf("  ticket_resp_esc2_date = to_date('%s', 'MM/DD/YYYY HH24:MI'), ", $resp_esc2_date);
	$update .= sprintf("  ticket_resp_esc3_date = to_date('%s', 'MM/DD/YYYY HH24:MI') ",  $resp_esc3_date);
	$update .= sprintf("where cm_ticket_no = '%s'", $ora1->cm_ticket_no);

	if ($ora2->sql($update) == false)
	{
		printf("FAILED: %s\n", $update);
		exit();
	}

	printf("Updating: %s\n", $ora1->cm_ticket_no);
}

$ora2->commit();

?>
