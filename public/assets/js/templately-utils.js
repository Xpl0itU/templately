/**
 * Shared utility functions for Templately
 */

// Global utility object
window.Templately = window.Templately || {};

const TEMPLATELY_CONFIRM_INTENT_CLASSES = {
    danger: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
    primary: 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500',
    success: 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
};

const TEMPLATELY_CONFIRM_OK_BASE = 'inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-150';
const TEMPLATELY_CONFIRM_CANCEL_BASE = 'inline-flex items-center px-4 py-2 text-sm font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 transition-colors duration-150';

// Modal handling utilities
Templately.Modal = {
    modals: {},
    currentModal: null,
    currentModalCallback: null,
    _keydownHandler: null,

    init: function(modalElements = {}) {
        this.modals = Object.assign({}, this.modals, modalElements);
        this._ensureGlobalHandlers();

        if (this.modals.confirm) {
            this._prepareConfirmModal(this.modals.confirm);
        }
    },

    registerModal: function(type, element) {
        if (!type || !element) {
            return;
        }

        this.modals[type] = element;
        this._ensureGlobalHandlers();

        if (type === 'confirm') {
            this._prepareConfirmModal(element);
        }
    },

    _ensureGlobalHandlers: function() {
        if (!this._keydownHandler) {
            this._keydownHandler = this.handleGlobalKeydown.bind(this);
            document.addEventListener('keydown', this._keydownHandler);
        }
    },

    handleGlobalKeydown: function(e) {
        if (!this.currentModal) return;

        // ESC key - dismiss any modal
        if (e.key === 'Escape') {
            e.preventDefault();
            this.dismissCurrentModal();
            return;
        }

        // Enter key - trigger primary action
        if (e.key === 'Enter') {
            e.preventDefault();
            this.triggerPrimaryAction();
            return;
        }

        // Tab navigation within modal
        if (e.key === 'Tab') {
            this.trapFocus(e);
        }
    },

    dismissCurrentModal: function() {
        if (!this.currentModal) return;

        const modalType = this.currentModal;

        if (modalType === 'confirm' && this.currentModalCallback) {
            this.currentModalCallback(false);
        } else if (modalType === 'input' && this.currentModalCallback) {
            this.currentModalCallback(null);
        }

        this.hideModal(modalType);
    },

    triggerPrimaryAction: function() {
        if (!this.currentModal) return;

        switch (this.currentModal) {
            case 'success':
                document.getElementById('successModalClose')?.click();
                break;
            case 'error':
                document.getElementById('errorModalClose')?.click();
                break;
            case 'confirm':
                document.getElementById('confirmOk')?.click();
                break;
            case 'input':
                document.getElementById('inputOk')?.click();
                break;
        }
    },

    trapFocus: function(e) {
        const modal = this.modals[this.currentModal];
        if (!modal) return;

        const focusableElements = modal.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );

        if (focusableElements.length === 0) return;

        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        if (e.shiftKey) {
            if (document.activeElement === firstFocusable) {
                e.preventDefault();
                lastFocusable.focus();
            }
        } else {
            if (document.activeElement === lastFocusable) {
                e.preventDefault();
                firstFocusable.focus();
            }
        }
    },

    setModalFocus: function(type) {
        const modal = this.modals[type];
        if (!modal) return;

        let focusTarget;

        if (type === 'input') {
            focusTarget = document.getElementById('inputValue');
        } else {
            // Find the first button or focusable element
            focusTarget = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        }

        if (focusTarget) {
            setTimeout(() => focusTarget.focus(), 100);
        }
    },

    showModal: function(type, callback = null) {
        const modal = this.modals[type];
        if (!modal) return;

        this.currentModal = type;
        this.currentModalCallback = callback;
        modal.classList.remove('hidden');
        this.setModalFocus(type);
    },

    hideModal: function(type) {
        const modal = this.modals[type];
        if (!modal) return;

        modal.classList.add('hidden');
        this.currentModal = null;
        this.currentModalCallback = null;
    },

    _prepareConfirmModal: function(modal) {
        if (!modal || modal.dataset.templatelyConfirmBound === 'true') {
            return;
        }

        modal.dataset.templatelyConfirmBound = 'true';

        const okBtn = modal.querySelector('[data-templately-confirm="ok"], #confirmOk');
        const cancelBtn = modal.querySelector('[data-templately-confirm="cancel"], #confirmCancel');

        const handleResult = (result) => {
            if (typeof this.currentModalCallback === 'function') {
                this.currentModalCallback(result);
            }
            this.hideModal('confirm');
        };

        if (okBtn) {
            okBtn.addEventListener('click', () => handleResult(true));
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => handleResult(false));
        }

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                handleResult(false);
            }
        });
    },

    _createConfirmModal: function() {
        const overlay = document.createElement('div');
        overlay.id = 'confirmModal';
        overlay.className = 'modal-overlay hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';

        overlay.innerHTML = `
            <div class="modal-content bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-start space-x-3 mb-4">
                    <div class="flex-shrink-0">
                        <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
                            <i class="fas fa-question text-yellow-500 text-2xl"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900" data-templately-confirm-title>Confirm Action</h3>
                        <p class="mt-2 text-sm text-gray-600" data-templately-confirm-message>Are you sure you want to continue?</p>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" id="confirmCancel" data-templately-confirm="cancel" class="${TEMPLATELY_CONFIRM_CANCEL_BASE}">Cancel</button>
                    <button type="button" id="confirmOk" data-templately-confirm="ok" class="${TEMPLATELY_CONFIRM_OK_BASE} ${TEMPLATELY_CONFIRM_INTENT_CLASSES.danger}">Confirm</button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        this.registerModal('confirm', overlay);

        return overlay;
    },

    ensureConfirmModal: function() {
        let modal = this.modals.confirm;

        if (!modal) {
            modal = this._createConfirmModal();
        } else {
            this._prepareConfirmModal(modal);
        }

        return modal;
    },

    _setConfirmContent: function(modal, message, options) {
        const {
            title = 'Confirm Action',
            confirmText = 'Confirm',
            cancelText = 'Cancel',
            intent = 'danger',
        } = options || {};

        const titleEl = modal.querySelector('[data-templately-confirm-title]');
        if (titleEl) {
            titleEl.textContent = title;
        }

        const messageEl = modal.querySelector('[data-templately-confirm-message]');
        if (messageEl) {
            messageEl.textContent = message;
        }

        const okBtn = modal.querySelector('[data-templately-confirm="ok"], #confirmOk');
        if (okBtn) {
            const intentClass = TEMPLATELY_CONFIRM_INTENT_CLASSES[intent] || TEMPLATELY_CONFIRM_INTENT_CLASSES.danger;
            okBtn.textContent = confirmText;
            okBtn.className = `${TEMPLATELY_CONFIRM_OK_BASE} ${intentClass}`;
        }

        const cancelBtn = modal.querySelector('[data-templately-confirm="cancel"], #confirmCancel');
        if (cancelBtn) {
            cancelBtn.textContent = cancelText;
            cancelBtn.className = TEMPLATELY_CONFIRM_CANCEL_BASE;
        }
    },

    confirm: function(message, options = {}) {
        const modal = this.ensureConfirmModal();
        this._setConfirmContent(modal, message, options);

        return new Promise((resolve) => {
            this.showModal('confirm', (result) => {
                resolve(Boolean(result));
            });
        });
    }
};

// Alert/Notification utilities
Templately.Alert = {
    showAlert: function(message, type = 'info', duration = 5000) {
        // Create or reuse alert container
        let alertContainer = document.getElementById('templately-alert-container');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'templately-alert-container';
            alertContainer.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(alertContainer);
        }
        
        // Create alert element
        const alertId = 'alert-' + Date.now();
        const alertElement = document.createElement('div');
        alertElement.id = alertId;
        alertElement.className = `px-4 py-3 rounded-lg shadow-lg transform transition-all duration-300 ${
            type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
            type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
            type === 'warning' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' :
            'bg-blue-100 text-blue-800 border border-blue-200'
        }`;
        alertElement.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${
                    type === 'success' ? 'check-circle' :
                    type === 'error' ? 'exclamation-circle' :
                    type === 'warning' ? 'exclamation-triangle' :
                    'info-circle'
                } mr-2"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Add to container
        alertContainer.appendChild(alertElement);
        
        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                alertElement.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => {
                    if (alertElement.parentNode) {
                        alertElement.parentNode.removeChild(alertElement);
                    }
                }, 300);
            }, duration);
        }
    }
};

// HTTP utilities
Templately.Http = {
    getCsrfToken: function() {
        const meta = document.querySelector('meta[name="X-CSRF-TOKEN"]');
        return meta ? meta.getAttribute('content') : null;
    },
    
    fetch: function(url, options = {}) {
        // Add default headers
        options.headers = options.headers || {};
        options.headers['X-Requested-With'] = 'XMLHttpRequest';
        
        const csrfToken = this.getCsrfToken();
        if (csrfToken) {
            options.headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        return window.fetch(url, options);
    }
};

// DOM utilities
Templately.DOM = {
    // Debounce function to limit how often a function can be called
    debounce: function(func, wait, immediate) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    },
    
    // Throttle function to limit how often a function can be called
    throttle: function(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },
    
    // Helper to create DOM elements more easily
    createElement: function(tag, attributes = {}, content = '') {
        const element = document.createElement(tag);
        Object.keys(attributes).forEach(key => {
            if (key === 'className') {
                element.className = attributes[key];
            } else if (key === 'textContent') {
                element.textContent = attributes[key];
            } else if (key === 'innerHTML') {
                element.innerHTML = attributes[key];
            } else {
                element.setAttribute(key, attributes[key]);
            }
        });
        if (content) {
            element.textContent = content;
        }
        return element;
    }
};

// File Explorer specific utilities
Templately.FileExplorer = {
    // Wizard state management
    wizardState: {
        currentStep: 1,
        templateFile: null,
        templateFields: [],
        templateName: ''
    },
    
    // Reset wizard to initial state
    resetWizard: function() {
        this.wizardState = {
            currentStep: 1,
            templateFile: null,
            templateFields: [],
            templateName: ''
        };
        
        // Reset UI elements
        const uploadModal = document.getElementById('uploadWizardModal');
        if (uploadModal) {
            uploadModal.classList.add('hidden');
        }
        
        // Reset form elements
        const fileInput = document.getElementById('templateFileWizard');
        if (fileInput) fileInput.value = '';
        
        const nameInput = document.getElementById('templateNameWizard');
        if (nameInput) nameInput.value = '';
        
        // Reset file preview
        const filePreview = document.getElementById('filePreview');
        if (filePreview) filePreview.classList.add('hidden');
        
        // Reset wizard steps
        this.updateWizardSteps(1);
    },
    
    // Update wizard step indicators
    updateWizardSteps: function(currentStep) {
        const steps = [
            document.getElementById('step1Circle'),
            document.getElementById('step2Circle'),
            document.getElementById('step3Circle'),
            document.getElementById('step4Circle')
        ];
        
        if (!steps[0]) return; // Elements not available yet
        
        steps.forEach((step, index) => {
            if (!step) return;
            
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
    },
    
    // File handling utilities
    updateFileLabel: function() {
        const input = document.getElementById('templateFileWizard');
        const fileLabel = document.getElementById('fileLabel');
        const filePreview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        
        if (input && input.files && input.files[0]) {
            const file = input.files[0];
            if (fileLabel) {
                fileLabel.parentElement.parentElement.classList.add('border-blue-500', 'bg-blue-50');
            }
            if (filePreview) {
                filePreview.classList.remove('hidden');
            }
            if (fileName) {
                fileName.textContent = file.name;
            }
            if (fileSize) {
                fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
            }
        } else {
            this.clearFileSelection();
        }
    },
    
    clearFileSelection: function() {
        const input = document.getElementById('templateFileWizard');
        const fileLabel = document.getElementById('fileLabel');
        const filePreview = document.getElementById('filePreview');
        
        if (input) input.value = '';
        if (fileLabel) {
            fileLabel.textContent = 'Click to browse or drop files here';
            fileLabel.parentElement.parentElement.classList.remove('border-blue-500', 'bg-blue-50');
        }
        if (filePreview) {
            filePreview.classList.add('hidden');
        }
    },
    
    // Loading modal utilities
    showLoadingModal: function(title = 'Processing...', message = 'Please wait while we process your request.') {
        const loadingModal = document.getElementById('loadingModal');
        const loadingTitle = document.getElementById('loadingTitle');
        const loadingMessage = document.getElementById('loadingMessage');
        const loadingStatus = document.getElementById('loadingStatus');
        
        if (loadingTitle) loadingTitle.textContent = title;
        if (loadingMessage) loadingMessage.textContent = message;
        if (loadingStatus) loadingStatus.textContent = 'Processing...';
        
        if (loadingModal) {
            loadingModal.classList.remove('hidden');
            Templately.Modal.currentModal = 'loading';
            setTimeout(() => {
                const modalContent = loadingModal.querySelector('.modal-content');
                if (modalContent) {
                    modalContent.classList.remove('scale-95');
                    modalContent.classList.add('scale-100');
                }
                
                // Focus the close button
                const closeButton = document.getElementById('loadingModalClose');
                if (closeButton) {
                    setTimeout(() => closeButton.focus(), 100);
                }
            }, 10);
        }
    },
    
    hideLoadingModal: function() {
        const loadingModal = document.getElementById('loadingModal');
        
        if (Templately.Modal.currentModal === 'loading') {
            Templately.Modal.currentModal = null;
        }
        
        if (loadingModal) {
            const modalContent = loadingModal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.classList.remove('scale-100');
                modalContent.classList.add('scale-95');
            }
            setTimeout(() => {
                loadingModal.classList.add('hidden');
            }, 150);
        }
    },
    
    updateLoadingStatus: function(status) {
        const loadingStatus = document.getElementById('loadingStatus');
        if (loadingStatus) {
            loadingStatus.textContent = status;
        }
    },
    
    // Image handling utilities
    clearImageSelection: function(fieldName) {
        const container = document.querySelector(`#input_container_${fieldName}`);
        if (container) {
            const fileInput = container.querySelector('input[type="file"]');
            const previewArea = container.querySelector('.mt-2');
            const hiddenInput = container.querySelector('input[type="hidden"]');
            const uploadArea = container.querySelector('.image-upload-area');
            
            if (fileInput) fileInput.value = '';
            if (previewArea) {
                previewArea.classList.add('hidden');
                previewArea.innerHTML = '';
            }
            if (hiddenInput) hiddenInput.removeAttribute('data-has-new-file');
            if (uploadArea) {
                uploadArea.innerHTML = `
                    <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2"></i>
                    <p class="text-gray-600">Click to upload or drag image here</p>
                `;
            }
        }
    }
};