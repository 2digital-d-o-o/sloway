<?php 
	use \Sloway\Admin;
	use \Sloway\acontrol;
	use \Sloway\catalog;
	use \Sloway\config;
?>
<script>
function add_slot_item(src) {
    $(src).catalog_browser({types: "group", level: 1}, function(r) {
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
    echo Admin::AjaxForm_Begin('AdminCatalog/Ajax_BundleHandler/' . $bundle->id);
	
	echo Admin::SectionBegin(et('Main'));  
	echo Admin::Field(et('Title'), acontrol::edit('title', $bundle->title));
    echo Admin::Field(et('Price'), acontrol::edit('price', str_replace(".", ",", $bundle->price)));
    
    $ops = array(
        "paths" => true,
        "style" => "height: 136px",
        "dependency" => "0110",
        "three_state" => false,
    );
    echo Admin::Field(et("Categories"), acontrol::checktree('categories', acontrol::tree_items($categories, "subcat"), $bundle->categories, $ops)); 
    echo Admin::Field(et("Short description"), Admin::HtmlEditor('short_desc', $bundle->short_desc, array("size" => "small", "menu" => "size,style")));
    echo Admin::SectionEnd();
    
    echo Admin::SectionBegin(et("Content"));
    echo Admin::TemplateEditor('description', $bundle->description);
    echo Admin::SectionEnd();	
    
	echo Admin::SectionBegin();
	echo $slots_editor;
	echo Admin::SectionEnd();
	
	echo Admin::SectionBegin();
	echo Admin::ImageList('images', $bundle->images, array('title' => et('Images')));
	echo Admin::SectionEnd();

    echo Admin::SectionBegin(et("Meta data"));
    echo Admin::Field(et('Title'), acontrol::edit('meta_title', $bundle->meta_title));
    echo Admin::Field(et('Keywords'), acontrol::edit('meta_keys', $bundle->meta_keys));
    echo Admin::Field(et('Description'), acontrol::edit('meta_desc', $bundle->meta_desc));
    echo Admin::SectionEnd();
	
	echo Admin::AjaxForm_End();
?>
