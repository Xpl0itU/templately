<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?> - Templately</title>
    
        <?= csrf_meta() ?>
    <meta name="X-CSRF-TOKEN" content="<?= csrf_hash() ?>" />
    
        <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
        <?= $this->renderSection('pageStyles') ?>
</head>
<body class="bg-gray-50 min-h-screen">
        <?= $this->renderSection('navigation') ?>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <?= $this->renderSection('pageHeader') ?>

                <?= $this->renderSection('content') ?>
    </div>

        <?= $this->renderSection('pageScripts') ?>
</body>
</html>
