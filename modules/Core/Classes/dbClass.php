<?php 

namespace Sloway;

/*
	dbClass.load( table, query, limit, lang, options )
	table   - ime tabele
	query   - mysql query, ce se zacne z %, ga sam dokonca
			- "%id = 1" -> "SELECT * FROM <table> WHERE id = 1"
	limit   - 0 = array, 1 = objekt, n = array(n)
	options
	 - index     - po cem se indeksira (ime fielda v tabeli)
	 - images    - loadanje/brisanje slik (avtomaticno)
	 - edit_user - ime userja ki se shrani skupaj z edit_time
	 - order     - ime fielda v tabeli (obicajno id_order), uposteva se samo ce v queryu ni ORDER BY
*/   

class dbClass extends genClass {
	public $table = '';   
	public static $get_table_name = null;
	public $ops;                                        
	
	public static $database = null;
	public static $options = array (
		'index' => null,
		'images' => false,
		'edit_user' => '',
		'db' => null,
	);
    
	protected static function getTable($table) {
		if (is_callable(self::$get_table_name)) {
			$tn = call_user_func(self::$get_table_name, $table); 
			if ($tn)
				return $tn; else
				return $table;
		}else
			return $table;
	}
	protected static function getFields($table, $db) {
		$q = $db->query("SHOW COLUMNS FROM `$table`");
		
		$f = array();
		foreach ($q->getResult() as $field) 
			$f[] = $field->Field;
		
		return $f;
	}
	protected static function getDB($ops) {
		if ($ops['db'] == null)
			return self::$database; else
			return $ops['db'];                
	}
	protected static function getOps($ops) {
		if ($options = null) $options = array();
		
		foreach (dbClass::$options as $name => $value) 
			if (!isset($ops[$name]))
				$ops[$name] = $value;
		
		return $ops;
	}
    
	public static function columns($table, $options = null) {
		$table = self::getTable($table);
		$ops = self::getOps($options);   
		$db = self::getDB($ops);    
		
		$q = $db->query("SHOW TABLES LIKE '$table'");
		if (!count($q)) 
			return array();
		$q = $db->query("SHOW FULL COLUMNS FROM " . $table); 
			
		foreach ($q as $e) 
			$res[$e->Field] = $e->Type;
		return $res;
	}
	public static function extend($table, $columns, $options = null) {
		$table = self::getTable($table);
		$ops = self::getOps($options);   
		$db = self::getDB($ops);   
		
		$orig = dbClass::columns($table, $options);	
		
		$sql = array();	
		foreach ($columns as $name => $type) {
			if (isset($orig[$name])) continue;
			
			$type = strtoupper($type);
			
			$sql[] = "ADD COLUMN `$name` $type NOT NULL";
		}
		
		if (!count($sql)) return false;
		
		$sql = "ALTER TABLE `$table` " . implode(", ", $sql);
		$db->query($sql);
	}
	public static function load($table, $query = '', $limit = 0, $options = null, $class = "\Sloway\dbClass", $filter = '') {
		$table = self::getTable($table);
		$ops = self::getOps($options);
		$db = self::getDB($ops);
						       
        if (is_int($query) || is_numeric($query))
            $query = "@id = '$query'";
                                
		if ($query == '') {
			$sql = "SELECT * FROM `$table`";                                        
			if ($filter != '')
				$sql.= " WHERE $filter";
		} else
		if ($query[0] == '%' || $query[0] == '@') {
			$sql = "SELECT * FROM `$table` WHERE ";
			if ($filter != '')
				$sql.= "$filter AND ";
			
			$sql.= substr($query, 1); 
		} else
		if ($query[0] == '#' || $query[0] == '*') {
			$sql = "SELECT * FROM `$table` ";
			if ($filter != '')
				$sql.= "WHERE $filter ";
			$sql.= substr($query, 1);             
		} else 
			$sql = $query;
			
		$q = $db->query($sql);
		$q = $q->getResult();
		
		if ($limit == 0)
			$cnt = count($q); else
			$cnt = min(count($q), $limit);
			
		if (!$cnt) {
			if ($limit == 1) 
				return null; else
				return array();
		}
		
		$first = null;
		$res = array();    
		for ($i = 0; $i < $cnt; $i++) {
            $cls = is_callable($class) ? call_user_func($class, $table, $q[$i]) : $class;
            if (!class_exists($cls))
                $cls = "\Sloway\dbClass";
            
			$obj = new $cls($table, get_object_vars($q[$i]), $ops); 
			
			$ind = $ops['index'];
			if ($ind != '' && isset($obj->__data[$ind]))
				$res[$obj->__data[$ind]] = $obj; else
				$res[] = $obj;
			
			if (!$first)
				$first = $obj;
		}
		
		if ($limit == 1) 
			return $first; else
			return $res;
	}
    public static function load_def($table, $query = '', $limit = 0, $options = null, $class = "\Sloway\dbClass", $filter = '') {
        $res = dbClass::load($table, $query, $limit, $options, $class, $filter);
        if (is_null($res))
            $res = dbClass::create($table, 0, $options, $class);
        
        return $res;
    }
	public static function create($table, $id = 0, $options = array(), $class = "\Sloway\dbClass") {
		$table = self::getTable($table);
		
		if (is_callable($class))
			$class = call_user_func($class, $table, null);
		
		return new $class($table , array('id' => $id), dbClass::getOps($options));
	}
	public static function post($table, $id = 0, $options = array(), $class = "\Sloway\dbClass") {
		$table = self::getTable($table);
		
		if (is_callable($class))
			$class = call_user_func($class, $table, null);
		
		$ops = self::getOps($options);
		$db = self::getDB($ops);
		
		$obj = new $class($table, array(), $ops);
		$obj->__data['id'] = $id;
		$fields = dbClass::getFields($table, $db);
		
		$prefix = (isset($ops['prefix'])) ? $ops['prefix'] : '';
		$postfix = (isset($ops['postfix'])) ? $ops['postfix'] : '';
		
		foreach ($fields as $fname) {
			$pname = $prefix . $fname . $postfix;            
			if (isset($_POST[$pname]))
				$obj->__data[$fname] = $_POST[$pname];
		}
		
		return $obj;
	}
                                                                          
