{assign var="self" value="Add setting"}
{assign var="parent" value="admin.php|Admin frontpage"}
{include file="header.tpl" title=$self}

<h1>{$self}</h1>
{include file="breadcrumbs.tpl"}

<h2>Add a setting</h2>

<form method="POST" action="{$smarty.server.SCRIPT_NAME}">
<input type="hidden" name="addsetting" value="yes" />
<table>
    <tr>
        <td>Name</td>
        <td><input type="text" name="name" value="" /></td><td>(e.g. selector type)</td>
    </tr>
    <tr>
        <td>Type</td>
        <td><input type="text" name="type" value="" /></td><td>(e.g. file or empty)</td>
    </tr>
    <tr>   
        <td colspan="2" align="right"><input type="submit" value="Save" /></td>
        <td></td>
    </tr>
</table>
</form>

{include file="footer.tpl"}
