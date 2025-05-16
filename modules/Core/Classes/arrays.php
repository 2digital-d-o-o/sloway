<?php
namespace Sloway;

class arrays {
	// [a => 10, b => 12] .... "a:10;b:12"
	public static function encode($arr, $sep1 = ":", $sep2 = ",") {
		if (!is_array($arr))
			return $arr;
			
		$res = "";
		$i = 0;
		foreach ($arr as $key => $val) {
			if ($i) $res.= $sep2;
			$res.= $key . $sep1 . $val; 
			
			$i++;               
		}
		
		return $res;
	}
	
	// "a:10;b:12" .... [a => 10, b => 12] 
	public static function decode($str, $sep1 = ":", $sep2 = ",") {
		if (is_array($str)) return $str;
		
		$res = array();
		$e = \Sloway\utils::explode($sep2, $str);
		foreach ($e as $p) {
			$ee = \Sloway\utils::explode($sep1, $p);        
			
			if (isset($ee[1]))
				$res[$ee[0]] = $ee[1];
		}
		
		return $res;
	}
	
	public static function mencode($arr, $sep1 = ":", $sep2 = ",") {
		if (!is_array($arr))
			return "";        
		
		$res = "";    
		$i = 0;
		foreach ($arr as $key => $val) {
			if (!is_array($val))
				$val = array($val);
			
			foreach ($val as $v) {
				if ($i) $res.= $sep2;
				
				$res.= $key . $sep1 . $v;	
			}
		}	    	
		
		return $val;
	}
	
	public static function mdecode($str, $sep1 = ":", $sep2 = ",", $unique = false) {
		if (is_array($str)) return $str;
		if (!is_string($str)) return array();
		
		$res = array();
		$e1 = explode($sep2, $str);
		foreach ($e1 as $p1) {
			$e2 = explode($sep1, $p1);
			if (count($e2) != 2) continue;
			
			$key = $e2[0];
			$val = $e2[1];    
			
			if (!isset($res[$key]) || !is_array($res[$key])) 
				$res[$key] = array();
			
			$res[$key][] = $val;
		}
        
        if ($unique)
            foreach ($res as $i => $val)
                $res[$i] = array_unique($val);
        
		
		return $res;
	}
	
	public static function mdecode_key($str, $key, $sep1 = ":", $sep2 = ",") {
		if (is_array($str)) return $str;
		if (!is_string($str)) return array();
		
		$res = array();
		$e1 = explode($sep2, $str);
		foreach ($e1 as $p1) {
			$e2 = explode($sep1, $p1);
			if (count($e2) != 2) continue;
			
			$k = $e2[0];
			$v = $e2[1];    

			if ($key == $k)
				$res[] = $v;
		}
		
		return $res;
	}
	
	// "a=>s:neki;b=>f:0.4" .... [a => neki(string), b => 0.4(float)]
	public static function options($str) {
		$res = array();
		$e1 = preg_split("%(,|;|&)%", $str);
		$i = 0;
		foreach ($e1 as $st) {
			$e2 = preg_split("%(=>|=)%", trim($st));
			
			$key = null;
			$typ = null;
			
			if (count($e2) == 1) {
				$val = trim($e2[0]);    
			} else {
				$key = trim($e2[0]);
				$val = trim($e2[1]);
			}
			
			if (($p = strpos($val, ":")) !== false) {
				$e3 = explode(":", $val);
				$val = trim($e3[1]);
				$typ = trim($e3[0]);    
			}
			
			switch ($typ) {
				case "i":
				case "int":
				case "integer":
					$val = intval($val);
					break;
				case "f":
				case "flt":
				case "float":
					$val = floatval($val);
					break;
				case "b":
				case "bool":
				case "boolean":
					$val = ($val > 0);
					break; 
			}
			
			if ($key)
				$res[$key] = $val; else
				$res[] = $val;
		}
		return $res;
	}
	
	public static function sub($array, $start, $end) {
		return array_slice($array, $start, $end - $start);	
	}
	
	public static function str_values($array) {
		$res = array();
		
		foreach ($array as $key => $val)
			$res[$key] = strval($val);
		
		return $res;
	}  	
    
	public static function value($array, $ind, $def = "") {
		if (is_array($array) && isset($array[$ind]))
			return $array[$ind]; else
			return $def;    
	}
	
    public static function to_keys($array, $st = null) {
        $res = array();    
        foreach ($array as $val)  
            $res[$val] = $st;
        
        return $res;
    }
    
