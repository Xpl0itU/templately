<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Resource Owner Model
 * 
 * Simplified ownership tracking for templates and filled files
 */
class ResourceOwnerModel extends Model
{
    protected $table = 'resource_owners';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'resource_type',
        'resource_id',
        'owner_id',
    ];
    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';

    protected $validationRules = [
        'resource_type' => 'required|in_list[template,filled_file]',
        'resource_id' => 'required|is_natural_no_zero',
        'owner_id' => 'required|is_natural_no_zero',
    ];

    /**
     * Set the owner of a resource
     */
    public function setOwner(string $resourceType, int $resourceId, int $ownerId): bool
    {
        // Check if owner already exists
        $existing = $this->where([
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ])->first();

        if ($existing) {
            // Update existing owner
            return $this->update($existing['id'], ['owner_id' => $ownerId]);
        }

        // Insert new owner
        return $this->insert([
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'owner_id' => $ownerId,
        ]) !== false;
    }

    /**
     * Get the owner of a resource
     */
    public function getOwner(string $resourceType, int $resourceId): ?int
    {
        $owner = $this->where([
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ])->first();

        return $owner ? (int)$owner['owner_id'] : null;
    }

    /**
     * Check if a user is the owner of a resource
     */
    public function isOwner(int $userId, string $resourceType, int $resourceId): bool
    {
        return $this->getOwner($resourceType, $resourceId) === $userId;
    }

    /**
     * Get all resources owned by a user
     */
    public function getResourcesOwnedByUser(int $userId, ?string $resourceType = null): array
    {
        $builder = $this->where('owner_id', $userId);

        if ($resourceType !== null) {
            $builder->where('resource_type', $resourceType);
        }

        return $builder->findAll();
    }

    /**
     * Transfer ownership of a resource
     * 
     * @param string $resourceType Resource type
     * @param int $resourceId Resource ID
     * @param int $newOwnerId New owner user ID
     * @param bool $createIfNotExists If true, creates ownership if it doesn't exist (default: false)
     * @return bool
     */
    public function transferOwnership(string $resourceType, int $resourceId, int $newOwnerId, bool $createIfNotExists = false): bool
    {
        $existing = $this->where([
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ])->first();

        if (!$existing) {
            if ($createIfNotExists) {
                return $this->setOwner($resourceType, $resourceId, $newOwnerId);
            }
            return false;
        }

        return $this->update($existing['id'], ['owner_id' => $newOwnerId]);
    }

    /**
     * Remove ownership of a resource (when deleting)
     */
    public function removeOwnership(string $resourceType, int $resourceId): bool
    {
        return $this->where([
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
        ])->delete();
    }

    /**
     * Get all resources owned by a user
     *
     * @param int $ownerId Owner user ID
     * @param string|null $resourceType Optional filter by resource type
     * @return array
     */
    public function getResourcesByOwner(int $ownerId, ?string $resourceType = null): array
    {
        $builder = $this->where('owner_id', $ownerId);
        
        if ($resourceType !== null) {
            $builder->where('resource_type', $resourceType);
        }
        
        return $builder->findAll();
    }

    /**
     * Alias for getResourcesByOwner
     */
    public function getUserOwnedResources(int $ownerId, ?string $resourceType = null): array
    {
        return $this->getResourcesByOwner($ownerId, $resourceType);
    }
}
