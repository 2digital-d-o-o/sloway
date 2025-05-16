<?php 
	namespace Sloway;

    if (!isset($id)) $id =  utils::generate_id();
    if (!$item_width) $item_width = 300;
    if (!$item_spacing) $item_spacing = 20;
    if (!$ratio) $ratio = 100;
?>
<script>
$(document).ready(function() {
    $("#<?=$id?>").distribute({
        width: "<?=$item_width?>",
        spacing: "<?=$item_spacing?>",
        center: true,
    });
    $("#<?=$id?>").find(".rl_template_banner").hover(function() {
        $(this).find(".rl_banner_desc").stop().slideDown(); 
    }, function() {
        $(this).find(".rl_banner_desc").stop().slideUp(); 
    });
});
</script>
<ul id="<?=$id?>">
    <?php foreach ($items as $i => $banner): ?>
    <li>
        <div class="rl_template_banner">
            <div class="rl_template_span">
            <?php 
                echo new View("Templates/Site/Banner", array(
                    "id" => $id . "-" . $i,
                    "ajax" => $ajax,
                    "script" => false,
                    "ratio" => $ratio,
                    "image" => $banner->image,
                    "title" => $banner->title,
                    "desc" => $banner->desc,
                    "link" => $banner->link,
                    "alt" => $banner->alt,
                ));
            ?>
            </div>
        </div>
    </li>
    <?php endforeach ?>
</ul>

