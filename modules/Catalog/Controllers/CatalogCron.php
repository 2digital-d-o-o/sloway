<?php 

namespace Sloway\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Sloway\utils;
use Sloway\config;
use Sloway\dbUtils;
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
use Sloway\catalog;
use Sloway\thumbnail;
use Sloway\slug;
 
class CatalogCron extends \App\Controllers\BaseController {
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
	{
		parent::initController($request, $response, $logger);
	}

	public function MarkPrices() {
		$time = time();

		$history = array();
		foreach ($this->db->query("SELECT * FROM catalog_history")->getResult() as $p) {
			$history[$p->id_product] = $p->price;
		}
			
		$insert = array();
		foreach ($this->db->query("SELECT id, price FROM catalog_product WHERE id_parent = 0 AND visible = 1 AND price != 0")->getResult() as $p) {
			$pid = $p->id;
			if (!isset($history[$pid]) || $history[$pid] != $p->price) {
				$insert[]= array(
					"id_product" => $pid,
					"time" => $time,
					"timef" => utils::mysql_datetime($time),
					"price" => $p->price
				);
			} 
		}

		echod($insert);
		//dbUtils::insert_update($this->db, "catalog_history", $insert, true);
	}
	public function NotifyStock() {
		$notify = array();
		$q = $this->db->query("SELECT sub.email,p.id,p.stock,p.title,p.url FROM catalog_product as p INNER JOIN catalog_stock_sub as sub WHERE p.id = sub.id_product")->getResult();
		foreach ($q as $qq) {
			if (!isset($notify[$qq->email]))
				$notify[$qq->email] = array();
			
			$notify[$qq->email][]= array("id" => $qq->id, "title" => $qq->title, "url" => $qq->url);
		}

		foreach ($notify as $email => $items) {
			$c = "";
			foreach ($items as $item) 
				$c.= "<a href='" . $item["url"] . "'>" . $item["title"] . "</a><br>";

			$mail = \Sloway\message::load("catalog.notify_stock", null, "mail", array("content" => $c))->to_mail();
			$mail->from = config::get("email.from");
			$mail->send($email);

		}
	}

}


