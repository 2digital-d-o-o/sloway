<?php 
	namespace Sloway;

    if (!isset($id)) $id =  utils::generate_id();
?>
<script>
$(document).ready(function() {
    $("#<?=$id?>").find(".rl_template_section [data-name=expanded]").click(function() {
        console.log(this); 
    });
});
</script>
