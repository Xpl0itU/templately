<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAclConstraints extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        if ($db->DBDriver === 'SQLite3') {
            // SQLite automatically creates indexes for unique constraints and
            // does not support the MySQL-specific SHOW INDEX statements used below.
            return;
        }
        // Check if the unique index for setting_key exists
        $indexes = $this->db->query("SHOW INDEX FROM acl_settings WHERE Key_name = 'unique_setting_key'")->getResultArray();
        if (empty($indexes)) {
            try {
                $this->db->query('ALTER TABLE acl_settings ADD UNIQUE KEY unique_setting_key (setting_key)');
            } catch (\Exception $e) {
                // If the unique key already exists, ignore the error
                if (strpos($e->getMessage(), 'Duplicate entry') === false && strpos($e->getMessage(), 'Duplicate key name') === false) {
                    throw $e;
                }
            }
        }
        
        // Check if the unique index for resource_type, resource_id exists
        $indexes = $this->db->query("SHOW INDEX FROM resource_owners WHERE Key_name = 'unique_resource'")->getResultArray();
        if (empty($indexes)) {
            try {
                $this->db->query('ALTER TABLE resource_owners ADD UNIQUE KEY unique_resource (resource_type, resource_id)');
            } catch (\Exception $e) {
                // If the unique key already exists, ignore the error
                if (strpos($e->getMessage(), 'Duplicate entry') === false && strpos($e->getMessage(), 'Duplicate key name') === false) {
                    throw $e;
                }
            }
        }
        
        // Check if the indexes for acl_entries exist
        $indexes = $this->db->query("SHOW INDEX FROM acl_entries WHERE Key_name = 'idx_resource_type_id'")->getResultArray();
        if (empty($indexes)) {
            try {
                $this->db->query('ALTER TABLE acl_entries ADD INDEX idx_resource_type_id (resource_type, resource_id)');
            } catch (\Exception $e) {
                // If the index already exists, ignore the error
                if (strpos($e->getMessage(), 'Duplicate entry') === false && strpos($e->getMessage(), 'Duplicate key name') === false) {
                    throw $e;
                }
            }
        }
        
        $indexes = $this->db->query("SHOW INDEX FROM acl_entries WHERE Key_name = 'idx_principal'")->getResultArray();
        if (empty($indexes)) {
            try {
                $this->db->query('ALTER TABLE acl_entries ADD INDEX idx_principal (principal_type, principal_id)');
            } catch (\Exception $e) {
                // If the index already exists, ignore the error
                if (strpos($e->getMessage(), 'Duplicate entry') === false && strpos($e->getMessage(), 'Duplicate key name') === false) {
                    throw $e;
                }
            }
        }
        
        $indexes = $this->db->query("SHOW INDEX FROM acl_entries WHERE Key_name = 'idx_permission_id'")->getResultArray();
        if (empty($indexes)) {
            try {
                $this->db->query('ALTER TABLE acl_entries ADD INDEX idx_permission_id (permission_id)');
            } catch (\Exception $e) {
                // If the index already exists, ignore the error
                if (strpos($e->getMessage(), 'Duplicate entry') === false && strpos($e->getMessage(), 'Duplicate key name') === false) {
                    throw $e;
                }
            }
        }
        
        // Check if the index for resource_owners exists
        $indexes = $this->db->query("SHOW INDEX FROM resource_owners WHERE Key_name = 'idx_owner_id'")->getResultArray();
        if (empty($indexes)) {
            try {
                $this->db->query('ALTER TABLE resource_owners ADD INDEX idx_owner_id (owner_id)');
            } catch (\Exception $e) {
                // If the index already exists, ignore the error
                if (strpos($e->getMessage(), 'Duplicate entry') === false && strpos($e->getMessage(), 'Duplicate key name') === false) {
                    throw $e;
                }
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        if ($db->DBDriver === 'SQLite3') {
            return;
        }

        $this->db->query('ALTER TABLE acl_settings DROP INDEX unique_setting_key');
        $this->db->query('ALTER TABLE resource_owners DROP INDEX unique_resource');
        $this->db->query('ALTER TABLE acl_entries DROP INDEX idx_resource_type_id');
        $this->db->query('ALTER TABLE acl_entries DROP INDEX idx_principal');
        $this->db->query('ALTER TABLE acl_entries DROP INDEX idx_permission_id');
        $this->db->query('ALTER TABLE resource_owners DROP INDEX idx_owner_id');
    }
}