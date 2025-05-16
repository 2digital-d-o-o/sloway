<?php 
	namespace Sloway;
?>
<script>  
$(document).ready(function() {
    var dg_model = <?=json_encode($dg_model)?>;
    
    $("#upsales").datagrid({
        mode: "ajax",
        model: dg_model,
        modules: ["col_resize", "sorting", "edit", "pages"],
        handler: doc_base + 'AdminPromo/Ajax_UpsalesHandler',
        cookie_name: "<?=core::$project_name?>_promo_upsales",
        pages: {
            per_page: 20,    
        },        
        layout: {
            freeze: [0,1],
            fill: "spacer",
        },
        footer: {
            left: "reload,row_check_info",
            right: "pages_perpage,pages_info,pages"
        },
		onCellEdit: function(param) {
            $.post(doc_base + 'AdminPromo/Ajax_UpsalesUpdate', {
                x: param.col,
                y: param.row,
                id: param.row_id,
                name: param.col_id,
                value: param.new_value
            }, function(r) {
                $("#upsales").datagrid("update_cell", r.x, r.y, r.content, r.value);    
            }, "json");
            
            return { overlay: false };
        }   		
    });
});

function upsales_add() {
    $.overlay_ajax(doc_base + "AdminPromo/Ajax_UpsalesAdd", "ajax", {}, {
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(r) {
            if (r) $("#upsales").datagrid("reload");
        }
    });     
    
    return false;   
}

function upsales_delete(id) {
    $.overlay_confirm("<div class='admin_message warning'><?=t("admin_confirm_prefix") . " " . et_js("delete upsale") . "?"?></div>", function() {
        $("#upsales").datagrid("reload", { "delete" : id });
    });    
}

function upsales_active(id) {
	$.overlay_ajax(doc_base + 'AdminPromo/Ajax_UpsalesActive/' + id, "ajax", {}, {
		auto_focus: false,
        onLoaded: function() {
            $(this).ac_create();
        },
        onClose: function(r) {
            $("#upsales").datagrid("reload");
        },
    });
}

</script>

<?php 
    $menu = Admin::ButtonS(et("Add upsale"), "#", "right", false, "onclick='upsales_add(); return false'");
    echo Admin::SectionBegin(et("Upsales") . $menu, false);
    
    echo "<div id='upsales'></div>";
    
    echo Admin::SectionEnd();
?>

