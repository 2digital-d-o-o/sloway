(function( $ ){  
     $.isArray = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object Array]');
    }
    $.isString = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object String]');
    }
    $.contextmenu = {
        def_options: {
            cls: "",
            attr: "",
            icons: true,
            zindex: 10000,
            onClick: null,
        },
        build: function(items, ops, level) {
            var cls,item,attr,name,typ,html = "";
            
            for (var i = 0; i < items.length; i++) {
                item = items[i];
                
                if (item.separator) {
                    html+= "<li class='cm_separator'></li>";
                    continue;
                }
                
                cls = "";                            
                if ($.isArray(item.items) && item.items.length)
                    cls = 'cm_parent';
                if (typeof item.cls != "undefined")
                    cls+= " " + item.cls;
                    
                attr = item.attr;
                if (typeof attr == "undefined")
                    attr = "";
                    
                if (item.check) {
                    cls+= " cm_item_check";
                    if (item.checked)
                        cls+= " cm_item_checked";
                }
                    
                if (item.name) 
                    attr+= " data-name='" + item.name + "'";
                
                html+= "<li class='cm_item " + cls + "'" + attr + ">";
                if (item.check)
                    html+= "<div class='cm_item_icon'></div>"; else
                if (ops.icons) {
                    var style = "";
                    if (typeof icon != "undefined")
                        style = "style='background-image: url(" + item.icon + ")'";    
                    html+= "<div class='cm_item_icon' " + style + "></div>"; 
                }
                html+= item.content;
                
                if ($.isArray(item.items) && item.items.length) {
                    var z = ops.zindex + level*10;
                    html+= "<ul style='z-index: " + z + "'>" + $.contextmenu.build(item.items, ops, level+1) + "</ul>";
                }
                
                html+= "</li>";
            }    
            
            return html;
        },
        global_events: false
    };
    $.contextmenu_close = function() {
        $("<div id='cm_list'></div>").remove();
    }
    $.fn.contextmenu = function(x, y, items, ops) {
        var ops = $.extend({}, $.contextmenu.def_options, ops);
        ops.x = x;
        ops.y = y;
        
        var html = "<ul class='contextmenu' style='z-index: " + ops.zindex + "'>";
        html+= $.contextmenu.build(items, ops, 0);
        html+= "</ul>";
        
        var attr = ops.attr;
        if (ops.cls)
            attr+= " class='" + ops.cls + "'";
        
        var cont = $("#cm_list");
        if (!cont.length)
            cont = $("<div id='cm_list'" + attr + "></div>").prependTo("body");
        
        var menu = $(html).appendTo(cont);
        if (ops.icons)
            menu.addClass("cm_icons");
        
        ops.target = $(this);
        menu.data("contextmenu", ops);
        menu.mousedown(function(e) {
            e.stopPropagation();
            return false;
        });
        menu.find("li").click(function(e) {
            var cm = $(this).parents(".contextmenu:first");
            var ops = cm.data("contextmenu");
            var name = $(this).attr("data-name");
            var close = true;
            
            if ($(this).is(".cm_item_check")) {
                var chk = $(this).is(".cm_item_checked");
                if (chk)
                    $(this).removeClass("cm_item_checked"); else
                    $(this).addClass("cm_item_checked");                
                
                close = false;
                
                if (typeof ops.onCheck == "function") 
                    ops.onCheck.apply(this, [!chk, ops.target, name]);
                if (typeof ops.onChange == "function") {
                    var checked = [];
                    cm.find("li.cm_item_checked").each(function() {
                        checked.push($(this).attr("data-name")); 
                    });
                    
                    ops.onChange.apply(cm, [checked, ops.target]);
                }
            } else 
            if (typeof ops.onClick == "function") {
                close = ops.onClick.apply(this, [ops.target, name, ops.x, ops.y]);  
                
                if (typeof close == "undefined")
                    close = true;
            }
            
            if (close) {
                if (typeof ops.onClose == "function")
                    ops.onClose.apply(cm);
                    
                cm.remove();
            }
            
            e.stopPropagation();
            //return false;
        });
        menu.find("li.cm_parent").mouseenter(function() {
            var parent = $(this).parents("ul:first");
            parent.find("ul").hide();
            
            var pos = $(this).offset();
            var x = pos.left - $(document).scrollLeft() + $(this).outerWidth();
            var y = pos.top - $(document).scrollTop() - 3;
            
            var ul = $(this).children("ul").show();
            var ul_w = ul.outerWidth();
            var ul_h = ul.outerHeight();
            var win_w = $(window).width();
            var win_h = $(window).height();
            
            if (x + ul_w > win_w) 
                x = pos.left - $(document).scrollLeft() - ul_w;    
                
            if (y + ul_h > win_h) y = win_h - ul_h;
            
            ul.css({left: x + "px", top: y + "px"});
        });        

        var w = menu.outerWidth() + 5;
        var h = menu.outerHeight() + 5;
        var win_w = $(window).width();
        var win_h = $(window).height();
        
        if (x + w > win_w) x = win_w - w;
        if (y + h > win_h) y = win_h - h;
        
        menu.css({"left" : x, "top" : y});
        
        if (!$.contextmenu.global_events) {
            $(document).mousedown(function() {
                $("#cm_list .contextmenu").each(function() {
                    var ops = $(this).data("contextmenu");
                    if (ops && typeof ops.onClose == "function")
                        ops.onClose.apply(this);
                    $(this).remove();
                });
            });    
            
            $.contextmenu.global_events = true;
        }
        
        if (typeof ops.onReady == "function")
            ops.onReady.apply(menu, [this]);
        
        return menu;
    };
})( jQuery );        


