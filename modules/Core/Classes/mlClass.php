<?php 
namespace Sloway;

/*
    mlClass.load( table, query, limit, lang, options )
    table   - ime tabele
    query   - mysql query, ce se zacne z %, ga sam dokonca
            - "%id = 1" -> "SELECT * FROM <table> WHERE id = 1"
    limit   - 0 = array, 1 = objekt, n = array(n)
    lang    - array (<lang>, <lang>) - kere jezike bere iz baze
            - null -> bere mlClass::$lang
            - '<lang>' .. samo en jezik
            - '*' ... bere vse jezike
    options - se pokrivajo z dbClass options
*/    


class mlClass extends dbClass {
    public static $multilang = false;
    public static $def_lang = 'si';
    public static $lang = 'si';
	public static $langs = array();
    public static $mlf = array();
	public static $tab_struct = array();
    
	public $_ml = true;
    public $curr_lang = ''; 
    
	private static function getTableStruct($name) {
		if (!isset(self::$tab_struct[$name])) 
			self::$tab_struct[$name] = dbClass::$database->query("SHOW TABLES LIKE '" . $name . "'")->getResult();
		
		return self::$tab_struct[$name];
	}
    private static function genMLTable($table, $mlf, $db) {
		$q = self::getTableStruct($table . "_ml");
        //$q = $db->query("SHOW TABLES LIKE '" . $table ."_ml'")->getResult();
        if (count($q)) {
            $q = $db->query("SHOW COLUMNS FROM `" . $table . "_ml`")->getResult(); 
            $f = array();
            
            foreach ($q as $r)
                $f[] = $r->Field;
            
            foreach ($mlf as $field) {
                if (!in_array($field, $f)) 
                    $db->query("ALTER TABLE `" . $table . "_ml` ADD `" . $field . "` text");
            }
        } else {
            $q = "CREATE TABLE IF NOT EXISTS `" . $table ."_ml` (
              `table_id` int NOT NULL,
              `lang` varchar(20) collate utf8_slovenian_ci NOT NULL";
              
            foreach ($mlf as $f)
                $q .= ", `" . $f . "` text collate utf8_slovenian_ci";
                
            $q .= " ,PRIMARY KEY (`table_id`,`lang`)) ENGINE=MyISAM DEFAULT CHARSET=utf8";
            
            $db->query($q);
        }
    }     

    public static function load($table, $query = "", $limit = 0, $options = null, $lang = null, $class = "\Sloway\mlClass") {
        $objs = parent::load($table, $query, $limit, $options, $class); 
		
        if ($lang == "*" || $lang == "_all") {
            $load_lang = mlClass::$langs;
            $curr_lang = mlClass::$lang;
            $load = true;
        } else
        if ($lang == null) {
            $load_lang = array(mlClass::$lang);
            $curr_lang = mlClass::$lang;
            $load = $curr_lang != mlClass::$def_lang;
        } else {
            if (is_string($lang))
                $load_lang = array($lang); else
                $load_lang = $lang;
                
            $curr_lang = $load_lang[0];
            $load = ($curr_lang != mlClass::$def_lang || count($load_lang));           
        }       
		
		if (is_array($objs))
			foreach ($objs as $obj) {
				$obj->curr_lang = $curr_lang;
				$obj->_ml = true;
				if ($obj->_trans)
					$obj->_ml = false; else
				if ($load) 
					$obj->load_lang($load_lang); 
			}
		if (is_object($objs)) {
			$objs->curr_lang = $curr_lang;
			$objs->_ml = true;
			if ($objs->_trans)
				$objs->_ml = false; else
			if ($load) 
				$objs->load_lang($load_lang); 
		}
        
        return $objs;
    }
    public static function create($table, $id = 0, $options = null, $lang = null, $class = "\Sloway\mlClass") {
        if ($id)
            $obj = self::load($table, "@id = $id", 1, $options, $lang, $class); 
        else {
            if (is_callable($class))
                $class = call_user_func($class, $table, null);
                
            $table = parent::getTable($table);
            $obj = new $class($table , array('id' => $id), dbClass::getOps($options));
        }
            
        $obj->curr_lang = ($lang) ? $lang : mlClass::$lang;
        
        return $obj;
    }
    public static function post($table, $id = 0, $options = null, $lang = null, $class = '\Sloway\mlClass') {
		$obj = self::create($table, $id, $options, $lang, $class);
		$obj->read_post();
        
        return $obj;
    }    
    public static function load_def($table, $query = "", $limit = 0, $options = null, $lang = null, $class = "\Sloway\mlClass") {
        $res = self::load($table, $query, $limit, $options, $lang, $class);
        if (is_null($res))
            $res = self::create($table);
           
        return $res;    
    }
	
