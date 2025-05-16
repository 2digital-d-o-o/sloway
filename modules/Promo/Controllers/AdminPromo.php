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
use Sloway\arrays;
use Sloway\admin;
use Sloway\mlClass;
use Sloway\dbClass;
use Sloway\genClass;
use Sloway\settings;
use Sloway\images;
use Sloway\files;
use Sloway\acontrol;
use Sloway\userdata;
use Sloway\dbModel;
use Sloway\lang;
use Sloway\catalog;
 
class AdminPromo extends AdminController {
    public $module = 'promo';    

    protected function codes_row($code) {
        $edit_url = url::site("AdminPromo/EditCode/" . $code->id);
        
        if (!$code->active)
            $s = "color: gray"; else
            $s = "";

        $t = $code->title;
		if ($code->code_count) 
			$t.= " (" . $code->code_count . ")"; 
            
        $title = "<a href='$edit_url' style='$s' onclick='return admin_redirect(this)'>" . $t . "</a>";
		
		$menu = Admin::IconB("icon-edit.png", "ajax:" . $edit_url, t("Edit"));
		$menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "codes_delete($code->id)");
        
        $typ = config::get("promo.code_types." . $code->type);
		
        $res = array(
            "title" => array("content" => $title, "value" => $code->title),
            "active" => Admin::VisibilityBar($code->active, "codes_active('$code->id')"),
			"code" => $code->code,
            "value" => array("content" => $typ . ": " . $code->value),
            "date_from" => \Sloway\utils::date_time($code->time_from),
            "date_to" => \Sloway\utils::date_time($code->time_to),
            "modified" => \Sloway\utils::modified($code->edit_time, $code->edit_user),
            "menu" => $menu
        );
        
