<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Libraries\PermissionManager;
use App\Libraries\AuditLogger;

class PermissionSystemFeatureTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = ['App'];

    public function testPermissionManagerServiceCreation()
    {
        // Test that we can get the PermissionManager service
        $permissionManager = service('permissions');
        $this->assertInstanceOf(PermissionManager::class, $permissionManager);
    }

    public function testAuditLoggerServiceCreation()
    {
        // Test that we can get the AuditLogger service
        $auditLogger = service('auditLogger');
        $this->assertInstanceOf(AuditLogger::class, $auditLogger);
    }

    public function testPermissionManagerMethods()
    {
        // Test that PermissionManager has expected methods
        $permissionManager = service('permissions');
        
        // Test can method exists
        $this->assertTrue(method_exists($permissionManager, 'can'), 'PermissionManager should have a can() method');
        
        // Test grantResourcePermission method exists
        $this->assertTrue(method_exists($permissionManager, 'grantResourcePermission'), 'PermissionManager should have a grantResourcePermission() method');
        
        // Test revokeResourcePermission method exists
        $this->assertTrue(method_exists($permissionManager, 'revokeResourcePermission'), 'PermissionManager should have a revokeResourcePermission() method');
    }

    public function testAuditLoggerMethods()
    {
        // Test that AuditLogger has expected methods
        $auditLogger = service('auditLogger');
        
        // Test log method exists
        $this->assertTrue(method_exists($auditLogger, 'log'), 'AuditLogger should have a log() method');
        
        // Test getRecentLogs method exists
        $this->assertTrue(method_exists($auditLogger, 'getRecentLogs'), 'AuditLogger should have a getRecentLogs() method');
    }
}