    public function __get($name) {
		return $this->get($name);
    }
    public function __set($name, $value) {
		$this->set($name, $value);
    }
	
    public function languages() {
        $db = self::getDB($this->ops);       
        if (!self::getMLF($this->table, $mlf)) 
            return array(mlClass::$def_lang);
        
        $c = count(dbClass::$database->query("SHOW TABLES LIKE ?", [$this->table . "_ml"])->getResult());
        if (!$c) 
            return array(mlClass::$def_lang);
        
        $q = $db->query("SELECT lang FROM `{$this->table}_ml` WHERE table_id = ?", [$this->id])->getResult();    
        $r = array(mlClass::$def_lang);
        foreach ($q as $qq)
            $r[] = $qq->lang;
            
        return $r;
    }
	public function read_post($map = null) {
		$db = self::getDB($this->ops);        
		
		if (!$map) {
		//	READ ACCORDING TO DB TABLE FIELDS
			$fields = self::getFields($this->table, $db);

			$mlf = isset(self::$mlf[$this->table]) ? self::$mlf[$this->table] : array();
			foreach ($fields as $name) {
				if (isset($_POST[$name])) {
					$this->__data[$name]= $_POST[$name];
				}
				if (!in_array($name, $mlf)) continue;
				foreach (self::$langs as $lng) {
					if (isset($_POST[$name . ":" . $lng])) {
						$dn = $name;
						if ($lng != self::$def_lang)
							$dn.= ":" . $lng;

						$this->__data[$dn] = $_POST[$name . ":" . $lng];
					}
				}
			}
		} else 
		if (is_array($map)) {
		//	READ FROM MAP
			foreach ($map as $from => $to) {
				foreach (self::$langs as $lang) {
					$_from = $from;
					$_to = $to;
					if ($lang != mlClass::$def_lang) {
						$_from.= ":" . $lang;
						$_to.= ":" . $lang;
					}
					
					if (isset($_POST[$_from]))
						$this->__data[$_to] = $_POST[$_from];
				}
			}
		}
	}
    public function load_lang($langs) {
        $db = self::getDB($this->ops);       
        
        $mlf = isset(self::$mlf[$this->table]) ? self::$mlf[$this->table] : array();
        
		$c = count(self::getTableStruct($this->table . "_ml"));
        if (!$c) return;
        
        $lsql = '';
		if (is_string($langs))
			$langs = array($langs);
        if (is_array($langs)) 
            $lsql = "AND lang IN ('" . implode("','", $langs) . "')"; 
		
		$id = $this->__data["id"];
        $q = $db->query("SELECT * FROM `{$this->table}_ml` WHERE table_id = '$id' $lsql")->getResult();    
        foreach ($q as $qe) {
            foreach ($mlf as $fname) {
                if (isset($qe->$fname) && !is_null($qe->$fname) && !empty($qe->$fname))
                    $this->__data[$fname . ':' . $qe->lang] = $qe->$fname;
            }
        }        
    }  	
	public function preload() {
		$mlf = isset(self::$mlf[$this->table]) ? self::$mlf[$this->table] : array();
		foreach ($mlf as $fname) {
			if (isset($this->__data[$fname]))
                $this->__data[$fname . ':' . $this->curr_lang] = $this->__data[$fname];
		}
	}
    public function save() {
        $db = self::getDB($this->ops);
        parent::save();
		
		$mlf = isset(self::$mlf[$this->table]) ? self::$mlf[$this->table] : array();
        self::genMLTable($this->table, $mlf, $db);
        
        $vals = array();
        foreach ($this->__data as $name => $value) {
            $pos = strpos($name,':');
            if ($pos === false) continue;
            
            $p = explode(':', $name);
            
            if (in_array($p[0], $mlf))
                $vals[$p[1]][$p[0]] = $value;            
        }
        
        foreach ($vals as $lang => $values) {
            $q = $db->query("SELECT * FROM `{$this->table}_ml` WHERE lang = ? AND table_id = ?", [$lang, $this->id])->getResult();            
            if (!count($q)) 
                $db->query("INSERT INTO `{$this->table}_ml` (table_id,lang) VALUES (?,?)", [$this->id, $lang]); 
            
            $sql = "UPDATE `{$this->table}_ml` SET " . implode("=?, ",array_keys($values)) . "=? WHERE lang = ? AND table_id = ?";  
            
            $values[] = $lang;
            $values[] = $this->id;
            
            $q = $db->query($sql, array_values($values));
        }
    }
    public function delete() {
        $db = self::getDB($this->ops);
        parent::delete();     

        if (self::ml()) {
            self::getMLF($this->table, $mlf);
            $q = $db->query("SHOW TABLES LIKE '" . $this->table ."_ml'")->getResult();
            if (count($mlf) && count($q))
                $db->query("DELETE FROM `{$this->table}_ml` WHERE table_id = '$this->id'");
        }
    }
	
