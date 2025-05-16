<?php
	use Sloway\Admin;
	use Sloway\mlClass;

	$tree_treshold = \Sloway\config::get("catalog.tree_treshold");
?>

<script>  
$(document).bind("content_lang", function(e, lang) {
	$("#catalog").datagrid("reload");
});
$(document).ready(function() {
	var catalog_model = <?=json_encode(array_values($dg_model))?>;

	$("#catalog").datagrid({
		mode: "ajax",
		cls: "catalog_list",
		model: catalog_model,
		modules: ["pages", "col_resize", "edit", "sorting", "<?=($mode == "tags") ? "row_check" : "treeview"?>"],
		handler: doc_base + 'AdminCatalog/Ajax_CatalogHandler/catalog/<?=$mode?>/<?=$product_id?>',
<?php /*        cookie_name: "<?=\Sloway\core::$project_name?>_catalog_<?=$product_id?>", */ ?>
        pages: {
            per_page: 20,    
        },
		layout: {
			freeze: [1,1],
			fill: "spacer",
		},
		footer: {
            left: "reload",
			right: "pages_perpage,pages_info,pages"
		},
		row_check: {
            single: false,
			name: "checked",
		},		
        treeview: {
            save_expanded: true,
            types: true,

            onExpand: function() {
                var cnt = $(this).attr("data-count");
                if (cnt > <?=$tree_treshold?>) {
                    admin_redirect(doc_base + "AdminCatalog/Index/<?=$mode?>/" + $(this).attr("data-id"));
                    return false;
                }
                return true;
            }
		},
        onCellCustomEdit: function(p) {
            if (p.col_id == "stock") {
                $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_StockManager/' + p.row_id, "ajax", {}, { 
                    width: 0.6,
                    height: 0.8,
                    auto_focus: false,
                    onLoaded: function() { $(this).ac_create() }, 
                    onClose: function(r) {
                        if (r)
                            $("#catalog").datagrid("reload");
                    } 
                });            
            } else
            if (p.col_id == "tags") {
                $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_EditTags/' + p.row_id, "ajax", {}, {
                    onLoaded: function() { $(this).ac_create() },
                    onClose: function(r) { if (r) $("#catalog").datagrid("reload") }
                });            
            } else            
            if (p.col_id == "properties") {
                $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_EditProperties/' + p.row_id, "ajax", {}, {
                    onLoaded: function() { $(this).ac_create() },
                    onClose: function(r) { if (r) $("#catalog").datagrid("reload") }
                });            
            } else
            if (p.col_id == "lists") {
                $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_EditLists/' + p.row_id, "ajax", {}, {
                    onLoaded: function() { $(this).ac_create() },
                    onClose: function(r) { if (r) $("#catalog").datagrid("reload") }
                });            
            } else
            if (p.col_id == "price") {
                $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_EditPrice/' + p.row_id, "ajax", {}, {
                    onLoaded: function() { $(this).ac_create() },
                    onClose: function(r) { if (r) $("#catalog").datagrid("reload") }
                });            
            } else
            if (p.col_id == 'flags') {
                $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_ProductFlags/' + p.row_id, "ajax", {}, {
                    onDisplay: function() { $(this).ac_create(); },                
                    onClose: function(r) { if (r) $("#catalog").datagrid("reload") }    
                });
            }             
        },
		onCellEdit: function(param) {
            $.post(doc_base + 'AdminCatalog/Ajax_CatalogUpdate', {
                x: param.col,
                y: param.row,
                id: param.row_id,
                name: param.col_id,
                value: param.new_value
            }, function(r) {
                $("#catalog").datagrid("update_cell", r.x, r.y, r.content, r.value);    
            }, "json");
            
            return { overlay: false };
        }   
	});
});

function catalog_new_product(type) {
    $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_NewProduct/' + type, "ajax", {}, {
		auto_focus: true,
        onLoaded: function() {
            $(this).ac_create();
        },
        onClose: function(url) {
            if (url) 
				admin_redirect(url); else
				$("#catalog").datagrid("reload");
        },
    });
    
    return false;
}

function catalog_new_item(pid) {
    $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_NewItem/' + pid, "ajax", {}, {
		auto_focus: true,
        onLoaded: function() {
            $(this).ac_create();
        },
        onClose: function(url) {
            if (url) 
				admin_redirect(url); else
				$("#catalog").datagrid("reload");
        },
    });
    
    return false;
}

function catalog_visibility(id) {
    $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Visibility/catalog_product/' + id, "ajax", {}, {
		auto_focus: false,
        onLoaded: function() {
            $(this).ac_create();
        },
        onClose: function(r) {
            $("#catalog").datagrid("reload");
        },
    });
    
    return false;
}

function catalog_duplicate(src) {
    var id = src.attr("data-id");
    var type = src.attr("data-type");
    
    $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Duplicate/' + id + '/' + type, "ajax", {}, {
        onLoaded: function() {
            $(this).ac_create();
        },
        onClose: function(r) {
            if (!r) return;
            
            if ($.isString(r)) {
                $.overlay_loader();
                window.location.href = r; 
            } else
                $("#catalog").datagrid("reload");
            
        },
    });
}

function catalog_toggle_tags() {
    var chk = $("#catalog").datagrid("checked");
    $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_TagsToggle', "ajax", {
        checked: chk,
    }, {
        auto_focus: false,
        scrollable: true,
        onLoaded: function() {
            $(this).ac_create();
        },
        onClose: function(r) {
            if (!r) return;
            
            if ($.isString(r)) {
                $.overlay_loader();
                window.location.href = r; 
            } else
                $("#catalog").datagrid("reload");
        },
    });
}


function catalog_delete(src) {
    var id = src.attr("data-id");
    var type = src.attr("data-type");
    
    $.overlay_confirm('<?=\Sloway\Admin::Confirm("delete product")?>', function() {
        var post = {};
        post["delete_" + type] = id;
        
        $("#catalog").datagrid("reload", post);
    });      
}

</script>


<?php   
    if ($product) {
        $header = Admin::IconB("icon-back.png", "ajax:" . site_url("AdminCatalog")) . "&nbsp;";         
        $header.= et("Product") . ": " . $product->title;
        
        if (Admin::auth("catalog.products.add") && $product->type == 'group')
            $header.= Admin::ButtonS(et("Add item"), null, "right", false, "onclick='catalog_new_item($product_id)'");
    } else 
    if ($mode == "tags") {
        $header = et("Tag Manager");

        $header.= Admin::ButtonS(et("Close tag manager"), "ajax:" . site_url("AdminCatalog"), "right", false);
        $header.= Admin::ButtonS(et("Toggle tags"), null, "right", false, "onclick='catalog_toggle_tags()'");
    } else {
        $header = et("Articles");
        
        if (\Sloway\config::get("catalog.tags"))
            $header.= Admin::ButtonS(et("Tag manager"), "ajax:" . site_url("AdminCatalog/Index/tags"), "right", false);

        if (Admin::auth("catalog.products.add")) 
        foreach ($types as $type)
            $header.= Admin::ButtonS(et("catalog_add_" . strtolower($type)), "#", "right", false, "onclick='catalog_new_product(\"$type\"); return false'");
    }
    
	echo Admin::SectionBegin($header, false, 1);
	echo "<div id='catalog'></div>";
	echo Admin::SectionEnd();    
?>
