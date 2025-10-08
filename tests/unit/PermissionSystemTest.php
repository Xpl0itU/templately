<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Libraries\PermissionManager;
use App\Libraries\AuditLogger;
use App\Models\ResourcePermissionModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

class PermissionSystemTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = ['App'];
    protected $refresh   = true;

    protected $permissionManager;
    protected $auditLogger;
    protected $resourcePermissionModel;
    protected $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize test components
        $this->permissionManager = new PermissionManager();
        $this->auditLogger = new AuditLogger();
        $this->resourcePermissionModel = new ResourcePermissionModel();
        
        // Create a test user
        $this->testUser = new User([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        
        // Save the test user
        $userModel = model('CodeIgniter\Shield\Models\UserModel');
        $userModel->save($this->testUser);
        $this->testUser = $userModel->findById($userModel->getInsertID());

        if (function_exists('auth')) {
            auth()->login($this->testUser);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (function_exists('auth')) {
            auth()->logout();
        }
        
        // Clean up test data
        if ($this->testUser) {
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $userModel->delete($this->testUser->id);
        }
    }

    public function testBasicPermissionCheck()
    {
        // Test that a user without permissions is denied
        $result = $this->permissionManager->can($this->testUser, 'templates.view');
        $this->assertFalse($result, 'User without permissions should be denied');
        
        // Test that a user with direct permissions is allowed
        $this->testUser->addPermission('templates.view');
        $this->refreshTestUser();
        $result = $this->permissionManager->can($this->testUser, 'templates.view');
        $this->assertTrue($result, 'User with direct permissions should be allowed');
    }

    public function testGroupPermissionCheck()
    {
        // Test that a user in a group with permissions is allowed
        $this->testUser->addGroup('user');
        $this->refreshTestUser();
        $result = $this->permissionManager->can($this->testUser, 'templates.view');
        $this->assertTrue($result, 'User in group with permissions should be allowed');
        
        // Test that a user in a group without permissions is denied
        $result = $this->permissionManager->can($this->testUser, 'admin.access');
        $this->assertFalse($result, 'User in group without permissions should be denied');
    }

    public function testResourceSpecificPermissionCheck()
    {
        if (function_exists('auth')) {
            auth()->logout();
        }

        // Create a test template
        $templateModel = model('App\Models\TemplateModel');
        $templateId = $templateModel->insert([
            'name' => 'Test Template',
            'description' => 'A test template for permissions',
            'templateFields' => json_encode([]),
            'path' => 'uploads/templates/test-template.docx',
            'size' => 1024,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s'),
        ]);

        if (function_exists('auth')) {
            auth()->login($this->testUser);
        }
        $this->refreshTestUser();
        
        // Test that a user without resource-specific permissions is denied
        $result = $this->permissionManager->can($this->testUser, 'templates.view', 'template', $templateId);
        $this->assertFalse($result, 'User without resource-specific permissions should be denied');
        
        // Grant resource-specific permission
        $userModel = model('CodeIgniter\Shield\Models\UserModel');
        $superAdmin = new User([
            'username' => 'superadmin_granter',
            'email' => 'superadmin_granter@example.com',
            'password' => 'password123',
        ]);
        $userModel->save($superAdmin);
        $superAdmin = $userModel->findById($userModel->getInsertID());

        $authConfig = config('Auth');
        $groupsUsersTable = $authConfig->tables['groups_users'] ?? 'auth_groups_users';
        $db = db_connect();
        $db->table($groupsUsersTable)->insert([
            'user_id' => $superAdmin->id,
            'group' => 'superadmin',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (function_exists('auth')) {
            auth()->logout();
            auth()->login($superAdmin);
        }

        $granted = $this->permissionManager->grantResourcePermission(
            $this->testUser->id, 
            'templates.view', 
            'template', 
            $templateId
        );
        $this->assertTrue($granted, 'Resource permission should be granted successfully');

        if (function_exists('auth')) {
            auth()->logout();
            auth()->login($this->testUser);
        }
        $this->refreshTestUser();

        $resourcePermissions = $this->permissionManager->getUserResourcePermissionsForResource(
            $this->testUser->id,
            'template',
            $templateId
        );
        $this->assertNotEmpty($resourcePermissions, 'Resource permission record should exist after granting');
        $this->assertSame('templates.view', $resourcePermissions[0]['permission']);

        $directModelCheck = $this->resourcePermissionModel->userHasResourcePermission(
            $this->testUser->id,
            'templates.view',
            'template',
            $templateId
        );
        $this->assertTrue($directModelCheck, 'ResourcePermissionModel should confirm permission');

        $cacheKey = 'perm_check_user_' . $this->testUser->id . '_' . md5('templates.view' . 'template' . $templateId . serialize([]));
        cache()->delete($cacheKey);

        $directCheck = $this->permissionManager->hasPermission(
            $this->testUser->id,
            'user',
            'templates.view',
            'template',
            $templateId
        );
        $this->assertTrue($directCheck, 'Direct permission check should succeed after granting');

        cache()->save($cacheKey, '0', 300);

        $directCheckWithUnexpectedCache = $this->permissionManager->hasPermission(
            $this->testUser->id,
            'user',
            'templates.view',
            'template',
            $templateId
        );
        $this->assertTrue(
            $directCheckWithUnexpectedCache,
            'Direct permission check should ignore unexpected cached values'
        );
        
        // Test that a user with resource-specific permissions is allowed
        $result = $this->permissionManager->can($this->testUser, 'templates.view', 'template', $templateId);
        $this->assertTrue($result, 'User with resource-specific permissions should be allowed');
        
        // Clean up
        if (isset($superAdmin)) {
            $userModel->delete($superAdmin->id, true);
        }
        $templateModel->delete($templateId);
    }

    public function testPermissionCaching()
    {
        $previouslyAuthenticatedUser = null;
        if (function_exists('auth')) {
            $authService = auth();
            if ($authService !== null) {
                $previouslyAuthenticatedUser = $authService->user();
                if ($previouslyAuthenticatedUser !== null) {
                    $authService->logout();
                }
            }
        }

        $templateModel = model('App\Models\TemplateModel');
        $templateId = $templateModel->insert([
            'name' => 'Caching Template',
            'description' => 'Template used for cache assertions',
            'templateFields' => json_encode([]),
            'path' => 'uploads/templates/caching-template.docx',
            'size' => 512,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s'),
        ]);

        if ($previouslyAuthenticatedUser !== null && function_exists('auth')) {
            auth()->login($previouslyAuthenticatedUser);
        }

        $cacheKey = 'perm_check_user_' . $this->testUser->id . '_' . md5('templates.view' . 'template' . $templateId . serialize([]));
        cache()->delete($cacheKey);

        $initialCacheValue = cache()->get($cacheKey);
        $this->assertTrue(
            in_array($initialCacheValue, [false, null], true),
            'Cache should start empty'
        );

        $result1 = $this->permissionManager->hasPermission(
            $this->testUser->id,
            'user',
            'templates.view',
            'template',
            $templateId
        );
        $this->assertFalse($result1, 'Initial permission check should be denied');
        $this->assertSame('deny', cache()->get($cacheKey), 'Denied result should be cached as "deny"');

        $grantResult = $this->resourcePermissionModel->grantResourcePermission(
            $this->testUser->id,
            'templates.view',
            'template',
            $templateId
        );
        $this->assertTrue($grantResult, 'Resource permission grant should succeed');

        cache()->delete($cacheKey);

        $result2 = $this->permissionManager->hasPermission(
            $this->testUser->id,
            'user',
            'templates.view',
            'template',
            $templateId
        );
        $this->assertTrue($result2, 'Permission should be allowed after granting');
        $this->assertSame('allow', cache()->get($cacheKey), 'Allowed result should be cached as "allow"');

        $this->resourcePermissionModel->revokeResourcePermission(
            $this->testUser->id,
            'templates.view',
            'template',
            $templateId
        );
        $templateModel->delete($templateId);
    }

    public function testAuditLogging()
    {
        // Test that permission checks are logged
        $logsBefore = count($this->auditLogger->getRecentLogs(100));
        
        $this->permissionManager->can($this->testUser, 'templates.view');
        
        $logsAfter = count($this->auditLogger->getRecentLogs(100));
        
        $this->assertGreaterThan($logsBefore, $logsAfter, 'Permission check should be logged');
    }

    public function testResourcePermissionGranting()
    {
        // Create a test template
        $templateModel = model('App\Models\TemplateModel');
        $templateId = $templateModel->insert([
            'name' => 'Test Template 2',
            'description' => 'Another test template',
            'templateFields' => json_encode([]),
            'path' => 'uploads/templates/test-template-2.docx',
            'size' => 2048,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s'),
        ]);
        
        // Grant resource-specific permission
        $granted = $this->permissionManager->grantResourcePermission(
            $this->testUser->id, 
            'templates.edit', 
            'template', 
            $templateId
        );
        $this->assertTrue($granted, 'Resource permission should be granted successfully');
        
        // Verify the permission was granted
        $permissions = $this->permissionManager->getUserResourcePermissionsForResource(
            $this->testUser->id, 
            'template', 
            $templateId
        );
        $this->assertCount(1, $permissions, 'User should have one resource-specific permission');
        $this->assertEquals('templates.edit', $permissions[0]['permission']);
        
        // Revoke the permission
        $revoked = $this->permissionManager->revokeResourcePermission(
            $this->testUser->id, 
            'templates.edit', 
            'template', 
            $templateId
        );
        $this->assertTrue($revoked, 'Resource permission should be revoked successfully');
        
        // Verify the permission was revoked
        $permissions = $this->permissionManager->getUserResourcePermissionsForResource(
            $this->testUser->id, 
            'template', 
            $templateId
        );
        $this->assertCount(0, $permissions, 'User should have no resource-specific permissions after revoking');
        
        // Clean up
        $templateModel->delete($templateId);
    }

    public function testWildcardPermissionMatching()
    {
        // Add user to admin group which has 'templates.*' permission
        $this->testUser->addGroup('admin');
    $this->refreshTestUser();
        
        // Test that wildcard permissions work
        $result = $this->permissionManager->can($this->testUser, 'templates.view');
        $this->assertTrue($result, 'Wildcard permission should match view action');
        
        $result = $this->permissionManager->can($this->testUser, 'templates.edit');
        $this->assertTrue($result, 'Wildcard permission should match edit action');
        
        $result = $this->permissionManager->can($this->testUser, 'templates.delete');
        $this->assertTrue($result, 'Wildcard permission should match delete action');
    }

    public function testAuditLogSearch()
    {
        // Perform some actions to generate logs
        $this->permissionManager->can($this->testUser, 'templates.view');
        $this->permissionManager->can($this->testUser, 'templates.edit');
        
        // Search for logs
        $logs = $this->auditLogger->searchLogs(['user_id' => $this->testUser->id]);
        $this->assertGreaterThanOrEqual(2, count($logs), 'Should find at least 2 logs for the user');
        
        // Search for specific permission logs
        $logs = $this->auditLogger->getPermissionLogs('templates.view');
        $this->assertGreaterThanOrEqual(1, count($logs), 'Should find logs for templates.view permission');
    }

    public function testAuditLogStatistics()
    {
        // Perform some actions to generate logs
        $this->permissionManager->can($this->testUser, 'templates.view');
        $this->permissionManager->can($this->testUser, 'templates.edit');
        $this->permissionManager->can($this->testUser, 'templates.delete');
        
        // Get statistics
        $stats = $this->auditLogger->getStatistics();
        
        $this->assertArrayHasKey('by_action', $stats);
        $this->assertArrayHasKey('by_result', $stats);
        $this->assertArrayHasKey('by_date', $stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertGreaterThanOrEqual(3, $stats['total'], 'Should have at least 3 total logs');
    }

    private function refreshTestUser(): void
    {
        /** @var ShieldUserModel $userModel */
        $userModel      = model('CodeIgniter\Shield\Models\UserModel');
        $this->testUser = $userModel->findById($this->testUser->id);

        if (function_exists('auth')) {
            auth()->logout();
            auth()->login($this->testUser);
        }
    }
}