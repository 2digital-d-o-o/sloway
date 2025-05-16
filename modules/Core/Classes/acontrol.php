<?php
namespace Sloway;

class acontrol {
    private static $edit_attr = array("label", "lines", "invalid", "readonly", "disabled", "password", "placeholder", "tab_index", "mask", "handler", "handler_name", "handler_value");
    private static $select_attr = array("invalid", "readonly", "disabled", "invert", "tab_index", "mode", "placeholder", "handler", "label");
    private static $checkbox_attr = array("invalid", "readonly", "disabled", "invert", "tab_index", "mode", "label");
    private static $checklist_attr = array("invalid", "readonly", "disabled", "invert", "tab_index", "mode", "border");
    private static $checktree_attr = array("invalid", "readonly", "disabled", "invert", "tab_index", "three_state", "expanded", "mode", "merge", "paths", "dependency", "state_cookie", "border");  
    private static $selecttree_attr = array("invalid", "readonly", "disabled", "tab_index", "expanded", "multi", "paths", "border");  
    
    private static function attributes($options, $model, $class) {
        $res = "";
        if (isset($options["id"]))
            $res.= "id='" . $options["id"] . "'";
        
        if (isset($options["style"]))
            $res.= " style='" . $options["style"] . "'";
            
        if (isset($options["attr"]))
            $res.= " " . $options["attr"];
        
        $cls = trim((isset($options["class"]) ? $options["class"] : "") . " " . $class);
        if ($cls)
            $res.= " class='$cls'";
        
        foreach ($model as $name) 
            if (isset($options[$name])) {
                $val = $options[$name];
                if (is_bool($val))
                    $val = intval($val);
                    
                $res.= " data-$name='" . htmlentities($val) . "'";
            }
        
        return trim($res);
    }
    
    private static function tree_build($items, $trans = false) {
        $res = "";     
                            
        foreach ($items as $item => $children) {
            if (is_int($item)) {
                $item = $children;
                $children = array();
            }       
            
            $node = acontrol::parse_node($item);
            $res.= "<li";
            
            foreach ($node["attr"] as $op_name => $op_val) {
                if ($op_name[0] == "i") 
                    $res.= " data-value='$op_val'"; else
                if ($op_name[0] == "c")
                    $res.= " data-check='$op_val'"; else
                if ($op_name[0] == "t") 
                    $res.= " data-type='$op_val'"; else
                if ($op_name[0] == "d")
                    $res.= " data-disabled='$op_val'"; else
                if ($op_name[0] == "e")
                    $res.= " data-expanded='$op_val'"; 
            }
            $res.= ">";
            if ($trans)
                $res.= et($node["title"]); else
                $res.= $node["title"];
            
            if (count($children)) {
                $res.= "<ul>";    
                $res.= self::tree_build($children, $trans);
                $res.= "</ul>";
            }
            
            $res.= "</li>";
        }  
        
        return $res;  
    }  
    private static function get_selector($sel, $level) {
        if (is_array($sel)) 
            return $sel[min(count($sel)-1, $level)]; else
            return $sel;    
    }
    
