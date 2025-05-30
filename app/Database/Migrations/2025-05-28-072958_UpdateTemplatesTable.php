<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTemplatesTable extends Migration
{
    public function up()
    {
        if (!$this->columnExists('templateFiles', 'originalFileName')) {
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
        if ($this->columnExists('templateFiles', 'originalFileName')) {
            $this->forge->dropColumn('templateFiles', 'originalFileName');
        }
    }

    private function columnExists($table, $column)
    {
        $query = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return $query->getNumRows() > 0;
    }
}