	public static function states($array, $ref = null) {
		$res = array();
		
		if (is_array($ref))
		foreach ($ref as $val) 
			$res[$val] = false;
				
		foreach ($array as $val) 
			$res[$val] = true;
		   
		 return $res;                
	}
	
	public static function states_check($array) {
		$res = null;
		foreach ($array as $e) {
			$es = ($e) ? 1 : 0;
			if (is_null($res)) 
				$res = $es; else
			if ($res != $e) {
				$res = -1;	
				break;
			}
		}	
		
		return $res;
	}
	
	public static function regen($array, $key_sel = "id", $val_sel = "title", $sort = null) {
		$r = array();
		foreach ($array as $key => $val) {
			if ($key_sel == null) 
				$k = $key; else
			if (is_object($val))
				$k = $val->$key_sel; else
			if (is_array($val) && isset($val[$key_sel]))
				$k = $val[$key_sel]; else
				$k = $key;
				
			if ($val_sel == null) 
				$v = $val; else
			if (is_object($val))
				$v = $val->$val_sel; else
			if (is_array($val) && isset($val[$val_sel]))
				$v = $val[$val_sel]; else
				$v = $val;
				
			$r[$k] = $v;
		}
		if ($sort)
			asort($r);
		
		return $r;
	}
	
	public static function transform($array, $mask, $callback = null, $callback_data = null) {
		$res = array();
		
		if (is_string($mask)) 
			$mask = explode("=>", $mask);
		
		if (count($mask) != 2) return $array;
		
		$mkey = $mask[0];
		$mval = $mask[1];
		
		foreach ($array as $key => $val) {
			if ($mkey == 'k')
				$ckey = $key; else
			if ($mkey == 'v')
				$ckey = $val; else
				$ckey = utils::value($val, $mkey, $key);
				
			if ($mval == 'k')
				$cval = $key; else
			if ($mval == 'v')
				$cval = $val; else
			if ($mval[0] == '#')
				$cval = substr($mval, 1); else
				$cval = utils::value($val, $mval, $val);
				
			if (is_callable($callback)) {
				$r = call_user_func($callback, $ckey, $cval, $callback_data);
				$ckey = $r[0];
				$cval = $r[1];
			}
			
			$res[$ckey] = $cval;
		}    
		
		return $res;
	}
	
	public static function validate($array, &$valid, $exc = false) {
		foreach ($array as $key => $val) {
			$i = array_search($val, $valid);
			if ($i === false)
				unset($array[$key]); else
			if ($exc)
				unset($valid[$i]);
		}    
		
		return $array;
	}
	
	public static function fill($start, $end, $keys = false, $r = array()) {
		if ($start > $end) {
			$i1 = $end;
			$i2 = $start;
		} else {
			$i1 = $start;
			$i2 = $end;    
		}
		
		for ($i = $i1; $i <= $i2; $i++) {
			if ($keys)
				$r[$i] = $i; else
				$r[] = $i;    
		}
		
		if ($start > $end)
			return array_reverse($r, true); else 
			return $r;
	}   
	
	public static function split($array, $mask, $mode = "_key", $pkey = false) {
		$args = func_get_args();
		$keys = array_keys($array);
		
		if ($single = (strlen($mask) && $mask[0] == "s")) 
			$mask = substr($mask, 1);			
		
		$mask = explode(",", $mask);
		
		$res = array();
		foreach ($mask as $i => $arg) {
			if (count($array) == 0) {
				$res[$i] = array();
				continue;
			}
			
			$e = explode(":", $arg);
			if (count($e) == 1) {
				$i1 = $arg;
				$i2 = $arg;
			} else {
				$i1 = $e[0];
				$i2 = $e[1];
			} 
			
			$res[$i] = array();  
			
			if ($i1 == "") $i1 = 0;
			if ($i2 == "") $i2 = $keys[count($array)-1];
			
			foreach ($array as $key => $val) {
				if ($mode == "_key")
					$cmp = $key; else
				if ($mode == "_val")
					$cmp = $val; else
					$cmp = utils::value($val, $mode, null);
				
				if ($cmp === null) continue;
				
				if ($cmp >= $i1 && $cmp <= $i2) {
					if ($pkey)
						$res[$i][$key] = $val; else
						$res[$i][] = $val;
				}
			}
		}
		
		if ($single && count($res))
			$res = $res[0];
		
		return $res;		
	}        
	
