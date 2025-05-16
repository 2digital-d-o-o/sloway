<?php

$config["payment"] = array(
    "methods" => array(),
    "default" => ""
);

$config["delivery"] = false;   
$config["company"] = false;
$config["phone"] = false;  // true, "code"

$config["cart_expiration"] = 1200;
$config["cart_columns"]["all"] = array("image", "desc", "quantity", "price_piece", "discount", "price_total");
$config["cart_columns"]["index"] = array("image", "desc", "quantity", "price_piece", "discount", "price_total");
$config["cart_columns"]["review"] = array("image", "desc", "quantity", "price_piece", "discount", "price_total");

$config["authorize"] = array(
    "expiration" => 720000,
    "action" => "accept",
    "payment" => array(
        "bankwire" => false,
        "at_delivery" => false,
    )
);
$config["flags"] = array();
$config["analytics"] = array();

$config["reservations"] = false;
$config["fields"]["payment"] = array("!email", "!firstname", "!lastname", "!street", "!zipcode", "!city", "!country", "!phone");
$config["fields"]["delivery"] = array("!firstname", "!lastname", "!street", "!zipcode", "!city", "!country");


$config["countries"]["list"] = \Sloway\countries::gen('', true);
$config["countries"]["default"] = "SI";

$config["editor"]["order_prices"] = array("shipping");
$config["editor"]["item_prices"] = array();

$config["mail"] = array(
    "lang" => "en"
);

$config['class'] = array(
    "order" => "\Sloway\order",
    "item" => "\Sloway\order_item",
    "group" => "\Sloway\order_group",
);

$config['states'] = array(
    "temporary" => array(
    ),
    "pending" => array(
        "actions" => array("accept", "cancel"),
    ),
    "accepted" => array(
        "actions" => array("confirm", "cancel"),
        "visible" => true,
    ),
    "confirmed" => array(
        "actions" => array("finalize", "cancel"),
        "visible" => true,
    ),
    "finalized" => array(
        "actions" => array("cancel"),
        "visible" => true,  
        "print" => true,
    ),
    "cancelled" => array(
        "actions" => array("confirm", "delete"),
        "visible" => true,
    ),
);

$config["actions"] = array(
    "accept" => array(
        "icon" => "icon-confirm.png",
        "state" => "accepted",
        "send_mail" => true,
    ),
    "confirm" => array(
        "icon" => "icon-confirm.png",
        "state" => "confirmed",
        "send_mail" => true,
    ),
    "finalize" => array(
        "icon" => "icon-confirm.png",
        "state" => "finalized",
        "send_mail" => true,
    ),
    "cancel" => array(
        "icon" => "icon-delete.png",
        "state" => "cancelled",
        "send_mail" => true,
        "stock_op" => 1,
    ),
    "delete" => array(
        "icon" => "icon-delete.png",
        "state" => "deleted",
    ),
);


