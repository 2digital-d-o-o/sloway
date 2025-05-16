<?php
namespace Sloway;

require_once MODPATH . "Core/Classes/CssToInlineStyles.php";

use Sloway\path;
use Sloway\thumbnail;
use Sloway\CssToInlineStyles;

define("MAIL_ATTACH_IMG", 1);
define("MAIL_RESIZE_IMG", 2);
class mail {
	protected $connection = null;
	protected $compiled = false;
	protected $attach_img;
	protected $resize_img;
	protected $attached = array();

	public $name = 'mail';
	public $message = null;
	public $content;
	public $subject;
	public $unsubscribe = null;
	public $style = null; 
	public $from = null;
	public $return = null;
	public $headers = array();
	public $path_host = null;
	public $path_root = null;
	public $files = array();
	public $config = null;
	public $css = array();

	public static function origin($r = null) {
		if (!$r)
			$r = Settings::get("email", null, "settings");

		if (!empty($r))
			$r = preg_split("%(;|,| )%", $r, 2); else
			$r = array(); 

		if (!isset($r[0]) || empty($r[0]))
			$r[0] = "info@" . str_replace(array("https://", "http://", "www."), "", url::base());

		if (!isset($r[1]) || empty($r[1]))
			$r[1] = Settings::get("title", "", "settings");

		return $r;                
	}
	public static function load_content($name, $lang = null) {
		$res = new stdClass();
		$res->header = mlClass::load_def("content", "@module = 'mail_$name' AND name = 'header'", 1, null, $lang)->content;
		$res->footer = mlClass::load_def("content", "@module = 'mail_$name' AND name = 'footer'", 1, null, $lang)->content;
		$res->variations = new stdClass();

		foreach (content::mail_variations($name) as $var_name) {  
			$c = mlClass::load_def("content", "@module = 'mail_$name' AND name = '$var_name'", 1, null, $lang);

			$var = new stdClass();
			$var->subject = $c->title;
			$var->content = $c->content;

			$res->variations->$var_name = $var;
		}
		return $res;
	}
	public static function build_sections($content, $sections) {
		$doc = \phpQuery::newDocument($content);

		foreach ($doc['[class*=section]'] as $s) {
			$classes = pq($s)->attr("class");

			$cls = null;
			foreach (explode(" ", $classes) as $c) {
				if (strpos($c, "section") === 0) {
					$cls = str_replace("section_", "", $c);

					if (!in_array($cls, $sections)) {
						pq($s)->remove();   
						break;    
					}
				}
			}

			//if ($cls && !in_array($cls, $sections))
			//    pq($s)->remove();            
		}

		return $doc->htmlOuter();
	}

	protected function resize_img($apath, $sm) {
		$size = array();
		foreach ($sm[1] as $i => $n) 
			$size[$n] = $sm[2][$i];

		$b = path::gen("root.uploads");                
		$tmp = array(
			"name" => $this->name,
			"width" => $size["width"],
			"height" => $size["height"],
			"mode" => "fit",                
		);

		$p = str_replace($b, "", $apath);

		return thumbnail::create($b, $p, null, $tmp);
	}
	protected function compile_css($match) { 
		$tag = $match[0];
		$img = $match["image"];

		$host = ($this->path_host) ? $this->path_host : path::gen("site.project");
		$root = ($this->path_root) ? $this->path_root : path::gen("root.project");
		$path = str_replace(array($host,'%20'), array(""," "), $img);
		$apath = rtrim($root,"/") . "/" . ltrim($path,"/");  

		$apath = path::to_root($img);

		if (!file_exists($apath)) 
			return $tag;

		if (!isset($this->attached[$apath])) {
			$cid = $this->message->embed(Swift_EmbeddedFile::fromPath($file));

			$this->attached[$apath] = $cid;                    
		} else
			$cid = $this->attached[$apath];

		return str_replace($img, $cid, $tag);
	}        
	protected function compile_img($match) { 
		$tag = $match[0];
		$img = $match[1];

		$host = ($this->path_host) ? $this->path_host : base_url();
		$root = ($this->path_root) ? $this->path_root : path::gen("root");
		$path = str_replace(array($host,'%20'), array(""," "), $img);
		$apath = rtrim($root,"/") . "/" . ltrim($path,"/");

		if (!file_exists($apath)) return "";

		if ($this->resize_img && preg_match_all('/(height|width)="(\d+)"/', $tag, $sm)) {
			$th = $this->resize_img($apath, $sm);

			$apath = $th->dst_base . $th->dst_path;
			$tag = str_replace($img, $th->result, $tag);
			$img = $th->result;
		}
		if (!$this->attach_img) 
			return $tag;

		if (!isset($this->attached[$apath])) {
			$this->message->attach($apath, 'inline');
			//$cid = $this->message->embed(Swift_EmbeddedFile::fromPath($apath));
			$cid = "cid:" . $this->message->setAttachmentCID($apath); 

			$this->attached[$apath] = $cid;
		} else
			$cid = $this->attached[$apath];

		return str_replace($img, $cid, $tag);
	}
	protected function compile_link($m) {
		$u = trim($m[1]);
		if (strpos($u, "mailto") === 0 || 
			strpos($u, "http") === 0 || 
			strpos($u, "file") === 0 || 
			strpos($u, "ftp") === 0 || 
			strpos($u, "%") === 0)
			return $m[0];    

		$url = $m[1];
		if (strlen($url)) 
			$url = url::site($m[1]);

		return str_replace($m[1], $url, $m[0]);     
	}             
	protected function wrap_html($content) {
		return '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>' . $content . '</body></html>';    
	}
	protected function unwrap_html($content) {
		return preg_replace('~<(?:!DOCTYPE|/?(?:html|head|body|meta))[^>]*>\s*~i', '', $content);    
	}

