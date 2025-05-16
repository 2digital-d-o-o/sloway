<?php

/* Use cache, content will be compiled only once, recompiled when saved */
$config["cache"] = false;
$config["media"] = array(
    "mobile" => array("width" => 600, "color" => "rgba(255,192,0)"),
    "tablet" => array("width" => 1000, "color" => "rgba(0,176,80)"),
    "laptop" => array("width" => 1366, "color" => "rgba(0,112,192)"),
    "desktop" => array("width" => 0, "color" => "rgba(0,176,240)"),
);

$config["adaptive_image_format"] = "webp";
$config["adaptive_image_delta"] = 200;

/*
$config["templates"][<NAME>] = array(
    "view" => <VIEW>,
    "styles" => array(<STYLE1>, <STYLE2>, ...)
    "attrs" => array(
        <ATTR_NAME> => <DEFAULT _VALUE>  - text editor
        <ATTR_NAME> => array(<VALUE1>, <VALUE2>, ...) - dropdown editor
    ),
    "add" => "<!><NAME1>,*<NAME2>" - ! = exclude, * = wild card
    "compiler" => array(<CLASS>, <METHOD>),
    "compiler_param" => array(...)              - passed to compiler
    "priority" => true|false                    - compile this first
    "loader" => true|false                      - load with ajax
    "loader_class"                              - place_holder class
);
*/
$config["templates"]["text"] = array(
    "view" => "\Sloway\Views\Templates\Text",
    "styles" => array("vert_center", "horiz_center"),
    "platform" => "*",    
);
$config["templates"]["image"] = array(
    "view" => "\Sloway\Views\Templates\Image",
    "styles" => array("ajax", "popup", "show_img_desc"),
    "attrs" => array(
        "img_alt" => "",
        "img_pos" => array("center", "left", "right"),
    ),
    "platform" => "*",
);
$config["templates"]["image_text"] = array(
    "view" => "\Sloway\Views\Templates\ImageText",
    "styles" => array("ajax", "popup", "show_img_desc"),
    "attrs" => array(
        "img_alt" => "",
        "img_pos" => array("center", "left", "right"),
    ),
    "platform" => "*",
);
$config["templates"]["text_image"] = array(
    "view" => "\Sloway\Views\Templates\TextImage",
    "styles" => array("ajax", "popup", "show_img_desc"),
    "attrs" => array(
        "img_alt" => "",
        "img_pos" => array("center", "left", "right"),
    ),
    "platform" => "*",
);
$config["templates"]["frame"] = array(
    "view" => "\Sloway\Views\Templates\Frame",
    "compiler" => '\Sloway\template_compiler::frame',
    "platform" => "site",
);
$config["templates"]["column2"] = array(
    "view" => "\Sloway\Views\Templates\Column2",
    "add" => "!grid*",
    "attrs" => array(
        "eq" => array("none", "left", "right"),
    ),
    "platform" => "*",
);
$config["templates"]["column3"] = array(
    "view" => "\Sloway\Views\Templates\Column3",
    "add" => "!grid*",
    "platform" => "site",
);
$config["templates"]["grid11"] = array(
    "view" => "\Sloway\Views\Templates\Grid11",
    "add" => "text,image,banner,slideshow,*slider",
    "priority" => true,
    "platform" => "site",
);
$config["templates"]["grid22"] = array(
    "view" => "\Sloway\Views\Templates\Grid22",
    "add" => "text,image,banner,slideshow,*slider",
    "priority" => true,
    "platform" => "site",
);
$config["templates"]["grid12"] = array(
    "view" => "\Sloway\Views\Templates\Grid12",
    "add" => "text,image,banner,slideshow,*slider",
    "priority" => true,
    "platform" => "site",
);
$config["templates"]["grid21"] = array(
    "view" => "\Sloway\Views\Templates\Grid21",
    "add" => "text,image,banner,slideshow,*slider",
    "priority" => true,
    "platform" => "site",
);
$config["templates"]["slideshow"] = array(
    "view" => "\Sloway\Views\Templates\Slideshow",
    "view_site" => "\Sloway\Views\Templates\Site\Slideshow",
    "attrs" => array(
        "sld_size" => "%,0,0",
        "sld_auto" => "",
    ),
    "compiler" => '\Sloway\template_compiler::slideshow',
    "platform" => "site",
);
$config["templates"]["image_list"] = array(
    "view" => "\Sloway\Views\Templates\ImageList",
    "view_site" => "\Sloway\Views\Templates\Site\ImageList",
    "styles" => array("ajax", "popup", "show_img_desc"),
    "attrs" => array(
        "itm_width" => "200",
        "itm_spacing" => "20",
        "itm_ratio" => "100",
        "img_mode" => array("contain", "cover"),
    ),
    "compiler" => '\Sloway\template_compiler::image_list',
    "platform" => "site",
);
$config["templates"]["page_list"] = array(
    "view" => "\Sloway\Views\Templates\PageList",
    "view_site" => "\Sloway\Views\Templates\Site\PageList",
    "styles" => array("ajax"),
    "attrs" => array(
        "itm_width" => "200",
        "itm_spacing" => "20",
        "itm_ratio" => "100",
        "img_mode" => array("contain", "cover"),
    ),
    "compiler" => '\Sloway\template_compiler::page_list',
    "platform" => "site",
);
$config["templates"]["news_list"] = array(
    "view" => "\Sloway\Views\Templates\NewsList",
    "view_site" => "\Sloway\Views\Templates\Site\NewsList",
    "styles" => array("ajax"),
    "attrs" => array(
        "itm_width" => "200",
        "itm_spacing" => "20",
        "itm_ratio" => "100",
        "img_mode" => array("contain", "cover"),
    ),
    "compiler" => '\Sloway\template_compiler::news_list',
    "platform" => "site",
);
$config["templates"]["image_slider"] = array(
    "view" => "\Sloway\Views\Templates\ImageSlider",
    "view_site" => "\Sloway\Views\Templates\Site\ImageSlider",
    "styles" => array("ajax", "popup"),
    "attrs" => array(
        "itm_height" => 200,
        "itm_width" => 200,
        "sld_auto" => "",
        "sld_speed" => "",
        "img_mode" => array("contain", "cover"),
    ),
    "compiler" => '\Sloway\template_compiler::image_slider',
    "platform" => "site",
);
$config["templates"]["banner"] = array(
    "view" => "\Sloway\Views\Templates\Banner",
    "view_site" => "\Sloway\Views\Templates\Site\Banner",
    "styles" => array("ajax"),
    "attrs" => array(
        "itm_ratio" => "%",
    ),
    "compiler" => '\Sloway\template_compiler::banner',
    "platform" => "site",
);
$config["templates"]["banner_list"] = array(
    "view" => "\Sloway\Views\Templates\BannerList",
    "view_site" => "\Sloway\Views\Templates\Site\BannerList",
    "styles" => array("ajax"),
    "attrs" => array(
        "itm_width" => "200",
        "itm_spacing" => "20",
        "itm_ratio" => "100",
    ),
    "compiler" => '\Sloway\template_compiler::banner_list',
    "platform" => "site",
);
$config["templates"]["section_list"] = array(
    "view" => "\Sloway\Views\Templates\SectionList",
    "view_site" => "\Sloway\Views\Templates\Site\SectionList",
    "add" => "section",
    "platform" => "site",
);
$config["templates"]["section"] = array(
    "view" => "\Sloway\Views\Templates\Section",
    "root" => false,
    "compiler" => '\Sloway\template_compiler::section',
    "platform" => "site",
);

