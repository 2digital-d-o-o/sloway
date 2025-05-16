<?php
	namespace Sloway;
?>

<script>
$(document).module_loaded(function() {
    $("#module_menu").ac_create();
    $("#module_menu input[type=text]").keypress(function(e) {
        if (e.which == $.ac.keys.ENTER) 
            $("#filter_apply").click();
    }); 
    $("#filter_form").submit(function() {
        var data = $(this).serializeObject();
        data["filter"] = 1;
        $("#news").datagrid("reload", data);
        
        return false;
    });
        
    $("#filter_apply").click(function() {
        $("#filter_form").submit();
    });
    $("#filter_reset").click(function() {
        $("#filter_form input").each(function() { $(this).ac_value("") });
        $("#filter_form").submit();
    }); 
});
</script>
<?php								  
	echo Admin::SectionBegin(et("Filter"), "options");
    echo "<form id='filter_form'>";
    echo Admin::FieldV(et("Search"), acontrol::edit("filter_search", $filter->search));
    
	echo Admin::ButtonS(t("Apply"), false, "left", false, "id='filter_apply' name='filter_apply'");
    echo Admin::ButtonS(t("Reset"), false, "right", true, "id='filter_reset'");
    echo "</form>";
	
	echo Admin::SectionEnd();
?>	

