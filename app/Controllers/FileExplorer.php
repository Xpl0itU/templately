<?php

namespace App\Controllers;

class FileExplorer extends BaseController
{
    public function index(): string
    {
        $templateModel = model('App\Models\TemplateFilesModel');
        $templates = $templateModel->findAll();

        $filledFilesModel = model('App\Models\FilledFilesModel');
        $filledFiles = $filledFilesModel->findAll();

        // Join the filled files with the templates
        foreach ($templates as &$template) {
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
}
