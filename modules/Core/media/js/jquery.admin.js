(function( $ ){   
    $.isObject = function(obj) {
        return (Object.prototype.toString.call(obj) === '[object Object]');
    }     
    $.admin = {
        xhr_pool: [],
        form_action: function(button) {
            var form = $(button).parents("form.admin_form:first");    
            $("[name=form_action]", form).val($(button).attr("name"));
        },
        form_data: function() {
            var data = $("#module_form").serialize();
            
            $("#module_menu form.module_menu_form").each(function() {
                data+= "&" + $(this).serialize(); 
            });    
            
            return data;
        },
        layout: function() {
            var module = $("#module_content");  
            if (module.width() < 600) 
                module.addClass("compact"); else
                module.removeClass("compact"); 
            
            //if ($(window).width() < 700)
            //    $("#module_menu").float_bar("disable"); else
            //    $("#module_menu").float_bar("enable"); 
        },
        close_menus: function() {
            if ($("#header_menu_button").is(":visible"))
                $("#main_left").removeAttr("style");
            if ($("#module_settings").is(":visible"))
                $("#module_menu").removeAttr("style");
        },
        reload_check: null,
        reload_module: function(url, push, post, callback, ignore_check) {
            $.admin.close_menus();
            $.overlay_close();
            
            if ($.admin.reload_check && !ignore_check) {
                $.admin.reload_check(url, function(res) {
                    if (res)
                        $.admin.reload_module(url, push, post, callback, true); else
                        if (typeof callback == "function") callback(false); 
                });
                return false;
            }
            
            for (var i in $.admin.xhr_pool) 
                $.admin.xhr_pool[i].abort();
            $.admin.xhr_pool = [];
            tinymce.remove();
            $.overlay_loader();
            
            $.post(doc_base + "Admin/Ajax_Logged", {}, function(r) {
                /*
                if (r != "1") {
                    window.location.href = doc_base + "Admin";                       
                    return;
                }
                */
                
                if (!url)
                    url = window.location.href;
                    
                if (!post)
                    post = {};
                post["module_ajax"] = true;
                    
                $.post(url, post, function(r) {
                    $.admin.reload_check = null;
                    $(document).unbind("module_loaded");
                    $(document).unbind("content_lang");
                    $("#module").replaceWith(r);  
                    $("#module").ac_create();  
                    $(document).trigger("module_loaded");
                    
                    if (push)
                        $("body").pushState(url, admin_module_title);
                    
                    $.overlay_close(); 
                    
                    if (typeof callback == "function") callback(true);
                }).fail(function() {
                    $.overlay_close();
                    $.overlay_message("<div class='admin_message failure'>Error occured while loading</div>");
                    
                    if (typeof callback == "function") callback(false);
                });
                
            });
            
        }
    }
    $.fn.module_loaded = function(callback) {
        $(document).bind("module_loaded", callback);    
    }
    $.fn.admin_form = function() {
        $.admin.reload_check = function(url, callback) {
            var form_data = $("#module_form_data");
            var curr_data = $.admin.form_data();
            var prev_data = form_data.val();
            
            if (form_data.hasClass("loaded") && curr_data != prev_data) {
                $.overlay_confirm("<div class='admin_message warning'>Contents of the form were changed. Continue without saving?</div>", function() {
                    callback(true);
                }, function() {
                    callback(false);
                });  
            } else
                callback(true);
        };
        $(this).submit(function(e, action, url) {
            $.admin.close_menus();
            
            var form = $(this);
            /*form.find(".admin_template_editor").each(function() {
                $.rleditor.output_proc($(this)); 
            });*/
            var data = $.admin.form_data();
            
            var formd = $("#module_form_data");
            var changed = (!formd.hasClass("loaded") || formd.val() == data) ? 0 : 1; 
            
            data+="&form_changed=" + changed;
            
            var lang = $("#module_form_lang").val();
            if (lang)
                data+="&admin_form_lang=" + lang;
            
            var module = $("#module_content");
            module.css("min-height", module.height() + "px");

            $.overlay_ajax(form.attr("action") + "/" + action, "ajax", data, { 
                form_data: {
                    action: action,  
                    redirect: url,
                },
                onLoaded: function(r) {
                    if (r.message) {
                        $.overlay_close();
                        $.overlay_message(r.message);
                    } else
                    if (r.reload) {
                        window.location.reload();
                    } else 
                    if (r.redirect) {
                        $.admin.reload_module(r.redirect, true, null, null, true);
                        $.overlay_close();
                    } else
                    if (r.form_data.action == "preview") {
                        window.open(r.form_data.redirect, "preview");  
                        r.show = false;   
                    } else
                    if (r.form_data.action == "close") {
                        window.location.href = r.form_data.redirect; 
                    } else {                     
                        if (r.form_content) {
                            if (r.menu_content) 
                                $("#module_menu").html(r.menu_content); else
                                $("#module_menu").html('');
                            
                            $(document).unbind("module_loaded");
                            $("#module_content").html(r.form_content).ac_create();
                            $(document).trigger("module_loaded");
                            
                            $("#module_language [name=edit_lang]").change(function() {
                                $.admin.toggle_edit_lang($(this).val());
                            });                            
                            
                            if (r.form_data.action == "save")
                                setTimeout(function() { $.overlay_close(true) }, 1000);
                        }
                    }
                },
                onFail: function() {
                    $.overlay_message("<div class='admin_message failure'>Error occured</div>");    
                }
            });  
            
            return false;       
        });
        
        setTimeout(function() { 
            $("#module_form_data").addClass("loaded").val($.admin.form_data());
        }, 1000);
        
        var menu = $("#module_menu");
        var section = menu.find(".admin_section.admin_form_menu");
        
        menu.ac_create();        
        
        section.find("[name=form_history]").change(function() { $("#module_form").trigger("submit", ["restore"]) });
        section.find(".admin_button_preview").click(function() { $("#module_form").trigger("submit", ["preview", $(this).attr("data-url") ]) });
        section.find(".admin_button_save").click(function() { $("#module_form").trigger("submit", ["save"]) }); 
        section.find(".admin_button_close").click(function() { $("#module_form").trigger("submit", ["close", $(this).attr("data-url")]) });
        section.find(".admin_button_revert").click(function() { 
            $.overlay_confirm("<div class='admin_message warning'>" + trans_admin_confirm_revert + "</div>", function() {
                $.admin.reload_module(window.location.href, false, null, null, true);
            });
        });
        section.find(".admin_button_cancel").click(function() { admin_redirect(this) });
        /*
        if ($(window).width() >= 700) {
            $("#module_menu").float_bar({
                height: function() {
                    return $("#module_content").height();
                },
                top: 10
            });
        }
        */
    }
    $.fn.admin_browse = function(callback, ops, data) {
        $.overlay_ajax(doc_base + "Admin/Ajax_Browser", false, {}, {
            resizable: true,
            width: 0.6,
            height: 0.8,
            zindex: (ops) ? ops.zindex : null,
            cookie_name: project_name + "_browser",
            browser: {
                target: $(this),
                callback: callback,
                callback_data: data,
            },
            onDisplay: function() {
                var ops = $(this).data("overlay");
                
                $(this).find("div.admin_browser").browser({
                    handler: doc_base + "Admin/Ajax_BrowserHandler",
                    onDoubleClick: function(path, type) {
                        if (type != "file") return;
                        
                        var ops = $.overlay_active().data("overlay").browser;
                        var paths = [path.substring(1)]; 
                        
                        ops.callback.apply(ops.target, [paths, ops.callback_data]);
                        
                        $.overlay_close();
                    }
                });
            },
            onClose: function(r) { 
                var ops = $(this).data("overlay").browser;
                var browser = $(this).find("div.admin_browser");
                
                if (r) {
                    var sel = browser.browser("selected");
                    var paths = [];
                    for (var i = 0; i < sel.length; i++) 
                        paths.push(sel[i].substring(1));
                    
                    if (paths.length)
                        ops.callback.apply(ops.target, [paths, ops.callback_data]);
                }
            }    
        });
    };
    
    $.admin.gallery_editor = {
        def_options: {},
        load_items: function(items) {
            var paths = [];
            for (var i = 0; i < items.length; i++)
                paths.push($(items[i]).attr("data-path"));
                
            var ul = $(this).children("ul");
            $.post(doc_base + "Admin/Ajax_Thumbnail", { 
                paths: paths,
                template: "admin_gallery_96"
            }, function(r) {
                for (var pth in r) 
                    ul.children("li[data-path='" + pth + "']").children(".admin_gei_image").css("background-image", "url('" + r[pth] + "')");
                
            }, "json");    
        },
        add_items: function(e) {
            var editor = $(this).parents(".admin_gallery_editor:first");
            editor.admin_browse(function(images) {
                var editor = $(this);
                var name = editor.attr("data-name");
                var list = editor.children("div.admin_ge_list");
                var ul = list.children("ul");
                var html, item;
                var load = $();
                for (var i = 0; i < images.length; i++) {
                    html = '<li class="admin_ge_item dyngrid_item dyngrid_pending" data-path="' + images[i] + '">';
                    html+= '    <div class="admin_gei_image"></div>';
                    html+= '    <div class="admin_gei_title"><div style="text-align: center">Click to edit title</div></div>';
                    html+= '    <input type="hidden" value="0" name="' + name + '_ids[]">';
                    html+= '    <input type="hidden" value="' + images[i] + '" name="' + name + '_paths[]">';
                    html+= '    <input type="hidden" value="" name="' + name + '_titles[]">';
                    html+= '</li>';
                    
                    item = $(html).appendTo(ul);
                    item.children("div.admin_gei_title").bind("click", $.admin.gallery_editor.edit_title).bind("mousedown", function(e) { e.stopPropagation() });
                    list.dynamic_grid("init_item", item);
                    
                    load = load.add(item);
                }
                
                $.admin.gallery_editor.load_items.apply(list, [load]);
            });    
        },
        del_items: function(e) {
            var editor = $(this).parents(".admin_gallery_editor:first");
            var editor_name = editor.attr("data-name");
            var editor_id = editor.attr("id");
            var menu = editor.children(".admin_ge_menu");
            var items = editor.children("div.admin_ge_list").children("ul").children("li.dyngrid_selected");
            var deleted = editor.children("div.admin_ge_delete");
            
            var item;
            for (var i = 0; i < items.length; i++) {
                item = $(items[i]);
                if (id = item.attr("data-id"))
                    $("<input type='hidden' name='" + editor_name + "_delete[]' value='" + id + "'>").appendTo(deleted);
                    
                item.removeClass("dyngrid_selected");
                item.fadeOut(function() {
                    $(this).remove(); 
                });
            }
            
            menu.children(".admin_button_del").hide();
        },
        edit_title: function(e) { 
            var item = $(this).parents("li:first");
            $.overlay({
                width: 0.6,
                mode: "inline",
                form: true,
                target: item,
                content: "<textarea name='title_edit' rows='6'></textarea>",
                title: "Edit image title",
                buttons: { "ok" : { key : null }, "cancel" : {}},  
                onLoaded: function(ops) {
                    var title = ops.target.children("input[name*='title']").val();
                    var edit = $(this).find("[name=title_edit]").ac_edit({value: title});
                },
                onClose: function(r, data, ops) {
                    if (!r) return;
                    
                    var title = $(this).find("[name=title_edit]").val();                     
                    
                    ops.target.children("div.admin_gei_title").html(title);
                    ops.target.children("input[name*='title']").val(title);
                }               
            });
            
            e.stopPropagation();
            return false;    
        }
    }
    $.fn.admin_gallery_editor = function(options) {
        if (!$(this).hasClass("admin_gallery_editor")) return $(this);
                                                                         
        var ops = $.extend({}, $.admin.gallery_editor.def_options, options); 
        var list = $(this).children("div.admin_ge_list");
        var menu = $(this).children("div.admin_ge_menu");
        
        menu.children("a.admin_button_add").click($.admin.gallery_editor.add_items);
        menu.children("a.admin_button_del").click($.admin.gallery_editor.del_items);

        var items = list.children("ul").children("li");
        items.each(function() {
            var title = $(this).children("div.admin_gei_title");
            
            title.bind("click", $.admin.gallery_editor.edit_title).bind("mousedown", function(e) { e.stopPropagation() });
            if (!title.html())
                title.html("<div style='text-align: center'>Click to edit title</div>");
        });
        
        list.dynamic_grid({
            mode: "sort",
            onLoadItems: $.admin.gallery_editor.load_items,
            onSelection: function(items) {
                var editor = $(this).parents(".admin_gallery_editor:first");
                var menu = editor.children(".admin_ge_menu");
                if (items.length)
                    menu.children(".admin_button_del").show(); else
                    menu.children(".admin_button_del").hide();
            }
        }).bind("keydown", function(e) {
            if (e.keyCode == $.dyngrid.keys.DELETE) {
                var editor = $(this).parents(".admin_gallery_editor");
                var items = $(this).children("ul").children("li.dyngrid_selected");
                
                if (items.length)
                    editor.find("a.admin_button_del").click();
                    
                e.stopPropagation();
                return false;
            } 
        });
    }
    
    $.admin.template_editor = {
        parse_style: function(style) {
            if (!style) return {};
            
            var rule, rules = style.split(";");
            var result = {};
            for (var i = 0; i < rules.length; i++) {
                rule = rules[i].trim().split(":");                                
                if (rule.length > 1)
                    result[rule[0].trim()] = rule[1].trim();
            }
            
            return result;
        }
    }
    $.fn.admin_template_editor = function(platform) {
        if (platform == "mail")
            $(this).css({"width" : "750px", "margin" : "auto"});
        
        var ops = {
            input: $(this).children("textarea"),
            input_cid: $(this).children("input"),
            platform: platform,
            image_mode: (platform == "mail") ? "fix" : "adaptive",
            fixate_images: platform == "mail",
            fixate_width: 750,
            onLoad: function(frag) {
                if (!frag.properties) return;
                
                var url = frag.properties.url;
                if (url && url.indexOf("http") != 0) 
                    frag.properties.url = doc_base + url;
            },
            onProperties: function(template, properties, ops) {
                var media = (typeof ops.media == "object") ? Object.keys(ops.media) : [];
                $.overlay_ajax(doc_base + "Admin/Ajax_TemplateProp", false, {
                     name: template.attr("data-name"),
                     media: media,
                     props: properties,
                }, {
                    width: 0.5,
                    target: template,
                    height: 0.8,
                    scrollable: true,
                    onLoaded: function(ops) {
                        $(this).ac_create(); 
                        $.admin.template_editor.properties_editor.apply(this, [ops.target]);  
                    },
                    onClose: function(r, data, ops) {
                        if (!r) return;
                        
                        $.admin.template_editor.set_properties.apply(this, [ops.target]);                        
                    }
                });
            },
            onEdit: function(elem, editor) {
                if (editor == "html" || editor == "area") {     
                    elem.admin_popup_editor("Content", elem.html(), function(content) {
                        $(this).rl_editor("set_content", content);
                    }, (editor == "area") ? "data-menu='format,size,style,align'" : ""); 
                } else 
                if (editor == "text") {
                    $.overlay({
                        mode: "inline",
                        title: "Edit text",
                        content: "<input type='text'>",
                        buttons: { "ok" : {}, "cancel" : {}},
                        target: elem,
                        form: true,
                        onLoaded: function(ops) {
                            $(this).find(".overlay_content").find("input").ac_edit({value: ops.target.html()});    
                        },
                        onClose: function(r, data, ops) {
                            if (r) ops.target.rl_editor("set_content", $(this).find("input").ac_value()); 
                        }
                    });    
                } else
                if (editor == "area") {
                    $.overlay({
                        mode: "inline",
                        title: "Edit text",
                        content: "<input data-lines='6'>",
                        buttons: { "ok" : { key: null }, "cancel" : {}},
                        target: elem,
                        form: true,
                        onLoaded: function(ops) {
                            $(this).find("input").ac_edit({value: ops.target.html()});    
                        },
                        onClose: function(r, data, ops) {
                            if (r) ops.target.rl_editor("set_content", $(this).find("input").ac_value()); 
                        }
                    });    
                } else           
                if (editor == "image") {
                    elem.admin_browse(function(files) {
                        var src = 'media/uploads/' + files[0];
                        var editor = $(this);
                        
                        var img = new Image();
                        img.src = src;
                        img.onload = function() {
                            editor.rl_editor("set_content", "<img src='" + src + "'>");
                        }
                    }, null);
                }
            },
        }
        var attr, attrs = $(this)[0].attributes;
        for (var i = 0; i < attrs.length; i++) {
            attr = attrs[i];
            ops[attr.name.replace("data-", "")] = attr.value;
        }
        
        $(this).responsive_layout_editor(ops);
    }
                
    $.admin.editor = {
        def_options: {
            plugins: 'image,code,link',
            promotion: false,
            menubar: false,
            toolbar: 'styles fontsize | bold italic underline strikethrough | link image media table | align | checklist numlist bullist indent outdent | emoticons charmap | removeformat code',
            image_advtab: true,
            preview_styles: false,
            style_formats_merge: true,
            branding: false,
            file_picker_types: 'image',
            relative_urls : false,
            remove_script_host : true,
            file_picker_callback: function (cb, value, meta) {
                $(this).admin_browse(function(files) {
                    var path = admin_site_domain.replace(/\/$/, '') + admin_upload_path + files[0];
                    cb(path);
                }, {zindex: 10020});			
            },
            setup: function(ed) {
                ed.on('change keyup', function(e) {
                    tinymce.triggerSave();
                });
            //  Add browser button to link
                ed.on('ExecCommand', function(e) {
                    if (e.command === 'mceLink') {                
                        setTimeout(function() {
                            var dialog = $(".tox-dialog__body-content");
                            var input = dialog.find('.tox-control-wrap .tox-textfield');  
                            
                            input.css("width", "90%");
                            var html = '<button style="top: 5px; left: 5px" title="Browse files" type="button" tabindex="-1" data-alloy-tabstop="true" class="tox-button tox-button--icon tox-button--naked tox-browse-url"><span class="tox-icon tox-tbtn__icon-wrap"><svg width="24" height="24" focusable="false"><path d="M19 4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4v-2h4V8H5v10h4v2H5a2 2 0 0 1-2-2V6c0-1.1.9-2 2-2h14Zm-8 9.4-2.3 2.3a1 1 0 1 1-1.4-1.4l4-4a1 1 0 0 1 1.4 0l4 4a1 1 0 0 1-1.4 1.4L13 13.4V20a1 1 0 0 1-2 0v-6.6Z" fill-rule="nonzero"></path></svg></span></button>';
                            var button = $(html).insertAfter(input);
                            
                            button.click(function() {
                                $(this).admin_browse(function(files, data) {
                                    var path = admin_site_domain.replace(/\/$/, '') + admin_upload_path + files[0];
                                    data.target.val(path);
                                    
                                }, {zindex: 10020}, { target: input });			
                            });
                        }, 1);                    
                    }
                });
            }	            
        }
    }
    
    $.fn.admin_editor = function(options) {   
        if ($(this).hasClass("admin_initialized")) return;

        var ops = $.extend({}, $.admin.editor.def_options, options); 
        ops.target = $(this).children("textarea")[0];
        
        tinymce.init(ops);
        $(this).addClass("admin_initialized");
    }
    $.fn.admin_popup_editor = function(title, content, onSuccess, attr, data) {
        if (typeof content == "undefined") content = "";
        
        if (!attr) attr = "";
        $.overlay({
            mode: "inline",
            width: 0.8,
            height: 0.8,
            min_height: 200,
            modal: true,
            content: "<div class='admin_html_editor' " + attr + "><textarea>" + content + "</textarea></div>",                          
            title: title, 
            editor: {
                target: $(this),
                callback: onSuccess,
                data: data,
            },
            buttons: {"ok": {}, "cancel": {}
            },
            onClose: function(r) {
                var ov = $(this);
                var ops = ov.data("overlay").editor;    
                var ed = tinymce.activeEditor;
                if (r && typeof ops.callback == "function") {
                    ops.callback.apply(ops.target, [ed.getContent(), ops.data]); 
                }

                tinymce.remove(ed);
            },
            onLoaded: function() {    
                var editor = $(this).find(".admin_html_editor");
                editor.css({
                    position: "absolute",
                    left: 5,
                    top: 5,
                    right: 5,
                    bottom: 5
                });
                editor.admin_editor(); 
                //console.log($(".tox_menu"));
            },  
        });        
    }   
    
    $.admin.edittree = {
        trim_brackets: function(str) {
            var i1 = 0;
            var i2 = str.length;
            if (str[0] == "[") i1++;
            if (str[str.length-1] == "]") i2--;
            
            return str.substring(i1,i2);
        },
        add: function(elem, type) { 
            if ($(elem).hasClass("admin_edittree")) {
                var tree = $(elem);
                var target = tree.children(".admin_et_root");
            } else {
                var tree = $(elem).closest(".admin_edittree");
                
                var target = $(elem);
                if (!target.hasClass("admin_et_node"))
                    target = target.parentsUntil(".admin_edittree", ".admin_et_node:first"); 
                
                if (!target.length)
                    target = tree;
            }         
            
            if (target.hasClass("admin_edittree"))
                target = target.children(".dyntree");
            
            var temp = tree.children("div.admin_et_templates").children("ul[data-type=" + type + "]");
            if (!temp.length) return $();
            
            var node = temp.children("li").clone(true, true);
            target.dynamic_tree("add", {content: node, position: "inside_last"}); 
                
            node.addClass("admin_et_node").ac_create();
            node.children(".dyntree_list").addClass("admin_et_nodes");
            $(tree).trigger("admin_edittree_add", [node, type, target]);
            
            return node;
        },
        remove: function(elem) {
            var node = $(elem).closest(".admin_et_node");
            var type = node.attr("data-type");
            var tree = node.parents(".admin_edittree:first");
            var deleted = tree.children(".admin_et_deleted");
            var tree_name = tree.attr("data-name");
            var ops = tree.children(".dyntree").data("dyntree");
            
            var id = node.attr("data-id");
            if (id) 
                $("<input type='hidden' name='" + tree_name + "_delete_" + type + "[]' value='" + id + "'>").appendTo(deleted);
            
            $(elem).dynamic_tree("remove");  
            if (typeof ops.onRemove == "function")
                ops.onRemove.apply(tree);
        },
        update: function(i, parent) {         
            var item = $(this).children("div");
            var path;
            
            if (parent) 
                path = parent.children("div").attr("data-path"); else 
                path = item.parents(".admin_edittree:first").attr("data-name");
            
            if (name = item.attr("data-name"))
                path+= "[" + name + "]";
                
            path+= "[" + i + "]";
            item.attr("data-path", path);  
            item.find("input[data-name], .ac_loading[data-name]").each(function() {
                var fn = $.admin.edittree.trim_brackets($(this).attr("data-fname"));
                
                $(this).attr("name", path + "[" + fn + "]").acontrol(function() {
                    var vid = $(this).closest(".acontrol").find(".ace_value_id");
                    if (!vid.length) return;
                    
                    var hn = vid.attr("name");
                    vid.attr("name", path + "[" + hn + "]");
                });
            });           
        }
    }
    $.fn.admin_edittree = function() {
        var tree = $(this).children("div.admin_et_root");
        var temp = $(this).children("div.admin_et_templates");
                                                     
        $(this).find("input, select, textarea, .ac_loading").each(function() {
            var name = $(this).attr("name");
            if (!name)
                name = $(this).attr("data-name");
            
            if (!name) return;
            
            $(this).attr("data-name", name);
            $(this).removeAttr("name").attr("data-fname", name);
        });
        tree.dynamic_tree({
            expandable: false,
            loaded: function() {
                //$(this).find(".admin_et_item").ac_create();
                $(this).trigger("admin_edittree_loaded");
            },
            nodeUpdate: $.admin.edittree.update
        });
    } 
    $.fn.admin_slideshow = function() {
        $(this).addClass("admin_slideshow");
        var tree = $(this).children("div.admin_et_root");
        var temp = $(this).children("div.admin_et_templates");
        var menu = $(this).children("div.admin_et_menu");
        
        var curr = tree.children(".admin_et_nodes").children("li").length;
        var count = parseInt($(this).attr("data-count"));
        if (count && curr >= count) 
            menu.children(".admin_button_add").addClass("disabled"); 
        
        menu.children(".admin_button_add").click(function() {
            if ($(this).hasClass("disabled")) return false;
            
            $(this).admin_browse(function(paths) {
                var list = $(this).closest(".admin_slideshow");
                var count = parseInt(list.attr("data-count"));
                var curr = list.find(".admin_et_nodes").children("li").length;
                
                var node,item,ids = [];
                var cnt = curr;
                for (var i = 0; i < paths.length; i++) {
                    node = $.admin.edittree.add(this, 'slide');
                    item = node.children(".admin_et_item");
                    
                    item.find(".admin_eti_title").html(paths[i]);
                    item.find("input[data-name=path]").val(paths[i]);
                    
                    ids.push(node.attr("id"));
                    cnt++;
                    if (count && cnt >= count) break;
                }
                
                if (count && cnt >= count) {
                    list.children(".admin_et_menu").children(".admin_button_add").addClass("disabled");    
                }
                
                $.post(doc_base + "Admin/Ajax_Thumbnail", { 
                    paths: paths,
                    ids: ids,
                    template: "admin_imagelist"
                }, function(r) {
                    for (var id in r) 
                        $("#" + id).children(".admin_et_item").find(".admin_eti_image .thumbnail_image").css("background-image", "url('" + r[id] + "')");
                }, "json");    
            }); 
        });
        
        $(this).find("input, select, textarea, .ac_loading").each(function() {
            var name = $(this).attr("name");
            if (!name)
                name = $(this).attr("data-name");
            
            if (!name) return;
            
            $(this).removeAttr("name").attr("data-fname", name);
        });
        tree.dynamic_tree({
            expandable: false,
            loaded: function() {
                //$(this).find(".admin_et_item").ac_create();
            },
            onRemove: function() {   
                var count = $(this).attr("data-count");
                var curr = $(this).find(".admin_et_nodes").children("li").length; 
                if (curr < count) 
                    $(this).children(".admin_et_menu").children(".admin_button_add").removeClass("disabled");    
            },
            nodeUpdate: $.admin.edittree.update
        });
    }
    $.fn.admin_imagelist = function() {
        $(this).addClass("admin_imagelist");
        var tree = $(this).children("div.admin_et_root");
        var temp = $(this).children("div.admin_et_templates");
        var menu = $(this).children("div.admin_et_menu");
        
        var curr = tree.children(".admin_et_nodes").children("li").length;
        var count = parseInt($(this).attr("data-count"));
        if (count && curr >= count) 
            menu.children(".admin_button_add").addClass("disabled"); 
        
        menu.children(".admin_button_add").click(function() {
            if ($(this).hasClass("disabled")) return false;
            
            $(this).admin_browse(function(paths) {
                var list = $(this).closest(".admin_imagelist");
                var count = parseInt(list.attr("data-count"));
                var curr = list.find(".admin_et_nodes").children("li").length;
                
                var node,item,ids = [];
                var cnt = curr;
                for (var i = 0; i < paths.length; i++) {
                    node = $.admin.edittree.add(this, 'image');
                    item = node.children(".admin_et_item");
                    
                    item.find(".admin_eti_title").html(paths[i]);
                    item.find("input[data-name=path]").val(paths[i]);
                    
                    ids.push(node.attr("id"));
                    cnt++;
                    if (count && cnt >= count) break;
                }
                
                if (count && cnt >= count) {
                    list.children(".admin_et_menu").children(".admin_button_add").addClass("disabled");    
                }
                
                $.post(doc_base + "Admin/Ajax_Thumbnail", { 
                    paths: paths,
                    ids: ids,
                    template: "admin_imagelist"
                }, function(r) {
                    for (var id in r) 
                        $("#" + id).children(".admin_et_item").find(".admin_eti_image .thumbnail_image").css("background-image", "url('" + r[id] + "')");
                }, "json");    
            }); 
        });
        
        $(this).find("input, select, textarea, .ac_loading").each(function() {
            var name = $(this).attr("name");
            if (!name)
                name = $(this).attr("data-name");
            
            if (!name) return;
            
            $(this).removeAttr("name").attr("data-fname", name);
        });
        tree.dynamic_tree({
            expandable: false,
            loaded: function() {
                //$(this).find(".admin_et_item").ac_create();
            },
            onRemove: function() {   
                var count = $(this).attr("data-count");
                var curr = $(this).find(".admin_et_nodes").children("li").length; 
                if (curr < count) 
                    $(this).children(".admin_et_menu").children(".admin_button_add").removeClass("disabled");    
            },
            nodeUpdate: $.admin.edittree.update
        });
    }
    $.fn.admin_filelist = function() {
        $(this).addClass("admin_filelist");
        var tree = $(this).children("div.admin_et_root");
        var temp = $(this).children("div.admin_et_templates");
        var menu = $(this).children("div.admin_et_menu");
        
        menu.children(".admin_button_add").click(function() {
            $(this).admin_browse(function(paths) {
                var node,item,ids = [];
                for (var i = 0; i < paths.length; i++) {
                    node = $.admin.edittree.add(this, 'image');
                    var item = node.children(".admin_et_item");
                    
                    var path = paths[i];
                    var s = path.split(".");
                    if (s.length)
                        item.find(".admin_eti_file").html("<label>" + s[s.length-1] + "</label>");
                    
                    item.find(".admin_eti_title").html(path);
                    item.find("input[data-name=path]").val(path);
                    
                    ids.push(node.attr("id"));
                }
            }); 
        });
        
        $(this).find("input, select, textarea, .ac_loading").each(function() {
            var name = $(this).attr("name");
            if (!name)
                name = $(this).attr("data-name");
            
            if (!name) return;
            
            $(this).removeAttr("name").attr("data-fname", name);
        });
        tree.dynamic_tree({
            expandable: false,
            loaded: function() {
                //$(this).find(".admin_et_item").ac_create();
            },
            nodeUpdate: $.admin.edittree.update
        });
    }  
    
    $.admin.monitor = {
        ajax_monitors: {},
        ajax_running: false,
        ajax_timeout: null,
        ajax_exec: function() {
            $.admin.monitor.ajax_running = true;

            var m;
            var names = [];
            for (var name in $.admin.monitor.ajax_monitors) {
                m = $.admin.monitor.ajax_monitors[name];
                if (!m.length || !m.is(":visible")) continue;
                if (m.hasClass("disabled")) continue;
                
                names.push(name);
            }
            
            $.post(doc_base + "Tasks/Ajax_ProcessMonitor", { name: names }, function(r) {
                var monitor, status, prog, text;
                var running = false;
                for (var name in r) {
                    monitor = $.admin.monitor.ajax_monitors[name];
                    if (!monitor) {
                        delete $.admin.monitor.ajax_monitors[name];
                        continue;    
                    }
                    if (monitor.hasClass("disabled")) continue;
                    status = r[name];
                    if (status.status == "running" || status.status == "idle")
                        running = true;
                        
                    $.admin.monitor.update(monitor, status);
                    
                    clearTimeout($.admin.monitor.ajax_timeout);
                    
                    var t = Math.floor(Date.now() / 1000);
                    if (t - $.admin.monitor.ajax_last < 120) 
                        $.admin.monitor.ajax_timeout = setTimeout(function() { $.admin.monitor.ajax_exec() }, (running) ? 1000 : 5000); else
                        $.admin.monitor.ajax_running = false;
                }    
            }, "json");
            
        },
        ajax_start: function() {
            $.admin.monitor.ajax_exec();
            $.admin.monitor.ajax_last = Math.floor(Date.now() / 1000);
            
            $(window).mousemove(function() {
                $.admin.monitor.ajax_last = Math.floor(Date.now() / 1000);
                if (!$.admin.monitor.ajax_running)
                    $.admin.monitor.ajax_exec();
            });
        },
        ajax_focus: function() {
            clearTimeout($.admin.monitor.ajax_timeout); 
            $.admin.monitor.ajax_exec();        
        },
        ajax_last: 0,
        update: function(monitor, status, animate) {
            var active = status.status == "running" || status.status == "idle";

            if (monitor.hasClass("error") && !active) return;
            if (monitor.hasClass("stopped") && status.status != "none" && !active) return;
            
            monitor.removeClass("none idle running stopped dead paused error");
                
            var states = monitor.attr("data-states");
            if (states && states.indexOf(status.status) == -1)
                status.status = "none";
            
            var old_status = monitor.data("old_status");
            var prog = monitor.addClass(status.status).children(".admin_monitor_progress").stop();
            
            if (old_status != status.status && old_status != "running" && old_status != "idle")
                prog.css({width: status.progress + "%"}); 
            else {
                if (typeof animate == "undefined") animate = true;
                prog.animate({width: status.progress + "%"}, (animate) ? 1200 : 100, "linear");
            }
            monitor.data("old_status", status.status);
            
            var ctrls = monitor.children(".admin_monitor_controls");
            ctrls.children("a").removeClass("disabled").hide();
            ctrls.children("a[data-mask*=" + status.status + "]").show();
               
            var text;
            if (status.status == "none")
                text = monitor.attr("data-text-passive"); else
            if (status.status == "finished")
                text = monitor.attr("data-text-finished"); else
                text = monitor.attr("data-text-active");
            
            if (typeof text == "undefined") text = "";
            
            if (status.message)
                text+= status.message;
            
            if (status.status != "none") {
                /*
                if (status.index && status.count) {
                    var ind = parseInt(status.index) + 1;
                    var cnt = status.count;
                    
                    text+= ind + "/" + cnt + " (" + status.progress + "%)"; 
                } else*/
                if (status.progress)
                    text+= " (" + status.progress + "%)";
                    
                if (status.elapsed) {
                    text+= ", " + trans_admin_elapsed + ":";
                    if (status.elapsed[0]) text+= " " + status.elapsed[0] + "h";
                    if (status.elapsed[1]) text+= " " + status.elapsed[1] + "m";
                    if (status.elapsed[2]) text+= " " + status.elapsed[2] + "s";
                }

                if ((status.status == "running" || status.status == "idle") && status.remaining) {
                    text+= ", " + trans_admin_remaining + ":";
                    if (status.remaining[0]) text+= " " + status.remaining[0] + "h";
                    if (status.remaining[1]) text+= " " + status.remaining[1] + "m";
                    if (status.remaining[2]) text+= " " + status.remaining[2] + "s";
                }
            }
                        
            monitor.children(".admin_monitor_text").html(text);
            monitor.trigger("monitor_state", [status]);
        },
        listener: function(id, status) {
            var monitor = $("#" + id);
            $.admin.monitor.update(monitor, status);
            
            if (status.status == "none" || status.status == "finished")
                monitor.children("iframe").attr("src", "");
                
        }
    }
    $.fn.admin_monitor = function(mode) {
        if (mode == "ajax") {
            $.admin.monitor.ajax_monitors[$(this).attr("data-name")] = $(this);
        } else 
        if (mode == "realtime") {
            var iframe = $(this).children("iframe");
            var id = $(this).attr("id");
            var callback = id.replace("admin_", "call_");
            iframe.attr("src", doc_base + 'Tasks/ProcessMonitor/' + $(this).attr("data-name") + "/" + callback);
        }
        
        var ctrls = $(this).children(".admin_monitor_controls");
        ctrls.children("a").click(function() {
            if ($(this).hasClass("disabled")) return false;
            
            var name = $(this).parents(".admin_monitor:first").attr("data-name");
            $(this).parent().children("a").addClass("disabled");
            
            var action = $(this).attr("data-action");
            var url = null;
            switch (action) {
                case "stop": url = doc_base + "Tasks/StopProcess/" + name; break;
                case "kill": url = doc_base + "Tasks/KillProcess/" + name; break;
                case "resume": url = doc_base + "Tasks/ResumeProcess/" + name; break;
            }
            if (url)
                $.post(url, {}, function() { $.admin.monitor.ajax_focus() });
        });
    } 
    
    $.admin.upload = {
        target: null,   
    }
    $.fn.admin_upload_button = function() {
        $.admin.upload.target = $(this);
        $(this).fileupload({
            url: doc_base + "Admin/Ajax_Upload",

            add: function(e, data) {
                var ov = $.overlay_ajax(doc_base + "Admin/Ajax_Upload", "ajax", { name: data.files[0].name }, {
                    onDisplay: function(ops) {
                    },
                    onClose: function() {
                        var xhr = $(this).data("upload_xhr");
                        xhr.cancelled = true;
                        xhr.abort();                
                    }    
                });
                
                data._overlay = ov;  
                var xhr = data.submit();
                
                ov.data("upload_xhr", xhr);
            },
            progress: function(e, data) {
                var ov = data._overlay;
                if (!ov) return;
                
                var monitor = ov.find(".admin_monitor");
                var status = {
                    status: "running",
                    progress: parseInt(100 * data.loaded / data.total),
                    index: parseInt(data.loaded / 1024),
                    count: parseInt(data.total / 1025),    
                }
                $.admin.monitor.update(monitor, status);
            },
            done: function(e, data) {
                var ov = data._overlay;
                if (!ov) return;
                
                var monitor = ov.find(".admin_monitor");
                var status = {
                    status: "finished",
                    progress: 100,
                }
                $.admin.monitor.update(monitor, status, false);
                
                setTimeout(function() { 
                    $.overlay_close();
                
                    var callback = $.admin.upload.target.attr("data-callback");
                    if (typeof window[callback] == "function")
                        window[callback].apply($.admin.upload.target, [data]);
                }, 500);
            },
            fail: function (e, data) {       
                $.overlay_close();                
                
                var xhr = data.jqXHR;                
                if (!xhr.cancelled)
                    $.overlay_message("<div class='admin_message failure'>Error occured during upload</div>");
            },
        });     
    } 
    
    $.admin.tageditor = {
        cmp: function(a, b) {
            var x = a.title.toLowerCase();
            var y = b.title.toLowerCase();
            
            return x < y ? -1 : x > y ? 1 : 0;
        },
        add: function(editor, items) {
            var sorted = items.slice(0);
            
            var ul = $(this).children("ul");
            var ids = [];
            ul.children("li").each(function() {
                sorted.push({ id: $(this).attr("data-value"), title: $(this).html()});
                ids.push($(this).attr("data-value"));
            });
            
            sorted.sort($.admin.tageditor.cmp);
            
            var item,html = "";
            for (var i in sorted) {
                item = sorted[i];
                if (ids.indexOf(item.id) != -1) continue;
                
                html+= "<li data-value='" + item.id + "'>" + item.title + "<a onclick='return \$.admin.tageditor.remove.apply(this)'></a></li>";
                ids.push(item.id);
            }
            
            editor.children("ul").append(html);
            
            $.admin.tageditor.update(editor);
        },
        remove: function(item) {
            var ed = $(this).closest(".admin_tageditor");
            
            $(this).parent().remove();     
            $.admin.tageditor.update(ed);
        },
        update: function(editor) {
            var ul = editor.children("ul");
            var val = [];
            ul.children("li").each(function() {
                val.push($(this).attr("data-value"));
            });
            
            editor.children("input").val(val.join(","));
        }    
    },
    $.fn.admin_tageditor = function() {
    },
    
    $.admin.lang_selector = {
        toggle: function() {
            var lang = $(this).attr("data-lang");
            var sel = $(this).parent();
            $("#module_form_lang").val(lang);
            
            $(".admin_lang_selector > span").removeClass("selected");
            $(".admin_lang_selector > span[data-lang=" + lang + "]").addClass("selected");

            $(".admin_content_ml").removeClass("selected");
            $(".admin_content_ml[data-lang=" + lang + "]").addClass("selected");            
        },
        copy: function(sel, src, dst) {
            if (sel.hasClass("admin_edit")) {
                $.admin.lang_selector.copy_edit(sel, src, dst);
            } else
            if (sel.hasClass("admin_select")) {
                $.admin.lang_selector.copy_select(sel, src, dst);
            } else
            if (sel.hasClass("admin_tmp_edit")) {
                $.admin.lang_selector.copy_tmp_edit(sel, src, dst);
            } else
            if (sel.hasClass("admin_html_edit")) {
                $.admin.lang_selector.copy_html_edit(sel, src, dst);
            }
        },
        copy_edit: function(sel, src, dst) {
            var c = sel.parent();
            var val = c.find(".admin_content_ml.selected .ac_edit").ac_value();
            var mask = ".admin_content_ml:not(.selected)";
            if (dst != "_all" && dst != "_empty") 
                mask+= "[data-lang=" + dst + "]";
            
            c.find(mask + " .ac_edit").each(function() {
                if (dst == "_empty" && $(this).ac_value() != "") return;
                
                $(this).ac_value(val);
            });
        },
        copy_select: function(sel, src, dst) {
            var c = sel.parent();
            var val = c.find(".admin_content_ml.selected .ac_select").ac_value();
            var mask = ".admin_content_ml:not(.selected)";
            if (dst != "_all" && dst != "_empty") 
                mask+= "[data-lang=" + dst + "]";
            
            c.find(mask + " .ac_select").each(function() {
                if (dst == "_empty" && $(this).ac_value() != "") return;
                
                $(this).ac_value(val);
            });
        },        
        copy_html_edit: function(sel, src, dst) {
            var c = sel.parent();
            var val = c.find(".admin_content_ml.selected textarea").val();

            var mask = ".admin_content_ml:not(.selected)";
            if (dst != "_all" && dst != "_empty") 
                mask+= "[data-lang=" + dst + "]";
            
            c.find(mask + " textarea").each(function() {
                var mce = tinymce.get($(this).attr("id"));
                var cv = mce.getContent();
                if (dst == "_empty" && cv != "") return;
                
                mce.setContent(val);
            });
        },        
        copy_tmp_edit: function(sel, src, dst) {
            var c = sel.parent();
            var val = c.find(".rl_editor").rl_editor("get_value");
            var mask = ".admin_content_ml:not(.selected)";
            if (dst != "_all" && dst != "_empty") 
                mask+= "[data-lang=" + dst + "]";
            
            c.find(mask + " .admin_template_editor").each(function() {
                var cv = $(this).rl_editor("get_value");
                if (dst == "_empty" && cv != "") return;
                
                $(this).rl_editor("set_value", val);
            });
        },
        context_menu: function(sel, src, e) {
            var buttons = sel.children(".admin_lang_button:not(.selected)");
            var src_lang = src.attr("data-lang");
            var src_icon = "<span class='admin_lang_flag' data-lang='" + src_lang + "'>" + src.html() + "</span>";
            var items = [{
                attr: "data-dst='_all' data-src='" + src_lang + "'",
                content: src_icon + "&rarr; *",
            }, {
                attr: "data-dst='_empty' data-src='" + src_lang + "'",
                content: src_icon + "&rarr; ''",
            }];
        
            buttons.each(function() {
                var dst_lang = $(this).attr("data-lang");
                if (src_lang == dst_lang) return;
                
                var dst_icon = "<span class='admin_lang_flag' data-lang='" + dst_lang + "'>" + $(this).html() + "</span>";
                items.push({
                    attr: "data-dst='" + dst_lang + "' data-src='" + src_lang + "'",
                    content: src_icon + "&rarr; " + dst_icon
                });
            });    
            

            sel.contextmenu(e.clientX + 5, e.clientY + 5, items, {
                icons: false,
                onClick: function(sel, name, x, y) {
                    var src = $(this).attr("data-src");
                    var dst = $(this).attr("data-dst");
                    
                    $.admin.lang_selector.copy(sel, src, dst);
                }
            });
        }
    },
    $.fn.admin_lang_selector = function() {
        $(this).find(".admin_lang_button").click(function() {
            var sel = $(this).closest(".admin_lang_selector");
            if (sel.hasClass("expanded")) return false;
            
            $.admin.lang_selector.toggle.apply(this);
        });
        
        $(this).find(".admin_expand").click(function(e) {
            var sel = $(this).closest(".admin_lang_selector");
            if (sel.hasClass("expanded")) {
                sel.removeClass("expanded");
                sel.parent().find(".admin_content_ml").removeClass("expanded");
            } else {
                sel.addClass("expanded");
                sel.parent().find(".admin_content_ml").addClass("expanded");
            }
        });
        $(this).children(".admin_lang_button").bind("contextmenu", function(e) {
            var sel = $(this).closest(".admin_lang_selector");
            if (sel.hasClass("expanded")) return false;
            
            $.admin.lang_selector.toggle.apply(this);
            $.admin.lang_selector.context_menu(sel, $(this), e);
            
            return false;
        });
        $(this).parent().children(".admin_content_ml").children(".admin_lang_flag").bind("contextmenu", function(e) {
            var cont = $(this).closest(".admin_field_content");
            var sel = cont.children();
            
            $.admin.lang_selector.context_menu(sel, $(this), e);
            
            return false;
        });        
    }
})( jQuery );      

