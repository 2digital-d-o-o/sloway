(function( $ ){
    $.fn.scrollTo = function(ofs, ops) {
        $('html, body').animate({
            scrollTop: ofs
        }, ops.duration);    
    }
})( jQuery );