<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFilledFilesTableMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'templateFileId' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'filledData' => [
                'type' => 'TEXT', // Will be JSON encoded data
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('templateFileId', 'templateFiles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('filledFiles', true);
    }

    public function down()
    {
        $this->forge->dropTable('filledFiles', true);
    }
}
