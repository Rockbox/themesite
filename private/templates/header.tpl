<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<link rel="STYLESHEET" type="text/css" href="{$root}/rbthemes.css">
<link rel="STYLESHEET" type="text/css" href="http://www.rockbox.org/style.css">
<link rel="shortcut icon" href="http://www.rockbox.org/favicon.ico">
{if $rss}
<link href="{$rss}" type="application/rss+xml" rel="alternate" title="{$rsstitle}" />
{/if}
<title>Rockbox Themes - {$title}</title>
<meta name="author" content="Rockbox Contributors">
{literal}
<script type="text/javascript">
function fsstrip() {
    var expr = /[0-9]+/;
    document.fsform.taskid.value = expr.exec(document.fsform.taskid.value);
    return true;
}
</script>

<style type="text/css">
td.error {
    font-weight: bold;
    color: red;
}
a img {
    border: 0px none;
}
</style>
{/literal}
</head>
<body>
<table border=0 cellpadding=7 cellspacing=0>
<tr valign="top">
{include file="menu.tpl"}
<td>
