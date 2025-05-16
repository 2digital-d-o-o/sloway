<?php
	namespace Sloway;
?>

<script>  
$(document).ready(function() {
    var news_model = <?=json_encode($dg_model)?>;
    
    $("#news").datagrid({
        mode: "ajax",
        model: news_model,
        modules: ["col_resize", "sorting", "edit", "pages"],
        handler: doc_base + 'AdminNews/Ajax_Handler',
        sorting: {
            sort: "id",
            sort_dir: -1
        },
        layout: {
            freeze: [0,1],
            fill: "spacer",
        },
        footer: {
            left: "reload",
            right: "pages_perpage,pages_info,pages"
        },
        onCellClick: function(e, param) {
            if (param.col_id == 'flags') {
                $.overlay_ajax(doc_base + 'AdminNews/Ajax_EditFlags/' + param.row_id, "ajax", {}, {
                    scrollable: true,
                    height: 0.3,
                    onDisplay: function() {
                        $("#ref_flags").ac_checktree();
                    },                
                    onClose: function(r) {
                        if (!r) return;
                        
                        $("#news").datagrid("reload");    
                    }
                });
            } else
                return false;
        },
    });
});

function news_add() {
    $.overlay_ajax(doc_base + "AdminNews/Ajax_AddNews", "ajax", {}, {
        onDisplay: function(r) {
            $(this).ac_create();
        },    
        onClose: function(r) {
            if (r) admin_redirect(r); else $("#news").datagrid("reload");
        }
    });     
    
    return false;   
}

function news_delete(id) {
    $.overlay_confirm('<?=Admin::Confirm("delete news")?>', function() {
        $("#news").datagrid("reload", { "delete" : id });
    });    
}

function news_visible(id) {
	$.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Visibility/news/' + id, "ajax", {}, {
		auto_focus: false,
        onLoaded: function() {
            $(this).ac_create();
        },
        onClose: function(r) {
            $("#news").datagrid("reload");
        },
    });
}

</script>

<?php 
	$menu = Admin::ButtonS(t("Add"), null, "right", false, "onclick='news_add()'");   
	echo Admin::SectionBegin(et("News") . $menu, false);
    
    echo "<div id='news'></div>";
    
	echo Admin::SectionEnd();
?>                  

