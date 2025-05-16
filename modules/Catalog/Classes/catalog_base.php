<?php

namespace Sloway;

class catalog_base {
	public static $db;
	public static $class = '\Sloway\catalog';
	public static $instance = null; 
	public static $mode = "virtual";     
	public static $mode_mask = "<span class='(mode)'>(value)</span>";
	public static $validate_types = false;
	public static $validate_slots = false;
    public static $validate_users = true;
	public static $validate_products = false;
	public static $validate_visible = false;

	public static $categories;
	public static $valid_cats = array();

	public static function & instance() {
		if (!catalog::$instance) 
			catalog::$instance = new catalog::$class();

		return catalog::$instance;
	}
	public static function res_name(&$name, &$mode, &$mask) {
		$mode = null;
		$mask = false;
		if (strpos($name, "static_") === 0) {
			$name = str_replace("static_", "", $name);
			$mode = "static";    
		} else
		if (strpos($name, "virtual_") === 0) {
			$name = str_replace("virtual_", "", $name);
			$mode = "virtual";    
		} else 
		if (strpos($name, "min_") === 0) {
			$name = str_replace("min_", "", $name);    
			$mode = "min";    
		} else
		if (strpos($name, "max_") === 0) {
			$name = str_replace("max_", "", $name);    
			$mode = "max";    
		}
		if (strpos($name, "_mode") === strlen($name) - 5) {
			$name = str_replace("_mode", "", $name);
			$mask = "mode";                    
		} else
		if (strpos($name, "_mask") === strlen($name) - 5) {
			$name = str_replace("_mask", "", $name);
			$mask = "mask";                    
		}
	} 
	
	public function __construct() {       
		$cfg = \Sloway\config::get("catalog", null);
		$this->stock_low = v($cfg, "stock_low", 10);
		$this->tax_rates = v($cfg, "tax_rates", array("0.220" => "22%", "0.095" => "9.5%", "0" => "0%"));
		$this->types = v($cfg, "types", array("product", "group", "packet", "media"));
		$this->add_prices = v($cfg, "add_prices", array('commission'));
		$this->image_templates = v($cfg, "image_templates", array());
	}

    public static function finalize_stock($pid) {
		$res = 0;
        $q = core::$db->query("SELECT amount FROM catalog_stock_op WHERE id_product = ? ORDER BY time", [$pid])->getResult();
        foreach ($q as $qq) 
            $res+= intval($qq->amount);
        
        core::$db->query("UPDATE catalog_product SET stock = ? WHERE id = ?", [$res, $pid]);
    }             
    public static function update_stock($order, $pid, $amount, $status = '', $reference = '') { 
        if (config::get("catalog.stock.manager")) {
            $entry = dbClass::create("catalog_stock_op");
            $entry->time = time();
            $entry->id_product = $pid;
            $entry->amount = $amount;
            $entry->status = $status;
            $entry->reference = $reference;
            $entry->save();
            
            catalog::finalize_stock($pid);        
            return $entry->id;
        }
        
        $q = core::$db->query("SELECT stock FROM catalog_product WHERE id = ?", [$pid])->getResult();
        if (!count($q)) return 0;
        
        $value = $q[0]->stock + $amount;
        
        core::$db->query("UPDATE catalog_product SET stock = ? WHERE id = ?", [$value, $pid]);
        
        return 0;
    }
    public static function check_stock($order, $pid) {   
        $q = core::$db->query("SELECT stock FROM catalog_product WHERE id = ?", [$pid])->getResult();
		return count($q) ? $q[0]->stock : 0;
    }    

