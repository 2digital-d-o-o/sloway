<?php 

namespace Sloway\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

use Sloway\path;

class AdminLogin extends AdminController {
	public $check_login = false;
	public function Index() {
        $this->message = false;

		$this->logo = path::gen('site.modules.Core','media/img/admin-logo.png');
		if ($s = \Sloway\Settings::get("logo")) {
			$this->logo = \Sloway\thumbnail::from_image(json_decode($s), "admin_logo")->result;
		}
		$image = \Sloway\config::get("admin.login_image");

		if ($this->request->getPost("login")) {
			$u = $this->request->getPost('username');
			$p = $this->request->getPost('password');
			$r = $this->request->getPost('remember');
			
			$user = \Sloway\admin_user::login($u, $p, $r);
            if (!$user->user_id)
                $this->message = et("admin_login_invalid");
		} else {
			$user = \Sloway\admin_user::instance();
		}
		
		if ($user->user_id) 
			$this->response->redirect(site_url("Admin"));
		
		return view('\Sloway\Views\AdminLogin', array(
			"logo" => $this->logo,
			"image" => $image,
			"message" => $this->message
		));  
	}

	public function Logout() {
		$user = \Sloway\admin_user::instance();  
		$user->logout();
		
		$this->response->redirect(site_url('AdminLogin'));
	}

}
