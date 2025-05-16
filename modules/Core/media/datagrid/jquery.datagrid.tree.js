/*
	TODO:
	- drop after/before -> kerti tipi so allowed
	- delovanje brez handlerja (manipulacija rows)
	- zapomne si, keri nodi so odprti, mogoce ajax calli za odpiranje? STATE object, isto narjen kot za param
	- jquery.mutate 
	- kaj narest z type = undefined, drop = undefined?
	- default collapsed/expanded?
	- vsak row ma "has_children", ki pove a lahk sploh drag[pos=inside] <---- DROP != ""
	- dnd -> open node on drag
	- onDrag?  
	- drag_grip, ce ga nima pomen da ga ne more draggat (zdj je narjen da je kr dg_node) 
	
	- NUJNO -> dg_node_grip se bojo podvajal eventi!
    
    
    
    
    Expanded + !Loaded => kaj se zgodi?!
*/

(function( $ ){   
    "use strict";
    
    $.flag_state = function(flags, flag, state) {
        if (typeof flag == "undefined" || !flag) return false;
        
        if (flags == "" || !$.isString(flags))
            flags = ","; else
            flags = "," + flags + ",";
        
        if (typeof state == "undefined") 
            return flags.indexOf("," + flag + ",") != -1; else
        if (state) {
            if (flags.indexOf("," + flag + ",") == -1)
                flags+= flag + ",";
        } else 
            flags = flags.replace("," + flag + ",", ",");
            
        if (flags == ",")
            return ""; else
            return flags.substring(1, flags.length-1);
    },
    $.datagrid.rows = {
            each: function(rows, callback, ops, data) {
                    var index = 0;
                    var each_row = function(rows, parent, data) {
                            var res, row;
                            if (!$.isArray(rows)) return;

                            for (var i in rows) {
                                    row = rows[i];

                                    res = callback(row, parent, data, { global: index, local: i });                                            

                                    index++;
                                    each_row(row.rows, row, res);                                                
                            }
                    }

                    each_row(rows, null, data);
            },
            get: function(path, ops) {
                    if ($.isString(path))
                            path = path.toString().split(".");

        if (!path.length)
            return null;

                    var index, result = ops;
                    var rows = ops.rows;

                    for (var i in path) {
                            index = parseInt(path[i]);
                            if (typeof rows == "undefined") return null;

                            if ($.inArray(index, rows)) {
                                    result = rows[index];
                                    rows = rows[index].rows;
                            } else
                                    return null;
                    }    

                    return result;            
            },
            add: function(path, row, ops) {
                    if ($.isString(path))
                            path = path.toString().split(".").push(-1);

                    $.datagrid.rows.insert(path, row, ops);     
            },
            remove: function(path, ops) {  
                    if ($.isString(path))
                            path = path.toString().split(".");

                    var index, result;
                    var rows = ops.rows;

                    for (var i = 0; i < path.length-1; i++) {
                            index = parseInt(path[i]);
                            if (typeof rows == "undefined") return null;

                            if ($.inArray(index, rows)) 
                                    rows = rows[index].rows; else
                                    return null;
                    }
                    index = parseInt(path[i]);

                    result = rows[index];
                    rows.splice(index,1);

                    return result;
            },
            insert: function(path, row, ops) {
                    if ($.isString(path))
                            path = path.toString().split(".");

                    if (!path.length) return;
                    if (!row) return;

                    var index = parseInt(path.pop()), target;

                    if (path.length)
                            target = $.datagrid.rows.get(path, ops); else
                            target = ops;

                    if (!target) return;

                    if (typeof target.rows == "undefined")
                            target.rows = [];

                    if (index == -1) index = target.rows.length;
                    if (index >= target.rows.length) 
                            target.rows.push(row); else
                            target.rows.splice(index, 0, row);
            },
            move: function(src_path, dst_path, ops) {
                    if (!$.isArray(src_path)) src_path = src_path.toString().split(".");
                    if (!$.isArray(dst_path)) dst_path = dst_path.toString().split(".");

                    var src_ind = src_path.pop();
                    var dst_ind = dst_path.pop();

                    var src = $.datagrid.rows.get(src_path, ops);
                    var dst = $.datagrid.rows.get(dst_path, ops);

                    if (!$.inArray(src_ind, src.rows)) return;
                    if (typeof dst.rows == "undefined")
                            dst.rows = [];

                    if (src.id == dst.id && src_ind < dst_ind)
                            dst_ind--;

                    var row = src.rows.splice(src_ind, 1)[0];
                    dst.rows.splice(dst_ind, 0, row);

                    return {
                            "src" : src,
                            "dst" : dst,
                            "row" : row,
                    };            
            }
    },
    $.datagrid.modules.edit.position = function(cell, edit, ops) {
            if ($.inArray("treeview", ops.modules) == -1) return;
            if (!cell.is(".dg_node")) return;

            var row = cell.parents(".dg_row:first");
            var level = parseInt(row.attr("data-level"));

            var p = ops.treeview.indent * level;
            if (ops.treeview.show_icons)
                    p+= ops.treeview.indent;

            edit.css("margin-left", p + "px");
    },
    $.datagrid.modules.treeview = {
        def_options: {
            column_id: null,
            column_index: 0,
            indent: 20,
            dnd: false,
            types: false,
            drop_treshold: 4,
            drag_treshold: 5,
            root_drop: "",
            drag_grip: null,
            show_lines: true,
            show_icons: true,
            expand_all: false,
            node_select: false,
            save_expanded: false,

            onDrop: null,
            onExpand: null,
        },
        state: {
            expanded: ""
        },
        state_ops: {
            expanded: { save: true }
        },
        global_events: false,
        dragging: null,
        drag_treshold: 5,
        drag_timeout: null,
        drag_coord: null,
        drop_source: null,
        drop_target: null,
        drop_index: null,
        drop_valid: false,

        config: function(grid, ops) {
            if (ops.treeview.column_id != null) {
                for (var i = 0; i < ops.model.length; i++)
                    if (ops.model[i].id == ops.treeview.column_id) {
                        ops.treeview.column_index = i;
                        break;
                    }    
            }
        },
        build: function(grid, ops) {
            if (ops.treeview.dnd) {
                if (!$(".dg_drop_locator", grid).length)
                    grid.append("<div class='dg_drop_locator'><div></div></div>");

                if (!$(".dg_drop_cursor", grid).length)
                    grid.append("<div class='dg_drop_cursor'></div>");
            }

            if (ops.treeview.types !== false && ops.treeview.root_drop)
                grid.attr('data-drop', ops.treeview.root_drop);

            if (ops.treeview.dnd && !$.datagrid.modules.treeview.global_events) {
                    $(window).bind("mouseup", function() {
                            var tw = $.datagrid.modules.treeview;
                            if (!tw.dragging) return false;

                            var node = tw.dragging;
                            if (!node.length) return;

                            var tw = $.datagrid.modules.treeview;
                            if (tw.drop_valid) 
                                    tw.drop.apply(this, [tw.drop_source, tw.drop_target, tw.drop_index]);

                            $(".dg_drop_cursor", grid).hide();
                            $(".dg_drop_locator", grid).hide();                    
                            $(".dg_drop", grid).removeClass("dg_drop dg_drop_before dg_drop_after dg_drop_inside");
                            $(".dg_node.dg_dragging", grid).removeClass("dg_dragging");

                            tw.dragging = null;
                    }).bind("scroll", function() {
                            $(".dg_drop_cursor").addClass("dg_invalid");
                            $(".dg_drop_locator").hide();

                            $.datagrid.modules.treeview.drop_valid = false;
                    });
                    $.datagrid.modules.treeview.global_events = true;
            }
        },
        process: function(grid, ops, rows) {
            var ci = ops.treeview.column_index;
            $.datagrid.rows.each(rows, function(row, parent, path, index) {
                var cell;

                cell = row.cells[ci];
                if ($.undefined(cell.content))
                    cell = {content: cell};

                if ($.undefined(cell.attr))                        
                    cell.attr = {};

                if (path != "")
                    cell.attr.path = path + "." + index.local; else
                    cell.attr.path = index.local;

                cell.attr.type = $.value(row.type, "");
                cell.attr.drop = $.value(row.drop, "");

                if (cell.attr.type) {
                    if (row.attr)
                        row.attr+= " data-type='" + cell.attr.type + "'"; else
                        row.attr = "data-type='" + cell.attr.type + "'";
                }
                row.cells[ci] = cell;

                return cell.attr.path;                                
            }, ops, "");
        },
        expand: function(cell) {
            var row = cell.parents(".dg_row:first");
            var row_id = row.attr("data-id");
            var grid = cell.parents(".datagrid:first");
            var ops = grid.data("datagrid");   
            var sr = $(".dg_subrows[data-parent=" + cell.attr("data-row") + "]", grid);
            
            row.removeClass("dg_collapsed");
            sr.stop().slideDown();

            if (ops.treeview.save_expanded && row_id) {   
                ops.state.expanded = $.flag_state(ops.state.expanded, row_id, !ops.treeview.expand_all);

                $.datagrid.save_state(grid, ops);
            }

            $.datagrid.save_state(grid, ops);                
        },    
        finalize: function(grid, ops, interval) {
            var tw = $.datagrid.modules.treeview;
            var cache = ops._cache;
            if (ops.treeview.show_lines) {
                var lc_queue = tw.init_lc_queue(interval[0]-1, ops);

                var s = ops.treeview.indent / 2 - 1;
                var h = ops.cell_height / 2;
                var tl_v  = "<div class='dg_tl dg_tl_v'  style='width: " + s + "px;";
                var tl_vh = "<div class='dg_tl dg_tl_vh' style='width: " + s + "px; height: " + h + "px;";
                var tl_h  = "<div class='dg_tl dg_tl_h'  style='width: " + s + "px; height: " + h + "px;";
            }

            var sub, row, row_id, exp, pid;
            for (var k = interval[0]; k < interval[1]; k++) {
                sub = $(cache.sub_rows[k]);  
                if (!sub.length) continue;

                row = $.datagrid.select_row(k, grid);
                row_id = row.attr("data-id");

                exp = (row_id) ? $.flag_state(ops.state.expanded, row_id) : !ops.treeview.expand_all;   
                if (!row.hasClass("dg_loaded")) exp = false;
                if (!ops.treeview.save_expanded) exp = false;

                if (exp == ops.treeview.expand_all) {  
                    row.addClass("dg_collapsed");
                    sub.hide();    
                } 
            }

            var cell,row,row_id,level,grip,v,padding,i,j,cdata;

            for (var k = interval[0]; k < interval[1]; k++) {
                cell = $.datagrid.select_cells(ops.treeview.column_index, k, grid);
                cdata = cell.find(".dg_cell_data");
                row = cell.parent();
                row_id = row.attr("data-id");
                level = parseInt(row.attr("data-level"));

                if (ops.treeview.drag_grip && (v = $(ops.treeview.drag_grip)).length)
                    grip = v; else
                    grip = cell;

                cell.addClass("dg_node");
                grip.addClass("dg_node_grip");

                padding = ops.treeview.indent * level;
                if (ops.treeview.show_icons)
                    padding+= ops.treeview.indent;

                cdata.css("padding-left", padding + "px");
                if (ops.treeview.show_lines) {
                    var html = "";
                    for (var i = 0; i < level; i++) 
                        if (!lc_queue[i])
                            html+= tl_v + " left: " + i * ops.treeview.indent + "px' data-i='" + i + "'></div>";

                    if (!row.nextAll(".dg_row").length) {
                        lc_queue[level] = true;
                        html+= tl_vh + " left: " + i * ops.treeview.indent + "px' data-i='" + i + "'></div>";
                    } else {
                        lc_queue[level] = false;
                        html+= tl_v + " left: " + i * ops.treeview.indent + "px' data-i='" + i + "'></div>";
                    }

                    html+= tl_h + " left: " + (i * ops.treeview.indent + s) + "px' data-i='" + i + "'></div>";     

                    $(html).prependTo(cell);
                } 

                if (ops.treeview.show_icons) {
                    var subrows = row.next(".dg_subrows");
                    if (subrows.length) {
                        var cls = ops.treeview.expand_all ? "dg_expanded" : "dg_collapsed";
                        var icon = $("<div class='dg_node_icon " + cls + "'></div>"); 

                        icon.prependTo(cell).css({width: ops.treeview.indent + 'px', height: ops.cell_height + 'px'});
                        icon.css("left", ops.treeview.indent * level + "px");
                        icon.bind("click", function(e) {
                            var cell = $(this).parents(".dg_cell:first");
                            var row = cell.parents(".dg_row:first");
                            var row_id = row.attr("data-id");
                            var grid = cell.parents(".datagrid:first");
                            var ops = grid.data("datagrid");
                            
                            if (row.hasClass("dg_loading")) return;

                            if (typeof ops.treeview.onExpand == "function") {
                                var res = ops.treeview.onExpand.apply(row);
                                if (!res) return false;
                            }

                            var sr = $(".dg_subrows[data-parent=" + cell.attr("data-row") + "]", grid);
                            if (sr.is(":visible")) {
                                row.addClass("dg_collapsed");
                                sr.stop().slideUp(); 

                                if (ops.treeview.save_expanded && row_id) {
                                    ops.state.expanded = $.flag_state(ops.state.expanded, row_id, ops.treeview.expand_all);

                                    $.datagrid.save_state(grid, ops);
                                }  
                            } else {
                                if (!row.hasClass("dg_loaded")) {
                                    $.datagrid.load_row(grid, row.attr("data-row"), ops, function() {
                                        row.addClass("dg_loaded");
                                        $.datagrid.modules.treeview.expand(cell);
                                    });                                
                                } else {
                                    $.datagrid.modules.treeview.expand(cell);
                                }
                            }

                            e.stopPropagation();
                            return false;
                        });
                    } 
                }        

                if (ops.treeview.node_select !== false) {
                    grip.bind("click", function(e) {
                        var grid = $(this).parents(".datagrid:first");

                        $(".dg_node_grip.dg_selected", grid).removeClass("dg_selected");

                        $(this).addClass("dg_selected");
                        return false;
                    });                                
                }   

                if (ops.treeview.dnd) {
                    $("img", cell).bind('dragstart', function(){ return false; });
                    grip.bind("mousedown", function(e) {
                        var tw = $.datagrid.modules.treeview;
                        var grid = $(this).parents(".datagrid:first");

                        var cell = $(this);
                        if (!cell.is('.dg_cell'))
                            cell = cell.parents(".dg_cell:first");

                        if (!cell.length) return;

                        cell.addClass("dg_dragging");
                        var data = $(".dg_cell_data", cell);
                        var w = data.width();
                        var h = data.height();

                        tw.dragging = cell;                        
                        tw.drag_coord = [e.pageX, e.pageY];

                        $(".dg_drop_cursor").html(data.html()).css({width: w + "px", height: h + "px"});
                    });
                    cell.bind("mousemove", function(e) {
                        var tw = $.datagrid.modules.treeview;
                        if (!tw.dragging) return false;

                        clearTimeout(tw.drag_timeout);

                        if (Math.abs(tw.drag_coord[0] - e.pageX) < tw.drag_treshold && Math.abs(tw.drag_coord[1] - e.pageY) < tw.drag_treshold)
                            return false;

                        tw.last_event = e;
                        tw.last_context = this;

                        tw.drag_timeout = setTimeout(function() {
                            tw.drag.apply(tw.last_context, [tw.last_event]);
                        }, 10);
                    });
                } 
            }  
        }, 
        init_lc_queue: function(ind, ops) {
            if (ind < 0) return [];

            var sub, row = $(ops._cache.rows[ind]);
            var res = [];
            while (row.length) {
                res.push(row.hasClass("dg_last"));
                sub = row.parentsUntil(".datagrid", ".dg_subrows:first");
                if (sub.length)
                    row = $(ops._cache.rows[sub.attr("data-parent")]); else
                    row = [];
            }

            return res.reverse();
        },                                          
        drag: function(e) {
                var src_node = $(".dg_node.dg_dragging");
                if (!src_node.length) return;

                $.datagrid.modules.treeview.drop_valid = null;

                var grid = $(this).parents(".datagrid:first");
                $(".dg_drop", grid).removeClass("dg_drop dg_drop_inside dg_drop_before dg_drop_after");
                var ops = grid.data("datagrid");

                var dst_node = $(this);                          

                var drop_cursor = $(".dg_drop_cursor", grid).removeClass("dg_invalid");
                var drop_locator = $(".dg_drop_locator", grid);

                var ofs = dst_node.offset(), pos, y, h;
                y = e.pageY - ofs.top;
                h = dst_node.outerHeight();
                if (y <= ops.treeview.drop_treshold)
                        pos = "before"; else
                if (y <= h - ops.treeview.drop_treshold)
                        pos = "inside"; else
                        pos = "after";

        //  LOCATOR    
                var indent = ops.treeview.indent, cy, cx, p, cw;
                if (pos == "before")    
                        cy = ofs.top; else
                if (pos == "after")
                        cy = ofs.top + h; else
                if (h/2 < indent/2)
                        cy = ofs.top + indent/2; else
                        cy = ofs.top + h/2;

                var dst_data = $(".dg_cell_data", dst_node);
                p = parseInt(dst_data.css("padding-left").replace("px",""));
                cx = ofs.left + p - indent/2;

                if (pos == "inside")
                        cw = 0; else
                        cw = dst_data.width() + indent/2;

                drop_locator.show();

                cx = cx - $(document).scrollLeft();
                cy = cy - $(document).scrollTop();

                drop_locator.css({top: cy, left: cx, width: cw + 'px'});

        //  CURSOR    
                cx = e.pageX + 12 - $(document).scrollLeft();
                cy = e.pageY + 10 - $(document).scrollTop();    

                drop_cursor.show().css({left: cx, top: cy}).removeClass(".dg_invalid");    

                if (pos == "inside" && dst_node.is(".dg_dragging")) {
                        //drop_locator.hide();
                        drop_cursor.addClass("dg_invalid");    
                        return;
                }

                var trg_node;
                if (pos != "inside") {
                        var subrows = dst_node.parents(".dg_subrows:first");
                        if (subrows.length) 
                                trg_node = $(".dg_row[data-row=" + subrows.attr("data-parent") + "] > .dg_node", grid); else
                                trg_node = null;
                } else 
                        trg_node = dst_node;

                var dst_row = dst_node.parents(".dg_row:first");
    var cont = dst_row.closest(".dg_subrows");
    if (!cont.length)
        cont = dst_row.closest(".dg_rows");



                var trg_index = cont.children(".dg_row").index(dst_row);//parseInt(dst_row.attr("data-index"));
                if (pos == 'inside') {
                        var part = trg_node.parents(".dg_part:first");
                        var dst_subrows = $(".dg_subrows[data-parent=" + dst_row.attr("data-row") + "] > .dg_row", part);
                        trg_index = dst_subrows.length;
                } else
                if (pos == 'after')
                        trg_index++;

                var src_path = src_node.attr('data-path');
                var trg_path = (trg_node) ? trg_node.attr('data-path') : "";

                if (trg_path.indexOf(src_path) == 0) {
                        drop_cursor.addClass("dg_invalid");    
                        return;
                }
                if (ops.treeview.types !== false) {
                        var trg_drop = (trg_node) ? $.datagrid.get_attr(trg_node, "data-drop", "") : $.datagrid.get_attr(grid, "data-drop", "");
                        var src_type = $.datagrid.get_attr(src_node, "data-type", "");

        if (trg_drop != "*")
                            trg_drop = "," + trg_drop + ",";
                        trg_drop.indexOf("," + src_type + ",");

                    if (trg_drop != "*" && (!src_type || trg_drop.indexOf("," + src_type + ",") == -1)) {
                                drop_cursor.addClass("dg_invalid");    
                                return;
                        } 
                }

                $.datagrid.modules.treeview.drop_source = src_node;
                $.datagrid.modules.treeview.drop_valid = true;
                $.datagrid.modules.treeview.drop_index = trg_index;
                $.datagrid.modules.treeview.drop_target = trg_node;

                if (trg_node)
                        trg_node.addClass("dg_drop dg_drop_inside");
                if (pos != "inside")
                        dst_node.addClass("dg_drop dg_drop_" + pos);
        },
        path: function(grid, node) {
                if (node == null)
                        return [];
                var row = node.parents(".dg_row:first");
                var res = [], parent;
                while (row.length) {
                        res.push(row.index());//parseInt(row.attr("data-index")));
                        parent = row.attr("data-parent");

                        row = $(".dg_row[data-row=" + parent + "]", grid);
                }

                return res.reverse();
        },
        drop: function(src, dst, index) {     
                var grid = src.parents(".datagrid:first");
                var ops = grid.data("datagrid");
                var tw = $.datagrid.modules.treeview;

    var src_id = src.closest(".dg_row").attr("data-id");
    var dst_id = (dst) ? dst.closest(".dg_row").attr("data-id") : 0;

                if (typeof ops.treeview.onDrop == "function")
                        ops.treeview.onDrop.apply(grid, [{
            "index"    : index,
                                "src_node" : src,
                                "dst_node" : dst,
            "src_id" : src_id,
            "dst_id" : dst_id
                        }]);
        }
    }

    $.datagrid.methods.get_row = function(args) {
            var ok = (args.length > 1 && args[0] == "get_row"); 
            if (!ok) return false;  

            var ops = $(this).data("datagrid");
            var path = args[1];

            return { result: $.datagrid.rows.get(path, ops) }
    }
    $.datagrid.methods.add_row = function(args) {
            var ok = (args.length > 2 && args[0] == "add_row"); 
            if (!ok) return false;  

            var ops = $(this).data("datagrid");
            var path = args[1];
            var row = args[2];

            $.datagrid.rows.insert(path, row, ops);

            return { result: true }
    }            
    $.datagrid.methods.move_row = function(args) {
            var ok = (args.length > 2 && args[0] == "move_row"); 
            if (!ok) return false;      

            var ops = $(this).data("datagrid");
            var src_path = args[1];
            var dst_path = args[2];

            var res = $.datagrid.rows.move(src_path, dst_path, ops);

            return { result: res }                
    }
    $.datagrid.methods.remove_row = function(args) {
            var ok = (args.length > 1 && args[0] == "remove_row"); 
            if (!ok) return false;      

            var ops = $(this).data("datagrid");
            var path = args[1];

            return { result: $.datagrid.rows.remove(path, ops) }
    }
    $.datagrid.methods.toggle_row = function(args) {
            var ok = (args.length > 1 && args[0] == "toggle_row"); 
            if (!ok) return false;  

            var ops = $(this).data("datagrid");
            var row = $.datagrid.select_row(args[1], this);    
            var icon = row.find(".dg_node_icon");    	
            if (args.length > 2 && args[2] != icon.is(".dg_collapsed")) return true;

            icon.click();
            return true;
    }
    
    $.datagrid.reload = function(grid, post, state) {
        var ops = grid.data("datagrid");
        if (ops.overlay)
            $.datagrid.overlay(grid, true);  
        
        if (ops.mode == "ajax") {
            var prop, v;
            if ($.undefined(post))
                post = {};
            
            $.extend(post, ops.param);
            $.extend(ops.state, state);
            
            for (var prop in ops.state) {
                if (ops.state[prop] != null && typeof (v = ops.state_ops[prop]) != "undefined" && v.post)
                    post[prop] = ops.state[prop];
            }
            
            if (ops.state.expanded)
                post["loaded"] = ops.state.expanded.split(",");
            
            $.post(ops.handler, post, function(r) {
                if (r === null) {
                    $.datagrid.overlay(grid, false);      
                    return;
                }
                ops.data = r.data;
                $.extend(ops.state, r.state);
                
                $.datagrid.load_data(grid, ops, r.rows);
                $.datagrid.overlay(grid, false);      
            }, "json").fail(function() { 
                $.datagrid.overlay(grid, false);      
            });    
        } else 
            $.datagrid.build(grid, ops);
    }   
})( jQuery );                                 

