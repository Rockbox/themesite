<?php
require_once('config.php');

# Filter the themes.txt by LCD type and return an array of matching themes
function filter($mainlcdfilter, $remotelcdfilter)
{
    $count = 0;

    $fh = fopen(THEMES, "r");
    if ($fh)
    {
        while ((list($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date) = fgetcsv($fh, 1000, "|")) !== FALSE)
        {
            if ($mainlcd==$mainlcdfilter)
                $themes[$count++] = array($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date);
        }
        fclose($fh);
    }

    if ($count==0)
        return array();
    else
        return $themes;
}

# Validate an uploaded theme zip file, exercising extreme paranoia

function validate_zip($filename, $model)
{
    $errors = array();

    $validdir['wps']=1;
    $validdir['themes']=1;
    $validdir['backdrops']=1;
    $validdir['icons']=1;
    $validdir['fonts']=1;

    # Step 1 - get a listing of the files inside the zip file
    $fh = popen(UNZIP." -l $filename","r");
    if (!$fh)
        return array('Not a valid ZIP file');

    $buf = '';
    while (!feof($fh))
        $buf .= fgets($fh, 4096);
    pclose($fh);

    $recs = split("\n",$buf);

    # Do some sanity checks on the unzip output
    if(count($recs) == 7) # Number of lines with one file in the zip
        return array("Zip contains only 1 file.");

    if (count($recs) < 7 ||
        ($recs[1] != "  Length     Date   Time    Name") ||
        ($recs[2] != " --------    ----   ----    ----"))
    {
         return array('Unexpected ZIP file error.');
    }

    # Check the total uncompressed size
    $s = preg_replace('/\ +/'," ",$recs[count($recs)-2]);
    $s = preg_replace('/^\ +/','',$s);
    $data = split(" ",$s);
    $count = count($data);
    if ($data[$count-1] != 'files')
         return array('Unexpected ZIP file error.');

    if ($data[$count-2] > MAXFILESINZIP)
        return array("Too many files in ZIP file ($numfiles)");

    if ($data[0] > MAXUNZIPPEDSIZE)
        return array("ZIP contents too large ($size bytes)");

    # Now go through each file in turn.

    # Check for exactly 1 .wps file
    # Check for 0 or 1 .rwps file
    # Check filetypes (extensions) in each subdir
    # Check filenames of .wps, .rwps and .cfg match, and is same as name of dir in wps/
    # Check for exactly one subdir in wps/

    for ($i=3;$i<count($recs)-3;$i++)
    {
        $f = substr($recs[$i], strpos($recs[$i], '.rockbox/'));

        if ($f=='.rockbox/')
            continue;

        $a = split('/',$f);

        if ($a[0] != '.rockbox')
        {
            $errors[] = "Not in .rockbox - $f";
            continue;
        }

        # Check if the directory structure is too deep
        if (count($a) > MAXTHEMEPATHDEPTH)
        {
            $errors[] = "Invalid directory structure for $f";
            continue;
        }

        # If there are two elements in the path...
        if (count($a)==2)
        {
            # If this is a directory name, check it
            if (substr($f,strlen($f)-1,1)=='/')
            {
                 if ($validdir[$a[1]] != 1)
                     $errors[] = "Invalid directory - $f";

                 continue;
            }
            else
            {
                # Else, it's a file and shouldn't be in .rockbox
                $errors[] = "Invalid file in .rockbox - $f";
                continue;
            }
        }

        # We know there are at least 3 elements in path
        if ($validdir[$a[1]] != 1)
            $errors[] = "Invalid directory - $f";

        # Check for known bad files
        switch(strtolower($a[count($a)-1]))
        {
            case "thumbs.db":
            case "desktop.ini":
            case ".ds_store":
            case ".directory":
                $errors[] = "Invalid file - $f";
            break;
        }
    }

    if(count($errors) == 0)
    {
        $checked = array();

        $tempname = tempnam(TMPDIR, "rbthemes-");
        if (!$tempname)
            die("Cannot create a temporary file!");
        $tmp_path = $tempname."_dir";
        if(!mkdir($tmp_path))
            die("Cannot create a temporary directory!");

        exec(UNZIP." -d $tmp_path $filename");
        foreach(glob("$tmp_path/.rockbox/backdrops/*") as $bmp)
        {
            $lcd_size = $model->lcd_size();
            switch(validate_bmp($bmp, $lcd_size))
            {
                case -1:
                    $errors[] = "Backdrop isn't a valid BMP - ".substr($bmp, strlen($tmp_path)+1);
                    break;
                case -2:
                    $errors[] = "Backdrop must be ".$lcd_size." - ".substr($bmp, strlen($tmp_path)+1);
                    break;
            }
            $checked[] = $bmp;
        }
        foreach(glob("$tmp_path/.rockbox/wps/*/*") as $bmp)
        {
            if(validate_bmp($bmp, false) != 0)
                $errors[] = "File isn't a valid BMP - ".substr($bmp, strlen($tmp_path)+1);
            $checked[] = $bmp;
        }
        foreach(glob("$tmp_path/.rockbox/wps/*.*wps") as $wps)
        {
            $ret = shell_exec(CHECKWPS.".".$model->checkwps." \"$wps\"");
            $ret = explode("\n", $ret);
            foreach($ret as $el)
            {
                if(strstr($el, "ERR: ") !== false)
                    $errors[] = "WPS validation error: ".htmlspecialchars($el)." - ".substr($wps, strlen($tmp_path)+1);
            }
            $checked[] = $wps;
        }
        foreach(glob("$tmp_path/.rockbox/themes/*.cfg") as $cfg)
        {
            $ret = file_get_contents($cfg);
            $ret = explode("\n", $ret);
            foreach(@$ret as $el)
            {
                if(substr(trim($el), 0, 1) != "#")
                {
                    $el = explode(":", trim($el));
                    $path = $tmp_path.trim(@$el[1]);
                    $path_disp = htmlspecialchars(substr($path, strlen($tmp_path)+1));
                    if(array_search($path, $checked) !== false)
                    {
                        switch(strtolower($el[0]))
                        {
                            case "wps":
                                if(substr(dirname($path), 0, strlen($tmp_path)) != $tmp_path
                                   || !file_exists($path))
                                    $errors[] = "WPS in config doesn't exist: ".$path_disp;
                                else
                                {
                                    $ret = shell_exec(CHECKWPS.".".$model->checkwps." \"$path\"");
                                    $ret = explode("\n", $ret);
                                    foreach($ret as $el)
                                    {
                                        if(strstr($el, "ERR: ") !== false)
                                            $errors[] = "WPS validation error: ".htmlspecialchars($el)." - ".substr($wps, strlen($tmp_path)+1);
                                    }
                                    $checked[] = $path;
                                }
                            break;
                            case "backdrop":
                                if(substr(dirname($path), 0, strlen($tmp_path)) != $tmp_path
                                   || !file_exists($path))
                                    $errors[] = "Backdrop in config doesn't exist: ".$path_disp;
                                else
                                {
                                    $lcd_size = $model->lcd_size();
                                    switch(validate_bmp($path, $lcd_size))
                                    {
                                        case -1:
                                            $errors[] = "Backdrop isn't a valid BMP - ".$path_disp;
                                            break;
                                        case -2:
                                            $errors[] = "Backdrop must be ".$lcd_size." - ".$path_disp;
                                            break;
                                    }
                                    $checked[] = $path;
                                }
                            break;
                            case "font":
                            case "iconset":
                            case "viewers iconset":
                                if(substr(dirname($path), 0, strlen($tmp_path)) != $tmp_path
                                   || !file_exists($path))
                                    $errors[] = "Path in config doesn't exist: ".$path_disp;
                                else
                                    $checked[] = $path;
                            break;
                        }
                    }
                }
            }
        }
        exec("rm -R -f $tmp_path");
        unlink($tempname);
    }

    return $errors;
}

