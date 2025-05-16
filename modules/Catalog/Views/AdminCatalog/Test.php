<?php 
	use Sloway\Admin;
?>

<script>  
$(document).ready(function() {
	var from;
	var to = [];
	
	
	var sel = $(".admin_lang_selector");
	var sx = sel.position().left;
	$(".admin_lang_selector .admin_lang_button").each(function() {
		var w = $(this).width();
		var x = $(this).position().left - sx + w/2;
		
		if ($(this).hasClass("selected"))
			from = x; else
			to.push(x);
	});
	
	console.log(from, to);
	const canvas = document.getElementById("canvas");
    const ctx = canvas.getContext("2d");	
	//for (var i in to) {
		ctx.translate(.5,.5);
		ctx.beginPath();
		ctx.lineWidth = 1;
		ctx.moveTo(10, 10);
		ctx.lineTo(10, 0);
		ctx.stroke();
	//}
});
</script>
<?php
	echo Admin::Field(et('Title'), Admin::Edit('title', null, true));
?>

<canvas id="canvas" height="40" width="300" ></canvas>