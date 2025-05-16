<?php 

namespace Sloway\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sloway\config;
use Sloway\fixed;
use Sloway\url;
use Sloway\message;
use Sloway\facebook_api;
use Sloway\promo_code;

class Cart extends \App\Controllers\BaseController {
    public $payment_fields = array("!email", "!firstname", "!lastname", "!street", "!zipcode", "!city", "!country");               
    public $delivery_fields = array("!firstname", "!lastname", "!street", "!zipcode", "!city", "!country");
	public $bottom_content = "";
	
	public function on_init(&$steps) {

	}
	public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)	{	
		parent::initController($request, $response, $logger);
		
        $this->script = true;
		$this->ajax = false;            
		$this->next = null;
		$this->prev = null;
		$this->step = 0;
        $this->message = false;    
        $this->message_type = "info";
		$this->content = "";
        
        $this->cart_columns = config::get("orders.cart_columns");
        if (!isset($this->cart_edit))
            $this->cart_edit = false;
        
		$steps = array(
			'cart' => 'Cart', 
			'user' => 'User', 
			'addr' => 'Address', 
			'pay' => 'Payment', 
			'rev' => 'Review', 
			'inv' => 'Invoice'
		);
		
		if ($this->order->user) 
            unset($steps['user']);

		$this->on_init($steps);

		$this->steps = array_values($steps);

        if ($error = $this->input->get("error")) {
            $this->message = $error;
            $this->message_type = "error"; 
        }
	}

    protected function update_item($item, $name, $value) {
        switch ($name) {
            case "quantity":
                $value = intval($value);
                if ($value > 0) {
                    $r = $this->order->setQuantity($item, $value, true); 
                } else
                    $this->order->remItem($index, true); 
                    
                if (!\Sloway\order::$reservations)
                    $this->order->validate(); else
                if (!$r)
                    $error = et("cart_error_qty_update");
                break;
            case "price": 
                if (!$this->cart_edit) return;
                
                $item->setPrice($value, $item->tax_rate);            
                break;
            case "discount":
                if (!$this->cart_edit) return;
                
                $item->discount = fixed::gen($value) / 100;
                break;
            case "commission":
                if (!$this->cart_edit) return;
                
                $item->commission = fixed::gen($value);
                break;
        }
    }
	protected function genButtons($step) {
		if (!count($this->order->items)) {
			$this->next = null;
			$this->prev = null;
			return;
		}

		$i = $this->stepIndex($this->step) - 1;           
		$this->prev = ($i > 0) ? $this->steps[$i-1] : null;
	
		if ($this->step == 'Review')
			$this->next = "Commit"; else
			$this->next = ($i < count($this->steps)-1) ? $this->steps[$i + 1] : null;
	}
	protected function stepIndex($step) {
		return array_search($step, $this->steps) + 1;            
	}
	protected function checkStep($step) {
        if ($this->order->in_stock) {
	        foreach ($this->steps as $max)
		        if (!v($this->order->cart_states, $max, false)) 
			        break;
        } else {
            $max = "Cart";
            $this->message_type = "error";
            $this->message = et("cart_error_out_of_stock");    
        }
		
		$mi = $this->stepIndex($max);
		$ci = $this->stepIndex($step);    
										 
		return ($mi < $ci) ? $max : true;
	}
	protected function cart_view($content) {
		return view(cart_find_view("Index"), array(
			"ajax" => false,
			"step" => $this->step,
			"steps" => $this->steps,
			"editable" => $this->cart_edit,
			"message" => $this->message,
			"message_type" => $this->message_type,
			"next" => $this->next,
			"prev" => $this->prev,
			"order" => $this->order,
			"content" => $content
		));
	}
	protected function submitStep($curr, $next, $curr_ind, $next_ind) {  
        $error = false;
        
        if ($curr == "Cart") {
            $action = $this->input->post("action");
            if ($action == "remove") {
                $index = $this->input->post("index");
                $this->order->remItem($index, true);
            } else
            if ($action == "update") {
                $index = $this->input->post("index");
                $name = $this->input->post("name");
                $value = $this->input->post("value");
                
                $item = v($this->order->items, $index);
                if (is_numeric($value) && $value >= 0) 
                    $this->update_item($item, $name, $value);
            } 
        }
		if ($curr == 'User') {
			$reg_mode = $this->input->post('reg_mode');
			switch ($reg_mode) {
				case "skip":
					$this->order->reg_mode = "skip";
					$this->order->register = false;
					break;
				case "reg":
					$this->order->reg_mode = "reg";
					$this->order->register = true;
					break;
				case "login":
					$this->order->reg_mode = "login";
					$this->order->register = false;
					break;    
			}
			if ($reg_mode == "login") {
				$user = $this->input->post('username');
				$pass = $this->input->post('password');
				
				if ($user == '' || $pass == '') {
					$error = et('cart_error_fill_required');   
				} else {
					$user = $this->order->login_user($user, $pass);
					if ($user) {
						$this->order->set_user($user); 
						$this->redirect = true;
					} else {
						$error = et('cart_error_login_failed');
					}
				} 
			} else
			if ($reg_mode == "skip") {
				$this->order->email = $this->input->post('email');

				if ($next_ind > $curr_ind && $this->order->email == '' && !$this->cart_edit) {
					$this->order->err_fields["email"] = true;   
					$error = et('cart_error_fill_required');  
				}
			} else {
				$email = $this->input->post("email");
				$username = $this->input->post("username");
				$password = $this->input->post("password");
				$cpassword = $this->input->post("cpassword");
				
				$this->order->email = $email;
				$this->order->reg_username = $username;
				$this->order->reg_password = '';
				
				if ($next_ind > $curr_ind) {
					if ($email == '' || $username == '' || $password == '' || $cpassword == '') {
						$error = et('cart_error_fill_required'); 
						
						if ($email == '') $this->order->err_fields["email"] = true;   
						if ($username == '') $this->order->err_fields["username"] = true;   
						if ($password == '') $this->order->err_fields["password"] = true;   
						if ($cpassword == '') $this->order->err_fields["cpassword"] = true;   
					} else 
					if ($password != $cpassword) {
                        if (!$error)
						    $error = et('cart_error_password_mismatch'); 
                            
						$this->order->err_fields["password"] = true; 
						$this->order->err_fields["cpassword"] = true;  
					} else
					if ($msg = $this->order->validate_user()) {
						$error = et($msg);  ;
					} else {
						$this->order->reg_password = $password;
					}
				}
			}
		}
		
		if ($curr == 'Address') { 
			$del = $this->input->post('del_diff'); 
			$this->order->del_diff = $del;
			
			foreach ($this->payment_fields as $n) {    
				if ($req = ($n[0] == '!')) $n = substr($n, 1);
				
				$this->order->$n = $this->input->post($n);
				if (!$this->cart_edit && $req && $this->order->$n == '') {
					$this->order->err_fields[$n] = true;
					$error = et('cart_error_fill_required');
				}
			}
            $this->order->message = $this->input->post("message");
            $this->order->company = $this->input->post("company");            
            $this->order->company_chk = $this->input->post("company_chk");            
            $this->order->vat_id = $this->input->post("vat_id");
            
            $phone_cfg = config::get("orders.phone");
            if ($phone_cfg) {
                $this->order->phone = $this->input->post("phone");
                if (!$this->order->phone) {
                    $this->order->err_fields["phone"] = true;
                    $error = et('cart_error_fill_required');
                }
                    
                if ($phone_cfg === "code") {
                    $this->order->phone_code = $this->input->post("phone_code");
                    if (!$this->order->phone_code) {
                        $this->order->err_fields["phone_code"] = true;
                        $error = et('cart_error_fill_required');
                    }
                }
            }              
			
			foreach ($this->delivery_fields as $n) {
				if ($req = ($n[0] == '!')) $n = substr($n, 1);
				
				$dn = 'del_' . $n;
				if ($del) {
					$this->order->$dn = $this->input->post($dn);
					if (!$this->cart_edit && $req && $this->order->$dn == '') {
						$error = et('cart_error_fill_required');
						$this->order->err_fields[$dn] = true;
					}
				} else
					$this->order->$dn = $this->order->$n;               
			}
			
			$this->order->accept = $this->input->post('accept');
			if (!$this->order->accept && !$this->cart_edit) {
				$this->order->err_fields["accept"] = true; 
                if (!$error) 
				    $error = et('cart_error_accept_conditions'); 
            }
        }
		if ($curr == 'Payment') {
			$this->order->payment = $this->input->post('payment');  
            $this->order->delivery = $this->input->post('delivery');          
		}
		if ($curr == 'Review') {
			$this->order->promo_code = $this->input->post('promo_code');
		}
        
        return $error;
	}
	protected function render($step, $ajax = false, $show_errors = 0) {
		$this->step = $step;
		$this->show_errors = $show_errors;

		$this->order->on_step("Cart");
		
		$this->genButtons($step);

		return $this->cart_view($this->content);
	}
	protected function onStep($step) {
	}
    protected function authorize_order($hash = '') {
        $hash = preg_replace('/[^a-zA-Z0-9]+/', "", $hash);
        
        $q = $this->db->query("SELECT id FROM `order` WHERE hash = ?", [$hash])->getResult();
        if (!count($q)) 
            return message::load("orders.auth_failed", "error");
            
        $action = kohana::config("orders.authorize.action");
        $state = kohana::config("orders.actions.$action.state");
        $order = order::load($q[0]->id);
        $check = $order->action_valid($action);
        if (!$check["valid"]) 
            return message::load("orders.auth_out_of_stock", "error");
            
        $order->action($action);
        $order->save();  
        
        $variables = $order->build_variables("mail_auth");
        $sections = $order->build_sections("mail_auth");
        
        $variables["invoice"] = buffer::view("Order/Invoice", array('order' => $order, 'media' => 'mail'));      
        
        return message::load("orders." . $state, "info", "site", $variables, $sections);
    } 

	public function Index($step = null, $show_errors = 0) {
		if (!$step || $step == "Index")
			$step = "Cart";

		if (!count($this->order->items)) {
			if ($step != "Cart")
				return redirect()->to("Cart"); 
			
			$this->step = "Cart";
            $this->message = et("cart_empty");

			return $this->render($step, false);
		} else {
			$chk = $this->checkStep($step); 
			if ($chk !== true) {
				return redirect()->to("Cart/$chk/1");    
			}
			$this->onStep($step);

			if ($step == "Review")
				$this->order->finalize();
			
			if ($step == "Cart")
				$bc = $this->bottom_content; else
				$bc = "";
			
			$this->content = view(cart_find_view($step), array(
				"columns" => $this->cart_columns,
				"editable" => $this->cart_edit,
				"order" => $this->order,
				"bottom_content" => $bc,
			));

			return $this->render($step, false, $show_errors);
		}
	} 
    public function Submit($step) {
        $res = new \stdClass();
        
        $this->redirect = false;
        $this->order->err_fields = array();
        $action = $this->input->post("action");
        $next = $this->input->post("next_step");
        $curr_ind = $this->stepIndex($step);
        $next_ind = $this->stepIndex($next);
        
        if (!count($this->order->items)) {
            $res->redirect = url::site("Cart");
            echo json_encode($res);
            
            exit;    
        }

		switch($step){
			case "Cart":
				facebook_api::sendConversion("InitiateCheckout", "Checkout", 0);
				break;
			case "Payment":
				facebook_api::sendConversion("AddPaymentInfo", "Checkout", 0);
				break;
		}

		facebook_api::sendConversion("PageView", "Checkout", 0);
        
        if ($next == "Commit") {
            $this->submitStep($step, $next, $curr_ind, $next_ind);
            
            $url = $this->order->commit();    
            $res->redirect = $url;
            echo json_encode($res);
            
            exit;
        }
        
        $error = $this->submitStep($step, $next, $curr_ind, $next_ind);
        if (!count($this->order->items)) {
            $res->redirect = url::site("Cart");
            echo json_encode($res);
            
            exit;    
        }
        
        $show_errors = false;
        if ($error) {
            if ($next_ind >= $curr_ind) {
                $this->order->cart_states[$step] = false;
                $show_errors = true;
                $next = $step;
                
                $this->message = $error;
                $this->message_type = "error";
            }
        } else {
            if ($next_ind > $curr_ind) 
                $this->order->cart_states[$step] = true;
            
            $this->order->on_submit($step, $curr_ind);
        }
        
        $this->order->last_submit = $step;

		if ($next == "Review") 
			$this->order->finalize();
                       
        $chk = $this->checkStep($next);
        if ($chk !== true)
            $next = $chk;
            
        if (!$this->redirect) {    
			$this->content = view(cart_find_view($next), array(
				"columns" => $this->cart_columns,
				"editable" => $this->cart_edit,
				"order" => $this->order,
			));

            $res->content = $this->render($next, true, $show_errors);
            $res->result = true;
        } else 
            $res->redirect = url::site("Cart/" . $next);    
                
        echo json_encode($res);
    }	
    public function Invoice($id, $code) {
		$this->auto_render = true;
		$order = order_query("@id = $id AND hash = '$code'", 1);
		if (!$order)
			notification::create("Error", t('order_invoice_error'), 'error')->display();
			
		$this->step = "Invoice";
		
		$variables = array(
			'invoice' => view(invoice_find_view(), array('order' => $order, 'mobile' => true)),
			'order_id' => $order->order_id,
			'price' => $order->price("order","all,format"),
		);


		facebook_api::sendConversion("Purchase", "Checkout", $order->order_id);
		        
        $func = array("payment_" . $order->payment, "authorization");
        $auth = is_callable($func) ? call_user_func($func) : false;
		if ($auth && $order->reg_mode != "login")
            $variant = "orders.auth_required"; else
            $variant = "orders.accepted";
            
        $msg = message::load($variant, "info", "site", $variables, $order->build_sections($variant)); 
            
		$this->content = view(cart_find_view("Invoice"), array("order" => $order, "msg" => $msg));
		
		$this->next = '';
		$this->prev = '';

		return $this->render("Invoice", false, 0);
	}
	
    public function CheckCode() {
        $this->auto_render = false;

        if (!count($_POST)) die();
        $code = trim($this->input->post("code"));
        $this->order->promo_code = $code;
        if (!$code)
            die();
        
        $pcode = promo_code::load($code);
        if ($pcode->valid)
			$res = "<span style='color: darkgreen'>Koda sprejeta</span>"; else            
			$res = "<span style='color: darkred'>Koda ni bila sprejeta, poskusite ponovno</span>"; 

        echo $res;
    }    	

}
