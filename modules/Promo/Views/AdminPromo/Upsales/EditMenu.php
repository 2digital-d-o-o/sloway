<?php 
	namespace Sloway;

	echo "<div class='admin_section admin_form_menu'>";
    echo "<h2 class='admin_section_header'>" . et("Options") . "</h2>";

	echo "<button class='admin_button_save admin_button vertical' onclick='return false'>" . et("Save") . "</button>";
	$ajax = isset($_POST["module_ajax"]) ? 1 : 0;        
	if ($back_url) {
		echo "<button class='admin_button_close admin_button vertical' onclick='return false' data-url='$back_url' data-ajax='$ajax'>" . et('Save and close') . "</button>";
		echo "<button class='admin_button_cancel admin_button vertical' onclick='return false' data-url='$back_url' data-ajax='$ajax'>" . et("Close") . "</button>";    
	}

	echo "</div>";
?>