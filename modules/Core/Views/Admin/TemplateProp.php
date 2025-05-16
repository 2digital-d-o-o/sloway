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

$.admin.template_editor.set_properties = function(template) {
    var attrs = {};
    var elems = {};
    $("#admin_template_attributes").find("input[name], textarea[name]").each(function() {
        attrs[$(this).attr("name")] = $(this).val(); 
    });
    $("#admin_template_elements").find("input[name], textarea[name]").each(function() {
        var val = $(this).val(); 
        if ($(this).parents(".acontrol:first").hasClass("url_edit"))
            val = $.rleditor.parse_url(val);
            
        elems[$(this).attr("name")] = val;
    });


    $.admin.template_editor.background_set_properties(template);
    
    var props = {
        id: $("#admin_template_properties").find("[name=id]").val(),
        url: $("#admin_template_properties").find("[name=url]").val(),  
        media: $("#admin_template_properties").find("[name=media]").val(),
        classes: $("#admin_template_properties").find("[name=classes]").val(),
        collapse: $("#admin_template_properties").find("[name=collapse]").val(),
        inner_css: $.rleditor.parse_style($("#admin_template_properties").find("[name=inner_css]").val(), true),
        outer_css: $.rleditor.parse_style($("#admin_template_properties").find("[name=outer_css]").val(), true),
        attributes: attrs,
        elements: elems
    }
    
    $.rleditor.set_properties(template, props);
    $.rleditor.output(template.closest(".rl_editor"));
}         
$.admin.template_editor.properties_editor = function(template) {
    $.admin.template_editor.innercss_properties_editor();
    $.admin.template_editor.outercss_properties_editor();
    $.admin.template_editor.background_properties_editor();


    $("#admin_template_image_add").click(function() {
        $(this).admin_browse(function(paths) {
            if (!paths.length) return;
            
            var input = $("#admin_template_properties").find("[name=inner_css]"); 
            var style = $.rleditor.parse_style(input.val(), true);
            style["background-image"] = "url(" + admin_uploads_url + paths[0] + ")";
            if (!style["background-repeat"]) style["background-repeat"] = "no-repeat";
            if (!style["background-size"]) style["background-size"] = "contain";
            if (!style["background-position"]) style["background-position"] = "center left";
            
            input.val($.rleditor.format_style(style, false, true));
        });
    });        
    $("#admin_template_image_rem").click(function() {
        var input = $("#admin_template_properties").find("[name=inner_css]"); 
        var style = $.rleditor.parse_style(input.val(), true);
        
        delete style["background-image"];
        delete style["background-repeat"];
        delete style["background-size"];
        delete style["background-position"];
        
        input.val($.rleditor.format_style(style, false, true));
    });        
    
    $(this).find(".admin_html_editor").admin_editor();
}
</script>  


<div id="admin_template_properties">
<?php      
    $media = v($data, "media");
    if (is_array($media)) $media = implode(",", $media);

    $classes = v($data, "classes");
    if (is_array($classes)) $classes = implode(",", $classes);

    echo "<div class='admin_heading3'>" . et("Main") . "</div>";
    echo Admin::Field(et("ID"), acontrol::edit("id", v($data, "id")));
    echo Admin::Field(et("URL"), acontrol::edit("url", v($data, "url")));   
    echo Admin::Field(et("Media"), acontrol::checklist("media", $media_items, $media));
    echo Admin::Field(et("Collapse factor"), acontrol::edit("collapse", v($data, "collapse", "1.0")));
    
    if (count($class_items)) 
        echo Admin::Field(et("Classes"), acontrol::checklist("classes", $class_items, $classes));
        
    echo "<div id='admin_template_attributes'>";
    foreach ($attributes as $name => $prop) {
        if ($prop->type == "edit")
            echo Admin::Field($prop->title, acontrol::edit($name, $prop->value, array("lines" => $prop->lines))); else
        if ($prop->type == "select")
            echo Admin::Field($prop->title, acontrol::select($name, $prop->items, $prop->value));
        
    }
    echo "</div>";

    
    echo "<div id='admin_template_elements'>";
    foreach (v($data,"elements", array()) as $name => $ops) {
        if ($ops["editor"] == "url") 
            echo Admin::Field(et("template_elem_" . $name), acontrol::edit($name, $ops["content"], array("class" => "url_edit"))); else
        if ($ops["editor"] == "text")
            echo Admin::Field(et("template_elem_" . $name), acontrol::edit($name, $ops["content"])); else
        if ($ops["editor"] == "html")
            echo Admin::Field(et("template_elem_" . $name), Admin::HtmlEditor($name, $ops["content"], array("size" => "small"))); else
        if ($ops["editor"] == "area")
            echo Admin::Field(et("template_elem_" . $name), acontrol::edit($name, $ops["content"], array("lines" => 5))); 
    }
    echo "</div>";

    echo view("\Sloway\Views\Admin\TemplateProp\TemplatePropBackground", array("classes"=>$classes));


    $title = et("CSS") . "<br>";
    $title.= "<a id='admin_template_image_add' class='admin_link add'>Add image</a><br>";
    $title.= "<a id='admin_template_image_rem' class='admin_link del'>Remove image</a>";
    
    echo "<div class='admin_heading3'>" . et("Inner style") . "</div>";
    echo Admin::Field($title, acontrol::edit("inner_css", v($data, "inner_css"), array("lines" => 5)));
    echo view("\Sloway\Views\Admin\TemplateProp\TemplatePropInnerCss", array());


    echo "<div class='admin_heading3'>" . et("Outer style") . "</div>";
    echo Admin::Field(et("CSS"), acontrol::edit("outer_css", v($data, "outer_css"), array("lines" => 5)));
    echo view("\Sloway\Views\Admin\TemplateProp\TemplatePropOuterCss", array());
?>
</div>


