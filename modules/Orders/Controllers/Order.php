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

class Order extends \App\Controllers\BaseController {
    protected function authorize_order($hash = '') {
        $hash = preg_replace('/[^a-zA-Z0-9]+/', "", $hash);
        
        $q = $this->db->query("SELECT id FROM `order` WHERE hash = ?", [$hash])->getResult();
        if (!count($q)) 
            return message::load("orders.auth_failed", "error");
            
        $action = config::get("orders.authorize.action");
        $state = config::get("orders.actions.$action.state");
        $order = order_load($q[0]->id);
        $check = $order->action_valid($action);
        if (!$check["valid"]) 
            return message::load("orders.auth_out_of_stock", "error");
            
        $order->action($action);
        $order->save();  
        
        $variables = $order->build_variables("mail_auth");
        $sections = $order->build_sections("mail_auth");
        
        $variables["invoice"] = view(invoice_find_view(), array('order' => $order, 'media' => 'mail'));      
        
        return message::load("orders." . $state, "info", "site", $variables, $sections);
    } 
  
}
