<script>  
$(document).ready(function() {
    var categories_model = <?=json_encode($dg_model)?>;
    
    $("#categories").datagrid({
        mode: "ajax",
        model: categories_model,
        modules: ["col_resize", "sorting", "edit", "treeview"],
        handler: doc_base + 'AdminCatalog/Ajax_CategoriesHandler',
        cookie_prefix: '<?=\Sloway\core::$project_name?>_',
        layout: {
            freeze: [0,1],
            fill: "spacer",
        },
        treeview: {
            dnd: true, 
            drag_grip: "img",
            save_expanded: true,

            onDrop: function(param) {
				$.overlay_confirm("<div class='admin_message warning'><?=t("admin_confirm_prefix") . " " . et_js("move category") . "?"?></div>", function() {
					$("#categories").datagrid("reload", {"reorder" : param.src_id, "parent" : param.dst_id, "index" : param.index}); 
				});
            }
        },
        footer: {
            left: "reload,row_check_info"
        },
        onCellEdit: function(param) {
            $.post(doc_base + 'AdminCatalog/Ajax_CategoryCellEdit', {
                x: param.col,
                y: param.row,
                id: param.row_id,
                name: param.col_id,
                value: param.new_value,
                height: 0.5
            }, function(r) {
                $("#categories").datagrid("update_cell", r.x, r.y, r.content, r.value);    
            }, "json");
            
            return { overlay: false };
        },
        onCellCustomEdit: function(p) {
            if (p.col_id == 'flags') {
                $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_CategoryFlags/' + p.row_id, "ajax", {}, {
                    scrollable: true,
                    height: 0.5,
                    onDisplay: function() {
                        $(this).ac_create();
                    },                
                    onClose: function(r) {
                        if (!r) return;
                        
                        $("#categories").datagrid("reload");    
                    }
                });
            } else
            if (p.col_id == 'users') {
                $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_CategoryUsers/' + p.row_id, "ajax", {}, {
                    scrollable: true,
                    height: 0.5,
                    onDisplay: function() {
                        $(this).ac_create();
                    },                
                    onClose: function(r) {
                        if (!r) return;
                        
                        $("#categories").datagrid("reload");    
                    }
                });
            }
        }
    });
});

function categories_add(pid) {
    if (typeof pid == "undefined") pid = 0;
    $.overlay_ajax(doc_base + "AdminCatalog/Ajax_CategoriesAdd/" + pid, "ajax", {}, {
		auto_focus: true,
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(url) {
            if (url) 
				admin_redirect(url); else
				$("#categories").datagrid("reload");
        }
    });     
    
    return false;   
}

function categories_delete(id) {
    $.overlay_confirm("<div class='admin_message warning'><?=t("admin_confirm_prefix") . " " . et_js("delete category") . "?"?></div>", function() {
        $("#categories").datagrid("reload", { "delete" : id });
    });    
}

function categories_visibility(id) {
	$.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Visibility/catalog_category/' + id, "ajax", {}, {
		auto_focus: false,
        onLoaded: function() {
            $(this).ac_create();
        },
        onClose: function(r) {
            $("#categories").datagrid("reload");
        },
    });
}

</script>

<?php 
    $menu = \Sloway\Admin::ButtonS(t("Add category"), "#", "right", false, "onclick='categories_add(); return false'");
    echo \Sloway\Admin::SectionBegin(et("Categories") . $menu, false, 1);
    
    echo "<div id='categories'></div>";
    
    echo \Sloway\Admin::SectionEnd();
?>

