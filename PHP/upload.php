<?php
require_once("config.php");
require_once('tools.php');

include('top.php');

$err = array();
$zip_err = array();
$err_desc = array();

/* Add a red box around the element when it's in $err */
function err_helper($name)
{
    global $err;

    if(array_search($name, $err) !== false)
        return 'class="error"';
    else
        return '';
}

if(isset($_POST['submit']))
{
    /* 'Basic form validation' made easy */
    foreach($_POST as $name => $element)
    {
        if(strlen(trim($element)) == 0)
            $err[] = $name;
    }

    /* Check if valid email address */
    if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $_POST["email"]))
        $err[] = "email";

    /* Check if valid target */
    if( !isset($models[$_POST['target']]) )
        $err[] = "target";

    /* Require a first *and* last name */
    if(count(explode(" ", $_POST['author'])) < 2)
        $err[] = "author";

    if(!($_FILES['zip']['type'] == "application/x-zip-compressed" ||
         $_FILES['zip']['type'] == "application/zip"))
        $err[] = "zip";

    foreach($_FILES as $name => $values)
    {
        if(strlen(trim($values["name"])) == 0 && $name != "menuimg")
            $err[] = $name;
        if(!is_uploaded_file($values["tmp_name"]) && strlen(trim($values["name"])) > 0)
            $err[] = $name;
    }
    
    if(!isset($_POST['agree']) || $_POST['agree'] != "yes")
        $err[] = "agree";

    if(count($err) == 0 && strlen($_FILES['menuimg']['name']) > 0 && /* Lazy checking */
       md5_file($_FILES['wpsimg']['tmp_name']) == md5_file($_FILES['menuimg']['tmp_name']))
    {
        $err[] = "wpsimg";
        $err[] = "menuimg";
    }
       
    if(count($err) == 0)
    {
        $model = $models[$_POST['target']];
        
        /* Drop last element from LCD size */
        $lcd_size = $model->lcd_size();
        
        if(!validate_png($_FILES['wpsimg']['tmp_name'], $lcd_size))
        {
            $wpsimg = convert_to_png($_FILES['wpsimg']['tmp_name'],
                                     $_FILES['wpsimg']['name'], $lcd_size);
            if($wpsimg === false)
                $err[] = "wpsimg";
        }
        else
            $wpsimg = $_FILES['wpsimg']['tmp_name'];

        if(strlen($_FILES['menuimg']['tmp_name']) > 0)
        {
            if(!validate_png($_FILES['menuimg']['tmp_name'], $lcd_size))
            {
                $menuimg = convert_to_png($_FILES['menuimg']['tmp_name'],
                                          $_FILES['menuimg']['name'], $lcd_size);
                if($menuimg === false)
                    $err[] = "menuimg";
            }
            else
                $menuimg = $_FILES['menuimg']['tmp_name'];
        }

        $ziptest = validate_zip($_FILES['zip']['tmp_name'], $models[$_POST['target']]);

        if(count($ziptest) > 0)
        {
            $err[] = "zip";
            $err_desc = $ziptest;
        }
        else
        {
            $name = htmlspecialchars(trim($_POST['name']));
            $author = ucwords(htmlspecialchars(trim($_POST['author'])));
            $email = htmlspecialchars(trim($_POST['email']));
            $description = str_replace(array("\n","\r","\t"), "", htmlspecialchars(trim($_POST['description'])));
            $date = date("Y-m-d");
            # make a filesystem safe filename
            $shortname = strtr(mb_convert_encoding($_FILES['zip']['name'], 'ASCII'), ' ,;:?*#!§$%&/(){}<>=`´|\\\'"','');

            if(file_exists(DATADIR."/".$model->display."/".$shortname.".zip"))
                $shortname .= substr(md5(time().$_SERVER['REMOTE_PORT'].$name.$description), 0, 5);

            $new = get_new_id()."|$name|$shortname|1|".
                   (strlen($_FILES['menuimg']['name']) > 0 ? "1" : "").
                   "|$author|$email|$model->display|/|$description|$date\n";

            if (!file_exists(DATADIR."/".$model->display))
                  mkdir(DATADIR."/".$model->display); // Make sure the dir is available

            rename($wpsimg, DATADIR."/".$model->display."/".$shortname.".png");
            if(isset($menuimg))
                rename($menuimg, DATADIR."/".$model->display."/".$shortname."_b.png");
            move_uploaded_file($_FILES['zip']['tmp_name'], DATADIR."/".$model->display."/".$shortname.".zip");

            $handle = fopen(PRE_THEMES, "a");
            if(!$handle)
                die("Database is missing; please report to administrator!");
            fwrite($handle, $new);
            fclose($handle);
            
            echo "<p>Theme successfully added!<br>";
            echo "After an administrator checked it, it will be available on the site.</p>";
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
    if(count($err_desc)>0)
    {
        print "<p>Additionally, these errors occured while processing your ZIP file:</p>\n";
        print "<ul>";
        foreach($err_desc as $el)
            print "<li>$el</li>\n";
        print "</ul>";
    }
    ?>
    <hr />
    <? endif; ?>
    
    <style type="text/css">
    .rockbox { width: 650px; }
    </style>

    <form enctype="multipart/form-data" action="upload.php" method="post">

    <h2>Section 1 - Theme information</h2>
    <table class="rockbox">
    <tr>
    <td><b>Theme name</b></td>
    <td><input type="text" name="name" size="32" <?=err_helper('name')?> value="<?=@$_POST['name']?>" /></td>
    </tr>

    <tr>
    <td><b>Target device</b></td>
    <td>
    <select <?=err_helper('target')?> name="target">
    <option value=""></option>
    <?
    foreach ($models as $id => $model)
    {
        echo '<option value="'.$id.'"';
        if(@$_POST['target'] == $id)
            echo ' selected="selected"';
        echo '>'.$model->name.' ('.$model->display.')</option>';
    }
    ?>
    </select>
    </td>
    </tr>

    <tr>
    <td><b>Your real name</b><br /><small><a href="http://www.rockbox.org/wiki/WhyRealNames">Why do I need to provide this?</a></td>
    <td><input type="text" name="author" size="32" <?=err_helper('author')?> value="<?=@$_POST['author']?>" /></td>
    </tr>

    <tr>
    <td><b>Your email address</b><br /><small>Not displayed publically</small></td>
    <td><input type="text" name="email" size="32" <?=err_helper('email')?> value="<?=@$_POST['email']?>" /></td>
    </tr>

    <tr>
    <td valign="top"><b>Description</b><br /><small>If your theme uses images from other<br />themes, please include the name(s)<br /> and author(s) of those themes<br />here</small></td>
    <td>
    <textarea cols="60" rows="6" <?=err_helper('description')?> name="description"><?=htmlspecialchars(@$_POST['description'])?></textarea></td>
    </tr>
    </table>

    <input type="hidden" name="MAX_FILE_SIZE" value="<?=MAXFILESIZE?>" />
    <h2>Section 2 - File uploads</h2>
    <table class="rockbox">
      <tr>
        <td><b>Main zip file</b></td>
        <td <?=err_helper('zip')?>><input type="file" name="zip" size="60" /><br /><small>Don't forget to read the <a href="http://www.rockbox.org/wiki/ThemeGuidelines">theme guidelines</a>.</small></td>
      </tr>
      <tr>
        <td><b>WPS screenshot</b><br /></td>
        <td <?=err_helper('wpsimg')?>><input type="file" name="wpsimg" size="60" /><br /><small>The dimensions should be the same as the LCD size</small></td>
      </tr>
      <tr>
        <td><b>Menu screenshot</b><br /><small>(Optional)</small></td>
        <td <?=err_helper('menuimg')?>><input type="file" name="menuimg" size="60" /><br /><small>The dimensions should be the same as the LCD size</small></td>
      </tr>
    </table>

    <h2>Section 3 - The legal stuff</h2>

    <table class="rockbox">
    <tr>
        <td colspan="2">
            In line with the spirit of Rockbox itself, all themes on this website are freely redistributable (in both modified and unmodified forms) without any restriction (e.g. commercial/non-commercial) on their use.
        </td>
    </tr>
    <tr>
        <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="2">
            By uploading your theme to this site, you are agreeing to license your work under the following license:
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/3.0/88x31.png" />
            <a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/"> Creative Commons Attribution-Share Alike 3.0 Unported License</a>
        </td>
    </tr>
    <tr>
        <td <?=err_helper('agree')?> colspan="2">
            <p><input type="checkbox" name="agree" value="yes" />&nbsp;I agree</p>
        </td>
    </tr>
    </table>
    
    <p><input type="submit" name="submit" value="Submit" />
    </p>
    </form>
<?
include('bottom.php');
?>