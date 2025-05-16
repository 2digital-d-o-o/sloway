
<div class="admin_catalog_property_editor">
    <input type="hidden" name="properties" value="<?=$value?>" style="width: 100%">
    <table class="admin_list" style="width: 100%">
    <thead>
    <tr>
        <th><?=et("Property")?></th>
        <th><?=et("Value")?></th>
        <th style="text-align: right">
            <a class="admin_button_add small" onclick="$.catalog.property_editor.add.apply(this)"><?=et("Add property")?></a>
        </th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($properties as $property): ?>
    <tr data-pid="<?=$property->id?>" data-vid="<?=$property->value->id?>">
        <td><?=$property->title?></td>
        <td><a onclick="$.catalog.property_editor.edit.apply(this)"><?=$property->value->title?></a></td>
        <td style="text-align: right">
            <a class="admin_button_del small" onclick="$.catalog.property_editor.rem.apply(this)"><?=et("Delete")?></a>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody>
    </table>
</div>
