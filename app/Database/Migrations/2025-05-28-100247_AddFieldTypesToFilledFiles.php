<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldTypesToFilledFiles extends Migration
{
    public function up()
    {
        if (!$this->columnExists('filledFiles', 'fieldTypes')) {
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
        if ($this->columnExists('filledFiles', 'fieldTypes')) {
            $this->forge->dropColumn('filledFiles', 'fieldTypes');
        }
    }

    private function columnExists($table, $column)
    {
        $query = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $query->getNumRows() > 0;
    }
}
