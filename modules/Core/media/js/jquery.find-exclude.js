(function( $ ){   
    $.fn.findExclude = function(selector, mask, result) {
        result = typeof result !== 'undefined' ? result : new jQuery();
        this.children().each( function(){
            thisObject = jQuery( this );
            if( thisObject.is( selector ) ) 
                result.push( this );
            if( !thisObject.is( mask ) )
                thisObject.findExclude( selector, mask, result );
        });
        return result;
    }
})( jQuery );        




