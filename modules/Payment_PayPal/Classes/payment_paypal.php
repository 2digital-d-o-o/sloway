<?php

namespace Sloway;

class payment_paypal {
    public static function commit($order, $send_mail) {  
        $mode = config::get("orders.paypal.mode", "test");
        $email = config::get("orders.paypal.email_" . $mode);
        
        if ($mode == "live")
            $redirect_url = "https://www.paypal.com/cgi-bin/webscr"; else
            $redirect_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        
        $notify_url = url::site("PayPal/Listener/$order->id");
        $return_url = url::site("Cart/Invoice/$order->id/$order->hash");
        $cancel_url = url::site("Cart/Review");    
          
        $q = array(
            'cmd' => "_cart",
            'notify_url' => $notify_url,
            'return' => $return_url,
            'cancel_return' => $cancel_url,
            'business' => $email,
            
            'upload' => "1", 
            'currency_code' => "EUR",
            'no_note' => 1,
            'no_shipping' => 1,
            'address_override' => '0',
            
            'address1' => $order->street,
            'city' => $order->city,
            'country' => $order->country,
            'first_name' => $order->firstname,
            'last_name' => $order->lastname,
            'zip' => $order->zipcode,
            'invoice' => $order->id,
        );
        
        $i = 1;
        $p = 0;
        foreach ($order->items as $item) {
            $ip = $order->itemPrice($item, 'item', 'discount,tax');
            
            $q["item_name_$i"] = $item->title;
            $q["item_number_$i"] = $item->code;
            $q["amount_$i"] = number_format($ip, 2, ".", "");
            $q["quantity_$i"] = $item->quantity;
            
            $p+= $item->quantity * $ip;
            $i++;
        }
        
        foreach ($order->getPrices() as $name => $ip) {  
            $ip = $ip['price'];        
            
            $q["item_name_$i"] = t("add_price_" . $name);
            $q["amount_$i"] = number_format($ip, 2, ".", "");
            $q["quantity_$i"] = 1;
            $i++;
        }
        
        $p = "?" . http_build_query($q);
        
        $order->paypal_request = json_encode($q);
        $order->save();
        
        // log::info("paypal", "commit", $order->id, d($q) . $redirect_url . $p);
        
        return $redirect_url . $p;
    }    
}