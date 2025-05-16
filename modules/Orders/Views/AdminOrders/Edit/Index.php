<?php
	namespace Sloway;
?>

<script>

function order_add_item(src) {
    $.overlay_close(false);
    $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Browser', "ajax", { param: { check: "item" }}, {
        height: 0.8,
        width: 0.7,
        target: src,
        onDisplay: function() {
            catalog_browser_init.apply(this);
        },
        onResize: function() {
            $(".catalog_browser", this).datagrid("update");
        },
        onClose: function(r) {
            if (!r) return;
            
            var ops = $(this).data("overlay");    
                                    
            for (var i = 0; i < r.length; i++) {
                var node = $.admin.edittree.add(ops.target, 'item');
                
                $("[data-name=id_ref]", node).val(r[i].id);
                $("[data-name=code]", node).val(r[i].code);   
                $("[data-name=title]", node).val(r[i].title); 
                $("[data-name$='[price]']", node).ac_value(r[i].price);                  
                $("[data-name$='[discount]']", node).ac_value(r[i].discount);                  
                $("[data-name$='[tax_rate]']", node).ac_value(r[i].tax_rate);                  
                $(".item_title", node).html(r[i].title).attr("title", r[i].title);
            }
        }    
    }); 
}   

$(document).module_loaded(function() {
    $("[name=date]").datetimepicker();
    
    $("#module_menu").ac_create(); 
    $("#module_menu [name=partner]").change(function() {
        var partner = partners[$(this).val()];
        var fields = ["email", "firstname", "lastname", "street", "zipcode", "city", "country"];
        var name, value, del_value;
        for (var i in fields) {
            name = fields[i];
            value = partner[name];
            del_value = partner["del_" + name];
            if (!del_value) del_value = value;

            $("#module_content [name=" + name + "]").ac_value(value);
            $("#module_content [name=del_" + name + "]").ac_value(del_value);
        }
    });
});
</script>
<?php    
    $date = ($order->date) ? date("d.m.Y H:i", $order->date) : "";
    
    echo Admin::AjaxForm_Begin("AdminOrders/Ajax_Save/" . $order->id, array("auto" => false, "back" => url::site("AdminOrders/View/" . $order->id)));
    
    echo "<input type='hidden' name='source' value=''>";
    echo "<input id='order_status' type='hidden' name='status_orig' value='" . $order->status . "'>";
    
    $caption = ($order->id) ? et("Order number") . " " . $order->order_id . " (" . et("order_status_" . $order->status) . ")" : "New order";
    
    echo Admin::SectionBegin($caption);
        
    echo Admin::Column1();
    echo Admin::Field(et("Date"), acontrol::edit("date", $date)); 
    if ($order->status != "temporary")
        echo Admin::Field(et("Status"), acontrol::select("status", $status_items, $order->status));
    echo Admin::Field(et("Payment"), acontrol::select("payment", $payment_items, $order->payment));
    
    echo Admin::Column2();
    foreach (config::get("orders.editor.order_prices") as $name) 
        echo Admin::Field(et("add_price_" . $name), acontrol::edit($name, "", array("placeholder" => t("Leave blank for auto calculation"))));
    
    echo Admin::ColumnEnd();
    
    echo "<br>";

    echo Admin::Column1();
    echo "<h2 class='admin_heading'>" . et("Billing information") . "</h2>"; 

    foreach (config::get("orders.fields.payment") as $name) {
        $name = trim($name, "!");
        if ($name == "country")
            echo Admin::Field(et("Country"), acontrol::select("country", countries::gen("", true), $order->country, array("mode" => "auto"))); else
            echo Admin::Field(et(ucfirst($name)), acontrol::edit($name, $order->$name));  
    }
    
    
    echo Admin::Column2();
    echo "<h2 class='admin_heading'>" . et("Delivery information") . "</h2>"; 

    foreach (config::get("orders.fields.delivery") as $name) {
        $name = trim($name, "!");
        if ($name == "country")
            echo Admin::Field(et("Country"), acontrol::select("del_country", countries::gen("", true), $order->country, array("mode" => "auto"))); else
            echo Admin::Field(et(ucfirst($name)), acontrol::edit("del_" . $name, $order->{"del_" . $name}));  
    }

    echo Admin::ColumnEnd();
    
    echo Admin::SectionEnd();
    
    echo Admin::SectionBegin("");  
    echo $articles_editor;
    echo Admin::SectionEnd();    
    
    echo Admin::AjaxForm_End();
?>    
