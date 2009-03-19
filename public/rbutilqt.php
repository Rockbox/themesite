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
header('Content-type: text/plain');

$themes = array();
if (!isset($_REQUEST['res'])) {
    $t->assign('errno', 1);
    $t->assign('errmsg', "Invalid URL");
}
else {
    $mainlcd = substr($_REQUEST['res'], 0, strrpos($_REQUEST['res'], 'x'));
    $themes = $site->listthemes($mainlcd);
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
