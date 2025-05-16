<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

$routes->match(['get','post'], 'AdminPromo', '\Sloway\Controllers\AdminPromo::Codes');
$routes->match(['get','post'], 'AdminPromo/(:any)', '\Sloway\Controllers\AdminPromo::$1');