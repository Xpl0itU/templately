<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Dashboard::index', ['filter' => 'sessionauth']);

// Auth routes
$routes->get('login', 'AuthController::loginView');
$routes->post('login', 'AuthController::loginAction');
$routes->get('logout', 'AuthController::logoutAction');
$routes->get('register', 'AuthController::registerView');
$routes->post('register', 'AuthController::registerAction');

// Dashboard route
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'sessionauth']);

// User management routes (admin only)
$routes->get('user-management', 'UserManagement::index', ['filter' => 'sessionauth']);
$routes->post('user-management/update-group', 'UserManagement::updateUserGroup', ['filter' => 'sessionauth']);
$routes->post('user-management/delete/(:num)', 'UserManagement::deleteUser/$1', ['filter' => 'sessionauth']);

// File explorer routes
$routes->get('/file-explorer', 'FileExplorer::index', ['filter' => 'sessionauth']);
$routes->get('/file-explorer/upload-template', 'FileExplorer::uploadTemplateWizard', ['filter' => 'sessionauth']);
$routes->get('/file-explorer/create-filled-file', 'FileExplorer::createFilledFileWizard', ['filter' => 'sessionauth']);
$routes->post('/file-explorer/update-filled-file/(:num)', 'FileExplorer::updateFilledFile/$1', ['filter' => 'sessionauth']);
$routes->post('/file-explorer/create-filled-file', 'FileExplorer::createFilledFile', ['filter' => 'sessionauth']);
$routes->post('/file-explorer/analyze-template', 'FileExplorer::analyzeTemplate', ['filter' => 'sessionauth']);
$routes->post('/file-explorer/finalize-template-upload', 'FileExplorer::finalizeTemplateUpload', ['filter' => 'sessionauth']);
$routes->post('/file-explorer/delete-template/(:num)', 'FileExplorer::deleteTemplate/$1', ['filter' => 'sessionauth']);
$routes->post('/file-explorer/delete-filled-file/(:num)', 'FileExplorer::deleteFilledFile/$1', ['filter' => 'sessionauth']);
$routes->get('/file-explorer/export-docx/(:num)', 'FileExplorer::exportDocx/$1', ['filter' => 'sessionauth']);
$routes->get('/file-explorer/export-pdf/(:num)', 'FileExplorer::exportPdf/$1', ['filter' => 'sessionauth']);
$routes->get('file-explorer/serve-image/(:num)/(:any)', 'FileExplorer::serveImage/$1/$2');
$routes->post('file-explorer/upload-field-image/(:num)/(:segment)', 'FileExplorer::uploadFieldImage/$1/$2');

// CI Shield default routes
service('auth')->routes($routes);