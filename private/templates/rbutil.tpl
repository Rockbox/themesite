[error]
code={$errno}
description={$errmsg}

{section name=i loop=$themes}
[{$themes[i].shortname}]
name={$themes[i].name}
size={$themes[i].size}
descriptionfile=
image={$themes[i].sshot_wps}
image2={$themes[i].sshot_menu}
archive={$themes[i].zipfile}
author={$themes[i].author}
version={$themes[i].timestamp}
about={$themes[i].description}

{/section}
