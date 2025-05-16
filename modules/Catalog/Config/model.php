<?php
	$model = array(
		'catalog_type' => array(
			'order'     => true,
			'index'     => 'id',
			'table'     => 'catalog_attribute',   
			'sql'       => "type = 'type'",
			
			'ch'        => array('attrs' => 'catalog_attr'),
			'ch_sql'    => array('attrs' => 'id_parent = @P.id'),
		),
		'catalog_attr' => array(
			'order'     => true,
			'table'     => 'catalog_attribute',
            'class'     => 'catalog_attr',
			'load'      => 'catalog::model_load_attr',
			'sql'       => "type = 'attr'",

			'ch'        => array('values' => 'catalog_value'),
			'ch_sql'    => array('values' => 'id_parent = @P.id'),
		),
		'catalog_value' => array(
			'order'     => true,
			'table'     => 'catalog_attribute',
			'sql'       => "(type = 'value' OR type = 'codelink')",
		),
	
		'catalog_category' => array(
            'order'     => false, 
            'index'     => 'id',  
            'class'     => 'catalog_category',
            'psql'      => 'id_parent = @P.id',
            'rsql'      => 'id_parent = 0',
			'ch'        => array(
                'subcat' => 'catalog_category',
                'images' => 'image:v'
            ),

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
        'catalog_maincat' => array(
            'index'     => 'id',
            'table'     => 'catalog_category',
            'sql'       => 'id_parent = 0',
            'order'     => true,
            'ch'        => array('subcats' => 'catalog_subcat', 'images' => 'image:v'), 
            'ch_sql'    => array('subcats' => 'id_parent = @P.id'),
        ),
        'catalog_subcat' => array(
            'order'     => true,
            'index'     => 'id',
            'table'     => 'catalog_category',
            'at_type'   => 'subcat',
            'ch'        => array('images' => 'image:v'), 
        ),		
		'catalog_product' => array(
			'order'     => false, 
			'table'     => "catalog_product",
			'ch'        => array(
                'images' => 'image:v',
                'files' => 'file:v', 
                'items' => 'catalog_item:v', 
                'slots' => 'catalog_slot:v',
                'props' => 'catalog_product_property:v',
            ),
			'ch_sql'    => array(
				'items' => "id_parent = @P.id AND type = 'item'",
				'slots' => "id_item = @P.id",
			),
            
			'duplicate' => array('id_parent' => "@P.id"),
			'class'     => "catalog::model_obj_class",
            
            'order?sort_num' => "ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1 ASC",
		),
        'catalog_filtered' => array(
            'order'     => false,
            'table'     => "catalog_filtered",
            'class'     => "catalog_product",
        ),        
		'catalog_item' => array( 
			'table'     => 'catalog_product',
			'order'     => true,
			'sql'       => "(type = 'item' OR type_id = '0')",
			'index'     => 'id',
			'ch'        => array(
                'images' => 'image:v',
                'props' => 'catalog_product_property:v',
            ),
			'class'     => "catalog::model_obj_class", 
            
            'order?sort_num' => "ORDER BY (CASE sort_num WHEN '' THEN '9999' ELSE sort_num END)*1 ASC",
		), 
        'catalog_product_property' => array(
            'table' => 'catalog_product_property',
            'psql'      => 'id_product = @P.id',
            'duplicate' => array('id_product' => "@P.id"),
        ),
		'catalog_slot' => array(
			'order'     => true,
			'psql'      => 'id_item = @P.id',
			'class'     => 'catalog_slot',
			'duplicate' => array('id_item' => "@P.id"),
		),
        'catalog_tag' => array(
			'order'     => false, 
			'table'     => "catalog_tag",
			'ch'        => array(
                'images' => 'image:v',
            ),
		),        
	);  
