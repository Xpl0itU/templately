<?php

namespace App\Models;

use CodeIgniter\Model;

class AclPermissionsModel extends Model
{
    protected $table = 'acl_permissions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name',
        'description',
    ];
    protected $useTimestamps = false;
    
    /**
     * Get all permissions
     * 
     * @return array Array of all permissions
     */
    public function getAllPermissions(): array
    {
        try {
            $query = $this->get();
            return $query->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Error getting ACL permissions: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get permission by name
     * 
     * @param string $name Permission name
     * @return array|null Permission data or null if not found
     */
    public function getPermissionByName(string $name): ?array
    {
        try {
            $query = $this->where('name', $name)->get();
            $result = $query->getRowArray();
            
            return $result ?: null;
        } catch (\Exception $e) {
            log_message('error', 'Error getting ACL permission by name: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get permission by ID
     * 
     * @param int $id Permission ID
     * @return array|null Permission data or null if not found
     */
    public function getPermissionById(int $id): ?array
    {
        try {
            $query = $this->where('id', $id)->get();
            $result = $query->getRowArray();
            
            return $result ?: null;
        } catch (\Exception $e) {
            log_message('error', 'Error getting ACL permission by ID: ' . $e->getMessage());
            return null;
        }
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
        try {
            // Check if permission already exists
            $existing = $this->getPermissionByName($name);
            if ($existing) {
                return $existing['id'];
            }
            
            $data = [
                'name' => $name,
                'description' => $description,
            ];
            
            $result = $this->insert($data);
            
            return $result !== false ? $result : false;
        } catch (\Exception $e) {
            log_message('error', 'Error creating ACL permission: ' . $e->getMessage());
            return false;
        }
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
        try {
            $data = [
                'name' => $name,
                'description' => $description,
            ];
            
            $result = $this->update($id, $data);
            
            return $result !== false;
        } catch (\Exception $e) {
            log_message('error', 'Error updating ACL permission: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a permission
     * 
     * @param int $id Permission ID
     * @return bool True on success, false on failure
     */
    public function deletePermission(int $id): bool
    {
        try {
            // Check if permission is in use
            $aclModel = model('App\Models\AclModel');
            $entries = $aclModel->where('permission_id', $id)->findAll();
            
            if (!empty($entries)) {
                // Cannot delete permission that is in use
                return false;
            }
            
            $result = $this->delete($id);
            
            return $result !== false;
        } catch (\Exception $e) {
            log_message('error', 'Error deleting ACL permission: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get permission hierarchy
     * 
     * @return array Array representing permission hierarchy
     */
    public function getPermissionHierarchy(): array
    {
        return [
            'full_control' => [
                'description' => 'Full control over the resource',
                'includes' => ['modify', 'read_execute', 'read', 'write'],
            ],
            'modify' => [
                'description' => 'Modify the resource',
                'includes' => ['read_execute', 'read', 'write'],
            ],
            'read_execute' => [
                'description' => 'Read and execute the resource',
                'includes' => ['read'],
            ],
            'read' => [
                'description' => 'Read the resource',
                'includes' => [],
            ],
            'write' => [
                'description' => 'Write to the resource',
                'includes' => [],
            ],
        ];
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
        $hierarchy = $this->getPermissionHierarchy();
        
        // If checking if a permission includes itself, return true
        if ($permission === $includedPermission) {
            return true;
        }
        
        // Check if the permission exists in the hierarchy
        if (!isset($hierarchy[$permission])) {
            return false;
        }
        
        // Check if the included permission is in the list of included permissions
        return in_array($includedPermission, $hierarchy[$permission]['includes']);
    }
    
    /**
     * Get all permissions that include a specific permission
     * 
     * @param string $permission Permission to check
     * @return array Array of permissions that include the specified permission
     */
    public function getPermissionsIncluding(string $permission): array
    {
        $hierarchy = $this->getPermissionHierarchy();
        $includingPermissions = [];
        
        foreach ($hierarchy as $perm => $details) {
            if (in_array($permission, $details['includes']) || $perm === $permission) {
                $includingPermissions[] = $perm;
            }
        }
        
        return $includingPermissions;
    }
}