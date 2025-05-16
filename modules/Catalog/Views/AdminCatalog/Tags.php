<?php 
	use Sloway\Admin;
?>

<script>  
$(document).ready(function() {
    var tags_model = <?=json_encode($dg_model)?>;
    
    $("#tags").datagrid({
        mode: "ajax",
        model: tags_model,
        modules: ["col_resize", "sorting", "edit", "pages"],
        handler: doc_base + 'AdminCatalog/Ajax_TagsHandler',
        cookie_name: "<?=\Sloway\core::$project_name?>_catalog_tags",
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

function tags_add() {
    $.overlay_ajax(doc_base + "AdminCatalog/Ajax_TagsAdd", "ajax", {}, {
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

function tags_delete(id) {
    $.overlay_confirm("<div class='admin_message warning'><?=t("admin_confirm_prefix") . " " . et_js("delete tag") . "?"?></div>", function() {
        $("#tags").datagrid("reload", { "delete" : id });
    });    
}

function tags_active(id) {
	$.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Visibility/catalog_tag/' + id, "ajax", {}, {
		auto_focus: false,
        onLoaded: function() {
            $(this).ac_create();
        },
        onClose: function(r) {
            $("#tags").datagrid("reload");
        },
    });
}

</script>

<?php 
    $menu = Admin::ButtonS(et("Add tag"), "#", "right", false, "onclick='tags_add(); return false'");
    echo Admin::SectionBegin(et("Tags") . $menu, false, 1);
    
    echo "<div id='tags'></div>";
    
    echo Admin::SectionEnd();
?>

