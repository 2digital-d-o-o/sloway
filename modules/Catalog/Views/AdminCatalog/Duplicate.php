<?php
	use Sloway\Admin;
	use Sloway\acontrol;
	use Sloway\config;
?>

<script>
function catalog_new_product_init() {
    $(this).ac_create();
    $(this).find("[name=type_id]").change(function() {
        var ov = $(this).closest(".overlay");
        var stock_ctrl = ov.find("[name=stock]").closest(".admin_field");
        if ($(this).val() == 0)
            stock_ctrl.show(); else
            stock_ctrl.hide();
    });        
}
</script>

<?php        
    $title = ($original->type == "group") ? $original->title() . " - copy" : "";
    echo Admin::Field(et("Title"), acontrol::edit('title', $title));
    
    if ($type != "bundle") {
        echo Admin::Field(et("Code"), acontrol::edit('code', ""));
    }
        
    echo Admin::Field(et("Initial stock"), acontrol::edit('stock', config::get("catalog.initial_stock", 0)));
?>
        
