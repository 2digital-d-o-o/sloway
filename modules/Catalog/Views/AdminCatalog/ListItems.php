<?php 
	use Sloway\Admin;
?>

<script>  
$(document).ready(function() {
    var tags_model = <?=json_encode($dg_model)?>;
    
    $("#listitems").datagrid({
        mode: "ajax",
        model: tags_model,
        modules: ["col_resize", "sorting", "edit", "pages", "row_check"],
        handler: doc_base + 'AdminCatalog/Ajax_ListItemsHandler/<?=$list->id?>',
        cookie_name: "<?=\Sloway\core::$project_name?>_catalog_lists_<?=$list->id?>",
        pages: {
            per_page: 20,    
        },        
        layout: {
            freeze: [0,1],
            fill: "spacer",
        },
        footer: {
            left:  "reload,|,menu1,|,row_check_info",
            right: "pages_perpage,pages_info,pages",
            elements: {
                menu1: {content: <?=json_encode($dg_footer)?>, enabled: "checked"},
            },      
        },
    });
});

function list_toggle_items(op) {
	var param = { 
		check: "group,item", 
		select_all: true,
		types: "",
		level: 0,
		select_all_title: (op == 1) ? '<?=t("Add shown items")?>' : '<?=t("Remove shown items")?>',
		select_chk_title: (op == 1) ? '<?=t("Add checked items")?>' : '<?=t("Remove checked items")?>',
	}
	$.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Browser', "ajax", { param: param }, {
		height: 0.8,
		width: 0.7,
		onDisplay: function() {
			catalog_browser_init.apply(this);
		},
		onResize: function() {
			$("#browser").datagrid("update");
		},
		onClose: function(r) {
			if (!r) return;
			
			if (r.checked) {
				var ids = [];
				for (var i in r.checked)
					ids.push(r.checked[i].id);
				
				if (op == 1)
					$("#listitems").datagrid("reload", {insert : ids}); else
					$("#listitems").datagrid("reload", {remove : ids});
			} else 
			if (r.filter) {
				if (op == 1)
					$("#listitems").datagrid("reload", {insert_flt : r.filter}); else
					$("#listitems").datagrid("reload", {remove_flt : r.filter}); 
			}
		}   
	});            
}
function list_remove_checked() {
	var ids = $("#listitems").datagrid("checked");
	if (ids)
		$("#listitems").datagrid("reload", {remove: ids});	
}
function list_remove(id) {
	$("#listitems").datagrid("reload", {remove: id});	
}
</script>

<?php 
	$header = Admin::IconB("icon-back.png", "ajax:" . site_url("AdminCatalog/Lists")) . "&nbsp;";   
	$header.= $list->title;	
	//if (!$list->locked) {
		$header.= Admin::ButtonS(et("Remove items"), null, "right", false, "onclick='list_toggle_items(-1)'");
		$header.= Admin::ButtonS(et("Add items"), null, "right", false, "onclick='list_toggle_items(1)'");
	//}
	
    echo Admin::SectionBegin($header, false, 1);
    
    echo "<div id='listitems'></div>";
    
    echo Admin::SectionEnd();
?>

