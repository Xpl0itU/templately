<?php

namespace App\Controllers;

class TestSetup extends BaseController
{
    public function index()
    {
        // Test the setup detection logic
        try {
            // Check if the users table exists
            $db = \Config\Database::connect();
            $tables = $db->listTables();
            
            // Get the users table name from the Shield configuration
            $usersModel = model('CodeIgniter\Shield\Models\UserModel');
            $usersTable = $usersModel->table;
            
            // If users table doesn't exist, setup is required
            $tableExists = in_array($usersTable, $tables, true);
            
            // Check if there are any users in the system
            $userCount = $tableExists ? $usersModel->countAll() : 0;
            
            $setupRequired = !$tableExists || $userCount === 0;
            
            return $this->response->setJSON([
                'tables' => $tables,
                'usersTable' => $usersTable,
                'tableExists' => $tableExists,
                'userCount' => $userCount,
                'setupRequired' => $setupRequired
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'error' => $e->getMessage(),
                'setupRequired' => true
            ]);
        }
    }
}