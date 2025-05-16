<?php      
	namespace Sloway;

    class promo_upsale_base {  
        public static function load($order) {
			$cids = array();
			$pids = array();
			$tids = array();

			if (!count($order->items)) return;

			foreach ($order->items as $grp) {
				if ($grp->categories)
					foreach (preg_split('/[.,]/', $grp->categories) as $cid)
						$cids[$cid] = 1;
				
				if ($grp->tags)
					foreach (preg_split('/[.,]/', $grp->tags) as $tid)
						$tids[$tid] = 1;

				$pids[] = $grp->group_id;
			}
			
			$lng = lang::$lang;
			$sql = "@(active = 1 OR active REGEXP '[[:<:]]($lng)[[:>:]]')";
			$asql = array();
			if (count($pids))
				$asql[]= "products REGEXP '[[:<:]](" . implode("|", $pids) . ")[[:>:]]'";

			if (count($cids))
				$asql[]= "categories REGEXP '[[:<:]](" . implode("|", array_keys($cids)) . ")[[:>:]]'";

			if (count($tids))
				$asql[]= "tags REGEXP '[[:<:]](" . implode("|", array_keys($tids)) . ")[[:>:]]'";
			
			if (count($asql))
				$sql.= " AND (" . implode(" OR ", $asql) . ")";

			$upsale = mlClass::load_def("promo_upsale", $sql . " ORDER BY priority DESC", 1);
			return $upsale;
        }
    }                                              
?>
