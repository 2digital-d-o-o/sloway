<?php 

namespace Sloway;

echo facebook_api::generateClientEventScript("Purchase", "Checkout", $order->order_id);
echo google_api::transaction($order);

?>

<div id="cart_notification">
	<h1><?= $msg->title ?></h1><?= $msg->content ?>
</div>

