<?php 
	use Sloway\Admin;
	use Sloway\acontrol;
	
	echo Admin::AjaxForm_Begin('AdminCatalog/Ajax_TagHandler/' . $tag->id);
	
	echo Admin::SectionBegin();
    echo Admin::Field(et("Title"), Admin::edit("title", $tag->get_ml("title"), "show_def"));
    
	echo Admin::ImageList("images", $tag->images, array("title" => et("Images")));
		
	echo Admin::AjaxForm_End();
?>
