<?php namespace Sloway ?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">      
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <base href="<?php echo url::base() ?>">
	<?php 
		foreach ($styles as $css)
			echo "<link rel='stylesheet' type='text/css' href='$css'>";
				
		echo "<script type='text/javascript' src='" . path::gen("site.modules.Core", "media/js/jquery-1.7.2.min.js") . "'></script>";
	?>
<style>
mark {
	background-color: transparent;	
}

@media all {
  .page_break  { display: none; }
}

@media print {
  .page_break  { display: block; page-break-before: always; }
}    
</style>
</head>
<script>
$(document).ready(function() {
	setTimeout(function() {
		if (window.print) {
			window.print();
		}
		else if (agt.indexOf("mac") != -1) {
			alert("Press 'Cmd+p' on your keyboard to print article.");
		}
		else {
			alert("Press 'Ctrl+p' on your keyboard to print article.")
		}
	}, 200);
});
</script>

<body style="padding: 0; margin: 0">

<?php echo $content ?>

</body>
</html>



