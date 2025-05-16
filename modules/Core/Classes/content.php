<?php 

namespace Sloway; 

class content {
    public static function filter_templates($flt, $list) {
        if (!$flt) return implode(",",$list);
        
        $exc = false;
        if (strpos($flt, "!") === 0) {
            $flt = substr($flt, 1);
            $exc = true;    
        }
            
        $r = array(" " => "", "," => "|", "*" => '[A-Za-z0-9\-\_]*');
        $flt = '~\b(' . strtr($flt, $r) . ')\b~i';
        
        $res = array();
        foreach ($list as $name) {
            $m = preg_match($flt, $name);
            if (($exc && !$m) || (!$exc && $m)) 
                $res[]= $name;
        }
        
        return implode(",", $res);
    }
    public static function load_templates() {
        $res = array();
        $styles = \Sloway\config::get("templates.styles"); 
        
        $g_styles = array();
        if (is_array($styles)) {
            foreach ($styles as $n) 
                $g_styles["rl_class_" . $n] = t("template_style_" . $n);
        }
        
        $templates = config::get("admin.templates");
        foreach ($templates as $name) {
            
            $ops = \Sloway\config::get("templates.templates.$name");
            if (!is_array($ops)) continue;
            // if (!kohana::find_file("views", $ops["view"])) continue;

			$view = $ops["view"];
            
            $styles = array();
            foreach ($g_styles as $n => $t)
                $styles[$n] = $t;
            foreach (v($ops, "styles", array()) as $n)
                $styles["rl_class_" . $n] = et("template_style_" . $n);
                
            $res[$name] = array(
                "title" => t("template_" . $name),
                "attrs" => v($ops, "attrs", array()),
                "attrs_html" => v($ops, "attrs_html", array()),
                "html" => view($view, array("media" => "editor")),
                "html_mail" => view($view, array("media" => "mail")),
                "styles" => $styles,
                "add" => self::filter_templates(v($ops, "add", ""), $templates),
                "root" => v($ops, "root", true),
                "platform" => v($ops, "platform", "site"),
                "auto_edit" => v($ops, "auto_edit", ""),
            );
        }
        
        return $res;
    }
    public static function load_media() {
        $res = \Sloway\config::get("templates.media"); 
        if (is_array($res)) {
            foreach ($res as $n => $ops) {
                $res[$n]["title"] = t("template_media_" . $n);
                $res[$n]["class"] = "rl_media_" . $n;
            }
        }        
        
        return $res;
    }
}            

