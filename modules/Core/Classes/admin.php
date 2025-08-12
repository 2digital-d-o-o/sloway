<?php
namespace Sloway;

class admin {
    public static $permissions = array();
	public static $user;
	public static $db;
	public static $admin_lang;
	public static $admin_edit_lang;
	public static $content_lang;

	public static $module_icon = null;
	private static $config = '';
	private static $count = 0; 
    private static $counter = 0;
	
	private static function ml_field($name, $value, $ml_mode, $class, $param, $callback) {
		$res = "";
		
		$langs = lang::languages(true);
		$cl = isset($_COOKIE[core::$project_name . "_admin_form_lang"]) ? $_COOKIE[core::$project_name . "_admin_form_lang"] : null;
		if (!$cl || !in_array($cl, $langs))
			$cl = reset($langs);
		
		if (count($langs) > 1) {
			$id = Admin::GenerateID();
			$res = "<div id='$id' class='admin_lang_selector $class'>";
			$res.= "<script>\$(document).module_loaded(function() { \$('#$id').admin_lang_selector() });</script>";
			$res.= "<span class='admin_expand'></span>";

			$first = false;
			foreach ($langs as $lang) {
				$cls = ($lang == $cl) ? "selected" : "";
				$res.= "<span class='admin_lang_flag admin_lang_button $cls' data-lang='$lang' title='" . t("lang_$lang") . "'>" . et("lang_abbr_" . $lang) . "</span>";
			}
			
			$res.= "</div>";
		} else {
			$res = "";
		}
		
		foreach ($langs as $lang) {
			$val = v($value, $lang, "");
			$def_val = "";

			$cls = "";
			if ($lang == mlClass::$def_lang) 
				$postfix = ""; else
				$postfix = ":" . $lang;

			if ($lang == $cl)
				$cls = "selected";
			
			$res.= "<div class='admin_content_ml $cls' data-lang='$lang'>";
			$res.= "<span class='admin_lang_flag' data-lang='$lang' title='" . t("lang_$lang") . "'>" . et("lang_abbr_" . $lang) . "</span>";
			$res.= call_user_func($callback, $name, $postfix, $val, $lang, $def_val, $param); 
			$res.= "</div>";
		}
		
		return $res;
	}	

	
    public static function GenerateID() {
        return "admin_" . str_replace(".", "", microtime(true)) . self::$counter++;        
    }
	
	public static function MassGenerateUrls($values, $lang, $module) {
		$result = array();
		$slugs = array();
		$map = array();
		$map_orig = array();
		foreach (self::$db->query("SELECT * FROM slug WHERE lang = '$lang'")->getResult() as $q) {
			$q_tag = $q->module . "-" . $q->module_id;
			$map_orig[$q_tag] = $q->slug;
			$map[$q_tag] = preg_replace('/-[0-9]*$/', '', $q->slug);
			$slug = $q->slug;
			$pf = "0";
			if (preg_match("/(.*?)[\-]+(\d+)$/", $q->slug, $vars)) {
				$slug = $vars[1];
				$pf = $vars[2];
			}
			if (!isset($slugs[$slug]))
				$slugs[$slug] = array();

			$slugs[$slug][$pf] = 1;
		}
		
		$insert = array();
		foreach ($values as $_tag => $slug) {
			$slug = preg_replace('/-[0-9]*$/', '', $slug);
			$tag = $module . "-" . $_tag;
			if (isset($map[$tag]) && $map[$tag] == $slug) {
				$result[$_tag] = $map_orig[$tag];
				continue;
			} 

			$pf = 0;
			if (isset($slugs[$slug])) {
				$used = $slugs[$slug];
				$pf = null;
				for ($i = 0; $i < 10; $i++) {
					if (!isset($used[$i])) {
						$pf = $i;
						break;
					}
				}
				$slugs[$slug][$pf] = 1;
			} else 
				$slugs[$slug] = array("0" => 1);

			if ($pf)
				$slug.= "-" . $pf;

			$result[$_tag] = $slug;

			$insert[]= array(
				"module" => $module,
				"module_id" => $_tag,
				"lang" => $lang,
				"slug" => $slug
			);
		}
		
		// echod($insert);
		
		dbUtils::insert_update(self::$db, "slug", $insert, true);
		return $result;
	}	
	public static function GenerateUrl($module, $module_id, $lang, $title) {
		$title = trim($title);
		if (!strlen($title)) {
			self::$db->query("DELETE FROM slug WHERE module = ? AND module_id = ? AND lang = ?", [$module, $module_id, $lang]);
			return "";
		}
		
		$slug = slug::gen($title);
		$slug = preg_replace('/-[0-9]*$/', '', $slug);

		$obj = null;
		$q = self::$db->query("SELECT * FROM slug WHERE module = ? AND module_id = ? AND lang = ?", [$module, $module_id, $lang])->getResult();
		if (count($q))
			$obj = $q[0];

		$used = array();
		
		foreach (config::get("admin.reserved_slugs", []) as $res) {
			if ($res == $slug) $used[0] = 1;
		}
		
		$q = self::$db->query("SELECT * FROM slug WHERE lang = '$lang' AND (slug = '$slug' OR slug REGEXP '^$slug-[0-9]+$')")->getResult();
		foreach ($q as $qq) {
			if ($qq->module == $module && $qq->module_id == $module_id) continue;

			$ind = ($slug == $qq->slug) ? 0 : str_replace($slug . "-", "", $qq->slug);
			$used[$ind] = 1;
		}

		$index = 0;
		for ($i = 0; $i < 100; $i++) {	
			if (!isset($used[$i])) {
				$index = $i;
				break;
			}
		}
		if ($index > 0)
			$slug.= "-" . $index;
			
		if ($obj)
			self::$db->query("UPDATE slug SET slug = ? WHERE id = ?", [$slug, $obj->id]); else
			self::$db->query("INSERT INTO slug (module, module_id, lang, slug) VALUES (?,?,?,?)", [$module, $module_id, $lang, $slug]);
		
		return $slug;
	}
	public static function GenerateUrls($module, $obj) {
		foreach (lang::languages(true) as $lang) {
			$curl = $obj->get("custom_url", $lang);
			if (!$curl)
				$curl = $obj->get("title", $lang);
			
			$gurl = Admin::GenerateUrl($module, $obj->id, $lang, $curl);
			
			$obj->set("url", $gurl, $lang); 
		}	
	}
	public static function LoadUrls($object) {
		$urls = $object->get_ml("url");
		$res = array();
		foreach ($urls as $lang => $uri) {
			if ($lang == "_def") continue;
			
			$res[$lang] = router::encodeUri($uri, $lang, true);
		}
		
		return $res;		
	}
	public static function Value($obj, $path, $def = '') {
		if (!$obj) return $def;
		
		$p = explode('.', $path);    
		foreach ($p as $pp) {
			if (is_array($obj)) {
				if (!isset($obj[$pp])) return $def; 
				$obj = $obj[$pp];
			} else 
			if (is_object($obj)) {
				try {
					$obj = $obj->$pp;
				} catch (Exception $e) {
					return $def; 
				}
			} else
				return $def;
		}
		
		return $obj;
	}
	public static function JSLink($title, $code, $attr = "") {
		return "<a href='#' $attr onclick=\"$code;return false;\">$title</a>";
	}

