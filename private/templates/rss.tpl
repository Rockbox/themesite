<?xml version="1.0"?>
<rss version="2.0">
    <channel>
{if $smarty.request.target}
        <title>Rockbox themes for {$smarty.request.target}</title>
{else}
        <title>Rockbox themes for all targets</title>
{/if}
        <link>{$hostname}/?target=</link>
{section name=i loop=$themes max=10}
    {assign var="picture" value="`$hostname`/`$root`/`$datadir`/`$themes[i].mainlcd`/`$themes[i].shortname`/`$themes[i].sshot_wps`"}
        <item>
            <title>{$themes[i].name} by {$themes[i].author}{if not $smarty.request.target} for {$themes[i].mainlcd} screens{/if}</title>
            <link>{$hostname}/{$datadir}/{$themes[i].mainlcd}/{$themes[i].shortname}/{$themes[i].zipfile}</link>
            <description>
                {"<img src='$picture' />"|escape:'html'}
                {$themes[i].description}
            </description>
            <pubDate>{$themes[i].timestamp|date_format:"%a, %d %B %Y %H:%M:%S GMT"}</pubDate>
        </item>
{/section}
    </channel>
</rss>
