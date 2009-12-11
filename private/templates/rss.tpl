<?xml version="1.0"?>
<rss version="2.0">
    <channel>
{if $smarty.request.target}
{if count($themes) == 1}
        <title>{$target} theme</title>
{else}
        <title>Rockbox themes for {$target}</title>    
{/if}        
{else}
        <title>Rockbox themes for all targets</title>
{/if}
        <link>{$hostname}/{if $smarty.request.target}?target={$smarty.request.target}{/if}</link>
{section name=i loop=$themes max=10}
    {assign var="picture" value="`$hostname`/`$root`/`$datadir`/`$themes[i].mainlcd`/`$themes[i].shortname`/`$themes[i].sshot_wps`"}
        <item>
            <title>{$themes[i].name|escape} by {$themes[i].author|escape:'html'}{if not $smarty.request.target} for {$themes[i].mainlcd} screens{/if}</title>
            <link>{$hostname}/{$datadir}/{$themes[i].mainlcd}/{$themes[i].shortname|escape:'html'}/{$themes[i].zipfile|escape:'html'}</link>
            <description>
                {"<img src='$picture' />"|escape:'html'}
                {$themes[i].description|escape:'html'}
            </description>
            <pubDate>{$themes[i].timestamp|date_format:"%a, %d %b %Y %H:%M:%S GMT"}</pubDate>
        </item>
{/section}
    </channel>
</rss>
