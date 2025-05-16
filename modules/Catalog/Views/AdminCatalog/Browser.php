<?php
	use Sloway\Admin;
	use Sloway\acontrol;
	use Sloway\arrays;
?>
<script>  
function catalog_browser_preview(src) {
    $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Preview/' + src.attr('href') + '/browser', false, {}, {
        min_height: 152,
        modal: false,
        close_outside: true,
    });         
    return false;
}

function catalog_browser_init(live_callback) {
    var browser_model = <?=json_encode($model)?>;
    var browser_param = <?=json_encode($param)?>;
    
    var toolbar = $(".catalog_browser_toolbar", this);
    toolbar.find("input[data-name=search]").keypress(function(e) {
        if (e.which == $.ac.keys.ENTER) 
            toolbar.find(".cbt_filter_apply").click();
    }); 
    toolbar.ac_create();
    toolbar.find(".cbt_filter_reset").click(function() {
        var tb = $(this).closest(".catalog_browser_toolbar");
        var ov = tb.closest(".overlay");
        tb.find("[name=categories]").ac_value("");
		tb.find("[name=tags]").ac_value("");
        tb.find("[name=search]").ac_value("");
		tb.find("[name=search_mode]").ac_value("groups");
        
        ov.find(".catalog_browser").datagrid("reload", {
            "apply_filter" : 1,
            "filter_cats" : "",
			"filter_tags" : "",
            "filter_search" : "",
			"filter_search_mode" : "groups",
        });
        
        return false; 
    });
    toolbar.find(".cbt_filter_apply").click(function() {
        var tb = $(this).closest(".catalog_browser_toolbar");
        var ov = tb.closest(".overlay");
        
        var dg = ov.find(".catalog_browser");
        dg.datagrid("reload", {
            "apply_filter" : 1,
            "filter_cats" : tb.find("[name=categories]").val(),
			"filter_tags" : tb.find("[name=tags]").val(),
            "filter_search" : tb.find("[name=search]").val(),
			"filter_search_mode" : tb.find("[name=search_mode]").val(),
        });
        
        return false; 
    });
	$(".catalog_browser", this).datagrid({
		mode: "ajax",
		model: browser_model,
		param: browser_param,
        cookie_name: "<?=\Sloway\core::$project_name?>_catalog_browser",
		height: "auto",
		style: {  },
		modules: ["pages", "col_resize", "treeview", "sorting", "<?=($mode == "click") ? "row_click" : "row_check"?>"],
		handler: doc_base + 'AdminCatalog/Ajax_CatalogHandler/browser',
		layout: {
			fill: "spacer",
            freeze: [<?=($mode == "check" || $mode == "check_single") ? "2,0" : "0"?>],
		},
		footer: {
			left: "reload,row_check_info,search",
			right: "pages_perpage,pages_info,pages"
		},
        <?php if ($mode == "check"): ?>
		row_check: {
			check_all: false,
            single: <?=($submode == "single") ? "true" : "false"?>,
			name: "checked",
		},
        <?php endif ?>
		treeview: {
			expand_all: false, 
			column_index: <?=($mode == "click") ? 0 : 1 ?>   
		},               
        <?php if ($mode == "click"): ?>
        onRowClick: function(row_id, index) {
            <?php if ($submode == "live"): ?>

            var ov = $(this).parents(".overlay:first");
            var ops = ov.data("overlay");
            if (typeof live_callback == "function") 
                live_callback.apply(ops.target, [[{id: row_id}], ov, ops]);
                
            <?php else: ?>

            var form = $(this).parents(".overlay_form:first");
            form.append("<input type='hidden' name='submit' value='1'>");
            form.append("<input type='hidden' name='checked' value='" + row_id + "'>");
            form.submit();

            <?php endif ?>
        }
        <?php endif ?>
	});
};
</script>
	
<div class="catalog_browser">

</div>

<div class="catalog_browser_toolbar">
    <div class="cbt_main">
        <fieldset>
            <label><?=et("Search")?></label>
            <?=acontrol::edit("search", $filter->search)?>
			<?=acontrol::select("search_mode", array("group" => "Groups", "item" => "Items"), ($filter->search_mode) ?: "group")?>
        </fieldset>
        <fieldset>
            <label><?=et("Categories")?></label>
            <?=acontrol::checktree("categories", acontrol::tree_items($categories, "subcat"), $filter->cats)?>
        </fieldset>
        <fieldset>
            <label><?=et("Tags")?></label>
            <?=acontrol::checklist("tags", $tags, $filter->tags)?>
        </fieldset>
	</div>
    <div class="cbt_footer">
    <?php 
        echo Admin::ButtonS(t("Reset"), false, "right", false, "", "cbt_filter_reset"); 
        echo Admin::ButtonS(t("Apply"), false, false, false, "style='margin-left: 0'", "cbt_filter_apply"); 
    ?>
    </div>
</div>

