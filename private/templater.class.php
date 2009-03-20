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


class templater {
    private $s;

    public function __construct($smartydir) {
        /* Load and set up Smarty */
        require_once(sprintf("%s/Smarty.class.php", $smartydir));
        $s = new smarty();
        $s->template_dir = sprintf("%s/templates", preconfig::privpath);
        $s->compile_dir = sprintf("%s/compiled", $s->template_dir);
        $s->cache_dir = sprintf("%s/cache", $s->template_dir);
        $s->caching = false;
        $s->debugging = true;
        $s->security = true;
        $s->security_settings['IF_FUNCS'] = array('array_key_exists', 'isset', 'is_array', 'count');
        $s->secure_dir = array(
            realpath(config::datadir),
            realpath($s->template_dir)
        );
        $s->config_load(realpath(sprintf("%s/themes.cfg", $s->template_dir)));
        $this->s = $s;
    }

    public function assign($name, $value) {
        $this->s->assign($name, $value);
    }

    public function render($pagename, $vars = array()) {
        if (is_array($vars)) {
            foreach($vars as $name => $value) {
                $this->assign($name, $value);
            }
        }
        $this->s->display($pagename);
        /* printf("<xmp>"); print_r($vars); print("</xmp>"); */
    }
}
?>
