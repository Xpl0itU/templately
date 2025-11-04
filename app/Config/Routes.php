<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Dashboard::index', ['filter' => 'sessionauth']);

// Setup routes
$routes->get('setup', 'Setup::index');
$routes->post('setup', 'Setup::index'); // Handle both GET and POST for setup
$routes->get('setup/check-requirements', 'Setup::checkRequirements');
$routes->post('setup/check-requirements', 'Setup::checkRequirements');
$routes->get('setup/success', 'Setup::success');

// Auth routes
$routes->get('login', 'AuthController::loginView');
$routes->post('login', 'AuthController::loginAction');
$routes->get('logout', 'AuthController::logoutAction');
$routes->get('register', 'AuthController::registerView');
$routes->post('register', 'AuthController::registerAction');

// Dashboard route
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'sessionauth']);

// Profile routes
$routes->group('profile', ['filter' => 'sessionauth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Profile::index');
    $routes->post('update-email', 'Profile::updateEmail');
    $routes->post('update-password', 'Profile::updatePassword');
});

// User management routes
$routes->group('users', ['filter' => 'sessionauth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Users::index');
    $routes->post('create', 'Users::create');
    $routes->get('get/(:num)', 'Users::getUser/$1');
    $routes->post('update', 'Users::update');
    $routes->post('delete/(:num)', 'Users::delete/$1');
});

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
