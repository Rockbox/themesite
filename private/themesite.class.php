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

require_once('db.class.php');

class themesite {
    private $db;
    private $themedir_public;
    private $themedir_private;

    public function __construct($dbfile) {
        $this->db = new db($dbfile);
        $this->themedir_public = sprintf("%s/%s/%s", $_SERVER['DOCUMENT_ROOT'], config::path, config::datadir);
        $this->themedir_private = sprintf("%s/%s", preconfig::privpath, config::datadir);
    }

    /*
     * Log a message to the log table. Time, IP and admin user (if any)
     * is automaticly added.
     */
    private function log($message) {
        $sql_f = "INSERT INTO log (time, ip, admin, msg) VALUES (datetime('now'), '%s', '%s', '%s')";
        $sql = sprintf($sql_f,
            $_SERVER['REMOTE_ADDR'],
            isset($_SESSION['user']) ? db::quote($_SESSION['user']) : '',
            db::quote($message)
        );
        $this->db->query($sql);
    }

    private function targetlist($orderby) {
        $sql = sprintf("
            SELECT targets.shortname AS shortname, fullname, pic, targets.mainlcd AS mainlcd, depth, targets.remotelcd AS remotelcd, COUNT(themes.name) AS numthemes 
            FROM targets LEFT OUTER JOIN (SELECT DISTINCT themes.name AS name,checkwps.target AS target 
            FROM themes,checkwps 
            WHERE themes.rowid=checkwps.themeid AND checkwps.pass=1 AND approved=1 AND emailverification=1) themes 
            ON targets.shortname=themes.target 
            GROUP BY targets.shortname||targets.mainlcd 
            ORDER BY %s
            ",
            $orderby
        );
        return $this->db->query($sql);
    }

    public function listtargets($orderby = 'LOWER(fullname) ASC') {
        $targets = $this->targetlist($orderby);
        $ret = array();
        while ($target = $targets->next()) {
            $ret[] = $target;
        }
        return $ret;
    }

    /*
     * Run checkwps on all our themes
     */
    public function checkallthemes($id = 0) {
        $this->log("Running checkwps");
        $sql = sprintf("SELECT RowID, * FROM themes WHERE RowID=%d OR %s",
            $id,
            $id === 0 ? 1 : 0
        );
        $themes = $this->db->query($sql);
        $return = array();
        while ($theme = $themes->next()) {
            $starttime = microtime(true);
            $zipfile = sprintf("%s/%s/%s/%s",
                $theme['approved'] == 1 ? $this->themedir_public : $this->themedir_private,
                $theme['mainlcd'],
                $theme['shortname'],
                $theme['zipfile']
            );
            $result = $this->checkwps($zipfile, $theme['mainlcd'], $theme['remotelcd']);

            /* 
             * Store the results and check if at least one check passed (for
             * the summary)
             */
            $passany = false;
            foreach($result as $version_type => $targets) {
                foreach($targets as $target => $result) {
                    if ($result['pass']) $passany = true; /* For the summary */
                    /*
                     * Maybe we want to have two tables - one with historic
                     * data, and one with only the latest results for fast
                     * retrieval?
                     */     
                    $this->db->query(sprintf("DELETE FROM checkwps WHERE themeid=%d AND version_type='%s' AND target='%s'", $theme['RowID'], db::quote($version_type), db::quote($target)));
                    $sql = sprintf("INSERT INTO checkwps (themeid, version_type, version_number, target, pass) VALUES (%d, '%s', '%s', '%s', '%s')",
                        $theme['RowID'],
                        db::quote($version_type),
                        db::quote($result['version']),
                        db::quote($target),
                        db::quote($result['pass'] ? 1 : 0)
                    );
                    $this->db->query($sql);
                }
            }
            $return[] = array(
                'theme' => $theme,
                'result' => $result,
                'summary' => array('theme' => $theme['name'], 'pass' => $passany, 'duration' => microtime(true) - $starttime)
            );
        }
        return $return;
    }

