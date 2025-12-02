<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldTypesToFilledFiles extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('fieldTypes', 'filledFiles')) {
            $this->forge->addColumn('filledFiles', [
                'fieldTypes' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'filledData'
                ]
            ]);
        }
    }

    public function down()
    {
        if ($this->db->DBDriver === 'SQLite3') {
            return;
        }

        if ($this->db->fieldExists('fieldTypes', 'filledFiles')) {
            $this->forge->dropColumn('filledFiles', 'fieldTypes');
        }
    }
}
