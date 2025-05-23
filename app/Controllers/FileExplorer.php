<?php

namespace App\Controllers;

use App\Models\FilledFilesModel;

class FileExplorer extends BaseController
{
    public function index(): string
    {
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
        ];
        return view('file_explorer', $data);
    }

    public function updateFilledFile($id = null)
    {
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

        $newFilledData = new \stdClass(); // Start with an empty object
        if (isset($template['templateFields']) && is_array($template['templateFields'])) {
            foreach ($template['templateFields'] as $field) {
                $newFilledData->$field = ''; // Initialize each field with an empty string
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
}
