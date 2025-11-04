<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldTypesToFilledFiles extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if (!$db->fieldExists('fieldTypes', 'filledFiles')) {
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
        $db = \Config\Database::connect();

        if ($db->fieldExists('fieldTypes', 'filledFiles')) {
            $this->forge->dropColumn('filledFiles', 'fieldTypes');
        }
    }
}
