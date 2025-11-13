<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>File Explorer<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include(
        'components/navigation',
        [
        'pageTitle' => 'File Explorer',
        'pageIcon' => 'folder-open',
        'stickyNav' => true,
        'showWelcome' => false
        ]
    ) ?>
<?php echo $this->endSection() ?>

<?php echo $this->section('pageStyles') ?>
    <style>
        .file-hierarchy ul {
            list-style-type: none;
            padding-left: 1.5rem;
            margin: 0;
        }
        
        .file-hierarchy li {
            margin-bottom: 0.5rem;
        }
        
        .file-hierarchy .file-item:hover,
        .file-hierarchy .folder-item > span:hover {
            background-color: #EFF6FF;
        }

        .glass-header {
            backdrop-filter: blur(8px);
            background-color: rgba(255, 255, 255, 0.8);
        }

        @keyframes spin {
            from {transform: rotate(0deg);}
            to {transform: rotate(360deg);}
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        .wizard-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .wizard-steps::before {
            content: "";
            position: absolute;
            top: 14px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e5e7eb;
            z-index: 0;
        }

        .wizard-step-item {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .wizard-step-circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .wizard-step-circle.active {
            border-color: #2563eb;
            background: #2563eb;
            color: white;
        }

        .wizard-step-circle.complete {
            border-color: #10b981;
            background: #10b981;
            color: white;
        }

        .wizard-step-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: #6b7280;
        }

        .modal-overlay {
            transition: opacity 0.3s ease-in-out;
        }

        .modal-content {
            transition: transform 0.3s ease-in-out;
        }

        .modal-content.scale-95 {
            transform: scale(0.95);
        }

        .modal-content.scale-100 {
            transform: scale(1);
        }

        .folder-chevron {
            transition: transform 0.2s ease-in-out;
        }
        
        .file-hierarchy .file-item {
            transition: all 0.2s ease;
        }

        .file-hierarchy .file-item:hover {
            transform: translateX(2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .file-hierarchy .folder-item > span {
            transition: all 0.2s ease;
        }

        .file-hierarchy .folder-item > span:hover {
            transform: translateX(2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .file-hierarchy .file-item.bg-blue-100,
        .file-hierarchy .folder-item > span.bg-blue-100 {
            background-color: #dbeafe !important;
            border-left: 4px solid #3b82f6;
            font-weight: 500;
        }

        .file-hierarchy .file-item.bg-green-100 {
            background-color: #dcfce7 !important;
            border-left: 4px solid #22c55e;
            font-weight: 500;
        }

        .button-loading {
            position: relative;
            pointer-events: none;
        }

        .button-loading::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 16px;
            height: 16px;
            margin: -8px 0 0 -8px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .button-loading .button-text {
            opacity: 0;
        }

        .fragment-highlight {
            background-color: #fef3c7 !important;
            border: 2px solid #f59e0b !important;
            animation: fragment-pulse 2s ease-in-out;
        }

        @keyframes fragment-pulse {
            0% { 
                background-color: #fef3c7;
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.7);
            }
            50% { 
                background-color: #fde68a;
                box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
            }
            100% { 
                background-color: #fef3c7;
                box-shadow: 0 0 0 0 rgba(245, 158, 11, 0);
            }
        }

        .loading-modal {
            transition: opacity 0.3s ease-in-out;
        }

        .loading-modal .modal-content {
            transition: transform 0.3s ease-in-out;
        }

        .loading-modal .modal-content.scale-95 {
            transform: scale(0.95);
        }

        .loading-modal .modal-content.scale-100 {
            transform: scale(1);
        }

        .loading-spinner {
            border: 4px solid #e5e7eb;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        .export-progress-bar {
            width: 100%;
            height: 4px;
            background-color: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 1rem;
        }

        .export-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8);
            border-radius: 2px;
            transition: width 0.3s ease;
            animation: progress-pulse 2s ease-in-out infinite;
        }

        @keyframes progress-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .field-type-selector {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }

        .field-input-container {
            position: relative;
        }

        .image-upload-area {
            border: 2px dashed #cbd5e0;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background: #f7fafc;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .image-upload-area:hover {
            border-color: #4299e1;
            background: #ebf8ff;
        }

        .image-upload-area.dragover {
            border-color: #3182ce;
            background: #bee3f8;
        }

        .image-preview {
            max-width: 100%;
            max-height: 200px;
            border-radius: 4px;
            margin-top: 0.5rem;
        }

        .field-type-icon {
            font-size: 0.75rem;
            margin-right: 0.25rem;
        }
    </style>
<?php echo $this->endSection() ?>

<?php echo $this->section('content') ?>
    <div class="flex flex-col md:flex-row gap-6">
                    <div class="w-full md:w-1/3 bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="glass-header p-4 border-b border-gray-200">
                    <input type="text" id="searchBox" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Search files...">
                </div>
                
                <?php if ($userPermissions['canCreateTemplates']) : ?>
                <div id="templateUploadSection" class="p-4 border-b border-gray-200 bg-gray-50">
                    <h4 class="text-lg font-semibold text-gray-700 mb-3">Upload New Template</h4>
                    <button id="openUploadWizardButton" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                        <i class="fas fa-upload mr-2"></i>Upload Template
                    </button>
                    <div id="uploadStatus" class="mt-2 text-sm text-gray-600"></div>
                </div>
                <?php endif; ?>
                
                <div class="p-4">
                    <h4 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-folder text-blue-500 mr-2"></i>Template Files
                    </h4>
                    <div class="file-hierarchy overflow-y-auto max-h-96" id="fileHierarchy">
                        <ul class="list-none p-0">
                            <?php if (!empty($templates)) : ?>
                                <?php foreach ($templates as $template) : ?>
                                    <li class="folder-item mb-2" data-template-id="<?php echo esc($template['id']) ?>">
                                        <span class="flex items-center p-2 rounded-md hover:bg-blue-50 cursor-pointer">
                                            <i class="fas fa-chevron-right text-gray-400 mr-2 transition-transform duration-200 folder-chevron"></i>
                                            <i class="fas fa-folder-open text-blue-500 mr-2"></i>
                                            <?php echo esc($template['name']) ?>
                                        </span>
                                        <ul class="pl-6" style="display: none;">
                                            <?php if (!empty($template['filledFiles'])) : ?>
                                                <?php foreach ($template['filledFiles'] as $filledFile) : ?>
                                                    <li class="file-item p-2 rounded-md hover:bg-blue-50 cursor-pointer mb-1 flex items-center" 
                                                        data-id="<?php echo esc($filledFile['id']) ?>"
                                                        data-name="<?php echo esc($filledFile['name']) ?>"
                                                        data-template-id="<?php echo esc($template['id']) ?>">
                                                        <i class="fas fa-edit text-green-500 mr-2"></i>
                                                        <?php echo esc($filledFile['name']) ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <li class="text-gray-500 p-2 italic">No filled files for this template.</li>
                                            <?php endif; ?>
                                        </ul>
                                    </li>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <li class="text-gray-500 p-4 text-center italic">No templates found.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

                        <div class="w-full md:w-2/3 bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="glass-header p-4 border-b border-gray-200">
                    <h2 id="fileNameHeading" class="text-xl font-bold text-gray-800">Select a file</h2>
                </div>
                
                <div id="fileActions" class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex flex-wrap gap-2" style="display: none;">
                    <button id="editButton" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </button>
                    <button id="saveButton" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center" style="display: none;">
                        <i class="fas fa-save mr-2"></i>Save
                    </button>
                    <button id="cancelButton" class="bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center" style="display: none;">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <div id="exportButtonGroup" class="relative" style="display: none;">
                        <button id="exportButton" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center">
                            <i class="fas fa-download mr-2"></i>Export
                            <i class="fas fa-chevron-down ml-2"></i>
                        </button>
                        <div id="exportDropdown" class="hidden absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-full">
                            <button id="exportDocxButton" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center">
                                <i class="fas fa-file-word text-blue-600 mr-2"></i>Export as DOCX
                            </button>
                            <button id="exportPdfButton" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center">
                                <i class="fas fa-file-pdf text-red-600 mr-2"></i>Export as PDF
                            </button>
                        </div>
                    </div>
                    <button id="deleteFilledFileButton" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center ml-auto" style="display: none;">
                        <i class="fas fa-trash mr-2"></i><span id="deleteFilledFileText">Delete File</span>
                    </button>
                    <button id="deleteTemplateButton" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center ml-auto" style="display: none;">
                        <i class="fas fa-trash-alt mr-2"></i><span id="deleteTemplateText">Delete Template</span>
                    </button>
                </div>
                
                <div id="fileDetails" class="p-6">
                    <div class="flex flex-col items-center justify-center text-center p-8">
                        <i class="fas fa-folder-open text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-600">Select a template or file from the sidebar to view its details here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <div id="uploadWizardModal" class="wizard-modal hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="wizard-content bg-white rounded-lg shadow-xl max-w-xl w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Template Upload Wizard</h2>
                <span class="close-wizard text-gray-600 text-2xl cursor-pointer hover:text-gray-800">&times;</span>
            </div>

                        <div class="wizard-steps mb-6">
                <div class="wizard-step-item">
                    <div id="step1Circle" class="wizard-step-circle active">1</div>
                    <div class="wizard-step-label">Upload</div>
                </div>
                <div class="wizard-step-item">
                    <div id="step2Circle" class="wizard-step-circle">2</div>
                    <div class="wizard-step-label">Analyze</div>
                </div>
                <div class="wizard-step-item">
                    <div id="step3Circle" class="wizard-step-circle">3</div>
                    <div class="wizard-step-label">Configure</div>
                </div>
                <div class="wizard-step-item">
                    <div id="step4Circle" class="wizard-step-circle">4</div>
                    <div class="wizard-step-label">Save</div>
                </div>
            </div>

                        <div id="wizardStepUploadFile" class="wizard-step active">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Step 1: Upload File</h3>
                <form id="wizardFileUploadForm">
                    <p class="mb-4 text-gray-600">Select a template file (.docx) containing placeholders in the format <code class="bg-gray-100 px-1 rounded">${placeholder_name}</code> that will be filled by users.</p>
                    <div id="fileDropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors cursor-pointer">
                        <input type="file" name="templateFileWizard" id="templateFileWizard" accept=".docx"
                            required class="hidden">
                        <div>
                            <div class="mb-3">
                                <i class="fas fa-cloud-upload-alt text-gray-400 text-4xl"></i>
                            </div>
                            <span id="fileLabel" class="text-gray-500">Click to browse or drop files here</span>
                        </div>
                    </div>
                    <div id="filePreview" class="mt-3 hidden">
                        <div class="bg-blue-50 p-3 rounded-lg flex items-center">
                            <i class="fas fa-file-alt text-blue-500 mr-3"></i>
                            <div class="flex-grow">
                                <div id="fileName" class="font-medium text-blue-700"></div>
                                <div id="fileSize" class="text-sm text-gray-500"></div>
                            </div>
                            <button type="button" onclick="Templately.FileExplorer.clearFileSelection()" class="text-gray-500 hover:text-red-500">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2 mt-6">
                        <button type="button" id="cancelStep1" class="bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                        <button type="submit" id="nextStep1" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center">
                            <i class="fas fa-arrow-right mr-2"></i>Next: Analyze
                        </button>
                    </div>
                </form>
            </div>

                        <div id="wizardStepAnalyzingFile" class="wizard-step hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Step 2: Analyzing File</h3>
                <p class="mb-4 text-gray-600">Please wait while the file is being analyzed...</p>
                <div class="bg-blue-50 p-6 rounded-lg text-center">
                    <div class="spinner mx-auto mb-4 w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full"></div>
                    <div id="analysisStatus" class="text-blue-700 font-medium">Processing document...</div>
                    <div class="text-gray-500 text-sm mt-2">Extracting fields and placeholders</div>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="button" id="cancelStep2" class="bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center">
                        <i class="fas fa-times mr-2"></i>Cancel Upload
                    </button>
                </div>
            </div>

                        <div id="wizardStepNameReview" class="wizard-step hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Step 3: Name and Review</h3>
                <form id="wizardNameReviewForm">
                    <div class="mb-4">
                        <label for="templateNameWizard" class="block text-sm font-medium text-gray-700 mb-1">Template Name:</label>
                        <input type="text" id="templateNameWizard" name="templateNameWizard" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-1">Original File:</p>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <i class="fas fa-file mr-2 text-gray-500"></i>
                                <span id="originalFileNameReview" class="text-gray-600"></span>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-1">File Size:</p>
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <i class="fas fa-weight-hanging mr-2 text-gray-500"></i>
                                <span id="fileSizeReview" class="text-gray-600"></span>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm font-medium text-gray-700 mb-1">Detected Fields:</p>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                        <div id="detectedFieldsList" class="max-h-36 overflow-y-auto text-gray-600 text-sm">
                            <!-- TO BE POPULATED -->
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2 mt-4">
                        <button type="button" id="backStep3" class="bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i>Back
                        </button>
                        <button type="button" id="cancelStep3" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </button>
                        <button type="submit" id="finishWizard" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center">
                            <i class="fas fa-check mr-2"></i>Finish & Save
                        </button>
                    </div>
                </form>
            </div>

                        <div id="wizardStepSavingTemplate" class="wizard-step hidden">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Step 4: Saving Template</h3>
                <p class="mb-4 text-gray-600">Please wait while the template is being saved...</p>
                <div class="bg-green-50 p-6 rounded-lg text-center">
                    <div class="spinner mx-auto mb-4 w-12 h-12 border-4 border-green-200 border-t-green-600 rounded-full"></div>
                    <div id="finalizingStatus" class="text-green-700 font-medium">Saving template...</div>
                    <div class="text-gray-500 text-sm mt-2">Your template will be available in a moment</div>
                </div>
                <div class="flex justify-end mt-6">
                    <button type="button" id="cancelStep4" class="bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center">
                        <i class="fas fa-times mr-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

        <div id="successModal" class="modal-overlay hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-lg shadow-xl max-w-md w-full p-6 transform transition-all duration-300 scale-95">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500 text-3xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-semibold text-gray-900">Success</h3>
                </div>
            </div>
            <div class="mb-6">
                <p id="successMessage" class="text-gray-700"></p>
            </div>
            <div class="flex justify-end">
                <button id="successModalClose" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>

        <div id="errorModal" class="modal-overlay hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-lg shadow-xl max-w-md w-full p-6 transform transition-all duration-300 scale-95">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-500 text-3xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-semibold text-gray-900">Error</h3>
                </div>
            </div>
            <div class="mb-6">
                <p id="errorMessage" class="text-gray-700"></p>
            </div>
            <div class="flex justify-end">
                <button id="errorModalClose" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
            </div>
        </div>
    </div>

        <div id="confirmModal" class="modal-overlay hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-lg shadow-xl max-w-md w-full p-6 transform transition-all duration-300 scale-95">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <i class="fas fa-question-circle text-yellow-500 text-3xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-semibold text-gray-900">Confirm Action</h3>
                </div>
            </div>
            <div class="mb-6">
                <p id="confirmMessage" class="text-gray-700"></p>
            </div>
            <div class="flex justify-end space-x-3">
                <button id="confirmCancel" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button id="confirmOk" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-check mr-2"></i>Confirm
                </button>
            </div>
        </div>
    </div>

        <div id="inputModal" class="modal-overlay hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-lg shadow-xl max-w-md w-full p-6 transform transition-all duration-300 scale-95">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <i class="fas fa-edit text-blue-500 text-3xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-semibold text-gray-900">Input Required</h3>
                </div>
            </div>
            <div class="mb-6">
                <p id="inputMessage" class="text-gray-700 mb-3"></p>
                <input type="text" id="inputValue" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Enter value...">
            </div>
            <div class="flex justify-end space-x-3">
                <button id="inputCancel" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button id="inputOk" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-check mr-2"></i>OK
                </button>
            </div>
        </div>
    </div>

    <div id="loadingModal" class="loading-modal modal-overlay hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-lg shadow-xl max-w-md w-full p-6 transform transition-all duration-300 scale-95">
            <div class="flex justify-between items-center mb-4">
                <h3 id="loadingTitle" class="text-lg font-semibold text-gray-900">Processing...</h3>
                <button id="loadingModalClose" class="text-gray-600 text-2xl cursor-pointer hover:text-gray-800 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="text-center">
                <div class="loading-spinner mb-4"></div>
                <p id="loadingMessage" class="text-gray-600 mb-4">Please wait while we process your request.</p>
                <div id="loadingStatus" class="text-sm text-gray-500 mt-2">Initializing...</div>
            </div>
        </div>
    </div>
<?php echo $this->endSection() ?>

<?php echo $this->section('pageScripts') ?>
    <script src="/assets/js/templately-utils.js"></script>
    <script src="/assets/js/modules/file-explorer-main.js"></script>
    <script src="/assets/js/modules/template-wizard.js"></script>
    <script src="/assets/js/modules/filled-file-editor.js"></script>
    <script src="/assets/js/app.js"></script>
    <script>
        Templately.Modal.init({
            success: document.getElementById('successModal'),
            error: document.getElementById('errorModal'),
            confirm: document.getElementById('confirmModal'),
            input: document.getElementById('inputModal'),
            loading: document.getElementById('loadingModal')
        });

        const uploadWizardModal = document.getElementById('uploadWizardModal');
        const loadingModal = document.getElementById('loadingModal');
        
        window.clearImageSelection = function(fieldName) {
            Templately.FileExplorer.clearImageSelection(fieldName);
        };

        function hideModal(type) {
            Templately.Modal.hideModal(type);
        }

        function setModalFocus(type) {
            Templately.Modal.setModalFocus(type);
        }

        function handleGlobalKeydown(e) {
            if (!Templately.Modal.currentModal) {
                if (!uploadWizardModal.classList.contains('hidden')) {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        resetWizard();
                        return;
                    }
                } else if (!loadingModal.classList.contains('hidden')) {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        hideLoadingModal();
                        return;
                    }
                }
                return;
            }
            
            Templately.Modal.handleGlobalKeydown(e);
        }

        document.addEventListener('keydown', handleGlobalKeydown);

        function handleWizardKeydown(e) {
            if (uploadWizardModal.classList.contains('hidden')) return;

            if (e.key === 'Escape') {
                e.preventDefault();
                resetWizard();
                return;
            }

            if (e.key === 'Enter') {
                e.preventDefault();
                const currentStep = wizardState.currentStep;
                
                if (currentStep === 'stepUploadFile') {
                    const fileInput = document.getElementById('templateFileWizard');
                    if (fileInput.files && fileInput.files.length > 0) {
                        wizardFileUploadForm.dispatchEvent(new Event('submit'));
                    }
                } else if (currentStep === 'stepNameReview') {
                    const nameInput = document.getElementById('templateNameWizard');
                    if (nameInput.value.trim()) {
                        wizardNameReviewForm.dispatchEvent(new Event('submit'));
                    }
                }
                return;
            }
        }

        document.addEventListener('keydown', handleWizardKeydown);

        function hideModal(type) {
            Templately.Modal.hideModal(type);
        }

        function setModalFocus(type) {
            Templately.Modal.setModalFocus(type);
        }

        function showInputModal(message) {
            document.getElementById('inputMessage').textContent = message;
            return new Promise((resolve) => {
                Templately.Modal.showModal('input', resolve);
            });
        }

        function showConfirmModal(message) {
            document.getElementById('confirmMessage').textContent = message;
            return new Promise((resolve) => {
                Templately.Modal.showModal('confirm', resolve);
            });
        }

        function showSuccessModal(message) {
            document.getElementById('successMessage').textContent = message;
            Templately.Modal.showModal('success');
        }

        function showErrorModal(message) {
            document.getElementById('errorMessage').textContent = message;
            Templately.Modal.showModal('error');
        }

        document.getElementById('successModalClose').addEventListener('click', () => hideModal('success'));
        document.getElementById('errorModalClose').addEventListener('click', () => hideModal('error'));

        // Ensure file input change event updates the UI
        const templateFileInputGlobal = document.getElementById('templateFileWizard');
        if (templateFileInputGlobal) {
            templateFileInputGlobal.addEventListener('change', function() {
                if (typeof Templately !== 'undefined' && Templately.FileExplorer && Templately.FileExplorer.updateFileLabel) {
                    Templately.FileExplorer.updateFileLabel();
                }
            });
        }

        // Ensure dropZone click opens file picker
        const fileDropZoneGlobal = document.getElementById('fileDropZone');
        if (fileDropZoneGlobal && templateFileInputGlobal) {
            fileDropZoneGlobal.addEventListener('click', function(e) {
                templateFileInputGlobal.click();
            });
        }

        function updateWizardSteps(currentStep) {
            const steps = [
                document.getElementById('step1Circle'),
                document.getElementById('step2Circle'),
                document.getElementById('step3Circle'),
                document.getElementById('step4Circle')
            ];
            
            steps.forEach((step, index) => {
                step.classList.remove('active', 'complete');
                if (index + 1 < currentStep) {
                    step.classList.add('complete');
                    step.innerHTML = '<i class="fas fa-check"></i>';
                } else if (index + 1 === currentStep) {
                    step.classList.add('active');
                    step.textContent = index + 1;
                } else {
                    step.textContent = index + 1;
                }
            });
        }

        function showLoadingModal(title = 'Processing...', message = 'Please wait while we process your request.') {
            currentModal = 'loading';
            loadingTitle.textContent = title;
            loadingMessage.textContent = message;
            loadingStatus.textContent = 'Processing...';
            
            loadingModal.classList.remove('hidden');
            setTimeout(() => {
                loadingModal.querySelector('.modal-content').classList.remove('scale-95');
                loadingModal.querySelector('.modal-content').classList.add('scale-100');
                
                // Focus the close button
                const closeButton = document.getElementById('loadingModalClose');
                if (closeButton) {
                    setTimeout(() => closeButton.focus(), 100);
                }
            }, 10);
        }

        function hideLoadingModal() {
            if (currentModal === 'loading') {
                currentModal = null;
            }
            
            loadingModal.querySelector('.modal-content').classList.remove('scale-100');
            loadingModal.querySelector('.modal-content').classList.add('scale-95');
            setTimeout(() => {
                loadingModal.classList.add('hidden');
            }, 150);
        }

        function updateLoadingStatus(status) {
            loadingStatus.textContent = status;
        }

        document.addEventListener('DOMContentLoaded', () => {
            const fileHierarchy = document.getElementById('fileHierarchy');
            const fileNameHeading = document.getElementById('fileNameHeading');
            const fileDetails = document.getElementById('fileDetails');
            const searchBox = document.getElementById('searchBox');
            const fileActionsDiv = document.getElementById('fileActions');
            const editButton = document.getElementById('editButton');
            const saveButton = document.getElementById('saveButton');
            const cancelButton = document.getElementById('cancelButton');
            const deleteFilledFileButton = document.getElementById('deleteFilledFileButton');
            const deleteFilledFileText = document.getElementById('deleteFilledFileText');
            const deleteTemplateButton = document.getElementById('deleteTemplateButton');
            const deleteTemplateText = document.getElementById('deleteTemplateText');
            const exportButtonGroup = document.getElementById('exportButtonGroup');
            const exportButton = document.getElementById('exportButton');
            const exportDropdown = document.getElementById('exportDropdown');
            const exportDocxButton = document.getElementById('exportDocxButton');
            const exportPdfButton = document.getElementById('exportPdfButton');
            const uploadStatusDiv = document.getElementById('uploadStatus');

            const loadingTitle = document.getElementById('loadingTitle');
            const loadingMessage = document.getElementById('loadingMessage');
            const loadingStatus = document.getElementById('loadingStatus');
            const loadingModalClose = document.getElementById('loadingModalClose');

            // Upload wizard elements (may not exist if user doesn't have upload permission)
            const wizardFileUploadForm = document.getElementById('wizardFileUploadForm');
            const wizardTemplateFileInput = document.getElementById('templateFileWizard');
            const nextStepUploadFileButton = document.getElementById('nextStep1');
            const cancelStepUploadFileButton = document.getElementById('cancelStep1');

            const analysisStatusDiv = document.getElementById('analysisStatus');
            const cancelStepAnalyzingFileButton = document.getElementById('cancelStep2');

            const wizardNameReviewForm = document.getElementById('wizardNameReviewForm');
            const templateNameWizardInput = document.getElementById('templateNameWizard');
            const originalFileNameReviewSpan = document.getElementById('originalFileNameReview');
            const fileSizeReviewSpan = document.getElementById('fileSizeReview');
            const detectedFieldsListUl = document.getElementById('detectedFieldsList');
            const backStepNameReviewButton = document.getElementById('backStep3');
            const cancelStepNameReviewButton = document.getElementById('cancelStep3');
            const finishStepNameReviewButton = document.getElementById('finishWizard');

            const finalizingStatusDiv = document.getElementById('finalizingStatus');
            const cancelStepSavingTemplateButton = document.getElementById('cancelStep4');

            const openUploadWizardButton = document.getElementById('openUploadWizardButton');
            const closeWizardButton = uploadWizardModal ? uploadWizardModal.querySelector('.close-wizard') : null;

            const wizardSteps = wizardFileUploadForm ? {
                stepUploadFile: document.getElementById('wizardStepUploadFile'),
                stepAnalyzingFile: document.getElementById('wizardStepAnalyzingFile'),
                stepNameReview: document.getElementById('wizardStepNameReview'),
                stepSavingTemplate: document.getElementById('wizardStepSavingTemplate')
            } : {};

            let currentSelectedFilledFile = null;
            let currentSelectedTemplate = null;
            let originalFilledData = null;

            const templatesData = <?php echo json_encode($templates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || [];
            const userPermissions = <?php echo json_encode($userPermissions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;

            function findFilledFileById(filledFileId) {
                for (const template of templatesData) {
                    if (template.filledFiles && Array.isArray(template.filledFiles)) {
                        const foundFile = template.filledFiles.find(ff => ff.id && ff.id.toString() === filledFileId.toString());
                        if (foundFile) {
                            if (typeof foundFile.filledData === 'string') {
                                try {
                                    foundFile.filledData = JSON.parse(foundFile.filledData || '{}');
                                } catch (e) {
                                    console.error("Error parsing filledData JSON:", e, foundFile.filledData);
                                    foundFile.filledData = {};
                                }
                            } else if (typeof foundFile.filledData !== 'object' || foundFile.filledData === null) {
                                foundFile.filledData = {};
                            }
                            return foundFile;
                        }
                    }
                }
                return null;
            }

            function findTemplateById(templateId) {
                return templatesData.find(t => t.id && t.id.toString() === templateId.toString());
            }

            // Only initialize wizard if upload form exists (user has upload permission)
            if (wizardFileUploadForm) {
                let wizardState = {
                currentStep: 'stepUploadFile',
                uploadedFile: null,
                tempFilePath: null,
                originalFileName: null,
                detectedFields: [],
                fileMimeType: null,
                fileSizeKB: null
            };

            function showWizardStep(stepName) {
                Object.values(wizardSteps).forEach(step => {
                    step.classList.add('hidden');
                    step.classList.remove('active');
                });
                
                if (wizardSteps[stepName]) {
                    wizardSteps[stepName].classList.remove('hidden');
                    wizardSteps[stepName].classList.add('active');
                    wizardState.currentStep = stepName;
                    
                                        let stepNumber = 1;
                    if (stepName === 'stepAnalyzingFile') stepNumber = 2;
                    if (stepName === 'stepNameReview') stepNumber = 3;
                    if (stepName === 'stepSavingTemplate') stepNumber = 4;
                    updateWizardSteps(stepNumber);
                }
            }

            function resetWizard() {
                wizardFileUploadForm.reset();
                wizardNameReviewForm.reset();
                wizardState = {
                    currentStep: 'stepUploadFile',
                    uploadedFile: null,
                    tempFilePath: null,
                    originalFileName: null,
                    detectedFields: [],
                    fileMimeType: null,
                    fileSizeKB: null
                };
                detectedFieldsListUl.innerHTML = '';
                analysisStatusDiv.textContent = 'Processing document...';
                finalizingStatusDiv.textContent = 'Saving template...';
                uploadWizardModal.classList.add('hidden');
                showWizardStep('stepUploadFile');
                Templately.FileExplorer.clearFileSelection();
            }

            openUploadWizardButton.addEventListener('click', () => {
                uploadWizardModal.classList.remove('hidden');
                showWizardStep('stepUploadFile');
            });

            closeWizardButton.addEventListener('click', resetWizard);
            cancelStepUploadFileButton.addEventListener('click', resetWizard);
            cancelStepAnalyzingFileButton.addEventListener('click', () => {
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-lg';
                toast.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i>Upload cancelled.</div>';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
                resetWizard();
            });
            cancelStepNameReviewButton.addEventListener('click', () => {
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-lg';
                toast.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i>Upload cancelled.</div>';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
                resetWizard();
            });
            cancelStepSavingTemplateButton.addEventListener('click', () => {
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-lg';
                toast.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i>Upload process cancelled.</div>';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
                resetWizard();
            });

            window.addEventListener('click', (event) => {
                if (event.target == uploadWizardModal) {
                    resetWizard();
                }
            });

            wizardFileUploadForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                if (!wizardTemplateFileInput.files || wizardTemplateFileInput.files.length === 0) {
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-lg';
                    toast.innerHTML = '<div class="flex items-center"><i class="fas fa-exclamation-circle mr-2"></i>Please select a file to upload.</div>';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 3000);
                    return;
                }
                wizardState.uploadedFile = wizardTemplateFileInput.files[0];
                wizardState.originalFileName = wizardState.uploadedFile.name;
                wizardState.fileMimeType = wizardState.uploadedFile.type;
                wizardState.fileSizeKB = (wizardState.uploadedFile.size / 1024).toFixed(2);

                showWizardStep('stepAnalyzingFile');
                analysisStatusDiv.textContent = 'Analyzing file...';

                const formData = new FormData();
                formData.append('templateFile', wizardState.uploadedFile);
                const csrfToken = document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content');
                formData.append('<?php echo csrf_token() ?>', csrfToken);

                try {
                    const response = await fetch('/file-explorer/analyze-template', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    const result = await response.json();

                    if (response.ok && result.success) {
                        wizardState.tempFilePath = result.tempFilePath;
                        wizardState.detectedFields = result.detectedFields || result.templateFields || [];

                        originalFileNameReviewSpan.textContent = wizardState.originalFileName;
                        fileSizeReviewSpan.textContent = wizardState.fileSizeKB + ' KB';
                        templateNameWizardInput.value = wizardState.originalFileName.split('.').slice(0, -1).join('.') || wizardState.originalFileName;

                        detectedFieldsListUl.innerHTML = '';
                        if (wizardState.detectedFields.length > 0) {
                            wizardState.detectedFields.forEach(field => {
                                const li = document.createElement('li');
                                li.className = 'py-1 px-2 flex items-center bg-white rounded border';
                                
                                // Handle both string and object field formats
                                const fieldName = typeof field === 'string' ? field : (field.name || field.field || field);
                                const displayName = fieldName.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                
                                li.innerHTML = `
                                    <i class="fas fa-tag text-blue-500 mr-2"></i> 
                                    <code class="bg-gray-100 px-2 py-1 rounded text-sm">\${${fieldName}}</code>
                                    <span class="ml-2 text-gray-600">→ ${displayName}</span>
                                `;
                                detectedFieldsListUl.appendChild(li);
                            });
                        } else {
                            const li = document.createElement('li');
                            li.className = 'py-2 px-2 flex items-center text-amber-600 bg-amber-50 rounded border border-amber-200';
                            li.innerHTML = `
                                <i class="fas fa-info-circle mr-2"></i> 
                                <span>No placeholder fields detected. Make sure your template uses the format <code class="bg-amber-100 px-1 rounded">\${field_name}</code></span>
                            `;
                            detectedFieldsListUl.appendChild(li);
                        }
                        showWizardStep('stepNameReview');
                    } else {
                        analysisStatusDiv.textContent = `Analysis failed: ${result.message || 'Unknown error'}`;
                        analysisStatusDiv.style.color = 'red';
                        setTimeout(() => showWizardStep('stepUploadFile'), 3000);
                    }
                } catch (error) {
                    console.error('Error analyzing file:', error);
                    analysisStatusDiv.textContent = `Analysis error: ${error.message}`;
                    analysisStatusDiv.style.color = 'red';
                    setTimeout(() => showWizardStep('stepUploadFile'), 3000);
                }
            });

            backStepNameReviewButton.addEventListener('click', () => {
                wizardFileUploadForm.reset();
                wizardState.uploadedFile = null;
                wizardState.tempFilePath = null;
                showWizardStep('stepUploadFile');
            });

            function refreshSidebar() {
                const fileHierarchyUl = fileHierarchy.querySelector('ul');
                fileHierarchyUl.innerHTML = ''; 
                if (templatesData.length === 0) {
                    const noTemplatesLi = document.createElement('li');
                    noTemplatesLi.className = 'text-gray-500 p-4 text-center italic';
                    noTemplatesLi.textContent = 'No templates found.';
                    fileHierarchyUl.appendChild(noTemplatesLi);
                    return;
                }

                templatesData.forEach(template => {
                    const templateLi = document.createElement('li');
                    templateLi.className = 'folder-item mb-2';
                    templateLi.dataset.templateId = template.id;

                    const templateSpan = document.createElement('span');
                    templateSpan.className = 'flex items-center p-2 rounded-md hover:bg-blue-50 cursor-pointer';
                    
                    const chevronIcon = document.createElement('i');
                    chevronIcon.className = 'fas fa-chevron-right text-gray-400 mr-2 transition-transform duration-200 folder-chevron';
                    templateSpan.appendChild(chevronIcon);
                    
                    const folderIcon = document.createElement('i');
                    folderIcon.className = 'fas fa-folder-open text-yellow-500 mr-2';
                    templateSpan.appendChild(folderIcon);
                    
                    const templateText = document.createTextNode(template.name);
                    templateSpan.appendChild(templateText);
                    templateLi.appendChild(templateSpan);

                    const filesUl = document.createElement('ul');
                    filesUl.className = 'pl-6';
                    filesUl.style.display = 'none';                     
                    if (template.filledFiles && template.filledFiles.length > 0) {
                        template.filledFiles.forEach(filledFile => {
                            const fileLi = document.createElement('li');
                            fileLi.className = 'file-item p-2 rounded-md hover:bg-green-50 cursor-pointer mb-1 flex items-center';
                            fileLi.dataset.id = filledFile.id || '';
                            fileLi.dataset.name = filledFile.name || 'Unnamed File';
                            fileLi.dataset.templateId = template.id || '';
                            
                            const fileIcon = document.createElement('i');
                            fileIcon.className = 'fas fa-edit text-green-500 mr-2';
                            fileLi.appendChild(fileIcon);
                            
                            const fileText = document.createTextNode(filledFile.name);
                            fileLi.appendChild(fileText);
                            filesUl.appendChild(fileLi);
                        });
                    } else {
                        const noFilesLi = document.createElement('li');
                        noFilesLi.className = 'text-gray-500 p-2 italic';
                        noFilesLi.textContent = 'No filled files for this template.';
                        filesUl.appendChild(noFilesLi);
                    }
                    templateLi.appendChild(filesUl);
                    fileHierarchyUl.appendChild(templateLi);
                });
                
                const activeFile = currentSelectedFilledFile ? fileHierarchy.querySelector(`.file-item[data-id='${currentSelectedFilledFile.id}']`) : null;
                const activeTemplate = currentSelectedTemplate ? fileHierarchy.querySelector(`.folder-item[data-template-id='${currentSelectedTemplate.id}']`) : null;

                        document.querySelectorAll('.file-item.bg-blue-100, .file-item.bg-green-100').forEach(item => item.classList.remove('bg-blue-100', 'bg-green-100'));
                        document.querySelectorAll('.folder-item > span.bg-blue-100').forEach(span => span.classList.remove('bg-blue-100'));

                if (activeFile) {
                    activeFile.classList.add('bg-green-100');
                    const parentFolder = activeFile.closest('.folder-item');
                    if (parentFolder) {
                        const folderList = parentFolder.querySelector('ul');
                        const chevronIcon = parentFolder.querySelector('.folder-chevron');
                        if (folderList) {
                            folderList.style.display = 'block';
                            if (chevronIcon) {
                                chevronIcon.style.transform = 'rotate(90deg)';
                            }
                        }
                    }
                } else if (activeTemplate) {
                    const templateSpan = activeTemplate.querySelector('span');
                    if (templateSpan) templateSpan.classList.add('bg-blue-100');
                    const folderList = activeTemplate.querySelector('ul');
                    const chevronIcon = activeTemplate.querySelector('.folder-chevron');
                    if (folderList) {
                        folderList.style.display = 'block';
                        if (chevronIcon) {
                            chevronIcon.style.transform = 'rotate(90deg)';
                        }
                    }
                }
                
                setTimeout(() => {
                    document.querySelectorAll('.folder-item').forEach(folderItem => {
                        const folderList = folderItem.querySelector('ul');
                        const chevronIcon = folderItem.querySelector('.folder-chevron');
                        
                        if (folderList && chevronIcon) {
                            let isOpen = false;
                            
                            if (folderList.style.display === 'block') {
                                isOpen = true;
                            } else {
                                isOpen = false;
                            }
                            
                            chevronIcon.style.transform = isOpen ? 'rotate(90deg)' : 'rotate(0deg)';
                        }
                    });
                }, 50);             
            }

            function updateButtonColorsForFileType(isFilledFile) { // Could change param from bool to string, for now, this will work just fine
                if (isFilledFile) {
                    // Green for filled files
                    editButton.className = 'bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center';
                    exportButton.className = 'bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center';
                } else {
                    // Blue for templates
                    editButton.className = 'bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center';
                    exportButton.className = 'bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center';
                }
            }

            function renderFileDetails(fileData, mode = 'view') {
                fileDetails.innerHTML = '';
                fileNameHeading.textContent = fileData.name || 'File Details';
                fileActionsDiv.style.display = 'flex';

                // Update button colors for filled files (green) or templates (blue)
                const isFilledFile = fileData.hasOwnProperty('template_id') || currentSelectedFilledFile;
                updateButtonColorsForFileType(isFilledFile);

                if (!fileData.filledData || typeof fileData.filledData !== 'object') {
                    fileDetails.innerHTML = '<div class="p-4 text-center"><i class="fas fa-exclamation-circle text-yellow-500 text-3xl mb-2"></i><p class="text-gray-600">No structured data available for this file.</p></div>';
                    editButton.style.display = 'none';
                    saveButton.style.display = 'none';
                    cancelButton.style.display = 'none';
                    return;
                }

                const list = document.createElement('div');
                list.className = 'space-y-4';
                
                const fieldTypes = fileData.fieldTypes || {};
                const imageSizes = fileData.imageSizes || {};
                
                for (const [key, value] of Object.entries(fileData.filledData)) {
                    const item = document.createElement('div');
                    const fieldType = fieldTypes[key] || 'text';
                    
                    if (mode === 'edit') {
                        item.className = 'bg-white rounded-lg shadow-sm border border-gray-200 p-4';
                        
                        const headerDiv = document.createElement('div');
                        headerDiv.className = 'flex items-center justify-between mb-3';
                        
                        const label = document.createElement('label');
                        label.className = 'block text-sm font-medium text-gray-700';
                        label.textContent = key;
                        
                        const typeSelector = document.createElement('select');
                        typeSelector.className = 'text-xs px-2 py-1 border border-gray-300 rounded';
                        typeSelector.name = `fieldType_${key}`;
                        typeSelector.innerHTML = `
                            <option value="text" ${fieldType === 'text' ? 'selected' : ''}>
                                Text
                            </option>
                            <option value="paragraph" ${fieldType === 'paragraph' ? 'selected' : ''}>
                                Paragraph
                            </option>
                            <option value="image" ${fieldType === 'image' ? 'selected' : ''}>
                                Image
                            </option>
                        `;
                        
                        headerDiv.appendChild(label);
                        headerDiv.appendChild(typeSelector);
                        item.appendChild(headerDiv);
                        
                        const inputContainer = document.createElement('div');
                        inputContainer.className = 'field-input-container';
                        inputContainer.id = `input_container_${key}`;
                        
                        function createFieldInput(type, fieldName, fieldValue) {
                            if (type === 'image') {
                                const imageDiv = document.createElement('div');
                                imageDiv.className = 'space-y-3';
                                
                                const hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.name = fieldName;
                                hiddenInput.value = fieldValue || '';
                                
                                // Get saved image sizes or use defaults
                                const savedSizes = imageSizes[fieldName] || {};
                                const defaultWidth = savedSizes.width || 300;
                                const defaultHeight = savedSizes.height || 200;
                                const defaultRatio = savedSizes.ratio !== undefined ? savedSizes.ratio : true;
                                
                                const sizeControlsDiv = document.createElement('div');
                                sizeControlsDiv.className = 'bg-gray-50 p-3 rounded-lg border border-gray-200';
                                
                                const sizeLabel = document.createElement('div');
                                sizeLabel.className = 'text-sm font-medium text-gray-700 mb-2 flex items-center';
                                sizeLabel.innerHTML = '<i class="fas fa-ruler-combined mr-2 text-blue-500"></i>Image Size Settings:';
                                sizeControlsDiv.appendChild(sizeLabel);
                                
                                const sizeGrid = document.createElement('div');
                                sizeGrid.className = 'grid grid-cols-2 gap-3 mb-2';
                                
                                const widthDiv = document.createElement('div');
                                const widthLabel = document.createElement('label');
                                widthLabel.className = 'block text-xs font-medium text-gray-600 mb-1';
                                widthLabel.textContent = 'Width (px):';
                                const widthInput = document.createElement('input');
                                widthInput.type = 'number';
                                widthInput.name = `imageWidth_${fieldName}`;
                                widthInput.className = 'w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500';
                                widthInput.min = '50';
                                widthInput.max = '1000';
                                widthInput.value = defaultWidth;
                                widthInput.placeholder = '300';
                                widthInput.dataset.fieldName = fieldName;
                                widthDiv.appendChild(widthLabel);
                                widthDiv.appendChild(widthInput);
                                
                                const heightDiv = document.createElement('div');
                                const heightLabel = document.createElement('label');
                                heightLabel.className = 'block text-xs font-medium text-gray-600 mb-1';
                                heightLabel.textContent = 'Height (px):';
                                const heightInput = document.createElement('input');
                                heightInput.type = 'number';
                                heightInput.name = `imageHeight_${fieldName}`;
                                heightInput.className = 'w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500';
                                heightInput.min = '50';
                                heightInput.max = '1000';
                                heightInput.value = defaultHeight;
                                heightInput.placeholder = '200';
                                heightInput.dataset.fieldName = fieldName;
                                heightDiv.appendChild(heightLabel);
                                heightDiv.appendChild(heightInput);
                                
                                sizeGrid.appendChild(widthDiv);
                                sizeGrid.appendChild(heightDiv);
                                sizeControlsDiv.appendChild(sizeGrid);
                                
                                const ratioDiv = document.createElement('div');
                                ratioDiv.className = 'flex items-center';
                                const ratioCheckbox = document.createElement('input');
                                ratioCheckbox.type = 'checkbox';
                                ratioCheckbox.name = `imageRatio_${fieldName}`;
                                ratioCheckbox.className = 'mr-2 text-blue-600 focus:ring-blue-500 border-gray-300 rounded';
                                ratioCheckbox.checked = defaultRatio;
                                ratioCheckbox.dataset.fieldName = fieldName;
                                const ratioLabel = document.createElement('label');
                                ratioLabel.className = 'text-xs text-gray-600';
                                ratioLabel.textContent = 'Maintain aspect ratio';
                                ratioDiv.appendChild(ratioCheckbox);
                                ratioDiv.appendChild(ratioLabel);
                                sizeControlsDiv.appendChild(ratioDiv);
                                
                                // Store original image dimensions for aspect ratio calculation
                                let originalAspectRatio = defaultWidth / defaultHeight;
                                
                                // Function to get actual image dimensions
                                const getImageDimensions = (imageUrl) => {
                                    return new Promise((resolve) => {
                                        const img = new Image();
                                        img.onload = function() {
                                            resolve({ width: this.width, height: this.height });
                                        };
                                        img.onerror = function() {
                                            resolve({ width: defaultWidth, height: defaultHeight });
                                        };
                                        img.src = imageUrl;
                                    });
                                };
                                
                                // If there's a current image, get its actual dimensions for aspect ratio
                                if (fieldValue && fieldValue.trim()) {
                                    const currentImageUrl = getImageUrl(fieldValue, currentSelectedFilledFile.id);
                                    if (currentImageUrl) {
                                        getImageDimensions(currentImageUrl).then(dims => {
                                            originalAspectRatio = dims.width / dims.height;
                                            // Store it on the inputs for reference
                                            widthInput.dataset.originalAspectRatio = originalAspectRatio;
                                            heightInput.dataset.originalAspectRatio = originalAspectRatio;
                                        });
                                    }
                                }
                                
                                // Add aspect ratio adjustment listeners
                                widthInput.addEventListener('input', function() {
                                    if (ratioCheckbox.checked) {
                                        const aspectRatio = parseFloat(this.dataset.originalAspectRatio) || originalAspectRatio;
                                        const newWidth = parseInt(this.value) || defaultWidth;
                                        const newHeight = Math.round(newWidth / aspectRatio);
                                        heightInput.value = newHeight;
                                    }
                                });
                                
                                heightInput.addEventListener('input', function() {
                                    if (ratioCheckbox.checked) {
                                        const aspectRatio = parseFloat(this.dataset.originalAspectRatio) || originalAspectRatio;
                                        const newHeight = parseInt(this.value) || defaultHeight;
                                        const newWidth = Math.round(newHeight * aspectRatio);
                                        widthInput.value = newWidth;
                                    }
                                });
                                
                                imageDiv.appendChild(sizeControlsDiv);
                                
                                if (fieldValue && fieldValue.trim()) {
                                    const currentImageSection = document.createElement('div');
                                    currentImageSection.className = 'bg-white p-3 rounded border border-gray-200';
                                    
                                    const currentLabel = document.createElement('div');
                                    currentLabel.className = 'text-sm font-medium text-gray-700 mb-2';
                                    currentLabel.textContent = 'Current Image:';
                                    currentImageSection.appendChild(currentLabel);
                                    
                                    const currentImagePreview = document.createElement('div');
                                    currentImagePreview.className = 'mb-2';
                                    
                                    const currentImg = document.createElement('img');
                                    currentImg.className = 'max-h-32 object-contain rounded border border-gray-200';
                                    currentImg.style.maxWidth = '200px';
                                    
                                    const currentImageUrl = getImageUrl(fieldValue, currentSelectedFilledFile.id);
                                    if (currentImageUrl) {
                                        currentImg.src = currentImageUrl;
                                        currentImg.alt = `Current: ${getImageDisplayName(fieldValue)}`;
                                        
                                        currentImg.onerror = function() {
                                            currentImagePreview.innerHTML = `
                                                <div class="text-center py-2 text-gray-500 bg-gray-100 rounded border-2 border-dashed border-gray-300">
                                                    <i class="fas fa-image text-gray-400 mb-1"></i>
                                                    <p class="text-xs">Preview not available</p>
                                                </div>
                                            `;
                                        };
                                        
                                        currentImg.onload = function() {
                                            currentImg.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.1)';
                                        };
                                        
                                        currentImagePreview.appendChild(currentImg);
                                    } else {
                                        currentImagePreview.innerHTML = `
                                            <div class="text-center py-2 text-gray-500 bg-gray-100 rounded border-2 border-dashed border-gray-300">
                                                <i class="fas fa-image text-gray-400 mb-1"></i>
                                                <p class="text-xs">Preview not available</p>
                                            </div>
                                        `;
                                    }
                                    
                                    currentImageSection.appendChild(currentImagePreview);
                                    
                                    const currentImageName = document.createElement('div');
                                    currentImageName.className = 'text-xs text-gray-600 font-mono bg-white px-2 py-1 rounded border';
                                    currentImageName.textContent = getImageDisplayName(fieldValue);
                                    currentImageSection.appendChild(currentImageName);
                                    
                                    imageDiv.appendChild(currentImageSection);
                                }
                                
                                const uploadArea = document.createElement('div');
                                uploadArea.className = 'image-upload-area';
                                uploadArea.innerHTML = `
                                    <div class="mb-2">
                                        <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl"></i>
                                    </div>
                                    <p class="text-gray-600">Click to upload or drag image here</p>
                                `;
                                
                                const fileInput = document.createElement('input');
                                fileInput.type = 'file';
                                fileInput.name = `image_${fieldName}`;
                                fileInput.accept = 'image/*';
                                fileInput.className = 'hidden';
                                
                                uploadArea.addEventListener('click', () => fileInput.click());
                                
                                fileInput.addEventListener('change', function() {
                                    if (this.files && this.files[0]) {
                                        const file = this.files[0];
                                        hiddenInput.setAttribute('data-has-new-file', 'true');
                                        
                                        const reader = new FileReader();
                                        reader.onload = function(e) {
                                            let previewArea = imageDiv.querySelector('.mt-2');
                                            if (!previewArea) {
                                                previewArea = document.createElement('div');
                                                previewArea.className = 'mt-2';
                                                imageDiv.appendChild(previewArea);
                                            }
                                            previewArea.innerHTML = `
                                                <div class="bg-blue-50 p-3 rounded-lg border border-blue-200">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-sm font-medium text-blue-700">New image selected:</span>
                                                        <button type="button" onclick="clearImageSelection('${fieldName}')" class="text-red-500 hover:text-red-700">
                                                            <i class="fas fa-times-circle"></i>
                                                        </button>
                                                    </div>
                                                    <img src="${e.target.result}" alt="Preview" class="max-h-32 object-contain rounded border border-gray-200 mx-auto block" style="max-width: 200px;" id="preview_${fieldName}">
                                                    <div class="text-xs text-gray-600 mt-2 text-center">${file.name} (${(file.size / 1024).toFixed(1)} KB)</div>
                                                </div>
                                            `;
                                            previewArea.classList.remove('hidden');
                                            
                                            // Update aspect ratio based on newly uploaded image
                                            const previewImg = document.getElementById(`preview_${fieldName}`);
                                            if (previewImg) {
                                                previewImg.onload = function() {
                                                    const newAspectRatio = this.naturalWidth / this.naturalHeight;
                                                    widthInput.dataset.originalAspectRatio = newAspectRatio;
                                                    heightInput.dataset.originalAspectRatio = newAspectRatio;
                                                    
                                                    // If aspect ratio is checked, update dimensions to match new image
                                                    if (ratioCheckbox.checked) {
                                                        const currentWidth = parseInt(widthInput.value) || defaultWidth;
                                                        const newHeight = Math.round(currentWidth / newAspectRatio);
                                                        heightInput.value = newHeight;
                                                    }
                                                    
                                                    console.log(`Updated aspect ratio for ${fieldName}: ${newAspectRatio.toFixed(2)} (${this.naturalWidth}x${this.naturalHeight})`);
                                                };
                                            }
                                        };
                                        reader.readAsDataURL(file);
                                    }
                                });
                                
                                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                                    uploadArea.addEventListener(eventName, function(e) {
                                        e.preventDefault();
                                        e.stopPropagation();
                                    });
                                });
                                
                                ['dragenter', 'dragover'].forEach(eventName => {
                                    uploadArea.addEventListener(eventName, function() {
                                        uploadArea.classList.add('dragover');
                                    });
                                });
                                
                                ['dragleave', 'drop'].forEach(eventName => {
                                    uploadArea.addEventListener(eventName, function() {
                                        uploadArea.classList.remove('dragover');
                                    });
                                });
                                
                                uploadArea.addEventListener('drop', function(e) {
                                    const files = e.dataTransfer.files;
                                    if (files.length > 0) {
                                        fileInput.files = files;
                                        fileInput.dispatchEvent(new Event('change'));
                                    }
                                });
                                
                                imageDiv.appendChild(hiddenInput);
                                imageDiv.appendChild(uploadArea);
                                imageDiv.appendChild(fileInput);
                                
                                return imageDiv;
                            } else if (type === 'paragraph') {
                                const textarea = document.createElement('textarea');
                                textarea.name = fieldName;
                                textarea.value = fieldValue || '';
                                textarea.className = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500';
                                textarea.rows = 4;
                                return textarea;
                            } else {
                                const input = document.createElement('input');
                                input.type = 'text';
                                input.name = fieldName;
                                input.value = fieldValue || '';
                                input.className = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500';
                                return input;
                            }
                        }
                        
                        const fieldInput = createFieldInput(fieldType, key, value);
                        inputContainer.appendChild(fieldInput);
                        item.appendChild(inputContainer);
                        
                        typeSelector.value = fieldType;
                        
                        typeSelector.addEventListener('change', function() {
                            const newType = this.value;
                            let currentValue = '';
                            const existingInput = inputContainer.querySelector('input[name="' + key + '"]');
                            if (existingInput) {
                                currentValue = existingInput.value;
                            } else {
                                const existingTextarea = inputContainer.querySelector('textarea[name="' + key + '"]');
                                if (existingTextarea) {
                                    currentValue = existingTextarea.value;
                                }
                            }
                            const newInput = createFieldInput(newType, key, currentValue || value);
                            inputContainer.innerHTML = '';
                            inputContainer.appendChild(newInput);
                        });
                        
                    } else {
                        // View mode
                        item.className = 'bg-white rounded-lg shadow-sm border border-gray-200 p-4';
                        
                        const keyEl = document.createElement('div');
                        keyEl.className = 'text-sm font-medium text-gray-700 mb-2 flex items-center';
                        keyEl.innerHTML = `<i class="fas fa-tag mr-2 text-gray-500"></i>${key}`;
                        item.appendChild(keyEl);

                        const valueEl = document.createElement('div');
                        
                        if (fieldType === 'image' && value && value.trim()) {
                            valueEl.className = 'px-4 py-3 text-gray-800 space-y-3';
                            
                            const imageContainer = document.createElement('div');
                            imageContainer.className = 'space-y-2';
                            
                            const imageInfo = document.createElement('div');
                            imageInfo.className = 'flex items-center text-sm text-gray-600';
                            imageInfo.innerHTML = `<i class="fas fa-image mr-2 text-purple-500"></i>Image: ${getImageDisplayName(value)}`;
                            imageContainer.appendChild(imageInfo);
                            
                            const imagePreview = document.createElement('div');
                            imagePreview.className = 'border border-gray-200 rounded-lg p-2 bg-gray-50';
                            
                            const img = document.createElement('img');
                            img.className = 'max-w-full max-h-48 object-contain rounded mx-auto block';
                            img.style.maxWidth = '300px';
                            
                            const imageUrl = getImageUrl(value, fileData.id);
                            if (imageUrl) {
                                img.src = imageUrl;
                                img.alt = `Preview of ${getImageDisplayName(value)}`;
                                
                                img.onerror = function() {
                                    imagePreview.innerHTML = `
                                        <div class="text-center py-4 text-gray-500">
                                            <i class="fas fa-image text-gray-400 text-2xl mb-2"></i>
                                            <p class="text-sm">Image preview not available</p>
                                            <p class="text-xs text-gray-400">${getImageDisplayName(value)}</p>
                                        </div>
                                    `;
                                };
                                
                                img.onload = function() {
                                    img.style.border = '1px solid #e5e7eb';
                                    img.style.boxShadow = '0 1px 3px rgba(0, 0, 0, 0.1)';
                                };
                                
                                imagePreview.appendChild(img);
                            } else {
                                imagePreview.innerHTML = `
                                    <div class="text-center py-4 text-gray-500">
                                        <i class="fas fa-image text-gray-400 text-2xl mb-2"></i>
                                        <p class="text-sm">Image preview not available</p>
                                        <p class="text-xs text-gray-400">${getImageDisplayName(value)}</p>
                                    </div>
                                `;
                            }
                            
                            imageContainer.appendChild(imagePreview);
                            valueEl.appendChild(imageContainer);
                        } else if (fieldType === 'paragraph') {
                            valueEl.className = 'px-4 py-3 text-gray-800 whitespace-pre-wrap bg-gray-50 rounded border border-gray-200';
                            valueEl.textContent = value || '';
                        } else {
                            valueEl.className = 'px-4 py-3 text-gray-800 bg-gray-50 rounded border border-gray-200';
                            valueEl.textContent = value || '';
                        }
                        
                        item.appendChild(valueEl);
                    }
                    
                    list.appendChild(item);
                }
                
                fileDetails.appendChild(list);
                
                if (mode === 'edit') {
                    editButton.style.display = 'none';
                    saveButton.style.display = userPermissions.canEditFilledFiles ? 'flex' : 'none';
                    cancelButton.style.display = 'flex';
                    exportButtonGroup.style.display = 'none';
                    deleteFilledFileButton.style.display = 'none';
                    deleteTemplateButton.style.display = 'none';
                } else {
                    editButton.style.display = userPermissions.canEditFilledFiles ? 'flex' : 'none';
                    saveButton.style.display = 'none';
                    cancelButton.style.display = 'none';
                    exportButtonGroup.style.display = userPermissions.canExportFilledFiles ? 'flex' : 'none';
                    
                    if (currentSelectedFilledFile) {
                        deleteFilledFileButton.style.display = userPermissions.canDeleteFilledFiles ? 'flex' : 'none';
                        deleteTemplateButton.style.display = 'none';
                    } else if (currentSelectedTemplate) {
                        deleteFilledFileButton.style.display = 'none';
                        deleteTemplateButton.style.display = userPermissions.canDeleteTemplates ? 'flex' : 'none';
                    }
                }
            }

            function renderTemplateOverview(template) {
                currentSelectedTemplate = template;
                fileNameHeading.textContent = `Template: ${template.name}`;
                fileDetails.innerHTML = '';
                fileActionsDiv.style.display = 'flex'; 
                
                // Reset button colors to blue for template view
                updateButtonColorsForFileType(false);
                
                editButton.style.display = 'none';
                saveButton.style.display = 'none';
                cancelButton.style.display = 'none';
                deleteFilledFileButton.style.display = 'none';
                deleteTemplateButton.style.display = userPermissions.canDeleteTemplates ? 'block' : 'none';
                exportButtonGroup.style.display = 'none';

                const container = document.createElement('div');
                container.className = 'space-y-6';

                const detailsSection = document.createElement('div');
                detailsSection.className = 'bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden';
                
                const detailsHeader = document.createElement('div');
                detailsHeader.className = 'bg-blue-50 px-4 py-3 border-b border-gray-200';
                
                const fieldsHeading = document.createElement('h3');
                fieldsHeading.className = 'text-lg font-semibold text-blue-800 flex items-center';
                fieldsHeading.innerHTML = '<i class="fas fa-folder-open mr-2"></i>Template Information';
                detailsHeader.appendChild(fieldsHeading);
                detailsSection.appendChild(detailsHeader);
                
                const detailsContent = document.createElement('div');
                detailsContent.className = 'p-4';
                
                const fieldsHeading2 = document.createElement('h4');
                fieldsHeading2.className = 'text-md font-semibold text-gray-700 mb-2';
                fieldsHeading2.textContent = 'Available Fields:';
                detailsContent.appendChild(fieldsHeading2);
                
                const fieldsList = document.createElement('ul');
                fieldsList.className = 'space-y-2 text-gray-700 mb-4';
                
                if (template.templateFields && Array.isArray(template.templateFields) && template.templateFields.length > 0) {
                    template.templateFields.forEach(field => {
                        const fieldItem = document.createElement('li');
                        fieldItem.className = 'flex items-center justify-between bg-gray-50 p-3 rounded border';
                        
                        // Handle both string and object field formats
                        const fieldName = typeof field === 'string' ? field : (field.name || field.field || field);
                        const fieldType = typeof field === 'object' ? (field.type || 'text') : 'text';
                        
                        const leftContent = document.createElement('div');
                        leftContent.className = 'flex items-center';
                        
                        const iconClass = fieldType === 'image' ? 'fa-image text-purple-500' : 
                                         fieldType === 'paragraph' ? 'fa-paragraph text-green-500' : 
                                         'fa-font text-blue-500';
                        
                        leftContent.innerHTML = `
                            <i class="fas ${iconClass} mr-2"></i>
                            <span class="font-medium">${fieldName}</span>
                        `;
                        
                        const rightContent = document.createElement('div');
                        rightContent.className = 'flex items-center space-x-2';
                        
                        const typeSpan = document.createElement('span');
                                               typeSpan.className = 'text-xs px-2 py-1 rounded-full ' + 
                            (fieldType === 'image' ? 'bg-purple-100 text-purple-700' :
                             fieldType === 'paragraph' ? 'bg-green-100 text-green-700' :
                             'bg-blue-100 text-blue-700');
                        typeSpan.textContent = fieldType.charAt(0).toUpperCase() + fieldType.slice(1);
                        rightContent.appendChild(typeSpan);
                        
                        fieldItem.appendChild(leftContent);
                        fieldItem.appendChild(rightContent);
                        fieldsList.appendChild(fieldItem);
                    });
                } else {
                    const noFieldsItem = document.createElement('li');
                    noFieldsItem.className = 'text-gray-500 flex items-center';
                    noFieldsItem.innerHTML = '<i class="fas fa-info-circle text-gray-400 mr-2"></i>No fields defined for this template.';
                    fieldsList.appendChild(noFieldsItem);
                }
                
                detailsContent.appendChild(fieldsList);
                detailsSection.appendChild(detailsContent);
                container.appendChild(detailsSection);

                const filesSection = document.createElement('div');
                filesSection.className = 'bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden';
                
                const filesHeader = document.createElement('div');
                filesHeader.className = 'bg-green-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center';
                
                const filesHeading = document.createElement('h3');
                filesHeading.className = 'text-lg font-semibold text-green-800 flex items-center';
                filesHeading.innerHTML = '<i class="fas fa-file-alt mr-2"></i>Filled Files';
                filesHeader.appendChild(filesHeading);
                
                if (userPermissions.canCreateFilledFiles) {
                    const createButton = document.createElement('button');
                    createButton.className = 'bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded-lg transition duration-200 text-sm flex items-center';
                    createButton.dataset.templateId = template.id;
                    createButton.id = 'createNewFilledFileButton';
                    
                    const buttonIcon = document.createElement('i');
                    buttonIcon.className = 'fas fa-plus-circle mr-1';
                    createButton.appendChild(buttonIcon);
                    
                    const buttonText = document.createTextNode('New File');
                    createButton.appendChild(buttonText);
                    
                    filesHeader.appendChild(createButton);
                    createButton.addEventListener('click', handleCreateNewFilledFile);
                }
                
                filesSection.appendChild(filesHeader);
                
                const filesContent = document.createElement('div');
                filesContent.className = 'p-4';

                const filesList = document.createElement('div');
                filesList.className = 'space-y-2';

                if (template.filledFiles && template.filledFiles.length > 0) {
                    template.filledFiles.forEach(filledFile => {
                        const fileItem = document.createElement('div');
                        fileItem.className = 'p-3 hover:bg-green-50 rounded-lg border border-gray-200 transition-colors duration-150 flex items-center cursor-pointer hover:border-green-300';
                        fileItem.dataset.id = filledFile.id;
                        fileItem.dataset.templateId = template.id;
                        fileItem.classList.add('template-overview-file-link');
                        
                        const fileIcon = document.createElement('i');
                        fileIcon.className = 'fas fa-edit text-green-500 mr-3 text-lg';
                        fileItem.appendChild(fileIcon);
                        
                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'flex-grow';
                        
                        const fileName = document.createElement('div');
                        fileName.className = 'font-medium text-gray-800';
                        fileName.textContent = filledFile.name;
                        fileInfo.appendChild(fileName);

                        // Show whichever date is more recent
                        let fileDate = null;
                        if (filledFile.updatedAt && filledFile.createdAt) {
                            fileDate = filledFile.updatedAt > filledFile.createdAt ? filledFile.updatedAt : filledFile.createdAt;
                        } else if (filledFile.updatedAt) {
                            fileDate = filledFile.updatedAt;
                        } else if (filledFile.createdAt) {
                            fileDate = filledFile.createdAt;
                        }
                        
                        if (fileDate) {
                            try {
                                fileDate = new Date(fileDate);
                                fileDate = `${fileDate.toLocaleDateString()} ${fileDate.toLocaleTimeString()}`;
                            } catch (e) {
                                fileDate = "Invalid date";
                            }
                        } else {
                            fileDate = "Unknown date";
                        }
                        
                        const fileDateDiv = document.createElement('div');
                        fileDateDiv.className = 'text-xs text-gray-500';
                        fileDateDiv.textContent = fileDate;
                        fileInfo.appendChild(fileDateDiv);
                        
                        fileItem.appendChild(fileInfo);
                        
                        const viewIcon = document.createElement('i');
                        viewIcon.className = 'fas fa-arrow-right text-gray-400 ml-2';
                        fileItem.appendChild(viewIcon);
                        
                        filesList.appendChild(fileItem);
                    });
                } else {
                    const noFilesItem = document.createElement('div');
                    noFilesItem.className = 'p-4 text-center text-gray-500';
                    noFilesItem.innerHTML = '<i class="fas fa-info-circle mb-2 text-gray-400 text-2xl"></i><p>No filled files for this template yet.</p>';
                    filesList.appendChild(noFilesItem);
                }
                
                filesContent.appendChild(filesList);
                filesSection.appendChild(filesContent);
                container.appendChild(filesSection);

                fileDetails.appendChild(container);
            }

            async function handleCreateNewFilledFile(event) {
                const templateId = event.target.dataset.templateId;
                const template = findTemplateById(templateId);
                if (!template) {
                    showErrorModal('Template not found.');
                    return;
                }

                const newFileName = await showInputModal(`Enter name for the new filled file (based on template: ${template.name}):`);
                if (!newFileName || newFileName.trim() === '') {
                    if (newFileName !== null) showErrorModal('File name cannot be empty.');
                    return;
                }

                try {
                    const response = await fetch(`/file-explorer/create-filled-file`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                        },
                        body: JSON.stringify({ template_id: templateId, name: newFileName.trim() })
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ message: 'Failed to create file. Server error.' }));
                        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();

                    if (result.success && result.newFilledFile) {
                        const newFilledFile = result.newFilledFile;
                        if (typeof newFilledFile.filledData === 'string') {
                            try {
                                newFilledFile.filledData = JSON.parse(newFilledFile.filledData || '{}');
                            } catch (e) {
                                console.error("Error parsing new filledData JSON:", e, newFilledFile.filledData);
                                newFilledFile.filledData = {};
                            }
                        } else if (typeof newFilledFile.filledData !== 'object' || newFilledFile.filledData === null) {
                            newFilledFile.filledData = {};
                        }
                        newFilledFile.template_id = templateId;

                        const targetTemplate = findTemplateById(templateId);
                        if (targetTemplate) {
                            if (!targetTemplate.filledFiles || !Array.isArray(targetTemplate.filledFiles)) {
                                targetTemplate.filledFiles = [];
                            }
                            targetTemplate.filledFiles.push(newFilledFile);
                        }

                        refreshSidebar();
                        
                        currentSelectedFilledFile = JSON.parse(JSON.stringify(newFilledFile));
                        currentSelectedTemplate = null;
                        originalFilledData = JSON.parse(JSON.stringify(newFilledFile.filledData));
                        renderFileDetails(currentSelectedFilledFile, 'view');

                        showSuccessModal('New file created successfully: ' + newFilledFile.name);
                                    } else {
                        throw new Error(result.message || 'Unknown error');
                    }
                } catch (error) {
                    console.error('Error creating file:', error);
                    showErrorModal('Error creating file: ' + error.message);
                }
            }

            wizardNameReviewForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const templateName = templateNameWizardInput.value.trim();
                if (!templateName) {
                    showErrorModal('Please enter a template name.');
                    return;
                }

                showWizardStep('stepSavingTemplate');
                finalizingStatusDiv.textContent = 'Saving template...';

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
                            templateFields: wizardState.detectedFields.map(field => 
                                typeof field === 'string' ? field : (field.name || field.field || field)
                            ),
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
                        finalizingStatusDiv.textContent = 'Template saved successfully!';
                        
                        if (result.newTemplate) {
                            templatesData.push(result.newTemplate);
                            refreshSidebar();
                        }
                        
                        setTimeout(() => {
                            showSuccessModal('Template uploaded successfully!');
                            resetWizard();
                        }, 1000);
                    } else {
                        throw new Error(result.message || 'Upload failed');
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    showErrorModal('Failed to save template: ' + error.message);
                    showWizardStep('stepNameReview');
                }
            });
            } // End of wizard initialization (if wizardFileUploadForm exists)

            fileHierarchy.addEventListener('click', function(event) {
                const target = event.target;
                
                let clickTarget = target;
                if (target.tagName === 'I') {
                    if (target.classList.contains('folder-chevron')) {
                        clickTarget = target.parentElement;
                    } else {
                        clickTarget = target.parentElement;
                    }
                }
                const parentElement = clickTarget.parentElement;

                document.querySelectorAll('.file-item, .folder-item > span').forEach(item => {
                    item.classList.remove('bg-blue-100', 'bg-green-100');
                });

                if (clickTarget.tagName === 'SPAN' && parentElement.classList.contains('folder-item')) {
                    const folderList = parentElement.querySelector('ul');
                    const chevronIcon = clickTarget.querySelector('.folder-chevron');
                    
                    if (folderList) {
                        const isOpen = folderList.style.display === 'block';
                        folderList.style.display = isOpen ? 'none' : 'block';
                        
                        if (chevronIcon) {
                            if (isOpen) {
                                chevronIcon.style.transform = 'rotate(0deg)';
                            } else {
                                chevronIcon.style.transform = 'rotate(90deg)';
                            }
                        }
                    }
                    
                    const templateId = parentElement.dataset.templateId;
                    const selectedTemplate = findTemplateById(templateId);

                    if (selectedTemplate) {
                        currentSelectedFilledFile = null;
                        originalFilledData = null;
                        renderTemplateOverview(selectedTemplate);
                        clickTarget.classList.add('bg-blue-100');
                    }
                } else if (clickTarget.classList.contains('file-item')) {
                    const filledFileId = clickTarget.dataset.id;
                    const templateIdForFile = clickTarget.dataset.templateId;
                    const selectedFile = findFilledFileById(filledFileId);

                    if (selectedFile) {
                        currentSelectedFilledFile = JSON.parse(JSON.stringify(selectedFile));
                        currentSelectedFilledFile.template_id = templateIdForFile;
                        currentSelectedTemplate = null;
                        originalFilledData = JSON.parse(JSON.stringify(selectedFile.filledData));
                        renderFileDetails(currentSelectedFilledFile, 'view');
                        clickTarget.classList.add('bg-green-100');
                    } else {
                        fileNameHeading.textContent = 'File not found';
                        fileDetails.innerHTML = '<p class="text-gray-600">Details could not be loaded.</p>';
                        fileActionsDiv.style.display = 'none';
                    }
                }
            });

            fileDetails.addEventListener('click', function(event) {
                const linkElement = event.target.closest('.template-overview-file-link');
                
                if (linkElement) {
                    event.preventDefault();
                    
                    const filledFileId = linkElement.dataset.id;
                    const templateId = linkElement.dataset.templateId;
                    const selectedFile = findFilledFileById(filledFileId);

                    if (selectedFile) {
                        currentSelectedFilledFile = JSON.parse(JSON.stringify(selectedFile));
                        currentSelectedFilledFile.template_id = templateId;
                        currentSelectedTemplate = null;
                        originalFilledData = JSON.parse(JSON.stringify(selectedFile.filledData));
                        renderFileDetails(currentSelectedFilledFile, 'view');

                        document.querySelectorAll('.file-item, .folder-item > span').forEach(item => {
                            item.classList.remove('bg-blue-100', 'bg-green-100');
                        });
                        
                        const sidebarFileItem = fileHierarchy.querySelector(`.file-item[data-id='${filledFileId}']`);
                        if (sidebarFileItem) sidebarFileItem.classList.add('bg-green-100');
                    }
                }
            });

            editButton.addEventListener('click', () => {
                if (currentSelectedFilledFile) {
                    renderFileDetails(currentSelectedFilledFile, 'edit');
                }
            });

            cancelButton.addEventListener('click', () => {
                if (currentSelectedFilledFile && originalFilledData) {
                    currentSelectedFilledFile.filledData = JSON.parse(JSON.stringify(originalFilledData));
                    renderFileDetails(currentSelectedFilledFile, 'view');
                }
            });

            saveButton.addEventListener('click', async () => {
                if (!currentSelectedFilledFile) return;

                const updatedData = {};
                const fieldTypes = {};
                const imageSizes = {};
                const formData = new FormData();
                
                const inputs = fileDetails.querySelectorAll('input[type="text"], textarea, input[type="hidden"]');
                inputs.forEach(input => {
                    // Skip special fields and fields without names
                    if (!input.name || 
                        input.name.startsWith('fieldType_') || 
                        input.name.startsWith('image_') || 
                        input.name.startsWith('imageWidth_') || 
                        input.name.startsWith('imageHeight_') || 
                        input.name.startsWith('imageRatio_')) {
                        return;
                    }
                    updatedData[input.name] = input.value || '';
                });
                
                console.log('Updated data:', updatedData);
                
                const typeSelectors = fileDetails.querySelectorAll('select[name^="fieldType_"]');
                typeSelectors.forEach(select => {
                    const fieldName = select.name.replace('fieldType_', '');
                    fieldTypes[fieldName] = select.value;
                });
                
                const widthInputs = fileDetails.querySelectorAll('input[name^="imageWidth_"]');
                const heightInputs = fileDetails.querySelectorAll('input[name^="imageHeight_"]');
                const ratioInputs = fileDetails.querySelectorAll('input[name^="imageRatio_"]');
                
                widthInputs.forEach(input => {
                    const fieldName = input.name.replace('imageWidth_', '');
                    if (!imageSizes[fieldName]) imageSizes[fieldName] = {};
                    imageSizes[fieldName].width = parseInt(input.value) || 300;
                });
                
                heightInputs.forEach(input => {
                    const fieldName = input.name.replace('imageHeight_', '');
                    if (!imageSizes[fieldName]) imageSizes[fieldName] = {};
                    imageSizes[fieldName].height = parseInt(input.value) || 200;
                });
                
                ratioInputs.forEach(input => {
                    const fieldName = input.name.replace('imageRatio_', '');
                    if (!imageSizes[fieldName]) imageSizes[fieldName] = {};
                    imageSizes[fieldName].ratio = input.checked;
                });
                
                const imageInputs = fileDetails.querySelectorAll('input[type="file"][name^="image_"]');
                const fieldsWithNewImages = new Set();
                
                imageInputs.forEach(fileInput => {
                    if (fileInput.files && fileInput.files.length > 0) {
                        const fieldName = fileInput.name.replace('image_', '');
                        formData.append(`image_${fieldName}`, fileInput.files[0]);
                        fieldsWithNewImages.add(fieldName);
                    }
                });
                
                formData.append('fieldsWithNewImages', JSON.stringify(Array.from(fieldsWithNewImages)));
                
                formData.append('name', currentSelectedFilledFile.name || 'Unnamed File');
                
                const filledDataJSON = JSON.stringify(updatedData);
                console.log('FilledData JSON:', filledDataJSON);
                console.log('FilledData JSON length:', filledDataJSON.length);
                
                formData.append('filledData', filledDataJSON);
                formData.append('fieldTypes', JSON.stringify(fieldTypes));
                formData.append('imageSizes', JSON.stringify(imageSizes));
                formData.append('_token', document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content'));
                
                // Debug: log all FormData entries
                console.log('FormData entries:');
                for (let [key, value] of formData.entries()) {
                    if (typeof value === 'string') {
                        console.log(`${key}: ${value.substring(0, 100)}${value.length > 100 ? '...' : ''}`);
                    } else {
                        console.log(`${key}: [File object]`);
                    }
                }

                try {
                    const response = await fetch(`/file-explorer/update-filled-file/${currentSelectedFilledFile.id}`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({
                            message: 'Failed to save. Server error.'
                        }));
                        console.error('Server error response:', errorData);
                        if (errorData.debug) {
                            console.error('Debug info:', errorData.debug);
                        }
                        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();
                    console.log('Save successful:', result);

                    if (result.success) {
                        // Update currentSelectedFilledFile with the data returned from server
                        if (result.data && result.data.filledData) {
                            currentSelectedFilledFile.filledData = result.data.filledData;
                            originalFilledData = JSON.parse(JSON.stringify(result.data.filledData));
                        }
                        
                        if (result.data && result.data.fieldTypes) {
                            currentSelectedFilledFile.fieldTypes = result.data.fieldTypes;
                        } else if (fieldTypes) {
                            currentSelectedFilledFile.fieldTypes = fieldTypes;
                        }
                        
                        if (result.data && result.data.imageSizes) {
                            currentSelectedFilledFile.imageSizes = result.data.imageSizes;
                        } else if (imageSizes) {
                            currentSelectedFilledFile.imageSizes = imageSizes;
                        }
                        
                        if (result.data && result.data.name) {
                            currentSelectedFilledFile.name = result.data.name;
                        }

                        // Update the template's filledFiles array in templatesData
                        const templateOfSavedFile = findTemplateById(currentSelectedFilledFile.template_id);
                        if (templateOfSavedFile && templateOfSavedFile.filledFiles) {
                            const fileIndex = templateOfSavedFile.filledFiles.findIndex(ff => ff.id.toString() === currentSelectedFilledFile.id.toString());
                            if (fileIndex > -1) {
                                templateOfSavedFile.filledFiles[fileIndex].filledData = currentSelectedFilledFile.filledData;
                                templateOfSavedFile.filledFiles[fileIndex].fieldTypes = currentSelectedFilledFile.fieldTypes;
                                if (currentSelectedFilledFile.imageSizes) {
                                    templateOfSavedFile.filledFiles[fileIndex].imageSizes = currentSelectedFilledFile.imageSizes;
                                }
                                if (currentSelectedFilledFile.name) {
                                    templateOfSavedFile.filledFiles[fileIndex].name = currentSelectedFilledFile.name;
                                }
                            }
                        }
                        
                        // Re-render the file details with updated data
                        renderFileDetails(currentSelectedFilledFile, 'view');
                        showSuccessModal('File updated successfully!');
                    } else {
                        showErrorModal('Failed to update file: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error saving file:', error);
                    showErrorModal('Error saving file: ' + error.message);
                }
            });

            deleteFilledFileButton.addEventListener('click', async () => {
                if (!currentSelectedFilledFile) return;
                
                const confirmed = await showConfirmModal('Are you sure you want to delete this filled file? This action cannot be undone.');
                if (!confirmed) {
                    return;
                }

                deleteFilledFileButton.disabled = true;
                deleteFilledFileButton.textContent = 'Deleting...';

                try {
                    const response = await fetch(`/file-explorer/delete-filled-file/${currentSelectedFilledFile.id}`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                        }
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({
                            message: 'Failed to delete file. Server error.'
                        }));
                        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();

                    if (result.success) {
                        const templateOfDeletedFile = findTemplateById(currentSelectedFilledFile.template_id);
                        if (templateOfDeletedFile && templateOfDeletedFile.filledFiles) {
                            const fileIndex = templateOfDeletedFile.filledFiles.findIndex(ff => ff.id.toString() === currentSelectedFilledFile.id.toString());
                            if (fileIndex > -1) {
                                templateOfDeletedFile.filledFiles.splice(fileIndex, 1);
                            }
                        }

                        refreshSidebar();
                        fileNameHeading.textContent = 'Select a file';
                        fileDetails.innerHTML = '<p>Click on a file to view its details here.</p>';
                        fileActionsDiv.style.display = 'none';
                        currentSelectedFilledFile = null;
                        originalFilledData = null;

                        showSuccessModal('File deleted successfully!');
                    } else {
                        showErrorModal('Failed to delete file: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error deleting file:', error);
                    showErrorModal('Error deleting file: ' + error.message);
                } finally {
                    deleteFilledFileButton.disabled = false;
                    deleteFilledFileButton.textContent = 'Delete File';
                }
            });

            deleteTemplateButton.addEventListener('click', async () => {
                if (!currentSelectedTemplate) return;
                
                const confirmed = await showConfirmModal(`Are you sure you want to delete the template "${currentSelectedTemplate.name}" and all its filled files? This action cannot be undone.`);
                if (!confirmed) {
                    return;
                }

                deleteTemplateButton.disabled = true;
                deleteTemplateButton.textContent = 'Deleting...';

                try {
                    const response = await fetch(`/file-explorer/delete-template/${currentSelectedTemplate.id}`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                        }
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({
                            message: 'Failed to delete template. Server error.'
                        }));
                        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();

                    if (result.success) {
                                                const templateIndex = templatesData.findIndex(t => t.id.toString() === currentSelectedTemplate.id.toString());
                        if (templateIndex > -1) {
                            templatesData.splice(templateIndex, 1);
                        }

                        refreshSidebar();
                        fileNameHeading.textContent = 'Select a file';
                        fileDetails.innerHTML = '<p>Click on a file to view its details here.</p>';
                        fileActionsDiv.style.display = 'none';
                        currentSelectedFilledFile = null;
                        currentSelectedTemplate = null;
                        originalFilledData = null;

                        showSuccessModal('Template deleted successfully!');
                    } else {
                        showErrorModal('Failed to delete template: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error deleting template:', error);
                    showErrorModal('Error deleting template: ' + error.message);
                } finally {
                    deleteTemplateButton.disabled = false;
                    deleteTemplateButton.textContent = 'Delete Template';
                }
            });

            exportButton.addEventListener('click', (e) => {
                e.stopPropagation();
                exportDropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!exportButtonGroup.contains(e.target)) {
                    exportDropdown.classList.add('hidden');
                }
            });

            exportDocxButton.addEventListener('click', async () => {
                if (!currentSelectedFilledFile) return;
                
                exportDropdown.classList.add('hidden');
                showLoadingModal('Exporting to DOCX', 'Generating your Word document...');

                try {
                    const response = await fetch(`/file-explorer/export-docx/${currentSelectedFilledFile.id}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Export failed');
                    }

                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `${currentSelectedFilledFile.name}.docx`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);

                    hideLoadingModal();
                    showSuccessModal('DOCX file downloaded successfully!');

                } catch (error) {
                    hideLoadingModal();
                    console.error('Error exporting DOCX:', error);
                    showErrorModal('Error exporting DOCX: ' + error.message);
                }
            });

            exportPdfButton.addEventListener('click', async () => {
                if (!currentSelectedFilledFile) return;
                
                exportDropdown.classList.add('hidden');
                showLoadingModal('Exporting to PDF', 'Converting your document to PDF...');

                try {
                    const response = await fetch(`/file-explorer/export-pdf/${currentSelectedFilledFile.id}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const errorData = await response.json();
                        throw new Error(errorData.message || 'Export failed');
                    }

                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `${currentSelectedFilledFile.name}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);

                    hideLoadingModal();
                    showSuccessModal('PDF file downloaded successfully!');

                } catch (error) {
                    hideLoadingModal();
                    console.error('Error exporting PDF:', error);
                    showErrorModal('Error exporting PDF: ' + error.message);
                }
            });

            if (templatesData.length < 0) {
                fileNameHeading.textContent = 'No Templates Available';
                fileDetails.innerHTML = '<p>There are no templates to display.</p>';
            }
            
            function initializeChevrons() {
                document.querySelectorAll('.folder-item').forEach(folderItem => {
                    const folderList = folderItem.querySelector('ul');
                    const chevronIcon = folderItem.querySelector('.folder-chevron');
                    
                    if (folderList && chevronIcon) {
                        let isOpen = false;
                        if (folderList.style.display === 'block') {
                            isOpen = true;
                        } else if (folderList.style.display === 'none') {
                            isOpen = false;
                        } else {
                            const computedStyle = window.getComputedStyle(folderList);
                            isOpen = computedStyle.display !== 'none';
                        }
                        
                        chevronIcon.style.transform = isOpen ? 'rotate(90deg)' : 'rotate(0deg)';
                    }
                });
            }
            
            setTimeout(initializeChevrons, 100);
            
            function handleUrlFragment() {
                const hash = window.location.hash;
                if (hash) {
                    if (hash === '#upload-template') {
                        const uploadWizardModal = document.getElementById('uploadWizardModal');
                        if (uploadWizardModal) {
                            uploadWizardModal.classList.remove('hidden');
                            history.replaceState(null, null, window.location.pathname + window.location.search);
                        }
                        return;
                    }
                    
                    const createMatch = hash.match(/^#create-filled-file-(\d+)$/);
                    if (createMatch) {
                        const templateId = createMatch[1];
                        const template = findTemplateById(templateId);
                        if (template) {
                            history.replaceState(null, null, window.location.pathname + window.location.search);
                            
                            currentSelectedTemplate = template;
                            currentSelectedFilledFile = null;
                            renderTemplateOverview(template);
                            
                            document.querySelectorAll('.file-item, .folder-item > span').forEach(item => {
                                item.classList.remove('bg-blue-100', 'bg-green-100');
                            });
                            
                            const templateFolder = fileHierarchy.querySelector(`[data-template-id="${templateId}"]`);
                            if (templateFolder) {
                                const templateSpan = templateFolder.querySelector('span');
                                if (templateSpan) templateSpan.classList.add('bg-blue-100');
                                
                                const folderList = templateFolder.querySelector('ul');
                                const chevronIcon = templateFolder.querySelector('.folder-chevron');
                                if (folderList && chevronIcon) {
                                    folderList.style.display = 'block';
                                    chevronIcon.style.transform = 'rotate(90deg)';
                                }
                            }
                            
                            setTimeout(async () => {
                                const newFileName = await showInputModal(`Enter name for the new filled file (based on template: ${template.name}):`);
                                if (newFileName && newFileName.trim()) {
                                    try {
                                        const response = await fetch(`/file-explorer/create-filled-file`, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                                            },
                                            body: JSON.stringify({ template_id: templateId, name: newFileName.trim() })
                                        });

                                        if (!response.ok) {
                                            throw new Error('Failed to create file');
                                        }

                                        const result = await response.json();
                                        if (result.success && result.newFilledFile) {
                                            const newFilledFile = result.newFilledFile;
                                            if (typeof newFilledFile.filledData === 'string') {
                                                try {
                                                    newFilledFile.filledData = JSON.parse(newFilledFile.filledData || '{}');
                                                } catch (e) {
                                                    newFilledFile.filledData = {};
                                                }
                                            }
                                            newFilledFile.template_id = templateId;

                                            const targetTemplate = findTemplateById(templateId);
                        if (targetTemplate) {
                            if (!targetTemplate.filledFiles || !Array.isArray(targetTemplate.filledFiles)) {
                                targetTemplate.filledFiles = [];
                            }
                            targetTemplate.filledFiles.push(newFilledFile);
                        }

                        refreshSidebar();
                        
                        currentSelectedFilledFile = JSON.parse(JSON.stringify(newFilledFile));
                        currentSelectedTemplate = null;
                        originalFilledData = JSON.parse(JSON.stringify(newFilledFile.filledData));
                        renderFileDetails(currentSelectedFilledFile, 'view');

                        showSuccessModal('New file created successfully: ' + newFilledFile.name);
                                        } else {
                                            throw new Error(result.message || 'Unknown error');
                                        }
                                    } catch (error) {
                                        console.error('Error creating file:', error);
                                        showErrorModal('Error creating file: ' + error.message);
                                    }
                                }
                            }, 500);
                        }
                        return;
                    }
                    
                    const match = hash.match(/^#(template|filled-file)-(\d+)$/);
                    if (match) {
                        const [, type, id] = match;
                        
                        if (type === 'template') {
                            const templateFolder = document.querySelector(`[data-template-id="${id}"]`);
                            if (templateFolder && templateFolder.classList.contains('folder-item')) {
                                highlightAndScrollToElement(templateFolder);
                                setTimeout(() => {
                                    const templateSpan = templateFolder.querySelector('span');
                                    if (templateSpan) templateSpan.click();
                                }, 100);
                            }
                        } else if (type === 'filled-file') {
                            const filledFile = document.querySelector(`[data-id="${id}"]`);
                            if (filledFile && filledFile.classList.contains('file-item')) {
                                const templateId = filledFile.getAttribute('data-template-id');
                                const templateFolder = document.querySelector(`[data-template-id="${templateId}"].folder-item`);
                                if (templateFolder) {
                                    const folderList = templateFolder.querySelector('ul');
                                    const chevronIcon = templateFolder.querySelector('.folder-chevron');
                                    if (folderList && chevronIcon) {
                                        folderList.style.display = 'block';
                                        chevronIcon.style.transform = 'rotate(90deg)';
                                    }
                                }
                                
                                highlightAndScrollToElement(filledFile);
                                setTimeout(() => {
                                    filledFile.click();
                                }, 100);
                            }
                        }
                    }
                }
            }
            
            function highlightAndScrollToElement(element) {
                document.querySelectorAll('.fragment-highlight').forEach(el => {
                    el.classList.remove('fragment-highlight');
                });
                
                element.classList.add('fragment-highlight');
                
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
                
                setTimeout(() => {
                    element.classList.remove('fragment-highlight');
                }, 3000);
            }
            setTimeout(handleUrlFragment, 200);
            
            window.addEventListener('hashchange', handleUrlFragment);

            // Check for action=upload query parameter
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'upload') {
                setTimeout(() => {
                    const openUploadWizardButton = document.getElementById('openUploadWizardButton');
                    if (openUploadWizardButton) {
                        openUploadWizardButton.click();
                    }
                }, 300);
            }

            window.addEventListener('load', () => {
                setTimeout(initializeChevrons, 50);
            });

            document.getElementById('successModalClose').addEventListener('click', () => hideModal('success'));
            document.getElementById('errorModalClose').addEventListener('click', () => hideModal('error'));
            document.getElementById('confirmCancel').addEventListener('click', () => hideModal('confirm'));
            document.getElementById('inputCancel').addEventListener('click', () => hideModal('input'));
            loadingModalClose.addEventListener('click', hideLoadingModal);
            
            function getImageDisplayName(imagePath) {
                if (!imagePath || typeof imagePath !== 'string') {
                    return 'Unknown';
                }
                return imagePath.split('/').pop() || imagePath;
            }
            
            function getImageUrl(imagePath, filledFileId) {
                if (!imagePath || !filledFileId || typeof imagePath !== 'string') {
                    return null;
                }
                
                const imageName = imagePath.split('/').pop();
                if (!imageName) {
                    return null;
                }
                
                if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
                    return imagePath;
                }
                
                return `/file-explorer/serve-image/${filledFileId}/${encodeURIComponent(imageName)}`;
            }
        });
    </script>
<?php echo $this->endSection() ?>