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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-file-alt text-blue-500 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Templates</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $totalTemplates ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-edit text-green-500 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Filled Files</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo $totalFilledFiles ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-indigo-500">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-shield text-indigo-500 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Your Role</p>
                    <p class="text-2xl font-bold text-gray-900 capitalize">
                        <?php echo esc($user->getGroups()[0] ?? 'User') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

        <div class="mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">
                <i class="fas fa-rocket mr-2 text-slate-500"></i>Quick Actions
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <?php if ($userPermissions['canViewTemplates']) : ?>
                <a href="/file-explorer" class="flex items-center p-6 bg-slate-50 rounded-lg hover:bg-slate-100 transition duration-200 text-center">
                    <div class="w-full">
                        <i class="fas fa-folder-open text-slate-500 text-3xl mb-3"></i>
                        <div class="font-medium text-gray-900 mb-1">Browse Files</div>
                        <div class="text-sm text-gray-600">View all templates and files</div>
                    </div>
                </a>
                <?php endif; ?>
                
                <?php if ($userPermissions['canCreateTemplates']) : ?>
                <a href="/file-explorer/upload-template" class="flex items-center p-6 bg-blue-50 rounded-lg hover:bg-blue-100 transition duration-200 text-center">
                    <div class="w-full">
                        <i class="fas fa-upload text-blue-500 text-3xl mb-3"></i>
                        <div class="font-medium text-gray-900 mb-1">Upload Template</div>
                        <div class="text-sm text-gray-600">Add a new template file</div>
                    </div>
                </a>
                <?php endif; ?>
                
                <?php if ($userPermissions['canCreateFilledFiles']) : ?>
                <a href="/file-explorer/create-filled-file" class="flex items-center p-6 bg-green-50 rounded-lg hover:bg-green-100 transition duration-200 text-center">
                    <div class="w-full">
                        <i class="fas fa-edit text-green-500 text-3xl mb-3"></i>
                        <div class="font-medium text-gray-900 mb-1">Create Filled File</div>
                        <div class="text-sm text-gray-600">Fill out a template</div>
                    </div>
                </a>
                <?php endif; ?>
                
                <?php if ($user->inGroup('superadmin', 'admin')) : ?>
                <a href="/user-management" class="flex items-center p-6 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition duration-200 text-center">
                    <div class="w-full">
                        <i class="fas fa-users-cog text-indigo-500 text-3xl mb-3"></i>
                        <div class="font-medium text-gray-900 mb-1">Manage Users</div>
                        <div class="text-sm text-gray-600">User administration</div>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-clock mr-2 text-blue-500"></i>Recent Templates
            </h3>
            <?php if (!empty($recentTemplates)) : ?>
            <div class="space-y-3">
                <?php foreach ($recentTemplates as $template): ?>
                <a href="/file-explorer#template-<?php echo $template['id'] ?>" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-blue-50 hover:border-blue-200 border border-transparent transition duration-200 cursor-pointer">
                    <i class="fas fa-file-alt text-blue-500 mr-3"></i>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 hover:text-blue-700"><?php echo esc($template['name']) ?></p>
                        <p class="text-sm text-gray-600">
                            Created: <?php echo date('M j, Y', strtotime($template['createdAt'])) ?>
                        </p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8">
                <i class="fas fa-file-alt text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500">No templates available</p>
                <?php if ($userPermissions['canCreateTemplates']) : ?>
                    <a href="/file-explorer/upload-template" class="inline-block mt-3 text-blue-600 hover:text-blue-700">
                        Upload your first template
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-history mr-2 text-green-500"></i>Recent Filled Files
            </h3>
            <?php if (!empty($recentFilledFiles)) : ?>
            <div class="space-y-3">
                <?php foreach ($recentFilledFiles as $file): ?>
                <a href="/file-explorer#filled-file-<?php echo $file['id'] ?? '' ?>" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-green-50 hover:border-green-200 border border-transparent transition duration-200 cursor-pointer">
                    <i class="fas fa-edit text-green-500 mr-3"></i>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 hover:text-green-700"><?php echo esc($file['name'] ?? 'Unnamed File') ?></p>
                        <p class="text-sm text-gray-600">
                            Template: <?php echo esc($file['template_name'] ?? 'Unknown Template') ?>
                        </p>
                        <p class="text-xs text-gray-500">
                            Created: <?php echo isset($file['createdAt']) && $file['createdAt'] ? date('M j, Y', strtotime($file['createdAt'])) : 'Unknown date' ?>
                        </p>
                    </div>
                    <i class="fas fa-arrow-right text-gray-400"></i>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-8">
                <i class="fas fa-edit text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500">No filled files available</p>
                <?php if ($userPermissions['canCreateFilledFiles']) : ?>
                    <a href="/file-explorer/create-filled-file" class="inline-block mt-3 text-green-600 hover:text-green-700">
                        Create your first filled file
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
<?php echo $this->endSection() ?>