<?php 
		
$config['icons']['catalog'] = \Sloway\path::gen("site.modules.Catalog", "media/img/modules/module_catalog.png");

$config["settings"] = array(
//    "product_def_image?type=img",
//    "category_def_image?type=img"
);


$config['permissions'] = array(
    "Catalog{id=catalog}" => array(
        "Products{id=products}" => array(
            "Add product{id=add}",
            "Delete product{id=delete}",
            "Edit product{id=edit}",
            "Archive products{id=archive}",
            "Stock management{id=stock}" => array("Reset stock{id=reset}"),
            "Codes management{id=codes}" => array("Manual codes{id=manual}"),
        ),
        "Categories{id=categories}" => array(
            "User management{id=users}"
        ),
        "Types{id=types}",
        "Archive{id=archive}"
    )
);

  
