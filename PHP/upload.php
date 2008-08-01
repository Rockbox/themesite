<?php

require_once("ini.php");
include('top.php');
include('tools.php');

$err = array();
$zip_err = array();

function disp_helper($name)
{
    global $err;
    $ret = "";
    if(isset($_POST[$name]))
        $ret .= " value=\"".htmlspecialchars($_POST[$name])."\"";
    if(array_search($name, $err) !== false)
        $ret .= " class=\"error\"";
    return $ret;
}

if(isset($_POST['submit']))
{
    foreach($_POST as $name => $element)
    {
        if(strlen(trim($element)) == 0)
            $err[] = $name;
    }
    
    foreach($_FILES as $name => $values)
    {
        if(strlen(trim($values["name"])) == 0 && $name != "menuimg")
            $err[] = $name;
    }
    
    /* Check if valid email address */
    if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $_POST["email"]))
        $err[] = "email";
    
    /* Check if valid target */
    if(array_search($_POST['target'], $models) === false)
        $err[] = "target";
    
    /* Require a first and last name */
    if(count(explode(" ", $_POST['author'])) < 2)
        $err[] = "author";
    
    if($_FILES['zip']['type'] != "application/zip")
        $err[] = "zip";
    
    if($_FILES['wpsimg']['type'] != "image/png")
        $err[] = "wpsimg";
    
    if($_FILES['menuimg']['type'] != "image/png" && strlen($_FILES['menuimg']['name']) > 0)
        $err[] = "menuimg";
    
    if(count($err)==0)
    {
		if(md5_file($_FILES['wpsimg']['tmp_name']) == md5_file($_FILES['menuimg']['tmp_name'])
		   && strlen($_FILES['menuimg']['name']) > 0)
		{
			$err[] = "wpsimg";
			$err[] = "menuimg";
		}
        if(!validate_png($_FILES['wpsimg']['tmp_name']))
            $err[] = "wpsimg";
        
        if(!validate_png($_FILES['menuimg']['tmp_name']) && strlen($_FILES['menuimg']['name']) > 0)
            $err[] = "menuimg";
        
        $ziptest = validate_zip($_FILES['zip']['tmp_name']);
    
        if (count($ziptest)==0)
            print "<p>ZIP OK!</p>\n";
        else
        {
            $err[] = "zip";
            $zip_err = $ziptest;
        }
    }
}
?>
    <h1>Rockbox Themes - Upload a theme</h1>
    
    <? if(count($err)>0): ?>
    <div class="error_desc">
    There were some errors while procesing your information; please check if everything is filled in correctly!
    </div>
    <?
    if(count($zip_err)>0)
    {
        print "<p>Additionally, the zip file contains the following errors:</p>\n";
        print "<ul>";
        for ($i=0;$i<count($zip_err);$i++)
            print "<li>$zip_err[$i]</li>\n";
        print "</ul>";
    }
    ?>
    <hr />
    <? endif; ?>

    <h2>Section 1 - Theme information</h2>
    <form enctype="multipart/form-data" action="upload.php" method="post">
    <input type="hidden" name="MAX_FILE_SIZE" value="<?=MAXFILESIZE?>" />
    
    <table class="rockbox">

    <tr>
    <td><b>Theme name</b></td>
    <td><input type="text" name="name" size="32"<?=disp_helper("name");?>/></td>
    </tr>

    <tr>
    <td><b>Target device</b></td>
    <td>
    <select name="target"<?=(array_search("target", $err)?" class=\"error\"":"")?>>
    <option value="X"></option>
    <?
    for ($i=0; $i<count($models); $i++)
    {
        echo "<option value=\"$models[$i]\"";
        if(@$_POST['target'] == $models[$i])
            echo " selected=\"selected\"";
        echo ">".$modelnames[$i]." (".$mainlcdtypes[$i].")</option>";
    }
    ?>
    </select>
    </td>
    </tr>

    <tr>
    <td><b>Your real name</b><br /><small><a href="http://www.rockbox.org/wiki/WhyRealNames">Why do I need to provide this?</a></td>
    <td><input type="text" name="author" size="32"<?=disp_helper("author");?>/></td>
    </tr>

    <tr>
    <td><b>Your email address</b><br /><small>Not displayed publically</small></td>
    <td><input type="text" name="email" size="32"<?=disp_helper("email");?>/></td>
    </tr>

    <tr>
    <td valign="top"><b>Description</b><br /><small>If your theme uses images from other<br />themes, please include the name(s)<br /> and author(s) of those themes<br />here</small></td>
    <td>
    <textarea cols="60" rows="6" name="description"<?=(array_search("description", $err)?" class=\"error\"":"")?>><?=(isset($_POST['description'])?htmlspecialchars($_POST['description']):"")?></textarea></td>
    </tr>

    </table>

    <h2>Section 2 - File uploads</h2>
    <table class="rockbox">

    <tr>
    <td><b>Main zip file</b></td>
    <td<?=disp_helper("zip");?>><input type="file" name="zip" size="60" /></td>
    </tr>
    <tr>
    <td><b>WPS screenshot</b><br /><small>PNG format only</small></td>
    <td<?=disp_helper("wpsimg");?>><input type="file" name="wpsimg" size="60" /></td>
    </tr>
    <tr>
    <td><b>Menu screenshot</b><br /><small>PNG format only<br />(Optional)</small></td>
    <td<?=disp_helper("menuimg");?>><input type="file" name="menuimg" size="60" /></td>
    </tr>
    </table>

    <h2>Section 3 - The legal stuff</h2>
    
    <p>In line with the spirit of Rockbox itself, all themes on this website are freely redistributable (in both modified and unmodified forms) without any restriction (e.g. commercial/non-commercial) on their use.</p>
<p>By uploading your theme to this site, you are agreeing to license your work under the <a href="http://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a> license.</p>

<p><input type="submit" name="submit" value="Submit"/></p>
</form>
<?
include('bottom.php');
?>
