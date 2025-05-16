(function( $ ){  
    $.rleditor.options.templates.gallery = {
        list_ops: {
            edit_title: true,
            template_class: "rl_template_gallery"
        },
        buttons: {
            add: { 
                title: 'Add image', 
                click: function(e) {
                    $.rleditor.image_list.add.apply(this, [e, $.rleditor.options.templates.gallery.list_ops]);
                }
            },
            del: { 
                title: 'Remove selected', 
                click: function(e) {
                    $.rleditor.image_list.remove.apply(this, [e, $.rleditor.options.templates.gallery.list_ops])
                }
            }        
        },
        onOutput: function(target, level, ops) {
            $.rleditor.image_list.output.apply(this, [target, level, ops, $.rleditor.options.templates.gallery.list_ops]);
        },
        onCreate: function(data, ops) {
            $.rleditor.image_list.create.apply(this, [data, ops, $.rleditor.options.templates.gallery.list_ops]); 
        },
        onLoad: function(template, level, ops) {
            $.rleditor.image_list.load.apply(this, [template, level, ops, $.rleditor.options.templates.gallery.list_ops]);
        }
    }
})( jQuery ); 

