<?php
	namespace Sloway;

	class dbModel {       
		public static $model;
		public static $db;
        public static $flags = array();
        public static $flags_curr = array();
		public static $preloaded = array();
		private static $parent;

        public static function parse_flags($name) {
            self::$flags_curr = array();//self::$flags;
            
            $e = explode("?", $name);    
            if (count($e) == 2) {
                $parts = explode("&", $e[1]);
                foreach ($parts as $part) {
                    $e1 = explode("=", trim($part));
                    self::$flags_curr[$e1[0]] = isset($e1[1]) ? trim($e1[1]) : "1";                        
                }
                $name = $e[0];
            } 
            
            return $name;
        }   
        /*
        public static function value($path, $default = "") {
            $val = null;
            if ($val === null) 
                $val = utils::value(self::$model, $path, null);
                
            if ($val === null) 
                $val = $default;
                
            return $val;
        } 
        */
        public static function value($path, $default = "") {
            $val = \Sloway\utils::value(self::$model, $path, null);
            
            $path = explode(".", $path);
            $res = self::$model;
            foreach ($path as $name) {
                $_res = null;
                if (isset(self::$flags_curr[$name])) {
                    $_name = $name . "?" . self::$flags_curr[$name];
                    
                    if (isset($res[$_name]))
                        $_res = $res[$_name];
                }
                if (is_null($_res) && isset($res[$name]))
                    $_res = $res[$name];
                
                if (is_null($_res)) 
                    return $default;
                
                $res = $_res;
            }
                
            return $res;
        } 
        public static function build_sql($name, $sql, $var_name, $parent, $order = null) {
			if (is_array($sql)) {
				if (count($sql))
					$sql = "@id IN (" . implode(",", $sql) . ")"; else
					$sql = "*";
			} else
			if (is_numeric($sql))
				$sql = "@id = '$sql'";
			
            $_sql = array($sql, self::value($name. ".sql", ""));
            /*
            foreach (self::$flags as $flag => $value) {
                if ($s = self::value($name. ".sql?" . $flag)) {
                    $_sql[] = str_replace("@V", $value, $s);
                }
            }
            */
            
            if ($parent) { 
                $psql = self::value($parent->_name . ".ch_sql." . $var_name);
                if (!$psql)
                    $psql = self::value($name . ".psql", "");
                    
                self::$parent = $parent;
                
                if (strpos($psql, "@P") !== false)
                    $psql = preg_replace_callback('%@P.([a-zA-Z0-9_=\!]+)%', "\Sloway\dbModel::parse_sql", $psql); 
				
                $_sql[] = $psql;
            } else
            if ($sql == "" || $sql == "*")
                $_sql[] = self::value("$name.rsql", ""); 
               
            return \Sloway\utils::sql_build($_sql, $order);
        }        
		public static function parse_sql($m) {
			$pn = $m[1];
			$pv = \Sloway\utils::value(self::$parent, $pn);
			
			return $pv;	
		}
        public static function get_ops(&$cname) {  
            $e = explode(":", $cname);
            $cname = $e[0];  
                           
            return (count($e) > 1) ? $e[1] : "";
        }
		public static function build_hiearchy($name) {
			$oname = $name;
			
			$cfg = self::$model[$name];
			$cfgs = array();
			
			while ($ext = \Sloway\utils::value(self::$model, $name . ".extends", null)) {
				$cfgs[] = \Sloway\utils::value(self::$model, $ext);
				
				$name = $ext;                				
			}
			
			if (!count($cfgs)) return;
			
			$cfgs = array_reverse($cfgs);
			$new_cfg = $cfgs[0];
			
			for ($i = 1; $i < count($cfgs); $i++) 
				$new_cfg = array_merge($new_cfg, $cfgs[$i]);
			
			$new_cfg = array_merge($new_cfg, $cfg);
			
			unset($new_cfg["extends"]);
			
			self::$model[$oname] = $new_cfg;
		}
        public static function duplicate_nodes($nodes, $parent, $depth = 0) {
            if ($nodes == null) return;
            
            if (!is_array($nodes))
                $nodes = array($nodes);
            
            foreach ($nodes as $node) {
                //echob(str_repeat("--", $depth), "duplicate(", $node->_name, ", ", $node->id, ")");
                $ch = self::value("$node->_name.ch", array());
                
                $n = dbModelObject::create($node->_name);
                $n->copy_from($node);
                $n->id_original = $node->id;
                $n->id = 0;
                
                if ($parent) {
                    $map = self::value($node->_name . ".duplicate", array("id_parent" => "@P.id"));
                    foreach ($map as $name => $value) {
                        if (strpos($value, "@P.") === 0) 
                            $value = v($parent, substr($value, 3), null);
                                
                        $n->$name = $value;
                    }
                }
                $n->__before_duplicate();
                $n->save();
                
                $n->__duplicate($node);
                $node->_duplicate = $n;
                
                foreach ($ch as $vname => $cname) {    
                    $ops = self::get_ops($cname); 
                                           
                    if (strpos($ops, "l") === false) {
                        self::duplicate_nodes($node->$vname, $n, $depth + 1);
                    }
                }
                
                $n->__finalize($node);
            }     
        }    
        public static function backup_nodes($nodes, $parent, $pid, $time, $tag, $all_ch, $trunc) {
            if ($nodes == null) return;
            
            if (!is_array($nodes))
                $nodes = array($nodes); 
            
            foreach ($nodes as $node) {
                $t = is_null($time) ? $node->edit_time : $time;
                
                if ($parent == null && history::collapse($node->id))
                    continue;                    
                
                $_pid = history::save($node->table, $node->id, $tag, $t, $node->__data, $pid);
                if ($trunc)
                    history::truncate($node->table, $node->id);
                    
                $node->history_id = $_pid;
                
                $ch = self::value("$node->_name.ch", array());
                foreach ($ch as $vname => $cname) {
                    $ops = self::get_ops($cname);
                    
                    if ($all_ch || strpos($ops, "b") !== false)
                        self::backup_nodes($node->$vname, $node, $_pid, $t, $tag, $all_ch, $trunc);
                }
            }     
        }          
		
		public static function build() {
			self::$model = array();

			foreach (core::$modules as $name => $path) {
				$fpath = $path . "/Config/dbmodel.php";
				if (file_exists($fpath)) {
					unset($model);
					require_once($fpath);
					
					if (isset($model))
						self::$model = array_merge_recursive(self::$model, $model);
				}
			}
			
			$path = APPPATH . "Config/dbmodel.php";
			if (file_exists($path)) {
				unset($model);
				require_once($path);
				
				if (isset($model))
					self::$model = array_merge_recursive(self::$model, $model);
			}

			foreach (self::$model as $name => $config) {
				self::build_hiearchy($name);	
			}
		}
        public static function count_children($name, $sql, $var_name, $parent) {
            $ch     = self::value("$name.ch", array());
            $table  = self::value("$name.table", $name);
			
			if (isset(self::$temp_tables[$table]))
				$table = self::$temp_tables[$table];
            
            $sql = self::build_sql($name, $sql, $var_name, $parent);
            $q = self::$db->query("SELECT COUNT(id) as cnt FROM `$table` WHERE " . trim($sql, "@#"))->getResult();
            
            return count($q) ? $q[0]->cnt : 0;
        }
		public static function load_preloaded($name, $sql, $limit, $parent, $var_name, $order_sql, $table, $index) {
			$objs = array();
			if ($parent) {
			//	LOAD FROM PRELOADED TREE
				if (isset(dbModel::$preloaded[$name]["tree"][$parent->id][$var_name])) {
					$ch_ids = dbModel::$preloaded[$name]["tree"][$parent->id][$var_name];

					$objs = array();
					foreach ($ch_ids as $ch_id) {
						if (isset(dbModel::$preloaded[$name]["objs"][$ch_id]))
							$objs[$ch_id] = dbModel::$preloaded[$name]["objs"][$ch_id];
					}	
				}
			} else {
				if (is_numeric($sql)) 
					$sql = [$sql];
				if (is_array($sql)) {
				//	LOAD DIRECTLY BY ID
					foreach ($sql as $id) {
						if (isset(dbModel::$preloaded[$name]["objs"][$id])) {
							$obj = dbModel::$preloaded[$name]["objs"][$id];
							if ($index && isset($obj->__data[$index]))
								$objs[$obj->__data[$index]] = $obj; else
								$objs[] = $obj;
						}
					}						
				} else {
				//	LOAD IDS BY SQL 
					$sql = self::build_sql($name, $sql, $var_name, $parent, $order_sql);
					foreach (self::$db->query("SELECT id FROM `$table` WHERE " . trim($sql, "@"))->getResult() as $q) {
						$id = $q->id;
						if (isset(dbModel::$preloaded[$name]["objs"][$id])) {
							$obj = dbModel::$preloaded[$name]["objs"][$id];
							if ($index && isset($obj->__data[$index]))
								$objs[$obj->__data[$index]] = $obj; else
								$objs[] = $obj;
						}
					}
				}

				if ($limit == 1)
					$objs = reset($objs);					
			}		
			return $objs;
		}
		public static function load_children($name, $sql, $limit, $parent, $var_name, $lang, $index = null, $param = null) { 
			$ch     = self::value("$name.ch", array());
			$order  = self::value("$name.order", false);
			$table  = self::value("$name.table", $name);
			$load   = self::value("$name.load", null);
			$class  = self::value("$name.class", "\Sloway\dbModelObject");
			if ($index === null)
				$index = self::value("$name.index", false);
			
            $order_sql = "";
			if (is_string($sql) && !is_numeric($sql)) {
				$e = explode("ORDER", $sql, 2);
				if (count($e))
					$sql = $e[0];
				if (count($e) > 1) 
					$order_sql = "ORDER" . $e[1];
			}
				
			if (!$order_sql) {
				if (is_string($order)) {
					$order_sql = $order;
				} else
				if ($order !== false)
					$order_sql = "ORDER BY $order ASC"; else
					$order_sql = "";
			}
				
			$objs = array();
			if (isset(dbModel::$preloaded[$name])) {
				$objs = self::load_preloaded($name, $sql, $limit, $parent, $var_name, $order_sql, $table, $index);
			} else {
				$sql = self::build_sql($name, $sql, $var_name, $parent, $order_sql);
				$objs = mlClass::load($table, $sql, $limit, array('index' => $index), $lang, $class);
			}

			if ($limit == 1)
				$objs = array($objs);
				
			$tmp = array();
			foreach ($objs as $key => $obj) {
				if (!$obj) continue;
				
				$obj->_sql = $sql;
				$obj->_name = $name;
				$obj->_parent = $parent;   
				$obj->_table = $table;
				
				foreach ($ch as $vname => $cname) {
					$ops = self::get_ops($cname); 
					              
					if (strpos($ops, "v") === false)
						self::load_children($cname, "", 0, $obj, $vname, $lang, null, $param);
				}
				
				if (is_callable($load))
					$s = call_user_func($load, $obj); else
					$s = $obj->__load($param);
				
				if ($s instanceof \Sloway\dbModelObject)
					$obj = $s;         
					
				$obj->_loaded = true;
				
				if ($s !== false) {
					if ($index === false)
						$tmp[] = $obj; else
						$tmp[$key] = $obj;
				} 
			}
			
			$objs = $tmp;  
			
			if ($parent) {
				$parent->_children[$var_name] = $objs; 
				$parent->_ch_sql[$var_name] = $sql;
			} else
			if ($limit == 1) {
				if (count($objs))
					return reset($objs); else
					return null;
			} else
				return $objs;
		}   
		public static function load($name, $sql = "*", $limit = 0, $index = null, $param = null, $lang = null) {
			$name = self::parse_flags($name);

			$res = self::load_children($name, $sql, $limit, null, null, $lang, $index, $param);
			
			return $res;
		}
		public static function delete($name, $sql) {
			$name = self::parse_flags($name);   
			$table = self::value("$name.table", $name);  
			
			$objs = mlClass::load($table, $sql);
			$ch = self::value("$name.ch", array());
			$on_delete = self::value("$name.on_delete", null);
			foreach ($objs as $obj) {
				$obj->_name = $name;
				foreach ($ch as $vname => $cname) {
					$ops = self::get_ops($cname);
					if (strpos($ops, "l") !== false) continue;
					
					$sql = self::build_sql($cname, "", $vname, $obj, "");
					
					self::delete($cname, $sql);    
				}
				if (is_callable($on_delete))
					call_user_func($on_delete, self::$db, $obj);

				$obj->delete();
			}   
		}
		public static function duplicate($name, $sql = "*", $limit = 0) {
			$nodes = self::load_children($name, $sql, $limit, null, null, "*");
            
			self::duplicate_nodes($nodes, null);
			
			if (is_array($nodes)) {
				$r = array();
				foreach ($nodes as $node)
					$r[] = $node->_duplicate;
				return $r;
			} else
				return $nodes->_duplicate;     
		}
        
        public static function backup($name, $id, $tag = "", $all_ch = false) {
			return;
            if (!config::get("backup.history")) return;
            
            $nodes = self::load_children($name, "@id = " . $id, 0, null, null, "*");
            
            self::backup_nodes($nodes, null, 0, null, $tag, $all_ch, true);
        }
        public static function restore($name, $id, $hid) {
            if (!config::get("backup.history")) return false;
            
            $status = history::status($hid);
            if (!$status) return false;
            
            if ($status != "active") {
                $nodes = self::load_children($name, "@id = " . $id, 0, null, null, "*");
                
                self::backup_nodes($nodes, null, 0, time(), "restore", false, false);
            }
            
            self::delete($name, "@id = " . $id);
            
            history::restore($hid);
        }
        public static function history($name, $id) {
            $table = self::value("$name.table", $name);  
            
            return history::find($table, $id);  
        }

		public static function preload($name, $objects, $tree) {
			self::$preloaded[$name] = array(
				"objs" => $objects,
				"tree" => $tree,
			);
		}
	}  

