<?php

namespace Sloway;

use Sloway\path;

class BrowserHandler {
    public $mime_types = array(
        "bmp"  => "image/bmp", 
        "gif"  => "image/gif",
        "jpe"  => "image/jpeg",
		"webp" => "image/webp",
        "jpeg" => "image/jpeg",
        "jpg"  => "image/jpeg",
        "svg"  => "image/svg+xml",
        "tif"  => "image/tiff",
        "tiff" => "image/tiff",
        "ico"  => "image/x-icon",
        "png"  => "image/png"
    );
    
    public $root;
    public $db;
    public $finfo;
    public $stat = array("files" => 0, "folders" => 0);
	public function input($name, $default = null) {
		if (isset($_GET[$name])) return $_GET[$name];
		if (isset($_POST[$name])) return $_POST[$name];

		return $default;
	}
    public function __construct($root, $database) {
        $this->root = rtrim($root, "/");
        if (PHP_MAJOR_VERSION >= 5 && PHP_MINOR_VERSION >= 3)
            $this->finfo = finfo_open(FILEINFO_MIME_TYPE); else
            $this->finfo = null;
        
        $this->db = $database;
    }
    public function mime_type($path) {
        if ($this->finfo) 
            return finfo_file($this->finfo, $path);
        
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (isset($this->mime_types[$ext]))
            return $this->mime_types[$ext]; else
            return "";
    }
    public function load_tree($path = "") {
        $res = array();
        
        $dir = path::gen("root.uploads", $path);
        foreach (scandir($dir) as $name) {
            if ($name == "." || $name == "..") continue;
            
            $apath = $dir . "/" . $name;
            $_path = trim($path . "/" . $name, "/");
            if (is_dir($apath)) {                       
                $res[] = array(
                    "type" => "dir", 
                    "path" => "/" . $_path, 
                    "files" => $this->load_tree($_path)
                );    
            } 
        }
        
        return $res;
    }        
    public function gen_name($path) {
        if (!file_exists($path)) return $path;
        
        $pi = pathinfo($path);
        $prefix = ($pi["dirname"] != ".") ? rtrim($pi["dirname"], "/") . "/" : "";
        $postfix = (isset($pi["extension"]) && $pi["extension"]) ? "." . $pi["extension"] : "";          
        
        $cnt = 1;
        if (preg_match('%(.*)\(([0-9]+)\)$%', $pi["filename"], $m)) {
            $cnt = $m[2] + 1;
            $pi["filename"] = $m[1];
        }
        
        $i = 0;
        while (true) {
            $path = $prefix . $pi["filename"] . "(" . $cnt . ")" . $postfix;  
            if (!file_exists($path) || $i > 1000) break;
            
            $i++;
            $cnt++;
        } 
        return $path;        
    }
    public function copy($src, $dst) {
        $dst = rtrim($dst, "/") . "/" . pathinfo($src, PATHINFO_BASENAME);
        
        if (file_exists($dst)) 
            $dst = $this->gen_name($dst);
            
        if (is_dir($src)) {
            mkdir($dst);
            foreach (scandir($src) as $name) {
                if ($name == "." || $name == "..") continue;
                
                $this->copy($src . "/" . $name, $dst);                
            }                
        } else 
            copy($src, $dst); 
        
        @chmod($dst, 0777); 
        
        return str_replace($this->root, "", $dst);
    }  
    public function sanitize($name) {
        $name = iconv("UTF-8", "ASCII//TRANSLIT", $name);
        $name = iconv("ASCII", "UTF-8", $name);  
        
        return preg_replace("/[^a-zA-Z0-9.\-_ ]/", "", $name); 
    }

