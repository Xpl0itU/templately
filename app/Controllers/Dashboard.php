<?php

namespace App\Controllers;

/**
 * Dashboard Controller
 * 
 * Handles the main dashboard display with statistics and recent activity.
 */
class Dashboard extends BaseController
{
    /**
     * Display the dashboard with user statistics and recent items
     *
     * @return mixed View or redirect to login
     */
    public function index()
    {
        if (!auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        $user = auth()->user();
        
        $templateModel = model('App\Models\TemplateModel');
        $filledFilesModel = model('App\Models\FilledFilesModel');
        
        $totalTemplates = $templateModel->countAll();
        $totalFilledFiles = $filledFilesModel->countAll();
        
        $recentTemplates = $templateModel->orderBy('createdAt', 'DESC')->findAll(5);
        
        $recentFilledFiles = $filledFilesModel->select('filledFiles.*, templateFiles.name as template_name')
            ->join('templateFiles', 'templateFiles.id = filledFiles.templateFileId', 'left')
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
