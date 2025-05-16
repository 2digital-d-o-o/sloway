<?php

$config['tax_rates'] = array();
$config['types'] = array('group');
$config['tags'] = true;
$config['lists'] = true;
$config['initial_stock'] = 1;
$config['product_base_columns'] = "id,id_parent,type,stock,title,code,sort_num,tags,categories,properties,price,price_action,url";
$config['category_base_columns'] = "id,id_parent,sort_num,title,url"; 
$config['filter_source'] = "catalog_product";
$config['filter_debug'] = false;
$config['filter_template'] = array("list", "boxes", "dropdown");
$config['selector_template'] = array("boxes", "dropdown");

$config["stock"] = array(
    "handler" => true,
    "manager" => true,
    "realtime" => false,
    "negative" => false,
);

$config["tree_treshold"] = 20;
$config["custom_property_values"] = false;
$config["slot_properties"] = array("price");
$config["bundle"]["types"] = "group";
$config["tree"] = array(
    "catalog" => true,
    "properties" => true,
    "categories" => true
);

$config["property_flags"] = array("gen_title");
$config["product_flags"] = array("infstock");
$config["sale_methods"] = array("ns");
$config["payment_methods"] = array();
$config["discounts"] = true;
$config["product_vis"] = array();

$config["move_cat_tables"] = array("catalog_product");
