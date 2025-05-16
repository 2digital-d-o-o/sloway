<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>    
<script>
$(document).ready(function() {
    var model = <?=json_encode($this->analytics_model())?>;  

    $("#report").datagrid({
        mode: "ajax",
        model: model,
        cookie_prefix: '<?=PROJECT_NAME?>_',
        modules: ["pages", "col_resize", "sorting", "edit"],
        handler: doc_base + 'AdminOrders/Ajax_AnalyticsHandler',
        layout: {
            freeze: [1,6],
            fill: "spacer",
        },     
        footer: {
            left: "reload,|,row_check_info",
            right: "pages_perpage,pages_info,pages",
        },
        onLoaded: function(ops) {
            $.overlay_close();
            $("#report_total").html(ops.data.totals);
        }        
    });
});

function analytics_print() {
    window.open(doc_base + 'AdminOrders/Ajax_AnalyticsExport/print', "Analytics", 'width=640,scrollbars=1');        
}
function analytics_export() {
    window.location.href = doc_base + 'AdminOrders/Ajax_AnalyticsExport/excel';    
}
</script>
<?php
    $menu = "";
    $menu.= Admin::ButtonS(et("Export"), false, "right", false, "onclick='analytics_export()'");
    $menu.= Admin::ButtonS(et("Print"), false, "right", false, "onclick='analytics_print()'");
    //$menu.= acontrol::select("analytics_view", array("items" => "View items", "orders" => "View orders"), $view, array("id" => "analytics_view"));
    
	echo Admin::SectionBegin(et("Analytics") . $menu);
    
    echo "<div id='report'></div><br>";
    echo "<div id='report_total'></div><br>";
    
    echo $menu;
    
	echo Admin::SectionEnd();                                           
?>
