<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>Manage Resource Permissions<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include('components/navigation', [
        'pageTitle' => 'Manage Resource Permissions',
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
            <p class="text-gray-600 mt-1">ID: <?php echo $resourceId ?> | Current Owner: 
                <span id="current-owner">
                    <?php if ($resourceOwner): ?>
                        <?php $userModel = model('CodeIgniter\Shield\Models\UserModel'); $owner = $userModel->find($resourceOwner); echo $owner ? $owner->username : 'Unknown'; ?>
                    <?php else: ?>
                        None (will be assigned to creator)
                    <?php endif; ?>
                </span>
            </p>
        </div>
        
        <div class="p-6">
            <!-- Current Permissions -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-list mr-2 text-gray-600"></i>Current Permissions
                </h3>
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <?php if (!empty($aclEntries)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Principal</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permission</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Granted By</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inherited</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($aclEntries as $entry): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php
                                            if ($entry['principal_type'] === 'user') {
                                                $userModel = model('CodeIgniter\Shield\Models\UserModel');
                                                $user = $userModel->find($entry['principal_id']);
                                                echo $user ? $user->username : 'Unknown User';
                                            } else {
                                                echo 'Group: ' . $entry['principal_id']; // In a real implementation, you would look up group names
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo ucfirst($entry['principal_type']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $entry['permission']['name'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if ($entry['granted_by']): ?>
                                                <?php $userModel = model('CodeIgniter\Shield\Models\UserModel'); $grantedBy = $userModel->find($entry['granted_by']); echo $grantedBy ? $grantedBy->username : 'Unknown'; ?>
                                            <?php else: ?>
                                                System
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $entry['inherited'] ? 'Yes' : 'No' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="#" class="text-red-600 hover:text-red-900 delete-permission" 
                                               data-entry-id="<?php echo $entry['id'] ?>"
                                               data-principal-id="<?php echo $entry['principal_id'] ?>"
                                               data-permission="<?php echo $entry['permission']['name'] ?>">Revoke</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-shield-alt text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No custom permissions set for this resource.</p>
                            <p class="text-gray-500 text-sm mt-1">Only the owner has full access by default.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Add Permission Form -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-plus-circle mr-2 text-green-600"></i>Add New Permission
                </h3>
                
                <div class="bg-gray-50 rounded-lg p-6">
                    <form id="add-permission-form" method="post" action="/acl/update/<?php echo $resourceType ?>/<?php echo $resourceId ?>">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="principal-select" class="block text-sm font-medium text-gray-700 mb-1">Grant To</label>
                                <select id="principal-select" name="principal_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="user">User</option>
                                    <option value="group">Group</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="user-select" class="block text-sm font-medium text-gray-700 mb-1">User/Group</label>
                                <select id="user-select" name="principal_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id'] ?>"><?php echo $user['username'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="permission-select" class="block text-sm font-medium text-gray-700 mb-1">Permission</label>
                                <select id="permission-select" name="permission" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <?php foreach ($availablePermissions as $permission): ?>
                                    <option value="<?php echo $permission['name'] ?>"><?php echo $permission['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-plus mr-2"></i>Add Permission
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Ownership -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-user-cog mr-2 text-purple-600"></i>Change Ownership
                </h3>
                
                <div class="bg-gray-50 rounded-lg p-6">
                    <form id="change-owner-form">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="new-owner-select" class="block text-sm font-medium text-gray-700 mb-1">New Owner</label>
                                <select id="new-owner-select" name="new_owner_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Select new owner...</option>
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id'] ?>" 
                                        <?php echo ($resourceOwner == $user['id']) ? 'selected' : '' ?>>
                                        <?php echo $user['username'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="flex items-end">
                                <button type="button" 
                                        id="change-owner-btn"
                                        class="w-full inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                                    <i class="fas fa-exchange-alt mr-2"></i>Change Owner
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Inheritance Section (for filled files) -->
            <?php if ($resourceType === 'template'): ?>
            <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-sitemap mr-2 text-blue-600"></i>Permission Inheritance
                </h3>
                
                <div class="bg-gray-50 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-md font-medium text-gray-900">Apply template permissions to filled files</h4>
                            <p class="text-gray-500 text-sm mt-1">
                                This will copy the current permissions of this template to all filled files created from it.
                                <?php if (isset($aclSettings['inheritance_cascading']) && $aclSettings['inheritance_cascading']): ?>
                                Future changes to template permissions will automatically apply to existing filled files.
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="ml-auto">
                            <button type="button" 
                                    id="inherit-permissions-btn"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-sync-alt mr-2"></i>Inherit Permissions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle add permission form
    const addPermissionForm = document.getElementById('add-permission-form');
    if (addPermissionForm) {
        addPermissionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(addPermissionForm);
            const principalType = formData.get('principal_type');
            const principalId = formData.get('principal_id');
            const permission = formData.get('permission');
            
            fetch(`/acl/update/<?php echo $resourceType ?>/<?php echo $resourceId ?>`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?php echo csrf_hash(); ?>'
                },
                body: JSON.stringify({
                    permissions: [permission],
                    user_ids: principalType === 'user' ? [parseInt(principalId)] : [],
                    group_ids: principalType === 'group' ? [parseInt(principalId)] : []
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding permission: ' + error.message);
            });
        });
    }
    
    // Handle change owner button
    const changeOwnerBtn = document.getElementById('change-owner-btn');
    if (changeOwnerBtn) {
        changeOwnerBtn.addEventListener('click', function() {
            const newOwnerId = document.getElementById('new-owner-select').value;
            
            if (!newOwnerId) {
                alert('Please select a new owner.');
                return;
            }
            
            fetch(`/acl/change-owner/<?php echo $resourceType ?>/<?php echo $resourceId ?>`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': '<?php echo csrf_hash(); ?>'
                },
                body: JSON.stringify({
                    new_owner_id: parseInt(newOwnerId)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    document.getElementById('current-owner').textContent = 
                        Array.from(document.getElementById('new-owner-select').options)
                            .find(option => option.value == newOwnerId)?.textContent || 'Unknown';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error changing owner: ' + error.message);
            });
        });
    }
    
    // Handle inherit permissions button (for templates)
    const inheritPermissionsBtn = document.getElementById('inherit-permissions-btn');
    if (inheritPermissionsBtn) {
        inheritPermissionsBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to apply template permissions to all filled files? This will overwrite any existing specific permissions on the filled files.')) {
                fetch(`/acl/inherit-permissions/<?php echo $resourceId ?>`, {
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
                        alert(data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error inheriting permissions: ' + error.message);
                });
            }
        });
    }
    
    // Handle delete permission
    document.querySelectorAll('.delete-permission').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const entryId = this.getAttribute('data-entry-id');
            const principalId = this.getAttribute('data-principal-id');
            const permission = this.getAttribute('data-permission');
            
            if (confirm(`Are you sure you want to revoke the "${permission}" permission for this ${this.getAttribute('data-principal-type') || 'user'}?`)) {
                // In a full implementation, this would make an API call to remove the specific ACL entry
                alert('In a full implementation, this would remove the ACL entry. For now, this is a placeholder.');
            }
        });
    });
    
    // Handle principal type change
    const principalSelect = document.getElementById('principal-select');
    const userSelect = document.getElementById('user-select');
    
    if (principalSelect && userSelect) {
        principalSelect.addEventListener('change', function() {
            // In a full implementation, this would load different lists based on type
            if (this.value === 'group') {
                // Load groups instead of users
                // This is a simplified implementation
            }
        });
    }
});
</script>
<?php echo $this->endSection() ?>