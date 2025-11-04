<?php

namespace App\Libraries;

use Config\Database;

class AuditLogger
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * Log a permission-related action
     *
     * @param int|null $userId User who performed the action
     * @param string $action Type of action (grant, revoke, check, etc.)
     * @param string|null $permission Specific permission involved
     * @param string|null $resourceType Type of resource affected
     * @param int|null $resourceId ID of specific resource affected
     * @param string $result Result of the action (allowed, denied, error)
     * @param string|null $details Additional details
     * @return bool True on success, false on failure
     */
    public function log(
        ?int $userId,
        string $action,
        ?string $permission = null,
        ?string $resourceType = null,
        ?int $resourceId = null,
        string $result = 'allowed',
        ?string $details = null
    ): bool {
        try {
            // Get request information
            $request = \Config\Services::request();
            $ipAddress = $request->getIPAddress();
            $userAgent = $request->getUserAgent() ? $request->getUserAgent()->getAgentString() : null;

            $data = [
                'user_id' => $userId,
                'target_user_id' => null, // This would be set for actions affecting other users
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'permission' => $permission,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'result' => $result,
                'details' => $details,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->table('permission_audit_log')->insert($data);

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error logging permission audit: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log an action affecting another user
     *
     * @param int|null $userId User who performed the action
     * @param int|null $targetUserId User affected by the action
     * @param string $action Type of action (grant, revoke, etc.)
     * @param string|null $permission Specific permission involved
     * @param string|null $resourceType Type of resource affected
     * @param int|null $resourceId ID of specific resource affected
     * @param string|null $details Additional details
     * @return bool True on success, false on failure
     */
    public function logUserAction(
        ?int $userId,
        ?int $targetUserId,
        string $action,
        ?string $permission = null,
        ?string $resourceType = null,
        ?int $resourceId = null,
        ?string $details = null
    ): bool {
        try {
            // Get request information
            $request = \Config\Services::request();
            $ipAddress = $request->getIPAddress();
            $userAgent = $request->getUserAgent() ? $request->getUserAgent()->getAgentString() : null;

            $data = [
                'user_id' => $userId,
                'target_user_id' => $targetUserId,
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'permission' => $permission,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'result' => 'allowed', // User management actions are typically allowed if they reach this point
                'details' => $details,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->table('permission_audit_log')->insert($data);

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error logging user action audit: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log a permission check (access control decision)
     *
     * @param int|null $userId User who attempted access
     * @param string $permission Permission being checked
     * @param string|null $resourceType Type of resource
     * @param int|null $resourceId ID of specific resource
     * @param string $result Result of the check (allowed, denied)
     * @param string|null $reason Reason for the decision
     * @return bool True on success, false on failure
     */
    public function logPermissionCheck(
        ?int $userId,
        string $permission,
        ?string $resourceType = null,
        ?int $resourceId = null,
        string $result = 'denied',
        ?string $reason = null
    ): bool {
        try {
            // Get request information
            $request = \Config\Services::request();
            $ipAddress = $request->getIPAddress();
            $userAgent = $request->getUserAgent() ? $request->getUserAgent()->getAgentString() : null;

            $data = [
                'user_id' => $userId,
                'target_user_id' => null,
                'action' => 'check',
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'permission' => $permission,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'result' => $result,
                'details' => $reason,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->table('permission_audit_log')->insert($data);

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error logging permission check audit: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log a resource access attempt
     *
     * @param int|null $userId User who attempted access
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of specific resource
     * @param string $action Action attempted (view, edit, delete, etc.)
     * @param string $result Result of the attempt (allowed, denied)
     * @param string|null $reason Reason for the decision
     * @return bool True on success, false on failure
     */
    public function logResourceAccess(
        ?int $userId,
        string $resourceType,
        int $resourceId,
        string $action,
        string $result = 'denied',
        ?string $reason = null
    ): bool {
        try {
            // Get request information
            $request = \Config\Services::request();
            $ipAddress = $request->getIPAddress();
            $userAgent = $request->getUserAgent() ? $request->getUserAgent()->getAgentString() : null;

            $data = [
                'user_id' => $userId,
                'target_user_id' => null,
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'permission' => "{$resourceType}.{$action}",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'result' => $result,
                'details' => $reason,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            $this->db->table('permission_audit_log')->insert($data);

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error logging resource access audit: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent audit logs
     *
     * @param int $limit Number of logs to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of audit logs
     */
    public function getRecentLogs(int $limit = 50, int $offset = 0): array
    {
        try {
            $query = $this->db->table('permission_audit_log pal')
                ->select('pal.*, u.username as user_username, tu.username as target_username')
                ->join('users u', 'pal.user_id = u.id', 'left')
                ->join('users tu', 'pal.target_user_id = tu.id', 'left')
                ->orderBy('pal.created_at', 'DESC')
                ->limit($limit, $offset)
                ->get();

            return $query->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Error retrieving audit logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get audit logs for a specific user
     *
     * @param int $userId User ID
     * @param int $limit Number of logs to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of audit logs
     */
    public function getUserLogs(int $userId, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = $this->db->table('permission_audit_log')
                ->where('user_id', $userId)
                ->orWhere('target_user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->limit($limit, $offset)
                ->get();

            return $query->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Error retrieving user audit logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get audit logs for a specific resource
     *
     * @param string $resourceType Resource type
     * @param int $resourceId Resource ID
     * @param int $limit Number of logs to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of audit logs
     */
    public function getResourceLogs(string $resourceType, int $resourceId, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = $this->db->table('permission_audit_log')
                ->where('resource_type', $resourceType)
                ->where('resource_id', $resourceId)
                ->orderBy('created_at', 'DESC')
                ->limit($limit, $offset)
                ->get();

            return $query->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Error retrieving resource audit logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get audit logs filtered by action type
     *
     * @param string $action Action type (grant, revoke, check, etc.)
     * @param int $limit Number of logs to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of audit logs
     */
    public function getActionLogs(string $action, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = $this->db->table('permission_audit_log pal')
                ->select('pal.*, u.username as user_username, tu.username as target_username')
                ->join('users u', 'pal.user_id = u.id', 'left')
                ->join('users tu', 'pal.target_user_id = tu.id', 'left')
                ->where('pal.action', $action)
                ->orderBy('pal.created_at', 'DESC')
                ->limit($limit, $offset)
                ->get();

            return $query->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Error retrieving action audit logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get audit logs for a specific permission
     *
     * @param string $permission Permission name
     * @param int $limit Number of logs to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of audit logs
     */
    public function getPermissionLogs(string $permission, int $limit = 50, int $offset = 0): array
    {
        try {
            $query = $this->db->table('permission_audit_log pal')
                ->select('pal.*, u.username as user_username, tu.username as target_username')
                ->join('users u', 'pal.user_id = u.id', 'left')
                ->join('users tu', 'pal.target_user_id = tu.id', 'left')
                ->where('pal.permission', $permission)
                ->orderBy('pal.created_at', 'DESC')
                ->limit($limit, $offset)
                ->get();

            return $query->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Error retrieving permission audit logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search audit logs with multiple criteria
     *
     * @param array $criteria Search criteria
     * @param int $limit Number of logs to retrieve
     * @param int $offset Offset for pagination
     * @return array Array of audit logs
     */
    public function searchLogs(array $criteria = [], int $limit = 50, int $offset = 0): array
    {
        try {
            $builder = $this->db->table('permission_audit_log pal');
            $builder->select('pal.*, u.username as user_username, tu.username as target_username');
            $builder->join('users u', 'pal.user_id = u.id', 'left');
            $builder->join('users tu', 'pal.target_user_id = tu.id', 'left');

            // Apply search criteria
            if (!empty($criteria['user_id'])) {
                $builder->where('pal.user_id', $criteria['user_id']);
            }

            if (!empty($criteria['target_user_id'])) {
                $builder->where('pal.target_user_id', $criteria['target_user_id']);
            }

            if (!empty($criteria['action'])) {
                $builder->where('pal.action', $criteria['action']);
            }

            if (!empty($criteria['permission'])) {
                $builder->where('pal.permission', $criteria['permission']);
            }

            if (!empty($criteria['resource_type'])) {
                $builder->where('pal.resource_type', $criteria['resource_type']);
            }

            if (!empty($criteria['resource_id'])) {
                $builder->where('pal.resource_id', $criteria['resource_id']);
            }

            if (!empty($criteria['result'])) {
                $builder->where('pal.result', $criteria['result']);
            }

            if (!empty($criteria['date_from'])) {
                $builder->where('pal.created_at >=', $criteria['date_from']);
            }

            if (!empty($criteria['date_to'])) {
                $builder->where('pal.created_at <=', $criteria['date_to']);
            }

            $builder->orderBy('pal.created_at', 'DESC');
            $builder->limit($limit, $offset);

            $query = $builder->get();
            return $query->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Error searching audit logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get audit log statistics
     *
     * @param string|null $period Time period (day, week, month)
     * @return array Statistics data
     */
    public function getStatistics(?string $period = null): array
    {
        try {
            $builder = $this->db->table('permission_audit_log');

            // Apply time period filter
            $dateColumn = 'DATE(created_at)';
            switch ($period) {
                case 'day':
                    $dateColumn = 'HOUR(created_at)';
                    $builder->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 day')));
                    break;
                case 'week':
                    $dateColumn = 'DATE(created_at)';
                    $builder->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 week')));
                    break;
                case 'month':
                    $dateColumn = 'DATE(created_at)';
                    $builder->where('created_at >=', date('Y-m-d H:i:s', strtotime('-1 month')));
                    break;
            }

            // Get total counts by action
            $actionStats = $builder->select("action, COUNT(*) as count")
                ->groupBy('action')
                ->get()
                ->getResultArray();

            // Get total counts by result
            $resultStats = $builder->select("result, COUNT(*) as count")
                ->groupBy('result')
                ->get()
                ->getResultArray();

            // Get counts by date
            $dateStats = $builder->select("{$dateColumn} as date, COUNT(*) as count")
                ->groupBy($dateColumn)
                ->orderBy($dateColumn, 'ASC')
                ->get()
                ->getResultArray();

            return [
                'by_action' => $actionStats,
                'by_result' => $resultStats,
                'by_date' => $dateStats,
                'total' => array_sum(array_column($actionStats, 'count'))
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error retrieving audit statistics: ' . $e->getMessage());
            return [
                'by_action' => [],
                'by_result' => [],
                'by_date' => [],
                'total' => 0
            ];
        }
    }
}
