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
use Sloway\dbModel;
use Sloway\lang;
 
class AdminPages extends AdminController {
	protected $module = "pages";

    protected function pages_model() {
        $res = array(
            array("id" => "title", "content" => et("Title"), "edit" => "text", "edit_grip" => "div", "edit_click" => false),
            array("id" => "flags", "content" => et("Flags"), "edit" => "custom"),
            array("id" => "modified", "content" => et("Modified")), 
            array("id" => "menu", "content" => "", "align" => "right", "fixed" => true, "width" => "150")
        );
        return $res;            
    }
    protected function pages_load($pages) {
        $rows = array();
        
        foreach ($pages as $page) {
            $icon = "<img class='admin_icon' src='" . \Sloway\utils::icon("icon-doc-white.png") . "'>";
            
            $menu = "";
			$menu.= Admin::IconB("icon-edit.png", "ajax:" . url::site("AdminPages/Edit/" . $page->id), t("Edit")); 
            $menu.= Admin::IconB("icon-add.png", false, t("Add"), "pages_add($page->id); return false");
            $menu.= Admin::IconB("icon-copy.png", false, t("Duplicate"), "pages_copy($page->id); return false");
            
            if (!$page->locked) 
				$menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "pages_delete($page->id)");
            
            $flags = array();            
            foreach (arrays::explode(",", $page->flags) as $flg) 
                $flags[] = et("admin_pages_" . $flg);

			$title = $page->title;
			if ($page->tags)
				$title.= " [" . $page->tags . "]";
            
            $row = array(
                "id" => $page->id,
                "attr" => "data-locked='" . intval($page->locked) . "'",
                "cells" => array(
                    array("content" =>  $icon . "<a href='" . url::site("AdminPages/Edit/" . $page->id) . "' onclick='return admin_redirect(this)'>$title</a>", "value" => $page->title),
                    arrays::implode(", ", $flags),
                    \Sloway\utils::modified($page->edit_time, $page->edit_user),
                    $menu
                ),
                "rows" => $this->pages_load(mlClass::load("pages", "@id_parent = " . $page->id . " ORDER BY id_order ASC"))
            );
            $rows[] = $row;            
        }
        
