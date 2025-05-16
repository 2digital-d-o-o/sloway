<?php

namespace Sloway;

class advform {   
	private static $fields;
	private static $fnames;
	private static $ops;
	private static $err;
						 
	public static function begin($submit, $attr = '', $check = true) {
		$st = ($check) ? "onsubmit='return advform_submit(\$(this))'" : "";
		
		return "<form $attr class='advform' method='post' enctype='multipart/form-data' action='$submit' $st>";
	}
	public static function message($msg = "", $class = "") {
		if ($msg == "")
			$msg = t('advcontrol_fill_req');
		
		return "<div class='advform_message $class' style='display: none'>" . $msg . "</div>";
	}
	public static function end() {
		return "</form>";    
	}
	public static function fields($config) {
		$f = array();
		foreach ($config as $name => $cfg) {
			$type = $cfg[0];
			$req = isset($cfg[1]) ? $cfg[1] : false;
			$ops = isset($cfg[2]) ? $cfg[2] : array();
			$f[$name] = advfield::create($type, $req, $ops);
		}		
		
		return $f;
	}
	public static function validate($fields, $input, &$errors, $ops = array()) {
		$error = array();
		
		$prefix = utils::value($ops, 'prefix', '');
		$postfix = utils::value($ops, 'postfix', '');
		$val_func = utils::value($ops, 'callback', null);
		$val_data = utils::value($ops, 'callback_data', null);
		
		foreach ($fields as $name => $field) {
			$chk = utils::value($field, 'chk', "");
			$req = utils::value($field, 'req', false);
			
			$value = $input->post($prefix . $name . $postfix);
			
			if (is_callable($val_func)) {
				$res = call_user_func($val_func, $name, $value, $field, $val_data);
				if ($res === true) continue;
				
				if ($res !== null) {
					$errors[$name] = $res;
					continue;
				}
			}    

			$res = true;
			if (preg_match("/ext\((.*)\)/", $chk, $m)) {
				$vext = explode(",", $m[1]);
				$ext = pathinfo($value, PATHINFO_EXTENSION);
				if (!in_array($ext, $vext))
					$res = t('advform_error.invalid_ext');  
			} else
			if ($req && $value == "")
				$res = t('advform_error.null'); else
			if ($chk == 'integer' && !is_int($value)) 
				$res = t('advform_error.not_int'); else 
			if ($chk == 'float' && !is_float(str_replace(",", ".", $value)))
				$res = t('advform_error.not_float'); 
				
			if ($res !== true) 
				$errors[$name] = $res;    
		}       
		
		return count($errors) == 0;
	}
	public static function review($fields, $input, $ops = array()) {
		$prefix = utils::value($ops, 'prefix', '');
		$postfix = utils::value($ops, 'postfix', '');
		$l_prefix = utils::value($ops, 'lang_prefix', ''); 
		$lang = utils::value($ops, 'lang', '');
		$callback = utils::value($ops, 'callback', null);
		if (is_array($callback)) {
			$calldata = utils::value($callback, "1", null);
			$callback = utils::value($callback, "0", null);
		}
		
		$attr = array();
		$head = array('attr' => utils::value($ops, 'attr_head', ''));  
		$body = array('attr' => utils::value($ops, 'attr_body', ''));   
		
		$crow = 0;
		foreach ($fields as $name => $o) {
			$req = false;
			if ($name[0] == '!') {
				$req = true;
				$name = substr($name, 1);                    
			}
			
			$type = utils::value($o, 'type', 'edit'); 
			
			if ($type != 'button' && $type != 'custom') {
				if ($type == 'span') {
					$attr[$crow] = "span(2)";                    
					$head[$crow] = utils::value($o, 'val', ''); 
				} else {
					$val = $input->post($prefix . $name . $postfix);
					if (is_callable($callback)) {
						$val = call_user_func($callback, $prefix . $name . $postfix, $type, $val, $calldata);
						if ($val === false)
							continue;
					} else                                        
					if ($type == 'check') {
						$val = ($val == 'on') ? t('advcontrol_check_on','',$lang) : t('advcontrol_check_off','',$lang);
					}

					$head[$crow] = lang::t($name, $l_prefix, $lang);
					$body[$crow] = $val;
				}
			}
			
			
			
			$crow++;
		}
		
		return build::grid($attr, $head, $body);
	}
	
	public static function build_rep($m) {
		if (is_numeric($m[1])) {
			$i = intval($m[1]);
			if (isset(self::$fnames[$i])) 
				$name = self::$fnames[$i]; else
				return $m[0];
		} else
			$name = $m[1];
		
		if (!isset(self::$fields[$name]))
			return $m[0]; else
			$field = self::$fields[$name];   
			
		//echo "field: $name:$m[2]<br />";
		switch ($m[2]) {
			case "id":
				return "id='form_$name'";
			case "req":
				return ($field['req']) ? '*' : '';
			case "head":
				return ($field['type'] != 'button') ? et($name, self::$ops['lang']) : "";
			case "body":
				return advfield::build(self::$fields, $name, self::$ops, self::$err, self::$ops['check']); 
			case "info":
				return utils::value($field, "desc", "");           
			case "err":    
				$se = intval(utils::value($field, 'show_error', false));
				if (isset(self::$err[$name]) && is_string(self::$err[$name]) && $se && self::$err[$name] != '')
					return self::$err[$name]; else
					return "";
		}
	}
	
	public static function build($template, $fields, $ops = array(), $err = array()) {
		self::$ops['check'] = utils::value($ops, 'check', false);
		self::$ops['class'] = utils::value($ops, 'class', '');
		self::$ops['l_prefix'] = utils::value($ops, 'lang_prefix', '');
		self::$ops['lang'] = utils::value($ops, 'lang', '');   
		
		$pattern = '%\$([a-zA-Z0-9_]+):(head|body|id|req|err|info)%';
		
		self::$fields = $fields;
		self::$fnames = array_keys($fields);
		self::$err = $err;
		
		return preg_replace_callback($pattern, "advform::build_rep", $template);
	}
}
