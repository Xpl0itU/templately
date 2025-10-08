<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;

class Users extends BaseController
{
    public function index()
    {
        if (!auth()->user()->inGroup('superadmin', 'admin')) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access user management.');
        }

        $userModel = model('CodeIgniter\Shield\Models\UserModel');
        $allUsers = $userModel->findAll();
        
        $users = [];
        foreach ($allUsers as $user) {
            $userGroups = $user->getGroups();
            $role = !empty($userGroups) ? $userGroups[0] : 'user';
            
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
    
    public function create()
    {
        if (!auth()->user()->inGroup('superadmin', 'admin')) {
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

        // Validate role permission
        if ($json->role === 'superadmin' && !auth()->user()->inGroup('superadmin')) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false, 
                'message' => 'Only superadmins can create superadmin users.'
            ]);
        }

        try {
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            
            // Check if username already exists
            $existingUser = $userModel->where('username', $json->username)->first();
            if ($existingUser) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false, 
                    'message' => 'Username already exists.'
                ]);
            }
            
            // Check if email already exists
            $identityModel = model('CodeIgniter\Shield\Models\UserIdentityModel');
            $existingEmail = $identityModel->where('secret', $json->email)->first();
            if ($existingEmail) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false, 
                    'message' => 'Email already exists.'
                ]);
            }
            
            // Create the user
            $user = new User([
                'username' => $json->username,
                'email' => $json->email,
                'password' => $json->password,
                'active' => true
            ]);
            
            $userModel->save($user);
            
            // Get the created user
            $createdUser = $userModel->where('username', $json->username)->first();
            
            // Assign role
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
    
    public function getUser($id = null)
    {
        if (!auth()->user()->inGroup('superadmin', 'admin')) {
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
    
    public function update()
    {
        if (!auth()->user()->inGroup('superadmin', 'admin')) {
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

            // Update user data
            $user->username = $json->username;
            $user->email = $json->email;
            $user->active = (bool) $json->active;
            
            $userModel->save($user);
            
            // Update role
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
            $success = $userModel->delete($id);

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
