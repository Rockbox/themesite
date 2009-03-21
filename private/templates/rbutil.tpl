[error]
code={$errno}
{if $errno neq 0}
description="{$errmsg}"
{/if}

{section name=i loop=$themes}
[{$themes[i].shortname}]
name="{$themes[i].name|escape:'html'}"
size={$themes[i].size}
descriptionfile=""
image="{$root}{$datadir}/{$themes[i].mainlcd}/{$themes[i].shortname}/{$themes[i].sshot_wps}"
{if $themes[i].sshot_menu neq ""}
image2="{$root}{$datadir}/{$themes[i].mainlcd}/{$themes[i].shortname}/{$themes[i].sshot_menu}"
{/if}
archive="{$root}{$datadir}/{$themes[i].mainlcd}/{$themes[i].shortname}/{$themes[i].zipfile}"
author="{$themes[i].author|escape:'html'}"
version="{$themes[i].timestamp}"
about="{$themes[i].description|escape:'html'}"

{/section}
