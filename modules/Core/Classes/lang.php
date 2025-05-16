<?php

namespace Sloway;

class lang {
	public static $profile_name;
	public static $routed = false;
	public static $lang;
	public static $langs;
	public static $def_lang;
	
	public static $content_lang;
	public static $content_langs;
	
	public static $cn;
	public static $ccn;
	
	public static $lang_disabled = array();
	public static $translator;
	public static $_translator;
	
	private static $profile;
	
	public static function load($profile_name, $set_lang = null) {
		$profile = config::get("lang." . $profile_name);
		
		self::$routed = isset($profile["routed"]) ? $profile["routed"] : false;
		self::$profile_name = $profile_name;
		self::$profile = $profile;
		self::$langs = $profile["langs"];
		self::$def_lang = reset(self::$langs);
		
		self::$cn = \Sloway\core::$project_name . "_" . $profile_name . "_lang";
		self::$ccn = \Sloway\core::$project_name . "_" . $profile_name . "_content_lang";
		
	//	Set language with ?sl=neki
		if (self::$routed && \Sloway\router::$lang) 
			self::$lang = \Sloway\router::$lang; else	
		if ($set_lang)
			self::$lang = $set_lang; else
        if (isset($_GET['sl'])) 
            self::$lang = $_GET['sl']; else
            self::$lang = get_cookie(self::$cn);
            
	//	Check if language exists
        if (!in_array(self::$lang, self::$langs)) 
			self::$lang = self::$def_lang;
    
		if (isset($profile['content'])) {
			self::$content_langs = $profile['content'];
			
            if (isset($_POST['admin_edit_lang'])) 
                self::$content_lang = $_POST['admin_edit_lang']; else
			    self::$content_lang = get_cookie(self::$ccn);
                
			if (!in_array(self::$content_lang, self::$content_langs)) 
				self::$content_lang = reset(self::$content_langs);
			
			mlClass::$lang = self::$content_lang;
			mlClass::$langs = self::$content_langs;
			mlClass::$def_lang = reset(self::$content_langs);
			mlClass::$multilang = count(self::$content_langs) > 1;
		} else {
			mlClass::$lang = self::$lang;
			mlClass::$langs = self::$langs;
			mlClass::$def_lang = self::$def_lang;
			mlClass::$multilang = (count(self::$langs) > 1);
		}
		
		set_cookie(self::$cn, self::$lang, 604800); 
		if (self::$content_lang) 
			set_cookie(self::$ccn, self::$content_lang, 604800); 

		mlClass::$mlf = config::get("lang.mlf", array());
		self::$translator = \Sloway\translator::instance(self::$lang, self::$profile_name);
		
		return self::$lang;    
	}
	public static function languages($content = false) {
		$r = array();
		
		if ($content) {
			foreach (self::$content_langs as $lang) 
				if (!in_array($lang, self::$lang_disabled))
					$r[]= $lang;
		} else
		foreach (self::$langs as $lang)
			if (!in_array($lang, self::$lang_disabled))
				$r[]= $lang;
		
		return $r;
	}
	public static function turl($url, $lang = null) {
		if (lang::$translator)
			return lang::$translator->url($url, $lang); else
			return $url;
	}
	public static function validate($lang, $profile_name = "site") {
		$profile = config::get("lang." . $profile_name);
		$langs = $profile["langs"];
		if (!$lang || !in_array($lang, $langs)) 
			$lang = reset($langs);
		
		return $lang;
	}	
	public static function set_translator($t) {
		self::$_translator = self::$translator;
		self::$translator = $t;
	}
	public static function reset_translator() {
		if (self::$_translator)
			self::$translator = self::$_translator;
	}
}      
