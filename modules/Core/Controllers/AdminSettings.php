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
use Sloway\acontrol;
use Sloway\account;
use Sloway\dbModel;
use Sloway\lang;
				  
class AdminSettings extends AdminController {
	protected $module = "settings";

    protected function roles_load($roles) {
        $result = array();
        foreach ($roles as $role) { 
	        $menu = Admin::IconB("icon-edit.png", false, t("Edit"), "edit_role($role->id)");
	        $menu.= Admin::IconB("icon-add.png", false, t("Add"), "add_role($role->id)");
            $menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "delete_role($role->id)");
            $res = array(
                "id"   => $role->id,
                "cells" => array(
                    array("content" => $role->name),
                    array("content" => \Sloway\utils::modified($role->edit_time, $role->edit_user)),
                    array("content" => $menu),
                ),
                "rows" => $this->roles_load($role->roles)
            );
            
            $result[] = $res;
        }
        
        return $result;
    }

    protected function parse_tree($items, $perm, $parent = null, $prefix = "") {
        $res = array();
        foreach ($items as $key => $val) {
            if (is_int($key)) {
                $item = $val;
                $children = array();
            } else {
                $item = $key;
                $children = $val;    
            }
                                                  
            $node = acontrol::parse_node($item);
            $id = v($node["attr"], "id", null);
            if ($id) {
                $pid = trim($prefix . "." . $id, ".");
                if (strpos("," . $perm . ",", "," . $pid . ",") === false) 
                    continue;    
            } else
                $pid = $prefix;
                
            if (is_int($key)) 
                $res[$key] = $item; else
                $res[$key] = $this->parse_tree($children, $perm, $item, $pid);
        }
        return $res;
    }

    protected function permissions_prop($role, $perm = null) {
        if (!$perm)
            $perm = $role->permissions;
        
        foreach ($role->roles as $sub) {
            echob("CHECK ROLE: ", $sub->name, ' ', $sub->permissions);
            $sub_perm = array();
            foreach(explode(",", $sub->permissions) as $rule) { 
                echob(" - check: $rule = ", strpos("," . $perm . ",", "," . $rule . ","));
                if (strpos("," . $perm . ",", "," . $rule . ",") !== false)
                    $sub_perm[] = $rule;
            }
            
            $sub->permissions = implode(",", $sub_perm);
            $sub->save();
            
            $this->permissions_prop($sub, $perm);
        }        
    }
    protected function permissions_tree($role) {
        $perm = config::get("admin.permissions");
        
        if (!$role->id_parent) 
            return $perm;
            
        $parent = dbClass::load("admin_role", "@id = " . $role->id_parent, 1);
        $perm = $this->parse_tree($perm, $parent->permissions);
        
        return $perm;
    }
    
    protected function save_settings() {
        $cfg = config::get('admin.settings');
        foreach ($cfg as $name) {
            if ($name == "__separator__") continue;
            
            $e = explode("?", $name, 2);
            $name = $e[0];
            $ops = (count($e) > 1) ? arrays::options($e[1]) : array();
            
            $type = \Sloway\utils::value($ops, "type", "edit");
            $ml = \Sloway\utils::value($ops, "ml", false);
            
            $r = mlClass::load_def("content", "@name = '$name' AND module = 'settings'", 1); 
            $r->name = $name;
            $r->module = "settings";
			$r->read_post([$name => "content"]);
            
            if ($type == "img") {
				if ($pth = v($r->content, "0.path")) {
					$c = new \stdClass;
					$c->path = v($r->content, "0.path");
					$c->link = v($r->content, "0.link");
					$c->title = v($r->content, "0.title");
					$c->visible = v($r->content, "0.visible");

					$r->content = json_encode($c);
				} else
					$r->content = "";
            }         
            
            $r->save();     
        }    
    }
    protected function save_profile() {
        $username = $this->input->post("username");
        if ($this->admin_user->username != $username) {       
            $q = $this->db->query("SELECT id FROM admin_user WHERE username = ?", $username);
            if (!count($q)) 
                $this->admin_user->username = $username; else
                return et_rep("User with username '%USERNAME%' already exists!", array("%USERNAME%" => $username));
        }
        
        $npassword = $this->input->post("npassword");
        $cpassword = $this->input->post("cpassword");
        if ($npassword || $cpassword) { 
            if ($npassword == $cpassword) 
                $this->admin_user->password = account::encode($npassword); else
                return et("Password doesn't match");
        } 
        
        $this->admin_user->save();
        return false;
    }
    protected function messages_config() {
        $this->config = array();
        $this->variables = array();
        foreach (config::get("messages") as $module => $ops) {
            if ($module == "variables")
                $this->variables = array_merge($this->variables, $ops); else
                $this->config[$module] = $ops;       
        }
    }  
    
    protected function navigation_model() {
        $res = array(
            array("id" => "type", "content" => et("Type") . "&nbsp;"),
            array("id" => "visible", "content" => et("Visible") . "&nbsp;"),
            array("id" => "value", "content" => et("Value") . "&nbsp;", "edit" => "custom"),
            array("id" => "modified", "content" => et("Modified") . "&nbsp;"), 
            array("id" => "menu", "align" => "right", "fixed" => true, "width" => "content")
        );
        return $res;            
    }
    protected function navigation_items($items) {
        $this->config = config::get("navigation");
        $rows = array();
        
        foreach ($items as $item) {
            $icon = "<img class='admin_icon' src='" . \Sloway\utils::icon("icon-doc-white.png") . "'>";
            
            $menu = "";
            $menu.= Admin::IconB("icon-edit.png", false, t("Edit"), "navigation_edit($item->id)");
            $menu.= Admin::IconB("icon-add.png", false, t("Add"), "navigation_edit(0, $item->id)");
            $menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "navigation_delete($item->id)");
            
            $flags = array();            
            foreach (arrays::explode(",", $item->flags) as $flg) 
                $flags[] = et("nav_flag_" . $flg);
                
            $value = "";
            if ($item->type != "static") {
                $name = v($this->config, "types.$item->type.model");
                $obj = dbModel::load($name, "@id = '" . $item->id_ref . "'", 1);
                
                if ($ts = v($this->config, "types.$item->type.title_sel", "title"))
                    $obj_title = v($obj, $ts, ""); else
                    $obj_title = "";
                
                if ($item->title)
                    $value = $item->title; else
                    $value = $obj_title;
                
                $value.= " (" . $obj_title . $item->param . ")";
            } else 
                $value = $item->title . " (" . $item->url . ")";
            
            $chk = ($item->visible) ? "checked" : ""; 
            $row = array(
                "id" => $item->id,
                "attr" => "data-ntype='" . $item->type . "'",
                "cells" => array(
                    $icon . t("nav_type_" . $item->type),
                    "<input type='checkbox' name='visible' $chk onclick='navigation_set_visible(\$(this))' data-id='$item->id' style='cursor: pointer'>",
                    $value,
                    \Sloway\utils::modified($item->edit_time, $item->edit_user),
                    $menu
                ),
                "rows" => $this->navigation_items(mlClass::load("navigation", "@id_parent = " . $item->id . " ORDER BY id_order ASC"))
            );
            $rows[] = $row;            
        }
        
        return $rows;
    }
    protected function navigation_build($cfg, $items) {
        $result = array();
        foreach ($items as $item) {
            $id_sel = v($cfg, "id_sel", "id");
            $ch_sel = v($cfg, "ch_sel", "children");
            $title_sel = v($cfg, "title_sel", "title");
            
            $id = v($item, $id_sel, 0);
            $ch = v($item, $ch_sel, array());
            $title = v($item, $title_sel, "");
            
            $node = $title . "{id=" . $id . "}";
            $result[$node] = array();
            if ($ch)
                $result[$node] = $this->navigation_build($cfg, $ch);
        }
        
        return $result;
    }
    
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
	{
		parent::initController($request, $response, $logger);
        
        $this->module_tabs = array(
            'profile'    => "<a href='" . url::site("AdminSettings/Profile") . "' onclick='return admin_redirect(this)'>" . et("Profile") . "</a>",
            'settings'   => "<a href='" . url::site("AdminSettings/Settings") . "' onclick='return admin_redirect(this)'>" . et('Settings') . "</a>",
            'users'      => "<a href='" . url::site("AdminSettings/Users") . "' onclick='return admin_redirect(this)'>" . et('Users') . "</a>",
            'messages'   => "<a href='" . url::site("AdminSettings/Messages") . "' onclick='return admin_redirect(this)'>" . et('Messages') . "</a>",
            'navigation' => "<a href='" . url::site("AdminSettings/Navigation") . "' onclick='return admin_redirect(this)'>" . et('Navigation') . "</a>",
            'redirects'  => "<a href='" . url::site("AdminSettings/Redirects") . "' onclick='return admin_redirect(this)'>" . et('Redirects') . "</a>",
//            'meta_pixel_conversion_api'  => "<a href='" . url::site("AdminSettings/MetaPixelAndConversionApi") . "' onclick='return admin_redirect(this)'>" . et('Meta pixel and conversion api') . "</a>",
        );
        $this->tabs_method = array(
            'settings' => "Settings",
            'profile' => "Profile",
            'users' => "Users"
        );
        
        //if (!admin::auth("settings.users")) unset($this->tabs["users"]);
        //if (!admin::auth("settings.settings")) unset($this->tabs["settings"]);
		
		$this->module_tabs_curr = arrays::first($this->module_tabs, true);
	}
	public function Index() {
		$this->Profile();
	}
	public function Profile() {
		$this->module_path = array(et('Profile') => ''); 
        
        $this->module_menu = Admin::EditMenu();
		$this->module_content = Admin::Tabs($this->module_tabs, "profile", view("Sloway\Views\AdminSettings\Profile", array("username" => $this->admin_user->username)));
		
		return $this->admin_view();
	}
	public function Settings() {
        // Admin::auth("settings.settings", $this);
		$this->path = array(); 

		$this->settings = array();
        $this->images = array();
		$this->config = config::get('admin.settings');
        
        $this->google_maps = array();
        $sep_cnt = 0;           
		foreach ($this->config as $cfg) {
            if ($cfg == "__separator__") {
                $r = new genClass();
                $r->type = "separator";
                $this->settings["sep" . $sep_cnt] = $r;
                $sep_cnt++;       
                
                continue;             
            }
            
			$e = explode("?", $cfg, 2);
			$name = $e[0];
			$ops = (count($e) > 1) ? arrays::options($e[1]) : array();
            
			$type = \Sloway\utils::value($ops, "type", "edit");
			$ml = \Sloway\utils::value($ops, "ml", false);
            
			$s = mlClass::load_def("content", "@name = '$name' AND module='settings'", 1, null, "*"); 
			$s->ml = $ml;
			$s->title = et(ucfirst($name));
			$s->type = $type;
            
            if ($type == "gmap") {
                $s->gmap_coord = Settings::get($name . "_coord");
                $this->google_maps[] = $name;
            } else
            if ($type == "img") {
                if ($s->value) {
                    $v = json_decode($s->value);
                    $img = new \stdClass();
					$img->id = 0;
                    $img->path = v($v, "path");
                    $img->visible = v($v, "visible");
                    $img->link = v($v, "link");
                    $img->title = v($v, "title");
                    
                    $s->image = array($img);
                } else
                    $s->image = array();
            }                

            if ($type == "img")
                $this->images[$name] = $s; else
			    $this->settings[$name] = $s;
		}    
        
		$this->module_menu = admin::EditMenu();
		$this->module_content = admin::tabs($this->module_tabs, "settings", view("Sloway\Views\AdminSettings\Settings", array(
			"settings" => $this->settings,
			"google_maps" => $this->google_maps,
			"images" => $this->images,
		)));
		
		return $this->admin_view();
	}
	public function Users() {
        // Admin::auth("settings.users", $this);
        
        $this->users_header = array(
            "title"     => array('title' => et("Username"), "width" => 200),
            "perm"      => array('title' => et("Permissions"), "width" => 200, "edit" => "custom"),
            "login"     => array('title' => et("Last login"), "width" => 100),
            "modified"  => array('title' => et("Modified"), "width" => 100),
            "menu"      => array('title' => "<a href='#' onclick='users_add(); return false'>" . et("Add") . "</a>", "align" => "right"),
        );
		
		$this->users = dbClass::load('admin_user');
		
		$this->module_path = array(et('Users') => '');
		$this->module_content = Admin::Tabs($this->module_tabs, "users", view("Sloway\Views\AdminSettings\Users", array(
			"users" => array(), 
			"users_header" => $this->users_header)
		));

		return $this->admin_view();
	}
    public function Messages() {
        $this->messages_config();
        $modules =  array();
        
        $icon_gray = "<img src='" . \Sloway\utils::icon("icon-doc-gray.png") . "' style='float: left; padding: 2px 2px 0 0'>";
        $icon_white = "<img src='" . \Sloway\utils::icon("icon-doc-white.png") . "' style='float: left; padding: 2px 2px 0 0'>";
        foreach ($this->config as $module => $ops) {
            $msg_h = mlClass::load_def("content", "@module='messages_$module' AND name='template_header'", 1)->content;
            $msg_f = mlClass::load_def("content", "@module='messages_$module' AND name='template_footer'", 1)->content;            
            if ($msg_h && $msg_f)
                $color = "darkgreen"; else
            if ($msg_h || $msg_f)
                $color = "orange"; else
                $color = "darkred";
            
            $m = array(
                "icon" => "<img src='" . v($ops, "icon", \Sloway\utils::icon("icon-doc-white.png")) . "' style='float: left; padding: 2px 2px 0 0'>",
                "title" => et("messages_" . $module),
                "name" => $module,
                "style" => "color: $color",
                "variations" => array(),
                "url" => url::site("AdminSettings/EditTemplate/$module"),
            );

            foreach ($ops['variations'] as $var) {
                $q = $this->db->query("SELECT * FROM content WHERE module = 'messages_$module' AND name = '$var'")->getResult();
                $msg_c = count($q) ? $q[0]->content : false;
                $msg_t = count($q) ? $q[0]->title : false;
                if ($msg_c && $msg_t)
                    $color = "darkgreen"; else
                if ($msg_c || $msg_t)
                    $color = "orange"; else
                    $color = "darkred";
                    
                $m["variations"][] = array(
                    "icon" => $icon_white,
                    "style" => "color: $color",
                    "name" => $module . "." . $var,
                    "title" => et("messages_{$module}_{$var}"),
                    "url" => url::site("AdminSettings/EditMessage/$module/$var"),                    
                );
            }
            $modules[] = $m;
        }       
        
		$this->module_path = array(et('Messages') => '');
        $this->module_content = Admin::tabs($this->module_tabs, "messages", view("\Sloway\Views\AdminSettings\Messages\Index", array(
			"modules" => $modules
		)));

		return $this->admin_view();
    }
    public function Redirects() {
        //Admin::auth("settings.redirects", $this);
        
        $this->model = array(
            array("id" => "source", "content" => et("From")),
            array("id" => "target", "content" => et("To")),
            array("id" => "menu", "content" => "", "align" => "right", "width" => 80)
        );
        
        //$this->tab_curr = 'redirects';
        //$this->tab_content = new View("AdminSettings/Redirects");

		$this->module_path = array(et('Redirects') => '');
        $this->module_content = Admin::tabs($this->module_tabs, "redirects", view("\Sloway\Views\AdminSettings\Redirects", array(
			"model" => $this->model
		)));
        
		return $this->admin_view();
        //Admin::StoreRecent("Edit redirects");
    }      
    public function Navigation($name = null) {
        $this->nav_config = config::get("navigation");     
         
        $this->nav_types = array("static" => et("nav_type_static"));
        foreach ($this->nav_config["types"] as $type => $ops)
            $this->nav_types[$type] = et("nav_type_$type");
        
        if (!$name)
            $name = reset($this->nav_config["modules"]);
        
        $this->nav_name = $name;

        $this->nav_tabs = array();
        foreach ($this->nav_config["modules"] as $module)
            $this->nav_tabs["nav_module_" . $module] = "<a href='" . url::site("AdminSettings/Navigation/" . $module) . "' style='display: block' onclick='return admin_redirect(this)'>" . et("nav_module_" . $module) . "</a>";
            
        $this->model = $this->navigation_model();
        
		$this->module_path = array(et('Navigation') => '');
        $this->module_content = Admin::tabs($this->module_tabs, "navigation", view("\Sloway\Views\AdminSettings\Navigation\Index", array(
			"model" => $this->model,
			"nav_tabs" => $this->nav_tabs,
			"nav_tabs_curr" => "nav_module_" . $name,
			"nav_name" => $this->nav_name,
		)));
		return $this->admin_view();
    }
    
    public function Ajax_RedirectsHandler() {
        $this->auto_render = false;

        $page = $this->input->post("page", 1) - 1;
        $perpage = $this->input->post("per_page", 20);
        $sort = $this->input->post("sort", "id_order");
        $sort_dir = $this->input->post("sort_dir", 1);
        
        $order = "ORDER BY $sort";
        if ($sort_dir == 1)
            $order.= " ASC"; else
            $order.= " DESC";
            
        $redirects = \Sloway\redirect_set::create($this->session);
        if (isset($_POST["delete"])) {
            $redirects->delete($_POST["delete"]);
        }
        
        $result = array(
            "rows" => array(),
            "state" => array(
                "total" => $redirects->total(),
                "page" => $page + 1,
                "sort" => $sort,
                "sort_dir" => $sort_dir            
            ),
            "data" => array(
                "committed" => $redirects->committed
            ),
        );            
        
        foreach ($redirects->items($page, $perpage) as $src => $dst) {
            $menu = Admin::IconB("icon-edit.png", false, t("Edit"), "redirect_edit.apply(this)");
            $menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "redirect_delete.apply(this)");
            
            $row = array(
                "id" => $src,
                "cells" => array(
                    $src,
                    $dst,
                    $menu
                ),
               // "rows" => $rows,
            );
            $result["rows"][] = $row;            
        }        
        
        echo json_encode($result);           
    }       
    public function Ajax_RedirectsRevert() {
        $this->auto_render = false;
        
        Session::instance()->delete("redirect_set");
    }       
    public function Ajax_RedirectsCommit() {
        $this->auto_render = false;
        
        redirect_set::create()->commit();
        sleep(2);
    }      
    public function Ajax_EditRedirect() {
        $redirects = \Sloway\redirect_set::create($this->session);
        $src = $this->input->post("src");
        $dst = $redirects->get($src);
        
        if ($this->input->post("save")) {
            $redirects->update($this->input->post("source"), $this->input->post("target"));
            $redirects->commit();
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            
            exit;
        } 
        
        $res['title'] = et("Edit redirect");
        $res['content'] = Admin::Field(et("From"), acontrol::edit("source", $src)) . Admin::Field(et("To"), acontrol::edit("target", $dst));
        $res['buttons'] = array("save" => array("title" => "OK", "submit" => true, "key" => 13), "cancel");
        echo json_encode($res);                
    }
    public function Ajax_ImportRedirects() {
        $this->auto_render = false;    
        
        if (count($_FILES)) {
            $path = $_FILES["files"]["tmp_name"];
            $name = $_FILES["files"]["name"];    
            
            @mkdir(APPPATH . "temp"); 
            
            $name = APPPATH . "temp/" . $name;
            @unlink($name);
            @copy($path, $name);
            
            exit($name);
        }
        
        $path = $this->input->post("path");
        
        include MODPATH . 'Excel/libraries/PHPExcel/IOFactory.php';
        $type = PHPExcel_IOFactory::identify($path);
        if ($type == "CSV") {
            $lines = array();
            $fh = fopen($path, "r");
            while(!feof($fh)) {
                $line = trim(fgets($fh));
                $lines[]= preg_split('/[^a-z0-9]+/i', $line);
            }
            fclose($fh);            
        } else {
            $doc = PHPExcel_IOFactory::load($path);
            $lines = $doc->getActiveSheet()->toArray();        
        }        

		$project_domain = $_SERVER["PROJECT_DOMAIN"];
        
        $redirects = new redirect_set();
        foreach ($lines as $line) {
            if (count($line) < 2) continue;
            
            $key = trim($line[0], "/ ");
            $val = trim($line[1], "/ ");
            
            $key = strtr($key, array("http://" . $project_domain => "", "https://" . $project_domain => ""));
            $val = strtr($val, array("http://" . $project_domain => "", "https://" . $project_domain => ""));
            
            if (!strlen($key) || !strlen($val)) continue;
            
            $redirects->_items[$key] = $val;
        }
        $redirects->commit();
        
        $res = array();
        $res['title'] = et("Import redirects");
        $res['content'] = "<div class='admin_message success'>Redirect list successfuly imported</div>";
        $res['buttons'] = array("ok");
        echo json_encode($res);        
    }    
    
    public function Ajax_SettingsHandler($action) {
        $dialog = new \stdClass();
        $reload = false;
        if ($action == "save" || $action == "close") {
            $this->save_settings();
            
            $reload = $action == "save";
            $dialog->content = "<div class='admin_message success'>Settings saved</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
        
        if ($reload) {            
            $this->Settings();
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }
                
        echo json_encode($dialog);      
    } 

	public function Ajax_ProfileHandler($action) { 
        $this->auto_render = false;
      
        $dialog = new \stdClass();
        $error = $this->save_profile();
        if ($error) {
            $dialog->content = "<div class='admin_message failure'>" . $error . "</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        } else { 
            // $dialog->reload = true;
            $dialog->content = "<div class='admin_message success'>Profile saved</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
        
        echo json_encode($dialog);
	}
    public function Ajax_UsersHandler() {
        $page = $this->input->post("page", 1) - 1;
        $perpage = $this->input->post("per_page", 20);
        
        if ($delete = $this->input->post("delete")) {
            $r = dbClass::create("admin_user", $delete);
            $r->delete();
        }

        $level = $this->admin_user->level;
        $where = "LEFT JOIN admin_role ON admin_user.id_role = admin_role.id WHERE COALESCE (LEVEL, 0) >= $level";
        
        $q = $this->db->query("SELECT COUNT(admin_user.id) as count FROM admin_user " . $where)->getResult();
        $count = $q[0]->count;
        
        $start = $page * $perpage;
        if ($page * $perpage >= $count)
            $page = 0;            
        $users = dbClass::load("admin_user", "SELECT admin_user.*, admin_role.level FROM admin_user $where LIMIT $start,$perpage");
        
        $result = array(
            "rows" => array(),
            "state" => array(
                "total" => $count,
                "page" => $page + 1,   
            ),
        );    
        foreach ($users as $user) { 
            $menu = Admin::IconB("icon-edit.png", false, t("Edit"), "edit_user($user->id)");
            $menu.= Admin::IconB("icon-delete.png", false, t("Delete"), "delete_user($user->id)");
            
            $role = mlClass::load("admin_role", "@id = '" . $user->id_role . "'", 1);
            if ($role)
                $role_name = $role->name; else
            if ($user->id_role == 0)
                $role_name = "Superuser"; else
                $role_name = "Unknown";
            
            $res = array(
                "id"   => $user->id,
                "cells" => array(
                    array("content" => $user->username),
                    array("content" => $role_name),
                    array("content" => \Sloway\utils::datetime($user->last_login)),
                    array("content" => \Sloway\utils::modified($user->edit_time, $user->edit_user)),
                    array("content" => $menu),
                )
            );
            $result["rows"][] = $res;
        }
        
        echo json_encode($result);   
    }    
	public function Ajax_EditUser($id = 0) {
		$this->auto_render = false;
		$this->user_id = 0;
		
        $user = dbClass::load_def("admin_user", "@id = $id", 1);
        $msg = "";
		if ($this->input->post("save")) {
			$u = trim($this->input->post('username'));
			$p = trim($this->input->post('npassword'));
			$cp = trim($this->input->post('cpassword'));
            
            $q = $this->db->query("SELECT id FROM admin_user WHERE username = '$u' AND id != ?", [$id])->getResult();
            if (count($q))
                $msg = et("admin_user_exists"); else
            if (!$id && ($u == "" || $p == ""))
                $msg = et("admin_enter_fields"); else
            if ($p != $cp)
                $msg = et("admin_password_mismatch"); 
            else {
				$user = dbClass::create('admin_user', $id);
				$user->username = $u;
                $user->id_role = $this->input->post("id_role");
				$user->password = account::encode($p);
				$user->lang = $this->input->post("lang");
				$user->save();
                
                $res['close'] = true;
                $res['result'] = true;
                echo json_encode($res);
                exit();
			}
		}
        
        if ($this->admin_user->id_role == 0)
            $roles = array(0 => "Superuser"); else
            $roles = array();
            
        $id_role = 0;
        $level = $this->admin_user->level;
        foreach (mlClass::load("admin_role", "@level >= $level ORDER BY level ASC") as $i => $role) {
            if ($i == 0) $id_role = $role->id;
            $roles[$role->id] = $role->name;        
        }
        if ($user->id_role)
            $id_role = $user->id_role;

		if ($this->admin_user->id_role == 0)
			$id_role = 0;

        
        $c = "";
        if ($msg)
            $c.= "<div class='admin_message failure'>$msg</div>";
		
		$langs = array();
		foreach (lang::languages(false) as $lang) {
			$langs[$lang] = t("lang_" . $lang);
		}
		
		$c.= Admin::Field(et('Language'), acontrol::select('lang', $langs, v($user, "lang")));
        $c.= Admin::Field(et('Username'), acontrol::edit('username', v($user, "username")));
        $c.= Admin::Field(et('Password'), acontrol::edit('npassword', '', array('password' => true)));
        $c.= Admin::Field(et('Confirm password'), acontrol::edit('cpassword', '', array('password' => true)));
        $c.= Admin::Field(et('Role'), acontrol::select('id_role', $roles, $id_role));
		
        $t = ($id) ? t("Save") : t("Create");
        
        $res = new \stdClass();                                                           
		$res->title = ($id) ? et("Edit user") : et("Add new user");
		$res->content = $c;
		$res->buttons = array("save" => array("align" => "left", "title" => $t, "submit" => true), "cancel");
		
		echo json_encode($res);
	}
    public function Ajax_EditUserRole($id) {
        $this->auto_render = false;
        
        $user = dbClass::load("admin_user", "@id = " . $id, 1);
        if ($this->admin_user->id_role == 0)
            $roles = array(0 => "Superuser"); else
            $roles = array();
        $level = $this->admin_user->level;
        foreach (mlClass::load("admin_role", "@level >= $level ORDER BY level ASC") as $role)
            $roles[$role->id] = $role->name;
            
        if ($this->input->post("save")) {
            $user->id_role = $this->input->post("id_role");
            $user->save();
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            exit();    
        }
        
        $res = new \stdClass();  
        $res->title = et("Edit user role");
        $res->content = Admin::Field(et('Role'), acontrol::select('id_role', $roles, $user->id_role));
        $res->buttons = array("save" => array("align" => "left", "title" => t("Save"), "submit" => true), "cancel");
        
        echo json_encode($res);
    }
    
    public function Ajax_RolesHandler() {
        if ($delete = $this->input->post("delete")) 
            \Sloway\dbModel::delete("admin_role", "@id = " . $delete);
        
        $level = $this->admin_user->level + 1;
        $roles = \Sloway\dbModel::load("admin_role", "@level = $level");
        
        $result = array(
            "rows" => $this->roles_load($roles)
        );    
        
        echo json_encode($result);   
    } 
    public function Ajax_EditRole($id, $pid = null) {
        $this->user_id = 0;
        
        $role = dbClass::load_def("admin_role", "@id = $id", 1);
        if ($this->input->post("save")) {
            $role = dbClass::post("admin_role", $id);
			$role->level = 1;
            if (!is_null($pid)) {
                $role->id_parent = $pid;
                if ($pid != 0) {
                    $q = $this->db->query("SELECT level FROM admin_role WHERE id = ?", $pid)->getResult(); 
                    $role->level = $q[0]->level + 1;
                } else
                    $role->level = 1;
            }
            $role->save();
            
            $this->permissions_prop(dbModel::load("admin_role", "@id = " . $role->id, 1));
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            exit();
        }
        
        $perm_tree = $this->permissions_tree($role);
        
        $t = ($id) ? t("Save") : t("Create");
                  
        $res = new \stdClass();                                               
        $res->title = ($id) ? et("Edit role") : et("Add new role");
        $res->content = view("\Sloway\Views\AdminSettings\Permissions", array("role" => $role, "perm_tree" => $perm_tree));
        $res->buttons = array("save" => array("align" => "left", "title" => $t, "submit" => true), "cancel");
        
        echo json_encode($res);
    }
    
    public function EditMessage($module, $var) {
        $this->lang_selector = false;
        $this->messages_config();
        
        $this->path = array();
        
        $this->msg_module = $module;
        $this->var = $var;
        $this->message = mlClass::load_def("content", "@module='messages_$module' AND name='$var'", 1, null, "*");
        $this->sections = array(
			"media" => array(
				"media_site" => "media_site",
				"media_mail" => "media_mail",
			)
        ) + v($this->config, "$module.sections", array());
        
        $this->classes = array(
            "class_info" => t("Box: info"),
            "class_error" => t("Box: error"),
        );
        
		$this->module_path = array(
			et('Messages') => "AdminSettings/Messages",
			et("messages_{$this->msg_module}_{$this->var}")	=> "",
		);

        $this->module_menu = Admin::EditMenu(array(
            "back" => "AdminSettings/Messages",
        ));
        $this->module_content = Admin::tabs($this->module_tabs, "messages", view("\Sloway\Views\AdminSettings\Messages\EditMessage", array(
			"msg_module" => $this->msg_module,
			"var" => $this->var,
			"message" => $this->message,
			"sections" => $this->sections,
			"classes" => $this->classes,
			"variables" => $this->variables
		)));

		return $this->admin_view();
    }
    public function EditTemplate($module) {
        $this->messages_config();
        
        $this->path = array();

        $this->msg_module = $module;
        $this->msg_header = mlClass::load_def("content", "@module='messages_$module' AND name='template_header'", 1, null, "*");
        $this->msg_footer = mlClass::load_def("content", "@module='messages_$module' AND name='template_footer'", 1, null, "*");
        $this->sections = array(
            "media_site" => t("Media: website"),
            "media_mail" => t("Media: email"),
        ) + v($this->config, "$module.sections", array());
        
        $this->module_content = Admin::tabs($this->module_tabs, "messages", view("\Sloway\AdminSettings\Messages\EditTemplate", array(
			"msg_module" => $this->msg_module,
			"msg_header" => $this->msg_header,
			"msg_footer" => $this->msg_footer,
			"sections" => $this->sections
		)));        
        $this->module_menu = Admin::EditMenu(array(
            "back" => "AdminSettings/Messages",
        ));

		return $this->admin_view();
    }
    
    public function Ajax_SaveMessage($module, $var, $action) {
        $this->messages_config();
        
        $dialog = new \stdClass();
        if ($action == "save" || $action == "close") {
            $reload = $action == "save";
            
            $message = mlClass::load_def("content", "@module='messages_$module' AND name='$var'", 1);
            $message->module = "messages_" . $module;
            $message->name = $var;
			$message->read_post(["title" => "title", "content" => "content"]);
            $message->save();
            
            $dialog->content = "<div class='admin_message success'>Message saved</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
                
        if ($reload) {
            $this->EditMessage($module, $var);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }                
        
        echo json_encode($dialog);                 
    }
    public function Ajax_SaveTemplate($module, $action) {
        $this->messages_config();
        
        $this->auto_render = false;
        
        $dialog = new \stdClass();
        if ($action == "save" || $action == "close") {
            $reload = $action == "save";
            
            $temp = mlClass::load_def("content", "@module='messages_$module' AND name='template_header'", 1);
            $temp->module = "messages_" . $module;
            $temp->name = "template_header";
            $temp->read_post(["header" => "content"]);
            $temp->save();
            
            $temp = mlClass::load_def("content", "@module='messages_$module' AND name='template_footer'", 1);
            $temp->module = "messages_" . $module;
            $temp->name = "template_footer";
            $temp->read_post(["footer" => "content"]);
            $temp->save();
            
            $dialog->content = "<div class='admin_message success'>Template saved</div>";
            $dialog->buttons = array("ok" => array("align" => "right"));  
        }
                
        if ($reload) {
            $this->EditTemplate($module);
            $dialog->form_content = $this->module_content;
            $dialog->menu_content = $this->module_menu;
        }                
        
        echo json_encode($dialog);                 
    }   
    
    public function Ajax_NavigationHandler($name) {
        if ($delete = $this->input->post("delete")) 
            dbModel::delete("navigation", "@id = " . $delete);
        
        if ($reorder = $this->input->post("reorder")) {
            $parent = $this->input->post("parent");
            $index = $this->input->post("index");    
            
            $q = $this->db->query("SELECT id,title FROM `navigation` WHERE id_parent = ? ORDER BY id_order ASC", [$parent])->getResult(); 
            for ($i = $index; $i < count($q); $i++) {
                if ($q[$i]->id == $reorder) continue;
                $this->db->query("UPDATE `navigation` SET id_order = ? WHERE id = ?", [$i+1, $q[$i]->id]);    
            }
            
            $this->db->query("UPDATE `navigation` SET id_order = ? WHERE id = ?", [$index, $reorder]);
        }   
        
        $items = mlClass::load("navigation", "@id_parent = 0 AND module = '$name' ORDER BY id_order ASC");
        $result = array(
            "rows" => $this->navigation_items($items),
        );    
        echo json_encode($result);           
    }
    public function Ajax_NavigationEditItem($module, $id, $pid = null) {
        $this->config = config::get("navigation");
        
        $type = "static";        
        $item = mlClass::load("navigation", "@id = " . $id, 1);
        if ($item) {
            $type = $item->type;
            $pid = $item->id_parent;
        }

        if (isset($_POST["type"]))
            $type = $_POST["type"]; 
            
        $image = \Sloway\images::load("navigation", $id);
            
        if ($this->input->post("create")) {
            $q = $this->db->query("SELECT MAX(id_order) as max FROM navigation WHERE id_parent = ?", [$pid])->getResult();
            $id_order = count($q) ? $q[0]->max + 1 : 0;
            
            if (!$item) {
                $item = mlClass::create("navigation");
                $item->module = $module;
                $item->type = $type;
                $item->id_order = $id_order;
                $item->id_parent = $pid;
            }
            $item->visible = 1;
            $item->type = $this->input->post("type");
            $item->tag = $this->input->post("tag");
            $item->id_ref = $this->input->post("id_ref");
            $item->title = $this->input->post("title");
            $item->url = $this->input->post("url");
            $item->attrs = $this->input->post("attrs");
            $item->autogen = $this->input->post("autogen");
            $item->flags = $this->input->post("flags");
            $item->save();
            
            Admin::ImageList_Save("image", "navigation", $item->id);
            
            $res['close'] = true;
            $res['result'] = true;
            echo json_encode($res);
            
            exit;
        }    
        
        $types = array("static" => et("nav_type_static"));
        foreach ($this->config["types"] as $t => $ops)
            $types[$t] = et("nav_type_$t");
            
        if ($type != "static") {
            $cfg = $this->config["types"][$type];
			
			if (isset($cfg["on_edit"]) && is_callable($cfg["on_edit"]))
				$items = call_user_func($cfg["on_edit"]); else
				$items = dbModel::load(v($cfg, "model"), v($cfg, "sql", "*"));
				
            $tree = $this->navigation_build($cfg, $items);    
        } else
            $tree = null;
            
        $flags = array(
            et("nav_flag_new_win") . "{id=new_win}",
            et("nav_flag_disabled") . "{id=disabled}"
        );
        foreach (v($this->config, "types.$type.flags", array()) as $flag)
            $flags[] = et("nav_flag_" . $flag) . "{id=$flag}";
            
        $res['title'] = ($id) ? et("Edit menu item") : et("Add menu item");
        $res['height'] = 0.6;
        $res['content'] = view("\Sloway\AdminSettings\Navigation\EditItem", array("types" => $types, "type" => $type, "tree" => $tree, "item" => $item, "flags" => $flags, "image" => $image));
        $res['buttons'] = array("create" => array("title" => "OK", "submit" => true, "key" => 13), "cancel");
        echo json_encode($res);                
    }
    public function Ajax_NavigationItemVisible($id, $st) {
        $r = dbClass::load('navigation', "@id = $id", 1);
        $r->visible = $st;
        $r->save();
    }
}
