<?php 
	use \Sloway\Admin;
	use \Sloway\acontrol;
	use \Sloway\catalog;
	use \Sloway\config;
	use \Sloway\mlClass;
	use \Sloway\utils;
?>
<script>
function add_slot_item(src) {
    $(src).catalog_browser({ types: "group", level: 1 }, function(r) {
        for (var i in r) {
            var node = $.admin.edittree.add($(this), 'item');
            
            $("[data-name=item_id]", node).val(r[i].id);
            $("[data-fname=price]", node).ac_value(r[i].price); 
            $(".item_title", node).html(r[i].title).attr("title", r[i].title);
        } 
    });
}
</script>
<?php   
	echo Admin::AjaxForm_Begin('AdminCatalog/Ajax_GroupHandler/' . $product->id);
	
    echo Admin::SectionBegin(et("Main"));
	echo Admin::Field(et('Title'), Admin::Edit('title', $product->get_ml("title"), true));
	echo Admin::Field(et('Code'), acontrol::edit('code', $product->code));
	
	// echod($product->get_ml("price"), $product->get_ml("price_action"));
	
	echo Admin::Field(et("Price"), Admin::Edit('price', utils::decode_price($product->get_ml("price")), true)); 
	echo Admin::Field(et("Price action"), Admin::Edit('price_action', utils::decode_price($product->get_ml("price_action")), true));
    echo Admin::Field(et('Tax rate'), Admin::Select('tax_rate', $tax_rates, $product->get_ml("tax_rate"), true));

	echo Admin::Field(et("Categories"), Admin::CategorySelect("categories", acontrol::tree_items($categories, "subcat"), $product->categories, array("style" => "height: 175px")));

    echo Admin::SectionEnd();
    
    echo Admin::SectionBegin(et("Properties"));
    echo $property_editor;
    echo Admin::SectionEnd();    
    
	echo Admin::SectionBegin(et("Content"));
	echo Admin::TemplateEditor("content", $product->get_ml("content"), true);
	echo Admin::SectionEnd();

	echo Admin::SectionBegin();  
	echo Admin::ImageList('images', $product->images, array('title' => et('Images')));
	
	echo Admin::SectionEnd();
    
    echo Admin::SectionBegin(et("SEO"));
	echo Admin::SEO($product, true);
    echo Admin::SectionEnd();
    
	echo Admin::AjaxForm_End();
?>
