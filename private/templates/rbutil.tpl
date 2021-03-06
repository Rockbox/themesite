{if ! $smarty.request.target}
[error]
code=1
description="Please update Rockbox Utility to version 1.2.1 or later, which fixes a bug with theme handling. Theme installation will not work with this version."
{else}
[error]
code={$errno}
{if $errno neq 0}
description="{$errmsg}"
{/if}
{/if}

[status]
msg=""

{section name=i loop=$themes}
[{$themes[i].shortname}]
name="{$themes[i].name|escape:'html'}"
size={$themes[i].size}
descriptionfile=""
image="{$root}/{$datadir}/{$themes[i].mainlcd}/{$themes[i].shortname}/{$themes[i].sshot_wps}"
{if $themes[i].sshot_menu neq ""}
image2="{$root}/{$datadir}/{$themes[i].mainlcd}/{$themes[i].shortname}/{$themes[i].sshot_menu}"
{/if}
archive="{$root}/download.php?themeid={$themes[i].id}"
author="{$themes[i].author|escape:'html'}"
version="{$themes[i].timestamp}"
about="{$themes[i].description|escape:'html'}"
{if $themes[i].current_pass}
pass_release="{$themes[i].release_version}"
{/if}
{if $themes[i].current_pass}
pass_current="{$themes[i].current_version}"
{/if}

{/section}
