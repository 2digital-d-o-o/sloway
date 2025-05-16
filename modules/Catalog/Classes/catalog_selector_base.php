<?php

namespace Sloway;

class catalog_selector_base {
    protected function parse($state) {
        if (!$state) return null;
        
        $res = array();
        
        $e = explode(",", $state);
        foreach ($e as $ee) {
            $e2 = explode(".", $ee);
            if (isset($res[$e2[0]]))
                unset($res[$e2[0]]);

			$key = $e2[0];
			$val = $e2[1];
			
			if (isset($this->properties[$key]))
				$res[$key] = $val;
        }
        
        return $res;
    }
    protected function find($ids, $dbg) {
        foreach ($this->items as $item) {
            $found = true;
            foreach ($ids as $pid => $vid) {   
                /*if ($vid[0] == "@") {
                    $t = trim(json_encode(array(strval($pid) => "@" . $this->custom_values[$vid])), "{}");
                } else
                    $t = "\"$pid\":\"$vid\"";*/
                
                $t = $pid . "." . $vid;
                
                //echod("FIND: $pid => $vid '$t'");
                    
                if (strpos($item->properties, $t) === false) 
                    $found = false;
            }
            if ($found) {
                return $item;
            }
        }    
        return null;
    }
    protected function build_property($property, $primary, $value = null) {
        if ($primary) {
            if (isset($this->state[$property->id])) {
                $this->queue[$property->id] = $this->state[$property->id]; 
            } else {
                $this->queue[$property->id] = arrays::first($property->values, true); 
            }
            $this->debug[$property->title] = $property->values[$this->queue[$property->id]]->title;
            $this->item = $this->find($this->queue, $this->debug);
            
            foreach ($property->values as $value)
                $value->status = 1;
        } else {
           foreach ($property->values as $vid => $value) {
                $test = $this->queue;
                $test[$property->id] = $vid;
                $test_dbg = $this->debug;
                $test_dbg[$property->title] = $value->title;
                $item = $this->find($test, $test_dbg);
                if ($item) {
                    $value->status = 1;
                    
                    if (isset($this->state[$property->id]) && $this->state[$property->id] == $vid) {
                        $this->item = $item;               
                        $this->queue[$property->id] = $vid;                                         
                    }                         
                    
                    if (!isset($this->queue[$property->id])) {
                        if (!$this->item) $this->item = $item;
                        
                        $this->queue[$property->id] = $vid;
                        $this->debug[$property->title] = $value->title;
                    }
                } 
            }   
        }                 
    }
    protected function build_properties($state = null) {
        $ids = array();
        $dbg = array();
        $this->state = ($state) ? $state : array();
        $this->debug = array();
        
        if ($state) {
            end($state);
            $primary_pid = key($state);
        } else {
            reset($this->properties);
            $primary_pid = key($this->properties);
        }
        
        $this->queue = array();
        $this->build_property($this->properties[$primary_pid], true);
        foreach ($this->properties as $pid => $property) {
            if ($pid != $primary_pid)
                $this->build_property($property, false);
        }   
        
        $this->state = $this->queue;
        
        foreach ($this->state as $pid => $vid) 
            $this->properties[$pid]->value = $vid;
    }
    protected function build_tree($prop_tree) {
        $prop_ids = implode("','", array_keys($prop_tree));  
        
        $properties = array();
        foreach (mlClass::load("catalog_property", "@id IN ('$prop_ids') ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1 ASC") as $_property) {
            $property = new \stdClass();
            $property->id = $_property->id;
            $property->title = $_property->title;
            $property->values = array();
            $property->value = "";
            
            $reg = $this->registered[$property->id];
            $property->name = $reg["name"];
            if (is_array($reg["variables"]))
                foreach ($reg["variables"] as $name => $val)
                    $property->$name = $val;
            
            /*
            foreach ($prop_tree[$_property->id] as $vid) {
                $value = new stdClass();
                $value->status = 0;
                $value->image = null;
                
                $img = images::load("catalog_property", $vid);
                if (count($img))
                    $value->image = $img[0]->path;
                
                $_value = dbClass::load("catalog_property", "@id = '$vid'", 1);
                if (!$_value) continue;
                
                $value->id = $_value->id;
                $value->title = $_value->title;
                $value->value = $_value->value;
                
                $property->values[$value->id]= $value;    
            }
            $properties[$_property->id] = $property;*/
            
            $val_ids = implode("','", $prop_tree[$_property->id]);
            foreach (mlClass::load("catalog_property", "@id IN ('$val_ids') ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1, title ASC") as $_value) {
                $value = new \stdClass();
                $value->id = $_value->id;
                $value->title = $_value->title;
                $value->status = 0;
                $value->value = $_value->value;
                $value->flags = $_value->flags;
                
                /*
                $images = images::load("catalog_property", $_value->id);
                if (count($images))
                    $value->image = $images[0]->path; else
                    $value->image = null;*/
                
                $property->values[$_value->id]= $value;    
            }    
            $properties[$_property->id] = $property;  
        }  
        
        $this->properties = $properties;     
    }
    
    public function register_all($variables = null) {
        foreach ($this->db->query("SELECT * FROM catalog_property WHERE id_parent = 0 AND flags NOT LIKE '%no_selector%'")->getResult() as $q) 
            $this->registered[$q->id] = array("name" => url::title($q->title), "variables" => $variables);
    }
    public function register($name, $id, $variables = null) {
        $this->registered[$id] = array("name" => $name, "variables" => $variables);
    }               
    public function build($state = null) {
        $prop_tree = array();
        
        if (!count($this->items) || !count($this->registered)) {
            $this->item = $this->product;
            $this->properties = array();
            $this->state = "";
            return;
        }        
        foreach ($this->items as $item) {
		//	if (!$item->stock) continue;
            $item_props = arrays::decode($item->properties, ".", ",");
            foreach ($item_props as $pid => $value) {
                if (!isset($prop_tree[$pid])) $prop_tree[$pid] = array();
                
                $prop_tree[$pid][]= $value;                
            }
        }
        foreach ($prop_tree as $pid => $values) {
            if (isset($this->registered[$pid])) 
                $prop_tree[$pid] = array_unique($values); else
                unset($prop_tree[$pid]);
        }
        
        $this->build_tree($prop_tree);
		
        $state = $this->parse($state);

        $this->build_properties($state);
        
        if ($this->item)
            $this->item = dbModel::load("catalog_product", "@id = '{$this->item->id}'", 1);                            
    }
    public function __construct($db, $mixed) {
        $this->db = $db;
                                      
        if (is_scalar($mixed))
            $this->product = dbModel::load("catalog_product", "@id = '$mixed'", 1); else
            $this->product = $mixed;
                     
        $this->items = $this->db->query("SELECT * FROM catalog_product WHERE visible = 1 AND id_parent = ?", [$this->product->id])->getResult();
        $this->item = null;
        $this->registered = array();
    }
}  


