<?php echo $this->extend('auth/layout') ?>

<?php echo $this->section('title') ?>Setup Complete<?php echo $this->endSection() ?>

<?php echo $this->section('pageStyles') ?>
<style>
.celebration-animation {
    animation: bounce 0.5s ease-in-out infinite alternate;
}
@keyframes bounce {
    0% { transform: translateY(0px); }
    100% { transform: translateY(-10px); }
}
.success-check {
    animation: checkmark 0.6s ease-in-out;
}
@keyframes checkmark {
    0% { stroke-dashoffset: 100; }
    100% { stroke-dashoffset: 0; }
}
</style>
<?php echo $this->endSection() ?>

<?php echo $this->section('main') ?>

<div class="glass-effect rounded-xl shadow-2xl p-8">
    <div class="text-center mb-8">
        <!-- Success Animation -->
        <div class="mx-auto w-24 h-24 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center mb-6 celebration-animation">
            <svg class="w-12 h-12 text-white success-check" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" 
                      stroke-dasharray="100" stroke-dashoffset="100" style="animation: checkmark 0.6s ease-in-out forwards;"></path>
            </svg>
        </div>
        
        <h2 class="text-3xl font-bold text-gray-800 mb-2">Setup Complete!</h2>
        <p class="text-gray-600 text-lg">Welcome to Templately - Your professional template management system is ready.</p>
    </div>

    <!-- Success Information -->
    <div class="space-y-6">
        <!-- Account Created -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-crown text-green-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-green-800 mb-2">Superadmin Account Created</h3>
                    <div class="text-green-700">
                        <p class="mb-2">Your superadmin account has been successfully created with the following details:</p>
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Username:</strong> <?php echo esc($username ?? 'N/A') ?></li>
                            <li><strong>Email:</strong> <?php echo esc($email ?? 'N/A') ?></li>
                            <li><strong>Role:</strong> Superadmin (Full Access)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-route text-blue-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-blue-800 mb-3">What's Next?</h3>
                    <div class="text-blue-700 space-y-2">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                            <span>Upload your first Word template</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                            <span>Create filled files from your templates</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                            <span>Invite team members and manage permissions</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-blue-500 mr-2"></i>
                            <span>Export documents as Word or PDF files</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Features Overview -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-magic text-purple-500 text-xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-purple-800 mb-3">Key Features Available</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-purple-700">
                        <div class="flex items-center">
                            <i class="fas fa-file-word text-purple-500 mr-2"></i>
                            <span>Word Template Processing</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-users text-purple-500 mr-2"></i>
                            <span>User Management</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-download text-purple-500 mr-2"></i>
                            <span>Export to Word & PDF</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-images text-purple-500 mr-2"></i>
                            <span>Image Field Support</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-shield-alt text-purple-500 mr-2"></i>
                            <span>Role-Based Access Control</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-cloud text-purple-500 mr-2"></i>
                            <span>Secure File Storage</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 pt-6">
            <a href="<?php echo base_url('dashboard') ?>" 
               class="flex-1 group relative flex justify-center py-3 px-6 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200 transform hover:scale-105">
                <span class="flex items-center">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    Go to Dashboard
                </span>
            </a>
            
            <a href="<?php echo base_url('file-explorer') ?>" 
               class="flex-1 group relative flex justify-center py-3 px-6 border border-indigo-300 text-sm font-medium rounded-lg text-indigo-700 bg-white hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200">
                <span class="flex items-center">
                    <i class="fas fa-upload mr-2"></i>
                    Upload First Template
                </span>
            </a>
        </div>

        <!-- Support Info -->
        <div class="text-center pt-6 border-t border-gray-200">
            <p class="text-sm text-gray-500 mb-2">
                <i class="fas fa-info-circle mr-1"></i>
                Need help getting started?
            </p>
            <div class="flex justify-center space-x-4 text-sm">
                <a href="#" class="text-indigo-600 hover:text-indigo-800 transition duration-200">
                    <i class="fas fa-book mr-1"></i>Documentation
                </a>
                <a href="#" class="text-indigo-600 hover:text-indigo-800 transition duration-200">
                    <i class="fas fa-question-circle mr-1"></i>Support
                </a>
            </div>
        </div>
    </div>
</div>

<?php echo $this->endSection() ?>

<?php echo $this->section('pageScripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        // Simple celebration effect - could be enhanced with a library like confetti.js
        const celebration = document.querySelector('.celebration-animation');
        if (celebration) {
            celebration.style.animation = 'bounce 0.3s ease-in-out 3';
        }
    }, 500);
});
</script>
<?php echo $this->endSection() ?>
