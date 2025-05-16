<?php ?>

<table class="admin_list" style="width: 100%">
<tr>
    <th>Title</th>
    <th>Date</th>
    <th>Value</th>
</tr>
<?php foreach ($discounts as $discount): ?>
<tr>
    <td><a href="<?=site_url("AdminCatalog/EditDiscount/" . $discount->id)?>" onclick="return admin_redirect(this)"><?=$discount->title?></a></td>
    <td><?=\Sloway\utils::date_time($discount->time_from) . " - " . \Sloway\utils::date_time($discount->time_to)?></td>
    <td><?=$discount->value?>%</td>
</tr>
<?php endforeach ?>
</table>
