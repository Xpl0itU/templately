<?= $this->extend('auth/layout') ?>

<?= $this->section('title') ?>Register<?= $this->endSection() ?>

<?= $this->section('main') ?>

<div class="glass-effect rounded-xl shadow-2xl p-8">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Create Account</h2>
        <p class="text-gray-600 mt-2">Join Templately today</p>
    </div>

    
    <?php if (session('error') !== null) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
            <div class="flex">
                <div class="py-1"><i class="fas fa-exclamation-circle mr-2"></i></div>
                <div><?= session('error') ?></div>
            </div>
        </div>
    <?php elseif (session('errors') !== null) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
            <div class="flex">
                <div class="py-1"><i class="fas fa-exclamation-circle mr-2"></i></div>
                <div>
                    <?php if (is_array(session('errors'))) : ?>
                        <?php foreach (session('errors') as $error) : ?>
                            <?= $error ?><br>
                        <?php endforeach ?>
                    <?php else : ?>
                        <?= session('errors') ?>
                    <?php endif ?>
                </div>
            </div>
        </div>
    <?php endif ?>

    <form action="<?= url_to('register') ?>" method="post" class="space-y-6">
        <?= csrf_field() ?>

        
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                <i class="fas fa-envelope mr-2"></i>Email Address
            </label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   inputmode="email" 
                   autocomplete="email" 
                   value="<?= old('email') ?>" 
                   required
                   class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition duration-200"
                   placeholder="Enter your email">
        </div>

        
        <div>
            <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
                <i class="fas fa-user mr-2"></i>Username
            </label>
            <input type="text" 
                   id="username" 
                   name="username" 
                   inputmode="text" 
                   autocomplete="username" 
                   value="<?= old('username') ?>" 
                   required
                   class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition duration-200"
                   placeholder="Choose a username">
        </div>

        
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                <i class="fas fa-lock mr-2"></i>Password
            </label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   inputmode="text" 
                   autocomplete="new-password" 
                   required
                   class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition duration-200"
                   placeholder="Create a strong password">
        </div>

        
        <div>
            <label for="password_confirm" class="block text-sm font-medium text-gray-700 mb-1">
                <i class="fas fa-lock mr-2"></i>Confirm Password
            </label>
            <input type="password" 
                   id="password_confirm" 
                   name="password_confirm" 
                   inputmode="text" 
                   autocomplete="new-password" 
                   required
                   class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition duration-200"
                   placeholder="Confirm your password">
        </div>

        
        <div>
            <button type="submit" 
                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200 transform hover:scale-105">
                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                    <i class="fas fa-user-plus h-5 w-5 text-indigo-500 group-hover:text-indigo-400"></i>
                </span>
                Create Account
            </button>
        </div>

        
        <div class="text-center">
            <p class="text-sm">
                Already have an account? 
                <a href="<?= url_to('login') ?>" class="text-indigo-600 hover:text-indigo-500 font-medium transition duration-200">
                    Sign in here
                </a>
            </p>
        </div>
    </form>
</div>

<?= $this->endSection() ?>
