<?php
    $media_items = array(
        "mobile" => "Mobile",
        "tablet" => "Tablet",
        "laptop" => "Laptop",    
        "desktop" => "Desktop"
    );
    
    use \Sloway\Admin;
    use \Sloway\acontrol;
    use \Sloway\catalog;
    use \Sloway\config;
?>
<script>
var template_name = "<?= $_POST["name"] ?>";
var padding = template_name=="document_list"? "24px" : "48px";
$.admin.template_editor.background_set_properties = function(template) {
    
    var properties = $("#admin_template_properties"); 
    let classes=properties.find("[name=classes]");
    let inputString = classes.val();
    let newValue = properties.find("[name=rl_class_background]").val();
    let array = inputString.split(',');
    array = array.map(value => value.startsWith("rl_class_background") ? "rl_class_background_"+newValue : value);
    if (!array.includes("rl_class_background_"+newValue)) {
        array.push("rl_class_background_"+newValue);
    }
    classes.val(array.join(','));
    
}
$.admin.template_editor.background_properties_editor = function(template) {

    var properties = $("#admin_template_properties");
    var background = properties.find("[name=rl_class_background]").val();
    var input = properties.find('[name=inner_css]'); 
    var style = $.rleditor.parse_style(input.val(), true);
    let outer_input = properties.find('[name=outer_css]')
    let outer = $.rleditor.parse_style(outer_input.val(), true);
            
    if(background=="site" || background=="fit"){
        properties.find('[name=bgr_color]').val(style["background-color"]);
    }
    else if(background=="full"){
        properties.find('[name=bgr_color]').val(style["background-color"]);
    }  

    function changeBackground(){
        var properties = $("#admin_template_properties");
        var short_width = properties.find('li[data-value="rl_class_short_width"]').hasClass("ac_checked");
        var background = properties.find("[name=rl_class_background]").val();

        var input = properties.find('[name=inner_css]'); 
        var style = $.rleditor.parse_style(input.val(), true);
        let outer_input = properties.find('[name=outer_css]')
        let outer = $.rleditor.parse_style(outer_input.val(), true);
                
        if(background=="site" || background=="fit"){
            style["background-color"] = properties.find('[name=bgr_color]').val();
            delete outer["background-color"];
        }
        else if(background=="full"){
            outer["background-color"] = properties.find('[name=bgr_color]').val();
            delete style["background-color"];
        } 

        if(background=="none"){
            properties.find("[name=inner_padding_top]").val("");
            properties.find("[name=inner_padding_bottom]").val("");
            properties.find("[name=inner_padding_right]").val("");
            properties.find("[name=inner_padding_left]").val("");
            delete style["padding-top"];
            delete style["padding-bottom"];
            delete style["padding-right"];
            delete style["padding-left"];
            delete style["background-color"];
            delete outer["background-color"];
        }
        else if((short_width && background=="site") || background=="full"){//top bot
            properties.find("[name=inner_padding_top]").val(padding);
            properties.find("[name=inner_padding_bottom]").val(padding);
            properties.find("[name=inner_padding_right]").val("");
            properties.find("[name=inner_padding_left]").val("");
            style["padding-top"] = padding;
            style["padding-bottom"] = padding;
            delete style["padding-right"];
            delete style["padding-left"];
        }
        else if((!short_width && background=="site") || background=="fit"){//all
            properties.find("[name=inner_padding_top]").val(padding);
            properties.find("[name=inner_padding_bottom]").val(padding);
            properties.find("[name=inner_padding_right]").val(padding);
            properties.find("[name=inner_padding_left]").val(padding);
            style["padding-top"] = padding;
            style["padding-bottom"] = padding;
            style["padding-right"] = padding;
            style["padding-left"] = padding;
        }
        
        input.val($.rleditor.format_style(style, false, true));
        outer_input.val($.rleditor.format_style(outer, false, true));
    }
    var properties = $("#admin_template_properties");
    var short_width_state = properties.find('li[data-value="rl_class_short_width"]').hasClass("ac_checked");
    properties.find('[name=classes]').on("change",function(){
        if(short_width_state != $(this).siblings("ul").find('li[data-value="rl_class_short_width"]').hasClass("ac_checked")){
            short_width_state = !short_width_state;
            if(properties.find("[name=rl_class_background]").val() !="none")
                changeBackground();  
        }
    });
    properties.find("[name=rl_class_background]").on("change", changeBackground);
    properties.find("[name=bgr_color]").on("change", changeBackground);

    function changeBackgroundColor(){
        var properties = $("#admin_template_properties");
        var background = properties.find("[name=rl_class_background]").val();

        var input = properties.find('[name=inner_css]'); 
        var style = $.rleditor.parse_style(input.val(), true);
        let outer_input = properties.find('[name=outer_css]')
        let outer = $.rleditor.parse_style(outer_input.val(), true);
                
        if(background=="site" || background=="fit"){
            properties.find('[name=bgr_color]').val(style["background-color"]);
        }
        else if(background=="full"){
            properties.find('[name=bgr_color]').val(outer["background-color"]);
        }      
        input.val($.rleditor.format_style(style, false, true));
        outer_input.val($.rleditor.format_style(outer, false, true));
    }
    properties.find("[name=inner_css]").on("change", changeBackgroundColor);
    properties.find("[name=outer_css]").on("change", changeBackgroundColor);

    properties.find(".background_color").on("click",function(){
        var properties = $("#admin_template_properties");
        var short_width = properties.find('li[data-value="rl_class_short_width"]').hasClass("ac_checked");
        var background = properties.find("[name=rl_class_background]").val();

        var input = properties.find('[name=inner_css]'); 
        var style = $.rleditor.parse_style(input.val(), true);
        let outer_input = properties.find('[name=outer_css]')
        let outer = $.rleditor.parse_style(outer_input.val(), true);

        if(background=="site" || background=="fit"){
            properties.find('[name=bgr_color]').val($(this).html());
            style["background-color"] = $(this).html();
            if(template_name=="document_list")
                style["--color"] = $(this).attr("data-color");
        }
        else if(background=="full"){
            properties.find('[name=bgr_color]').val($(this).html());
            outer["background-color"] = $(this).html();
            if(template_name=="document_list")
                outer["--color"] = $(this).attr("data-color");
        }

        input.val($.rleditor.format_style(style, false, true));
        outer_input.val($.rleditor.format_style(outer, false, true));
    });
}
</script>
<?php
$background = "rl_class_background_none";
foreach(explode(",",$classes) as $class)
    if (strpos($class, 'rl_class_background_') === 0){
        $background = $class;
        break;
    }
$colors = array("#F0F6FA"=>"#0B141B","#FDF9EB"=>"#302500","#EDF5EB"=>"#0C2E07","#ECF7F8"=>"#003438");
echo "<div class='admin_heading3'>" . et("Background") . "</div>";
echo Admin::Field("", acontrol::select("rl_class_background",  array("none"=>"None", "fit"=>"Fit content", "site"=>"Site width", "full"=>"Full width"),substr($background, strlen("rl_class_background_"))));
echo "<div id='background_colors' style='display:flex;align-items:center;'>";
echo "<div style='width:120px;display:inline-block;'>". Admin::Field("",acontrol::color("bgr_color", "#FFFFFF")) . "</div><div style='display:inline-flex'>";
foreach($colors as $bg=>$color){
    echo '<div class="background_color" data-color="'.$color.'" style="background-color:'.$bg.';padding:5px;border:1px solid gainsboro;border-radius:5px;margin-right:5px;cursor:pointer;">'.$bg.'</div>';
}
echo "</div></div>";

?>