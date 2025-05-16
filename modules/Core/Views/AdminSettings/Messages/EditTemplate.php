<?php 
	namespace Sloway;

    $attr = "data-sections='" . arrays::encode($sections, "=", "&") . "'";

	echo Admin::AjaxForm_Begin('AdminSettings/Ajax_SaveTemplate/' . $msg_module);
	echo Admin::SectionBegin("Template for '" . et("messages_{$msg_module}") . "'");
    echo Admin::Field(et("Header"), Admin::HtmlEditor('header', $msg_header->get_ml("content"), true, array("attr" => $attr)));
    echo Admin::Field(et("Footer"), Admin::HtmlEditor('footer', $msg_footer->get_ml("content"), true, array("attr" => $attr)));
    
    echo Admin::SectionEnd();
    
	echo Admin::AjaxForm_End();
?>
