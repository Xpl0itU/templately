<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTemplateFilesTableMigration extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name'        => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'path'        => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'size'        => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
            'createdAt'  => [
                'type'      => 'DATETIME',
                'null'      => true,
            ],
            'updatedAt'  => [
                'type'      => 'DATETIME',
                'null'      => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('templateFiles', true);
    }

    public function down()
    {
        $this->forge->dropTable('templateFiles', true);
    }
}
