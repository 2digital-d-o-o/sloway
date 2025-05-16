<?php
$model = array(
	'catalog_value' => array(
		'order'     => true,
		'table'     => 'catalog_attribute',
		'sql'       => "(type = 'value' OR type = 'codelink')",
	),

	'catalog_category' => array(
		'order'     => false, 
		'index'     => 'id',  
		'class'     => '\Sloway\catalog_category',
		'psql'      => 'id_parent = @P.id',
		'rsql'      => 'id_parent = 0',
		'ch'        => array(
			'subcat' => 'catalog_category',
			'images' => 'image:v'
		),

		'on_delete' => function($db, $obj) {
			$db->query("DELETE FROM slug WHERE module = 'category' AND module_id = '{$obj->id}'");
		},

		'order?sort_num' => "ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1 ASC",
	),
	'catalog_property' => array(
		'order'     => "ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1, title ASC",  
		'index'     => 'id',
		'psql'      => 'id_parent = @P.id',
		'rsql'      => 'id_parent = 0',
		'ch'        => array(
			'values' => 'catalog_property:v',
			'images' => 'image:v'
		),
	),
	'catalog_product' => array(
		'order'     => false, 
		'table'     => "catalog_product",
		'ch'        => array(
			'images' => 'image:v',
			'files' => 'file:v', 
			'items' => 'catalog_item:v', 
			'slots' => 'catalog_slot:v',
		),
		'ch_sql'    => array(
			'items' => "id_parent = @P.id AND type = 'item'",
			'slots' => "id_item = @P.id",
		),

		'on_delete' => function($db, $obj) {
			$db->query("DELETE FROM slug WHERE module = 'product' AND module_id = '{$obj->id}'");
		},
		'duplicate' => array('id_parent' => "@P.id"),
		'class'     => '\Sloway\catalog_product',

		'order?sort_num' => "ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1 ASC",
	),

	'catalog_filtered' => array(
		'order'     => false,
		'table'     => "catalog_filtered",
		'class'     => "\Sloway\catalog_product",
	),        
	'catalog_item' => array( 
		'table'     => 'catalog_product',
		'order'     => true,
		'sql'       => "(type = 'item')",
		'index'     => 'id',
		'ch'        => array(
			'images' => 'image:v',
		),
		'class'     => '\Sloway\catalog_product', 

		'order?sort_num' => "ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1 ASC",
	), 
	'catalog_slot' => array(
		'order'     => true,
		'psql'      => 'id_item = @P.id',
		'class'     => '\Sloway\catalog_slot',
		'duplicate' => array('id_item' => "@P.id"),
	),
	'catalog_tag' => array(
		'order'     => false, 
		'table'     => "catalog_tag",
		'ch'        => array(
			'images' => 'image:v',
		),
	),       
				
	'adm_catalog_category' => array(
		'index'     => 'id', 
		'order'     => 'ORDER BY title ASC',
		'table'     => 'adm_catalog_category',
		'class'     => '\Sloway\catalog_category',
		'psql'      => 'id_parent = @P.id',
		'rsql'      => 'id_parent = 0',
		'ch'        => array(
			'subcat' => 'adm_catalog_category',
		),
	),	
		
	'adm_catalog_product' => array(
		'order'     => false, 
		'table'     => "adm_catalog_product",
		'class'     => '\Sloway\catalog_product',
		'ch'        => array(
			'items' => 'catalog_item:v', 
			'images' => 'image:v',
		),
		'ch_sql'    => array(
			'items' => "id_parent = @P.id AND type = 'item'",
			'images' => "module_id = @P.id AND module = 'catalog_product'",
		),
	),				
);  
		
		
