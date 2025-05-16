<?php

namespace Sloway;
use Sloway\fixed;
use Sloway\message;

class order_base extends dbClass {
    public static $class_order = "order";
    public static $class_item = "order_item";
    public static $class_group = "order_group";

    public static $view_item = "Cart/Item";
              
    public static $user_login;
    public static $cart_steps;
    
    public static $payment_methods;
    public static $payment_default;
    public static $delivery_methods;

    public static $login_methods;
    public static $login_default;
    
    public static $payment_fields;
    public static $delivery_fields;
    
    public static $countries;
    public static $country_default;
    public static $reservations;
    
    public static $accept_conditions = array();
    public static $cart_info = "";

	public static $db;
                
    public $in_stock = true;
    public $group_count = 0;
    public $user = null;
    public $items = array();
    
    public $err_fields = array();
    public $cart_states = array();
    public $cart_finalized = false;

    public $register = false;
    public $action_log = array();
    
    protected function compareGroup($g1, $g2) {
        $k1 = array();
        $k2 = array();
        
        foreach ($g1->items as $i) $k1[] = $i->id_ref;
        foreach ($g2->items as $i) $k2[] = $i->id_ref;
        
        $d = array_diff($k1, $k2);
        return (count($d) == 0);
    }    
    protected function compareItem($i1, $i2) {
        $f1 = explode(",", $i1->flags);
        $f2 = explode(",", $i2->flags);
        
        return ($i1->id_ref == $i2->id_ref && $i1->price == $i2->price && !count(array_diff($f1,$f2)));
    }
    
    protected static function stock_mul($stock, $mul) {
        if (!is_array($stock)) $stock = explode("/", $stock);
        
        for ($i = 0; $i < count($stock); $i++)
            $stock[$i]*= $mul;
           
        return implode("/", $stock);        
    }
    
    public static function load_group($order, $id) {
        foreach ($order->items as $item) {
            if ($item instanceof order_item) continue;
            
            if ($item->id == $id)
                return $item;
        }
        
        $g = dbClass::load("order_group", "@id = $id", 1, array(), self::$class_group); 
        $order->addGroup($g, false);
        
        return $g;
    }
    public static function load_order($order, $groups = false, $items_sql = null) {
        $sql = "@id_order = $order->id";
        if ($items_sql)
            $sql.= " " . $items_sql;
        
        $items = dbClass::load("order_item", $sql, 0, array(), "\Sloway\order_item");
        foreach ($items as $item)                               
            $order->on_load_item($item);
        
        if ($groups) {
            foreach ($items as $item) {
                if ($item->id_group && ($g = $order->load_group($order, $item->id_group))) {
                    $item->quantity = $item->group_qty;
                    $g->addItem($item);
                } else
                    $order->addItem($item, false);
            }
        } else
            $order->items = $items;
            
        $order->on_load();
        
        return $order;
    }
    
    public static function log($order_id, $action, $details = '', $compress = false) {
        if ($compress && $details)
            $details = gzcompress($details, 6);
        
        $entry = dbClass::create("order_log");
        $entry->id_order = $order_id;           
        $entry->time = time();
        $entry->user = isset(dbClass::$options['edit_user']) ? dbClass::$options['edit_user'] : "";
        $entry->action = $action;
        $entry->details = $details;
        $entry->compressed = intval($compress);
        $entry->save();
        
        return $entry->id;
    }   
    public static function log_update($lid, $action, $details = null, $compress = false) {
        $entry = dbClass::load("order_log", "@id = " . $lid, 1);        
        if (!$entry) return;
        
        if (!is_null($action)) $entry->action = $action;
        if (!is_null($details)) {
            if ($compress && $details)
                $details = gzcompress($details, 6);
                
            $entry->details = $details;
            $entry->compressed = intval($compress);
        }
        
        $entry->save();
    }
    
