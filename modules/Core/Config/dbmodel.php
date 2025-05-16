<?php
$model = array(
    'admin_role' => array(
        'rsql' => 'id_parent = 0',
        'psql' => 'id_parent = @P.id',
        'ch' => array('roles' => 'admin_role')
    ),
	'page' => array(
		'order' => true,
		'table' => 'pages',
		'psql' => 'id_parent = @P.id',
		'rsql' => 'id_parent = 0',
		'ch' => array(
            'sub_pages' => 'page', 
            'images' => 'image:v',  
        ),
		'on_delete' => function($db, $obj) {
			$db->query("DELETE FROM slug WHERE module = 'page' AND module_id = '{$obj->id}'");
		}
	),
    'news' => array(
        'table' => 'news',
        'ch' => array(
            'images' => 'image:b',  
        ),
		'on_delete' => function($db, $obj) {
			$db->query("DELETE FROM slug WHERE module = 'news' AND module_id = '{$obj->id}'");
		}
    ),        
    
    'navigation' => array(
        'order' => true,
        'psql' => 'id_parent = @P.id',
        'rsql' => 'id_parent = 0',
        'ch' => array(
            'sub_items' => 'navigation',
            'images' => 'image:v' 
        ),
    ),        
	
	'gallery' => array(
		'order' => true,
		'table' => 'gallery',
        'psql' => 'id_parent = @P.id',
        'rsql' => 'id_parent = 0',
		'ch' => array('sub' => 'gallery', 'images' => 'image'),
		
		'at_ch' => array('gallery'),
		'at_type' => "gallery"
	),
	
	'image' => array(
		'table' => 'images',
		'psql'  => "module = '@P.table' AND module_id = '@P.id'",
		'duplicate' => array('module_id' => '@P.id'),
		'order' => true,
	),
    
    'file' => array(
        'index' => 'id_order',
        'table' => 'files',
        'psql'  => "module = '@P.table' AND module_id = '@P.id'",
        'duplicate' => array('module_id' => '@P.id'),
        'order' => true,
    ),
);  

