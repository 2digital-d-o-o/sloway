<?php
	namespace Sloway;
	
    class navigation {	
		public static $preloaded = false;
        public static $def_cfg = array(
            "model" => null,
            "id_sel" => "id",
            "ch_sel" => null,
            "title_sel" => "title",
            "flags" => array(),
            "url_mask" => "%ID/%TITLE",
            "full_path" => false,
        );
        public static $config = null;
        public static function gen_class($node, $side_menu = false) {
            $res = "";
            if ($side_menu) {
                if (config::get("navigation.arrows", false)) {
                    if (count($node->sub_items))
                        $res.= " nav_parent";
                } else 
                if (count($node->sub_items) && \Sloway\flags::get($node->flags, "disabled"))
                    $res.= " nav_parent";
            } else {
                if (count($node->sub_items))
                    $res.= " nav_parent";
            
                //if ($node->fullwidth)
                //    $res.= " nav_fullwidth"; else
                    $res.= " nav_floating";
            }
             
            foreach (explode(",", $node->flags) as $flag) {
                $flag = trim($flag);
                if (!$flag) continue;
                
                $res.= " nav_" . $flag;
            }                
            
            return trim($res);
        }
        public static function gen_url($cfg, $item, $ids, $titles) {
            if ($cfg["full_path"]) {
                $url_id = "";
                $url_title = "";
                foreach ($ids as $id) $url_id.= $id . "-";
                foreach ($titles as $title) $url_title.= url::title($title) . "/"; 
                
                $url_id.= v($item, $cfg["id_sel"], 0);
                $url_title.= url::title(v($item, $cfg["title_sel"], 0));
            } else {
                $url_id = v($item, $cfg["id_sel"], 0);
                $url_title = url::title(v($item, $cfg["title_sel"], 0));
            }
			$url_slug = v($item, "url");
            
            $r = strtr($cfg["url_mask"], array("%ID" => $url_id, "%TITLE" => $url_title, "%SLUG" => $url_slug));
            
            return $r;
        }
        public static function autogen_nodes($cfg, $ref_items, $ids, $titles, $depth, $max_depth) {
            $result = array();
            foreach ($ref_items as $ref_item) {
				$callback = v($cfg, "callback", false);
				if (is_callable($callback)) 
					if (call_user_func($callback, $ref_item) === false) continue;

                $id = v($ref_item, $cfg["id_sel"], 0);
                $title = v($ref_item, $cfg["title_sel"], "");
                
                $sub_nodes = v($ref_item, $cfg["ch_sel"], array());
                
                $node = new \stdClass();
                $node->title = $title;
                $node->url = self::gen_url($cfg, $ref_item, $ids, $titles);
                $node->flags = "";
                                      
                if ($depth < $max_depth-1)
                    $node->sub_items = self::autogen_nodes($cfg, $sub_nodes, $ids + array($id), $titles + array($title), $depth+1, $max_depth); else
                    $node->sub_items = array();
                
                $node->class = self::gen_class($node);
                
                $result[] = $node;   
            }
            
            return $result;
        }
        public static function build_nodes($nav_items) {
            $result = array();
            foreach ($nav_items as $nav_item) {
                if (!$nav_item->visible) continue;
                
                $node = new genClass();
                $node->attrs = arrays::decode($nav_item->attrs, "=", ";");
                $node->tag = $nav_item->tag;
                
                //if (is_array($nav_item->images) && count($nav_item->images))
                //    $node->image = $nav_item->images[0]; else
                    $node->image = null;
                
                if ($nav_item->type == "static") {
                    $node->title = $nav_item->title;
                    $node->url = flags::get($nav_item->flags, "disabled") ? false : $nav_item->url;
                    $node->flags = $nav_item->flags;
                    $node->fullwidth = $nav_item->fullwidth;
                    
                    if (strlen($node->url) && $node->url[0] == "/")
                        $node->url = url::site($node->url);
                    
                    $node->sub_items = self::build_nodes($nav_item->sub_items);
                    $node->class = self::gen_class($node);
                    $node->class_side = self::gen_class($node, true);
                } else 
                if (isset(self::$config["types"][$nav_item->type])) {
                    $cfg = self::$config["types"][$nav_item->type]; 
                    if (!$cfg["model"]) continue;
                    
                    $model = $cfg["model"];
                    if (!$model) continue;
                    
                    $ref_item = dbModel::load($model, "@id = " . $nav_item->id_ref, 1);
                    $id = v($ref_item, $cfg["id_sel"], 0);
                    
                    $title = $nav_item->title;
                    if (!$title)
                        $title = v($ref_item, $cfg["title_sel"], "");
					
                    $node->title = $title;
                    $node->url = self::gen_url($cfg, $ref_item, array(), array());
                    $node->flags = $nav_item->flags;
                    $node->fullwidth = $nav_item->fullwidth;
                    $node->sub_items = array();
                    
                    if ($nav_item->autogen && $cfg["ch_sel"]) {
                        $sub_items = v($ref_item, $cfg["ch_sel"], array());
                        $node->sub_items = self::autogen_nodes($cfg, $sub_items, array($id), array($title), 0, $nav_item->autogen);
                    } else 
                        $node->sub_items = self::build_nodes($nav_item->sub_items);

                    $node->class = self::gen_class($node);
                    $node->class_side = self::gen_class($node, true);
                } else
                    continue;
                
                /*if ($nav_item->tag)
                    $result[$nav_item->tag] = $node; else*/
                    $result[] = $node;
            }  
            
            return $result;  
        }
        public static function build($module, $item_callback = null) {
            $result = array();

            if (!self::$config) {
                self::$config = config::get("navigation");
                foreach (self::$config["types"] as $type => $cfg)
                    self::$config["types"][$type] = arrays::extend(self::$def_cfg, $cfg);    
            }
			self::$config["callback"] = $item_callback;
			
			$use_cache = v(self::$config, "cache");
			if ($use_cache && $res = cache("nav_" . $module))
				return $res;

			if (!self::$preloaded) {
				$items = mlClass::load("navigation", "*", 0, array("index" => "id"), null, "\Sloway\dbModelObject");
				dbModel::preload("navigation", $items, arrays::make_tree($items, "sub_items"));
				
				self::$preloaded = true;
			}
            
            $items = dbModel::load("navigation", "@module = '$module' AND id_parent = 0 AND visible = 1 ORDER BY id_order ASC");
            $res = self::build_nodes($items);

			if ($use_cache)
				cache()->save("nav_" . $module, $res, 3600);

			return $res;
        }
    }  
?>
