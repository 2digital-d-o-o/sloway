(function( $ ){   
    $.floatbar = {
        def_options: {
            top: 0,
            bottom: 0,
            height: 0,
        },
        elements: [],
        global_events: false,
                    
        update: function() {
            var element;
            for (var i = 0; i < $.floatbar.elements.length; i++) {
                element = $($.floatbar.elements[i]);
                if (!element.length) continue;
                
                $.floatbar.update_element(element);
            }
        },
        observer: function() {
            var elem, cont, ops, w, h;
            for (var i = 0; i < $.floatbar.elements.length; i++) {
                elem = $.floatbar.elements[i];
                if (!elem) continue;
                
                if (elem.hasClass("floatbar_disabled")) continue;
                
                ops = elem.data("floatbar");
                if (!ops) continue;
                
                cont = elem.children("div");
                w = cont.width();
                h = cont.height();
                if (w != ops._width || h != ops._height) 
                    $.floatbar.update_element(elem);   
                
                ops._width = w;
                ops._height = h;
            }      
            
            setTimeout($.floatbar.observer, 500);          
        },
        update_element: function(bar) {
            if (bar.hasClass("floatbar_disabled")) return;
            
            var ops = bar.data("floatbar");
            if (!ops) return;
            var ofs = bar.offset();
            
            var height = ops.height;
            if (typeof height == "function")
                height = height.apply(bar, [ops]);
                
            var cont = bar.children("div");
            var cont_h = cont.height();
            if (height < cont_h)
                height = cont_h;            
            
            bar.css("height", height + "px");
            var left = bar.offset().left;
            var width = bar.width();
            
            var top = ofs.top - $(window).scrollTop();
            var min = ops.top;
            if (typeof min == "function")
                min = min.apply(bar, [ops]); 
            
            var ch = cont.height(); 
            var bh = bar.height();
            
            var mt = -top + min;
            var max_mt = bh - ch;
            
            /*
            if (mt > max_mt) mt = max_mt;
            if (top < min) {
                bar.children("div").css({
                    position: "absolute",
                    left: 0,
                    right: 0,
                    top: mt + "px",
                });    
            } else {
                bar.children("div").css({
                    position: "static",
                    top: 0
                });    
            } */
            
            if (mt > max_mt) {
                bar.children("div").css({
                    "margin-top" : max_mt + "px",  
                    "width" : width + "px",
                    "position" : "static"
                }); 
            } else         
            if (top < min) {
                bar.children("div").css({
                    "margin-top" : 0,
                    "left" : left + "px",
                    "width" : width + "px",
                    "top" : min + "px",
                    "position" : "fixed"
                }); 
            } else
                bar.children("div").css({
                    "margin-top" : 0,
                    "width" : width + "px",
                    "position" : "static",
                    "width" : "auto", 
                });      
        }
    }
    $.fn.float_bar = function(options) {
        if (options == "disable") {
            if (!$(this).hasClass("float_bar")) return $(this);
            
            $(this).css("height", "auto");
            $(this).children(".floatbar_cont").removeAttr("style");
            $(this).addClass("floatbar_disabled"); 
            
            return $(this);
        } 
        if (options == "enable") {
            if (!$(this).hasClass("float_bar")) return $(this);
            
            $(this).removeClass("floatbar_disabled");
            $.floatbar.update_element($(this));
            
            return $(this);
        }
        
        var ops = $.extend({}, $.floatbar.def_options, options);
        
        $(this).addClass("float_bar");
        var cont = $(this).children("div").addClass("floatbar_cont");

        $(this).data("floatbar", ops);
        
        ops._width = cont.width();
        ops._height = cont.height();
                                           
        $.floatbar.elements.push($(this));
        if (!$.floatbar.global_events) {
            $(window).scroll($.floatbar.update).resize($.floatbar.update);
            $.floatbar.global_events = true;
        }
        
        setTimeout($.floatbar.observer, 500);
        
        if ($.floatbar.scroll_curr === null)
            $.floatbar.scroll_curr = $(window).scrollTop();
        
        $.floatbar.update();
    }
})( jQuery );        