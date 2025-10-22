Templately.FileExplorerMain = (function() {
    'use strict';
    
    let fileHierarchy = null;
    let fileNameHeading = null;
    let fileDetails = null;
    let searchBox = null;
    let fileActionsDiv = null;
    let editButton = null;
    let saveButton = null;
    let cancelButton = null;
    let deleteFilledFileButton = null;
    
    function initializeElements() {
        fileHierarchy = document.getElementById('fileHierarchy');
        fileNameHeading = document.getElementById('fileNameHeading');
        fileDetails = document.getElementById('fileDetails');
        searchBox = document.getElementById('searchBox');
        fileActionsDiv = document.getElementById('fileActions');
        editButton = document.getElementById('editButton');
        saveButton = document.getElementById('saveButton');
        cancelButton = document.getElementById('cancelButton');
        deleteFilledFileButton = document.getElementById('deleteFilledFileButton');
    }
    
    function attachEventListeners() {
        if (searchBox) {
            searchBox.addEventListener('input', Templately.DOM.debounce(handleSearch, 300));
        }
        
        if (editButton) {
            editButton.addEventListener('click', handleEditClick);
        }
        
        if (saveButton) {
            saveButton.addEventListener('click', handleSaveClick);
        }
        
        if (cancelButton) {
            cancelButton.addEventListener('click', handleCancelClick);
        }
        
        if (deleteFilledFileButton) {
            deleteFilledFileButton.addEventListener('click', handleDeleteClick);
        }
        
        attachModalEventListeners();
    }
    
    function attachModalEventListeners() {
        const successModalClose = document.getElementById('successModalClose');
        const errorModalClose = document.getElementById('errorModalClose');
        const confirmOk = document.getElementById('confirmOk');
        const confirmCancel = document.getElementById('confirmCancel');
        const inputOk = document.getElementById('inputOk');
        const inputCancel = document.getElementById('inputCancel');
        const loadingModalClose = document.getElementById('loadingModalClose');
        
        if (successModalClose) {
            successModalClose.addEventListener('click', () => Templately.Modal.hideModal('success'));
        }
        
        if (errorModalClose) {
            errorModalClose.addEventListener('click', () => Templately.Modal.hideModal('error'));
        }
        
        if (confirmOk) {
            confirmOk.addEventListener('click', () => {
                if (Templately.Modal.currentModalCallback) {
                    Templately.Modal.currentModalCallback(true);
                }
                Templately.Modal.hideModal('confirm');
            });
        }
        
        if (confirmCancel) {
            confirmCancel.addEventListener('click', () => {
                if (Templately.Modal.currentModalCallback) {
                    Templately.Modal.currentModalCallback(false);
                }
                Templately.Modal.hideModal('confirm');
            });
        }
        
        if (inputOk) {
            inputOk.addEventListener('click', () => {
                const inputValue = document.getElementById('inputValue');
                if (Templately.Modal.currentModalCallback) {
                    Templately.Modal.currentModalCallback(inputValue ? inputValue.value : null);
                }
                Templately.Modal.hideModal('input');
            });
        }
        
        if (inputCancel) {
            inputCancel.addEventListener('click', () => {
                if (Templately.Modal.currentModalCallback) {
                    Templately.Modal.currentModalCallback(null);
                }
                Templately.Modal.hideModal('input');
            });
        }
        
        if (loadingModalClose) {
            loadingModalClose.addEventListener('click', Templately.FileExplorer.hideLoadingModal);
        }
        
        Object.values(Templately.Modal.modals).forEach(modal => {
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        const modalType = Object.keys(Templately.Modal.modals).find(key => Templately.Modal.modals[key] === modal);
                        if (modalType) {
                            Templately.Modal.hideModal(modalType);
                        }
                    }
                });
            }
        });
    }
    
    function handleSearch(e) {
        const searchTerm = e.target.value.toLowerCase();
        filterFileHierarchy(searchTerm);
    }
    
    function handleEditClick() {
        console.log('Edit button clicked');
    }
    
    function handleSaveClick() {
        console.log('Save button clicked');
    }
    
    function handleCancelClick() {
        console.log('Cancel button clicked');
    }
    
    function handleDeleteClick() {
        console.log('Delete button clicked');
    }
    
    function filterFileHierarchy(searchTerm) {
        if (!fileHierarchy) return;
        
        const fileItems = fileHierarchy.querySelectorAll('.file-item');
        fileItems.forEach(item => {
            const fileName = item.querySelector('.file-name');
            if (fileName) {
                const text = fileName.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            }
        });
    }
    
    return {
        init: function() {
            initializeElements();
            attachEventListeners();
        },
        
        filterFiles: filterFileHierarchy
    };
})();