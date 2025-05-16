<?php
	use Sloway\admin;
?>

<script>     
doc_base = "<?=base_url()?>";
$(document).ready(function() { 
    var users_model = [{
        id: 'title',
        width: 'content',
        content: "<?=t("Username")?>",
    },{
        id: 'role',
        width: 'content',
        edit: 'custom',
        content: "<?=t("Role")?>",
    },{
        id: 'login',
        width: 'content',
        content: "<?=t("Last activity")?>",
    },{
        id: 'modified',
        width: 'content',
        content: "<?=t("Modified")?>",
    },{
        id: 'menu',
        align: 'right',
        width: '80',
        content: "",
    }]; 
    
    var roles_model = [{
        id: 'title',
        width: 'content',
        content: "<?=t("Title")?>",
    },{
        id: 'modified',
        width: 'content',
        content: "<?=t("Modified")?>",
    },{
        id: 'menu',
        align: 'right',
        width: '100',
        content: "",
    }];     

    $("#users").datagrid({
        mode: "ajax",
        model: users_model,
        modules: ["pages", "col_resize", "edit"],
        handler: doc_base + 'AdminSettings/Ajax_UsersHandler', 
        inner_height: 262,
        layout: {
            freeze: [0,1],
            fill: "spacer",
        },
        footer: {
            left: "reload",
            right: "pages_perpage,pages_info,pages"
        },
        onCellClick: function(e, param) {
            if (param.col_id != 'role') return false;
            
            $.overlay_ajax(doc_base + 'AdminSettings/Ajax_EditUserRole/' + param.row_id, "ajax", {}, {
                auto_focus: false,
                onDisplay: function() {
                    $(this).ac_create();
                },                
                onClose: function(r) {
                    if (!r) return;
                    
                    $("#users").datagrid("reload");    
                }
            });
        }
    });

    <?php /* if (Admin::auth("settings.users.roles")): */ ?>
    $("#roles").datagrid({
        mode: "ajax",
        model: roles_model,
        modules: ["col_resize", "treeview"],
        handler: doc_base + 'AdminSettings/Ajax_RolesHandler', 
        inner_height: 262,
        treeview: {
            expand_all: true,  
        },
        layout: {
            freeze: [0,1],
            fill: "spacer",
        },
        footer: {
            left: "reload",
        },
    });
    <?php // endif ?>
});

function edit_user(id) {
    $.overlay_ajax(doc_base + "AdminSettings/Ajax_EditUser/" + id, "ajax", {}, {
        scrollable: 1,
        height: 0.8,
        onDisplay: function() {
            $(this).ac_create();  
        },
        onClose: function(r) {
            if (r) $("#users").datagrid("reload", { "focus" : r });
        }
    });  
}

function edit_role(id) {
    $.overlay_ajax(doc_base + "AdminSettings/Ajax_EditRole/" + id, "ajax", {}, {
        scrollable: 1,
        width: 0.8,
        height: 0.8,
        onDisplay: function() {
            $(this).ac_create();  
        },
        onClose: function(r) {
            if (r) $("#roles").datagrid("reload", { "focus" : r });
        }
    });  
}

function add_role(pid) {
    $.overlay_ajax(doc_base + "AdminSettings/Ajax_EditRole/0/" + pid, "ajax", {}, {
        scrollable: 1,
        width: 0.8,
        height: 0.8,
        onDisplay: function() {
            $(this).ac_create();  
        },
        onClose: function(r) {
            if (r) $("#roles").datagrid("reload", { "focus" : r });
        }
    });      
}

function delete_user(id) {
    $.overlay_confirm('<?=Admin::Confirm("delete user")?>', function() {
        $("#users").datagrid("reload", { "delete" : id });
    });    
}

function delete_role(id) {
    $.overlay_confirm('<?=Admin::Confirm("delete role")?>', function() {
        $("#roles").datagrid("reload", { "delete" : id });
    });    
}



</script>

<?php
	$menu = Admin::ButtonS(et("Add user"), null, "right", false, "onclick='edit_user(0)'");
	echo Admin::SectionBegin(et("Users") . $menu, false);
	echo "<div id='users'></div>";
	echo Admin::SectionEnd();

	$menu = Admin::ButtonS(et("Add role"), null, "right", false, "onclick='edit_role(0)'");
	echo Admin::SectionBegin(et("Roles") . $menu, false);
	echo "<div id='roles'></div>";
	echo Admin::SectionEnd();
?>

