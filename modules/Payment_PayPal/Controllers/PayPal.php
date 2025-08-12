<?php

namespace Sloway\Controllers;

use Sloway\utils;
use Sloway\config;
use Sloway\url;
use Sloway\dbModel;
use Sloway\order;

class PayPal extends \App\Controllers\BaseController
{
    protected function get_raw_post()
    {
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);

        $myPost = array();
        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2)
                $myPost[$keyval[0]] = urldecode($keyval[1]);
        }

        $req = 'cmd=_notify-validate';
        if (function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;
        }

        foreach ($myPost as $key => $value) {
            $req .= "&" . urlencode($key) . "=" . urlencode($value);
        }

        return $req;
    }

    public function Listener($order_id)
    {
        $raw = $this->get_raw_post();
        // log::info("paypal", "ipn", $order_id, d($_POST));

        $order = order_load($order_id);
        if (!$order) exit();

        $order->paypal_ipn = json_encode($_POST);
        $order->save();

        $mode = config::get("orders.paypal.mode", "test");
        $email = config::get("orders.paypal.email_" . $mode);
        $status = config::get("orders.paypal.state");
        $action = config::get("orders.paypal.action");

        if ($mode == "live")
            $url = "https://ipnpb.paypal.com/cgi-bin/webscr";
        else
            $url = "https://ipnpb.sandbox.paypal.com/cgi-bin/webscr";

        $price = number_format($order->price("order", "all"), 2, ".", ",");

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $raw);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

        $res = curl_exec($ch);
        $err = curl_error($ch);

        curl_close($ch);

        if ($err) {
            $order->paypal_error = $err;
            $order->save();
            //  Cannot validate IPN, force resend
            header('HTTP/1.0 404 Not Found');
            exit();
        }

        //  Invalid IPN Message
        if (strcmp($res, "VERIFIED")) {
            $order->paypal_error = "Invalid IPN Message";
            $order->save();
            exit();
        }

        /*            
        $p_email = $_POST['receiver_email'];
        $p_price = $_POST['mc_gross'];
        $p_trans_id = $_POST['txn_id'];
        $p_status = $_POST['payment_status'];
                
        if ($order->pp_trans_id == $p_trans_id) {
            log::info("paypal", "ipn", $order_id, "Duplicate IPN Message ($order->pp_trans_id:$p_trans_id)");   
            return;
        }
        //if ($p_email != $email) 
          //  return $this->mark_order($order, "INVALID", "Invalid merchant email ($p_email:$email)", $p_trans_id); 
        
        //if ($p_price != $price)
            //return $this->mark_order($order, "INVALID", "Invalid price ($p_price:$price)", $p_trans_id); 
        */

        $order->paypal_response = $res;
        $order->paypal_date = utils::mysql_datetime(time());

        $order->save();

        if ($order->status != "confirmed")
            $order->action("confirm");
    }
}
