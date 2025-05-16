<?php 
	use Sloway\admin;
	use Sloway\acontrol;

    echo Admin::AjaxForm_Begin('AdminPages/Ajax_PageHandler/' . $page->id);
    echo Admin::SectionBegin(et('Main'));
    echo Admin::Field(et('Title'), Admin::edit('title', $page->get_ml("title"), true));
    //echo Admin::Field(et("Flags"), acontrol::checktree('flags', $flags, $page->flags));
    echo Admin::SectionEnd();
    
    echo Admin::SectionBegin(et("Content"));    
    echo Admin::TemplateEditor("content", $page->get_ml("content"), true);
    echo Admin::SectionEnd(); 
    
    echo Admin::SectionBegin();    
    echo Admin::ImageList('images', $images, array('title' => et("Images")));
    echo Admin::SectionEnd(); 
    
    echo Admin::SectionBegin();    
    echo Admin::FileList('files', $files, array('title' => et("Files")));
    echo Admin::SectionEnd(); 

    echo Admin::SectionBegin(et("SEO"));
	echo Admin::SEO($page, true);
    echo Admin::SectionEnd();
    
    echo Admin::AjaxForm_End();
 
?>
