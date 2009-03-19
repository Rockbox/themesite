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

/* Decide what to do */
if (isset($_REQUEST['target']) && isset($_REQUEST['themename'])) {
    $site->showtheme($_REQUEST['target'], $_REQUEST['themename']);
}
elseif (isset($_REQUEST['target'])) {
    $values['themes'] = $site->listthemes($_REQUEST['target']);
    $lcd = $site->target2lcd($_REQUEST['target']);
    $t->assign('mainlcd', $lcd['mainlcd']);
    $template = 'themelist.tpl';
}
elseif (isset($_REQUEST['theme'])) {
    $site->showtheme($_REQUEST['theme']);
}
else {
    $values['targets'] = $site->listtargets();
    $template = 'frontpage.tpl';
}
$t->render($template, $values);
?>