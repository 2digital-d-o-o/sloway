(function( $ ){   
    $.utils = {
        resize_event: null,
        resize_timeout: null,
        resize_callbacks: [],  
               
        fixed_header_height: 0,
        google_map: {
            grayscale: [{"featureType":"landscape","stylers":[{"saturation":-100},{"lightness":65},{"visibility":"on"}]},{"featureType":"poi","stylers":[{"saturation":-100},{"lightness":51},{"visibility":"simplified"}]},{"featureType":"road.highway","stylers":[{"saturation":-100},{"visibility":"simplified"}]},{"featureType":"road.arterial","stylers":[{"saturation":-100},{"lightness":30},{"visibility":"on"}]},{"featureType":"road.local","stylers":[{"saturation":-100},{"lightness":40},{"visibility":"on"}]},{"featureType":"transit","stylers":[{"saturation":-100},{"visibility":"simplified"}]},{"featureType":"administrative.province","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"labels","stylers":[{"visibility":"on"},{"lightness":-25},{"saturation":-100}]},{"featureType":"water","elementType":"geometry","stylers":[{"hue":"#ffff00"},{"lightness":-25},{"saturation":-97}]}],
            key: "AIzaSyBnBO4mjWVLOXTFY3Gl7YqDli0Mi_qVqHo",
        },        
        gapi_maps_show: function(target, lat, lng, address, ops) {
            var directionsDisplay = new google.maps.DirectionsRenderer();       
            
            var pos = new google.maps.LatLng(lat,lng);
            var options = $.extend({
                zoom: 15,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                center: pos,
                scrollwheel: false
            }, ops);

            var map = new google.maps.Map(target, options);
            directionsDisplay.setMap(map);
            
            
            var marker = new google.maps.Marker({
                position: pos,
                map: map,
                title: address,
            });           
            
            $(target).addClass("google_map");
        },
        gapi_maps_autocomplete: function(target, changed) {
            var ac = new google.maps.places.Autocomplete(target, { types: ['geocode'] });
            ac._callback = changed;
            ac._target = target;
            
            google.maps.event.addListener(ac, 'place_changed', function() {   
                var place = this.getPlace();

                var lat = place.geometry.location.lat();
                var lng = place.geometry.location.lng();
                
                if (typeof this._callback == "function") 
                    this._callback.apply(this._target, [lat, lng, place]);
            });     
        }
    }
    $.google_maps_load = function(loaded) {
        if (typeof google == "object" && google.maps) {    
            if (typeof loaded == "function") loaded();
            
            return;
        }
        
        var key = $.utils.google_map.key;
        if (!key) 
            key = $("head").children("meta[name=gm_key]").attr("content");
                    
        jQuery.getScript("//maps.googleapis.com/maps/api/js?sensor=false&language=en&libraries=places&key=" + key, function() {
            if (typeof loaded == "function") loaded();
        });     
    }
    $.fn.google_map = function(lat, lng, address, style) {
        var target = $(this).get(0);
        if (!target) return;
        
        $.google_maps_load(function() {
            $.utils.gapi_maps_show(target, lat, lng, address, style);
        });
    }
    $.fn.google_map_autocomplete = function(callback) {
        var target = $(this).get(0);
        if (!target) return;
        
        $.google_maps_load(function() {
            $.utils.gapi_maps_autocomplete(target, callback);
        });
    }
    $.window_resize = function(callback) {
        $.utils.resize_callbacks.push(callback);
        $(window).resize(function(e) {
            clearTimeout($.utils.resize_timeout);
            
            $.utils.resize_event = e;
            $.utils.resize_timeout = setTimeout(function() {
                for (var i = 0; i < $.utils.resize_callbacks.length; i++) 
                    $.utils.resize_callbacks[i].apply(window, [$.utils.resize_event]);
            }, 20);
        });
    }
    $.hash_set = function(val) {
        var hash = window.location.hash;
        if (hash.indexOf(val) == -1)
            hash+= val;
        
        window.location.hash = hash;
    }
    $.hash_get = function(val) {
        return window.location.hash.indexOf(val) != -1;
    }
    $.hash_rem = function(val) {
        var hash = window.location.hash;
        hash = hash.replace(val, "");
        window.location.hash = hash;
    }
    $.fn.css_val = function(n) { 
        r = $(this).css(n);
        return parseInt(r.replace('px',''));
    }

    $.fn.box_width = function(n) {
        r = $(this).css_val('width') + 
            $(this).css_val('padding-left') + 
            $(this).css_val('border-left-width') + 
            $(this).css_val('margin-left') + 

            $(this).css_val('padding-right') + 
            $(this).css_val('border-right-width') + 
            $(this).css_val('margin-right');
        
        return r;
    }

    $.fn.box_height = function(n) {
        r = $(this).css_val('height') + 
            $(this).css_val('padding-top') + 
            $(this).css_val('border-top-height') + 
            $(this).css_val('margin-top') + 

            $(this).css_val('padding-bottom') + 
            $(this).css_val('border-bottom-height') + 
            $(this).css_val('margin-bottom');
        
        return r;
    }   
    
    $.fn.clear_ws = function() {
        $(this).contents().filter(function() {
            return this.nodeType == 3; 
        }).remove();        
    };
    
    $.mod_int = function(value, size) {
        var r = value % size;
        if (r < 0) 
            r = size + r;
        
        return r;
    }
    $.s_curve = function(x, x1,x2,y1,y2) {
        if (x < x1) return y1;
        if (x > x2) return y2;
        
        var y = (Math.cos(Math.PI * (x - x1) / (x2-x1)) + 1) / 2;
        return y2 + (y1 - y2) * y;
    }    
    $.fn.scrollToElem = function() {
        if (!$(this).length) return;    
        
        var pos = $(this).offset().top - $.utils.fixed_header_height;
        $(window).scrollTo(pos, 400);
    }
    $.fn.serializeObject = function() {
      var arrayData, objectData;
      arrayData = this.serializeArray();
      objectData = {};

      $.each(arrayData, function() {
        var value;

        if (this.value != null) {
          value = this.value;
        } else {
          value = '';
        }

        if (objectData[this.name] != null) {
          if (!objectData[this.name].push) {
            objectData[this.name] = [objectData[this.name]];
          }

          objectData[this.name].push(value);
        } else {
          objectData[this.name] = value;
        }
      });

      return objectData;
    };    
})( jQuery );  

