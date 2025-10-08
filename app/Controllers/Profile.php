<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;

class Profile extends BaseController
{
    protected $helpers = ['form'];

    public function index()
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->to('/login')->with('error', 'You must be logged in to view your profile.');
        }

        // Get user's email from auth_identities table
        $identityModel = model('UserIdentityModel');
        $emailIdentity = $identityModel
            ->where('user_id', $user->id)
            ->where('type', 'email_password')
            ->first();

        $email = $emailIdentity ? $emailIdentity->secret : null;

        return view('profile/index', [
            'user' => $user,
            'email' => $email,
            'title' => 'My Profile'
        ]);
    }

    public function updateEmail()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $user = auth()->user();
        
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'You must be logged in.'
            ]);
        }

        $rules = [
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[254]',
            ],
            'current_password' => [
                'label' => 'Current Password',
                'rules' => 'required',
            ],
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $email = $this->request->getPost('email');
        $currentPassword = $this->request->getPost('current_password');

        // Verify current password
        if (!auth()->check(['password' => $currentPassword])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ]);
        }

        try {
            $identityModel = model('UserIdentityModel');
            
            // Check if email already exists for another user
            $existingIdentity = $identityModel
                ->where('type', 'email_password')
                ->where('secret', $email)
                ->where('user_id !=', $user->id)
                ->first();

            if ($existingIdentity) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'This email is already in use by another account.'
                ]);
            }

            // Update or create email identity
            $identity = $identityModel
                ->where('user_id', $user->id)
                ->where('type', 'email_password')
                ->first();

            if ($identity) {
                // Update existing email
                $identityModel->update($identity->id, [
                    'secret' => $email,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Create new email identity
                $identityModel->insert([
                    'user_id' => $user->id,
                    'type' => 'email_password',
                    'secret' => $email,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Email updated successfully.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error updating email: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while updating your email.'
            ]);
        }
    }

    public function updatePassword()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $user = auth()->user();
        
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'You must be logged in.'
            ]);
        }

        $rules = [
            'current_password' => [
                'label' => 'Current Password',
                'rules' => 'required',
            ],
            'new_password' => [
                'label' => 'New Password',
                'rules' => 'required|min_length[8]|max_byte[72]|strong_password',
                'errors' => [
                    'strong_password' => 'The password must be at least 8 characters long and contain a mix of uppercase, lowercase, numbers, and symbols.'
                ]
            ],
            'confirm_password' => [
                'label' => 'Confirm Password',
                'rules' => 'required|matches[new_password]',
            ],
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $currentPassword = $this->request->getPost('current_password');
        $newPassword = $this->request->getPost('new_password');

        // Verify current password
        if (!auth()->check(['password' => $currentPassword])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ]);
        }

        try {
            // Update password using Shield's built-in method
            $user->password = $newPassword;
            
            $userModel = model('CodeIgniter\Shield\Models\UserModel');
            $userModel->save($user);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Password updated successfully.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error updating password: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while updating your password.'
            ]);
        }
    }
}
