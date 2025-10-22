<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Shield\Authentication\Authenticators\Session;

/**
 * User Group Member Model
 * 
 * Manages user-to-group memberships with synchronization support
 */
class UserGroupMemberModel extends Model
{
    protected $table = 'user_group_members';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'group_id',
        'added_by',
        'created_at',
    ];
    protected $useTimestamps = false;
    
    /**
     * Get all members of a group
     * 
     * @param int $groupId Group ID
     * @return array Array of group members
     */
    public function getGroupMembers(int $groupId): array
    {
        return $this->where('group_id', $groupId)->findAll();
    }
    
    /**
     * Get all groups a user belongs to
     * 
     * @param int $userId User ID
     * @return array Array of user groups
     */
    public function getUserGroups(int $userId): array
    {
        return $this->where('user_id', $userId)->findAll();
    }

    /**
     * Synchronize a user's group memberships
     *
     * @param int $userId User ID
     * @param array<int> $groupIds Group IDs to keep
     * @param int|null $updatedBy Acting user
     * @return bool
     */
    public function syncUserGroups(int $userId, array $groupIds, ?int $updatedBy = null): bool
    {
        $groupIds = array_values(array_unique(array_map('intval', $groupIds)));

        $this->db->transStart();

        $existing = $this->where('user_id', $userId)->findAll();
        $existingIds = array_map(static fn(array $row) => (int) $row['group_id'], $existing);

        $toRemove = array_diff($existingIds, $groupIds);
        if (!empty($toRemove)) {
            $this->where('user_id', $userId)
                ->whereIn('group_id', array_values($toRemove))
                ->delete();
        }

        $toAdd = array_diff($groupIds, $existingIds);
        foreach ($toAdd as $groupId) {
            $this->insert([
                'user_id' => $userId,
                'group_id' => $groupId,
                'added_by' => $updatedBy,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->transComplete();

        return $this->db->transStatus();
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
        // Check if user is already in the group
        $existing = $this->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->first();
            
        if ($existing) {
            return $existing['id']; // Return existing membership ID
        }
        
        $data = [
            'user_id' => $userId,
            'group_id' => $groupId,
            'added_by' => $addedBy,
            'created_at' => date('Y-m-d H:i:s'),
        ];
        
        return $this->insert($data);
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
        return $this->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->delete();
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
        return $this->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->countAllResults() > 0;
    }
    
    /**
     * Get the count of members in a group
     * 
     * @param int $groupId Group ID
     * @return int Number of members in the group
     */
    public function getGroupMemberCount(int $groupId): int
    {
        return $this->where('group_id', $groupId)->countAllResults();
    }
    
    /**
     * Get detailed information about group members including user details
     * 
     * @param int $groupId Group ID
     * @return array Array of group members with user details
     */
    public function getGroupMembersWithDetails(int $groupId): array
    {
        $authConfig      = config('Auth');
        $usersTable      = $authConfig->tables['users'] ?? 'users';
        $identitiesTable = $authConfig->tables['identities'] ?? 'auth_identities';

        $builder = $this->db->table($this->table . ' ugm');
        $builder->select('ugm.*, u.username, ai.secret AS email');
        $builder->join($usersTable . ' u', 'ugm.user_id = u.id');
        $builder->join(
            $identitiesTable . ' ai',
            'ai.user_id = u.id AND ai.type = ' . $this->db->escape(Session::ID_TYPE_EMAIL_PASSWORD),
            'left'
        );
        $builder->where('ugm.group_id', $groupId);
        $builder->orderBy('u.username', 'ASC');

        $results = $builder->get()->getResultArray();

        return array_map(static function (array $row): array {
            if (! array_key_exists('email', $row) || $row['email'] === null) {
                $row['email'] = '';
            }

            return $row;
        }, $results);
    }
}