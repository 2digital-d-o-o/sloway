<?php

namespace Sloway;

class catalog_category_base extends dbModelObject {
    public function __get($name) {
        if ($name == "title_code") {
            $res = $this->title;
            if ($this->code)
                $res.= "&nbsp;<span style='color: silver'>[" . $this->code . "]</span>";
            
            return $res; 
        } else
            return parent::__get($name);    
    }

    public function __load($param) {
        if ($this->visible != 1 && catalog::$validate_visible) 
            return false;

/*        
        if ($pth = Settings::get("category_def_image")) {
            $this->image = $pth;
            $this->image_site = path::gen("site.uploads", $pth);
        }
        if (!$this->image && $pth = Settings::get("default_image")) {
            $this->image = $pth;
            $this->image_site = path::gen("site.uploads", $pth);
        }
        
        $images = dbClass::load("images", "@module = 'catalog_category' AND module_id = '$this->id' ORDER BY id_order ASC");   
        if (count($images)) {
            $pth = path::gen("root.uploads", $images[0]->path);
            if (file_exists($pth) && $size = @getimagesize($pth)) {
                $this->image = $images[0]->path;
                $this->image_site = path::gen("site.uploads", $this->image);
            }
        } 
 * 
 */
    }    
    
    public function image() {
        return count($this->images) ? $this->images[0]->path : null;
    }        
}

