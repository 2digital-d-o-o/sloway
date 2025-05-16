(function($){   
    $.admin.order_editor = {
        add_item: function(src) {
            var param = {
                types: "group,travel,bundle",
                level: 0,    
                mode: "click_live"
            }
            $(src).catalog_browser(param, function(r) {
                $.overlay_ajax(doc_base + "AdminOrders/Ajax_Interface/" + r[0].id, "ajax", {}, {
                    scrollable: true,
                    onDisplay: function() {
                        interface_ready.apply(this);
                    },
                    onClose: function(r) {
                        if (!r) return;
                        
                        if (r.type == "item") {
                            var node = $.admin.edittree.add($("#article_list"), "item");
                            var input;
                            for (var i in r) {
                                input = node.find("[data-fname=" + i + "]");
                                if (input.hasClass("ac_value"))
                                    input.ac_value(r[i]); else
                                    input.val(r[i]);
                            }
                                
                            node.find(".item_title").html(r.title).attr("title", r.title);
                            
                            $.admin.order_editor.build_node(node);
                            node.find("[data-fname=quantity]").trigger("change");
                        } else
                        if (r.type == "group") {
                            var node = $.admin.edittree.add($("#article_list"), "group");
                            var input;
                            for (var i in r) {
                                input = node.find("[data-fname=" + i + "]");
                                if (input.hasClass("ac_value"))
                                    input.ac_value(r[i]); else
                                    input.val(r[i]);
                            }
                            node.find(".item_title").html(r.title).attr("title", r.title);
                            
                            var target = node;
                            for (var j = 0; j < r.items.length; j++) {
                                var node = $.admin.edittree.add(target, "item");
                                var input;
                                for (var i in r.items[j]) {
                                    input = node.find("[data-fname=" + i + "]");
                                    if (input.hasClass("ac_value"))
                                        input.ac_value(r.items[j][i]); else
                                        input.val(r.items[j][i]);
                                }
                                node.find(".item_title").html(r.items[j].title).attr("title", r.items[j].title);  
                                $.admin.order_editor.build_node(node);
                                node.find("[data-fname=quantity]").trigger("change");
                            }
                        }
                    }
                });     
            });
        },
        build_tickets: function(node, count, type) {
            var tickets = node.find(".admin_et_nodes > li");
            var cnt = tickets.length;
            if (cnt < count) {
                for (var i = 0; i < count-cnt; i++)
                    $.admin.edittree.add(node, type);
            } else {
                for (var i = count; i < cnt; i++) {
                    $.admin.edittree.remove($(tickets[i]));
                }    
            }    
        },        
        build_node: function(node) {
            node.find("[data-fname=quantity]").bind("change", function() {
                var value = parseInt($(this).val());
                if (isNaN(value) || value <= 0 || value > 10) {
                    $(this).val($(this).attr("data-prev-value")).blur();
                    return;    
                }
                
                var item = $(this).parents(".admin_et_item:first");
                var node = item.parent();
                
                var flags = "," + item.find("[data-fname=flags]").val() + ","; 
                var type = false;
                if (flags.indexOf(",travel,") != -1)
                    type = "tticket"; else
                if (flags.indexOf(",es,") != -1)
                    type = "eticket";
                
                if (type)
                    $.admin.order_editor.build_tickets(node, value, type);
            });
            
            var item = $(this).children(".admin_et_item"); 
            var flags = "," + item.find("[data-fname=flags]").val() + ","; 
            if (flags.indexOf(",es,") == -1) {
                var item = node.children(".admin_et_item");
                var flags = "," + item.find("[data-fname=flags]").val() + ",";         
                if (flags.indexOf(",es,") == -1)
                    item.find(".admin_eti_menu > a.admin_link.edit").hide();
            }                        
        },        
        ticket_delete: function(src) {
            var node = $(src).closest(".admin_et_node");
            var parent = node.parents(".admin_et_node:first");
            var qty_input = parent.find("[data-fname=quantity]");
            var qty = parseInt(qty_input.val()) - 1;
            
            $.admin.edittree.remove(node);
            qty_input.val(qty);
            
            if (qty == 0) 
                $.admin.edittree.remove(parent);
        },
        eticket_tags: function(src) {
            var item = $(src).closest(".admin_et_item");
            var node = item.parent();
            var tags = "";
            if (node.attr("data-type") == "eticket")
                tags = item.find("[data-fname=tags]").val(); 
                
            $.overlay_ajax(doc_base + "AdminOrders/Ajax_ETicketTags/", "ajax", { tags: tags }, {
                scrollable: true,
                height: 0.8, 
                target: node,
                onDisplay: function() {
                    $(this).ac_create();   
                },
                onClose: function(r, d, ops) {   
                    if (!r) return;
                    
                    if (!r.tags_text)
                        r.tags_text = "Edit tags";
                    
                    if (ops.target.attr("data-type") == "eticket") {
                        ops.target.find("[data-fname=tags]").val(r.tags);
                        ops.target.find(".admin_eti_caption > a").html(r.tags_text);    
                    } else {
                        var items = ops.target.find(".admin_et_nodes > li");
                        items.each(function() {
                            $(this).find("[data-fname=tags]").val(r.tags);
                            $(this).find(".admin_eti_caption > a").html(r.tags_text);    
                        });
                    }
                }
            }); 
        },
        create: function(editor) {
            editor.bind("admin_edittree_loaded", function() {
                $(this).find(".admin_et_node[data-type=item]").each(function() {
                    $.admin.order_editor.build_node($(this)); 
                });
            }).bind("admin_edittree_add", function(e, node, type) {
                if (type == "eticket" || type == "tticket") {
                    var val, name, fields = ["email", "firstname", "lastname", "street", "zipcode", "city", "country"];
                    for (var i in fields) {
                        name = fields[i];
                        val = $("#module_content [name=del_" + name + "]").val();
                        if (!val)
                            val = $("#module_content [name=" + name + "]").val();
                        
                        node.find("[data-fname=" + name + "]").ac_value(val);
                    }
                } else
                if (type == "item") {
                    var item = node.children(".admin_et_item");
                    var flags = "," + item.find("[data-fname=flags]").val() + ",";         
                    if (flags.indexOf(",es,") == -1)
                        item.find(".admin_eti_menu > a.admin_link.edit").hide();
                }
            });   
        } 
    }
})( jQuery );        