    public static function update_discounts() {
        $db = self::$db;
        
        $db->query("UPDATE catalog_product SET discount = 0, discount_tag = ''");

        $q = $db->query("SELECT * FROM catalog_discount WHERE active = 1 AND (time_from = 0 OR date_from < NOW()) AND (time_to = 0 OR date_to > NOW())")->getResult();
        foreach ($q as $qq) {
            $d = $qq->value / 100;
            $sql_pids = ($qq->products) ? "OR (id IN ($qq->products))" : "";
            $sql = "UPDATE catalog_product 
            SET 
                discount = CASE WHEN discount < '$d' THEN '$d' ELSE discount END,
                discount_tag = CASE WHEN discount < '$d' THEN '$qq->tag' ELSE discount_tag END
            WHERE (categories REGEXP CONCAT('[[:<:]](', REPLACE(REPLACE('{$qq->categories}','.','|'), ',','|') , ')[[:>:]]') $sql_pids)";
            
            $db->query($sql);
        }
    }
    public static function stock_add($stock, $amount, $mul = 1) {
        if (!is_array($stock)) $stock = explode("/", $stock);
        if (!is_array($amount)) $amount = explode("/", $amount);

        $res = $stock;
        $l = 0;
        foreach ($amount as $i => $ee) {
            if (!isset($res[$i]))
                $res[$i] = $l; else
                $l = $res[$i];
            
            if (!is_numeric($ee))
                $ee = 0; else
                $ee = intval($ee) * $mul;
            
            $res[$i]+= $ee;    
        }        
        return $res;
    }
    public static function stock_mul($stock, $mul) {  
        if (!is_array($stock)) $stock = explode("/", $stock);     
        
        for ($i = 0; $i < count($stock); $i++)
            $stock[$i] = intval($stock[$i]) * $mul;
        
        return $stock;
    }
    public static function stock_min($stock, $check = null, $mask = false) {
        if (!is_array($stock)) $stock = explode("/", $stock); 
        
        if (!$check)
            return min($stock);
        
        if (!is_array($check)) $check = explode("/", $check);
        
        $min = array();
        for ($i = 0; $i < count($check); $i++) {
            $s = intval(isset($stock[$i]) ? $stock[$i] : $stock[count($stock)-1]);
            $c = intval($check[$i]);
            
            if ($c) {
                if (!$mask)
                    $min[] = min($c, $s); else
                    $min[] = $s;
            }
        }
        
        return min($min);
    }

    public static function reservation_create($order, $pid, $amount, $status = "in_cart") {
        return catalog::update_stock($order, $pid, -$amount, $status);            
    }
    public static function reservation_update($order, $rid, $amount, $status = null, $reference = null, $force = false) {
        $db = core::$db;
        $q = $db->query("SELECT id_product, amount, status, reference FROM catalog_stock_op WHERE id = ?", [$rid])->getResult();
        if (!count($q)) return $amount;
        
        if (is_null($status))
            $status = $q[0]->status;
        if (is_null($reference))
            $reference = $q[0]->reference;
        $pid = $q[0]->id_product;
        
        $mask = "1";
        if (is_null($amount)) {         
            $amount = catalog::stock_mul($q[0]->amount, -1);
            
            $qty = catalog::stock_min($amount, $mask, true);
            $amount = catalog::stock_mul($amount, -1);
        } else
        if ($force) {
            $qty = $amount;
            $amount = catalog::stock_mul($mask, -$qty);
        } else {
            $q1 = $db->query("SELECT stock FROM catalog_product WHERE id = ?", [$q[0]->id_product])->getResult();
            $stock = catalog::stock_add(count($q1) ? $q1[0]->stock : 0, $q[0]->amount, -1);
            
            $qty = min(catalog::stock_min($stock, $mask, true), $amount);
            $amount = catalog::stock_mul($mask, -$qty); 
        }
        
        $db->query("UPDATE catalog_stock_op SET amount = ?, time = ?, status = ?, reference = ? WHERE id = ?", [implode("/", $amount), time(), $status, $reference, $rid]);
        catalog::finalize_stock($pid); 
            
        return $qty;     
    }
    public static function reservation_release($order, $rid) {
        $db = core::$db;
        $q = $db->query("SELECT id_product FROM catalog_stock_op WHERE id = ?", [$rid])->getResult();
        if (!count($q)) return;
        
        $pid = $q[0]->id_product;
        $db->query("DELETE FROM catalog_stock_op WHERE id = ?", [$rid]);
        
        catalog::finalize_stock($pid, $db);
    }
    public static function reservation_check($order, $rid) {
        $db = core::$db;
        $q = $db->query("SELECT id FROM catalog_stock_op WHERE id = ?", [$rid])->getResult();
        
        if (!count($q))
            return false;
        
        $db->query("UPDATE catalog_stock_op SET time = ? WHERE id = ?", [time(), $rid]);
        
        return true;
    }
	
