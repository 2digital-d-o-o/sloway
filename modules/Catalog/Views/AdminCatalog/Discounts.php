<?php 
	namespace Sloway;
?>
<script>  
$(document).ready(function() {
    var discounts_model = <?=json_encode($dg_model)?>;
    
    $("#discounts").datagrid({
        mode: "ajax",
        model: discounts_model,
        modules: ["col_resize", "sorting", "edit", "pages"],
        handler: doc_base + 'AdminCatalog/Ajax_DiscountsHandler',
        cookie_name: "<?=\Sloway\core::$project_name?>_catalog_discounts",
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

function discounts_add() {
    $.overlay_ajax(doc_base + "AdminCatalog/Ajax_DiscountsAdd", "ajax", {}, {
		auto_focus: true,
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(r) {
            if (r) admin_redirect(r);
        }
    });     
    
    return false;   
}

function discounts_delete(id) {
    $.overlay_confirm("<div class='admin_message warning'><?=t("admin_confirm_prefix") . " " . et_js("delete discount") . "?"?></div>", function() {
        $("#discounts").datagrid("reload", { "delete" : id });
    });    
}

function discounts_active(id) {
	$.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Visibility/catalog_discount/' + id, "ajax", {}, {
		auto_focus: false,
        onLoaded: function() {
            $(this).ac_create();
        },
        onClose: function(r) {
            $("#discounts").datagrid("reload");
        },
    });
}

</script>

<?php 
    $menu = Admin::ButtonS(et("Add discount"), "#", "right", false, "onclick='discounts_add(); return false'");
    echo Admin::SectionBegin(et("Discounts") . $menu, false, 1);
    
    echo "<div id='discounts'></div>";
    
    echo Admin::SectionEnd();
?>

