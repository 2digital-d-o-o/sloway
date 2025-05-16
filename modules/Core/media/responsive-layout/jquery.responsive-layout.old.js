(function( $ ){   
    "use strict";
    
    $.rl = {
        initial_width: 800,
        interval: 500,
        images_url: null,
        template_handlers: {},

        intersect: function(r1_x1,r1_y1,r1_x2,r1_y2, r2_x1,r2_y1,r2_x2,r2_y2) {
            return !(r2_x1 > r1_x2 || 
                     r2_x2 < r1_x1 || 
                     r2_y1 > r1_y2 ||
                     r2_y2 < r1_y1);
        },        
        layout_fonts: function(data) {
            data.fonts.each(function(i) {
                var font = $(this).attr("data-font");
                if (!font) return;
                
                font = font.split(",");
                if (font.length != 4) return;
                
                var y1 = parseInt(font[0]);
                var y2 = parseInt(font[2]);
                var x1 = parseInt(font[1]);
                var x2 = parseInt(font[3]);
                
                var size = 0;
                var width = $(this).width();
                if (width > x1)
                    size = y1; else
                if (width < x2)
                    size = y2; else
                    size = y1 + (y2-y1)*(width-x1)/(x2-x1);
                
                $(this).animate({"font-size" : size + "px"});
            });
        },
        layout_images: function(data) {
            var ids = [];
            var paths = [];
            var sizes = [];
            var wmarks = [];
            var modes = [];
            var count = 0;
            
            var win_st = $(window).scrollTop();
            var win_sl = $(window).scrollLeft();
            var win_w = $(window).width();
            var win_h = $(window).height();
            
            data.images.each(function(i) {
                if (!$(this).is(":visible")) return;
                if ($(this).hasClass("loading")) return;
                if ($(this).hasClass("invalid")) return;
                                                                        
                var size = $(this).attr("data-size");
                if (typeof size == "undefined")
                    $(this).addClass("empty");
                    
                var width = $(this).outerWidth();
                var height = $(this).outerHeight();
                var ofs = $(this).offset();
                
                var x1 = ofs.left - win_sl;
                var y1 = ofs.top - win_st;
                var x2 = x1 + width;
                var y2 = y1 + height;
                
                if (!$.rl.intersect(x1,y1,x2,y2, 0,0,win_w,win_h)) return;
                
                if (typeof size == "undefined" || parseInt(size) < width) {
                    ids.push(i);
                    paths.push($(this).attr("data-path"));
                    sizes.push([width, height]);
                    var mode = $(this).attr("data-mode");
                    if (typeof mode == "undefined")   
                        mode = "fit";
                    
                    modes.push(mode);
                    
                    var wmark = $(this).attr("data-wmark");
                    if (typeof wmark == "undefined")   
                        wmark = false;
                        
                    wmarks.push(wmark);
                    
                    $(this).addClass("loading"); 
                
                    count++;
                } 
            }); 
            
            if (!count) return;
            $.post($.rl.images_url, {
                ids: ids,
                paths: paths,
                sizes: sizes,
                modes: modes,
                wmarks: wmarks, 
            }, function(r) {
                var img;
                for (var i in r.images) {
                    var cont = $(data.images[i]);
                    if (r.errors[i]) {
                        cont.removeClass("loading empty").addClass("invalid");
                        continue;   
                    }
                    cont.attr("data-size", r.sizes[i]);
                    img = new Image();
                    img._container = cont;
                    img._mode = r.modes[i];
                    img.onload = function() {
                        var cont = this._container;
                        cont.removeClass("loading empty").addClass("loaded");
                        
                        var curr = cont.children("div.adaptive_image_content:visible");
                        var zind = (curr.length) ? curr.index() : 0;
                        
                        var mode = (this._mode == 'fill') ? 'cover' : 'contain';
                        var image = $("<div class='adaptive_image_content' style=\"background-image: url('" + this.src + "'); background-size: " + mode + "; display: none; z-index: " + zind + "\"></div>").appendTo(cont);
                        
                        cont.trigger("rl_image_loaded", [this]);                            
                        image.fadeIn(function() {
                            curr.hide(); 
                        });
                    }
                    img.src = r.images[i];
                }
            }, "json");            
        },
        layout_editable: function(editable) {
            var level = parseInt(editable.attr("data-level"));
            var template = editable.closest(".rl_template");
            var width = editable.width();
            
            var rel_w = width / $.rl.initial_width;
            var treshold = width / template.width() * Math.pow(0.7, level + 1);   
            
            if (rel_w < treshold) 
                template.addClass("rl_collapsed"); 
        },
        layout_templates: function(data) {      
            data.templates.removeClass("rl_collapsed");
                
            for (var i = 0; i < data.editables.length; i++) 
                $.rl.layout_editable($(data.editables[i]));
            
            var template, name;
            for (var i = 0; i < data.templates.length; i++) {
                template = $(data.templates[i]);
                name = template.attr("data-name");
                
                if (typeof $.rl.template_handlers[name] == "function")
                    $.rl.template_handlers[name].apply("layout");
            }
        }, 
        listener: function(src) {
            var data = src.data("responsive_layout");  
            if (!data) return;
            
            var w = src.width();
            var st = $(window).scrollTop();
            var sl = $(window).scrollLeft();
            if (w != data.width) {
                $.rl.layout_templates(data);
                $.rl.layout_images(data);
                $.rl.layout_fonts(data);
                
                data.width = w;                
            } else 
            if (st != data.scroll_top || sl != data.scroll_left) {
                $.rl.layout_images(data);
                
                data.scroll_top = st;
                data.scroll_left = sl;    
            }
            data.timeout = setTimeout(function() {
                $.rl.listener(src);
            }, $.rl.interval);
        },        
        register: function(src) {
            var data = {
                editables: src.find(".rl_editable"),
                templates: src.find(".rl_template"),
                width: null
            }
            
            $(document).trigger("rl_register", [data]);
            
            data["images"] = src.find(".adaptive_image");
            data["fonts"] = src.find(".adaptive_font");
            
            src.data("responsive_layout", data);
            $.rl.listener(src);
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
    $.fn.responsive_layout = function() {
        $.rl.register($(this));
    }
})( jQuery ); 

