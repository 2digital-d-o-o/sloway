<?php

namespace Sloway;
use Sloway\path;
use Sloway\url;
use Sloway\utils;

class thumbnail {
	public $clip;
	public $size;
    public $filter = null;
	public $orig_size;
	public $src_base;
	public $src_path;
	public $src_fmt;
	public $dst_base;
	public $dst_path;
	public $dst_name;
	public $dst_fmt;
	
    public $error = null;
    public $invalid = false;
	public $new = false;
	public $result = false;
	public $title = null;
	
	public $template;  

    protected function apply_filter($img, $filter) {
        switch ($filter) {
            case "gray":
                imagefilter($img, IMG_FILTER_GRAYSCALE);
                
                break;
            case "blur":
                for ($i = 0; $i < 10; $i++)
                    imagefilter($img, IMG_FILTER_GAUSSIAN_BLUR);
        }    
    }
    protected function gen_watermark($target, $width, $height) {
        $src = v($this->watermark, "source");
        if (!file_exists($src)) return;
        
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        
        $pos = v($this->watermark, "position", "11");    
        if (!is_string($pos) || strlen($pos) < 2)
            $pos = "11";
            
        $spacing = v($this->watermark, "spacing", 0.05);
        $size = v($this->watermark, "size", 0.3);
        
        if ($ext == "jpg" || $ext == "jpeg")
            $src_img = @imagecreatefromjpeg($src); else
        if ($ext == "gif")
            $src_img = @imagecreatefromgif($src); else
        if ($ext == "png") 
            $src_img = @imagecreatefrompng($src);
            
        imagealphablending($target, true);
        
        $src_w = imageSX($src_img);
        $src_h = imageSY($src_img);
        
        $spacing = intval($spacing * $width);
        $trg_w = intval($width * $size);
        $trg_h = intval($trg_w * $src_h / $src_w);

        if ($trg_w > $src_w) $trg_w = $src_w;
        if ($trg_h > $src_h) $trg_h = $src_h;
        
        if ($pos[0])
            $trg_x = $width - $trg_w - $spacing; else
            $trg_x = $spacing;
        if ($pos[1])
            $trg_y = $height - $trg_h - $spacing; else
            $trg_y = $spacing;
            
        imagecopyresampled($target, $src_img, $trg_x, $trg_y, 0,0, $trg_w, $trg_h, $src_w, $src_h);
    }
	protected function position() {
		if (!$this->result) return;
		
		$valign = v($this->template, "valign", "center");
		$halign = v($this->template, "halign", "center");
		$mode = v($this->template, 'mode', 'fit');
		$tw = v($this->template, 'width', 0);
		$th = v($this->template, 'height', 0);
		
		$sw = $this->size[0];
		$sh = $this->size[1];
		
		if ($mode == 'fit') {
			$mx = 0;
			$my = 0;

			if ($tw) {        
				$dw = $tw - $sw;
				
				if ($halign == "center" || $halign === 0)
					$mx = $dw / 2; else
				if ($halign == "right" || $halign === 1)
					$mx = $dw;
			}
			
			if ($th) {
				$dh = $th - $sh;
					
				if ($valign == "center" || $valign === 0)
					$my = $dh / 2; else
				if ($valign == "bottom" || $valign === 1)
					$my = $dh;
			}  
			$res->x = intval($mx) . "px";
			$res->y = intval($my) . "px";
			$res->w = $sw . "px";
			$res->h = $sh . "px";
		} else {
			$res->x = "0px";
			$res->y = "0px";
			$res->w = $tw . "px";
			$res->h = $th . "px";
		}
		
		return $res;  
	}

