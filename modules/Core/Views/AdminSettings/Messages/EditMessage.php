<?php 
	use Sloway\arrays;
	use Sloway\admin;
	use Sloway\acontrol;

	echo Admin::AjaxForm_Begin('AdminSettings/Ajax_SaveMessage/' . $msg_module . "/" . $var);
	
	echo Admin::SectionBegin(et("messages_{$msg_module}_{$var}"));
	echo Admin::Field(et('Title'), Admin::edit('title', $message->get_ml("title"), true));
    echo Admin::Field(et("Content"), Admin::HtmlEditor('content', $message->get_ml("content"), true, array("sections" => $sections, "menubar" => "false")));
    echo Admin::SectionEnd();
    
	/*
    echo Admin::SectionBegin(et("Variables"));
    echo '<table class="admin_list" style="width: 100%">';
    foreach ($variables as $variable) {
        echo '<tr><td>%' . strtoupper($variable) . '%</td>';
        echo '<td>' . et("variable_$variable") . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo Admin::SectionEnd();
	 * 
	 */
    
	echo Admin::AjaxForm_End();
?>