    public static function FormAction() {
        $input = Input::instance();
        if ($input->post("form_save"))
            return "save"; else                
        if ($input->post("form_close"))
            return "save_close"; else                
        if ($input->post("form_cancel"))
            return "cancel";

        return null;
    }
	public static function FormBegin($submit, $attr = '') { 
		$submit = url::site($submit);
        
		$res = "<form class='admin_form' action='$submit' method='post' $attr>";
        $res.= "<input type='hidden' name='form_action' value=''>";
		$res.= "<input type='hidden' name='form_lang' value=''>";
        
        return $res;
	}
	public static function FormEnd($buttons = null, $clear = true) { 
		if ($buttons === null) 
			$buttons = array(
				'save' => t('Save'),
				'close' => t('Save and close'),
				'!cancel' => t('Cancel')
			);

		$res = "<div>";
		foreach ($buttons as $name => $title) {
			if ($name[0] == '!') {
				$name = substr($name,1);
				$a = 'right';
			} else
				$a = 'left';
										
			$res.= Admin::Submit($title, $name, $a, false);
		}
		
		if ($clear)
			$res.= "<div style='clear: both'></div>";
		$res.= '</div></form>';
		
		return $res;
	}
    
    public static function AjaxForm_Begin($submit, $ops = null) {
        $submit = url::site($submit);
		
        $attr = v($ops, "attr", "");
        $res = "<script>\$(document).module_loaded(function() { \$('#module_form').admin_form() });</script>";   
        $res.= "<input type='hidden' id='module_form_data'>";
		$res.= "<input name='admin_form_lang' type='hidden' id='module_form_lang'>";
        $res.= "<form id='module_form' class='admin_ajax_form' action='$submit' method='post' $attr>";
                
        return $res;    
    }
    public static function AjaxForm_End() { 
        $res = "<div class='admin_form_menu'></div>";
        $res.= '</form>';
        
        return $res;
    }
    public static function EditMenu($ops = null) {
        $back_url = v($ops, "back", "");
		$save_close = v($ops, "save_close", true);
        $view_url = v($ops, "view_url", "");
        $visible = v($ops, "visible", null);
        $preview = v($ops, "preview", false);        
        $history = v($ops, "history", null);
        
        $res = "<div class='admin_section admin_form_menu'>";
        
        if ($view_url) {
            $res.= "<h2 class='admin_section_header'>" . et("URL") . "</h2>";
			$langs = lang::languages(true);
			$cl = isset($_COOKIE[core::$project_name . "_admin_form_lang"]) ? $_COOKIE[core::$project_name . "_admin_form_lang"] : null;
			if (!$cl || !in_array($cl, $langs))
				$cl = reset($langs);	
			
			if (count($langs) > 1) {
				$res.= "<div class='admin_lang_selector' style='margin-bottom: 5px'>";
				foreach ($langs as $lang) {
					$cls = ($lang == $cl) ? "selected" : "";
					$res.= "<span class='admin_lang_flag $cls' data-lang='$lang' title='" . t("lang_$lang") . "' onclick='$.admin.lang_selector.toggle.apply(this)'>" . t("lang_abbr_$lang") . "</span>";
				}		
				$res.= "</div>";
			}
			if (is_array($view_url)) {
				foreach ($view_url as $lang => $url) {
					$cls = ($lang == $cl) ? "selected" : "";
					$res.= "<div class='admin_content_ml $cls' data-lang='$lang'>";
					$res.= "<a href='" . $url . "' target='_blank' style='display: block; word-wrap: break-word'>" . $url . "</a><br><br>";
					$res.= "</div>";
				}
				
			} else
				$res.= "<a href='" . $view_url . "' target='_blank' style='display: block; word-wrap: break-word'>" . $view_url . "</a><br><br>";			
        }
        
        $res.= "<h2 class='admin_section_header'>" . et("Options") . "</h2>";
        $res.= "<form class='module_menu_form'>";
        if (!is_null($visible)) 
            $res.= acontrol::checkbox("visible", $visible, array("label" => t("Visible"))); 
        $res.= "</form>";
        
        if ($view_url && $preview) {
            $url = url::query($view_url, "preview=1");
            $res.= "<button class='admin_button_preview admin_button vertical' onclick='return false' data-url='$url'>" . et("Preview") . "</button>";
        }
            
        $res.= "<button class='admin_button_save admin_button vertical' onclick='return false'>" . et("Save") . "</button>";
        
        $ajax = isset($_POST["module_ajax"]) ? 1 : 0;        
        if ($back_url) {
			if ($save_close) 
				$res.= "<button class='admin_button_close admin_button vertical' onclick='return false' data-url='$back_url' data-ajax='$ajax'>" . et('Save and close') . "</button>";
            $res.= "<button class='admin_button_cancel admin_button vertical' onclick='return false' data-url='$back_url' data-ajax='$ajax'>" . et("Close") . "</button>";    
        }
        
        $res.= "</div>";
        
        return $res;
    }

    public static function FormData() {
        $res = "";
        $args = func_get_args();
        foreach ($args as $fields) {
            foreach ($fields as $name => $value) 
                $res.= "<input type='hidden' name='$name' value='$value'>";
        }
        
        return $res;
    }
    
	public static function Field($title, $content, $attr = '') {
        if (is_numeric($attr))
            $attr = "style='width: {$attr}px'";
            
		$res = "<div class='admin_field' $attr>";
		$res.= "<div class='admin_field_header'>$title</div>";
		$res.= "<div class='admin_field_content'>$content</div>";
		$res.= "</div>";
		
		return $res;
	}

