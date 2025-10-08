<?php

namespace App\Models;

use CodeIgniter\Model;

class AclSettingModel extends Model
{
    protected $table = 'acl_settings';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'setting_key',
        'setting_value',
        'description',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
    
    /**
     * Get a setting value
     * 
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed Setting value or default
     */
    public function getSetting(string $key, $default = null)
    {
        try {
            $query = $this->where('setting_key', $key)->get();
            $result = $query->getRow();
            
            if ($result) {
                // Try to decode as JSON first, then as boolean, then return as string
                $value = $result->setting_value;
                
                // Check if it's a boolean value
                if ($value === '1' || $value === 'true') {
                    return true;
                }
                if ($value === '0' || $value === 'false') {
                    return false;
                }
                
                // Try to decode as JSON
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
                
                return $value;
            }
            
            return $default;
        } catch (\Exception $e) {
            log_message('error', 'Error getting ACL setting: ' . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Set a setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool True on success, false on failure
     */
    public function setSetting(string $key, $value): bool
    {
        try {
            // Convert value to string for storage
            if (is_bool($value)) {
                $valueStr = $value ? '1' : '0';
            } elseif (is_array($value) || is_object($value)) {
                $valueStr = json_encode($value);
            } else {
                $valueStr = (string) $value;
            }
            
            // Check if setting already exists
            $existing = $this->where('setting_key', $key)->first();
            
            if ($existing) {
                // Update existing setting
                $data = [
                    'setting_value' => $valueStr,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                
                return $this->update($existing['id'], $data);
            }
            
            // Insert new setting
            $data = [
                'setting_key' => $key,
                'setting_value' => $valueStr,
                'description' => '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            
            return $this->insert($data) !== false;
        } catch (\Exception $e) {
            log_message('error', 'Error setting ACL setting: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all settings
     * 
     * @return array Array of all settings
     */
    public function getAllSettings(): array
    {
        try {
            $query = $this->get();
            $results = $query->getResultArray();
            
            $settings = [];
            foreach ($results as $result) {
                $settings[$result['setting_key']] = $this->getSetting($result['setting_key']);
            }
            
            return $settings;
        } catch (\Exception $e) {
            log_message('error', 'Error getting all ACL settings: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get settings with descriptions
     * 
     * @return array Array of settings with descriptions
     */
    public function getSettingsWithDescriptions(): array
    {
        try {
            $query = $this->get();
            $results = $query->getResultArray();
            
            $settings = [];
            foreach ($results as $result) {
                $settings[$result['setting_key']] = [
                    'value' => $this->getSetting($result['setting_key']),
                    'description' => $result['description'] ?? '',
                ];
            }
            
            return $settings;
        } catch (\Exception $e) {
            log_message('error', 'Error getting ACL settings with descriptions: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Reset a setting to its default value
     * 
     * @param string $key Setting key
     * @return bool True on success, false on failure
     */
    public function resetSetting(string $key): bool
    {
        try {
            // Only allow superadmin to reset settings
            $currentUser = auth()->user();
            if (!$currentUser->inGroup('superadmin')) {
                return false;
            }
            
            // Define default values
            $defaults = [
                'inheritance_enabled' => true,
                'inheritance_cascading' => true,
                'owner_can_manage_permissions' => true,
                'default_template_permissions' => 'read',
                'default_filled_file_permissions' => 'read_execute',
                'auto_inherit_on_creation' => true,
                'permission_override_allowed' => true,
                'audit_log_enabled' => true,
            ];
            
            if (isset($defaults[$key])) {
                return $this->setSetting($key, $defaults[$key]);
            }
            
            // If no default, delete the setting
            $existing = $this->where('setting_key', $key)->first();
            
            if ($existing) {
                return $this->delete($existing['id']);
            }
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error resetting ACL setting: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reset all settings to their default values
     * 
     * @return bool True on success, false on failure
     */
    public function resetAllSettings(): bool
    {
        try {
            // Only allow superadmin to reset all settings
            $currentUser = auth()->user();
            if (!$currentUser->inGroup('superadmin')) {
                return false;
            }
            
            // Define default values
            $defaults = [
                'inheritance_enabled' => true,
                'inheritance_cascading' => true,
                'owner_can_manage_permissions' => true,
                'default_template_permissions' => 'read',
                'default_filled_file_permissions' => 'read_execute',
                'auto_inherit_on_creation' => true,
                'permission_override_allowed' => true,
                'audit_log_enabled' => true,
            ];
            
            // Update each setting to its default value
            foreach ($defaults as $key => $value) {
                $this->setSetting($key, $value);
            }
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Error resetting all ACL settings: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate a setting value
     * 
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool True if valid, false if invalid
     */
    public function validateSetting(string $key, $value): bool
    {
        // Define validation rules for each setting
        switch ($key) {
            case 'inheritance_enabled':
            case 'inheritance_cascading':
            case 'owner_can_manage_permissions':
            case 'auto_inherit_on_creation':
            case 'permission_override_allowed':
            case 'audit_log_enabled':
                // Boolean values
                return is_bool($value) || $value === '1' || $value === '0' || $value === 'true' || $value === 'false';
                
            case 'default_template_permissions':
            case 'default_filled_file_permissions':
                // String values representing permission levels
                $validPermissions = ['full_control', 'modify', 'read_execute', 'read', 'write'];
                return in_array($value, $validPermissions);
                
            default:
                // Unknown setting, assume valid
                return true;
        }
    }
}