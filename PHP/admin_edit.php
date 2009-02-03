<?
ob_start();
session_start();
require_once("config.php");
require_once("tools.php");

include_once("top.php");
if(@$_SESSION['loggedin'] === true)
{
    $id = (int)$_GET['id'];
    $pre = (@$_GET['pre'] == 1 ? true : false);
    $theme = get_theme($id, ($pre ? PRE_THEMES : THEMES));
    if($theme !== false)
    {
        list($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date) = $theme;
        if(isset($_POST['name']))
        {
            $ntheme = "$id|".$_POST['name']."|$shortname|$img1|$img2|".$_POST['author'].
                      "|".$_POST['email']."|$mainlcd|$remotelcd|".$_POST['description'].
                      "|".$_POST['date'];
            file_put_contents(($pre ? PRE_THEMES : THEMES), str_replace(implode("|", $theme),
                                                            $ntheme,
                                                            file_get_contents(($pre ? PRE_THEMES : THEMES))));
            header('Location: '.SITEURL.'/admin_edit.php?id='.$id.'&pre='.(int)$pre, true, 302);
            exit();
        }
        ?>
        <form action="admin_edit.php?id=<?=$id?>" method="POST">
        <table class="admin-edit">
        <tr>
            <td>Theme name</td>
            <td><input type="text" name="name" value="<?=$name?>" /></td>
        </tr>
        <tr>
            <td>Theme author</td>
            <td><input type="text" name="author" value="<?=$author?>" /></td>
        </tr>
        <tr>
            <td>Theme author email</td>
            <td><input type="text" name="email" value="<?=$email?>" /></td>
        </tr>
        <tr>
            <td>Theme image</td>
            <td><a href="<?=($pre ? PRESITEDIR : SITEDIR)?>/<?=$mainlcd?>/<?=$shortname?>.zip" <?=($img2 == "1" ? "onmouseout=\"MM_swapImgRestore()\" onmouseover=\"MM_swapImage('$shortname','','".($pre ? PRESITEDIR : SITEDIR)."/$mainlcd/".$shortname."_b.png',1)\" >" : ">")?><img src="<?=($pre ? PRESITEDIR : SITEDIR)?>/<?=$mainlcd?>/<?=$shortname?>.png" name="<?=$shortname?>" border="0" /></a></td>
        </tr>
        <tr>
            <td>Theme description</td>
            <td><textarea name="description" rows="<?=strlen($description)/60?>" cols="60"><?=htmlspecialchars($description)?></textarea></td>
        </tr>
        <tr>
            <td>Theme zip contents</td>
            <td>
            <pre id="zip-content"><? passthru(UNZIP." -lqq ".($pre ? PREDATADIR : DATADIR)."/$mainlcd/$shortname.zip"); ?></pre>
            </td>
        <tr>
            <td>Added on </td>
            <td><input type="text" name="date" value="<?=$date?>" /></td>
        </tr>
        <tr>
            <td colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" value="Edit" /></td>
        </tr>
        </table>
        </form>
        <?
        
    }
}
include_once("bottom.php");
?>