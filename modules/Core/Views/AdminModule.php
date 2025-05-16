<?php
	use \Sloway\lang;

    $module_icon = v($module_path, "0.icon");
    $langs = array();
	foreach (lang::languages(true) as $lang) {
		$langs[$lang] = t("lang_" . $lang);
	}
    if (count($langs) <= 1) $langs = null;
    
	$header_class = "";
    if ($langs) $header_class.= " with_lang";
	
	$lang_class = "";
	if (!$lang_select) $lang_class = "disabled";
?>
<div id="module" class="<?=$module_class?>"> 
	<div id="module_header" class="<?=$header_class?>">
		<?php if ($langs): ?>
		<div id="module_language" class="<?=$lang_class?>">
			Frontend
			<?php echo \Sloway\acontrol::select("edit_lang", $langs, \Sloway\Admin::$admin_edit_lang) ?>
		</div>
		<?php endif ?>

		<div id="module_path">
			<?php foreach ($module_path as $part): ?>
			<a href="<?=$part["link"]?>" onclick="return admin_redirect(this)"><?=$part["title"]?></a>
			<?php endforeach ?>
		</div>

		<a id="module_settings"></a>
	</div>

	<?php if ($module_menu): ?>
	<div id="module_menu">
		<div><?=$module_menu?></div>
	</div>
	<?php endif ?>

	<div id="module_content" <?php if ($module_menu) echo "class='with_menu'"?>>
		<?php echo $module_content ?>
	</div>
</div>

<script>
var admin_module_title = '';
var admin_edit_lang = 'si';  

$(document).module_loaded(function() {
	$.admin.layout();

	$("#module_settings").click(function() {
		if ($("#module_menu").is(":visible")) {
			$("#module_menu").slideUp(function() { 
				$(this).removeAttr("style");
				$("#module_header").removeClass("dropdown");
			}); 
		} else {
			$("#module_menu").slideDown(); 
			$("#module_header").addClass("dropdown");
		}
	});


	// $("#module").ac_create();
/*	$("#module_language [name=edit_lang]").change(function() {
		$.admin.toggle_edit_lang($(this).val());
	});
		 * 
 */
	
	$("#module_language [name=edit_lang]").change(function() {
		$.admin.reload_module(null, false, { admin_edit_lang: $(this).val() }, function(res) {
			//$("#module_language [name=edit_lang]").ac_value(admin_edit_lang);
		});
	});
	
});
</script>
