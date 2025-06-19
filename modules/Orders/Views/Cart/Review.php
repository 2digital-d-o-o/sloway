<?php
	namespace Sloway;
?>

<script>
$(document).ready(function() {
	$("#cart_promo_apply").click(function() {
		cart_reload();
		return false;
	});
});
</script>

<?php $columns = $columns["review"]; ?>
<h1><?php echo et('cart_review_header') ?></h1>

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
        echo view(cart_find_view("Item"), array("order" => $order, "item" => $item, "edit" => false, "index" => $i, "columns" => $columns));
?>
</tbody>

<tfoot>
<?php foreach ($order->getPrices() as $name => $p): ?>
<tr>
	<td colspan="<?=count($columns)-1?>" style="text-align: right"><?=et("add_price_$name")?>:</td>
	<td><?=$order->price($name,'format') ?></td>
</tr>
<?php endforeach ?>

<tr>
	<td colspan="<?=count($columns)-1?>" style="text-align: right"><?php echo et('Total') ?>:</td> 
	<td><h2><?=$order->price('order','all,format') ?></h2></td>
</tr>
</table>

<?php if ($order->_promo_code): ?>
<div class="cart_promo_dode">
    <span><?= et("cart_promo_code") ?></span>
    
    <input id="cart_promo_code" type="text" name="promo_code" value="<?=$order->promo_code?>" style="max-width: 150px; line-height: 25px" class="ac_border">
	<button id="cart_promo_apply"><?= et("promo_code_apply") ?></button>
    <div id="cart_promo_msg" style="padding-top: 10px"><?=$order->_promo_code->message()?></div>  
</div>
<?php endif ?>

<table id="cart_review_info">
<tr>
    <td>
        <h4><?=et('Payment address')?></h4>
		<?php if ($order->company): ?>
		<span><?=et("Company")?></span><?=$order->company?><br>
		<span><?=et("Vat ID")?></span><?=$order->vat_id?><br>
		<?php endif?>
		<span><?=et("Name and surname")?></span><?=$order->firstname . " " . $order->lastname?><br>
		<span><?=et("Street")?></span><?=$order->street?><br>
		<span><?=et("Zipcode")?></span><?=$order->zipcode?><br>
		<span><?=et("City")?></span><?=$order->city?><br>
		<span><?=et("Country")?></span><?=countries::title($order->country)?><br>
	</td>
	<td>
        <h4><?=et('Delivery address')?></h4>
		<span><?=et("Name and surname")?></span><?=$order->del_firstname . " " . $order->del_lastname?><br>
		<span><?=et("Street")?></span><?=$order->del_street?><br>
		<span><?=et("Zipcode")?></span><?=$order->del_zipcode?><br>
		<span><?=et("City")?></span><?=$order->del_city?><br>
		<span><?=et("Country")?></span><?=countries::title($order->del_country)?><br>
	</td>
	<td>
        <h4><?=et('Payment')?></h4>
        <?=et("payment_" . $order->payment)?>
    </td>
    <?php if (order::$delivery_methods): ?>
    <td>
        <h4><?=et('Delivery')?></h4>
        <?=et("delivery_" . $order->delivery)?>
    </td>
    <?php endif ?>
</tr>
</table>

<?php //xd($order) ?>