    public function adminlogin($user, $pass) {
        $sql = sprintf("SELECT COUNT(*) as count FROM admins WHERE name='%s' AND pass='%s'",
            db::quote($user),
            db::quote(md5($pass))
        );
        $result = $this->db->query($sql)->next();
        return $result['count'] == 1 ? true : false;
    }

    public function target2fullname($shortname) {
        $sql = sprintf("SELECT fullname FROM targets WHERE shortname='%s'",
            db::quote($shortname)
        );
        $result = $this->db->query($sql)->next();
        return $result === false ? '' : $result['fullname'];
    }

    public function hasrwps($zipfile) {
        $ret = false;
        foreach ($this->zipcontents($zipfile) as $file) {
            $pathinfo = $this->my_pathinfo($file);
        }
        return $ret;
    }

    private function zipcontents($zipfile) {
        $zip = zip_open($zipfile);
        $files = array();
        while ($ze = zip_read($zip)) {
            $filename = zip_entry_name($ze);
            $files[] = $filename;
        }
        return $files;
    }

    public function themedetails($id) {
        $sql = sprintf("
            SELECT
            name, author, timestamp, mainlcd, approved, reason, description, shortname, zipfile, sshot_wps, sshot_menu, email,
            emailverification = 1 as verified,
            themes.RowId as id,
            c.version_number AS current_version,
            c.pass AS current_pass,
            r.version_number as release_version,
            r.pass as release_pass
            FROM themes
            LEFT OUTER JOIN checkwps c ON (themes.rowid=c.themeid and c.version_type='current')
            LEFT OUTER JOIN checkwps r ON (themes.rowid=r.themeid and r.version_type='release')
            WHERE id=%d",
            db::quote($id)
        );
        $theme = $this->db->query($sql)->next();
        $zipfile = sprintf("%s/%s/%s/%s",
            $theme['approved'] == 1 ? $this->themedir_public : $this->themedir_private,
            $theme['mainlcd'],
            $theme['shortname'],
            $theme['zipfile']
        );
        $theme['files'] = $this->zipcontents($zipfile);
        
        $theme['size'] = filesize(sprintf("%s/%s/%s/%s",
                $theme['approved'] == 1 ? $this->themedir_public : $this->themedir_private,
                $theme['mainlcd'],
                $theme['shortname'],
                $theme['zipfile']
            ));
        return $theme;
    }

    public function listthemes($target = false, $orderby = 'timestamp DESC', $approved = 'approved', $onlyverified = true) {
        $ret = array();
        switch($approved) {
            case 'any':
                $approved_clause = "";
                break;
            case 'hidden':
                $approved_clause = " AND approved = 0 ";
                break;
            case 'approved':
            default:
                $approved_clause = " AND approved = 1 ";
                break;
        }
        if ($onlyverified == true) {
            $verified = " AND emailverification = 1 ";
        }
        else {
            $verified = "";
        }

        if ($target === false) {
            $sql = "SELECT DISTINCT themes.name AS name, author, timestamp, mainlcd, approved, reason, description, shortname, zipfile, sshot_wps, sshot_menu, emailverification = 1 as verified, themes.RowId as id FROM themes,checkwps WHERE themes.rowid=checkwps.themeid AND checkwps.pass=1 AND approved=1 AND emailverification=1 ORDER BY " . $orderby;
        }
        else {
            $sql = sprintf("
                SELECT
                name, author, timestamp, mainlcd, approved, reason, description, shortname, zipfile, sshot_wps, sshot_menu,email,
                emailverification = 1 as verified,
                themes.RowId as id,
                c.version_number AS current_version,
                c.pass AS current_pass,
                r.version_number as release_version,
                r.pass as release_pass
                FROM themes
                LEFT OUTER JOIN checkwps c ON (themes.rowid=c.themeid and c.version_type='current' and c.target='%s')

                LEFT OUTER JOIN checkwps r ON (themes.rowid=r.themeid and r.version_type='release' and r.target='%s') 
                WHERE 1 %s %s AND (current_pass=1 OR release_pass=1)
                ORDER BY %s
                ",
                db::quote($target),
                db::quote($target),
                $verified,
                $approved_clause,
                $orderby
            );
        }
        $themes = $this->db->query($sql);
        while ($theme = $themes->next()) {
            $theme['size'] = filesize(sprintf("%s/%s/%s/%s",
                $theme['approved'] == 1 ? $this->themedir_public : $this->themedir_private,
                $theme['mainlcd'],
                $theme['shortname'],
                $theme['zipfile']
            ));
            $ret[] = $theme;
        }
        return $ret;
    }

