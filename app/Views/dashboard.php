<?= $this->extend('layouts/app') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('navigation') ?>
    <?= $this->include('components/navigation', [
        'pageTitle' => 'Templately',
        'pageIcon' => 'file-alt',
        'stickyNav' => false,
        'showWelcome' => true
    ]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-file-alt text-blue-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Templates</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $totalTemplates ?></p>
                    </div>
                </div>
            </div>

                        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-file-pdf text-green-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Filled Files</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $totalFilledFiles ?></p>
                    </div>
                </div>
            </div>

                        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-user-shield text-purple-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Your Role</p>
                        <p class="text-2xl font-bold text-gray-900 capitalize">
                            <?= esc($user->getGroups()[0] ?? 'User') ?>
                        </p>
                    </div>
                </div>
            </div>

                        <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-bolt text-orange-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Quick Actions</p>
                        <p class="text-sm text-gray-900">Get started fast</p>
                    </div>
                </div>
            </div>
        </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                        <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-rocket mr-2 text-blue-500"></i>Quick Actions
                </h3>
                <div class="space-y-3">
                    <?php if ($userPermissions['canViewTemplates']): ?>
                    <a href="/file-explorer" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition duration-200">
                        <i class="fas fa-folder-open text-blue-500 mr-3"></i>
                        <span class="font-medium text-gray-900">Browse Templates & Files</span>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($userPermissions['canCreateTemplates']): ?>
                    <button onclick="openFileExplorer()" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition duration-200 w-full text-left">
                        <i class="fas fa-plus text-green-500 mr-3"></i>
                        <span class="font-medium text-gray-900">Upload New Template</span>
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($userPermissions['canCreateFilledFiles']): ?>
                    <button onclick="openFileExplorer()" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition duration-200 w-full text-left">
                        <i class="fas fa-file-plus text-purple-500 mr-3"></i>
                        <span class="font-medium text-gray-900">Create Filled File</span>
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($user->inGroup('superadmin', 'admin')): ?>
                    <a href="/user-management" class="flex items-center p-3 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition duration-200">
                        <i class="fas fa-users-cog text-indigo-500 mr-3"></i>
                        <span class="font-medium text-gray-900">Manage Users</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

                        <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-shield-alt mr-2 text-green-500"></i>Your Permissions
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="flex items-center">
                        <i class="fas fa-<?= $userPermissions['canViewTemplates'] ? 'check text-green-500' : 'times text-red-500' ?> mr-2"></i>
                        <span class="text-sm text-gray-700">View Templates</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-<?= $userPermissions['canCreateTemplates'] ? 'check text-green-500' : 'times text-red-500' ?> mr-2"></i>
                        <span class="text-sm text-gray-700">Create Templates</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-<?= $userPermissions['canEditTemplates'] ? 'check text-green-500' : 'times text-red-500' ?> mr-2"></i>
                        <span class="text-sm text-gray-700">Edit Templates</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-<?= $userPermissions['canDeleteTemplates'] ? 'check text-green-500' : 'times text-red-500' ?> mr-2"></i>
                        <span class="text-sm text-gray-700">Delete Templates</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-<?= $userPermissions['canViewFilledFiles'] ? 'check text-green-500' : 'times text-red-500' ?> mr-2"></i>
                        <span class="text-sm text-gray-700">View Filled Files</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-<?= $userPermissions['canCreateFilledFiles'] ? 'check text-green-500' : 'times text-red-500' ?> mr-2"></i>
                        <span class="text-sm text-gray-700">Create Filled Files</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-<?= $userPermissions['canEditFilledFiles'] ? 'check text-green-500' : 'times text-red-500' ?> mr-2"></i>
                        <span class="text-sm text-gray-700">Edit Filled Files</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-<?= $userPermissions['canDeleteFilledFiles'] ? 'check text-green-500' : 'times text-red-500' ?> mr-2"></i>
                        <span class="text-sm text-gray-700">Delete Filled Files</span>
                    </div>
                </div>
            </div>
        </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-clock mr-2 text-blue-500"></i>Recent Templates
                </h3>
                <?php if (!empty($recentTemplates)): ?>
                <div class="space-y-3">
                    <?php foreach ($recentTemplates as $template): ?>
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <i class="fas fa-file-alt text-blue-500 mr-3"></i>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900"><?= esc($template['name']) ?></p>
                            <p class="text-sm text-gray-600">
                                Created: <?= date('M j, Y', strtotime($template['createdAt'])) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-gray-600 text-center py-4">No templates available</p>
                <?php endif; ?>
            </div>

                        <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-history mr-2 text-green-500"></i>Recent Filled Files
                </h3>
                <?php if (!empty($recentFilledFiles)): ?>
                <div class="space-y-3">
                    <?php foreach ($recentFilledFiles as $file): ?>
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <i class="fas fa-file-pdf text-green-500 mr-3"></i>
                        <div class="flex-1">
                            <p class="font-medium text-gray-900"><?= esc($file['name']) ?></p>
                            <p class="text-sm text-gray-600">
                                Template: <?= esc($file['template_name']) ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                Created: <?= date('M j, Y', strtotime($file['createdAt'])) ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-gray-600 text-center py-4">No filled files available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
    <script>
        function openFileExplorer() {
            window.location.href = '/file-explorer';
        }
    </script>
<?= $this->endSection() ?>
