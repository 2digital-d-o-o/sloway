<?php 
	use Sloway\Admin;
?>

<script>  
$(document).ready(function() {
    var tags_model = <?=json_encode($dg_model)?>;
    
    $("#lists").datagrid({
        mode: "ajax",
        model: tags_model,
        modules: ["col_resize", "sorting", "edit", "pages"],
        handler: doc_base + 'AdminCatalog/Ajax_ListsHandler',
        cookie_name: "<?=\Sloway\core::$project_name?>_catalog_lists",
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

function list_add() {
    $.overlay_ajax(doc_base + "AdminCatalog/Ajax_ListsAdd", "ajax", {}, {
		auto_focus: true,
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(r) {
            $("#lists").datagrid("reload");
        }
    });     
    
    return false;   
}
function list_edit(id) {
    $.overlay_ajax(doc_base + "AdminCatalog/Ajax_ListsEdit/" + id, "ajax", {}, {
		auto_focus: true,
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(r) {
            $("#lists").datagrid("reload");
        }
    });     
    
    return false;   
}

function list_delete(id) {
    $.overlay_confirm("<div class='admin_message warning'><?=t("admin_confirm_prefix") . " " . et_js("delete list") . "?"?></div>", function() {
        $("#lists").datagrid("reload", { "delete" : id });
    });    
}
</script>

<?php 
	$title = et("Lists");
	if ($list) {
		$title.= " - " . $list->title;
		$menu = "";
	} else {
		$menu = Admin::ButtonS(et("Add list"), "#", "right", false, "onclick='list_add(); return false'");
	}
	
    echo Admin::SectionBegin($title . $menu, false, 1);
    
    echo "<div id='lists'></div>";
    
    echo Admin::SectionEnd();
?>

