(function( $ ){   
    $.isArray = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object Array]');
    }
    $.isString = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object String]');
    }    
    $.toRange = function(v, min, max) {
        if (v === null) return null;
        
        if (min !== null && v < min) v = min;
        if (max !== null && v > max) v = max;
            
        return v;  
    },    
	$.resizer = {
		def_options: {
            size_const: false
		},

        global_events: false,
        curr_handle: null,
        update: function(resizer, ops) {
            var pos = ops.target.offset();
            var pt = parseInt(ops.target.css("padding-top"));    
            var pb = parseInt(ops.target.css("padding-bottom"));    
            var pl = parseInt(ops.target.css("padding-left"));    
            var pr = parseInt(ops.target.css("padding-right"));   
            
            resizer.css({
                "top" : pos.top,
                "left" : pos.left,
                "width" : ops.target.width() + pl + pr,
                "height" : ops.target.height() + pt + pb
            });                                            
        },

        ensure_ratio: function(r, ratio) {
            var x1 = r[1] * ratio;
            
            if (x1 > r[0]) 
                return [x1, r[1]]; else                                
                return [r[0], r[0] / ratio];
        },
        apply_size: function(handle, resizer, ops) {
            var pt = parseInt(ops.target.css("padding-top"));    
            var pb = parseInt(ops.target.css("padding-bottom"));    
            var pl = parseInt(ops.target.css("padding-left"));    
            var pr = parseInt(ops.target.css("padding-right")); 
            
            if (ops.size_const) {
                ops.target.css({"width" : resizer.width() - pl - pr, "height" : "auto"});
                ops.target.css({"height" : ops.target.outerHeight()});
            } else 
            ops.target.css({
                width: resizer.width() - pl - pr,
                height: resizer.height() - pt - pb
            });    
        },
        create: function(options) {
            var ops = $.extend({}, $.resizer.def_options, options);
            var cont = $("#resizer_cont");
            if (!cont.length) 
                cont = $("<div id='resizer_cont'></div>").prependTo("body");    
                
            var html = "<div class='resizer'>";
            html+= "<div class='resizer_handle resizer_top resizer_left resizer_corner'></div>";
            html+= "<div class='resizer_handle resizer_top'></div>";
            html+= "<div class='resizer_handle resizer_top resizer_right resizer_corner'></div>";
            
            html+= "<div class='resizer_handle resizer_left'></div>";
            html+= "<div class='resizer_handle resizer_right'></div>";
            
            html+= "<div class='resizer_handle resizer_bottom resizer_left resizer_corner'></div>";
            html+= "<div class='resizer_handle resizer_bottom'></div>";
            html+= "<div class='resizer_handle resizer_bottom resizer_right resizer_corner'></div>";
            
            html+= "</div>";
            
            var resizer = $(html).appendTo(cont);
            
            resizer.find(".resizer_handle").mousedown(function(e) {
                $.resizer.curr_handle = $(this);     
                
                e.stopPropagation();
                return false;
            });
            ops.target = $(this);         
            ops.aspect_ratio = $(this).outerWidth() / $(this).outerHeight();
            resizer.data("resizer", ops);

            $.resizer.update(resizer, ops);
            
            if (!$.resizer.global_events) {
                $(document).mousemove(function(e) {
                    if (!$.resizer.curr_handle) return;
                    
                    var handle = $.resizer.curr_handle;
                    var resizer = handle.parents(".resizer:first");

                    var x = e.pageX;
                    var y = e.pageY;
                    
                    var pos = ops.target.offset();
                    var x1 = pos.left + parseInt(ops.target.css("padding-left"));
                    var y1 = pos.top + parseInt(ops.target.css("padding-top"));
                    var x2 = x1 + ops.target.width();
                    var y2 = y1 + ops.target.height();
                    
                    var sx1 = parseInt(resizer.css("left"));
                    var sy1 = parseInt(resizer.css("top"));
                    var sx2 = sx1 + resizer.width();
                    var sy2 = sy1 + resizer.height();
                    
                    if (handle.is(".resizer_left")) sx1 = x;
                    if (handle.is(".resizer_top")) sy1 = y;
                    if (handle.is(".resizer_right")) sx2 = x;
                    if (handle.is(".resizer_bottom")) sy2 = y;
                    
                    if (handle.is(".resizer_corner")) {
                        size = $.resizer.ensure_ratio([sx2-sx1, sy2-sy1], ops.aspect_ratio);
                        
                        sx2 = sx1 + size[0];               
                        sy2 = sy1 + size[1];
                    } else 
                    if (ops.size_const) {
                        if (handle.is(".resizer_top") || handle.is(".resizer_bottom"))    
                            sx2 = sx1 + (sy2 - sy1) * ops.aspect_ratio; else
                            sy2 = sy1 + (sx2 - sx1) / ops.aspect_ratio;
                    }
                        
                    resizer.css({
                        left: sx1,
                        top: sy1,
                        width: sx2 - sx1,
                        height: sy2 - sy1
                    });
                }).mouseup(function(e) {
                    if (!$.resizer.curr_handle) return;
                    
                    var handle = $.resizer.curr_handle;
                    var resizer = handle.parents(".resizer:first");
                    var ops = resizer.data("resizer");
                    
                    $.resizer.apply_size(handle, resizer, ops); 
                    $.resizer.update(resizer, ops);
                    $.resizer.curr_handle = null;
                    
                    e.stopPropagation();
                    return false; 
                });
                    
                $.resizer.global_events = true;
            }
            
            return resizer;
        }
    }

    $.fn.resizer = function(ops) {
        return $.resizer.create.apply(this, [ops]); 
    }
})( jQuery );        


