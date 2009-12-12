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
 * Simple DB class using sqlite and a bunch of assumptions.
 */
class db {
    private $file;
    private $dh;
    public function __construct($file) {
        $this->file = $file;
        $this->dh = @sqlite_open($file, 0666, $err);
        if ($this->dh === false) {
            $this->error($err);
        }
        else {
            $res = $this->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
            if ($res->next('count') === "0") {
                $checkwps_table = <<<END
CREATE TABLE checkwps (
    themeid INTEGER,
    version_type TEXT,
    version_number TEXT,
    target TEXT,
    pass INTEGER
);
END;

                $theme_table = <<<END
CREATE TABLE themes (
    name TEXT,
    approved INTEGER,
    shortname TEXT,
    author TEXT,
    email TEXT,
    mainlcd TEXT,
    remotelcd TEXT,
    description TEXT,
    zipfile TEXT,
    sshot_wps TEXT,
    sshot_menu TEXT,
    emailverification TEXT,
    reason TEXT,
    timestamp FLOAT
)
END;
                $admin_table = <<<END
CREATE TABLE admins (
    name TEXT PRIMARY KEY,
    pass TEXT
)
END;
                $target_table = <<<END
CREATE TABLE targets (
    shortname TEXT PRIMARY KEY,
    fullname TEXT,
    mainlcd TEXT,
    remotelcd TEXT,
    pic TEXT,
    depth INTEGER
)
END;
                $log_table = <<<END
CREATE TABLE log (
    time TEXT,
    ip TEXT,
    admin TEXT,
    msg TEXT
)
END;
                $this->query($target_table);
                $this->query($checkwps_table);
                $this->query($theme_table);
                $this->query($admin_table);
                $this->query($log_table);
            }
        }
    }

    public function query($sql) {
        $res = @sqlite_query(
            $this->dh,
            $sql,
            SQLITE_ASSOC,
            $err
        );
        if ($res === false) {
            $this->error($err, $sql);
        }
        else {
            return new result($res, $this->dh);
        }
    }

    private function error($err, $sql = "") {
        /* 
         * Sometimes the error is empty, in which case the explanation can be
         * found like this
         */
        if ($err == "") {
            $code = sqlite_last_error($this->dh);
            $err = sprintf("%s (%d)",
                sqlite_error_string($code),
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
        return sqlite_escape_string($input);
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

    public function numrows() {
        return sqlite_num_rows($this->rh);
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
    
    public function current($field = false) {
        $row = sqlite_current($this->rh);
        if ($field !== false && isset($row[$field])) {
            return $row[$field];
        }
        else {
            return $row;
        }
    }

    public function insertid() {
        return sqlite_last_insert_rowid($this->dh);
    }

    public function rowsaffected() {
        return sqlite_changes($this->dh);
    }
}

?>
