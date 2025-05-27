<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>Create Filled File<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include(
        'components/navigation', [
        'pageTitle' => 'Create Filled File',
        'pageIcon' => 'file-plus',
        'stickyNav' => false
        ]
    ) ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('content') ?>
    <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                <i class="fas fa-file-plus mr-2 text-purple-500"></i>
                Select a Template
            </h2>
            
            <?php if (!empty($templates)) : ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($templates as $template): ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-200 cursor-pointer template-card" 
                             onclick="selectTemplate(<?php echo $template['id'] ?>, '<?php echo esc($template['name']) ?>')">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-file-alt text-blue-500 text-xl mr-3"></i>
                                <h3 class="font-semibold text-gray-900 truncate"><?php echo esc($template['name']) ?></h3>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-2">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                Created: <?php echo date('M j, Y', strtotime($template['createdAt'])) ?>
                            </p>
                            
                            <?php if (!empty($template['templateFields']) && is_array($template['templateFields'])): ?>
                                <p class="text-xs text-gray-500">
                                    <i class="fas fa-list mr-1"></i>
                                    <?php echo count($template['templateFields']) ?> field<?php echo count($template['templateFields']) !== 1 ? 's' : '' ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <button class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm transition duration-200">
                                    <i class="fas fa-arrow-right mr-2"></i>
                                    Use This Template
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-folder-open text-gray-400 text-6xl mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No Templates Available</h3>
                    <p class="text-gray-500 mb-6">You need to upload templates before creating filled files.</p>
                    
                    <?php if ($userPermissions['canCreateTemplates']): ?>
                        <a href="/file-explorer/upload-template" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition duration-200">
                            <i class="fas fa-upload mr-2"></i>
                            Upload Template First
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
                        <div class="mt-8 pt-6 border-t border-gray-200">
                <a href="/dashboard" class="inline-flex items-center text-gray-600 hover:text-blue-600 transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
<?php echo $this->endSection() ?>

<?php echo $this->section('pageScripts') ?>
    <script>
        function selectTemplate(templateId, templateName) {
            // Redirect to file explorer with the selected template
            const url = `/file-explorer#create-filled-file-${templateId}`;
            window.location.href = url;
        }
        
        // Add hover effects for template cards
        document.addEventListener('DOMContentLoaded', function() {
            const templateCards = document.querySelectorAll('.template-card');
            templateCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.classList.add('border-purple-300', 'bg-purple-50');
                });
                card.addEventListener('mouseleave', function() {
                    this.classList.remove('border-purple-300', 'bg-purple-50');
                });
            });
        });
    </script>
<?php echo $this->endSection() ?>
