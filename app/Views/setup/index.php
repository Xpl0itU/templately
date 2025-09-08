<?php echo $this->extend('auth/layout') ?>

<?php echo $this->section('title') ?>Setup - Create Superadmin<?php echo $this->endSection() ?>

<?php echo $this->section('pageStyles') ?>
<style>
.requirement-item {
    transition: all 0.3s ease;
}
.requirement-item:hover {
    transform: translateY(-1px);
}
.setup-step {
    opacity: 0.5;
    transition: all 0.3s ease;
}
.setup-step.active {
    opacity: 1;
}
.setup-step.completed {
    opacity: 1;
}
</style>
<?php echo $this->endSection() ?>

<?php echo $this->section('main') ?>

<div class="glass-effect rounded-xl shadow-2xl p-8">
    <div class="text-center mb-8">
        <div class="mx-auto w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-magic text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-800">Welcome to Templately!</h2>
        <p class="text-gray-600 mt-2">Let's set up your professional template management system.</p>
    </div>

    <!-- Progress Indicator -->
    <div class="mb-8">
        <div class="flex items-center justify-center space-x-4">
            <div class="setup-step active flex items-center text-indigo-600" id="step-requirements">
                <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                    <i class="fas fa-check hidden step-check"></i>
                    <span class="step-number">1</span>
                </div>
                <span class="ml-2 text-sm font-medium">Requirements</span>
            </div>
            <div class="w-12 h-1 bg-gray-200 rounded step-connector"></div>
            <div class="setup-step flex items-center text-gray-400" id="step-account">
                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-sm font-semibold">
                    <i class="fas fa-check hidden step-check"></i>
                    <span class="step-number">2</span>
                </div>
                <span class="ml-2 text-sm font-medium">Create Admin</span>
            </div>
            <div class="w-12 h-1 bg-gray-200 rounded step-connector"></div>
            <div class="setup-step flex items-center text-gray-400" id="step-complete">
                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-sm font-semibold">
                    <i class="fas fa-check hidden step-check"></i>
                    <span class="step-number">3</span>
                </div>
                <span class="ml-2 text-sm font-medium">Complete</span>
            </div>
        </div>
    </div>

    <!-- Step 1: System Requirements -->
    <div id="requirements-panel" class="setup-panel">
        <div class="text-center mb-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-2">System Requirements Check</h3>
            <p class="text-gray-600">We need to verify that your system meets all requirements.</p>
        </div>

        <div class="space-y-4" id="requirements-list">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin text-indigo-500 text-2xl"></i>
                <p class="text-gray-600 mt-2">Checking system requirements...</p>
            </div>
        </div>

        <div class="mt-6 text-center">
            <button type="button" 
                    id="recheck-requirements"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-refresh mr-2"></i>
                Recheck Requirements
            </button>
            <button type="button" 
                    id="continue-to-account"
                    class="ml-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                <i class="fas fa-arrow-right mr-2"></i>
                Continue to Account Creation
            </button>
        </div>
    </div>

    <!-- Step 2: Create Account -->
    <div id="account-panel" class="setup-panel hidden">
        <?php if (isset($error)) : ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                <div class="flex">
                    <div class="py-1"><i class="fas fa-exclamation-circle mr-2"></i></div>
                    <div><?php echo esc($error) ?></div>
                </div>
            </div>
        <?php endif ?>

        <?php if (isset($validation)) : ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                <div class="flex">
                    <div class="py-1"><i class="fas fa-exclamation-circle mr-2"></i></div>
                    <div>
                        <?php foreach ($validation->getErrors() as $error) : ?>
                            <?php echo esc($error) ?><br>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <div class="text-center mb-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Create Superadmin Account</h3>
            <p class="text-gray-600">This account will have full access to manage your Templately installation.</p>
        </div>

        <form action="<?php echo base_url('setup') ?>" method="post" class="space-y-6">
            <?php echo csrf_field() ?>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            First-Time Setup
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>You're creating the first superadmin account for this Templately installation. This account will have full access to all features and will be able to manage other users.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Username Field -->
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-user mr-2"></i>Username
                </label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       value="<?php echo old('username') ?>" 
                       required
                       class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition duration-200"
                       placeholder="Choose a username">
                <p class="mt-1 text-sm text-gray-500">This will be your login username. Use 3-30 characters.</p>
            </div>

            <!-- Email Field -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-envelope mr-2"></i>Email Address
                </label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="<?php echo old('email') ?>" 
                       required
                       class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition duration-200"
                       placeholder="Enter your email address">
                <p class="mt-1 text-sm text-gray-500">You can also use this email to sign in.</p>
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-lock mr-2"></i>Password
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required
                       class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition duration-200"
                       placeholder="Create a strong password">
                <p class="mt-1 text-sm text-gray-500">Use a strong password with at least 8 characters.</p>
            </div>

            <!-- Confirm Password Field -->
            <div>
                <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-lock mr-2"></i>Confirm Password
                </label>
                <input type="password" 
                       id="password_confirm" 
                       name="password_confirm" 
                       required
                       class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition duration-200"
                       placeholder="Confirm your password">
            </div>

            <!-- Superadmin Privileges Info -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-crown text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            Superadmin Privileges
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Full system access and control</li>
                                <li>Manage all users and their permissions</li>
                                <li>Create, edit, and delete templates</li>
                                <li>Access all filled files and documents</li>
                                <li>System configuration and settings</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200 transform hover:scale-105">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-magic h-5 w-5 text-indigo-300 group-hover:text-indigo-200"></i>
                    </span>
                    Create Superadmin Account & Complete Setup
                </button>
            </div>

            <!-- Security Note -->
            <div class="text-center">
                <p class="text-xs text-gray-500">
                    <i class="fas fa-shield-alt mr-1"></i>
                    Your password will be securely encrypted and stored.
                </p>
            </div>
        </form>

        <div class="mt-6 text-center">
            <button type="button" 
                    id="back-to-requirements"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Requirements
            </button>
        </div>
    </div>
