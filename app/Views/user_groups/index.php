<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>User Groups<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include('components/navigation', [
        'pageTitle' => 'User Groups',
        'pageIcon' => 'users',
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
                <i class="fas fa-users mr-2 text-blue-500"></i>User Groups
            </h2>
            <p class="text-gray-600 mt-1">Manage user groups and their members</p>
        </div>
        
        <div class="p-6">
            <!-- Create Group Form -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-plus-circle mr-2 text-green-500"></i>Create New Group
                    </h3>
                </div>
                
                <form id="create-group-form" method="post" action="/user-groups/create" class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Group Name</label>
                            <input type="text" 
                                   name="name" 
                                   id="group-name" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                   placeholder="Enter group name"
                                   required>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <input type="text" 
                                   name="description" 
                                   id="group-description" 
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                   placeholder="Enter group description">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-plus mr-2"></i>Create Group
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- User Groups List -->
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-list mr-2 text-blue-500"></i>User Groups
                    </h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        <?php echo count($userGroups) ?> Groups
                    </span>
                </div>
                
                <?php if (empty($userGroups)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">No User Groups</h3>
                    <p class="text-gray-500">Create your first user group using the form above.</p>
                </div>
                <?php else: ?>
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Members</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($userGroups as $group): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo esc($group['name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?php echo esc($group['description'] ?? 'No description') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo $group['member_count'] ?> members
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button type="button" 
                                            class="manage-group-members-btn text-indigo-600 hover:text-indigo-900 mr-3"
                                            data-group-id="<?php echo $group['id'] ?>"
                                            data-group-name="<?php echo esc($group['name']) ?>">
                                        <i class="fas fa-users mr-1"></i>Manage Members
                                    </button>
                                    <button type="button" 
                                            class="delete-group-btn text-red-600 hover:text-red-900"
                                            data-group-id="<?php echo $group['id'] ?>"
                                            data-group-name="<?php echo esc($group['name']) ?>">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Manage Group Members Modal -->
    <div id="manage-members-modal" class="hidden fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="manage-members-modal-title">
                                Manage Group Members
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">
                                    Add or remove members from group: <span id="group-name-display" class="font-medium"></span>
                                </p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Add Member Section -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-md font-medium text-gray-900 mb-3">
                                            <i class="fas fa-user-plus mr-2 text-green-500"></i>Add Member
                                        </h4>
                                        
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Select User</label>
                                            <select id="add-member-user-id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                <option value="">Select a user...</option>
                                                <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id'] ?>">
                                                    <?php echo esc($user['username']) ?>
                                                    (<?php echo $user['email'] ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <button type="button" 
                                                id="add-member-btn"
                                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <i class="fas fa-plus mr-2"></i>Add to Group
                                        </button>
                                    </div>
                                    
                                    <!-- Current Members Section -->
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="text-md font-medium text-gray-900 mb-3">
                                            <i class="fas fa-users mr-2 text-blue-500"></i>Current Members
                                        </h4>
                                        
                                        <div id="current-members-list" class="max-h-60 overflow-y-auto">
                                            <div class="text-center py-8">
                                                <i class="fas fa-sync-alt fa-spin text-gray-400 text-xl mb-2"></i>
                                                <p class="text-gray-500">Loading members...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" 
                            id="close-manage-members-modal-btn"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Create group form submission
    const createGroupForm = document.getElementById('create-group-form');
    if (createGroupForm) {
        createGroupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const groupName = document.getElementById('group-name').value.trim();
            const groupDescription = document.getElementById('group-description').value.trim();
            
            if (!groupName) {
                showAlert('Group name is required.', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('name', groupName);
            formData.append('description', groupDescription);
            
            fetch('/user-groups/create', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error creating group:', error);
                showAlert('Error creating group.', 'error');
            });
        });
    }
    
    // Manage group members buttons
    document.querySelectorAll('.manage-group-members-btn').forEach(button => {
        button.addEventListener('click', function() {
            const groupId = this.getAttribute('data-group-id');
            const groupName = this.getAttribute('data-group-name');
            
            openManageMembersModal(groupId, groupName);
        });
    });
    
    // Delete group buttons
    document.querySelectorAll('.delete-group-btn').forEach(button => {
        button.addEventListener('click', function() {
            const groupId = this.getAttribute('data-group-id');
            const groupName = this.getAttribute('data-group-name');
            
            if (confirm(`Are you sure you want to delete the group "${groupName}"? This will remove all members from the group.`)) {
                deleteGroup(groupId, groupName);
            }
        });
    });
    
    // Add member button
    document.getElementById('add-member-btn').addEventListener('click', function() {
        const groupId = document.getElementById('manage-members-modal').getAttribute('data-group-id');
        const userId = document.getElementById('add-member-user-id').value;
        
        if (!userId) {
            showAlert('Please select a user to add.', 'error');
            return;
        }
        
        addMemberToGroup(groupId, userId);
    });
    
    // Close modal button
    document.getElementById('close-manage-members-modal-btn').addEventListener('click', function() {
        closeManageMembersModal();
    });
    
    // Handle click outside modal to close it
    document.getElementById('manage-members-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeManageMembersModal();
        }
    });
});

function openManageMembersModal(groupId, groupName) {
    const modal = document.getElementById('manage-members-modal');
    modal.setAttribute('data-group-id', groupId);
    document.getElementById('manage-members-modal-title').textContent = `Manage Members - ${groupName}`;
    document.getElementById('group-name-display').textContent = groupName;
    
    // Reset add member form
    document.getElementById('add-member-user-id').value = '';
    
    // Load current members
    loadGroupMembers(groupId);
    
    modal.classList.remove('hidden');
}

function closeManageMembersModal() {
    document.getElementById('manage-members-modal').classList.add('hidden');
}

function loadGroupMembers(groupId) {
    const membersList = document.getElementById('current-members-list');
    membersList.innerHTML = `
        <div class="text-center py-8">
            <i class="fas fa-sync-alt fa-spin text-gray-400 text-xl mb-2"></i>
            <p class="text-gray-500">Loading members...</p>
        </div>
    `;
    
    fetch(`/user-groups/get-group-members/${groupId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayGroupMembers(data.members, groupId);
        } else {
            membersList.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-red-400 text-xl mb-2"></i>
                    <p class="text-gray-500">Error loading members: ${data.message}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading group members:', error);
        membersList.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-triangle text-red-400 text-xl mb-2"></i>
                <p class="text-gray-500">Error loading members</p>
            </div>
        `;
    });
}

