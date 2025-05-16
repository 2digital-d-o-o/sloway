<?php            
	$tabs = array();
	foreach ($this->tabs as $name => $ops) {
        $cnt = v($this->tab_stats, $name, "");
        if ($cnt != "") $cnt = "($cnt)";
        $url = v($ops, "url");
            
        $tab = "<a href='$url' onclick='return admin_redirect(this)'>";
        $tab.= $ops['title'] . "<span class='admin_orders_count'>$cnt</span>";
        $tab.= "</a>";      
            
		$tabs[$name] = $tab;
    }
	
	echo Admin::TabsBegin($tabs, $this->tabs_curr, false, 110);
	echo Admin::TabsPage('', "<div id='analytics_content'>" . $this->tabs_content . "</div>");
