<?php 
	namespace Sloway;
?>
<script>
$(document).module_loaded(function() {
    $("[name=date]").datetimepicker({dateFormat: 'dd.mm.yy'});  
});
</script>                                       

<?php
    echo Admin::AjaxForm_Begin("AdminNews/Ajax_NewsHandler/" . $news->id);
    
    echo Admin::SectionBegin(et('Main'));
    echo Admin::Field(et("Title"), Admin::edit('title', $news->get_ml("title"), true));
    echo Admin::Field(et("Date"), acontrol::edit('date', date("d.m.Y H:i", $news->date)));
    echo Admin::SectionEnd();
    
    echo Admin::SectionBegin(et("Content"));    
    echo Admin::TemplateEditor("content", $news->content);   
    echo Admin::SectionEnd(); 
                                                              
    echo Admin::SectionBegin();
    echo Admin::ImageList('images', $images, array('title' => et("Images")));
    echo Admin::SectionEnd();  
    
    echo Admin::SectionBegin(et("SEO"));    
	echo Admin::SEO($news, true);
    echo Admin::SectionEnd(); 
    
    echo Admin::AjaxForm_End();
?>