    public function move($src, $dst) {
        $dst = rtrim($dst, "/") . "/" . pathinfo($src, PATHINFO_BASENAME);        
        
        if (file_exists($dst)) 
            $dst = $this->gen_name($dst);
        
        rename($src, $dst);        
        
        return str_replace($this->root, "", $dst);
    }
    public function file_details($apath, $rpath, $size) {
        $iinfo = @getimagesize($apath);
        
        $res = array(
            "time" => \Sloway\utils::datetime(filemtime($apath)),
            "size" => filesize($apath),
            "info" => $this->mime_type($apath),
            "width" => ($iinfo) ? $iinfo[0] : 0,
            "height" => ($iinfo) ? $iinfo[1] : 0,
            "url" => path::gen("site.uploads", $rpath),
        );    
        
        if (strpos($res["info"], "image") === 0) {
            $th = thumbnail::create(path::gen("root.uploads"), $rpath, null, "admin_browser_" . $size);    
            if ($th->result)
                $res["image"] = $th->result;  
            if ($th->invalid)
                $res["corrupt"] = true;
        }
           
        unset($th);      
        
        return $res;
    }
    public function load_details($paths, $size) {
        $res = array();
        $base = path::gen("root.uploads");
        foreach ($paths as $path) 
            $res[$path] = $this->file_details($base . $path, $path, $size);
            
        return $res;
    }
    public function load_list($path, $search) {
        $res = array();
        
        $dir = $this->root . $path;
        $folders = array();
        $files = array();
        foreach (scandir($dir) as $name) {
            if ($name == "." || $name == "..") continue;
            
            $apath = $dir . "/" . $name;
            $_path = trim($path . "/" . $name, "/");
            if (is_dir($apath)) {                       
                $folders[] = array(
                    "type" => "dir", 
                    "path" => "/" . $_path, 
                );    
            } else
            if (is_file($apath)) {
                if ($search && strpos(strtolower($name), strtolower($search)) === false) continue;
                $file = array(
                    "type" => "file",
                    "path" => "/" . $_path 
                );
                
                if ($this->count < 10)
                    $file["details"] = $this->file_details($apath, $_path, $this->input("image_size"));
                
                $this->count++;
                
                $files[] = $file;
            }
        }
        
        $this->stat["files"] = count($files);
        $this->stat["folders"] = count($folders);
        
        return array_merge($folders, $files);
    }
    public function delete_tree($path) {
        if (is_dir($path)) {
            foreach (scandir($path) as $name) {
                if ($name == "." || $name == "..") continue;
                
                $this->delete_tree($path . "/" . $name);
            }
            rmdir($path);
        } else {
            $this->db->query("DELETE FROM `images` WHERE path = ?", trim($path, "/"));
            $this->db->query("DELETE FROM `files` WHERE path = ?", trim($path, "/"));
            
            unlink($path);   
        }
    }
    public function execute() {      
        $result = array("status" => true, "message" => null, "error" => null);
        
        if ($path = $this->input("upload")) {
            $file = $_FILES["files"];
            $src_path = $_FILES["files"]["tmp_name"][0];
            
            $org_path = $path;
            $pi = pathinfo($path);
            $dir = $pi["dirname"];
            $name = $this->sanitize($pi["basename"]);
            
            $new_path = $dir . "/" . $name;
            $dst_path = path::gen("root.uploads", $new_path);
            
            @copy($src_path, $dst_path);
            @chmod($dst_path, 0777);
            
            $info = $this->file_details($dst_path, trim($new_path, "/"), $this->input("image_size"));
            $info["type"] = "file";
            $info["path"] = $new_path;
            
            $result["file"] = $info;
            $result["orig_path"] = $org_path;
            
            $result["message"] = "Successfully uploaded file '" . pathinfo($path, PATHINFO_BASENAME) . "'";
        }
        
        if ($path = $this->input("create_folder")) {
            $name = $this->sanitize($this->input("name"));
            $res = @mkdir(path::gen("root.uploads", $path . $name), 0777);
            
            if ($res) 
                $result["message"] = "Successfully created folder '$name'"; else
                $result["error"] = "Error creating folder '$name'"; 
        }
        
        if ($paths = $this->input("delete")) {
            foreach ($paths as $path)
                $this->delete_tree(path::gen("root.uploads", $path));  
               
            $result["message"] = "Successfully deleted " . count($paths) . " folder(s)/files(s)";  
        }
        
        if ($paths = $this->input("copy")) {
            $result["files"] = array();
            
            foreach ($paths as $path) 
                $result["files"][] = $this->copy($this->root . $path, $this->root . $this->input("dest")); 
                
            $result["message"] = "Successfully copied " . count($paths) . " folder(s)/files(s)";  
        }

        if ($paths = $this->input("move")) {
            $result["files"] = array();
            
            foreach ($paths as $path) 
                $result["files"][] = $this->move($this->root . $path, $this->root . $this->input("dest"));
                
            $result["message"] = "Successfully moved " . count($paths) . " folder(s)/files(s)";  
        }
            
        if ($path = $this->input("rename")) {
            $old_path = path::gen("root.uploads", $path);
            $old_name = pathinfo($old_path, PATHINFO_BASENAME);
            $new_name = $this->sanitize($this->input("name"));
            $new_path = str_replace($old_name, $new_name, $old_path);
            
            if (file_exists($new_path)) {
                $c = is_dir($new_path) ? "directory" : "file";
                $result["error"] = "Cannot rename '$old_name', $c '$new_name' already exists"; 
            } else
            if (@rename($old_path, $new_path)) {
                $result["path"] = str_replace($this->root, "/", $new_path);
                $result["message"] = "Successfully renamed '$old_name' to '$new_name'";
            } else 
                $result["error"] = "Error renaming '$old_name' to '$new_name'";
        }

        if ($paths = $this->input("load_details")) 
            $result["details"] = $this->load_details($paths, $this->input("image_size"));
                
        if ($path = $this->input("load")) {
            if (!file_exists($this->root . $path))
                $path = "/";
            
            $this->count = 0;
            $result["tree"] = $this->load_tree("/");  
            $result["list"] = $this->load_list($path, $this->input("search"));
            $result["path"] = $path;
            
            if ($path == "/")
                $path = "Root";
            if (!$result["error"] && !$result["message"])
                $result["message"] = "Loaded directory '$path': " . $this->stat["folders"] . " folder(s), " . $this->stat["files"] . " file(s)";
        }
        
        return json_encode($result);
    }    
} 
