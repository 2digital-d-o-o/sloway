<?php

namespace Sloway;

class lang {
	public static $lang;
	public static $langs;
	public static $def_lang;
	public static $content_lang;
	public static $profile_name;
	
	public static $cn;
	public static $ccn;
	
	public static $session;
	public static $lang_disabled = array();
	public static $translator;
	
	private static $slang;
	private static $profile;
	private static $cprofile;     
	private static $content_sep;
	
	
	public static function load($profile_name) {
		$profile = config::get("lang." . $profile_name);
		
		self::$profile_name = $profile_name;
		self::$profile = $profile;
		self::$langs = $profile["langs"];
		self::$def_lang = reset(self::$langs);
		
		self::$cn = \Sloway\core::$project_name . "_" . $profile_name . "_lang";
		self::$ccn = \Sloway\core::$project_name . "_" . $profile_name . "_content_lang";
		
	//	Set language with ?sl=neki
        if (isset($_GET['sl'])) 
            self::$lang = $_GET['sl']; else   
		if (\Sloway\router::$lang)
			self::$lang = \Sloway\router::$lang; else
            self::$lang = get_cookie($profile['session']);		
            
	//	Check if language exists
        if (!in_array(self::$lang, $profile['langs'])) {
			self::$lang = self::$def_lang;
            delete_cookie($profile['session']);
        }
		self::$content_lang = self::$lang;
    
        
	//  ???
		$session = self::$session;
		$session->set($profile['session'], self::$lang);
		
		if (isset($profile['content'])) {
			$c = $profile['content'];
			$cp = config::get("lang." . $c['profile']);

			$cp['session'] = $c['session'];

			self::$cprofile = $cp;
			self::$content_sep = true;
			
            if (isset($_POST['admin_edit_lang'])) 
                mlClass::$lang = $_POST['admin_edit_lang']; else
			    mlClass::$lang = \Sloway\utils::cookie($cp['session'], $cp['default']);
                
			if (!in_array(mlClass::$lang, $cp['langs'])) {
				mlClass::$lang = $cp['default'];
                delete_cookie($cp['session']);   
            }
                
            if (isset($_POST['admin_edit_lang']))
                set_cookie($cp['session'], mlClass::$lang, 604800);   
				
			mlClass::$multilang = (count($cp['langs']) > 1);
			mlClass::$langs = $cp['langs'];
			mlClass::$def_lang = $cp['default'];
			
			self::$content_lang = mlClass::$lang;
		} else {
			self::$cprofile = $profile;
			self::$content_sep = false;
			
			mlClass::$lang = self::$lang;
			mlClass::$multilang = (count(self::$profile['langs']) > 1);
			mlClass::$langs = $profile['langs'];
			mlClass::$def_lang = self::$def_lang;
		}
		
		mlClass::$mlf = config::get("lang.mlf", array());
		self::$translator = \Sloway\translator::instance(self::$profile['langs'], self::$lang, self::$profile_name);
		
		return self::$lang;    
	}
	
	public static function languages($content = false) {
		$r = array();
		
		if ($content) {
			foreach (self::$cprofile['langs'] as $lang) 
				if (!in_array($lang, self::$lang_disabled))
					$r[]= $lang;
		} else
		foreach (self::$profile['langs'] as $lang)
			if (!in_array($lang, self::$lang_disabled))
				$r[]= $lang;
		
		return $r;
	}
	
	public static function turl($url, $lang = null) {
		if (lang::$translator)
			return lang::$translator->url($url, $lang); else
			return $url;
	}
	
	public static function gen_routes() {
		if (lang::$translator) 
			return lang::$translator->routes(); else
			return array();
	}
	
	public static function set($lang) {
		self::$slang = self::$lang;
		self::$lang = $lang;    
		
		mlClass::$lang = $lang;
	}
	
	public static function reset() {
		if (self::$slang != '') {
			self::$lang = self::$slang;    
			mlClass::$lang = self::$slang;	
		}
	}
}      

/*
function dt($name, $lang = '', $def = null, $prefix = '') {
	//return lang::t($name, $prefix, $lang, true);
	return lang::$translator->translate($name);
}
function t($name, $lang = null, $editable = false) {
	if (lang::$translator)
		return lang::$translator->get($name, $lang, $editable); 
}
function t_js($name, $lang = null, $editable = false) {
    $res = lang::$translator->get($name, $lang, $editable); 
    return trim(json_encode($res), '"');
}
function et($name, $lang = null) {
	if (lang::$translator)
		return lang::$translator->get($name, $lang, true); 
}
function et_js($name, $lang = null) {
    $res = lang::$translator->get($name, $lang, true); 
    return trim(json_encode($res), '"');
}
function et_rep($name, $replace, $lang = null) {
	if (lang::$translator) {
		$t = lang::$translator->get($name, $lang, false);
		return strtr($t, $replace);
	}
}

function _ta_callback($key, $val, $t) {
	$r[0] = $key;
	$r[1] = $val;
	
	if ($t['key']) $r[0] = t($t['prefix'] . $r[0], $t['lang']);
	if ($t['val']) $r[1] = t($t['prefix'] . $r[1], $t['lang']);
	
	return $r;
}

function ta($arr, $mask = 'k=>t:v', $prefix = '', $lang = '') {
	$mask = explode("=>", $mask);
	if (strpos($mask[0], "t:") === 0) {
		$trans['key'] = true;
		$mask[0] = substr($mask[0], 2);    		
	} else
		$trans['key'] = false;
	if (strpos($mask[1], "t:") === 0) {
		$trans['val'] = true;
		$mask[1] = substr($mask[1], 2);            
	} else
		$trans['val'] = false;
		
	$trans['lang'] = $lang;
	$trans['prefix'] = $prefix;
	
	return arrays::transform($arr, $mask, "_ta_callback", $trans);
}

*/