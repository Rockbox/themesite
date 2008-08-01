<?php

include('top.php');

include('tools.php');

if(isset($_GET['model']))
	$modelid = get_modelid($_GET['model']);
else
	$modelid = false;

if ($modelid == false)
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

    print "<p><a href=\"index.php\">Return to themes home page</a></p>\n";

    $themes = filter($mainlcdtypes[$modelid],'');

    if (count($themes)==0)
    {
        print "<p>Sorry, no themes are available for the $modelnames[$modelid].</p>\n";
    }
    else
    {
        $lcd = $mainlcdtypes[$modelid];
        if ($lcd == 'charcell') {
            $width = 132;  # Width of the LCD in the sim for the Player
        } else {
            list($width,$height,$depth) = split("x",$lcd);
        }

        if ($width <= 220) { $cols = 3; }
        else { $cols = 2; }

        print "<table class='rockbox' summary='layout' width='792'>\n";
        print "<caption>Click On The Image To Download The Theme</caption>\n";

        for ($i = 0 ; $i < count($themes); $i += $cols)
        {
            print "<tr valign=\"top\">\n";
            for ($j=$i; $j < $i+$cols; $j++)
            {
                if ($j < count($themes))
                {
                    list($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date) = $themes[$j];
                    print "<th width='264'><a name='$modelnames[$modelid]' id='$shortname'></a>$name</th>\n";
                }
            }
            print "</tr>\n";

            print "<tr valign='top'>\n";
            for ($j=$i; $j < $i+$cols; $j++)
            {
                if ($j < count($themes))
                {
                    list($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date) = $themes[$j];
                    print "<td><p align=\"center\">\n";
                    print "<a href=\"data/$lcd/$shortname.zip\" ";
                    if ($img2) {
                        print "onmouseout=\"MM_swapImgRestore()\" ";
                        print "onmouseover=\"MM_swapImage('$shortname','','data/$lcd/".$shortname."_b.png',1)\">\n";
                    }
                    print "<img src=\"data/$lcd/$shortname.png\" ";
                    print "alt=\"$name\" name=\"$shortname\" width=\"$width\" height=\"$height\" />";
                    print "</a><br />\n";
                    print "<small> Size: ".round(filesize($DATADIR."/".$lcd."/".$shortname.".zip")/1024, 2)." KB</small>\n";
                    print "</p>\n";
                    print "<small>";
                    print "<strong>Submitter:</strong><br />\n";
                    print "&nbsp;$author<br />\n";
                    print "<strong>Notes:</strong><br />\n";
                    print "&nbsp;$description<br />\n";
                    print "</small>\n";
                    print "</td>\n";
                }
            }
            print "</tr>\n";
        }
        print "</table>\n";
    }
}

include('bottom.php');


?>
