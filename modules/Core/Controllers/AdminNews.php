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
use Sloway\userdata;
use Sloway\files;
use Sloway\acontrol;
use Sloway\dbModel;
use Sloway\slug;
 
class AdminNews extends AdminController {
    public $module = 'news';
    public $load_module = "";
    public $load_module_id = 0;
    
    protected function news_model() {
        $res = array(
            array(
                "id" => "id",
                "sort" => true,
                "content" => "#",
				"width" => 50,
            ),
            array(
                "id" => "title", 
                "sort" => true,
                "content" => et("Title"),
                "width" => 300
            ),
            array(
                "id" => "visible",
                "width" => "content",
                "content" => t("Visible"),
            ),
            array(
                "id" => "date",
                "sort" => true,
                "content" => et("Date"),
            ),
            array(
                "id" => "modified", 
                "align" => "left",
                "content" => et("Modified"),
            ),
            array(
                "id" => "menu", 
                "align" => "right",
                "fixed" => true,
				"width" => 80,
                "content" => "",
            )
        );
        
        return $res;
    }
	protected function save_news($id) {
        $r = mlClass::post('news', $id);
		foreach ($r->get_ml("title") as $lang => $title) {
			if (!strlen(trim($title)))
				return et("Title cannot be empty");
		}
		$r->date = strtotime($r->date);
		
        $r->save();

		Admin::GenerateUrls("news", $r); 
		$r->save();
		
		Admin::ImageList_Save('images', 'news', $r->id);
	}   
	
    public function Index() { 
        $this->filter = userdata::get_object("news_filter_", array("search")); 

        $this->model = $this->news_model();
		$this->module_menu = view("\Sloway\Views\AdminNews\Toolbar", array(
			"filter" => $this->filter
		));
        $this->module_content = view("\Sloway\Views\AdminNews\Index", array(
			"dg_model" => $this->model
		));        
		return $this->admin_view();
   
    }
    public function Edit($id) {
        $this->news = mlClass::load('news', "@id = " . $id, 1, null, "*");
        $this->images = images::load('news', $id, false);
        
        $this->module_path = array($this->news->title => "");
        $this->module_menu = Admin::EditMenu(array(
			"back" => "AdminNews",
			"view_url" => Admin::LoadUrls($this->news),
		));
        $this->module_content = view("\Sloway\Views\AdminNews\Edit", array(
			"news" => $this->news,
			"images" => $this->images,
		));

		return $this->admin_view();
    }    

    public function Ajax_Handler() {
        if ($id = $this->input->post("delete")) {
            dbModel::delete("news", "@id = '$id'");
		}
        
        $page = $this->input->post("page", 1) - 1;
        $perpage = $this->input->post("per_page", 20);
        $sort = $this->input->post("sort", "date");
        $sort_dir = $this->input->post("sort_dir", 1);
        
        if ($this->input->post("filter")) {
            $filter = new \stdClass();
            $filter->search = trim($this->input->post("filter_search"));
            userdata::set_object("news_filter_", $filter);        
        } else 
            $filter = userdata::get_object("news_filter_", array("search"));            
        
        $order = "ORDER BY $sort";
        if ($sort_dir == 1)
            $order.= " ASC"; else
            $order.= " DESC";
        
        $where = "module = '$this->load_module' AND module_id = '$this->load_module_id'";
        if ($terms = $filter->search)
            $where.= " AND (title LIKE '%$terms%' OR content LIKE '%$terms%')";
            
        $q = $this->db->query("SELECT COUNT(id) as cnt FROM news WHERE $where")->getResult();
        $count = $q[0]->cnt;
        
        if ($page * $perpage > $count)
            $page = 0;
        
        $start = $page * $perpage;
        if ($page * $perpage >= $count)
            $page = 0;        
        
        $news = mlClass::load("news", "@$where $order LIMIT $start,$perpage");
        $result = array(
            "rows" => array(),
            "state" => array(
                "total" => $count,
                "page" => $page + 1,
                "sort" => $sort,
                "sort_dir" => $sort_dir
            ),
            "debug" => "@$where $order LIMIT $start,$perpage"
        );            
        
        foreach ($news as $n) {
			$edit_url = url::site("AdminNews/Edit/" . $n->id);
			$menu = Admin::IconB("icon-edit.png", "ajax:" . $edit_url, t("Edit"));
			$menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "news_delete($n->id)");
            
            $row = array(
                "id" => $n->id,
                "cells" => array(
                    $n->id,
                    "<a href='" . url::site("AdminNews/Edit/" . $n->id) . "' onclick='return admin_redirect(this)'>$n->title</a>",
                    Admin::VisibilityBar($n->visible, "news_visible('$n->id')"),
                    \Sloway\utils::date($n->date),
                    \Sloway\utils::modified($n->edit_time, $n->edit_user),
                    $menu
                ),
            );
            $result["rows"][] = $row;            
        }        
        
