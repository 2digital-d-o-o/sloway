<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

$routes->match(['get','post'], 'AdminCatalog', '\Sloway\Controllers\AdminCatalog::Index');
$routes->match(['get','post'], 'AdminCatalog/(:any)', '\Sloway\Controllers\AdminCatalog::$1');

$routes->match(['get','post'], 'CatalogCron/MarkPrices', '\Sloway\Controllers\CatalogCron::MarkPrices');
$routes->match(['get','post'], 'CatalogCron/NotifyStock', '\Sloway\Controllers\CatalogCron::NotifyStock');