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
        'size',
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
            // Check if we have a cached version
            $cacheKey = 'template_fields_' . md5($record['templateFields']);
            $cached = cache($cacheKey);
            
            if ($cached !== null) {
                $record['templateFields'] = $cached;
            } else {
                $decoded = json_decode($record['templateFields'], true);
                $parsed = is_array($decoded) ? $decoded : [];
                $record['templateFields'] = $parsed;
                
                // Cache for 1 hour
                cache()->save($cacheKey, $parsed, 3600);
            }
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

    /**
     * Optimized version that uses JOIN to fetch templates with filled files in a single query
     * This eliminates the N+1 query problem
     */
    public function getTemplatesWithFilledFilesOptimized()
    {
        // First, get all templates
        $templates = $this->findAll();
        
        // Create a map of template IDs for efficient lookup
        $templateIds = array_column($templates, 'id');
        
        if (empty($templateIds)) {
            return $templates;
        }
        
        // Get all filled files for these templates in a single query
        $filledFileModel = new FilledFilesModel();
        $filledFiles = $filledFileModel->whereIn('templateFileId', $templateIds)->findAll();
        
        // Group filled files by template ID
        $filledFilesByTemplate = [];
        foreach ($filledFiles as $file) {
            $templateId = $file['templateFileId'];
            if (!isset($filledFilesByTemplate[$templateId])) {
                $filledFilesByTemplate[$templateId] = [];
            }
            $filledFilesByTemplate[$templateId][] = $file;
        }
        
        // Attach filled files to templates
        foreach ($templates as &$template) {
            $templateId = $template['id'];
            $template['filledFiles'] = $filledFilesByTemplate[$templateId] ?? [];
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

    /**
     * Optimized version that uses a single query to fetch template with filled files
     */
    public function getTemplateWithFilledFilesOptimized($templateId)
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
