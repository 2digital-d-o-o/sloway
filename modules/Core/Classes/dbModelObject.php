<?php
	namespace Sloway;

    class dbModelObject extends mlClass {   
        public $_name;
        public $_sql;
        public $_parent; 
        public $_loaded = false;
        public $_duplicate;
        public $_children = array();
        public $_ch_sql = array();
        
        private static function set_name($objs, $name) {
            if (is_array($objs)) {
                foreach ($objs as $obj)
                    $obj->_name = $name;
            } else 
            if (is_object($objs))
                $objs->_name = $name;
                
            return $objs;
        }         
        
        public function children($names = null) {
            if (is_string($names)) 
                $names = explode(",", $names);
                                
            $valid = \Sloway\utils::value(dbModel::$model, "$this->_name.ch", array());
            if (!is_array($names) || !count($names))
                $names = array_keys($valid);
            
            $res = array();
            foreach ($names as $name) {
                $name = trim($name);    
                if (!isset($valid[$name])) continue;
                
                if (!isset($this->_children[$name])) {
                    $obj_name = $valid[$name];

                    dbModel::get_ops($obj_name);
                    dbModel::load_children($obj_name, "", 0, $this, $name, null, false); 
                }
                
                $res+= $this->_children[$name];
            }
            return $res;
        }
        public function children_count($names = null) {
            if (is_string($names)) 
                $names = explode(",", $names);
                                
            $valid = \Sloway\utils::value(dbModel::$model, "$this->_name.ch", array());
            if (!is_array($names) || !count($names))
                $names = array_keys($valid);
            
            $res = 0;
            foreach ($names as $name) {
                $name = trim($name);    
                if (!isset($valid[$name])) continue;
                
                if (!isset($this->_children[$name])) {
                    $obj_name = $valid[$name];

                    dbModel::get_ops($obj_name);
                    $res+= dbModel::count_children($obj_name, "", $name, $this); 
                } else 
                    $res+= count($this->_children[$name]);
            }
            return $res;
        }        
        
        public function __get($name) {
            $ch = \Sloway\utils::value(dbModel::$model, "$this->_name.ch", array());

            if (isset($ch[$name])) {
                if (!isset($this->_children[$name])) {
                    $n = $ch[$name];
                    
                    $ops = dbModel::get_ops($n);
                    
                    dbModel::load_children($n, "", 0, $this, $name, null); 
                }
                return $this->_children[$name];
            } else 
                return parent::__get($name);
        }
        public function __set($name, $value) {
            if (isset($this->_children[$name]))
                $this->_children[$name] = $value; else
                parent::__set($name, $value);
        } 
        public function __load($param) {  
        }
        public function __before_duplicate() {
        }
        public function __duplicate($orig) {
        }
        public function __finalize($orig) {
        }
        
        /*
        public function ch_count($names = null) {
            if (is_string($names)) 
                $names = "," . trim($names, ",") . ",";
            
            $ch = utils::value(dbModel::$model, "$this->_name.ch", array());
            $res = 0;
            foreach ($ch as $name => $o) {
                if ($names && strpos($names, $name) === false) continue;
                $res+= dbModel::count_children($name, "", 
            }
            
            if (isset($ch[$name])) {
                if (!isset($this->_children[$name])) {
                    $n = $ch[$name];
                    
                    //$ops = dbModel::get_ops($n);
                    
                    dbModel::load_children($n, "", 0, $this, $name, null); 
                }
                return $this->_children[$name];
            } else 
                return parent::__get($name);
        } */       
        public function ch_get($name = null) {
                
        }
        
        public static function load($name, $sql = "*", $limit = 0, $options = null, $lang = null, $class = '\Sloway\dbModelObject') {
            $name = dbModel::parse_flags($name);
            
            $table = dbModel::value("$name.table", $name);
            $class = dbModel::value("$name.class", "\Sloway\dbModelObject");
            
            $r = parent::load($table, $sql, $limit, $options, $lang, $class);
            return self::set_name($r, $name);
        }
        public static function create($name, $id = 0, $options = null, $lang = null, $class = '\Sloway\dbModelObject') {
            $name = dbModel::parse_flags($name); 
            $table = dbModel::value("$name.table", $name);
            $class = dbModel::value("$name.class", "\Sloway\dbModelObject");
            
            $r = parent::create($table, $id, $options, $lang, $class);
            return self::set_name($r, $name);         
        }
        public static function post($name, $id = 0, $options = null, $lang = null, $class = '\Sloway\dbModelObject') {
            $name = dbModel::parse_flags($name);
            $table = dbModel::value("$name.table", $name);
            $class = dbModel::value("$name.class", "\Sloway\dbModelObject");
            
            $r = parent::post($table, $id, $options, $lang, $class);  
            return self::set_name($r, $name);         
        }
        
    }
