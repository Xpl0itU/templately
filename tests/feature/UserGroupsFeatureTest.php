<?php

namespace Tests\Feature;

use App\Models\UserGroupMemberModel;
use App\Models\UserGroupModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class UserGroupsFeatureTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    private const TEST_PASSWORD = 'Password123!';

    protected $refresh   = true;
    protected $namespace = ['App'];

    public function testGuestIsRedirectedFromUserGroups(): void
    {
        $response = $this->get('/user-groups');

        $response->assertRedirect();
        $response->assertHeader('Location', 'http://example.com/index.php/setup');
    }

    public function testSuperadminCanViewUserGroupsPage(): void
    {
        $superAdmin = $this->createUser('superadmin_user', ['superadmin']);
        $this->loginAs($superAdmin);

        $response = $this->get('/user-groups');

        $response->assertStatus(200);
        $response->assertSee('User Groups');
    }

    public function testAddMemberSupportsJsonPayload(): void
    {
        $admin = $this->createUser('groups_admin', ['superadmin']);
        $this->loginAs($admin);

        $groupModel = new UserGroupModel();
        $groupId    = $groupModel->createGroup('QA Team', 'Quality assurance team');

        $member = $this->createUser('qa_member');

        $response = $this->post(
            '/user-groups/add-member/' . $groupId,
            [
                'user_id' => $member->id,
            ]
        );

        $response->assertJSONFragment(['success' => true]);

        $memberModel = new UserGroupMemberModel();
        $membership  = $memberModel
            ->where('user_id', $member->id)
            ->where('group_id', $groupId)
            ->first();

        $this->assertNotNull($membership, 'Membership record should exist after adding member.');

        $membersResponse = $this->get('/user-groups/get-group-members/' . $groupId);
        $membersResponse->assertJSONFragment(['success' => true]);

        $membersData = json_decode($membersResponse->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($membersData['members']);
        $this->assertSame($member->email, $membersData['members'][0]['email']);
    }

    protected function tearDown(): void
    {
        if (function_exists('auth')) {
            try {
                auth()->logout();
            } catch (\Throwable $exception) {
                // Ignore logout errors during tests
            }
        }

        parent::tearDown();
    }

    private function createUser(string $username, array $groups = []): User
    {
        /** @var \CodeIgniter\Shield\Models\UserModel $userModel */
        $userModel = model('CodeIgniter\Shield\Models\UserModel');

        $user = new User([
            'username' => $username,
            'email'    => $username . '@example.com',
            'password' => self::TEST_PASSWORD,
        ]);

        $userModel->save($user);
        $userId = $userModel->getInsertID();

        if (! empty($groups)) {
            $authConfig       = config('Auth');
            $groupsUsersTable = $authConfig->tables['groups_users'] ?? 'auth_groups_users';
            $db               = db_connect();

            foreach ($groups as $group) {
                $db->table($groupsUsersTable)->insert([
                    'user_id'    => $userId,
                    'group'      => $group,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        return $userModel->withIdentities()->findById($userId);
    }

    private function loginAs(User $user): void
    {
        if (function_exists('auth')) {
            auth()->logout();
            auth()->login($user);
        }
    }
}
