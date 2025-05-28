<?php

namespace Sloway;

class translator {
	public static $class = "\Sloway\translator";
	public static $instance = null;   
									 
	public static $use_cache = false;        
	
	public $lang; 		
	public $profile;
	
	public $trans = array();
	public $added = array();
	public $cache = array();
	public $loaded = array();
	
	protected function load_files($path) {
		$lang = $this->lang;
		$fc = config::get("lang.file_mask." . $lang, $lang);
		if ($this->profile) {
			$this->load_file("$path/{$this->profile}.$fc.php", $lang);
		}
		   
		$this->load_file("$path/$fc.php", $lang); 	
	}
	protected function load_file($path, $lang) {
		if (isset($this->loaded[$path])) return;
		
		if (file_exists($path)) {
			unset($l);
			unset($u);
			unset($prefix);
			include $path;
			
			if (!isset($this->trans[$lang]))
				$this->trans[$lang] = array();
				
			if (isset($l)) 
				$this->trans[$lang] = array_merge($this->trans[$lang], $l);
		}
		
		$this->loaded[$path] = true;
	}
	protected function build() {
		//if (self::$use_cache) {
		//	$c = Cache::instance();
		//	$this->trans = $c->get("translator::trans");
		//}
		
		if (!$this->trans) {
			foreach (core::$modules as $name => $path) {
				$path = $path . "/Lang";

				$this->load_files($path);
			}
			
			$path = APPPATH . "Lang";
			$this->load_files($path);
			
			//if (self::$use_cache)
			//	$c->set("translator::trans", $this->trans);
		}
		
		//if (self::$use_cache)
		//	$this->cache = $c->get("translator::cache", array()); 
	}
	protected function trans($text, $lang) {
		if (is_null($lang))
			$lang = $this->lang;  
		
		if (self::$use_cache && isset($this->cache[$lang][$text]))
			return $this->cache[$lang][$text];    

		$terms = array();        
		if (strpos($text, ".") !== false) {
			$var = arrays::variations($text, true);
			
			$default = $var[count($var)-1];
			
			$terms = array_merge($terms, $var);
		} else {
			$default = $text;
			
			$terms = array($text);                            
		}
		
		$res = null;
		foreach ($terms as $term) {
			if (isset($this->trans[$lang][$term])) {
				$res = $this->trans[$lang][$term];                    
				break;
			}
		}
		
		if (self::$use_cache)
			$this->cache[$lang][$text] = $res;        
		
		if (is_null($res)) 
			$res = $default;
					
		return $res;
	}
	
	public static function & instance($config, $lang = null, $profile = null) {
		if (!translator::$instance) 
			translator::$instance = new translator($config, $lang, $profile);

		return translator::$instance;
	}
	public static function safe($text) {
		if (strpos($text, "<mark") !== 0) return $text;
		
		$p1 = strpos($text, ">");
		$p2 = strpos($text, "<", $p1);
		
		return substr($text, $p1+1, $p2-$p1-1);
	}
	
	public function __construct($lang, $profile) {
		$this->lang = $lang;  
		$this->profile = $profile; 
		
		$this->build();   
	} 
	public function __destruct() {
		if (self::$use_cache)
			Cache::instance()->set("translator::cache", $this->cache);	
	}
	public function add($key, $text, $lang = null) {
		if ($lang == null)
			$lang = $this->lang;
			              
		
		$this->trans[$lang][$key] = $text;
		$this->added[$lang][$key] = $text;
		
		if (self::$use_cache)
			$this->clear_cache();
	}
	public function get($text, $replace = null, $editable = null, $lang = null) {
		$trans = $this->trans($text, $lang);
		
		if (is_null($lang))
			$lang = $this->lang;
			
		$res = $trans;
		if (is_array($replace))
			$res = strtr($trans, $replace); 
			
		if ($editable) { 
			$text = htmlspecialchars($text);
			$trans = htmlspecialchars($trans);
			$res = "<mark class=\"translation\" data-key=\"$text\" data-lang=\"$lang\" data-trans=\"$trans\">$res</mark>";
		} 
		
		return $res;
	}
	public function routes() {
		$r = array();
		if (!isset($this->links[$this->lang])) 
			return $r;
		
		foreach ($this->links[$this->lang] as $name => $value) {
			$v = $value . "(\/.*)?";
			$r[$v] = $name . "\$1";   
		}      
		
		return $r;      	
	}
	public function url($url, $lang = null) {
		if (is_null($lang))
			$lang = $this->lang;  
			
		if (!isset($this->links[$lang])) return $url;

		$l = 0;
		$r = null;
		foreach ($this->links[$lang] as $name => $value) {
			if (strpos($url, $name) !== false && strlen($value) > $l) {
				$r = str_replace($name, $value, $url);
				$l = strlen($value);
			}
		}
		
		if ($r !== null)
			return $r; else
			return $url;
	}
	
	public function clear_cache() {
		$c = Cache::instance();
		$c->delete("translator::trans");
		$c->delete("translator::cache");
	}
	public function save($base = null, $profile = null) {   
		if (!$base)
			$base = APPPATH . "Lang";
			
		foreach ($this->added as $lang => $trans) {
			$fc = config::get("lang.file_mask." . $lang, $lang);
			if ($profile)
				$path = "$base/$profile.$fc.php"; else
				$path = "$base/$fc.php";
			
			$l = array();
			if (file_exists($path)) 
				include $path;	    
			
			$l = array_merge($l, $trans);  
			if (isset($this->removed[$lang]))
				foreach ($this->removed[$lang] as $n)
					unset($l[$lang][$n]);
                    
            ksort($l);
			
			$c = "<?php\n";
			foreach ($l as $key => $val) {
				$key = addslashes($key);
				$val = addslashes($val);
				$c.= "\$l[\"$key\"] = \"$val\";\n";
			} 
            
			file_put_contents($path, $c);
		}
	}
}

