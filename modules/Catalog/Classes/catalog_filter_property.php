<?php

namespace Sloway;

class catalog_filter_property extends catalog_filter_part {
    public $property;
    public function __construct($mixed, $variables = null) {
        parent::__construct($mixed, $variables);
        
        if (is_int($mixed))
            $this->property = dbModel::load("catalog_property", "@id = '$mixed'", 1); else
            $this->property = $mixed;
    }
    public function build_tree($level) {
        if (!isset($this->filter->property_tree))
            $this->filter->property_tree = array();
        
        if (!isset($this->filter->property_tree[$level])) {
            $tree = array();                               
            foreach ($this->filter->db->query("SELECT DISTINCT properties FROM catalog_filtered WHERE flt_level = ?", [$level])->getResult() as $q) {
                foreach (explode(",", $q->properties) as $p) {
                    $p = explode(".", $p);
                    if (count($p) != 2) continue;
                    
                    if (!isset($tree[$p[0]])) $tree[$p[0]] = array();
                    $tree[$p[0]][]= $p[1];        
                }
            }                                    
            $this->filter->property_tree[$level] = array();
            foreach ($tree as $key => $values)
                $this->filter->property_tree[$level][$key] = array_unique($values);
                
        }
        return $this->filter->property_tree[$level];
    }
    public function build($level) {
        $pid = $this->property->id;
        $tree_g = $this->build_tree(0);  
        $tree_c = $this->build_tree($level); 
        
        $all_values = v($tree_g, $pid, array());   
        $enabled_values = v($tree_c, $pid, array());   
        $items = array();
        $select = array();
        foreach ($this->property->values as $value) {  
            if (!in_array($value->id, $all_values)) continue;
            
            $item = new \stdClass();
            $item->id = $value->id;
            $item->title = $value->title;
            $item->value = $value->value;
            $item->image = false; //count($value->images) ? $value->images[0]->path : false;
            $item->enabled = in_array($value->id, $enabled_values);

            $items[$item->id] = $item;    
        }
        
        $this->items = $items;        
        $this->enabled = count($items) > 0;
    }
    public function sql($values) {
        $values = explode(",", $values);
        $ids = "|";
        foreach ($values as $value)
            $ids.= $this->property->id . "[.]" . $value . "|";
        
        $ids = trim($ids, "|");           
            
        return "properties REGEXP '[[:<:]]($ids)[[:>:]]'";
    }   
    public function caption() {
        $val = $this->filter->get_value($this->name);
        if ($val) {
            $e = explode(",", $val);
            $v = "";
            foreach ($e as $id)
                $v.= ", " . v($this->items, $id . ".title");
            
            return $this->title . ": " . trim($v, ", ");
        }
        
        return "";
    }
}


