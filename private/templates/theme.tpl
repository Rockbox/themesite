{assign var="themename" value=$theme.name}
{assign var="self" value="$themename Theme"}
{if $target}
    {assign var="parent" value="index.php?target=`$smarty.request.target`|Themes for `$target`"}
    {assign var="grandparent" value="index.php|Frontpage"}
    {include file="header.tpl" title=$self rss="rss.php?target=`$smarty.request.target`&amp;themeid=`$theme.id` " rsstitle="`$smarty.request.theme.shortname` theme for `$smarty.request.target`"}
{else}
    {assign var="parent" value="index.php?allthemes|All themes"}
    {assign var="grandparent" value="index.php|Frontpage"}
    {include file="header.tpl" title=$self rss="rss.php?themeid=`$theme.id` " rsstitle="`$smarty.request.theme.shortname` theme"}
{/if}

<h1>{$self}</h1>
{include file="breadcrumbs.tpl"}

{if $msg}<p>{$msg}</p>{/if}

<p><b>Note that this theme may require the optional Rockbox font pack installed.  The latest version can always be found <a href="//www.rockbox.org/daily.shtml">here</a></b></p>

<table class="rockbox">
  {* First print a row with theme name *}
  <tr>
    <th align="center" ><a href="index.php?themeid={$theme.id}{if $target}&amp;target={$smarty.request.target}{/if}">{$theme.name|escape:'html'}</th>
  </tr>

  {* Then a row with "the rest" *}
  <tr valign="top">
    <td>
    <p align="center">
    {html_image file="`$datadir`/`$theme.mainlcd`/`$theme.shortname`/`$theme.sshot_wps`" href="download.php?themeid=`$theme.id`" alt="Download" title="Download"}
    {if $theme.sshot_menu != "" }{html_image file="`$datadir`/`$theme.mainlcd`/`$theme.shortname`/`$theme.sshot_menu`" href="download.php?themeid=`$theme.id`" alt="Download" title="Download"} {/if}
    {if $theme.sshot_1 != "" && $theme.sshot_1 != "1-"}{html_image file="`$datadir`/`$theme.mainlcd`/`$theme.shortname`/`$theme.sshot_1`" href="download.php?themeid=`$theme.id`" alt="Download" title="Download"} {/if}
    {if $theme.sshot_2 != "" && $theme.sshot_2 != "2-"}{html_image file="`$datadir`/`$theme.mainlcd`/`$theme.shortname`/`$theme.sshot_2`" href="download.php?themeid=`$theme.id`" alt="Download" title="Download"} {/if}
    {if $theme.sshot_3 != "" && $theme.sshot_3 != "3-"}{html_image file="`$datadir`/`$theme.mainlcd`/`$theme.shortname`/`$theme.sshot_3`" href="download.php?themeid=`$theme.id`" alt="Download" title="Download"} {/if}
    <br />
    <a href="download.php?themeid={$theme.id}">Download</a>
    <br />
    <small>Size: {$theme.size|siprefix}B</small>
    </p> <p align="center">
    <strong>Rating:</strong> &nbsp;
    {section name=i loop=10 step=2}
        {if $smarty.section.i.iteration*2 <= $theme.ratings}
           <img src="filled.png" style="width:15px; height:15px;" />
        {elseif ($smarty.section.i.iteration*2)-1 <= $theme.ratings}
            <img src="half.png" style="width:15px; height:15px;" />
        {else}
            <img src="empty.png" style="width:15px; height:15px;" />
        {/if}
    {/section} 
    {$theme.numratings} vote{if $theme.numratings !=1}s.{/if} </p>
    <form method="POST" action="{$smarty.server.SCRIPT_NAME}?themeid={$theme.id}{if $target}&amp;target={$smarty.request.target}{/if}">
        <input type="hidden" name="ratetheme" value={$theme.id} />
        <p align="center">
        <select name=rating>
            <option value='10'>10 - Top</option>
            <option value='9'>9</option>
            <option value='8'>8</option>
            <option value='7'>7</option>
            <option value='6'>6</option>
            <option value='5'>5- Medium</option>
            <option value='4'>4</option>
            <option value='3'>3</option>
            <option value='2'>2</option>
            <option value='1'>1</option>
            <option value='0'>0 - Flop</option>
        </select>
        <input type="submit" value="Rate" />
        </p>
    </form>
    <p align="center">
    <small>
    <strong>Submitter: </strong>&nbsp;{$theme.author|escape:'html'}<br />
    <strong>Submitted: </strong>&nbsp;{$theme.timestamp|escape:'html'}<br />
    <strong>Downloaded {$theme.downloadcnt|escape:'html'} time{if $theme.downloadcnt != 1}s{/if}</strong><br />
    {if !$target}
    <strong>Designed for LCD size: </strong>&nbsp;{$theme.mainlcd|escape:'html'}<br />
    {if $theme.remotelcd} <strong>Designed for remote LCD size: </strong>&nbsp;{$theme.remotelcd|escape:'html'}<br /> {/if}
    {/if}
    <strong>Description:</strong><br />  
    &nbsp;{$theme.description|escape:'html'}<br />
    {if $theme.current_pass}
    <strong>Works with <span class="build_info" title="{$theme.current_version}">current dev build</span></strong><br />
    {else}
    <strong class="broken_build">Doesn't work with <span class="build_info" title="{$theme.current_version} - {$theme.checkwps_output}">current build</span></strong><br />
    {/if}
    {if $theme.release_pass}
    <strong>Works with release {$theme.release_version}</strong><br />
    {/if}
    </small> 
    </p>
    </td>
    <tr>
        <th>Zip contents</th>
    </tr>
    <tr>
        <td><pre>
{section name=i loop=$theme.files}
{$theme.files[i]}
{/section} </pre></td>
    </tr>
    <tr>
    <th>Report</th>
    </tr>
    <td>
    <div>
    If this theme violates any copyright law, you can report it to the admins.<br/>
    Please describe the problem, as detailed as possible, in the input box.<br/>

    If the theme does not work, please discuss the issue on the forums instead.<br/>
    If the theme causes rockbox to crash or hang, please report a bug instead.<br/>
    </div>
    <form method="POST" action="{$smarty.server.SCRIPT_NAME}?themeid={$theme.id}{if $target}&amp;target={$smarty.request.target}{/if}">
        <input type="hidden" name="reporttheme" value={$theme.id} />
        <textarea cols="45" rows="4" name="reason"></textarea> <br />
        <script type="text/javascript"
            src="http://api.recaptcha.net/challenge?k={$recaptchakey}">
        </script>
        <noscript>
            <iframe src="http://api.recaptcha.net/noscript?k={$recaptchakey}"
                height="300" width="500" frameborder="0"></iframe><br>
            <textarea name="recaptcha_challenge_field" rows="3" cols="40">
            </textarea>
            <input type="hidden" name="recaptcha_response_field" 
                value="manual_challenge">
        </noscript>
        <input type="submit" value="Report" />
    </form>
    </td>
  </tr>
</table>
{include file="footer.tpl"}
