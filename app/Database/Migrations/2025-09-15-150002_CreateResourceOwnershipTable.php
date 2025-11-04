<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateResourceOwnershipTable extends Migration
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
                'comment' => 'User who owns the resource',
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
            'ownership_type' => [
                'type' => 'ENUM',
                'constraint' => ['owner', 'creator', 'maintainer'],
                'null' => false,
                'default' => 'owner',
                'comment' => 'Type of ownership relationship',
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
        $this->forge->addKey(['user_id', 'resource_type', 'resource_id'], false, true);
        $this->forge->addKey(['resource_type', 'resource_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('resource_ownership', true);
    }

    public function down()
    {
        $this->forge->dropTable('resource_ownership', true);
    }
}
