{include file="header.tpl" title="Themes for $mainlcd" rss="rss.php?target=`$smarty.request.target`" rsstitle="Themes for `$smarty.request.target`"}

<h1>Themes for {$mainlcd}</h1>

{if count($themes) == 0}
<p>No themes uploaded yet for this screen size</p>
{else}
<ul>
{section name=mysec loop=$themes}
<li>
{html_image file="`$datadir`/`$mainlcd`/`$themes[mysec].shortname`/`$themes[mysec].sshot_wps`" href="`$datadir`/`$mainlcd`/`$themes[mysec].shortname`/`$themes[mysec].zipfile`"}
{$themes[mysec].name}
</li>
{/section}
</ul>
{/if}
{include file="footer.tpl"}

