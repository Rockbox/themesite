{assign var="self" value="Themes for $target"}
{assign var="parent" value="index.php|Frontpage"}
{include file="header.tpl" title=$self rss="rss.php?target=`$smarty.request.target`" rsstitle="Themes for `$smarty.request.target`"}

<h1>{$self}</h1>
{include file="breadcrumbs.tpl"}

{if count($themes) == 0}
<p>No themes uploaded yet for this screen size</p>
{else}

{* Decide the number of columns by the lcd width *}
{math assign="cols" equation="floor(min(10, x / y))" x=1000 y=$mainlcd|regex_replace:'/x.*/':''}
{assign var="cols" value="3"}

<table class="rockbox">
  {section name=tr loop=$themes step=$cols}
  {* First print a row with theme names *}
  <tr>
    {section name=td start=$smarty.section.tr.index
loop=$smarty.section.tr.index+$cols}
    {if $themes[td]}
    <th align="center" width="320"><a href="index.php?themeid={$themes[td].id}&amp;target={$smarty.request.target}">{$themes[td].name|escape:'html'}</a></th>
    {/if}
    {/section}
  </tr>

  {* Then a row with "the rest" *}
  <tr valign="top">
    {section name=td start=$smarty.section.tr.index loop=$smarty.section.tr.index+$cols}
    {if $themes[td]}
    <td>
    <p align="center">
    {if $themes[td].sshot_menu != ""}
        {assign var="oversrc" value="`$datadir`/`$themes[td].mainlcd`/`$themes[td].shortname`/`$themes[td].sshot_menu`"}
    {else}
        {assign var="oversrc" value=""}
    {/if}
    {html_image file="`$datadir`/`$themes[td].mainlcd`/`$themes[td].shortname`/`$themes[td].sshot_wps`" href="`$datadir`/`$themes[td].mainlcd`/`$themes[td].shortname`/`$themes[td].zipfile`" oversrc=$oversrc}<br />
    <small>Size: {$themes[td].size|siprefix}B</small>
    </p>
    <small>
    <strong>Submitter:</strong><br />  
    &nbsp;{$themes[td].author|escape:'html'}<br />
    <strong>Submited:</strong><br />  
    &nbsp;{$themes[td].timestamp|escape:'html'}<br />
    <strong>Description:</strong><br />  
    &nbsp;{$themes[td].description|escape:'html'}<br />
    {if $themes[td].current_pass}
    <strong>Works with <span class="build_info" title="{$themes[td].current_version}">current build</span></strong><br />
    {else}
    <strong class="broken_build">Doesn't work with <span class="build_info" title="{$themes[td].current_version}">current build</span></strong><br />
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
href="http://www.rockbox.org/wiki/ThemeGuidelines">the theme
guidelines</a> and then <a
href="upload.php?target={$smarty.request.target}">upload your theme</a>.</p> 

{include file="footer.tpl"}
