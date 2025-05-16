/*
    TODO: 
    - nek class, da se avtomaticno nardijo?
    - null_value, delete key
    - select button drag -> focus out ne dela
    - ac_select/edit na elementu, ki je ze zgeneriran
    
    - pos auto select se input da na opacity = 0, main se nafila z vrednostjo (karkol gre notr), keydown ga spet skrije    
    
    - select value na zacetku ni narjen (null_value, handler za ajax itd?), default value itd (dejansko naloga phpja?)
    - podvajanje eventov (expand, layout), za prevert, kdaj se zgodi expand pri OK?
    - expand -> items.count = 0, da ga ne prikaze (oz hide)
    - items callback, kako ve a je filtriran al ne?
    
    - id generation za items
    - 870 -> ops.auto_select?
    
    - update event za edit/slider
    
    - tab index za slider
    - edit -> load iz ul (da se avtomaticno dodajo items kot li)
    
    - slider change se sprozi, ce je sprememba bla sploh!
    
    var cls = $.ac.get_attr_def(src, "class", ""); 
    
********* BUGS
    edit - items se pokaze sele, ko je kaka crka OK
    
    IE8
    - get attribute disabled (<input type="text" disabled>)
    - select -> (multi) ko druzga izberes, crkne hover? OK
    - select -> (search) ne dela click OK
    - select -> (auto) setselectionrange ne dela OK
*/

/*
if(input.setSelectionRange){
    selectionStart = adjustOffset(input, selectionStart);
    selectionEnd = adjustOffset(input, selectionEnd);
    input.setSelectionRange(selectionStart, selectionEnd);

  }else if(input.createTextRange){
    var range = input.createTextRange();
    range.collapse(true);
    range.moveEnd('character', selectionEnd);
    range.moveStart('character', selectionStart);
    range.select();
  }
*/

