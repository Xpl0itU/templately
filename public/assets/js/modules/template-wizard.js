/**
 * Template Wizard Module
 */
Templately.TemplateWizard = (function() {
    'use strict';
    
    // Private variables
    let wizardState = {
        currentStep: 1,
        templateFile: null,
        templateFields: [],
        templateName: ''
    };
    
    let uploadWizardModal = null;
    let wizardFileUploadForm = null;
    let wizardNameReviewForm = null;
    
    // Private functions
    function initializeElements() {
        uploadWizardModal = document.getElementById('uploadWizardModal');
        wizardFileUploadForm = document.getElementById('wizardFileUploadForm');
        wizardNameReviewForm = document.getElementById('wizardNameReviewForm');
    }
    
    function attachEventListeners() {
        // Wizard form submissions
        if (wizardFileUploadForm) {
            wizardFileUploadForm.addEventListener('submit', handleFileUploadSubmit);
        }
        
        if (wizardNameReviewForm) {
            wizardNameReviewForm.addEventListener('submit', handleNameReviewSubmit);
        }
        
        // Navigation buttons
        const nextButtons = document.querySelectorAll('[data-action="next-step"]');
        nextButtons.forEach(button => {
            button.addEventListener('click', goToNextStep);
        });
        
        const prevButtons = document.querySelectorAll('[data-action="prev-step"]');
        prevButtons.forEach(button => {
            button.addEventListener('click', goToPreviousStep);
        });
        
        // Close wizard button
        const closeWizardButton = document.getElementById('closeWizard');
        if (closeWizardButton) {
            closeWizardButton.addEventListener('click', resetWizard);
        }
        
        // File input change
        const templateFileWizard = document.getElementById('templateFileWizard');
        if (templateFileWizard) {
            templateFileWizard.addEventListener('change', handleFileInputChange);
        }
        
        // Drag and drop
        setupDragAndDrop();
    }
    
    function setupDragAndDrop() {
        const dropZone = document.getElementById('fileDropZone');
        if (!dropZone) return;
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-blue-500', 'bg-blue-50');
        });
        
        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
            
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                const fileInput = document.getElementById('templateFileWizard');
                if (fileInput) {
                    fileInput.files = e.dataTransfer.files;
                    Templately.FileExplorer.updateFileLabel();
                }
            }
        });
    }
    
    // Event handler functions
    function handleFileInputChange(e) {
        Templately.FileExplorer.updateFileLabel();
    }
    
    function handleFileUploadSubmit(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('templateFileWizard');
        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
            Templately.Alert.showAlert('Please select a file to upload.', 'error');
            return;
        }
        
        const file = fileInput.files[0];
        if (file.type !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            Templately.Alert.showAlert('Please upload a valid .docx file.', 'error');
            return;
        }
        
        // Show loading
        Templately.FileExplorer.showLoadingModal('Analyzing Template', 'Please wait while we analyze your template...');
        
        // Submit form via AJAX
        const formData = new FormData();
        formData.append('templateFile', file);
        
        Templately.Http.fetch('/file-explorer/analyze-template', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            Templately.FileExplorer.hideLoadingModal();
            
            if (data.success) {
                wizardState.templateFile = file;
                wizardState.templateFields = data.templateFields || [];
                wizardState.templateName = data.templateName || file.name.replace('.docx', '');
                
                // Update name review step
                const templateNameInput = document.getElementById('templateNameWizard');
                if (templateNameInput) {
                    templateNameInput.value = wizardState.templateName;
                }
                
                // Show fields in review step
                showTemplateFields();
                
                // Go to next step
                goToStep(2);
            } else {
                Templately.Alert.showAlert(data.message || 'Failed to analyze template.', 'error');
            }
        })
        .catch(error => {
            Templately.FileExplorer.hideLoadingModal();
            Templately.Alert.showAlert('An error occurred while analyzing the template.', 'error');
            console.error('Error:', error);
        });
    }
    
    function handleNameReviewSubmit(e) {
        e.preventDefault();
        
        const templateNameInput = document.getElementById('templateNameWizard');
        const templateName = templateNameInput ? templateNameInput.value.trim() : '';
        
        if (!templateName) {
            Templately.Alert.showAlert('Please enter a template name.', 'error');
            return;
        }
        
        wizardState.templateName = templateName;
        
        // Go to final step
        goToStep(3);
    }
    
    function goToNextStep() {
        if (wizardState.currentStep < 3) {
            goToStep(wizardState.currentStep + 1);
        }
    }
    
    function goToPreviousStep() {
        if (wizardState.currentStep > 1) {
            goToStep(wizardState.currentStep - 1);
        }
    }
    
    function goToStep(step) {
        // Hide all steps
        for (let i = 1; i <= 3; i++) {
            const stepElement = document.getElementById(`step${i}`);
            if (stepElement) {
                stepElement.classList.add('hidden');
            }
        }
        
        // Show current step
        const currentStepElement = document.getElementById(`step${step}`);
        if (currentStepElement) {
            currentStepElement.classList.remove('hidden');
        }
        
        // Update wizard state
        wizardState.currentStep = step;
        
        // Update step indicators
        Templately.FileExplorer.updateWizardSteps(step);
    }
    
    function showTemplateFields() {
        const fieldsContainer = document.getElementById('templateFieldsContainer');
        if (!fieldsContainer) return;
        
        if (wizardState.templateFields.length === 0) {
            fieldsContainer.innerHTML = '<p class="text-gray-500">No fields found in this template.</p>';
            return;
        }
        
        let html = '<div class="space-y-3">';
        wizardState.templateFields.forEach((field, index) => {
            html += `
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">${field.name}</div>
                        <div class="text-sm text-gray-500">${field.type}</div>
                    </div>
                    <div class="text-sm text-gray-500">
                        ${field.isRequired ? 'Required' : 'Optional'}
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        fieldsContainer.innerHTML = html;
    }
    
    function resetWizard() {
        Templately.FileExplorer.resetWizard();
        wizardState = {
            currentStep: 1,
            templateFile: null,
            templateFields: [],
            templateName: ''
        };
    }
    
    // Public API
    return {
        init: function() {
            initializeElements();
            attachEventListeners();
            
            // Set up keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (uploadWizardModal && !uploadWizardModal.classList.contains('hidden')) {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        resetWizard();
                    }
                }
            });
        },
        
        // Expose some functions for external use
        reset: resetWizard,
        getState: () => ({ ...wizardState })
    };
})();