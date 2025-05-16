<?php

namespace Sloway;

class payment_bankwire {
    public static function authorization() {
        return false;    
    }
	public static function commit($order, $send_mail) { 
/*		if ($order->reg_mode != 'login') {
            $order->wait_authorization();
			
			if ($send_mail)
				$order->send_mail('auth_mail');
		} else {*/
			$order->action("accept", false);
            
			if ($send_mail)
				$order->send_mail('accepted');
		// }   
	}
}