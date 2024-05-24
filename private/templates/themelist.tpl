{if $target}
{assign var="self" value="Themes for $target"}
{assign var="parent" value="index.php|Frontpage"}
{include file="header.tpl" title=$self rss="rss.php?target=`$smarty.request.target`" rsstitle="Themes for `$smarty.request.target`"}
{else}
{assign var="self" value="All themes"}
{assign var="parent" value="index.php|Frontpage"}
{include file="header.tpl" title=$self rss="rss.php" rsstitle="All themes"}
{/if}

<h1>{$self}</h1>
{include file="breadcrumbs.tpl"}

{if count($themes) == 0}
<p>No themes uploaded yet for this screen size</p>
{else}

{* Decide the number of columns by the lcd width *}
{if $mainlcd} {math assign="cols" equation="floor(min(10, x / y))" x=1000 y=$mainlcd|regex_replace:'/x.*/':''} {/if}
{assign var="cols" value="3"}

{* let the user select order *}
{if $target}
<form method="POST" action="{$smarty.server.SCRIPT_NAME}?target={$smarty.request.target}">
{else}
<form method="POST" action="{$smarty.server.SCRIPT_NAME}?allthemes">
{/if}        
        <input type="hidden" name="order" value="yes" />
        Ordered by: {html_options name=orderby options=$sortings selected=$smarty.request.orderby} 
        {html_options name=direction options=$directions selected=$smarty.request.direction}
        <input type="submit" value="Go" />
</form>
<table class="rockbox">
  {section name=tr loop=$themes step=$cols}
  {* First print a row with theme names *}
  <tr>
    {section name=td start=$smarty.section.tr.index loop=$smarty.section.tr.index+$cols}
    {if $themes[td]}
    <th align="center" width="320"><a href="index.php?themeid={$themes[td].id}{if $target}&amp;target={$smarty.request.target}{/if}">{$themes[td].name|escape:'html'}</a></th>
    {/if}
    {/section}
  </tr>

  {* Then a row with "the rest" *}
  <tr valign="top">
    {section name=td start=$smarty.section.tr.index loop=$smarty.section.tr.index+$cols}
    {if $themes[td]}
    <td>
    <p align="center">
    {assign var="path" value="`$datadir`/`$themes[td].mainlcd`/`$themes[td].shortname`/"}
    {if $target}{assign var="url" value="index.php?themeid=`$themes[td].id`&amp;target=`$smarty.request.target`"}
    {else} {assign var="url" value="index.php?themeid=`$themes[td].id`"}
    {/if}
    {html_image file="`$datadir`/`$themes[td].mainlcd`/`$themes[td].shortname`/`$themes[td].sshot_wps`" href=$url 
                        path=$path oversrc=$themes[td].sshot_menu oversrc1=$themes[td].sshot_1 oversrc2=$themes[td].sshot_2 oversrc3=$themes[td].sshot_3
                        alt="Theme details" title="Theme details"}<br />
    <small><a href="download.php?themeid={$themes[td].id}">Download</a> Size: {$themes[td].size|siprefix}B </small>
    </p>
    <strong>Rating:</strong> &nbsp;
    {section name=i loop=10 step=2}
        {if $smarty.section.i.iteration*2 <= $themes[td].ratings}
           <img src="filled.png" style="width:15px; height:15px;" />
        {elseif ($smarty.section.i.iteration*2)-1 <= $themes[td].ratings}
            <img src="half.png" style="width:15px; height:15px;" />
        {else}
            <img src="empty.png" style="width:15px; height:15px;" />
        {/if}
    {/section} 
    {$themes[td].numratings} vote{if $themes[td].numratings !=1}s.{/if}
    <br />
    <small>
    <strong>Submitter:</strong> &nbsp;{$themes[td].author|escape:'html'}<br />
    <strong>Submitted:</strong>  &nbsp;{$themes[td].timestamp|escape:'html'}<br />
    <strong>Downloaded {$themes[td].downloadcnt|escape:'html'} time{if $themes[td].downloadcnt != 1}s{/if}</strong><br />
    {if !$target}
    <strong>Designed for LCD size: </strong>&nbsp;{$themes[td].mainlcd|escape:'html'}<br />
    {if $themes[td].remotelcd} <strong>Designed for remote LCD size: </strong>&nbsp;{$themes[td].remotelcd|escape:'html'}<br /> {/if}
    {/if}
    <strong>Description:</strong><br />  
    &nbsp;{$themes[td].description|escape:'html'}<br />
    {if $themes[td].current_pass}
    <strong>Works with <span class="build_info" title="{$themes[td].current_version}">current dev build</span></strong><br />
    {else}
    <strong class="broken_build">Doesn't work with <span class="build_info" title="{$themes[td].current_version} - {$themes[td].checkwps_output}">current build</span></strong><br />
    {/if}
    {if $themes[td].release_pass}
    <strong>Works with release {$themes[td].release_version}</strong><br />
    {/if}
    </small>
    </td>
    {/if}
    {/section}
  </tr>
  {/section}
</table>


{/if}

<h2>Upload your own theme</h2> <p>Have you made a theme that is not listed
here? Please read <a
href="//www.rockbox.org/wiki/ThemeGuidelines">the theme
guidelines</a> and then 
{if $target}
<a href="upload.php?target={$smarty.request.target}">
{else}
<a href="upload.php">
{/if}
upload your theme</a>.</p> 
{include file="footer.tpl"}
