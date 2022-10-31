<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
/**
 * <work_issues.php>
 *
 * @package    CCT
 * @file       work_issues.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       02/15/2015
 * @version    6.1.0
 *
 * Super Tracker
 *
 */
 
//set_include_path(".;C:\PHP\pear\pear;C:\www\pmt4\classes;C:\www\pmt4\includes;C:\www\pmt4\servers;/xxx/www/pmt4/classes:/xxx/www/pmt4/includes:/xxx/www/pmt4/servers");
 
ini_set('xdebug.collect_vars', '5');
ini_set('xdebug.collect_vars', 'on');
ini_set('xdebug.collect_params', '4');
ini_set('xdebug.dump_globals', 'on');
ini_set('xdebug.dump.SERVER', 'REQUEST_URI');
ini_set('xdebug.show_local_vars', 'on');
 
//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
	require_once('autoloader.php');
}

$h = new html();  // classes/html.php
$h->debug_start('work_issues.txt');
//$h->html_dump();

//
// Default action is to show Active Work requests for the user's assigned groups
//
$h->set_loading_file_on("Test DataTables");
$h->set_program("work_issues.php");


$h->html_styles();
?>
<!-- For multiSelect -->
<link href="css/jquery.multiSelect.css" rel="stylesheet" type="text/css" />

<?php
$h->html_top();

$select_group_members = isset($h->parm['select_group_members']) ? $h->parm['select_group_members']  : "";
$select_issue_type    = isset($h->parm['select_issue_type'])    ? $h->parm['select_issue_type']     : "";
$select_issue_color   = isset($h->parm['select_issue_color'])   ? $h->parm['select_issue_color']    : "";
$select_starting_date = isset($h->parm['select_starting_date']) ? $h->parm['select_starting_date']  : "";

$select_view_detail   = isset($h->parm['select_view_detail'])   ? $h->parm['select_view_detail']    : "";
$select_priority      = isset($h->parm['select_priority'])      ? $h->parm['select_priority']       : "";
$select_open_closed   = isset($h->parm['select_open_closed'])   ? $h->parm['select_open_closed']    : "";
$select_ending_date   = isset($h->parm['select_ending_date'])   ? $h->parm['select_ending_date']    : "";

?>
<!-- For multiSelect -->
<script src="js/jquery.min.js" type="text/javascript"></script>
<script src="js/jquery.bgiframe.min.js" type="text/javascript"></script>
<script src="js/jquery.multiSelect.js" type="text/javascript"></script>
<link href="css/jquery.multiSelect.css" rel="stylesheet" type="text/css" />

<script type="text/javascript">
$(document).ready(function() {

	
	// Default options
	$("#control_1, #control_3, #control_4, #control_5").multiSelect();
	
	// With callback
	$("#control_6").multiSelect( null, function(el) {
		$("#callbackResult").show().fadeOut();
	});
	
	// Options displayed in comma-separated list
	$("#control_7").multiSelect({ oneOrMoreSelected: '*' });
	
	// 'Select All' text changed
	$("#control_8").multiSelect({ selectAllText: 'Pick &lsquo;em all!' });
	
	// Show test data
	$("FORM").submit( function() {
		$.post('work_issues.php', $(this).serialize(), function(r) {
			alert(r);
		});
		return false;
	});
				
} );
</script>

<center><font size="+2">Work Issues</font></center>

<table border="0" cellpadding="2" cellspacing="0">
<tr>
	<td align="right">Filter:</td>
    <td align="left">
        <select id="control_8" name="control_8[]" multiple="multiple" size="5" style="margin-left: 3px; width: 20em;">
            <option value=""></option>
            <option value="option_1">Option 1</option>
            <option value="option_2">Option 2</option>
            <option value="option_3">Option 3</option>
            <option value="option_4">Option 4</option>
            <option value="option_5">Option 5</option>
            <option value="option_6">Option 6</option>
            <option value="option_7">Option 7</option>
        </select>    	
    </td>
</tr>
</table>


<?php
$h->html_bot();
?>