<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdatePermissionAuditLogTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $isSqlite = $db->DBDriver === 'SQLite3';

        if ($isSqlite) {
            // SQLite migrations handled via base create migration; updates below rely on MySQL syntax.
            return;
        }
        // Check if the table exists
    if (!$db->tableExists('permission_audit_log')) {
            // Create the table if it doesn't exist
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'target_user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'action' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false,
                ],
                'resource_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                ],
                'resource_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                ],
                'permission' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'ip_address' => [
                    'type' => 'VARCHAR',
                    'constraint' => 45,
                    'null' => true,
                ],
                'user_agent' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'result' => [
                    'type' => 'ENUM',
                    'constraint' => ['allowed', 'denied', 'error'],
                    'null' => false,
                ],
                'details' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);
            
            $this->forge->addKey('id', true);
            $this->forge->addKey('user_id');
            $this->forge->addKey('target_user_id');
            $this->forge->addKey('action');
            $this->forge->addKey('resource_type');
            $this->forge->addKey('resource_id');
            $this->forge->addKey('permission');
            $this->forge->addKey('created_at');
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('target_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('permission_audit_log', true);
        } else {
            // Update existing table structure if needed
            // Add any missing columns or modify existing ones
            
            // Check if target_user_id column exists
            if (!$db->fieldExists('target_user_id', 'permission_audit_log')) {
                $this->forge->addColumn('permission_audit_log', [
                    'target_user_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'after' => 'user_id',
                    ],
                ]);
                
                // Add foreign key for target_user_id
                $this->forge->addForeignKey('target_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            }
            
            // Check if resource_type column exists
            if (!$db->fieldExists('resource_type', 'permission_audit_log')) {
                $this->forge->addColumn('permission_audit_log', [
                    'resource_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => true,
                        'after' => 'action',
                    ],
                ]);
            }
            
            // Check if resource_id column exists
            if (!$db->fieldExists('resource_id', 'permission_audit_log')) {
                $this->forge->addColumn('permission_audit_log', [
                    'resource_id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'null' => true,
                        'after' => 'resource_type',
                    ],
                ]);
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        if ($db->DBDriver === 'SQLite3') {
            return;
        }
        // Don't drop the table in down migration to preserve audit logs
        // Just remove added columns if they exist
        if ($db->fieldExists('target_user_id', 'permission_audit_log')) {
            $this->forge->dropColumn('permission_audit_log', 'target_user_id');
        }

        if ($db->fieldExists('resource_type', 'permission_audit_log')) {
            $this->forge->dropColumn('permission_audit_log', 'resource_type');
        }

        if ($db->fieldExists('resource_id', 'permission_audit_log')) {
            $this->forge->dropColumn('permission_audit_log', 'resource_id');
        }
    }
}