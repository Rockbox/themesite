{include file="header.tpl" title="Upload a theme"}

<h1>Rockbox Themes - Upload a theme</h1>

<h2>Before you start</h2>

<p>Make sure you have read and understood the <a
href="http://www.rockbox.org/wiki/ThemeGuidelines">theme guidelines</a></p>

{if is_array($general_errors)}
<p class="error">
Some internal internal error{if count($general_errors) != 0}s{/if} occured.
This is very unlikely to be your fault.
</p>
<ul>
    {section name=i loop=$general_errors}
    <li>{$general_errors[i]}</li>
    {/section}
</ul>
{/if}

    <style type="text/css">
{literal}
    .rockbox { width: 650px; }
{/literal}
    </style>

    <form action="{$smarty.server.SCRIPT_NAME}" method="post" enctype="multipart/form-data">
    <input type="hidden" name="MAX_FILE_SIZE" value="{$maxuploadsize}" />

    <h2>Section 1 - Theme information</h2>
    <table class="rockbox">

    <tr>
        <td>Theme name:</td>
        <td><input type="text" name="themename" size="32" value="{$smarty.post.themename}" /></td>
        {if is_array($errors) && isset($errors.themename)}<td class='error'>{$errors.themename}</td>{/if}
    </tr>

    <tr>
        <td><b>Target device</td>
        <td>{html_options name="target" options=$targets selected=$smarty.post.target}</td>
    </tr>

    <tr>
    <td><b>Your real name</b><br /><small><a href="http://www.rockbox.org/wiki/WhyRealNames">Why do I need to provide this?</a></td>
    <td><input type="text" name="author" size="32" value="{$smarty.post.author}" /></td>
    {if is_array($errors) && isset($errors.author)}<td class='error'>{$errors.author}</td>{/if}
    </tr>

    <tr>
    <td><b>Your email address</b><br /><small>Not displayed publically</small></td>
    <td><input type="text" name="email" size="32" value="{$smarty.post.email}" /></td>
    {if is_array($errors) && isset($errors.email)}<td class='error'>{$errors.email}</td>{/if}
    </tr>

    <tr>
    <td valign="top"><b>Description</b><br /><small>If your theme uses images from other<br />themes, please include the name(s)<br /> and author(s) of those themes<br />here</small></td>
    <td>
    <textarea cols="60" rows="6" name="description">{$smarty.post.description}</textarea></td>
    </tr>
    </table>

    <h2>Section 2 - File uploads</h2>
    <table class="rockbox">
      <tr>
        <td><b>Main zip file</b></td>
        <td><input type="file" name="themefile" size="60" /><br /><small>Don't forget to read the <a href="http://www.rockbox.org/wiki/ThemeGuidelines">theme guidelines</a>.</small></td>
        {if is_array($errors) && array_key_exists('themefile', $errors)}
            <td class='error'>
                <ul>
                {section name=i loop=$errors.themefile}
                    <li>{$errors.themefile[i]}</li>
                {/section}
                </ul>
            </td>
        {/if}
      </tr>
      <tr>
        <td><b>WPS screenshot</b><br /></td>
        <td><input type="file" name="sshot_wps" size="60" /><br /><small>The dimensions should be the same as the LCD size</small></td>
        {if is_array($errors) && array_key_exists('sshot_wps', $errors)}
            <td class='error'>
                <ul>
                {section name=i loop=$errors.sshot_wps}
                    <li>{$errors.sshot_wps[i]}</li>
                {/section}
                </ul>
            </td>
        {/if}
      </tr>
      <tr>
        <td><b>Menu screenshot</b><br /><small>(Optional)</small></td>
        <td><input type="file" name="sshot_menu" size="60" /><br /><small>The dimensions should be the same as the LCD size</small></td>
        {if is_array($errors) && array_key_exists('sshot_menu', $errors)}
            <td class='error'>
                <ul>
                {section name=i loop=$errors.sshot_menu}
                    <li>{$errors.sshot_menu[i]}</li>
                {/section}
                </ul>
            </td>
        {/if}
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
        <td colspan="2">
            <p><input type="checkbox" name="ccbysa" {if $smarty.post.ccbysa eq "on"}checked="checked" {/if}/>&nbsp;I agree</p>
        </td>
        {if is_array($errors) && isset($errors.ccbysa)}<td class='error'>{$errors.ccbysa}</td>{/if}
    </tr>
    </table>
    
    <p><input type="submit" name="submit" value="Submit" />
    </p>
    </form>

{include file="footer.tpl"}
