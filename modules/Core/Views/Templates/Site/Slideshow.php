<?php 
	namespace Sloway;

    if (!isset($id)) $id =  utils::generate_id();
    if (!isset($framed)) $framed = false;
    if (!isset($min_height)) $min_height = 0;
    if (!isset($max_height)) $max_height = 0;
    if (!isset($aspect_ratio)) $aspect_ratio = 1;
    if (!isset($arrows)) $arrows = true;
    if (!isset($bullets)) $bullets = false;
    if (!isset($interval)) $interval = 0;
?>
<script>
$(document).ready(function() {
    $("#<?=$id?> > ul").slideshow({ 
        anim_duration: 400,
        anim_type: "slide",
        calc_height: <?=($framed) ? "false" : "true"?>,
        aspect_ratio: "<?=$aspect_ratio?>",
        min_height: "<?=$min_height?>",
        max_height: "<?=$max_height?>",
        layered: true,
        interval: <?=$interval?>,
        <?php if ($bullets): ?>
        pager_selector: "#<?=$id?> .slideshow_bullets > a[data-index=%INDEX%]",
        <?php endif ?>
    });    
    
    <?php if (count($slides) > 1 && $arrows): ?>
    $("#<?=$id?> > .slideshow_prev").click(function() { $("#<?=$id?> > ul").slideshow("prev") });
    $("#<?=$id?> > .slideshow_next").click(function() { $("#<?=$id?> > ul").slideshow("next") });
    <?php endif ?>
});
</script>

<div id="<?=$id?>" class="rl_slideshow">
    <?php if (count($slides) > 1 && $arrows): ?>
    <a class="slideshow_prev"></a>    
    <a class="slideshow_next"></a>    
    <?php endif ?>
    <ul>
        <?php foreach ($slides as $slide): ?>
        <li style="background-image: url('<?=$slide->background?>'); background-size: cover; background-repeat: no-repeat; background-position: center">
            <div class="slideshow_slide">
                <?php if ($slide->title): ?>
                <h2 class="slideshow_slide_title"><?=$slide->title?></h2>
                <?php endif ?>
                <?php if ($slide->content): ?>
                <div class="slideshow_slide_content"><?=$slide->content?></div>
                <?php endif ?>
            </div>
            <?php if ($link = v($slide, "link")): ?>
            <a class="slideshow_link" draggable="false" href="<?=$link->url?>" target="<?=$link->trg?>"></a>
            <?php endif ?>
        </li>
        <?php endforeach ?>
    </ul> 
    
    <?php if (count($slides) > 1 && $bullets): ?>
    <div class="rl_slideshow_bullets">
        <?php foreach ($slides as $index => $slide): ?>
        <a data-index="<?=$index?>"></a>
        <?php endforeach ?>
    </div>    
    <?php endif ?>
</div>
 