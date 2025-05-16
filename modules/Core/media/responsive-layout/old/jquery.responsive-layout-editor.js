(function( $ ){   
    "use strict";
    
    $.fn.findExclude = function(selector, mask, result) {
        result = typeof result !== 'undefined' ? result : new jQuery();
        this.children().each( function(){
            var thisObject = jQuery( this );
            if( thisObject.is( selector ) ) 
                result.push( this );
            if( !thisObject.is( mask ) )
                thisObject.findExclude( selector, mask, result );
        });
        return result;
    }    
    $.fn.htmlClean = function() {
        this.contents().filter(function() {
            if (this.nodeType != 3) {
                $(this).htmlClean();
                return false;
            }
            else {
                this.textContent = $.trim(this.textContent);
                return !/\S/.test(this.nodeValue);
            }
        }).remove();
        return this;
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
    $.rleditor = {
        options: {
            input: $(),
            fixate_images: false,
            fixate_width: 0,
            responsive_images: true,
            insert_cid: true,
            url_base: null,
            platform: "",
            media: {
                "mobile" : { "width" : 600, "color" :"rgba(255,192,0)" },
                "tablet" : { "width" : 1000, "color" : "rgba(0,176,80)" },
                "laptop" : { "width" : 1366, "color" : "rgba(0,112,192)" },
                "desktop" : { "width" : 0, "color" : "rgba(0,176,240)" },
            },
            templates: {},    
            trans: {},
            base_url: null,
            onEdit: null, 
            onPurgeCache: null,
        },  
        media_options: {
            title: "",  
            color: "",
        },
        template_options: {
            title: "",
            html: "",   
            html_output: null,
            sub_frags: "",
            buttons: null,
            onLoad: null,
            onUpdate: null,
            onCreate: null,
            onOutput: null    
        },
        dyntree_options: {
            nodeEmpty: function() {
                $(this).addClass("rle_empty");
            },
            drop: function(parent) {
                var editor = parent.parents(".rl_editor:first");
                var ops = editor.data("rleditor");
                var temp = parent.closest(".rl_template");
                if (temp.length) {
                    var name = temp.attr("data-name");
                    if (typeof ops.templates[name].onUpdate == "function")
                        ops.templates[name].onUpdate(temp, null);
                }
                    
                parent.removeClass("rle_empty");
                $.rleditor.output(editor);  
            },
            dropValid: function(src_node, dst_tree, src_tree) {
                return src_tree.hasClass("rl_editable") || src_tree.hasClass("rle_tree");                
            },
        },
        resizing: null,
        fs_resizing: null,   
        index: 1000, 

        global_events: false,
        decode: function(string) {
            var res = {};
            
            if (string == "") return res;
            
            var rule,rules = string.split(";");
            for (var i = 0; i < rules.length; i++) {
                rule = rules[i].split("=");
                res[rule[0]] = rule[1];
            }  
            
            return res;
        },
        attributes: function(elem) {
            var attr;
            var res = {
                properties: {},
                widths: {},
                padding: {}
            };
            for (var i = 0; i < elem.attributes.length; i++) {
                attr = elem.attributes[i];
                if (!attr.specified) continue;
                
                if (attr.name.indexOf("data-prop-") == 0) 
                    res.properties[attr.name.replace("data-prop-", "")] = attr.value; else
                if (attr.name.indexOf("data-width-") == 0)
                    res.widths[attr.name.replace("data-width-", "")] = attr.value; else
                if (attr.name.indexOf("data-pad-") == 0)
                    res.padding[attr.name.replace("data-pad-", "")] = attr.value; 
            }
            
            if ($.isEmptyObject(res.properties)) res.properties = undefined;
            if ($.isEmptyObject(res.widths)) res.widths = undefined;
            if ($.isEmptyObject(res.padding)) res.padding = undefined;
            
            return res;
        },   
        
        parse_style: function(src, expand) {
            var result = {}
            
            var style = src;            
            if (src instanceof jQuery) 
                style = src.attr("style");
                
            if (!style) return result;
        
            var rule, rules = style.split(";");
            var map = ["top", "right", "bottom", "left"];
            var key,val,sub,last;
            for (var i = 0; i < rules.length; i++) {
                rule = rules[i].trim().split(":");                                
                if (rule.length > 1) {
                    key = rule[0].trim();
                    val = rule[1].trim();

                    if (key == "display") continue;
                    
                    if (expand) {
                        if (key == "padding" || key == "margin") {
                            sub = val.split(" ");
                            switch (sub.length) {
                                case 1:
                                    result[key + "-top"] = sub[0];
                                    result[key + "-right"] = sub[0];
                                    result[key + "-bottom"] = sub[0];
                                    result[key + "-left"] = sub[0];
                                    break;
                                case 2:
                                    result[key + "-top"] = sub[0];
                                    result[key + "-right"] = sub[1];
                                    result[key + "-bottom"] = sub[0];
                                    result[key + "-left"] = sub[1];
                                    break;
                                case 3:
                                    result[key + "-top"] = sub[0];
                                    result[key + "-right"] = sub[1];
                                    result[key + "-bottom"] = sub[2];
                                    result[key + "-left"] = sub[1];
                                    break;
                                case 4:    
                                    result[key + "-top"] = sub[0];
                                    result[key + "-right"] = sub[1];
                                    result[key + "-bottom"] = sub[2];
                                    result[key + "-left"] = sub[3];
                                    break;
                            }
                        } else 
                            result[key] = val;    
                    } else
                        result[key] = val;
                }
            }
            
            return result;    
        },  
        parse_url: function(url) {
            if (!url) return "";
            
            var b = $.rleditor.options.base_url.replace("http://", "").replace("https://", "");
            var b1 = "http://" + b;
            var b2 = "https://" + b;
                        
            if (url.indexOf(b) === 0) return url.replace(b, "");
            if (url.indexOf(b1) === 0) return url.replace(b1, "");            
            if (url.indexOf(b2) === 0) return url.replace(b2, "");            
            
            return url;
        },
        format_style: function(style, collapse, newlines, filter) {
            var res = [];
            
            if (collapse) {
                var pv = [style["padding-top"], style["padding-right"], style["padding-bottom"], style["padding-left"]];
                var v,all_z = true;
                var padding = "padding:";
                for (var i = 0; i < 4; i++) {
                    if (typeof pv[i] == "undefined") 
                        v = "0"; else
                        v = pv[i];       
                    
                    if (parseFloat(v)) all_z = false;
                    
                    padding+= v + " ";
                }
                if (!all_z) res.push(padding.trim());
                
                var pv = [style["margin-top"], style["margin-right"], style["margin-bottom"], style["margin-left"]];
                var v,all_z = true;
                var margin = "margin:";
                for (var i = 0; i < 4; i++) {
                    if (typeof pv[i] == "undefined") 
                        v = "0"; else
                        v = pv[i];       
                    
                    if (parseFloat(v)) all_z = false;
                    
                    margin+= v + " ";
                }
                if (!all_z) res.push(margin.trim());
            }
            
            var val;
            for (var key in style) {
                val = style[key];
                if (collapse && (key.indexOf("margin-") === 0 || key.indexOf("padding-") === 0)) continue; 
                
                if (filter && filter.indexOf(key) !== false) continue;
                
                res.push(key + ":" + val);
            }
            
            if (newlines)
                return res.join(";\n"); else
                return res.join(";");
        },
        output_timeout: null,
        
        menu: {
            remove_fragment: function(id) {
                var edit = $(this).parents(".rl_editor:first");
                
                $("#" + id).dynamic_tree("remove");
                $.rleditor.output(edit);
                $.contextmenu_close();
            },
            copy_fragment: function(id) {
                var target = $("#" + id);
                console.log(target);
                
                var edit = target.parents(".rl_editor:first");
                var ops = edit.data("rleditor");                                 
                
                var cb = edit.children(".rle_clipboard");
                cb.html("");
                
                $.rleditor.output_fragment(target, cb, 0, ops);
                
                var txt = cb.html();
                localStorage.setItem("rle_clipboard_name", target.attr("data-name"));
                localStorage.setItem("rle_clipboard_html", txt);
                
                $.contextmenu_close();    
            },
            paste_fragment: function(id) {
                var target = $("#" + id);
                
                var edit = target.parents(".rl_editor:first");
                var ops = edit.data("rleditor");                                 
                
                var txt = localStorage.getItem("rle_clipboard_html");
                var tmp = edit.children(".rle_clipboard").html(txt);           
                var temp = tmp.children("div");
                var level = parseInt($(this).attr("data-level"));
                
                $.rleditor.load_template(temp, target.children("ul"), level+1, edit, ops);
                $.rleditor.output(edit); 
            },
            paste_fragment_root: function(id, name, pos_y) {  
                var editor = $("#" + id).closest(".rl_editor");
                var ops = editor.data("rleditor");
                var ofs = editor.offset();
                var y = pos_y - ofs.top + $(window).scrollTop();
                
                var ul = editor.children(".rle_tree").children(".rle_fragments");
                var lis = ul.children(".rle_fragment");
                var li, h = 0, itm = null;
                for (var i = 0; i < lis.length; i++) {    
                    li = $(lis[i]);
                    
                    if (h > y || (y >= h && y < h + li.outerHeight())) {
                        itm = li;
                        break;                            
                    }
                    h+= li.outerHeight(true);
                }    
                var txt = localStorage.getItem("rle_clipboard_html");
                var tmp = editor.children(".rle_clipboard").html(txt);           
                var temp = tmp.children("div");
                
                //var frag = $.rleditor.create_fragment(editor, name, ops, ul, true, itm);
                
                $.rleditor.load_template(temp, ul, 0, editor, ops, itm);
                $.rleditor.output(editor); 
            },
            edit_editable: function(id) {
                var target = $("#" + id);
                var edit = target.parents(".rl_editor:first");
                var ops = edit.data("rleditor");                                 

                if (typeof ops.onEdit == "function")
                    ops.onEdit.apply(edit, [target, target.attr("data-editor")]);
            },                         
            add_fragment: function(id, name) {  
                var target = $("#" + id);
                var edit = target.parents(".rl_editor:first");
                var ops = edit.data("rleditor");                                 
                
                var ul = target.children("ul.rle_fragments");
                target.removeClass("rle_empty");
                
                var frag = $.rleditor.create_fragment(edit, name, ops, ul, true);
                $.rleditor.create_resizers(frag);
                
                var temp = target.closest(".rl_template");
                var name = temp.attr("data-name");
                if (typeof ops.templates[name].onUpdate == "function")
                    ops.templates[name].onUpdate(temp, null);
                
                $.rleditor.output(edit);
            },
            add_fragment_root: function(id, name, pos_y) {  
                var editor = $("#" + id).closest(".rl_editor");
                var ops = editor.data("rleditor");
                var ofs = editor.offset();
                var y = pos_y - ofs.top + $(window).scrollTop();
                
                var ul = editor.children(".rle_tree").children(".rle_fragments");
                var lis = ul.children(".rle_fragment");
                var li, h = 0, itm = null;
                for (var i = 0; i < lis.length; i++) {    
                    li = $(lis[i]);
                    
                    if (h > y || (y >= h && y < h + li.outerHeight())) {
                        itm = li;
                        break;                            
                    }
                    h+= li.outerHeight(true);
                }
                
                var frag = $.rleditor.create_fragment(editor, name, ops, ul, true, itm);
                $.rleditor.create_resizers(frag);
                
                $.rleditor.output(editor);      
            },            
            clear_editable: function(id) {
                var editable = $("#" + id);
                
                editable.addClass("rle_empty");
                editable.children("ul").html('');
                
                $.rleditor.output(editable.parents(".rl_editor:first"));
            },
            edit_padding: function(id) {
                var trg = $("#" + id);
                
                if (trg.hasClass("rle_fragment"))
                    trg = trg.children("div").children(".rl_template").children(".rl_template_span");
                                    
                var ops = {
                    relative: "1111",
                    attachment: "1101",
                    clip: 1,
                    onChange: function() {
                        $.rleditor.output($(this).closest(".rl_editor"));        
                    }
                }
                                    
                trg.elemlayout("padding", ops);
            },   
            edit_margin: function(id) {
                var trg = $("#" + id);
                
                var ops = {
                    attachment: "1101",
                    margin_mask: "1010",
                    clip: 10,
                    onChange: function() {
                        $.rleditor.output($(this).closest(".rl_editor"));        
                    }
                }
                
                $("#" + id).elemlayout("margin", ops);
            },   
            edit_size: function(id) {  
                $("#" + id).resizer({size_const: true});
            }
        },
        valid_fragments: function(target, ops) {
            var name = target.parents(".rl_template:first").attr("data-name");
            var res = ops.templates[name].sub_frags;
            if (!res) return ops.template_list;
            
            return res.split(",");
            
            /*
            var frags = target.attr("data-frags");
            if (!frags) return [];
            
            var st = frags.toLowerCase();
            if (st == "true" || st == "1") return ops.template_list;
            if (st == "false" || st == "0") return [];
            
            var name, res = [];
            frags = "," + frags.replace(/\s/g, '') + ",";
            
            for (var i = 0; i < ops.template_list.length; i++) {
                name = ops.template_list[i];
                if (frags.indexOf("," + name + ",") != -1)
                    res.push(name);                    
            }
            
            return res;    */
        },
        translate: function(tag, ops) {
            if (typeof ops.trans[tag] != "undefined")
                return ops.trans[tag]; else
                return tag;
        },
        show_overlay: function(id) {
            var ov = $("#rle_overlay");
            if (!ov.length) 
                ov = $("<div id='rle_overlay'></div>").prependTo("#rle_cont");    
                
            var elem = $("#" + id);
            var ofs = elem.offset();
            
            var x1 = ofs.top;
            var y1 = ofs.left;
            var x2 = elem.outerWidth() - 2;
            var y2 = elem.outerHeight() - 2;
            
            if (elem.hasClass("rle_fragment")) {
                x1--; y1--; x2+=2; y2+=2;            
            }
            
            ov.css({
                "top" : x1 + "px",
                "left" : y1 + "px",
                "width" : x2  + "px",
                "height" : y2 + "px",
            }).show();
        },
        element_title: function(elem) {
            var res = $.rleditor.tags[elem[0].nodeName.toLowerCase()];
            if (typeof res == "undefined")
                res = elem[0].nodeName;
            
            return res;
        },   
        build_menu: function(edit, target, ops, x, y) {
            var item,items = [];
            var ref = "data-target='" + target.attr("id") + "'";
            
            if (target.is('.tl_element')) {
                var item = {
                    "attr"    : ref,
                    "cls"     : "rlm_element",
                    "content" : "<span>" + $.rleditor.element_title(target) + "</span>",
                    "items"   : []
                }
                
                if (!target.is("img"))
                    item.items.push({
                        "attr"    : ref,
                        "cls"     : "rlm_element_padding",
                        "content" : "<span>" + $.rleditor.translate("Padding", ops) + "</span>"
                    });
                
                items.push(item);
            } else
            if (target.is('.rl_editable')) {
                var item = {
                    "attr"    : ref,
                    "cls"   : "rlm_editable",
                    "content" : "<span>" + $.rleditor.translate("Container", ops) + "</span>",
                    "items"   : []
                }
                
                if (!target.is(".dyntree_parent"))
                    item.items.push({   
                        "attr"    : ref,
                        "cls"   : "rlm_editable_edit",
                        "content" : "<span>" + $.rleditor.translate("Edit", ops) + "</span>"
                    });
                
                item.items.push({
                    "attr"    : ref,
                    "cls" : "rlm_editable_clear", 
                    "content" : "<span>" + $.rleditor.translate("Clear", ops) + "</span>"
                });
                
                var outline = target.attr("data-outline");
                if (typeof outline != "undefined" && (outline == "1" || outline == "true")) {
                    item.items.push({
                        "attr"    : ref,
                        "cls"     : "rlm_element_padding",
                        "content" : "<span>" + $.rleditor.translate("Padding", ops) + "</span>"
                    });
                }
                    
                if (target.hasClass("dyntree")) {
                    var subitem = {
                        "attr"    : ref,
                        "cls"   : "rlm_editable_add",                        
                        "content" : "<span>" + $.rleditor.translate("Add template", ops) + "</span>",
                        "items"   : []
                    }
                    
                    var name;
                    var valid_frags = $.rleditor.valid_fragments(target, ops);
                    for (var i in valid_frags) {
                        name = valid_frags[i];
                        if (typeof ops.templates[name] == "undefined") continue;
                        
                        subitem.items.push({
                            "attr"    : ref + " data-name='" + name + "'",
                            "cls"   : "rlm_editable_add_template",
                            "content" : "<span class='rle_template_icon' data-name='" + name + "'>" + ops.templates[name].title + "</span>"
                        });
                    }
                    
                    item.items.push(subitem);
                    var paste = localStorage.getItem("rle_clipboard_name");
                    if (paste) {
                        var title = $.rleditor.translate("Paste", ops);
                        title+= " (<span class='rle_template_icon rle_paste_item' data-name='" + paste + "'></span>";
                        title+= ops.templates[paste].title;
                        title+= ")";
                        item.items.push({
                            "attr"    : ref,
                            "cls"   : "rlm_editable_paste",                        
                            "content" : "<span>" + title + "</span>",
                            "items"   : []
                        }); 
                    }
                }
                
                items.push(item);
                
                var name = target.closest(".rle_fragment").attr("data-name");
                if (typeof ops.templates[name].onMenu === "function") {
                    var ofs = target.offset();
                    var ax = x - (ofs.left - $(window).scrollLeft());
                    var ay = y - (ofs.top - $(window).scrollTop());
                    
                    var pos = {
                        x: ax,
                        y: ay,
                        rx: ax / target.width(),
                        ry: ay / target.height()
                    }
                    
                    ops.templates[name].onMenu.apply(item.items, [target, ref, ops, pos]);
                }
            } else
            if (target.is('.rle_fragment')) {
                var name = target.attr("data-name");
                
                item = {
                    "attr"  : ref,
                    "cls"   : "rlm_fragment",
                    "content" : "<span class='rle_template_icon' data-name='" + name + "'>" + ops.templates[name].title + "</span>",
                    "items"   : [{
                        "attr"    : ref,
                        "cls"   : "rlm_fragment_delete",
                        "content" : "<span>" + $.rleditor.translate("Remove", ops) + "</span>"
                    }]
                }  
                items.push(item);
                item.items.push({
                    "attr"    : ref,
                    "cls"     : "rlm_fragment_copy",
                    "content" : "<span>" + $.rleditor.translate("Copy", ops) + "</span>"
                }); 
                item.items.push({
                    "attr"    : ref,
                    "cls"     : "rlm_element_padding",
                    "content" : "<span>" + $.rleditor.translate("Padding", ops) + "</span>"
                });
                item.items.push({
                    "attr"    : ref,
                    "cls"     : "rlm_element_margin",
                    "content" : "<span>" + $.rleditor.translate("Margin", ops) + "</span>"
                });
                
                if (ops.templates[name].menu) {
                    var itm;
                    for (var i in ops.templates[name].menu) {
                        itm = ops.templates[name].menu[i];
                        itm.attr = ref;
                        item.items.push(itm);
                    }    
                }
                item.items = item.items.concat();
            }   
            
            for (var i in items)
                if (!items[i].attr)
                    items[i].attr = ref;
            
            target = target.parents(".rle_node:first");
            if (target.length) 
                items = $.rleditor.build_menu(edit, target, ops).concat(items); 
            else {
                var name, root_add = [];
                for (var i in ops.template_list) {
                    name = ops.template_list[i];
                    root_add.push({
                        "attr"    : ref + " data-name='" + name + "'",
                        "cls"   : "rlm_editable_add_root",
                        "content" : "<span class='rle_template_icon' data-name='" + name + "'>" + ops.templates[name].title + "</span>"
                    });
                }                   
                var paste = localStorage.getItem("rle_clipboard_name");
                if (paste) {
                    var title = $.rleditor.translate("Paste", ops);
                    title+= " (<span class='rle_template_icon rle_paste_item' data-name='" + paste + "'></span>";
                    title+= ops.templates[paste].title;
                    title+= ")";
                    root_add.push({
                        "attr"    : ref,
                        "cls"   : "rlm_editable_paste_root",                        
                        "content" : "<span>" + title + "</span>",
                        "items"   : []
                    }); 
                }                
                
                var root = [{
                    "attr"    : ref,
                    "cls"     : "rlm_document",
                    "content" : "<span class='rle_root_icon'>Document</span>",
                    "items"   : root_add
                }];
                

                items = root.concat(items);
            }
            
                       
            return items;
        }, 
        get_classes: function(obj) {
            var cls = obj.attr("class").split(" ");
            var res = [];
            for (var i in cls) {
                if (cls[i].indexOf("rl_class") == 0 || cls[i] == "rl_framed")
                    res.push(cls[i]);
            }
            
            return res;
        },
        get_media: function(template, as_class) {
            var cls = template.attr("class").split(" ");
            var res = [];
            for (var i in cls) {
                if (cls[i].indexOf("rl_media") != 0) continue;
                
                if (as_class)
                    res.push(cls[i]); else
                    res.push(cls[i].replace("rl_media_", "")); 
            }
                    
            return res;
        },
        get_attrs: function(obj) {
            var attr,attrs = {};
            for (var i = 0; i < obj[0].attributes.length; i++) {
                attr = obj[0].attributes[i];
                if (!attr.specified) continue;
                
                if (attr.name.indexOf("data_attr_") == 0) 
                    attrs[attr.name.replace("data_attr_", "")] = attr.value; 
            }
            return attrs;
        },
        get_elems: function(obj) {
            var elements = {};
            obj.findExclude(".rl_template_element", ".rle_fragment").each(function() {
                var name = $(this).attr("data-name");
                var editor = $(this).attr("data-editor");
                if (!name) return;
                if (!editor) editor = "text";
                
                elements[name] = { editor: editor, content: $(this).html() }
            }); 
            
            return elements;   
        },
        set_media: function(template, media) {
            var frag = template.closest(".rle_fragment");      
            var med = $.rleditor.get_media(template, true);
            
            var cls = media.replace(/,/g, " rl_media_");
            if (cls) cls = "rl_media_" + cls;
            
            template.removeClass(med.join(" "));
            template.addClass(cls);    
            
            frag.attr("data-media", media);
            frag.children(".dyntree_item").children(".rle_frag_header").find(".rle_frag_media > span").each(function() {
                var m = $(this).attr("data-media");
                if (!media || media.indexOf(m) != -1)
                    $(this).removeClass("disabled"); else
                    $(this).addClass("disabled");
            });
        },
        set_classes: function(template, classes) {
            var cls = $.rleditor.get_classes(template);    
            
            
            template.removeClass(cls.join(" "));
            template.addClass(classes.replace(/\,/g, " "));
        },
        get_margin: function(style) {
            var res = [];
            if (typeof style["margin-top"] != "undefined") res.push("margin-top: " + style["margin-top"]);
            if (typeof style["margin-right"] != "undefined") res.push("margin-right: " + style["margin-right"]);
            if (typeof style["margin-bottom"] != "undefined") res.push("margin-bottom: " + style["margin-bottom"]);
            if (typeof style["margin-left"] != "undefined") res.push("margin-left: " + style["margin-left"]);
            
            return res.join(";");
        },
        get_padding: function(style) {
            var res = [];
            if (typeof style["padding-top"] != "undefined") res.push("padding-top: " + style["padding-top"]);
            if (typeof style["padding-right"] != "undefined") res.push("padding-right: " + style["padding-right"]);
            if (typeof style["padding-bottom"] != "undefined") res.push("padding-bottom: " + style["padding-bottom"]);
            if (typeof style["padding-left"] != "undefined") res.push("padding-left: " + style["padding-left"]);
            
            return res.join(";");
        },         
        copy_elems: function(src, dst) {
            src.findExclude(".rl_template_element", ".rle_fragment").each(function() {
                var name = $(this).attr("data-name");
                var dst_elem = dst.findExclude(".rl_template_element[data-name=" + name + "]", ".rle_fragment"); 
                if (dst_elem.length)
                    dst_elem.html($(this).html());
            });
        },
        set_elems: function(obj, values) {
            obj.findExclude(".rl_template_element", ".rl_template").each(function() {
                var name = $(this).attr("data-name");
                if (!name) return;
                
                var c = values[name];
                if (!c) c = "";
                
                $(this).html(c);
            }); 
        },        
        set_attrs: function(obj, attrs) {
            for (var name in attrs) {
                obj.attr("data_attr_" + name, attrs[name]);    
            }      
        },
        context_menu: function(edit, target, x, y, ops, items) {
            var items = $.rleditor.build_menu(edit, target, ops, x, y);
            
            var menu = target.contextmenu(x, y, items, {
                icons: false,
                cls: "rle_menu",
                onClick: function(trg, name, x, y) {
                    var id = $(this).attr("data-target");
                    if ($(this).is(".rlm_properties"))
                        $.rleditor.menu.edit_properties(id); else
                    if ($(this).is(".rlm_fragment_copy"))
                        $.rleditor.menu.copy_fragment(id); else
                    if ($(this).is(".rlm_editable_paste"))
                        $.rleditor.menu.paste_fragment(id); else
                    if ($(this).is(".rlm_editable_paste_root"))
                        $.rleditor.menu.paste_fragment_root(id, name, y); else
                    if ($(this).is(".rlm_editable_edit")) 
                        $.rleditor.menu.edit_editable(id); else
                    if ($(this).is(".rlm_editable_clear"))
                        $.rleditor.menu.clear_editable(id); else
                    if ($(this).is(".rlm_fragment_delete"))
                        $.rleditor.menu.remove_fragment(id); else
                    if ($(this).is(".rlm_editable_add_template"))
                        $.rleditor.menu.add_fragment(id, name); else
                    if ($(this).is(".rlm_editable_add_root"))
                        $.rleditor.menu.add_fragment_root(id, name, y); else
                    if ($(this).is(".rlm_element_resize"))
                        $.rleditor.menu.edit_size(id); else
                    if ($(this).is(".rlm_element_margin")) 
                        $.rleditor.menu.edit_margin(id);
                    if ($(this).is(".rlm_element_padding")) 
                        $.rleditor.menu.edit_padding(id);
                    
                    for (var i in ops.templates)
                        if (typeof ops.templates[i].menuClick == "function")
                            ops.templates[i].menuClick.apply(this, [$("#" + id)]);
                },
                onClose: function() {
                    $("#rle_overlay").hide();    
                }
            });
            menu.find("li").mouseenter(function() {
                var n = $(this).attr("data-target");
                if (n) $.rleditor.show_overlay(n);
            }).mouseleave(function() {
                $("#rle_overlay").hide();    
            });
            menu.children("li").each(function(i) {
                $(this).children("span").css("margin-left", (i * 20) + "px");    
            });
        },
        create_resizers: function(fragment) {
            fragment.findExclude(".rle_resizer", ".rle_fragment").each(function() {
                var resizer = $(this);
                //resizer.parent().css("position", "relative");
                var parent = resizer.parent();
                //parent.children().wrapAll("<div class='rle_resizer_wrapper'></div>");
                
                var prev = resizer.prev();
                var next = resizer.next();
                
                var x1 = prev.position().left + prev.outerWidth() + parseInt(prev.css("margin-left"));
                var x2 = next.position().left + parseInt(next.css("margin-left"));
                
                var pw = resizer.parent().width();
                var pos = (x1 + x2) / (2 * pw);
                pos = Math.round(pos * 200) / 2;
                
                resizer.css({"left" : pos + "%", "z-index" : 10}).attr("data-space", Math.round((x2-x1) * 200 / pw) / 2);
                $("<div/>").appendTo(resizer).mousedown($.rleditor.resize_start);
                $("<label>" + pos + "%</label>").appendTo(resizer);
            });    
        },
        create_fragment: function(edit, name, ops, target, new_frag, before) {
            var temp_ops = ops.templates[name];
            if (!temp_ops) return;
            
            if (!target)
                target = edit.children("div.rle_tree").children(".rle_fragments");  
                
            var level = parseInt(target.attr("data-level"));
            
            var temp_title = temp_ops.title;
            var fragment_id = "rle_" + $.rleditor.index++;
                               
            var html = "<li class='rle_fragment rle_node' data-name='" + name + "' data-level='" + level + "'>";
            html+= "<div>";
            html+= "<input type='hidden' data-name='template' value='" + name + "'>";
            
            html+= "<div class='rle_frag_header rle_template_icon dyntree_hook' data-name='" + name + "'>";
            html+= "<span class='rle_frag_title'>"; 
            html+= "<span>" + temp_title + "</span>";
            html+= "<sub></sub>"; 
            html+= "</span>";
            
            html+= "<span class='rle_frag_media' style='right: 45px'>";
            
            var o;
            for (var name in ops.media) {
                o = ops.media[name];
                html+= "<span data-media='" + name + "' style='background-color: " + o.color + "' title='" + name + "'></span>";
            }
            html+= "</span>";
            
            html+= "<a href='#' class='rle_frag_delete' onclick='return false' title='" + $.rleditor.translate("Delete", ops) + "' style='right: 5px'></a>";
            html+= "<a href='#' class='rle_frag_properties' onclick='return false' title='" + $.rleditor.translate("Properties", ops) + "' style='right: 25px'></a>";

            var button, button_title, button_class, right = 90;
            if (temp_ops.buttons) 
            for (var button_name in temp_ops.buttons) {
                button = temp_ops.buttons[button_name];
                button_title = (button.title) ? button.title : button_name;
                
                html+= "<a href='#' class='rle_frag_header_" + button_name + " ' data-name='" + button_name + "' onclick='return false' title='" + button["title"] + "' style='right: " + right + "px'></a>";
                
                right+= 20;
            }
            
            html+= "</div>";            
            html+= temp_ops.html;
            html+= "</div>";
            html+= "</li>";
            
            if (before)
                var fragment = $(html).insertBefore(before); else
                var fragment = $(html).appendTo(target);
                
            fragment.find(".rl_editable").addClass("rle_empty");
            fragment.dynamic_tree("register");
            fragment.attr("id", fragment_id);
            
            var editables = fragment.find(".rl_editable");
            var template = fragment.find(".rl_template");
            
            var parent = fragment.parents(".rle_fragment:first");
            if (!parent.length || !new_frag) {
                var temp_css = $.rleditor.format_style($.rleditor.parse_style(template, true), true);
                fragment.attr("style", temp_css);
            }
            template.removeAttr("style");                        
            
            fragment.data("rle_editables", editables);
            editables.each(function() {  
                if ($(this).attr("data-frags") == "true") {
                    $(this).append("<ul class='rle_fragments' data-level='" + (level+1) + "'></ul>");
                    $(this).dynamic_tree($.rleditor.dyntree_options);
                }
                
                $(this).attr("data-level", level);
                $(this).click(function(e) {
                    var edit = $(this).closest(".rl_editor");
                    if (edit.hasClass("rle_fullscreen")) return;
                        
                    if ($(this).hasClass("dyntree")) {
                        if (!$(this).hasClass("dyntree_parent")) { 
                            var edit = $(this).parents(".rl_editor:first");
                            var ops = edit.data("rleditor");
                            
                            $.rleditor.context_menu(edit, $(this), e.clientX, e.clientY, ops);
                
                            e.preventDefault();
                            return false;                              
                        }
                        
                        return;                            
                    }
                    var edit = $(this).parents(".rl_editor:first");
                    var ops = edit.data("rleditor");                                 

                    if (typeof ops.onEdit == "function")
                        ops.onEdit.apply(edit, [$(this), $(this).attr("data-editor")]);         
                        
                    e.stopPropagation();
                    return false;
                });
                $(this).addClass("rle_node").attr("id", "rle_" + $.rleditor.index++);
            });
            
            var header = fragment.children("div").children(".rle_frag_header");
            header.css("padding-right", right + "px");
            header.children("a").mousedown(function(e) { e.stopPropagation() });
            header.children("a.rle_frag_properties").click(function(e) {
                var frag = $(this).parents(".rle_fragment:first");
                var temp = frag.findExclude(".rl_template", ".rle_fragment");
                var edit = target.parents(".rl_editor:first");
                var ops = edit.data("rleditor");       
                var props = $.rleditor.get_properties(temp);
                
                if (typeof ops.onProperties == "function")
                    ops.onProperties.apply(edit, [temp, props, ops]);   

                e.stopPropagation();
                return false;
            });
            header.children("a.rle_frag_delete").click(function(e) {
                var edit = $(this).parents(".rl_editor:first");
                $(this).parents(".rle_fragment:first").dynamic_tree("remove");
                $.rleditor.output(edit);
                $.contextmenu_close();

                e.stopPropagation();
            });   
            
            if (temp_ops.buttons)
            for (button_name in temp_ops.buttons) {
                button = temp_ops.buttons[button_name];
                if (typeof button.click == "function") 
                    header.children("[data-name=" + button_name + "]").bind("click", button.click);    
            }
            
            fragment.bind("contextmenu", function(e) {
                var edit = $(this).parents(".rl_editor:first");
                if (edit.hasClass("rle_fullscreen")) return;
                
                var ops = edit.data("rleditor");
                $.rleditor.context_menu(edit, $(this), e.clientX, e.clientY, ops);
                
                e.preventDefault();
                return false;  
            });
            $(".rl_editable", fragment).bind("contextmenu", function(e) {
                var edit = $(this).parents(".rl_editor:first");
                if (edit.hasClass("rle_fullscreen")) return;
                
                var ops = edit.data("rleditor");
                $.rleditor.context_menu(edit, $(this), e.clientX, e.clientY, ops);
                
                e.preventDefault();
                return false;  
            });
            
            if (typeof temp_ops.onCreate == "function") 
                temp_ops.onCreate.apply(fragment, [temp_ops, new_frag]);
            
            return fragment;
        },
        load: function(edit, ops) {
            var input = edit.children(".rle_input");
            var root = edit.children(".rle_tree").children("ul");
            
            ops.load_timeout = null;            
            input.html(ops.input.val());
            
            var ins = input.children("ins");
            ops.content_id = ins.attr("data-cid");
            if (!ops.content_id) ops.content_id = "";
            ins.remove();
            
            input.children(".rl_template, .template").each(function() {
                $.rleditor.load_template($(this), root, 0, edit, ops);                             
            });
            root.find(".rl_image > img").each(function() {
                $(this).removeAttr("width height"); 
            }); 
            
            ops.load_timeout = setTimeout(function() {
                $.rleditor.output(edit);
            }, 100);
        },  
        load_template: function(template, parent, level, edit, ops, before) {
            var name = template.attr("data-name");
            var fragment = $.rleditor.create_fragment(edit, name, ops, parent, false, before);
            var orig_span = template.children(".rl_template_span");
            var temp_ops = ops.templates[name];
            
            var cls = $.rleditor.get_classes(template);
            var media = $.rleditor.get_media(template).join(","); 
            var href = template.attr("href");
            var temp = fragment.find(".rl_template"); 
            var span = temp.children(".rl_template_span");
            var temp_style = $.rleditor.parse_style(template, true);
            var span_style = $.rleditor.parse_style(orig_span, true);
            
            if (href)
                temp.attr("data-url", href);
                
            $.rleditor.set_attrs(temp, $.rleditor.get_attrs(template));
            $.rleditor.copy_elems(template, temp);

            fragment.attr("data-media", media);
            fragment.attr("style", $.rleditor.format_style(temp_style, true));
            
            span.attr("style", $.rleditor.format_style(span_style, true));
            
            fragment.children(".dyntree_item").children(".rle_frag_header").find(".rle_frag_media > span").each(function() {
                var m = $(this).attr("data-media");
                if (!media || media.indexOf(m) != -1)
                    $(this).removeClass("disabled"); else
                    $(this).addClass("disabled");
            });            
            
            var collapse = parseFloat(template.attr("data-collapse"));
            if (isNaN(collapse)) collapse = "1.0";
            
            temp.attr("data-collapse", collapse);
            temp.attr("data-id", template.attr("id"));
            temp.addClass(cls.join(" "));
            
            cls = media.replace(/,/g, " rl_media_");
            if (cls) cls = "rl_media_" + cls;            
            temp.addClass(cls);
            
            // temp.attr("style", template.attr("style")); 
            // temp.css({"margin" : 0});
            temp.find("[data-name]").each(function() {
                var orig = template.findExclude("[data-name=" + $(this).attr("data-name") + "]", ".rl_template");
                $(this).attr("style", orig.attr("style"));
                
                if ($(this).hasClass("rl_editable")) {
                    if (orig.hasClass("rl_frame")) $(this).addClass("rl_frame");
                    if ($(this).attr("data-frags") == "true") {
                        var temps = orig.children(".rl_template");
                        for (var i = 0; i < temps.length; i++)
                            $.rleditor.load_template($(temps[i]), $(this).children("ul"), level+1, edit, ops);
                    } else {
                        $(this).html(orig.html());  
                    }
                } 
            });   
            
            if (typeof temp_ops.onLoad == "function")
                temp_ops.onLoad.apply(fragment, [template, level, ops]);
            
            $.rleditor.create_resizers(fragment);      
        },      
        resize_start: function(e) {
            $("body").addClass("prevent_selection");
            var resizer = $(this).parent();
            var prev = resizer.prev();
            var next = resizer.next();
            
            var pw = resizer.parent().width();
            var prev_pos = prev.position().left;
            var next_pos = next.position().left;
            var prev_width = prev.outerWidth();
            var next_width = next.outerWidth();
            
            var prev_m1 = parseInt(prev.css("margin-left"));
            var prev_m2 = parseInt(prev.css("margin-right"));
            var next_m1 = parseInt(next.css("margin-left"));
            var next_m2 = parseInt(next.css("margin-right"));
            
            var clip1 = Math.round((prev_pos + prev_m1) * 200 / pw) / 2;
            var clip2 = Math.round((pw - next_pos - next_m1 - next_width) * 200 / pw) / 2;
            
            var space = parseInt(resizer.attr("data-space"));
            var min1 = prev.css("min-width");
            var min2 = next.css("min-width");
            
            if (min1.indexOf("%") == -1) 
                min1 = Math.round(parseInt(min1) * 200 / pw) / 2; else
                min1 = parseInt(min1);

            if (min2.indexOf("%") == -1) 
                min2 = Math.round(parseInt(min2) * 200 / pw) / 2; else
                min2 = parseInt(min2);            
           
            var min_x = min1 + clip1 + space/2;
            var max_x = 100 - min2 - clip2 - space/2;
            
            $.rleditor.resizing = {
                editor: resizer.closest(".rl_editor"),
                resizer: resizer,
                clip1: clip1,
                clip2: clip2,
                space: space / 2,
                min_x: min_x,
                max_x: max_x
            }
        },  
        resize: function(e) {
            if (!$.rleditor.resizing) return;
            
            var r = $.rleditor.resizing;        
            var resizer = r.resizer;
            var parent = resizer.parent();
            var prev = resizer.prev();
            var next = resizer.next();
            
            var pw = parent.width();   
            var x = Math.round((e.pageX - parent.offset().left) * 200 / pw) / 2;
            
            if (x < r.min_x) x = r.min_x;
            if (x > r.max_x) x = r.max_x;
            
            var w1 = Math.round((x - r.space - r.clip1) * 2) / 2;
            var w2 = Math.round((100 - r.clip2 - (x + r.space)) * 2) / 2;
            
            prev.css("width", w1 + "%");
            next.css("width", w2 + "%");
            resizer.css("left", x + "%");
            resizer.children("label").html(x + "%");
            
            //var prev_name = prev.attr("data-name");
            //var next_name = next.attr("data-name");
            
            //var frag = resizer.parents(".rle_fragment:first");
            /*
            var widths = frag.data("rle_widths");
            if (prev_name) widths[prev_name] = w1 + "%";
            if (next_name) widths[next_name] = w2 + "%";  
            */
            
            if (r.editor.hasClass("rle_fullscreen"))
                $.rleditor.update_layout(r.editor);                    
            
            e.stopPropagation();    
        },
        resize_end: function(e) {
            if (!$.rleditor.resizing) return;
                    
            var edit = $.rleditor.resizing.resizer.parents(".rl_editor:first");
            $.rleditor.output(edit);
            
            $.rleditor.resizing = null;                    
            $("body").removeClass("prevent_selection");    
        },
        output: function(edit) {
            clearTimeout($.rleditor.output_timeout);
            $.rleditor.output_timeout = setTimeout(function() {
                $.rleditor.output_proc(edit);    
            }, 100);
        },
        output_proc: function(edit) {
            // console.log("output");
            var ops = edit.data("rleditor");
            var output = edit.children("div.rle_output").html('');
            var tree = edit.children("div.rle_tree");
            
            var tree_w = tree.width();
            var image,images = tree.find("img");
            
            output.show();
            
            var frags = tree.children("ul").children("li.rle_fragment");
            for (var i = 0; i < frags.length; i++) 
                $.rleditor.output_fragment($(frags[i]), output, 0, ops);
                
            output.hide(); 
            
            var output_html = output.html();
            if (!ops.content_id && ops.insert_cid) 
                ops.content_id = "content-" + new Date().getTime();
                
            if (output_html && ops.insert_cid)
                output_html = "<ins data-cid='" + ops.content_id + "'></ins>" + output_html;
            
            ops.input.val(output_html);
            ops.input_cid.val(ops.content_id);
        },
        output_fragment: function(fragment, parent, level, ops) {
            var name = fragment.attr("data-name");
            var temp_ops = ops.templates[name];
            if (temp_ops["html_" + ops.platform])
                var html = temp_ops["html_" + ops.platform].trim(); else
                var html = temp_ops.html.trim();
            
            var orig_temp = fragment.children("div").children(".rl_template");
            var orig_span = orig_temp.children(".rl_template_span");
            var url = orig_temp.attr("data-url");
            
            if (url) 
                html = html.replace(/<div/, "<a href='" + url + "'").replace(/<\/div>$/, "</a>");
                
            var classes = $.rleditor.get_classes(orig_temp);
            var media = $.rleditor.get_media(orig_temp, true);
            
            var temp = $(html);
            var span = temp.children(".rl_template_span");
            
            var temp_css = $.rleditor.format_style($.rleditor.parse_style(fragment, true), true);
            var span_css = $.rleditor.format_style($.rleditor.parse_style(orig_span, true), true);
            
            $.rleditor.set_attrs(temp, $.rleditor.get_attrs(orig_temp));
            $.rleditor.copy_elems(orig_temp, temp);
            
            var collapse = parseFloat(orig_temp.attr("data-collapse"));
            if (isNaN(collapse)) collapse = "1.0";
            
            if (ops.fixate_images) {
                var tree = fragment.parents("div.rle_tree:first");
                var tree_w = tree.width();   
                fragment.findExclude("img", ".rle_fragment").each(function() {
                    var w = $(this).width();
                    var h = $(this).height();
                    var rw = w / tree_w;
                    var width = rw * ops.fixate_width;                    
                    var height = width * h / w;

                    $(this).attr("width", parseInt(width)).attr("height", parseInt(height));
                }); 
            }
            temp.attr("data-collapse", collapse);
            temp.addClass(classes.join(" "));
            temp.addClass(media.join(" "));
            temp.addClass("rl_level_" + level);
            temp.attr("style", temp_css);
            temp.attr("id", orig_temp.attr("data-id"));
            temp.find(".rle_resizer").remove();
            temp.find("[data-name]").each(function() {
                var orig = fragment.findExclude("[data-name=" + $(this).attr("data-name") + "]", ".rle_fragment");
                $(this).attr("style", orig.attr("style"));
                if (orig.hasClass("rl_frame")) $(this).addClass("rl_frame");
                
                if ($(this).hasClass("rl_editable")) {
                    $(this).attr("data-level", level);
                    
                    $(this).removeAttr("data-editor data-frags");
                    $.rleditor.output_editable(orig, $(this), level, ops);
                }
            });
            
            span.attr("style", span_css);
            
            if (typeof temp_ops.onOutput == "function")
                temp_ops.onOutput.apply(fragment, [temp, level, ops]);
            
            temp.appendTo(parent);
        },
        output_editable: function(editable, parent, level, ops) {
            var ul = editable.children("ul.dyntree_root");
            if (ul.length) {
                var frags = ul.children("li.rle_fragment");
                for (var i = 0; i < frags.length; i++) 
                    $.rleditor.output_fragment($(frags[i]), parent, level+1, ops);                
            } else {
                parent.append(editable.html());
            } 
        }, 
        has_media: function(trg, filter) {
            media = trg.attr("data-media");
            if (!media) return true;
            
            for (var i in filter)
                if (media.indexOf(filter[i]) !== -1)
                    return true;
            
            return false;
        },
        filter_media: function(edit, filter) {
            var frags = edit.find(".rle_fragment");
            var frag,media;
               
            if (filter == "all") {
                frags.show();
                return;   
            }

            for (var i = 0; i < frags.length; i++) {
                frag = $(frags[i]);
                media = frag.attr("data-media");
                
                if (!media || media.indexOf(filter) !== -1) 
                    frag.show(); else
                    frag.hide();
            } 
        },
        set_properties: function(template, properties) {
            // console.log(properties);
            var props = $.extend({}, {
                id: "",
                url: "",
                classes: "",
                media: "",
                collapse: "1.0",
                inner_css: "",
                outer_css: "",
                attributes: {},
                elements: {},
            }, properties);
            
            var span = template.children(".rl_template_span");
            var frag = template.parents(".rle_fragment:first");
            
            if (props.id) {
                template.attr("data-id", "site_" + props.id); 
                frag.find(".rle_frag_title > sub").html("#site_" + props.id);
            } else {
                template.removeAttr("data-id");
                frag.find(".rle_frag_title > sub").html("");
            }
            
            if (props.url) 
                template.attr("data-url", props.url); else
                template.removeAttr("data-url");
            
            template.attr("data-collapse", props.collapse);
            var framed = template.hasClass("rl_framed");
            
            $.rleditor.set_attrs(template, props.attributes);
            $.rleditor.set_elems(template, props.elements);
            $.rleditor.set_media(template, props.media);
            $.rleditor.set_classes(template, props.classes);
            
            template.toggleClass("rl_framed", framed);
            
            var inner_css = $.rleditor.format_style(props.inner_css, true);
            var outer_css = $.rleditor.format_style(props.outer_css, true);
            
            frag.attr("style", outer_css);
            span.attr("style", inner_css);
            
            var editor = template.parents(".rl_editor:first");
            var ops = editor.data("rleditor");
            var name = template.attr("data-name");
            if (typeof ops.templates[name].onUpdate == "function") {
                ops.templates[name].onUpdate(template, props);    
            }
                
            $.rleditor.output(template.closest(".rl_editor"));
        },
        get_properties: function(template) {
            var url = template.attr("data-url");
            if (!url) url = "";
            
            var id = template.attr("data-id");
            if (id) id = id.replace("site_", ""); else id = "";
            
            var span = template.children(".rl_template_span");
            var frag = template.parents(".rle_fragment:first");
            
            var outer_css = $.rleditor.format_style($.rleditor.parse_style(frag, true), false, true);
            var inner_css = $.rleditor.format_style($.rleditor.parse_style(span, true), false, true);
            
            var collapse = parseFloat(template.attr("data-collapse"));
            if (isNaN(collapse)) collapse = 1.0;
            
            return {
                id: id,
                url: url,
                collapse: collapse,
                classes: $.rleditor.get_classes(template),
                media: $.rleditor.get_media(template),
                outer_css: outer_css,
                inner_css: inner_css,
                elements: $.rleditor.get_elems(template),
                attributes: $.rleditor.get_attrs(template),
            }
        },
       
        create: function(ops) {
            ops = $.extend({}, $.rleditor.options, ops);
            for (var name in ops.templates)
                ops.templates[name] = $.extend({}, $.rleditor.template_options, ops.templates[name]);            
                
            if (ops.media)
            for (var name in ops.media)
                ops.media[name] = $.extend({}, $.rleditor.media_options, ops.media[name]);  
                
            ops.template_list = ops["template_list_" + ops.platform];
                
            var cont = $("#rle_cont");
            if (!cont.length)
                $("<div id='rle_cont'></div>").prependTo("body");
                
            var html = "";
            html+= "<div class='rle_tree'>";
            html+= "<ul class='rle_fragments rle_root' data-level='0'>";
            html+= "</div>";
            html+= "</ul>";
            
            html+= "<div class='rle_menu'>";
            html+= "<ul class='rle_add_fragment'>";
            var def_template = null;
            
            var name;
            for (var i in ops.template_list) {
                name = ops.template_list[i];
                if (def_template === null)
                    def_template = name;  
                    
                if (!ops.templates[name].root) continue;
                
                html+= "<li data-value='" + name + "'><div class='rle_addfrag_item rle_template_icon' data-name='" + name + "'>" + ops.templates[name].title + "</div></li>";    
            }
            html+= "</ul>";
            if (ops.media) {
                html+= "<ul class='rle_media_select' data-value='all'>";
                html+= "<li data-value='all'>Media: all</li>";
                for (var name in ops.media) {
                    html+= "<li data-value='" + name + "'>Media: " + name + "</li>";                        
                }
                html+= "</ul>";
            }
            
            html+= "</div>";
            html+= "<div class='rle_input' style='display: none'></div>";
            html+= "<div class='rle_output'></div>";
            html+= "<div class='rle_clipboard'></div>";
             
            ops.def_template = def_template;
            
            $(this).addClass("rl_editor");
            $(this).append(html);
            
            var menu = $(this).children(".rle_menu");
            menu.children("a.headers").click(function() {
                var edit = $(this).closest(".rl_editor");
                if (edit.hasClass("rle_hide_headers"))   
                    edit.removeClass("rle_hide_headers"); else
                    edit.addClass("rle_hide_headers"); 
            });

            menu.children("a.layout").click(function() {
                var edit = $(this).closest(".rl_editor");
                $.rleditor.fullscreen_open(edit);
            });
            
            menu.children("a.input").click(function() {
                var edit = $(this).closest(".rl_editor");
                var ops = edit.data("rleditor");
                
                if (ops.input.is(":visible"))
                    ops.input.hide(); else
                    ops.input.show();
            });
            
            
            $(this).data("rleditor", ops);
            $(this).children("div.rle_tree").dynamic_tree($.rleditor.dyntree_options);
            
            menu.children(".rle_add_fragment").ac_select({
                place_holder: $.rleditor.translate("Add template", ops)    
            }).change(function() {
                var edit = $(this).parents(".rl_editor:first");
                var name = $(this).val();
                var frag = $.rleditor.create_fragment(edit, name, edit.data("rleditor"), null, true);
                $.rleditor.create_resizers(frag);
                
                $.rleditor.output(edit);
                $(this).ac_value('');     
            });
            menu.children(".rle_media_select").ac_select({
            }).change(function() {
                var edit = $(this).parents(".rl_editor:first");
                $.rleditor.filter_media(edit, $(this).val().replace("rl_media_", ""));
            });            
            
            var frags = $(".rle_fragments", this);
            /*
            $(this).children(".rle_clipboard").children("textarea").pastableTextarea().bind('pasteText', function(event, text) {
                console.log(text); 
            });  */
            
            $(this).bind("contextmenu", function(e) {
                var edit = $(this);
                var ops = edit.data("rleditor");
                
                $.rleditor.context_menu(edit, $(this), e.clientX, e.clientY, ops);
                
                e.preventDefault();
                return false;  
            });            
            
            //if ($.isArray(ops.content) && ops.content.length) 
                //$.rleditor.load_content($(this), ops.content, ops, frags); else
            $.rleditor.load($(this), ops); 
            //if (ops.placeholder)     
              //  $.rleditor.create_fragment($(this), ops.placeholder, ops, frags).addClass("rle_placeholder");
                
            $.rleditor.output($(this));
            if (!$.rleditor.global_events) {
                $(window).mousedown(function(e) {
                    $("#rle_overlay").hide();
                    
                    $(".elemlayout").remove();
                    $(".resizer").remove();
                });    
                $(document).mousemove($.rleditor.resize);
                $(document).mouseup($.rleditor.resize_end);
                
                $.rleditor.global_events = true;
            }
            
            if (typeof ops.onLoaded == "function")
                ops.onLoaded.apply(this);

        },
        methods: {
            set_content: function(args) {
                var ok = ($(this).is(".rl_editable") && args.length > 1 && args[0] == "set_content");
                if (!ok) return false;
                
                var content = args[1].trim();
                
                $(this).html(content);
                if (content)
                    $(this).removeClass("rle_empty"); else
                    $(this).addClass("rle_empty");
                
                $(this).find("img").hide().bind("load", function() {
                    var img = $(this)[0];
                    $(this).css("max-width", img.naturalWidth + "px").show();
                    $(this).attr("data-width", img.naturalWidth);
                    $.rleditor.output($(this).parents(".rl_editor:first"));
                });
                $.rleditor.output($(this).parents(".rl_editor:first"));
                
                $(this).parents(".rle_fragment:first").removeClass("rle_placeholder");
                
                return { result: $(this) };
            },
            create: function(args) {
                var ok = (args.length == 1 || (args.length > 1 && args[0] == "create"));
                if (!ok) return false;
                
                var ops = (args.length > 1) ? args[1] : args[0];
                var edit = $.rleditor.create.apply(this, [ops]);
                   
                return { result: edit };
            },    
        }
    }
    $.fn.responsive_layout_editor = function() {
        var res;
        for (var method in $.rleditor.methods) {
            res = $.rleditor.methods[method].apply(this, [arguments])
            if (typeof res != "undefined" && res !== false) 
                return res.result;
        }
    }
    $.fn.rl_editor = function() {
        var res;
        for (var method in $.rleditor.methods) {
            res = $.rleditor.methods[method].apply(this, [arguments])
            if (typeof res != "undefined" && res !== false) 
                return res.result;
        }
    }
})( jQuery );        