	public function __construct($table, $data = array(), $ops = array()) {
		$this->table = $table;
		$this->__data = $data;
        
        foreach ($this->__data as $name => $value)
            $this->__data[$name] = $this->__value($name, $value, false);
        
		$this->ops = $ops;
	}        
    public function __value($name, $value, $save) {
        return $value;            
    }
	public function refresh($fields = null) {
		if (!$this->id) return;
		$db = self::getDB($this->ops);
		
		if (!$fields)
			$fields = self::getFields($this->table, $db); else
		if (is_string($fields)) 
			$fields = array($fields); 
			
		$query = "SELECT " . implode(",", $fields) . " FROM `" . $this->table . "` WHERE id = " . $this->id;
		
		$q = $db->query($query);
		$q = $q[0];
		
		foreach ($fields as $name)
			if (isset($q->$name))
				$this->__data[$name] = $q->$name;
				
		if (count($fields) == 1)
			return $this->__data[$fields[0]]; else
			return true;
	}
	
	public function commit_id() {
		$db = self::getDB($this->ops); 
		return $db->auto_increment($this->table);
	}
	public function save() {   
		$db = self::getDB($this->ops);
		$fields = dbClass::getFields($this->table, $db);
		
		$id = isset($this->__data['id']) ? $this->__data['id'] : 0;
        if (!$id) $id = 0; 
		                     
		$q = $db->query("SELECT id FROM `$this->table` WHERE id = $id");
		if (!count($q->getResult())) {
			$q = $db->query("INSERT INTO `$this->table` VALUES ()");
			$this->__data['id'] = $db->insertID();    
		}
		
		$this->__data['edit_user'] = $this->ops['edit_user'];
		$this->__data['edit_time'] = time();
        if (!$id)
            $this->__data['create_time'] = time();
		     
		$keys = array();
		$vals = array();
		foreach ($fields as $fname) {     
			if (!isset($this->__data[$fname])) continue;
			$value = $this->__value($fname, $this->__data[$fname], true);
			
			if (is_object($value) || is_array($value)) continue;
			if ($fname == 'id') continue;
			
			$keys[]= "`" . $fname . "`";
			$vals[]= $value;                
		}
		
		$sql = "UPDATE `$this->table` SET " . implode(" = ?, ", $keys) . " = ? WHERE id = ?";
		$vals[] = $this->__data['id'];

		$db->query($sql, $vals);
	}
	public function delete() {
		$db = self::getDB($this->ops);
		$sql = "DELETE FROM `$this->table` WHERE id = ?";
		$db->query($sql, $this->id);    
	}   
}