        return $res;
    } 
    protected function codes_model() {
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
				"id" => "code",
				"width" => "content",
				"content" => t("Code"),
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
    protected function upsales_row($upsale) {
        $edit_url = url::site("AdminPromo/EditUpsale/" . $upsale->id);
        
        if (!$upsale->active)
            $s = "color: gray"; else
            $s = "";

        $t = $upsale->title;
           
        $title = "<a href='$edit_url' style='$s' onclick='return admin_redirect(this)'>" . $t . "</a>";

        $typ = config::get("promo.upsale_types." . $upsale->type);

		
		$menu = Admin::IconB("icon-edit.png", "ajax:" . $edit_url, t("Edit"));
		$menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "upsales_delete($upsale->id)");
		
        $res = array(
            "title" => array("content" => $title, "value" => $upsale->title),
			"priority" => $upsale->priority,
            "active" => Admin::VisibilityBar($upsale->active, "upsales_active('$upsale->id')"),
            "modified" => \Sloway\utils::modified($upsale->edit_time, $upsale->edit_user),
            "menu" => $menu
        );
        
        return $res;
    } 
    protected function upsales_model() {
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
                "id" => "priority",
                "content" => "#",
                "width" => "content",
                "edit" => "text",
                "sort" => true
            ),
            array(
                "id" => "active",
                "content" => et("Active"),
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
    protected function edit_code($id) {
        $this->categories = dbModel::load("catalog_category");
		$this->tags = mlClass::load("catalog_tag");
        $this->code = dbClass::load("promo_code", "@id = $id", 1);
        $this->products = ($this->code->products) ? dbClass::load("catalog_product", "@id IN (" . $this->code->products . ") ORDER BY title ASC") : array();
        $this->code_types = config::get("promo.code_types");

		$q = $this->db->query("SELECT COUNT(id) AS cnt FROM promo_code WHERE id_parent = '$id'")->getResult();
		$this->count = $q[0]->cnt;
    }   
    protected function save_code($id) {
        $date_from = $this->input->post("date_from");
        $date_to = $this->input->post("date_to");
        
        $time_from = strtotime($date_from);
        $time_to = strtotime($date_to);
        
        $r = dbClass::post("promo_code", $id);
        $r->time_from = $time_from;
        $r->time_to = $time_to;
        $r->date_from = \Sloway\utils::mysql_datetime($time_from);
        $r->date_to = \Sloway\utils::mysql_datetime($time_to);
        $r->save();

		$this->db->query("UPDATE promo_code SET type = ?, value = ?, categories = ?, products = ?, time_from = ?, time_to = ?, date_from = ?, date_to = ? WHERE id_parent = '$r->id'", array(
			$r->type, $r->value, $r->categories, $r->products, $r->time_from, $r->time_to, $r->date_from, $r->date_to
		));
    } 

    protected function edit_upsale($id) {
        $this->categories = dbModel::load("catalog_category");
		$this->tags = mlClass::load("catalog_tag");
        $this->upsale = mlClass::load("promo_upsale", "@id = '$id'", 1, null, "*");
        $this->products = ($this->upsale->products) ? dbClass::load("catalog_product", "@id IN (" . $this->upsale->products . ") ORDER BY title ASC") : array();
    }   
    protected function save_upsale($id) {
        $date_from = $this->input->post("date_from");
        $date_to = $this->input->post("date_to");
        
        $time_from = strtotime($date_from);
        $time_to = strtotime($date_to);
        
        $r = mlClass::post("promo_upsale", $id);
        $r->time_from = $time_from;
        $r->time_to = $time_to;
        $r->date_from = \Sloway\utils::mysql_datetime($time_from);
        $r->date_to = \Sloway\utils::mysql_datetime($time_to);
        $r->save();
    } 

    protected function format_price($price) {
        if ($price === "" || is_null($price)) return "";
        
        return str_replace(".", ",", strval($price));
    } 
	protected function generate_codes($pid, $count, $mask) {
		$count = intval($count);

		$prefix = "";
		$chars = 0;
		for ($i = 0; $i < strlen($mask); $i++) {
			if ($mask[$i] == "#")
				$chars++; else
			if (!$chars)
				$prefix.= $mask[$i];
		}
		$seed = \Sloway\dbUtils::auto_increment($this->db, "promo_code");

		$parent = dbClass::load("promo_code", $pid, 1);
		$values = array();
		for ($i = 0; $i < $count; $i++) {
			$code = $prefix . strtoupper(\Sloway\uniqueid::encode($seed + $i, $chars));
			$values[]= array(
				"id_parent" => $pid,
				"active" => $parent->active,
				"value" => $parent->value,
				"price_tr" => $parent->price_tr,
				"title" => "",
				"type" => $parent->type,
				"code" => $code,
				"time_from" => $parent->time_from,
				"time_to" => $parent->time_to,
				"date_from" => $parent->date_from,
				"date_to" => $parent->date_to,
				"categories" => $parent->categories,
				"products" => $parent->products,
				"counter" => 0,
			);
		}
		\Sloway\dbUtils::insert_multiple($this->db, "promo_code", $values, true);

		$q = $this->db->query("SELECT COUNT(id) AS cnt FROM promo_code WHERE id_parent = '$pid'")->getResult();
		$this->db->query("UPDATE promo_code SET code_count = ? WHERE id = ?", [$q[0]->cnt, $pid]);
	}
    
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)	{
		parent::initController($request, $response, $logger);

		$this->module_tabs = array(
            'codes'   => "<a href='" . url::site("AdminPromo/Codes") . "' onclick='return admin_redirect(this)'>" . et('Codes') . "</a>",
            'upsales' => "<a href='" . url::site("AdminPromo/Upsales") . "' onclick='return admin_redirect(this)'>" . et('Upsales') . "</a>",
        );
	} 

    public function Codes() {
        Admin::auth("promo_codes", $this);
        
        $categories = acontrol::tree_items(dbModel::load('catalog_category'), "subcat");
        $filter = userdata::get_object("promo_codes_", array("from", "to", "cats", "search"));
        
        $model = $this->codes_model();

        $this->module_path = array(et('Promo Codes') => '');
        $this->module_menu = view("\Sloway\AdminPromo\Codes\Menu", array(
			"filter" => $filter,
			"categories" => $categories,
		));
        $this->module_content = Admin::tabs($this->module_tabs, "codes", view("\Sloway\AdminPromo\Codes\Index", array(
			"dg_model" => $model,
		)));

		return $this->admin_view();
    }    
    public function Upsales() {
        Admin::auth("promo_upsales", $this);
        
        $categories = acontrol::tree_items(dbModel::load('catalog_category'), "subcat");
        $filter = userdata::get_object("promo_upsales_", array("from", "to", "cats", "search"));
        
        $model = $this->upsales_model();

        $this->module_path = array(et('Upsale') => '');
        $this->module_menu = view("\Sloway\AdminPromo\Upsales\Menu", array(
			"filter" => $filter,
			"categories" => $categories,
		));
        $this->module_content = Admin::tabs($this->module_tabs, "upsales", view("\Sloway\AdminPromo\Upsales\Index", array(
			"dg_model" => $model,
		)));

		return $this->admin_view();
    }    
    public function EditCode($id) {
        Admin::auth("promo_codes", $this);
		
		catalog::$db = $this->db;
		catalog::build_adm_categories();
		
        $this->edit_code($id);
        
        $this->module_path = array(
            et("Promo Codes") => url::site("AdminPromo/Codes"),
            $this->code->title
        );
        $this->module_menu = view("\Sloway\AdminPromo\Codes\EditMenu", array(
			"code" => $this->code,
			"count" => $this->count,
            "back_url" => "AdminPromo/Codes",
        ));
        $this->module_content = view("\Sloway\AdminPromo\Codes\Edit", array(
			"code" => $this->code,
			"count" => $this->count,
			"code_types" => $this->code_types,
			"categories" => $this->categories,
			"products" => $this->products,
			"tags" => $this->tags,
		));
		return $this->admin_view();
    }    
    public function EditUpsale($id) {
        Admin::auth("promo_codes", $this);
		
		catalog::$db = $this->db;
		catalog::build_adm_categories();
		
        $this->edit_upsale($id);
        
        $this->module_path = array(
            et("Upsales") => url::site("AdminPromo/Upsales"),
            $this->upsale->title
        );
        $this->module_menu = view("\Sloway\AdminPromo\Upsales\EditMenu", array(
			"upsale" => $this->upsale,
            "back_url" => "AdminPromo/Upsales",
        ));
        $this->module_content = view("\Sloway\AdminPromo\Upsales\Edit", array(
			"upsale" => $this->upsale,
			"categories" => $this->categories,
			"products" => $this->products,
			"tags" => $this->tags,
		));
		return $this->admin_view();
    }    
    
    public function Ajax_CodesHandler($pid = 0) {
        if ($delete = $this->input->post("delete")) {
			$this->db->query("DELETE FROM promo_code WHERE id_parent = ?", [$delete]);
            $this->db->query("DELETE FROM promo_code WHERE id = ?", [$delete]);    
		}
            
        if ($this->input->post("filter")) {
            $filter = new \stdClass();
            $filter->search = trim($this->input->post("filter_search"));
            $filter->from = strtotime($this->input->post("filter_from"));
            $filter->to = strtotime($this->input->post("filter_to"));
           
            userdata::set_object("promo_codes_", $filter);        
        } else 
            $filter = userdata::get_object("promo_codes_", array("search", "from", "to"));    

        
        $page = $this->input->post("page", 1) - 1;
        $perpage = $this->input->post("per_page", 20);        
        $sort = $this->input->post("sort", "title");
        $sort_dir = $this->input->post("sort_dir", 1);
        
        $sql = array("id_parent = 0");
        if ($filter->search)
            $sql[]= "title LIKE '%$filter->search%'";
        if ($from = $filter->from)
            $sql[]= "(date_from = 0 OR time_to >= $from)";
        if ($from = $filter->to)
            $sql[]= "(date_to = 0 OR time_from <= $to)";
            
        $where = count($sql) ? " WHERE " . implode(" AND ", $sql) : "";        
        
        $q = $this->db->query("SELECT COUNT(id) as count FROM `promo_code`" . $where)->getResult();
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
        $codes = mlClass::load("promo_code", "SELECT * FROM promo_code " . $where . $order . $limit);
        
        $rows = array();
        foreach ($codes as $code) {
            $row = array(
                "id" => $code->id,
                "cells" => array_values($this->codes_row($code)),
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
    public function Ajax_CodesAdd() {
        $this->auto_render = false;
        
        $title = $this->input->post("title");
        if ($this->input->post("create") && !empty($title)) {
            $r = dbClass::create("promo_code");
            $r->title = $title;
            $r->save();
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            
            exit;
        } 
        
        $res["title"] = et("Add code");
        $res["content"] = Admin::Field(et("Title"), acontrol::edit("title"));
        $res["buttons"] = array("create" => array("title" => "OK", "submit" => true, "key" => 13), "cancel");
        
        echo json_encode($res);                
    }    
	public function Ajax_CodesActive($id) {
		$this->auto_render = false;    		

		$obj = dbClass::load("promo_code", "@id = '$id'", 1);
		$lang_select = array();
		$lang_all = "";
		$langs = lang::languages(true);
		
		foreach ($langs as $lang) {
			$lang_all.= "," . $lang;
			$lang_select[$lang] = t("lang_" . $lang);
		}
		$langs_all = trim($lang_all, ",");
		
		if ($obj->active == "1")
			$value = $langs_all; else
			$value = $obj->active;
		
		if ($this->input->post("submit")) {
			$val = $this->input->post("visible");
			if ($val == $langs_all)
				$val = "1";
			
			$obj->active = $val;
            $obj->save();
			
			$this->db->query("UPDATE promo_code SET active = ? WHERE id_parent = ?", [$val, $obj->id]);
            
            $res["close"] = true;
            $res["result"] = true;
            exit(json_encode($res));
		}
		
		
		$res['title'] = et("Active"); 
		$res['content'] = acontrol::checklist("visible", $lang_select, $value);
		$res['buttons'] = array("submit" => array("title" => "OK", "submit" => true), "cancel");
		echo json_encode($res);		
	}	
    public function Ajax_CodeHandler($id, $action) {
        $dialog = new \stdClass();
		if ($action == "del_codes") {
			$this->db->query("DELETE FROM promo_code WHERE id_parent = '$id'");
			$this->db->query("UPDATE promo_code SET code_count = 0 WHERE id = '$id'");

            $dialog->content = "<div class='admin_message success'>Codes deleted</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
			$reload = true;
		} else
        if ($action == "save" || $action == "close") {
			$count = $this->input->post("gen_count");
			$mask = $this->input->post("gen_mask");

            $reload = $action == "save";
            $this->save_code($id);

			if ($count && $mask) {
				$this->generate_codes($id, $count, $mask);
				$msg = $count . " codes generated";
			} else
				$msg = "Code saved";

            $dialog->content = "<div class='admin_message success'>$msg</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
                
        if ($reload) {
            $this->EditCode($id);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }                
        
        echo json_encode($dialog);           
    } 

    public function Ajax_Generate($pid) {        
		$count = intval($this->input->post("count"));
		$mask = $this->input->post("mask");
        if ($this->input->post("create") && $count && strlen($mask)) {
            $res['result'] = array(
				"count" => $count,
				"mask" => $mask
			);
			$res['close'] = 1;
            echo json_encode($res);
            
            exit;
        } 
        
        $res["title"] = et("Generate codes");
        $res["content"] = Admin::Field(et("Count"), acontrol::edit("count", 10)) . Admin::Field(et("Mask"), acontrol::edit("mask", "######"));
        $res["buttons"] = array("create" => array("title" => "Generate", "submit" => true, "key" => 13), "cancel");
        
        echo json_encode($res);                
    }    
	public function Ajax_DeleteCodes($pid) {
		$this->db->query("DELETE FROM promo_code WHERE id_parent = '$pid'");
	}
	public function Export($pid) {
		require MODPATH . "Core/Classes/FastExcelWriter/autoload.php";

		$lines = array();
		foreach ($this->db->query("SELECT * FROM promo_code WHERE id_parent = '$pid'")->getResult() as $code) {
			$lines[]= array(
				$code->code,
			);
		}

		\Sloway\excel::output($lines, "Codes");
	}

    public function Ajax_UpsalesHandler() {
	/*
        if ($delete = $this->input->post("delete")) {
			$this->db->query("DELETE FROM promo_code WHERE id_parent = ?", [$delete]);
            $this->db->query("DELETE FROM promo_code WHERE id = ?", [$delete]);    
		}
	 * 
	 */
            
        if ($this->input->post("filter")) {
            $filter = new \stdClass();
            $filter->search = trim($this->input->post("filter_search"));
            $filter->from = strtotime($this->input->post("filter_from"));
            $filter->to = strtotime($this->input->post("filter_to"));
           
            userdata::set_object("promo_upsales_", $filter);        
        } else 
            $filter = userdata::get_object("promo_upsales_", array("search", "from", "to"));    

        
        $page = $this->input->post("page", 1) - 1;
        $perpage = $this->input->post("per_page", 20);        
        $sort = $this->input->post("sort", "title");
        $sort_dir = $this->input->post("sort_dir", 1);
        
        $sql = array();
        if ($filter->search)
            $sql[]= "title LIKE '%$filter->search%'";
        if ($from = $filter->from)
            $sql[]= "(date_from = 0 OR time_to >= $from)";
        if ($from = $filter->to)
            $sql[]= "(date_to = 0 OR time_from <= $to)";
            
        $where = count($sql) ? " WHERE " . implode(" AND ", $sql) : "";        
        
        $q = $this->db->query("SELECT COUNT(id) as count FROM `promo_upsale`" . $where)->getResult();
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
        $codes = mlClass::load("promo_upsale", "SELECT * FROM promo_upsale " . $where . $order . $limit);
        
        $rows = array();
        foreach ($codes as $code) {
            $row = array(
                "id" => $code->id,
                "cells" => array_values($this->upsales_row($code)),
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
    public function Ajax_UpsalesAdd() {
        $title = $this->input->post("title");
        if ($this->input->post("create") && !empty($title)) {
            $r = dbClass::create("promo_upsale");
            $r->title = $title;
            $r->save();
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            
            exit;
        } 
        
        $res["title"] = et("Add upsale");
        $res["content"] = Admin::Field(et("Title"), acontrol::edit("title"));
        $res["buttons"] = array("create" => array("title" => "OK", "submit" => true, "key" => 13), "cancel");
        
        echo json_encode($res);                
    }    
    public function Ajax_UpsalesActive($id) {
		$this->auto_render = false;    		

		$obj = dbClass::load("promo_upsale", "@id = '$id'", 1);
		$lang_select = array();
		$lang_all = "";
		$langs = lang::languages(true);
		
		foreach ($langs as $lang) {
			$lang_all.= "," . $lang;
			$lang_select[$lang] = t("lang_" . $lang);
		}
		$langs_all = trim($lang_all, ",");
		
		if ($obj->active == "1")
			$value = $langs_all; else
			$value = $obj->active;
		
		if ($this->input->post("submit")) {
			$val = $this->input->post("visible");
			if ($val == $langs_all)
				$val = "1";
			
			$obj->active = $val;
            $obj->save();
			
            $res["close"] = true;
            $res["result"] = true;
            exit(json_encode($res));
		}
		
		
		$res['title'] = et("Active"); 
		$res['content'] = acontrol::checklist("visible", $lang_select, $value);
		$res['buttons'] = array("submit" => array("title" => "OK", "submit" => true), "cancel");
		echo json_encode($res);	
	}
	public function Ajax_UpsalesUpdate() {
		$this->auto_render = false;
		
        $x = $this->input->post("x");
        $y = $this->input->post("y");
        $id = $this->input->post("id");
        $name = $this->input->post("name");
        $value = $this->input->post("value");      
        
		$r = dbClass::load('promo_upsale', "@id = $id", 1);
        $r->$name = $value;
        $r->save();
        
        $res = array(
            "x" => $x,
            "y" => $y,
            "content" => $r->priority,
            "value" => $value
        );
        
        echo json_encode($res);
	}

    public function Ajax_UpsaleHandler($id, $action) {
        $dialog = new \stdClass();
        if ($action == "save" || $action == "close") {
            $reload = $action == "save";
            $this->save_upsale($id);

			$msg = "Upsale saved";

            $dialog->content = "<div class='admin_message success'>$msg</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
                
        if ($reload) {
            $this->EditUpsale($id);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }                
        
        echo json_encode($dialog);           
    } 


}


