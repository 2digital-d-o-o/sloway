<?php
use Sloway\admin;

?>


<script>  
$(document).ready(function() {
    $("#uploads").browser({
        handler: doc_base + "Admin/Ajax_BrowserHandler",
    });
});
</script>

<?php 
	echo Admin::SectionBegin(et("Uploads"), false);
    
    echo "<div id='uploads' style='height: 400px'></div>";
    
	echo Admin::SectionEnd();
?>

