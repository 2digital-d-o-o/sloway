<?php
namespace Sloway;

class path_base {
	public static $c = null;
	public static $db;
	
	private static function read_config() {
		$paths = new \Config\Paths;
		$app = new \Config\App;
		
		$media = realpath(rtrim($paths->mediaDirectory, '\\/ ')) . DIRECTORY_SEPARATOR;
		$uploads = realpath(rtrim($paths->uploadsDirectory, '\\/ ')) . DIRECTORY_SEPARATOR;
		$thumbs = realpath(rtrim($paths->thumbsDirectory, '\\/ ')) . DIRECTORY_SEPARATOR;

		$r = array();
		$r["modules"] = trim(str_replace(DOCROOT, "", MODPATH), "/");
		$r["media"] = trim(str_replace(DOCROOT, "", $media), "/");
		$r["uploads"] = trim(str_replace(DOCROOT, "", $uploads), "/");
		$r["thumbs"] = trim(str_replace(DOCROOT, "", $thumbs), "/");
		$r['site'] = trim(str_replace(["http://", "https://"], "", $app->baseURL), "/");
        
		self::$c = $r;
	}
	public static function gp($base, $path) {
		if (is_array($base)) 
			$base = arrays::implode("/", $base) . "/";
		
		if ($path === false)
			return trim($base, "/"); else
			return $base . trim($path, "/");
	}
	public static function gen($base, $path = "") {
     	if (!self::$c) self::read_config();

		$p = explode('.', $base); 
		$curr = array();
		
		switch ($p[0]) {
			case 'site':    
				$pr = ((empty($_SERVER['HTTPS']) OR $_SERVER['HTTPS'] === 'off') ? 'http' : 'https').'://';  
				$curr[] = $pr . self::$c['site'];                
				break;
			case 'root':
				$curr[] = rtrim(ROOTPATH, "/");
				break;
			case 'modules':
				$curr[] = self::$c['modules'];
				if (count($p) > 1) 
                    $curr[] = $p[1];
//					$curr[] = utils::value(core::$modules, $p[1] . ".path", "");
				
				return self::gp($curr, $path);
			default:          
				if (($c = \Sloway\utils::value(self::$c, $p[0], false)) !== false)
					$curr[] = $c; else
					return false;
		}
		
		if (count($p) == 1) 
			return self::gp($curr, $path);
			
		if ($p[1] == 'modules') {
			$curr[] = self::$c['modules'];
			if (count($p) > 2)
				$curr[] = $p[2];
				//$curr[] = utils::value(core::$modules, $p[2] . ".path", "");

			return self::gp($curr, $path); 
		} else
		if (($c = \Sloway\utils::value(self::$c, $p[1], false)) !== false) {
			$curr[] = $c;
			return self::gp($curr, $path); 
		} else                        
			return false;
	}
	
	public static function rel($path, $base) {
		$path = utils::trim_slashes($path);
		$base = utils::trim_slashes($base);
		
		if (strpos($path, $base . "/") === 0)
			return str_replace($base . "/", '', $path);
			
		if (strpos($path, $base) === 0)
			return str_replace($base, '', $path);
			
		return false;
	}
	public static function resolve($path) {
		if ($path == '') return '';
		if ($path == '/') return '/';
		
		$base = "";
		if (strpos($path, "http://") === 0) {
			$base = "http://";
			$path = str_replace("http://", "", $path);
		} else
		if (strpos($path, "https://") === 0) {
			$base = "https://";
			$path = str_replace("https://", "", $path);
		}   
		
		$p1 = $path[0] == '/';
		$p2 = $path[strlen($path)-1] == '/';
				
		$e = explode('/', $path);
		
		if ($p1) $e[1] = "/$e[1]";
		if ($p2) $e[count($e)-2].= "/";
		
		$r = $base . array_reduce($e, array('path','reduce'));
		
		return $r;
	}
	public static function is_root($path) {
		return strpos($path, DOCROOT) === 0;
	}
	public static function is_site($path) {
		return strpos($path, url::base()) === 0;
	}
	public static function to_root($path) {
		//echod(url::base(), $path, str_replace(url::base(), "", $path));
		return path::resolve(DOCROOT . "/" . str_replace(url::base(), "", $path));
	}
	public static function to_site($path) {
		return url::site(str_replace(DOCROOT, "", path::resolve($path))); 
	}
	
	public static function url2($slug, $lang) {
		
	}
	
	public static function url($module, $data) {
		$gen_url = config::get("admin.generate_url." . $module);
		if ($gen_url) 
			return url::base() . $data->url;
			
		if ($module == "page")
			return url::from_obj("", $data); 
		if ($module == "news")
			return url::from_obj("n", $data); 
		if ($module == "product")
			return url::from_obj("p", $data); 
		if ($module == "category")
			return url::from_obj("c", $data); 

		return "";
	}
}