	public static function build($name, $var, $lang = null, $variables = array(), $sections = array(), $structure = null, $styles = array()) {
		$mail = self::load_content($name, $lang);

		$v = \Sloway\utils::value($mail, "variations.$var");

		$res = new mail();
		$res->name = $name;
		$res->var = $var;

		$res->origin = mail::origin();
		$res->subject = utils::value($v, "subject");

		$header = utils::value($mail, "header");
		$content = utils::value($v, "content");
		$footer = utils::value($mail, "footer");

		$content = self::build_sections($content, $sections);
		if (count($variables)) {
			$content = parser::variables($content, $variables);
		}

		if (!$structure)
			$structure = array("%header","%content","%footer");

		$res->style = "";
		foreach ($styles as $css) 
			if (file_exists($css)) 
				$res->style.= file_get_contents($css);         

		$res->content = "";
		foreach ($structure as $part) {
			if ($part == '%header')
				$res->content.= "<div class='mail_header'>$header</div>"; else
			if ($part == '%content')
				$res->content.= "<div class='mail_content'>$content</div>"; else
			if ($part == '%footer')
				$res->content.= "<div class='mail_footer'>$footer</div>"; else
				$res->content.= "<div class='mail_content'>$part</div>";
		}

		return $res;
	}
	public static function check($address, $syntax_only = false) {
		if (empty($address)) return false;

		if (!preg_match('/^\S+@\S+\.\S+$/', $address)) return false;
		if ($syntax_only) return true;

		$e = explode("@", $address);
		$domain = $e[1];

		return checkdnsrr($domain, "MX") || checkdnsrr($domain,"A");
	}                               

	public function compile($resize_img = true, $attach_img = true) {
		$this->message = \Config\Services::email();

	//  Attachments
		if (is_array($this->files))
		foreach ($this->files as $path) {
			if (!file_exists($path)) continue;
			
			$this->message->attach($path);
		}

		$this->attach_img = $attach_img;
		$this->resize_img = $resize_img;

		if (is_array($this->css)) {
			foreach ($this->css as $css) 
				if (file_exists($css)) 
					$this->style.= file_get_contents($css);         
		}

		if ($this->style) {
			if ($attach_img)
				$this->style = preg_replace_callback('~\bbackground(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\)~i', array($this, "compile_css"), $this->style);      

			$conv = new CssToInlineStyles();   
			$conv->setHTML($this->wrap_html($this->content));
			$conv->setCSS($this->style);

			$this->content = $this->unwrap_html($conv->convert());
		}      

		$this->content = preg_replace_callback('~<a.*?href=.([\/.a-z0-9?&=:_\-\s\%20]+).*?>~si', array($this, "compile_link"), $this->content);
		$this->content = preg_replace_callback('~<img.*?src=.([\/.a-z0-9:_\-\s\%20]+).*?>~si', array($this, "compile_img"), $this->content);

		//if ($attach_img)
		//	$this->content = preg_replace_callback('~\bbackground(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\)~i', array($this, "compile_css"), $this->content);  

		$this->compiled = true;

		return $this;    
	}    
	public function instance() {
		$inst = new mail_instance($this);
		$inst->content = $this->content;
		$inst->headers = $this->headers;
		$inst->subject = $this->subject;
		$inst->return = $this->return;

		return $inst;
	}
	public function send($address) {   
		if (is_array($address))
			$address = reset($address);

		$args = func_get_args();
		$inst = count($args) > 1 ? $args[1] : null;

		if (!$this->compiled) 
			$this->compile();

		if ($inst) {
			$return  = $inst->return;
			$subject = $inst->subject;
			$headers = $inst->headers;
			$content = $inst->content;
		} else {
			$return  = $this->return;
			$subject = $this->subject;
			$headers = $this->headers;
			$content = $this->content;
		}

	//  Set return path            
	//	if ($return) 
	//		$this->message->setReturnPath($return); 
		$this->message->setSubject($subject); 

	//  Assign custom headers
	//	$msg_headers = $this->message->getHeaders();
		foreach ($headers as $name => $value)  
			$this->message->setHeader($name, $value); 

		/*if ($listuns)
			$msg_headers->addTextHeader("List-Unsubscribe", $listuns);*/
		//$plain = strip_tags($content);

		$content = $this->wrap_html($content);

		$this->message->setMessage($content, "text/html");
		$this->message->setTo($address);

	//  Assign sender
	/*
		if (is_null($this->origin)) 
			$this->origin = Settings::get("email","");

		if (is_string($this->origin) && strpos($this->origin, ";") !== false)
			$this->origin = utils::explode(";", $this->origin, true); 

		if (is_array($this->origin) && count($this->origin) == 1) 
			$this->origin = $this->origin[0];    
	*/        
/*
		$from = $this->from;
		if (!$from)
			$from = config::get("email.from");

		echod($from);

		if (!$from) return false;*/

		//$this->message->setFrom($from);
		
		$res = $this->message->send();
		$this->message->clear();
		//foreach ($headers as $name => $value)  
		//	$msg_headers->remove($name); 

		return $res;       
	}        
}  

class mail_instance {
	public $mail;

	public $content;
	public $subject;
	public $unsubscribe;
	public $return = null;
	public $headers = array();

	public function __construct($mail) {    
		$this->mail = $mail;                
	}
	public function send($address) {
		$this->mail->send($address, $this);
	}
}

