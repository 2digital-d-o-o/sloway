<?php namespace Sloway ?>

<?php $hidden_fields = array("id_ref", "id_parent", "reservation_id", "code", "stock_mask", "title", "flags", "categories", "status", "stock_mask", "ac_title"); ?>    

<input type="hidden" data-name="type" data-fname="type" value="item">
<?php foreach ($hidden_fields as $name): ?>
<input type="hidden" data-name="<?=$name?>" data-fname="<?=$name?>" value="<?=v($data, $name)?>">
<?php endforeach ?>

<div class="admin_eti_menu">
    <a class="admin_link del" onclick="$.admin.edittree.remove(this)"><?=et("Remove")?></a>
</div>

<div class="admin_eti_main">
	<div class="admin_eti_caption item_title"><?=$data->title?></div>

	<table class="admin_et_main">
	<tr>
		<td><?=et("Quantity")?></td>
		<td><?=acontrol::edit("quantity", v($data, "quantity", 1))?></td>
		<td><?=et("Price")?></td>
		<td><?=acontrol::edit("price", fixed::fmt(v($data, "price")))?></td>
		<td><?=et("Discount")?> (%)</td>
		<td><?=acontrol::edit("discount", fixed::gen(v($data, "discount", 0) * 100))?></td>
		<td><?=et("Tax rate")?> (%)</td>
		<td><?=acontrol::edit("tax_rate", fixed::gen(v($data, "tax_rate", 0) * 100))?></td>
	</tr>
	</table>
</div>
