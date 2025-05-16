<?php
	namespace Sloway;

	echo Admin::Field(t('Title'), acontrol::edit('title', $task->title));
	echo Admin::Field(t('Command'), acontrol::edit('command', $task->command));
	
	$sc = "<table width='100%'>";
	$sc.= "<tr><td width='100'>Minute</td><td>" . acontrol::edit("schedule_0", $schedule[0]) . "</td></tr>";
	$sc.= "<tr><td>Hour</td><td>" . acontrol::edit("schedule_1", $schedule[1]) . "</td></tr>";
	$sc.= "<tr><td>Day/Month</td><td>" . acontrol::edit("schedule_2", $schedule[2]) . "</td></tr>";
	$sc.= "<tr><td>Month</td><td>" . acontrol::edit("schedule_3", $schedule[3]) . "</td></tr>";
	$sc.= "<tr><td>Day/Week</td><td>" . acontrol::edit("schedule_4", $schedule[4]) . "</td></tr>";
	$sc.= "</table>";
	
	echo Admin::Field(t('Schedule'), $sc);
?>
