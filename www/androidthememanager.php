<?php
/***************************************************************************
 *             __________               __   ___.
 *   Open      \______   \ ____   ____ |  | _\_ |__   _______  ___
 *   Source     |       _//  _ \_/ ___\|  |/ /| __ \ /  _ \  \/  /
 *   Jukebox    |    |   (  <_> )  \___|    < | \_\ (  <_> > <  <
 *   Firmware   |____|_  /\____/ \___  >__|_ \|___  /\____/__/\_ \
 *                     \/            \/     \/    \/            \/
 * $Id$
 *
 * Copyright (C) 2011 Maurus Cuelenaere
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This software is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY
 * KIND, either express or implied.
 *
 ****************************************************************************/

require_once('preconfig.inc.php');

function die_json($str) {
    die(json_encode(array("error" => $str)));
}

header('Content-type: application/json');

if (!isset($_GET['resolution']))
    die_json("Invalid URL");

$themes = $site->listthemesbyresolution($_GET['resolution']);

if (count($themes) == 0)
    die_json("No themes available for that resolution");

$output = array();
foreach ($themes as $theme) {
    $ret = array(
        "name" => $theme['name'],
        "author" => $theme['author'],
        "date" => $theme['timestamp'],
        "description" => $theme['description'],
        "filesize" => $theme['size'],
        "images" => array(),
        "link" => sprintf("%s/download.php?themeid=%d", config::path, $theme['id']),
        "pass_release" => $theme['release_pass'],
        "pass_current" => $theme['current_pass'],
    );
    foreach (array($theme['sshot_wps'], $theme['sshot_menu'], $theme['sshot_1'], $theme['sshot_2'], $theme['sshot_3']) as $image) {
        if (empty($image))
            continue;

        $ret["images"][] = sprintf("%s/%s/%s/%s/%s", config::path, config::datadir, $theme['mainlcd'], $theme['shortname'], $image);
    }

    $output[] = $ret;
}

echo json_encode($output);
?>