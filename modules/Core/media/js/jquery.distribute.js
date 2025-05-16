(function( $ ){   
    $.distribute = {
        def_options: {
            width: 100,
            spacing: 0,
            adjust_heights: false,
            center: false,
            class_first: "first",
            class_last: "last",
            child_selector: "li"
        },
        elements: [],
        global_events: false,           
        update: function(src, force) {
            if (!src.length || !src.is(":visible")) return;
            
            var ops = src.data("distribute");
            
            var item_width = ops.width;     
            var item_spacing = ops.spacing;
        
            src.css("width", "auto");
            var width = src.width();
            var sp, ch, chs = src.children(ops.child_selector);
            var cnt = chs.length;
            if (cnt == 0) return;
            
            var pw = cnt * item_width + (cnt-1)*item_spacing;
            if (pw < width) {
                if (ops.center) {
                    src.css({
                        "width" : pw + "px",
                        "margin-left" : "auto",
                        "margin-right" : "auto",
                    });
                }
                if (ops._count == "static") return;
                
                for (var i = 0; i < chs.length; i++) {
                    ch = $(chs[i]);
                    sp = (i == cnt-1) ? 0 : item_spacing;
                    ch.css({
                        "width" : item_width + "px",
                        "margin-right" : sp + "px",
                        "max-width" : "100%"
                    });
                }
                
                ops._count = "static";
                return;
            } 
            
            cnt = parseInt((width + item_spacing) / (item_width + item_spacing));
            
            var t = null;
            var i1 = cnt-1;
            var i2 = cnt+1;
            if (i1 < 1) i1 = 1;    
        
            var ct;
            for (var i = i1; i <= i2; i++) {
                ct = width / (item_width * i + item_spacing * (i-1));
                    
                if (t === null || Math.abs(ct-1) < Math.abs(t-1)) {
                    t = ct;
                    cnt = i;
                }
            }            
            var vs = t * item_spacing;
            
            if (ops._count == cnt && !force) {
                var li;
                for (var i = 0; i < chs.length; i++) {
                    li = $(chs[i]);
                    if (li.hasClass(ops.class_last))
                        li.css("margin-bottom", 0); else
                        li.css("margin-bottom", vs + "px");
                }
                return;
            }
            
            ops._count = cnt;
                 
            cw = t * item_width / width;
            cs = t * item_spacing / width;  
            
            var row_h = [], max_h = null, itm_h;
            var mod;
            
            var c = chs.length / cnt;
            var row_c = parseInt(c);
            if (row_c - c) row_c++;
            var last_t = (row_c-1) * cnt;

            for (var i = 0; i < chs.length; i++) {
                ch = $(chs[i]);
                mod = (i % cnt);
                sp = (mod == cnt-1) ? 0 : cs;
                
                     
                ch.css({
                    "height": "auto",
                    "width": 100 * cw + "%",
                    "margin-right": 100 * sp + "%",
                    "margin-bottom": (i < last_t) ? vs + "px" : 0,
                });  
                ch.removeClass(ops.class_first).removeClass(ops.class_last);
                if (i < cnt)
                    ch.addClass(ops.class_first); 
                if (i >= last_t) 
                    ch.addClass(ops.class_last);
                    
                if (ops.adjust_heights) {
                    itm_h = ch.outerHeight();
                    if (max_h === null || itm_h > max_h)
                        max_h = itm_h;
                    if (mod == cnt-1) {
                        row_h[parseInt(i / cnt)] = max_h;
                        max_h = null;    
                    }
                }
            }     
            
            if (ops.adjust_heights) {
                var row;
                for (var i = 0; i < chs.length; i++) {
                    row = parseInt(i / cnt);
                    $(chs[i]).css("height", row_h[row] + "px");
                }    
            }
        }    
    }
    $.fn.distribute = function(options, arg) {
        if (options == "update") {
            $.distribute.update($(this), true);
            return $(this);    
        }
        var ops = $.extend({}, $.distribute.def_options, options);
        
        if (ops.width) ops.width = parseInt(ops.width);
        if (ops.spacing) ops.spacing = parseInt(ops.spacing);
        
        $(this).data("distribute", ops);
        $.distribute.elements.push($(this));
        
        $(this).css({
            "list-style" : "none outside none",
            "padding" : 0,
            "margin" : 0 
        });
        $(this).children("li").css({"float" : "left"});
        
        if (!$.distribute.global_events) {
            $(window).resize(function() {
                for (var i = 0; i < $.distribute.elements.length; i++) 
                    $.distribute.update($($.distribute.elements[i]));
            }); 
            
            $.distribute.global_events = true;    
        }
        $.distribute.update($(this));
    }
})( jQuery );        




