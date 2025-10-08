<?php

namespace App\Controllers;

use App\Libraries\PermissionManager;
use CodeIgniter\Shield\Entities\User;

class UserGroups extends BaseController
{
    protected $permissionManager;
    protected $userGroupModel;
    protected $userGroupMemberModel;

    /** @var \CodeIgniter\Shield\Models\UserModel */
    protected $userModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->permissionManager = service('permissionManager');
        $this->userGroupModel = model('App\Models\UserGroupModel');
        $this->userGroupMemberModel = model('App\Models\UserGroupMemberModel');
        $this->userModel = model('CodeIgniter\Shield\Models\UserModel');
    }

    public function index()
    {
        $currentUser = $this->getAuthenticatedUser();

        if ($currentUser === null) {
            return redirect()->to('/login');
        }

        // Check if user has permission to manage user groups
        if (!$this->permissionManager->can($currentUser, 'user-groups.view')) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access this page.');
        }

        $data = [
            'title' => 'User Groups',
            'userGroups' => $this->getAllUserGroupsWithMemberCounts(),
            'users' => $this->getAllUsers(),
        ];

        return view('user_groups/index', $data);
    }

    public function create()
    {
        $currentUser = $this->getAuthenticatedUser();

        if ($currentUser === null) {
            return $this->unauthorizedResponse();
        }

        if (!$this->permissionManager->can($currentUser, 'user-groups.create')) {
            return $this->forbiddenResponse('You do not have permission to create user groups.');
        }

        if ($this->request->getMethod(true) !== 'POST') {
            return $this->invalidMethodResponse();
        }

        $name = trim((string) $this->request->getPost('name'));
        $description = trim((string) $this->request->getPost('description')) ?: null;

        if ($name === '') {
            return $this->validationErrorResponse('Group name is required.');
        }

        if ($this->userGroupModel->getGroupByName($name) !== null) {
            return $this->validationErrorResponse('A group with this name already exists.');
        }

        $groupId = $this->userGroupModel->createGroup($name, $description);

        if ($groupId === false) {
            return $this->errorResponse('Failed to create user group.');
        }

        return $this->respondJson(true, 'User group created successfully.', 201, ['group_id' => $groupId]);
    }

    public function update(int $groupId)
    {
        $currentUser = $this->getAuthenticatedUser();

        if ($currentUser === null) {
            return $this->unauthorizedResponse();
        }

        if (!$this->permissionManager->can($currentUser, 'user-groups.edit')) {
            return $this->forbiddenResponse('You do not have permission to edit user groups.');
        }

        if ($this->request->getMethod(true) !== 'POST') {
            return $this->invalidMethodResponse();
        }

        $group = $this->userGroupModel->getGroup($groupId);
        if ($group === null) {
            return $this->notFoundResponse('User group not found.');
        }

        $name = trim((string) $this->request->getPost('name'));
        $description = trim((string) $this->request->getPost('description')) ?: null;

        if ($name === '') {
            return $this->validationErrorResponse('Group name is required.');
        }

        $existingGroup = $this->userGroupModel->getGroupByName($name);
        if ($existingGroup !== null && (int) $existingGroup['id'] !== $groupId) {
            return $this->validationErrorResponse('Another group with this name already exists.');
        }

        $result = $this->userGroupModel->updateGroup($groupId, [
            'name' => $name,
            'description' => $description,
        ]);

        if (!$result) {
            return $this->errorResponse('Failed to update user group.');
        }

        return $this->respondJson(true, 'User group updated successfully.');
    }

    public function delete(int $groupId)
    {
        $currentUser = $this->getAuthenticatedUser();

        if ($currentUser === null) {
            return $this->unauthorizedResponse();
        }

        if (!$this->permissionManager->can($currentUser, 'user-groups.delete')) {
            return $this->forbiddenResponse('You do not have permission to delete user groups.');
        }

        if ($this->request->getMethod(true) !== 'POST') {
            return $this->invalidMethodResponse();
        }

        $group = $this->userGroupModel->getGroup($groupId);
        if ($group === null) {
            return $this->notFoundResponse('User group not found.');
        }

        $result = $this->userGroupModel->deleteGroup($groupId);

        if (!$result) {
            return $this->errorResponse('Failed to delete user group.');
        }

        return $this->respondJson(true, 'User group deleted successfully.');
    }

    public function addMember(int $groupId)
    {
        $currentUser = $this->getAuthenticatedUser();

        if ($currentUser === null) {
            return $this->unauthorizedResponse();
        }

        if (!$this->permissionManager->can($currentUser, 'user-groups.manage-members')) {
            return $this->forbiddenResponse('You do not have permission to manage group members.');
        }

        if ($this->request->getMethod(true) !== 'POST') {
            return $this->invalidMethodResponse();
        }

        if ($this->request->getMethod(true) !== 'POST') {
            return $this->invalidMethodResponse();
        }

        $group = $this->userGroupModel->getGroup($groupId);
        if ($group === null) {
            return $this->notFoundResponse('User group not found.');
        }

        $payload = $this->request->getJSON(true) ?? [];
        $userId = $payload['user_id'] ?? $this->request->getPost('user_id');

        if (empty($userId)) {
            return $this->validationErrorResponse('User ID is required.');
        }

        $targetUser = $this->userModel->find((int) $userId);
        if ($targetUser === null) {
            return $this->notFoundResponse('User not found.');
        }

        $memberId = $this->userGroupMemberModel->addUserToGroup((int) $userId, $groupId, $currentUser->id);

        if ($memberId === false) {
            return $this->errorResponse('Failed to add user to group.');
        }

        return $this->respondJson(true, 'User added to group successfully.');
    }

    public function removeMember(int $groupId, int $userId)
    {
        $currentUser = $this->getAuthenticatedUser();

        if ($currentUser === null) {
            return $this->unauthorizedResponse();
        }

        if (!$this->permissionManager->can($currentUser, 'user-groups.manage-members')) {
            return $this->forbiddenResponse('You do not have permission to manage group members.');
        }

        $group = $this->userGroupModel->getGroup($groupId);
        if ($group === null) {
            return $this->notFoundResponse('User group not found.');
        }

        $targetUser = $this->userModel->find($userId);
        if ($targetUser === null) {
            return $this->notFoundResponse('User not found.');
        }

        $result = $this->userGroupMemberModel->removeUserFromGroup($userId, $groupId);

        if (!$result) {
            return $this->errorResponse('Failed to remove user from group.');
        }

        return $this->respondJson(true, 'User removed from group successfully.');
    }

    public function getGroupMembers(int $groupId)
    {
        $currentUser = $this->getAuthenticatedUser();

        if ($currentUser === null) {
            return $this->unauthorizedResponse();
        }

        if (!$this->permissionManager->can($currentUser, 'user-groups.view-members')) {
            return $this->forbiddenResponse('You do not have permission to view group members.');
        }

        $group = $this->userGroupModel->getGroup($groupId);
        if ($group === null) {
            return $this->notFoundResponse('User group not found.');
        }

        $members = $this->userGroupMemberModel->getGroupMembersWithDetails($groupId);

        return $this->respondJson(true, 'Group members fetched successfully.', 200, ['members' => $members]);
    }

    private function getAllUserGroupsWithMemberCounts(): array
    {
        return $this->userGroupModel->getAllGroupsWithMemberCounts();
    }

    private function getAllUsers(): array
    {
        $users = $this->userModel->findAll();

        return array_map(static function ($user): array {
            if ($user instanceof User) {
                return [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'email'    => $user->email,
                ];
            }

            return [
                'id'       => $user['id'] ?? null,
                'username' => $user['username'] ?? '',
                'email'    => $user['email'] ?? '',
            ];
        }, $users);
    }

    private function getAuthenticatedUser(): ?User
    {
        if (!function_exists('auth')) {
            return null;
        }

        try {
            $auth = auth();
            if ($auth === null || !method_exists($auth, 'user')) {
                return null;
            }

            $user = $auth->user();

            return $user instanceof User ? $user : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function respondJson(bool $success, string $message, int $statusCode = 200, array $extra = [])
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON(array_merge([
                'success' => $success,
                'message' => $message,
            ], $extra));
    }

    private function unauthorizedResponse(string $message = 'You must be logged in to perform this action.')
    {
        return $this->respondJson(false, $message, 401);
    }

    private function forbiddenResponse(string $message)
    {
        return $this->respondJson(false, $message, 403);
    }

    private function invalidMethodResponse()
    {
        return $this->respondJson(false, 'Invalid request method.', 405);
    }

    private function validationErrorResponse(string $message)
    {
        return $this->respondJson(false, $message, 422);
    }

    private function errorResponse(string $message)
    {
        return $this->respondJson(false, $message, 500);
    }

    private function notFoundResponse(string $message)
    {
        return $this->respondJson(false, $message, 404);
    }
}