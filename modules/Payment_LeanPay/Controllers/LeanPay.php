<?php 

namespace Sloway\Controllers;
use Sloway\order;
use Sloway\url;

// defined('SYSPATH') OR die('No direct access allowed.');

class LeanPay extends \App\Controllers\BaseController {
	public function Success($id) {
		$order = order::load("order", "@id=".$id."", 1);
		$order->action("accept", true);  

		$url = url::site("Cart/Invoice/" . $order->id . "/" . $order->hash);
		
		return $url;
	}

	public function Status() {
        $input = file_get_contents('php://input');
        $data = json_decode($input);
        log::write("info", "leanpay", "json", "", d($data));
        
        $trans_id = $data->vendorTransactionId;
        $status = $data->status;
        
        $order = order::query("@leanpay_trans_id = '$trans_id'", 1);
        if ($order) {
			if ($order->status == "temporary")
				$order->action("accept", true);
			
            $order->leanpay_response = $input;
            $order->leanpay_status = $status;
            $order->save();
        }
	}

	public function Test() {
		echo "test";
	}
}
