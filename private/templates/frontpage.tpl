{* This template is also used for the admin frontpage *}

{if !$title}{assign var="title" value="Frontpage"}{/if}
{include file="header.tpl" title=$title}

{if $admin}
<h1>Admin area</h1>
<p>Pick a device below to manage themes for that target/screen size</p>
{else}
<h1>Rockbox themes</h1>
<p>Pick a device below to see themes for that target/screen size</p>
{/if}

<table>
{section name=mysec loop=$targets}
    <td align="center">
        <a href="{$smarty.server.SCRIPT_NAME}?target={$targets[mysec].shortname}">
        <img src="http://www.rockbox.org/playerpics/{$targets[mysec].pic}" title="{$targets[mysec].fullname}" />
        </a><br />
        <small>LCD: {$targets[mysec].mainlcd}x{$targets[mysec].depth}</small>
    </td>
{/section}
</table>

{if $admin}
<hr />
{if $adminmsg}<p>{$adminmsg}</p>{/if}
<h2>Add a missing target</h2>
<form method="POST" action="{$smarty.server.SCRIPT_NAME}">
<input type="hidden" name="addtarget" value="yes" />
<table>
    <tr>
        <td>Name</td>
        <td><input type="text" name="fullname" /></td><td>(e.g. Apple Ipod Video)</td>
    </tr>
    <tr>
        <td>Shortname</td>
        <td><input type="text" name="shortname" /></td><td>(e.g. ipodvideo)</td>
    </tr>
    <tr>
        <td>Main LCD resolution</td>
        <td><input type="text" name="mainlcd" /></td><td>(e.g. 320x240)</td>
    </tr>
    <tr>
        <td>Main LCD screen depth</td>
        <td><input type="text" name="depth" /></td><td>(e.g. 16)</td>
    </tr>
    <tr>
        <td>Remote LCD resolution</td>
        <td><input type="text" name="remotelcd" /></td><td>(e.g. 320x240)</td>
    </tr>
    <tr>
        <td>Picture</td>
        <td><input type="text" name="pic" /></td><td>(e.g. ipodvideo-small.png)</td>
    </tr>
    <tr>
        <td colspan="2" align="right"><input type="submit" value="Add" /></td>
        <td></td>
    </tr>
</table>
</form>
{else}
<h2>TODO list</h2>
<pre>
{include file="TODO"}
<pre>
{/if}

{include file="footer.tpl"}
