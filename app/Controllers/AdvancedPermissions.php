<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Libraries\PermissionManager;

class AdvancedPermissions extends BaseController
{
    protected $permissionManager;
    protected $userGroupModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->permissionManager = service('permissionManager');
        $this->userGroupModel = model('App\\Models\\UserGroupModel');
    }

    public function index()
    {
        // Check if user has permission to access ACL management
        if (!$this->permissionManager->can(auth()->user(), 'acl.manage', null, null)) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access this page.');
        }

        $data = [
            'title' => 'Advanced Permissions (ACL)',
            'aclSettings' => $this->permissionManager->getSettingsWithDescriptions(),
            'users' => $this->getAllUsers(),
            'userGroups' => $this->getAllUserGroups(),
            'templates' => $this->getAllTemplates(),
            'filledFiles' => $this->getAllFilledFiles(),
        ];

        return view('advanced_permissions/index', $data);
    }

    public function grantResourcePermission()
    {
        $request = service('request');
        
        if (strtolower($request->getMethod()) !== 'post') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method.']);
        }

        // Get JSON data from request body
        $jsonData = $request->getJSON(true);
        
        if ($jsonData) {
            $resourceId = $jsonData['resourceId'] ?? null;
            $resourceType = $jsonData['resourceType'] ?? null;
            $principalId = $jsonData['principalId'] ?? $jsonData['userId'] ?? null; // Support both principalId and userId for backward compatibility
            $permission = $jsonData['permission'] ?? null;
            $principalType = $jsonData['principalType'] ?? 'user'; // Default to user for backward compatibility
        } else {
            // Fallback to form data
            $resourceId = $request->getPost('resourceId');
            $resourceType = $request->getPost('resourceType');
            $principalId = $request->getPost('principalId') ?? $request->getPost('userId');
            $permission = $request->getPost('permission');
            $principalType = $request->getPost('principalType') ?? 'user'; // Default to user for backward compatibility
        }

        // Check if user has permission to manage ACL for this resource
        if (!$this->permissionManager->can(auth()->user(), 'acl.manage', $resourceType, $resourceId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'You do not have permission to modify permissions for this resource.']);
        }

        if (empty($resourceType) || empty($resourceId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Missing resource information.']);
        }

        $principalIds = is_array($principalId) ? $principalId : [$principalId];
        $principalIds = array_values(array_filter(array_map('intval', $principalIds), static fn ($id) => $id > 0));

        $permissionsInput = $jsonData['permissions']
            ?? $request->getPost('permissions')
            ?? $permission
            ?? [];

        $permissions = is_array($permissionsInput) ? $permissionsInput : [$permissionsInput];
        $permissions = array_values(array_filter(array_map(static fn ($perm) => is_string($perm) ? trim($perm) : $perm, $permissions)));

        if (empty($principalIds) || empty($permissions)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Select at least one principal and one permission.']);
        }

        $resourceId = (int) $resourceId;
        $principalType = is_array($principalType) ? ($principalType[0] ?? 'user') : $principalType;
        $principalType = $principalType ?: 'user';

        $totalOperations = 0;
        $successCount = 0;
        $failures = [];

        foreach ($principalIds as $currentPrincipalId) {
            foreach ($permissions as $permissionName) {
                if (! is_string($permissionName) || $permissionName === '') {
                    continue;
                }

                $totalOperations++;

                $result = $this->permissionManager->grantResourcePermission(
                    $currentPrincipalId,
                    $permissionName,
                    $resourceType,
                    $resourceId,
                    $principalType
                );

                if ($result) {
                    $successCount++;
                } else {
                    $failures[] = [
                        'principalId'   => $currentPrincipalId,
                        'principalType' => $principalType,
                        'permission'    => $permissionName,
                    ];
                }
            }
        }

        $success = $successCount === $totalOperations && $totalOperations > 0;

        if ($this->permissionManager->getSetting('audit_log_enabled', true)) {
            $currentUser = auth()->user();
            $grantedPermissions = implode(', ', $permissions);
            $principalList = implode(', ', array_map(static fn ($id) => (string) $id, $principalIds));

            $this->permissionManager->logResourceAccess(
                $currentUser ? $currentUser->id : null,
                $resourceType,
                $resourceId,
                'grant_permission',
                $successCount > 0 ? 'allowed' : 'denied',
                $successCount > 0
                    ? sprintf('Granted %s to %s principal(s) [%s]', $grantedPermissions, $principalType, $principalList)
                    : sprintf('Failed to grant %s to %s principal(s) [%s]', $grantedPermissions, $principalType, $principalList)
            );
        }

        if ($totalOperations === 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Nothing to grant. Please select at least one permission.',
            ]);
        }

        if ($success) {
            return $this->response->setJSON([
                'success' => true,
                'message' => sprintf('Granted %d permission(s).', $successCount),
            ]);
        }

        if ($successCount > 0) {
            return $this->response->setJSON([
                'success' => true,
                'message' => sprintf('Granted %d of %d permission changes. Some permissions may already exist or could not be applied.', $successCount, $totalOperations),
                'failures' => $failures,
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to grant the selected permissions.',
            'failures' => $failures,
        ]);
    }

    public function revokeResourcePermission()
    {
        $request = service('request');
        
        if (strtolower($request->getMethod()) !== 'post') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method.']);
        }

        // Get JSON data from request body
        $jsonData = $request->getJSON(true);
        
        if ($jsonData) {
            $resourceId = $jsonData['resourceId'] ?? null;
            $resourceType = $jsonData['resourceType'] ?? null;
            $principalId = $jsonData['principalId'] ?? $jsonData['userId'] ?? null; // Support both principalId and userId for backward compatibility
            $permission = $jsonData['permission'] ?? null;
            $principalType = $jsonData['principalType'] ?? 'user'; // Default to user for backward compatibility
        } else {
            // Fallback to form data
            $resourceId = $request->getPost('resourceId');
            $resourceType = $request->getPost('resourceType');
            $principalId = $request->getPost('principalId') ?? $request->getPost('userId');
            $permission = $request->getPost('permission');
            $principalType = $request->getPost('principalType') ?? 'user'; // Default to user for backward compatibility
        }

        // Check if user has permission to manage ACL for this resource
        if (!$this->permissionManager->can(auth()->user(), 'acl.manage', $resourceType, $resourceId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'You do not have permission to modify permissions for this resource.']);
        }

        if (empty($resourceType) || empty($resourceId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Missing resource information.']);
        }

        $entriesInput = $jsonData['entries'] ?? $request->getPost('entries') ?? null;

        $entries = [];
        if ($entriesInput !== null) {
            foreach ((array) $entriesInput as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $entryPrincipalId = isset($entry['principalId']) ? (int) $entry['principalId'] : null;
                $entryPermission = $entry['permission'] ?? null;
                $entryPrincipalType = $entry['principalType'] ?? ($entry['principal_type'] ?? 'user');

                if (! $entryPrincipalId || empty($entryPermission)) {
                    continue;
                }

                $entries[] = [
                    'principalId'   => $entryPrincipalId,
                    'principalType' => $entryPrincipalType,
                    'permission'    => is_string($entryPermission) ? trim($entryPermission) : $entryPermission,
                ];
            }
        }

        if (empty($entries)) {
            if ($principalId === null || $permission === null) {
                return $this->response->setJSON(['success' => false, 'message' => 'Select at least one permission to revoke.']);
            }

            $entries[] = [
                'principalId'   => (int) $principalId,
                'principalType' => $principalType,
                'permission'    => $permission,
            ];
        }

        $resourceId = (int) $resourceId;

        $totalOperations = 0;
        $successCount = 0;
        $failures = [];

        foreach ($entries as $entry) {
            $permissionName = is_string($entry['permission']) ? trim($entry['permission']) : null;
            if ($permissionName === null || $permissionName === '') {
                continue;
            }

            $totalOperations++;

            $result = $this->permissionManager->revokeResourcePermission(
                (int) $entry['principalId'],
                $permissionName,
                $resourceType,
                $resourceId,
                $entry['principalType'] ?? 'user'
            );

            if ($result) {
                $successCount++;
            } else {
                $failures[] = [
                    'principalId'   => (int) $entry['principalId'],
                    'principalType' => $entry['principalType'] ?? 'user',
                    'permission'    => $permissionName,
                ];
            }
        }

        if ($this->permissionManager->getSetting('audit_log_enabled', true)) {
            $currentUser = auth()->user();
            $principalSummary = implode(', ', array_map(static function ($entry) {
                return sprintf('%s:%s', $entry['principalType'] ?? 'user', $entry['principalId']);
            }, $entries));

            $this->permissionManager->logResourceAccess(
                $currentUser ? $currentUser->id : null,
                $resourceType,
                $resourceId,
                'revoke_permission',
                $successCount > 0 ? 'allowed' : 'denied',
                $successCount > 0
                    ? sprintf('Revoked permissions from [%s]', $principalSummary)
                    : sprintf('Failed to revoke permissions from [%s]', $principalSummary)
            );
        }

        if ($totalOperations === 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Nothing to revoke. Please select at least one permission entry.',
            ]);
        }

        if ($successCount === $totalOperations) {
            return $this->response->setJSON([
                'success' => true,
                'message' => sprintf('Revoked %d permission(s).', $successCount),
            ]);
        }

        if ($successCount > 0) {
            return $this->response->setJSON([
                'success' => true,
                'message' => sprintf('Revoked %d of %d permission changes. Some permissions may not have been removed.', $successCount, $totalOperations),
                'failures' => $failures,
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Failed to revoke the selected permissions.',
            'failures' => $failures,
        ]);
    }

    public function getUserResourcePermissions()
    {
        $request = service('request');
        
        // Try to get parameters from query string first, then from JSON body
        $resourceId = $request->getGet('resourceId') ?? ($request->getJSON(true)['resourceId'] ?? null);
        $resourceType = $request->getGet('resourceType') ?? ($request->getJSON(true)['resourceType'] ?? null);
        $userId = $request->getGet('userId') ?? ($request->getJSON(true)['userId'] ?? null);

        if (!$this->permissionManager->can(auth()->user(), 'acl.manage', $resourceType, $resourceId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'You do not have permission to access this resource.']);
        }

        // Get all permissions for the resource (not just for a specific user)
        $permissions = $this->permissionManager->getResourceAclEntries($resourceType, $resourceId);

        $ownerData = null;
        $ownerId = $this->permissionManager->getResourceOwner($resourceType, $resourceId);

        if ($ownerId) {
            /** @var \CodeIgniter\Shield\Models\UserModel $userModel */
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $owner = $userModel->find($ownerId);

            if ($owner !== null) {
                $ownerArray = method_exists($owner, 'toArray') ? $owner->toArray() : (array) $owner;

                $ownerData = [
                    'id'       => (int) ($ownerArray['id'] ?? $ownerId),
                    'username' => $ownerArray['username'] ?? null,
                    'email'    => $ownerArray['email'] ?? null,
                    'display'  => $ownerArray['full_name']
                        ?? $ownerArray['username']
                        ?? $ownerArray['email']
                        ?? 'User #' . $ownerId,
                ];
            }
        }

        return $this->response->setJSON([
            'success'     => true,
            'permissions' => $permissions,
            'owner'       => $ownerData,
        ]);
    }

    private function getAllUsers()
    {
        $userModel = model('CodeIgniter\Shield\Models\UserModel');
        $users = $userModel->findAll();

        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'roles' => $user->getGroups(),
                'user_groups' => $this->userGroupModel->getGroupsForUser($user->id),
            ];
        }

        return $result;
    }

    private function getAllUserGroups()
    {
        return $this->userGroupModel->getAllGroupsWithMemberCounts();
    }

    private function getAllTemplates()
    {
        $templateModel = model('App\Models\TemplateModel');
        $templates = $templateModel->findAll();
        
        $result = [];
        foreach ($templates as $template) {
            $ownerId = $this->permissionManager->getResourceOwner('template', $template['id']);
            $owner = null;
            
            if ($ownerId) {
                $userModel = model('CodeIgniter\Shield\Models\UserModel');
                $ownerUser = $userModel->find($ownerId);
                $owner = $ownerUser ? $ownerUser->username : 'Unknown';
            }
            
            $result[] = [
                'id' => $template['id'],
                'name' => $template['name'],
                'owner' => $owner,
                'type' => 'template',
                'permissions' => $this->permissionManager->getResourceAclEntries('template', $template['id'])
            ];
        }
        
        return $result;
    }

    private function getAllFilledFiles()
    {
        $filledFileModel = model('App\Models\FilledFilesModel');
        $files = $filledFileModel->findAll();
        
        $result = [];
        foreach ($files as $file) {
            $ownerId = $this->permissionManager->getResourceOwner('filled_file', $file['id']);
            $owner = null;
            
            if ($ownerId) {
                $userModel = model('CodeIgniter\Shield\Models\UserModel');
                $ownerUser = $userModel->find($ownerId);
                $owner = $ownerUser ? $ownerUser->username : 'Unknown';
            }
            
            $result[] = [
                'id' => $file['id'],
                'name' => $file['name'],
                'owner' => $owner,
                'type' => 'filled_file',
                'permissions' => $this->permissionManager->getResourceAclEntries('filled_file', $file['id'])
            ];
        }
        
        return $result;
    }
}