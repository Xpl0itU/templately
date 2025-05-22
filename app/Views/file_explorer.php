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
                                    foundFile.filledData = JSON.parse(foundFile.filledData);
                                } catch (e) {
                                    console.error("Error parsing filledData JSON:", e, foundFile.filledData);
                                    return { ...foundFile, filledData: {} };
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
                for (const [key, value] of Object.entries(fileData.filledData)) {
                    const li = document.createElement('li');
                    if (mode === 'edit') {
                        const label = document.createElement('label');
                        label.textContent = `${key}: `;
                        label.style.marginRight = '5px';
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.name = key;
                        input.value = value;
                        input.dataset.originalValue = value; // Store original for cancel
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

            fileHierarchy.addEventListener('click', function(event) {
                const target = event.target;

                if (target.tagName === 'SPAN' && target.parentElement.classList.contains('folder-item')) {
                    target.parentElement.classList.toggle('open');
                } else if (target.classList.contains('file-item')) {
                    const filledFileId = target.dataset.id;
                    const selectedFile = findFilledFileById(filledFileId);

                    if (selectedFile) {
                        currentSelectedFilledFile = JSON.parse(JSON.stringify(selectedFile));
                        originalFilledData = JSON.parse(JSON.stringify(selectedFile.filledData)); // Store for cancel
                        renderFileDetails(currentSelectedFilledFile, 'view');

                        document.querySelectorAll('.file-item.active').forEach(item => item.classList.remove('active'));
                        target.classList.add('active');
                    } else {
                        fileNameHeading.textContent = 'File not found';
                        fileDetails.innerHTML = '<p>Details could not be loaded.</p>';
                        fileActionsDiv.style.display = 'none';
                    }
                }
            });

            editButton.addEventListener('click', () => {
                if (currentSelectedFilledFile) {
                    renderFileDetails(currentSelectedFilledFile, 'edit');
                }
            });

            cancelButton.addEventListener('click', () => {
                if (currentSelectedFilledFile) {
                    currentSelectedFilledFile.filledData = JSON.parse(JSON.stringify(originalFilledData)); // Restore original
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
                        originalFilledData = JSON.parse(JSON.stringify(updatedData)); // Update original data too

                        const templateIndex = templatesData.findIndex(t => t.id.toString() === currentSelectedFilledFile.templateFileId.toString());
                        if (templateIndex > -1) {
                            const fileIndex = templatesData[templateIndex].filledFiles.findIndex(ff => ff.id.toString() === currentSelectedFilledFile.id.toString());
                            if (fileIndex > -1) {
                                templatesData[templateIndex].filledFiles[fileIndex].filledData = updatedData;
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
        });
    </script>
</body>

</html>