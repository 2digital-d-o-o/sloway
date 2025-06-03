<?php 

namespace Sloway\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sloway\path;
use Sloway\config;
use Sloway\url;
use Sloway\utils;
use Sloway\arrays;
use Sloway\admin;
use Sloway\mlClass;
use Sloway\dbClass;
use Sloway\genClass;
use Sloway\settings;
use Sloway\images;
use Sloway\files;
use Sloway\acontrol;
use Sloway\dbModel;
use Sloway\userdata;
use Sloway\catalog;
use Sloway\thumbnail;
use Sloway\slug;
use Sloway\dbUtils;
use Sloway\lang;
use Sloway\mlClass2;
 
class AdminCatalog extends AdminController {
    public $module = 'catalog';    
    public $catalog_mode = null;
    public $history = array();
    public $history_handler = null;
    public $_properties;
	public $_lists = null;
	
	protected function gen_item_title($item, $lang, $parent = null) {
		if (!$parent)
			$parent = mlClass::load("catalog_product", "@id = '$item->id_parent'", 1, null, "*");
		
		if (!$parent) return $title;			
		
		$res = $parent->get("title", $lang);
		$postfix = "";

		$ids = arrays::decode($item->properties, ".", ",");

		$pids = array_keys($ids);
		if (count($pids)) {
			$props = mlClass::load("catalog_property", "@id IN (" . implode(",", $pids) . ") AND (flags LIKE '%gen_title%' OR selector_template != '') ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1 ASC", 0, null, $lang);

			foreach ($props as $prop) {
				$vid = $ids[$prop->id];
				$value = mlClass::load("catalog_property", "@id = '$vid'", 1, null, $lang);

				if (!$value) continue;

				$postfix.= ", " . $prop->title . ": " . $value->title; 
			}
		}

		if ($postfix)
			$res.= " - " . trim($postfix, ", ");

		return $res;
	}
	protected function gen_item_titles($item) {
		$parent = mlClass::load("catalog_product", "@id = '$item->id_parent'", 1, null, "*");
		if (!$parent) return $title;
		
		$result = array();
		foreach (lang::languages(true) as $lang) {
			$result[$lang] = $this->gen_item_title($item, $lang, $parent);
		}
		
		return $result;
	}
	protected function check_sort($name, $model) {
		$default = null;
		foreach ($model as $ops) {
			if (is_null($default)) $default = $ops["id"];
			if ($ops["id"] == $name && $ops["sort"]) return $name;
		}

		return $default;
	}    
    protected function browser_node($node, $data) {
        return $node;     
    }
    protected function browser_result($product) {
        
    }
    protected function serialize_categories($ids) {
        if (!$ids) return "";
        
        $res = array();
        $ids = explode(",", $ids);
        foreach ($ids as $id) {
            $idps = explode(".", $id);
            
            $p2 = array();
            foreach ($idps as $idp) {
				$cat = isset(catalog::$categories[$idp]) ? catalog::$categories[$idp] : null;
                //$cat = mlClass::load("catalog_category", "@id = '$idp'", 1);
                if (!$cat) continue;
                
                $p2[] = $cat->title;
            }    
            
            $res[] = implode(" - ", $p2);
        }
        
        return implode(", ", $res);
    }
    protected function serialize_properties($ids) {
        if (!$ids) return "";
        
        $ids = arrays::decode($ids, ".", ",");
        $pids = array_keys($ids);
		if (!count($pids)) return "";
		
        $props = mlClass::load("catalog_property", "@id IN (" . implode(",", $pids) . ")");
        
        $res1 = "";
        $res2 = "";
        foreach ($props as $prop) {
            $vid = $ids[$prop->id];
            $value = mlClass::load("catalog_property", "@id = '$vid'", 1);
            
            if (!$value) continue;
            
            if ($prop->selector_template || \Sloway\flags::get($prop->flags, "gen_title"))
                $res1.= ", " . $prop->title . ": " . $value->title; else
                $res2.= ", " . $prop->title . ": " . $value->title; 
        }
        
        $res = trim($res1, ", ") . ", " . trim($res2, ", ");
        return trim($res, ", ");
    }
    protected function serialize_tags($ids) {
        if (!$ids) return "";
        
        $res = "";
        $ids = trim($ids,",");
		$lng = mlClass::$lang;
        $tags = ($ids) ? mlClass::load("catalog_tag", "@id IN ($ids) ORDER BY title ASC") : array();
        foreach ($tags as $tag)
            $res.= ", " . $tag->title;
        
        return trim($res, ", ");
    }
	protected function serialize_lists($item) {
		if ($item->id_parent) return "";
		
		$res = "";
		$q = $this->db->query("SELECT DISTINCT id_parent FROM catalog_list WHERE id_product = ?", [$item->id])->getResult();
		foreach ($q as $qq) {
			if (isset($this->_lists[$qq->id_parent]))
				$res.= ", " . $this->_lists[$qq->id_parent]->title;
		}
		
		return trim($res, ", ");
	}    
    protected function catalog_action() {
        if ($id = $this->input->post("delete_group")) {
            dbModel::delete('catalog_product', "@id = " . $id, 1); 
        }

        if ($id = $this->input->post("delete_bundle")) {
            dbModel::delete('catalog_product', "@id = " . $id, 1); 
        }

        if ($id = $this->input->post("delete_item")) {
            dbModel::delete('catalog_item', "@id = " . $id, 1); 
        }
    }
    protected function catalog_model() {
        $model = array(
            "id" => array(
                "id" => "ID",
                "content" => "ID", 
                "width" => 50, 
                "sort" => true
            ),
            "title" => array(
                "id" => "title",
                "content" => et("Title"), 
                "width" => 250, 
                "sort" => true
            ),
            "properties" => array(
                "id" => "properties",
                "content" => et("Properties"),
                "edit" => "custom",
                "max_width" => 600,
            ),
            "visible" => array(
                "id" => "visible",
                "content" => et("Visibility"),
            ),
            "sort_num" => array(
                "id" => "sort_num",
                "content" => "#",
                "width" => "content",
                "edit" => "text",
                "sort" => true
            ),
            "code" => array(
                "id" => "code",
                "content" => et("Code"), 
                "width" => "content",
                "edit" => "text", 
                "sort" => true
            ),
            "price" => array(
                "id" => "price",
                "content" => et("Price"), 
                "width" => "content",
                "edit" => false, 
            ),
            "stock" => array(
                "id" => "stock",
                "content" => et("Stock"), 
                "width" => "content",
                "edit" => "custom"
            ),
            "tags" => array(
                "id" => "tags",
                "content" => et("Tags"),
                "edit" => "custom",
            ),            
            "categories" => array(
                "id" => "categories",
                "content" => et("Categories"),
                "edit" => false,
                "max_width" => 250,
            ),
			"lists" => array(
                "id" => "lists",
                "content" => et("Lists"),
                "edit" => "custom",
                "max_width" => 250,
			),
            "modified" => array(
                "id" => "modified",
                "sort" => true,
                "content" => et("Last modified"),
                "width" => "content",
            ),
            "menu" => array(
                "id" => "menu",
                "content" => "", 
                "align" => "right",
                "fixed" => true, 
                "width" => "110"
            ),
        );  
        if (!$this->stock_manager)
            $model["stock"]["edit"] = "text";
        if (!Admin::auth("catalog.products.stock")) 
            $model["stock"]["edit"] = false;
		if (!Admin::auth("catalog.lists") || !config::get("catalog.lists"))
			unset($model["lists"]);
        
        return $model;  
    }
	
	protected function build_products($filter, $from = null) {
		if (!$from) $from = "catalog_product";
		
		dbUtils::clone_table($this->db, "catalog_product", "adm_catalog_product_t", true);
		dbUtils::clone_table($this->db, "catalog_product", "adm_catalog_product", true);
		
		if (($lng = mlClass::$lang) != mlClass::$def_lang) {
			$sql_vals = catalog::sql_ml_select("catalog_product", "p", "ml");
			$sql = "SELECT $sql_vals FROM $from as p LEFT JOIN catalog_product_ml as ml ON ml.table_id = p.id AND ml.lang = '$lng'";
		} else
			$sql = "SELECT * FROM $from";
		
		$this->db->query("INSERT INTO `adm_catalog_product_t` " . $sql);
		
		$filter_search = preg_replace('!\s+!', '|', $filter->search);
        $filter_cats = $filter->cats;
        $filter_tags = $filter->tags;

        $groups_sql = array("id_parent = 0");
        if ($filter_tags)
			$groups_sql[]= "(tags REGEXP '[[:<:]]($filter_tags)[[:>:]]')";

        if ($filter_cats) { 
            if ($filter_cats == "none") {
                $q = $this->db->query("SELECT GROUP_CONCAT(id) as ids FROM catalog_category");
                if (count($q))
                    $groups_sql[] = "categories NOT REGEXP '[[:<:]](" . str_replace(",", "|", $q[0]->ids) . ")[[:>:]]'";
            } else
                $groups_sql[] = "categories REGEXP '[[:<:]](" . str_replace(",", "|", $filter_cats) . ")[[:>:]]'";
        }     				
		
		if ($filter_search) {
			// SEARCH ITEMS
			if ($filter->search_mode == "item") {
				$this->db->query("INSERT INTO `adm_catalog_product` SELECT * FROM adm_catalog_product_t as p WHERE p.type = 'item' AND p.id_parent != 0 AND (p.title REGEXP '($filter_search)' OR p.code REGEXP '($filter_search)')");
				$q = $this->db->query("SELECT GROUP_CONCAT(id_parent) as ids FROM adm_catalog_product")->getResult();

				if (count($q) && ($ids = $q[0]->ids))
					$groups_sql[]= "id IN (" . $q[0]->ids . ")"; else
					$groups_sql = null; // NO RESULTS
			} else {
				// SEARCH GROUPS
				$this->db->query("INSERT INTO `adm_catalog_product` SELECT * FROM adm_catalog_product_t as p WHERE p.type = 'item' AND p.id_parent != 0");
				if ($filter_search) 
					$groups_sql[]= "(title REGEXP '($filter_search)' OR code REGEXP '($filter_search)')";
			}
		} else
			$this->db->query("INSERT INTO `adm_catalog_product` SELECT * FROM adm_catalog_product_t as p WHERE p.type = 'item' AND p.id_parent != 0");

		$this->db->query("INSERT INTO `adm_catalog_product` SELECT * FROM adm_catalog_product_t as p WHERE " . implode(" AND ", $groups_sql));
	}
	protected function build_categories() {
		catalog::build_adm_categories();
	}
	protected function build_properties() {
		dbUtils::clone_table($this->db, "catalog_property", "adm_catalog_property", true);
		
		if (($lng = mlClass::$lang) != mlClass::$def_lang) {
			$sql_vals = catalog::sql_ml_select("catalog_property", "c", "ml");
			$sql = "SELECT $sql_vals FROM catalog_property as c LEFT JOIN catalog_property_ml as ml ON ml.table_id = c.id AND ml.lang = '$lng'";
		} else
			$sql = "SELECT * FROM catalog_property";
		
		$this->db->query("INSERT INTO `adm_catalog_property` " . $sql);		
	}
	protected function build_tags() {
		dbUtils::clone_table($this->db, "catalog_tag", "adm_catalog_tag", true);
		
		if (($lng = mlClass::$lang) != mlClass::$def_lang) {
			$sql_vals = catalog::sql_ml_select("catalog_tag", "c", "ml");
			$sql = "SELECT $sql_vals FROM catalog_tag as c LEFT JOIN catalog_tag_ml as ml ON ml.table_id = c.id AND ml.lang = '$lng'";
		} else
			$sql = "SELECT * FROM catalog_tag";
		
		$this->db->query("INSERT INTO `adm_catalog_tag` " . $sql);			
	}
 
