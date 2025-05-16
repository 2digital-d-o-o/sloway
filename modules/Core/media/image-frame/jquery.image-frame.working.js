(function( $ ){   
	$.image_frame = {
		options: {
            anim_image: "fade",
            anim_blur: "fade",
            background: false,
            zoom: false,
            view: null,
            ajax: false,
            pan: false,
            hammer: false,
            handler: null,
            path: null,
            preload: null,
            observer: true,
            
            onLoad: false,
		},
        panning: null,
        zooming: null,
        panning: null,
        global_events: false,
        resize_timeout: null,
        observing: false,
        instances: [],
        
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
            var ops = $(this).data("image-frame");
            var old_scale = parseFloat($(this).attr("data-scale"));
            var pan = $(this).attr("data-pan").split(",");
            
            orig_x = orig_x - ops.view[3];
            orig_y = orig_y - ops.view[0];

            if (scale < 1)
                scale = 1;
            
            var img_x = orig_x - parseFloat(pan[0]);
            var img_y = orig_y - parseFloat(pan[1]);
            
            var pan_x = orig_x - scale / old_scale * img_x;
            var pan_y = orig_y - scale / old_scale * img_y;
            
            $.image_frame.update.apply(this, [scale, [pan_x, pan_y], false, false]);
        },
        anim_end_img: function() {
            $(this).parent().children("img.if_previous").remove();
            $(this).css("visibility", "visible").css("z-index", 1);
        },
        anim_end_blur: function() {            
            $(this).parent().children("div.if_background.if_previous").remove();
            $(this).css("visibility", "visible").css("z-index", 0);    
        },
        animate: function(method, complete) {
            if (method == "fade") {
                $(this).hide().css("visibility", "visible").fadeIn(complete);
            } else
            if (method == "slide_left") {
                var x = parseInt($(this).css("left"));
                $(this).css({left: "100%", visibility: "visible"});
                $(this).animate({"left" : x + "px"}, {complete: complete, duration: 400});
            } else 
            if (method == "slide_right") {
                var x = parseInt($(this).css("left"));
                $(this).css({left: "-100%", visibility: "visible"});
                $(this).animate({"left" : x + "px"}, {complete: complete, duration: 400});
            } else
                complete.apply($(this));
        },
        update: function(scale, pan, anim_image, anim_blur) {
            if (typeof scale == "undefined" || scale === null)
                scale = parseFloat($(this).attr("data-scale"));
            if (typeof pan == "undefined" || pan === null)
                pan = $(this).attr("data-pan").split(",");
                
            var ops = $(this).data("image-frame");
            var cont_w = $(this).width() - ops.view[1] - ops.view[3];
            var cont_h = $(this).height() - ops.view[0] - ops.view[2];
            
            var img = $(this).children("img.if_current");
            var img_w = img[0].naturalWidth;
            var img_h = img[0].naturalHeight;
            
            var size = $.image_frame.contain(cont_w, cont_h, img_h / img_w); 
            if (scale < 0) scale = 1;
            
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
                left: pos_x + ops.view[3] + "px", 
                top: pos_y + ops.view[0] + "px",
            });
            var blur = $(this).children("div.if_background.if_current");
            
            if (typeof anim_image == "undefined") anim_image = ops.anim_image;
            if (typeof anim_blur == "undefined") anim_blur = ops.anim_blur;
            
            $.image_frame.animate.apply(img, [anim_image, $.image_frame.anim_end_img]);
            $.image_frame.animate.apply(blur, [anim_blur, $.image_frame.anim_end_blur]);
            
            $(this).removeClass("if_loading");
            $(this).attr("data-scale", scale);
            $(this).attr("data-pan", pos_x + "," + pos_y);
        },
        load: function(paths, callback, callback_data) {
            var ops = $(this).data("image-frame");    
            if (typeof ops.onLoad == "function") {
                $.image_frame.load_custom.apply(this, [paths, callback, callback_data]); 
            } else
            if (ops.ajax) {
                $.image_frame.load_ajax.apply(this, [paths, callback, callback_data]); 
            } else
            if (typeof callback == "function")
                callback.apply(this, [callback_data]);
        },    
        load_cache: function(paths, images) {
            var ops = $(this).data("image-frame");
            var path, img;            
            
            for (var i = 0; i < paths.length; i++) {      
                path = paths[i];
                if (!ops.cache[path]) ops.cache[path] = {};
                
                ops.cache[path]
                
                if (images["c" + i]) ops.cache[path].src = images["c" + i].scaled;
                if (images["b" + i]) ops.cache[path].src_blur = images["b" + i].scaled;
            }    
        }, 
        load_ajax: function(paths, callback, callback_data) {
            var ops = $(this).data("image-frame");    
            var cw = $(this).width();
            var ch = $(this).height(); 
            var bw = cw * 0.2;
            var bh = ch * 0.2;
            
            if (ops.zoom) {
                cw = cw * 2;
                ch = ch * 2;
            }
            
            var post = {};
            for (var i = 0; i < paths.length; i++) {
                post["c" + i] = {
                    path: paths[i],
                    width: cw    
                }
                if (ops.background == "blur") {
                    post["b" + i] = {
                        path: paths[i],
                        width: bw    
                    }
                }
            }
            
            if (!callback_data) callback_data = {};
            var xhr = $.post(ops.ajax, post, function(r, s, xhr) {
                $.image_frame.load_cache.apply(xhr.frame, [xhr.paths, r]);
                
                if (typeof xhr.callback == "function")
                    xhr.callback.apply(xhr.frame, [xhr.callback_data]);
            }, "json");
            
            xhr.paths = paths;
            xhr.callback = callback;
            xhr.callback_data = callback_data;
            xhr.frame = $(this);
        },
        load_custom: function(paths, callback, callback_data) {
            var ops = $(this).data("image-frame");    
            var cw = $(this).width();
            var ch = $(this).height(); 

            var sizes = [[cw,ch]];
            if (ops.background == "blur")
                sizes.push([0.2 * cw, 0.2 * ch]);
            
            var loader = {
                frame: $(this),
                paths: paths,
                callback: callback,
                callback_data: callback_data,
                exec: function(paths) {
                    if (typeof this.callback == "function")
                        this.callback.apply(this.frame, [callback_data]);
                }   
            }
            ops.onLoad.apply(this, [paths, sizes, loader]);
        },
        load_image: function(src, src_blur, anim_image, anim_blur) {
            var ops = $(this).data("image-frame");
            
            $(this).addClass("if_loading");
            $(this).children("img.if_foreground").addClass("if_previous").removeClass("if_current");
            $(this).children("div.if_background").addClass("if_previous").removeClass("if_current");
                                 
            var img = new Image();
            img.frame = $(this);
            img.src = src;  
            img.src_blur = src_blur; 
            img.ops = ops;
            img.anim_image = anim_image;
            img.anim_blur = anim_blur;
            img.onload = function() {
                this.frame.append("<img src='" + this.src + "' class='if_foreground if_current' style='z-index: 3; visibility: hidden'>").bind("dragstart", function() { return false });        

                if (this.ops.background) {
                    var blur = $("<div class='if_background if_current' style='z-index: 2; visibility: hidden'></div>").appendTo(this.frame); 
                    if (this.ops.background == "blur") 
                        blur.css("background-image", "url('" + this.src_blur + "')"); else
                        blur.css("background-color", this.ops.background);
                }
                
                $.image_frame.update.apply(this.frame, [null, null, this.anim_image, this.anim_blur]);
            }
        },          
        change: function(path, anim_img, anim_blur) {
            var ops = $(this).data("image-frame");
            if (!ops.cache[path]) {
                $.image_frame.load.apply(this, [[path], function(data) {
                    var ops = $(this).data("image-frame");    
                
                    var src = ops.cache[data.path].src;
                    var src_blur = ops.cache[data.path].src_blur;

                    $.image_frame.load_image.apply(this, [src, src_blur, data.anim_img, data.anim_blur]);    
                }, {
                    path: path,
                    anim_img: anim_img,
                    anim_blur: anim_blur                    
                }]); 
            } else
                $.image_frame.load_image.apply(this, [ops.cache[path].src, ops.cache[path].src_blur, anim_img, anim_blur]); 
        },
        hammer: function() {
            var hammer = new Hammer($(this)[0], { domEvents: true });
            
            hammer.get('pinch').set({ enable: true });
            hammer.get('pan').set({ direction: Hammer.DIRECTION_ALL });
            hammer.get('swipe').set({ velocity: 1, treshold: 20 });
            hammer.on('panstart', function(ev) {
                var cont = $(ev.target).closest(".image_frame");
                $.image_frame.panning = {
                    orig: cont.attr("data-pan").split(",")     
                }
                
                ev.preventDefault();
            });
            hammer.on('pan', function(ev) {
                var cont = $(ev.target).closest(".image_frame");
                if (!$.image_frame.panning) return;
                
                var orig = $.image_frame.panning.orig;
                var pan = [
                    parseFloat(orig[0]) + ev.deltaX,
                    parseFloat(orig[1]) + ev.deltaY
                ];
                $.image_frame.update.apply(cont, [null, pan, false, false]);  

                ev.preventDefault();
                ev.stopPropagation();
                
                return false;
            });     
            hammer.on('panend', function(ev) {
                $.image_frame.panning = null;

                ev.preventDefault();
            });

            hammer.on('pinchstart', function(ev) {
                var cont = $(ev.target).closest(".image_frame");
                $.image_frame.zooming = {
                    timeout: null,
                    target: cont,
                    orig_scale: parseFloat(cont.attr("data-scale")),   
                }
                
                ev.preventDefault();
            });
            hammer.on('pinchend', function(ev) {
                clearTimeout($.image_frame.zooming.timeout);
                $.image_frame.zooming = null;

                ev.preventDefault();
            });
            hammer.on('pinch', function(ev) {
                if (!$.image_frame.zooming) return;
                
                var zooming = $.image_frame.zooming;
                var cont = $(ev.target).closest(".image_frame");
                var ofs = cont.offset();
                var scale = ev.scale * zooming.orig_scale; 
                
                $.image_frame.zoom.apply(cont, [scale, ev.center.x - ofs.left, ev.center.y - ofs.top, false, false]);
                
                ev.preventDefault();
            });    
        },
        observer: function() {
            var instance, ops;
            var count = 0;
            for (var i = 0; i < $.image_frame.instances.length; i++) {
                instance = $.image_frame.instances[i];    
                if (!instance) {
                    delete $.image_frame.instances[i];
                    continue;
                }
                
                ops = instance.data("image-frame");
                if (!ops) {
                    delete $.image_frame.instances[i];
                    continue;
                }
                
                count++;
                
                if (!instance.is(":visible")) continue;
                
                var inst_w = instance.width();
                var inst_h = instance.height();
                if (inst_w != ops._width || inst_h != ops._height) {
                    ops._width = inst_w;
                    ops._height = inst_h;
                    $.image_frame.update.apply(instance, [null,null,false,false]); 
                }
            }    
            
            if (count) 
                $.image_frame.observing = setTimeout($.image_frame.observer, 500);
        },
        create: function(options) {
            var attr = {
                path: $(this).attr("data-path"),
                ajax: $(this).attr("data-ajax"),
                background : $(this).attr("data-background")
            };
            var ops = $.extend({}, $.image_frame.options, options, attr);
            
            if (ops.background === true)
                ops.background = "white";
            if (!$.isArray(ops.view) || ops.view.length != 4) 
                ops.view = [0,0,0,0];
            ops.cache = {};
            
            $(this).addClass("image_frame if_loading");
            if (ops.background == "blur")
                $(this).addClass("if_blur");
            $(this).attr("data-scale", 1);
            $(this).attr("data-pan", "0,0");
            $(this).data("image-frame", ops);
            
            if (ops.zoom) {
                $(this).mousewheel(function(e) {
                    var ofs = $(this).offset();
                    var scale = parseFloat($(this).attr("data-scale")) + e.deltaY * 0.2;
                    $.image_frame.zoom.apply(this, [scale, e.pageX - ofs.left, e.pageY - ofs.top]);
                    
                    return false; 
                });
            }
            if (ops.pan) {
                $(this).mousedown(function(e) {
                    $.image_frame.panning = {
                        target: $(this),
                        prevX: e.pageX,
                        prevY: e.pageY,
                    };    
                });
            }
            
            if (ops.hammer) 
                $.image_frame.hammer.apply(this);    
            
            if (!$.image_frame.global_events && ops.pan) {
                $(document).mousemove(function(e) {
                    if (!$.image_frame.panning) return;

                    var panning = $.image_frame.panning;
                    var dx = e.pageX - panning.prevX;
                    var dy = e.pageY - panning.prevY;
                    
                    var pan = panning.target.attr("data-pan").split(",");
                    pan[0] = parseInt(pan[0]) + dx;
                    pan[1] = parseInt(pan[1]) + dy;
                    
                    $.image_frame.update.apply(panning.target, [null, pan, false, false]);
                    
                    panning.prevX = e.pageX;
                    panning.prevY = e.pageY;
                    
                    e.stopPropagation();
                    e.preventDefault();
                    return false;
                }).mouseup(function(e) {
                    $.image_frame.panning = null;
                });
                
                $.image_frame.global_events = true;    
            }
                                                                     
            var paths = [ops.path];
            if (ops.preload) {
                paths = ops.preload;
                var inc = false;
                for (var i = 0; i < paths.length; i++)
                    if (paths[i] == ops.path) {
                        inc = true;
                        break;    
                    }
                
                if (!inc)
                    paths.push(ops.path);
            }
                
            $.image_frame.load.apply(this, [paths, function(data) {
                var ops = $(this).data("image-frame");
                
                var src = ops.cache[data.path].src;
                var src_blur = ops.cache[data.path].src_blur;

                $.image_frame.load_image.apply(this, [src, src_blur, null, null]);   
            }, { path: ops.path }]);
            
            ops._width = $(this).width();
            ops._height = $(this).height();
            if (ops.observer) {
                $.image_frame.instances.push($(this));
                
                clearTimeout($.image_frame.observing);
                $.image_frame.observing = setTimeout($.image_frame.observer, 500);
            }
        }
    }
	$.fn.image_frame = function(method, arg, arg1, arg2) {
        if (!this instanceof jQuery) return this;
        
        if (!$(this).length) return $(this);
        
        if ($.isObject(method) || typeof method == "undefined") 
            return $.image_frame.create.apply(this, [method]); 
            
        if (!$(this).hasClass("image_frame")) return $(this);
        
        if (method == "update") {
            var ops = $(this).data("image-frame");
            ops._width = $(this).width();
            ops._height = $(this).height();
            
            $.image_frame.update.apply($(this), [null,null,false,false]);
        } else
        if (method == "reload") {
            var ops = $(this).data("image-frame");
            $(this).attr("data-scale", 1).attr("data-pan", "0,0");
            
            ops.path = arg;
            $.image_frame.change.apply(this, [arg, arg1, arg2]);
        } 
        
        return $(this);
	}
})( jQuery );        


