<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Libraries\PermissionManager;

class Acl extends BaseController
{
    protected $permissionManager;
    protected $aclSettingModel;
    protected $aclEntryModel;
    protected $resourceOwnerModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->permissionManager = service('permissionManager');
        $this->aclSettingModel = model('App\Models\AclSettingModel');
        $this->aclEntryModel = model('App\Models\AclEntryModel');
        $this->resourceOwnerModel = model('App\Models\ResourceOwnerModel');
    }

    public function index()
    {
        // Check if user has permission to access ACL management
        if (!$this->permissionManager->can(user(), 'acl.manage', null, null)) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access this page.');
        }

        $data = [
            'title' => 'ACL Management',
            'aclSettings' => $this->permissionManager->getSettingsWithDescriptions(),
            'aclPermissions' => $this->permissionManager->getAllPermissions(),
            'user' => $this->request->user ?? null,
        ];

        return view('acl/index', $data);
    }

    /**
     * Manage permissions for a specific resource
     */
    public function manageResource(string $resourceType, int $resourceId)
    {
        // Check if user has permission to manage ACL for this resource
        if (!$this->permissionManager->can(user(), 'acl.manage', $resourceType, $resourceId)) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to manage ACL for this resource.');
        }

        $data = [
            'title' => 'Manage Resource Permissions',
            'resourceType' => $resourceType,
            'resourceId' => $resourceId,
            'aclEntries' => $this->permissionManager->getResourceAclEntries($resourceType, $resourceId),
            'resourceOwner' => $this->permissionManager->getResourceOwner($resourceType, $resourceId),
            'availablePermissions' => $this->permissionManager->getAllPermissions(),
            'users' => $this->getAllUsers(),
            'groups' => $this->getAllGroups(),
        ];

        return view('acl/manage_resource', $data);
    }

    /**
     * Update permissions for a specific resource
     */
    public function updateResource($resourceType, $resourceId)
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method.']);
        }

        // Check if user has permission to manage ACL for this resource
        if (!$this->permissionManager->can(user(), 'acl.update', $resourceType, $resourceId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'You do not have permission to update ACL for this resource.']);
        }

        $permissions = $this->request->getPost('permissions');
        $userIds = $this->request->getPost('user_ids');
        $groupIds = $this->request->getPost('group_ids');

        $success = true;
        $message = 'Permissions updated successfully.';

        try {
            // Clear existing permissions for this resource (except owner permissions)
            $existingEntries = $this->permissionManager->getResourceAclEntries($resourceType, $resourceId);
            $resourceOwner = $this->permissionManager->getResourceOwner($resourceType, $resourceId);

            foreach ($existingEntries as $entry) {
                // Don't remove owner permissions
                if ($entry['principal_type'] === 'user' && $entry['principal_id'] === $resourceOwner) {
                    continue;
                }
                
                $this->permissionManager->revokeResourcePermission(
                    $entry['principal_id'],
                    $entry['permission']['name'],
                    $resourceType,
                    $resourceId
                );
            }

            // Add new permissions
            if ($permissions && $userIds) {
                foreach ($userIds as $userId) {
                    foreach ($permissions as $permission) {
                        $this->permissionManager->grantResourcePermission(
                            $userId,
                            $permission,
                            $resourceType,
                            $resourceId,
                            'user', // Principal type
                            user()->id // Granted by current user
                        );
                    }
                }
            }

            if ($permissions && $groupIds) {
                foreach ($groupIds as $groupId) {
                    foreach ($permissions as $permission) {
                        // Grant permission to group
                        $this->permissionManager->grantResourcePermission(
                            $groupId,
                            $permission,
                            $resourceType,
                            $resourceId,
                            'group', // Principal type
                            user()->id // Granted by current user
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $success = false;
            $message = 'Error updating permissions: ' . $e->getMessage();
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => $success, 'message' => $message]);
        }

        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * Change the owner of a resource
     */
    public function changeOwner(string $resourceType, int $resourceId)
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method.']);
        }

        // Check if user has permission to change ownership
        if (!$this->permissionManager->can(user(), 'acl.change_owner', $resourceType, $resourceId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'You do not have permission to change the owner of this resource.']);
        }

        $newOwnerId = $this->request->getPost('new_owner_id');

        $success = $this->permissionManager->transferResourceOwnership($resourceType, $resourceId, $newOwnerId, user()->id);
        $message = $success ? 'Resource ownership changed successfully.' : 'Failed to change resource ownership.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => $success, 'message' => $message]);
        }

        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * Update ACL settings
     */
    public function updateSettings()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method.']);
        }

        // Check if user is superadmin
        if (!user()->inGroup('superadmin')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Only superadmins can update ACL settings.']);
        }

        $settings = $this->request->getPost('settings');

        $success = true;
        $message = 'Settings updated successfully.';

        foreach ($settings as $key => $value) {
            // Validate setting
            if (!$this->permissionManager->validateSetting($key, $value)) {
                $success = false;
                $message = "Invalid value for setting: {$key}";
                break;
            }

            if (!$this->permissionManager->setSetting($key, $value)) {
                $success = false;
                $message = "Failed to update setting: {$key}";
                break;
            }
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => $success, 'message' => $message]);
        }

        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * Reset ACL settings to defaults
     */
    public function resetSettings()
    {
        // Check if user is superadmin
        if (!user()->inGroup('superadmin')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Only superadmins can reset ACL settings.']);
        }

        $success = $this->permissionManager->resetAllSettings();
        $message = $success ? 'Settings have been reset to defaults.' : 'Failed to reset settings.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => $success, 'message' => $message]);
        }

        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * Get all users for ACL management
     */
    protected function getAllUsers()
    {
        $userModel = model('CodeIgniter\Shield\Models\UserModel');
        $users = $userModel->findAll();
        
        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'groups' => $user->getGroups()
            ];
        }
        
        return $result;
    }

    /**
     * Get all groups for ACL management
     */
    protected function getAllGroups()
    {
        $authGroups = config('AuthGroups');
        $groups = [];
        
        foreach ($authGroups->groups as $key => $group) {
            $groups[] = [
                'id' => $key,
                'title' => $group['title'],
                'description' => $group['description']
            ];
        }
        
        return $groups;
    }

    /**
     * Display a list of resources for permission management
     */
    public function manageList(string $resourceType)
    {
        // Check if user has permission to manage ACL for this resource type
        if (!$this->permissionManager->can(user(), 'acl.manage', $resourceType, null)) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to manage ACL for this resource type.');
        }

        $data = [
            'title' => "Manage {$resourceType} Permissions",
            'resourceType' => $resourceType,
        ];

        // Load resources based on type
        switch ($resourceType) {
            case 'template':
                $model = model('TemplateModel');
                $data['resources'] = $model->findAll();
                break;
            case 'filled_file':
                $model = model('FilledFilesModel');
                $data['resources'] = $model->findAll();
                break;
            case 'user':
                $model = model('CodeIgniter\Shield\Models\UserModel');
                $data['resources'] = $model->findAll();
                break;
            default:
                return redirect()->to('/acl')->with('error', 'Invalid resource type.');
        }

        return view('acl/manage_list', $data);
    }

    /**
     * Display audit log
     */
    public function auditLog()
    {
        // Check if user has permission to view audit log
        if (!$this->permissionManager->can(user(), 'acl.audit.view', null, null)) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to view the audit log.');
        }

        $data = [
            'title' => 'Permission Audit Log',
        ];

        return view('acl/audit_log', $data);
    }

    /**
     * Handle inheritance of permissions from a template to filled files
     */
    public function inheritPermissions(int $templateId)
    {
        // Check if user has permission to manage ACL for this template
        if (!$this->permissionManager->can(user(), 'acl.manage', 'template', $templateId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'You do not have permission to manage ACL for this template.']);
        }

        $aclSettingModel = model('App\Models\AclSettingModel');
        if (!$aclSettingModel->getSetting('inheritance_enabled', true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission inheritance is disabled.']);
        }

        // Get filled files for this template
        $filledFilesModel = model('App\Models\FilledFilesModel');
        $filledFiles = $filledFilesModel->where('templateFileId', $templateId)->findAll();

        $successCount = 0;
        foreach ($filledFiles as $file) {
            // Apply template permissions to filled file
            $templateAclEntries = $this->permissionManager->getResourceAclEntries('template', $templateId);
            
            foreach ($templateAclEntries as $entry) {
                $this->permissionManager->grantResourcePermission(
                    $entry['principal_id'],
                    $entry['permission']['name'],
                    'filled_file',
                    $file['id'],
                    $entry['principal_type'], // Principal type from the template ACL entry
                    null, // Granted by (will be set to current user)
                    true, // Inherited
                    'template', // Inheritance source type
                    $templateId, // Inheritance source ID
                    null // Expires at
                );
            }
            
            $successCount++;
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => "Permissions inherited for {$successCount} filled files."
        ]);
    }
}