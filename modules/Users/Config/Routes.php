<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

$routes->match(['get','post'], 'AdminUsers', '\Sloway\Controllers\AdminUsers::Index');
$routes->match(['get','post'], 'AdminUsers/(:any)', '\Sloway\Controllers\AdminUsers::$1');

$routes->match(['get','post'], 'User', '\App\Controllers\User::Login');
$routes->match(['get','post'], 'User/(:any)', '\App\Controllers\User::$1');
