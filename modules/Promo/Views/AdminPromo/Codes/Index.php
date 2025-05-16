<?php 
	namespace Sloway;
?>
<script>  
$(document).ready(function() {
    var codes_model = <?=json_encode($dg_model)?>;
    
    $("#codes").datagrid({
        mode: "ajax",
        model: codes_model,
        modules: ["col_resize", "sorting", "edit", "pages"],
        handler: doc_base + 'AdminPromo/Ajax_CodesHandler',
        cookie_name: "<?=core::$project_name?>_catalog_codes",
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
    });
});

function codes_add() {
    $.overlay_ajax(doc_base + "AdminPromo/Ajax_CodesAdd", "ajax", {}, {
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(r) {
            if (r) $("#codes").datagrid("reload");
        }
    });     
    
    return false;   
}

function codes_delete(id) {
    $.overlay_confirm("<div class='admin_message warning'><?=t("admin_confirm_prefix") . " " . et_js("delete code") . "?"?></div>", function() {
        $("#codes").datagrid("reload", { "delete" : id });
    });    
}

function codes_active(id) {
	$.overlay_ajax(doc_base + 'AdminPromo/Ajax_CodesActive/' + id, "ajax", {}, {
		auto_focus: false,
        onLoaded: function() {
            $(this).ac_create();
        },
        onClose: function(r) {
            $("#codes").datagrid("reload");
        },
    });
}

</script>

<?php 
    $menu = Admin::ButtonS(et("Add code"), "#", "right", false, "onclick='codes_add(); return false'");
    echo Admin::SectionBegin(et("Promo codes") . $menu, false);
    
    echo "<div id='codes'></div>";
    
    echo Admin::SectionEnd();
?>

