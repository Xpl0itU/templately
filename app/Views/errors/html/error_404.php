<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo lang('Errors.pageNotFound') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-100 text-gray-700 flex items-center justify-center py-16">
    <div class="max-w-xl w-full mx-auto bg-white shadow-2xl rounded-2xl p-10 text-center">
        <p class="text-6xl font-extrabold text-indigo-600 mb-4">404</p>
        <h1 class="text-2xl font-semibold text-gray-900 mb-3"><?php echo lang('Errors.pageNotFound') ?></h1>
        <p class="text-gray-600 mb-8">
            <?php if (ENVIRONMENT !== 'production') : ?>
                <?php echo nl2br(esc($message)) ?>
            <?php else : ?>
                <?php echo lang('Errors.sorryCannotFind') ?>
            <?php endif; ?>
        </p>
        <div class="flex items-center justify-center gap-3">
            <a href="<?php echo base_url('/') ?>" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <span class="mr-2">&#8592;</span>
                Go Back Home
            </a>
            <a href="javascript:history.back()" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Go Back
            </a>
        </div>
    </div>
</body>
</html>