    public static function content_sections() {
        $r = array();
        foreach (self::$payment_methods as $p) {
            $r["payment_$p"] = t("payment_$p");
        }
        $r["reguser"] = t("Registered User");
        $r["default"] = t("Normal");
        $r["media"] = t("Media");
        
        return $r;
    }
    public static function payment_methods() {
        $r = array();
        foreach (self::$payment_list as $p)
            $r[$p] = t("payment_$p");
        
        return $r;
    }
    public static function invoice_url($order) {
        return url::site("Cart/Invoice/$order->id/$order->hash");     
    }
    public static function clear_db($order) {
        $db = self::$db;
        
        $id = $order->id;
        
        foreach (dbClass::load("order_item", "@id_order = $id") as $item) {
            $order->on_delete_item($item);
            $item->delete();
        }
        foreach (dbClass::load("order_group", "@id_order = $id") as $group) {
            $order->on_delete_group($group);
            $group->delete();
        }
        $db->query("DELETE FROM `files` WHERE module = 'orders' AND module_id = '$id'");
    }
                           
    public function __value($name, $value, $save) {
        if ($name != "prices") return $value;
        
        if ($save) {
            if (is_array($value))
                $res = json_encode($value);
        } else {
            $res = json_decode($value, true);
            if (!$res) $res = array();
        }
        //echod($value, $res); 
        
        return $res;
    }
	public function initialize() {
		return parent::binded("initialize");
	}
    public function commit($id_user = 0, $send_mail = true) {
        $db = self::$db;
        
        self::clear_db($this, $db);
        if (!$id_user && !$this->id_user)
            $this->id_user = $this->user_id();
            
        $this->date = time();
        $this->hash = md5( mt_rand() . microtime(true) . $this->email);
        $this->agree = "YES";
        $this->agree_date = utils::mysql_datetime(time());
        $this->status = "temporary";
        
    //  get id
        if ($this->id) {
            $q = $db->query("SELECT id FROM `order` WHERE id = ?", [$this->id])->getResult();
            if (!count($q))
                $this->id = 0;
        }
        parent::save();
        $this->order_id = $this->order_id();
        
        parent::save();
       
        foreach ($this->items as $item) 
            $item->commit($this, $this->id);
        
        $this->calc_tax_spec();
        $this->on_commit();

        parent::binded("commit", [$id_user, $send_mail]);
        
		$invoice_view = invoice_find_view();
        order::log($this->id, "commit", view($invoice_view, array("order" => $this)), true);

        if ($this->register)
            $this->register_user();
            
        $this->save();    
        
        $order = order_load($this->id);    
        $callback = array("\Sloway\payment_" . $order->payment, "commit");
        if (is_callable($callback))
            $url = call_user_func($callback, $order, $send_mail); else
            $url = null;
        
        if (!$url)
            $url = self::invoice_url($order);
        
        return $url;
    }
    public function finalize() {
        parent::binded("finalize");
        
        $this->clearPrices();
        
        $this->flags = "";
        $this->ac_tags = "";
        $this->categories = "";
        
        foreach ($this->items as $item)
            $item->finalize($this);
        
        $this->flags = flags::unique($this->flags);
        $this->ac_tags = flags::unique($this->ac_tags);
        $this->categories = flags::unique($this->categories);
        
        $this->total = $this->price("order", "all");
    }
    public function wait_authorization() {
        $this->status = "pending";
        foreach ($this->items as $item) {
            $item->status = "pending";
            foreach ($item->items as $sub) {
                $sub->status == "pending";
                $sub->save();
            }    
            $item->save();
        }
        $this->save();
        
        if (order::$reservations) {
            foreach ($this->items as $item)
                $this->update_res($item, null, "pending", $this->id);
        }    
    }
    public function calc_tax_spec() {
        $res = array();                       
        foreach ($this->items as $item) {
            if ($item instanceof order_group) {
                foreach ($item->items as $i) {
                    $tr = fixed::gen($i->tax_rate);
                    if (!isset($res[$tr])) $res[$tr] = 0; 
                    
                    $res[$tr] += $this->itemPrice($i, 'item', 'all');
                }
            } else {
                $tr = fixed::gen($item->tax_rate);
                if (!isset($res[$tr])) $res[$tr] = 0; 
                
                $res[$tr] += $this->itemPrice($item, 'item', 'all');
            }   
        }  
        
        foreach ($this->getPrices() as $p) { 
            $tr = fixed::gen($p['tax_rate']);
            if (!isset($res[$tr])) $res[$tr] = 0; 
            
            $res[$tr] += floatVal($p['price']);
        }       
        
        foreach ($res as $tr => $val) 
            if (!$val) unset($res[$tr]);

        $this->tax_spec = $res;
    }
    public function action_check($action) {
        return true;    
    }
    public function action($action, $send_mail = null) { 
        $this->action_log = array();
        
       
        $ops = config::get("orders.actions.$action");
        /*
        if ($ops["state"] == $this->status) {
            $this->action_log[] = array("type" => "warning", "message" => "order_already_" . $this->status);
            return;
        }
        
        */
        
        $chk = $this->action_check($action);
        if ($chk !== true) {
            $this->action_log[] = array("type" => "warning", "message" => $chk);
            return;
        }
        if (is_null($send_mail))
            $send_mail = v($ops, "send_mail", false); 
            
        $os = $this->status;
        
        if ($this->id_user) {
            $this->confirm_user();                
        }               
            
        if ($action == "finalize") {
            $this->fin_date = time();
        }
        $this->action = $action;
        $this->status = $ops['state'];
        $this->save();
        foreach ($this->items as $item) {
            $item->status = $this->status;
            $item->fin_date = $this->fin_date;
            $item->save();
            
            foreach ($item->items as $sub) {
                $sub->status = $this->status;
                $sub->fin_date = $this->fin_date;
                $sub->save();
            }
        }
        $this->on_action($action);
        $this->on_status($this->status);
        
        $stock_op = v($ops, "stock_op", -1);
		if ($this->stock_op != $stock_op) {
            $this->update_stock($stock_op, $action);                            
		}
        
        order::log($this->id, $action, view(invoice_find_view(), array("order" => $this)), true); 
        
        if ($send_mail) {        
            $lid = $this->send_mail($ops['state']);        
            if ($lid) 
                $this->action_log[] = array("type" => "warning", "message" => "order_mail_error", "lid" => $lid);
        }
    }
    public function action_valid($action) {      
        $result = array(
            "valid" => true,
            "out_of_stock" => array()
        );
        $cfg = config::get("orders.actions.$action");
        $stock_op = v($cfg, "stock_op", -1);
        
        if ($stock_op == $this->stock_op || $stock_op != -1) 
            return $result;
        if (($this->status == 'temporary' || $this->status == 'pending')) {
			$v = $this->validate(false);
			if ($v) 
				return $result;
		}

        foreach ($this->items as $item) {
            $stock = $this->check_item_stock($item->id_ref);
            if ($stock < $item->quantity) {
                $item->stock = $stock;
                $result["valid"] = false;
                $result["out_of_stock"][] = $item;   
            }
        }
        
        return $result;
    }
    public function delete() {
        parent::delete();
        
        foreach ($order->items as $item) 
            $item->delete();
    }     
    public function setPrice($name, $price, $tax_rate) {
        $prices = $this->prices;
        if (!$price)
            unset($prices[$name]); else
            $prices[$name] = array("price" => fixed::gen($price), "tax_rate" => fixed::gen($tax_rate));
        
        $this->prices = $prices;
    }
    public function getPrice($name) {
        return v($this->prices, "$name.price", 0);        
    }  
    public function getPrices() {
        $res = $this->prices;
        if (!is_array($res))
            $res = array();
        
        return $res;
    }  
    public function clearPrices() {
        $this->prices = array();    
    }     
    public function calcDiscount($item) {
		if (is_array($item->_discount) && count($item->_discount)) {
			$res = 0;
		//	CALC MAX DISCOUNT
			foreach ($item->_discount as $d) 
				if ($d["mode"] == "max" && $d["value"] > $res)
					$res = $d["value"];
				
			$res = (1 - $res);
			foreach ($item->_discount as $d) 
				if ($d["mode"] == "add")
				$res*= (1 - $d["value"]);
			
			return 1 - $res;
		} else
			return $item->discount;
    }
    public function groupPrice($group, $type = 'group', $ops = null, $callback = null) {
        $a = explode(',', $ops);
        $price = 0;

        if ($type == 'order' || $type == 'group')
            $type = 'item';
        $ops = str_replace('format','', $ops);
        
        foreach ($group->items as $item) {
            if (is_callable($callback) && call_user_func($callback, $item) === false) continue;
            
            $price += $this->itemPrice($item, $type, $ops . ',quantity');            
        }
        
        $price = $price * $group->quantity;
    
        if (in_array('format', $a)) 
            $price = utils::price($price);

        return $price;            
    }
    public function itemPrice($item, $type = 'item', $ops = null) {
        if ($item instanceof order_group)
            return $this->groupPrice($item, 'group', $ops);
            
        $a = explode(',', $ops);
        if (in_array('all', $a))
            $a = array_merge($a, array('quantity','discount','tax'));
        
        $price = fixed::mul($item->price, 1 + $item->tax_rate, 3); 
        switch ($type) {
            case 'item':
                if (!in_array('tax', $a)) 
                    $price = $item->price;
                    
                if (in_array('discount', $a)) {
                    $d = fixed::mul($this->calcDiscount($item), $price);
                    $price = fixed::sub($price, $d);
                }

                if (in_array('quantity', $a)) 
                    $price = fixed::mul($price, $item->quantity);
                
                if (in_array('format', $a)) 
                    $price = utils::price($price);

                break;
            case 'discount':
                $price = fixed::gen($this->calcDiscount($item));
                
                if (in_array('format', $a)) 
                    $price = utils::price($price * 100, '%');
                    
                break;
            case 'tax':
                if (in_array('discount', $a)) {
                    $d = fixed::mul($this->calcDiscount($item), $price);
                    $price = fixed::sub($price, $d);
                }
                
                $price = fixed::sub($price, fixed::div($price, 1 + $item->tax_rate));
                
                if (in_array('quantity', $a)) $price = fixed::mul($price, $item->quantity);
                if (in_array('format', $a)) 
                    $price = utils::price($price);
                    
                break;
        }
        
        return $price;
    }
    public function price($type = 'order', $ops = '', $callback = null) {
        $a = explode(',', $ops);
        $price = 0;

        if ($type != 'order' && $type != 'tax' && $type != 'discount') 
            $price = v($this->prices, $type . ".price", 0);
        else {
            if ($type == 'order')
                $type = 'item';
            $ops = str_replace('format','', $ops);
            
            foreach ($this->items as $item) {
                if ($item instanceof order_group) {
                    $price = fixed::add($price, $this->groupPrice($item, $type, $ops . ',quantity', $callback)); 
                } else {
                    if (is_callable($callback) && call_user_func($callback, $item) === false) continue;
                
                    $price = fixed::add($price, $this->itemPrice($item, $type, $ops . ',quantity'));            
                }
            }
            foreach ($this->getPrices() as $name => $ap) {
                if (in_array('prices', $a) || in_array('all', $a) || in_array($name, $a)) 
                    $price = fixed::add($price, $ap['price']);    
            }
        }
            
        if (in_array('format', $a)) 
            $price = utils::price($price);

        return $price;            
    }
    public function itemQuantitySelect($item) {
		$max = $item->max_qty;
		if ($max > 100) $max = 100;
        return arrays::fill(1, $max, true);    
    }
    
