<?php

namespace Sloway;

class payment_leanpay {
    public static function authorization() {
        return false;    
    }
	public static function commit($order, $send_mail) {
        $order->leanpay_trans_id = $order->id . "-" . time();
        $order->save();
      
        $price = $order->price('order','all');
        $params = array(
            "vendorApiKey" => config::get("orders.leanpay.api_key"),
            "vendorTransactionId" => $order->leanpay_trans_id,
            "amount" => $price,
            "successUrl" => url::site("LeanPay/Success/" . $order->id),
            "statusUrl" => url::site("LeanPay/Status"),
            "errorUrl" => url::site("Cart/Review"),
            "vendorPhoneNumber" => $order->phone,
            "vendorFirstName" => $order->firstname,
            "vendorLastName" => $order->lastname,
            "vendorAddress" => $order->street,
            "vendorZip" => $order->zipcode,
            "vendorCity" => $order->city,
            "language" => "sl"
        );
        $params = json_encode($params);
        $ch = curl_init();

		$mode = config::get("orders.leanpay.mode");
		if ($mode == "test") 
			$url = "https://stage-app.leanpay.si/"; else
			$url = "https://app.leanpay.si/";

        curl_setopt($ch, CURLOPT_URL, $url . "vendor/token");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $chr = curl_exec($ch);
        curl_close($ch);   
        
        $res = json_decode($chr, true);    
        
        return $url . "vendor/checkout?token=" . $res["token"];
	}
}
