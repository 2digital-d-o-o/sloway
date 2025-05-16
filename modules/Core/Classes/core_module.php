<?php
namespace Sloway;

use CodeIgniter\Router;

class core_module {
    public static $access = array();
    public static $operations = array();
    public static $permissions = array();
    public static $permissions_list = array();
    public static $user = null;
    
    public static function admin_user_log($ctrl) {
        if (!config::get("admin.user_log")) return;
        
        if (!isset($ctrl->admin_user) || !$ctrl->admin_user || !$ctrl->admin_user->log) return;
        $data = array(                        
            "id_user" => $ctrl->admin_user->id,
            "username" => $ctrl->admin_user->username,
            "time" => date("Y-m-d H:i:s"), 
            "ip" => v($_SERVER, "REMOTE_ADDR"),
            "url" => url::curr(),
            "get" => mysqli_real_escape_string(str_replace("=&gt;", "=>", d($_GET))),
            "post" => mysqli_real_escape_string(str_replace("=&gt;", "=>", d($_POST)))
        );                                                              
        $keys = "`" . implode("`,`", array_keys($data)) . "`";
        $vals = "'" . implode("','", $data) . "'";                
        $sql = "INSERT INTO `admin_user_log` ($keys) VALUES($vals)";
        
        $ctrl->db->query($sql);
    }
    public static function lang_config($profile) {
        if (!self::$user) return Kohana::config("lang.$profile");
        
        $langs = array();
        if ($profile == 'admin') {
            $cfg = Kohana::config("lang.admin");
            
            foreach ($cfg['langs'] as $l) {
                if (strpos(self::$user->deny_admin_lang, $l) === false)
                    $langs[] = $l;
            }    
        } else {
            $cfg = Kohana::config("lang.$profile");

            foreach ($cfg['langs'] as $l) {
                if (strpos(self::$user->deny_edit_lang, $l) === false)
                    $langs[] = $l;
            }    
        }
        if (!in_array($cfg['default'], $langs))
            $cfg['default'] = $langs[0];
        
        $cfg['langs'] = $langs;
        
        return $cfg;
    }
    public static function init_templates() {
        $cfg = config::get("templates");
        
        $res = array(
            "sizes" => array(
                "small" => $cfg["sizes"]["small"],
                "medium" => $cfg["sizes"]["medium"],
            ),
            "scale" => array()
        );
        
        foreach ($cfg["templates"] as $name => $ops) 
            $res["scale"][$name] = v($ops, "scale", 1);    
            
        echo "<script>\n";
        echo javascript::gen_array("template_options", $res);
        echo "</script>";
    }        
    public static function permissions() {
        $res = config::get("admin.permissions");
        /*
        $mods = utils::config("admin.modules");
        foreach ($res as $key => $val) {
            $node = utils::parse_node(is_array($val) ? $key : $val);
            $mod = v($node, "attr.id");
            
            unset($mods[$mod]);
        }   
        
        foreach ($mods as $name => $mod)
            $res[] = ucfirst($name) . "{id=" . $name . "}";*/
            
        self::$permissions = $res;
        self::$permissions_list = utils::serialize_tree($res);
    }
    public static function load_includes($files) {
        $res = array();
        
        foreach ($files as $file) {
		//	echob($file);
            $file = explode(":", $file, 2);
            if (count($file) > 1) {
                $abs_path = path::gen("root.modules." . $file[0], $file[1]); 
                $rel_path = path::gen("site.modules." . $file[0], $file[1]); 
            } else {
                $abs_path = path::gen("root", $file[0]);
                $rel_path = path::gen("site", $file[0]); 
            }
		//	echob($abs_path);
		//	echob($rel_path);
		//	echob();
			
            
            if (!file_exists($abs_path)) continue;
            
            $inc = new \stdClass();
            $inc->path_root = $abs_path;
            $inc->path_site = $rel_path;
            $inc->modified = filemtime($abs_path);
            
            $res[] = $inc;
        }    
        
        return $res;    
    }
    public static function minify_config($profile) {
        if (!isset($_SERVER['MINIFY'])) return array("js" => false, "css" => false);
        
        $min = strtolower($_SERVER['MINIFY']);
        if ($min == "1" || $min == "true") return array("js" => true, "css" => true);
        if ($min == "0" || $min == "false") return array("js" => false, "css" => false);
        
        $min = explode(";", $min);
        foreach ($min as $part) {
            $e = explode("=", $part);
            if (count($e) != 2) continue;
            
            $pf = trim($e[0]);
            $st = trim($e[1]);
            
            if ($pf == $profile) {
                return array(
                    "js" => $st == "1" || $st == "true" || strpos("js", $st) !== false,
                    "css" => $st == "1" || $st == "true" || strpos("css", $st) !== false,
                );        
            }
        }            
        array("js" => false, "css" => false);
    }
    public static function redirect_301() {
            
    }
    