</div>

<?php echo $this->endSection() ?>

<?php echo $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing setup wizard...');
    
    const requirementsPanel = document.getElementById('requirements-panel');
    const accountPanel = document.getElementById('account-panel');
    const requirementsList = document.getElementById('requirements-list');
    const continueBtn = document.getElementById('continue-to-account');
    const recheckBtn = document.getElementById('recheck-requirements');
    const backBtn = document.getElementById('back-to-requirements');
    
    console.log('Elements found:', {
        requirementsPanel: !!requirementsPanel,
        accountPanel: !!accountPanel,
        requirementsList: !!requirementsList,
        continueBtn: !!continueBtn,
        recheckBtn: !!recheckBtn,
        backBtn: !!backBtn
    });
    
    let requirementsPassed = false;

    // Check requirements on page load
    checkRequirements();

    // Event listeners
    recheckBtn.addEventListener('click', checkRequirements);
    continueBtn.addEventListener('click', showAccountPanel);
    backBtn.addEventListener('click', showRequirementsPanel);

    function checkRequirements() {
        console.log('Checking requirements...');
        
        if (!requirementsList) {
            console.error('requirements-list element not found!');
            return;
        }
        
        requirementsList.innerHTML = `
            <div class="text-center">
                <i class="fas fa-spinner fa-spin text-indigo-500 text-2xl"></i>
                <p class="text-gray-600 mt-2">Checking system requirements...</p>
            </div>
        `;

        fetch('/setup/check-requirements', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?php echo csrf_hash() ?>'
            }
        })
        .then(response => response.json())
        .then(data => {
            displayRequirements(data);
            requirementsPassed = data.allPassed;
            updateContinueButton();
            updateStepStatus('requirements', data.allPassed);
        })
        .catch(error => {
            console.error('Error:', error);
            requirementsList.innerHTML = `
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <div>Failed to check requirements. Please refresh the page and try again.</div>
                    </div>
                </div>
            `;
        });
    }

    function displayRequirements(data) {
        const requirementsList = document.getElementById('requirements-list');
        let html = '';

        // System requirements
        html += '<div class="mb-6"><h4 class="font-semibold text-gray-800 mb-3">System Requirements</h4>';
        for (const [key, requirement] of Object.entries(data.systemCheck)) {
            const statusIcon = requirement.status ? 
                '<i class="fas fa-check-circle text-green-500"></i>' : 
                '<i class="fas fa-times-circle text-red-500"></i>';
            const statusClass = requirement.status ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50';
            
            html += `
                <div class="requirement-item ${statusClass} border rounded-lg p-3 mb-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            ${statusIcon}
                            <span class="ml-3 font-medium text-gray-800">${requirement.name}</span>
                        </div>
                        <div class="text-sm text-gray-600">
                            ${requirement.current}
                        </div>
                    </div>
                    ${!requirement.status ? `<div class="mt-2 text-sm text-red-600 ml-6">${requirement.required}</div>` : ''}
                </div>
            `;
        }
        html += '</div>';

        // Database connection
        html += '<div class="mb-4"><h4 class="font-semibold text-gray-800 mb-3">Database Connection</h4>';
        const dbStatusIcon = data.dbCheck.status ? 
            '<i class="fas fa-check-circle text-green-500"></i>' : 
            '<i class="fas fa-times-circle text-red-500"></i>';
        const dbStatusClass = data.dbCheck.status ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50';
        
        html += `
            <div class="requirement-item ${dbStatusClass} border rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        ${dbStatusIcon}
                        <span class="ml-3 font-medium text-gray-800">Database Connection</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        ${data.dbCheck.database || 'Not connected'}
                    </div>
                </div>
                <div class="mt-2 text-sm ${data.dbCheck.status ? 'text-green-600' : 'text-red-600'} ml-6">
                    ${data.dbCheck.message}
                </div>
            </div>
        `;
        html += '</div>';

        // Overall status
        if (data.allPassed) {
            html += `
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <div>All requirements are met! You can proceed with the setup.</div>
                    </div>
                </div>
            `;
        } else {
            html += `
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <div>Some requirements are not met. Please resolve the issues above before continuing.</div>
                    </div>
                </div>
            `;
        }

        requirementsList.innerHTML = html;
    }

    function updateContinueButton() {
        continueBtn.disabled = !requirementsPassed;
        if (requirementsPassed) {
            continueBtn.classList.remove('disabled:opacity-50', 'disabled:cursor-not-allowed');
        } else {
            continueBtn.classList.add('disabled:opacity-50', 'disabled:cursor-not-allowed');
        }
    }

    function updateStepStatus(step, completed) {
        const stepElement = document.getElementById(`step-${step}`);
        if (completed) {
            stepElement.classList.add('completed');
            stepElement.classList.remove('text-gray-400');
            stepElement.classList.add('text-green-600');
            stepElement.querySelector('.step-check').classList.remove('hidden');
            stepElement.querySelector('.step-number').classList.add('hidden');
            stepElement.querySelector('.w-8').classList.remove('bg-gray-200', 'bg-indigo-600');
            stepElement.querySelector('.w-8').classList.add('bg-green-600');
        }
    }

    function showAccountPanel() {
        if (!requirementsPassed) return;
        
        requirementsPanel.classList.add('hidden');
        accountPanel.classList.remove('hidden');
        
        // Update step indicators
        updateStepStatus('account', false);
        document.getElementById('step-account').classList.add('active');
        document.getElementById('step-account').classList.remove('text-gray-400');
        document.getElementById('step-account').classList.add('text-indigo-600');
        document.getElementById('step-account').querySelector('.w-8').classList.remove('bg-gray-200');
        document.getElementById('step-account').querySelector('.w-8').classList.add('bg-indigo-600');
    }

    function showRequirementsPanel() {
        accountPanel.classList.add('hidden');
        requirementsPanel.classList.remove('hidden');
        
        // Reset step indicators
        document.getElementById('step-account').classList.remove('active');
        document.getElementById('step-account').classList.add('text-gray-400');
        document.getElementById('step-account').classList.remove('text-indigo-600');
        document.getElementById('step-account').querySelector('.w-8').classList.add('bg-gray-200');
        document.getElementById('step-account').querySelector('.w-8').classList.remove('bg-indigo-600');
    }
});
</script>

<?php echo $this->endSection() ?>
