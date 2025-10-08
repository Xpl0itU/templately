<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>Test Permissions<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include('components/navigation', [
        'pageTitle' => 'Test Permissions',
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
                <i class="fas fa-shield-alt mr-2 text-blue-500"></i>Permission Testing
            </h2>
            <p class="text-gray-600 mt-1">Test and manage user permissions</p>
        </div>
        
        <div class="p-6">
            <!-- Users Section -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-users mr-2 text-blue-500"></i>System Users
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($users as $user): ?>
                    <div class="user-card bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-blue-300 transition-colors cursor-pointer"
                         data-user-id="<?php echo $user['id'] ?>">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <i class="fas fa-user text-indigo-600"></i>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900"><?php echo esc($user['username']) ?></div>
                                <div class="text-xs text-gray-500"><?php echo implode(', ', $user['groups']) ?></div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" 
                                    class="test-permission-btn inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Test Permissions
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Templates Section -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-file-alt mr-2 text-purple-500"></i>Templates
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($templates as $template): ?>
                    <div class="template-card bg-purple-50 rounded-lg p-4 border border-purple-200 hover:border-purple-300 transition-colors cursor-pointer"
                         data-template-id="<?php echo $template['id'] ?>">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-purple-100 flex items-center justify-center">
                                <i class="fas fa-file-alt text-purple-600"></i>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900"><?php echo esc($template['name']) ?></div>
                                <div class="text-xs text-gray-500">Created: <?php echo date('M j, Y', strtotime($template['createdAt'])) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Filled Files Section -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-file-word mr-2 text-green-500"></i>Filled Files
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($filledFiles as $file): ?>
                    <div class="file-card bg-green-50 rounded-lg p-4 border border-green-200 hover:border-green-300 transition-colors cursor-pointer"
                         data-file-id="<?php echo $file['id'] ?>">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="fas fa-file-word text-green-600"></i>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900"><?php echo esc($file['name']) ?></div>
                                <div class="text-xs text-gray-500">Created: <?php echo date('M j, Y', strtotime($file['createdAt'])) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- System Permissions Section -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-key mr-2 text-yellow-500"></i>System Permissions
                </h3>
                
                <div class="bg-white rounded-lg border border-gray-200 p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <?php foreach ($systemPermissions as $permission => $description): ?>
                        <div class="permission-item flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <input type="checkbox" 
                                   id="perm_<?php echo str_replace(['.', '-'], '_', $permission) ?>"
                                   data-permission="<?php echo $permission ?>"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="perm_<?php echo str_replace(['.', '-'], '_', $permission) ?>" class="ml-3 block text-sm font-medium text-gray-700">
                                <?php echo $permission ?>
                                <p class="text-gray-500 text-xs"><?php echo $description ?></p>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test permission button click handler
    document.querySelectorAll('.test-permission-btn').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.closest('.user-card').getAttribute('data-user-id');
            const permission = prompt('Enter permission to test:');
            
            if (!permission) {
                return;
            }
            
            testUserPermission(userId, permission);
        });
    });
    
    // Template card click handler
    document.querySelectorAll('.template-card').forEach(card => {
        card.addEventListener('click', function() {
            const templateId = this.getAttribute('data-template-id');
            alert(`Template ID: ${templateId}`);
        });
    });
    
    // File card click handler
    document.querySelectorAll('.file-card').forEach(card => {
        card.addEventListener('click', function() {
            const fileId = this.getAttribute('data-file-id');
            alert(`File ID: ${fileId}`);
        });
    });
});

// Function to test user permission
function testUserPermission(userId, permission) {
    fetch('/index.php/permissions/test-user-permission', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '<?php echo csrf_hash(); ?>'
        },
        body: JSON.stringify({
            user_id: userId,
            permission: permission
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(`User ${data.result ? 'HAS' : 'does NOT have'} permission: ${permission}`, data.result ? 'success' : 'warning');
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error testing user permission:', error);
        showAlert('An error occurred while testing the user permission.', 'error');
    });
}

// Function to show alert messages
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertMessage');
    alertContainer.className = `mb-6 p-4 rounded-lg ${
        type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
        type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
        type === 'warning' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' :
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