<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateAclPermissionsTable extends Migration
{
    public function up()
    {
        /** @var \CodeIgniter\Database\BaseConnection $db */
        $db = \Config\Database::connect();

        // Check if bit_value column already exists
        $fields = $db->getFieldData('acl_permissions');
        $hasBitValue = false;
        
        foreach ($fields as $field) {
            if ($field->name === 'bit_value') {
                $hasBitValue = true;
                break;
            }
        }
        
        // Add the bit_value column only if it doesn't exist
        if (!$hasBitValue) {
            $fields = [
                'bit_value' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'comment' => 'Bit value for permission',
                    'after' => 'description'
                ],
            ];
            
            $this->forge->addColumn('acl_permissions', $fields);
        }
        
        // Check if default permissions already exist
        $existingPermissions = $db->table('acl_permissions')->whereIn('name', ['full_control', 'modify', 'read_execute', 'read', 'write'])->countAllResults();
        
        // Insert default ACL permissions only if they don't exist
        if ($existingPermissions < 5) {
            // First, delete any existing entries that match these names
            $db->table('acl_permissions')->whereIn('name', ['full_control', 'modify', 'read_execute', 'read', 'write'])->delete();
            
            // Then insert the default permissions with bit values
            $permissions = [
                ['name' => 'full_control', 'description' => 'Full control over the resource', 'bit_value' => 2032127], // 0x1F01FF
                ['name' => 'modify', 'description' => 'Modify the resource', 'bit_value' => 1245631], // 0x1301BF
                ['name' => 'read_execute', 'description' => 'Read and execute the resource', 'bit_value' => 1180095], // 0x1200BF
                ['name' => 'read', 'description' => 'Read the resource', 'bit_value' => 1179926], // 0x120096
                ['name' => 'write', 'description' => 'Write to the resource', 'bit_value' => 1180038], // 0x120086
            ];
            
            $db->table('acl_permissions')->insertBatch($permissions);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->DBDriver === 'SQLite3') {
            // SQLite does not support dropping columns in-place; nothing to do.
            return;
        }

        if ($db->fieldExists('bit_value', 'acl_permissions')) {
            $this->forge->dropColumn('acl_permissions', 'bit_value');
        }
    }
}