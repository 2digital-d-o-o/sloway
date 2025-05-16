<?php
	use Sloway\admin;
	use Sloway\acontrol;
?>


<?php

echo Admin::AjaxForm_Begin('AdminSettings/Ajax_MetaPixelAndConversionApi');
echo Admin::SectionBegin();
foreach ($fields as $name => $s) {
    switch ($s->type) {
        case "caption":
            echo "<h2>" . $s->title . "</h2>";
            break;
        case "separator":
            echo Admin::Separator();
            break;
        case "gmap":
            echo Admin::Field($s->title, acontrol::edit($name, $s->value));
            echo "<input type='hidden' name='{$name}_coord' value='$s->gmap_coord'>";
            break;
        case "date": 
            echo Admin::Field($s->title, acontrol::edit($name, $s->value, array("class" => "admin_settings_date")));
            break;
        case "text":
            echo Admin::Field($s->title, acontrol::edit($name, $s->value, array("lines" => 10)));
            break;
        case "html":
            echo Admin::Field($s->title, Admin::HtmlEditor($name, $s->value, array("size" => "small", "menu" => "size,style")));
            break;
        case "checkbox":
            echo Admin::Field($s->title, acontrol::checkbox($name, $s->value));
            break;
        default:
            echo Admin::Field($s->title, acontrol::edit($name, $s->value));
            break;
    }
}
echo Admin::SectionEnd();
 
echo Admin::AjaxForm_End();



