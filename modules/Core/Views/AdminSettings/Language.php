<?php 
	namespace Sloway;
	
	$cn = core::$project_name . "_admin_lang";
?>
<script>  
$(document).ready(function() {
	$("#module_content [data-name=language]").change(function() {
		$.post(doc_base + "Core/Ajax_Language", { profile: "admin", lang: $(this).val() }, function(r) {
			if (r == "OK")
				window.location.reload();
		});
	});
});
</script>
<?php
	$langs = array();
	foreach (lang::languages(false) as $lang) {
		$langs[$lang] = t("lang_" . $lang);
	}

    echo Admin::SectionBegin();
	echo Admin::Field(et("Language"), acontrol::select("language", $langs, lang::$lang));
    echo Admin::SectionEnd();
?>