	public static function Edit($name, $value, $ml_mode = false, $param = array()) {
		if ($ml_mode) {
			return self::ml_field($name, $value, $ml_mode, "admin_edit", $param, function($name, $postfix, $val, $lang, $def_val, $param) {
				return acontrol::edit($name . $postfix, $val, $param);
			});
		}
		
		return acontrol::edit($name, v($value, $name), $param);
	}
    public static function CheckBox($name, $value, $ml_mode = false, $param = array()) {
		if ($ml_mode) {
			return self::ml_field($name, $value, $ml_mode, "admin_check", $param, function($name, $postfix, $val, $lang, $def_val, $param) {
				return acontrol::checkbox($name . $postfix, $val, $param);
			});
		}
        
		return acontrol::checkbox($name, v($value, $name), $param);
	}
	public static function Select($name, $items, $value, $ml_mode = false, $ops = null) {
		if ($ml_mode) 
			return self::ml_field($name, $value, $ml_mode, "admin_select", [$ops, $items], function($name, $postfix, $val, $lang, $def_val, $param) {
				$ops = $param[0];
				$lst = $param[1];
				if (!is_null($def_val) && isset($lst[$def_val]))
					$ops["placeholder"] = $lst[$def_val];
				
				return acontrol::select($name . $postfix, $param[1], $val, $ops);
		});
		return acontrol::select($name, $items, v($value, $name), $ops);
	}
	
	public static function SEO($obj, $ml = false) {
		$res = Admin::Field(et('Custom URL'), Admin::edit('custom_url', $obj->get_ml("custom_url"), $ml));
		$res.= Admin::Field(et('Canonical URL'), Admin::edit('canonical_url', $obj->get_ml("canonical_url"), $ml));
		$res.= Admin::Field(et('Title'), Admin::edit('meta_title', $obj->get_ml("meta_title"), $ml));
		$res.= Admin::Field(et('Keywords'), Admin::edit('meta_keys', $obj->get_ml("meta_keys"), $ml));
		$res.= Admin::Field(et('Description'), Admin::edit('meta_desc', $obj->get_ml("meta_desc"), $ml));
		$res.= Admin::Field(et('Head content'), Admin::edit('meta_head', $obj->get_ml("meta_head"), $ml, array("lines" => 5)));
		$res.= Admin::Field(et('Body content'), Admin::edit('meta_body', $obj->get_ml("meta_body"), $ml, array("lines" => 5)));
		
		return $res;
	}
	public static function LineBegin() {
		echo "<div class='admin_line'>";
	}
	public static function LineEnd() { 
		echo "</div>";    
	}
	public static function FieldV($title, $content, $attr = '') {
		$res = "<div class='admin_field vertical' $attr>";
		$res.= "<div class='admin_field_header'>$title</div>";
		$res.= "<div class='admin_field_content'>$content</div>";
		$res.= "</div>";
		
		return $res;
	}
	public static function FieldS($content, $attr = '') {
		$res = "<div class='admin_field' $attr>$content</div>";
		
		return $res;
	}
    
    public static function Column1() {
        return "<div class='admin_columns'><div class='admin_column1'>";    
    }
    public static function Column2() {
        return "</div><div class='admin_column2'>";
    }
    public static function ColumnEnd() {
        return "</div></div>";
    }
    public static function Columns() {
        $args = func_get_args();
        $res = "<div style='overflow: auto'>";
        
        $cnt = count($args);
        if (!$cnt) return "";
        
        $w = (100 - ($cnt-1)*2) / $cnt;
        foreach ($args as $i => $arg) {
            $m = ($i < $cnt-1) ? "margin-right: 2%" : "";
            $res.= "<div style='float: left; width: $w%; $m'>" . $arg . "</div>";    
        }
        
        $res.= "</div>";
        return $res;
    }
    
    public static function GridBegin() {
        return "<div class='admin_field_grid'>";
    }
    public static function GridEnd() {
        return "</div>";    
    }

	public static function ESectionBegin($title, $name = null, $size = 1, $attr = "", $exp = null) {
		$id = $name . "_" . url::current();
		
		if ($exp === null && $name)
			$exp = userdata::get($id, false);
			
		$c = ($exp) ? "expanded" : "collapsed";
		$res = "<div class='admin_section expandable $c' name='$name' id='$id'>";
		$res.= "<h$size class='admin_section_header'>$title</h$size>";
		$res.= "<div class='admin_section_content'>";
			
		return $res;
	}
	public static function SectionBegin($title = null, $bordered = true, $size = 2, $name = "") {
		$cls = ($bordered) ? " bordered" : "";
		$res = "<div class='admin_section$cls' name='$name'>";
		if ($title)
			$res.= "<h$size class='admin_section_header'>$title</h$size>";
			
		return $res;
	}
	public static function SectionEnd() {
		return "</div>";    
	}
	public static function ESectionEnd() {
		return "</div></div>";    
	}
	
	public static function Button($title, $href, $align = 'left', $clear = true, $attr = '', $class = '') {
		if ($align == 'left')
			$s = "style='float: left; margin-right: 5px'"; else
        if ($align == "right") 
			$s = "style='float: right; margin-left: 5px'"; else
            $s = "";
		
        if (!$href) 
            $res = "<button $attr type='button' onclick='return false' class='admin_button $class' $s>$title</button>"; else
        if (strpos($href, "ajax:") === 0)
            $res = "<button $attr type='button' data-url='" . trim(str_replace("ajax:", "", $href)) . "' onclick='return admin_redirect(this)' class='admin_button $class' $s>$title</button>"; else
            $res = "<button $attr type='button' onclick='window.location.href=\"$href\"' class='admin_button $class' $s>$title</button>";
			
		if ($clear)
			$res.= "<div style='clear: $align'></div>";
            
		return $res; 
	}
	public static function ButtonV($title, $href) {
		echo Admin::Button($title, $href, null, false, "", "vertical");
	}
	public static function ButtonS($title, $href, $align = 'left', $clear = true, $attr = '', $class = '') {
        return Admin::Button($title, $href, $align, $clear, $attr, trim($class . " small"));
	}
    public static function ButtonI($icon, $href, $title = '', $onclick = false, $attr = '', $class = '') {
        $parts = "";
        $img = \Sloway\utils::icon($icon);
        if ($href) {
            if (strpos($href, "ajax:") === 0) {
                $href = trim(str_replace("ajax:", "", $href));
                $onclick = "return admin_redirect(this)";    
            } 
            
            $parts.= " href='$href'";
        }
        
        if ($onclick === false)
            $parts.= " onclick='return false'"; else
        if ($onclick)
            $parts.= " onclick='$onclick'";
        if ($attr)
            $parts.= " " . $attr;
        
        return "<a class='admin_icon big $class' title='$title' " . trim($parts) . "><img src='$img'></a>";
    }
	
	public static function Submit($title, $name, $align = 'left', $clear = true) {
		if ($align == 'left')
			$s = "style='float: left; margin-right: 5px'"; else
			$s = "style='float: right; margin-left: 5px'";
		
		$res = "<input type='submit' name='form_$name' value='$title' class='advbutton admin_button' $s onclick='\$.admin.form_action(this)'>";
		if ($clear)
			$res.= "<div style='clear: $align'></div>";
		return $res; 
	}
	public static function Clear() {
		return "<div style='clear: both'></div>";    
	}
	
