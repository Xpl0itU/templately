<!DOCTYPE html>
<html lang="en">

<head>
    <?= csrf_meta() ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Explorer</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: #f0f0f0;
            padding: 15px;
            border-right: 1px solid #ccc;
            overflow-y: auto;
        }

        .search-box {
            width: calc(100% - 10px);
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .file-hierarchy ul {
            list-style-type: none;
            padding-left: 15px;
            margin: 0;
        }

        .file-hierarchy li {
            margin-bottom: 5px;
        }

        .file-hierarchy .file-item,
        .file-hierarchy .folder-item>span {
            cursor: pointer;
            display: block;
            padding: 5px;
            border-radius: 3px;
        }

        .file-hierarchy .file-item:hover,
        .file-hierarchy .folder-item>span:hover {
            background-color: #e0e0e0;
        }

        .file-hierarchy .folder-item>ul {
            display: none;
            /* Hidden by default */
            padding-left: 20px;
        }

        .file-hierarchy .folder-item.open>ul {
            display: block;
        }

        .file-hierarchy .folder-item>span::before {
            content: '\25B6';
            /* Right-pointing triangle */
            display: inline-block;
            margin-right: 8px;
            transition: transform 0.2s ease-in-out;
        }

        .file-hierarchy .folder-item.open>span::before {
            transform: rotate(90deg);
        }

        .file-hierarchy .file-item.active,
        .file-hierarchy .folder-item.active > span {
            background-color: #cce5ff;
            /* A light blue for active items */
            font-weight: bold;
        }

        .file-hierarchy .file-item.active:hover,
        .file-hierarchy .folder-item.active > span:hover {
            background-color: #b8daff;
        }

        .main-content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .main-content h1 {
            margin-top: 0;
        }

        .action-button {
            padding: 10px 15px;
            margin-right: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        #editButton {
            background-color: #007bff;
            color: white;
        }

        #saveButton {
            background-color: #28a745;
            color: white;
        }

        #cancelButton {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <input type="text" id="searchBox" class="search-box" placeholder="Search files...">
        <div class="file-hierarchy" id="fileHierarchy">
            <ul>
                <?php if (!empty($templates)) : ?>
                    <?php foreach ($templates as $template) : ?>
                        <li class="folder-item" data-template-id="<?= esc($template['id']) ?>">
                            <span><?= esc($template['name']) ?></span>
                            <ul>
                                <?php if (!empty($template['filledFiles'])) : ?>
                                    <?php foreach ($template['filledFiles'] as $filledFile) : ?>
                                        <li class="file-item"
                                            data-id="<?= esc($filledFile['id']) ?>"
                                            data-name="<?= esc($filledFile['name']) ?>"
                                            data-template-id="<?= esc($template['id']) ?>">
                                            <?= esc($filledFile['name']) ?>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <li>No filled files for this template.</li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                <?php else : ?>
                    <li>No templates found.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="main-content">
        <h1 id="fileNameHeading">Select a file</h1>
        <div id="fileActions" style="margin-bottom: 15px; display: none;">
            <button id="editButton" class="action-button">Edit</button>
            <button id="saveButton" class="action-button" style="display: none;">Save</button>
            <button id="cancelButton" class="action-button" style="display: none;">Cancel</button>
        </div>
        <div id="fileDetails">
            <p>Click on a file to view its details here.</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fileHierarchy = document.getElementById('fileHierarchy');
            const fileNameHeading = document.getElementById('fileNameHeading');
            const fileDetails = document.getElementById('fileDetails');
            const searchBox = document.getElementById('searchBox');
            const fileActionsDiv = document.getElementById('fileActions');
            const editButton = document.getElementById('editButton');
            const saveButton = document.getElementById('saveButton');
            const cancelButton = document.getElementById('cancelButton');

            let currentSelectedFilledFile = null;
            let originalFilledData = null;

            const templatesData = <?= json_encode($templates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?> || [];

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

            function renderFileDetails(fileData, mode = 'view') {
                fileDetails.innerHTML = ''; // Clear previous details
                fileNameHeading.textContent = fileData.name || 'File Details';
                fileActionsDiv.style.display = 'block';

                if (!fileData.filledData || typeof fileData.filledData !== 'object') {
                    fileDetails.innerHTML = '<p>No structured data available for this file.</p>';
                    editButton.style.display = 'none';
                    saveButton.style.display = 'none';
                    cancelButton.style.display = 'none';
                    return;
                }

                const ul = document.createElement('ul');
                ul.style.listStyleType = 'none';
                ul.style.paddingLeft = '0';
                for (const [key, value] of Object.entries(fileData.filledData)) {
                    const li = document.createElement('li');
                    li.style.marginBottom = '5px';
                    if (mode === 'edit') {
                        const label = document.createElement('label');
                        label.textContent = `${key}: `;
                        label.style.marginRight = '5px';
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.name = key;
                        input.value = value;
                        input.dataset.originalValue = value; // Store original for cancel
                        input.style.width = 'calc(100% - 100px)';
                        input.style.padding = '5px';
                        input.style.border = '1px solid #ccc';
                        input.style.borderRadius = '3px';
                        li.appendChild(label);
                        li.appendChild(input);
                    } else {
                        li.textContent = `${key}: ${value}`;
                    }
                    ul.appendChild(li);
                }
                fileDetails.appendChild(ul);

                if (mode === 'edit') {
                    editButton.style.display = 'none';
                    saveButton.style.display = 'inline-block';
                    cancelButton.style.display = 'inline-block';
                } else {
                    editButton.style.display = Object.keys(fileData.filledData).length > 0 ? 'inline-block' : 'none';
                    saveButton.style.display = 'none';
                    cancelButton.style.display = 'none';
                }
            }

            function renderTemplateOverview(template) {
                fileNameHeading.textContent = `Template: ${template.name}`;
                fileDetails.innerHTML = ''; // Clear previous details
                fileActionsDiv.style.display = 'none'; // Hide file actions

                const heading = document.createElement('h3');
                heading.textContent = 'Filled Files:';
                fileDetails.appendChild(heading);

                const ul = document.createElement('ul');
                ul.style.listStyleType = 'disc';
                ul.style.paddingLeft = '20px';

                if (template.filledFiles && template.filledFiles.length > 0) {
                    template.filledFiles.forEach(filledFile => {
                        const li = document.createElement('li');
                        const a = document.createElement('a');
                        a.href = '#';
                        a.textContent = filledFile.name;
                        a.dataset.id = filledFile.id;
                        a.dataset.templateId = template.id;
                        a.classList.add('template-overview-file-link');
                        a.style.textDecoration = 'underline';
                        a.style.cursor = 'pointer';
                        li.appendChild(a);
                        ul.appendChild(li);
                    });
                } else {
                    const li = document.createElement('li');
                    li.textContent = 'No filled files for this template yet.';
                    ul.appendChild(li);
                }
                fileDetails.appendChild(ul);

                const createButton = document.createElement('button');
                createButton.textContent = 'Create New Filled File from this Template';
                createButton.classList.add('action-button');
                createButton.style.backgroundColor = '#17a2b8';
                createButton.style.color = 'white';
                createButton.style.marginTop = '20px';
                createButton.dataset.templateId = template.id;
                createButton.id = 'createNewFilledFileButton';
                fileDetails.appendChild(createButton);

                createButton.addEventListener('click', handleCreateNewFilledFile);
            }

            async function handleCreateNewFilledFile(event) {
                const templateId = event.target.dataset.templateId;
                const template = findTemplateById(templateId);
                if (!template) {
                    alert('Template not found.');
                    return;
                }

                const newFileName = prompt(`Enter name for the new filled file (based on template: ${template.name}):`);
                if (!newFileName || newFileName.trim() === '') {
                    if (newFileName !== null) alert('File name cannot be empty.');
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
                                    templateLi.appendChild(filesUl);
                                }
                                const noFilesLi = Array.from(filesUl.children).find(child => child.textContent.includes('No filled files'));
                                if (noFilesLi) noFilesLi.remove();

                                const newFileLiElement = document.createElement('li');
                                newFileLiElement.classList.add('file-item');
                                newFileLiElement.dataset.id = newFilledFile.id;
                                newFileLiElement.dataset.name = newFilledFile.name;
                                newFileLiElement.dataset.templateId = templateId;
                                newFileLiElement.textContent = newFilledFile.name;
                                filesUl.appendChild(newFileLiElement);
                            }
                        }

                        currentSelectedFilledFile = JSON.parse(JSON.stringify(newFilledFile));
                        originalFilledData = JSON.parse(JSON.stringify(newFilledFile.filledData));
                        renderFileDetails(currentSelectedFilledFile, 'view');

                        document.querySelectorAll('.file-item.active, .folder-item.active').forEach(item => item.classList.remove('active'));
                        const newSidebarFileItem = fileHierarchy.querySelector(`.file-item[data-id='${newFilledFile.id}']`);
                        if (newSidebarFileItem) newSidebarFileItem.classList.add('active');
                        
                        alert('New file created successfully: ' + newFilledFile.name);
                        renderTemplateOverview(targetTemplate); // Re-render template overview to show the new file
                    } else {
                        alert('Failed to create file: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error creating file:', error);
                    alert('Error creating file: ' + error.message);
                }
            }

            fileHierarchy.addEventListener('click', function(event) {
                const target = event.target;
                const parentElement = target.parentElement;

                document.querySelectorAll('.file-item.active, .folder-item.active').forEach(item => {
                    item.classList.remove('active');
                });
                document.querySelectorAll('.folder-item > span.active').forEach(span => {
                    span.classList.remove('active');
                    if(span.parentElement) span.parentElement.classList.remove('active');
                });


                if (target.tagName === 'SPAN' && parentElement.classList.contains('folder-item')) {
                    parentElement.classList.toggle('open');
                    const templateId = parentElement.dataset.templateId;
                    const selectedTemplate = findTemplateById(templateId);

                    if (selectedTemplate) {
                        currentSelectedFilledFile = null;
                        originalFilledData = null;
                        renderTemplateOverview(selectedTemplate);
                        parentElement.classList.add('active');
                    }
                } else if (target.classList.contains('file-item')) {
                    const filledFileId = target.dataset.id;
                    const templateIdForFile = target.dataset.templateId;
                    const selectedFile = findFilledFileById(filledFileId);

                    if (selectedFile) {
                        currentSelectedFilledFile = JSON.parse(JSON.stringify(selectedFile));
                        currentSelectedFilledFile.template_id = templateIdForFile;
                        originalFilledData = JSON.parse(JSON.stringify(selectedFile.filledData));
                        renderFileDetails(currentSelectedFilledFile, 'view');
                        target.classList.add('active');
                    } else {
                        fileNameHeading.textContent = 'File not found';
                        fileDetails.innerHTML = '<p>Details could not be loaded.</p>';
                        fileActionsDiv.style.display = 'none';
                    }
                }
            });

            fileDetails.addEventListener('click', function(event) {
                if (event.target.classList.contains('template-overview-file-link')) {
                    event.preventDefault();
                    const filledFileId = event.target.dataset.id;
                    const templateId = event.target.dataset.templateId;
                    const selectedFile = findFilledFileById(filledFileId);

                    if (selectedFile) {
                        currentSelectedFilledFile = JSON.parse(JSON.stringify(selectedFile));
                        currentSelectedFilledFile.template_id = templateId;
                        originalFilledData = JSON.parse(JSON.stringify(selectedFile.filledData));
                        renderFileDetails(currentSelectedFilledFile, 'view');

                        document.querySelectorAll('.file-item.active, .folder-item.active').forEach(item => item.classList.remove('active'));
                        const sidebarFileItem = fileHierarchy.querySelector(`.file-item[data-id='${filledFileId}']`);
                        if (sidebarFileItem) {
                            sidebarFileItem.classList.add('active');
                            const parentFolder = sidebarFileItem.closest('.folder-item');
                            if (parentFolder && !parentFolder.classList.contains('open')) {
                                parentFolder.classList.add('open');
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
                        body: JSON.stringify({ filledData: updatedData })
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ message: 'Failed to save. Server error.' }));
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
                        alert('File updated successfully!');
                    } else {
                        alert('Failed to update file: ' + (result.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error saving file:', error);
                    alert('Error saving file: ' + error.message);
                }
            });

            if (templatesData.length < 0) {
                fileNameHeading.textContent = 'No Templates Available';
                fileDetails.innerHTML = '<p>There are no templates to display.</p>';
            }

        });
    </script>
</body>

</html>