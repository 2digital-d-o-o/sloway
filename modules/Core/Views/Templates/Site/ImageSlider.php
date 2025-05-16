<?php 
	namespace Sloway;

    if (!isset($id)) $id = utils::generate_id(); 
    if (!isset($ajax)) $ajax = false;
    if (!isset($caption)) $caption = false;
    if (!isset($framed)) $framed = false;
    if (!isset($images)) $images = array();
    if (!isset($image_mode)) $image_mode = "contain";
    if (!isset($interval)) $interval = 0;
    if (!isset($speed)) $speed = 1000;
    if (!isset($class)) $class = "";
    if (!isset($item_width) || !$item_width) $item_width = 200;
    if (!isset($item_height) || !$item_height) $item_height = 200;
    
    if ($caption) $class.= " with_caption";
?>
<script>                
$(document).ready(function() {
    $("#<?=$id?> > div").carousel({
        item_width: "<?=$item_width?>",
        height: "<?=($framed) ? "auto" : "items"?>",
        interval: "<?=$interval?>",
        interval_speed: "<?=$speed?>",
        infinite: true,
        next: "#<?=$id?> .slider_next",
        prev: "#<?=$id?> .slider_prev",
        onUpdate: function(items) {  
            items.each(function() {       
                $(this).responsive_layout();  
            });                
        },
    });
});          
</script>
<div id="<?=$id?>" class="slider image_slider <?=$class?>">
    <?php if ($caption): ?>
    <h2 class="slider_caption"><?=$caption?></h2>
    <?php endif ?>
    
    <a class="slider_next"></a>
    <a class="slider_prev"></a>
    <div>
    <ul>
	    <?php foreach ($images as $i => $image): ?>
	    <li>
			<?php if ($image->url): ?>
            <a href="<?=$image->url?>" class="adaptive_image" data-path="<?=$image->path?>" data-mode="contain" style="height: <?=$item_height?>px; width: <?=$item_width?>px">
                <?php if (!$ajax): ?>
				<img src="<?=$image->path?>">
				<?php endif ?>
            </a>        
			<?php else: ?>
            <div class="adaptive_image" data-path="<?=$image->path?>" data-mode="contain" style="height: <?=$item_height?>px; width: <?=$item_width?>px">
                <?php if (!$ajax): ?>
                <img src="<?=$image->path?>">
				<?php endif ?>
            </div>        
			<?php endif ?>
	    </li>
	    <?php endforeach ?>
    </ul>
    </div>
</div>
