<?php 

namespace Sloway;      

class userdata {   
	public static $data = null;
	public static $profile;
	public static $token = null;
	public static $cleanup_timeout = 7;
	public static $db;
	
	private static function update() {
		$n = "userdata_" . self::$profile;
		$c = get_cookie($n);
		if (!$c) return;
		
		$c = json_decode($c, true);		
		if (!$c) return;    
		
		self::get_token(true);		
		self::$data = $c;
		
		delete_cookie($n);
	}
	private static function get_token($generate = false) {
		$cn = "userdata_token";     
		self::$token = get_cookie($cn);
		
		if (!self::$token) {
			self::$token = md5(mt_rand() . microtime(true));  
			set_cookie($cn, self::$token, 14 * 24 * 60 * 60);
		}
		
		return self::$token;
	}
	private static function cleanup() {
		$t = strtotime("-" . self::$cleanup_timeout . " days");
		self::$db->query("DELETE FROM userdata WHERE time < $t");
	}
	private static function load($gen_token = false) {
		if (self::$data === null) {
			self::$data = array();
			self::cleanup();
			self::update();
			self::get_token($gen_token);
			
			if (self::$token) {
				$q = self::$db->query("SELECT * FROM userdata WHERE token = ?", [self::$token])->getResult();
				if (count($q)) {
					$d = json_decode($q[0]->data, true);
					if ($d && is_array($d))
						self::$data = array_merge(self::$data, $d);
				}
			} 
			self::save();
		}        
	}
	private static function save() {
		if (!self::$token) return;
		
		$q = self::$db->query("SELECT * FROM userdata WHERE token = ?", [self::$token])->getResult();
		if (!count($q)) {
			$q = self::$db->query("INSERT INTO userdata VALUES()");
			$id = self::$db->insertID();
		} else
			$id = $q[0]->id;
			
		self::$db->query("UPDATE userdata SET data = ?, token = ?, time = ? WHERE id = ?", [json_encode(self::$data), self::$token, time(), $id]);
	}
	
    public static function token() {
        self::load();  
        return self::$token;  
    }
	public static function get($name = null, $default = '') {
		self::load();
		
		$d = is_array(self::$data) ? self::$data : array();
		if ($name === null)
			return $d; else
			return isset($d[$name]) ? $d[$name] : $default;
	}
	public static function set() {
		self::load(true);  
		
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0])) {
			foreach ($args[0] as $name => $value)
				self::$data[$name] = $value;
				
			self::save();        
		} else
		if (count($args) == 2) {
			self::$data[$args[0]] = $args[1];
			self::save();
            
            return $args[1];        
		} 
	}
	public static function delete($name) {
		self::load();  
		
		if (isset(self::$data[$name])) {
			unset(self::$data[$name]);                    
			self::save();
		}
	}
	public static function clear() {
		self::$data = array();    
		self::save();
		
		delete_cookie("userdata_token");
	}
    
    public static function set_object($prefix, $obj) {
        foreach (get_object_vars($obj) as $key => $val) 
            userdata::set($prefix . $key, $val);    
    }
    public static function get_object($prefix, $keys) {
        self::load();
        $d = is_array(self::$data) ? self::$data : array();
        
        $res = new \Sloway\genClass();
        foreach ($keys as $key) {
            if (isset($d[$prefix . $key])) 
                $res->$key = $d[$prefix . $key]; else
                $res->$key = null;
        }
        
        return $res;
    }
    public static function set_array($prefix, $obj) {
        foreach ($obj as $key => $val) 
            userdata::set($prefix . $key, $val);    
    }
    public static function get_array($prefix, $keys) {
        self::load();
        $d = is_array(self::$data) ? self::$data : array();
        
        $res = array();
        foreach ($keys as $key) {
            if (isset($d[$prefix . $key])) 
                $res[$key] = $d[$prefix . $key]; else
                $res[$key] = null;
        }
        
        return $res;
    }    
}     


