<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>Permissions<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include('components/navigation', [
        'pageTitle' => 'Permissions',
        'pageIcon' => 'shield-alt',
        'stickyNav' => false,
        'showWelcome' => false
    ]) ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('content') ?>
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div id="alertMessage" class="hidden mb-6 p-4 rounded-lg"></div>

    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-shield-alt mr-2 text-blue-500"></i>Permission Management
            </h2>
            <p class="text-gray-600 mt-1">Manage user permissions and access controls</p>
        </div>
        
        <div class="p-6">
            <!-- Simplified Navigation -->
            <div class="mb-6">
                <nav class="flex space-x-4" aria-label="Permission sections">
                    <button type="button" 
                            class="permission-nav-button bg-blue-100 text-blue-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            data-section="users">
                        <i class="fas fa-users mr-2"></i>Users
                    </button>
                    <button type="button" 
                            class="permission-nav-button bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                            data-section="templates">
                        <i class="fas fa-file-alt mr-2"></i>Templates
                    </button>
                    <button type="button" 
                            class="permission-nav-button bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                            data-section="files">
                        <i class="fas fa-file-word mr-2"></i>Filled Files
                    </button>
                    <button type="button" 
                            class="permission-nav-button bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
                            data-section="audit">
                        <i class="fas fa-history mr-2"></i>Audit Log
                    </button>
                </nav>
            </div>
            
            <!-- Users Section -->
            <div class="permission-section active" id="users-section">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-users mr-2 text-blue-500"></i>Manage User Permissions
                    </h3>
                    <p class="text-gray-600 mb-4">Assign roles and permissions to users in your system.</p>
                </div>
                
                <!-- User Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select User</label>
                    <select id="user-selector" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Choose a user...</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id'] ?>">
                            <?php echo esc($user['username']) ?>
                            <?php if (!empty($user['roles'])): ?>
                                (Roles: <?php echo esc(implode(', ', $user['roles'])) ?>)
                            <?php endif; ?>
                            <?php if (!empty($user['user_groups'])): ?>
                                [Groups: <?php echo esc(implode(', ', array_column($user['user_groups'], 'name'))) ?>]
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- User Permissions Panel -->
                <div id="user-permissions-panel" class="hidden">
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <i class="fas fa-user text-indigo-600"></i>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-md font-medium text-gray-900" id="selected-user-name"></h4>
                                <p class="text-sm text-gray-500" id="selected-user-roles"></p>
                                <p class="text-sm text-gray-500" id="selected-user-groups"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Role Assignment -->
                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-900 mb-3">User Role</h4>
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($roleGroups as $group): ?>
                                <?php $roleInputId = 'role-' . $group['name']; ?>
                                <div class="flex items-center">
                                    <input type="radio"
                                           name="user-role"
                                           id="<?php echo esc($roleInputId) ?>"
                                           value="<?php echo esc($group['name']) ?>"
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                                    <label for="<?php echo esc($roleInputId) ?>" class="ml-3 block text-sm font-medium text-gray-700">
                                        <?php echo esc($group['title']) ?>
                                        <p class="text-gray-500 text-xs"><?php echo esc($group['description']) ?></p>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4">
                                <button type="button" 
                                        id="save-user-role" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-save mr-2"></i>Save Role
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- User Groups Assignment -->
                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-900 mb-3">User Groups</h4>
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <?php if (empty($userGroups)): ?>
                                <p class="text-sm text-gray-500">No user groups have been created yet. Visit the User Groups page to set some up.</p>
                            <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($userGroups as $group): ?>
                                <?php $groupInputId = 'user-group-' . $group['id']; ?>
                                <div class="flex items-center">
                                    <input type="checkbox"
                                           name="user-group-membership"
                                           id="<?php echo esc($groupInputId) ?>"
                                           value="<?php echo $group['id'] ?>"
                                           data-user-group="<?php echo $group['id'] ?>"
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="<?php echo esc($groupInputId) ?>" class="ml-3 block text-sm font-medium text-gray-700">
                                        <?php echo esc($group['name']) ?>
                                        <p class="text-gray-500 text-xs">
                                            <?php echo esc($group['description'] ?? 'No description provided.') ?>
                                            <?php if (isset($group['member_count'])): ?>
                                                <span class="block text-blue-400">Members: <?php echo (int) $group['member_count'] ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <div class="mt-4">
                                <button type="button"
                                        id="save-user-groups"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-save mr-2"></i>Save Group Membership
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Individual Permissions -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-3">Individual Permissions</h4>
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <div class="space-y-3">
                                <?php foreach ($systemPermissions as $permission => $description): ?>
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           id="perm-<?php echo str_replace('.', '-', $permission) ?>"
                                           data-permission="<?php echo $permission ?>"
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="perm-<?php echo str_replace('.', '-', $permission) ?>" class="ml-3 block text-sm font-medium text-gray-700">
                                        <?php echo $permission ?>
                                        <p class="text-gray-500 text-xs"><?php echo $description ?></p>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-4 flex space-x-3">
                                <button type="button" 
                                        id="save-user-permissions" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-save mr-2"></i>Save Permissions
                                </button>
                                <button type="button" 
                                        id="reset-user-permissions" 
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-undo mr-2"></i>Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div id="user-empty-state" class="text-center py-12">
                    <i class="fas fa-user-circle text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Select a User</h3>
                    <p class="text-gray-500">Choose a user from the dropdown to manage their permissions</p>
                </div>
            </div>
            
            <!-- Templates Section -->
            <div class="permission-section hidden" id="templates-section">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-file-alt mr-2 text-blue-500"></i>Template Permissions
                    </h3>
                    <p class="text-gray-600 mb-4">Manage permissions for specific templates in your system.</p>
                </div>
                
                <!-- Template Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Template</label>
                    <select id="template-selector" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Choose a template...</option>
                        <?php foreach ($templates as $template): ?>
                        <option value="<?php echo $template['id'] ?>">
                            <?php echo esc($template['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Template Permissions Panel -->
                <div id="template-permissions-panel" class="hidden">
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                <i class="fas fa-file-alt text-purple-600"></i>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-md font-medium text-gray-900" id="selected-template-name"></h4>
                                <p class="text-sm text-gray-500" id="selected-template-date"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Template Access Controls -->
                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Template Access</h4>
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Who can view this template?</label>
                                    <select id="template-view-access" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="all">All users</option>
                                        <option value="group">Selected groups only</option>
                                        <option value="user">Specific users only</option>
                                        <option value="none">Nobody (private)</option>
                                    </select>
                                </div>
                                
                                <div id="template-group-access" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Groups</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <?php foreach ($userGroups as $group): ?>
                                        <div class="flex items-center">
                                            <input type="checkbox" 
                                                   id="template-group-<?php echo $group['id'] ?>"
                                                   data-group-id="<?php echo $group['id'] ?>"
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <label for="template-group-<?php echo $group['id'] ?>" class="ml-3 block text-sm font-medium text-gray-700">
                                                <?php echo esc($group['name']) ?>
                                                <?php if (!empty($group['description'])): ?>
                                                <p class="text-gray-500 text-xs"><?php echo esc($group['description']) ?></p>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div id="template-user-access" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Users</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-40 overflow-y-auto">
                                        <?php foreach ($users as $user): ?>
                                        <div class="flex items-center">
                                            <input type="checkbox" 
                                                   id="template-user-<?php echo $user['id'] ?>"
                                                   data-user="<?php echo $user['id'] ?>"
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <label for="template-user-<?php echo $user['id'] ?>" class="ml-3 block text-sm font-medium text-gray-700">
                                                <?php echo esc($user['username']) ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="button" 
                                        id="save-template-permissions" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-save mr-2"></i>Save Template Permissions
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div id="template-empty-state" class="text-center py-12">
                    <i class="fas fa-file-alt text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Select a Template</h3>
                    <p class="text-gray-500">Choose a template from the dropdown to manage its permissions</p>
                </div>
            </div>
            
            <!-- Filled Files Section -->
            <div class="permission-section hidden" id="files-section">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-file-word mr-2 text-blue-500"></i>Filled File Permissions
                    </h3>
                    <p class="text-gray-600 mb-4">Manage permissions for specific filled files in your system.</p>
                </div>
                
                <!-- Filled File Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Filled File</label>
                    <select id="file-selector" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Choose a filled file...</option>
                        <?php foreach ($filledFiles as $file): ?>
                        <option value="<?php echo $file['id'] ?>">
                            <?php echo esc($file['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Filled File Permissions Panel -->
                <div id="file-permissions-panel" class="hidden">
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="fas fa-file-word text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-md font-medium text-gray-900" id="selected-file-name"></h4>
                                <p class="text-sm text-gray-500" id="selected-file-date"></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filled File Access Controls -->
                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-900 mb-3">File Access</h4>
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Who can access this file?</label>
                                    <select id="file-access" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="owner">Owner only</option>
                                        <option value="group">Owner's group</option>
                                        <option value="all">All users</option>
                                        <option value="specific">Specific users only</option>
                                    </select>
                                </div>
                                
                                <div id="file-user-access" class="hidden">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Users</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-40 overflow-y-auto">
                                        <?php foreach ($users as $user): ?>
                                        <div class="flex items-center">
                                            <input type="checkbox" 
                                                   id="file-user-<?php echo $user['id'] ?>"
                                                   data-user="<?php echo $user['id'] ?>"
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <label for="file-user-<?php echo $user['id'] ?>" class="ml-3 block text-sm font-medium text-gray-700">
                                                <?php echo esc($user['username']) ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="button" 
                                        id="save-file-permissions" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <i class="fas fa-save mr-2"></i>Save File Permissions
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div id="file-empty-state" class="text-center py-12">
                    <i class="fas fa-file-word text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Select a Filled File</h3>
                    <p class="text-gray-500">Choose a filled file from the dropdown to manage its permissions</p>
                </div>
            </div>
            
            <!-- Audit Log Section -->
            <div class="permission-section hidden" id="audit-section">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-history mr-2 text-blue-500"></i>Permission Audit Log
                    </h3>
                    <p class="text-gray-600 mb-4">Track all permission-related activities in your system.</p>
                </div>
                
                <!-- Audit Log Filters -->
                <div class="mb-6 bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by Action</label>
                            <select id="audit-action-filter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Actions</option>
                                <option value="grant">Grant</option>
                                <option value="revoke">Revoke</option>
                                <option value="check">Check</option>
                                <option value="access_denied">Access Denied</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Filter by User</label>
                            <select id="audit-user-filter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id'] ?>"><?php echo esc($user['username']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="flex items-end">
                            <button type="button" 
                                    id="refresh-audit-log" 
                                    class="w-full inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-sync-alt mr-2"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Audit Log Table -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Timestamp</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resource</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permission</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Result</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="audit-log-body">
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Loading audit log entries...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <button type="button" 
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </button>
                            <button type="button" 
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </button>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span id="audit-log-start">0</span> to <span id="audit-log-end">0</span> of <span id="audit-log-total">0</span> results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <button type="button" 
                                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Previous</span>
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button type="button" 
                                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        <span class="sr-only">Next</span>
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Permission section navigation
    const navButtons = document.querySelectorAll('.permission-nav-button');
    const sections = document.querySelectorAll('.permission-section');
    
    navButtons.forEach(button => {
        button.addEventListener('click', function() {
            const sectionName = this.getAttribute('data-section');
            
            // Update active nav button
            navButtons.forEach(btn => {
                if (btn === this) {
                    btn.classList.remove('bg-gray-100', 'text-gray-700');
                    btn.classList.add('bg-blue-100', 'text-blue-700');
                } else {
                    btn.classList.remove('bg-blue-100', 'text-blue-700');
                    btn.classList.add('bg-gray-100', 'text-gray-700');
                }
            });
            
            // Update active section
            sections.forEach(section => {
                if (section.id === `${sectionName}-section`) {
                    section.classList.remove('hidden');
                    section.classList.add('active');
                } else {
                    section.classList.remove('active');
                    section.classList.add('hidden');
                }
            });
        });
    });
    
    // User selection
    const userSelector = document.getElementById('user-selector');
    const userPermissionsPanel = document.getElementById('user-permissions-panel');
    const userEmptyState = document.getElementById('user-empty-state');
    
    userSelector.addEventListener('change', function() {
        const userId = this.value;
        
        if (userId) {
            // Show user permissions panel
            userEmptyState.classList.add('hidden');
            userPermissionsPanel.classList.remove('hidden');
            
            // Load user details
            loadUserDetails(userId);
        } else {
            // Hide user permissions panel
            userPermissionsPanel.classList.add('hidden');
            userEmptyState.classList.remove('hidden');
        }
    });
    
    // Template selection
    const templateSelector = document.getElementById('template-selector');
    const templatePermissionsPanel = document.getElementById('template-permissions-panel');
    const templateEmptyState = document.getElementById('template-empty-state');
    
    templateSelector.addEventListener('change', function() {
        const templateId = this.value;
        
        if (templateId) {
            // Show template permissions panel
            templateEmptyState.classList.add('hidden');
            templatePermissionsPanel.classList.remove('hidden');
            
            // Load template details
            loadTemplateDetails(templateId);
        } else {
            // Hide template permissions panel
            templatePermissionsPanel.classList.add('hidden');
            templateEmptyState.classList.remove('hidden');
        }
    });
    
    // Filled file selection
    const fileSelector = document.getElementById('file-selector');
    const filePermissionsPanel = document.getElementById('file-permissions-panel');
    const fileEmptyState = document.getElementById('file-empty-state');
    
    fileSelector.addEventListener('change', function() {
        const fileId = this.value;
        
        if (fileId) {
            // Show file permissions panel
            fileEmptyState.classList.add('hidden');
            filePermissionsPanel.classList.remove('hidden');
            
            // Load file details
            loadFileDetails(fileId);
        } else {
            // Hide file permissions panel
            filePermissionsPanel.classList.add('hidden');
            fileEmptyState.classList.remove('hidden');
        }
    });
    
    // Template access controls
    const templateViewAccess = document.getElementById('template-view-access');
    const templateGroupAccess = document.getElementById('template-group-access');
    const templateUserAccess = document.getElementById('template-user-access');
    
    templateViewAccess.addEventListener('change', function() {
        const value = this.value;
        
        if (value === 'group') {
            templateGroupAccess.classList.remove('hidden');
            templateUserAccess.classList.add('hidden');
        } else if (value === 'user') {
            templateGroupAccess.classList.add('hidden');
            templateUserAccess.classList.remove('hidden');
        } else {
            templateGroupAccess.classList.add('hidden');
            templateUserAccess.classList.add('hidden');
        }
    });
    
    // File access controls
    const fileAccess = document.getElementById('file-access');
    const fileUserAccess = document.getElementById('file-user-access');
    
    fileAccess.addEventListener('change', function() {
        const value = this.value;
        
        if (value === 'specific') {
            fileUserAccess.classList.remove('hidden');
        } else {
            fileUserAccess.classList.add('hidden');
        }
    });
    
    // Save user role
    const saveUserRoleButton = document.getElementById('save-user-role');
    if (saveUserRoleButton) {
        saveUserRoleButton.addEventListener('click', function() {
            const userId = userSelector.value;
            const selectedRole = document.querySelector('input[name="user-role"]:checked');
            
            if (!userId) {
                showAlert('Please select a user first.', 'error');
                return;
            }
            
            if (!selectedRole) {
                showAlert('Please select a role.', 'error');
                return;
            }
            
            saveUserRole(userId, selectedRole.value);
        });
    }

    // Save user groups
    const saveUserGroupsButton = document.getElementById('save-user-groups');
    if (saveUserGroupsButton) {
        saveUserGroupsButton.addEventListener('click', function() {
            const userId = userSelector.value;
            const selectedGroups = [];

            document.querySelectorAll('input[name="user-group-membership"]:checked').forEach(checkbox => {
                selectedGroups.push(parseInt(checkbox.value, 10));
            });

            if (!userId) {
                showAlert('Please select a user first.', 'error');
                return;
            }

            saveUserGroups(userId, selectedGroups);
        });
    }
    
    // Save user permissions
    const saveUserPermissionsButton = document.getElementById('save-user-permissions');
    if (saveUserPermissionsButton) {
        saveUserPermissionsButton.addEventListener('click', function() {
            const userId = userSelector.value;
            const selectedPermissions = [];
            
            document.querySelectorAll('input[type="checkbox"][data-permission]').forEach(checkbox => {
                if (checkbox.checked) {
                    selectedPermissions.push(checkbox.getAttribute('data-permission'));
                }
            });
            
            if (!userId) {
                showAlert('Please select a user first.', 'error');
                return;
            }
            
            saveUserPermissions(userId, selectedPermissions);
        });
    }
    
    // Reset user permissions
    const resetUserPermissionsButton = document.getElementById('reset-user-permissions');
    if (resetUserPermissionsButton) {
        resetUserPermissionsButton.addEventListener('click', function() {
            const userId = userSelector.value;
            
            if (!userId) {
                showAlert('Please select a user first.', 'error');
                return;
            }
            
            resetUserPermissions(userId);
        });
    }
    
    // Save template permissions
    const saveTemplatePermissionsButton = document.getElementById('save-template-permissions');
    if (saveTemplatePermissionsButton) {
        saveTemplatePermissionsButton.addEventListener('click', function() {
            const templateId = templateSelector.value;
            const viewAccess = templateViewAccess.value;
            const selectedGroups = [];
            const selectedUsers = [];
            
            if (viewAccess === 'group') {
                document.querySelectorAll('input[type="checkbox"][data-group-id]').forEach(checkbox => {
                    if (checkbox.checked) {
                        selectedGroups.push(parseInt(checkbox.getAttribute('data-group-id'), 10));
                    }
                });
            } else if (viewAccess === 'user') {
                document.querySelectorAll('input[type="checkbox"][data-user]').forEach(checkbox => {
                    if (checkbox.checked) {
                        selectedUsers.push(checkbox.getAttribute('data-user'));
                    }
                });
            }
            
            if (!templateId) {
                showAlert('Please select a template first.', 'error');
                return;
            }
            
            saveTemplatePermissions(templateId, viewAccess, selectedGroups, selectedUsers);
        });
    }
    
    // Save file permissions
    const saveFilePermissionsButton = document.getElementById('save-file-permissions');
    if (saveFilePermissionsButton) {
        saveFilePermissionsButton.addEventListener('click', function() {
            const fileId = fileSelector.value;
            const accessLevel = fileAccess.value;
            const selectedUsers = [];
            
            if (accessLevel === 'specific') {
                document.querySelectorAll('input[type="checkbox"][data-user]').forEach(checkbox => {
                    if (checkbox.checked) {
                        selectedUsers.push(checkbox.getAttribute('data-user'));
                    }
                });
            }
            
            if (!fileId) {
                showAlert('Please select a filled file first.', 'error');
                return;
            }
            
            saveFilePermissions(fileId, accessLevel, selectedUsers);
        });
    }
    
    // Refresh audit log
    const refreshAuditLogButton = document.getElementById('refresh-audit-log');
    if (refreshAuditLogButton) {
        refreshAuditLogButton.addEventListener('click', function() {
            loadAuditLog();
        });
    }
    
    // Initialize
    loadAuditLog();
});

// Function to load user details
function loadUserDetails(userId) {
    fetch(`/index.php/permissions/get-user-details/${userId}`, {
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
            const user = data.user;
            const roleNames = user.roles || [];
            const groupNames = (user.user_groups || []).map(group => group.name);

            document.getElementById('selected-user-name').textContent = user.username;
            document.getElementById('selected-user-roles').textContent = roleNames.length
                ? `Roles: ${roleNames.join(', ')}`
                : 'Roles: none assigned';
            document.getElementById('selected-user-groups').textContent = groupNames.length
                ? `Groups: ${groupNames.join(', ')}`
                : 'Groups: none assigned';

            const roleRadios = document.querySelectorAll('input[name="user-role"]');
            roleRadios.forEach(radio => {
                radio.checked = roleNames.includes(radio.value);
            });

            const groupCheckboxes = document.querySelectorAll('input[name="user-group-membership"]');
            groupCheckboxes.forEach(checkbox => {
                const groupId = parseInt(checkbox.value, 10);
                const isMember = (user.user_groups || []).some(group => parseInt(group.id, 10) === groupId);
                checkbox.checked = isMember;
            });

            const permissionCheckboxes = document.querySelectorAll('input[type="checkbox"][data-permission]');
            permissionCheckboxes.forEach(checkbox => {
                const permission = checkbox.getAttribute('data-permission');
                checkbox.checked = user.permissions.includes(permission);
            });
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error loading user details:', error);
        showAlert('An error occurred while loading user details.', 'error');
    });
}

// Function to load template details
function loadTemplateDetails(templateId) {
    fetch(`/index.php/permissions/get-template-details/${templateId}`, {
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
                document.getElementById('selected-template-name').textContent = data.template.name;
                document.getElementById('selected-template-date').textContent = data.template.createdAt;
                
                // Set template access controls based on current permissions
                // This would need to be implemented based on how template permissions are stored
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading template details:', error);
            showAlert('An error occurred while loading template details.', 'error');
        });
}

// Function to load filled file details
function loadFileDetails(fileId) {
    fetch(`/index.php/permissions/get-file-details/${fileId}`, {
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
                document.getElementById('selected-file-name').textContent = data.file.name;
                document.getElementById('selected-file-date').textContent = data.file.createdAt;
                
                // Set file access controls based on current permissions
                // This would need to be implemented based on how file permissions are stored
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error loading file details:', error);
            showAlert('An error occurred while loading file details.', 'error');
        });
}

// Function to save user role
function saveUserRole(userId, role) {
    fetch('/index.php/permissions/save-user-role', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '<?php echo csrf_hash(); ?>'
        },
        body: JSON.stringify({
            user_id: userId,
            role: role
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadUserDetails(userId);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving user role:', error);
        showAlert('An error occurred while saving the user role.', 'error');
    });
}

// Function to save user group membership
function saveUserGroups(userId, groupIds) {
    fetch('/index.php/permissions/save-user-groups', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '<?php echo csrf_hash(); ?>'
        },
        body: JSON.stringify({
            user_id: userId,
            group_ids: groupIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadUserDetails(userId);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving user groups:', error);
        showAlert('An error occurred while saving the user groups.', 'error');
    });
}

// Function to save user permissions
function saveUserPermissions(userId, permissions) {
    fetch('/index.php/permissions/save-user-permissions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '<?php echo csrf_hash(); ?>'
        },
        body: JSON.stringify({
            user_id: userId,
            permissions: permissions
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving user permissions:', error);
        showAlert('An error occurred while saving user permissions.', 'error');
    });
}

// Function to reset user permissions
function resetUserPermissions(userId) {
    if (!confirm('Are you sure you want to reset this user\'s permissions to their default group permissions?')) {
        return;
    }
    
    fetch('/index.php/permissions/reset-user-permissions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '<?php echo csrf_hash(); ?>'
        },
        body: JSON.stringify({
            user_id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            // Reload user details to show reset permissions
            loadUserDetails(userId);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error resetting user permissions:', error);
        showAlert('An error occurred while resetting user permissions.', 'error');
    });
}

// Function to save template permissions
function saveTemplatePermissions(templateId, viewAccess, groups, users) {
    fetch('/index.php/permissions/save-template-permissions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '<?php echo csrf_hash(); ?>'
        },
        body: JSON.stringify({
            template_id: templateId,
            view_access: viewAccess,
            groups: groups,
            users: users
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving template permissions:', error);
        showAlert('An error occurred while saving template permissions.', 'error');
    });
}

// Function to save filled file permissions
function saveFilePermissions(fileId, accessLevel, users) {
    fetch('/index.php/permissions/save-file-permissions', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '<?php echo csrf_hash(); ?>'
        },
        body: JSON.stringify({
            file_id: fileId,
            access_level: accessLevel,
            users: users
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving file permissions:', error);
        showAlert('An error occurred while saving file permissions.', 'error');
    });
}

// Function to load audit log
function loadAuditLog() {
    const actionFilter = document.getElementById('audit-action-filter').value;
    const userFilter = document.getElementById('audit-user-filter').value;
    
    fetch('/index.php/permissions/get-audit-log', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '<?php echo csrf_hash(); ?>'
        },
        body: JSON.stringify({
            action: actionFilter,
            user: userFilter
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAuditLog(data.logs);
            updateAuditLogPagination(data.pagination);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error loading audit log:', error);
        showAlert('An error occurred while loading the audit log.', 'error');
    });
}

// Function to display audit log entries
function displayAuditLog(logs) {
    const tbody = document.getElementById('audit-log-body');
    
    if (logs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                    No audit log entries found
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    logs.forEach(log => {
        html += `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${log.timestamp}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${log.user || 'System'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${log.action}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${log.resource || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${log.permission || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                        log.result === 'allowed' ? 'bg-green-100 text-green-800' : 
                        log.result === 'denied' ? 'bg-red-100 text-red-800' : 
                        'bg-yellow-100 text-yellow-800'
                    }">
                        ${log.result}
                    </span>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Function to update audit log pagination
function updateAuditLogPagination(pagination) {
    document.getElementById('audit-log-start').textContent = pagination.start;
    document.getElementById('audit-log-end').textContent = pagination.end;
    document.getElementById('audit-log-total').textContent = pagination.total;
}

// Function to show alert messages
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertMessage');
    alertContainer.className = `mb-6 p-4 rounded-lg ${
        type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
        type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
        'bg-blue-100 text-blue-800 border border-blue-200'
    }`;
    alertContainer.textContent = message;
    alertContainer.classList.remove('hidden');
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alertContainer.classList.add('hidden');
    }, 5000);
}
</script>
<?php echo $this->endSection() ?>