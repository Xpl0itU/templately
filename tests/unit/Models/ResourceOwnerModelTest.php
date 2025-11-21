<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\ResourceOwnerModel;

/**
 * @internal
 */
final class ResourceOwnerModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = ['App'];
    protected $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new ResourceOwnerModel();
    }

    public function testSetOwner(): void
    {
        // Create a real user
        $userModel = model('UserModel');
        $ownerId = $userModel->insert([
            'username' => 'owner1',
            'email' => 'owner1@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $result = $this->model->setOwner('template', 1, $ownerId);
        $this->assertTrue($result);

        $ownership = $this->model->where([
            'resource_type' => 'template',
            'resource_id' => 1,
        ])->first();

        $this->assertNotNull($ownership);
        $this->assertEquals($ownerId, $ownership['owner_id']);
    }

    public function testSetOwnerUpdatesExisting(): void
    {
        // Create real users
        $userModel = model('UserModel');
        $owner1 = $userModel->insert([
            'username' => 'owner2a',
            'email' => 'owner2a@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $owner2 = $userModel->insert([
            'username' => 'owner2b',
            'email' => 'owner2b@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        // Set initial owner
        $this->model->setOwner('template', 2, $owner1);

        // Change owner
        $result = $this->model->setOwner('template', 2, $owner2);
        $this->assertTrue($result);

        $owner = $this->model->getOwner('template', 2);
        $this->assertEquals($owner2, $owner);
    }

    public function testGetOwner(): void
    {
        // Create a real user
        $userModel = model('UserModel');
        $ownerId = $userModel->insert([
            'username' => 'owner3',
            'email' => 'owner3@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $this->model->setOwner('filled_file', 5, $ownerId);

        $retrievedId = $this->model->getOwner('filled_file', 5);
        $this->assertEquals($ownerId, $retrievedId);
    }

    public function testGetOwnerReturnsNullForNonExistent(): void
    {
        $ownerId = $this->model->getOwner('template', 99999);
        $this->assertNull($ownerId);
    }

    public function testIsOwner(): void
    {
        // Create a real user
        $userModel = model('UserModel');
        $ownerId = $userModel->insert([
            'username' => 'owner4',
            'email' => 'owner4@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $nonOwnerId = $userModel->insert([
            'username' => 'nonowner4',
            'email' => 'nonowner4@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $this->model->setOwner('template', 10, $ownerId);

        $isOwner = $this->model->isOwner($ownerId, 'template', 10);
        $this->assertTrue($isOwner);

        $isOwner = $this->model->isOwner($nonOwnerId, 'template', 10);
        $this->assertFalse($isOwner);
    }

    public function testTransferOwnership(): void
    {
        // Create real users
        $userModel = model('UserModel');
        $oldOwner = $userModel->insert([
            'username' => 'oldowner5',
            'email' => 'oldowner5@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $newOwner = $userModel->insert([
            'username' => 'newowner5',
            'email' => 'newowner5@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $this->model->setOwner('filled_file', 20, $oldOwner);

        $result = $this->model->transferOwnership('filled_file', 20, $newOwner);
        $this->assertTrue($result);

        $retrievedOwner = $this->model->getOwner('filled_file', 20);
        $this->assertEquals($newOwner, $retrievedOwner);
    }

    public function testTransferOwnershipFailsForNonExistent(): void
    {
        // Create a real user for target
        $userModel = model('UserModel');
        $newOwner = $userModel->insert([
            'username' => 'target6',
            'email' => 'target6@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $result = $this->model->transferOwnership('template', 99999, $newOwner);
        $this->assertFalse($result);
    }

    public function testRemoveOwnership(): void
    {
        // Create a real user
        $userModel = model('UserModel');
        $ownerId = $userModel->insert([
            'username' => 'owner7',
            'email' => 'owner7@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $this->model->setOwner('template', 30, $ownerId);

        $owner = $this->model->getOwner('template', 30);
        $this->assertNotNull($owner);

        $result = $this->model->removeOwnership('template', 30);
        $this->assertTrue($result);

        $owner = $this->model->getOwner('template', 30);
        $this->assertNull($owner);
    }

    public function testGetResourcesByOwner(): void
    {
        // Create real users
        $userModel = model('UserModel');
        $owner1 = $userModel->insert([
            'username' => 'owner8',
            'email' => 'owner8@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $owner2 = $userModel->insert([
            'username' => 'owner9',
            'email' => 'owner9@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        // Create multiple resources owned by same user
        $this->model->setOwner('template', 100, $owner1);
        $this->model->setOwner('template', 101, $owner1);
        $this->model->setOwner('filled_file', 200, $owner1);
        $this->model->setOwner('template', 102, $owner2);

        $resources = $this->model->getResourcesByOwner($owner1);
        $this->assertCount(3, $resources);

        // Filter by type
        $templates = $this->model->getResourcesByOwner($owner1, 'template');
        $this->assertCount(2, $templates);

        foreach ($templates as $resource) {
            $this->assertEquals('template', $resource['resource_type']);
            $this->assertEquals($owner1, $resource['owner_id']);
        }
    }

    public function testGetUserOwnedResources(): void
    {
        // Create a real user
        $userModel = model('UserModel');
        $ownerId = $userModel->insert([
            'username' => 'owner10',
            'email' => 'owner10@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $this->model->setOwner('template', 50, $ownerId);
        $this->model->setOwner('filled_file', 60, $ownerId);

        $resources = $this->model->getUserOwnedResources($ownerId);
        $this->assertCount(2, $resources);
    }

    public function testMultipleResourceTypes(): void
    {
        // Create real users
        $userModel = model('UserModel');
        $owner1 = $userModel->insert([
            'username' => 'owner11',
            'email' => 'owner11@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $owner2 = $userModel->insert([
            'username' => 'owner12',
            'email' => 'owner12@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        $this->model->setOwner('template', 1, $owner1);
        $this->model->setOwner('filled_file', 1, $owner2);

        // Same resource_id, different types
        $templateOwner = $this->model->getOwner('template', 1);
        $this->assertEquals($owner1, $templateOwner);

        $filledFileOwner = $this->model->getOwner('filled_file', 1);
        $this->assertEquals($owner2, $filledFileOwner);
    }

    public function testBulkOwnershipOperations(): void
    {
        // Create real users
        $userModel = model('UserModel');
        $owner1 = $userModel->insert([
            'username' => 'bulkowner1',
            'email' => 'bulkowner1@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);
        $owner2 = $userModel->insert([
            'username' => 'bulkowner2',
            'email' => 'bulkowner2@test.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'active' => 1,
        ]);

        // Set ownership for multiple resources
        for ($i = 1; $i <= 5; $i++) {
            $this->model->setOwner('template', $i, $owner1);
        }

        $resources = $this->model->getResourcesByOwner($owner1, 'template');
        $this->assertCount(5, $resources);

        // Transfer all to new owner
        foreach ($resources as $resource) {
            $this->model->transferOwnership(
                $resource['resource_type'],
                $resource['resource_id'],
                $owner2
            );
        }

        $oldOwnerResources = $this->model->getResourcesByOwner($owner1, 'template');
        $this->assertCount(0, $oldOwnerResources);

        $newOwnerResources = $this->model->getResourcesByOwner($owner2, 'template');
        $this->assertCount(5, $newOwnerResources);
    }
}
