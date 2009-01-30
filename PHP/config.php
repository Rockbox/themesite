<?php
# Site setup
define("DOCROOT", dirname(__FILE__));
define("TMPDIR", "/tmp");

# Change this to where your local install is storing the (pre-)themes.txt files
define("SITEURL",  "http://mcuelenaere.alwaysdata.net/themes_rockbox/PHP");
define("THEMEDIR", "/themes");
define("PRETHEMEDIR", THEMEDIR."/pre-themes");
define("DATADIR",  DOCROOT.THEMEDIR);
define("PREDATADIR", DOCROOT.PRETHEMEDIR);
define("SITEDIR",  SITEURL.THEMEDIR);
define("PRESITEDIR", SITEURL.PRETHEMEDIR);

# Theme constraints
define("MAXFILESINZIP", 150);
define("MAXUNZIPPEDSIZE", 5*1024*1024);
define("MAXFILESIZE", 1*1024*1024);
define("MAXTHEMEPATHDEPTH", 4); # Note: this includes a possible file element in the path

# Program locations
define("UNZIP", "/usr/bin/unzip");
define("IMAGEMAGICK", "/usr/bin/convert");
define("CHECKWPS", DOCROOT."/../bin/checkwps");

define("USERS",      DATADIR."/users.txt");
define("PRE_THEMES", DATADIR."/pre-themes.txt");
define("THEMES",     DATADIR."/themes.txt");


# For first time use, make sure that needed files are here.
# REMEMBER to make a USERS file with as contens value pairs of user|md5(password)
# otherwise you will not be able to approve themes via the admin interface
if (!file_exists(PRE_THEMES))
    touch(PRE_THEMES);
if (!file_exists(THEMES))
    touch(THEMES);

# Define models
class model
{
    public $name;
    public $display;
    public $image;
    public $checkwps;

    function __construct($name, $display, $image, $checkwps)
    {
        $this->name = $name;
        $this->display = $display;
        $this->image = $image;
        $this->checkwps = $checkwps;
    }
    
    function lcd_size()
    {
        /* Drop last element from LCD size */
        $lcd_size = explode("x", $this->display);
        array_pop($lcd_size);
        return implode("x", $lcd_size);
    }
}

global $models;
$models = array();
$models['player']        = new model('Archos Player/Studio',  'charcell', 'player-small.png', 'player');
$models['recorder']      = new model('Archos Recorder v1', '112x64x1', 'recorder-small.png', 'recorder');
$models['recorder8mb']   = new model('Archos Recorder 8MB', '112x64x1', 'recorder-small.png', 'recorder');
$models['fmrecorder']    = new model('Archos FM Recorder', '112x64x1', 'recorderv2fm-small.png', 'fmrecorder');
$models['recorderv2']    = new model('Archos Recorder v2', '112x64x1', 'recorderv2fm-small.png', 'recorderv2');
$models['ondiofm']       = new model('Archos Ondio FM',  '112x64x1', 'ondiofm-small.png', 'ondiofm');
$models['ondiosp']       = new model('Archos Ondio SP', '112x64x1', 'ondiosp-small.png', 'ondiosp');
$models['iaudiom5']      = new model('iAudio M5', '160x128x2', 'm5-small.png', 'm5');
$models['iaudiox5']      = new model('iAudio X5', '160x128x16', 'x5-small.png', 'x5');
$models['iaudiom3']      = new model('iAudio M3', '128x96x2', 'm3-small.png', 'm3');
$models['h100']          = new model('iriver H100/115',  '160x128x2', 'h100-small.png', 'h100');
$models['h120']          = new model('iriver H120/140', '160x128x2', 'h100-small.png', 'h120');
$models['h300']          = new model('iriver H320/340', '220x176x16', 'h300-small.png', 'h300');
$models['h10_5gb']       = new model('iriver H10 5GB', '128x128x16', 'h10_5gb-small.png', 'h10_5gb');
$models['h10']           = new model('iriver H10 20GB', '160x128x16', 'h10-small.png', 'h10');
$models['ipod1g2g']      = new model('iPod 1st and 2nd gen',  '160x128x2', 'ipod1g2g-small.png', 'ipod1g2g');
$models['ipod3g']        = new model('iPod 3rd gen', '160x128x2', 'ipod3g-small.png', 'ipod3g');
$models['ipod4gray']     = new model('iPod 4th gen Grayscale', '160x128x2', 'ipod4g-small.png', 'ipod4g');
$models['ipodcolor']     = new model('iPod Color/Photo', '220x176x16', 'ipodcolor-small.png', 'ipodcolor');
$models['ipodvideo']     = new model('iPod Video 30GB', '320x240x16', 'ipodvideo-small.png', 'ipodvideo');
$models['ipodvideo64mb'] = new model('iPod Video 60/80GB',  '320x240x16', 'ipodvideo-small.png', 'ipodvideo');
$models['ipodmini1g']    = new model('iPod Mini 1st gen', '138x110x2', 'ipodmini-small.png', 'ipodmini');
$models['ipodmini2g']    = new model('iPod Mini 2nd gen', '138x110x2', 'ipodmini-small.png', 'ipodmini2g');
$models['ipodnano']      = new model('iPod Nano 1st gen', '176x132x16', 'ipodnano-small.png', 'ipodnano');
$models['gigabeatf']     = new model('Toshiba Gigabeat F/X', '240x320x16', 'gigabeatf-small.png', 'gigabeatf');
$models['sansae200']     = new model('SanDisk Sansa e200',  '176x220x16', 'e200-small.png', 'e200');
$models['sansac200']     = new model('SanDisk Sansa c200', '132x80x16', 'c200-small.png', 'c200');
$models['mrobe100']      = new model('Olympus M-Robe 100', '160x128x1', 'mrobe100-small.png', 'mrobe100');
?>