(function( $ ){   
    $.axis.on_create = function(ops) {
        $(this).bind("contextmenu", function(e) {        
            var ops = $(this).data("axis");
            var x = e.pageX - $(this).offset().left;
            var ofs = $.axis.read($(this), x, true);  
            
            $.axis.add_offset = ofs;  
                
            var menu = [];
            var types = $(this).axis("valid_types", ofs);
            var typ;
            for (var i = 0; i < types.length; i++) {
                typ = types[i];
                menu.push({
                    content: "Add " + ops.sequence[typ].title,
                    name: typ
                });
            }
        
            if (menu.length) 
            $(this).contextmenu(e.clientX, e.clientY, menu, {
                onClick: function(trg, name) {
                    trg.axis("add_event", name, $.axis.add_offset);        
                }                                               
            });        
            
            e.preventDefault();
            return false;          
        });
    }
    $.axis.on_build = function(ops) {
        var main = $(this).children(".axis_main");
        var events = main.children(".axis_events");
        
        events.find(".axis_event_hook").bind("contextmenu", function(e) {
            var menu = [{ content: "Remove", name: "remove"}];
            $(this).contextmenu(e.clientX, e.clientY, menu, {
                onClick: function(trg, name) {
                    var event = trg.closest(".axis_event");
                    var axis = event.closest(".axis");
                    
                    axis.axis("rem_event", event);
                }                                               
            }); 
            
            e.preventDefault();
            return false;         
        });
    }
})( jQuery );