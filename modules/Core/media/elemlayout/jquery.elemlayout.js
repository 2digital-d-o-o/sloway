(function( $ ){  
    "use strict"; 
    
    var TOP = 1;
    var RIGHT = 2;
    var BOTTOM = 4;
    var LEFT = 8;
    var TOP_LEFT = 16;
    var TOP_RIGHT = 32;
    var BOTTOM_LEFT = 64;
    var BOTTOM_RIGHT = 128;
    var LOG2 = { 0: 0, 1: 0, 2: 1, 4: 2, 8: 3 };
    var DIR_STR = ["top", "right", "bottom", "left"];
    
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
	$.elemlayout = {
		def_options: {
            treshold: 8,
            attachment: "detect",
            padding_mask: "1111",
            margin_mask: "1111",
            padding_min: [0,0,0,0],
            padding_max: [null,null,null,null],
            margin_min: [0,0,0,0],
            margin_max: [null,null,null,null],
            relative: "0000",
            labels: true,
            clip: false,
            
            onUpdate: null,
            onChange: null,
		},
        
        curr_grip: null,
        global_events: false,
        grip_options: {
            1:   { dir: [ 0,-1], rect: function(w,h,t) { return [0, -t, w, t+t] } },              
            2:   { dir: [ 1, 0], rect: function(w,h,t) { return [w-t, 0, t+t, h] } },
            4:   { dir: [ 0, 1], rect: function(w,h,t) { return [0, h-t, w, t+t] } },
            8:   { dir: [-1, 0], rect: function(w,h,t) { return [-t, 0, t+t, h] } },
            
            16:  { dir: [-1,-1], rect: function(w,h,t) { return [-t, -t, t+t, t+t] } },
            32:  { dir: [ 1,-1], rect: function(w,h,t) { return [w-t, -t, t+t, t+t] } },
            64:  { dir: [-1, 1], rect: function(w,h,t) { return [-t, h-t, t+t, t+t] } },
            128: { dir: [ 1, 1], rect: function(w,h,t) { return [w-t, h-t, t+t, t+t] } },
        },
        handle_options: {
            margin: {
                1:   { rect: function(w,h,wi,hi,p,m) { return [m.l, 0, w, m.t] },             map: [0, TOP] },
                2:   { rect: function(w,h,wi,hi,p,m) { return [m.l + w, m.t, m.r, h] },       map: [RIGHT, 0] },
                4:   { rect: function(w,h,wi,hi,p,m) { return [m.l, m.t + h, w, m.b] },       map: [0, BOTTOM] },
                8:   { rect: function(w,h,wi,hi,p,m) { return [0, m.t, m.l, h] },             map: [LEFT, 0] },
                
                16:  { rect: function(w,h,wi,hi,p,m) { return [0, 0, m.l, m.t] },             map: [LEFT, TOP] },
                32:  { rect: function(w,h,wi,hi,p,m) { return [m.l + w, 0, m.r, m.t] },       map: [RIGHT, TOP] },
                64:  { rect: function(w,h,wi,hi,p,m) { return [0, m.t + h, m.l, m.b] },       map: [LEFT, BOTTOM] },
                128: { rect: function(w,h,wi,hi,p,m) { return [m.l + w, m.t + h, m.r, m.b] }, map: [RIGHT, BOTTOM] },
            },
            padding: {
                1:   { rect: function(w,h,wi,hi,p,m) { return [m.l + p.l, m.t, wi, p.t] },                  map: [null, "top"],       ind: [null,0]},
                2:   { rect: function(w,h,wi,hi,p,m) { return [m.l + p.l + wi, m.t + p.t, p.r, hi] },       map: ["right", null],     ind: [1,null]},
                4:   { rect: function(w,h,wi,hi,p,m) { return [m.l + p.l, m.t + p.t + hi, wi, p.b] },       map: [null, "bottom"],    ind: [null,2]},
                8:   { rect: function(w,h,wi,hi,p,m) { return [m.l, m.t + p.t, p.l, hi] },                  map: ["left", null],      ind: [3,null]},
                
                16:  { rect: function(w,h,wi,hi,p,m) { return [m.l, m.t, p.l, p.t] },                       map: ["left", "top"],     ind: [3,0]},
                32:  { rect: function(w,h,wi,hi,p,m) { return [m.l + p.l + wi, m.t, p.r, p.t] },            map: ["right", "top"],    ind: [1,0]},
                64:  { rect: function(w,h,wi,hi,p,m) { return [m.l, m.t + p.t + hi, p.l, p.b] },            map: ["left", "bottom"],  ind: [3,2]},
                128: { rect: function(w,h,wi,hi,p,m) { return [m.l + p.l + wi, m.t + p.t + hi, p.r, p.b] }, map: ["right", "bottom"], ind: [1,2]},
            },
        },
        get_attr: function(elem, name, bool) {
            var r = null;
            if (elem[0].hasAttribute(name))
                r = elem[0].getAttribute(name); else
            if (elem[0].hasAttribute("data-" + name))
                r = elem[0].getAttribute("data-" + name); 
            
            if (bool) 
                return (r === 1 || r === "1" || r === "true" || r === true);
                
            if (r === null) return; 
            return r;
        },
        get_attr_def: function(elem, name, def, bool) {
            if (typeof def == "undefined") def = "";
            
            var r = null;
            if (elem[0].hasAttribute(name))
                r = elem[0].getAttribute(name); else
            if (elem[0].hasAttribute("data-" + name))
                r = elem[0].getAttribute("data-" + name); 

            if (r == null)
                r = def;
        
            if (bool) 
                return (r === "" || r === 1 || r === "1" || r === "true" || r === true);
                
            return r;
        },     

        parse_mask: function(mask) {
            var c, r = 0, m = 1;
            for (var i = 0; i < mask.length; i++) {
                c = parseInt(mask.charAt(i));
                if (c) r = r | m;
                
                m = m << 1;
            }  
            
            return r;
        },
        parse_bounds: function(b, cnt) {
            if ($.isString(b))
                b = b.split(",");
               
            if (typeof cnt == "undefined") cnt = 4;
            var res = (cnt == 2) ? [null,null] : [null,null,null,null];
            
            var pi;
            for (var i = 0; i < Math.min(b.length, cnt); i++) {
                pi = parseInt(b[i]);
                res[i] = isNaN(pi) ? null : pi;
            }
                
            return res;
        },
        calc_bounds: function(trg, ops) {
            ops.padding_min = $.elemlayout.parse_bounds(ops.padding_min);
            ops.padding_max = $.elemlayout.parse_bounds(ops.padding_max);
            ops.margin_min = $.elemlayout.parse_bounds(ops.margin_min);
            ops.margin_max = $.elemlayout.parse_bounds(ops.margin_max);
        },
        detect_attachment: function(trg, ops) {
            var width = trg.outerWidth();
            var height = trg.outerHeight();
            var offset = trg.offset();
            var res = "";

            trg.css("margin-top", "+=5");
            res+= (trg.offset().top != offset.top || trg.outerHeight() != height) ? "1" : "0";
            trg.css("margin-top", "-=5");

            trg.css("margin-right", "+=5");
            res+= (trg.offset().left != offset.left || trg.outerWidth() != width) ? "1" : "0";
            trg.css("margin-right", "-=5");
            
            trg.css("margin-bottom", "+=5");
            res+= (trg.offset().top != offset.top || trg.outerHeight() != height) ? "1" : "0";
            trg.css("margin-bottom", "-=5");
            
            trg.css("margin-left", "+=5");
            res+= (trg.offset().left != offset.left || trg.outerWidth() != width) ? "1" : "0";
            trg.css("margin-left", "-=5");
            
            return res;
        },

        update: function(res, ops) {
            var trg = ops._target;
        
            var w = trg.outerWidth();
            var h = trg.outerHeight();
            var ofs = trg.offset();

            var p = {
                t: parseInt(trg.css("padding-top")),
                b: parseInt(trg.css("padding-bottom")),
                l: parseInt(trg.css("padding-left")),
                r: parseInt(trg.css("padding-right"))
            }
        
            var m = {
                t: parseInt(trg.css("margin-top")),
                b: parseInt(trg.css("margin-bottom")),
                l: parseInt(trg.css("margin-left")),
                r: parseInt(trg.css("margin-right"))
            }
            
            res.css({
                left: ofs.left - m.l + "px",
                top: ofs.top - m.t + "px",
                width: w + m.l + m.r + "px",
                height: h + m.t + m.b + "px"
            });            
            
            var wi = trg.width();
            var hi = trg.height();
            
            var css = $.elemlayout.get_outline(trg.attr("style"));
            console.log(trg.attr("style"), css);
            
            var data,handle_rect,grip_rect,cfg,mode,label;
            res.children(".elemlayout_handle").each(function() {
                cfg = $(this).attr("data-cfg");
                mode = $(this).attr("data-mode");
                
                handle_rect = $.elemlayout.handle_options[mode][cfg].rect(w,h,wi,hi,p,m);
                $(this).css({
                    left: handle_rect[0] + "px",
                    top: handle_rect[1] + "px",   
                });
                
                $(this).attr("data-width", handle_rect[2]);
                $(this).attr("data-height", handle_rect[3]);
                
                $(this).css({                                
                    width: handle_rect[2] + "px",
                    height: handle_rect[3] + "px"
                });
                
                var label = $(this).children(".elemlayout_label");
                if (label.length) {
                    var sel = ops._mode + "-" + label.attr("data-sel");    
                    var val = css[sel];
                    if (typeof val == "undefined") val = "";
                    label.html(val);
                }
                $(this).children(".elemlayout_grip").each(function() {
                    cfg = parseInt($(this).attr("data-cfg")); 
                    grip_rect = $.elemlayout.grip_options[cfg].rect(handle_rect[2], handle_rect[3], ops.treshold / 2);
                    
                    if (grip_rect) {
                        $(this).css({
                            left: grip_rect[0] + "px",
                            top: grip_rect[1] + "px",
                            width: grip_rect[2] + "px",
                            height: grip_rect[3] + "px" 
                        });
                    }
                });
            });
        },   
        update_margin: function(handle, cfg, nw,nh, w,h, ops, event) {
            var map = $.elemlayout.handle_options["margin"][cfg].map;
      
            if (nw === null) nw = w;
            if (nh === null) nh = h;
            
            if (ops.clip && !event.ctrlKey) {
                nw = Math.round(nw / ops.clip) * ops.clip;
                nh = Math.round(nh / ops.clip) * ops.clip;
            }            
            
            var css = {};
            var p1,p2;
            
            if (map[0] && (ops._mask.margin & map[0])) {
                p1 = LOG2[map[0]];
                css["margin-" + DIR_STR[p1]] = $.toRange(parseInt(nw), ops.margin_min[p1], ops.margin_max[p1]) + "px";
            }
            
            if (map[1] && (ops._mask.margin & map[1])) {
                p2 = LOG2[map[1]];
                css["margin-" + DIR_STR[p2]] = $.toRange(parseInt(nh), ops.margin_min[p2], ops.margin_max[p2]) + "px";
            }
            
            ops._target.css(css);
        },
        update_padding: function(handle, cfg, nw,nh, w,h, ops, event) {
            var h_ops = $.elemlayout.handle_options["padding"][cfg];
            var map = h_ops.map;
            var ind = h_ops.ind;
      
            if (nw === null) nw = w;
            if (nh === null) nh = h;
            
            nw = parseInt(nw);
            nh = parseInt(nh);
            if (nw < 0) nw = 0;
            if (nh < 0) nh = 0;
            
            var parent = ops._target.parent();
            if (ops._relative[map[0]])
                nw = (100 * nw / parent.width()).toFixed(2); else
                nw = nw.toFixed(2);
                           
            if (ops._relative[map[1]])
                nh = (100 * nh / parent.width()).toFixed(2); else
                nh = nh.toFixed(2);
                
            if (ops.clip && !event.ctrlKey) {
                nw = Math.round(nw / ops.clip) * ops.clip;
                nh = Math.round(nh / ops.clip) * ops.clip;
            }
            if (ops._relative[map[0]]) nw += "%"; else nw += "px";
            if (ops._relative[map[1]]) nh += "%"; else nh += "px";
                
            var css = {};
            if (map[0]) css["padding-" + map[0]] = nw;
            if (map[1]) css["padding-" + map[1]] = nh;
            
            var pad = {};
            if (ind[0] !== null) pad[ind[0]] = nw;
            if (ind[1] !== null) pad[ind[1]] = nh;
            
            $.extend(ops._result, pad);
            ops._target.css(css);
            
            if (typeof ops.onUpdate == "function")
                ops.onUpdate.apply(ops._target, [ops._result]);
        },
        load_options: function(trg) {
            var res = {
                treshold: $.elemlayout.get_attr(trg, "treshold"),
                attachment: $.elemlayout.get_attr(trg, "attachment"),
                padding_mask: $.elemlayout.get_attr(trg, "padding-mask"),
                padding_min: $.elemlayout.get_attr(trg, "padding-min"),
                padding_max: $.elemlayout.get_attr(trg, "padding-max"),
                margin_mask: $.elemlayout.get_attr(trg, "margin-mask"),
                margin_min: $.elemlayout.get_attr(trg, "margin-min"),
                margin_max: $.elemlayout.get_attr(trg, "margin-max"),
            }    
            
            return res;
        },
        get_outline: function(style) {
            if (!style) return {};
            
            var rule, rules = style.split(";");
            var result = {
                "padding-top" : 0,
                "padding-right" : 0,
                "padding-bottom" : 0,
                "padding-left" : 0,
                "margin-top" : 0,
                "margin-right" : 0,
                "margin-bottom" : 0,
                "margin-left" : 0,
            };
            var map = ["top", "right", "bottom", "left"];
            var key,val,sub,last;
            for (var i = 0; i < rules.length; i++) {
                rule = rules[i].trim().split(":");                                
                if (rule.length > 1) {
                    key = rule[0].trim();
                    val = rule[1].trim();
                    
                    if (key == "padding" || key == "margin") {
                        sub = val.split(" ");
                        switch (sub.length) {
                            case 1:
                                result[key + "-top"] = sub[0];
                                result[key + "-right"] = sub[0];
                                result[key + "-bottom"] = sub[0];
                                result[key + "-left"] = sub[0];
                                break;
                            case 2:
                                result[key + "-top"] = sub[0];
                                result[key + "-right"] = sub[1];
                                result[key + "-bottom"] = sub[0];
                                result[key + "-left"] = sub[1];
                                break;
                            case 3:
                                result[key + "-top"] = sub[0];
                                result[key + "-right"] = sub[1];
                                result[key + "-bottom"] = sub[2];
                                result[key + "-left"] = sub[1];
                                break;
                            case 4:    
                                result[key + "-top"] = sub[0];
                                result[key + "-right"] = sub[1];
                                result[key + "-bottom"] = sub[2];
                                result[key + "-left"] = sub[3];
                                break;
                        }
                    } else 
                    if (key.indexOf("padding") === 0 || key.indexOf("margin") === 0) {
                        result[key] = val;    
                    } 
                }
            }
            
            for (var i in result)
                if (result[i] == "0" || result[i] == "0%" || result[i] == "0px")
                    result[i] = "";
            
            return result;
        },     
        gen_config: function(mode, ops) {
            var att = ops.attachment.split("");
            var dir = [
                (att[0] == "1") ? BOTTOM : TOP,
                (att[1] == "1") ? LEFT : RIGHT,
                (att[2] == "1") ? TOP : BOTTOM,
                (att[3] == "1") ? RIGHT : LEFT
            ];
            
            var mask = ops._mask[mode];
            if (!(mask & TOP)) dir[0] = 0;
            if (!(mask & LEFT)) dir[1] = 0;
            if (!(mask & BOTTOM)) dir[2] = 0;
            if (!(mask & RIGHT)) dir[3] = 0;
                                      
            var res = {                
                1: (TOP | BOTTOM) & dir[0], 
                2: (LEFT | RIGHT) & dir[1],
                4: (TOP | BOTTOM) & dir[2],
                8: (LEFT | RIGHT) & dir[3],
            
                16: dir[0] & TOP | dir[3] & LEFT,
                32: dir[0] & TOP | dir[1] & RIGHT, 
                64: dir[2] & BOTTOM | dir[3] & LEFT,
                128: dir[2] & BOTTOM | dir[1] & RIGHT,
            };

            if (dir[0] == TOP && dir[3] == LEFT) res[16] = res[16] | TOP_LEFT;
            if (dir[0] == TOP && dir[1] == RIGHT) res[32] = res[32] | TOP_RIGHT;
            if (dir[2] == BOTTOM && dir[3] == LEFT) res[64] = res[64] | BOTTOM_LEFT;
            if (dir[2] == BOTTOM && dir[1] == RIGHT) res[128] = res[128] | BOTTOM_RIGHT;
            
            if (dir[0] == BOTTOM && dir[3] == RIGHT) res[16] = res[16] | BOTTOM_RIGHT;
            if (dir[0] == BOTTOM && dir[1] == LEFT) res[32] = res[32] | BOTTOM_LEFT;
            if (dir[2] == TOP && dir[3] == RIGHT) res[64] = res[64] | TOP_RIGHT;
            if (dir[2] == TOP && dir[1] == LEFT) res[128] = res[128] | TOP_LEFT;
            
            return res;
        },
        gen_box_config: function(trg, mode, ops) {
            var dir = $.elemlayout.test_directions(trg, ops);
            
            return $.elemlayout.gen_config(dir, mode, ops)
        },
        gen_handle: function(elemlayout, cfg, cls, mask, ops) {
            var html = "<div class='elemlayout_handle elemlayout_" + cls + "' data-cfg='" + cfg + "' data-mode='" + cls + "'>";
            
            if (mask & TOP)    html+= "<div class='elemlayout_grip elemlayout_grip_t' data-cfg='" + TOP + "'></div>";    
            if (mask & RIGHT)  html+= "<div class='elemlayout_grip elemlayout_grip_r' data-cfg='" + RIGHT + "'></div>";    
            if (mask & BOTTOM) html+= "<div class='elemlayout_grip elemlayout_grip_b' data-cfg='" + BOTTOM + "'></div>";    
            if (mask & LEFT)   html+= "<div class='elemlayout_grip elemlayout_grip_l' data-cfg='" + LEFT + "'></div>";    

            if (cfg == 1) html+= "<div class='elemlayout_label' data-sel='top'>T</div>";    
            if (cfg == 2) html+= "<div class='elemlayout_label' data-sel='right'>R</div>";    
            if (cfg == 4) html+= "<div class='elemlayout_label' data-sel='bottom'>B</div>";    
            if (cfg == 8) html+= "<div class='elemlayout_label' data-sel='left'>L</div>";    

            if (mask & TOP_LEFT)     html+= "<div class='elemlayout_grip elemlayout_grip_tl' data-cfg='" + TOP_LEFT + "'></div>";    
            if (mask & TOP_RIGHT)    html+= "<div class='elemlayout_grip elemlayout_grip_tr' data-cfg='" + TOP_RIGHT + "'></div>";    
            if (mask & BOTTOM_LEFT)  html+= "<div class='elemlayout_grip elemlayout_grip_bl' data-cfg='" + BOTTOM_LEFT + "'></div>";    
            if (mask & BOTTOM_RIGHT) html+= "<div class='elemlayout_grip elemlayout_grip_br' data-cfg='" + BOTTOM_RIGHT + "'></div>";    
            html+= "</div>";
            
            var handle = $(html).appendTo(elemlayout); 
            
            handle.children().bind("mousedown", function(e) {
                $.elemlayout.curr_grip = $(this);
                
                e.stopPropagation();
                return false;
            });
        },
        
        create: function(mode, options) {
            var ops = $.extend({}, $.elemlayout.def_options, $.elemlayout.load_options($(this)), options);
            
            $.elemlayout.calc_bounds($(this), ops);
            if (ops.attachment == "detect")
                ops.attachment = $.elemlayout.detect_attachment($(this), ops); 
                
            ops._mode = mode;       
            ops._mask = {
                margin: $.elemlayout.parse_mask(ops.margin_mask),
                padding: $.elemlayout.parse_mask(ops.padding_mask)
            }
            
            var rel = $.elemlayout.parse_mask(ops.relative);
            ops._relative = {
                "top" : (rel & 1) == 1,
                "right" : (rel & 2) == 2,
                "bottom" : (rel & 4) == 4,
                "left" : (rel & 8) == 8,
            };
            ops._result = {};
            
            var cont = $("#elemlayout_cont");
            if (!cont.length) 
                cont = $("<div id='elemlayout_cont'></div>").prependTo("body");
            
            var res = $("<div class='elemlayout'></div>").appendTo(cont);
            var cfg = $.elemlayout.gen_config(mode, ops);
            
            for (var i in cfg)
                $.elemlayout.gen_handle(res, i, mode, cfg[i], ops);
                          
            ops._mode = mode;
            ops._target = $(this);
            res.data("elemlayout", ops);
            
            $.elemlayout.update(res, ops);
            
            if (!$.elemlayout.global_events) {
                $(document).bind("mouseup", function(e) {
                    var grip = $.elemlayout.curr_grip;
                    if (!grip) return;
                    
                    var handle = grip.parent();               
                    var res = handle.parents(".elemlayout:first");
                    var ops = res.data("elemlayout");
                    
                    if (typeof ops.onChange == "function") 
                        ops.onChange.apply(ops._target, [ops._result]);
                    
                    $.elemlayout.curr_grip = null; 
                    
                    e.stopPropagation();
                    return false;
                }).bind("mousemove", function(e) {
                    var grip = $.elemlayout.curr_grip;
                    if (!grip) return;
                    
                    var handle = grip.parent();               
                    var res = handle.parents(".elemlayout:first");
                    var ops = res.data("elemlayout");
                    
                    var ofs = handle.offset();
                    var w = parseInt(handle.attr("data-width"));                 
                    var h = parseInt(handle.attr("data-height"));                 
                    
                    var x1 = ofs.left;
                    var y1 = ofs.top;
                    var x2 = x1 + w;
                    var y2 = y1 + h;
                    
                    var handle_cfg = parseInt(handle.attr("data-cfg"));
                    var handle_mode = handle.attr("data-mode");
                    
                    var grip_cfg = parseInt(grip.attr("data-cfg"));
                    var dir = $.elemlayout.grip_options[grip_cfg].dir;
                    
                    var nw = null;
                    var nh = null;
                    
                    if (dir[0] == 1)
                        nw = e.pageX - x1; else
                    if (dir[0] == -1)
                        nw = x2 - e.pageX; 
                    
                    if (dir[1] == 1)
                        nh = e.pageY - y1; else
                    if (dir[1] == -1)
                        nh = y2 - e.pageY; 
                        
                    
                    
                    if (handle_mode == "margin")
                        $.elemlayout.update_margin(handle, handle_cfg, nw,nh, w,h, ops, e); else
                    if (handle_mode == "padding")
                        $.elemlayout.update_padding(handle, handle_cfg, nw,nh, w,h, ops, e);
                        
                    $.elemlayout.update(res, ops); 
                    
                    e.stopPropagation();
                    return false;
                });
                $.elemlayout.global_events = true;    
            } 
            
            return res;
        },
    };
    
    $.fn.elemlayout = function() {
        var mode = "padding";
        var ops = {};
        
        if (arguments.length > 0) 
            mode = arguments[0];
            
        if (arguments.length > 1) 
            ops = arguments[1];
        
        return $.elemlayout.create.apply(this, [mode, ops]); 
    }
})( jQuery );        


