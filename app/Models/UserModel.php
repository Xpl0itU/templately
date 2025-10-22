<?php

namespace App\Models;

use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Authentication\Authenticators\Session;

/**
 * User Model
 * 
 * Extends Shield UserModel to support username-only authentication
 */
class UserModel extends ShieldUserModel
{
    /**
     * Find user by credentials (username or email)
     * 
     * Overrides parent to properly handle username-only authentication
     * in addition to email-based authentication
     * 
     * @param array $credentials User credentials (username or email + password)
     * @return User|null User entity or null if not found
     */
    public function findByCredentials(array $credentials): ?User
    {
        if (isset($credentials['email'])) {
            return parent::findByCredentials($credentials);
        }

        $username = $credentials['username'] ?? null;
        if ($username === null) {
            return null;
        }

        $userData = $this->where('username', $username)->asArray()->first();
        if ($userData === null) {
            return null;
        }

        $identity = model('UserIdentityModel')
            ->where('user_id', $userData['id'])
            ->where('type', Session::ID_TYPE_USERNAME)
            ->first();

        if ($identity === null) {
            $identity = model('UserIdentityModel')
                ->where('user_id', $userData['id'])
                ->where('type', Session::ID_TYPE_EMAIL_PASSWORD)
                ->first();
        }

        if ($identity === null) {
            return null;
        }

        $userData['password_hash'] = $identity->secret2;
        $userData['email'] = $identity->secret ?? null;

        $user = new $this->returnType($userData);
        $user->syncOriginal();

        return $user;
    }
}