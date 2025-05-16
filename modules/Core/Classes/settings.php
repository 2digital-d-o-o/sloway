<?php
	namespace Sloway;

	class Settings {
		public static $_data = array();
		public static function get($name, $def = '', $module = 'settings', $module_id = null) {
			if ($module == "settings" && $module_id == null) {
				if (!self::$_data) 
					self::$_data = mlClass::load("content", "@module = 'settings'", 0, array("index" => "name"));

				if (isset(self::$_data[$name]))
					return self::$_data[$name]->content; else
					return $def;
			} else {
				$sql = "@name = '$name' AND module = '$module'";
				if ($module_id !== null)
					$sql.= " AND module_id = '$module_id'";

				$r = mlClass::load('content', $sql, 1);
				if ($r)
					$res = trim($r->content); else 
					$res = $def;
			}

			return $res;
		}
		
		public static function set($name, $value, $module = 'settings', $module_id = null, $lang = null) {
			$sql = "@name = '$name' AND module = '$module'";
			if ($module_id !== null)
				$sql.= " AND module_id = '$module_id'";
				
			$r = mlClass::load('content', $sql, 1);
			if (!$r) { 
				$r = mlClass::create('content');
				$r->name = $name;
				$r->module = $module;
				$r->module_id = ($module_id) ? $module_id : '';	
			}
			
			$r->set("content", $value, $lang);
			$r->save();
		}
        
        public static function email() {
            $email = Settings::get("email");
            $e = explode(";", $email);
            return count($e) ? $e[0] : "";
        }
        public static function email_from() {
            $email = Settings::get("email");
            $e = explode(";", $email);
            if (count($e) > 1)
                return array($e[0] => $e[1]); else
                return $e[0];
        }
        
        public static function location() {
            $coord = arrays::explode(",", Settings::get("location_coord"));
            $address = Settings::get("location");            
            
            if (count($coord) == 2) {
                $res = new stdClass();
                $res->lat = $coord[0];
                $res->lng = $coord[1];
                $res->address = $address;
                
                return $res;
            }
            
            return null;
        }
	}  
?>