	public static function Separator() {
		return '<div class="admin_separator admin_field"></div>';    
	}
	
	public static function TabsBegin($tabs2, $curr = '', $script = true, $tab_width = null, $name = '') {
		$js = ($script) ? 'js' : '';
		$res = "<div class='admin_tabs $js' name='$name'>";
		$res.= '<div class="admin_tabs_header">';

		$tabs = array();
		foreach ($tabs2 as $name => $title) {
			if ($title !== false)
				$tabs[$name] = $title;    
		}

		if ($curr !== false && $curr == '' && count($tabs)) {
			$k = array_keys($tabs);
			$curr = $k[0];
		}
			
		foreach ($tabs as $tname => $ttitle) { 
			$sel = ($tname == $curr) ? "selected" : "";
			$res.= "<div class='admin_tabs_tab $sel' page='$tname'>$ttitle</div>";
		}
		$res.= "</div>";
		
		return $res;
	}
	public static function TabsPage($name, $content) { 
		return "<div class='admin_tabs_page' page='$name'>" . $content . "</div>";
	}
	public static function TabsEnd() {
	}
	public static function Tabs($tabs, $curr, $content) {
		$c = admin::TabsBegin($tabs, $curr, false, 120);
		$c.= admin::TabsPage($curr, $content);
		$c.= admin::TabsEnd();

		return $c;
	}
	
	public static function CTabsBegin($tabs2, $curr = null, $ops = null) { 
        $script = v($ops, "script", false);
        $tab_width = v($ops, "tab_width", null);
        $name = v($ops, "name", "");
        $menu = v($ops, "menu", "");
        
		if ($script && $name) {
			$id = $name . "_" . url::current();
			if ($curr == null)
				$curr = userdata::get($id, null);
		} else
			$id = "";
		
		$js = ($script) ? 'js' : '';
		$res = "<div class='admin_ctabs $js' name='$id'>";
		$res.= '<div class="admin_ctabs_header">';
		
		if ($tab_width)
			$s = "style='width: {$tab_width}px'"; else
			$s = "";
		
		$tabs = array();
		foreach ($tabs2 as $name => $title) {
			if ($title !== false)
				$tabs[$name] = $title;    
		}

		if ($curr !== false && $curr == '' && count($tabs)) {
			$k = array_keys($tabs);
			$curr = $k[0];
		}
			
		foreach ($tabs as $tname => $ttitle) { 
			$sel = ($tname == $curr) ? "selected" : "";
			$res.= "<div $s class='admin_ctabs_tab $sel' page='$tname'>$ttitle</div>";
		}
		
        if ($menu)
		    $res.= "<div class='admin_ctabs_menu'>$menu</div>";
		$res.= "</div>";
		return $res;
	}
	public static function CTabsPageBegin($name) {
		return "<div class='admin_ctabs_page' page='$name' style='display: none'>";    
	}
	public static function CTabsPageEnd() {
		return "<div style='clear: both'></div></div>";    
	}
	public static function CTabsPage($name, $content) { 
		return "<div class='admin_ctabs_page' page='$name'>" . $content . "<div style='clear: both'></div></div>";
	}
	public static function CTabsEnd() {
		return "</div>";
	}
    
    public static function Confirm($action, $replace = null) {
        $res = '<div class="admin_message warning">' . et("admin_confirm_prefix") . " " . et($action) . '?</div>';    
        if ($replace)
            $res = strtr($res, $replace);
        
        return $res;
    }
    public static function IndexPages($ind, $link, $attr = '') {
        $res = "<div class='admin_index_pages' $attr link='$link'>";
        if ($ind->prev) {                                                    
            $l = url::site(preg_replace('/%p/', $ind->prev, $link));
            $res.= "<a href='$l' class='admin_index_pages_prev'><</a>";
        }
        foreach ($ind->buttons as $b) {
            if ($b == 0)
                $res.= "<span class='admin_index_pages_sep'>...</span>"; else
            if ($b == $ind->curr) {
                $res.= acontrol::select("admin_index_page", arrays::fill(1, $ind->num_pages, true), $ind->curr);
            }
            else {
                $l = url::site(preg_replace('/%p/', $b, $link));
                $res.= "<a class='admin_index_pages_page' href='$l'>$b</a>";
            }
        }
        
        if ($ind->next) {
            $l = url::site(preg_replace('/%p/', $ind->next, $link));
            $res.= "<a href='$l' class='admin_index_pages_next'>></a>";
        }

        $res.= "</div>";
        return $res;
    }
    public static function Header($c, $attr = '') {
        return "<div class='admin_header' $attr>$c</div>";
    }
    public static function BoxBegin($name, $title, $visible = false) {
        $s = ($visible) ? "style='display: block'" : "";
        $c = ($visible) ? "exp" : "";
        
        $r = "<div class='admin_header admin_box_header' name='$name'>";
        $r.= "<div class='admin_box_icon $c'></div>" . $title . "</div>";
        $r.= "<div class='admin_box_content' name='$name' $s>";
        return $r;
    }
    public static function BoxEnd() {
        return "</div>";
    }
	
	public static function LangSelect($titles = true, $content = false, $editable = false) {
		$r = array();
		
		foreach (lang::languages($content) as $code => $title) {
			$i = "<img src='" . path::gen('site.modules.Core', "media/img/flags/$code.png") . "'/>";
			if ($titles === true)
				$i.= "&nbsp;" . t("lang_$code", null, $editable); else
			if ($titles === 'code')
				$i.= "&nbsp;" . strtoupper($code);
			$r[$code] = $i;
		}        
		
		return $r;
	}
	public static function Icon($icon, $href = false, $title = "", $onclick = null, $attr = "", $class = "") {
        $parts = "";
        $img = \Sloway\utils::icon($icon);
        if ($href) {
            if (strpos($href, "ajax:") === 0) {
                $href = trim(str_replace("ajax:", "", $href));
                $onclick = "return admin_redirect(this)";    
            } 
            
            $parts.= " href='$href'";
        }
        
        if ($onclick === false)
            $parts.= " onclick='return false'"; else
        if ($onclick)
            $parts.= " onclick='$onclick'";
        if ($attr)
            $parts.= " " . $attr;
        
        return "<a class='admin_icon $class' title='$title' " . trim($parts) . "><img src='$img'></a>";
	}
	public static function IconB($icon, $href = false, $title = "", $onclick = null, $attr = "", $class = "") {
		$class.= " big";
		return Admin::Icon($icon, $href, $title, $onclick, $attr, $class);
	}	
	public static function Accessible($what) {
		if (!core_module::$user) return false;
		
		return !preg_match('%' . $what . '($|[^:])%', core_module::$user->deny_access);
	}
	public static function CtrlIcon($ctrl) {
		$ctrl = explode("/", $ctrl, 2);
		$ctrl = $ctrl[0];
		
		$icon = config::get("admin.icons.$ctrl", null);
		echod(get_parent_class($ctrl));    
	}
	public static function Operation($op) {
		if (!core_module::$user) return false;
		
		return (strpos(core_module::$user->operations, $op) !== false);
	}
	public static function StoreRecent($title) {
        $uid = admin_user::instance()->user_id;
        if (!$uid) $uid = "";
		//$uid = $this->admin_user->id;
		$url = url::current();
		$db = Database::instance();
        
		$curr = $db->query("SELECT * FROM admin_recent WHERE id_user = '$uid' AND url = '$url'");
		if (count($curr)) {
			$db->query("UPDATE admin_recent SET time = ? WHERE id = ?", time(), $curr[0]->id);    
			return;
		} 
		
		$ctrl = explode("/", $url, 2);           
		$icon = config::get("admin.icons." . $ctrl[0], "");
		
		$db->query("INSERT INTO admin_recent (id_user, url, title, time, icon) VALUES (?,?,?,?,?)", $uid, $url, $title, time(), $icon);
		$q = $db->query("SELECT * FROM admin_recent WHERE id_user = $uid ORDER BY time ASC");
		if (count($q) > 10)
			$db->query("DELETE FROM admin_recent WHERE id = ?", $q[0]->id);                
	}
    
