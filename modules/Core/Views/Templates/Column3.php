<?php if ($media != "mail"): ?>

<div class="rl_template rl_template_column3" data-name="column3" style="margin-bottom: 20px">
    <div class="rl_template_element" data-name="tags" data-editor="text"></div>
    <div class="rl_template_span">
        <div>
            <div class="rl_left rl_column" data-name="col1"> 
                <div class="rl_content rl_editable" data-name="col1_ed" data-editor="html" data-frags="true"></div>
            </div>
            <?php if ($media == "editor"): ?>
            <div class="rle_resizer"></div>
            <?php endif ?>
            <div class="rl_center rl_column" data-name="col2">
                <div class="rl_content rl_editable" data-name="col2_ed" data-editor="html" data-frags="true"></div>
            </div>
            <?php if ($media == "editor"): ?>
            <div class="rle_resizer"></div>
            <?php endif ?>
            <div class="rl_right rl_column" data-name="col3">
                <div class="rl_content rl_editable" data-name="col3_ed" data-editor="html" data-frags="true"></div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<table class="rl_template rl_template_column3" data-name="column3" cellpadding="0" cellspacing="0" border="0" width="100%">
<tr>
    <td class="rl_left rl_column" data-name="col1"> 
        <div class="rl_content rl_editable" data-name="col1_ed"></div>
    </td>
    <td class="rl_center rl_column" data-name="col2">
        <div class="rl_content rl_editable" data-name="col2_ed"></div>
    </td>
    <td class="rl_right rl_column" data-name="col3">
        <div class="rl_content rl_editable" data-name="col3_ed"></div>
    </td>
</tr>
</table>

<?php endif ?>