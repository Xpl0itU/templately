<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;
use App\Libraries\PermissionManager;

class SimplePermissions extends BaseController
{
    protected $permissionManager;
    protected $userModel;
    protected $templateModel;
    protected $filledFileModel;
    protected $userGroupModel;
    protected $userGroupMemberModel;

    public function __construct()
    {
    $this->permissionManager = service('permissionManager');
        $this->userModel = model('CodeIgniter\Shield\Models\UserModel');
        $this->templateModel = model('App\Models\TemplateModel');
        $this->filledFileModel = model('App\Models\FilledFilesModel');
        $this->userGroupModel = model('App\Models\UserGroupModel');
        $this->userGroupMemberModel = model('App\Models\UserGroupMemberModel');
    }

    /**
     * Display the simplified permissions management interface
     */
    public function index()
    {
        if (!$this->permissionManager->can(auth()->user(), 'permissions.view')) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access this page.');
        }

        $data = [
            'title' => 'Permissions',
            'users' => $this->getAllUsers(),
            'roleGroups' => $this->getAllRoleGroups(),
            'userGroups' => $this->getAllUserGroups(),
            'templates' => $this->getAllTemplates(),
            'filledFiles' => $this->getAllFilledFiles(),
            'systemPermissions' => $this->getSystemPermissions(),
        ];

