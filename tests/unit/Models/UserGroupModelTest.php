<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\UserGroupModel;

/**
 * @internal
 */
final class UserGroupModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = ['App'];
    protected $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new UserGroupModel();
    }

    public function testCreateGroup(): void
    {
        $groupId = $this->model->createGroup('Test Group', 'A test group description');
        $this->assertIsNumeric($groupId);
        $this->assertGreaterThan(0, $groupId);

        $group = $this->model->find($groupId);
        $this->assertNotNull($group);
        $this->assertEquals('Test Group', $group['name']);
        $this->assertEquals('A test group description', $group['description']);
    }

    public function testCreateGroupWithoutDescription(): void
    {
        $groupId = $this->model->createGroup('Simple Group');
        $this->assertIsNumeric($groupId);

        $group = $this->model->find($groupId);
        $this->assertNotNull($group);
        $this->assertEquals('Simple Group', $group['name']);
        $this->assertNull($group['description']);
    }

    public function testGetGroup(): void
    {
        $groupId = $this->model->createGroup('Get Test Group', 'Description');
        
        $group = $this->model->getGroup($groupId);
        $this->assertNotNull($group);
        $this->assertEquals($groupId, $group['id']);
        $this->assertEquals('Get Test Group', $group['name']);
    }

    public function testGetGroupReturnsNullForNonExistent(): void
    {
        $group = $this->model->getGroup(99999);
        $this->assertNull($group);
    }

    public function testGetAllGroups(): void
    {
        // Create multiple groups
        $this->model->createGroup('Group 1');
        $this->model->createGroup('Group 2');
        $this->model->createGroup('Group 3');

        $groups = $this->model->getAllGroups();
        $this->assertIsArray($groups);
        $this->assertGreaterThanOrEqual(3, count($groups));
    }

    public function testUpdateGroup(): void
    {
        $groupId = $this->model->createGroup('Original Name', 'Original Description');

        $result = $this->model->updateGroup($groupId, 'Updated Name', 'Updated Description');
        $this->assertTrue($result);

        $group = $this->model->getGroup($groupId);
        $this->assertEquals('Updated Name', $group['name']);
        $this->assertEquals('Updated Description', $group['description']);
    }

    public function testUpdateGroupPartial(): void
    {
        $groupId = $this->model->createGroup('Name', 'Description');

        // Update only name
        $result = $this->model->updateGroup($groupId, 'New Name');
        $this->assertTrue($result);

        $group = $this->model->getGroup($groupId);
        $this->assertEquals('New Name', $group['name']);
        $this->assertEquals('Description', $group['description']);
    }

    public function testDeleteGroup(): void
    {
        $groupId = $this->model->createGroup('Delete Me');

        $result = $this->model->deleteGroup($groupId);
        $this->assertTrue($result);

        $group = $this->model->getGroup($groupId);
        $this->assertNull($group);
    }

    public function testDeleteGroupReturnsFalseForNonExistent(): void
    {
        $result = $this->model->deleteGroup(99999);
        $this->assertFalse($result);
    }

    public function testAddUserToGroup(): void
    {
        // Create a real user first
        $userModel = model('UserModel');
        $userId = $userModel->insert([
            'username' => 'groupuser1',
            'email' => 'groupuser1@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $groupId = $this->model->createGroup('Member Group');

        $result = $this->model->addUserToGroup($userId, $groupId);
        $this->assertTrue($result);

        $members = $this->model->getGroupMembers($groupId);
        $this->assertCount(1, $members);
        $this->assertEquals($userId, $members[0]['user_id']);
    }

    public function testAddUserToGroupWithAddedBy(): void
    {
        // Create real users
        $userModel = model('UserModel');
        $userId = $userModel->insert([
            'username' => 'groupuser2',
            'email' => 'groupuser2@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $addedBy = $userModel->insert([
            'username' => 'admin1',
            'email' => 'admin1@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $groupId = $this->model->createGroup('Tracked Group');

        $result = $this->model->addUserToGroup($userId, $groupId, $addedBy);
        $this->assertTrue($result);

        $members = $this->model->getGroupMembers($groupId);
        $this->assertCount(1, $members);
        $this->assertEquals($addedBy, $members[0]['added_by']);
    }

    public function testAddUserToGroupPreventsDuplicates(): void
    {
        // Create a real user
        $userModel = model('UserModel');
        $userId = $userModel->insert([
            'username' => 'dupuser',
            'email' => 'dupuser@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $groupId = $this->model->createGroup('No Duplicates');

        $this->model->addUserToGroup($userId, $groupId);
        $this->model->addUserToGroup($userId, $groupId); // Try adding again

        $members = $this->model->getGroupMembers($groupId);
        $this->assertCount(1, $members); // Should still be 1
    }

    public function testRemoveUserFromGroup(): void
    {
        // Create a real user
        $userModel = model('UserModel');
        $userId = $userModel->insert([
            'username' => 'removeuser',
            'email' => 'removeuser@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $groupId = $this->model->createGroup('Remove Test');

        $this->model->addUserToGroup($userId, $groupId);
        
        $members = $this->model->getGroupMembers($groupId);
        $this->assertCount(1, $members);

        $result = $this->model->removeUserFromGroup($userId, $groupId);
        $this->assertTrue($result);

        $members = $this->model->getGroupMembers($groupId);
        $this->assertCount(0, $members);
    }

    public function testGetGroupMembers(): void
    {
        // Create real users
        $userModel = model('UserModel');
        $userId1 = $userModel->insert([
            'username' => 'member1',
            'email' => 'member1@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $userId2 = $userModel->insert([
            'username' => 'member2',
            'email' => 'member2@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $userId3 = $userModel->insert([
            'username' => 'member3',
            'email' => 'member3@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $groupId = $this->model->createGroup('Multi Member');
        
        $this->model->addUserToGroup($userId1, $groupId);
        $this->model->addUserToGroup($userId2, $groupId);
        $this->model->addUserToGroup($userId3, $groupId);

        $members = $this->model->getGroupMembers($groupId);
        $this->assertCount(3, $members);
    }

    public function testGetGroupMembersWithDetails(): void
    {
        // Create a real user for this test
        $userModel = model('UserModel');
        $userId = $userModel->insert([
            'username' => 'detailtest',
            'email' => 'detail@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $groupId = $this->model->createGroup('Details Group');
        $this->model->addUserToGroup($userId, $groupId);

        $members = $this->model->getGroupMembersWithDetails($groupId);
        $this->assertCount(1, $members);
        $this->assertArrayHasKey('username', $members[0]);
        $this->assertArrayHasKey('email', $members[0]);
        $this->assertEquals('detailtest', $members[0]['username']);
    }

    public function testGetUserGroups(): void
    {
        // Create a real user
        $userModel = model('UserModel');
        $userId = $userModel->insert([
            'username' => 'multigroupuser',
            'email' => 'multigroupuser@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $groupId1 = $this->model->createGroup('Group A');
        $groupId2 = $this->model->createGroup('Group B');

        $this->model->addUserToGroup($userId, $groupId1);
        $this->model->addUserToGroup($userId, $groupId2);

        $userGroups = $this->model->getUserGroups($userId);
        $this->assertCount(2, $userGroups);
    }

    public function testIsUserInGroup(): void
    {
        // Create a real user
        $userModel = model('UserModel');
        $userId = $userModel->insert([
            'username' => 'checkuser',
            'email' => 'checkuser@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $groupId = $this->model->createGroup('Check Group');

        $isMember = $this->model->isUserInGroup($userId, $groupId);
        $this->assertFalse($isMember);

        $this->model->addUserToGroup($userId, $groupId);

        $isMember = $this->model->isUserInGroup($userId, $groupId);
        $this->assertTrue($isMember);
    }

    public function testGetGroupCount(): void
    {
        $initialCount = $this->model->getGroupCount();

        $this->model->createGroup('Count Test 1');
        $this->model->createGroup('Count Test 2');

        $newCount = $this->model->getGroupCount();
        $this->assertEquals($initialCount + 2, $newCount);
    }

    public function testDeleteGroupRemovesMembers(): void
    {
        // Create real users
        $userModel = model('UserModel');
        $userId1 = $userModel->insert([
            'username' => 'delmember1',
            'email' => 'delmember1@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $userId2 = $userModel->insert([
            'username' => 'delmember2',
            'email' => 'delmember2@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $groupId = $this->model->createGroup('Delete With Members');
        
        $this->model->addUserToGroup($userId1, $groupId);
        $this->model->addUserToGroup($userId2, $groupId);

        $members = $this->model->getGroupMembers($groupId);
        $this->assertCount(2, $members);

        $this->model->deleteGroup($groupId);

        // Verify members are also removed
        $db = \Config\Database::connect();
        $builder = $db->table('user_group_members');
        $remainingMembers = $builder->where('group_id', $groupId)->countAllResults();
        $this->assertEquals(0, $remainingMembers);
    }
}
