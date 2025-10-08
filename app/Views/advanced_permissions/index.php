<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>Advanced Permissions<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include('components/navigation', [
        'pageTitle' => 'Advanced Permissions',
        'pageIcon' => 'key',
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
                <i class="fas fa-shield-alt mr-2 text-blue-500"></i>Advanced Permissions (ACL)
            </h2>
            <p class="text-gray-600 mt-1">Fine-tune access controls using advanced permission system</p>
        </div>
        
        <div class="p-6">
            <!-- Resources Dashboard -->
            <div class="space-y-8">
                <!-- Settings Overview -->
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-cog text-blue-500 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-md font-medium text-blue-900">ACL Settings</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 mt-2">
                                <?php foreach ($aclSettings as $key => $setting): ?>
                                <div class="flex items-center text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo ucfirst(str_replace('_', ' ', $key)) ?>: 
                                        <?php echo $setting['value'] ? 'Enabled' : 'Disabled' ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Resource Management -->
                <div class="space-y-6">
                    <!-- Templates Section -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <i class="fas fa-file-alt mr-2 text-indigo-600"></i>Templates
                                <span class="ml-2 bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                    <?php echo count($templates) ?> Resources
                                </span>
                            </h3>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissions</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($templates as $template): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo esc($template['name']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $template['owner'] ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                <?php echo count($template['permissions']) ?> permissions
                                                <?php if (!empty($template['permissions'])): ?>
                                                    <span class="text-xs text-gray-400">
                                                        (<?php echo implode(', ', array_map(function($p) { return $p['permission_name'] ?? ($p['permission']['name'] ?? ($p['permission'] ?? $p['name'] ?? 'Unknown')); }, $template['permissions'])) ?>)
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button type="button" 
                                                    class="manage-permissions-btn text-indigo-600 hover:text-indigo-900"
                                                    data-resource-id="<?php echo $template['id'] ?>"
                                                    data-resource-type="template"
                                                    data-resource-name="<?php echo esc($template['name']) ?>">
                                                Manage
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Filled Files Section -->
                    <div class="bg-white rounded-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <i class="fas fa-file-word mr-2 text-green-600"></i>Filled Files
                                <span class="ml-2 bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                    <?php echo count($filledFiles) ?> Resources
                                </span>
                            </h3>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Owner</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permissions</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($filledFiles as $file): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo esc($file['name']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $file['owner'] ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">
                                                <?php echo count($file['permissions']) ?> permissions
                                                <?php if (!empty($file['permissions'])): ?>
                                                    <span class="text-xs text-gray-400">
                                                        (<?php echo implode(', ', array_map(function($p) { return $p['permission']['name']; }, $file['permissions'])) ?>)
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button type="button" 
                                                    class="manage-permissions-btn text-indigo-600 hover:text-indigo-900"
                                                    data-resource-id="<?php echo $file['id'] ?>"
                                                    data-resource-type="filled_file"
                                                    data-resource-name="<?php echo esc($file['name']) ?>">
                                                Manage
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal for managing permissions -->
    <div id="permissionsModal" class="hidden fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">
                                Manage Permissions
                            </h3>
                            <div class="mt-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Resource</label>
                                        <p class="text-sm text-gray-900 font-medium" id="modalResourceName">-</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Owner</label>
                                        <p class="text-sm text-gray-900" id="modalResourceOwner">-</p>
                                    </div>
                                </div>
                                
                                <div class="border rounded-lg divide-y divide-gray-200">
                                    <div class="p-4 bg-gray-50 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Current Permissions</h4>
                                            <p class="text-xs text-gray-500">Select entries to manage them in bulk.</p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <label class="inline-flex items-center text-sm text-gray-600 cursor-pointer">
                                                <input type="checkbox" id="select-all-permissions" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                                <span class="ml-2">Select all</span>
                                            </label>
                                            <button type="button" 
                                                    id="bulk-revoke-btn"
                                                    class="inline-flex items-center px-3 py-1.5 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 opacity-40 cursor-not-allowed"
                                                    disabled>
                                                <i class="fas fa-trash mr-2"></i>Remove Selected
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div id="currentPermissionsList" class="divide-y divide-gray-200">
                                        <div class="text-center py-8">
                                            <i class="fas fa-sync-alt fa-spin text-gray-400 text-xl mb-2"></i>
                                            <p class="text-gray-500">Loading permissions...</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-6 border-t border-gray-200 pt-6">
                                    <h4 class="text-sm font-medium text-gray-900 mb-4">Add New Permission</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Principal Type</label>
                                            <select id="add-principal-type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                <option value="user">User</option>
                                                <option value="group">Group</option>
                                            </select>
                                            <p class="mt-1 text-xs text-gray-500">Switch to load matching principals.</p>
                                        </div>
                                        
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Principals</label>
                                            <select id="add-principal-id" multiple class="w-full h-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            </select>
                                            <p class="mt-1 text-xs text-gray-500">Hold <?php echo strpos(PHP_OS, 'Darwin') === 0 ? '⌘' : 'Ctrl'; ?> or Shift to choose multiple principals.</p>
                                        </div>
                                        
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Permissions</label>
                                            <select id="add-permissions" multiple class="w-full h-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                <option value="read">Read</option>
                                                <option value="write">Write</option>
                                                <option value="read_execute">Read &amp; Execute</option>
                                                <option value="modify">Modify</option>
                                                <option value="full_control">Full Control</option>
                                            </select>
                                            <p class="mt-1 text-xs text-gray-500">Select one or more permissions to grant in a single action.</p>
                                        </div>
                                        
                                        <div class="flex items-end">
                                            <button type="button" 
                                                    id="add-permission-btn"
                                                    class="w-full inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                <i class="fas fa-plus mr-2"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" 
                            id="closeModalBtn"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/templately-utils.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get modal elements
    const modal = document.getElementById('permissionsModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalResourceName = document.getElementById('modalResourceName');
    const modalResourceOwner = document.getElementById('modalResourceOwner');
    const currentPermissionsList = document.getElementById('currentPermissionsList');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const selectAllPermissionsCheckbox = document.getElementById('select-all-permissions');
    const bulkRevokeBtn = document.getElementById('bulk-revoke-btn');
    
    // Get form elements
    const principalTypeSelect = document.getElementById('add-principal-type');
    const principalIdSelect = document.getElementById('add-principal-id');
    const permissionsSelect = document.getElementById('add-permissions');
    const addPermissionBtn = document.getElementById('add-permission-btn');
    
    const selectedPermissionEntries = new Map();
    let currentResourceOwnerId = null;
    
    const escapeHtml = (value) => {
        if (value === null || value === undefined) {
            return '';
        }
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    const sanitizeForId = (value) => {
        if (value === null || value === undefined) {
            return '';
        }
        return String(value)
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            || 'entry';
    };

    const formatTitleCase = (value) => {
        if (!value) {
            return '';
        }

        return String(value)
            .split(/[_\s]+/)
            .filter(Boolean)
            .map(part => part.charAt(0).toUpperCase() + part.slice(1))
            .join(' ');
    };
    
    // Set up modal close functionality
    closeModalBtn.addEventListener('click', closePermissionsModal);
    
    // Handle click outside modal to close it
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closePermissionsModal();
        }
    });
    
    // Handle principal type change
    principalTypeSelect.addEventListener('change', function() {
        const principalType = this.value;
        
        // Update the principal ID select options based on principal type
        updatePrincipalOptions(principalType);
        
        // Reset the principal ID select
        principalIdSelect.value = '';
    });
    
    // Handle add permission button click
    addPermissionBtn.addEventListener('click', function() {
        const resourceId = modal.getAttribute('data-resource-id');
        const resourceType = modal.getAttribute('data-resource-type');
        const principalType = principalTypeSelect.value;
        const principalIds = Array.from(principalIdSelect.selectedOptions || [])
            .map(option => option.value)
            .filter(value => value !== '')
            .map(value => parseInt(value, 10))
            .filter(Number.isFinite);
        const permissions = Array.from(permissionsSelect.selectedOptions || [])
            .map(option => option.value)
            .filter(value => value !== '');

        if (!resourceId || !resourceType || principalIds.length === 0 || permissions.length === 0) {
            showAlert('Select at least one principal and one permission.', 'error');
            return;
        }

        grantPermissions(resourceId, resourceType, principalIds, permissions, principalType);
    });

    if (selectAllPermissionsCheckbox) {
        selectAllPermissionsCheckbox.addEventListener('change', handleSelectAllToggle);
    }

    if (bulkRevokeBtn) {
        bulkRevokeBtn.addEventListener('click', handleBulkRevoke);
    }
    
    // Set up buttons to manage permissions
    document.querySelectorAll('.manage-permissions-btn').forEach(button => {
        button.addEventListener('click', function() {
            const resourceId = this.getAttribute('data-resource-id');
            const resourceType = this.getAttribute('data-resource-type');
            const resourceName = this.getAttribute('data-resource-name');
            
            openPermissionsModal(resourceId, resourceType, resourceName);
        });
    });
    
    // Function to update principal options based on principal type
    function updatePrincipalOptions(principalType) {
        // Clear existing options except the placeholder
        while (principalIdSelect.firstChild) {
            principalIdSelect.removeChild(principalIdSelect.firstChild);
        }
        
        // Add placeholder option
        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = principalType === 'user' ? 'Select user(s)...' : 'Select group(s)...';
        placeholderOption.disabled = true;
        placeholderOption.hidden = true;
        principalIdSelect.appendChild(placeholderOption);
        
        // Add options based on principal type
        if (principalType === 'user') {
            // Add user options
            <?php foreach ($users as $user): ?>
            const userOption = document.createElement('option');
            userOption.value = '<?php echo $user['id'] ?>';
            userOption.textContent = '<?php echo esc($user['username']) ?>' +
                <?php $roleSummary = empty($user['roles']) ? '' : 'Roles: ' . implode(', ', $user['roles']); ?>
                <?php $userGroupSummary = empty($user['user_groups']) ? '' : 'Groups: ' . implode(', ', array_map(fn($group) => $group['name'], $user['user_groups'])); ?>
                <?php $parts = array_filter([$roleSummary, $userGroupSummary]); ?>
                <?php if (!empty($parts)): ?>
                ' (<?php echo esc(implode(' | ', $parts)) ?>)'
                <?php else: ?>
                ''
                <?php endif; ?>;
            userOption.setAttribute('data-type', 'user');
            principalIdSelect.appendChild(userOption);
            <?php endforeach; ?>
        } else if (principalType === 'group') {
            // Add group options
            <?php foreach ($userGroups as $group): ?>
            const groupOption = document.createElement('option');
            groupOption.value = '<?php echo $group['id'] ?>';
            groupOption.textContent = '<?php echo esc($group['name']) ?>' + 
                <?php if (!empty($group['description'])): ?> 
                ' (<?php echo esc($group['description']) ?>)'
                <?php else: ?> 
                ''
                <?php endif; ?>;
            groupOption.setAttribute('data-type', 'group');
            principalIdSelect.appendChild(groupOption);
            <?php endforeach; ?>
        }
        
        // Update the label
        const label = principalIdSelect.previousElementSibling;
        if (label && label.tagName === 'LABEL') {
            label.textContent = principalType === 'user' ? 'Users' : 'Groups';
        }
        
        // Reset the select
        Array.from(principalIdSelect.options).forEach(option => {
            option.selected = false;
        });
        principalIdSelect.selectedIndex = -1;
    }
    
    function openPermissionsModal(resourceId, resourceType, resourceName) {
        // Set modal attributes for later use
        modal.setAttribute('data-resource-id', resourceId);
        modal.setAttribute('data-resource-type', resourceType);
        
        // Update modal content
        modalTitle.textContent = `Manage Permissions - ${resourceType} ${resourceId}`;
        modalResourceName.textContent = resourceName;
        
        // Load resource owner and permissions
        loadResourceOwner(resourceId, resourceType);
        loadResourcePermissions(resourceId, resourceType);
        
        // Show the modal
        modal.classList.remove('hidden');
        
        // Reset form
        principalTypeSelect.value = 'user';
        updatePrincipalOptions('user');
        Array.from(principalIdSelect.options).forEach(option => {
            option.selected = false;
        });
        Array.from(permissionsSelect.options).forEach(option => {
            option.selected = false;
        });

        selectedPermissionEntries.clear();
        if (selectAllPermissionsCheckbox) {
            selectAllPermissionsCheckbox.checked = false;
            selectAllPermissionsCheckbox.indeterminate = false;
        }
        refreshBulkActionsUI();
    }
    
    function closePermissionsModal() {
        modal.classList.add('hidden');
    }
    
    function loadResourceOwner(resourceId, resourceType) {
        currentResourceOwnerId = null;
        modalResourceOwner.textContent = 'Loading owner…';
        modalResourceOwner.removeAttribute('data-owner-id');
    }
    
    function loadResourcePermissions(resourceId, resourceType) {
        fetch(`/advanced-permissions/user-resource-permissions?resourceId=${resourceId}&resourceType=${resourceType}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ownerInfo = data.owner || null;

                    if (ownerInfo) {
                        const ownerLabel = ownerInfo.display
                            || ownerInfo.username
                            || ownerInfo.email
                            || `User #${ownerInfo.id}`;
                        modalResourceOwner.textContent = ownerLabel;
                        modalResourceOwner.setAttribute('data-owner-id', ownerInfo.id);
                        const parsedOwnerId = parseInt(ownerInfo.id, 10);
                        currentResourceOwnerId = Number.isFinite(parsedOwnerId) ? parsedOwnerId : null;
                    } else {
                        modalResourceOwner.textContent = 'Unassigned';
                        modalResourceOwner.removeAttribute('data-owner-id');
                        currentResourceOwnerId = null;
                    }

                    displayCurrentPermissions(data.permissions, resourceId, resourceType);
                } else {
                    currentPermissionsList.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-triangle text-red-400 text-xl mb-2"></i>
                            <p class="text-gray-500">Error loading permissions: ${data.message}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading permissions:', error);
                currentPermissionsList.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-red-400 text-xl mb-2"></i>
                        <p class="text-gray-500">Error loading permissions</p>
                    </div>
                `;
            });
    }
    
    function displayCurrentPermissions(permissions, resourceId, resourceType) {
        selectedPermissionEntries.clear();

        if (selectAllPermissionsCheckbox) {
            selectAllPermissionsCheckbox.checked = false;
            selectAllPermissionsCheckbox.indeterminate = false;
        }

        refreshBulkActionsUI();

        if (!Array.isArray(permissions) || permissions.length === 0) {
            currentPermissionsList.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-lock-open text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">No permissions set for this resource</p>
                    <p class="text-gray-400 text-sm mt-1">Add permissions using the form above</p>
                </div>
            `;
            return;
        }

        const rows = permissions.map((perm) => {
            const principalType = perm.principal_type || 'user';
            const principalId = perm.principal_id ?? perm.principalId ?? '';
            const permissionName = perm.permission_name || perm.permission || '';
            const permissionLabel = permissionName ? formatTitleCase(permissionName) : 'N/A';

            let iconClass = 'fa-user';
            let bgColor = 'bg-indigo-100';
            let textColor = 'text-indigo-600';
            let principalLabel = 'User';
            let principalBadgeClass = 'bg-indigo-100 text-indigo-700';

            if (principalType === 'group') {
                iconClass = 'fa-users';
                bgColor = 'bg-purple-100';
                textColor = 'text-purple-600';
                principalLabel = 'Group';
                principalBadgeClass = 'bg-purple-100 text-purple-700';
            }

            const numericPrincipalId = parseInt(principalId, 10);
            const isOwner = principalType === 'user'
                && currentResourceOwnerId !== null
                && Number.isFinite(numericPrincipalId)
                && numericPrincipalId === Number(currentResourceOwnerId);

            const ownerBadge = isOwner
                ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Owner</span>'
                : '';

            const principalName = principalType === 'group'
                ? (perm.group_name || `Group #${principalId}`)
                : (perm.user_name || `User #${principalId}`);

            const inherited = perm.inherited === true || perm.inherited === 1 || perm.inherited === '1';
            const inheritanceParts = [];
            if (perm.inheritance_source_type) {
                inheritanceParts.push(perm.inheritance_source_type);
            }
            if (perm.inheritance_source_id) {
                inheritanceParts.push(`#${perm.inheritance_source_id}`);
            }
            const inheritanceSuffix = inheritanceParts.length ? ` · ${escapeHtml(inheritanceParts.join(' '))}` : '';
            const inheritanceBadge = inherited
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Inherited${inheritanceSuffix}</span>`
                : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Direct</span>';

            const detailPieces = [];
            if (principalType === 'user' && perm.user_email) {
                detailPieces.push(`Email: ${perm.user_email}`);
            }
            if (principalType === 'group' && perm.group_description) {
                detailPieces.push(perm.group_description);
            }
            if (perm.permission_description) {
                detailPieces.push(perm.permission_description);
            }

            const detailLine = detailPieces.length
                ? `<div class="text-xs text-gray-500 mt-2">${detailPieces.map(piece => escapeHtml(piece)).join(' • ')}</div>`
                : '';

            const principalKey = perm.principal_key || `${principalType}:${principalId}:${permissionName}`;
            const checkboxId = `perm-${sanitizeForId(resourceType)}-${sanitizeForId(principalType)}-${sanitizeForId(principalId)}-${sanitizeForId(permissionName)}`;

            return `
                <div class="permission-entry p-4 flex items-center justify-between gap-6" data-principal-key="${escapeHtml(principalKey)}" data-principal-id="${escapeHtml(principalId)}" data-principal-type="${escapeHtml(principalType)}" data-permission="${escapeHtml(permissionName)}">
                    <div class="flex items-start gap-4">
                        <div class="pt-1">
                            <input type="checkbox" id="${escapeHtml(checkboxId)}" class="permission-select h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </div>
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full ${bgColor} flex items-center justify-center">
                                <i class="fas ${iconClass} ${textColor}"></i>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                                    ${escapeHtml(principalName)}
                                    ${ownerBadge}
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-600">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${principalBadgeClass}">${principalLabel}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">${escapeHtml(permissionLabel)}</span>
                                    ${inheritanceBadge}
                                </div>
                                ${detailLine}
                            </div>
                        </div>
                    </div>
                    <button type="button" 
                            class="single-revoke-btn inline-flex items-center px-3 py-1.5 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50"
                            data-principal-id="${escapeHtml(principalId)}"
                            data-principal-type="${escapeHtml(principalType)}"
                            data-permission="${escapeHtml(permissionName)}">
                        <i class="fas fa-trash mr-1"></i> Remove
                    </button>
                </div>
            `;
        });

        currentPermissionsList.innerHTML = rows.join('');
        attachPermissionSelectionHandlers(resourceId, resourceType);
        refreshBulkActionsUI();
    }

    function attachPermissionSelectionHandlers(resourceId, resourceType) {
        const rows = currentPermissionsList.querySelectorAll('.permission-entry');

        rows.forEach((row) => {
            const checkbox = row.querySelector('.permission-select');
            if (!checkbox) {
                return;
            }

            checkbox.addEventListener('change', () => {
                const entryKey = row.dataset.principalKey;

                if (!entryKey) {
                    return;
                }

                const parsedId = parseInt(row.dataset.principalId, 10);

                if (checkbox.checked) {
                    selectedPermissionEntries.set(entryKey, {
                        principalId: Number.isFinite(parsedId) ? parsedId : row.dataset.principalId,
                        principalType: row.dataset.principalType || 'user',
                        permission: row.dataset.permission || '',
                    });
                } else {
                    selectedPermissionEntries.delete(entryKey);
                }

                refreshBulkActionsUI();
            });

            row.addEventListener('click', (event) => {
                const interactiveElement = event.target.closest('button, a, input, label, select, textarea');
                if (interactiveElement) {
                    return;
                }

                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        currentPermissionsList.querySelectorAll('.single-revoke-btn').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const entry = {
                    principalId: (() => {
                        const raw = button.getAttribute('data-principal-id');
                        const parsed = parseInt(raw, 10);
                        return Number.isFinite(parsed) ? parsed : raw;
                    })(),
                    principalType: button.getAttribute('data-principal-type') || 'user',
                    permission: button.getAttribute('data-permission') || '',
                };

                requestRevoke(resourceId, resourceType, [entry], {
                    title: 'Revoke Permission',
                    message: 'Are you sure you want to revoke this permission?',
                });
            });
        });
    }

    function refreshBulkActionsUI() {
        const hasSelection = selectedPermissionEntries.size > 0;

        if (bulkRevokeBtn) {
            bulkRevokeBtn.disabled = !hasSelection;
            bulkRevokeBtn.classList.toggle('opacity-40', !hasSelection);
            bulkRevokeBtn.classList.toggle('cursor-not-allowed', !hasSelection);
        }

        if (selectAllPermissionsCheckbox) {
            const totalRows = currentPermissionsList.querySelectorAll('.permission-entry').length;
            const selectedCount = selectedPermissionEntries.size;

            if (totalRows === 0 || selectedCount === 0) {
                selectAllPermissionsCheckbox.checked = false;
                selectAllPermissionsCheckbox.indeterminate = false;
            } else if (selectedCount === totalRows) {
                selectAllPermissionsCheckbox.checked = true;
                selectAllPermissionsCheckbox.indeterminate = false;
            } else {
                selectAllPermissionsCheckbox.checked = false;
                selectAllPermissionsCheckbox.indeterminate = true;
            }
        }
    }

    function handleSelectAllToggle(event) {
        const checkboxes = currentPermissionsList.querySelectorAll('.permission-select');
        checkboxes.forEach((checkbox) => {
            checkbox.checked = event.target.checked;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function handleBulkRevoke() {
        const resourceId = modal.getAttribute('data-resource-id');
        const resourceType = modal.getAttribute('data-resource-type');

        if (!resourceId || !resourceType || selectedPermissionEntries.size === 0) {
            return;
        }

        const entries = Array.from(selectedPermissionEntries.values());
        requestRevoke(resourceId, resourceType, entries, {
            title: 'Revoke Selected Permissions',
            message: `Revoke ${entries.length} selected permission${entries.length > 1 ? 's' : ''}?`,
        });
    }

    async function requestRevoke(resourceId, resourceType, entries, confirmOptions = {}) {
        if (!Array.isArray(entries) || entries.length === 0) {
            return;
        }

        const confirmTitle = confirmOptions.title || (entries.length > 1 ? 'Revoke Selected Permissions' : 'Revoke Permission');
        const confirmMessage = confirmOptions.message || (entries.length > 1
            ? `Are you sure you want to revoke ${entries.length} permissions?`
            : 'Are you sure you want to revoke this permission?');

        let confirmed = true;

        if (window.Templately && Templately.Modal && typeof Templately.Modal.confirm === 'function') {
            confirmed = await Templately.Modal.confirm(confirmMessage, {
                title: confirmTitle,
                confirmText: 'Revoke',
                cancelText: 'Cancel',
                intent: 'danger'
            });
        } else if (!window.confirm(confirmMessage)) {
            confirmed = false;
        }

        if (!confirmed) {
            return;
        }

        const numericResourceId = parseInt(resourceId, 10);

        const payloadEntries = entries
            .map((entry) => {
                const parsedId = parseInt(entry.principalId, 10);
                const permissionName = entry.permission || '';

                if (!permissionName) {
                    return null;
                }

                return {
                    principalId: Number.isFinite(parsedId) ? parsedId : entry.principalId,
                    principalType: entry.principalType || 'user',
                    permission: permissionName,
                };
            })
            .filter(Boolean);

        if (payloadEntries.length === 0) {
            showAlert('Select at least one permission to revoke.', 'error');
            return;
        }

        fetch('/advanced-permissions/revoke-resource-permission', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
            },
            body: JSON.stringify({
                resourceId: Number.isFinite(numericResourceId) ? numericResourceId : resourceId,
                resourceType: resourceType,
                entries: payloadEntries,
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message || 'Permission(s) revoked successfully.', 'success');
                selectedPermissionEntries.clear();
                if (selectAllPermissionsCheckbox) {
                    selectAllPermissionsCheckbox.checked = false;
                    selectAllPermissionsCheckbox.indeterminate = false;
                }
                refreshBulkActionsUI();
                loadResourcePermissions(resourceId, resourceType);
            } else {
                showAlert(data.message || 'Failed to revoke permissions.', 'error');
            }
        })
        .catch(error => {
            console.error('Error revoking permission:', error);
            showAlert('Error revoking permission.', 'error');
        });
    }

    function grantPermissions(resourceId, resourceType, principalIds, permissions, principalType = 'user') {
        const numericResourceId = parseInt(resourceId, 10);

        fetch('/advanced-permissions/grant-resource-permission', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
            },
            body: JSON.stringify({
                resourceId: Number.isFinite(numericResourceId) ? numericResourceId : resourceId,
                resourceType: resourceType,
                principalId: principalIds,
                permissions: permissions,
                principalType: principalType
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message || 'Permission(s) granted successfully.', 'success');
                loadResourcePermissions(resourceId, resourceType);
                Array.from(principalIdSelect.options).forEach(option => {
                    option.selected = false;
                });
                Array.from(permissionsSelect.options).forEach(option => {
                    option.selected = false;
                });
                principalIdSelect.scrollTop = 0;
                permissionsSelect.scrollTop = 0;
            } else {
                showAlert(data.message || 'Failed to grant permissions.', 'error');
            }
        })
        .catch(error => {
            console.error('Error granting permission:', error);
            showAlert('Error granting permission.', 'error');
        });
    }
    
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alertMessage');
        alertContainer.className = `mb-6 p-4 rounded-lg ${
            type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
            type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
            'bg-blue-100 text-blue-800 border border-blue-200'
        }`;
        alertContainer.textContent = message;
        alertContainer.classList.remove('hidden');
        
        setTimeout(() => {
            alertContainer.classList.add('hidden');
        }, 5000);
    }
});
</script>
<?php echo $this->endSection() ?>