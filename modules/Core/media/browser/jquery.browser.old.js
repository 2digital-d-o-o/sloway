/*
    TODO:
    - select/deselect on click?
    - translate
    - keys
    - kaj se zgodi ce ni loaded?
    - destroy
    - upload filter?
    - na menu dat se copy/cut/paste
    - copy/paste se na tree
    - edit name only (no extension?) NUJNO!
*/
(function( $ ){   
	"use strict";

	$.browser = { 
		def_options: {
			handler: null,
            root_title: "Root",
            view: "large",
            save_state: true,
            cookie_name: null,
            onDialog: null,
            filter: null,
            update_interval: 500,
            update_max_items: 10, 
            cache_size: 100,
            select_filter: null,
            upload_filter: null,
            
            sizes: {
                large: 96,
                medium: 48,
                small: 16,
            }
        },
        extensions: {
            image: "bmp,gif,jpg,jpeg,png,tif,tiff",
            video: "avi,fla,flv,mov,mpeg,mpg,wmv",
            document: "pdf,txt,doc,xls,xslx",
        },
        selecting: false,
        scroll: null,
        global_events: false,
		modules: {
			call: function(name, grid, ops, args) {
				var module,func;                
				
				for (var i in ops.modules) {
					module = ops.modules[i];
				
					func = $.browser.modules[module][name];
					if (typeof func == "function")
						func(grid, ops, args);
				}              
			},    
		},
        check_rect: function(r1_x1,r1_y1,r1_x2,r1_y2, r2_x1,r2_y1,r2_x2,r2_y2) {
            return !(r2_x1 > r1_x2 || 
                     r2_x2 < r1_x1 || 
                     r2_y1 > r1_y2 ||
                     r2_y2 < r1_y1);
        },
        items_rect: function(list, x1,y1,x2,y2, filter) {
            var items = list.children(".bl_item");
            var ops = $.browser.options(list);
            
            if (!items.length) return [];
            
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
            
            var result = [];
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
                    add = $.browser.check_rect(ix1 + mx1, iy1 + my1, ix1 + w - mx2, iy1 + h - my2, x1,y1,x2,y2);
                    if (filter && !item.is(filter))
                        add = false;
                    
                    if (add) 
                        result.push(item);
                    
                    ind++;    
                    if (ind >= items.length) break;
                }
                
                ind+= skip;    
                if (ind >= items.length) break;
            }
            
            return result;
        },
        select_rect: function(list, x1,y1,x2,y2) {
            var ops = $.browser.options(list);
            var stat = { files: 0, folders: 0, uploads: 0 }
            
            list.children(".bl_selecting").removeClass("bl_selecting bl_selected");
            
            var item,items = $.browser.items_rect(list, x1,y1,x2,y2);
            for (var i = 0; i < items.length; i++) {
                item = items[i];
                if (item.hasClass("bl_file"))
                    stat.files++; else
                if (item.hasClass("bl_folder"))
                    stat.folders++; else
                if (item.hasClass("bl_upload"))
                    stat.uploads++;
                    
                item.addClass("bl_selected bl_selecting");
            }
            $.browser.menu_update(ops, stat);
        },
        visible_items: function(list, filter) {
            var x1 = list.scrollLeft();
            var y1 = list.scrollTop();
            var x2 = x1 + list.width();  
            var y2 = y1 + list.height();
            
            return $.browser.items_rect(list.children("ul"), x1,y1, x2,y2, filter);
        },
        options: function(elem) {
            if (!elem.hasClass("browser"))
                elem = elem.parents(".browser:first");
            
            return elem.data("browser");
        },
        rename: function(target, value) {
            var browser = target.parents(".browser:first");
            var ops = browser.data("browser");
            var old_path = target.attr("data-path");
            
            $.post(ops.handler, {
                rename: old_path,
                name: value,
            }, function(r) {
                if (!$.browser.response(browser, r)) return;
                if (r.error) return;
                
                var tree = ops._elem.tree;
                var list = ops._elem.list;
                
                var info = $.browser.path_info(r.path);
                var node = tree.find(".bt_item[data-path='" + old_path + "']");
                var item = list.find(".bl_item[data-path='" + old_path + "']");
                
                var attr = {
                    "data-path" : r.path,
                    "title" : info.file    
                }
                node.attr(attr).find(".bt_title").html(info.file);
                item.attr(attr).find(".bl_title").html(info.file);
            }, "json").fail(function() {
                $.browser.message(browser, "Cannot connect to server", true);
            });
        },
        create_folder: function(target, name) {
            var browser = target.parents(".browser:first");
            var ops = browser.data("browser");
                  
            $.browser.load(browser, { create_folder: target.attr("data-path") + name });   
        },
        confirm_delete: function(target) {
            var browser = target.parents(".browser:first");
            var ops = browser.data("browser");

            var paths = [];
            for (var i = 0; i < target.length; i++) {
                if ($(target).is(".bl_upload")) continue;
                
                paths.push($(target[i]).attr("data-path")); 
            }
            if (!paths.length) return;
            
            ops._action = {
                name: "delete",
                callback: $.browser.delete_file,
                param: [paths]
            }
            
            if (typeof ops.onConfirm == "function") 
                ops.onConfirm.apply(browser, ["delete", paths]); else
                $.browser.complete_action(confirm("Are you sure you want to delete " + paths.length + " files/folders"), browser);                
        },
        delete_file: function(paths) {
            var browser = $(this);
            var ops = browser.data("browser");
                
            $.browser.load(browser, { "delete" : paths });   
        },
        change_view: function(browser, view, ops) {   
            ops.state.view = view;
            $.browser.save_state(ops); 
            
            var views = browser.children(".browser_header").children(".browser_view");
            views.children("a").removeClass("bv_active");
            views.children("a[data-view=" + ops.state.view + "]").addClass("bv_active");
            
            $.browser.load(browser); 
        },
        path_info: function(path) {
            var s1 = path.split(".");
            var r = {};
            if (s1.length > 1)
                r.ext = s1.pop(); else
                r.ext = "";
            
            var s2 = s1.join(".").split("/");
            r.name = s2.pop();
            r.path = s2.join("/");
            if (r.path == "")
                r.path = "/";
            
            r.file = r.name;
            if (r.ext)
                r.file+= "." + r.ext;
            
            return r;                              
        },
        path_add: function(path, add) {
            if (path == "/")
                return "/" + add; else
                return path + "/" + add;
        },
        tree_build: function(browser, nodes, ops, indent) {
            if (typeof indent == "undefined")
                indent = 0;
                
            var margin,path,node,info,sub,cls;
            var html = "<ul>";
            for (var i = 0; i < nodes.length; i++) {
                node = nodes[i];
                
                if (node.type == "file") continue;
                
                info = $.browser.path_info(node.path);
                sub = $.isArray(node.files) && node.files.length > 0;
                
                if (sub) {
                    if (ops.state.expanded[node.path])
                        cls = " bt_expanded"; else
                        cls = " bt_collapsed";    
                } else  
                    cls = "";
                
                margin = indent * 20;
                
                html+= "<li class='bt_node' title='" + info.name + "'>";
                html+= "<div class='bt_item" + cls + "' data-path='" + node.path + "' data-level='" + indent + "'>";
                html+= "<span class='bt_state' style='margin-left: " + margin + "px'></span>";
                html+= "<span class='bt_icon'></span>";                
                html+= "<span class='bt_title'>" + info.name + "</span>";
                html+= "</div>";
                if (sub)                
                    html+= $.browser.tree_build(browser, node.files, ops, indent + 1);
                
                html+= "</li>";
            }   
            
            html+= "</ul>"; 
            
            return html;                              
        },
        tree_update: function(browser, ops) {
            var path = ops.state.curr_path;
            
            var tree = ops._elem.tree;
            var sel = tree.find(".bt_item[data-path='" + path + "']");
            
            tree.find(".bt_selected").removeClass("bt_selected");
            sel.addClass("bt_selected");
            
            var html = "<li data-path='" + path + "'>" + sel.find(".bt_title").html() + "</li>";
            
            var item, parent = sel.parent().parents(".bt_node:first");
            while (parent.length) {
                item = parent.children(".bt_item");
                item.addClass("bt_expanded").removeClass("bt_collapsed");
                
                html = "<li data-path='" + item.attr("data-path") + "'>" + item.find(".bt_title").html() + "</li>" + html;
                
                parent = parent.parent().parents("li:first");
            }    
            
            var addr = ops._elem.address;
            html = "<a href='#' class='browser_reload' onclick='return false'></a><ul>" + html + "</ul>";
            
            addr.html(html);
            addr.find("li").click(function() {
                var browser = $(this).parents(".browser");
                var ops = browser.data("browser");
                
                $.browser.change_dir(browser, $(this).attr("data-path"), ops);
            });
            addr.children(".browser_reload").click(function() {
                var browser = $(this).parents(".browser:first");
                var ops = browser.data("browser");
                
                $.browser.load(browser); 
            });
        },  
        tree_delete: function(item) {
            var browser = item.parents(".browser:first");
            var ops = browser.data("browser");
            var path = item.attr("data-path");
            
            if (ops.state.curr_path.indexOf(path) == 0) {
                ops.state.curr_path = $.browser.path_info(path).path;
                $.browser.save_state(ops);
            }
                
            $.browser.confirm_delete(item);
        },              
        tree_load: function(browser, nodes, ops) {
            var html = "<ul>";
            html+= "<li class='bt_node'>";
            html+= "<div class='bt_item bt_root' data-path='/'>";
            html+= "<span class='bt_icon'></span>";                
            html+= "<span class='bt_title'>" + ops.root_title + "</span>";
            html+= "</div>";
            
            html+= $.browser.tree_build(browser, nodes, ops);
            html+= "</li></ul>";
            var tree = ops._elem.tree;//browser.find(".browser_tree");
            
            tree.html(html);  
            
            var items = tree.find(".bt_item");   
            items.bind("contextmenu", function(e) {
                if ($(this).is(".bt_root")) {
                    var menu = [{
                        "name" : "create",
                        "content" : "New folder"
                    }];
                } else {
                    var menu = [{
                        "name" : "rename",
                        "content" : "Rename",
                    },{
                        "name" : "delete",
                        "content" : "Delete",             
                    },{
                        "name" : "create",
                        "content" : "New folder"
                    }];
                }
                
                $(this).contextmenu(e.clientX, e.clientY, menu, {
                    cls: "browser_cmenu",
                    zindex: ops._zindex + 1000,
                    onClick: function(target, name) {
                        if (name == "rename") 
                            $.browser.tree_rename(target); else
                        if (name == "create") 
                            $.browser.tree_create_folder(target); else 
                        if (name == "delete") 
                            $.browser.tree_delete(target);
                    }     
                });
                
                return false;
            }).click(function(e) {
                if ($(this).is(".bt_selected")) return;
                
                var tree = $(this).parents(".browser_tree");
                var browser = tree.parents(".browser");
                tree.find(".bt_selected").removeClass("bt_selected");
                
                $(this).addClass("bt_selected"); 
                
                $.browser.change_dir(browser, $(this).attr("data-path"), browser.data("browser"));
                e.stopPropagation();
            }).bind("dragover", function(e) {
                if ($.fn.fileupload) {
                    $(this).addClass("bt_targeted");
                    
                    var ops = $(this).parents(".browser").data("browser");
                    ops._elem.uploader.fileupload({dropZone: $(this)});
                }
                e.stopPropagation();
                e.preventDefault();
            }).bind("dragleave drop", function(e) {
                $(this).removeClass("bt_targeted");
                
                e.stopPropagation();            
                e.preventDefault();
            });
            items.find(".bt_state").click(function(e) {
                var item = $(this).parent();
                var browser = item.parents(".browser");
                var ops = browser.data("browser");
                
                if (item.is(".bt_collapsed")) {
                    item.removeClass("bt_collapsed").addClass("bt_expanded");
                    item.children("ul").show();    
                                                  
                    ops.state.expanded[item.attr("data-path")] = true;
                    $.browser.save_state(ops);
                    
                    e.stopPropagation(); 
                } else 
                if (item.is(".bt_expanded")) {
                    item.addClass("bt_collapsed").removeClass("bt_expanded");
                    item.children("ul").hide();     
                    
                    delete ops.state.expanded[item.attr("data-path")];
                    $.browser.save_state(ops);

                    e.stopPropagation(); 
                }
            });
        },     
        tree_rename: function(item) {
            var browser = item.parents(".browser:first");
            var ops = browser.data("browser");
            var tree = ops._elem.tree;//browser.find(".browser_tree");    
            
            var title = item.find(".bt_title");
            var ul = tree.children("ul");
            
            var edit = ul.find(".bt_edit");
            if (!edit.length)
                edit = $("<li class='bt_edit'><input type='text'></li>").appendTo(ul);
                
            var ul_ofs = ul.offset();
            var x = title.offset().left - ul_ofs.left;
            var y = item.offset().top - ul_ofs.top;
            
            browser.addClass("bs_renaming");
            edit.data("browser_target", item);
            edit.css({
                left: x - 4,
                top: y + 1,
            });
            
            var input = edit.children("input");
            input.val(title.html());
            input.bind("blur", function() {
                var edit = $(this).parent();
                var item = edit.data("browser_target");
                var value = $(this).val().trim();
                
                if (item.is(".bt_creating")) {
                    if (value == "") {
                        var node = item.parent();
                        var parent = node.parents(".bt_node:first");
                        
                        node.remove(); 
                        if (!parent.children("ul").children("li").length)
                            parent.children(".bt_item").removeClass("bt_expanded bt_collapsed");
                    } else
                        $.browser.create_folder(item, value);
                } else {
                    var title = item.find(".bt_title");
                    if (title.html() != value)
                        $.browser.rename(item, value);
                }
                edit.remove();
                
                 browser.removeClass("bs_renaming");
            }).bind("keydown", function(e) {
                if (e.which == 13) {
                    $(this).blur();                    
                    e.stopPropagation();    
                } else 
                if (e.which == 27) {
                    var edit = $(this).parent();
                    var item = edit.data("browser_target");
                    
                    $(this).val(item.find(".bt_title").html());
                    $(this).blur();
                    
                    e.stopPropagation();    
                }
            });
            
            input.focus();
        },
        tree_create_folder: function(item) {
            var browser = item.parents(".browser:first");
            var ops = browser.data("browser"); 
            var tree = ops._elem.tree;//browser.find(".browser_tree");    
            
            var path = item.attr("data-path") + "/";
            var level = parseInt(item.attr("data-level")) + 1;
            var margin = level * 20;
            
            var html = "<li class='bt_node'>";
            html+= "<div class='bt_item bt_creating' data-level='" + level + "' data-path='" + path + "'>";
            html+= "<span class='bt_state' style='margin-left: " + margin + "px'></span>";
            html+= "<span class='bt_icon'></span>";                
            html+= "<span class='bt_title'></span>";
            html+= "</div>";
            html+= "</li>";   
            
            item.addClass("bt_expanded");
            ops.state.expanded[item.attr("data-path")] = true;
            $.browser.save_state(ops);
            
            var node = item.parent();
            var ul = node.children("ul");
            if (!ul.length)
                ul = $("<ul/>").appendTo(node);
                
            var sub = $(html).prependTo(ul);
            $.browser.tree_rename(sub.children(".bt_item"));
        },
        list_build_item: function(node, upload, ops) {
            var path,cls;
            var info = $.browser.path_info(node.path);
            
            if (upload)
                cls = "bl_upload"; else
            if (node.type == 'dir')
                cls = "bl_folder"; else
                cls = "bl_file bl_pending";
                
            cls+= " bl_" + ops.state.view;
                
            var style = "";
            var cached = ops._cache[node.path];
            if (!cached) cached = {};
            
            if ($.browser.extensions.image.indexOf(info.ext.toLowerCase()) != -1)
                cls+= " bl_picture";
            
            if (cached["image_" + ops.state.view]) {
                style = "style=\"background-image: url('" + cached["image_" + ops.state.view] + "')\""; 
                cls+= " bl_border";
            }
            if (cached.corrupt)
                cls+= " bl_corrupt";
            
            var html = "<li class='bl_item " + cls + "' data-path='" + node.path + "' title='" + info.file + "'>";
            html+= "<div class='bl_image' " + style + "></div>";
            html+= "<div class='bl_main'>";
            html+= "<div class='bl_title'>" + info.file + "</div>";
            if (upload) 
                html+= "<div class='bl_progress'><div style='width: " + parseInt(upload.progress * 100) + "%'></div></div>";
                
            html+= "<div class='bl_type bl_info'>";
            if (cached.type == 'dir')
                html+= "directory"; else
            if (cached.info) 
                html+= cached.info;
            html+= "</div>";                
                
            html+= "<div class='bl_imgsize bl_info'>";
            if (cached.width && cached.height)
                html+= cached.width + " x " + cached.height;
            html+= "</div>";
            
            html+= "<div class='bl_time bl_info'>";
            if (cached.time)
                html+= cached.time;
            html+= "</div>";
            html+= "</div>";
            html+= "</li>";                
            
            return html;
        },
        list_build: function(browser, nodes, ops) {
            var html = "<ul>";    
            
            for (var i = 0; i < nodes.length; i++) 
                html+= $.browser.list_build_item(nodes[i], null, ops);
            
            var file;
            for (var path in ops._uploads) {
                file = ops._uploads[path];
                
                if (file.path == ops.state.curr_path)                 
                    html+= $.browser.list_build_item({"path" : path}, file, ops); 
            }
            
            html+= "<li class='bl_selector'></li>";
            html+= "</ul>";
            
            return html;
        },  
        list_init_item: function(item) {
            item.bind("contextmenu", function(e) {
                var ops = $.browser.options($(this));
                
                if (!$(this).is(".bl_selected")) 
                    $(this).parents("ul:first").find(".bl_selected").removeClass("bl_selected bl_last");
                                
                $(this).addClass("bl_selected bl_last");
                $.browser.menu_update(ops);
            }).click(function(e) {
                var ops = $.browser.options($(this));
                var ul = ops._elem.list.children("ul");
                
                if (!e.shiftKey)
                    ul.children(".bl_last").removeClass("bl_last");
                    
                if (e.ctrlKey) {              
                    if ($(this).is(".bl_selected")) 
                        $(this).removeClass("bl_selected"); else
                        $(this).addClass("bl_selected bl_last"); 
                } else 
                if (e.shiftKey) {                                                     
                    ul.children(".bl_selected").removeClass("bl_selected");
                    
                    var last = ul.children(".bl_last");     
                    var last_ind = last.index();
                    var curr = $(this).index();
                    
                    if (last_ind != -1) { 
                        if (last_ind < curr)
                            $(this).prevUntil(".bl_last").addClass("bl_selected"); else
                            $(this).nextUntil(".bl_last").addClass("bl_selected");
                        
                        last.addClass("bl_selected");
                    } else
                        $(this).addClass("bl_last");
                    
                    $(this).addClass("bl_selected");
                } else {
                    //var sel = $(this).is(".bl_selected");  
                    ul.children(".bl_selected").removeClass("bl_selected");
                    //if (!sel)
                        $(this).addClass("bl_selected bl_last");
                }
                $.browser.menu_update(ops);
                
                e.stopPropagation();
            }).dblclick(function(e) {
                if (!$(this).is(".bl_folder")) return;
                
                var path = $(this).attr("data-path");
                var browser = $(this).parents(".browser:first");
                
                $.browser.change_dir(browser, path, browser.data("browser"));
            });            
            
            if (item.is(".bl_folder")) {            
                item.bind("dragover", function(e) {
                    if (!$(this).is(".bl_folder")) return;
                    
                    $(this).addClass("bl_targeted");
                    if ($.fn.fileupload) {
                        var ops = $(this).parents(".browser").data("browser");
                        ops._elem.uploader.fileupload({dropZone: $(this)});
                    }
                    
                    e.stopPropagation();
                    e.preventDefault();
                }).bind("dragleave drop", function(e) {
                    if (!$(this).is(".bl_folder")) return;
                    
                    $(this).removeClass("bl_targeted");
                    
                    e.stopPropagation();            
                    e.preventDefault();
                });
            }
        },
        list_init: function(browser, ops) {
            var list = ops._elem.list;
            list.bind("contextmenu", function(e) {
                var menu = [{
                    name : "create",
                    content : "New folder",
                }];
                if ($.browser.paste_valid(ops.state.curr_path, ops))
                    menu.push({ name : "paste", content : "Paste" });
                
                var sel = $(this).children("ul").children(".bl_selected");
                
                if (sel.filter(".bl_file, .bl_folder").length) {
                    menu.push({ separator: true });
                    
                    if (sel.filter(".bl_file:not(.bl_pending)").length)
                        menu.push({ name : "view", content : "View" });
                    
                    menu.push({ name : "rename", content : "Rename" });
                    menu.push({ name : "delete", content : "Delete" });
                    menu.push({ name : "copy", content : "Copy" });
                    menu.push({ name : "cut", content : "Cut" });
                }
                if (sel.filter(".bl_upload").length) {
                    menu.push({ separator: true });
                    menu.push({ name : "cancel", content : "Cancel upload" });
                }
                    
                $(this).contextmenu(e.clientX, e.clientY, menu, {
                    cls: "browser_cmenu",
                    zindex: ops._zindex + 1000,
                    onClick: function(target, name) {
                        var sel = target.children("ul").children(".bl_selected");
                        if (name == "create") 
                            $.browser.list_create_folder(target); else
                        if (name == "rename") 
                            $.browser.list_rename(sel.filter(":not(.bl_upload):first")); else
                        if (name == "delete")
                            $.browser.confirm_delete(sel.filter(":not(.bl_upload)")); else
                        if (name == "cancel")
                            $.browser.uploader_cancel(sel.filter(".bl_upload")); else
                        if (name == "view")        
                            $.browser.view_file(sel.filter(":not(.bl_upload):first"));
                        if (name == "copy")
                            $.browser.list_queue(list, sel.filter(":not(.bl_upload)"), "copy"); else
                        if (name == "cut")
                            $.browser.list_queue(list, sel.filter(":not(.bl_upload)"), "move"); else
                        if (name == "paste")
                            $.browser.list_queue_exec(list); 
                    },
                });
                
                return false;
            }).bind("dragover", function(e) {
                if ($.fn.fileupload) {
                    var ops = $(this).parents(".browser").data("browser");
                    ops._elem.uploader.fileupload({dropZone: $(this)});
                }
            }).mouseup(function(e) {  
                if ($.browser.selecting_moved || e.shiftKey || e.ctrlKey || e.which == 3) return;
                
                var ops = $.browser.options($(this));                
                var ul = $(this).children("ul");
                ul.children(".bl_last, .bl_selected").removeClass("bl_last bl_selected");
                
                $.browser.menu_update(ops);
            }).bind("scroll", function() {
                var ops = $.browser.options($(this)); 
                
                clearTimeout(ops._update_timeout);
                ops._update_timeout = setTimeout(function() {
                    $.browser.check_layout(browser, ops);
                }, ops.update_interval);                
            });
        },
        list_queue: function(list, nodes, mode) {
            var ops = $.browser.options(list);
                        
            var node, paths = [];
            for (var i = 0; i < nodes.length; i++) {
                node = $(nodes[i]);
                if (mode == "move")
                    node.addClass("bl_cut");
                paths.push(node.attr("data-path"));
            }
            
            ops._queue = paths;
            ops._queue_mode = mode;
        },
        list_queue_exec: function(list) {
            var browser = list.parents(".browser:first");
            var ops = browser.data("browser");
            
            if (!ops._queue.length) return;
            
            $.browser.overlay_show(browser, true);
            var post = {
                filter: $.isObject(ops.filter) ? Object.keys(ops.filter) : null,
                load : ops.state.curr_path,
                image_size: ops.sizes[ops.state.view]
            }
            
            post[ops._queue_mode] = ops._queue;
            $.browser.load(browser, post, function(browser, ops, r) {
                $.browser.select(browser, r.files, ops);
                
                ops._queue_mode = null;
                ops._queue = [];
            });
        },
        list_load: function(browser, nodes, ops) {
            clearTimeout(ops._update_timeout);
            
            var list = ops._elem.list;
            var html = $.browser.list_build(browser, nodes, ops);
            
            list.html(html);
            list.find(".bl_item").each(function() {
                $.browser.list_init_item($(this));                
            });
            
            list.children("ul").mousedown(function(e) {
                if (e.which == 3) return;
                                    
                var list = $(this).parent();
                var browser = list.parents(".browser:first");
                if (browser.is(".bs_renaming")) return;
                
                $.browser.selecting = list;
                var ul = $(this);
                
                var sel = ul.children(".bl_selector");
                var ofs = ul.offset();
                
                var x = e.pageX - ofs.left;
                var y = e.pageY - ofs.top;
                
                sel.attr("data-orig-x", x).attr("data-orig-y", y);
                sel.css({width: 0, height: 0});
            });
            
            if (ops._update_xhr)
                ops._update_xhr.abort();
                
            ops._layout = {
                width: ops._elem.list.width(),
                height: ops._elem.list.height(),
                scroll_top: ops._elem.list.scrollTop(),
                scroll_left: ops._elem.list.scrollLeft()
            }
            ops._update_timeout = setTimeout(function() {
                $.browser.check_layout(browser, ops);                            
            }, ops.update_interval);                 
                
            $.browser.menu_update(ops);
            $.browser.list_update(browser, ops);            
        },
        list_rename: function(node) {
            var browser = node.parents(".browser:first");
            var ops = browser.data("browser");
            
            var list = ops._elem.list;
            
            var title = node.find(".bl_title");
            title.hide();
            
            browser.addClass("bs_renaming");
            
            var value = title.html();
            var edit = $("<div class='bl_edit'><input type='text' value='" + value + "'></div>").insertAfter(title);
            var input = edit.children("input");
            
            input.focus().select();
            input.blur(function() {
                var item = $(this).parents(".bl_item:first");
                var title = item.find(".bl_title");
                var value = $(this).val().trim();
                
                if (item.is(".bl_creating")) {
                    if (value == "") 
                        item.remove(); else
                        $.browser.create_folder(item, value);
                } else
                if (title.html() != value)
                    $.browser.rename(item, value);
                
                item.find(".bl_edit").remove();
                item.find(".bl_title").show();
                
                 browser.removeClass("bs_renaming");
            }).bind("keydown", function(e) {
                if (e.which == 13) {
                    $(this).blur();                    
                    e.stopPropagation();    
                } else 
                if (e.which == 27) {
                    var item = $(this).parents(".bl_item:first");
                                        
                    $(this).val(item.find(".bl_title").html());
                    $(this).blur();
                    
                    e.stopPropagation();    
                }
            });                                       
        },
        cache_details: function(path, details, ops) {
            details._time = new Date().getTime();
            details["image_" + ops.state.view] = details.image;
                        
            var c = ops._cache[path];
            if (typeof c == "undefined") 
                ops._cache_size++;
                
            ops._cache[path] = $.extend(ops._cache[path], details);
                
            if (ops._cache_size > ops.cache_size) {
                var min = 0;
                var ind = 0;
                for (var pth in ops._cache) {
                    if (min == 0 || ops._cache[pth].time < min)
                        ind = pth;                        
                }
                
                delete ops._cache[pth];
            }
        },
        list_update: function(browser, ops) {
            var list = ops._elem.list;
            var li,paths = [];
            var items = $.browser.visible_items(list, ".bl_pending");
            
            list = list.children("ul");
            
            for (var i = 0; i < Math.min(ops.update_max_items, items.length); i++) {
                li = $(items[i]);
                
                paths.push(li.attr("data-path"));    
                
                li.removeClass("bl_pending");
            }
            
            if (!paths.length) return;
            
            ops._update_xhr = $.post(ops.handler, {
                load_details: paths,
                image_size: ops.sizes[ops.state.view]
            }, function(res) {
                ops._update_xhr = null;
                
                if (!res || !res.status) return;
                
                var li,d;
                for (var path in res.details) {
                    d = res.details[path];
                    
                    $.browser.cache_details(path, d, ops);
                    
                    li = list.children("li[data-path='" + path + "']");
                    
                    if (d.url)
                        li.attr("data-url", d.url);
                    
                    if (d.corrupt)
                        li.addClass("bl_corrupt");
                        
                    if (d.image) {
                        li.children(".bl_image").css("background-image", "url('" + d.image + "')");
                        li.addClass("bl_border");
                    }
                    
                    if (d.info)
                        li.find(".bl_type").html(d.info);
                    
                    if (d.width && d.height)
                        li.find(".bl_imgsize").html(d.width + " x " + d.height);
                    
                    if (d.time)
                        li.find(".bl_time").html(d.time);
                } 
            }, "json").fail(function() {
                ops._update_xhr = null;  
            });
            
            ops._update_force = items.length > ops.update_max_items;
        },
        list_create_folder: function(list) {
            var browser = list.parents(".browser:first");
            var ops = browser.data("browser"); 
            
            var path = ops.state.curr_path + "/";
            $.browser.save_state(ops);
            
            var html = "<li class='bl_item bl_folder bl_creating bl_" + ops.state.view + "' data-path='" + path + "'>";
            html+= "<div class='bl_image'></div>";
            html+= "<div class='bl_title'></div>";
            html+= "</li>";  
                
            var ul = list.children("ul");
            var sub = $(html).appendTo(ul);
            
            list[0].scrollTop = list[0].scrollHeight;

            $.browser.list_rename(sub);
        },  
        view_file: function(node) {
            var url = node.attr("data-url");
            if (url)
                window.open(url, "_blank");                
        },
        is_parent: function(path, from) {
            path = path.split("/");
            from = from.split("/");
            
            if (path.length > from.length) return false;
            for (var i = 0; i < path.length; i++) {
                if (path[i] != from[i]) return false;                                
            }
            
            return true;
        },
        paste_valid: function(target, ops) {
            if (!ops._queue.length) return false;   
                
            for (var i = 0; i < ops._queue.length; i++) 
                if ($.browser.is_parent(ops._queue[i], target)) return false;
            
            return true;
        },
        uploader_cancel: function(target) {
            var browser = target.parents(".browser:first");
            var ops = browser.data("browser");
                
            var upload, path;
            for (var i = 0; i < target.length; i++) {
                path = $(target[i]).attr("data-path");
                upload = ops._uploads[path];
                
                if (typeof upload == "undefined") continue;
                
                upload.cancelled = true;
                upload.xhr.abort();
            }
        },
        uploader_done: function(uploader, data) {
            var browser = uploader.parents(".browser:first");
            var ops = browser.data("browser");
            
            var file = data.result.file;
            var info = $.browser.path_info(file.path);
            
            delete ops._uploads[file.path];
            
            if (info.path == "") info.path = "/";
            if (info.path == ops.state.curr_path) {
                var ul = ops._elem.list.children("ul");                            
                
                var item = $($.browser.list_build_item(file, null, ops));
                var old_item = ul.find(".bl_item[data-path='" + file.path + "']");
                if (!old_item.length) 
                    item.appendTo(ops._elem.list.children("ul")); else
                    old_item.replaceWith(item); 
                
                $.browser.list_init_item(item);
            } 
            
            ops._elem.uploads.find("li[data-path='" + file.path + "']").remove();
            
            $.browser.list_update(browser, ops);
        },
        uploader_add: function(data, ops) {
            var fpath, file, dropped = 0;
            for (var i = 0; i < data.files.length; i++) {
                file = data.files[i];
                if (!file.size) continue;
                
                fpath = $.browser.path_add(ops._upload_path, file.name);
                ops._uploads[fpath] = {
                    path: ops._upload_path,
                    progress: 0,
                    name: file.name
                }
                dropped++;
            }    
            
            return dropped;
        },
        uploader_fail: function(uploader, data) {
            var browser = uploader.parents(".browser:first");
            var ops = browser.data("browser");
            
            var path = data.formData.upload;  
            
            delete ops._uploads[path];
            
            ops._elem.list.find(".bl_upload[data-path='" + path + "']").remove();   
            ops._elem.uploads.find("li[data-path='" + path + "']").remove();     
        },
        uploader_progress: function(uploader, data) {
            var browser = uploader.parents(".browser:first");
            var ops = browser.data("browser");
                    
            var path = data.formData.upload;  
            var prog = data.loaded / data.total;
            
            ops._uploads[path].progress = prog;

            ops._elem.list.find(".bl_upload[data-path='" + path + "'] .bl_progress > div").css("width", parseInt(prog*100) + "%");
            ops._elem.uploads.find("li[data-path='" + path + "'] .bu_progress > div").css("width", parseInt(prog*100) + "%");
        },
        uploader_init: function(browser, ops) {   
            if (!$.fn.fileupload) return;      
            
            ops._elem.uploader.fileupload({
                dataType: 'json',
                url: ops.handler,  
                dropZones: $(),                              
                drop: function(e, data) {
                    var browser = $(this).parents(".browser:first");
                    var target = $(e.currentTarget);
                    var ops = browser.data("browser");
                    
                    var path;
                    if (target.is(".bl_folder") || target.is(".bt_item")) {
                        path = target.attr("data-path"); 
                        $.browser.change_dir(browser, path, ops);
                    } else 
                        path = ops.state.curr_path;
                    
                    ops._upload_path = path;
                    
                    if ($.browser.uploader_add(data, ops))
                        $.browser.change_dir(browser, ops.state.curr_path, ops); 
                },
                add: function(e, data) {
                    var browser = $(this).parents(".browser:first");
                    var target = $(e.currentTarget);
                    var ops = browser.data("browser");
                    
                    var file = data.files[0];
                    if (!file.size) return;
                    
                    var path;
                    if (target.is("input")) {
                        path = $.browser.path_add(ops.state.curr_path, file.name); 
                        $.browser.uploader_add(data, ops); 
                    } else
                        path = $.browser.path_add(ops._upload_path, file.name);
                        
                    data.formData = { 
                        upload: path,
                        image_size: ops.sizes[ops.state.view]
                    }

                    var html = "<li data-path='" + data.formData.upload + "'>";
                    html+= "    <div class='bu_title'>" + file.name + "</div>";
                    html+= "    <div class='bu_progress'><div></div></div>";
                    html+= "    <a class='bu_cancel' href='#' onclick='return false'></a>";
                    html+= "</li>";
                    
                    var upload = $(html).appendTo(ops._elem.uploads.children("ul"));
                    upload.find(".bu_cancel").click(function() {
                        $.browser.uploader_cancel($(this).parents("li:first")); 
                        
                        return false;        
                    });                    
                    var xhr = data.submit();
                    
                    ops._uploads[path].xhr = xhr;     
                },
                progress: function(e, data) {   
                    $.browser.uploader_progress($(this), data);
                },
                done: function(e, data) {   
                    $.browser.uploader_done($(this), data); 
                },
                fail: function (e, data) {   
                    $.browser.uploader_fail($(this), data);     
                },
            });              
        },
        overlay_show: function(browser, loader) {
            var ov = browser.children(".browser_overlay").show();
            if (loader)
                ov.addClass("bo_loader"); else
                ov.removeClass("bo_loader");
        },
        overlay_hide: function(browser) {
            browser.children(".browser_overlay").hide();  
        },
        menu_build: function(browser, ops) {
            var html = "<ul>";
            html+= "<li class='bm_item bm_create'><div class='bm_icon'></div><div class='bm_title'>Create folder</div></li>";
            html+= "<li class='bm_item bm_upload'>";
            html+= "    <div class='bm_icon'></div>";
            html+= "    <div class='bm_title'>Upload</div>";
            html+= "    <input type='file' multiple name='files[]'>";
            html+= "</li>";
            html+= "<li class='bm_item bm_rename'><div class='bm_icon'></div><div class='bm_title'>Rename</div></li>";
            html+= "<li class='bm_item bm_delete'><div class='bm_icon'></div><div class='bm_title'>Delete</div></li>";
            html+= "</ul>";    
            
            var menu = ops._elem.menu.html(html);
            var ul = menu.children("ul");
            
            ul.children("li.bm_rename").click(function() {
                var ops = $.browser.options($(this));
                
                var items = ops._elem.list.children("ul").children(".bl_selected");
                var item = items.filter(".bl_last");
                
                if (!item.length)
                    item = items.get(0);
                    
                $.browser.list_rename(item);
            });
            ul.children("li.bm_delete").click(function() {
                var ops = $.browser.options($(this));
                
                var items = ops._elem.list.children("ul").children(".bl_selected:not(.bl_upload)");
                $.browser.confirm_delete(items);
            });
            ul.children("li.bm_create").click(function() {
                var ops = $.browser.options($(this));
                
                $.browser.list_create_folder(ops._elem.list);                
            });
        },
        menu_update: function(ops, stat) {
            var menu = ops._elem.menu;
            var items = ops._elem.list.children("ul").children("li.bl_selected");
            
            var item;
            
            if (!stat) {
                var stat = {
                    files: 0,
                    folders: 0,
                    uploads: 0
                }
                if (stat !== false) 
                    for (var i = 0; i < items.length; i++) {
                        item = $(items[i]);
                        if (item.hasClass("bl_file")) 
                            stat.files++; else
                        if (item.hasClass("bl_folder")) 
                            stat.folders++; else
                        if (item.hasClass("bl_upload")) 
                            stat.uploads++; 
                    }
            }
            if (stat.files || stat.folders)                    
                menu.find(".bm_delete, .bm_rename").show(); else
                menu.find(".bm_delete, .bm_rename").hide(); 
        },
        build: function(browser, ops) {
            browser.addClass("browser");
            
            var html = "";
            html = "<div class='browser_header'>";
            html+= "    <div class='browser_view'>";
            html+= "        <a href='#' class='bv_large' data-view='large' onclick='return false'></a>";
            html+= "        <a href='#' class='bv_medium' data-view='medium' onclick='return false'></a>";
            html+= "        <a href='#' class='bv_small' data-view='small' onclick='return false'></a>";
            html+= "    </div>";
            html+= "    <div class='browser_address'></div>";
            html+= "</div>";
            html+= "<div class='browser_left'>";
            html+= "    <div class='browser_tree'></div>";
            html+= "    <div class='browser_menu'></div>";
            html+= "    <div class='browser_uploads'><ul></ul></div>";
            html+= "</div>";
            html+= "<div class='browser_main'>";
            html+= "    <div class='browser_list'></div>";
            html+= "</div>";
            html+= "<div class='browser_footer'></div>";
            html+= "<div class='browser_overlay'></div>";
            
            browser.html(html);
            browser.data("browser", ops);
            
            return browser;
        },
        set_selector: function(list, mx, my, clear) {    
            var ul = list.children("ul");
            var sel = ul.children(".bl_selector");
            var ofs = ul.offset();                          
            
            if (clear)
                ul.find(".bl_selected").removeClass("bl_selected");
            
            var x = mx - ofs.left;
            var y = my - ofs.top;                                    
            
            var ox = parseInt(sel.attr("data-orig-x"));
            var oy = parseInt(sel.attr("data-orig-y"));
            
            if (x < ox) { var t = x; x = ox; ox = t }
            if (y < oy) { var t = y; y = oy; oy = t }
            
            sel.show().css({
                left: parseInt(ox),
                top: parseInt(oy),
                width: parseInt(x - ox - 2) + "px",
                height: parseInt(y - oy - 2) + "px"
            });
            
            $.browser.select_rect(ul, ox,oy, x,y);      
        },
        get_selected: function(browser) {
            var ops = browser.data("browser");
            var res = [];
            var itm,sel = ops._elem.list.find(".bl_selected");
            var ext, valid;
            for (var i = 0; i < sel.length; i++) {
                itm = $(sel[i]);
                
                valid = false;
                if (itm.hasClass("bl_file")) {
                    if (ops.select_filter) 
                        valid = ops.select_filter[$.browser.path_info(itm.attr("data-path")).ext]; else
                        valid = true;
                } else
                if (itm.hasClass("bl_folder")) 
                    valid = (ops.select_filter && ops.select_filter["<dir>"]); else
                if (itm.hasClass("bl_corrupt")) 
                    valid = false; 
                    
                if (!valid) continue;
                    
                res.push(itm.attr("data-path"));
            }
            
            return res;
        },
        scroll_callback: function() {
            var list = $.browser.selecting;
            var scroll = $.browser.scroll;
                                    
            list[0].scrollTop+= 10 * $.browser.scroll.direction;
            $.browser.set_selector($.browser.selecting, scroll.x, scroll.y, scroll.clear);
            
            $.browser.scroll.timeout = setTimeout($.browser.scroll_callback, 20);
        },
        scroll_stop: function() {
            if ($.browser.scroll)
                clearTimeout($.browser.scroll.timeout);    
                            
            $.browser.scroll = null;    
        },
        load_state: function(ops) {
            if (!ops.save_state) return;
            
            var st = $.evalJSON($.cookie(ops.cookie_name));                  
            $.extend(ops.state, st);
        },
        save_state: function(ops) {
            if (ops.save_state) 
                $.cookie(ops.cookie_name, $.toJSON(ops.state), { path: '/', expires: 7});
        },
        complete_action: function(state, browser) {
            var ops = browser.data("browser");
            if (state) 
                ops._action.callback.apply(browser, ops._action.param);
            
            ops._action = null;   
        },
        array_to_object: function(arr) {
            var r = {};
            for (var i = 0; i < arr.length; i++)  
                r[arr[i]] = true;
            
            return r;
        },
        parse_filter: function(flt) {
            if (flt == null) return null;
            
            var r = {};
            var j,p,s = flt.split(",");
            var ext_img = $.browser.array_to_object($.browser.extensions.image.split(","));
            var ext_vid = $.browser.array_to_object($.browser.extensions.video.split(","));
            var ext_doc = $.browser.array_to_object($.browser.extensions.document.split(","));
            
            var pos = [];
            var neg = [];
            for (var i = 0; i < s.length; i++) {
                p = s[i];
                if (!p.length) continue;
                
                if (p[0] == "!")
                    neg.push(p.substring(1)); else
                    pos.push(p);
            }
            
            for (i = 0; i < pos.length; i++) {
                p = pos[i];
                if (p == "<img>")
                    r = $.extend(r, ext_img); else
                if (p == "<doc>")    
                    r = $.extend(r, ext_doc); else
                if (p == "<vid>")
                    r = $.extend(r, ext_vid); else
                    r[p] = true;
            }
            
            for (i = 0; i < neg.length; i++) 
                delete r[neg[i]];
            
            return r;
        },
        check_layout: function(browser, ops) {
            var list = ops._elem.list;
            if (!ops._update_xhr) {
                var layout = {
                    width: list.width(),
                    height: list.height(),
                    scroll_top: list.scrollTop(),
                    scroll_left: list.scrollLeft()    
                }
                
                if (!layout.width || !layout.height)
                    return;
                    
                if (ops._update_force ||
                    layout.width != ops._layout.width ||
                    layout.height != ops._layout.height ||
                    layout.scroll_top != ops._layout.scroll_top ||
                    layout.scroll_left != ops._layout.scroll_left) {
                    
                    $.browser.list_update(browser, ops)
                    ops._layout = layout;    
                }
            }
            
            ops._update_timeout = setTimeout(function() {
                $.browser.check_layout(browser, ops);
            }, ops.update_interval);
        },
        message: function(browser, content, error) {
            if (!content && error)
                content = "Error occured"; else
            if (!content)
                content = "";
            
            var cls = (error) ? "bm_error" : "";
            var html = "<div class='browser_message " + cls + "'>" + content + "</div>";
            browser.children(".browser_footer").html(html);
        },  
        response: function(browser, r) {
            if (!r) {
                $.browser.message(browser, "Cannot connect to server", true);
                return false;
            }
            
            if (!r.status) {
                $.browser.message(browser, r.error, true);    
                return false;
            }
            
            if (r.message)
                $.browser.message(browser, r.message, false); else
            if (r.error)
                $.browser.message(browser, r.error, true);  
            
            return true;          
        },
        load: function(browser, data, callback) {
            var ops = browser.data("browser");
            data = $.extend({}, {
                load: ops.state.curr_path,
                filter: $.isObject(ops.filter) ? Object.keys(ops.filter) : null,
                image_size: ops.sizes[ops.state.view],
            }, data);
            
            $.browser.overlay_show(browser, true);
            $.post(ops.handler, data, function(r) {
                $.browser.overlay_hide(browser);

                if (!$.browser.response(browser, r)) return;
                
                ops.state.curr_path = r.path;
                $.browser.tree_load(browser, r.tree, ops);
                $.browser.list_load(browser, r.list, ops);
                $.browser.tree_update(browser, ops);
                $.browser.menu_update(ops);
                    
                if (typeof callback == "function")
                    callback(browser, ops, r);
            }, "json").fail(function() {
                $.browser.overlay_hide(browser);
                $.browser.message(browser, "Error connecting to server", true);
            });
        },
        create: function(browser, options) {
            var ops = $.extend({}, $.browser.def_options, options);
            var browser = $.browser.build(browser, ops);
            
            var parent = browser.parent();
            var zindex = 0;
            while (parent.length) {
                zindex = parent.css("z-index");
                if (zindex !== "auto") 
                    break;    
                   
                parent = parent.parent();                
            }
            
            ops.filter = $.browser.parse_filter(ops.filter);
            ops.select_filter = $.browser.parse_filter(ops.select_filter);
            ops.upload_filter = $.browser.parse_filter(ops.upload_filter);
            
            ops._cache_size = 0;
            ops._cache = {};
            ops._loaded = false;
            ops._queue = [];
            ops._zindex = parseInt(zindex);            
            ops._elem = {
                list: browser.find(".browser_list"),
                tree: browser.find(".browser_tree"),
                menu: browser.find(".browser_menu"),
                address: browser.find(".browser_address"),
                uploads: browser.find(".browser_uploads"),
            }

            $.browser.menu_build(browser, ops);
            
            ops._elem["uploader"] = ops._elem.menu.find("input");
                
            if (typeof $.cookie == "undefined")
                ops.save_state = false;
                                                                              
            if (ops.save_state && !ops.cookie_name) {
                var id = browser.attr("id");
                if (typeof id != "undefined")
                    ops.cookie_name = "browser_" + id; else
                    ops.cookie_name = "browser";                    
            }
            
            $.browser.list_init(browser, ops);
            ops.state = {
                view: ops.view,
                curr_path : "/",
                expanded : {},
            }
            ops._update_xhr = null;
            ops._update_timeout = null;
            ops._uploads = {};
            ops._action = null;     
            $.browser.load_state(ops);
            
            var views = browser.children(".browser_header").children(".browser_view");
            views.children("a[data-view=" + ops.state.view + "]").addClass("bv_active");
            views.children("a").click(function() {
                var browser = $(this).parents(".browser:first");
                $.browser.change_view(browser, $(this).attr("data-view"), browser.data("browser"));
                
                return false;
            });
            
            if (!$.browser.global_events) {  
                $(document).mousemove(function(e) {
                    var list = $.browser.selecting;
                    if (!list) return;
                    
                    $("body").addClass("prevent_selection").attr("onselectstart", "return false");
                    
                    var ul = list.children("ul");     

                    var mx = e.pageX;
                    var my = e.pageY;
                    var list_ofs = list.offset();
                    var list_w = list.outerWidth();
                    var list_h = list.outerHeight();
                    
                    var outside = false;
                    
                    if (mx < list_ofs.left) mx = list_ofs.left;
                    if (mx > list_ofs.left + list_w) mx = list_ofs.left + list_w;

                    if (my < list_ofs.top) { my = list_ofs.top; outside = -1 }
                    if (my > list_ofs.top + list_h) { my = list_ofs.top + list_h; outside = 1 }
                    
                    if (outside != 0) {
                        if (!$.browser.scroll)
                            $.browser.scroll = {
                                direction: outside,
                                clear: !e.shiftKey && !e.ctrlKey,
                                timeout: setTimeout($.browser.scroll_callback)
                            }                               
                        $.browser.scroll.x = mx;
                        $.browser.scroll.y = my;
                        return false;
                    } else 
                        $.browser.scroll_stop();
                    
                    $.browser.selecting_moved = true;
                    $.browser.set_selector(list, mx, my, !e.shiftKey && !e.ctrlKey);
                }).bind("mouseup", function(e) {
                    var list = $.browser.selecting;
                    if (!list) return;

                    $.browser.scroll_stop();
                    
                    $("body").removeClass("prevent_selection").removeAttr("onselectstart");
                    
                    var ul = list.children("ul");
                
                    ul.children(".bl_selecting").removeClass("bl_selecting");
                    ul.children(".bl_selector").hide();  

                    $.browser.selecting = false;
                    $.browser.selecting_moved = false;
                    e.stopPropagation();
                    return false;
                });
                $.browser.global_events = true;
            }   
            
            $.browser.uploader_init(browser, ops);
            $.browser.load(browser, null, function(browser, ops) {
                ops._loaded = true;
            });
        },
        change_dir: function(browser, path, ops) {
            $.browser.overlay_show(browser, true);
            ops.state.curr_path = path;
            $.browser.save_state(ops);
            
            $.browser.load(browser);
        },
        select: function(browser, paths, ops) {
            if (!$.isArray(paths)) return;
            
            var itm;  
            for (var i = 0; i < paths.length; i++) {
                itm = ops._elem.list.find(".bl_item[data-path='" + paths[i] + "']");                  
                if (itm.length) 
                    itm.addClass("bl_selected");
            }
        },
		methods: {
            selected: function(args) {
                var ok = (args.length > 0 && args[0] == "selected");
                if (!ok) return false;
                                
                var res = $.browser.get_selected($(this));                                
                
                return { result: res };
            },
			create: function(args) {
				var ok = (args.length == 1 || (args.length > 1 && args[0] == "create"));
				if (!ok) return false;

				$.browser.create($(this), (args.length > 1) ? args[1] : args[0]);
				   
				return { result: $(this) };
			},
            action: function(args) {
                var ok = (args.length == 2 && args[0] == "action");
                if (!ok) return false;
                                
                $.browser.complete_action($(this), args[1]);                                
                
                return { result: $(this) };
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

	$.fn.browser = function() {
		var res;
		for (var method in $.browser.methods) {
			res = $.browser.methods[method].apply(this, [arguments])
			if (typeof res != "undefined" && res !== false) 
				return res.result;
		}
	}
})( jQuery );        

