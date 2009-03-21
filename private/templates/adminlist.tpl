{include file="header.tpl" title="Admin - handle themes for $mainlcd"}

<h1>Manage themes for {$mainlcd}</h1>
   
   <a href="{$smarty.server.SCRIPT_NAME}?target={$smarty.request.target}&amp;approved=any">Show{if $approved=="any"}ing{/if} all</a>
 | <a href="{$smarty.server.SCRIPT_NAME}?target={$smarty.request.target}&amp;approved=approved">Show{if $approved=="approved"}ing{/if} approved</a>
 | <a href="{$smarty.server.SCRIPT_NAME}?target={$smarty.request.target}&amp;approved=hidden">Show{if $approved=="hidden"}ing{/if} hidden</a>

{if count($themes) == 0}
<p>No themes match your selection</p>
{else}
<form method="POST" action="{$smarty.server.SCRIPT_NAME}">
<input type="hidden" name="target" value="{$smarty.request.target}" />
<input type="hidden" name="approved" value="{$smarty.request.approved}" />
<table class="rockbox">
    <tr>
        <th>Screenshot</th>
        <th>Name</th>
        <th>Status</th>
        <th>Removal reason (will be mailed)</th>
    </tr>
{section name=i loop=$themes}
    {assign var='id' value=$themes[i].id}
    <tr>
        <td>{html_image file="`$datadir`/`$mainlcd`/`$themes[i].shortname`/`$themes[i].sshot_wps`" href="`$datadir`/`$mainlcd`/`$themes[i].shortname`/`$themes[i].zipfile`"}</td>
        <td>{$themes[i].name}</td>
        <td>
            <!-- <input type="hidden" name="id[]" value="{$themes[i].id}" /> -->
            <input type="hidden" name="prevstatus[{$id}]" value="{$themes[i].approved}" />
            Approved <input type="radio" name="status[{$id}]" value="1" {if $themes[i].approved == 1}checked="checked" {/if}/><br />
            Hidden <input type="radio" name="status[{$id}]" value="0" {if $themes[i].approved == 0}checked="checked" {/if}/><br />
            Delete <input type="radio" name="status[{$id}]" value="-1" />
        </td>
        <td>
            <textarea rows="10" cols="40" name="reason[{$id}]">{$themes[i].reason|escape:'html'}</textarea>
        </td>
    </tr>
{/section}
</table>

<p><input type="submit" name="changestatuses" value="Update all themes" /></p>
</form>
{/if}
{include file="footer.tpl"}

