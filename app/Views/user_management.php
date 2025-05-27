<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>User Management<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include(
        'components/navigation', [
        'pageTitle' => 'User Management',
        'pageIcon' => 'users-cog',
        'stickyNav' => false,
        'showWelcome' => false
        ]
    ) ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('content') ?>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <div id="alertMessage" class="hidden mb-6 p-4 rounded-lg"></div>

                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-users mr-2 text-blue-500"></i>System Users
                </h2>
                <p class="text-gray-600 mt-1">Manage user accounts and permissions</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Current Group
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50" data-user-id="<?php echo $user['id'] ?>">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                                            <?php echo strtoupper(substr($user['username'] ?? $user['email'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo esc($user['username'] ?? 'N/A') ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            ID: <?php echo $user['id'] ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo esc($user['email']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize
                                    <?php echo match($user['group'] ?? 'user') {
                                        'superadmin' => 'bg-red-100 text-red-800',
                                        'admin' => 'bg-orange-100 text-orange-800',
                                        'developer' => 'bg-purple-100 text-purple-800',
                                        'beta' => 'bg-blue-100 text-blue-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    } ?>">
                                    <i class="fas fa-shield-alt mr-1"></i>
                                    <?php echo esc($user['group'] ?? 'user') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M j, Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <select class="group-select text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-blue-500 focus:border-blue-500"
                                        data-user-id="<?php echo $user['id'] ?>"
                                        <?php echo $user['id'] === $currentUser->id ? 'disabled' : '' ?>>
                                    <?php foreach ($groups as $group): ?>
                                    <option value="<?php echo esc($group['title']) ?>" 
                                            <?php echo (($user['group'] ?? 'user') === $group['title']) ? 'selected' : '' ?>>
                                        <?php echo esc(ucfirst($group['title'])) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <?php if ($currentUser->inGroup('superadmin') && $user['id'] !== $currentUser->id) : ?>
                                <button onclick="deleteUser(<?php echo $user['id'] ?>)" 
                                        class="inline-flex items-center px-3 py-1 border border-transparent text-xs leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-200">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

                <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>Permission Groups
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center p-3 bg-red-50 rounded-lg">
                        <i class="fas fa-crown text-red-500 mr-3"></i>
                        <div>
                            <div class="font-medium text-gray-900">Superadmin</div>
                            <div class="text-sm text-gray-600">Full system access and user management</div>
                        </div>
                    </div>
                    <div class="flex items-center p-3 bg-orange-50 rounded-lg">
                        <i class="fas fa-user-shield text-orange-500 mr-3"></i>
                        <div>
                            <div class="font-medium text-gray-900">Admin</div>
                            <div class="text-sm text-gray-600">Template and file management, user viewing</div>
                        </div>
                    </div>
                    <div class="flex items-center p-3 bg-purple-50 rounded-lg">
                        <i class="fas fa-code text-purple-500 mr-3"></i>
                        <div>
                            <div class="font-medium text-gray-900">Developer</div>
                            <div class="text-sm text-gray-600">Create and edit templates, manage filled files</div>
                        </div>
                    </div>
                    <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                        <i class="fas fa-flask text-blue-500 mr-3"></i>
                        <div>
                            <div class="font-medium text-gray-900">Beta</div>
                            <div class="text-sm text-gray-600">View and create filled files only</div>
                        </div>
                    </div>
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <i class="fas fa-user text-gray-500 mr-3"></i>
                        <div>
                            <div class="font-medium text-gray-900">User</div>
                            <div class="text-sm text-gray-600">View templates and filled files only</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-lg rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-chart-bar mr-2 text-green-500"></i>User Statistics
                </h3>
                <div class="space-y-3">
                    <?php 
                    $groupCounts = [];
                    foreach ($users as $user) {
                        $group = $user['group'] ?? 'user';
                        $groupCounts[$group] = ($groupCounts[$group] ?? 0) + 1;
                    }
                    ?>
                    <?php foreach ($groupCounts as $group => $count): ?>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                        <span class="font-medium text-gray-900 capitalize"><?php echo esc($group) ?></span>
                        <span class="text-2xl font-bold text-blue-600"><?php echo $count ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="border-t pt-3 mt-3">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-gray-900">Total Users</span>
                            <span class="text-2xl font-bold text-green-600"><?php echo count($users) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php echo $this->endSection() ?>

<?php echo $this->section('pageScripts') ?>
    <script>
                document.querySelectorAll('.group-select').forEach(select => {
            select.addEventListener('change', async function() {
                const userId = this.dataset.userId;
                const newGroup = this.value;
                
                try {
                    const response = await fetch('/user-management/update-group', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            group: newGroup
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        showAlert(result.message, 'success');
                                                updateGroupBadge(userId, newGroup);
                    } else {
                        showAlert(result.message, 'error');
                                                location.reload();
                    }
                } catch (error) {
                    showAlert('An error occurred while updating the user group.', 'error');
                    location.reload();
                }
            });
        });

        function updateGroupBadge(userId, group) {
            const row = document.querySelector(`tr[data-user-id="${userId}"]`);
            const badge = row.querySelector('.inline-flex');
            
                        badge.className = badge.className.replace(/bg-\w+-100 text-\w+-800/g, '');
            
                        const colorMap = {
                'superadmin': 'bg-red-100 text-red-800',
                'admin': 'bg-orange-100 text-orange-800',
                'developer': 'bg-purple-100 text-purple-800',
                'beta': 'bg-blue-100 text-blue-800',
                'user': 'bg-gray-100 text-gray-800'
            };
            
            badge.className += ' ' + (colorMap[group] || colorMap['user']);
            badge.textContent = group.charAt(0).toUpperCase() + group.slice(1);
            badge.innerHTML = '<i class="fas fa-shield-alt mr-1"></i>' + badge.textContent;
        }

        async function deleteUser(userId) {
            if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(`/user-management/delete/${userId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, 'success');
                                        document.querySelector(`tr[data-user-id="${userId}"]`).remove();
                } else {
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                showAlert('An error occurred while deleting the user.', 'error');
            }
        }

        function showAlert(message, type) {
            const alertDiv = document.getElementById('alertMessage');
            alertDiv.className = `mb-6 p-4 rounded-lg ${type === 'success' ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'}`;
            alertDiv.textContent = message;
            alertDiv.classList.remove('hidden');
            
                        setTimeout(() => {
                alertDiv.classList.add('hidden');
            }, 5000);
        }
    </script>
<?php echo $this->endSection() ?>
