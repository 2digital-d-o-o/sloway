<?php
	namespace Sloway;
?>

<script>
function show_fields() {
    var values = {
        company_chk: $("#cart [name=company_chk]").val(),
        del_diff: $("#cart [name=del_diff]").val(),
        shipping_addr: $("#cart [name=shipping_addr]").val(),
    }    
    for (var i in values)
        if (values[i] == "") values[i] = "0";
    
    $("#cart [data-vis]").each(function() {
        var vis = $(this).attr("data-vis").split(" ");
        var res = true;
        for (var i in vis) {
            var cnd = vis[i];
            if (cnd[0] == "!") 
                res = res && values[cnd.substring(1)] == "0"; else
                res = res && values[cnd] != "0";
        } 
        
        if (res)
            $(this).show(); else
            $(this).hide();
    });
}
$(document).ready(function() {
    <?php if (config::get("orders.company")): ?>
    $("#cart [name=company_chk]").bind("ac_created", function(e, trg) {
        trg.change(function() { show_fields() });
    });
    <?php endif ?>
    $("#cart [data-name=shipping_addr]").bind("ac_created", function(e, trg) {
        trg.change(function() {
            var code = $(this).val();
            var addr = addresses[code];

            $("#cart [data-name=del_street").attr("readonly", 1).ac_value(addr.street);
            $("#cart [data-name=del_city").attr("readonly", 1).ac_value(addr.city);
            $("#cart [data-name=del_country").attr("readonly", 1).ac_value("SI");
            $("#cart [data-name=del_zipcode_str").attr("readonly", 1).ac_value(addr.title);
            
            $("#cart [name=del_zipcode").val(addr.zipcode);
            $("#cart .delivery .acontrol.ac_invalid").removeClass("ac_invalid");
            
            show_fields();
        }); 
    });
    $("#cart [name=del_diff]").bind("ac_created", function(e, trg) {
        trg.change(function() { show_fields() });
    });
    
    $("#cart").ac_create(); 
    
    show_fields();  
});
</script>

<h1><?php echo et('order_address_header') ?></h1>

<table class="cart_form">
<tr class="required">
    <td><?=et("Email")?></td>
    <td><?=acontrol::edit("email", v($order, "email"), array("invalid" => v($order->err_fields, "email", 0)))?></td>
</tr>
<tr class="required">
    <td><?=et("Firstname")?></td>
    <td><?=acontrol::edit("firstname", v($order, "firstname"), array("invalid" => v($order->err_fields, "firstname", 0)))?></td>
</tr>
<tr class="required">
    <td><?=et("Lastname")?></td>
    <td><?=acontrol::edit("lastname", v($order, "lastname"), array("invalid" => v($order->err_fields, "lastname", 0)))?></td>
</tr>  
<?php if ($phone_cfg = config::get("orders.phone", false)): ?>    
<tr class="required">
    <td><?=et("Phone")?></td>
    <td>
        <?php if ($phone_cfg === "code"): ?>
        <div style="width: 30%; float: left">
            <?=acontrol::edit("phone_code", v($order, "phone_code"), array("placeholder" => t("Dial code"), "invalid" => v($order->err_fields, "phone_code", 0)))?>
        </div>
        <div style="width: 68%; float: right">
            <?=acontrol::edit("phone", v($order, "phone"), array("placeholder" => t("Phone number"), "invalid" => v($order->err_fields, "phone", 0)))?>
        </div>
        <?php else: ?>
        <div>
            <?=acontrol::edit("phone", v($order, "phone"), array("invalid" => v($order->err_fields, "phone", 0)))?>
        </div>
        <?php endif ?>
    </td>
</tr>
<?php endif ?>
<?php if (config::get("orders.company")): ?>
<tr>
    <td></td>
    <td>
        <label for="company_chk"><?=et("company_chk")?></label>
        <?=acontrol::checkbox("company_chk", v($order, "company_chk", 0))?>
    </td>
</tr>
<tr data-vis="company_chk">
    <td><?=et("Company")?></td>
    <td><?=acontrol::edit("company", v($order, "company"))?></td>
</tr>
<tr data-vis="company_chk">
    <td><?=et("Company_vat")?></td>
    <td><?=acontrol::edit("vat_id", v($order, "vat_id"))?></td>
</tr>
<?php endif ?>
<tr class="required">
    <td><?=et("Street")?></td>
    <td><?=acontrol::edit("street", v($order, "street"), array("invalid" => v($order->err_fields, "street", 0)))?></td>
</tr>
<tr class="required">
    <td><?=et("Zipcode")?></td>
    <td><?=acontrol::edit("zipcode", v($order, "zipcode"), array("invalid" => v($order->err_fields, "zipcode", 0)))?></td>
</tr>
<tr class="required">
    <td><?=et("City")?></td>
    <td><?=acontrol::edit("city", v($order, "city"), array("invalid" => v($order->err_fields, "city", 0)))?></td>
</tr>
<tr class="required">
    <td><?=et("Country")?></td>
    <td><?=acontrol::select("country", order::$countries, v($order, "country", "SI"), array("invalid" => v($order->err_fields, "country", 0)))?></td>
</tr>
<tr>
    <td></td>
    <td>
        <input class="ac_checkbox" type="checkbox" name="del_diff" value="<?=intval($order->del_diff)?>">
        <label for="del_diff"><?=et('delivery_chk')?></label>
    </td>
</tr>

<tr data-vis="del_diff" class="required">
    <td><?=et("Firstname")?></td>
    <td><?=acontrol::edit("del_firstname", v($order, "del_firstname"), array("invalid" => v($order->err_fields, "del_firstname", 0)))?></td>
</tr>
<tr data-vis="del_diff" class="required">
    <td><span class="required">*</span><?=et("Lastname")?></td>
    <td><?=acontrol::edit("del_lastname", v($order, "del_lastname"), array("invalid" => v($order->err_fields, "del_lastname", 0)))?></td>
</tr>    
<tr data-vis="del_diff" class="required">
    <td><?=et("Street")?></td>
    <td><?=acontrol::edit("del_street", v($order, "del_street"), array("invalid" => v($order->err_fields, "del_street", 0)))?></td>
</tr>
<tr data-vis="del_diff" class="required">
    <td><?=et("Zipcode")?></td>
    <td><?=acontrol::edit("del_zipcode", v($order, "del_zipcode"), array("invalid" => v($order->err_fields, "del_zipcode", 0)))?></td>
</tr>    
<tr data-vis="del_diff" class="required">
    <td><?=et("City")?></td>
    <td><?=acontrol::edit("del_city", v($order, "del_city"), array("invalid" => v($order->err_fields, "del_city", 0)))?></td>
</tr>
<tr data-vis="del_diff" class="required">
    <td><span class="required">*</span><?=et("Country")?></td>
    <td><?=acontrol::select("del_country", order::$countries, v($order, "del_country", "SI"), array("invalid" => v($order->err_fields, "del_country", 0)))?></td>
</tr>
</table>

<div class="cart_form_option">
    <input class="ac_checkbox terms" type="checkbox" name="accept" value="<?=intval($order->accept)?>" <?php if (v($order->err_fields, "accept", false)) echo "data-invalid='true'"?>>
    <label for="accept"><?=message::load("terms.order")->content?></label>
</div>

