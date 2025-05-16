<?php

namespace Sloway;

class fixed {  
	public static $decimals = 2;
	public static function real($a) {
		$a = str_replace(",", ".", $a);
		if (!is_numeric($a))
			$a = 0;
			
		return floatval($a);
	}
	public static function gen($a, $dec = null) {
        if (is_null($dec)) $dec = self::$decimals;
            
		return number_format(self::real($a), $dec, ".", "");
	}   
	public static function add($a, $b, $dec = null) {
        if (is_null($dec)) $dec = self::$decimals;
		
        $res = number_format(self::real($a) + self::real($b), $dec, ".", "");
		
		return $res;
	}
	public static function sub($a, $b, $dec = null) {
        if (is_null($dec)) $dec = self::$decimals;

        $res = number_format(self::real($a) - self::real($b), $dec, ".", "");

		return $res;
	}
	public static function mul($a, $b, $dec = null) {
        if (is_null($dec)) $dec = self::$decimals;
		
        $res = number_format(self::real($a) * self::real($b), $dec, ".", "");

		return $res;
	}
	public static function div($a, $b, $dec = null) {
        if (is_null($dec)) $dec = self::$decimals;
        
        $res = number_format(self::real($a) / self::real($b), $dec, ".", "");

		return $res;
	}
	public static function fmt($a) {
		return number_format(self::real($a), 2, ",", "");
	}
}


