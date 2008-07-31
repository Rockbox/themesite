<?php

include('top.php');

include('tools.php');

$submit = $_POST['submit'];

if (!$submit)
{
    print "<h1>Rockbox Themes - Upload a theme</h1>\n";

    print "<h2>Section 1 - Theme information</h2>\n";
    print "<form enctype=\"multipart/form-data\" action=\"upload.php\" method=\"post\">\n";
    # The following line is just for convenience for the user - the real maximum is specified in the php config file
    print "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"1000000\" />\n";

    print "<table class=\"rockbox\">\n";

    print "<tr>";
    print "<td><b>Theme name</b></td>";
    print "<td><input type=\"text\" name=\"name\" size=\"32\" /></td>";
    print "</tr>\n";

    print "<tr>";
    print "<td><b>Target device</b></td>";
    print "<td><select name=\"target\">";
    print "<option value=\"X\"></option>\n";
    for ($i=0;$i<count($models);$i++) {
        print "<option value=\"$models[$i]\">$modelnames[$i] ($mainlcdtypes[$i])</option>\n";
    }
    print "</select></td>";
    print "</tr>\n";

    print "<tr>";
    print "<td><b>Your real name</b><br /><small><a href=\"http://www.rockbox.org/wiki/WhyRealNames\">Why do I need to provide this?</a></td>";
    print "<td><input type=\"text\" name=\"author\" size=\"32\" /></td>";
    print "</tr>\n";

    print "<tr>";
    print "<td><b>Your email address</b><br /><small>Not displayed publically</small></td>";
    print "<td><input type=\"text\" name=\"email\" size=\"32\" /></td>";
    print "</tr>\n";

    print "<tr>";
    print "<td valign=\"top\"><b>Description</b><br /><small>If your theme uses images from other<br />themes, please include the name(s)<br /> and author(s) of those themes<br />here</small></td>";
    print "<td><textarea cols=\"60\" rows=\"6\" name=\"description\"></textarea></td>";
    print "</tr>\n";

    print "</table>\n";

    print "<h2>Section 2 - File uploads</h2>\n";
    print "<table class=\"rockbox\">\n";

    print "<tr>";
    print "<td><b>Main zip file</b></td>";
    print "<td><input type=\"file\" name=\"zip\" size=\"60\" /></td>";
    print "</tr>\n";
    print "<tr>";
    print "<td><b>WPS screenshot</b><br /><small>PNG format only</small></td>";
    print "<td><input type=\"file\" name=\"wpsimg\" size=\"60\" /></td>";
    print "</tr>\n";
    print "<tr>";
    print "<td><b>Menu screenshot</b><br /><small>PNG format only<br />(Optional)</small></td>";
    print "<td><input type=\"file\" name=\"menuimg\" size=\"60\" /></td>";
    print "</tr>\n";
    print "</table>\n";

    print "<h2>Section 3 - The legal stuff</h2>\n";

    print "<p>In line with the spirit of Rockbox itself, all themes on this website are freely redistributable (in both modified and unmodified forms) without any restriction (e.g. commercial/non-commercial) on their use.</p>\n";
    print "<p>By uploading your theme to this site, you are agreeing to license your work under the <a href=\"\">CC-BY-SA</a> license.</p>\n";

    print "<p><input type=\"submit\" name=\"submit\" value=\"Submit\"/></p>\n";
    print "</form>\n";
}
else
{
    $name = $_POST['name'];
    $target = $_POST['target'];
    $author = $_POST['author'];
    $email = $_POST['email'];
    $description = $_POST['description'];

    print "<p>Uploaded.</p>\n";
    print "<p>name=$name</p>\n";
    print "<p>target=$target</p>\n";
    print "<p>author=$author</p>\n";
    print "<p>email=$email</p>\n";
    print "<p>description=$description</p>\n";

    $ziptest = validate_zip($_FILES['zip']['tmp_name']);

    if (count($ziptest)==0)
    {
        print "<p>ZIP OK!</p>\n";
    }
    else
    {
        print "<p>The zip file contained the following errors:</p>\n";
        print "<ul>";
        for ($i=0;$i<count($ziptest);$i++)
        {
            print "<li>$ziptest[$i]</li>\n";
        }
        print "</ul>";
    }
}

include('bottom.php');


?>
