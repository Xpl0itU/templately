<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/file-explorer', 'FileExplorer::index');
$routes->post('/file-explorer/update-filled-file/(:num)', 'FileExplorer::updateFilledFile/$1');
$routes->post('/file-explorer/create-filled-file', 'FileExplorer::createFilledFile');
$routes->post('/file-explorer/analyze-template', 'FileExplorer::analyzeTemplateFile');
$routes->post('/file-explorer/finalize-template-upload', 'FileExplorer::finalizeTemplateUpload');