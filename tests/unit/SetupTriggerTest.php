<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class SetupTriggerTest extends CIUnitTestCase
{
    use ControllerTestTrait;
    use DatabaseTestTrait;

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
        
        // Count users to confirm table is empty
        $userCount = $db->table('users')->countAllResults();
        $this->assertEquals(0, $userCount, 'Users table should be empty');
        
        // Try to access a protected route
        $result = $this->withURI('http://example.com/dashboard')
            ->controller(\App\Controllers\Dashboard::class)
            ->execute('index');
            
        // Should redirect to setup
        $this->assertTrue($result->isRedirect(), 'Should redirect to setup when no users exist');
        $this->assertEquals('http://example.com/setup', $result->getRedirectUrl(), 'Should redirect to setup page');
    }

    public function testSetupTriggeredWhenUsersTableDoesNotExist(): void
    {
        // This test would require dropping the users table, which might affect other tests
        // In a real scenario, we would mock the database connection to simulate this condition
        $this->markTestIncomplete('This test requires complex database mocking');
    }
}