	public static function complete($arr, $mask, $null = null) {
		$res = array();
		foreach ($mask as $msk) {
			$e = explode(":", $msk);
			if (count($e) == 2) {
				$m = preg_grep("/" . $e[1] . "/", array_keys($arr));
				foreach ($m as $key)
					$res[$key] = $arr[$key];
			} else {
				$res[$msk] = isset($arr[$msk]) ? $arr[$msk] : $null;
			}
		}	    	
		
		return $res;
	}
	
	public static function implode($del, $arr, $index = 0, $count = 0, $enclose = '', $val_sel = null) {
		if (!is_array($arr))
			return "";
        
        $arr = array_values($arr);
            			
		$c = count($arr);
        if ($c == 0) return "";
        
		$i1 = $index;
		
		if ($count <= 0)
			$i2 = $c - 1 + $count; else
			$i2 = $count;
			
		if ($i1 < 0) $i1 = 0;
		if ($i1 >= $c-1) $i1 = $c-1;
		
		if ($i2 < 0) $i2 = 0;
		if ($i2 >= $c-1) $i2 = $c-1;
		
		$r = "";
		for ($i = $i1; $i <= $i2; $i++) {
			if ($arr[$i] === null || $arr[$i] === "") continue;
			
			if ($i) $r.= $del;
            
            $val = $arr[$i];
            if (is_object($val) && $val_sel)
                $val = $val->$val_sel; else
            if (is_array($val) && $val_sel)
                $val = $val[$val_sel];
                        
			$r.= $enclose . $val . $enclose;
		}
		
		return $r;
	}
	
	public static function explode($del, $str, $trim = false) {
		if (!is_string($str) || strlen($str) == 0 || $str == $del)
			return array(); 

		$res = explode($del, $str);         
		if ($trim)
			foreach ($res as $i => $val) {
				$res[$i] = trim($val);
				if ($val == "")
					unset($res[$i]);
			}
		
		return $res;
	} 
	
	public static function parts($del, $str) {
		if (is_array($del))
			$del = implode("", $del);
			
		$parts = preg_split("/([$del])/", $str, -1, PREG_SPLIT_DELIM_CAPTURE);
		$res = array();
		for ($i = 0; $i < count($parts); $i+=2) {
			$s = $parts[$i];
			if ($i)
				$s = $parts[$i-1].$s;
			$res[] = $s;
		}
		
		return $res;
	} 
	
	/*  pos: <pos>:<element>
		pos:    
			a        - insert after element (default)
			b        - insert before element
		element:    
			end,e    - element = end of array
			start,s  - element = beginning of array
			#<value> - find element by key
			@<value> - fing element by value
	*/
	public static function insert(&$array, $mixed, $pos = null) {
		if (!$pos) 
			$pos = "a:end";
		
		if (strpos($pos, ":") !== false) {
			$e = explode(":", $pos);
			$m = ($e[0] != "") ? $e[0] : "a";
			if (count($e) > 1) {
				if ($e[1] != "")
					$p = $e[1]; else
				if ($m == "a")
					$p = "end"; else
				if ($m == "b")
					$p = "start"; else
					return $array;
			}
		} else {
			$m = "a";
			$p = $pos;			
		}
		
		if ($p === "end" || $p === "e") 
			$p = count($array)-1; else
		if ($p === "start" || $p === "s") 
			$p = 0; else
		if ($p[0] == "#") {
			$p = array_search(substr($p, 1), array_keys($array), false);
			if ($p === false)
				return $array;    
		} else
		if ($p[0] == "@") {
			$p = array_search(substr($p, 1), $array, false);
			if ($p === false)
				return $array;	
		}
		
		if ($m == "b" || $m == "r")
			$p--;
		$c = ($m == "r") ? 1 : 0;
		
		if (!is_array($mixed))
			$mixed = array($mixed);
			
		if ($p >= 0) 
			$array = array_merge(array_slice($array, 0, $p+1, true), $mixed, array_slice($array, $p+1, NULL, true)); else 
			$array = array_merge(array($mixed), $array);
			
		return $array;
	}
	
	public static function filter($array, $eval, $pkey = false) {
		$res = array();
		
		foreach ($array as $key => $val) {
			if (is_callable($eval))
				$r = call_user_func($eval, $key, $val); else
				$r = eval($eval);
			
			if (!$r) continue;
			
			if ($pkey)
				$res[$key] = $val; else
				$res[] = $val;
		}
		
		return $res;
	}

