<?php
namespace Sloway;

class path_base {
	public static $config = null;
	
	public static function read_config() {
		$paths = new \Config\Paths;
		$app = new \Config\App;
		
		$c = array();
		$c["sloway_root"] = realpath(rtrim($paths->slowayRoot, '\\/ ')) . DIRECTORY_SEPARATOR;
		$c["media_root"] = realpath(rtrim($paths->mediaRoot, '\\/ ')) . DIRECTORY_SEPARATOR;
		$c["uploads_root"] = realpath(rtrim($paths->uploadsRoot, '\\/ ')) . DIRECTORY_SEPARATOR;
		$c["thumbs_root"] = realpath(rtrim($paths->thumbsRoot, '\\/ ')) . DIRECTORY_SEPARATOR;
		
		$c["sloway_site"] = (strpos($paths->slowaySite, "http") === 0) ? $c["sloway_site"] = $paths->slowaySite : site_url($paths->slowaySite);
		$c["media_site"] = (strpos($paths->mediaSite, "http") === 0) ? $c["media_site"] = $paths->mediaSite : site_url($paths->mediaSite);
		$c["uploads_site"] = (strpos($paths->uploadsSite, "http") === 0) ? $c["uploads_site"] = $paths->uploadsSite : site_url($paths->uploadsSite);
		$c["thumbs_site"] = (strpos($paths->thumbsSite, "http") === 0) ? $c["thumbs_site"] = $paths->thumbsSite : site_url($paths->thumbsSite);
			
		$c["sloway_site"] = rtrim($c["sloway_site"], '\\/ ') . DIRECTORY_SEPARATOR;
		$c["media_site"] = rtrim($c["media_site"], '\\/ ') . DIRECTORY_SEPARATOR;
		$c["uploads_site"] = rtrim($c["uploads_site"], '\\/ ') . DIRECTORY_SEPARATOR;
		$c["thumbs_site"] = rtrim($c["thumbs_site"], '\\/ ') . DIRECTORY_SEPARATOR;
		
		self::$config = $c;
	}
	
	public static function gen_base($base) {
		if (is_null(self::$config))
			self::read_config();
		
		$c = self::$config;
			
		if ($base == "root") 
			return realpath(APPPATH . "/..") . "/";
		if ($base == "site")
			return site_url();

		if ($base == "site.media")
			return $c["media_site"];
		if ($base == "root.media")
			return $c["media_root"];
		
		if ($base == "site.uploads")
			return $c["uploads_site"];
		if ($base == "root.uploads")
			return $c["uploads_root"];
		
		if ($base == "site.thumbs")
			return $c["thumbs_site"];
		if ($base == "root.thumbs")
			return $c["thumbs_root"];		
		
		if (strpos($base, "site.modules") === 0) {
			$pf = str_replace("site.modules", "", $base, );
			if ($pf)
				return $c["sloway_site"] . trim($pf, ".") . "/"; else
				return $c["sloway_site"];
		}
		if (strpos($base, "root.modules") === 0) {
			$pf = str_replace("root.modules", "", $base, );
			if ($pf)
				return $c["sloway_root"] . trim($pf, ".") . "/"; else
				return $c["sloway_root"];			
		}
	}
	public static function gen($base, $path = "") {
		$b = self::gen_base($base);
		if (!is_null($b) && $path)
			$b.= $path;
		
		return $b;
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

	public static function to_root($path) {
		//echod(url::base(), $path, str_replace(url::base(), "", $path));
		return path::resolve(DOCROOT . "/" . str_replace(url::base(), "", $path));
	}
	public static function to_site($path) {
		return url::site(str_replace(DOCROOT, "", path::resolve($path))); 
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


