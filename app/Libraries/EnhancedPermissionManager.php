<?php

namespace App\Libraries;

use CodeIgniter\Shield\Entities\User;
use App\Models\AclModel;
use App\Models\ResourceOwnerModel;
use App\Models\AclSettingsModel;
use App\Models\AclPermissionsModel;

class PermissionManager
{
    protected $aclModel;
    protected $resourceOwnerModel;
    protected $aclSettingsModel;
    protected $aclPermissionsModel;
    protected $cache;
    protected $auditLogger;

    public function __construct()
    {
        $this->aclModel = new AclModel();
        $this->resourceOwnerModel = new ResourceOwnerModel();
        $this->aclSettingsModel = new AclSettingsModel();
        $this->aclPermissionsModel = new AclPermissionsModel();
        $this->cache = \Config\Services::cache();
        $this->auditLogger = new AuditLogger();
    }

    /**
     * Check if a user has a specific permission for a resource
     * 
     * @param User $user The user to check
     * @param string $permission The permission to check (e.g., 'templates.view')
     * @param string|null $resourceType Type of resource (e.g., 'template', 'filled_file')
     * @param int|null $resourceId ID of the specific resource
     * @param array $context Additional context for ABAC evaluation
     * @return bool True if user has permission, false otherwise
     */
    public function can(User $user, string $permission, ?string $resourceType = null, ?int $resourceId = null, array $context = []): bool
    {
        // Create cache key
        $cacheKey = "perm_check_" . $user->id . "_" . md5($permission . $resourceType . $resourceId . serialize($context));
        
        // Check cache first
        $cachedResult = $this->cache->get($cacheKey);
        if ($cachedResult !== null) {
            // Log the cached result
            $this->auditLogger->logPermissionCheck(
                $user->id, 
                $permission, 
                $resourceType, 
                $resourceId, 
                $cachedResult ? 'allowed' : 'denied', 
                'Cached result'
            );
            return $cachedResult;
        }

        // 1. Check if user is owner of the resource (owners have full control)
        if ($resourceType !== null && $resourceId !== null) {
            if ($this->resourceOwnerModel->isOwner($user->id, $resourceType, $resourceId)) {
                $this->auditLogger->logPermissionCheck($user->id, $permission, $resourceType, $resourceId, 'allowed', 'Resource owner has full control');
                $this->cache->save($cacheKey, true, 300); // Cache for 5 minutes
                return true;
            }
        }

        // 2. Check direct user permissions
        if ($user->hasPermission($permission)) {
            $this->auditLogger->logPermissionCheck($user->id, $permission, $resourceType, $resourceId, 'allowed', 'Direct user permission');
            $this->cache->save($cacheKey, true, 300); // Cache for 5 minutes
            return true;
        }

        // 3. Check group permissions
        $userGroups = $user->getGroups();
        foreach ($userGroups as $group) {
            if ($this->hasGroupPermission($group, $permission)) {
                $this->auditLogger->logPermissionCheck($user->id, $permission, $resourceType, $resourceId, 'allowed', "Group permission: {$group}");
                $this->cache->save($cacheKey, true, 300); // Cache for 5 minutes
                return true;
            }
        }

        // 4. Check resource-specific permissions
        if ($resourceType !== null && $resourceId !== null) {
            $resourcePermResult = $this->aclModel->userHasPermission($user->id, $resourceType, $resourceId, $permission, $userGroups);
            if ($resourcePermResult) {
                $this->auditLogger->logPermissionCheck($user->id, $permission, $resourceType, $resourceId, 'allowed', 'Resource-specific permission');
                $this->cache->save($cacheKey, true, 300); // Cache for 5 minutes
                return true;
            }
        }

        // 5. Check ABAC policies
        if ($resourceType !== null && $resourceId !== null) {
            $policyResult = $this->evaluatePolicies($user, $permission, $resourceType, $resourceId, $context);
            if ($policyResult !== null) {
                $resultText = $policyResult ? 'allowed' : 'denied';
                $this->auditLogger->logPermissionCheck($user->id, $permission, $resourceType, $resourceId, 
                    $resultText, 'ABAC policy evaluation');
                $this->cache->save($cacheKey, $policyResult, 300); // Cache for 5 minutes
                return $policyResult;
            }
        }

        // 6. Default deny
        $this->auditLogger->logPermissionCheck($user->id, $permission, $resourceType, $resourceId, 'denied', 'Default deny');
        $this->cache->save($cacheKey, false, 300); // Cache for 5 minutes
        return false;
    }

