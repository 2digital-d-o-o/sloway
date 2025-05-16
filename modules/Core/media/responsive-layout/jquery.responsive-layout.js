(function( $ ){   
    "use strict";
    
    $.rl = {
        initial_width: 800,
        interval: 400,
        images_url: null,
        loader_size: 50,
        loader_class: "",
        media: {
            "mobile" :  600,
            "tablet" : 1000,
            "laptop" : 1366,
            "desktop" : 0,
        },
        media_classes : null,
        state: {
            id_counter: 0,
            width: null, 
            scroll_top: null,
            scroll_left: null,  
            editables: {},
            templates: {},
            frames: {},
            images: {}, 
        },
        template_handlers: {
        },
        intersect: function(r1_x1,r1_y1,r1_x2,r1_y2, r2_x1,r2_y1,r2_x2,r2_y2) {
            return !(r2_y1 > r1_y2 || r2_y2 < r1_y1);
        },        
        load_image: function() {
            var cont = this.container;
            var mode = cont.attr("data-mode");
            if (!mode) mode = "contain"; 
            cont.removeClass("loading empty");
            cont.children(".adaptive_image_loader").remove();
            
            var title = cont.attr("data-title"); if (!title) title = "";
            var alt = cont.attr("data-alt"); if (!alt) alt = "";                        
            var rel = cont.attr("data-rel"); if (!rel) rel = "";
            
            cont.append("<img src='" + this.src + "' alt='" + alt + "' rel='" + rel + "' title='" + title + "'>");
                        
            $.rl.layout_image(cont);
            cont.addClass("loaded");
            cont.trigger("rl_image_loaded", [this]);                
        },
        layout_image: function(image) {
            var img = image.children("img")[0];
            var cont_r = image.height() / image.width();
            var img_r = img.naturalHeight / img.naturalWidth;
            
            var mode = image.attr("data-mode");
            if (mode == "contain") 
                image.attr("data-adapt", (cont_r > img_r) ? "width" : "height"); else
                image.attr("data-adapt", (cont_r < img_r) ? "width" : "height");  
        },
        layout_images: function() {
            var ids = [];
            var paths = [];
            var sizes = [];
            var wmarks = [];
            var modes = [];

            var count = 0;
            var images = {};
            var containers = {};
            
            var win_st = $(window).scrollTop();
            var win_sl = $(window).scrollLeft();
            var win_w = $(window).width();
            var win_h = $(window).height();
            
            var image;
            for (var i in $.rl.state.images) {
                image = $.rl.state.images[i];
                
                if (!image.is(":visible")) continue;
                if (image.hasClass("loading")) continue;
                if (image.hasClass("invalid")) continue;
                
                var size = image.attr("data-size");
                if (!size) size = 0;
                    
                var width = image.outerWidth();
                var height = image.outerHeight();
                var ofs = image.offset();
                
                /*
                if (!$(this).hasClass("loaded")) {
                    var loader = $(this).children("div.adaptive_image_loader");
                    if (!loader.length) {
                        loader = $("<div class='adaptive_image_loader'><div class='" + $.rl.loader_class + "'></div>").appendTo($(this));
                        if (width > height) 
                            loader.css("width", $.rl.loader_size * height / width + "%"); else
                            loader.css("width", $.rl.loader_size + "%");
                    }
                }
                                     */
                /*
                var x1 = ofs.left - win_sl;
                var y1 = ofs.top - win_st;
                var x2 = x1 + width;
                var y2 = y1 + height;
                
                if (!$.rl.intersect(x1,y1,x2,y2, 0,0,win_w,win_h)) return;*/
                
                if (image.hasClass("ajax") && parseInt(size) < width) {
                    images[count] = {
                        path: image.attr("data-path"),
                        mode: image.attr("data-mode"),
                        width: width,
                        height: height    
                    }
                    containers[count] = image;
                    
                    image.addClass("loading"); 
                
                    count++;     
                } else {
                    image.addClass("loaded");
                    $.rl.layout_image(image);
                }
            }
            
            if (!count) return;
            var xhr = $.post($.rl.images_url, images, function(images, status, xhr) {
                var image, img, cont, size, mode;
                for (var i in images) {
                    image = images[i];
                    cont = xhr.containers[i];
                    size = [image.width, image.height];
                    
                    if (image.error) {
                        cont.removeClass("loading empty").addClass("invalid");
                        continue;   
                    }
                    cont.attr("data-size", size);
                    cont.attr("data-imgsize", [image.scaled_width, image.scaled_height]);
                    
                    img = new Image();
                    img.container = cont;
                    img.onload = $.rl.load_image;
                    img.src = image.scaled;
                }
            }, "json"); 
            xhr.containers = containers;           
        },
        layout_editable: function(editable) {
            var level = parseInt(editable.attr("data-level"));
            var template = editable.closest(".rl_template");
            var width = editable.width();
            var scale = parseFloat(template.attr("data-collapse"));
            if (isNaN(scale)) scale = 1;
            
            var rel_w = width / $.rl.initial_width;
            var treshold = width / template.width() * scale * Math.pow(0.7, level + 1);   
            
            if (rel_w < treshold) 
                template.addClass("rl_collapsed"); 
        },
        layout_frame: function(frame) {
            var template = frame.closest(".rl_template");
            var c = template.hasClass("rl_collapsed");
            var pc = template.data("rl_collapsed_state");
            
            if (c == pc) return;
            
            frame.children(".rl_template").toggleClass("rl_framed", !c);                    
        },        
        layout_templates: function() {    
            var scr_width = $(window).width();
            var media = null;
            var i, template, name;
            
            for (i in $.rl.state.templates) {
                template = $.rl.state.templates[i];
                template.data("rl_collapsed_state", template.hasClass("rl_collapsed"));    
                template.removeClass("rl_collapsed");
            }
            for (media in $.rl.media) {
                if (scr_width < $.rl.media[media])                    
                    break;
            }
            var media_cls = "rl_media_" + media;
            
            for (i in $.rl.state.editables) 
                $.rl.layout_editable($.rl.state.editables[i]);
                
            for (i in $.rl.state.templates) {
                template = $.rl.state.templates[i];
                name = template.attr("data-name");
                
                if (template.hasClass(media_cls))
                    template.show(); else
                    template.hide();
                
                if (typeof $.rl.template_handlers[name] == "function")
                    $.rl.template_handlers[name].apply(template);
            }
        }, 
        listener: function() {
            var w = $(window).width();
            var st = $(window).scrollTop();
            var sl = $(window).scrollLeft();
            
            var w_st = w != $.rl.state.width || $.rl.state.force_update;
            var s_st = st != $.rl.state.scroll_top || sl != $.rl.state.scroll_left || $.rl.state.force_update;
            
            if (w_st || s_st) {
                if (w_st)
                    $.rl.layout_templates();
                
                $.rl.layout_images();
            }
            $.rl.state.width = w;     
            $.rl.state.scroll_top = st;
            $.rl.state.scroll_left = sl;    
            $.rl.state.force_update = false;
            
            $.rl.state.timeout = setTimeout(function() {
                $.rl.listener();
            }, $.rl.interval);
        },        
        register: function(src) {
            if (!$.rl.media_classes) {
                var media_cls_all = [];
                for (var m in $.rl.media)
                    media_cls_all.push("rl_media_" + m);
                $.rl.media_classes = media_cls_all.join(" ");
            }
            
            src.find(".rl_editable").each(function() {
                var rlid = $(this).data("rl-tid");
                if (!rlid) {
                    rlid = "rl-" + $.rl.state.id_counter++;
                    $.rl.state.editables[rlid] = $(this);  
                    
                    $(this).data("rl-tid", rlid);      
                } 
            });
            src.find(".rl_template").each(function() {
                var rlid = $(this).data("rl-eid"); 
                if (!rlid) {
                    rlid = "rl-" + $.rl.state.id_counter++;
                    $.rl.state.templates[rlid] = $(this);        
                    
                    if ($(this).attr("class").indexOf("rl_media") == -1) 
                        $(this).addClass($.rl.media_classes);                    

                    $(this).data("rl-eid", rlid);      
                } 
            });
            src.find(".adaptive_image").each(function() {
                var rlid = $(this).attr("data-rlid"); 
                if (!rlid) {
                    rlid = "rl-" + $.rl.state.id_counter++;
                    $.rl.state.images[rlid] = $(this);        
                    
                    var mode = $(this).attr("data-mode");
                    if (mode == "fit") mode = "contain";
                    if (mode == "fill") mode = "cover";
                    if (!mode) mode = "contain";
                    
                    if ($(this).parents(".rl_template.rl_framed").length)
                        mode = "cover";              
                    
                    // if (!$(this).outerHeight()) mode = "static";      
                    if (!$(this).hasClass("ajax") && !$(this).children("img").length)
                        $(this).addClass("ajax");
                    
                    $(this).attr("data-mode", mode);
                    $(this).attr("data-rlid", rlid);      
                } 
            });

            // $(document).trigger("rl_register", [$.rl.state]);
            //console.log($.rl.state);
            
            $.rl.state.force_update = true;
            if (!$.rl.state.listener) {
                $.rl.listener();
                $.rl.state.listener = true;     
            }
        },
    }
    $.fn.reload_image = function() {   
        var cont = $(this);
        
        $.post($.rl.images_url, {
            ids: [1],
            wmarks: [$(this).attr("data-wmark")],
            paths: [$(this).attr("data-path")],
            sizes: [[$(this).width(), $(this).height()]]
        }, function(r) { 
            var img;
            for (var i in r.images) {
                cont.attr("data-size", r.sizes[i]);
                img = new Image();
                img._container = cont;
                img.onload = function() {
                    var cont = this._container;
                    cont.removeClass("loading empty").addClass("loaded");
                    
                    var curr = cont.children("div.adaptive_image_content:visible");
                    var zind = (curr.length) ? curr.index() : 0;
                    
                    var image = $("<div class='adaptive_image_content' style=\"background-image: url('" + this.src + "'); display: none; z-index: " + zind + "\"></div>").appendTo(cont);
                    
                    cont.trigger("rl_image_loaded", [this]);    
                    image.fadeIn(function() {
                        curr.hide();    
                    });
                }
                img.src = r.images[i];
            }
        }, "json");            
    }    
    $.fn.responsive_layout = function(method) {
        if (method == "update") {
            $.rl.layout_templates();
            $.rl.layout_images();
        } else
            $.rl.register($(this));
    }    
})( jQuery ); 

