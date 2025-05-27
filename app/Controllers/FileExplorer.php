<?php

namespace App\Controllers;

use App\Models\FilledFilesModel;

class FileExplorer extends BaseController
{
    public function index()
    {
        if (!auth()->user()->can('templates.view')) {
            return redirect()->to('/')->with('error', 'You do not have permission to view templates.');
        }

        $templateModel = model('App\Models\TemplateFilesModel');
        $templates = $templateModel->findAll();

        $filledFilesModel = model('App\\Models\\FilledFilesModel');
        $filledFiles = $filledFilesModel->findAll();

        // Join the filled files with the templates
        foreach ($templates as &$template) {
            if (!empty($template['templateFields']) && is_string($template['templateFields'])) {
                $decodedFields = json_decode($template['templateFields'], true);
                $template['templateFields'] = is_array($decodedFields) ? $decodedFields : [];
            } elseif (empty($template['templateFields'])) {
                $template['templateFields'] = [];
            }

            $template['filledFiles'] = [];
            foreach ($filledFiles as $file) {
                if ($file['templateFileId'] == $template['id']) {
                    $template['filledFiles'][] = $file;
                }
            }
        }

        $data = [
            'templates' => $templates,
            'userPermissions' => [
                'canViewTemplates' => auth()->user()->can('templates.view'),
                'canCreateTemplates' => auth()->user()->can('templates.create'),
                'canEditTemplates' => auth()->user()->can('templates.edit'),
                'canDeleteTemplates' => auth()->user()->can('templates.delete'),
                'canViewFilledFiles' => auth()->user()->can('filled-files.view'),
                'canCreateFilledFiles' => auth()->user()->can('filled-files.create'),
                'canEditFilledFiles' => auth()->user()->can('filled-files.edit'),
                'canDeleteFilledFiles' => auth()->user()->can('filled-files.delete'),
            ]
        ];
        return view('file_explorer', $data);
    }

