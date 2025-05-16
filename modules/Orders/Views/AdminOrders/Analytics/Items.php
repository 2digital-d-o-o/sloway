<h2>
    Date: <?=utils::date_time($report->date1)?> - <?=utils::date_time($report->date2)?>
</h2> 

<table class="admin_orders_analytics" width="100%">
<thead>
<tr>
	<td>Code</td>
	<td>Product name</td>
	<td>Price/Piece</td>
	<td>Count</td>
	<td>NET</td>
	<td>VAT</td>
	<td>GROSS</td>
</tr>
</thead>
<tbody>
    <?php foreach ($report->items as $item): ?>
    <tr>
        <td><?=$item->code?></td>
        <td><?=$item->title?></td>
        <td><?=utils::price($item->price)?></td>
        <td><?=$item->quantity?></td>
        <td><?=utils::price($item->sum_net)?></td>
        <td><?=utils::price($item->sum_tax)?></td>
        <td><?=utils::price(fixed::add($item->sum_net, $item->sum_tax))?></td>
    </tr>    
    <?php endforeach ?>
</tbody>
<tfoot>	
<tr>
	<td colspan="3" align="right"><b>Total:</b></td>
	<td><?=$report->total->quantity?></td>
	<td><?=utils::price($report->total->sum_price)?></td>
	<td><?=utils::price($report->total->sum_tax)?></td>
	<td><?=utils::price(fixed::add($report->total->sum_price, $report->total->sum_tax))?></td>
</tr>

<?php foreach ($report->total->sum_add as $name => $price): ?>
<tr>
	<td colspan="3" align="right"><b><?=t("add_price_$name")?>:</b></td>  
	<td colspan="3"></td>
	<td><?=utils::price($price)?></td>
</tr>
<?php endforeach ?>
</tfoot>

