<?php 
	namespace Sloway;
?>
<script>

function datepicker_pos(input, inst) {
    var ofs = $(input).offset();
    var left = ofs.left - inst.dpDiv.outerWidth() - 10;
    setTimeout(function() {
        inst.dpDiv.css({ 
           'left' : left + "px"
        });
    }, 10);    
}

$(document).module_loaded(function() {
    $("#module_menu").ac_create();
    $("#module_menu [name=filter_from]").datetimepicker({ dateFormat: "dd.mm.yy", beforeShow: datepicker_pos });  
    $("#module_menu [name=filter_to]").datetimepicker({ dateFormat: "dd.mm.yy", beforeShow: datepicker_pos }); 
    $("#module_menu [name=filter_search]").keypress(function(e) {
        if (e.which == $.ac.keys.ENTER) 
            $("#filter_apply").click();
    }); 
    $("#filter_form").submit(function() {
        var data = $(this).serializeObject();
        data["filter"] = 1;
        $("#codes").datagrid("reload", data);
        
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
    echo Admin::Field(et("Search"), acontrol::edit("filter_search", $filter->search));
    echo Admin::Field(et("Date from"), acontrol::edit("filter_from", utils::date_time($filter->from)));
    echo Admin::Field(et("Date to"), acontrol::edit("filter_to", utils::date_time($filter->to)));
    
    echo Admin::ButtonS(t("Apply"), false, "left", false, "id='filter_apply'");
    echo Admin::ButtonS(t("Reset"), false, "right", true, "id='filter_reset'");
    echo "</form>";
    
    echo Admin::SectionEnd();
?>