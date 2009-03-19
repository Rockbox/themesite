{include file="header.tpl" title="Upload a theme"}

<h1>Upload a new theme</h1>

{if is_array($general_errors)}
<p>
Some internal internal error{if count($general_errors) != 0}s{/if} occured.
This is very unlikely to be your fault.
</p>
<ul>
    {section name=i loop=$general_errors}
    <li>{$general_errors[i]}</li>
    {/section}
</ul>
{/if}

<form action="{$smarty.server.SCRIPT_NAME}" method="post" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="{$maxuploadsize}" />

<table>
    <tr>
        <td>Author:</td>
        <td><input type="text" name="author" value="{$smarty.post.author}" /></td>
        {if is_array($errors) && isset($errors.author)}<td class='error'>{$errors.author}</td>{/if}
    </tr>
    <tr>
        <td>Email:</td>
        <td><input type="text" name="email" value="{$smarty.post.email}" /></td>
        {if is_array($errors) && isset($errors.email)}<td class='error'>{$errors.email}</td>{/if}
    </tr>
    <tr>
        <td>Theme name:</td>
        <td><input type="text" name="themename" value="{$smarty.post.themename}" /></td>
        {if is_array($errors) && isset($errors.themename)}<td class='error'>{$errors.themename}</td>{/if}
    </tr>
    <tr>
        <td>Target:</td>
        <td>{html_options name="target" options=$targets selected=$smarty.post.target}</td>
    </tr>
    <tr>
        <td valign="top">Description:</td>
        <td><textarea name="description" rows="10" cols="50">{$smarty.post.description}</textarea></td>
    </tr>
    <tr>
        <td>CC-BY-SA:</td>
        <td><input type="checkbox" name="ccbysa" {if $smarty.post.ccbysa eq "on"}checked="checked" {/if}/></td>
        {if is_array($errors) && isset($errors.ccbysa)}<td class='error'>{$errors.ccbysa}</td>{/if}
    </tr>
    <tr>
        <td>Theme file:</td>
        <td><input type="file" name="themefile" /></td>
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
        <td>WPS sshot:</td>
        <td><input type="file"  name="sshot_wps" /></td>
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
        <td>Menu sshot:</td>
        <td><input type="file" name="sshot_menu" /></td>
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
    <tr>
        <td><input type="submit" value="Upload" /></td>
    </tr>
</table>
</form>

{include file="footer.tpl"}
