<?php echo $this->extend('auth/layout') ?>

<?php echo $this->section('title') ?>Register<?php echo $this->endSection() ?>

<?php
// If user is already logged in, redirect to dashboard
if (auth()->loggedIn()) {
    return redirect()->to(config(\Config\Auth::class)->loginRedirect());
}
?>

<?php echo $this->section('main') ?>

<div class="w-full max-w-md mx-auto">
    <div class="rounded-2xl shadow-2xl p-8 bg-white/20 backdrop-blur-lg border border-white/20">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-plus text-white text-2xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-800">Create Account</h2>
            <p class="text-gray-600 mt-2">Join Templately today</p>
        </div>

    
    <?php if (session('error') !== null) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle mt-0.5"></i>
                </div>
                <div class="ml-3"><?php echo session('error') ?></div>
            </div>
        </div>
    <?php elseif (session('errors') !== null) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle mt-0.5"></i>
                </div>
                <div class="ml-3">
                    <?php if (is_array(session('errors'))) : ?>
                        <?php foreach (session('errors') as $error) : ?>
                            <?php echo $error ?><br>
                        <?php endforeach ?>
                    <?php else : ?>
                        <?php echo session('errors') ?>
                    <?php endif ?>
                </div>
            </div>
        </div>
    <?php endif ?>

    <form action="<?php echo url_to('register') ?>" method="post" class="space-y-5">
        <?php echo csrf_field() ?>

        
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-user mr-2"></i>Username
            </label>
            <input type="text" 
                   id="username" 
                   name="username" 
                   inputmode="text" 
                   autocomplete="username" 
                   value="<?php echo old('username') ?>" 
                   required
                   class="appearance-none rounded-lg relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-200"
                   placeholder="Choose a username">
        </div>

        
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-lock mr-2"></i>Password
            </label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   inputmode="text" 
                   autocomplete="new-password" 
                   required
                   class="appearance-none rounded-lg relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-200"
                   placeholder="Create a strong password">
        </div>

        
        <div>
            <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-lock mr-2"></i>Confirm Password
            </label>
            <input type="password" 
                   id="password_confirm" 
                   name="password_confirm" 
                   inputmode="text" 
                   autocomplete="new-password" 
                   required
                   class="appearance-none rounded-lg relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition duration-200"
                   placeholder="Confirm your password">
        </div>

        
        <div class="pt-2">
            <button type="submit" 
                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-200 transform hover:scale-[1.02] shadow-md">
                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                    <i class="fas fa-user-plus h-5 w-5 text-green-300 group-hover:text-green-200"></i>
                </span>
                Create Account
            </button>
        </div>

        
        <div class="text-center pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-600">
                Already have an account? 
                <a href="<?php echo url_to('login') ?>" class="text-indigo-600 hover:text-indigo-500 font-medium transition duration-200">
                    Sign in here
                </a>
            </p>
        </div>
    </form>
</div>
</div>

<?php echo $this->endSection() ?>
