<?php
	use Sloway\Admin;
	use Sloway\acontrol;
?>

<script>
$(document).module_loaded(function() {	
    $("#module_menu [name=filter_search]").keypress(function(e) {
        if (e.which == $.ac.keys.ENTER) 
            $("#catalog_filter_apply").click();
    }); 
	$("#catalog_filter_apply").click(function() {
		$("#catalog").datagrid("reload", {
			"apply_filter" : 1,
            "filter_search" : $("#module_menu [name=filter_search]").val(),
			"filter_search_mode" : $("#module_menu [name=filter_search_mode]").val(),
			"filter_cats" : $("#module_menu [name=filter_cats]").val(),
            "filter_tags" : $("#module_menu [name=filter_tags]").val(),
		});
	});
    $("#catalog_filter_reset").click(function() {
        $("#module_menu [name=filter_search]").ac_value("");
		$("#module_menu [name=filter_search_mode]").ac_value("group");
        $("#module_menu [name=filter_cats]").ac_value("");
        $("#module_menu [name=filter_tags]").ac_value("");
        $("#catalog_filter_apply").click();
    });
});
</script>
<?php								  
    echo Admin::SectionBegin("Filter", false);

	echo Admin::FieldV(et("Search"), 
		acontrol::edit("filter_search", $filter->search, array("lines" => 5)) .
		acontrol::select("filter_search_mode", array("group" => "Groups", "item" => "Items"), $filter->search_mode)
	);
    
	echo Admin::FieldV(et("Categories"), Admin::CategoryFilter("filter_cats", $filter_cats_tree, $filter->cats, array("style" => "max-height: 150px; overflow: auto")));
    echo Admin::FieldV(et("Tags"), acontrol::checklist("filter_tags", $filter_tags_list, $filter->tags, array("style" => "max-height: 150px; overflow: auto")));
    
	echo Admin::ButtonS(t("Apply"), false, "left", false, "id='catalog_filter_apply'");
    echo Admin::ButtonS(t("Reset"), false, "right", true, "id='catalog_filter_reset'");
	
	echo Admin::SectionEnd();

?>	

