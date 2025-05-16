<?php

namespace Sloway;

class advfield {
	/**
	 * Create form field
	 * @param string Type of field (edit, select, group, check, checklist, file, area)
	 * @param boolean Required field
	 * @param array Options
	 * @param - info ... content for field popup
	 * @param - value ... Value / Source
	 * @param - default ... default value for control
	 * @param - check ... validation function
	 * @param - ops ... additional options for individual advcontrol
	 * @return value
	 */    
	public static function create($type, $req = false, $ops = array()) {
		$o = utils::value($ops, 'ops', array());
		$o['password'] = utils::value($ops, 'password', false);
		
		return array(
			'items' => utils::value($ops, 'items', array()),
			'type' => $type,
			'req' => $req,
			'info' => utils::value($ops, 'info', ''),
			'val' => utils::value($ops, 'value', null),
			'def' => utils::value($ops, 'default', ''),
			'chk' => utils::value($ops, 'check', ''),
			'ops' => $o,
		//  grid settings
			'desc' => utils::value($ops, 'desc'),
			'attr' => utils::value($ops, 'attr'),
			'show_error' => utils::value($ops, 'show_error', false),
		);
	}
	public static function button($content) {
		return self::create('button', false, array('value' => $content));
	}    
	
	public static function build($fields, $name, $ops = array(), $err = array()) {
		$res = "";
		
		if (!isset($fields[$name])) return $res;
		$o = $fields[$name];
		
		$prefix = utils::value($ops, 'prefix', '');
		$postfix = utils::value($ops, 'postfix', '');
		$l_prefix = utils::value($ops, 'lang_prefix', '');
		$lang = utils::value($ops, 'lang', '');
		
		$type = utils::value($o, 'type', 'edit');
		$val = utils::value($o, 'val', null);
		
		if ($type == 'button') 
			return "<input class='advbutton' type='submit' name='$name' value='$val'>";
		
		$req = utils::value($o, 'req', false); 
		$op = utils::value($o, 'ops', array());
		
		$n = $prefix . $name . $postfix;
		$b = "";
		$c = utils::value($o, 'chk', "");
		
		$op['error'] = isset($err[$n]); 
		switch ($type) {
			case 'edit':
				$op['default'] = $o['def'];
				$b = acontrol::edit($n, $val, $op);
				
				break;
			case 'select':
				$op['default'] = $o['def'];  
				$b = acontrol::select($n, $o['items'], $val, $op);
				
				break;
			case 'group': 
				if (!$val)
					$val = $o['def'];
				
				if ($val == '')
					$val = reset($o['items']);
				
				$op['exclusive'] = true;
				$b = acontrol::checklist($n, $o['items'], $val, $op);
				
				break;
			case 'check': 
				$b = acontrol::check($n, $val, $op);
			
				break;
			case 'checklist':
				$b = acontrol::checklist($n, $o['items'], $val, $op);
				
				break;
			case 'area':
				$op['default'] = $o['def'];
				
				$b = acontrol::area($n, $val, $op);

				break;
			case 'custom':
				$b = $val;
				break;
		}
		
		return $b;
	}
}
