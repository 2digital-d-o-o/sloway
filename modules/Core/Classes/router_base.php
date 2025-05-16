<?php

namespace Sloway;

class router_base {  
	public static $lang = "_def";
	public static function handleSlug($url) {
		return null;
	}
	
	public static function encodeUri($uri, $lang, $domain = false) {
		if ($domain) 
			return site_url($uri);
		
		return $uri;
	}
	public static function decodeUri($uri) {
		return  ["lang" => null, "uri" => $uri];
	}
	
}


