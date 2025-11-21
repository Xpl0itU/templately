<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Libraries\PermissionManager;
use CodeIgniter\Shield\Entities\User;

/**
 * @internal
 */
final class PermissionManagerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = ['App'];
    protected $permissionManager;
    protected $superadminUser;
    protected $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionManager = new PermissionManager();
        
        // Create test users
        $userModel = model('UserModel');
        
        // Create superadmin user
        $superadminId = $userModel->insert([
            'username' => 'superadmin_test',
            'email' => 'superadmin@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $this->superadminUser = $userModel->findById($superadminId);
        $this->superadminUser->addGroup('superadmin');
        
        // Create regular user
        $regularId = $userModel->insert([
            'username' => 'regular_test',
            'email' => 'regular@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $this->regularUser = $userModel->findById($regularId);
        $this->regularUser->addGroup('contributor');
    }

    public function testCanReturnsFalseForNullUser(): void
    {
        $result = $this->permissionManager->can(null, 'templates.view');
        $this->assertFalse($result);
    }

    public function testSuperadminCanDoAnything(): void
    {
        // Global permission
        $result = $this->permissionManager->can($this->superadminUser, 'some.random.permission');
        $this->assertTrue($result);
        
        // Resource permission
        $result = $this->permissionManager->can($this->superadminUser, 'read', 'template', 999);
        $this->assertTrue($result);
    }

    public function testGlobalPermissionsCheckedViaShield(): void
    {
        // Add specific permission to regular user
        $this->regularUser->addPermission('templates.view');
        
        $result = $this->permissionManager->can($this->regularUser, 'templates.view');
        $this->assertTrue($result);
        
        // Permission they don't have
        $result = $this->permissionManager->can($this->regularUser, 'templates.delete');
        $this->assertFalse($result);
    }

    public function testResourceOwnershipPermissions(): void
    {
        $templateModel = model('App\Models\TemplateModel');
        
        // Create a template owned by regular user
        $templateData = [
            'name' => 'Permission Test Template',
            'originalFileName' => 'test.docx',
            'path' => '/fake/path/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['name']),
        ];
        $templateId = $templateModel->insert($templateData);
        
        // Set ownership
        $this->permissionManager->setOwner('template', $templateId, $this->regularUser->id);
        
        // Owner should have access
        $result = $this->permissionManager->can($this->regularUser, 'read', 'template', $templateId);
        $this->assertTrue($result);
        
        $result = $this->permissionManager->can($this->regularUser, 'write', 'template', $templateId);
        $this->assertTrue($result);
        
        $result = $this->permissionManager->can($this->regularUser, 'delete', 'template', $templateId);
        $this->assertTrue($result);
    }

    public function testNonOwnerCannotAccessResource(): void
    {
        $userModel = model('UserModel');
        
        // Create another user
        $otherId = $userModel->insert([
            'username' => 'other_test',
            'email' => 'other@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $otherUser = $userModel->findById($otherId);
        
        $templateModel = model('App\Models\TemplateModel');
        
        // Create a template owned by regular user
        $templateData = [
            'name' => 'Private Template',
            'originalFileName' => 'test.docx',
            'path' => '/fake/path/test.docx',
            'size' => 1024,
            'templateFields' => json_encode(['name']),
        ];
        $templateId = $templateModel->insert($templateData);
        
        // Set ownership to regular user
        $this->permissionManager->setOwner('template', $templateId, $this->regularUser->id);
        
        // Other user should not have access
        $result = $this->permissionManager->can($otherUser, 'read', 'template', $templateId);
        $this->assertFalse($result);
    }

    public function testSetOwner(): void
    {
        $result = $this->permissionManager->setOwner('template', 1, $this->regularUser->id);
        $this->assertTrue($result);
        
        $owner = $this->permissionManager->getOwner('template', 1);
        $this->assertEquals($this->regularUser->id, $owner);
    }

    public function testGetOwner(): void
    {
        $this->permissionManager->setOwner('template', 99, $this->regularUser->id);
        
        $owner = $this->permissionManager->getOwner('template', 99);
        $this->assertEquals($this->regularUser->id, $owner);
    }

    public function testGetOwnerReturnsNullForNonExistent(): void
    {
        $owner = $this->permissionManager->getOwner('template', 99999);
        $this->assertNull($owner);
    }

    public function testIsOwner(): void
    {
        $this->permissionManager->setOwner('filled_file', 50, $this->regularUser->id);
        
        $result = $this->permissionManager->isOwner($this->regularUser->id, 'filled_file', 50);
        $this->assertTrue($result);
        
        $result = $this->permissionManager->isOwner($this->superadminUser->id, 'filled_file', 50);
        $this->assertFalse($result);
    }

    public function testTransferOwnership(): void
    {
        $this->permissionManager->setOwner('template', 77, $this->regularUser->id);
        
        $owner = $this->permissionManager->getOwner('template', 77);
        $this->assertEquals($this->regularUser->id, $owner);
        
        // Transfer to superadmin
        $result = $this->permissionManager->transferOwnership('template', 77, $this->superadminUser->id);
        $this->assertTrue($result);
        
        $owner = $this->permissionManager->getOwner('template', 77);
        $this->assertEquals($this->superadminUser->id, $owner);
    }

    public function testRemoveResourcePermissions(): void
    {
        $this->permissionManager->setOwner('template', 88, $this->regularUser->id);
        
        $owner = $this->permissionManager->getOwner('template', 88);
        $this->assertNotNull($owner);
        
        // Remove ownership
        $result = $this->permissionManager->removeResourcePermissions('template', 88);
        $this->assertTrue($result);
        
        $owner = $this->permissionManager->getOwner('template', 88);
        $this->assertNull($owner);
    }

    public function testGetAllGroups(): void
    {
        // Create a test group first
        $this->permissionManager->createGroup('Test Group', 'A test group');
        
        $groups = $this->permissionManager->getAllGroups();
        $this->assertIsArray($groups);
        $this->assertNotEmpty($groups);
    }

    public function testCreateAndDeleteGroup(): void
    {
        $groupId = $this->permissionManager->createGroup('Test Group', 'A test group');
        $this->assertIsNumeric($groupId);
        
        $group = $this->permissionManager->getGroup($groupId);
        $this->assertNotNull($group);
        $this->assertEquals('Test Group', $group['name']);
        $this->assertEquals('A test group', $group['description']);
        
        $result = $this->permissionManager->deleteGroup($groupId);
        $this->assertTrue($result);
        
        $group = $this->permissionManager->getGroup($groupId);
        $this->assertNull($group);
    }

    public function testAddAndRemoveUserFromGroup(): void
    {
        $groupId = $this->permissionManager->createGroup('Member Test Group', 'Testing members');
        
        $result = $this->permissionManager->addUserToGroup($this->regularUser->id, $groupId);
        $this->assertTrue($result);
        
        $members = $this->permissionManager->getGroupMembers($groupId);
        $this->assertCount(1, $members);
        $this->assertEquals($this->regularUser->id, $members[0]['user_id']);
        
        $result = $this->permissionManager->removeUserFromGroup($this->regularUser->id, $groupId);
        $this->assertTrue($result);
        
        $members = $this->permissionManager->getGroupMembers($groupId);
        $this->assertCount(0, $members);
    }

    public function testGetGroupMembers(): void
    {
        $groupId = $this->permissionManager->createGroup('Members Group');
        
        $this->permissionManager->addUserToGroup($this->regularUser->id, $groupId);
        $this->permissionManager->addUserToGroup($this->superadminUser->id, $groupId, $this->regularUser->id);
        
        $members = $this->permissionManager->getGroupMembers($groupId);
        $this->assertCount(2, $members);
        $this->assertArrayHasKey('username', $members[0]);
        $this->assertArrayHasKey('email', $members[0]);
    }
}
