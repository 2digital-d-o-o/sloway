<?php 

$config['flags'] = array();
$config['reg_auth'] = true;

$config['registration'] = array(
	'username'  => array('edit', true),
	'npassword' => array('edit', true, array('password' => true, 'check' => 'account_match_password')),
	'cpassword' => array('edit', true, array('password' => true, 'check' => 'account_match_password')),
	'email'     => array('edit', true),
	'firstname' => array('edit', true),
	'lastname'  => array('edit', true),
	'street'    => array('edit', true),
	'zipcode'   => array('edit', true),
	'city'      => array('edit', true),
	'country'   => array('select', true, array('items' => \Sloway\countries::gen("", true))),
);

//  ACCOUNT => ORDER
$config['order_mapping'] = array(
	'email',
	'firstname',
	'lastname',  
	'street',   
	'zipcode',   
	'city',      
	'country', 
    'company',
    'vat_id',
    'phone',    
);
