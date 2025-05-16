<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<?php
	foreach (utils::config("admin.desktop", array()) as $row) {
		echo "<div class='admin_desktop_row'>";
		if (!is_array($row))
			$row = array($row);
        
        $c = count($row);    
		$w = (100 - ($c-1) * 2) / $c;
		foreach ($row as $i => $cell) {
			echo "<div class='admin_desktop_cont' style='width: $w%; margin-right: 2%'>";
			
			if (is_callable($cell)) {
				$c = call_user_func($cell, $this);
					
				echo "<div class='admin_desktop_header'>" . $c['title'] . "</div>";
				echo "<div class='admin_desktop_content'>" . $c['content'] . "</div>";
			}
			
			echo "</div>";
		}
		
		echo "</div>";
	}
?>
