(function( $ ){  
    $.catalog = {
        timeline: {
            sequence: {
                sale_start:  { title: 'Sale Start', price: true},
                sale_stage:  { title: 'Sale Stage', price: true, multiple: true},
                event_start: { title: 'Event Start' },
                event_end:   { title: 'Event End' },
            },     
            format_event: function(src, event, time) {   
                var desc = event.desc;
                if (desc)
                    title = "<span>" + desc + "</span>"; else
                    title = "<span>" + $.catalog.timeline.sequence[event.type].title + "</span>";
                    
                title+= "&nbsp;<span>" + dateFormat(time, "d.m") + "</span>";
                if (event.price)
                    title+= "&nbsp;<span style='color: darkgreen'>" + event.price + "€</span>"; 
                    
                var p = event.properties;
                if (p) {
                    title+= "&nbsp;";
                    var s = p.split(",");
                    for (var i = 0; i < s.length; i++) 
                        title+= "<span class='admin_flag gray'>" + s[i].trim() + "</span>";    
                }
                
                var name = src.attr("data-name");
                if (!name) name = "timeline";
                
                var price = event.price; 
                var comm = event.commission;
                var prop = event.properties;
                
                if (!price) price = '';
                if (!comm) comm = '';
                if (!prop) prop = '';  
                if (!desc) desc = '';
                
                title+= "<input type='hidden' name='" + name + "[" + event.index + "][type]' value='" + event.type + "'>"; 
                title+= "<input type='hidden' name='" + name + "[" + event.index + "][desc]' value='" + event.desc + "'>"; 
                title+= "<input type='hidden' name='" + name + "[" + event.index + "][date]' value='" + event.date + "'>";
                title+= "<input type='hidden' name='" + name + "[" + event.index + "][price]' value='" + price + "'>";
                title+= "<input type='hidden' name='" + name + "[" + event.index + "][commission]' value='" + comm + "'>";
                title+= "<input type='hidden' name='" + name + "[" + event.index + "][properties]' value='" + prop + "'>";
                return title;     
            },
            event_edit: function(event, ops) {
                var tl = $(this).closest(".axis");
                var prev = (event.index > 0) ? ops.events[event.index-1] : null;
                $.overlay_ajax(doc_base + "AdminCatalog/Ajax_EditTimeline", false, {
                    prices: tl.attr("data-prices"),
                    curr: event,
                    prev: prev   
                }, {
                    scrollable: true,
                    axis: tl,
                    target: event,
                    onLoaded: function() {
                        $(this).ac_create();
                    },
                    onClose: function(r, ops) {
                        if (!r) return;
                        
                        var ops = $(this).data("overlay");                    
                        ops.target.desc = $(this).find("[name=desc]").val(); 
                        ops.target.price = $(this).find("[name=price]").val();
                        ops.target.commission = $(this).find("[name=commission]").val();
                        ops.target.properties = $(this).find("[name=properties]").val();
                        
                        ops.axis.axis("update");
                    }    
                });
            },
        },
        property_editor: {
            update: function() {
                var value = [];
                $(this).find("table > tbody > tr").each(function() {
                    value.push($(this).attr("data-pid") + "." + $(this).attr("data-vid"));
                });
                
                value = value.join(",");
                $(this).children("input").val(value);
            },
            edit: function() {
                var row = $(this).closest("tr");
                
                $.catalog.property_editor.add.apply(this, [row]);
            },
            add: function(row) {
                var editor = $(this).closest(".admin_catalog_property_editor");

                var exclude = [];
                editor.find("table > tbody > tr").each(function() {
                    exclude.push($(this).attr("data-pid"));
                });
                
                var pid = (row) ? row.attr("data-pid") : undefined;                
                var vid = (row) ? row.attr("data-vid") : undefined;                
                
                $.overlay_ajax(doc_base + "AdminCatalog/Ajax_PropertyBrowser", "ajax", { exclude: exclude, pid: pid, vid: vid }, { 
                    target: editor,             
                    target_row: row,
                    onDisplay: function() {
                        $(this).ac_create();
                        $(this).find("[name=property]").change(function() {
                            var overlay = $(this).closest(".overlay_popup");
                            $.post(doc_base + "AdminCatalog/Ajax_PropertyBrowser", { property_change: $(this).val() }, function(r) {
                                overlay.find(".admin_property_select_value").html(r).ac_create();
                            });
                        });
                    },                                           
                    onClose: function(r, ops) {
                        if (!r) return;
                        
                        var ops = $(this).data("overlay");
                        
                        var html = '<tr data-pid="' + r.property_id + '" data-vid="' + r.value_id + '">';
                        html+= '<td>' + r.property_title + '</td>';
                        html+= '<td><a onclick="$.catalog.property_editor.edit.apply(this)">' + r.value_title + "</a></td>";
                        html+= '<td style="text-align: right"><a class="admin_button_del small" onclick="$.catalog.property_editor.rem.apply(this)">' + trans_admin_delete + '</a></td>';
                        
                        if (ops.target_row)
                            ops.target_row.replaceWith(html); else
                            ops.target.children("table").append(html);
                            
                        $.catalog.property_editor.update.apply(ops.target);    
                    }        
                });
            },
            rem: function() {
                var editor = $(this).closest(".admin_catalog_property_editor");    
                $(this).closest("tr").remove();
                
                $.catalog.property_editor.update.apply(editor);    
            }            
        },
        discount_list: function(type, id) {
            $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Discounts/' + type + '/' + id, false, {}, {
                min_height: 152,
                width: 0.6,
                height: 0.9,
                scrollable: true,
                close_outside: true,
                onClose: function(r) {
                    if (r) { 
                        $.overlay_loader();
                        window.location.href = r;
                    }
                }
            });         
            return false;    
        }
    }
    $.fn.catalog_timeline = function(events) {
        if ($.isArray(events) && events.length) {
            start = events[0].date;
            end = events[events.length-1].date;
            
            start = $.timeline.date2time(start);
            end = $.timeline.date2time(end);
        } else {
            start = new Date().getTime() / 1000;
            end = start;
        }
            
        if (end - start < 30*24*60*60)
            end = start + 30*24*60*60;
            
        start = dateFormat((start - 24*60*60) * 1000, "dd.mm.yyyy");
        end = dateFormat((end + 10*24*60*60) * 1000, "dd.mm.yyyy");
        
        $(this).timeline({
            start: start,
            end: end,
            events: events,
            sequence: $.catalog.timeline.sequence,
            format_event: $.catalog.timeline.format_event,
            onEventEdit: $.catalog.timeline.event_edit,
            onEventAdd: $.catalog.timeline.event_edit,
            onEventRemove: function() {  },                      
            onEventSelect: function(event) {  },                      
            onEventDeselect: function() { },                      
        });        
    }
    $.fn.property_editor = function() {
        
    }
    $.fn.catalog_browser = function(param, callback) {
        var ops = {
            height: 0.8,
            width: 0.7,
            target: $(this),
            callback: callback,
            close_outside: true,
            onDisplay: function(ops) {
                catalog_browser_init.apply(this, [ops.callback]);
            },
            onResize: function() {
                $(".catalog_browser", this).datagrid("update");
            },
            onClose: function(r) {
                if (!r) return;
                
                var ops = $(this).data("overlay");
                if (typeof ops.callback == "function")
                    ops.callback.apply(ops.target, [r, $(this), ops]);
            }    
        }
        $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Browser', "ajax", { param: param }, ops);     
    }
})( jQuery );       