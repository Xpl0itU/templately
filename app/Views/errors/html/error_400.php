<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo lang('Errors.badRequest') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 text-gray-700 flex items-center justify-center py-16">
    <div class="max-w-xl w-full mx-auto bg-white shadow-2xl rounded-2xl p-10 text-center">
        <p class="text-6xl font-extrabold text-amber-500 mb-4">400</p>
        <h1 class="text-2xl font-semibold text-gray-900 mb-3"><?php echo lang('Errors.badRequest') ?></h1>
        <p class="text-gray-600">
            <?php if (ENVIRONMENT !== 'production') : ?>
                <?php echo nl2br(esc($message)) ?>
            <?php else : ?>
                <?php echo lang('Errors.sorryBadRequest') ?>
            <?php endif; ?>
        </p>
        <div class="mt-8 flex items-center justify-center gap-3">
            <a href="javascript:history.back()" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-amber-600 bg-amber-50 rounded-lg hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-400">
                Try Again
            </a>
            <a href="<?php echo base_url('/') ?>" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-amber-500 rounded-lg shadow hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-400">
                Home
            </a>
        </div>
    </div>
</body>
</html>
