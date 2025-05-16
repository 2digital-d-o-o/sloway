<?php 

$actions = \Sloway\config::get("orders.actions");

$config["variables"] = array("order_id", "invoice", "price", "reference", "confirm_link");
$config["orders"] = array(
    "icon" => \Sloway\path::gen("site.modules.Orders", "media/img/icon_orders.png"),
    "variations" => array(
        "auth_required",
        "auth_mail",
        "auth_failed",
        "auth_out_of_stock"
    ),
    "sections" => array(),
);
foreach ($actions as $action => $ops) {
	if (v($ops, "send_mail", false) && $state = v($ops, "state"))
		$config["orders"]["variations"][] = $state;
}
$config["orders"]["variations"][] = "print";

foreach (\Sloway\config::get("orders.payment.methods") as $p) {
    $config["orders"]["sections"][] = "payment_$p";
}

	
	
	
	
	


