function rl_section_toggle() {
    var s = $(this).closest(".rl_template_section");
    var p = $(this).parent();
    var c = p.children(".rl_section_content").stop();
    if (s.hasClass("rl_section_expanded")) {
        s.removeClass("rl_section_expanded");
        c.slideUp(); 
    } else {
        c.hide().css("height", "auto");
        s.addClass("rl_section_expanded");
        c.slideDown(function() {
            $(this).responsive_layout();
        });
    }    
}