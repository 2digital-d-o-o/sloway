<?php

namespace Sloway;

class utils {
    public static $currency = '€';
	public static $timer = 0;
	public static $debug = false;
    public static $counter = 0;
	/**
	 * Read values from multidimensional array/ objects 
	 * Examples: 
	 * - value(null, 'prop', 'default') ... 'default'
	 * - value('a', 'prop', 'default') ... 'default'
	 * - value(array('a' => 'neki'), 'a') ... 'neki'
	 * - value(array('a' => array('b' => 'neki')), 'a.b') ... 'neki'
	 * @param $obj (object,array,string,null) Source from where to extract value.
	 * @param $path (string) Method/index path
	 * @param $default (mixed) Value to return on failure
	 * @return value
	 */    
    public static function parse_node($item) {
        $res = array(
            "title" => "",
            "attr" => array()
        );
        $br1 = strpos($item, "{");
        $br2 = strpos($item, "}");
    
        $t = ($br1) ? substr($item, 0, $br1) : $item;
        $res["title"] = str_replace(array("__DEL1__", "__DEL2__"), array("{", "}"),  $t);
        
        $ops = ($br1 && $br2) ? substr($item, $br1+1, $br2-$br1-1) : "";
            
        if (!empty($ops)) {            
            $ops = explode(",", $ops);
            foreach ($ops as $pair) {
                $pair = explode("=", $pair);
                $op_name = trim($pair[0]);
                $op_val = count($pair) > 1 ? trim($pair[1]) : 1;
                
                $res["attr"][$op_name] = $op_val;
            }
        }        
        
        return $res;
    }    
    public static function serialize_tree($tree, $property = "id") {
        $res = array();
        foreach ($tree as $key => $val) {
            $node = utils::parse_node(is_array($val) ? $key : $val);
            $prop = v($node, "attr." . $property);
            
            if (is_array($val)) {
                $sub = utils::serialize_tree($val, $property);
                foreach ($sub as $sub_prop)
                    $res[] = $prop . "." . $sub_prop;    
            }
            
            $res[] = $prop;
        }
        return $res;
    }    
    public static function generate_id() {
        return "site_" . str_replace(".", "", microtime(true)) . self::$counter++;        
    }    
     
	public static function html_specialchars($str, $double_encode = TRUE) {
		// Force the string to be a string
		$str = (string) $str;

		// Do encode existing HTML entities (default)
		if ($double_encode === TRUE)
		{
			$str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
		}
		else
		{
			// Do not encode existing HTML entities
			// From PHP 5.2.3 this functionality is built-in, otherwise use a regex
			if (version_compare(PHP_VERSION, '5.2.3', '>='))
			{
				$str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8', FALSE);
			}
			else
			{
				$str = preg_replace('/&(?!(?:#\d++|[a-z]++);)/ui', '&amp;', $str);
				$str = str_replace(array('<', '>', '\'', '"'), array('&lt;', '&gt;', '&#39;', '&quot;'), $str);
			}
		}

		return $str;
	}
	public static function debug() {
		if (func_num_args() === 0)
			return;

		// Get params
		$params = func_get_args();
		$output = array();

		foreach ($params as $var)
		{
			$output[] = '<pre>('.gettype($var).') '.utils::html_specialchars(print_r($var, TRUE)).'</pre>';
		}

		return implode("\n", $output);
	}

	public static function value($obj, $path, $default = '') {
		if (!$obj) return $default;
        if (strval($path) == "") return $default;
		
		if (is_string($path))
			$p = ($path != '') ? explode('.', $path) : array(); else
			$p = array($path);    
            
        //echod($p);
		
		foreach ($p as $pp) {
			if ($by_ind = (strlen($pp) && $pp[0] == "#"))
				$pp = substr($pp,1);
			
			if (is_array($obj)) {
				if ($by_ind) $obj = array_values($obj);
				
				if (isset($obj[$pp])) 
					$obj = $obj[$pp]; else
					return $default;
			} else 
			if (is_object($obj)) {
				$c = get_class($obj);
				if ($c == "Input") {
					if (($obj = $obj->post($pp)) == null)
						return $default; 
				} else
				if ($c == "Session") {
					if (($obj = $obj->get($pp, null)) == null)
						return $default;
				} else {
					@$obj = $obj->$pp;
					if ($obj === null)
						return $default;
				}
			} else
				return $default;
		}
		
		return $obj;
	}
	public static function search() {
		$args = func_get_args();
		foreach ($args as $arg)
			if ($arg) return $arg;
		
		return null;	
	}
	public static function where_and($c, $st = false) {
		$r = ($st) ? "AND " : "";
		$r.= implode(" AND ", $c);
		
		return $r;
	}
	
