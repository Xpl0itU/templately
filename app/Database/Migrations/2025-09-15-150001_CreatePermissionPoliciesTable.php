<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePermissionPoliciesTable extends Migration
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
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'Descriptive name for the policy',
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Detailed description of the policy',
            ],
            'resource_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'Type of resource this policy applies to',
            ],
            'effect' => [
                'type' => 'ENUM',
                'constraint' => ['allow', 'deny'],
                'null' => false,
                'default' => 'deny',
                'comment' => 'Whether this policy allows or denies access',
            ],
            'conditions' => [
                'type' => 'JSON',
                'null' => false,
                'comment' => 'JSON-encoded conditions for the policy',
            ],
            'priority' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => false,
                'default' => 0,
                'comment' => 'Priority level for policy evaluation',
            ],
            'is_active' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
                'comment' => 'Whether this policy is currently active',
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->addKey('resource_type');
        $this->forge->addKey('is_active');
        $this->forge->addKey('priority');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('permission_policies', true);
    }

    public function down()
    {
        $this->forge->dropTable('permission_policies', true);
    }
}
