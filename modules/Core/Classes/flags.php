<?php

namespace Sloway;

class flags {
	public static function get($src, $flags = null) {
		$src = explode(",", $src);
		if ($flags == null)
			return $src;
		
		if (is_string($flags))
			$flags = explode(",", $flags);
		$res = array();
		
		foreach ($flags as $flag) 
			$res[$flag] = in_array($flag, $src);
			
		if (count($flags) > 1)
			return $res; else
			return reset($res);
	}
	public static function set($src, $flags, $st = null) {
		if ($st !== null && !$st)
			return self::rem($src, $flags);
		
		if ($src == null)
			$src = array(); else
			$src = explode(",", $src);  
		
		if (is_string($flags))
			$flags = explode(",", $flags);
		
		$src = array_merge($src, $flags);
		return implode(",", array_unique($src));		
	}
	public static function rem($src, $flags) {
		if ($src == null)
			$src = array(); else
			$src = explode(",", $src);  
		
		if (is_string($flags))
			$flags = explode(",", $flags);
		
		$src = array_diff($src, $flags);
		return implode(",", $src);
	}
    public static function unique($src) {
        if (is_null($src))  
            $src = array();
        if (is_string($src))
            $src = explode(",", trim($src, ","));
            
        $src = array_unique($src);
            
        return implode(",", $src);
    }
}