    public function target2lcd($shortname) {
        $sql = sprintf("SELECT mainlcd, remotelcd, depth FROM targets WHERE shortname='%s'",
            db::quote($shortname)
        );
        return $this->db->query($sql)->next();
    }

    public function themenameexists($name, $mainlcd) {
        $sql = sprintf("SELECT COUNT(*) as count FROM themes WHERE name='%s' AND mainlcd='%s'",
            db::quote($name),
            db::quote($mainlcd)
        );
        $result = $this->db->query($sql)->next();
        return $result['count'] > 0 ? true : false;
    }

    public function changestatus($themeid, $newstatus, $oldstatus, $reason) {
        $status_text = array('1' => 'Approved', '0' => 'hidden', '-1' => 'deleted');
        $this->log(sprintf("Changing status of theme %d from %s to %s - Reason: %s",
            $themeid,
            $status_text[$oldstatus],
            $status_text[$newstatus],
            $reason
        ));
        $sql = sprintf("SELECT shortname, mainlcd, email, name, author, zipfile FROM themes WHERE RowID='%d'", db::quote($themeid));
        $theme = $this->db->query($sql)->next();

        if ($newstatus == -1) {
            $sql = sprintf("DELETE FROM themes WHERE RowID='%d'",
                db::quote($themeid)
            );

            /* Delete the files */
            foreach(array($this->themedir_public, $this->themedir_private) as $root) {
                $dir = sprintf("%s/%s/%s",
                    $root,
                    $theme['mainlcd'],
                    $theme['shortname']
                );
                if (file_exists($dir)) {
                    foreach(glob(sprintf("%s/*", $dir)) as $file) {
                        unlink($file);
                    }
                    rmdir($dir);
                }
            }
        }
        else {
            $sql = sprintf("UPDATE themes SET approved='%d', reason='%s' WHERE RowID='%d'",
                db::quote($newstatus),
                db::quote($reason),
                db::quote($themeid)
            );
            $from = sprintf("%s/%s/%s/%s", $this->themedir_public, $theme['mainlcd'], $theme['shortname'], $theme['zipfile']);
            $to = sprintf("%s/%s/%s/%s", $this->themedir_private, $theme['mainlcd'], $theme['shortname'], $theme['zipfile']);
            if ($newstatus == 1) {
                $temp = $to;
                $to = $from;
                $from = $temp;
            }
            rename($from, $to);
        }
        if ($oldstatus == 1 && $newstatus < 1) {
            // Send a mail to notify the user that his theme has been
            // hidden/deleted. No reason to distinguish, since the result
            // for him is the same.
            $to = sprintf("%s <%s>", $theme['author'], $theme['email']);
            $subject = sprintf("Your theme '%s' has been removed from %s", $theme['name'], config::hostname);
            $msg = <<<END
Your theme {$theme['name']} was removed from the Rockbox theme site. The
following reason should explain why:

----------
{$reason}
----------

If you think this was a mistake, or disagree with the decision, contact the
theme site admins in the Rockbox Forums or on IRC.
END;
            $this->send_mail($subject, $to, $msg);
        }
        $this->db->query($sql);
    }

