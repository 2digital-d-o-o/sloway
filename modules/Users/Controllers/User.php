<?php 

namespace Sloway\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use Sloway\utils;
use Sloway\config;
use Sloway\advform;
use Sloway\advfield;
use Sloway\dbClass;
use Sloway\account;
use Sloway\message;
use Sloway\url;

define("HASH_SALT", "kjsfl94j3kl!lks;alkd;");

class User extends \App\Controllers\BaseController { 
	public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)	{	
		parent::initController($request, $response, $logger);
		
		$cfg = config::get("users.registration");		
		
		$this->fields = advform::fields($cfg);  
	}

    protected function validate_form($fields, $callback = null, $user = null) {
        $result = array();
        foreach ($fields as $name => $ops) {
            $req = v($ops, 'req', false);
            
            $val = trim($this->input->post($name));
            if ($val == "" && $req) 
                $result[$name] = "form_fill_required";
                
            if (!is_null($callback)) {
                $chk = call_user_func_array($callback, array($name, $val, $user));
                if ($chk !== true) 
                    $result[$name] = $chk;
            }
        }    
        return $result;
    }
	protected function validate_account($name, $value, $acc) {
		if ($name == 'username' && $value != $acc->username) {
			$q = $this->db->query("SELECT * FROM account WHERE username = '$value'");
			if (count($q)) 
                return "form_username_exists"; else
				return true;
		}
		if ($name == 'email' && $value != $acc->email) {
			$q = $this->db->query("SELECT * FROM account WHERE email = '$value'");
			if (count($q)) 
				return "form_email_exists"; else
				return true;
		}
		if ($name == 'npassword' || $name == 'cpassword') {
			$np = $this->input->post('npassword');
			$cp = $this->input->post('cpassword');
			
			if ($np == '' && $cp == '') return true;
			
			if ($np != $cp) {
				if ($name == 'cpassword') 
					return "form_invalid_password"; else
                    return true;
			} 
		} 
		
        return true;    
	}    
    protected function validate_registration($name, $value) {
        if ($name == 'username' && $value != '') {
            $q = $this->db->query("SELECT * FROM account WHERE username = '$value' AND status = 1")->getResult();
            if (count($q)) 
                return "form_username_exists"; else
                return true;
        }
        if ($name == 'email' && $value != '') {
            $q = $this->db->query("SELECT * FROM account WHERE email = '$value' AND status = 1")->getResult();
            if (count($q)) 
                return "form_email_exists"; else
                return true;
        }
        if ($name == 'npassword' || $name == 'cpassword') {
            $np = $this->input->post('npassword');
            $cp = $this->input->post('cpassword');

            if ($np != $cp) {
                if ($name == 'cpassword') 
                    return "form_invalid_password"; else
                    return true;
            } 
        } 
        
        return true;
    }
    protected function validate_token($token, $timeout = 0) {
        if (!strlen($token) || !preg_match('/^([a-z0-9]+)$/', $token)) 
            return false;
            
        $user = dbClass::load("account", "@token LIKE '$token%'", 1);
        if (!$user) 
            return false;
        
        $e = explode(":", $user->token);
        if ($timeout) {
            if (count($e) != 2) return false;
            $t = intval($e[1]);
            if ($t + $timeout < time()) return false;
        }
        
        return $user;
    }
	protected function filter_input($obj, $fields) {
		foreach ($fields as $name => $ops) {
			$val = $this->input->post($name);
			$val = trim(htmlspecialchars(strip_tags($val), ENT_QUOTES));
			
			$obj->$name = trim($val);
		}
	}

	protected function login() { 
		$errors = array();
        
        if (count($_POST)) {
			$cookie = $this->input->post('persistent');
            
            $username = trim($this->input->post('username'));
            $password = trim($this->input->post('password'));
            
            if (empty($username)) $errors["username"] = "form_fill_required";
            if (empty($password)) $errors["password"] = "form_fill_required";
			
            if (!count($errors)) {
			    $user = \Sloway\site_user::login($username, $password, $cookie);
			    if ($user->user_id) {
				    //if (isset($this->order)) 
					//    $this->order->set_user($user->data);
			    } else {
                    $errors = array(
                        "username" => "form_invalid_login",
                        "password" => "form_invalid_login"
                    );    
                }
            }
		}   
		
		return $errors;
	} 
	protected function logout() {
		if (isset($this->order)) 
			$this->order->set_user(null);
		
		$user = user::instance("site");
		$user->logout();
		
		url::redirect($this->redirect);
	}
	protected function reset_password() { 
        $errors = array();
		if (count($_POST)) {
			$username = trim($this->input->post('username'));
            if (empty($username)) {
                $errors["username"] = "form_empty_username";
            } else {
				$q = $this->db->query("SELECT id FROM account WHERE username = ?", [$username])->getResult();
				if (count($q)) {
					$user = dbClass::load('account', "@username = '$username'", 1);
                    $user->token = ($token = md5(microtime(true) . HASH_SALT)) . ":" . time();
				    $user->save();
				    
				    $url = url::site("User/ChangePass/" . $token);
                    $vars = array('confirm_link' => "<a href='$url'>$url</a>");
                    
                    $mail = message::load('users.cpassword', 'info', 'mail', $vars)->to_mail();
				    $mail->send($user->email);
			    } 
            }          
		}	
		
		return $errors;
	}
	protected function update_password($user) {
		$errors = array();

		if (count($_POST)) {
			$un = trim($this->input->post("username"));
			$np = trim($this->input->post("npassword"));
			$cp = trim($this->input->post("cpassword"));
			
			if ($un != $user->username) return array("form_invalid_username");

			if (!$np || !$cp) return array("form_invalid_password");	

			if ($np != $cp) return array("form_invalid_password");	
			
			$account = dbClass::post('account', $user->id, array('filter' => true));
			$account->password = account::encode($np);
			$account->save();
		}

		return array();
	}

	protected function send_username() {
        $errors = array();
		if (count($_POST)) {
			$email = trim($this->input->post('email'));
			if (empty($email)) {
                $errors["email"] = "form_empty_email";
            } else {
				$q = $this->db->query("SELECT id FROM account WHERE email = ?", [$email])->getResult();
				if (count($q)) {
					$account = dbClass::load('account', "@email = '$email'", 1);
                    $mail = message::load('users.fusername', 'info', 'mail', array('username' => $account->username))->to_mail();
                    $mail->send($account->email);
			    } 
            }
		}    
		
		return $errors;
	}
	protected function register() {                   
        $errors = array();
		
		$this->fields['cpassword']['req'] = true;
		$this->fields['npassword']['req'] = true;
		
		if (count($_POST)) {
			$errors = $this->validate_form($this->fields, array($this, "validate_registration")); 
            if (!$this->input->post("agree"))
                $errors["agree"] = "form_must_agree";
			
			if (!count($errors)) {
                $reg_auth = config::get("users.reg_auth");
                $username = trim($this->input->post("username"));
                $email = trim($this->input->post("email"));

                $this->db->query("DELETE FROM `account` WHERE username = ? OR email = ?", [$username, $email]);
                
				$user = dbClass::create('account');
				$this->filter_input($user, $this->fields);
				$user->password = account::encode($this->input->post('npassword'));
				$user->status = intval(!$reg_auth);
				$user->token = ($token = md5(microtime(true) . HASH_SALT)) . ":" . time();
				$user->save();
				
                if ($reg_auth) {
                    $url = url::site("User/Confirm/" . $token);
                    $vars = array('confirm_link' => "<a href='$url'>$url</a>");

                    $mail = message::load('users.auth_mail', 'info', 'mail', $vars)->to_mail();
                    $mail->send($user->email);
                } else {
                    $mail = message::load('users.registered', 'info', 'mail')->to_mail();
                    $mail->send($user->email);
                }
			} 
		}
		
		return $errors;
	}
	protected function update_account($user, $req_pass = false) {    
        $errors = array();

        $this->fields['cpassword']['req'] = $req_pass;
        $this->fields['npassword']['req'] = $req_pass;
             
        if (count($_POST)) {
			$errors = $this->validate_form($this->fields, array($this, "validate_account"), $user); 
			if (!count($errors)) {
				$q = $this->db->query("SELECT id FROM account WHERE id = ?", [$user->id])->getResult();
				if (count($q)) {
					$account = dbClass::load('account', "@id = '$user->id'", 1);   
					$this->filter_input($account, $this->fields);
					$np = $this->input->post('npassword');

					if ($np != '')
						$account->password = account::encode($np);

					$account->save();
				}
			} 
		}           
		
		return $errors;
	}
    protected function confirm_account($user) {
        $user->status = 1;
        $user->reg_date = time();
        $user->agree = "YES";
        $user->agree_date = utils::mysql_datetime(time());
        $user->save();
        
                   
        $mail = message::load('users.registered', 'info', 'mail')->to_mail();
        $mail->send($user->email);
    }        
}
