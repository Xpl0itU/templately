<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>ACL Management<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include('components/navigation', [
        'pageTitle' => 'ACL Management',
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
                <i class="fas fa-shield-alt mr-2 text-blue-500"></i>Advanced Permission Management (ACL)
            </h2>
            <p class="text-gray-600 mt-1">Granular permissions with ownership and inheritance</p>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- ACL Settings -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-cog mr-2 text-gray-600"></i>ACL Settings
                    </h3>
                    
                    <form id="acl-settings-form" method="post" action="/acl/settings/update">
                        <div class="space-y-4">
                            <?php foreach ($aclSettings as $key => $setting): ?>
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input id="setting-<?php echo $key ?>" 
                                           name="settings[<?php echo $key ?>]" 
                                           type="checkbox" 
                                           value="1" 
                                           class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                           <?php echo $setting['value'] ? 'checked' : '' ?>>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="setting-<?php echo $key ?>" class="font-medium text-gray-700">
                                        <?php echo ucfirst(str_replace('_', ' ', $key)) ?>
                                    </label>
                                    <p class="text-gray-500"><?php echo $setting['description'] ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-6 flex space-x-3">
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-save mr-2"></i>Save Settings
                            </button>
                            <button type="button" 
                                    id="reset-settings-btn"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-undo mr-2"></i>Reset to Defaults
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- ACL Permissions Overview -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-list mr-2 text-gray-600"></i>Available Permissions
                    </h3>
                    
                    <ul class="grid grid-cols-1 gap-3">
                        <?php foreach ($aclPermissions as $permission): ?>
                        <li class="col-span-1 bg-white rounded-lg shadow divide-y divide-gray-200">
                            <div class="w-full flex items-center justify-between p-6 space-x-6">
                                <div class="flex-1 truncate">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-gray-900 text-sm font-medium truncate"><?php echo $permission['name'] ?></h3>
                                        <span class="flex-shrink-0 inline-block px-2 py-0.5 text-green-800 text-xs font-medium bg-green-100 rounded-full">
                                            ACL
                                        </span>
                                    </div>
                                    <p class="mt-1 text-gray-500 text-sm truncate"><?php echo $permission['description'] ?></p>
                                    <p class="mt-1 text-gray-500 text-xs">Bit value: <?php echo $permission['bit_value'] ?></p>
                                </div>
                                <div class="flex">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                        <?php echo $permission['name'] ?>
                                    </span>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Resource Management -->
            <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-folder mr-2 text-gray-600"></i>Manage Resource Permissions
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                                <i class="fas fa-file-alt text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">Templates</h4>
                                <p class="text-gray-500">Manage permissions for templates</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="/acl/manage/template" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Manage Templates
                            </a>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                                <i class="fas fa-file-word text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">Filled Files</h4>
                                <p class="text-gray-500">Manage permissions for filled files</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="/acl/manage/filled_file" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Manage Filled Files
                            </a>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                                <i class="fas fa-users text-white text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-medium text-gray-900">Users</h4>
                                <p class="text-gray-500">Manage user permissions</p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="/acl/manage/user" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                Manage Users
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Audit Log -->
            <div class="mt-8">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-history mr-2 text-gray-600"></i>Recent Permission Audit Log
                    </h3>
                    <a href="/acl/audit" 
                       class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        View All
                    </a>
                </div>
                
                <div class="mt-4 bg-white shadow overflow-hidden rounded-md">
                    <ul class="divide-y divide-gray-200">
                        <li>
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium text-indigo-600 truncate">Permission audit log will appear here</p>
                                    <div class="ml-2 flex flex-shrink-0">
                                        <p class="inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Coming soon
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2 sm:flex sm:justify-between">
                                    <div class="sm:flex">
                                        <p class="flex items-center text-sm text-gray-500">
                                            <i class="fas fa-user mr-1.5 text-gray-400"></i>
                                            No recent activity
                                        </p>
                                    </div>
                                    <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                                        <p>
                                            <time datetime="2023-01-01">-</time>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle ACL settings form submission
    const settingsForm = document.getElementById('acl-settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(settingsForm);
            const settings = {};
            
            // Process form data to extract settings
            for (let [key, value] of formData.entries()) {
                if (key.startsWith('settings[') && key.endsWith(']')) {
                    const settingKey = key.replace('settings[', '').replace(']', '');
                    settings[settingKey] = value === '1';
                }
            }
            
            fetch('/acl/settings/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?php echo csrf_hash(); ?>'
                },
                body: JSON.stringify({ settings: settings })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('ACL settings updated successfully!');
                } else {
                    alert('Error updating settings: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating settings: ' + error.message);
            });
        });
    }
    
    // Handle reset settings button
    const resetBtn = document.getElementById('reset-settings-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to reset all ACL settings to their default values?')) {
                fetch('/acl/settings/reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '<?php echo csrf_hash(); ?>'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('ACL settings have been reset to defaults!');
                        location.reload();
                    } else {
                        alert('Error resetting settings: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error resetting settings: ' + error.message);
                });
            }
        });
    }
});
</script>
<?php echo $this->endSection() ?>