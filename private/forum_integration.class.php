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
 * Copyright (C) 2010 Jonathan Gordon
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


class forum_integration {
    private $username = "testbot";
    private $password = "";
    private $board = "42"; /* spam board */
    private $cookie;

    private function forum_login() {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $this->cookie = tempnam ("/tmp", "CURLCOOKIE");
        curl_setopt ($curl, CURLOPT_COOKIEJAR, $this->cookie);

        $data = array('user' => $this->username,
                      'passwrd' => $this->password, 'cookielength' => '60');

        /* Login */
        curl_setopt($curl, CURLOPT_URL,
                    "http://forums.rockbox.org/index.php?action=login2");
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

        $result = curl_exec ($curl);

        return $curl;
    }

    private function cleanup($curl) {
        curl_close($curl);
        unlink($this->cookie);
    }

    private function get_seqnum_and_sc($html)
    {
        preg_match('/name="seqnum" value="([0-9]*)"/',
                   $html, $matches, PREG_OFFSET_CAPTURE);
        $seqnum = $matches[1][0];
        preg_match('/name="sc" value="([0-9a-f]*)"/',
                   $html, $matches, PREG_OFFSET_CAPTURE);
        $sc = $matches[1][0];

        $vals = array('seqnum' => $seqnum, 'sc' => $sc);
        return $vals;
    }



    /* Login and create a new thread.
     * Returns the thread number.
     * TODO: Error handling
     */
    public function create_thread($title, $message) {
        $curl = $this->forum_login();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

        /* Open the new post page to get some important values */
        curl_setopt($curl, CURLOPT_URL,
                    "http://forums.rockbox.org/index.php?action=post");
        $data = array('board' => $this->board . '.0');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec ($curl);

        $vals = $this->get_seqnum_and_sc($result);
        /* create the new post */
        sleep(3); /* forum software is mean, we apparently need to sleep */

        $data = array('start' => '0', 'board' => $this->board,
                      'subject' => $title,
                      'message' => $message,
                      'additional_options' => '1', 'goback' => '1',
                      'seqnum=' => $vals['seqnum'],
                      'sc' => $vals['sc']);
        curl_setopt($curl, CURLOPT_URL,
                    "http://forums.rockbox.org/index.php?action=post2");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec ($curl);

        $header  = curl_getinfo( $curl );
        $threadurl = $header['url'];
        preg_match('/topic=([0-9]*)/',
                   $threadurl, $matches, PREG_OFFSET_CAPTURE);
        $threadnum = $matches[1][0];

        $this->cleanup($curl);
        return $threadnum;
    }
    /*
    public reply_thread($thread_id, $message) {
        $curl = $this->forum_login();


        $this->cleanup($curl);
    }*/
}

$forum = new forum_integration();
$newthread = $forum->create_thread("hellfghdghdo", "fdjkshlkfahsldfhalsjd fkhds flsahdlfslhfa");

?>
