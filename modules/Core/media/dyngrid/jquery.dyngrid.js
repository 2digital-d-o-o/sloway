/*
    drag over se more zbrisat title
    dnd iz druzga grida?!
    optimizirat detachane pri dnd
    scroll to item
    
    selecting se vedno drka
*/
(function($){  
    $.isString = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object String]');
    }
	$.dyngrid = {
		def_options: {
            item_width: null,
            item_height: null,
            item_handle: null,
            mode: null,
            detached_z: 100000,
            auto_scroll: true,
            
            onSelection: null,
            onLoadItems: null,
            onLayoutChange: null,
            onDragStart: null,
            onDragOver: null,
            onDrop: null, 
            
            shift_mode: "list",
            treshold: 5,
            patch_load: 10,
            scroll_speed: 10,
            scroll_treshold: 20,
		},
        
        keys: {
            LEFT: 37,
            UP: 38,
            RIGHT: 39,
            DOWN: 40,
            PG_UP: 33,
            PG_DOWN: 34,
            END: 35,
            HOME: 36,
            ENTER: 13,
            DELETE: 46,
        },        

        scroll_timeout: null,
        global_events: false,
        dragging: null,
        selecting: null,
        scrolling: null,
        dragged: false,
        deselect: false,
        treshold_orig: null,
        treshold_value: null,
        treshold_reached: false,
        prev_selection: {},
        scroll_callback: function() {
            var target = $.dyngrid.scrolling.target;
            var ops = target.data("dyngrid");
            
            if (!ops) return;
            
            target[0].scrollLeft+= ops.scroll_speed * $.dyngrid.scrolling.dir_x;
            target[0].scrollTop+= ops.scroll_speed * $.dyngrid.scrolling.dir_y;
            
            if ($.dyngrid.selecting)
                $.dyngrid.update_selector($.dyngrid.selecting.target, $.dyngrid.scrolling.x, $.dyngrid.scrolling.y, ops);
                
            $.dyngrid.scrolling.timeout = setTimeout($.dyngrid.scroll_callback, 20);
        },
        scroll_stop: function() {
            if ($.dyngrid.scrolling)
                clearTimeout($.dyngrid.scrolling.timeout);    
                            
            $.dyngrid.scrolling = null;    
        },
        scroll_check: function(target, px, py, ops) {
            var width = target.children(".dyngrid_wcalc");
            if (!width.length)
                width = $("<div class='dyngrid_wcalc' style='position: absolute; left: 0; right: 0'></div>").appendTo(target);

            var height = target.children(".dyngrid_hcalc");
            if (!height.length)
                height = $("<div class='dyngrid_hcalc' style='position: absolute; top: 0; bottom: 0'></div>").appendTo(target);
            
            var target_ofs = target.offset();
            var target_w = width.width();
            var target_h = height.height();
            
            var x1 = target_ofs.left;
            var x2 = target_ofs.left + target_w;
            
            var y1 = target_ofs.top;
            var y2 = target_ofs.top + target_h;
            
            var dir_x = 0;
            var dir_y = 0;
            
            if (px > x1 - ops.scroll_treshold && px < x1) { px = target_ofs.left; dir_x = -1 }
            if (px > x2 && px < x2 + ops.scroll_treshold) { px = target_ofs.left + target_w; dir_x = 1 }

            if (py > y1 - ops.scroll_treshold && py < y1) { py = target_ofs.top; dir_y = -1 }
            if (py > y2 && py < y2 + ops.scroll_treshold) { py = target_ofs.top + target_h; dir_y = 1 }
            
            return [px,py, dir_x,dir_y];
        },       
        get_item: function(handle, ops) {
            if (!handle.hasClass("dyngrid_item"))   
                return handle.parents("li.dyngrid_item"); else
                return handle;
        },
        project: function(x, y, grid, ops) {
            var ul = grid.children("ul");
            var li = ul.children("li.dyngrid_item");
            if (!li.length)
                return 0;
            
            var li_w = li.outerWidth(true);
            var li_h = li.outerHeight(true);
            var w = ul.width();
            var col_cnt = parseInt(w / li_w);
            var row_cnt = parseInt(li.length / col_cnt);
            if (li.length % col_cnt)
                row_cnt++;
                
            if (row_cnt < 1)
                row_cnt = 1;
                
            var gx = parseInt(x / li_w);
            var gy = parseInt(y / li_h);

            if (gx < 0 || gx >= col_cnt) return -1;
            if (gy >= row_cnt) 
                gy = row_cnt-1;
            
            var res = gy * col_cnt + gx;
            if (res >= li.length)
                res = li.length;
                
            return res;
        },
        intersect: function(r1_x1,r1_y1,r1_x2,r1_y2, r2_x1,r2_y1,r2_x2,r2_y2) {
            return !(r2_x1 > r1_x2 || 
                     r2_x2 < r1_x1 || 
                     r2_y1 > r1_y2 ||
                     r2_y2 < r1_y1);
        },
        items_in_rect: function(grid, x1,y1,x2,y2, filter, assoc) {
            var ops = grid.data("dyngrid");
            var list = grid.children("ul");
            var items = list.children("li.dyngrid_item");
            
            var result = (assoc) ? {} : $();
            if (!items.length) return result;
            
            var f = $(items[0]);
            var w = f.outerWidth(true);
            var h = f.outerHeight(true);
            var px = parseInt(list.css("padding-left"));
            var py = parseInt(list.css("padding-top"));
            
            x1 = x1 - px; if (x1 < 0) x1 = 0;
            x2 = x2 - px; if (x2 < 0) x2 = 0;
            
            y1 = y1 - py; if (y1 < 0) y1 = 0;
            y2 = y2 - py; if (y2 < 0) y2 = 0;           

            var mx1 = parseInt(f.css("margin-left"));
            var mx2 = parseInt(f.css("margin-right"));
            var my1 = parseInt(f.css("margin-top"));
            var my2 = parseInt(f.css("margin-bottom"));
            
            var per_row = parseInt(list.width() / w);
            var rx1 = parseInt(x1 / w);
            var ry1 = parseInt(y1 / h);            
            var rx2 = parseInt(x2 / w);
            var ry2 = parseInt(y2 / h);       
            
            if (rx2 >= per_row) rx2 = per_row-1;
            
            var item, add;
            var skip = per_row - (rx2 - rx1) - 1;
            var ind = ry1 * per_row + rx1;
            var ix1,iy1,pos;
            
            for (var i = 0; i <= ry2-ry1; i++) {
                for (var j = 0; j <= rx2-rx1; j++) {
                    if (ind >= items.length) break;
                    
                    ix1 = (rx1 + j) * w;
                    iy1 = (ry1 + i) * h;
                    
                    item = $(items[ind]); 
                    add = $.dyngrid.intersect(ix1 + mx1, iy1 + my1, ix1 + w - mx2, iy1 + h - my2, x1,y1,x2,y2);
                    if (filter && !item.is(filter))
                        add = false;
                    
                    if (add) {
                        if (assoc)
                            result[ind] = item; else
                            result = result.add(item);
                    }
                    
                    ind++;    
                    if (ind >= items.length) break;
                }
                
                ind+= skip;    
                if (ind >= items.length) break;
            }
            
            return result;
        }, 
        visible_items: function(grid, filter) {
            var x1 = grid.scrollLeft();
            var y1 = grid.scrollTop();
            var x2 = x1 + grid.width();  
            var y2 = y1 + grid.height();
            
            return $.dyngrid.items_in_rect(grid, x1,y1, x2,y2, filter);
        },
        clear_selection: function(grid) {
            var ops = grid.data("dyngrid");
            grid.children("ul").children("li.dyngrid_selected").removeClass("dyngrid_selected dyngrid_last dyngrid_orig");  
            
            if (typeof ops.onSelection == "function")
                ops.onSelection.apply(grid, [$()]);
        },
        select_single: function(grid, item, clear, toggle) {
            if (!item || !item.length) return;

            var ops = grid.data("dyngrid");
            var list = grid.children("ul");
            var items = list.children("li");

            if (clear)
                items.filter("li.dyngrid_selected").removeClass("dyngrid_selected dyngrid_last");
                
            if (toggle && item.hasClass("dyngrid_selected"))
                item.removeClass("dyngrid_selected"); else
                item.addClass("dyngrid_selected");
            
            items.filter("li.dyngrid_last").removeClass("dyngrid_last");
            item.addClass("dyngrid_last");
            
            if (typeof ops.onSelection == "function")
                ops.onSelection.apply(grid, [list.children("li.dyngrid_selected")]);                
        },
        select_multiple: function(grid, from, to) {
            var ops = grid.data("dyngrid");
            var list = grid.children("ul");
            var items = list.children("li");
            
            items.filter("li.dyngrid_selected").removeClass("dyngrid_selected dyngrid_last");
            
            var ind1 = from.index();    
            var ind2 = to.index();
            
            if (ind2 < ind1) {
                var t = ind1;
                ind1 = ind2;
                ind2 = t;    
            }
            
            if (ops.shift_mode == "grid") {
                var sx = parseInt(list.width() / items.outerWidth(true));  
                var sy = parseInt(items.length / sx);    
                if (items.length % sx)
                    sy++;
                    
                var x1 = ind1 % sx;
                var y1 = parseInt(ind1 / sx);

                var x2 = ind2 % sx;
                var y2 = parseInt(ind2 / sx);
                
                if (x1 > x2) { var t = x1; x1 = x2; x2 = t }
                if (y1 > y2) { var t = y1; y1 = y2; y2 = t }
                
                ind1 = y1 * sx + x1;
                ind2 = y2 * sx + x2;
                
                var x,y, l = items.length;
                for (var i = Math.min(ind1,l); i <= Math.min(ind2,l); i++) { 
                    x = i % sx;
                    y = parseInt(i / sx);
                    
                    if (x >= x1 && x <= x2 && y >= y1 && y <= y2)
                        $(items[i]).addClass("dyngrid_selected"); 
                } 
            } else {                
                for (var i = ind1; i <= ind2; i++) 
                    $(items[i]).addClass("dyngrid_selected"); 
            }
            
            items.filter("li.dyngrid_last").removeClass("dyngrid_last");
            to.addClass("dyngrid_last");
            
            if (typeof ops.onSelection == "function")
                ops.onSelection.apply(grid, [list.children("li.dyngrid_selected")]);
        },
        init_item: function(item, ops) {
            if (item.hasClass("dyngrid_selector")) return;
            
            item.addClass("dyngrid_item");
            if (ops.item_width)
                item.css("width", ops.item_width + "px");
            if (ops.item_height)
                item.css("height",ops.item_height + "px");
            
            var handle;
            if (ops.item_handle)
                handle = item.find(ops.item_handle); else
                handle = item;
            
            handle.bind("mousedown", function(e) {
                if (e.altKey || e.which != 1) return;
                                                                 
                var grid = $(this).parents(".dyngrid:first");
                var ops = grid.data("dyngrid");
                var item = $.dyngrid.get_item($(this), ops);
                var list = item.parent();
                var orig = list.children("li.dyngrid_orig");
                
                $.dyngrid.treshold_orig = [e.pageX, e.pageY];
                $.dyngrid.treshold_reached = false;
                $.dyngrid.treshold_value = ops.treshold;                
                
                $.dyngrid.deselect = false;
                if (e.ctrlKey) {
                    $.dyngrid.select_single(grid, item, false, true);
                } else
                if (e.shiftKey) {
                    $.dyngrid.select_multiple(grid, orig, item);
                } else 
                if (item.hasClass("dyngrid_selected")) {
                    $.dyngrid.select_single(grid, item);
                    $.dyngrid.deselect = true;
                } else {
                    orig.removeClass("dyngrid_orig");
                    item.addClass("dyngrid_orig");
                    
                    $.dyngrid.select_single(grid, item, true);
                }
                    
                if (!e.shiftKey) {
                    var ofs = item.offset();
                    var x = e.pageX - ofs.left + parseInt(item.css("margin-left"));
                    var y = e.pageY - ofs.top + parseInt(item.css("margin-top"));
                    
                    if (ops.mode) {
                        var items = list.children("li.dyngrid_selected");
                        var res = true;
                        if (typeof ops.onDragStart == "function") 
                            res = ops.onDragStart.apply(grid, [e, items]);
                        
                        if (res instanceof jQuery) 
                            items = res;
                            
                        if (items.length) {
                            $.dyngrid.dragging = {
                                "item" : items,
                                "grid" : grid,     
                                "hook" : [x,y],
                                "orig" : item.index,
                                "curr" : null,
                                "css" : {
                                    "width" : items.outerWidth() + "px", 
                                    "height" : items.outerHeight() + "px",
                                    "margin-top" : items.css("margin-top"),
                                    "margin-bottom" : items.css("margin-bottom"),
                                    "margin-left" : items.css("margin-left"),
                                    "margin-right" : items.css("margin-right")
                                }
                            }
                        }
                    }
                }
                
                e.stopPropagation();
            }).bind("mouseup", function(e) {
                if ($.dyngrid.deselect && !e.ctrlKey && !e.shiftKey) {
                    var grid = $(this).parents(".dyngrid:first");
                    var ops = grid.data("dyngrid");
                    var item = $.dyngrid.get_item($(this), ops);
                    var list = item.parent();
                    
                    list.children("li.dyngrid_selected:not(.dyngrid_last)").removeClass("dyngrid_selected");
                    
                    if (typeof ops.onSelection == "function")
                        ops.onSelection.apply(grid, [list.children("li.dyngrid_selected")]);
                }  
            });
            
            if (ops.mode == "dnd")
            handle.bind("mousemove", function(e) {
                $.dyngrid.dragover(e, $(this));
            }); 
        },
        load_items: function(grid, ops) {
            if (typeof ops.onLoadItems != "function") return true;
            
            var items = $.dyngrid.visible_items(grid, "li.dyngrid_pending");
            
            var cnt = items.length;
            if (!cnt) return true;
            
            if (ops.patch_load && cnt > ops.patch_load) 
                items.length = ops.patch_load;
                
            if (ops.onLoadItems.apply(grid, [items]) !== false) {
                items.removeClass("dyngrid_pending");
                
                return cnt == items.length;
            } else
                return false;
        },
        check_layout: function(grid, ops) {
            var layout = {
                width: grid.width(),
                height: grid.height(),
                scroll_top: grid.scrollTop(),
                scroll_left: grid.scrollLeft()    
            }
            
            if (!layout.width || !layout.height)
                return;
                
            if (layout.width != ops._layout.width ||
                layout.height != ops._layout.height ||
                layout.scroll_top != ops._layout.scroll_top ||
                layout.scroll_left != ops._layout.scroll_left) {
                    
                var r = true;
                if (typeof ops.onLayoutChange == "function")
                    r = ops.onLayoutChange.apply(grid);
                
                if ($.dyngrid.load_items(grid, ops) === false)
                    r = false;
                    
                if (r !== false)
                    ops._layout = layout;    
            }
                     
            ops._update_timeout = setTimeout(function() {
                $.dyngrid.check_layout(grid, ops);
            }, 500);
        }, 

        dragover: function(e, item) {
            if (!$.dyngrid.dragging) return;
            
            var grid = $.dyngrid.dragging.grid;
            var ops = grid.data("dyngrid");
            
            $.dyngrid.scroll_stop();
            $.dyngrid.dragging.no_scroll = true;

            if (item.is($.dyngrid.dragging.drop_target)) 
                return;
                
            $.dyngrid.dragging.drop_valid = false;
            if ($.dyngrid.dragging.drop_target) 
                $.dyngrid.dragging.drop_target.removeClass("dyngrid_dropzone");

            $.dyngrid.dragging.drop_target = item;
            if (item.hasClass("dyngrid_dragged") || item.hasClass("dyngrid_selected")) {
                $("#dyngrid_cursor > label").hide();
                
                return;
            }
                
            var status = true;
            if (typeof ops.onDragOver == "function")
                status = ops.onDragOver.apply(item, [e, $.dyngrid.dragging.item]);
            
            if (status !== false) {
                if (!$.isString(status))
                    status = "Drop";
                
                $("#dyngrid_cursor > label").show().html(status);
                item.addClass("dyngrid_dropzone");
            } else 
                $("#dyngrid_cursor > label").hide();
            
            $.dyngrid.dragging.drop_valid = status !== false;
        },
        detach: function(e, grid, items, ops) {
            if ($.dyngrid.dragging.detached) return;
            
            var cursor = $("#dyngrid_cursor").show();
            var ul = cursor.children("ul");
            
            if (ops.mode == "sort") {
                var list = grid.children("ul");
                list.css("min-height", list.height() + "px");
                
                $.dyngrid.dragging.detached = items.addClass("dyngrid_dragged dyngrid_detached").detach().appendTo(ul); 
                cursor.removeClass("dyngrid_dnd").addClass("dyngrid_sort");
            } else
            if (ops.mode == "dnd") { 
                $.dyngrid.dragging.detached = items.addClass("dyngrid_dragged").clone().addClass("dyngrid_detached").appendTo(ul);
                cursor.removeClass("dyngrid_sort").addClass("dyngrid_dnd");
            }
            $.dyngrid.dragging.detached.each(function(i) {
                $(this).css({left: i * 5, top: i * 5});
                
                if (i > 4)
                    $(this).addClass("dyngrid_hidden");
            });
            
            return $.dyngrid.dragging.detached;
        },
        reorder: function(x,y, grid, ops) {
            var list = grid.children("ul");
            var items = list.children("li.dyngrid_item");
            
            var pos = $.dyngrid.project(x,y, grid, ops);
            if (pos !== $.dyngrid.dragging.curr && pos >= 0) {
                var trg = $(items.get(pos));
                
                var holder = list.children("li.dyngrid_holder");
                if (!holder.length) {
                    holder = $("<li class='dyngrid_holder'></div>"); 
                    holder.css($.dyngrid.dragging.css);
                } else          
                    holder.detach();
                    
                if (trg.length) 
                    holder.insertBefore(trg); else
                    holder.appendTo(list);  
            }
            
            $.dyngrid.dragging.curr = pos;
        },
        dragged: function(e) {
            if ($.dyngrid.treshold_reached) return true;  
            
            if (Math.abs(e.pageX - $.dyngrid.treshold_orig[0]) > $.dyngrid.treshold_value || 
                Math.abs(e.pageY - $.dyngrid.treshold_orig[1]) > $.dyngrid.treshold_value)
                $.dyngrid.treshold_reached = true;
            
            return $.dyngrid.treshold_reached;
        },
        dragging_mousemove: function(e) {
            $("body").addClass("prevent_selection");
            
            if (!$.dyngrid.dragged(e)) return;
            
            $.dyngrid.deselect = false;
            
            var item = $.dyngrid.dragging.item;
            var grid = $.dyngrid.dragging.grid;
            var ops = grid.data("dyngrid");    
            
            var cursor = $("#dyngrid_cursor");
            if (ops.mode == "sort") 
                cursor.css({left: e.clientX - $.dyngrid.dragging.hook[0], top: e.clientY - $.dyngrid.dragging.hook[1]}); else
            if (ops.mode == "dnd") 
                cursor.css({left: e.clientX + 5, top: e.clientY + 5});
            
            var list = grid.children("ul");
            var items = $.dyngrid.detach(e, grid, item, ops);
            
            var ofs = list.offset();
            var x = e.pageX - ofs.left;
            var y = e.pageY - ofs.top;
            
            var pos = $.dyngrid.scroll_check(grid, e.pageX, e.pageY, ops);
            if ((pos[2] || pos[3]) && ops.auto_scroll && !$.dyngrid.dragging.no_scroll) {
                if (!$.dyngrid.scrolling) 
                    $.dyngrid.scrolling = {
                        target: $.dyngrid.dragging.grid,
                        timeout: setTimeout($.dyngrid.scroll_callback)
                    };
                
                $.dyngrid.scrolling.dir_x = pos[2];
                $.dyngrid.scrolling.dir_y = pos[3];
                $.dyngrid.scrolling.x = x;
                $.dyngrid.scrolling.y = y;
                
                $.dyngrid.update_cursor(null, ops);
                $.dyngrid.dragging.curr = -1;   
                
                $.dyngrid.dragging.drop_target = null;
                $("#dyngrid_cursor > label").hide();
                
                return false;
            } else 
                $.dyngrid.scroll_stop();   
            
            $.dyngrid.dragging.no_scroll = false;
                
            if (ops.mode == "sort")
                $.dyngrid.reorder(x,y, grid, ops);
        }, 
        dragging_mouseup: function(e) {
            var item = $.dyngrid.dragging.item;
            
            if ($.dyngrid.dragging.detached) {
                var grid = $.dyngrid.dragging.grid;
                var ops = grid.data("dyngrid");
                var list = grid.children("ul");
                
                if (ops.mode == "sort") {
                    var holder = list.children("li.dyngrid_holder");
                
                    item.removeClass("dyngrid_dragged dyngrid_detached dyngrid_hidden").detach().css({top: 0, left: 0});
                    holder.replaceWith(item);
                    item.removeClass("dyngrid_hidden");
                    
                    list.css("min-height", 0);
                    if (typeof ops.onDrop == "function") 
                        ops.onDrop.apply(grid, [e, item]);
                } else
                if (ops.mode == "dnd") {
                    var trg = $.dyngrid.dragging.drop_target;
                    if (trg && $.dyngrid.dragging.drop_valid && typeof ops.onDrop == "function") 
                        ops.onDrop.apply(trg, [e, item]);
                    
                    $.dyngrid.dragging.detached.remove();                                   
                } 
                
                list.children("li").removeClass("dyngrid_dropzone dyngrid_dragged dyngrid_detached");
            }       
            
            $("#dyngrid_cursor").hide();
            $("body").removeClass("prevent_selection");    
            $.dyngrid.dragging = null;   
            e.stopPropagation();
            return false;     
        },
        selecting_mousemove: function(e) {
            if (!$.dyngrid.dragged(e)) return;
            
            var target = $.dyngrid.selecting.target;
            var ops = target.data("dyngrid");
            
            $.dyngrid.deselect = false;
            
            $("body").addClass("prevent_selection").attr("onselectstart", "return false");

            if (!$.dyngrid.selecting.moved) {
                items = target.children("ul").children("li.dyngrid_item");
                if (!e.ctrlKey && !e.shiftKey) 
                    items.filter("li.dyngrid_selected").removeClass("dyngrid_selected"); else
                    items.filter("li.dyngrid_selected").addClass("dyngrid_locked");
            }
            
            var pos = $.dyngrid.scroll_check(target, e.pageX, e.pageY, ops);
            if (ops.auto_scroll && (pos[2] || pos[3])) {
                if (!$.dyngrid.scrolling) 
                    $.dyngrid.scrolling = {
                        target: $.dyngrid.selecting.target,
                        timeout: setTimeout($.dyngrid.scroll_callback)
                    };
                
                $.dyngrid.scrolling.dir_x = pos[2];
                $.dyngrid.scrolling.dir_y = pos[3];
                $.dyngrid.scrolling.x = pos[0];
                $.dyngrid.scrolling.y = pos[1];
                
                return false;
            } else 
                $.dyngrid.scroll_stop();
            
            $.dyngrid.selecting.moved = true;
            $.dyngrid.update_selector(target, pos[0], pos[1], ops);
        },  
        selecting_mouseup: function(e) {
            var grid = $.dyngrid.selecting.target;
            var ops = grid.data("dyngrid");
            var list = grid.children("ul");
            var items = list.children("li.dyngrid_item");
            
            $.dyngrid.scroll_stop();
            
            $("body").removeClass("prevent_selection").removeAttr("onselectstart");
            
            grid.children(".dyngrid_wcalc, .dyngrid_hcalc").remove();
            list.children("li.dyngrid_selector").hide();
            items.filter("li.dyngrid_locked").removeClass("dyngrid_locked");
            
            if ($.dyngrid.selecting.last) {
                items.filter("li.dyngrid_last").removeClass("dyngrid_last");
                $($.dyngrid.selecting.last).addClass("dyngrid_last");
            }
            $.dyngrid.selecting = null;
            
            if (typeof ops.onSelection == "function")
                ops.onSelection.apply(grid, [list.children("li.dyngrid_selected")]);

            e.stopPropagation();
            return false;    
        },
        update_cursor: function(trg, ops) {
            var status = "Drop";
            if (trg) {
                if (trg.hasClass("dyngrid_dragged")) 
                    status = false; else
                if (typeof ops.onDragOver == "function")
                    status = ops.onDragOver.apply(grid, [e, items, trg]);
            } else
                status = false;
            
            if (status) {
                $.dyngrid.dragging.drop_target = trg;    
                $.dyngrid.dragging.drop_target.addClass("dyngrid_dropzone");

                $("#dyngrid_label").html(status); 
            } else {
                $("#dyngrid_label").hide();
                if ($.dyngrid.dragging.drop_target)
                    $.dyngrid.dragging.drop_target.removeClass("dyngrid_dropzone");
                
                $.dyngrid.dragging.drop_target = null;
            }
        },
        update_selector: function(target, mx, my, ops) {
            var list = target.children("ul");
            var sel = list.children("li.dyngrid_selector");
            var ofs = list.offset();                          
            
            var x = mx - ofs.left;
            var y = my - ofs.top;                                    
            
            var ox = $.dyngrid.selecting.orig[0];
            var oy = $.dyngrid.selecting.orig[1];
            
            $.dyngrid.selecting.rect = [ox,oy, x,y];
            
            if (x < ox) { var t = x; x = ox; ox = t }
            if (y < oy) { var t = y; y = oy; oy = t }   
            
            sel.show().css({
                left: parseInt(ox),
                top: parseInt(oy),
                width: parseInt(x - ox - 2) + "px",
                height: parseInt(y - oy - 2) + "px"
            });
    
            var items = $.dyngrid.items_in_rect(target, ox,oy, x,y, null, true);
            var cnt = 0;
            var i;
            for (i in items) { 
                items[i].addClass("dyngrid_selected");
                delete $.dyngrid.prev_selection[i];
                
                cnt++;
            }
            $.dyngrid.selecting.last = cnt ? items[i] : null;
            
            var itm;
            for (var i in $.dyngrid.prev_selection) {
                itm = $.dyngrid.prev_selection[i];
                if (!itm.hasClass("dyngrid_locked"))
                    $.dyngrid.prev_selection[i].removeClass("dyngrid_selected");
            } 
                
            $.dyngrid.prev_selection = items;
        },  
        find_item: function(item, dx, dy, ul, ops) {
            var items = ul.children("li.dyngrid_item");
            
            var sx = parseInt(ul.width() / items.outerWidth(true));  
            var sy = parseInt(items.length / sx);
            if (items.length % sx)
                sy++;
            
            var ind = item.index();
            var x = ind % sx + dx;
            var y = parseInt(ind / sx) + dy;
            
            if (x < 0 || x > sx-1 || y < 0 || y > sy-1) return $();
            ind = y * sx + x;
            if (ind > items.length-1)
                ind = items.length-1;
            
            return $(items[ind]);
        },
        init_keys: function(grid, ops) {
            grid.attr("tabindex", 0);
            
            grid.bind("keydown", function(e) {
                var ops = $(this).data("dyngrid");

                var list = $(this).children("ul");   
                var sel = list.children("li.dyngrid_selected");
                var curr = sel.filter("li.dyngrid_last");
                if (!curr.length)
                    curr = sel.filter("li.last");
                
                var next = null; 
                var nav = false;
                
                if (e.keyCode == $.dyngrid.keys.RIGHT) {
                    nav = true;
                    if (curr.length)     
                        next = curr.next("li.dyngrid_item"); else
                        next = list.children("li.dyngrid_item:first");
                }
                
                if (e.keyCode == $.dyngrid.keys.LEFT) {
                    nav = true;
                    if (curr.length)     
                        next = curr.prev("li.dyngrid_item"); else
                        next = list.children("li.dyngrid_item:first");
                }
                
                if (e.keyCode == $.dyngrid.keys.DOWN) {
                    nav = true;
                    if (curr.length) 
                        next = $.dyngrid.find_item(curr, 0, 1, list, ops); else
                        next = list.children("li.dyngrid_item:first");                     
                }
                
                if (e.keyCode == $.dyngrid.keys.UP) {
                    nav = true;
                    if (curr.length) 
                        next = $.dyngrid.find_item(curr, 0, -1, list, ops); else
                        next = list.children("li.dyngrid_item:last");                     
                }                
                
                if (nav) {  
                    var orig = list.children("li.dyngrid_orig");
                    if (next.length) {
                        if (curr.length) {
                            if (e.shiftKey)
                                $.dyngrid.select_multiple(grid, orig, next); else
                                $.dyngrid.select_single(grid, next, !e.ctrlKey && !e.shiftKey);
                        } else 
                            $.dyngrid.select_single(grid, next);
                        
                        if (!e.shiftKey && !e.ctrlKey) {
                            orig.removeClass("dyngrid_orig");
                            next.addClass("dyngrid_orig");
                        }
                    }
                    e.stopPropagation();
                    return false;
                }
            }); 
        },
        destroy: function(options) {
            if (!$(this).hasClass("dyngrid")) return;
            
            var ops = $(this).data("dyngrid");
            if (!ops) return;
            clearTimeout(ops._update_timeout);
            
            $(this).data("dyngrid", null);
            $(this).removeClass("dyngrid");
        },   
        create: function(options) {
            if ($(this).hasClass("dyngrid")) {
                $.dyngrid.destroy.apply(this);
            }
                
            var ops = $.extend({}, $.dyngrid.def_options, options);
            
            $(this).data("dyngrid", ops);
            
            var list = $(this).children("ul");
            var items = list.children("li");
                         
            var css_pos = $(this).css("position");
            if (css_pos != "relative" && css_pos != "absolute")
                $(this).css("position", "relative");
                
            $(this).addClass("dyngrid");
            $(this).bind("scroll", function() {
                var ops = $(this).data("dyngrid");
                var grid = $(this);
                
                clearTimeout(ops._update_timeout);
                ops._update_timeout = setTimeout(function() {
                    $.dyngrid.check_layout(grid, ops);
                }, 500);   
            });
            list.bind("mousedown", function(e) {
                if (e.which != 1) return;
                
                var grid = $(this).parent();
                var ops = grid.data("dyngrid");
                
                $.dyngrid.treshold_orig = [e.pageX, e.pageY];
                $.dyngrid.treshold_reached = false;
                $.dyngrid.treshold_value = ops.treshold;                
                
                if (!e.ctrlKey && !e.shiftKey)
                    $.dyngrid.clear_selection(grid, null, null, true);
                    
                var ofs = $(this).offset();
                var x = e.pageX - ofs.left;
                var y = e.pageY - ofs.top;
                
                $(this).children("li.dyngrid_selector").css({width: 0, height: 0});                
                $.dyngrid.selecting = {
                    target: grid,
                    orig: [x,y],
                    moved: false                        
                }
            }); 
            
            var cont = $("#dyngrid_cont");
            if (!cont.length) {
                var html = "<div id='dyngrid_cont'>";
                html+= "<div id='dyngrid_cursor' style='z-index: " + ops.detached_z + "'>";
                html+= "<label>Dragging element</label>";
                html+= "<ul></ul>";
                html+= "</div>";
                
                cont = $(html).appendTo($("body")).bind("mousemove", function(e) {
                    e.stopPropagation();
                    e.preventDefault();
                    
                    return false;
                });
                
                $("#dyngrid_cursor").mousemove($.dyngrid.dragging_mousemove);
            }
            
            var grid = $(this);
            var list = grid.children("ul");
            var items = list.children();
            list.contents().filter(function() {
                return this.nodeType == 3; 
            }).remove();    
            
            items.each(function(i) {
                $.dyngrid.init_item($(this), ops);
            });
            list.append("<li class='dyngrid_selector'></li>");

            $.dyngrid.init_keys(grid, ops);
            ops._layout = {
                width: 0,
                height: 0,
                scroll_top: 0,
                scroll_left: 0,
            }
            $.dyngrid.check_layout(grid, ops);                            
            
            if (!$.dyngrid.global_events) {
                $(document).mousemove(function(e) {
                    if ($.dyngrid.dragging) 
                        return $.dyngrid.dragging_mousemove(e);
                    if ($.dyngrid.selecting)
                        return $.dyngrid.selecting_mousemove(e);
                }).mouseup(function(e) {
                    if ($.dyngrid.dragging)
                        return $.dyngrid.dragging_mouseup(e); 
                    if ($.dyngrid.selecting)
                        return $.dyngrid.selecting_mouseup(e);                                                                
                });
                    
                $.dyngrid.global_events = true;
            } 
            
            return $(this);
        }
    }

    $.fn.dynamic_grid = function() {
        var args = arguments;
        
        if (args.length == 0) return $(this);
        
        if ($.isString(args[0])) {
            if (args[0] == "create")    
                $.dyngrid.create.apply(this, [args[1]]); else
            if (args[0] == "load_items")
                $.dyngrid.load_items($(this), $(this).data("dyngrid"));
            if (args[0] == "destroy")
                $.dyngrid.destroy.apply(this); else
            if (args[0] == "visible_items") 
                return $.dyngrid.visible_items(this, args[1]); else
            if (args[0] == "init_item") {
                var ops = $(this).data("dyngrid");
                $(args[1]).each(function() {
                    $.dyngrid.init_item($(this), ops);
                });
                return $(this);
            }
        } else 
            $.dyngrid.create.apply(this, [args[0]]);
            
        return $(this);        
    }
})( jQuery );        


