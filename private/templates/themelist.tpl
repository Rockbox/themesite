{assign var="self" value="Themes for $mainlcd"}
{assign var="parent" value="index.php|Frontpage"}
{include file="header.tpl" title=$self rss="rss.php?target=`$smarty.request.target`" rsstitle="Themes for `$smarty.request.target`"}

<h1>Themes for {$mainlcd}</h1>
{include file="breadcrumbs.tpl"}

{if count($themes) == 0}
<p>No themes uploaded yet for this screen size</p>
{else}

{assign var="cols" value=#themecolumns#}
{* Maybe themecolumns should be decided by the lcd width? *}

<table class="rockbox">
  {section name=tr loop=$themes step=$cols}
  {* First print a row with theme names *}
  <tr>
    {section name=td start=$smarty.section.tr.index
loop=$smarty.section.tr.index+$cols}
    {if $themes[td]}
    <th align="center" width="110">{$themes[td].name|escape:'html'}</th>
    {/if}
    {/section}
  </tr>

  {* Then a row with "the rest" *}
  <tr valign="top">
    {section name=td start=$smarty.section.tr.index loop=$smarty.section.tr.index+$cols}
    {if $themes[td]}
    <td>
    <p align="center">
{html_image file="`$datadir`/`$mainlcd`/`$themes[td].shortname`/`$themes[td].sshot_wps`" href="`$datadir`/`$mainlcd`/`$themes[td].shortname`/`$themes[td].zipfile`"}
    <small>Size: {$themes[td].size|siprefix}B</small>
    </p>
    <small>
    <strong>Submitter:</strong><br />  
    &nbsp;{$themes[td].author|escape:'html'}<br />
    <strong>Description:</strong><br />  
    &nbsp;{$themes[td].description|escape:'html'}<br />
    {if $themes[td].current_pass}
    <strong>Works with <span title="{$themes[td].current_version}">current build</span></strong><br />
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
{include file="footer.tpl"}
