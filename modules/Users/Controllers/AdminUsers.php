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
use Sloway\arrays;
use Sloway\url;
use Sloway\userdata;
use Sloway\admin;
use Sloway\mlClass;
use Sloway\dbClass;
use Sloway\genClass;
use Sloway\settings;
use Sloway\account;
use Sloway\acontrol;
use Sloway\dbModel;
 
class AdminUsers extends AdminController {
	public $_lists = array();
    public $module = 'users';
    public $search_fields = "username, email, firstname, lastname, street, zipcode, city"; 
	
	protected function check_sort($name, $model) {
		$default = null;
		foreach ($model as $ops) {
			if (is_null($default)) $default = $ops["id"];
			if ($ops["id"] == $name && $ops["sort"]) return $name;
		}

		return $default;;
	}
    protected function users_model() {
        $res = array(
            "username" => array(
                "id" => "username", 
                "width" => "content", 
                "edit" => "text",
                "edit_click" => false,
                "sort" => true,
                "edit_grip" => "div",
                "content" => et("Username"),
                "search" => true,
            ),
            "flags" => array(
                "id" => "flags",    
                "width" => 100,
                "content" => et("Flags"),
                "edit" => "custom", 
            ),
			"lists" => array(
                "id" => "lists",
				"edit" => "custom", 
                "content" => et("Lists"),
			),
            "email" => array(
                "id" => "email",
                "sort" => true,
                "width" => "content",
                "content" => et("E-mail"),
                "search" => true,
            ),
			"name" => array(
                "id" => "name",
                "sort" => true,
                "width" => "content",
                "content" => et("Name"),
                "search" => true,
            ),
            "company" => array(
                "id" => "company",
                "sort" => true,
                "width" => "content",
                "content" => et("Company"),
                "search" => true,
            ),
            "address" => array(
                "id" => "address",
                "sort" => true,
                "width" => "content",
                "content" => et("Address"),
                "search" => true,
            ),
            "reg_date" => array(
                "id" => "reg_date",
                "sort" => true,
                "width" => "content",
                "content" => et("Registered"),
            ),
            "modified" => array(            
                "id" => "modified", 
                "width" => "content", 
                "align" => "left",
                "content" => et("Modified"),
                "can_hide" => false 
            ),
            "menu" => array(
                "id" => "menu", 
                "width" => 80, 
                "align" => "right",
                "fixed" => true,
                "content" => "", 
				"align" => "right",
            )
        );   
		if (!config::get("catalog.lists"))
			unset($res["lists"]);

		return array_values($res);
    }
    protected function users_row($user) {
        $menu = Admin::IconB("icon-edit.png", false, t("Edit"), "user_edit($user->id)");
        $menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "user_delete($user->id)");
        
        $flags = array();            
        foreach (arrays::explode(",", $user->flags) as $flg) 
            $flags[] = et("admin_user_" . $flg);
        
        if ($user->status) {
            $st = "checked disabled";
            $title = t("User confirmed");                
        } else {
            $st = "style='cursor: pointer'";
            $title = t("Confirm user");
        }    

		$lists = "";
		if (config::get("catalog.lists")) {
			if ($user->lists_inc) {
				$lids = explode(",", $user->lists_inc);
				foreach ($lids as $lid)
					if (isset($this->_lists[$lid]))
						$lists.= "<span class='user_list_inc'>" . $this->_lists[$lid]->title . "</span>";
			}
			if ($user->lists_exc) {
				$lids = explode(",", $user->lists_exc);
				foreach ($lids as $lid)
					if (isset($this->_lists[$lid]))
						$lists.= "<span class='user_list_exc'>" . $this->_lists[$lid]->title . "</span>";
			}
		}
        
        $res = array(
            "username" => "<input type='checkbox' $st title='$title'>&nbsp;<a href='#' onclick='user_edit($user->id); return false'>$user->username</a>",
            "flags" => arrays::implode(", ", $flags),
			"lists" => $lists,
            "email" => $user->email,
            "name" => $user->firstname . " " . $user->lastname,
			"company" => $user->company,
            "address" => $user->street . ", " . $user->zipcode . " " . $user->city . ", " . \Sloway\countries::title($user->country),
            "reg_date" => \Sloway\utils::datetime($user->reg_date),
            "modified" => \Sloway\utils::modified($user->edit_time, $user->edit_user),
            "menu" => $menu        
        );

