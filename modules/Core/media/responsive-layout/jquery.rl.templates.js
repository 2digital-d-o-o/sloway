(function( $ ){
    $.rleditor.img_to_adaptive = function(img_mode) {
        if (!img_mode) img_mode = "";
        $(this).find("img").each(function() {
             var template = $(this).closest(".rl_template");
             var alt = template.attr("data_attr_img_alt");
             if (!alt) alt = "";
             
             var title = template.findExclude(".rl_template_element[data-name=img_title]", ".rle_fragment").html();
             if (!alt) alt = title;
             
             var lazy = template.hasClass("rl_class_ajax");
             var popup = template.hasClass("rl_class_popup");
             var path = $(this).attr("src");
             
             var pad = 100 * this.naturalHeight / this.naturalWidth;
             
             var style1 = "";
             var style2 = "";
             if (!template.hasClass("rl_framed")) {
                style1 = "style='max-height: " + this.naturalHeight + "px; width: " + this.naturalWidth + "px; max-width: 100%'";                
                style2 = "";//"width: " + this.naturalWidth + "px; max-width: 100%";                
             }
             
             var cont = $(this).parents(".rl_image_wrapper:first");
             cont.css({"width" : this.naturalWidth + "px", "max-width" : "100%"});
                       
             var html = "";            
             if (popup)
                html = "<a class='adaptive_image image_popup' href='" + path + "' data-path='" +  path + "' data-alt='" + alt + "' rel='__gallery' title='" + title + "' " + style1 + ">"; else
                html = "<div class='adaptive_image' data-path='" +  path + "' data-alt='" + alt + "' data-mode='" + img_mode + "' " + style1 + ">";

             html+= "<span style='padding-top: " + pad.toFixed(2) + "%; " + style2 + "'></span>";
             if (!lazy) 
                html+= "<img src='" + path + "' alt='" + alt + "'>";
             
             if (popup)
                html+= "</a>"; else
                html+= "</div>";
                
             $(this).replaceWith(html);
        });
    }
    $.rleditor.adaptive_to_img = function() {
        $(this).find(".adaptive_image").each(function() {
            var html = "<img src='" + $(this).attr("data-path") + "'>";
            $(this).replaceWith(html);
        });
    } 
    $.rleditor.options.templates.column2 = {
        onUpdate: function(temp, props) { 
            var eq = temp.attr("data_attr_eq");
            var left = temp.findExclude(".rl_left", ".rle_fragment").children(".rl_content");
            var right = temp.findExclude(".rl_right", ".rle_fragment").children(".rl_content");
            
            var left_temps = left.children("ul").children("li").findExclude(".rl_template", ".rle_fragment");            
            var right_temps = right.children("ul").children("li").findExclude(".rl_template", ".rle_fragment");            
            
            left.toggleClass("rl_frame", eq == "right");
            right.toggleClass("rl_frame", eq == "left");
            
            left_temps.toggleClass("rl_framed", eq == "right");
            right_temps.toggleClass("rl_framed", eq == "left");
        },    
    }
    $.rleditor.options.templates.grid11 = {
        onUpdate: function(temp, props) { temp.find(".rl_template").addClass("rl_framed") }
    }
    $.rleditor.options.templates.grid12 = {
        onUpdate: function(temp, props) { temp.find(".rl_template").addClass("rl_framed") }
    }
    $.rleditor.options.templates.grid21 = {
        onUpdate: function(temp, props) { temp.find(".rl_template").addClass("rl_framed") }
    }
    $.rleditor.options.templates.grid22 = {
        onUpdate: function(temp, props) { temp.find(".rl_template").addClass("rl_framed") }
    }
    $.rleditor.options.templates.image = {
        onLoad: function(temp, level, ops) { 
            if (ops.image_mode == "adaptive")
                $.rleditor.adaptive_to_img.apply(this) },
        onOutput: function(temp, level, ops) { 
            if (ops.image_mode == "adaptive")
                $.rleditor.img_to_adaptive.apply(temp); 
            
            temp.clear_ws(); 
        },
        onCreate: function(ops, new_frag) {
            if (new_frag) {
                var parent = $(this).parents(".rl_template_frame:first");
                if (parent.length) {
                    $(this).find(".rl_template").addClass("rl_class_cover");                
                }
                
                $(this).find(".rl_editable[data-name=img]").click();
            }
        }
    }
    $.rleditor.options.templates.banner = {
        onLoad: function(temp, level, ops) { $.rleditor.adaptive_to_img.apply(this) },
        onOutput: function(temp, level, ops) { $.rleditor.img_to_adaptive.apply(temp, ["cover"]) },
        onCreate: function(ops, new_frag) {
            if (new_frag) {
                var parent = $(this).parents(".rl_template_frame:first");
                if (parent.length) {
                    $(this).find(".rl_template").addClass("rl_class_cover");                
                }
                $(this).find(".rl_editable[data-name=img]").click();
            }
        }
    }   
    $.rleditor.options.templates.text = { 
        onCreate: function(ops, new_frag) {
            if (new_frag) $(this).find(".rl_editable[data-name=content]").click();
        }
    }
    $.rleditor.options.templates.image_text = {
        onLoad: function(temp, level, ops) { $.rleditor.adaptive_to_img.apply(this) },
        onOutput: function(temp, level, ops) { $.rleditor.img_to_adaptive.apply(temp) },
        onCreate: function(ops, new_frag) {
            if (new_frag) $(this).find(".rl_editable[data-name=img]").click();
        }
    }
    $.rleditor.options.templates.text_image = {
        onLoad: function(temp, level, ops) { $.rleditor.adaptive_to_img.apply(this) },
        onOutput: function(temp, level, ops) { $.rleditor.img_to_adaptive.apply(temp) },
        onCreate: function(ops, new_frag) {
            if (new_frag) $(this).find(".rl_editable[data-name=img]").click();
        }
    }
    $.rleditor.image_list = {
        edit_properties: function(e) {
            var list_ops = $(this).closest(".rl_template").data("list-ops");
            var edit_ops = $.extend({}, { title: false, desc: false, alt: false, url: false }, list_ops.edit);
            var data = {
                edit: edit_ops,
                title: $(this).children(".admin_grid_item_title").html(),
                desc: $(this).children(".admin_grid_item_desc").html(),
                alt: $(this).children(".admin_grid_item_alt").html(),
                url: $(this).children(".admin_grid_item_url").html(),
            }
            for (var i in data)
                if (!data[i]) data[i] = "";
                
            $.overlay_ajax(doc_base + "Admin/Ajax_TemplateImage", false, data, {
                target: $(this),
                width: 0.5,
                height: 0.8,
                scrollable: true,
                onLoaded: function(ops) {
                    $(this).ac_create(); 
                    $(this).find(".admin_html_editor").admin_editor();
                },
                onClose: function(r, data, ops) {
                    if (!r) return;
                    
                    var target = ops.target;
                    var url = $.rleditor.parse_url($(this).find("[name=url]").val());
                    
                    target.children(".admin_grid_item_title").html($(this).find("[name=title]").val());
                    target.children(".admin_grid_item_desc").html($(this).find("[name=desc]").val());
                    target.children(".admin_grid_item_alt").html($(this).find("[name=alt]").val());
                    target.children(".admin_grid_item_url").html(url);
                    
                    $.rleditor.output(target.closest(".rl_editor"));
                }
            });
        },
        output: function(target, level, ops, list_ops) {
            var grid = $(this).find("." + list_ops.template_class);
            var items = grid.children("ul").children("li.admin_grid_item");
            
            var path, html, item, trg_item, title, desc, url;
            
            var trg_ul = target.children("ul");
            for (var i = 0; i < items.length; i++) {
                item = $(items[i]);
                
                path = admin_uploads_url + item.attr("data-path");
                title = $(item).children(".admin_grid_item_title").html();
                desc = $(item).children(".admin_grid_item_desc").html();
                alt = $(item).children(".admin_grid_item_alt").html();
                url = $(item).children(".admin_grid_item_url").html();
                
                html = "<li data-path='" + path + "'>";
                if (title) html+= "<div class='rl_template_element' data-name='title'>" + title + "</div>";                
                if (desc)  html+= "<div class='rl_template_element' data-name='desc'>" + desc + "</div>";                
                if (url)   html+= "<div class='rl_template_element' data-name='url'>" + url + "</div>";                
                if (alt)   html+= "<div class='rl_template_element' data-name='alt'>" + alt + "</div>";                
                html+= "</li>";
                   
                trg_item = $(html).appendTo(trg_ul);
            } 
        },
        create: function(temp_ops, new_frag, list_ops) {
            var header = $(this).children(".dyntree_item").children(".rle_frag_header");
            header.children(".rle_frag_header_del").hide();
            
            var grid = $(this).find("." + list_ops.template_class).css("min-height", "102px");
            
            grid.data("list-ops", list_ops);
            grid.mouseup(function(e) { 
                if (!$(this).children("ul").children("li.admin_grid_item").length) {
                    var frag = $(this).parents(".rle_fragment:first");
                    var header = frag.children(".dyntree_item").children(".rle_frag_header");
                    
                    header.children(".rle_frag_header_add").click();    
                }    
            });
            
            grid.dynamic_grid({
                mode: "sort",
                onLoadItems: function(items) {
                    var paths = [];
                    var ids = [];
                    var item;
                    for (var i = 0; i < items.length; i++) {
                        item = $(items[i]);
                        paths.push(item.attr("data-path"));
                    }
                    
                    var ul = $(this).children("ul");
                    $.post(doc_base + "Admin/Ajax_Thumbnail", { 
                        paths: paths,
                        template: "admin_gallery_96"
                    }, function(r) {
                        for (var id in r) 
                            ul.children("li[data-path='" + id + "']").children(".admin_grid_item_image").css("background-image", "url('" + r[id] + "')");
                        
                    }, "json");      
                },
                onSelection: function(items) {
                    var frag = $(this).parents(".rle_fragment:first");
                    var header = frag.children(".dyntree_item").children(".rle_frag_header");
                    if (items.length)
                        header.children(".rle_frag_header_del").show(); else
                        header.children(".rle_frag_header_del").hide();
                },
                onDrop: function() {
                    $.rleditor.output($(this).closest(".rl_editor"));
                }           
            });
            
           if (new_frag) 
                header.children(".rle_frag_header_add").click();       
        }, 
        load: function(template, level, ops, list_ops) {
            var html = "", item, path, items = template.children("ul").children("li");
            var item_title, item_url;
            for (var i = 0; i < items.length; i++) {
                item = $(items[i]);    
                
                path = item.attr("data-path").replace(admin_uploads_url, "");
                
                item_title = item.children(".rl_template_element[data-name=title]").html();
                item_desc = item.children(".rl_template_element[data-name=desc]").html();
                item_alt = item.children(".rl_template_element[data-name=alt]").html();
                item_url = item.children(".rl_template_element[data-name=url]").html();
                
                if (!item_title) item_title = "";
                if (!item_desc) item_desc = "";
                if (!item_alt) item_alt = "";
                if (!item_url) item_url = "";

                html+= '<li class="admin_grid_item dyngrid_item dyngrid_pending" data-path="' + path + '">';
                html+= '    <div class="admin_grid_item_image"></div>';
                html+= '    <div class="admin_grid_item_title">' + item_title + '</div>';
                html+= '    <div class="admin_grid_item_desc">' + item_desc + '</div>';
                html+= '    <div class="admin_grid_item_alt">' + item_alt + '</div>';
                html+= '    <div class="admin_grid_item_url">' + item_url + '</div>';
                html+= '</li>';       
            }
            
            var temp = $(this).find("." + list_ops.template_class);
            var ul = temp.children("ul").append(html);
            
            if (list_ops.edit) 
                ul.find(".admin_grid_item_title").click(function(e) {
                    $.rleditor.image_list.edit_properties.apply($(this).closest(".admin_grid_item"));
                    
                    e.stopPropagation();
                    return false;  
                });
            
            temp.dynamic_grid("init_item", ul.children("li"));
        },
        add: function(e, list_ops) {
            var frag = $(this).closest(".rle_fragment");
            var temp = frag.find("." + list_ops.template_class);
            temp.admin_browse(function(paths) {
                for (var i = 0; i < paths.length; i++) {
                    html = '<li class="admin_grid_item dyngrid_item dyngrid_pending">';
                    html+= '    <div class="admin_grid_item_image"></div>';
                    html+= '    <div class="admin_grid_item_title"></div>';
                    html+= '    <div class="admin_grid_item_desc"></div>';
                    html+= '    <div class="admin_grid_item_alt"></div>';
                    html+= '    <div class="admin_grid_item_url"></div>';
                    html+= '</li>';
                    
                    item = $(html).appendTo($(this).children("ul"));
                    item.attr("data-path", paths[i]);
                    
                    if (list_ops.edit) {
                        item.children(".admin_grid_item_title").click(function(e) {
                            $.rleditor.image_list.edit_properties.apply($(this).closest(".admin_grid_item"));
                            
                            e.stopPropagation();
                            return false;  
                        });
                    }
                    
                    $(this).dynamic_grid("init_item", item);
                }
                $(this).dynamic_grid("load_items");
                $.rleditor.output($(this).closest(".rl_editor"));
            });        
        },
        remove: function(e, list_ops) {
            var frag = $(this).closest(".rle_fragment");
            var grid = frag.find("." + list_ops.template_class);
            grid.children("ul").children("li.dyngrid_selected").remove();
            frag.children("div").children(".rle_frag_header").children(".rle_frag_header_del").hide();
            
            $.rleditor.output(grid.closest(".rl_editor"));
        }
    } 
    $.rleditor.options.templates.slideshow = {
        list_ops: {
            edit: { title: true, url: true, desc: true },
            template_class: "rl_template_slideshow"
        },
        buttons: {
            add: { 
                title: 'Add image', 
                click: function(e) {
                    $.rleditor.image_list.add.apply(this, [e, $.rleditor.options.templates.slideshow.list_ops]);
                }
            },
            del: { 
                title: 'Remove selected', 
                click: function(e) {
                    $.rleditor.image_list.remove.apply(this, [e, $.rleditor.options.templates.slideshow.list_ops])
                }
            }        
        },
        onOutput: function(target, level, ops) {
            $.rleditor.image_list.output.apply(this, [target, level, ops, $.rleditor.options.templates.slideshow.list_ops]);
        },
        onCreate: function(temp_ops, new_frag) {
            $.rleditor.image_list.create.apply(this, [temp_ops, new_frag, $.rleditor.options.templates.slideshow.list_ops]); 
        },
        onLoad: function(template, level, ops) {
            $.rleditor.image_list.load.apply(this, [template, level, ops, $.rleditor.options.templates.slideshow.list_ops]);
        }
    }     
    $.rleditor.options.templates.banner_list = {
        list_ops: {
            edit: { title: true, url: true, desc: true, alt: true },
            template_class: "rl_template_banner_list"
        },
        buttons: {
            add: { 
                title: 'Add banner', 
                click: function(e) {
                    $.rleditor.image_list.add.apply(this, [e, $.rleditor.options.templates.banner_list.list_ops]);
                }
            },
            del: { 
                title: 'Remove selected', 
                click: function(e) {
                    $.rleditor.image_list.remove.apply(this, [e, $.rleditor.options.templates.banner_list.list_ops])
                }
            }        
        },
        onOutput: function(target, level, ops) {
            $.rleditor.image_list.output.apply(this, [target, level, ops, $.rleditor.options.templates.banner_list.list_ops]);
        },
        onCreate: function(temp_ops, new_frag) {
            $.rleditor.image_list.create.apply(this, [temp_ops, new_frag, $.rleditor.options.templates.banner_list.list_ops]); 
        },
        onLoad: function(template, level, ops) {
            $.rleditor.image_list.load.apply(this, [template, level, ops, $.rleditor.options.templates.banner_list.list_ops]);
        }
    }     
    $.rleditor.options.templates.image_list = {
        list_ops: {
            edit: { alt: true, title: true },
            template_class: "rl_template_image_list"
        },
        buttons: {
            add: { 
                title: 'Add image', 
                click: function(e) {
                    $.rleditor.image_list.add.apply(this, [e, $.rleditor.options.templates.image_list.list_ops]);
                }
            },
            del: { 
                title: 'Remove selected', 
                click: function(e) {
                    $.rleditor.image_list.remove.apply(this, [e, $.rleditor.options.templates.image_list.list_ops])
                }
            }        
        },
        onOutput: function(target, level, ops) {
            $.rleditor.image_list.output.apply(this, [target, level, ops, $.rleditor.options.templates.image_list.list_ops]);
        },
        onCreate: function(temp_ops, new_frag) {
            $.rleditor.image_list.create.apply(this, [temp_ops, new_frag, $.rleditor.options.templates.image_list.list_ops]); 
        },
        onLoad: function(template, level, ops) {
            $.rleditor.image_list.load.apply(this, [template, level, ops, $.rleditor.options.templates.image_list.list_ops]);
        }
    } 
    $.rleditor.options.templates.image_slider = {
        list_ops: {
            edit: { alt: true, title: true },
            template_class: "rl_template_image_slider"
        },
        buttons: {
            add: { 
                title: 'Add image', 
                click: function(e) {
                    $.rleditor.image_list.add.apply(this, [e, $.rleditor.options.templates.image_slider.list_ops]);
                }
            },
            del: { 
                title: 'Remove selected', 
                click: function(e) {
                    $.rleditor.image_list.remove.apply(this, [e, $.rleditor.options.templates.image_slider.list_ops])
                }
            }        
        },
        onOutput: function(target, level, ops) {
            $.rleditor.image_list.output.apply(this, [target, level, ops, $.rleditor.options.templates.image_slider.list_ops]);
        },
        onCreate: function(temp_ops, new_frag) {
            $.rleditor.image_list.create.apply(this, [temp_ops, new_frag, $.rleditor.options.templates.image_slider.list_ops]); 
        },
        onLoad: function(template, level, ops) {
            $.rleditor.image_list.load.apply(this, [template, level, ops, $.rleditor.options.templates.image_slider.list_ops]);
        }
    }     
    $.rleditor.options.templates.page_list = {
        buttons: {
            add: { 
                title: 'Add page', 
                click: function(e) {
                    var frag = $(this).closest(".rle_fragment");
                    $.overlay_ajax(doc_base + 'AdminPages/Ajax_Browser', "ajax", {}, {
                        height: 0.8,
                        width: 0.7,
                        target: frag.find(".rl_template"),
                        onDisplay: function() {
                            $(this).ac_create();
                        },
                        onClose: function(r) {
                            if (!r) return;
                            
                            var ops = $(this).data("overlay");
                            var style, item, html = "";
                            for (var i = 0; i < r.length; i++) {
                                if (r[i].thumb)
                                    style = 'style="background-image: url(' + r[i].thumb + ')"'; else
                                    style = '';
                                
                                html = '<li class="admin_grid_item dyngrid_item dyngrid_pending">';
                                html+= '    <div class="admin_grid_item_image" ' + style + '></div>';
                                html+= '    <div class="admin_grid_item_title">' + r[i].title + '</div>';
                                html+= '</li>';
                                
                                item = $(html).appendTo(ops.target.find("ul"));
                                
                                item.attr("data-id", r[i].id);
                                
                                ops.target.dynamic_grid("init_item", item);
                            }  
                            
                            ops.target.dynamic_grid("load_items");
                            $.rleditor.output(ops.target.closest(".rl_editor"));
                        }    
                    });              
                }
            },
            del: { 
                title: 'Remove selected', 
                click: function() {
                    var frag = $(this).closest(".rle_fragment");
                    var grid = frag.find(".rl_template");
                    grid.find("ul").children("li.dyngrid_selected").remove();
                    frag.children("div").children(".rle_frag_header").children(".rle_frag_header_del").hide();
                    
                    $.rleditor.output(grid.closest(".rl_editor"));    
                }
            },        
        },
        onOutput: function(target, level, ops) {
            var grid = $(this).find(".rl_template");
            var items = grid.find("ul").children("li.admin_grid_item");
            
            var rel_width = ($(this).width() / $(this).closest(".rl_editor").width()).toFixed(2);
            var html = "";
            for (var i = 0; i < items.length; i++) {
                item = $(items[i]);
                
                html+= "<li data-id='" + item.attr("data-id") + "'>"; 
            } 
            
            target.attr("data-width", rel_width);
            target.find("ul").html(html);
        },         
        onCreate: function(temp_ops, new_frag) {
            var header = $(this).children(".dyntree_item").children(".rle_frag_header");
            header.children(".rle_frag_header_del").hide();
            
            var grid = $(this).find(".rl_template").css("min-height", "102px");
            grid.mouseup(function(e) { 
                if (!$(this).find("ul").children("li.admin_grid_item").length) {
                    var frag = $(this).parents(".rle_fragment:first");
                    var header = frag.children(".dyntree_item").children(".rle_frag_header");
                    
                    header.children(".rle_frag_header_add").click();    
                }    
            });
            
            grid.dynamic_grid({
                mode: "sort",
                onSelection: function(items) {
                    var frag = $(this).parents(".rle_fragment:first");
                    var header = frag.children(".dyntree_item").children(".rle_frag_header");
                    if (items.length)
                        header.children(".rle_frag_header_del").show(); else
                        header.children(".rle_frag_header_del").hide();
                },
                onDrop: function() {
                    var edit = $(this).parents(".rl_editor:first");
                    $.rleditor.output(edit);
                },
                onLoadItems: function(items) {
                    var template = items.parents(".rl_template:first").attr("data-name");
                    var ids = [];
                    var item, img;
                    for (var i = 0; i < items.length; i++) 
                        ids.push($(items[i]).attr("data-id"));
                    
                    if (!ids.length) return;
                    
                    var xhr = $.post(doc_base + "AdminPages/Ajax_TemplateItem", {
                        ids: ids,
                        template: template,
                    }, function(r, textStatus, xhr) {
                        var ul = xhr._target;
                        var li;
                        for (var id in r) {
                            li = ul.children("li[data-id='" + id + "']");
                            if (r[id].image)
                                li.children(".admin_grid_item_image").css("background-image", "url('" + r[id].image + "')");
                                
                            li.children(".admin_grid_item_title").html(r[id].title);
                        }
                    }, "json");
                    xhr._target = $(this).find("ul");
                },
            });
            if (new_frag)
                header.children(".rle_frag_header_add").click();  
        },      
        onLoad: function(template, level, ops) {
            var html = "", item, items = template.find("ul").children("li");
            for (var i = 0; i < items.length; i++) {
                item = $(items[i]);    

                html+= '<li class="admin_grid_item dyngrid_item dyngrid_pending" data-id="' + item.attr("data-id") + '">';
                html+= '    <div class="admin_grid_item_image"></div>';
                html+= '    <div class="admin_grid_item_title"></div>';
                html+= '</li>';       
            }
            
            var temp = $(this).find(".rl_template");
            var ul = temp.find("ul").append(html);
            
            temp.dynamic_grid("init_item", ul.children("li"));
        }                   
    } 
    $.rleditor.options.templates.news_list = {
        buttons: {
            add: { 
                title: 'Add news', 
                click: function(e) {
                    var frag = $(this).closest(".rle_fragment");
                    $.overlay_ajax(doc_base + 'AdminNews/Ajax_Browser', "ajax", {}, {
                        height: 0.8,
                        width: 0.7,
                        target: frag.find(".rl_template"),
                        onDisplay: function() {
                            news_browser_init.apply(this);
                        },
                        onResize: function() {
                            $("#admin_news_browser").datagrid("update");
                        },                        
                        onClose: function(r) {
                            if (!r) return;
                            
                            var ops = $(this).data("overlay");
                            var style, item, html = "";
                            for (var i = 0; i < r.length; i++) {
                                if (r[i].thumb)
                                    style = 'style="background-image: url(' + r[i].thumb + ')"'; else
                                    style = '';
                                
                                html = '<li class="admin_grid_item dyngrid_item dyngrid_pending">';
                                html+= '    <div class="admin_grid_item_image" ' + style + '></div>';
                                html+= '    <div class="admin_grid_item_title">' + r[i].title + '</div>';
                                html+= '</li>';
                                
                                item = $(html).appendTo(ops.target.find("ul"));
                                
                                item.attr("data-id", r[i].id);
                                
                                ops.target.dynamic_grid("init_item", item);
                            }  
                            
                            ops.target.dynamic_grid("load_items");
                            $.rleditor.output(ops.target.closest(".rl_editor"));
                        }    
                    });              
                }
            },
            del: { 
                title: 'Remove selected', 
                click: function() {
                    var frag = $(this).closest(".rle_fragment");
                    var grid = frag.find(".rl_template");
                    grid.find("ul").children("li.dyngrid_selected").remove();
                    frag.children("div").children(".rle_frag_header").children(".rle_frag_header_del").hide();
                    
                    $.rleditor.output(grid.closest(".rl_editor"));    
                }
            },        
        },
        onOutput: function(target, level, ops) {
            var grid = $(this).find(".rl_template");
            var items = grid.find("ul").children("li.admin_grid_item");
            
            var rel_width = ($(this).width() / $(this).closest(".rl_editor").width()).toFixed(2);
            var html = "";
            for (var i = 0; i < items.length; i++) {
                item = $(items[i]);
                
                html+= "<li data-id='" + item.attr("data-id") + "'>"; 
            } 
            
            target.attr("data-width", rel_width);
            target.find("ul").html(html);
        },         
        onCreate: function(temp_ops, new_frag) {
            var header = $(this).children(".dyntree_item").children(".rle_frag_header");
            header.children(".rle_frag_header_del").hide();
            
            var grid = $(this).find(".rl_template").css("min-height", "102px");
            grid.mouseup(function(e) { 
                if (!$(this).find("ul").children("li.admin_grid_item").length) {
                    var frag = $(this).parents(".rle_fragment:first");
                    var header = frag.children(".dyntree_item").children(".rle_frag_header");
                    
                    header.children(".rle_frag_header_add").click();    
                }    
            });
            
            grid.dynamic_grid({
                mode: "sort",
                onSelection: function(items) {
                    var frag = $(this).parents(".rle_fragment:first");
                    var header = frag.children(".dyntree_item").children(".rle_frag_header");
                    if (items.length)
                        header.children(".rle_frag_header_del").show(); else
                        header.children(".rle_frag_header_del").hide();
                },
                onLoadItems: function(items) {
                    var template = items.parents(".rl_template:first").attr("data-name");
                    var ids = [];
                    var item, img;
                    for (var i = 0; i < items.length; i++) 
                        ids.push($(items[i]).attr("data-id"));
                    
                    if (!ids.length) return;
                    
                    var xhr = $.post(doc_base + "AdminNews/Ajax_TemplateItem", {
                        ids: ids,
                        template: template,
                    }, function(r, textStatus, xhr) {
                        var ul = xhr._target;
                        var li;
                        for (var id in r) {
                            li = ul.children("li[data-id='" + id + "']");
                            if (r[id].image)
                                li.children(".admin_grid_item_image").css("background-image", "url('" + r[id].image + "')");
                                
                            li.children(".admin_grid_item_title").html(r[id].title);
                        }
                    }, "json");
                    xhr._target = $(this).find("ul");
                },
            });
            if (new_frag)
                header.children(".rle_frag_header_add").click();  
        },      
        onLoad: function(template, level, ops) {
            var html = "", item, items = template.find("ul").children("li");
            for (var i = 0; i < items.length; i++) {
                item = $(items[i]);    

                html+= '<li class="admin_grid_item dyngrid_item dyngrid_pending" data-id="' + item.attr("data-id") + '">';
                html+= '    <div class="admin_grid_item_image"></div>';
                html+= '    <div class="admin_grid_item_title"></div>';
                html+= '</li>';       
            }
            
            var temp = $(this).find(".rl_template");
            var ul = temp.find("ul").append(html);
            
            temp.dynamic_grid("init_item", ul.children("li"));
        }                   
    } 
})( jQuery ); 
