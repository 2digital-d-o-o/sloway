<?php

namespace Sloway;
use Sloway\mlClass;
use Sloway\path;

class files {
	public static function load($module, $module_id, $vis_only = true, $limit = 0, $tag = null) {
		$sql = "%module = '$module' AND module_id = '$module_id'";
        if (!is_null($tag))
            $sql.= " AND tag = '$tag'";
		if ($vis_only)
			$sql.= " AND visible = 1";
		
		$sql.= " ORDER BY id_order ASC";
		if ($limit)
			$sql.= " LIMIT $limit";
		
		return mlClass::load('files', $sql);
	}
	
	public static function delete($module, $module_id) {
		$imgs = self::load($module, $module_id, false);
		foreach ($imgs as $img)
			$img->delete();
	}
	
	public static function add($module, $module_id, $path, $title = '', $desc = '', $visible = 1) {
		$img = mlClass::create('files');
		$img->module_id = $module_id;
		$img->module = $module;
		$img->visible = $visible;
		$img->path = $path;
		$img->title = $title;
		$img->description = $desc;
		
		$img->save();
	}    
}

