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
        'updatedAt'
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
            'min_length' => 'File name cannot be empty',
            'max_length' => 'File name cannot exceed 255 characters'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert = ['prepareFilledData', 'prepareFieldTypes'];
    protected $afterInsert = [];
    protected $beforeUpdate = ['prepareFilledData', 'prepareFieldTypes'];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['parseFilledData', 'parseFieldTypes'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    protected function prepareFilledData(array $data)
    {
        // Handle both direct data and nested data structures
        if (isset($data['data']['filledData']) && is_array($data['data']['filledData'])) {
            $data['data']['filledData'] = json_encode($data['data']['filledData']);
        } elseif (isset($data['filledData']) && is_array($data['filledData'])) {
            $data['filledData'] = json_encode($data['filledData']);
        }

        return $data;
    }

    protected function parseFilledData(array $data)
    {
        if (isset($data['data']) && !is_array($data['data'])) {
            return $data;
        }
        
        if (isset($data['data'])) {
            if (is_array($data['data']) && isset($data['data'][0])) {
                foreach ($data['data'] as &$record) {
                    $record = $this->parseFilledDataForRecord($record);
                }
            } else {
                $data['data'] = $this->parseFilledDataForRecord($data['data']);
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
        
        $record['id'] = $record['id'] ?? null;
        $record['name'] = $record['name'] ?? 'Unnamed File';
        $record['templateFileId'] = $record['templateFileId'] ?? null;
        $record['createdAt'] = $record['createdAt'] ?? null;
        $record['updatedAt'] = $record['updatedAt'] ?? null;
        
        if (isset($record['filledData']) && is_string($record['filledData'])) {
            $decoded = json_decode($record['filledData'], true);
            $record['filledData'] = is_array($decoded) ? $decoded : [];
        } elseif (!isset($record['filledData'])) {
            $record['filledData'] = [];
        }
        return $record;
    }

    protected function prepareFieldTypes(array $data)
    {
        // Handle both direct data and nested data structures
        if (isset($data['data']['fieldTypes']) && is_array($data['data']['fieldTypes'])) {
            $data['data']['fieldTypes'] = json_encode($data['data']['fieldTypes']);
        } elseif (isset($data['fieldTypes']) && is_array($data['fieldTypes'])) {
            $data['fieldTypes'] = json_encode($data['fieldTypes']);
        }

        return $data;
    }

    protected function parseFieldTypes(array $data)
    {
        if (isset($data['data']) && !is_array($data['data'])) {
            return $data;
        }
        
        if (isset($data['data'])) {
            if (is_array($data['data']) && isset($data['data'][0])) {
                foreach ($data['data'] as &$record) {
                    $record = $this->parseFieldTypesForRecord($record);
                }
            } else {
                $data['data'] = $this->parseFieldTypesForRecord($data['data']);
            }
        } elseif (isset($data['fieldTypes'])) {
            $data = $this->parseFieldTypesForRecord($data);
        }

        return $data;
    }

    protected function parseFieldTypesForRecord($record)
    {
        if (!is_array($record)) {
            return $record;
        }
        
        if (isset($record['fieldTypes']) && is_string($record['fieldTypes'])) {
            $decoded = json_decode($record['fieldTypes'], true);
            $record['fieldTypes'] = is_array($decoded) ? $decoded : [];
        } elseif (!isset($record['fieldTypes'])) {
            $record['fieldTypes'] = [];
        }
        return $record;
    }

    public function createFromTemplate($templateId, $fileName)
    {
        $templateModel = new TemplateModel();
        $template = $templateModel->find($templateId);
        
        if (!$template) {
            throw new \Exception('Template not found');
        }

        $templateFields = [];
        if (!empty($template['templateFields'])) {
            $templateFields = json_decode($template['templateFields'], true) ?: [];
        }

        $initialData = [];
        $defaultFieldTypes = [];
        foreach ($templateFields as $field) {
            // Extract field name from different formats
            $fieldName = '';
            if (is_string($field)) {
                $fieldName = $field;
            } elseif (is_array($field)) {
                $fieldName = $field['name'] ?? $field['field'] ?? $field;
            }
            
            if (!empty($fieldName)) {
                $initialData[$fieldName] = '';
                $defaultFieldTypes[$fieldName] = 'text'; // Default all fields to text type
            }
        }

        $filledFileData = [
            'templateFileId' => $templateId,
            'name' => $fileName,
            'filledData' => json_encode($initialData),
            'fieldTypes' => json_encode($defaultFieldTypes)
        ];

        $filledFileId = $this->insert($filledFileData);
        
        if (!$filledFileId) {
            throw new \Exception('Failed to create filled file');
        }

        return $this->find($filledFileId);
    }

    public function updateFilledData($filledFileId, array $filledData, array $fieldTypes = [])
    {
        $updateData = [
            'filledData' => json_encode($filledData),
            'updatedAt' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($fieldTypes)) {
            $updateData['fieldTypes'] = json_encode($fieldTypes);
        }
        
        return $this->update($filledFileId, $updateData);
    }

    public function getByTemplateId($templateId)
    {
        return $this->where('templateFileId', $templateId)->findAll();
    }

    public function getWithTemplate($filledFileId)
    {
        $filledFile = $this->find($filledFileId);
        if ($filledFile) {
            $templateModel = new TemplateModel();
            $filledFile['template'] = $templateModel->find($filledFile['templateFileId']);
        }
        return $filledFile;
    }

    public function isNameExistsForTemplate($templateId, $name, $excludeId = null)
    {
        $builder = $this->where('templateFileId', $templateId)
                        ->where('name', $name);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() > 0;
    }
}