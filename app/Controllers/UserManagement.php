<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;

class UserManagement extends BaseController
{
    public function index()
    {
        // Check if user has admin privileges
        if (!auth()->user()->inGroup('superadmin', 'admin')) {
            return redirect()->to('/')->with('error', 'You do not have permission to access user management.');
        }

        $userModel = model('CodeIgniter\Shield\Models\UserModel');
        
        // Get groups from configuration
        $authGroups = config('AuthGroups');
        
        // Get all users
        $allUsers = $userModel->findAll();
        
        // Process users to include group information
        $users = [];
        foreach ($allUsers as $user) {
            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'active' => $user->active,
                'group' => null
            ];
            
            // Get user's groups
            $userGroups = $user->getGroups();
            if (!empty($userGroups)) {
                $userData['group'] = $userGroups[0]; // Get the first group
            } else {
                $userData['group'] = 'user'; // Default group
            }
            
            $users[] = $userData;
        }
        
        // Convert groups configuration to array format for the view
        $groups = [];
        foreach ($authGroups->groups as $groupKey => $groupData) {
            $groups[] = [
                'title' => $groupKey,
                'description' => $groupData['description'] ?? ''
            ];
        }
        
        $data = [
            'users' => $users,
            'groups' => $groups,
            'currentUser' => auth()->user()
        ];
        
        return view('user_management', $data);
    }
    
    public function updateUserGroup()
    {
        // Check if user has admin privileges
        if (!auth()->user()->inGroup('superadmin', 'admin')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage users.']);
        }
        
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        $json = $this->request->getJSON();
        
        if (empty($json) || !isset($json->user_id) || !isset($json->group)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid data received.']);
        }
        
        $userModel = model('CodeIgniter\Shield\Models\UserModel');
        $user = $userModel->find($json->user_id);
        
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User not found.']);
        }
        
        // Prevent removing admin privileges from the current user
        if ($user->id === auth()->user()->id && !in_array($json->group, ['superadmin', 'admin'])) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'You cannot remove your own admin privileges.']);
        }
        
        try {
            // Remove user from all groups first
            $user->syncGroups();
            
            // Add to new group
            $user->addGroup($json->group);
            
            return $this->response->setJSON(['success' => true, 'message' => 'User group updated successfully.']);
        } catch (\Exception $e) {
            log_message('error', 'Error updating user group: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while updating the user group.']);
        }
    }
    
    public function deleteUser($id = null)
    {
        // Check if user has superadmin privileges
        if (!auth()->user()->inGroup('superadmin')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Only superadmins can delete users.']);
        }
        
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }
        
        if (empty($id)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'User ID is required.']);
        }
        
        $userModel = model('CodeIgniter\Shield\Models\UserModel');
        $user = $userModel->find($id);
        
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'User not found.']);
        }
        
        // Prevent deleting own account
        if ($user->id === auth()->user()->id) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'You cannot delete your own account.']);
        }
        
        try {
            $userModel->delete($id);
            return $this->response->setJSON(['success' => true, 'message' => 'User deleted successfully.']);
        } catch (\Exception $e) {
            log_message('error', 'Error deleting user: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while deleting the user.']);
        }
    }
}
