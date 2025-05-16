<?php
	namespace Sloway;
?>

<script>
$(document).module_loaded(function() {
    $("#module [name=date_from]").datetimepicker({dateFormat: 'dd.mm.yy', showTimepicker: false});  
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
    $time_from = $code->time_from;
    $time_to = $code->time_to;
	
    echo Admin::AjaxForm_Begin("AdminPromo/Ajax_CodeHandler/" . $code->id);

	echo "<input name='gen_count' type='hidden' id='admin_gen_count'>";
	echo "<input name='gen_mask' type='hidden' id='admin_gen_mask'>";
     
    echo Admin::SectionBegin(et("Main"));
    echo Admin::Field(et("Title"), acontrol::edit("title", $code->title));
	if (!$count)
		echo Admin::Field(et("Code"), acontrol::edit("code", $code->code));

    echo Admin::Field(et("Type"), acontrol::select("type", $code_types, $code->type));
    echo Admin::Field(et("Value"), acontrol::edit("value", $code->value));
    // echo Admin::Field(et("Price treshold"), acontrol::edit("price_tr", $this->format_price($code->price_tr)));
    
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
    
    echo Admin::Field(et("Categories"), acontrol::checktree("categories", acontrol::tree_items($categories, "subcat"), $code->categories, $ops));
	echo Admin::Field(et("Tags"), acontrol::checklist("tags", arrays::regen($tags, "id", "title", true), $code->tags));
    echo Admin::Field(et("Products") . $menu, Admin::TagEditor("products", arrays::regen($products)));
   
    echo Admin::SectionEnd();
    
    echo Admin::AjaxForm_End();