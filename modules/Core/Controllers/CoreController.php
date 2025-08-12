<?php

namespace Sloway\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sloway\core;
use Sloway\input;
use Sloway\path;
use Sloway\config;
use Sloway\url;
use Sloway\dbClass;
use Sloway\utils;
use Sloway\cron;

require_once MODPATH . "Core/Classes/core.php";

class CoreController extends Controller
{
    protected $request;
	protected $response;
    protected $helpers = [];
    protected $session;
	protected $input;

	public $db;

    public function scale_image($path, $width, $height, $mode, $level, $format, $delta) {
        $result = array(
            "path" => $path,
            "width" => $width,
            "height" => $height,
            "scaled" => $path,
            "scaled_width" => 0,
            "scaled_height" => 0,
            "max_width" => 0,
            "max_height" => 0,
            "level" => 0,
            "error" => null
        );
        
        if (!$width) {
            $result["error"] = "not_found";
            return $result;
        }
        
        $base = url::base();
        if (strpos($path, $base) === 0)
            $src_path = str_replace($base, "", $path); else
            $src_path = $path;

        $rel_path = $src_path;   
        $src_path = ROOTPATH . $src_path;    
        
        if (!file_exists($src_path)) {
            $result["error"] = "not_found";
            return $result;
        }
        
        $size = @getimagesize($src_path);
        if (!$size) {
            $result["error"] = "invalid_size";
            return $result;
        }
        
        $result["max_width"] = $size[0];
        $result["max_height"] = $size[1];
        
        $result["scaled_width"] = $size[0];
        $result["scaled_height"] = $size[1];
        
        $ratio = $size[1] / $size[0];
        if (!$height) {
            $height = intval($width * $ratio);
            $result["height"] = $height;
        }
            
        if ($mode == "cover") 
            $rect = \Sloway\utils::cover($width, $height, $ratio); else    
            $rect = \Sloway\utils::contain($width, $height, $ratio); 

		if (is_null($level)) {
			$max_level = intval(($size[0] - 200) / 200);
			$level = intval(($size[0] - $width) / 200);
			if ($level < 0) $level = 0;
			if ($level > $max_level) $level = $max_level;
		}

		// always recreate?
//        if ($level == 0) 
//            return $result;
            
        $result["level"] = $level;
        
        $info = pathinfo($rel_path);
        $ext = strtolower($info["extension"]);

		$exif = exif_imagetype($src_path);
		if ($exif == IMAGETYPE_JPEG) {
			$src_fmt = "jpg";
			$dst_fmt = $format ?: "jpg";
		} else
		if ($exif == IMAGETYPE_PNG) {
			$src_fmt = "png";
			$dst_fmt = $format ?: "png";
		} else
		if ($exif == IMAGETYPE_WEBP) {
			$src_fmt = "webp";
			$dst_fmt = $format ?: "webp";
		} else
		if ($exif = IMAGETYPE_GIF) {
			$src_fmt = "gif";
			$dst_fmt = "gif";
		} 
		if (!$src_fmt) {
            $result["error"] = "invalid_format";
            return $result;
		}
        
        $hash = filemtime($src_path) . $size[0] . $size[1] . "-" . $level;
        $dst_name = str_replace("/", "_", $info["dirname"]) . "_" . $info["filename"] . "." . $hash . "." . $dst_fmt;
        $dst_path = path::gen("root.thumbs", "adaptive");
		if (!file_exists($dst_path))
			mkdir($dst_path);

        $dst_path.= "/" . $dst_name;
	
		$dst_w = $size[0] - $level*200;
		$dst_h = intval($dst_w * $size[1] / $size[0]);

        $result["scaled_width"] = $dst_w;
        $result["scaled_height"] = $dst_h;
        
        $result["scaled"] = path::gen("site.thumbs", "adaptive/" . $dst_name);
        if (file_exists($dst_path)) {
            touch($dst_path);
            return $result;
        }
		
		$src_img = null;
        if ($src_fmt == "jpg")
            $src_img = imagecreatefromjpeg($src_path); else
        if ($src_fmt == "webp")
            $src_img = imagecreatefromwebp($src_path); else
        if ($src_fmt == "gif")
            $src_img = imagecreatefromgif($src_path); else
        if ($src_fmt == "png") 
            $src_img = imagecreatefrompng($src_path);    
            
        if (!$src_img) {            
			$result["error"] = "invalid_image";
            return $result;
		}

        $dst_img = ImageCreateTrueColor($dst_w, $dst_h);
        imagealphablending($dst_img, false);
        imagecopyresampled($dst_img, $src_img, 0,0,0,0, $dst_w, $dst_h, $size[0],$size[1]);
        
        if ($dst_fmt == "png") {
            imagesavealpha($dst_img, true);
            imagepng($dst_img, $dst_path); 
        } else
		if ($dst_fmt == "webp") {
            imagesavealpha($dst_img, true);
            imagewebp($dst_img, $dst_path, 90); 
		} else 
        if ($dst_fmt == "gif") 
            imagegif($dst_img, $dst_path); else
            imagejpeg($dst_img, $dst_path, 90);

        imagedestroy($src_img);
        imagedestroy($dst_img);      
        
        return $result;
    }    

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

