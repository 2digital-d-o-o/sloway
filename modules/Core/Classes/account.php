<?php          
	namespace Sloway;
	
	class account extends \Sloway\genClass { 
		protected static $instances = array();
		public static $prefix;
		public static $db;
		public static $session;
		
		public $profile;
		public $user_id = 0;        
		
		protected function gen_token($cookie) {  
			$uid = $this->user_id;
			if (!$this->sid)
				$this->sid = md5(mt_rand() . microtime(true) . $uid);   
			
			$token = md5(mt_rand() . microtime(true) . $uid);   
			$name = self::$prefix . "_" . $this->profile . "_token";
			
			if ($cookie) {
				$time = 14 * 24 * 60 * 60;
				set_cookie($name, $this->sid . ":" . $token, $time); 
			} else
				self::$session->set($name, $this->sid . ":" . $token);
				
			//log::debug("account", Router::$controller . "/" . Router::$method . "::new token", $token);
			
			$db = self::$db;
			$q = $db->query("SELECT * FROM account_token WHERE id_user = ? AND session = ?", [$uid, $this->sid])->getResult();
			if (!count($q)) 
				$db->query("INSERT INTO account_token (id_user, token, session, time) VALUES(?,?,?,?)", [$uid, $token, $this->sid, time()]); else
				$db->query("UPDATE account_token SET old_token = token, token = ?, session = ?, time = ? WHERE id = ?", [$token, $this->sid, $q[0]->id, time()]);                
		}
		protected function get_user() {
			$cookie = true;
			$regen = false;
			
			$name = self::$prefix . "_" . $this->profile . "_token";     
			
			$token = get_cookie($name);
			
			if (!$token) {
				$cookie = false;
				$token = self::$session->get($name, null);
			} else
                set_cookie($name, $token, 14 * 24 * 60 * 60);
			
			if (!$token) 
				return 0;
			
			if (!preg_match('/^([a-z0-9:]+)$/', $token)) 
				return 0; 
			
			$e = explode(":", $token, 2);
			if (count($e) != 2) 
				return 0;
				
			$sid = $e[0];
			$tid = $e[1];
				
			$db = self::$db;
			
			$q = $db->query("SELECT * FROM account_token WHERE session = ?", [$sid])->getResult();
			if (count($q) && ($q[0]->old_token == $tid || $q[0]->token == $tid)) {  
				$this->sid = $q[0]->session;
				$this->user_id = $q[0]->id_user;
				
				/*if ($q[0]->old_token == $tid || time() - $q[0]->time > utils::config("account.regenerate_time", 10)) {
					echo "regenerate";
					$this->gen_token($cookie);
				} */
                
				return $this->user_id;
			} 
		}
		protected function check($username, $password) {
			return 0;	
		}
		
		public function logged() {
			return $user_id != 0;	
		}
		public function logout() {
			if (!$this->user_id) return;
			
			self::$db->query("DELETE FROM account_token WHERE session = ?", $this->sid);
			
			$name = self::$prefix . "_" . $this->profile . "_token";   
			delete_cookie($name);
			self::$session->remove($name);
			
			unset(account::$instances[$this->profile]);
			
			$this->user_id = 0; 
		}
		public function __construct($profile) {
			parent::__construct();
			
			$this->profile = $profile;
		}
		
		public static function encode($password) {
			return hash::create(sha1($password)); 
		}      
		public static function instance($profile = null, $class = "account") {
			if ($profile == null)
				$profile = Router::$profile;
				
			if (isset(account::$instances[$profile]))
				return account::$instances[$profile];
			
			$res = new $class($profile);
			$res->get_user();	
			account::$instances[$profile] = $res;
			
			return $res;
		}
		public static function login($username, $password, $cookie = false, $profile = null, $class = "\Sloway\account") {
			$res = new $class($profile);
			$res->check($username, $password);
			
			if ($res->user_id)
				$res->gen_token($cookie);
				
			account::$instances[$profile] = $res; 
				
			return $res;
		}
	}  

