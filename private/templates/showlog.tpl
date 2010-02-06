{assign var="parent" value="admin.php|Admin frontpage"}
{assign var="self" value="Show log"}
{include file="header.tpl" title="Admin - $self"}

<h1>Show log</h1>

{include file="breadcrumbs.tpl"}

<table class="rockbox">
{foreach from=$log item=logitem}
    <tr> 
    <td>{$logitem.time}</td>
    <td>{$logitem.ip}</td>
    <td>{$logitem.admin}</td>
    <td>{$logitem.msg}</td>
    </tr>
{/foreach}
</table>

{include file="footer.tpl"}
