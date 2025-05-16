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
            alt: null,
            preload: null,
            observer: true,

            onLoad: false,
            onSwipe: null,
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
            
            if (size[0] > img_w) {
                var sc = Math.pow(2, Math.ceil(Math.log(scale)/Math.log(2)));        
                /*$.image_frame.load.apply(this, [[ops.path], function(image) {
                    
                }, {}, sc]);*/
            }
            
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
        display: function(image, anim_image, anim_blur) {
            var ops = $(this).data("image-frame");
            ops.curr_image = image;
            
            $(this).addClass("if_loading");
            $(this).children("img.if_foreground").addClass("if_previous").removeClass("if_current");
            $(this).children("div.if_background").addClass("if_previous").removeClass("if_current");
            
            $(this).append("<img src='" + image.src + "' alt='" + ops.alt + "' class='if_foreground if_current' style='z-index: 3; visibility: hidden'>").bind("dragstart", function() { return false });        
            
            if (ops.background) {
                var blur = $("<div class='if_background if_current' style='z-index: 2; visibility: hidden'></div>").appendTo(this); 
                if (ops.background == "blur") {
                    var blur_img = $.image_frame.loaded(ops.path, $(this).width() * 0.2, ops);
                    blur.css("background-image", "url('" + blur_img.src + "')"); 
                } else
                    blur.css("background-color", ops.background);
            } 
            
            $.image_frame.update.apply(this, [null, null, anim_image, anim_blur]);            
        },
        loaded: function(path, width, ops) {
            var set_w = null;
            var max_w = null;
            var img;
            
            if (!ops.images[path]) return false;
            
            for (var cw in ops.images[path]) {
                if (width <= cw && (set_w === null || cw < set_w))
                    set_w = cw;
                
                if (max_w === null || cw > max_w)
                    max_w = cw;
            }  
            
            if (!set_w) set_w = max_w;
            
            return ops.images[path][set_w];
        },        
        load: function(paths, callback, callback_data, scale) {
            var ops = $(this).data("image-frame"); 
            if (ops.ajax) {
                var cw = $(this).width();
                var ch = $(this).height(); 
                var bw = cw * 0.2;
                var bh = ch * 0.2;
                
                if (scale) {
                    cw = cw * scale; 
                    ch = ch * scale;
                } else
                if (ops.zoom) {
                    cw = cw * 2;
                    ch = ch * 2;
                } 
                
                var post = {}; 
                var ind = 0;
                var loaded = false;
                for (var i = 0; i < paths.length; i++) {
                    var img = $.image_frame.loaded(paths[i], cw, ops);
                    if (img && paths[i] == ops.path)
                        loaded = img;
                        
                    if (!img)
                        post[ind++] = { path: paths[i], width: cw, height: ch, mode: "contain" }; 
                    
                    if (!scale && ops.background == "blur" && !$.image_frame.loaded(paths[i], cw, ops))
                        post[ind++] = { path: paths[i], width: bw, height: bh, mode: "contain" } 
                }
                
                if (loaded && typeof callback == "function")
                    callback.apply(this, [loaded, callback_data]);
                
                if (ind == 0) return;
                var xhr = $.post(ops.ajax, post, function(images, s, xhr) {
                    var ops = xhr.frame.data("image-frame");
                    var img;
                    for (var i in images) {
                        img = images[i];

                        if (!ops.images[img.path]) ops.images[img.path] = {}
                        if (ops.images[img.path][img.width]) 
                            continue;
                        
                        var image = new Image();
                        if (img.path == ops.path && img.width >= xhr.width) {
                            image.onload = function() { 
                                if (typeof this.callback == "function")
                                    this.callback.apply(this.frame, [this, this.callback_data]);
                            }
                            image.frame = xhr.frame;
                            image.callback = xhr.callback;
                            image.callback_data = xhr.callback_data;
                        }
                        image.src = img.scaled;
                        image.max_width = img.max_width;
                        image.max_height = img.max_height;
                        
                        ops.images[img.path][img.width] = image;
                    }
                }, "json");
                
                xhr.width = cw; 
                xhr.callback = callback;
                xhr.callback_data = callback_data;
                xhr.frame = $(this);
            } else 
            for (var i in paths) {
                var img = new Image();
                img.onload = function() { 
                    if (typeof this.callback == "function")
                        this.callback.apply(this.frame, [this, this.callback_data]);    
                }
                img.frame = $(this);
                img.callback = callback;
                img.callback_data = callback_data;
                img.src = paths[i];
                    
                if (!ops.images[img.path]) ops.images[img.path] = {};
                ops.images[img.path][img.width] = img;
            }
        },    
        change: function(path, anim_image, anim_blur) {
            var ops = $(this).data("image-frame");
            
            $.image_frame.load.apply(this, [[path], function(image, data) {
                $.image_frame.display.apply(this, [image, data.anim_image, data.anim_blur]);    
            }, {
                anim_image: anim_image,
                anim_blur: anim_blur    
            }]);
        },
        hammer: function() {
            var hammer = new Hammer($(this)[0], { domEvents: true });
            
            hammer.get('pinch').set({ enable: true });
            hammer.get('pan').set({ direction: Hammer.DIRECTION_ALL });
            hammer.get('swipe').set({ velocity: 0.1, treshold: 10 });
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
                //ev.stopPropagation();
                
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
            hammer.on('swipe', function(ev) {
                var cont = $(ev.target).closest(".image_frame");
                var ops = cont.data("image-frame");
                
                if (typeof ops.onSwipe === "function")
                    ops.onSwipe.apply(cont, [ev.velocityX]);
                
                ev.preventDefault();
                
                return false;
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
            ops.images = {};
            
            $(this).addClass("image_frame if_loading");
            if (ops.background == "blur")
                $(this).addClass("if_blur");
            $(this).attr("data-scale", 1);
            $(this).attr("data-pan", "0,0");
            $(this).data("image-frame", ops);
            
            if (ops.zoom) {
                $(this).mousewheel(function(e) {
                    var ofs = $(this).offset();
                    var scale = parseFloat($(this).attr("data-scale"));
                    if (scale == 1 && e.deltaY < 0) return;
                    
                    var scale = scale + e.deltaY * 0.2;
                    
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
            var titles = [ops.alt];
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
            
            $.image_frame.load.apply(this, [paths, function(image, data) {
                $.image_frame.display.apply(this, [image]);
                //    
            }]);
            
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


