<script>
<?php   
	if ($this->temp) {
		echo "var template = 1;\n"; 
		echo "var mode = '" . $this->temp['mode'] . "';\n";
			
		echo "var tw = {$this->temp['width']}\n";
		echo "var th = {$this->temp['height']}\n";
	} else {
		echo "var template = 0;\n";
		echo "var mode = 'fit';\n";
	}
	echo "var img_id = '{$this->img_id}'\n";
	echo "var changed = false;\n";
	
	if ($this->area) {
		echo "var rx1 = {$this->area[0]};\n";
		echo "var ry1 = {$this->area[1]};\n";
		echo "var rx2 = {$this->area[2]};\n";
		echo "var ry2 = {$this->area[3]};\n";
		echo "var area_set = 1;\n";
	} else
		echo "var area_set = 0;\n";
	
?>
var as = null;

function display_area() {
	sw = $("#img").width();
	sh = $("#img").height();
	
	if (template && (sw < tw || sh < th)) {
		r = fit_rect(tw,th, sw,sh);
		tw = r.w;
		th = r.h;
	} 
	
	if (area_set) {
		sx1 = rx1 * sw;
		sy1 = ry1 * sh;
		sx2 = rx2 * sw;
		sy2 = ry2 * sh;

		if (mode == 'fill') {
			sasp = (sx2-sx1) / (sy2-sy1);
			tasp = tw / th;    
			if (Math.abs(sasp - tasp) > 0.05) {
				console.log('invalid: ', sasp, ' ', tasp);
				area_set = false;
			}
		}
		
		sx1 = parseInt(sx1);
		sy1 = parseInt(sy1);
		sx2 = parseInt(sx2);
		sy2 = parseInt(sy2);
	}
	
	if (!area_set) {
		console.log('area not set');
		sx1 = 0;
		sy1 = 0;
		
		if (mode == 'fill') {
			r = fit_rect(tw,th, sw,sh, true);
			sx2 = r.w / sw;
			sy2 = r.h / sh;
			
			sx1 = parseInt(sx1 * sw);
			sy1 = parseInt(sy1 * sh);
			sx2 = parseInt(sx2 * sw);
			sy2 = parseInt(sy2 * sh);
		} else {
			sx2 = sw;
			sy2 = sh;	
		}
	} 
	
	area_ops = {
		handles: true,
		"x1": sx1,
		"y1": sy1,
		"x2": sx2,
		"y2": sy2,
		persistent: true,
		instance: true,
		minWidth: 50,
		onSelectEnd: function(img, coord) {
			changed = true;
			rx1 = coord.x1 / $(img).width();
			ry1 = coord.y1 / $(img).height();
			rx2 = coord.x2 / $(img).width();
			ry2 = coord.y2 / $(img).height();
		}
	};
	if (mode == 'fill')
		area_ops['aspectRatio'] = (sx2-sx1) + ":" + (sy2-sy1);
	
	as = $("#img").imgAreaSelect(area_ops);
}	

$("#overlay").bind("overlay_close", function(e, result) {
	oc = $(".overlay_content", this);
	$("#img").imgAreaSelect({remove: true});
	name = $("#img").attr('data-name');
	
	if (changed && result) {	
		$.overlay_loader(); 
		
		i = $("#" + img_id);
		inp = $("#" + img_id + " [name$=position]");
		inp.val(rx1 + "," + ry1 + "," + rx2 + "," + ry2);
		
		var cont = $(".admin_imagelist_cont", i);
		o = {
			'template' : cont.attr('template'),
			'position' : $("[name$=position]", i).val(),
			'name' : name,
			'src' : cont.attr('imgsrc'),
		}    
		
		$.post(doc_base + 'Admin/Ajax_ImageThumb', o, function(r) {
			cont.html(r['html']);	
			$.overlay_close_all();	
		}, "json");
	}
});

$("#img").imagesLoaded(function() { 
	$.overlay_show();
});

$("#overlay").bind("overlay_ready", function() {
	img = $("#img");
	
	img.attr("w", img.width());
	img.attr("h", img.height());    
});
$("#overlay").bind("overlay_resize", function() {
	oc = $(".overlay_content", this);
	img = $("#img");
	
	r = fit_rect(img.attr('w'), img.attr('h'), oc.width(), oc.height());
	oh = oc.height();
	
	if (oh > r.h) {
		m = (oh - r.h) / 2; 
		img.css("margin-top", m + "px");
	}
	
	img.css({"width" : r.w + "px", "height" : r.h + "px"});
	display_area();
});
</script>


<img id="img" src="<?php echo $this->img_url ?>" style="margin: auto; display: block" data-name="<?=$this->name?>"/>
