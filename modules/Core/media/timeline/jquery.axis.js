(function( $ ){   
    $.axis = {
        def_options: {
            offset : 0,
            offset_end : null,
            unit : 50,
            loaded : null,
            zoom : 0,
            zoom_min : -3,
            zoom_max : 0.5,
            snap : 1,
            int_min : 50,
            format : null,
            regions : null,
            val_prefix : "",
            event_overflow : 3,
            
            onEventDeselect: null, // [event_data, ops]
            onEventSelect: null,   // [event_data, ops]
            onEventMove: null,     // [event_data, ops]
            onEventAdd: null,
            enEventRemove: null,
        },
        on_create: null,
        on_build: null,
        
        global_events: false,
        moving: null,
        id_counter: 0,
        get: function(src, def) {
            args = Array.prototype.slice.call( arguments, 2 );
            if (typeof src == "undefined")
                return def; else
            if (typeof src == "function")
                return src.apply(this, args); else
                return src;
        },           
        move: function(src, delta, offset, update) {  
            if (typeof offset == "undefined")       
                offset = parseFloat(src.attr("data-offset"));
                
            var scale = $.axis.calc_scale(src);
            offset+= delta / scale;      
                                
            src.attr("data-offset", offset.toFixed(6));
            if (typeof update == "undefined" || update)
                $.axis.update(src);  
        }, 
        zoom: function (src, s, update) {       
            var ops = src.data("axis");
            var zoom = parseFloat(src.attr("data-zoom")) + s;
            
            if (zoom < ops.zoom_min)
                zoom = ops.zoom_min;
            if (zoom > ops.zoom_max)
                zoom = ops.zoom_max;
            
            src.attr("data-zoom", zoom);
            if (typeof update == "undefined" || update)
                $.axis.update(src, true);  
        },         
        calc_scale: function(src) { 
            var zoom = parseFloat(src.attr('data-zoom'));
            var unit = parseInt(src.attr('data-unit'));
            var s;
            if (zoom >= 0) 
                s = 1 + zoom; else
                s = Math.exp(zoom);     
            
            return s * unit;
        },
        calc_interval: function(src, scale, mul) {  
            var int_min = parseInt(src.attr('data-int-min'));
            
            var result = 1;
            while (result * scale < int_min) 
                result = result * mul;
    
            return result;
        },
        clip: function(x,c1,c2) {
            if (x < c1) x = c1;
            if (x > c2) x = c2;
            
            return x;            
        },
        project: function(src, ofs) {
            var offset = parseFloat(src.attr("data-offset"));
            var scale = $.axis.calc_scale(src);
    
            return Math.round((ofs - offset) * scale);
        },        
        set_pos: function(src, x, ofs, update) {            
            var offset = parseFloat(src.attr("data-offset"));
            var scale = $.axis.calc_scale(src);
            
            var offset = ofs - x / scale; 
            src.attr("data-offset", offset.toFixed(6));
            
            if (typeof update == "undefined" || update)
                $.axis.update(src);  
        },
        read: function(src, x, snap) {
            var offset = parseFloat(src.attr("data-offset"));  
            var scale = $.axis.calc_scale(src);
            var res = offset + x / scale;
            
            if (snap) 
                res = $.axis.snap(src, res);
            
            return res;
        },   
        snap: function(src, ofs) { 
            var snap = parseFloat(src.attr("data-snap"));
            var ofs = Math.round(ofs / snap) * snap;
    
            return ofs;
        },
        
        event_data: function(src, ev) {
            var ind = ev.attr("data-ind");
            var ops = src.data("axis");
            
            return ops.events[ind];    
        },
        sort_events: function(src, ops) {
            ops.events.sort(function(e1,e2) { return e1.offset - e1.offset });
            var ev;
            for (var i = 0; i < ops.events.length; i++) {
                ev = ops.events[i];
                ev.index = i;
                $("#" + ev.id).attr("data-ind", i);   
            }
        },        
        move_event: function(src, event, pos) {
            var ofs = $.axis.read(src, pos, true);
            if (Math.abs(parseFloat(event.attr("data-ofs")) - ofs) < 0.0001)
                return;
                
            var ind = parseInt(event.attr("data-ind"));
            if (!$.axis.validate(src, ind, ofs))
                return;
            
            var x = $.axis.project(src, ofs);
            event.css("left", x + "px");
            event.attr("data-ofs", ofs.toFixed(6));
            
            var ops = src.data("axis");
            ops.events[ind].offset = ofs;
            $.axis.sort_events(src, ops);
            
            if (typeof ops.onEventMove == "function")
                ops.onEventMove.apply(event, [ops.events[ind], ops]);
                
            $.axis.update_events(src);         
        },
        validate: function(src, ind, ofs) {
            var ops = src.data("axis");
            if (!ops.sequence) return true;
            
            var events = ops.events.slice(0);
            events[ind].offset = ofs;
            events.sort(function(e1, e2) { 
                var d = e1.offset - e2.offset;
                if (Math.abs(d) > 0.0001)
                    return d;
            
                return e1.itype - e2.itype;
            });
            
            var keys = Object.keys(ops.sequence);
            var lind = -1;
            var event, tind; 
            for (var i = 0; i < events.length; i++) {
                event = events[i];
                tind = $.inArray(event.type, keys);
                if (tind == -1) continue;
                
                if (lind != -1 && tind < lind)
                    return false;
                
                lind = tind;
            } 
            
            return true;
        },        
        
        build: function(src, offset1, offset2, scale, ops) {
            offset1 = offset1 - 400 / scale;
            offset2 = offset2 + 400 / scale;
                
            var clip1 = Math.floor(offset1);
            var clip2 = Math.floor(offset2) + 1;
            var rhtml = "";
            
            regions = $.axis.get(ops.regions, null, offset1, offset2, ops);
            if (regions == null) {
                var s = Math.floor(offset1);
                if (s % 2 != 0) s--;
                regions = [{
                    interval: 2,
                    title : '',
                    start : s,
                    end : Math.floor(offset2) + 1,
                }];    
            }
            for (var i = 0; i < regions.length; i++) {
                var region = regions[i];
                
                var interval = $.axis.calc_interval(src, scale, $.axis.get(region.interval, 2));
                var rw = Math.round((region.end - region.start) * scale);
                var pos = $.axis.project(src, region.start);//src.axis("project", region.start);
                
                rhtml+= '<div class="axis_region" style="width: ' + rw + 'px; left: ' + pos + 'px" data-ofs="' + region.start.toFixed(6) + '">';
                rhtml+= '<div class="axis_region_title"><span>' + region.title + '</span></div>';
                
                if (typeof region.html != 'undefined')
                    rhtml+= region.html;
                
                rhtml+= '<div class="axis_scale">';
                
                var m1 = 0;
                var m2 = region.end - region.start;
                
                var c1 = clip1 - region.start;
                var c2 = clip2 - region.start;
                
                var m1 = Math.floor($.axis.clip(m1, c1, c2) / interval); 
                var m2 = Math.floor($.axis.clip(m2, c1, c2) / interval) + 1;
                
                var val, ip, iw;
                for (var j = m1; j < m2; j++) {
                    val = region.start + j * interval;
                    ip = Math.round((val - region.start) * scale);
                    iw = parseInt(interval * scale);
                    
                    if (typeof ops.format != "undefined")
                        val = ops.format(src, val, ops, "marker");
                    
                    rhtml+= '<div class="axis_scale_marker" style="width: ' + iw + 'px; left: ' + ip + 'px">' + val + "</div>";
                }
                                
                rhtml+= '</div>';
                rhtml+= '</div>';
            }
            
            rhtml+= '<div class="axis_events">';
            
            var events = $.axis.get(ops.events, []);
            for (var i = 0; i < events.length; i++) {
                var event = events[i];
                event.index = i;
                if (!event.id)
                    event.id = "axis_event_" + $.axis.id_counter++;
                
                var e_attr = $.axis.get(event.attr, "");
                var e_type = $.axis.get(event.type, null);
                var e_title = $.axis.get(event.title, "");
                var e_offset = $.axis.get(event.offset, null);
                
                if (e_offset === null) continue;
                if (e_type !== null)
                    e_attr += ' data-type="' + e_type + '"';
                    
                var seq = $.axis.get(ops.sequence, false);
                if (seq) {
                    var t = seq[e_type];
                    var k = Object.keys(seq);
                    if (typeof t != "undefined") {
                        e_title = $.axis.get(t.title, e_title);
                        event.itype = $.inArray(e_type, k);
                    }
                }
                
                var x = $.axis.project(src, e_offset);
                var a_title = e_title;
                
                if (typeof ops.format == "function" && (v = ops.format(src, e_offset, ops, "event", event)) != null)
                    e_title = v;
                
                rhtml+= '<div class="axis_event" id="' + event.id + '" style="left: ' + x + 'px" data-ofs="' + e_offset + '" data-ind="' + i + '" ' + e_attr + '>';
                rhtml+= '<div class="axis_event_title axis_event_hook">' + e_title + '</div>';
                rhtml+= $.axis.get(event.html, "");
                rhtml+= '</div>';    
            }  
            rhtml+= '</div>';
            
            rhtml+= "<div class='axis_cursor' style='display: none'></div>";
            
            var main = src.children(".axis_main");
            main.html(rhtml);
            main.children(".axis_region").children(".axis_positioned").each(function() {
                var region = $(this).parents(".axis_region:first");
                
                var ofs = parseFloat($(this).attr("data-ofs"));
                var dur = parseFloat($(this).attr("data-dur"));
                
                if (region.length)
                    ofs = ofs - parseFloat(region.attr("data-ofs"));
                
                $(this).css("left", ofs * scale + "px");
                if (dur)
                    $(this).css("width", dur * scale + "px");
            });
            for (var i = 0; i < events.length; i++) {
                $("#" + events[i].id).find(".axis_event_hook").bind("mousedown", function(e) {
                    var ev = $(this).closest(".axis_event")
                    var src = ev.closest(".axis");
                    var ops = src.data("axis");
                    
                    if (!ev.hasClass("axis_selected")) {
                        var sel = src.find(".axis_event.axis_selected");
                        if (sel.length) {
                            if (typeof ops.onEventDeselect == "function") 
                                ops.onEventDeselect.apply(ops, [$.axis.event_data(src, sel), ops]);
                                
                            sel.removeClass("axis_selected");    
                        }
                        if (typeof ops.onEventSelect == "function")                        
                            ops.onEventSelect.apply(ev, [$.axis.event_data(src, ev), ops]);
                    }
                    
                    $.axis.dragging = {
                        axis: src,
                        event: ev,
                        hook: $(this),
                        pick_ofs: e.pageX - $(this).offset().left
                    }
                    ev.addClass("axis_selected");
                    e.stopPropagation();
                }).dblclick(function() {
                    var ev = $(this).closest(".axis_event")
                    var src = ev.closest(".axis");
                    var ops = src.data("axis");
                    
                    if (typeof ops.onEventEdit == "function")                        
                        ops.onEventEdit.apply(ev, [$.axis.event_data(src, ev), ops]);
                });
            }
            
            ops.loaded = [offset1, offset2];    
            if (typeof $.axis.on_build == "function")
                $.axis.on_build.apply(src, [ops]);
        },
        update: function(src, regen) {
            if (!src.is(":visible")) 
                return;
                
            var t1 = new Date().getTime();

            var w = src.width();
            var ops = src.data("axis");
            var scale = $.axis.calc_scale(src);
            
            var offset1 = parseFloat(src.attr("data-offset"));     
            var offset2 = offset1 + w / scale; 
            
            if (typeof regen == "undefined")
                regen = (ops.loaded == null || offset1 < ops.loaded[0] || offset2 > ops.loaded[1]);
            
            if (regen) {  
                $.axis.build(src, offset1, offset2, scale, ops);
            } else {
                src.find(".axis_region").each(function() {
                    ofs = parseFloat($(this).attr("data-ofs"));
                    $(this).css("left", $.axis.project(src, ofs));
                });
            }
            
            src.find(".axis_region_title").each(function() {
                var x1 = $(this).offset().left;
                var x2 = $(this).parents(".axis_main:first").offset().left;
                var d = x1 - x2;
                
                var m = (d < 0) ? -d + 5 : 0;
                $("span", this).css("margin-left", m + "px"); 
            });
            
            $.axis.update_events(src);
            $.axis.update_cursor(src);
            
            var t2 = new Date().getTime();
            //console.log("update: ", t2 - t1);
        },   
        update_cursor: function(src) {
            var ops = src.data("axis");
            if (!ops) return;
            
            var cursor = src.find(".axis_cursor");
            
            var ofs = parseFloat(cursor.attr("data-offset"));
            var x = $.axis.project(src, ofs);
            
            if (typeof ops.format == "function")
                ofs = ops.format(src, ofs, ops, "cursor");
            
            cursor.css("left", x + "px");
            cursor.html("<span>" + ofs + "</span>");
            cursor.show();
        },
        update_events: function(src) {
            var ops = src.data("axis");
            
            if (!ops.events.length) return;
            
            var lanes = [];
            var fev = $("#" + ops.events[0].id).children(".axis_event_title");
            var eh = fev.outerHeight() - ops.event_overflow;
            
            var event, d_event, et, ex, ew;
            for (var i = 0; i < ops.events.length; i++) {
                var d_event = ops.events[i];
                var event = $("#" + d_event.id);
                var et = $(".axis_event_title", event)
                var ex = parseInt($(event).css("left").replace("px",""));
                var ew = et.outerWidth();
                
                var lx;
                for (var j = 0; j < lanes.length; j++) {
                    lx = lanes[j];
                    
                    if (lx === null || lx < ex) break;
                }    
                
                et.css("top", (j * eh - ops.event_overflow) + 'px');
                et.css("z-index", 20 + i);
                
                lanes[j] = ex + ew;
                
                var e_title = $.axis.get(d_event.title, "");
                var e_offset = $(event).attr("data-ofs");
                var e_type = $(event).attr("data-type");
                var e_value = e_type + ":" + e_offset;
                
                if (typeof ops.format == "function" && (v = ops.format(src, e_offset, ops, "event", d_event)) != null)
                    e_title = v;
                    
                et.html(e_title);
                $(event).css("left", $.axis.project(src, e_offset));
            }
            
            if (typeof ops.onUpdate == "function")
                ops.onUpdate.apply(src);
        },        
    }
	
	var methods = {   
		add_event: function(type, ofs) {
			var ops = $(this).data("axis");	
			
			var ev = {
                type: type, 
                offset: ofs 
            };
			
			ops.events.push(ev); 
            $.axis.sort_events($(this), ops);
            
            if (typeof ops.onEventAdd == "function")
                ops.onEventAdd.apply(this, [ev, ops]);
            
            $.axis.update($(this), true);			
		},
		rem_event: function(event) {
			var ind = parseInt(event.attr("data-ind"));
			var typ = event.attr("data-type");
							
			var ops = $(this).data("axis");  
			
			var ev = ops.events[ind];
            ops.events.splice(ind,1);
            $.axis.sort_events($(this), ops);

			if (typeof ops.onEventRemove == "function")
				ops.onEventRemove.apply(this, [ev, ops]);
			
            $.axis.update($(this), true);            
		},
        valid_types: function(ofs) {
            var ops = $(this).data("axis");
            var seq = ops.sequence;
            
            if (!seq) return [];
            
            var events = ops.events;
            var prev = null;
            var next = (events.length > 0) ? events[0] : null;
            for (var i = 0; i < events.length; i++) {
                if (ofs < events[i].offset) 
                    break;    
                    
                prev = events[i];
                next = (i < events.length-1) ? events[i+1] : null;
            }   
            
            var i1,i2;
            var keys = Object.keys(seq);
            if (prev) {
                var pind = $.inArray(prev.type, keys);
                if (pind != -1) {
                    i1 = pind + 1;
                    if (seq[prev.type].multiple === true)
                        i1--;
                }
            } else
                i1 = 0;
                
            if (next) {
                var nind = $.inArray(next.type, keys);
                if (nind != -1) {
                    i2 = nind;    
                    if (seq[next.type].multiple === true)
                        i2++;
                }
            } else
                i2 = keys.length;
            
            return keys.slice(i1,i2);
        },   
        update: function() {
            $.axis.update($(this));
        },     
		create: function(options) {
            var settings = $.extend({}, $.axis.def_options, options);
		 
			$(this).html(
				'<div class="axis_main"></div>' +
				'<div class="axis_footer">' +
				'    <a class="axis_backward" href="#" data-amount="-20" onclick="return false"><</a>' +
				'    <a class="axis_forward" href="#" data-amount="20" onclick="return false">></a>' +  
				'    <a class="axis_zoom_in" href="#" data-amount="0.1" onclick="return false">+</a>' + 
				'    <a class="axis_zoom_out" href="#" data-amount="-0.1" onclick="return false">-</a>' +
				'</div>'
			);	
            
            var h = $(this).height();
            var fh = $(this).children(".axis_footer").height();
            
            if (h)
                $(this).children(".axis_main").css("height", h - fh); else
                $(this).children(".axis_main").css("height", 100);
			
			if (settings.offset_end != null && settings.offset_end >= settings.offset) {
				var w = $(this).width();
				var s = w / (settings.offset_end - settings.offset) / settings.unit;
				
				settings.zoom = (s >= 1) ? s-1 : Math.log(s);
			}
			
			$(this).addClass("axis");
			$(this).attr('data-offset', settings.offset);
			$(this).attr('data-zoom', settings.zoom);
			$(this).attr('data-unit', settings.unit);
			$(this).attr('data-snap', settings.snap);
			$(this).attr('data-int-min', settings.int_min);
			$(this).data("axis", settings);
            
            settings._main = $(this).children(".axis_main");
            settings._events = settings._main.children(".axis_events");
			
			$(this).find(".axis_backward, .axis_forward").bind("click", function() {
				var src = $(this).parents(".axis:first");

                $.axis.move(src, parseFloat($(this).attr('data-amount')));
			});
			$(".axis_zoom_in, .axis_zoom_out", this).bind("click", function() {
				var a = parseFloat($(this).attr('data-amount'));
				
				src = $(this).parents(".axis:first");   
                $.axis.zoom(src, a);
			});
            $(this).bind("mouseleave", function() {
                $(this).find(".axis_cursor").hide();
            });
            $(this).bind("mousedown", function(e) {
                var src = $(this);
                var ops = src.data("axis");
                var x = e.pageX - src.offset().left;
        
                $.axis.moving = {
                    axis: $(this),
                    pick: x,
                    pick_ofs: parseFloat($(this).attr("data-offset"))
                }
                $(this).find(".axis_event.axis_selected").each(function() {
                    if (typeof ops.onEventDeselect == "function")
                        ops.onEventDeselect.apply(this, [$.axis.event_data(src, $(this)), ops]);
                    
                    $(this).removeClass("axis_selected");
                });
            });
            $(this).bind("mousewheel", function (e, delta, deltaX, deltaY) {
                var src = $(this);
                var x = e.pageX - src.offset().left; 
                
                var ofs = $.axis.read(src, x);
                
                $.axis.zoom(src, delta * 0.1, false);
                $.axis.set_pos(src, x, ofs, false);
                $.axis.update(src, true);
                
                e.preventDefault();
            });
            
            $(this).find(".axis_main").bind("mousemove", function(e) {
                if ($.axis.moving) return;
                
                var src = $(this).closest(".axis");
                var x = e.pageX - src.offset().left;
            
                var ofs = $.axis.read(src, x, true);
                var cursor = src.find(".axis_cursor");
                
                cursor.attr("data-offset", ofs.toFixed(6));
                $.axis.update_cursor(src);
            });
            if (!$.axis.global_events) {
			    $(document).bind("mouseup", function(e) {
                    $.axis.moving = null;
                    $.axis.dragging = null;
			    });
			    $(document).bind("mousemove", function(e) {
                    if ($.axis.moving) {
                        clearTimeout($.axis.moving.timeout);
                        
                        $.axis.moving.pageX = e.pageX;
                        $.axis.moving.timeout = setTimeout(function() {
                            var src = $.axis.moving.axis;
                            var x = $.axis.moving.pageX - src.offset().left;
                            var dx = $.axis.moving.pick - x;
                    
                            $.axis.move(src, dx, $.axis.moving.pick_ofs);
                        }, 5);
                    } else
                    if ($.axis.dragging) {
                        var src = $.axis.dragging.axis;
                        var ev = $.axis.dragging.event;
                        var x = e.pageX - src.offset().left - parseInt($.axis.dragging.pick_ofs);
                        
                        ev.css("z-index", 10);
                        $.axis.move_event(src, ev, x);
                        
                        $(".axis_cursor", src).hide();
                        e.stopPropagation();
                        return;    
                    }    
			    });
                $.axis.global_events = true;
            }
            
            if (typeof $.axis.on_create == "function")
                $.axis.on_create.apply(this, [settings]);
                
            $.axis.update($(this));  
		},
	};

	$.fn.axis = function(method) {
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || ! method ) {
			return methods.create.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
		}    
	};
})( jQuery );
