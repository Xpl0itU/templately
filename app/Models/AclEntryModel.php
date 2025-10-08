<?php

namespace App\Models;

use CodeIgniter\Model;

class AclEntryModel extends Model
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
        'expires_at',
    ];
    protected $useTimestamps = false;
    
    /**
     * Get ACL entries for a specific resource with permission details
     * 
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return array Array of ACL entries with permission details
     */
    public function getResourceAclEntries(string $resourceType, int $resourceId): array
    {
        return $this->select('acl_entries.*, acl_permissions.name as permission_name, acl_permissions.description as permission_description')
            ->join('acl_permissions', 'acl_entries.permission_id = acl_permissions.id')
            ->where('acl_entries.resource_type', $resourceType)
            ->where('acl_entries.resource_id', $resourceId)
            ->findAll();
    }
    
    /**
     * Get ACL entries for a specific principal
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
     * @return array Array of ACL entries
     */
    public function getUserAclEntries(int $userId): array
    {
        // Get direct user permissions
        $userEntries = $this->where('principal_type', 'user')
            ->where('principal_id', $userId)
            ->findAll();
        
        // Get group permissions
        $groupEntries = [];
        $user = model('CodeIgniter\Shield\Models\UserModel')->find($userId);
        if ($user) {
            $userGroups = $user->getGroups();
            
            if (!empty($userGroups)) {
                $builder = $this->db->table($this->table);
                $builder->where('principal_type', 'group');
                $builder->whereIn('principal_id', $userGroups);
                $query = $builder->get();
                $groupEntries = $query->getResultArray();
            }
        }
        
        // Merge and return all entries
        return array_merge($userEntries, $groupEntries);
    }
    
    /**
     * Check if a principal (user or group) has a specific permission for a resource
     * 
     * @param int $principalId ID of the user or group
     * @param string $principalType Type of principal ('user' or 'group')
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param string $permission Permission to check
     * @return bool True if principal has permission, false otherwise
     */
    public function principalHasPermission(int $principalId, string $principalType, string $resourceType, int $resourceId, string $permission): bool
    {
        // Get the permission ID
        $permissionModel = model('App\\Models\\AclPermissionModel');
        $permissionRecord = $permissionModel->getPermissionByName($permission);
        
        if (!$permissionRecord) {
            return false;
        }
        
        $permissionId = $permissionRecord['id'];
        
        // Check for direct principal permissions
        $principalQuery = $this->db->table($this->table)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('principal_type', $principalType)
            ->where('principal_id', $principalId)
            ->where('permission_id', $permissionId)
            ->where('(expires_at IS NULL OR expires_at > NOW())')
            ->get();
        
        if ($principalQuery->getNumRows() > 0) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if a user has a specific permission for a resource
     * 
     * @param int $userId ID of the user
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param string $permission Permission to check
     * @return bool True if user has permission, false otherwise
     */
    public function userHasPermission(int $userId, string $resourceType, int $resourceId, string $permission): bool
    {
        // Use the principalHasPermission method with 'user' as principal type
        return $this->principalHasPermission($userId, 'user', $resourceType, $resourceId, $permission);
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
            // Get the permission ID
            $permissionModel = model('App\Models\AclPermissionModel');
            $permissionRecord = $permissionModel->getPermissionByName($permission);
            
            if (!$permissionRecord) {
                return false;
            }
            
            $permissionId = $permissionRecord['id'];
            
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
                    'inherited' => $inherited ? 1 : 0,
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
            // Get the permission ID
            $permissionModel = model('App\Models\AclPermissionModel');
            $permissionRecord = $permissionModel->getPermissionByName($permission);
            
            if (!$permissionRecord) {
                return false;
            }
            
            $permissionId = $permissionRecord['id'];
            
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
     * Get all permissions for a principal (user or group) on a resource
     * 
     * @param int $principalId ID of the user or group
     * @param string $principalType Type of principal ('user' or 'group')
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return array Array of permissions
     */
    public function getPrincipalResourcePermissions(int $principalId, string $principalType, string $resourceType, int $resourceId): array
    {
        // Get direct principal permissions
        $principalQuery = $this->db->table($this->table . ' ae')
            ->select('ap.name as permission, ap.description, ae.inherited, ae.inheritance_source_type, ae.inheritance_source_id, ae.expires_at')
            ->join('acl_permissions ap', 'ae.permission_id = ap.id')
            ->where('ae.resource_type', $resourceType)
            ->where('ae.resource_id', $resourceId)
            ->where('ae.principal_type', $principalType)
            ->where('ae.principal_id', $principalId)
            ->where('(ae.expires_at IS NULL OR ae.expires_at > NOW())')
            ->get();
        
        $principalPermissions = $principalQuery->getResultArray();
        
        // For users, also get group permissions
        if ($principalType === 'user') {
            $user = model('CodeIgniter\Shield\Models\UserModel')->find($principalId);
            $groupPermissions = [];
            
            if ($user) {
                $userGroups = $user->getGroups();
                
                if (!empty($userGroups)) {
                    $groupQuery = $this->db->table($this->table . ' ae')
                        ->select('ap.name as permission, ap.description, ae.inherited, ae.inheritance_source_type, ae.inheritance_source_id, ae.expires_at')
                        ->join('acl_permissions ap', 'ae.permission_id = ap.id')
                        ->where('ae.resource_type', $resourceType)
                        ->where('ae.resource_id', $resourceId)
                        ->where('ae.principal_type', 'group')
                        ->whereIn('ae.principal_id', $userGroups)
                        ->where('(ae.expires_at IS NULL OR ae.expires_at > NOW())')
                        ->get();
                    
                    $groupPermissions = $groupQuery->getResultArray();
                }
            }
            
            // Merge and deduplicate permissions
            $allPermissions = array_merge($principalPermissions, $groupPermissions);
            $uniquePermissions = [];
            
            foreach ($allPermissions as $permission) {
                $uniquePermissions[$permission['permission']] = $permission;
            }
            
            return array_values($uniquePermissions);
        }
        
        return $principalPermissions;
    }
    
    /**
     * Get all permissions for a user on a resource (deprecated - use getPrincipalResourcePermissions instead)
     * 
     * @param int $userId ID of the user
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return array Array of permissions
     */
    public function getUserResourcePermissions(int $userId, string $resourceType, int $resourceId): array
    {
        return $this->getPrincipalResourcePermissions($userId, 'user', $resourceType, $resourceId);
    }
}