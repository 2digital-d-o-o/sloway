(function( $ ){   
	$.image_frame = {
		options: {
			valign: "center",
			halign: "center",
			loader: false,
			mode: "fit",
			path: false,

			magnifier: false,
			mag_width: 100,
			mag_height: 100,
			mag_zoom: 2,
			mag_path: false,            
		},
		frame_count: 0,
		build_css: function(obj) {
			var val,res = "";
			for (var key in obj) {	
				val = obj[key];
				
				res+= key + ": " + val + "; ";
			}
			return res;
		},
		build: function(frame) {
			var ops = frame.data("image-frame");
			var img = new Image();
			
			img.src = ops.path;    
			img.frame = frame;
			img.onload = function() {
				var frame = this.frame;
				var ops = frame.data("image-frame");
				var css = {
					"background-repeat" : "no-repeat",
					"background-position" : ops.halign + " " + ops.valign,
					"background-size" : (ops.mode == "fit") ? "contain" : "cover",
					"background-image" : "url('" + ops.path + "')"
				}
				
				if (ops.magnifier) {
					var mag = frame.find(".magnifier");
					var has_mag = mag.length != 0;
					
					mag.remove();                    
					var style = $.image_frame.build_css({
						"position" : "fixed", 
						"width" : ops.mag_width + "px",
						"height" : ops.mag_height + "px",
						"display" : "none",
						"overflow" : "hidden"
					});
					
					var html = "<div class='magnifier' data-frame-id='" + ops.frame_id + "' style='" + style + "'></div>";	
					
					frame.append(html);
					
					if (!has_mag)
					frame.mousemove(function(e) {
						var mag = $(this).find(".magnifier");
						var ops = $(this).data("image-frame");
						
						var ofs = $(this).offset();
						var w = $(this).innerWidth();
						var h = $(this).innerHeight();
						
						if (e.pageX < ofs.left || e.pageX > ofs.left + w || e.pageY < ofs.top || e.pageY > ofs.top + h) {
							mag.hide();
							return false;
						}   
						
						var sx = e.clientX - ops.mag_width / 2;
						var sy = e.clientY - ops.mag_height / 2;
						
						mag.css({left: sx + "px", top: sy + "px"}).show();
						
						var layer = $(">div", mag);
						if (!layer.length) {
							var bw = parseInt($(this).width() * ops.mag_zoom);
							var bh = parseInt($(this).height() * ops.mag_zoom);

							var style = $.image_frame.build_css({     
								"position" : "absolute", 
								"background-image" : "url(" + ops.mag_path + ")",
								"width" : bw + "px",
								"height" : bh + "px", 
								"background-repeat" : "no-repeat",
								"background-size" : (ops.mode == "fit") ? "contain" : "cover",  
								"background-position" : ops.halign + " " + ops.valign
							});
		
							layer = $("<div style='" + style + "'></div>").appendTo(mag);
						}
						var rx = (e.pageX - ofs.left) / w;
						var ry = (e.pageY - ofs.top) / h;
						
						var lx = rx * layer.width();
						var ly = ry * layer.height();
						
						layer.css({left: ops.mag_width / 2 - lx + "px", top: ops.mag_height / 2 - ly});
					}).mouseleave(function() {     
						var mag = $(this).find(".magnifier");
						$(">div", mag).remove();
						mag.hide(); 
					});
				}
				
				frame.css(css);
			}
		}
	};
	$.fn.image_frame = function(options, handler) {
		var i,w,h,ops,attr,fid;
		var post = {};
		
		for (i = 0; i < this.length; i++) {
			frame = $(this[i]);
			w = frame.width();
			h = frame.height();
			
			attr = {
				valign: frame.attr("data-valign"),
				halign: frame.attr("data-halign"),
				mode: frame.attr("data-mode"),
				path: frame.attr("data-path"),
				magnifier: frame.attr("data-magnifier")
			};
			
			fid = $.image_frame.frame_count++;
			ops = $.extend({}, $.image_frame.options, options, attr);
			ops.frame_id = fid;
			ops.magnifier = (ops.magnifier === "true" || ops.magnifier === "1" || ops.magnifier === true || ops.magnifier === 1);
			
			frame.data("image-frame", ops);
			frame.attr("data-frame-id", fid);
			
			if (ops.loader) 
				frame.css({
					"background-image" : "url('" + ops.loader + "')",
					"background-position" : "center center",
					"background-repeat" : "no-repeat", 
					"background-size" : "auto",
				});

			
			if (handler) {
				post[fid] = {
					width: w, 
					height: h,
					path: ops.path,
				}
			} else 
				$.image_frame.build(frame, ops.path);
		}
		
		if (handler) {
			$.post(handler, post, function(r) {
				var frame,ops,fid;
				for (fid in r) {
					frame = $("[data-frame-id=" + fid + "]");
					if (!frame.length) break;
					
					var ops = frame.data("image-frame");
					$.extend(ops, r[fid]);
					$.image_frame.build(frame);
				}	
			}, "json").fail(function() {
				return false;
			});    		    	
		} 
	}
})( jQuery );        


