<?php
	namespace Sloway;
?>

<script>  
$(document).ready(function() {
    var navigation_model = <?=json_encode($model) ?>;
    
    $("#navigation").datagrid({
        mode: "ajax",
        model: navigation_model,
        modules: ["col_resize", "sorting", "edit", "treeview"],
        cookie_prefix: '<?=\Sloway\core::$project_name?>_',
        handler: doc_base + 'AdminSettings/Ajax_NavigationHandler/<?=$nav_name?>',
        layout: {
            freeze: [0,1],
            fill: "spacer",
        },
        treeview: {
            dnd: true,  
            drag_grip: "img",
            save_expanded: true,
            onDrop: function(param) {
                $(this).datagrid("reload", {"reorder" : param.src_id, "parent" : param.dst_id, "index" : param.index});
            } 
        },
        footer: {
            left: "reload"
        },
        onCellCustomEdit: function(param) {
            var row = $(this).closest(".dg_row");
            var typ = row.attr("data-ntype");
            
            if (param.col_id == 'value') {
                navigation_edit(param.row_id);
            }
        },
    });
});

function navigation_edit(id, pid) {
    if (typeof id == "undefined") id = 0;
    if (typeof pid == "undefined") pid = 0;
    $.overlay_ajax(doc_base + "AdminSettings/Ajax_NavigationEditItem/<?=$nav_name?>/" + id + "/" + pid, "ajax", {}, {
        auto_focus: false,
        scrollable: true,
        onDisplay: function(r) {
            $(this).ac_create();
            $(this).find("[name=type]").change(function() {
                $(this).closest(".overlay").find("form").submit();
            });
            
            $("#admin_nav_item_image").admin_imagelist();
        },    
        onClose: function(r) {
            if (r) $("#navigation").datagrid("reload");
        }
    });     
    
    return false;   
}

function navigation_set_visible(src) {
    var vis = src.is(":checked") ? 1 : 0;
    $.post(doc_base + 'AdminSettings/Ajax_NavigationItemVisible/' + src.attr("data-id") + "/" + vis);
}

function navigation_delete(id) {
    $.overlay_confirm('<?=Admin::Confirm("delete item")?>', function() {
        $("#navigation").datagrid("reload", { "delete" : id });
    });    
}

</script>

<?php 
    $menu = Admin::ButtonS(et("Add"), "#", "right", false, "onclick='navigation_edit(); return false'");
    
    echo Admin::CTabsBegin($nav_tabs, $nav_tabs_curr, array("menu" => $menu));
    echo Admin::CTabsPage('', "<div id='navigation'></div>");
?>



