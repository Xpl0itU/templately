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
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['parseFilledData'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

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
}