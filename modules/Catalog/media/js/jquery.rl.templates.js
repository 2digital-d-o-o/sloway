(function( $ ){
    $.rleditor.catalog = {   
        menu_add_product: function(e) {
            var frag = $(this).closest(".rle_fragment");
            var param = { 
                check: "group,item", 
                types: "",
                level: 0,
            }
            $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Browser', "ajax", { param: param }, {
                height: 0.8,
                width: 0.7,
                target: frag.find(".rl_template"),
                onDisplay: function() {
                    catalog_browser_init.apply(this);
                },
                onResize: function() {
                    $("#browser").datagrid("update");
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
        },
        menu_add_category: function(e) {
            var frag = $(this).closest(".rle_fragment");

            $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_CategoryBrowser', "ajax", { }, {
                height: 0.8,
                width: 0.7,
                target: frag.find(".rl_template"),
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
                        
                        item = $(html).appendTo(ops.target.children("ul"));
                        
                        item.attr("data-id", r[i].id);
                        item.attr("data-title", r[i].title);
                        if (r[i].image)
                            item.attr("data-image", r[i].image);
                        
                        ops.target.dynamic_grid("init_item", item);
                    }  
                    
                    ops.target.dynamic_grid("load_items");
                    $.rleditor.output(ops.target.closest(".rl_editor"));
                }    
            }); 
        },
        menu_rem: function(e) {
            var frag = $(this).closest(".rle_fragment");
            var grid = frag.find(".rl_template");
            grid.find("ul").children("li.dyngrid_selected").remove();
            frag.children("div").children(".rle_frag_header").children(".rle_frag_header_del").hide();
            
            $.rleditor.output(grid.closest(".rl_editor"));    
        },
        output: function(target, level, ops) {
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
        create: function(temp_ops, new_frag) {
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
                onLoadItems: function(items) {
                    var template = items.parents(".rl_template:first").attr("data-name");
                    var ids = [];
                    var item, img;
                    for (var i = 0; i < items.length; i++) 
                        ids.push($(items[i]).attr("data-id"));
                    
                    if (!ids.length) return;
                    
                    var xhr = $.post(doc_base + "AdminCatalog/Ajax_TemplateItem", {
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
        load: function(template, level, ops) {
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
    $.rleditor.options.templates.product_slider = {
        buttons: {
            add: { 
                title: 'Add product', 
                click: $.rleditor.catalog.menu_add_product,
            },
            del: { 
                title: 'Remove selected', 
                click: $.rleditor.catalog.menu_rem,
            },        
        },
        onOutput: $.rleditor.catalog.output,
        onCreate: $.rleditor.catalog.create,
        onLoad: $.rleditor.catalog.load,
    }
    $.rleditor.options.templates.product_list = {
        buttons: {
            add: { 
                title: 'Add product', 
                click: $.rleditor.catalog.menu_add_product,
            },
            del: { 
                title: 'Remove selected', 
                click: $.rleditor.catalog.menu_rem,
            },        
        },
        onOutput: $.rleditor.catalog.output,
        onCreate: $.rleditor.catalog.create,
        onLoad: $.rleditor.catalog.load,
    }    
    $.rleditor.options.templates.category_slider = {
        buttons: {
            add: { 
                title: 'Add category', 
                click: $.rleditor.catalog.menu_add_category,
            },
            del: { 
                title: 'Remove selected', 
                click: $.rleditor.catalog.menu_rem,
            },        
        },
        onOutput: $.rleditor.catalog.output,
        onCreate: $.rleditor.catalog.create,
        onLoad: $.rleditor.catalog.load,
    }
    $.rleditor.options.templates.category_list = {
        buttons: {
            add: { 
                title: 'Add category', 
                click: $.rleditor.catalog.menu_add_category,
            },
            del: { 
                title: 'Remove selected', 
                click: $.rleditor.catalog.menu_rem,
            },        
        },
        onOutput: $.rleditor.catalog.output,
        onCreate: $.rleditor.catalog.create,
        onLoad: $.rleditor.catalog.load,
    }     
    $.rleditor.options.templates.tagged_image = {
        init_tag: function() {
            var x = $(this).attr("data-x");
            var y = $(this).attr("data-y");
            var ids = $(this).attr("data-ids");
            
            var xhr = $.post(doc_base + "AdminCatalog/Ajax_TemplateItem", {
                ids: ids,
                template: "product",
            }, function(r, textStatus, xhr) {
                var tag = xhr._target;
                
                var html = "";
                for (var id in r) 
                    html+= "<div class='rl_template_tagged'>" + r[id].title + "</div>";
                
                tag.html(html);
                $("<a class='rl_template_tag_rem'></a>").appendTo(tag).click(function(e) {
                    $.rleditor.output($(this).closest(".rl_editor"));
                    $(this).parent().remove();

                    e.stopPropagation();
                });   
            }, "json");
            xhr._target = $(this);            
            
            $(this).css({left: x + "%", top: y + "%"});
            $(this).hover(function() {
                var ofs = $(this).offset();
                var x = ofs.left - $(window).scrollLeft();
                var y = ofs.top - $(window).scrollTop();

                $(this).css({left: x + "px", top: y + "px"}).addClass("hovered");
            }, function() {
                var x = $(this).attr("data-x");
                var y = $(this).attr("data-y");

                $(this).css({left: x + "%", top: y + "%"}).removeClass("hovered");
            });            
        },
        onMenu: function(target, ref, ops, pos) {
            ref = ref + " data-pos-x='" + pos.rx + "' data-pos-y='" + pos.ry + "'";
            this.push({
                "attr" : ref,
                "cls" : "rlm_editable_tag", 
                "content" : "<span>" + $.rleditor.translate("Tag", ops) + "</span>",

            });
        },
        onLoad: function(temp, level, ops) { 
            if (ops.image_mode == "adaptive")
                $.rleditor.adaptive_to_img.apply(this);
            
            $(this).find(".rl_template_tag").each(function() {
                $.rleditor.options.templates.tagged_image.init_tag.apply(this);
            }); 
        },   
        onOutput: function(temp, level, ops) { 
            if (ops.image_mode == "adaptive")
                $.rleditor.img_to_adaptive.apply(temp); 
            
            temp.find(".rl_template_tag").each(function() {
                $(this).html("");
                $(this).removeAttr("style");
            });
        },        
        menuClick : function() {
            if (!$(this).hasClass("rlm_editable_tag")) return;
            
            var trg_id = $(this).attr("data-target");
            var trg = $("#" + trg_id);
            var x = $(this).attr("data-pos-x") * 100;
            var y = $(this).attr("data-pos-y") * 100;
            
            var param = { 
                check: "group", 
                types: "",
                level: 0,
            }
            $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Browser', "ajax", { param: param }, {
                height: 0.8,
                width: 0.7,
                target: trg,
                target_x: x.toFixed(2),
                target_y: y.toFixed(2),
                onDisplay: function() {
                    catalog_browser_init.apply(this);
                },
                onResize: function() {
                    $("#browser").datagrid("update");
                },
                onClose: function(r) {
                    if (!r) return;
                    
                    var ops = $(this).data("overlay");
                    var ids = [];
                    for (var i = 0; i < r.length; i++) {
                        ids.push(r[i].id);
                    }
                    
                    var html = "<div class='rl_template_tag' data-x='" + ops.target_x + "' data-y='" + ops.target_y + "' data-ids='" + ids.join(",") + "'></div>";
                    var tag = $(html).appendTo(ops.target);
                    
                    $.rleditor.options.templates.tagged_image.init_tag.apply($(tag));
                    $.rleditor.output(ops.target.closest(".rl_editor"));
                }    
            });                 
        }        
    }        
})( jQuery );            