	public function get($name, $lang = null) {
		if (!$this->_ml)
			return isset($this->__data[$name]) ? $this->__data[$name] : null;
		
		$mlf = isset(self::$mlf[$this->table]) ? self::$mlf[$this->table] : array();		
		if (!in_array($name, $mlf)) 
			return isset($this->__data[$name]) ? $this->__data[$name] : null;
		
		if (is_null($lang)) 
			$lang = $this->curr_lang; else
        if ($lang == "_def")
            $lang = mlClass::$def_lang; 
		
	//	RETURN ALL LANGUAGES
		if ($lang == "*" || $lang == "_all") {
			$res = array();
			foreach (mlClass::$langs as $l) 
				if ($l == mlClass::$def_lang) 
					$res[$l] = isset($this->__data[$name]) ? $this->__data[$name] : null; else
				if (isset($this->__data[$name . ":" . $l]))
					$res[$l]= $this->__data[$name . ":" . $l]; else
					$res[$l] = "";
				
			return $res;
		} 
		
	//	RETURN DEFAULT LANGUAGE
        if ($lang == mlClass::$def_lang)
            return isset($this->__data[$name]) ? $this->__data[$name] : "";
		
	//	RETURN NONDEFAULT LANGUAGE
        if (isset($this->__data[$name . ":" . $lang]))
            return $this->__data[$name . ":" . $lang]; 
    }
	public function get_ml($name) {
		return $this->get($name, "_all");
	}
	
    public function set($name, $value, $lang = null) { 
		if (!$this->_ml) {
			$this->__data[$name] = $value;
			return;
		}
		$mlf = isset(self::$mlf[$this->table]) ? self::$mlf[$this->table] : array();		
		if (!in_array($name, $mlf)) {
			$this->__data[$name] = $value;
			return;
		}
		
	//	VALUE IS ARRAY lang => val
		if (is_array($value)) {
			foreach ($value as $lng => $val) {
				if ($lng == "_def") $lng = mlClass::$def_lang;
				
				if ($lng != mlClass::$def_lang)
					$this->__data[$name . ":" . $lng] = $val; else
					$this->__data[$name] = $val;
			}
			
			return;
		}
		
	//	COPY VALUE TO ALL LANGUAGES
		if ($lang == "_all") {
			foreach (mlClass::$langs as $lng) {
				if ($lng != mlClass::$def_lang)
					$this->__data[$name . ":" . $lng] = $value; else
					$this->__data[$name] = $value;
			}
			return;
		}
		
	//	SET VALUE TO SINGLE LANGUAGE
		if (is_null($lang)) $lang = $this->curr_lang;
		if ($lang == "_def") $lang = mlClass::$def_lang;
        if ($lang && $lang != mlClass::$def_lang) 
            $this->__data[$name . ':' . $lang] = $value; else
            $this->__data[$name] = $value;
    }  
}
