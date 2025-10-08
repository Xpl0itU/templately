<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MarkAclTablesAsMigrated extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->DBDriver === 'SQLite3') {
            return;
        }
        // Check if the migration record already exists
        $result = $this->db->table('migrations')
            ->where('version', '2025-09-15-150006')
            ->where('class', 'CreateAclTables')
            ->countAllResults();
        
        if ($result == 0) {
            // Insert a record to mark the migration as completed
            $data = [
                'version' => '2025-09-15-150006',
                'class' => 'CreateAclTables',
                'namespace' => 'App',
                'group' => 'default',
                'time' => time(),  // Use current Unix timestamp
                'batch' => 5  // Use a batch number that's higher than existing
            ];
            
            $this->db->table('migrations')->insert($data);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->DBDriver === 'SQLite3') {
            return;
        }
        $this->db->table('migrations')
            ->where('version', '2025-09-15-150006')
            ->where('class', 'CreateAclTables')
            ->delete();
    }
}