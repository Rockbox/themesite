<?php

include('top.php');

require_once('tools.php');
require_once('ini.php');

if(isset($_GET['model']))
    $modelid = get_modelid($_GET['model']);
else
    $modelid = -1;

if ($modelid == -1)
{
    # HOME PAGE
    print "<h1>Rockbox Themes</h1>\n";
    print "<p>Welcome to the official Rockbox Themes repository - a collection of themes designed and created by the Rockbox community.  For more information, please visit:</p>\n";

    print "<ul>\n";
    print "<li><a href=\"http://www.rockbox.org/wiki/ThemeInstallation\">ThemeInstallation</a> - Installation instructions;</li>\n";
    print "<li><a href=\"http://www.rockbox.org/wiki/ThemeSubmission\">ThemeSubmission</a> - How to submit your theme to the Rockbox Themes site.</li>\n";
    print "</ul>\n";

    show_main_table();
}
else
{
    print "<h1>Rockbox Themes - $modelnames[$modelid] ($mainlcdtypes[$modelid])</h1>\n";
    # LIST OF THEMES FOR A SINGLE TARGET

    print "<p><a href=\"".SITEURL."/index.php\">Return to themes home page</a></p>\n";

    $themes = filter($mainlcdtypes[$modelid],'');

    if (count($themes)==0)
        print "<p>Sorry, no themes are available for the $modelnames[$modelid].</p>\n";
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
        <input type="hidden" name="model" value="<?=$models[$modelid]?>" />
        Set column size:
        <select name="skip">
        <?
            for($i=1;$i<=10;$i++)
            {
                if($skip==$i)
                    print "<option value=\"$i\" selected=\"selected\">$i</option>\n";
                else
                    print "<option value=\"$i\">$i</option>\n";
            }
        ?>
        </select>
        <input type="submit" value="Set" />
        </form>
        </p>
        <?
        
        $lcd = $mainlcdtypes[$modelid];
        if ($lcd == 'charcell') {
            $width = 132;  # Width of the LCD in the sim for the Player
        } else {
            list($width,$height,$depth) = split("x",$lcd);
        }
        
        print "<table>\n";
        print "<caption>Click On The Image To Download The Theme</caption>\n";
        for ($i = 0; $i < count($themes); $i++)
        {
            $status = $i % $skip;
            list($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date) = $themes[$i];
            if(file_exists(DATADIR."/".$lcd."/".$shortname.".zip"))
            {
                if($status == 0)
                    print "<tr>\n";
                print "<td class=\"themebox\" style=\"width: ".((int)($width)+20)."px\">\n";
                print "<h2><a name='$modelnames[$modelid]' id='$shortname'></a><a href=\"#$shortname\">$name</a></h2>\n";
                
                print "<p align='center'>\n";
                print "<a href=\"".SITEURL."/data/$lcd/$shortname.zip\" ";
                if ($img2)
                {
                    print "onmouseout=\"MM_swapImgRestore()\" ";
                    print "onmouseover=\"MM_swapImage('$shortname','','".SITEURL."/data/$lcd/".$shortname."_b.png',1)\">\n";
                }
                else
                    print ">\n";
                print "<img border=\"0\" src=\"".SITEURL."/data/$lcd/$shortname.png\" ";
                print "alt=\"$name\" name=\"$shortname\" width=\"$width\" height=\"$height\" />";
                print "</a><br />\n";
                $filesize = filesize(DATADIR."/".$lcd."/".$shortname.".zip");
                if($filesize > 1024*1024)
                    print "<small> Size: ".round(filesize(DATADIR."/".$lcd."/".$shortname.".zip")/1024/1024, 2)." MiB</small>\n";
                else
                    print "<small> Size: ".round(filesize(DATADIR."/".$lcd."/".$shortname.".zip")/1024, 2)." KiB</small>\n";
                print "</p>\n";
                print "<small>\n";
                print "<strong>Submitter:</strong><br />\n";
                print "&nbsp;$author<br />\n";
                print "<strong>Notes:</strong><br />\n";
                print "&nbsp;$description<br />\n";
                print "</small>\n";
                print "</td>\n";
                if($status == $skip-1)
                    print "</tr>\n";
            }
        }
        if($status != $skip-1)
            print "</tr>\n";
        print "</table>\n";
    }
}

include('bottom.php');
?>