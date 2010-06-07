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
 * Copyright (C) 2009 Jonas Häggqvist
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

/* workaround to make the script redirect to the old theme site copy for old
 * Rockbox versions. To be removed once the theme site can handle multiple
 * theme syntax versions itself.
 */
$revision_syntax_changed = 26641;
$old_script = "/oldsite/www/rbutilqt.php";
if(array_key_exists("revision", $_REQUEST)) {
    if($_REQUEST['revision'] > 0 && $_REQUEST['revision'] < $revision_syntax_changed) {
        $target = "http://" . $_SERVER['SERVER_NAME'] . $old_script;
        header("Location: " . $target);
        exit(0);
    }
}
if(array_key_exists("release", $_REQUEST)) {
    list($major, $minor, $micro) = explode('.', $_REQUEST['release']);
    if($major > 0 && $major <= 3 && $minor <= 6) {
        $target = "http://" . $_SERVER['SERVER_NAME'] . $old_script;
        header("Location: " . $target);
        exit(0);
    }
}
else {
    $target = "http://" . $_SERVER['SERVER_NAME'] . $old_script;
    header("Location: " . $target);
    exit(0);
}

header('Content-type: text/plain');

$themes = array();
if (!isset($_REQUEST['target'])) {
    $t->assign('errno', 1);
    $t->assign('errmsg', "Invalid URL");
}
else {
    $themes = $site->listthemes($_REQUEST['target']);
}

if (count($themes) == 0) {
    $t->assign('errno', 1);
    $t->assign('errmsg', "No themes available for the selected target");
}
else {
    $t->assign('themes', $themes);
    /* Not sure what kind of error message we would want to send? */
    $t->assign('errno', 0);
    $t->assign('errmsg', 'Rocking da boxes');
}

$t->render('rbutil.tpl');
?>
