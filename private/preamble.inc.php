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

require_once(sprintf("%s/config.inc.php", preconfig::privpath));
require_once('themesite.class.php');
require_once('templater.class.php');
require_once('recaptchalib.php');

date_default_timezone_set('UTC');

$site = new themesite(config::dbstr, config::dbuser, config::dbpass);
$t = new templater(config::smartydir);
$t->assign('datadir', config::datadir);
$t->assign('root', config::path);
$t->assign('hostname', config::hostname);
$t->assign('maxuploadsize', config::maxzippedsize);
$t->assign('recaptchakey',config::recaptchakey);
$values = array();
?>
