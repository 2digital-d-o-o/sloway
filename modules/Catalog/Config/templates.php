<?php

$config["templates"]["product_list"] = array(
    "view" => "\Sloway\Views\Templates\ProductList",
	"view_site" => "\App\Views\Product\List",
    "attrs" => array(
        "itm_width" => "",
        "itm_spacing" => "",
        "img_mode" => array("contain", "cover"),
    ),
    "compiler" => '\Sloway\catalog_template_compiler::product_list',
    "platform" => "*",
); 
$config["templates"]["product_slider"] = array(
    "view" => "\Sloway\Views\Templates\ProductSlider",
	"view_site" => "\App\Views\Product\Slider",
    "attrs" => array(
        "sld_auto" => "",
        "sld_speed" => "",
        "img_mode" => array("contain", "cover"),
    ),
    "compiler" => '\Sloway\catalog_template_compiler::product_slider',
    "platform" => "site",
); 
$config["templates"]["category_list"] = array(
    "view" => "\Sloway\Views\Templates/CategoryList",
	"view_site" => "\App\Views\Category\List",
    "attrs" => array(
        "itm_width" => "",
        "itm_spacing" => "",
        "img_mode" => array("contain", "cover"),
    ),
    "compiler" => '\Sloway\catalog_template_compiler::category_list',
    "platform" => "*",
); 
$config["templates"]["category_slider"] = array(
    "view" => "\Sloway\Views\Templates\CategorySlider",
	"view_site" => "\App\Views\Category\Slider",
    "attrs" => array(
        "sld_auto" => "",
        "sld_speed" => "",
        "img_mode" => array("contain", "cover"),
    ),
    "compiler" => '\Sloway\catalog_template_compiler::category_slider',
    "platform" => "site",
); 
$config["templates"]["tagged_image"] = array(
    "view" => "\Sloway\Views\Templates\TaggedImage",
	"view_site" => "\App\Views\Product\Tag",
	"styles" => array("ajax", "popup", "show_img_desc"),
    "attrs" => array(
        "img_alt" => "",
        "img_pos" => array("center", "left", "right"),
    ),
    "compiler" => '\Sloway\catalog_template_compiler::tagged_image',
    "platform" => "*",
); 

