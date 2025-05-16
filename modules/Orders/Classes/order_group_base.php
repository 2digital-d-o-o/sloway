<?php

namespace Sloway;

class order_group_base extends dbClass {
    public $data = null;
    public $items = array();
    
    public function addItem($item) {
        $item->grouped = true;
        
        $i = $this->items;
        $i[] = $item;
        $this->items = $i;        
    }
    public function changeTaxRate($tr) { 
        foreach ($this->items as $i)
            $i->changeTaxRate($tr);    
    }
    public function finalize($order) {
        foreach ($this->items as $item) 
            $item->finalize($order);
        
        $this->ac_tags = $order->item_ac_tags($this);    
        if ($this->categories)
            $order->categories.= "," . $this->categories;
        if ($this->flags)
            $order->flags.= "," . $this->flags;      
        if ($this->ac_tags)
            $order->ac_tags.= ",". $this->ac_tags;    
    }
    public function commit($order, $id_order) {
        $r = dbClass::create("order_group", $this->commit_id);  
        $r->delete();
        
        $r->copy_from($this);
        $r->id_order = $id_order;
        $r->save();
        
        $this->commit_id = $r->id;
        
        foreach ($this->items as $item) 
            $item->commit($order, $id_order, $this);

        $order->on_commit_group($this);
    }
    public function to_array() {
        $res = $this->__data;
        $res["type"] = "group";
        $res["items"] = array();
        foreach ($this->items as $item)
            $res["items"][] = $item->to_array();
        
        return $res;
    }
}