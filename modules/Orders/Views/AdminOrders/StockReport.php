<table width="100%" class="admin_list">
<thead>
<tr>
    <th><?=et("Order ID")?></th>
    <th><?=et("Item")?></th>
    <th><?=et("Quantity")?></th>
    <th><?=et("Stock")?></th>
</tr>
</thead>
<tbody>
<?php foreach ($orders as $oid => $items): ?>
<?php foreach ($items as $item): ?>
<tr>
    <td><?=$oid?></td>
    <td><?=$item->code . " - " . $item->title?></td>
    <td><?=$item->quantity?></td>
    <td><?=$item->stock?></td>
</tr>
<?php endforeach ?>
<?php endforeach ?>
</tbody>
</table>

