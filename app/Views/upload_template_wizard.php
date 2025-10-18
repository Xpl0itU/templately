<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>Upload Template<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include('components/navigation', [
        'pageTitle' => 'Upload Template',
        'pageIcon' => 'upload',
        'stickyNav' => false,
        'showWelcome' => false
    ]) ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('content') ?>
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-file-upload mr-2 text-blue-500"></i>Upload New Template
            </h2>
            <p class="text-gray-600 mt-1">Upload a Word document template to get started</p>
        </div>
        
        <div class="p-6">
            <div id="uploadWizard">
                <!-- Step 1: File Upload -->
                <div id="stepFileUpload" class="step active">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Step 1: Upload Template File</h3>
                        <p class="text-gray-600">Upload your Word document (.docx) that will serve as a template for filled documents.</p>
                    </div>
                    
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center mb-6">
                        <div class="mb-4">
                            <i class="fas fa-cloud-upload-alt text-gray-400 text-4xl"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">Upload Template</h4>
                        <p class="text-gray-500 mb-4">Drag and drop your Word document here, or click to browse files</p>
                        <input type="file" id="templateFileInput" accept=".docx" class="hidden">
                        <button type="button" 
                                onclick="document.getElementById('templateFileInput').click()" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-folder-open mr-2"></i>Choose File
                        </button>
                    </div>
                    
                    <div id="fileInfo" class="hidden mb-6 p-4 bg-blue-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-file-word text-blue-500 text-xl mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900" id="fileNameDisplay"></p>
                                <p class="text-sm text-gray-500" id="fileSizeDisplay"></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" 
                                id="nextToAnalysisBtn"
                                onclick="nextStep('stepFileUpload', 'stepAnalysis')"
                                disabled
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Step 2: Analysis -->
                <div id="stepAnalysis" class="step hidden">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Step 2: Template Analysis</h3>
                        <p class="text-gray-600">Analyzing your template to identify fillable fields...</p>
                    </div>
                    
                    <div class="text-center py-8">
                        <i class="fas fa-cog fa-spin text-gray-400 text-4xl mb-4"></i>
                        <p class="text-lg font-medium text-gray-900">Analyzing template...</p>
                        <p class="text-gray-500">Please wait while we process your document</p>
                    </div>
                </div>
                
                <!-- Step 3: Field Review and Naming -->
                <div id="stepFieldReview" class="step hidden">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Step 3: Review Template Fields</h3>
                        <p class="text-gray-600">Review the fields detected in your template and assign names</p>
                    </div>
                    
                    <div class="mb-6">
                        <label for="templateName" class="block text-sm font-medium text-gray-700 mb-1">Template Name</label>
                        <input type="text" 
                               id="templateName" 
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                               placeholder="Enter a name for your template">
                    </div>
                    
                    <div class="mb-6">
                        <h4 class="text-md font-medium text-gray-900 mb-3">Detected Fields</h4>
                        <div id="fieldsList" class="space-y-3">
                            <!-- Fields will be populated here -->
                        </div>
                    </div>
                    
                    <div class="flex justify-between">
                        <button type="button" 
                                onclick="prevStep('stepFieldReview', 'stepAnalysis')"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </button>
                        <button type="button" 
                                id="finalizeUploadBtn"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-check mr-2"></i>Finalize Upload
                        </button>
                    </div>
                </div>
                
                <!-- Step 4: Finalizing -->
                <div id="stepFinalizing" class="step hidden">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Step 4: Finalizing Upload</h3>
                        <p class="text-gray-600">Creating your template...</p>
                    </div>
                    
                    <div class="text-center py-8">
                        <i class="fas fa-cog fa-spin text-gray-400 text-4xl mb-4"></i>
                        <p class="text-lg font-medium text-gray-900">Finalizing upload...</p>
                        <p class="text-gray-500" id="finalizingStatus">Saving your template to the system</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let wizardState = {
    uploadedFile: null,
    tempFilePath: null,
    originalFileName: null,
    detectedFields: [],
    templateName: null
};

// File input change handler
document.getElementById('templateFileInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        wizardState.uploadedFile = file;
        displayFileInfo(file);
        document.getElementById('nextToAnalysisBtn').disabled = false;
    }
});

// Display file information
function displayFileInfo(file) {
    const fileInfoDiv = document.getElementById('fileInfo');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const fileSizeDisplay = document.getElementById('fileSizeDisplay');
    
    fileNameDisplay.textContent = file.name;
    fileSizeDisplay.textContent = formatFileSize(file.size);
    
    fileInfoDiv.classList.remove('hidden');
}

