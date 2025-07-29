<?php

namespace Sloway;

class catalog_filter_base {
    public $parts = array();
    public $values = array();
    public $base;
    public $count;
    public $state = "";
	public $source;
    
    public function __construct($db, $base_sql = null, $source = null) {
        $this->db = $db;
        $this->base_sql = $base_sql;

		$this->source = config::get("catalog.filter_source");
		if ($source) $this->source = $source;
    }
    public function add($name, $part, $value = null) {
        $this->parts[$name] = $part;
        if (!is_null($value))
            $this->values[$name] = $value;    
        
        $part->name = $name;
        $part->filter = $this;
                                                                  
        return $part;
    }
    public function set_value($name, $value) {
        $this->values[$name] = $value;    
    }
    public function get_value($name) {
        return v($this->state, $name, null);    
    }
    public function active() {
        return count($this->state) > 0;
    }
    
    public function build_tables() {
		
		
		$columns = trim(config::get("catalog.base_columns") . "," . config::get("catalog.columns"), ",");
        $columns = explode(",", $columns);
		
        $result = array();
        $debug = config::get("catalog.filter_debug");
		$source = $this->source;
        $struct = array();
        foreach ($this->db->query("SHOW COLUMNS FROM `$source`")->getResult() as $qq)
            $struct[$qq->Field] = $qq->Type;
            
        $values = "";
        foreach ($columns as $name) {
            if (!isset($struct[$name])) continue;
            $type = $struct[$name];
            $result[]= $name;
            
            $values.= "`$name` $type NOT NULL,";
        }
		
		$values.= "`flt_price` DECIMAL(10,2) NOT NULL,";
        $values.= "`flt_discount` DECIMAL(10,4) NOT NULL,";
        $values.= "`flt_discount_id` INT NOT NULL,";
        $values.= "`flt_level` INT NOT NULL,";
        $values.= "`flt_name` VARCHAR(50) NOT NULL,";
        $values.= "`filtered` TINYINT NOT NULL";
        
        $temp = "TEMPORARY";
        if ($debug) {
            $temp = "";
            $q = $this->db->query("SHOW TABLES LIKE 'catalog_filtered'")->getResult();
            if (count($q))
                $this->db->query("DROP TABLE `catalog_filtered`");
        }
            
        $sql2 = "CREATE $temp TABLE `catalog_filtered` (\n $values) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
        $this->db->query($sql2);
        
        return implode(",", $result);
    }    
    public function build_state() {
        $input = Input::instance();
        
        $state = array();
        $st = explode(";", $input->search("flt"));
            
        foreach ($st as $stp) {
            $stp_e = explode(":", $stp);
            if (count($stp_e) == 2)
                $state[$stp_e[0]] = $stp_e[1];
        }

        if ($set = $input->search("flt_set")) {
            $e = explode(":", $set);
            if (count($e) == 2) {
                foreach ($state as $key => $val)
                    if ($key == $e[0])
                        unset($state[$e[0]]);
                
                if ($e[1])
                    $state[$e[0]] = $e[1];
            }
        }
        
        $this->state = $state;
    }
    public function build() {
        $this->build_state();
        $db = $this->db;      
		
		$source = $this->source;		
		$columns = config::get("catalog.product_base_columns") . "," . config::get("catalog.product_columns");		
		$add_cols = array(
			"flt_price" => "DECIMAL(10,2) NOT NULL",
			"flt_discount" => "DECIMAL(10,4) NOT NULL",
			"flt_discount_id" => "INT NOT NULL",
			"flt_level" => "INT NOT NULL",
			"flt_name" => "VARCHAR(50) NOT NULL",
			"filtered" => "TINYINT NOT NULL"
		);
		$select = dbUtils::clone_table($db, $this->source, "catalog_filtered", true, $columns, $add_cols);
        
        if ($this->base_sql)
            $base_sql = " AND " . $this->base_sql; else
            $base_sql = "";                    
        
		$db->query("INSERT INTO `catalog_filtered` SELECT $select,price,0,0,0,0,1 FROM `$source` WHERE type = 'group' $base_sql GROUP BY id"); 
        $sql = 'UPDATE catalog_filtered AS p INNER JOIN (SELECT id_parent, GROUP_CONCAT(properties) AS properties FROM `' . $source . '` WHERE id_parent != 0 AND stock > 0 GROUP BY id_parent ) AS i ON p.id = i.id_parent SET p.properties = CONCAT(p.properties,",",i.properties)';
        $db->query($sql);      

	//	ACTION PRICES
		$sql = "UPDATE catalog_filtered SET flt_price = price_action, flt_discount = 1 - price_action/price WHERE price_action != 0 AND price_action < price";
		$db->query($sql);     
		
	//  DISCOUNTS
		$q = $db->query("SELECT * FROM catalog_discount WHERE (visible = 1 OR visible REGEXP '[[:<:]]" . lang::$lang . "[[:>:]]') AND (time_from = 0 OR date_from < NOW()) AND (time_to = 0 OR date_to > NOW())")->getResult();
		foreach ($q as $qq) {
			$d = $qq->value / 100;
			$where = [];
			if ($qq->categories)
				$where[]= "(categories REGEXP CONCAT('[[:<:]](', REPLACE(REPLACE('{$qq->categories}','.','|'), ',','|') , ')[[:>:]]'))";
			if ($qq->tags)
				$where[]= "(tags REGEXP CONCAT('[[:<:]](', REPLACE('{$qq->tags}',',','|'), ')[[:>:]]'))";
			if ($qq->products)
				$where[]= "(id IN ($qq->products))";

			if (count($where)) {
				$sql = "UPDATE catalog_filtered SET flt_price = flt_price * (1 - $d), flt_discount_id = '$qq->id', flt_discount = '$d' WHERE flt_discount < '$d' AND " . implode(" OR ", $where);

				$db->query($sql);
			}
		}	
        
        $level = 0;
        foreach ($this->state as $name => $val) {
            if (!isset($this->parts[$name])) continue;
            
            $part = $this->parts[$name];
            $part_sql = $part->sql($val);
            
            $l = $level+1;
            $sql = "INSERT INTO `catalog_filtered` SELECT $columns,'$l','$name','1' FROM `catalog_filtered` WHERE flt_level = '$level' AND " . $part_sql;
			// echod($sql);
            $this->db->query($sql);
            
            $part->build($level);
            
            $level++;
			// NEKI
        }
        
        $this->level = $level;
        
        foreach ($this->parts as $name => $part) {
            if (isset($this->state[$name])) continue;

            $part->build($this->level);
        }
            
        $q = $db->query("SELECT COUNT(id) as cnt FROM `catalog_filtered` WHERE flt_level = ?", [$this->level])->getResult();
        $this->count = $q[0]->cnt;
    }
    public function state_str() {
        $res = "";
        foreach ($this->state as $key => $val) {
            $res.= ";" . $key . ":" . $val;
        }
        
        return trim($res, ";");
    }
    
