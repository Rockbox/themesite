<?xml version="1.0"?>
<rss version="2.0">
    <channel>
        <title>Rockbox themes for {$smarty.request.target}</title>
        <link>{$hostname}/?target=</link>
{section name=i loop=$themes max=10}
    {assign var="picture" value="`$datadir`/`$themes[i].mainlcd`/`$themes[i].shortname`/`$themes[i].sshot_wps`"}
        <item>
            <title>{$themes[i].name}</title>
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
