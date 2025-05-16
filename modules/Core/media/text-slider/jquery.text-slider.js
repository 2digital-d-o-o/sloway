(function( $ ){   
    $.text_slider = {
        def_options: {
            spacing: 200
        },
        step: function(ul) {
            var li = ul.children("li:first");
            
            var li_w = li.outerWidth();
            var ul_x = parseInt(ul.css("margin-left"));
            
            if (ul_x < -li_w) {      
                li.remove().appendTo(ul);
                
                ul_x+= li_w;                       
                ul.css("margin-left", ul_x + "px");
            } 
            
            ul_x = ul_x - 80;    
            
            ul.animate({"margin-left" : ul_x + 'px'}, 1000, "linear", function() {
                $.text_slider.step($(this));
            });
        }        
    };
    $.fn.text_slider = function(options) {
        $(this).addClass("text_slider");
        
        var ul = $(this).children("ul");
        var ul_w = ul.outerWidth();
        if (ul_w) {    
            var sw = $(this).innerWidth();
            var html = ul.html();
            //ul.css("margin-left", sw + "px");
            
            var cnt = parseInt(sw * 2 / ul_w);
            for (var i = 0; i < cnt-1; i++)
                ul.append(html);
                
            ul.children("li").css("padding-right", 200);
                           
            $.text_slider.step(ul);
            
            ul.hover(function() {            
                $(this).stop().addClass("ts_paused");
                
            }, function() {
                $(this).removeClass("ts_paused");
                $.text_slider.step(ul);
            });
        } 
    };
})( jQuery );        


