<?php echo $this->extend('layouts/app') ?>

<?php echo $this->section('title') ?>File Explorer<?php echo $this->endSection() ?>

<?php echo $this->section('navigation') ?>
    <?php echo $this->include(
        'components/navigation', [
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
    </style>
<?php echo $this->endSection() ?>

<?php echo $this->section('content') ?>
    <div class="flex flex-col md:flex-row gap-6">
                    <div class="w-full md:w-1/3 bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="glass-header p-4 border-b border-gray-200">
                    <input type="text" id="searchBox" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Search files...">
                </div>
                
                <div id="templateUploadSection" class="p-4 border-b border-gray-200 bg-gray-50">
                    <h4 class="text-lg font-semibold text-gray-700 mb-3">Upload New Template</h4>
                    <button id="openUploadWizardButton" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center">
                        <i class="fas fa-upload mr-2"></i>Upload Template Wizard
                    </button>
                    <div id="uploadStatus" class="mt-2 text-sm text-gray-600"></div>
                </div>
                
                <div class="p-4">
                    <h4 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-folder text-yellow-500 mr-2"></i>Template Files
                    </h4>
                    <div class="file-hierarchy overflow-y-auto max-h-96" id="fileHierarchy">
                        <ul class="list-none p-0">
                            <?php if (!empty($templates)) : ?>
                                <?php foreach ($templates as $template) : ?>
                                    <li class="folder-item mb-2" data-template-id="<?php echo esc($template['id']) ?>">
                                        <span class="flex items-center p-2 rounded-md hover:bg-blue-50 cursor-pointer">
                                            <i class="fas fa-chevron-right text-gray-400 mr-2 transition-transform duration-200 folder-chevron"></i>
                                            <i class="fas fa-folder-open text-yellow-500 mr-2"></i>
                                            <?php echo esc($template['name']) ?>
                                        </span>
                                        <ul class="pl-6" style="display: none;">
                                            <?php if (!empty($template['filledFiles'])) : ?>
                                                <?php foreach ($template['filledFiles'] as $filledFile) : ?>
                                                    <li class="file-item p-2 rounded-md hover:bg-blue-50 cursor-pointer mb-1 flex items-center" 
                                                        data-id="<?php echo esc($filledFile['id']) ?>"
                                                        data-name="<?php echo esc($filledFile['name']) ?>"
                                                        data-template-id="<?php echo esc($template['id']) ?>">
                                                        <i class="fas fa-file-alt text-blue-500 mr-2"></i>
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
                        <button id="exportButton" class="bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg transition duration-200 flex items-center">
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
                    <p class="mb-4 text-gray-600">Select a template file (.docx, .pdf, .txt) containing placeholders that will be filled by users.</p>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors">
                        <input type="file" name="templateFileWizard" id="templateFileWizard" accept=".docx,.pdf,.txt"
                            required class="hidden" onchange="updateFileLabel()">
                        <label for="templateFileWizard" class="cursor-pointer">
                            <i class="fas fa-cloud-upload-alt text-gray-400 text-4xl mb-3 block"></i>
                            <span id="fileLabel" class="text-gray-500">Click to browse or drop files here</span>
                        </label>
                    </div>
                    <div id="filePreview" class="mt-3 hidden">
                        <div class="bg-blue-50 p-3 rounded-lg flex items-center">
                            <i class="fas fa-file-alt text-blue-500 mr-3"></i>
                            <div class="flex-grow">
                                <div id="fileName" class="font-medium text-blue-700"></div>
                                <div id="fileSize" class="text-sm text-gray-500"></div>
                            </div>
                            <button type="button" onclick="clearFileSelection()" class="text-gray-500 hover:text-red-500">
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
                        <ul id="detectedFieldsList" class="max-h-36 overflow-y-auto text-gray-600 text-sm">
                                                    </ul>
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
<?php echo $this->endSection() ?>

<?php echo $this->section('pageScripts') ?>
    <script>
        const modals = {
            success: document.getElementById('successModal'),
            error: document.getElementById('errorModal'),
            confirm: document.getElementById('confirmModal'),
            input: document.getElementById('inputModal')
        };

        function showModal(type, message, callback = null) {
            const modal = modals[type];
            if (!modal) return;

            if (type === 'success') {
                document.getElementById('successMessage').textContent = message;
            } else if (type === 'error') {
                document.getElementById('errorMessage').textContent = message;
            } else if (type === 'confirm') {
                document.getElementById('confirmMessage').textContent = message;
            } else if (type === 'input') {
                document.getElementById('inputMessage').textContent = message;
                document.getElementById('inputValue').value = '';
            }

            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('.modal-content').classList.remove('scale-95');
                modal.querySelector('.modal-content').classList.add('scale-100');
            }, 10);

            if (type === 'confirm' && callback) {
                const confirmOk = document.getElementById('confirmOk');
                const confirmCancel = document.getElementById('confirmCancel');
                
                const handleConfirm = () => {
                    hideModal('confirm');
                    callback(true);
                    confirmOk.removeEventListener('click', handleConfirm);
                    confirmCancel.removeEventListener('click', handleCancel);
                };
                
                const handleCancel = () => {
                    hideModal('confirm');
                    callback(false);
                    confirmOk.removeEventListener('click', handleConfirm);
                    confirmCancel.removeEventListener('click', handleCancel);
                };
                
                confirmOk.addEventListener('click', handleConfirm);
                confirmCancel.addEventListener('click', handleCancel);
            }

            if (type === 'input' && callback) {
                const inputOk = document.getElementById('inputOk');
                const inputCancel = document.getElementById('inputCancel');
                const inputValue = document.getElementById('inputValue');
                
                const handleInput = () => {
                    const value = inputValue.value.trim();
                    if (value) {
                        hideModal('input');
                        callback(value);
                        inputOk.removeEventListener('click', handleInput);
                        inputCancel.removeEventListener('click', handleInputCancel);
                        inputValue.removeEventListener('keypress', handleKeyPress);
                    }
                };
                
                const handleInputCancel = () => {
                    hideModal('input');
                    callback(null);
                    inputOk.removeEventListener('click', handleInput);
                    inputCancel.removeEventListener('click', handleInputCancel);
                    inputValue.removeEventListener('keypress', handleKeyPress);
                };

                const handleKeyPress = (e) => {
                    if (e.key === 'Enter') {
                        handleInput();
                    } else if (e.key === 'Escape') {
                        handleInputCancel();
                    }
                };
                
                inputOk.addEventListener('click', handleInput);
                inputCancel.addEventListener('click', handleInputCancel);
                inputValue.addEventListener('keypress', handleKeyPress);
                
                setTimeout(() => inputValue.focus(), 100);
            }
        }

        function hideModal(type) {
            const modal = modals[type];
            if (!modal) return;

            modal.querySelector('.modal-content').classList.remove('scale-100');
            modal.querySelector('.modal-content').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 150);
        }

        function showInputModal(message) {
            return new Promise((resolve) => {
                showModal('input', message, resolve);
            });
        }

        function showConfirmModal(message) {
            return new Promise((resolve) => {
                showModal('confirm', message, resolve);
            });
        }

        document.getElementById('successModalClose').addEventListener('click', () => hideModal('success'));
        document.getElementById('errorModalClose').addEventListener('click', () => hideModal('error'));

        Object.values(modals).forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    const modalType = Object.keys(modals).find(key => modals[key] === modal);
                    hideModal(modalType);
                }
            });
        });

        function updateFileLabel() {
            const input = document.getElementById('templateFileWizard');
            const fileLabel = document.getElementById('fileLabel');
            const filePreview = document.getElementById('filePreview');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                fileLabel.parentElement.parentElement.classList.add('border-blue-500', 'bg-blue-50');
                filePreview.classList.remove('hidden');
                fileName.textContent = file.name;
                fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
            } else {
                clearFileSelection();
            }
        }
        
        function clearFileSelection() {
            const input = document.getElementById('templateFileWizard');
            const fileLabel = document.getElementById('fileLabel');
            const filePreview = document.getElementById('filePreview');
            
            input.value = '';
            fileLabel.textContent = 'Click to browse or drop files here';
            filePreview.classList.add('hidden');
            fileLabel.parentElement.parentElement.classList.remove('border-blue-500', 'bg-blue-50');
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

            const openUploadWizardButton = document.getElementById('openUploadWizardButton');
            const uploadWizardModal = document.getElementById('uploadWizardModal');
            const closeWizardButton = uploadWizardModal.querySelector('.close-wizard');

            const wizardSteps = {
                stepUploadFile: document.getElementById('wizardStepUploadFile'),
                stepAnalyzingFile: document.getElementById('wizardStepAnalyzingFile'),
                stepNameReview: document.getElementById('wizardStepNameReview'),
                stepSavingTemplate: document.getElementById('wizardStepSavingTemplate')
            };
            
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

            let currentSelectedFilledFile = null;
            let currentSelectedTemplate = null;
            let originalFilledData = null;

            const templatesData = <?php echo json_encode($templates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || [];
            const userPermissions = <?php echo json_encode($userPermissions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;

            function findFilledFileById(filledFileId) {
                for (const template of templatesData) {
                    if (template.filledFiles && Array.isArray(template.filledFiles)) {
                        const foundFile = template.filledFiles.find(ff => ff.id.toString() === filledFileId.toString());
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
                return templatesData.find(t => t.id.toString() === templateId.toString());
            }

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
                clearFileSelection();
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

            const dropZone = document.querySelector('.border-dashed');
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                dropZone.classList.add('border-blue-500', 'bg-blue-50');
            }
            
            function unhighlight() {
                dropZone.classList.remove('border-blue-500', 'bg-blue-50');
            }
            
            dropZone.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                if (files.length) {
                    document.getElementById('templateFileWizard').files = files;
                    updateFileLabel();
                }
            }

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
                        wizardState.detectedFields = result.templateFields || [];

                        originalFileNameReviewSpan.textContent = wizardState.originalFileName;
                        fileSizeReviewSpan.textContent = wizardState.fileSizeKB + ' KB';
                        templateNameWizardInput.value = wizardState.originalFileName.split('.').slice(0, -1).join('.') || wizardState.originalFileName;

                        detectedFieldsListUl.innerHTML = '';
                        if (wizardState.detectedFields.length > 0) {
                            wizardState.detectedFields.forEach(field => {
                                const li = document.createElement('li');
                                li.className = 'py-1 px-2 flex items-center';
                                li.innerHTML = `<i class="fas fa-tag text-blue-500 mr-2"></i> ${field}`;
                                detectedFieldsListUl.appendChild(li);
                            });
                        } else {
                            const li = document.createElement('li');
                            li.className = 'py-1 px-2 flex items-center text-gray-500 italic';
                            li.innerHTML = '<i class="fas fa-info-circle mr-2"></i> No fields detected (or using mock data).';
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
                            fileLi.className = 'file-item p-2 rounded-md hover:bg-blue-50 cursor-pointer mb-1 flex items-center';
                            fileLi.dataset.id = filledFile.id;
                            fileLi.dataset.name = filledFile.name;
                            fileLi.dataset.templateId = template.id;
                            
                            const fileIcon = document.createElement('i');
                            fileIcon.className = 'fas fa-file-alt text-blue-500 mr-2';
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

                document.querySelectorAll('.file-item.bg-blue-100').forEach(item => item.classList.remove('bg-blue-100'));
                document.querySelectorAll('.folder-item > span.bg-blue-100').forEach(span => span.classList.remove('bg-blue-100'));

                if (activeFile) {
                    activeFile.classList.add('bg-blue-100');
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

            function renderFileDetails(fileData, mode = 'view') {
                fileDetails.innerHTML = '';
                fileNameHeading.textContent = fileData.name || 'File Details';
                fileActionsDiv.style.display = 'flex';

                if (!fileData.filledData || typeof fileData.filledData !== 'object') {
                    fileDetails.innerHTML = '<div class="p-4 text-center"><i class="fas fa-exclamation-circle text-yellow-500 text-3xl mb-2"></i><p class="text-gray-600">No structured data available for this file.</p></div>';
                    editButton.style.display = 'none';
                    saveButton.style.display = 'none';
                    cancelButton.style.display = 'none';
                    return;
                }

                const list = document.createElement('div');
                list.className = 'space-y-4';
                
                for (const [key, value] of Object.entries(fileData.filledData)) {
                    const item = document.createElement('div');
                    
                    if (mode === 'edit') {
                        item.className = 'flex flex-col';
                        
                        const label = document.createElement('label');
                        label.className = 'block text-sm font-medium text-gray-700 mb-1';
                        label.textContent = key;
                        
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.name = key;
                        input.value = value;
                        input.dataset.originalValue = value;
                        input.className = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500';
                        
                        item.appendChild(label);
                        item.appendChild(input);
                    } else {
                        item.className = 'bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden';
                        
                        const label = document.createElement('div');
                        label.className = 'bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 border-b border-gray-200';
                        label.textContent = key;
                        
                        const valueEl = document.createElement('div');
                        valueEl.className = 'px-4 py-3 text-gray-800';
                        valueEl.textContent = value;
                        
                        item.appendChild(label);
                        item.appendChild(valueEl);
                    }
                    list.appendChild(item);
                }
                fileDetails.appendChild(list);

                if (mode === 'edit') {
                    editButton.style.display = 'none';
                    saveButton.style.display = 'block';
                    cancelButton.style.display = 'block';
                    deleteFilledFileButton.style.display = 'none';
                    deleteTemplateButton.style.display = 'none';
                    exportButtonGroup.style.display = 'none';
                } else {
                    editButton.style.display = Object.keys(fileData.filledData).length > 0 && userPermissions.canEditFilledFiles ? 'block' : 'none';
                    saveButton.style.display = 'none';
                    cancelButton.style.display = 'none';
                    deleteFilledFileButton.style.display = userPermissions.canDeleteFilledFiles ? 'block' : 'none';
                    deleteTemplateButton.style.display = 'none';
                    exportButtonGroup.style.display = Object.keys(fileData.filledData).length > 0 && userPermissions.canExportFilledFiles ? 'block' : 'none';
                }
            }

            function renderTemplateOverview(template) {
                currentSelectedTemplate = template;
                fileNameHeading.textContent = `Template: ${template.name}`;
                fileDetails.innerHTML = '';
                fileActionsDiv.style.display = 'flex'; 
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
                fieldsHeading.innerHTML = '<i class="fas fa-info-circle mr-2"></i>Template Information';
                detailsHeader.appendChild(fieldsHeading);
                detailsSection.appendChild(detailsHeader);
                
                const detailsContent = document.createElement('div');
                detailsContent.className = 'p-4';
                
                const fieldsHeading2 = document.createElement('h4');
                fieldsHeading2.className = 'text-md font-semibold text-gray-700 mb-2';
                fieldsHeading2.textContent = 'Available Fields:';
                detailsContent.appendChild(fieldsHeading2);
                
                const fieldsList = document.createElement('ul');
                fieldsList.className = 'space-y-1 text-gray-700 mb-4';
                
                if (template.templateFields && Array.isArray(template.templateFields) && template.templateFields.length > 0) {
                    template.templateFields.forEach(field => {
                        const fieldItem = document.createElement('li');
                        fieldItem.className = 'flex items-center';
                        fieldItem.innerHTML = `<i class="fas fa-tag text-blue-500 mr-2"></i> ${field}`;
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
                        fileItem.className = 'p-3 hover:bg-gray-50 rounded-lg border border-gray-200 transition-colors duration-150 flex items-center';
                        
                        const fileIcon = document.createElement('i');
                        fileIcon.className = 'fas fa-file-alt text-blue-500 mr-3 text-lg';
                        fileItem.appendChild(fileIcon);
                        
                        const fileInfo = document.createElement('div');
                        fileInfo.className = 'flex-grow';
                        
                        const fileName = document.createElement('div');
                        fileName.className = 'font-medium text-gray-800';
                        fileName.textContent = filledFile.name;
                        fileInfo.appendChild(fileName);
                        
                        const fileDate = document.createElement('div');
                        fileDate.className = 'text-xs text-gray-500';
                        fileDate.textContent = filledFile.created_at ? new Date(filledFile.created_at).toLocaleDateString() : 'Unknown date';
                        fileInfo.appendChild(fileDate);
                        
                        fileItem.appendChild(fileInfo);
                        
                        const viewButton = document.createElement('button');
                        viewButton.className = 'ml-2 text-blue-600 hover:text-blue-800 flex items-center text-sm';
                        viewButton.innerHTML = '<i class="fas fa-eye mr-1"></i> View';
                        viewButton.dataset.id = filledFile.id;
                        viewButton.dataset.templateId = template.id;
                        viewButton.classList.add('template-overview-file-link');
                        fileItem.appendChild(viewButton);
                        
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
                    showModal('error', 'Template not found.');
                    return;
                }

                const newFileName = await showInputModal(`Enter name for the new filled file (based on template: ${template.name}):`);
                if (!newFileName || newFileName.trim() === '') {
                    if (newFileName !== null) showModal('error', 'File name cannot be empty.');
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

                            const templateLi = fileHierarchy.querySelector(`.folder-item[data-template-id='${templateId}']`);
                            if (templateLi) {
                                let filesUl = templateLi.querySelector('ul');
                                if (!filesUl) {
                                    filesUl = document.createElement('ul');
                                    filesUl.className = 'pl-6';
                                    templateLi.appendChild(filesUl);
                                }
                                const noFilesLi = Array.from(filesUl.children).find(child => child.textContent.includes('No filled files'));
                                if (noFilesLi) noFilesLi.remove();

                                const newFileLiElement = document.createElement('li');
                                newFileLiElement.className = 'file-item p-2 rounded-md hover:bg-blue-50 cursor-pointer mb-1 flex items-center';
                                newFileLiElement.dataset.id = newFilledFile.id;
                                newFileLiElement.dataset.name = newFilledFile.name;
                                newFileLiElement.dataset.templateId = templateId;
                                
                                const fileIcon = document.createElement('i');
                                fileIcon.className = 'fas fa-file-alt text-blue-500 mr-2';
                                newFileLiElement.appendChild(fileIcon);
                                
                                const fileText = document.createTextNode(newFilledFile.name);
                                newFileLiElement.appendChild(fileText);
                                filesUl.appendChild(newFileLiElement);
                                
                                filesUl.style.display = 'block';
                                
                                const chevronIcon = templateLi.querySelector('.folder-chevron');
                                if (chevronIcon) {
                                    chevronIcon.style.transform = 'rotate(90deg)';
                                }
                            }
                        }

                        currentSelectedFilledFile = JSON.parse(JSON.stringify(newFilledFile));
                        originalFilledData = JSON.parse(JSON.stringify(newFilledFile.filledData));
                        renderFileDetails(currentSelectedFilledFile, 'view');

                        document.querySelectorAll('.file-item.bg-blue-100').forEach(item => item.classList.remove('bg-blue-100'));
                        document.querySelectorAll('.folder-item > span.bg-blue-100').forEach(span => span.classList.remove('bg-blue-100'));
                            
                        const newSidebarFileItem = fileHierarchy.querySelector(`.file-item[data-id='${newFilledFile.id}']`);
                        if (newSidebarFileItem) newSidebarFileItem.classList.add('bg-blue-100');
                        
                        showModal('success', 'New file created successfully: ' + newFilledFile.name);
                        renderTemplateOverview(targetTemplate);
                    } else {
                        showModal('error', 'Failed to create file: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error creating file:', error);
                    showModal('error', 'Error creating file: ' + error.message);
                }
            }

            wizardNameReviewForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const templateName = templateNameWizardInput.value.trim();
                if (!templateName) {
                    showModal('error', 'Please enter a template name.');
                    return;
                }

                showWizardStep('stepSavingTemplate');
                finalizingStatusDiv.textContent = 'Saving...';

                const payload = {
                    tempFilePath: wizardState.tempFilePath,
                    templateName: templateName,
                    templateFields: wizardState.detectedFields,
                    originalFileName: wizardState.originalFileName,
                    fileMimeType: wizardState.fileMimeType,
                    fileSizeKB: wizardState.fileSizeKB,
                    '<?php echo csrf_token() ?>': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                };

                try {
                    const response = await fetch('/file-explorer/finalize-template-upload', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();

                    if (response.ok && result.success && result.newTemplate) {
                        finalizingStatusDiv.textContent = 'Template saved successfully!';
                        finalizingStatusDiv.style.color = 'green';

                        if (typeof result.newTemplate.templateFields === 'string') {
                            result.newTemplate.templateFields = JSON.parse(result.newTemplate.templateFields || '[]');
                        }
                        result.newTemplate.filledFiles = result.newTemplate.filledFiles || [];
                        templatesData.push(result.newTemplate);
                        refreshSidebar();

                        setTimeout(() => {
                            resetWizard();
                            const newTemplateLi = fileHierarchy.querySelector(`.folder-item[data-template-id='${result.newTemplate.id}'] > span`);
                            if (newTemplateLi) newTemplateLi.click();
                        }, 1500);

                    } else {
                        finalizingStatusDiv.textContent = `Save failed: ${result.message || 'Unknown error'}`;
                        finalizingStatusDiv.style.color = 'red';
                        cancelStepSavingTemplateButton.textContent = 'Close';
                    }
                } catch (error) {
                    console.error('Error finalizing template:', error);
                    finalizingStatusDiv.textContent = `Save error: ${error.message}`;
                    finalizingStatusDiv.style.color = 'red';
                    cancelStepSavingTemplateButton.textContent = 'Close';
                }
            });

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
                    item.classList.remove('bg-blue-100');
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
                        currentSelectedTemplate = null;                         originalFilledData = JSON.parse(JSON.stringify(selectedFile.filledData));
                        renderFileDetails(currentSelectedFilledFile, 'view');
                        clickTarget.classList.add('bg-blue-100');
                    } else {
                        fileNameHeading.textContent = 'File not found';
                        fileDetails.innerHTML = '<p class="text-gray-600">Details could not be loaded.</p>';
                        fileActionsDiv.style.display = 'none';
                    }
                }
            });

            fileDetails.addEventListener('click', function(event) {
                if (event.target.classList.contains('template-overview-file-link') || 
                    (event.target.parentElement && event.target.parentElement.classList.contains('template-overview-file-link'))) {
                    event.preventDefault();
                    
                    const linkElement = event.target.classList.contains('template-overview-file-link') ? 
                    event.target : event.target.parentElement;
                    
                    const filledFileId = linkElement.dataset.id;
                    const templateId = linkElement.dataset.templateId;
                    const selectedFile = findFilledFileById(filledFileId);

                    if (selectedFile) {
                        currentSelectedFilledFile = JSON.parse(JSON.stringify(selectedFile));
                        currentSelectedFilledFile.template_id = templateId;
                        currentSelectedTemplate = null;                         originalFilledData = JSON.parse(JSON.stringify(selectedFile.filledData));
                        renderFileDetails(currentSelectedFilledFile, 'view');

                                                document.querySelectorAll('.file-item, .folder-item > span').forEach(item => {
                            item.classList.remove('bg-blue-100');
                        });
                        
                        const sidebarFileItem = fileHierarchy.querySelector(`.file-item[data-id='${filledFileId}']`);
                        if (sidebarFileItem) {
                            sidebarFileItem.classList.add('bg-blue-100');
                                                        const parentFolder = sidebarFileItem.closest('.folder-item');
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
                        }
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
                const inputs = fileDetails.querySelectorAll('input[type="text"]');
                inputs.forEach(input => {
                    updatedData[input.name] = input.value;
                });

                try {
                    const response = await fetch(`/file-explorer/update-filled-file/${currentSelectedFilledFile.id}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            filledData: updatedData
                        })
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({
                            message: 'Failed to save. Server error.'
                        }));
                        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();

                    if (result.success) {
                        currentSelectedFilledFile.filledData = updatedData;
                        originalFilledData = JSON.parse(JSON.stringify(updatedData));

                        const templateOfSavedFile = findTemplateById(currentSelectedFilledFile.template_id);
                        if (templateOfSavedFile && templateOfSavedFile.filledFiles) {
                            const fileIndex = templateOfSavedFile.filledFiles.findIndex(ff => ff.id.toString() === currentSelectedFilledFile.id.toString());
                            if (fileIndex > -1) {
                                templateOfSavedFile.filledFiles[fileIndex].filledData = updatedData;
                            }
                        }
                        renderFileDetails(currentSelectedFilledFile, 'view');
                        showModal('success', 'File updated successfully!');
                    } else {
                        showModal('error', 'Failed to update file: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error saving file:', error);
                    showModal('error', 'Error saving file: ' + error.message);
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

                        showModal('success', 'File deleted successfully!');
                    } else {
                        showModal('error', 'Failed to delete file: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error deleting file:', error);
                    showModal('error', 'Error deleting file: ' + error.message);
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

                        showModal('success', 'Template deleted successfully!');
                    } else {
                        showModal('error', 'Failed to delete template: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error deleting template:', error);
                    showModal('error', 'Error deleting template: ' + error.message);
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
                
                try {
                    const response = await fetch(`/file-explorer/export-docx/${currentSelectedFilledFile.id}`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
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

                    showModal('success', 'DOCX file exported successfully!');
                } catch (error) {
                    console.error('Error exporting DOCX:', error);
                    showModal('error', 'Error exporting DOCX: ' + error.message);
                }
            });

            exportPdfButton.addEventListener('click', async () => {
                if (!currentSelectedFilledFile) return;
                
                exportDropdown.classList.add('hidden');
                
                try {
                    const response = await fetch(`/file-explorer/export-pdf/${currentSelectedFilledFile.id}`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="X-CSRF-TOKEN"]').getAttribute('content')
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
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

                    showModal('success', 'PDF file exported successfully!');
                } catch (error) {
                    console.error('Error exporting PDF:', error);
                    showModal('error', 'Error exporting PDF: ' + error.message);
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

            window.addEventListener('load', () => {
                setTimeout(initializeChevrons, 50);
            });
        });
    </script>
<?php echo $this->endSection() ?>