    public function addtarget($shortname, $fullname, $mainlcd, $pic, $depth, $remotelcd = false) {    
        $this->log(sprintf("Add new target %s", $fullname));

        $sql = sprintf("INSERT INTO targets
                        (shortname, fullname, mainlcd, pic, depth, remotelcd)
                        VALUES
                        ('%s', '%s', '%s', '%s', '%s', %s)",
            db::quote($shortname),
            db::quote($fullname),
            db::quote($mainlcd),
            db::quote($pic),
            db::quote($depth),
            $remotelcd === false ? 'NULL' : sprintf("'%s'", db::quote($remotelcd))
        );
        $this->db->query($sql);
        /* Create the target's dir in both the private and public theme dir */
        foreach(array($this->themedir_public, $this->themedir_private) as $root) {
            $themedir = sprintf("%s/%s", $root, $mainlcd);
            if (!file_exists($themedir)) {
                mkdir($themedir);
            }
        }
    }

    public function edittarget($shortname, $fullname, $mainlcd, $pic, $depth, $remotelcd = false) {    
        $this->log(sprintf("Edit target %s", $fullname));

        $sql = sprintf("UPDATE targets SET shortname='%s', fullname='%s', mainlcd='%s',
                         pic='%s', depth='%s', remotelcd='%s' WHERE shortname='%s'",
            db::quote($shortname),
            db::quote($fullname),
            db::quote($mainlcd),
            db::quote($pic),
            db::quote($depth),
            $remotelcd === false ? 'NULL' : sprintf("'%s'", db::quote($remotelcd)),
            db::quote($shortname)
        );
        $this->db->query($sql);
    }
    
    private function send_mail($subject, $to, $msg) {
        $msg = wordwrap($msg, 78);
        $headers = 'From: themes@rockbox.org';
        mail($to, $subject, $msg, $headers);
    }

    public function validatetheme($zipfile) {
        $err = array();
        return $err;
    }

    public function prepareverification($id, $email, $author) {
        $token = md5(uniqid());
        $sql = sprintf("UPDATE themes SET emailverification='%s' WHERE RowID='%s'",
            db::quote($token),
            db::quote($id)
        );
        $this->db->query($sql);
        $url = sprintf("%s%s/verify.php?t=%s", config::hostname, config::path, $token);
        /* xxx: Someone rewrite this message to not sound horrible */
        $msg = <<<END
Hello, you just uploaded a Rockbox theme and now we need you to verify your
email address. To do this, simply open the link below in your browser. You
may have to copy/paste the text into your browser's location bar in some cases.

$url

Thank for your contributions

The Rockbox Theme Site team.
END;
        /* ' (this is here to keep my syntax hilighting happy) */
        $subject = "Rockbox Theme Site email verification";
        $to = sprintf("%s <%s>", $author, $email);
        $this->send_mail($subject, $to, $msg);
    }

    public function verifyemail($token) {
        $sql = sprintf("UPDATE themes SET emailverification=1 WHERE emailverification='%s'",
            db::quote($token)
        );
        $res = $this->db->query($sql);
        return $res->rowsaffected();
    }