	public static function mark_price($id_item, $price) {
		if ($price <= 0) return;

		$date = time();
		$db = core::$db;
		$q = $db->query("SELECT * FROM catalog_ref_price WHERE id_item = ?", [$id_item])->getResult();

		$ref_id = 0;
		$update = false;
		if (count($q)) {
			$ref_id = $q[0]->id;
			$ref_date = strtotime($q[0]->date);
			$ref_price = $q[0]->price;
			
			if ($ref_date < strtotime("-30 days", $date) || $price < $ref_price) 
				$update = true;
		} else
			$update = true;

		if ($update) {
			$r = dbClass::create("catalog_ref_price");
			$r->id = $ref_id;
			$r->price = $price;
			$r->date = utils::mysql_datetime($date);
			$r->id_item = $id_item;
			$r->save();
		}
	}
    public static function format_price($price) {
		if (is_array($price)) {
			$res = array();
			foreach ($price as $key => $val) {
				if ($val === "" || is_null($val)) $val = "";
				$res[$key]= str_replace(".", ",", strval($val));
			}
			return $res;
		}
		
        if ($price === "" || is_null($price)) return "";
        
        return str_replace(".", ",", strval($price));
    }
	public static function build($load_sql = "", $include_lists = "", $exclude_lists = "", $serialize = false) {
		$db = core::$db;
		
		mlClass::$mlf["site_catalog_product"] = mlClass::$mlf["catalog_product"];
		mlClass::$mlf["site_catalog_category"] = mlClass::$mlf["catalog_category"];

		$columns = config::get("catalog.product_base_columns") . "," . config::get("catalog.product_columns");
		$select = dbUtils::clone_table($db, "catalog_product", "site_catalog_product", true, $columns, null, "p.");
		
		$add_sql = "p.id_parent = 0 AND (p.visible = 1 OR p.visible REGEXP '[[:<:]]" . lang::$lang . "[[:>:]]') AND p.categories != '' AND p.price != 0";
		if ($load_sql) $add_sql.= " AND " . $load_sql;
	
		if ($include_lists) {
		//	LOAD FROM LISTS
			$db->query("INSERT INTO `site_catalog_product` SELECT $select FROM catalog_product AS p INNER JOIN catalog_list AS l ON l.id_product = p.id AND $add_sql AND l.id_parent IN ($include_lists)");
		} else {
		//	LOAD WHOLE CATALOG
			$db->query("INSERT INTO `site_catalog_product` SELECT $select FROM catalog_product as p WHERE " . $add_sql);
		}

		if ($exclude_lists) {
		//	DELETE ITEMS FROM EXCLUDED LISTS
			$db->query("DELETE FROM `site_catalog_product` WHERE id IN (SELECT DISTINCT p.id FROM catalog_product AS p INNER JOIN catalog_list AS l ON l.id_product = p.id AND l.id_parent IN ($exclude_lists)" . $load_sql . ")");
		}
		
		//	TRANSLATE
		if (lang::$lang != lang::$def_lang) {
			$ml_cols = config::get("lang.mlf.catalog_product", array());
			$fl_cols = "," . $columns . ",";
			$sql = "";
			foreach ($ml_cols as $col) {
				if (strpos($fl_cols, "," . $col . ",") !== false) 
					$sql.= ",p.$col = CASE WHEN ml.$col IS NOT NULL AND ml.$col != '' THEN ml.$col ELSE p.$col END";
			}
			if ($sql) {
				$sql = "UPDATE `site_catalog_product` AS p INNER JOIN `catalog_product_ml` AS ml ON ml.table_id = p.id AND ml.lang = '" . lang::$lang . "' SET " . trim($sql, ",");
				$db->query($sql);
			}
		}		

		//	SERIALIZE CATALOG
		/*
		if ($serialize) {
			$db->query("INSERT INTO `$table_name` SELECT p.* FROM catalog_product AS p INNER JOIN `$table_name` AS c ON p.id_parent = c.id");
			$db->query("UPDATE `$table_name` AS t INNER JOIN (SELECT id, categories, url FROM catalog_product WHERE item_count != 0) AS s ON t.id_parent = s.id SET t.categories = s.categories, t.url = s.url, t.type = 'group'");
			//$db->query("UPDATE `$table_name` SET type = 'group'");
		}
		*/
		
	//	LOAD CATEGORY IDS FROM CATALOG
		$cids = array();
        foreach ($db->query("SELECT DISTINCT categories FROM site_catalog_product")->getResult() as $q) {
			$e = preg_split("%[,.]%", $q->categories);
			foreach ($e as $ee)
				if ($ee) $cids[$ee] = 1;
        }
		
	//	CREATE TEMP TABLE FOR CATEGORIES
		$columns = config::get("catalog.category_base_columns") . "," . config::get("catalog.category_columns");
		$select = dbUtils::clone_table($db, "catalog_category", "site_catalog_category", true, $columns, ["_trans" => "tinyint NOT NULL"]);
		$cids = arrays::partition(array_keys($cids), 200);
		
	//	FILL TABLE
		$where = "(visible = 1 OR visible REGEXP '[[:<:]]" . \Sloway\lang::$lang . "[[:>:]]')";
		foreach ($cids as $_cids) {
			$sql = "INSERT INTO `site_catalog_category` SELECT $select,1 FROM catalog_category WHERE id IN (" . implode(",", $_cids) . ") AND " . $where;
			$db->query($sql);	
		}
		
	//	TRANSLATE
		if (lang::$lang != lang::$def_lang) {
			$ml_cols = config::get("lang.mlf.catalog_category", array());
			$fl_cols = "," . $columns . ",";
			$sql = "";
			foreach ($ml_cols as $col) {
				if (strpos($fl_cols, "," . $col . ",") !== false) 
					$sql.= ",p.$col = CASE WHEN ml.$col IS NOT NULL AND ml.$col != '' THEN ml.$col ELSE p.$col END";
			}
			if ($sql) {
				$sql = "UPDATE `site_catalog_category` AS p INNER JOIN `catalog_category_ml` AS ml ON ml.table_id = p.id AND ml.lang = '" . lang::$lang . "' SET " . trim($sql, ",");
				$db->query($sql);
			}
		}	
		
		$cats = mlClass::load("site_catalog_category", null, 0, array("index" => "id"), true, "\Sloway\dbModelObject");
		foreach ($cats as $cat) 
			self::$valid_cats[$cat->id] = true;
		
		self::$categories = $cats;
		dbModel::preload("catalog_category", $cats, arrays::make_tree($cats, "subcat"));				
	}

