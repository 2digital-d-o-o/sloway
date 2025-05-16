<?php 
	use Sloway\Admin;
	use Sloway\acontrol;

?>

<script>
function cancel_reservations(obj) {
    var ov = $(obj).closest(".overlay");
    var ov_form = ov.find(".overlay_form").append("<input type='hidden' name='cancel_res' value='1'>");
    
    ov_form.submit();
}
</script>
<?php if ($updated): ?>
<script>
$("#catalog").datagrid("reload");
</script>
<?php endif?>

<div style="overflow: auto; position: absolute; top: 20px; left: 20px; right: 20px; bottom: 80px">
    <table width="100%" class="admin_list">
    <thead>
    <tr>
        <th><?=et("Time")?></th>
        <th><?=et("Amount")?></th>
        <th><?=et("New stock")?></th>
        <th><?=et("Info")?></th>
    </tr>    
    </thead>
    <tbody>
    <?php foreach ($entries as $entry): ?>
    <tr>
        <td><?=date("d.m.Y H:i", $entry->time)?></td>
        <td><?=$entry->amount?></td>
        <td><?=$entry->curr_stock?></td>
        <td><?=$entry->info?>&nbsp;</td>
    </tr>
    <?php endforeach ?>
    </tbody>
    </table>
</div>
<div style="position: absolute; left: 20px; right: 20px; bottom: 20px">
    <h2 class="admin_heading2">
        <?=$message?>

		<?php if ($reservations): ?>
        <a class="admin_link del" style="float: right" onclick="cancel_reservations(this)"><?=et("Cancel reservations")?></a>
		<?php endif ?>
    </h2>
    <?php echo Admin::Field(et("Amount") . " (+/-)", acontrol::edit("amount")) ?>
</div>
        
