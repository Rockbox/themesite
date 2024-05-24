#!/usr/bin/env php
<?php
$_SERVER['DOCUMENT_ROOT'] = "/home/rockbox/themes/www";
class preconfig {
    // The path to the private dir. Might be relative or absolute. Should
    // NOT be accessible through the webserver.
    const privpath = "/home/rockbox/themes/private/";
}

require_once('preamble.inc.php');
$results = $site->checkallthemes(0, 1);  // change the second '0' to '1' to check release too
?>
