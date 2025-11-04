<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Model;

class ResourcePermissionModel extends Model
{
    protected $table = 'resource_permissions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'resource_type',
        'resource_id',
        'permission',
        'scope',
        'created_at',
        'expires_at'
    ];
    protected $useTimestamps = false;

    protected function applyActiveFilter(BaseBuilder $builder): BaseBuilder
    {
        $now = date('Y-m-d H:i:s');

        return $builder->groupStart()
            ->where('expires_at', null)
            ->orWhere('expires_at >', $now)
            ->groupEnd();
    }

    /**
     * Get all resource permissions for a user
     *
     * @param int $userId The user ID
     * @return array Array of resource permissions
     */
    public function getUserResourcePermissions(int $userId): array
    {
        $builder = $this->builder();
        $builder->where('user_id', $userId);
        $this->applyActiveFilter($builder);

        return $builder->get()->getResultArray();
    }

    /**
     * Get specific resource permissions for a user
     *
     * @param int $userId The user ID
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return array Array of resource permissions
     */
    public function getUserResourcePermissionsForResource(int $userId, string $resourceType, int $resourceId): array
    {
        $builder = $this->builder();
        $builder->where('user_id', $userId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId);

        $this->applyActiveFilter($builder);

        return $builder->get()->getResultArray();
    }

    /**
     * Check if a user has a specific permission for a resource
     *
     * @param int $userId The user ID
     * @param string $permission The permission to check
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return bool True if user has permission, false otherwise
     */
    public function userHasResourcePermission(int $userId, string $permission, string $resourceType, int $resourceId): bool
    {
        $builder = $this->builder();
        $builder->where('user_id', $userId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('permission', $permission);

        $this->applyActiveFilter($builder);

        $query = $builder->get();
        $result = $query->getFirstRow('array');

        log_message(
            'debug',
            'Resource permission lookup: ' . json_encode([
                'user_id' => $userId,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'permission' => $permission,
                'found' => $result !== null,
            ])
        );

        return $result !== null;
    }

    /**
     * Grant a resource permission to a user
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
        $data = [
            'user_id' => $userId,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'permission' => $permission,
            'scope' => $scope,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expiresAt,
        ];

        return $this->insert($data) !== false;
    }

    /**
     * Revoke a resource permission from a user
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
        return $this->where('user_id', $userId)
            ->where('resource_type', $resourceType)
            ->where('resource_id', $resourceId)
            ->where('permission', $permission)
            ->delete();
    }

    /**
     * Get all users with permissions for a specific resource
     *
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return array Array of users with permissions
     */
    public function getUsersForResource(string $resourceType, int $resourceId): array
    {
        $builder = $this->db->table('resource_permissions rp');
        $builder->select('rp.*, u.username, u.email');
        $builder->join('users u', 'rp.user_id = u.id');
        $builder->where('rp.resource_type', $resourceType);
        $builder->where('rp.resource_id', $resourceId);
        $now = date('Y-m-d H:i:s');
        $builder->groupStart()
            ->where('rp.expires_at', null)
            ->orWhere('rp.expires_at >', $now)
            ->groupEnd();

        $query = $builder->get();
        return $query->getResultArray();
    }

    /**
     * Get all resources a user has permissions for
     *
     * @param int $userId The user ID
     * @param string $resourceType Type of resource
     * @return array Array of resources
     */
    public function getResourcesForUser(int $userId, string $resourceType): array
    {
        $builder = $this->db->table('resource_permissions rp');
        $builder->select('rp.*, t.name as resource_name, t.created_at as resource_created_at');
        $builder->join("{$resourceType}s t", "rp.resource_id = t.id");
        $builder->where('rp.user_id', $userId);
        $builder->where('rp.resource_type', $resourceType);
        $now = date('Y-m-d H:i:s');
        $builder->groupStart()
            ->where('rp.expires_at', null)
            ->orWhere('rp.expires_at >', $now)
            ->groupEnd();
        $builder->groupBy('rp.resource_id');

        $query = $builder->get();
        return $query->getResultArray();
    }
}
