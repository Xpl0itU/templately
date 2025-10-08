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
// TODO: Add a flag to enable these on dev envs
$routes->get('setup/cleanup', 'Setup::cleanup');
$routes->get('setup/debug', 'Setup::debug');

// Test route for setup detection
$routes->get('test-setup', 'TestSetup::index');

// Auth routes
$routes->get('login', 'AuthController::loginView');
$routes->post('login', 'AuthController::loginAction');
$routes->get('logout', 'AuthController::logoutAction');
$routes->get('register', 'AuthController::registerView');
$routes->post('register', 'AuthController::registerAction');

// Advanced permissions routes
$routes->get('advanced-permissions', 'AdvancedPermissions::index', ['filter' => 'sessionauth']);
$routes->post('advanced-permissions/grant-resource-permission', 'AdvancedPermissions::grantResourcePermission', ['filter' => 'sessionauth']);
$routes->post('advanced-permissions/revoke-resource-permission', 'AdvancedPermissions::revokeResourcePermission', ['filter' => 'sessionauth']);
$routes->get('advanced-permissions/user-resource-permissions', 'AdvancedPermissions::getUserResourcePermissions', ['filter' => 'sessionauth']);

// Simple permissions routes
$routes->group('permissions', static function (RouteCollection $routes) {
    $routes->get('/', 'SimplePermissions::index');
    $routes->post('get-user-details/(:num)', 'SimplePermissions::getUserDetails/$1');
    $routes->post('get-template-details/(:num)', 'SimplePermissions::getTemplateDetails/$1');
    $routes->post('get-file-details/(:num)', 'SimplePermissions::getFileDetails/$1');
    $routes->post('save-user-role', 'SimplePermissions::saveUserRole');
    $routes->post('save-user-groups', 'SimplePermissions::saveUserGroups');
    $routes->post('save-user-permissions', 'SimplePermissions::saveUserPermissions');
    $routes->post('reset-user-permissions', 'SimplePermissions::resetUserPermissions');
    $routes->post('save-template-permissions', 'SimplePermissions::saveTemplatePermissions');
    $routes->post('save-file-permissions', 'SimplePermissions::saveFilePermissions');
    $routes->post('get-audit-log', 'SimplePermissions::getAuditLog');
});

// Advanced ACL routes
$routes->group('acl', static function (RouteCollection $routes) {
    $routes->get('/', 'Acl::index');
    $routes->get('manage/(:any)', 'Acl::manageList/$1');
    $routes->get('manage/(:any)/(:num)', 'Acl::manageResource/$1/$2');
    $routes->post('update/(:any)/(:num)', 'Acl::updateResource/$1/$2');
    $routes->post('change-owner/(:any)/(:num)', 'Acl::changeOwner/$1/$2');
    $routes->post('settings/update', 'Acl::updateSettings');
    $routes->post('settings/reset', 'Acl::resetSettings');
    $routes->post('inherit-permissions/(:num)', 'Acl::inheritPermissions/$1');
    $routes->get('audit', 'Acl::auditLog');
});

// Dashboard route
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'sessionauth']);

// Profile routes
$routes->group('profile', ['filter' => 'sessionauth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Profile::index');
    $routes->post('update-email', 'Profile::updateEmail');
    $routes->post('update-password', 'Profile::updatePassword');
});

// Unified user management routes (replaces user-groups, user-management, and advanced-permissions)
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