<?php
session_start();
require_once("config.php");
require_once("tools.php");


include_once("top.php");

if(@$_SESSION['loggedin'] === true)
{
    switch(@$_GET['p'])
    {
        case "logout":
            $_SESSION['loggedin'] = false;
            $_SESSION['user'] = false;
            ?>
            Please click <a href="<?=SITEURL?>/admin.php">here</a> if you aren't being transferred in 5 seconds...
            <script>
            <!--
            window.onload = function() { window.location = "<?=SITEURL?>/admin.php"; };
            //-->
            </script>
            <?
            include_once("bottom.php");
            exit();
        break;
        case "commit":
            $themes = explode("\n", file_get_contents(DATADIR."/pre-themes.txt"));
            for($i = 0; $i < count($themes); $i++)
            {
                $tmp = explode("|", $themes[$i]);
                unset($themes[$i]);
                $themes[$tmp[0]] = $tmp;
            }
            if(isset($_POST['delete']))
            {
                foreach($_POST['delete'] as $del)
                {
                    if(isset($themes[$del]))
                    {
                        unlink(PREDATADIR."/".$themes[$del][7]."/".$themes[$del][2].".zip");
                        unlink(PREDATADIR."/".$themes[$del][7]."/".$themes[$del][2].".png");
                        if($themes[$del][4] == "1")
                            unlink(PREDATADIR."/".$themes[$del][7]."/".$themes[$del][2]."_b.png");
                        file_put_contents(DATADIR."/pre-themes.txt", str_replace(implode("|", $themes[$del]), "", file_get_contents(DATADIR."/pre-themes.txt")));
                    }
                }
            }
            if(isset($_POST['accept']))
            {
                foreach($_POST['accept'] as $acc)
                {
                    if(isset($themes[$acc]))
                    {
                        file_put_contents(PRE_THEMES, str_replace(implode("|", $themes[$acc]), "", file_get_contents(PRE_THEMES)));
                        file_put_contents(THEMES, file_get_contents(THEMES).implode("|", $themes[$acc])."\n");
                    }
                }
            }
        break;
    }
    ?>
    <h3>Themes</h3>
    <form action="<?=SITEURL?>/admin.php?p=commit" method="POST">
    <table class="admin">
    <?
    $themes = explode("\n", file_get_contents(PRE_THEMES));
    foreach($themes as $theme)
    {
        if(strlen($theme)>0)
        {
            list($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date) = explode("|", $theme);
            $lcd = explode("x", $mainlcd);
            ?>
            <tr class="title">
                <td colspan="2"><?=$name?> [<?=$author?> &lt;<?=$email?>&gt;] - <?=$date?></td>
                <td class="check">Accept</td>
                <td class="check">Reject</td>
            </tr>
            <tr>
                <td class="image" width="<?=$lcd[0]?>">
                <a href="<?=SITEURL?>/admin_edit.php?id=<?=$id?>&pre=1" <?=($img2 == "1" ? "onmouseout=\"MM_swapImgRestore()\" onmouseover=\"MM_swapImage('$shortname','','".PRESITEDIR."/$mainlcd/".$shortname."_b.png',1)\" >" : ">")?><img src="<?=PRESITEDIR?>/<?=$mainlcd?>/<?=$shortname?>.png" name="<?=$shortname?>" border="0" /></a>
                </td>
                <td class="desc"><?=$description?></td>
                <td class="check"><input type="checkbox" name="accept[]" value="<?=$id?>" /></td>
                <td class="check"><input type="checkbox" name="delete[]" value="<?=$id?>" /><br /><br />
                                  <input type="text" name="delete_<?=$id?>" value="Reason" />
                </td>
            </tr>
            <?
        }
    }
    ?>
    <tr>
    <td colspan="3" align="center">
    <input type="submit" value="Commit" />
    </td>
    </tr>
    </table>
    </form>
    <a href="<?=SITEURL?>/admin.php?p=logout">Logout</a>
    <?
}
else
{
    if(isset($_POST['login']))
    {
        $txt = explode("\n", @file_get_contents(USERS));
        $_SESSION['loggedin'] = false;
        foreach($txt as $user)
        {
            $user = explode("|", $user);
            if($_POST['user'] == trim($user[0]) && md5($_POST['pass']) == trim($user[1]) )
            {
                $_SESSION['loggedin'] = true;
                $_SESSION['user'] = $user[0];
            }
        }
        if($_SESSION['loggedin'] === true)
        {
            ?>
            Please click <a href="<?=SITEURL?>/admin.php">here</a> if you aren't being transferred in 5 seconds...
            <script>
            <!--
            window.onload = function() { window.location = "<?=SITEURL?>/admin.php"; };
            //-->
            </script>
            <?
        }
        else
        {
            ?>
            Wrong password and/or username!
            <?
        }
    }
    else
    {
?>
<form action="<?=SITEURL?>/admin.php" method="POST">
<table>
    <tr>
        <td>Username:</td>
        <td><input name="user" type="text"></td>
    </tr>
    <tr>
        <td>Password:</td>
        <td><input name="pass" type="password"></td>
    </tr>
    <tr>
        <td colspan="2" align="center"><input name="login" type="submit" value="Login"></td>
    </tr>
</table>
</form>
<?
    }
}

include_once("bottom.php");
?>