<?php
	namespace Sloway;

    require_once MODPATH . "Core/Classes/Cron/CronExpression.php";

	class cron {
        public static function due($exp) {
            $cron = \CronExpression::factory($exp);  
            return $cron->isDue();            
        }
        public static function next($exp) {
            $cron = \CronExpression::factory($exp);  
            $date = $cron->getNextRunDate();
            
            return $date->getTimestamp();
        }
	}
?>
