<?php 

namespace Sloway;       

class slideshow {   
	public static $loaded = array();
	public static function preload($module) {
		if (isset(self::$loaded[$module])) 
			return self::$loaded[$module];

		$ssl = mlClass::load("slideshow", "@module = '$module'", 0, null, array("index" => "id"));

		$pids = implode(",", array_keys($ssl));
		$slides = ($pids) ? mlClass::load("slideshow_slide", "@id_parent IN ($pids) AND visible = 1") : array();

		$sl = array();
		foreach ($slides as $slide) {
			$pid = $slide->id_parent;
			if (!isset($sl[$pid])) $sl[$pid] = array();
			$sl[$pid][]= $slide;
		}
		
		$data = array();
		foreach ($ssl as $ss) {
			if (isset($sl[$ss->id]))
				$ss->slides = $sl[$ss->id];
				
			$data[$ss->media] = $ss;
		}
		self::$loaded[$module] = $data;
		return $data;
	}
    public static function calc_size($result) {
        $image_size = null;
        $height = null;
        foreach ($result->slides as $slide) {
            if (is_null($height)) {
                $image_size = getimagesize(path::gen("root.uploads", $slide->background));
                $height = $image_size[1];
            }
            $slide->background = path::gen("site.uploads", $slide->background);
        }
        $result->aspect_ratio = null;
        $s = trim($result->size);
        
		$e = null;
        if (strlen($s)) {
            $e = explode(",", $s);
            if (isset($e[0])) {
                $height = $e[0];
                if ($height == "%") {
                    $result->aspect_ratio = $image_size[1] / $image_size[0];
                    $height = null;
                } else
                if (strpos($height, "%")) {
                    $result->aspect_ratio = str_replace("%", "", $height) / 100;
                    $height = null;
                } 
            }
        }
        $result->height = $height;
        $result->min_height = intval(v($e, "1", 0));
        $result->max_height = intval(v($e, "2", 0));
        if (!$result->min_height) $result->min_height = $height;
        if (!$result->max_height) $result->max_height = $height;
    }
    public static function create($slides, $size) {
        $result = genClass::create();
        $result->slides = $slides;
        $result->size = $size;
        
        self::calc_size($result);
        
        return $result;
    }
    public static function load($module, $media) {
        $db = core::$db;
        $list = array_reverse(config::get("slideshow.media"));
        $result = null;

		//self::preload($module);
        
        $ssl = array();
        $i = 0;
        $index = 0;
        foreach ($list as $name => $width) {
            if ($name == $media) $index = $i;
            $q1 = $db->query("SELECT id FROM slideshow WHERE module = ? AND media = ?", [$module, $name])->getResult();
            $q2 = $db->query("SELECT COUNT(id) as cnt FROM slideshow_slide WHERE id_parent = ? AND visible = 1", [count($q1) ? $q1[0]->id : 0])->getResult();
            
            $ssl[$i] = $q2[0]->cnt ? $q1[0]->id : null;
            $i++;
        }
        
        if (!$ssl[$index]) {
            $f = false;
        //  Find next bigger
            for ($i = $index+1; $i < count($ssl); $i++) {
                if ($ssl[$i]) {
                    $index = $i;
                    $f = true;
                    break;
                }
            }
            
        //  Find next smaller                            
            if (!$f) {
                for ($i = $index-1; $i >= 0; $i--) {
                    if ($ssl[$i]) {
                        $index = $i;
                        $f = true;
                        break;
                    }
                }
            }
        }
        
        $id = $ssl[$index];        
        $result = dbClass::load_def("slideshow", "@id = '$id'", 1);  
        if (!$result->size)
            $result->size = config::get("slideshow.size.$media"); 

        $result->slides = mlClass::load("slideshow_slide", "@id_parent = '$id' AND visible = 1 ORDER BY id_order ASC");  
        foreach ($result->slides as $slide) {
            if (preg_match('/^.*\.(mp4|mov)$/i', $slide->background))
                $slide->type = "video"; else
                $slide->type = "image";
        }
        
        self::calc_size($result);
        
        return $result;
    }
}     

