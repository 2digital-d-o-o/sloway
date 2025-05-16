<?php 

namespace Sloway;

class genClass {
	public static $binded;
    
    protected $__result;
	public $__data = array();

	protected function exec_binded($method, $args) {
		$this->__result = null;   
		array_unshift($args, $this);  
		foreach (genClass::$binded[$method] as $callable) 
			$this->__result = call_user_func_array($callable, $args);
		
		return $this->__result;
	}

	public static function bind($name, $callable, $tag = null) {
		if ($tag) 
			genClass::$binded[$name][$tag] = $callable;    
		else {
			if (!isset(genClass::$binded[$name]))
				genClass::$binded[$name] = array();
				
			genClass::$binded[$name][] = $callable;
		}
	}
	public function binded($method, $args = [], $default = null) {
		if ($this->is_binded($method))
			return $this->exec_binded($method, $args); else
			return $default;
	}
	
    public function add_to_array($name, $value) {
        if (!isset($this->__data[$name]) || !is_array($this->__data[$name]))
            $this->__data[$name] = array();
        
        $this->__data[$name][] = $value;    
    }
	public function is_binded($method) {
		return isset(genClass::$binded[$method]) && is_array(genClass::$binded[$method]); 
	}
	public function __call($method, $arguments) {
		if ($this->is_binded($method))
			return $this->exec_binded($method, $arguments);
	}
	public function __construct($obj = null) {
		if ($obj != null)
			$data = get_object_vars($obj);
	}
	public function __get($name){
		if (isset($this->__data[$name]))
			return $this->__data[$name]; else
			return null;
	}
	public function __set($name, $value){
		$this->__data[$name] = $value;
	}         
	public function duplicate(&$obj) {
		foreach ($this->__data as $name => $value) 
			$obj->$name = $value;
	}
	public function copy_to($obj) {
		foreach ($this->__data as $name => $value) 
			$obj->$name = $value;
	}
	public function copy_from($obj) {
		foreach ($obj->__data as $name => $value) 
			$this->$name = $value;
	}
}
