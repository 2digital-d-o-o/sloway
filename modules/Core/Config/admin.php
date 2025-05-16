<?php 

use Sloway\path;

$config['icons'] = array(
	'settings'  => path::gen("site.modules.Core", "media/img/modules/module_settings.png"),
    'users'     => path::gen("site.modules.Core", "media/img/modules/module_users.png"),
	'pages'     => path::gen("site.modules.Core", "media/img/modules/module_pages.png"),
    'news'      => path::gen("site.modules.Core", "media/img/modules/module_news.png"),
	'content'   => path::gen("site.modules.Core", "media/img/modules/module_content.png"),
    'uploads'   => path::gen("site.modules.Core", "media/img/modules/module_uploads.png"),
    'gallery'   => path::gen("site.modules.Core", "media/img/modules/module_gallery.png"),
	'slideshow' => path::gen("site.modules.Core", "media/img/modules/module_slideshow.png"),
);

$config['modules'] = array(
	'settings'    => 'AdminSettings',
	'pages'       => 'AdminPages',
	'uploads'     => 'AdminUploads',
);
$config['pages']['flags'] = array("visible"); 
$config['pages']['flags_default'] = "visible";

$config['news']['time'] = false;
$config['news']['flags'] = array('visible');
$config['news']['short_content'] = true;

$config['user_flags'] = array('dis', 'master');
$config['log'] = array('account', 'mail');

$config["permissions"] = array(
    "Global{id=global}" => array(
        "History{id=history}"
    ),
    "Settings{id=settings}" => array(
        "Settings{id=settings}",
        "Users{id=users}" => array("Edit roles{id=roles}"),
        "Profile{id=profile}",
        "Messages{id=messages}",
        "Redirects{id=redirects}",
        "Navigation{id=navigation}",
    ),
    "Pages{id=pages}" => array("Lock page{id=lock}", "Delete page{id=delete}"),
    "Uploads{id=uploads}",
    "News{id=news}",
    "Slideshow{id=slideshow}"
);

$config["editor"] = array(
    "classes" => array(
        "test"
    )
);

$config["settings"] = array(

);

$config["templates"] = array(
    "text", 
    "image", 
    "image_text", 
    "text_image", 
    "column2", 
    "column3",
	
);

$config["generate_url"] = array(
	"page" => true,
	"news" => true,
	"product" => true,
	"category" => true
);


$config["sites"] = array();
