<?php
	use \Sloway\Admin;
	use \Sloway\acontrol;
?>
<script>
$(document).module_loaded(function() {    
    setTimeout(function() {
        $("#module_menu [name=filter_search]").keypress(function(e) {
            if (e.which == $.ac.keys.ENTER) 
                $("#catalog_filter_apply").click();
        }); 
    }, 1000);
    $("#catalog_filter_apply").click(function() {
        $("#properties").datagrid("reload", {
            "apply_filter" : 1,
            "filter_search" : $("#module_menu [name=filter_search]").val(),
        });
    });
    $("#catalog_filter_reset").click(function() {
        $("#module_menu [name=filter_search]").ac_value("");
        $("#catalog_filter_apply").click();
    });
});
</script>
<?php                                  
    echo Admin::SectionBegin(et("Filter"));
    echo Admin::FieldV(et("Search"), acontrol::edit("filter_search", $filter_search));
    echo Admin::ButtonS(t("Apply"), false, "left", false, "id='catalog_filter_apply'");
    echo Admin::ButtonS(t("Reset"), false, "right", true, "id='catalog_filter_reset'");
    
    echo Admin::SectionEnd();

