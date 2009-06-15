/***************************************************************************
 *             __________               __   ___.
 *   Open      \______   \ ____   ____ |  | _\_ |__   _______  ___
 *   Source     |       _//  _ \_/ ___\|  |/ /| __ \ /  _ \  \/  /
 *   Jukebox    |    |   (  <_> )  \___|    < | \_\ (  <_> > <  <
 *   Firmware   |____|_  /\____/ \___  >__|_ \|___  /\____/__/\_ \
 *                     \/            \/     \/    \/            \/
 * $Id$
 *
 * Copyright (C) 2009 by Maurus Cuelenaere
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

function addLoadEvent(func)
{
    var oldonload = window.onload;
    if (typeof window.onload != 'function')
        window.onload = func;
    else
    {
        window.onload = function()
                        {
                            if (oldonload)
                                oldonload();
                            func();
                        };
    }
}

function overSrcInit()
{
    var imgs = document.getElementsByTagName("img");
    for(var i=0; i<imgs.length; i++)
    {
        var img = imgs[i];
        var overSrc = img.getAttribute('oversrc');
        if(overSrc)
        {
            img.origSrc = img.src;
            img.onmouseover = function() { this.src = this.getAttribute('oversrc'); };
            img.onmouseout = function() { this.src = this.origSrc; };
        }
    }
}

addLoadEvent(overSrcInit);
