<?php
	use Sloway\Admin;
	use Sloway\acontrol;
	use Sloway\arrays;
?>
<script>
$(document).module_loaded(function() {
    $("#module [name=date_from]").datetimepicker({dateFormat: 'dd.mm.yy'});  
    $("#module [name=date_to]").datetimepicker({dateFormat: 'dd.mm.yy'});
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
    $time_from = $discount->time_from;
    $time_to = $discount->time_to;
    
    echo Admin::AjaxForm_Begin("AdminCatalog/Ajax_DiscountHandler/" . $discount->id);
     
    echo Admin::SectionBegin(et("Main"));
    echo Admin::Field(et("Title"), acontrol::edit("title", $discount->title));
    echo Admin::Field(et("Value") . " (%)", acontrol::edit("value", $discount->value));
    echo Admin::Field(et("Tag"), Admin::edit("tag", $discount->get_ml("tag"), true));
    
    echo Admin::Column1();
    echo Admin::Field(et("Date from"), acontrol::edit("date_from", $time_from ? date("d.m.Y H:i", $time_from) : ""));
    echo Admin::Column2();
    echo Admin::Field(et("Date to"), acontrol::edit("date_to", $time_to ? date("d.m.Y H:i", $time_to) : ""));
    echo Admin::ColumnEnd();
    
    $ops = array();
    $ops["paths"] = false;
    $ops["merge"] = false;
    $ops["dependency"] = "0101";
    $ops["three_state"] = false;
    $ops["style"] = "height: 284px";
        
    $menu = "<br><a class='admin_link add' id='admin_product_list_add'>" . et("Add") . "</a>";
    $menu.= "<br><a class='admin_link del' id='admin_product_list_rem'>" . et("Clear") . "</a>";
    
    echo Admin::Field(et("Categories"), acontrol::checktree("categories", acontrol::tree_items($categories, "subcat"), $discount->categories, $ops));
	echo Admin::Field(et("Tags"), acontrol::checklist("tags", arrays::regen($tags, "id", "title", true), $discount->tags));
    echo Admin::Field(et("Products") . $menu, Admin::TagEditor("products", arrays::regen($products)));
    
    echo Admin::SectionEnd();
    
    echo Admin::AjaxForm_End();