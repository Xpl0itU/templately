<?php

namespace App\Services;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AuditLogger
{
    protected $db;
    protected $session;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->session = Services::session();
    }

    /**
     * Log a permission check attempt
     *
     * @param int $userId ID of the user making the request
     * @param string $permission The permission being checked
     * @param string|null $resourceType Type of resource (template, filled_file, etc.)
     * @param int|null $resourceId ID of the specific resource
     * @param string $result Result of the permission check ('allowed', 'denied')
     * @param string $reason Reason for the decision
     * @return bool True on success, false on failure
     */
    public function logPermissionCheck(int $userId, string $permission, ?string $resourceType, ?int $resourceId, string $result, string $reason = ''): bool
    {
        if (!$this->isAuditLogEnabled()) {
            return true;
        }

        try {
            $data = [
                'user_id' => $userId,
                'timestamp' => date('Y-m-d H:i:s'),
                'action' => 'permission_check',
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'permission' => $permission,
                'result' => $result,
                'reason' => $reason,
                'ip_address' => \Config\Services::request()->getIPAddress(),
                'user_agent' => \Config\Services::request()->getUserAgent()->getAgentString() ?? '',
            ];

            $this->db->table('audit_log')->insert($data);
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error logging permission check: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log a user action (grant, revoke, etc.)
     *
     * @param int $userId ID of the user initiating the action
     * @param int|null $targetUserId ID of the target user
     * @param string $action The action being performed
     * @param string $permission The permission being modified
     * @param string|null $resourceType Type of resource (template, filled_file, etc.)
     * @param int|null $resourceId ID of the specific resource
     * @param string $result Result of the action ('allowed', 'denied')
     * @param string $details Additional details about the action
     * @return bool True on success, false on failure
     */
    public function logUserAction(int $userId, ?int $targetUserId, string $action, string $permission, ?string $resourceType, ?int $resourceId, string $result, string $details = ''): bool
    {
        if (!$this->isAuditLogEnabled()) {
            return true;
        }

        try {
            $data = [
                'user_id' => $userId,
                'target_user_id' => $targetUserId,
                'timestamp' => date('Y-m-d H:i:s'),
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'permission' => $permission,
                'result' => $result,
                'reason' => $details,
                'ip_address' => \Config\Services::request()->getIPAddress(),
                'user_agent' => \Config\Services::request()->getUserAgent()->getAgentString() ?? '',
            ];

            $this->db->table('audit_log')->insert($data);
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error logging user action: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log a resource access attempt
     *
     * @param int|null $userId ID of the user making the request
     * @param string $resourceType Type of resource (template, filled_file, etc.)
     * @param int $resourceId ID of the specific resource
     * @param string $action Action being attempted (view, edit, delete, etc.)
     * @param string $result Result of the attempt ('allowed', 'denied')
     * @param string|null $reason Reason for the decision
     * @return bool True on success, false on failure
     */
    public function logResourceAccess(?int $userId, string $resourceType, int $resourceId, string $action, string $result = 'denied', ?string $reason = null): bool
    {
        if (!$this->isAuditLogEnabled()) {
            return true;
        }

        try {
            $data = [
                'user_id' => $userId,
                'timestamp' => date('Y-m-d H:i:s'),
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'result' => $result,
                'reason' => $reason,
                'ip_address' => \Config\Services::request()->getIPAddress(),
                'user_agent' => \Config\Services::request()->getUserAgent()->getAgentString() ?? '',
            ];

            $this->db->table('audit_log')->insert($data);
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error logging resource access: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log a permission inheritance event
     *
     * @param int $userId ID of the user involved
     * @param string $action The action (inherit, revoke_inheritance, etc.)
     * @param string|null $resourceType Type of resource (template, filled_file, etc.)
     * @param int|null $resourceId ID of the specific resource
     * @param string|null $sourceResourceType Type of source resource (template, etc.)
     * @param int|null $sourceResourceId ID of the source resource
     * @param string $result Result of the action ('allowed', 'denied')
     * @param string $details Additional details about the inheritance
     * @return bool True on success, false on failure
     */
    public function logPermissionInheritance(int $userId, string $action, ?string $resourceType, ?int $resourceId, ?string $sourceResourceType, ?int $sourceResourceId, string $result, string $details = ''): bool
    {
        if (!$this->isAuditLogEnabled()) {
            return true;
        }

        try {
            $data = [
                'user_id' => $userId,
                'timestamp' => date('Y-m-d H:i:s'),
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'permission' => 'inheritance',
                'result' => $result,
                'reason' => $details,
                'ip_address' => \Config\Services::request()->getIPAddress(),
                'user_agent' => \Config\Services::request()->getUserAgent()->getAgentString() ?? '',
            ];

            // Add source information
            $data['inheritance_source_type'] = $sourceResourceType;
            $data['inheritance_source_id'] = $sourceResourceId;

            $this->db->table('audit_log')->insert($data);
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error logging permission inheritance: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Query audit logs
     *
     * @param array $filters Filters to apply
     * @param int $limit Number of results to return
     * @param int $offset Number of results to skip
     * @return array Array of audit log entries
     */
    public function queryLogs(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        try {
            $builder = $this->db->table('audit_log');

            // Apply filters
            if (isset($filters['user_id']) && $filters['user_id']) {
                $builder->where('user_id', $filters['user_id']);
            }

            if (isset($filters['action']) && $filters['action']) {
                $builder->where('action', $filters['action']);
            }

            if (isset($filters['resource_type']) && $filters['resource_type']) {
                $builder->where('resource_type', $filters['resource_type']);
            }

            if (isset($filters['resource_id']) && $filters['resource_id']) {
                $builder->where('resource_id', $filters['resource_id']);
            }

            if (isset($filters['permission']) && $filters['permission']) {
                $builder->where('permission', $filters['permission']);
            }

            if (isset($filters['start_date']) && $filters['start_date']) {
                $builder->where('timestamp >=', $filters['start_date']);
            }

            if (isset($filters['end_date']) && $filters['end_date']) {
                $builder->where('timestamp <=', $filters['end_date']);
            }

            if (isset($filters['result']) && $filters['result']) {
                $builder->where('result', $filters['result']);
            }

            // Order by timestamp descending
            $builder->orderBy('timestamp', 'DESC');

            // Apply pagination
            $builder->limit($limit, $offset);

            $query = $builder->get();
            return $query->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Error querying audit logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Count audit logs based on filters
     *
     * @param array $filters Filters to apply
     * @return int Number of matching audit log entries
     */
    public function countLogs(array $filters = []): int
    {
        try {
            $builder = $this->db->table('audit_log');

            // Apply filters
            if (isset($filters['user_id']) && $filters['user_id']) {
                $builder->where('user_id', $filters['user_id']);
            }

            if (isset($filters['action']) && $filters['action']) {
                $builder->where('action', $filters['action']);
            }

            if (isset($filters['resource_type']) && $filters['resource_type']) {
                $builder->where('resource_type', $filters['resource_type']);
            }

            if (isset($filters['resource_id']) && $filters['resource_id']) {
                $builder->where('resource_id', $filters['resource_id']);
            }

            if (isset($filters['permission']) && $filters['permission']) {
                $builder->where('permission', $filters['permission']);
            }

            if (isset($filters['start_date']) && $filters['start_date']) {
                $builder->where('timestamp >=', $filters['start_date']);
            }

            if (isset($filters['end_date']) && $filters['end_date']) {
                $builder->where('timestamp <=', $filters['end_date']);
            }

            if (isset($filters['result']) && $filters['result']) {
                $builder->where('result', $filters['result']);
            }

            return $builder->countAllResults();
        } catch (\Exception $e) {
            log_message('error', 'Error counting audit logs: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if audit logging is enabled
     *
     * @return bool True if enabled, false otherwise
     */
    protected function isAuditLogEnabled(): bool
    {
        $aclSettingModel = model('App\Models\AclSettingsModel');
        return $aclSettingModel->getSetting('audit_log_enabled', true);
    }
}
