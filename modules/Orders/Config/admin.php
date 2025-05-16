<?php
$config['icons'] = array( 
	'orders' => \Sloway\path::gen("site.modules.Orders", "media/img/module_orders.png"),  
);         
$config['operations'] = array('orders_close', 'orders_cancel', 'orders_delete');
$config['access'] = array('orders' => "AdminOrders(.*)");
$config['log'] = array('orders');

$config["permissions"] = array(
    "Orders{id=orders}" => \Sloway\orders_module::gen_permissions(),
    "Catalog{id=catalog}" => array(
        "Categories{id=categories}" => array(
            "View all{id=view_all}"
        )
    )
);
