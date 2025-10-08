<?php

namespace App\Models;

use CodeIgniter\Model;

class ResourceOwnerModel extends Model
{
    protected $table = 'resource_owners';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'resource_type',
        'resource_id',
        'owner_id',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
    
    /**
     * Set the owner of a resource
     * 
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param int $ownerId ID of the user who owns the resource
     * @return bool True on success, false on failure
     */
    public function setOwner(string $resourceType, int $resourceId, int $ownerId): bool
    {
        try {
            // Check if owner already exists for this resource
            $existing = $this->where('resource_type', $resourceType)
                ->where('resource_id', $resourceId)
                ->first();
            
            if ($existing) {
                // Update existing owner
                $data = [
                    'owner_id' => $ownerId,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                
                return $this->update($existing['id'], $data);
            }
            
            // Insert new owner
            $data = [
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'owner_id' => $ownerId,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            return $this->insert($data) !== false;
        } catch (\Exception $e) {
            log_message('error', 'Error setting resource owner: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the owner of a resource
     * 
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return int|null Owner ID or null if not found
     */
    public function getOwner(string $resourceType, int $resourceId): ?int
    {
        try {
            $query = $this->where('resource_type', $resourceType)
                ->where('resource_id', $resourceId)
                ->get();
            
            $result = $query->getRow();
            
            return $result ? $result->owner_id : null;
        } catch (\Exception $e) {
            log_message('error', 'Error getting resource owner: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if a user is the owner of a resource
     * 
     * @param int $userId ID of the user
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return bool True if user is owner, false otherwise
     */
    public function isOwner(int $userId, string $resourceType, int $resourceId): bool
    {
        try {
            $ownerId = $this->getOwner($resourceType, $resourceId);
            
            return $ownerId === $userId;
        } catch (\Exception $e) {
            log_message('error', 'Error checking resource ownership: ' . $e->getMessage());
            return false;
        }
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
        try {
            $builder = $this->db->table($this->table);
            $builder->where('owner_id', $userId);
            
            if ($resourceType !== null) {
                $builder->where('resource_type', $resourceType);
            }
            
            $query = $builder->get();
            return $query->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Error getting resources owned by user: ' . $e->getMessage());
            return [];
        }
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
    public function transferOwnership(string $resourceType, int $resourceId, int $newOwnerId, ?int $transferredBy = null): bool
    {
        try {
            // Get current owner
            $currentOwnerId = $this->getOwner($resourceType, $resourceId);
            
            if ($currentOwnerId === null) {
                // Resource doesn't have an owner yet, set new owner
                return $this->setOwner($resourceType, $resourceId, $newOwnerId);
            }
            
            if ($currentOwnerId === $newOwnerId) {
                // Already owned by the new owner
                return true;
            }
            
            // Only allow owner or superadmin to transfer ownership
            $currentUser = auth()->user();
            if ($currentUser->id !== $currentOwnerId && !$currentUser->inGroup('superadmin')) {
                return false;
            }
            
            // Update owner
            $data = [
                'owner_id' => $newOwnerId,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            $result = $this->where('resource_type', $resourceType)
                ->where('resource_id', $resourceId)
                ->update($data);
            
            // Log the transfer
            if ($result) {
                $auditLogger = service('auditLogger');
                $auditLogger->logUserAction(
                    $transferredBy ?? $currentUser->id,
                    $newOwnerId,
                    'transfer',
                    'ownership',
                    $resourceType,
                    $resourceId,
                    'allowed',
                    "Transferred ownership from user {$currentOwnerId} to user {$newOwnerId}"
                );
            }
            
            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Error transferring resource ownership: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove ownership of a resource
     * 
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return bool True on success, false on failure
     */
    public function removeOwnership(string $resourceType, int $resourceId): bool
    {
        try {
            // Only allow superadmin to remove ownership
            $currentUser = auth()->user();
            if (!$currentUser->inGroup('superadmin')) {
                return false;
            }
            
            $result = $this->where('resource_type', $resourceType)
                ->where('resource_id', $resourceId)
                ->delete();
            
            // Log the removal
            if ($result) {
                $auditLogger = service('auditLogger');
                $auditLogger->logUserAction(
                    $currentUser->id,
                    null,
                    'remove',
                    'ownership',
                    $resourceType,
                    $resourceId,
                    'allowed',
                    "Removed ownership of resource"
                );
            }
            
            return $result;
        } catch (\Exception $e) {
            log_message('error', 'Error removing resource ownership: ' . $e->getMessage());
            return false;
        }
    }
}