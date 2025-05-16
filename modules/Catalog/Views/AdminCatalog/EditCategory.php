<?php 
	use Sloway\Admin;
	use Sloway\acontrol;

    $readonly = $cat->locked == 1;
    echo Admin::AjaxForm_Begin('AdminCatalog/Ajax_CategoryHandler/' . $cat->id);
    
    echo Admin::SectionBegin(et("Main"));
	echo Admin::Field(et('Title'), Admin::Edit('title', $cat->get_ml("title"), "show_def"));
    //echo Admin::Field(et('Title'), Admin::edit('title', $cat->get_ml("title"), true, array("readonly" => $readonly)));
    
    //if (Admin::auth("catalog.categories.assign_user")) 
    //    echo Admin::Field(et("Users"), acontrol::checklist("users", $users, $cat->users), array("style" => "max-height: 150px"));
    echo Admin::SectionEnd();
    
    echo Admin::SectionBegin(et("Description"));
    echo Admin::TemplateEditor("description", $cat->get_ml("description"), true);
    echo Admin::SectionEnd();
    
    echo Admin::SectionBegin();  
    echo Admin::ImageList('images', $cat->images, array('title' => et('Images')));
    echo Admin::SectionEnd();
    
    echo Admin::SectionBegin(et("SEO"));
	echo Admin::SEO($cat, true);
    echo Admin::SectionEnd();
    
    echo Admin::AjaxForm_End();
?>