	/**
	 * Result = ($cond) ? $true : $false
	 */      
	public static function cvalue($cond, $true, $false = "") {
		if ($cond)    
			return $true; else
			return $false;
	}
	public static function avalue($array, $ind, $def = "") {
		if (is_array($array) && isset($array[$ind]))
			return $array[$ind]; else
			return $def;    
	}
    public static function afvalue($array, $ind, $flags, $def = "") {
        if (!is_array($array) || empty($ind)) return $def;    
        
        foreach ($flags as $name => $val)
            if ($val === true)
                $flags[$name] = "1";
        
        $result = $def;
        foreach ($array as $key => $val) {
            // echob("Testing $key => $val");
            if (strpos($key, $ind) !== 0) continue;            
            
            $e = explode("?", $key);
            if (count($e) == 2) {
                $parts = explode("&", $e[1]);
                $match = true;
                foreach ($parts as $part) {
                    $rule = explode("=", $part);
                    $flag = $rule[0];
                    $cond = isset($rule[1]) ? $rule[1] : "1";
                    
                    $m = isset($flags[$flag]) && $flags[$flag] === $cond;
                    // echob("COND $flag = $cond: " , $m ? "yes" : "no");
                    
                    if (!$m) {
                        $match = false;
                        break;
                    }                    
                }            
                if ($match) {
                    $result = $val;
                    break;
                }
            } else
                $result = $val;
        }
        
        return $result;
    }
	
	public static function cookie($name, $default) {
		$c = get_cookie($name);
		if ($c === null) {
			$c = $default;
			set_cookie($name, $c, 604800);
		}    
		
		return $c;
	}
	public static function session($name, $default) {
		$s = Session::instance();
		$r = $s->get($name, null);
		if ($r === null) {
			$s->set($name, $default);
			$r = $default;
		}
		
		return $r;
	}
	public static function getkey($array, $ind) {
		$keys = array_keys($array);
		
		if (isset($keys[$ind]))
			return $keys[$ind]; else
			return null;    
	}

	public static function array2str($arr, $del1 = ";", $del2 = ":") {
		$r = "";
		$i = 0;
		foreach ($arr as $n => $v) {
			if ($i) $r.= $del1;
			
			$r.= $n . $del2 . $v;
			
			$i++;    
		}
		return $r;
	}
	public static function str2array($str, $del1 = ";", $del2 = ":") {
		$r = array();
		$e = utils::explode($del1, $str);
		foreach ($e as $ee) {
			$ee = utils::explode($del2, $ee);
			if (count($ee) == 2)
				$r[$ee[0]] = $ee[1];
		}            
	
		return $r;
	}
	public static function array_value($arr, $index, $default = null) {
		$i = 0;
		foreach ($arr as $key => $val) {
			if ($i == $index) 
				return $val;
			$i++;
		}
		
		return $default;
	}
	public static function array_key($arr, $index, $default = false) {
		$i = 0;
		foreach ($arr as $key => $val) {
			if ($i == $index) 
				return $key;
			$i++;
		}
		
		return $default;
	}
	public static function filter($obj, $filter) {
		foreach ($filter as $var => $value) {
			$neg = false;
			if (strlen($value) && $value[0] == '!') {
				$neg = true;
				$value = substr($value, 1);
			}
			
			$v = utils::value($obj, $var, "");
			
			if ($neg && $v == $value || !$neg && $v != $value) return false;
		}
		
		return true;
	}
	public static function gen_array($arr, $sel_key = 'id', $sel_val = 'title', $r = null, $filter = null) {
		$i = 0;
		if (!$r) $r = array();
		
		if (!is_array($arr))
			return $r;
		
		foreach ($arr as $obj) {
			$key = utils::value($obj, $sel_key, $i);
			
			if ($filter && !utils::filter($obj, $filter)) continue;
			
			if (is_array($sel_val)) {
				$val = array();
				foreach ($sel_val as $name)
					$val[$name] = utils::value($obj, $name);
				
			} else 
				$val = utils::value($obj, $sel_val);
			
			$r[$key] = $val;
			$i++;
		}
		return $r;
	}
	public static function property($obj, $name, $def = null) {
		$pn = $name;
		if (is_object($obj) && isset($obj->$pn))
			return $obj->$pn; else
			return $def;
	}
	