    protected function catalog_ch_sel($item, $mode) { 
        return array("items");    
    }
    protected function catalog_load($items, $loaded, $types, $level, $mode) {
        $rows = array();
        $check = $this->input->post("check", "group,item");
        
        foreach ($items as $item) {      
            $type = ($item->item_count) ? "group" : "item";
            $res = array(
                "id"   => $item->id,
                "check" => strpos($check, $type) !== false ? true : "disabled"
            );
            
		/*
            if (config::get("catalog.discounts")) {
                $item->discount = 0;
				$lng = mlClass::$lang;
                $sql = "SELECT * FROM catalog_discount WHERE (visible = 1 OR visible REGEXP '[[:<:]]($lng)[[:>:]]') AND (";
                $sql.= "categories REGEXP CONCAT('[[:<:]](', REPLACE(REPLACE('{$item->categories}','.','|'), ',','|') , ')[[:>:]]') OR products REGEXP '[[:<:]]{$item->id}[[:>:]]') AND ";
                $sql.= "(time_from = 0 OR date_from < NOW()) AND (time_to = 0 OR date_to > NOW()) ORDER BY value DESC LIMIT 1";
                $q = $this->db->query($sql)->getResult();
                if (count($q))
                    $item->discount = $q[0]->value; 
            }
		
		*/
            
            if ($level != 0) {
                $res["rows"] = array();
                $count = $item->children_count($this->catalog_ch_sel($item, $mode));
				$res["rows_cnt"] = $count;
				
                if ($count <= $this->tree_treshold && in_array($item->id, $loaded)) {
                    foreach ($this->catalog_ch_sel($item, $mode) as $ch_name) {
                        $ch = v($item, $ch_name, array());
                        foreach ($ch as $sub) {
                            $s = array(
                                'id' => $sub->id,
                                'cells' => $this->catalog_row($sub)
                            );
                            $res["rows"][] = $s;                    
                        }   
                    }
                        
                    //$res["loaded"] = true;
                } //else 
                   // $res["loaded"] = $count == 0;
                
                $res["attr"] = "data-count='$count'";
                $item->ch_count = $count;
            }
            $res["cells"] = $this->catalog_row($item);
            $rows[] = $res;
        }   
        
        return $rows; 
    }
    protected function catalog_editable($column, $node) {
        if (!Admin::auth("catalog.products.edit")) return false;
        
        switch ($column) {
            case "code": 
                if ($node->locked) return false;
                
                return (Admin::auth("catalog.codes")) ? "text" : false;
            case "sort_num":
                return ($node->locked) ? false : "text";
            case "price":
                return ($node->locked) ? false : "custom";
            case "stock":       
                if (!Admin::auth("catalog.products.stock")) return false;
                //if ($node->locked) return false;
                if ($node->type == "bundle") return false;
                
                return ($this->stock_manager) ? "custom" : "text";
            case "type": 
                if ($node->locked) return false;
                return ($node->type == 'group') ? "custom" : false;
            case "categories":
                return ($node->type != "item") ? "custom" : false;
            case "properties":
                return "custom";
			case "tags":
				return ($node->type != "item") ? "custom" : false;
			case "lists":
				return ($node->type != "item") ? "custom" : false;
        }                                        
    }
    protected function catalog_cell($column, $node) {
        $edit_url = url::site("AdminCatalog/Edit" . ucfirst($node->type) . "/$node->id");
        
        switch ($column) {
			case "id": 
				return $node->id;
            case "title": 
                if (count($node->images)) 
                    $icon = "<div style='display: inline-block; vertical-align: middle; margin-right: 3px'>" . thumbnail::from_image($node->images, "admin_catalog_icon")->display() . "</div>"; else
                    $icon = "<img class='admin_icon' src='" . \Sloway\utils::icon("icon-doc-white.png") . "'>";
                    
				$title = $node->title;
                if ($this->catalog_mode == "catalog" && Admin::auth("catalog.products.edit")) 
                    $title = $icon . "<a href='$edit_url' onclick='return admin_redirect(this)'>" . $title . "</a>"; else
                    $title = $icon . $title;
                
                if ($node->ch_count)
                    $title.= "&nbsp;<span style='color: gray'>($node->ch_count)</span>";
                    
                $res = $title . "&nbsp;";
                if (floatval($node->discount))
                    $res.= "<span class='admin_flag green' style='cursor: pointer' onclick='\$.catalog.discount_list(\"product\", $node->id)'>&nbsp;-" . number_format($node->discount,0) . "%</span>";

                return $res;
            case "sort_num":
                return array("content" => $node->sort_num, "edit" => $this->catalog_editable("sort_num", $node));
            case "code":
                return array("content" => $node->code, "edit" => $this->catalog_editable("code", $node));
            case "price":
                if (floatval($node->price_action) && !config::get("catalog.discounts")) 
                    $value = utils::decode_price($node->price_action) . "&nbsp;<span style='color: silver; text-decoration: line-through'>" . utils::decode_price($node->price) . "</span>"; else
					$value = utils::decode_price($node->price);
            
                return array("content" => $value);
            case "stock":
                $val = $node->stock;
                if ($node->type == "bundle") 
                    $val = "";
                    
                return array("content" => $val, "edit" => $this->catalog_editable("stock", $node));
            case "type": 
                $type = "";
                if ($node->type == 'group') {
                    if ($node->type_id == 0)
                        $type = "Default"; else
                        $type = v(mlClass::load("catalog_attribute", "@id = " . $node->type_id, 1), "title", "Unknown");
                }
        
                return array("content" => $type, "edit" => $this->catalog_editable("type", $node));
            case "visible":
				return Admin::VisibilityBar($node->visible, "catalog_visibility('$node->id')");
            case "flags":
                $flags = array();            
                foreach (arrays::explode(",", $node->flags) as $flg) 
                    $flags[] = et("admin_catalog_" . $flg);        

                return array("content" => implode(", ", $flags));            
            case "categories":
                $categories = "";
                if ($node->type != 'item') 
                    $categories = $this->serialize_categories($node->categories);                
                    
                return array("content" => $categories, "edit" => $this->catalog_editable("categories", $node));
            case "tags":
                return array("content" => $this->serialize_tags($node->tags), "edit" => $this->catalog_editable("tags", $node));
                
            case "properties":
                $properties = $this->serialize_properties($node->properties);                
                    
                return array("content" => $properties, "edit" => $this->catalog_editable("properties", $node));
			case "lists": 
				return array("content" => $this->serialize_lists($node), "edit" => $this->catalog_editable("lists", $node));
            case "modified":
                return \Sloway\utils::modified($node->edit_time, $node->edit_user);
            case "menu":
                $menu = "";
                if (Admin::auth("catalog.products.add") && $node->type == 'group')// && $this->structured_type($node->type_id)) 
                    $menu.= Admin::IconB("icon-add.png", null, t("Add"), "catalog_new_item($node->id)");
                    
                //if (Admin::auth("catalog.products.add"))
                //    $menu.= Admin::IconB("icon-copy.png", null, t("Duplicate"), "catalog_duplicate(\$(this))", "data-type='$node->type' data-id='$node->id'");

                if (Admin::auth("catalog.products.edit"))
                    $menu.= Admin::IconB("icon-edit.png", "ajax:" . $edit_url, t("Edit"));
                
                if (Admin::auth("catalog.products.delete") && !$node->locked)
                    $menu.= Admin::IconB("icon-delete.png", null, t("Delete"), "catalog_delete(\$(this))", "data-type='$node->type' data-id='$node->id'");
                    
                return $menu;
        }
    }
    protected function catalog_row($node, $cell = null) {
        if ($this->catalog_mode == "browser") {
            $categories = "";
            if ($node->type != 'item') 
                $categories = $this->serialize_categories($node->categories);
                
            return array(
                $node->get("title", Admin::$content_lang),    
                $node->code,
                ($node->price_action) ? utils::decode_price($node->price_action) : utils::decode_price($node->price),
                $this->serialize_tags($node->tags),
                $categories,
                \Sloway\utils::modified($node->edit_time, $node->edit_user),
            );    
        }
        
        $res = array();
        foreach ($this->model as $col_id => $column) 
            $res[$col_id] = $this->catalog_cell($col_id, $node);
        
        if (!is_null($cell))
            return v($res, $cell, ""); else
            return array_values($res);
    } 
     
    protected function categories_row($category) {
        $edit_url = url::site("AdminCatalog/EditCategory/" . $category->id);
        $icon = "<img class='admin_icon' src='" . \Sloway\utils::icon("icon-doc-white.png") . "'>";
        
        $title = $icon . "<a href='$edit_url' onclick='return admin_redirect(this)'>" . $category->title . "</a>";
        
        if ($category->discount)
            $title.= " <span class='admin_flag green' onclick='\$.catalog.discount_list(\"category\", $category->id)'>&nbsp;-" . number_format($category->discount,0) . "%</span>";        
            
        
        $menu = Admin::IconB("icon-edit.png", "ajax:" . $edit_url, t("Edit"));
        $menu.= Admin::IconB("icon-add.png", false, t("Add"), "categories_add($category->id)");
        if (!$category->locked)
            $menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "categories_delete($category->id)");
            
        $q = $this->db->query("SELECT COUNT(id) as cnt FROM catalog_product WHERE categories REGEXP '[[:<:]]{$category->id}[[:>:]]'")->getResult();
        $cnt = $q[0]->cnt;
        
        if ($cnt)
            $title.= "&nbsp;<span style='color: gray'>($cnt)</span>";
            
        $users = $category->users ? arrays::regen(dbClass::load("admin_user", "@id IN ($category->users)"), "id", "username") : array();
        
        $res = array(
            "title" => array("content" => $title, "value" => $category->title),
            "sort_num" => array("content" => $category->sort_num),
            "visible" => Admin::VisibilityBar($category->visible, "categories_visibility('$category->id')"),
            "users" => implode(", ", $users),
            "modified" => \Sloway\utils::modified($category->edit_time, $category->edit_user),
            "menu" => $menu
        );
        
