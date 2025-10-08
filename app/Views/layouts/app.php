<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $this->renderSection('title') ?> - Templately</title>
    
        <?php echo csrf_meta() ?>
    <meta name="X-CSRF-TOKEN" content="<?php echo csrf_hash() ?>" />
    
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: '#4f46e5',
                            primaryDark: '#4338ca',
                            secondary: '#1f2937',
                            danger: '#dc2626',
                            success: '#16a34a'
                        }
                    }
                }
            };
        </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Alpine.js for dropdown functionality -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
        <?php echo $this->renderSection('pageStyles') ?>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php echo $this->renderSection('navigation') ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php echo $this->renderSection('pageHeader') ?>

        <?php echo $this->renderSection('content') ?>
    </main>

    <?php echo $this->renderSection('pageScripts') ?>
</body>
</html>