    public function getItem($id) {
        foreach ($this->items as $item) {
            if ($item instanceof order_group) {
                foreach ($item->items as $sub)
                    if ($sub->id == $id) 
                        return $sub;    
            } else
            if ($item->id == $id)
                return $item;
        }    
        
        return null;
    }    
    public function getGroup($id_ref) {
        foreach ($this->items as $i)
            if ($item instanceof order_group && $item->id_ref == $id_ref)
                return $item;
        
        return false;
    }
    public function add($obj) {
        if ($obj == null) return false;
        
        if ($obj instanceof order_group)
            return $this->addGroup($obj); else
            return $this->addItem($obj);
    }
    public function addItem($item, $merge = true) {  
        if ($item == null) return false;
        
        $this->cart_finalized = false;
        if ($merge) {
        foreach ($this->items as $i) 
            if ($item instanceof order_item && $this->compareItem($item, $i)) 
                return $this->setQuantity($i, $i->quantity + $item->quantity);
        }                   
                
        $this->items[] = $item;
        if (self::$reservations)
            $this->create_res($item);
           
        return true;        
    }
    public function addGroup($group, $merge = true) {
        if ($group == null) return false;
        
        $this->cart_finalized = false;
        
        if ($merge) {
        foreach ($this->items as $item)
            if ($item instanceof order_group && $this->compareGroup($group, $item)) 
                return $this->setQuantity($item, $item->quantity + $group->quantity);
        }
        $this->items[] = $group;
        if (self::$reservations)
            $this->create_res($group);
           
        return true;              
    }
    public function remItem($id_ref, $by_index = false) {
        $this->cart_finalized = false;
        
        if ($by_index) {
            $item = v($this->items, $id_ref, null);
            if ($item) {
                $this->release_res($item);
                unset($this->items[$id_ref]); 
            }
        } else {
        foreach ($this->items as $i => $item)
            if ($item->id_ref == $id_ref) {
                $this->release_res($this->items[$i]);
                unset($this->items[$i]);
            }
        }
        
        $this->clearPrices();
    }   
    public function clear($release = true) {
        $this->cart_finalized = false;
        
        $this->id = 0;
        if ($release)
        foreach ($this->items as $item)
            $this->release_res($item);
            
        $this->flags = "";
        $this->ac_tags = "";
        $this->total = 0;
        $this->categories = "";
        $this->items = array();
        $this->prices = array();
    } 
    public function setQuantity($item, $quantity, $force = false) {
        $this->cart_finalized = false;
        
        if ($quantity > $item->max_quantity) 
            $quantity = $item->max_quantity;

        if (self::$reservations) {
            $this->update_res($item, $quantity, null, null); 
            return $item->quantity == $quantity;    
        }
                              
        $max_qty = min($quantity, $this->check_item_stock($item->id_ref));
        if ($force)
            $item->quantity = $quantity; else
            $item->quantity = $max_qty;
        
        return ($quantity == $max_qty);
    }
    public function quantity($format = false) {
        $q = 0;
        foreach ($this->items as $i)
            $q+= $i->quantity;
        
        if ($format) {
            $c = t("order_products.$q");
                
            return $q . " " . $c;
        } else
            return $q;
    }
    
