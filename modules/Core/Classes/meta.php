<?php  
	namespace Sloway;

    class meta {  
        public static $def_ops = array(
            "title" => "meta_title,title,s:title",       // s: = vzame iz Settings
            "desc" => "description,title,s:title",
            "meta_keys" => "meta_keys,s:meta_keys",
            "meta_desc" => "meta_desc,description,s:meta_desc",
            "image" => "image,images,s:default_image"
        );
        public function get_value($object, $pth, $default = "") {
            foreach (explode(",", $pth) as $name) {
                if ($name[0] == "s") {
                    $name = str_replace("s:", "", $name);
                    $val = Settings::get($name);    
                } else 
                    $val = v($object, $name);
                    
                if ($val) 
                    return $val;
            }
            
            return $default;
        }
        public function get_image($object, $pth) {    
            $res = new \stdClass();
            $res->path = "";
            $res->type = "";
            $res->width = 0;
            $res->height = 0;
            
            foreach (explode(",", $pth) as $name) { 
                if ($name[0] == "s") {
                    $name = str_replace("s:", "", $name);
                    $val = Settings::get($name);    
                } else
                    $val = v($object, $name);
                    
                if (!$val) continue;
            
                if (is_array($val))
                    $val = reset($val);
                if (is_object($val))
                    $val = $val->path; 
                
                $pth_site = path::gen("site.uploads", $val);
                $pth_root = path::gen("root.uploads", $val);
                if (file_exists($pth_root)) {
                    $size = getimagesize($pth_root);
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $pth_root);
                    
                    $res->path = str_replace(' ', '%20', $pth_site);
                    $res->width = $size[0];
                    $res->height = $size[1];
                    $res->type = $mime;
                    
                    return $res;
                }
            }  
            return null;     
        }
        
		public static function default() {
			$res = new meta();         
			$res->title = Settings::get("title");
			$res->desc = "";
			$res->meta_keys = Settings::get("meta_keys");
			$res->meta_desc = Settings::get("meta_desc");
			$res->image = "";
			$res->head_content = "";
			$res->body_content = "";
			$res->canonical_url = url::current();

			return $res;
		}
        public static function create($object = null, $ops = array()) {
            $ops = arrays::extend($ops, meta::$def_ops);
            $res = new meta();         
            $res->title = strip_tags($res->get_value($object, $ops["title"]));  
            $res->desc = strip_tags($res->get_value($object, $ops["desc"]));
            $res->meta_keys = $res->get_value($object, $ops["meta_keys"]);
            $res->meta_desc = strip_tags($res->get_value($object, $ops["meta_desc"]));
            $res->image = $res->get_image($object, $ops["image"]);
			$res->head_content = v($object, "meta_head");
			$res->body_content = v($object, "meta_body");
			$res->canonical_url = v($object, "canonical_url");
            $res->page_id = $object->id;
			if (!$res->canonical_url) $res->canonical_url = url::current();
            
            return $res;
        }
        public function head() {
            $url = current_url();
            
            $res = "<title>{$this->title}</title>\n";
			$res.= "<link rel='canonical' href='{$this->canonical_url}'/>\n";
            $res.= "<meta name='description' content='{$this->meta_desc}'>\n";
            $res.= "<meta name='keywords' content='{$this->meta_keys}'>\n";

			/*
            $res.= "<meta property='og:url' content='$url'>\n";
            $res.= "<meta property='og:type' content='article'>\n";
            $res.= "<meta property='og:title' content='{$this->title}'>\n";
            $res.= "<meta property='og:description' content='{$this->meta_desc}'>\n";
                
            if ($img = $this->image) {
                $res.= "<meta property='og:image' content='{$img->path}'>\n";
                $res.= "<meta property='og:image:type' content='{$img->type}'>\n";
                $res.= "<meta property='og:image:width' content='{$img->width}'>\n";
                $res.= "<meta property='og:image:height' content='{$img->height}'>\n";
            }
			 * 
			 */
			
			if ($meta_pixel = Settings::get("facebook_pixel")) 
				$res.= "
<!-- Meta Pixel -->
  <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.12';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '" . $meta_pixel . "');
  </script>
  <noscript><img height='1' width='1' style='display:none'
  src='https://www.facebook.com/tr?id=" . $meta_pixel . "&amp;nocscript=1' /></noscript>";


			$res.= $this->head_content;
            
            return $res;                
        }
		public function body() {
			return $this->body_content;
		}
    }  
?>
