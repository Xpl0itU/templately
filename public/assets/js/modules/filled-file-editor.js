/**
 * Filled File Editor Module
 */
Templately.FilledFileEditor = (function() {
    'use strict';
    
    // Private variables
    let currentFilledFile = null;
    let isEditing = false;
    
    // Private functions
    function attachEventListeners() {
        // Image field event listeners
        document.addEventListener('change', handleImageFieldChange);
        document.addEventListener('click', handleImageFieldClick);
    }
    
    function handleImageFieldChange(e) {
        if (e.target.matches('input[type="file"][data-field-type="image"]')) {
            handleImageUpload(e.target);
        }
    }
    
    function handleImageFieldClick(e) {
        // Handle remove image button clicks
        if (e.target.matches('[data-action="remove-image"]')) {
            e.preventDefault();
            const fieldName = e.target.dataset.fieldName;
            if (fieldName) {
                removeImage(fieldName);
            }
        }
    }
    
    function handleImageUpload(fileInput) {
        if (!fileInput.files || !fileInput.files[0]) return;
        
        const file = fileInput.files[0];
        const fieldName = fileInput.dataset.fieldName;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!allowedTypes.includes(file.type)) {
            Templately.Alert.showAlert('Please upload a valid image file (JPEG, PNG, GIF, or WebP).', 'error');
            fileInput.value = '';
            return;
        }
        
        // Preview the image
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewArea = document.querySelector(`#input_container_${fieldName} .mt-2`);
            if (previewArea) {
                previewArea.classList.remove('hidden');
                previewArea.innerHTML = `
                    <div class="relative">
                        <img src="${e.target.result}" alt="Preview" class="max-w-full h-auto rounded">
                        <button type="button" 
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                                data-action="remove-image"
                                data-field-name="${fieldName}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            }
            
            // Mark that this field has a new file
            const hiddenInput = document.querySelector(`input[name="filledData[${fieldName}]"]`);
            if (hiddenInput) {
                hiddenInput.setAttribute('data-has-new-file', 'true');
            }
        };
        reader.readAsDataURL(file);
    }
    
    function removeImage(fieldName) {
        Templately.FileExplorer.clearImageSelection(fieldName);
    }
    
    // Public API
    return {
        init: function() {
            attachEventListeners();
        },
        
        // Expose some functions for external use
        setCurrentFile: function(file) {
            currentFilledFile = file;
        },
        
        getCurrentFile: function() {
            return currentFilledFile;
        },
        
        setEditing: function(editing) {
            isEditing = editing;
        },
        
        isEditing: function() {
            return isEditing;
        }
    };
})();