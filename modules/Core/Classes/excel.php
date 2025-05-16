<?php

namespace Sloway;

require MODPATH . "Core/Classes/FastExcelWriter/autoload.php";

class excel {
	public static function create($lines) {
		$excel = \avadim\FastExcelWriter\Excel::create(['Sheet1']);
		$sheet = $excel->getSheet();

		foreach ($lines as $cells) {
			$sheet->writeRow($cells);
		}
		
		return $excel;
	}
	public static function output($lines, $filename) {
		$excel = self::create($lines);
		$excel->output($filename . '.xlsx');
	}
}

?>