<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTemplateFieldsToTemplateFilesMigration extends Migration
{
    public function up()
    {
        $this->forge->addColumn('templateFiles', [
            'templateFields' => [
                'type' => 'TEXT',
                'null' => true,
            ]
        ]);
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->DBDriver === 'SQLite3') {
            return;
        }

        $this->forge->dropColumn('templateFiles', 'templateFields');
    }
}
