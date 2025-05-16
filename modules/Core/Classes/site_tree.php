<?php

namespace Sloway;

class site_tree {      
    public static $options_def = array(
        "title_sel" => "title",
        "items_sel" => "items",
        "id_sel" => "id",
        "url_sel" => "url",
		"slug_sel" => "url",
        "url_mask" => false,
		"visible_sel" => false,
    );
    public static $_path = array();
    public static function build_node($node, $active_id, $ops) {
        $res = new \stdClass();
        $res->id = v($node, $ops["id_sel"]);
        $res->title = v($node, $ops["title_sel"]);
		$slug = v($node, $ops["slug_sel"]);
        if ($ops["url_mask"]) 
            $res->url = url::site(strtr($ops["url_mask"], array("%SLUG%" => $slug, "%ID%" => $res->id, "%TITLE%" => url::title($res->title)))); else
            $res->url = v($node, $ops["url_sel"]);
            
        $res->items = array();
        $res->active = ($active_id && $res->id) ? $active_id == $res->id : v($node, "active", false);
        $res->active_parent = false;
        
        $class = array();
        
        foreach (v($node, $ops["items_sel"], array()) as $item) {
            $callback = v($ops, "item_callback", false);
            if (is_callable($callback)) 
                if (call_user_func($callback, $item) === false) continue;

			if ($ops["visible_sel"])
				$vis = v($item, $ops["visible_sel"], true); else
				$vis = true;

			if (!$vis) continue;
            
            $res->items[]= self::build_node($item, $active_id, $ops);
        }
            
        foreach ($res->items as $item) 
            if ($item->active || $item->active_parent)
                $res->active_parent = true;
                
        if ($res->active || $res->active_parent)                 
            self::$_path[]= array("title" => $res->title, "url" => $res->url);
                
        if (count($res->items)) $class[]= "parent";
        if ($res->active) $class[]= "active";
        if ($res->active_parent) $class[]= "active_parent";
        
        $res->expanded = count($res->items) && ($res->active || $res->active_parent);
        $res->class = implode(" ", $class);
            
        return $res;
    }
    public static function build($node, $active_id = null, $ops = null) {
        self::$_path = array();
        
        $ops = arrays::extend(self::$options_def, $ops);
        
        $res = new \stdClass();
        $res->root = self::build_node($node, $active_id, $ops);
        $res->title = count(self::$_path) ? self::$_path[0]["title"] : "";
        $res->path = array_reverse(self::$_path);
        
        return $res;
    }
}  

