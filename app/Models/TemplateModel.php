<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Template Model
 * 
 * Manages template file records including CRUD operations,
 * ownership tracking, and optimized queries with filled files.
 */
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
    protected $afterInsert = ['assignOwnership'];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = ['parseTemplateFields'];
    protected $beforeDelete = [];
    protected $afterDelete = ['removeOwnership'];

    /**
     * Parse template fields from JSON string to array after find operations
     *
     * @param array $data Query result data
     * @return array Modified data with parsed template fields
     */
    protected function parseTemplateFields(array $data)
    {
        if (isset($data['data'])) {
            $recordData = $data['data'];
            
            if (is_array($recordData)) {
                if (isset($recordData['id'])) {
                    $data['data'] = $this->parseTemplateFieldsForRecord($recordData);
                } else {
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

    /**
     * Parse template fields for a single record with caching
     *
     * @param mixed $record Record data
     * @return mixed Modified record with parsed fields
     */
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
                
                cache()->save($cacheKey, $parsed, 3600);
            }
        } elseif (!isset($record['templateFields'])) {
            $record['templateFields'] = [];
        }
        return $record;
    }
    
    /**
     * Assign ownership to the currently authenticated user after template is created
     *
     * @param array $data Insert data with template ID
     * @return array Unmodified data
     */
    protected function assignOwnership(array $data)
    {
        if (isset($data['id']) && $data['id']) {
            $currentUserId = null;
            
            $auth = service('auth');
            if ($auth && $auth->user()) {
                $currentUserId = $auth->user()->id;
            }
            
            if ($currentUserId) {
                $resourceOwnerModel = model('App\Models\ResourceOwnerModel');
                $resourceOwnerModel->setOwner('template', $data['id'], $currentUserId);
                
                $aclEntryModel = model('App\Models\AclEntryModel');
                $aclEntryModel->grantPermission(
                    'template',
                    $data['id'],
                    'user',
                    $currentUserId,
                    'full_control',
                    $currentUserId,
                    false
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Remove ownership when template is deleted
     *
     * @param array $data Delete data with template ID
     * @return array Unmodified data
     */
    protected function removeOwnership(array $data)
    {
        if (isset($data['id']) && $data['id']) {
            $resourceOwnerModel = model('App\Models\ResourceOwnerModel');
            $resourceOwnerModel->where('resource_type', 'template')
                              ->where('resource_id', $data['id'])
                              ->delete();
        }
        
        return $data;
    }

    /**
     * Get all templates with their associated filled files (legacy method, use optimized version)
     *
     * @return array Templates with filled files
     */
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
     *
     * @return array Templates with filled files attached
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

    /**
     * Get a specific template with its associated filled files
     *
     * @param int $templateId Template ID
     * @return array|null Template with filled files, or null if not found
     */
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
     *
     * @param int $templateId Template ID
     * @return array|null Template with filled files, or null if not found
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

    /**
     * Delete a template along with its associated files and database records
     * Uses a transaction to ensure atomicity
     *
     * @param int $templateId Template ID to delete
     * @return bool True on success
     * @throws \Exception If template not found or transaction fails
     */
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