(function( $ ){   
    "use strict";
    
    $.isObject = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object Object]');
    }      
    $.isArray = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object Array]');
    }
    $.isString = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object String]');
    }
    $.focus_timeout = null;
    $.focus_target = null;
              
    $.fn.focusleave = function(fn) {
        $(this).data("focusleave_callback", fn);
        $(this).bind("focusout", function(e) {
            clearTimeout($.focus_timeout);
            
            $.focus_target = $(this);
            $.focus_timeout = setTimeout(function() {
                if (!$.focus_target) return;
                                 
                var has_focus = $.focus_target.find(":focus").length > 0;
                if (!has_focus) {
                    var ops = $.focus_target.data("ac");
                    if (ops && ops._dropdown) 
                        has_focus = ops._dropdown.is(":focus") || ops._dropdown.find(":focus").length != 0;
                }
                //var f = $(":focus", $.focus_target);
                if (!has_focus) {
                    var fn = $.focus_target.data("focusleave_callback");
                    if (typeof fn == "function")
                        fn.apply($.focus_target);
                } else
                    $.focus_target.focus();
                        
                $.focus_target = null;
            }, 50);
        });
        
        return $(this);
    }   
    $.ac = {
        controls: [],
        keys: {
            UP: 38,
            DOWN: 40,
            RIGHT: 39,
            LEFT: 37,
            PG_UP: 33,
            PG_DOWN: 34,
            BCK_SPACE: 8,
            END: 35,
            HOME: 36,
            ENTER: 13,
            DELETE: 46,
            ESC: 27,                     
            TAB: 9
        },
        get_args: function(args) {
            var res = [{}, null, "create"];
            for (var i = 0; i < args.length; i++) 
                if (typeof args[i] == "function")
                    res[1] = args[i]; else
                if ($.isString(args[i]))
                    res[2] = args[i]; else
                    res[0] = args[i];
            
            return res;
        },
        get_attr: function(elem, name, bool) {
            var r = null;
            if (elem[0].hasAttribute(name))
                r = elem[0].getAttribute(name); else
            if (elem[0].hasAttribute("data-" + name))
                r = elem[0].getAttribute("data-" + name); 
            
            if (r === null) return;
            if (bool) 
                return (r === '' || r === 1 || r === "1" || r === "true" || r === true);
                
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
        
        parse_css: function(style) {
            if (!style) return {};
            
            var rule, rules = style.split(";");
            var result = {};
            for (var i = 0; i < rules.length; i++) {
                rule = rules[i].trim().split(":");                                
                if (rule.length > 1)
                    result[rule[0].trim()] = rule[1].trim();
            }
            
            return result;
        },        
        load_data: function(elem) {
            var res = "";
            var attrs = elem[0].attributes, attr;
            var j = 0;
            for (var i = 0; i < attrs.length; i++) {
                attr = attrs[i];
                
                if (attr.specified) {
                    if (j) res+= " ";
                    res+= attr.name + "=" + attr.value;
                    
                    j++;
                }
            }
            
            return res;
        },
        load_attr: function(elem, name, bool) {
            var r = $.ac.get_attr(elem, name, bool);
            
            elem[0].removeAttribute(name);
            elem[0].removeAttribute("data-" + name);
            
            return r;
        },
        load_attr_def: function(elem, name, bool) {
            var r = $.ac.get_attr_def(elem, name, false, bool);

            elem[0].removeAttribute(name);
            elem[0].removeAttribute("data-" + name);
            
            return r;
        },
        
        set_selection: function(input, start, end) {
            input = input[0];
            if (input.createTextRange) {
                var range = input.createTextRange();
                range.collapse(true);
                range.moveEnd('character', end);
                range.moveStart('character', start);
                range.select();
            } else 
                input.setSelectionRange(start, end);
        },
        clr_selection: function(input) {
            var l = input.val().length;
            if (input.createTextRange) {
                var range = input.createTextRange();
                range.collapse(true);
            } else {
                input[0].selectionStart = l;
                input[0].selectionEnd = l;
            }
        },
        get_selection: function(input) {
            input = input[0];
            if (input.createTextRange) {
                var r = document.selection.createRange();
                
                var re = input.createTextRange();
                var rc = re.duplicate();
                re.moveToBookmark(r.getBookmark());
                rc.setEndPoint('EndToStart', re);

                return [rc.text.length, rc.text.length + r.text.length];
            } else 
                return [input.selectionStart, input.selectionEnd];            
        },
        queue_create: function(values) {
            var r = {    
                values: {},
                count: values.length,
            }  
            
            for (var i = 0; i < values.length; i++) 
                r.values[values[i]] = true;
            
            return r;
        },
        queue_next: function(queue) {
            value = null;
            for (var value in queue.values)
                break;  
            
            return value;
        },
        queue_remove: function(queue, value) {
            if (typeof queue.values[value] != "undefined") {
                delete queue.values[value];
                queue.count--;    
            }
        },   
        copy_events: function(src, dst, types) {
            var attr,attrs = src[0].attributes;
            for (var i = 0; i < attrs.length; i++) {
                attr = attrs[i];
                if (attr.nodeName.indexOf("data-") == 0) 
                    dst.attr(attr.nodeName, attr.nodeValue);    
            }            
            
            var evs, type, events = $._data(src[0]).events;
            if (typeof events == "undefined") return;
            
            for (var i = 0; i < types.length; i++) {
                type = types[i];
                if (typeof events[type] == "undefined") continue;
                
                evs = events[type];
                for (var j = 0; j < evs.length; j++) 
                    $.event.add(dst[0], evs[j].type, evs[j]);
            }
        },
        
        get_time: function() {
            var d = new Date();
            return d.getTime();    
        },
        focus_timeout: null,
        focus_target: null,
        focus_leave: function(target, callback) {
            target.data("ac_focusleave", callback);
            target.bind("focusout", function(e) {
                clearTimeout($.ac.focus_timeout);

                $.ac.focus_target = $(this);
                $.ac.focus_timeout = setTimeout(function() {
                    if (!$.ac.focus_target) return;
                                     
                    var has_focus = $.ac.focus_target.find(":focus").length > 0;
                    if (!has_focus) {
                        var ops = $.ac.focus_target.data("ac");
                        if (ops && ops._dropdown) 
                            has_focus = ops._dropdown.is(":focus") || ops._dropdown.find(":focus").length != 0;
                    }
                    
                    if (!has_focus) {
                        var fn = $.ac.focus_target.data("ac_focusleave");
                        if (typeof fn == "function")
                            fn.apply($.ac.focus_target);
                    } else
                        $.ac.focus_target.focus();
                            
                    $.ac.focus_target = null;
                }, 50);
            });
        },
        dropdown: {
            def_options: {    
                dd_auto_select: false,
                dd_multi_select: false,
                dd_flt_deselect: true,
                dd_filter_start: false,
                dd_endhome_keys: true,
                
                dd_select: null,
                dd_collapse: null,
                dd_expand: null,
                dd_filter: null,
            },
            ignore_hover: false,
            global_events: false,
            filter_timeout: null,
            filter_target: null,
            filter_value: null,
            layout: function(pos, ctrl, ops) {
                if (typeof ops._dropdown == "undefined") return;
                
                var items = ops._dropdown;//$(".ac_items", ctrl);  
                var ul = $(">ul", items);
                
                var ctrl_h = ctrl.height();
                var sw = ctrl.width(); 
                var ul_w = ul.width();
                var items_width = ul_w;
                    
                var s = ops.popup_width.split("-");
                var min = s[0];
                var max = (s.length > 1) ? s[1] : s[0];
                
                if (min == "fill") 
                    min = sw; else
                if (min.indexOf("%") != -1)
                    min = sw * parseInt(min) / 100;
                    
                if (max == "fill") 
                    max = sw; else
                if (max.indexOf("%") != -1)
                    max = sw * parseInt(max) / 100;
                    
                if (min && items_width < min) items_width = min;
                if (max && items_width > max) items_width = max;
                    
                items.css("width", items_width + "px");
                
                var ofs = ctrl.offset();
                var x = ofs.left - $(window).scrollLeft();
                var y = ofs.top - $(window).scrollTop();
                
                items.css("max-height", ops.popup_height + "px");

                var sp1 = y;
                var sp2 = $(window).height() - ctrl.height() - y;
                
                if (sp1 < 0) sp1 = 0;
                if (sp2 < 0) sp2 = 0;
                
                
                var cnt = Math.min($("li", items).length, 20);
                var tmp = $("<li class='ac_item ac_temp'></li>").appendTo(items);
                var li_h = tmp.outerHeight();
                tmp.remove();
                
                var css = {
                    "top": "auto",
                    "bottom": "auto",
                    "left": ofs.left,
                    "right": "auto",
                }
                
                if (li_h * cnt > sp2 && sp1 > sp2) {
                // TOP                                                            
                    cnt = parseInt(sp1 / li_h); 
                    items.css("max-height", cnt * li_h + "px");
                    css.top = ofs.top - items.height();                                                               
                } else {
                // BOTTOM
                    cnt = parseInt(sp2 / li_h);
                    items.css("max-height", cnt * li_h + "px");
                    css.top = ofs.top + ctrl_h;
                }
                
                items.css(css);
            },
            create: function(ctrl, input, ops) {
                var cont = $("body").children(".ac_cont");
                if (!cont.length) 
                    cont = $("<div class='ac_cont'></div>").prependTo("body");
                
                //var items = $("<div class='ac_items' tabindex='-1'><ul></ul></div>").appendTo(ctrl);
                var items = $("<div class='ac_items' tabindex='-1'><ul></ul></div>").appendTo(cont);
                ops._dropdown = items;
                items.data("ac_parent", ctrl);
                items.mouseleave(function() {
                    if (!$.ac.dropdown.ignore_hover) {
                        var ctrl = $(this).data("ac_parent");
                        var ops = ctrl.data("ac");
                        //$.ac.dropdown.focus_item(null, $(this), $(this).data("ac"));
                        $.ac.dropdown.focus_item(null, ctrl, ops);
                    }
                }).click(function(e) {
                    e.stopPropagation();  
                    return false; 
                });
                
                input.keydown(function(e) {    
                    var ctrl = $(this);
                    if (!ctrl.is(".acontrol"))
                        ctrl = ctrl.parents(".acontrol:first");    
                                
                    var ops = ctrl.data("ac");
                    var next,chr;     
                    var items = ops._dropdown;
                    
                    var set = $(".ac_item:not(.ac_disabled):visible", items);  
                    var curr = $();
                    if (ops.dd_auto_select)
                        curr = items.find(".ac_item.ac_selected"); 
                    if (!curr.length)
                        curr = items.find(".ac_item.ac_focused");
                       
                    var curr_ind = curr.length ? set.index(curr) : -1;
                    var next_ind = null;   
                    
                    if (!ops.dd_endhome_keys && (e.which == $.ac.keys.END || e.which == $.ac.keys.HOME)) return;       
                    
                    switch (e.which) {
                        case $.ac.keys.ENTER:
                        case $.ac.keys.ESC: 
                            if (ctrl.hasClass("ac_expanded")) {
                                e.stopPropagation();
                                e.preventDefault();
                                
                                return false;     
                            }
                            break;
                        case $.ac.keys.UP: next_ind = curr_ind - 1; break;
                        case $.ac.keys.DOWN: next_ind = curr_ind + 1; break;
                        case $.ac.keys.PG_UP: next_ind = curr_ind - 10; break;
                        case $.ac.keys.PG_DOWN: next_ind = curr_ind + 10; break;
                        case $.ac.keys.HOME: next_ind = 0; break;
                        case $.ac.keys.END: next_ind = set.length-1;
                    }
                    
                    if (next_ind !== null) {
                        if (next_ind < 0) next_ind = 0;
                        if (next_ind >= set.length) next_ind = set.length-1;
                    
                        next = $(set.get(next_ind));
                    
                        if (!ctrl.is(".ac_expanded"))
                            $.ac.dropdown.expand(ctrl, ops);

                        $.ac.dropdown.focus_item(next, ctrl, ops);
                        if (ops.dd_auto_select)
                            $.ac.dropdown.select_item(next, 1, ctrl, ops);
                        
                        return false;
                    }        
                }).keyup(function(e) {
                    var ctrl = $(this);
                    if (!ctrl.is(".acontrol"))
                        ctrl = ctrl.parents(".acontrol:first");    
                                
                    var ops = ctrl.data("ac");
                    var items = ops._dropdown;
                    
                    if (e.which == $.ac.keys.ENTER) {
                        $.ac.dropdown.select_item($(".ac_item.ac_focused", items), ops.dd_multi_select ? 0 : 1, ctrl, ops);
                        
                        if (!ops.dd_multi_select)
                            $.ac.dropdown.collapse(ctrl, ops);
                        
                        e.stopPropagation();    
                        return false;
                    }
                    
                    if (e.which == $.ac.keys.ESC) {
                        $.ac.dropdown.collapse(ctrl, ops); 

                        e.stopPropagation();    
                        return false;
                    } 
                });                       
                
                if (!$.ac.dropdown.global_events) {
                    $(document).mousemove(function() {
                        $.ac.dropdown.ignore_hover = false;
                    });
                    $.ac.dropdown.global_events = true;
                }
                
            },
            filter: function(flt, ctrl, ops) {
                var dd = $.ac.dropdown;
                clearTimeout(dd.filter_timeout);
                    
                dd.filter_value = flt;
                dd.filter_target = ctrl;                    
                dd.filter_timeout = setTimeout(function() {
                    var dd = $.ac.dropdown;
                    if (!dd.filter_target) return;
                    
                    var ops = dd.filter_target.data('ac');
                    
                    $.ac.dropdown.build(dd.filter_value, dd.filter_target, ops);
                    $.ac.dropdown.expand(dd.filter_target, ops);                    
                }, ops.filter_delay);
            },
            build: function(flt, ctrl, ops, handled) {
                if (typeof ops._dropdown == "undefined") return;
                ops._build = true;

                var rgx = null;
                var html = "";
                var items,chars = {};

                ops._filter = flt;
                if (flt && ops.filter_mask) {
                    flt = ops.filter_mask.replace("__TERM__", flt);
                    rgx = new RegExp(flt, "i");
                } 
                
                if (ops.handler && handled !== true) {
                    var xhr = $.post(ops.handler, { filter: flt }, function(r, textStatus, xhr) {
                        var ops = xhr.ac_build.ops;
                        
                        if ($.isArray(r) && r.length) {
                            console.log(r);
                            ops.items = r;
                            $.ac.dropdown.build(xhr.ac_build.flt, xhr.ac_build.ctrl, ops, true);    
                        }
                    }, "json");
                    xhr.ac_build = {
                        flt: flt, 
                        ctrl: ctrl,
                        ops: ops
                    }
                    
                    return;
                } else
                if (typeof ops.items == "function")
                    items = ops.items.apply(ctrl, [flt]); else
                    items = ops.items;
                    
                if (!$.isArray(items) || !items.length) return;
                
                var dropdown = ops._dropdown;
                var tmp = $("<li class='ac_item ac_temp'></li>").appendTo(dropdown);
                var padding = parseInt(tmp.css("padding-left"));
                tmp.remove();
                
                var each_item = function(items, level) {
                    var style = "style='padding-left: " + (ops.indent * level + padding) + "px'";
                    var res = "",sub;
                    var itm,chr,cls,attr;
                    
                    for (var i = 0; i < items.length; i++) {
                        itm = items[i];    
                        cls = itm.cls;
                        if (typeof cls == "undefined")
                            cls = "";
                            
                        attr = itm.attr;
                        if (typeof attr == "undefined")
                            attr = "";
                        
                        if (itm.disabled)
                            cls+= " ac_disabled";
                        
                        if ($.isArray(itm.items) && itm.items.length) {
                            sub = each_item(itm.items, level+1);
                            if (sub != "")
                                res+= "<li class='ac_group " + cls + "'" + style + ">" + itm.label + "</li>" + sub;
                        } else {
                            if (!itm.value) itm.value = itm.content;
                            if (!itm.label) itm.label = itm.content;

                            if (rgx && !rgx.test(itm.label))
                                continue;

                            chr = itm.label.charCodeAt(0); 
                            chars[chr] = true;
                        
                            res+= "<li class='ac_item " + cls + "' data-value='" + itm.value + "' data-label='" + itm.label + "' data-char='" + chr + "'" + style + " " + attr + ">" + itm.content + "</li>";
                        }
                    }
                    ops._chars = chars;
                    
                    return res;
                }
                html = each_item(items, 0);
                
                dropdown.children("ul").html(html);
                
                var value = ops.value;
                if ($.isString(value)) 
                    value = (value == "") ? [] : value.split(",");
                    
                if ($.isArray(value)) 
                for (var i = 0; i < value.length; i++) 
                    $(".ac_item[data-value='" + value[i] + "']", dropdown).addClass("ac_selected");    
                
                $(".ac_item", dropdown).mouseup(function(e) {
                    if ($(this).is(".ac_disabled")) return;
                    
                    var ctrl = $(this).parents(".ac_items:first").data("ac_parent");
                    var ops = ctrl.data("ac");

                    if (!ops._ignoremouseup) {
                        $.ac.dropdown.select_item($(this), ops.dd_multi_select ? 0 : 1, ctrl, ops);
                        if (!ops.dd_multi_select)
                            $.ac.dropdown.collapse(ctrl, ops);    
                    }
                    ops._ignoremouseup = false;
                }).bind("mousedown", function(e) {
                    var ctrl = $(this).parents(".ac_items:first").data("ac_parent");
                    var ops = ctrl.data("ac");
                    ops._ignoremouseup = false;
                    
                    return false;
                }).mouseenter(function() {
                    if ($(this).is(".ac_disabled")) return;
                    
                    if (!$.ac.dropdown.ignore_hover) {  
                        var items = $(this).parents(".ac_items:first");
                        
                        $("li.ac_focused", items).removeClass("ac_focused");
                        $(this).addClass("ac_focused");
                    }
                });  
                
                ops._build = true;
                if ($(this).is(".ac_expanded")) 
                    $.ac.dropdown.layout(false, ctrl, ops);
            },
            expand: function(ctrl, ops) {
                if (typeof ops._dropdown == "undefined") return;
                if (ctrl.is(".ac_expanded")) return;
                if (!ops._build)
                    $.ac.dropdown.build(ops._filter, ctrl, ops);
                
                var items = ops._dropdown;//$(".ac_items", ctrl);
                if (!items.length) return;
                
                ops._ignoremouseup = true;
                
                ctrl.addClass("ac_expanded");
                items.show();
                
                $.ac.dropdown.layout(true, ctrl, ops);

                //var itm = $(".ac_items .ac_item.ac_selected:first", ctrl);
                var itm = $(".ac_item.ac_selected:first", items);
                if (itm.length)
                    $.ac.dropdown.focus_item(itm, ctrl, ops); else
                    //$(".ac_items", ctrl).scrollTop(0);
                    items.scrollTop(0);
                
                if (typeof ops.dd_expand == "function")
                    ops.dd_expand(ctrl, ops);
            },
            collapse: function(ctrl, ops) {
                if (typeof ops._dropdown == "undefined") return;
                ctrl.removeClass("ac_expanded");
                
                ops._dropdown.hide();
                //$(".ac_items", ctrl).hide();  

                if (typeof ops.dd_collapse == "function")
                    ops.dd_collapse(ctrl, ops);
            },
            focus_item: function(li, ctrl, ops) {  
                //console.log(li, ctrl, ops);
                //var items = $(".ac_items", ctrl);
                var items = ops._dropdown;
                if (!items.length) return;

                if (li === null) {
                    $(".ac_item.ac_focused", items).removeClass("ac_focused");
                    return;    
                }
                
                if (!li.length) return;
                
                $(".ac_item.ac_focused", items).removeClass("ac_focused");
                    
                var y1 = li.position().top;
                var y2 = y1 + li.outerHeight();
                var s = items.scrollTop();
                var ih = items.height();
                
                if (y1 < 0)
                    items.scrollTop(s + y1); else
                if (y2 > ih)
                    items.scrollTop(s + y2 - ih);
                
                $.ac.dropdown.ignore_hover = true;  
                li.addClass("ac_focused");
            },
            select_item: function(li, state, ctrl, ops) {
                //var items = $(".ac_items", ctrl);
                var items = ops._dropdown;
                if (!items.length) return;
                                                  
                if (li === null) {
                    $(".ac_item.ac_selected", items).removeClass("ac_selected");
                    ops.update_value(ctrl, ops);
                    
                    return;
                }
                if (!li.length) return;
                var os = state;

                if (state == 0)
                    state = li.is(".ac_selected") ? -1 : 1;
                
                if (!ops.dd_multi_select && state == 1) 
                    $(".ac_item.ac_selected", items).removeClass("ac_selected");
                    
                if (state == 1)
                    li.addClass("ac_selected"); else
                    li.removeClass("ac_selected");
                    
                if (typeof ops.dd_select == "function")
                    ops.dd_select(li, state, ctrl, ops);
            },
            
            load: function(elements) {
                var res = [];
                elements.each(function() {
                    var ul = $(this).children("ul").detach();
                    var html = $(this).html();
                    res.push({
                        value:   $.ac.get_attr_def($(this), "value", html),
                        label:   $.ac.get_attr_def($(this), "label", html),
                        cls:     $.ac.get_attr($(this), "class"),
                        dis:     $.ac.get_attr_def($(this), "disabled", false, true),
                        content: html,
                        items: $.ac.dropdown.load(ul.children("li"))
                    });
                    
                    $(this).append(ul);
                });
                return res;                
            }
        },
        tree: {
            def_options: {
                indent: 20,  
                animate: true,
                expandable: true, 
                expanded: false,
                state_cookie: null,
                states: false,
                partial_state: 0,
                initial_state: 0,
                dependency: "1111",   // [neg up, neg down, pos up, pos down]
                
                onCreateNode: null,
                onStateChange: null, 
            },
            row_index: 0,
            node_last: {},
            reset: function(ul, ops) {
                ul.find(".act_node").each(function() {
                    $(this).attr("data-state", 0).attr("data-mask", "0000");  
                    if (typeof ops.onStateChange == "function")
                        ops.onStateChange.apply($(this), [0, ops]); 
                });
            },
            save_state: function(val, expanded, ops) {
                if (ops._state) {
                    if (expanded)
                        ops._state[val] = 1; else
                        delete ops._state[val];
                    
                    $.cookie(ops.state_cookie, $.toJSON(ops._state), { path: '/', expires: 7});
                }                    
            },
            node_toggle: function(li, state, anim, ops, prop_up, prop_down) {
                var ul = li.children("ul");
                
                if (anim === null)
                    anim = ops.animate;
                
                if (state === null)
                    state = !li.is(".act_expanded");
                    
                if (li.is(".act_expanded") == state) return;
                
                if (state) {                    
                    if (anim)
                        ul.stop().slideDown(200); else
                        ul.show();
                    
                    $.ac.tree.save_state(li.attr("data-value"), true, ops);
                        
                    li.removeClass("act_collapsed").addClass("act_expanded");
                } else {
                    if (anim) 
                        ul.stop().slideUp(200); else
                        ul.hide();
                        
                    $.ac.tree.save_state(li.attr("data-value"), false, ops);
                
                    li.removeClass("act_expanded").addClass("act_collapsed");
                }
                
                if (prop_down) {
                    var ch = ul.children("li");
                    for (var i = 0; i < ch.length; i++)
                       $.ac.tree.node_toggle($(ch[i]), state, anim, ops, false, true); 
                }
                
                if (prop_up) {
                    var p = li.parents("li.act_node:first");
                    if (p.length)
                        $.ac.tree.node_toggle(p, state, anim, ops, true, false);
                }
            },
            node_state: function(node, state, ops, propagate) {
                var ul = node.parents("ul:first");
                var curr = $.ac.get_attr(node, "state");
                var typ = $.ac.get_attr(node, "type");
                var parent = ul.parents("li.act_node:first");
                if (!parent.length) 
                    parent = null;

                if (state == null) {
                    state = parseInt(node.attr("data-state"));
                    if (state == 2)
                        state = 1; else
                    if (state == 0)
                        state = 1; else
                        state = 0;
                }
                
                var ptyp = (parent) ? $.ac.get_attr(parent, "type") : false;
                
                node.attr("data-state", state);                             
                if (typeof ops.onStateChange == "function")
                    ops.onStateChange.apply(node, [state, ops]);  

                var prop_up, prop_down;
                if (state == 0) {
                    prop_up = ops.dependency[0] == "1";
                    prop_down = ops.dependency[1] == "1";
                } else {
                    prop_up = ops.dependency[2] == "1";
                    prop_down = ops.dependency[3] == "1";
                }                 
                      
                if (parent && ptyp != "none" && prop_up && propagate !== false)
                    $.ac.tree.prop_state_up(parent, node, ops);
                
                if (typ != "none" && prop_down && propagate !== false)
                    $.ac.tree.prop_state_down(node, ops);
            },
            prop_state_down: function(node, ops) {
                var li,ch = node.children("ul").children("li");
                if (!ch.length) return;
                
                var typ = $.ac.get_attr_def(node, "type", "def");        
                var state = parseInt(node.attr("data-state"));
                
                var msk = [0,0,0,0];
                if (state == 1) 
                    msk[0] = 1; else 
                    msk[1] = 1;
                
                if (typ != "none" && !node.is("act_leaf")) 
                    msk[(state == 1) ? 2 : 3] = 1;

                node.attr("data-mask", msk.join(""));
                                                                              
                for (var i = 0; i < ch.length; i++) {
                    li = $(ch[i]);
                    li.attr("data-state", state);
                    if (typeof ops.onStateChange == "function")
                        ops.onStateChange.apply(li, [state, ops]);
                    
                    $.ac.tree.prop_state_down(li, ops);
                }
            },              
            prop_state_up: function(node, source, ops) {
                var state,st,msk;
                var li,ch = node.children("ul").children("li");
                var typ = $.ac.get_attr_def(node, "type", "def");
                
                var c_cnt = 0;
                var p_cnt = 0;
                var pp_cnt = 0;
                var n_st = false;
                var p_st = false;
                var cmp;
                for (var i = 0; i < ch.length; i++) {
                    li = $(ch[i]);          
                    
                    if (typ == "single" && !li.is(source)) {
                        li.attr("data-state", 0);
                        if (typeof ops.onStateChange == "function")
                            ops.onStateChange.apply(li, [0, ops]);
                        
                        if (ops.dependency[1] == "1")
                            $.ac.tree.prop_state_down(li, ops);                            
                    } 
                      
                    st = parseInt(li.attr("data-state"));
                    if (st > 0)
                        p_cnt++;
                    if (st == 1)
                        c_cnt++;            
                    if (st == 2)
                        pp_cnt++;
                    
                    msk = li.attr("data-mask");
                    p_st = p_st || st == 1 || msk[2] == 1;
                    n_st = n_st || st == 0 || msk[3] == 0;
                }
                
                if (typ == "def" || typ == "single") {
                    if (pp_cnt == ch.length)
                        state = 2; else
                    if (p_cnt == ch.length)
                        state = 1; else
                    if (p_cnt == 0)
                        state = 0; else
                        state = 2;
                } else 
                if (typ == "or") {
                    cmp = ops.partial_state ? p_cnt : c_cnt;
                    state = (cmp != 0) ? 1 : 0; 
                } else
                if (typ == "and") {
                    cmp = ops.partial_state ? p_cnt : c_cnt;
                    state = (cmp == ch.length) ? 1 : 0; 
                }
                
                var mask = [0,0,0,0];
                if (c_cnt == ch.length) mask[0] = 1;    
                if (c_cnt == 0) mask[1] = 1;
                if (p_st) mask[2] = 1;
                if (n_st) mask[3] = 1;
                                                               
                node.attr("data-mask", mask.join(""));
                node.attr("data-state", state);
                if (typeof ops.onStateChange == "function")
                    ops.onStateChange.apply(node, [state, ops]);
                
                var parent = node.parents("li.act_node:first");
                if (parent.length)
                    $.ac.tree.prop_state_up(parent, node, ops); 
            },
            single_click: function(e) {           
                var last_click = $(this).data("ac_lastclick");
                if (typeof last_click == "undefined")
                    last_click = 0;

                var li = $(this).parents("li:first");
                var tree = li.parents(".ac_tree:first");
                var ops = tree.data("ac");
                    
                var time = $.ac.get_time();
                if (time - last_click < 300) {
                    var state = li.is(".act_expanded");
                    var ch = li.children("ul").children("li");
                    for (var i = 0; i < ch.length; i++)
                        $.ac.tree.node_toggle($(ch[i]), state, null, ops, false, true); 
                } else 
                    $.ac.tree.node_toggle(li, null, null, ops);
                
                $(this).data("ac_lastclick", time);
            }, 
            build_node: function(li, ops, level, parent) {
                var cls,lines;                        
                var ul = li.children("ul").detach();

                var last = li.nextAll("li").length == 0;           
                li.wrapInner("<div class='act_item' />").append(ul);
                                                                                                          
                var item = li.children("div").css("margin-left", (level+1) * ops.indent + "px").addClass(li.attr("class"));
                var h = item.outerHeight();
                var w = ops.indent / 2;
                
                lines = "";
                for (var i = 0; i < level; i++)
                    if (!$.ac.tree.node_last[i])
                        lines+= "<div class='act_line_v' style='left: " + (i * ops.indent) + "px; height: " + h + "px; width: " + (w-1) + "px'></div>";
                
                lines+= "<div class='act_line_v' style='left: " + (level * ops.indent) + "px; height: " + (last ? h/2 : h) + "px; width: " + (w-1) + "px'></div>";
                lines+= "<div class='act_line_h' style='left: " + (level * ops.indent + w - 1) + "px; height: " + (h / 2) + "px; width: " + w + "px'></div>";
                
                if (ops.expandable && ul.length) {
                    var c_exp = (ops._state) ? ops._state[li.attr("data-value")] : null;
                    if (typeof c_exp == "undefined" || c_exp === null)
                        c_exp = ops.expanded;
                        
                    var exp = $.ac.get_attr_def(li, "expanded", c_exp, true);
                    if (exp) 
                        li.addClass("act_expanded"); else
                        li.addClass("act_collapsed");
                        
                    lines+= "<div class='act_button' style='left: " + (level * ops.indent) + "px; width: " + (w + w) + "px; height: " + h + "px'><div class='act_icon'></div></div>";
                }
                
                li.prepend(lines);
                li.addClass("act_node");        
                li.children(".act_button").click($.ac.tree.single_click);

                var ch = ul.children("li");
                if (!ch.length)
                    li.addClass("act_leaf");
                    
                if (ops.states) {
                    if (ch.length) {
                        var typ = $.ac.get_attr_def(li, "type", "def");
                        li.attr("data-type", typ);
                    } else
                        li.removeAttr("data-type");
                                          
                    if (ops.initial_state == 0)
                        li.attr("data-mask", "0101"); else
                        li.attr("data-mask", "1010");
                        
                    li.attr("data-state", ops.initial_state);
                }
                
                if (typeof ops.onCreateNode == "function")
                    ops.onCreateNode.apply(li, [parent, ops, $.ac.tree.row_index]);
                
                $.ac.tree.row_index++;
                $.ac.tree.node_last[level] = last;
                
                ch.each(function() {
                    $.ac.tree.build_node($(this), ops, level+1, li); 
                });
            },
            build: function(ul, ops) {
                ops = $.extend({}, $.ac.tree.def_options, ops);
                if (ops.dependency == "up")
                    ops.dependency = "0110";                                  
                                 
                $.ac.tree.row_index = 0;                                
                if (ops.state_cookie) {
                    ops._state = $.evalJSON($.cookie(ops.state_cookie));
                    if (!$.isObject(ops._state))
                        ops._state = {};
                }
                
                ul.addClass("ac_tree");
                ul.children("li").each(function() {
                    $.ac.tree.build_node($(this), ops, 0, null);                         
                });
                
                ul.find("li.act_collapsed > ul").hide();
                ul.data("ac", ops);
                
                return ul;
            }   
        }, 
        edit: {
            def_options: {
                name: "",
                cls: "",
                style: "",
                id: "",
                value: "",
                mask: "",
                handler: "",
                handler_name: "",
                handler_value: "",
                lines: 1,
                indent: 10,
                tab_index: 0,
                max_length: 0,
                place_holder: "",
                invalid: false,    
                disabled: false,
                readonly: false,
                password: false,
                resizable: false,
                filter_mask: "__TERM__",
                filter_delay: 500,
            
                popup_height: 200,
                popup_width: "100%-150%",
            },    
            load: function(src) {
                var res = {
                    id: $.ac.get_attr(src, "id"),
                    cls: $.ac.get_attr(src, "class"),
                    style: $.ac.get_attr(src, "style"),
                    name: $.ac.get_attr(src, "name"),
                    handler: $.ac.get_attr(src, "handler"),
                    handler_name: $.ac.get_attr(src, "handler_name"),
                    handler_value: $.ac.get_attr(src, "handler_value"),
                    lines: $.ac.get_attr(src, "lines"),
                    invalid: $.ac.get_attr(src, "invalid", true),
                    max_length: $.ac.get_attr(src, "maxlength"), 
                    readonly: $.ac.get_attr(src, "readonly", true),
                    disabled: $.ac.get_attr(src, "disabled", true),
                    password: src.attr("type") == "password" || $.ac.get_attr(src, "password", true),
                    resizable: $.ac.get_attr(src, "disabled", true),
                    autocomplete: $.ac.get_attr(src, "autocomplete"),
                    place_holder: $.ac.get_attr(src, "placeholder"),
                    tab_index: $.ac.get_attr(src, "tabindex"),
                    mask: $.ac.get_attr(src, "mask"),
                    value: $.ac.get_attr(src, "value")
                }
                
                if (src.is("textarea")) {
                    res.lines = $.ac.get_attr(src, "rows");    
                    res.value = src.html();
                }
                
                return res;
            },    
            dd_select: function(li, state, edit, ops) {
                var sel = $(".ac_item.ac_selected", ops._dropdown);
                var lab = sel.attr("data-label");
                var input = $(".ac_value", edit).val(lab);
                ops._value = lab;
                
                $(".ace_value_id", edit).val(sel.attr("data-value"));
                $(".ac_item.ac_selected", edit).removeClass("ac_selected");
                
                input.trigger("ac_complete");
            },
            set_value: function(edit, value) {
                var ops = edit.data("ac");
                
                $(".ace_value", edit).css("opacity", 1);
                $(".ac_value", edit).val(value);
                
                if (value == "")
                    $(".ace_default", edit).show(); else
                    $(".ace_default", edit).hide();
            },
            get_value: function(edit) {
                return $(".ac_value", edit).val();
            },
            gen_menu: function(edit, content) {
                var menu = $("<div class='ac_menu'>" + content + "</div>").appendTo(edit);
                var w = menu.outerWidth();
                var h = menu.outerHeight();
                
                var m = parseInt((edit.height() - h) / 2);
                menu.css({
                    top: m,
                    bottom: m
                });
                               
                edit.children(".ace_value").css("margin-right", w + 5 + "px"); 
                edit.children(".ace_default").css("right", w);
            },
            create: function(src, ops) {    
                ops = $.extend({}, $.ac.edit.def_options, $.ac.edit.load(src), ops, $.ac.dropdown.def_options);
                if (typeof ops.items == "undefined") {
                    if (src.is("select"))
                        ops.items = $("option", src); else
                    if (src.is("ul"))
                        ops.items = $("li", src);
                }
                
                if (ops.items instanceof $) 
                    ops.items = $.ac.dropdown.load(ops.items); 
                if (typeof ops.items == "undefined")
                    ops.items = [];
                
                var typ = (ops.password) ? "password" : "text";
                var cls = ops.cls;
                
                var ctrl_attr = "";   
                var input_attr = "";
                
                if (ops.readonly) input_attr+= " readonly";
                if (ops.disabled) {
                    input_attr+= " disabled";
                    cls+= " ac_disabled";
                }
                if (ops.items.length || ops.handler || ops.autocomplete == 'off')
                    input_attr+= " autocomplete='off'";
                if (ops.invalid)
                    cls+= " ac_invalid";
                    
                if (ops.id)
                    ctrl_attr+= " id='" + ops.id + "'";
                if (ops.style)
                    ctrl_attr+= " style='" + ops.style + "'";  
                    
                var edit = $("<div class='acontrol ac_border " + cls + "'" + ctrl_attr + "></div>").insertAfter(src).addClass("ac_edit");
                var ctrl_height = edit.height();
                
                var html = "    <div class='ace_value'>";
                if (ops.lines > 1)
                    html+= "        <textarea class='ac_value' data-name='" + ops.name + "' name='" + ops.name + "' rows='1' " + input_attr + ">" + ops.value + "</textarea>"; else
                    html+= "        <input class='ac_value' type='" + typ + "' data-name='" + ops.name + "' name='" + ops.name + "' " + input_attr + " autocorrect='off' autocapitalize='off' spellcheck='false'>"
                html+= "    </div>";
                    
                html+= "    <div class='ace_default ac_empty'>" + ops.place_holder + "</div>";
                //if (ops.items.length) 
                    //html+= "    <div class='ac_items' tabindex='-1'><ul></ul>";
                    
                if (ops.handler_name)
                    html+= "<input class='ace_value_id' type='hidden' name='" + ops.handler_name + "' value='" + ops.handler_value + "'>";
                html+= "</div>";
                
                edit.append(html);
                src.detach().appendTo(edit).hide().removeAttr("name id data-name class");
            
                edit.find(".ac_value").val(ops.value); 
                if (ops.value == "") {
                    $(".ace_default", edit).show(); 
                    $(".ace_value", edit).css("opacity", 0);    
                } else
                    $(".ace_default", edit).hide();
                    
                if (ops.max_length)
                    $("input", edit).attr("maxlength", ops.max_length);                    

                if (ops.mask && typeof $.fn.mask != "undefined") 
                    $("input", edit).mask(ops.mask);              

            //  LAYOUT
                var pl = parseInt(edit.css("padding-left"));
                var pr = parseInt(edit.css("padding-right"));
                var pt = parseInt(edit.css("padding-top"));
                var pb = parseInt(edit.css("padding-bottom"));
                
                edit.data("ac", ops);
                edit.css({
                    "padding" : 0,
                    "min-height" : ctrl_height + "px",
                });
                    
                $(".ace_value", edit).css({
                    "padding-left" : pl,
                    "padding-right" : pr,
                    "padding-top" : pt,
                    "padding-bottom" : pb
                });                
                $(".ace_default", edit).css({
                    "left" : pl,
                    "right" : pr,
                    "line-height" : ctrl_height + "px"
                });

                if (ops.lines > 1) {
                    var area = edit.find(".ac_value");
                    var line_height = area.height();
                    var pd = (ctrl_height - line_height) / 2;
                    area.attr("rows", ops.lines).css({"padding-top" : pd + "px", "padding-bottom" : pd + "px"});
                } else
                    $(".ac_value", edit).css("min-height", ctrl_height - pt - pb);
                    
                edit.css("height", "auto");
                   
                //monitorEvents($(".ac_value", edit)[0]);       
                $(".ac_value", edit).keydown(function(e) { 
                    if (e.keyCode == $.ac.keys.ENTER) 
                        e.originalEvent.auto_complete = $(this).hasClass("ac_autocomplete");
                    
                    if (e.keyCode == $.ac.keys.UP || 
                        e.keyCode == $.ac.keys.DOWN ||
                        e.keyCode == $.ac.keys.PG_UP ||
                        e.keyCode == $.ac.keys.PG_DOWN ||
                        e.keyCode == $.ac.keys.HOME || 
                        e.keyCode == $.ac.keys.END) 
                        $(this).addClass("ac_autocomplete");                            
                }).bind("input", function() {
                    $(this).removeClass("ac_autocomplete");
                }).bind("focus", function() {
                    $(this).attr("data-prev-value", $(this).val());
                });          
                edit.focusin(function() {   
                    var edit = $(this);
                    var ops = edit.data("ac"); 
                    
                    edit.addClass("ac_focused");
                    $(".ace_value", edit).css("opacity", 1);
                    $(".ace_default", edit).hide();
                    $(".ac_value", edit).removeClass("ac_autocomplete");
                    var val = $(".ac_value", edit).val();
                    if (val !== "") {
                        $.ac.dropdown.build(val, edit, ops);
                        $.ac.dropdown.expand(edit, ops);
                    }
                }).mousedown(function(e) {
                    var edit = $(this);
                    
                    var input = $(".ac_value", edit);
                    var val = input.val();
                    input.removeClass("ac_autocomplete");
                    if (!edit.is(".ac_expanded") && val !== "") {
                        $.ac.dropdown.build(val, edit, edit.data("ac"));
                        $.ac.dropdown.expand(edit, ops);
                    }  
                }).focusleave(function() {       
                    var edit = $(this);   
                    var ops = $(this).data("ac");
                    var val = $(".ac_value", edit); 
                    
                    if (val.val() == "") {
                        $(".ace_value", edit).css("opacity", 0);
                        $(".ace_default", edit).show();
                    } 
                    edit.removeClass("ac_focused");
                
                    $.ac.dropdown.collapse(edit, ops);
                });
                
                $.ac.copy_events(src, $(".ac_value", edit), ["change", "input"]);
                
                if (ops.items.length || ops.handler) {
                    ops.dd_select = $.ac.edit.dd_select;
                    $.ac.dropdown.create(edit, $("input", edit), ops);
                    
                    $("input", edit).keyup(function(e) { 
                        var edit = $(this).parents(".ac_edit:first");
                        var ops = edit.data("ac");
                        var val = $(this).val();
                        
                        var changed = (ops._value != val);
                        ops._value = val;
                        
                        if (!changed) return;
                        
                        
                        if (val !== "") {
                            $.ac.dropdown.filter($(this).val(), edit, ops);                        
                            //$.ac.dropdown.expand(edit, ops);
                        } else 
                            $.ac.dropdown.collapse(edit, ops);    
                    });    
                }
                
                edit.children(".ace_value, .ace_default:visible").fadeTo(500, 1).removeClass("ac_loading");
                edit.removeClass("ac_loading");
                
                return $("input", edit);
            },
        },       
        slider: {
            def_options: {
                name: "",
                id: "",
                cls: "",
                data: "html",
                value: "",
                min: 0,
                max: 100,
                step: 1,
                grips: 1,
                label_selector: null,
                label_format: null,
                label_sorted: true,
                null_boundary: false,
                null_value: false,  
                tab_index: 0,
                invalid: false,    
                disabled: false,
                onUpdate: null,
            },    
            global_events: false,
            current_grip: null,
            load: function(src) {
                var res = {
                    id: $.ac.get_attr(src, "id"),
                    cls: $.ac.get_attr(src, "class"),
                    name: $.ac.get_attr(src, "name"),
                    step: $.ac.get_attr(src, "step"),
                    invalid: $.ac.get_attr(src, "invalid", true),
                    disabled: $.ac.get_attr(src, "disabled", true),
                    tab_index: $.ac.get_attr(src, "tabindex"),
                    min: $.ac.get_attr(src, "min"),
                    max: $.ac.get_attr(src, "max"),
                    amin: $.ac.get_attr(src, "amin"),
                    amax: $.ac.get_attr(src, "amax"),
                    grips: $.ac.get_attr(src, "grips"),
                    val: $.ac.get_attr(src, "value"),
                }
                
                if (typeof res.val == "undefined")
                    res.val = []; else
                    res.val = res.val.split(",");
                
                return res;
            },
            snap: function(number, ops, rel, clip) {
                var r;
                if (rel) {
                    var s = ops.step / (ops.max - ops.min);
                    r = Math.round(number / s) * s;    
                    
                    if (r < 0) r = 0;
                    if (r > 1) r = 1;
                } else {
                    r = Math.round(Math.round(number / ops.step) * ops.step);    
                    
                    if (r < ops.min) r = ops.min;
                    if (r > ops.max) r = ops.max;
                    
                }
                
                return r;
            },
            drag: function(grip, e, slider, ops) {
                var ox = grip.attr("data-pos");
                var sd = slider.width() * ops.step / (ops.max - ops.min);
                var x = e.pageX - (slider.offset().left + ops._padding[0]);
                
                var x = Math.round(x / sd) * sd;
                
                var rmin = (ops.amin - ops.min) / (ops.max - ops.min);
                var rmax = (ops.amax - ops.min) / (ops.max - ops.min); 
                
                x = x / slider.width();
                if (x < rmin) x = rmin;
                if (x > rmax) x = rmax;
                
                if (x != ox) {            
                    grip.attr("data-pos", x);
                    $.ac.slider.update(slider, ops);    
                }
            },
            update_labels: function(slider, ops, values) {
                if (!ops.label_selector) return;
                
                var sel,val;
                if (ops.label_sorted) {
                    for (var i = 0; i < values.length; i++) {
                        sel = ops.label_selector.replace(/%INDEX%/g, i);
                        if (typeof ops.label_format == "function")
                            val = ops.label_format(values[i].val); else
                            val = values[i].val;
                            
                        $(sel).html(val);
                    }
                }   
            },
            update: function(slider, ops) {
                var w = slider.width();
                var arr = [];
                var boundary = true;
                $(".acs_grip", slider).each(function() {
                    var slider = $(this).closest(".ac_slider");
                    var x = parseFloat($(this).attr("data-pos"));
                    var p = x * w + ops._padding[0]; 
                    
                    var rp = 100 * p / slider.outerWidth();
                    $(this).css("left", (x * 100) + "%");

                    var v = $.ac.slider.snap(ops.min + x * (ops.max - ops.min), ops, false);
                    if (Math.abs(v - ops.amin) > 0.0001 && Math.abs(v - ops.amax) > 0.0001)
                        boundary = false;
                        
                    $(this).attr("data-value", parseFloat(v).toFixed(2));

                    arr.push({
                        pos: p,
                        rpos: x * 100,
                        val: parseFloat(v).toFixed(2)
                    });
                });
                
                arr.sort(function(a,b) {
                    return a.val - b.val;
                });
                
                
                if (arr.length > 1) {
                    $(".acs_range", slider).css({
                        left: arr[0].rpos + "%",
                        width: arr[arr.length-1].rpos - arr[0].rpos + "%"
                    });
                }
                
                $.ac.slider.update_labels(slider, ops, arr);                
                
                if (boundary && ops.null_boundary)
                    arr = [];
                    
                var inp = $(".ac_value", slider);
                if (inp.is("select")) {
                    var o = $(".ac_value > option", slider);
                    for (var i = 0; i < arr.length; i++) 
                        $(o[i]).attr("value", arr[i].val);
                } else {
                    var v = "";
                    for (var i = 0; i < arr.length; i++) 
                        v+= (i ? "," : "") + arr[i].val;
                    inp.val(v);    
                }
                
                inp.trigger("update");
                if (typeof ops.onUpdate == "function")
                    ops.onUpdate.apply(inp);
            },
            changed: function(old_val, new_val) {
                if ($.isArray(old_val))
                    old_val = old_val.join(",");
                if ($.isArray(new_val))
                    new_val = new_val.join(",");
                
                //console.log("changed", old_val, new_val);
                
                return old_val != new_val;
            },
            create: function(src, ops) {       
                var o = $.ac.slider.load(src);
                ops = $.extend({}, $.ac.slider.def_options, $.ac.slider.load(src), ops);
                
                ops.min = parseFloat(ops.min);
                ops.max = parseFloat(ops.max);
                
                ops.min = $.ac.slider.snap(ops.min, ops, false);
                ops.max = $.ac.slider.snap(ops.max, ops, false);
                
                if (typeof ops.amin == "undefined") ops.amin = ops.min; else ops.amin = parseFloat(ops.amin);
                if (typeof ops.amax == "undefined") ops.amax = ops.max; else ops.amax = parseFloat(ops.amax);
                
                ops.amin = $.ac.slider.snap(ops.amin, ops, false);
                ops.amax = $.ac.slider.snap(ops.amax, ops, false);
                
                if (ops.amin < ops.min || ops.amin > ops.max) ops.amin = ops.min;
                if (ops.amax < ops.min || ops.amax > ops.max) ops.amax = ops.max;
                
                if (ops.min == ops.max) {
                    ops.grips = 0;
                    ops.disabled = true;
                    ops.val = [];                        
                }
                
               var cls = ops.cls;
                if (ops.disabled) 
                    cls+= " ac_disabled";
                if (ops.invalid)
                    cls+= " ac_invalid";
                
                var style = src.attr("style");
                if (typeof style != "undefined")
                    style = " style='" + style + "'"; else
                    style = "";
                    
                var i,v,p,pos = [];
                if (ops.val.length != ops.grips) {
                    var r1 = (ops.amin - ops.min) / (ops.max - ops.min);
                    var r2 = (ops.amax - ops.min) / (ops.max - ops.min);
                    var d = (r2 - r1) / ops.val.length;
                    
                    for (i = 0; i < ops.grips; i++) 
                        pos[i] = $.ac.slider.snap(r1 + i * d, ops, true);//.toFixed(2);
                } else
                for (i = 0; i < ops.val.length; i++) {
                    v = ops.val[i];
                    if (v < ops.amin) v = ops.amin;
                    if (v > ops.amax) v = ops.amax;
                    
                    pos[i] = ($.ac.slider.snap(v, ops) - ops.min) / (ops.max - ops.min);
                }
                
                var slider = $("<div class='acontrol " + cls + "'" + style + "></div>").addClass("ac_slider").insertAfter(src);
                
                var html = "";
                if (ops.name.indexOf("[]") == ops.name.length-2) {
                    html+= "    <select class='ac_value' name='" + ops.name + "' multiple data-min='" + ops.amin + "' data-max='" + ops.amax + "'>";
                    for (i = 0; i < ops.grips; i++)
                        html+= "    <option value='' selected></option>";
                    html+= "</select>"; 
                } else
                    html+= "    <input class='ac_value' type='hidden' name='" + ops.name + "' data-min='" + ops.amin + "' data-max='" + ops.amax + "'>";
                    
                html+= "<div class='acs_cont'>";
                html+= "<div class='acs_range'></div>";
                for (i = 0; i < ops.grips; i++) 
                    html+= "    <div class='acs_grip' data-pos='" + pos[i] + "'></div>";
                    
                html+= "</div>";
                html+= "<div class='acs_line'></div>";
                
                slider.append(html);
                src.detach().appendTo(slider).hide().removeAttr("name id data-name class");

                ops._padding = [parseInt(slider.css("padding-left")), parseInt(slider.css("padding-right"))];
                
                slider.data("ac", ops);
                $(".acs_grip", slider).each(function() {
                    $(this).mousedown(function() {
                        var slider = $(this).parents(".ac_silder:first");
                        if (slider.is(".ac_disabled"))
                            return;
                            
                        $.ac.slider.current_grip = $(this);
                    });
                    $(this).css("margin-left", "-" + $(this).width() / 2 + "px");
                });
                var h = $(slider).height();
                $(".acs_range", slider).css("top", h / 2 + "px");
                
                $.ac.slider.update(slider, ops);
                ops._value = $(".ac_value", slider).val();
                
                if (!$.ac.slider.global_events) {
                    $(window).bind("mousemove", function(e) {
                        if (!$.ac.slider.current_grip) return false;
                        
                        var grip = $.ac.slider.current_grip;
                        var slider = grip.parents(".ac_slider:first");
                        var ops = slider.data("ac");
            
                        $.ac.slider.drag(grip, e, slider, ops);
                    }).bind("mouseup", function() {
                        if (!$.ac.slider.current_grip) return false; 
                        
                        var slider = $.ac.slider.current_grip.parents(".ac_slider:first");
                        var ops = slider.data("ac");
                        var val = $(".ac_value", slider).val();
                        
                        if ($.ac.slider.changed(ops._value, val)) {                       
                            $(".ac_value", slider).trigger("change");
                            ops._value = val;
                        }
        
                        $.ac.slider.current_grip = null;
                    });
                    $.ac.slider.global_events = true;
                }
                
                return $(".ac_value", slider);
            }        
        },     
        select: {
            def_options: {
                id: "",
                name: "",
                cls: "",
                style: "",
                mode: "single",
                value: "",
                indent: 10,
                tab_index: 0,
                auto_select: true,
                place_holder: "",
                filter_mask: null,
                filter_delay: 500,
                invalid: false,
                disabled: false,
                handler: null,
            
                popup_height: 200,
                popup_width: "100%-150%",
            },
            get_value: function(select) {
                return $(".ac_value", select).val();
            },            
            set_value: function(val, select, ops) {
                var i,j,items;
                var value = [];
                var label = [];
                
                $(".ac_item.ac_selected", ops._dropdown).removeClass("ac_selected");
                    
                    /*
                if (typeof ops.items == "function")
                    items = ops.items.apply(select, flt); else*/
                items = ops.items;
                    
                var each_item = function(val, items) {
                    for (var j = 0; j < items.length; j++) {
                        if (val == items[j].value) {
                            value.push(val);
                            label.push(items[j].label);
                        }
                        
                        each_item(val, items[j].items);
                    }
                }
                if ($.isArray(items)) {
                    for (i = 0; i < val.length; i++) 
                        each_item(val[i], items);
                }
                
                if (!ops.dd_multi_select && value.length) {
                    value = [value[0]];
                    label = [label[0]];
                }    
                label = label.join(", ");
                
                var input = $(".ac_value", select);
                if (input.is("select")) {
                    var html = "";
                    for (i = 0; i < value.length; i++) 
                        html+= "<option    value='" + value[i] + "' selected></option>";                    
                    
                    input.html(html);
                } else 
                    $(".ac_value", select).val(value.join(","));
                
                if (!label.length) 
                    $(".acs_main", select).html(ops.place_holder).addClass("ac_empty"); else
                    $(".acs_main", select).html(label).removeClass("ac_empty");
                    
                $(".acs_edit > input", select).val(label);
            },
            complete: function(val, select, ops) {
                if (ops._filter) {
                    $(".acs_edit", select).addClass("ac_completed");
                    $.ac.set_selection($(".acs_edit > input", select), ops._filter.length, val.length);
                }                    
            },
            update: function(select, ops) {
                var input = $(".ac_value", select);
                var main = $(".acs_main", select);
                var value,label;
                
                value = [];
                label = [];
                
                $(".ac_item.ac_selected", ops._dropdown).each(function() {
                    value.push($(this).attr("data-value"));
                    label.push($(this).attr("data-label"));
                });                            
                
                label = label.join(", ");    
                
                if (label) 
                    main.html(label).removeClass("ac_empty"); else
                    main.html(ops.place_holder).addClass("ac_empty");
                
                ops._value = label;              
                if (input.is("select")) {
                    var html = "";
                    for (var i = 0; i < value.length; i++) 
                        html+= "<option value='" + value[i] + "' selected></option>";
                    input.html(html);
                } else 
                    input.val(value.join(","));     
                     
                $(".acs_edit > input", select).val(label);  
                
                if (ops.mode == "auto") 
                    $.ac.select.complete(label, select, ops); else
                if (ops.mode == "search") 
                    $.ac.dropdown.build(label, select, ops);
                
                input.val(value).trigger("change");
            },  
            dd_select: function(li, state, select, ops) { 
                $.ac.select.update(select, ops);
            },
            dd_collapse: function(select, ops) {
                var f = $(":focus", select);
                if (f.length)
                    $.ac.clr_selection($(".acs_edit > input", select));
            },    
            dd_filter: function(flt, select, ops) {
                $.ac.dropdown.expand(select, ops);
                $(".ac_item.ac_selected", select).removeClass("ac_selected");
                if (ops.mode == "auto") {
                    $(".ac_value", select).val("");
                    if (flt != "")
                        $.ac.dropdown.select_item($(".ac_item:not(.ac_disabled):visible:first", select), 1, select, ops); 
                } else
                    $.ac.dropdown.focus_item(null, select, ops); 
            },
            load: function(src) {
                var res = {
                    id: $.ac.get_attr(src, "id"),
                    cls: $.ac.get_attr(src, "class"),
                    style: $.ac.get_attr(src, "style"),
                    name: $.ac.get_attr(src, "name"),
                    handler: $.ac.get_attr(src, "handler"),
                    invalid: $.ac.get_attr(src, "invalid", true),
                    tab_index: $.ac.get_attr(src, "tabindex"),
                    disabled: $.ac.get_attr(src, "disabled", true),
                    place_holder: $.ac.get_attr(src, "placeholder"),
                    value: $.ac.get_attr_def(src, "value", ""),
                }
                if (src.is("select")) {
                    var m = (typeof src.attr("multiple") != "undefined") ? "multi" : "single";
                    res.mode = $.ac.get_attr_def(src, "mode", m);
                } else
                    res.mode = $.ac.get_attr(src, "mode");

                if (res.value == "")
                    res.value = []; else                                
                    res.value = res.value.split(",");
                
                return res;
            },
            select_focusin: function() {
                if ($(this).is(".ac_disabled")) return;
                
                var ops = $(this).data("ac")
                
                $(this).addClass("ac_focused");
                if (ops.mode == "search" || ops.mode == "auto")  {
                    $(".acs_main", this).hide();    
                    $(".acs_edit", this).css("opacity", 1);
                }
                
                if (ops.mode == "search" || ops.mode == "auto") {
                    $.ac.dropdown.build($(".acs_edit > input", this).val(), $(this), ops);
                    $.ac.dropdown.focus_item(null, $(this), ops);
                }
                $.ac.dropdown.expand($(this), ops);
            },   
            select_focusleave: function() {
                if ($(this).is(".ac_disabled")) return;
                
                //console.log("focusleave");
                
                var ops = $(this).data("ac");
            
                if (ops.mode == "search" || ops.mode == "auto")  {
                    $(".acs_main", this).show();
                    $(".acs_edit", this).css("opacity", 0);
                    
                    /*var val = $(".ac_value", this).val();
                    if (val == "")
                        $(".acs_main", this).html(ops.place_holder).addClass("ac_empty"); else
                        $(".acs_main", this).removeClass("ac_empty");*/
                    
                    $(".acs_main", this).show();
                    $(".acs_edit", this).css("opacity", 0);
                }
                
                $.ac.dropdown.collapse($(this), ops);
                $(this).removeClass("ac_focused");
            },  
            select_mousedown: function() {
                if ($(this).is(".ac_disabled") || !$(this).is(".ac_focused")) return;  
                
                var ops = $(this).data("ac");
                
                $(".acs_edit", this).removeClass("ac_completed");             

                if (!$(this).is(".ac_expanded")) {
                    if (ops.mode == "search" || ops.mode == "auto") {
                        $.ac.dropdown.build($(".acs_edit > input", this).val(), $(this), ops);
                        $.ac.dropdown.focus_item(null, $(this), ops);
                    }
                    $.ac.dropdown.expand($(this), ops);
                } 
                
            }, 
            select_keyup: function(e) {
                var ops = $(this).data("ac");
                
                if (ops.mode == "single" || ops.mode == "multi") return;
                
                var input = $(".acs_edit > input", this);
                var val = input.val();
                
                var changed = (ops._value != val);
                ops._value = val;
                
                if (!changed) return;    
                
                $.ac.dropdown.filter(val, $(this), ops);
            },  
            select_keydown: function(e) {
                if ($(this).is(".ac_disabled")) return;
                var ops = $(this).data("ac");
                
                if (ops.mode == "search" || ops.mode == "auto") {  
                    if (e.which == $.ac.keys.BCK_SPACE && !$(this).is(".ac_completed")) {
                        var input = $(".acs_edit > input", this);  
                        var val = input.val();
                        var s = $.ac.get_selection(input)[0];
                        
                        val = val.substring(0, s-1);
                        input.val(val);
                        
                        $.ac.select.select_keyup.apply($(this), [e]);
                        
                        return false;
                    }
                    $(".acs_edit", this).removeClass("ac_completed");             
                } else {
                    var curr, next;
                    
                    if (e.which in ops._chars) {
                        if (ops.dd_multi_select)
                            curr = ops._dropdown.find(".ac_item.ac_selected[data-char=" + e.which + "]"); else
                            curr = ops._dropdown.find(".ac_item.ac_focused[data-char=" + e.which + "]");
                        
                        if (curr.length)
                            next = curr.nextAll(".ac_item[data-char=" + e.which + "]:not(.ac_disabled):visible:first"); else
                            next = $(".ac_item[data-char=" + e.which + "]:not(.ac_disabled):visible:first");

                        $.ac.dropdown.focus_item(next, $(this), ops);
                        if (ops.auto_select)
                            $.ac.dropdown.select_item(next, 1, $(this), ops);
                        
                        return false;
                    }
                }
            },
            create: function(src, ops) {     
                var ops = $.extend({}, $.ac.select.def_options, $.ac.select.load(src), ops, $.ac.dropdown.def_options);
                
                if (typeof ops.items == "undefined") {
                    if (src.is("select"))
                        ops.items = src.children("option,optgroup"); else
                    if (src.is("ul"))
                        ops.items = src.children("li,ul");
                }
                
                if (ops.items instanceof $) 
                    ops.items = $.ac.dropdown.load(ops.items);
                
                switch (ops.mode) {
                    case "single": 
                        ops.dd_auto_select = true;
                        break;
                    case "multi":
                        ops.dd_multi_select = true;
                        break;
                    case "search":
                        ops.dd_auto_select = false;
                        ops.dd_flt_deselect = false;
                        ops.dd_endhome_keys = false;
                        ops.dd_filter = $.ac.select.dd_filter;
                        if (typeof ops.filter_mask == "undefined" || ops.filter_mask == null)
                            ops.filter_mask = "__TERM__";
                        break;
                    case "auto":
                        ops.dd_flt_start = true; 
                        ops.dd_auto_select = true; 
                        ops.dd_filter_start = true;
                        ops.dd_flt_deselect = false; 
                        ops.dd_endhome_keys = false;
                        ops.dd_filter = $.ac.select.dd_filter;
                        ops.dd_collapse = $.ac.select.dd_collapse; 
                        if (!ops.filter_mask)
                            ops.filter_mask = "^__TERM__";      
                        break;
                }
                ops.dd_select = $.ac.select.dd_select;
                
                var cls = ops.cls;
                if (ops.invalid)
                    cls+= " ac_invalid";
                if (ops.dd_multi_select)
                    cls+= " ac_multiple";
                if (ops.disabled) {                 
                    cls+= " ac_disabled";
                    ops.tab_index = -1;    
                }
                
                var ctrl_attr = "";
                if (ops.id) 
                    ctrl_attr+= " id='" + ops.id + "'";
                if (ops.style)
                    ctrl_attr+= " style='" + ops.style + "'"; 
                    
                var select = $("<div class='acontrol ac_border " + cls + "'" + ctrl_attr + "></div>").insertAfter(src).addClass("ac_select");
                var ctrl_height = select.height();

                var html = "";                
                if (ops.name.indexOf("[]") == ops.name.length-2) 
                    html+= "    <select class='ac_value' data-name='" + ops.name + "' name='" + ops.name + "' multiple></select>"; else
                    html+= "    <input class='ac_value' type='hidden' data-name='" + ops.name + "' name='" + ops.name + "'>";
                    
                html+= "    <div class='acs_button' tabindex='-1'></div>";
                html+= "    <div class='acs_main'>DUMMY</div>";
                if (ops.mode == "search" || ops.mode == "auto")
                    html+= "    <div class='acs_edit'><input type='text'></div>";
                    
                select.append(html);                
                src.detach().appendTo(select).hide().removeAttr("name id data-name class");
                
                if (ops.mode == "search" || ops.mode == "auto") {
                    $(".acs_edit", select).css("opacity", 0);
                    $(".acs_edit > input", select).attr("tabindex", ops.tab_index).css("height", ops.height + "px");
                } else {
                    select.attr("tabindex", ops.tab_index);
                }
                
                var pl = parseInt(select.css("padding-left"));
                var pr = parseInt(select.css("padding-right"));
                var pt = parseInt(select.css("padding-top"));
                var pb = parseInt(select.css("padding-bottom"));
                
                var inner_height = $(".acs_main", select).height();
                var button_width = $(".acs_button").width();
                var outer_height;
                
                select.data("ac", ops);
                select.css("padding", 0);
                $(".acs_main", select).css({
                    "left" : pl, 
                    "right" : button_width,
                    "top" : pt,
                    "bottom" : pb,
                    "line-height" : ctrl_height + "px",
                });       
                
                var ctrl_height = select.height();                     
                var acs_edit = select.children(".acs_edit");
                acs_edit.css({
                    "padding" : "0 " + pl + "px",
                });
                acs_edit.children("input").css("min-height", ctrl_height - pt - pb);
                
                $(".acs_main", select).html(ops.place_holder).css("position", "absolute");
                $.ac.select.set_value(ops.value, select, ops);
                $.ac.dropdown.create(select, select, ops); 
                $.ac.copy_events(src, $(".ac_value", select), ["change", "input"]);
                
                $(".acs_main, .acs_button", select).mousedown(function(e) {
                    var select = $(this).parents(".ac_select:first");
                    var ops = select.data("ac");
             
                    
                    if (!select.is(".ac_focused")) 
                        select.focus(); else
                    if (!select.is(".ac_expanded"))                        
                        $.ac.dropdown.expand(select, select.data("ac")); else
                        $.ac.dropdown.collapse(select, select.data("ac")); 
                        
                    return false;
                }).mouseup(function(e) {

                });
                
                $.ac.focus_leave(select, $.ac.select.select_focusleave);
                
                select.mousedown($.ac.select.select_mousedown);
                select.focusin($.ac.select.select_focusin);
                select.keydown($.ac.select.select_keydown);
                select.keyup($.ac.select.select_keyup);

                select.children(".acs_button, .acs_main").fadeTo(500, 1).removeClass("ac_loading");
                select.removeClass("ac_loading");
                
                return $(".ac_value", select);
            },
        },
        checklist: {
            def_options: {
                name: "",
                id: "",
                value: "",
                cls: "",
                style: "",
                invert: false,
                mode: "multi", // multi, single, radio
                uncheck: true,
                horiz: false,
                border: false,
                tab_index: 0,
                invalid: false,    
                disabled: false,
                onUpdate: null,
                onCreateItem: null,
            },    
            load_items: function(src, ops) {
                var t, label, res = [];
                ops.items.each(function() {
                    t = null;
                    if ($(this).is("input")) {
                        label = $("label[for='" + ops.name + "'], label[for='" + $(this).attr('id') + "']");
                        if (label.length)
                            t = label.html();
                    } 
                    
                    if (!t)
                        t = $(this).html();
                    
                    res.push({
                        title: t,
                        hint: $.ac.load_attr_def($(this), "hint", ""),
                        value: $.ac.load_attr_def($(this), "value", ""),
                        style: $.ac.load_attr_def($(this), "style", ""),
                        disabled: $.ac.load_attr_def($(this), "disabled", true), 
                        attr: $.ac.load_data($(this)),
                    });
                });    
                
                return res;
            },
            load: function(src) {
                var res = {
                    id: $.ac.get_attr(src, "id"),
                    cls: $.ac.get_attr(src, "class"),
                    style: $.ac.get_attr(src, "style"),
                    name: $.ac.get_attr(src, "name"),
                    border: $.ac.get_attr(src, "border", true),
                    horiz: $.ac.get_attr(src, "horiz", true),
                    invalid: $.ac.get_attr(src, "invalid", true),
                    disabled: $.ac.get_attr(src, "disabled", true),
                    tab_index: $.ac.get_attr(src, "tabindex"),
                    mode: $.ac.get_attr(src, "mode"), 
                    uncheck: $.ac.get_attr(src, "uncheck", true), 
                    value: $.ac.get_attr(src, "value"),
                }
                
                return res;
            },    
            update: function(list, ops) {
                var value = [];        
                var input = $(".ac_value", list);
                
                $(".acl_item", list).each(function() {
                    var chk = $(this).is(".ac_checked");
                    if (ops.invert != chk)
                        value.push($(this).attr("data-value"));
                });
                
                if (input.is("select")) {
                    var html = "";
                    for (var i = 0; i < value.length; i++) 
                        html+= "<option value='" + value[i] + "' selected></option>";
                    
                    input.html(html);
                } else
                    input.val(value.join(","));
                
                if (typeof ops.onUpdate == "function")
                    ops.onUpdate.apply(input);
                    
                input.trigger("update");
                input.trigger("change");
                
            },
            set_value: function(list, value, ops) {
                if ($.isString(value))
                    value = value.split(",");
                
                $.ac.checklist.uncheck_all(list, false);
                var ul = list.children("ul");
                for (var i = 0; i < value.length; i++) 
                    ul.children(".acl_item[data-value='" + value[i] + "']:not(.ac_disabled)").addClass("ac_checked");
                
                $.ac.checklist.update(list, list.data("ac"));
            },
            check_all: function(list, update) {
                var ul = list.children(".acl_items");
                ul.children("li.acl_item:not(.ac_disabled)").addClass("ac_checked");
                
                if (update !== false)
                    $.ac.checklist.update(list, list.data("ac"));
            },
            uncheck_all: function(list, update) {
                var ul = list.children(".acl_items");
                ul.children("li.acl_item").removeClass("ac_checked");
                
                if (update !== false)
                    $.ac.checklist.update(list, list.data("ac"));
            },
            get_value: function(list) {
                return list.find(".ac_value").val();    
            },
            create: function(src, ops) { 
                ops = $.extend({}, $.ac.checklist.def_options, $.ac.checklist.load(src), ops);
                ops.value = (ops.value != "") ? ops.value.split(",") : [];
                
                if (typeof ops.items == "undefined") 
                    ops.items = src.children("li,input");
                
                if (ops.items instanceof $) 
                    ops.items = $.ac.checklist.load_items(src, ops);
                    
                var cls = ops.cls;
                if (ops.disabled) {
                    cls+= " ac_disabled";
                    ops.tab_index = -1;
                }
                if (ops.invalid)
                    cls+= " ac_invalid";
                if (ops.border)
                    cls+= " ac_border";
                
                var ctrl_attr = "";
                var height_set = false;
                
                if (ops.id)
                    ctrl_attr+= "id='" + ops.id + "'";
                if (ops.style) {
                    ctrl_attr+= " style='" + ops.style + "'"; 
                    var css = $.ac.parse_css(ops.style);
                    
                    if (css.height)
                        height_set = css.height;                             
                }                
                var list = $("<div class='acontrol " + cls + "'" + ctrl_attr + "></div>").insertAfter(src).addClass("ac_checklist");
                var ctrl_height = list.height();
                
                var html = "";
                if (ops.name.indexOf("[]") == ops.name.length - 2) 
                    html+= "    <select class='ac_value' data-name='" + ops.name + "' name='" + ops.name + "' multiple></select>"; else
                    html+= "    <input class='ac_value' type='hidden' data-name='" + ops.name + "' name='" + ops.name + "'>";

                html+= "<div class='ac_valign'></div>";
                html+= "<ul class='acl_items'>";
                
                var itm,hint,style,attrs,attr,st,ctab = ops.tab_index;
                for (var i = 0; i < ops.items.length; i++) {
                    itm = ops.items[i];
                    cls = "";
                    
                    if (itm.disabled) 
                        cls+= " ac_disabled"; 
                        
                    st = $.inArray(itm.value.toString(), ops.value);                                         
                    st = (ops.invert) ? st == -1 : st != -1;
                    if (st)
                        cls+= " ac_checked";
                    
                    hint = itm.hint; 
                    if (!hint) hint = '';
                    style = (itm.style) ? " style='" + itm.style + "'" : "";
                    attr = itm.attr;
                    if (typeof attr == "undefined")
                        attr = "";   
                    
                    if (itm.disabled)
                        attr+= " tabindex='-1'"; else
                        attr+= " tabindex='" + ctab + "'";
                    
                    if (ops.tab_index)
                        ctab++;
                    
                    html+= "<li class='acl_item " + cls + "' title='" + hint + "' data-value='" + itm.value + "'" + style + " " + attr + "><div class='ac_bullet'></div>" + itm.title + "</li>";
                    if (!ops.horiz)
                        html+= "<br>";
                }
                
                html+= "</ul>";
                list.append(html).data("ac", ops);
                
                src.detach().appendTo(list).hide().removeAttr("name id data-name class");
                
                var pl = parseInt(list.css("padding-left"));
                var pr = parseInt(list.css("padding-right"));
                var pt = parseInt(list.css("padding-top"));
                var pb = parseInt(list.css("padding-bottom"));
                list.find(".ac_valign").css("height", ctrl_height - 1);
                list.find(".acl_items").css({
                    "padding-left" : pl,
                    "padding-right" : pr,
                    "padding-top" : pt,   
                    "padding-bottom": pb
                });
                list.css({
                    "padding" : 0,
                    "min-height" : ctrl_height,
                    "height" : (height_set !== false) ? height_set : "auto"
                });
                
                if (ops.id)
                    list.attr("id", ops.id);

                if (!ops.value.length && ops.mode == "radio") 
                    $(".acl_item:not(.ac_disabled):first", list).addClass("ac_checked");
                
                $.ac.checklist.update(list, ops);
                $.ac.copy_events(src, $(".ac_value", list), ["change", "input"]);
                
                list.focusin(function() {
                    $(this).addClass("ac_focused");
                });
                $.ac.focus_leave(list, function() {
                    $(this).removeClass("ac_focused"); 
                    $(this).find(".ac_focused").removeClass("ac_focused");
                });
                list.keydown(function(e) {
                    var curr = $(this).find("li:focus");

                    
                });
                $(".acl_item", list).each(function() {
                    $("a", this).click(function(e) {
                        e.stopPropagation();
                    });
                    
                    $(this).keydown(function(e) {
                        if (e.which == $.ac.keys.ENTER) {
                            $(this).click();    
                        
                            e.stopPropagation();
                            return false;
                        } else 
                        if (e.which == $.ac.keys.DOWN) {
                            $(this).nextAll(".acl_item:first").focus();

                            e.stopPropagation();
                            return false;
                        } else 
                        if (e.which == $.ac.keys.UP) {
                            $(this).prevAll(".acl_item:first").focus();

                            e.stopPropagation();
                            return false;
                        } 
                    });
                    $(this).click(function() {
                        if ($(this).is(".ac_disabled")) return;
                        
                        var list = $(this).parents(".ac_checklist:first");
                        var ops = list.data("ac");
                        var chk = $(this).is(".ac_checked");
                        var chg = false;
                        
                        switch (ops.mode) {
                            case "multi":
                                if (chk)                        
                                    $(this).removeClass("ac_checked"); else
                                    $(this).addClass("ac_checked");
                                    
                                chg = true;
                                break;
                            case "single":
                                if (!chk) {
                                    $(".acl_item.ac_checked", list).removeClass("ac_checked");    
                                    $(this).addClass("ac_checked");
                                } else 
                                if (ops.uncheck) 
                                    $(this).removeClass("ac_checked"); 

                                chg = true;
                                    
                                break;                            
                            case "radio":    
                                if (!chk) {
                                    $(".acl_item.ac_checked", list).removeClass("ac_checked");    
                                    $(this).addClass("ac_checked");
                                    chg = true;
                                }                         
                            
                                break;
                        }
                            
                        $.ac.checklist.update(list, ops);
                    });
                });
                
                list.children(".acl_items").fadeTo(1000, 1).removeClass("ac_loading");
                list.removeClass("ac_loading");
                
                return $(".ac_value", list);
            },
        }, 
        checkbox: {
            create: function(src, ops) {
                var res = {
                    id: $.ac.get_attr(src, "id"),
                    cls: $.ac.get_attr(src, "class"),
                    style: $.ac.get_attr(src, "style"),
                    name: $.ac.get_attr(src, "name"),
                    label: $.ac.get_attr(src, "label"),
                    border: $.ac.get_attr(src, "border", true),
                    horiz: $.ac.get_attr(src, "horiz", true),
                    invalid: $.ac.get_attr(src, "invalid", true),
                    disabled: $.ac.get_attr(src, "disabled", true),
                    tab_index: $.ac.get_attr(src, "tabindex"),
                    mode: "single", 
                    value: $.ac.get_attr(src, "value"),
                }    
                
                if (typeof res.value == "undefined") 
                    res.value = src.is(":checked") ? 1 : 0;
                
                var t,label = null;
                
                if (typeof res.label == "undefined") {
                    res.label = "";
                    label = $("label[for='" + res.name + "'], label[for='" + src.attr('id') + "']");
                    if (label.length) {
                        label.detach();    
                        
                        res.label = label.html();
                    }
                }
                
                if (!res.label)
                    res.label = "&nbsp;";
                
                res.items = [{
                    title: res.label,
                    hint: $.ac.get_attr_def(src, "hint", ""),
                    value: 1,
                    style: $.ac.get_attr_def(src, "style", ""),
                }];
                
                var r = $.ac.checklist.create(src, res);
                if (label && label.length) {
                    label.find("a").click(function(e) {
                        e.stopPropagation();
                    });
                }
                
                //tree.children(":visible").fadeTo(1000, 1).removeClass("ac_loading");
                //tree.removeClass("ac_loading");
                    
                return r;                        
            },
            get_value: function(check) {
                return check.find(".ac_value").val();    
            },
            set_value: function(check, val) {
                if (val)
                    check.find(".acl_item").addClass("ac_checked"); else
                    check.find(".acl_item").removeClass("ac_checked"); 
                
                if (val === true) val = 1;
                if (val === false) val = 0;
                check.find(".ac_value").val(val);
            }
        },
        checktree: {
            def_options: {
                name: "",
                id: "",
                cls: "",
                style: "",
                value: "",
                paths: false,  
                mode: "multi", 
                three_state: true, 
                invert: false,
                merge: false,
                border: false,
                tab_index: 0,
                invalid: false,    
                disabled: false,
            },  
            load: function(src) {
                var res = {
                    id: $.ac.get_attr(src, "id"),
                    cls: $.ac.get_attr(src, "class"),
                    style: $.ac.get_attr(src, "style"),
                    mode: $.ac.get_attr(src, "mode"),
                    name: $.ac.get_attr(src, "name"),
                    paths: $.ac.get_attr(src, "paths", true),
                    border: $.ac.get_attr(src, "border", true),
                    invalid: $.ac.get_attr(src, "invalid", true),
                    invert: $.ac.get_attr(src, "invert", true),
                    merge: $.ac.get_attr(src, "merge", true),
                    disabled: $.ac.get_attr(src, "disabled", true),
                    expanded: $.ac.get_attr(src, "expanded", true),
                    tab_index: $.ac.get_attr(src, "tabindex"),
                    three_state: $.ac.get_attr(src, "three_state", true),
                    dependency: $.ac.get_attr(src, "dependency"),
                    state_cookie: $.ac.get_attr(src, "state_cookie"),
                    value: $.ac.get_attr(src, "value"),
                }
                
                return res;
            },   
            item_click: function(e) {
                var item = $(this);
                if (item.is(".ac_disabled")) return;
                if (!item.hasClass("act_check")) return;
                                
                var node = item.parents("li.act_node:first");
                
                var tree = node.parents(".ac_checktree:first");
                var ops = tree.data("ac");
                var state = parseInt(node.attr("data-state"));
                
                if (ops.mode == "single" && !state) {
                    $.ac.tree.reset(tree, ops);
                    $.ac.tree.node_state(node, 1, ops);
                } else 
                if (!ops.three_state) {
                    if (state > 0)  
                        state = 0; else
                        state = 1;
                    $.ac.tree.node_state(node, state, ops); 
                } else
                    $.ac.tree.node_state(node, null, ops);
                    
                $.ac.checktree.update(node.parents(".ac_checktree:first"), ops);
            },
            state_change: function(state, ops) {
                var item = $(this).children(".act_item");
                item.removeClass("ac_checked ac_partial");
                              
                if (state == 1) 
                    item.addClass("ac_checked"); else                                  
                if (state == 2) {
                    if (ops.three_state)
                        item.addClass("ac_partial"); else
                        item.addClass("ac_checked");
                }
                
                var path = $(this).attr("data-path");
                if (ops._queue && path != "")
                    $.ac.queue_remove(ops._queue, path);
            },
            node_build: function(parent, ops, index) {
                var node = $(this);
                var item = node.children(".act_item");   
                var hint = $.ac.get_attr_def(node, "hint", "");
                var value = $.ac.get_attr_def(node, "value", "");
                var check = $.ac.get_attr_def(node, "check", true, true);
                var style = $.ac.get_attr_def(node, "style", "");
                var disabled = $.ac.get_attr_def(node, "disabled", false, true);

                if (disabled)                                
                    item.addClass("ac_disabled");
                    
                var leaf = node.children("ul").length == 0;
                
                if (check) {
                    item.addClass("act_check");
                    item.prepend("<div class='ac_bullet'></div>");    
                }
                
                if (ops.paths) {
                    var path = (parent) ? $.ac.get_attr_def(parent, "path", "") : "";
                    if (value)
                        path+= (path) ? "." + value : value;
                } else
                    var path = value;
                    
                ops._cache[path] = node;
                
                node.attr("data-path", path); 
                var ctab = (ops.tab_index) ? ops.tab_index + index : 0;
                if (disabled)
                    ctab = "-1";
                    
                item.attr("tabindex", ctab).attr("data-index", index);
                item.keydown(function(e) {
                    var node = $(this).parents(".act_node:first");
                    if (e.which == $.ac.keys.TAB && node.is(".act_collapsed")) {
                        node.children(".act_button").click();
                    } else
                    if (e.which == $.ac.keys.ENTER) {
                        $(this).click();
                        
                        e.stopPropagation();                           
                        return false;    
                    } else
                    if (e.which == $.ac.keys.DOWN) {
                        var tree = $(this).parents(".ac_tree");
                        var ind = parseInt($(this).attr("data-index")) + 1;
                        
                        var next = tree.find(".act_item[data-index=" + ind + "]");
                        var node = $(this).parents(".act_node:first");
                        if (node.is(".act_collapsed"))
                            node.children(".act_button").click();
                             
                        next.focus();
                        
                        e.stopPropagation();
                        return false;
                    } else
                    if (e.which == $.ac.keys.UP) {
                        var tree = $(this).parents(".ac_tree");
                        var ind = parseInt($(this).attr("data-index")) - 1;
                        
                        var prev = tree.find(".act_item[data-index=" + ind + "]");
                        prev.focus();
                        
                        e.stopPropagation();
                        return false;
                    } 
                });
                    
                if (ops.invert)
                    item.addClass("ac_checked");
                
                if (ops.mode == "multi" || leaf)
                    item.click($.ac.checktree.item_click);      
                    
                if (hint)
                    item.attr("title", hint);
                
                if (typeof $.ac.checktree.on_build_node == "function")
                    $.ac.checktree.on_build_node.apply(node, parent, ops, index);
            },   
            apply_values: function(tree, ops) {
                ops._queue = $.ac.queue_create(ops.value);

                var st = ops.invert ? 0 : 1;
                var val,node;         
                while (val = $.ac.queue_next(ops._queue)) {
                    if (typeof ops._cache[val] != "undefined") {
                        node = ops._cache[val];
                                                      
                        $.ac.tree.node_state(node, st, ops);
                        $.ac.tree.node_toggle(node, 1, false, ops, true);
                    } else 
                        $.ac.queue_remove(ops._queue, val);
                }
                ops._queue = null;
                $.ac.checktree.update(tree, ops);
            },                            
            update_nodes: function(nodes, values, ops) {
                var pth,st,ch,node,mask,push;
                var ind = ops.invert ? 1 : 0;
                for (var i = 0; i < nodes.length; i++) {
                    node = $(nodes[i]);
                    pth = node.attr("data-path");
                    st = parseInt(node.attr("data-state"));
                                       
                    if (ops.invert)
                        push = pth != "" && st == 0; else
                        push = pth != "" && (st == 1 || ops.partial_state && st == 2);
                    
                    if (push)
                        values.push(pth);
                    
                    /*
                    mask = node.attr("data-mask").split("");
                    if (!parseInt(mask[2 + ind])) continue;
                    if (parseInt(mask[ind]) && ops.collapse_values && pth != "") continue;
                    */
                    
                    ch = node.children("ul").children("li");
                    if (ch.length)
                        $.ac.checktree.update_nodes(ch, values, ops);
                }
            },
            merge_values: function(values) {
                var i,j,val_i,val_j,add,result = [];
                for (i = 0; i < values.length; i++) {
                    val_i = values[i];
                    add = true;
                    for (j = 0; j < result.length; j++) {
                        val_j = result[j];
                        if ((val_j.indexOf(val_i) == 0 || val_i.indexOf(val_j) == 0) && val_i.length > val_j.length) {
                            result[j] = val_i;
                            add = false;    
                        }
                    }
                    
                    if (add) result.push(val_i);
                }  
                
                return result;
            },
            update: function(tree, ops) {
                var values = [];
                var ul = tree.children("ul.ac_tree");
                $.ac.checktree.update_nodes(ul.children("li"), values, ops);
                
                if (ops.merge)
                    values = $.ac.checktree.merge_values(values);
                
                var tree = ul.parents(".ac_checktree:first");
                var input = $(".ac_value", tree);

                if (input.is("select")) {
                    var html = "";
                    for (var i = 0; i < values.length; i++) 
                        html+= "<option value='" + value[i] + "' selected></option>";
                    
                    input.html(html);
                } else
                    input.val(values.join(","));
                
                if (typeof ops.onUpdate == "function")
                    ops.onUpdate.apply(input);
                    
                input.trigger("update");
                input.trigger("change");
            },
            create: function(src, ops) { 
                ops = $.extend({}, $.ac.checktree.def_options, $.ac.tree.def_options, $.ac.checktree.load(src), ops);
                ops.value = (ops.value != "") ? ops.value.split(",") : [];
                
                if (ops.mode == "single" && ops.value.length > 1)
                    ops.value = [ops.value[0]];
                
                var cls = ops.cls;
                if (typeof cls == "undefined")
                    cls = "";
                if (ops.disabled) {
                    cls+= " ac_disabled";
                    ops.tab_index = -1;
                }
                if (ops.invalid)
                    cls+= " ac_invalid";
                if (ops.border)
                    cls+= " ac_border";
                
                var ctrl_attr = "";
                var height_set = false;
                
                if (ops.id) 
                    ctrl_attr+= " id='" + ops.id + "'";
                if (ops.style) {
                    ctrl_attr+= " style='" + ops.style + "'"; 
                    var css = $.ac.parse_css(ops.style);
                    
                    if (css.height)
                        height_set = css.height;                             
                }
                
                var tree = $("<div class='acontrol " + cls + "'" + ctrl_attr + "></div>").insertAfter(src).addClass("ac_checktree");
                var ctrl_height = tree.height();
                
                var html = "";
                if (ops.name.indexOf("[]") == ops.name.length - 2) 
                    html+= "    <select class='ac_value' data-name='" + ops.name + "' name='" + ops.name + "' multiple></select>"; else
                    html+= "    <input class='ac_value' type='hidden' data-name='" + ops.name + "' name='" + ops.name + "'>";
                //html+= "<div class='ac_valign'></div>";
                
                tree.append(html).data("ac", ops);
                src.after(tree).detach().appendTo(tree).removeClass("ac_checktree");
                src.removeAttr("style");
                
                ops.states = true;  
                ops.partial_state = ops.three_state ? 0 : 1;
                ops.initial_state = ops.invert ? 1 : 0;
                ops.onStateChange = $.ac.checktree.state_change;
                ops.onStateChanged = $.ac.checktree.state_changed;
                ops.onCreateNode = $.ac.checktree.node_build;
                ops._cache = {};
                
                $.ac.tree.build(src, ops);
                $.ac.checktree.apply_values(tree, ops);
                
                var pl = parseInt(tree.css("padding-left"));
                var pr = parseInt(tree.css("padding-right"));
                var pt = parseInt(tree.css("padding-top"));
                var pb = parseInt(tree.css("padding-bottom"));
                tree.find(".ac_valign").css("height", ctrl_height - 1);
                tree.find(".ac_tree").css({
                    "padding-left" : pl,
                    "padding-right" : pr,
                    "padding-top" : pt,   
                    "padding-bottom": pb
                });          
                tree.css({
                    "padding" : 0,
                    "min-height" : ctrl_height,
                    "height" : (height_set !== false) ? height_set : "auto"
                });
                
                if (ops.id)
                    tree.attr("id", ops.id);
                
                $.ac.copy_events(src, $(".ac_value", tree), ["change", "input"]);                
                
                tree.focusin(function() {
                    $(this).addClass("ac_focused");
                });
                $.ac.focus_leave(tree, function() {
                    $(this).removeClass("ac_focused"); 
                    $(this).find(".ac_focused").removeClass("ac_focused");
                });         
                
                tree.children(".ac_tree").fadeTo(1000, 1).removeClass("ac_loading");
                tree.removeClass("ac_loading");
                
                return $(".ac_value", tree);
            },
            set_value: function(tree, value) {
                var ops = tree.data("ac");
                
                if ($.isString(value)) {
                    if (value.length)
                        value = value.split(","); else
                        value = [];
                }
                
                ops.value = value;
                $.ac.checktree.uncheck_all(tree, false);
                $.ac.checktree.apply_values(tree, ops);
            },
            check_all: function(tree, update) {
                var ops = tree.data("ac");
                for (var i in ops._cache) 
                    $.ac.tree.node_state($(ops._cache[i]), 1, ops, true); 
                    
                if (update !== false)
                    $.ac.checktree.update(tree, ops);
            },
            uncheck_all: function(tree, update) {
                var ops = tree.data("ac");
                for (var i in ops._cache) 
                    $.ac.tree.node_state($(ops._cache[i]), 0, ops, true); 
                    
                if (update !== false)
                    $.ac.checktree.update(tree, ops);
            },
            get_value: function(list) {
                return list.find(".ac_value").val();    
            },  
                                      
        },   
        selecttree: {
            def_options: {
                name: "",
                id: "",
                cls: "",
                style: "",
                value: "",
                multi: false,
                border: false,
                tab_index: 0,
                invalid: false,  
                paths: false,  
                disabled: false,
            },  
            load: function(src) {
                var res = {
                    id: $.ac.get_attr(src, "id"),
                    cls: $.ac.get_attr(src, "class"),
                    style: $.ac.get_attr(src, "style"),
                    multi: $.ac.get_attr(src, "multi", true),
                    paths: $.ac.get_attr(src, "paths", true),
                    name: $.ac.get_attr(src, "name"),
                    border: $.ac.get_attr(src, "border", true),
                    invalid: $.ac.get_attr(src, "invalid", true),
                    disabled: $.ac.get_attr(src, "disabled", true),
                    expanded: $.ac.get_attr(src, "expanded", true),
                    tab_index: $.ac.get_attr(src, "tabindex"),
                    value: $.ac.get_attr(src, "value"),
                }
                
                return res;
            },   
            item_click: function(e) {
                var item = $(this);
                if (item.is(".ac_disabled")) return;
                
                var node = item.parents("li.act_node:first");
                
                var tree = node.parents(".ac_selecttree:first");
                var ops = tree.data("ac");
                var state = parseInt(node.attr("data-state"));
                if (!item.hasClass("ac_selected")) {
                    if (!ops.multi) 
                        tree.find(".act_item").removeClass("ac_selected");
                    item.addClass("ac_selected");
                } else
                    item.removeClass("ac_selected"); 
                    
                $.ac.selecttree.update(node.parents(".ac_tree:first"), ops);
            },
            node_build: function(parent, ops, index) {
                var node = $(this);
                var item = node.children(".act_item");   
                var hint = $.ac.get_attr_def(node, "hint", "");
                var value = $.ac.get_attr_def(node, "value", "");
                var style = $.ac.get_attr_def(node, "style", "");
                var disabled = $.ac.get_attr_def(node, "disabled", false, true);

                if (disabled)                                
                    item.addClass("ac_disabled");
                    
                var leaf = node.children("ul").length == 0;
                
                var path = (parent) ? $.ac.get_attr_def(parent, "path", "") : "";
                if (value)
                    path+= (path) ? "." + value : value;
                
                ops._cache[path] = node;
                
                node.attr("data-path", path); 
                var ctab = (ops.tab_index) ? ops.tab_index + index : 0;
                if (disabled)
                    ctab = "-1";
                    
                item.attr("tabindex", ctab).attr("data-index", index);
                item.keydown(function(e) {
                    var node = $(this).parents(".act_node:first");
                    if (e.which == $.ac.keys.TAB && node.is(".act_collapsed")) {
                        node.children(".act_button").click();
                    } else
                    if (e.which == $.ac.keys.ENTER) {
                        $(this).click();
                        
                        e.stopPropagation();                           
                        return false;    
                    } else
                    if (e.which == $.ac.keys.DOWN) {
                        var tree = $(this).parents(".ac_tree");
                        var ind = parseInt($(this).attr("data-index")) + 1;
                        
                        var next = tree.find(".act_item[data-index=" + ind + "]");
                        var node = $(this).parents(".act_node:first");
                        if (node.is(".act_collapsed"))
                            node.children(".act_button").click();
                             
                        next.focus();
                        
                        e.stopPropagation();
                        return false;
                    } else
                    if (e.which == $.ac.keys.UP) {
                        var tree = $(this).parents(".ac_tree");
                        var ind = parseInt($(this).attr("data-index")) - 1;
                        
                        var prev = tree.find(".act_item[data-index=" + ind + "]");
                        prev.focus();
                        
                        e.stopPropagation();
                        return false;
                    } 
                });
                    
                if (ops.invert)
                    item.addClass("ac_selected");
                
                item.click($.ac.selecttree.item_click);      
                    
                if (hint)
                    item.attr("title", hint);
            },   
            apply_values: function(tree, ops) {
                var node;
                var val, s, _value = [];
                for (var i = 0; i < ops.value.length; i++) {
                    val = ops.value[i];
                    
                    if (ops.paths) 
                        node = tree.find(".act_node[data-path='" + val.replace(".", "\\.") + "']"); else
                        node = tree.find(".act_node[data-value='" + val + "']");
                        
                    if (node.length) {
                        node.children(".act_item").addClass("ac_selected");    
                        _value.push(val);
                    }
                }
                
                var input = $(".ac_value", tree.closest(".acontrol"));
                if (input.is("select")) {
                    var html = "";
                    for (var i = 0; i < _value.length; i++) 
                        html+= "<option value='" + _value[i] + "' selected></option>";
                    
                    input.html(html);
                } else
                    input.val(_value.join(","));                
            },                            
            update: function(ul, ops) {
                var values = [];
                
                var sel = ul.find(".act_item.ac_selected");
                var val;
                for (var i = 0; i < sel.length; i++) {
                    if (ops.paths)
                        val = $(sel[i]).parent().attr("data-path"); else
                        val = $(sel[i]).parent().attr("data-value");
                        
                    values.push(val);                                         
                }
                
                var tree = ul.parents(".ac_selecttree:first");
                var input = $(".ac_value", tree);
                
                if (input.is("select")) {
                    var html = "";
                    for (var i = 0; i < values.length; i++) 
                        html+= "<option value='" + value[i] + "' selected></option>";
                    
                    input.html(html);
                } else
                    input.val(values.join(","));
                
                if (typeof ops.onUpdate == "function")
                    ops.onUpdate.apply(input);
                    
                input.trigger("update");
                input.trigger("change");
            },
            create: function(src, ops) { 
                ops = $.extend({}, $.ac.selecttree.def_options, $.ac.tree.def_options, $.ac.selecttree.load(src), ops);
                ops.value = (ops.value != "") ? ops.value.split(",") : [];
                
                if (!ops.multi && ops.value.length > 1)
                    ops.value = [ops.value[0]];
                
                var cls = ops.cls;
                if (typeof cls == "undefined")
                    cls = "";
                if (ops.disabled) {
                    cls+= " ac_disabled";
                    ops.tab_index = -1;
                }
                if (ops.invalid)
                    cls+= " ac_invalid";
                if (ops.border)
                    cls+= " ac_border";
                
                var ctrl_attr = "";
                if (ops.id) 
                    ctrl_attr+= " id='" + ops.id + "'";
                if (ops.style)
                    ctrl_attr+= " style='" + ops.style + "'"; 
                
                var tree = $("<div class='acontrol " + cls + "'" + ctrl_attr + "></div>").insertAfter(src).addClass("ac_selecttree");
                var ctrl_height = tree.height();
                
                var html = "";
                if (ops.name.indexOf("[]") == ops.name.length - 2) 
                    html+= "    <select class='ac_value' data-name='" + ops.name + "' name='" + ops.name + "' multiple></select>"; else
                    html+= "    <input class='ac_value' type='hidden' data-name='" + ops.name + "' name='" + ops.name + "'>";
                //html+= "<div class='ac_valign'></div>";
                
                tree.append(html).data("ac", ops);
                src.after(tree).detach().appendTo(tree).removeClass("ac_selecttree");
                src.removeAttr("style");
                
                //ops.states = true;  
                //ops.initial_state = ops.invert ? 1 : 0;
                //ops.onStateChange = $.ac.checktree.state_change;
                //ops.onStateChanged = $.ac.checktree.state_changed;
                ops.onCreateNode = $.ac.selecttree.node_build;
                ops._cache = {};
                
                $.ac.tree.build(src, ops);
                $.ac.selecttree.apply_values(src, ops);
                
                var pl = parseInt(tree.css("padding-left"));
                var pr = parseInt(tree.css("padding-right"));
                var pt = parseInt(tree.css("padding-top"));
                var pb = parseInt(tree.css("padding-bottom"));
                tree.find(".ac_valign").css("height", ctrl_height - 1);
                tree.find(".ac_tree").css({
                    "padding-left" : pl,
                    "padding-right" : pr,
                    "padding-top" : pt,   
                    "padding-bottom": pb
                });
                tree.css({
                    "padding" : 0,
                    "min-height" : ctrl_height,
                    "height" : "auto"
                });
      
                
                if (ops.id)
                    tree.attr("id", ops.id);
                
                $.ac.copy_events(src, $(".ac_value", tree), ["change", "input"]);                
                
                tree.focusin(function() {
                    $(this).addClass("ac_focused");
                });
                $.ac.focus_leave(tree, function() {
                    $(this).removeClass("ac_focused"); 
                    $(this).find(".ac_focused").removeClass("ac_focused");
                });     
                
                tree.children(".ac_tree").fadeTo(500, 1).removeClass("ac_loading");
                tree.removeClass("ac_loading");

                return $(".ac_value", tree);
            },
            set_value: function(tree, value) {
                var ops = tree.data("ac");
                
                if ($.isString(value)) {
                    if (value.length)
                        value = value.split(","); else
                        value = [];
                }
                
                ops.value = value;
                $.ac.checktree.apply_values(tree, ops);
            },
            get_value: function(list) {
                return list.find(".ac_value").val();    
            },                            
        },           
    }         
    $.ac_init = function(ops) {
        $.extend($.ac.def_options, ops);    
    }
    $.fn.ac_edit = function(ops) {
        var args = $.ac.get_args(arguments);
        var ops = args[0];
        
        if (args[1])
            $(this).bind("ac_created", args[1]); 
            
        var trg,res = $();
        
        $(this).each(function() {
            if ($(this).is(".acontrol")) return;  
                    
            trg = $.ac.edit.create($(this), ops);
            res = res.add(trg);    
                              
            $(this).trigger("ac_created", [trg]);   
        });
        
        return res;
    }
    $.fn.ac_select = function(ops) {
        var args = $.ac.get_args(arguments);
        var ops = args[0];
        
        if (args[1])
            $(this).bind("ac_created", args[1]); 
            
        var trg,res = $();
        
        $(this).each(function() {
            if ($(this).is(".acontrol")) return;
            
            trg = $.ac.select.create($(this), ops);
            res = res.add(trg);  
            
            $(this).trigger("ac_created", [trg]);     
        });
        
        return res;
    }
    $.fn.ac_slider = function(ops) {
        var args = $.ac.get_args(arguments);
        var ops = args[0];
        
        if (args[1])
            $(this).bind("ac_created", args[1]); 
            
        var trg,res = $();
        
        $(this).each(function() {
            if ($(this).is(".acontrol")) return;
            
            trg = $.ac.slider.create($(this), ops);
            res = res.add(trg);
            
            $(this).trigger("ac_created", [trg]);   
        });
        
        return res;
    }
    $.fn.ac_checklist = function(ops) {
        var args = $.ac.get_args(arguments);
        var ops = args[0];
        
        if (args[1])
            $(this).bind("ac_created", args[1]); 
        
        var trg,res = $();
        
        $(this).each(function() {
            if ($(this).is(".acontrol")) return;
            
            trg = $.ac.checklist.create($(this), ops);
            res = res.add(trg);  
            
            $(this).trigger("ac_created", [trg]);     
        });
        
        return res;
    } 
    $.fn.ac_checktree = function(ops) {
        var args = $.ac.get_args(arguments);
        var ops = args[0];
        
        if (args[1])
            $(this).bind("ac_created", args[1]); 
        
        var trg,res = $();
        
        $(this).each(function() {
            if ($(this).is(".acontrol")) return;
            
            trg = $.ac.checktree.create($(this), ops);
            res = res.add(trg);  
                            
            $(this).trigger("ac_created", [trg]);     
        });
        
        return res;
    }    
    $.fn.ac_selecttree = function(ops) {
        var args = $.ac.get_args(arguments);
        var ops = args[0];
        
        if (args[1])
            $(this).bind("ac_created", args[1]); 
        
        var trg,res = $();
        
        $(this).each(function() {
            if ($(this).is(".acontrol")) return;
            
            trg = $.ac.selecttree.create($(this), ops);
            res = res.add(trg);  
                            
            $(this).trigger("ac_created", [trg]);     
        });
        
        return res;
    }        
    $.fn.ac_checkbox = function(ops) {
        var args = $.ac.get_args(arguments);
        var ops = args[0];
        
        if (args[1])
            $(this).bind("ac_created", args[1]); 
   
        var trg,res = $();
        
        $(this).each(function() {
            if ($(this).is(".acontrol")) return;
            
            trg = $.ac.checkbox.create($(this), ops);
            res = res.add(trg);   
                                              
            $(this).trigger("ac_created", [trg]);    
        });
        
        return res;
    }
    $.fn.ac_create = function(callback) {
        $(".ac_edit, .ac_select, .ac_checklist, .ac_checkbox, .ac_checktree, .ac_selecttree", this).each(function() {
            if ($(this).is(".acontrol")) return;
            
            var ag = $(this).parents(".ac_noautogen:first");
            if (ag.length) return;            
            
            if ($(this).is(".ac_edit"))
                $(this).ac_edit(); else
            if ($(this).is(".ac_select"))
                $(this).ac_select(); else
            if ($(this).is(".ac_checklist"))
                $(this).ac_checklist(); else
            if ($(this).is(".ac_checkbox"))
                $(this).ac_checkbox(); else
            if ($(this).is(".ac_checktree"))
                $(this).ac_checktree(); else
            if ($(this).is(".ac_selecttree"))
                $(this).ac_selecttree();
        });
        
        if (typeof callback == "function")
            callback.apply(this);
    }    
    $.fn.ac_value = function(val) {                    
        var ctrl = $(this);
        
        if (!ctrl.hasClass("acontrol")) 
            ctrl = ctrl.parents(".acontrol:first");
        
        if (!ctrl.length) return; 
        
        if (typeof val == "undefined") {
            if (ctrl.is(".ac_edit")) 
                return $.ac.edit.get_value(ctrl); else
            if (ctrl.is(".ac_select")) 
                return $.ac.select.get_value(ctrl); else
            if (ctrl.is(".ac_checkbox"))
                return $.ac.checkbox.get_value(ctrl); else
            if (ctrl.is(".ac_checklist"))
                return $.ac.checklist.get_value(ctrl); else
            if (ctrl.is(".ac_checktree"))
                return $.ac.checktree.get_value(ctrl);
                
        } else {
            if (ctrl.is(".ac_select")) 
                $.ac.select.set_value([val], ctrl, ctrl.data("ac")); else
            if (ctrl.is(".ac_edit")) 
                $.ac.edit.set_value(ctrl, val); else
            if (ctrl.is(".ac_checkbox")) 
                $.ac.checkbox.set_value(ctrl, val); else
            if (ctrl.is(".ac_checklist"))
                $.ac.checklist.set_value(ctrl, val); else
            if (ctrl.is(".ac_checktree"))
                $.ac.checktree.set_value(ctrl, val);
            
            return ctrl;
        }
    }
    $.fn.ac_menu = function(content) {
        var ctrl = $(this);
        
        if (!ctrl.hasClass("acontrol"))
            ctrl = ctrl.parents(".acontrol:first");
        
        if (ctrl.is(".ac_edit")) 
            $.ac.edit.gen_menu(ctrl, content); 
    }
    $.fn.ac_options = function(new_ops) {
        var ops = $(this).data("ac");
        
        if (typeof new_ops != "undefined")
            $.extend(ops, new_ops);    
        
        return ops;
    }
    $.fn.acontrol = function(callback) {
        if ($(this).is(".acontrol")) return;
            
        if ($(this).is(".ac_edit"))
            $(this).ac_edit({}, callback); else
        if ($(this).is(".ac_select"))
            $(this).ac_select({}, callback); else
        if ($(this).is(".ac_checklist"))
            $(this).ac_checklist({}, callback); else
        if ($(this).is(".ac_checkbox"))
            $(this).ac_checkbox({}, callback); else
        if ($(this).is(".ac_checktree"))
            $(this).ac_checktree({}, callback); 
              
    }
    $.fn.update = function(callback) {
        $(this).bind("update", callback);    
    }
    /*$.ac_initialize = function() {
        console.log("init controls", $.ac.controls);
        var elem;
        for (var i = 0; i < $.ac.controls.length; i++) {
            elem = $("#" + $.ac.controls[i]);
            
            if (elem.is(".acontrol")) return;
            
            if (elem.is(".ac_edit"))
                elem.ac_edit(); else
            if (elem.is(".ac_select"))
                elem.ac_select(); else
            if (elem.is(".ac_checklist"))
                elem.ac_checklist(); else
            if (elem.is(".ac_checkbox"))
                elem.ac_checkbox(); else
            if (elem.is(".ac_checktree"))
                elem.ac_checktree();                   
        }  
        
        $.ac.controls = [];
    } */
})( jQuery );        