    public function build_item_sections($variant, $item) {
        return array();
    }
    public function build_sections($variant) {
        $r = array("payment_" . $this->payment);
        foreach ($this->items as $item) {
            if ($item instanceof order_group) {
                foreach ($item->items as $sub) 
                    $r = array_merge($r, $this->build_item_sections($variant, $sub));
            } else 
                $r = array_merge($r, $this->build_item_sections($variant, $item));                    
        }
        
        return array_unique($r);    
    }
    public function build_variables($variant) {
        $variables = array (
            'price' => $this->price('order','all,format'),
            'order_id' => $this->order_id,
            'reference' => $this->reference(),
            'tracking' => $this->tracking_number,
            'confirm_link' => "<a href='" . url::site("Order/Authorize/" . $this->hash) . "'>" . url::site("Order/Authorize/" . $this->hash) . "</a>"
        );
        
        return $variables;
    }
    public function build_css($variant) {
        return array(
            path::gen('root.modules.Orders', 'media/css/invoice.css'),
            path::gen('root.media', 'css/invoice.css')
        );
    }
    public function build_mail($variant) {
        $ops = $this->on_build_mail($variant);
		
		$lang = lang::validate($this->lang, "site");
        
		lang::set_translator(new translator($lang, "site"));
		
        $invoice = view(invoice_find_view(), array('order' => order_load($this->id), 'media' => 'mail'));      
        $sections = $this->build_sections($variant);
        $sections[] = "media_mail";
        
        $variables = $this->build_variables($variant);
        $variables["invoice"] = $invoice;
        
        $mail = message::load("orders." . $variant, null, "mail", $variables, $sections, $lang)->to_mail();
        
        $mail->css = array_merge($mail->css, $this->build_css($variant));
        $mail->from = config::get("email.from");
        $mail->return = config::get("orders.bounce.address");
        $mail->headers = array("Order-ID" => $this->id); 
		
		lang::reset_translator();
        
        return $mail;
    }          
    public function send_mail($action) {  
        $mail = $this->build_mail($action);
        $result = 0;
        if ($mail) {
            $addr = array($this->email); 
            
            $lid = order::log($this->id, "send_mail_" . $action); 
            
            $mail->headers = array(
                "Order-ID" => $this->id,
                "OrderLog-ID" => $lid,
            ); 
            
            $result = 0;
            try {
                $mail->send($addr); 
            } catch (Exception $e) {
                order::log_update($lid, null, $e->getMessage(), true);
                $result = $lid;
            } 
            
            $this->on_mail_send($action, $mail);
        }      
        
        return $result;        
    }
    
