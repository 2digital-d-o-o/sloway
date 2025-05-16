<?php 
	use \Sloway\Admin;
	use \Sloway\acontrol;
	use \Sloway\catalog;
	use \Sloway\config;
	use \Sloway\utils;
?>

<script>
$(document).ready(function() {
	$("#admin_custom_title [data-name=custom_title_chk]").change(function() {
		var val = $(this).val();
		var field = $(this).parents(".admin_field:first").children(".admin_field_content");
		if (val) field.show(); else field.hide();
	});
	
	<?php if (!$custom_title_chk): ?>
	$("#admin_custom_title > .admin_field_content").hide();
	<?php endif ?>
	
});
</script>
<?php
	echo Admin::AjaxForm_Begin('AdminCatalog/Ajax_ItemHandler/' . $item->id);

	echo Admin::SectionBegin();
	echo Admin::Field(et('Default Title'), Admin::Edit("default_title", $default_title, "show_def", array("readonly" => true)));
    echo Admin::Field(et('Custom Title') . acontrol::checkbox("custom_title_chk", $custom_title_chk), Admin::Edit('title', ($custom_title_chk) ? $item->get_ml("title") : null, "show_def"), "id='admin_custom_title'");
	echo Admin::Field(et("Code"), acontrol::edit("code", $item->code));
    
	echo Admin::Field(et("Price"), Admin::Edit('price', utils::decode_price($item->get_ml("price")), true)); 
	echo Admin::Field(et("Price action"), Admin::Edit('price_action', utils::decode_price($item->get_ml("price_action")), true));
	
    echo Admin::SectionEnd();
    
    echo Admin::SectionBegin(et("Properties"));
    echo $property_editor;
    echo Admin::SectionEnd();

    echo Admin::SectionBegin(et("Description"));
	echo Admin::TemplateEditor("description", $item->description);
	echo Admin::SectionEnd();

	echo Admin::SectionBegin(); 
	echo Admin::ImageList("images", $item->images, array("title" => et("Images")));
	echo Admin::SectionEnd();
		
	echo Admin::AjaxForm_End();
?>
