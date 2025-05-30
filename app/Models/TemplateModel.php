<?php

namespace App\Models;

use CodeIgniter\Model;

class TemplateModel extends Model
{
    protected $table = 'templateFiles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'name',
        'originalFileName',
        'path',
        'templateFields',
        'createdAt',
        'updatedAt'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'createdAt';
    protected $updatedField = 'updatedAt';

    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[255]',
        'originalFileName' => 'permit_empty|max_length[255]',
        'path' => 'permit_empty|max_length[500]',
        'templateFields' => 'permit_empty'
    ];
    
    protected $validationMessages = [
        'name' => [
            'required' => 'Template name is required',
            'min_length' => 'Template name must be at least 3 characters long',
            'max_length' => 'Template name cannot exceed 255 characters'
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
    protected $afterFind = ['parseTemplateFields'];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    protected function parseTemplateFields(array $data)
    {
        if (isset($data['data'])) {
            $recordData = $data['data'];
            
            if (is_array($recordData)) {
                if (isset($recordData['id'])) {
                    $data['data'] = $this->parseTemplateFieldsForRecord($recordData);
                } else { // Multiple records
                    foreach ($recordData as &$record) {
                        if (is_array($record)) {
                            $record = $this->parseTemplateFieldsForRecord($record);
                        }
                    }
                    $data['data'] = $recordData;
                }
            }
        } elseif (isset($data['templateFields'])) {
            $data = $this->parseTemplateFieldsForRecord($data);
        }

        return $data;
    }

    protected function parseTemplateFieldsForRecord($record)
    {
        if (!is_array($record)) {
            return $record;
        }
        
        if (isset($record['templateFields']) && is_string($record['templateFields'])) {
            $decoded = json_decode($record['templateFields'], true);
            $record['templateFields'] = is_array($decoded) ? $decoded : [];
        } elseif (!isset($record['templateFields'])) {
            $record['templateFields'] = [];
        }
        return $record;
    }

    public function getTemplatesWithFilledFiles()
    {
        $templates = $this->findAll();
        $filledFileModel = new FilledFilesModel();
        
        foreach ($templates as &$template) {
            $template['filledFiles'] = $filledFileModel->where('templateFileId', $template['id'])->findAll();
        }
        
        return $templates;
    }

    public function getTemplateWithFilledFiles($templateId)
    {
        $template = $this->find($templateId);
        if ($template) {
            $filledFileModel = new FilledFilesModel();
            $template['filledFiles'] = $filledFileModel->where('templateFileId', $templateId)->findAll();
        }
        return $template;
    }

    public function deleteTemplateWithFiles($templateId)
    {
        $this->db->transStart();
        
        try {
            $template = $this->find($templateId);
            if (!$template) {
                throw new \Exception('Template not found');
            }
            
            $filledFileModel = new FilledFilesModel();
            $filledFileModel->where('templateFileId', $templateId)->delete();
            
            if (!empty($template['path']) && file_exists($template['path'])) {
                unlink($template['path']);
            }
            
            $this->delete($templateId);
            
            $this->db->transComplete();
            
            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }
            
            return true;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}
