<?php

namespace Sloway;

class catalog_filter_part {
    public $db;
    public $filter;
    public $name = "";
    public $items = array();
    public $param = array();
    public $enabled = false;
    
    public function __construct($mixed, $variables) {
        foreach ($variables as $name => $value)
            $this->$name = $value;
    }
    public function sql($values) {}
    public function build($level) {}
    public function caption() {
        return "";
    }        
}
