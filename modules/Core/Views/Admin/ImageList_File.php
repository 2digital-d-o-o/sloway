<input type="hidden" data-name="path" value="<?=$file->path?>">
<div class="admin_eti_image">
    
</div>
<div class="admin_eti_main with_image">
    <div class="admin_eti_row">
        <div class="admin_eti_menu">
             <a class="admin_button_del small" onclick="$.admin.edittree.remove(this)"><?=t("Delete")?></a>
        </div>
        <span class="admin_eti_title"><?=$file->path?></span>
        <?php echo acontrol::checkbox("visible", v($file, "visible", 1), array("label" => et("Visible")));?>
    </div>
    <div class="admin_eti_row">
        <div style="width: 50%; float: left">
            <fieldset>
                <label><?=et("Title")?></label>
                <div><?=acontrol::edit("title", $file->title)?></div>
            </fieldset>
        </div>
        <div style="width: 50%; float: left">
            <fieldset>
                <label><?=et("Link")?></label>
                <div><?=acontrol::edit("link", $file->link)?></div>
            </fieldset>
        </div>
    </div>
</div>            