    public static function Auth($subject, $ctrl = null, $dbg = false) {
        $user = self::$user;
        if (!$user && !$ctrl) return false;
        
        if (!in_array($subject, self::$permissions)) return true;
        
        $role = $user->role;
        if ($role) 
            $res = strpos("," . $role->permissions . ",", "," . $subject . ",") !== false; else
            $res = true;
        
        if (!$res && $ctrl) {
            $ctrl->deny_access = true;
            return false;
        }
                        
        return $res;
    }
    
    public static function EditTree_Build($nodes, $builder, $tree_name, $level, $types, $ops) {
        $res = "<ul class='admin_et_nodes'>";
        foreach ($nodes as $node) {
            $type = v($node, "type", "");

            $cfg = call_user_func($builder, "node", $type, v($node, "data", new genClass()), $types, $ops);
            $drop = v($cfg, "drop", "");
            $name = v($cfg, "name", "");
            $id = v($cfg, "id", "");
            
            $res.= "<li class='ac_noautogen admin_et_node' data-type='$type' data-drop='$drop' data-id='$id'>";    
            $res.= "<div class='admin_et_item' data-name='$name'>";
            
            $res.= "<div class='admin_eti_hook dyntree_hook'></div>";
            
            $res.= "<input type='hidden' data-name='id' value='$id'>";
            $res.= v($cfg, "content", "");
            $res.= "</div>";
            
            if ($sub = v($node, "nodes", null))
                $res.= Admin::EditTree_Build($sub, $builder, $tree_name, $level+1, $types, $ops); else
                $res.= "<ul class='admin_et_nodes'></ul>";
            
            $res.= "</li>";
        }    
        $res.= "</ul>";
        
        return $res;
    }
    public static function EditTree_Node($type, $data, $children = array()) {
        return array(
            "type" => $type,
            "data" => $data,
            "nodes" => $children
        );
    }
    public static function EditTree($name, $nodes, $builder, $types = null, $ops = null) {
        $title = v($ops, "title", "&nbsp;");
        
        $res = "";
        $id = v($ops, "id", Admin::GenerateID());
        if (v($ops, "autogen", true))
            $res = "<script>\$(document).module_loaded(function() { \$('#$id').admin_edittree() });</script>";        
        $count = v($ops, "count", 0);
        
        $res.= "<div id='$id' class='admin_edittree' data-name='$name' data-count='$count'>";

        $tree_cfg = call_user_func($builder, "root", null, null, $types, $ops);
        
        $res.= "<div class='admin_et_menu'>";
        $res.= v($tree_cfg, "menu", "");
        $res.= "<h2>" . $title . "</h2>";
        $res.= "</div>";

        $res.= "<div class='admin_et_deleted'></div>";
        $res.= "<div class='admin_et_templates' style='display: none'>";
        if (is_array($types)) 
        foreach ($types as $type) {
            $cfg = call_user_func($builder, "node", $type, new genClass(), $types, $ops);
            
            $drop = v($cfg, "drop", "");
            $item_name = v($cfg, "name", "");
            
            $res.= "<ul data-type='$type'>";
            $res.= "<li data-type='$type' data-drop='$drop' class='ac_noautogen'>";    
            $res.= "<div class='admin_et_item' data-name='$item_name'>";
            $res.= "<div class='admin_eti_hook' draggable=true></div>";
            $res.= "<input type='hidden' data-name='id' data-fname='id'>";
            $res.= v($cfg, "content");
            $res.= "</div>";
            $res.= "<ul class='admin_et_nodes'></ul>";
            $res.= "</li>";
            $res.= "</ul>";
        }
        $res.= "</div>";
        
        $drop = v($tree_cfg, "drop", "");
        
        $res.= "<div class='admin_et_root' data-drop='$drop'>";
        $res.= Admin::EditTree_Build($nodes, $builder, $name, 0, $types, $ops);
        $res.= "</div>";
        $res.= "</div>";                    
        
        return $res;
    }
    
    public static function ImageList_Builder($mode, $type, $image, $types, $ops) {
        $res = array();
        if ($mode == "root") {
            $res["drop"] = "image";
            $res["menu"] = "<a href='#' class='admin_button_add' onclick='return false'>" . t("Add") . "</a>"; 
        } else
        if ($mode == "node") {
            $res["id"] = $image->id;
            $res["content"] = view("\Sloway\Views\Admin\ImageList_Item", array("image" => $image));
        }
        
        return $res;
    }                                           
    public static function ImageList_Save($name, $module, $module_id, $hid = false) {
        $db = self::$db;
        
        $images = v($_POST, $name);
        $delete = v($_POST, $name . "_delete_image");
        if (is_array($delete) && count($delete))
            $db->query("DELETE FROM `images` WHERE id IN (" . implode(",", $delete) . ")");        
        
        if (is_array($images))
        foreach ($images as $i => $image) {
            $img = mlClass::load("images", "@id = '" . $image["id"] . "'", 1);   
            if (!$img)
                $img = mlClass::create("images");
                
            $img->id = $image["id"];
            $img->module = $module;
            $img->module_id = $module_id;   
            $img->visible = v($image, "visible", 0);
            $img->id_order = $i;
            $img->path = v($image, "path","");
            $img->title = v($image, "title", "");
            $img->link = v($image, "link", "");
            $img->description = v($image, "description", "");
            
            $img->save();
        }
    }
    public static function ImageList_Load($name) {
        $result = array();
        $images = Input::instance()->post($name);
        if (is_array($images))
        foreach ($images as $i => $image) {
            $img = mlClass::create('images');    
            $img->id = $image["id"];
            $img->visible = $image["visible"];
            $img->id_order = $i;
            $img->path = $image["path"];
            $img->title = $image["title"];
            $img->link = $image["link"];
            
            $result[] = $img;
        }
        
        return $result;
    }    
    public static function ImageList($name, $images, $ops = null) {
        $nodes = array();
        foreach ($images as $image) 
            $nodes[] = array("type" => "image", "data" => $image);
            
        if (isset($ops["builder"]))
            $builder = $ops["builder"]; else
            $builder = array("self", "ImageList_Builder");
            
        $id = isset($ops["id"]) ? $ops["id"] : Admin::GenerateID();
        $ops["id"] = $id;
        $ops["autogen"] = false;
        
        $res = "<script>\$(document).module_loaded(function() { \$('#$id').admin_imagelist() });</script>";        
        $res.= Admin::EditTree($name, $nodes, $builder, array("image"), $ops);
        
        return $res;
    }

