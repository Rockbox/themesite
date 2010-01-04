{assign var="self" value="Edit theme `$theme.name`"}
{if $parenttarget}
{assign var="parent" value="admin.php?target=`$smarty.request.parenttarget`|Edit themes for `$theme.mainlcd`"}
{else}
{assign var="parent" value="admin.php?allthemes|Edit all themes"}
{/if}
{assign var="grandparent" value="admin.php|Admin frontpage"}
{include file="header.tpl" title=$self}

<h1>{$self}</h1>
{include file="breadcrumbs.tpl"}

<form method="post" action="{$smarty.server.SCRIPT_NAME}">
<input type="hidden" name="edittheme" value="{$theme.id}" />
{if $parenttarget}<input type="hidden" name="parenttarget" value="{$smarty.request.parenttarget}" /> {/if}
<table class="rockbox">
    <tr>
        <td><b>Theme name</b></td>
        <td><input type="text" name="themename" size="32" value="{$theme.name|escape:'html'}" /></td>
    </tr>
    <tr>
        <td><b>Mainlcd</b></td>
        <td><input type="text" name="mainlcd" size="32" value="{$theme.mainlcd|escape:'html'}" /></td>
    </tr>
    <tr>
        <td><b>Author</b></td>
        <td><input type="text" name="author" size="32" value="{$theme.author|escape:'html'}" /></td>
    </tr>
    <tr>
        <td><b>Email</b></td>
        <td><input type="text" name="email" size="32" value="{$theme.email|escape:'html'}" /></td>
    </tr>
    <tr>
        <td valign="top"><b>Description</b></td>
        <td><textarea cols="60" rows="6" name="description">{$theme.description|escape:'html'}</textarea></td>
    </tr>
    <tr>
        <td colspan="2">
            <input type="submit" value="Update" />
        </td>
    </tr>
    <tr>
        <th colspan="2">Zip contents</th>
    </tr>
    <tr>
        <td colspan="2"><pre>
{section name=i loop=$theme.files}
{$theme.files[i]}
{/section}</pre></td>
    </tr>
</table>

</form>

{include file="footer.tpl"}
