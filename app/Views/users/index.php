<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>User Management<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include('components/navigation', [
        'pageTitle' => 'Access Control',
        'pageIcon' => 'users-cog',
        'stickyNav' => false,
        'showWelcome' => false
    ]) ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('content') ?>
<div class="px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
        <p class="mt-2 text-sm text-gray-600">Manage user accounts and role assignments</p>
    </div>

    <!-- Alert Messages -->
    <div id="alertMessage" class="hidden mb-6"></div>

    <!-- Quick Stats -->
    <?php
    // Get current user role
    $currentUser = auth()->user();
    $currentUserRole = 'viewer'; // default
    $currentUserRoleLabel = 'Viewer';

    // Find current user in users array to get their role
    foreach ($users as $user) {
        if ($user['id'] == $currentUser->id) {
            $currentUserRole = $user['role'] ?? 'viewer';
            break;
        }
    }

    // Map roles to display labels and Font Awesome icons
    $roleInfo = [
        'superadmin' => [
            'label' => 'System Administrator',
            'color' => 'red',
            'icon' => 'shield-halved'
        ],
        'manager' => [
            'label' => 'Manager',
            'color' => 'indigo',
            'icon' => 'briefcase'
        ],
        'editor' => [
            'label' => 'Editor',
            'color' => 'blue',
            'icon' => 'pen-to-square'
        ],
        'contributor' => [
            'label' => 'Contributor',
            'color' => 'green',
            'icon' => 'file-pen'
        ],
        'viewer' => [
            'label' => 'Viewer',
            'color' => 'gray',
            'icon' => 'eye'
        ]
    ];

    $currentUserRoleLabel = $roleInfo[$currentUserRole]['label'] ?? 'Viewer';
    $totalUsers = count($users);
    ?>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Total Users Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Users</p>
                    <p class="text-4xl font-bold text-gray-900 mt-2"><?php echo $totalUsers ?></p>
                </div>
                <div class="w-16 h-16 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-indigo-600 text-4xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Your Role Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wide">Your Role</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo $currentUserRoleLabel ?></p>
                </div>
                <div class="w-16 h-16 bg-<?php echo $roleInfo[$currentUserRole]['color'] ?>-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-<?php echo $roleInfo[$currentUserRole]['icon'] ?> text-<?php echo $roleInfo[$currentUserRole]['color'] ?>-600 text-4xl"></i>
                </div>
            </div>
        </div>
    </div>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-900">
            <i class="fas fa-users mr-2 text-indigo-600"></i>All Users
        </h2>
        <?php if ($currentUser->inGroup('superadmin', 'manager')) : ?>
        <button onclick="openCreateModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition duration-200">
            <i class="fas fa-plus mr-2"></i>Create User
        </button>
        <?php endif; ?>
    </div>
        <!-- Search and Filter -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between">
                <div class="flex-1 max-w-lg">
                    <div class="relative">
                        <input type="text" 
                               id="searchUsers" 
                               placeholder="Search users by name or email..."
                               class="<?= ui_input('search') ?>">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                <div class="ml-4">
                    <select id="filterRole" 
                            class="<?= ui_select(['px-4', 'py-2', 'bg-white', 'text-gray-900']) ?>">
                        <option value="">All Roles</option>
                        <option value="superadmin">System Administrator</option>
                        <option value="manager">Manager</option>
                        <option value="editor">Editor</option>
                        <option value="contributor">Contributor</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody" class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user) : ?>
                    <tr class="hover:bg-gray-50 transition duration-150" 
                        data-user-id="<?php echo $user['id'] ?>"
                        data-user-role="<?php echo $user['role'] ?? 'user' ?>"
                        data-user-name="<?php echo esc($user['username']) ?>">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold shadow-sm">
                                        <?php echo strtoupper(substr($user['username'], 0, 2)) ?>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo esc($user['username']) ?>
                                        <?php if ($user['id'] === $currentUser->id) : ?>
                                            <span class="ml-2 text-xs text-indigo-600">(You)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo esc($user['email']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $roleConfig = [
                                'superadmin' => ['color' => 'red', 'icon' => 'crown', 'label' => 'Superadmin'],
                                'manager' => ['color' => 'indigo', 'icon' => 'briefcase', 'label' => 'Manager'],
                                'editor' => ['color' => 'blue', 'icon' => 'pen-to-square', 'label' => 'Editor'],
                                'contributor' => ['color' => 'green', 'icon' => 'file-pen', 'label' => 'Contributor'],
                                'viewer' => ['color' => 'gray', 'icon' => 'eye', 'label' => 'Viewer']
                            ];
                            $userRole = $user['role'] ?? 'viewer';
                            $config = $roleConfig[$userRole] ?? $roleConfig['viewer'];
                            ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-<?php echo $config['color'] ?>-100 text-<?php echo $config['color'] ?>-800">
                                <i class="fas fa-<?php echo $config['icon'] ?> mr-1.5"></i>
                                <?php echo $config['label'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($user['active']) : ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Active
                                </span>
                            <?php else : ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <i class="fas fa-ban mr-1"></i>Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo date('M j, Y', strtotime($user['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                <?php if ($currentUser->inGroup('superadmin', 'manager')) : ?>
                                <button onclick="openEditUserModal(<?php echo $user['id'] ?>)" 
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded-md transition duration-150">
                                    <i class="fas fa-edit mr-1.5"></i>Edit
                                </button>
                                <?php endif; ?>
                                <?php if ($currentUser->inGroup('superadmin') && $user['id'] !== $currentUser->id) : ?>
                                <button onclick="deleteUser(<?php echo $user['id'] ?>, '<?php echo esc($user['username']) ?>')" 
                                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-900 hover:bg-red-50 rounded-md transition duration-150">
                                    <i class="fas fa-trash mr-1.5"></i>Delete
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Role Descriptions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-info-circle mr-2 text-indigo-500"></i>Role Permissions
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="p-4 bg-red-50 rounded-lg border border-red-100">
                <div class="flex items-center mb-2">
                    <i class="fas fa-shield-halved text-red-600 mr-2"></i>
                    <h4 class="font-semibold text-red-900">System Administrator</h4>
                </div>
                <ul class="text-sm text-red-800 space-y-1">
                    <li>• Full system access</li>
                    <li>• Manage all users & roles</li>
                    <li>• Access all templates & files</li>
                    <li>• System configuration</li>
                </ul>
            </div>
            
            <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                <div class="flex items-center mb-2">
                    <i class="fas fa-briefcase text-indigo-600 mr-2"></i>
                    <h4 class="font-semibold text-indigo-900">Manager</h4>
                </div>
                <ul class="text-sm text-indigo-800 space-y-1">
                    <li>• Access all templates & files</li>
                    <li>• Create & edit documents</li>
                    <li>• Manage all user content</li>
                    <li>• Delete files & templates</li>
                </ul>
            </div>
            
            <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                <div class="flex items-center mb-2">
                    <i class="fas fa-pen-to-square text-blue-600 mr-2"></i>
                    <h4 class="font-semibold text-blue-900">Editor</h4>
                </div>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• Create & edit all documents</li>
                    <li>• Upload & manage templates</li>
                    <li>• Fill templates with data</li>
                    <li>• Export documents to PDF/DOCX</li>
                </ul>
            </div>
            
            <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                <div class="flex items-center mb-2">
                    <i class="fas fa-file-pen text-green-600 mr-2"></i>
                    <h4 class="font-semibold text-green-900">Contributor</h4>
                </div>
                <ul class="text-sm text-green-800 space-y-1">
                    <li>• Create own documents</li>
                    <li>• Edit own files only</li>
                    <li>• Use existing templates</li>
                    <li>• Export own documents</li>
                </ul>
            </div>
            
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex items-center mb-2">
                    <i class="fas fa-eye text-gray-600 mr-2"></i>
                    <h4 class="font-semibold text-gray-900">Viewer</h4>
                </div>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• View all documents</li>
                    <li>• Download files</li>
                    <li>• Read-only access</li>
                    <li>• Cannot create or edit</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- Create User Modal -->
<div id="createUserModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-xl bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-user-plus mr-2 text-indigo-600"></i>Create New User
            </h3>
            <button onclick="closeCreateUserModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="createUserForm" class="space-y-4">
            <div>
                <label for="newUsername" class="<?= ui_label() ?>">
                    <i class="fas fa-user mr-1"></i>Username
                </label>
                <input type="text" 
                       id="newUsername" 
                       name="username"
                       required
                       class="<?= ui_input() ?>"
                       placeholder="Enter username">
            </div>
            
            <div>
                <label for="newEmail" class="<?= ui_label() ?>">
                    <i class="fas fa-envelope mr-1"></i>Email
                </label>
                <input type="email" 
                       id="newEmail" 
                       name="email"
                       required
                       class="<?= ui_input() ?>"
                       placeholder="Enter email address">
            </div>
            
            <div>
                <label for="newPassword" class="<?= ui_label() ?>">
                    <i class="fas fa-lock mr-1"></i>Password
                </label>
                <input type="password" 
                       id="newPassword" 
                       name="password"
                       required
                       minlength="8"
                       class="<?= ui_input() ?>"
                       placeholder="Enter password (min 8 characters)">
            </div>
            
            <div>
                <label for="newRole" class="<?= ui_label() ?>">
                    <i class="fas fa-shield-alt mr-1"></i>Role
                </label>
                <select id="newRole" 
                        name="role"
                        required
                        class="<?= ui_select() ?>">
                    <option value="viewer">Viewer - Read-only access</option>
                    <option value="contributor">Contributor - Create own documents</option>
                    <option value="editor">Editor - Edit all documents</option>
                    <option value="manager">Manager - Oversee & approve</option>
                    <?php if ($currentUser->inGroup('superadmin')) : ?>
                    <option value="superadmin">System Administrator - Full control</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                <button type="button" 
                        onclick="closeCreateUserModal()"
                        class="<?= ui_button('ghost', 'md') ?>">
                    Cancel
                </button>
                <button type="submit" 
                        class="<?= ui_button('primary', 'md') ?>">
                    <i class="fas fa-plus mr-2"></i>Create User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-xl bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-user-edit mr-2 text-indigo-600"></i>Edit User
            </h3>
            <button onclick="closeEditUserModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="editUserForm" class="space-y-4">
            <input type="hidden" id="editUserId" name="user_id">
            
            <div>
                <label for="editUsername" class="<?= ui_label() ?>">
                    <i class="fas fa-user mr-1"></i>Username
                </label>
                <input type="text" 
                       id="editUsername" 
                       name="username"
                       required
                       class="<?= ui_input() ?>"
                       placeholder="Enter username">
            </div>
            
            <div>
                <label for="editEmail" class="<?= ui_label() ?>">
                    <i class="fas fa-envelope mr-1"></i>Email
                </label>
                <input type="email" 
                       id="editEmail" 
                       name="email"
                       required
                       class="<?= ui_input() ?>"
                       placeholder="Enter email address">
            </div>
            
            <div>
                <label for="editRole" class="<?= ui_label() ?>">
                    <i class="fas fa-shield-alt mr-1"></i>Role
                </label>
                <select id="editRole" 
                        name="role"
                        required
                        class="<?= ui_select() ?>">
                    <option value="viewer">Viewer - Read-only access</option>
                    <option value="contributor">Contributor - Create own documents</option>
                    <option value="editor">Editor - Edit all documents</option>
                    <option value="manager">Manager - Oversee & approve</option>
                    <?php if ($currentUser->inGroup('superadmin')) : ?>
                    <option value="superadmin">System Administrator - Full control</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <div>
                <label class="<?= ui_label() ?>">
                    <i class="fas fa-lock mr-1"></i>Status
                </label>
                <div class="flex items-center space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" 
                               name="active" 
                               value="1" 
                               id="editStatusActive"
                               class="form-radio h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">Active</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" 
                               name="active" 
                               value="0" 
                               id="editStatusInactive"
                               class="form-radio h-4 w-4 text-indigo-600 focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">Inactive</span>
                    </label>
                </div>
            </div>
            
            <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                <button type="button" 
                        onclick="closeEditUserModal()"
                        class="<?= ui_button('ghost', 'md') ?>">
                    Cancel
                </button>
                <button type="submit" 
                        class="<?= ui_button('primary', 'md') ?>">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php echo $this->endSection() ?>

<?php echo $this->section('pageScripts') ?>
<script src="/assets/js/templately-utils.js"></script>
<script>
// Search and filter functionality
document.getElementById('searchUsers').addEventListener('input', filterUsers);
document.getElementById('filterRole').addEventListener('change', filterUsers);

function filterUsers() {
    const searchTerm = document.getElementById('searchUsers').value.toLowerCase();
    const roleFilter = document.getElementById('filterRole').value;
    const rows = document.querySelectorAll('#usersTableBody tr');
    
    rows.forEach(row => {
        const userName = row.dataset.userName.toLowerCase();
        const userRole = row.dataset.userRole;
        
        const matchesSearch = userName.includes(searchTerm);
        const matchesRole = !roleFilter || userRole === roleFilter;
        
        row.style.display = matchesSearch && matchesRole ? '' : 'none';
    });
}

// Create User Modal
function openCreateModal() {
    document.getElementById('createUserModal').classList.remove('hidden');
    document.getElementById('createUserForm').reset();
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').classList.add('hidden');
}

document.getElementById('createUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        username: document.getElementById('newUsername').value,
        email: document.getElementById('newEmail').value,
        password: document.getElementById('newPassword').value,
        role: document.getElementById('newRole').value
    };
    
    try {
        const response = await Templately.Http.fetch('/users/create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            Templately.Alert.showAlert(result.message, 'success');
            closeCreateUserModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            Templately.Alert.showAlert(result.message, 'error');
        }
    } catch (error) {
        Templately.Alert.showAlert('An error occurred while creating the user.', 'error');
    }
});

// Edit User Modal
async function openEditUserModal(userId) {
    try {
        const response = await Templately.Http.fetch(`/users/get/${userId}`);
        const result = await response.json();
        
        if (result.success) {
            const user = result.user;
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editRole').value = user.role;
            
            if (user.active) {
                document.getElementById('editStatusActive').checked = true;
            } else {
                document.getElementById('editStatusInactive').checked = true;
            }
            
            document.getElementById('editUserModal').classList.remove('hidden');
        } else {
            Templately.Alert.showAlert(result.message, 'error');
        }
    } catch (error) {
        Templately.Alert.showAlert('An error occurred while loading user data.', 'error');
    }
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}

document.getElementById('editUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const userId = document.getElementById('editUserId').value;
    const formData = {
        user_id: userId,
        username: document.getElementById('editUsername').value,
        email: document.getElementById('editEmail').value,
        role: document.getElementById('editRole').value,
        active: document.querySelector('input[name="active"]:checked').value
    };
    
    try {
        const response = await Templately.Http.fetch('/users/update', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            Templately.Alert.showAlert(result.message, 'success');
            closeEditUserModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            Templately.Alert.showAlert(result.message, 'error');
        }
    } catch (error) {
        Templately.Alert.showAlert('An error occurred while updating the user.', 'error');
    }
});

// Delete User
async function deleteUser(userId, username) {
    const confirmed = await Templately.Modal.confirm(
        `Are you sure you want to delete user "${username}"? This action cannot be undone.`,
        {
            title: 'Delete User',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            intent: 'danger'
        }
    );
    
    if (!confirmed) {
        return;
    }
    
    try {
        const response = await Templately.Http.fetch(`/users/delete/${userId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        
        const result = await response.json();
        
        if (result.success) {
            Templately.Alert.showAlert(result.message, 'success');
            document.querySelector(`tr[data-user-id="${userId}"]`).remove();
        } else {
            Templately.Alert.showAlert(result.message, 'error');
        }
    } catch (error) {
        Templately.Alert.showAlert('An error occurred while deleting the user.', 'error');
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const createModal = document.getElementById('createUserModal');
    const editModal = document.getElementById('editUserModal');
    
    if (event.target === createModal) {
        closeCreateModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
}
</script>
<?php echo $this->endSection() ?>
