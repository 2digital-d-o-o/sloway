<input type="hidden" data-name="path" value="<?=$file->path?>">
<div class="admin_eti_image admin_eti_file">
    <?php if ($ext = pathinfo($file->path, PATHINFO_EXTENSION)): ?>
    <label><?=$ext?></label>
    <?php endif ?>
</div>
<div class="admin_eti_main with_image">
    <div class="admin_eti_row">
        <div class="admin_eti_menu">
             <a class="admin_button_del small" onclick="$.admin.edittree.remove(this)"><?=t("Delete")?></a>
        </div>
        <span class="admin_eti_title"><?=$file->path?></span>
        <?php echo \Sloway\acontrol::checkbox("visible", v($file, "visible", 1), array("label" => et("Visible")));?>
    </div>
    <div class="admin_eti_row">
        <?php if (is_array($tags)): ?>
        <div style="width: 80%; float: left">
            <fieldset>
                <label><?=et("Description")?></label>
                <div><?=\Sloway\acontrol::edit("desc", $file->description)?></div>
            </fieldset>
        </div>
        <div style="width: 20%; float: left">
            <fieldset>
                <label><?=et("Tag")?></label>
                <div><?=\Sloway\acontrol::select("tag", $tags, $file->tag)?></div>
            </fieldset>
        </div>    
        <?php else: ?>
        <fieldset>
            <label><?=et("Description")?></label>
            <div><?=\Sloway\acontrol::edit("desc", $file->description)?></div>
        </fieldset>
        <?php endif ?>
    </div>
</div>            