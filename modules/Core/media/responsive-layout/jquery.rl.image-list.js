(function( $ ){  
    $.rleditor.image_list = {
        edit_properties: function(e) {
            var c = "<label>Title</label><input type='text' name='title_edit'>";
            c+= "<label>Url</label><input type='text' name='url_edit'>";
            
            $.overlay({
                width: 0.4,
                mode: "inline",
                form: true,
                edit: {
                    target: $(this),    
                    title: $(this).children(".admin_grid_item_title").html(),
                    url: $(this).children(".admin_grid_item_url").html(),
                },
                content: c,
                title: "Edit properties",
                buttons: { "ok" : { key : 13 }, "cancel" : {}},  
                onLoaded: function(ops) {
                    var edit = $(this).find("[name=title_edit]").ac_edit({value: ops.edit.title});
                    var edit = $(this).find("[name=url_edit]").ac_edit({value: ops.edit.url});
                },
                onClose: function(r, data, ops) {
                    if (!r) return;
                    
                    var title = $(this).find("[name=title_edit]").val(); 
                    var url = $(this).find("[name=url_edit]").val();                     
                    
                    ops.edit.target.children(".admin_grid_item_title").html(title);
                    ops.edit.target.children(".admin_grid_item_url").html(url);
                    
                    $.rleditor.output(ops.edit.target.closest(".rl_editor"));
                }               
            });
            
                   
        },
        output: function(target, level, ops, list_ops) {
            var grid = $(this).find("." + list_ops.template_class);
            var items = grid.children("ul").children("li.admin_grid_item");
            
            var path, html, item, trg_item;
            
            var trg_ul = target.children("ul");
            for (var i = 0; i < items.length; i++) {
                item = $(items[i]);
                
                path = admin_uploads_url + item.attr("data-path")
                trg_item = $("<li data-path='" + path  + "'></li>").appendTo(trg_ul);
                
                trg_item.attr("data-title", $(item).find(".admin_grid_item_title").html());
                trg_item.attr("data-url", $(item).find(".admin_grid_item_url").html());
            } 
        },
        create: function(data, ops, list_ops) {
            var header = $(this).children(".dyntree_item").children(".rle_frag_header");
            header.children(".rle_frag_header_del").hide();
            
            var grid = $(this).find("." + list_ops.template_class).css("min-height", "102px");
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
        }, 
        load: function(template, level, ops, list_ops) {
            var html = "", item, path, items = template.children("ul").children("li");
            var item_title, item_url;
            for (var i = 0; i < items.length; i++) {
                item = $(items[i]);    
                
                path = item.attr("data-path").replace(admin_uploads_url, "");
                
                item_title = item.attr("data-title");
                item_url = item.attr("data-url");
                if (!item_title) item_title = "";
                if (!item_url) item_url = "";

                html+= '<li class="admin_grid_item dyngrid_item dyngrid_pending" data-path="' + path + '">';
                html+= '    <div class="admin_grid_item_image"></div>';
                html+= '    <div class="admin_grid_item_url">' + item_url + "</div>";
                html+= '    <div class="admin_grid_item_title">' + item_title + '</div>';
                html+= '</li>';       
            }
            
            var temp = $(this).find("." + list_ops.template_class);
            var ul = temp.children("ul").append(html);
            
            if (list_ops.edit_title) 
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
                    html+= '    <div class="admin_grid_item_url"></div>';
                    html+= '    <div class="admin_grid_item_title"></div>';
                    html+= '</li>';
                    
                    item = $(html).appendTo($(this).children("ul"));
                    item.attr("data-path", paths[i]);
                    
                    //if (list_ops.edit_title) 
                        item.children(".admin_grid_item_title").click(function(e) {
                            $.rleditor.image_list.edit_properties.apply($(this).closest(".admin_grid_item"));
                            
                            e.stopPropagation();
                            return false;  
                        });
                    
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
})( jQuery ); 
