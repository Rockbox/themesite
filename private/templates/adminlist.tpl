{if $target}
{assign var="self" value="handle themes for $target"}
{else}
{assign var="self" value="handle all themes"}
{/if}
{assign var="parent" value="admin.php|Admin frontpage"}
{include file="header.tpl" title="Admin - $self"}

<h1>{$self|capitalize}</h1>
{include file="breadcrumbs.tpl"}
{if $target}   
   <a href="{$smarty.server.SCRIPT_NAME}?target={$smarty.request.target}&amp;approved=any">Show{if $approved=="any"}ing{/if} all</a>
 | <a href="{$smarty.server.SCRIPT_NAME}?target={$smarty.request.target}&amp;approved=approved">Show{if $approved=="approved"}ing{/if} approved</a>
 | <a href="{$smarty.server.SCRIPT_NAME}?target={$smarty.request.target}&amp;approved=hidden">Show{if $approved=="hidden"}ing{/if} hidden</a>
{else}
   <a href="{$smarty.server.SCRIPT_NAME}?allthemes&amp;approved=any">Show{if $approved=="any"}ing{/if} all</a>
 | <a href="{$smarty.server.SCRIPT_NAME}?allthemes&amp;approved=approved">Show{if $approved=="approved"}ing{/if} approved</a>
 | <a href="{$smarty.server.SCRIPT_NAME}?allthemes&amp;approved=hidden">Show{if $approved=="hidden"}ing{/if} hidden</a>
{/if}
{if count($themes) == 0}
<p>No themes match your selection</p>
{else}
<form method="POST" action="{$smarty.server.SCRIPT_NAME}">
{if $target}
<input type="hidden" name="target" value="{$smarty.request.target}" />
{else}
<input type="hidden" name="allthemes" value="yes" />
{/if}
<input type="hidden" name="approved" value="{$smarty.request.approved}" />
<input type="hidden" name="changestatuses" value="1" />
<table class="rockbox">
    <tr>
        <th>Screenshot</th>
        <th>Details</th>
        <th>Status</th>
        <th>Removal reason (will be mailed)</th>
    </tr>
{section name=i loop=$themes}
    {assign var='id' value=$themes[i].id}
    <tr>
        {if $themes[i].sshot_menu != ""}
            {assign var="oversrc" value="`$datadir`/`$themes[i].mainlcd`/`$themes[i].shortname`/`$themes[i].sshot_menu`"}
        {else}
            {assign var="oversrc" value=""}
        {/if}
        <td>{html_image file="`$datadir`/`$themes[i].mainlcd`/`$themes[i].shortname`/`$themes[i].sshot_wps`" href="download.php?themeid=`$themes[i].id`" oversrc=$oversrc}</td>
        <td>
            <strong>{$themes[i].name}</strong>
            <p><small>
            <strong>Author:</strong> <a href="mailto:{$themes[i].email|escape:'html'}">{$themes[i].author|escape:'html'}</a><br />
            {if $themes[i].verified == 0}
            <strong style='color:red'>The author has not verified this theme</strong><br />
            {/if}
            <strong>Submitted:</strong>&nbsp;{$themes[td].timestamp|escape:'html'}<br />
            <strong>Downloaded {$themes[i].downloadcnt|escape:'html'} time{if $themes[i].downloadcnt != 1}s{/if}.</strong><br />
            {if !$target}
            <strong>Designed for LCD size: </strong>&nbsp;{$themes[i].mainlcd|escape:'html'}<br />
            {if $themes[i].remotelcd} <strong>Designed for remote LCD size: </strong>&nbsp;{$themes[i].remotelcd|escape:'html'}<br /> {/if}
            {/if}
            {$themes[i].description|escape:'html'}
            {if $target}
            {if $themes[i].current_pass}
            <br /><strong>Works with <span title="$themes[i].current_version}">current build</span></strong>
            {/if}
            {if $themes[i].release_pass}
            <br /><strong>Works with release {$themes[i].release_version}</strong>
            {/if}
            {/if}
            <br /><a href="admin.php?edittheme={$themes[i].id}&amp;{if $target}parenttarget={$smarty.request.target}{/if}">Edit theme</a>
            </small></p>
        </td>
        <td>
            <input type="hidden" name="prevstatus[{$id}]" value="{$themes[i].approved}" />
            <label for="approved[{$id}]">Approved</label>
            <input type="radio" id="approved[{$id}]" name="status[{$id}]" value="1" {if $themes[i].approved == 1}checked="checked" {/if}/><br />

            <label for="hidden[{$id}]">Hidden</label>
            <input type="radio" id="hidden[{$id}]" name="status[{$id}]" value="0" {if $themes[i].approved == 0}checked="checked" {/if}/><br />
            
            <label for="delete[{$id}]">Delete</label>
            <input type="radio" id="delete[{$id}]" name="status[{$id}]" value="-1" />
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

