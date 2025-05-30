<?php 
	use Sloway\Admin;
	use Sloway\acontrol;

    echo Admin::AjaxForm_Begin("AdminCatalog/Ajax_PropertyHandler/" . $property->id);
    
    echo Admin::SectionBegin(et("Main"));
    echo Admin::Field(et("Title"), Admin::Edit('title', $property->get_ml("title"), "show_def"));
    if (!$property->id_parent) {
        echo Admin::Column1();
        echo Admin::Field(et("Filter title"), acontrol::edit("title_flt", $property->title_flt));
        echo Admin::Field(et("Selector title"), acontrol::edit("title_sel", $property->title_sel));
        echo Admin::Column2();
        echo Admin::Field(et("Filter template"), acontrol::select("filter_template", $filter_templates, $property->filter_template));
        echo Admin::Field(et("Selector template"), acontrol::select("selector_template", $selector_templates, $property->selector_template));
        echo Admin::ColumnEnd();
    } else 
        echo Admin::Field(et("Value"), acontrol::edit("value", $property->value)); 
        
    echo Admin::SectionEnd();
    
    echo Admin::SectionBegin();  
    echo Admin::ImageList("images", $property->images, array("title" => et("Images")));
    echo Admin::SectionEnd();
    
    echo Admin::AjaxForm_End();
