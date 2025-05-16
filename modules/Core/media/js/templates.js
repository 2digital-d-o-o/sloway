var template_options;

function layout_templates() {
    $(".template").each(function() {
        $(this).removeClass("tls_small tls_medium");
        
        var scale = parseFloat($(this).attr("data-scale"));
        var twidth = $(this).width();
        var pwidth = template_options.max_width * scale;
        
        var rel = twidth / pwidth;
        
        $(this).attr("data-temp", twidth + " " + pwidth.toFixed(2) + " " + rel.toFixed(2));
        if (rel <= template_options.sizes.small)
            $(this).addClass("tls_small"); else
        if (rel <= template_options.sizes.medium)
            $(this).addClass("tls_medium"); 
    });
}

$(document).ready(function() {
    
    /*
    var max_w = parseInt($("meta[name=templates]").attr("content"));
    if (isNaN(max_w)) max_w = 980;
    
    template_options["max_width"] = max_w;
    
    $(".template").each(function() {
        var parent = $(this).parents(".template:first");
        var mul, name, scale = 1;
        while (parent.length) {
            name = parent.attr("data-name");
            mul = parseFloat(template_options["scale"][name]);
            if (isNaN(mul)) mul = 1;
            
            scale = scale * parseFloat(mul);
            
            parent = parent.parents(".template:first");
        }
        
        $(this).attr("data-scale", scale.toFixed(2));
    });
    
    layout_templates();
    
    $(window).resize(function() {
        layout_templates(); 
    });     */
});