	public static function _load($ctrl) {
		dbClass::$database = $ctrl->db;
		account::$db = $ctrl->db;
		account::$session = \Config\Services::session();
		account::$prefix = core::$project_name;

		$mp = \Sloway\path::gen("site.modules.Core");

		//dbModel::build();
		//log::init();   
		
		$ctrl->doc_base = trim(base_url(), "/");
		$router = service("router");
		$ctrl_name = str_replace('\Sloway\Controllers\\','', $router->controllerName());
        $chk = (core::$profile == 'admin' && $ctrl_name != 'AdminLogin' && $ctrl_name != 'Core');

        $ctrl->admin_user = null;
        if ($chk) {
            $uid = admin_user::instance()->user_id;  
            if (!$uid) {
                //if (!\utils::is_ajax()) {
                    // userdata::set("login_redirect", url::current());
					echod("redirect");
					header('Location: ' . url::site("AdminLogin"));
                    // url::redirect("AdminLogin");
                //}
            }              
            
/*
            $ctrl->admin_user = dbClass::load("admin_user", "@id = $uid", 1);
            if ($ctrl->admin_user) {
                $role = ($rid = $ctrl->admin_user->id_role) ? dbClass::load("admin_role", "@id = " . $rid, 1) : 0;
                
                $ctrl->admin_user->level = ($role) ? $role->level : 0;
                $ctrl->admin_user->role = $role;
                self::$user = $ctrl->admin_user;     
                dbClass::$options['edit_user'] = $ctrl->admin_user->username;
                
                $ctrl->db->query("UPDATE admin_user SET last_login = ? WHERE id = ?", time(), $uid);
            }
            
 */
            
        }        
            
        if (PROFILE != 'admin') {
            $uid = admin_user::instance()->user_id;  
            $ctrl->admin_user = dbClass::load("admin_user", "@id = $uid", 1);
            self::$user = $ctrl->admin_user;     
            
            if ($ctrl->admin_user)
                $ctrl->body_class = "admin_panel"; else
                $ctrl->body_class = "";
            
            $ctrl->site_title = Settings::get('title', '', 'settings');
            $ctrl->site_keys = Settings::get('meta_keys', '', 'settings');
            $ctrl->site_desc = Settings::get('meta_desc', '', 'settings');
            $ctrl->site_tags = array();
        } else {
            if (ADMIN && isset($_POST["module_ajax"]) & $ctrl instanceof Template_Controller) {
                $ctrl->template = "AdminModule";
            }
        }
        define("ADMIN_LOGGED", $ctrl->admin_user ? $ctrl->admin_user->id : 0);
        
        //lang::bind('admin', "core_module::lang_config");
        content::load();       
        self::permissions();   

	}
    public static function load_end() {
        // 
    }

	public static function head() {
		$mp = path::gen("site.modules.Core"); 

		echo "<script type='text/javascript' src='{$mp}media/js/jquery-1.7.2.min.js'></script>\n";
		echo "<script type='text/javascript' src='{$mp}media/js/history.js'></script>\n";
        
		$profile = core::$profile;
        $styles = array_merge(
            config::get("includes.styles", array()), 
            config::get("includes.$profile.styles", array()), 
//          \utils::value($ctrl, "includes.styles", array())
        );

        $script = array_merge(
            config::get("includes.script", array()), 
            config::get("includes.$profile.script", array()), 
//            \utils::value($ctrl, "includes.script", array())
        );                                              
        $styles = self::load_includes($styles);
        $script = self::load_includes($script);
		

        foreach ($script as $file) 
            echo "<script type='text/javascript' src='$file->path_site?t=$file->modified'></script>\n"; 
        foreach ($styles as $file)    
            echo "<link rel='stylesheet' type='text/css' href='$file->path_site?t=$file->modified'>\n";  
	}
    public static function script() {
		if (core::$profile == "admin") {
			echo "<script type='text/javascript' src='" . path::gen("site.modules.Core", "media/tinymce/tinymce.min.js") . "'></script>\n";
			echo "<script type='text/javascript' src='" . site_url("Admin/Script") . "'></script>\n";
		} else {
			echo "<script type='text/javascript' src='" . url::site("Core/Script") . "'></script>\n";
		}

        //echo "<script>\$(document).ready(function() { \$.core.start_listeners(); console.log('listeners') });</script>";
    }     
	public static function body() {
	}

    public static function end() {
        echo "<script>\$(document).ready(function() {\n";
        if (core::$profile != 'admin')
            echo "\$(\"body\").responsive_layout();\n";
        echo "\$.core.start_listeners();});</script>";
    }
}  

