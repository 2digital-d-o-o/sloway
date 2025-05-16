<?php
    $media_items = array(
        "mobile" => "Mobile",
        "tablet" => "Tablet",
        "laptop" => "Laptop",    
        "desktop" => "Desktop"
    );
?>

<script>

var classes = "<?=implode(" ", array_keys($style_items))?>";

function update_background() {
    var props = $("#image_properties");
    var size = props.find("[name=background-size]").val();    
    var image = props.find("[name=background-image]").val();          
    var repeat = props.find("[name=background-repeat]").val();    
    var pos = props.find("[name=background-position]").val();    
    
    var css = {
        "background-size" : size,
        "background-repeat" : repeat,
        "background-position" : pos.replace("-", " "),
        "background-image" : image
    }
        
    $("#image_preview").css(css);
}
$.admin.template_editor.set_properties = function(template) {
    var props = $("#image_properties");
    var size = props.find("[name=background-size]").val();    
    var image = props.find("[name=background-image]").val();          
    var repeat = props.find("[name=background-repeat]").val();    
    var pos = props.find("[name=background-position]").val();    
    var url = $("#properties").find("[name=url]").val();  
    var cls = $("#properties").find("[name=classes]").val().replace(/,/g, " ");
    var id = $("#properties").find("[name=id]").val();
    
    if (url) {
        url = url.replace(new RegExp('^<?=url::site()?>'), '');
        template.attr("data-url", url); 
    } else
        template.removeAttr("data-url");
        
    if (image) {
        var style = "background-image: " + image + "; background-size: " + size + "; background-repeat: " + repeat + "; background-position: " + pos.replace("-", " "); 
        template.attr("style", style);
    }
    
    if (id)
        template.attr("data-id", "site_" + id); else
        template.removeAttr("data-id");
        
    template.removeClass(classes);
    template.addClass(cls);

    $.rleditor.output(template.closest(".rl_editor"));
}
$.admin.template_editor.properties_editor = function(template) {
    var props = $("#image_properties");
    var style = $.admin.template_editor.parse_style(template.attr("style"));
    for (var name in style) {
        if (name == "background-image")
            props.find("[name=" + name + "]").val(style[name]); else
            props.find("[name=" + name + "]").ac_value(style[name].replace(" ", "-"));
    }
    
    var id = template.attr("data-id");
    if (!id) id = "";
    id = id.replace("site_", "");
    
    var url = template.attr("data-url");
    $("#properties [name=url]").ac_value(url);
    
    var cls = template.attr("class").split(" ");
    var cls_val = [];
    for (var i in cls) {
        if (cls[i].indexOf("rl_class") == 0)
            cls_val.push(cls[i]);
    }
    $("#properties").find("[name=classes]").ac_value(cls_val);
    $("#properties").find("[name=id]").ac_value(id);
    
    $("#image_preview").click(function() {
        $(this).admin_browse(function(paths) {
            if (!paths.length) return;
            
            $("#image_properties").find("input[name=background-image]").val("url('" + admin_uploads_url + paths[0] + "')");
            update_background();
        });
    });        
    update_background();
    
    $("#image_properties input[name]").change(function() {
         update_background();
    });
}
</script>   

<div style="float: left; width: 48%">
    <div class="admin_heading3"><?=et("Main")?></div>
    <div id="properties">
        <?php
            echo Admin::Field(et("ID"), acontrol::edit("id"));
            echo Admin::Field(et("Link"), acontrol::edit("url"));
            echo Admin::Field(et("Media"), acontrol::checklist("media", $media_items));
            if (count($style_items))
                echo Admin::Field(et("Style"), acontrol::checklist("classes", $style_items));
        ?>
    </div>
    <div class="admin_heading3"><?=et("Background")?></div>
    <div id="image_properties">
        <input type="hidden" name="background-image">
        <?php         
            echo Admin::Field(et("Position"), acontrol::select("background-position", $pos_items, "center-center"));
            echo Admin::Field(et("Size"), acontrol::select("background-size", $size_items, "contain"));
            echo Admin::Field(et("Repeat"), acontrol::select("background-repeat", $repeat_items, "no-repeat"));
        ?>            
    </div>
</div>
        
<div style="float: right; width: 48%">
    <div class="admin_heading3"><?=et("Background preview")?></div>
    
    <div id="image_preview" class="admin_border" style="padding-top: 50%"></div>
</div>

<div style="clear: both"></div>


