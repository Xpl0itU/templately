<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->renderSection('title') ?> - Templately</title>
    
        <?php echo csrf_meta() ?>
    <meta name="X-CSRF-TOKEN" content="<?php echo csrf_hash() ?>" />
    
        <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
        <?php echo $this->renderSection('pageStyles') ?>
</head>
<body class="bg-gray-50 min-h-screen">
        <?php echo $this->renderSection('navigation') ?>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <?php echo $this->renderSection('pageHeader') ?>

                <?php echo $this->renderSection('content') ?>
    </div>

        <?php echo $this->renderSection('pageScripts') ?>
</body>
</html>
