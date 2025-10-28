<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class LoginTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;

    public function testUserCanLoginWithUsername(): void
    {
        // Create a test user
        $this->createTestUser('testuser', 'password123');

        // Try to login
        $result = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        // Should redirect to dashboard
        $result->assertRedirect();
        $result->assertRedirectTo(site_url());
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        // Create a test user
        $this->createTestUser('testuser', 'password123');

        // Try to login with wrong password
        $result = $this->post('/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);

        // Should redirect back to login with error
        $result->assertRedirect();
        $result->assertRedirectTo(site_url('login'));
    }

    public function testLoginFailsWithNonExistentUser(): void
    {
        // Try to login with non-existent user
        $result = $this->post('/login', [
            'username' => 'nonexistentuser',
            'password' => 'password123',
        ]);

        // Should redirect back to setup page when no users exist
        $result->assertRedirect();
        $result->assertRedirectTo(site_url('setup'));
    }

    private function createTestUser(string $username, string $password): void
    {
        $users = model('UserModel');
        
        $user = new \CodeIgniter\Shield\Entities\User([
            'username' => $username,
            'password' => $password,
            'email'    => $username . '@example.com', // Dummy email for identity
        ]);
        
        $users->save($user);
        $user = $users->findById($users->getInsertID());
        $users->addToDefaultGroup($user);
    }
}