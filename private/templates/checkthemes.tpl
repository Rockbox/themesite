{include file="header.tpl" title="Check themes"}

<h1>Checking all themes</h1>

{section name=i loop=$checkwpsresults}
{if $checkwpsresults[i].summary.pass}
    <tt style="color: green">pass</tt>
{else}
    <tt style="color: red">fail</tt>
{/if}

&mdash; {$checkwpsresults[i].theme.mainlcd}/{$checkwpsresults[i].theme.shortname}/{$checkwpsresults[i].theme.zipfile}<br />
{/section}

{include file="footer.tpl"}
