<?php
namespace Sloway;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;

class input {
	protected static $instance;
	public static $request;

	public static function instance()
	{
		if (Input::$instance === NULL)
		{
			return new Input;
		}

		return Input::$instance;
	}

	public function get($key, $default = null) {
		$res = input::$request->getGet($key);
		if (is_null($res)) $res = $default;

		return $res;
	}
	public function post($key, $default = null) {
		$res = input::$request->getPost($key);
		if (is_null($res)) $res = $default;

		return $res;
	}
	public function search($key, $default = null, $methods = null, $filter = null) {
		$val = null;		
		if ($methods == null || $methods == "post")
			$val = $this->post($key, null);
		
		if (!$val && ($methods == null || $methods == "get"))
			$val = $this->get($key, null); 
		
		if ($val == null)
			$val = $default;

		if ($filter)
			$val = preg_replace($filter, '', $val);

		return $val;
	}
	
	public function extract($keys, $method = null, $target = "var") {
		if ($target == "var")
			$target = array(); else
		if ($target == "obj")
			$target = new \stdClass();
		
		foreach ($keys as $key) {
			if (strpos($key, "@") !== false) {
				$e = explode("@", $key, 2);
				$key = $e[0];
				$def = $e[1];
			} else
				$def = null;
				
			if ($method == "post" || $method == null)
				$val = $this->post($key, null);
			
			if (is_null($val) && ($method == "get" || $method == null))
				$val = $this->get($key, null);
			
			if (is_null($val))
				$val = $def;
			
			if (is_object($target))
				$target->$key = $val; else
			if (is_array($target))
				$target[$key] = $val; 
		}
		
		return $target;
	}
}