    public function build_cat_ids($level) {
        if (!isset($this->category_ids))
            $this->category_ids = array();
        
        if (!isset($this->category_ids[$level])) {
            $ids = array();                               
            foreach ($this->db->query("SELECT DISTINCT categories FROM catalog_filtered WHERE flt_level = ?", $level) as $q) {
                foreach (explode(",", $q->categories) as $p) {
                    $p = explode(".", $p);
                    foreach ($p as $pp)
                        $ids[]= $pp;
                }
            }                                    
            $this->category_ids[$level] = $ids;
        }
        return $this->category_ids[$level];
    }  
    public function build_tag_ids($level) {
        if (!isset($this->tag_ids))
            $this->tag_ids = array();
        
        if (!isset($this->tag_ids[$level])) {
            $ids = array();                               
            foreach ($this->db->query("SELECT DISTINCT tags FROM catalog_filtered WHERE flt_level = ?", $level) as $q) {
                foreach (explode(",", $q->tags) as $p) {
                    $p = explode(".", $p);
                    foreach ($p as $pp)
                        $ids[]= $pp;
                }
            }                                    
            $this->tag_ids[$level] = $ids;
        }
        return $this->tag_ids[$level];
    }      
    
    public function parts() {
        $res = array();
        foreach ($this->parts as $name => $part) 
            if ($part->enabled)
                $res[$name] = $part;
        
        return $res;    
    }
    public function active_parts() {
        $res = array();
        foreach ($this->state as $name => $val) {
            if (!isset($this->parts[$name])) continue;
            
            $res[$name]= $this->parts[$name];
        }
        
        return $res;
    }
}

