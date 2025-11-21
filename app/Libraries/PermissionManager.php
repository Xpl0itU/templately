<?php

namespace App\Libraries;

use CodeIgniter\Shield\Entities\User;

/**
 * Ultra-Simplified Permission Manager
 * 
 * Only handles resource ownership - owners have full access
 * Uses Shield's built-in permissions for global actions
 */
class PermissionManager
{
    protected $resourceOwnerModel;
    protected $userGroupModel;
    protected $cache;

    public function __construct()
    {
        $this->resourceOwnerModel = model('App\Models\ResourceOwnerModel');
        $this->userGroupModel = model('App\Models\UserGroupModel');
        $this->cache = \Config\Services::cache();
    }

    /**
     * Check if a user can perform an action
     * 
     * For global permissions (2 args): can($user, 'templates.view')
     * For resource permissions (4 args): can($user, 'read', 'template', 1)
     * 
     * @param User|null $user The user
     * @param string $permission Permission to check
     * @param string|null $resourceType Resource type (template, filled_file) - optional
     * @param int|null $resourceId Resource ID - optional
     * @return bool
     */
    public function can(?User $user, string $permission, ?string $resourceType = null, ?int $resourceId = null): bool
    {
        if ($user === null) {
            return false;
        }

        // Superadmins can do anything
        if ($user->inGroup('superadmin')) {
            return true;
        }

        // If no resource specified, check global permission via Shield
        if ($resourceType === null || $resourceId === null) {
            return $user->can($permission);
        }

        // For resource-specific permissions, only owners have access
        return $this->resourceOwnerModel->isOwner($user->id, $resourceType, $resourceId);
    }

    /**
     * Set resource owner
     */
    public function setOwner(string $resourceType, int $resourceId, int $ownerId): bool
    {
        return $this->resourceOwnerModel->setOwner($resourceType, $resourceId, $ownerId);
    }

    /**
     * Get resource owner
     */
    public function getOwner(string $resourceType, int $resourceId): ?int
    {
        return $this->resourceOwnerModel->getOwner($resourceType, $resourceId);
    }

    /**
     * Check if user is owner
     */
    public function isOwner(int $userId, string $resourceType, int $resourceId): bool
    {
        return $this->resourceOwnerModel->isOwner($userId, $resourceType, $resourceId);
    }

    /**
     * Transfer ownership
     */
    public function transferOwnership(string $resourceType, int $resourceId, int $newOwnerId): bool
    {
        return $this->resourceOwnerModel->transferOwnership($resourceType, $resourceId, $newOwnerId);
    }

    /**
     * Remove ownership for a resource (when deleting)
     */
    public function removeResourcePermissions(string $resourceType, int $resourceId): bool
    {
        return $this->resourceOwnerModel->removeOwnership($resourceType, $resourceId);
    }

    /**
     * Get all user groups
     */
    public function getAllGroups(): array
    {
        return $this->userGroupModel->getAllGroups();
    }

    /**
     * Get group by ID
     */
    public function getGroup(int $groupId): ?array
    {
        return $this->userGroupModel->getGroup($groupId);
    }

    /**
     * Create a new group
     */
    public function createGroup(string $name, ?string $description = null)
    {
        return $this->userGroupModel->createGroup($name, $description);
    }

    /**
     * Delete a group
     */
    public function deleteGroup(int $groupId): bool
    {
        return $this->userGroupModel->deleteGroup($groupId);
    }

    /**
     * Get users in a group
     */
    public function getGroupMembers(int $groupId): array
    {
        return $this->userGroupModel->getGroupMembersWithDetails($groupId);
    }

    /**
     * Add user to group
     */
    public function addUserToGroup(int $userId, int $groupId, ?int $addedBy = null)
    {
        return $this->userGroupModel->addUserToGroup($userId, $groupId, $addedBy);
    }

    /**
     * Remove user from group
     */
    public function removeUserFromGroup(int $userId, int $groupId): bool
    {
        return $this->userGroupModel->removeUserFromGroup($userId, $groupId);
    }
}