function admin_redirect(obj) {
    if ($.isString(obj)) { 
        var url = obj; 
    } else {
        var url = $(obj).attr("href");
        if (!url)
            url = $(obj).attr("data-url");
    }
    
    $("#header_dropdown").hide();
        
    if (url)
        $.admin.reload_module(url, true);
     
    return false;    
}

function admin_func_eval(func) {
    try {
        eval(func);
    } catch(err) {
        return false;    
    }	
}

function admin_icon_click(func, confirm) {
    var href = $(this).attr("href");
    var context = this;

    if (func == '' && confirm == '')
        window.location.href = href; else
    if (func == '' && confirm != '') {
        $.overlay_confirm(confirm, function() {
            window.location.href = href;
        });	
    } else
    if (func != '' && confirm == '') {
        admin_func_eval.apply(context, [func]); 
    } else {
        $.overlay_confirm(confirm, function() {
            admin_func_eval.apply(context, [func]);
        });    
    }
    return false;        
}

$.ajaxSetup({
    beforeSend: function(jqXHR) {
        $.admin.xhr_pool.push(jqXHR);
    },
    complete: function(jqXHR) {
        var index = $.admin.xhr_pool.indexOf(jqXHR);
        if (index > -1) {
            $.admin.xhr_pool.splice(index, 1);
        }
    }
});

