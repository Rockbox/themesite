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
            img.next = 1;
            img.onmouseover = startImageSwitcher;
            img.onmouseout = stopImageSwitcher;
        }
    }
}

function startImageSwitcher() 
{
    var pos = this.next;
    switch(pos) {
        case 0: this.src = this.origSrc;
            if(this.getAttribute('oversrc') == "") this.next = 0;
            else this.next = this.next+1;
            break;
        case 1: 
            this.src = this.getAttribute('path') + this.getAttribute('oversrc');
            if(this.getAttribute('oversrc1') == "") this.next = 0;
            else this.next = this.next+1;
            break;
        case 2:
            this.src = this.getAttribute('path') + this.getAttribute('oversrc1');
            if(this.getAttribute('oversrc2') == "") this.next = 0;
            else this.next = this.next+1;
            break;
        case 3: 
            this.src = this.getAttribute('path') + this.getAttribute('oversrc2');
            if(this.getAttribute('oversrc3') == "") this.next = 0;
            else this.next = this.next+1;
            break;
        case 4: this.src = this.getAttribute('path') + this.getAttribute('oversrc3');
            this.next = 0;
            break;            
        default: 
            this.src = this.origSrc;
            this.next = 0;
    }
    
    this.timer = setTimeout(function(thisObj) { thisObj.onmouseover(); }, 2000, this);
    
}

function stopImageSwitcher() 
{
    clearTimeout(this.timer);
    this.src = this.origSrc;    
    this.next = 0;
}

addLoadEvent(overSrcInit);
