<?php
require_once('config.php');
require_once('tools.php');

include_once('top.php');

if(isset($_GET['model']) && isset($models[$_GET['model']]))
{
    $id = $_GET['model'];
    $model = $models[$id];
}
else
    $model = NULL;

if (!$model)
{
    # HOME PAGE
    include_once('intro.php');

    echo "<p><table class=\"rockbox\" cellpadding=\"0\">\n";
    foreach ($models as $id => $model)
    {
       echo "<div class=\"playerbox\"><a href=\"".SITEURL;
       echo "/index.php?model=$id\"><img border=\"0\" src=\"";
       echo "http://www.rockbox.org/playerpics/$model->image\" alt=\"";
       echo "$model->name\" /><p>$model->name</a></div>\n";
    }
    echo "</table></p>\n";
}
else
{
    echo "<h1>Rockbox Themes - $model->name ($model->display)</h1>\n";
    # LIST OF THEMES FOR A SINGLE TARGET

    echo "<p><a href=\"".SITEURL."/index.php\">Return to themes home page</a></p>\n";

    $themes = filter($model->display,'');

    if (count($themes)==0)
        echo "<p>Sorry, no themes are available for the $model->name.</p>\n";
    else
    {
        if(isset($_GET['skip']))
            $skip = (int)$_GET['skip'];
        else
            $skip = 3;

        if($skip < 1 || $skip > 10)
            $skip = 3;
        ?>
        <p>
        <form method="GET" action="<?=SITEURL?>/index.php">
        <input type="hidden" name="model" value="<?=$id?>" />
        Set column size:
        <select name="skip">
        <?
            for($i=1;$i<=10;$i++)
            {
                if($skip==$i)
                    echo "<option value=\"$i\" selected=\"selected\">$i</option>\n";
                else
                    echo "<option value=\"$i\">$i</option>\n";
            }
        ?>
        </select>
        <input type="submit" value="Set" />
        </form>
        </p>
        <?

        $lcd = $model->display;
        if ($lcd == 'charcell')
            $width = 132;  # Width of the LCD in the sim for the Player
        else
            list($width,$height,$depth) = split("x",$lcd);

        echo "<table>\n";
        echo "<caption>Click On The Image To Download The Theme</caption>\n";
        for ($i = 0; $i < count($themes); $i++)
        {
            $status = $i % $skip;
            list($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date) = $themes[$i];
            if(file_exists(DATADIR."/".$lcd."/".$shortname.".zip"))
            {
                if($status == 0)
                    echo "<tr>\n";
                echo "<td class=\"themebox\" style=\"width: ".((int)($width)+20)."px\">\n";
                echo "<h2><a name='$model->name' id='$shortname'></a><a href=\"#$shortname\">$name</a></h2>\n";

                echo "<p align='center'>\n";
                echo "<a href=\"".SITEURL.THEMEDIR."/$lcd/$shortname.zip\" ";
                if ($img2)
                {
                    echo "onmouseout=\"MM_swapImgRestore()\" ";
                    echo "onmouseover=\"MM_swapImage('$shortname','','".SITEURL.THEMEDIR."/$lcd/".$shortname."_b.png',1)\">\n";
                }
                else
                    echo ">\n";
                echo "<img border=\"0\" src=\"".SITEURL.THEMEDIR."/$lcd/$shortname.png\" ";
                echo "alt=\"$name\" name=\"$shortname\" width=\"$width\" height=\"$height\" />";
                echo "</a><br />\n";
                $filesize = filesize(DATADIR."/$lcd/$shortname.zip");
                echo "<small>Size: ".human_filesize($filesize)."</small>\n";
                echo "</p>\n";
                echo "<small>\n";
                echo "<strong>Submitter:</strong><br />\n";
                echo "&nbsp;$author<br />\n";
                echo "<strong>Notes:</strong><br />\n";
                echo "&nbsp;$description<br />\n";
                echo "</small>\n";
                echo "</td>\n";
                if($status == $skip-1)
                    echo "</tr>\n";
            }
        }
        if($status != $skip-1)
            echo "</tr>\n";
        echo "</table>\n";
    }
}

include('bottom.php');
?>