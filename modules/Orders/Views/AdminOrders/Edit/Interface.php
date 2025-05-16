<?php 
    $stock_select = array();
    for ($i = 1; $i <= 10; $i++) {
        if ($i > $status->stock)
            $stock_select[$i] = "<strong style='color: darkred'>$i - out of stock</strong>"; else        
            $stock_select[$i] = $i;
    }
?>
<script>
function interface_ready() {
    $(this).ac_create(); 
    $(this).find("input[name^=attr], input[name^=slot], input[name=mode], input[name=location], input[name=country]").change(function() { 
        $(this).closest(".overlay_form").submit();
    });   
}
</script>     

<?php if ($status->group): ?>
<div class="product_form_section">
    <?php foreach ($status->group->attrs as $attr): ?>
    <fieldset class="admin_field">
        <label><?=$attr->title?></label>
        <?=acontrol::select($attr->name, $attr->values, $attr->value)?>
    </fieldset>
    <?php endforeach ?>
</div>
<?php endif ?>

<?php if ($status->bundle): ?>
<div class="product_form_section">
    <?php foreach ($status->bundle->slots as $slot): ?>
    <fieldset class="admin_field">
        <label><?=$slot->title?></label>
        <?=acontrol::select($slot->name, $slot->values, $slot->value)?>
    </fieldset>
    <?php endforeach ?>
</div>
<?php endif ?>    

<fieldset class="admin_field">
    <label><?=et("Amount")?></label>        
    <?=acontrol::select("amount", $stock_select, $status->amount)?>
</fieldset>

  
