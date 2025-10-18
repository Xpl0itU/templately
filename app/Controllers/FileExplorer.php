<?php

namespace App\Controllers;

use PhpOffice\PhpWord\TemplateProcessor;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\PermissionManager;

class FileExplorer extends BaseController
{
    protected $templateModel;
    protected $filledFileModel;
    protected $permissionManager;

    public function __construct()
    {
        $this->templateModel = model('App\Models\TemplateModel');
        $this->filledFileModel = model('App\Models\FilledFilesModel');
        $this->permissionManager = service('permissions');
    }

    public function index()
    {
        if (!$this->permissionManager->can(auth()->user(), 'templates.view')) {
            return $this->redirectWithError('/', 'You do not have permission to view templates.');
        }

        $templates = $this->getTemplatesWithFilledFiles();
        
        return view(
            'file_explorer', [
            'title' => 'File Explorer',
            'templates' => $templates,
            'userPermissions' => $this->getUserPermissions()
            ]
        );
    }

    private function getTemplatesWithFilledFiles(): array
    {
        // Use the optimized method from TemplateModel that eliminates N+1 queries
        $templates = $this->templateModel->getTemplatesWithFilledFilesOptimized();
        
        // Process template fields for each template
        foreach ($templates as &$template) {
            $template['templateFields'] = $this->parseJsonField($template['templateFields']);
        }

        return $templates;
    }

    private function normalizeFilledFile(array $file): array
    {
        $file = array_merge(
            [
            'id' => null,
            'name' => 'Unnamed File',
            'createdAt' => null,
            'updatedAt' => null,
            'filledData' => []
            ], $file
        );

        $file['filledData'] = $this->parseJsonField($file['filledData']);

        return $file;
    }

    private function updateTemplate(int $templateId, array $data): array
    {
        // Validate that the user has permission to edit this template
        if (!$this->permissionManager->can(auth()->user(), 'templates.edit', 'template', $templateId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to edit this template.'
            ];
        }

        // Check if template exists
        $existingTemplate = $this->templateModel->find($templateId);
        if (!$existingTemplate) {
            return [
                'success' => false,
                'message' => 'Template not found.'
            ];
        }

        // Update the template
        $updateData = [
            'name' => $data['name'] ?? $existingTemplate['name'],
            'description' => $data['description'] ?? $existingTemplate['description'],
            'templateFields' => $data['templateFields'] ?? $existingTemplate['templateFields'],
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        $result = $this->templateModel->update($templateId, $updateData);

        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Failed to update template. Please try again.',
                'errors' => $this->templateModel->errors()
            ];
        }

