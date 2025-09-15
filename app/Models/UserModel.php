<?php

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Authentication\Authenticators\Session;

class UserModel extends ShieldUserModel
{
    /**
     * Override the findByCredentials method to properly handle username-only authentication
     */
    public function findByCredentials(array $credentials): ?User
    {
        // Handle email authentication through parent method
        if (isset($credentials['email'])) {
            return parent::findByCredentials($credentials);
        }

        // Handle username-only authentication
        $username = $credentials['username'] ?? null;
        if ($username === null) {
            return null;
        }

        // First get the user from the users table with all fields
        $userData = $this->where('username', $username)->asArray()->first();
        if ($userData === null) {
            return null;
        }

        // Now get the password hash from the identities table
        $identity = model('UserIdentityModel')
            ->where('user_id', $userData['id'])
            ->where('type', Session::ID_TYPE_EMAIL_PASSWORD) // Still using email_password because that's what we create
            ->first();

        if ($identity === null) {
            return null;
        }

        // Set the password hash on the user data
        $userData['password_hash'] = $identity->secret2;
        // Set email from identity if it exists
        $userData['email'] = $identity->secret ?? null;

        // Create user object - we need to handle this carefully to avoid the private method call
        $user = new $this->returnType($userData);
        $user->syncOriginal();

        return $user;
    }
}