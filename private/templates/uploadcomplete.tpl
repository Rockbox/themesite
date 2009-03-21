{assign var="self" value="Upload succesful"}
{assign var="grandparent" value="index.php|Frontpage"}
{assign var="parent" value="upload.php|Upload a theme"}
{include file="header.tpl" title=$self}

<h1>{$self}</h1>
{include file="breadcrumbs.tpl"}

<p>Your theme was succesfully uploaded. You will receive a mail with a
confirmation link that you need to visit before your theme will be
accepted.</p>
{include file="footer.tpl"}
