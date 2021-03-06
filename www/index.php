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
    /* check if there is a text */ 
    if($_REQUEST['reason'] != "") {
        /* check captcha */
        $resp = recaptcha_check_answer (config::recaptchakey_priv,
                                $_SERVER["REMOTE_ADDR"],
                                $_POST["recaptcha_challenge_field"],
                                $_POST["recaptcha_response_field"]);

        if ($resp->is_valid) {
            $site->changestatus($_REQUEST['reporttheme'], 2, 1, $_REQUEST['reason']);
            $t->assign('msg',"Theme successfully reported.");  
        }
        else   {
            $t->assign('msg',"Captcha failed ! Are you a bot ?");
        }
    }
    else {
        $t->assign('msg',"You need to provide a reason.");
    }
}

/* Bots gonna bot... */
if (isset($_REQUEST['target'])) {
    if (!(strstr($_REQUEST['target'], '/') === FALSE) ||
        !(strstr($_REQUEST['target'], '.') === FALSE)) {
         header('HTTP/1.1 400 Bad Request');
         exit;
    }
}

/* show more details about a theme */
if (isset($_REQUEST['themeid'])) {
    if (isset($_REQUEST['target'])) {
        $t->assign('target', $site->target2fullname($_REQUEST['target']));
    }
    // get the newest theme that is approved or the current theme
    $newest = $site->getNewestChildTheme($_REQUEST['themeid']);
    if($newest != $_REQUEST['themeid']){
        $target = isset($_REQUEST['target']) ? 'target=' . rawurlencode($_REQUEST['target']) . '&' : '';
        // send the user to the newst theme
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ?' . $target . 'themeid=' . $newest);
        exit;
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
                                'rating' => 'Rating',
                                'numratings' => 'Number of Votes',
                                'name' => 'Themename',
                                'author' => 'Author'));
    $t->assign('directions',array('DESC' => 'Descending','ASC' => 'Ascending'));
    $t->assign('mainlcd', $lcd['mainlcd']);
    $t->assign('target', $site->target2fullname($_REQUEST['target']));
    $template = 'themelist.tpl';
}
/* search for themes */
elseif (isset($_REQUEST['searchtheme'])) {
        $template = 'themelist.tpl';
        $themes = $site->searchthemes($_REQUEST['searchtype'],$_REQUEST['searchword'],false);
        $t->assign('themes', $themes);
    }
/* Show all themes */
elseif (isset($_REQUEST['allthemes'])) {
    if(isset($_REQUEST['order'])) $values['themes'] = $site->listthemes(false,sprintf("%s %s",$_REQUEST['orderby'],$_REQUEST['direction']));
    else $values['themes'] = $site->listthemes(false);
    $t->assign('sortings',array('timestamp' => 'Submitted time',
                                'downloadcnt' => 'Download count',
                                'rating' => 'Rating',
                                'numratings' => 'Number of Votes',
                                'name' => 'Themename',
                                'author' => 'Author',
                                'mainlcd' => 'LCD size',
                                'remotelcd' => 'Remote LCD size'));
    $t->assign('directions',array('DESC' => 'Descending','ASC' => 'Ascending'));
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
