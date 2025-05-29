<?php

namespace App\Controllers;

use PhpOffice\PhpWord\TemplateProcessor;

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
        if (!auth()->user()->can('templates.view')) {
            return redirect()->to('/')->with('error', 'You do not have permission to view templates.');
        }

        $templates = $this->templateModel->findAll();
        $filledFiles = $this->filledFileModel->findAll();

        // Join the filled files with templates
        foreach ($templates as &$template) {
            if (!empty($template['templateFields']) && is_string($template['templateFields'])) {
                $decodedFields = json_decode($template['templateFields'], true);
                $template['templateFields'] = is_array($decodedFields) ? $decodedFields : [];
            } elseif (empty($template['templateFields'])) {
                $template['templateFields'] = [];
            }

            $template['filledFiles'] = [];
            foreach ($filledFiles as $file) {
                if (isset($file['templateFileId']) && isset($template['id']) && $file['templateFileId'] == $template['id']) {
                    $file['id'] = $file['id'] ?? null;
                    $file['name'] = $file['name'] ?? 'Unnamed File';
                    $file['createdAt'] = $file['createdAt'] ?? null;
                    $file['updatedAt'] = $file['updatedAt'] ?? null;
                    
                    // Parse filledData if it's a JSON string
                    if (isset($file['filledData']) && is_string($file['filledData'])) {
                        $file['filledData'] = json_decode($file['filledData'], true) ?: [];
                    } else {
                        $file['filledData'] = $file['filledData'] ?? [];
                    }
                    $template['filledFiles'][] = $file;
                }
            }
        }

        $data = [
            'title' => 'File Explorer',
            'templates' => $templates,
            'userPermissions' => $this->getUserPermissions()
        ];

        return view('file_explorer', $data);
    }

    protected function getUserPermissions()
    {
        return [
            'canViewTemplates' => auth()->user()->can('templates.view'),
            'canCreateTemplates' => auth()->user()->can('templates.create'),
            'canEditTemplates' => auth()->user()->can('templates.edit'),
            'canDeleteTemplates' => auth()->user()->can('templates.delete'),
            'canViewFilledFiles' => auth()->user()->can('filled-files.view'),
            'canCreateFilledFiles' => auth()->user()->can('filled-files.create'),
            'canEditFilledFiles' => auth()->user()->can('filled-files.edit'),
            'canDeleteFilledFiles' => auth()->user()->can('filled-files.delete'),
            'canExportFilledFiles' => auth()->user()->can('filled-files.view'),
        ];
    }

    public function analyzeTemplate()
    {
        if (!auth()->user()->can('templates.create')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to create templates.']);
        }

        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $file = $this->request->getFile('templateFile');
        
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'message' => 'No valid file uploaded']);
        }

        if ($file->getClientExtension() !== 'docx') {
            return $this->response->setJSON(['success' => false, 'message' => 'Only .docx files are supported']);
        }

        try {
            $tempDir = WRITEPATH . 'uploads/temp/';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Generate unique filename
            $tempFileName = uniqid('template_') . '.docx';
            $tempFilePath = $tempDir . $tempFileName;
            
            if (!$file->move($tempDir, $tempFileName)) {
                throw new \Exception('Failed to save uploaded file');
            }

            $templateFields = $this->extractTemplateFields($tempFilePath);

            return $this->response->setJSON([
                'success' => true,
                'tempFilePath' => $tempFilePath,
                'templateFields' => $templateFields,
                'message' => 'Template analyzed successfully'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Template analysis error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error analyzing template: ' . $e->getMessage()
            ]);
        }
    }

    protected function extractTemplateFields($filePath)
    {
        try {
            $templateProcessor = new TemplateProcessor($filePath);
            
            $variables = $templateProcessor->getVariables();
            
            return $variables;
        } catch (\Exception $e) {
            log_message('error', 'Field extraction error: ' . $e->getMessage());
            return [];
        }
    }

    public function finalizeTemplateUpload()
    {
        if (!auth()->user()->can('templates.create')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to create templates.']);
        }

        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $json = $this->request->getJSON(true);
        
        if (!isset($json['tempFilePath']) || !isset($json['templateName'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Missing required data']);
        }

        try {
            $tempFilePath = $json['tempFilePath'];
            $templateName = trim($json['templateName']);
            $templateFields = $json['templateFields'] ?? [];
            $originalFileName = $json['originalFileName'] ?? 'template.docx';

            if (!file_exists($tempFilePath)) {
                throw new \Exception('Temporary file not found');
            }

            $storageDir = WRITEPATH . 'uploads/templates/';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $permanentFileName = uniqid('template_') . '.docx';
            $permanentFilePath = $storageDir . $permanentFileName;

            if (!rename($tempFilePath, $permanentFilePath)) {
                throw new \Exception('Failed to save template file');
            }

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
            $newTemplate['templateFields'] = is_array($newTemplate['templateFields']) ? $newTemplate['templateFields'] : json_decode($newTemplate['templateFields'], true);
            $newTemplate['filledFiles'] = [];

            return $this->response->setJSON([
                'success' => true,
                'newTemplate' => $newTemplate,
                'message' => 'Template uploaded successfully'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Template finalization error: ' . $e->getMessage());
            
            if (isset($tempFilePath) && file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error saving template: ' . $e->getMessage()
            ]);
        }
    }

    public function createFilledFile()
    {
        if (!auth()->user()->can('filled-files.create')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to create filled files.']);
        }

        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        $json = $this->request->getJSON();

        if (empty($json) || !isset($json->template_id) || !isset($json->name) || empty(trim($json->name))) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid data received. template_id and name are required.']);
        }

        try {
            if ($this->filledFileModel->where('templateFileId', $json->template_id)->where('name', trim($json->name))->first()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'A file with this name already exists for this template'
                ]);
            }

            $template = $this->templateModel->find($json->template_id);
            if (!$template) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Template not found.']);
            }

            $templateFields = [];
            if (!empty($template['templateFields'])) {
                $templateFields = is_string($template['templateFields']) ? 
                    json_decode($template['templateFields'], true) : 
                    $template['templateFields'];
            
                if (!is_array($templateFields)) {
                    $templateFields = [];
                }
            }

            $initialData = [];
            $defaultFieldTypes = [];
            foreach ($templateFields as $field) {
                $fieldName = '';
                if (is_string($field)) {
                    $fieldName = $field;
                } elseif (is_array($field) && isset($field['name'])) {
                    $fieldName = $field['name'];
                } elseif (is_array($field) && isset($field['field'])) {
                    $fieldName = $field['field'];
                }
                
                if (!empty($fieldName)) {
                    $initialData[$fieldName] = '';
                    $defaultFieldTypes[$fieldName] = 'text'; // Default all fields to text
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
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Could not create file in database.']);
            }

            $newFilledFile = $this->filledFileModel->find($newFileId);
            if ($newFilledFile) {
                return $this->response->setJSON(['success' => true, 'message' => 'File created successfully.', 'newFilledFile' => $newFilledFile]);
            }

        } catch (\Exception $e) {
            log_message('error', '[Controller Exception] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An unexpected error occurred on the server: ' . $e->getMessage()]);
        }
    }

    public function updateFilledFile($id = null)
    {
        if (!auth()->user()->can('filled-files.edit')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to edit filled files.']);
        }

        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        if (empty($id)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'File ID is required.']);
        }

        try {
            $existingFile = $this->filledFileModel->find($id);
            if (!$existingFile) {
                return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'File not found.']);
            }

            $filledDataJson = $this->request->getPost('filledData');
            $fieldTypesJson = $this->request->getPost('fieldTypes');
            $imageSizesJson = $this->request->getPost('imageSizes');
            $fieldsWithNewImagesJson = $this->request->getPost('fieldsWithNewImages');
            
            if (empty($filledDataJson)) {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid data received.']);
            }

            $filledData = json_decode($filledDataJson, true);
            $fieldTypes = $fieldTypesJson ? json_decode($fieldTypesJson, true) : [];
            $imageSizes = $imageSizesJson ? json_decode($imageSizesJson, true) : [];
            $fieldsWithNewImages = $fieldsWithNewImagesJson ? json_decode($fieldsWithNewImagesJson, true) : [];
            
            if (!is_array($filledData)) {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid filled data format.']);
            }

            log_message('info', 'Starting image upload handling for filled file ID: ' . $id);
            
            $uploadedFiles = $this->request->getFiles();
            log_message('info', 'getFiles() returned: ' . count($uploadedFiles) . ' files');
            
            if (!empty($uploadedFiles)) {
                log_message('info', 'File keys from getFiles(): ' . implode(', ', array_keys($uploadedFiles)));
                
                foreach ($uploadedFiles as $fieldName => $file) {
                    log_message('info', 'Processing file field: ' . $fieldName);
                    log_message('info', 'File valid: ' . ($file->isValid() ? 'yes' : 'no'));
                    log_message('info', 'File moved: ' . ($file->hasMoved() ? 'yes' : 'no'));
                    
                    if (strpos($fieldName, 'image_') === 0 && $file->isValid() && !$file->hasMoved()) {
                        $actualFieldName = substr($fieldName, 6); // Remove 'image_' prefix
                        log_message('info', 'Processing image for field: ' . $actualFieldName);

                        $mimeType = $file->getClientMimeType();
                        log_message('info', 'File MIME type: ' . $mimeType);
                        
                        if (!$mimeType || strpos($mimeType, 'image/') !== 0) {
                            log_message('warning', 'Skipping non-image file: ' . $mimeType);
                            continue; // Skip non-image files
                        }

                        $imageDir = WRITEPATH . 'uploads/images/filled_files/' . $id . '/';
                        if (!is_dir($imageDir)) {
                            if (!mkdir($imageDir, 0755, true)) {
                                log_message('error', 'Failed to create image directory: ' . $imageDir);
                                continue;
                            }
                            log_message('info', 'Created image directory: ' . $imageDir);
                        }

                        // Generate unique filename
                        $extension = $file->getClientExtension();
                        $newName = $actualFieldName . '_' . time() . '_' . uniqid() . '.' . $extension;
                        log_message('info', 'Generated filename: ' . $newName);

                        if ($file->move($imageDir, $newName)) {
                            log_message('info', 'Successfully moved file to: ' . $imageDir . $newName);
                            
                            $existingFilledData = is_string($existingFile['filledData']) ?
                                json_decode($existingFile['filledData'], true) :
                                $existingFile['filledData'];

                            if (!empty($existingFilledData[$actualFieldName])) {
                                $oldImagePath = $imageDir . basename($existingFilledData[$actualFieldName]);
                                if (file_exists($oldImagePath)) {
                                    @unlink($oldImagePath);
                                    log_message('info', 'Deleted old image: ' . $oldImagePath);
                                }
                            }

                            $filledData[$actualFieldName] = $newName;
                            log_message('info', 'Updated filledData for field: ' . $actualFieldName . ' = ' . $newName);
                        } else {
                            log_message('error', 'Failed to move file: ' . $file->getErrorString());
                        }
                    }
                }
            } else {
                log_message('warning', 'No files found in upload');
            }

            $updateData = [
                'filledData' => json_encode($filledData),
                'updatedAt' => date('Y-m-d H:i:s')
            ];
            
            if (!empty($fieldTypes)) {
                $updateData['fieldTypes'] = json_encode($fieldTypes);
            }
            
            if (!empty($imageSizes)) {
                $updateData['imageSizes'] = json_encode($imageSizes);
            }

            $success = $this->filledFileModel->update($id, $updateData);

            if ($success) {
                return $this->response->setJSON([
                    'success' => true, 
                    'message' => 'File updated successfully.',
                    'updatedData' => $filledData,
                    'fieldTypes' => $fieldTypes
                ]);
            } else {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Could not update file in database.']);
            }
        } catch (\Exception $e) {
            log_message('error', '[Controller Exception] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An unexpected error occurred on the server: ' . $e->getMessage()]);
        }
    }

    public function deleteTemplate($id = null)
    {
        if (!auth()->user()->can('templates.delete')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to delete templates.']);
        }

        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        if (is_null($id)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Template ID is required.']);
        }

        try {
            $this->filledFileModel->where('templateFileId', $id)->delete();

            $template = $this->templateModel->find($id);
            if ($template && !empty($template['path']) && file_exists($template['path'])) {
                unlink($template['path']);
            }

            $success = $this->templateModel->delete($id);

            if ($success) {
                return $this->response->setJSON(['success' => true, 'message' => 'Template and associated filled files deleted successfully.']);
            } else {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Failed to delete template.']);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error deleting template: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while deleting the template.']);
        }
    }

    public function deleteFilledFile($id = null)
    {
        if (!auth()->user()->can('filled-files.delete')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to delete filled files.']);
        }

        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        if (empty($id)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Filled file ID is required.']);
        }

        try {
            $success = $this->filledFileModel->delete($id);
            
            if ($success) {
                return $this->response->setJSON(['success' => true, 'message' => 'Filled file deleted successfully.']);
            } else {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Failed to delete filled file.']);
            }
        } catch (\Exception $e) {
            log_message('error', 'Error deleting filled file: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while deleting the filled file.']);
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
                    $imagePath = WRITEPATH . 'uploads/images/filled_files/' . $id . '/' . $value;
                    if (file_exists($imagePath)) {
                        try {
                            $imageSettings = $imageSizes[$placeholder] ?? [];
                            $width = $imageSettings['width'] ?? 300;
                            $height = $imageSettings['height'] ?? 200;
                            $ratio = $imageSettings['ratio'] ?? true;

                            $templateProcessor->setImageValue($placeholder, [
                                'path' => $imagePath,
                                'width' => $width,
                                'height' => $height,
                                'ratio' => $ratio
                            ]);
                        } catch (\Exception $e) {
                            $templateProcessor->setValue($placeholder, '[Image: ' . basename($value) . ']');
                        }
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
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error exporting DOCX: ' . $e->getMessage()
            ]);
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
                    $imagePath = WRITEPATH . 'uploads/images/filled_files/' . $id . '/' . $value;
                    if (file_exists($imagePath)) {
                        try {
                            $imageSettings = $imageSizes[$placeholder] ?? [];
                            $width = $imageSettings['width'] ?? 300;
                            $height = $imageSettings['height'] ?? 200;
                            $ratio = $imageSettings['ratio'] ?? true;

                            $templateProcessor->setImageValue($placeholder, [
                                'path' => $imagePath,
                                'width' => $width,
                                'height' => $height,
                                'ratio' => $ratio
                            ]);
                        } catch (\Exception $e) {
                            $templateProcessor->setValue($placeholder, '[Image: ' . basename($value) . ']');
                        }
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
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error exporting PDF: ' . $e->getMessage()
            ]);
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

            return $this->response->setJSON([
                'success' => true,
                'message' => 'File analyzed successfully.',
                'tempFilePath' => $tempFilePath,
                'originalFileName' => $file->getClientName(),
                'templateFields' => $templateFields
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Template analysis error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error analyzing template: ' . $e->getMessage()
            ]);
        }
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
                    if (is_string($value) && strpos($value, $imageName) !== false) {
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
