{include file="header.tpl" title="Verify email"}
<h1>Email verification</h1>
{if $result > 0}
<p>You have succesfully verified your email address. Thank you.</p>
{else}
<p>An error occured. Please recheck the URL you followed.</p>
{/if}
{include file="footer.tpl"}