function displayGroupMembers(members, groupId) {
    const membersList = document.getElementById('current-members-list');
    
    if (members.length === 0) {
        membersList.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-user-friends text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No members in this group</p>
                <p class="text-gray-400 text-sm mt-1">Add members using the form on the left</p>
            </div>
        `;
        return;
    }
    
    let html = '<div class="space-y-3">';
    members.forEach(member => {
        html += `
            <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                        <i class="fas fa-user text-indigo-600"></i>
                    </div>
                    <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">${member.username}</div>
                        <div class="text-sm text-gray-500">${member.email}</div>
                    </div>
                </div>
                <button type="button" 
                        onclick="removeMemberFromGroup(${groupId}, ${member.user_id})"
                        class="inline-flex items-center px-3 py-1 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                    <i class="fas fa-trash mr-1"></i> Remove
                </button>
            </div>
        `;
    });
    html += '</div>';
    
    membersList.innerHTML = html;
}

function addMemberToGroup(groupId, userId) {
    fetch(`/user-groups/add-member/${groupId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
        },
        body: JSON.stringify({
            user_id: userId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadGroupMembers(groupId);
            // Reset the add member form
            document.getElementById('add-member-user-id').value = '';
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error adding member to group:', error);
        showAlert('Error adding member to group.', 'error');
    });
}

function removeMemberFromGroup(groupId, userId) {
    if (!confirm('Are you sure you want to remove this user from the group?')) {
        return;
    }
    
    fetch(`/user-groups/remove-member/${groupId}/${userId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadGroupMembers(groupId);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error removing member from group:', error);
        showAlert('Error removing member from group.', 'error');
    });
}

function deleteGroup(groupId, groupName) {
    fetch(`/user-groups/delete/${groupId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting group:', error);
        showAlert('Error deleting group.', 'error');
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
</script>
<?php echo $this->endSection() ?>