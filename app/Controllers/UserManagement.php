<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;

class UserManagement extends BaseController
{
    public function index()
    {
        if (!auth()->user()->inGroup('superadmin', 'admin')) {
            return redirect()->to('/')->with('error', 'You do not have permission to access user management.');
        }

        $userModel = model('CodeIgniter\Shield\Models\UserModel');
        
        /**
 * @var \CodeIgniter\Shield\Config\AuthGroups $authGroups 
*/
        $authGroups = config('AuthGroups');
        
        $allUsers = $userModel->findAll();
        
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
            
            $userGroups = $user->getGroups();
            if (!empty($userGroups)) {
                $userData['group'] = $userGroups[0];
            } else {
                $userData['group'] = 'user';
            }
            
            $users[] = $userData;
        }
        
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
        if (!auth()->user()->inGroup('superadmin', 'admin')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to manage users.']);
        }
        
        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        $json = $this->request->getJSON();
        
        if (!$json || !isset($json->user_id, $json->group)) {
            return $this->response->setStatusCode(400)->setJSON(
                [
                'success' => false, 
                'message' => 'Missing user ID or group.'
                ]
            );
        }

        $userId = (int) $json->user_id;
        $newGroup = $json->group;

        if ($userId === auth()->user()->id && $newGroup !== 'superadmin') {
            return $this->response->setStatusCode(400)->setJSON(
                [
                'success' => false, 
                'message' => 'You cannot change your own group.'
                ]
            );
        }

        try {
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $user = $userModel->find($userId);
            
            if (!$user) {
                return $this->response->setStatusCode(404)->setJSON(
                    [
                    'success' => false, 
                    'message' => 'User not found.'
                    ]
                );
            }

            $user->syncGroups($newGroup);

            return $this->response->setJSON(
                [
                'success' => true, 
                'message' => 'User group updated successfully.'
                ]
            );

        } catch (\Exception $e) {
            log_message('error', 'Error updating user group: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(
                [
                'success' => false, 
                'message' => 'An error occurred while updating the user group.'
                ]
            );
        }
    }

    public function deleteUser($id = null)
    {
        if (!auth()->user()->inGroup('superadmin')) {
            return $this->response->setStatusCode(403)->setJSON(
                [
                'success' => false, 
                'message' => 'You do not have permission to delete users.'
                ]
            );
        }

        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(
                [
                'success' => false, 
                'message' => 'Method Not Allowed'
                ]
            );
        }

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(
                [
                'success' => false, 
                'message' => 'User ID is required.'
                ]
            );
        }

        if ((int) $id === auth()->user()->id) {
            return $this->response->setStatusCode(400)->setJSON(
                [
                'success' => false, 
                'message' => 'You cannot delete your own account.'
                ]
            );
        }

        try {
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $success = $userModel->delete($id);

            if ($success) {
                return $this->response->setJSON(
                    [
                    'success' => true, 
                    'message' => 'User deleted successfully.'
                    ]
                );
            } else {
                return $this->response->setStatusCode(500)->setJSON(
                    [
                    'success' => false, 
                    'message' => 'Failed to delete user.'
                    ]
                );
            }

        } catch (\Exception $e) {
            log_message('error', 'Error deleting user: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(
                [
                'success' => false, 
                'message' => 'An error occurred while deleting the user.'
                ]
            );
        }
    }
}
