<?php namespace Sloway ?>

<?php $hidden_fields = array("id_ref", "code", "title", "flags", "categories", "properties", "status", "stock_mask", "ac_title", "tax_rate", "tax_code"); ?>    

<input type="hidden" data-name="type" data-fname="type" value="group">
<?php foreach ($hidden_fields as $name): ?>
<input type="hidden" data-name="<?=$name?>" data-fname="<?=$name?>" value="<?=v($data, $name)?>">
<?php endforeach ?>

<div class="admin_eti_main">
	<div class="admin_eti_menu">
		<a class="admin_link del" onclick="$.admin.edittree.remove(this)"><?=et("Delete")?></a>
	</div>

	<div class="admin_eti_caption item_title"><?=$data->title?></div>

	<table class="admin_et_main">
	<tr>
		<td><?=et("Quantity")?></td>
		<td><?=acontrol::edit("quantity", v($data, "quantity", 1))?></td>
	</tr>
	</table>
</div>
