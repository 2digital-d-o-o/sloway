<?php
	use Sloway\core;
	use Sloway\admin;
?>

<style>
.user_list_inc {
	display: inline-block;
	border: 1px solid darkgreen;
	color: darkgreen;
	height: 16px;
	line-height: 16px;
	border-radius: 2px;
	padding: 0 3px;
	margin-right: 3px;
}
.user_list_exc {
	display: inline-block;
	border: 1px solid darkred;
	color: darkred;
	height: 16px;
	line-height: 16px;
	border-radius: 2px;
	padding: 0 3px;
	margin-right: 3px;
}
</style>
<script>  
$(document).ready(function() {
    var users_model = <?=json_encode($model)?>;
    
    $("#users").datagrid({
        mode: "ajax",
        model: users_model,
        modules: ["col_resize", "sorting", "edit", "pages"],
        cookie_prefix: '<?=core::$project_name?>_',
        handler: doc_base + 'AdminUsers/Ajax_Handler',
        layout: {
            freeze: [0,1],
            fill: "spacer",
        },
        footer: {
            left: "reload,search",
            right: "pages_perpage,pages_info,pages",
        },
        onLoaded: function() {
            var cells = $("#users").datagrid("get_cell", "@username");
            cells.find("input").change(function() {
                var id = $(this).parents(".dg_row:first").attr("data-id");
                $.post(doc_base + "AdminUsers/Ajax_Confirm/" + id);
                
                $(this).attr("disabled", 1);
            });
        },
        onCellClick: function(e, param) {
            if (param.col_id == 'flags') {
                $.overlay_ajax(doc_base + 'AdminUsers/Ajax_EditFlags/' + param.row_id, "ajax", {}, {
                    onDisplay: function() {
                        $(this).ac_create();
                    },                
                    onClose: function(r) {
                        if (!r) return;
                        
                        $("#users").datagrid("update_cell", "@flags", "@" + r.id, r.flags);
                    }
                });
            } else
            if (param.col_id == 'lists') {
                $.overlay_ajax(doc_base + 'AdminUsers/Ajax_EditLists/' + param.row_id, "ajax", {}, {
                    onDisplay: function() {
                        $(this).ac_create();
                    },                
                    onClose: function(r) {
                        if (!r) return;
                        
                        $("#users").datagrid("reload");
                    }
                });
            } else
                return false;
        },  
    });
});

function user_add() {
    $.overlay_ajax(doc_base + "AdminUsers/Ajax_Edit", "ajax", {}, {
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(r) {
			if (r) $("#users").datagrid("reload");
		}
    });     
    
    return false;   
}

function user_edit(id) {
    $.overlay_ajax(doc_base + "AdminUsers/Ajax_Edit/" + id, "ajax", {}, {
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(r) {
            $("#users").datagrid("reload");
        }
    });     
    
    return false;   
}

function user_delete(id) {
    $.overlay_confirm("<?=t("admin_confirm_prefix") . " " . t("delete") . "?"?>", function() {
        $("#users").datagrid("reload", { "delete" : id });
    });    
}

</script>

<?php 
	$menu = "<button class='admin_button small' style='float: right' onclick='user_add(); return false'>" . et("Add user") . "</button>";
    echo Admin::SectionBegin(et("Users") . $menu, false);
    
    echo "<div id='users'></div>";
    
    echo Admin::SectionEnd();
?>

