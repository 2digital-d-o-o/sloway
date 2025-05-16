<table class="admin_orders_analytics" width="100%" style="table-layout: fixed">
<thead>
<tr>
	<td style="width: 40%"></td>
	<td><?=et("Count")?></td>
	<td><?=et("NET")?></td>
	<td><?=et("VAT")?></td>
	<td><?=et("GROSS")?></td>
</tr>
</thead>
<tbody>
<tr>
	<td><b><?=et("Total")?>:</b></td>
	<td><?=$report->quantity?></td>
	<td><?=utils::price($report->sum_price)?></td>
	<td><?=utils::price($report->sum_tax)?></td>
	<td><?=utils::price(fixed::add($report->sum_price, $report->sum_tax))?></td>
</tr>

<?php foreach ($report->sum_add as $name => $price): ?>
<tr>
	<td><b><?=t("add_price_$name")?>:</b></td>  
	<td colspan="3"></td>
	<td><?=utils::price($price)?></td>
</tr>
<?php endforeach ?>
</tbody>
</table>