	public static function abs_path($path = '', $project = false) {
		if ($project)
			$proj = '/projects/' . Kohana::config('config.project_name'); else
			$proj = '';
		if ($path != '' && $path[0] == '/')
			return DOC_ROOT . $proj . $path; else
			return DOC_ROOT . $proj . '/' . $path;
	}
	public static function php_array($name, $value, $endchar = ';', $asschar = '=', $indent = 0) {
		$ind = str_repeat(' ', $indent*4);          
		
		$c = '';
		if (is_array($value)) {          
			$c.= $ind . "$name $asschar array(\n";
			
			$i = 0;
			foreach ($value as $name => $val) {
				$c.= utils::php_array("'" . $name . "'", $val, ($i < count($value)-1) ? "," : "", '=>', $indent + 1);
				$i++;
			}
			$c.= $ind .")$endchar\n";
		} else {                                
			if (is_string($value))
				$value = "'$value'"; else
			if (is_bool($value))
				$value = ($value) ? "true" : "false"; else
				$value = "'$value'";
			$c.= $ind . "$name $asschar {$value}$endchar\n";
		}
		return $c;        
	}
	public static function php_config($config) {
		$c = "<?php defined('SYSPATH') OR die('No direct access allowed.');\n";
		
		foreach ($config as $name => $value) {
			$c.= utils::php_array("\$config['$name']", $value); 
		}
		
		return $c;
	}
	
	public static function view($name, $vars = array()) {
		if (is_object($name))
			$view = $name; else
			$view = new View($name);
		
		foreach ($vars as $vname => $val) 
			$view->$vname = $val;
		
		return $view;            
	}
	public static function sview($name, $vars = array()) {
		ob_start();
		
		echo utils::view($name, $vars);
		
		$res = ob_get_contents();
		ob_end_clean();
		
		return $res;    
	}
	public static function exists($path, $project = false) {
		$p = utils::abs_path($path, $project);
		return file_exists($p);
	}
	public static function price($p, $currency = null, $dec_point = ',') {
        if (is_null($currency))
            $currency = self::$currency;
        
		$p = floatval(str_replace(",", ".", $p));   
        $pre = "";
        $post = "";
        if ($currency == '&#36;')
            $pre = '$'; else
            $post = $currency;
            
		return $pre . number_format($p, 2, $dec_point, '.') . $post;
	}
	public static function array_delete($arr, $value) {
		$res = array();
		foreach ($arr as $v) 
			if ($v != $value)
				$res[] = $v;
		
		return $res;
	}
	
	public static function date($time, $sep = '.') {
		if (intval($time) <= 0) return '';
		
		return date("d{$sep}m{$sep}Y", intval($time));    
	}
	public static function time($time, $sep = ':', $seconds = true) {
		if (intval($time) <= 0) return '';
		
		if ($seconds)
			return date("H{$sep}i{$sep}s", intval($time)); else
			return date("H{$sep}i", intval($time));
	}
    public static function hms_timestamp($time, $sep = ":") {
        $time = trim($time);
        if ($time == "") return 0;
        
        $e = explode($sep, $time);
        $r = 0;
        if (count($e)) 
            $r = intval($e[0]) * 3600;    
        if (count($e) > 1)
            $r+= intval($e[1]) * 60;
        if (count($e) > 2)
            $r+= intval($e[2]);
        
        return $r;
    }
	public static function datetime($time, $sep = '-', $tsep = ':', $dsep = '.') {   
        if (!$time) return "";
           
		return utils::time($time, $tsep) . $sep . utils::date($time, $dsep);    
	}
    public static function date_time($time, $sep = " ") {
        if ($time == 0) return "";
        return date("d.m.Y", intval($time)) . $sep . date("H:i", intval($time));
    }
	public static function starts_with($haystack, $needle) {
		return (strpos($haystack, $needle) === 0);
	}
	public static function ends_with($haystack, $needle) {
		return (strpos(strrev($haystack), strrev($needle)) === 0);
	}
	public static function trim_str($s, $e, $c) {
		$ls = strlen($s);
		$le = strlen($e);
		
		if (strpos($c, $s) === 0) 
			$c = substr($c, $ls);
		
		$l = strlen($c);                    
		if (strpos($c, $e, $l-$le) == $l-$le) 
			$c = substr($c, 0, $l-$le);
		
		return $c;
	}
	
