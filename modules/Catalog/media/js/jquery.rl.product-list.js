if ($.rleditor)
$.rleditor.options.templates.product_list = {
    buttons: {
        add: { 
            title: 'Add product', 
            click: function(e) {
                var frag = $(this).closest(".rle_fragment");
                var param = { 
                    check: "group,item", 
                    types: "",
                    level: 0,
                }
                $.overlay_ajax(doc_base + 'AdminCatalog/Ajax_Browser', "ajax", { param: param }, {
                    height: 0.8,
                    width: 0.7,
                    target: frag.find(".rl_template_product_list"),
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
            }
        },
        del: { 
            title: 'Remove selected', 
            click: function(e) {
                var frag = $(this).closest(".rle_fragment");
                var grid = frag.find(".rl_template_product_list");
                grid.children("ul").children("li.dyngrid_selected").remove();
                frag.children("div").children(".rle_frag_header").children(".rle_frag_header_del").hide();
                
                $.rleditor.output(grid.closest(".rl_editor"));
            }
        }        
    },
    menu: [{
        "cls"     : "rlm_product_list_add",
        "content" : "<span>Add product</span>",
    },{
        "cls"     : "rlm_product_list_refresh",
        "content" : "<span>Refresh</span>",
    }],
    menuClick: function(target) {
        if ($(this).hasClass("rlm_product_list_add")) {
            var header = target.children(".dyntree_item").children(".rle_frag_header");
                
            header.children(".rle_frag_header_add").click();   
        }
    },
    onOutput: function(target, level, ops) {
        var grid = $(this).find(".rl_template_product_list");
        var items = grid.children("ul").children("li.admin_grid_item");
        
        var rel_width = ($(this).width() / $(this).closest(".rl_editor").width()).toFixed(2);
        var html = "";
        for (var i = 0; i < items.length; i++) {
            item = $(items[i]);
            
            html+= "<li data-id='" + item.attr("data-id") + "' data-title='" + item.attr("data-title") + "' data-image='" + item.attr("data-image") + "'>"; 
        } 
        
        target.attr("data-width", rel_width);
        target.children("ul").html(html);
    },
    onCreate: function(data, ops) {
        var header = $(this).children(".dyntree_item").children(".rle_frag_header");
        header.children(".rle_frag_header_del").hide();
        
        var grid = $(this).find(".rl_template_product_list").css("min-height", "102px");
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
                var item, img;
                for (var i = 0; i < items.length; i++) {
                    item = $(items[i]);
                    
                    ids.push(item.attr("data-id"));
                    paths.push(img);
                }
                
                if (ids.length) {
                    var ul = $(this).children("ul");
                    $.post(doc_base + "AdminCatalog/Ajax_ProductImage", { 
                        ids: ids,
                        template: "admin_gallery_96"
                    }, function(r) {
                        for (var id in r) 
                            ul.children("li[data-id='" + id + "']").children(".admin_grid_item_image").css("background-image", "url('" + r[id] + "')");
                    }, "json");      
                    
                    /*$.post(doc_base + "Admin/Ajax_Thumbnail", { 
                        ids: ids,
                        paths: paths,
                        template: "admin_gallery_96"
                    }, function(r) {
                        for (var id in r) 
                            ul.children("li[data-id='" + id + "']").children(".admin_grid_item_image").css("background-image", "url('" + r[id] + "')");
                        
                    }, "json");      */
                }
            },    
            onDrop: function() {  
                $.rleditor.output($(this).closest(".rl_editor"));
            },            
            onSelection: function(items) {
                var frag = $(this).parents(".rle_fragment:first");
                var header = frag.children(".dyntree_item").children(".rle_frag_header");
                if (items.length)
                    header.children(".rle_frag_header_del").show(); else
                    header.children(".rle_frag_header_del").hide();
            }            
        });
    }, 
    onLoad: function(template, level, ops) {
        var html = "", item, items = template.children("ul").children("li");
        for (var i = 0; i < items.length; i++) {
            item = $(items[i]);    

            html+= '<li class="admin_grid_item dyngrid_item dyngrid_pending" data-id="' + item.attr("data-id") + '" data-title="' + item.attr("data-title") + '">';
            html+= '    <div class="admin_grid_item_image"></div>';
            html+= '    <div class="admin_grid_item_title">' + item.attr("data-title") + '</div>';
            html+= '</li>';       
        }
        
        var temp = $(this).find(".rl_template_product_list");
        var ul = temp.children("ul").append(html);
        
        temp.dynamic_grid("init_item", ul.children("li"));
    }
}

$.rl.template_handlers["product_list"] = function() {
    if ($.product_grid) 
        $.product_grid.update($(this).find(".product_grid"));
};