    public function addtheme($name, $shortname, $author, $email, $mainlcd, $remotelcd, $description, $zipfile, $sshot_wps, $sshot_menu) {
        $err = array();
        /* return array("Skipping upload"); */

        /* Create the destination dir in both private and public area */
        foreach(array($this->themedir_public, $this->themedir_private) as $root) {
            mkdir(sprintf("%s/%s/%s",
                $root,
                $mainlcd,
                $shortname
            ));
        }

        /* This is the actual destination dir */
        $destdir = sprintf("%s/%s/%s",
            config::defaultstatus == 1 ? $this->themedir_public : $this->themedir_private,
            $mainlcd,
            $shortname
        );
        
        /* Prepend wps- and menu- to screenshots */
        $sshot_wps['name']  = empty($sshot_wps['name'])  ? '' : 'wps-'.$sshot_wps['name'];
        $sshot_menu['name'] = empty($sshot_menu['name']) ? '' : 'menu-'.$sshot_menu['name'];

        /* Start moving files in place */
        $uploads = array($zipfile, $sshot_wps, $sshot_menu);
        $movedfiles = array();
        foreach($uploads as $file) {
            if ($file === false || empty($file['tmp_name'])) {
                continue;
            }
            $dest = sprintf("%s/%s",
                $destdir,
                $file['name']
            );

            if (!@move_uploaded_file($file['tmp_name'], $dest)) {
                /* Upload went wrong, clean up */
                foreach ($movedfiles as $movedfile) {
                    unlink($movedfile);
                }
                rmdir($destdir);
                $err[] = sprintf("Couldn't move %s.", $file['name'], $dest);
                return $err;
            }
            else {
                $movedfiles[] = $dest;
            }
        }
        $sql_f = "INSERT INTO themes (author, email, name, mainlcd, zipfile, sshot_wps, sshot_menu, remotelcd, description, shortname, emailverification, timestamp, approved) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', %s, %s, '%s', '%s', 0, datetime('now'), %d)";
        $sql = sprintf($sql_f,
            db::quote($author),
            db::quote($email),
            db::quote($name),
            db::quote($mainlcd),
            db::quote($zipfile['name']),
            db::quote($sshot_wps['name']),
            $sshot_menu === false ? 'NULL' : sprintf("'%s'", db::quote($sshot_menu['name'])),
            $remotelcd === false ? 'NULL' : sprintf("'%s'", db::quote($remotelcd)),
            db::quote($description),
            db::quote($shortname),
            config::defaultstatus
        );
        $result = $this->db->query($sql);
        $id = $result->insertid();
        $this->checkallthemes($id);
        $this->log(sprintf("Added theme %d (email: %s)", $id, $email));
        return $id;
    }

    public function updatetheme($id, $name, $mainlcd, $author, $email, $description) {
        $sql = sprintf("UPDATE themes SET name='%s', mainlcd='%s', author='%s', email='%s', description='%s' WHERE RowID=%d",
            db::quote($name),
            db::quote($mainlcd),
            db::quote($author),
            db::quote($email),
            db::quote($description),
            db::quote($id)
        );
        $this->db->query($sql);
    }

    /* add a new column to a table, make backup of theme file before */
    /* Sqlite2 doesnt support live column add, so export, drop, import it */
    public function addcolumn($table, $column,$value) {
        
        /* backup db */
        $i = 0;
        do {
            $backupname = sprintf("%s/themes-%s.db.bak",preconfig::privpath,"$i");
            $i++;
        } while (file_exists($backupname));
        $cmd = sprintf("cp %s/%s %s",preconfig::privpath,config::dbfile,$backupname);
        system($cmd,$retval);
        if($retval != 0)
            return "backup db failed";
        
        
        /* get complete table */ 
        $sql = sprintf("SELECT * from %s", 
                db::quote($table));
        $tabledata = $this->db->query($sql);
        $tabletypes = $this->db->columntypes(db::quote($table));
        
        /* wrap in transaction */
        $sql = "BEGIN TRANSACTION;";
        $this->db->query($sql);
        /* drop tabe */
        $sql = sprintf("DROP TABLE %s",db::quote($table)); 
        $this->db->query($sql);
        
        /* create new table */
        $sql = sprintf("CREATE TABLE %s(",db::quote($table));
        foreach ($tabletypes as $entry => $type) {
            $sql = sprintf("%s%s %s ,",$sql,$entry,$type);
        } 
        $sql = sprintf("%s%s)",$sql,db::quote($column));
        $this->db->query($sql);
        
        /* fill in data */
        while($tableentry = $tabledata->next()){
            $sql = sprintf("INSERT INTO %s (",db::quote($table));
            foreach ($tabletypes as $entry => $type) {
                $sql = sprintf("%s%s ,",$sql,$entry);
            }
            $sql = sprintf("%s%s) VALUES(",$sql,db::quote($column));
            foreach ($tabletypes as $entry => $type) {
                $sql = sprintf("%s'%s' ,",$sql, db::quote($tableentry[$entry]));
            }
            $sql = sprintf("%s'%s')",$sql,db::quote($value));
            $this->db->query($sql);
        }
        
        $sql = "COMMIT";
        $this->db->query($sql);
            
        $this->log(sprintf("Column %s added to %s. Backup is: ", $column,$table,$backupname));    
        return "Column added";
    }
    
