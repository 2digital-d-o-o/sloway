<?php 
	use Sloway\admin;
?>
<script>  
var model = <?=json_encode($model) ?>;

$(document).ready(function() {
    $("#redirects").datagrid({
        mode: "ajax",
        modules: ["col_resize", "sorting", "edit", "pages"],
        model: model, 
        cookie_prefix: '<?=\Sloway\core::$project_name?>_',
        handler: doc_base + 'AdminSettings/Ajax_RedirectsHandler',
        layout: {
            freeze: [0,1],
            fill: "spacer",
        },
        treeview: {
            save_expanded: true,
        },
        footer: {
            left: "reload",
            right: "pages_perpage,pages_info,pages"
        },
        onLoaded: function(r) {
            if (r.data.committed)
                $("#redirects_commit_menu").hide(); else
                $("#redirects_commit_menu").show(); 
        }
    });
    
    $("#redirects_import").fileupload({
        url: doc_base + "AdminSettings/Ajax_ImportRedirects",

        add: function(e, data) {
            $.overlay_loader();
            data.submit();
        },
        done: function(e, data) {     
            $.overlay_close();
            $.overlay_ajax(doc_base + "AdminSettings/Ajax_ImportRedirects", "ajax", { path: data.result }, {
                scrollable: true,
                onDisplay: function(ops) {
                    $("#redirects").datagrid("reload");
                }    
            });
        },
        fail: function (e, data) {       
            $.overlay_close();
        },
    }); 
});

function redirect_edit() {
    var src = $(this).closest(".dg_row").attr("data-id");
    $.overlay_ajax(doc_base + "AdminSettings/Ajax_EditRedirect", "ajax", {
        src: src,
    }, {
        auto_focus: true,
        onDisplay: function(r) {
            $(this).ac_create();
        },                                             
        onClose: function(r) {
            if (r) {
                $("#redirects").datagrid("reload");
                $("#redirects_commit_menu").show(); 
            }    
        }
    });     
    
    return false;   
}
function redirect_add() {
    $.overlay_ajax(doc_base + "AdminSettings/Ajax_EditRedirect", "ajax", {}, {
        auto_focus: true,
        onDisplay: function(r) {
            $(this).ac_create();
        },                                             
        onClose: function(r) {
            if (r) {
                $("#redirects").datagrid("reload");
                $("#redirects_commit_menu").show(); 
            }
        }
    });     
    
    return false;   
}

function redirect_delete() {
    var src = $(this).closest(".dg_row").attr("data-id");
    
    $("#redirects").datagrid("reload", { "delete" : src }, { onLoaded: function(r) { $("#redirects_commit_menu").show() }});
}

function redirects_commit() {
    $.overlay_loader();
    $.post(doc_base + "AdminSettings/Ajax_RedirectsCommit", {}, function(r) {
        $("#redirects").datagrid("reload");
        $("#redirects_commit_menu").hide();
        $.overlay_close();
    });    
}
function redirects_revert() {
    $.post(doc_base + "AdminSettings/Ajax_RedirectsRevert", {}, function(r) {
        $("#redirects").datagrid("reload");
        $("#redirects_commit_menu").hide();
    });    
}

</script>
<?php
    $menu = Admin::ButtonS(et("Add"), false, "right", false, "onclick='redirect_add()'");
    $menu.= "<div style='float: right' class='admin_button small admin_upload_button' id='redirects_import'>";
    $menu.= "Import";
    $menu.= "<input type='file' name='files'>";
    $menu.= "</div>";
     
    echo Admin::SectionBegin(et("Redirects") . $menu);
    echo "<div id='redirects'></div>";
    echo Admin::SectionEnd();
?>
