<?php

use Sloway\order;
use Sloway\dbClass;
use Sloway\core;
use Sloway\utils;
use Sloway\flags;
use Sloway\config;
use Sloway\lang;

function order_load($id, $groups = false) {
	$order = dbClass::load("order", "@id = $id", 1, array(), order::$class_order);

	if (!$order) return null;

	$r = order::$reservations;
	order::$reservations = false;

	$res = order::load_order($order, $groups);

	order::$reservations = $r;

	return $res;
}
function order_delete($id) {
	$order = order_load($id);
	$order->action("delete", false);

	$db = Database::instance();

	foreach (dbClass::load("order_item", "@id_order = $id") as $item) {
		$order->on_delete_item($item);
		$item->delete();
	}
	foreach (dbClass::load("order_group", "@id_order = $id") as $group) {
		$order->on_delete_group($group);
		$group->delete();
	}
	$db->query("DELETE FROM `files` WHERE module = 'orders' AND module_id = '$id'");

	$order->on_delete();
	$db->query("DELETE FROM `order` WHERE id = $id");
}
function order_query($sql, $limit = 0, $groups = false) {
	if (!is_array($sql))
		$sql = array($sql);

	$osql = isset($sql[1]) ? $sql[1] : null;

	$orders = dbClass::load("order", $sql[0], $limit, array(), order::$class_order);

	if (is_array($orders)) {
		foreach ($orders as $order) {
			order::load_order($order, $groups, $osql);
		}
	} else
	if ($orders)
		order::load_order($orders, $groups, $osql);

	return $orders;
}
function order_create($session = null, $name = 'order') {   
	if ($session) {
		$order = $session->get(core::$project_name . "_" . $name, null);
		if ($order) {
		//	ORDER EXISTS IN SESSION
			if ($order->id) {
			//	ORDER WAS COMMITED
				$q = core::$db->query("SELECT status FROM `order` WHERE id = ?", $order->id)->getResult();
				if (!count($q) || $q[0]->status != "temporary") {
				//	ORDER NO LONGER IN CART(temporary status), delete session 
					$session->remove(core::$project_name . "_" . $name);
					$order = null;
				} else
					$order->validate();
			}
		}
		if (!$order) {
			$order = dbClass::create("order", 0, array(), order::$class_order);
			$order->payment = order::$payment_default;   
			$order->status = "temporary";
			$order->reg_mode = "skip";
			$order->stock_op = 0;
			$order->on_create();
			$session->set(core::$project_name . "_" . $name, $order);
		} 
		$order->stock_op = 0;
		$order->lang = lang::$lang;
	} else {
		$order = dbClass::create("order", 0, array(), order::$class_order);  
		$order->reg_mode = "skip";
		$order->stock_op = 0;
		$order->payment = order::$payment_default;   
		$order->lang = lang::$lang;
		$order->on_create();
	}

	//$order->prices = array();
	$order->status = "temporary";
	$order->initialize();

	return $order;    
}

function order_item_create($id_ref, $title, $quantity, $data, $ops = array()) {
	$discount = utils::value($ops, "discount", 0);
	$gdiscount = utils::value($ops, "gdiscount",  1);
	$image = utils::value($ops, "image");
	$media = utils::value($ops, "media", null);
	$code = utils::value($ops, "code");
	$attr = utils::value($ops, "attr");
	$max_q = utils::value($ops, "max_quantity", 10);
	$flags = utils::value($ops, "flags", array());
	$stock_mask = utils::value($ops, "stock_mask", 1);
	if (!is_null($media))
		$flags[]= "media";

	$i = dbClass::create("order_item", 0, array(), order::$class_item);
	$i->status = "temporary";
	$i->grouped = false;
	$i->item_data = $data;
	$i->title = $title;
	$i->quantity = $quantity;
	$i->max_quantity = $max_q;
	$i->image = $image;
	$i->code = $code;
	$i->discount = $discount;
	$i->gdiscount = $gdiscount;
	$i->id_ref = $id_ref;
	$i->attr = $attr;
	$i->stock_mask = $stock_mask;
	$i->flags = flags::set(null, $flags);
	$i->commit_id = 0;

	return $i;
}

function cart_find_view($view) {
	if (file_exists(APPPATH . "Views/Cart/" . $view . ".php"))
		return "\App\Views\Cart\\" . $view; else
		return "\Sloway\Views\Cart\\" . $view;
}
function invoice_find_view() {
	if (file_exists(APPPATH . "Views/Order/Invoice.php"))
		return "\App\Views\Order\Invoice"; else
		return "\Sloway\Views\Order\Invoice";
}

