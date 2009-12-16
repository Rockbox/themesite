{assign var="parent" value="admin.php|Admin frontpage"}
{assign var="self" value="Check themes"}
{include file="header.tpl" title="Admin - $self"}

<h1>Checking all themes</h1>

{include file="breadcrumbs.tpl"}

<table class="rockbox">
{section name=i loop=$checkwpsresults}
    <tr> <td>
    {if $checkwpsresults[i].summary.pass}
        <tt style="color: green">pass</tt>
    {else}
        <tt style="color: red">fail</tt>
    {/if}
    </td> <td> 
    {$checkwpsresults[i].theme.mainlcd}/{$checkwpsresults[i].theme.shortname}/{$checkwpsresults[i].theme.zipfile} ({$checkwpsresults[i].summary.duration|string_format:'%0.3f'} seconds)
    </td>
    {foreach from=$checkwpsresults[i].result key=version item=result}
        {foreach from=$result key=target item=res}
            <td> 
            {if $res.pass}
                <tt style="color: green">{$target} {$version} </tt>
            {else}
                <tt style="color: red">{$target} {$version} </tt>
            {/if}
            </td>
        {/foreach}
    {/foreach}
    </tr>
{/section}
</table>

{include file="footer.tpl"}