	protected function gen_size() {
		$dw = v($this->template, "width", 0);
		$dh = v($this->template, "height", 0);
		$mode = v($this->template, "mode", "fit");
		$valign = v($this->template, "valign", 0);
		$halign = v($this->template, "halign", 0);

		$s = @getimagesize($this->src_base . $this->src_path); 
        if (!$s) {
            $this->error = "get_image_size"; 
            return false;
        }
        
		$org_w = $s[0];
		$org_h = $s[1]; 
        
        if ($org_w*$org_h*4 > 64*1024*1024) {
            $this->error = "image too big";
            $this->invalid = true;
            
            return false;
        }
        
        if (!$org_w || !$org_h || (!$dw && !$dh)) {    
            $this->error = "invalid sizes";
            $this->invalid = true;
            
            return false;
        }
        
        if (!$dh)
            $dh = intval($dw * $org_h / $org_w);
        if (!$dw)
            $dw = intval($dh * $org_w / $org_h);
		
		$this->orig_size = $s;  
		
		if (!$this->clip) {
			if ($mode == 'fill') {
				$over = utils::rect_overlay($org_w, $org_h, $dw, $dh);
				
				$mx = $over[0] - $dw;
				$my = $over[1] - $dh;
				
				if ($halign == "center" || $halign == 0) 
					$mx = $mx / 2; else
				if ($halign == "left" || $halign == -1)
					$mx = 0;

				if ($valign == "center" || $valign == 0) 
					$my = $my / 2; else
				if ($valign == "top" || $valign == -1)
					$my = 0;
				
				
				$this->clip = array(
					$mx / $over[0],
					$my / $over[1],
					($mx + $dw) / $over[0],
					($my + $dh) / $over[1],
				);
			} else 
				$this->clip = array(0,0,1,1);
		}                  
		
		$sw = ($this->clip[2] - $this->clip[0]) * $org_w;
		$sh = ($this->clip[3] - $this->clip[1]) * $org_h;

        if ($mode != "fill" && $sw <= $dw && $sh <= $dh) {
			$this->size = array($org_w, $org_h);
            return $this->size;
		}
        
	//  Resize by width
		$w_nw = $dw;
		$w_nh = $dw * $sh / $sw;
		
	//  Resize by height
		$h_nw = $sw * $dh / $sh;
		$h_nh = $dh;
		
		$c = ($mode == 'fit') ? $w_nh <= $dh : $w_nh >= $dh;
		
		if ($c) {                    
			$nw = $w_nw;
			$nh = $w_nh;
		} else {
			$nw = $h_nw;
			$nh = $h_nh;
		}
        $this->size = array(intval($nw), intval($nh));

		return $this->size;
	}
	protected function gen_formats($des_type = null) {
		$fmt = exif_imagetype($this->src_base . $this->src_path);
		if ($fmt == IMAGETYPE_JPEG) {
			$this->src_fmt = "jpg";
			$this->dst_fmt = $des_type ?: $this->src_fmt;
		} else
		if ($fmt == IMAGETYPE_PNG) {
			$this->src_fmt = "png";
			$this->dst_fmt = $des_type ?: $this->src_fmt;
		} else
		if ($fmt == IMAGETYPE_WEBP) {
			$this->src_fmt = "webp";
			$this->dst_fmt = $des_type ?: $this->src_fmt;
		} else
		if ($fmt = IMAGETYPE_GIF) {
			$this->src_fmt = "gif";
			$this->dst_fmt = "gif";
		} else {
			$this->invalid = true;
			$this->error = "invalid_format";
		}
	}
	protected function gen_name() {
		$this->time = filemtime($this->src_base . $this->src_path);
		$this->filter = v($this->template, "filter", null);
		$this->watermark = v($this->template, "watermark", null);

        $hash = implode("", $this->orig_size) . implode("", $this->size) . implode("", $this->clip) . $this->time;
        if ($this->filter)
            $hash.= $this->filter;
        if (is_array($this->watermark)) 
            $hash.= implode("", $this->watermark);		
			
		$hash = md5($hash);

		$pi = pathinfo($this->src_path);
		$dn = $pi["dirname"];
		if ($dn == ".") $dn = "";
		$this->dst_base = path::gen("root.thumbs"); 
		$this->dst_path = $this->template['name'];
		if ($dn) $this->dst_path.= "/" . $dn;
		
		$this->dst_name = $pi["filename"] . "." . $hash . "." .  $this->dst_fmt;
	}
	protected function gen_thumb() {
		$src = $this->src_base . $this->src_path;
		$dst = $this->dst_base . $this->dst_path . "/" . $this->dst_name;

		if (file_exists($dst)) {
			// touch($dst);
			return true;
		}
	
		$this->new = true;
		$parts = explode("/", $this->dst_path);   
		$curr = "/" . trim($this->dst_base, "/");
		foreach ($parts as $p) {
			$curr.= "/" . $p;
			
			if (!file_exists($curr)) {
				mkdir($curr);
				chmod($curr, 0777);
			}
		}
		
		$src = $this->src_base . $this->src_path;
		$dst = $this->dst_base . $this->dst_path . "/" . $this->dst_name;
		
		$width = $this->size[0];
		$height = $this->size[1]; 
		
		$src_img = null;
		if ($this->src_fmt == "jpg")
			$src_img = @imagecreatefromjpeg($src); else
		if ($this->src_fmt == "gif")
			$src_img = @imagecreatefromgif($src); else
		if ($this->src_fmt == "png") 
			$src_img = @imagecreatefrompng($src); else
		if ($this->src_fmt == "webp")
			$src_img = @imagecreatefromwebp($src);
		
		if (!$src_img) {
            $this->invalid = true;
			return false;
        }
			
		$src_w = imageSX($src_img);
		$src_h = imageSY($src_img);
		
		$cx = intval($this->clip[0] * $src_w);
		$cy = intval($this->clip[1] * $src_h);
		$cw = intval(($this->clip[2] - $this->clip[0]) * $src_w);
		$ch = intval(($this->clip[3] - $this->clip[1]) * $src_h);
		
		$width = $this->size[0];
		$height = $this->size[1];
		
		$i2 = ImageCreateTrueColor($width, $height);
		imagealphablending($i2, false);
		imagecopyresampled($i2, $src_img, 0,0,$cx,$cy, $width,$height, $cw,$ch);
        
        $this->apply_filter($i2, $this->filter);
        if ($this->watermark)
            $this->gen_watermark($i2, $width, $height);
        
		if ($this->dst_fmt == "png") {
			imagesavealpha($i2, true);
			imagepng($i2, $dst); 
		} else
		if ($this->dst_fmt == "webp") {
			imagesavealpha($i2, true);
			imagewebp($i2, $dst, 90); 
		} else
		if ($this->dst_fmt == "gif") 
			imagegif($i2, $dst); else
			imagejpeg($i2, $dst, 90);

		imagedestroy($src_img);
		imagedestroy($i2); 
		
		return true; 
	}
	
