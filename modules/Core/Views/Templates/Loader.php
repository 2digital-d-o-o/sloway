<?php $id = utils::generate_id(); ?>
<script>
$(document).ready(function() {
    $.post(doc_base + "Core/TemplateLoader/<?=$name?>", {
        content: <?=json_encode($content)?>,    
    }, function(r) {
        $("#<?=$id?>").replaceWith(r).responsive_layout();
    });    
});
</script>
<div class="rl_template_loader" id="<?=$id?>">
    <div class="<?=$class?>"></div>
</div>