	public static function category_visible($id) {	
		return isset(self::$build_cats[$id]);
	}
	public static function product_visible($id) {
		$q = core::$db->query("SELECT id FROM `site_catalog_product` WHERE id = '$id'")->getResult();
		return count($q) != 0;
	}

	public static function stock_notify($email, $product_id) {
		$q = core::$db->query("SELECT * FROM catalog_stock_sub WHERE email = ? AND id_product = ?", [$email, $product_id])->getResult();
		if (!count($q)) {
			$sub = dbClass::create("catalog_stock_sub");
			$sub->id_product = $product_id;
			$sub->email = $email;
			$sub->date = utils::mysql_datetime(time());
			$sub->save();
		}
	}
	
	public static function sql_ml_select($table, $n_def, $n_ml, $use_def = false) {
		$ml = "," . implode(",", config::get("lang.mlf." . $table, array())) . ",";

		$q = self::$db->query("SHOW COLUMNS FROM `$table`")->getResult();
		$res = [];
		foreach ($q as $qq) {
			$col_name = $qq->Field;
			if (strpos($ml, "," . $col_name . ",") !== false) {
				if ($use_def) {
					$res[]= "(CASE WHEN $n_ml.$col_name != '' THEN $n_ml.$col_name ELSE $n_def.$col_name END) AS $col_name";
				} else
					$res[]= "$n_ml.$col_name AS " . $col_name; 
			} else
				$res[]= $n_def . "." . $col_name;
		}
		return implode(",\n", $res);	
	}
	public static function category_path($cid, $as_string = false) {
		$res = array($cid);
		$q = self::$db->query("SELECT id_parent FROM catalog_category WHERE id = '$cid'")->getResult();
		$pid = count($q) ? $q[0]->id_parent : 0;
		while ($pid) {
			$res[]= $pid;
			$q = self::$db->query("SELECT id_parent FROM catalog_category WHERE id = '$pid'")->getResult();
			$pid = count($q) ? $q[0]->id_parent : 0;
		}
		
		$res = array_reverse($res);
		if ($as_string)
			$res = implode(".", $res);
		
		return $res;
	}
	public static function move_category($cid, $pid, $index) {
		$src_path = catalog::category_path($cid, true);
		if ($pid)
			$dst_path = catalog::category_path($pid, true) . "." . $cid; else
			$dst_path = $cid;
		
		$src = $src_path . ".";
		$dst = $dst_path . ".";
		
		$tables = config::get("catalog.move_cat_tables");
		foreach ($tables as $table) {
			$rows = self::$db->query("SELECT id,categories FROM `$table` WHERE categories REGEXP '[[:<:]]($src_path)[[:>:]]'")->getResult();
			$insert = array();
			$cache = array();
			$cache_size = 0;
			foreach ($rows as $prod) {
				//echob("BEFORE: ", $prod->categories);

				if (isset($cache[$prod->categories])) {
					// echob("cache: ", $cache[$prod->categories]);
					$insert[]= array("id" => $prod->id, "categories" => $cache[$prod->categories]);

					continue;
				}

				$cats = explode(",", $prod->categories);
				$new_cats = array();

				foreach ($cats as $cat) {
					if (strpos($cat . ".", $src) === 0) 
						$new_cat = trim(str_replace($src, $dst, $cat . "."), "."); else
						$new_cat = $cat;

					$add = true;
					foreach ($new_cats as $i => $ex_cat) {
						$cmp = \Sloway\utils::str_compare($new_cat, $ex_cat);

						if ($cmp !== false)
							$add = false;

						if ($cmp === 1) 
							$new_cats[$i] = $new_cat; 
					}
					if ($add) {
						$new_cats[] = $new_cat;
					}
				}
				//echob("after: ", implode(",", $new_cats));

				if ($cache_size < 100)
					$cache[$prod->categories] = implode(",", $new_cats);

				$insert[]= array("id" => $prod->id, "categories" => implode(",", $new_cats));
			}		

			// echod($insert);
			dbUtils::insert_update(self::$db, $table, $insert, true);
		}
		
		self::$db->query("UPDATE catalog_category SET id_parent = ? WHERE id = ?", [$pid, $cid]);
	}
	public static function delete_category($cid) {
		$pth = catalog::category_path($cid);
		$cmp = implode(".", $pth) . ".";
		array_pop($pth);
		$rep = implode(".", $pth);
		
		$insert = array();
		foreach (self::$db->query("SELECT id,categories FROM catalog_product WHERE id_parent = 0 AND categories REGEXP '[[:<:]]($cid)[[:>:]]'")->getResult() as $prod) {
			$parts = array();
			foreach (explode(",", $prod->categories) as $_part) {
				if (strpos($_part . ".", $cmp) !== 0) 
					$parts[]= $_part; else
				if ($rep)
					$parts[]= $rep;
			}
		
			$parts = catalog::merge_ids($parts);
			$insert[]= array("id" => $prod->id, "categories" => implode(",", $parts));
		}		
		dbUtils::insert_update(self::$db, "catalog_product", $insert, true);
	}
	public static function merge_ids($ids) {
		$res = [];
		foreach ($ids as $id) {
			$add = true;
			foreach ($res as $i => $ex_id) {
				$cmp = \Sloway\utils::str_compare($id, $ex_id);

				if ($cmp !== false)
					$add = false;

				if ($cmp === 1) 
					$res[$i] = $id; 
			}
			if ($add) {
				$res[] = $id;
			}
		}		
		
		return $res;
	}
	
