<?php

namespace Sloway;
use Sloway\mlClass;
use Sloway\path;

class images {
	public static function load($module, $module_id, $vis_only = true, $limit = 0, $index = null) {
		$sql = "@module = '$module' AND module_id = $module_id";
		if ($vis_only)
			$sql.= " AND visible = 1";
		
		$sql.= " ORDER BY id_order ASC";
		if ($limit)
			$sql.= " LIMIT $limit";
            
		return mlClass::load('images', $sql, 0, array('index' => $index));
	}
    public static function load_site($module, $module_id, $limit = 0, $default = null) {
        $imgs = self::load($module, $module_id, true, $limit);
        $res = array();
        foreach ($imgs as $img) 
            $res[]= path::gen("site.uploads", $img->path); 
            
        if ($limit == 1)
            return count($res) ? $res[0] : $default; 
        
        return count($res) ? $res : $default;
    }
    public static function load_thumb($module, $module_id, $template, $limit = 0, $default = null) {
        $imgs = self::load($module, $module_id, true);
        //echob("load_thumb: $module($module_id)");
        
        if (!count($imgs) && $default) {
            $img = new genClass();   
            $img->path = $default;
            $imgs = array($img);
        }
        //echod($imgs);
            
        $res = array();
        foreach ($imgs as $i => $img) {
            $res[]= thumbnail::from_image($img, $template);
                        
            if ($limit && $i >= $limit) break;
        }
        
        if ($limit == 1)
            return count($res) ? $res[0] : null;
        
        return count($res) ? $res : null;
    }
	
	public static function delete($module, $module_id) {
		$imgs = self::load($module, $module_id, false);
		foreach ($imgs as $img)
			$img->delete();
	}
	
	public static function add($module, $module_id, $path, $title = '', $visible = 1) {
		$img = mlClass::create('images');
		$img->module_id = $module_id;
		$img->module = $module;
		$img->visible = $visible;
		$img->path = $path;
		$img->title = $title;
		
		$img->save();
	}
	
	public static function merge() {
		$res = array();
		
		$args = func_get_args();
		foreach ($args as $images) {
            if (!is_array($images)) continue;
			foreach ($images as $image)
				$res[$image->path] = $image;
		}    
		
		return array_values($res);
	}
	
	public static function unique($target, $images) {
		foreach ($images as $image) {
			if (!isset($target[$image->path])) {
				$target[$image->path] = $image;				    
				$target[$image->path]->_merged = array();
			} 
			
			$m = $target[$image->path]->_merged;
			$m[] = $image;
			$target[$image->path]->_merged = $m;
		}
		
		return $target;
	}     
}

