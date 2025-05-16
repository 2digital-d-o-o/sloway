<?php 

namespace Sloway\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sloway\path;
use Sloway\config;
use Sloway\url;
use Sloway\arrays;
use Sloway\admin;
use Sloway\mlClass;
use Sloway\dbClass;
use Sloway\genClass;
use Sloway\settings;
use Sloway\images;
use Sloway\files;
use Sloway\acontrol;
use Sloway\dbModel;
use Sloway\userdata;
use Sloway\order;
use Sloway\order_query;
use Sloway\fixed;
use Sloway\lang;
use Sloway\utils;
use Sloway\message; 

class AdminOrders extends AdminController {
	public $module = 'orders';
	public $module_tabs = array();
	public $def_tax_rate = 0.22;
    public $def_search_fields = array("order_id", "firstname", "lastname", "street", "email", "zipcode", "city");
    
    protected function model($type) {
        $model = array(
            array('id' => 'id', 'content' => et('Order ID'), 'sort' => true, 'search' => true),
            array('id' => 'receipt_id', 'content' => t('Receipt ID'), 'sort' => true, 'search' => true),
            array('id' => 'email', 'content' => et('E-mail'), 'sort' => true, 'search' => true),
            array('id' => 'name', 'content' => et('Name'), 'sort' => true, 'search' => true),
            array('id' => 'address', 'content' => et('Address'), 'sort' => true, 'search' => true),
            array('id' => 'payment', 'content' => et('Payment')),
            array('id' => 'date', 'content' => et('Date'), 'sort' => true),
            array('id' => 'price', 'content' => et("Price")),
            array('id' => 'terms', 'content' => et("GDPR")),
            array('id' => 'menu', 'content' => '', 'align' => 'right', 'width' => 200, 'fixed' => true),
        );  
        
        return $model;
    }

	protected function check_sort($name, $model) {
		$default = null;
		foreach ($model as $ops) {
			if (is_null($default)) $default = $ops["id"];
			if ($ops["id"] == $name && $ops["sort"]) return $name;
		}

		return $default;
	}    
	protected function sort_config(&$sort, &$sort_dir) {
        switch ($sort) {
            case "id":
                $sort_dir*=-1;
                break;
            case "email":
                break;
            case "name":
                $sort = "firstname";
                break;
            case "address":
                $sort = "street";
                break;
            case "date":
                $sort_dir*=-1; 
                break;
            case "price":    
                break;
        }
    }
    protected function sql($sql) {
        return $sql;
    }
    protected function orders_sql($status, $filter) {
        if ($status == "nonauth")
            $sql = array("status = 'pending'"); else
            $sql = array("status = '$status'");
            
        if ($filter->search) {            
            $terms = $filter->search;
            $fields = "order_id,email,firstname,lastname,street,zipcode,city";
            
            $sql[]= "CONCAT(" . trim($fields, ",") . ") REGEXP '($terms)'";
        }                                                                                           
        if ($filter->from)
            $sql[]= "date >= " . $filter->from;
        if ($filter->to)
            $sql[] = "date <= " . $filter->to;
        if ($flags = $filter->flags)
            $sql[]= "flags REGEXP '[[:<:]](" . str_replace(",", "|", $flags) . ")[[:>:]]'";
        if ($cats = $filter->cats)
            $sql[]= "categories REGEXP '[[:<:]](" . str_replace(",", "|", $cats) . ")[[:>:]]'";
            
        return $sql;
    } 
    protected function stats_sql($status) {
        if ($status == "nonauth")
            return "status = 'pending'"; else
            return "status = '$status'";
    }
    protected function order_row($model, $order) {
        $res = array();
        foreach ($model as $column)
            $res[] = $this->order_cell($column['id'], $order);
        
        return $res;
    }
    protected function order_cell($name, $order) {
        switch ($name) {
            case "id":
                $url = url::site("AdminOrders/View/" . $order->id);
                $cls = \Sloway\flags::get($order->viewed, $this->admin_user->id) ? "admin_order_viewed" : "";
                
                $res = "<a href='$url' onclick='return admin_redirect(this)' class='$cls'>$order->order_id</a>";
				$res.= "&nbsp;<span class='admin_lang_button' data-lang='$order->lang'>" . et("lang_abbr_" . $order->lang) . "</span>";
				return $res;
            case "receipt_id":
                return $order->receipt_id;
            case "email":
                if ($order->bounced)
                    return "<span style='color: darkred'>$order->email</span>"; else
                    return $order->email;
            case "name":
                return $order->firstname . " " . $order->lastname;
            case "address":
                return $order->street . ", " . $order->zipcode . " " . $order->city . "," . \Sloway\countries::title($order->country);
            case "payment":
                return et("payment_" . $order->payment);
            case "date": 
                return \Sloway\utils::date($order->date) . ", " . \Sloway\utils::time($order->date, ":", false);                
            case "price":
                return $order->price('order','all,format');
            case "terms":
                return ($order->terms_agree) ? \Sloway\utils::datetime(strtotime($order->terms_agree_date)) : "";
            case "menu": 
                $r = "";
                
                if (Admin::auth("orders.resend"))
                    $r.= Admin::ButtonI("icon-email.png", null, t("Resend e-mail"), "orders_resend(\$(this))", "data-id='$order->id'");
                
                $r.= Admin::ButtonI("icon-edit.png", "ajax:" . url::site("AdminOrders/View/$order->id"));   

                foreach (v($this->states, $order->status . ".actions", array()) as $action) {
                    $action_title = t("order_action_$action");
                
                    $callback = v($this->actions[$action], "callback", null);
                    if (!$callback) 
                        $callback = "orders_action(\$(this))";
                        
                    $r.= Admin::ButtonI($this->actions[$action]['icon'], null, $action_title, $callback, "data-id='$order->id' data-action='$action'");
                }
                
                if (v($this->states, $order->status . ".print", false)) 
                    $r.= Admin::ButtonI("icon-print.png", null, t('Print'), "orders_print(\$(this))", "data-id='$order->id'"); 
                
                if (Admin::auth("ordes.log"))
                    $r.= Admin::ButtonI("icon-log.png", null, t('Log'), "orders_log(\$(this))", "data-id='$order->id'");
                    
                return $r;
            default: 
                return "";
        }    
    } 
    
