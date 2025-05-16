<?php if ($media != "mail"): ?>

<div class="rl_template rl_template_text" data-name="text" style="margin-bottom: 20px">
    <div class="rl_template_element" data-name="tags" data-editor="text"></div>
    <div class="rl_template_span">
        <div class="rl_content rl_editable" data-name="content" data-editor="html" data-resimg="false"></div>
    </div>
</div>

<?php else: ?>

<table class="rl_template rl_template_text" data-name="text" cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
        <td class="rl_content rl_editable" data-name="content"></td>
    </tr>
</table>

<?php endif ?>