    public static function FileList_Builder($mode, $type, $file, $types, $ops) {
        $res = array();
        if ($mode == "root") {
            $res["drop"] = "file";
            $res["menu"] = "<a href='#' class='admin_button_add' onclick='return false'>" . t("Add") . "</a>"; 
        } else
        if ($mode == "node") {
            $res["id"] = $file->id;
            $res["content"] = view("\Sloway\Views\Admin\FileList_Item", array("file" => $file, "tags" => v($ops, "tags", null)));
        }
        
        return $res;
    }                                           
    public static function FileList_Save($name, $module, $module_id) {
        $db = self::$db;
        
        $files = v($_POST, $name);
        $delete = v($_POST, $name . "_delete_file");
        if (is_array($delete) && count($delete))
            $db->query("DELETE FROM `files` WHERE id IN (" . implode(",", $delete) . ")");       
        
        if (is_array($files))
        foreach ($files as $i => $file) {
            $f = dbClass::create('files');    
            $f->id = $file["id"];
            $f->module = $module;
            $f->module_id = $module_id;   
            $f->visible = v($file, "visible", 0);
            $f->id_order = $i;
            $f->tag = v($file, "tag", "");
            $f->path = v($file, "path", "");
            $f->description = v($file, "desc", "");
            
            $f->save();
        }
    }
    public static function FileList_Load($name) {
        $result = array();
        $files = Input::instance()->post($name);
        if (is_array($files))
        foreach ($files as $i => $file) {
            $f = dbClass::create('files');    
            $f->id = $file["id"];
            $f->visible = $file["visible"];
            $f->id_order = $i;
            $f->path = $file["path"];
            $f->description = $file["desc"];
            
            $result[] = $f;
        }
        
        return $result;
    }    
    public static function FileList($name, $images, $ops = null) {
        $nodes = array();
        foreach ($images as $image) 
            $nodes[] = array("type" => "file", "data" => $image);
            
        if (isset($ops["builder"]))
            $builder = $ops["builder"]; else
            $builder = array("self", "FileList_Builder");
            
        $id = Admin::GenerateID();
        $ops["id"] = $id;
        $ops["autogen"] = false;
        
        $res = "<script>\$(document).module_loaded(function() { \$('#$id').admin_filelist() });</script>";        
        $res.= Admin::EditTree($name, $nodes, $builder, array("image"), $ops);
        
        return $res;
    }
    
    public static function VideoList_Builder($mode, $type, $file, $types, $ops) {
        $res = array();
        if ($mode == "root") {
            $res["drop"] = "video";
            $res["menu"] = "<a class='admin_button_add' onclick=\"$.admin.edittree.add(this,'video')\">" . et("Add") . "</a>";
        } else
        if ($mode == "node") {
            $res["id"] = $file->id;
            $res["content"] = buffer::view("Admin/VideoList_Item", array("video" => $file));
        }
        
        return $res;
    }                                           
    public static function VideoList_Save($name, $module, $module_id) {
        $input = Input::instance();
        $db = Database::instance();        
        
        $videos = $input->post($name);
        $delete = $input->post($name . "_delete_video");
        if (is_array($delete) && count($delete))
            $db->query("DELETE FROM `files` WHERE id IN (" . implode(",", $delete) . ")");       
        
        if (is_array($videos))
        foreach ($videos as $i => $video) {
            $f = dbClass::create('files');    
            $f->id = $video["id"];
            $f->module = $module;
            $f->module_id = $module_id;   
            $f->visible = v($video, "visible", 0);
            $f->id_order = $i;
            $f->path = v($video, "path", "");
            $f->description = v($video, "desc", "");
            
            $f->save();
        }
    }
    public static function VideoList_Load($name) {
        $result = array();
        $videos = Input::instance()->post($name);
        if (is_array($videos))
        foreach ($videos as $i => $video) {
            $f = dbClass::create('files');    
            $f->id = $video["id"];
            $f->visible = $video["visible"];
            $f->id_order = $i;
            $f->path = $video["path"];
            $f->description = $video["desc"];
            
            $result[] = $f;
        }
        
        return $result;
    }    
    public static function VideoList($name, $videos, $ops = null) {
        $nodes = array();
        foreach ($videos as $video) 
            $nodes[] = array("type" => "video", "data" => $video);
            
        if (isset($ops["builder"]))
            $builder = $ops["builder"]; else
            $builder = array("self", "VideoList_Builder");
            
        $id = Admin::GenerateID();
        $ops["id"] = $id;
        $ops["autogen"] = true;
        
        $res = Admin::EditTree($name, $nodes, $builder, array("video"), $ops);
        
        return $res;
    }    
    
	public static function parse_sections($sections) {
		$css_query = [];
		$formats = [];
		foreach ($sections as $key => $val) {
			if (is_array($val)) {
				$items = array();
				foreach ($val as $key1 => $val1) {
					$items[]= array(
						"title" => t("msg_" . $val1), 
						"block" => "div", 
						"classes" => "section_" . $key1,
						"wrapper" => true
					);
					$css_query[]= "section_" . $key1 . "=" . urlencode(t("msg_" . $key)) . ": " . urlencode(t("msg_" . $val1));
				}
				$formats[]= array(
					"title" => t("msg_" . $key),
					"items" => $items,
				);
			} else {
				$css_query[]= "section_" . $val . "=" . urlencode(t("msg_" . $val));
				$formats[]= array(
					"title" => t("msg_" . $val),
					"block" => "div", 
					"classes" => "section_" . $val,
					"wrapper" => true
				);
			}

		}

		$res = new \stdClass();
		$res->formats = $formats;
		$res->css_query = $css_query;

		return $res;
	}
	
