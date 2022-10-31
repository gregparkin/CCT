<?php
/**
 * @package    CCT
 * @file       index.php
 * @author     Greg Parkin <gregparkin58@gmail.com>
 * @date       $Date:  $ GMT
 * @version    $Revision:  $
 *
 * $Log:  $
 *
 * $Source:  $
 *
 * @brief     This is the main control program for CCT. It contains all the panels (layouts) used
 *            to display content for this application.
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

$lib = new library();
date_default_timezone_set('America/Denver');
$lib->globalCounter();

//
// header() is a PHP function for modifying the HTML header information
// going to the client's browser. Here we tell their browser not to cache
// the page coming in so their browser will always rebuild the page from
// scratch instead of retrieving a copy of one from its cache.
//
// header("Expires: " . gmstrftime("%a, %d %b %Y %H:%M:%S GMT"));
//
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

//
// Disable buffering - This is what makes the loading screen work properly.
//
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('output_buffering', 'Off');
@ini_set('implicit_flush', 1);

ob_implicit_flush(1); // Flush buffers

for ($i = 0, $level = ob_get_level(); $i < $level; $i++)
{
    ob_end_flush();
}

//
// If this is the first time the user has logged into CCT then session variable $_SESSION['CCT_INIT']
// will not exist. In this case we want to call jQuery AJAX code below to retrieve the local user's
// workstation timezone information. The session variable in the AJAX routine will set this variable
// to READY meaning it has the timezone information stored in session variables. After the AJAX routine
// finishes running it will reload the index.php page where now the $_SESSION['CCT_INIT'] variable
// will now exist.
//
if (!isset($_SESSION['CCT_INIT']))
{
    //
    // Call cct_init.php
    //
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
				?><base href="https://lxomp47x.corp.intranet/cct7/"><?php
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
        <title>Change Coordination Tool</title>
        <link rel="icon" type="image/png" href="images/handshake1.ico">
        <script src="js/jquery-2.1.4.js"></script>
        <script type="text/javascript" src="js/jstz.js"></script><!-- Determines user's timezone of the PC their using -->
    </head>
    <body>
    <form name="f1" method="get" action="index.php">
        <script type="text/javascript">
            $(document).ready(
                function ()
                {
                    var tz = jstz.determine();
                    $.ajax(
                        {
                            type:    "GET",
                            url:     "cct_init.php",
                            data:    'timezone=' + tz.name(),
                            success: function ()
                            {
                                f1.action = "index.php";
                                f1.submit();
                            },
                            error: function(jqXHR, exception, errorThrown)
                            {
                                if (jqXHR.status === 0) {
                                    alert('Not connect.\n Verfiy Network.');
                                } else if (jqXHR.status == 404) {
                                    alert('Requested page not found. [404]');
                                } else if (jqXHR.status == 500) {
                                    alert('Internal Server Error [500]');
                                } else if (exception === 'parsererror') {
                                    alert('Requested JSON parse failed.' + ' Error code: ' + errorThrown + ' ResponseText: ' + jqXHR.responseText);
                                } else if (exception === 'timeout') {
                                    alert('Time out error.');
                                } else if (exception === 'abort') {
                                    alert('Ajax request aborted.');
                                } else {
                                    alert('Uncaught Error.\n' + jqXHR.responseText);
                                }
                            }
                        }
                    );
                }
            );
        </script>
    </form>
    </body>
    </html>
    <?php
    exit(0);
}

//
// Okay we should now have everything we need to setup the main application page.
//
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
			?><base href="https://lxomp47x.corp.intranet/cct7/"><?php
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
    <title>CCT 7.0</title>
    <link rel="icon" type="image/png" href="images/handshake1.ico">

    <link rel="stylesheet" type="text/css" media="screen" href="css/jquery-ui.css">
    <link rel="stylesheet" type="text/css"                href="css/font-awesome.css">

    <style>
        .home            { background-image: url('images/home.png');       background-size: 16px 16px; }
        .new             { background-image: url('images/new.gif');        background-size: 16px 16px; }
        .open            { background-image: url('images/door4.gif');      background-size: 16px 16px; }
        .schedule        { background-image: url('images/schedule6.gif');  background-size: 16px 16px; }
        .lists           { background-image: url('images/list.gif')  ;     background-size: 16px 16px; }
        .approve         { background-image: url('images/okay4.gif') ;     background-size: 16px 16px; }
        .reports         { background-image: url('images/reports1.gif') ;  background-size: 16px 16px; }
        .tools           { background-image: url('images/toolbox.png');    background-size: 16px 16px; }
        .debug           { background-image: url('images/ladybug.png');    background-size: 16px 16px; }
        .logs            { background-image: url('images/log1.gif') ;      background-size: 16px 16px; }
        .search          { background-image: url('images/find4.gif');      background-size: 16px 16px; }
        .help            { background-image: url('images/help5.gif');      background-size: 16px 16px; }
        .left_panel      { background-image: url('images/help14.png');     background-size: 16px 16px; }
        .right_panel     { background-image: url('images/help14.png');     background-size: 16px 16px; }
        .email           { background-image: url('images/email2.gif');     background-size: 16px 16px; }
        .stats           { background-image: url('images/reports1.gif');   background-size: 16px 16px; }
        .slides          { background-image: url('images/slidedeck.png');  background-size: 16px 16px; }
        .custom          { background-image: url('images/customize1.gif'); background-size: 16px 16px; }
        .servers         { background-image: url('images/computer5.gif');  background-size: 16px 16px; }
        .subscriber      { background-image: url('images/subscribe.gif');  background-size: 16px 16px; }
        .group_tickets   { background-image: url('images/group2.gif');     background-size: 16px 16px; }
        .all_tickets     { background-image: url('images/tickets1.gif');   background-size: 16px 16px; }
        .group_schedules { background-image: url('images/schedule2.png');  background-size: 16px 16px; }
        .all_schedules   { background-image: url('images/check2.gif');      background-size: 16px 16px; }
        .attachment      { background-image: url('images/attachment1.gif'); background-size: 16px 16px; }
        .no_response     { background-image: url('images/check_red2.png'); background-size: 16px 16px; }
        .server_contacts { background-image: url('images/assign_group1.png'); background-size: 16px 16px; }
        .work_schedule   { background-image: url('images/schedule2.gif'); background-size: 16px 16px; }
        .index           { background-image: url('images/info1.png');      background-size: 16px 16px; }
        .toolbox         { background-image: url('images/toolbox.png');      background-size: 16px 16px; }
        .switch_user     { background-image: url('images/anger_bird.png');      background-size: 16px 16px; }
        .debug_files     { background-image: url('images/bug2.gif');      background-size: 16px 16px; }
        .log_files       { background-image: url('images/log1.gif');      background-size: 16px 16px; }
        .dump_environment { background-image: url('images/environment1.png');      background-size: 16px 16px; }
        .php_info        { background-image: url('images/php1.png');      background-size: 16px 16px; }
        .netpin_overrides { background-image: url('images/detour2.png');      background-size: 16px 16px; }
    </style>

    <script type="text/javascript" src="js/jquery-2.1.4.js"></script>
    <script type="text/javascript" src="js/jquery-ui.js"></script>
    <!-- w2ui popup -->
    <!-- link rel="stylesheet" type="text/css" href="css/w2ui-1.4.3.css" / -->
    <!-- script type="text/javascript" src="js/w2ui-1.4.3.js"></script -->

    <link rel="stylesheet" type="text/css" href="css/w2ui-1.5.rc1.css" />
    <script type="text/javascript" src="js/w2ui-1.5.rc1.js"></script>
</head>
<body style="margin-botton: 0; margin-top: 0; margin-right: 0; margin-left: 0;">


    <div id="layout" style="width: 100%; height: 99%;"></div>

    <script type="text/javascript">

        //
        // Handy prototype function used in formatting dates.
        //
        Date.prototype.formatDate = function (format) {
            var date    = this,
                day     = date.getDate(),
                month   = date.getMonth() + 1,
                year    = date.getFullYear(),
                hours   = date.getHours(),
                minutes = date.getMinutes(),
                seconds = date.getSeconds();

            if (!format)
            {
                format = "MM/dd/yyyy";
            }

            format = format.replace("MM", month.toString().replace(/^(\d)$/, '0$1'));

            if (format.indexOf("yyyy") > -1)
            {
                format = format.replace("yyyy", year.toString());
            }
            else if (format.indexOf("yy") > -1)
            {
                format = format.replace("yy", year.toString().substr(2, 2));
            }

            format = format.replace("dd", day.toString().replace(/^(\d)$/, '0$1'));

            if (format.indexOf("t") > -1)
            {
                if (hours > 11)
                {
                    format = format.replace("t", "pm");
                }
                else
                {
                    format = format.replace("t", "am");
                }
            }

            if (format.indexOf("HH") > -1)
            {
                format = format.replace("HH", hours.toString().replace(/^(\d)$/, '0$1'));
            }

            if (format.indexOf("hh") > -1)
            {
                if (hours > 12)
                {
                    hours -= 12;
                }

                if (hours === 0)
                {
                    hours = 12;
                }

                format = format.replace("hh", hours.toString().replace(/^(\d)$/, '0$1'));
            }

            if (format.indexOf("mm") > -1)
            {
                format = format.replace("mm", minutes.toString().replace(/^(\d)$/, '0$1'));
            }

            if (format.indexOf("ss") > -1)
            {
                format = format.replace("ss", seconds.toString().replace(/^(\d)$/, '0$1'));
            }

            return format;
        };

        var help_file = 'help_home_page.php';

        var search_buffer = '';

        function update_search_buffer(el)
        {
            search_buffer = el.value;
        }

        function closePanel(panel)
        {
            w2ui['layout'].toggle(panel, window.instant);
        }

        $(function ()
        {
            var pstyle  = 'border: 1px solid #dfdfdf; border-radius: 3px; padding: 5px; ';

            $('#layout').w2layout({
                name: 'layout',
                panels: [
                    {
                        //
                        // TOP PANEL - Used for TOOLBAR
                        //
                        type:      'top',
                        size:      33,
                        resizable: true,
                        hidden:    false,
                        style:     pstyle,
                        //
                        // TOOLBAR
                        //
                        toolbar: {
                            items: [
                                {
                                    type:    'button',
                                    id:      'button_home',
                                    caption: 'Home',
                                    img:     'home',
                                    onClick: function(event)
                                    {
                                        help_file = 'help_home_page.php';
                                        w2ui['layout'].load('main', 'home_page.php', 'flip-top', function () {
                                            console.log('content loaded');
                                        });
                                    }
                                },
                                { type: 'break' },
                                {
                                    type:    'button',
                                    id:      'button_new',
                                    caption: 'New Ticket',
                                    img:     'new',
                                    onClick: function(event)
                                    {
                                        help_file = 'help_toolbar_new.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="toolbar_new.php?do=step1" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                    }
                                },
                                { type: 'break' },
                                { type:      'button',
                                    id:      'open_group_tckets',
                                    caption: 'Open Tickets',
                                    img:     'open',
                                    onClick: function(event)
                                    {
                                        help_file = 'help_toolbar_open_group.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="toolbar_open.php?what_tickets=group" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                    }
                                },
                                { type: 'break' },
                                { type:      'button',
                                    id:      'all_schedules',
                                    caption: 'Ready Work',
                                    img:     'schedule'
                                    ,
                                    onClick: function(event)
                                    {
                                        help_file = 'help_toolbar_schedule_group.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="toolbar_schedule.php?what_tickets=group" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                    }
                                },
                                { type: 'break' },
                                { type:      'menu',
                                    id:      'open_list',
                                    caption: 'Lists',
                                    hint:    'Subscriber and server lists.',
                                    img:     'lists',
                                    items: [
                                        {
                                            id:   'server_lists',
                                            text: 'Server Lists',
                                            img:  'servers'
                                        },
                                        {
                                            id:   'subscriber_lists',
                                            text: 'Subscriber Lists',
                                            img:  'subscriber'
                                        },
                                        {
                                            id:   'netpin_overrides',
                                            text: 'Netpin Overrides',
                                            img:  'netpin_overrides'
                                        }
                                    ]
                                },
                                { type: 'break' },
                                { type:      'menu',
                                    id:      'reports',
                                    caption: 'Reports',
                                    hint:    'Useful reports.',
                                    img:     'reports',
                                    items: [
                                        {
                                            id:   'remedy_attachment',
                                            text: 'Remedy Attachment',
                                            img:  'attachment'
                                        },
                                        {
                                            id:   'no_response',
                                            text: 'No Response',
                                            img:  'no_response'
                                        },
                                        {
                                            id:   'server_contacts',
                                            text: 'Server Contacts',
                                            img:  'server_contacts'
                                        },
                                        {
                                            id:   'work_schedule',
                                            text: 'Work Schedule',
                                            img:  'work_schedule'
                                        }
                                    ]
                                },
                                { type: 'break' },
                                {
                                    type:    'button',
                                    id:      'button_approve',
                                    caption: 'Approve',
                                    img:     'approve',
                                    onClick: function(event)
                                    {
                                        help_file = 'help_toolbar_open_approve.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="toolbar_open.php?what_tickets=approve" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                    }
                                },
                                { type: 'break' },
                                {
                                    type:    'html',
                                    id:      'input_search',
                                    html:    '<div style="padding: 3px 10px;">' +
                                    '   <input id="input_search" size="15" ' +
                                    '     name="input_search" onkeyup="update_search_buffer(this)"' +
                                    '     style="padding: 3px; border-radius: 2px; border: 1px solid silver">' +
                                    '</div>'
                                },
                                {
                                    type:    'button',
                                    id:      'button_search',
                                    img:     'search',
                                    onClick: function(event)
                                    {
                                        help_file = 'help_toolbar_open_search.php';
                                        var el = document.getElementById('input_search');
                                        if (el.value == '')
                                        {
                                            alert('Please type in a ticket, hostname or cuid');
                                            return;
                                        }
                                        var url = cctSearch(el.value);
                                        w2ui['layout'].content('main',
                                            '<iframe src="' + url + '" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        el.value = '';
                                    }
                                },
                                { type: 'break' },
                                { type:      'button',
                                    id:      'open_all_tickets',
                                    caption: 'All Tickets',
                                    img:     'all_tickets',
                                    onClick: function(event)
                                    {
                                        help_file = 'help_toolbar_open_all.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="toolbar_open.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                    }
                                },
                                { type: 'break' },
                                { type:      'button',
                                    id:      'all_group_schedules',
                                    caption: 'All Ready',
                                    img:     'group_schedules',
                                    onClick: function(event)
                                    {
                                        help_file = 'help_toolbar_schedule_all.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="toolbar_schedule.php?what_tickets=all" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                    }
                                },
<?php
								if ($_SESSION['real_cuid'] === 'gparkin')
								{
?>
                                { type: 'break' },
                                { type:      'menu',
                                    id:      'tools',
                                    caption: 'Tools',
                                    hint:    'CCT Support Tools',
                                    img:     'toolbox',
                                    items: [
                                        {
                                            id:   'find_cuid',
                                            text: 'Find CUID',
                                            img:  'search'
                                        },
                                        {
                                            id:   'switch_user',
                                            text: 'Switch User',
                                            img:  'switch_user'
                                        },
                                        {
                                            id:   'debug_files',
                                            text: 'Debug Files',
                                            img:  'debug_files'
                                        },
                                        {
                                            id:   'log_files',
                                            text: 'Log Files',
                                            img:  'log_files'
                                        },
                                        {
                                            id:   'dump_environment',
                                            text: 'Dump Environment',
                                            img:  'dump_environment'
                                        },
                                        {
                                            id:   'php_info',
                                            text: 'PHP Info',
                                            img:  'php_info'
                                        }
                                    ]
                                },
                                { type: 'break' },

<?php
								}
?>
                                { type: 'spacer' },
                                // {
                                //    type:    'button',
                                //    id:      'button_email',
                                //    caption: 'Email',
                                //    img:     'email',
                                //    onClick: function(event)
                                //    {
                                //        help_file = 'help_toolbar_email.php';
                                //        w2ui['layout'].toggle('right', window.instant);
                                //        w2ui['layout'].load('right', "view_last_email.php", 'flip-top', function () {
                                //            console.log('content loaded');
                                //        });
                                //    }
                                // },
                                { type: 'break' },
                                { type:      'menu',
                                    id:      'help_index',
                                    caption: 'Index',
                                    hint:    'List of Help Topics',
                                    img:     'index',
                                    // All Ready       New Ticket            Search
                                    // All Tickets     No Response           Server Lists
                                    // Approve         Open Tickets          Slides
                                    // Email           Ready Work            Subscriber Lists
                                    // Help            Remedy Attachment     Welcome
                                    items: [
                                        {
                                            id:   'help_new_ticket',
                                            text: 'New Ticket',
                                            img:  'new'
                                        },
                                        {
                                            id:   'help_open_tickets',
                                            text: 'Open Tickets',
                                            img:  'open'
                                        },
                                        {
                                            id:   'help_ready_work',
                                            text: 'Ready Work',
                                            img:  'schedule'
                                        },
                                        {
                                            id:   'help_server_lists',
                                            text: 'Server Lists',
                                            img:  'servers'
                                        },
                                        {
                                            id:   'help_subscriber_lists',
                                            text: 'Subscriber Lists',
                                            img:  'subscriber'
                                        },
                                        {
                                            id:   'help_toolbar_override_netpins',
                                            text: 'Netpin Overrides',
                                            img:  'netpin_overrides'
                                        },
                                        {
                                            id:   'help_remedy_attachment',
                                            text: 'Remedy Attachment',
                                            img:  'attachment'
                                        },
                                        {
                                            id:   'help_no_response',
                                            text: 'No Response',
                                            img:  'no_response'
                                        },
                                        {
                                            id:   'help_work_schedule',
                                            text: 'Work Schedule',
                                            img:  'work_schedule'
                                        },
                                        {
                                            id:   'help_server_contacts',
                                            text: 'Server Contacts',
                                            img:  'server_contacts'
                                        },
                                        {
                                            id:   'help_approve',
                                            text: 'Approve',
                                            img:  'approve'
                                        },
                                        {
                                            id:   'help_search',
                                            text: 'Search',
                                            img:  'search'
                                        },
                                        {
                                            id:   'help_open_all_tickets',
                                            text: 'All Tickets',
                                            img:  'all_tickets'
                                        },
                                        {
                                            id:   'help_all_ready_work',
                                            text: 'All Ready',
                                            img:  'group_schedules'
                                        },
                                        //{
                                        //    id:   'help_email',
                                        //    text: 'Email',
                                        //    img:  'email'
                                        //},
                                        {
                                            id:   'help_welcome',
                                            text: 'Welcome',
                                            img:  'home'
                                        },
                                        {
                                            id:   'help_cct7_overview',
                                            text: 'CCT7 Overview',
                                            img:  'home'
                                        },
                                        {
                                            id:   'help_cct7_approve',
                                            text: 'CCT7 Approve',
                                            img:  'home'
                                        },
                                        {
                                            id:   'help_cct7_new_work_request',
                                            text: 'CCT7 New Work Request',
                                            img:  'home'
                                        }
                                    ]
                                },
                                {
                                    type:    'button',
                                    id:      'button_slides',
                                    caption: 'Slides',
                                    img:     'slides',
                                    onClick: function(event)
                                    {
                                        help_file = 'help_toolbar_slides.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="slidedeck.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                    }
                                },
                                {
                                    type:    'button',
                                    id:      'button_help',
                                    caption: 'Help',
                                    img:     'help',
                                    onClick: function(event)
                                    {
                                        w2ui['layout'].toggle('preview', window.instant);
                                        w2ui['layout'].load('preview', help_file, 'flip-top', function () {
                                            console.log('content loaded');
                                        });
                                    }
                                }
                            ],
                            onClick: function(target, info)
                            {
                                //  if (info.item.id == 'item2' && info.subItem && info.subItem.id == 'Item 1') {

                                switch (target)
                                {
                                    case "tools_menu:left_panel":
                                        help_file = 'help_tools_left.php';
                                        w2ui['layout'].toggle('left', window.instant);
                                        break;

                                    case "tools_menu:right_panel":
                                        help_file = 'help_tools_right.php';
                                        w2ui['layout'].toggle('right', window.instant);
                                        break;
                                    //
                                    // Lists: Server Lists
                                    //
                                    case "open_list:server_lists":
                                        help_file = 'help_toolbar_server_lists.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="toolbar_server_lists.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    //
                                    // Lists: Subscriber Lists
                                    //
                                    case "open_list:subscriber_lists":
                                        help_file = 'help_toolbar_subscriber_lists.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="toolbar_subscriber_lists.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    //
                                    // Lists: Netpin Overrides
                                    //
                                    case "open_list:netpin_overrides":
                                        help_file = 'help_toolbar_override_netpins.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="toolbar_override_netpins.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    //
                                    // Reports: Remedy Attachment
                                    //
                                    case "reports:remedy_attachment":
                                        help_file = 'help_toolbar_remedy_attachment.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="toolbar_remedy_attachment.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    //
                                    // Reports: No Response
                                    //
                                    case "reports:no_response":
                                        help_file = 'help_toolbar_no_response.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="toolbar_no_response.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    //
                                    // Reports: Server Contacts
                                    //
                                    case "reports:server_contacts":
                                        help_file = 'help_server_contacts.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="server_contacts.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    //
                                    // Reports: Work Schedule
                                    //
                                    case "reports:work_schedule":
                                        help_file = 'help_work_schedule.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="work_schedule.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    //
                                    // Tools: Find CUID
                                    //
                                    case "tools:find_cuid":
                                        help_file = 'help_find_cuid.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="find_cuid.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    //
                                    // Tools: Switch User
                                    //
                                    case "tools:switch_user":
                                        help_file = 'help_switch_user.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="switch_user.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    //
                                    // Tools: Debug Files
                                    //
                                    case "tools:debug_files":
                                        help_file = 'help_debug_files.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="debug_files.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    //
                                    // Tools: Log Files
                                    //
                                    case "tools:log_files":
                                        help_file = 'help_log_files.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="log_files.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    //
                                    // Tools: Dump Environment
                                    //
                                    case "tools:dump_environment":
                                        help_file = 'help_dump_environment.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="dump_environment.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    //
                                    // Tools: php_info
                                    //
                                    case "tools:php_info":
                                        help_file = 'help_php_info.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="phpinfo.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_new_ticket":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_new.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_open_tickets":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_open_group.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_ready_work":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_schedule_group.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_server_lists":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_server_lists.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_subscriber_lists":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_subscriber_lists.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_toolbar_override_netpins":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_override_netpins.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_remedy_attachment":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_remedy_attachment.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_no_response":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_no_response.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_server_contacts":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_server_contacts.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_approve":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_open_approve.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_search":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_open_search.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_open_all_tickets":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_open_all.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_all_ready_work":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_schedule_all.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_email":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_toolbar_email.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_welcome":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="help_home_page.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_cct7_overview":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="cct7_overview.html" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_cct7_approve":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="cct7_approve.html" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;

                                    case "help_index:help_cct7_new_work_request":
                                        help_file = 'xxx.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="cct7_new_work_request.html" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;
                                    //
                                    // Search
                                    //
                                    case "button_search":

                                        break;
                                    case "button_email":
                                        /**
                                        w2ui['layout'].content('main',
                                            '<iframe src="view_last_email.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                         */

                                        break;
                                        /**
                                    case "button_stats":
                                        help_file = 'help_toolbar_stats.php';
                                        w2ui['layout'].content('main',
                                            '<iframe src="view_cct_usage_info.php" ' +
                                            'style="border: none; min-width: 100%; min-height: 100%;">' +
                                            '</iframe>');
                                        break;
                                         */
                                    case "button_slides":

                                        break;
                                    //
                                    // Help
                                    //
                                    case "button_help":
                                        // Do not include a help_file here!
                                        // w2ui['layout'].toggle('left', window.instant);  // Help Index

                                        break;
                                }
                                // alert('item '+ target + ' is clicked.');
                            }
                        }
                    },
                    {
                        //
                        // LEFT PANEL - - Available hidden left panel.
                        //
                        type:      'left',
                        size:      200,
                        resizable: true,
                        hidden:    true,
                        style:     pstyle,
                        content:   'left',
                        title:     '<table border=0 width="100%"><tr><td align="left">Help Index</td><td align="right">' +
                                   '<img src="images/close_button_16x16.png" onclick="closePanel(\'left\')">' +
                                   '</td></tr></table>'
                    },
                    {
                        //
                        // MAIN PANEL - Where main application content exists.
                        //
                        type:      'main',
                        hidden:    false,
                        style:     pstyle,
                        content:   '&nbsp;'
                    },
                    {
                        //
                        // PREVIEW PANEL - 50% of MAIN PANEL when open
                        // outage4.png
                        // outage6.png
                        //
                        type:      'preview',
                        size:      '85%',
                        resizable: true,
                        hidden:    true,
                        style:     pstyle,
                        //content:   'Help panel.',
                        title:     '<table border=0 width="100%"><tr><td align="left">Help</td><td align="right">' +
                                   '<img src="images/close_button_16x16.png" onclick="closePanel(\'preview\')">' +
                                   '</td></tr></table>'
                    },
                    {
                        //
                        // RIGHT PANEL - Available hidden right panel.
                        //
                        type:      'right',
                        size:      '45%',
                        resizable: true,
                        hidden:    true,
                        style:     pstyle,
                        content:   'right',
                        title:     '<table border=0 width="100%"><tr><td align="right">' +
                                   '<img src="images/close_button_16x16.png" onclick="closePanel(\'right\')">' +
                                   '</td></tr></table>'
                    },
                    {
                        //
                        // BOTTOM PANEL - Contains the Footer information.
                        //
                        type:      'bottom',
                        size:      70,
                        resizable: true,
                        hidden:    false,
                        style:     'border: 1px solid #dfdfdf; padding: 0px;',
                        content: ' bottom'
                    }
                ]
            });

            w2ui['layout'].load('bottom', 'footer.php', 'flip-top', function () {
                console.log('bottom (footer) content loaded loaded');
            });

            w2ui['layout'].load('main', 'home_page.php', 'flip-top', function () {
                console.log('content loaded');
            });

            /** Uncomment this block of code if you want the help pane to display when CCT is first loaded

            w2ui['layout'].toggle('preview', window.instant);
            w2ui['layout'].load('preview', help_file, 'flip-top', function () {
                console.log('content loaded');
            });
            */
        });

        function cctSearch(search_buffer)
        {
            var url = 'ajax_toolbar_search.php?search_buffer=' + search_buffer;

            //
            // Make a jQuery AJAX call to ajax_get_status_info.php to return the data we want to update in the grids.
            //
            $.ajax({
                type: 'GET',
                url:   url,
                async: false,
                beforeSend: function (xhr) {
                    if (xhr && xhr.overrideMimeType) {
                        xhr.overrideMimeType('application/json;charset=utf-8');
                    }
                },
                dataType: 'json',
                success: function (data)
                {
                    //
                    // Check ajax return status
                    //
                    // $json['ajax_status']               = 'SUCCESS';
                    // $json['ajax_message']              = '';
                    //
                    if (data['ajax_status'] === 'FAILED')
                    {
                        alert(data['ajax_message']);
                        url = 'home_page.php';
                        return;
                    }

                    url = data['ajax_message'];
                },
                error: function(jqXHR, exception, errorThrown)
                {
                    if (jqXHR.status === 0) {
                        alert('Not connect.\n Verfiy Network.');
                    } else if (jqXHR.status == 404) {
                        alert('Requested page not found. [404]');
                    } else if (jqXHR.status == 500) {
                        alert('Internal Server Error [500]');
                    } else if (exception === 'parsererror') {
                        alert('Requested JSON parse failed.' + ' Error code: ' + errorThrown + ' ResponseText: ' + jqXHR.responseText);
                    } else if (exception === 'timeout') {
                        alert('Time out error.');
                    } else if (exception === 'abort') {
                        alert('Ajax request aborted.');
                    } else {
                        alert('Uncaught Error.\n' + jqXHR.responseText);
                    }
                }
            });

            return url;
        }
    </script>

</body>
</html>

