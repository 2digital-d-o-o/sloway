<?php
namespace Sloway;

class core {
	public static $modules = array();
	public static $scripts = array();
	public static $styles = array();
	public static $profile = "site";
	public static $project_name = "";
	public static $db;
	
	public static function modules_call($func) {
        $args = func_get_args();
        $args = array_splice($args, 1); 

        foreach (self::$modules as $name => $path) {
			$pth = $path . "/Classes/" . strtolower($name) . "_module.php";

			if (!file_exists($pth)) continue;
			require_once $pth;

            $_func = "\\Sloway\\" . strtolower($name) . "_module::" . $func;

            if (is_callable($_func)) {
                call_user_func_array($_func, $args); 
			}
        }
	}
	public static function load($ctrl) {
		$app = new \Config\App;
        self::$modules = $app->modules;		

		self::modules_call("load", $ctrl);
		self::modules_call("load_end", $ctrl);
	}
    public static function document() {
        self::modules_call("document");     
    }
	public static function head() {
        self::modules_call("head");				
        self::modules_call("script");                
	}
    public static function body() {  
        self::modules_call('body');    
    }
    public static function end() {  
        self::modules_call('end');    
    }
}
