<script>
$.admin.order_editor.add_item = function(src) {
    $.overlay_close(false);
    $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Browser', "ajax", { param: { check: "item"}}, {
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
            console.log(r);
            if (!r) return;
            
            $.overlay_ajax(doc_base + "AdminOrders/Ajax_Interface/" + r[0].id, "ajax");
            
            /*
            var ops = $(this).data("overlay");
            
            for (var i = 0; i < r.length; i++) {
                var node = $.admin.edittree.add(ops.target, 'item');
                
                $("[data-name=id_ref]", node).val(r[i].id);
                $("[data-name=code]", node).val(r[i].code);   
                $("[data-name=title]", node).val(r[i].title);   
                $("[data-name=price]", node).ac_value(r[i].price);
                $(".item_title", node).html(r[i].title).attr("title", r[i].title);
            } */
        }    
    }); 
}
$(document).module_loaded(function() {
    $("[name=date]").datetimepicker();
    $("#article_list").bind("admin_edittree_loaded", function() {
        $(this).find(".admin_et_node[data-type=item] [data-fname=quantity]").bind("change", function() {
            var value = parseInt($(this).val());
            if (isNaN(value) || value <= 0 || value > 10) {
                $(this).val($(this).attr("data-prev-value")).blur();
                return;    
            }
            
            var node = $(this).parents(".admin_et_node:first");
            var tickets = node.find(".admin_et_nodes > li");
            var cnt = tickets.length;
            if (cnt < value) {
                for (var i = 0; i < value-cnt; i++)
                    $.admin.edittree.add(node, "eticket");
            } else {
                for (var i = value; i < cnt; i++) {
                    $.admin.edittree.remove($(tickets[i]));
                }    
            }
        });
    }).bind("admin_edittree_add", function(e, node, type) {
        if (type == "eticket" || type == "tticket") {
            node.find("[data-fname=firstname]").ac_value($("#module_content [name=del_firstname]").val());
            node.find("[data-fname=lastname]").ac_value($("#module_content [name=del_lastname]").val());
            node.find("[data-fname=street]").ac_value($("#module_content [name=del_street]").val());
            node.find("[data-fname=city]").ac_value($("#module_content [name=del_city]").val());
            node.find("[data-fname=zipcode]").ac_value($("#module_content [name=del_zipcode]").val());
            node.find("[data-fname=country]").ac_value($("#module_content [name=del_country]").val());
        }
    });
});
</script>
<?php    
    $date = ($this->order->date) ? date("d.m.Y H:i", $this->order->date) : "";
    
    echo Admin::AjaxForm_Begin('AdminOrders/Ajax_Save/' . $this->order->id, array("auto" => false, "back" => url::site("AdminOrders/View/" . $this->order->id)));
    
    echo "<input id='order_status' type='hidden' name='status_orig' value='" . $this->order->status . "'>";
    
    echo Admin::SectionBegin(et("Order number") . " " . $this->order->order_id . " (" . et("order_status_" . $this->order->status) . ")");
        
    echo "<div style='float: left; width: 49%'>"; 
    echo Admin::Field(et("Date"), acontrol::edit("date", $date)); 
    if ($this->order->status != "temporary")
        echo Admin::Field(et("Status"), acontrol::select("status", $this->status_items, $this->order->status));
        
    echo "</div>";
    echo "<div style='float: right; width: 49%'>";  
    echo Admin::Field(et("Payment"), acontrol::select("payment", $this->payment_items, $this->order->payment));
    echo "</div>";
    echo "<div style='clear: both'></div>"; 
    echo "<br><br>";      

    echo "<div style='float: right; width: 49%'>";
    echo "<h2>" . et("Delivery information") . "</h2>"; 
    echo Admin::Field(et("First name"), acontrol::edit("del_firstname", $this->order->del_firstname));
    echo Admin::Field(et("Last name"), acontrol::edit("del_lastname", $this->order->del_lastname));
    echo Admin::Field(et("Street"), acontrol::edit("del_street", $this->order->del_street));
    echo Admin::Field(et("Zipcode"), acontrol::edit("del_zipcode", $this->order->del_zipcode));
    echo Admin::Field(et("City"), acontrol::edit("del_city", $this->order->del_city));
    echo Admin::Field(et("Country"), acontrol::select("del_country", countries::gen("", true), $this->order->del_country, array("mode" => "auto")));
    echo "</div>";
    
    echo "<div style='float: left; width: 49%'>";
    echo "<h2>" . et("Billing information") . "</h2>"; 
    echo Admin::Field(et("E-Mail"), acontrol::edit("email", $this->order->email));  
    echo Admin::Field(et("First name"), acontrol::edit("firstname", $this->order->firstname));
    echo Admin::Field(et("Last name"), acontrol::edit("lastname", $this->order->lastname));
    echo Admin::Field(et("Street"), acontrol::edit("street", $this->order->street));
    echo Admin::Field(et("Zipcode"), acontrol::edit("zipcode", $this->order->zipcode));
    echo Admin::Field(et("City"), acontrol::edit("city", $this->order->city));
    echo Admin::Field(et("Country"), acontrol::select("country", countries::gen("", true), $this->order->country, array("mode" => "auto")));
    echo "</div>";
    
    echo "<div style='clear: both'></div>";
    
    echo Admin::SectionEnd();
    
    echo Admin::SectionBegin('');  
    echo Admin::EditTree('articles', $this->articles, array($this, "Article_Builder"), array("item","ticket"), array('title' => et("Articles"), "id" => "article_list"));
    echo Admin::SectionEnd();    
    
    echo Admin::AjaxForm_End();
?>    
