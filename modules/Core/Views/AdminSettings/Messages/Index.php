<?php
	use Sloway\admin;

?>

<?php echo Admin::SectionBegin(et("Messages")) ?>
<?php foreach ($modules as $module): ?>
<table class="admin_list" style="width: 100%">
<thead>
<tr>
    <td>
        <?=$module["title"]?> (<a style="<?=v($module, "style")?>" href="<?=$module["url"]?>" onclick="return admin_redirect(this)">Edit template)</a>
    </td>
    <td style="width: 150px"><?=$module["name"]?></td>
</tr>
</thead>
<?php foreach ($module["variations"] as $var): ?>
<tr>
    <td><a style="<?=v($var, "style")?>" href="<?=$var["url"]?>" onclick="return admin_redirect(this)"><?=$var["title"]?></a></td>
    <td><?=$var["name"]?></td>
</tr>
<?php endforeach ?>    
</table>
<br>
<?php endforeach ?>    

