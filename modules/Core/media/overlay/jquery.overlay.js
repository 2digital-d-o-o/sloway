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
            position: null, // fixed, movable
            resizable: true, // top, right, left, bottom
            maximize: false, // true, vert, horiz
            mode: null, // throbber, dialog, inline, iframe, ajax
            name: "",
            parent: "",
            title: "",
            content: "",
            pos_x: "center",
            pos_y: "center",
            width: 0.3, 
            height: 0.2,
            content_ratio: null,
            content_width: null,
            content_height: null,
            min_height: 100,
            min_width: 200,
            max_height: 0,
            max_width: 0,
            aspect_ratio: null,
            url: "",
            form: false,
            form_data: {},
            loader: "",                                  // loader gif url   
            elem_loader: {},
            post: {},
            buttons: {},
            push_state: false,
            close_outside: true,
            "class": "",    
            scrollable: false,        
            show: true,                                  // show automaticaly, set to false for special timeout (example image loaded)
            activate: true,
            timeout: false,
            fade: false,
            grow: false,
            header: true,
            persistent: false,
            container: "body",
            element: null,
            context: null,
            modal: true,   
            auto_focus: false,
            onResize : null,
            onDisplay : null,
            onActivate : null,
            onSuccess : function() {},
            onCancel : function() {},
            onClose : function() {},
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
        box_inner: function(elem) {
            return {
                top: parseInt(elem.css("padding-top")),
                right: parseInt(elem.css("padding-right")),
                bottom: parseInt(elem.css("padding-bottom")),
                left: parseInt(elem.css("padding-left"))
            }
        },
        box_outer: function(elem) {
            return {
                top: parseInt(elem.css("margin-top")) + parseInt(elem.css("border-top-width")),
                right: parseInt(elem.css("padding-right")) + parseInt(elem.css("border-right-width")),
                bottom: parseInt(elem.css("padding-bottom")) + parseInt(elem.css("border-bottom-width")),
                left: parseInt(elem.css("padding-left")) + parseInt(elem.css("border-left-width"))
            }
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
                        } else {
                            x1 = x1 - px;
                            y1 = y1 - py;
                        }
                            
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
            var cls = ops['class'];

            var html = "<div class='overlay " + cls + "' data-index='" + index + "' data-name='" + ops.name + "'>";
            html+= "<div class='overlay_glass' style='display: none'></div>";
            html+= "<div class='overlay_loader' style='display: none'></div>";
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
        parse_size: function(value, def, ref, min, max) {
            if (!value)
                value = def;
                
            if (value[value.length-1] == "%")
                value = parseInt(value) / 100;
            
            if (value > 0 && value <= 1)
                value = ref * value;
                
            if (value < min) value = min;
            if (max && value > max) value = max;
            if (value > ref) value = ref;
            
            return value;
        },
        clip_size: function(value, min, max, max2) {
            if (value < min) value = min;
            if (max && value > max) value = max;
            if (value > max2) value = max2;
            
            return value;
        },
        calc_size: function(popup, ops) {
            var cw,ch;
            var win_w = $(window).width();
            var win_h = $(window).height();
            var main = popup.find(".overlay_main");
            var content = main.children(".overlay_content");
            content.css("position","static");
            
            if (cw = ops.content_width) {
                if (cw >= 0 && cw <= 1) cw = win_w * cw;
                
                content.css("width", cw + "px");
                if (ops.content_ratio && !ops.content_height)
                    content.css("height", cw * ops.content_ratio + "px");
            }
            if (ch = ops.content_height) {
                if (ch >= 0 && ch <= 1) ch = win_h * ch;

                content.css("height", ch + "px");
                if (ops.content_ratio && !ops.content_width) 
                    content.css("width", ch / ops.content_ratio + "px");
            }
            var header = popup.find(".overlay_header");
            var width = $.ov.parse_size(ops.width, main.outerWidth(), win_w, ops.min_width, ops.max_width);    
            var height = $.ov.parse_size(ops.height, main.outerHeight() + header.outerHeight(), win_h, ops.min_height, ops.max_height);
            
            content.css({height: "auto", width: "auto", position: "absolute"});
            
            return [width, height];
        },
        gen_options: function(options) {
            var ops = $.extend({}, $.ov.default_options, options);
            if (ops.position === null)
                ops.position == "movable";
            
        //  Maximize
            var maximize = { vert: false, horiz: false }
            if (ops.maximize === true || ops.resizable === "all") {
                maximize = { vert: true, horiz: true }
            } else
            if ($.isString(ops.maximize))
                maximize = {
                    vert: ops.maximize.includes("vert"),
                    horiz: ops.maximize.includes("horiz"),
                }
            ops.maximize = maximize;
            
        //  Resizable    
            var resizable = { top: false, left: false, right: false, bottom: false };
            if (ops.resizable === true || ops.resizable === "all") {
                resizable = { top: true, left: true, right: true, bottom: true }
            } else
            if ($.isString(ops.resizable))
                resizable = {
                    top: ops.resizable.includes("top"),
                    left: ops.resizable.includes("left"),
                    bottom: ops.resizable.includes("bottom"),
                    right: ops.resizable.includes("right"),
                }
            ops.resizable = resizable;
            
            if (maximize.vert) {
                ops.resizable.top = false;
                ops.resizable.bottom = false;
                ops.pos_y = 0;
                ops.height = 1;
                ops.position = "fixed";
            }
            if (maximize.horiz) {
                ops.resizable.left = false;
                ops.resizable.right = false;
                ops.pos_x = 0;
                ops.width = 1;
                ops.position = "fixed";
            }
            
            return ops;
        },
        gen_popup: function(overlay, ops) {
            if (!ops)
                ops = overlay.data("overlay");
                
            var popup = overlay.children(".overlay_popup");
            if (ops.position != "fixed")
                popup.addClass("overlay_resizable"); else
                popup.removeClass("overlay_resizable");
        
            if (ops.maximize.vert)
                popup.addClass("overlay_maximized_v");
            if (ops.maximize.horiz)
                popup.addClass("overlay_maximized_h");
                
            popup.addClass(ops.mode);
            if (!ops.header)
                popup.addClass("overlay_noheader");
                
            var hc;
            if (ops.position != "fixed")
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
            
            var oc_class = (ops.scrollable) ? "overlay_scrollable" : "";
            
            html+= "<div class='overlay_content " + oc_class + "'>";
            html+= ops.content;
            html+= "</div>";
                
            html+= $.ov.gen_buttons(ops);
            if (ops.form != false) 
                html+= "</form>";
            
            html+= "</div>";
            
            if (ops.header) {
                html+= "<div class='overlay_controls'>";
                html+= "<div class='overlay_control_close' class='overlay_control'></div>";
                html+= "</div>";
            }
            if (ops.resizable !== false) {     
                var rs_t = ops.resizable.top;
                var rs_l = ops.resizable.left;
                var rs_r = ops.resizable.right;
                var rs_b = ops.resizable.bottom;

                if (rs_t && rs_l) html+= "<div class='overlay_resize_tl overlay_hook overlay_resize_corner' data-cfg='1,1,0,0'></div>";
                if (rs_t && rs_r) html+= "<div class='overlay_resize_tr overlay_hook overlay_resize_corner' data-cfg='0,1,1,0'></div>";
                if (rs_b && rs_l) html+= "<div class='overlay_resize_bl overlay_hook overlay_resize_corner' data-cfg='1,0,0,1'></div>";
                if (rs_b && rs_r) html+= "<div class='overlay_resize_br overlay_hook overlay_resize_corner' data-cfg='0,0,1,1'></div>";
                
                if (rs_t) html+= "<div class='overlay_resize_t overlay_hook overlay_resize_border' data-cfg='0,1,0,0'></div>";
                if (rs_l) html+= "<div class='overlay_resize_l overlay_hook overlay_resize_border' data-cfg='1,0,0,0'></div>";
                if (rs_r) html+= "<div class='overlay_resize_r overlay_hook overlay_resize_border' data-cfg='0,0,1,0'></div>";
                if (rs_b) html+= "<div class='overlay_resize_b overlay_hook overlay_resize_border' data-cfg='0,0,0,1'></div>";
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
                var content = overlay.find(".overlay_content");
                
                var px = e.pageX - $(this).offset().left + parseInt(popup.css("padding-left"));
                var py = e.pageY - $(this).offset().top + parseInt(popup.css("padding-top")); 
                 
                $(this).attr("data-pick", px + "," + py);
                
                var x1 = popup.offset().left - $(window).scrollLeft();
                var y1 = popup.offset().top - $(window).scrollTop();
                var x2 = $(window).width() - (x1 + popup.outerWidth());
                var y2 = $(window).height() - (y1 + popup.outerHeight());
                
                $.ov.resizing = {
                    overlay: overlay,
                    delta_x: popup.outerWidth() - content.outerWidth(),
                    delta_y:popup.outerHeight() - content.outerHeight(),
                    hook: $(this)
                }
                outline.css({
                    left: x1 + "px",
                    top: y1 + "px",
                    right: x2 + "px",
                    bottom: y2 + "px",
                });
            });
            
            var controls = popup.children(".overlay_controls");
            controls.children(".overlay_control_close").click(function() {
                $.ov.close($(this).parents(".overlay:first"));
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
                    
            var x,y;
            var size = $.ov.calc_size(popup, ops);
            var width = size[0];
            var height = size[1];
            
            if (ops.pos_x == "center")
                x = ($(window).width() - width) / 2; else
            if (ops.pos_x == "left")
                x = 0; else
            if (ops.pos_x == "right")
                x = $(window).width() - width; else
            if (ops.pos_x % 1 !== 0 && ops.pos_x >= 0 && ops.pos_x <= 1)
                x = ops.pos_x * $(window).width(); else
                x = ops.pos_x;
            
            if (ops.pos_y == "center")
                y = ($(window).height() - height) / 2; else
            if (ops.pos_y == "top")
                y = 0; else
            if (ops.pos_y == "bottom")
                y = $(window).height() - height; else
            if (ops.pos_y % 1 !== 0 && ops.pos_y >= 0 && ops.pos_y <= 1)
                y = ops.pos_y * $(window).height(); else
                y = ops.pos_y;
                
            $.ov.resize(overlay, x,y, width, height);  
            overlay.children(".overlay_loader").hide();                
            if (!ops.modal)
                overlay.children(".overlay_glass").hide();
        },
        activate: function(target) {
            var stack = $("#overlay_stack");
            var list = stack.children(".overlay");
            
            if (!target)         
                target = list.get(-1);
            
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
            
            /*
            if (ops.push_state && ops.name) {
                var url = $.ov.parse_url(window.location.href);
                url.query.w = ops.name;
                
                $("#overlay_stack").pushState($.ov.build_url(url), "", ops.name);
            } */
            
            return target;
        },
        close: function(overlay, result, data) {
            var ops = overlay.data("overlay");
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
            
            $.ov.activate();
        },
        resize: function(overlay, x,y, width, height) {
            var ops = overlay.data("overlay"); 
            var popup = overlay.children(".overlay_popup");
            var header = popup.children(".overlay_header");
            var main = popup.children(".overlay_main");
            
            var content = main.find(".overlay_content");
            var buttons = main.find(".overlay_buttons");
            
            var win_h = $(window).height();
            var win_w = $(window).width();
            
            var dw = parseFloat(content.css("padding-left")) + 
                     parseFloat(content.css("padding-right")) + 
                     parseFloat(main.css("border-left-width")) + 
                     parseFloat(main.css("border-right-width"));
                     
            var dh = parseFloat(content.css("padding-top")) + 
                     parseFloat(content.css("padding-bottom")) + 
                     parseFloat(main.css("border-top-width")) + 
                     parseFloat(main.css("border-bottom-width")) + 
                     header.outerHeight() + 
                     buttons.outerHeight();
  
                   
            var x1 = x;
            var y1 = y;
            
            if (!overlay.hasClass("overlay_maximized")) {
                if (width < ops.min_width) width = ops.min_width;
                if (height < ops.min_height) height = ops.min_height;
                
                if (width > win_w)
                    width = win_w;
            }
            
            var popup_css = {
                left: x1 + "px", 
                top: y1 + "px"
            }
                
            if (!ops.maximize.vert) 
                popup_css.height = height + "px";
            if (!ops.maximize.horiz) 
                popup_css.width = width + "px";
            
            popup.css(popup_css);
            
            var content_css = {};
            content_css.top = header.outerHeight() + "px";
            content_css.bottom = buttons.outerHeight() + "px";
            content.css(content_css);
            
            if (typeof ops.onResize == "function")
                ops.onResize.apply(overlay, [ops]);              
        },
        display: function(overlay) {
            var ops = overlay.data("overlay");
            
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

            $.ov.activate(overlay);
            
            if (typeof ops.onDisplay == "function")
                ops.onDisplay.apply(overlay, [ops]);

            if (typeof $.ov.loaded_callback == "function") {
                $.ov.loaded_callback.apply(overlay, [ops]);
                $.ov.loaded_callback = null;
            }

            if (ops.form != false && ops.auto_focus) 
                overlay.find(":tabbable:not([readonly])").eq(0).focus();
            
        /*
            if (ops.maximize) {
                if (ops.maximize === true) 
                    ops.maximize = "vert,horiz";
                
                var v = ops.maximize.includes("vert");
                var h = ops.maximize.includes("horiz");
                $.ov.maximize(overlay, v, h);
            }
             * 
         */
            
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
    $.overlay_close = function() {
        var overlay = $("#overlay_stack").children(".overlay_active");
        if (overlay.length)
            $.ov.close(overlay);
    };
    $.overlay_close_all = function() {
        $("#overlay_stack").children(".overlay").each(function() {
            $.ov.close($(this));
        });
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
            buttons: { "ok" : {} },
            controls: [ "close" ],
            position: "movable",
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
            position: "movable",
            onClose: function(result) {
                if (result && typeof confirmed == "function")
                    confirmed(); else
                if (!result && typeof cancelled    == "function")
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
        var ops = $.ov.gen_options(options);
        var ov;
        
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
            
            $.post(ops.url, ops.post, function(r) {
                if (!r) {
                    $.ov.close(overlay);
                    return;   
                }
                    
                $.extend(ops, r);
                
                if (typeof r.buttons != "undefined") {
                    ops.buttons = {};
                    for (var button in r.buttons) {
                        if (!isNaN(parseInt(button)))
                            ops.buttons[r.buttons[button]] = {}; else
                            ops.buttons[button] = r.buttons[button];
                    }
                }
                
                $.ov.gen_popup(overlay, ops);
                if (ops.show)
                    $.ov.display(overlay); else
                    $.ov.close(overlay);
            }, "json").fail(function() { 
                $.ov.close(overlay);
            });
        };
        
        return overlay;
    }  
    
    $.fn.overlay_loaded = function(callback) {
        $.ov.loaded_callback = callback;
    }
})(jQuery);
       

