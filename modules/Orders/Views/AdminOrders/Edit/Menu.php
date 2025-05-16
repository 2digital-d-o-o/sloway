<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<?php
    echo "<div class='admin_section admin_form_menu'>";
    echo "<h2 class='admin_section_header'>" . et("Options") . "</h2>";
    
    if (!$this->order->id) 
        echo acontrol::select("partner", $this->partners_select, "", array("placeholder" => "Choose partner")) . "<br>";
    
    $back_url = url::site("AdminOrders/View/" . $this->order->id);
    
    echo "<button class='admin_button_save admin_button vertical' onclick='return false'>" . et("Save") . "</button>";
    echo "<button class='admin_button_cancel admin_button vertical' onclick='return false' data-url='$back_url' data-ajax='1'>" . et("Close") . "</button>"; 
    echo "<button class='admin_button_revert admin_button vertical' onclick='return false'>" . et("Revert") . "</button>";   
    echo "</div>";    
?>