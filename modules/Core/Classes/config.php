<?php
namespace Sloway;

class config {
	public static $instance = null;   

	public $configuration = array();
	
	public static function & instance() {
		if (!config::$instance) 
			config::$instance = new self();

		return config::$instance;
	}    
	
	public function load($group) {        
		if (isset($this->configuration[$group]))
			return;

		$app = new \Config\App();
		$mods = $app->modules;
											  
		$res = array();
		foreach ($mods as $mod_path) {
			$file = $mod_path . "/Config/$group.php";

			if (!file_exists($file)) continue;
			
			unset($config);
			include $file;
		
			if (isset($config) && is_array($config)) 
				$res = arrays::merge($res, $config);
		}        
		
		$file = APPPATH . "Config/$group.php";
		if (file_exists($file)) {
			unset($config);
			include $file;
		
			if (isset($config) && is_array($config)) 
				$res = arrays::merge($res, $config);
		}        
		
		return $res;
	}
	
	public static function get($path = null, $default = null) {
		$cfg = config::instance();
		
		if ($path == null) 
			return $cfg->configuration;
		
		$group = explode(".", $path, 2);
		$group = $group[0];
		
		if (!isset($cfg->configuration[$group])) {
			$cfg->configuration[$group] = $cfg->load($group);
		}

		$res = \Sloway\utils::value($cfg->configuration, $path, $default);

		return $res;
	}
	public static function set($path, $value) {
		$group = explode(".", $path, 2); 
		
		$cfg = config::instance();  
		$cfg->load($group[0]);
		$cfg->configuration = arrays::build_insert($cfg->configuration, $path, $value);
	}
	public static function add($path) {
		$args = func_get_args();
		for ($i = 1; $i < count($args); $i++) {
			$group = explode(".", $path, 2); 
			$value = $args[$i];
			
			$cfg = config::instance();  
			$cfg->load($group[0]);
			$cfg->configuration = arrays::md_append($cfg->configuration, $path, $value);
			echo xd($cfg->configuration);   
		}
	}
}
