<?php echo $this->extend('auth/layout') ?>

<?php echo $this->section('title') ?>Login<?php echo $this->endSection() ?>

<?php echo $this->section('main') ?>

<div class="glass-effect rounded-xl shadow-2xl p-8">
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-gray-800">Welcome Back</h2>
        <p class="text-gray-600 mt-2">Sign in to your account</p>
    </div>

    
    <?php if (session('error') !== null) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
            <div class="flex">
                <div class="py-1"><i class="fas fa-exclamation-circle mr-2"></i></div>
                <div><?php echo session('error') ?></div>
            </div>
        </div>
    <?php elseif (session('errors') !== null) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
            <div class="flex">
                <div class="py-1"><i class="fas fa-exclamation-circle mr-2"></i></div>
                <div>
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

    
    <?php if (session('message') !== null) : ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
            <div class="flex">
                <div class="py-1"><i class="fas fa-check-circle mr-2"></i></div>
                <div><?php echo session('message') ?></div>
            </div>
        </div>
    <?php endif ?>

    <form action="<?php echo url_to('login') ?>" method="post" class="space-y-6">
        <?php echo csrf_field() ?>

        
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                <i class="fas fa-envelope mr-2"></i>Email Address
            </label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   inputmode="email" 
                   autocomplete="email" 
                   value="<?php echo old('email') ?>" 
                   required
                   class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition duration-200"
                   placeholder="Enter your email">
        </div>

        
        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                <i class="fas fa-lock mr-2"></i>Password
            </label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   inputmode="text" 
                   autocomplete="current-password" 
                   required
                   class="appearance-none rounded-lg relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm transition duration-200"
                   placeholder="Enter your password">
        </div>

        
        <?php if (setting('Auth.sessionConfig')['allowRemembering']) : ?>
            <div class="flex items-center">
                <input id="remember" 
                       name="remember" 
                       type="checkbox" 
                       <?php if (old('remember')) : ?> checked<?php 
                       endif ?>
                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="remember" class="ml-2 block text-sm text-gray-700">
                    Remember me
                </label>
            </div>
        <?php endif; ?>

        
        <div>
            <button type="submit" 
                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-200 transform hover:scale-105">
                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                    <i class="fas fa-sign-in-alt h-5 w-5 text-indigo-500 group-hover:text-indigo-400"></i>
                </span>
                Sign In
            </button>
        </div>

        
        <div class="text-center space-y-2">
            <?php if (setting('Auth.allowMagicLinkLogins')) : ?>
                <p class="text-sm">
                    <a href="<?php echo url_to('magic-link') ?>" class="text-indigo-600 hover:text-indigo-500 transition duration-200">
                        <i class="fas fa-magic mr-1"></i>Forgot your password?
                    </a>
                </p>
            <?php endif ?>

            <?php if (setting('Auth.allowRegistration')) : ?>
                <p class="text-sm">
                    Don't have an account? 
                    <a href="<?php echo url_to('register') ?>" class="text-indigo-600 hover:text-indigo-500 font-medium transition duration-200">
                        Create one now
                    </a>
                </p>
            <?php endif ?>
        </div>
    </form>
</div>

<?php echo $this->endSection() ?>
