/*
    TODO:
    - select/deselect on click?
    - kaj se zgodi ce ni loaded?
    - destroy
    - upload filter?
    - detached mogoce se mal drgac?
    - create folder -> select after
    - item highlighted namesto hover
    - ce nima extensiona, se neki zjebe?
    - filter narest na mime type?
    - rename + key down pa to?
    - F2
*/
(function( $ ){   
	"use strict";

	$.browser = { 
		def_options: {
			handler: null,
            root_title: "Root",
            view: "large",
            drag_and_drop: true,
            save_state: true,
            edit_extension: false,
            cookie_name: null,
            filter: null,
            update_max_items: 10, 
            cache_size: 100,
            select_filter: null,
            upload_filter: null,
            
            onDialog: null,
            onDoubleClick: null,            
            
            trans: {},
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
        translate: function(tag, ops) {
            if (typeof ops.trans[tag] != "undefined")
                return ops.trans[tag]; else
                return tag;
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
                  
            $.browser.load(browser, { create_folder: target.attr("data-path"), name: name });   
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
        confirm_move: function(browser, paths, dest, mode, ops) {
            ops._action = {
                name: mode,
                callback: $.browser.move_file,
                param: [paths, dest, mode]
            }
            
            if (typeof ops.onConfirm == "function") 
                ops.onConfirm.apply(browser, [mode, paths, dest]); else
                $.browser.complete_action(confirm("Are you sure you want to " + mode + " " + paths.length + " files/folders"), browser);                
        },       
        move_file: function(paths, dest, mode) {
            var browser = $(this);
            var ops = browser.data("browser");   
            
            var post = {dest : dest}
            post[mode] = paths; 
            
            $.browser.load(browser, post, function(browser, ops, r) {
                $.browser.select(browser, r.files, ops);
            });   
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
        path_info: function(path, is_dir) {
            var s1 = path.split(".");
            var r = {};
            if (!is_dir && s1.length > 1)
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
                
                info = $.browser.path_info(node.path, node.type == "dir");
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
            var tree = ops._elem.tree;
            
            tree.html(html);  
            
            var items = tree.find(".bt_item");   
            items.bind("contextmenu", function(e) {
                var ops = $(this).parents(".browser:first").data("browser");
                if ($(this).is(".bt_root")) {
                    var menu = [{
                        "name" : "create",
                        "content" : $.browser.translate("New folder", ops)
                    }];
                } else {
                    var menu = [{
                        "name" : "rename",
                        "content" : $.browser.translate("Rename", ops),
                    },{
                        "name" : "delete",
                        "content" : $.browser.translate("Delete", ops),             
                    },{
                        "name" : "create",
                        "content" : $.browser.translate("New folder")
                    }];
                    
                    var path = $(this).attr("data-path");
                    if ($.browser.paste_valid(path, ops._queue))
                        menu.push({
                            "name" : "paste",
                            "content" : $.browser.translate("Paste", ops)
                        });
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
                            $.browser.tree_delete(target); else
                        if (name == "paste") 
                            $.browser.list_queue_exec(ops._elem.list, target);
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
            }).bind("mousemove", function(e) {
                $.dyngrid.dragover(e, $(this));
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
            var tree = ops._elem.tree;  
            
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
                } else 
                if (e.which == 27) {
                    var edit = $(this).parent();
                    var item = edit.data("browser_target");
                    
                    $(this).val(item.find(".bt_title").html());
                    $(this).blur();
                }
                e.stopPropagation();
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
            var info = $.browser.path_info(node.path, node.type == "dir");
            
            if (upload)
                cls = "bl_upload"; else
            if (node.type == 'dir')
                cls = "bl_folder"; else
                cls = "bl_file";
                
            cls+= " bl_" + ops.state.view;
                
            var style = "";
            var details = node.details;
            if (!details && ops._cache[node.path]) {
                details = ops._cache[node.path];
                details.image = details["image_" + ops.state.view];
            }
            if (!details) {
                if (node.type == 'file')
                    cls+= " dyngrid_pending";
                    
                details = {};
            }
                
            if ($.browser.extensions.image.indexOf(info.ext.toLowerCase()) != -1)
                cls+= " bl_picture";
            
            if (details.image) {
                style = "style=\"background-image: url('" + details.image + "')\""; 
                cls+= " bl_border";
            }
            if (details.corrupt)
                cls+= " bl_corrupt";
            
            var url = (details.url) ? details.url : "";
            
            var html = "<li class='bl_item " + cls + "' data-path='" + node.path + "' title='" + info.file + "' data-url='" + url + "'>";
            html+= "<div class='bl_image' " + style + "></div>";
            html+= "<div class='bl_main'>";
            html+= "<div class='bl_title'>" + info.file + "</div>";
            if (upload) 
                html+= "<div class='bl_progress'><div style='width: " + parseInt(upload.progress * 100) + "%'></div></div>";
                
            html+= "<div class='bl_type bl_info'>";
            if (details.type == 'dir')
                html+= "directory"; else
            if (details.info) 
                html+= details.info;
            html+= "</div>";                
                
            html+= "<div class='bl_imgsize bl_info'>";
            if (details.width && details.height)
                html+= details.width + " x " + details.height;
            html+= "</div>";
            
            html+= "<div class='bl_time bl_info'>";
            if (details.time)
                html+= details.time;
            html+= "</div>";
            html+= "</div>";
            html+= "</li>";                
            
            return html;
        },
        list_build: function(browser, nodes, ops) {
            var html = "<div class='browser_list'>";
            html+= "<ul>";    
            
            for (var i = 0; i < nodes.length; i++) 
                html+= $.browser.list_build_item(nodes[i], null, ops);
            
            var file;
            for (var path in ops._uploads) {
                file = ops._uploads[path];
                
                if (file.path == ops.state.curr_path)                 
                    html+= $.browser.list_build_item({"path" : path}, file, ops); 
            }
            
            html+= "</ul>";                     
            html+= "</div>";
            
            return html;
        },  
        list_init_item: function(item) {
            item.bind("contextmenu", function(e) {
                var ops = $.browser.options($(this));
                
                if (!$(this).is(".dyngrid_selected")) 
                    $(this).parents("ul:first").find(".dyngrid_selected").removeClass("dyngrid_selected dyngrid_last");
                                
                $(this).addClass("dyngrid_selected dyngrid_last");
                $.browser.menu_update(ops);
            });
            item.dblclick(function(e) {
                var browser = $(this).parents(".browser:first");
                var ops = browser.data("browser");
                
                var type;
                var path = $(this).attr("data-path");
                if ($(this).hasClass("bl_folder"))
                    type = "folder"; else
                if ($(this).hasClass("bl_file"))
                    type = "file";
                
                if (!type) return;
                
                if (typeof ops.onDoubleClick == "function") {
                    var r = ops.onDoubleClick.apply(this, [path, type]);
                    if (r === false) return;    
                }
                
                if (type == "folder")                
                    $.browser.change_dir(browser, path, ops);
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
                var ops = $(this).parents(".browser:first").data("browser");
                var menu = [{
                    name : "create",
                    content : $.browser.translate("New folder", ops)
                }];
                if ($.browser.paste_valid(ops.state.curr_path, ops._queue))
                    menu.push({ name : "paste", content : $.browser.translate("Paste", ops) });
                
                var sel = $(this).children("ul").children(".dyngrid_selected");
                
                if (sel.filter(".bl_file, .bl_folder").length) {
                    menu.push({ separator: true });
                    
                    if (sel.filter(".bl_file:not(.dyngrid_pending)").length)
                        menu.push({ name : "view", content : $.browser.translate("View", ops) });
                    
                    menu.push({ name : "rename", content : $.browser.translate("Rename", ops) });
                    menu.push({ name : "delete", content : $.browser.translate("Delete", ops) });
                    menu.push({ name : "copy", content : $.browser.translate("Copy", ops) });
                    menu.push({ name : "cut", content : $.browser.translate("Cut", ops) });
                }
                if (sel.filter(".bl_upload").length) {
                    menu.push({ separator: true });
                    menu.push({ name : "cancel", content : $.browser.translate("Cancel upload", ops) });
                }
                    
                $(this).contextmenu(e.clientX, e.clientY, menu, {
                    cls: "browser_cmenu",
                    zindex: ops._zindex + 1000,
                    onClick: function(target, name) {
                        var sel = target.children("ul").children(".dyngrid_selected");
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
            });
            
            list.dynamic_grid({
                mode: "dnd",
                
                onDragStart: function(e, items) {
                    return items.filter(":not(.bl_upload)");  
                },
                onDragOver: function(e, items) {
                    if (!$(this).hasClass("bl_folder") && !$(this).hasClass("bt_item")) 
                        return false;
                    
                    var paths = [];
                    for (var i = 0; i < items.length; i++) 
                        paths.push($(items[i]).attr("data-path"));
                        
                    var path = $(this).attr("data-path");
                    if (!$.browser.paste_valid(path, paths)) return false;
                    
                    if ($(this).hasClass("bt_root"))
                        name = ops.root_title; else
                        name = $.browser.path_info(path).name;
                        
                    var html;
                    if (e.ctrlKey || e.shiftKey)                    
                        html = "<span class='bc_copy'>" + $.browser.translate("Copy to", ops) + " " + name + "</span>"; else
                        html = "<span class='bc_move'>" + $.browser.translate("Move to", ops) + " " + name + "</span>";
                    
                    return html;
                },
                onDrop: function(e, items) {
                    var browser = $(this).parents(".browser:first");
                    var ops = browser.data("browser");
            
                    $.browser.overlay_show(browser, true);
                    
                    var paths = [];
                    for (var i = 0; i < items.length; i++) 
                        paths.push($(items[i]).attr("data-path"));
                        
                    var dest = $(this).attr("data-path");
                    $.browser.move_file.apply(browser, [paths, dest, (e.ctrlKey || e.shiftKey) ? "copy" : "move"]);
                    //$.browser.confirm_move(browser, paths, dest, (e.ctrlKey || e.shiftKey) ? "copy" : "move", ops); 
                },
                onSelection: function(items) {
                    var browser = $(this).parents(".browser:first");
                    var ops = browser.data("browser");                    
                    
                    $.browser.menu_update(ops, null, items);
                },
                onLoadItems: function(items) {
                    var browser = $(this).parents(".browser:first");
                    var ops = browser.data("browser");                    
                    
                    return $.browser.list_update(browser, items, ops);
                }
            });
        },
        list_load: function(browser, nodes, ops) {
            var html = $.browser.list_build(browser, nodes, ops);
            var list = $(html);
            
            ops._elem.list.replaceWith(list);
            ops._elem.list = list;
            $.browser.list_init(browser, ops);

            var list = ops._elem.list;
            list.find(".bl_item").each(function() {
                $.browser.list_init_item($(this));                
            });
            
            list.children("ul").mousedown(function(e) {
                if (e.which == 3) return;
                                    
                var list = $(this).parent();
                var browser = list.parents(".browser:first");
                if (browser.is(".bs_renaming")) return;
            });
            
            //if (ops._update_xhr)
              //  ops._update_xhr.abort();
                
            $.browser.menu_update(ops);
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
            
            $.browser.menu_update(ops);
        },
        list_queue_exec: function(list, dest) {
            var browser = list.parents(".browser:first");
            var ops = browser.data("browser");
            
            if (!ops._queue.length) return;
            if (!dest) dest = ops.state.curr_path;
            
            
            $.browser.overlay_show(browser, true);
            var post = {
                filter: $.isObject(ops.filter) ? Object.keys(ops.filter) : null,
                dest : dest,
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

        list_rename: function(node) {
            var browser = node.parents(".browser:first");
            var ops = browser.data("browser");
            
            var list = ops._elem.list;
            
            var title = node.find(".bl_title");
            title.hide();
            
            browser.addClass("bs_renaming");
            
            var value, ext = "";
            if (!ops.edit_extension) {
                var pi = $.browser.path_info(node.attr("data-path"), node.hasClass("bl_folder"));
                value = pi.name;
                if (pi.ext)
                    ext = "." + pi.ext;
            } else 
                value = title.html();
            
            var edit = $("<div class='bl_edit'><input type='text' value='" + value + "' data-ext='" + ext + "'></div>").insertAfter(title);
            var input = edit.children("input");
            
            input.focus().select();
            input.blur(function() {
                var item = $(this).parents(".bl_item:first");                                   
                var title = item.find(".bl_title");
                var value = $(this).val().trim() + $(this).attr("data-ext");
                
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
                } else 
                if (e.which == 27) {
                    var item = $(this).parents(".bl_item:first");
                                        
                    $(this).val(item.find(".bl_title").html());
                    $(this).blur();
                }
                e.stopPropagation();
            });                                       
        },
        list_update: function(browser, items, ops) {
            if (ops._update_xhr) return false;
            
            var paths = [];
            for (var i = 0; i < items.length; i++)
                paths.push($(items[i]).attr("data-path"));
                
            var list = ops._elem.list.children("ul");
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
        view_file: function(node) {
            var url = node.attr("data-url");
            if (url)
                window.open(url, "_blank");                
        },
        paste_valid: function(target, files) {
            if (!files.length) return false;   
                
            var pth;
            for (var i = 0; i < files.length; i++) {
                pth = files[i] + "/";
                if (target.indexOf(pth) === 0) return false;    
            }
            
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
            
            var orig_path = data.result.orig_path;
            var file = data.result.file;
            var info = $.browser.path_info(file.path);
            
            delete ops._uploads[orig_path];
            
            if (info.path == "") info.path = "/";
            if (info.path == ops.state.curr_path) {
                var ul = ops._elem.list.children("ul");                            
                
                var item = $($.browser.list_build_item(file, null, ops));
                var old_item = ul.find(".bl_item[data-path='" + orig_path + "']");
                if (!old_item.length) 
                    item.appendTo(ops._elem.list.children("ul")); else
                    old_item.replaceWith(item); 
                
                $.browser.list_init_item(item);
                $.browser.list_update(browser, item, ops);
                ops._elem.list.dynamic_grid("init_item", item);
            } 
            
            ops._elem.uploads.find("li[data-path='" + orig_path + "']").remove();
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
                        //$.browser.change_dir(browser, path, ops);
                    } else 
                        path = ops.state.curr_path;
                    
                    ops._upload_path = path;
                    
                    $.browser.uploader_add(data, ops);
                      //  $.browser.change_dir(browser, ops.state.curr_path, ops); 
                },
                add: function(e, data) {
                    var browser = $(this).parents(".browser:first");
                    var target = $(e.currentTarget);
                    var ops = browser.data("browser");
                    
                    var file = data.files[0];
                    if (!file.size) return;
                    
                    var path;
                    if (target.is("input")) {
                        ops._upload_path = ops.state.curr_path;
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
                    
                    $.browser.change_dir(browser, ops.state.curr_path, ops); 
                    
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
            html+= "<li class='bm_item bm_create' data-show='*' style='display: block'><div class='bm_icon'></div><div class='bm_title'>" + $.browser.translate("New folder", ops) + "</div></li>";
            html+= "<li class='bm_item bm_upload' data-show='*' style='display: block'>";
            html+= "    <div class='bm_icon'></div>";
            html+= "    <div class='bm_title'>" + $.browser.translate("Upload", ops) + "</div>";
            html+= "    <input type='file' multiple name='files[]'>";
            html+= "</li>";
            html+= "<li class='bm_item bm_paste'  data-show='q'><div class='bm_icon'></div><div class='bm_title'>" + $.browser.translate("Paste", ops) + "</div></li>";
            html+= "</ul>";    
            
            var menu = ops._elem.menu.html(html);
            var ul = menu.children("ul");
            
            ul.children("li.bm_create").click(function() {
                var ops = $.browser.options($(this));
                
                $.browser.list_create_folder(ops._elem.list);                
            });            
            ul.children("li.bm_rename").click(function() {
                var ops = $.browser.options($(this));
                
                var items = ops._elem.list.children("ul").children(".dyngrid_selected");
                var item = items.filter(".dyngrid_last");
                
                if (!item.length)
                    item = items.get(0);
                    
                $.browser.list_rename(item);
            });
            ul.children("li.bm_paste").click(function() {
                var ops = $.browser.options($(this));
                
                $.browser.list_queue_exec(ops._elem.list);
            });
        },
        menu_update: function(ops) {
            var menu = ops._elem.menu;            
            if ($.browser.paste_valid(ops.state.curr_path, ops._queue))
                menu.find("li.bm_paste").show(); else    
                menu.find("li.bm_paste").hide(); 
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
            html+= "    <div class='browser_search'>";
            html+= "        <input type='text' placeholder='Išči'>";
            html+= "        <button class='browser_search_apply'></button>";
            html+= "        <button class='browser_search_reset'></button>";
            html+= "    </div>";
            html+= "    <div class='browser_address'></div>";
            html+= "</div>";
            html+= "<div class='browser_left'>";
            html+= "    <div class='browser_menu'></div>";
            html+= "    <div class='browser_uploads'><ul></ul></div>";
            html+= "    <div class='browser_tree'></div>";
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
        get_selected: function(browser) {
            var ops = browser.data("browser");
            var res = [];
            var itm,sel = ops._elem.list.find(".dyngrid_selected");
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
                ops._action.callback.apply(browser, ops._action.param); else
                $.browser.overlay_hide(browser);
            
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
                search: ops.state.search,
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
            while (parent.length && !parent.is("body")) {
                zindex = parent.css("z-index");
                if (zindex !== "auto") 
                    break;    
                   
                parent = parent.parent();                
            }
            if (zindex == "auto") zindex = 0;
            
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
                search: "",
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
            var search = browser.children(".browser_header").children(".browser_search");
            search.children("input").keydown(function(e) {
                if (e.which == 13) 
                    $(this).parent().children("button.browser_search_apply").click();
            });
            search.children("button.browser_search_apply").click(function() {
                var browser = $(this).closest(".browser");
                var ops = browser.data("browser");
                var input = $(this).parent().children("input");
                var value = input.val().trim();
                
                ops.state.search = value;    
                $.browser.load(browser);
            });
            search.children("button.browser_search_reset").click(function() {
                var browser = $(this).closest(".browser");
                var ops = browser.data("browser");
                var input = $(this).parent().children("input");
                input.val("");
                
                ops.state.search = "";    
                $.browser.load(browser);
            });
            
            if (!$.browser.global_events) {  
                $.browser.global_events = true;
            }   
            
            $.browser.uploader_init(browser, ops);
            $.browser.load(browser, null, function(browser, ops) {
                ops._loaded = true;
            });
            
            browser.data("browser", ops);
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
                    itm.addClass("dyngrid_selected");
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

