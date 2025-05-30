<?php

namespace App\Controllers;

use PhpOffice\PhpWord\TemplateProcessor;
use \CodeIgniter\HTTP\ResponseInterface;

class FileExplorer extends BaseController
{
    protected $templateModel;
    protected $filledFileModel;

    public function __construct()
    {
        $this->templateModel = model('App\Models\TemplateModel');
        $this->filledFileModel = model('App\Models\FilledFilesModel');
    }

    public function index()
    {
        if (!$this->checkPermission('templates.view')) {
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
        $templates = $this->templateModel->findAll();
        $filledFiles = $this->filledFileModel->findAll();

        foreach ($templates as &$template) {
            $template['templateFields'] = $this->parseJsonField($template['templateFields']);
            $template['filledFiles'] = $this->getFilledFilesForTemplate($template['id'], $filledFiles);
        }

        return $templates;
    }

    private function getFilledFilesForTemplate(int $templateId, array $allFilledFiles): array
    {
        $filledFiles = [];
        
        foreach ($allFilledFiles as $file) {
            if (isset($file['templateFileId']) && $file['templateFileId'] == $templateId) {
                $file = $this->normalizeFilledFile($file);
                $filledFiles[] = $file;
            }
        }
        
        return $filledFiles;
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

    private function parseJsonField($field): array
    {
        if (is_string($field)) {
            $decoded = json_decode($field, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return is_array($field) ? $field : [];
    }

    private function checkPermission(string $permission): bool
    {
        return auth()->user()->can($permission);
    }

    private function redirectWithError(string $url, string $message)
    {
        return redirect()->to($url)->with('error', $message);
    }

    private function jsonError(int $statusCode, string $message): ResponseInterface
    {
        return $this->response->setStatusCode($statusCode)->setJSON(
            [
            'success' => false,
            'message' => $message
            ]
        );
    }

    private function jsonSuccess(string $message, array $data = []): ResponseInterface
    {
        $response = ['success' => true, 'message' => $message];
        return $this->response->setJSON(array_merge($response, $data));
    }

    private function validateAjaxRequest(string $method = 'POST'): bool
    {
        return $this->request->isAJAX() && $this->request->getMethod(true) === $method;
    }

    protected function getUserPermissions()
    {
        $user = auth()->user();
        return [
            'canViewTemplates' => $user->can('templates.view'),
            'canCreateTemplates' => $user->can('templates.create'),
            'canEditTemplates' => $user->can('templates.edit'),
            'canDeleteTemplates' => $user->can('templates.delete'),
            'canViewFilledFiles' => $user->can('filled-files.view'),
            'canCreateFilledFiles' => $user->can('filled-files.create'),
            'canEditFilledFiles' => $user->can('filled-files.edit'),
            'canDeleteFilledFiles' => $user->can('filled-files.delete'),
            'canExportFilledFiles' => $user->can('filled-files.view'),
        ];
    }

    public function analyzeTemplate()
    {
        if (!$this->checkPermission('templates.create')) {
            return $this->jsonError(403, 'You do not have permission to create templates.');
        }

        if (!$this->validateAjaxRequest()) {
            return $this->jsonError(400, 'Invalid request');
        }

        $file = $this->request->getFile('templateFile');
        
        if (!$this->isValidTemplateFile($file)) {
            return $this->jsonError(400, 'No valid .docx file uploaded');
        }

        try {
            $tempFilePath = $this->saveTemporaryFile($file);
            $templateFields = $this->extractTemplateFields($tempFilePath);

            return $this->jsonSuccess(
                'Template analyzed successfully', [
                'tempFilePath' => $tempFilePath,
                'templateFields' => $templateFields
                ]
            );

        } catch (\Exception $e) {
            log_message('error', 'Template analysis error: ' . $e->getMessage());
            return $this->jsonError(500, 'Error analyzing template: ' . $e->getMessage());
        }
    }

    private function isValidTemplateFile($file): bool
    {
        return $file && $file->isValid() && $file->getClientExtension() === 'docx';
    }

    private function saveTemporaryFile($file): string
    {
        $tempDir = WRITEPATH . 'uploads/temp/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFileName = uniqid('template_') . '.docx';
        $tempFilePath = $tempDir . $tempFileName;
        
        if (!$file->move($tempDir, $tempFileName)) {
            throw new \Exception('Failed to save uploaded file');
        }

        return $tempFilePath;
    }

    protected function extractTemplateFields($filePath)
    {
        try {
            $templateProcessor = new TemplateProcessor($filePath);
            return $templateProcessor->getVariables();
        } catch (\Exception $e) {
            log_message('error', 'Field extraction error: ' . $e->getMessage());
            return [];
        }
    }

    public function finalizeTemplateUpload()
    {
        if (!$this->checkPermission('templates.create')) {
            return $this->jsonError(403, 'You do not have permission to create templates.');
        }

        if (!$this->validateAjaxRequest()) {
            return $this->jsonError(400, 'Invalid request');
        }

        $json = $this->request->getJSON(true);
        
        if (!$this->validateTemplateUploadData($json)) {
            return $this->jsonError(400, 'Missing required data');
        }

        try {
            $result = $this->processTemplateUpload($json);
            return $this->jsonSuccess('Template uploaded successfully', ['newTemplate' => $result]);

        } catch (\Exception $e) {
            log_message('error', 'Template finalization error: ' . $e->getMessage());
            $this->cleanupTempFile($json['tempFilePath'] ?? null);
            return $this->jsonError(500, 'Error saving template: ' . $e->getMessage());
        }
    }

    private function validateTemplateUploadData(?array $json): bool
    {
        return $json && isset($json['tempFilePath'], $json['templateName']);
    }

    private function processTemplateUpload(array $json): array
    {
        $tempFilePath = $json['tempFilePath'];
        $templateName = trim($json['templateName']);
        $templateFields = $json['templateFields'] ?? [];
        $originalFileName = $json['originalFileName'] ?? 'template.docx';

        if (!file_exists($tempFilePath)) {
            throw new \Exception('Temporary file not found');
        }

        $permanentFilePath = $this->moveToStorageDirectory($tempFilePath);
        
        $templateData = [
            'name' => $templateName,
            'originalFileName' => $originalFileName,
            'path' => $permanentFilePath,
            'templateFields' => json_encode($templateFields),
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        $templateId = $this->templateModel->insert($templateData);
        
        if (!$templateId) {
            if (file_exists($permanentFilePath)) {
                unlink($permanentFilePath);
            }
            throw new \Exception('Failed to save template to database');
        }

        $newTemplate = $this->templateModel->find($templateId);
        $newTemplate['templateFields'] = $this->parseJsonField($newTemplate['templateFields']);
        $newTemplate['filledFiles'] = [];

        return $newTemplate;
    }

    private function moveToStorageDirectory(string $tempFilePath): string
    {
        $storageDir = WRITEPATH . 'uploads/templates/';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        $permanentFileName = uniqid('template_') . '.docx';
        $permanentFilePath = $storageDir . $permanentFileName;

        if (!rename($tempFilePath, $permanentFilePath)) {
            throw new \Exception('Failed to save template file');
        }

        return $permanentFilePath;
    }

    private function cleanupTempFile(?string $tempFilePath): void
    {
        if ($tempFilePath && file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }
    }

    public function createFilledFile()
    {
        if (!$this->checkPermission('filled-files.create')) {
            return $this->jsonError(403, 'You do not have permission to create filled files.');
        }

        if (!$this->validateAjaxRequest()) {
            return $this->jsonError(405, 'Method Not Allowed');
        }

        $json = $this->request->getJSON();

        if (!$this->validateFilledFileData($json)) {
            return $this->jsonError(400, 'Invalid data received. template_id and name are required.');
        }

        try {
            $newFilledFile = $this->processFilledFileCreation($json);
            return $this->jsonSuccess('File created successfully.', ['newFilledFile' => $newFilledFile]);

        } catch (\Exception $e) {
            log_message('error', '[Controller Exception] ' . $e->getMessage());
            return $this->jsonError(500, 'An unexpected error occurred: ' . $e->getMessage());
        }
    }

    private function validateFilledFileData($json): bool
    {
        return $json && isset($json->template_id, $json->name) && !empty(trim($json->name));
    }

    private function processFilledFileCreation($json): array
    {
        // Check for duplicate name
        if ($this->filledFileModel->where('templateFileId', $json->template_id)            ->where('name', trim($json->name))            ->first()
        ) {
            throw new \Exception('A file with this name already exists for this template');
        }

        $template = $this->templateModel->find($json->template_id);
        if (!$template) {
            throw new \Exception('Template not found');
        }

        $templateFields = $this->parseJsonField($template['templateFields']);
        $initialData = [];
        $defaultFieldTypes = [];
        
        foreach ($templateFields as $field) {
            $fieldName = $this->extractFieldName($field);
            if (!empty($fieldName)) {
                $initialData[$fieldName] = '';
                $defaultFieldTypes[$fieldName] = 'text';
            }
        }

        $dataToInsert = [
            'name' => trim($json->name),
            'templateFileId' => $json->template_id,
            'filledData' => json_encode($initialData),
            'fieldTypes' => json_encode($defaultFieldTypes),
            'createdAt' => date('Y-m-d H:i:s'),
        ];

        $newFileId = $this->filledFileModel->insert($dataToInsert);
        if ($newFileId === false) {
            throw new \Exception('Could not create file in database');
        }

        return $this->filledFileModel->find($newFileId);
    }

    private function extractFieldName($field): string
    {
        if (is_string($field)) {
            return $field;
        }
        
        if (is_array($field)) {
            return $field['name'] ?? $field['field'] ?? '';
        }
        
        return '';
    }

    public function deleteTemplate($id = null)
    {
        if (!$this->checkPermission('templates.delete')) {
            return $this->jsonError(403, 'You do not have permission to delete templates.');
        }

        if (!$this->validateAjaxRequest()) {
            return $this->jsonError(405, 'Method Not Allowed');
        }

        if (is_null($id)) {
            return $this->jsonError(400, 'Template ID is required.');
        }

        try {
            $this->deleteTemplateWithFiles($id);
            return $this->jsonSuccess('Template and associated filled files deleted successfully.');
        } catch (\Exception $e) {
            log_message('error', 'Error deleting template: ' . $e->getMessage());
            return $this->jsonError(500, 'An error occurred while deleting the template.');
        }
    }

    private function deleteTemplateWithFiles(int $templateId): void
    {
        $this->filledFileModel->where('templateFileId', $templateId)->delete();

        $template = $this->templateModel->find($templateId);
        if ($template && !empty($template['path']) && file_exists($template['path'])) {
            unlink($template['path']);
        }

        $success = $this->templateModel->delete($templateId);
        if (!$success) {
            throw new \Exception('Failed to delete template');
        }
    }

    public function deleteFilledFile($id = null)
    {
        if (!$this->checkPermission('filled-files.delete')) {
            return $this->jsonError(403, 'You do not have permission to delete filled files.');
        }

        if (!$this->validateAjaxRequest()) {
            return $this->jsonError(405, 'Method Not Allowed');
        }

        if (empty($id)) {
            return $this->jsonError(400, 'Filled file ID is required.');
        }

        try {
            $success = $this->filledFileModel->delete($id);
            
            if ($success) {
                return $this->jsonSuccess('Filled file deleted successfully.');
            } else {
                return $this->jsonError(500, 'Failed to delete filled file.');
            }
        } catch (\Exception $e) {
            log_message('error', 'Error deleting filled file: ' . $e->getMessage());
            return $this->jsonError(500, 'An error occurred while deleting the filled file.');
        }
    }

    public function exportDocx($id = null)
    {
        if (!auth()->user()->can('filled-files.view')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to export filled files.']);
        }

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'File ID is required.']);
        }

        try {
            $filledFile = $this->filledFileModel->find($id);
            if (!$filledFile) {
                throw new \Exception('Filled file not found');
            }

            if (!isset($filledFile['templateFileId'])) {
                throw new \Exception('Template file ID not found in filled file record');
            }

            $template = $this->templateModel->find($filledFile['templateFileId']);
            if (!$template) {
                throw new \Exception('Template not found');
            }

            if (!file_exists($template['path'])) {
                throw new \Exception('Template file not found');
            }

            $templateProcessor = new TemplateProcessor($template['path']);

            $filledData = is_string($filledFile['filledData']) ?
                json_decode($filledFile['filledData'], true) :
                $filledFile['filledData'];
            
            $fieldTypes = [];
            if (!empty($filledFile['fieldTypes'])) {
                $fieldTypes = is_string($filledFile['fieldTypes']) ? 
                    json_decode($filledFile['fieldTypes'], true) : 
                    $filledFile['fieldTypes'];
            }
            
            $imageSizes = [];
            if (!empty($filledFile['imageSizes'])) {
                $imageSizes = is_string($filledFile['imageSizes']) ? 
                    json_decode($filledFile['imageSizes'], true) : 
                    $filledFile['imageSizes'];
            }
            
            foreach ($filledData as $placeholder => $value) {
                $fieldType = $fieldTypes[$placeholder] ?? 'text';

                if ($fieldType === 'image' && !empty($value)) {
                    $imagePath = WRITEPATH . 'uploads/images/filled_files/' . $filledFile['id'] . '/' . $value;
                    if (file_exists($imagePath)) {
                        $width = $imageSizes[$placeholder]['width'] ?? 200;
                        $height = $imageSizes[$placeholder]['height'] ?? 200;
                        $templateProcessor->setImageValue(
                            $placeholder, [
                            'path' => $imagePath,
                            'width' => $width,
                            'height' => $height,
                            'ratio' => false
                            ]
                        );
                    } else {
                        $templateProcessor->setValue($placeholder, '[Image not found]');
                    }
                } else {
                    $templateProcessor->setValue($placeholder, $value ?: '');
                }
            }

            $outputFileName = $filledFile['name'] . '.docx';
            
            $tempOutputPath = tempnam(sys_get_temp_dir(), 'export_') . '.docx';
            $templateProcessor->saveAs($tempOutputPath);

            $this->response->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $outputFileName . '"');
            $this->response->setHeader('Content-Length', filesize($tempOutputPath));

            $fileContent = file_get_contents($tempOutputPath);
            
            unlink($tempOutputPath);

            return $this->response->setBody($fileContent);

        } catch (\Exception $e) {
            log_message('error', 'DOCX export error: ' . $e->getMessage());
            return $this->response->setJSON(
                [
                'success' => false,
                'message' => 'Error exporting DOCX: ' . $e->getMessage()
                ]
            );
        }
    }

    public function exportPdf($id = null)
    {
        if (!auth()->user()->can('filled-files.view')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to export filled files.']);
        }

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'File ID is required.']);
        }

        try {
            $filledFile = $this->filledFileModel->find($id);
            if (!$filledFile) {
                throw new \Exception('Filled file not found');
            }

            if (!isset($filledFile['templateFileId'])) {
                throw new \Exception('Template file ID not found in filled file record');
            }

            $template = $this->templateModel->find($filledFile['templateFileId']);
            if (!$template) {
                throw new \Exception('Template not found');
            }

            if (!file_exists($template['path'])) {
                throw new \Exception('Template file not found');
            }

            $templateProcessor = new TemplateProcessor($template['path']);

            $filledData = is_string($filledFile['filledData']) ?
                json_decode($filledFile['filledData'], true) :
                $filledFile['filledData'];
            
            $fieldTypes = [];
            if (!empty($filledFile['fieldTypes'])) {
                $fieldTypes = is_string($filledFile['fieldTypes']) ? 
                    json_decode($filledFile['fieldTypes'], true) : 
                    $filledFile['fieldTypes'];
            }
            
            $imageSizes = [];
            if (!empty($filledFile['imageSizes'])) {
                $imageSizes = is_string($filledFile['imageSizes']) ? 
                    json_decode($filledFile['imageSizes'], true) : 
                    $filledFile['imageSizes'];
            }
            
            foreach ($filledData as $placeholder => $value) {
                $fieldType = $fieldTypes[$placeholder] ?? 'text';

                if ($fieldType === 'image' && !empty($value)) {
                    $imagePath = WRITEPATH . 'uploads/images/filled_files/' . $filledFile['id'] . '/' . $value;
                    if (file_exists($imagePath)) {
                        $width = $imageSizes[$placeholder]['width'] ?? 200;
                        $height = $imageSizes[$placeholder]['height'] ?? 200;
                        $templateProcessor->setImageValue(
                            $placeholder, [
                            'path' => $imagePath,
                            'width' => $width,
                            'height' => $height,
                            'ratio' => false
                            ]
                        );
                    } else {
                        $templateProcessor->setValue($placeholder, '[Image not found]');
                    }
                } else {
                    $templateProcessor->setValue($placeholder, $value ?: '');
                }
            }

            $tempDocxPath = tempnam(sys_get_temp_dir(), 'export_') . '.docx';
            $templateProcessor->saveAs($tempDocxPath);

            $tempPdfPath = $this->convertDocxToPdf($tempDocxPath);
            
            if (!$tempPdfPath || !file_exists($tempPdfPath)) {
                throw new \Exception('PDF conversion failed. Please ensure LibreOffice is installed.');
            }

            $outputFileName = $filledFile['name'] . '.pdf';

            $this->response->setHeader('Content-Type', 'application/pdf');
            $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $outputFileName . '"');
            $this->response->setHeader('Content-Length', filesize($tempPdfPath));

            $fileContent = file_get_contents($tempPdfPath);
            
            unlink($tempDocxPath);
            unlink($tempPdfPath);

            return $this->response->setBody($fileContent);

        } catch (\Exception $e) {
            log_message('error', 'PDF export error: ' . $e->getMessage());
            return $this->response->setJSON(
                [
                'success' => false,
                'message' => 'Error exporting PDF: ' . $e->getMessage()
                ]
            );
        }
    }

    protected function convertDocxToPdf($docxPath)
    {
        try {
            $outputDir = dirname($docxPath);
            $pdfPath = str_replace('.docx', '.pdf', $docxPath);
            
            // Use LibreOffice to convert DOCX to PDF
            $command = "soffice --headless --convert-to pdf --outdir " . escapeshellarg($outputDir) . " " . escapeshellarg($docxPath) . " 2>/dev/null";
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($pdfPath)) {
                return $pdfPath;
            }
            
            return false;

        } catch (\Exception $e) {
            log_message('error', 'PDF conversion error: ' . $e->getMessage());
            return false;
        }
    }

    public function uploadTemplateWizard()
    {
        if (!auth()->user()->can('templates.create')) {
            return redirect()->to('/')->with('error', 'You do not have permission to create templates.');
        }

        return redirect()->to('/file-explorer#upload-template');
    }
    
    public function createFilledFileWizard()
    {
        if (!auth()->user()->can('filled-files.create')) {
            return redirect()->to('/')->with('error', 'You do not have permission to create filled files.');
        }

        $data = [
            'templates' => $this->templateModel->findAll(),
            'userPermissions' => $this->getUserPermissions()
        ];

        return view('create_filled_file_wizard', $data);
    }

    public function analyzeTemplateFile()
    {
        if (!auth()->user()->can('templates.create')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to create templates.']);
        }

        if ($this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        $validationRule = [
            'templateFile' => [
                'label' => 'Template File',
                'rules' => [
                    'uploaded[templateFile]',
                    'mime_in[templateFile,application/vnd.openxmlformats-officedocument.wordprocessingml.document]',
                    'max_size[templateFile,20480]', // Max 20MB
                ],
            ],
        ];

        if (!$this->validate($validationRule)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Validation failed', 'errors' => $this->validator->getErrors()]);
        }

        $file = $this->request->getFile('templateFile');

        if (!$file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'File error: ' . $file->getErrorString() . ' (' . $file->getError() . ')']);
        }

        try {
            $tempUploadPath = WRITEPATH . 'uploads/temp/';
            if (!is_dir($tempUploadPath)) {
                mkdir($tempUploadPath, 0777, true);
            }

            $newName = uniqid('template_') . '.docx';
            $file->move($tempUploadPath, $newName);
            $tempFilePath = $tempUploadPath . $newName;

            $templateFields = $this->extractTemplateFields($tempFilePath);

            return $this->response->setJSON(
                [
                'success' => true,
                'message' => 'File analyzed successfully.',
                'tempFilePath' => $tempFilePath,
                'originalFileName' => $file->getClientName(),
                'templateFields' => $templateFields
                ]
            );

        } catch (\Exception $e) {
            log_message('error', 'Template analysis error: ' . $e->getMessage());
            return $this->response->setJSON(
                [
                'success' => false,
                'message' => 'Error analyzing template: ' . $e->getMessage()
                ]
            );
        }
    }

    public function updateFilledFile($id = null)
    {
        if (!$this->checkPermission('filled-files.edit')) {
            return $this->jsonError(403, 'You do not have permission to edit filled files.');
        }

        if (!$this->validateAjaxRequest()) {
            return $this->jsonError(405, 'Method Not Allowed');
        }

        if (!$id) {
            return $this->jsonError(400, 'File ID is required.');
        }

        try {
            $filledFile = $this->filledFileModel->find($id);
            if (!$filledFile) {
                return $this->jsonError(404, 'Filled file not found.');
            }

            $result = $this->processFilledFileUpdate($id, $filledFile);
            return $this->jsonSuccess('File updated successfully', $result);

        } catch (\Exception $e) {
            log_message('error', 'Error updating filled file: ' . $e->getMessage());
            return $this->jsonError(500, 'Error updating file: ' . $e->getMessage());
        }
    }

    private function processFilledFileUpdate(int $id, array $filledFile): array
    {
        $filledData = json_decode($this->request->getPost('filledData'), true) ?: [];
        $fieldTypes = json_decode($this->request->getPost('fieldTypes'), true) ?: [];
        $imageSizes = json_decode($this->request->getPost('imageSizes'), true) ?: [];
        $fieldsWithNewImages = json_decode($this->request->getPost('fieldsWithNewImages'), true) ?: [];

        // Handle image uploads
        foreach ($fieldsWithNewImages as $fieldName) {
            $imageFile = $this->request->getFile("image_{$fieldName}");
            if ($imageFile && $imageFile->isValid()) {
                $imagePath = $this->saveFieldImage($id, $fieldName, $imageFile);
                $filledData[$fieldName] = basename($imagePath);
            }
        }

        $updateData = [
            'filledData' => json_encode($filledData),
            'fieldTypes' => json_encode($fieldTypes),
            'imageSizes' => json_encode($imageSizes),
            'updatedAt' => date('Y-m-d H:i:s')
        ];

        $success = $this->filledFileModel->update($id, $updateData);
        if (!$success) {
            throw new \Exception('Failed to update file in database');
        }

        return [
            'updatedData' => $filledData,
            'fieldTypes' => $fieldTypes
        ];
    }

    private function saveFieldImage(int $filledFileId, string $fieldName, $imageFile): string
    {
        if (!$imageFile->isValid()) {
            throw new \Exception('Invalid image file for field: ' . $fieldName);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imageFile->getMimeType(), $allowedTypes)) {
            throw new \Exception('Invalid image type. Only JPEG, PNG, GIF, and WebP are allowed.');
        }

        $uploadDir = WRITEPATH . 'uploads/images/filled_files/' . $filledFileId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = $imageFile->getClientExtension();
        $fileName = $fieldName . '_' . uniqid() . '.' . $extension;
        $fullPath = $uploadDir . $fileName;

        if (!$imageFile->move($uploadDir, $fileName)) {
            throw new \Exception('Failed to save image for field: ' . $fieldName);
        }

        return $fullPath;
    }

    public function serveImage($filledFileId = null, $imageName = null)
    {
        if (!auth()->user()->can('filled-files.view')) {
            return $this->response->setStatusCode(403);
        }

        if (!$filledFileId || !$imageName) {
            return $this->response->setStatusCode(404);
        }

        try {
            $filledFile = $this->filledFileModel->find($filledFileId);
            if (!$filledFile) {
                return $this->response->setStatusCode(404);
            }

            $imageName = basename($imageName);
            $imagePath = WRITEPATH . 'uploads/images/filled_files/' . $filledFileId . '/' . $imageName;

            if (!file_exists($imagePath) || !is_file($imagePath)) {
                return $this->response->setStatusCode(404);
            }

            $filledData = is_string($filledFile['filledData']) ? 
                json_decode($filledFile['filledData'], true) : 
                $filledFile['filledData'];
            
            $imageFound = false;
            if (is_array($filledData)) {
                foreach ($filledData as $value) {
                    if (is_string($value) && basename($value) === $imageName) {
                        $imageFound = true;
                        break;
                    }
                }
            }

            if (!$imageFound) {
                return $this->response->setStatusCode(403);
            }

            $mimeType = mime_content_type($imagePath);
            if (!$mimeType || strpos($mimeType, 'image/') !== 0) {
                return $this->response->setStatusCode(400);
            }

            $this->response->setHeader('Content-Type', $mimeType);
            $this->response->setHeader('Content-Length', filesize($imagePath));
            $this->response->setHeader('Cache-Control', 'private, max-age=3600');
            
            return $this->response->setBody(file_get_contents($imagePath));

        } catch (\Exception $e) {
            log_message('error', 'Error serving image: ' . $e->getMessage());
            return $this->response->setStatusCode(500);
        }
    }
}
