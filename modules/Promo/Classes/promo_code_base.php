<?php    
	namespace Sloway;
	
    class promo_code_base {  
        public $valid = true;
		public $applied = false;
		public $code = null;
		public $text = "";
		public $message = "";
        public static function load($text) {
			$res = new promo_code();
			$res->text = $text;
			
            $txt = strtolower($text);
			$lng = lang::$lang;
            $res->code = dbClass::load("promo_code", "SELECT * FROM promo_code WHERE code != '' AND LOWER(code) = '$txt' AND (active = 1 OR active REGEXP '[[:<:]]($lng)[[:>:]]') AND code_count = 0", 1);
            if (!$res->code) {
				$res->valid = false;
				return $res;
			}
            if ($res->code->time_from && time() < $res->code->time_from) $res->valid = false;
            if ($res->code->time_to && time() > $res->code->time_to) $res->valid = false;
            
            return $res;
        }
		public static function reset($order) {
			foreach ($order->items as $item) {
				if ($item instanceof order_group) {
					foreach ($item->items as $sub) {
						$sub->setDiscount("promo", null);
					}    
				} else {
					$item->setDiscount("promo", null);
				}
			}				
		}
        public function affects($item) {
			if (!$this->code) return false;

            if (!$this->code->products && !$this->code->categories && !$this->code->tags) {
                return true;
            }
            
            if ($this->code->products && $item->group_id && flags::get($this->code->products, $item->group_id)) {
                return true;
            }

			if ($this->code->tags && $item->tags) {
				$tids = explode(",", $item->tags);
                foreach ($tids as $tid) {
                    if (flags::get($this->code->tags, $tid)) { 
                        return true;    
                    }
                }
			}     
			
            if ($this->code->categories && $item->categories) {
                $cids = explode(",", $item->categories);
                foreach ($cids as $cid) {
                    if (flags::get($this->code->categories, $cid)) { 
                        return true;    
                    }
                }
            }
            
            return false;
        }
        public function apply($order) {
			if (!$this->code) return;
            if (!$this->valid) return;
			
			$this->applied = false;
            
			$type = $this->code->type;
            if ($type == "shipping") {
                $order->setPrice("shipping", $this->code->value, 0.22);
				$this->applied = true;
            }

            if ($type == "credit_p") {
                $price = $order->price('order','all'); 
                foreach ($order->getPrices() as $name => $ap) 
                    $price = fixed::sub($price, $ap['price']);
                
                $value = -$this->code->value / 100 * $price;    
                $order->setPrice("credit", $value, 0.22);
				$this->applied = true;
            }
			
            if ($type == "credit") {
                $value = -$this->code->value;    
                $order->setPrice("credit", $value, 0.22);
				$this->applied = true;
            }
               
            if ($type == "discount" || $type == "discount_a") {
                foreach ($order->items as $item) {
                    if ($item instanceof order_group) {
                        foreach ($item->items as $sub) {
                            if (!$this->affects($sub)) continue;
                            
							$this->applied = true;
                            $value = $this->code->value / 100;
                            if ($value > $sub->discount)
                                $sub->discount = $value;
                        }    
                    } else {
                        if (!$this->affects($item)) continue;
                        
						$item->setDiscount("promo", $this->code->value / 100, ($type == "discount") ? "max" : "add");
						$this->applied = true;
                    }
                }
            }    
        }
		public function message() {
			if ($this->applied)
				$res = "<span style='color: darkgreen'>" . t("promo_code_accepted") . "</span>"; else            
			if ($this->text) 
				$res = "<span style='color: darkred'>" . t("promo_code_declined") . "</span>"; else
				$res = "";
			
			return $res;
		}
    }                                              
?>
