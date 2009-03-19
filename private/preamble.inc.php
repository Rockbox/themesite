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

require_once('../private/config.inc.php');
require_once('themesite.class.php');
require_once('templater.class.php');

/* temporary shit */
/*
unlink(config::dbfile);
unlink(sprintf("%s/176x220/widecabbie/%s", config::datadir, 'widecabbie.zip'));
unlink(sprintf("%s/176x220/widecabbie/%s", config::datadir, 'widecabbie-menu.png'));
unlink(sprintf("%s/176x220/widecabbie/%s", config::datadir, 'widecabbie-wide.png'));
rmdir(sprintf("%s/176x220/widecabbie", config::datadir));
/* Delete the above */

$site = new themesite(config::privdir .'/' . config::dbfile);
$t = new templater(config::smartydir);
$t->assign('datadir', config::datadir);
$t->assign('root', config::path);
$t->assign('hostname', config::hostname);
$t->assign('maxuploadsize', config::maxzippedsize);

/* More temporary shit */
//$site->addtarget('e200', 'Sandisk Sansa E200', '176x220', 'e200-small.png', '16');
//$site->addtarget('ipodnano', 'Apple Ipod Nano 1G', '176x132', 'ipodnano-small.png', '16');
/* Delete this as well */

$values = array();
?>
