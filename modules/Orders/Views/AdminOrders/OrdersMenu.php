<?php
	use \Sloway\acontrol;
	use \Sloway\admin;
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

$(document).ready(function() {
    $("#module_menu").ac_create();
    $("#module_menu [name=filter_from]").datetimepicker({ dateFormat: "dd.mm.yy", beforeShow: datepicker_pos });  
    $("#module_menu [name=filter_to]").datetimepicker({ dateFormat: "dd.mm.yy", beforeShow: datepicker_pos }); 
    $("#module_menu input[type=text]").keypress(function(e) {
        if (e.which == $.ac.keys.ENTER) 
            $("#filter_apply").click();
    }); 
    $("#filter_form").submit(function() {
        var data = $(this).serializeObject();
        data["filter"] = 1;
        $("#orders").datagrid("reload", data);
        
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
    echo Admin::Field(et("Date from"), acontrol::edit("filter_from", \Sloway\utils::date_time($filter->from)));
    echo Admin::Field(et("Date to"), acontrol::edit("filter_to", \Sloway\utils::date_time($filter->to)));
    
    //echo Admin::FieldV(et("Categories"), Admin::CategoryFilter("filter_cats", $this->categories, $this->filter->cats, array("style" => "max-height: 150px; overflow: auto")));
    echo Admin::FieldV(et("Flags"), acontrol::checktree("filter_flags", $flags, $filter->flags, array("style" => "max-height: 150px; overflow: auto")));
    
    echo Admin::ButtonS(t("Apply"), false, "left", false, "id='filter_apply'");
    echo Admin::ButtonS(t("Reset"), false, "right", true, "id='filter_reset'");
    echo "</form>";
    
    echo Admin::SectionEnd();
?>