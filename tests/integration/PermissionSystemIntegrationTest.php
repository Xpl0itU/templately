<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Libraries\PermissionManager;
use App\Libraries\AuditLogger;
use App\Models\ResourcePermissionModel;

class PermissionSystemIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = ['App'];

    public function testPermissionManagerCanMethod()
    {
        // Test that PermissionManager can be instantiated and has the can method
        $permissionManager = new PermissionManager();
        $this->assertInstanceOf(PermissionManager::class, $permissionManager);
        $this->assertTrue(method_exists($permissionManager, 'can'), 'PermissionManager should have a can() method');
    }

    public function testAuditLoggerCanLog()
    {
        // Test that AuditLogger can be instantiated and has the log method
        $auditLogger = new AuditLogger();
        $this->assertInstanceOf(AuditLogger::class, $auditLogger);
        $this->assertTrue(method_exists($auditLogger, 'log'), 'AuditLogger should have a log() method');
    }

    public function testResourcePermissionModelCanBeInstantiated()
    {
        // Test that ResourcePermissionModel can be instantiated
        $resourcePermissionModel = new ResourcePermissionModel();
        $this->assertInstanceOf(ResourcePermissionModel::class, $resourcePermissionModel);
    }

    public function testServicesCanBeRetrieved()
    {
        // Test that services can be retrieved
        $permissionManager = service('permissions');
        $this->assertInstanceOf(PermissionManager::class, $permissionManager);
        
        $auditLogger = service('auditLogger');
        $this->assertInstanceOf(AuditLogger::class, $auditLogger);
    }
}