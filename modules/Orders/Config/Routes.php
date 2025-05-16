<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

$routes->match(['get','post'], 'AdminOrders', '\Sloway\Controllers\AdminOrders::Index');
$routes->match(['get','post'], 'AdminOrders/(:any)', '\Sloway\Controllers\AdminOrders::$1');

$routes->match(['get'], 'Cart', '\App\Controllers\Cart::Index');
$routes->match(['get'], 'Cart/Invoice/(:any)', '\App\Controllers\Cart::Invoice/$1');
$routes->match(['get'], 'Cart/(:any)', '\App\Controllers\Cart::Index/$1');
$routes->match(['post'], 'Cart/Submit/(:any)', '\App\Controllers\Cart::Submit/$1');