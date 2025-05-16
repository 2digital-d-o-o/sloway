<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

$routes->match(['get','post'], 'Core/(:any)', '\Sloway\Controllers\CoreController::$1');

$routes->match(['get'], 'admin', '\Sloway\Controllers\Admin::Index');
$routes->match(['get','post'], 'Admin', '\Sloway\Controllers\Admin::Index');
$routes->match(['get','post'], 'Admin/(:any)', '\Sloway\Controllers\Admin::$1');

$routes->match(['get','post'], 'AdminLogin', '\Sloway\Controllers\AdminLogin::Index');
$routes->get('AdminLogin/Logout', '\Sloway\Controllers\AdminLogin::Logout');

$routes->match(['get','post'], 'AdminSettings', '\Sloway\Controllers\AdminSettings::Profile');
$routes->match(['get','post'], 'AdminSettings/(:any)', '\Sloway\Controllers\AdminSettings::$1');

$routes->match(['get','post'], 'AdminPages', '\Sloway\Controllers\AdminPages::Index');
$routes->match(['get','post'], 'AdminPages/(:any)', '\Sloway\Controllers\AdminPages::$1');

$routes->match(['get','post'], 'AdminUploads', '\Sloway\Controllers\AdminUploads::Index');

$routes->match(['get','post'], 'AdminUsers', '\Sloway\Controllers\AdminUsers::Index');
$routes->match(['get','post'], 'AdminUsers/(:any)', '\Sloway\Controllers\AdminUsers::$1');

$routes->match(['get','post'], 'AdminNews', '\Sloway\Controllers\AdminNews::Index');
$routes->match(['get','post'], 'AdminNews/(:any)', '\Sloway\Controllers\AdminNews::$1');

$routes->match(['get','post'], 'AdminTasks', '\Sloway\Controllers\AdminTasks::Index');
$routes->match(['get','post'], 'AdminTasks/(:any)', '\Sloway\Controllers\AdminTasks::$1');