<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * User Group Model
 *
 * Manages user groups with member management functionality
 */
class UserGroupModel extends Model
{
    protected $table = 'user_groups';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name',
        'description',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;

    /**
     * Get all user groups with member counts
     *
     * @return array Array of user groups with member counts
     */
    public function getAllGroupsWithMemberCounts(): array
    {
        $groups = $this->findAll();

        foreach ($groups as &$group) {
            $group['member_count'] = $this->getGroupMemberCount($group['id']);
        }

        return $groups;
    }

    /**
     * Get all user groups
     *
     * @return array Array of user groups
     */
    public function getAllGroups(): array
    {
        return $this->findAll();
    }

    /**
     * Get all user groups with membership information for a specific user
     *
     * @param int $userId
     * @return array
     */
    public function getGroupsForUser(int $userId): array
    {
        $builder = $this->db->table($this->table . ' ug');
        $builder->select('ug.id, ug.name, ug.description, ug.created_at, ug.updated_at');
        $builder->join('user_group_members ugm', 'ugm.group_id = ug.id');
        $builder->where('ugm.user_id', $userId);

        return $builder->get()->getResultArray();
    }

    /**
     * Get the IDs of the groups a user belongs to
     *
     * @param int $userId
     * @return array<int>
     */
    public function getGroupIdsForUser(int $userId): array
    {
        $groups = $this->getGroupsForUser($userId);

        return array_column($groups, 'id');
    }

    /**
     * Get a user group by ID
     *
     * @param int $groupId Group ID
     * @return array|null Group data or null if not found
     */
    public function getGroup(int $groupId): ?array
    {
        return $this->find($groupId);
    }

    /**
     * Get a user group by name
     *
     * @param string $groupName Group name
     * @return array|null Group data or null if not found
     */
    public function getGroupByName(string $groupName): ?array
    {
        return $this->where('name', $groupName)->first();
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
        $data = [
            'name' => $name,
            'description' => $description,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return $this->insert($data);
    }

    /**
     * Update a user group
     *
     * @param int $groupId Group ID
     * @param array $data Group data to update
     * @return bool True on success, false on failure
     */
    public function updateGroup(int $groupId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($groupId, $data);
    }

    /**
     * Delete a user group
     *
     * @param int $groupId Group ID
     * @return bool True on success, false on failure
     */
    public function deleteGroup(int $groupId): bool
    {
        return $this->delete($groupId);
    }

    /**
     * Get all members of a group
     *
     * @param int $groupId Group ID
     * @return array Array of group members
     */
    public function getGroupMembers(int $groupId): array
    {
        $userGroupMemberModel = model('App\Models\UserGroupMemberModel');

        return $userGroupMemberModel->getGroupMembers($groupId);
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
        $userGroupMemberModel = model('App\Models\UserGroupMemberModel');

        return $userGroupMemberModel->addUserToGroup($userId, $groupId, $addedBy);
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
        $userGroupMemberModel = model('App\Models\UserGroupMemberModel');

        return $userGroupMemberModel->removeUserFromGroup($userId, $groupId);
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
        $userGroupMemberModel = model('App\Models\UserGroupMemberModel');

        return $userGroupMemberModel->isUserInGroup($userId, $groupId);
    }

    /**
     * Get detailed information about group members including user details
     *
     * @param int $groupId Group ID
     * @return array Array of group members with user details
     */
    public function getGroupMembersWithDetails(int $groupId): array
    {
        $userGroupMemberModel = model('App\Models\UserGroupMemberModel');

        return $userGroupMemberModel->getGroupMembersWithDetails($groupId);
    }

    /**
     * Get the count of members in a group
     *
     * @param int $groupId Group ID
     * @return int Number of members in the group
     */
    public function getGroupMemberCount(int $groupId): int
    {
        $userGroupMemberModel = model('App\Models\UserGroupMemberModel');

        return $userGroupMemberModel->getGroupMemberCount($groupId);
    }
}