		$this->request = $request;
		$this->response = $response;

		input::$request = $request;
		$this->input = new input();
	
		core::$project_name = isset($_SERVER["PROJECT_NAME"]) ? $_SERVER["PROJECT_NAME"] : "project";

		helper("utils");
		helper("cookie");
		
		$this->db = \Config\Database::connect();
        $this->session = \Config\Services::session();
		$this->session->set("current_url", current_url());
		
		\Sloway\core::$db = $this->db;
		\Sloway\dbClass::$database = $this->db;
		\Sloway\account::$db = $this->db;
		\Sloway\account::$session = $this->session;
		\Sloway\account::$prefix = core::$project_name;

		\Sloway\userdata::$profile = core::$profile;
		\Sloway\userdata::$db = $this->db;

		$mp = \Sloway\path::gen("site.modules.Core");

		//log::init();   
		
		$this->doc_base = trim(base_url(), "/");
		$uid = \Sloway\admin_user::instance()->user_id;  
		if ($uid) 
			$this->admin_user = \Sloway\dbClass::load("admin_user", "@id = $uid", 1); else
			$this->admin_user = null;

		$pth = strtolower($request->getPath());
		if (strpos($pth, "admin") === 0) {
			core::$profile = "admin";
			define("PROFILE", "admin");
		} else {
			core::$profile = "site";
			define("PROFILE", "site");
		}

		core::load($this);
		
