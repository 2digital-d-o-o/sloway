<?php          
namespace Sloway;

require_once MODPATH . "Core/Classes/phpQuery.php";

class message {
	public $type;
	public $title;
	public $content;

	public static function build($message, $header, $footer, $media) {
		$content = "<div id='message' class='$media'>";
		if ($header)
			$content.= "<div id='message_header'>" . trim($header->content) . "</div>";

		$content.= "<div id='message_content'>" . $message->content . "</div>";

		if ($footer)
			$content.= "<div id='message_footer'>" . trim($footer->content) . "</div>";
		$content.= "</div>";  

		return $content;  
	}
	public static function create($title = "", $content = "", $type = 'info') {
		$r = new message();
		$r->title = $title;
		$r->type = $type;
		$r->content = $content;
		$r->source = $source;

		return $r;	
	}
	public static function load($path, $type = 'info', $media = 'site', $variables = array(), $sections = array(), $lang = null) {
		if (is_null($type)) $type = 'info';
		if (is_null($media)) $media = 'site';

		$e = explode(".", $path);
		if (count($e) < 2) return new message();

		$module = $e[0];
		$name = $e[1];

		$message = mlClass::load('content', "@module = 'messages_$module' AND name = '$name'", 1, null, $lang); 
		if (!$message || trim(!$message->content)) {
			$res = new message();
			$res->content = "";   

			return $res;
		}

		$header = mlClass::load_def('content', "@module = 'messages_$module' AND name = 'template_header'", 1, null, $lang);
		$footer = mlClass::load_def('content', "@module = 'messages_$module' AND name = 'template_footer'", 1, null, $lang);
		$result = new message();
		$result->title = $message->title;
		$result->type = $type;

		$content = message::build($message, $header, $footer, $media);

		if ($media)
			$sections[] = "media_" . $media;

		if (count($sections)) {
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
			}
			$content = $doc->htmlOuter();
		}

		$email = Settings::email("email");
		$title = Settings::get("title");
		$variables["email"] = $email;
		$variables["email_link"] = "<a href='mailto:$email'>$email</a>";
		$variables["title"] = $title;

		if (count($variables)) {
			$tr = array();
			foreach ($variables as $name => $value) 
				$tr["%" . strtoupper($name) . "%"] = $value;

			$content = strtr($content, $tr);                    
		}

		$result->content = $content;

		return $result;
	}       

	public function to_mail() {
		$res = new mail();
		$res->css = array(path::gen("root.media", "css/messages.css"), path::gen("root.modules.Core", "media/css/messages.css"));
		$res->subject = $this->title;
		$res->content = $this->content;

		return $res;    
	}
}  