        return view('simple_permissions/index', $data);
    }

    /**
     * Get user details for AJAX requests
     */
    public function getUserDetails($userId = null)
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->permissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        if (!$userId) {
            $json = $this->request->getJSON(true);
            $userId = $json['user_id'] ?? null;
        }

        if (!$userId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'User ID is required.']);
        }

        try {
            $user = $this->userModel->find($userId);
            
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User not found.']);
            }

            $customGroups = $this->userGroupModel->getGroupsForUser($user->id);

            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'roles' => $user->getGroups(),
                'user_groups' => array_map(static function (array $group) {
                    return [
                        'id' => $group['id'],
                        'name' => $group['name'],
                    ];
                }, $customGroups),
                'permissions' => $user->getPermissions(),
            ];

            return $this->response->setJSON(['success' => true, 'user' => $userData]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting user details: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while retrieving user details.']);
        }
    }

    /**
     * Get template details for AJAX requests
     */
    public function getTemplateDetails($templateId = null)
    {
    if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->permissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        if (!$templateId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Template ID is required.']);
        }

        try {
            $template = $this->templateModel->find($templateId);
            
            if (!$template) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Template not found.']);
            }

            $templateData = [
                'id' => $template['id'],
                'name' => $template['name'],
                'description' => $template['description'],
                'createdAt' => $template['createdAt'],
                'updatedAt' => $template['updatedAt'],
            ];

            return $this->response->setJSON(['success' => true, 'template' => $templateData]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting template details: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while retrieving template details.']);
        }
    }

    /**
     * Get filled file details for AJAX requests
     */
    public function getFileDetails($fileId = null)
    {
    if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->permissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        if (!$fileId) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'File ID is required.']);
        }

        try {
            $file = $this->filledFileModel->find($fileId);
            
            if (!$file) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'File not found.']);
            }

            $fileData = [
                'id' => $file['id'],
                'name' => $file['name'],
                'templateFileId' => $file['templateFileId'],
                'createdAt' => $file['createdAt'],
                'updatedAt' => $file['updatedAt'],
            ];

            return $this->response->setJSON(['success' => true, 'file' => $fileData]);
        } catch (\Exception $e) {
            log_message('error', 'Error getting file details: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while retrieving file details.']);
        }
    }

    /**
     * Save user role for AJAX requests
     */
    public function saveUserRole()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->permissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON();
        
        if (!$json || !isset($json->user_id, $json->role)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing user ID or role.']);
        }

        $userId = (int) $json->user_id;
        $role = $json->role;

        try {
            $user = $this->userModel->find($userId);
            
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User not found.']);
            }

            // Sync user groups (this will replace all existing groups with the new one)
            $user->syncGroups($role);

            // Log the action
            $this->permissionManager->logResourceAccess(
                auth()->id(),
                'user',
                $userId,
                'update_role',
                'allowed',
                "Updated role assignment to {$role}"
            );

            return $this->response->setJSON(['success' => true, 'message' => 'User role updated successfully.']);
        } catch (\Exception $e) {
            log_message('error', 'Error saving user role: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while saving the user role.']);
        }
    }

    /**
     * Save user group membership assignments
     */
    public function saveUserGroups()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        if (!$this->permissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON(true);

        if (!$json || !isset($json['user_id'], $json['group_ids']) || !is_array($json['group_ids'])) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing user ID or group assignments.']);
        }

        $userId = (int) $json['user_id'];
        $targetGroupIds = array_values(array_unique(array_map('intval', $json['group_ids'])));

        try {
            $user = $this->userModel->find($userId);

            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User not found.']);
            }

            $currentGroupIds = $this->userGroupModel->getGroupIdsForUser($userId);

            $groupsToAdd = array_diff($targetGroupIds, $currentGroupIds);
            $groupsToRemove = array_diff($currentGroupIds, $targetGroupIds);

            foreach ($groupsToAdd as $groupId) {
                $this->userGroupModel->addUserToGroup($userId, $groupId, auth()->id());
            }

            foreach ($groupsToRemove as $groupId) {
                $this->userGroupModel->removeUserFromGroup($userId, $groupId);
            }

            $this->permissionManager->logResourceAccess(
                auth()->id(),
                'user',
                $userId,
                'update_user_groups',
                'allowed',
                'Updated user group memberships'
            );

            return $this->response->setJSON(['success' => true, 'message' => 'User groups updated successfully.']);
        } catch (\Exception $e) {
            log_message('error', 'Error saving user groups: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while saving the user groups.']);
        }
    }

    /**
     * Save user permissions for AJAX requests
     */
    public function saveUserPermissions()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->permissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON();
        
        if (!$json || !isset($json->user_id, $json->permissions)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing user ID or permissions.']);
        }

        $userId = (int) $json->user_id;
        $permissions = $json->permissions;

        try {
            $user = $this->userModel->find($userId);
            
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User not found.']);
            }

            // Sync user permissions (this will replace all existing permissions with the new ones)
            $user->syncPermissions($permissions);

            // Log the action
            $this->permissionManager->logResourceAccess(
                auth()->id(), 
                'user', 
                $userId, 
                'update_permissions', 
                'allowed', 
                'Updated user permissions'
            );

            return $this->response->setJSON(['success' => true, 'message' => 'User permissions updated successfully.']);
        } catch (\Exception $e) {
            log_message('error', 'Error saving user permissions: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while saving user permissions.']);
        }
    }

    /**
     * Reset user permissions to default group permissions for AJAX requests
     */
    public function resetUserPermissions()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->permissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON();
        
        if (!$json || !isset($json->user_id)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing user ID.']);
        }

        $userId = (int) $json->user_id;

        try {
            $user = $this->userModel->find($userId);
            
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User not found.']);
            }

            // Remove all direct permissions (keep only group permissions)
            $user->syncPermissions([]);

            // Log the action
            $this->permissionManager->logResourceAccess(
                auth()->id(), 
                'user', 
                $userId, 
                'reset_permissions', 
                'allowed', 
                'Reset user permissions to default group permissions'
            );

            return $this->response->setJSON(['success' => true, 'message' => 'User permissions reset to default group permissions.']);
        } catch (\Exception $e) {
            log_message('error', 'Error resetting user permissions: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while resetting user permissions.']);
        }
    }

    /**
     * Save template permissions for AJAX requests
     */
    public function saveTemplatePermissions()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->permissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON();
        
        if (!$json || !isset($json->template_id, $json->view_access)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing template ID or view access setting.']);
        }

        $templateId = (int) $json->template_id;
        $viewAccess = $json->view_access;
        $groups = $json->groups ?? [];
        $users = $json->users ?? [];

        try {
            $template = $this->templateModel->find($templateId);
            
            if (!$template) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Template not found.']);
            }

            // For now, we'll just log the action
            // In a real implementation, you would save these permissions to a database table
            $this->permissionManager->logResourceAccess(
                auth()->id(), 
                'template', 
                $templateId, 
                'update_permissions', 
                'allowed', 
                "Updated template permissions: view_access={$viewAccess}"
            );

            return $this->response->setJSON(['success' => true, 'message' => 'Template permissions updated successfully.']);
        } catch (\Exception $e) {
            log_message('error', 'Error saving template permissions: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while saving template permissions.']);
        }
    }

    /**
     * Save filled file permissions for AJAX requests
     */
    public function saveFilePermissions()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->permissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON();
        
        if (!$json || !isset($json->file_id, $json->access_level)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Missing file ID or access level.']);
        }

        $fileId = (int) $json->file_id;
        $accessLevel = $json->access_level;
        $users = $json->users ?? [];

        try {
            $file = $this->filledFileModel->find($fileId);
            
            if (!$file) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'File not found.']);
            }

            // For now, we'll just log the action
            // In a real implementation, you would save these permissions to a database table
            $this->permissionManager->logResourceAccess(
                auth()->id(), 
                'filled_file', 
                $fileId, 
                'update_permissions', 
                'allowed', 
                "Updated file permissions: access_level={$accessLevel}"
            );

            return $this->response->setJSON(['success' => true, 'message' => 'File permissions updated successfully.']);
        } catch (\Exception $e) {
            log_message('error', 'Error saving file permissions: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while saving file permissions.']);
        }
    }

    /**
     * Get audit log entries for AJAX requests
     */
    public function getAuditLog()
    {
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        // Check if current user has permission to manage permissions
        if (!$this->permissionManager->can(auth()->user(), 'admin.access')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage permissions.']);
        }

        $json = $this->request->getJSON();
        
        $action = $json->action ?? null;
        $user = $json->user ?? null;

        try {
            // For now, we'll return a mock audit log
            // In a real implementation, you would query the audit log database table
            $logs = [
                [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user' => 'admin',
                    'action' => 'login',
                    'resource' => 'system',
                    'permission' => 'auth.login',
                    'result' => 'allowed'
                ],
                [
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                    'user' => 'admin',
                    'action' => 'view',
                    'resource' => 'template',
                    'permission' => 'templates.view',
                    'result' => 'allowed'
                ],
                [
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                    'user' => 'admin',
                    'action' => 'create',
                    'resource' => 'filled_file',
                    'permission' => 'filled-files.create',
                    'result' => 'allowed'
                ]
            ];

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
            log_message('error', 'Error getting audit log: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while retrieving the audit log.']);
        }
    }

    /**
     * Retrieve all users along with their role and user group assignments
     */
    protected function getAllUsers(): array
    {
        $users = $this->userModel->findAll();
        $results = [];

        foreach ($users as $user) {
            $customGroups = $this->userGroupModel->getGroupsForUser($user->id);

            $results[] = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'roles' => $user->getGroups(),
                'user_groups' => array_map(static function (array $group) {
                    return [
                        'id' => $group['id'],
                        'name' => $group['name'],
                    ];
                }, $customGroups),
            ];
        }

        return $results;
    }

    /**
     * Retrieve all role definitions from the authorization config
     */
    protected function getAllRoleGroups(): array
    {
    /** @var \Config\AuthGroups $authGroups */
    $authGroups = config('AuthGroups');
        $groups = [];

        foreach ($authGroups->groups as $name => $group) {
            $groups[] = [
                'name' => $name,
                'title' => $group['title'],
                'description' => $group['description'],
            ];
        }

        return $groups;
    }

    /**
     * Retrieve all user-defined groups stored in the database
     */
    protected function getAllUserGroups(): array
    {
        return $this->userGroupModel->getAllGroupsWithMemberCounts();
    }

    /**
     * Retrieve all templates for display
     */
    protected function getAllTemplates(): array
    {
        $templates = $this->templateModel->findAll();
        $results = [];

        foreach ($templates as $template) {
            $ownerId = $this->permissionManager->getResourceOwner('template', $template['id']);
            $ownerName = null;

            if ($ownerId) {
                $ownerEntity = $this->userModel->find($ownerId);
                $ownerName = $ownerEntity ? $ownerEntity->username : 'Unknown';
            }

            $results[] = [
                'id' => $template['id'],
                'name' => $template['name'],
                'owner' => $ownerName,
                'permissions' => $this->permissionManager->getResourceAclEntries('template', $template['id']),
            ];
        }

        return $results;
    }

    /**
     * Retrieve all filled files for display
     */
    protected function getAllFilledFiles(): array
    {
        $files = $this->filledFileModel->findAll();
        $results = [];

        foreach ($files as $file) {
            $ownerId = $this->permissionManager->getResourceOwner('filled_file', $file['id']);
            $ownerName = null;

            if ($ownerId) {
                $ownerEntity = $this->userModel->find($ownerId);
                $ownerName = $ownerEntity ? $ownerEntity->username : 'Unknown';
            }

            $results[] = [
                'id' => $file['id'],
                'name' => $file['name'],
                'owner' => $ownerName,
                'permissions' => $this->permissionManager->getResourceAclEntries('filled_file', $file['id']),
            ];
        }

        return $results;
    }

    /**
     * Retrieve system-level permissions from configuration
     */
    protected function getSystemPermissions(): array
    {
        /** @var \Config\AuthGroups $authGroups */
        $authGroups = config('AuthGroups');

        return $authGroups->permissions ?? [];
    }
}