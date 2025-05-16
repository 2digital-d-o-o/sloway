<?php 
	namespace Sloway;

    if (!isset($id)) $id =  utils::generate_id();
    if (!isset($ajax)) $ajax = false;
    if (!isset($center)) $center = false;
    if (!isset($image_mode)) $image_mode = "cover";
    if (!isset($item_width)) $item_width = 300;
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
});
</script>
<div id="<?=$id?>" class="rl_news_list">
<ul>
    <?php foreach ($items as $item): ?>
    <li>
        <div class="rl_news_list_item">
            <div class="rl_news_list_item_pad" style="padding-top: <?=$aspect_ratio?>%"></div>
            <div class="adaptive_image" data-path="<?=$item->img_path?>" data-mode="<?=$item->img_mode?>">
                <?php if (!$ajax): ?>
                <img src="<?=$item->img_path?>">
                <?php endif ?>
            </div>
            <?php if ($item->title): ?>
            <div class="rl_news_list_item_title"><?=$item->title?></div>
            <?php endif ?>
            <?php if ($item->desc): ?>
            <div class="rl_news_list_item_desc"><?=$item->desc?></div>
            <?php endif ?>
            <?php if ($item->url): ?>
            <a href="<?=$item->url?>" class="rl_news_list_item_link"></a>
            <?php endif ?>
        </div>
    </li>
    <?php endforeach ?>
</ul>
</div>
