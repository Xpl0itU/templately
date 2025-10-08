<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>Dashboard<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include(
        'components/navigation', [
        'pageTitle' => 'Templately',
        'pageIcon' => 'file-alt',
        'stickyNav' => false,
        'showWelcome' => true
        ]
    ) ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('content') ?>
<div class="space-y-8">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Templates</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $totalTemplates ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-edit text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Filled Files</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $totalFilledFiles ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-shield text-indigo-600 text-xl"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Your Role</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1 capitalize">
                        <?php echo esc($user->getGroups()[0] ?? 'User') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                <i class="fas fa-rocket mr-2 text-indigo-500"></i>Quick Actions
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php if ($userPermissions['canViewTemplates']) : ?>
                <a href="/file-explorer" class="group flex flex-col items-center p-6 bg-gray-50 rounded-lg hover:bg-blue-50 hover:border-blue-200 border-2 border-transparent transition-all duration-200">
                    <div class="w-14 h-14 bg-white rounded-lg flex items-center justify-center mb-3 shadow-sm group-hover:shadow-md transition-shadow">
                        <i class="fas fa-folder-open text-gray-600 text-2xl group-hover:text-blue-600 transition-colors"></i>
                    </div>
                    <div class="font-medium text-gray-900 group-hover:text-blue-700 transition-colors">Browse Files</div>
                    <div class="text-sm text-gray-600 text-center mt-1">View all templates and files</div>
                </a>
                <?php endif; ?>
                
                <?php if ($userPermissions['canCreateTemplates']) : ?>
                <a href="/file-explorer/upload-template" class="group flex flex-col items-center p-6 bg-gray-50 rounded-lg hover:bg-blue-50 hover:border-blue-200 border-2 border-transparent transition-all duration-200">
                    <div class="w-14 h-14 bg-white rounded-lg flex items-center justify-center mb-3 shadow-sm group-hover:shadow-md transition-shadow">
                        <i class="fas fa-upload text-gray-600 text-2xl group-hover:text-blue-600 transition-colors"></i>
                    </div>
                    <div class="font-medium text-gray-900 group-hover:text-blue-700 transition-colors">Upload Template</div>
                    <div class="text-sm text-gray-600 text-center mt-1">Add a new template file</div>
                </a>
                <?php endif; ?>
                
                <?php if ($userPermissions['canCreateFilledFiles']) : ?>
                <a href="/file-explorer/create-filled-file" class="group flex flex-col items-center p-6 bg-gray-50 rounded-lg hover:bg-green-50 hover:border-green-200 border-2 border-transparent transition-all duration-200">
                    <div class="w-14 h-14 bg-white rounded-lg flex items-center justify-center mb-3 shadow-sm group-hover:shadow-md transition-shadow">
                        <i class="fas fa-edit text-gray-600 text-2xl group-hover:text-green-600 transition-colors"></i>
                    </div>
                    <div class="font-medium text-gray-900 group-hover:text-green-700 transition-colors">Create Filled File</div>
                    <div class="text-sm text-gray-600 text-center mt-1">Fill out a template</div>
                </a>
                <?php endif; ?>
                
                <?php if ($user->inGroup('superadmin', 'admin')) : ?>
                <a href="/user-management" class="group flex flex-col items-center p-6 bg-gray-50 rounded-lg hover:bg-indigo-50 hover:border-indigo-200 border-2 border-transparent transition-all duration-200">
                    <div class="w-14 h-14 bg-white rounded-lg flex items-center justify-center mb-3 shadow-sm group-hover:shadow-md transition-shadow">
                        <i class="fas fa-users-cog text-gray-600 text-2xl group-hover:text-indigo-600 transition-colors"></i>
                    </div>
                    <div class="font-medium text-gray-900 group-hover:text-indigo-700 transition-colors">Manage Users</div>
                    <div class="text-sm text-gray-600 text-center mt-1">User administration</div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Items -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Templates -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-clock mr-2 text-blue-500"></i>Recent Templates
                </h3>
            </div>
            <div class="p-6">
                <?php if (!empty($recentTemplates)) : ?>
                    <div class="space-y-3">
                        <?php foreach ($recentTemplates as $template): ?>
                            <a href="/file-explorer#template-<?php echo $template['id'] ?>" 
                               class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-blue-50 hover:border-blue-200 border-2 border-transparent transition-all duration-200 group">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                                        <i class="fas fa-file-alt text-blue-600"></i>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 group-hover:text-blue-700 truncate transition-colors">
                                        <?php echo esc($template['name']) ?>
                                    </p>
                                    <p class="text-sm text-gray-600 mt-0.5">
                                        Created: <?php echo date('M j, Y', strtotime($template['createdAt'])) ?>
                                    </p>
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-blue-500 transition-colors"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-file-alt text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-gray-500 font-medium">No templates available</p>
                        <?php if ($userPermissions['canCreateTemplates']) : ?>
                            <a href="/file-explorer/upload-template" class="inline-block mt-3 text-blue-600 hover:text-blue-700 font-medium">
                                Upload your first template →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Filled Files -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-history mr-2 text-green-500"></i>Recent Filled Files
                </h3>
            </div>
            <div class="p-6">
                <?php if (!empty($recentFilledFiles)) : ?>
                    <div class="space-y-3">
                        <?php foreach ($recentFilledFiles as $file): ?>
                            <a href="/file-explorer#filled-file-<?php echo $file['id'] ?? '' ?>" 
                               class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-green-50 hover:border-green-200 border-2 border-transparent transition-all duration-200 group">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                                        <i class="fas fa-edit text-green-600"></i>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 group-hover:text-green-700 truncate transition-colors">
                                        <?php echo esc($file['name'] ?? 'Unnamed File') ?>
                                    </p>
                                    <p class="text-sm text-gray-600 mt-0.5 truncate">
                                        Template: <?php echo esc($file['template_name'] ?? 'Unknown Template') ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Created: <?php echo isset($file['createdAt']) && $file['createdAt'] ? date('M j, Y', strtotime($file['createdAt'])) : 'Unknown date' ?>
                                    </p>
                                </div>
                                <div class="ml-4 flex-shrink-0">
                                    <i class="fas fa-arrow-right text-gray-400 group-hover:text-green-500 transition-colors"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-edit text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-gray-500 font-medium">No filled files available</p>
                        <?php if ($userPermissions['canCreateFilledFiles']) : ?>
                            <a href="/file-explorer/create-filled-file" class="inline-block mt-3 text-green-600 hover:text-green-700 font-medium">
                                Create your first filled file →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php echo $this->endSection() ?>