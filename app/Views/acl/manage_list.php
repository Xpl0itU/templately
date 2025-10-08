<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>Manage <?php echo ucfirst($resourceType) ?> Permissions<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include('components/navigation', [
        'pageTitle' => "Manage {$resourceType} Permissions",
        'pageIcon' => 'shield-alt',
        'stickyNav' => false,
        'showWelcome' => false
    ]) ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('content') ?>
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-shield-alt mr-2 text-blue-500"></i>Manage <?php echo ucfirst($resourceType) ?> Permissions
            </h2>
            <p class="text-gray-600 mt-1">Select a <?php echo $resourceType ?> to manage its permissions</p>
        </div>
        
        <div class="p-6">
            <?php if (empty($resources)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-folder-open text-gray-400 text-5xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No <?php echo ucfirst($resourceType) ?>s Found</h3>
                    <p class="text-gray-500">There are no <?php echo $resourceType ?>s to manage permissions for.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($resources as $resource): ?>
                        <div class="bg-white rounded-lg shadow border border-gray-200 overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                                        <?php if ($resourceType === 'template'): ?>
                                            <i class="fas fa-file-alt text-white text-xl"></i>
                                        <?php elseif ($resourceType === 'filled_file'): ?>
                                            <i class="fas fa-file-word text-white text-xl"></i>
                                        <?php else: ?>
                                            <i class="fas fa-user text-white text-xl"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <?php if ($resourceType === 'user'): ?>
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo $resource->username ?></h4>
                                            <p class="text-gray-500 text-sm"><?php echo $resource->email ?></p>
                                        <?php else: ?>
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo $resource['name'] ?? $resource['title'] ?? 'Unnamed' ?></h4>
                                            <?php if (isset($resource['created_at'])): ?>
                                                <p class="text-gray-500 text-sm">Created: <?php echo $resource['created_at'] ?></p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-between">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo ucfirst($resourceType) ?>
                                    </span>
                                    <a href="/acl/manage/<?php echo $resourceType ?>/<?php echo $resource['id'] ?? $resource->id ?>" 
                                       class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        Manage
                                        <i class="fas fa-cog ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php echo $this->endSection() ?>