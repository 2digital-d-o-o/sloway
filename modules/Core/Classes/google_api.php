<?php

namespace Sloway;

class google_api {
	public static $currency = "EUR";
	public static function view_item($item_name, $item_id, $price) { 
		$data = [
            'event' => 'view_item',
            'ecommerce' => [
                'value' => $price,
                'currency' => self::$currency,
                'items' => [
					[
						'item_name' => $item_name,
						'item_id' => $item_id, 
						'price' => $price,
						'quantity' => 1
					]
                ]
            ]
		];

		$res = "<script>if (typeof dataLayer !== 'undefined') dataLayer.push(" . json_encode($data) . ");</script>";
		
		return $res;
	}
	public static function add_to_cart($item_name, $item_id, $price, $quantity_name) { 
	//	quantity_name = IME JS VARIABLE, ki ima vrednost za kolicino
		$data = [
            'event' => 'add_to_cart',
            'ecommerce' => [
                'value' => $price,
                'currency' => self::$currency,
                'items' => [
					[
						'item_name' => $item_name,
						'item_id' => $item_id, 
						'price' => $price,
						'quantity' => "%QUANTITY_NAME%",
					]
                ]
            ]
		];

		$res = "if (typeof dataLayer !== 'undefined')\n";
        $res.= "dataLayer.push(" . json_encode($data) . ");\n";

		$res = str_replace('"%QUANTITY_NAME%"', $quantity_name, $res);
		
		
		return $res;
	}
	public static function begin_checkout($order) {
		$data_items = [];
        foreach ($order->items as $i => $item) {
            if ($item instanceof order_group) 
                $item_price = $order->groupPrice($item, "item", "tax, format"); else
                $item_price = $order->itemPrice($item, "item", "tax, format");

			$data_items[]= array(
				'item_name' => $item->title,
				'item_id' => $item->id_ref,
				'price' => $item_price,
				'quantity' => $item->quantity
            );
		}

		$data = [
			'event' => 'begin_checkout',
			'ecommerce' => [
				'value' => $order->price('order', 'all'),
				'currency' => self::$currency,
				'items' => $data_items,
            ]
        ];

		$res = "<script>if (typeof dataLayer !== 'undefined') dataLayer.push(" . json_encode($data) . ");</script>";
		
		return $res;		
	}
	public static function transaction($order) {
		$data_products = array();
        foreach ($order->items as $i => $item) {
			$data_products[]= array(
				'name' => $item->title,
				'id' => $item->id_ref,
				'price' => $order->itemPrice($item, 'item', 'all'),
				'quantity' => $item->quantity,
				'currency' => self::$currency,
			);
		}
		
		$data = [
			'event' => 'transaction',
			'ecommerce' => [
				'purchase' => [
					'actionField' => [
						'id' => $order->order_id, // Transaction ID. Required for purchases and refunds.
						'affiliation' => url::site(),
						'revenue' => $order->price('order', 'all'), // Total transaction value (incl. tax and shipping)
						'shipping' => $order->price('shipping', 'all'),
						'coupon' => $order->coupon,
						'currency' => self::$currency,
					],
					'products' => $data_products,
				]
			]
		];
		
		$res = "<script>if (typeof dataLayer !== 'undefined') dataLayer.push(" . json_encode($data) . ");</script>";
		
		return $res;		
	}

}
