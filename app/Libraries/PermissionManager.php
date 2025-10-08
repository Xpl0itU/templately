<?php

namespace App\Libraries;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Database\BaseBuilder;
use App\Models\AclEntryModel;
use App\Models\ResourceOwnerModel;
use App\Models\AclSettingModel;
use App\Models\AclPermissionModel;

class PermissionManager
{
    protected $db;
    protected $cache;
    protected $auditLogger;
    protected $aclEntryModel;
    protected $resourceOwnerModel;
    protected $aclSettingModel;
    protected $aclPermissionModel;
    protected $resourcePermissionModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->cache = \Config\Services::cache();
        $this->auditLogger = service('auditLogger');
        $this->aclEntryModel = model('App\Models\AclEntryModel');
        $this->resourceOwnerModel = model('App\Models\ResourceOwnerModel');
        $this->aclSettingModel = model('App\Models\AclSettingModel');
        $this->aclPermissionModel = model('App\Models\AclPermissionModel');
        $this->resourcePermissionModel = model('App\Models\ResourcePermissionModel');
    }

    /**
     * Log a resource access event using the audit logger when available.
     */
    public function logResourceAccess(
        ?int $userId,
        string $resourceType,
        int $resourceId,
        string $action,
        string $result = 'denied',
        ?string $reason = null
    ): bool {
        if (! $this->auditLogger || ! method_exists($this->auditLogger, 'logResourceAccess')) {
            log_message('warning', 'Audit logger missing logResourceAccess implementation.');
            return false;
        }

        try {
            return (bool) $this->auditLogger->logResourceAccess(
                $userId,
                $resourceType,
                $resourceId,
                $action,
                $result,
                $reason
            );
        } catch (\Throwable $exception) {
            log_message('error', 'Failed to log resource access: ' . $exception->getMessage());
            return false;
        }
    }

    protected function buildCacheKey(string $principalType, int $principalId, string $permission, ?string $resourceType, ?int $resourceId, array $context = []): string
    {
        $resourceTypePart = $resourceType ?? '';
        $resourceIdPart   = $resourceId !== null ? (string) $resourceId : '';

        return "perm_check_{$principalType}_{$principalId}_" . md5($permission . $resourceTypePart . $resourceIdPart . serialize($context));
    }

    /**
     * Check if a user or group has a specific permission for a resource
     * 
     * @param int $principalId ID of the user or group
     * @param string $principalType Type of principal ('user' or 'group')
     * @param string $permission Permission to check (e.g., 'templates.view')
     * @param string|null $resourceType Type of resource (template, filled_file, etc.)
     * @param int|null $resourceId ID of the specific resource
     * @param array $context Additional context for ABAC evaluation
     * @return bool True if principal has permission, false otherwise
     */
    public function hasPermission(int $principalId, string $principalType, string $permission, ?string $resourceType = null, ?int $resourceId = null, array $context = []): bool
    {
        // Create cache key
    $cacheKey = $this->buildCacheKey($principalType, $principalId, $permission, $resourceType, $resourceId, $context);
        
        // Check cache first
        $cachedRaw = $this->cache->get($cacheKey);
        if (is_string($cachedRaw)) {
            if ($cachedRaw === 'allow' || $cachedRaw === 'deny') {
                log_message('debug', 'Permission cache hit: ' . json_encode([
                    'key' => $cacheKey,
                    'value' => $cachedRaw,
                ]));

                $cachedResult = $cachedRaw === 'allow';

                if ($this->aclSettingModel->getSetting('audit_log_enabled', true)) {
                    $this->auditLogger->logPermissionCheck(
                        $principalId,
                        $permission,
                        $resourceType,
                        $resourceId,
                        $cachedResult ? 'allowed' : 'denied',
                        'Cached result'
                    );
                }

                return $cachedResult;
            }

            log_message('debug', 'Permission cache ignored unexpected value: ' . json_encode([
                'key' => $cacheKey,
                'value' => $cachedRaw,
            ]));
        }

        // 1. Check if user is owner of the resource (owners have full control)
        if ($resourceType !== null && $resourceId !== null && $principalType === 'user') {
            if ($this->resourceOwnerModel->isOwner($principalId, $resourceType, $resourceId)) {
                if ($this->aclSettingModel->getSetting('audit_log_enabled', true)) {
                    $this->auditLogger->logPermissionCheck($principalId, $permission, $resourceType, $resourceId, 'allowed', 'Resource owner has full control');
                }
                $this->cache->save($cacheKey, 'allow', 300); // Cache for 5 minutes
                return true;
            }
        }

        // 2. Check resource permissions table for direct grants (string-based permissions)
        if ($resourceType !== null && $resourceId !== null && $principalType === 'user') {
            $resourcePermission = $this->resourcePermissionModel->userHasResourcePermission(
                $principalId,
                $permission,
                $resourceType,
                $resourceId
            );

            if ($resourcePermission) {
                if ($this->aclSettingModel->getSetting('audit_log_enabled', true)) {
                    $this->auditLogger->logPermissionCheck(
                        $principalId,
                        $permission,
                        $resourceType,
                        $resourceId,
                        'allowed',
                        'Resource-specific permission'
                    );
                }

                $this->cache->save($cacheKey, 'allow', 300);
                return true;
            }
        }

        // 3. Check direct principal permissions using ACL entries
        if ($resourceType !== null && $resourceId !== null) {
            $principalHasPermission = $this->aclEntryModel->principalHasPermission($principalId, $principalType, $resourceType, $resourceId, $permission);
            if ($principalHasPermission) {
                if ($this->aclSettingModel->getSetting('audit_log_enabled', true)) {
                    $this->auditLogger->logPermissionCheck($principalId, $permission, $resourceType, $resourceId, 'allowed', 'Explicit principal permission');
                }
                $this->cache->save($cacheKey, 'allow', 300); // Cache for 5 minutes
                return true;
            }
        }

        // 4. Check group permissions if principal is a user
        if ($principalType === 'user' && $resourceType !== null && $resourceId !== null) {
            $userGroupPermissions = $this->checkUserGroupPermissions($principalId, $permission, $resourceType, $resourceId);
            if ($userGroupPermissions) {
                if ($this->aclSettingModel->getSetting('audit_log_enabled', true)) {
                    $this->auditLogger->logPermissionCheck($principalId, $permission, $resourceType, $resourceId, 'allowed', 'Group permission');
                }
                $this->cache->save($cacheKey, 'allow', 300); // Cache for 5 minutes
                return true;
            }
        }

        // 5. Check inherited permissions if enabled
        if ($this->aclSettingModel->getSetting('inheritance_enabled', true) && 
            $resourceType !== null && $resourceId !== null) {
            $inheritedPermission = $this->checkInheritedPermissions($principalId, $principalType, $permission, $resourceType, $resourceId, $context);
            if ($inheritedPermission !== null) {
                if ($this->aclSettingModel->getSetting('audit_log_enabled', true)) {
                    $resultText = $inheritedPermission ? 'allowed' : 'denied';
                    $this->auditLogger->logPermissionCheck($principalId, $permission, $resourceType, $resourceId, 
                        $resultText, 'Inherited permission');
                }
                $this->cache->save($cacheKey, $inheritedPermission ? 'allow' : 'deny', 300); // Cache for 5 minutes
                return $inheritedPermission;
            }
        }

        // 6. Check ABAC policies
        if ($resourceType !== null && $resourceId !== null) {
            $policyResult = $this->evaluatePolicies($principalId, $principalType, $permission, $resourceType, $resourceId, $context);
            if ($policyResult !== null) {
                if ($this->aclSettingModel->getSetting('audit_log_enabled', true)) {
                    $resultText = $policyResult ? 'allowed' : 'denied';
                    $this->auditLogger->logPermissionCheck($principalId, $permission, $resourceType, $resourceId, 
                        $resultText, 'ABAC policy evaluation');
                }
                $this->cache->save($cacheKey, $policyResult ? 'allow' : 'deny', 300); // Cache for 5 minutes
                return $policyResult;
            }
        }

        // 7. Default deny
        if ($this->aclSettingModel->getSetting('audit_log_enabled', true)) {
            $this->auditLogger->logPermissionCheck($principalId, $permission, $resourceType, $resourceId, 'denied', 'Default deny');
        }
        $this->cache->save($cacheKey, 'deny', 300); // Cache for 5 minutes
        return false;
    }
    
    /**
     * Check if a user has permissions through their group memberships
     * 
     * @param int $userId User ID
     * @param string $permission Permission to check
     * @param string|null $resourceType Type of resource
     * @param int|null $resourceId ID of the specific resource
     * @return bool True if user has permission through group membership, false otherwise
     */
    protected function checkUserGroupPermissions(int $userId, string $permission, ?string $resourceType = null, ?int $resourceId = null): bool
    {
        // Get user's groups
        $userGroupModel = model('App\Models\UserGroupModel');
        $groupIds = $userGroupModel->getGroupIdsForUser($userId);

        if (empty($groupIds)) {
            return false;
        }
        
        // Check each group for the permission
        foreach ($groupIds as $groupId) {
            // Check if group has permission for this resource
            if ($resourceType !== null && $resourceId !== null) {
                $groupHasPermission = $this->aclEntryModel->principalHasPermission($groupId, 'group', $resourceType, $resourceId, $permission);
                if ($groupHasPermission) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get all groups a user belongs to
     * 
     * @param int $userId User ID
     * @return array Array of user groups
     */
    public function getUserGroups(int $userId): array
    {
        $userGroupModel = model('App\Models\UserGroupModel');
        return $userGroupModel->getGroupsForUser($userId);
    }
    
    /**
     * Get all members of a group
     * 
     * @param int $groupId Group ID
     * @return array Array of group members
     */
    public function getGroupMembers(int $groupId): array
    {
        $userGroupModel = model('App\Models\UserGroupModel');
        return $userGroupModel->getGroupMembers($groupId);
    }
    
    /**
     * Add a user to a group
     * 
     * @param int $userId User ID
     * @param int $groupId Group ID
     * @param int|null $addedBy User ID of the person adding the user (optional)
     * @return int|false Member ID on success, false on failure
     */
    public function addUserToGroup(int $userId, int $groupId, ?int $addedBy = null)
    {
        $userGroupModel = model('App\Models\UserGroupModel');
        return $userGroupModel->addUserToGroup($userId, $groupId, $addedBy);
    }
    
    /**
     * Remove a user from a group
     * 
     * @param int $userId User ID
     * @param int $groupId Group ID
     * @return bool True on success, false on failure
     */
    public function removeUserFromGroup(int $userId, int $groupId): bool
    {
        $userGroupModel = model('App\Models\UserGroupModel');
        return $userGroupModel->removeUserFromGroup($userId, $groupId);
    }
    
    /**
     * Check if a user is a member of a group
     * 
     * @param int $userId User ID
     * @param int $groupId Group ID
     * @return bool True if user is a member, false otherwise
     */
    public function isUserInGroup(int $userId, int $groupId): bool
    {
        $userGroupModel = model('App\Models\UserGroupModel');
        return $userGroupModel->isUserInGroup($userId, $groupId);
    }
    
    /**
     * Get detailed information about group members including user details
     * 
     * @param int $groupId Group ID
     * @return array Array of group members with user details
     */
    public function getGroupMembersWithDetails(int $groupId): array
    {
        $userGroupModel = model('App\Models\UserGroupModel');
        return $userGroupModel->getGroupMembersWithDetails($groupId);
    }
    
    /**
     * Get the count of members in a group
     * 
     * @param int $groupId Group ID
     * @return int Number of members in the group
     */
    public function getGroupMemberCount(int $groupId): int
    {
        $userGroupModel = model('App\Models\UserGroupModel');
        return $userGroupModel->getGroupMemberCount($groupId);
    }
    
    /**
     * Create a new user group
     * 
     * @param string $name Group name
     * @param string|null $description Group description
     * @return int|false Group ID on success, false on failure
     */
    public function createGroup(string $name, ?string $description = null)
    {
        $userGroupModel = model('App\Models\UserGroupModel');
        return $userGroupModel->createGroup($name, $description);
    }
    
    /**
     * Delete a user group
     * 
     * @param int $groupId Group ID
     * @return bool True on success, false on failure
     */
    public function deleteGroup(int $groupId): bool
    {
        $userGroupModel = model('App\Models\UserGroupModel');
        return $userGroupModel->deleteGroup($groupId);
    }
    
    /**
     * Get all user groups
     * 
     * @return array Array of user groups
     */
    public function getAllGroups(): array
    {
        $userGroupModel = model('App\Models\UserGroupModel');
        return $userGroupModel->getAllGroups();
    }
    
    /**
     * Get a user group by ID
     * 
     * @param int $groupId Group ID
     * @return array|null Group data or null if not found
     */
    public function getGroup(int $groupId): ?array
    {
        $userGroupModel = model('App\Models\UserGroupModel');
        return $userGroupModel->getGroup($groupId);
    }
    
    /**
     * Grant a permission to a principal (user or group) for a resource
     * 
     * @param int $principalId ID of the user or group
     * @param string $principalType Type of principal ('user' or 'group')
     * @param string $permission Permission to grant
     * @param string $resourceType Type of resource (template, filled_file, etc.)
     * @param int $resourceId ID of the specific resource
     * @param int|null $grantedBy ID of the user who granted the permission
     * @param bool $inherited Whether this permission is inherited
     * @param string|null $inheritanceSourceType Type of resource this permission was inherited from
     * @param int|null $inheritanceSourceId ID of the resource this permission was inherited from
     * @param string|null $expiresAt Optional expiration date for temporary permissions
     * @return bool True on success, false on failure
     */
    public function grantPermission(
        int $principalId, 
        string $principalType,
        string $permission, 
        string $resourceType, 
        int $resourceId, 
        ?int $grantedBy = null, 
        bool $inherited = false,
        ?string $inheritanceSourceType = null, 
        ?int $inheritanceSourceId = null, 
        ?string $expiresAt = null
    ): bool
    {
        try {
            // Only allow superadmin or resource owner to grant permissions
            $currentUser = null;

            if (function_exists('auth')) {
                try {
                    $auth = auth();
                    if ($auth !== null && method_exists($auth, 'user')) {
                        $currentUser = $auth->user();
                    }
                } catch (\Throwable $exception) {
                    $currentUser = null;
                }
            }

            if ($currentUser !== null &&
                ! $currentUser->inGroup('superadmin') && 
                ! $this->resourceOwnerModel->isOwner($currentUser->id, $resourceType, $resourceId)) {
                return false;
            }

            $actingUserId = $grantedBy ?? ($currentUser !== null ? $currentUser->id : null);

            $result = $this->aclEntryModel->grantPermission(
                $resourceType,
                $resourceId,
                $principalType,
                $principalId,
                $permission,
                $actingUserId,
                $inherited,
                $inheritanceSourceType,
                $inheritanceSourceId,
                $expiresAt
            );
            
            if ($result) {
                // Clear cache for this principal-permission combination
                $cacheKey = $this->buildCacheKey($principalType, $principalId, $permission, $resourceType, $resourceId);
                if (! $this->cache->delete($cacheKey)) {
                    log_message('debug', 'Failed to delete permission cache key: ' . $cacheKey);
                }
                
                // Log the action if audit logging is enabled
                if ($this->aclSettingModel->getSetting('audit_log_enabled', true) && $actingUserId !== null) {
                    $this->auditLogger->logPermissionGrant(
                        $principalId,
                        $principalType,
                        $resourceType,
                        $resourceId,
                        $permission,
                        $result ? 'allowed' : 'denied',
                        $result ? "Granted {$permission} to {$principalType} {$principalId}" : "Failed to grant {$permission} to {$principalType} {$principalId}"
                    );
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Error granting permission: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Revoke a permission from a principal (user or group) for a resource
     * 
     * @param int $principalId ID of the user or group
     * @param string $principalType Type of principal ('user' or 'group')
     * @param string $permission Permission to revoke
     * @param string $resourceType Type of resource (template, filled_file, etc.)
     * @param int $resourceId ID of the specific resource
     * @return bool True on success, false on failure
     */
    public function revokePermission(
        int $principalId, 
        string $principalType,
        string $permission, 
        string $resourceType, 
        int $resourceId
    ): bool
    {
        try {
            // Only allow superadmin or resource owner to revoke permissions
            $currentUser = null;

            if (function_exists('auth')) {
                try {
                    $auth = auth();
                    if ($auth !== null && method_exists($auth, 'user')) {
                        $currentUser = $auth->user();
                    }
                } catch (\Throwable $exception) {
                    $currentUser = null;
                }
            }

            if ($currentUser !== null &&
                ! $currentUser->inGroup('superadmin') && 
                ! $this->resourceOwnerModel->isOwner($currentUser->id, $resourceType, $resourceId)) {
                return false;
            }

            $actingUserId = $currentUser !== null ? $currentUser->id : null;

            $result = $this->aclEntryModel->revokePermission(
                $resourceType,
                $resourceId,
                $principalType,
                $principalId,
                $permission
            );
            
            if ($result) {
                // Clear cache for this principal-permission combination
                $cacheKey = $this->buildCacheKey($principalType, $principalId, $permission, $resourceType, $resourceId);
                if (! $this->cache->delete($cacheKey)) {
                    log_message('debug', 'Failed to delete permission cache key: ' . $cacheKey);
                }
                
                // Log the action if audit logging is enabled
                if ($this->aclSettingModel->getSetting('audit_log_enabled', true) && $actingUserId !== null) {
                    $this->auditLogger->logPermissionRevoke(
                        $principalId,
                        $principalType,
                        $resourceType,
                        $resourceId,
                        $permission,
                        $result ? 'allowed' : 'denied',
                        $result ? "Revoked {$permission} from {$principalType} {$principalId}" : "Failed to revoke {$permission} from {$principalType} {$principalId}"
                    );
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Error revoking permission: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all permissions for a principal (user or group) on a resource
     * 
     * @param int $principalId ID of the user or group
     * @param string $principalType Type of principal ('user' or 'group')
     * @param string $resourceType Type of resource (template, filled_file, etc.)
     * @param int $resourceId ID of the specific resource
     * @return array Array of permissions
     */
    public function getPrincipalResourcePermissions(int $principalId, string $principalType, string $resourceType, int $resourceId): array
    {
        return $this->aclEntryModel->getPrincipalResourcePermissions($principalId, $principalType, $resourceType, $resourceId);
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
    /** @var \Config\AuthGroups $authGroups */
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
     * Check inherited permissions from parent resources
     *
     * @param int $principalId The principal ID (must be a user)
     * @param string $principalType Type of principal ('user' or 'group')
     * @param string $permission The permission to check
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param array $context Additional context
     * @return bool|null True/false if inherited permission applies, null if no inherited permission
     */
    protected function checkInheritedPermissions(int $principalId, string $principalType, string $permission, string $resourceType, int $resourceId, array $context = []): ?bool
    {
        if ($principalType !== 'user') {
            return null;
        }

        // For filled files, check if they inherit permissions from their template
        if ($resourceType === 'filled_file') {
            $filledFileModel = model('App\Models\FilledFilesModel');
            $filledFile = $filledFileModel->find($resourceId);

            if (!$filledFile || empty($filledFile['template_id'])) {
                return null;
            }

            $templateId = (int) $filledFile['template_id'];
            $inherited = $this->hasPermission($principalId, 'user', $permission, 'template', $templateId, $context);

            if ($inherited !== false) {
                // true means allowed; false means explicitly denied; null means no match
                return $inherited;
            }
        }

        return null;
    }

    /**
     * Convenience method to check if a user has a specific permission for a resource
     *
     * @param User $user The user to check
     * @param string $permission The permission to check (e.g., 'templates.view')
     * @param string|null $resourceType Type of resource (template, filled_file, etc.)
     * @param int|null $resourceId ID of the specific resource
     * @param array $context Additional context for ABAC evaluation
     * @return bool True if user has permission, false otherwise
     */
    public function can(User $user, string $permission, ?string $resourceType = null, ?int $resourceId = null, array $context = []): bool
    {
        $cacheKey = $this->buildCacheKey('user', $user->id, $permission, $resourceType, $resourceId, $context);
        $auditEnabled = $this->aclSettingModel->getSetting('audit_log_enabled', true);

        if (method_exists($user, 'inGroup') && $user->inGroup('superadmin')) {
            if ($auditEnabled) {
                $this->auditLogger->logPermissionCheck(
                    $user->id,
                    $permission,
                    $resourceType,
                    $resourceId,
                    'allowed',
                    'Superadmin override'
                );
            }

            $this->cache->save($cacheKey, 'allow', 300);

            return true;
        }

        $hasGlobalPermission = method_exists($user, 'can') && $user->can($permission);

        if ($hasGlobalPermission && ($resourceType === null || in_array($permission, ['acl.manage'], true))) {
            if ($auditEnabled) {
                $reason = $resourceType === null ? 'Global permission grant' : 'Global permission override';
                $this->auditLogger->logPermissionCheck(
                    $user->id,
                    $permission,
                    $resourceType,
                    $resourceId,
                    'allowed',
                    $reason
                );
            }

            $this->cache->save($cacheKey, 'allow', 300);

            return true;
        }

        return $this->hasPermission($user->id, 'user', $permission, $resourceType, $resourceId, $context);
    }

    /**
     * Evaluate ABAC policies
     * 
    * @param int $principalId The principal ID (must be a user)
    * @param string $principalType Type of principal ('user' or 'group')
     * @param string $permission The permission to check
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param array $context Additional context
     * @return bool|null True/false if policy applies, null if no applicable policy
     */
    protected function evaluatePolicies(int $principalId, string $principalType, string $permission, string $resourceType, int $resourceId, array $context): ?bool
    {
        // For now, we'll return null to indicate no applicable policy
        // In a real implementation, this would evaluate ABAC policies
        return null;
    }

    /**
     * Grant a resource-specific permission to a user or group
     * 
     * @param int $principalId ID of the user or group
     * @param string $permission Permission to grant (e.g., 'templates.view')
     * @param string $resourceType Type of resource (template, filled_file, etc.)
     * @param int $resourceId ID of the specific resource
     * @param string $principalType Type of principal ('user' or 'group')
     * @param int|null $grantedBy ID of the user who granted the permission
     * @param bool $inherited Whether this permission is inherited (1) or explicit (0)
     * @param string|null $inheritanceSourceType Type of resource this permission was inherited from
     * @param int|null $inheritanceSourceId ID of the resource this permission was inherited from
     * @param string|null $expiresAt Optional expiration date for temporary permissions
     * @return bool True on success, false on failure
     */
    public function grantResourcePermission(
        int $principalId, 
        string $permission, 
        string $resourceType, 
        int $resourceId, 
        string $principalType = 'user',
        ?int $grantedBy = null,
        bool $inherited = false,
        ?string $inheritanceSourceType = null, 
        ?int $inheritanceSourceId = null, 
        ?string $expiresAt = null
    ): bool
    {
        try {
            $currentUser = null;

            if (function_exists('auth')) {
                try {
                    $currentUser = auth()->user();
                } catch (\Throwable $exception) {
                    $currentUser = null;
                }
            }

            if ($currentUser !== null &&
                ! $currentUser->inGroup('superadmin') &&
                ! $this->resourceOwnerModel->isOwner($currentUser->id, $resourceType, $resourceId)) {
                return false;
            }

            $actingUserId = $grantedBy
                ?? ($currentUser !== null ? $currentUser->id : null);

            $resourcePermissionCreated = true;

            if ($principalType === 'user') {
                $this->resourcePermissionModel
                    ->where('user_id', $principalId)
                    ->where('resource_type', $resourceType)
                    ->where('resource_id', $resourceId)
                    ->where('permission', $permission)
                    ->delete();

                $resourcePermissionCreated = $this->resourcePermissionModel->grantResourcePermission(
                    $principalId,
                    $permission,
                    $resourceType,
                    $resourceId,
                    null,
                    $expiresAt
                );

                if ($resourcePermissionCreated === false) {
                    log_message(
                        'error',
                        'Failed to grant resource permission: ' . json_encode([
                            'errors' => $this->resourcePermissionModel->errors(),
                            'dbError' => $this->resourcePermissionModel->db->error(),
                        ])
                    );
                }
            }

            $aclPermissionExists = $this->aclPermissionModel->getPermissionByName($permission) !== null;
            $aclResult = true;

            if ($aclPermissionExists) {
                $aclResult = $this->aclEntryModel->grantPermission(
                    $resourceType,
                    $resourceId,
                    $principalType,
                    $principalId,
                    $permission,
                    $actingUserId,
                    $inherited,
                    $inheritanceSourceType,
                    $inheritanceSourceId,
                    $expiresAt
                );
            }

            $result = ($resourcePermissionCreated !== false) && ($aclResult !== false);

            if ($result) {
                $cacheKey = $this->buildCacheKey($principalType, $principalId, $permission, $resourceType, $resourceId);
                $this->cache->delete($cacheKey);

                if ($this->aclSettingModel->getSetting('audit_log_enabled', true) && $actingUserId !== null) {
                    $this->auditLogger->logUserAction(
                        $actingUserId,
                        $principalId,
                        'grant',
                        $permission,
                        $resourceType,
                        $resourceId,
                        'allowed',
                        "Granted {$permission} to {$principalType} {$principalId}"
                    );
                }
            }

            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Error granting resource permission: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoke a resource-specific permission from a user or group
     * 
     * @param int $principalId ID of the user or group
     * @param string $permission Permission to revoke (e.g., 'templates.view')
     * @param string $resourceType Type of resource (template, filled_file, etc.)
     * @param int $resourceId ID of the specific resource
     * @param string $principalType Type of principal ('user' or 'group')
     * @return bool True on success, false on failure
     */
    public function revokeResourcePermission(
        int $principalId, 
        string $permission, 
        string $resourceType, 
        int $resourceId,
        string $principalType = 'user'
    ): bool
    {
        try {
            $currentUser = null;

            if (function_exists('auth')) {
                try {
                    $currentUser = auth()->user();
                } catch (\Throwable $exception) {
                    $currentUser = null;
                }
            }

            if ($currentUser !== null &&
                ! $currentUser->inGroup('superadmin') &&
                ! $this->resourceOwnerModel->isOwner($currentUser->id, $resourceType, $resourceId)) {
                return false;
            }

            $resourcePermissionRemoved = true;

            if ($principalType === 'user') {
                $resourcePermissionRemoved = $this->resourcePermissionModel->revokeResourcePermission(
                    $principalId,
                    $permission,
                    $resourceType,
                    $resourceId
                );
            }

            $aclPermissionExists = $this->aclPermissionModel->getPermissionByName($permission) !== null;
            $aclResult = true;

            if ($aclPermissionExists) {
                $aclResult = $this->aclEntryModel->revokePermission(
                    $resourceType,
                    $resourceId,
                    $principalType,
                    $principalId,
                    $permission
                );
            }

            $result = ($resourcePermissionRemoved !== false) && ($aclResult !== false);

            if ($result) {
                $cacheKey = $this->buildCacheKey($principalType, $principalId, $permission, $resourceType, $resourceId);
                $this->cache->delete($cacheKey);

                if ($this->aclSettingModel->getSetting('audit_log_enabled', true) && $currentUser !== null) {
                    $this->auditLogger->logUserAction(
                        $currentUser->id,
                        $principalId,
                        'revoke',
                        $permission,
                        $resourceType,
                        $resourceId,
                        'allowed',
                        "Revoked {$permission} from {$principalType} {$principalId}"
                    );
                }
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
        // Only allow superadmin to set resource owners
        $currentUser = auth()->user();
        if (!$currentUser->inGroup('superadmin')) {
            return false;
        }
        
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
     * Retrieve resource-specific permissions for a user
     *
     * @param int $userId ID of the user
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return array
     */
    public function getUserResourcePermissionsForResource(int $userId, string $resourceType, int $resourceId): array
    {
        return $this->resourcePermissionModel->getUserResourcePermissionsForResource($userId, $resourceType, $resourceId);
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
     * Get all ACL entries for a specific principal (user or group) on a resource
     * 
     * @param int $principalId ID of the user or group
     * @param string $principalType Type of principal ('user' or 'group')
     * @param string $resourceType Type of resource (template, filled_file, etc.)
     * @param int $resourceId ID of the specific resource
     * @return array Array of ACL entries
     */
    public function getPrincipalResourceAclEntries(int $principalId, string $principalType, string $resourceType, int $resourceId): array
    {
        return $this->aclEntryModel->getPrincipalResourcePermissions($principalId, $principalType, $resourceType, $resourceId);
    }
    
    /**
     * Get all ACL entries for a specific user on a resource
     * 
     * @param int $userId ID of the user
     * @param string $resourceType Type of resource (template, filled_file, etc.)
     * @param int $resourceId ID of the specific resource
     * @return array Array of ACL entries
     */
    public function getUserResourceAclEntries(int $userId, string $resourceType, int $resourceId): array
    {
        return $this->aclEntryModel->getPrincipalResourcePermissions($userId, 'user', $resourceType, $resourceId);
    }
    
    /**
     * Get all ACL entries for a specific resource
     * 
     * @param string $resourceType Type of resource (template, filled_file, etc.)
     * @param int $resourceId ID of the specific resource
     * @return array Array of ACL entries
     */
    public function getResourceAclEntries(string $resourceType, int $resourceId): array
    {
        $entries = $this->aclEntryModel->getResourceAclEntries($resourceType, $resourceId);

        if (empty($entries)) {
            return [];
        }

        $userIds = [];
        $groupIds = [];

        foreach ($entries as $entry) {
            if ($entry['principal_type'] === 'user') {
                $userIds[] = (int) $entry['principal_id'];
            } elseif ($entry['principal_type'] === 'group') {
                $groupIds[] = (int) $entry['principal_id'];
            }
        }

        $userDetails = [];
        if (! empty($userIds)) {
            /** @var \CodeIgniter\Shield\Models\UserModel $userModel */
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $users = $userModel->whereIn('id', array_unique($userIds))->findAll();

            foreach ($users as $user) {
                $userArray = method_exists($user, 'toArray') ? $user->toArray() : (array) $user;
                $userDetails[(int) $userArray['id']] = [
                    'id'       => (int) $userArray['id'],
                    'username' => $userArray['username'] ?? null,
                    'email'    => $userArray['email'] ?? null,
                    'firstName'=> $userArray['first_name'] ?? null,
                    'lastName' => $userArray['last_name'] ?? null,
                    'display'  => $userArray['full_name'] ?? null,
                ];
            }
        }

        $groupDetails = [];
        if (! empty($groupIds)) {
            /** @var \App\Models\UserGroupModel $groupModel */
            $groupModel = model('App\Models\UserGroupModel');
            $uniqueGroupIds = array_unique($groupIds);

            foreach ($uniqueGroupIds as $groupId) {
                $group = $groupModel->getGroup($groupId);
                if ($group !== null) {
                    $groupDetails[(int) $groupId] = $group;
                }
            }
        }

        foreach ($entries as &$entry) {
            if ($entry['principal_type'] === 'user') {
                $principalId = (int) $entry['principal_id'];
                $userInfo    = $userDetails[$principalId] ?? null;

                if ($userInfo !== null) {
                    $displayName = $userInfo['display']
                        ?? $userInfo['username']
                        ?? $userInfo['email']
                        ?? 'User #' . $principalId;

                    $entry['user_id']    = $principalId;
                    $entry['user_name']  = $displayName;
                    $entry['user_email'] = $userInfo['email'] ?? null;
                } else {
                    $entry['user_id']    = $principalId;
                    $entry['user_name']  = 'Former User #' . $principalId;
                    $entry['user_email'] = null;
                }
            } elseif ($entry['principal_type'] === 'group') {
                $principalId = (int) $entry['principal_id'];
                $groupInfo   = $groupDetails[$principalId] ?? null;

                if ($groupInfo !== null) {
                    $entry['group_id']          = $principalId;
                    $entry['group_name']        = $groupInfo['name'] ?? ('Group #' . $principalId);
                    $entry['group_description'] = $groupInfo['description'] ?? null;
                } else {
                    $entry['group_id']          = $principalId;
                    $entry['group_name']        = 'Group #' . $principalId;
                    $entry['group_description'] = null;
                }
            }

            $entry['principal_key'] = sprintf(
                '%s:%s:%s',
                $entry['principal_type'],
                $entry['principal_id'],
                $entry['permission_name'] ?? $entry['permission'] ?? ''
            );
        }
        unset($entry);

        return $entries;
    }

    /**
     * Get all ACL entries for a specific user
     * 
     * @param int $userId ID of the user
     * @return array Array of ACL entries
     */
    public function getUserAclEntries(int $userId): array
    {
        return $this->aclEntryModel->getUserAclEntries($userId);
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
        return $this->aclSettingModel->getSetting($key, $default);
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
        
        return $this->aclSettingModel->setSetting($key, $value);
    }

    /**
     * Get all settings
     * 
     * @return array Array of all settings
     */
    public function getAllSettings(): array
    {
        return $this->aclSettingModel->getAllSettings();
    }

    /**
     * Get settings with descriptions
     * 
     * @return array Array of settings with descriptions
     */
    public function getSettingsWithDescriptions(): array
    {
        return $this->aclSettingModel->getSettingsWithDescriptions();
    }

    /**
     * Reset a setting to its default value
     * 
     * @param string $key Setting key
     * @return bool True on success, false on failure
     */
    public function resetSetting(string $key): bool
    {
        return $this->aclSettingModel->resetSetting($key);
    }

    /**
     * Reset all settings to their default values
     * 
     * @return bool True on success, false on failure
     */
    public function resetAllSettings(): bool
    {
        return $this->aclSettingModel->resetAllSettings();
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
        return $this->aclSettingModel->validateSetting($key, $value);
    }

    /**
     * Get all permissions
     * 
     * @return array Array of all permissions
     */
    public function getAllPermissions(): array
    {
        return $this->aclPermissionModel->getAllPermissions();
    }

    /**
     * Get permission by name
     * 
     * @param string $name Permission name
     * @return array|null Permission data or null if not found
     */
    public function getPermissionByName(string $name): ?array
    {
        return $this->aclPermissionModel->getPermissionByName($name);
    }

    /**
     * Get permission by bit value
     * 
     * @param int $bitValue Bit value of the permission
     * @return array|null Permission data or null if not found
     */
    public function getPermissionByBitValue(int $bitValue): ?array
    {
        return $this->aclPermissionModel->getPermissionByBitValue($bitValue);
    }

}