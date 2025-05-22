<!DOCTYPE html>
<html lang="en">

<head>
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
    </style>
</head>

<body>
    <div class="sidebar">
        <input type="text" id="searchBox" class="search-box" placeholder="Search files...">
        <div class="file-hierarchy" id="fileHierarchy">
            <ul>
                <li class="folder-item">
                    <?php foreach ($templates as $template) { ?>
                <li class="folder-item">
                    <span><?= $template['name'] ?></span>
                    <ul>
                        <?php foreach ($template['filledFiles'] as $filledFile) { ?>
                            <li class="file-item" data-filename="<?= $filledFile['name'] ?>">
                                <?= $filledFile['name'] ?>
                            </li>
                        <?php } ?>
                    </ul>
                </li>
            <?php } ?>
            </ul>
        </div>
    </div>
    <div class="main-content">
        <h1 id="fileNameHeading">Select a file</h1>
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

            fileHierarchy.addEventListener('click', function(event) {
                const target = event.target;

                // Handle folder clicks
                if (target.tagName === 'SPAN' && target.parentElement.classList.contains('folder-item')) {
                    target.parentElement.classList.toggle('open');
                }
                // Handle file clicks
                else if (target.classList.contains('file-item')) {
                    const fileName = target.dataset.filename || target.textContent;
                    fileNameHeading.textContent = fileName;

                    // List fields of selected filled file
                    const filledFile = <?= json_encode($templates) ?>.find(template => {
                        return template.filledFiles.some(file => file.name === fileName);
                    });
                    if (filledFile) {
                        const filledFileData = filledFile.filledFiles.find(file => file.name === fileName);
                        if (filledFileData) {
                            filledFileData.filledData = JSON.parse(filledFileData.filledData);
                            fileNameHeading.textContent = filledFileData.name;
                            fileDetailsHeading = document.createElement('h3');
                            fileDetailsHeading.textContent = 'File Details:';
                            fileDetails.innerHTML = ''; // Clear previous details
                            fileDetails.appendChild(fileDetailsHeading);
                            fileDetailsUL = document.createElement('ul');
                            for (const [key, value] of Object.entries(filledFileData.filledData)) {
                                const li = document.createElement('li');
                                li.textContent = `${key}: ${value}`;
                                fileDetailsUL.appendChild(li);
                            }
                            fileDetails.appendChild(fileDetailsUL);
                        } else {
                            fileDetails.innerHTML = `<p>No details available for ${fileName}.</p>`;
                        }
                    }

                    // Remove active class from other files and add to current
                    document.querySelectorAll('.file-item.active').forEach(item => item.classList.remove('active'));
                    target.classList.add('active');
                }
            });

            searchBox.addEventListener('input', function(event) {
                const searchTerm = event.target.value.toLowerCase();
                const allItems = fileHierarchy.querySelectorAll('li'); // Includes files and folders

                allItems.forEach(item => {
                    const itemNameElement = item.classList.contains('file-item') ? item : item.querySelector('span');
                    if (itemNameElement) {
                        const itemName = itemNameElement.textContent.toLowerCase();
                        const isVisible = itemName.includes(searchTerm);
                        item.style.display = isVisible ? '' : 'none';
                    }
                });
            });
        });
    </script>
</body>

</html>