<?php

namespace Sloway;

class catalog_filter_price extends catalog_filter_part {
    public $price_min;
    public $price_max;
    public $enabled = true;
    public function __construct($variables = null) {
        parent::__construct($variables);
    }
    public function build($level) {
        $q = $this->db->query("SELECT MIN(price) as min, MAX(price) as max FROM catalog_filter_base");
        if (count($q)) {
            $this->price_min = $q[0]->min;
            $this->price_max = $q[0]->max;
            
            if (!$this->price_min) $this->price_min = 0;
            if (!$this->price_max) $this->price_max = 0;
        } else 
            $this->enabled = false;
    }
    public function sql($values) {
        $range = explode(",", $values);
        
        return "price >= $range[0] AND price <= $range[1]";
    }    
}


