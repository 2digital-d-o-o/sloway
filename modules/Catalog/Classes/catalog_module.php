<?php

namespace Sloway;

class catalog_module {
	public static function load() { 
		catalog::$db = core::$db;
		if (!config::get("catalog.stock.handler", true)) return;

		if (class_exists("Sloway\order")) {		
			if (config::get("catalog.stock.realtime", false)) {
				order::bind("reservation_create", "Sloway\catalog::reservation_create");
				order::bind("reservation_update", "Sloway\catalog::reservation_update");
				order::bind("reservation_check", "Sloway\catalog::reservation_check");
				order::bind("reservation_release", "Sloway\catalog::reservation_release");
			}

			//order::bind("update_item_stock", "Sloway\catalog::update_stock");
			//order::bind("check_item_stock", "Sloway\catalog::check_stock");
		}
	}
}  

