<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * FilledFiles Model
 * Manages filled file records with template field data, ownership tracking, and permission inheritance
 * Handles JSON parsing and caching for filledData and fieldTypes fields
 */
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

    /**
     * Parse JSON filled data after fetching from database
     * Handles both single records and arrays of records
     *
     * @param array $data Query result data
     * @return array Parsed data with decoded JSON fields
     */
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

    /**
     * Parse filled data and field types for a single record with caching
     * Decodes JSON strings and caches parsed results for 1 hour
     *
     * @param mixed $record Record data to parse
     * @return mixed Parsed record with decoded JSON fields
     */
    protected function parseFilledDataForRecord($record)
    {
        if (!is_array($record)) {
            return $record;
        }

        if (isset($record['filledData']) && is_string($record['filledData'])) {
            $cacheKey = 'filled_data_' . md5($record['filledData']);
            $cached = cache($cacheKey);

            if ($cached !== null) {
                $record['filledData'] = $cached;
            } else {
                $decoded = json_decode($record['filledData'], true);
                $parsed = is_array($decoded) ? $decoded : [];
                $record['filledData'] = $parsed;

                cache()->save($cacheKey, $parsed, 3600);
            }
        } elseif (!isset($record['filledData'])) {
            $record['filledData'] = [];
        }

        if (isset($record['fieldTypes']) && is_string($record['fieldTypes'])) {
            $cacheKey = 'field_types_' . md5($record['fieldTypes']);
            $cached = cache($cacheKey);

            if ($cached !== null) {
                $record['fieldTypes'] = $cached;
            } else {
                $decoded = json_decode($record['fieldTypes'], true);
                $parsed = is_array($decoded) ? $decoded : [];
                $record['fieldTypes'] = $parsed;

                cache()->save($cacheKey, $parsed, 3600);
            }
        } elseif (!isset($record['fieldTypes'])) {
            $record['fieldTypes'] = [];
        }

        return $record;
    }

    /**
     * Assign ownership and inherit permissions from template after filled file creation
     * Sets owner, grants full control to creator, and inherits permissions from parent template
     *
     * @param array $data Insert data with filled file ID
     * @return array Unmodified data
     */
    protected function assignOwnershipAndInheritPermissions(array $data)
    {
        if (isset($data['id']) && $data['id']) {
            $currentUserId = null;

            $auth = service('auth');
            if ($auth && $auth->user()) {
                $currentUserId = $auth->user()->id;
            }

            if ($currentUserId) {
                $resourceOwnerModel = model('App\Models\ResourceOwnerModel');
                $resourceOwnerModel->setOwner('filled_file', $data['id'], $currentUserId);

                $aclEntryModel = model('App\Models\AclEntryModel');
                $aclEntryModel->grantPermission(
                    'filled_file',
                    $data['id'],
                    'user',
                    $currentUserId,
                    'full_control',
                    $currentUserId,
                    false
                );

                $aclSettingModel = model('App\Models\AclSettingsModel');
                if ($aclSettingModel && $aclSettingModel->getSetting('inheritance_enabled', true)) {
                    $templateId = $data['data']['templateFileId'] ?? $data['templateFileId'] ?? null;

                    if ($templateId) {
                        // Inherit permissions from parent template
                        $templateAclEntries = $aclEntryModel->getResourceAclEntries('template', $templateId);

                        foreach ($templateAclEntries as $entry) {
                            $aclEntryModel->grantPermission(
                                'filled_file',
                                $data['id'],
                                $entry['principal_type'],
                                $entry['principal_id'],
                                $entry['permission_name'],
                                $entry['granted_by'],
                                true,
                                'template',
                                $templateId
                            );
                        }

                        $resourceOwnerModel = model('App\Models\ResourceOwnerModel');
                        $templateOwner = $resourceOwnerModel->getOwner('template', $templateId);

                        if ($templateOwner && $templateOwner !== $currentUserId) {
                            $aclEntryModel->grantPermission(
                                'filled_file',
                                $data['id'],
                                'user',
                                $templateOwner,
                                'read_execute',
                                $templateOwner,
                                true,
                                'template',
                                $templateId
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
     *
     * @param array $data Delete data with filled file ID
     * @return array Unmodified data
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
