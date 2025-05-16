/*
    empty tree drop
    dropzone je drugo kot hook


*/

(function($){  
    $.isString = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object String]');
    }
    $.dyntree = {
        def_options: {
            list_selector: "ul",
            item_selector: "div",
            hook_selector: "dyntree_hook",
            treshold: 10,
            expand_all: true,
            cross_tree: true,
            detach_z: 10000,
            detach: true,
            scroll_speed: 10,
            scroll_treshold: 20,
            auto_scroll: true,  
            drag_treshold: 10, 
            spacers: false,
            
        //  callbacks
            loaded: null,
            drop: null,
            nodeUpdate: null,
            nodeEmpty: null,
            dropValid: null, 
            dragPosition: null,
            beforeDrop: null,
        },
        def_add_options: {
            content: "",
            position: "inside"  
        },
        id_counter: 0,
        counter: 0,
        dragging: null,
        scrolling: null,
        global_events: false,
        
        scroll_callback: function() {
            var target = $.dyntree.scrolling.target; 
            var ops = target.data("dyntree");
            
            if (!ops) return;                                          
            
            target[0].scrollLeft+= ops.scroll_speed * $.dyntree.scrolling.dir_x;
            target[0].scrollTop+= ops.scroll_speed * $.dyntree.scrolling.dir_y;
            
            $.dyntree.scrolling.timeout = setTimeout($.dyntree.scroll_callback, 50);
        },
        scroll_stop: function() {
            if ($.dyntree.scrolling)
                clearTimeout($.dyntree.scrolling.timeout);    
                            
            $.dyntree.scrolling = null;    
        },
        scroll_check: function(target, px, py, ops) {
            if (!ops.auto_scroll) return false;
            
           
            var width = target.children(".dyntree_wcalc");
            if (!width.length)
                width = $("<div class='dyntree_wcalc' style='position: absolute; left: 0; right: 0'></div>").appendTo(target);

            var height = target.children(".dyntree_hcalc");
            if (!height.length)
                height = $("<div class='dyntree_hcalc' style='position: absolute; top: 0; bottom: 0'></div>").appendTo(target);
            
            var target_ofs = target.offset();
            var target_w = width.width();
            var target_h = height.height();
            
            var dir_x = 0;
            var dir_y = 0;
            
            var x1 = target_ofs.left;
            var x2 = target_ofs.left + target_w;
            
            var y1 = target_ofs.top;
            var y2 = target_ofs.top + target_h;

            if (px > x1 - ops.scroll_treshold && px < x1) { px = target_ofs.left; dir_x = -1 }
            if (px > x2 && px < x2 + ops.scroll_treshold) { px = target_ofs.left + target_w; dir_x = 1 }

            if (py > y1 - ops.scroll_treshold && py < y1) { py = target_ofs.top; dir_y = -1 }
            if (py > y2 && py < y2 + ops.scroll_treshold) { py = target_ofs.top + target_h; dir_y = 1 }
            
            if (dir_x || dir_y) {
                if (!$.dyntree.scrolling) 
                    $.dyntree.scrolling = {
                        timeout: setTimeout($.dyntree.scroll_callback, 50)
                    };
                    
                $.dyntree.scrolling.target = target;
                $.dyntree.scrolling.dir_x = dir_x;
                $.dyntree.scrolling.dir_y = dir_y;
                
                return true;
            }
            
            $.dyntree.scroll_stop();
            
            return false;
        },        

        invalidate_drag: function() {
            $("body").addClass("dyntree_invalid");
            
            $.dyntree.dragging.valid = false;
        }, 
        validate_drag: function(tree) {
            $("body").removeClass("dyntree_invalid");
            $.dyntree.scroll_stop();                   
            
            $.dyntree.dragging.valid = true;
        },
        
        drag_position: function(y, h, inside, ops) {
            if (y < 20)
                return -1; else
            if (inside)
                return 0; else
            if (y > h - 20)
                return 1; else
                return false;
        },
        drop_valid: function(tree, node, ops) {
            if (!ops.cross_tree && !tree.is($.dyntree.dragging.tree)) return false;
            
            var source = $.dyntree.dragging.source;
            if (typeof ops.dropValid == "function") 
                if (ops.dropValid.apply(node, [source, tree, source.parents(".dyntree:first")]) === false) return false;
            
            var drop = node.attr("data-drop");
            var type = source.attr("data-type");
            
            if (typeof drop == "undefined" || drop == "*" || drop == "1" || drop == "true") return true;
            if (typeof type == "undefined") return false;
            
            type = "," + type + ",";
            drop = "," + drop + ",";
            
            return drop.indexOf(type) != -1;
        },
        shadow: function(state) {
            var shadow = $.dyntree.dragging.shadow;
            if (!state) 
                shadow.hide(); else
            if (!shadow.is(":visible"))
                shadow.detach().insertAfter($.dyntree.dragging.holder).show();
        },
        item_mousedown: function(e) {
            if (e.which != 1) return;
            
            $("body").addClass("prevent_selection");
            
            var node = $(this).parents(".dyntree_node:first");
            var tree = node.parents(".dyntree:first");
            var ops = tree.data("dyntree");
            var item = $(this).parents(".dyntree_item:first");
            var css = {
                "height" : item.outerHeight() + "px",
                "margin-top" : item.css("margin-top"), 
                "margin-left" : item.css("margin-left"), 
                "margin-bottom" : item.css("margin-bottom"), 
                "margin-right" : item.css("margin-right")
            }

            var holder = $("<li class='dyntree_holder'><div></div></li>").insertAfter(node).attr("id", "dyntree_" + $.dyntree.id_counter++);
            var shadow = $("<li class='dyntree_shadow'><div></div></li>").insertAfter(node).attr("id", "dyntree_" + $.dyntree.id_counter++); 
            
            holder.children("div").css(css);
            shadow.hide().children("div").css(css);
            
            if (ops.detach) {
                var cursor = $("#dyntree_cursor");
                var clone = node.clone().appendTo(cursor.children("ul")).addClass("dyntree_detached");
                clone.css({
                    width: node.width() + "px",
                    height: node.height() + "px"
                });
                
                cursor.show();                
                $.dyntree.update_cursor(e);
            }
            
            node.hide();
            
            $.dyntree.dragging = {
                tree: node.parents(".dyntree:first"),
                source: node,             
                holder: holder,
                shadow: shadow,
                valid: true
            }              
            
            $.dyntree.validate_drag(tree);
            e.stopPropagation();    
        },        
        item_mousemove: function(e) {
            if (!$.dyntree.dragging) return;
            
            $.dyntree.update_cursor(e);
            
            var node = $(this).parents(".dyntree_node:first");
            var tree = node.parents(".dyntree:first");
            var ops = tree.data("dyntree");
            
            var ofs = $(this).offset();
            var x = e.originalEvent.pageX - ofs.left;
            var y = e.originalEvent.pageY - ofs.top;
            
            var h = $(this).outerHeight();
            var pos;
            
            var ul = node.children(ops.list_selector);
            var pos = $.dyntree.drag_position.apply(node, [y, h, ul.length && node.hasClass("dyntree_expanded"), ops]);
            
            var valid; 
            if (pos === 0)            
                valid = $.dyntree.drop_valid(tree, node, ops); else 
                valid = $.dyntree.drop_valid(tree, node.parents(".dyntree_node:first"), ops);
            
            if (valid) {   
                $.dyntree.validate_drag(tree);
                
                if (pos !== false) {
                    $.dyntree.shadow(!tree.is($.dyntree.dragging.tree));
                    
                    var holder = $.dyntree.dragging.holder.detach().show();
                    
                    if (pos == 0)                                  
                        holder.prependTo(node.children("ul")); else
                    if (pos == -1)
                        holder.insertBefore(node); else
                        holder.insertAfter(node);
                }
            } else {
                $.dyntree.invalidate_drag();
            }
                
            e.stopPropagation();    
        },
        tree_mousemove: function(e) {
            if (!$.dyntree.dragging) return; 
            
            var valid = $.dyntree.drop_valid($(this), $(this), $(this).data("dyntree"));
            if (!valid) return;
            
            $.dyntree.validate_drag($(this));
            
            if (!$(this).hasClass("dyntree_parent")) {
                $.dyntree.shadow(!$(this).is($.dyntree.dragging.tree));
                $.dyntree.dragging.holder.detach().appendTo($(this).children("ul"));
            }
            
            $.dyntree.update_cursor(e);
            e.stopPropagation(); 
        },
        doc_mousemove: function(e) { 
            if (!$.dyntree.dragging) return; 
                    
            //$.dyntree.invalidate_drag();
            $.dyntree.update_cursor(e);
            
            var ops = $.dyntree.dragging.tree.data("dyntree");

            $.dyntree.scroll_check($.dyntree.dragging.tree, e.pageX, e.pageY, ops);    
        },
        doc_mouseup: function(e) {
                //console.log("up?", $.dyntree.dragging);
            if (!$.dyntree.dragging) return; 
                                                
            var tree = $.dyntree.dragging.holder.parents(".dyntree:first");
            var ops = tree.data("dyntree");
            
            $.dyntree.scroll_stop();
            
            if ($.dyntree.dragging.valid) {
                if (typeof ops.beforeDrop == "function") 
                    ops.beforeDrop.apply($.dyntree.dragging.source);

                var old_parent = $.dyntree.dragging.source.parents(".dyntree_node:first");
                $.dyntree.dragging.source.detach().insertAfter($.dyntree.dragging.holder);
                $.dyntree.dragging.holder.remove();
                $.dyntree.dragging.source.show();
                
                if (!old_parent.children("ul").children("li.dyntree_node").length) {
                    if (typeof ops.nodeEmpty == "function")
                        ops.nodeEmpty.apply(old_parent);
                        
                    old_parent.removeClass("dyntree_parent");
                }
                
                var new_parent = $.dyntree.dragging.source.parents(".dyntree_node:first");
                new_parent.addClass("dyntree_parent");
                
                if (typeof ops.drop == "function") 
                    ops.drop.apply($.dyntree.dragging.source, [new_parent]);
                                
                $.dyntree.update(tree, ops);                  
            } else {
                $.dyntree.dragging.holder.remove();
                $.dyntree.dragging.source.show();
            }        
            $.dyntree.dragging.shadow.remove();
            $.dyntree.dragging = null;          
            
            var cursor = $("#dyntree_cursor");
            if (ops.detach)
                cursor.children("ul").html("");
            cursor.hide();
            
            $("body").removeClass("prevent_selection");
            $("body").removeClass("dyntree_invalid");
            
            e.stopPropagation();        
        },        
        
        update_cursor: function(e) {
            var cursor = $("#dyntree_cursor");
            cursor.css({left: e.clientX + 5, top: e.clientY + 5});
        },
        update: function(tree, ops) {
            if (typeof ops.nodeUpdate != "function") return;
            
            $.dyntree.counter = 0;
            $.dyntree.update_nodes(tree.children("ul"), 0, null, ops);
        },     
        update_nodes: function(list, level, parent, ops) {
            var node, nodes = list.children("li.dyntree_node");
            /*if (parent)
                parent = parent.children(ops.item_selector);*/
                
            for (var i = 0; i < nodes.length; i++) {
                node = $(nodes[i]);
                
                ops.nodeUpdate.apply(node, [i, parent, level, $.dyntree.counter]);
                
                $.dyntree.update_nodes(node.children("ul"), level+1, node, ops);
            }
        },
        init_node: function(node, ops) {
            if (node.hasClass("dyntree_node")) return;
            if (!ops) {
                var tree = node.parents(".dyntree:first");
                if (!tree.length) return;
                
                ops = tree.data("dyntree");
            }
            
            if (ops.expandable) {
                var button = $("<div class='dyntree_button'/>").prependTo(node);
                button.click(function() {
                    var node = $(this).parent();
                    if (node.hasClass("dyntree_expanded")) {
                        node.children(".dyntree_list").stop().slideUp(200);
                        node.removeClass("dyntree_expanded");
                    } else {
                        node.children(".dyntree_list").stop().slideDown(200);
                        node.addClass("dyntree_expanded");
                    } 
                });
            }            
            
            node.addClass("dyntree_node");
            if (ops.expand_all)
                node.addClass("dyntree_expanded");
            
            var item = node.children(ops.item_selector);
            var list = node.children("ul");
            
            var hook;    
            if (typeof item.attr("draggable") != "undefined" || item.hasClass(ops.hook_selector))
                hook = item; else
                hook = item.find("[draggable], ." + ops.hook_selector);
            
            if (hook && !hook.length)
                hook = false;
            
            if (!ops.expand_all)
                list.hide();
                
            var id = node.attr("id");
            if (!id) {
                id = "dyntree_" + $.dyntree.id_counter++;
                node.attr("id", id);  
            }
            
            if (hook) {
                hook.addClass("dyntree_hook");
                hook.bind("mousedown", $.dyntree.item_mousedown);
            }
            item.bind("mousemove", $.dyntree.item_mousemove);
            
            item.addClass("dyntree_item");
            list.addClass("dyntree_list");
        },
        init_nodes: function(ul, ops, level, parent) { 
            ul.addClass("dyntree_list");
            var li = ul.children("li");
            if (li.length)
                ul.parent().addClass("dyntree_parent");            
            
            if (parent)
                parent = parent.children(ops.item_selector); 
                
            li.each(function(i) {
                $.dyntree.init_node($(this), ops);
                
                var list = $(this).children("ul");
                var sub = list.children("li");
                if (sub.length) 
                    $.dyntree.init_nodes(list, ops);
            });
                                         
            if (ops.spacer) {
                var spacer = $("<li class='dyntree_spacer' style='z-index: " + level + "'><div style='height: " + ops.drag_treshold + "px'></div></li>").appendTo(ul);
                spacer.bind("mousemove", function(e) {
                    if (!$.dyntree.dragging) return;
                    
                    var parent = $(this).parents(".dyntree_node:first");
                    var tree = $(this).parents(".dyntree:first");
                    var ops = tree.data("dyntree");

                    $.dyntree.validate_drag(tree);
                    
                    if ($.dyntree.drop_valid(tree, parent, ops)) 
                        $.dyntree.dragging.holder.detach().insertBefore($(this));
                        
                    $.dyntree.update_cursor(e.originalEvent);

                    e.preventDefault(); 
                    e.stopPropagation(); 
                });
            }
        },
        add_item: function(src, options) {
            var ops;
            if ($.isString(options)) 
                ops = $.extend({}, $.dyntree.def_add_options, { content: options }); else
                ops = $.extend({}, $.dyntree.def_add_options, options); 
                
            var tree;
            var node = $(ops.content);           
            if (src.hasClass("dyntree")) {     
                if (ops.position == "inside_last")
                    src.children("ul").append(node); else
                    src.children("ul").prepend(node);   
                
                src.addClass("dyntree_expanded dyntree_parent");
                tree = src;
            } else {                                                            
                if (!src.hasClass("dyntree_node")) 
                    src = src.parentsUntil(".dyntree", ".dyntree_node:first"); 
                    
                if (!src.length) return;
                switch (ops.position) {
                    case "inside":
                    case "inside_last":
                        src.children("ul").append(node);
                        break;
                    case "inside_first":
                        src.children("ul").prepend(node);
                        break;
                    case "before":
                        node.insertBefore(src);
                        src.parents(".dyntree_node").addClass("dyntree_expanded dyntree_parent");
                        break;
                    case "after":
                        node.insertAfter(src);
                        src.parents(".dyntree_node").addClass("dyntree_expanded dyntree_parent");                        
                        break;
                }
                
                tree = node.parents(".dyntree:first");
            }
            
            var tree_ops = tree.data("dyntree");
            $.dyntree.init_node(node, tree_ops);
            $.dyntree.update(tree, tree_ops);

            return node;
        },
        remove_item: function(src, arg) {
            if (src.hasClass("dyntree")) 
                src = arg; else
            if (!src.hasClass("dyntree_node"))
                src = src.parentsUntil(".dyntree", ".dyntree_node:first"); 
            
            if (!src.hasClass("dyntree_node")) return;
            
            var parent = src.parents(".dyntree_node:first");
        
            var tree = src.parents(".dyntree:first");
            var ops = tree.data("dyntree");
            src.remove();
            
            if (parent.length) {
                var list = parent.children("ul");
                if (!list.children(".dyntree_node").length) {
                    parent.removeClass("dyntree_parent");   
                     
                    if (typeof ops.nodeEmpty == "function")
                        ops.nodeEmpty.apply(parent);
                }
            } 
            
            $.dyntree.update(tree, ops);
        },
        create: function(options) { 
            if ($(this).hasClass("dyntree")) return $(this);
            
            var ops = $.extend({}, $.dyntree.def_options, options);
            
            if (!ops.expandable)
                ops.expand_all = true;
            
            var css_pos = $(this).css("position");
            if (css_pos != "relative" && css_pos != "absolute")
                $(this).css("position", "relative");           
                 
            $(this).data("dyntree", ops).addClass("dyntree dyntree_node");
            $(this).bind("mousemove", $.dyntree.tree_mousemove);
            if (ops.expandable)
                $(this).addClass("dyntree_expandable");
                
            var ul = $(this).children("ul");
            ul.addClass("dyntree_root");
            $.dyntree.counter = 0;
            $.dyntree.init_nodes(ul, ops, 0, null);
            $.dyntree.update($(this), ops);
            
            var cont = $("#dyntree_cont");
            if (!cont.length) {
                var html = "<div id='dyntree_cont'>";
                html+= "<div id='dyntree_cursor' style='z-index: " + ops.detach_z + "'>";
                html+= "<ul></ul>";
                html+= "</div>";
                
                cont = $(html).appendTo($("body")).bind("mousemove", function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    
                    return false;
                });
            }
            
            if (typeof ops.loaded == "function")
                ops.loaded.apply(this);
            
            if (!$.dyntree.global_events) {
                $(document).bind("mouseup", $.dyntree.doc_mouseup);
                $(document).bind("mousemove", $.dyntree.doc_mousemove);
                
                $.dyntree.global_events = true;
            }
        }
    }

    $.fn.dynamic_tree = function() {
        var method = "create";
        var arg = {};
        
        if (arguments.length == 1) {
            if ($.isString(arguments[0]))
                method = arguments[0]; 
            else {
                method = "create";
                arg = arguments[0];
            }
        } else
        if (arguments.length > 1) {
            method = arguments[0];
            arg = arguments[1];
        }
        
        var res = $();
        $(this).each(function() {
            switch (method) {
                case "create":
                    res = res.add($.dyntree.create.apply(this, [arg]));
                    break;
                case "update":
                    res = res.add($.dyntree.update($(this), arg));
                    break;      
                case "remove":
                case "delete":
                    res = res.add($.dyntree.remove_item($(this), arg));
                    break;
                case "register": 
                    $.dyntree.init_node($(this));
                    $(this).parents(".dyntree_node:first").addClass("dyntree_parent");
                    
                    res = res.add(this);
                    break;
                case "add":
                    res = res.add($.dyntree.add_item($(this), arg));
                    break;
            }
        });
        
        return res;
    }
})( jQuery );        

