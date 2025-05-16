<?php 
	namespace Sloway;

    $phone = trim(trim($order->phone_code) . " " . $order->phone);
    
    $payment_info = "";
    $payment_info.= t("E-mail") . ": <span><a href='mailto:$order->email'>$order->email</a></span><br>";
    if ($phone)
        $payment_info.= t("Phone") . ": <span>$phone</span><br>";
    $payment_info.= t("Name") . ": <span>$order->firstname $order->lastname</span><br>";
    $payment_info.= t("Address") . ": <span>$order->street, $order->zipcode $order->city</span><br>";
    $payment_info.= t("Country") . ": <span>" . v(order::$countries, $order->country, "") . "</span><br>";
    
    if ($order->company)
        $payment_info.= t("Company") . ": <span>$order->company</span><br>";
    if ($order->vat_id)
        $payment_info.= t("VAT ID") . ": <span>$order->vat_id</span><br>";
    
    $delivery_info = "";
    $delivery_info = t("Name") . ": <span>$order->del_firstname $order->del_lastname</span><br>";
    $delivery_info.= t("Address") . ": <span>$order->del_street, $order->del_zipcode $order->del_city</span><br>";
    $delivery_info.= t("Country") . ": <span>" . v(order::$countries, $order->del_country, "") . "</span>";
?>

