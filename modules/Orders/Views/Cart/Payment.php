<?php
	namespace Sloway;
?>

<script>
$(document).ready(function() {
	$("#cart").ac_create();    
});
</script>

<br>
<h2><?php echo et('cart_payment_header') ?></h2>
<br>

<ul class="ac_checklist" data-name="payment" data-mode="radio" data-value="<?=$order->payment?>">
<?php 
	foreach (order::$payment_methods as $pm) 
		echo "<li data-value='$pm'><span>" . et('payment_' . $pm) . "</span></li>";
?>
</ul>

<?php if (order::$delivery_methods): ?>
<br>
<h2><?php echo et('cart_delivery_header') ?></h2>
<br>

<ul class="ac_checklist" data-name="delivery" data-mode="radio" data-value="<?=$order->delivery?>">
<?php 
    foreach (order::$delivery_methods as $pm) 
        echo "<li data-value='$pm'><span>" . et('delivery_' . $pm) . "</span></li>";
?>
</ul>
<?php endif ?>




