<?php
	namespace Sloway;

	class task {
		public static function schedule($sch) {
			$sch = ($sch) ? @explode(" ", $sch) : array();
			$res = array();
			$res[0] = isset($sch[0]) ? $sch[0] : "0";
			$res[1] = isset($sch[1]) ? $sch[1] : "12";
			$res[2] = isset($sch[2]) ? $sch[2] : "*";
			$res[3] = isset($sch[3]) ? $sch[3] : "*";
			$res[4] = isset($sch[4]) ? $sch[4] : "*";

			return $res;
		}
        public static function reschedule($id, $time = 0) {
            if (!$time) $time = time();
            
            $task = dbClass::load('task',"@id = $id", 1);
            if (!$task) return false;
            
            $task->time_last = $time;
            $task->time_next = cron::next($task->schedule);
            $task->running = 0;
            $task->save();
            
            return $task->time_next;
        }        
	}
?>