    public static function HtmlEditor($name, $content, $ml_mode = false, $ops = null) {
        $size = v($ops, "size", "medium");
		$sections = v($ops, "sections", array());
		
		if ($sections) {
			$r = admin::parse_sections($sections);
			
			$sf = v($ops, "style_formats", array());
			$sf+= $r->formats;

			$ops["style_formats"] = $sf;
			$ops["content_css"] = site_url("Admin/ContentCss") . "?" . implode("&", $r->css_query);

			unset($ops["sections"]);
		}

        switch ($size) {
            case "small": $ops["height"]= "300px"; break;                
            case "large": $ops["height"]= "600px"; break;                
            default: $ops["height"]= "300px"; break;                
        }
		unset($ops["size"]);
		
		if ($ml_mode) {
			return self::ml_field($name, $content, $ml_mode, "admin_html_edit", $ops, function($name, $postfix, $val, $lang, $def_val, $ops) {
				$id = Admin::GenerateID();
				$eid = $id . "-ed";

				$res = "\n<script>\$(document).module_loaded(function() { \n";
				$res.= "\$('#$id').admin_editor(" . json_encode($ops) . ");\n";
				$res.= "});\n";
				$res.= "</script>\n";        
				$res.= "<div id='$id' class='admin_html_editor'>\n";
				$res.= "<textarea id='$eid' name='" . $name . $postfix . "'>" . $val . "</textarea>\n";
				$res.= "</div>\n";

				return $res;				
			});
		}

        $id = Admin::GenerateID();
        $eid = $id . "-ed";
        
        $res = "\n<script>\$(document).module_loaded(function() { \n";
		$res.= "\$('#$id').admin_editor(" . json_encode($ops) . ");\n";
		$res.= "});\n";
		$res.= "</script>\n";        
        $res.= "<div id='$id' class='admin_html_editor'>\n";
        $res.= "<textarea id='$eid' name='$name'>" . $content . "</textarea>\n";
        $res.= "</div>\n";
        
        return $res;
    }
    public static function TemplateEditor($name, $content, $ml_mode = false, $platform = "site") {
		if ($ml_mode) 
			return self::ml_field($name, $content, $ml_mode, "admin_tmp_edit", $platform, function($name, $postfix, $val, $lang, $def_val, $platform) {
				$id = Admin::GenerateID();
				$n = $name . $postfix;
				$res = "<script>\$(document).module_loaded(function() { \$('#$id').admin_template_editor('$platform') });</script>";
				$res.= "<div id='$id' class='admin_template_editor'>";
				$res.= "<textarea name='$n' style='width: 100%; height: 100px; display: none'>$val</textarea>";
				$res.= "<input type='hidden' name='purge_cache[]'>";
				$res.= "</div>";    
				
				return $res;
			});
		
		if (is_array($content))
			$content = v($content, mlClass::$def_lang, "");
			
        $id = Admin::GenerateID();
        $res = "<script>\$(document).module_loaded(function() { \$('#$id').admin_template_editor('$platform') });</script>";
        $res.= "<div id='$id' class='admin_template_editor'>";
        $res.= "<textarea name='$name' style='width: 100%; height: 100px; display: none'>$content</textarea>";
        $res.= "<input type='hidden' name='purge_cache[]'>";
        $res.= "</div>";    
        
        return $res;
    }
    public static function GalleryEditor($name, $images, $title = '') {
        $id = Admin::GenerateID();
        
        $res = "<script>\$(document).module_loaded(function() { \$('#$id').admin_gallery_editor() });</script>";
        
        $res.= "<div class='admin_gallery_editor' id='$id' data-name='$name'>";
        $res.= "<div class='admin_ge_delete'></div>";
        $res.= "<div class='admin_ge_menu'>";
        if (!$title) $title = "&nbsp;";
        
        $res.= "<a href='#' class='admin_button_add' onclick='return false'>" . t("Add") . "</a>";
        $res.= "<a href='#' class='admin_button_del' onclick='return false' style='display: none'>" . t("Delete") . "</a>";
        $res.= "<h2>" . $title . "</h2>";
        
        $res.= "</div>";
        
        $res.= "<div class='admin_ge_list'>";
        $res.= "<ul>";
        $cnt = 0;
        foreach ($images as $image) {
            $style = "";
            $cls = "dyngrid_pending";
            if ($cnt < 10) {
                $th = thumbnail::from_image($image, "admin_gallery_96"); 
                if ($th->result) 
                    $style = "style=\"background-image: url('$th->result')\"";
                
                $cls = "";
            }
            
            $res.= "<li class='admin_ge_item $cls' data-id='$image->id' data-path='$image->path'>";
            $res.= "<div class='admin_gei_image' $style></div>";
            $res.= "<div class='admin_gei_title'>" . $image->title . "</div>";
            
            $res.= "<input type='hidden' name='{$name}_ids[]' value='$image->id'>";
            $res.= "<input type='hidden' name='{$name}_paths[]' value='$image->path'>";
            $res.= "<input type='hidden' name='{$name}_titles[]' value='$image->title'>";
            
            $res.= "</li>";
            
            $cnt++;
        }
        $res.= "</ul>";
        $res.= "</div>";
        $res.= "</div>";
        
        return $res;
    }   
    public static function GallerySave($name, $module, $module_id) {
        $input = Input::instance();
        $db = Database::instance();
        
        $delete = $input->post($name . "_delete");
        $titles = $input->post($name . "_titles");
        $paths = $input->post($name . "_paths");
        $ids = $input->post($name . "_ids");
        
        if (is_array($delete) && count($delete))
            $db->query("DELETE FROM `images` WHERE id IN (" . implode(",", $delete) . ")");
        
        if (is_array($paths)) 
        foreach ($paths as $i => $path) {
            $img = mlClass::create('images', $ids[$i]);
            $img->module = $module;
            $img->module_id = $module_id;   
            $img->visible = 1;
            $img->id_order = $i;
            $img->path = $path;
            $img->title = $titles[$i];
            
            $img->save();
        }
    } 
    public static function GalleryLoad($name) {
        $input = Input::instance();
        
        $titles = $input->post($name . "_titles");
        $paths = $input->post($name . "_paths");
        $ids = $input->post($name . "_ids");
        
        $result = array();
        
        if (is_array($paths)) 
        foreach ($paths as $i => $path) {
            $img = dbClass::create('images', $ids[$i]);
            $img->visible = 1;
            $img->id_order = $i;
            $img->path = $path;
            $img->title = $titles[$i];
            
            $result[] = $img;
        }
        
        return $img;
    }
    