	public static function first($array, $key = false, $default = null) {
		if (!is_array($array) || !count($array)) 
			return $default;
		
		if ($key) {
            $keys = array_keys($array);
			return reset($keys); 
        } else
			return reset($array);
	}
    public static function json_decode($string) {
        $r = json_decode($string, true);
        if (!is_array($r)) $r = array();
        
        return $r;    
    }
	
	public static function extend() {
		$args = func_get_args();
		if (!count($args)) return null;
		
		$res = $args[0];
		for ($i = 1; $i < count($args); $i++) {
			$arg = $args[$i];
			
			if (!is_array($arg)) continue;
			
			foreach ($arg as $key => $val) {
				if (!is_null($val))
					$res[$key] = $val;
			}
		}
		
		return $res;
	}
	
	//  "a.b.c.d
	public static function variations($string, $reverse = false, $sep = ".") {
		$e = explode($sep, $string);
		$res = array();
		
		$curr = null;
		foreach ($e as $p) {
			if (is_null($curr))
				$curr = $p; else
				$curr.= $sep . $p;
			
			$res[] = $curr;
		}
		
		if ($reverse)
			$res = array_reverse($res);
		
		return $res;
	}
	
	public static function prefix($array, $prefix, $mode = "value") {
		$r = array();
		foreach ($array as $key => $val) {
			if ($mode == "value")                     
				$r[$key] = $prefix . $val; else 
			if ($mode == "key")
				$r[$prefix . $key] = $val;       
		}	
		
		return $r;
	}
	
	public static function echo_fields($array) {
		$r = "";
		$args = func_get_args();
		unset($args[0]);
		
		foreach ($array as $key => $value) {
			if (!is_scalar($value)) {
				$r.= $key . " => (";
				foreach ($args as $i => $arg) {
					if ($i-1)
						$r.= ",";
					
					$v = utils::value($value, $arg, null);
					$r.= $arg . " = '" . $v . "'";
				}
				$r.= ")";
			} else
				$r.= $key . " => " . $value; 
			
			$r.= "<br />";
		}
		
		return $r;
	}
	
	public static function md_build($path, $value, $sep = ".") {
		if (is_string($path))
			$path = explode($sep, $path);
			
		if (count($path) == 1)
			return array($path[0] => $value);
			
		$key = array_shift($path);
		return array($key => arrays::md_build($path, $value, $sep));
	}
		
	public static function md_insert($target, $path, $value, $sep = ".") {
		return arr::merge($target, arrays::md_build($path, $value, $sep));    
	}
	
	public static function md_overwrite($target, $path, $value, $sep = ".") {
		$a = arr::merge($target, arrays::md_build($path, null, $sep));       
		return arr::merge($a, arrays::md_build($path, $value, $sep));                 
	}
	
	public static function md_append($target, $base, $value, $sep = ".") {
		$v = utils::value($target, $base);
		if (!is_array($v))
			$v = array($value); else
			$v[] = $value;
															
		return arrays::md_overwrite($target, $base, $v, $sep);
	}   

	public static function merge()
	{
		$total = func_num_args();

		$result = array();
		for ($i = 0; $i < $total; $i++)
		{
			foreach (func_get_arg($i) as $key => $val)
			{
				if (isset($result[$key]))
				{
					if (is_array($val))
					{
						// Arrays are merged recursively
						$result[$key] = arrays::merge($result[$key], $val);
					}
					elseif (is_int($key))
					{
						// Indexed arrays are appended
						array_push($result, $val);
					}
					else
					{
						// Associative arrays are replaced
						$result[$key] = $val;
					}
				}
				else
				{
					// New values are added
					$result[$key] = $val;
				}
			}
		}

		return $result;
	}

	public static function make_tree($objs, $var_name, $id_sel = "id", $pid_sel = "id_parent") {
		$tree = array();
		foreach ($objs as $obj) {
			if ($obj->$pid_sel) {
				if (!isset($tree[$obj->id_parent])) 
					$tree[$obj->id_parent] = array("$var_name" => array());
				
				$tree[$obj->id_parent]["$var_name"][]= $obj->id;
			}
		}		
		
		return $tree;
	}
	
	public static function partition($arr, $size) {
		$res = array();
		$i = 0;
		foreach ($arr as $key => $val) {
			$p = intval($i / $size);
			
			if (!isset($res[$p]))
				$res[$p] = array();
			
			$res[$p][$key] = $val;
			
			$i++;
		}
		
		return $res;
	}
}
