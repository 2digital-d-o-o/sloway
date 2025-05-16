<?php 

$config['modules'] = array();
$config['types'] = array(
    'page' => array(
        'model' => 'page',
        'url_mask' => '%ID/%TITLE',

        'ch_sel' => 'sub_pages',
        'id_sel' => 'id',
        'title_sel' => 'title',
    ),
    'catalog_category' => array(
        'model' => 'catalog_category',
        'url_mask' => 'c%ID/%TITLE',

        'ch_sel' => 'subcat',
    ),
);
        
