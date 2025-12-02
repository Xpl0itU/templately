<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTemplatesTable extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('originalFileName', 'templateFiles')) {
            $this->forge->addColumn('templateFiles', [
                'originalFileName' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'name'
                ]
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('originalFileName', 'templateFiles')) {
            $this->forge->dropColumn('templateFiles', 'originalFileName');
        }
    }
}
