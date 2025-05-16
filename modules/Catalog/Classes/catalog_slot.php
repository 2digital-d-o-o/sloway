<?php
	namespace Sloway;

	class catalog_slot extends dbModelObject {
		private $_items = null;
		private $_prices = null;
		public $def_item = null;
		
		private function get_items() {
			if ($this->_items === null) {
				$ids = parent::__get('items');
				if ($ids != '') {
					$vis = catalog::$validate_visible;
					catalog::$validate_visible = false;
					$i = dbModel::load("catalog_product", "@id IN ($ids) ORDER BY FIELD(id,$ids)", 0, 'id'); 
					catalog::$validate_visible = $vis;
                    
					if (count($i))
						$this->def_item = reset($i)->id;
						
					$this->_items = $i;
				} else
					$this->_items = array();
				
				foreach ($this->_items as $item)
					$item->slot = $this;          
			}    
			
			return $this->_items;
		}
		
		private function get_price($mode, $mask) {
			$p = "";
			
			if ($mode != "static") {   
				foreach ($this->get_items() as $item) {
					$ip = $this->item_price($item->id, "virtual");
					//$ip = $item->static_price;
					
					if ($ip == "") continue;
					
					if ($p == "" || ($mode == "min" && $ip < $p) || ($mode == "max" && $ip > $p))
						$p = $ip;
				}
			}
			
			if ($mask == "mask")
				$p = preg_replace(array('#\(mode\)#','#\(value\)#'), array($mode, $p), catalog::$mode_mask); else
			if ($mask == "mode")
				$p = array("mode" => $mode, "value" => $p);
				
			return $p;

		}
		
		private function get_stock($mode, $mask) {
			$p = "";
			
			if ($mode != "static") {            
				foreach ($this->get_items() as $item) {
					$ip = $item->static_stock;
					
					if ($ip == "") continue;
					
					if ($p == "" || ($mode == "min" && $ip < $p) || ($mode == "max" && $ip > $p))
						$p = $ip;
				}
			}
			
			if ($mask == "mask")
				$p = preg_replace(array('#\(mode\)#','#\(value\)#'), array($mode, $p), catalog::$mode_mask); else
			if ($mask == "mode")
				$p = array("mode" => $mode, "value" => $p);
				
			return $p;            
		}
		
		public function __get($name) {
			catalog::res_name($name, $mode, $mask);
			
			switch ($name) {
				case "items": 
					return $this->get_items();
				case "price":
					return $this->get_price($mode, $mask);
				case "stock":
					return $this->get_stock($mode, $mask); 
				default:
					return parent::__get($name);
			}
		}
		
		public function __load($param) {
			$this->_prices = json_decode($this->prices); 
			$this->_add_prices = json_decode($this->add_prices);
			
			if (!count($this->items) && catalog::$validate_slots)
				return false;
		}
		
		public function gen_array($mask = null, $filter_stock = false) {
			$r = array();
			foreach ($this->items as $item) {
				if ($filter_stock && $item->stock <= 0) continue;
				if ($mask) {
					$vars = array(
						"@price" => utils::price($this->item_price($item->id)),
						"@title" => $item->title,
						"@in_stock" => ($item->stock > 0),
						"@stock" => max($item->stock, 0),
					);
						
					$r[$item->id] = parser::build($mask, $vars);
				} else
					$r[$item->id] = $item->title;
			}		
			
			return $r;	
		}
		
		public function item_price($item_id, $mode = null) {
			if ($mode === null)
				$mode = catalog::$mode;
			
			$p = v($this->_prices, $item_id, "");
			if ($mode == 'actual' || ($p == "" && $mode == "virtual")) {
				$item = v($this->items, $item_id, null);    
				if ($item) 
					$p = $item->virtual_price;
			} 
			
			return $p;
		}
		
		public function item_add_price($item_id, $type, $mode = null) {
			if ($mode === null)
				$mode = catalog::$mode;
			
			$p = v($this->_add_prices, $item_id . "." . $type, "");
			if ($mode == 'actual' || ($p == "" && $mode == "virtual")) {
				$item = arrays::value($this->items, $item_id, null);    
				if ($item) 
					$p = $item->virtual_price;
			} 
			
			return $p;
		}
		
		public function item_stock($item_id) {
			$item = arrays::value($this->items, $item_id, null);
			if ($item)
				return $item->static_stock; else
				return false;    
		}
	}
