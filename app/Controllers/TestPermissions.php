<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;
use App\Libraries\EnhancedPermissionManager;

class TestPermissions extends BaseController
{
    protected $enhancedPermissionManager;

    public function __construct()
    {
        $this->enhancedPermissionManager = service('enhancedPermissions');
    }

    public function index()
    {
        // Check if current user has permission to test permissions
        if (!$this->enhancedPermissionManager->can(auth()->user(), 'admin.access')) {
            return redirect()->to('/')->with('error', 'You do not have permission to test permissions.');
        }

        // Get current user
        $currentUser = auth()->user();
        
        // Get all users
        $userModel = model('CodeIgniter\Shield\Models\UserModel');
        $users = $userModel->findAll();
        
        // Get all templates
        $templateModel = model('App\Models\TemplateModel');
        $templates = $templateModel->findAll();
        
        // Get all filled files
        $filledFileModel = model('App\Models\FilledFilesModel');
        $filledFiles = $filledFileModel->findAll();

        // Format users with their groups and permissions
        $formattedUsers = [];
        foreach ($users as $user) {
            $formattedUsers[] = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'groups' => $user->getGroups(),
                'direct_permissions' => $user->getPermissions(),
            ];
        }

        // Get system permissions
        $authGroups = config('AuthGroups');
        $systemPermissions = $authGroups->permissions;

        $data = [
            'users' => $formattedUsers,
            'templates' => $templates,
            'filledFiles' => $filledFiles,
            'systemPermissions' => $systemPermissions,
            'currentUser' => $currentUser,
        ];

        return view('test_permissions/index', $data);
    }

    public function testUserPermission()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to test permissions
        if (!$this->enhancedPermissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to test permissions.']);
        }

        $json = $this->request->getJSON();
        
        if (!$json || !isset($json->user_id, $json->permission)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing user ID or permission.']);
        }

        $userId = (int) $json->user_id;
        $permission = $json->permission;
        $resourceType = $json->resource_type ?? null;
        $resourceId = $json->resource_id ?? null;

        try {
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $user = $userModel->find($userId);
            
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User not found.']);
            }

            $result = $this->enhancedPermissionManager->can($user, $permission, $resourceType, $resourceId ? (int)$resourceId : null);
            
            return $this->response->setJSON([
                'success' => true,
                'result' => $result,
                'message' => $result ? 'User has permission.' : 'User does not have permission.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error testing user permission: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while testing the user permission.']);
        }
    }

    public function grantUserPermission()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->enhancedPermissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON();
        
        if (!$json || !isset($json->user_id, $json->permission)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing user ID or permission.']);
        }

        $userId = (int) $json->user_id;
        $permission = $json->permission;
        $resourceType = $json->resource_type ?? null;
        $resourceId = $json->resource_id ?? null;
        $scope = $json->scope ?? null;
        $expiresAt = $json->expires_at ?? null;

        try {
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $user = $userModel->find($userId);
            
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User not found.']);
            }

            if ($resourceType !== null && $resourceId !== null) {
                // Grant resource-specific permission
                $result = $this->enhancedPermissionManager->grantResourcePermission(
                    $userId, 
                    $permission, 
                    $resourceType, 
                    (int)$resourceId, 
                    $scope, 
                    $expiresAt
                );
            } else {
                // Grant direct permission
                $user->addPermission($permission);
                $result = true;
            }
            
            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Permission granted successfully.'
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to grant permission.'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error granting user permission: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while granting the permission.']);
        }
    }

    public function revokeUserPermission()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->enhancedPermissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON();
        
        if (!$json || !isset($json->user_id, $json->permission)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing user ID or permission.']);
        }

        $userId = (int) $json->user_id;
        $permission = $json->permission;
        $resourceType = $json->resource_type ?? null;
        $resourceId = $json->resource_id ?? null;

        try {
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $user = $userModel->find($userId);
            
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User not found.']);
            }

            if ($resourceType !== null && $resourceId !== null) {
                // Revoke resource-specific permission
                $result = $this->enhancedPermissionManager->revokeResourcePermission(
                    $userId, 
                    $permission, 
                    $resourceType, 
                    (int)$resourceId
                );
            } else {
                // Revoke direct permission
                $user->removePermission($permission);
                $result = true;
            }
            
            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Permission revoked successfully.'
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to revoke permission.'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error revoking user permission: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while revoking the permission.']);
        }
    }

    public function setUserRole()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->enhancedPermissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON();
        
        if (!$json || !isset($json->user_id, $json->role)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing user ID or role.']);
        }

        $userId = (int) $json->user_id;
        $role = $json->role;

        try {
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $user = $userModel->find($userId);
            
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User not found.']);
            }

            // Set user role (group)
            $user->syncGroups($role);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'User role updated successfully.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error setting user role: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while updating the user role.']);
        }
    }

    public function getAuditLog()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->enhancedPermissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON();
        
        $criteria = [];
        if ($json) {
            $criteria = [
                'user_id' => $json->user_id ?? null,
                'action' => $json->action ?? null,
                'permission' => $json->permission ?? null,
                'resource_type' => $json->resource_type ?? null,
                'resource_id' => $json->resource_id ?? null,
                'result' => $json->result ?? null,
                'date_from' => $json->date_from ?? null,
                'date_to' => $json->date_to ?? null,
            ];
        }

        try {
            $auditLogger = service('auditLogger');
            $logs = $auditLogger->searchLogs($criteria, 50, 0);
            
            return $this->response->setJSON([
                'success' => true,
                'logs' => $logs,
                'pagination' => [
                    'start' => 1,
                    'end' => count($logs),
                    'total' => count($logs)
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error retrieving audit log: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while retrieving the audit log.']);
        }
    }

    public function getSettings()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'GET') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->enhancedPermissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        try {
            $settingsModel = model('App\Models\AclSettingsModel');
            $settings = $settingsModel->getSettingsWithDescriptions();
            
            return $this->response->setJSON([
                'success' => true,
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error retrieving settings: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while retrieving settings.']);
        }
    }

    public function updateSetting()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->enhancedPermissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON();
        
        if (!$json || !isset($json->key, $json->value)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing setting key or value.']);
        }

        $key = $json->key;
        $value = $json->value;

        try {
            $settingsModel = model('App\Models\AclSettingsModel');
            
            // Validate the setting
            if (!$settingsModel->validateSetting($key, $value)) {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid setting value.']);
            }
            
            $result = $settingsModel->setSetting($key, $value);
            
            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Setting updated successfully.'
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to update setting.'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error updating setting: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while updating the setting.']);
        }
    }

    public function resetSetting()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->enhancedPermissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON();
        
        if (!$json || !isset($json->key)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing setting key.']);
        }

        $key = $json->key;

        try {
            $settingsModel = model('App\Models\AclSettingsModel');
            $result = $settingsModel->resetSetting($key);
            
            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Setting reset to default successfully.'
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to reset setting.'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error resetting setting: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while resetting the setting.']);
        }
    }

    public function resetAllSettings()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->enhancedPermissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        try {
            $settingsModel = model('App\Models\AclSettingsModel');
            $result = $settingsModel->resetAllSettings();
            
            if ($result) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'All settings reset to default successfully.'
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to reset all settings.'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error resetting all settings: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while resetting all settings.']);
        }
    }
}