{include file="header.tpl" title="Admin area"}
<h1>Bla</h1>
{if $smarty.get.show == 'all'}
<a href="{$smarty.server.SCRIPT_NAME}?show=approved">Show approved</a>
{else}
<a href="{$smarty.server.SCRIPT_NAME}?show=all">Show all</a>
{/if}

{include file="footer.tpl"}