        return [
            'success' => true,
            'message' => 'Template updated successfully.',
            'data' => [
                'templateId' => $templateId,
                'name' => $updateData['name'],
                'description' => $updateData['description'],
                'templateFields' => $updateData['templateFields'],
                'updatedAt' => $updateData['updatedAt']
            ]
        ];
    }

    public function createFilledFile()
    {
        // Only accept JSON requests
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        
        $result = $this->processCreateFilledFile($data);
        return $this->response->setJSON($result);
    }

    private function processCreateFilledFile(array $data): array
    {
        // Validate template ID
        $templateId = (int) ($data['templateId'] ?? $data['template_id'] ?? 0);
        if ($templateId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid template ID provided.'
            ];
        }

        // Validate that the user has permission to create filled files from this template
        if (!$this->permissionManager->can(auth()->user(), 'filled-files.create', 'template', $templateId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to create filled files from this template.'
            ];
        }

        // Check if template exists
        $template = $this->templateModel->find($templateId);
        if (!$template) {
            return [
                'success' => false,
                'message' => 'Template not found.'
            ];
        }

        // Validate filled data - allow empty object for templates with no fields
        $filledData = $data['filledData'] ?? $data['filled_data'] ?? [];
        
        // filledData can be empty for templates with no fields or new files
        // Convert to array if it's not already
        if (is_string($filledData)) {
            $filledData = json_decode($filledData, true) ?? [];
        }
        if (!is_array($filledData)) {
            $filledData = [];
        }

        // Validate filled file name
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            return [
                'success' => false,
                'message' => 'File name is required.'
            ];
        }

        // First create the filled file without images to get an ID
        $insertData = [
            'name' => $name,
            'templateFileId' => $templateId,
            'filledData' => json_encode($filledData),
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        // Insert the filled file
        $filledFileId = $this->filledFileModel->insert($insertData);

        if ($filledFileId === false) {
            return [
                'success' => false,
                'message' => 'Failed to create filled file. Please try again.',
                'errors' => $this->filledFileModel->errors()
            ];
        }

        // Now process uploaded images with the new filled file ID
        $processedImages = [];
        if (isset($data['images']) && is_array($data['images'])) {
            foreach ($data['images'] as $fieldKey => $imageData) {
                if (isset($imageData['hasNewFile']) && $imageData['hasNewFile'] === 'true') {
                    // Handle image upload
                    $uploadedImagePath = $this->handleImageUpload($imageData['file'] ?? null, $fieldKey, $filledFileId);
                    if ($uploadedImagePath !== false) {
                        $processedImages[$fieldKey] = $uploadedImagePath;
                    } else {
                        // Clean up - delete the filled file since image upload failed
                        $this->filledFileModel->delete($filledFileId);
                        return [
                            'success' => false,
                            'message' => 'Failed to process image for field: ' . $fieldKey
                        ];
                    }
                } elseif (isset($imageData['existingPath'])) {
                    // Use existing image
                    $processedImages[$fieldKey] = $imageData['existingPath'];
                }
            }
        }

        // If we have images, update the filled file with image paths
        if (!empty($processedImages)) {
            $filledDataWithImages = array_merge($filledData, $processedImages);
            $this->filledFileModel->update($filledFileId, [
                'filledData' => json_encode($filledDataWithImages),
                'updatedAt' => date('Y-m-d H:i:s')
            ]);
        } else {
            $filledDataWithImages = $filledData;
        }

        // Update template's updatedAt timestamp
        $this->templateModel->update($templateId, ['updatedAt' => date('Y-m-d H:i:s')]);

        return [
            'success' => true,
            'message' => 'Filled file created successfully.',
            'newFilledFile' => [
                'id' => $filledFileId,
                'name' => $name,
                'templateFileId' => $templateId,
                'filledData' => json_encode($filledDataWithImages),
                'createdAt' => $insertData['createdAt'],
                'updatedAt' => $insertData['updatedAt']
            ]
        ];
    }

    private function handleImageUpload($file, string $fieldKey, int $filledFileId): string|false
    {
        if (!$file || !$file->isValid()) {
            return false;
        }

        // Create directory for this filled file's images if it doesn't exist
        $uploadPath = WRITEPATH . 'uploads/filled_files/' . $filledFileId . '/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Generate a unique filename
        $originalName = $file->getName();
        $extension = $file->getExtension();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $fileName = $safeName . '_' . uniqid() . '.' . $extension;

        // Move the uploaded file
        if ($file->move($uploadPath, $fileName)) {
            return $fileName; // Return just the filename, not the full path
        }

        return false;
    }

    public function uploadFieldImage(int $filledFileId, string $fieldName)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        // Check permissions
        if (!$this->permissionManager->can(auth()->user(), 'filled-files.edit', 'filled_file', $filledFileId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'You do not have permission to edit this filled file.'
            ]);
        }

        // Check if filled file exists
        $filledFile = $this->filledFileModel->find($filledFileId);
        if (!$filledFile) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Filled file not found.'
            ]);
        }

        // Get the uploaded file
        $file = $this->request->getFile('image');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No valid image file provided.'
            ]);
        }

        // Upload the image
        $fileName = $this->handleImageUpload($file, $fieldName, $filledFileId);
        if ($fileName === false) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to upload image.'
            ]);
        }

        // Update the filled file data with the new image path
        $filledData = is_string($filledFile['filledData']) 
            ? json_decode($filledFile['filledData'], true) ?? [] 
            : $filledFile['filledData'];
        
        $filledData[$fieldName] = $fileName;

        $this->filledFileModel->update($filledFileId, [
            'filledData' => json_encode($filledData),
            'updatedAt' => date('Y-m-d H:i:s')
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Image uploaded successfully.',
            'fileName' => $fileName,
            'imageUrl' => site_url("file-explorer/serve-image/{$filledFileId}/" . urlencode($fileName))
        ]);
    }

    public function updateFilledFile(int $filledFileId)
    {
        // Only accept AJAX requests
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        // Get form data (could be JSON or multipart/form-data with files)
        // Try to get JSON first, but don't fail if it's not JSON
        $jsonData = null;
        try {
            $jsonData = $this->request->getJSON(true);
        } catch (\Exception $e) {
            // Not JSON, that's okay - it's probably FormData
        }
        
        $postData = $this->request->getPost();
        
        // Merge both sources, preferring JSON data
        $data = array_merge($postData ?? [], $jsonData ?? []);
        
        // Handle file uploads for images
        $files = $this->request->getFiles();
        
        $result = $this->processUpdateFilledFile($filledFileId, $data, $files);
        return $this->response->setJSON($result);
    }

    private function processUpdateFilledFile(int $filledFileId, array $data, array $files = []): array
    {
        // Validate that the user has permission to edit this filled file
        if (!$this->permissionManager->can(auth()->user(), 'filled-files.edit', 'filled_file', $filledFileId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to edit this filled file.'
            ];
        }

        // Check if filled file exists
        $existingFile = $this->filledFileModel->find($filledFileId);
        if (!$existingFile) {
            return [
                'success' => false,
                'message' => 'Filled file not found.'
            ];
        }

        // Validate filled data - decode if it's a JSON string
        $filledDataRaw = $data['filledData'] ?? [];
        
        // DEBUG: Log what we receive
        log_message('debug', 'FilledData raw type: ' . gettype($filledDataRaw));
        log_message('debug', 'FilledData raw value: ' . print_r($filledDataRaw, true));
        
        // Handle case where filledData might be an array (FormData duplicate keys)
        if (is_array($filledDataRaw) && !empty($filledDataRaw)) {
            // If it's an array with multiple values, take the last one (the actual data, not placeholder)
            if (isset($filledDataRaw[0]) && is_string($filledDataRaw[0])) {
                $filledDataRaw = end($filledDataRaw);
                log_message('debug', 'Took last element from array: ' . $filledDataRaw);
            } else {
                // It's already a proper array of data, use it directly
                $filledData = $filledDataRaw;
                log_message('debug', 'Using filledDataRaw directly as array');
                goto skip_json_decode;
            }
        }
        
        if (is_string($filledDataRaw)) {
            // Try to decode JSON
            $filledData = json_decode($filledDataRaw, true);
            
            // Check for JSON errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'Failed to parse JSON string. Error: ' . json_last_error_msg(),
                    'debug' => [
                        'raw_data' => $filledDataRaw,
                        'raw_data_length' => strlen($filledDataRaw),
                        'first_100_chars' => substr($filledDataRaw, 0, 100),
                        'json_error' => json_last_error_msg(),
                        'data_keys' => array_keys($data)
                    ]
                ];
            }
            
            if (!is_array($filledData)) {
                $filledData = [];
            }
        } else {
            $filledData = $filledDataRaw;
        }
        
        skip_json_decode:
        
        if (empty($filledData) && empty($files)) {
            return [
                'success' => false,
                'message' => 'Filled data is required.'
            ];
        }

        // Validate filled file name
        $name = trim($data['name'] ?? $existingFile['name'] ?? '');
        if (empty($name)) {
            return [
                'success' => false,
                'message' => 'File name is required.'
            ];
        }

        // Get list of fields with new images
        $fieldsWithNewImages = [];
        if (isset($data['fieldsWithNewImages'])) {
            if (is_string($data['fieldsWithNewImages'])) {
                $fieldsWithNewImages = json_decode($data['fieldsWithNewImages'], true) ?? [];
            } else {
                $fieldsWithNewImages = $data['fieldsWithNewImages'];
            }
        }

        // Process uploaded images from FormData
        $processedImages = [];
        if (!empty($files)) {
            foreach ($files as $key => $file) {
                // Check if this is an image field (starts with 'image_')
                if (strpos($key, 'image_') === 0) {
                    $fieldName = substr($key, 6); // Remove 'image_' prefix
                    
                    if ($file->isValid() && !$file->hasMoved()) {
                        $uploadedPath = $this->handleImageUpload($file, $fieldName, $filledFileId);
                        if ($uploadedPath !== false) {
                            $processedImages[$fieldName] = $uploadedPath;
                        }
                    }
                }
            }
        }

        // Merge processed images with filled data
        $filledDataWithImages = array_merge($filledData, $processedImages);

        // Prepare data for update
        $updateData = [
            'name' => $name,
            'filledData' => json_encode($filledDataWithImages),
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        // Update the filled file
        $result = $this->filledFileModel->update($filledFileId, $updateData);

        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Failed to update filled file. Please try again.',
                'errors' => $this->filledFileModel->errors()
            ];
        }

        return [
            'success' => true,
            'message' => 'Filled file updated successfully.',
            'data' => [
                'filledFileId' => $filledFileId,
                'name' => $name,
                'filledData' => $filledDataWithImages,
                'updatedAt' => $updateData['updatedAt']
            ]
        ];
    }

    public function deleteTemplate(int $templateId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $result = $this->processDeleteTemplate($templateId);
        return $this->response->setJSON($result);
    }

    private function processDeleteTemplate(int $templateId): array
    {
        // Validate that the user has permission to delete this template
        if (!$this->permissionManager->can(auth()->user(), 'templates.delete', 'template', $templateId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to delete this template.'
            ];
        }

        // Check if template exists
        $template = $this->templateModel->find($templateId);
        if (!$template) {
            return [
                'success' => false,
                'message' => 'Template not found.'
            ];
        }

        // Check if there are any filled files associated with this template
        $filledFiles = $this->filledFileModel->where('templateId', $templateId)->findAll();
        if (!empty($filledFiles)) {
            return [
                'success' => false,
                'message' => 'Cannot delete template because it has associated filled files. Please delete the filled files first.'
            ];
        }

        // Delete the template
        $result = $this->templateModel->delete($templateId);

        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Failed to delete template. Please try again.'
            ];
        }

        return [
            'success' => true,
            'message' => 'Template deleted successfully.'
        ];
    }

    public function deleteFilledFile(int $filledFileId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $result = $this->processDeleteFilledFile($filledFileId);
        return $this->response->setJSON($result);
    }

    private function processDeleteFilledFile(int $filledFileId): array
    {
        // Validate that the user has permission to delete this filled file
        if (!$this->permissionManager->can(auth()->user(), 'filled-files.delete', 'filled_file', $filledFileId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to delete this filled file.'
            ];
        }

        // Check if filled file exists
        $filledFile = $this->filledFileModel->find($filledFileId);
        if (!$filledFile) {
            return [
                'success' => false,
                'message' => 'Filled file not found.'
            ];
        }

        // Delete the filled file
        $result = $this->filledFileModel->delete($filledFileId);

        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Failed to delete filled file. Please try again.'
            ];
        }

        // Update template's updatedAt timestamp
        if (isset($filledFile['templateId'])) {
            $this->templateModel->update($filledFile['templateId'], ['updatedAt' => date('Y-m-d H:i:s')]);
        }

        return [
            'success' => true,
            'message' => 'Filled file deleted successfully.'
        ];
    }

    private function parseJsonField($field)
    {
        if (is_array($field)) {
            return $field;
        }

        if (! is_string($field) || trim($field) === '') {
            return [];
        }

        $decoded = json_decode($field, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function extractFieldName($field): string
    {
        if (is_string($field)) {
            return $field;
        }

        if (is_array($field)) {
            if (isset($field['name']) && is_string($field['name'])) {
                return $field['name'];
            }

            if (isset($field['field']) && is_string($field['field'])) {
                return $field['field'];
            }

            $first = reset($field);

            return is_string($first) ? $first : '';
        }

        return '';
    }

    private function getTemplateVariableFields(TemplateProcessor $templateProcessor): array
    {
        if (method_exists($templateProcessor, 'getVariableFields')) {
            $method = 'getVariableFields';

            return (array) $templateProcessor->$method();
        }

        return [];
    }

    protected function getUserPermissions()
    {
        $userId = auth()->id();
        $cacheKey = 'user_permissions_' . $userId;
        $cached = cache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $user = auth()->user();
        $permissions = [
            'canViewTemplates' => $this->permissionManager->can($user, 'templates.view'),
            'canCreateTemplates' => $this->permissionManager->can($user, 'templates.create'),
            'canEditTemplates' => $this->permissionManager->can($user, 'templates.edit'),
            'canDeleteTemplates' => $this->permissionManager->can($user, 'templates.delete'),
            'canViewFilledFiles' => $this->permissionManager->can($user, 'filled-files.view'),
            'canCreateFilledFiles' => $this->permissionManager->can($user, 'filled-files.create'),
            'canEditFilledFiles' => $this->permissionManager->can($user, 'filled-files.edit'),
            'canDeleteFilledFiles' => $this->permissionManager->can($user, 'filled-files.delete'),
            'canExportFilledFiles' => $this->permissionManager->can($user, 'filled-files.view'),
        ];
        
        // Cache for 5 minutes
        cache()->save($cacheKey, $permissions, 300);
        
        return $permissions;
    }

    private function redirectWithError(string $url, string $message)
    {
        return redirect()->to($url)->with('error', $message);
    }
    
    // New methods to handle template upload functionality
    public function uploadTemplateWizard()
    {
        if (!$this->permissionManager->can(auth()->user(), 'templates.create')) {
            return $this->redirectWithError('/dashboard', 'You do not have permission to create templates.');
        }
        
        return view('upload_template_wizard', [
            'title' => 'Upload Template',
            'userPermissions' => $this->getUserPermissions()
        ]);
    }
    
    public function createFilledFileWizard()
    {
        if (!$this->permissionManager->can(auth()->user(), 'filled_files.create')) {
            return $this->redirectWithError('/dashboard', 'You do not have permission to create filled files.');
        }
        
        // Get all available templates for the user to select
        $templates = $this->templateModel->findAll();
        
        return view('create_filled_file_wizard', [
            'title' => 'Create Filled File',
            'templates' => $templates,
            'userPermissions' => $this->getUserPermissions()
        ]);
    }
    
    public function analyzeTemplate()
    {
        if (!$this->permissionManager->can(auth()->user(), 'templates.create')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'You do not have permission to create templates.'
            ]);
        }
        
        $file = $this->request->getFile('templateFile');
        
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid template file provided.'
            ]);
        }
        
        if (!$file->guessExtension() || !in_array($file->guessExtension(), ['docx'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Only DOCX files are allowed.'
            ]);
        }
        
        // Create a temporary file path
        $tempPath = WRITEPATH . 'uploads/' . $file->getRandomName();
        $file->move(WRITEPATH . 'uploads', $file->getRandomName());
        
        // Analyze the template file to find field names
        try {
            $templateProcessor = new TemplateProcessor($tempPath);
            $fields            = $this->getTemplateVariableFields($templateProcessor);
            
            // Clean up the field names
            $cleanedFields = [];
            foreach ($fields as $field) {
                $fieldName = $this->extractFieldName($field);

                if ($fieldName === '') {
                    continue;
                }

                // Remove special characters that might be in the field names
                $cleanedField = preg_replace('/[^a-zA-Z0-9_]/', '', $fieldName);
                if (!empty($cleanedField) && !in_array($cleanedField, $cleanedFields)) {
                    $cleanedFields[] = $cleanedField;
                }
            }
            
            return $this->response->setJSON([
                'success' => true,
                'tempFilePath' => $tempPath,
                'originalFileName' => $file->getName(),
                'detectedFields' => $cleanedFields
            ]);
        } catch (\Exception $e) {
            // Clean up the file if analysis failed
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to analyze template file: ' . $e->getMessage()
            ]);
        }
    }
    
    public function finalizeTemplateUpload()
    {
        if (!$this->permissionManager->can(auth()->user(), 'templates.create')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'You do not have permission to create templates.'
            ]);
        }
        
        $postData = $this->request->getJSON(true);
        
        $tempFilePath = $postData['tempFilePath'] ?? '';
        $templateName = trim($postData['templateName'] ?? '');
        $templateFields = $postData['templateFields'] ?? [];
        $originalFileName = $postData['originalFileName'] ?? '';
        
        // Validate required fields
        if (empty($tempFilePath) || !file_exists($tempFilePath)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Template file is required.'
            ]);
        }
        
        if (empty($templateName)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Template name is required.'
            ]);
        }
        
        // Generate a safe path for the template
        $uploadPath = FCPATH . 'uploads/templates/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $newFileName = $this->generateUniqueFileName($originalFileName, $uploadPath);
        $finalPath = $uploadPath . $newFileName;
        
        // Move the temporary file to the final location
        if (!rename($tempFilePath, $finalPath)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to save template file.'
            ]);
        }
        
        // Calculate file size
        $fileSize = filesize($finalPath);
        
        // Prepare data for the template model
        $templateData = [
            'name' => $templateName,
            'originalFileName' => $originalFileName,
            'path' => 'uploads/templates/' . $newFileName,
            'size' => $fileSize,
            'templateFields' => json_encode($templateFields)
        ];
        
        // Insert the template
        $templateId = $this->templateModel->insert($templateData);
        
        if ($templateId === false) {
            // Remove the file if database insertion failed
            if (file_exists($finalPath)) {
                unlink($finalPath);
            }
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to create template record.',
                'errors' => $this->templateModel->errors()
            ]);
        }
        
        // The TemplateModel's afterInsert hook will handle creating ownership and permissions
        // But we need to make sure it has the correct current user context
        $currentUser = auth()->user();
        if ($currentUser) {
            // Create resource ownership (this is handled by the afterInsert callback in the model)
            // But we'll also ensure proper permissions are set
            $resourceOwnerModel = model('App\Models\ResourceOwnerModel');
            $aclEntryModel = model('App\Models\AclEntryModel');
            
            // Verify ownership is set
            if (!$resourceOwnerModel->getOwner('template', $templateId)) {
                $resourceOwnerModel->setOwner('template', $templateId, $currentUser->id);
            }
            
            // Ensure the owner has full control
            $aclEntryModel->grantPermission(
                'template',
                $templateId,
                'user',
                $currentUser->id,
                'full_control',
                $currentUser->id,
                false
            );
        }
        
        // Return the new template details
        $newTemplate = $this->templateModel->find($templateId);
        if ($newTemplate) {
            $newTemplate['templateFields'] = json_decode($newTemplate['templateFields'], true) ?: [];
        }
        
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Template uploaded successfully.',
            'newTemplate' => $newTemplate
        ]);
    }
    
    private function generateUniqueFileName(string $originalFileName, string $uploadPath): string
    {
        $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);
        $basename = pathinfo($originalFileName, PATHINFO_FILENAME);
        
        $counter = 1;
        $newFileName = $basename . '.' . $extension;
        $newFilePath = $uploadPath . $newFileName;
        
        while (file_exists($newFilePath)) {
            $newFileName = $basename . '_' . $counter . '.' . $extension;
            $newFilePath = $uploadPath . $newFileName;
            $counter++;
        }
        
        return $newFileName;
    }

    public function serveImage(int $filledFileId, string $imageName)
    {
        // Get the filled file to verify it exists and get image path
        $filledFile = $this->filledFileModel->find($filledFileId);
        
        if (!$filledFile) {
            return $this->response->setStatusCode(404)->setBody('Filled file not found');
        }

        // Check if user has permission to view this filled file
        if (!$this->permissionManager->can(auth()->user(), 'filled-files.view', 'filled_file', $filledFileId)) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        // Decode the filled data to get image paths
        // The model may auto-decode JSON, so check if it's already an array
        $filledData = $filledFile['filledData'];
        if (is_string($filledData)) {
            $filledData = json_decode($filledData, true) ?? [];
        } elseif (!is_array($filledData)) {
            $filledData = [];
        }
        
        // Find the image in the filled data
        $imagePath = null;
        foreach ($filledData as $field => $value) {
            if (is_string($value) && str_contains($value, $imageName)) {
                $imagePath = $value;
                break;
            }
        }

        if (!$imagePath) {
            return $this->response->setStatusCode(404)->setBody('Image not found in filled file data');
        }

        // Check if it's an absolute path or relative
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            // Redirect to external URL
            return redirect()->to($imagePath);
        }

        // Construct the full file path
        // Images are stored in writable/uploads/filled_files/{filledFileId}/
        $uploadsPath = WRITEPATH . 'uploads/filled_files/' . $filledFileId . '/';
        $fullImagePath = $uploadsPath . basename($imagePath);

        // Check if file exists
        if (!file_exists($fullImagePath)) {
            return $this->response->setStatusCode(404)->setBody('Image file not found on server');
        }

        // Get mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fullImagePath);
        finfo_close($finfo);

        
        // Serve the image
        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Length', (string) filesize($fullImagePath))
            ->setHeader('Cache-Control', 'public, max-age=86400') // Cache for 1 day
            ->setBody(file_get_contents($fullImagePath));
    }

    public function exportDocx(int $filledFileId)
    {
        // Check permissions
        if (!$this->permissionManager->can(auth()->user(), 'filled-files.view', 'filled_file', $filledFileId)) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        // Get the filled file
        $filledFile = $this->filledFileModel->find($filledFileId);
        if (!$filledFile) {
            return $this->response->setStatusCode(404)->setBody('Filled file not found');
        }

        // Get the template
        $template = $this->templateModel->find($filledFile['templateFileId']);
        if (!$template) {
            return $this->response->setStatusCode(404)->setBody('Template not found');
        }

        try {
            // Load the template file - path is already absolute
            $templatePath = $template['path'];
            
            log_message('debug', 'Export: Template path: ' . $templatePath);
            
            if (!file_exists($templatePath)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Template file not found on disk.'
                ]);
            }

            // Create template processor
            $templateProcessor = new TemplateProcessor($templatePath);

            // Get filled data - check if already decoded
            $filledData = is_array($filledFile['filledData']) ? $filledFile['filledData'] : json_decode($filledFile['filledData'], true);
            $fieldTypes = is_array($filledFile['fieldTypes']) ? $filledFile['fieldTypes'] : json_decode($filledFile['fieldTypes'], true);
            $imageSizes = is_array($filledFile['imageSizes']) ? $filledFile['imageSizes'] : json_decode($filledFile['imageSizes'], true);

            // Process each field
            foreach ($filledData as $field => $value) {
                $fieldType = $fieldTypes[$field] ?? 'text';
                
                if ($fieldType === 'image' && !empty($value)) {
                    // Handle image fields
                    $imagePath = WRITEPATH . 'uploads/images/' . $value;
                    if (file_exists($imagePath)) {
                        $width = $imageSizes[$field]['width'] ?? 300;
                        $height = $imageSizes[$field]['height'] ?? 200;
                        $ratio = $imageSizes[$field]['ratio'] ?? true;
                        
                        $templateProcessor->setImageValue(
                            $field,
                            [
                                'path' => $imagePath,
                                'width' => $width,
                                'height' => $height,
                                'ratio' => $ratio
                            ]
                        );
                    } else {
                        // Image not found, replace with placeholder text
                        $templateProcessor->setValue($field, '[Image not found]');
                    }
                } else {
                    // Handle text/paragraph fields
                    $templateProcessor->setValue($field, $value ?? '');
                }
            }

            // Generate output filename
            $outputFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filledFile['name']) . '_' . date('Y-m-d_His') . '.docx';
            $outputPath = WRITEPATH . 'uploads/exports/' . $outputFilename;

            // Ensure exports directory exists
            if (!is_dir(WRITEPATH . 'uploads/exports/')) {
                mkdir(WRITEPATH . 'uploads/exports/', 0755, true);
            }

            // Save the generated file
            $templateProcessor->saveAs($outputPath);

            // Return file for download
            return $this->response->download($outputPath, null)->setFileName($outputFilename);

        } catch (\Exception $e) {
            log_message('error', 'DOCX export error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error generating DOCX: ' . $e->getMessage()
            ]);
        }
    }

    public function exportPdf(int $filledFileId)
    {
        // Check permissions
        if (!$this->permissionManager->can(auth()->user(), 'filled-files.view', 'filled_file', $filledFileId)) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        // Get the filled file
        $filledFile = $this->filledFileModel->find($filledFileId);
        if (!$filledFile) {
            return $this->response->setStatusCode(404)->setBody('Filled file not found');
        }

        // TODO: Implement PDF generation
        // This would involve:
        // 1. Loading the template file
        // 2. Replacing placeholders with filled data
        // 3. Converting to PDF (possibly via DOCX first)

        return $this->response->setJSON([
            'success' => false,
            'message' => 'PDF export is not yet implemented.'
        ]);
    }
}