    public function has($flags) {
        foreach ($this->items as $item) {
            if (flags::get($item->flags, $flags)) return true;
            foreach ($item->items as $sub)
                if (flags::get($sub->flags, $flags)) return true;
        }
        return false;
    }
    
    public function reference() {
        return $this->order_id;    
    }
    public function order_id() {
        return $this->id . '-' . date('Y', $this->date);
    }
    public function notification($step) {
        return "";
    }

    public function set_user($user) {
        $this->user = $user;
        if ($user)
            $this->accept = true;
        
        $this->cart_states['User'] = false;
        
        parent::binded("set_user", [$user]);
    }
    public function user_id() { 
        return parent::binded("user_id", [], 0);
    }
    public function username() {     
        return parent::binded("username", [], "");
    }
    public function login_user($username, $password) {
        return parent::binded("login_user", [$username, $password]);
    }    
    public function register_user() {
        return parent::binded("register_user");    
    }
    public function validate_user() {
        return parent::binded("validate_user");    
    }
    public function confirm_user() {
        return parent::binded("confirm_user");    
    }
    
    public function validate_item($item) {
        $stock = $this->check_item_stock($item->id_ref);
        if ($item->reservation_id) {
            $in_stock = $this->check_res($item->reservation_id);
            
            if ($in_stock)
                $stock = $stock + $item->quantity;
        } else {
            $in_stock = $stock >= $item->quantity;
        }
        
        $item->max_qty = min($stock, $item->max_quantity);
        $item->in_stock = $in_stock;

        return $in_stock;
    }    
    public function validate($clear = true) {
        $this->in_stock = true;
        
        $valid = true;
        foreach ($this->items as $item) {
            if ($item instanceof order_group) {
                $item->in_stock = true;
                $max = null;
                foreach ($item->items as $sub) {
                    $item_valid = $this->validate_item($sub);
                    $item->in_stock &= $item_valid;
                    
                    $valid&= $item_valid;
                    
                    if (is_null($max) || $sub->max_qty < $max)
                        $max = $sub->max_qty;
                }
                $item->max_qty = $max;
            } else 
                $valid&= $this->validate_item($item);
        }    
        
        $this->in_stock = $valid;
        //if (!$valid && $clear && order::$reservations)
        //    $this->clear();
           
        return $valid; 
    }
    public function update_stock($stock_op, $action) {
        $cfg = config::get("orders.actions");
        $state = "order_" . v($cfg, $action . ".state", $action);
        
        foreach ($this->items as $item) {
			if (self::$reservations) {
				if ($this->check_res($item->reservation_id)) {
					if ($stock_op == -1) {                                               
						$this->update_res($item, null, $state, $this->id); 
					} else {
						$this->release_res($item); 
						$item->save();
					}
				}
            } else {
                $this->update_item_stock($item->id_ref, $stock_op * $item->quantity, $state, $this->id);    
			}
        }        
        $this->stock_op = $stock_op;
        parent::save();
    }
	public function update_item_stock($pid, $amount, $state, $reference = '') {
		return parent::binded("update_item_stock", [$pid, $amount, $state, $reference]);
	}
    public function check_item_stock($pid) {
        return parent::binded("check_item_stock", [$pid], 100);
    }
    
