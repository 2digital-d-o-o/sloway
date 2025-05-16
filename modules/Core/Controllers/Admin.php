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
use Sloway\content;
use Sloway\core;
use Sloway\path;
use Sloway\url;
use Sloway\acontrol;
use Sloway\admin_user;

class Admin extends AdminController {
	public $title;
	public $modules;
	public $module_title;
	public $module_path;

	public function Index() {
		if ($uri = config::get("admin.default_module")) {
			return redirect()->to($uri);
		}
		$this->module_content = "";
	
		return $this->admin_view();
	}
    public function Ajax_Logged() {
        echo intval(admin_user::instance()->user_id != 0);
    }
	public function Ajax_ContentLang() {
	}

    public function Ajax_Browser() {
        $res['title'] = et("Select files");
        $res['content'] = "<div class='admin_browser' style='position: absolute; top: 5px; bottom: 0; left: 0; right: 0'></div>";
        $res['buttons'] = array("ok", "cancel");
        
        echo json_encode($res);            
    }
    public function Ajax_BrowserHandler() {
        $handler = new \Sloway\BrowserHandler(path::gen("root.uploads"), $this->db);
        echo $handler->execute($_POST);
    }

    public function Ajax_Thumbnail() {
        $res = array();
        $ids = $this->request->getPost("ids");
        $paths = $this->request->getPost("paths");
        $template = $this->request->getPost("template");
        
        foreach ($paths as $i => $path) {
            $th = \Sloway\thumbnail::create(null, $path, null, $template);
            if (!$th->result) continue;
            
            $key = isset($ids[$i]) ? $ids[$i] : $path;
            $res[$key] = $th->result; 
        }
        
        echo json_encode($res);
    }
	public function ContentCss() {
		$this->response->setHeader('Content-Type', 'text/css');
		
        return view("\Sloway\Views\Admin\ContentCss", array("sections" => $_GET));
	}

    public function Ajax_TemplateProp() {
        $name = $this->input->post("name");
        $data = $this->input->post("props");
        // echod($data);

        $templates = content::load_templates();
        $class_list = v($templates, "$name.styles", array());

        $media_all = content::load_media();
        $media_list = array();
        foreach ($this->input->post("media", array()) as $mname) {
            $media_list[$mname] = $media_all[$mname]["title"];
        }

        $attributes = array();
        foreach (v($templates, "$name.attrs", array()) as $tname => $values) {
            $e = explode(":", $tname);
            $tname = $e[0];
            $op = isset($e[1]) ? $e[1] : 1;

            $prop = new \stdClass();
            $prop->type = "edit";
            $prop->lines = $op;
            $prop->title = t("template_attr_" . $tname);
            $prop->items = array();
            $prop->value = v($data, "attributes." . $tname);
            if (!$prop->value && is_string($values))
                $prop->value = $values;

            if (is_array($values) && count($values)) {
                $prop->type = "select";
                foreach ($values as $value)
                    $prop->items[$value] = t("template_attr_" . $tname . "_" . $value);

                if (!$prop->value)
                    $prop->value = $values[0];
            }

            $attributes[$tname] = $prop;
        }

        $vars = array(
            "data" => $data,
            "class_items" => $class_list,
            "media_items" => $media_list,
            "attributes" => $attributes,
        );

        $res = new \stdClass();
        $res->title = et("Template properties");
        $res->content = view("\Sloway\Views\Admin\TemplateProp", $vars);

        $res->buttons = array("ok", "cancel");

        echo json_encode($res);
    }
    public function Ajax_TemplateImage() {
        $res = new \stdClass();
        $res->title = et("Image properties");
        $res->content = view("\Sloway\Views\Admin\ImageProp", array(
			"title" => $this->input->post("title"),
			"desc" => $this->input->post("desc"),
			"url" => $this->input->post("url"),
			"alt" => $this->input->post("alt"),
		));
        $res->buttons = array("ok", "cancel");   
        
        echo json_encode($res);   
    }
}
