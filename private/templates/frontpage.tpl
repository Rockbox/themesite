{* This template is also used for the admin frontpage *}

{if $adminworkneeded}
<div style='float:right;margin:0;padding:0;'>
<img src="adminworkneeded.png" alt="admin work needed">
</div>
{/if}

{if !$title}{assign var="title" value="Frontpage"}{/if}
{include file="header.tpl" title=$title rss="rss.php"}

{if $admin}
<h1>Admin area</h1>
<p>Pick a device below to manage themes for that target/screen size</p>
{else}
<h1>Rockbox themes</h1>

<h2>Upload your own theme</h2>
<p>Have you made a theme that is not listed on this site? Please read <a
href="//www.rockbox.org/wiki/ThemeGuidelines">the theme
guidelines</a> and then <a href="upload.php">upload your theme</a>.</p> 
{/if}

<h2>Downloading themes</h2> <p>All themes on this website can be downloaded and
installed via the integrated themes browser in <a
href="//www.rockbox.org/wiki/RockboxUtility">Rockbox
Utility</a>. You can also download themes manually by clicking on the picture
of your player in the table below, or browse all themes <a href="{$smarty.server.SCRIPT_NAME}?allthemes">here</a>.</p>

{assign var="cols" value=#targetcolumns#}
<table class="rockbox">
  {section name=tr loop=$targets step=$cols}
  <tr valign="top">
    {section name=td start=$smarty.section.tr.index loop=$smarty.section.tr.index+$cols}
    {if $targets[td]}

    <td align='center'>
        <a href="{$smarty.server.SCRIPT_NAME}?target={$targets[td].shortname}" title="{$targets[td].fullname}">
        <img border="0" src="//www.rockbox.org/playerpics/{$targets[td].pic}" alt="{$targets[td].fullname}">
        <p>{$targets[td].fullname}</a><br><small>{$targets[td].numthemes} theme{if $targets[td].numthemes ne 1}s{/if}</small></td>
    {/if}
    {/section}
  </tr>
  {/section}
</table>
<br/>
You can also directly search for specific themes here: <br/>
<form method="POST" action="{$smarty.server.SCRIPT_NAME}">
    <input type="hidden" name="searchtheme" value=1 />
    <select name=searchtype>
        <option value='name'>Theme name</option>
        <option value='author'>Author</option>
        <option value='mainlcd'>Lcd size</option>    
    </select>
    <input type="text" name="searchword" />
    <input type="submit" value="Search" />
</form>

{if $admin}
<p><a href="{$smarty.server.SCRIPT_NAME}?runcheckwps">Run checkwps on all themes</a></p>
<hr />
{if $adminmsg}<p>{$adminmsg}</p> <hr />{/if}
<p><a href="{$smarty.server.SCRIPT_NAME}?showlog">View database log</a></p>
<hr />
<h2>Edit/Add targets</h2>
<form method="POST" action="{$smarty.server.SCRIPT_NAME}">
    <input type="hidden" name="showtarget" value="yes" />
    <select name=curtarget>
        <option>New target</option>
        {foreach from=$targets item=target}
            <option>{$target.shortname}</option>
        {/foreach}
    </select>
    <input type="submit" value="Edit" />
</form>
<hr />
<h2> Allowed Themesettings </h2>
<p><a href="{$smarty.server.SCRIPT_NAME}?showsetting">Add Setting</a> </p>
Current Themesettings:
<select>
    {foreach from=$settings item=setting}
        <option>{$setting.name} - {$setting.type}</option>
    {/foreach}
</select>
<hr />
<h2>TODO list</h2>
<pre>
{include file="TODO"}
<pre>
{/if}

{include file="footer.tpl"}
