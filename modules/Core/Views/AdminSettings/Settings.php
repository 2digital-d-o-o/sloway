<?php
	use Sloway\admin;
	use Sloway\acontrol;
?>

<script>
$(document).module_loaded(function() {
    <?php if (count($google_maps)): ?>
    $.google_maps_load(function() {
        <?php foreach ($google_maps as $name): ?>
        $("#main_content [name=<?=$name?>]").google_map_autocomplete(function(lat, lng) {
            $("#main_content [name=<?=$name?>_coord]").val(lat + "," + lng);    
        });
        <?php endforeach ?>
    });    
    <?php endif ?>
    
    $("#module_content .admin_settings_date input").each(function() {
        $(this).datetimepicker({dateFormat: 'dd.mm.yy'}); 
    }); 
});
</script>
<?php
	echo Admin::AjaxForm_Begin('AdminSettings/Ajax_SettingsHandler');
	echo "TEST";
	echo Admin::SectionBegin();
	foreach ($settings as $name => $s) {
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
                echo Admin::Field($s->title, acontrol::edit($name, $s->content, array("class" => "admin_settings_date")));
                break;
            case "text":
				echo Admin::Field($s->title, Admin::Edit($name, $s->get_ml("content"), true, array("lines" => 10)));
                break;
            case "html":
                //echo Admin::Field($s->title, Admin::HtmlEditor($name, $s->get_ml("content"), true, array("size" => "small", "menu" => "size,style")));
                break;
            default:
				echo Admin::Field($s->title, Admin::Edit($name, $s->get_ml("content"), "show_def"));
                break;
        }
	}
	echo Admin::SectionEnd();
    
    if (count($images)) {
        echo Admin::SectionBegin();   
        
        foreach ($images as $name => $s)  
            echo Admin::ImageList($name, $s->image, array('title' => $s->title, 'count' => 1));
            
        echo Admin::SectionEnd();
    }
 	
	echo Admin::AjaxForm_End();

?>