    /*
     * Use this rather than plain pathinfo for compatibility with PHP<5.2.0
     */
    private function my_pathinfo($path) {
        $pathinfo = pathinfo($path);
        /* Make sure we have the $pathinfo['filename'] element added in PHP 5.2.0 */
        if (!isset($pathinfo['filename'])) {
            $pathinfo['filename'] = substr(
                $pathinfo['basename'],
                0,
                strrpos($pathinfo['basename'],'.') === false ? strlen($pathinfo['basename']) : strrpos($pathinfo['basename'],'.')
            );
        }
        return $pathinfo;
    }

    /*
     * Convenience function called from several locations
     */
    private function getzipentrycontents($zip, $ze) {
        $ret = "";
        zip_entry_open($zip, $ze);
        while($read = zip_entry_read($ze)) {
            $ret .= $read;
        }
        zip_entry_close($ze);
        return $ret;
    }

    /*
     * xxx: I don't know what kind of validation is wanted for cfg files
     */
    public function validatecfg($cfg, $files) {
        $conf = array();
        foreach(explode("\n", $cfg) as $line) {
            if (substr($line, 0, 1) == '#') continue;
            preg_match("/^(?P<name>[^:]*)\s*:\s*(?P<value>[^#]*)\s*$/", $line, $matches);
            if (count($matches) > 0) {
                extract($matches);
                switch($name) {
                    default:
                        break;
                }
            }
        }
    }

    public function lcd2targets($lcd) {
        $ret = array();
        $sql = sprintf("SELECT shortname FROM targets WHERE mainlcd='%s' OR remotelcd='%s'",
            db::quote($lcd),
            db::quote($lcd)
        );
        $targets = $this->db->query($sql);
        while ($target = $targets->next()) {
            $ret[] = $target['shortname'];
        }
        return $ret;
    }

    /*
     * Check a WPS against two revisions: current and the latest release
     */
    public function checkwps($zipfile, $mainlcd, $remotelcd) {
        $return = array();

        /* First, create a temporary dir */
        $tmpdir = sprintf("%s/temp-%s", preconfig::privpath, md5(uniqid()));
        mkdir($tmpdir);

        /* Then, unzip the theme here */
        $cmd = sprintf("%s -d %s %s", config::unzip, $tmpdir, escapeshellarg($zipfile));
        exec($cmd, $dontcare, $ret);

        /* Now, cd into that dir */
        $olddir = getcwd();
        chdir($tmpdir);

        /* 
         * For all .wps and .rwps, run checkwps of both release and current for
         * all applicable targets
         *
         * 2009-06-20: Changed to only check .wps, since matching on remotelcd
         *             had some less desirable side effects.
         */
        foreach(glob('.rockbox/wps/*{wps,sbs}',GLOB_BRACE) as $file) {
            $p = $this->my_pathinfo($file);
            $lcd = ($p['extension'] == 'rwps' || $p['extension'] == 'rsbs'  ? $remotelcd : $mainlcd);
            foreach(array('release', 'current') as $version) {
                foreach($this->lcd2targets($lcd) as $shortname) {
                    $result = array();
                    $checkwps = sprintf("%s/checkwps/%s/checkwps.%s",
                        '..', /* We'll be in a subdir of the private dir */
                        $version,
                        $shortname
                    );
                    $result['version'] = trim(file_get_contents(sprintf('%s/checkwps/%s/VERSION',
                        '..',
                        $version,
                        $shortname
                    )));
                    if (file_exists($checkwps)) {
                        exec(sprintf("%s %s", $checkwps, escapeshellarg($file)), $output, $ret);
                        $result['pass'] = ($ret == 0);
                        $result['output'] = $output;
                        $return[$version][$shortname] = $result;
                    }
                }
            }
        }

        /* chdir back */
        chdir($olddir);

        /* Remove the tempdir */
        $this->rmdir_recursive($tmpdir);
        return $return;
    }

