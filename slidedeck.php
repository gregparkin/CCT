<?php
/**
 * slidedeck.php
 *
 * @package   PhpStorm
 * @file      slidedeck.php
 * @author    gparkin
 * @date      11/18/16
 * @version   7.0
 *
 * @brief     http://www.w3schools.com/w3css/w3css_slideshow.asp
 */

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

$lib = new library();
date_default_timezone_set('America/Denver');

$lib->globalCounter();
?>

<html>
<title>W3.CSS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="w3.css">
<body>
<form name="f1">

<div class="w3-content w3-display-container">
    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide1.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide2.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide3.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide4.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide5.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide6.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide7.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide8.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide9.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide10.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide11.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide12.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide13.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide14.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide15.png" style="width:100%">
    </div>

    <div class="w3-display-container mySlides">
        <img src="help/slide_deck/slide16.png" style="width:100%">
    </div>

    <a class="w3-btn-floating w3-display-left" onclick="plusDivs(-1)">&#10094;</a>
    <a class="w3-btn-floating w3-display-right" onclick="plusDivs(1)">&#10095;</a>

</div>
    <br><center><input type="button" value="Start Over" onclick="location.reload(true);"></center>

<script>
    var slideIndex = 1;
    showDivs(slideIndex);

    function plusDivs(n) {
        showDivs(slideIndex += n);
    }

    function showDivs(n) {
        var i;
        var x = document.getElementsByClassName("mySlides");
        if (n > x.length) {slideIndex = 1}
        if (n < 1) {slideIndex = x.length}
        for (i = 0; i < x.length; i++) {
            x[i].style.display = "none";
        }
        x[slideIndex-1].style.display = "block";
    }
</script>
</form>
</body>
</html>