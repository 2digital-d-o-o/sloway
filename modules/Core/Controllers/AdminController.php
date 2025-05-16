<?php 

namespace Sloway\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Controllers\BaseController;
use Sloway\config;
use Sloway\core;
use Sloway\path;
use Sloway\url;
use Sloway\admin;
use Sloway\mlClass;

class AdminController extends BaseController {
	public $title;
	public $modules;
	public $module_title;
	public $module_path;
	public $module_content;
	public $module_menu;
	public $lang_selector = true;
	public $check_login = true;
	public $deny_access = false;

	protected function filter_templates($flt, $list) {
        if (!$flt) return implode(",",$list);
        
        $exc = false;
        if (strpos($flt, "!") === 0) {
            $flt = substr($flt, 1);
            $exc = true;    
        }
            
        $r = array(" " => "", "," => "|", "*" => '[A-Za-z0-9\-\_]*');
        $flt = '~\b(' . strtr($flt, $r) . ')\b~i';
        
        $res = array();
        foreach ($list as $name) {
            $m = preg_match($flt, $name);
            if (($exc && !$m) || (!$exc && $m)) 
                $res[]= $name;
        }
        
        return implode(",", $res);
	}
	protected function load_templates() {
        $res = array();
        $styles = \Sloway\config::get("templates.styles"); 
        
        $g_styles = array();
        if (is_array($styles)) {
            foreach ($styles as $n) 
                $g_styles["rl_class_" . $n] = t("template_style_" . $n);
        }
        
        $templates = array_unique(config::get("admin.templates"));
        foreach ($templates as $name) {
            
            $ops = \Sloway\config::get("templates.templates.$name");
            if (!is_array($ops)) continue;
            // if (!kohana::find_file("views", $ops["view"])) continue;

			$view = $ops["view"];
			
            
            $styles = array();
            foreach ($g_styles as $n => $t)
                $styles[$n] = $t;
            foreach (v($ops, "styles", array()) as $n)
                $styles["rl_class_" . $n] = et("template_style_" . $n);
                
            $res[$name] = array(
                "title" => t("template_" . $name),
                "attrs" => v($ops, "attrs", array()),
                "attrs_html" => v($ops, "attrs_html", array()),
                "html" => view($view, array("media" => "editor")),
                "html_mail" => view($view, array("media" => "mail")),
                "styles" => $styles,
                "add" => $this->filter_templates(v($ops, "add", ""), $templates),
                "root" => v($ops, "root", true),
                "platform" => v($ops, "platform", "site"),
                "auto_edit" => v($ops, "auto_edit", ""),
            );
        }
        
        return $res;
	}
	protected function load_media() {
        $res = \Sloway\config::get("templates.media"); 
        if (is_array($res)) {
            foreach ($res as $n => $ops) {
                $res[$n]["title"] = t("template_media_" . $n);
                $res[$n]["class"] = "rl_media_" . $n;
            }
        }        
        
        return $res;
	}

	public function Index() {
		return $this->admin_view();
	}
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
		
		//echo "Admin::init";
		parent::initController($request, $response, $logger);
		//echod(\Sloway\admin_user::instance());
		
	
		if ($this->admin_user) {
			$uid = $this->admin_user->id;
			$role = ($rid = $this->admin_user->id_role) ? \Sloway\dbClass::load("admin_role", "@id = '$rid'", 1) : 0;

			$this->admin_user->level = ($role) ? $role->level : 0;
			$this->admin_user->role = $role;
			\Sloway\dbClass::$options['edit_user'] = $this->admin_user->username;

			$this->db->query("UPDATE admin_user SET last_login = ? WHERE id = ?", [time(), $uid]);
		} else {
			$pth = $request->getPath();
			if (strpos($pth, "AdminLogin") !== 0) {
				header('Location: ' . site_url("AdminLogin"));
				exit();
			}
		}
			
		
		\Sloway\lang::load("admin", ($this->admin_user) ? $this->admin_user->lang : null);
//		if ($this->check_login && !$this->admin_user)
//			$response->redirect("AdminLogin");

