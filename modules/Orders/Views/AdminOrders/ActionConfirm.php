<?php
    if ($report->count)
        echo "<div class='admin_message warning'>" . et("admin_confirm_prefix") . " " . et("order_action_$action.confirm") . " " . $report->count . " " . et("order_mul." . $report->count) . "?</div>"; else
        echo "<div class='admin_message failure'>" . et("No orders") . "</div>";
?>
<table width="100%" class="admin_list">
<thead>
<tr>
    <th><?=et("Order ID")?></th>
    <th><?=et("Info")?></th>
</tr>
</thead>
<tbody>
<?php foreach ($report->log as $oid => $entry): ?>
<tr>
    <?php
        $style = "";
        $type = v($entry, "type", null);
        if ($type == "warning")
            $style = "style='color: orange'"; else
        if ($type == "failure")
            $style = "style='color: darkred; font-weight: bold'"; 
    ?>
    <td <?=$style?>><?=$oid?></td>
    <td <?=$style?>><?=v($entry, "message")?>&nbsp;</td>
</tr>
<?php endforeach ?>
</tbody>
</table>

