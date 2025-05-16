$(document).ready(function() {
    window.History.Adapter.bind(window, 'statechange', function() {
        state = window.History.getState();
        
        if ($.history.suppress_change) {
            $.history.suppress_change = false;
            return false;
        }
        
        $.history.suppress_change = false;
        
        if (state.data.id) 
            $("#" + state.data.id).trigger("stateChanged", [state.url, state.data.param]);
    });
});

(function( $ ){   
    $.history = {
        suppress_change: false
    }

    $.fn.initialState = function(data) {
        if (typeof data == "undefined")
            data = {};
        data.id = $(this).attr("id");

        $.history.suppress_change = true;
        window.History.replaceState(data);
        $.history.suppress_change = false;
    }
    
    $.fn.pushState = function(url, title, param) {
        var data = {
            id: $(this).attr("id"),
            param: param
        }
        if (typeof title == "undefined") title = "";

        $.history.suppress_change = true;
        window.History.pushState(data, title, url);
        $.history.suppress_change = false;
    }      
    
    $.fn.replaceState = function(url, title, param) {
        var data = {
            id: $(this).attr("id"),
            param: param
        }
        if (typeof title == "undefined") title = "";
        
        $.history.suppress_change = true;
        window.History.replaceState(data, title, url);
        $.history.suppress_change = false;
    }
    
    $.pushState = function(url, title, param) {
        var data = {
            param: param
        }
        if (typeof title == "undefined") title = "";
            
        $.history.suppress_change = true;
        window.History.pushState(null, title, url);
        $.history.suppress_change = false;
        
    }
    $.replaceState = function(url, title, param) {
        var data = {
            param: param
        }
        if (typeof title == "undefined") title = "";
        
        $.history.suppress_change = true;
        window.History.replaceState(null, title, url);
        $.history.suppress_change = false;
    }
})( jQuery );    

