(function( $ ){   
	$.image_frame = {
		options: {
		},
        panning: null,
        zooming: null,
        panning: null,
        global_events: false,
        contain: function(w,h, ratio) {
            var cw = w;
            var ch = cw * ratio;
            if (ch > h) {
                ch = h;
                cw = ch / ratio;    
            }        
            
            return [cw,ch];
        },
        cover: function(w,h, ratio) {
            var cw = w;
            var ch = cw * ratio;
            if (ch < h) {
                ch = h;
                cw = ch / ratio;    
            }        
            
            return [cw,ch];
        },
        clip: function(v, min, max) {
            if (min > max) {
                var t = min;
                min = max;
                max = t;    
            }
            
            if (v < min) v = min;
            if (v > max) v = max;
            
            return v;  
        },
        zoom: function(scale, orig_x, orig_y) {
            var old_scale = parseFloat($(this).attr("data-scale"));
            var pan = $(this).attr("data-pan").split(",");

            if (scale < 1)
                scale = 1;
            
            var img_x = orig_x - parseFloat(pan[0]);
            var img_y = orig_y - parseFloat(pan[1]);
            
            var pan_x = orig_x - scale / old_scale * img_x;
            var pan_y = orig_y - scale / old_scale * img_y;
            
            $.image_frame.update.apply(this, [scale, [pan_x, pan_y]]);
        },
        update: function(scale, pan) {
            if (typeof scale == "undefined" || scale === null)
                scale = parseFloat($(this).attr("data-scale"));
            if (typeof pan == "undefined" || pan === null)
                pan = $(this).attr("data-pan").split(",");
                
            var cont_w = $(this).width();
            var cont_h = $(this).height();
            
            var img = $(this).children("img");
            var img_w = 1024;//img[0].naturalWidth;
            var img_h = 768;//img[0].naturalHeight;
            
            var size = $.image_frame.contain(cont_w, cont_h, img_h / img_w); 
            if (scale < 0) scale = 1;
            
            //var scale = Math.exp(0.1 * zoom);
            size[0] = size[0] * scale;
            size[1] = size[1] * scale;
            
            var delta_x = size[0] - cont_w;
            var delta_y = size[1] - cont_h;
            var pos_x = parseFloat(pan[0]);
            var pos_y = parseFloat(pan[1]);
            
            if (delta_x > 0) 
                pos_x = $.image_frame.clip(pos_x, -delta_x, 0); else
                pos_x = -delta_x / 2;
                
            if (delta_y > 0) 
                pos_y = $.image_frame.clip(pos_y, -delta_y, 0); else
                pos_y = -delta_y / 2;
            
            img.css({
                width: size[0] + "px", 
                height: size[1] + "px", 
                left: pos_x + "px", 
                top: pos_y + "px"
            });
            
            $(this).attr("data-scale", scale);
            $(this).attr("data-pan", pos_x + "," + pos_y);
        }
    }
	$.fn.image_frame = function(options) {
        var ops = $.extend({}, $.image_frame.options, options);
        
        var img = $(this).append("<img src='" + ops.src + "' style='position: absolute; z-index: 1'>"); 
        var blur = $("<div style='position: absolute; top: -5px; bottom: -5px; left: -5px; right: -5px'></div>").appendTo($(this));
        blur.css({
            "background-image": "url('" + ops.src + "')",
            "filter" : "blur(5px)",
            "background-size" : "cover",
            "opacity" : 0.7
        });
        
        $(this).addClass("image_frame");
        $(this).attr("data-scale", 1);
        $(this).attr("data-pan", "0,0");
        $.image_frame.update.apply($(this));
        $(this).mousewheel(function(e) {
            var ofs = $(this).offset();
            var scale = parseFloat($(this).attr("data-scale")) + e.deltaY * 0.2;
            $.image_frame.zoom.apply(this, [scale, e.pageX - ofs.left, e.pageY - ofs.top]);
            
            return false; 
        }).mousedown(function(e) {
            $.image_frame.panning = {
                target: $(this),
                prevX: e.pageX,
                prevY: e.pageY,
            };    
        });
        var hammer= new Hammer($(this)[0], { domEvents: true });
        hammer.get('pinch').set({ enable: true });
        hammer.on('panstart', function(ev) {
            var cont = $(ev.target).closest(".image_frame");
            $.image_frame.panning = {
                orig: cont.attr("data-pan").split(",")     
            }
        });
        hammer.on('pan', function(ev) {
            var cont = $(ev.target).closest(".image_frame");
            var orig = $.image_frame.panning.orig;
            var pan = [
                parseFloat(orig[0]) + ev.deltaX,
                parseFloat(orig[1]) + ev.deltaY
            ];
            $.image_frame.update.apply(cont, [null, pan]);        
        });     
        hammer.on('panend', function(ev) {
            $.image_frame.panning = null;
        });

        hammer.on('pinchstart', function(ev) {
            var cont = $(ev.target).closest(".image_frame");
            $.image_frame.zooming = {
                scale: parseFloat(cont.attr("data-scale"))   
            }
        });
        hammer.on('pinchend', function(ev) {
            $.image_frame.zooming = null;
        });
        hammer.on('pinch', function(ev) {
            var cont = $(ev.target).closest(".image_frame");
            var ofs = cont.offset();
            
            var scale = ev.scale * $.image_frame.zooming.scale;
            $.image_frame.zoom.apply(cont, [scale, ev.center.x - ofs.left, ev.center.y - ofs.top]);
        });
        
        img.bind("dragstart", function() { return false });
        
        if (!$.image_frame.global_events) {
            $(document).mousemove(function(e) {
                if (!$.image_frame.panning) return;

                var panning = $.image_frame.panning;
                var dx = e.pageX - panning.prevX;
                var dy = e.pageY - panning.prevY;
                
                var pan = panning.target.attr("data-pan").split(",");
                pan[0] = parseInt(pan[0]) + dx;
                pan[1] = parseInt(pan[1]) + dy;
                
                $.image_frame.update.apply(panning.target, [null, pan]);
                
                panning.prevX = e.pageX;
                panning.prevY = e.pageY;
                
                e.stopPropagation();
                e.preventDefault();
                return false;
            }).mouseup(function(e) {
                $.image_frame.panning = null;
                
                e.stopPropagation();
                e.preventDefault();
                return false;
            });
            
            $.image_frame.global_events = true;    
        }
	}
})( jQuery );        


