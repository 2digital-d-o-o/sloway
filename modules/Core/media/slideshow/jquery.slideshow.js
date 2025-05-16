(function( $ ){   
    $.isObject = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object Object]');
    }     
    $.slideshow = {
        def_options: {
            height: 0,
            pager_selector: null,
            aspect_ratio: 0,
            max_height: 0,
            z_index: 0,
            min_height: 0,
            anim_type: "fade",
            anim_duration: 1000,
            calc_height: true,
            interval: 0,
            layered: false,
            observer: true,
            pager_active_class: "active",
            onUpdate: null,
            onChange: null,
            onLoaded: null,
            onChangeBegin: null,
            onChangeEnd: null,
        },
        
        global_events: false,   
        instances: [],
        observing: false,
        
        scroll_active: false, 
        scroll_timeout: null,
        layout: function() {
            var ops = $(this).data("slideshow");
            
            if (!$(this).is(":visible")) return;
            
            if (typeof ops.onUpdate == "function")
                ops.onUpdate.apply(this, [ops]);
            
            if (!ops.calc_height) return;
            
            var ratio = ops.aspect_ratio;
            if (typeof ratio === "function")
                ratio = ratio.apply(this);
            if (ratio == "screen") 
                ratio = $(window).height() / $(window).width();
            
            var h = $(this).width() * ratio;
            if (ops.max_height && h > ops.max_height)
                h = ops.max_height;
            if (ops.min_height && h < ops.min_height)
                h = ops.min_height;
                
            if (h)
                $(this).css("height", h);
        },
        update_queue: function(slider, ops) {
            var li = slider.children("li");
            var z = ops.z_index + li.length;
            
            for (var i = 0; i < li.length; i++) 
                $(li[i]).css("z-index", z-i);    
        },
        animation_end: function(data, callback) {
            var slider = $(this).parents(".slideshow"); 
            var ops = slider.data("slideshow");

            $(this).addClass("active");
            
            var li,ch = slider.children("li");
            for (var i = 0; i < ch.length; i++) {
                li = $(ch[i]);
                if (li.is(".active")) break;
                
                li.detach().appendTo(slider);
            } 
            
            $.slideshow.update_queue(slider, ops);
            
            if (typeof ops.onChange == "function")
                ops.onChange.apply(slider, [slider.children("li:first"), data]);
                
            if (typeof ops.onChangeEnd == "function")
                ops.onChangeEnd.apply(slider, [slider.children("li:first"), data]);   
                
            if (typeof callback == "function")
                callback.apply(slider, [slider.children("li:first"), data]);            
                
            $(this).addClass("front");
            
            if (ops.interval) 
                ops._timeout = setTimeout(function() {
                    $.slideshow.next_slide.apply(slider);    
                }, ops.interval);
            
            slider.removeClass("fading");
        },
        toggle_slide: function(index, data, dir, callback) {
            if ($(this).is(".fading")) return;
            
            var ops = $(this).data("slideshow");  
            var ch = $(this).children("li");
            if (index < 0 || index > ch.length-1) return;
            
            var curr = ch.filter("li:first");
            var curr_index = parseInt(curr.attr("data-index"));
            if (curr_index == index) return;
            
            $(this).addClass("fading");
            ch.addClass("back");
            
            if (typeof dir == "undefined") 
                dir = (index > curr_index) ? "next" : "prev";
            
            curr.removeClass("active front");
            var next = ch.filter("li[data-index=" + index + "]").removeClass("back");

            if (typeof ops.onChangeBegin == "function")
                ops.onChangeBegin.apply(this, [curr, next, data]);            
            
            if (ops.anim_type == "slide") {
                var z = ops.z_index + ch.length;                
                var l = (dir == "next") ? "100%" : "-100%";
                next.show().css({"left" : l, "z-index" : z}).animate({"left" : 0}, ops.anim_duration, "swing", function() {
                    $.slideshow.animation_end.apply(this, [data, callback]);
                });
            } else {
                var z = ops.z_index + ch.length;   
                next.css({"z-index" : z}).fadeIn(ops.anim_duration, function() {
                    $.slideshow.animation_end.apply(this, [data, callback]);
                });
                curr.fadeOut(ops.anim_duration);
            }
            
            clearTimeout(ops._timeout);
            
            if (ops.pager_selector) {
                var curr_sel = ops.pager_selector.replace("%INDEX%", curr.attr("data-index"));
                var next_sel = ops.pager_selector.replace("%INDEX%", index);    
                
                $(curr_sel).removeClass(ops.pager_active_class);
                $(next_sel).addClass(ops.pager_active_class);
            }
        },
        next_slide: function(data, callback) {
            var li = $(this).children("li");  
            var ind = parseInt($(li[0]).attr("data-index"));
            
            ind = (ind + 1) % li.length;
            
            $.slideshow.toggle_slide.apply(this, [ind, data, "next", callback]);
        },
        prev_slide: function(data, callback) {
            var li = $(this).children("li");  
            var ind = parseInt($(li[0]).attr("data-index")) - 1;
            
            if (ind < 0)
                ind = li.length - 1;            
            
            $.slideshow.toggle_slide.apply(this, [ind, data, "prev", callback]);
        },
        observer: function() {
            var instance, ops;
            var count = 0;
            for (var i = 0; i < $.slideshow.instances.length; i++) {
                instance = $.slideshow.instances[i];
                if (!instance) {
                    delete $.slideshow.instances[i];
                    continue;
                }
                
                ops = instance.data("slideshow");
                if (!ops) {
                    delete $.slideshow.instances[i];
                    continue;    
                }
                
                count++;
                
                if (!instance.is(":visible")) continue;
                
                var inst_w = instance.width();
                var inst_h = instance.height();
                var force = (typeof ops.needsUpdate == "function") && ops.needsUpdate.apply(instance, [ops]);
                
                if (inst_w != ops._width || inst_h != ops._height || force) {
                    ops._width = inst_w;
                    ops._height = inst_h;  
                    
                    $.slideshow.layout.apply(instance); 
                }
            }    
            
             if (count) 
                $.slideshow.observing = setTimeout($.slideshow.observer, 500);
        },        
        create: function(options) {
            if ($(this).is(".slideshow")) 
                return this;
            
            var ops = $.extend({}, $.slideshow.def_options, options);
            if (ops.height) ops.height = parseInt(ops.height);
            if (ops.max_height) ops.max_height = parseInt(ops.max_height);
            if (ops.min_height) ops.min_height = parseInt(ops.min_height);
            if (ops.aspect_ratio) ops.aspect_ratio = parseFloat(ops.aspect_ratio);
            
            
            /*                 
            if (!ops.aspect_ratio)
                ops.aspect_ratio = ops.height / $(this).width();
            
            if (!ops.aspect_ratio)
                return $(this);            
            */
            
            $(this).addClass("slideshow").data("slideshow", ops);
            
            var ch = $(this).children("li");  
            var active;          
            ch.each(function(i) {
                if (i == 0) 
                    active = $(this).show().addClass("active"); else
                    $(this).addClass("back");
                
                $(this).attr("data-index", i);
            });
            
            if (ops.pager_selector) {
                $(ops.pager_selector.replace("%INDEX%", 0)).addClass(ops.pager_active_class);
                
                var bullet;
                for (var i = 0; i < ch.length; i++) {
                    bullet = $(ops.pager_selector.replace("%INDEX%", i));
                    bullet.data("slideshow", {
                        target: this,
                        index: i 
                    }).click(function() {
                        var data = $(this).data("slideshow");
                        
                        $.slideshow.toggle_slide.apply(data.target, [data.index]);
                    });
                }
            }
            
            if (typeof Hammer == "function") {
                var hammer = new Hammer($(this)[0], { domEvents: true });
                hammer.on('swipe', function(ev) {
                    var trg = $(ev.target).closest(".slideshow");
                    
                    var delta = Math.sign(ev.deltaX);
                    if (ev.deltaX < 0)
                        $.slideshow.next_slide.apply(trg); else
                        $.slideshow.prev_slide.apply(trg); 
                });
            }
            
            
            $.slideshow.update_queue($(this), ops);
            $.slideshow.layout.apply(this);
            
            if (ops.interval && ch.length > 1) {  
                var slider = this;
                ops._timeout = setTimeout(function() {  
                    $.slideshow.next_slide.apply(slider);
                }, ops.interval);
            }
            
            ops._width = $(this).width();
            ops._height = $(this).height();
            if (ops.observer) {
                $.slideshow.instances.push($(this));
                
                clearTimeout($.slideshow.observing);
                $.slideshow.observing = setTimeout($.slideshow.observer, 500);
            }  
            active.addClass("front");
            
            if (typeof ops.onLoaded == "function")
                ops.onLoaded.apply(this, [ch, ops]);
            
            return $(this);
        }
    };
    $.fn.slideshow = function(arg, arg1, callback) {
        if (!this.length) return;
        
        if ($.isObject(arg))
            return $.slideshow.create.apply(this, [arg]); else
        if (arg == "next")
            return $.slideshow.next_slide.apply(this, [arg1, callback]); else
        if (arg == "prev")
            return $.slideshow.prev_slide.apply(this, [arg1, callback]); else
            return $.slideshow.toggle_slide.apply(this, [arg, arg1, callback]);
    };
})( jQuery );        