    public static function parse_tree($items) {
        $res = array();
        foreach ($items as $item => $children) {
            if (is_int($item)) {
                $item = $children;
                $children = array();
            }       
                                                  
            $node = acontrol::parse_node($item);
            $id = v($node["attr"], "id", null);
             
            if (count($children)) 
                $node["ch"] = self::parse_tree($children);
                
            $res[$id] = $node;
        }
        return $res;            
    }
    public static function parse_node($item) {
        $res = array(
            "title" => "",
            "attr" => array()
        );
        $br1 = strpos($item, "{");
        $br2 = strpos($item, "}");
    
        $t = ($br1) ? substr($item, 0, $br1) : $item;
        $res["title"] = str_replace(array("__DEL1__", "__DEL2__"), array("{", "}"),  $t);
        
        $ops = ($br1 && $br2) ? substr($item, $br1+1, $br2-$br1-1) : "";
            
        if (!empty($ops)) {            
            $ops = explode(",", $ops);
            foreach ($ops as $pair) {
                $pair = explode("=", $pair);
                $op_name = trim($pair[0]);
                $op_val = count($pair) > 1 ? trim($pair[1]) : 1;
                
                $res["attr"][$op_name] = $op_val;
            }
        }        
        
        return $res;
    }
    public static function init_script() {
        return "<script>\$(document).ready(function() { \$(document).ac_create(); });</script>";
    }                       
    public static function edit($name, $value = null, $options = null) {
        $attr = self::attributes($options, self::$edit_attr, "ac_edit ac_border ac_loading");
        if (is_null($value)) $value = '';
        
		$value = htmlentities($value);
        $items = v($options, "items", false);
        if (is_array($items)) {
            $res = "<ul $attr data-name='$name' value='$value'>";
            foreach ($items as $id => $item) 
                $res.= "<li data-value='$id'>$item</li>";
            $res.= "</ul>";            
        } else {       
            $res = "<div $attr data-name='$name' data-value='$value'>";
            $res.= "</div>";
        }
            
        return $res;
    }
    public static function select($name, $items, $value = null, $options = null, $tree = false) {
        $attr = self::attributes($options, self::$select_attr, "ac_select ac_border ac_loading");
        if (is_null($value)) $value = "";
        
        if ($tree) {
            $res = "<ul $attr data-name='$name' data-value='$value'>";
            $res.= self::tree_build($items, v($options, "trans", false));
            $res.= "</ul>";
        } else {
            $res = "<ul $attr data-name='$name' value='$value'>";
            foreach ($items as $id => $item) 
                $res.= "<li data-value='$id'>$item</li>";
            $res.= "</ul>";
        }
        
        return $res;
    }
    public static function color($name, $value = null, $options = null) {
        $attr = self::attributes($options, self::$checkbox_attr, "ac_color ac_loading");
        if (is_null($value)) $value = '';       
        
        $res = "<input $attr type='color' name='$name' data-name='$name' value='$value'>";
        return $res;
    }
    public static function checkbox($name, $value = null, $options = null) {
		if (is_null($options))
			$options = array();
		$style = isset($options["style"]) ? $options["style"] : "";
		$style.= " display: inline-block; vertical-align: middle";
		
		$options["style"] = $style;
        $attr = self::attributes($options, self::$checkbox_attr, "ac_checkbox ac_loading");
        if (is_null($value)) $value = '';   
        
        $res = "<input $attr type='checkbox' name='$name' data-name='$name' value='$value'>";
        return $res;
    }
    public static function checklist($name, $items, $value = null, $options = null) {
        $attr = self::attributes($options, self::$checklist_attr, "ac_checklist ac_loading");
        if (is_null($value)) $value = "";
        
        $res = "<ul $attr class='ac_checklist' data-name='$name' value='$value'>";
        foreach ($items as $id => $item) {
			$attr = "";
			if (is_array($item)) {
				$title = isset($item["title"]) ? $item["title"] : "";
				$attr = isset($item["attr"]) ? $item["attr"] : "";
			} else
				$title = $item;

            $res.= "<li data-value='$id' $attr>$title</li>";
		}
        $res.= "</ul>";
        
        return $res;
    }    
    public static function checktree($name, $items, $value = null, $options = null) {
        $attr = self::attributes($options, self::$checktree_attr, "ac_checktree ac_loading");
        
        $res = "<ul $attr data-name='$name' data-value='$value'>";
        $res.= self::tree_build($items, v($options, "trans", false));
        $res.= "</ul>";
        
        return $res;
    }
    public static function selecttree($name, $items, $value = null, $options = null) {
        $attr = self::attributes($options, self::$selecttree_attr, "ac_selecttree ac_loading");
        
        $res = "<ul $attr data-name='$name' data-value='$value'>";
        $res.= self::tree_build($items, v($options, "trans", false));
        $res.= "</ul>";
        
        return $res;
    }
    
    public static function tree_items($root, $ch_sel = "sub", $id_sel = "id", $title_sel = "title", $level = 0) {
        $res = array();
        foreach ($root as $key => $node) {
            if (is_callable($ch_sel)) {
                $r = call_user_func($ch_sel, $node);
                $id = v($r, 'id', 0);
                $title = v($r, 'title', '');
                $ops = v($r, 'ops', false);
                $ch = v($r, 'ch', array());
            } else {
                $_ch_sel = self::get_selector($ch_sel, $level);
                $_id_sel = self::get_selector($id_sel, $level);
                $_title_sel = self::get_selector($title_sel, $level);
                
                if ($_id_sel == "__KEY")
                    $id = $key; else
                    $id = $node->$_id_sel;
                    
                $title = $node->$_title_sel;
                $ops = false;
                $ch = $node->$_ch_sel;
                if (!is_array($ch))
                    $ch = array();
            }            
            
            $title = str_replace(array("{", "}"), array("__DEL1__", "__DEL2__"), $title);
            $tv = $title . "{id=" . $id;
            if ($ops)
                $tv.= "," . $ops;
            $tv.= "}";
            
            $c = self::tree_items($ch, $ch_sel, $id_sel, $title_sel, $level+1);
            if (count($c))
                $res[$tv] = $c; else
                $res[] = $tv;
        }    
        
        return $res;
    }    
    public static function tree_serialize($tree, $value, $del = ", ") {
        $tree = acontrol::parse_tree($tree);  
        $value = explode(",", $value);
        $result = array();
        
        foreach ($value as $pth) {
            $pth = str_replace(".", ".ch.", $pth);
            $result[] = v($tree, $pth . ".title");                                    
        }  
        
        return implode($del, $result);
    }
}

