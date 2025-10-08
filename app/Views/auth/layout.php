<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title><?php echo $this->renderSection('title') ?> | Templately</title>

    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php echo $this->renderSection('pageStyles') ?>
</head>

<body class="min-h-screen bg-gradient-to-br from-indigo-500 via-indigo-600 to-purple-700">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            
            <div class="text-center">
                <h1 class="text-4xl font-bold text-white mb-2">
                    <i class="fas fa-file-alt mr-2"></i>Templately
                </h1>
                <p class="text-white/80 text-sm">Professional Template Management System</p>
            </div>

            
            <?php echo $this->renderSection('main') ?>
        </div>
    </div>

    <?php echo $this->renderSection('pageScripts') ?>
</body>
</html>
