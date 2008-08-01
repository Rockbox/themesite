<?php

include 'ini.php';

# Some values for sanity checks of uploaded zip files
$maxfilesinzip = 100;
$maxunzippedsize = 1000000;  # In bytes

# The main list of devices.

$models = array('player','recorder','recorder8mb','fmrecorder','recorderv2','ondiofm','ondiosp','iaudiom5','iaudiox5','iaudiom3','h100','h120','h300','h10_5gb','h10','ipod1g2g','ipod3g','ipod4gray','ipodcolor','ipodvideo','ipodvideo64mb','ipodmini1g','ipodmini2g','ipodnano','gigabeatf','sansae200','sansac200','mrobe100');

$nummodels = count($models);

$modelnames = array('Archos Player/Studio','Archos Recorder v1','Archos Recorder 8MB','Archos FM Recorder','Archos Recorder v2','Archos Ondio FM','Archos Ondio SP','iAudio M5','iAudio X5','iAudio M3','iriver H100/115','iriver H120/140','iriver H320/340','iriver H10 5GB','iriver H10 20GB','iPod 1st and 2nd gen','iPod 3rd gen','iPod 4th gen Grayscale','iPod color/Photo','iPod Video 30GB','iPod Video 60/80GB','iPod Mini 1st gen','iPod Mini 2nd gen','iPod Nano 1st gen','Toshiba Gigabeat F/X','SanDisk Sansa e200','SanDisk Sansa c200','Olympus M-Robe 100');

$mainlcdtypes = array('charcell','112x64x1','112x64x1','112x64x1','112x64x1','112x64x1','112x64x1','160x128x2','160x128x16','128x96x2','160x128x2','160x128x2','220x176x16','128x128x16','160x128x16','160x128x2','160x128x2','160x128x2','220x176x16','320x240x16','320x240x16','138x110x2','138x110x2','176x132x16','240x320x16','176x220x16','132x80x16','160x128x1');

$imagenames = array('player-small.png','recorder-small.png','recorder-small.png','recorderv2fm-small.png','recorderv2fm-small.png','ondiofm-small.png','ondiosp-small.png','m5-small.png','x5-small.png','m3-small.png','h100-small.png','h100-small.png','h300-small.png','h10_5gb-small.png','h10-small.png','ipod1g2g-small.png','ipod3g-small.png','ipod4g-small.png','ipodcolor-small.png','ipodvideo-small.png','ipodvideo-small.png','ipodmini-small.png','ipodmini-small.png','ipodnano-small.png','gigabeatf-small.png','e200-small.png','c200-small.png','mrobe100-small.png');

function get_modelid($model)
{
    global $models;
	
	return array_search($model, $models);
}

function show_main_table()
{
    global $nummodels;
    global $modelnames,$imagenames,$models;

    print "<p><table class=\"rockbox\" cellpadding=\"0\">\n";
    for ($i=0;$i<$nummodels;$i++)
    {
       if (($i % 7) == 0) {
           if ($i > 0) {
              print "</tr>\n";
           }
           print "<tr valign=\"top\">";
       }
       print "<td align=\"center\"><a href=\"index.php?model=$models[$i]\"><img border=\"0\" src=\"http://www.rockbox.org/playerpics/$imagenames[$i]\" alt=\"$modelnames[$i]\" /><p>$modelnames[$i]</a></td>\n";
    }
    print "</table></p>\n";
}

# Filter the themes.txt by LCD type and return an array of matching themes
function filter($mainlcdfilter,$remotelcdfilter)
{
    global $DATADIR;

    $count = 0;

    $fh = fopen("$DATADIR/themes.txt", "r");
    if ($fh)
    {
        while ((list($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date) = fgetcsv($fh, 1000, "|")) !== FALSE)
        {
            if ($mainlcd==$mainlcdfilter)
            {
                $themes[$count++] = array($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date);
            }
        }
    }
    fclose($fh);

    if ($count==0) 
    {
        return array();
    } else {
        return $themes;
    }
}    

# Validate an uploaded theme zip file, exercising extreme paranoia

function validate_zip($filename)
{
    GLOBAL $maxfilesinzip;
    GLOBAL $maxunzippedsize;

    $nerrs = 0;

    $validdir['wps']=1;
    $validdir['themes']=1;
    $validdir['backdrops']=1;
    $validdir['icons']=1;
    $validdir['fonts']=1;

    # Step 1 - get a listing of the files inside the zip file
    $fh = popen("/usr/bin/unzip -l $filename","r");
    if (!$fh)
    {
        return 'Not a valid ZIP file';
    }
    $buf = '';
    while (!feof($fh)) 
    {
        $buf .= fgets($fh, 4096);
    }
    pclose($fh);

    $recs = split("\n",$buf);

    # Do some sanity checks on the unzip output
    if ((count($recs) < 7) ||  # Number of lines with one file in the zip
        ($recs[1] != "  Length     Date   Time    Name") ||
        ($recs[2] != " --------    ----   ----    ----"))
    {
         return array('Unexpected ZIP file error.');
    }

    # Check the total uncompressed size
    $s = preg_replace('/\ +/'," ",$recs[count($recs)-2]);
    $s = preg_replace('/^\ +/','',$s);
    list($size,$numfiles,$s) = split(" ",$s);
    if ($s != 'files')
    {
         return array('Unexpected ZIP file error.');
    }

    if ($numfiles > $maxfilesinzip)
        return array("Too many files in ZIP file ($numfiles)");

    if ($size > $maxunzippedsize)
        return array("ZIP contents too large ($size bytes)");

    # Now go through each file in turn.

    # Check for exactly 1 .wps file
    # Check for 0 or 1 .rwps file
    # Check filetypes (extensions) in each subdir
    # Check filenames of .wps, .rwps and .cfg match, and is same as name of dir in wps/
    # Check for exactly one subdir in wps/

    # TODO: Return an array of errors, not just the first one...

    for ($i=3;$i<count($recs)-3;$i++)
    {
        $f = substr($recs[$i],28);

        if ($f=='.rockbox/')
            continue;

        $a = split('/',$f);

        if ($a[0] != '.rockbox')
        {
            $errors[$nerrs++] = "Not in .rockbox - $f";
            continue;
        }

        # Check if the directory structure is too deep
        if (count($a) > 4)
        {
            $errors[$nerrs++] = "Invalid directory structure for $f";
            continue;
        }

        # If there are two elements in the path...
        if (count($a)==2)
        {
            # If this is a directory name, check it
            if (substr($f,strlen($f)-1,1)=='/')
            {
                 if ($validdir[$a[1]] != 1)
                     $errors[$nerrs++] = "Invalid directory - $f";

                 continue;
            }
            else
            {
                # Else, it's a file and shouldn't be in .rockbox
                $errors[$nerrs++] = "Invalid file in .rockbox - $f";
                continue;
            }
        }        

        # We know there are at least 3 elements in path
        if ($validdir[$a[1]] != 1)
        {
            $errors[$nerrs++] = "Invalid directory - $f";
        }

        # Check for known bad files        
        if (strtolower($a[count($a)-1]) == 'thumbs.db')
        {
            $errors[$nerrs++] = "Invalid file - $f";
        }

        #print "<p>$i - $f (".strtolower($a[count($a)-1]).")</p>\n";
    }

    if ($nerrs==0)
        return array();
    else
        return $errors;
}

?>
