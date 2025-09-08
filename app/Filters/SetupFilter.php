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
        
        // Skip setup check for setup routes, auth routes, and static assets
        $skipPatterns = [
            '/setup',
            '/login',
            '/register',
            '/logout',
            '/auth/',
            '/api/'
        ];
        
        // Check if current path should be skipped
        foreach ($skipPatterns as $pattern) {
            if (strpos($currentPath, $pattern) === 0) {
                return;
            }
        }
        
        // Skip for AJAX requests
        if ($request->hasHeader('X-Requested-With') && $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return;
        }
        
        // Skip for static assets
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/i', $currentPath)) {
            return;
        }

        // Check if setup is needed
        if ($this->isSetupRequired()) {
            return redirect()->to('/setup');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do here
    }

    /**
     * Check if setup is required (no users exist)
     */
    private function isSetupRequired(): bool
    {
        try {
            // Check if there are any users in the system
            $users = model('CodeIgniter\Shield\Models\UserModel');
            $userCount = $users->countAll();
            
            return $userCount === 0;
        } catch (\Exception $e) {
            // If there's an error checking users (e.g., database not set up),
            // assume setup is required
            return true;
        }
    }
}
