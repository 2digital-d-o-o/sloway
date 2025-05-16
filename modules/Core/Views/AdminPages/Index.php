<?php
	use Sloway\url;
	use Sloway\core;
	use Sloway\admin;
?>

<script>  
$(document).ready(function() {
    var op_lock = <?=intval(\Sloway\Admin::Operation("lock_page"))?>;
    var pages_model = <?=json_encode($dg_model) ?>;
    
    $("#pages").datagrid({
        mode: "ajax",
        model: pages_model,
        modules: ["col_resize", "sorting", "edit", "treeview"],
        cookie_prefix: '<?=core::$project_name?>_',
        handler: doc_base + 'AdminPages/Ajax_Handler',
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
        onLoaded: function() {
            var cells = $("#pages").datagrid("get_cell", "@title");
            cells.bind("contextmenu", function(e) {
                var row = $(this).parents(".dg_row:first");
                var menu = [
                    {content: "<?=t("Rename")?>", name: "rename", icon: "<?=\Sloway\utils::icon("icon-rename.png")?>"},
                    {content: "<?=t("Add subpage")?>", name: "add", icon: "<?=\Sloway\utils::icon("icon-doc-white-add.png")?>"},   
                ];
                if (!parseInt(row.attr("data-locked")) || op_lock)
                    menu.push({content: "<?=t("Delete")?>", name: "delete", icon: "<?=\Sloway\utils::icon("icon-delete.png")?>"});
                
                $(this).contextmenu(e.clientX, e.clientY, menu, {
                    onClick: function(cell, name) {
                        var row = cell.parents(".dg_row:first");
                        if (name == "delete")
                            pages_delete(row.attr("data-id")); else
                        if (name == "add")
                            pages_add(row.attr("data-id")); else
                            $("#pages").datagrid("edit_cell", cell.attr("data-col"), cell.attr("data-row"));
                    }                                               
                });
                
                return false;
            });
        },
        onCellClick: function(e, param) {
            if (param.col_id != 'flags') return false;
            
            $.overlay_ajax(doc_base + 'AdminPages/Ajax_EditFlags/' + param.row_id, "ajax", {}, {
                scrollable: true,
                height: 0.6,
                onDisplay: function() {
                    $("#page_flags").ac_checktree();
                },                
                onClose: function(r) {
                    if (!r) return;
                    
                    $("#pages").datagrid("reload");    
                }
            });
        },
        
        onCellEdit: function(param) {
            $.post(doc_base + 'AdminPages/Ajax_EditCell', {
                x: param.col,
                y: param.row,
                id: param.row_id,
                name: param.col_id,
                value: param.new_value
            }, function(r) {
                $("#pages").datagrid("update_cell", r.x, r.y, r.content, r.value);    
            }, "json");
            
            return { overlay: false };
        },
    });
});

function pages_add(pid) {
    if (typeof pid == "undefined") pid = 0;
    $.overlay_ajax(doc_base + "AdminPages/Ajax_AddPage/" + pid, "ajax", {}, {
        auto_focus: true,
		position: "fixed",
		resizable: "left",
		maximize: "vert",
		pos_x: "right",
		pos_y: 0,
		width: 0.3,
		height: 1,
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(r) {
            if (r) admin_redirect(r); else $("#pages").datagrid("reload");
        }
    });     
    
    return false;   
}

function pages_delete(id) {
    $.overlay_confirm('<?=Admin::Confirm("delete page")?>', function() {
        $("#pages").datagrid("reload", { "delete" : id });
    });    
}
function pages_copy(id) {
    $.overlay_ajax(doc_base + "AdminPages/Ajax_Duplicate/" + id, "ajax", {}, {
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(r) {
            $("#pages").datagrid("reload");
        }
    });     
    
    return false;   
}
</script>

<?php 
	$menu = Admin::ButtonS(et("Add page"), null, "right", false, "onclick='pages_add(0)'");
	echo Admin::SectionBegin(et("Pages") . $menu, false);
    
    echo "<div id='pages'></div>";
    
	echo Admin::SectionEnd();
?>

