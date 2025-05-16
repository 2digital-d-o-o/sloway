<?php 
	namespace Sloway;

    if (!isset($id)) $id =  utils::generate_id();
    if (!isset($ajax)) $ajax = false;
    if (!isset($center)) $center = false;
    if (!isset($popup)) $popup = false;
    if (!isset($image_mode)) $image_mode = "contain";
    if (!isset($item_width)) $item_width = 200;
    if (!isset($item_spacing)) $item_spacing = 20;
    if (!isset($aspect_ratio)) $aspect_ratio = 100;
?>
<script>
$(document).ready(function() {
    $("#<?=$id?> > ul").distribute({
        width: "<?=$item_width?>",
        spacing: "<?=$item_spacing?>",
        center: <?=($center) ? "true" : "false" ?>
    });
    <?php if ($popup): ?>
    $("#<?=$id?>").find(".rl_image_list_item_link").swipebox();
    <?php endif ?>
});
</script>
<div id="<?=$id?>" class="rl_image_list">
<ul>
    <?php foreach ($images as $image): ?>
    <li>
        <div class="rl_image_list_item">
            <div class="rl_image_list_item_pad" style="padding-top: <?=$aspect_ratio?>%"></div>
			<?php if ($image->url): ?>
            <a href="<?=$image->url?>" class="adaptive_image" data-path="<?=$image->path?>" data-mode="<?=$image->mode?>" data-alt="<?=$image->alt?>">
                <?php if (!$ajax): ?>
                <img src="<?=$image->path?>" alt="<?=$image->alt?>">
                <?php endif ?>
            </a>
			<?php else: ?>
            <div class="adaptive_image" data-path="<?=$image->path?>" data-mode="<?=$image->mode?>" data-alt="<?=$image->alt?>">
                <?php if (!$ajax): ?>
                <img src="<?=$image->path?>" alt="<?=$image->alt?>">
                <?php endif ?>
            </div>
			<?php endif ?>
            <?php if ($image->title): ?>
            <div class="rl_image_list_item_title"><?=$image->title?></div>
            <?php endif ?>
            <?php if ($popup): ?>
            <a class="rl_image_list_item_link" href="<?=$image->path?>" title="<?=$image->title?>" rel="__gallery"></a>
            <?php endif ?>
        </div>
    </li>
    <?php endforeach ?>
</ul>
</div>
