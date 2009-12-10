{if $target}
    {assign var="self" value="Edit target `$target.shortname`"}
{else}
    {assign var="self" value="Add target"}
{/if}
{assign var="parent" value="admin.php|Admin frontpage"}
{include file="header.tpl" title=$self}

<h1>{$self}</h1>
{include file="breadcrumbs.tpl"}

{if $target}
<h2>Edit target {$target.shortname}</h2>
{else}
<h2>Add a target</h2>
{/if}

<form method="POST" action="{$smarty.server.SCRIPT_NAME}">
{if $target}
    <input type="hidden" name="edittarget" value="{$target.id}" />
{else}
    <input type="hidden" name="addtarget" value="yes" />
{/if}
<table>
    <tr>
        <td>Name</td>
        <td><input type="text" name="fullname" value="{$target.fullname}" /></td><td>(e.g. Apple Ipod Video)</td>
    </tr>
    <tr>
        <td>Shortname</td>
        <td><input type="text" name="shortname" value="{$target.shortname}" /></td><td>(e.g. ipodvideo - must match checkwps usage)</td>
    </tr>
    <tr>
        <td>Main LCD resolution</td>
        <td><input type="text" name="mainlcd" value="{$target.mainlcd}" /></td><td>(e.g. 320x240)</td>
    </tr>
    <tr>
        <td>Main LCD screen depth</td>
        <td><input type="text" name="depth" value="{$target.depth}" /></td><td>(e.g. 16)</td>
    </tr>
    <tr>
        <td>Remote LCD resolution</td>
        {if isset($target.remotelcd) }
        <td><input type="text" name="remotelcd" /></td><td>(e.g. 320x240)</td>
        {else}
        <td><input type="text" name="remotelcd" value="{$target.remotelcd}" /></td><td>(e.g. 320x240)</td>
        {/if}
    </tr>
    <tr>
        <td>Picture</td>
        <td><input type="text" name="pic" value="{$target.pic}" /></td><td>(e.g. ipodvideo-small.png)</td>
    </tr>
    <tr>   
        <td colspan="2" align="right"><input type="submit" value="Save" /></td>
        <td></td>
    </tr>
</table>
</form>

{include file="footer.tpl"}
