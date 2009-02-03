<?
require_once("config.php");
require_once("tools.php");

function error($code, $message)
{
    echo "[error]\n".
         "code=$code\n".
         "description=$message\n\n";
}

header('Content-type: text/plain');

if(!isset($_GET['res']))
    error(1, 'Invalid URL');
else if(!check_resolution($_GET['res']))
    error(2, 'Invalid resolution');
else
{
    error(0, 'Rocking da boxes');
    foreach(filter($_GET['res']) as $theme)
    {
        /* $id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date */
        echo "[".$theme[2]."]\n".
             "name=".$theme[1]."\n".
             "size=".(@filesize(DATADIR."/".$theme[7]."/".$theme[2].".zip")/1024)."\n".
             "descriptionfile=\n";

        if($theme[3] == 1)
            echo "image=".THEMEDIR."/".$theme[7]."/".$theme[2].".png\n";
        if($theme[4] == 1)
            echo "image2=".THEMEDIR."/".$theme[7]."/".$theme[2]."_b.png\n";

        echo "archive=".THEMEDIR."/".$theme[7]."/".$theme[2].".zip\n".
             "author=".$theme[5]."\n".
             "version="."\n".
             'about="'.$theme[9]."\"\n\n";
    }
}
?>