<?php

namespace App\Controllers;

use PhpOffice\PhpWord\TemplateProcessor;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\PermissionManager;

/**
 * FileExplorer Controller
 *
 * Handles template and filled file management operations including viewing,
 * creating, updating, deleting, and exporting documents.
 */
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

    /**
     * Display the file explorer interface
     *
     * @return mixed View or redirect
     */
    public function index()
    {
        if (!$this->permissionManager->can(auth()->user(), 'templates.view')) {
            return $this->redirectWithError('/', 'You do not have permission to view templates.');
        }

        $templates = $this->getTemplatesWithFilledFiles();

        return view(
            'file_explorer',
            [
            'title' => 'File Explorer',
            'templates' => $templates,
            'userPermissions' => $this->getUserPermissions()
            ]
        );
    }

    /**
     * Retrieve all templates with their associated filled files
     *
     * @return array Templates with parsed fields
     */
    private function getTemplatesWithFilledFiles(): array
    {
        $templates = $this->templateModel->getTemplatesWithFilledFilesOptimized();

        foreach ($templates as &$template) {
            $template['templateFields'] = $this->parseJsonField($template['templateFields']);
            
            // Add permission information for templates
            $template['canDelete'] = $this->permissionManager->can(auth()->user(), 'templates.delete', 'template', $template['id']);

            // Initialize empty filledData for old filled files
            if (isset($template['filledFiles']) && is_array($template['filledFiles'])) {
                foreach ($template['filledFiles'] as &$filledFile) {
                    $filledFile['filledData'] = $this->parseJsonField($filledFile['filledData']);
                    $filledFile['fieldTypes'] = $this->parseJsonField($filledFile['fieldTypes']);
                    $filledFile['imageSizes'] = $this->parseJsonField($filledFile['imageSizes']);
                    
                    // Add ownership information
                    $filledFile['isOwner'] = isset($filledFile['user_id']) && $filledFile['user_id'] == auth()->id();
                    $filledFile['canEdit'] = $this->permissionManager->can(auth()->user(), 'filled-files.edit', 'filled_file', $filledFile['id']);
                    $filledFile['canDelete'] = $this->permissionManager->can(auth()->user(), 'filled-files.delete', 'filled_file', $filledFile['id']);
                    $filledFile['canExport'] = $this->permissionManager->can(auth()->user(), 'filled-files.export', 'filled_file', $filledFile['id']);

                    // If filledData is empty or is an empty array, initialize with template fields
                    if (empty($filledFile['filledData']) || (is_array($filledFile['filledData']) && count($filledFile['filledData']) === 0)) {
                        $filledData = [];
                        if (is_array($template['templateFields'])) {
                            foreach ($template['templateFields'] as $field) {
                                $fieldName = is_string($field) ? $field : ($field['name'] ?? $field['field'] ?? '');
                                if ($fieldName) {
                                    $filledData[$fieldName] = '';
                                }
                            }
                        }
                        $filledFile['filledData'] = $filledData;
                    }
                }
            }
        }

        return $templates;
    }

    /**
     * Normalize filled file data with default values
     *
     * @param array $file File data to normalize
     * @return array Normalized file data
     */
    private function normalizeFilledFile(array $file): array
    {
        $file = array_merge(
            [
            'id' => null,
            'name' => 'Unnamed File',
            'createdAt' => null,
            'updatedAt' => null,
            'filledData' => []
            ],
            $file
        );

        $file['filledData'] = $this->parseJsonField($file['filledData']);

        return $file;
    }

    /**
     * Update an existing template
     *
     * @param int $templateId Template ID to update
     * @param array $data Update data
     * @return array Result with success status and message
     */
    private function updateTemplate(int $templateId, array $data): array
    {
        if (!$this->permissionManager->can(auth()->user(), 'templates.edit', 'template', $templateId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to edit this template.'
            ];
        }

        $existingTemplate = $this->templateModel->find($templateId);
        if (!$existingTemplate) {
            return [
                'success' => false,
                'message' => 'Template not found.'
            ];
        }

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

    /**
     * Create a new filled file from a template (AJAX endpoint)
     *
     * @return ResponseInterface JSON response with creation result
     */
    public function createFilledFile()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        $result = $this->processCreateFilledFile($data);
        return $this->response->setJSON($result);
    }

    /**
     * Process the creation of a filled file with validation and permission checks
     *
     * @param array $data Request data with template ID, name, and filled data
     * @return array Response with success status and message
     */
    private function processCreateFilledFile(array $data): array
    {
        $templateId = (int) ($data['templateId'] ?? $data['template_id'] ?? 0);
        if ($templateId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid template ID provided.'
            ];
        }

        // Check if user has global permission to create filled files
        if (!$this->permissionManager->can(auth()->user(), 'filled-files.create')) {
            return [
                'success' => false,
                'message' => 'You do not have permission to create filled files.'
            ];
        }

        $template = $this->templateModel->find($templateId);
        if (!$template) {
            return [
                'success' => false,
                'message' => 'Template not found.'
            ];
        }

        // Initialize filledData with template fields if not provided
        $filledData = $data['filledData'] ?? $data['filled_data'] ?? null;

        if (is_string($filledData)) {
            $filledData = json_decode($filledData, true) ?? [];
        }
        if (!is_array($filledData)) {
            $filledData = [];
        }

        // If filledData is empty or null, initialize with template fields as empty strings
        if (empty($filledData)) {
            $templateFields = $this->parseJsonField($template['templateFields']);
            if (is_array($templateFields)) {
                foreach ($templateFields as $field) {
                    $fieldName = is_string($field) ? $field : ($field['name'] ?? $field['field'] ?? '');
                    if ($fieldName) {
                        $filledData[$fieldName] = '';
                    }
                }
            }
        }

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
            'user_id' => auth()->id(),
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

    /**
     * Handle image upload for filled file fields
     *
     * @param mixed $file Uploaded file object
     * @param string $fieldKey Field key for the image
     * @param int $filledFileId Filled file ID for organizing uploads
     * @return string|false Relative path to uploaded image or false on failure
     */
    private function handleImageUpload($file, string $fieldKey, int $filledFileId): string|false
    {
        if (!$file || !$file->isValid()) {
            return false;
        }

        $uploadPath = WRITEPATH . 'uploads/filled_files/' . $filledFileId . '/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $originalName = $file->getName();
        $extension = $file->getExtension();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
        $fileName = $safeName . '_' . uniqid() . '.' . $extension;

        if ($file->move($uploadPath, $fileName)) {
            return $fileName; // Return just the filename, not the full path
        }

        return false;
    }

    /**
     * Upload an image for a specific field in a filled file (AJAX endpoint)
     *
     * @param int $filledFileId Filled file ID
     * @param string $fieldName Field name for the image
     * @return ResponseInterface JSON response with upload result
     */
    public function uploadFieldImage(int $filledFileId, string $fieldName)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        if (!$this->permissionManager->can(auth()->user(), 'filled-files.edit', 'filled_file', $filledFileId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'You do not have permission to edit this filled file.'
            ]);
        }

        $filledFile = $this->filledFileModel->find($filledFileId);
        if (!$filledFile) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Filled file not found.'
            ]);
        }

        $file = $this->request->getFile('image');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No valid image file provided.'
            ]);
        }

        $fileName = $this->handleImageUpload($file, $fieldName, $filledFileId);
        if ($fileName === false) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to upload image.'
            ]);
        }

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

    /**
     * Update an existing filled file (AJAX endpoint)
     * Handles both JSON and multipart form data
     *
     * @param int $filledFileId Filled file ID to update
     * @return ResponseInterface JSON response with update result
     */
    public function updateFilledFile(int $filledFileId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        // Try to get JSON first, but don't fail if it's not JSON (could be FormData)
        $jsonData = null;
        try {
            $jsonData = $this->request->getJSON(true);
        } catch (\Exception $e) {
            // Not JSON, that's okay
        }

        $postData = $this->request->getPost();

        // Merge both sources, preferring JSON data
        $data = array_merge($postData ?? [], $jsonData ?? []);

        $files = $this->request->getFiles();

        $result = $this->processUpdateFilledFile($filledFileId, $data, $files);
        return $this->response->setJSON($result);
    }

    /**
     * Process filled file update with validation, permission checks, and image handling
     *
     * @param int $filledFileId Filled file ID to update
     * @param array $data Request data with name and filled data
     * @param array $files Uploaded image files
     * @return array Response with success status and message
     */
    private function processUpdateFilledFile(int $filledFileId, array $data, array $files = []): array
    {
        if (!$this->permissionManager->can(auth()->user(), 'filled-files.edit', 'filled_file', $filledFileId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to edit this filled file.'
            ];
        }

        $existingFile = $this->filledFileModel->find($filledFileId);
        if (!$existingFile) {
            return [
                'success' => false,
                'message' => 'Filled file not found.'
            ];
        }

        $filledDataRaw = $data['filledData'] ?? [];

        log_message('debug', 'FilledData raw type: ' . gettype($filledDataRaw));
        log_message('debug', 'FilledData raw value: ' . print_r($filledDataRaw, true));

        // Handle case where filledData might be an array (FormData duplicate keys)
        if (is_array($filledDataRaw) && !empty($filledDataRaw)) {
            if (isset($filledDataRaw[0]) && is_string($filledDataRaw[0])) {
                $filledDataRaw = end($filledDataRaw);
                log_message('debug', 'Took last element from array: ' . $filledDataRaw);
            } else {
                $filledData = $filledDataRaw;
                log_message('debug', 'Using filledDataRaw directly as array');
                goto skip_json_decode;
            }
        }

        if (is_string($filledDataRaw)) {
            $filledData = json_decode($filledDataRaw, true);

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

        $name = trim($data['name'] ?? $existingFile['name'] ?? '');
        if (empty($name)) {
            return [
                'success' => false,
                'message' => 'File name is required.'
            ];
        }

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

        // Extract and update fieldTypes and imageSizes if provided
        $fieldTypes = null;
        $imageSizes = null;

        if (isset($data['fieldTypes'])) {
            if (is_string($data['fieldTypes'])) {
                $fieldTypes = json_decode($data['fieldTypes'], true);
            } else {
                $fieldTypes = $data['fieldTypes'];
            }
        }

        if (isset($data['imageSizes'])) {
            if (is_string($data['imageSizes'])) {
                $imageSizes = json_decode($data['imageSizes'], true);
            } else {
                $imageSizes = $data['imageSizes'];
            }
        }

        // Prepare data for update
        $updateData = [
            'name' => $name,
            'filledData' => json_encode($filledDataWithImages),
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        // Add fieldTypes and imageSizes to update if provided
        if ($fieldTypes !== null) {
            $updateData['fieldTypes'] = json_encode($fieldTypes);
        }

        if ($imageSizes !== null) {
            $updateData['imageSizes'] = json_encode($imageSizes);
        }

        // Update the filled file
        $result = $this->filledFileModel->update($filledFileId, $updateData);

        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Failed to update filled file. Please try again.',
                'errors' => $this->filledFileModel->errors()
            ];
        }

        // Prepare response data
        $responseData = [
            'filledFileId' => $filledFileId,
            'name' => $name,
            'filledData' => $filledDataWithImages,
            'updatedAt' => $updateData['updatedAt']
        ];

        if ($fieldTypes !== null) {
            $responseData['fieldTypes'] = $fieldTypes;
        }

        if ($imageSizes !== null) {
            $responseData['imageSizes'] = $imageSizes;
        }

        return [
            'success' => true,
            'message' => 'Filled file updated successfully.',
            'data' => $responseData
        ];
    }

    /**
     * Delete a template (AJAX endpoint)
     *
     * @param int $templateId Template ID to delete
     * @return ResponseInterface JSON response with deletion result
     */
    public function deleteTemplate(int $templateId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $result = $this->processDeleteTemplate($templateId);
        return $this->response->setJSON($result);
    }

    /**
     * Process template deletion with permission checks and validation
     * Prevents deletion if template has associated filled files
     *
     * @param int $templateId Template ID to delete
     * @return array Response with success status and message
     */
    private function processDeleteTemplate(int $templateId): array
    {
        if (!$this->permissionManager->can(auth()->user(), 'templates.delete', 'template', $templateId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to delete this template.'
            ];
        }

        $template = $this->templateModel->find($templateId);
        if (!$template) {
            return [
                'success' => false,
                'message' => 'Template not found.'
            ];
        }

        // Prevent deletion if template has associated filled files
        $filledFiles = $this->filledFileModel->where('templateFileId', $templateId)->findAll();
        if (!empty($filledFiles)) {
            return [
                'success' => false,
                'message' => 'Cannot delete template because it has associated filled files. Please delete the filled files first.'
            ];
        }

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

    /**
     * Delete a filled file (AJAX endpoint)
     *
     * @param int $filledFileId Filled file ID to delete
     * @return ResponseInterface JSON response with deletion result
     */
    public function deleteFilledFile(int $filledFileId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $result = $this->processDeleteFilledFile($filledFileId);
        return $this->response->setJSON($result);
    }

    /**
     * Process filled file deletion with permission checks
     *
     * @param int $filledFileId Filled file ID to delete
     * @return array Response with success status and message
     */
    private function processDeleteFilledFile(int $filledFileId): array
    {
        if (!$this->permissionManager->can(auth()->user(), 'filled-files.delete', 'filled_file', $filledFileId)) {
            return [
                'success' => false,
                'message' => 'You do not have permission to delete this filled file.'
            ];
        }

        $filledFile = $this->filledFileModel->find($filledFileId);
        if (!$filledFile) {
            return [
                'success' => false,
                'message' => 'Filled file not found.'
            ];
        }

        $result = $this->filledFileModel->delete($filledFileId);

        if ($result === false) {
            return [
                'success' => false,
                'message' => 'Failed to delete filled file. Please try again.'
            ];
        }

        if (isset($filledFile['templateId'])) {
            $this->templateModel->update($filledFile['templateId'], ['updatedAt' => date('Y-m-d H:i:s')]);
        }

        return [
            'success' => true,
            'message' => 'Filled file deleted successfully.'
        ];
    }

    /**
     * Parse JSON field data, returning array or empty array on error
     *
     * @param mixed $field JSON string or array
     * @return array Parsed field data
     */
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

    /**
     * Extract field name from various field data structures
     *
     * @param mixed $field Field data (string, array with 'name' or 'field' key)
     * @return string Extracted field name or empty string
     */
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

    /**
     * Get variable fields from template processor
     *
     * @param TemplateProcessor $templateProcessor Template processor instance
     * @return array Variable fields extracted from template
     */
    private function getTemplateVariableFields(TemplateProcessor $templateProcessor): array
    {
        try {
            // TemplateProcessor has a getVariables() method that returns all placeholders
            if (method_exists($templateProcessor, 'getVariables')) {
                return $templateProcessor->getVariables();
            }

            // Fallback: use reflection if getVariables doesn't exist
            log_message('warning', 'TemplateProcessor::getVariables() not found, using reflection fallback');

            $reflection = new \ReflectionClass($templateProcessor);
            $fields = [];

            // Try to access getVariablesForPart if it exists
            if ($reflection->hasMethod('getVariablesForPart')) {
                $method = $reflection->getMethod('getVariablesForPart');
                $method->setAccessible(true);

                if ($reflection->hasProperty('tempDocumentMainPart')) {
                    $property = $reflection->getProperty('tempDocumentMainPart');
                    $property->setAccessible(true);
                    $mainPart = $property->getValue($templateProcessor);

                    $fields = array_merge($fields, $method->invoke($templateProcessor, $mainPart));
                }

                // Check headers
                if ($reflection->hasProperty('tempDocumentHeaders')) {
                    $property = $reflection->getProperty('tempDocumentHeaders');
                    $property->setAccessible(true);
                    $headers = $property->getValue($templateProcessor);

                    if (is_array($headers)) {
                        foreach ($headers as $header) {
                            $fields = array_merge($fields, $method->invoke($templateProcessor, $header));
                        }
                    }
                }

                // Check footers
                if ($reflection->hasProperty('tempDocumentFooters')) {
                    $property = $reflection->getProperty('tempDocumentFooters');
                    $property->setAccessible(true);
                    $footers = $property->getValue($templateProcessor);

                    if (is_array($footers)) {
                        foreach ($footers as $footer) {
                            $fields = array_merge($fields, $method->invoke($templateProcessor, $footer));
                        }
                    }
                }
            }

            return array_values(array_unique(array_filter($fields)));
        } catch (\Exception $e) {
            log_message('error', 'Failed to extract template variables: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Get cached user permissions for templates and filled files
     * Caches results for 5 minutes to improve performance
     *
     * @return array Associative array of permission flags
     */
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
            'canExportFilledFiles' => $this->permissionManager->can($user, 'filled-files.export'),
        ];

        // Cache for 5 minutes
        cache()->save($cacheKey, $permissions, 300);

        return $permissions;
    }

    /**
     * Redirect with error message in flash data
     *
     * @param string $url URL to redirect to
     * @param string $message Error message to display
     * @return RedirectResponse Redirect response with error message
     */
    private function redirectWithError(string $url, string $message)
    {
        return redirect()->to($url)->with('error', $message);
    }

    /**
     * Handle template upload wizard step 1 - analyze uploaded file (AJAX endpoint)
     * Extracts template fields and stores temporary file
     *
     * @return ResponseInterface JSON response with detected fields and temp file path
     */
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

        // Create a temporary file path using a single generated random name
        $tempFileName = $file->getRandomName();
        $tempPath     = WRITEPATH . 'uploads/' . $tempFileName;

        if (!$file->move(WRITEPATH . 'uploads', $tempFileName)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unable to store the uploaded template temporarily.'
            ]);
        }

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
                'detectedFields' => $cleanedFields,
                'rawFields' => $fields  // Add raw fields for debugging
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

    /**
     * Finalize template upload after field configuration (AJAX endpoint)
     * Moves temporary file to permanent location and creates database record
     *
     * @return ResponseInterface JSON response with upload result
     */
    public function finalizeTemplateUpload()
    {
        if (!$this->permissionManager->can(auth()->user(), 'templates.create')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'You do not have permission to create templates.'
            ]);
        }

        $postData = $this->request->getJSON(true);

        // Log the received data for debugging
        log_message('debug', 'Finalize template upload - raw postData: ' . json_encode($postData));

        $tempFilePath = $postData['tempFilePath'] ?? '';
        $templateName = trim($postData['templateName'] ?? '');
        $templateFieldsRaw = $postData['templateFields'] ?? [];

        // Handle templateFields - could be array or JSON string
        if (is_string($templateFieldsRaw)) {
            $templateFields = json_decode($templateFieldsRaw, true) ?? [];
        } else {
            $templateFields = is_array($templateFieldsRaw) ? $templateFieldsRaw : [];
        }

        $originalFileName = $postData['originalFileName'] ?? '';

        log_message('debug', 'Finalize template - templateFields type: ' . gettype($templateFieldsRaw) . ', count: ' . count($templateFields));

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
        // Ensure templateFields is an array before encoding
        if (!is_array($templateFields)) {
            log_message('error', 'templateFields is not an array: ' . gettype($templateFields));
            $templateFields = [];
        }

        $templateFieldsJson = json_encode($templateFields);
        if ($templateFieldsJson === false) {
            log_message('error', 'Failed to encode templateFields: ' . json_last_error_msg());
            $templateFieldsJson = '[]';
        }

        $templateData = [
            'name' => $templateName,
            'originalFileName' => $originalFileName,
            'path' => 'uploads/templates/' . $newFileName,
            'size' => $fileSize,
            'templateFields' => $templateFieldsJson
        ];

        log_message('debug', 'Template data prepared: ' . json_encode($templateData));

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

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Template uploaded successfully.',
            'newTemplate' => $newTemplate
        ]);
    }

    /**
     * Generate a unique filename by appending counter if file already exists
     *
     * @param string $originalFileName Original uploaded filename
     * @param string $uploadPath Directory path to check for existing files
     * @return string Unique filename
     */
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

    /**
     * Serve an image file from filled file uploads
     *
     * @param int $filledFileId Filled file ID containing the image
     * @param string $imageName Image filename to serve
     * @return ResponseInterface Image file response or 404
     */
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

    /**
     * Export a filled file as a DOCX document with merged template data
     *
     * @param int $filledFileId Filled file ID to export
     * @return ResponseInterface DOCX file download or error response
     */
    public function exportDocx(int $filledFileId)
    {
        if (!$this->permissionManager->can(auth()->user(), 'filled-files.export', 'filled_file', $filledFileId)) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $filledFile = $this->filledFileModel->find($filledFileId);
        if (!$filledFile) {
            return $this->response->setStatusCode(404)->setBody('Filled file not found');
        }

        $template = $this->templateModel->find($filledFile['templateFileId']);
        if (!$template) {
            return $this->response->setStatusCode(404)->setBody('Template not found');
        }

        try {
            $templatePath = $template['path'];

            log_message('debug', 'Export: Template path: ' . $templatePath);

            if (!file_exists($templatePath)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Template file not found on disk.'
                ]);
            }

            $templateProcessor = new TemplateProcessor($templatePath);

            $filledData = is_array($filledFile['filledData']) ? $filledFile['filledData'] : json_decode($filledFile['filledData'] ?? '{}', true);
            $fieldTypes = is_array($filledFile['fieldTypes']) ? $filledFile['fieldTypes'] : json_decode($filledFile['fieldTypes'] ?? '{}', true);
            $imageSizes = is_array($filledFile['imageSizes']) ? $filledFile['imageSizes'] : json_decode($filledFile['imageSizes'] ?? '{}', true);

            // Process each field based on type
            foreach ($filledData as $field => $value) {
                $fieldType = $fieldTypes[$field] ?? 'text';

                if ($fieldType === 'image' && !empty($value)) {
                    // Image path is stored relative to filled_files directory with filledFileId subdirectory
                    $imagePath = WRITEPATH . 'uploads/filled_files/' . $filledFileId . '/' . $value;

                    if (file_exists($imagePath)) {
                        $width = $imageSizes[$field]['width'] ?? 300;
                        $height = $imageSizes[$field]['height'] ?? 200;
                        $ratio = $imageSizes[$field]['ratio'] ?? true;

                        log_message('debug', 'Export DOCX: Setting image for field ' . $field . ' from path: ' . $imagePath);

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
                        log_message('warning', 'Export DOCX: Image not found for field ' . $field . ' at path: ' . $imagePath);
                        $templateProcessor->setValue($field, '[Image not found]');
                    }
                } else {
                    $templateProcessor->setValue($field, $value ?? '');
                }
            }

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

    /**
     * Export a filled file as a PDF document
     * Uses LibreOffice to convert generated DOCX to PDF
     *
     * @param int $filledFileId Filled file ID to export
     * @return ResponseInterface PDF file download or error response
     */
    public function exportPdf(int $filledFileId)
    {
        if (!$this->permissionManager->can(auth()->user(), 'filled-files.export', 'filled_file', $filledFileId)) {
            return $this->response->setStatusCode(403)->setBody('Access denied');
        }

        $filledFile = $this->filledFileModel->find($filledFileId);
        if (!$filledFile) {
            return $this->response->setStatusCode(404)->setBody('Filled file not found');
        }

        $template = $this->templateModel->find($filledFile['templateFileId']);
        if (!$template) {
            return $this->response->setStatusCode(404)->setBody('Template not found');
        }

        try {
            $templatePath = $template['path'];

            log_message('debug', 'PDF Export: Template path: ' . $templatePath);

            if (!file_exists($templatePath)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Template file not found on disk.'
                ]);
            }

            // Step 1: Generate DOCX file first
            $templateProcessor = new TemplateProcessor($templatePath);

            $filledData = is_array($filledFile['filledData']) ? $filledFile['filledData'] : json_decode($filledFile['filledData'] ?? '{}', true);
            $fieldTypes = is_array($filledFile['fieldTypes']) ? $filledFile['fieldTypes'] : json_decode($filledFile['fieldTypes'] ?? '{}', true);
            $imageSizes = is_array($filledFile['imageSizes']) ? $filledFile['imageSizes'] : json_decode($filledFile['imageSizes'] ?? '{}', true);

            // Process each field based on type
            foreach ($filledData as $field => $value) {
                $fieldType = $fieldTypes[$field] ?? 'text';

                if ($fieldType === 'image' && !empty($value)) {
                    // Image path is stored relative to filled_files directory with filledFileId subdirectory
                    $imagePath = WRITEPATH . 'uploads/filled_files/' . $filledFileId . '/' . $value;

                    if (file_exists($imagePath)) {
                        $width = $imageSizes[$field]['width'] ?? 300;
                        $height = $imageSizes[$field]['height'] ?? 200;
                        $ratio = $imageSizes[$field]['ratio'] ?? true;

                        log_message('debug', 'Export PDF: Setting image for field ' . $field . ' from path: ' . $imagePath);

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
                        log_message('warning', 'Export PDF: Image not found for field ' . $field . ' at path: ' . $imagePath);
                        $templateProcessor->setValue($field, '[Image not found]');
                    }
                } else {
                    $templateProcessor->setValue($field, $value ?? '');
                }
            }

            $baseFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filledFile['name']) . '_' . date('Y-m-d_His');
            $docxFilename = $baseFilename . '.docx';
            $pdfFilename = $baseFilename . '.pdf';

            $docxPath = WRITEPATH . 'uploads/exports/' . $docxFilename;
            $pdfPath = WRITEPATH . 'uploads/exports/' . $pdfFilename;
            $exportsDir = WRITEPATH . 'uploads/exports/';

            // Ensure exports directory exists
            if (!is_dir($exportsDir)) {
                mkdir($exportsDir, 0755, true);
            }

            // Save the generated DOCX file
            $templateProcessor->saveAs($docxPath);

            log_message('debug', 'PDF Export: DOCX generated at: ' . $docxPath);

            // Step 2: Convert DOCX to PDF using LibreOffice
            $libreOfficePath = $this->findLibreOfficePath();

            if (!$libreOfficePath) {
                // Clean up DOCX file
                if (file_exists($docxPath)) {
                    unlink($docxPath);
                }

                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'LibreOffice is not installed or could not be found. Please install LibreOffice to enable PDF export.'
                ]);
            }

            // Execute LibreOffice conversion command
            // Use custom user profile directory to avoid permission issues
            $userProfile = WRITEPATH . 'cache/libreoffice';
            if (!is_dir($userProfile)) {
                mkdir($userProfile, 0755, true);
            }

            $command = sprintf(
                '%s -env:UserInstallation=file://%s --headless --convert-to pdf:writer_pdf_Export --outdir %s %s 2>&1',
                escapeshellarg($libreOfficePath),
                escapeshellarg($userProfile),
                escapeshellarg($exportsDir),
                escapeshellarg($docxPath)
            );

            log_message('debug', 'PDF Export: Executing command: ' . $command);

            exec($command, $output, $returnCode);

            log_message('debug', 'PDF Export: Command output: ' . implode("\n", $output));
            log_message('debug', 'PDF Export: Return code: ' . $returnCode);

            // Clean up DOCX file
            if (file_exists($docxPath)) {
                unlink($docxPath);
            }

            // Check if PDF was created successfully
            if ($returnCode !== 0 || !file_exists($pdfPath)) {
                log_message('error', 'PDF Export: Conversion failed. Output: ' . implode("\n", $output));
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Failed to convert DOCX to PDF. LibreOffice conversion error.'
                ]);
            }

            log_message('info', 'PDF Export: Successfully generated PDF at: ' . $pdfPath);

            // Return PDF file for download
            return $this->response->download($pdfPath, null)->setFileName($pdfFilename);
        } catch (\Exception $e) {
            log_message('error', 'PDF export error: ' . $e->getMessage());

            // Clean up any temporary files
            if (isset($docxPath) && file_exists($docxPath)) {
                unlink($docxPath);
            }
            if (isset($pdfPath) && file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Find LibreOffice executable path
     * Checks common installation locations on different operating systems
     *
     * @return string|null Path to LibreOffice executable or null if not found
     */
    private function findLibreOfficePath(): ?string
    {
        $possiblePaths = [];

        // Detect operating system and set possible paths
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows paths
            $possiblePaths = [
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
                'soffice.exe', // If in PATH
            ];
        } elseif (PHP_OS === 'Darwin') {
            // macOS paths
            $possiblePaths = [
                '/Applications/LibreOffice.app/Contents/MacOS/soffice',
                '/usr/local/bin/soffice',
                'soffice', // If in PATH
            ];
        } else {
            // Linux/Unix paths
            $possiblePaths = [
                '/usr/bin/soffice',
                '/usr/bin/libreoffice',
                '/usr/local/bin/soffice',
                '/usr/local/bin/libreoffice',
                '/opt/libreoffice/program/soffice',
                'soffice', // If in PATH
                'libreoffice', // If in PATH
            ];
        }

        // Check each possible path
        foreach ($possiblePaths as $path) {
            // For commands without full path, check if they exist in PATH
            if (basename($path) === $path) {
                $checkCommand = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'where' : 'which';
                exec("$checkCommand $path 2>&1", $output, $returnCode);
                if ($returnCode === 0 && !empty($output[0])) {
                    log_message('debug', 'LibreOffice found via PATH: ' . $output[0]);
                    return trim($output[0]);
                }
            } else {
                // Check if full path exists
                if (file_exists($path)) {
                    log_message('debug', 'LibreOffice found at: ' . $path);
                    return $path;
                }
            }
        }

        log_message('warning', 'LibreOffice executable not found in any common locations');
        return null;
    }
}