    public function mark_viewed($uid) {
        $v = explode(",", $this->viewed);
        if (!in_array($uid, $v)) {
            $db = self::$db;
            
            $v[] = $uid;
            $v = implode(",", $v);
            $db->query("UPDATE `order` SET viewed = '$v' WHERE id = " . $this->id);
        }
    }
    public function check_vat_id() {
        $this->vat_id_valid = 0;
        $this->vat_included = 1;
        
        if (!$this->vat_id) return;
        
        if ($this->country == "SI") {
            $this->vat_id_valid = vat_id::validate($this->country, $this->vat_id) ? 1 : -1;
            $this->vat_included = 1;            
        } else 
        if (countries::in_eu($this->country)) {
            $this->vat_id_valid = vat_id::validate($this->country, $this->vat_id) ? 1 : -1;
            $this->vat_included = intval($this->vat_id_valid != 1);
        } else {
            $this->vat_included = 0;    
        }
        
        $st = $this->vat_included;
        foreach ($this->items as $item) {
            if ($item instanceof order_group) {
                foreach ($item->items as $sub) {
                    if (is_null($sub->tax_rate_orig))
                        $sub->tax_rate_orig = $sub->tax_rate;
                        
                    $sub->tax_rate = ($st) ? $sub->tax_rate_orig : 0;    
                }
            } else {
                if (is_null($item->tax_rate_orig))
                    $item->tax_rate_orig = $item->tax_rate;
                    
                $item->tax_rate = ($st) ? $item->tax_rate_orig : 0;    
            }
        }  
    }

