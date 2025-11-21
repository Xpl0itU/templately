<?php

namespace Tests\Support\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTestTables extends Migration
{
    public function up()
    {
        // Drop existing tables if they exist
        $this->forge->dropTable('user_group_members', true);
        $this->forge->dropTable('user_groups', true);
        $this->forge->dropTable('resource_owners', true);
        $this->forge->dropTable('filledFiles', true);
        $this->forge->dropTable('templateFiles', true);
        $this->forge->dropTable('users', true);
        
        // Users table (simplified for testing)
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'unique'     => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'unique'     => true,
            ],
            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'active' => [
                'type'       => 'INTEGER',
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('users');

        // Templates table
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'originalFileName' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'path' => [
                'type'       => 'VARCHAR',
                'constraint' => '500',
            ],
            'size' => [
                'type'     => 'INTEGER',
                'unsigned' => true,
            ],
            'templateFields' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'createdAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updatedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('templateFiles');

        // Filled files table
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'templateFileId' => [
                'type'     => 'INTEGER',
                'unsigned' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'filledData' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'fieldTypes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'createdAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updatedAt' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('filledFiles');

        // Resource owners table
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'resource_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            'resource_id' => [
                'type'     => 'INTEGER',
                'unsigned' => true,
            ],
            'owner_id' => [
                'type'     => 'INTEGER',
                'unsigned' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('resource_owners');

        // User groups table
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'unique'     => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('user_groups');

        // User group members table
        $this->forge->addField([
            'user_id' => [
                'type'     => 'INTEGER',
                'unsigned' => true,
            ],
            'group_id' => [
                'type'     => 'INTEGER',
                'unsigned' => true,
            ],
            'added_by' => [
                'type'     => 'INTEGER',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey(['user_id', 'group_id'], true);
        $this->forge->createTable('user_group_members');
    }

    public function down()
    {
        $this->forge->dropTable('user_group_members', true);
        $this->forge->dropTable('user_groups', true);
        $this->forge->dropTable('resource_owners', true);
        $this->forge->dropTable('filledFiles', true);
        $this->forge->dropTable('templateFiles', true);
        $this->forge->dropTable('users', true);
    }
}
