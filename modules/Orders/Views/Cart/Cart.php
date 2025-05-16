<?php

namespace Sloway;

echo google_api::begin_checkout($order);
?>

<script>
function cart_update_item() {
    var post = {
        next_step: "Cart",    
        action: "update",
        name: $(this).attr("name"),
        index: $(this).parents("tr").attr("data-index"),
        value: $(this).val()
    }
    $.post('<?=url::site("Cart/Submit/Cart")?>', post, function(r) {
        if (r.redirect) {
            $.overlay_loader();

            window.location.href = r.redirect; 
        } else
        if (r.result)
            $("#cart").replaceWith(r.content);
    }, "json");
        
    return false;     
}
$(document).ready(function() {
    $("#cart").ac_create();
	$("#cart .cart_item_remove").click(function() {
		var post = {
			next_step: "Cart",    
			action: "remove",
			index: $(this).attr("href")
		}
		
		$.post('<?=url::site("Cart/Submit/Cart")?>', post, function(r) {
			if (r.redirect) {
                $.overlay_loader();
                
				window.location.href = r.redirect; 
            } else
			if (r.result)
				$("#cart").replaceWith(r.content);
		}, "json");
			
		return false;     
	});
    
    $("#cart .cart_item input").change(function() {
        cart_update_item.apply(this);
	}).keyup(function(e) {
        if (e.keyCode == 13) 
            cart_update_item.apply(this);
    });
    
});
</script>

<?php
    $columns = $columns[$editable ? "all" : "index"]; 
?>

<h1><?=et("cart_cart_header")?></h1>

<table id="cart_items">
<thead>
<tr>
    <?php foreach ($columns as $column): ?>
	<th data-col="<?=$column?>"><?=et('cart_column_' . $column)?></th>
    <?php endforeach ?>
</tr>
</thead>
<tbody>
<?php 
	foreach ($order->items as $i => $item)  
		echo view(cart_find_view("Item"), array("order" => $order, "item" => $item, "edit" => true, "index" => $i, "columns" => $columns));
?>
</tbody>
<tfoot>
<tr>
	<td colspan="<?=count($columns)-2?>"></td>
	<td style="text-align: right"><?php echo et('Total') ?>:</td> 
	<td><h2><?php echo $order->price('order','all,format') ?></h2></td>
</tr>
</table>

<?php echo $bottom_content ?>
