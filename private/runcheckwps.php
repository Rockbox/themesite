#!/usr/bin/env php
<?php
$_SERVER['DOCUMENT_ROOT'] = "/home/themes/www";
class preconfig {
    // The path to the private dir. Might be relative or absolute. Should
    // NOT be accessible through the webserver.
    const privpath = "./";
}

require_once('preamble.inc.php');
$results = $site->checkallthemes();
?>
