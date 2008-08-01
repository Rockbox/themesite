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
        $lcd = $mainlcdtypes[$modelid];
        if ($lcd == 'charcell') {
            $width = 132;  # Width of the LCD in the sim for the Player
        } else {
            list($width,$height,$depth) = split("x",$lcd);
        }
		
?>
<style type="text/css">
div.themebox
{
	height: <?=((int)$height)+350?>px;
}
</style>
<?
		
        print "<table>\n";
		print "<tr><td>\n";
        print "<caption>Click On The Image To Download The Theme</caption>\n";
		print "</td></tr>\n";
		print "<tbody><tr><td style=\"border:outset 1px #ffffff; background:url(http://home.infocity.de/m.arnold/temp/themes/themes-themes.php-Dateien/bglogo.gif) no-repeat right bottom;\">";
        for ($i = 0; $i < count($themes); $i++)
        {
			list($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date) = $themes[$i];
			if(file_exists(DATADIR."/".$lcd."/".$shortname.".zip"))
			{
				print "<div name=\"boxie\" class=\"themebox\" style=\"width: ".((int)($width)+20)."px\">\n";
				print "<h2><a name='$modelnames[$modelid]' id='$shortname'></a>$name</h2>\n";
				
				print "<p align='center'>\n";
				print "<a href=\"data/$lcd/$shortname.zip\" ";
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
				print "</div>\n";
			}
        }
		print "</td></tr></tbody>";
        print "</table>\n";
    }
}

include('bottom.php');


/*

***
To be fixed...
***

<script type="text/javascript">
<!--
function set_divs_right()
{
	var alles = document.getElementsByName("boxie");
	var maxi = 0;
	for(i=0; i<alles.length; i++)
		maxi = Math.max(maxi, alles[i].scrollHeight);
	for(i=0; i<alles.length; i++)
		alles[i].style.height = maxi;
	alert(maxi);
}
window.onload = set_divs_right;
// -->
</script>
*/
?>