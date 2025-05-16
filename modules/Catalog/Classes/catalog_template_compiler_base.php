<?php    

namespace Sloway;

class catalog_template_compiler_base {
    public static function product_list($list, $param, $ops) {
        $span = pq($list)->children("ul");
        $style = pq($span)->attr("style");
        $items = pq($span)->children("li");
        $ids = array();
        foreach ($items as $item) 
            $ids[] = pq($item)->attr("data-id");    
            
        if (!count($ids)) return;
        $ids = implode(",", $ids);
        $products = dbModel::load("catalog_product", "@id IN ($ids) ORDER BY FIELD(id, $ids)"); 
		
		$load_list_callback = config::get("catalog.templates_load_list", null);
		if (is_callable($load_list_callback))
			call_user_func($load_list_callback, $products, $ids);

        $html = "<div class='rl_template_span' style='$style'>";
        $html.= view($ops["view_site"], array(
            "items" => $products,
            "item_width" => pq($list)->attr("data_attr_itm_width"),
            "item_spacing" => pq($list)->attr("data_attr_itm_spacing"),
            "image_mode" => pq($list)->attr("data_attr_img_mode"),
            "dynamic" => false,
        ));
        $html.= "</div>";
        
        pq($list)->html($html);
    } 
    public static function product_slider($list, $param, $ops) {
        $framed = pq($list)->hasClass("rl_framed");
        $span = pq($list)->children("ul");
        $style = pq($span)->attr("style");
        $items = pq($span)->children("li");
        
        $ids = array();
        foreach ($items as $item) 
            $ids[] = pq($item)->attr("data-id");
            
        if (!count($ids)) return;
        
        $ids = implode(",", $ids);
        $products = dbModel::load("catalog_product", "@id IN ($ids) AND visible = 1 ORDER BY FIELD(id, $ids)"); 
		$load_list_callback = config::get("catalog.templates_load_list", null);
		if (is_callable($load_list_callback))
			call_user_func($load_list_callback, $products, $ids);

        
        $html = "<div class='rl_template_span' style='$style'>";
        $html.= view($ops["view_site"], array(
            "items" => $products, 
            "framed" =>  $framed,
            "class" => "rl_slider",
            "caption" => pq($list)->find(".rl_template_element[data-name=title]")->html(),
            "image_mode" => pq($list)->attr("data_attr_img_mode"),
            "interval" => pq($list)->attr("data_attr_sld_auto"),
            "speed" => pq($list)->attr("data_attr_sld_speed"),
        ));     
        $html.= "</div>";
        
        pq($list)->html($html);
    }   
    public static function category_list($list, $param, $ops) {
        $span = pq($list)->children("ul");
        $style = pq($span)->attr("style");
        $items = pq($span)->children("li");
        $ids = array();
        foreach ($items as $item) 
            $ids[] = pq($item)->attr("data-id");    
            
        if (!count($ids)) return;
        $ids = implode(",", $ids);
        $cats = dbModel::load("catalog_category", "@id IN ($ids) ORDER BY FIELD(id, $ids)"); 
        
        $html = "<div class='rl_template_span' style='$style'>";
        $html.= view($ops["view_site"], array(
            "items" => $cats,
            "item_width" => pq($list)->attr("data_attr_itm_width"),
            "item_spacing" => pq($list)->attr("data_attr_itm_spacing"),
            "image_mode" => pq($list)->attr("data_attr_img_mode"),
        ));
        $html.= "</div>";
        
        pq($list)->html($html);
    }     
    public static function category_slider($list, $param, $ops) {
        $interval = pq($list)->attr("data_attr_sld_auto");
        $speed = pq($list)->attr("data_attr_sld_speed");
        $img_mode = pq($list)->attr("data_attr_img_mode");
        
        $span = pq($list)->children("ul");
        $style = pq($span)->attr("style");
        $items = pq($span)->children("li");
        
        $ids = array();
        foreach ($items as $item) 
            $ids[] = pq($item)->attr("data-id");
            
        if (!count($ids)) return;
        $ids = implode(",", $ids);
        $cats = dbModel::load("catalog_category", "@id IN ($ids) ORDER BY FIELD(id, $ids)"); 
        
        $html = "<div class='rl_template_span' style='$style'>";
        $html.= view($ops["view_site"], array(
            "items" => $cats, 
            "class" => "rl_slider",            
            "image_mode" => pq($list)->attr("data_attr_img_mode"),
            "interval" => pq($list)->attr("data_attr_sld_auto"),
            "speed" => pq($list)->attr("data_attr_sld_speed"),
            "loader" => config::get("templates.templates.category_slider.loader"),

        ));     
        $html.= "</div>";
        
        pq($list)->html($html);
    }        
    public static function tagged_image($image, $param, $ops) {
		$tags = pq($image)->find(".rl_template_tag");
		foreach ($tags as $tag) {
			$px = pq($tag)->attr("data-x");
			$py = pq($tag)->attr("data-y");
			$ids = pq($tag)->attr("data-ids");
			
			if ($ids) {
				$products = dbModel::load("catalog_product", "@id IN ($ids) ORDER BY FIELD(id, $ids)"); 
				catalog_template_compiler::product_list_load($products);
				
				$html = buffer::view($param["view_tag"], array("products" => $products));

				pq($tag)->attr("style", "left: {$px}%; top: {$py}%");
				pq($tag)->html($html);
			}
		}

        // pq($list)->html($html);
    }        
}