    public function updateFilledFile($id = null)
    {
        if (!auth()->user()->can('filled-files.edit')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to edit filled files.']);
        }

        if (!$this->request->isAJAX() || $this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        $json = $this->request->getJSON();

        if (empty($id) || empty($json) || !isset($json->filledData) || !is_object($json->filledData)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid data received. ID and filledData object are required.']);
        }

        $filledFilesModel = new FilledFilesModel();
        $file = $filledFilesModel->find($id);

        if (!$file) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'File not found.']);
        }

        $dataToUpdate = [
            'filledData' => json_encode($json->filledData),
        ];

        try {
            if ($filledFilesModel->update($id, $dataToUpdate)) {
                return $this->response->setJSON(['success' => true, 'message' => 'File updated successfully.']);
            } else {
                log_message('error', 'Failed to update file ID: ' . $id . ' Errors: ' . print_r($filledFilesModel->errors(), true));
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Could not update file in database.', 'errors' => $filledFilesModel->errors()]);
            }
        } catch (\Exception $e) {
            log_message('error', '[Controller Exception] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An unexpected error occurred on the server.']);
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

        $templateModel = model('App\\Models\\TemplateFilesModel');
        $template = $templateModel->find($json->template_id);

        if (!$template) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Template not found.']);
        }

        // Ensure templateFields is an array, decoding if it's a JSON string
        if (!empty($template['templateFields']) && is_string($template['templateFields'])) {
            $decodedFields = json_decode($template['templateFields'], true);
            $template['templateFields'] = is_array($decodedFields) ? $decodedFields : [];
        } elseif (empty($template['templateFields'])) {
            $template['templateFields'] = []; // Default to empty array
        }

        $filledFilesModel = new FilledFilesModel();

        $newFilledData = new \stdClass();
        if (isset($template['templateFields']) && is_array($template['templateFields'])) {
            foreach ($template['templateFields'] as $field) {
                $newFilledData->$field = '';
            }
        }

        $dataToInsert = [
            'name' => trim($json->name),
            'templateFileId' => $json->template_id,
            'filledData' => json_encode($newFilledData),
            'createdAt' => date('Y-m-d H:i:s'),
        ];

        try {
            $newFileId = $filledFilesModel->insert($dataToInsert);
            if ($newFileId === false) {
                log_message('error', 'Failed to insert new filled file. Errors: ' . print_r($filledFilesModel->errors(), true));
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Could not create file in database.', 'errors' => $filledFilesModel->errors()]);
            }

            $newFilledFile = $filledFilesModel->find($newFileId);
            if ($newFilledFile) {
                $newFilledFile['filledData'] = json_decode($newFilledFile['filledData'] ?? '{}');
                return $this->response->setJSON(['success' => true, 'message' => 'File created successfully.', 'newFilledFile' => $newFilledFile]);
            } else {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'File created but could not be retrieved.']);
            }
        } catch (\Exception $e) {
            log_message('error', '[Controller Exception] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An unexpected error occurred on the server.']);
        }
    }

    // TODO: Implement
    private function extractTemplateFieldsFromFile(string $filePath): array
    {
        sleep(2);
        log_message('info', 'Mock extracting fields from: ' . $filePath);
        return ['firstName', 'lastName', 'date', 'productName'];
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
                    'mime_in[templateFile,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/pdf,text/plain]',
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

        $tempUploadPath = WRITEPATH . 'uploads/temp_templates';
        if (!is_dir($tempUploadPath)) {
            mkdir($tempUploadPath, 0777, true);
        }

        $newName = $file->getRandomName();
        $file->move($tempUploadPath, $newName);
        $tempFilePath = $tempUploadPath . '/' . $newName;

        $templateFields = $this->extractTemplateFieldsFromFile($tempFilePath);

        return $this->response->setJSON(
            [
            'success' => true,
            'message' => 'File analyzed successfully.',
            'tempFilePath' => $tempFilePath,
            'originalFileName' => $file->getClientName(),
            'templateFields' => $templateFields
            ]
        );
    }

    public function finalizeTemplateUpload()
    {
        if (!auth()->user()->can('templates.create')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'You do not have permission to create templates.']);
        }

        if ($this->request->getMethod(true) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['success' => false, 'message' => 'Method Not Allowed']);
        }

        $json = $this->request->getJSON();
        log_message('debug', 'Finalize Template Upload JSON payload: ' . print_r($json, true));

        if (empty($json) || !isset($json->tempFilePath) || !isset($json->templateName) || !isset($json->templateFields) || !isset($json->originalFileName) || !isset($json->fileMimeType) || !isset($json->fileSizeKB)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid data received for finalization. Missing one or more required fields.']);
        }
        
        $tempFilePath = $json->tempFilePath;
        $templateName = trim($json->templateName);
        $templateFields = $json->templateFields;
        $fileMimeType = $json->fileMimeType;
        $fileSizeKB = $json->fileSizeKB;

        if (empty($templateName)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Template name cannot be empty.']);
        }
        
        $realTempFilePath = realpath($tempFilePath);
        $expectedTempDir = realpath(WRITEPATH . 'uploads/temp_templates');

        if ($realTempFilePath === false) {
            log_message('error', "Finalize Error: tempFilePath '{$tempFilePath}' does not resolve to a real path or does not exist.");
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Temporary file path is invalid or file does not exist. Please try uploading again.']);
        }

        if ($expectedTempDir === false) {
            log_message('critical', "Finalize Error: Expected temporary directory " . WRITEPATH . "uploads/temp_templates' does not exist or is not accessible.");
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Server configuration error regarding temporary storage. Contact administrator.']);
        }

        if (strpos($realTempFilePath, $expectedTempDir) !== 0) {
            log_message('error', "Finalize Error: tempFilePath '{$realTempFilePath}' is outside the expected directory '{$expectedTempDir}'.");
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid temporary file path (security check failed).']);
        }

        if (!file_exists($realTempFilePath)) {
            log_message('error', "Finalize Error: Temporary file '{$realTempFilePath}' not found after realpath success. This could be a race condition or permissions issue.");
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Temporary file not found. It might have expired or been moved. Please try uploading again.']);
        }

        $uploadPath = WRITEPATH . 'uploads/templates';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $newFileNameOnServer = basename($realTempFilePath);
        $finalFilePath = $uploadPath . '/' . $newFileNameOnServer;

        if (!rename($realTempFilePath, $finalFilePath)) {
            if (file_exists($realTempFilePath)) {
                unlink($realTempFilePath);
            }
            log_message('error', "Finalize Error: Could not move temp file '{$realTempFilePath}' to '{$finalFilePath}'. Check permissions.");
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Could not move template file to final destination.']);
        }
        
        $templateModel = model('App\\Models\\TemplateFilesModel');
        $dataToInsert = [
            'name' => $templateName,
            'path' => $finalFilePath,
            'size' => $fileSizeKB, 
            'type' => $fileMimeType,
            'templateFields' => json_encode($templateFields),
            'createdAt' => date('Y-m-d H:i:s')
        ];

        try {
            $newTemplateId = $templateModel->insert($dataToInsert);
            if ($newTemplateId === false) {
                if (file_exists($finalFilePath)) {
                    unlink($finalFilePath);
                }
                log_message('error', 'Failed to insert new template file (finalize). Errors: ' . print_r($templateModel->errors(), true));
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Could not save template to database.', 'errors' => $templateModel->errors()]);
            }

            $newTemplate = $templateModel->find($newTemplateId);
            if ($newTemplate) {
                if (!empty($newTemplate['templateFields']) && is_string($newTemplate['templateFields'])) {
                    $decodedFields = json_decode($newTemplate['templateFields'], true);
                    $newTemplate['templateFields'] = is_array($decodedFields) ? $decodedFields : [];
                } else {
                    $newTemplate['templateFields'] = [];
                }
                $newTemplate['filledFiles'] = [];
                return $this->response->setJSON(['success' => true, 'message' => 'Template finalized and saved successfully.', 'newTemplate' => $newTemplate]);
            } else {
                if (file_exists($finalFilePath)) {
                    unlink($finalFilePath);
                }
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Template saved but could not be retrieved.']);
            }
        } catch (\Exception $e) {
            if (file_exists($finalFilePath)) {
                unlink($finalFilePath);
            }
            log_message('error', '[Controller Exception - FinalizeTemplate] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An unexpected server error occurred during finalization.']);
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

        if (empty($id)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Template ID is required.']);
        }

        $templateModel = model('App\\Models\\TemplateFilesModel');
        $template = $templateModel->find($id);

        if (!$template) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Template not found.']);
        }

        try {
            $filledFilesModel = new FilledFilesModel();
            $filledFilesModel->where('templateFileId', $id)->delete();

            if (!empty($template['path']) && file_exists($template['path'])) {
                unlink($template['path']);
            }

            $templateModel->delete($id);

            return $this->response->setJSON(['success' => true, 'message' => 'Template and associated filled files deleted successfully.']);
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

        $filledFilesModel = new FilledFilesModel();
        $file = $filledFilesModel->find($id);

        if (!$file) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Filled file not found.']);
        }

        try {
            $filledFilesModel->delete($id);
            return $this->response->setJSON(['success' => true, 'message' => 'Filled file deleted successfully.']);
        } catch (\Exception $e) {
            log_message('error', 'Error deleting filled file: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'An error occurred while deleting the filled file.']);
        }
    }
}
