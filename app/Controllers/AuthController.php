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

        $credentials             = $this->request->getPost(setting('Auth.validFields'));
        $credentials             = array_filter($credentials);
        $credentials['password'] = $this->request->getPost('password');
        $remember                = (bool) $this->request->getPost('remember');

        /**
 * @var Session $authenticator 
*/
        $authenticator = auth('session')->getAuthenticator();

        $result = $authenticator->remember($remember)->attempt($credentials);
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

        if ($result->extraInfo() !== null) {
            $authenticator->completeLogin($result->extraInfo());
        }


        if (! $authenticator->hasAction()) {
            return redirect()->to(config(\Config\Auth::class)->loginRedirect());
        }

        return redirect()->to((string) $authenticator->getAction());
    }

    public function logoutAction()
    {
        // Capture the user before the session is destroyed.
        $user = auth()->user();

        auth()->logout();
        
        // Shield's logout() method should handle the event dispatching.
        // If custom event needed: Events::trigger('logout', $user);

        return redirect()->to(config(\Config\Auth::class)->logoutRedirect())->with('message', lang('Auth.logoutSuccess'));
    }

    /**
     * Displays the registration form.
     */
    public function registerView()
    {
        if (auth()->loggedIn()) {
            return redirect()->to(config(\Config\Auth::class)->loginRedirect());
        }

        // Check if registration is allowed
        if (! setting('Auth.allowRegistration')) {
            return redirect()->back()->withInput()->with('error', lang('Auth.disabledRegistration'));
        }

        return $this->view(setting('Auth.views')['register']);
    }

    /**
     * Attempts to register the user.
     */
    public function registerAction()
    {
        // Check if registration is allowed
        if (! setting('Auth.allowRegistration')) {
            return redirect()->back()->withInput()->with('error', lang('Auth.disabledRegistration'));
        }

        $users = model(setting('Auth.userProvider'));

        // Validate here first, since some things,
        // like the password, can only be validated properly here.
        $rules = $this->getValidationRules('register');

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Save the user
        $allowedPostFields = array_keys($rules);
        $userEntity              = $this->getUserEntity();
        $userEntity->fill($this->request->getPost($allowedPostFields));

        // Workaround for email only registration/login
        if ($userEntity->username === null && in_array('username', $allowedPostFields, true)) {
            // If username is part of the form but not provided (e.g. for email-only login type)
            // ensure it's explicitly set to null if your database schema allows it,
            // or handle as per your specific user model requirements.
            // Shield's default User entity handles username being null if not in $validFields or $allowedPostFields.
        }


        try {
            $users->save($userEntity);
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->with('errors', $users->errors());
        }

        // To get the complete user object with ID, we need to get from the database
        $user = $users->findById($users->getInsertID());

        // Add to default group
        $users->addToDefaultGroup($user);

        /**
 * @var Session $authenticator 
*/
        $authenticator = auth('session')->getAuthenticator();

        // If an action is required after registration (e.g. email activation)
        if ($authenticator->hasAction()) {
            $authenticator->startLogin($user); // Prepares the action
        }

        // If no action (like email activation) is set, complete the login directly.
        // Otherwise, the action (e.g., AuthAction\EmailActivator) will handle completion.
        if (!setting('Auth.requireEmailActivation') && !$authenticator->hasAction()) {
            $authenticator->completeLogin($user);
        }


        // Success!
        $redirectURL = $authenticator->hasAction() ? (string)$authenticator->getAction() : config(\Config\Auth::class)->registerRedirect();
        return redirect()->to($redirectURL)->with('message', lang('Auth.registrationSuccess'));
    }

    /**
     * Returns the rules that should be used for validation.
     *
     * @param          string|null $type The type of rules to return. (login|register)
     * @return         array<string, array<string, array<string>|string>>
     * @phpstan-return array<string, array<string, string|list<string>>>
     */
    protected function getValidationRules(?string $type = null): array
    {
        if ($type === 'login') {
            // Login uses fixed fields: 'email' or 'username' (defined by 'Auth.validFields') and 'password'.
            $validFields = setting('Auth.validFields') ?? ['email'];
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

        // Registration rules
        // Shield v1.1.0 stores rules as arrays with 'rules' key
        $authConfig = config(\Config\Auth::class);
        
        // Extract rules arrays and convert to strings
        $usernameRulesArray = $authConfig->usernameValidationRules['rules'] ?? ['required', 'min_length[3]'];
        $emailRulesArray = $authConfig->emailValidationRules['rules'] ?? ['required', 'valid_email'];
        
        // Add uniqueness checks for Shield's table structure
        $usernameRulesArray[] = 'is_unique[users.username]';
        $emailRulesArray[] = 'is_unique[auth_identities.secret,auth_identities.type,email]';
        
        // Convert arrays to pipe-separated strings
        $usernameRules = implode('|', $usernameRulesArray);
        $emailRules = implode('|', $emailRulesArray);


        $rules = [
            'password' => [
                'label'  => 'Auth.password',
                'rules'  => 'required|strong_password', // strong_password is a Shield rule
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

        // Add username rules if 'username' is in the allowed fields for registration
        if (in_array('username', $registrationFields, true)) {
             $rules['username'] = [
                'label' => 'Auth.username',
                'rules' => $usernameRules,
             ];
        }
        // Add email rules if 'email' is in the allowed fields for registration
        if (in_array('email', $registrationFields, true)) {
            $rules['email'] = [
               'label' => 'Auth.email',
               'rules' => $emailRules,
            ];
        }
        return $rules;
    }

    /**
     * Returns the User entity that should be used.
     *
     * @return \CodeIgniter\Shield\Entities\User
     */
    protected function getUserEntity(): \CodeIgniter\Shield\Entities\User
    {
        return new \CodeIgniter\Shield\Entities\User();
    }
}
