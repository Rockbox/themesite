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

class config {
    // Max size of the theme .zip file in bytes
    const maxzippedsize = 1000000;

    // Max size of the themes when unzipped in bytes
    const maxthemesize = 5000000;

    // Max number of files in a theme (includes dirs)
    const maxfiles = 100;

    // Fully qualified hostname of your server. Without trailing slash or path.
    // Including http://
    const hostname = "http://themes.rockbox.org"; 

    // Path to the theme site, relative to your server's document root, without trailing slash (might be '')
    const path = "";

    // Full, absolute path to the location of the smarty template engine
    const smartydir = "/usr/share/php/smarty/libs";

    // Location to store theme data. Relative to the path given above - must be web-accessible
    const datadir = "themes"; 

    // Location of the db within the above dir. Don't make it web-accessible.
    const dbfile = "themes.db";

    // Default status for newly uploaded themes. 1=approved. 0=hidden.
    const defaultstatus = 1;

    // Location of 'unzip'
    const unzip = "/usr/bin/unzip";
}

?>
