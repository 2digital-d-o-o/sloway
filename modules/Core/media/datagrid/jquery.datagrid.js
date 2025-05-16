/*
    TODO:
    - resize -> min width?
    - update -> samo en row! NI TREBA
    - parts heights after expand/collapse! OK?
    - sync heights = modul
    - editable -> za posamezni cell
    
    - subrows build sele ko se expanda! (ce ma expanded = false v options)
    - validate
    - resize left part over right (ne sme)
*/

(function( $ ){   
    "use strict";

    $.datagrid = { 
        def_options: {
            mode: null,
            handler: null,
            row_count: null,
            height: null,
            inner_height: null,
            max_cell_width: 0,
            min_cell_width: 30,
            save_state: true,
            width: false,
            cookie_prefix: "",
            cookie_name: "",
            row_height: 20,
            cls: "",
            attr: "",
            footer: {},
            overlay: true,
            state: {},
            modules: [],  
            model: [],
            rows: [],
            style: null,
            row_count: 0,
            
            trans: {},
            
            onCellCustomEdit: null,
            onCellEdit: null,
            onCellClick: null,
            beforeBuild: null,
            afterBuild: null,
            beforeLoad: null,
            afterLoad: null,
            onLoaded: null,
        },  
        def_layout: {
            fixed: false,
            freeze: [0,0],
            fill: "none",
        },
        def_column: {
            align: "left",

            width: "content",
            id: null,    
            cls: "", 
            
            edit: false,
            edit_grip: null,
            
            max_width: null,
            min_width: null,
            fixed: false,
        },
        state: {
            start: 0,
            end: 0,
            count: 0,
            widths: {},
        },
        state_ops: {
        },
        modules: {
            call: function(name, grid, ops, args) {
                var module,func;                
                
                for (var i in ops.modules) {
                    module = ops.modules[i];
                
                    func = $.datagrid.modules[module][name];
                    if (typeof func == "function")
                        func.apply($.datagrid.modules[module], [grid, ops, args]);
                }              
            },   
            core: {
                finalize: function(grid, ops, interval) {
                    for (var i = interval[0]; i < interval[1]; i++) {
                        $.datagrid.select_row(i, grid).hover(function() {
                            var grid = $(this).parents(".datagrid:first"); 
                            
                            $.datagrid.select_row($(this).attr("data-row"), grid).addClass("dg_hover");
                        }, function() {
                            var grid = $(this).parents(".datagrid:first"); 
                            
                            $.datagrid.select_row($(this).attr("data-row"), grid).removeClass("dg_hover");
                        });
                        $.datagrid.select_cells(null, i, grid).hover(function() {
                            $(this).addClass("dg_hover");
                        }, function() {
                            $(this).removeClass("dg_hover");
                        }).click(function(e) {
                            var grid = $(this).parents(".datagrid:first");
                            var ops = grid.data("datagrid");
                            
                            if ($(this).hasClass("dg_copyable")) {
                                var val = $(this).children(".dg_cell_data").text();
                                
                                var temp = $("<input>");
                                $("body").append(temp);
                                temp.val(val).select();
                                document.execCommand("copy");
                                temp.remove();                                  
                            } else 
                            if (typeof ops.onCellClick == 'function') {
                                var ci = parseInt($(this).attr('data-col'));
                                var row = $(this).parents(".dg_row:first");
                                var data = {
                                    col: ci,
                                    row: parseInt($(this).attr('data-row')),
                                    col_id: ops.model[ci].id,
                                    row_id: row.attr('data-id')
                                }
                                ops.onCellClick.apply(this, [e, data]);
                            }
                        });
                    }   
                }
            }, 
            col_resize: {
                def_options: {
                },
                state: {
                    widths: {},
                },
                state_ops: {
                    widths: { save: true }
                },
                global_events: false,
                dragging: null,
                update_column: null,
                fit_column: function(grid, col) {
                    var ops = grid.data("datagrid");
                    var rid = ops.model[col].id;      
                    
                    if (ops.model[col].width != "content")
                        ops.state.widths[rid] = "content"; else
                        delete ops.state.widths[rid];
                    
                    $.datagrid.update(grid, ops);  
                },
                get_grip_cell: function(grid, grip) {
                    return $(".dg_header .dg_cell[data-col=" + grip.attr("data-col") + "]", grid);       
                },
                build: function(grid, ops) {
                    var i,j,part,grip,cls;
                    var cs = $.datagrid.modules.col_resize;
                    
                    for (i in ops.model) {
                        //if (i == ops.model.length - ops.layout.freeze[1] - 1 && ops.layout.fill != 'spacer') continue;

                        part = $.datagrid.get_part(i, ops);
                        cls = ops.model[i].fixed ? " dg_fixed" : "";
                        grip = "<div class='dg_grip" + cls + "' data-col='" + i + "'><div></div></div>";
                                
                        $(grip).appendTo($(".dg_header .dg_" + part + " .dg_rows", grid)).bind("mousedown", function(e) {
                            var grid = $(this).parents(".datagrid:first");
                            //$(".dg_resizer_lock", grid).show();
                            grid.addClass("dg_dis_select");
                            
                            $(this).addClass("dg_dragging");
                            $.datagrid.modules.col_resize.dragging = $(this);
                            
                            e.preventDefault();
                        }).dblclick(function(e) {
                            var grid = $(this).parents(".datagrid:first");
                            $.datagrid.modules.col_resize.fit_column(grid, $(this).attr("data-col"));
                            
                            e.preventDefault();
                            return false;
                        });
                    }
                    
                    $("<div class='dg_resizer'><div></div></div>").appendTo(grid);
                    //$("<div class='dg_resizer_lock'></div>").appendTo(grid);   
                               
                    if (!cs.global_events) {
                        $(window).bind("mouseup", function() {
                            var cs = $.datagrid.modules.col_resize;  
                            
                            var grip = cs.dragging;
                            if (!grip) return;
                            
                            grip.removeClass("dg_dragging");
                            cs.dragging = null;
                            
                            var part = grip.parents(".dg_part:first");
                            var grid = part.parents(".datagrid:first");
                            var ops = grid.data("datagrid");
                            
                            grid.removeClass("dg_dis_select");
                            
                            var res = grid.find(".dg_resizer > div");
                            if (!res.is(":visible")) return;
                                                         
                            var sx = parseInt(res.css("left").replace("px", "")); 
                            var cell = cs.get_grip_cell(grid, grip);
                            var w, rows;
                            if (part.is(".dg_right")) {
                                var dw = part.position().left + cell.position().left - sx;
                                w = cell.width() + dw;
                            } else {
                                rows = $("> .dg_rows", part);
                                w = sx - part.position().left - parseInt(part.css("margin-left")) - parseInt(rows.css("margin-left"));
                                
                                var prev = grip.prevAll(".dg_grip:visible:first");
                                if (prev.length) {
                                    var prev_cell = cs.get_grip_cell(grid, prev);
                                    
                                    w = w - prev_cell.width() - prev_cell.position().left;
                                } 
                            }
                            var col = parseInt(cell.attr("data-col"));
                            var col_id = ops.model[col].id;
                            
                            ops.state.widths[col_id] = parseInt(w);
                            $.datagrid.save_state(grid, ops);
                            cs.update_column = col;
                            $.datagrid.update(grid, ops);
                            
                            $(".dg_resizer", grid).hide();
                            
                            //return false;
                        }).bind("mousemove", function(e) {
                            var grip =  $.datagrid.modules.col_resize.dragging;        
                            if (!grip) return;
                            
                            var cont = grip.parents(".dg_cont:first");
                            var grid = cont.parents(".datagrid:first");
                            
                            var x = e.pageX - cont.offset().left;
                            $(".dg_resizer_lock", grid).show();
                            $(".dg_resizer", grid).show().children().css('left', x + 'px');

                            e.stopPropagation();
                            e.preventDefault();
                            
                            return false;
                        });
                        
                        cs.global_events = true;
                    }                              
                },
                finalize: function(grid, ops) {
                    var i,j,part,grip,cls;
                    var cs = $.datagrid.modules.col_resize;
                    
                    for (i in ops.model) {
                        //if (i == ops.model.length - ops.layout.freeze[1] - 1 && ops.layout.fill != 'spacer') continue;
                        
                        part = $.datagrid.get_part(i, ops);
                        
                        cls = ops.model[i].fixed ? " dg_fixed" : "";
                        grip = "<div class='dg_grip" + cls + "' data-col='" + i + "'><div></div></div>";
                                
                        $(grip).appendTo($(".dg_body .dg_" + part, grid)).bind("mousedown", function(e) {
                            var grid = $(this).parents(".datagrid:first");
                            grid.addClass("dg_dis_select");
                            
                            $(this).addClass("dg_dragging");
                            $.datagrid.modules.col_resize.dragging = $(this);
                            
                            e.preventDefault();
                        }).dblclick(function(e) {
                            var grid = $(this).parents(".datagrid:first");
                            $.datagrid.modules.col_resize.fit_column(grid, $(this).attr("data-col"));
                            
                            e.preventDefault();
                            return false;
                        });
                    } 
                    
                },
                update: function(grid, ops) { 
                    $(".dg_header .dg_part", grid).each(function() {
                        var part = $(this);
                        $(".dg_cell", part).each(function() {
                            var grip = $(".dg_grip[data-col=" + $(this).attr("data-col") + "]", grid);
                            
                            var pos = $(this).position().left;
                            if (!part.is(".dg_right"))
                                pos+= $(this).width();  
                               
                            grip.css("left", pos);  
                        });
                    });  
                },
            },
            visibility: {
                hide_columns: function(grid, ops) {
                    var column;
                    for (var i in ops.model) {
                        column = ops.model[i];
                        if (typeof column.visible != "undefined" && !column.visible) {
                            $(".dg_header .dg_cell[data-col=" + i + "]", grid).hide();
                            $(".dg_header .dg_grip[data-col=" + i + "]", grid).hide();
                            $.datagrid.select_cells(i, null, grid).hide();
                            
                            $(".dg_body .dg_main .dg_grip[data-col=" + i + "]", grid).hide();
                        } 
                    }
                },
                build: function(grid, ops) {
                    $.datagrid.modules.visibility.hide_columns(grid, ops);    
                },
                finalize: function(grid, ops) {
                    $.datagrid.modules.visibility.hide_columns(grid, ops);
                }
            },
            pages: {
                def_options: {
                    per_page: 10,
                    values: [10,20,50],
                },
                state: {
                    page: 1,
                    per_page: 10,
                    page_count: 0,
                },
                state_ops: {
                    per_page: { save: true, post: true },
                    page: { save: true, post: true},
                },
                build_menu: function(curr, count) {
                    if (count <= 1) return "";
                    
                    var buttons = [];
                    for (var i = 1; i < Math.min(curr-1, 3); i++) 
                        buttons.push(i);
                    if (curr > 4) buttons.push(0);
                    if (curr > 1) buttons.push(curr-1);
            
                    buttons.push(curr);

                    if (curr < count) buttons.push(curr+1);
                    if (count - curr > 3) buttons.push(0);

                    for (i = Math.max(curr + 2, count-1); i <= count; i++) 
                        buttons.push(i);
                        
                    var prev = (curr > 1) ? curr - 1 : 0;
                    var next = (curr < count) ? curr + 1 : 0;
                    
                    var res = "";
                    if (prev)
                        res+= "<a href='" + prev + "' class='dg_pages_prev' onclick='return false'></a>";
                    
                    for (var i = 0; i < buttons.length; i++) {
                        var ind = buttons[i];
                        if (ind == 0)
                            res+= "<span>...</span>"; else
                        if (ind == curr) {
                            res+= "<select name='dg_page'>";
                            for (var j = 1; j <= count; j++) {
                                var sel = (j == curr) ? "selected" : "";
                                res+= "<option value='" + j + "'" + sel + ">" + j + "</option>";                
                            }
                            res+= "</select>";    
                        } else 
                            res+= "<a href='" + ind + "' onclick='return false'>" + ind + "</a>";
                    }
                    
                    if (next)
                        res+= "<a href='" + next + "' class='dg_pages_next' onclick='return false'></a>";
                    
                    return "<div class='dg_pages'>" + res + "</div>";
                },
                build_info: function(ops) {
                    var start = (ops.state.page-1) * ops.state.per_page + 1;
                    var end = start + ops.row_count - 1;  
                    
                    return $.datagrid.translate("Viewing", ops) + ": " + start + " - " + end + " " + $.datagrid.translate("of", ops) + " " + ops.state.total; 
                },
                build_pp_menu: function(ops) {
                    var res = $.datagrid.translate("Rows per page", ops) + ": <select name='dg_perpage'>";
                    var value,chk;
                    
                    for (var i in ops.pages.values) {
                        value = ops.pages.values[i];
                        chk = (value == ops.state.per_page) ? "selected" : "";
                        
                        res+= "<option value='" + value + "' " + chk + ">" + value + "</option>";
                    }
                    res+= "</select>";    
                    
                    return res;
                },
                config: function(grid, ops) {
                    if ($.undefined(ops.footer.elements))
                        ops.footer.elements = {};
                    
                    ops.footer.elements.pages = {};
                    ops.footer.elements.pages_info = {};
                    ops.footer.elements.pages_perpage = {};
                },
                process: function(grid, ops) {
                    var pg = this;
                    
                    ops.state.page_count = 0;
                    ops.state.total = parseInt(ops.state.total);
                    ops.state.per_page = parseInt(ops.state.per_page);
                    if (ops.state.per_page < ops.state.total) {
                        ops.state.page_count = parseInt(ops.state.total / ops.state.per_page);
                        if (ops.state.total % ops.state.per_page)
                            ops.state.page_count++;
                    } 
                    
                    if (ops.state.page > ops.state.page_count) {
                        ops.state.page = 1;
                        $.datagrid.save_state(grid, ops);
                    }
    
                    if (ops.mode != "ajax") {
                        ops.state.row_start = ops.state.per_page * (ops.state.page-1);
                        ops.state.total = ops.state.per_page;
                    }                    
                    
                    var elem_pages = $.datagrid.get_footer_elem(grid, "pages");
                    var elem_pages_info = $.datagrid.get_footer_elem(grid, "pages_info");
                    var elem_pages_perpage = $.datagrid.get_footer_elem(grid, "pages_perpage");
                    
                    if (ops.state.page_count > 1)
                        elem_pages.html(pg.build_menu(ops.state.page, ops.state.page_count)).show(); else
                        elem_pages.hide();
                        
                    if (ops.state.total)
                        elem_pages_info.html(pg.build_info(ops)).show(); else
                        elem_pages_info.hide();
                                        
                    elem_pages_perpage.html(pg.build_pp_menu(ops));
                },
                finalize: function(grid, ops) {
                    $(".dg_pages [name=dg_page]", grid).change(function() {
                        var grid = $(this).parents(".datagrid:first");
                        var ops = grid.data("datagrid");

                        ops.state.page = parseInt($(this).val());
                        
                        $.datagrid.save_state(grid, ops);
                        $.datagrid.reload(grid);
                    });  
                    $("[name=dg_perpage]", grid).change(function() {
                        var grid = $(this).parents(".datagrid:first");
                        var ops = grid.data("datagrid");

                        ops.state.per_page = parseInt($(this).val());
                        
                        $.datagrid.save_state(grid, ops);
                        $.datagrid.reload(grid);
                    });
                    $(".dg_pages > a", grid).click(function() {
                        var grid = $(this).parents(".datagrid:first");
                        var ops = grid.data("datagrid");

                        ops.state.page = parseInt($(this).attr("href"));

                        $.datagrid.save_state(grid, ops);
                        $.datagrid.reload(grid);
                        
                        return false;
                    });      
                },
            },
            sorting: {
                def_options: {
                    sort: null,
                    sort_dir: 1,
                },
                state_ops: {
                    sort: { save: true, post: true },
                    sort_dir: { save: true, post: true },
                },                
                config: function(grid, ops) {
                    var col,cls;
                    
                    if (typeof ops.state.sort == "undefined")
                        ops.state.sort = ops.sorting.sort;
                    
                    var def = null;
                    var valid = false;
                    for (var col in ops.model) {
                        if (!ops.model[col].sort) continue;
                        
                        if (def == null) def = ops.model[col].id;
                        if (ops.state.sort == ops.model[col].id) valid = true;
                    }
                    
                    if (!valid)
                        ops.state.sort = def;
                    
                    if (typeof ops.state.sort_dir == "undefined")
                        ops.state.sort_dir = ops.sorting.sort_dir;
                },
                build: function(grid, ops) {
                    $(".dg_header .dg_cell", grid).each(function() {
                        var col = parseInt($(this).attr("data-col"));
                        
                        if (!ops.model[col].sort) return;     
                        
                        $(this).addClass("dg_sortable");
                        $(this).click(function() {
                            var grid = $(this).parents(".datagrid:first");
                            var cell = $(this).parents(".dg_cell:first");
                            var ops = grid.data("datagrid"), dir;
                            var col = parseInt($(this).attr("data-col"));  
                            var col_id = ops.model[col].id;
                            
                            if ($(this).is(".dg_sorted_asc")) {
                                ops.state.sort = col_id;
                                ops.state.sort_dir = -1;
                            } else {
                                ops.state.sort = col_id;
                                ops.state.sort_dir = 1;
                            }
                            
                            if (ops.state.sort_dir === true)
                                ops.state.sort_dir = 1;
                                
                            $.datagrid.save_state(grid, ops);
                            $.datagrid.reload(grid);
                        });
                    });
                },
                finalize: function(grid, ops) {
                    $(".dg_header .dg_cell", grid).each(function() {
                        var col = parseInt($(this).attr("data-col"));
                        
                        $(this).removeClass("dg_sorted_asc dg_sorted_desc");
                        if (ops.model[col].id == ops.state.sort) 
                            $(this).addClass(ops.state.sort_dir == 1 ? "dg_sorted_asc" : "dg_sorted_desc"); 
                    });                    
                }
            },
            row_check: {
                def_options: {
                    fixed: true,
                    name: null,
                    single: false,
                    check_all: true,
                    cross_pages: false,
                    width: 30,
                },
                state: {
                    checked: "",  
                    last_checked: null,
                },      
                checked: function(id, ops) {
                    var checked;
                    var all = ops.state.checked.indexOf('all') == 0;
                    if (id == 'all')
                        return all;
                        
                    if (id == 'count') {
                        checked = ops.state.checked;   
                        var c = (checked != "") ? checked.split(",").length : 0;
                        
                        return (all) ? ops.state.count - c + 1 : c;
                    }
                        
                    checked = "," + ops.state.checked + ",";     
                    
                    var ind = checked.indexOf("," + id + ",");
                    
                    return (all) ? ind == -1 : ind != -1;
                },
                check: function(grid, id, state, ops) {
                    if (id == 'all') {
                        ops.state.checked = 'all';
                        if ($.isString(ops.row_check.name))
                            grid.find("[name=" + ops.row_check.name + "]").val(ops.state.checked);                        
                            
                        return;    
                    }
                    
                    if (ops.row_check.single) {
                        ops.state.checked = (state) ? id : "";  
                        if ($.isString(ops.row_check.name))
                            grid.find("[name=" + ops.row_check.name + "]").val(ops.state.checked);                        
                        
                        return;
                    }
                    
                    var all = ops.state.checked.indexOf('all') == 0;  
                    var checked = "," + ops.state.checked + ",";
                    
                    if (all) state = !state;
                    
                    if (state) {
                        if (checked.indexOf("," + id + ",") != -1) return;
                    
                        if (ops.state.checked.length)
                            ops.state.checked+= ",";
                    
                        ops.state.checked+= id;
                    } else {
                        checked = checked.replace("," + id + ",", ",");
                        checked = checked.substring(1, checked.length-1);
                        
                        if (checked == ",") 
                            checked = "";
                        
                        ops.state.checked = checked;
                    } 
                    
                    if ($.isString(ops.row_check.name))
                        grid.find("[name=" + ops.row_check.name + "]").val(ops.state.checked);
                },
                config: function(grid, ops) {
                    if (ops.row_check.single)
                        ops.check_all = false;
                        
                    var cb_col = {
                        id: "rowcheck",
                        sort: false,
                        width: ops.row_check.width,
                        fixed: ops.row_check.fixed,
                        valign: "center",
                        min_width: 0,
                        content: (ops.row_check.check_all) ? "<input type='checkbox' class='dg_check'>" : "",
                    }    
                    ops.model.splice(0, 0, cb_col);
                    
                    if (!ops.footer.elements)
                        ops.footer.elements = {};
                        
                    ops.footer.elements.row_check_info = {}; 
                },     
                build: function(grid, ops) {
                    if ($.isString(ops.row_check.name))
                        $("<input name='" + ops.row_check.name + "' type='hidden' value=''>").appendTo(grid);
                    
                    if (ops.row_check.single) 
                        $(".dg_header .dg_check", grid).attr("disabled", 1); else
                    if (this.checked('all', ops)) 
                        $(".dg_header .dg_check", grid).attr("checked", 1);
                               
                    if (!ops.row_check.single)
                    $(".dg_header .dg_check", grid).bind("click", function () {
                        var grid = $(this).parents(".datagrid:first");
                        var ops = grid.data("datagrid");
                        
                        var val = $(this).is(":checked") ? 1 : 0; 
                        var chk, row, rows = ops._cache.rows;
                        
                        var checked = [];
                        for (var i in rows) {
                            row = $(rows[i]);
                            chk = $(row).find(".dg_check");
                            
                            if (val) {
                                row.addClass("cg_checked");
                                chk.attr("checked", 1); 
                                checked.push(row.attr("data-id"));
                            } else {
                                row.removeClass("cg_checked");
                                chk.removeAttr("checked");                                
                            }
                        }
                        
                        ops.state.checked = checked.join(",");
                        $.datagrid.modules.row_check.update_footer(grid, ops);        
                    });
                },     
                process: function(grid, ops, rows) {
                    var init_rows = function(rows, ops) {
                        var chk;
                        for (var i = 0; i < rows.length; i++) {
                            chk = rows[i].check;
                            if (typeof chk == "undefined")
                                chk = true;
                                
                            if (chk === "disabled")
                                rows[i].cells.splice(0, 0, "<input type='checkbox' class='dg_check' disabled>"); else
                            if (chk === true || chk === "true")
                                rows[i].cells.splice(0, 0, "<input type='checkbox' class='dg_check'>"); else
                                rows[i].cells.splice(0, 0, ""); 
                                
                            if (typeof rows[i].rows != "undefined")
                                init_rows(rows[i].rows, ops);
                        }                
                    }
                
                    init_rows(rows, ops); 
                },
                finalize: function(grid, ops, interval) {
                    var checks = $.datagrid.select_cells(0, null, grid).find(".dg_check");
                    for (var i = 0; i < checks.length; i++) {
                        var check = $(checks[i]);    
                        var row = check.parents(".dg_row:first");    
                        
                        if (this.checked(row.attr("data-id"), ops))
                            check.attr("checked", 1);     
                        
                        check.bind("click", function(e) {
                            var grid = $(this).parents(".datagrid:first");
                            var ops = grid.data("datagrid");
                            var row = $(this).parents(".dg_row:first");
                            var rows, chk;
                            var last = ops.state.last_checked;
                            var val = $(this).is(":checked");
                            
                            if (!ops.row_check.single && e.shiftKey && last.length && $(".dg_check", last).is(":checked")) {
                                val = true;
                                var li = last.index(".dg_row");
                                var ci = row.index(".dg_row");
                                
                                if (ci > li)
                                    rows = last.nextUntil(row, ".dg_row"); else
                                    rows = row.nextUntil(last, ".dg_row");
                                
                                rows.each(function() {
                                    chk = $(".dg_check", this);
                                    if (chk.is(":checked")) return;
                                    
                                    chk.attr("checked", 1);
                                        
                                    $.datagrid.modules.row_check.check(grid, $(this).attr("data-id"), val, ops);
                                })
                            } 
                                
                            ops.state.last_checked = row;

                            if (val) {
                                if (ops.row_check.single)
                                    $.datagrid.select_cells(0, null, grid).find(".dg_check").removeAttr("checked");    
                                
                                $(this).attr('checked', 1); 
                            } else
                                $(this).removeAttr('checked');
                            
                            $.datagrid.modules.row_check.check(grid, row.attr("data-id"), val, ops);
                            $.datagrid.modules.row_check.update_footer(grid, ops);  
                        });  
                    }
                    this.update_footer(grid, ops);                     
                },
                update_footer: function(grid, ops) {
                    $(".dg_footer .dg_footer_elem", grid).removeClass("dg_disabled");
                    var cnt = $.datagrid.modules.row_check.checked('count', ops), stat = "";
                    
                    if (cnt) {
                        $(".dg_footer .dg_footer_elem[data-enabled=unchecked]", grid).addClass("dg_disabled"); 

                        $(".dg_footer .dg_footer_elem[data-visible=unchecked]", grid).hide();
                        $(".dg_footer .dg_footer_elem[data-visible=checked]", grid).show();
                        
                        stat = cnt + " row(s) selected";
                    } else {
                        $(".dg_footer .dg_footer_elem[data-enabled=checked]", grid).addClass("dg_disabled");
                        
                        $(".dg_footer .dg_footer_elem[data-visible=checked]", grid).hide();
                        $(".dg_footer .dg_footer_elem[data-visible=unchecked]", grid).show();
                    }
                    
                    $(".dg_footer .dg_footer_elem[data-id=row_check_info]", grid).html(stat);
                }
            },
            row_click: {
                config: function(grid, ops) {
                    if (typeof ops.onRowClick != "function") return;
                    
                    grid.addClass("dg_row_click");
                },
                finalize: function(grid, ops, interval) {
                    if (typeof ops.onRowClick != "function") return;
                    
                    for (var i = interval[0]; i < interval[1]; i++) { 
                        $.datagrid.select_row(i, grid).click(function(e) {
                            var grid = $(this).parents(".datagrid:first"); 
                            var ops = grid.data("datagrid");
                            var row_id = $(this).attr("data-id");
                            var row_ind = $(this).attr("data-row");
                            
                            ops.onRowClick.apply(this, [row_id, row_ind]);                          
                            
                            e.stopPropagation();
                            return false;
                        });
                    }   
                }
            },
            search: { 
                def_options: {
                    save: false,
                    columns: false,
                    value: "",
                },
                state: {
                    search: "",
                    search_columns: [],  
                },                    
                config: function(grid, ops) {       
                    var cls = (ops.search.columns) ? "dg_search_padded" : "";
                    
                    var html = "<span class='dg_search_label'>Search</span>&nbsp;";
                    html+= "<input class='dg_search " + cls + "' type='text' value='" + ops.search.value + "'>";
                    if (ops.search.columns)
                        html+= "<a href='#' class='dg_search_more' onclick='return false'></a>";
                        
                    html+= "<a href='#' class='dg_search_submit' onclick='return false'></a>";
                    html+= "<a href='#' class='dg_search_reset' onclick='return false'></a>";
                    
                    ops.footer.elements.search = {content: html};  
                },
                build: function(grid, ops) {
                    var se = $.datagrid.get_footer_elem(grid, "search");
                    if (!se.length) return;
                    
                    $(".dg_search", se).keydown(function(e) {
                        if (e.keyCode == 13)
                            $.datagrid.reload(grid, { search : $(this).val() });
                    });
                    if (ops.search.columns) {
                        $(".dg_search_more", se).click(function(e) {
                            var grid = $(this).parents(".datagrid:first");
                            var ops = grid.data("datagrid");
                            
                            var items = [];
                            for (var i in ops.model) {
                                var col = ops.model[i];
                                if (col.search)
                                    items.push({content: col.content, name: col.id, check: true, checked: $.inArray(col.id, ops.state.search_columns) >= 0});
                            }
                            
                            $(this).contextmenu(e.clientX, e.clientY, items, {
                                onChange: function(checked, trg) {
                                    var grid = trg.parents(".datagrid:first");
                                    var ops = grid.data("datagrid");
                                    ops.state.search_columns = checked;
                                    
                                    var col, lab = [];
                                    for (var i in checked) {
                                        col = $.datagrid.find_column(checked[i], grid, ops);
                                        lab.push(ops.model[col].content);
                                    }
                                    
                                    if (checked.length)
                                        grid.find(".dg_search_label").html("Search (" + lab.join(", ") + "): "); else
                                        grid.find(".dg_search_label").html("Search:");
                                }    
                            });
                        });
                    }
                    $(".dg_search_submit", se).click(function() {
                        var grid = $(this).parents(".datagrid:first");
                        var ops = grid.data("datagrid");
                        var input = $(this).parent().children(".dg_search");
                        
                        var val = input.val();
                        var post = { search: val };
                        if (ops.search.columns) 
                            post["search_columns"] = ops.state.search_columns;
                            
                        $.datagrid.reload(grid, post);
                        
                        ops.state.search = val;
                    });
                    $(".dg_search_reset", se).click(function() {
                        var grid = $(this).parents(".datagrid:first");
                        var input = $(this).parent().children(".dg_search");
                        
                        input.val("");
                        ops.state.search_columns = [];
                        
                        var post = { search: "" };
                        if (ops.search.columns) 
                            post["search_columns"] = ops.state.search_columns;                        
                        
                        $.datagrid.reload(grid, post);
                        
                        ops.state.search = "";
                    });    
                    
                }
            },
            edit: {
                state: {
                    editing: null
                },
                update_cell: function(cell, col, row, ops) {
                    $.datagrid.modules.edit.init_grip(cell, col, ops);    
                },
                trigger: function(grid, cell, ops) {
                    if (cell.is(".dg_cell_overlay")) return;
                    if (cell.is(".dg_editing")) return;
                    
                    if (ops.state.editing) 
                        $.datagrid.modules.edit.end(ops.state.editing, grid, ops);
                    
                    $.datagrid.modules.edit.start(cell, grid, ops);
                },
                init_grip: function(cell, col, ops) {
                    var column = ops.model[col];
                    var grip = (column.edit_grip) ? cell.find(".dg_cell_data " + column.edit_grip) : cell;
                    
                    var onclick = column.edit_click;
                    if (typeof onclick == "undefined")
                        onclick = true;
                        
                    if (onclick) {
                        grip.bind('click', function() {
                            var cell;
                            if (!$(this).is(".dg_cell"))
                                cell = $(this).parents(".dg_cell:first"); else
                                cell = $(this);

                            var grid = cell.parents(".datagrid:first");
                            var ops = grid.data("datagrid");
                            
                            $.datagrid.modules.edit.trigger(grid, cell, ops);
                            
                            return false;
                        });
                    }
                },
                position: function(cell, edit, ops) {
                },
                start: function(cell, grid, ops, edit_type) {
                    var edit, input;
                    var col = parseInt(cell.attr('data-col')); 
                    var row = parseInt(cell.attr('data-row'));
                    var row_id = parseInt(cell.parents(".dg_row:first").attr("data-id"));
                    var val = $(".dg_cell_value", cell);
                    var cd = $(".dg_cell_data", cell);
                    
                    edit_type = cell.attr("data-edit");//$.getValue(ops.srows, [row, "cells", col, "edit"], ops.model[col].edit);

                    if (edit_type == 'custom') {
                        if (typeof ops.onCellCustomEdit == "function") {
                            var data = {
                                col: col,
                                row: row,
                                col_id: ops.model[col].id,
                                row_id: row_id,
                            }
                            ops.onCellCustomEdit.apply(cell, [data]);    
                        }
                    } else
                    if (edit_type == 'text') {
                        edit = $("<div class='dg_edit dg_edit_text'><input type='text' value='" + val.html() + "'></div>").appendTo(cell);
                        $.datagrid.modules.edit.position(cell, edit, ops);                        
                        
                        var h = cd.height()-8;
                        input = $("input", edit).css("height", h + "px").data("old_value", val.html());
                        if (typeof ops.onCellEditStart == "function")
                            ops.onCellEditStart.apply(input);
                            
                        input.focus();//.select();
                        input.bind("blur", function() {
                            var grid = $(this).parents(".datagrid:first");
                            var ops = grid.data("datagrid");
                            var cell = $(this).parents(".dg_cell:first");
                            $.datagrid.modules.edit.end(cell, grid, ops);
                        }).bind("keydown", function(e) {
                            if (e.keyCode == 27) {
                                $(this).val($(this).data("old_value")).blur();    
                                
                            }
                            if (e.keyCode == 13) 
                                $(this).blur(); 
                        });
                        cell.addClass('dg_editing');
                    } else
                    if (edit_type == 'select') {
                        var edit_html = "<div class='dg_edit dg_edit_select'><select>";
                        for (var key in ops.model[col].edit_items) 
                            edit_html+= "<option value='" + key + "'>" + ops.model[col].edit_items[key] + "</option>";
                        edit_html+= "</div>";    
                        
                        edit = $(edit_html).appendTo(cell);        
                        $.datagrid.modules.edit.position(cell, edit, ops);  
                                        
                        input = $("select", edit).focus();
                        input.bind("change", function() {
                            var grid = $(this).parents(".datagrid:first");
                            var ops = grid.data("datagrid");
                            var cell = $(this).parents(".dg_cell:first");
                            $.datagrid.modules.edit.end(cell, grid, ops);            
                        });
                        cell.addClass('dg_editing');
                    } 
                    
                    
                    ops.state.editing = cell;
                },
                end: function(cell, grid, ops) {
                    var col = parseInt(cell.attr("data-col"));
                    if (ops.model[col].edit == "custom") return;
                    
                    var column = ops.model[col];
                    var cell_edit = $(".dg_edit", cell);
                    var cell_value = $(".dg_cell_value", cell);
                    var cell_data = $(".dg_cell_data", cell);
                    var old_value = cell_value.html();
                    var new_value = $("input", cell_edit).val();
                    var new_content, v, overlay;
                    
                    var row = parseInt(cell.attr("data-row"));
                    var grip = (column.edit_grip) ? $(".dg_cell_data " + column.edit_grip, cell) : $();
                    
                    if (old_value != new_value) {
                        new_content = new_value;
                        if (typeof ops.onCellEdit == 'function') {
                            var param = {
                                "new_value" : new_value,
                                "old_value" : old_value,    
                                "col" : col,
                                "row" : row,
                                "col_id" : ops.model[col].id,
                                "row_id" : cell.parents(".dg_row:first").attr("data-id"),
                            };
                            
                            var res = ops.onCellEdit.apply(cell, [param]);
                            if (typeof res != "undefined") {
                                if (typeof res.value != "undefined")
                                    new_value = res.value;
                                if (typeof res.content != "undefined")
                                    new_content = res.content; 
                                
                                overlay = res.overlay;
                            }
                        }
                        
                        cell_value.html(new_value);
                        if (grip.length)
                            grip.html(new_content); else
                            cell_data.html(new_content);
                    }
                    
                    cell.removeClass("dg_editing");
                    cell_edit.remove();
                    
                    if (overlay)
                        $.datagrid.overlay(cell, true); else
                        cell_data.show();
                    
                    ops.state.editing = null;
                },   
                finalize: function(grid, ops, interval) {
                    var column,cells,cell,i,j,input,grip,edit_type;
                    
                    var k = 0;
                    for (var row = interval[0]; row < interval[1]; row++) {
                        for (i in ops.model) {
                            column = ops.model[i];
                            if (!column.edit) continue;            
                            
                            cells = $.datagrid.select_cells(i, row, grid);
                            for (var j = 0; j < cells.length; j++) {
                                cell = $(cells[j]);
                                
                                edit_type = $.getValue(ops.srows, [k, "cells", i, "edit"], column.edit);
                                
                                if (edit_type)
                                    cell.addClass("dg_editable").attr("data-edit", edit_type); else
                                    continue;
                                
                                $.datagrid.modules.edit.init_grip(cell, i, ops);   
                            }
                        }
                        k++;
                    }
                }        
            },
        },
        
        sb_width: null,
        global_events: false,
        resize_timeout: null,
        click_target: null,
        click_event: null,
        
        translate: function(tag, ops) {
            if (typeof ops.trans[tag] != "undefined")
                return ops.trans[tag]; else
                return tag;
        },
        validate_modules: function(modules) {
            var name, result = ["core"];
            for (var i = 0; i < modules.length; i++) {
                name = modules[i];
                if ($.datagrid.modules[name])
                    result.push(name);
            }
            return result;           
        },
        build_options: function(grid, ops) { 
            var result = $.extend({}, $.datagrid.def_options, ops);
            
            result.state_ops = $.datagrid.state_ops;
            result.layout = $.extend({}, $.datagrid.def_layout, ops.layout);
            
            ops.modules.unshift("core");
            
            $.extend(result.state, $.datagrid.state); 
            
            var module;
            for (var i in ops.modules) {
                module = ops.modules[i];
                
                result[module] = $.extend({}, $.datagrid.modules[module].def_options, ops[module]);
                $.extend(result.state, $.datagrid.modules[module].state);
                $.extend(result.state_ops, $.datagrid.modules[module].state_ops);
            } 
            
            $.extend(result.state, $.datagrid.load_state(grid, result));
            
            return result;
        },
        build_attr: function(attr) {
            var prop, res = "";
            if (typeof attr == "undefined") 
                return res;    
                
            for (prop in attr) 
                res+= " data-" + prop + "='" + attr[prop] + "'";
                
            return res;
        },
        build_header: function(ops) {
            var column,cls,align,pid,cell_html,part,min,max,content;
            var data = { html_left: '', html_main: '', html_right: ''};

            for (var i = 0; i < ops.model.length; i++) {
                ops.model[i] = $.extend({}, $.datagrid.def_column, ops.model[i]);
                
                column = ops.model[i];
                if (column.id == null)
                    column.id = ops.id + "_" + i;
                
                cls = "dg_cell dg_align_" + column.align + " " + column.cls;
                if (column.valign)
                    cls+= " dg_valign_" + column.valign;  
                   
                content = column.content;
                if (typeof content == "undefined") content = "";
                
                cell_html = "<div class='" + cls + "' data-row='-1' data-col='" + i + "'>";
                cell_html+= "<div class='dg_cell_data'>" + content + "</div>";
                cell_html+= "</div>";
                       
                part = $.datagrid.get_part(i, ops);
                
                data["html_" + part]+= cell_html;
            }
            
            if (ops.layout.fill == "spacer")
                data.html_main+= "<div class='dg_spacer' data-row='-1'><div class='dg_cell_data'></div></div>";
            
            var res = "";
            if (ops.layout.freeze[0])
                res = "<div class='dg_left dg_part'><div class='dg_rows'>" + data.html_left + "</div></div>";
            res+= "<div class='dg_main dg_part'><div class='dg_rows'>" + data.html_main + "</div></div>";
            if (ops.layout.freeze[1])
                res+= "<div class='dg_right dg_part'><div class='dg_rows'>" + data.html_right + "</div></div>";
            
            return res;
        },
        build_cell: function(ops, row, col_index) {
            var v,i;
            var column = ops.model[col_index];
                
            var cls = " dg_align_" + column.align + " " + column.cls;
            if (column.valign)
                cls+= " dg_valign_" + column.valign;
            
            var cell = ($.isArray(row.cells) && col_index < row.cells.length) ? row.cells[col_index] : "";
            if (typeof cell == "undefined" || cell === null)
                cell = {"content" : ""};
            if ($.isString(cell)) 
                cell = {"content" : cell}; 

            var attr = $.datagrid.build_attr(cell.attr);
            if (typeof (v = cell.cls) != "undefined")
                cls+= " " + v;   
            
            var content = (typeof cell.content != "undefined") ? cell.content : "";
            var value = (typeof (v = cell.value) != "undefined") ? v : content;   
            var style = (typeof (v = cell.style) != "undefined") ? "style='" + v + "'" : "";
            
            if (column.copy && content) {
                cls+= " dg_copyable";
                attr+= " title='Click to copy'";
            }
               
            var html = "<div class='dg_cell" + cls + "' data-col='" + col_index + "' " + attr + " " + style + ">"; 
            html+= "<div class='dg_cell_value'>" + value + "</div>";
            html+= "<div class='dg_cell_data' style='height: " + ops.row_height + "px'>" + content + "</div>";
            html+= "</div>";
            
            return html;
        },
        build_row: function(row, ops, level, last) {
            var res = {}, part, j, v;
            
            var cls = "dg_row";
            if (typeof (v = row.cls) != "undefined")
                cls+= " " + row.cls;
            
            if (row.rows_cnt) {
                if ($.isArray(row.rows) && row.rows.length)
                    cls+= " dg_loaded";
            }
            
            // if (row.loaded === false)
            //    cls+= " dg_pending";
            
            if (last)
                cls+= " dg_last";
            
            var attr = row.attr;
            if (typeof attr == "undefined") attr = "";
            
            res.left  = "<div class='" + cls + "' data-id='" + row.id + "' data-level='" + level + "' data-part='left' " + attr + ">";
            res.main  = "<div class='" + cls + "' data-id='" + row.id + "' data-level='" + level + "' data-part='main' " + attr + ">";
            res.right = "<div class='" + cls + "' data-id='" + row.id + "' data-level='" + level + "' data-part='right' " + attr + ">";
                
            for (j = 0; j < ops.model.length; j++) {
                part = $.datagrid.get_part(j, ops);  
                
                res[part]+= $.datagrid.build_cell(ops, row, j);
            }         
            
            if (ops.layout.fill == "spacer")                                 
                res.main+= "<div class='dg_spacer'><div class='dg_cell_data' style='height: " + ops.row_height + "px'></div></div>";
            
            res.left+= "</div>";
            res.main+= "</div>";
            res.right+= "</div>";    
            
            return res;
        },
        build_rows: function(rows, ops, data, level) {
            var start, end, v, row, cls, i, j, row_html, part;
            if (level == 0) {
                start = ops.state.start;
                end = ops.state.end;
                if (!end)
                    end = rows.length;
            } else {
                start = 0;
                end = rows.length;    
            }
            
            for (i = start; i < end; i++) {
                row = rows[i];
                
                ops.srows[data.row_index] = row;
                row_html = $.datagrid.build_row(row, ops, level, i == end-1);
                
                data.html_left+= row_html.left;
                data.html_right+= row_html.right;
                data.html_main+= row_html.main;
                
                data.row_index++;
                
                var sub_rows = row.rows;
                if (row.rows_cnt > 0) {
                    data.html_left+= "<div class='dg_subrows' data-part='left'>";
                    data.html_main+= "<div class='dg_subrows' data-part='main'>";
                    data.html_right+= "<div class='dg_subrows' data-part='right'>";
                    
                    if ($.isArray(sub_rows) && sub_rows.length)
                        $.datagrid.build_rows(sub_rows, ops, data, level+1);
                        
                    data.html_left+= "</div>";
                    data.html_main+= "</div>";
                    data.html_right+= "</div>";
                } 
            }    
        },  
        build_footer: function(grid, ops) {
            var v;
            var parts = {
                left: typeof (v = ops.footer.left) != "undefined" ? v.split(",") : [],
                right: typeof (v = ops.footer.right) != "undefined" ? v.split(",") : [],
                center: typeof (v = ops.footer.center) != "undefined" ? v.split(",") : [],
            }
            
            if (parts.left.length == 0 && parts.right.length == 0 && parts.center.length == 0)
                return null;
            
            var html = "";
            for (var part in parts) {
                html+= "<div class='dg_footer_" + part + "'>";
                for (var i in parts[part]) {
                    var id = parts[part][i].trim();
                    
                    if (id == "") continue;
                    
                    if (id == "|")
                        html+= "<div class='dg_footer_sep'></div>";    
                        
                    var elem = ops.footer.elements[id];
                    if (typeof elem == "undefined") continue;
                    
                    var enabled = typeof (v = elem.enabled) != "undefined" ? v : "";    
                    var visible = typeof (v = elem.visible) != "undefined" ? v : ""; 
                    var content = typeof (v = elem.content) != "undefined" ? v : "";   
                    html+= "<div class='dg_footer_elem' data-id='" + id + "' data-enabled='" + enabled + "' data-visible='" + visible + "'>" + content + "</div>";
                }
                html+= "</div>";
            } 
            
            return html;            
        },
        build_indices: function(grid, ops) {
            //var t = new Date().getTime();
            var cache = {
                rows: {},
                sub_rows: {},
                cells_by_col: {},
                cells_by_row: {}
            };
            var parts, part, rows, row, sub_row, cells, cell, col;
            var p,r,c;
            
            parts = $(".dg_body .dg_part", grid);
            for (p = 0; p < parts.length; p++) {
                part = $(parts[p]);    
                
                rows = $(".dg_row", part);
                for (r = 0; r < rows.length; r++) { 
                    row = rows[r];
                    
                    $(row).attr("data-row", r);
                    $(row).children(".dg_cell").attr("data-row", r);
                    
                    if (typeof cache.rows[r] == "undefined")
                        cache.rows[r] = [];
                    cache.rows[r].push(row);
                    
                    sub_row = $(row).next(".dg_subrows");
                    if (sub_row.length) {
                        sub_row.attr("data-parent", r);
                        if (typeof cache.sub_rows[r] == "undefined")
                            cache.sub_rows[r] = [];
                        cache.sub_rows[r].push(sub_row[0]);
                    } 
                    
                    cells = $(".dg_cell, .dg_spacer", row);
                    for (c = 0; c < cells.length; c++) {
                        cell = cells[c];                
                        col = cell.getAttribute('data-col');
                        row = cell.getAttribute('data-row');
                        
                        if (typeof cache.cells_by_col[col] == "undefined")
                            cache.cells_by_col[col] = []; 
                        cache.cells_by_col[col].push(cell);
                        
                        if (typeof cache.cells_by_row[row] == "undefined")
                            cache.cells_by_row[row] = []; 
                        cache.cells_by_row[row].push(cell);
                    }
                } 
            }   
            
            //console.log(new Date().getTime() - t);
            ops._cache = cache;
        },
        build: function(target, ops) {  
            ops.id = $(target).attr('id');
            var id = (ops.id) ? "id='" + ops.id + "'" : "";
            
            $.datagrid.validate(ops);
            
            var html = "<div class='datagrid " + ops.cls + "' " + id + " " + ops.attr + ">"; 
            html+=     "<div class='dg_header'>";
            html+=         "<div class='dg_cont'>";
            html+=         "</div>"; 
            html+=     "</div>"; 
            html+=     "<div class='dg_body'>";
            html+=         "<div class='dg_cont'>";
            html+=         "</div>"; 
            html+=     "</div>"; 
            html+=     "<div class='dg_scroller'><div></div></div>";
            html+=     "<div class='dg_footer'></div>";
            html+=     "<div class='dg_overlay'></div>";
            html+= "</div>";
            
            var grid = $(html);  
            var cls = target.attr("class");
            grid.addClass(cls);
            
            $(target).replaceWith(grid);  
            
            if (ops.style)
                grid.css(ops.style);
                
            if (typeof ops.beforeBuild == "function")
                ops.beforeBuild.apply(grid, [ops]);   
            
            $.datagrid.modules.call("config", grid, ops);     
            
            var header_html = $.datagrid.build_header(ops); 
            $(".dg_header .dg_cont", grid).html(header_html);

            if (typeof ops.footer.elements == "undefined")
                ops.footer.elements = {};
                
            ops.footer.elements["reload"] = {content: "<a href='#' class='dg_reload' onclick='return false'></a>", enabled: true};
            
            var footer_html = $.datagrid.build_footer(grid, ops);
            if (footer_html) 
                $(".dg_footer", grid).html(footer_html); else
                $(".dg_footer", grid).hide();
                      
            $.datagrid.get_footer_elem(grid, "reload").click(function() {
                var grid = $(this).parents(".datagrid:first").datagrid("reload"); 
            });
            
            
            $.datagrid.layout(grid, ops);
            
        //  EVENTS                          
            if (!$.datagrid.global_events) {
                $(window).resize(function() {
                    clearTimeout($.datagrid.resize_timeout);
                    $.datagrid.resize_timeout = setTimeout($.datagrid.resize, 200);
                });
                $.datagrid.global_events = true;
            }
            $(".dg_scroller", grid).bind("scroll", function(e) {
                var grid = $(this).parents(".datagrid:first");
                
                var sx = $(this).scrollLeft();
                var sw = $(this).width();
                var mw = $(".dg_body .dg_main", grid).width();
                
                var x = mw * sx / sw;
                $(".dg_main > div", grid).css("margin-left", -x + "px");        
            });            
            
            $.datagrid.modules.call("build", grid, ops);      
            
            grid.data("datagrid", ops);  
            $.datagrid.save_state(grid, ops);
            $.datagrid.overlay(grid, false);
            
            if (typeof ops.afterBuild == "function")
                ops.afterBuild.apply(grid, [ops]);
            
            return grid;    
        },
        
        scrollbar_width: function() {
            if ($.datagrid.sb_width == null) {
                var $inner = jQuery('<div style="width: 100%; height:200px;">test</div>'),
                    $outer = jQuery('<div style="width:200px;height:150px; position: absolute; top: 0; left: 0; visibility: hidden; overflow:hidden;"></div>').append($inner),
                    inner = $inner[0],
                    outer = $outer[0];
                 
                jQuery('body').append(outer);
                var width1 = inner.offsetWidth;
                $outer.css('overflow', 'scroll');
                var width2 = outer.clientWidth;
                $outer.remove();
         
                $.datagrid.sb_width = width1 - width2;
            }
            
            return $.datagrid.sb_width;
        },
        
        select_cells: function(col, row, grid) {
            var ops = grid.data("datagrid");
            var cache = ops._cache;
            if (typeof cache == 'undefined')
                return $();
                
            var res;
            if (col != null) {
                res = $(cache.cells_by_col[col]);    
                if (row != null)
                    res = $(res[row]);
            } else
            if (row != null) 
                res = $(cache.cells_by_row[row]); 
            
            if (typeof res == "undefined")
                res = $();       
            
            return res;
        },
        select_row: function(row, grid) {
            var ops = grid.data("datagrid");
            var cache = ops._cache;
            if (typeof cache == 'undefined')
                return $();    
            
            return $(cache.rows[row]);
        },
        get_attr: function(obj, name, def) {
            var r = obj.attr(name);
            if (typeof r == "undefined")
                return def; 
                
            return r;
        },
        get_part: function(col, ops) {
            if (col < ops.layout.freeze[0])
                return "left";
            if (col >= ops.model.length - ops.layout.freeze[1])
                return "right";
            
            return "main";
        },
        get_footer_elem: function(grid, id) {
            return $(".dg_footer .dg_footer_elem[data-id=" + id + "]", grid);
        },
        find_column: function(id, grid, ops) {
            for (var i in ops.model) {
                if (ops.model[i].id == id) 
                    return i;    
            }
            
            return false;
        },
        find_row: function(id, grid, ops) {
            var ops = grid.data("datagrid");
            var cache = ops._cache;
            
            if (!cache) return false;
            
            for (var i in cache.rows) {
                if ($(cache.rows[i]).attr("data-id") == id)
                    return i;
            }
            
            return false;
        },
        
        layout: function(grid, ops) {
            var inner = true;
            var h = ops.inner_height;
            if (h === null || h === false) {
                inner = false;
                h = ops.height;
            }
            
            if (h == "auto") 
                h = grid.height();
                
            if (h === null || h === false) return;
            
            if (!$.isArray(h))
                h = [h];
                
            if (!h.length) return;
                
            var fix_height = (h.length == 1);
            var min_height = parseInt(h[0]);
            var max_height = (h.length > 1) ? parseInt(h[1]) : 0;
            var hf_height = $(".dg_header", grid).outerHeight() + $(".dg_footer", grid).outerHeight() + $(".dg_scroller", grid).height();
            
            if (!inner) {
                min_height-= hf_height;
                max_height-= hf_height;
            }
            
            if (fix_height) 
                $(".dg_body", grid).css("height", min_height + "px"); else
            if (min_height)
                $(".dg_body", grid).css("min-height", min_height + "px"); else
            if (max_height) 
                $(".dg_body", grid).css("max-height", max_height + "px");
                
            $(".dg_body .dg_left", grid).css("min-height", min_height + "px");    
            $(".dg_body .dg_right", grid).css("min-height", min_height + "px");    
            $(".dg_body .dg_main", grid).css("min-height", min_height + "px");    
        },
        update: function(grid, ops) {
            $.datagrid.layout(grid, ops);       
            
            var header_width = $(".dg_header .dg_cont", grid).width();
            var body_width = $(".dg_body .dg_cont", grid).width();
            
        //  Vertical scroller margin 
        /*
            if (body_width < header_width) {
                $(".dg_header", grid).css("padding-right", header_width - body_width + 'px');
                $(".dg_scroller", grid).css("margin-right", header_width - body_width + 'px');
            }
             * 
         */
                                    
            var widths = { left: 0, right: 0, main: 0};
            var cell,column,cell_width,part,cw,mw,col_id;
            var cells = $(".dg_header .dg_cell", grid);
            
        //  Sync column widths    
            var maxw, minw;
            for (var i = 0; i < cells.length; i++) {
                cell = $(cells[i]);
                column = cell.attr("data-col");
                col_id = ops.model[column].id;   
                
                part = $.datagrid.get_part(column, ops);

                if (ops.model[column].fixed && ops.model[column].width != "content")
                    cell_width = ops.model[column].width; else
                    cell_width = ops.state.widths[col_id];
                
                if (typeof cell_width == "undefined") 
                    cell_width = ops.model[column].width;
                
                if (part == "main" && (cell_width == 'content' || cell_width == 'fit')) {
                    mw = cell.css("width", "auto").width(); 
                    
                    $.datagrid.select_cells(column, null, grid).each(function() {
                        cw = $(this).css("width", "auto").width();
                        
                        if (cw > mw) mw = cw;
                    });                    
                    
                    cell_width = mw;
                } else
                    cell_width = parseInt(cell_width);
                
                if (!cell_width) 
                    cell_width = 100;
                
                if (cell_width < ops.min_cell_width)
                    cell_width = ops.min_cell_width;
                
                maxw = ops.model[column].max_width;
                minw = ops.model[column].min_width;
                
                if (maxw !== null && cell_width > maxw) cell_width = maxw;
                if (minw !== null && cell_width < minw) cell_width = minw;
                
                cell.css("width", cell_width + "px");  
                $.datagrid.select_cells(column, null, grid).css("width", cell_width + "px");
                
                if (cell.is(":visible"))                
                    widths[part]+= cell_width;
            }   
            
        //  Fix parts width
            var wl = widths["left"];
            var wr = widths["right"];
            /*
            if (wl + wr + 100 > body_width) {
                grid.children(".dg_header").css("width", wl + wr + 100 + "px");
                grid.children(".dg_body").css("width", wl + wr + 100 + "px");
                grid.children(".dg_scroller").css("width", wl + wr + 100 + "px");
                grid.children(".dg_footer").css("width", wl + wr + 100 + "px");
            } else {
                grid.children(".dg_header").css("width", "auto");
                grid.children(".dg_body").css("width", "auto");
                grid.children(".dg_scroller").css("width", "auto");
                grid.children(".dg_footer").css("width", "auto");
            }
             * 
             */
            
            $(".dg_left", grid).css("width", wl + "px");
            $(".dg_right", grid).css("width", wr + "px");
            
            $(".dg_header .dg_main", grid).css({"margin-left" :  wl + "px", "margin-right" : wr + "px"});
            $(".dg_body .dg_main", grid).css({"margin-left" :  wl + "px", "margin-right" : wr + "px"});
            
            var main_cont_width = body_width - $(".dg_left", grid).outerWidth() - $(".dg_right", grid).outerWidth();

            if (ops.layout.fill == "spacer")
                widths.main+= 20;
                
            if (widths.main < main_cont_width) {       
                var ws = main_cont_width - widths.main;
                if (ops.layout.fill == 'cell') {
                    var c = ops.model.length - ops.layout.freeze[1] - 1;
                    var cells = $.datagrid.select_cells(c, null, grid);
                    
                    cells.css("width", "+=" + ws + "px");
                    $(".dg_header .dg_cell[data-col=" + c + "]", grid).css("width", "+=" + ws + "px");
                } else
                    $(".dg_body .dg_spacer", grid).css("width", (ws + 20) + "px"); 
                    
                widths.main = main_cont_width;                            
            } else 
            if (ops.layout.fill == 'spacer') {
                $(".dg_body .dg_spacer", grid).css("width", "20px");
            }
            
            var scroller = $(".dg_scroller", grid).show();
            var scroller_width = scroller.width() * widths.main / main_cont_width;
            
            if (scroller_width <= scroller.width()) {
                scroller.hide(); 
                $(".dg_main > div", grid).css("margin-left", 0);      
            } else {
                scroller.show();
                scroller.children("div").css({height: "3px", "margin-top": "-2px"});
            }
            
            $(".dg_scroller > div", grid).css("width", scroller_width + "px");      
            $(".dg_scroller", grid).scroll();

            $.datagrid.modules.call("update", grid, ops);
            $.datagrid.layout(grid, ops);       
        },
        resize: function() {
            $(".datagrid").each(function() {
                $.datagrid.update($(this), $(this).data("datagrid"));
            });
        },

        overlay: function(target, st) {
            if (target.is(".datagrid")) {
                if (st)
                    $(".dg_overlay", target).show(); else
                    $(".dg_overlay", target).hide(); 
            } else 
            if (target.is(".dg_cell")) {
                if (st) {
                    $(".dg_cell_data", target).hide();
                    target.addClass("dg_cell_overlay"); 
                } else {
                    $(".dg_cell_data", target).show();
                    target.removeClass("dg_cell_overlay");
                }
            }
        },
        save_state: function(grid, ops) {
            if (!ops.save_state) return;
            
            var st = {}, v;
            for (var prop in ops.state) {
                if (ops.state[prop] != null && typeof (v = ops.state_ops[prop]) != "undefined" && v.save)
                    st[prop] = ops.state[prop];
            }
            
            var cn = ops.cookie_name;
            if (!cn) {
                var id = grid.attr("id");
                if (typeof id == "undefined") return;
            
                cn = ops.cookie_prefix + id + "_datagrid_state"
            }
            
            $.cookie(cn, $.toJSON(st), { path: '/', expires: 7});
        },
        load_state: function(grid, ops) {
            var cn = ops.cookie_name;
            if (!cn) {
                var id = grid.attr("id");
                if (typeof id == "undefined") return;
            
                cn = ops.cookie_prefix + id + "_datagrid_state"
            }
            console.log(cn);
            
            var save = ops.save_state;
            if (typeof save == "undefined")
                save = $.datagrid.def_options.save_state;
                
            if (!save) return;
            
            var cookie = $.cookie(cn);
            if (!cookie) return;
            
            return $.evalJSON(cookie); 
        },
        
        validate: function(ops) {
            if (!$.isArray(ops.layout.freeze) || ops.layout.length == 0)
                ops.layout.freeze = [0,0];
            
            if (ops.layout.freeze.length == 1)
                ops.layout.freeze.push(0);
            
            var module,func;
            for (var i in ops.modules) {
                module = ops.modules[i];
                
                func = $.datagrid.modules[module][name];
                if (typeof func == "function")
                    func(ops);
            }            
        },
        serialize: function(grid, ops, serialize_grid) {
            var def_ops = {
                elements: "input, select, textarea",
            },
            ops = $.extend(def_ops, ops);
            
            var grid_ops = grid.data("datagrid");
            
            var data = {};
            var index = 0;
            
            var get_value = function(elem, ops) {
                var type = elem.attr("type");
                if (type == "hidden" && !ops.hidden)
                    return ""; 
                
                if (type == "checkbox")
                    return elem.is(":checked") ? 1 : 0;
                
                var val = elem.val();
                return (typeof val != "undefined") ? val : "";
            }
            
            var serialize_rows = function(rows, parent) {
                var i,row,row_data;
                for (i in rows) {
                    row_data = {};
                    row = rows[i];
                    
                    if (!row.id) continue;
                    $(".dg_row[data-row=" + index + "]", grid).each(function() {
                        $(ops.elements, this).each(function() {
                            var name = $(this).attr("name");
                        
                            if (name) {
                                row_data[name] = get_value($(this));
                                if (serialize_grid)
                                    $(this).remove();                                
                            }
                        });
                    });
                    if (!$.isEmptyObject(row_data)) {
                        if (typeof parent.rows == "undefined")
                            parent.rows = {};
                        parent.rows[row.id] = row_data;
                    }
                    
                    index++;
                                        
                    if ($.isArray(row.rows))
                        serialize_rows(row.rows, row_data);
                }                                    
            }
            
            serialize_rows(grid_ops.rows, data);
            
            if (serialize_grid) {
                var name = (typeof ops.name != "undefined") ? ops.name : grid.attr("id");
                var input = $("<input name='" + grid.attr("id") + "' type='hidden'/>").appendTo(grid);
                input.val($.toJSON(data));
            }
            
            return data;
        },                                    
        reload: function(grid, post, state) {
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
        },
        load_data: function(grid, ops, data) {
            var body, body_html;

            ops.row_count = data.length;
               
            if (typeof ops.beforeLoad == "function")
                ops.beforeLoad.apply(grid, [ops]);
               
            $.datagrid.modules.call("process", grid, ops, data);
            
            body = { html_left: '', html_main: '', html_right: '', row_index: 0};
            
            ops.srows = {};
            $.datagrid.build_rows(data, ops, body, 0);
            
            ops.state.count = body.row_index;
            
            var body_html = "";
            if (ops.layout.freeze[0])
                body_html = "<div class='dg_left dg_part'><div class='dg_rows'>" + body.html_left + "</div></div>";
                
            body_html+= "<div class='dg_main dg_part'><div class='dg_rows'>" + body.html_main + "</div></div>";
            if (ops.layout.freeze[1])
                body_html+= "<div class='dg_right dg_part'><div class='dg_rows'>" + body.html_right + "</div></div>";
            
            $(".dg_body .dg_cont", grid).html(body_html);
            
            $.datagrid.build_indices(grid, ops);
            ops.cell_height = $($.getValue(ops._cache.cells_by_col, [0,0], $())).height();
            
            $.datagrid.modules.call("finalize", grid, ops, [0, ops.state.count]);
            $.datagrid.update(grid, ops); 
            
            if (typeof ops.afterLoad == "function")
                ops.afterLoad.apply(grid, [ops]);
            
            if (typeof ops.onLoaded == "function")
                ops.onLoaded.apply(grid, [ops]);  
        },
        load_row: function(grid, row_ind, ops, callback, callback_data) {
            //$.datagrid.overlay(grid, true);    
            var row = $(ops._cache.rows[row_ind]).addClass("dg_loading");
            var sub_rows = $(ops._cache.sub_rows[row_ind]);
            
            var level = parseInt(row.attr("data-level"));
            var post = $.extend({root_id: row.attr("data-id")}, ops.param);
            $.post(ops.handler, post, function(r) {
                if (r === null) {
                    $.datagrid.overlay(grid, false);      
                    return;
                }
                
                $.datagrid.modules.call("process", grid, ops, r.rows);
                
                var data = { html_left: '', html_main: '', html_right: '', row_index: 0 };
                $.datagrid.build_rows(r.rows, ops, data, level+1);
                
                var sub_row, part;
                for (var i = 0; i < sub_rows.length; i++) {
                    sub_row = $(sub_rows[i]);
                    part = sub_row.attr("data-part");
                    
                    sub_row.html(data["html_" + part]);
                }
                row.removeClass("dg_pending dg_loading");
                $.datagrid.build_indices(grid, ops);
                
                var f = parseInt(sub_rows.children(".dg_row:first").attr("data-row"));
                var l = parseInt(sub_rows.children(".dg_row:last").attr("data-row")) + 1;
                
                $.datagrid.modules.call("finalize", grid, ops, [f,l]);
                $.datagrid.update(grid, ops); 
                
                if (typeof callback == "function")
                    callback(callback_data);
                    
                //$.datagrid.overlay(grid, false);      
            }, "json").fail(function() { 
                //$.datagrid.overlay(grid, false); 
                row.removeClass("dg_pending dg_loading");     
            });     
        },
        
        methods: {
            overlay: function(args) {
                var ok = (args.length > 0 && args[0] == "overlay");
                if (!ok) return false;
                
                var st = args.length > 1 ? args[1] : true;
                $.datagrid.overlay($(this), st); 
                
                return { retult: $(this) };
            },
            reload: function(args) {
                var ok = (args.length > 0 && args[0] == "reload");
                if (!ok) return false;

                $.datagrid.reload($(this), args[1], args[2]);     
                   
                return { result: $(this) };
            },
            get_cell: function(args) {
                var v,ok = (args.length >= 2 && args[0] == "get_cell");      
                if (!ok) return false;    

                if (!$(this).hasClass("datagrid")) return { result: $() }
                
                var ops = $(this).data("datagrid");
                var col = args[1];
                var row = args[2];
                if (typeof row == "undefined") row = null;
                
                if (col != null && col[0] == "@")
                    col = $.datagrid.find_column(col.replace("@", ""), $(this), ops);
                if (row != null && row[0] == "@")
                    row = $.datagrid.find_row(row.replace("@", ""), $(this), ops);
                
                var cell = $.datagrid.select_cells(col, row, $(this));
                return { result: cell };
            },
            update_cell: function(args) {
                var v,ok = (args.length > 3 && args[0] == "update_cell");      
                if (!ok) return false;
                
                var ops = $(this).data("datagrid");
                var col = args[1];
                var row = args[2];
                
                if (col[0] == "@")
                    col = $.datagrid.find_column(col.replace("@", ""), $(this), ops);
                if (row[0] == "@")
                    row = $.datagrid.find_row(row.replace("@", ""), $(this), ops);
                    
                if (col === false || row === false)
                    return { result: false };
                    
                var content = args[3];
                var value = $.value(args[4], content);
                
                var cell = $.datagrid.select_cells(col, row, $(this));
                cell.find(".dg_cell_data").html(content);
                cell.find(".dg_cell_value").html(value);
                
                var module,func;                
                for (var i in ops.modules) {
                    module = ops.modules[i];
                
                    func = $.datagrid.modules[module]["update_cell"];
                    if (typeof func == "function")
                        func(cell, col, row, ops);
                }                         
                
                return { result: cell };
            },
            edit_cell: function(args) {
                var v,ok = (args.length > 2 && args[0] == "edit_cell");      
                if (!ok) return false;
                
                var ops = $(this).data("datagrid");   
                var col = args[1];
                var row = args[2];
                
                if (col[0] == "@")
                    col = $.datagrid.find_column(col.replace("@", ""), $(this), ops);
                if (row[0] == "@")
                    row = $.datagrid.find_row(row.replace("@", ""), $(this), ops);
                    
                if (col === false || row === false)
                    return { result: false };
                    
                var cell = $.datagrid.select_cells(col, row, $(this));
                $.datagrid.modules.edit.trigger($(this), cell, ops);
                
                return { result: cell };
            },            
            update: function(args) {
                var ok = (args.length > 0 && args[0] == "update");                      
                if (!ok) return false;    
                
                if (!$(this).is(".datagrid")) 
                    return { result: $() };        
                
                var ops = $(this).data("datagrid");
                $.datagrid.update($(this), ops);  
                   
                return { result: $(this) };                
            },
            check_row: function(args) {
                var ok = (args.length > 1 && args[0] == "check_row"); 
                if (!ok) return false;      
                
                var ops = $(this).data("datagrid");
                var row = $.datagrid.select_row(args[1], this);
                var chk = row.find(".dg_check");
                
                if (!chk.length) return true;
                
                var st = (args.length > 2) ? args[2] : !chk.is(":checked");
                
                $.datagrid.modules.row_check.check(this, row.attr("data-id"), st, ops);                
                $.datagrid.modules.row_check.update_footer(this, ops); 
                
                if (st)
                    chk.attr("checked", 1); else
                    chk.removeAttr("checked");
                
                return true;  
            },
            cell_info: function(args) {
                var ok = (args.length > 0 && args[0] == "cell_info");      
                if (!ok) return false;
                
                var cell = $(this);
                if (!cell.is(".dg_cell"))
                    cell = cell.parents(".dg_cell:first");
                if (!cell.length)
                    return { result: null }
                    
                var grid = cell.parents(".datagrid:first");
                var ops = grid.data("datagrid");    
                var ci = parseInt(cell.attr("data-col"));                
                                        
                var r = {
                    col: ci,
                    row: parseInt(cell.attr("data-row")),
                    col_id: ops.model[ci].id,
                    row_id: cell.parents(".dg_row:first").attr('data-id')                    
                }
                
                return { result: r } 
            },
            checked: function(args) {
                var ok = (args.length > 0 && args[0] == "checked");                      
                if (!ok) return false;

                if (!$(this).is(".datagrid")) return false;
                
                var ops = $(this).data("datagrid");   
                return { result: ops.state.checked };
            },
            insert_row: function(args) {
                var ok = (args.length > 2 && args[0] == "insert_row"); 
                if (!ok) return false;      
                
                var ops = $(this).data("datagrid");
                var path = args[1];
                var row = args[2];

                $.datagrid.rows.insert(args[1], args[2], ops);
                return true;
            },
            serialize: function(args) {
                var ok = (args.length > 0 && args[0] == "serialize"); 
                if (!ok) return false;      
                
                var ops = {};
                var ops_ind = 1;
                var sg = (args.length > 1 && args[1] === true);
                if (sg) ops_ind++;
                    
                if (ops_ind < args.length)
                    ops = args[ops_ind];        
                    
                return { result: $.datagrid.serialize($(this), ops, sg) }    
            },            
            create: function(args) {
                var ok = (args.length == 1 || (args.length > 1 && args[0] == "create"));
                if (!ok) return false;

                $(this).removeClass(".datagrid");      
                var ops = $.datagrid.build_options($(this), (args.length > 1) ? args[1] : args[0]);
                
                var grid = $.datagrid.build($(this), ops);
                             
                if (ops.mode == "ajax" && ops.handler) {
                    $.datagrid.update(grid, ops);
                    $.datagrid.reload(grid); 
                } else
                    $.datagrid.load(grid, ops);
                   
                return { result: grid };
            },
        }
    }
    $.value = function(obj, def, ignore_null) {
        if (typeof obj == "undefined") 
            return def;
        
        if (ignore_null && obj === null)
            return def;
            
        return obj;
    }
    $.defined = function(obj) {
        return typeof obj != "undefined";    
    }
    $.undefined = function(obj) {
        return typeof obj == "undefined";    
    }     
    $.isArray = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object Array]');
    }
    $.isString = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object String]');
    }
    $.getValue = function(obj, path, def) {
        if (typeof obj == "undefined") return def;
        
        if ($.isString(path))
            path = path.split(".");
        
        var pi;
        for (var i = 0; i < path.length; i++) {
            pi = path[i];
            
            if (typeof obj[pi] == "undefined" || obj[pi] == null) return def;
            obj = obj[pi];     
        }
        
        return obj;
    }
    $.fn.multi_click = function(click, dblclick) {
        if (typeof click == "function")
            $(this).bind("single_click", click);
        
        if (typeof dblclick == "function")
            $(this).bind("double_click", dblclick);
        
        if (!$(this).data("mc_enabled")) {
            $(this).click(function(e) {
                var ct = $(this).data("mc_timeout");
                if (ct) 
                    return false;
                
                $.datagrid.click_event = e;
                $.datagrid.click_target = $(this);
                $(this).data("mc_timeout", setTimeout(function() {
                    var trg = $.datagrid.click_target;
                    trg.data("mc_timeout", null);
                    
                    trg.trigger("single_click", [$.datagrid.click_event]);
                }, 300));
            }).dblclick(function(e) {
                clearTimeout($(this).data("mc_timeout"));
                $(this).data("mc_timeout", null);            
                
                $(this).trigger("double_click", e); 
            });
            $(this).data("mc_enabled", true);
        }
    }
    $.fn.datagrid = function() {
        var res;
        for (var method in $.datagrid.methods) {
            res = $.datagrid.methods[method].apply(this, [arguments])
            if (typeof res != "undefined" && res !== false) 
                return res.result;
        }
    }
})( jQuery );        

