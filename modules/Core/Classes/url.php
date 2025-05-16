<?php
namespace Sloway;

class url {
	public static function current()
	{
		return (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";		
	}

	public static function base($index = FALSE, $protocol = null)
	{
		if (lang::$routed) 
			return router::encodeUri("", lang::$lang, true);
		
		$base_url = base_url($index, $protocol);
		
		return rtrim($base_url, '/').'/';
	}

	public static function site($uri = '') {
		if (lang::$routed) 
			return router::encodeUri($uri, lang::$lang, true);
			
		return site_url($uri);
	}
	public static function test($uri = '') {
		if (lang::$routed) {
			echod($uri, lang::$lang);
			return router::encodeUri($uri, lang::$lang, true);
		}
			
		return site_url($uri);
	}
	public static function media($pth) {
		return site_url($pth);
	}
	public static function uploads($pth) {
		return path::gen("site.uploads", $pth);
	}

	public static function title($title, $separator = '-')
	{
		if (is_null($title)) $title = "";
		return url_title($title, $separator);
	}

	public static function redirect($uri = '', $method = '302')
	{
		redirect()->to($uri);
	}

    public static function from_obj($prefix, $objs, $id_sel = 'id', $title_sel = 'meta_title,title') {
        if (!is_array($objs))
            $objs = array($objs);
            
        if (is_null($id_sel)) $id_sel = 'id';
        if (is_null($title_sel)) $title_sel = 'meta_title,title';
        
        $p1 = array();
        $p2 = array();
        foreach ($objs as $obj) {
            if (is_null($obj)) continue;
            $p1[]= \Sloway\utils::find($obj, $id_sel);
            $p2[]= url::title(\Sloway\utils::find($obj, $title_sel));
        }
        
        return site_url($prefix . implode("-", $p1) . "/" . implode("/", $p2));
    }

    public static function parse_query($query, $del1 = "&", $del2 = "=") {
        if (!$query) return array();
        
        if (is_string($query)) {
            $query = explode($del1, $query);
            
            $res = array();
            foreach ($query as $query_part) {
                $e = explode($del2, $query_part);
                if (count($e) > 1) 
                    $res[trim($e[0])] = trim($e[1]); else
                    $res[trim($e[0])] = true;                
            }
        } else 
            $res = $query;
        
        return $res;
    }
    public static function build_query($query, $mask = null) {
        $res = array();
        
        if ($mask) {
            $masked = array();
            if (is_string($mask)) {
                $mask = self::parse_query($mask, "&", "=");
            }
                
            foreach ($mask as $key => $default) {
                if (isset($query[$key]) && $query[$key] != $default)
                    $masked[$key]= $query[$key];
            }    
            
            $query = $masked;
        } 
        
        foreach ($query as $key => $val) {
            if ($val === true)
                $res[] = $key; else
                $res[] = $key . "=" . $val;    
        }           
        
        return implode("&", $res);
    }
    public static function build_url($parsed) {
        $res = "";
        $auth = "";
        if (isset($parsed["scheme"])) $res.= $parsed["scheme"] . "://";
        if (isset($parsed["user"])) $auth.= $parsed["user"];
        if (isset($parsed["pass"])) $auth.= ":" . $parsed["pass"];
        
        if ($auth) $res.= $auth . "@";
        if (isset($parsed["host"])) $res.= $parsed["host"];
        if (isset($parsed["port"])) $res.= ":" . $parsed["port"];
        if (isset($parsed["path"])) $res.= $parsed["path"];
        if (isset($parsed["query"])) $res.= "?" . $parsed["query"];
        if (isset($parsed["fragment"])) $res.= "#" . $parsed["fragment"];            
        
        return trim($res, "?");
    }    


    public static function query($url, $query, $mask = null) {
        if (is_null($url))
            $url = url::current();
            
        $p = parse_url($url);
        
        $query = self::parse_query($query);  
        $curr_query = self::parse_query(isset($p["query"]) ? $p["query"] : "");
        
        foreach ($query as $key => $val) 
            $curr_query[$key] = $val;    
        
        $p["query"] = self::build_query($curr_query, $mask);
        
        return self::build_url($p);
    }

} 