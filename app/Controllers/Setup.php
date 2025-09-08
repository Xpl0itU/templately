<?php

namespace App\Controllers;

use CodeIgniter\Shield\Entities\User;

class Setup extends BaseController
{
    public function index()
    {
        log_message('debug', 'Setup::index() called - Method: ' . $this->request->getMethod());
        
        // Check if setup is already completed
        if ($this->isSetupCompleted()) {
            return redirect()->to('/dashboard')->with('error', 'Application has already been set up.');
        }

        // Handle POST request (form submission)
        if (strtolower($this->request->getMethod()) === 'post') {
            log_message('debug', 'Processing POST request for setup');
            return $this->createSuperadmin();
        }

        // Handle GET request (show setup form)
        $systemCheck = $this->checkSystemRequirements();
        $dbCheck = $this->checkDatabaseConnection();

        return view('setup/index', [
            'systemCheck' => $systemCheck,
            'dbCheck' => $dbCheck
        ]);
    }

    public function createSuperadmin()
    {
        // Check if setup is already completed
        if ($this->isSetupCompleted()) {
            $contentType = $this->request->getHeaderLine('Content-Type');
            $isJsonRequest = strpos($contentType, 'application/json') !== false;
            
            if ($isJsonRequest) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Application has already been set up.'
                ]);
            }
            return redirect()->to('/dashboard')->with('error', 'Application has already been set up.');
        }

        // Get data from either JSON or POST
        $data = [];
        $contentType = $this->request->getHeaderLine('Content-Type');
        $isAjax = $this->request->hasHeader('X-Requested-With') && 
                  $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
        $isJsonRequest = (strpos($contentType, 'application/json') !== false) || $isAjax;
        
        log_message('debug', 'Request detection - Content-Type: ' . $contentType . ', isAjax: ' . ($isAjax ? 'yes' : 'no') . ', isJsonRequest: ' . ($isJsonRequest ? 'yes' : 'no'));
        
        if ($isJsonRequest && $this->request->getJSON()) {
            $json = $this->request->getJSON(true);
            $data = $json;
        } else if ($isJsonRequest) {
            // For AJAX requests that might not have JSON body
            $data = [
                'username' => $this->request->getPost('username'),
                'email' => $this->request->getPost('email'),
                'password' => $this->request->getPost('password'),
                'password_confirm' => $this->request->getPost('password_confirm'),
            ];
        } else {
            $data = [
                'username' => $this->request->getPost('username'),
                'email' => $this->request->getPost('email'),
                'password' => $this->request->getPost('password'),
                'password_confirm' => $this->request->getPost('password_confirm'),
            ];
        }

        $rules = [
            'username' => [
                'label' => 'Username',
                'rules' => 'required|min_length[3]|max_length[30]|alpha_numeric_punct|is_unique[users.username]',
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'required|max_length[254]|valid_email|is_unique[auth_identities.secret]',
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required|min_length[8]|max_byte[72]',
            ],
            'password_confirm' => [
                'label' => 'Confirm Password',
                'rules' => 'required|matches[password]',
            ],
        ];

        if (!$this->validate($rules, $data)) {
            if ($this->isJsonRequest()) {
                return $this->response->setJSON([
                    'success' => false,
                    'errors' => $this->validator->getErrors()
                ]);
            }
            return view('setup/index', [
                'validation' => $this->validator
            ]);
        }

        try {
            $users = model('CodeIgniter\Shield\Models\UserModel');
            
            // Start database transaction
            $db = \Config\Database::connect();
            $db->transBegin();
            
            // Create the user entity
            $userEntity = new User([
                'username' => $data['username'],
                'email'    => $data['email'],
                'active'   => true,
            ]);

            $users->save($userEntity);
            $insertId = $users->getInsertID();
            
            $user = $users->findById($insertId);
            
            if ($user === null) {
                throw new \Exception('Failed to create user account.');
            }

            // Check if email identity already exists for this user
            $identityModel = model('CodeIgniter\Shield\Models\UserIdentityModel');
            $existingIdentity = $identityModel->where('user_id', $user->id)
                                             ->where('type', 'email_password')
                                             ->where('secret', $data['email'])
                                             ->first();
            
            if (!$existingIdentity) {
                // Set the password only if identity doesn't exist
                $user->createEmailIdentity([
                    'email'    => $data['email'],
                    'password' => $data['password'],
                ]);
            } else {
                log_message('debug', 'Email identity already exists for user: ' . $user->id);
            }

            // Add user to superadmin group
            $user->addGroup('superadmin');

            // If we get here, commit the transaction
            $db->transCommit();

            // Mark setup as completed
            $this->markSetupCompleted();

            // Log the user in
            auth('session')->login($user);

            if ($this->isJsonRequest()) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Setup completed successfully!',
                    'redirect' => '/setup/success',
                    'user' => [
                        'username' => $data['username'],
                        'email' => $data['email']
                    ]
                ]);
            }

            // Redirect to success page with user data
            return redirect()->to('/setup/success')
                            ->with('message', 'Welcome to Templately! Your superadmin account has been created successfully.')
                            ->with('username', $data['username'])
                            ->with('email', $data['email']);

        } catch (\Exception $e) {
            // Rollback transaction if it was started
            if (isset($db) && $db->transStatus() !== false) {
                $db->transRollback();
            }
            
            log_message('error', 'Setup failed: ' . $e->getMessage());
            
            if ($this->isJsonRequest()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'An error occurred during setup: ' . $e->getMessage()
                ]);
            }
            
            return view('setup/index', [
                'error' => 'An error occurred during setup: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show setup success page
     */
    public function success()
    {
        // Check if setup is completed (user exists)
        if (!$this->isSetupCompleted()) {
            return redirect()->to('/setup');
        }

        // If user is logged in, show success page
        if (auth()->loggedIn()) {
            $data = [
                'username' => session('username') ?? auth()->user()->username,
                'email' => session('email') ?? auth()->user()->email
            ];
            return view('setup/success', $data);
        }

        // If setup is complete but user is not logged in, 
        // it means setup was successful but session expired
        // Show a simple success message and redirect to login
        return view('setup/success', [
            'username' => session('username') ?? 'Admin',
            'email' => session('email') ?? '',
            'session_expired' => true
        ]);
    }

    /**
     * Check if this is a JSON/AJAX request
     */
    private function isJsonRequest(): bool
    {
        $contentType = $this->request->getHeaderLine('Content-Type');
        $isAjax = $this->request->hasHeader('X-Requested-With') && 
                  $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
        return (strpos($contentType, 'application/json') !== false) || $isAjax;
    }

    /**
     * Check if the application setup is completed
     */
    private function isSetupCompleted(): bool
    {
        try {
            // Check if there are any users in the system
            $users = model('CodeIgniter\Shield\Models\UserModel');
            $userCount = $users->countAll();
            
            return $userCount > 0;
        } catch (\Exception $e) {
            // If there's an error checking users, assume setup is not completed
            return false;
        }
    }

    /**
     * Mark setup as completed (for future use if needed)
     */
    private function markSetupCompleted(): void
    {
        // For now, we just rely on user count, but we could add a setup flag to database or cache
        // This method is here for future extensibility
        cache()->save('app_setup_completed', true, 0); // Save indefinitely
    }

    /**
     * Check system requirements
     */
    private function checkSystemRequirements(): array
    {
        $requirements = [
            'php_version' => [
                'name' => 'PHP Version (>= 8.1)',
                'status' => version_compare(PHP_VERSION, '8.1.0', '>='),
                'current' => PHP_VERSION,
                'required' => '8.1.0+'
            ],
            'writable_dir' => [
                'name' => 'Writable Directory',
                'status' => is_writable(WRITEPATH),
                'current' => WRITEPATH,
                'required' => 'Must be writable'
            ],
            'uploads_dir' => [
                'name' => 'Uploads Directory',
                'status' => $this->checkUploadsDirectory(),
                'current' => WRITEPATH . 'uploads/',
                'required' => 'Must exist and be writable'
            ],
            'phpoffice_phpword' => [
                'name' => 'PhpOffice\PhpWord Library',
                'status' => class_exists('PhpOffice\PhpWord\TemplateProcessor'),
                'current' => class_exists('PhpOffice\PhpWord\TemplateProcessor') ? 'Installed' : 'Not found',
                'required' => 'Required for template processing'
            ],
            'codeigniter_shield' => [
                'name' => 'CodeIgniter Shield',
                'status' => class_exists('CodeIgniter\Shield\Models\UserModel'),
                'current' => class_exists('CodeIgniter\Shield\Models\UserModel') ? 'Installed' : 'Not found',
                'required' => 'Required for authentication'
            ]
        ];

        return $requirements;
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection(): array
    {
        try {
            $db = \Config\Database::connect();
            $db->query('SELECT 1');
            
            return [
                'status' => true,
                'message' => 'Database connection successful',
                'database' => $db->getDatabase()
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if uploads directory exists and is writable
     */
    private function checkUploadsDirectory(): bool
    {
        $uploadsDir = WRITEPATH . 'uploads/';
        
        if (!is_dir($uploadsDir)) {
            if (!mkdir($uploadsDir, 0755, true)) {
                return false;
            }
        }

        return is_writable($uploadsDir);
    }

    /**
     * API endpoint to check system requirements
     */
    public function checkRequirements()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $systemCheck = $this->checkSystemRequirements();
        $dbCheck = $this->checkDatabaseConnection();

        return $this->response->setJSON([
            'systemCheck' => $systemCheck,
            'dbCheck' => $dbCheck,
            'allPassed' => $this->allRequirementsPassed($systemCheck, $dbCheck)
        ]);
    }

    /**
     * Check if all requirements are met
     */
    private function allRequirementsPassed(array $systemCheck, array $dbCheck): bool
    {
        // Check if all system requirements pass
        foreach ($systemCheck as $requirement) {
            if (!$requirement['status']) {
                return false;
            }
        }

        // Check if database connection is working
        return $dbCheck['status'];
    }

    /**
     * Clean up existing data for testing (only during development)
     */
    /**
     * Debug method to check database state
     */
    public function debug()
    {
        try {
            $users = model('CodeIgniter\Shield\Models\UserModel');
            $userCount = $users->countAll();
            
            $db = \Config\Database::connect();
            $identityCount = $db->table('auth_identities')->countAllResults();
            $groupCount = $db->table('auth_groups_users')->countAllResults();
            
            $allUsers = $users->findAll();
            $userDetails = [];
            foreach ($allUsers as $user) {
                $userDetails[] = [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'active' => $user->active
                ];
            }
            
            return $this->response->setJSON([
                'userCount' => $userCount,
                'identityCount' => $identityCount,
                'groupCount' => $groupCount,
                'users' => $userDetails,
                'setupCompleted' => $this->isSetupCompleted()
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'error' => $e->getMessage()
            ]);
        }
    }

    public function cleanup()
    {
        if (ENVIRONMENT !== 'development') {
            return $this->response->setJSON(['error' => 'Not allowed in production']);
        }
        
        try {
            $db = \Config\Database::connect();
            
            // Clear all users and identities
            $db->query('DELETE FROM auth_groups_users');
            $db->query('DELETE FROM auth_identities');
            $db->query('DELETE FROM users');
            
            // Reset auto-increment counters
            $db->query('ALTER TABLE auth_identities AUTO_INCREMENT = 1');
            $db->query('ALTER TABLE users AUTO_INCREMENT = 1');
            $db->query('ALTER TABLE auth_groups_users AUTO_INCREMENT = 1');
            
            return $this->response->setJSON(['success' => true, 'message' => 'Database cleaned and auto-increment reset']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['error' => $e->getMessage()]);
        }
    }
}
