<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;

/**
 * Users Controller
 * Handles user management operations including listing, creating, updating, and deleting users
 * Requires admin or superadmin privileges
 */
class Users extends BaseController
{
    /**
     * Display list of all users with their roles
     * Restricted to admin and superadmin users only
     *
     * @return ResponseInterface|string View with user list or redirect
     */
    public function index()
    {
        if (!auth()->user()->inGroup('superadmin', 'manager')) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access user management.');
        }

        $userModel = model('CodeIgniter\Shield\Models\UserModel');
        $allUsers = $userModel->findAll();

        $users = [];
        foreach ($allUsers as $user) {
            $userGroups = $user->getGroups();
            $role = !empty($userGroups) ? $userGroups[0] : 'viewer';

            $users[] = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'active' => $user->active,
                'role' => $role
            ];
        }

        return view('users/index', [
            'users' => $users,
            'currentUser' => auth()->user()
        ]);
    }

    /**
     * Create a new user (AJAX endpoint)
     * Validates permissions and checks for existing username/email
     *
     * @return ResponseInterface JSON response with creation result
     */
    public function create()
    {
        if (!auth()->user()->inGroup('superadmin', 'manager')) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'You do not have permission to create users.'
            ]);
        }

        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON([
                'success' => false,
                'message' => 'Method Not Allowed'
            ]);
        }

        $json = $this->request->getJSON();

        if (!$json || !isset($json->username, $json->email, $json->password, $json->role)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Missing required fields.'
            ]);
        }

        if ($json->role === 'superadmin' && !auth()->user()->inGroup('superadmin')) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Only superadmins can create superadmin users.'
            ]);
        }

        try {
            $userModel = model('CodeIgniter\Shield\Models\UserModel');

            $existingUser = $userModel->where('username', $json->username)->first();
            if ($existingUser) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Username already exists.'
                ]);
            }

            $identityModel = model('CodeIgniter\Shield\Models\UserIdentityModel');
            $existingEmail = $identityModel->where('secret', $json->email)->first();
            if ($existingEmail) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Email already exists.'
                ]);
            }

            $user = new User([
                'username' => $json->username,
                'email' => $json->email,
                'password' => $json->password,
                'active' => true
            ]);

            $userModel->save($user);

            $createdUser = $userModel->where('username', $json->username)->first();

            if ($createdUser) {
                $createdUser->syncGroups($json->role);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'User created successfully.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error creating user: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while creating the user: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get details of a specific user (AJAX endpoint)
     *
     * @param int|null $id User ID to fetch
     * @return ResponseInterface JSON response with user data
     */
    public function getUser($id = null)
    {
        if (!auth()->user()->inGroup('superadmin', 'manager')) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'You do not have permission to view users.'
            ]);
        }

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'User ID is required.'
            ]);
        }

        try {
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $user = $userModel->find($id);

            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'User not found.'
                ]);
            }

            $userGroups = $user->getGroups();
            $role = !empty($userGroups) ? $userGroups[0] : 'user';

            return $this->response->setJSON([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'active' => $user->active,
                    'role' => $role
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error fetching user: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while fetching the user.'
            ]);
        }
    }

    /**
     * Update an existing user's information and role (AJAX endpoint)
     *
     * @return ResponseInterface JSON response with update result
     */
    public function update()
    {
        if (!auth()->user()->inGroup('superadmin', 'manager')) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'You do not have permission to update users.'
            ]);
        }

        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON([
                'success' => false,
                'message' => 'Method Not Allowed'
            ]);
        }

        $json = $this->request->getJSON();

        if (!$json || !isset($json->user_id, $json->username, $json->email, $json->role)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Missing required fields.'
            ]);
        }

        $userId = (int) $json->user_id;

        // Prevent users from changing their own role unless they're superadmin
        if ($userId === auth()->user()->id && $json->role !== 'superadmin' && auth()->user()->inGroup('superadmin')) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'You cannot change your own role.'
            ]);
        }

        // Validate role permission
        if ($json->role === 'superadmin' && !auth()->user()->inGroup('superadmin')) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Only superadmins can assign superadmin role.'
            ]);
        }

        try {
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $user = $userModel->find($userId);

            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'User not found.'
                ]);
            }

            $user->username = $json->username;
            $user->email = $json->email;
            $user->active = (bool) $json->active;

            $userModel->save($user);

            $user->syncGroups($json->role);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'User updated successfully.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error updating user: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while updating the user.'
            ]);
        }
    }

    /**
     * Delete a user (AJAX endpoint)
     * Restricted to superadmin only
     *
     * @param int|null $id User ID to delete
     * @return ResponseInterface JSON response with deletion result
     */
    public function delete($id = null)
    {
        if (!auth()->user()->inGroup('superadmin')) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Only superadmins can delete users.'
            ]);
        }

        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON([
                'success' => false,
                'message' => 'Method Not Allowed'
            ]);
        }

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'User ID is required.'
            ]);
        }

        if ((int) $id === auth()->user()->id) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'You cannot delete your own account.'
            ]);
        }

        try {
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $user = $userModel->find($id);
            
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'User not found.'
                ]);
            }
            
            // Delete user identities (email, etc.) before deleting the user
            $identityModel = model('CodeIgniter\Shield\Models\UserIdentityModel');
            $identityModel->where('user_id', $id)->delete();
            
            // Force permanent deletion (bypass soft deletes if enabled)
            $success = $userModel->delete($id, true);

            if ($success) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'User deleted successfully.'
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to delete user.'
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error deleting user: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'An error occurred while deleting the user.'
            ]);
        }
    }
}
