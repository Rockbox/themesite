{assign var="themename" value=$theme.name}
{assign var="self" value="$themename Theme"}
{assign var="parent" value="index.php?target=`$smarty.request.target`|Themes for `$target`"}
{assign var="grandparent" value="index.php|Frontpage"}
{include file="header.tpl" title=$self rss="rss.php?target=`$smarty.request.target`&amp;themeid=`$theme.id` " rsstitle="`$smarty.request.theme.shortname` theme for `$smarty.request.target`"}

<h1>{$self}</h1>
{include file="breadcrumbs.tpl"}

<table class="rockbox">
  {* First print a row with theme name *}
  <tr>
    <th align="center" width="320"><a href="index.php?themeid={$theme.id}&amp;target={$smarty.request.target}">{$theme.name|escape:'html'}</th>
  </tr>

  {* Then a row with "the rest" *}
  <tr valign="top">
    <td>
    <p align="center">
    {if $theme.sshot_menu != ""}
        {assign var="oversrc" value="`$datadir`/`$theme.mainlcd`/`$theme.shortname`/`$theme.sshot_menu`"}
    {else}
        {assign var="oversrc" value=""}
    {/if}
    {html_image file="`$datadir`/`$theme.mainlcd`/`$theme.shortname`/`$theme.sshot_wps`" href="download.php?themeid=`$theme.id`" oversrc=$oversrc}<br />
    <small>Size: {$theme.size|siprefix}B</small>
    </p>
    <strong>Rating:</strong> &nbsp;
    {section name=i loop=10 step=2}
        {if $smarty.section.i.iteration*2 <= $theme.ratings}
           <img src="filled.png" style="width:15px; height:15px;" />
        {elseif ($smarty.section.i.iteration*2)-1 <= $theme.ratings}
            <img src="half.png" style="width:15px; height:15px;" />
        {else}
            <img src="empty.png" style="width:15px; height:15px;" />
        {/if}
    {/section} 
    {$theme.numratings} vote{if $theme.numratings !=1}s.{/if}
    <br />
    <small>
    <strong>Submitter: </strong>&nbsp;{$theme.author|escape:'html'}<br />
    <strong>Submitted: </strong>&nbsp;{$theme.timestamp|escape:'html'}<br />
    <strong>Downloaded {$theme.downloadcnt|escape:'html'} time{if $theme.downloadcnt != 1}s{/if}</strong><br />
    <strong>Description:</strong><br />  
    &nbsp;{$theme.description|escape:'html'}<br />
    {if $theme.current_pass}
    <strong>Works with <span class="build_info" title="{$theme.current_version}">current build</span></strong><br />
    {else}
    <strong class="broken_build">Doesn't work with <span class="build_info" title="{$theme.current_version}">current build</span></strong><br />
    {/if}
    {if $theme.release_pass}
    <strong>Works with release {$theme.release_version}</strong><br />
    {/if}
    </small>
    <form method="POST" action="{$smarty.server.SCRIPT_NAME}?themeid={$theme.id}&amp;target={$smarty.request.target}">
        <input type="hidden" name="ratetheme" value={$theme.id} />
        <select name=rating>
            <option value='10'>10 - Top</option>
            <option value='9'>9</option>
            <option value='8'>8</option>
            <option value='7'>7</option>
            <option value='6'>6</option>
            <option value='5'>5- Medium</option>
            <option value='4'>4</option>
            <option value='3'>3</option>
            <option value='2'>2</option>
            <option value='1'>1</option>
            <option value='0'>0 - Flop</option>
        </select>
        <input type="submit" value="Rate" />
    </form>
    </td>
  </tr>
</table>
{include file="footer.tpl"}
