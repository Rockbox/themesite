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

// wrapper functions for old SQLite2 calls, copied from php.net
// see http://www.php.net/manual/en/book.sqlite3.php#106779
function sqlite_open($location,$mode)
{
    $handle = new SQLite3($location);
    return $handle;
}
function sqlite_query($dbhandle,$query)
{
    $array['dbhandle'] = $dbhandle;
    $array['query'] = $query;
    $result = $dbhandle->query($query);
    return $result;
}
function sqlite_fetch_array(&$result,$type=0)
{
    #Get Columns
    $i = 0;
    while ($result->columnName($i))
    {
        $columns[ ] = $result->columnName($i);
        $i++;
    }
   
    $resx = $result->fetchArray(SQLITE3_ASSOC);
    return $resx;
} 

/*
 * Simple DB class using sqlite and a bunch of assumptions.
 */
class db {
    private $file;
    private $dh;
    /* array of all table in the db */
    /* if you add tables or entrys the will be automatically added */
    /* WARNING dont move entry or you will loose data */
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
                               'timestamp'          => 'FLOAT',
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
                                
    public function __construct($file) {
        $this->file = $file;
        /* open db */
        $this->dh = @sqlite_open($file, 0666, $err);
// FIXME: database update currrently not working with SQLite3
//
//        if ($this->dh === false) {
//            $this->error($err);
//        }
//        else {
//            /* try to  create tables */
//            /* wrap in transaction */
//            $this->query("BEGIN TRANSACTION");
//            /* create all tables if they dont exist */
//            foreach($this->tables as $name => $table)
//            {
//                if($this->columntypes($name) == false)
//                {
//                    $sql = sprintf("CREATE TABLE %s(",$name);
//                    foreach ($table as $entry => $type) {
//                        $sql = sprintf("%s%s %s ,",$sql,$entry,$type);
//                    } 
//                    $sql = sprintf("%s)",chop($sql,','));
//                    $this->query($sql);
//                }
//            }
//            /* end transaction */
//            $this->query("COMMIT");
//            
//            /* check if we need to add rows */
//            $addColumns = array();
//            foreach($this->tables as $name => $table)
//            {
//                $curtable = $this->columntypes($name);
//                $diff = 0;
//                foreach($table as $key => $entry)
//                {
//                    if(!array_key_exists($key,$curtable))
//                    {
//                        $diff = 1;
//                    }
//                }
//                
//                if($diff > 0) {
//                    $addColumns[$name] = $table;
//                }
//            }
//            
//            /* add rows if needed */
//            if(count($addColumns) > 0) {
//                /* Sqlite2 doesnt support live column add, so backup, export, drop, import it */
//                 /* backup db */
//                $i = 0;
//                do {
//                    $backupname = sprintf("%s/themes-%s.db.bak",preconfig::privpath,"$i");
//                    $i++;
//                } while (file_exists($backupname));
//                $cmd = sprintf("cp %s %s",$file,$backupname);
//                system($cmd,$retval);
//                if($retval != 0) {
//                    $this->log(sprintf("Failed to backup DB for upgrade: %s",$backupname));   
//                    die(sprintf("Failed to backup DB for upgrade: %s",$backupname));
//                }    
//                /* wrap in transaction */
//                $this->query("BEGIN TRANSACTION");   
//                foreach($addColumns as $name => $table)
//                {
//                    /* get complete table */ 
//                    $sql = sprintf("SELECT RowID,* from %s",$name);
//                    $tabledata = $this->query($sql);
//                    $tabletypes = $this->columntypes($name);
//                    /* drop tabe */
//                    $sql = sprintf("DROP TABLE %s",$name); 
//                    $this->query($sql);
//                    /* create new table */
//                    $sql = sprintf("CREATE TABLE %s(",$name);
//                    foreach ($table as $entry => $type) {
//                        $sql = sprintf("%s%s %s ,",$sql,$entry,$type);
//                    } 
//                    $sql = sprintf("%s)",chop($sql,','));
//                    $this->query($sql);
//                    /* fill in data */
//                    while($tableentry = $tabledata->next()){
//                        $sql = sprintf("INSERT INTO %s (rowid, ",$name);
//                        foreach ($tabletypes as $entry => $type) {
//                            $sql = sprintf("%s%s ,",$sql,db::quote($entry));
//                        }
//                        $sql = sprintf("%s) VALUES(%s, ",chop($sql,','),$tableentry['RowID']);
//                        
//                        foreach ($tabletypes as $entry => $type) {
//                            $sql = sprintf("%s'%s' ,",$sql,db::quote($tableentry[$entry]));
//                        }
//                        $sql = sprintf("%s)",chop($sql,','));
//                        
//                        $this->query($sql);
//                    }
//                }
//                /* end transaction */
//                $this->query("COMMIT");
//                $this->log(sprintf("Database upgraded. Backup is: %s",$backupname));    
//            }
//        }
    }
    
    /* Log a message to the log table.   */
    private function log($message) {
        $sql = sprintf("INSERT INTO log (time, ip, admin, msg) VALUES (datetime('now'), 'self', 'selfupdate', '%s')",$message);
        $this->query($sql);
    }
    
    public function query($sql) {
        $res = @sqlite_query(
            $this->dh,
            $sql
        );
        if ($res === false) {
            $code = $this->dh->lastErrorCode();
            $err = sprintf("%s (%d)", $this->dh->lastErrorMsg(), $code);
            $this->error($err, $sql);
        }
        else {
            return new result($res, $this->dh);
        }
    }

    public function columntypes($table) {
        return @sqlite_fetch_column_types($table, $this->dh, SQLITE_ASSOC);
    }
    
    private function error($err, $sql = "") {
        /* 
         * Sometimes the error is empty, in which case the explanation can be
         * found like this
         */
        if ($err == "") {
            $code = $this->dh->lastErrorCode();
            $err = sprintf("%s (%d)",
                $this->dh->lastErrorMsg(),
                $code
            );
        }
        $msg = sprintf("<b>DB Error:</b> %s", $err);
        if ($sql != "") {
            $msg .= sprintf("<br />\n<b>SQL:</b> %s", $sql);
        }
        /* xxx: We'd probably want to log this rather than output it */
        die($msg);
    }

    public static function quote($input) {
        return SQLite3::escapeString($input);
    }
}

/*
 * Simple OO wrapper around the regular sqlite functions. Newer PHP versions
 * have something like this, but use this to avoid depending on that.
 */
class result {
    private $rh;
    private $dh;

    public function __construct($rh, &$dh) {
        $this->rh = $rh;
        $this->dh = $dh;
    }

    public function next($field = false) {
        $row = sqlite_fetch_array($this->rh);
        if ($field !== false && isset($row[$field])) {
            return $row[$field];
        }
        else {
            return $row;
        }
    }
    
    public function insertid() {
        return this->dh->lastInsertRowID();
    }

    public function rowsaffected() {
        return this->dh->changes();
    }
}

?>
