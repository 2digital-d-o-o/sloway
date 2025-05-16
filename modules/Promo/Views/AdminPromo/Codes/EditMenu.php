<?php 
	namespace Sloway;
?>
<script>
function codes_delete() {
	$.overlay_confirm("<div class='admin_message warning'>Are you sure you want to delete codes?</div>", function() {
		$("#module_form").trigger("submit", ["del_codes"]);
	});
}
function codes_export() {
	window.location.href = doc_base + "AdminPromo/Export/<?=$code->id?>";
}
function codes_generate() {
    $.overlay_ajax(doc_base + "AdminPromo/Ajax_Generate/<?=$code->id?>", "ajax", {}, {
        onDisplay: function(r) {
			$(this).ac_create();
        },
		onClose: function(r) {
			if (r) {
				$("#admin_gen_count").val(r.count);
				$("#admin_gen_mask").val(r.mask);
				$("#module_form").trigger("submit", ["save"]);
			}
		}
    });	
}

</script>
<?php
    echo "<div class='admin_section admin_form_menu'>";
	if ($count) {
		echo "<h2 class='admin_section_header'>" . $count . " codes generated</h2>";
		echo "<button class='admin_button vertical' onclick='codes_export(); return false'>" . et("Export") . "</button>";
		echo "<button class='admin_button vertical' onclick='codes_delete(); return false'>" . et("Delete") . "</button>";
	} else {
		echo "<h2 class='admin_section_header'>Codes</h2>";
	}
	echo "<button class='admin_button vertical' onclick='codes_generate(); return false'>" . et("Generate") . "</button>";

	echo "<br>";


	echo "<h2 class='admin_section_header'>" . et("Options") . "</h2>";
	echo "<button class='admin_button_save admin_button vertical' onclick='return false'>" . et("Save") . "</button>";
	$ajax = isset($_POST["module_ajax"]) ? 1 : 0;        
	if ($back_url) {
		echo "<button class='admin_button_close admin_button vertical' onclick='return false' data-url='$back_url' data-ajax='$ajax'>" . et('Save and close') . "</button>";
		echo "<button class='admin_button_cancel admin_button vertical' onclick='return false' data-url='$back_url' data-ajax='$ajax'>" . et("Close") . "</button>";    
	}

	echo "</div>";
?>