	public function __construct($base, $path, $clip, $template, $format = null) {
		if (is_null($base))
			$base = path::gen("root.uploads");
			
		$this->src_base = $base;
		$this->src_path = $path;
        if ($clip === false)
		    $this->clip = array(0,0,1,1); else
            $this->clip = $clip;

		if (is_string($template)) {
			$this->template = config::get("thumbs." . $template, null); 
			$this->template['name'] = $template;
		} else
			$this->template = $template;   
		
		if ($base && $path && file_exists($base . $path)) {
			$this->gen_size();
			$this->gen_formats($format);

           if ($this->invalid) {
                $this->size = false;
                $this->result = false;            
				return;
            } 

			$this->gen_name();
			$this->gen_thumb();

			$this->result = path::gen("site.thumbs", $this->dst_path . "/". $this->dst_name); 
		} else {
			$this->size = false;
			$this->result = false;			
		}
	}
	
	public static function from_image($image, $template) {
		$base = path::gen("root.uploads");
		
		if (is_array($image))
			$image = reset($image);
			
		if (!$image)
			return new thumbnail($base, null, array(0,0,1,1), $template);
			
		$path = $image->path;
		$clip = (isset($image->clip) && is_string($image->clip)) ? explode(",", $image->clip) : null;
		if (config::get("thumbs.webp"))
			$fmt = "webp"; else
			$fmt = null;
		
		$res = new thumbnail($base, $path, $clip, $template, $fmt);
		$res->title = $image->title;
		
		return $res;
	}
	public static function create($base, $path, $clip, $template, $format = null) {
		return new thumbnail($base, $path, $clip, $template, $format);	
	}
	
	public function display($ops = null) {
		$href    = utils::value($ops, "href", "");
		$class   = utils::value($ops, "class", "");
        $cstyle  = utils::value($ops, "style", "");
		$attr    = utils::value($ops, "attr", "");
		$title   = utils::value($ops, "title", $this->title);
        
		$width = v($this->template, "width", 0);
		$height = v($this->template, "height", 0);         
        $valign = v($this->template, "valign", "center");
        $halign = v($this->template, "halign", "center");
        $mode = v($this->template, 'mode', 'fit');		
        
		$e = ($href != '') ? "a" : "div";
		$href = ($href != '') ? "href='$href'" : "";
		
        $hr = $height / $width * 100;
        
        $style = "background-position: $halign $valign;";
        if ($this->result)
            $style.= " background-image: url('$this->result');"; 
        if ($mode == 'fill') 
            $style.= " background-size: cover"; else
        if ($this->new)
            $style.= " background-size: contain"; 
        
        $res = "<$e class=\"thumbnail $class\" style=\"width: {$width}px; display: block; $cstyle\" $href $attr title=\"$title\">";
        $res.= "<div style=\"padding-top: $hr%\"></div>";
        $res.= "<div class=\"thumbnail_image\" style=\"$style\"></div>"; 
        $res.= "</$e>";
		
		return $res;
	}
	public function display_link() {
		$ops['validate'] = true;
		$ops['href'] = path::gen("site.uploads", $this->src_path);
		
		return $this->display($ops);
	}
	public function display_valid($ops = array()) {
		$ops['validate'] = true;	
		
		return $this->display($ops);
	}
	
	public function url() {
		return $this->result;	
	} 
}
