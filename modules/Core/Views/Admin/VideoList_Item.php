<div class="admin_eti_image admin_eti_video"></div>
<div class="admin_eti_main with_image">
    <div class="admin_eti_row">
        <div class="admin_eti_menu">
             <a class="admin_button_del small" onclick="$.admin.edittree.remove(this)"><?=t("Delete")?></a>
             <?php echo acontrol::checkbox("visible", v($video, "visible", 1), array("label" => et("Visible")));?>
        </div>
        <fieldset style="margin-bottom: 5px">
            <div><?=acontrol::edit("path", $video->path, array("placeholder" => t("Paste iframe code")))?></div>
        </fieldset>
        <fieldset>
            <div><?=acontrol::edit("desc", $video->description, array("placeholder" => t("Description")))?></div>
        </fieldset>
    </div>
</div>            