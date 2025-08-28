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

    public function __construct($dbstr, $dbuser, $dbpass) {
        $this->db = new db($dbstr, $dbuser, $dbpass);
        $this->themedir_public = sprintf('%s/%s/%s', $_SERVER['DOCUMENT_ROOT'], config::path, config::datadir);
        $this->themedir_private = sprintf('%s/%s', preconfig::privpath, config::datadir);
    }

    /*
     * Log a message to the log table. Time, IP and admin user (if any)
     * is automaticly added.
     */
    private function log($message) {
        $sql = 'INSERT INTO log (time, ip, admin, msg) VALUES (datetime("now"), :ip, :admin, :msg)';
        $args = array(
            ':ip' => $_SERVER['REMOTE_ADDR'],
            ':admin' => isset($_SESSION['user']) ? $_SESSION['user'] : '',
            ':msg' => $message
        );
        $this->db->query($sql, $args);
    }

    public function getlog() {
        $ret = array();
        $sql = 'SELECT time, ip, admin, msg FROM log ORDER BY time DESC';
        $results = $this->db->query($sql);
        while ($result = $results->next()) {
            $ret[] = $result;
        }
        return $ret;
    }

    private function targetlist($orderby) {
        $sql = sprintf('SELECT targets.shortname AS shortname, fullname, pic, targets.mainlcd AS mainlcd, depth,
                               targets.remotelcd AS remotelcd, COUNT(themes.name) AS numthemes
            FROM targets LEFT OUTER JOIN (SELECT DISTINCT themes.name AS name,checkwps.target AS target
            FROM themes,checkwps
            WHERE themes.themeid=checkwps.themeid AND checkwps.pass=1 AND approved>=1 AND emailverification=1) themes
            ON targets.shortname=themes.target
            GROUP BY targets.shortname,targets.mainlcd
            ORDER BY %s',
            db::quote($orderby)
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

    /* Returns the themeid of the newest theme with the same name and mainlcd size */
    public function getNewestChildTheme($themeid){
        $sql = 'SELECT name, approved, mainlcd, timestamp FROM themes WHERE themeid=:id';
        $args = array(':id' => $themeid);
        $new = $themeid;
        $old = $this->db->query($sql, $args)->next();
        if($old['approved'] != 1){
            $sql = 'SELECT themeid, timestamp FROM themes WHERE name=:name AND mainlcd=:mainlcd AND approved >= 1 AND emailverification = 1 ORDER BY timestamp DESC';
            $args = array(':name' => $old['name'], ':mainlcd' => $old['mainlcd']);
            $new = $this->db->query($sql, $args)->next('themeid');
        }
        return $new;
    }

    /*
     * Run checkwps on all our themes
     */
    public function checkallthemes($id = 0, $release = 0) {
        $this->log('Running checkwps');
        $sql = 'SELECT * FROM themes WHERE themeid=:id OR (:wmtf AND approved > 0)';
        $args = array(':id' => $id, ':wmtf' => $id === 0 ? 1 : 0);
        $themes = $this->db->query($sql, $args);
        $return = array();
        while ($theme = $themes->next()) {
            $starttime = microtime(true);
            $zipfile = sprintf('%s/%s/%s/%s',
                $theme['approved'] >= 1 ? $this->themedir_public : $this->themedir_private,
                $theme['mainlcd'],
                $theme['shortname'],
                $theme['zipfile']
            );
            $result = $this->checkwps($zipfile, $theme['mainlcd'], $theme['remotelcd'], $release);

            /*
             * Store the results and check if at least one check passed (for
             * the summary)
             */
            $this->db->query('DELETE FROM checkwps WHERE themeid=:id', array(':id' => $theme['themeid']));
            $passany = false;
            foreach($result as $version_type => $targets) {
                foreach($targets as $target => $result2) {
                    if ($result2['pass']) $passany = true; /* For the summary */
                    /*
                     * Maybe we want to have two tables - one with historic
                     * data, and one with only the latest results for fast
                     * retrieval?
                     */
                    $sql = 'INSERT INTO checkwps (themeid, version_type, version_number, target, pass, output) VALUES (:id, :ver_type, :ver_num, :target, :pass, :output)';
                    $args = array(
                        ':id' => $theme['themeid'],
                        ':ver_type' => $version_type,
                        ':ver_num' => $result2['version'],
                        ':target' => $target,
                        ':pass' => $result2['pass'] ? 1 : 0,
                        ':output' => implode(' ',$result2['output'])
                    );
                    $this->db->query($sql, $args);
                }
            }
            $return[] = array(
                'theme' => $theme,
                'result' => $result,
                'summary' => array('theme' => $theme['name'], 'pass' => $passany, 'duration' => microtime(true) - $starttime)
            );

            /* update filesize and zipcontents here, so old themes always have this data */
            $filesize = filesize(sprintf('%s/%s/%s/%s',
                $theme['approved'] >= 1 ? $this->themedir_public : $this->themedir_private,
                $theme['mainlcd'],$theme['shortname'],$theme['zipfile']));
            $sql = 'UPDATE themes SET filesize=:fs WHERE themeid=:id';
            $args = array(':fs' => $filesize, ':id' => $theme['themeid']);
            $this->db->query($sql, $args);

            $zipfiles = $this->zipcontents(sprintf('%s/%s/%s/%s',
                $theme['approved'] >= 1 ? $this->themedir_public : $this->themedir_private,
                $theme['mainlcd'],$theme['shortname'],$theme['zipfile']));
            $this->db->query('DELETE FROM zipcontents WHERE themeid=:id', array(':id' => $theme['themeid']));
            foreach($zipfiles as $file)
            {
                $this->db->query('INSERT into zipcontents(themeid,filename) VALUES(:id , :fn)', array(':id' => $theme['themeid'], ':fn' => $file));
            }
        }
        return $return;
    }

    public function adminlogin($user, $pass) {
        $sql = 'SELECT COUNT(*) as count FROM admins WHERE name=:name AND pass=:pass';
        $args = array(':name' => $user, ':pass' => md5($pass));
        $result = $this->db->query($sql, $args)->next();
        return $result['count'] == 1 ? true : false;
    }

    public function target2fullname($shortname) {
        $sql = 'SELECT fullname FROM targets WHERE shortname=:sn';
        $args = array(':sn' => $shortname);
        $result = $this->db->query($sql, $args)->next();
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

    public function themedetails($id, $onlyapproved = false, $onlyverified = false) {
        $verified = $onlyverified ? ' AND emailverification=1 ' : '';
        $approved = $onlyapproved ? ' AND approved >= 1 ' : '';
        $sql = sprintf('
            SELECT
            name, author, timestamp, mainlcd, approved, reason, description, shortname, zipfile, sshot_wps, sshot_menu, sshot_1, sshot_2,sshot_3,
            email, downloadcnt, ratings, numratings, filesize as size,
            emailverification = 1 as verified,
            themes.themeid as id,
            c.version_number AS current_version,
            c.pass AS current_pass,
            r.version_number as release_version,
            r.pass as release_pass,
            c.output as checkwps_output
            FROM themes
            LEFT OUTER JOIN checkwps c ON (themes.themeid=c.themeid and c.version_type="current")
            LEFT OUTER JOIN checkwps r ON (themes.themeid=r.themeid and r.version_type="release")
            WHERE themes.themeid=:id %s %s',
            $verified,
            $approved
        );
        $args = array(':id' => $id);
        $theme = $this->db->query($sql, $args)->next();
        $fileresult = $this->db->query('SELECT filename FROM zipcontents WHERE themeid=:id', array(':id' => $theme['id']));
        $files = array();
        while ($file = $fileresult->next()) {$files[] = $file['filename']; }
        $theme['files'] = $files;
        if($theme['numratings'] > 0) $theme['ratings'] = $theme['ratings'] /$theme['numratings'] ;
        return $theme;
    }

    public function searchthemes($searchrow,$needle,$admin = false) {
        $ret = array();
        $checkwps_clause = '';
        $approved_clause = '';
        $verified = '';
        if($admin === false)
        {
            $checkwps_clause = 'AND (c.pass=1 OR r.pass=1)';
            $approved_clause = 'AND approved >= 1';
            $verified = 'AND emailverification = 1';
        }

        $sql = sprintf('SELECT themes.name AS name, author, timestamp, mainlcd, approved, reason, description, shortname,
                zipfile, sshot_wps, sshot_menu, sshot_1, sshot_2, sshot_3,
                email, downloadcnt, ratings, numratings, filesize as size,
                emailverification = 1 as verified,
                themes.themeid as id,
                c.version_number AS current_version,
                c.pass AS current_pass,
                r.version_number as release_version,
                r.pass as release_pass,
                c.output as checkwps_output
                FROM themes
                LEFT OUTER JOIN checkwps c ON (themes.themeid=c.themeid and c.version_type="current")
                LEFT OUTER JOIN checkwps r ON (themes.themeid=r.themeid and r.version_type="release")
                WHERE 1 %s %s %s AND %s LIKE "%%%s%%" GROUP BY name, mainlcd',
                $verified,
                $approved_clause,
                $checkwps_clause,
                db::quote($searchrow),
                db::quote($needle)
        );
        $themes = $this->db->query($sql);
        /* create additional data */
        while ($theme = $themes->next()) {
            if($theme['numratings'] > 0) $theme['ratings'] = $theme['ratings'] / $theme['numratings'];
            $ret[] = $theme;
        }
        return $ret;
    }

    public function listthemes($target = false, $orderby = 'timestamp DESC', $approved = 'approved', $onlyverified = true) {
        $ret = array();
        $checkwps_clause = 'AND (c.pass=1 OR r.pass=1)';
        switch($approved) {
            case 'any': $approved_clause = ''; break;
            case 'hidden': $approved_clause = ' AND approved = 0 '; break;
            case 'reported': $approved_clause = ' AND approved = 2 '; break;
            case 'approved':
            default:
                $approved_clause = ' AND approved >= 1 ';
                break;
        }
        if ($onlyverified == true) {
            $verified = ' AND emailverification = 1 ';
        }else {
            $checkwps_clause = 'AND (c.pass<>2 OR r.pass<>2)';  //workaround. without this we somehow get all themes
            $verified = '';
        }
        // special case for ratings
        if(substr($orderby, 0, 6) == 'rating'){
            $orderby = explode(' ', $orderby);
            $orderby = 'ratings/numratings ' . $orderby[count($orderby) - 1] . ', numratings ' . $orderby[count($orderby) - 1];
        }
        if ($target === false) {
            $sql = sprintf('SELECT themes.name AS name, author, timestamp, mainlcd, approved, reason, description, shortname,
                            zipfile, sshot_wps, sshot_menu,sshot_1,sshot_2,sshot_3,downloadcnt, ratings, numratings, filesize as size,
                            emailverification = 1 as verified, themes.themeid as id,
                            c.version_number AS current_version,
                            c.pass AS current_pass,
                            r.version_number as release_version,
                            r.pass as release_pass,
                            c.output as checkwps_output
                            FROM themes
                            LEFT OUTER JOIN checkwps c ON (themes.themeid=c.themeid and c.version_type="current")
                            LEFT OUTER JOIN checkwps r ON (themes.themeid=r.themeid and r.version_type="release")
                            WHERE 1 %s %s %s GROUP BY name, mainlcd ORDER BY %s',
                        $checkwps_clause,
                        $verified,
                        $approved_clause,
                        db::quote($orderby)
                    );
            $args= array();
        } else {
            $sql = sprintf('SELECT name, author, timestamp, mainlcd, approved, reason, description, shortname,
                zipfile, sshot_wps, sshot_menu, sshot_1, sshot_2, sshot_3,
                email, downloadcnt, ratings, numratings, filesize as size,
                emailverification = 1 as verified,
                themes.themeid as id,
                c.version_number AS current_version,
                c.pass AS current_pass,
                r.version_number as release_version,
                r.pass as release_pass,
                c.output as checkwps_output
                FROM themes
                LEFT OUTER JOIN checkwps c ON (themes.themeid=c.themeid and c.version_type="current" and c.target=:ctarget)
                LEFT OUTER JOIN checkwps r ON (themes.themeid=r.themeid and r.version_type="release" and r.target=:rtarget)
                WHERE 1 %s %s %s GROUP BY name ORDER BY %s',
                $verified,
                $approved_clause,
                $checkwps_clause,
                db::quote($orderby)
            );
            $args = array(
                ':ctarget' => $target,
                ':rtarget' => $target
            );
        }
        $themes = $this->db->query($sql, $args);
        /* create additional data */
        while ($theme = $themes->next()) {
            if($theme['numratings'] > 0) $theme['ratings'] = $theme['ratings'] / $theme['numratings'];
            $ret[] = $theme;
        }
        return $ret;
    }

    public function listthemesbyresolution($mainlcd = false, $remotelcd = false) {
        $ret = array();

        $lcd = '';
        $args = !$mainlcd && !$remotelcd ? null : array();
        if ($mainlcd){
            $lcd .= ' AND mainlcd=:mainlcd ';
            $args[':mainlcd'] = $mainlcd;
        }
        if ($remotelcd){
            $lcd .= ' AND remotelcd=:remotelcd ';
            $args[':remotelcd'] = $remotelcd;
        }

        $sql = sprintf('SELECT name, author, timestamp, mainlcd, approved, reason, description, shortname,
            zipfile, sshot_wps, sshot_menu, sshot_1, sshot_2, sshot_3,
            email, downloadcnt, ratings, numratings, filesize as size,
            emailverification = 1 as verified,
            themes.themeid as id,
            c.version_number AS current_version,
            c.pass AS current_pass,
            r.version_number as release_version,
            r.pass as release_pass,
            c.output as checkwps_output
            FROM themes
            LEFT OUTER JOIN checkwps c ON (themes.themeid=c.themeid and c.version_type="current")
            LEFT OUTER JOIN checkwps r ON (themes.themeid=r.themeid and r.version_type="release")
            WHERE (c.pass=1 OR r.pass=1) AND emailverification = 1 AND approved >= 1 %s GROUP BY name ORDER BY timestamp DESC',
            $lcd
        );

        $themes = $this->db->query($sql, $args);
        /* create additional data */
        while ($theme = $themes->next()) {
            if($theme['numratings'] > 0) $theme['ratings'] = $theme['ratings'] / $theme['numratings'];
            $ret[] = $theme;
        }
        return $ret;
    }

    public function downloadUrl($themeid) {
        $sql = 'SELECT mainlcd, shortname, zipfile FROM themes WHERE themeid=:id';
        $args = array(':id' => $themeid);
        $data = $this->db->query($sql, $args)->next();
        $url = sprintf('%s/%s/%s',$data['mainlcd'],$data['shortname'],$data['zipfile']);
        $cookiename = "downloadcnt_{$themeid}";

        /* prevent abuse by setting a cookie
         * it will expire after 3 min, then downloads will be counted again */
        if (!(isset($_COOKIE[$cookiename])))
        {
            $sql = 'UPDATE themes SET downloadcnt=downloadcnt+1 WHERE themeid=:id';
            $args = array(':id' => $themeid);
        }
        setcookie($cookiename, 'foo', time()+(3*60)); // 3 min

        $this->db->query($sql, $args);
        return $url;
    }


    public function target2lcd($shortname) {
        $sql = 'SELECT mainlcd, remotelcd, depth FROM targets WHERE shortname=:sn';
        $args = array(':sn' => $shortname);
        return $this->db->query($sql, $args)->next();
    }

    public function themenameexists($name, $mainlcd) {
        $sql = 'SELECT COUNT(*) as count FROM themes WHERE name=:name AND mainlcd=:mainlcd AND approved>=1';
        $args = array(':name' => $name, ':mainlcd' => $mainlcd);
        $result = $this->db->query($sql, $args)->next();
        return $result['count'] > 0 ? true : false;
    }

    public function adminworkneeded() {
        /* any reported themes ? */
        $sql = 'SELECT COUNT(*) as count FROM themes WHERE approved=2';
        $result = $this->db->query($sql)->next();
        return $result['count'] > 0 ? true : false;
    }

    public function themeisupdate($name, $mainlcd,$author,$email) {
        $sql = 'SELECT COUNT(*) as count FROM themes WHERE name=:name AND mainlcd=:mainlcd AND approved>=1 AND author=:author AND email=:email';
        $args = array(
            ':name' => $name,
            ':mainlcd' => $mainlcd,
            ':author' => $author,
            ':email' => $email
        );
        $result = $this->db->query($sql, $args)->next();
        return $result['count'] > 0 ? true : false;
    }

    public function changestatus($themeid, $newstatus, $oldstatus, $reason) {
        $status_text = array('2' => 'Reported', '1' => 'Approved', '0' => 'hidden', '-1' => 'deleted');
        $this->log(sprintf('Changing status of theme %d from %s to %s - Reason: %s',
            $themeid,
            $status_text[$oldstatus],
            $status_text[$newstatus],
            $reason
        ));
        $sql = 'SELECT shortname, mainlcd, email, name, author, zipfile FROM themes WHERE themeid=:id';
        $args = array(':id' => $themeid);
        $theme = $this->db->query($sql, $args)->next();

        if ($newstatus == -1) {
            $sql = 'DELETE FROM zipcontents WHERE themeid=:id';
            $args = array(':id' => $themeid);
            $this->db->query($sql, $args);
            $sql = 'DELETE FROM checkwps WHERE themeid=:id';
            $args = array(':id' => $themeid);
            $this->db->query($sql, $args);
            $sql = 'DELETE FROM themes WHERE themeid=:id';
            $args = array(':id' => $themeid);

            /* Delete the files */
            foreach(array($this->themedir_public, $this->themedir_private) as $root) {
                $dir = sprintf('%s/%s/%s',
                    $root,
                    $theme['mainlcd'],
                    $theme['shortname']
                );
                if (file_exists($dir)) {
                    foreach(glob(sprintf('%s/*', $dir)) as $file) {
                        unlink($file);
                    }
                    rmdir($dir);
                }
            }
        } else {
            $sql = 'UPDATE themes SET approved=:approved, reason=:reason WHERE themeid=:id';
            $args = array(
                ':approved' => $newstatus,
                ':reason' => $reason,
                ':id' => $themeid
            );

            $public = sprintf('%s/%s/%s/%s', $this->themedir_public, $theme['mainlcd'], $theme['shortname'], $theme['zipfile']);
            $private = sprintf('%s/%s/%s/%s', $this->themedir_private, $theme['mainlcd'], $theme['shortname'], $theme['zipfile']);
            if ($oldstatus == 0 && $newstatus >= 1) {
                rename($private, $public);
            }
            else if ($oldstatus >=1 && $newstatus == 0 ) {
                 rename($public, $private);
            }
        }
        if ($oldstatus >= 1 && $newstatus < 1) {
            // Send a mail to notify the user that his theme has been
            // hidden/deleted. No reason to distinguish, since the result
            // for him is the same.
            $to = sprintf('%s <%s>', $theme['author'], $theme['email']);
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
        $this->db->query($sql, $args);
    }

    public function addtarget($shortname, $fullname, $mainlcd, $pic, $depth, $remotelcd = false) {
        $this->log(sprintf('Add new target %s', $fullname));

        $sql = 'INSERT INTO targets (shortname, fullname, mainlcd, pic, depth, remotelcd)
                VALUES (:sn, :fn, :mainlcd, :pic, :depth, :remotelcd)';
        $args = array(
            ':sn' => $shortname,
            ':fn' => $fullname,
            ':mainlcd' => $mainlcd,
            ':pic' => $pic,
            ':depth' => $depth,
            ':remotelcd' => $remotelcd === false ? 'NULL' : $remotelcd
        );
        $this->db->query($sql, $args);
        /* Create the target's dir in both the private and public theme dir */
        foreach(array($this->themedir_public, $this->themedir_private) as $root) {
            $themedir = sprintf('%s/%s', $root, $mainlcd);
            if (!file_exists($themedir)) {
                mkdir($themedir);
            }
        }
    }

    public function edittarget($id, $shortname, $fullname, $mainlcd, $pic, $depth, $remotelcd = false) {
        $this->log(sprintf('Edit target %s', $fullname));

        $sql = 'UPDATE targets SET shortname=:sn, fullname=:fn, mainlcd=:mainlcd,
                pic=:pic, depth=:depth, remotelcd=:remotelcd WHERE themeid=:id';
        $args = array(
            ':sn' => $shortname,
            ':fn' => $fullname,
            ':mainlcd' => $mainlcd,
            ':pic' => $pic,
            ':depth' => $depth,
            ':remotelcd' => $remotelcd === false ? 'NULL' : $remotelcd,
            ':id' => $id
        );
        $this->db->query($sql, $args);
    }

    private function send_mail($subject, $to, $msg) {
        $msg = wordwrap($msg, 78);
        $headers = sprintf("From: %s", config::outboundemail);
        mail($to, $subject, $msg, $headers);
    }

    public function prepareverification($id, $email, $author) {
        $token = md5(uniqid());
        $sql = 'UPDATE themes SET emailverification=:emv WHERE themeid=:id';
        $args = array(':emv' => $token, ':id' => $id);
        $this->db->query($sql, $args);
        $url = sprintf('%s/%s/verify.php?t=%s', config::hostname, config::path, $token);
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
        $subject = 'Rockbox Theme Site email verification';
        $to = sprintf('%s <%s>', $author, $email);
        $this->send_mail($subject, $to, $msg);
    }

    public function verifyemail($token) {
        /* get theme details to search for updates */
        $sql = 'SELECT mainlcd, email, name, author FROM themes WHERE emailverification=:emv';
        $args = array(':emv' => $token);
        $searchtheme = $this->db->query($sql, $args)->next();
        /* hide potentially updated themes but keep the download count alive */
        $sql = 'SELECT themeid, approved, downloadcnt FROM themes WHERE mainlcd=:mainlcd AND name=:name AND email=:email AND author=:author AND approved>=1 AND emailverification=1';
        $args = array(
            ':mainlcd' => $searchtheme['mainlcd'],
            ':name' => $searchtheme['name'],
            ':email' => $searchtheme['email'],
            ':author' => $searchtheme['author']
        );
        $themes = $this->db->query($sql, $args);
        $dlcount = 0;
        while ($theme = $themes->next()) {
            $this->changestatus($theme['themeid'],0,$theme['approved'],'Theme was replaced by newer version.');
            /* the highest download count should be the newest one */
            if ($theme['downloadcnt'] > $dlcount)
                $dlcount = $theme['downloadcnt'];
        }
        /* change theme as verified */
        $sql = 'UPDATE themes SET emailverification=1, downloadcnt=:dlcount WHERE emailverification=:emv';
        $args = array(
            ':dlcount' => $dlcount,
            ':emv' => $token
        );
        $res = $this->db->query($sql, $args);
        return $res->rowsaffected();
    }

    public function addtheme($name, $shortname, $author, $email, $mainlcd, $remotelcd, $description, $zipfile, $sshot_wps, $sshot_menu,$sshot_1,$sshot_2,$sshot_3) {
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
        $sshot_wps['name']  = empty($sshot_wps['name'])  ? '' : 'wps-'.str_replace(' ', '_', $sshot_wps['name']);
        $sshot_menu['name'] = empty($sshot_menu['name']) ? '' : 'menu-'.str_replace(' ', '_', $sshot_menu['name']);
        $sshot_1['name'] = empty($sshot_1['name']) ? '' : '1-'.str_replace(' ', '_', $sshot_1['name']);
        $sshot_2['name'] = empty($sshot_2['name']) ? '' : '2-'.str_replace(' ', '_', $sshot_2['name']);
        $sshot_3['name'] = empty($sshot_3['name']) ? '' : '3-'.str_replace(' ', '_', $sshot_3['name']);

        /* Start moving files in place */
        $uploads = array($zipfile, $sshot_wps, $sshot_menu,$sshot_1,$sshot_2,$sshot_3);
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
        $sql = 'INSERT INTO themes (author, email, name, mainlcd, zipfile, sshot_wps, sshot_menu, sshot_1, sshot_2, sshot_3,
                  remotelcd, description, shortname, emailverification, timestamp, approved, downloadcnt, ratings, numratings)
                  VALUES (:author, :email, :name, :mainlcd, :zipf, :sswps, :ssmenu, :ss1, :ss2, :ss3, :remotelcd, :desc, :sn, 0, datetime("now"), :app, 0, 0, 0)';
        $args = array(
            ':author' => $author,
            ':email' => $email,
            ':name' => $name,
            ':mainlcd' => $mainlcd,
            ':zipf' => $zipfile['name'],
            ':sswps' => $sshot_wps['name'],
            ':ssmenu' => $sshot_menu === false ? 'NULL' : $sshot_menu['name'],
            ':ss1' => $sshot_1 === false ? 'NULL' : $sshot_1['name'],
            ':ss2' => $sshot_2 === false ? 'NULL' : $sshot_2['name'],
            ':ss3' => $sshot_3 === false ? 'NULL' : $sshot_3['name'],
            ':remotelcd' => $remotelcd === false ? 'NULL' : $remotelcd,
            ':desc' => $description,
            ':sn' => $shortname,
            ':app' => config::defaultstatus
        );
        $result = $this->db->query($sql, $args);
        $id = $result->insertid();
        $this->checkallthemes($id, 1);  // We want to check it against stable!
        $this->log(sprintf("Added theme %d (email: %s)", $id, $email));
        return $id;
    }

    public function updatetheme($id, $name, $mainlcd, $author, $email, $description) {
        $sql = 'UPDATE themes SET name=:name, mainlcd=:mainlcd, author=:author, email=:email, description=:desc WHERE themeid=:id';
        $args = array(
            ':name' => $name,
            ':mainlcd' => $mainlcd,
            ':author' => $author,
            ':email' => $email,
            ':desc' => $description,
            ':id' => $id
        );
        $this->db->query($sql, $args);
    }

    public function ratetheme($id,$rating) {
        /* prevent abusing with a cookie which virtually never expires
         * so one can only rate a theme once */
        $cookiename = "rating_{$id}";
        if(!isset($_COOKIE[$cookiename]) && $rating >= 0 && $rating <= 10)
        {
            $sql = 'UPDATE themes SET ratings=ratings+:rating, numratings=numratings+1 WHERE themeid=:id';
            $args = array(
                ':rating' => $rating,
                ':id' => $id
            );
            $this->db->query($sql, $args);
        }
        setcookie($cookiename, "bar", time()+(60*60*24*365*10)); // 10 years
    }

    /*
     * Use this rather than plain pathinfo for compatibility with PHP<5.2.0
     */
    private function my_pathinfo($path) {
        $pathinfo = pathinfo($path);
        if(!isset($pathinfo['extension'])){ $pathinfo['extension'] = ''; }
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
        $ret = '';
        zip_entry_open($zip, $ze);
        while($read = zip_entry_read($ze)) {
            $ret .= $read;
        }
        zip_entry_close($ze);
        return $ret;
    }

    public function allowedsettings()
    {
        $ret = array();
        $results=$this->db->query('SELECT name, type FROM settings');
        while ($result = $results->next()) {
            $ret[] = $result;
        }
        return $ret;
    }

    public function addsetting($name,$type)
    {
        $this->log(sprintf("Add new setting %s %s", $name,$type));

        $sql = 'INSERT INTO settings (name, type) VALUES (:name, :type)';
        $args = array(
            ':name' => $name,
            ':type' => $type
        );
        $this->db->query($sql, $args);
    }

    /*
     * Check if the settings are all in the allowed list. If they are of filetype, check if file exists
     */
    public function validatecfg($cfg, $files) {
        $settings = $this->allowedsettings();
        foreach(explode("\n", $cfg) as $line) {
            if (preg_match("/\s*#/", $line)) continue;
            preg_match("/^(?P<name>[^:]*)\s*:\s*(?P<value>[^#]*)\s*$/", $line, $matches);
            if (count($matches) > 0) {
                extract($matches);
                /* check if it is in the list of allowed settings */
                $found =false;
                foreach($settings as $setting)
                {
                    if($setting['name'] == $name)
                    {
                        /* check file type settings */
                        if($setting['type'] == 'file')
                        {
                            $value_info = $this->my_pathinfo($value);
                            /* fonts from the fontpack dont need to exist
                             * also accept '-' filenames used to explicitely
                             * deactivate loading a file/generated by the
                             * write theme settings feature in rb */
                            $fname = preg_replace('/\.fnt\s*$/','',$value_info['filename']);
                            if ((preg_match('/\.fnt\s*$/', $value) && $this->isfontpackfont($fname)) ||
                                (preg_match('/\.bmp\s*$/', $value) && $this->isrockbboxicon($fname)) ||
                                (preg_match('/^\s*-\s*$/', $value)) )
                            {
                                $found = true;
                                break;
                            }

                            /* check if filename exists in $files */
                            $foundfile = false;
                            foreach($files as $file)
                            {
                                $file_info = $this->my_pathinfo($file);
                                if($file_info['filename'] == $value_info['filename'])
                                {
                                    $foundfile=true;
                                    break;
                                }
                            }
                            if($foundfile == false)
                                return sprintf("The file %s from the setting '%s' doesnt exist.",$value,$name);
                        } elseif ($setting['type'] == 'viewport') {
                             $array = explode(",", $value);
                             if (count($array) == 1) {
                                if ($array[0] !== '-' && $array[0] !== '')
                                    return sprintf("The '%s' setting is malformed.",$name);
                             } elseif (count($array) != 7) {
                                return sprintf("The '%s' setting is malformed.",$name);
                             }
                        }
                        /* all other setting types are currently unchecked */
                        $found = true;
                        break;
                    }
                }
                if($found == false)
                    return sprintf("%s is not an allowed theme setting.",$name);
            }
        }
        return '';
    }

    public function lcd2targets($lcd) {
        $sql = 'SELECT shortname, remotelcd FROM targets WHERE mainlcd=:mainlcd';
        $args = array(':mainlcd' => $lcd);
        return $this->db->query($sql, $args);
    }

    /*
     * Check a WPS against two revisions: current and the latest release
     */
    public function checkwps($zipfile, $mainlcd, $remotelcd, $release = 0) {
        $return = array();

        /* First, create a temporary dir */
        $tmpdir = sprintf('/tmp/temp-%s', md5(uniqid()));
        mkdir($tmpdir);

        /* Then, unzip the theme here */
        $cmd = sprintf('%s -d %s %s', config::unzip, $tmpdir, escapeshellarg($zipfile));
        exec($cmd, $dontcare, $ret);

        /* Now, cd into that dir */
        $olddir = getcwd();
        chdir($tmpdir);

        /*
         * For all .wps and .rwps, run checkwps of both release and current for
         * all applicable targets
         */
        /* get list of targets to check */
        $targets =  $this->lcd2targets($mainlcd);
        /* for every target */
        while($target = $targets->next()){
            /* for both versions */
            foreach(array('release', 'current') as $version) {
                if ($release == 0 && $version == 'release') {
                     continue;
                 }
                /* for every skin file in the theme */
                foreach(glob('.rockbox/wps/*{wps,sbs,fms}',GLOB_BRACE) as $file) {
                    $p = $this->my_pathinfo($file);
                    /* skip file if it is a remote file, and remote resolution doesnt fit (ie remotechecking is optional on targets without native remote lcd resolution */
                    if(($p['extension'] == 'rwps' || $p['extension'] == 'rsbs' || $p['extension'] == 'rfms') && ($target['remotelcd'] != $remotelcd))
                        continue;

                    $result = array();
                    /* Read in version info */
                    $vfn = sprintf('%s/checkwps/%s/VERSION.%s',
                        preconfig::privpath,
                        $version,
                        $target['shortname']);
                    if (file_exists($vfn)) {
                        $result['version'] = trim(file_get_contents($vfn));
                    } else {
                        $vfn = sprintf('%s/checkwps/%s/VERSION',
                            preconfig::privpath,
                            $version);
                        $result['version'] = trim(file_get_contents($vfn));
                    }
                    /* run checkwps */
                    $checkwps = sprintf('%s/checkwps/%s/checkwps.%s',
                        preconfig::privpath,
                        $version,
                        $target['shortname']
                    );
                    if (file_exists($checkwps)) {
                        exec(sprintf('%s %s', $checkwps, escapeshellarg($file)), $output, $ret);
                        $result['pass'] = ($ret == 0);
                        $result['output'] = $output;
                        /* only overwrite results if there is no previous result or previous did pass */
                        if(empty($return[$version][$target['shortname']]) || $return[$version][$target['shortname']]['pass']) {
                            $return[$version][$target['shortname']] = $result;
                        }
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
            $path = sprintf('%s/%s', $dir->path, $entry);
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

    private function isfontpackfont($name) {
        $ourfonts = glob(sprintf('%s/*bdf', config::fontdir));
        foreach($ourfonts as &$font) {
            $font = basename($font, '.bdf');
        }
        return in_array($name, $ourfonts);
    }

    private function isrockbboxicon($name) {
        $ouricons = glob(sprintf('%s/*bmp', config::icondir));
        foreach($ouricons as &$icon) {
            $icon = basename($icon, '.bmp');
        }
        return in_array($name, $ouricons);
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
            $err[] = sprintf("'Couldn't open zipfile %s", $themezipupload['name']);
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
                $err[] = sprintf('File outside /.rockbox/: %s', $filename);

            /* Check if the font is already included in Rockbox */
            if (strtolower($pathinfo['extension']) == 'fnt') {
                if ($this->isfontpackfont($pathinfo['filename'])) {
                    $err[] = sprintf("This font is included in the Rockbox font pack. Don't include it in your theme: %s", $filename);
                }
            }

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
                        $err[] = sprintf('Filename invalid: %s (should be %s.%s)', $filename, $shortname, $pathinfo['extension']);
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
                    $err[] = sprintf('Invalid dirname: %s (should be %s.)', $filename, $shortname);
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
                $err[] = sprintf('Non-bmp file not allowed here: %s', $filename);
            }

            /* Check for paths that are too deep */
            if (count(explode('/', $pathinfo['dirname'])) > 3) {
                $err[] = sprintf('Path too deep: %s', $filename);
            }

            /* Check for unwanted junk files */
            switch(strtolower($pathinfo['basename'])) {
                case 'thumbs.db':
                case 'desktop.ini':
                case '.ds_store':
                case '.directory':
                    $err[] = sprintf('Unwanted file: %s', $filename);
            }
        }

        /* Now we check all the things that could be wrong */
        $error = $this->validatecfg($cfg, $files);
        if($error != '')
            $err[] = $error;
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
        $dimensions = sprintf('%dx%d', $size[0], $size[1]);
        $lcdsize = explode('x',$mainlcd);
        if ($size === false) {
            $err[] = sprintf("Couldn't open screenshot %s", $upload['name']);
        }
        else {
            //workaround for archos player screenshots
            if($lcdsize[0] == 11 && $lcdsize[1] == 2) {
                $lcdsize[0] = 132;
                $lcdsize[1] = 64;
            }
            //check size
            if ($lcdsize[0] > ($size[0]+2) || $lcdsize[1] > ($size[1]+2) || $lcdsize[0] < ($size[0]-2) || $lcdsize[1] < ($size[1]-2) ) {
                $err[] = sprintf("Wrong resolution of %s. Should be %dx%d (is %s).", $upload['name'], $lcdsize[0], $lcdsize[1], $dimensions);
            }
            if ($size[2] != IMAGETYPE_PNG) {
                $err[] = 'Screenshots must be of type PNG.';
            }
        }
        return $err;
    }
}
?>
