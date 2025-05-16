<?php 
	echo Admin::TabsBegin($this->tabs, $this->tabs_curr, false, 100);
	echo Admin::TabsPage('', $this->tabs_content);       
	echo Admin::TabsEnd();   
?>

