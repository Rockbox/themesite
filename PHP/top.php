<? require_once('config.php'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<link rel="stylesheet" type="text/css" href="http://www.rockbox.org/style.css">
<link rel="stylesheet" type="text/css" href="<?=SITEURL?>/theme_site_style.css">
<link rel="shortcut icon" href="http://www.rockbox.org/favicon.ico">
<title>Rockbox Themes</title>
<meta name="author" content="Rockbox Contributors">
<script type="text/javascript">
<!--
function fsstrip() {
    var expr = /[0-9]+/;
    document.fsform.taskid.value = expr.exec(document.fsform.taskid.value);
    return true;
}
function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}
//-->
</script>
</head>
<body>
<table border=0 cellpadding=7 cellspacing=0>
<tr valign="top">
<td bgcolor="#6887bb" valign="top">
<br>
<div align="center"><a href="http://www.rockbox.org/">

<img src="http://www.rockbox.org/rockbox100.png" width=99 height=30 border=0 alt="Rockbox.org home"></a>
</div>
<div style="margin-top:20px">
<div class="submenu">
Downloads
</div>
<img width=16 height=16 src='http://www.rockbox.org/silk_icons/package.png' align='top'> <a class="menulink" href="http://www.rockbox.org/download/">releases</a><br>
<img width=16 height=16 src='http://www.rockbox.org/silk_icons/bomb.png' align='top'> <a class="menulink" href="http://build.rockbox.org">current build</a><br>
<img width=16 height=16 src='http://www.rockbox.org/silk_icons/style.png' align='top'> <a class="menulink" href="http://www.rockbox.org/twiki/bin/view/Main/RockboxExtras">extras</a><br>
<img width=16 height=16 src='http://www.rockbox.org/silk_icons/palette.png' align='top'> <a class="menulink" href="<?=SITEURL?>/index.php">themes</a>
<div class="submenu">
Documentation

</div>
<img width=16 height=16 src='http://www.rockbox.org/silk_icons/page_white_acrobat.png' align='top'> <a class="menulink" href="http://www.rockbox.org/manual.shtml">manual</a><br>
<img width=16 height=16 src='http://www.rockbox.org/silk_icons/application_edit.png' align='top'> <a class="menulink" href="http://www.rockbox.org/twiki/">wiki</a><br>
<img width=16 height=16 src='http://www.rockbox.org/silk_icons/book_open.png' align='top'> <a class="menulink" href="http://www.rockbox.org/twiki/bin/view/Main/DocsIndex">docs index</a>
<div class="submenu">
Support
</div>
<img width=16 height=16 src='http://www.rockbox.org/silk_icons/email.png' align='top'> <a class="menulink" href="http://www.rockbox.org/mail/">mailing lists</a><br>
<img width=16 height=16 src='http://www.rockbox.org/silk_icons/group.png' align='top'> <a class="menulink" href="http://www.rockbox.org/irc/">IRC</a><br>

<img width=16 height=16 src='http://www.rockbox.org/silk_icons/comment_edit.png' align='top'> <a class="menulink" href="http://forums.rockbox.org/">forums</a>
<div class="submenu">
Tracker
</div>
<img width=16 height=16 src='http://www.rockbox.org/silk_icons/bug.png' align='top'> <a class="menulink" href="http://www.rockbox.org/tracker/index.php?type=2">bugs</a><br>
<img width=16 height=16 src='http://www.rockbox.org/silk_icons/brick.png' align='top'> <a class="menulink" href="http://www.rockbox.org/tracker/index.php?type=4">patches</a><br>
<img width=16 height=16 src='http://www.rockbox.org/silk_icons/lightbulb.png' align='top'>&nbsp;<a class="menulink" href="http://www.rockbox.org/tracker/index.php?type=1">requests</a><br>
<div class="submenu">
Search
</div>

<form id="fsform" action="http://www.rockbox.org/tracker/index.php" method="get" onSubmit="return fsstrip();">
<input id="taskid" name="show_task" type="text" size="10" maxlength="10" accesskey="t"><br>
<input class="mainbutton" type="submit" value="Flyspray #">
</form>
<br>
<form action="http://www.google.com/search">
<input name=as_q size=10><br>
<input value="Search" type=submit>
<input type=hidden name=as_sitesearch value="www.rockbox.org">
</form>
<p><form action="https://www.paypal.com/cgi-bin/webscr" method="get">
<input type="hidden" name="cmd" value="_xclick">
<input type="hidden" name="business" value="bjorn@haxx.se">
<input type="hidden" name="item_name" value="Donation to the Rockbox project">
<input type="hidden" name="no_shipping" value="1">
<input type="hidden" name="cn" value="Note to the Rockbox team">
<input type="hidden" name="currency_code" value="USD">

<input type="hidden" name="tax" value="0">
<input type="image" src="http://www.rockbox.org/paypal-donate.gif" name="submit">
</form>
</div>
</td>
<td>
<!-- Start of main content -->
