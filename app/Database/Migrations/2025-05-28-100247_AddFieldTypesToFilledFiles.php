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
        $dbDriver = $this->db->getPlatform();
        
        if (stripos($dbDriver, 'mysql') !== false) {
            // MySQL approach
            $query = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
            return $query->getNumRows() > 0;
        } else {
            // Generic approach for other databases (including SQLite)
            try {
                $query = $this->db->query("SELECT * FROM pragma_table_info('{$table}') WHERE name = '{$column}'");
                return $query->getNumRows() > 0;
            } catch (\Exception $e) {
                // Fallback approach
                try {
                    $this->db->query("SELECT {$column} FROM {$table} LIMIT 1");
                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            }
        }
    }
}
