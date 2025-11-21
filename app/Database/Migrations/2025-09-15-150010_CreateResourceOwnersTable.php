<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateResourceOwnersTable extends Migration
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
            'resource_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
            ],
            'resource_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'owner_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['resource_type', 'resource_id'], false, true); // Unique constraint
        
        // Only add foreign key for MySQL/PostgreSQL, not SQLite
        if ($this->db->DBDriver !== 'SQLite3') {
            $this->forge->addForeignKey('owner_id', 'users', 'id', 'CASCADE', 'CASCADE');
        }
        
        $this->forge->createTable('resource_owners');
    }

    public function down()
    {
        $this->forge->dropTable('resource_owners', true);
    }
}
