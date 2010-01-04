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

if (isset($_REQUEST['reporttheme'])) {
    $site->changestatus($_REQUEST['reporttheme'], 2, 1, $_REQUEST['reason']);
}

/* show more details about a theme */
if (isset($_REQUEST['themeid'])) {
    if (isset($_REQUEST['target'])) {
        $t->assign('target', $site->target2fullname($_REQUEST['target']));
    }
    $t->assign('theme',$site->themedetails($_REQUEST['themeid'],true,true));
    $template = 'theme.tpl';
}
/* Show all themes for a specific target */
elseif (isset($_REQUEST['target'])) {
    $lcd = $site->target2lcd($_REQUEST['target']);
    if(isset($_REQUEST['order'])) $values['themes'] = $site->listthemes($_REQUEST['target'],sprintf("%s %s",$_REQUEST['orderby'],$_REQUEST['direction']));
    else $values['themes'] = $site->listthemes($_REQUEST['target']);
    $t->assign('sortings',array('timestamp' => 'Submitted time',
                                'downloadcnt' => 'Download count',
                                'ratings/numratings' => 'Rating',
                                'numratings' => 'Number of Votes',
                                'name' => 'Themename',
                                'author' => 'Author'));
    $t->assign('directions',array('DESC' => 'descending','ASC' => 'ascending'));                            
    $t->assign('mainlcd', $lcd['mainlcd']);
    $t->assign('target', $site->target2fullname($_REQUEST['target']));
    $template = 'themelist.tpl';
}
/* Show all themes */
elseif (isset($_REQUEST['allthemes'])) {
    if(isset($_REQUEST['order'])) $values['themes'] = $site->listthemes(false,sprintf("%s %s",$_REQUEST['orderby'],$_REQUEST['direction']));
    else $values['themes'] = $site->listthemes(false);
    $t->assign('sortings',array('timestamp' => 'Submitted time',
                                'downloadcnt' => 'Download count',
                                'ratings/numratings' => 'Rating',
                                'numratings' => 'Number of Votes',
                                'name' => 'Themename',
                                'author' => 'Author',
                                'mainlcd' => 'LCD size',
                                'remotelcd' => 'Remote LCD size'));
    $t->assign('directions',array('DESC' => 'descending','ASC' => 'ascending'));                            
    $template = 'themelist.tpl';
}
/* Just show the frontpage */
else {
    $values['targets'] = $site->listtargets();
    $t->assign('adminworkneeded',$site->adminworkneeded());
    $template = 'frontpage.tpl';
}

$t->render($template, $values);
?>