        return $res;
    } 
    protected function categories_load($pid, $loaded, $sort, $sort_dir) {
        $result = array();
        
        if ($sort == "sort_num") {
            if ($sort_dir > 0)
                $order = "ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1 ASC"; else
                $order = "ORDER BY (CASE sort_num WHEN '' THEN '0' ELSE sort_num END)*1 DESC"; 
        } else
            $order = " ORDER BY $sort " . (($sort_dir > 0) ? 'ASC' : 'DESC');
		
        $categories = mlClass::load("adm_catalog_category", "@id_parent = '$pid'" . $order, 0, array("index" => "id"));
        foreach ($categories as $category) {
            $category->discount = 0;
            if (config::get("catalog.discounts")) {
				$lng = mlClass::$lang;
                $sql = "SELECT * FROM catalog_discount WHERE visible REGEXP '[[:<:]](1|$$lng)[[:>:]]' AND categories REGEXP '[[:<:]]{$category->id}[[:>:]]' AND (time_from = 0 OR date_from < NOW()) AND (time_to = 0 OR date_to > NOW()) ORDER BY (UNIX_TIMESTAMP() - time_from - value) ASC LIMIT 1";
                $q = $this->db->query($sql)->getResult();
                if (count($q))
                    $category->discount = $q[0]->value;
            }
			
			$q = $this->db->query("SELECT COUNT(id) as count FROM adm_catalog_category WHERE id_parent = ?", [$category->id])->getResult();
            $cnt = $q[0]->count;
			
            $row = array(
                "id" => $category->id,
                "cells" => array_values($this->categories_row($category)),
				"rows_cnt" => $cnt,
                "rows" => array(),
            );
            
            if (is_array($loaded) && in_array($category->id, $loaded)) {
                $row["rows"] = $this->categories_load($category->id, $loaded, $sort, $sort_dir);
				// $res["loaded"] = true;
			} //else
				//$res["loaded"] = $cnt == 0;
            
            $result[] = $row;            
        } 
        return $result;               
    } 
    protected function categories_model() {
        $model = array(
            array(
               "id" => "title", 
               "width" => "content", 
               "edit" => "text",
               "edit_grip" => "div", 
               "edit_click" => false,
               "content" => t("Title"),
               "sort" => true
            ),
            array(
                "id" => "sort_num",
                "content" => "#",
                "width" => "content",
                "edit" => "text",
                "sort" => true
            ),
            array(
                "id" => "visible",
                "width" => "content",
                "content" => t("Visible"),
            ),
            array(
                "id" => "users",                          
                "width" => "content",
                "content" => t("Users"),
                "edit" => Admin::auth("catalog.categories.users") ? "custom" : false
            ),
            array(
                "id" => "modified", 
                "width" => "content", 
                "align" => "left",
                "content" => t("Modified"),
                "can_hide" => false 
            ),
            array(
                "id" => "menu", 
                "width" => "110", 
                "align" => "right",
                "fixed" => true,
            )
        );
        
        return $model;
    }
    
    protected function properties_load($pid, $sql_add, $loaded, $page, $perpage, $sort, $sort_dir) {
        $result = array();
        
        if ($sort == "sort_num") {
            if ($sort_dir > 0)
                $order = "ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1 ASC"; else
                $order = "ORDER BY (CASE sort_num WHEN '' THEN '0' ELSE sort_num END)*1 DESC"; 
        } else
            $order = " ORDER BY $sort " . (($sort_dir > 0) ? 'ASC' : 'DESC'); 
        
        $sql = "@id_parent = $pid" . $sql_add . " " . $order;
        if ($perpage) {
            $start = $page * $perpage;
            $sql.= " LIMIT $start,$perpage";
        }
        $properties = mlClass::load("adm_catalog_property", $sql);
        
        foreach ($properties as $property) {
            $q = $this->db->query("SELECT COUNT(id) as cnt FROM adm_catalog_property WHERE id_parent = ?", [$property->id])->getResult();
            $property->ch_count = $q[0]->cnt;
            
            $row = array(
                "id" => $property->id,
                "attr" => "data-count='$property->ch_count'",
                "cells" => array_values($this->properties_row($property)),
				"rows_cnt" => $property->ch_count,
                "rows" => array()
            );
            
            if (is_array($loaded) && in_array($property->id, $loaded)) {
                $row["rows"] = $this->properties_load($property->id, "", $loaded, 0, 0, $sort, $sort_dir);
                $row["loaded"] = true;    
            } else {
                $q = $this->db->query("SELECT COUNT(id) as count FROM catalog_property WHERE id_parent = ?", [$property->id])->getResult();
                $row["loaded"] = $q[0]->count == 0;
            }
            
            $result[] = $row;            
        } 
        return $result;               
    } 
    protected function properties_model() {
        $model = array(
            array(
               "id" => "title", 
               "width" => "content", 
               "edit" => "text",
               "edit_grip" => "div", 
               "edit_click" => false,
               "content" => t("Title"),
               "sort" => true
            ),
            array(
                "id" => "sort_num",
                "content" => "#",
                "width" => "content",
                "edit" => "text",
                "sort" => true
            ),
            array(
                "id" => "flags",
                "width" => "content",
                "content" => t("Flags"),
                "edit" => "custom",
            ),		
			array(
                "id" => "filter_template",
                "width" => "content",
                "content" => et("Filter template"),
                "edit" => "custom",
            ),
            array(
                "id" => "selector_template",
                "width" => "content",
                "content" => et("Selector template"),
                "edit" => "custom",
            ),
            array(
                "id" => "modified", 
                "width" => "content", 
                "align" => "left",
                "content" => t("Modified"),
                "can_hide" => false 
            ),
            array(
                "id" => "menu", 
                "width" => "110", 
                "align" => "right",
                "fixed" => true,
            )
        );
        
        return $model;
    }
    protected function properties_row($property) {
        $edit_url = url::site("AdminCatalog/EditProperty/" . $property->id);
        $icon = "<img class='admin_icon' src='" . \Sloway\utils::icon("icon-doc-white.png") . "'>";
        
        $title = $icon . "<a href='$edit_url' onclick='return admin_redirect(this)'>" . $property->title . "</a>";
        
        $menu = Admin::IconB("icon-edit.png", "ajax:" . $edit_url, et("Edit"));
        
        if (!$property->id_parent)
            $menu.= Admin::IconB("icon-add.png", false, t("Add"), "properties_add($property->id)");
            
        if (!$property->locked)
            $menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "properties_delete($property->id)");
            
        if ($property->ch_count)
            $title.= "&nbsp;<span style='color: gray'>($property->ch_count)</span>";
            
        $flags = array();            
        foreach (arrays::explode(",", $property->flags) as $flg) 
            $flags[] = et("admin_catalog_" . $flg);    
           
        $cfg = ($property->id_parent) ? "catalog.property_value_flags" : "catalog.property_flags";
        $flags_edit = false; // count(config::get($cfg, array())) ? "custom" : false;
        
        $flt_tmp = "";
        $sel_tmp = ""; 
        if (!$property->id_parent) {
			$flt_tmp = "<span style='color: silver'>" . et("Brez izbire") . "</span>";
			$sel_tmp = "<span style='color: silver'>" . et("Brez izbire") . "</span>"; 
			
            if ($property->filter_template) $flt_tmp = et("catalog_ft_" . $property->filter_template);
            if ($property->selector_template) $sel_tmp = et("catalog_st_" . $property->selector_template);
        }  
            
        $chk = ($property->visible) ? "checked" : ""; 
        $res = array(
            "title" => array("content" => $title, "value" => $property->title),
            //"value" => array("content" => $property->value, "edit" => $property->id_parent ? "text" : false),
			"flags" => array("content" => implode(", ", $flags), "edit" => $flags_edit),
            "sort_num" => array("content" => $property->sort_num),   
            //"visible" => "<input type='checkbox' name='visible' $chk onclick='properties_set_visible(\$(this))' data-id='$property->id' style='cursor: pointer'>",
            "filter_template" => array("content" => $flt_tmp, "edit" => !$property->id_parent ? "custom" : false),
            "selector_template" => array("content" => $sel_tmp, "edit" => !$property->id_parent ? "custom" : false),
            "modified" => \Sloway\utils::modified($property->edit_time, $property->edit_user),
            "menu" => $menu
        );
        
        return $res;
    } 
    
    protected function discounts_row($discount) {
        $edit_url = url::site("AdminCatalog/EditDiscount/" . $discount->id);
        $title = "<a href='$edit_url' onclick='return admin_redirect(this)'>" . $discount->title . "</a>";
        
        $menu = Admin::IconB("icon-edit.png", "ajax:" . $edit_url, t("Edit"));
        $menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "discounts_delete($discount->id)");
        
        $chk = ($discount->active) ? "checked" : ""; 
        $res = array(
            "title" => array("content" => $title, "value" => $discount->title),
        //    "active" => "<input type='checkbox' name='visible' $chk onclick='discounts_set_active(\$(this))' data-id='$discount->id' style='cursor: pointer'>",
			"active" => Admin::VisibilityBar($discount->visible, "discounts_active('$discount->id')"),
            "value" => array("content" => $discount->value),
            "date_from" => \Sloway\utils::date_time($discount->time_from),
            "date_to" => \Sloway\utils::date_time($discount->time_to),
            "modified" => \Sloway\utils::modified($discount->edit_time, $discount->edit_user),
            "menu" => $menu
        );
        
        return $res;
    } 
    protected function discounts_model() {
        $model = array(
            array(
               "id" => "title", 
               "width" => "content", 
               "edit_grip" => "div", 
               "edit_click" => false,
               "content" => t("Title"),
               "sort" => true
            ),
            array(
                "id" => "active",
                "content" => et("Active"),
            ),
            array(
                "id" => "value",
                "width" => "content",
                "content" => t("Value"),
                "sort" => true
            ),            
            array(
                "id" => "date_from",
                "width" => "content",
                "content" => t("Date from"),
                "sort" => true
            ),            
            array(
                "id" => "date_to",
                "width" => "content",
                "content" => t("Date to"),
                "sort" => true
            ),            
            array(
                "id" => "modified", 
                "width" => "content", 
                "align" => "left",
                "content" => t("Modified"),
                "can_hide" => false 
            ),
            array(
                "id" => "menu", 
                "width" => "80", 
                "align" => "right",
                "fixed" => true,
            )
        );
        
        return $model;
    }
    
    protected function tags_model() {
        $model = array(
            array(
               "id" => "title", 
               "width" => "content", 
               "edit_grip" => "div", 
               "edit_click" => false,
               "content" => t("Title"),
               "sort" => true
            ),
            array(
                "id" => "active",
                "width" => "content",
                "content" => t("Active"),
            ),
            array(
                "id" => "modified", 
                "width" => "content", 
                "align" => "left",
                "content" => t("Modified"),
                "can_hide" => false 
            ),
            array(
                "id" => "menu", 
                "width" => "80", 
                "align" => "right",
				"fixed" => true,
            )
        );
        
        return $model;
    }
    protected function tags_row($tag) {
        $edit_url = url::site("AdminCatalog/EditTag/" . $tag->id);
        
        $title = "<a href='$edit_url' onclick='return admin_redirect(this)'>" . $tag->title . "</a>";
        
        $menu = Admin::IconB("icon-edit.png", "ajax:" . $edit_url, t("Edit"));
        $menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "tags_delete($tag->id)");
        
        $res = array(
            "title" => array("content" => $title, "value" => $tag->title),
			"active" => Admin::VisibilityBar($tag->visible, "tags_active('$tag->id')"),
            "modified" => \Sloway\utils::modified($tag->edit_time, $tag->edit_user),
            "menu" => $menu
        );
        
        return $res;
    } 
   
    protected function lists_model() {
		$model = array(
			array(
			   "id" => "id", 
			   "width" => "content", 
			   "content" => t("ID"),
			   "sort" => true
			),
			array(
			   "id" => "title", 
			   "width" => "content", 
			   "edit_grip" => "div", 
			   "edit_click" => false,
			   "content" => t("Title"),
			   "sort" => true
			),
			array(
				"id" => "products", 
				"width" => "content", 
				"align" => "left",
				"content" => t("Products"),
			),
			array(
				"id" => "modified", 
				"width" => "content", 
				"align" => "left",
				"content" => t("Modified"),
				"can_hide" => false 
			),
			array(
				"id" => "menu", 
				"width" => "80", 
				"align" => "right",
				"fixed" => true,
			)
		);
        
        return $model;
    }
    protected function lists_row($list) {
        $edit_url = url::site("AdminCatalog/ListItems/" . $list->id);
        $title = "<a href='$edit_url' onclick='return admin_redirect(this)'>" . $list->title . "</a>";
		$menu = "";

		if (!$list->locked) {
			$menu.= Admin::IconB("icon-edit.png", false, t("Edit"), "list_edit($list->id)");
			$menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "list_delete($list->id)");
		}

		$q = $this->db->query("SELECT COUNT(id) AS count FROM catalog_list WHERE id_parent = '$list->id'")->getResult();
        $res = array(
			"id" => $list->id,
            "title" => array("content" => $title, "value" => $list->title),
			"products" => $q[0]->count . " " . t("items"),
            "modified" => \Sloway\utils::modified($list->edit_time, $list->edit_user),
            "menu" => $menu
        );
        
        return $res;
    } 

	protected function listitems_model() {
		$model = array(
			array(
			   "id" => "title", 
			   "width" => "content", 
			   "content" => t("Title"),
			   "sort" => true
			),
			array(
				"id" => "code", 
				"width" => "content", 
				"align" => "left",
				"content" => t("Code"),
			),
			array(
				"id" => "tags", 
				"width" => "content", 
				"align" => "left",
				"content" => t("Tags"),
			),
			array(
				"id" => "categories", 
				"width" => "content", 
				"align" => "left",
				"max_width" => 250,
				"content" => t("Categories"),
			),
			array(
				"id" => "properties", 
				"width" => "content", 
				"align" => "left",
				"max_width" => 250,
				"content" => t("Properties"),
			),
			array(
				"id" => "modified", 
				"width" => "content", 
				"align" => "left",
				"content" => t("Modified"),
				"can_hide" => false 
			),
			array(
				"id" => "menu", 
				"width" => "40", 
				"align" => "right",
				"fixed" => true,
			)
		);
		return $model;
	}
	protected function listitems_row($item, $list) {
        $menu = (!$list->locked) ? Admin::IconB("icon-delete.png", false, t("Remove"), "list_remove($item->id)") : "";
		$res = array(
			"title" => $item->title,
			"code" => $item->code,
			"tags" => $this->serialize_tags($item->tags),
			"categories" => $this->serialize_categories($item->categories),
			"properties" => $this->serialize_properties($item->properties),
            "modified" => \Sloway\utils::modified($item->edit_time, $item->edit_user),
            "menu" => $menu
		);

		return $res;
	}
 
    protected function edit_item($id) {
        $this->item = dbModel::load('catalog_item', "@id = $id", 1, null, null, "*");
		
		$this->default_title = $this->gen_item_titles($this->item, false);
		$this->custom_title_chk = \Sloway\flags::get($this->item->flags, "ct");

        $this->group = mlClass::load('catalog_product', "@id = {$this->item->id_parent}", 1);
    }
    protected function edit_group($id) {
        $this->categories = dbModel::load("adm_catalog_category"); 
        $this->product = dbModel::load("catalog_product", "@id = '$id'", 1, null, null, "*");
        $this->properties = dbModel::load("catalog_property");
    }
    protected function edit_bundle($id) {
        $this->bundle = dbModel::load('catalog_product', "@id = $id", 1);
        
        $this->categories = dbModel::load('adm_catalog_category' . $this->categories_flags);
        
        $this->nodes = array();
        foreach ($this->bundle->slots as $slot) {
            $sub = array();
            foreach ($slot->items as $item) {
                $item->title = $item->title();
                $item->slot_price = $slot->item_price($item->id, "static");
                $sub[]= Admin::EditTree_Node("item", $item);
            }
            
            $this->nodes[]= Admin::EditTree_Node("slot", $slot, $sub);
        }          
    }    
    protected function edit_property($id) {
        $this->property = dbModel::load("catalog_property", "@id = $id", 1, null, null, "*");
		$this->flt_templates = null;            
		$this->sel_templates = null;            
        
        if (!$this->property->id_parent) {
            $flt_templates = array("none" => et("None"));
            foreach (config::get("catalog.filter_template") as $name)
                $flt_templates[$name]= t("catalog_ft_" . $name);

            $sel_templates = array("none" => et("None"));
            foreach (config::get("catalog.selector_template") as $name)
                $sel_templates[$name]= t("catalog_st_" . $name);
            
            $this->flt_templates = $flt_templates;            
            $this->sel_templates = $sel_templates;            
            if (!$this->property->filter_template) $this->property->filter_template = "none";            
            if (!$this->property->selector_template) $this->property->selector_template = "none";            
        }
    }               
    protected function edit_discount($id) {
        $this->categories = dbModel::load("adm_catalog_category"); 
		$this->tags = mlClass::load("catalog_tag");
        $this->discount = mlClass::load("catalog_discount", "@id = $id", 1, null, "*");
        $this->products = ($this->discount->products) ? mlClass::load("catalog_product", "@id IN (" . $this->discount->products . ") ORDER BY title ASC") : array();
    }   
    protected function edit_category($id) {
        $this->users = arrays::regen(dbClass::load("admin_user"), 'id', 'username');
        $this->cat = mlClass::load("catalog_category", "@id = $id", 1, null, "*");
		$this->cat->images = images::load("catalog_category", $this->cat->id);
    }            
    protected function edit_tag($id) {
        $this->tag = dbModel::load("catalog_tag", "@id = '$id'", 1, null,null, "*");
    }   
                         
    protected function save_slots($id, $hid = 0) {
        $pslots = $this->input->post("slots", array());  
        $pslots_delete = $this->input->post("slots_delete_slot",array());
        if (count($pslots_delete))
            $this->db->query("DELETE FROM catalog_slot WHERE id IN (" . implode(",",$pslots_delete) . ")");
            
        $slot_props = config::get("catalog.slot_properties");
        foreach ($pslots as $i => $pslot) {
            $slot = mlClass::create("catalog_slot", v($pslot, "id", 0));
            $slot->id_item = $id;
            $slot->id_order = $i;
            $slot->title = v($pslot, "title");
            
            $ids = array();
            $props = array();
            
            foreach (v($pslot, "items", array()) as $item) {
                if ($iid = v($item, "item_id", 0))
                    $ids[] = $iid;
                
                if (!isset($props[$iid]))
                    $props[$iid] = array();
                
                foreach ($slot_props as $name)
                    $props[$iid][$name] = v($item, $name, "");
            }
            
            $slot->items = implode(",", $ids);
            $slot->properties = json_encode($props);
            $slot->save($hid);
        }    
    }                    
    protected function save_group($id) {
        $r = mlClass::post('catalog_product', $id);
		$r->set("price", utils::encode_price($r->get_ml("price")));
		$r->set("price_action", utils::encode_price($r->get_ml("price_action")));
		foreach ($r->get_ml("title") as $lang => $title) {
			if (!strlen(trim($title)))
				return et("Title cannot be empty");
		}
		
        $r->save();
		
		Admin::GenerateUrls("product", $r);
		$r->save();
		
        Admin::ImageList_Save("images", "catalog_product", $id);
        Admin::FileList_Save("files", "catalog_product", $id);
		
		catalog::regen_items("SELECT * FROM catalog_product WHERE type = 'item' AND id_parent = '$id'");
    }
    protected function save_item($id) {
        $r = mlClass::post('catalog_product', $id);
        $r->type = 'item';
		$r->set("price", utils::encode_price($r->get_ml("price")));
		$r->set("price_action", utils::encode_price($r->get_ml("price_action")));
		$r->flags = "";
		
		$def_title = $this->gen_item_titles($r);
		if ($this->input->post("custom_title_chk") && !empty($r->get("title"))) {
			$r->flags = "ct";
		} else {
			$r->set("title", $def_title);
		}
        $r->save();
        
        Admin::ImageList_Save('images', 'catalog_product', $id);
        
        return $r;
    }
    protected function save_bundle($id) {
        $r = \Sloway\catalog_product::post('catalog_product', $id);
        $r->price = $this->save_price($r->price);

		if (config::get("admin.generate_url.product"))
			$r->url = Admin::GenerateUrl($this->db, "product-" . $id, $r->meta_title ?: $r->title);

        $hid = $r->save(true);
        
        $this->save_slots($id, $hid);        
        
        Admin::ImageList_Save('images', 'catalog_product', $id, $hid);
        
        return $r;
    }
    protected function save_tag($id) {
        $r = mlClass::post('catalog_tag', $id);
        $r->save();
        
        Admin::ImageList_Save('images', 'catalog_tag', $id);
        
        return $r;
    }    

    protected function save_category($id) {
        $r = mlClass::post('catalog_category', $id);
		foreach ($r->get_ml("title") as $lang => $title) {
			if (!strlen(trim($title)))
				return et("Title cannot be empty");
		}
		
        $r->save();

		Admin::GenerateUrls("category", $r); 
		$r->save();
		
        Admin::ImageList_Save('images', 'catalog_category', $r->id);
    }
    protected function save_property($id) {
        $r = mlClass::post('catalog_property', $id);
        if ($r->filter_template == "none") $r->filter_template = "";
        if ($r->selector_template == "none") $r->selector_template = "";
        $r->save();
        Admin::ImageList_Save('images', 'catalog_property', $r->id);
		
		catalog::regen_items("SELECT * FROM catalog_product WHERE type = 'item' AND properties REGEXP '[[:<:]]($id)[[:>:]]'");
    }    
    protected function save_discount($id) {
        $date_from = $this->input->post("date_from");
        $date_to = $this->input->post("date_to");
        
        $time_from = strtotime($date_from);
        $time_to = strtotime($date_to);
        
        $r = mlClass::post("catalog_discount", $id);
        $r->time_from = $time_from;
        $r->time_to = $time_to;
        $r->date_from = \Sloway\utils::mysql_datetime($time_from);
        $r->date_to = \Sloway\utils::mysql_datetime($time_to);
        $r->save();
    }        
    
    protected function stock_manager_info($entry) {
        if (strpos($entry->status, "order_") === 0) {
            $st = str_replace("order_", "", $entry->status);
            return et("sm_order_status_" . $st) . " (" . $entry->reference . ")";
        } else
        switch ($entry->status) {
            case "pending": 
                return et("Awaiting authorization") . " ($entry->reference)";
            case "in_cart":
                return et("In shopping cart");
            case "create":
                return et("Product created by user") . " '$entry->reference'";
            case "update":
                return et("Stock updated by user") . " '$entry->reference'";
            case "sync":
                return et("Stock updated by system");
            case "reset":
                return et("Stock reset by user") . " '$entry->reference'";                
        }
        return "";
    }
    
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
	{
		parent::initController($request, $response, $logger);
		
        $this->categories_flags = (!Admin::auth("catalog.categories.view_all")) ? "?user=" . $this->admin_user->id : "";
        $this->catalog_types = config::get("catalog.types");
        $this->stock_manager = config::get("catalog.stock.manager", false);
		$this->tax_rates = array();
		foreach (config::get("catalog.tax_rates") as $val => $title) {
			$this->tax_rates[number_format($val, 3)] = $title;
		}
		
		$this->icons = array(
			"product" => \Sloway\utils::icon("icon-product.png", "Catalog"),
			"bundle" => \Sloway\utils::icon("icon-bundle.png", "Catalog"), 
			"group" => \Sloway\utils::icon("icon-group.png", "Catalog"),
			"item" => \Sloway\utils::icon("icon-item.png", "Catalog"),
		);
		
		$this->display = true;
		catalog::$db = $this->db;
		catalog::$mode_mask = "<span class='catalog_(mode)'>(value)</span>";

		$this->list_config = array(
			array('title' => et('Title'), 'sort' => 'title', 'callback' => '_listTitle'),
			array('title' => "<span style='padding-left: 5px'>" . et('Order') . "</span>", 'width' => 60, 'sort' => 'sequence', 'callback' => '_listSequence'),
			array('title' => "<span style='padding-left: 5px'>" . et('Price') . "</span>", 'width' => 60, 'callback' => '_listPrice'),
			array('title' => "<span style='padding-left: 5px'>" . et('Stock') . "</span>", 'width' => 60, 'callback' => '_listStock'),
			array('title' => et('Last modified'), 'width' => 100, 'sort' => 'mod', 'callback' => '_listModified'),
			array('title' => "V", 'width' => 20, 'callback' => '_listVisibility'),
		);                  
		
		$this->module_tabs = array(
            'catalog'    => "<a href='" . url::site("AdminCatalog") . "' onclick='return admin_redirect(this)'>" . et('Articles') . "</a>",
            'categories' => "<a href='" . url::site("AdminCatalog/Categories") . "' onclick='return admin_redirect(this)'>" . et('Categories') . "</a>",
            'properties' => "<a href='" . url::site("AdminCatalog/Properties") . "' onclick='return admin_redirect(this)'>" . et('Properties') . "</a>",
            'discounts'  => "<a href='" . url::site("AdminCatalog/Discounts") . "' onclick='return admin_redirect(this)'>" . et('Discounts') . "</a>",
            'tags'       => "<a href='" . url::site("AdminCatalog/Tags") . "' onclick='return admin_redirect(this)'>" . et('Tags') . "</a>",
			'lists'		 => "<a href='" . url::site("AdminCatalog/Lists") . "' onclick='return admin_redirect(this)'>" . et('Lists') . "</a>",
        );
        
        if (!Admin::auth("catalog.products")) unset($this->module_tabs['catalog']);
        if (!Admin::auth("catalog.categories")) unset($this->module_tabs['categories']);
        if (!Admin::auth("catalog.properties")) unset($this->module_tabs['properties']);
        if (!Admin::auth("catalog.discounts") || !config::get("catalog.discounts")) unset($this->module_tabs['discounts']);
        if (!Admin::auth("catalog.tags") || !config::get("catalog.tags")) unset($this->module_tabs['tags']);
        if (!Admin::auth("catalog.lists") || !config::get("catalog.lists")) unset($this->module_tabs['lists']);
        
        $this->tree_treshold = config::get("catalog.tree_treshold");
		$this->module_wide = true;
		
		$this->_lists = dbClass::load("catalog_list", "@id_parent = 0", 0, array("index" => "id"));
		$this->build_categories();
	}

	public function Index($mode = "0", $pid = 0) {
        Admin::auth("catalog", $this, true);
		
		$this->categories = dbModel::load('adm_catalog_category' . $this->categories_flags); 
        $this->filter = userdata::get_object("catalog_catalog_", array("search", "search_mode", "cats", "tags", "types"));
		
        $this->model = $this->catalog_model();
        $this->mode = $mode;
        $this->pid = $pid;
        $this->product = ($pid) ? dbClass::load("catalog_product", "@id = $pid", 1) : 0;
        
        $this->filter_cats_tree = acontrol::tree_items($this->categories, "subcat");
        $this->filter_cats_tree[et("Uncategorized") . "{id=none}"] = array();
        $this->filter_tags_list = config::get("catalog.tags") ? arrays::regen(mlClass::load("catalog_tag", "* ORDER BY title ASC"), "id", "title", true) : array();
    	
        $this->module_menu = view("\Sloway\Views\AdminCatalog\Toolbar", array(
			"filter" => $this->filter,
			"filter_cats_tree" => $this->filter_cats_tree,
			"filter_tags_list" => $this->filter_tags_list,
		));

        $this->module_content = Admin::tabs($this->module_tabs, "catalog", view("\Sloway\Views\AdminCatalog\Catalog", array(
			"dg_model" => $this->model,
			"mode" => $this->mode,
			"product_id" => $this->pid,
			"product" => $this->product,
			"types" => $this->catalog_types,
		)));
		
		return $this->admin_view();
	}  
	public function Categories() {
        Admin::auth("catalog.categories", $this);

		$this->module_path = array(et('Categories') => '');
        $this->module_content = Admin::tabs($this->module_tabs, "categories", view("\Sloway\Views\AdminCatalog\Categories", array(
			"dg_model" => $this->categories_model(),
		)));
		
		return $this->admin_view();
	}
    public function Properties($pid = 0) {
        Admin::auth("catalog.properties", $this);
        $this->module_path = array(et('Properties') => '');
		
		if (!$pid) {
			$this->filter_search = userdata::get("properties_search");   
			$this->module_menu = view("\Sloway\Views\AdminCatalog\PropertiesToolbar", array(
				"filter_search" => $this->filter_search,
			));
		}

        $this->pid = $pid;
        $this->property = ($pid) ? mlClass::load("catalog_property", "@id = $pid", 1) : null;

        $this->module_content = Admin::tabs($this->module_tabs, "properties", view("\Sloway\Views\AdminCatalog\Properties", array(
			"dg_model" => $this->properties_model(),
			"property_id" => $this->pid,
			"property" => $this->property,
		)));
		
		return $this->admin_view();
    }
    public function Discounts() {
        Admin::auth("catalog.discounts", $this);
        if (!config::get("catalog.discounts")) $this->deny_access = true;

        $this->filter = userdata::get_object("catalog_discounts_", array("from", "to", "search"));

        $this->module_path = array(et('Discounts') => '');
        $this->module_menu = view("\Sloway\Views\AdminCatalog\DiscountsMenu", array(
			"filter" => $this->filter,
		));
        $this->module_content = Admin::tabs($this->module_tabs, "discounts", view("\Sloway\Views\AdminCatalog\Discounts", array(
			"dg_model" => $this->discounts_model(),
		)));
		
		return $this->admin_view();
    }    
    public function Tags() {
        Admin::auth("catalog.tags", $this);

		$this->module_menu = view("\Sloway\Views\AdminCatalog\TagsToolbar", array(
			"filter_search" => userdata::get("tags_search")
		));
      
        $this->module_path = array(et('Tags') => '');
        $this->module_content = Admin::tabs($this->module_tabs, "tags", view("\Sloway\Views\AdminCatalog\Tags", array(
			"dg_model" => $this->tags_model(),
		)));

		return $this->admin_view();
    }       
    public function Lists() {
        Admin::auth("catalog.lists", $this);

		$this->module_menu = view("\Sloway\Views\AdminCatalog\ListsToolbar", array(
			"filter_search" => userdata::get("lists_search")
		));

		$this->module_path = array(et('Lists') => '');
		$this->module_content = Admin::tabs($this->module_tabs, "lists", view("\Sloway\Views\AdminCatalog\Lists", array(
			"list" => null,
			"dg_model" => $this->lists_model(),
		)));

		return $this->admin_view();
    }       
    public function ListItems($id) {
        Admin::auth("catalog.lists", $this);

		$this->categories = dbModel::load("adm_catalog_category"); 
		$this->filter = userdata::get_object("catalog_listitems_", array("search", "cats", "tags", "types"));
        $this->filter_cats_tree = acontrol::tree_items($this->categories, "subcat");
        $this->filter_cats_tree[et("Uncategorized") . "{id=none}"] = array();
        $this->filter_tags_list = config::get("catalog.tags") ? arrays::regen(mlClass::load("catalog_tag", "* ORDER BY title ASC"), "id", "title", true) : array();

        $this->module_menu = view("\Sloway\Views\AdminCatalog\ListItemsToolbar", array(
			"filter" => $this->filter,
			"filter_cats_tree" => $this->filter_cats_tree,
			"filter_tags_list" => $this->filter_tags_list,
		));

		$list = dbClass::load("catalog_list", "@id = '$id'", 1);

		$footer = Admin::ButtonI("icon-delete.png", null, t('Remove'), "list_remove_checked()");

		$this->module_path = array(et('Lists') => url::site("AdminCatalog/Lists"), $list->title => '');
		$this->module_content = Admin::tabs($this->module_tabs, "lists", view("\Sloway\Views\AdminCatalog\ListItems", array(
			"list" => $list,
			"dg_model" => $this->listitems_model(),
			"dg_footer" => $footer,
		)));

		return $this->admin_view();
    }      

    public function Ajax_CategoriesHandler() {
        if ($id = $this->input->post("delete")) {
            dbModel::delete("catalog_category", "@id = '$id'");
			$this->build_categories();
		}
        
        if ($cid = $this->input->post("reorder")) {
            $pid = $this->input->post("parent");
            $index = $this->input->post("index");   
			
			catalog::move_category($cid, $pid, $index);
			$this->build_categories();
        }    
	
        $sort = $this->input->post("sort", "title");
        $sort_dir = $this->input->post("sort_dir", 1);
        
        $root_id = $this->input->post("root_id", 0);    
        $loaded = $this->input->post("loaded", array());    
        $result = array(
            "rows" => $this->categories_load($root_id, $loaded, $sort, $sort_dir)
        );    
        
        echo json_encode($result);           
    }
    public function Ajax_CategoriesAdd($pid) {
        $title = trim($this->input->post("title"));
		if ($title && ($this->input->post('create') || $this->input->post('create_edit'))) {
            $q = $this->db->query("SELECT MAX(id_order) as max FROM catalog_category WHERE id_parent = ?", [$pid])->getResult();
            $id_order = count($q) ? $q[0]->max + 1 : 0;
            
            $r = mlClass::create("catalog_category");
            $r->set("title", $title, "_all");
            $r->id_order = $id_order;
            $r->id_parent = $pid;
            $r->save();

			Admin::GenerateUrls("category", $r);
			$r->save();
            
            $res['close'] = true;
            $res['result'] = $this->input->post('create_edit') ? url::site("AdminCatalog/EditCategory/" . $r->id) : null;
            echo json_encode($res);
            
            exit;
        } 
        
        $res['title'] = et("Add category");
        $res['content'] = Admin::Field(et("Title"), acontrol::edit("title"));
		$res['buttons'] = array(
			"create" => array("title" => "Create", "submit" => true, "key" => 13), 
			"create_edit" => array("title" => "Create and edit", "submit" => true),
			"cancel"
		);
		echo json_encode($res);                
    }
    public function Ajax_CategoryFlags($id) {
        $category = dbClass::load("catalog_category", "@id = " . $id, 1);
        if ($this->input->post("save")) {
            $category->flags = $this->input->post("category_flags");
            $category->save();
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            exit();
        }   
        
        $flags = array();
        foreach (config::get("catalog.categories.flags", array()) as $flag) 
            $flags[] = et("admin_catalog_" . $flag) . "{id=$flag}";
            
        $c = acontrol::checktree('category_flags', $flags, $category->flags, array("class" => "category_flags"));
        
        $res = new \stdClass();
        $res->title = et("Flags");
        $res->content = $c;
        $res->buttons = array("save" => array("align" => "left", "title" => t("Save"), "submit" => true), "cancel");
        
        echo json_encode($res);
    }
    public function Ajax_CategoryUsers($id) {
        $category = dbClass::load("catalog_category", "@id = " . $id, 1);
        if ($this->input->post("save")) {
            $users = $this->input->post("users");
            $category->users = $users;
            $category->save();

            /*            
            while ($category->id_parent) {
                $category = dbClass::load("catalog_category", "@id = " . $category->id_parent, 1);    
                
                $cat_users = trim($category->users . "," . $users, ",");
                $category->users_path = implode(",", array_unique(explode(",", $cat_users)));
                $category->save();
            } */
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            exit();
        }   
        
        $this->users = arrays::regen(dbClass::load("admin_user", "@id_role != 0"), 'id', 'username');
        
		$res = new \stdClass();
        $res->title = et("Users");
        $res->content = Admin::Field(et("Users"), acontrol::checklist("users", $this->users, $category->users));
        $res->buttons = array("save" => array("align" => "left", "title" => t("Save"), "submit" => true), "cancel");
        
        echo json_encode($res);    
    }
    public function Ajax_CategoryCellEdit() {
        $this->auto_render = false;
        
        $x = $this->input->post("x");
        $y = $this->input->post("y");
        $id = $this->input->post("id");
        $name = $this->input->post("name");
        $value = $this->input->post("value");

        $cat = mlClass::load("catalog_category", "@id = " . $id, 1);
        $cat->$name = $value;
        $cat->save();
        
        $row = $this->categories_row($cat);
        
        $res = array();
        $res["content"] = $row[$name]["content"];
        $res["x"] = $x;
        $res["y"] = $y;
        
        echo json_encode($res);
    }      
    
    public function Ajax_PropertiesHandler($pid = 0) {
        $this->auto_render = false;
        
        if ($delete = $this->input->post("delete")) {
            dbModel::delete("catalog_property", "@id = " . $delete);
			catalog::regen_items("SELECT * FROM catalog_product WHERE type = 'item' AND properties REGEXP '[[:<:]]($delete)[[:>:]]'");
		}
            
        if ($this->input->post("apply_filter")) {
            userdata::set("properties_search", trim($this->input->post("filter_search")));
        }
		
		$this->build_properties();
        
        $filter_search = userdata::get("properties_search", null);
        
        $page = $this->input->post("page", 1) - 1;
        $perpage = $this->input->post("per_page", 20);        
        $sort = $this->input->post("sort", "title");
        $sort_dir = $this->input->post("sort_dir", 1);
        
        $root_id = $pid;
        if (isset($_POST["root_id"]))
            $root_id = $_POST["root_id"];
            
		$sql_add = "";
		if (!$root_id && $filter_search) 
			$sql_add = " AND title LIKE '%$filter_search%'";

        $q = $this->db->query("SELECT COUNT(id) as count FROM `catalog_property` WHERE id_parent = ?" . $sql_add, [$pid])->getResult();
        $count = $q[0]->count;
        
        if ($page * $perpage >= $count)
            $page = 0;
        
        $loaded = $this->input->post("loaded", array());    
        $result = array(
            "state" => array(
                "page" => $page + 1,
                "total" => $count,   
            ),
			"debug" => $sql_add,
            "rows" => $this->properties_load($root_id, $sql_add, $loaded, $page, $perpage, $sort, $sort_dir)
        );    
        
        echo json_encode($result);           
    }
    public function Ajax_PropertiesAdd($pid) {
        $title = trim($this->input->post("title"));
		if ($title && ($this->input->post('create') || $this->input->post('create_edit'))) {
            $q = $this->db->query("SELECT MAX(id_order) as max FROM catalog_property WHERE id_parent = ?", [$pid])->getResult();
            $id_order = count($q) ? $q[0]->max + 1 : 0;
            
            $r = mlClass::post("catalog_property");
            $r->id_order = $id_order;
            $r->id_parent = $pid;
			$r->set("title", $title, "_all");
            $r->value = $this->input->post("value");
            $r->save();
            
            $res['close'] = true;
            $res['result'] = $this->input->post('create_edit') ? url::site("AdminCatalog/EditProperty/" . $r->id) : null;
            echo json_encode($res);
            
            exit;
        } 
        
        if ($pid) {
            $res["title"] = et("Add property value");
            $res["content"] = Admin::Field(et("Title"), acontrol::edit("title")) . Admin::Field(et("Value"), acontrol::edit("value"));
        } else {
            $res["title"] = et("Add property");
            $res["content"] = Admin::Field(et("Title"), acontrol::edit("title"));
        }
		$res['buttons'] = array(
			"create" => array("title" => "Create", "submit" => true, "key" => 13), 
			"create_edit" => array("title" => "Create and edit", "submit" => true),
			"cancel"
		);
        echo json_encode($res);                
    }
    public function Ajax_PropertyCellEdit() {
        $this->auto_render = false;
        
        $x = $this->input->post("x");
        $y = $this->input->post("y");
        $id = $this->input->post("id");
        $name = $this->input->post("name");
        $value = $this->input->post("value");

        $cat = mlClass::load("catalog_property", "@id = " . $id, 1);
        $cat->$name = $value;
        $cat->save();
        
        $row = $this->properties_row($cat);
        
        $res = array();
        $res["content"] = $row[$name]["content"];
        $res["x"] = $x;
        $res["y"] = $y;
        
        echo json_encode($res);
    }      
    public function Ajax_PropertyFlags($id) {
        $this->auto_render = false;
        
        $property = mlClass::load("catalog_property", "@id = " . $id, 1);
        if ($this->input->post("save")) {
            $property->flags = $this->input->post("property_flags");
            $property->save();
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            exit();
        }   
        
        $flags = array();
        $cfg = ($property->id_parent) ? "catalog.property_value_flags" : "catalog.property_flags";
        $cfg = config::get($cfg);
        if (is_array($cfg)) {
            foreach ($cfg as $flag) 
                $flags[] = et("admin_catalog_" . $flag) . "{id=$flag}";
        }
            
        $c = acontrol::checktree("property_flags", $flags, $property->flags, array("class" => "property_flags"));
        
        $res = new \stdClass();
        $res->title = et("Flags");
        $res->content = $c;
        $res->buttons = array("save" => array("align" => "left", "title" => t("Save"), "submit" => true), "cancel");
        
        echo json_encode($res);
    }
    public function Ajax_PropertyTemplate($temp, $id) {
        $this->auto_render = false;
        $this->edit_property($id);    
    
        $property = $this->property;
        if ($this->input->post("save")) {
            $property->$temp = $this->input->post($temp);
            if ($property->filter_template == "none") $property->filter_template = "";
            if ($property->selector_template == "none") $property->selector_template = "";
            $property->save();
			
			catalog::regen_items("SELECT * FROM catalog_product WHERE type = 'item' AND properties REGEXP '[[:<:]]($id)[[:>:]]'");
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            exit();
        }   
        
        $list = ($temp == "filter_template") ? $this->flt_templates : $this->sel_templates;
        $c = acontrol::select($temp, $list, $property->$temp); 
        
        $res = new \stdClass();
        $res->title = et("Edit template");
        $res->content = $c;
        $res->buttons = array("save" => array("align" => "left", "title" => t("Save"), "submit" => true), "cancel");
        
        echo json_encode($res);
    }

    public function Ajax_DiscountsHandler() {
        if ($delete = $this->input->post("delete")) 
            $this->db->query("DELETE FROM catalog_discount WHERE id = ?", $delete);    
            
        if ($this->input->post("filter")) {
            $filter = new \stdClass();
            $filter->search = trim($this->input->post("filter_search"));
            $filter->from = strtotime($this->input->post("filter_from"));
            $filter->to = strtotime($this->input->post("filter_to"));
            $filter->cats = $this->input->post("filter_cats");
            
            userdata::set_object("catalog_discounts_", $filter);        
        } else 
            $filter = userdata::get_object("catalog_discounts_", array("search", "from", "to",  "cats"));            
        
        $page = $this->input->post("page", 1) - 1;
        $perpage = $this->input->post("per_page", 20);        
        $sort = $this->input->post("sort", "title");
        $sort_dir = $this->input->post("sort_dir", 1);
        
        $sql = array();
        if ($filter->cats)
            $sql[]= "categories REGEXP '[[:<:]]($filter->cats)[[:>:]]'";
        if ($filter->search)
            $sql[]= "title LIKE '%$filter->search%'";
        if ($from = $filter->from)
            $sql[]= "(date_from = 0 OR time_to >= $from)";
        if ($to = $filter->to)
            $sql[]= "(date_to = 0 OR time_from <= $to)";
            
        $where = count($sql) ? " WHERE " . implode(" AND ", $sql) : "";        
        
        $q = $this->db->query("SELECT COUNT(id) as count FROM `catalog_discount`" . $where)->getResult();
        $count = $q[0]->count;
        
        if ($page * $perpage >= $count)
            $page = 0;
            
        $rows = array();
        $order = " ORDER BY $sort " . (($sort_dir > 0) ? 'ASC' : 'DESC'); 
        
        $limit = "";
        if ($perpage) {
            $start = $page * $perpage;
            $limit.= " LIMIT $start,$perpage";
        }
        $discounts = mlClass::load("catalog_discount", "SELECT * FROM catalog_discount " . $where . $order . $limit);
        
        $rows = array();
        foreach ($discounts as $discount) {
            $row = array(
                "id" => $discount->id,
                "cells" => array_values($this->discounts_row($discount)),
                "rows" => array()
            );
            $rows[] = $row;            
        } 
            
        $result = array(
            "state" => array(
                "page" => $page + 1,
                "total" => $count,   
            ),
            "rows" => $rows
        );    
        
        echo json_encode($result);           
    }
    public function Ajax_DiscountsAdd() {
        $title = $this->input->post("title");
        if ($this->input->post("create") && !empty($title)) {
            $r = dbClass::create("catalog_discount");
            $r->title = $title;
            $r->save();
            
            $res['close'] = true;
            $res['result'] = url::site("AdminCatalog/EditDiscount/" . $r->id);
            echo json_encode($res);
            
            exit;
        } 
        
        $res["title"] = et("Add discount");
        $res["content"] = Admin::Field(et("Title"), acontrol::edit("title"));
        $res["buttons"] = array("create" => array("title" => "OK", "submit" => true, "key" => 13), "cancel");
        
        echo json_encode($res);                
    }    
    
    public function Ajax_TagsHandler() {
        if ($delete = $this->input->post("delete")) 
            $this->db->query("DELETE FROM catalog_tag WHERE id = ?", [$delete]);    
            
        $page = $this->input->post("page", 1) - 1;
        $perpage = $this->input->post("per_page", 20);        
        $sort = $this->input->post("sort", "title");
        $sort_dir = $this->input->post("sort_dir", 1);
            
        $q = $this->db->query("SELECT COUNT(id) as count FROM `catalog_tag`")->getResult();
        $count = $q[0]->count;
        
        if ($page * $perpage >= $count)
            $page = 0;
            
        $rows = array();
        $order = " ORDER BY $sort " . (($sort_dir > 0) ? 'ASC' : 'DESC'); 
        
        $limit = "";
        if ($perpage) {
            $start = $page * $perpage;
            $limit.= " LIMIT $start,$perpage";
        }
		$this->build_tags();
        $tags = mlClass::load("adm_catalog_tags", "SELECT * FROM adm_catalog_tag " . $order . $limit);
        
        $rows = array();
        foreach ($tags as $tag) {
            $row = array(
                "id" => $tag->id,
                "cells" => array_values($this->tags_row($tag)),
                "rows" => array()
            );
            $rows[] = $row;            
        } 
            
        $result = array(
            "state" => array(
                "page" => $page + 1,
                "total" => $count,   
            ),
            "rows" => $rows
        );    
        
        echo json_encode($result);           
    }
    public function Ajax_TagsAdd() {
        $this->auto_render = false;
        
        $title = $this->input->post("title");
        if ($this->input->post("create") && !empty($title)) {
            $r = mlClass::create("catalog_tag");
            $r->set("title", $title, "_all");
            $r->save();
            
            $res['close'] = true;
            $res['result'] = url::site("AdminCatalog/EditTag/" . $r->id);
            echo json_encode($res);
            
            exit;
        } 
        
        $res["title"] = et("Add tag");
        $res["content"] = Admin::Field(et("Title"), acontrol::edit("title"));
        $res["buttons"] = array("create" => array("title" => "OK", "submit" => true, "key" => 13), "cancel");
        
        echo json_encode($res);                
    }  
    public function Ajax_TagsToggle() {
        $this->auto_render = false;
        
        $modes = array(
            "add" => et("Add selected tags"),
            "set" => et("Reset to selected tags"),
            "rem" => et("Remove selected tags"),
        );
        $tags = dbModel::load("catalog_tag", "* ORDER BY title ASC");
        $tags_sel = $this->input->post("tags");
        $mode = $this->input->post("mode", "add");
        $checked = $this->input->post("checked");
        
		$filter = userdata::get_object("catalog_catalog_", array("search", "search_mode", "cats", "tags", "types"));
		$this->build_products($filter);
		
        if ($checked)
            $sql = array("id IN ($checked)"); 
            
        $sql = "SELECT COUNT(id) as count FROM `adm_catalog_product` WHERE id_parent = '0'";
		if ($checked)
			$sql.= " AND id IN ($checked)";
       
        $q = $this->db->query($sql)->getResult();
        $count = $q[0]->count;
        
        if ($this->input->post("ok")) {
            $sql = "SELECT id,tags FROM `adm_catalog_product` WHERE id_parent = 0";
            if ($checked)
                $sql.= " AND id IN ($checked)";
            
            $values = array();
            foreach ($this->db->query($sql)->getResult() as $prod) {
                $t = $prod->tags;

                switch ($mode) {
                    case "add": $t = \Sloway\flags::set($prod->tags, $tags_sel); break;
                    case "set": $t = $tags_sel; break;
                    case "rem": $t = \Sloway\flags::rem($prod->tags, $tags_sel); break;
                }
                $values[]= array("id" => $prod->id, "tags" => $t);
            }
            \Sloway\dbUtils::insert_update($this->db, "catalog_product", $values, true);
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            
            exit;
        }         
        
        $c = "";
        $c.= "<h2 class='admin_heading'>" . $count . " products selected</h2>";
        $c.= Admin::Field(et("Mode"), acontrol::select("mode", $modes, $mode));
        $c.= Admin::Field(et("Selected"), acontrol::checklist("tags", arrays::regen($tags, "id", "title", true)));
        $c.= "<input type='hidden' name='checked' value='$checked'>";
        
        $res["title"] = et("Toggle tags");
        $res["content"] = $c;
        $res["buttons"] = array("ok" => array("title" => "OK", "submit" => true, "key" => 13), "cancel");
        
        echo json_encode($res);      
    }

    public function Ajax_ListsHandler() {
        if ($delete = $this->input->post("delete")) {
			$this->db->query("DELETE FROM catalog_list WHERE id_parent = ?", [$delete]);
            $this->db->query("DELETE FROM catalog_list WHERE id = ?", [$delete]);    
		}
            
        $page = $this->input->post("page", 1) - 1;
        $perpage = $this->input->post("per_page", 20);        
        $sort = $this->input->post("sort", "title");
        $sort_dir = $this->input->post("sort_dir", 1);
            
        $q = $this->db->query("SELECT COUNT(id) as count FROM `catalog_list` WHERE id_parent = 0")->getResult();
        $count = $q[0]->count;
        
        if ($page * $perpage >= $count)
            $page = 0;
            
        $rows = array();
        $order = " ORDER BY $sort " . (($sort_dir > 0) ? 'ASC' : 'DESC'); 
        
        $limit = "";
        if ($perpage) {
            $start = $page * $perpage;
            $limit.= " LIMIT $start,$perpage";
        }
        $lists = mlClass::load("catalog_list", "SELECT * FROM catalog_list WHERE id_parent = 0 " . $order . $limit);
        
        $rows = array();
        foreach ($lists as $list) {
            $row = array(
                "id" => $list->id,
                "cells" => array_values($this->lists_row($list)),
                "rows" => array()
            );
            $rows[] = $row;            
        } 
            
        $result = array(
            "state" => array(
                "page" => $page + 1,
                "total" => $count,   
            ),
            "rows" => $rows
        );    
        
        echo json_encode($result);           
    }
    public function Ajax_ListsAdd() {
        $this->auto_render = false;
        
        $title = $this->input->post("title");
        if ($this->input->post("create") && !empty($title)) {
            $r = dbClass::create("catalog_list");
            $r->title = $title;
            $r->save();

			$r->key = $r->id;
			$r->save();
            
            $res['close'] = true;
            $res['result'] = url::site("AdminCatalog/EditList/" . $r->id);
            echo json_encode($res);
            
            exit;
        } 
        
        $res["title"] = et("Add list");
        $res["content"] = Admin::Field(et("Title"), acontrol::edit("title"));
        $res["buttons"] = array("create" => array("title" => "OK", "submit" => true, "key" => 13), "cancel");
        
        echo json_encode($res);                
    }  
    public function Ajax_ListsEdit($id) {
        $this->auto_render = false;

		$list = dbClass::load("catalog_list", "@id = '$id'", 1);
        
        $title = $this->input->post("title");
        if ($this->input->post("ok") && !empty($title)) {
            $list->title = $title;
            $list->save();
            
            $res['close'] = true;
            echo json_encode($res);
            
            exit;
        } 
        
        $res["title"] = et("Edit list");
        $res["content"] = Admin::Field(et("Title"), acontrol::edit("title", $list->title));
        $res["buttons"] = array("ok" => array("title" => "OK", "submit" => true, "key" => 13), "cancel");
        
        echo json_encode($res);                
    }  

    public function Ajax_ListItemsHandler($id) {
		if ($insert = $this->input->post("insert")) {
			$values = array();
			foreach ($insert as $cid) {
				$values[]= array("id_parent" => $id, "id_product" => $cid, "key" => $id . "-" . $cid);
			}
			dbUtils::insert_update($this->db, "catalog_list", $values, true);
		}		
		if ($remove = $this->input->post("remove")) {
			$this->db->query("DELETE FROM catalog_list WHERE id_parent = '$id' AND id_product IN ($remove)");
		}
		if ($ins_flt = $this->input->post("insert_flt")) {
			$flt = new \stdClass();
			$flt->search = $ins_flt["search"];
			$flt->search_mode = $ins_flt["search_mode"];
			$flt->cats = $ins_flt["categories"];
			$flt->tags = $ins_flt["tags"];

			$this->build_products($flt);
			$values = array();
			foreach ($this->db->query("SELECT id FROM adm_catalog_product WHERE id_parent = 0")->getResult() as $p) {
				$values[]= array("id_parent" => $id, "id_product" => $p->id, "key" => $id . "-" . $p->id);
			}
			dbUtils::insert_update($this->db, "catalog_list", $values, true);
		}
		if ($rem_flt = $this->input->post("remove_flt")) {
			$flt = new \stdClass();
			$flt->search = $rem_flt["search"];
			$flt->cats = $rem_flt["categories"];
			$flt->search_mode = $rem_flt["search_mode"];
			$flt->tags = $rem_flt["tags"];
			
			$this->build_products($flt);

			$sql = "DELETE FROM catalog_list WHERE id_parent = '$id' AND id_product IN (SELECT id FROM adm_catalog_product WHERE id_parent = 0)";
			$this->db->query($sql);
		}

        if ($this->input->post("apply_filter")) {
            $filter = new \stdClass();
            $filter->search = trim($this->input->post("filter_search"));
			$filter->search_mode = $this->input->post("filter_search_mode", "group");
            $filter->types = $this->input->post("filter_types");
            $filter->cats = $this->input->post("filter_cats");
            $filter->tags = $this->input->post("filter_tags");

            userdata::set_object("catalog_lists_", $filter);        
        } else 
            $filter = userdata::get_object("catalog_lists_", array("search", "search_mode", "cats", "tags", "types"));

		dbUtils::clone_table($this->db, "catalog_product", "adm_catalog_product_l", false);
		$this->db->query("INSERT INTO adm_catalog_product_l SELECT p.* FROM catalog_product AS p INNER JOIN catalog_list AS l ON l.id_parent = '$id' AND l.id_product = p.id");
		$this->build_products($filter, "adm_catalog_product_l");

		$model = $this->catalog_model();
		$list = dbClass::load("catalog_list", "@id = '$id'", 1);

		$page = $this->input->post("page", 1) - 1;
        $perpage = $this->input->post("per_page", 20);
        $sort = $this->input->post("sort", "title");
        $sort_dir = $this->input->post("sort_dir", 1);

		$sort = $this->check_sort($sort, $model);
        if ($sort == "modified")
            $order = " ORDER BY edit_time " . (($sort_dir > 0) ? 'ASC' : 'DESC'); else    
        if ($sort == "sort_num") {
            if ($sort_dir > 0)
                $order = "ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1 ASC"; else
                $order = "ORDER BY (CASE sort_num WHEN '' THEN '0' ELSE sort_num END)*1 DESC"; 
        } else 
            $order = " ORDER BY $sort " . (($sort_dir > 0) ? 'ASC' : 'DESC'); 
            
        $q = $this->db->query("SELECT COUNT(id) as count FROM adm_catalog_product")->getResult();
        $count = $q[0]->count;
        
        if ($page * $perpage >= $count)
            $page = 0;
            
        $rows = array();
        
        $limit = "";
        if ($perpage) {
            $start = $page * $perpage;
            $limit.= " LIMIT $start,$perpage";
        }
        $items = mlClass::load("catalog_product", "SELECT * FROM adm_catalog_product " . $order . $limit);
        
        $rows = array();
        foreach ($items as $item) {
            $row = array(
                "id" => $item->id,
                "cells" => array_values($this->listitems_row($item, $list)),
                "rows" => array()
            );
            $rows[] = $row;            
        } 
            
        $result = array(
            "state" => array(
                "page" => $page + 1,
                "total" => $count,   
            ),
            "rows" => $rows
        );    
        
        echo json_encode($result);           
    }

    public function Ajax_CatalogHandler($mode = "catalog", $archived = 0, $pid = 0) {
        //$this->profiler = new Profiler();
        $this->model = $this->catalog_model();
        $this->catalog_mode = $mode;

        if ($this->input->post("apply_filter")) {
            $filter = new \stdClass();
            $filter->search = trim($this->input->post("filter_search"));
			$filter->search_mode = $this->input->post("filter_search_mode", "group");
            $filter->types = $this->input->post("filter_types");
            $filter->cats = $this->input->post("filter_cats");
            $filter->tags = $this->input->post("filter_tags");

            userdata::set_object("catalog_" . $mode . "_", $filter);        
        } else 
            $filter = userdata::get_object("catalog_" . $mode . "_", array("search", "search_mode", "cats", "tags", "types"));
        
        $this->catalog_action();
		$this->build_products($filter);
                        
        if ($id = $this->input->post("root_id")) {
            $prod = dbModel::load('adm_catalog_product', "@id = $id", 1);
	        $result = array(
                "rows" => array(),
            );      
            foreach ($this->catalog_ch_sel($prod, $mode) as $ch_name) {
                $ch = v($prod, $ch_name, array());
                foreach ($ch as $item) {
                    $result["rows"][] = array(
                        "id" => $item->id,
                        "cells" => $this->catalog_row($item)
                    );
                }
            }
            
            exit(json_encode($result));
        }		
            
        $page = $this->input->post("page", 1) - 1;
        $perpage = $this->input->post("per_page", 20);
        $sort = $this->input->post("sort", "title");
        $sort_dir = $this->input->post("sort_dir", 1);
		
		$sort = $this->check_sort($sort, $this->model);
        if ($sort == "modified")
            $order = " ORDER BY edit_time " . (($sort_dir > 0) ? 'ASC' : 'DESC'); else    
        if ($sort == "sort_num") {
            if ($sort_dir > 0)
                $order = "ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1 ASC"; else
                $order = "ORDER BY (CASE sort_num WHEN '' THEN '0' ELSE sort_num END)*1 DESC"; 
        } else 
            $order = " ORDER BY $sort " . (($sort_dir > 0) ? 'ASC' : 'DESC'); 
		
        $q = $this->db->query("SELECT COUNT(id) as count FROM `adm_catalog_product` WHERE id_parent = '$pid'")->getResult();
        $count = $q[0]->count;
        
        if ($page * $perpage >= $count)
            $page = 0;

        $start = $page * $perpage;
        $items = dbModel::load("adm_catalog_product", "@id_parent = '$pid'" . $order . " LIMIT $start,$perpage");
        
        $loaded = $this->input->post("loaded");
        if (!is_array($loaded))
            $loaded = array();
            
        $level = $this->input->post("level", 1);     
        $result = array(
            "rows" => $this->catalog_load($items, $loaded, $filter->types, $level, $mode),  
            "state" => array(
                "page" => $page + 1,
                "total" => $count,   
            ),
        );  

        $this->auto_render = false;
        echo json_encode($result);        
    } 
    public function Ajax_Discounts($type, $id) {
        $this->auto_render = false;
        
        if ($type == "product") {
            $product = dbClass::load("catalog_product", "@id = '$id'", 1);
			$lng = mlClass::$lang;
            $sql = "SELECT * FROM catalog_discount WHERE visible REGEXP '[[:<:]](1|$lng)[[:>:]]' AND (";    
            $sql.= "categories REGEXP CONCAT('[[:<:]](', REPLACE(REPLACE('{$product->categories}','.','|'), ',','|') , ')[[:>:]]') OR products REGEXP '[[:<:]]{$product->id}[[:>:]]') AND "; 
            $sql.= "(time_from = 0 OR date_from < NOW()) AND (time_to = 0 OR date_to > NOW()) ORDER BY value DESC";
        } else {
            $sql = "SELECT * FROM catalog_discount WHERE visible REGEXP '[[:<:]](1|$lng)[[:>:]]' AND categories REGEXP '[[:<:]]{$id}[[:>:]]' AND ";    
            $sql.= "(time_from = 0 OR date_from < NOW()) AND (time_to = 0 OR date_to > NOW()) ORDER BY value DESC ASC";
        }
        
        $discounts = dbClass::load("catalog_discount", $sql);  
        
        $res = array();
        $res['title'] = et("Current discounts");
        $res['content'] = view("\Sloway\Views\AdminCatalog\DiscountList", array("discounts" => $discounts));
        $res['buttons'] = array(
            "close" => array("align" => "right", "title" => t("Close"), "result" => false)
        );
        
        echo json_encode($res);
    }  
	public function Ajax_NewProduct($type)  {
		$this->type = $type;
		
        $categories = dbModel::load('adm_catalog_category');
        
		$title = trim($this->input->post("title"));
		if ($title && ($this->input->post('create') || $this->input->post('create_edit'))) {
			$product = mlClass::post('catalog_product');
			$product->type = $type;
			$product->set("title", $title, "_all");
			$product->save();

			Admin::GenerateUrls("product", $product);
			$product->save();
            
            $stock = $this->input->post("stock");
            if (is_numeric($stock)) {
                catalog::update_stock(null, $product->id, $stock, null, "create", \Sloway\admin_user::name());
            }
            
            $edit_url = url::site("AdminCatalog/Edit" . ucfirst($type) . "/" . $product->id);   
                
            $res["close"] = true;
            $res["result"] = $this->input->post("create_edit") ? $edit_url : null;
            
            exit(json_encode($res));
		} 
		
        catalog::$validate_types = true;
        
		$res['title'] = et("catalog_new_" . $type);
		$res['content'] = view("\Sloway\Views\AdminCatalog\NewProduct", array(
            "categories" => $categories,
            "categories_value" => str_replace("|", ",", userdata::get("catalog_cats", "")),
            "type" => $type,
        ));
		$res['buttons'] = array(
			"create" => array("title" => t("Create"), "submit" => true, "key" => 13), 
			"create_edit" => array("title" => t("Create and edit"), "submit" => true),
			"cancel"
		);
				
		echo json_encode($res);
	}
	public function Ajax_NewItem($gid) {
		$this->auto_render = false;
		
        $group = \Sloway\catalog_product::load("catalog_product", "@id = '$gid'", 1);
		if ($this->input->post('create') || $this->input->post('create_edit')) { 
			$r = mlClass::post('catalog_product');
			$r->id_parent = $gid;
			$r->type = 'item';
			$r->title = $this->gen_item_titles($r);
			$r->save();
            
            $stock = $this->input->post("stock");
            if (is_numeric($stock))
                catalog::update_stock(null, $r->id, $stock, null, "create", \Sloway\admin_user::name());
	
            $res["close"] = true;
            $res["result"] = $this->input->post("create_edit") ? url::site("AdminCatalog/EditItem/" . $r->id) : null;
            
            exit(json_encode($res));
		}
		
        $c = Admin::Field(et("Code"), acontrol::edit("code", ""));
        $c.= Admin::Field(et("Initial stock"), acontrol::edit("stock", config::get("catalog.initial_stock", 0)));
        $c.= $this->PropertyEditor();
		
		$res['title'] = et("New item");
		$res['content'] = $c;
		$res['buttons'] = array(
			"create" => array("title" => t("Create"), "submit" => true, "key" => 13), 
			"create_edit" => array("title" => t("Create and edit"), "submit" => true),
			"cancel"
		);
		
		echo json_encode($res);
	}
    public function Ajax_StockManager($id) {
        $this->auto_render = false;
        
		$rt = config::get("catalog.stock.realtime");
        $amount = $this->input->post("amount");  
        
        $updated = false;
        if ($this->input->post('cancel_res')) {
            $q = $this->db->query("SELECT id FROM catalog_stock_op WHERE status = 'in_cart' OR status = 'pending'")->getResult();
            foreach ($q as $qq)
                catalog::reservation_release(null, $qq->id);
            
            $updated = true;
        }
        
        if ($this->input->post('reset')) {
            $this->db->query("DELETE FROM catalog_stock_op WHERE id_product = ?", [$id]);
            catalog::update_stock(null, $id, $amount, "reset", \Sloway\admin_user::name());
            $updated = true; 
        } else
        if ($this->input->post('update')) {
            catalog::update_stock(null, $id, $amount, "update", \Sloway\admin_user::name()); 
            $updated = true;
        } else 
        if ($this->input->post("update_close")) {
            catalog::update_stock(null, $id, $amount, "update", \Sloway\admin_user::name()); 
            
            $res = array();
            $res["close"] = 1;
            $res["result"] = true;
            
            exit(json_encode($res));
        }
        
        $product = mlClass::load("catalog_product", "@id = " . $id, 1);  
        $q = $this->db->query("SELECT amount FROM catalog_stock_op WHERE id_product = ? AND (status = 'in_cart' OR status = 'pending') ORDER BY time", [$id])->getResult();
        
        $reserved = 0;
        foreach ($q as $qq) 
            $reserved = catalog::stock_add($reserved, $qq->amount, -1);                           
            
        if (is_array($reserved)) $reserved = implode("/", $reserved);                                                                     
                                                                                                                
        $curr_stock = array();
        $entries = dbClass::load("catalog_stock_op", "@id_product = $id ORDER BY time ASC");
        foreach ($entries as $entry) {
            $curr_stock = catalog::stock_add($curr_stock, $entry->amount);
            
            $entry->curr_stock = implode("/", $curr_stock);
            $entry->info = $this->stock_manager_info($entry);
        }
        
        if ($updated)        
            $message = "<span style='color: darkgreen'>" . et("Stock successfuly updated to") . ": " . $product->stock . "</span>"; else
            $message = et("Current stock") . ": " . $product->stock;
        
		if ($rt)
        $message.= "&nbsp;&nbsp;&nbsp;<span style='color: orange'>" . et("Reserved") . ": " . $reserved . "</span>";
             
        $res = array();
        $res['title'] = et("Stock manager") . " " . $product->title;
        $res['content'] = view("\Sloway\Views\AdminCatalog\StockManager", array(
            "entries" => $entries, 
            "product" => $product, 
            "message" => $message, 
            "updated" => $updated,
			"reservations" => $rt,
        ));
        $res['buttons'] = array(
            "update" => array("align" => "left", "title" => t("Update"), "submit" => true),                                                        
            "update_close" =>  array("align" => "left", "title" => t("Update & close"), "submit" => true, "key" => 13),
        //    "reset" => array("align" => "left", "title" => t("Reset"), "submit" => true),                                                        
            "refresh" => array("align" => "left", "title" => t("Refresh"), "submit" => true),
            "close"
        );
        if (!Admin::auth("catalog.stock.reset"))
            unset($res['buttons']['reset']);
        
        
        echo json_encode($res);
    }    
    
    public function Ajax_Duplicate($id, $type) {
        $this->type = $type;
        
        $orig = dbModel::load("catalog_product", "@id = " . $id, 1);        
        $categories = dbModel::load('catalog_category' . $this->categories_flags);
        if ($this->input->post('create') || $this->input->post('edit')) {
            $product = dbModel::duplicate("catalog_product", "@id = '$id'", 1);
            $product->title = $this->input->post("title");
            
            if ($orig->code) 
                $product->code = $orig->code . " - copy";
                
            $product->save();    
            
            $stock = $this->input->post("stock");
            if (is_numeric($stock)) {
                catalog::update_stock(null, $product->id, $stock, null, "create", \Sloway\admin_user::name());
            }
            
            $edit_url = url::site("AdminCatalog/Edit" . ucfirst($type) . "/" . $product->id);   
                
            $res["close"] = true;
            $res["result"] = $this->input->post("edit") ? $edit_url : true;
            
            exit(json_encode($res));
        } 
        
        catalog::$validate_types = true;
        
        $res['title'] = et("Duplicate product");
        $res['content'] = view("\Sloway\Views\AdminCatalog\Duplicate", array(
            "categories" => $categories,
            "categories_value" => $orig->categories,
            "type" => $type,
            "original" => $orig,
        ));
        $res['buttons'] = array(
            "create" => array("align" => "left", "title" => t("Create"), "submit" => true, "key" => 13), 
            "edit"   => array("align" => "left", "title" => t("Create and edit"), "submit" => true), 
            "cancel"
        );
                
        echo json_encode($res);
    }
	public function Ajax_CatalogUpdate() {
		$this->auto_render = false;
		
        $x = $this->input->post("x");
        $y = $this->input->post("y");
        $id = $this->input->post("id");
        $name = $this->input->post("name");
        $value = $this->input->post("value");      
        
		$r = dbModel::load('catalog_product', "@id = $id", 1);
        $r->$name = $value;
        $r->save();
        
        $this->archived = false;
        $this->model = $this->catalog_model();
        
        $res = array(
            "x" => $x,
            "y" => $y,
            "content" => v($this->catalog_row($r, $name), "content"),
            "value" => $value
        );
        
        echo json_encode($res);
	}

	public function Ajax_Browser() {
		$this->auto_render = false;

        $param = $this->input->post("param");  
        $level = v($param, "level", 1);
		$select_all = v($param, "select_all", false);
        $th_temp = v($param, "thumbnail", false);
        $mode = v($param, "mode", "check");
        $e = explode("_", $mode);
        $mode = $e[0];
        $submode = count($e) > 1 ? $e[1] : "";
			
		$sel_chk = $this->input->post("select_checked");
		$sel_all = $this->input->post("select_all");
		if ($sel_chk || $sel_all) {
			$selected = array();
			if ($checked = $this->input->post("checked")) {
				$checked = explode(",", $checked);
				foreach ($checked as $cid) {          
					$prod = dbModel::load("catalog_product", "@id = " . $cid, 1);
                    
                    $cnt = 0; 
                    $ch_sel = $this->catalog_ch_sel($prod, "browser");
                    foreach ($ch_sel as $ch_name) 
                        if (is_array($prod->$ch_name)) $cnt+= count($prod->$ch_name);   

					$selected[$cid] = $this->browser_node(array(
						"id" => $cid,
						"code" => $prod->code,
						"image" => v($prod->images, "0.path", null),
						"thumb" => ($th_temp) ? thumbnail::from_image($prod->images, $th_temp)->result : null,
						"price" => str_replace(".", ",", $prod->price),
						"title" => $prod->title,
						"discount" => \Sloway\fixed::gen($prod->discount),
						"tax_rate" => \Sloway\fixed::gen($prod->tax_rate * 100),
					), $prod, $param);
				}
			}

			if ($select_all) {
				if ($sel_chk)
					$res["result"]["checked"] = array_values($selected); else
				if ($sel_all)
					$res["result"]["filter"] = array(
						"search" => $this->input->post("search"),
						"search_mode" => $this->input->post("search_mode"),
						"categories" => $this->input->post("categories"),
						"tags" => $this->input->post("tags"),
					);
			} else
				$res["result"] = array_values($selected);

			$res["close"] = true;
			echo json_encode($res);            
			exit;
		}
		
		$model = array(
			array('id' => 'title', 'content' => et('Title'), 'sort' => true),
			array('id' => 'code', 'content' => et('Code'), 'sort' => true),
			array('id' => 'price', 'content' => et('Price')),
            array('id' => 'tags', 'content' => et('Tags')),
            array('id' => 'categories', 'content' => et('Categories')),
		);
        $categories = dbModel::load("adm_catalog_category");
		$tags = arrays::regen(mlClass::load("catalog_tag", "* ORDER BY title ASC"), "id", "title", true);
        $filter = userdata::get_object("catalog_browser_", array("search", "search_mode", "cats", "tags", "types"));
		
		$res["title"] = et("Choose articles"); 
        $res["postdata"] = http_build_query($_POST);
		$res["content"] = view("\Sloway\Views\AdminCatalog\Browser", array("model" => $model, "param" => $param, "categories" => $categories, "tags" => $tags, "filter" => $filter, "mode" => $mode, "submode" => $submode));
        
		if ($select_all) 
			$res["buttons"] = array(
				"select_checked" => array("title" => v($param, "select_chk_title", t("Select checked items")), "submit" => true), 
				"select_all" => array("title" => v($param, "select_all_title", t("Select all items")), "submit" => true), 
				"cancel"); 
		else
        if ($mode == "check")
		    $res["buttons"] = array("select_checked" => array("title" => "OK", "submit" => true), "cancel"); else
            $res["buttons"] = array("close" => array("result" => false)); 
		
		echo json_encode($res);    
	}
    public function Ajax_EditTags($id) {
		$this->auto_render = false;    		
		
        $product = dbModel::load("catalog_product", "@id = $id", 1);
		if ($this->input->post("submit")) {
			$product->tags = $this->input->post("catalog_tags");
            $product->save();
            
            $res["close"] = true;
            $res["result"] = true;
            exit(json_encode($res));
		}
		
		$product = dbModel::load("catalog_product", "@id = $id", 1);
		$tags = arrays::regen(mlClass::load("catalog_tag", "* ORDER BY title ASC"), "id", "title");    
		
		$res['title'] = et("Tags"); 
		$res['content'] = acontrol::checklist("catalog_tags", $tags, $product->tags);
		$res['buttons'] = array("submit" => array("title" => "OK", "submit" => true), "cancel");
		echo json_encode($res);
	}
    public function Ajax_EditProperties($id) {
        $this->auto_render = false;  
        
        $product = dbClass::load("catalog_product", "@id = '$id'", 1); 
        $title = $product->title;

        if ($this->input->post("submit")) {
            $product->properties = $this->input->post("properties");
            $product->save();
			
			catalog::regen_items("SELECT * FROM catalog_product WHERE id = '$product->id'");
            
            $res["close"] = true;
            $res["result"] = true;
            exit(json_encode($res));
        }
        
        $res['title'] = et("Properties"); 
        $res['content'] = "<h2 class='admin_heading'>" . $title . "</h2>" . $this->PropertyEditor($product);
        $res['buttons'] = array("submit" => array("title" => "OK", "submit" => true), "cancel");
        echo json_encode($res);
    }
	public function Ajax_EditLists($id) {
        $this->auto_render = false;                
        
        $product = mlClass::load("catalog_product", "@id = " . $id, 1);
        if ($this->input->post("submit")) {
			$lids = $this->input->post("lists");
			if ($lids) $lids = explode(",", $lids); else $lids = array();

			$add = array();
			$rem = array();
			foreach ($this->_lists as $lid => $list)
				if (in_array($lid, $lids))
					$add[]= $lid; else
					$rem[]= $lid;

			if (count($rem))
				$this->db->query("DELETE FROM catalog_list WHERE id_product = '$id' AND id_parent IN (" . implode(",", $rem) . ")");
				
			$values = array();
			foreach ($add as $lid) {
				$values[]= array(
					"id_parent" => $lid,
					"id_product" => $id,
					"key" => $lid . "-" . $id,
				);
			}
			dbUtils::insert_update($this->db, "catalog_list", $values, true);
            
            $res["close"] = true;
            $res["result"] = true;
            exit(json_encode($res));
        }
        
        $title = $product->title;
		$lids = array();
		foreach ($this->db->query("SELECT DISTINCT id_parent FROM catalog_list WHERE id_product = ?", [$product->id])->getResult() as $q) {
			$lids[]= $q->id_parent;
		}

		$list_select = array();
		foreach ($this->_lists as $lid => $list) {
			$list_select[$lid] = array("title" => $list->title, "attr" => "data-disabled=" . intval($list->locked));
		}
        
        $c = "<h2 class='admin_heading'>" . $title . "</h2>";
        $c.= acontrol::checklist("lists", $list_select, implode(",", $lids));
                
        $res['title'] = et("Edit lists");
        $res['content'] = $c;
        $res['buttons'] = array("submit" => array("title" => "OK", "submit" => true), "cancel");
        echo json_encode($res);
	}
    public function Ajax_CategoryBrowser() {
        $this->auto_render = false;            
        
        $res = array();
        
        $param = $this->input->post("param");  
        $th_temp = v($param, "thumbnail", false);
        
        $categories = acontrol::tree_items(dbModel::load('catalog_category'), "subcat"); 
        if ($this->input->post("submit")) {
            $res["result"] = array();
            if ($cats = $this->input->post("categories")) {
                $cats = explode(",", $cats);
                foreach ($cats as $cid) {          
                    $cat = dbModel::load("catalog_category", "@id = " . $cid, 1);
                    
                    $res["result"][$cid] = array(
                        "id" => $cid,
                        "image" => v($cat->images, "#0.path", null),
                        "thumb" => ($th_temp) ? thumbnail::from_image($cat->images, $th_temp)->result : null,
                        "title" => $cat->title
                    );
                }
            }
            $res["result"] = array_values($res["result"]);     
            $res["close"] = true;
            echo json_encode($res);            
            exit;
        }             
        
        $res['title'] = et("Categories"); 
        $res['content'] = buffer::view("AdminCatalog/CategoryBrowser", array("categories" => $categories, "param" => $param));
        $res['buttons'] = array("submit" => array("title" => "OK", "submit" => true), "cancel");
        echo json_encode($res);
    }  
    public function Ajax_PropertyBrowser() {
        $this->auto_render = false;
        
        if ($this->input->post("submit")) {
            $pid = $this->input->post("property");
            $vid = $this->input->post("value");
            $new_title = $this->input->post("new_title");   
            $new_value = $this->input->post("new_value");   
            
            if (!$vid && !$new_title) {
                $res["close"] = true;
                echo json_encode($res);
            
                exit;        
            }
            
            if ($new_title) {
                $new = mlClass::create("catalog_property");
                $new->title = $new_title;            
                $new->value = $new_value;
                $new->id_parent = $pid;
                $new->visible = 1;
                $new->custom = 1;
                $new->save();
                
                $vid = $new->id;
            }
            
            $property = mlClass::load("catalog_property", "@id = '$pid'", 1);
            $value = mlClass::load("catalog_property", "@id = '$vid'", 1);
            
            $res["result"] = array(
                "property_id" => $pid,
                "property_title" => $property->title,
                "value_id" => $vid,
                "value_title" => $value->title
            ); 
            $res["close"] = true;
            echo json_encode($res);
            
            exit;    
        }
        if ($pid = $this->input->post("property_change")) {
            $values = dbClass::load("catalog_property", "@id_parent = '$pid' ORDER BY title ASC", 0, array("index" => "id"));
            $value_id = arrays::first($values, true);
            
            echo Admin::Field(et("Value"), acontrol::select("value", arrays::regen($values), $value_id));
            exit();
        }
        
        $pid = $this->input->post("pid");
        $vid = $this->input->post("vid");
        $exclude = $this->input->post("exclude");
        $sql = "@id_parent = 0";
        if (!is_null($exclude) && !$pid)
            $sql.= " AND id NOT IN (" . implode(",", $exclude) . ")";
        
        $properties = dbClass::load("catalog_property", $sql . " ORDER BY title ASC", 0, array("index" => "id"));
        if (!count($properties)) {
            $res = array();
            $res['title'] = et("Properties");       
            $res['content'] = "<div class='admin_message warning'>" . et("All properties are set") . "</div>";
            $res['buttons'] = array("cancel");
            echo json_encode($res); 
            exit();   
        }
        $property_id = ($pid) ? $pid : arrays::first($properties, true);
        
        $values = dbClass::load("catalog_property", "@id_parent = '$property_id' ORDER BY title ASC", 0, array("index" => "id"));
        $value_id = ($vid) ? $vid : arrays::first($values, true);
        
        $c = Admin::Field(et("Select property"), acontrol::select("property", arrays::regen($properties), $property_id));
        $c.= "<div class='admin_property_select_value'>";
        $c.= Admin::Field(et("Select value"), acontrol::select("value", arrays::regen($values), $value_id));
        $c.= "</div>";
        $c.= "<div class='admin_property_select_newvalue'>";
        $c.= "<h2 class='admin_heading'>" . et("Create new value") . "</h2>";
        $c.= Admin::Field(et("Title"), acontrol::edit("new_title"));
        //$c.= Admin::Field(et("Value"), acontrol::edit("new_value"));
        $c.= "</div>";
        
        $res['title'] = et("Properties");       
        $res['content'] = $c;
        $res['buttons'] = array("submit" => array("title" => "OK", "submit" => true), "cancel");
        echo json_encode($res);
    }    
	
