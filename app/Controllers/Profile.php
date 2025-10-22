<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;

/**
 * Profile Controller
 * Handles user profile management including viewing profile and updating email/password
 */
class Profile extends BaseController
{
    protected $helpers = ['form'];

    /**
     * Display user profile page
     * Shows current user information including username and email
     *
     * @return ResponseInterface|string Profile view or redirect to login
     */
    public function index()
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->to('/login')->with('error', 'You must be logged in to view your profile.');
        }

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

    /**
     * Update user email address (AJAX endpoint)
     * Requires current password verification
     *
     * @return ResponseInterface JSON response with update result
     */
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

        if (!auth()->check(['password' => $currentPassword])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ]);
        }

        try {
            $identityModel = model('UserIdentityModel');
            
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

            $identity = $identityModel
                ->where('user_id', $user->id)
                ->where('type', 'email_password')
                ->first();

            if ($identity) {
                $identityModel->update($identity->id, [
                    'secret' => $email,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
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

    /**
     * Update user password (AJAX endpoint)
     * Requires current password verification and strong password validation
     *
     * @return ResponseInterface JSON response with update result
     */
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

        if (!auth()->check(['password' => $currentPassword])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ]);
        }

        try {
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