    /**
     * Check if a group has a specific permission
     * 
     * @param string $groupName The group name
     * @param string $permission The permission to check
     * @return bool True if group has permission, false otherwise
     */
    protected function hasGroupPermission(string $groupName, string $permission): bool
    {
        $authGroups = config('AuthGroups');
        $matrix = $authGroups->matrix;
        
        if (!isset($matrix[$groupName])) {
            return false;
        }
        
        $groupPermissions = $matrix[$groupName];
        
        // Check for exact match
        if (in_array($permission, $groupPermissions)) {
            return true;
        }
        
        // Check for wildcard matches
        $permissionParts = explode('.', $permission);
        if (count($permissionParts) >= 2) {
            $wildcard = $permissionParts[0] . '.*';
            if (in_array($wildcard, $groupPermissions)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Evaluate ABAC policies
     * 
     * @param User $user The user
     * @param string $permission The permission to check
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param array $context Additional context
     * @return bool|null True/false if policy applies, null if no applicable policy
     */
    protected function evaluatePolicies(User $user, string $permission, string $resourceType, int $resourceId, array $context)
    {
        // For now, we'll return null to indicate no applicable policy
        // In a real implementation, this would evaluate ABAC policies
        return null;
    }

    /**
     * Grant a resource-specific permission to a user
     * 
     * @param int $userId The user ID
     * @param string $permission The permission to grant
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param string|null $scope Scope of the permission
     * @param string|null $expiresAt Optional expiration date
     * @return bool True on success, false on failure
     */
    public function grantResourcePermission(
        int $userId, 
        string $permission, 
        string $resourceType, 
        int $resourceId, 
        ?string $scope = null, 
        ?string $expiresAt = null
    ): bool {
        try {
            $result = $this->aclModel->grantPermission(
                $resourceType,
                $resourceId,
                'user',
                $userId,
                $permission,
                auth()->id(), // Granted by current user
                false, // Not inherited
                null, // No inheritance source
                null, // No inheritance source ID
                $expiresAt
            );
            
            if ($result) {
                // Clear cache for this user-permission combination
                $cacheKey = "perm_check_" . $userId . "_" . md5($permission . $resourceType . $resourceId);
                $this->cache->delete($cacheKey);
                
                // Log the action
                $this->auditLogger->logUserAction(
                    auth()->id(), 
                    $userId,
                    'grant', 
                    $permission, 
                    $resourceType, 
                    $resourceId, 
                    "Granted resource permission to user {$userId}"
                );
            }
            
            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Error granting resource permission: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoke a resource-specific permission from a user
     * 
     * @param int $userId The user ID
     * @param string $permission The permission to revoke
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return bool True on success, false on failure
     */
    public function revokeResourcePermission(
        int $userId, 
        string $permission, 
        string $resourceType, 
        int $resourceId
    ): bool {
        try {
            $result = $this->aclModel->revokePermission(
                $resourceType,
                $resourceId,
                'user',
                $userId,
                $permission
            );
            
            if ($result) {
                // Clear cache for this user-permission combination
                $cacheKey = "perm_check_" . $userId . "_" . md5($permission . $resourceType . $resourceId);
                $this->cache->delete($cacheKey);
                
                // Log the action
                $this->auditLogger->logUserAction(
                    auth()->id(), 
                    $userId,
                    'revoke', 
                    $permission, 
                    $resourceType, 
                    $resourceId, 
                    "Revoked resource permission from user {$userId}"
                );
            }
            
            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Error revoking resource permission: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Set the owner of a resource
     * 
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param int $ownerId ID of the user who owns the resource
     * @return bool True on success, false on failure
     */
    public function setResourceOwner(string $resourceType, int $resourceId, int $ownerId): bool
    {
        return $this->resourceOwnerModel->setOwner($resourceType, $resourceId, $ownerId);
    }

    /**
     * Get the owner of a resource
     * 
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return int|null Owner ID or null if not found
     */
    public function getResourceOwner(string $resourceType, int $resourceId): ?int
    {
        return $this->resourceOwnerModel->getOwner($resourceType, $resourceId);
    }

    /**
     * Check if a user is the owner of a resource
     * 
     * @param int $userId ID of the user
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return bool True if user is owner, false otherwise
     */
    public function isResourceOwner(int $userId, string $resourceType, int $resourceId): bool
    {
        return $this->resourceOwnerModel->isOwner($userId, $resourceType, $resourceId);
    }

    /**
     * Transfer ownership of a resource
     * 
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param int $newOwnerId ID of the new owner
     * @param int|null $transferredBy ID of the user who initiated the transfer
     * @return bool True on success, false on failure
     */
    public function transferResourceOwnership(string $resourceType, int $resourceId, int $newOwnerId, ?int $transferredBy = null): bool
    {
        return $this->resourceOwnerModel->transferOwnership($resourceType, $resourceId, $newOwnerId, $transferredBy);
    }

    /**
     * Remove ownership of a resource
     * 
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return bool True on success, false on failure
     */
    public function removeResourceOwnership(string $resourceType, int $resourceId): bool
    {
        return $this->resourceOwnerModel->removeOwnership($resourceType, $resourceId);
    }

    /**
     * Get all resources owned by a user
     * 
     * @param int $userId ID of the user
     * @param string|null $resourceType Optional resource type filter
     * @return array Array of resources owned by the user
     */
    public function getResourcesOwnedByUser(int $userId, ?string $resourceType = null): array
    {
        return $this->resourceOwnerModel->getResourcesOwnedByUser($userId, $resourceType);
    }

    /**
     * Get all ACL entries for a specific resource
     * 
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return array Array of ACL entries
     */
    public function getResourceAclEntries(string $resourceType, int $resourceId): array
    {
        return $this->aclModel->getResourceAclEntries($resourceType, $resourceId);
    }

    /**
     * Get all ACL entries for a specific user
     * 
     * @param int $userId ID of the user
     * @return array Array of ACL entries
     */
    public function getUserAclEntries(int $userId): array
    {
        $userGroups = auth()->user()->getGroups();
        return $this->aclModel->getUserAclEntries($userId, $userGroups);
    }

    /**
     * Get a setting value
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed Setting value or default
     */
    public function getSetting(string $key, $default = null)
    {
        return $this->aclSettingsModel->getSetting($key, $default);
    }

    /**
     * Set a setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool True on success, false on failure
     */
    public function setSetting(string $key, $value): bool
    {
        // Only allow superadmin to set settings
        $currentUser = auth()->user();
        if (!$currentUser->inGroup('superadmin')) {
            return false;
        }
        
        return $this->aclSettingsModel->setSetting($key, $value);
    }

    /**
     * Get all settings
     * 
     * @return array Array of all settings
     */
    public function getAllSettings(): array
    {
        return $this->aclSettingsModel->getAllSettings();
    }

    /**
     * Get settings with descriptions
     * 
     * @return array Array of settings with descriptions
     */
    public function getSettingsWithDescriptions(): array
    {
        return $this->aclSettingsModel->getSettingsWithDescriptions();
    }

    /**
     * Reset a setting to its default value
     * 
     * @param string $key Setting key
     * @return bool True on success, false on failure
     */
    public function resetSetting(string $key): bool
    {
        // Only allow superadmin to reset settings
        $currentUser = auth()->user();
        if (!$currentUser->inGroup('superadmin')) {
            return false;
        }
        
        return $this->aclSettingsModel->resetSetting($key);
    }

    /**
     * Reset all settings to their default values
     * 
     * @return bool True on success, false on failure
     */
    public function resetAllSettings(): bool
    {
        // Only allow superadmin to reset all settings
        $currentUser = auth()->user();
        if (!$currentUser->inGroup('superadmin')) {
            return false;
        }
        
        return $this->aclSettingsModel->resetAllSettings();
    }

    /**
     * Validate a setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool True if valid, false if invalid
     */
    public function validateSetting(string $key, $value): bool
    {
        return $this->aclSettingsModel->validateSetting($key, $value);
    }

    /**
     * Get all permissions
     * 
     * @return array Array of all permissions
     */
    public function getAllPermissions(): array
    {
        return $this->aclPermissionsModel->getAllPermissions();
    }

    /**
     * Get permission by name
     * 
     * @param string $name Permission name
     * @return array|null Permission data or null if not found
     */
    public function getPermissionByName(string $name): ?array
    {
        return $this->aclPermissionsModel->getPermissionByName($name);
    }

    /**
     * Get permission by ID
     * 
     * @param int $id Permission ID
     * @return array|null Permission data or null if not found
     */
    public function getPermissionById(int $id): ?array
    {
        return $this->aclPermissionsModel->getPermissionById($id);
    }

    /**
     * Create a new permission
     * 
     * @param string $name Permission name
     * @param string|null $description Permission description
     * @return int|false Permission ID on success, false on failure
     */
    public function createPermission(string $name, ?string $description = null)
    {
        // Only allow superadmin to create permissions
        $currentUser = auth()->user();
        if (!$currentUser->inGroup('superadmin')) {
            return false;
        }
        
        return $this->aclPermissionsModel->createPermission($name, $description);
    }

    /**
     * Update a permission
     * 
     * @param int $id Permission ID
     * @param string $name Permission name
     * @param string|null $description Permission description
     * @return bool True on success, false on failure
     */
    public function updatePermission(int $id, string $name, ?string $description = null): bool
    {
        // Only allow superadmin to update permissions
        $currentUser = auth()->user();
        if (!$currentUser->inGroup('superadmin')) {
            return false;
        }
        
        return $this->aclPermissionsModel->updatePermission($id, $name, $description);
    }

    /**
     * Delete a permission
     * 
     * @param int $id Permission ID
     * @return bool True on success, false on failure
     */
    public function deletePermission(int $id): bool
    {
        // Only allow superadmin to delete permissions
        $currentUser = auth()->user();
        if (!$currentUser->inGroup('superadmin')) {
            return false;
        }
        
        return $this->aclPermissionsModel->deletePermission($id);
    }

    /**
     * Get permission hierarchy
     * 
     * @return array Array representing permission hierarchy
     */
    public function getPermissionHierarchy(): array
    {
        return $this->aclPermissionsModel->getPermissionHierarchy();
    }

    /**
     * Check if a permission includes another permission
     * 
     * @param string $permission Permission to check
     * @param string $includedPermission Permission that might be included
     * @return bool True if permission includes includedPermission, false otherwise
     */
    public function permissionIncludes(string $permission, string $includedPermission): bool
    {
        return $this->aclPermissionsModel->permissionIncludes($permission, $includedPermission);
    }

    /**
     * Get all permissions that include a specific permission
     * 
     * @param string $permission Permission to check
     * @return array Array of permissions that include the specified permission
     */
    public function getPermissionsIncluding(string $permission): array
    {
        return $this->aclPermissionsModel->getPermissionsIncluding($permission);
    }

    /**
     * Log a resource access attempt
     * 
     * @param int|null $userId User who attempted access
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of specific resource
     * @param string $action Action attempted (view, edit, delete, etc.)
     * @param string $result Result of the attempt (allowed, denied)
     * @param string|null $reason Reason for the decision
     * @return bool True on success, false on failure
     */
    public function logResourceAccess(
        ?int $userId, 
        string $resourceType, 
        int $resourceId, 
        string $action, 
        string $result = 'denied', 
        ?string $reason = null
    ): bool {
        return $this->auditLogger->logResourceAccess($userId, $resourceType, $resourceId, $action, $result, $reason);
    }
}