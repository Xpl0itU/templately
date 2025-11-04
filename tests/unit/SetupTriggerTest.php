<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class SetupTriggerTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $namespace = ['App'];
    protected $refresh   = true;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations to ensure tables exist
        $this->migrateDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testSetupTriggeredWhenNoUsersExist(): void
    {
        // Ensure the users table exists but is empty
        $db = db_connect();
        $this->assertTrue($db->tableExists('users'), 'Users table should exist');
        
        // Clear any existing users (use DELETE instead of TRUNCATE to avoid FK constraint issues)
        $db->table('users')->emptyTable();

        // Ensure setup completion cache is cleared
        cache()->delete('app_setup_completed');
        
        // Count users to confirm table is empty
        $userCount = $db->table('users')->countAllResults();
        $this->assertEquals(0, $userCount, 'Users table should be empty');
        
        // Try to access a protected route
        $result = $this->get('/dashboard');

        // Should redirect to setup
        $result->assertRedirect();
        $result->assertRedirectTo(site_url('setup'));
    }

    public function testSetupTriggeredWhenUsersTableDoesNotExist(): void
    {
        $db = db_connect();
        $forge = \Config\Database::forge();
        
        // Clear setup completion cache
        cache()->delete('app_setup_completed');
        
        // Drop the users table temporarily
        if ($db->tableExists('users')) {
            $forge->dropTable('users', true);
        }
        
        // Verify table doesn't exist
        $this->assertFalse($db->tableExists('users'), 'Users table should not exist');
        
        // Try to access a protected route
        $result = $this->get('/dashboard');

        // Should redirect to setup
        $result->assertRedirect();
        $result->assertRedirectTo(site_url('setup'));
        
        // Recreate the users table for other tests
        $this->migrateDatabase();
    }
}