<div class="invoice">
    <table width="100%" class="invoice_top">
    <tr>
        <td align="left">
        </td>
        <td align="left" style="width: 200px; text-align: right">
        </td>
    </tr>
    </table>
    
    <table class="invoice_header" width="100%">
    <tr>
        <td>
        <?php 
            if ($order->order_id) {
                echo t('Order id') . ':&nbsp;';
                echo $order->order_id . "&nbsp;&nbsp;&nbsp;";
            }
            echo t('Date') . ':&nbsp;';
            echo date("d.m.Y", intval($order->date));  
        ?>      
        </td>
    </tr>
    </table>

    <table width="100%" class="invoice_columns">
    <tr>
        <td width="49%">
            <table class="invoice_info invoice_table">
            <thead>
            <tr><td width="50%"><?=t('Payment address')?>:</td></tr>  
            </thead>
            <tbody>
            <tr><td><?=$payment_info?></td></tr>
            </tbody>
            </table>
        </td>
        <td width="2%"></td>   
        <td width="49%">
            <table class="invoice_info invoice_table">
            <thead>
            <tr><td width="50%"><?=t('Delivery address')?>:</td></tr>  
            </thead>
            <tbody>
            <tr><td><?=$delivery_info?></td></tr>
            </tbody>
            </table>
        </td>
    </tr>
    </table>
    
    <table class="invoice_items invoice_table">
    <thead>
        <tr>
            <td class="invoice_item_id"><?=t('Item ID') ?></td>
            <td class="invoice_item_title"><?=t('Title') ?></td>
            <td class="invoice_item_price"><?=t('Price/piece') ?></td>
            <td class="invoice_item_price_tax"><?=t('Price/piece + VAT') ?></td>
            <td class="invoice_item_quantity"><?=t('Quantity') ?></td>
            <td class="invoice_item_discount"><?=t('Discount') ?></td>
            <td class="invoice_item_tax"><?=t('VAT amount') ?></td>
            <td class="invoice_item_total"><?=t('Sum GROSS') ?></td>
        </tr>
    </thead>
    
    <tbody>
    <?php 
        $i = 0;
        foreach ($order->items as $id => $item) {
            $dis = ($order->discount) ? $order->discount : $item->discount;
            $price = $order->itemPrice($item, 'item', 'format');
    ?>
        <tr class="invoice_items_<?php echo $i % 2 ?>">
            <td class="invoice_item_id"><?=$item->code?></td>
            <td class="invoice_item_title"><?=$item->title?></td>
            <td class="invoice_item_price"><?=$price?></td>
            <td class="invoice_item_price_tax"><?=$order->itemPrice($item, 'item', 'tax,format')?></td>
            <td class="invoice_item_quantity"><?=$item->quantity ?></td>
            <td class="invoice_item_discount"><?=$order->itemPrice($item, 'discount', 'format')?></td>
            <td class="invoice_item_tax"><?=$order->itemPrice($item, 'tax', 'format,discount,quantity')?></td>
            <td class="invoice_item_total"><?=$order->itemPrice($item, 'item', 'format,discount,quantity,tax')?></td>
        </tr>
    <?php 
            $i++;
        }
    ?>
    </tbody>
    
    <tfoot>
        <?php foreach ($order->getPrices() as $name => $ap): ?>
        <tr class="invoice_add">
            <td colspan="7" align="right"><?php echo t("add_price_" . $name) ?></td>
            <td><?php echo $order->price($name,'format') ?></td>
        </tr>
        <?php endforeach ?>
        <tr class="invoice_total">
            <td colspan="7" align="right"><?php echo t('Total') ?></td>
            <td><?php echo $order->price('order','all,format') ?></td>
        </tr>
    </tfoot>
    </table>      
    
    <table class="invoice_info invoice_table invoice_mobile">
    <tbody>
    <?php foreach ($order->items as $id => $item): ?>
    <tr>
        <th><?=$item->title?></th>
        <th><?=$item->code?></th>
    <tr>
        <td>
            <?=t('Price/piece')?><br>
            <?=t('Price/piece + VAT')?><br>
            <?=t('Quantity')?><br>
            <?=t('Discount')?><br>
            <?=t('VAT amount')?><br>
            <strong><?=t('Sum GROSS')?></strong>
        </td>
        <td>
            <?php echo $order->itemPrice($item, 'item', 'format') ?><br>
            <?php echo $order->itemPrice($item, 'item', 'tax,format') ?><br>
            <?php echo $item->quantity ?><br>
            <?php echo $order->itemPrice($item, 'discount', 'format') ?><br>
            <?php echo $order->itemPrice($item, 'tax', 'format,discount,quantity') ?><br>
            <strong><?php echo $order->itemPrice($item, 'item', 'format,discount,quantity,tax') ?></strong>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody>
    <tfoot>
    <?php foreach ($order->getPrices() as $name => $ap): ?>
        <tr class="invoice_add">
            <td align="right"><?php echo t("add_price_" . $name) ?></td>
            <td><?php echo $order->price($name,'format') ?></td>
        </tr>
        <?php endforeach ?>
        <tr class="invoice_total">
            <td align="right"><?php echo t('Total') ?></td>
            <td><?php echo $order->price('order','all,format') ?></td>
        </tr>    
        </tfoot>
    </table>
    
    <?php $w = (order::$delivery_methods) ? "32%" : "49%"; ?>
    <table width="100%" class="invoice_columns">
    <tr>
        <td width="<?=$w?>" style="vertical-align: top">
            <table class="invoice_spec invoice_table" width="100%">
            <thead>
            <tr>
                <td><?php echo t('VAT rate') ?></td>
                <td><?php echo t('VAT base') ?></td>
                <td><?php echo t('VAT amount') ?></td>
                <td><?php echo t('Total') ?></td>
            </tr>
            </thead>
            <?php 
                foreach ($order->tax_spec as $tax_rate => $total) {
                    $tr = floatVal($tax_rate);
                    //if ($tr == 0) continue;
                    
                    $b = $total / (1 + $tr);
                    
                    echo "<tr>";
                    echo "<td>" . utils::price($tax_rate * 100,'%') . "</td>";
                    echo "<td>" . utils::price($b) . "</td>";
                    echo "<td>" . utils::price($b * $tr) . "</td>";
                    echo "<td>" . utils::price($total) . "</td>";
                    echo "</tr>";
                } 
            ?>
            </table>
        </td>
        <td width="2%"></td>   
        <td width="<?=$w?>" style="vertical-align: top">
            <table class="invoice_payment invoice_table" width="100%">
            <thead>
            <tr><td><?php echo t('Payment') ?></td></tr>
            </thead>
            <tr><td><?php echo t("payment_" . $order->payment) ?></td></tr>
            </table>
        </td>
        <?php if (order::$delivery_methods): ?>
        <td width="2%"></td>   
        <td width="<?=$w?>" style="vertical-align: top">
            <table class="invoice_payment invoice_table" width="100%">
            <thead>
            <tr><td><?php echo t('Delivery') ?></td></tr>
            </thead>
            <tr><td><?php echo t("delivery_" . $order->delivery) ?></td></tr>
            </table>
        </td>
        <?php endif ?>
    </tr>
    </table>
    <?php /*
    <br />
    
    <table width="100%">
    <tr>
        <td class="invoice_footer">
            
        </td>
    </tr>
    </table>
    */ ?>
</div>
