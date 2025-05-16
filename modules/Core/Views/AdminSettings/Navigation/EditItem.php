<?php
	namespace Sloway;

    echo Admin::Field(et("Type"), acontrol::select("type", $types, $type));
    echo Admin::Field(et("Title"), acontrol::edit("title", v($item, "title")));
    echo Admin::Field(et("Tag"), acontrol::edit("tag", v($item, "tag")));
        
    if ($type == 'static') {
        echo Admin::Field(et("Url"), acontrol::edit("url", v($item, "url")));           
    } else {
        if ($tree)
            echo Admin::Field(et("Select") . " " . et("nav_type_$type.select"), acontrol::selecttree("id_ref", $tree, v($item, "id_ref", ""), array("border" => false)));
            
        echo Admin::Field(et("Autogen"), acontrol::edit("autogen", v($item, "autogen")));
    }

    echo Admin::Field(et("Flags"), acontrol::checktree("flags", $flags, v($item, "flags")));
    echo Admin::Field(et("Attributes"), acontrol::edit("attrs", v($item, "attrs")));
    echo Admin::ImageList("image", $image, array('title' => et("Image"), 'count' => 1, 'id' => 'admin_nav_item_image'));
    