        echo json_encode($result);           
    }
    public function Ajax_AddNews() {
        $this->auto_render = false;
                                             
        $title = $this->input->post("title");
        if ($title && ($this->input->post('create') || $this->input->post('create_edit'))) {
            $r = mlClass::create("news"); 
            $r->module = $this->load_module;   
            $r->module_id = $this->load_module_id;                             
            $r->set("title", $title, "_all");
            $r->flags = "";
            $r->date = time();
            $r->save();

			Admin::GenerateUrls("news", $r);
			$r->save();            
			
            $res['close'] = true;
            $res['result'] = $this->input->post('create_edit') ? url::site("AdminNews/Edit/" . $r->id) : null;
            echo json_encode($res);
            
            exit;
        }                              
        
        $res['title'] = et("Add news");
        $res['content'] = Admin::Field(et("Title"), acontrol::edit("title"));
        $res['buttons'] = array(
			"create" => array("title" => "Create", "submit" => true, "key" => 13), 
			"create_edit" => array("title" => "Create and edit", "submit" => true),			
			"cancel"
		);
        echo json_encode($res);                
    }
    public function Ajax_EditFlags($id) {
        $page = dbClass::load("news", "@id = $id", 1);
        if ($this->input->post("save")) {
            $page->flags = $this->input->post("ref_flags");
            $page->save();
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            exit();
        }   
        
        $flags = $this->flags();
        $c = acontrol::checktree('ref_flags', $flags, $page->flags, array("id" => "ref_flags"));
        
        $res = new \stdClass();
        $res->title = et("Flags");
        $res->content = $c;
        $res->buttons = array("save" => array("align" => "left", "title" => t("Save"), "submit" => true), "cancel");
        
        echo json_encode($res);
    }    
    public function Ajax_NewsHandler($id, $action) {
        $news = mlClass::post('news', $id);
        
        
        $dialog = new \stdClass();
        $reload = false;
        if ($action == "save" || $action == "close") {
            $err = $this->save_news($id);
			if ($err) {
				$dialog->message = "<div class='admin_message failure'>" . $err . "</div>";
				echo json_encode($dialog); 
				exit();
			}
            
            $reload = $action == "save";
            $dialog->content = "<div class='admin_message success'>" . et("News saved") . "</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }  
        
        if ($reload) {        
            $this->Edit($news->id);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }
        
        echo json_encode($dialog);      
    } 
    
    public function Ajax_Browser() {
        $this->auto_render = false;
        
        if ($this->input->post("submit")) {
            $res["result"] = array();
            
            if ($checked = $this->input->post("checked")) {
                $checked = explode(",", $checked);
                foreach ($checked as $cid) {          
                    $page = mlClass::load("news", "@id = " . $cid, 1);
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
        
        $model = array(
            array('id' => 'id', 'content' => et('#'), 'sort' => true),
            array('id' => 'title', 'content' => et('Title'), 'sort' => true),
            array('id' => 'date', 'content' => et('Date'), 'sort' => true),
        );
        
        $res["title"] = et("Choose news"); 
        $res["postdata"] = http_build_query($_POST);
        $res["content"] = buffer::view("AdminNews/Browser", array("model" => $model));
        $res["buttons"] = array("submit" => array("title" => "OK", "submit" => true), "cancel"); 
        
        echo json_encode($res);       
    }
    public function Ajax_TemplateItem() {
        $this->auto_render = false;
                              
        $result = array();
        $ids = $this->input->post("ids", array());
        $items = ($ids) ? mlClass::load("news", "@id IN (" . implode(",", $ids) . ")") : array(); 
        
        foreach ($items as $item) {
            $img = images::load("news", $item->id, false, 0);
            $res = new stdClass();
            $res->title = $item->title;
            $res->image = count($img) ? thumbnail::create(null, $img[0]->path, null, "admin_gallery_96")->result : false;
            
            $result[$item->id] = $res;
        } 
        
        echo json_encode($result);    
    }           
    
}
