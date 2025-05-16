(function ($) {
    "use strict"
    $.isString = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object String]');
    }    
    $.ov = {
        keys: {
            ENTER: 13,
            ESC: 27                     
        },
        timeout: null,
        global_events: false,    
              
        default_options: {
            fixed: false,
            mode: null, // throbber, dialog, inline, iframe, ajax
            name: "",
            parent: "",
            title: "",
            content: "",
            pos_x: "center",
            pos_y: "center",
            width: 0.3,
            height: null, 
            fullscreen: false,
            min_height: 100,
            min_width: 100,
            max_height: 0,
            max_width: 0,
            url: "",
            form: false,
            form_data: {},
            loader_html: "", 
            post: {},
            buttons: {},
            controls: ["close", "maximize"],
            close_outside: false,
            "class": "",    
            show: true,                                  // show automaticaly, set to false for special timeout (example image loaded)
            activate: true,
            timeout: false,
            fade: false,
            header: true,
            container: "body",
            context: null,
            modal: true,   
            auto_focus: false,
            onResize: null,
            onDisplay: null,
            onActivate: null,
            onSuccess: function() {},
            onCancel: function() {},
            onClose: function() {},
            onFail: null,
        },
        zbase: 10000,
        resizing: false,
        default_buttons: {
            "ok"       : { title : "OK", result : true, key : 13 },
            "cancel"   : { title : "Cancel", result : false, align: "right", key : 27 },
            "close"    : { title : "Close", result : true, align: "right", key : 27 },
        },
        default_button_ops: {
            title : null, 
            align: "left", 
            element: "input", 
            result: null,
            submit: false,
            attr: "",
            "class": "", 
            
            onClick: null,
        },
        default_form_ops: {
            url: null,
            method: "post",
            enter_submit: false,
            ajax: false,
        },
        
        is_fullscreen: false,
        loaded_callback: null,
        resize_timeout: null,
        parse_url: function(url) {
            var s = url.split("?");
            var res = {
                "url" : s[0],
                "query" : {}    
            }
            if (s.length != 2) return res;
            
            var s3,s2 = s[1].split("&");
            for (var i = 0; i < s2.length; i++) {
                s3 = s2[i].split("=");
                if (s3.length != 2) continue;
                res.query[s3[0].trim()] = s3[1].trim();
            }
            
            return res;
        },
        build_url: function(obj) {
            var res = obj.url;
            var q = [];
            for (var name in obj.query)
                q.push(name + "=" + obj.query[name]);
            
            if (q.length)
                res+= "?" + q.join("&");
            
            return res;
        },
        get_overlay: function(name) {
            if (typeof name == "undefined" || name == null || name == "") return null;
            
            var ov = $("#overlay_stack").children(".overlay[data-name=" + name + "]");
            if (ov.length)
                return ov; else
                return null;
        },  
        gen_zindices: function() {
            var list = $("#overlay_stack").children(".overlay");
            list.each(function() {
                var ops = $(this).data("overlay");
                if (!ops.zindex)
                    var base = $.ov.zbase + parseInt($(this).attr("data-index")) * 100; else
                    var base = ops.zindex; 
                
                $(".overlay_glass", this).css("z-index", base);
                $(".overlay_popup", this).css("z-index", base + 1);
                $(".overlay_loader", this).css("z-index", base + 100 - 5);
                $(".overlay_outline", this).css("z-index", base + 100 - 10);   
                $(".overlay_resize_border", this).css("z-index", base + 10);
                $(".overlay_resize_corner", this).css("z-index", base + 11);
            });
        },
        gen_stack: function() {
            var stack = $("#overlay_stack");    
            if (!stack.length) 
                stack = $("<div id='overlay_stack' nid='1'></div>").appendTo($("body"));
            
            if (!$.ov.global_events) {
                $(window).bind("resize", function() {
                    clearTimeout($.ov.resize_timeout);

                    $.ov.resize_timeout = setTimeout(function() {
                        var win_w = $(window).width();
                        var win_h = $(window).height();
                        $("#overlay_stack").children(".overlay").each(function() {
                            $.ov.position($(this)); 
                        });
                    }, 20);
                });
                $(window).bind("hashchange", function() {
                    var stack = $("#overlay_stack");
                    var fl_ov = stack.children(".overlay_fullscreen");
                    
                    var hash = window.location.hash;
                    if (fl_ov.length && hash.indexOf("#ov_fs") == -1) 
                        $.overlay_close_all();
                });
                $(document).bind("mouseup", function() {
                    if (!$.ov.resizing) return;
                    
                    var overlay = $.ov.resizing.overlay.removeClass("overlay_resizing");
                    var ops = overlay.data("overlay");
                    var outline = overlay.children(".overlay_outline");
                    if (outline.is(":visible")) {
                        $.ov.resize(overlay,
                            outline.offset().left - $(window).scrollLeft(), 
                            outline.offset().top - $(window).scrollTop(),
                            outline.outerWidth(),
                            outline.outerHeight()
                        );
                    };
                    
                    if (!ops.modal)
                        overlay.children(".overlay_glass").hide();
                    
                    outline.hide(); 
                    
                    $.ov.resizing = null;
                });
                $(document).bind("mousemove", function(e) {
                    if (!$.ov.resizing) return;
                    
                    var overlay = $.ov.resizing.overlay;
                    var popup = overlay.children(".overlay_popup");
                    var ops = overlay.data("overlay");
                    var hook = $.ov.resizing.hook;
                    
                    var cfg = hook.attr("data-cfg");
                    var pick = hook.attr("data-pick").split(",");
                    var px = pick[0];
                    var py = pick[1];
                    
                    var outline = overlay.children(".overlay_outline");
                    if (!outline.is(":visible")) {
                        if (typeof ops.onResizeStart == "function")
                            ops.onResizeStart.apply(overlay);
                            
                        overlay.addClass("overlay_resizing");
                    }
                                        
                    outline.show();
                    if (!ops.modal)
                        overlay.children(".overlay_glass").show();
                    
                    var win_w = $(window).width();     
                    var win_h = $(window).height();
                    
                    var hh = popup.children(".overlay_header").outerHeight();
                    
                    var oo_w = outline.outerWidth();
                    var oo_h = outline.outerHeight();
                    
                    var x1,y1,x2,y2,max = false;
                    if (cfg == "move") {
                        if (overlay.hasClass("overlay_maximized")) return;
                        
                        x1 = e.clientX;
                        y1 = e.clientY;
                        
                        if (x1 < 0) x1 = 0;
                        if (y1 < 0) y1 = 0;
                        if (x1 > win_w) x1 = win_w;
                        if (y1 > win_h) y1 = win_h;

                        /*
                        if (x1 < 5) {
                            x1 = 0;
                            y1 = 0;
                            oo_w = win_w / 2;
                            oo_h = win_h; 
                             
                            max = "left";
                        } else
                        if (x1 > win_w - 5) {
                            x1 = win_w / 2;
                            y1 = 0;
                            oo_w = win_w / 2;
                            oo_h = win_h;
                            
                            max = "right";  
                        } else {      */
                            x1 = x1 - px;
                            y1 = y1 - py;
                        //}
                            
                        x2 = x1 + oo_w;
                        y2 = y1 + oo_h;
                    } else {
                        x1 = outline.offset().left - $(window).scrollLeft();
                        y1 = outline.offset().top - $(window).scrollTop();
                        
                        x2 = x1 + oo_w;
                        y2 = y1 + oo_h;
                        
                        cfg = cfg.split(",");
                        if (cfg[0] == '1') { 
                            x1 = e.pageX - $(window).scrollLeft();
                            if (x2 - x1 < ops.min_width)
                                x1 = x2 - ops.min_width;
                        }
                        if (cfg[1] == '1') {
                            y1 = e.pageY - $(window).scrollTop();
                            if (y2 - y1 < (ops.min_height + hh))
                                y1 = y2 - (ops.min_height + hh);
                        }
                        if (cfg[2] == '1') { 
                            x2 = e.pageX - $(window).scrollLeft();
                            if (x2 - x1 < ops.min_width)
                                x2 = x1 + ops.min_width;
                        }
                        if (cfg[3] == '1') { 
                            y2 = e.pageY - $(window).scrollTop();    
                            if (y2 - y1 < (ops.min_height + hh))
                                y2 = y1 + ops.min_height + hh;
                        }
                        
                        if (x1 < 0) x1 = 0;
                        if (y1 < 0) y1 = 0;
                        if (x2 > win_w) x2 = win_w;
                        if (y2 > win_h) y2 = win_h;
                        
                        overlay.removeClass("overlay_maximized");
                    }
                              
                    outline.css({
                        left: x1 + "px",
                        top: y1 + "px",
                        right: $(window).width() - x2 + "px",
                        bottom: $(window).height() - y2 + "px",
                    });
                    
                    e.stopPropagation();
                    return false;    
                });
                
                $.ov.global_events = true;
            }
            
            return stack;    
        },
        gen_overlay: function(ops) {
            var stack = $.ov.gen_stack();
            var oid = parseInt(stack.attr('nid'));
            var index = stack.children(".overlay").length;
            
            if (ops.fullscreen)
                ops['class']+= " overlay_fullscreen";

            var html = "<div class='overlay " + ops['class'] + "' data-index='" + index + "' data-name='" + ops.name + "'>";
            html+= "<div class='overlay_glass' style='display: none'></div>";
            html+= "<div class='overlay_loader' style='display: none'>" + ops.loader_html + "</div>";
            html+= "<div class='overlay_outline'></div>";
            html+= "<div class='overlay_popup'>";
            html+= "</div>";
            html+= "</div>";
            
            var overlay = $(html).appendTo(stack);
            
            overlay.children(".overlay_popup").mousedown(function() {
                var overlay = $(this).parents(".overlay:first");
                
                $.ov.activate(overlay);
            });
            overlay.children(".overlay_glass").click(function() {
                var ops = $(this).parents(".overlay:first").data("overlay");
                if (ops.close_outside)
                    $.overlay_close();
            });
            overlay.data("overlay", ops);
            
            return overlay;
        },
        gen_buttons: function(ops) {
            var op, button, cnt = 0;
            var html = "<div class='overlay_buttons' id='overlay_buttons'>";
            
            for (button in ops.buttons) {
                op = ops.buttons[button];
                
                var base = jQuery.extend({}, $.ov.default_button_ops);
                if (typeof $.ov.default_buttons[button] != "undefined") 
                    base = $.extend(base, $.ov.default_buttons[button]);
                
                op = $.extend(base, op);
                ops.buttons[button] = op;
                
                cnt++;
            }
            
            if (!cnt)
                return "";
            
            var b_title, itype, attr;
            for (button in ops.buttons) {
                op = ops.buttons[button];
                
                b_title = (op.title != null) ? op.title : button;
                itype = (op.submit) ? "submit" : "button";
                attr = op.attr;
                if (typeof attr == "undefined")
                    attr = "";
                
                if (typeof op.key != "undefined")
                    attr+= " data-key='" + op.key + "'";
                                
                if (op.element == "input")
                    html+= "<input type='" + itype + "' class='overlay_button " + op.align + "' name='" + button + "' value='" + b_title + "' " + attr + ">"; else
                if (op.element == "a")
                    html+= "<a class='overlay_button " + op.align + ' ' + op['class'] + "' name='" + button + "' onclick='return false' " + attr + ">" + b_title + "</a>"; else
                    html+= "<div class='overlay_button " + op.align + ' ' + op['class'] + "' name='" + button + "' " + attr + ">" + b_title + "</div>"; 
            }
            html+= "</div>";
            
            return html;
        },
        gen_popup: function(overlay, ops) {
            if (!ops)
                ops = overlay.data("overlay");
                
            var popup = overlay.children(".overlay_popup");
            if (ops.fixed)
                popup.removeClass("overlay_resizable"); else
                popup.addClass("overlay_resizable"); 
            
            if (ops.maximize)
                overlay.addClass("overlay_maximized");
                
            popup.addClass(ops.mode);
            if (!ops.header)
                popup.addClass("overlay_noheader");
                
            var hc;
            if (!ops.fixed)
                hc = "overlay_hook"; else
                hc = "";                
                    
            var html = "";
            if (ops.header)
                html = "<div class='overlay_header " + hc + "' data-cfg='move'>" + ops.title + "</div>";
                
            html+= "<div class='overlay_main'>";
            
            if (ops.form != false) {
                if (ops.form == "ajax")
                    ops.form = {url : ops.url, ajax : true}; else
                if (ops.form == true)
                    ops.form = {url : ops.url}; 
                    
                var form_ops = $.extend({}, $.ov.default_form_ops, ops.form);
                    
                html+= "<form action='" + form_ops.url + "' method='" + form_ops.method + "' class='overlay_form'>";
                for (var fn in ops.form_data) 
                    html+= "<input type='hidden' name='" + fn + "' value='" + ops.form_data[fn] + "'>";
            }            
            
            html+= "<div class='overlay_content'>";
            html+= ops.content;
            html+= "</div>";
                
            html+= $.ov.gen_buttons(ops);
            if (ops.form != false) 
                html+= "</form>";
            
            html+= "</div>";
            
            if (ops.header) {
                html+= "<div class='overlay_controls'>";
                if (!ops.fixed && $.inArray("maximize", ops.controls) != -1)
                    html+= "<div class='overlay_control_max' class='overlay_control'></div>";
                if ($.inArray("close", ops.controls) != -1)
                    html+= "<div class='overlay_control_close' class='overlay_control'></div>";
                html+= "</div>";
            }
            
            if (!ops.fixed) {
                html+= "<div class='overlay_resize_tl overlay_hook overlay_resize_corner' data-cfg='1,1,0,0'></div>";
                html+= "<div class='overlay_resize_tr overlay_hook overlay_resize_corner' data-cfg='0,1,1,0'></div>";
                html+= "<div class='overlay_resize_bl overlay_hook overlay_resize_corner' data-cfg='1,0,0,1'></div>";
                html+= "<div class='overlay_resize_br overlay_hook overlay_resize_corner' data-cfg='0,0,1,1'></div>";
                
                html+= "<div class='overlay_resize_t overlay_hook overlay_resize_border' data-cfg='0,1,0,0'></div>";
                html+= "<div class='overlay_resize_l overlay_hook overlay_resize_border' data-cfg='1,0,0,0'></div>";
                html+= "<div class='overlay_resize_r overlay_hook overlay_resize_border' data-cfg='0,0,1,0'></div>";
                html+= "<div class='overlay_resize_b overlay_hook overlay_resize_border' data-cfg='0,0,0,1'></div>";
            }
            
            popup.html(html);
            $.ov.gen_zindices();
            
            /*
            popup.bind("focusin", function() {
                var overlay = $(this).parents(".overlay:first");
                
                $.ov.activate(overlay);    
            });*/
            popup.children(".overlay_hook").bind("mousedown", function(e) {
                var overlay = $(this).parents(".overlay:first");
                if (!overlay.is(".overlay_active")) 
                    $.ov.activate(overlay);    
                
                var popup = overlay.children(".overlay_popup");
                var outline = overlay.children(".overlay_outline");
                
                if (outline.hasClass("overlay_maximized")) return false;
                
                var px = e.pageX - $(this).offset().left + parseInt(popup.css("padding-left"));
                var py = e.pageY - $(this).offset().top + parseInt(popup.css("padding-top")); 
                 
                $(this).attr("data-pick", px + "," + py);
                
                var x1 = popup.offset().left - $(window).scrollLeft();
                var y1 = popup.offset().top - $(window).scrollTop();
                var x2 = $(window).width() - (x1 + popup.outerWidth());
                var y2 = $(window).height() - (y1 + popup.outerHeight());
                
                $.ov.resizing = {
                    overlay: overlay,
                    hook: $(this)
                }
                outline.css({
                    left: x1 + "px",
                    top: y1 + "px",
                    right: x2 + "px",
                    bottom: y2 + "px",
                });
            }).bind("dblclick", function() {  
                var overlay = $(this).parents(".overlay:first");
                var ops = overlay.data("overlay");
                
                if (ops.fullscreen) return;
                
                if (overlay.hasClass("overlay_maximized"))
                    $.ov.restore(overlay); else
                    $.ov.maximize(overlay);
            });
            
            var controls = popup.children(".overlay_controls");
            controls.children(".overlay_control_close").click(function() {
                $.ov.close($(this).parents(".overlay:first"));
            });
            controls.children(".overlay_control_max").click(function() {  
                var overlay = $(this).parents(".overlay:first");
                if (overlay.is(".overlay_maximized"))
                    $.ov.restore(overlay); else
                    $.ov.maximize(overlay);
            });
            
            var main = popup.children(".overlay_main");
            var form = main.children(".overlay_form");
            form.keydown(function(e) {
                var button = $(this).find(".overlay_button[data-key=" + e.keyCode + "]");
                if (button.length && !e.originalEvent.auto_complete) {
                    $(this).find("*:focus").blur();
                    
                    button.click();
                    e.preventDefault();
                    e.stopPropagation();    
                    
                    return;
                }               
                   
                if (e.keyCode == 13) {
                    if (!e.target.nodeName != 'TEXTAREA' && !e.originalEvent.auto_complete) {
                        e.preventDefault();
                        
                        return false;    
                    }
                }    
            });
            form.submit(function() {
                var overlay = $(this).parents(".overlay:first");
                var ops = overlay.data("overlay");
                if (ops.form.ajax) {
                    var data = $(this).serialize();
                    if (ops.postdata) {
                        if (data) data+= "&";
                        data+= ops.postdata;    
                    }
                    
                    overlay.children(".overlay_popup").hide();
                    overlay.children(".overlay_loader").show();
                    
                    $.post(ops.form.url, data, function(r) {
                        if (r == null) 
                            $.ov.close(overlay); else
                        if (r.close) 
                            $.ov.close(overlay, r.result); 
                        else {        
                            $.extend(ops, r);
                            
                            if (typeof r.height != "undefined")
                                ops.height = r.height;
                            if (typeof r.width != "undefined")                                
                                ops.width = r.width;

                            ops.buttons = {};
                            if (typeof r.buttons != "undefined") {
                                for (var button in r.buttons) {
                                    if (!isNaN(parseInt(button)))
                                        ops.buttons[r.buttons[button]] = {}; else
                                        ops.buttons[button] = r.buttons[button];
                                }
                            }
                    
                            $.ov.gen_popup(overlay);  
                            if (ops.show)
                                $.ov.display(overlay); else
                                $.ov.close(overlay);
                        }
                    }, "json").fail(function() { 
                        $.ov.close(overlay);
                    });
                    
                    return false;    
                } 
                
                return true;
            });
            main.find(".overlay_button").click(function() {
                var overlay = $(this).parents(".overlay:first");
                $.ov.activate(overlay);
                var name = $(this).attr("name");
                
                var op = ops.buttons[name];
                if (typeof op == "undefined") return false;
                
                if (typeof op.onClick == "function")
                    return op.onClick.apply(this); 
                if (typeof window[op.onClick] == "function")
                    return window[op.onClick].apply(this); 
                
                if (!op.submit) {
                    $.ov.close(overlay, op.result);
                    return false;
                } 
                
                if (ops.form && op.submit)
                    overlay.find(".overlay_form").append("<input type='hidden' name='" + name + "' value = '1'>");        
            });
            
            if (typeof ops.onLoaded == "function")
                ops.onLoaded.apply(overlay, [ops]);
            
            popup.show();
                    

                
            $.ov.position(overlay);  
            overlay.children(".overlay_loader").hide();                
            if (!ops.modal)
                overlay.children(".overlay_glass").hide();
        },
        activate: function(target) {
            var stack = $("#overlay_stack");
            var list = stack.children(".overlay");
            
            if (!target)         
                target = $(list.get(-1));
            
            if (!target) return false; 
            if (!target.length || target.hasClass("overlay_active")) return false;
            
            list.removeClass("overlay_active");
            target.addClass("overlay_active");
            
            var target_ind = parseInt(target.attr("data-index"));
            var max = list.length - 1;
            list.each(function() {
                var ind = parseInt($(this).attr("data-index"));
                if (ind > target_ind)
                    $(this).attr("data-index", ind - 1);
            });     
            target.attr("data-index", max);         
            
            var ops = target.data("overlay");
            if (typeof ops.onActivate == "function")
                ops.onActivate.apply(target, [ops]);
                
            //target.trigger("overlay_activate");
            
            $.ov.gen_zindices();
            
            return target;
        },
        maximize: function(overlay) {
            $.ov.activate(overlay);
            
            var ops = overlay.data("overlay");
            var popup = overlay.children(".overlay_popup");
            var main = popup.children(".overlay_main");
        
            if (!overlay.hasClass("overlay_maximized")) {
                ops.rect = {
                    x: ops.pos_x,
                    y: ops.pos_y,
                    width: main.width(),
                    height: main.height(),
                };
                console.log(ops.rect);
            };
            ops.maximize = true;
        
            overlay.addClass("overlay_maximized");
            $.ov.position(overlay);        
        
            if (typeof ops.onResize == "function")
                ops.onResize.apply(this, [ops]);    
        },
        restore: function(overlay) {
            if (!overlay.hasClass("overlay_maximized")) return;
            
            var ops = overlay.data("overlay"); 
            ops.maximize = false; 
            if (ops.rect) {
                console.log(ops.rect);
                $.ov.position(overlay, ops.rect.x, ops.rect.y, ops.rect.width, ops.rect.height);   
            }
            
            overlay.removeClass("overlay_maximized");
        },
        fadeout: function(overlay, result, data) {
            overlay.fadeOut(function() {
                $.ov.close(overlay, result, data);
            });  
        },
        close: function(overlay, result, data) {
            var ops = overlay.data("overlay");
            if (!ops) return;
            
            var fs = overlay.hasClass("overlay_fullscreen");
            
            if (ops.context)
                var context = ops.context; else
                var context = overlay;
        
            if (result === false)
                ops.onCancel.apply(context, [data, ops]); else
                ops.onSuccess.apply(context, [data, ops]);
        
            ops.onClose.apply(context, [result, data, ops]);
            overlay.remove();

            if (fs) {
                var hash = window.location.hash;
                hash = hash.replace("#ov_fs", "");
                
                window.location.hash = hash;
            }
            
            setTimeout(function() { $.ov.activate() }, 20);
        },
        resize: function(overlay, x,y, width, height) {
            var ops = overlay.data("overlay"); 
            if (!ops) return;                       
            
            var popup = overlay.children(".overlay_popup");
            var header = popup.children(".overlay_header");
            var pl = parseInt(popup.css("padding-left")) + parseInt(popup.css("border-left-width"));
            var pr = parseInt(popup.css("padding-right")) + parseInt(popup.css("border-right-width"));
            var pt = parseInt(popup.css("padding-top")) + parseInt(popup.css("border-top-width"));
            var pb = parseInt(popup.css("padding-bottom")) + parseInt(popup.css("border-bottom-width"));
            var hh = (header.length) ? parseInt(header.outerHeight()) : 0;
            
            $.ov.position(overlay, x + pl, y + pt, width - pl - pr, height - pt - pb - hh);            
        },
        position: function(overlay, x,y, width, height) {
            var ops = overlay.data("overlay"); 
            if (!ops) return;

            if (typeof width == "undefined" || width === null) width = ops.width;
            if (typeof height == "undefined" || height === null) height = ops.height;
            if (typeof x == "undefined" || x === null) x = ops.pos_x;
            if (typeof y == "undefined" || y === null) y = ops.pos_y;
            
            var popup = overlay.children(".overlay_popup");
            var header = popup.children(".overlay_header");
            var main = popup.children(".overlay_main");
            
            var content = main.find(".overlay_content");
            var buttons = main.find(".overlay_buttons");
            
            var win_h = $(window).height();
            var win_w = $(window).width();
            
            var px = parseInt(popup.css("padding-left")) + 
                     parseInt(popup.css("padding-right")) + 
                     parseInt(main.css("border-left-width")) + 
                     parseInt(main.css("border-right-width"));
            
            var py = parseInt(popup.css("padding-top")) + 
                     parseInt(popup.css("padding-bottom")) + 
                     parseInt(main.css("border-top-width")) + 
                     parseInt(main.css("border-bottom-width"));
                     
            if (width > 0 && width <= 1) width = parseInt(width * win_w);
            if (height > 0 && height <= 1) height = parseInt(height * win_h);
            
            if (ops.maximize) width = win_w;
            
            var buttons_li = buttons.children(".overlay_button");
            var buttons_w = (buttons_li.length - 1) * 20;
            for (var i = 0; i < buttons_li.length; i++)   
                buttons_w += $(buttons_li[i]).outerWidth();
                
            buttons_w+= parseInt(buttons.css("padding-left")) + parseInt(buttons.css("padding-right")); 
            if (width < buttons_w) width = buttons_w;
            
            if (width > win_w) width = win_w;
            
            if (buttons_w > width) 
                buttons.addClass("overlay_collapsed"); else
                buttons.removeClass("overlay_collapsed"); 
                
            main.css("width", width + "px");
            if (height === null)
                height = main.css("height", "auto").outerHeight(); 

            var header_h = header.outerHeight();
                
            if (height > win_h) height = win_h;
            if (ops.maximize) height = win_h - header_h;
            
            main.css("height", height + "px");
            
            if (x == "center")
                x = (win_w - width) / 2; else
            if (x == "left")
                x = 0; else
            if (x == "right")
                x = win_w - width; else
            if (x % 1 !== 0 && x >= 0 && x <= 1)
                x = ops.pos_x * win_w; 
            
            if (y == "center") 
                y = (win_h - height - header_h) / 2; else
            if (y == "top")
                y = 0; else
            if (y == "bottom")
                y = win_h - height - header_h; else
            if (y % 1 !== 0 && y >= 0 && y <= 1)
                y = ops.pos_y * win_h;  
            
            if (ops.maximize) {
                x = 0;
                y = 0;    
            }
            
            if (x > win_w - 50) x = win_w - 50;
            if (x + width < 50) x = 50 - width;
            
            if (y > win_h - 50) y = win_h - 50;
            if (y + height < 50) y = 50 - height;
                
            popup.css({
                left: x - parseInt(popup.css("padding-left")) - parseInt(popup.css("border-left-width")) + "px", 
                top: y - parseInt(popup.css("padding-top")) - parseInt(popup.css("border-top-width")) + "px"
            });
            var px = parseInt(main.css("padding-left")) + 
                     parseInt(main.css("padding-right")) + 
                     parseInt(main.css("border-left-width")) + 
                     parseInt(main.css("border-right-width"));
                     
            var py = parseInt(main.css("padding-top")) + 
                     parseInt(main.css("padding-bottom")) + 
                     parseInt(main.css("border-top-width")) + 
                     parseInt(main.css("border-bottom-width"));  
                     
            var buttons_h = buttons.outerHeight();
            
            content.css({width: width - px + "px", height: height - py - buttons_h + "px"});     
            
            if (!ops.fixed) {
                ops.pos_x = x;
                ops.pos_y = y;
                ops.width = width;
                ops.height = height;
            }                          
            
            if (typeof ops.onResize == "function")
                ops.onResize.apply(overlay, [ops]);  
        },
        display: function(overlay) {
            var ops = overlay.data("overlay");
            if (!ops) return;
            
            switch (ops.mode) {
                case null: 
                    if (ops.modal)
                        overlay.children(".overlay_glass").show();
                    break;
                case "loader":
                    overlay.children(".overlay_loader").show();
                    overlay.children(".overlay_glass").show();
                    break;
                case "glass":
                    overlay.children(".overlay_glass").show();
                    break;
                case "inline":
                case "dialog": 
                    if (ops.modal)
                        overlay.children(".overlay_glass").show();
                    break;
            }

            if (ops.fullscreen) {
                var hash = window.location.hash;
                if (hash.indexOf("#ov_fs") == -1)
                    hash+= "#ov_fs";
                
                window.location.hash = hash;
            }
            
            $.ov.activate(overlay);
            
            if (typeof ops.onDisplay == "function")
                ops.onDisplay.apply(overlay, [ops]);

            if (typeof $.ov.loaded_callback == "function") {
                $.ov.loaded_callback.apply(overlay, [ops]);
                $.ov.loaded_callback = null;
            }

            if (ops.form != false && ops.auto_focus) 
                overlay.find(":tabbable:not([readonly])").eq(0).focus();
            
            if (ops.fade) {
                var delay = 1000;
                var dur = 500;
                if ($.isArray(ops.fade)) {
                    if (ops.fade.length > 0) 
                        delay = ops.fade[0];
                    if (ops.fade.length > 1)    
                        dur = ops.fade[0];
                }    
                
                if (delay > 0) {
                    setTimeout(function() {
                        if (dur > 0)
                            overlay.fadeOut(dur, function() {
                                $.ov.close($(this));   
                            }); else
                            overlay.hide();
                    }, delay);
                }
            }
        }             
    }
    
    $.overlay_move = function(x, y) {
        var oc = $("#overlay_popup");
        var oc_w = oc.width();
        var oc_h = oc.height();
        
        var win_w = $(window).width();
        var win_h = $(window).height();
        
        if (x + oc_w > win_w)
            x = win_w - oc_w;
        if (y + oc_h > win_h)
            y = win_h - oc_h;
            
        if (x < 0)
            x = 0;
        if (y < 0) 
            y = 0;
        
        oc.css({left : parseInt(x) + 'px', top : parseInt(y) + 'px'});     
        oc.css({left : parseInt(x) + 'px', top : parseInt(y) + 'px'});     
    }; 
    $.overlay_close = function(fade) {
        var overlay = $("#overlay_stack").children(".overlay_active");
        if (!overlay.length) return;
        
        if (fade)
            $.ov.fadeout(overlay); else
            $.ov.close(overlay);
    };
    $.overlay_close_all = function() {
        $("#overlay_stack").children(".overlay").each(function() {
            $.ov.close($(this));
        });
    };
    $.overlay_maximize = function(name) {
        var ov = $.ov.get_overlay(name);
        if (!ov) ov = $("#overlay");
        
        $.ov.activate(ov);
        
        var ops = ov.data("overlay");
        var op = $("#overlay_popup");
        var oc = $("#overlay_content");
        
        if (!oc.is(".maximized")) {
            ops.rect = {
                x: op.offset().left - $(window).scrollLeft(),
                y: op.offset().top - $(window).scrollTop(),
                width: op.width(),
                height: op.height(),
            };
        };
        
        $.ov.resize($("#overlay"), 0,0, $(window).width(), $(window).height());        
        op.addClass("maximized");
        
        //$("#overlay").trigger("overlay_resize");
        ops.onResize();
    };  
    $.overlay_restore = function(name) {
        var ov = $.ov.get_overlay(name);
        if (!ov) ov = $("#overlay");
        
        $.ov.activate(ov);

        var ops = ov.data("overlay");

        var op = $("#overlay_popup");
        var oc = $("#overlay_content");
        
        if (!op.is(".maximized")) return;
        if (typeof ops.rect == "undefined") return;
        
        $.ov.resize(ov, ops.rect.x, ops.rect.y, ops.rect.width, ops.rect.height);
        op.removeClass("maximized"); 

        ops.onResize();
    }; 
    $.overlay_activate = function(name) {
        var ol = $(".overlay[name=" + name + "]");
        if (ol.length) 
            $.ov.activate(ol);    
    }
    $.overlay_ajax = function(url, form, data, options) {
        var ops = $.extend({}, {
            mode: "ajax",
            url: url,
            form: form,
            post: data,
        }, options);
        
        return $.overlay(ops);
    };
    $.overlay_show = function() {
        $.ov.display($("#overlay"));
    };
    $.overlay_message = function (text, tout, button_ok, ops) {
        if (tout === true)
            tout = 1000;
            
        ops = $.extend({}, {
            mode: "dialog",
            content: text,
            buttons: { "ok" : { align: "right" } },
            controls: [ "close" ],
            fixed: true,
            height: "80px",
            timeout: tout,
        }, ops);
        
        if (button_ok === false)
            ops.buttons = {};
            
        return $.overlay(ops);    
    };
    $.overlay_confirm = function (text, confirmed, cancelled, ops) {
        return $.overlay($.extend({}, {
            mode: "dialog",
            content: text,
            buttons: { "ok" : {}, "cancel" : {}},
            controls: [ "close" ],
            fixed: true,
            onClose: function(result) {
                if (result && typeof confirmed == "function")
                    confirmed(); else
                if (!result && typeof cancelled == "function")
                    cancelled();
                
                return true;
            }
        }, ops));    
    };
    $.overlay_loader = function(name, ops) {
        return $.overlay($.extend({}, {mode: "loader", "name": name}, ops));     
    }
    $.overlay_glass = function(name, ops) {
        return $.overlay($.extend({}, {mode: "glass", "name": name}, ops));
    } 
    $.overlay_exists = function(name) {
        return ($(".overlay[name=" + name + "]").length != 0);    
    }
    $.overlay_init = function(options) {
        $.extend($.ov.default_options, options);
    }
    $.overlay_active = function() {
        return $("#overlay_stack").children(".overlay_active");
    }
    $.overlay = function(options) {
        var ops = $.extend({}, $.ov.default_options, options);
        var ov;
        
        if (ops.fullscreen) {
            ops.resizable = false;
            ops.maximize = true;
            ops.fixed = true;
        }
        
        if (ov = $.ov.get_overlay(ops.name)) 
            return $.ov.activate(ov);
        
        var overlay = $.ov.gen_overlay(ops);
        
        if (ops.mode == null) {
            overlay.append(ops.content);
            $.ov.display(overlay);    
        } else
        if (ops.mode == "glass" || ops.mode == "loader") {
            $.ov.display(overlay);
        } else 
        if (ops.mode == 'inline' || ops.mode == 'dialog') {
            $.ov.gen_popup(overlay);
            if (ops.show)
                $.ov.display(overlay);                
        } else
        if (ops.mode == 'ajax') {
            $.ov.activate(overlay);
            overlay.children(".overlay_loader").show();
            overlay.children(".overlay_glass").show();
            overlay.children(".overlay_popup").hide();
            
            var xhr = $.post(ops.url, ops.post, function(result, s, xhr) {
                var target = xhr.overlay_target;
                var ops = target.data("overlay");
                
                if (!result) {
                    if (typeof ops.onFail == "function")
                        ops.onFail.apply(target);
                    
                    $.ov.close(target);
                    return;   
                }
                    
                $.extend(ops, result);
                
                if (typeof result.buttons != "undefined") {
                    ops.buttons = {};
                    for (var button in result.buttons) {
                        if (!isNaN(parseInt(button)))
                            ops.buttons[result.buttons[button]] = {}; else
                            ops.buttons[button] = result.buttons[button];
                    }
                }
                
                $.ov.gen_popup(target, ops);
                if (ops.show)
                    $.ov.display(target); else
                    $.ov.close(target);
            }, "json").fail(function(xhr) { 
                var target = xhr.overlay_target;
                var ops = target.data("overlay");
                
                $.ov.close(target);
                
                if (typeof ops.onFail == "function")
                    ops.onFail.apply(target);
            }).overlay_target = overlay;
        };
        
        return overlay;
    }  
    
    $.fn.overlay_loaded = function(callback) {
        $.ov.loaded_callback = callback;
    }
})(jQuery);
       