        return $rows;
    }
	protected function save_page($id) {
        $r = mlClass::post('pages', $id);
		foreach ($r->get_ml("title") as $lang => $title) {
			if (!strlen(trim($title)))
				return et("Title cannot be empty");
		}
		
        $r->save();

		Admin::GenerateUrls("page", $r); 
		$r->save();
		
		Admin::ImageList_Save('images', 'pages', $r->id);
		Admin::FileList_Save('files', 'pages', $r->id);
	}
    
    public function Index() {
        $this->model = $this->pages_model();
        $this->module_content = view("\Sloway\Views\AdminPages\Index", array(
			"dg_model" => $this->model
		));        
		return $this->admin_view();
    }
    public function Edit($id) {
		$this->lang_selector = false;
        $this->flags = array();
        foreach (config::get("admin.pages.flags") as $flag) 
            $this->flags[] = et("admin_pages_" . $flag) . "{id=$flag}";

        $this->page = mlClass::load('pages', "@id = " . $id, 1, null, "*");
        $this->images = images::load("pages", $id, false);
        $this->files = files::load("pages", $id, false);
        
        $this->module_path = array($this->page->title);
        $this->module_menu = Admin::EditMenu(array(
			"back" => "AdminPages",
			"view_url" => Admin::LoadUrls($this->page),
		));
        $this->module_content = view("\Sloway\Views\AdminPages\Edit", array(
			"page" => $this->page,
			"flags" => $this->flags,
			"images" => $this->images,
			"files" => $this->files,
		));

		return $this->admin_view();
    }    

    public function Ajax_Handler() {
        $this->auto_render = false;
        
        if ($id = $this->input->post("delete")) {
            dbModel::delete("page", "@id = '$id'");
		}
        
        if ($reorder = $this->input->post("reorder")) {
            $parent = $this->input->post("parent");
            $index = $this->input->post("index");    
            
            $q = $this->db->query("SELECT id,title,id_order FROM `pages` WHERE id_parent = ? ORDER BY id_order ASC", [$parent])->getResult(); 
            for ($i = 0; $i < count($q); $i++) {
                if ($i < $index)
                    $this->db->query("UPDATE `pages` SET id_order = ? WHERE id = ?", [$i, $q[$i]->id]); else
                    $this->db->query("UPDATE `pages` SET id_order = ? WHERE id = ?", [$i+1, $q[$i]->id]); 
            }
            $this->db->query("UPDATE `pages` SET id_parent = ?, id_order = ? WHERE id = ?", [$parent, $index, $reorder]);
        }
        
        $pages = mlClass::load("pages", "@id_parent = 0 ORDER BY id_order ASC");
        $result = array(
            "rows" => $this->pages_load($pages),
        );    
        echo json_encode($result);           
    }
    public function Ajax_AddPage($pid) {
        $title = $this->input->post("title");
        if ($title && ($this->input->post('create') || $this->input->post('create_edit'))) {
            $q = $this->db->query("SELECT MAX(id_order) as max FROM pages WHERE id_parent = ?", [$pid])->getResult();
            $id_order = count($q) ? $q[0]->max + 1 : 0;
            
            $r = mlClass::create("pages");
            $r->set("title", $title, "_all");
            $r->id_order = $id_order;
            $r->id_parent = $pid;
            $r->save();
			
			Admin::GenerateUrls("page", $r);
			$r->save();
            
            $res['close'] = true;
            $res['result'] = $this->input->post('create_edit') ? url::site("AdminPages/Edit/" . $r->id) : null;
            echo json_encode($res);
            
            exit;
        } 
        
        $res['title'] = ($pid) ? et("Add page") : et("Add subpage");
        $res['content'] = Admin::Field(et("Title"), acontrol::edit("title"));
        $res['buttons'] = array(
			"create" => array("title" => "Create", "submit" => true, "key" => 13), 
			"create_edit" => array("title" => "Create and edit", "submit" => true),			
			"cancel"
		);
        echo json_encode($res);                
    }
    public function Ajax_EditCell() {
        $this->auto_render = false;
        
        $x = $this->input->post("x");
        $y = $this->input->post("y");
        $id = $this->input->post("id");
        $name = $this->input->post("name");
        $value = $this->input->post("value");

        $page = mlClass::load("pages", "@id = " . $id, 1);
        $page->$name = $value;
        $page->save();
        
        if ($name == "title") {
            $icon = "<img src='" . \Sloway\utils::icon("icon-doc-white.png") . "' style='float: left; padding: 2px 2px 0 0'>";
            $content = $icon . "<a href='" . url::site("AdminPages/Edit/" . $page->id) . "'>" . $page->title . "</a>";
        } else
            $content = $value;
            
        $res = array(
            "x" => $x,
            "y" => $y,
            "content" => $content,
            "value" => $value,
        );
        
        echo json_encode($res);
    }  
    public function Ajax_EditFlags($id) {
        $page = mlClass::load("pages", "@id = $id", 1);
        if ($this->input->post("save")) {
            $page->flags = $this->input->post("page_flags");
            $page->save();
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            exit();
        }   
        
        $flags = array();
        foreach (config::get("admin.pages.flags") as $flag) 
            $flags[] = et("admin_pages_" . $flag) . "{id=$flag}";
            
        $c = acontrol::checktree('page_flags', $flags, $page->flags, array("id" => "page_flags"));
        
        $res = new \stdClass();
        $res->title = et("Flags");
        $res->content = $c;
        $res->buttons = array("save" => array("align" => "left", "title" => t("Save"), "submit" => true), "cancel");
        
        echo json_encode($res);
        
    }
    public function Ajax_PageHandler($id, $action) {
        $this->auto_render = false;
        
        $page = mlClass::post('pages', $id);
        
        $dialog = new \stdClass();
        $reload = false;
        if ($action == "save" || $action == "close") {
            $err = $this->save_page($id);
			if ($err) {
				$dialog->message = "<div class='admin_message failure'>" . $err . "</div>";
				echo json_encode($dialog); 
				exit();
			}
			
            $reload = $action == "save";
            $dialog->content = "<div class='admin_message success'>" . et("Page saved") . "</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
        
        if ($reload) {            
            $this->Edit($id);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }
                
        echo json_encode($dialog);      
    } 
    public function Ajax_Duplicate($id) {
        $res = array();
		$page = dbModel::load("page", "@id = '$id'", 1);
		
		if ($this->input->post('create')) {   
			$r = dbModel::duplicate("page", "@id = $id", 1);
			$r->title = $this->input->post('title'); 
			$r->content = preg_replace('~<ins data-cid=\'([a-z0-9\-]+)\'></ins>~si', "", $r->content);

			if (config::get("admin.generate_url.page"))
				$r->url = Admin::GenerateUrl($this->db, "page-" . $id, $r->meta_title ?: $r->title); 

			$r->save();
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            
            exit;            
		}
		
		$res['title'] = et("Duplicate");
		$res['content'] = Admin::Field(et("Title"), acontrol::edit("title", $page->title . "-copy"));
		$res['buttons'] = array("create" => array("align" => "left", "title" => t("Create"), "submit" => true), "cancel");
		
		echo json_encode($res);
	}      
    
    public function Ajax_Browser() {
        $this->auto_render = false;
        
        if ($this->input->post("submit")) {
            $res["result"] = array();
            
            if ($checked = $this->input->post("pages")) {
                $checked = explode(",", $checked);
                foreach ($checked as $cid) {          
                    $page = mlClass::load("pages", "@id = " . $cid, 1);
                    $res["result"][$cid] = array(
                        "id" => $cid,
                        "title" => $page->title
                    );
                }
            }
            $res["result"] = array_values($res["result"]);     
            $res["close"] = true;
            echo json_encode($res);            
            exit;
        } 
        
        $pages = dbModel::load("page");
        $tree = acontrol::tree_items($pages, "sub_pages");
        $ops = array();
        $ops["paths"] = false;
        $ops["dependency"] = "0000";
        $ops["three_state"] = false;
        $ops["style"] = "position: absolute; top: 0; bottom: 0; left: 0; right: 0";
        
        $res["title"] = et("Choose pages"); 
        $res["postdata"] = http_build_query($_POST);
        $res["content"] = acontrol::checktree("pages", $tree, "", $ops);     
        $res["buttons"] = array("submit" => array("title" => "OK", "submit" => true), "cancel"); 
        
        echo json_encode($res);       
    }
    public function Ajax_TemplateItem() {
        $this->auto_render = false;
                              
        $result = array();
        $ids = $this->input->post("ids", array());
        $items = ($ids) ? mlClass::load("pages", "@id IN (" . implode(",", $ids) . ")") : array(); 
        
        foreach ($items as $item) {
            $img = images::load("page", $item->id, false, 0);
            $res = new stdClass();
            $res->title = $item->title;
            $res->image = count($img) ? thumbnail::create(null, $img[0]->path, null, "admin_gallery_96")->result : false;
            
            $result[$item->id] = $res;
        } 
        
        echo json_encode($result);    
    }    

	public function Test() {
		//$page = \Sloway\dbModel::load("page", "@id = '28'", 1);
		\Sloway\dbModel::delete("page", "@id = 28");
	}
}
