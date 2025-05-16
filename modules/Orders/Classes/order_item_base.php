<?php

namespace Sloway;

class order_item_base extends dbClass {
    public $items = array();
    public $data = null;
	public $_discount = array();
    public function __load() {
        $this->prices = json_decode($this->prices, true);
        if (!$this->prices)
            $this->prices = array();
    }
    public function __save() {
        $this->prices = json_encode($this->prices);    
    }
    public function setPrice($price, $tax_rate) {
        $this->price = fixed::gen($price / (1 + $tax_rate), 3);
        
        $this->tax_rate = fixed::gen($tax_rate, 4);
    }
	public function setDiscount($name, $amount, $mode = "max", $tag = null) {
		if (!$amount) {
			unset($this->_discount[$name]); 
			return;
		}
		
		if (!isset($this->_discount[$name]))
			$this->_discount[$name] = array();
		
		$this->_discount[$name]["value"] = $amount;
		$this->_discount[$name]["mode"] = $mode;
		$this->_discount[$name]["tag"] = $tag;
	}
    public function changeTaxRate($tr) {
        $p = (1 + $this->tax_rate) * $this->price;
        
        $this->tax_rate = $tr;
        $this->price = $this->price / (1 + $tr);    
    }
    public function finalize($order) {
    }
    public function commit($order, $id_order, $group = null) {
        $r = dbClass::create("order_item", $this->commit_id);  
        $r->delete();

        $r->copy_from($this);
        $r->id_order = $id_order;
        if ($group) {
            $r->id_group = $group->commit_id;
            $r->group_qty = $this->quantity;
            $r->quantity = $this->quantity * $group->quantity;
            $r->title = $group->title . " - " . $this->title;    
        }
		$r->discount = $order->calcDiscount($this);
        $r->save();
        
        $this->commit_id = $r->id;
        
        $order->on_commit_item($this, $group);
    }
    public function to_array() {
        $res = $this->__data;
        $res["type"] = "item";
        $res["items"] = array();
        
        $res["price"] = fixed::mul($this->price, 1 + $this->tax_rate);
        
        return $res;
    }
}

