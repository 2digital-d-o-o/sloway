<?php
	use \Sloway\admin;

	echo admin::TabsBegin($tabs, $tab_curr, false, 120);
	echo admin::TabsPage($tab_curr, $tab_content);
	echo admin::TabsEnd();
?>
