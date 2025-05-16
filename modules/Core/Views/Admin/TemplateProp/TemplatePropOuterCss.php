
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
	use \Sloway\path;
?>
<script>
    $.admin.template_editor.outercss_properties_editor = function(template) {
        function changeValue(event){
            var input = $("#admin_template_properties").find("[name="+event.data.field+"]"); 
            var style = $.rleditor.parse_style(input.val(), true);
            if($(this).val())
                style[event.data.property] = $(this).val();
            else
                delete style[event.data.property];
            input.val($.rleditor.format_style(style, false, true));
        }

        var properties = $("#admin_template_properties");
        var style = $.rleditor.parse_style(properties.find("[name=outer_css]").val(), true);
        if (style["background-color"]) properties.find("[name=outer_bgr_color]").val(style["background-color"]);
        for (type of ["padding", "margin"]) {

            if (style[type + "-top"]) properties.find("[name=outer_" + type + "_top]").val(style[type + "-top"]);
            if (style[type + "-bottom"]) properties.find("[name=outer_" + type + "_bottom]").val(style[type + "-bottom"]);
            if (style[type + "-right"]) properties.find("[name=outer_" + type + "_right]").val(style[type + "-right"]);
            if (style[type + "-left"]) properties.find("[name=outer_" + type + "_left]").val(style[type + "-left"]);

            properties.find("[name=outer_" + type + "_top]").on("input", { field: "outer_css", property: type + "-top" }, changeValue);
            properties.find("[name=outer_" + type + "_bottom]").on("input", { field: "outer_css", property: type + "-bottom" }, changeValue);
            properties.find("[name=outer_" + type + "_right]").on("input", { field: "outer_css", property: type + "-right" }, changeValue);
            properties.find("[name=outer_" + type + "_left]").on("input", { field: "outer_css", property: type + "-left" }, changeValue);
        }
        
        properties.find("[name=outer_bgr_color]").on("input", { field: "outer_css", property: "background-color" }, changeValue);

        properties.find("[name=outer_css]").on("change",function(){
            var style = $.rleditor.parse_style($(this).val(), true);
            for (type of ["padding", "margin"]) {
                if (style[type + "-top"]) properties.find("[name=outer_" + type + "_top]").val(style[type + "-top"]);
                if (style[type + "-bottom"]) properties.find("[name=outer_" + type + "_bottom]").val(style[type + "-bottom"]);
                if (style[type + "-right"]) properties.find("[name=outer_" + type + "_right]").val(style[type + "-right"]);
                if (style[type + "-left"]) properties.find("[name=outer_" + type + "_left]").val(style[type + "-left"]);
            }
            $(this).val($.rleditor.format_style(style, false, true));
            
        });
    }
</script>
<?php
function outer_padding_margin($type)
{
    $content = '<div style="display:flex">';
    $content .= '<div style="display:flex;width:100px;border:1px solid rgb(128 128 128 / 37%);margin-right:10px;"><div style="display:flex;align-items: center;padding:0 9px"><img src="' . path::gen("site.modules.Core", "media/img/icon-arrow-top.png") . '"></div>' . acontrol::edit("outer_" . $type . "_top", "") . "</div>";
    $content .= '<div style="display:flex;width:100px;border:1px solid rgb(128 128 128 / 37%);margin-right:10px;"><div style="display:flex;align-items: center;padding:0 9px"><img src="' . path::gen("site.modules.Core", "media/img/icon-arrow-right.png") . '"></div>' . acontrol::edit("outer_" . $type . "_right", "") . "</div>";
    $content .= '<div style="display:flex;width:100px;border:1px solid rgb(128 128 128 / 37%);margin-right:10px;"><div style="display:flex;align-items: center;padding:0 9px"><img src="' . path::gen("site.modules.Core", "media/img/icon-arrow-bottom.png") . '"></div>' . acontrol::edit("outer_" . $type . "_bottom", "") . "</div>";
    $content .= '<div style="display:flex;width:100px;border:1px solid rgb(128 128 128 / 37%);margin-right:10px;"><div style="display:flex;align-items: center;padding:0 9px"><img src="' . path::gen("site.modules.Core", "media/img/icon-arrow-left.png") . '"></div>' . acontrol::edit("outer_" . $type . "_left", "") . "</div>";
    $content .= "</div>";
    return $content;
}
echo Admin::Field(et("Padding"), outer_padding_margin("padding"));
echo Admin::Field(et("Margin"), outer_padding_margin("margin"));
?>