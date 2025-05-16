<?php

namespace Sloway;

class redirect_set {  
    public $_items = array();
    public $committed = true;
	public static $session;
    
    public static function create($session) {
		self::$session = $session;

        $result = $session->get("redirect_set", null);
        if (!$result) {
            $result = new redirect_set();
            $result->_items = array();
            
            $pth = APPPATH . "Config/redirect.php";
            if (!file_exists($pth)) return $result;
            
            include $pth;
        
            if (!isset($config) || !is_array($config)) return $result;
            
            $result->_items = $config;
        } else
            $result->committed = false;
            
        return $result;
    }
    public function items($page, $perpage = null) {
        $result = array();
        
        $start = ($perpage) ? $page * $perpage : 0;
        $end = ($perpage) ? $start + $perpage : count($this->_items);    
        
        $keys = array_keys($this->_items);
        $vals = array_values($this->_items);
        
        for ($i = $start; $i < $end; $i++) 
            if (isset($keys[$i]) && isset($vals[$i]))
                $result[$keys[$i]] = $vals[$i];
        
        return $result;
    }
    public function total() {
        return count($this->_items);    
    }
    public function get($src) {
        return isset($this->_items[$src]) ? $this->_items[$src] : "";    
    }
    public function delete($src) {
        unset($this->_items[$src]);

        self::$session->set("redirect_set", $this);
        $this->committed = false;
    }
    public function update($src, $dst) {
        $this->_items[$src]= $dst;
        
        self::$session->set("redirect_set", $this);
        $this->committed = false;
    }
    public function commit() {
        $this->committed = true;
        
        $c = "<?php\n";
        foreach ($this->_items as $key => $val) {
            $key = addslashes($key);
            $val = addslashes($val);
            $c.= "\$config[\"$key\"] = \"$val\";\n";
        } 
        
        file_put_contents(APPPATH . "Config/redirect.php", $c); 
        
        self::$session->remove("redirect_set");
    }
}


