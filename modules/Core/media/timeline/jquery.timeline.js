(function( $ ){   
    $.timeline = {
        date2time: function (v) {
            if (v == '') return 0;
            
            v = v.split('.');
            if (v.length != 3) return 0;
            
            var d = new Date(v[2],v[1]-1,v[0]);
            return d.getTime() / 1000;
        }    
    }
	$.fn.timeline = function(options) {
		var e = null;
		
        var base;
        if (!options.start) {
            var d = new Date();
            d.setHours(0, 0, 0, 0);
            base = d.getTime() / 1000; 
        } else
            base = $.timeline.date2time(options.start); 
        
		if (typeof options.end != "undefined") 
			e = ($.timeline.date2time(options.end) - base) / (60 * 60 * 24);
		                                             
		var ops = $.extend({
			offset: 0,
			format: function(src, offset, ops, mode, event) {
				var t = (ops.base + offset * 60 * 60 * 24) * 1000;
				
				if (mode == "marker")
					return dateFormat(t, "d.m"); else
				if (mode == "event") {       
                    if (typeof ops.format_event == "function")
                        return ops.format_event(src, event, t); else          
				        return ops.sequence[event.type].title + " <span style='color: silver'>" + dateFormat(t, "d.m") + "</span>"; 
                } else
					return dateFormat(t, "ddd, d.m.yyyy");
			},
			regions: function(d1, d2, s) {
				var ms1 = s.base * 1000 + d1 * 60 * 60 * 24 * 1000;
				var ms2 = s.base * 1000 + d2 * 60 * 60 * 24 * 1000;
				
				var dd1 = new Date(ms1);
				var dd2 = new Date(ms2);
				
				var m1 = dd1.getYear() * 12 + dd1.getMonth();
				var m2 = dd2.getYear() * 12 + dd2.getMonth();
				
				var t1,t2,s1,s2,dc,dw,cm,days,c,cp1,cp2,cp,regions = [];
				for (i = 0; i <= m2-m1; i++) {
					t1 = new Date(ms1);
					t1.setDate(1);
					t1.setMonth(t1.getMonth() + i);
					t1.setMinutes(0);
					t1.setHours(0);
					t1.setSeconds(0);
					t1.setMilliseconds(0); 
					
					t2 = new Date(t1);
					t2.setMonth(t2.getMonth() + 1);
					
					s1 = t1.getTime() / 1000 - s.base;
					s2 = t2.getTime() / 1000 - s.base;
					
					s1 = Math.round(s1 / (60 * 60 * 24));
					s2 = Math.round(s2 / (60 * 60 * 24));
					
					t2.setDate(0);
					dc = t2.getDate();
					dw = t1.getDay();
					
					cm = -1;
					days = [{"dw" : dw, "ofs" : 0, "dur" : 1}];
					for (var j = 1; j < dc; j++) {
						dw = (dw + 1) % 7;
						
						c = days[days.length-1];
						cp1 = (c.dw == 0 || c.dw == 6) ? 0 : 1;
						cp2 = (dw == 0 || dw == 6 ) ? 0 : 1;
						
						if (cp1 != cp2) 
							days.push({"dw" : dw, "ofs" : j, "dur" : 1}); else
							days[days.length-1].dur++;
					}
					
					var dhtml = '';
					for (var j = 0; j < days.length; j++) {
						c = days[j];
						cp = (c.dw == 0 || c.dw == 6) ? 0 : 1; 
						
						if (cp == 0)
						dhtml+= "<div class='axis_positioned timeline_weekend' data-ofs='" + (c.ofs + s1) + "' data-dur='" + c.dur + "'></div>";                                               						
					}
					
					var region = {
						start: s1, 
						end: s2,
						title: dateFormat(t1, "mmmm, yyyy"),
						interval: (typeof options.interval != "undefined") ? options.interval : 2,
						html: dhtml,
					}
					regions.push(region);            
				}
				return regions;    
			},
            format_event: options.format_event,
			base: base,
            tl_onEventAdd: options.onEventAdd,
            tl_onEventMove: options.onEventMove
		}, options);
        
		ops.offset_end = e;
		ops.sequence = options.sequence;
		ops.events = [];
		ops.onEventAdd = function(event, ops) {
			var t = ops.base * 1000 + event.offset * 60 * 60 * 24 * 1000;
			event.date = dateFormat(t, "d.m.yyyy");		
			
			if (typeof ops.tl_onEventAdd == "function")
				ops.tl_onEventAdd.apply(this, [event, ops]);
		}
		ops.onEventMove = function(event, ops) {
			var t = ops.base * 1000 + event.offset * 60 * 60 * 24 * 1000; 
			event.date = dateFormat(t, "d.m.yyyy");        

			if (typeof ops.tl_onEventMove == "function")
				ops.tl_onEventMove.apply(this, [event, ops]);
		}
		
		if (typeof options.events != "undefined") {
		    for (var i = 0; i < options.events.length; i++) {
			    var event = options.events[i];	
			    event.offset = ($.timeline.date2time(event.date) - base) / (60 * 60 * 24);
			    ops.events.push(event);
		    }
        }
		
		$(this).axis(ops);
	};
})( jQuery );        


