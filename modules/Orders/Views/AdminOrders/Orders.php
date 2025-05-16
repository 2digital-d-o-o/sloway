<?php namespace Sloway; ?>

<script>  
var total_orders;

function orders_resend(src) {
    $.overlay_ajax(doc_base + 'AdminOrders/Ajax_Resend/' + src.attr("data-id"), "ajax", {}, {
        auto_focus: false,
        onDisplay: function() {
            $(this).ac_create();
        }    
    });
}

function orders_refresh() {
	$("#orders").datagrid("reload"); 
}

function orders_print(src) {
	var in_footer = src.parents(".dg_footer:first").length;
	var ids = (in_footer) ? $("#orders").datagrid("checked") : src.attr("data-id");
	
	window.open(doc_base + 'AdminOrders/PrintOrders?ids=' + ids, 'Print orders', 'width=640');      
}

function orders_export() {
    var q = "<?="<div class='admin_message warning'>Are you sure you want to export %COUNT% order/s?</div>"?>";
    q = q.replace("%COUNT%", total_orders);
    $.overlay_confirm(q, function() {
        window.location.href = doc_base + 'AdminOrders/Export/<?=$status?>';        
    });
}

function orders_create() {
    $.overlay_ajax(doc_base + "AdminOrders/Ajax_Create/<?=$status?>", "ajax", {}, {
        onDisplay: function() {
            create_order_init.apply(this);
        }
    });
}

function orders_log(src) {
    var id = src.attr("data-id"); 
    
    $.overlay_ajax(doc_base + 'AdminOrders/Ajax_OrderLog/' + id);
}

function orders_action(src) {
    var action = src.attr("data-action");
    var in_footer = src.parents(".dg_footer:first").length;
    var ids = (in_footer) ? $("#orders").datagrid("checked") : src.attr("data-id");
                                                  
    $.overlay_ajax(doc_base + "AdminOrders/Ajax_Action/" + action, "ajax", { ids: ids }, {
        onDisplay: function(r) {
            if (r.action_end) 
                $("#orders").datagrid("reload", {}, { checked: "" });
        }
    });
}

$(document).ready(function() {
	var orders_model = <?=json_encode($dg_model)?>;  

	$("#orders").datagrid({
		mode: "ajax",
		cls: "orders_list",
		model: orders_model,
        cookie_prefix: '<?=\Sloway\core::$project_name?>_',
		modules: ["pages", "col_resize", "row_check", "sorting", "edit"],
		handler: doc_base + 'AdminOrders/Ajax_OrdersHandler/<?=$status?>',
		layout: {
			freeze: [2,1],
			fill: "spacer",
		},     
		footer: {
			left: "reload,|,menu1,|,row_check_info",
			right: "pages_perpage,pages_info,pages",
            elements: {
                menu1: {content: <?=json_encode($dg_footer)?>, enabled: "checked"},
            },      
 
		},
		onLoaded: function(ops) {
			/*
            total_orders = ops.state.total;
			var stats = ops.data.stats;
			$(".admin_tabs_tab[page^=status]").each(function() {
				var s = $(this).attr("page").replace("status_", "");
				var cnt = (typeof stats[s] != "undefined") ? "(" + stats[s] + ")" : "";
				
				$(".admin_orders_count", this).html(cnt);
			});
			 * 
			 */
            
            $("#orders_export").removeAttr("disabled");
		}
	});
});
</script>
	
<?php   
	echo Admin::SectionBegin(et("order_status_$status.mul") . " " . et("orders") . $header_menu, false);

	echo "<div id='orders'></div>";
	
	echo Admin::SectionEnd();    
?>


