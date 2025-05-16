<?php
	namespace Sloway;
?>

<script>
$(document).module_loaded(function() {
    $("#admin_product_list_add").click(function() {
        var editor = $(this).closest(".admin_field").find(".admin_tageditor");
        editor.catalog_browser({types: "group", level: 0}, function(r) {
            $.admin.tageditor.add($(this), r);
        });       
    });
    $("#admin_product_list_rem").click(function() {
        var editor = $(this).closest(".admin_field").find(".admin_tageditor");
        editor.children("input").val("");
        editor.children("ul").html("");    
    });
});
</script>
<style>
#admin_product_list {
    min-height: 100px;    
}
</style>
<?php
    $time_from = $upsale->time_from;
    $time_to = $upsale->time_to;
	
    echo Admin::AjaxForm_Begin("AdminPromo/Ajax_UpsaleHandler/" . $upsale->id);

	echo "<input name='gen_count' type='hidden' id='admin_gen_count'>";
	echo "<input name='gen_mask' type='hidden' id='admin_gen_mask'>";
     
    echo Admin::SectionBegin(et("Main"));
    echo Admin::Field(et("Title"), acontrol::edit("title", $upsale->title));
    echo Admin::Field(et("Priority"), acontrol::edit("priority", $upsale->priority));
    
    $ops = array();
    $ops["paths"] = false;
    $ops["merge"] = false;
    $ops["dependency"] = "0101";
    $ops["three_state"] = false;
    $ops["style"] = "height: 284px";
        
    $menu = "<br><a class='admin_link add' id='admin_product_list_add'>" . et("Add") . "</a>";
    $menu.= "<br><a class='admin_link del' id='admin_product_list_rem'>" . et("Clear") . "</a>";
    
    echo Admin::Field(et("Categories"), acontrol::checktree("categories", acontrol::tree_items($categories, "subcat"), $upsale->categories, $ops));
    echo Admin::Field(et("Tags"), acontrol::checklist("tags", arrays::regen($tags, "id", "title", true), $upsale->tags));
	echo Admin::Field(et("Products") . $menu, Admin::TagEditor("products", arrays::regen($products)));
    
    echo Admin::SectionEnd();

	echo Admin::SectionBegin(et("Content"));
	echo Admin::TemplateEditor('content', $upsale->get_ml("content"), true);
	echo Admin::SectionEnd();
    
    echo Admin::AjaxForm_End();