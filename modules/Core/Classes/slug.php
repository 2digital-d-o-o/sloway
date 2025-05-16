<?php

namespace Sloway;

class slug {  
	public static $generator;
	public static function gen($string) {
		require_once MODPATH . "Core/Classes/SlugGenerator/SlugGeneratorInterface.php";
		require_once MODPATH . "Core/Classes/SlugGenerator/SlugOptions.php";
		require_once MODPATH . "Core/Classes/SlugGenerator/SlugGenerator.php";

		if (!self::$generator) {
			$ops = new \Ausi\SlugGenerator\SlugOptions();
			$ops->setValidChars('a-z0-9');
			$ops->setLocale('si');
			$ops->setDelimiter('-');
			self::$generator = new \Ausi\SlugGenerator\SlugGenerator($ops);
		}
		
		$res = self::$generator->generate($string);

	//	Compact numbers at the end
		if (preg_match("/(.*?)([\-\d]+)$/", $res, $vars)) {
			$res = $vars[1] . str_replace("-", "", $vars[2]);
		}

		return $res;
	}
}