    protected function categories() {
        return array();    
    }
    protected function action_check($orders, $action) {
        $res = new \stdClass();
        $res->count = 0;
        $res->log = array();
        $res->valid = array();
        
        foreach ($orders as $order) {
            $actions = config::get("orders.states.{$order->status}.actions", array());
            if (in_array($action, $actions)) {
                $res->log[$order->order_id] = array("type" => "", "message" => et("order_status_" . $order->status));
                $res->count++;
                $res->valid[]= $order;
            } else
                $res->log[$order->order_id] = array("type" => "failure", "message" => et("order_status_" . $order->status)); 
        } 
        
        return $res;
    }
    protected function action($orders, $action) {
        $res = new \stdClass();
        $res->log = array();
        $res->count = 0;
        $res->error = false;
        $res->valid = array();
        
        $translator = lang::$translator;
        
        foreach ($orders as $order) {
            $actions = config::get("orders.states.{$order->status}.actions", array());
            if (in_array($action, $actions)) {
                $order->action($action);
                if (!count($order->action_log)) {
                    $res->log[$order->order_id] = array(array("type" => "success", "message" => "order_status_" . $order->status));
                    $res->count++;
                    $res->valid[]= $order;
                } else {
                    $res->log[$order->order_id] = $order->action_log;
                    $res->error = true;
                }
            } else {
                $res->log[$order->order_id] = array(array("type" => "failure", "message" => "order_status_" . $order->status));
                $res->error = true;
            }
        } 
        
        lang::$translator = $translator;
        foreach ($res->log as $oid => $log)
            foreach ($log as $i => $entry)
                $res->log[$oid][$i]["message"] = et($entry["message"]);
        
        return $res;
    }
    protected function query($ids, $status, $complete = true) {
        if ($status == "nonauth")
            $result = "(o.status = 'temporary' OR o.status = 'pending')"; else
            $result = "o.status = '$status'";
        
        if (empty($ids)) return false;
        if (strpos($ids, "all") === 0) {
            $ids = trim(str_replace("all", "", $ids), ",");
            if ($ids)
                $result.= " AND o.id NOT IN ($ids)";
        } else
            $result.= " AND o.id IN($ids)";
        
        if ($complete)
            $result = "SELECT * FROM `order` as o WHERE " . $result;
            
        return $result;           
    }
    protected function header_menu($type) {
        return Admin::ButtonS(et("Export"), false, "right", false, "onclick='orders_export()' id='orders_export' disabled=1");    
    }
    protected function footer($type) {
        $res = "";
        foreach (v($this->states, $type . ".actions", array()) as $action) {
            $action_title = t("order_action_$action.mul");
            
            $callback = v($this->actions[$action], "callback", null);
            if (!$callback) 
                $callback = "orders_action(\$(this))";
                
            $res.= Admin::ButtonI($this->actions[$action]['icon'], null, $action_title, $callback, "data-action='$action'"); 
        }
        if (v($this->states, $type . ".print", false))
            $res.= Admin::ButtonI("icon-print.png", null, t('Print'), "orders_print(\$(this))");  
            
        return $res;
    }
	protected function generate_tabs() {
        foreach ($this->visible_states as $state) {
			$q = $this->db->query("SELECT COUNT(id) AS cnt FROM `order` WHERE status = '$state'")->getResult();
			$cnt = $q[0]->cnt;
            
			$url = url::site("AdminOrders/Index/$state");
			$tab = "<a href='$url' onclick='return admin_redirect(this)'>";
			$tab.= et("order_status_$state.mul");
			$tab.= "<span class='admin_orders_count'>($cnt)</span>"; 
			$tab.= "</a>";
			
			$this->module_tabs["status_" . $state] = $tab;
        } 

		if (Admin::auth("orders.nonauth")) {
			$q = $this->db->query("SELECT COUNT(id) AS cnt FROM `order` WHERE status = 'pending'")->getResult();
			$cnt = $q[0]->cnt;

			$url = url::site("AdminOrders/Index/nonauth");
			$tab = "<a href='$url' onclick='return admin_redirect(this)'>";
			$tab.= et("order_status_nonauth.mul");
			$tab.= "<span class='admin_orders_count'>($cnt)</span>"; 
			$tab.= "</a>";
			
			$this->module_tabs["status_nonauth"] = $tab;		
		}
	}
	
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)	{
		parent::initController($request, $response, $logger);
		
        $this->actions = config::get('orders.actions');
		$this->states = array();
		$this->visible_states = array();
        foreach (config::get('orders.states') as $name => $ops) {
            if (!Admin::auth("orders.$name")) continue;    
            
            $actions = v($ops, "actions", array());
            foreach ($actions as $i => $action) {
                if (!Admin::auth("orders.$name.$action")) 
                    unset($actions[$i]);
            }
            $ops["actions"] = $actions;
            $this->states[$name] = $ops;

			if (v($ops, "visible", 0))
				$this->visible_states[]= $name;
        }
		$this->module_wide = true;

        /*
		if (Admin::auth("orders.nonauth")) {
            $this->tabs["status_nonauth"] = array("title" => et("Nonauthorized"), "url" => url::site("AdminOrders/Index/nonauth"));
            $this->tab_stats["status_nonauth"] = 0;
        }

        if (Admin::auth("orders.analytics")) 
            $this->tabs["analytics"] = array("title" => et("Analytics"), "url" => url::site("AdminOrders/Analytics"));   
		 * 
		 */

	} 
      
	public function Index($type = null) {
		if (is_null($type))
			$type = arrays::first($this->visible_states);

		Admin::auth("orders." . $type, $this);

		$this->generate_tabs();
        
		$this->type = $type;
        $this->categories = $this->categories();
        $this->flags = config::get("orders.flags", array());
        $this->filter = userdata::get_object("orders_filter_", array("search", "from", "to", "cats", "flags"));
		
		if (!$type)
			$type = arrays::first(arrays::filter($this->states, 'return v($val, "visible", false);', true), true);
		
        $this->model = $this->model($type);
        $this->footer = $this->footer($type);
		//echod($this->module_tabs);

        $this->module_menu = view("\Sloway\Views\AdminOrders\OrdersMenu", array(
			"flags" => $this->flags,
			"filter" => $this->filter,
		));
		$this->module_path = array(et("order_status_$this->type.mul"));
        $this->module_content = Admin::tabs($this->module_tabs, "status_$type", view("\Sloway\Views\AdminOrders\Orders", array(
			"status" => $this->type,
			"header_menu" => $this->header_menu($this->type),
			"dg_model" => $this->model,
			"dg_footer" => $this->footer,
		)));

		return $this->admin_view();
	}
	public function Ajax_OrdersHandler($status) {
        $model = $this->model($status);
		$action = $this->input->post("action");
		if ($action) {
			$query = $this->query($this->input->post("ids"), $status);
			if ($query)
				$this->action($query, $action); 	 
		}
        
        if ($this->input->post("filter")) {
            $filter = new \stdClass();
            $filter->search = trim($this->input->post("filter_search"));
            $filter->from = strtotime($this->input->post("filter_from"));
            $filter->to = strtotime($this->input->post("filter_to"));
            $filter->cats = $this->input->post("filter_cats");
            $filter->flags = $this->input->post("filter_flags");
            
            userdata::set_object("orders_filter_", $filter);        
        } else 
            $filter = userdata::get_object("orders_filter_", array("search", "from", "to",  "cats", "flags"));
            
		$page = $this->input->post("page", 1) - 1;
		$perpage = $this->input->post("per_page", 20);
		$sort = $this->check_sort($this->input->post("sort", "id"), $model);
		$sort_dir = $this->input->post("sort_dir", 1); 
		
		$result_state = array(
			"page" => $page + 1, 
			"sort" => $sort,
            "per_page" => $perpage,
			"sort_dir" => $sort_dir,   
		);
        $sql = $this->orders_sql($status, $filter);
        $dbg_sql = $sql;
        
        $this->sort_config($sort, $sort_dir);
        if (!is_array($sort))
            $sort = array($sort);
        
        $order = " ORDER BY ";
        foreach ($sort as $i => $s) {
            if ($i) $order.= ", ";
            $order.= $s . (($sort_dir > 0) ? ' ASC' : ' DESC'); 
        }
        
		$sql1 = "SELECT COUNT(id) AS cnt FROM `order`";
		if (count($sql))
			$sql1.= " WHERE " . implode(" AND ", $sql);
			
		$q = $this->db->query($sql1)->getResult();
		$count = $q[0]->cnt;
        $result_state["total"] = $count;
		
		$start = $page * $perpage;
        if ($start >= $count) {
            $result_state['page'] = 1;
            $start = 0;   
        } 
            
		$orders = order_query("@" . implode(" AND ", $sql) . $order . " LIMIT $start,$perpage");
		
/*
		$stats = array();
		foreach ($this->states as $state => $ops) 
			$stats[$state] = $this->db->count("SELECT COUNT(id) as cnt FROM `order` WHERE " . $this->stats_sql($state));
		
		$stats["nonauth"] = $this->db->count("SELECT COUNT(id) as cnt FROM `order` WHERE " . $this->stats_sql("nonauth"));
 * 
 */
		
		$result = array(
			"rows" => array(),
			"state" => $result_state,
			"data" => array(
		       // "stats" => $stats
			),
            "sql" => $sql,
            "filter" => $filter
		);    
		foreach ($orders as $order) {  
			$res = array(
				"id"   => $order->id,
				"cells" => $this->order_row($model, $order)
			);
				
			$result["rows"][] = $res;
		}
		
		$this->auto_render = false;
		echo json_encode($result);     
	} 
    public function Export($status) {
        $this->auto_render = false;
        
        $filter = userdata::get_object("orders_filter_", array("from", "to",  "cats", "flags"));
        $sql = $this->orders_sql($status, $filter);
        
        $lines = array();        
        foreach ($this->db->query("SELECT * FROM `order` WHERE " . implode(" AND ", $sql)) as $order) {
            $lines[] = array(
                $order->order_id,
                utils::date_time($order->date),
                $order->firstname . " " . $order->lastname,
            );            
            
            foreach ($this->db->query("SELECT * FROM `order_item` WHERE id_order = ?", $order->id) as $item) {
                $lines[] = array(
                    "", 
                    $item->code,
                    $item->title                
                );
            }
        }
        
        excel::output($lines, "Orders");
    }    
	public function Ajax_Action($action) {
		$this->auto_render = false;
        $res = array();
        
        $ids = $this->input->post("ids");
        $cnt = count(explode(",", $ids));
        $res['title'] = et("order_action_$action") . " " . et("order_mul." . $cnt);
        
        if ($this->input->post("confirm")) {
            $report = $this->action(order_query("SELECT * FROM `order` WHERE id IN ($ids)"), $action);
            
            $content = view("\Sloway\Views\AdminOrders\ActionReport", array("report" => $report, "action" =>  $action));

            $res['content'] = $content;
            $res['action_end'] = true;
            $res['buttons'] = array("ok" => array("align" => "right"));
        } else {
            $report = $this->action_check(order_query("SELECT * FROM `order` WHERE id IN ($ids)"), $action);
            
            $content = "<input type='hidden' name='ids' value='$ids'>";
            $content.= view("\Sloway\Views\AdminOrders\ActionConfirm", array("report" => $report, "action" => $action));
            
            if ($report->count)
                $res['buttons'] = array("confirm" => array("title" => t("order_action_$action"), "submit" => true, "key" => 13), "cancel" => array("title" => t("Back"))); else
                $res['buttons'] = array("cancel");
            $res['content'] = $content;
        }
        echo json_encode($res); 
	} 
    public function Ajax_Resend($id) {
        $order = order_load($id);
        
        $res = array();
        $action = $this->input->post("action");
        if ($this->input->post("send") && $action) {
            $lid = $order->send_mail($action);
            if (!$lid)
                $c = "<div class='admin_message success'>" . et("E-mail was successfuly sent") . "</div>"; else
                $c = "<div class='admin_message failure'>" . et("E-mail could not be sent") . "</div>";
            
            $res['title'] = et("Resend e-mail");
            $res['content'] = $c;
            $res['buttons'] = array('ok' => array("align" => "right", "close" => true));  
            
        } else {
            $vars = array();    
            foreach (config::get("messages.orders.variations") as $var)    
                $vars[$var] = t("messages_orders_" . $var);
            
            $res['title'] = et("Resend e-mail");
            $res['content'] = Admin::Field("Message type", acontrol::select("action", $vars, $order->action));
            $res['buttons'] = array('send' => array("align" => "left", "title" => "OK", "submit" => true), 'cancel');
        }
        
        echo json_encode($res);
    }   
	                                                 
	public function View($id, $ajax = false) {
		$order = order_load($id);
		$this->generate_tabs();

		//$uid = $this->admin_user->id;
		//$this->db->query("UPDATE `order` SET viewed = TRIM(BOTH ',' FROM CONCAT(viewed, ',$uid')) WHERE id = $id");

        $status = $order->status;
        if ($status == "pending" || $status == "temporary")
		    $tabs_curr = "status_nonauth"; else
            $tabs_curr = "status_" . $status; 

		$actions = array();
		foreach (v($this->states, $order->status . ".actions", array()) as $act) {
			$callback = v($this->actions[$act], "callback", "orders_action(\$(this)); return false");   
			$actions[$act] = $callback;
		}

		$this->module_path = array($order->order_id);
        $this->module_content = Admin::tabs($this->module_tabs, $tabs_curr, view("\Sloway\Views\AdminOrders\View", array(
			"status" => $status,
			"order" => $order,
			"actions" => $actions,
			"invoice" => view(invoice_find_view(), array("order" => $order)),
		)));
		return $this->admin_view();
	}

    public function PrintOrders() {
        $ids = utils::explode(",", $this->input->get("ids"));
        
        $styles = array();
        $styles[]= path::gen('site.modules.Orders', 'media/css/invoice.css');
        $styles[]= path::gen('site.modules.Core', 'media/css/messages.css');

        if (file_exists(path::gen('root.media', 'css/invoice.css')))
            $styles[]= path::gen('site.media', 'css/invoice.css');        
        if (file_exists(path::gen('root.media', 'css/messages.css')))
            $styles[]= path::gen('site.media', 'css/messages.css');        

        
        $title = "";
        $content = "";
        
        foreach ($ids as $i => $id) {
            if ($i)
                $content.= "<div class='page_break'></div>";

            $order = order_load($id);  
            
            $invoice = view(invoice_find_view(), array("order" => $order));  
            $message = message::load("orders.print", null, "print", array("invoice" => $invoice));
            
            $content.= $message->content;
        }

		return view("\Sloway\Views\Order\Print", array("title" => $title, "content" => $content, "styles" => $styles));
    }   
	public function Ajax_EditArticle() {
		$this->auto_render = false;
		
		$code = $this->input->post("code", "");
		$price = $this->input->post("price", "");
		$quantity = $this->input->post("quantity", "");
		
		$msg = false;
		if ($this->input->post("submit") && $code) {
			$prod = dbModel::load("catalog_product", "@code = '$code' AND ((type = 'group' AND type_id = 0) OR (type = 'item'))", 1);
			if ($prod) {
				$res['close'] = true;
				$res['result'] = array(
					"id_ref" => $prod->id,
					"code" => $code,
					"price" => $price,
					"title" => $prod->title,
					"quantity" => $quantity,
				);
				echo json_encode($res);    
				exit();
			} else
				$msg = et("Article '$code' not found");
		}
		
		$c = "";
		if ($msg)
			$c.= "<p style='color: darkred; padding: 5px 2px'><strong>$msg</strong></p>";        		
			
		$c.= Admin::Field(et("Article code"), advcontrols::edit("code", $code));
		$c.= Admin::Field(et("Quantity"), advcontrols::edit("quantity", $quantity));
		$c.= Admin::Field(et("Price"), advcontrols::edit("price", $price));
		
		$res['title'] = ($code) ? et("Edit item") : et("Add item"); 
		$res['content'] = $c;
		$res['buttons'] = array("submit" => array("title" => "OK", "submit" => true), "cancel");
		
		echo json_encode($res);    
	} 
    public function Ajax_OrderLog($id) {
        $res = new \stdClass();
        
        $order = order_load($id);
        $entries = dbClass::load("order_log", "@id_order = $id ORDER BY time ASC");
                
        $res->title = et("Order log") . ": " . $order->order_id;
        $res->content = view("\Sloway\Views\AdminOrders\OrderLog", array("order" => $order, "entries" => $entries));
        $res->scrollable = true;
        $res->width = 0.6;
        $res->height = 0.8;
        $res->buttons = array("ok" => array("align" => "right"));
        
        echo json_encode($res);
    }  
    public function Ajax_OrderLogDetails($id) {
        $res = new \stdClass();
        
        $entry = dbClass::load("order_log", "@id = $id", 1);
        $order = order_load($entry->id_order);
        
        $details = $entry->details;
        if ($entry->compressed)
            $details = gzuncompress($details);
        
        $res->title = et("Order log") . ": " . $order->order_id;
        $res->content = $details;
        $res->scrollable = true;
        $res->width = 0.6;
        $res->height = 0.8;
        $res->buttons = array("ok" => array("align" => "right"));
        
        echo json_encode($res);
    }  

    protected function cleanup_ids($name) {
        $val = $this->input->post($name);
        if (is_array($val))
            $res = implode(",", $val); else
            $res = "";
        
        return $res;
    }
    protected function cleanup() {
        $group_ids = $this->cleanup_ids("order_delete_group");
        $item_ids = $this->cleanup_ids("order_delete_item");
        
        if ($group_ids) {
            $q = $this->db->query("SELECT GROUP_CONCAT(id) as ids FROM `order_item` WHERE id_group IN ($group_ids)");        
            $item_ids = trim($item_ids . "," . $q[0]->ids, ",");
            
            $this->db->query("DELETE FROM `order_group` WHERE id IN ($group_ids)");
        }
        
        if ($item_ids) {
            $this->db->query("DELETE FROM `order_item` WHERE id IN ($item_ids)");
        }
    }
    protected function load_item($item) {
        $item->price = fixed::mul($item->price, 1 + $item->tax_rate);
        
        $ch = array();            
        return Admin::EditTree_Node("item", $item, $ch);    
    }
    protected function edit_order($id) {
        if (!$id) {                                     
            $this->order = dbClass::create("order", 0, null, order::$class_order);
            $this->order->status = "confirmed";
            $this->order->date = time();
            $this->order->payment = "none";
        } else
            $this->order = order_load($id, true); 
            
        $this->status_items = array();
        
        foreach (config::get("orders.states") as $name => $ops) {
            if ($name != "temporary")
                $this->status_items[$name] = t("order_status_" . $name);
        }
        foreach (config::get("orders.payment.methods") as $payment) 
            $this->payment_items[$payment] = t("payment_$payment"); 
        $this->payment_items["none"] = "None";
        
        $this->articles = array();                
        foreach ($this->order->items as $item) {
            if ($item instanceof order_group) {
                $ch = array();
                foreach ($item->items as $sub) 
                    $ch[] = $this->load_item($sub);    
                
                $this->articles[] = Admin::EditTree_Node("group", $item, $ch);
            } else 
            $this->articles[] = $this->load_item($item);
        }    
    }   
    protected function save_order($order) {
        $this->cleanup();
        
        $order->save();
        $order->order_id = $order->order_id();    
        
        $order->save();
        
        foreach ($order->groups as $group) {
            $group->id_order = $order->id;
            $group->save();
        }
        foreach ($order->items as $item) {
            $item->id_order = $order->id;
            $item->id_group = ($item->_group) ? $item->_group->id : 0;
            
            if (order::$reservations) {
                if ($item->reservation_id)
                    $order->update_res($item, $item->quantity, null, null, true); else
                    $order->create_res($item, "order_" . $order->status);
            }
            
            $item->save();
        } 
    }
    protected function build_order($id) {
        $order = dbClass::load_def("order", "@id = '$id'", 1, null, order::$class_order);
        
        $fields = array("email", "firstname", "lastname", "street", "zipcode", "phone", "city", "country", "del_firstname", "del_lastname", "del_street", "del_zipcode", "del_city", "del_country");
        foreach ($fields as $name)
            $order->$name = $this->input->post($name);
        
        $order->source = $this->input->post("source");
        $order->stock_op = 0;
        $order->date = strtotime($this->input->post("date"));
        $order->payment = $this->input->post("payment");
        $order->status = $this->input->post("status");
        $order->bounced = 0;
        $order->items = array();
        $order->groups = array(); 
        
        if (!$order->del_firstname) $order->del_firstname = $order->firstname;
        if (!$order->del_lastname) $order->del_lastname = $order->lastname;
        if (!$order->del_street) $order->del_street = $order->street;
        if (!$order->del_zipcode) $order->del_zipcode = $order->zipcode;
        if (!$order->del_city) $order->del_city = $order->city;
        if (!$order->del_country) $order->del_country = $order->country;
        
        $items = v($_POST, "order.items", array());
        foreach ($items as $i => $node) {
            if ($node["type"] == "group") 
                $this->build_group($node, $order); else
                $this->build_item($node, $order, null);
        }  
        
        // $order->finalize();
        
        $shipping = trim($this->input->post("shipping"));
        if ($shipping != "") $order->setPrice("shipping", fixed::real($shipping), 0.22);
        
        $action = null;
        foreach (config::get("orders.actions") as $name => $ops)
            if ($ops["state"] == $order->status) {
                $action = $name;
                break;    
            }
            
        $order->action = $action;
        
        return $order;
    }  
    protected function build_group($node, $order) {
        $id = av($node, "id", 0);     
        
        $group = dbClass::load_def("order_group", "@id = '$id'", 1, null, order::$class_group);    
        $group->title = av($node, "title");
        $group->quantity = av($node, "quantity", 1);
        $group->id_ref = av($node, "id_ref", 0);
        $group->code = av($node, "code", 0);
        $group->data = dbClass::load("catalog_product", "@id = '$group->id_ref'", 1);
        $group->status = $order->status;
        
        foreach (v($node, "items", array()) as $subnode) 
            $this->build_item($subnode, $order, $group); 
            
        $order->add_to_array("groups", $group);
    }
    protected function build_item($node, $order, $group) {
        $id = av($node, "id", 0);  
        
        $item = dbClass::load_def("order_item", "@id = '$id'", 1, null, order::$class_item);
        
        $discount = av($node, "discount", 0);
        $discount = fixed::real($discount) / 100;
        
        if ($discount < 0) $discount = 0;
        if ($discount > 1) $discount = 1;
        
        $taxrate = fixed::real(av($node, "tax_rate", 0));
        $taxrate = $taxrate / 100;
            
        if ($taxrate < 0) $taxrate = 0; 
        if ($taxrate > 1) $taxrate = 1; 

        $price = fixed::real(v($node, "price", 0));
        $price = $price / (1 + $taxrate);
        
        $qty = intval(v($node, "quantity", 1));
        if ($qty <= 0) $qty = 1;
        
        $g_qty = v($group, "quantity", 1);
        
        $item->status = $order->status;
        $item->id_ref = av($node, "id_ref", 0);
        $item->code = av($node, "code", 0);
        $item->title = av($node, "title");
        $item->flags = av($node, "flags");
        $item->categories = av($node, "categories");
        $item->ac_title = av($node, "ac_title");        
        $item->price = fixed::gen($price, 4);
        $item->tax_rate = fixed::gen($taxrate, 4);
        $item->stock_mask = av($node, "stock_mask", 0);
        $item->discount = $discount;
        $item->quantity = $qty * $g_qty;
        $item->group_qty = $qty;
        $item->reservation_id = av($node, "reservation_id", 0);
        $item->id_parent = av($node, "id_parent", 0);
        $item->id_station = av($node, "id_station", 0);
        $item->data = dbClass::load("catalog_product", "@id = '$item->id_ref'", 1);
        
        $item->_group = $group;
        
        $order->items[] = $item;
    }    
     
    public function Edit_ItemBuilder($mode, $type, $data, $types) {
        $res = array();
        if ($mode == "root") {
            $menu = "<a class='admin_button_add' onclick=\"order_add_item(this)\">" . et("Add") . "</a>";
            
            $res["drop"] = "product";
            $res["menu"] = $menu;
        } else
        if ($type == "item") {
            $res["id"] = $data->id;
            $res["name"] = "items";
            $res["content"] = view("\Sloway\AdminOrders\Edit\Item", array("data" => $data));
        } else 
        if ($type == "group") {
            $res["id"] = $data->id;
            $res["name"] = "items";
            $res["content"] = view("\Sloway\AdminOrders\Edit\Group", array("data" => $data));
        } 
        
        return $res;
    }   
    public function Edit($id) {
        Admin::auth("orders.edit", $this);
                                
		$this->generate_tabs();
        $this->edit_order($id);
        if ($id) {
            $this->path = array(
                $this->order->order_id => url::site("AdminOrders/View/" . $this->order->id),
                et("Edit")
            );
        } else 
            $this->path = array("Create");

        $status = $this->order->status;
        if ($status == "pending" || $status == "temporary")
		    $tabs_curr = "status_nonauth"; else
            $tabs_curr = "status_" . $status;                
		
		$articles_editor = Admin::EditTree("order", $this->articles, array($this, "Edit_ItemBuilder"), array("item","group"), array('title' => et("Articles"), "id" => "article_list"));

        $this->module_menu = Admin::EditMenu(array(
            "back" => url::site("AdminOrders/View/" . $this->order->id),
			"save_close" => false,
        ));       
        $this->module_content = Admin::tabs($this->module_tabs, $tabs_curr, view("\Sloway\Views\AdminOrders\Edit\Index", array(
			"status" => $status,
			"order" => $this->order,
			"status_items" => $this->status_items,
			"payment_items" => $this->payment_items,
			"articles_editor" => $articles_editor,
		)));
		return $this->admin_view();
    }       

    public function Ajax_Save($id, $action) {
        $res = new \stdClass();
        
        $this->auto_render = false;
        $this->warning = false;
        $this->error = false;
        
        $continue = $this->input->post("save") || $this->input->post("save_close");
        $order = $this->build_order($id);
        
        if ($continue) {
            $this->save_order($order);
            
            $res->title = et("Information"); 
            $res->width = 0.3;
            $res->height = 0;               
            $res->content = "<div class='admin_message success'>" . et("Order saved") . "</div>";
            $res->buttons = array("ok" => array("align" => "right"));   
            
            if ($this->input->post("save_close"))
                $res->redirect = url::site("AdminOrders/View/" . $order->id); else
            if (!$id) 
                $res->redirect = url::site("AdminOrders/Edit/" . $order->id); 
            else {
                $this->Edit($order->id);
                $res->menu_content = $this->module_menu;
                $res->form_content = $this->module_content;
            }
        } else {  
            $res->title = et("Confirm changes to order before saving"); 
            
            $order->on_load(); 
            
            unset($_POST["save"]);
            unset($_POST["save_close"]);
            unset($_POST["cancel"]);
                           
            $res->content = view("\Sloway\AdminOrders\Edit\Preview", array("order" => $order));
            $res->scrollable = true;
            $res->width = 0.8;
            $res->height = 0.8;
            $res->postdata = http_build_query($_POST);
            $res->buttons = array(
                "save" => array("submit" => true, "title" => t("Save")), 
                "save_close" => array("submit" => true, "title" => t("Save and close")),
                "cancel" => array("title" => t("Cancel"))
            );  
        }          
        echo json_encode($res);     
    }    
    public function Ajax_Interface($pid) {
        $product = dbModel::load("catalog_product", "@id = " . $pid, 1);
        $status = $product->status();  
        
        $status->amount = $this->input->post("amount", 1);
        
        $order_item = null;
        if ($this->input->post("add")) {
            $order_item = $product->commit("ns", $status->amount, $status);   
            
            $res["result"] = $order_item->to_array();
            $res["close"] = true;
            
            exit(json_encode($res));            
        }
        
        $res = array();
        $res["title"] = $product->title;
        $res["content"] = buffer::view("AdminOrders/Edit/Interface", array("status" => $status, "product" => $product));
        $res["buttons"] = array("add" => array("submit" => true, "title" => "OK"), "cancel");
        
        echo json_encode($res);  
    }  

	public function Test() {
		$order = order_load(1);
		$mail = $order->build_mail("auth_required");
		echod($mail);
		//$mail->compile();

	}
}

