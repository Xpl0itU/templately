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
        
        // Clear any existing users
        $db->table('users')->truncate();

    // Ensure setup completion cache is cleared
    cache()->delete('app_setup_completed');
        
        // Count users to confirm table is empty
        $userCount = $db->table('users')->countAllResults();
        $this->assertEquals(0, $userCount, 'Users table should be empty');
        
        // Try to access a protected route
        $result = $this->get('/dashboard');

        // Should redirect to setup
        $result->assertRedirect();
    $result->assertHeader('Location', 'http://example.com/index.php/setup');
    }

    public function testSetupTriggeredWhenUsersTableDoesNotExist(): void
    {
        // This test would require dropping the users table, which might affect other tests
        // In a real scenario, we would mock the database connection to simulate this condition
        $this->markTestIncomplete('This test requires complex database mocking');
    }
}