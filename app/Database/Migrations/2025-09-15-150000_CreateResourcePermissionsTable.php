<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateResourcePermissionsTable extends Migration
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
                'null' => false,
            ],
            'resource_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'Type of resource (template, filled_file, etc.)',
            ],
            'resource_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'ID of the specific resource',
            ],
            'permission' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'Specific permission (view, edit, delete, etc.)',
            ],
            'scope' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Scope of permission (global, department, owner, etc.)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Optional expiration date for temporary permissions',
            ],
        ]);
        
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'resource_type', 'resource_id']);
        $this->forge->addKey('resource_type');
        $this->forge->addKey('resource_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('resource_permissions', true);
    }

    public function down()
    {
        $this->forge->dropTable('resource_permissions', true);
    }
}