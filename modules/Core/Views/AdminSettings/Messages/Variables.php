<div class="admin_section admin_form_menu">
    <h2 class="admin_section_header plain"><?=et("Variables")?></h2>
    <div style="overflow: auto">
        <table class="admin_list" style="width: 100%">
            <?php foreach ($variables as $variable): ?>
            <tr>
                <td>%<?=strtoupper($variable)?>%</td>
                <td><?=et("variable_$variable")?></td>
            </tr>
            <?php endforeach ?>
        </table>
    </div>
</div>
