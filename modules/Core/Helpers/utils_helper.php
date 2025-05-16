<?php

use Sloway\lang;

function t($name, $lang = null, $editable = false) {
	if (lang::$translator)
		return lang::$translator->get($name, $lang, $editable); else
		return $name;
}
function t_js($name, $lang = null, $editable = false) {
    $res = lang::$translator->get($name, $lang, $editable); 
    return trim(json_encode($res), '"');
}
function et($name, $lang = null) {
	if (lang::$translator)
		return lang::$translator->get($name, $lang, true); else
		return $name;
}
function et_js($name, $lang = null) {
    $res = lang::$translator->get($name, $lang, true); 
    return trim(json_encode($res), '"');
}

function echob() {
	$args = func_get_args();
	foreach ($args as $arg) {
		echo $arg;
	}    
	
	echo "<br />";
}

function iechob($indent) {
	$args = func_get_args();
	foreach ($args as $i => $arg) {
		if ($i == 0)
			echo str_repeat("&nbsp;", intval($arg)); else
			echo $arg;
	}    
	
	echo "<br />";
}

function echod() {
	$args = func_get_args();
	foreach ($args as $arg)
		echo \Sloway\utils::debug($arg);	
}
function dbg() {
	$args = func_get_args();	
	$res = "";
	foreach ($args as $arg)
		$res.= \Sloway\utils::debug($arg);	

	return $res;
}

function v($obj, $path, $default = '') { 
	return \Sloway\utils::value($obj, $path, $default);    
}
function av($arr, $name, $default = '') {
    if (is_array($arr) && isset($arr[$name]))
        return $arr[$name]; else
    if (is_object($arr) && isset($arr->$name))
        return $arr->$name; else
        return $default;
}
function ev($string, $del, $index, $default = '') {
	$e = explode($del, $string);
	if (isset($e[$index]))
		return $e[$index]; else
		return $default;
}

function xd(...$vars) {
	Kint::dump(...$vars);
}