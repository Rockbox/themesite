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
/* update a rating ? */
if (isset($_REQUEST['ratetheme'])) {
    $site->ratetheme($_REQUEST['ratetheme'],$_REQUEST['rating']);
}

/* show more details about a theme */
if (isset($_REQUEST['target']) && isset($_REQUEST['themeid'])) {
    $t->assign('target', $site->target2fullname($_REQUEST['target']));
    $t->assign('theme',$site->themedetails($_REQUEST['themeid'],true,true));
    $template = 'theme.tpl';
}
/* Show all themes for a specific target */
elseif (isset($_REQUEST['target'])) {
    $lcd = $site->target2lcd($_REQUEST['target']);
    if(isset($_REQUEST['order'])) $values['themes'] = $site->listthemes($_REQUEST['target'],$_REQUEST['orderby']);
    else $values['themes'] = $site->listthemes($_REQUEST['target']);
    $t->assign('sortings',array('timestamp ASC' => 'Submitted time - ascending',
                                'timestamp DESC' => 'Submitted time - descending',
                                'downloadcnt ASC' => 'Download count - ascending',
                                'downloadcnt DESC' => 'Download count - descending',
                                'ratings ASC' => 'Rating - ascending',
                                'ratings DESC' => 'Rating - descending',
                                'numratings ASC' => 'Number of Votes - ascending',
                                'numratings DESC' => 'Number of Votes - descending'));
    $t->assign('mainlcd', $lcd['mainlcd']);
    $t->assign('target', $site->target2fullname($_REQUEST['target']));
    $template = 'themelist.tpl';
}
/* Just show the frontpage */
else {
    $values['targets'] = $site->listtargets();
    $template = 'frontpage.tpl';
}

$t->render($template, $values);
?>