	public static function trim_slashes($str) {
		$sd = trim($str);
		
		$sl = strlen($sd);
		if ($sl && $sd[0] == '/')
			$sd = substr($sd, 1);
		
		$sl = strlen($sd);
		if ($sl && $sd[$sl-1] == '/')
			$sd = substr($sd, 0, $sl-1);
			
		return $sd;
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
	public static function implode($del, $arr, $index = 0, $count = 0) {
		$vals = array_values($arr);
		
		$c = count($arr);
		$i1 = $index;
		
		if ($count <= 0)
			$i2 = $c - 1 + $count; else
			$i2 = $count;
			
		if ($i1 < 0) $i1 = 0;
		if ($i1 >= $c-1) $i1 = $c-1;
		
		if ($i2 < 0) $i2 = 0;
		if ($i2 >= $c-1) $i2 = $c-1;
		
		//echo "I: $count $c $i1 $i2 <br />";
		
		$r = "";
		for ($i = $i1; $i <= $i2; $i++) {
			if ($arr[$i] === null || $arr[$i] === "") continue;
			
			if ($i) $r.= $del;
			$r.= $arr[$i];	
		}
		
		return $r;
	}
	public static function expval($del, $str, $ind, $trim = false, $default = '') {
		if ($str == '' || $str == $del)
			return array(); 
		
		$s = explode($del, $str);
		if (isset($s[$ind])) {
			$s = $s[$ind];
			if ($trim)
				$s = trim($s);
		} else
			$s = $default;       
		
		return $s;
	}
    
    public static function mktime($hour, $min, $sec, $day, $month, $year, $time = null) {
        if (is_null($time))
            $time = time();
        
        if (!is_numeric($time))
            return 0;
        
        if (!$time) return 0;
        
        $date = getdate($time);
        if (is_null($sec)) $sec = $date["seconds"];
        if (is_null($min)) $min = $date["minutes"];
        if (is_null($hour)) $hour = $date["hours"];
        if (is_null($day)) $day = $date["mday"];
        if (is_null($month)) $month = $date["mon"];
        if (is_null($year)) $year = $date["year"];
        
        return mktime($hour, $min, $sec, $month, $day, $year);
    }
    
    public static function contain($w,$h, $ratio) {
        $cw = $w;
        $ch = $cw * $ratio;
        if ($ch > $h) {
            $ch = $h;
            $cw = $ch / $ratio;    
        }        
        
        return array($cw,$ch);
    }
    public static function cover($w,$h, $ratio) {
        $cw = $w;
        $ch = $cw * $ratio;
        if ($ch < $h) {
            $ch = $h;
            $cw = $ch / $ratio;    
        }        
        
        return array($cw,$ch);
    }
    	
	public static function call_func($func, $def) {
		$args = array_splice(func_get_args(), 2);
		
		if (is_callable($func))
			return call_user_func_array($func, $args); else
			return $def;
	}
	public static function call($class, $func, $default) {
		$args = array_splice(func_get_args(), 3);
		$func = array($class, $func);
		
		if (is_callable($func))
			return call_user_func_array($func, $args); else
			return $default;
	}
    
    public static function mask_object($obj, $mask, $mapping = null) {
        $e = explode("[", $mask);
        
        $res = "";
        foreach ($e as $p) {
            if (empty($p)) continue;
            
            $e2 = explode("]", $p);
            if (count($e2) > 1) {
                $del = $e2[0];
                $prop = $e2[1];    
            } else {
                $del = "";
                $prop = $p;
            }
            
            $val = v($obj, $prop, null);
            if (empty($val)) continue;

            $mval = v($mapping, $prop . "." . $val, null);
            if (!empty($mval))
                $val = $mval;
            
            if ($res) $res.= $del;
            
            $res.= $val;
        }
        
        return $res;
    }
    public static function contact_info($contact, $mask = null, $mapping = null) {
        if (is_null($mask))
            $mask = "firstname[ ]lastname[, ]street[, ]zipcode[ ]city[, ]country"; else
        if ($mask == "NAME")
            $mask = "firstname[ ]lastname"; else
        if ($mask == "ADDRESS")
            $mask = "street[, ]zipcode[ ]city[, ]country"; 
            
        if (is_null($mapping))
            $mapping = array("country" => countries::$list);
            
        return utils::mask_object($contact, $mask, $mapping);        
    }

    public static function search_terms($str) {
        $str = preg_replace('/(\w+)\:"(\w+)/', '"${1}:${2}', $str);
        $str = self::str_getcsv($str, ' ');        
        
        return $str;
    }

    public static function shorten_string($str, $length) {
        $t = preg_replace('/\s+?(\S+)?$/', '', substr($str, 0, $length));
        if (strlen($t) < strlen($str))
            $t.= "...";   
            
        return $t;
    }   
    public static function find($mixed, $names, $default = null) {
        if (!$mixed || !$names) return $default;
        
        if (is_string($names))
            $names = explode(",", $names);
           
        foreach ($names as $name) {
            $val = trim(v($mixed, $name, null));
            if ($val) return $val;
        }    
        
        return $default;
    }
    
    public static function adaptive_image($path, $ops = array()) {
        $class = isset($ops["class"]) ? $ops["class"] : "";
        $ajax = isset($ops["ajax"]) ? $ops["ajax"] : false;
        $mode = isset($ops["mode"]) ? $ops["mode"] : "contain";
        $href = isset($ops["href"]) ? $ops["href"] : false;
        $title = isset($ops["title"]) ? $ops["title"] : "";
        $alt = isset($ops["alt"]) ? $ops["alt"] : "";
        $rel = isset($ops["rel"]) ? $ops["rel"] : "";
        
        if ($href === true)
            $href = $path;
        
        $res = ($href) ? "<a href='$href' " : "<div ";
        $res.= " class='adaptive_image $class' data-path='$path' data-mode='$mode' data-title='$title' data-alt='$alt' data-rel='$rel'>";
        if (!$ajax)
            $res.= "<img src='$path' title='$title' alt='$alt' rel='$rel'>";
        $res.= ($href) ? "</a>" : "</div>";
        
        return $res;        
    }
    
	
	//  Real time output init
	public static function rt_init() {
		while(ob_get_level()) ob_end_flush();        
	}
	//  Realtime echo
	public static function rt_echo() {
		$args = func_get_args();
		$c = implode('',$args);
		echo str_pad($c, 32768);
		flush();
	}
	public static function rt_init2() {
		$i = 0;
		while(ob_get_level()) {
			$i++;
			ob_end_flush();  
		}
	}
	//  Realtime echo
	public static function rt_echo2() {
		$args = func_get_args();
		$c = implode('',$args);
		echo str_pad($c, 32768);
		flush();
	}
	
	//  Background process init (realtime part)
	public static function bgp_header() {
		while(ob_get_level()) ob_end_clean();
		
		header("Connection: close");
		session_write_close();
		ignore_user_abort(true); 
		ob_start();
	}
	
	//  Background process start (background)
	public static function bgp_body() {
		$size = ob_get_length();
		header("Content-Encoding: none");
		header("Content-Length: $size");
		ob_end_flush(); 
		flush();        
	}
	
	public static function sperm($perm) {
		return substr(sprintf("%o", $perm), -3);       
	}
    public static function elapsed($time1, $time2) {   
        $d = ($time2 - $time1) / (60 * 60);
        
        return number_format($d, 2) . "h";
    }    
    public static function time_hms($time) {
        $h = intval($time / 3600);
        $m = intval(($time - $h * 3600) / 60);
        $s = intval($time - $h * 3600 - $m * 60);        
        
        return array($h, $m, $s);
    }
    public static function hour_min($time) {
        $hours = $time / (60 * 60);
        $minutes = ($hours - floor($hours)) * 60;        
        
        return array(intval($hours), intval($minutes));
    }
    public static function elapsed_hms($time1, $time2) {
        return self::time_hms($time2 - $time1);
    }
    public static function format_time($mask, $time) {
        $h = intval($time / 3600);
        $m = intval(($time - $h * 3600) / 60);
        $s = intval($time - $h * 3600 - $m * 60);        
        
        $res = strtr($mask, array(
            "HH" => str_pad(strval($h), 2, "0", STR_PAD_LEFT), 
            "MM" => str_pad(strval($m), 2, "0", STR_PAD_LEFT),  
            "SS" => str_pad(strval($s), 2, "0", STR_PAD_LEFT),  
            "H" => $h,
            "M" => $m,
            "S" => $s,
        ));
        
        return $res;
    }
	
	public static function get_css($name) {
		$p = path::gen("root.project", "media/css/$name.css");

		if (file_exists($p)) 
			$c = file_get_contents($p); else 
			$c = "";
			
		return "<style>\n$c\n</style>";          
	}
	
	public static function ftp_exists($ftp, $path) {
		$d = pathinfo($path, PATHINFO_DIRNAME);
		$l = ftp_nlist($ftp, $d);
		
		if ($l === false) return false;
		
		return in_array($path, $l);        
	}
	
	public static function url_rel($url) {
		$b = url::base();
		$b1 = str_replace('http://', '', $b);
		
		if (strpos($url, $b) === 0)
			return str_replace($b, '', $url); else
		if (strpos($url, $b1) === 0)
			return str_replace($b1, '', $url); else
			return false;            
	}
	
	public static function val2keys($arr) {
		$r = array();
		foreach ($arr as $a) {
			$r[$a] = $a;
		}
		
		return $r;
	}
	
	public static function list_dir($path, $dir = true, $ext = null) {
		if (!is_dir($path)) return array();

		if (is_string($ext))
			$ext = array($ext);
		
		$ignore = array('cgi-bin', '.', '..');
		$list = array();
		
		$dh = @opendir($path);
		while(false !== ($file = readdir($dh))) {
			if (in_array($file, $ignore)) continue;
			
			$p = $path . "/" . $file;
			if (is_array($ext) && !in_array(pathinfo($p, PATHINFO_EXTENSION), $ext)) continue;
			if (!$dir && is_dir($p)) continue;
			
			$list[] = $p;
		}
		
		closedir($dh);
		
		return $list;
	}   
	
	public static function read_post($obj, $names) {
		foreach ($names as $name) {
			if (isset($_POST[$name]))
				$obj->$name = $_POST[$name];
		}
	}
	
	public static function fill_array($start, $end, $keys = false) {
		$r = array();
		
		
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
	
	public static function load_debug() {
		$s = Session::instance();
		$dbg = $s->get(PROJECT_NAME . "_debug", false);
		
		if (isset($_GET['debug'])) {
			$dbg = $_GET['debug'] == "1";                
		}
		
		$s->set(PROJECT_NAME . "_debug", $dbg);
		define("DEBUG", $dbg);
	} 
	
	public static function str_array($array) {
		$res = array();
		
		foreach ($array as $key => $val)
			$res[$key] = strval($val);
		
		return $res;
	}       
	
	public static function encode_array($arr, $sep1 = ":", $sep2 = ",") {
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
	
	public static function decode_array($str) {
		if (is_array($str)) return $str;
		
		$res = array();
		$e = utils::explode(",", $str);
		foreach ($e as $p) {
			$ee = utils::explode(":", $p);        
			
			if (isset($ee[1]))
				$res[$ee[0]] = $ee[1];
		}
		
		return $res;
	}
	
	public static function style($s) {
		if (count($s)) 
			return "style='" . implode("; ", $s) . "'"; else
			return "";
	}
	
	public static function mysql_datetime($time) {
        if ($time == 0)
            return "0000-00-00 00:00:00"; else
		    return date("Y-m-d H:i:s", intval($time));
	}
	
	public static function sql_order($sort, $cfg) {
		if (!strlen($sort)) return false;
		
		if ($sort[0] == '-') {
			$sn = substr($sort, 1);
			$sd = -1;
		} else {
			$sn = $sort;
			$sd = 1;
		}
		
		if (!isset($cfg[$sn])) return false;
		
		$e = utils::explode(":", $cfg[$sn]);
		$fn = $e[0];
		$fm = $e[1];
		
		if ($fm == 'n') $sd*= -1;
		
		return "ORDER BY $fn " . (($sd > 0) ? 'ASC' : 'DESC'); 
	}
	
	public static function sql_build($sql, $order = null) {
		$r = "";
		$i = 0;
		foreach ($sql as $s) {
			$s = trim($s, "*@ ");
			if ($s != "") {
				if ($i) $r.= " AND ";
				$r.= $s;
				
				$i++;
			}    
		}
		
        
        if (strpos($r, "SELECT") !== 0) {
            if ($i)
                $r = "@" . $r; else
                $r = "*" . $r;
        }
        		
		if ($order)
			$r.= " " . $order;
		
		return $r;
	}
	
	public static function capture($callback) {
		ob_start();
		
		call_user_func($callback);
		
		$res = ob_get_contents();
		ob_end_clean();
		
		return $res;    
	}
	
	public static function decode_post($prefix, $null = "", $sep = ":") {
		$res = array();
		
		$prefix.= $sep;
		foreach ($_POST as $name => $value) {
			if (strpos($name, $prefix) !== 0) continue;
			
			if ($value == "")
				$value = $null;
				
			$key = substr($name, strlen($prefix));            
			$res[$key] = $value;
		}
		
		return $res;                 
	}
	
	public static function decode_get($prefix, $null = "", $sep = ":") {
		$res = array();
		
		$prefix.= $sep;
		foreach ($_GET as $name => $value) {
			if (strpos($name, $prefix) !== 0) continue;
			
			if ($value == "")
				$value = $null;
				
			$key = substr($name, strlen($prefix));            
			$res[$key] = $value;
		}
		
		return $res;                 
	}    
	public static function icon($name, $img = false) {
		$paths = array(
			APPPATH . "../media/img/icons/$name" => \Sloway\url::site("media/img/icons/$name"),
			MODPATH . "Core/media/img/icons/$name" => \Sloway\path::gen("site.modules.Core", "media/img/icons/$name")
		);
		
		if (strpos($name, "@")) {
			$e = explode("@", $name);
			$name = $e[0];
			$args = explode(",", $e[1]);    	
		} else {
			$args = func_get_args();
			unset($args[0]);
		}

		foreach ($args as $arg) 
			$paths[MODPATH . "$arg/media/img/icons/$name"] = \Sloway\path::gen("site.modules.$arg", "media/img/icons/$name");
			
		foreach ($paths as $abs => $rel) {
			if (file_exists($abs))
				return $rel;        
		} 
		
		return false;
	}    
	
	public static function clear() {
		return "<div style='clear: both'></div>";    
	}
	
	public static function format_bytes($a_bytes) {
		if ($a_bytes < 1024) {
			return $a_bytes .' B';
		} elseif ($a_bytes < 1048576) {
			return round($a_bytes / 1024, 2) .' KB';
		} elseif ($a_bytes < 1073741824) {
			return round($a_bytes / 1048576, 2) . ' MB';
		} elseif ($a_bytes < 1099511627776) {
			return round($a_bytes / 1073741824, 2) . ' GB';
		} else
			return round($a_bytes / 1099511627776, 2) .' TB';
	}     
	
	public static function realpath($path) {
		$pattern = '/\w+\/\.\.\//';
		while(preg_match($pattern,$path)){
			$path = preg_replace($pattern, '', $path);
		}
		
		return $path;
	}        
	
	public static function class_var($class, $name, $def = "") {
		$r = get_class_vars($class);
		return isset($r[$name]) ? $r[$name] : $def;
	}
	
	public static function save_file($path, $content, $mode = FTP_ASCII) {
		$apath = DOCROOT . $path;
		$fname = pathinfo($path, PATHINFO_BASENAME);
				
		if (!file_exists($apath)) return;
		
		$perm = fileperms($apath);
		$tpath = DOCROOT . "gen/" . $fname;
		file_put_contents($tpath, $content);
		
		$ftp = Kohana::config("config.ftp");
		$h = ftp_connect($ftp['host']);
		ftp_login($h, $ftp['user'], $ftp['pass']);
		$c = ftp_pwd($h);
		
		$spath = $ftp['path'] . "/gen/" . $fname;
		$dpath = $ftp['path'] . "/" . $path;
		
		ftp_put($h, $dpath, $tpath, $mode);
		ftp_chmod($h, $perm, $dpath);
		
		ftp_close($h);
		unlink($tpath);
	}
	
	public static function is_ajax() {
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');	
	}
	
	public static function cron_next($schedule, $time = 0) {
		$e = split(" ", $schedule);
		if (count($e) != 5) return false;
		
		$cp = new cron_parser($schedule);
		return $cp->next_runtime($time);
	}
	public static function new_object($vars = array()) {
		$r = new stdClass();
		foreach ($vars as $name => $value)
			$r->$name = $value;
		
		return $r;
	}
    public static function clear_object(&$obj) {
        foreach (get_object_vars($obj) as $key => $val)    
            $obj->$key = "";
    }
	
	public static function date2time($time = 0) {
		if (!$time)
			$time = time();
		$d = getdate($time);	
		return strtotime($d['mday'] . "." . $d['mon'] . "." . $d['year']);
	}
	
	public static function load_js() {
		$args = func_get_args();
		
		$res = '';
		foreach ($args as $path)
			$res.= "<script src='$path' type='text/javascript'></script>";
		
		return $res;
	}
	
	public static function load_css() {
		$args = func_get_args();
		
		$res = "<script>";
		foreach ($args as $path) 
			$res.= "\$(\"<link href='$path' type='text/css' rel='stylesheet'>\").appendTo('head');\n";
		$res.= "</script>";
		
		return $res;
	}
	
	public static function detect_ie() {
		if (!isset($_SERVER['HTTP_USER_AGENT'])) return false;
		
		preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);

		if (count($matches) > 1)
			return $matches[1]; else
			return false;
	}
	
	public static function timer_start() {
		self::$timer = microtime(true);
	}
	
	public static function timer_read() {
		$t = microtime(true);
		$r = $t - self::$timer;
		
		self::$timer = $t;
		
		return $r;	
	}
	
	public static function rect_overlay($sw,$sh, $dw,$dh) {
		
		$rw = $dw;
		$rh = $sh * $dw / $sw;
		
		if ($rh < $dh) {
			$rw = $sw * $dh / $sh;	
			$rh = $dh;	
		}
		
		return array($rw,$rh);
	}
	
	public static function sk_check() {
		$key = config::get("config.service_key");
		
		$sk = isset($_GET["sk"]) ? $_GET["sk"] : null;
		
		return ($key == $sk);
	}
	
	public static function sk_postfix($append = false) {
		$key = config::get("config.service_key");
		
		if ($key)
			return ($append) ? "&sk=" . $key : "?sk=" . $key; else
			return "";
	}
    
    public static function modified($time, $user) {
        if (!$time) return "";
        
        $res = utils::datetime($time);
        if ($user)
            $res.= " - " . $user;
        
        return $res;
    }   
    
    public static function rect_fit($sw,$sh, $dw,$dh) {
        $w_nw = $dw;
        $w_nh = $dw * $sh / $sw;
        
        $h_nw = $sw * $dh / $sh;
        $h_nh = $dh;
        
        if ($w_nh <= $dh) {                    
            $nw = $w_nw;
            $nh = $w_nh;
        } else {
            $nw = $h_nw;
            $nh = $h_nh;
        }
        
        return array($nw, $nh);
    }
    
    public static function prod_tag() {
        $home = Router::$controller == "Index" && Router::$method == "Index";
        return ($home) ? "" : "nofollow";
    }
	
	public static function str_compare($str1, $str2) {
		if ($str1 == "" || $str2 == "") return false;
		
		$l1 = strlen($str1);
		$l2 = strlen($str2);
		if ($l1 > $l2) $max = $l1; else $max = $l2;
		
		for ($i = 0; $i < $max; $i++) {
			if ($i >= $l1)
				return -1;
			if ($i >= $l2)
				return 1;
			
			if ($str1[$i] != $str2[$i]) return false;
		}
		
		return true;		
	}
	
	public static function decode_price($price) {
		if (is_array($price)) {
			foreach ($price as $key => $val) {
				if ($val == "" || is_null($val)) $val = 0;
				$price[$key] = number_format($val, 2, ",", "");
			}
			
			return $price;
		} 
		
		if ($price == "" || is_null($price)) $price = 0;
		return number_format($price, 2, ",", "");
	}
	public static function encode_price($price) {
		if (is_array($price)) {
			foreach ($price as $key => $val) {
				$val = trim($val);
				
				$val = str_replace(",", ".", $val);
				if (!is_numeric($val) || $val < 0) $val = 0;
				
				$price[$key] = $val;
			}
			return $price;
		}
		
		$price = trim($price);
		if (!is_numeric($price)) $price = 0;
		if ($price < 0) $price = 0;

		return str_replace(",", ".", $price);
	}
	public static function get_quantity_sentence_slo($quantity){
		
        if($quantity < 1){
            $quantity_term =  $quantity . " izdelkov v košarici";
        }

        if($quantity == 1){
            $quantity_term =  $quantity . " izdelek v košarici";
        }

        if($quantity == 2){
            $quantity_term =  $quantity . " izdelka v košarici";
        }

        if($quantity > 2){
            $quantity_term =  $quantity . " izdelki v košarici";
        }

		return $quantity_term;
	}

}