// Format file size for display
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' bytes';
    else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + ' KB';
    else return (bytes / 1048576).toFixed(2) + ' MB';
}

// Navigation between steps
function nextStep(currentStep, nextStep) {
    document.getElementById(currentStep).classList.add('hidden');
    document.getElementById(nextStep).classList.remove('hidden');
    
    if (nextStep === 'stepAnalysis') {
        analyzeTemplate();
    }
}

function prevStep(currentStep, prevStep) {
    document.getElementById(currentStep).classList.add('hidden');
    document.getElementById(prevStep).classList.remove('hidden');
}

// Analyze the template file
async function analyzeTemplate() {
    if (!wizardState.uploadedFile) return;
    
    const formData = new FormData();
    formData.append('templateFile', wizardState.uploadedFile);
    
    try {
        const response = await fetch('/file-explorer/analyze-template', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            wizardState.tempFilePath = result.tempFilePath;
            wizardState.originalFileName = result.originalFileName;
            wizardState.detectedFields = result.detectedFields;
            
            // Move to next step after a short delay to show analysis is complete
            setTimeout(() => {
                document.getElementById('stepAnalysis').classList.add('hidden');
                document.getElementById('stepFieldReview').classList.remove('hidden');
                
                // Populate the fields list
                populateFieldsList();
            }, 1500);
        } else {
            alert(result.message || 'Failed to analyze template');
            document.getElementById('stepAnalysis').classList.add('hidden');
            document.getElementById('stepFileUpload').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Analysis error:', error);
        alert('Error analyzing template: ' + error.message);
        document.getElementById('stepAnalysis').classList.add('hidden');
        document.getElementById('stepFileUpload').classList.remove('hidden');
    }
}

// Populate the fields list in the review step
function populateFieldsList() {
    const fieldsList = document.getElementById('fieldsList');
    
    if (wizardState.detectedFields.length === 0) {
        fieldsList.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-info-circle text-gray-400 text-xl mb-2"></i>
                <p class="text-gray-600">No fields detected automatically. You can still create filled documents manually.</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    wizardState.detectedFields.forEach((field, index) => {
        html += `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-tag text-gray-400 mr-3"></i>
                    <span class="text-sm font-medium text-gray-900">${field}</span>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    Field ${index + 1}
                </span>
            </div>
        `;
    });
    
    fieldsList.innerHTML = html;
}

// Finalize upload
document.getElementById('finalizeUploadBtn').addEventListener('click', async function() {
    const templateName = document.getElementById('templateName').value.trim();
    
    if (!templateName) {
        alert('Please enter a template name');
        return;
    }
    
    wizardState.templateName = templateName;
    
    // Show finalizing step
    document.getElementById('stepFieldReview').classList.add('hidden');
    document.getElementById('stepFinalizing').classList.remove('hidden');
    
    try {
        const response = await fetch('/file-explorer/finalize-template-upload', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
            },
            body: JSON.stringify({
                tempFilePath: wizardState.tempFilePath,
                templateName: templateName,
                templateFields: wizardState.detectedFields,
                originalFileName: wizardState.originalFileName
            })
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({
                message: 'Failed to finalize template. Server error.'
            }));
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            document.getElementById('finalizingStatus').textContent = 'Template saved successfully!';
            
            setTimeout(() => {
                alert('Template uploaded successfully!');
                window.location.href = '/file-explorer';
            }, 1000);
        } else {
            throw new Error(result.message || 'Upload failed');
        }
    } catch (error) {
        console.error('Upload error:', error);
        document.getElementById('finalizingStatus').textContent = 'Error: ' + error.message;
        
        setTimeout(() => {
            alert('Failed to save template: ' + error.message);
            document.getElementById('stepFinalizing').classList.add('hidden');
            document.getElementById('stepFieldReview').classList.remove('hidden');
        }, 2000);
    }
});

// Handle drag and drop
const dropArea = document.querySelector('.border-dashed');
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropArea.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    dropArea.classList.add('border-indigo-500', 'bg-indigo-50');
}

function unhighlight(e) {
    dropArea.classList.remove('border-indigo-500', 'bg-indigo-50');
}

dropArea.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length) {
        document.getElementById('templateFileInput').files = files;
        const event = new Event('change', { bubbles: true });
        document.getElementById('templateFileInput').dispatchEvent(event);
    }
}
</script>
<?php echo $this->endSection() ?>