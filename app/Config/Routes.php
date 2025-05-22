<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/file-explorer', 'FileExplorer::index');
$routes->post('/file-explorer/update-filled-file/(:num)', 'FileExplorer::updateFilledFile/$1');