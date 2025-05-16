(function( $ ){   
    $.isArray = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object Array]');
    }
    $.isString = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object String]');
    }
    $.isObject = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object Object]');
    }    
    $.toRange = function(v, min, max) {
        if (v === null) return null;
        
        if (min !== null && v < min) v = min;
        if (max !== null && v > max) v = max;
            
        return v;  
    },    
    $.carousel = {
        global_events: false,
        def_options: {
            height: "items",
            item_width: 100,     
            interval: false,
            speed: 200,
            index: 0,
            select: 0,
            next: null,
            prev: null,  
            observer: true,
            orientation: "horizontal",
            infinite: false,
            ins_expand: true, // expand items when insuficient
            active_pos: "first", // first, last, center   
            disabledClass: "disabled",
            beforeUpdate: null, 
            needsUpdate: null,
            onUpdate: null,
            onSlideStart: null,
            onSlideEnd: null,
            classes: null,
            
            counter: 0,
        },

        global_events: false,
        instances: [],
        observing: false,
                   
        mod: function(i, cnt) {
            if (i < 0) 
                return cnt - (-i-1) % cnt - 1; else
                return i % cnt;
        },   
        insert_items: function(ul, i1, i2, li_w, li_h, ops) {
            var li,ch = ul.children("li");
            var first_ind = null;
            for (var i = 0; i < ch.length; i++) {
                li = $(ch[i]);
                if (first_ind === null)
                    first_ind = parseInt(li.attr("data-index"));
                    
                li.addClass("detach");
            }     
            
            var new_items = $();   
            var queue = {};
            var id;
            for (var i = i1; i < i2; i++) {
                ind = $.carousel.mod(i, ops.items.length);
                
                if (ops.cache[ind]) {
                    var li = ul.children("li[data-index=" + i + "]");
                    if (li.length) {
                        cl = li.removeClass("detach"); 
                    } else
                    if (first_ind !== null && i < first_ind)
                        cl = ops.cache[ind].clone(true).removeClass("detach").prependTo(ul); else 
                        cl = ops.cache[ind].clone(true).removeClass("detach").appendTo(ul);
                    
                    id = cl.attr("data-id");
                } else {
                    cl = $("<li>" + ops.items[ind].content + "</li>");
                    cl.attr(ops.items[ind].attributes).addClass("carousel_item");
                    
                    if (first_ind !== null && i < first_ind)
                        cl.prependTo(ul).attr("data-orig", ind); else
                        cl.appendTo(ul).attr("data-orig", ind);
                    
                    id = "ITM_" + ops.counter++;    
                    cl.attr("data-id", id); 
                    new_items = new_items.add(cl);
                    
                    queue[ind] = cl;
                }
                cl.attr("data-index", i);
                cl.attr("data-i", i);
                cl.css({
                    "width" : li_w, 
                    "height" : li_h,
                });     
            }
            ul.children("li.detach").detach();
            for (var i in queue)     
                ops.cache[i] = queue[i];
                
            return new_items;
        },
        tag_items: function(ul, i1,i2, ind1,ind2, ops) {
            var vis1 = i1;
            var vis2 = i2 - 1;
            if (ind2 > ind1) vis1+= ind2 - ind1;
            if (ind2 < ind1) vis2+= ind2 - ind1;
            
            ul.children("li").removeClass("first last visible selected");
            var li;
            for (var i = vis1; i <= vis2; i++) {
                li = ul.children("li[data-index=" + i + "]");
                li.addClass("visible");
                
                if (ops.selected === i)
                    li.addClass("selected");
                
                if (i == vis1) li.addClass("first");
                if (i == vis2)
                    li.addClass("last");
            }  
            
            return [vis1,vis2];   
        }, 
        update: function(from, to, mode, callback, callback_data) {
            var ops = $(this).data("carousel");     
            
            if ($(this).hasClass("sliding")) return;
            
            $(this).addClass("sliding");
            if (typeof ops.beforeUpdate == "function")
                ops.beforeUpdate.apply(this, [ops]);

               
            if (ops.orientation == "vertical") 
                $.carousel.update_vertical.apply(this, [from, to, mode, ops]); else
                $.carousel.update_horizontal.apply(this, [from, to, mode, ops]); 

            if (typeof callback == "function")
                callback.apply(this, [callback_data]);
        },
        update_horizontal: function(from, to, mode, ops, callback, callback_data) {   
            var width = $(this).innerWidth();
            
            if (ops.height == "items") {
                $(this).css("height", ops.natural_item_height + "px");
            } else 
            if (ops.height != "auto") {
                $(this).css("height", parseInt(ops.height) + "px");
            }
            
            var height = $(this).height();
            if (ops.classes) {
                var cls = "";
                var all_cls = "";
                for (var h in ops.classes) {
                    all_cls+= " " + ops.classes[h];
                    if (height < h) {
                        cls = ops.classes[h];
                    }
                }                   
                $(this).removeClass(all_cls);
                $(this).addClass(cls); 
            }
            
            var clip = $(this).children(".carousel_clip");
            var ul = clip.children("ul");
            var ul_w = 0;
            
            clip.css("height", height + "px");
            
            var cnt = parseInt(width / ops.item_width);
            ops.slots = cnt;
            if (!ops.ins_expand && ops.items.length < cnt && !ops.infinite) {
                if (ops._count === null) {
                    $(this).removeClass("sliding");
                    return false;
                }
                ops._count = null;
                
                var build = $.carousel.insert_items(ul, 0, ops.items.length, ops.item_width + "px", height + "px", ops);  
                
                $(ops.prev).addClass("disabled"); 
                $(ops.next).addClass("disabled");  
                                  
                if (typeof ops.onUpdate == "function" && build.length)
                    ops.onUpdate.apply(this, [build]);
                             
                return;
            } 

            if (cnt == 0) cnt = 1;
            if (ops._count === cnt && from == to) {
                $(this).removeClass("sliding");
                
                $(this).find(".carousel_item").css("height", height + "px");
                
                return false;
            } 
            ops._count = cnt;
            
            cnt = Math.min(cnt, ops.items.length);
            
            item_width = width / cnt;  
            clip.css({"left" : 0, "right" : 0, "top" : 0, "bottom" : 0}); 
            
            var i1,i2;
            var ind1 = from;
            var ind2 = to;      
            
            var clip_left = false;
            var clip_right = false;
            if (!ops.infinite) {
                if (ind1 < 0) ind1 = 0;
                if (ind2 < 0) ind2 = 0;
                
                if (ind1 >= ops.items.length - cnt) ind1 = ops.items.length - cnt;    
                if (ind2 >= ops.items.length - cnt) ind2 = ops.items.length - cnt;  
            }
                
            if (ind2 < ind1) {
                i1 = ind2;
                i2 = ind1 + cnt;   
            } else {     
                i1 = ind1;
                i2 = ind2 + cnt;
            }
            ops.index = ind2;   
            ops._width = width;
            
            var ind, cl;      
            if (i1 >= i2) i2 = i1 + 1;    
            
            var step = 100 * item_width / width;
            var ul_w = 100 * (i2-i1) * item_width / width;
            var li_w = 100 / (i2 - i1);                 
            
            ul.css("width", ul_w + "%");
            var build = $.carousel.insert_items(ul, i1, i2, li_w + "%", height + "px", ops); 
            
            var slide_ops = {
                from: from,
                to: to,
                auto: mode
            };
            
            var anim_ops = {
                duration: ops.speed,
                complete: function() {
                    if (mode !== null && typeof ops.onSlideEnd == "function")
                        ops.onSlideEnd.apply(this, [slide_ops]);
                
                    $(this).closest(".carousel").removeClass("sliding"); 
                }    
            }
            
            ul.css("top", 0).css("left", 0);
            
            if (ind1 > ind2) {
                ul.css("left", (ind2-ind1) * step + "%");
                ul.animate({"left" : 0}, anim_ops);
            } else {              
                ul.animate({"left" : "+=" + (ind1-ind2) * step + "%"}, anim_ops); 
            }
            
            var visible = $.carousel.tag_items(ul, i1,i2, ind1,ind2, ops);
            if (!ops.infinite) {
                if (visible[0] == 0) 
                    $(ops.prev).addClass("disabled"); else
                    $(ops.prev).removeClass("disabled"); 

                if (visible[1] == ops.items.length-1) 
                    $(ops.next).addClass("disabled"); else
                    $(ops.next).removeClass("disabled"); 
            }
            
            
            if (typeof ops.onUpdate == "function" && build.length)
                ops.onUpdate.apply(this, [build]);
                             
            if (mode !== null && typeof ops.onSlideStart == "function")
                ops.onSlideStart.apply(this, [slide_ops]);
        },
        update_vertical: function(from, to, mode, ops, callback, callback_data) {   
            var height = $(this).innerHeight();
            
            if (ops.width == "items") {
                $(this).css("width", ops.natural_item_width + "px");
            } else 
            if (ops.width != "auto") {
                $(this).css("width", parseInt(ops.width) + "px");
            }
            
            var width = $(this).width();
            if (ops.classes) {
                var cls = "";
                var all_cls = "";
                for (var h in ops.classes) {
                    all_cls+= " " + ops.classes[h];
                    if (height < h) {
                        cls = ops.classes[h];
                    }
                }                   
                $(this).removeClass(all_cls);
                $(this).addClass(cls); 
            }
            
            var clip = $(this).children(".carousel_clip");
            var ul = clip.children("ul");
            var ul_h = 0;
            
            clip.css("width", width + "px");
            
            var cnt = parseInt(height / ops.item_height);
            ops.slots = cnt;
            if (!ops.ins_expand && ops.items.length < cnt && !ops.infinite) {
                if (ops._count === null) {
                    $(this).removeClass("sliding");
                    return false;
                }
                ops._count = null;
                
                var build = $.carousel.insert_items(ul, 0, ops.items.length, ops.item_height + "px", width + "px", ops);  
                
                $(ops.prev).addClass("disabled"); 
                $(ops.next).addClass("disabled");  
                                  
                if (typeof ops.onUpdate == "function" && build.length)
                    ops.onUpdate.apply(this, [build]);
                             
                return;
            } 

            if (cnt == 0) cnt = 1;
            if (ops._count === cnt && from == to) {
                $(this).removeClass("sliding");
                
                $(this).find(".carousel_item").css("width", width + "px");
                
                return false;
            } 
            ops._count = cnt;
            
            cnt = Math.min(cnt, ops.items.length);
            
            item_height = height / cnt;  
            clip.css({"left" : 0, "right" : 0, "top" : 0, "bottom" : 0}); 
            
            var i1,i2;
            var ind1 = from;
            var ind2 = to;      
            
            var clip_left = false;
            var clip_right = false;
            if (!ops.infinite) {
                if (ind1 < 0) ind1 = 0;
                if (ind2 < 0) ind2 = 0;
                
                if (ind1 >= ops.items.length - cnt) ind1 = ops.items.length - cnt;    
                if (ind2 >= ops.items.length - cnt) ind2 = ops.items.length - cnt;  
            }
                
            if (ind2 < ind1) {
                i1 = ind2;
                i2 = ind1 + cnt;   
            } else {     
                i1 = ind1;
                i2 = ind2 + cnt;
            }
            ops.index = ind2;   
            ops._height = height;
            
            var ind, cl;      
            if (i1 >= i2) i2 = i1 + 1;    
            
            var step = 100 * item_height / height;
            var ul_h = 100 * (i2-i1) * item_height / height;
            var li_h = 100 / (i2 - i1);                 
            
            ul.css("height", ul_h + "%");
            var build = $.carousel.insert_items(ul, i1, i2, width + "px", li_h + "%", ops); 
            
            var slide_ops = {
                from: from,
                to: to,
                auto: mode
            };
            
            var anim_ops = {
                duration: ops.speed,
                complete: function() {
                    if (mode !== null && typeof ops.onSlideEnd == "function")
                        ops.onSlideEnd.apply(this, [slide_ops]);
                
                    $(this).closest(".carousel").removeClass("sliding"); 
                }    
            }
            
            ul.css("top", 0).css("left", 0);
            
            if (ind1 > ind2) {
                ul.css("top", (ind2-ind1) * step + "%");
                ul.animate({"top" : 0}, anim_ops);
            } else {              
                ul.animate({"top" : "+=" + (ind1-ind2) * step + "%"}, anim_ops); 
            }
            
            var visible = $.carousel.tag_items(ul, i1,i2, ind1,ind2, ops);
            if (!ops.infinite) {
                if (visible[0] == 0) 
                    $(ops.prev).addClass("disabled"); else
                    $(ops.prev).removeClass("disabled"); 

                if (visible[1] == ops.items.length-1) 
                    $(ops.next).addClass("disabled"); else
                    $(ops.next).removeClass("disabled"); 
            }
            
            
            if (typeof ops.onUpdate == "function" && build.length)
                ops.onUpdate.apply(this, [build]);
                             
            if (mode !== null && typeof ops.onSlideStart == "function")
                ops.onSlideStart.apply(this, [slide_ops]);
        },
        stop: function() {
            var ops = $(this).data("carousel");
            
            clearTimeout(ops.timeout);
            
            return $(this);
        },
        start: function() {
            var ops = $(this).data("carousel");
            
            if (!ops.interval) return;  
            
            var src = this;
            if (ops.interval) {
                ops.timeout = setTimeout(function() {
                    $.carousel.timeout.apply(src);     
                }, ops.interval);
            }   
            
            return $(this);   
        },
        timeout: function() { 
            var ops = $(this).data("carousel");
                
            if (ops.slots < ops.items.length) {
                var old_index = ops.index;
                $.carousel.update.apply(this, [ops.index, ops.index+1, true]);
            }
            $.carousel.start.apply(this);      
        },
        attributes: function(elem) {
            elem = elem[0];
            
            var attr, res = {};
            for (var i = 0; i < elem.attributes.length; i++) {
                attr = elem.attributes[i];
                if (attr.specified)
                    res[attr.name] = attr.value;
            }
            
            return res;
        },
        orientation: function(dir) {
            var ops = $(this).data("carousel");
                
            ops.orientation = dir;
            if (dir == "vertical")
                $(this).addClass("carousel_vertical"); else
                $(this).removeClass("carousel_vertical");
                
            return $(this);
        },        
        observer: function() {
            var instance, ops;
            var count = 0;
            for (var i = 0; i < $.carousel.instances.length; i++) {
                instance = $.carousel.instances[i];
                if (!instance) {
                    delete $.carousel.instances[i];
                    continue;
                }
                
                ops = instance.data("carousel");
                if (!ops) {
                    delete $.carousel.instances[i];
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
                    $.carousel.update.apply(instance, [ops.index, ops.index, null]); 
                }
            }    
            
             if (count) 
                $.carousel.observing = setTimeout($.carousel.observer, 500);
        },
        create: function(options) {
            var ops = $.extend({}, $.carousel.def_options, options);     
            ops.items = [];           
            ops.timeout = null;
            ops.cache = {};
                
            if (ops.interval) ops.interval = parseInt(ops.interval);
            if (ops.speed) ops.speed = parseInt(ops.speed);
            
            ops.selected = ops.select;
                
            $(this).data("carousel", ops);
                
            var list = $(this).children("ul");
            var item,items = list.children("li");
            ops.natural_item_height = $(items[0]).outerHeight();
            ops.natural_item_width = $(items[0]).outerWidth();
            
            $(this).addClass("carousel");
            if (ops.orientation == "vertical")
                $(this).addClass("carousel_vertical");
            
            list.addClass("carousel_items");
            list.wrap("<div class='carousel_clip'></div>");       
            list.contents().filter(function() { return this.nodeType == 3; }).remove();  
            
            if (!items.length) return;     
            
            var attr = {};
            for (var i = 0; i < items.length; i++) {
                item = $(items[i]);
                ops.items.push({
                    attributes: $.carousel.attributes(item),
                    content: item.html()
                });
            }
            list.html("");
            
            if (ops.items.length < 1)
                ops.infinite = false;
            if (!ops.infinite)
                ops.interval = 0;
            
            
            if (typeof Hammer == "function") {
                var hammer = new Hammer($(this)[0], { domEvents: true });
                hammer.get('swipe').set({ direction: Hammer.DIRECTION_ALL });
                hammer.on('swipe', function(ev) {
                    var trg = $(ev.target).closest(".carousel");
                    var ops = trg.data("carousel"); 
                    
                    var sign, delta;
                    if (ops.orientation == "vertical") {
                        sign = Math.sign(ev.velocityY);
                        delta = parseInt(Math.abs(ev.velocityY));
                    } else {
                        sign = Math.sign(ev.velocityX);
                        delta = parseInt(Math.abs(ev.velocityX));
                    }
                    if (delta < 1) delta = 1;
                    
                    if (delta > ops.slots) delta = ops.slots;
                    
                    $.carousel.stop.apply(trg);
                    $.carousel.update.apply(trg, [ops.index, ops.index - sign * delta, false]);
                    $.carousel.start.apply(trg);
                });
            }  
            
            /*
            $(this).bind("mousedown", function(e) {
                if (e.which != 1) return;
                
                var ul = $(this).find(".carousel_items");
                var item_w = ul.children("li:first").width();
                
                $.carousel.dragging = {
                    target: $(this),
                    item_w: item_w,
                    pos_x: e.clientX,
                    pos_y: e.clientY
                }
            });
            if (!$.carousel.global_events) {
                $(document).mousemove(function(e) {
                    var d = $.carousel.dragging;
                    
                    if (!d) return;                    
                    var delta = (e.clientX - d.pos_x) / d.item_w;
                    $.carousel.stop.apply(d.target);
                    $.carousel.update.apply(d.target, [ops.index, ops.index - delta, false]);
                    $.carousel.start.apply(d.target);
                }).mouseup(function(e) {
                    $.carousel.dragging = null;
                });
                    
                $.carousel.global_events = true;
            }    */         
            $(ops.next).data("carousel_target", this).click(function() {
                var trg = $(this).data("carousel_target");  
                var ops = trg.data("carousel");
                var old_index = ops.index;
                
                $.carousel.stop.apply(trg);
                $.carousel.update.apply(trg, [ops.index, ops.index+1, false]);
                $.carousel.start.apply(trg);
                
                return false;       
            });
            $(ops.prev).data("carousel_target", this).click(function() {
                var trg = $(this).data("carousel_target");    
                var ops = trg.data("carousel");
                var old_index = ops.index;
                
                $.carousel.stop.apply(trg);
                $.carousel.update.apply(trg, [ops.index, ops.index-1, false]);
                $.carousel.start.apply(trg);
                
                return false;       
            });
            
            $.carousel.update.apply(this, [ops.index,ops.index, null]);
            $.carousel.start.apply(this);
            
            ops._width = $(this).width();
            ops._height = $(this).height();
            if (ops.observer) {
                $.carousel.instances.push($(this));
                //console.log("carousel.start observing");
                
                clearTimeout($.carousel.observing);
                $.carousel.observing = setTimeout($.carousel.observer, 500);
            }  
            
            return $(this);
        },
        remove: function(indices, callback, callback_data) {    
            var ops = $(this).data("carousel");
            var new_list = []; 
            for (var i in ops.items) {
                if (indices.indexOf(i) == -1) 
                    new_list.push(ops.items[i]);    
            }
            
            ops.items = new_list;    
            ops.cache = {};
            ops._count = 0;
            $.carousel.update.apply(this, [ops.index, ops.index,  null, callback, callback_data]);                        
        },  
        result: function(obj, callback, callback_data) {
            if (typeof callback == "function")
                callback.apply(obj, [callback_data]);
                
            return $(obj);
        }
    }

    $.fn.carousel = function(method, arg, callback, callback_data) {
        if (!this instanceof jQuery) return this;
        
        if (!$(this).length) return $(this);
        
        if ($.isObject(method)) 
            return $.carousel.create.apply(this, [method]); 
            
        if ($(this).hasClass("carousel")) {
            if (method == "remove") {
                $.carousel.remove.apply(this, [arg, callback, callback_data]);
            } else
            if (method == "update") {
                var ops = $(this).data("carousel"); 
                ops._width = $(this).width();
                ops._height = $(this).height();
                $.carousel.update.apply(this, [ops.index,ops.index, null, callback, callback_data]);
            } else 
            if (method == "stop") {
                $.carousel.stop.apply(this); 
            } else
            if (method == "start" || method == "continue") {
                $.carousel.start.apply(this); 
            } else
            if (method == "orientation") {
                $.carousel.orientation.apply(this, [arg]); 
            } else           
            if (method == "state") {
                var ops = $(this).data("carousel"); 
                return {
                    index: ops.index,
                    slots: ops.slots,
                    count: ops.items.count    
                }
            } else
            if (method == "slide") {
                var ops = $(this).data("carousel"); 
                $.carousel.stop.apply(this);
                $.carousel.update.apply(this, [ops.index, ops.index + arg, false, callback, callback_data]);
                $.carousel.start.apply(this);
            } else
            if (method == "slide_to" && typeof arg != "undefined") {
                var ops = $(this).data("carousel"); 
                var to, from = ops.index;
                var by_index = false;
                
                if (arg[0] == "#") {
                    arg = arg.substring(1);
                    if (ops.infinite)
                        by_index = true;    
                }
                arg = parseInt(arg);
                
                if (!by_index && (arg < 0 || arg >= ops.items.length)) 
                    return $.carousel.result(this, callback, callback_data);

                if (by_index) {
                    to = arg;    
                } else
                if (ops.infinite) {
                    var curr = ops.index % ops.items.length;
                    if (curr == arg) 
                        return $.carousel.result(this, callback, callback_data);
                    
                    var left = arg;
                    var right = arg;
                    if (left > curr) left-= ops.items.length;
                    if (right < curr) right+= ops.items.length;
                    
                    var dl = curr - left;
                    var dr = right - curr;
                    
                    if (dl < dr) 
                        to = ops.index - dl; else
                        to = ops.index + dr;
                } else 
                    to = arg;
                
                if (from == to)                     
                    return $.carousel.result(this, callback, callback_data);
                
                $.carousel.stop.apply(this);
                $.carousel.update.apply(this, [from, to, false, callback, callback_data]);
                $.carousel.start.apply(this);
            } else 
            if (method == "selected") {
                var ops = $(this).data("carousel"); 
                var ul = $(this).children("div.carousel_clip").children("div.carousel_items");        
                 
                return {
                    item: ul.children("li.carousel_item[data-index=" + ops.selected + "]"),
                    current: ops.selected,
                    previous: ops.prev_selected                    
                }  
            } else
            if ((method == "show" || method == "select") && typeof arg != "undefined") {
                var ops = $(this).data("carousel"); 
                var to, from = ops.index;    
                var by_index = false;
                
                if (arg[0] == "#") {
                    arg = arg.substring(1);
                    if (ops.infinite)
                        by_index = true;
                }
                arg = parseInt(arg);
                ops.prev_selected = ops.selected;   
                
                if (!by_index && (arg < 0 || arg >= ops.items.length))                      
                    return $.carousel.result(this, callback, callback_data);
                
                if (by_index) {                      
                    if (arg < ops.index) 
                        to = arg; else                                
                    if (arg >= ops.index + ops.slots)   
                        to = arg - ops.slots + 1; else
                        to = from;
                    
                    if (method == "select")
                        ops.selected = arg;
                } else
                if (ops.infinite) {
                    var curr = ops.index % ops.items.length;
                    var left = arg;
                    var right = arg;
                    if (left > curr) left-= ops.items.length;
                    if (right < curr) right+= ops.items.length;
                    
                    var dl = curr - left;
                    var dr = right - curr;
                    var drv = dr - ops.slots + 1;  
                    
                    if (method == "select") {
                        if (dl < dr && drv > 0) 
                            ops.selected = ops.index - dl; else
                            ops.selected = ops.index + dr; 
                    }
                    
                    if (dl <= 0 || drv <= 0) 
                        to = from; else
                    if (dl < dr) 
                        to = ops.index - dl; else
                        to = ops.index + dr;
                } else {
                    if (arg < ops.index) 
                        to = arg; else
                    if (arg >= ops.index + ops.slots)
                        to = arg - ops.slots + 1; else
                        to = from;
                    
                    if (method == "select")
                        ops.selected = arg;
                }        
                
                if (from == to) {
                    var ul = $(this).children("div.carousel_clip").children("div.carousel_items");      
                    
                    ul.children("li").removeClass("selected");
                    ul.children("li.carousel_item[data-index=" + ops.selected + "]").addClass("selected");
                    
                    if (typeof callback == "function")
                        callback.apply(this, [callback_data]);
                } else {
                    $.carousel.stop.apply(this);
                    $.carousel.update.apply(this, [from, to, false, callback, callback_data]);
                    $.carousel.start.apply(this);    
                }
            }
        }
        
        return $(this);
    }
})( jQuery );        

