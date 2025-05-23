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
        $this->forge->dropColumn('templateFiles', 'templateFields');
    }
}
