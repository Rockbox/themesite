<?php

# Change this to where your local install is storing the themes.txt file

define("SITEURL", "http://themes.rockbox.org/");
define("DATADIR", "/path/to/data/dir");
define("USERS", DATADIR."/ULTRA_SECRET_TXT_FILE_NAME_PROBABLY_HIDDEN_SOMEWHERE_ELSE.txt");
define("MAXFILESINZIP", 150);
define("MAXUNZIPPEDSIZE", 5*1024*1024);
define("MAXFILESIZE", 5*1024*1024); /* Don't forget to also change .htaccess */

?>
