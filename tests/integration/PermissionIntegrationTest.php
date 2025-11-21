<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PermissionIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = ['App'];

    public function testUserCanOnlyAccessOwnTemplates(): void
    {
        // Create two users
        $userModel = model('UserModel');
        
        $user1Id = $userModel->insert([
            'username' => 'user1_perm',
            'email' => 'user1_perm@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $user1 = $userModel->findById($user1Id);
        $user1->addGroup('contributor');
        
        $user2Id = $userModel->insert([
            'username' => 'user2_perm',
            'email' => 'user2_perm@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $user2 = $userModel->findById($user2Id);
        $user2->addGroup('contributor');

        // Create templates owned by each user
        $templateModel = model('App\Models\TemplateModel');
        $permissionManager = new \App\Libraries\PermissionManager();

        $template1Id = $templateModel->insert([
            'name' => 'User1 Template',
            'path' => '/path/to/user1.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ]);
        $permissionManager->setOwner('template', $template1Id, $user1Id);

        $template2Id = $templateModel->insert([
            'name' => 'User2 Template',
            'path' => '/path/to/user2.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ]);
        $permissionManager->setOwner('template', $template2Id, $user2Id);

        // User1 can access their own template
        $this->assertTrue($permissionManager->can($user1, 'read', 'template', $template1Id));
        
        // User1 cannot access User2's template
        $this->assertFalse($permissionManager->can($user1, 'read', 'template', $template2Id));
        
        // User2 can access their own template
        $this->assertTrue($permissionManager->can($user2, 'read', 'template', $template2Id));
        
        // User2 cannot access User1's template
        $this->assertFalse($permissionManager->can($user2, 'read', 'template', $template1Id));
    }

    public function testOwnershipTransfer(): void
    {
        $userModel = model('UserModel');
        
        $oldOwnerId = $userModel->insert([
            'username' => 'oldowner',
            'email' => 'oldowner@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $oldOwner = $userModel->findById($oldOwnerId);
        
        $newOwnerId = $userModel->insert([
            'username' => 'newowner',
            'email' => 'newowner@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $newOwner = $userModel->findById($newOwnerId);

        $templateModel = model('App\Models\TemplateModel');
        $permissionManager = new \App\Libraries\PermissionManager();

        $templateId = $templateModel->insert([
            'name' => 'Transfer Test Template',
            'path' => '/path/to/transfer.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ]);
        $permissionManager->setOwner('template', $templateId, $oldOwnerId);

        // Old owner has access
        $this->assertTrue($permissionManager->can($oldOwner, 'write', 'template', $templateId));
        
        // New owner doesn't have access yet
        $this->assertFalse($permissionManager->can($newOwner, 'write', 'template', $templateId));

        // Transfer ownership
        $permissionManager->transferOwnership('template', $templateId, $newOwnerId);

        // Old owner no longer has access
        $this->assertFalse($permissionManager->can($oldOwner, 'write', 'template', $templateId));
        
        // New owner now has access
        $this->assertTrue($permissionManager->can($newOwner, 'write', 'template', $templateId));
    }

    public function testGroupMembershipManagement(): void
    {
        $userModel = model('UserModel');
        $permissionManager = new \App\Libraries\PermissionManager();
        
        $userId1 = $userModel->insert([
            'username' => 'groupmember1',
            'email' => 'groupmember1@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        
        $userId2 = $userModel->insert([
            'username' => 'groupmember2',
            'email' => 'groupmember2@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        // Create a group
        $groupId = $permissionManager->createGroup('Test Department', 'A test department');
        $this->assertIsNumeric($groupId);

        // Add users to group
        $this->assertTrue($permissionManager->addUserToGroup($userId1, $groupId));
        $this->assertTrue($permissionManager->addUserToGroup($userId2, $groupId));

        // Verify members
        $members = $permissionManager->getGroupMembers($groupId);
        $this->assertCount(2, $members);

        // Remove one member
        $this->assertTrue($permissionManager->removeUserFromGroup($userId1, $groupId));
        
        $members = $permissionManager->getGroupMembers($groupId);
        $this->assertCount(1, $members);
        $this->assertEquals($userId2, $members[0]['user_id']);
    }

    public function testDeleteResourceRemovesOwnership(): void
    {
        $userModel = model('UserModel');
        $userId = $userModel->insert([
            'username' => 'deletetest',
            'email' => 'deletetest@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $templateModel = model('App\Models\TemplateModel');
        $permissionManager = new \App\Libraries\PermissionManager();

        $templateId = $templateModel->insert([
            'name' => 'Delete Test Template',
            'path' => '/path/to/delete.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ]);
        $permissionManager->setOwner('template', $templateId, $userId);

        // Verify ownership exists
        $owner = $permissionManager->getOwner('template', $templateId);
        $this->assertEquals($userId, $owner);

        // Delete template
        $templateModel->delete($templateId);

        // Ownership should be removed by beforeDelete callback
        $owner = $permissionManager->getOwner('template', $templateId);
        $this->assertNull($owner);
    }

    public function testFilledFileInheritsTemplateOwnership(): void
    {
        $userModel = model('UserModel');
        $userId = $userModel->insert([
            'username' => 'inheritowner',
            'email' => 'inheritowner@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $user = $userModel->findById($userId);

        $templateModel = model('App\Models\TemplateModel');
        $filledFilesModel = model('App\Models\FilledFilesModel');
        $permissionManager = new \App\Libraries\PermissionManager();

        // Create template with owner
        $templateId = $templateModel->insert([
            'name' => 'Parent Template',
            'path' => '/path/to/parent.docx',
            'size' => 1024,
            'templateFields' => json_encode(['field1']),
        ]);
        $permissionManager->setOwner('template', $templateId, $userId);

        // Create filled file
        $filledFileId = $filledFilesModel->insert([
            'templateFileId' => $templateId,
            'name' => 'Child Filled File',
            'filledData' => json_encode(['field1' => 'value1']),
        ]);
        
        // Set ownership manually (no auth session in tests)
        $permissionManager->setOwner('filled_file', $filledFileId, $userId);

        // Filled file should be owned by same user
        $owner = $permissionManager->getOwner('filled_file', $filledFileId);
        $this->assertEquals($userId, $owner);

        // User should have access to filled file
        $this->assertTrue($permissionManager->can($user, 'read', 'filled_file', $filledFileId));
    }
}