		\Sloway\admin::$db = $this->db;
		\Sloway\dbModel::$db = $this->db;
		\Sloway\dbModel::build();
	//	\Sloway\lang::$session = $this->session;
	//	\Sloway\lang::load(core::$profile);
    }
    public function Ajax_Translate($profile) {
        $lang = $this->input->post("lang");
        $text = $this->input->post("text");
        $key = $this->input->post("key");
        
        if ($this->input->post("ok")) {
            $target = $this->input->post("target");
            set_cookie("translator_trg", $target);
            
            if ($target == "module")
                $base = path::gen("root.modules.Lang", "lang"); else
                $base = null;
            
            \Sloway\lang::$translator->add($key, htmlspecialchars_decode($text), $lang);
            \Sloway\lang::$translator->save($base, $profile);
            
            exit;
        }
        
        $target = get_cookie("translator_trg");
		if (!$target) $target = "site";
        
        $c = "<label>Key</label>";
        $c.= \Sloway\acontrol::edit("key", $key, array("readonly" => true));
        $c.= "<label>Translation</label>";
        $c.= \Sloway\acontrol::edit("text", $text);
        $c.= "<label>Target</label>";
        $c.= \Sloway\acontrol::select("target", array("site" => "Local", "module" => "Module"), $target);
        $c.= "<input type='hidden' name='lang' value='$lang'>";
        
        $res['title'] = t("Edit translation") . "[$lang]";              
        $res['content'] = $c;
        $res['buttons'] = array("ok" => array("submit" => true, "result" => true), "cancel");
        
        echo json_encode($res);
    }   
    public function Ajax_Language() {
        $profile_name = $this->input->post('profile');
        $lang = $this->input->post('lang');
        $profile = \Sloway\config::get("lang.$profile_name");
		
		if (!in_array($lang, $profile["langs"])) die("FAILED");
		set_cookie(core::$project_name . "_" . $profile_name . "_lang", $lang, 604800);
		
		echo "OK";
    }
    public function Ajax_Image() {    
        $this->auto_render = false;

		$format = config::get("templates.adaptive_image_format", null);
		$delta = config::get("templates.adaptive_image_delta", 200);

        $result = array();
        foreach ($_POST as $id => $image) {
            $path = v($image, "path");
            $level = v($image, "level", null);
            $width = v($image, "width", null);
            $height = v($image, "height", null);
            $mode = v($image, "mode", "contain");
            
            $result[$id] = $this->scale_image($path, $width, $height, $mode, $level, $format, $delta);
        } 
        
        echo json_encode($result);
    }    

    public function Script() {
		$paths = new \Config\Paths;
		$img_srv = $paths->imageServer;
		if (!$img_srv) $img_srv = site_url("Core/Ajax_Image");

		$admin_logged = \Sloway\admin_user::instance()->user_id;  
		$this->response->setHeader('Content-Type', 'text/javascript');

		return view("\Sloway\Views\Script", array("img_srv" => $img_srv, "admin_logged" => $admin_logged));
    }	
	public function Error() {
		if (file_exists(APPPATH . "Views/Error.php"))
			return view("\App\Views\Error.php"); else
			return view("\Sloway\Views\Error.php");
	}

	public function CronHandler() {
		$time = time();

	/*  
		$log = dbClass::create("task_log");
		$log->time = $time;
		$log->timef = utils::datetime($time);
		$log->command = "Ping";
		$log->save();
	 * 
	 */

        foreach (dbClass::load('task', "@active = 1") as $task) {
            if ($task->running && $time - $task->last_time < 30*60) continue;
			if ($task->time_next == 0 || $time > $task->time_next) {
				//echo "start task: " . $task->title . " " . url::site("Core/StartTask/" . $task->id);

			    $ch = curl_init();
			    curl_setopt($ch, CURLOPT_URL, url::site("Core/StartTask/" . $task->id));
			   // curl_setopt($ch, CURLOPT_HEADER, false);         
			  //  curl_setopt($ch, CURLOPT_NOBODY, true);          
			    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
			    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
			    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
			    curl_exec($ch);
			    curl_close($ch);

				break;
			}
		}	
	}   
	public function StartTask($id) {
		if (!is_numeric($id)) die();
		
		$task = dbClass::load('task',"@id = '$id'", 1);
		if (!$task) die();
        
		$task->time_last = time();
		$task->running = 1;
		$task->save();

		
		$ch = curl_init();             

		curl_setopt($ch, CURLOPT_URL, url::site($task->command));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array("source" => "CronHandler"));           
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
		$res = curl_exec($ch);
		curl_close($ch);
        
		$time = time();         
		$task->time_next = cron::next($task->schedule);
		$task->running = 0;
		$task->save();
        
		if ($res) {
			$log = dbClass::create("task_log");
			$log->id_task = $task->id;
			$log->time = $time;
			$log->timef = utils::datetime($time);
			$log->command = url::site($task->command);
			$log->content = $res;
			$log->save();
		}
	}    
}
