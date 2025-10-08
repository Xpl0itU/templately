<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogTable extends Migration
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
                'comment' => 'ID of the user involved in the action',
            ],
            'target_user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID of the target user for user-specific actions',
            ],
            'timestamp' => [
                'type' => 'DATETIME',
                'null' => false,
                'comment' => 'When the action occurred',
            ],
            'action' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'Type of action performed',
            ],
            'resource_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Type of resource involved',
            ],
            'resource_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID of the specific resource',
            ],
            'permission' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
                'comment' => 'Permission being checked or modified',
            ],
            'result' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => false,
                'comment' => 'Result of the action',
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Reason or details about the action',
            ],
            'inheritance_source_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Type of source resource for inheritance',
            ],
            'inheritance_source_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID of the source resource for inheritance',
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
                'null' => true,
                'comment' => 'IP address of the user',
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'User agent string of the request',
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('target_user_id');
        $this->forge->addKey('timestamp');
        $this->forge->addKey('action');
        $this->forge->addKey(['resource_type', 'resource_id']);
        $this->forge->addKey('permission');
        $this->forge->addKey('result');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('target_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        
        $this->forge->createTable('audit_log', true);
    }

    public function down()
    {
        $this->forge->dropTable('audit_log', true);
    }
}