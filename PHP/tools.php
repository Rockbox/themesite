<?php
require_once('config.php');

# Filter the themes.txt by LCD type and return an array of matching themes
function filter($mainlcdfilter, $remotelcdfilter=false, $pre_themes=false)
{
    $count = 0;

    $fh = fopen(($pre_themes ? PRE_THEMES : THEMES), "r");
    if ($fh)
    {
        while( ($line = fgetcsv($fh, 1000, "|")) !== FALSE )
        {
            if(count($line) > 1)
            {
                list($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date) = $line;
                if($mainlcd == $mainlcdfilter)
                    $themes[$count++] = array($id,$name,$shortname,$img1,$img2,$author,$email,$mainlcd,$remotelcd,$description,$date);
            }
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
    $buf = shell_exec(UNZIP." -l $filename");
    if($buf === false)
        return array('Not a valid ZIP file');

    $recs = explode("\n", $buf);

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
        return array("Too many files in ZIP file (".$data[$count-2].")");

    if ($data[0] > MAXUNZIPPEDSIZE)
        return array("ZIP contents too large (".$data[0]." bytes)");

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
            $errors = array_merge($errors, check_wps($wps, $model));
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
                                    $errors = array_merge($errors, check_wps($path, $model));
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

function human_filesize($size)
{
    $units = explode(' ','B KiB MiB GiB TiB PiB');
    for ($i = 0; $size > 1024; $i++)
        $size /= 1024;

    return round($size, 2).' '.$units[$i];
}

function check_resolution($resolution)
{
	global $models;

	foreach($models as $model)
	{
		if($model->display == $resolution)
			return true;
	}

	return false;
}

function check_model($model_name)
{
    global $models;
    
    return isset($models[$model_name]);
}

function check_wps($wps, $model)
{
    $errors = array();

    $ret = shell_exec(CHECKWPS.".".$model->checkwps." \"$wps\"");
    if($ret === false)
        return array('Unable to run checkwps.'.$model->checkwps);
    foreach(explode("\n", $ret) as $el)
    {
        if(strstr($el, "ERR: ") !== false)
            $errors[] = "WPS validation error: ".htmlspecialchars($el)." - ".basename($wps);
    }

    return $errors;
}

function get_theme($id, $location)
{
    $themes = explode("\n", file_get_contents($location));
    foreach($themes as $theme)
    {
        if(strlen($theme)>0)
        {
            $ret = explode("|", $theme);
            if($ret[0] == $id)
                return $ret;
        }
    }
    return false;
}

function count_themes($mainlcd, $remotelcd=false, $pre_themes=false)
{
    return count(filter($mainlcd, $remotelcd, $pre_themes));
}

class theme
{
    public $id;
    public $name;
    public $shortname;
    public $img1;
    public $img2;
    public $author;
    public $email;
    public $mainlcd;
    public $remotelcd;
    public $description;
    public $date;

    function __construct($init)
    {
        $this->id = $init[0];
        $this->name = $init[1];
        $this->shortname = $init[2];
        $this->img1 = $init[3];
        $this->img2 = $init[4];
        $this->author = $init[5];
        $this->email = $init[6];
        $this->mainlcd = $init[7];
        $this->remotelcd = $init[8];
        $this->description = $init[9];
        $this->date = $init[10];
        
    }
    
    function resolution($remote=false)
    {
        /* Drop last element from LCD size */
        $lcd_size = explode("x", ($remote ? $this->remotelcd : $this->mainlcd));
        array_pop($lcd_size);
        return implode("x", $lcd_size);
    }
    
    private function path($url, $pre_theme)
    {
        if($pre_theme)
            return ($url ? PRESITEDIR : PREDATADIR);
        else
            return ($url ? SITEDIR : DATADIR);
    }
    
    function zip($url=false, $pre_theme = false)
    {
        return $this->path($url, $pre_theme)."/".$this->mainlcd."/".$this->shortname.".zip";
    }
    
    function image($url=false, $pre_theme = false)
    {
        return $this->path($url, $pre_theme)."/".$this->mainlcd."/".$this->shortname.".png";
    }
    
    function image2($url=false, $pre_theme = false)
    {
        return $this->path($url, $pre_theme)."/".$this->mainlcd."/".$this->shortname."_b.png";
    }
}
?>