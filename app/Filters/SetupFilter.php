<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SetupFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Get the current path
        $currentPath = $request->getUri()->getPath();
        
        log_message('debug', 'SetupFilter: Checking path ' . $currentPath);
        
        // Skip setup check for setup routes and API routes
        $skipPatterns = [
            '/setup',
            '/index.php/setup',
            '/api/',
            '/index.php/api/'
        ];
        
        // Check if current path should be skipped
        foreach ($skipPatterns as $pattern) {
            if (strpos($currentPath, $pattern) === 0) {
                log_message('debug', 'SetupFilter: Skipping path ' . $currentPath);
                return;
            }
        }
        
        // Skip for AJAX requests
        if ($request->hasHeader('X-Requested-With') && $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            log_message('debug', 'SetupFilter: Skipping AJAX request');
            return;
        }
        
        // Skip for static assets
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/i', $currentPath)) {
            log_message('debug', 'SetupFilter: Skipping static asset');
            return;
        }

        // Check if setup is needed
        $setupRequired = $this->isSetupRequired();
        log_message('debug', 'SetupFilter: Setup required = ' . ($setupRequired ? 'true' : 'false'));
        
        if ($setupRequired) {
            log_message('debug', 'SetupFilter: Redirecting to setup page');
            // Redirect to setup page regardless of current path
            return redirect()->to('/setup');
        }
        
        log_message('debug', 'SetupFilter: Not redirecting, setup not required or already on setup page');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do here
    }

    /**
     * Check if setup is required (no users exist or users table doesn't exist)
     */
    private function isSetupRequired(): bool
    {
        try {
            log_message('debug', 'SetupFilter: Checking if setup is required');
            
            // First check if we have a cache entry indicating setup is completed
            $cacheEntry = cache('app_setup_completed');
            if ($cacheEntry === true) {
                log_message('debug', 'SetupFilter: Cache indicates setup completed');
                return false;
            }
            
            // Check if the users table exists
            $db = \Config\Database::connect();
            $tables = $db->listTables();

            log_message('debug', 'SetupFilter: Database tables: ' . implode(', ', $tables));

            // Get the users table name from the Shield configuration
            $usersModel = model('CodeIgniter\Shield\Models\UserModel');
            $usersTable = $usersModel->table;
            
            log_message('debug', 'SetupFilter: Users table name: ' . $usersTable);
            
            // If users table doesn't exist, setup is required
            if (! $db->tableExists($usersTable)) {
                log_message('debug', 'SetupFilter: Users table does not exist, setup required');
                return true;
            }
            
            // Check if there are any users in the system
            $userCount = $db->table($usersTable)->countAll();
            
            log_message('debug', 'SetupFilter: User count: ' . $userCount);
            
            $setupRequired = $userCount === 0;
            log_message('debug', 'SetupFilter: Setup required based on user count: ' . ($setupRequired ? 'true' : 'false'));
            
            return $setupRequired;
        } catch (\Exception $e) {
            // If there's an error checking users (e.g., database not set up or table doesn't exist),
            // assume setup is required
            log_message('debug', 'SetupFilter: Exception occurred - ' . $e->getMessage());
            return true;
        }
    }
}
