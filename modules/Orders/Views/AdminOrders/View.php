<?php 
	use Sloway\admin;

?>
<script>  
function print() {
	window.open('<?php echo site_url('AdminOrders/PrintOrders?ids=' . $order->id) ?>', '<?php echo "Print_" . str_replace("-","_", $order->order_id) ?>', 'width=640')
}

function orders_action(src) {
    var action = src.attr("data-action");
    
    $.overlay_ajax(doc_base + "AdminOrders/Ajax_Action/" + action, "ajax", { ids: "<?=$order->id?>"}, {
        onDisplay: function(r) {
            if (r.action_ok) 
                $.post(doc_base + "AdminOrders/View/<?=$order->id?>/1", {}, function(r) {
                    $("#module_content").html(r);    
                });
        }
    });    
}
</script>

<?php 
	$bs = $order->status;
	if ($bs == "pending" || $bs == "temporary")
		$bs = "nonauth"; 
		
	$back_url = site_url("AdminOrders/Index/" . $bs);

	echo Admin::SectionBegin();
	echo "<h2>";
	echo Admin::IconB("icon-back.png", "ajax:" . $back_url, "", false) . "&nbsp;"; 
	echo et("Status") . ": " . et("order_status_" . $order->status);

	foreach ($actions as $action => $callback) {
		echo Admin::ButtonS(et("order_action_" . $action) . " " . et("order"), false, 'right', false, "onclick='$callback'; data-action='$action'");
	}

	if (Admin::auth("orders.edit"))
	    echo Admin::ButtonS(et("Edit.action"), "ajax:" . site_url("AdminOrders/Edit/" . $order->id), 'right', false);
		
	echo "<div style='clear: right'></div>";
	echo "</h2>";
	
	echo "<h2>" . et("Invoice");
	echo Admin::ButtonS(et("Print"), false, "right", true, "onclick='print()'");
	echo "</h2>";
	
	echo "<div id='invoice' style='border: 1px solid silver; margin-bottom: 10px'>";
	echo $invoice;
	echo "</div>";   
	
	//$back = utils::search($this->input->get("b"), "AdminOrders");		   
	//echo Admin::Button(et('Close'), url::site($back), 'right', false);

	echo "<div style='clear: both'></div>";

	if ($order->message) {
		echo "<h2 style='margin-bottom: 0'>" . et("Message") . "</h2>";
		echo "<div style='border: 1px solid silver; padding: 10px; margin-bottom: 10px'>";
		echo $order->message;
		echo "</div>";
	}
	
	echo Admin::SectionEnd();
?>
