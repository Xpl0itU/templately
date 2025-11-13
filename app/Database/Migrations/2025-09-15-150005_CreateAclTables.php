<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAclTables extends Migration
{
    public function up()
    {
        // Create ACL permissions table for standard permissions
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'bit_value' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'Bit value for permission',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('name');
        $this->forge->addKey('bit_value');
        $this->forge->createTable('acl_permissions', true);

        // Check if default permissions already exist before inserting
        $existingCount = $this->db->table('acl_permissions')->countAllResults();
        if ($existingCount == 0) {
            // Insert default ACL permissions only if table is empty
            $permissions = [
                ['name' => 'full_control', 'description' => 'Full control over the resource', 'bit_value' => 2032127], // 0x1F01FF
                ['name' => 'modify', 'description' => 'Modify the resource', 'bit_value' => 1245631], // 0x1301BF
                ['name' => 'read_execute', 'description' => 'Read and execute the resource', 'bit_value' => 1180095], // 0x1200BF
                ['name' => 'read', 'description' => 'Read the resource', 'bit_value' => 1179926], // 0x120096
                ['name' => 'write', 'description' => 'Write to the resource', 'bit_value' => 1180038], // 0x120086
            ];

            $this->db->table('acl_permissions')->insertBatch($permissions);
        }

        // Create ACL table for storing access control entries
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'resource_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'Type of resource (template, filled_file, etc.)',
            ],
            'resource_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'ID of the specific resource',
            ],
            'principal_type' => [
                'type' => 'ENUM',
                'constraint' => ['user', 'group'],
                'null' => false,
                'comment' => 'Type of principal (user or group)',
            ],
            'principal_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'ID of the user or group',
            ],
            'permission_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'ID of the permission level',
            ],
            'granted_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID of the user who granted the permission',
            ],
            'inherited' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
                'comment' => 'Whether this permission is inherited (1) or explicit (0)',
            ],
            'inheritance_source_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'comment' => 'Type of resource this permission was inherited from',
            ],
            'inheritance_source_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'comment' => 'ID of the resource this permission was inherited from',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Optional expiration date for temporary permissions',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['resource_type', 'resource_id']);
        $this->forge->addKey(['principal_type', 'principal_id']);
        $this->forge->addKey('permission_id');
        $this->forge->addKey('granted_by');
        $this->forge->addForeignKey('principal_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('granted_by', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('permission_id', 'acl_permissions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('acl_entries', true);

        // Create resource ownership table
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'resource_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => false,
                'comment' => 'Type of resource (template, filled_file, etc.)',
            ],
            'resource_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'ID of the specific resource',
            ],
            'owner_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
                'comment' => 'ID of the owner user',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['resource_type', 'resource_id']);
        $this->forge->addKey('owner_id');
        $this->forge->addForeignKey('owner_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('resource_owners', true);

        // Create ACL settings table for customizable behavior
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'setting_key' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
            'setting_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('setting_key');
        $this->forge->createTable('acl_settings', true);

        // Insert default settings
        $now = date('Y-m-d H:i:s');
        $settings = [
            ['setting_key' => 'inheritance_enabled', 'setting_value' => '1', 'description' => 'Enable inheritance of permissions from templates to filled files'],
            ['setting_key' => 'inheritance_cascading', 'setting_value' => '1', 'description' => 'Enable cascading inheritance (changes to template permissions affect existing filled files)'],
            ['setting_key' => 'owner_can_manage_permissions', 'setting_value' => '1', 'description' => 'Allow resource owners to manage permissions'],
            ['setting_key' => 'default_template_permissions', 'setting_value' => 'read', 'description' => 'Default permission level for new templates'],
            ['setting_key' => 'default_filled_file_permissions', 'setting_value' => 'read_execute', 'description' => 'Default permission level for new filled files'],
            ['setting_key' => 'auto_inherit_on_creation', 'setting_value' => '1', 'description' => 'Automatically inherit permissions when creating filled files from templates'],
            ['setting_key' => 'permission_override_allowed', 'setting_value' => '1', 'description' => 'Allow overriding inherited permissions with explicit permissions'],
        ];

        $settings = array_map(static function (array $setting) use ($now) {
            $setting['created_at'] = $now;
            $setting['updated_at'] = $now;

            return $setting;
        }, $settings);

        $this->db->table('acl_settings')->insertBatch($settings);
    }

    public function down()
    {
        $this->forge->dropTable('acl_settings', true);
        $this->forge->dropTable('resource_owners', true);
        $this->forge->dropTable('acl_entries', true);
        $this->forge->dropTable('acl_permissions', true);
    }
}