    public function on_submit($step) {
            
    }
    public function on_load() {
        $this->calc_tax_spec();
    }
    public function on_load_item($item) {
        $p[$this->currency] = array(
            'price' => $item->price,
            'tax_rate' => $item->tax_rate
        );    
        
        $item->prices = $p;    
    }
    public function on_create() {
    }
    public function on_step($step) {
    }
    public function on_action($action) {
    }
    public function on_status($status) { 
    }
    public function on_commit() {
    }    
    public function on_commit_item($item, $group) {
    }
    public function on_commit_group($group) {
    }
    public function on_delete() {
    }    
    public function on_delete_item($item) {
    }
    public function on_delete_group($group) {
    }

    public function on_build_mail($action) {
        return array('template' => 'orders', 'lang' => lang::$def_lang); 
    }
    public function on_mail_send($action, $mail) {
    }

    public function create_res($item, $status = "in_cart") {
        if ($item instanceof order_group) {
            foreach ($item->items as $sub) 
                $sub->reservation_id = $this->reservation_create($sub->id_ref, $sub->quantity * $item->quantity, $status);
        } else
            $item->reservation_id = $this->reservation_create($item->id_ref, $item->quantity, $status);
    }
    public function update_res($item, $quantity, $status, $reference, $force = false) {
        if ($item instanceof order_group) {
            $min = null;
            foreach ($item->items as $sub) {
                $qty = $this->reservation_update($sub->reservation_id, $quantity, $status, $reference, $force);
                if (is_null($min) || $qty < $min)
                    $min = $qty;
            }
            $item->quantity = $min;
        } else {
            $item->quantity = $this->reservation_update($item->reservation_id, $quantity, $status, $reference, $force);
        }
    }
	public function check_res($rid) {	
		return $this->reservation_check($rid);
	}
    public function release_res($item) {
        if (!order::$reservations) return;
        
        if ($item instanceof order_group) {
            foreach ($item->items as $sub) {
                $this->reservation_release($sub->reservation_id);
                $sub->reservation_id = 0;
            }
        } else {
            $this->reservation_release($item->reservation_id);
            $item->reservation_id = 0;
        }
    }

    public function reservation_create($pid, $quantity) {
        return parent::binded("reservation_create", [$pid, $quantity]);
    }
    public function reservation_update($rid, $quantity, $status, $reference) {
        return parent::binded("reservation_update", [$rid, $quantity, $status, $reference]);
    }
    public function reservation_check($rid) {
        return parent::binded("reservation_check", [$rid], false);
    }
    public function reservation_release($rid) {
        return parent::binded("reservation_release", [$rid]);
    }
}


order_base::$class_order = config::get("orders.class.order", "\Sloway\order");
order_base::$class_group = config::get("orders.class.group", "\Sloway\order_group");
order_base::$class_item = config::get("orders.class.item", "\Sloway\order_item");
                                       
order_base::$user_login = config::get("orders.user_login");    
order_base::$cart_steps = config::get("orders.steps", array('Cart', 'User', 'Address', 'Payment', 'Review', 'Invoice'));

order_base::$payment_fields = config::get("orders.fields.payment");    
order_base::$delivery_fields = config::get("orders.fields.delivery");    
order_base::$countries = config::get("orders.countries.list"); 
order_base::$country_default = config::get("orders.countries.default");

order_base::$reservations = config::get("orders.reservations", false);
order_base::$login_methods = config::get("orders.login.methods", array("new_user"));
order_base::$login_default = config::get("orders.login.default", "new_user");
order_base::$payment_methods = config::get("orders.payment.methods");
order_base::$payment_default = config::get("orders.payment.default");
order_base::$delivery_methods = config::get("orders.delivery.methods");
