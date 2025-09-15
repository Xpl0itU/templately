<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Exceptions\ValidationException;
use CodeIgniter\Shield\Traits\Viewable;

class AuthController extends BaseController
{
    use Viewable;
    use ResponseTrait;

    protected $helpers = ['auth', 'setting'];

    public function loginView()
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config(\Config\Auth::class)->loginRedirect());
        }

        return $this->view(setting('Auth.views')['login']);
    }

    public function loginAction()
    {
        $rules = $this->getValidationRules('login');

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // If user is already logged in, log them out first to prevent session conflicts
        if (auth()->loggedIn()) {
            auth()->logout();
        }

        // Get credentials properly for username-only authentication
        $validFields = setting('Auth.validFields') ?? ['username'];
        $credentials = [];
        
        // Extract only the valid fields from POST data
        foreach ($validFields as $field) {
            if ($this->request->getPost($field)) {
                $credentials[$field] = $this->request->getPost($field);
            }
        }
        
        // Always include password
        $credentials['password'] = $this->request->getPost('password');
        $remember = (bool) $this->request->getPost('remember');

        /**
         * @var Session $authenticator 
         */
        $authenticator = auth('session')->getAuthenticator();

        // Add debugging
        log_message('debug', 'Login attempt with credentials: ' . json_encode($credentials));
        
        $result = $authenticator->remember($remember)->attempt($credentials);
        log_message('debug', 'Login result: ' . ($result->isOK() ? 'SUCCESS' : 'FAILED - ' . $result->reason()));
        
        if (! $result->isOK()) {
            return redirect()->route('login')->withInput()->with('error', $result->reason());
        }

        if ($authenticator->hasAction() && $result->extraInfo() !== null) {
            $authenticator->startLogin($result->extraInfo());
        }

        $user = $result->extraInfo() ?? $authenticator->getUser(); // Ensure $user is set

        if ($user->isBanned()) {
            $authenticator->logout();

            return redirect()->route('login')->withInput()->with('error', lang('Auth.bannedUser'));
        }

        // Ensure login is completed
        if ($result->extraInfo() !== null) {
            $authenticator->completeLogin($result->extraInfo());
        } else {
            // Make sure we have a user and complete login
            $user = $authenticator->getUser();
            if ($user !== null) {
                $authenticator->completeLogin($user);
            }
        }

        if (! $authenticator->hasAction()) {
            return redirect()->to(config(\Config\Auth::class)->loginRedirect())->withCookies();
        }

        return redirect()->to((string) $authenticator->getAction())->withCookies();
    }

    public function logoutAction()
    {
        auth()->logout();

        return redirect()->to(config(\Config\Auth::class)->logoutRedirect())->with('message', lang('Auth.logoutSuccess'));
    }

    public function registerView()
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config(\Config\Auth::class)->loginRedirect());
        }

        if (! setting('Auth.allowRegistration')) {
            return redirect()->back()->withInput()->with('error', lang('Auth.disabledRegistration'));
        }

        return $this->view(setting('Auth.views')['register']);
    }

    public function registerAction()
    {
        if (! setting('Auth.allowRegistration')) {
            return redirect()->back()->withInput()->with('error', lang('Auth.disabledRegistration'));
        }

        $users = model(setting('Auth.userProvider'));

        $rules = $this->getValidationRules('register');

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Get the POST data
        $allowedPostFields = array_keys($rules);
        $postData = $this->request->getPost($allowedPostFields);
        
        // If we're only using username for registration and no email field is provided,
        // add a dummy email address to prevent TypeError in the User entity
        $registrationFields = setting('Auth.registrationFields') ?? ['username', 'email'];
        if (!in_array('email', $registrationFields, true) && !isset($postData['email'])) {
            // Create a dummy email from the username
            $postData['email'] = $postData['username'] . '@example.com';
        }

        $userEntity = $this->getUserEntity();
        $userEntity->fill($postData);

        try {
            $users->save($userEntity);
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->with('errors', $users->errors());
        }

        $insertId = $users->getInsertID();
        
        $user = auth()->getProvider()->findById($insertId);
        
        if ($user === null) {
            return redirect()->back()->withInput()->with('error', 'Failed to create user account.');
        }

        $users->addToDefaultGroup($user);

        /**
         * @var Session $authenticator 
         */
        $authenticator = auth('session')->getAuthenticator();

        $authenticator->startLogin($user);

        if (!setting('Auth.requireEmailActivation') && !$authenticator->hasAction()) {
            $authenticator->completeLogin($user);
        }


        $redirectURL = $authenticator->hasAction() ? (string)$authenticator->getAction() : config(\Config\Auth::class)->registerRedirect();
        return redirect()->to($redirectURL)->with('message', lang('Auth.registrationSuccess'));
    }

    protected function getValidationRules(?string $type = null): array
    {
        if ($type === 'login') {
            $validFields = setting('Auth.validFields') ?? ['username'];
            $authConfig = config(\Config\Auth::class);
            $rules = [];
            
            if (in_array('email', $validFields, true)) {
                $emailRulesArray = $authConfig->emailValidationRules['rules'] ?? ['required', 'valid_email'];
                $rules['email'] = [
                    'label' => 'Auth.email',
                    'rules' => implode('|', $emailRulesArray),
                ];
            }
            if (in_array('username', $validFields, true)) {
                $usernameRulesArray = $authConfig->usernameValidationRules['rules'] ?? ['required'];
                $rules['username'] = [
                    'label' => 'Auth.username',
                    'rules' => implode('|', $usernameRulesArray),
                ];
            }
            $rules['password'] = [
                'label' => 'Auth.password',
                'rules' => 'required',
            ];
            return $rules;
        }

        $authConfig = config(\Config\Auth::class);
        
        $usernameRulesArray = $authConfig->usernameValidationRules['rules'] ?? ['required', 'min_length[3]'];
        $emailRulesArray = $authConfig->emailValidationRules['rules'] ?? ['required', 'valid_email'];
        
        $usernameRulesArray[] = 'is_unique[users.username]';
        $emailRulesArray[] = 'is_unique[auth_identities.secret,auth_identities.type,email]';
        
        $usernameRules = implode('|', $usernameRulesArray);
        $emailRules = implode('|', $emailRulesArray);


        $rules = [
            'password' => [
                'label'  => 'Auth.password',
                'rules'  => 'required|strong_password',
                'errors' => [
                    'strong_password' => lang('Auth.errorPasswordStrong'),
                ],
            ],
            'password_confirm' => [
                'label' => 'Auth.passwordConfirm',
                'rules' => 'required|matches[password]',
            ],
        ];

        $registrationFields = setting('Auth.registrationFields') ?? ['username', 'email'];

        if (in_array('username', $registrationFields, true)) {
             $rules['username'] = [
                'label' => 'Auth.username',
                'rules' => $usernameRules,
             ];
        }
        if (in_array('email', $registrationFields, true)) {
            $rules['email'] = [
               'label' => 'Auth.email',
               'rules' => $emailRules,
            ];
        }
        return $rules;
    }

    protected function getUserEntity(): \CodeIgniter\Shield\Entities\User
    {
        return new \CodeIgniter\Shield\Entities\User();
    }
}
