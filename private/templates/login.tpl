{include file="header.tpl" title="Admin area"}

<h1>Admin login</h1>

{if $msg}<p>{$msg}</p>{/if}

<form action="{$smarty.server.SCRIPT_NAME}" method="POST">
<table>
    <tr>
        <td>User:</td>
        <td><input type="text" name="user" /></td>
    </tr>
    <tr>
        <td>Pass:</td>
        <td><input type="password" name="pass" /></td>
    </tr>
    <tr>
        <td colspan="2" align="right"><input type="submit" value="Login" /></td>
    </tr>
</table>
</form>

{include file="footer.tpl"}
