<?php

namespace Sloway;

class catalog_filter_category extends catalog_filter_part {
    public $category;
    public function __construct($mixed, $variables = null) {
        parent::__construct($variables);
        
        if (is_int($mixed))
            $this->category = dbModel::load("catalog_category", "@id = '$mixed'", 1); else
            $this->category = $mixed;
    }

    public function build($level) {
        $pid = $this->category->id;
        $all_values = $this->filter->build_cat_ids(0);  
        $enabled_values = $this->filter->build_cat_ids($level); 
        
        $items = array();
        $select = array();
        foreach ($this->category->subcat as $value) {
            if (!in_array($value->id, $all_values)) continue;
            
            $item = new stdClass();
            $item->id = $value->id;
            $item->title = $value->title;
            $item->value = $value->value;
            $item->image = false;
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
            $ids.= $this->category->id . "." . $value . "|";
        
        $ids = trim($ids, "|");
            
        return "categories REGEXP '[[:<:]]($ids)[[:>:]]'";
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


