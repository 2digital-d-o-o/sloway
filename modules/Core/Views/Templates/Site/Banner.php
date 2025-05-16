<?php 
	namespace Sloway;

    if (!isset($script)) $script = true;
    $id = utils::generate_id();
?>
<?php if ($script): ?>
<script>
$(document).ready(function() {
    $("#<?=$id?>").hover(function() {
        $(this).find(".rl_banner_desc").stop().slideDown(); 
    }, function() {
        $(this).find(".rl_banner_desc").stop().slideUp(); 
    });
});
</script>
<?php endif ?>

<div class="rl_banner" id="<?=$id?>">
    <div class="rl_banner_pad" style="padding-top: <?=$ratio?>%"></div>
    <div class="rl_banner_image">
        <?php if ($image): ?>
        <div class="adaptive_image" data-path="<?=$image?>" data-mode="cover" data-alt="<?=$alt?>">
            <?php if (!$ajax): ?>
            <img src="<?=$image?>" alt="<?=$alt?>">
            <?php endif ?>
        </div>
        <?php endif ?>
    </div>
    <div class="rl_banner_content">
        <div>
            <h2 class="rl_banner_title"><?=$title?></h2>
            <div class="rl_banner_desc"><?=$desc?></div>
        </div>
    </div>
    <?php if ($link): ?>
    <a class="rl_banner_link" href="<?=$link->url?>" target="<?=$link->trg?>"></a>
    <?php endif ?>
</div>

