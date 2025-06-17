<?php

namespace Sloway;

class catalog_product_base extends dbModelObject {
	public static $loaded_tags = null;
	public static $loaded_discounts = null;
	protected $_tags = null;
	public function load_tags() {
		if (is_null(self::$loaded_tags)) {
			$tags = mlClass::load("catalog_tag", "*", 0, array("index" => "id"));
			foreach ($tags as $tag) {
				$tag->icon_list = images::load("catalog_tag", $tag->id, true, 1);
				$tag->icon_view = $tag->icon_list;
			}
			self::$loaded_tags = $tags;
		}

		if (is_null($this->_tags)) {
			$this->_tags = array();
			foreach (explode(",", $this->tags) as $id) {
				if (!isset(self::$loaded_tags[$id]))  continue;

				$tag = self::$loaded_tags[$id];
				if (!$tag->style) $tag->style = "blue";

				$this->_tags[]= $tag;
			}
		}
	}

	public function gen_discount_tag($res) {
		if ($did = $res->discount_id) {
			if (!isset(self::$loaded_discounts[$did]))
				self::$loaded_discounts[$did] = mlClass::load("catalog_discount", "@id = '$did'", 1);

			$res->discount_tag = self::$loaded_discounts[$did]->tag;
		} else
			$res->discount_tag = (floatval($res->discount)) ? "-" . number_format($res->discount * 100, 0) . "%" : "";
	}
    public function parent() {
        if ($this->type == 'item') {
            if (!$this->_parent) 
                $this->_parent = dbModel::load("catalog_product", "@id = '{$this->id_parent}'", 1);
                
            return $this->_parent;
        } else
            return $this;    
    }	
    public function price() {
        $res = new \stdClass();
        $res->current = 0;
        $res->regular = 0;
        $res->discount = 0;
        $res->discount_tag = "";
        $res->discount_id = 0;
        
		if ($this->filtered) {
			$res->current = $this->flt_price;
			$res->regular = $this->flt_price / (1 - $this->flt_discount);

			$res->discount = $this->flt_discount;
			$res->discount_id = $this->flt_discount_id;
			
			$this->gen_discount_tag($res);
			
			return $res;			
		}		
		
		$cats = $this->categories;
		$tags = $this->tags;
		$pid = $this->id;
		$price = floatval($this->price);
		$price_action = floatval($this->price_action);
		
		if ($this->type == "item") {
			$group = $this->parent();
			$cats = $group->categories;
			$tags = $group->tags;
			$pid = $group->id;
			
			if (!floatval($price)) $price = floatval($group->price);
			if (!floatval($price_action)) $price_action = floatval($group->price_action);
		}

	//	ACTION PRICE
		$res->current = $price;
		$res->regular = $price;
		$res->discount = 0;
		if ($price_action && $price_action < $price) {
			$res->current = $price_action;
			if (floatval($res->regular) && floatval($res->current))
				$res->discount = ($res->regular - $res->current) / $res->regular; 
		}
	
	//	DISCOUNTS
		$d = $res->discount;
		$sql = "SELECT * FROM catalog_discount WHERE (visible = 1 OR visible REGEXP '[[:<:]]" . lang::$lang . "[[:>:]]') AND \n";
		$sql.= "(\n";
		if ($cats)
			$sql.= "(categories REGEXP CONCAT('[[:<:]](', REPLACE(REPLACE('$cats','.','|'), ',','|') , ')[[:>:]]')) OR \n"; 
		if ($tags) 
			$sql.= "(tags REGEXP CONCAT('[[:<:]](', REPLACE('$tags', ',','|') , ')[[:>:]]')) OR \n"; 
		$sql.= "(products REGEXP '[[:<:]]($pid)[[:>:]]')\n";
		$sql.= ") AND \n";
		$sql.= "value / 100 > '$d' AND (time_from = 0 OR date_from < NOW()) AND (time_to = 0 OR date_to > NOW()) ORDER BY value DESC LIMIT 1";   
		
		$db = dbModel::$db;
		$q = $db->query($sql)->getResult(); 
		if (count($q)) {
			$res->discount = $q[0]->value / 100;
			$res->discount_id = $q[0]->id;
			$res->current = $res->regular * (1 - $res->discount);
		}
		
		$this->gen_discount_tag($res);
		
		return $res;			
    }
	public function get_tags($mode = "list") {
     	$this->load_tags();
		$sel = "icon_" . $mode;

		$result = array();
		foreach ($this->_tags as $tag) {
			if (!count($tag->$sel))
				$result[]= $tag;
		}

		return $result;
	}
	public function get_icons($mode = "list") {
		$this->load_tags();

		$sel = "icon_" . $mode;

		$result = array();
		foreach ($this->_tags as $tag) {
			if (count($tag->$sel)) {
				$icon = new \stdClass();
				$icon->title = $tag->title;
				$icon->image = thumbnail::from_image($tag->$sel, "product_{$mode}_icon")->result;
				$result[]= $icon;
			}
		}

		return $result;
	}
	
	public function find_category() {
		foreach (explode(",", $this->categories) as $part) {
			$vpart = [];
			foreach (explode(".", $part) as $cid) {
				if (isset(\Sloway\catalog::$valid_cats[$cid]))
					$vpart[]= $cid; else
					break;
			}
			
			if (count($vpart)) 
				return end($vpart);
		}		
		
		return false;
	}
}

