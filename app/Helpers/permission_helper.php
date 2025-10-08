<?php

use App\Libraries\PermissionManager;

if (!function_exists('has_permission')) {
    /**
     * Check if the current user has a specific permission
     * 
     * @param string $permission The permission to check
     * @param string|null $resourceType Type of resource (optional)
     * @param int|null $resourceId ID of the specific resource (optional)
     * @param array $context Additional context for ABAC evaluation (optional)
     * @return bool True if user has permission, false otherwise
     */
    function has_permission(
        string $permission, 
        ?string $resourceType = null, 
        ?int $resourceId = null, 
        array $context = []
    ): bool {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }
        
        // Get the PermissionManager service
        $permissionManager = service('permissions');
        
        return $permissionManager->can($user, $permission, $resourceType, $resourceId, $context);
    }
}

if (!function_exists('require_permission')) {
    /**
     * Require a specific permission, redirecting if not available
     * 
     * @param string $permission The permission required
     * @param string|null $resourceType Type of resource (optional)
     * @param int|null $resourceId ID of the specific resource (optional)
     * @param array $context Additional context for ABAC evaluation (optional)
     * @param string $redirectUrl URL to redirect to if permission denied
     * @param string $errorMessage Error message to display
     * @return void
     */
    function require_permission(
        string $permission, 
        ?string $resourceType = null, 
        ?int $resourceId = null, 
        array $context = [],
        string $redirectUrl = '/',
        string $errorMessage = 'You do not have permission to access this resource.'
    ): void {
        if (!has_permission($permission, $resourceType, $resourceId, $context)) {
            // Log the denied access attempt
            $auditLogger = service('auditLogger');
            $user = auth()->user();
            $userId = $user ? $user->id : null;
            $auditLogger->log($userId, 'access_denied', $permission, $resourceType, $resourceId, 'denied', $errorMessage);
            
            // Redirect with error message
            exit(redirect()->to($redirectUrl)->with('error', $errorMessage)->send());
        }
    }
}

if (!function_exists('grant_resource_permission')) {
    /**
     * Grant a resource-specific permission to a user
     * 
     * @param int $userId The user ID
     * @param string $permission The permission to grant
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @param string|null $scope Scope of the permission
     * @param string|null $expiresAt Optional expiration date
     * @return bool True on success, false on failure
     */
    function grant_resource_permission(
        int $userId, 
        string $permission, 
        string $resourceType, 
        int $resourceId, 
        ?string $scope = null, 
        ?string $expiresAt = null
    ): bool {
        $permissionManager = service('permissions');
        return $permissionManager->grantResourcePermission($userId, $permission, $resourceType, $resourceId, $scope, $expiresAt);
    }
}

if (!function_exists('revoke_resource_permission')) {
    /**
     * Revoke a resource-specific permission from a user
     * 
     * @param int $userId The user ID
     * @param string $permission The permission to revoke
     * @param string $resourceType Type of resource
     * @param int $resourceId ID of the specific resource
     * @return bool True on success, false on failure
     */
    function revoke_resource_permission(
        int $userId, 
        string $permission, 
        string $resourceType, 
        int $resourceId
    ): bool {
        $permissionManager = service('permissions');
        return $permissionManager->revokeResourcePermission($userId, $permission, $resourceType, $resourceId);
    }
}