<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index()
    {
        // Check if user is logged in
        if (!auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        $user = auth()->user();
        
        // Get user statistics
        $templateModel = model('App\Models\TemplateFilesModel');
        $filledFilesModel = model('App\Models\FilledFilesModel');
        
        $totalTemplates = $templateModel->countAll();
        $totalFilledFiles = $filledFilesModel->countAll();
        
        // Recent templates (limit 5)
        $recentTemplates = $templateModel->orderBy('createdAt', 'DESC')->findAll(5);
        
        // Recent filled files (limit 5)
        $recentFilledFiles = $filledFilesModel->select('filledFiles.*, templateFiles.name as template_name')
            ->join('templateFiles', 'templateFiles.id = filledFiles.templateFileId')
            ->orderBy('filledFiles.createdAt', 'DESC')
            ->findAll(5);
        
        $data = [
            'user' => $user,
            'totalTemplates' => $totalTemplates,
            'totalFilledFiles' => $totalFilledFiles,
            'recentTemplates' => $recentTemplates,
            'recentFilledFiles' => $recentFilledFiles,
            'userPermissions' => [
                'canViewTemplates' => $user->can('templates.view'),
                'canCreateTemplates' => $user->can('templates.create'),
                'canEditTemplates' => $user->can('templates.edit'),
                'canDeleteTemplates' => $user->can('templates.delete'),
                'canViewFilledFiles' => $user->can('filled-files.view'),
                'canCreateFilledFiles' => $user->can('filled-files.create'),
                'canEditFilledFiles' => $user->can('filled-files.edit'),
                'canDeleteFilledFiles' => $user->can('filled-files.delete'),
            ]
        ];
        
        return view('dashboard', $data);
    }
}
