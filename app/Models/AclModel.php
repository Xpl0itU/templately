<?php

namespace App\Models;

use CodeIgniter\Model;

class AclModel extends Model
{
    protected $table = 'acl_entries';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'resource_type',
        'resource_id',
        'principal_type',
        'principal_id',
        'permission_id',
        'granted_by',
        'inherited',
        'inheritance_source_type',
        'inheritance_source_id',
        'created_at',
        'updated_at',
        'expires_at'
    ];
    protected $useTimestamps = false;

    /**
     * Get all ACL entries for a specific resource
     *
     * @param string $resourceType Type of resource (template, filled_file, etc.)
     * @param int $resourceId ID of the specific resource
     * @return array Array of ACL entries
     */
    public function getResourceAclEntries(string $resourceType, int $resourceId): array
    {
        return $this->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->findAll();
    }

    /**
     * Get ACL entries for a specific user/group
     *
     * @param string $principalType Type of principal (user, group)
     * @param int $principalId ID of the user or group
     * @return array Array of ACL entries
     */
    public function getPrincipalAclEntries(string $principalType, int $principalId): array
    {
        return $this->where('principal_type', $principalType)
            ->where('principal_id', $principalId)
            ->findAll();
    }

    /**
     * Get ACL entries for a specific user (including group memberships)
     *
     * @param int $userId ID of the user
     * @param array $userGroups Array of group names the user belongs to
     * @return array Array of ACL entries
     */
    public function getUserAclEntries(int $userId, array $userGroups = []): array
    {
        $builder = $this->db->table($this->table);
        $builder->where('(principal_type = "user" AND principal_id = ' . $userId . ')');

        if (!empty($userGroups)) {
            // Get group IDs from the groups_users table
            $groupIds = [];
            $groupsResult = $this->db->table('auth_groups_users')
                ->select('group')
                ->where('user_id', $userId)
                ->get()
                ->getResultArray();

            foreach ($groupsResult as $groupRow) {
                $groupIds[] = $groupRow['group'];
            }

            if (!empty($groupIds)) {
                $builder->orWhere('(principal_type = "group" AND principal_id IN (' . implode(',', array_map('intval', $groupIds)) . '))');
            }
        }

        $query = $builder->get();
        return $query->getResultArray();
    }

    /**
     * Check if a user has a specific permission for a resource
     *
     * @param int $userId ID of the user
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param string $permission Permission to check
     * @param array $userGroups Array of group names the user belongs to
     * @return bool True if user has permission, false otherwise
     */
    public function userHasPermission(int $userId, string $resourceType, int $resourceId, string $permission, array $userGroups = []): bool
    {
        // First check if the user is the owner of the resource
        $ownerModel = model('App\Models\ResourceOwnerModel');
        if ($ownerModel->isOwner($userId, $resourceType, $resourceId)) {
            return true; // Owners have full control
        }

        // Get the permission ID
        $permissionId = $this->getPermissionId($permission);
        if (!$permissionId) {
            return false;
        }

        // Check for explicit user permissions
        $userQuery = $this->db->table($this->table)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('principal_type', 'user')
            ->where('principal_id', $userId)
            ->where('permission_id', $permissionId)
            ->where('(expires_at IS NULL OR expires_at > NOW())')
            ->get();

        if ($userQuery->getNumRows() > 0) {
            return true;
        }

        // Check for group permissions
        if (!empty($userGroups)) {
            $groupIds = [];
            $groupsResult = $this->db->table('auth_groups_users')
                ->select('group')
                ->where('user_id', $userId)
                ->get()
                ->getResultArray();

            foreach ($groupsResult as $groupRow) {
                $groupIds[] = $groupRow['group'];
            }

            if (!empty($groupIds)) {
                $groupQuery = $this->db->table($this->table)
                    ->where('resource_type', $resourceType)
                    ->where('resource_id', $resourceId)
                    ->where('principal_type', 'group')
                    ->whereIn('principal_id', $groupIds)
                    ->where('permission_id', $permissionId)
                    ->where('(expires_at IS NULL OR expires_at > NOW())')
                    ->get();

                if ($groupQuery->getNumRows() > 0) {
                    return true;
                }
            }
        }

        // Check inherited permissions if enabled
        $settingsModel = model('App\Models\AclSettingsModel');
        if ($settingsModel->getSetting('inheritance_enabled', true)) {
            return $this->checkInheritedPermissions($userId, $resourceType, $resourceId, $permission, $userGroups);
        }

        return false;
    }

    /**
     * Check inherited permissions from parent resources
     *
     * @param int $userId ID of the user
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param string $permission Permission to check
     * @param array $userGroups Array of group names the user belongs to
     * @return bool True if user has inherited permission, false otherwise
     */
    protected function checkInheritedPermissions(int $userId, string $resourceType, int $resourceId, string $permission, array $userGroups = []): bool
    {
        // For filled files, check if they inherit permissions from their template
        if ($resourceType === 'filled_file') {
            // Get the template ID for this filled file
            $filledFileModel = model('App\Models\FilledFilesModel');
            $filledFile = $filledFileModel->find($resourceId);

            if ($filledFile && isset($filledFile['templateFileId'])) {
                $templateId = $filledFile['templateFileId'];

                // Check if the template has the permission for this user
                return $this->userHasPermission($userId, 'template', $templateId, $permission, $userGroups);
            }
        }

        return false;
    }

    /**
     * Grant a permission to a user or group for a resource
     *
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param string $principalType Type of principal (user, group)
     * @param int $principalId ID of the user or group
     * @param string $permission Permission to grant
     * @param int|null $grantedBy ID of the user who granted the permission
     * @param bool $inherited Whether this permission is inherited
     * @param string|null $inheritanceSourceType Type of resource this permission was inherited from
     * @param int|null $inheritanceSourceId ID of the resource this permission was inherited from
     * @param string|null $expiresAt Optional expiration date
     * @return bool True on success, false on failure
     */
    public function grantPermission(
        string $resourceType,
        int $resourceId,
        string $principalType,
        int $principalId,
        string $permission,
        ?int $grantedBy = null,
        bool $inherited = false,
        ?string $inheritanceSourceType = null,
        ?int $inheritanceSourceId = null,
        ?string $expiresAt = null
    ): bool {
        try {
            $permissionId = $this->getPermissionId($permission);
            if (!$permissionId) {
                return false;
            }

            // Check if this permission already exists
            $existing = $this->where('resource_type', $resourceType)
                ->where('resource_id', $resourceId)
                ->where('principal_type', $principalType)
                ->where('principal_id', $principalId)
                ->where('permission_id', $permissionId)
                ->first();

            if ($existing) {
                // Update existing permission
                $data = [
                    'granted_by' => $grantedBy ?? $existing['granted_by'],
                    'inherited' => $inherited,
                    'inheritance_source_type' => $inheritanceSourceType,
                    'inheritance_source_id' => $inheritanceSourceId,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'expires_at' => $expiresAt,
                ];

                return $this->update($existing['id'], $data);
            }

            // Insert new permission
            $data = [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'principal_type' => $principalType,
                'principal_id' => $principalId,
                'permission_id' => $permissionId,
                'granted_by' => $grantedBy,
                'inherited' => $inherited ? 1 : 0,
                'inheritance_source_type' => $inheritanceSourceType,
                'inheritance_source_id' => $inheritanceSourceId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt,
            ];

            return $this->insert($data) !== false;
        } catch (\Exception $e) {
            log_message('error', 'Error granting permission: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoke a permission from a user or group for a resource
     *
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param string $principalType Type of principal (user, group)
     * @param int $principalId ID of the user or group
     * @param string $permission Permission to revoke
     * @return bool True on success, false on failure
     */
    public function revokePermission(
        string $resourceType,
        int $resourceId,
        string $principalType,
        int $principalId,
        string $permission
    ): bool {
        try {
            $permissionId = $this->getPermissionId($permission);
            if (!$permissionId) {
                return false;
            }

            // Delete the permission
            return $this->where('resource_type', $resourceType)
                ->where('resource_id', $resourceId)
                ->where('principal_type', $principalType)
                ->where('principal_id', $principalId)
                ->where('permission_id', $permissionId)
                ->delete();
        } catch (\Exception $e) {
            log_message('error', 'Error revoking permission: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get permission ID by name
     *
     * @param string $permission Permission name
     * @return int|null Permission ID or null if not found
     */
    public function getPermissionId(string $permission): ?int
    {
        $query = $this->db->table('acl_permissions')
            ->select('id')
            ->where('name', $permission)
            ->get();

        $result = $query->getRow();

        return $result ? $result->id : null;
    }

    /**
     * Get permission name by ID
     *
     * @param int $permissionId Permission ID
     * @return string|null Permission name or null if not found
     */
    public function getPermissionName(int $permissionId): ?string
    {
        $query = $this->db->table('acl_permissions')
            ->select('name')
            ->where('id', $permissionId)
            ->get();

        $result = $query->getRow();

        return $result ? $result->name : null;
    }

    /**
     * Get all permissions
     *
     * @return array Array of all permissions
     */
    public function getAllPermissions(): array
    {
        $query = $this->db->table('acl_permissions')
            ->get();

        return $query->getResultArray();
    }

    /**
     * Get all principals with permissions for a resource
     *
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return array Array of principals with permissions
     */
    public function getResourcePrincipals(string $resourceType, int $resourceId): array
    {
        $builder = $this->db->table($this->table . ' ae');
        $builder->select('ae.*, ap.name as permission_name, u.username as principal_name');
        $builder->join('acl_permissions ap', 'ae.permission_id = ap.id');
        $builder->join('users u', 'ae.principal_id = u.id AND ae.principal_type = "user"', 'left');
        $builder->where('ae.resource_type', $resourceType);
        $builder->where('ae.resource_id', $resourceId);
        $builder->orderBy('ae.principal_type', 'ASC');
        $builder->orderBy('ae.principal_id', 'ASC');

        $query = $builder->get();
        return $query->getResultArray();
    }
}