        $default_icon = path::gen("site.modules.Core", "media/img/modules/module_default.png");
        $modules = array();
        foreach (config::get("admin.modules") as $name => $link) {
            // if (!\Sloway\admin::auth($name)) continue;

            $modules[$name] = array(
                "icon" => config::get("admin.icons.$name", $default_icon),
                "title" => et(ucfirst($name) . ".module"),
                "link" => url::site($link)
            );
        }


		\Sloway\Admin::$user = $this->admin_user;
		\Sloway\Admin::$admin_lang = \Sloway\lang::$lang;
		\Sloway\Admin::$content_lang = get_cookie(core::$project_name . "_admin_content_lang", mlClass::$def_lang);
		\Sloway\Admin::$admin_edit_lang = \Sloway\lang::$content_lang;
		\Sloway\admin::$permissions = \Sloway\utils::serialize_tree(config::get("admin.permissions"));

		$this->title = "Admin";

		$this->modules = $modules;
		$this->module_content = "";
		$this->module_menu = "";
		$this->module_wide = false;

		$this->logo = path::gen('site.modules.Core','media/img/admin-logo.png');
		if ($s = \Sloway\Settings::get("logo")) {
			$this->logo = \Sloway\thumbnail::from_image(json_decode($s), "admin_logo")->result;
		}

        $purge = $this->input->post("purge_cache", array());
        if (count($purge)) {
            foreach ($purge as $cid)
                cache()->delete($cid);
        }

    }
	
	public function admin_view() {	
		$module_title = "";
		$module_path = array();
		if (isset($this->module) && isset($this->modules[$this->module])) {
			$module_path[] = $this->modules[$this->module];
			$module_title = $this->modules[$this->module]["title"];
		}

		if (isset($this->module_path)) {
			foreach ($this->module_path as $key => $val) {
				if (is_int($key))
					$part = array("title" => ($val) ? $val : et("Edit.verb"), "link" => url::current()); else
					$part = array("title" => $key, "link" => ($val) ? $val : url::current());                    

				$module_path[] = $part;
			}
		}
		$module_class = $this->module_wide ? "" : "narrow";
		
		if ($this->deny_access) {
			$content = view("Sloway\Views\AdminModule", array(
				"module_path" => $module_path,
				"module_content" => '<div class="admin_message failure">' . et('invalid_access') . '</div>',
				"module_menu" => "",
				"module_class" => $module_class,
			));
		} else {
			$content = view("Sloway\Views\AdminModule", array(
				"module_path" => $module_path,
				"module_content" => $this->module_content,
				"module_menu" => $this->module_menu,
				"module_class" => $module_class,
				"lang_select" => $this->lang_selector,
			));
		}
		if ($this->request->getPost("module_ajax")) 
			return $content;

		return view("Sloway\Views\Admin", array(
			"logo" => $this->logo,
			"lang_select" => $this->lang_selector,
			"modules" => $this->modules,
			"module_title" => $module_title,
			"content" => $content,
			"admin_username" => $this->admin_user ? $this->admin_user->username : "",
		));
	}

    public function Script() {
		$this->response->setHeader('Content-Type', 'text/javascript');

		$paths = new \Config\Paths;
		$img_srv = $paths->imageServer;
		if (!$img_srv) $img_srv = site_url("Core/Ajax_Image");		
		
		$res = array();
		$cfg = \Sloway\config::get("templates");
		foreach ($cfg["templates"] as $name => $ops) {
			if (isset($ops["sizes"]))
				$res[$name] = $ops["sizes"];          
		}

		$template_sizes = $res;
		$templates = $this->load_templates();
		$media = $this->load_media();

		return view("\Sloway\Views\Script", array(
			"template_sizes" => $template_sizes,
			"templates" => $templates,
			"media" => $media,
			"img_srv" => $img_srv,
		));
    }	

}
