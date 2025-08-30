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

/*
 * Simple DB class using SQLite3
 */
class db {
    private $dh;
    /*
     * Array of all tables in the db
     * This is used as a reference to the database hierarchy
    */
    private $tables = array(
            'checkwps' =>array('themeid'            => 'INTEGER' ,
                               'version_type'       => 'TEXT' ,
                               'version_number'     => 'TEXT',
                               'target'             => 'TEXT',
                               'pass'               => 'INTEGER',
                               'output'             => 'TEXT'),
            'themes' => array( 'name'               => 'TEXT',
                               'approved'           => 'INTEGER',
                               'shortname'          => 'TEXT',
                               'author'             => 'TEXT',
                               'email'              => 'TEXT',
                               'mainlcd'            => 'TEXT',
                               'remotelcd'          => 'TEXT',
                               'description'        => 'TEXT',
                               'zipfile'            => 'TEXT',
                               'sshot_wps'          => 'TEXT',
                               'sshot_menu'         => 'TEXT',
                               'sshot_1'            => 'TEXT',
                               'sshot_2'            => 'TEXT',
                               'sshot_3'            => 'TEXT',
                               'emailverification'  => 'TEXT',
                               'reason'             => 'TEXT',
                               'timestamp'          => 'DATETIME',
                               'downloadcnt'        => 'INTEGER',
                               'ratings'            => 'INTEGER',
                               'numratings'         => 'INTEGER',
                               'filesize'           => 'INTEGER'),
            'admins' => array( 'name'               => 'TEXT' ,
                               'pass'               => 'TEXT'),
            'targets' => array('shortname'          => 'TEXT' ,
                               'fullname'           => 'TEXT',
                               'mainlcd'            => 'TEXT',
                               'remotelcd'          => 'TEXT',
                               'pic'                => 'TEXT',
                               'depth'              => 'INTEGER' ),
            'log' => array(    'time'               => 'TEXT',
                               'ip'                 => 'TEXT',
                               'admin'              => 'TEXT',
                               'msg'                => 'TEXT'),
            'settings' =>array('name'               => 'TEXT',
                               'type'               => 'TEXT'),
            'zipcontents'=>array('themeid'          => 'INTEGER',
                                'filename'          => 'TEXT'));

    public function __construct($dbstr, $dbuser, $dbpass) {
        /* open db */
        $this->dh = new PDO($dbstr, $dbuser, $dbpass);
    }

    public function query($sql, $args = null){
        //prepare the query
        $stmt = $this->dh->prepare($sql);
        if(is_array($args)){
            //loop through each value that needs to be bound
            foreach($args as $key => $val){
                $stmt->bindValue($key, $val);
            }
        }
        //run the query
        $res = $stmt->execute();
        //check for errors
        if($res === false){
            $code = $this->dh->errorCode();
            $err = sprintf('%s (%d)', $this->dh->errorInfo(), $code);
            $sql = $sql . ' ' . print_r($args, true);
            $this->error($err, $sql);
        } else{
            return new result($stmt, $this->dh);
        }
    }

    /* Log a message to the log table. */
    private function log($message) {
        $sql = 'INSERT INTO log (time, ip, admin, msg) VALUES (datetime("now"), "self", "selfupdate", :msg)';
        $args = array( ':msg' => $message );
        $this->query($sql, $args);
    }

    private function error($err, $sql = '') {
        /*
         * Sometimes the error is empty, in which case the explanation can be
         * found like this
         */
        if ($err == '') {
            $code = $this->dh->lastErrorCode();
            $err = sprintf('%s (%d)',
                $this->dh->lastErrorMsg(),
                $code
            );
        }
        $msg = sprintf('<b>DB Error:</b> %s', $err);
        if ($sql != '') {
            $msg .= sprintf("<br />\n<b>SQL:</b> %s", $sql);
        }
        /* xxx: We'd probably want to log this rather than output it */
        die($msg);
    }

    /*
    * Used for cases where item cant be bound to a prepared statement
    * Mostly used with "Order By" syntax or when a dynamic field name is needed
    */
    public static function quote($input) {
        return SQLite3::escapeString($input);
    }
}

/*
 * Simple OO wrapper to make retrieving SQLite3 results easy.
 */
class result {
    private $rh;
    private $dh;

    public function __construct($rh, &$dh) {
        $this->rh = $rh;
        $this->dh = $dh;
    }

    public function next($field = false) {
        $row = $this->rh->fetch();
        if ($field !== false && isset($row[$field])) {
            return $row[$field];
        } else {
            return $row;
        }
    }

    public function insertid() {
        return $this->dh->lastInsertId();
    }

    public function rowsaffected() {
        return $this->rh->rowCount();
    }
}

?>
