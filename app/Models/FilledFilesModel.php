<?php

namespace App\Models;

use CodeIgniter\Model;

class FilledFilesModel extends Model
{
    protected $table = 'filledFiles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'templateFileId',
        'name',
        'filledData',
        'fieldTypes',
        'createdAt',
        'updatedAt',
        'imageSizes'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'createdAt';
    protected $updatedField = 'updatedAt';

    protected $validationRules = [
        'templateFileId' => 'required|is_natural_no_zero',
        'name' => 'required|min_length[1]|max_length[255]',
        'filledData' => 'permit_empty'
    ];
    
    protected $validationMessages = [
        'templateFileId' => [
            'required' => 'Template ID is required',
            'is_natural_no_zero' => 'Template ID must be a valid number'
        ],
        'name' => [
            'required' => 'File name is required',
            'min_length' => 'File name must be at least 1 character long',
            'max_length' => 'File name cannot exceed 255 characters'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = ['assignOwnershipAndInheritPermissions'];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['parseFilledData'];
    protected $beforeDelete = [];
    protected $afterDelete = ['removeOwnership'];

    protected function parseFilledData(array $data)
    {
        if (isset($data['data'])) {
            $recordData = $data['data'];
            
            if (is_array($recordData)) {
                if (isset($recordData['id'])) {
                    $data['data'] = $this->parseFilledDataForRecord($recordData);
                } else {
                    foreach ($recordData as &$record) {
                        if (is_array($record)) {
                            $record = $this->parseFilledDataForRecord($record);
                        }
                    }
                    $data['data'] = $recordData;
                }
            }
        } elseif (isset($data['filledData'])) {
            $data = $this->parseFilledDataForRecord($data);
        }

        return $data;
    }

    protected function parseFilledDataForRecord($record)
    {
        if (!is_array($record)) {
            return $record;
        }
        
        // Parse filledData with caching
        if (isset($record['filledData']) && is_string($record['filledData'])) {
            $cacheKey = 'filled_data_' . md5($record['filledData']);
            $cached = cache($cacheKey);
            
            if ($cached !== null) {
                $record['filledData'] = $cached;
            } else {
                $decoded = json_decode($record['filledData'], true);
                $parsed = is_array($decoded) ? $decoded : [];
                $record['filledData'] = $parsed;
                
                // Cache for 1 hour
                cache()->save($cacheKey, $parsed, 3600);
            }
        } elseif (!isset($record['filledData'])) {
            $record['filledData'] = [];
        }

        // Parse fieldTypes with caching
        if (isset($record['fieldTypes']) && is_string($record['fieldTypes'])) {
            $cacheKey = 'field_types_' . md5($record['fieldTypes']);
            $cached = cache($cacheKey);
            
            if ($cached !== null) {
                $record['fieldTypes'] = $cached;
            } else {
                $decoded = json_decode($record['fieldTypes'], true);
                $parsed = is_array($decoded) ? $decoded : [];
                $record['fieldTypes'] = $parsed;
                
                // Cache for 1 hour
                cache()->save($cacheKey, $parsed, 3600);
            }
        } elseif (!isset($record['fieldTypes'])) {
            $record['fieldTypes'] = [];
        }

        return $record;
    }
    
    /**
     * Assign ownership to the currently authenticated user and inherit permissions from template after filled file is created
     */
    protected function assignOwnershipAndInheritPermissions(array $data)
    {
        if (isset($data['id']) && $data['id']) {
            $currentUserId = null;
            
            // Get the current user ID from the session
            $auth = service('auth');
            if ($auth && $auth->user()) {
                $currentUserId = $auth->user()->id;
            }
            
            if ($currentUserId) {
                // Create resource ownership
                $resourceOwnerModel = model('App\Models\ResourceOwnerModel');
                $resourceOwnerModel->setOwner('filled_file', $data['id'], $currentUserId);
                
                // Set default permissions for the owner
                $aclEntryModel = model('App\Models\AclEntryModel');
                $aclEntryModel->grantPermission(
                    'filled_file',
                    $data['id'],
                    'user',
                    $currentUserId,
                    'full_control',
                    $currentUserId, // Granted by owner
                    false // Not inherited
                );
                
                // Check if permission inheritance is enabled
                $aclSettingModel = model('App\Models\AclSettingModel');
                if ($aclSettingModel->getSetting('inheritance_enabled', true)) {
                    // Get the template ID to inherit permissions from
                    $templateId = $data['data']['templateFileId'] ?? $data['templateFileId'] ?? null;
                    
                    if ($templateId) {
                        // Inherit permissions from the template
                        $templateAclEntries = $aclEntryModel->getResourceAclEntries('template', $templateId);
                        
                        foreach ($templateAclEntries as $entry) {
                            $aclEntryModel->grantPermission(
                                'filled_file',
                                $data['id'],
                                $entry['principal_type'],
                                $entry['principal_id'],
                                $entry['permission']['name'], // Need to get the permission name
                                $entry['granted_by'],
                                true, // Inherited
                                'template', // Inheritance source
                                $templateId // Inheritance source ID
                            );
                        }
                        
                        // Also apply the template's owner as an owner of the filled file
                        $resourceOwnerModel = model('App\Models\ResourceOwnerModel');
                        $templateOwner = $resourceOwnerModel->getOwner('template', $templateId);
                        
                        if ($templateOwner && $templateOwner !== $currentUserId) {
                            // Grant read/execute permission to template owner on the filled file
                            $aclEntryModel->grantPermission(
                                'filled_file',
                                $data['id'],
                                'user',
                                $templateOwner,
                                'read_execute', // Template owner gets read permission
                                $templateOwner,
                                true, // Inherited
                                'template', // Inheritance source
                                $templateId // Inheritance source ID
                            );
                        }
                    }
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Remove ownership when filled file is deleted
     */
    protected function removeOwnership(array $data)
    {
        if (isset($data['id']) && $data['id']) {
            $resourceOwnerModel = model('App\Models\ResourceOwnerModel');
            $resourceOwnerModel->where('resource_type', 'filled_file')
                              ->where('resource_id', $data['id'])
                              ->delete();
        }
        
        return $data;
    }
}