//  EDIT
    public function EditCategory($id) {
        $this->edit_category($id);

        $this->module_path = array(
            et('Categories') => url::site('AdminCatalog/Categories'),
            $this->cat->title
        );

        $this->module_menu = Admin::EditMenu(array(
            "back" => "AdminCatalog/Categories",
			"view_url" => Admin::LoadUrls($this->cat),
        ));        
        $this->module_content = Admin::tabs($this->module_tabs, "categories", view("\Sloway\Views\AdminCatalog\EditCategory", array(
			"cat" => $this->cat,
			"users" => $this->users,
		)));
		return $this->admin_view();
    }
    public function EditProperty($id) {
        $this->edit_property($id);
        
        $this->module_path = array(
            et("Properties") => url::site("AdminCatalog/Properties"),
            $this->property->title
        );
        $this->module_menu = Admin::EditMenu(array(
            "back" => "AdminCatalog/Properties",
        ));        
        $this->module_content = Admin::tabs($this->module_tabs, "properties", view("\Sloway\Views\AdminCatalog\EditProperty", array(
			"property" => $this->property,
			"filter_templates" => $this->flt_templates,
			"selector_templates" => $this->sel_templates
		)));    
		return $this->admin_view();
	}    
    public function EditGroup($id) {
		$this->lang_selector = false;
        $this->edit_group($id);
        
        $this->module_path = array($this->product->title);
        $this->module_menu = Admin::EditMenu(array(
            "back" => "AdminCatalog",
			"view_url" => Admin::LoadUrls($this->product),
        ));
        $this->module_content = Admin::tabs($this->module_tabs, "catalog", view("\Sloway\Views\AdminCatalog\EditGroup", array(
			"product" => $this->product,
			"tax_rates" => $this->tax_rates,
			"categories" => $this->categories,
			"property_editor" => $this->PropertyEditor($this->product),
		)));

		return $this->admin_view();
    }
    public function EditBundle($id) {
        $this->edit_bundle($id);

        $this->module_path = array($this->bundle->title);
        
        $this->module_menu = Admin::EditMenu(array(
            "back" => "AdminCatalog",
            "visible" => $this->bundle->visible,
			"view_url" => path::url("product", $this->bundle)
        ));        
        $this->module_content = Admin::tabs($this->module_tabs, "catalog", view("\Sloway\Views\AdminCatalog\EditBundle", array(
			"bundle" => $this->bundle,
			"tax_rates" => $this->tax_rates,
			"categories" => $this->categories,
			"slots_editor" => Admin::EditTree('slots', $this->nodes, array($this, "Slot_Builder"), array("slot", "item"), array('title' => et("Bundle")))
		)));

		return $this->admin_view();
    }    
    public function EditItem($id) {
        $this->edit_item($id);
        
        $this->module_path = array($this->item->title);
        $this->tabs_curr = "catalog";
        
        $q = $this->db->query("SELECT COUNT(id) as cnt FROM catalog_product WHERE id_parent = ?", [$this->item->id_parent])->getResult();
        $c = $q[0]->cnt;
        
        $this->module_menu = Admin::EditMenu(array(
            "back" => ($c > $this->tree_treshold) ? "AdminCatalog/Index/0/" . $this->item->id_parent : "AdminCatalog",
            "visible" => $this->item->visible,
        ));        
        $this->module_content = Admin::tabs($this->module_tabs, "catalog", view("\Sloway\Views\AdminCatalog\EditItem", array(
			"item" => $this->item,
			"default_title" => $this->default_title,
			"custom_title_chk" => $this->custom_title_chk,
			"property_editor" => $this->PropertyEditor($this->item),
		)));

		return $this->admin_view();
    }
    public function EditDiscount($id) {
        Admin::auth("catalog.discounts", $this);
        if (!config::get("catalog.discounts")) $this->deny_access = true;
        $this->edit_discount($id);
        
        $this->module_path = array(
            et("Discounts") => url::site("AdminCatalog/Discounts"),
            $this->discount->title
        );

        $this->module_menu = Admin::EditMenu(array(
            "back" => "AdminCatalog/Discounts",
        ));        
        $this->module_content = Admin::tabs($this->module_tabs, "discounts", view("\Sloway\Views\AdminCatalog\EditDiscount", array(
			"discount" => $this->discount,
			"tags" => $this->tags,
			"products" => $this->products,
			"categories" => $this->categories
		)));
		return $this->admin_view();
    }    
    public function EditTag($id) {
        Admin::auth("catalog.tags", $this);
        $this->edit_tag($id);
        
        $this->module_path = array(
            et("Tags") => url::site("AdminCatalog/Tags"),
            $this->tag->title
        );
        $this->module_menu = Admin::EditMenu(array(
            "back" => "AdminCatalog/Tags",
        ));        
        $this->module_content = Admin::tabs($this->module_tabs, "tags", view("\Sloway\Views\AdminCatalog\EditTag", array(
			"tag" => $this->tag
		)));

		return $this->admin_view();
    }    

