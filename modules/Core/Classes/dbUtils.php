<?php 

namespace Sloway;

class dbUtils {
	public static $temp_tables = [];
	public static function count($db, $sql) {
		$q = $db->query($sql)->getResult();
		if (!count($q)) return -1;
		
		$q = (array)$q[0];
		foreach ($q as $key => $val) {
			if (strpos($key, "COUNT") === 0)
				return $val;
		}
		
		return -1;
	}

	public static function auto_increment($db, $table) {
		$q = $db->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_NAME = '$table'")->getResult();
		if (count($q))
			return $q[0]->AUTO_INCREMENT; else
			return 0;
	}
    
    public static function insert_multiple($db, $table, $rows, $multiple = false, $batch = 500) {
        $sql = array();          
        $sql_keys = "";
        $sql_vals = "";
        
        if (!$multiple)
            $rows = array($rows);
             
        $i = 0;
        $j = 0;
        foreach ($rows as $values) { 
            $sql_row_vals = "";
            foreach ($values as $key => $val) {
                if ($i == 0) 
                    $sql_keys.= ",`" . $key . "`";
                
                if ($val != "NULL")
                    $val = "'" . $db->escapeString($val) . "'";
                
                $sql_row_vals.= "," . $val;
            }    
            $sql_vals.= "(" . ltrim($sql_row_vals, ",") . "),";
            $i++;
            $j++;
            if ($batch && $j > $batch) {
                $sql_keys = ltrim($sql_keys, ",");
                $sql_vals = rtrim($sql_vals, ",");
                $sql[]= "INSERT INTO `$table`($sql_keys) VALUES\n$sql_vals";
                
                $sql_vals = "";
                $j = 0;                
            }
        }
        
        $sql_keys = ltrim($sql_keys, ",");
        $sql_vals = rtrim($sql_vals, ",");
        if ($sql_vals)
            $sql[]= "INSERT INTO `$table`($sql_keys) VALUES\n$sql_vals";

			
        
        foreach ($sql as $_sql)
            $db->query($_sql);
    }    
    
    public static function insert_update($db, $table, $rows, $multiple = false, $map = null, $batch = 500) {
        $sql = array();
        $sql_keys = "";
        $sql_vals = "";
        $sql_map = "";                         
        
        if (!$multiple)
            $rows = array($rows);
                       
        $i = 0;
        $j = 0;
        foreach ($rows as $values) { 
            $sql_row_vals = "";
            foreach ($values as $key => $val) {
                if ($i == 0) {
                    $sql_keys.= ",`" . $key . "`";
                    if ($map && isset($map[$key]))
                        $sql_map.= ",`" . $key . "` = " . $map[$key]; else
                        $sql_map.= ",`" . $key . "` = VALUES(`" . $key . "`)";
                }
					
				if (is_null($val))
					$val = "";
                if ($val != "NULL")
                    $val = "'" . $db->escapeString($val) . "'";
                
                $sql_row_vals.= "," . $val;
            }    
            $sql_vals.= "(" . ltrim($sql_row_vals, ",") . "),";
            $i++;
            $j++;
            
            if ($batch && $j > $batch) {
                $sql_keys = ltrim($sql_keys, ",");
                $sql_vals = rtrim($sql_vals, ",");
                $sql_map = ltrim($sql_map, ",");
                $sql[]= "INSERT INTO `$table`($sql_keys) VALUES\n$sql_vals\nON DUPLICATE KEY UPDATE $sql_map";
                
                $sql_vals = "";
                $j = 0;                
            }
        }
        $sql_keys = ltrim($sql_keys, ",");
        $sql_vals = rtrim($sql_vals, ",");
        $sql_map = ltrim($sql_map, ",");
        
        if ($sql_vals) 
            $sql[]= "INSERT INTO `$table`($sql_keys) VALUES\n$sql_vals\nON DUPLICATE KEY UPDATE $sql_map";  

        foreach ($sql as $_sql)
            $q = $db->query($_sql);
        
       // return $q->insert_id();
    }    
    
    public static function table_exists($db, $name) {
        $q = $db->query("SHOW TABLES LIKE '$name'")->getResult();
        return (count($q) != 0);
    }
    
    public static function query_value($db, $sql, $path, $default = "") {
        $q = $db->query($sql)->getResult();
        
        $e = explode(".", $path, 2);
        if (count($e) > 0) {
            if (!isset($q[$e[0]])) return $default;
            
            $res = $q[$e[0]];
        }
        if (count($e) > 1)
            $res = v($res, $e[1], $default);
        
        return $res;
    }
    
    public static function column_exists($db, $table, $name) {
        $q = $db->query("SHOW COLUMNS FROM `$table` LIKE '$name'")->getResult();
        return count($q) > 0;
    }
    
    public static function clone_table($db, $table, $name, $temporary, $columns = null, $add_columns = "", $res_prefix = "") {
		if (is_string($columns)) $columns = "," . $columns . ",";
		
		$values = [];
		$result = "";
		foreach ($db->query("SHOW COLUMNS FROM `$table`")->getResult() as $qq) {
			$col_name = $qq->Field;
			$col_type = $qq->Type;
			if ($columns && strpos($columns, "," . $col_name . ",") === false) continue;
			
			$values[]= "`$col_name` $col_type NOT NULL";
			$result.= "," . $res_prefix . $col_name;
		}
		if (is_array($add_columns)) {
			foreach ($add_columns as $col_name => $col_param) {
				$values[]= "`$col_name` " . $col_param;
			}
		}
		$values = implode(",\n", $values) . "\n";
		if ($temporary) {
			if (isset(self::$temp_tables[$name]))
				$db->query("DROP TEMPORARY TABLE `$name`");
			
			$sql = "CREATE TEMPORARY TABLE `$name` (\n$values) ENGINE=MyISAM DEFAULT CHARSET=utf8";
			$db->query($sql);
			
			self::$temp_tables[$name] = true;
		} else {
			$q = $db->query("SHOW TABLES LIKE '$name'")->getResult();
			if (!count($q)) {
				$sql = "CREATE TABLE `$name` (\n$values) ENGINE=MyISAM DEFAULT CHARSET=utf8";
				$db->query($sql);
			} else
				$db->query("TRUNCATE TABLE `$name`");
		}
		if ($columns)
			return trim($result, ","); else
			return $res_prefix . "*";
    }    
    
    public static function root_id($db, $table, $field, $id, $sql = "") {
        if ($sql)
            $sql = " AND " . $sql; 
            
        $q = $db->query("SELECT id,id_parent FROM pages WHERE id = ?" . $sql, [$id])->getResult();    
        while (count($q) && $q[0]->id_parent) 
            $q = $db->query("SELECT id,id_parent FROM pages WHERE id = ?" . $sql, [$q[0]->id_parent])->getResult();                    
        
        return count($q) ? $q[0]->id : 0;
    }
}
