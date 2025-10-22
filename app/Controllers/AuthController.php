<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Exceptions\ValidationException;
use CodeIgniter\Shield\Traits\Viewable;

/**
 * AuthController
 * Handles user authentication including login, logout, and registration
 * Extends CodeIgniter Shield authentication with custom username-only support
 */
class AuthController extends BaseController
{
    use Viewable;
    use ResponseTrait;

    protected $helpers = ['auth', 'setting'];

    /**
     * Display login page
     * Redirects to dashboard if user is already logged in
     *
     * @return ResponseInterface|string Login view or redirect
     */
    public function loginView()
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config(\Config\Auth::class)->loginRedirect());
        }

        return $this->view(setting('Auth.views')['login']);
    }

    /**
     * Process login form submission
     * Supports username-only or email+username authentication
     *
     * @return ResponseInterface Redirect to dashboard or back to login with errors
     */
    public function loginAction()
    {
        $rules = $this->getValidationRules('login');

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Prevent session conflicts by logging out if already logged in
        if (auth()->loggedIn()) {
            auth()->logout();
        }

        $validFields = setting('Auth.validFields') ?? ['username'];
        $credentials = [];
        
        foreach ($validFields as $field) {
            if ($this->request->getPost($field)) {
                $credentials[$field] = $this->request->getPost($field);
            }
        }
        
        $credentials['password'] = $this->request->getPost('password');
        $remember = (bool) $this->request->getPost('remember');

        /**
         * @var Session $authenticator 
         */
        $authenticator = auth('session')->getAuthenticator();

        log_message('debug', 'Login attempt with credentials: ' . json_encode($credentials));
        
        $result = $authenticator->remember($remember)->attempt($credentials);
        log_message('debug', 'Login result: ' . ($result->isOK() ? 'SUCCESS' : 'FAILED - ' . $result->reason()));
        
        if (! $result->isOK()) {
            return redirect()->route('login')->withInput()->with('error', $result->reason());
        }

        if ($authenticator->hasAction() && $result->extraInfo() !== null) {
            $authenticator->startLogin($result->extraInfo());
        }

        $user = $result->extraInfo() ?? $authenticator->getUser();

        if ($user->isBanned()) {
            $authenticator->logout();

            return redirect()->route('login')->withInput()->with('error', lang('Auth.bannedUser'));
        }

        if ($result->extraInfo() !== null) {
            $authenticator->completeLogin($result->extraInfo());
        } else {
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

    /**
     * Process logout action
     * Clears user session and redirects to logout page
     *
     * @return ResponseInterface Redirect to logout page
     */
    public function logoutAction()
    {
        auth()->logout();

        return redirect()->to(config(\Config\Auth::class)->logoutRedirect())->with('message', lang('Auth.logoutSuccess'));
    }

    /**
     * Display registration page
     * Redirects if user is already logged in or registration is disabled
     *
     * @return ResponseInterface|string Registration view or redirect
     */
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

    /**
     * Process registration form submission
     * Creates new user account and adds to default group
     *
     * @return ResponseInterface Redirect to dashboard or back to registration with errors
     */
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

        $allowedPostFields = array_keys($rules);
        $postData = $this->request->getPost($allowedPostFields);
        
        // Add dummy email for username-only registration to prevent TypeError
        $registrationFields = setting('Auth.registrationFields') ?? ['username', 'email'];
        if (!in_array('email', $registrationFields, true) && !isset($postData['email'])) {
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

    /**
     * Get validation rules for login or registration
     *
     * @param string|null $type Type of validation ('login' or 'register')
     * @return array Validation rules configuration
     */
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

    /**
     * Get new user entity instance for registration
     *
     * @return \CodeIgniter\Shield\Entities\User User entity
     */
    protected function getUserEntity(): \CodeIgniter\Shield\Entities\User
    {
        return new \CodeIgniter\Shield\Entities\User();
    }
}
