<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class FileExplorerAuthorizationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = ['App'];

    public function testUnauthorizedUserCannotAccessFileExplorer(): void
    {
        // Without login, should redirect to login
        $result = $this->get('file-explorer');
        $result->assertRedirect();
    }

    public function testAuthorizedUserCanAccessFileExplorer(): void
    {
        // Create and login a user
        $userModel = model('UserModel');
        $userId = $userModel->insert([
            'username' => 'fileuser',
            'email' => 'fileuser@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $user = $userModel->findById($userId);
        $user->addGroup('contributor');

        // Simulate login by setting session
        $_SESSION['user'] = ['id' => $userId];
        
        $result = $this->withSession(['user' => ['id' => $userId]])
            ->get('file-explorer');
        
        $result->assertOK();
    }

    public function testUserCanOnlySeeOwnTemplates(): void
    {
        $userModel = model('UserModel');
        $templateModel = model('App\Models\TemplateModel');
        $permissionManager = new \App\Libraries\PermissionManager();
        
        // Create two users
        $user1Id = $userModel->insert([
            'username' => 'viewer1',
            'email' => 'viewer1@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        
        $user2Id = $userModel->insert([
            'username' => 'viewer2',
            'email' => 'viewer2@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        // Create templates for each user
        $template1Id = $templateModel->insert([
            'name' => 'User1 Private Template',
            'path' => '/path/to/user1.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ]);
        $permissionManager->setOwner('template', $template1Id, $user1Id);

        $template2Id = $templateModel->insert([
            'name' => 'User2 Private Template',
            'path' => '/path/to/user2.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ]);
        $permissionManager->setOwner('template', $template2Id, $user2Id);

        // User1 can see their template
        $user1 = $userModel->findById($user1Id);
        $this->assertTrue($permissionManager->can($user1, 'read', 'template', $template1Id));
        $this->assertFalse($permissionManager->can($user1, 'read', 'template', $template2Id));
    }

    public function testSuperadminCanSeeAllTemplates(): void
    {
        $userModel = model('UserModel');
        $templateModel = model('App\Models\TemplateModel');
        $permissionManager = new \App\Libraries\PermissionManager();
        
        // Create superadmin
        $adminId = $userModel->insert([
            'username' => 'superadmin_viewer',
            'email' => 'superadmin_viewer@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $admin = $userModel->findById($adminId);
        $admin->addGroup('superadmin');
        
        // Create regular user
        $userId = $userModel->insert([
            'username' => 'regularuser',
            'email' => 'regularuser@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        // Create template owned by regular user
        $templateId = $templateModel->insert([
            'name' => 'Private User Template',
            'path' => '/path/to/private.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ]);
        $permissionManager->setOwner('template', $templateId, $userId);

        // Superadmin can see it
        $this->assertTrue($permissionManager->can($admin, 'read', 'template', $templateId));
        $this->assertTrue($permissionManager->can($admin, 'write', 'template', $templateId));
        $this->assertTrue($permissionManager->can($admin, 'delete', 'template', $templateId));
    }
}
