<?php 
	namespace Sloway;
	use Sloway\core;
	use Sloway\config;
	use Sloway\Admin;

	$tree_mode = config::get("catalog.tree.properties", true); 
	$tree_treshold = \Sloway\config::get("catalog.tree_treshold");
?>
<script>  
$(document).ready(function() {
    var properties_model = <?=json_encode($dg_model)?>;
    
    $("#properties").datagrid({
        mode: "ajax",
        model: properties_model,
        modules: ["col_resize", "sorting", "edit", "treeview", "pages"],
        handler: doc_base + 'AdminCatalog/Ajax_PropertiesHandler/<?=$property_id?>',
        cookie_name: "<?=core::$project_name?>_catalog_properties_<?=$property_id?>",
        pages: {
            per_page: 20,    
        },        
        layout: {
            freeze: [0,1],
            fill: "spacer",
        },
        treeview: {
            drag_grip: "img",
            save_expanded: true,
            
            onExpand: function() {
                var cnt = $(this).attr("data-count");
                if (cnt > <?=$tree_treshold?>) {
                    admin_redirect(doc_base + "AdminCatalog/Properties/" + $(this).attr("data-id"));
                    return false;
                }
                return true;
            }
        },
        footer: {
            left: "reload,row_check_info",
            right: "pages_perpage,pages_info,pages"
        },
        onCellEdit: function(param) {
            $.post(doc_base + 'AdminCatalog/Ajax_PropertyCellEdit', {
                x: param.col,
                y: param.row,
                id: param.row_id,
                name: param.col_id,
                value: param.new_value,
            }, function(r) {
                $("#properties").datagrid("update_cell", r.x, r.y, r.content, r.value);    
            }, "json");
            
            return { overlay: false };
        },
        onCellCustomEdit: function(p) {
            if (p.col_id == 'flags') {
                $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_PropertyFlags/' + p.row_id, "ajax", {}, {
                    scrollable: true,
                    height: 0.6,
                    onDisplay: function() {
                        $(this).ac_create();
                    },                
                    onClose: function(r) {
                        if (!r) return;
                        
                        $("#properties").datagrid("reload");    
                    }
                });
            } else
            if (p.col_id == 'filter_template' || p.col_id == 'selector_template') {
                $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_PropertyTemplate/' + p.col_id + '/' + p.row_id, "ajax", {}, {
                    scrollable: true,
                    height: 0.6,
                    onDisplay: function() {
                        $(this).ac_create();
                    },                
                    onClose: function(r) {
                        if (!r) return;
                        
                        $("#properties").datagrid("reload");    
                    }
                });
            }             
        }
    });
});

function properties_add(pid) {
    if (typeof pid == "undefined") pid = 0;
    $.overlay_ajax(doc_base + "AdminCatalog/Ajax_PropertiesAdd/" + pid, "ajax", {}, {
		auto_focus: true,
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(url) {
            if (url) 
				admin_redirect(url); else
				$("#properties").datagrid("reload");
        }
    });     
    
    return false;   
}

function properties_delete(id) {
    $.overlay_confirm("<div class='admin_message warning'><?=t("admin_confirm_prefix") . " " . et_js("delete property") . "?"?></div>", function() {
        $("#properties").datagrid("reload", { "delete" : id });
    });    
}

</script>

<?php 
    if ($property) {
        $header = Admin::ButtonS(t("Back"), "ajax:" . \Sloway\url::site("AdminCatalog/Properties"), "left", false); 
        $header.= et("Property") . ": " . $property->title;
        $header.= Admin::ButtonS(t("Add value"), "#", "right", false, "onclick='properties_add({$property->id}); return false'");
        
        echo Admin::SectionBegin($header, false, 1);
    } else {
        $menu = Admin::ButtonS(t("Add property"), "#", "right", false, "onclick='properties_add(); return false'");
        echo Admin::SectionBegin(et("properties") . $menu, false, 1);
    }
    
    echo "<div id='properties'></div>";
    
    echo Admin::SectionEnd();
?>

