<?php if ($media != "mail"): ?>

<div class="rl_template rl_template_text_image" data-name="text_image" data-collapse="1.1"  data_attr_img_pos="right" style="margin-bottom: 20px">
    <div class="rl_template_element" data-name="tags" data-editor="text"></div>
    <div class="rl_template_span"> 
        <div>
            <div class="rl_left" data-name="left">
                <div class="rl_content rl_editable" data-name="content" data-editor="html" data-resimg="false"></div>
            </div>
            <?php if ($media == "editor"): ?>
            <div class="rle_resizer"></div>
            <?php endif ?>
            <div class="rl_right" data-name="right">
                <div class="rl_image_wrapper">
                    <div class="rl_image rl_editable" data-name="img" data-editor="image"></div>
                    <div class="rl_image_desc rl_template_element" data-name="img_title" data-editor="text"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<table class="rl_template rl_template_text_image" data-name="text_image" cellpadding="0" cellspacing="0" border="0" width="100%">
<tr>
    <td class="rl_left" data-name="left" valign="top">
        <div class="rl_content rl_editable" data-name="content"></div>
    </td>
    <td></td>
    <td class="rl_right" data-name="right" valign="top">
        <div class="rl_image rl_editable" data-name="img"></div>
        <div class="rl_desc rl_editable" data-name="desc"></div>
    </td>
</tr>
</table>

<?php endif ?>