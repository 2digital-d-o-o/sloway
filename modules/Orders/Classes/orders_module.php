<?php

namespace Sloway;

class orders_module {
	public static function load($ctrl) {
		helper("orders");

		order::$db = $ctrl->db;
	}
	public static function gen_permissions() {
		$res = array();
		foreach (\Sloway\config::get("orders.states") as $name => $state) {
			if (!v($state, "visible", false)) continue;

			$ops = array();
			foreach (v($state, "actions", array()) as $action)
				$ops[] = "order_perm_{$action}{id=$action}";

			$res["order_perm_{$name}{id=$name}"] = $ops;
		}   
		$res[] = "order_perm_nonauth{id=nonauth}";
		$res[] = "order_perm_log{id=log}";
		$res[] = "order_perm_resend{id=resend}";
		$res[] = "order_perm_edit{id=edit}";
		$res[] = "order_perm_cart_edit{id=cart_edit}";

		return $res;
	}     
}  