//  SAVE
    public function Ajax_CategoryHandler($id, $action) {
        $dialog = new \stdClass();
        if ($action == "save" || $action == "close") {
            $reload = $action == "save";
			$err = $this->save_category($id);
			if ($err) {
				$dialog->message = "<div class='admin_message failure'>" . $err . "</div>";
				echo json_encode($dialog); 
				exit();
			}            
            $dialog->content = "<div class='admin_message success'>Category saved</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
                
        if ($reload) {
            $this->EditCategory($id);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }                
        
        echo json_encode($dialog);           
    }
    public function Ajax_PropertyHandler($id, $action) {
        $dialog = new \stdClass();
        if ($action == "save" || $action == "close") {
            $reload = $action == "save";
            $this->save_property($id);
            
            $dialog->content = "<div class='admin_message success'>Property saved</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
                
        if ($reload) {
            $this->EditProperty($id);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }                
        
        echo json_encode($dialog);           
    }
	public function Ajax_GroupHandler($id, $action) {
        $this->auto_render = false;
        
        $dialog = new \stdClass();
        if ($action == "save" || $action == "close") {
            $reload = $action == "save";
            $err = $this->save_group($id);
			if ($err) {
				$dialog->message = "<div class='admin_message failure'>" . $err . "</div>";
				echo json_encode($dialog); 
				exit();
			}
            
            $dialog->content = "<div class='admin_message success'>Article saved</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
                
        if ($reload) {
            $this->EditGroup($id);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }                
        
        echo json_encode($dialog);           
	}
    public function Ajax_BundleHandler($id, $action) {
       $dialog = new \stdClass();
        if ($action == "save" || $action == "close") {
            $reload = $action == "save";
            $this->save_bundle($id);
            
            $dialog->content = "<div class='admin_message success'>Article saved</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
                
        if ($reload) {
            $this->EditBundle($id);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }                
        
        echo json_encode($dialog);           
    }
	public function Ajax_ItemHandler($id, $action) {
        $dialog = new \stdClass();
        if ($action == "save" || $action == "close") {
            $reload = $action == "save";
            $this->save_item($id);
            
            $dialog->content = "<div class='admin_message success'>Article saved</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
                
        if ($reload) {
            $this->EditItem($id);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }                
        
        echo json_encode($dialog);              
	}
    public function Ajax_DiscountHandler($id, $action) {
        $dialog = new \stdClass();
        if ($action == "save" || $action == "close") {
            $reload = $action == "save";
            $this->save_discount($id);
            
            $dialog->content = "<div class='admin_message success'>Discount saved</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
                
        if ($reload) {
            $this->EditDiscount($id);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }                
        
        echo json_encode($dialog);           
    }    
    public function Ajax_TagHandler($id, $action) {
        $dialog = new \stdClass();
        if ($action == "save" || $action == "close") {
            $reload = $action == "save";
            $this->save_tag($id);
            
            $dialog->content = "<div class='admin_message success'>Tag saved</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
                
        if ($reload) {
            $this->EditTag($id);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }                
        
        echo json_encode($dialog);           
    }    
    
    public function Ajax_ProductImage() {
        $this->auto_render = false;    
        
        $res = array();
        $ids = $this->input->post("ids");
        $template = $this->input->post("template");
                
        if (count($ids)) {
            $products = dbModel::load("catalog_product", "@id IN (" . implode(",", $ids) . ")");
            foreach ($products as $product) {
                $img = $product->image();
                if (!$img) continue;
                
                $th = thumbnail::create(null, $img, null, $template);                
                if (!$th->result) continue;
                
                $res[$product->id] = $th->result; 
            }
        }
        
        echo json_encode($res);
    }
    public function Ajax_CategoryImage() {
        $this->auto_render = false;    
        
        $res = array();
        $ids = $this->input->post("ids");
        $template = $this->input->post("template");
                
        if (count($ids)) {
            $categories = dbModel::load("catalog_category", "@id IN (" . implode(",", $ids) . ")");
            foreach ($categories as $category) {
                $img = $category->image();
                if (!$img) continue;
                
                $th = thumbnail::create(null, $img, null, $template);                
                if (!$th->result) continue;
                
                $res[$category->id] = $th->result; 
            }
        }
        
        echo json_encode($res);
    }
    public function Ajax_TemplateItem() {
        $result = array();
        $ids = $this->input->post("ids", array());
		if (is_string($ids))
			$ids = explode(",", $ids);

        $template = $this->input->post("template");
        if (strpos($template, "product") === 0) 
            $items = ($ids) ? dbModel::load("catalog_product", "@id IN (" . implode(",", $ids) . ")") : array(); else
        if (strpos($template, "category") === 0) 
            $items = ($ids) ? dbModel::load("catalog_category", "@id IN (" . implode(",", $ids) . ")") : array(); 
        
        foreach ($items as $item) {
            $res = new \stdClass();
            $res->title = $item->title;
            $res->image = thumbnail::create(null, $item->image(), null, "admin_gallery_96")->result;
            
            $result[$item->id] = $res;
        } 
        
        echo json_encode($result);    
    }   
	
	public function Ajax_Visibility($table, $id) {
		$this->auto_render = false;    		

		$obj = dbClass::load($table, "@id = '$id'", 1);
		$lang_select = array();
		$lang_all = "";
		$langs = lang::languages(true);
		
		foreach ($langs as $lang) {
			$lang_all.= "," . $lang;
			$lang_select[$lang] = t("lang_" . $lang);
		}
		$langs_all = trim($lang_all, ",");
		
		if ($obj->visible == "1")
			$value = $langs_all; else
			$value = $obj->visible;
		
		if ($this->input->post("submit")) {
			$val = $this->input->post("visible");
			if ($val == $langs_all)
				$val = "1";
			
			$obj->visible = $val;
            $obj->save();
            
            $res["close"] = true;
            $res["result"] = true;
            exit(json_encode($res));
		}
		
		
		$res['title'] = et("Visibility"); 
		$res['content'] = acontrol::checklist("visible", $lang_select, $value);
		$res['buttons'] = array("submit" => array("title" => "OK", "submit" => true), "cancel");
		echo json_encode($res);		
	}
                                                                  
    public function Slot_Builder($mode, $type, $data, $types) {
        $c = "";
        $res = array();
        if ($mode == "root") {
            $res["drop"] = "slot,item";
            $res["menu"] = "<a href='#' class='admin_button_add' onclick='\$.admin.edittree.add(this, \"slot\"); return false'>" . et("Add slot") . "</a>";
        } else
        if ($type == "slot") {
            $c.= "<div class='admin_eti_menu'>";
            $c.= "<a class='admin_button_add small' onclick='add_slot_item(this); return false'>" . et("Add") . "</a>";
            $c.= "<a class='admin_button_del small' onclick='\$.admin.edittree.remove(this)'>" . et("Delete") . "</a>";
            $c.= "</div>";
            
            $c.= "<table class='admin_et_main'></tr>";
            $c.= "<td>" . et('Slot title') . "</td>";
            $c.= "<td>" . acontrol::edit("title", $data->title) . "</td>";
            $c.= "</tr></table>";
            
            $res["id"] = $data->id;
            $res["drop"] = "item";
            $res["content"] = $c;
        } else
        if ($type == "item") {
            $c.= "<input type='hidden' data-name='item_id' value='$data->id'>"; 
            
            $c.= "<div class='admin_eti_menu'>";
            $c.= "<a class='admin_button_del small' onclick='\$.admin.edittree.remove(this)'>" . et("Delete") . "</a>";
            $c.= "</div>";
            
            $c.= "<table class='admin_et_main'></tr>";
            
            $s = "width: 150px; text-overflow: ellipsis; display: block; overflow: hidden";
            $c.= "<td><div class='item_title' style='$s'>$data->title</div></td>";
            
            $props = v($data, "slot.properties");
            if (!is_array($props))
                $props = json_decode($props);
                
            foreach (config::get("catalog.slot_properties") as $prop_name) {
                $c.= "<td>" . et("catalog_slot_" . $prop_name) . "</td>";
                $c.= "<td>" . acontrol::edit($prop_name, v($props, $data->id . "." . $prop_name, "")) . "</td>";
            }
            $c.= "</tr></table>";
            
            $res["id"] = $data->id;
            $res["name"] = "items";
            $res["content"] = $c;
        }
        
        return $res;
    }  
    
    public function PropertyEditor($product = null) {
        $prop_ids = $product ? arrays::decode($product->properties, ".", ",") : array();
        
        $properties = array();
        $prop_value = "";
        foreach ($prop_ids as $pid => $vid) {
            $property = mlClass::load("catalog_property", "@id = '$pid'", 1);
            if (!$property) continue;
            
            $value = mlClass::load("catalog_property", "@id = '$vid'", 1);
            if (!$value) continue;
            
            $prop_value.= "," . $pid . "." . $vid;
            $property->value = $value;
            $properties[$pid] = $property;
        }
        $prop_value = trim($prop_value, ",");
        
        return view("\Sloway\Views\AdminCatalog\PropertyEditor", array("properties" => $properties, "value" => $prop_value));
    }     
    
	public function Repair() {
		$insert = array();
		foreach ($this->db->query("SELECT * FROM catalog_product")->getResult() as $prod) {
			$cats = array();
			$e = explode(",", $prod->categories);
			foreach ($e as $ee) {
				if (strpos($ee, ".") !== false)
					$cats[]= $ee;
			}
			
			$insert[]= array("id" => $prod->id, "categories" => implode(",", $cats));
		}
		
		dbUtils::insert_update($this->db, "catalog_product", $insert, true);
	}
	public function Test() {
        $this->module_content = view("\Sloway\Views\AdminCatalog\Test");
		
		return $this->admin_view();		
	}
}


