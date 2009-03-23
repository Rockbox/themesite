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

function checkuploadfields(&$site, &$err) {
    $lcd = $site->target2lcd($_REQUEST['target']);

    foreach($_REQUEST as $field => $value) {
        switch($field) {
        case 'author':
            if (strpos($value, ' ') === false) {
                $err[$field] = sprintf("This doesn't look like a proper full name (should contain at least one whitespace character): %s", $value);
            }
            break;
        case 'email':
            if (!preg_match("/.*@.*\..*/", $value)) {
                $err[$field] = sprintf("This doesn't look like an email I can reach: %s", $value);
            }
            break;
        case 'themename':
            if (trim($value) == '') {
                $err[$field] = "You need to provide a theme name";
            }
            elseif ($site->themenameexists($value, $lcd['mainlcd'])) {
                $err[$field] = sprintf("A theme with the name '%s' already exists for this target", $value);
            }
            break;
        case 'ccbysa':
            if ($value !== "on") {
                $err[$field] = "You need to accept to license your work under the Creative Commons Attribution Share Alike license to share your work here.";
            }
            break;
        case 'target':
        case 'description':
            /* These can't really be wrong */
            break;
        }
    }
    if (!isset($_REQUEST['ccbysa'])) {
        $err['ccbysa'] = "You need to accept to license your work under the Creative Commons Attribution Share Alike license to share your work here.";
    }
}

function checkuploadfiles(&$site, &$err) {
    $lcd = $site->target2lcd($_REQUEST['target']);
    $requiredfiles = array('themefile', 'sshot_wps');
    foreach($requiredfiles as $field) {
        if (!isset($_FILES[$field]) || empty($_FILES[$field]['tmp_name'])) {
            $err[$field] = array("You must provide this file");
        }
    }
    foreach($_FILES as $name => $values) {
        if (isset($err[$name]) || empty($_FILES[$field]['tmp_name'])) {
            if (!empty($values['name'])) {
                $err[$name][] = "Looks like upload failed. Was the file too large?";
            }
            continue;
        }
        $result = array();
        switch($name) {
            case 'themefile':
                $result = $site->validatezip($values);
                $test = $site->checkwps($values['tmp_name'], $lcd['mainlcd'], $lcd['remotelcd']);
                $pass = false;
                /* See if the wps passed at least one target/version combination */
                foreach($test as $version => $targets) {
                    foreach($targets as $target => $results) {
                        if ($results['pass']) {
                            $pass = true;
                        }
                        elseif (!empty($results['output'])) {
                            $output = $results['output'];
                        }
                    }
                }
                /* If not, reject with an error */
                if ($pass == false) {
                    $result[] = sprintf("Your wps didn't pass checkwps. Here's the output: %s", join("\n", $output));
                }
                break;
            case 'sshot_wps':
            case 'sshot_menu':
                if (isset($values['tmp_name']) && trim($values['tmp_name']) != "") {
                    $result = $site->validatesshot($values, $lcd['mainlcd']);
                }
                break;
        }
        if (count($result) > 0) {
            $err[$name] = $result;
        }
    }
}

if (isset($_REQUEST['author'])) {
    $err = array();
    /* First we do some checking of the uploaded data */
    checkuploadfields($site, $err);
    checkuploadfiles($site, $err);
    if (count($err) > 0) {
        $t->assign('errors', $err);
    }
    /* If that went wrong, go on and include the theme */
    else {
        /* 
         * At this stage, the theme has been validated, any possible errors
         * are now our fault.
         */
        $lcd = $site->target2lcd($_REQUEST['target']);

        /* 
         * Figure out a decent shortname. Use the zipfile name and add some
         * numbers if that exists.
         */
        $i = 0;
        do {
            $shortname = sprintf("%s%s",
                basename(str_replace(' ', '_', strtolower($_FILES['themefile']['name'])), '.zip'),
                $i == 0 ? '' : "-$i"
            );
            $destdir = sprintf("%s/%s/%s", config::datadir, $lcd['mainlcd'], $shortname);
            $i++;
        } while (file_exists($destdir));

        $result = $site->addtheme(
            $_REQUEST['themename'],
            $shortname,
            $_REQUEST['author'],
            $_REQUEST['email'],
            $lcd['mainlcd'],
            $lcd['remotelcd'] == '' ? false : $lcd['remotelcd'],
            $_REQUEST['description'],
            $_FILES['themefile'],
            $_FILES['sshot_wps'],
            isset($_FILES['sshot_menu']) ? $_FILES['sshot_menu'] : false
        );
        if (is_array($result)) {
            $t->assign('general_errors', $result);
        }
        else {
            $insertid = $result;
            $site->prepareverification($insertid, $_REQUEST['email'], $_REQUEST['author']);
            $template = "uploadcomplete.tpl";
        }
    }
}
/* 
 * If no template is set, it either means this is the first load, or upload
 * failed
 */
if (!isset($template)) {
    $template = "upload.tpl";
    $targets = $site->listtargets();
    foreach($targets as $target) {
        $values['targets'][$target['shortname']] = $target['fullname'];
    }
}

$t->render($template, $values);
?>
