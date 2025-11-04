<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePermissionAuditLogTable extends Migration
{
    public function up()
    {
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
                'comment' => 'User who performed the action (NULL for system actions)',
            ],
            'target_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'User affected by the action (when applicable)',
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'Type of action (grant, revoke, check, etc.)',
            ],
            'resource_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Type of resource affected (NULL for general permissions)',
            ],
            'resource_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID of specific resource affected',
            ],
            'permission' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'comment' => 'Specific permission involved',
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
                'comment' => 'IP address of the request',
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'User agent string of the request',
            ],
            'result' => [
                'type' => 'ENUM',
                'constraint' => ['allowed', 'denied', 'error'],
                'null' => false,
                'comment' => 'Result of the permission check',
            ],
            'details' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Additional details about the action',
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
        $this->forge->addKey('permission');
        $this->forge->addKey('result');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('target_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('permission_audit_log', true);
    }

    public function down()
    {
        $this->forge->dropTable('permission_audit_log', true);
    }
}