    private function rmdir_recursive($dirname) {
        $dir = dir($dirname);
        while (false !== ($entry = $dir->read())) {
            if ($entry == '.' || $entry == '..') continue;
            $path = sprintf("%s/%s", $dir->path, $entry);
            if (is_dir($path)) {
                chmod($path, 0700); // To make sure we're allowed to delete files
                $this->rmdir_recursive($path);
            }
            else {
                unlink($path);
            }
        }
        $dir->close();
        rmdir($dirname);
    }

    /*
     * This rather unwieldy function validates the structure of a theme's
     * zipfile. It checks the following:
     * - Exactly 1 .wps file
     * - 0 or 1 .rwps file
     * - Only .bmp files in /.rockbox/backdrops/ and /.rockbox/wps/<shortname>/
     * - All files are inside /.rockbox
     * - All .wps, .rwps and .cfg files use the same shortname, which is also
     *   the one used for the subdir in /.rockbox/wps
     *
     * It does not uncompress any of the files.
     *
     * We continue checking for errors, rather than aborting, so the uploader
     * gets a full list of things we didn't like.
     */
    public function validatezip($themezipupload) {
        $err = array();
        $zip = zip_open($themezipupload['tmp_name']);
        $totalsize = 0;
        $files = array();
        $wpsfound = array();
        $rwpsfound = array();
        $sbsfound = array();
        $rsbsfound = array();
        $cfgfound = array();
        $shortname = '';
        $cfg = '';

        if (is_int($zip)) {
            $err[] = sprintf("Couldn't open zipfile %s", $themezipupload['name']);
            return $err;
        }
        while ($ze = zip_read($zip)) {
            $filename = zip_entry_name($ze);
            $pathinfo = $this->my_pathinfo($filename);
            $totalsize += zip_entry_filesize($ze);
            $files[] = $filename;

            /* Count .wps and .rwps  and [.r]sbs files for later checking */
            if (strtolower($pathinfo['extension']) == 'wps')
                $wpsfound[] = $filename;
            if (strtolower($pathinfo['extension']) == 'rwps')
                $rwpsfound[] = $filename;
            if (strtolower($pathinfo['extension']) == 'sbs')
                $sbsfound[] = $filename;
            if (strtolower($pathinfo['extension']) == 'rsbs')
                $rsbsfound[] = $filename;

            /* Check that all files are within .rockbox */
            if (strpos($filename, '.rockbox') !== 0)
                $err[] = sprintf("File outside /.rockbox/: %s", $filename);

            /* Check that all .wps, .rwps and .cfg filenames use the same shortname */
            switch(strtolower($pathinfo['extension'])) {
                case 'cfg':
                    /* Save the contents for later checking */
                    $cfg = $this->getzipentrycontents($zip, $ze);
                    $cfgfound[] = $filename;
                case 'sbs':
                case 'rsbs':
                case 'wps':
                case 'rwps':
                    if ($shortname === '')
                        $shortname = $pathinfo['filename'];
                    elseif ($shortname !== $pathinfo['filename'])
                        $err[] = sprintf("Filename invalid: %s (should be %s.%s)", $filename, $shortname, $pathinfo['extension']);
                    break;
            }

            /* 
             * Check that the dir inside /.rockbox/wps also has the same name.
             * This automatically ensures that there is only one.
             */
            if ($pathinfo['dirname'] == '.rockbox/wps' && $pathinfo['extension'] == '') {
                if ($shortname === '')
                    $shortname = $pathinfo['filename'];
                elseif ($shortname !== $pathinfo['filename'])
                    $err[] = sprintf("Invalid dirname: %s (should be %s.)", $filename, $shortname);
            }

            /*
             * Check that the only files we have inside /.rockbox/backdrops/
             * and subdirs of /.rockbox/wps/ are .bmp files
             */
            if (strtolower($pathinfo['extension']) != 'bmp' && 
                ($pathinfo['dirname'] == '.rockbox/backdrops' || // Files inside .rockbox/backdrops
                  ($pathinfo['dirname'] != '.rockbox/wps' && strpos($pathinfo['dirname'], '.rockbox/wps') === 0) // Files in a subdir of .rockbox/wps (first part or dirname is .rockbox/wps, but it's not all of it)
                )
               ) {
                $err[] = sprintf("Non-bmp file not allowed here: %s", $filename);
            }

            /* Check for paths that are too deep */
            if (count(explode('/', $pathinfo['dirname'])) > 3) {
                $err[] = sprintf("Path too deep: %s", $filename);
            }

            /* Check for unwanted junk files */
            switch(strtolower($pathinfo['basename'])) {  
                case "thumbs.db":
                case "desktop.ini":
                case ".ds_store":
                case ".directory":
                    $err[] = sprintf("Unwanted file: %s", $filename);
            }
        }

        /* Now we check all the things that could be wrong */
        $this->validatecfg($cfg, $files);

        if ($themezipupload['size'] > config::maxzippedsize)
            $err[] = sprintf("Theme zip too large at %s (max size is %s)", $themezipupload['size'], config::maxzippedsize);
        if ($totalsize > config::maxthemesize)
            $err[] = sprintf("Unzipped theme size too large at %s (max size is %s)", $totalsize, config::maxthemesize);
        if (count($files) > config::maxfiles)
            $err[] = sprintf("Too many files+dirs in theme (%d). Maximum is %d.", count($files), config::maxfiles);

        if (count($wpsfound) > 1)
            $err[] = sprintf("More than one .wps found (%s).", implode(', ', $wpsfound));
        elseif (count($wpsfound) == 0)
            $err[] = "No .wps files found.";

        if (count($cfgfound) > 1)
            $err[] = sprintf("More than one .cfg found (%s).", implode(', ', $cfgfound));
        elseif (count($cfgfound) == 0)
            $err[] = "No .cfg files found.";

        if (count($rwpsfound) > 1)
            $err[] = sprintf("More than one .rwps found (%s).", implode(', ', $rwpsfound));
            
        if (count($sbsfound) > 1)
            $err[] = sprintf("More than one .sbs found (%s).", implode(', ', $sbsfound));
            
        if (count($rsbsfound) > 1)
            $err[] = sprintf("More than one .rsbs found (%s).", implode(', ', $rsbsfound));
        return $err;
    }

    public function validatesshot($upload, $mainlcd) {
        $err = array();
        $size = getimagesize($upload['tmp_name']);
        $dimensions = sprintf("%dx%d", $size[0], $size[1]);
        if ($size === false) {
            $err[] = sprintf("Couldn't open screenshot %s", $upload['name']);
        }
        else {
            if ($dimensions != $mainlcd) {
                $err[] = sprintf("Wrong resolution of %s. Should be %s (is %s).", $upload['name'], $mainlcd, $dimensions);
            }
            if ($size[2] != IMAGETYPE_PNG) {
                $err[] = "Screenshots must be of type PNG.";
            }
        }
        return $err;
    }
}
?>
