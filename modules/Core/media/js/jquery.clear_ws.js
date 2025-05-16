(function( $ ){   
	$.fn.clear_ws = function() {
		$(this).contents().filter(function() {
			return this.nodeType == 3; 
		}).remove();        
	};
})( jQuery );        