function validate_bmp($bmp, $dimensions=false)
{
    return validate_image($bmp, "BMP", $dimensions);
}

function validate_png($filename, $dimensions=false)
{
    if(validate_image($filename, "PNG", $dimensions) !== 0)
        return false;
    else
        return true;
}

function validate_image($filename, $type, $dimensions=false)
{
    $ret = getimagesize($filename);
    if($ret === false)
        return -3;
    switch($ret["mime"])
    {
        case "image/png":
            if($type != "PNG")
                return -1;
            break;
        case "image/bmp":
            if($type != "BMP")
                return -1;
            break;
    }
    if($dimensions != false)
    {
        $size = $ret[0]."x".$ret[1];
        if($dimensions != $size)
            return -2;
    }
    return 0;
}

function get_new_id()
{
    $fh = fopen(THEMES, "r");
    if($fh)
    {
        while(($el = fgetcsv($fh, 1000, "|")) !== FALSE)
            $ret = ((int)$el[0])+1;
        fclose($fh);

        $fh = fopen(PRE_THEMES, "r");
        while(($el = fgetcsv($fh, 1000, "|")) !== FALSE)
            $ret = max($ret, ((int)$el[0])+1);
        fclose($fh);

        if (!$ret) //Make sure we return an ID when there is no theme added yet.
            $ret = 1;

        return $ret;
    }
    else
        return false;
}

function convert_to_png($uploaded_file, $filename, $lcd_size)
{
    rename($uploaded_file, $uploaded_file.$filename);
    
    $ret = system(IMAGEMAGICK." ".
                  "'".$uploaded_file.$filename."'".
                  " -resize $lcd_size ".
                  "'".$uploaded_file.".png'");
    
    unlink($uploaded_file.$filename);
    
    if(strlen($ret) > 0)
        return false;
    else
        return $uploaded_file.".png";
}
?>