    public static function CategorySelect($name, $tree, $value, $ops = array()) {
        $ops["paths"] = true;
        $ops["dependency"] = "0110";
        $ops["three_state"] = false;
		$ops["merge"] = true;
        
        return acontrol::checktree($name, $tree, $value, $ops);
    }
    public static function PropertySelect($name, $tree, $value, $ops = array()) {
        $ops["paths"] = true;
        $ops["merge"] = true;
        $ops["dependency"] = "0110";
        $ops["three_state"] = false;
        
        return acontrol::checktree($name, $tree, $value, $ops);
    }
    public static function CategoryFilter($name, $tree, $value, $ops = array()) {
        $ops["paths"] = true;
        $ops["merge"] = true;
        $ops["dependency"] = "0110";
        $ops["three_state"] = false;
        
        return acontrol::checktree($name, $tree, $value, $ops);
    }
    
    public static function HistoryDialog($name, $id, &$res) {
        $input = Input::instance();
        $db = Database::instance();
        
        $hid = $input->post("form_history");
        $res = new stdClass();
        $res->title = "Restore from previous version";
        
        $q = $db->query("SELECT * FROM `history` WHERE id = ?", $hid);
        $time = utils::datetime(strtotime($q[0]->time));
        if (!count($q)) {
            $res->content = "<div class='admin_message error'>Cannot restore</div>";
            $res->buttons = array("cancel");
            return false;
        }
        
        if ($input->post("restore_confirm")) {
            dbModel::restore($name, $id, $hid);
            $res->content = "<div class='admin_message success'>" . et("Successfuly restored from") . ": " . $time . "</div>";
        
            $res->result = true;
            $res->buttons = array("ok");    
            return true;
        }
        
        $res->postdata = "form_history=" . $hid;
        $res->content = Admin::Confirm("restore from: <br>%DATE%", array("%DATE%" => $time));
        $res->buttons = array("restore_confirm" => array("title" => t("OK"), "submit" => true), "cancel");
        
        return false;      
    }
    public static function HistoryList($name, $id) {
        $list = dbModel::history($name, $id);
        if (!$list) return false;
        
        $result = array();
        foreach ($list as $entry) {  
            $t = utils::date(strtotime($entry->time)) . " " . utils::time(strtotime($entry->time), ":", false) . " [" . $entry->user . "]";
            if ($entry->active)
                $result[$entry->id] = "<strong>" . $t . "</strong>"; else
                $result[$entry->id] = $t; 
        }
        
        return $result;
    }
    
    public static function ProcessMonitor($name, $ops = null) {
        $id = Admin::GenerateID();
        $callback = str_replace("admin_", "call_", $id);
        
        $mode = v($ops, "mode", "ajax");
        $ctrl = v($ops, "controls", "kill");
        if ($ctrl == "all")
            $ctrl = "kill,stop,resume";
        $class = v($ops, "class", "");
        $text_passive = v($ops, "text_passive", "");
        $text_active = v($ops, "text_active", "");
        $text_finished = v($ops, "text_finished", "");
        $states = v($ops, "states", "all");   
        if ($states == "all")
            $states = "none,running,idle,stopped,dead,finished";
        
        if ($mode)
            $res = "<script>\$(document).ready(function() { \$('#$id').admin_monitor('$mode'); console.log('$id') });</script>";  
            
        $res.= "<div class='admin_monitor $class' id='$id' data-name='$name' data-states='$states' data-text-passive='$text_passive' data-text-finished='$text_finished' data-text-active='$text_active'>";
        if ($mode == "realtime") {                                                                                                                                 
            $res.= "<iframe style='display: none'></iframe>";
            $res.= "<script>function $callback(data) { \$.admin.monitor.listener('$id', data) };</script>";
        }
        $res.= "<div class='admin_monitor_progress'></div>";
        $res.= "<div class='admin_monitor_highlight'></div>";
        $res.= "<div class='admin_monitor_text'></div>";
        
        $res.= "<div class='admin_monitor_controls'>";
        
        if (strpos($ctrl, "stop") !== false) 
            $res.= "<a class='admin_monitor_stop' data-mask='running,idle' data-action='stop'></a>";
        if (strpos($ctrl, "resume") !== false) 
            $res.= "<a class='admin_monitor_resume' data-mask='stopped,dead' data-action='resume'></a>";
        if (strpos($ctrl, "kill") !== false) 
            $res.= "<a class='admin_monitor_kill' data-mask='running,idle,dead,stopped' data-action='kill'></a>";
            
        $res.= "</div>";
        
        $res.= "</div>";
        
        return $res;
    }
    public static function ProgressBar($name, $title) {
        return Admin::ProcessMonitor($name, array(
            "text_passive" => $title,
            "text_active" => $title,
        ));    
    }
    public static function UploadButton($title, $callback, $align = 'left', $clear = true, $attr = '', $class = '') {
        $id = Admin::GenerateID();
        if ($align == 'left')
            $s = "style='float: left; margin-right: 5px'"; else
        if ($align == "right") 
            $s = "style='float: right; margin-left: 5px'";
        
        $res = "<script>\$(document).module_loaded(function() { \$('#$id').admin_upload_button() });</script>";  
        $res.= "<div id='$id' style='float: right' class='admin_button admin_upload_button $class' $attr data-callback='$callback'>";
        $res.= $title;
        $res.= "<input type='file' name='files'>";
        $res.= "</div>";
            
        if ($clear)
            $res.= "<div style='clear: $align'></div>";
            
        return $res; 
    }
    public static function UploadButtonS($title, $callback, $align = 'left', $clear = true, $attr = '', $class = '') {
        return Admin::UploadButton($title, $callback, $align, $clear, $attr, trim($class . " small"));    
    }
    
    public static function TagEditor($name, $items) {
        $id = Admin::GenerateID();
        
        $ids = implode(",", array_keys($items));
        
        $res = "<script>\$(document).module_loaded(function() { \$('#$id').admin_tageditor() });</script>";        
        $res.= "<div class='admin_tageditor' id='$id'>";
        $res.= "<input type='hidden' name='$name' value='$ids'>";
        $res.= "<ul>";
        
        foreach ($items as $id => $title)
            $res.= "<li data-value='$id'>$title<a onclick='return \$.admin.tageditor.remove.apply(this)'></a></li>";
        
        $res.= "</ul>";
        $res.= "</div>";
        
        return $res;
    }
	public static function VisibilityBar($value, $onclick) {
		$langs = lang::languages(true);
		
		$res = '<div class="admin_vis_bar" onclick="' . $onclick . '">';
		$value = "," . $value . ",";
		foreach ($langs as $lang) {
			$cls = ($value == ",1," || str_contains($value, "," . $lang . ",")) ? "selected" : "";
			$res.= "<span data-lang='$lang' class='admin_lang_flag $cls' title='" . t("lang_" . $lang) . "'>" . t("lang_abbr_" . $lang) . "</span>";
		}
		
		$res.= "</div>";
		return $res;
	}

}



