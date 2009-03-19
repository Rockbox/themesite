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
    private $themedir_abs;

    public function __construct($dbfile) {
        $this->db = new db($dbfile);
        $this->themedir_abs = sprintf("%s/%s", $_SERVER['DOCUMENT_ROOT'], config::datadir);

        /* Make sure the theme dir exists */
        if (!file_exists($this->themedir_abs)) {
            if (!@mkdir($this->themedir_abs)) {
                die("The theme dir doesn't exist, and I can't create it. Giving up.");
            }
        }
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
        $sql = "SELECT shortname, fullname, pic, mainlcd, depth, remotelcd FROM targets ORDER BY " . $orderby;
        return $this->db->query($sql);
    }

    public function listtargets($orderby = 'fullname ASC') {
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
    public function checkallthemes() {
        $this->log("Running checkwps");
        $sql = "SELECT RowID, * FROM themes";
        $themes = $this->db->query($sql);
        $return = array();
        while ($theme = $themes->next()) {
            $starttime = microtime(true);
            $zipfile = sprintf("%s/%s/%s/%s",
                config::datadir,
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
                    $this->db->query(sprintf("DELETE FROM checkwps WHERE themeid=%d AND version_type='%s'", $theme['RowID'], db::quote($version_type)));
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

    public function listthemes($target, $orderby = 'timestamp DESC', $approved = 'approved', $onlyverified = true) {
        $ret = array();
        switch($approved) {
            case 'any':
                $approved_clause = "";
                break;
            case 'hidden':
                $approved_clause = " AND th.approved = 0 ";
                break;
            case 'approved':
            default:
                $approved_clause = " AND th.approved = 1 ";
                break;
        }
        if ($onlyverified == true) {
            $verified = " AND th.emailverification = 1 ";
        }
        else {
            $verified = "";
        }
        $sql = sprintf("SELECT name, timestamp, th.mainlcd as mainlcd, approved, reason, description, th.RowID as id, th.shortname AS shortname, zipfile, sshot_wps, sshot_menu, emailverification = 1 as verified FROM themes th, targets ta WHERE 1 %s %s AND th.mainlcd=ta.mainlcd and ta.shortname='%s' AND (ta.remotelcd IS NULL OR ta.remotelcd=th.remotelcd) ORDER BY %s",
            $verified,
            $approved_clause,
            db::quote($target),
            $orderby
        );
        $themes = $this->db->query($sql);
        while ($theme = $themes->next()) {
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
        $sql = sprintf("SELECT shortname, mainlcd, email, name, author FROM themes WHERE RowID='%d'", db::quote($themeid));
        $theme = $this->db->query($sql)->next();

        if ($newstatus == -1) {
            $sql = sprintf("DELETE FROM themes WHERE RowID='%d'",
                db::quote($themeid)
            );

            /* Delete the files */
            $dir = sprintf("%s/%s/%s",
                config::datadir,
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
        else {
            $sql = sprintf("UPDATE themes SET approved='%d', reason='%s' WHERE RowID='%d'",
                db::quote($newstatus),
                db::quote($reason),
                db::quote($themeid)
            );
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
        $themedir = sprintf("%s/%s", $this->themedir_abs, $mainlcd);
        if (!file_exists($themedir)) {
            mkdir($themedir);
        }
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

        /* Create the destination dir */
        $destdir = sprintf("%s/%s/%s",
            $this->themedir_abs,
            $mainlcd,
            $shortname
        );
        if (!file_exists($destdir) && !mkdir($destdir)) {
            $err[] = sprintf("Couldn't create themedir %s", $destdir);
            return $err;
        }
        
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
        $check = $this->checkwps(sprintf("%s/%s/%s", config::datadir, $mainlcd, $zipfile['name']), $mainlcd, $remotelcd);
        /* xxx: store these results */
        $this->log(sprintf("Added theme %d (email: %s)", $id, $email));
        return $id;
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
         */
        foreach(glob('.rockbox/wps/*wps') as $file) {
            $p = $this->my_pathinfo($file);
            $lcd = ($p['extension'] == 'rwps' ? $remotelcd : $mainlcd);
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
                        exec(sprintf("%s %s", $checkwps, $file), $output, $ret);
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

            /* Count .wps and .rwps files for later checking */
            if (strtolower($pathinfo['extension']) == 'wps')
                $wpsfound[] = $filename;
            if (strtolower($pathinfo['extension']) == 'rwps')
                $rwpsfound[] = $filename;

            /* Check that all files are within .rockbox */
            if (strpos($filename, '.rockbox') !== 0)
                $err[] = sprintf("File outside /.rockbox/: %s", $filename);

            /* Check that all .wps, .rwps and .cfg filenames use the same shortname */
            switch(strtolower($pathinfo['extension'])) {
                case 'cfg':
                    /* Save the contents for later checking */
                    $cfg = $this->getzipentrycontents($zip, $ze);
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

        if (count($rwpsfound) > 1)
            $err[] = sprintf("More than one .rwps found (%s).", implode(', ', $rwpsfound));
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
