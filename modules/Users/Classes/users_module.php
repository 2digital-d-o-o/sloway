<?php
	namespace Sloway;

	require_once MODPATH . "Users/Classes/site_user.php";

	class users_module {
		public static function load($ctrl) { 
            if (class_exists("Sloway\order")) {
				order::bind("initialize", "Sloway\users_module::order_initialize");
			    order::bind("set_user", "Sloway\users_module::order_set_user");
			    order::bind("username", "Sloway\users_module::order_username");
			    order::bind("user_id", "Sloway\users_module::order_user_id");
			    order::bind("login_user", "Sloway\users_module::order_login_user");
			    order::bind("register_user", "Sloway\users_module::order_register_user");
			    order::bind("validate_user", "Sloway\users_module::order_validate_user");
			    order::bind("confirm_user", "Sloway\users_module::order_confirm_user");
            }
		}

		public static function order_initialize($order) {
			$user = site_user::instance();
			if (is_null($user->data) != is_null($order->user))
				$order->set_user($user->data);	
		}
		public static function order_set_user($order, $user) {
			if ($user) {
				foreach (config::get('users.order_mapping', array()) as $key => $val) {
					if (is_int($key))
						$order->$val = $user->$val; else
						$order->$val = $user->$key;	
				}
				$order->reg_mode = 'login';
				
				$order->id_user = $user->id;
			} else {
				foreach (config::get('users.order_mapping', array()) as $key => $val) 
					$order->$val = '';					

				$order->reg_mode = 'skip';
				$order->clear();
				$order->id_user = 0;
			}
		}   
		public static function order_username($order) {
			if ($order->user)
				return $order->user->username;    
		}
		public static function order_user_id($order) {
			if ($order->user)
				return $order->user->id; else
				return 0;    
		}    
		public static function order_login_user($order, $username, $password) {
			$aid = site_user::login($username, $password)->user_id;
			if ($aid) {
				$acc = dbClass::load('account', "@id = $aid", 1);
				$order->set_user($acc);
				
				return $acc;
			}
		}  
		public static function order_validate_user($order) {
			$user = dbClass::load('account', "@username = '$order->reg_username' AND status = 1", 1);
			if ($user)
				return "order_user_exists"; else
				return null;
		}
		public static function order_confirm_user($order) {
			$user = dbClass::load('account', "@id = '$order->id_user' AND status = 0", 1);
			if ($user) {
				$user->status = 1;
				$user->reg_date = time();  
				$user->save();
				
				$mail = message::load("users.registered", null, "mail", null,null, $order->language)->to_mail();
				$mail->send($user->email);            
			}
		}
		public static function order_register_user($order) {
			$user = dbClass::create('account');
			foreach (config::get('users.order_mapping') as $key => $val) {
				$user_fn = is_int($key) ? $val : $key;
				$order_fn = $val;
				
				$user->$user_fn = $order->$order_fn;
			}
			$user->username = $order->reg_username;
			$user->password = account::encode($order->reg_password);
			
			$user->status = 0;
			$user->save();
			
			$order->id_user = $user->id;
		}
	}  
?>