	public static function regen_items($sql) {
		$db = self::$db;
		
		$prop_ids = array();
		$grp_ids = array();
		$items = mlClass::load("catalog_product", $sql, 0, null, "*");
		foreach ($items as $item) {
			$_ids = ($item->properties) ? preg_split('/[,.]/', $item->properties) : array();
			foreach ($_ids as $id)
				$prop_ids[$id] = 1;
			
			$grp_ids[$item->id_parent] = 1;
		}
		$prop_ids = array_keys($prop_ids);
		$grp_ids = array_keys($grp_ids);
		
		$props = count($prop_ids) ? mlClass::load("catalog_property", "@id IN (" . implode(",", $prop_ids) . ")", 0, array("index" => "id"), "*") : array();
		$groups = count($grp_ids) ? mlClass::load("catalog_product", "@id IN (" . implode(",", $grp_ids) . ")", 0, array("index" => "id"), "*") : array();
			
		$insert = array();
		$insert_ml = array();
		foreach ($items as $item) {
			if (!isset($groups[$item->id_parent])) continue;
			
			$ids = arrays::decode($item->properties, ".", ",");
			$_props = "";
			foreach ($ids as $pid => $vid)
				if (isset($props[$pid]) && isset($props[$vid]))
					$_props.= "," . $pid . "." . $vid;
			
			foreach (lang::languages(true) as $lang) {
				if (!\Sloway\flags::get($item->flags, "ct")) {
					$title = $groups[$item->id_parent]->get("title", $lang);
					$pf = "";
					foreach ($ids as $pid => $vid) {
						if (!isset($props[$pid]) || !isset($props[$vid])) continue;
						
						if ($props[$pid]->selector_template)
							$pf.= ", " . $props[$pid]->get("title", $lang). ": " . $props[$vid]->get("title", $lang);
					}

					if ($pf)
						$title.= " " . trim($pf, " ,");
				} else
					$title = $item->get("title", $lang);
			
				if ($lang == mlClass::$def_lang) 
					$insert[]= array("id" => $item->id, "title" => $title, "properties" => trim($_props, ","));
					$insert_ml[]= array("table_id" => $item->id, "lang" => $lang, "title" => $title);
			}
		}
		
		dbUtils::insert_update($db, "catalog_product", $insert, true);
		dbUtils::insert_update($db, "catalog_product_ml", $insert_ml, true);
	}
	
	public static function build_adm_categories() {
		$db = core::$db;
		
		$select = dbUtils::clone_table($db, "catalog_category", "adm_catalog_category", true, null, ["_trans" => "tinyint NOT NULL"]);
		if (($lng = mlClass::$lang) != mlClass::$def_lang) {
			$sql_vals = catalog::sql_ml_select("catalog_category", "c", "ml", true);
			$sql = "SELECT $sql_vals,1 FROM catalog_category as c LEFT JOIN catalog_category_ml as ml ON ml.table_id = c.id AND ml.lang = '$lng'";
		} else
			$sql = "SELECT c.*,1 FROM catalog_category as c";
		
		$db->query("INSERT INTO `adm_catalog_category` " . $sql);	
		
		$cats = mlClass::load("adm_catalog_category", null, 0, array("index" => "id"), null, "\Sloway\dbModelObject");
		dbModel::preload("adm_catalog_category", $cats, arrays::make_tree($cats, "subcat"));	
		
		self::$categories = $cats;
	}
}  