		return array_values($res);
    }
    

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
	{
		parent::initController($request, $response, $logger);
		
		if (config::get("catalog.lists"))
			$this->_lists = dbClass::load("catalog_list", "@id_parent = 0", 0, array("index" => "id"));
	}

    public function Index() {
        $this->filter = userdata::get_object("users_filter_", array("search", "from", "to")); 
        $this->model = $this->users_model();

		$this->module_content = view("\Sloway\Views\AdminUsers\Index", array("model" => $this->model));
        $this->module_menu = view("\Sloway\Views\AdminUsers\Toolbar", array("filter" => $this->filter));

		return $this->admin_view();
	}
    public function Ajax_Handler() {
        $this->auto_render = false;
        
        if ($delete = $this->input->post("delete")) 
            dbClass::create("account", $delete)->delete();
        
        $page = $this->input->post("page", 1) - 1;
        $perpage = $this->input->post("per_page", 20);
        $sort = $this->input->post("sort", "username");
		$sort = "neki";
        $sort_dir = $this->input->post("sort_dir", 1);
        
        if ($this->input->post("filter")) {
            $filter = new \stdClass();
            $filter->search = trim($this->input->post("filter_search"));
            $filter->from = strtotime($this->input->post("filter_from"));
            $filter->to = strtotime($this->input->post("filter_to"));
            userdata::set_object("users_filter_", $filter);        
        } else 
            $filter = userdata::get_object("users_filter_", array("search", "from", "to"));        
        
        $where = "";
        if ($filter->search) {
            $where = "WHERE CONCAT($this->search_fields) LIKE '%" . $filter->search . "%'";
            $page = 0;    
        }
        if ($filter->from)
            $where[]= "reg_date >= " . $filter->from;
        if ($filter->to)
            $where[] = "reg_date <= " . $filter->to;        
        
		$sort = $this->check_sort($sort, $this->users_model());
        if ($sort == "modified")
            $order = "ORDER BY edit_time"; else
            $order = "ORDER BY $sort";
        if ($sort_dir == 1)
            $order.= " ASC"; else
            $order.= " DESC";
            
        if ($focus = $this->input->post("focus")) {
            $this->db->query("SET @i = 0");
            $q = $this->db->query("SELECT pos FROM (SELECT id, @i:=@i+1 AS pos FROM `account` $where $order) as t WHERE t.id = ?", $focus);
            $page = intval($q[0]->pos / $perpage);
        }
            
        $q = $this->db->query("SELECT COUNT(id) as cnt FROM account $where")->getResult();
        $count = $q[0]->cnt;
        
        $start = $page * $perpage;
        $users = mlClass::load("account", "SELECT * FROM account $where $order LIMIT $start,$perpage");
        $result = array(
            "rows" => array(),
            "state" => array(
                "total" => $count,                
                "page" => $page + 1,
                "sort" => $sort,
                "sort_dir" => $sort_dir
            ),
        );            
        
        foreach ($users as $user) {
            $row = array(
                "id" => $user->id,
                "cells" => $this->users_row($user)
            );
            $result["rows"][] = $row;            
        }        
        
        echo json_encode($result);           
    }    
    public function Ajax_EditFlags($id) {
        $user = mlClass::load("account", "@id = $id", 1);
        if ($this->input->post("save")) {
            $user->flags = $this->input->post("user_flags");
            $user->save();

            $flags = array();            
            foreach (arrays::explode(",", $user->flags) as $flg) 
                $flags[] = et("admin_user_" . $flg);
                
            $res['close'] = true;
            $res['result'] = array(
                "id" => $id,
                "flags" => arrays::implode(", ", $flags)
            );
            echo json_encode($res);
            exit();
        }   
        
        $flags = array();
        foreach (config::get("users.flags") as $flag) 
            $flags[] = et("admin_user_" . $flag) . "{id=$flag}";
            
        $c = acontrol::checktree('user_flags', $flags, $user->flags, array("id" => "user_flags"));
        
		$res = new \stdClass();
        $res->title = et("Flags");
        $res->content = $c;
        $res->buttons = array("save" => array("align" => "left", "title" => t("Save"), "submit" => true), "cancel");
        
        echo json_encode($res);
    }  
    public function Ajax_Edit($id = 0) {
       
        $username = $this->input->post("username");
        $npassword = $this->input->post("npassword");
        $cpassword = $this->input->post("cpassword");
        
        if ($this->input->post("create") && !empty($username)) {
            $r = dbClass::post("account", $id);
            
            if (!empty($npassword) && $npassword == $cpassword)
                $r->password = account::encode($npassword);
            
            $r->save();
            
            $res['close'] = true;
            $res['result'] = $r->id;
            echo json_encode($res);
            
            exit;
        }
        
        $acc = dbClass::load_def("account", "@id = $id", 1);
        
        $res['title'] = ($id) ? et("Edit user") : et("Add user");
        $res['content'] = view("\Sloway\Views\AdminUsers\Edit", array("user" => $acc));
        
        $res['buttons'] = array("create" => array("title" => "OK", "submit" => true), "cancel");
        echo json_encode($res);                
    }    
    public function Ajax_Confirm($id) {
        $this->auto_render = false;
        
        $user = dbClass::load("account", "@id = $id", 1);
        $user->status = 1;
        $user->save();    
    }
    public function Ajax_EditLists($id) {
        $user = mlClass::load("account", "@id = $id", 1);
        if ($this->input->post("save")) {
            $user->lists_inc = $this->input->post("include");
            $user->lists_exc = $this->input->post("exclude");
            $user->save();

            $res['close'] = true;
			$res['result'] = $user->id;
            echo json_encode($res);
            exit();
        }   

		$lists = dbClass::load("catalog_list", "@id_parent = 0");

        $c = "<h3>" . et("Show items from lists") . "</h2>" . acontrol::checklist("include", arrays::regen($lists), $user->lists_inc);
        $c.= "<h3>" . et("Hide items from lists") . "</h2>" . acontrol::checklist("exclude", arrays::regen($lists), $user->lists_exc);

		$res = new \stdClass();
        $res->title = et("Lists");
        $res->content = $c;
        $res->buttons = array("save" => array("align" => "left", "title" => t("Save"), "submit" => true), "cancel");
        
        echo json_encode($res);
    }  

}
