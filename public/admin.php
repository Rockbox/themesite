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
session_start();

function changestatuses(&$site) {
    foreach($_REQUEST['prevstatus'] as $id => $prevstatus) {
        $newstatus = $_REQUEST['status'][$id];
        $oldstatus = $_REQUEST['prevstatus'][$id];
        $reason    = $_REQUEST['reason'][$id];
        if ($oldstatus != $newstatus) {
            $site->changestatus($id, $newstatus, $oldstatus, $reason);
        }
    }
}

/* First, check if the user is logged in and handle logins */
if (isset($_REQUEST['logout'])) {
    unset($_SESSION['user']);
    $t->assign('msg', 'Logged out.');
}
if (isset($_REQUEST['user'])) {
    if ($site->adminlogin($_REQUEST['user'], $_REQUEST['pass'])) {
        $_SESSION['user'] = $_REQUEST['user'];
    }
    else {
        $t->assign('msg', 'Login failed. Please try again');
    }
}
/* If not logged in, show the login form */
if (!isset($_SESSION['user'])) {
    $template = 'login.tpl';
}
else {
    /* Else, we need to figure out what to do then */
    if (isset($_REQUEST['target'])) {
        if (isset($_REQUEST['changestatuses'])) {
            changestatuses($site);
        }
        $approved = isset($_REQUEST['approved']) ? $_REQUEST['approved'] : 'any';
        $template = 'adminlist.tpl';
        $lcd = $site->target2lcd($_REQUEST['target']);
        $themes = $site->listthemes($lcd['mainlcd'], 'timestamp DESC',$approved, $onlyverified = false);
        $t->assign('mainlcd', $lcd['mainlcd']);
        $t->assign('themes', $themes);
        $t->assign('approved', $approved);
        $t->assign('target', $site->target2fullname($_REQUEST['target']));
    }
    /* Show a theme's details, possibly updating it as result of an admin
     * submitting changes */
    elseif (isset($_REQUEST['edittheme'])) {
        /* Update the theme */
        if (isset($_REQUEST['themename'])) {
            $site->updatetheme(
                $_REQUEST['edittheme'],
                $_REQUEST['themename'],
                $_REQUEST['mainlcd'],
                $_REQUEST['author'],
                $_REQUEST['email'],
                $_REQUEST['description']
            );
        }
        $theme = $site->themedetails($_REQUEST['edittheme']);
        $targets = array();
        foreach($site->listtargets() as $target) {
            $targets[$target['shortname']] = $target['fullname'];
        }
        $t->assign('targets', $targets);
        $t->assign('theme', $theme);
        $template = 'edittheme.tpl';
    }
    /* Adding a target */
    elseif (isset($_REQUEST['addtarget'])) {
        $site->addtarget(
            $_REQUEST['shortname'],
            $_REQUEST['fullname'],
            $_REQUEST['mainlcd'],
            $_REQUEST['pic'],
            $_REQUEST['depth'],
            empty($_REQUEST['remotelcd']) ? false : $_REQUEST['remotelcd']
        );
        $t->assign('adminmsg', 'Target added');
    }
    /* Run checkwps on all themes */
    elseif (isset($_REQUEST['runcheckwps'])) {
        $results = $site->checkallthemes();
        $template = 'checkthemes.tpl';
        $t->assign('checkwpsresults', $results);
    }
    /* Or just show the front page */
    if (!isset($template)) {
        $t->assign('title', 'Admin');
        $t->assign('targets', $site->listtargets());
        $t->assign('admin', true);
        $template = 'frontpage.tpl';
    }
}
$t->render($template);
?>
