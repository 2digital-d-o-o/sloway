<?php
    $text = $report->count . " " . et("order_mul." . $report->count) . " " . et("order_action_" . $action . "_proc." . $report->count);
    $cls = ($report->error) ? "warning" : "success";
    
    echo "<div class='admin_message $cls'>$text</div>"; 
?>        

<script>
function action_report_details(obj) {
    $.overlay_ajax(doc_base + 'AdminOrders/Ajax_OrderLogDetails/' + $(obj).attr("href"));    
}
</script>
<table width="100%" class="admin_list">
<thead>
<tr>
    <th><?=et("Order ID")?></th>
    <th><?=et("Info")?></th>
    <th>&nbsp;</th>
</tr>
</thead>
<tbody>
<?php foreach ($report->log as $oid => $entries): ?>
<?php foreach ($entries as $entry): ?>
<tr>
    <?php
        $style = "";
        $type = v($entry, "type", null);
        if ($type == "warning")
            $style = "style='color: orange; font-weight: bold'"; else
        if ($type == "failure")
            $style = "style='color: darkred; font-weight: bold'"; 
    ?>
    <td <?=$style?>><?=$oid?></td>
    <td <?=$style?>><?=v($entry, "message")?></td>
    <td style="text-align: right">
    <?php 
        if (isset($entry["lid"])) 
            echo "<a href='{$entry["lid"]}' onclick='action_report_details(this); return false'>" . et("Details") . "</a>"; else
            echo "&nbsp;";
    ?>        
    </td>
</tr>
<?php endforeach ?>
<?php endforeach ?>
</tbody>
</table>

