<script>
function order_log_details(id) {
    $.overlay_ajax(doc_base + 'AdminOrders/Ajax_OrderLogDetails/' + id);
}
</script>
<table id="order_log_table" width="100%" class="admin_list">
<thead>
<tr>
    <th><?=et("Time")?></th>
    <th><?=et("Action")?></th>
    <th><?=et("User")?></th>
    <th></th>
</tr>    
</thead>
<tbody>
    <?php foreach ($entries as $entry): ?>
    <tr>
        <td><?=date("d.m.Y H:i", $entry->time)?></td>
        <td><?=et("order_log_" . $entry->action)?></td>
        <td><?=($entry->user) ? $entry->user : "system"?></td>
        <td style="text-align: right">
            <?php if ($entry->details): ?>
            <a href="#" onclick="order_log_details(<?=$entry->id?>); return false"><?=et("View details")?></a>
            <?php endif ?>
            &nbsp;
        </td>
    </tr>
    <?php endforeach ?>
</tbody>
</table>

