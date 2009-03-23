[error]
code={$errno}
{if $errno neq 0}
description="{$errmsg}"
{/if}

[status]
msg="The theme site is in beta and still doesn't contain many themes. If you don't find a theme you like, please try again later."

{section name=i loop=$themes}
[{$themes[i].shortname}]
name="{$themes[i].name|escape:'html'}"
size={$themes[i].size}
size={math equation="x / 1024" x=$themes[i].size}
descriptionfile=""
image="{$root}{$datadir}/{$themes[i].mainlcd}/{$themes[i].shortname}/{$themes[i].sshot_wps}"
{if $themes[i].sshot_menu neq ""}
image2="{$root}{$datadir}/{$themes[i].mainlcd}/{$themes[i].shortname}/{$themes[i].sshot_menu}"
{/if}
archive="{$root}{$datadir}/{$themes[i].mainlcd}/{$themes[i].shortname}/{$themes[i].zipfile}"
author="{$themes[i].author|escape:'html'}"
version="{$themes[i].timestamp}"
about="{$themes[i].description|escape:'html'}"
pass_release="{$themes[i].release_version}"
pass_current="{$themes[i].current_version}"

{/section}
