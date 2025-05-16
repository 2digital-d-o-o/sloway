<input type="hidden" data-name="path" value="<?=$image->path?>">
<div class="admin_eti_image">
    <?=\Sloway\thumbnail::from_image($image, "admin_imagelist")->display()?>
</div>
<div class="admin_eti_main with_image">
    <div class="admin_eti_row">
        <div class="admin_eti_menu">
             <a class="admin_button_del small" onclick="$.admin.edittree.remove(this)"><?=t("Delete")?></a>
        </div>
        <span class="admin_eti_title"><?=$image->path?></span>
        <?php echo \Sloway\acontrol::checkbox("visible", v($image, "visible", 1), array("label" => et("Visible")));?>
    </div>
    <div class="admin_eti_row">
        <div style="width: 49%; float: left">
            <?=\Sloway\acontrol::edit("title", $image->title, array("placeholder" => t("Title")))?>
        </div>
        <div style="width: 49%; float: right">
            <?=\Sloway\acontrol::edit("link", $image->link, array("placeholder" => t("Link")))?>
        </div>
    </div>
</div>            