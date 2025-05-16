<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<script>

function analytics_generate() { 
    $("#report").datagrid("reload", {
        "generate" : 1,
        "date_from" : $("#module_menu [name=filter_from]").val(),
        "date_to" : $("#module_menu [name=filter_to]").val(),
        "cats" : $("#module_menu [name=filter_cats]").val(),
        "tags" : $("#module_menu [name=filter_tags]").val(),
        "search" : $("#module_menu [name=filter_search]").val(),
    });    
}
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
        data["generate"] = 1;
        $("#report").datagrid("reload", data);
        
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
    $def_time1 = utils::date_time(utils::mktime(0,0,0, null, null, null, strtotime("-3 months")));
    $def_time2 = utils::date_time(time());

    echo Admin::SectionBegin(et("Filter"), "options");
    echo "<form id='filter_form'>";
    echo Admin::Field(et("Date from"), acontrol::edit("filter_from", utils::date_time($this->filter->from), array("placeholder" => $def_time1)));
    echo Admin::Field(et("Date to"), acontrol::edit("filter_to", utils::date_time($this->filter->to), array("placeholder" => $def_time2)));
    echo Admin::Field(et("Search"), acontrol::edit("filter_search", $this->filter->search));
    echo Admin::FieldV(et("Categories"), acontrol::checktree("filter_cats", $this->categories, $this->filter->cats, array("style" => "max-height: 150px; overflow: auto")));
    echo Admin::FieldV(et("Tags"), acontrol::checktree("filter_tags", $this->tags, $this->filter->tags, array("style" => "max-height: 150px; overflow: auto")));
    
    echo Admin::ButtonS(t("Apply"), false, "left", false, "id='filter_apply'");
    echo Admin::ButtonS(t("Reset"), false, "right", true, "id='filter_reset'");
    echo "</form>";
    
    echo Admin::SectionEnd();
?>