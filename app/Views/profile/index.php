<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>My Profile<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include('components/navigation', [
        'pageTitle' => 'My Profile',
        'pageIcon' => 'user-circle',
        'stickyNav' => false
    ]) ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('content') ?>
<div class="max-w-4xl mx-auto">
    <!-- Profile Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center space-x-4">
            <div class="w-20 h-20 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center">
                <i class="fas fa-user text-white text-3xl"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo esc($user->username) ?></h1>
                <p class="text-gray-600">
                    <i class="fas fa-shield-alt mr-1"></i>
                    <?php echo esc(ucfirst($user->getGroups()[0] ?? 'User')) ?>
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <!-- Update Email Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center mb-4">
                <i class="fas fa-envelope text-indigo-600 text-xl mr-3"></i>
                <h2 class="text-xl font-semibold text-gray-900">Email Address</h2>
            </div>
            
            <form id="updateEmailForm" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo esc($email ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="your.email@example.com"
                           required>
                    <p class="mt-1 text-sm text-gray-500">Used for notifications and account recovery</p>
                </div>

                <div>
                    <label for="email_current_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Current Password
                    </label>
                    <input type="password" 
                           id="email_current_password" 
                           name="current_password" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Enter your current password"
                           required>
                    <p class="mt-1 text-sm text-gray-500">Confirm your password to update email</p>
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200">
                        <i class="fas fa-save mr-2"></i>Update Email
                    </button>
                </div>
            </form>
        </div>

        <!-- Update Password Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center mb-4">
                <i class="fas fa-lock text-indigo-600 text-xl mr-3"></i>
                <h2 class="text-xl font-semibold text-gray-900">Change Password</h2>
            </div>
            
            <form id="updatePasswordForm" class="space-y-4">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Current Password
                    </label>
                    <input type="password" 
                           id="current_password" 
                           name="current_password" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Enter your current password"
                           required>
                </div>

                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                        New Password
                    </label>
                    <input type="password" 
                           id="new_password" 
                           name="new_password" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Enter your new password"
                           required>
                    <p class="mt-1 text-sm text-gray-500">At least 8 characters with uppercase, lowercase, numbers, and symbols</p>
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Confirm New Password
                    </label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Confirm your new password"
                           required>
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200">
                        <i class="fas fa-key mr-2"></i>Update Password
                    </button>
                </div>
            </form>
        </div>

        <!-- Account Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center mb-4">
                <i class="fas fa-info-circle text-indigo-600 text-xl mr-3"></i>
                <h2 class="text-xl font-semibold text-gray-900">Account Information</h2>
            </div>
            
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Username</span>
                    <span class="font-medium text-gray-900"><?php echo esc($user->username) ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">User ID</span>
                    <span class="font-medium text-gray-900">#<?php echo esc($user->id) ?></span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200">
                    <span class="text-gray-600">Account Created</span>
                    <span class="font-medium text-gray-900">
                        <?php echo date('F j, Y', strtotime($user->created_at)) ?>
                    </span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-600">Account Status</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>Active
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <i class="fas fa-check text-green-600 text-xl"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Success!</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="successMessage"></p>
            </div>
            <div class="items-center px-4 py-3">
                <button onclick="closeSuccessModal()" 
                        class="px-4 py-2 bg-green-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Error</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="errorMessage"></p>
                <div id="errorDetails" class="mt-2 text-xs text-left text-red-600"></div>
            </div>
            <div class="items-center px-4 py-3">
                <button onclick="closeErrorModal()" 
                        class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update Email Form
    document.getElementById('updateEmailForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
        
        try {
            const response = await fetch('/profile/update-email', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccessModal(result.message);
                document.getElementById('email_current_password').value = '';
            } else {
                showErrorModal(result.message, result.errors);
            }
        } catch (error) {
            showErrorModal('An error occurred. Please try again.');
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    });
    
    // Update Password Form
    document.getElementById('updatePasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
        
        try {
            const response = await fetch('/profile/update-password', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                },
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccessModal(result.message);
                this.reset();
            } else {
                showErrorModal(result.message, result.errors);
            }
        } catch (error) {
            showErrorModal('An error occurred. Please try again.');
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    });
});

function showSuccessModal(message) {
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successModal').classList.remove('hidden');
}

function closeSuccessModal() {
    document.getElementById('successModal').classList.add('hidden');
}

function showErrorModal(message, errors = null) {
    document.getElementById('errorMessage').textContent = message;
    
    const errorDetails = document.getElementById('errorDetails');
    if (errors) {
        let errorHtml = '<ul class="list-disc list-inside">';
        for (const [field, error] of Object.entries(errors)) {
            errorHtml += `<li>${error}</li>`;
        }
        errorHtml += '</ul>';
        errorDetails.innerHTML = errorHtml;
    } else {
        errorDetails.innerHTML = '';
    }
    
    document.getElementById('errorModal').classList.remove('hidden');
}

function closeErrorModal() {
    document.getElementById('errorModal').classList.add('hidden');
}
</script>
<?php echo $this->endSection() ?>
