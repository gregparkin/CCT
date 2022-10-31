<?php
/**
 * @package    CCT
 * @file       footer.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       $Date:  $ GMT
 * @version    $Revision:  $
 *
 * $Log:  $
 *
 * $Source:  $
 */

//
// Called once when a user signs into CCT. 
//

set_include_path("/opt/ibmtools/www/cct7/classes");

//
// Class autoloader - Removes the need to add include and require statements
//
if (!function_exists('__autoload'))
{
    require_once('classes/autoloader.php');
}

//
// Required to start once in order to retrieve user session information
//
if (session_id() == '')
    session_start();

if (isset($_SESSION['user_cuid']) && $_SESSION['user_cuid'] == 'gparkin')
{
	ini_set('xdebug.collect_vars',    '5');
	ini_set('xdebug.collect_vars',    'on');
	ini_set('xdebug.collect_params',  '4');
	ini_set('xdebug.dump_globals',    'on');
	ini_set('xdebug.dump.SERVER',     'REQUEST_URI');
	ini_set('xdebug.show_local_vars', 'on');

	//$path = '/usr/lib/pear';
	//set_include_path(get_include_path() . PATH_SEPARATOR . $path);
}
else
{
	ini_set('display_errors', 'Off');
}

date_default_timezone_set('America/Denver');

?>

<html>
<head>
	<?php
	switch ( $_SERVER['SERVER_NAME'] )
	{
		case 'cct.corp.intranet':
			?><base href="https://cct.corp.intranet/"><?php
			break;
		case 'lxomp47x.corp.intranet':
			?><base href="https://lxomp47xcct.corp.intranet/cct7/"><?php
			break;
		case 'cct.test.intranet':
			?><base href="https://cct.test.intranet/"><?php
			break;
		case 'vlodts022.test.intranet':
			?><base href="https://vlodts022.test.intranet/cct7/"><?php
			break;
		default:
			?><base href="http://cct7.localhost/"><?php
			break;
	}
	?>
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE10">
</head>
<body style="margin-botton: 0; margin-top: 0; margin-right: 0; margin-left: 0; background-color: white">
<center>
    <table border="0" cellpadding="8" cellspacing="4" style="border-collapse: collapse; background-color: white" bordercolor="#111111" width="100%">
        <tr>
            <td align="left" valign="top" width="25%">
                <?php
                if (isset($_SESSION['user_name']))
                {
                    ?>
                    <font color="#4485c4"><b>Welcome:</b> <?php echo $_SESSION['user_name'] ?></font>
                    <br>
                    <!--
                    <font color="#800000"><b>For help, contact: </b>
                        <a href="javascript:NewWindow('http://net.qintra.com/NET/Notification.jsp?gid=<?php echo $_SESSION['NET_PIN']; ?>')">
                            <font color="#800000"><?php echo $_SESSION['NET_GROUP_NAME']; ?></font></a></font><br> -->
                    <img align="absmiddle" src="images/ibm.png">
                    <font color="#800000"><b>CCT: </b><?php echo $_SESSION['BUILD_VERSION']; ?> - <?php echo $_SESSION['BUILD_DATE']; ?></font>&nbsp;
                    <?php
                    if (isset($_SESSION['is_debug_on']) && $_SESSION['is_debug_on'] == 'Y')
                    {
                        printf("<img align=\"absmiddle\" src=\"images/ladybug.png\">\n");
                    }
                    ?>
                    <br><font size="2" color="#8b008b"><b>Local TZ:</b> <?php printf("%s", $_SESSION['local_timezone']); ?></font>
                    <?php
                }
                else
                {
                    ?><img src="images/Preloader_105.gif"><?php // Display animated loading circle called bigSnake.gif
                }
                ?>

            </td>
            <td valign="top" align="center" width="50%">
                <font size="2" color="#800000" face="Arial Narrow">
                    Internal Use Only<br>
                    Disclose and Distribute only to Authorized CMP Employees<br>
                    Disclosure outside of CMP is prohibited without authorization</font>
            </td>
            <td valign="top" align="center" width="25%">
                <font size="2" color="#800000"><b><?php echo $_SESSION['CCT_APPLICATION']; ?></b></font><br>
                <a href="<?php echo $_SESSION['WWW']; ?>"><font size="1" color="#800000"><b><?php echo $_SESSION['WWW']; ?></b></font></a><br>
                <?php page_hits(); ?>
            </td>
        </tr>
    </table>
</center>
</body>
</html>

<?php
/** @fn page_hits()
 *  @brief Update the page hit counter and display the result in the footer
 *  @return void
 */
function page_hits()
{
    $lib = new library();

    $page_hit_count = $lib->globalCounter();

    printf("<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse\" bordercolor=\"#111111\">\n");
    printf("<tr>\n");
    printf("<td><b><font size=\"2\" color=\"#800000\">Page Hits:&nbsp;</font></b></td>\n");

    $str = sprintf("%1$08d", $page_hit_count);
    //$len = strlen($str);

    $str2 = str_split($str);
    reset($str2);

    while (list(, $value) = each($str2))
    {
        echo "<td><img border=0 src=images/$value.gif width=9 height=13></td>";
    }

    printf("</tr>\n");
    printf("</table>\n");
}
